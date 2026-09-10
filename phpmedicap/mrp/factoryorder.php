<?php
/**
 * Medicap Factory Order (Process Order Planning) API
 * Separate from mrp.php — used by planning/mrp/Factoryorder
 * Ported from zuma backend/phpzuma/mrp/mrp.php factory-order handlers.
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/factoryorder-error.log');

require '../db.php';
require '../token.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"] ?? "";
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents("php://input"), true);

function mrp_esc_str($conn, $val) {
    return $conn->real_escape_string((string)($val ?? ''));
}

function mrp_esc_int($val) {
    return (int)$val;
}

function mrp_esc_float($val) {
    return is_numeric($val) ? (float)$val : 0.0;
}

/** In-request cache for material purchase lead times (batched by code list). */
function mrp_material_lead_cache() {
    static $cache = array();
    return $cache;
}

function mrp_prefetch_material_lead_for_codes($conn, array $materialCodes) {
    $cache = &mrp_material_lead_cache();
    $missing = array();
    foreach (array_unique($materialCodes) as $code) {
        if ($code !== '' && !array_key_exists($code, $cache)) {
            $missing[] = $code;
        }
    }
    if (empty($missing)) {
        return $cache;
    }
    $escaped = array();
    foreach ($missing as $code) {
        $escaped[] = "'" . $conn->real_escape_string($code) . "'";
    }
    $inList = implode(',', $escaped);
    $tables = array('material', 'others_material');
    foreach ($tables as $table) {
        $sql = "SELECT material_code,
                       COALESCE(indent_approve_date, 0) AS indent_approve_date,
                       COALESCE(Purchase_prepare_date, 0) AS Purchase_prepare_date,
                       COALESCE(ForPayment, 0) AS ForPayment,
                       COALESCE(PurchaseDeliveryTime, 0) AS PurchaseDeliveryTime
                FROM " . $table . " WHERE material_code IN (" . $inList . ")";
        $result = $conn->query($sql);
        if (!$result) {
            continue;
        }
        while ($row = $result->fetch_assoc()) {
            $code = $row['material_code'] ?? '';
            if ($code === '' || array_key_exists($code, $cache)) {
                continue;
            }
            $cache[$code] = (int)$row['indent_approve_date']
                + (int)$row['Purchase_prepare_date']
                + (int)$row['ForPayment']
                + (int)$row['PurchaseDeliveryTime'];
        }
    }
    foreach ($missing as $code) {
        if (!array_key_exists($code, $cache)) {
            $cache[$code] = 0;
        }
    }
    return $cache;
}

/** Purchase lead time (days) = MRP Log total receiving, same as planning/mrp/Log. */
function mrp_get_material_receiving_lead_days($conn, $materialCode) {
    if ($materialCode === null || $materialCode === '') {
        return 0;
    }
    mrp_prefetch_material_lead_for_codes($conn, array($materialCode));
    $cache = mrp_material_lead_cache();
    return $cache[$materialCode] ?? 0;
}

/** Batch FG available stock for Factory Order list (one query for all products). */
function mrp_prefetch_fg_available_stock_map($conn, array $productCodes) {
    $map = array();
    $codes = array_values(array_unique(array_filter($productCodes)));
    if (empty($codes)) {
        return $map;
    }
    $escaped = array();
    foreach ($codes as $code) {
        $escaped[] = "'" . $conn->real_escape_string($code) . "'";
    }
    $inList = implode(',', $escaped);
    $sql = "SELECT a.material_code,
                   SUM(a.qty) AS stock_qty,
                   IFNULL(b.issued_qty, 0) AS issued_qty,
                   IFNULL(c.booked_qty, 0) AS booked_qty
            FROM fg_stock_book a
            LEFT JOIN (
                SELECT material_code, SUM(qty) AS issued_qty
                FROM material_issue GROUP BY material_code
            ) b ON a.material_code = b.material_code
            LEFT JOIN (
                SELECT material_code, SUM(qty) AS booked_qty
                FROM mrp_bookedStock GROUP BY material_code
            ) c ON a.material_code = c.material_code
            WHERE a.material_code IN ($inList)
            GROUP BY a.material_code, b.issued_qty, c.booked_qty";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $map[$row['material_code']] = max(0, (float)$row['stock_qty']
                - (float)$row['issued_qty']
                - (float)$row['booked_qty']);
        }
    }
    foreach ($codes as $code) {
        if (!isset($map[$code])) {
            $map[$code] = 0;
        }
    }
    return $map;
}

/** Create table if missing; add each column only when it does not already exist. */
function mrp_fo_add_column_if_missing($conn, $table, $column, $definition) {
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM `" . $tableEsc . "` LIKE '" . $columnEsc . "'");
    if ($check && $check->num_rows > 0) {
        return;
    }
    @$conn->query("ALTER TABLE `" . $tableEsc . "` ADD COLUMN `" . $columnEsc . "` " . $definition);
}

/**
 * Factory Order schema guard for medicap.
 * - CREATE TABLE IF NOT EXISTS for FO tables
 * - ADD COLUMN only when missing (existing tables stay intact)
 */
