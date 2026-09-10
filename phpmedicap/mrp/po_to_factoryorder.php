<?php
/**
 * Medicap — after Marketing PO Approval (/marketing/po/approval),
 * push approved PO product lines into Factory Order Pending
 * (/planning/mrp/Factoryorder).
 *
 * Endpoints:
 *   type=pushApprovedPoToFactoryOrder   (po_id or order_no)
 *   type=syncPlantApprovedPosToFactoryOrder  (plant_id) — backfill
 *
 * Also included by marketing/po.php after status=Approved.
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/po-to-factoryorder-error.log');

if (!defined('MRP_PO_FO_HELPERS')) {
    define('MRP_PO_FO_HELPERS', true);

    function mrp_po_fo_esc($conn, $val) {
        return $conn->real_escape_string((string)($val ?? ''));
    }

    function mrp_po_fo_add_column_if_missing($conn, $table, $column, $definition) {
        $tableEsc = mrp_po_fo_esc($conn, $table);
        $columnEsc = mrp_po_fo_esc($conn, $column);
        $check = $conn->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
        if ($check && $check->num_rows > 0) {
            return;
        }
        @$conn->query("ALTER TABLE `{$tableEsc}` ADD COLUMN `{$columnEsc}` {$definition}");
    }

    /** Ensure tables/columns needed for Factory Order feed. */
    function mrp_po_fo_ensure_schema($conn) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $omExists = $conn->query("SHOW TABLES LIKE 'order_materials'");
        if ($omExists && $omExists->num_rows > 0) {
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'reqStatus', "TEXT DEFAULT 'pending'");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'plant_id', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'order_no', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'product_code', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'order_qty', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'unit', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'pack_size', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'details', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'quantity_inpacks', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'deliveryDate', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'delivery_deadline_date', "VARCHAR(20) DEFAULT NULL");
        }

        $peExists = $conn->query("SHOW TABLES LIKE 'po_entry'");
        if ($peExists && $peExists->num_rows > 0) {
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'status', "VARCHAR(50) DEFAULT 'pending'");
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'approve_by', "VARCHAR(50) DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'approve_date', "VARCHAR(50) DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'remark', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'approve_digital_signature', "TEXT DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'approve_digital_signature_date', "VARCHAR(50) DEFAULT NULL");
            mrp_po_fo_add_column_if_missing($conn, 'po_entry', 'plant_id', "TEXT DEFAULT NULL");
        }
    }

    /**
     * Load po_entry by id or order_no.
     * @return array|null
     */
    function mrp_po_fo_load_po($conn, $poId = 0, $orderNo = '') {
        $poId = (int)$poId;
        $orderNo = trim((string)$orderNo);
        if ($poId > 0) {
            $res = $conn->query("SELECT * FROM po_entry WHERE id=" . $poId . " LIMIT 1");
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc();
            }
        }
        if ($orderNo !== '') {
            $esc = mrp_po_fo_esc($conn, $orderNo);
            $res = $conn->query("SELECT * FROM po_entry WHERE order_no='{$esc}' ORDER BY id DESC LIMIT 1");
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc();
            }
        }
        return null;
    }

    /**
     * Insert missing order_materials lines from po_entry.products JSON if needed.
     */
    function mrp_po_fo_ensure_order_material_lines($conn, array $po, $plantId) {
        $orderNo = trim((string)($po['order_no'] ?? ''));
        if ($orderNo === '') {
            return 0;
        }
        $orderEsc = mrp_po_fo_esc($conn, $orderNo);
        $plantEsc = mrp_po_fo_esc($conn, $plantId);

        $poId = (int)($po['id'] ?? 0);
        $existing = $conn->query(
            "SELECT COUNT(*) AS c FROM order_materials
             WHERE order_no='{$orderEsc}'" . ($poId > 0 ? " OR po_entry_id={$poId}" : '')
        );
        $count = 0;
        if ($existing && ($r = $existing->fetch_assoc())) {
            $count = (int)($r['c'] ?? 0);
        }
        if ($count > 0) {
            return 0;
        }

        $products = json_decode($po['products'] ?? '[]', true);
        if (!is_array($products) || count($products) === 0) {
            return 0;
        }

        $inserted = 0;
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $productCode = mrp_po_fo_esc($conn, $product['product_code'] ?? '');
            if ($productCode === '') {
                continue;
            }
            $unit = mrp_po_fo_esc($conn, $product['unit'] ?? ($product['planUnit'] ?? ''));
            $quantity = mrp_po_fo_esc($conn, $product['quantity'] ?? ($product['order_qty'] ?? ($product['planQty'] ?? '0')));
            $qtyInPacks = mrp_po_fo_esc($conn, $product['quantity_inpacks'] ?? '');
            $deliveryDate = mrp_po_fo_esc($conn, $product['delivery_date'] ?? ($product['deliveryDate'] ?? ($po['deliveryDate'] ?? '')));
            $packSize = $product['pack_size'] ?? array(
                'pack_size' => $product['packingStyle'] ?? '',
                'unit' => $product['packingUnit'] ?? ($product['planUnit'] ?? ''),
            );
            $packSizeJson = mrp_po_fo_esc($conn, json_encode($packSize));
            $detailsJson = mrp_po_fo_esc($conn, json_encode($product));
            $poEntryCol = $poId > 0 ? ', po_entry_id' : '';
            $poEntryVal = $poId > 0 ? (", '{$poId}'") : '';

            $sql = "INSERT INTO order_materials
                    (plant_id, order_no, product_code, unit, order_qty, pack_size, details, quantity_inpacks, deliveryDate, reqStatus{$poEntryCol})
                    VALUES
                    ('{$plantEsc}', '{$orderEsc}', '{$productCode}', '{$unit}', '{$quantity}',
                     '{$packSizeJson}', '{$detailsJson}', '{$qtyInPacks}', '{$deliveryDate}', 'pending'{$poEntryVal})";
            if ($conn->query($sql)) {
                $inserted++;
            }
        }
        return $inserted;
    }

    /**
     * After Marketing PO Approval → make lines visible on Factory Order Pending.
     * Medicap stores planQty/planUnit; Factory Order reads order_qty/unit + reqStatus=pending.
     *
     * @return array{status:string,message?:string,order_no?:string,updated?:int,inserted?:int}
     */
    function mrp_po_fo_push_approved_po($conn, $poId = 0, $orderNo = '', $plantId = '', $empId = '') {
        mrp_po_fo_ensure_schema($conn);
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'planQty', 'TEXT DEFAULT NULL');
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'plan_qty', 'TEXT DEFAULT NULL');
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'planUnit', 'TEXT DEFAULT NULL');
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'packingStyle', 'TEXT DEFAULT NULL');
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'packingUnit', 'TEXT DEFAULT NULL');
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'po_entry_id', 'INT DEFAULT NULL');
        mrp_po_fo_add_column_if_missing($conn, 'order_materials', 'status', 'TEXT DEFAULT NULL');

        $po = mrp_po_fo_load_po($conn, $poId, $orderNo);
        if (!$po) {
            return array('status' => 'error', 'message' => 'PO not found');
        }

        $poId = (int)($po['id'] ?? $poId);
        $orderNo = trim((string)($po['order_no'] ?? ''));
        $orderEsc = mrp_po_fo_esc($conn, $orderNo);
        if ($orderNo === '' && $poId <= 0) {
            return array('status' => 'error', 'message' => 'PO has no order_no');
        }

        $plantId = trim((string)$plantId);
        if ($plantId === '') {
            $plantId = trim((string)($po['plant_id'] ?? ''));
        }
        if ($plantId === '' && $orderEsc !== '') {
            $pRes = $conn->query("SELECT plant_id FROM order_materials WHERE order_no='{$orderEsc}' AND TRIM(IFNULL(plant_id,''))<>'' LIMIT 1");
            if ($pRes && ($pr = $pRes->fetch_assoc())) {
                $plantId = trim((string)($pr['plant_id'] ?? ''));
            }
        }
        if ($plantId === '' && $poId > 0) {
            $pRes = $conn->query("SELECT plant_id FROM order_materials WHERE po_entry_id={$poId} AND TRIM(IFNULL(plant_id,''))<>'' LIMIT 1");
            if ($pRes && ($pr = $pRes->fetch_assoc())) {
                $plantId = trim((string)($pr['plant_id'] ?? ''));
            }
        }
        $plantEsc = mrp_po_fo_esc($conn, $plantId);

        $empEsc = mrp_po_fo_esc($conn, $empId);
        $now = date('Y-m-d H:i:s');
        $plantSet = $plantEsc !== ''
            ? ", plant_id='{$plantEsc}'"
            : '';
        $conn->query(
            "UPDATE po_entry SET status='Approved',
                approve_by=CASE WHEN TRIM(IFNULL(approve_by,''))='' THEN '{$empEsc}' ELSE approve_by END,
                approve_date=CASE WHEN TRIM(IFNULL(approve_date,''))='' THEN '{$now}' ELSE approve_date END
                {$plantSet}
             WHERE id={$poId}"
        );

        $inserted = mrp_po_fo_ensure_order_material_lines($conn, $po, $plantId);

        $whereLines = array();
        if ($orderEsc !== '') {
            $whereLines[] = "order_no='{$orderEsc}'";
        }
        if ($poId > 0) {
            $whereLines[] = "po_entry_id={$poId}";
        }
        if (!$whereLines) {
            return array('status' => 'error', 'message' => 'No order_materials match key');
        }
        $whereSql = '(' . implode(' OR ', $whereLines) . ')';

        // Sync medicap plan fields → FO fields + set reqStatus pending (skip locked pipeline rows)
        $sel = $conn->query("SELECT * FROM order_materials WHERE {$whereSql}");
        $updated = 0;
        if ($sel) {
            while ($line = $sel->fetch_assoc()) {
                $lineId = (int)($line['id'] ?? 0);
                if ($lineId <= 0) {
                    continue;
                }
                $req = strtolower(trim((string)($line['reqStatus'] ?? '')));
                $locked = array(
                    'inprocess', 'send for requirement', 'send for requirement analysis',
                    'complete', 'dispatch approved', 'cancelled', 'canceled'
                );
                if (in_array($req, $locked, true)) {
                    continue;
                }

                $qty = trim((string)($line['order_qty'] ?? ''));
                if ($qty === '' || $qty === '0') {
                    $qty = trim((string)($line['planQty'] ?? ($line['plan_qty'] ?? ($po['plan_qty'] ?? '0'))));
                }
                $unit = trim((string)($line['unit'] ?? ''));
                if ($unit === '') {
                    $unit = trim((string)($line['planUnit'] ?? ($line['packingUnit'] ?? '')));
                }
                $packRaw = trim((string)($line['pack_size'] ?? ''));
                if ($packRaw === '' || $packRaw === 'null' || $packRaw === '{}' || json_decode($packRaw) === null) {
                    $packRaw = json_encode(array(
                        'pack_size' => (string)($line['packingStyle'] ?? ''),
                        'unit' => (string)($line['packingUnit'] ?? $unit),
                    ));
                }
                $delivery = trim((string)($line['deliveryDate'] ?? ''));
                $qtyEsc = mrp_po_fo_esc($conn, $qty);
                $unitEsc = mrp_po_fo_esc($conn, $unit);
                $packEsc = mrp_po_fo_esc($conn, $packRaw);
                $delEsc = mrp_po_fo_esc($conn, $delivery);
                $plantSql = $plantEsc !== '' ? ", plant_id='{$plantEsc}'" : '';
                $orderSql = $orderEsc !== '' ? ", order_no='{$orderEsc}'" : '';
                $poEntrySql = $poId > 0 ? ", po_entry_id={$poId}" : '';

                $ok = $conn->query(
                    "UPDATE order_materials SET
                        reqStatus='pending',
                        order_qty='{$qtyEsc}',
                        unit='{$unitEsc}',
                        pack_size='{$packEsc}',
                        deliveryDate=CASE WHEN TRIM(IFNULL(deliveryDate,''))='' THEN '{$delEsc}' ELSE deliveryDate END
                        {$plantSql}
                        {$orderSql}
                        {$poEntrySql}
                     WHERE id={$lineId}"
                );
                if ($ok) {
                    $updated++;
                }
            }
        }

        // Count how many FO-ready lines exist now
        $ready = 0;
        $readySql = "SELECT COUNT(*) AS c FROM order_materials
                     WHERE {$whereSql}
                       AND LOWER(TRIM(IFNULL(reqStatus,'')))='pending'";
        if ($plantEsc !== '') {
            $readySql .= " AND plant_id='{$plantEsc}'";
        }
        $readyRes = $conn->query($readySql);
        if ($readyRes && ($rr = $readyRes->fetch_assoc())) {
            $ready = (int)($rr['c'] ?? 0);
        }

        return array(
            'status' => $ready > 0 ? 'success' : 'error',
            'order_no' => $orderNo,
            'po_id' => $poId,
            'plant_id' => $plantId,
            'updated' => $updated,
            'inserted' => $inserted,
            'ready_lines' => $ready,
            'message' => $ready > 0
                ? 'PO pushed to Factory Order Pending'
                : 'Approved but no order_materials lines ready for Factory Order (check plant_id / product lines)',
        );
    }

    /**
     * Backfill: all Approved POs for a plant → Factory Order pending lines.
     */
    function mrp_po_fo_sync_plant_approved($conn, $plantId) {
        mrp_po_fo_ensure_schema($conn);
        $plantId = trim((string)$plantId);
        $results = array();
        $sql = "SELECT id, order_no FROM po_entry
                WHERE LOWER(TRIM(IFNULL(status,''))) IN ('approved','approve')";
        if ($plantId !== '') {
            $plantEsc = mrp_po_fo_esc($conn, $plantId);
            $sql .= " AND (TRIM(IFNULL(plant_id,''))='' OR plant_id='{$plantEsc}')";
        }
        $sql .= " ORDER BY id DESC LIMIT 2000";
        $res = $conn->query($sql);
        $ok = 0;
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $r = mrp_po_fo_push_approved_po($conn, (int)$row['id'], $row['order_no'] ?? '', $plantId, '');
                if (($r['status'] ?? '') === 'success') {
                    $ok++;
                }
                $results[] = $r;
            }
        }
        return array('status' => 'success', 'synced' => $ok, 'items' => $results);
    }
}