function mrp_ensure_factoryorder_schema($conn) {
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mrp_bookedStock (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id TEXT DEFAULT NULL,
            qty TEXT DEFAULT NULL,
            material_code TEXT DEFAULT NULL,
            order_no TEXT DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mrp_dispatch_approval (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) DEFAULT NULL,
            order_no VARCHAR(100) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            unit VARCHAR(50) DEFAULT NULL,
            order_qty DECIMAL(18,3) DEFAULT 0,
            dispatch_qty DECIMAL(18,3) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'Pending',
            requested_by VARCHAR(100) DEFAULT NULL,
            requested_on DATETIME DEFAULT NULL,
            approved_by VARCHAR(100) DEFAULT NULL,
            approved_on DATETIME DEFAULT NULL,
            remark VARCHAR(255) DEFAULT NULL,
            KEY idx_status (status),
            KEY idx_order (order_no, product_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS split_planning_qty (
            id INT(11) NOT NULL AUTO_INCREMENT,
            order_no TEXT DEFAULT NULL,
            plant_id TEXT DEFAULT NULL,
            product_name TEXT DEFAULT NULL,
            product_code TEXT DEFAULT NULL,
            date TEXT DEFAULT NULL,
            month TEXT DEFAULT NULL,
            year TEXT DEFAULT NULL,
            oder_qty TEXT DEFAULT NULL,
            Qty TEXT DEFAULT NULL,
            outQty TEXT DEFAULT NULL,
            InQty TEXT DEFAULT NULL,
            balance_qty TEXT DEFAULT NULL,
            avbl_stock TEXT DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Pending',
            demand_source VARCHAR(20) DEFAULT 'CONFIRMED',
            demand_reference_type VARCHAR(40) DEFAULT NULL,
            demand_reference_no VARCHAR(120) DEFAULT NULL,
            remarkText TEXT DEFAULT NULL,
            entry_by TEXT DEFAULT NULL,
            planUnit VARCHAR(50) DEFAULT NULL,
            unit VARCHAR(50) DEFAULT NULL,
            delivery_deadline_date VARCHAR(20) DEFAULT NULL,
            deliveryDate VARCHAR(20) DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Existing split_planning_qty — add FO columns if missing
    $splitCols = array(
        'Qty' => "TEXT DEFAULT NULL",
        'InQty' => "TEXT DEFAULT NULL",
        'outQty' => "TEXT DEFAULT NULL",
        'balance_qty' => "TEXT DEFAULT NULL",
        'avbl_stock' => "TEXT DEFAULT NULL",
        'status' => "VARCHAR(50) DEFAULT 'Pending'",
        'demand_source' => "VARCHAR(20) DEFAULT 'CONFIRMED'",
        'demand_reference_type' => "VARCHAR(40) DEFAULT NULL",
        'demand_reference_no' => "VARCHAR(120) DEFAULT NULL",
        'unit' => "VARCHAR(50) DEFAULT NULL",
        'planUnit' => "VARCHAR(50) DEFAULT NULL",
        'plant_id' => "TEXT DEFAULT NULL",
        'product_name' => "TEXT DEFAULT NULL",
        'product_code' => "TEXT DEFAULT NULL",
        'order_no' => "TEXT DEFAULT NULL",
        'date' => "TEXT DEFAULT NULL",
        'month' => "TEXT DEFAULT NULL",
        'year' => "TEXT DEFAULT NULL",
        'oder_qty' => "TEXT DEFAULT NULL",
        'entry_by' => "TEXT DEFAULT NULL",
        'deliveryDate' => "VARCHAR(20) DEFAULT NULL",
        'delivery_deadline_date' => "VARCHAR(20) DEFAULT NULL",
        'remarkText' => "TEXT DEFAULT NULL",
        'batches' => "LONGTEXT DEFAULT NULL",
        'work_order_planned_qty' => "VARCHAR(50) DEFAULT NULL",
    );
    foreach ($splitCols as $col => $def) {
        mrp_fo_add_column_if_missing($conn, 'split_planning_qty', $col, $def);
    }

    // order_materials — FO list / send columns
    $omCols = array(
        'reqStatus' => "TEXT DEFAULT 'pending'",
        'deliveryDate' => "TEXT DEFAULT NULL",
        'delivery_deadline_date' => "VARCHAR(20) DEFAULT NULL",
        'send_by' => "VARCHAR(30) DEFAULT NULL",
        'send_on' => "VARCHAR(30) DEFAULT NULL",
        'history' => "LONGTEXT DEFAULT NULL",
        'pack_size' => "TEXT DEFAULT NULL",
        'unit' => "TEXT DEFAULT NULL",
        'order_qty' => "TEXT DEFAULT NULL",
        'plant_id' => "TEXT DEFAULT NULL",
        'order_no' => "TEXT DEFAULT NULL",
        'product_code' => "TEXT DEFAULT NULL",
    );
    // Only alter if table exists
    $omExists = $conn->query("SHOW TABLES LIKE 'order_materials'");
    if ($omExists && $omExists->num_rows > 0) {
        foreach ($omCols as $col => $def) {
            mrp_fo_add_column_if_missing($conn, 'order_materials', $col, $def);
        }
    }

    $bookCols = array(
        'plant_id' => "TEXT DEFAULT NULL",
        'qty' => "TEXT DEFAULT NULL",
        'material_code' => "TEXT DEFAULT NULL",
        'order_no' => "TEXT DEFAULT NULL",
    );
    foreach ($bookCols as $col => $def) {
        mrp_fo_add_column_if_missing($conn, 'mrp_bookedStock', $col, $def);
    }

    $dispatchCols = array(
        'plant_id' => "VARCHAR(50) DEFAULT NULL",
        'order_no' => "VARCHAR(100) DEFAULT NULL",
        'product_code' => "VARCHAR(100) DEFAULT NULL",
        'product_name' => "VARCHAR(255) DEFAULT NULL",
        'unit' => "VARCHAR(50) DEFAULT NULL",
        'order_qty' => "DECIMAL(18,3) DEFAULT 0",
        'dispatch_qty' => "DECIMAL(18,3) DEFAULT 0",
        'status' => "VARCHAR(30) DEFAULT 'Pending'",
        'requested_by' => "VARCHAR(100) DEFAULT NULL",
        'requested_on' => "DATETIME DEFAULT NULL",
        'approved_by' => "VARCHAR(100) DEFAULT NULL",
        'approved_on' => "DATETIME DEFAULT NULL",
        'remark' => "VARCHAR(255) DEFAULT NULL",
    );
    foreach ($dispatchCols as $col => $def) {
        mrp_fo_add_column_if_missing($conn, 'mrp_dispatch_approval', $col, $def);
    }

    $ensured = true;
}

/** Create the dispatch-approval queue table on first use (no-op if it exists). */
function mrp_ensure_dispatch_table($conn) {
    mrp_ensure_factoryorder_schema($conn);
}

/** Minimal schema guard so split rows can carry confirmed-vs-forecast identity. */
function mrp_ensure_split_demand_columns($conn) {
    mrp_ensure_factoryorder_schema($conn);
}

/** Sum of FG qty already reserved by Pending dispatch requests, per product_code. */
function mrp_prefetch_pending_dispatch_map($conn, array $productCodes) {
    mrp_ensure_dispatch_table($conn);
    $map = array();
    $codes = array_values(array_unique(array_filter($productCodes)));
    if (empty($codes)) {
        return $map;
    }
    $escaped = array();
    foreach ($codes as $code) {
        $escaped[] = "'" . $conn->real_escape_string($code) . "'";
    }
    $inList = implode(',', $escaped);
    $sql = "SELECT product_code, SUM(dispatch_qty) AS reserved
            FROM mrp_dispatch_approval
            WHERE status = 'Pending' AND product_code IN ($inList)
            GROUP BY product_code";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $map[$row['product_code']] = max(0, (float)$row['reserved']);
        }
    }
    return $map;
}

/** Per-order dispatch request status keyed by order_no|product_code (latest non-rejected). */
function mrp_prefetch_dispatch_status_map($conn, array $rows) {
    mrp_ensure_dispatch_table($conn);
    $map = array();
    $pairs = array();
    foreach ($rows as $row) {
        $on = $conn->real_escape_string($row['order_no'] ?? '');
        $pc = $conn->real_escape_string($row['product_code'] ?? '');
        if ($on === '' && $pc === '') {
            continue;
        }
        $pairs["('$on','$pc')"] = true;
    }
    if (empty($pairs)) {
        return $map;
    }
    $inList = implode(',', array_keys($pairs));
    $sql = "SELECT order_no, product_code, status, dispatch_qty
            FROM mrp_dispatch_approval
            WHERE status IN ('Pending','Approved') AND (order_no, product_code) IN ($inList)";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $key = ($row['order_no'] ?? '') . '|' . ($row['product_code'] ?? '');
            // Pending wins over Approved for button disabling purposes.
            if (!isset($map[$key]) || $row['status'] === 'Pending') {
                $map[$key] = array(
                    'status' => $row['status'],
                    'dispatch_qty' => (float)$row['dispatch_qty'],
                );
            }
        }
    }
    return $map;
}

/**
 * Allocate FG stock cumulatively across orders of the same product (earliest PO first).
 * Mutates each row, adding net_fg_stock, fg_dispatchable, produce_qty, fg_balance_after,
 * fg_sufficient and dispatch_status. Quantities are never negative.
 */
function mrp_apply_cumulative_fg_allocation(&$rows, array $fgStockMap, array $reservedMap = array(), array $dispatchStatusMap = array()) {
    if (!is_array($rows) || empty($rows)) {
        return;
    }
    $order = array_keys($rows);
    usort($order, function ($i, $j) use ($rows) {
        $di = isset($rows[$i]['po_date']) ? strtotime((string)$rows[$i]['po_date']) : 0;
        $dj = isset($rows[$j]['po_date']) ? strtotime((string)$rows[$j]['po_date']) : 0;
        $di = $di ?: 0;
        $dj = $dj ?: 0;
        if ($di === $dj) {
            return ((int)($rows[$i]['pid'] ?? 0)) - ((int)($rows[$j]['pid'] ?? 0));
        }
        return $di - $dj;
    });

    $remaining = array();
    foreach ($order as $idx) {
        $pc = $rows[$idx]['product_code'] ?? '';
        if (!array_key_exists($pc, $remaining)) {
            $base = (float)($fgStockMap[$pc] ?? 0) - (float)($reservedMap[$pc] ?? 0);
            $remaining[$pc] = max(0, $base);
        }
        $orderQty = max(0, (float)($rows[$idx]['order_qty'] ?? 0));
        $avail = max(0, $remaining[$pc]);
        $dispatchable = min($avail, $orderQty);

        $rows[$idx]['net_fg_stock'] = $avail;
        $rows[$idx]['avblStock'] = $avail;
        $rows[$idx]['fg_dispatchable'] = $dispatchable;
        $rows[$idx]['produce_qty'] = max(0, $orderQty - $dispatchable);
        $rows[$idx]['fg_balance_after'] = max(0, $avail - $dispatchable);
        $rows[$idx]['fg_sufficient'] = ($orderQty > 0 && $dispatchable >= $orderQty) ? 1 : 0;

        $statusKey = ($rows[$idx]['order_no'] ?? '') . '|' . $pc;
        $rows[$idx]['dispatch_status'] = isset($dispatchStatusMap[$statusKey])
            ? $dispatchStatusMap[$statusKey]['status']
            : '';

        $remaining[$pc] = $rows[$idx]['fg_balance_after'];
    }
}

function mrp_collect_material_codes_from_raw_json($rawMaterialsJson) {
    $codes = array();
    $raw = is_string($rawMaterialsJson) ? json_decode($rawMaterialsJson, true) : $rawMaterialsJson;
    if (!is_array($raw)) {
        return $codes;
    }
    foreach ($raw as $mat) {
        if (is_array($mat) && !empty($mat['material_code'])) {
            $codes[] = $mat['material_code'];
        }
    }
    return $codes;
}

function mrp_row_pair_key($orderNo, $productCode) {
    return (string)$orderNo . "\0" . (string)$productCode;
}

/** One query for all splits on listed orders (avoids N+1 over remote DB). */
function mrp_prefetch_splits_map($conn, array $rows, $activeOnly = false) {
    $map = array();
    $orderNos = array();
    foreach ($rows as $row) {
        if (!empty($row['order_no'])) {
            $orderNos[] = $row['order_no'];
        }
    }
    $orderNos = array_values(array_unique($orderNos));
    if (empty($orderNos)) {
        return $map;
    }
    $escaped = array();
    foreach ($orderNos as $orderNo) {
        $escaped[] = "'" . $conn->real_escape_string($orderNo) . "'";
    }
    $inList = implode(',', $escaped);
    $activeSql = $activeOnly ? (' AND ' . mrp_factory_order_active_split_sql('a')) : '';
    $sql = "SELECT *, a.oder_qty AS Qty, a.balance_qty AS bal_qty
            FROM split_planning_qty a
            WHERE a.order_no IN ($inList)" . $activeSql;
    $result = $conn->query($sql);
    if (!$result) {
        return $map;
    }
    while ($row = $result->fetch_assoc()) {
        $key = mrp_row_pair_key($row['order_no'] ?? '', $row['product_code'] ?? '');
        if (!isset($map[$key])) {
            $map[$key] = array();
        }
        $map[$key][] = $row;
    }
    return $map;
}

/** Batch stock lookup for raw materials (2 queries total instead of 2 per material). */
function mrp_enrich_raw_materials_available_stock($conn, &$rawMaterials) {
    if (!is_array($rawMaterials) || empty($rawMaterials)) {
        return;
    }
    $codes = array();
    foreach ($rawMaterials as $mat) {
        if (is_array($mat) && !empty($mat['material_code'])) {
            $codes[] = $mat['material_code'];
        }
    }
    $codes = array_values(array_unique($codes));
    if (empty($codes)) {
        return;
    }
    $escaped = array();
    foreach ($codes as $code) {
        $escaped[] = "'" . $conn->real_escape_string($code) . "'";
    }
    $inList = implode(',', $escaped);

    $stockMap = array();
    $sqlStock = "SELECT material_code, COALESCE(SUM(qty), 0) AS total_stock, MAX(unit) AS stock_unit
                 FROM stock_book WHERE material_code IN ($inList) GROUP BY material_code";
    $resStock = $conn->query($sqlStock);
    if ($resStock) {
        while ($row = $resStock->fetch_assoc()) {
            $stockMap[$row['material_code']] = array(
                'stock' => (float)$row['total_stock'],
                'unit' => $row['stock_unit'],
            );
        }
    }

    $issueMap = array();
    $sqlIssue = "SELECT material_code, COALESCE(SUM(qty), 0) AS total_issue
                 FROM material_issue WHERE material_code IN ($inList) GROUP BY material_code";
    $resIssue = $conn->query($sqlIssue);
    if ($resIssue) {
        while ($row = $resIssue->fetch_assoc()) {
            $issueMap[$row['material_code']] = (float)$row['total_issue'];
        }
    }

    foreach ($rawMaterials as $key => $material) {
        if (!is_array($material) || empty($material['material_code'])) {
            continue;
        }
        $code = $material['material_code'];
        $totalStock = $stockMap[$code]['stock'] ?? 0;
        $totalStockUnit = $stockMap[$code]['unit'] ?? null;
        $totalIssue = $issueMap[$code] ?? 0;
        $rawMaterials[$key]['avbl_stock'] = max(0, (float)$totalStock - (float)$totalIssue);
        $rawMaterials[$key]['avbl_stock_unit'] = $totalStockUnit;
    }
}

function mrp_max_purchase_lead_from_raw_materials($conn, $rawMaterials) {
    if (!is_array($rawMaterials)) {
        return 0;
    }
    $max = 0;
    foreach ($rawMaterials as $mat) {
        if (!is_array($mat) || empty($mat['material_code'])) {
            continue;
        }
        $days = mrp_get_material_receiving_lead_days($conn, $mat['material_code']);
        if ($days > $max) {
            $max = $days;
        }
    }
    return $max;
}

function mrp_enrich_factory_order_lead_times($conn, &$row) {
    if (isset($row['raw_materials']) && is_string($row['raw_materials'])) {
        $decoded = json_decode($row['raw_materials'], true);
        $row['raw_materials'] = is_array($decoded) ? $decoded : array();
    }
    $purchaseLead = mrp_max_purchase_lead_from_raw_materials($conn, $row['raw_materials'] ?? null);
    $productionLead = (int)($row['productionDate'] ?? 0);
    $totalDelivery = $purchaseLead + $productionLead;
    $baseDateStr = !empty($row['po_date']) ? $row['po_date'] : date('Y-m-d');
    $baseTs = strtotime($baseDateStr);
    if ($baseTs === false) {
        $baseTs = time();
    }
    $expectedTs = strtotime('+' . $totalDelivery . ' days', $baseTs);
    $row['purchase_lead_time_days'] = $purchaseLead;
    $row['production_lead_time_days'] = $productionLead;
    $row['total_delivery_lead_time_days'] = $totalDelivery;
    $row['expected_delivery_date'] = $expectedTs ? date('Y-m-d', $expectedTs) : null;
}

/** True when a decoded material JSON/array list has at least one usable line. */
function mrp_json_material_list_nonempty($jsonOrArray) {
    if ($jsonOrArray === null || $jsonOrArray === '') {
        return false;
    }
    $list = is_string($jsonOrArray) ? json_decode($jsonOrArray, true) : $jsonOrArray;
    if (!is_array($list) || count($list) === 0) {
        return false;
    }
    foreach ($list as $item) {
        if (!is_array($item)) {
            continue;
        }
        $code = trim((string)($item['material_code'] ?? $item['Material_code'] ?? ''));
        if ($code !== '' && !in_array($code, array('-', 'N/A', 'NA'), true)) {
            return true;
        }
        $name = trim((string)($item['material_name'] ?? $item['Material_name'] ?? ''));
        $qty = $item['qty'] ?? $item['total_qty'] ?? $item['Qty'] ?? null;
        if ($name !== '' && $qty !== null && $qty !== '' && is_numeric($qty) && (float)$qty > 0) {
            return true;
        }
    }
    return false;
}

/** Nested batch_formula_info packing_materials groups. */
function mrp_bfr_packing_json_has_materials($jsonOrArray) {
    $groups = is_string($jsonOrArray) ? json_decode($jsonOrArray, true) : $jsonOrArray;
    if (!is_array($groups)) {
        return false;
    }
    foreach ($groups as $group) {
        if (!is_array($group)) {
            continue;
        }
        $list = $group['packing_list'] ?? null;
        if (mrp_json_material_list_nonempty($list)) {
            return true;
        }
    }
    return false;
}