// Standalone HTTP entry (when called as mrp/po_to_factoryorder.php)
// Uses factoryorder-style auth — do not require mrp/_bootstrap.php (often missing on host).
$isDirect = (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
if ($isDirect) {
    ini_set('display_errors', 0);
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../token.php';

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');

    $token = $_GET['token'] ?? '';
    $tokenEsc = $conn->real_escape_string((string)$token);
    $tres = $conn->query("SELECT * FROM token WHERE token='" . $tokenEsc . "'");
    $auth_ok = false;
    $_GET['emp_id'] = $_GET['emp_id'] ?? '';
    if ($tres && $tres->num_rows > 0) {
        while ($row = $tres->fetch_assoc()) {
            $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
            $parts = explode('$', $string);
            $_GET['emp_id'] = $parts[0] ?? '';
            break;
        }
        $auth_ok = true;
    }
    if (!$auth_ok) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid or missing token'));
        exit;
    }

    $type = $_GET['type'] ?? '';
    $plantId = $_GET['plant_id'] ?? '';
    $empId = $_GET['emp_id'] ?? '';
    $poId = (int)($_GET['po_id'] ?? $_GET['id'] ?? 0);
    $orderNo = $_GET['order_no'] ?? '';

    if ($type === 'pushApprovedPoToFactoryOrder') {
        echo json_encode(mrp_po_fo_push_approved_po($conn, $poId, $orderNo, $plantId, $empId));
        exit;
    }
    if ($type === 'syncPlantApprovedPosToFactoryOrder') {
        echo json_encode(mrp_po_fo_sync_plant_approved($conn, $plantId));
        exit;
    }
    echo json_encode(array('status' => 'error', 'message' => 'Unknown type'));
    exit;
}