/**
 * Factory Order BOM gate â€” same manufacturing plant as order_materials.plant_id.
 * Uses approved unit formula JSON, batch_formula_info JSON, and batch_materials rows.
 */
function mrp_factory_order_row_has_bom($conn, $productCode, $plantId, $rawMaterials = null, $packingMaterials = null) {
    $productCode = trim((string)$productCode);
    $plantId = trim((string)$plantId);
    if ($productCode === '') {
        return false;
    }
    if (mrp_json_material_list_nonempty($rawMaterials)) {
        return true;
    }
    if (mrp_json_material_list_nonempty($packingMaterials)) {
        return true;
    }

    if ($plantId === '') {
        return false;
    }

    $pcEsc = $conn->real_escape_string($productCode);
    $plEsc = $conn->real_escape_string($plantId);

    $ufSql = "SELECT raw_materials, packing_materials, primary_pm_list, consumeableMaterial, additional_materials
              FROM unitformula
              WHERE product_code = '$pcEsc'
                AND plant_id = '$plEsc'
                AND LOWER(TRIM(IFNULL(status,''))) = 'approve'
              ORDER BY id DESC LIMIT 1";
    $ufRes = $conn->query($ufSql);
    if ($ufRes && $ufRes->num_rows > 0) {
        $uf = $ufRes->fetch_assoc();
        foreach (array('raw_materials', 'packing_materials', 'primary_pm_list', 'consumeableMaterial', 'additional_materials') as $field) {
            if (mrp_json_material_list_nonempty($uf[$field] ?? null)) {
                return true;
            }
        }
    }

    $bfrSql = "SELECT bfr_no, raw_materials, packing_materials, consumable_materials, additional_materials
               FROM batch_formula_info
               WHERE product_code = '$pcEsc' AND plant_id = '$plEsc'
               ORDER BY id DESC LIMIT 5";
    $bfrRes = $conn->query($bfrSql);
    $bfrNos = array();
    if ($bfrRes) {
        while ($bfr = $bfrRes->fetch_assoc()) {
            if (mrp_json_material_list_nonempty($bfr['raw_materials'] ?? null)) {
                return true;
            }
            if (mrp_bfr_packing_json_has_materials($bfr['packing_materials'] ?? null)) {
                return true;
            }
            foreach (array('consumable_materials', 'additional_materials') as $field) {
                if (mrp_json_material_list_nonempty($bfr[$field] ?? null)) {
                    return true;
                }
            }
            $bfrNo = trim((string)($bfr['bfr_no'] ?? ''));
            if ($bfrNo !== '') {
                $bfrNos[] = $bfrNo;
            }
        }
    }

    $bfrNos = array_values(array_unique($bfrNos));
    if (count($bfrNos) > 0) {
        $bfrList = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $bfrNos)) . "'";
        $bmRes = $conn->query("SELECT COUNT(*) AS c FROM batch_materials
                               WHERE bfr_no IN ($bfrList)
                                 AND plant_id = '$plEsc'
                                 AND TRIM(IFNULL(material_code,'')) NOT IN ('', '-', 'N/A')");
        if ($bmRes && ($r = $bmRes->fetch_assoc()) && (int)($r['c'] ?? 0) > 0) {
            return true;
        }
    }

    return false;
}

/** SQL predicate: split still on Factory Order (not yet in Processing / WO). */
function mrp_factory_order_active_split_sql($alias = 'sp') {
    $a = $alias;
    return "(
        {$a}.status IS NULL
        OR TRIM(IFNULL({$a}.status,'')) = ''
        OR LOWER(TRIM({$a}.status)) = 'pending'
    )
    AND {$a}.id NOT IN (
        SELECT DISTINCT CAST(wom.doc_no AS UNSIGNED)
        FROM Work_order_materials wom
        WHERE wom.doc_no IS NOT NULL AND TRIM(wom.doc_no) != '' AND wom.doc_no != '0'
    )";
}

/** Strict normalizer for planning demand source. */
function mrp_normalize_demand_source($value) {
    $v = strtoupper(trim((string)$value));
    if ($v === 'FORECAST') {
        return 'FORECAST';
    }
    if ($v === 'CONFIRMED') {
        return 'CONFIRMED';
    }
    return '';
}

/** Infer demand source from PO type labels (handles legacy "forcast" spellings too). */
function mrp_demand_source_from_po_type($poType) {
    $t = strtolower(trim((string)$poType));
    if ($t === '') {
        return '';
    }
    if (preg_match('/(forecast|forcast|projection|estimate)/', $t)) {
        return 'FORECAST';
    }
    return 'CONFIRMED';
}

/** Resolve order-level demand context from PO header for split persistence. */
function mrp_get_order_demand_context($conn, $orderNo) {
    $out = array(
        'source' => 'CONFIRMED',
        'po_type' => '',
        'po_no' => '',
        'reference_type' => 'PO',
        'reference_no' => (string)$orderNo,
    );
    $orderNo = trim((string)$orderNo);
    if ($orderNo === '') {
        return $out;
    }
    $orderEsc = $conn->real_escape_string($orderNo);
    $res = $conn->query(
        "SELECT po_type, po_no, client_type
         FROM po_entry
         WHERE order_no = '$orderEsc'
         ORDER BY id DESC
         LIMIT 1"
    );
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $out['po_type'] = (string)($row['po_type'] ?? '');
        $out['po_no'] = (string)($row['po_no'] ?? '');
        $clientType = strtolower(trim((string)($row['client_type'] ?? '')));
        if (preg_match('/(forecast|forcast)/', $clientType)) {
            $out['source'] = 'FORECAST';
        } else {
            $out['source'] = mrp_demand_source_from_po_type($out['po_type']);
        }
        if ($out['source'] === '') {
            $out['source'] = 'CONFIRMED';
        }
        $out['reference_type'] = ($out['source'] === 'FORECAST') ? 'FORECAST_ENTRY' : 'PO';
        $out['reference_no'] = $out['po_no'] !== '' ? $out['po_no'] : $orderNo;
    }
    return $out;
}

/** Keep split rows readable even if historical records predate demand columns. */
function mrp_apply_split_demand_defaults(&$splits, $row) {
    if (!is_array($splits)) {
        return;
    }
    $defaultSource = mrp_normalize_demand_source($row['demand_source'] ?? '');
    if ($defaultSource === '') {
        $defaultSource = mrp_demand_source_from_po_type($row['po_type'] ?? '');
    }
    if ($defaultSource === '') {
        $defaultSource = 'CONFIRMED';
    }
    $defaultRefType = trim((string)($row['demand_reference_type'] ?? ''));
    if ($defaultRefType === '') {
        $defaultRefType = ($defaultSource === 'FORECAST') ? 'FORECAST_ENTRY' : 'PO';
    }
    $defaultRefNo = trim((string)($row['demand_reference_no'] ?? ''));
    if ($defaultRefNo === '') {
        $defaultRefNo = trim((string)($row['po_no'] ?? ''));
    }
    if ($defaultRefNo === '') {
        $defaultRefNo = trim((string)($row['order_no'] ?? ''));
    }
    foreach ($splits as $i => $sp) {
        $spSource = mrp_normalize_demand_source($sp['demand_source'] ?? '');
        if ($spSource === '') {
            $spSource = $defaultSource;
        }
        $spRefType = trim((string)($sp['demand_reference_type'] ?? ''));
        if ($spRefType === '') {
            $spRefType = $defaultRefType;
        }
        $spRefNo = trim((string)($sp['demand_reference_no'] ?? ''));
        if ($spRefNo === '') {
            $spRefNo = $defaultRefNo;
        }
        $splits[$i]['demand_source'] = $spSource;
        $splits[$i]['demand_reference_type'] = $spRefType;
        $splits[$i]['demand_reference_no'] = $spRefNo;
    }
}

$tokenEsc = $conn->real_escape_string((string)($token ?? ""));
$sql = "SELECT * FROM token WHERE token='" . $tokenEsc . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt("decrypt", $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.($_GET["type"] ?? "").'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER["REQUEST_METHOD"].'", "REMOTE_ADDR": "'.$_SERVER["REMOTE_ADDR"].'"}';
    @file_put_contents("../logs.txt", $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    // Ensure FO tables exist; add missing columns on existing tables
    mrp_ensure_factoryorder_schema($conn);

      if ($_GET["type"] == "getPOsLogForSplitsLog") {
      
      header('Content-Type: application/json; charset=utf-8');
      set_time_limit(180);

      $output = array();
      $plantId = $conn->real_escape_string($_GET['plant_id'] ?? '');
      mrp_ensure_split_demand_columns($conn);

      $sql = "SELECT
    a.id AS pid,a.plant_id,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,
    CASE
        WHEN LOWER(TRIM(IFNULL(b.po_type,''))) REGEXP 'forecast|forcast|projection|estimate' THEN 'FORECAST'
        ELSE 'CONFIRMED'
    END AS demand_source,
    CASE
        WHEN LOWER(TRIM(IFNULL(b.po_type,''))) REGEXP 'forecast|forcast|projection|estimate' THEN 'FORECAST_ENTRY'
        ELSE 'PO'
    END AS demand_reference_type,
    IFNULL(NULLIF(b.po_no,''), a.order_no) AS demand_reference_no,
    a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade AS product_grade,d.generic_name,d.genericProductCode,
    e.raw_materials,e.packing_materials,e.id AS unit_formula_id,a.order_qty AS qty_to_prepare,a.deliveryDate,
    d.generic_name,d.productionDate
FROM
    order_materials a
LEFT JOIN po_entry b ON a.order_no = b.order_no
LEFT JOIN client c ON b.client_code = c.client_code
LEFT JOIN product d ON a.product_code = d.product_code AND d.plant_id = a.plant_id
LEFT JOIN unitformula e ON
    e.product_code = a.product_code
    AND e.plant_id = a.plant_id
    AND LOWER(TRIM(IFNULL(e.status,''))) = 'approve'
    AND e.id = (
        SELECT MAX(uf2.id) FROM unitformula uf2
        WHERE uf2.product_code = a.product_code
          AND uf2.plant_id = a.plant_id
          AND LOWER(TRIM(IFNULL(uf2.status,''))) = 'approve'
    )
WHERE
    a.reqStatus = 'Inprocess'
    AND LOWER(TRIM(IFNULL(b.status,''))) IN ('approved', 'approve')
    AND a.plant_id = '".$plantId."'
    AND EXISTS (
        SELECT 1 FROM split_planning_qty sp
        WHERE sp.order_no = a.order_no
          AND sp.product_code = a.product_code
          AND ".mrp_factory_order_active_split_sql('sp')."
    )
ORDER BY a.id DESC";

      $result = $conn->query($sql);
      if ($result === false) {
          echo json_encode(array('error' => $conn->error, 'items' => array()));
      } elseif ($result->num_rows > 0) {
          $rows = array();
          $materialCodes = array();
          $productCodes = array();
          while ($row = $result->fetch_assoc()) {
              $rows[] = $row;
              $productCodes[] = $row['product_code'];
              $materialCodes = array_merge(
                  $materialCodes,
                  mrp_collect_material_codes_from_raw_json($row['raw_materials'] ?? null)
              );
          }
          mrp_prefetch_material_lead_for_codes($conn, $materialCodes);
          $splitsMap = mrp_prefetch_splits_map($conn, $rows, true);

          // Cumulative FG-stock netting across orders of the same product (earliest PO first).
          $fgStockMap = mrp_prefetch_fg_available_stock_map($conn, $productCodes);
          $reservedMap = mrp_prefetch_pending_dispatch_map($conn, $productCodes);
          $dispatchStatusMap = mrp_prefetch_dispatch_status_map($conn, $rows);
          mrp_apply_cumulative_fg_allocation($rows, $fgStockMap, $reservedMap, $dispatchStatusMap);

          foreach ($rows as $row) {
              $key = mrp_row_pair_key($row['order_no'] ?? '', $row['product_code'] ?? '');
              $output2 = $splitsMap[$key] ?? array();
              if (count($output2) === 0) {
                  continue;
              }
              mrp_apply_split_demand_defaults($output2, $row);
              $row['splits'] = $output2;
              $row['pack_size'] = json_decode($row['pack_size']);
              $row['raw_materials'] = json_decode($row['raw_materials'] ?? '', true);
              if (!is_array($row['raw_materials'])) {
                  $row['raw_materials'] = array();
              }
              $row['packing_materials'] = json_decode($row['packing_materials'] ?? '', true);
              if (!is_array($row['packing_materials'])) {
                  $row['packing_materials'] = array();
              }
              $row['has_bom_materials'] = mrp_factory_order_row_has_bom(
                  $conn,
                  $row['product_code'] ?? '',
                  $row['plant_id'] ?? '',
                  $row['raw_materials'],
                  $row['packing_materials']
              );
              mrp_enrich_factory_order_lead_times($conn, $row);
              $output[] = $row;
          }
          echo json_encode($output);
      } else {
          echo json_encode(array());
      }

  }

  if ($_GET["type"] == "sendForDispatchApproval") {
      header('Content-Type: application/json; charset=utf-8');
      mrp_ensure_dispatch_table($conn);

      $plantId = $conn->real_escape_string($_GET['plant_id'] ?? '');
      $empId = $conn->real_escape_string($_GET['emp_id'] ?? '');
      $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : array();
      if (empty($items)) {
          echo json_encode(array('status' => 'error', 'message' => 'No orders selected'));
          exit();
      }

      $inserted = 0;
      $skipped = array();
      foreach ($items as $it) {
          $orderNo = $conn->real_escape_string($it['order_no'] ?? '');
          $productCode = $conn->real_escape_string($it['product_code'] ?? '');
          $productName = $conn->real_escape_string($it['product_name'] ?? '');
          $unit = $conn->real_escape_string($it['unit'] ?? '');
          $orderQty = max(0, (float)($it['order_qty'] ?? 0));
          $dispatchQty = max(0, (float)($it['dispatch_qty'] ?? 0));

          if ($orderNo === '' || $productCode === '' || $dispatchQty <= 0) {
              $skipped[] = $orderNo;
              continue;
          }

          // Skip if a pending/approved request already exists for this order line.
          $chk = $conn->query(
              "SELECT id FROM mrp_dispatch_approval
               WHERE order_no = '$orderNo' AND product_code = '$productCode'
                 AND status IN ('Pending','Approved') LIMIT 1"
          );
          if ($chk && $chk->num_rows > 0) {
              $skipped[] = $orderNo;
              continue;
          }

          $ins = "INSERT INTO mrp_dispatch_approval
                    (plant_id, order_no, product_code, product_name, unit, order_qty, dispatch_qty, status, requested_by, requested_on)
                  VALUES
                    ('$plantId','$orderNo','$productCode','$productName','$unit','$orderQty','$dispatchQty','Pending','$empId','$entry_date')";
          if ($conn->query($ins)) {
              $inserted++;
          } else {
              $skipped[] = $orderNo;
          }
      }

      if ($inserted > 0) {
          echo json_encode(array('status' => 'success', 'inserted' => $inserted, 'skipped' => $skipped));
      } else {
          echo json_encode(array('status' => 'error', 'message' => 'Nothing was queued (already requested or invalid quantity)', 'skipped' => $skipped));
      }
      exit();
  }

      if ($_GET["type"] == "checkFactoryOrderBom") {
          header('Content-Type: application/json; charset=utf-8');
          $productCode = trim((string)($_GET['product_code'] ?? ''));
          $mfgPlantId = trim((string)($_GET['mfg_plant_id'] ?? $_GET['po_plant_id'] ?? ''));
          if ($mfgPlantId === '') {
              $mfgPlantId = trim((string)($_GET['plant_id'] ?? ''));
          }
          if ($productCode === '' || $mfgPlantId === '') {
              echo json_encode(array(
                  'status' => 'error',
                  'can_plan' => false,
                  'can_add' => false,
                  'message' => 'Product code and manufacturing plant are required',
              ));
              exit();
          }
          $ok = mrp_factory_order_row_has_bom($conn, $productCode, $mfgPlantId);
          echo json_encode(array(
              'status' => 'success',
              'can_plan' => $ok,
              'can_add' => $ok,
              'plant_id' => $mfgPlantId,
              'product_code' => $productCode,
              'message' => $ok
                  ? 'BOM available'
                  : 'Materials not available in BOM. Please create or complete the unit/batch formula with materials before planning.',
          ));
          exit();
      }

      if ($_GET["type"] == "getPOsLogForSplits") {
      
      header('Content-Type: application/json; charset=utf-8');
      set_time_limit(180);

      $prepares = array();
      $pending = array();

      $plantId = $conn->real_escape_string($_GET['plant_id'] ?? '');
      mrp_ensure_split_demand_columns($conn);
      $sql = "SELECT
    a.id AS pid,a.plant_id,
    COALESCE(NULLIF(TRIM(a.unit),''), NULLIF(TRIM(a.planUnit),''), NULLIF(TRIM(a.packingUnit),'')) AS unit,
    a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,b.billing_type,
    CASE
        WHEN LOWER(TRIM(IFNULL(b.billing_type,''))) REGEXP 'forecast|forcast|projection|estimate'
          OR LOWER(TRIM(IFNULL(b.po_type,''))) REGEXP 'forecast|forcast|projection|estimate' THEN 'FORECAST'
        ELSE 'CONFIRMED'
    END AS demand_source,
    CASE
        WHEN LOWER(TRIM(IFNULL(b.billing_type,''))) REGEXP 'forecast|forcast|projection|estimate'
          OR LOWER(TRIM(IFNULL(b.po_type,''))) REGEXP 'forecast|forcast|projection|estimate' THEN 'FORECAST_ENTRY'
        ELSE 'PO'
    END AS demand_reference_type,
    IFNULL(NULLIF(b.po_no,''), a.order_no) AS demand_reference_no,
    a.product_code,
    COALESCE(NULLIF(TRIM(a.order_qty),''), NULLIF(TRIM(a.planQty),''), NULLIF(TRIM(a.plan_qty),''), '0') AS order_qty,
    CASE
        WHEN a.pack_size IS NOT NULL AND TRIM(a.pack_size)<>'' AND TRIM(a.pack_size) NOT IN ('null','{}','[]') THEN a.pack_size
        ELSE CONCAT('{\"pack_size\":\"', IFNULL(a.packingStyle,''), '\",\"unit\":\"', IFNULL(a.packingUnit,''), '\"}')
    END AS pack_size,
    d.product_name,d.grade AS product_grade,d.generic_name,d.genericProductCode,
    e.raw_materials,e.packing_materials,e.id AS unit_formula_id,
    COALESCE(NULLIF(TRIM(a.order_qty),''), NULLIF(TRIM(a.planQty),''), NULLIF(TRIM(a.plan_qty),''), '0') AS qty_to_prepare,
    a.deliveryDate,
    d.generic_name,d.productionDate
FROM
    order_materials a
LEFT JOIN po_entry b ON
    a.order_no = b.order_no
    OR (a.po_entry_id IS NOT NULL AND a.po_entry_id = b.id)
LEFT JOIN client c ON
    b.client_code = c.client_code
LEFT JOIN product d ON
    a.product_code = d.product_code AND (d.plant_id = a.plant_id OR TRIM(IFNULL(d.plant_id,'')) = '')
LEFT JOIN unitformula e ON
    e.product_code = a.product_code
    AND e.plant_id = a.plant_id
    AND LOWER(TRIM(IFNULL(e.status,''))) = 'approve'
    AND e.id = (
        SELECT MAX(uf2.id) FROM unitformula uf2
        WHERE uf2.product_code = a.product_code
          AND uf2.plant_id = a.plant_id
          AND LOWER(TRIM(IFNULL(uf2.status,''))) = 'approve'
    )
WHERE
    LOWER(TRIM(IFNULL(a.reqStatus,''))) = 'pending'
    AND LOWER(TRIM(IFNULL(b.status,''))) IN ('approved', 'approve')
    AND a.plant_id = '".$plantId."'
ORDER BY a.id DESC";

      $result = $conn->query($sql);
      if ($result === false) {
          echo json_encode(array('error' => $conn->error, 'prepares' => array(), 'pending' => array()));
      } elseif ($result->num_rows > 0) {
          $rows = array();
          $productCodes = array();
          $materialCodes = array();
          while ($row = $result->fetch_assoc()) {
              $rows[] = $row;
              $productCodes[] = $row['product_code'];
              $materialCodes = array_merge(
                  $materialCodes,
                  mrp_collect_material_codes_from_raw_json($row['raw_materials'] ?? null)
              );
          }

          $fgStockMap = mrp_prefetch_fg_available_stock_map($conn, $productCodes);
          $reservedMap = mrp_prefetch_pending_dispatch_map($conn, $productCodes);
          $dispatchStatusMap = mrp_prefetch_dispatch_status_map($conn, $rows);
          mrp_prefetch_material_lead_for_codes($conn, $materialCodes);
          $splitsMap = mrp_prefetch_splits_map($conn, $rows, false);

          // Cumulative FG-stock netting across all orders of the same product (earliest PO first).
          mrp_apply_cumulative_fg_allocation($rows, $fgStockMap, $reservedMap, $dispatchStatusMap);

          foreach ($rows as $row) {
              $key = mrp_row_pair_key($row['order_no'] ?? '', $row['product_code'] ?? '');
              $row['splits'] = $splitsMap[$key] ?? array();
              mrp_apply_split_demand_defaults($row['splits'], $row);
              $row['pack_size'] = json_decode($row['pack_size']);
              $row['packing_configuration'] = array();
              $row['raw_materials'] = json_decode($row['raw_materials'], true);
              if (!is_array($row['raw_materials'])) {
                  $row['raw_materials'] = array();
              }
              $row['packing_materials'] = json_decode($row['packing_materials'] ?? '', true);
              if (!is_array($row['packing_materials'])) {
                  $row['packing_materials'] = array();
              }
              $row['has_bom_materials'] = mrp_factory_order_row_has_bom(
                  $conn,
                  $row['product_code'] ?? '',
                  $row['plant_id'] ?? '',
                  $row['raw_materials'],
                  $row['packing_materials']
              );

              mrp_enrich_factory_order_lead_times($conn, $row);

              if (!empty($row['splits'])) {
                  $prepares[] = $row;
              } else {
                  $pending[] = $row;
              }
          }

          echo json_encode(array(
              'prepares' => $prepares,
              'pending' => $pending,
          ));
      } else {
          echo json_encode(array('prepares' => array(), 'pending' => array()));
      }

  }

if ($_GET["type"] == "updateSplitQty") {
    header('Content-Type: application/json; charset=utf-8');
    $splitId = mrp_esc_int($input['id'] ?? 0);
    if ($splitId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid split id'));
        exit;
    }

    $checkSql = "SELECT status FROM split_planning_qty WHERE id=" . $splitId . " LIMIT 1";
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        $checkRow = $checkResult->fetch_assoc();
        $currentStatus = $checkRow['status'] ?? 'Pending';

        if ($currentStatus !== 'Pending') {
            echo json_encode(array('status' => 'error', 'message' => "Only splits with status 'Pending' can be updated"));
            exit;
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Split not found'));
        exit;
    }

    $inQty = mrp_esc_float($input['InQty'] ?? 0);
    $outQty = mrp_esc_float($input['outQty'] ?? 0);
    $qty = mrp_esc_float($input['Qty'] ?? ($input['oder_qty'] ?? 0));
    $oderQty = mrp_esc_float($input['oder_qty'] ?? ($input['Qty'] ?? 0));

    $sql = "UPDATE split_planning_qty
            SET InQty='" . $inQty . "',
                outQty='" . $outQty . "',
                Qty='" . $qty . "',
                oder_qty='" . $oderQty . "'
            WHERE id=" . $splitId;

    if ($conn->query($sql)) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $conn->error));
    }
}

if ($_GET["type"] == "deleteSplit") {
    header('Content-Type: application/json; charset=utf-8');
    $splitId = mrp_esc_int($input['id'] ?? 0);
    if ($splitId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid split id'));
        exit;
    }

    $checkSql = "SELECT status FROM split_planning_qty WHERE id=" . $splitId . " LIMIT 1";
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        $checkRow = $checkResult->fetch_assoc();
        $currentStatus = $checkRow['status'] ?? 'Pending';

        if ($currentStatus !== 'Pending') {
            echo json_encode(array('status' => 'error', 'message' => "Only splits with status 'Pending' can be deleted"));
            exit;
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Split not found'));
        exit;
    }

    $sql = "DELETE FROM split_planning_qty WHERE id=" . $splitId;

    if ($conn->query($sql)) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $conn->error));
    }
}

if ($_GET["type"] == "updateMarketingOrder") {
    header('Content-Type: application/json; charset=utf-8');
    $orderMaterialId = mrp_esc_int($input['id'] ?? 0);
    $orderQty = mrp_esc_float($input['order_qty'] ?? 0);
    if ($orderMaterialId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid order material id'));
        exit;
    }

    $sql = "UPDATE order_materials SET order_qty='" . $orderQty . "' WHERE id=" . $orderMaterialId;

    if ($conn->query($sql)) {
        $orderNoEsc = mrp_esc_str($conn, $input['order_no'] ?? '');

        if (!empty($input['splits']) && is_array($input['splits'])) {
            $splits = $input['splits'];
            $input_ids = array();

            foreach ($splits as $split) {
                if (!is_array($split)) {
                    continue;
                }
                $inQty = mrp_esc_float($split['InQty'] ?? 0);
                $outQty = mrp_esc_float($split['outQty'] ?? 0);
                $balQty = mrp_esc_float($split['bal_qty'] ?? 0);
                $avblStock = mrp_esc_float($split['avbl_stock'] ?? 0);
                $oderQty = mrp_esc_float($split['oder_qty'] ?? 0);
                $monthEsc = mrp_esc_str($conn, $split['month'] ?? '');
                $yearEsc = mrp_esc_str($conn, $split['year'] ?? '');

                if (!empty($split['id'])) {
                    $splitId = mrp_esc_int($split['id']);
                    if ($splitId <= 0) {
                        continue;
                    }
                    $input_ids[] = $splitId;

                    $sql1 = "UPDATE split_planning_qty SET
                                InQty='" . $inQty . "',
                                month='" . $monthEsc . "',
                                year='" . $yearEsc . "',
                                outQty='" . $outQty . "',
                                balance_qty='" . $balQty . "',
                                avbl_stock='" . $avblStock . "',
                                oder_qty='" . $oderQty . "'
                             WHERE id=" . $splitId;
                    $conn->query($sql1);
                } elseif ($orderNoEsc !== '') {
                    $sqlInsert = "INSERT INTO split_planning_qty
                                  (order_no, InQty, month, year, outQty, balance_qty, avbl_stock, oder_qty)
                                  VALUES (
                                    '" . $orderNoEsc . "',
                                    '" . $inQty . "',
                                    '" . $monthEsc . "',
                                    '" . $yearEsc . "',
                                    '" . $outQty . "',
                                    '" . $balQty . "',
                                    '" . $avblStock . "',
                                    '" . $oderQty . "'
                                  )";
                    $conn->query($sqlInsert);
                }
            }

            if ($orderNoEsc !== '') {
                if (!empty($input_ids)) {
                    $ids_to_keep = implode(',', array_map('intval', $input_ids));
                    $delete_sql = "DELETE FROM split_planning_qty
                                   WHERE order_no='" . $orderNoEsc . "'
                                   AND id NOT IN (" . $ids_to_keep . ")";
                    $conn->query($delete_sql);
                } else {
                    $conn->query("DELETE FROM split_planning_qty WHERE order_no='" . $orderNoEsc . "'");
                }
            }
        } elseif ($orderNoEsc !== '') {
            $conn->query("DELETE FROM split_planning_qty WHERE order_no='" . $orderNoEsc . "'");
        }

        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $conn->error));
    }
}

  if ($_GET["type"] == "sendFor_ConcolidatePlan") {
    header('Content-Type: application/json; charset=utf-8');

    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '' && !empty($_GET['order_no']) && !empty($_GET['product_code'])) {
        $orderNo = $conn->real_escape_string($_GET['order_no']);
        $productCode = $conn->real_escape_string($_GET['product_code']);
        $lookup = $conn->query(
            "SELECT id FROM order_materials
             WHERE order_no='$orderNo' AND product_code='$productCode'
             ORDER BY id DESC LIMIT 1"
        );
        if ($lookup && $lookup->num_rows > 0) {
            $id = (string)$lookup->fetch_assoc()['id'];
        }
    }

    if ($id === '' || !ctype_digit($id)) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid order material id'));
        exit;
    }

    $idEsc = $conn->real_escape_string($id);
    $empId = $conn->real_escape_string($_GET['emp_id'] ?? '');
    $sql = "UPDATE order_materials 
            SET reqStatus='Inprocess',
                send_by='" . $empId . "',
                send_on='" . $entry_date . "'
            WHERE id='" . $idEsc . "'";

    if ($conn->query($sql)) {
      $omRes = $conn->query(
        "SELECT order_no, product_code,
                COALESCE(NULLIF(TRIM(unit),''), NULLIF(TRIM(planUnit),''), NULLIF(TRIM(packingUnit),'')) AS unit,
                plant_id
         FROM order_materials WHERE id='" . $idEsc . "' LIMIT 1"
      );
      if ($omRes && $omRes->num_rows > 0) {
        $om = $omRes->fetch_assoc();
        $order_no = $conn->real_escape_string($om['order_no'] ?? '');
        $product_code = $conn->real_escape_string($om['product_code'] ?? '');
        $unit = $conn->real_escape_string($om['unit'] ?? '');
        $plant_id = $conn->real_escape_string($om['plant_id'] ?? ($_GET['plant_id'] ?? ''));

        if ($order_no !== '' && $product_code !== '') {
          // Ensure every split for this FO line is Pending so Processing/entry can pick it up
          $syncSql = "UPDATE split_planning_qty 
                      SET status='Pending',
                          unit = CASE WHEN unit IS NULL OR TRIM(unit)='' THEN '$unit' ELSE unit END,
                          planUnit = CASE WHEN planUnit IS NULL OR TRIM(planUnit)='' THEN '$unit' ELSE planUnit END,
                          plant_id = CASE WHEN plant_id IS NULL OR TRIM(plant_id)='' THEN '$plant_id' ELSE plant_id END
                      WHERE order_no='$order_no' 
                        AND product_code='$product_code'
                        AND (
                          status IS NULL OR TRIM(status)='' 
                          OR LOWER(TRIM(status)) IN ('pending','')
                        )";
          $conn->query($syncSql);

          // If no split rows exist yet, leave a clear message for the client
          $cntRes = $conn->query(
            "SELECT COUNT(*) AS c FROM split_planning_qty
             WHERE order_no='$order_no' AND product_code='$product_code'"
          );
          $splitCount = 0;
          if ($cntRes && ($cr = $cntRes->fetch_assoc())) {
              $splitCount = (int)($cr['c'] ?? 0);
          }

          echo json_encode(array(
              'status' => 'success',
              'order_no' => $om['order_no'] ?? '',
              'product_code' => $om['product_code'] ?? '',
              'reqStatus' => 'Inprocess',
              'split_count' => $splitCount,
              'message' => $splitCount > 0
                  ? 'Moved to Log / Processing Entry'
                  : 'Marked Inprocess but no month splits found — re-save plan months first',
          ));
          exit;
        }
      }

      echo json_encode(array('status' => 'success', 'reqStatus' => 'Inprocess'));
    } else {
      echo json_encode(array('status' => 'error', 'message' => $conn->error));
    }
  }

  if ($_GET["type"] == "saveWOEntryData") {
         
        header('Content-Type: application/json; charset=utf-8');
        mrp_ensure_split_demand_columns($conn);
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !is_array($input)) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid input'));
            exit;
        }

        $order_no = $conn->real_escape_string($input["order_no"] ?? '');
        $product_code = $conn->real_escape_string($input["product_code"] ?? '');
        $product_name = $conn->real_escape_string($input["product_name"] ?? '');
        $month = $conn->real_escape_string($input["month"] ?? '');
        $year = $conn->real_escape_string($input["year"] ?? '');
        $date = $conn->real_escape_string($input["date"] ?? date('Y-m-d'));
        $qty = $conn->real_escape_string($input["Qty"] ?? '0');
        $bal_qty = $conn->real_escape_string($input["bal_qty"] ?? '0');
        $inQty = $conn->real_escape_string($input["InQty"] ?? '0');
        $outQty = $conn->real_escape_string($input["outQty"] ?? '0');
        $avblStock = $conn->real_escape_string($input["AvblStock"] ?? '0');
        $bookedQty = $conn->real_escape_string($input["BookedQty"] ?? '0');
        $unit = $conn->real_escape_string($input["unit"] ?? '');
        $plant_id = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $emp_id = $conn->real_escape_string($_GET["emp_id"] ?? '');
        $inputDemandSource = mrp_normalize_demand_source($input['demand_source'] ?? '');
        $orderDemand = mrp_get_order_demand_context($conn, $order_no);
        $demandSource = $inputDemandSource !== '' ? $inputDemandSource : ($orderDemand['source'] ?? 'CONFIRMED');
        if ($demandSource === '') {
            $demandSource = 'CONFIRMED';
        }
        $demandReferenceType = trim((string)($input['demand_reference_type'] ?? ($orderDemand['reference_type'] ?? '')));
        if ($demandReferenceType === '') {
            $demandReferenceType = ($demandSource === 'FORECAST') ? 'FORECAST_ENTRY' : 'PO';
        }
        $demandReferenceNo = trim((string)($input['demand_reference_no'] ?? ($orderDemand['reference_no'] ?? '')));
        if ($demandReferenceNo === '') {
            $demandReferenceNo = $order_no;
        }
        $demandSourceEsc = $conn->real_escape_string($demandSource);
        $demandReferenceTypeEsc = $conn->real_escape_string($demandReferenceType);
        $demandReferenceNoEsc = $conn->real_escape_string($demandReferenceNo);

        if ($order_no === '' || $product_code === '' || $month === '' || $year === '') {
            echo json_encode(array('status' => 'error', 'message' => 'Missing order, product, month or year'));
            exit;
        }

        if ($unit === '') {
            $unitRes = $conn->query(
                "SELECT COALESCE(NULLIF(TRIM(unit),''), NULLIF(TRIM(planUnit),''), NULLIF(TRIM(packingUnit),'')) AS unit
                 FROM order_materials 
                 WHERE order_no='$order_no' AND product_code='$product_code' 
                 ORDER BY id DESC LIMIT 1"
            );
            if ($unitRes && $unitRes->num_rows > 0) {
                $unitRow = $unitRes->fetch_assoc();
                $unit = $conn->real_escape_string($unitRow['unit'] ?? '');
            }
        }
        if ($unit === '') {
            $unit = 'NOS';
        }

        $splitId = 0;
        $checkSql = "SELECT id FROM split_planning_qty
                     WHERE order_no='$order_no'
                       AND product_code='$product_code'
                       AND year='$year'
                       AND month='$month'
                     LIMIT 1";
        $checkRes = $conn->query($checkSql);

        if ($checkRes && $checkRes->num_rows > 0) {
            $existing = $checkRes->fetch_assoc();
            $splitId = (int)$existing['id'];
            $updateSql = "UPDATE split_planning_qty SET
                product_name='$product_name',
                date='$date',
                oder_qty='$qty',
                balance_qty='$bal_qty',
                InQty='$inQty',
                outQty='$outQty',
                avbl_stock='$avblStock',
                status='Pending',
                demand_source='$demandSourceEsc',
                demand_reference_type='$demandReferenceTypeEsc',
                demand_reference_no='$demandReferenceNoEsc',
                unit=CASE WHEN unit IS NULL OR TRIM(unit)='' THEN '$unit' ELSE unit END,
                planUnit=CASE WHEN planUnit IS NULL OR TRIM(planUnit)='' THEN '$unit' ELSE planUnit END,
                plant_id=CASE WHEN plant_id IS NULL OR TRIM(plant_id)='' THEN '$plant_id' ELSE plant_id END,
                entry_by='$emp_id'
                WHERE id='$splitId'";
            if (!$conn->query($updateSql)) {
                echo json_encode(array('status' => 'error', 'message' => $conn->error));
                exit;
            }
        } else {
            $insertSql = "INSERT INTO split_planning_qty (order_no,product_name, product_code,date, month, year,
                oder_qty,balance_qty,plant_id,entry_by,InQty,outQty,avbl_stock,status,demand_source,demand_reference_type,demand_reference_no,unit,planUnit)
               VALUES ('$order_no','$product_name','$product_code','$date','$month','$year','$qty','$bal_qty','$plant_id',
               '$emp_id','$inQty','$outQty','$avblStock','Pending','$demandSourceEsc','$demandReferenceTypeEsc','$demandReferenceNoEsc','$unit','$unit')";
            if (!$conn->query($insertSql)) {
                echo json_encode(array('status' => 'error', 'message' => $conn->error));
                exit;
            }
            $splitId = (int)$conn->insert_id;
        }

        if ((float)$bookedQty > 0) {
            $bookSql = "INSERT INTO mrp_bookedStock (plant_id,order_no,qty,material_code)
                        VALUES ('$plant_id','$order_no','$bookedQty','$product_code')";
            $conn->query($bookSql);
        }

        echo json_encode(array('status' => 'success', 'split_id' => $splitId));
        
     }

  if ($_GET["type"] == "getAwaitingBatchQtySumByProduct") {
      header('Content-Type: application/json; charset=utf-8');
      $plantId = $conn->real_escape_string($_GET['plant_id'] ?? '');
      $productCode = $conn->real_escape_string(trim((string)($_GET['product_code'] ?? '')));
      if ($productCode === '') {
          echo json_encode(array('qty' => 0));
          exit();
      }
      $plantFilter = $plantId !== '' ? " AND bp.plant_id = '$plantId'" : '';
      // Sum batch_size for open pipeline plans (batch_planning linked to unfinished WO).
      $sql = "SELECT COALESCE(SUM(bp.batch_size), 0) AS qty
              FROM batch_planning bp
              WHERE bp.product_code = '$productCode'
                $plantFilter
                AND EXISTS (
                  SELECT 1 FROM Work_order_materials wom
                  WHERE (wom.plan_no = bp.plan_no OR wom.product_code = bp.product_code)
                    AND LOWER(TRIM(IFNULL(wom.status,''))) NOT IN ('completed','closed','cancelled','done')
                )";
      $res = $conn->query($sql);
      $qty = 0;
      if ($res && ($row = $res->fetch_assoc())) {
          $qty = (float)($row['qty'] ?? 0);
      }
      echo json_encode(array('qty' => $qty));
      exit();
  }

} else {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array("status" => "error", "message" => "Invalid or missing token"));
}
?>

