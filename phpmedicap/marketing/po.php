<?php
require '../db.php';
require '../token.php';

/**
 * Prefer medicap helper; if not deployed yet, load sibling phpzuma copy
 * (host layout: .../phpdevlop/phpmedicap and .../phpdevlop/phpzuma).
 */
if (!function_exists('medicap_require_helper')) {
    function medicap_require_helper($relativeFromMarketing) {
        $relativeFromMarketing = str_replace('\\', '/', (string)$relativeFromMarketing);
        $local = str_replace('\\', '/', __DIR__ . '/' . $relativeFromMarketing);
        if (is_file($local)) {
            require_once $local;
            return;
        }

        // Map ../master/foo.php → phpzuma/master/foo.php
        // Map ./bar.php or bar.php → phpzuma/marketing/bar.php
        $norm = $relativeFromMarketing;
        if (strpos($norm, '../master/') === 0) {
            $zumaRel = 'master/' . substr($norm, strlen('../master/'));
        } else {
            $zumaRel = 'marketing/' . ltrim($norm, './');
        }

        $roots = array(
            dirname(__DIR__) . '/../phpzuma/',           // phpmedicap/../phpzuma
            dirname(__DIR__, 2) . '/phpzuma/',           // phpdevlop/phpzuma
        );
        foreach ($roots as $root) {
            $cand = str_replace('\\', '/', $root . $zumaRel);
            if (is_file($cand)) {
                require_once $cand;
                return;
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Missing PHP helper: ' . $relativeFromMarketing . '. Upload helpers or open mrp/sync_marketing_helpers_from_zuma.php on the server.',
        ));    
        exit;
    }
}

medicap_require_helper('../master/marketing_po_helpers.php');
medicap_require_helper('mrp_wo_schema_helpers.php');

/** Receive / Generate WO helpers used by Medicap MRP stages (may be missing on older phpzuma builds). */
if (!function_exists('isCombiCategory')) {
    function isCombiCategory($category) {
        $c = strtolower(trim((string)$category));
        if ($c === '') {
            return false;
        }
        return (strpos($c, 'combi') !== false)
            || $c === 'combination'
            || $c === 'combo';
    }
}

if (!function_exists('poEnrichCombiChildForProcessing')) {
    function poEnrichCombiChildForProcessing($conn, &$mat, $plantId = '') {
        if (!is_array($mat)) {
            return;
        }
        if (!isset($mat['kit_qty']) || $mat['kit_qty'] === '' || $mat['kit_qty'] === null) {
            $mat['kit_qty'] = $mat['CombiMaster_dtl_qty'] ?? 1;
        }
        $mat['is_combi_child'] = true;
        if (empty($mat['product_name']) && !empty($mat['product_code']) && ($conn instanceof mysqli)) {
            $pc = $conn->real_escape_string((string)$mat['product_code']);
            $pl = $conn->real_escape_string(trim((string)$plantId));
            $sql = "SELECT product_name FROM product WHERE product_code='{$pc}'";
            if ($pl !== '') {
                $sql .= " AND plant_id='{$pl}'";
            }
            $sql .= " ORDER BY id DESC LIMIT 1";
            $res = $conn->query($sql);
            if ($res && ($row = $res->fetch_assoc()) && !empty($row['product_name'])) {
                $mat['product_name'] = $row['product_name'];
            }
        }
    }
}

if (!function_exists('fetchBatchFormulasForProduct')) {
    function fetchBatchFormulasForProduct($conn, $productCode, $planUnit = '') {
        $out = array();
        $productCode = trim((string)$productCode);
        if ($productCode === '' || !($conn instanceof mysqli)) {
            return $out;
        }
        $pc = $conn->real_escape_string($productCode);
        $sql = "SELECT id, bfr_no, mfr_no, product_code, batch_formula_weight, rm_batch_size_unit,
                       unit_formula_batch_weight, status, version_no, effective_date
                FROM batch_formula_info
                WHERE product_code='{$pc}'
                  AND LOWER(TRIM(IFNULL(status,''))) IN ('approve','approved')
                ORDER BY id DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                if ($planUnit !== '' && !empty($row['rm_batch_size_unit'])
                    && strcasecmp(trim((string)$row['rm_batch_size_unit']), trim((string)$planUnit)) !== 0) {
                    // still include; unit filter is soft for Receive FO/PO
                }
                $row['batch_size'] = $row['batch_formula_weight'] ?? '';
                $row['batch_unit'] = $row['rm_batch_size_unit'] ?? $planUnit;
                $out[] = $row;
            }
        }
        return $out;
    }
}



// ini_set('display_errors', 1);
// error_reporting(E_ALL);

 
 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
date_default_timezone_set("Asia/Kolkata");
$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$_GET['token'] = $token;
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = array();
}

if (!function_exists('gw_get_open_po_cancelled_plan_qty')) {
    function gw_get_open_po_cancelled_plan_qty($conn, $material_code) {
        if (empty($material_code)) {
            return 0;
        }
        $code = $conn->real_escape_string($material_code);
        $sql = "SELECT IFNULL(SUM(CAST(pm.qty AS DECIMAL(15,4))), 0) AS open_po
                FROM po_material pm
                LEFT JOIN purchaseorder p ON p.po_no = pm.po_no
                WHERE pm.material_code = '$code'
                  AND (pm.isreceive = 'No' OR pm.isreceive IS NULL OR pm.isreceive = '')
                  AND EXISTS (
                      SELECT 1
                      FROM indend_raw ir
                      INNER JOIN mrp_raised_indnd_qty m ON CAST(m.indend_id AS CHAR) = CAST(ir.id AS CHAR)
                      INNER JOIN WO_deductions wd ON CAST(wd.id AS CHAR) = CAST(m.WO_deductions_id AS CHAR)
                      INNER JOIN Work_order_materials wom ON wom.workorder_no = wd.workorder_no
                      LEFT JOIN split_planning_qty sp ON wom.doc_no = sp.id
                      WHERE ir.material_code = pm.material_code
                        AND (
                            ir.indend_no = pm.indend_no
                            OR ir.no = pm.indend_no
                            OR CAST(ir.id AS CHAR) = CAST(pm.indend_no AS CHAR)
                        )
                        AND (
                            wd.status IN ('CAN_PLAN', 'Cancel', 'CAN_PLAN_MC_QTY_USED')
                            OR wom.status IN ('CAN_PLAN', 'Cancel')
                            OR sp.status IN ('CAN_PLAN', 'Cancel')
                        )
                  )";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['open_po']);
        }
        return 0;
    }
}

    /** First name + (emp_id) for compact UI columns (Receive FO/PO Entry User). */
    if (!function_exists('poEmpFirstNameIdSql')) {
        function poEmpFirstNameIdSql($empIdExpr) {
            return "(SELECT CASE
                        WHEN TRIM(IFNULL(firstname, '')) <> ''
                            THEN CONCAT(TRIM(firstname), ' (', emp_id, ')')
                        ELSE emp_id
                    END
                    FROM employee WHERE emp_id = ".$empIdExpr." LIMIT 1)";
        }
    }

    /** Client → Planning planner mapping (Receive FO/PO visibility). */
    if (!function_exists('po_ensure_order_materials_receive_cols')) {
        function po_ensure_order_materials_receive_cols($conn) {
            static $done = false;
            if ($done || !($conn instanceof mysqli)) {
                return;
            }
            $tables = array('order_materials', 'Work_order_materials');
            $cols = array(
                'Wo_Generated_by' => "VARCHAR(100) NULL DEFAULT NULL",
                'Wo_Generated_on' => "VARCHAR(50) NULL DEFAULT NULL",
            );
            foreach ($tables as $table) {
                $tCheck = $conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($table)."'");
                if (!$tCheck || $tCheck->num_rows === 0) {
                    continue;
                }
                foreach ($cols as $colName => $colDef) {
                    $col = $conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$colName."'");
                    if (!$col || $col->num_rows === 0) {
                        @$conn->query("ALTER TABLE `".$table."` ADD COLUMN `".$colName."` ".$colDef);
                    }
                }
            }
            $done = true;
        }
    }
    if (!function_exists('po_ensure_client_assigned_planner_table')) {
        function po_ensure_client_assigned_planner_table($conn) {
            static $done = false;
            if ($done || !($conn instanceof mysqli)) {
                return;
            }
            $conn->query("CREATE TABLE IF NOT EXISTS `client_assigned_planner` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `plant_id` VARCHAR(10) DEFAULT NULL,
                `client_code` VARCHAR(20) DEFAULT NULL,
                `emp_id` VARCHAR(50) DEFAULT NULL,
                `emp_name` VARCHAR(200) DEFAULT NULL,
                `assigned_by` VARCHAR(50) DEFAULT NULL,
                `assigned_on` VARCHAR(50) DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_client_assigned_planner` (`plant_id`, `client_code`, `emp_id`),
                KEY `idx_client_planner_code` (`client_code`),
                KEY `idx_client_planner_emp` (`emp_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
            $done = true;
        }

        function po_is_planning_dept_head($conn, $plant_id, $emp_id) {
            $plantEsc = mysqli_real_escape_string($conn, (string)$plant_id);
            $empEsc = mysqli_real_escape_string($conn, (string)$emp_id);
            $sql = "SELECT dept_head FROM emp_rights
                WHERE plant_id='" . $plantEsc . "' AND emp_id='" . $empEsc . "'
                AND department='Planning' AND status='approve' LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return isset($row['dept_head']) && strtoupper(trim((string)$row['dept_head'])) === 'YES';
            }
            return false;
        }

        /**
         * Medicap: Client Planner mapping is not used — always show all planning rows.
         */
        function po_receive_planner_filter_context($conn, $plant_id, $emp_id) {
            return array(
                'is_head' => true,
                'my_codes' => array(),
                'clients_with_planners' => array(),
            );
        }

        function po_receive_fo_visible_for_planner($ctx, $client_code, $groupcode = '') {
            return true;
        }

        /** Medicap: no planner visibility filter on Work_order_materials rows. */
        function po_wo_planner_row_visible($ctx, $row, $empId = '') {
            return true;
        }

        /** Medicap: planner display not used — keep empty fields for API compatibility. */
        function po_attach_fo_planner_info($conn, &$row, $plant_id) {
            $row['assigned_planner_emp_ids'] = array();
            $row['client_has_planners'] = false;
            $row['planner_to_display'] = '';
        }

        /** Standard Work_order_materials → order_materials join (product-scoped). */
        function po_wo_om_join_sql($alias = 'a', $joinAlias = 'b') {
            return "LEFT JOIN order_materials " . $joinAlias . " ON " . $joinAlias . ".id = (
                SELECT om.id FROM order_materials om
                WHERE om.order_no = " . $alias . ".order_no
                  AND TRIM(COALESCE(" . $alias . ".product_code, '')) != ''
                  AND TRIM(om.product_code) = TRIM(COALESCE(" . $alias . ".product_code, ''))
                ORDER BY om.id DESC
                LIMIT 1
            )";
        }

        /** groupcode + client_code for planner visibility on WO rows. */
        function po_wo_planner_client_select_sql($alias = 'a', $joinAlias = 'b') {
            return ",
                COALESCE(
                  NULLIF(TRIM(" . $alias . ".groupcode), ''),
                  NULLIF(TRIM(" . $joinAlias . ".groupcode), ''),
                  (SELECT om2.groupcode FROM order_materials om2
                     WHERE om2.order_no = " . $alias . ".order_no
                       AND TRIM(COALESCE(" . $alias . ".product_code, '')) != ''
                       AND TRIM(om2.product_code) = TRIM(COALESCE(" . $alias . ".product_code, ''))
                     ORDER BY om2.id DESC LIMIT 1),
                  (SELECT om2.groupcode FROM order_materials om2
                     WHERE om2.order_no = " . $alias . ".order_no
                     ORDER BY om2.id DESC LIMIT 1)
                ) AS groupcode,
                COALESCE(
                  (SELECT pe.client_code FROM po_entry pe
                     INNER JOIN order_materials om ON om.po_entry_id = pe.id
                     WHERE om.order_no = " . $alias . ".order_no
                       AND TRIM(COALESCE(" . $alias . ".product_code, '')) != ''
                       AND TRIM(om.product_code) = TRIM(COALESCE(" . $alias . ".product_code, ''))
                     ORDER BY om.id DESC LIMIT 1),
                  (SELECT pe.client_code FROM po_entry pe
                     INNER JOIN order_materials om ON om.po_entry_id = pe.id
                     WHERE om.order_no = " . $alias . ".order_no
                     ORDER BY om.id DESC LIMIT 1),
                  (SELECT pe.client_code FROM po_entry pe
                     INNER JOIN order_materials om ON om.po_entry_id = pe.id
                     WHERE om.order_no = " . $joinAlias . ".order_no
                     ORDER BY om.id DESC LIMIT 1)
                ) AS client_code";
        }
    }


if (!function_exists('gw_get_open_indent_previous_plan_qty')) {
    function gw_get_open_indent_previous_plan_qty($conn, $material_code, $exclude_plan_month = '') {
        if (empty($material_code)) {
            return 0;
        }
        $code = $conn->real_escape_string($material_code);
        $excludeClause = '';
        if (!empty($exclude_plan_month)) {
            $exclude = $conn->real_escape_string($exclude_plan_month);
            $excludeClause = " AND COALESCE(
                NULLIF(CONCAT(sp.month, '-', sp.year), '-'),
                NULLIF(wom.planMonth, ''),
                ''
            ) <> '$exclude'";
        }
        $sql = "SELECT IFNULL(SUM(
                    CAST(COALESCE(NULLIF(m.ordered_qty, ''), NULLIF(ir.req_qty, ''), 0) AS DECIMAL(15,4))
                ), 0) AS open_indent
                FROM mrp_raised_indnd_qty m
                INNER JOIN indend_raw ir ON CAST(m.indend_id AS CHAR) = CAST(ir.id AS CHAR)
                INNER JOIN WO_deductions wd ON CAST(wd.id AS CHAR) = CAST(m.WO_deductions_id AS CHAR)
                INNER JOIN Work_order_materials wom ON wom.workorder_no = wd.workorder_no
                LEFT JOIN split_planning_qty sp ON wom.doc_no = sp.id AND wom.order_no = sp.order_no
                WHERE m.material_code = '$code'
                  AND wd.indent_status IN ('Raised', 'Indent Sent')
                  AND LOWER(COALESCE(ir.status, '')) IN ('approve', 'approved')
                  AND COALESCE(ir.po_indend, 'pending') = 'pending'
                  $excludeClause";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['open_indent']);
        }
        return 0;
    }
}

if (!function_exists('gw_get_cancelled_release_booked_qty')) {
    /**
     * RM booked on cancelled plan / forecast / WO still counted in vw_total_available_stock
     * (view excludes only status='Cancel', not CAN_PLAN). Add back to available for shortage.
     */
    function gw_get_cancelled_release_booked_qty($conn, $material_code) {
        if (empty($material_code)) {
            return 0;
        }
        $code = $conn->real_escape_string($material_code);
        $sql = "SELECT IFNULL(SUM(
                    CAST(COALESCE(wd.deducted_from_RM, 0) AS DECIMAL(15,4)) +
                    CAST(COALESCE(wd.deducted_from_MC, 0) AS DECIMAL(15,4))
                ), 0) AS released_booked
                FROM WO_deductions wd
                LEFT JOIN Work_order_materials wom ON wom.workorder_no = wd.workorder_no
                LEFT JOIN split_planning_qty sp ON wom.doc_no = sp.id
                    AND wom.order_no = sp.order_no
                    AND wom.product_code = sp.product_code
                WHERE wd.material_code = '$code'
                  AND wd.status <> 'Cancel'
                  AND (
                      wd.status IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')
                      OR wom.status IN ('CAN_PLAN', 'Cancel')
                      OR sp.status IN ('CAN_PLAN', 'Cancel')
                  )";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['released_booked']);
        }
        return 0;
    }
}

if (!function_exists('gw_get_shortages_stock_fields')) {
    function gw_get_shortages_stock_fields($conn, $material_code, $exclude_plan_month = '') {
        $empty = [
            'available_Stock_qty' => 0,
            'booked_qty' => 0,
            'total_stock_qty' => 0,
            'available_qty' => 0,
            'rm_balance' => 0,
            'openPO' => 0,
            'openIndent' => 0,
            'releasedBookedQty' => 0,
            'effective_available_rm' => 0,
        ];
        if (empty($material_code)) {
            return $empty;
        }

        $code = $conn->real_escape_string($material_code);
        $result = $conn->query("SELECT material_code,
            COALESCE(CAST(available_qty AS DECIMAL(15,4)), 0) AS available_qty,
            COALESCE(CAST(booked_qty AS DECIMAL(15,4)), 0) AS booked_qty,
            COALESCE(CAST(total_stock_qty AS DECIMAL(15,4)), 0) AS total_stock_qty
            FROM vw_total_available_stock
            WHERE material_code = '$code'
            LIMIT 1");

        $available = 0;
        $booked = 0;
        $totalStock = 0;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $available = floatval($row['available_qty']);
            $booked = floatval($row['booked_qty']);
            $totalStock = floatval($row['total_stock_qty']);
        }

        $openPO = gw_get_open_po_cancelled_plan_qty($conn, $material_code);
        $openIndent = gw_get_open_indent_previous_plan_qty($conn, $material_code, $exclude_plan_month);
        $releasedBooked = gw_get_cancelled_release_booked_qty($conn, $material_code);
        $planningOpenIndent = 0;
        $cancelledPlanningIndent = 0;
        if (function_exists('gw_get_planning_sent_open_indent_qty')) {
            $planningOpenIndent = gw_get_planning_sent_open_indent_qty($conn, $material_code, $exclude_plan_month);
        }
        if (function_exists('gw_get_cancelled_planning_indent_qty')) {
            $cancelledPlanningIndent = gw_get_cancelled_planning_indent_qty($conn, $material_code);
        }
        $openIndentTotal = $openIndent + $planningOpenIndent;
        $effective = $available + $openPO + $openIndentTotal + $releasedBooked + $cancelledPlanningIndent;

        return [
            'available_Stock_qty' => round($available, 4),
            'booked_qty' => round($booked, 4),
            'total_stock_qty' => round($totalStock, 4),
            'available_qty' => round($available, 4),
            'rm_balance' => round($available, 4),
            'openPO' => round($openPO, 4),
            'openIndent' => round($openIndentTotal, 4),
            'planningOpenIndent' => round($planningOpenIndent, 4),
            'cancelledPlanningIndent' => round($cancelledPlanningIndent, 4),
            'releasedBookedQty' => round($releasedBooked, 4),
            'effective_available_rm' => round($effective, 4),
        ];
    }
}

/** Prefetch stock fields for many material codes (WoAnalysis / Shortages). */
if (!function_exists('gw_batch_shortages_stock_fields')) {
    function gw_batch_shortages_stock_fields($conn, array $materialCodes, $light = false) {
        $empty = [
            'available_Stock_qty' => 0,
            'booked_qty' => 0,
            'total_stock_qty' => 0,
            'available_qty' => 0,
            'rm_balance' => 0,
            'openPO' => 0,
            'openIndent' => 0,
            'releasedBookedQty' => 0,
            'effective_available_rm' => 0,
        ];
        $map = array();
        $codes = array_values(array_unique(array_filter(array_map(function ($c) {
            return trim((string)$c);
        }, $materialCodes))));
        if (empty($codes)) {
            return $map;
        }

        $stockRows = gw_prefetch_vw_stock_available_map($conn, $codes);
        $in = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $codes)) . "'";

        $releasedMap = array();
        $sqlReleased = "SELECT wd.material_code,
            IFNULL(SUM(
                CAST(COALESCE(wd.deducted_from_RM, 0) AS DECIMAL(15,4)) +
                CAST(COALESCE(wd.deducted_from_MC, 0) AS DECIMAL(15,4))
            ), 0) AS released_booked
            FROM WO_deductions wd
            LEFT JOIN Work_order_materials wom ON wom.id = wd.work_order_id
            LEFT JOIN split_planning_qty sp ON wom.doc_no = sp.id
            WHERE wd.material_code IN ($in)
              AND wd.status <> 'Cancel'
              AND (
                  wd.status IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')
                  OR wom.status IN ('CAN_PLAN', 'Cancel')
                  OR sp.status IN ('CAN_PLAN', 'Cancel')
              )
            GROUP BY wd.material_code";
        $resReleased = $conn->query($sqlReleased);
        if ($resReleased) {
            while ($row = $resReleased->fetch_assoc()) {
                $releasedMap[$row['material_code']] = (float)$row['released_booked'];
            }
            $resReleased->free();
        }

        foreach ($codes as $code) {
            if ($code === '') {
                continue;
            }
            $available = (float)($stockRows[$code] ?? 0);
            $openPO = 0;
            $openIndentTotal = 0;
            $planningOpenIndent = 0;
            $cancelledPlanningIndent = 0;
            if (!$light) {
                $openPO = gw_get_open_po_cancelled_plan_qty($conn, $code);
                $openIndent = gw_get_open_indent_previous_plan_qty($conn, $code, '');
                if (function_exists('gw_get_planning_sent_open_indent_qty')) {
                    $planningOpenIndent = gw_get_planning_sent_open_indent_qty($conn, $code, '');
                }
                if (function_exists('gw_get_cancelled_planning_indent_qty')) {
                    $cancelledPlanningIndent = gw_get_cancelled_planning_indent_qty($conn, $code);
                }
                $openIndentTotal = $openIndent + $planningOpenIndent;
            }
            $releasedBooked = (float)($releasedMap[$code] ?? 0);
            $effective = $available + $openPO + $openIndentTotal + $releasedBooked + $cancelledPlanningIndent;
            $map[$code] = [
                'available_Stock_qty' => round($available, 4),
                'booked_qty' => 0,
                'total_stock_qty' => round($available, 4),
                'available_qty' => round($available, 4),
                'rm_balance' => round($available, 4),
                'openPO' => round($openPO, 4),
                'openIndent' => round($openIndentTotal, 4),
                'planningOpenIndent' => round($planningOpenIndent, 4),
                'cancelledPlanningIndent' => round($cancelledPlanningIndent, 4),
                'releasedBookedQty' => round($releasedBooked, 4),
                'effective_available_rm' => round($effective, 4),
            ];
        }
        return $map;
    }
}

/** order_no|product_code => factory order no (Inprocess preferred). */
if (!function_exists('gw_wowise_factory_order_map')) {
    function gw_wowise_factory_order_map($conn) {
        $map = array();
        $sql = "SELECT om.order_no, om.product_code, om.order_no AS factory_order_no
                FROM order_materials om
                INNER JOIN (
                    SELECT order_no, product_code,
                           SUBSTRING_INDEX(
                               GROUP_CONCAT(id ORDER BY
                                   CASE
                                       WHEN reqStatus = 'Inprocess' THEN 0
                                       WHEN reqStatus = 'pending' THEN 1
                                       ELSE 2
                                   END, id DESC
                               ), ',', 1
                           ) AS pick_id
                    FROM order_materials
                    GROUP BY order_no, product_code
                ) pick ON om.id = CAST(pick.pick_id AS UNSIGNED)";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $key = ($row['order_no'] ?? '') . '|' . ($row['product_code'] ?? '');
                $map[$key] = $row['factory_order_no'] ?? '';
            }
        }
        return $map;
    }
}

/** One row per material_code for WoAnalysis (avoids duplicate material master rows). */
if (!function_exists('gw_prefetch_material_display_map')) {
    function gw_prefetch_material_display_map($conn, array $materialCodes) {
        $map = array();
        $codes = array_values(array_unique(array_filter(array_map(function ($c) {
            return trim((string)$c);
        }, $materialCodes))));
        if (empty($codes)) {
            return $map;
        }
        $in = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $codes)) . "'";

        $queries = array(
            "SELECT m.material_code, m.material_name, m.material_type, m.material_subtype, m.unit, m.mother_code
             FROM material m
             INNER JOIN (
                 SELECT material_code, MIN(id) AS pick_id FROM material
                 WHERE material_code IN ($in) GROUP BY material_code
             ) p ON m.id = p.pick_id",
            "SELECT o.material_code, o.material_name, o.material_type, o.material_subtype, o.unit, '' AS mother_code
             FROM others_material o
             INNER JOIN (
                 SELECT material_code, MIN(id) AS pick_id FROM others_material
                 WHERE material_code IN ($in) GROUP BY material_code
             ) p ON o.id = p.pick_id",
            "SELECT b.bulkCode AS material_code, b.bulkName AS material_name, b.material_type, '' AS material_subtype, '' AS unit, '' AS mother_code
             FROM bulkMaster b
             WHERE b.bulkCode IN ($in)",
        );

        foreach ($queries as $sql) {
            $res = $conn->query($sql);
            if (!$res) {
                continue;
            }
            while ($row = $res->fetch_assoc()) {
                $code = $row['material_code'] ?? '';
                if ($code !== '' && !isset($map[$code])) {
                    $map[$code] = $row;
                }
            }
            $res->free();
        }
        return $map;
    }
}

if (!function_exists('gw_prefetch_vw_stock_available_map')) {
    function gw_prefetch_vw_stock_available_map($conn, array $materialCodes) {
        $map = array();
        $codes = array_values(array_unique(array_filter(array_map(function ($c) {
            return trim((string)$c);
        }, $materialCodes))));
        if (empty($codes)) {
            return $map;
        }
        $in = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $codes)) . "'";
        $sql = "SELECT material_code,
                COALESCE(CAST(available_qty AS DECIMAL(15,4)), 0) AS available_qty
                FROM vw_total_available_stock
                WHERE material_code IN ($in)";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[$row['material_code']] = (float)$row['available_qty'];
            }
            $res->free();
        }
        return $map;
    }
}

if (!function_exists('gw_prefetch_product_meta_maps')) {
    function gw_prefetch_product_meta_maps($conn, array $productCodes) {
        $names = array();
        $bulk = array();
        $codes = array_values(array_unique(array_filter(array_map(function ($c) {
            return trim((string)$c);
        }, $productCodes))));
        if (empty($codes)) {
            return array('names' => $names, 'bulk' => $bulk);
        }
        $in = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $codes)) . "'";

        $res = $conn->query("SELECT product_code, product_name FROM product WHERE product_code IN ($in)");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $names[$row['product_code']] = $row['product_name'] ?? '';
            }
            $res->free();
        }

        $sqlBulk = "SELECT uf1.product_code, uf1.mfr_no
                    FROM unitformula uf1
                    INNER JOIN (
                        SELECT product_code, MAX(id) AS max_id
                        FROM unitformula
                        WHERE product_code IN ($in)
                        GROUP BY product_code
                    ) ufmx ON uf1.product_code = ufmx.product_code AND uf1.id = ufmx.max_id";
        $resBulk = $conn->query($sqlBulk);
        if ($resBulk) {
            while ($row = $resBulk->fetch_assoc()) {
                $bulk[$row['product_code']] = $row['mfr_no'] ?? '';
            }
            $resBulk->free();
        }

        return array('names' => $names, 'bulk' => $bulk);
    }
}

if (!function_exists('gw_wowise_normalize_scope')) {
    function gw_wowise_normalize_scope($scope) {
        $scope = strtolower(trim((string)$scope));
        return ($scope === 'log') ? 'log' : 'current';
    }
}

if (!function_exists('gw_wowise_scope_sql')) {
    /** current = sent for analysis, indent not raised yet, not proceeded; log = proceeded or older analysis cycles */
    function gw_wowise_scope_sql($scope) {
        $scope = gw_wowise_normalize_scope($scope);
        // Treats the WO Analysis "Proceed to Shortage Planning" marker (set on WO_deductions).
        // Proceeded rows leave "current" and surface in "log" even before the indent is raised.
        $notProceeded = "(a.wo_analysis_proceeded_at IS NULL OR a.wo_analysis_proceeded_at = '0000-00-00 00:00:00')";
        $proceeded = "(a.wo_analysis_proceeded_at IS NOT NULL AND a.wo_analysis_proceeded_at <> '0000-00-00 00:00:00')";
        if ($scope === 'current') {
            return " AND a.indent_status = 'Not Raised'
                  AND COALESCE(sp.status, b.status) = 'Send For Requirement Analysis'
                  AND $notProceeded";
        }
        // Can-plan batches belong on the Can Planned tab, never on WO Analysis (current or log).
        return " AND COALESCE(sp.status, b.status) NOT IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')
                AND (
                    a.indent_status IN ('Raised', 'Indent Sent')
                    OR $proceeded
                    OR (
                        a.indent_status = 'Not Raised'
                        AND COALESCE(sp.status, b.status) <> 'Send For Requirement Analysis'
                    )
                )";
    }
}

if (!function_exists('gw_wowise_plan_stage_label')) {
    function gw_wowise_plan_stage_label($indentStatus, $planStatus, $proceededAt = null) {
        $indentStatus = trim((string)$indentStatus);
        $planStatus = trim((string)$planStatus);
        $proceeded = $proceededAt !== null
            && trim((string)$proceededAt) !== ''
            && trim((string)$proceededAt) !== '0000-00-00 00:00:00';
        if ($indentStatus === 'Raised' || $indentStatus === 'Indent Sent') {
            return 'Indent raised (proceeded)';
        }
        if ($proceeded) {
            return 'Proceeded to Shortage Planning';
        }
        if ($planStatus === 'Send For Requirement Analysis') {
            return 'Pending indent';
        }
        return 'Previous analysis cycle';
    }
}

if (!function_exists('gw_wowise_summary_from_flat_output')) {
    function gw_wowise_summary_from_flat_output($flatOutput) {
        $shortMaterials = array();
        $hasRows = is_array($flatOutput) && count($flatOutput) > 0;
        if (!$hasRows) {
            return array(
                'short_material_count' => 0,
                'has_shortage' => false,
                'can_proceed_production' => false,
            );
        }
        foreach ($flatOutput as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = trim((string)($row['material_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $shortage = max(
                (float)($row['rm_shortage'] ?? 0),
                (float)($row['mc_shortage'] ?? 0),
                (float)($row['clmc_shortage'] ?? 0),
                (float)($row['shortage'] ?? 0)
            );
            if ($shortage > 0) {
                $shortMaterials[$code] = true;
            }
        }
        $shortCount = count($shortMaterials);
        return array(
            'short_material_count' => $shortCount,
            'has_shortage' => $shortCount > 0,
            'can_proceed_production' => !$shortCount,
        );
    }
}

if (!function_exists('gw_wowise_cache_path')) {
    function gw_wowise_cache_path($plantId, $scope = 'current') {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zuma_wowise';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $scope = gw_wowise_normalize_scope($scope);
        $key = ($plantId !== '' ? $plantId : 'all') . '_' . $scope;
        return $dir . DIRECTORY_SEPARATOR . 'wowise_' . md5($key) . '.json';
    }
}

if (!function_exists('gw_mpo_material_lead_cache')) {
    function gw_mpo_material_lead_cache() {
        static $cache = array();
        return $cache;
    }
}

if (!function_exists('gw_mpo_prefetch_material_lead_rows')) {
    /** Purchase lead time rows from Material Lead Time master (planning/mrp/log). */
    function gw_mpo_prefetch_material_lead_rows($conn, array $materialCodes) {
        $cache = &gw_mpo_material_lead_cache();
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
            $sql = "SELECT material_code, material_name,
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
                $code = trim((string)($row['material_code'] ?? ''));
                if ($code === '' || array_key_exists($code, $cache)) {
                    continue;
                }
                $indentApproval = (int)$row['indent_approve_date'];
                $poAfterIndent = (int)$row['Purchase_prepare_date'];
                $paymentAfterPo = (int)$row['ForPayment'];
                $vendorTransit = (int)$row['PurchaseDeliveryTime'];
                $cache[$code] = array(
                    'material_code' => $code,
                    'material_name' => trim((string)($row['material_name'] ?? '')),
                    'lead_time_days' => $indentApproval + $poAfterIndent + $paymentAfterPo + $vendorTransit,
                    'breakdown' => array(
                        'indent_approval' => $indentApproval,
                        'po_after_indent' => $poAfterIndent,
                        'payment_after_po' => $paymentAfterPo,
                        'vendor_transit' => $vendorTransit,
                    ),
                );
            }
        }
        foreach ($missing as $code) {
            if (!array_key_exists($code, $cache)) {
                $cache[$code] = array(
                    'material_code' => $code,
                    'material_name' => '',
                    'lead_time_days' => 0,
                    'breakdown' => array(
                        'indent_approval' => 0,
                        'po_after_indent' => 0,
                        'payment_after_po' => 0,
                        'vendor_transit' => 0,
                    ),
                );
            }
        }
        return $cache;
    }
}

if (!function_exists('gw_mpo_request_manufacturing_plant_id')) {
    /** Manufacturing plant for PO — never use $_GET['plant_id'] (login plant from auth suffix). */
    function gw_mpo_request_manufacturing_plant_id() {
        foreach (array('mfg_plant_id', 'po_plant_id', 'plantID') as $key) {
            $v = trim((string)($_GET[$key] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }
        return '';
    }
}

if (!function_exists('gw_mpo_collect_material_codes_from_json_list')) {
    function gw_mpo_collect_material_codes_from_json_list($jsonOrArray) {
        $codes = array();
        $list = is_string($jsonOrArray) ? json_decode($jsonOrArray, true) : $jsonOrArray;
        if (!is_array($list)) {
            return $codes;
        }
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            $code = trim((string)($item['material_code'] ?? $item['Material_code'] ?? ''));
            if ($code !== '') {
                $codes[] = $code;
            }
        }
        return $codes;
    }
}

if (!function_exists('gw_mpo_json_list_has_materials')) {
    function gw_mpo_json_list_has_materials($jsonOrArray) {
        if (count(gw_mpo_collect_material_codes_from_json_list($jsonOrArray)) > 0) {
            return true;
        }
        $list = is_string($jsonOrArray) ? json_decode($jsonOrArray, true) : $jsonOrArray;
        if (!is_array($list) || count($list) === 0) {
            return false;
        }
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string)($item['material_name'] ?? $item['Material_name'] ?? ''));
            $qty = trim((string)($item['qty'] ?? $item['Qty'] ?? $item['total_qty'] ?? ''));
            if ($name !== '' && $qty !== '') {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('gw_mpo_bfr_info_json_has_materials')) {
    /** batch_formula_info JSON columns when batch_materials table rows are missing. */
    function gw_mpo_bfr_info_json_has_materials($infoRow) {
        if (!is_array($infoRow)) {
            return false;
        }
        if (gw_mpo_json_list_has_materials($infoRow['raw_materials'] ?? null)) {
            return true;
        }
        foreach (array('consumable_materials', 'consumableMaterial', 'additional_materials') as $field) {
            if (gw_mpo_json_list_has_materials($infoRow[$field] ?? null)) {
                return true;
            }
        }
        $packJson = $infoRow['packing_materials'] ?? null;
        $groups = is_string($packJson) ? json_decode($packJson, true) : $packJson;
        if (!is_array($groups)) {
            return false;
        }
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $list = $group['packing_list'] ?? null;
            if (gw_mpo_json_list_has_materials($list)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('gw_mpo_product_batch_formula_json_has_materials')) {
    function gw_mpo_product_batch_formula_json_has_materials($conn, $productCode, $plantId = '', array $bfrNos = array()) {
        $productCode = trim((string)$productCode);
        if ($productCode === '') {
            return false;
        }
        $pcEsc = $conn->real_escape_string($productCode);
        $plantWhere = $plantId !== ''
            ? " AND plant_id = '" . $conn->real_escape_string($plantId) . "'"
            : '';
        $bfrFilter = '';
        $bfrNos = array_values(array_unique(array_filter(array_map('trim', $bfrNos))));
        if (count($bfrNos) > 0) {
            $bfrFilter = " AND bfr_no IN ('" . implode("','", array_map(array($conn, 'real_escape_string'), $bfrNos)) . "')";
        }
        $sql = "SELECT raw_materials, packing_materials, consumable_materials, additional_materials
                FROM batch_formula_info
                WHERE product_code = '$pcEsc'" . $plantWhere . $bfrFilter . "
                ORDER BY id DESC LIMIT 10";
        $res = $conn->query($sql);
        if (!$res) {
            return false;
        }
        while ($row = $res->fetch_assoc()) {
            if (gw_mpo_bfr_info_json_has_materials($row)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('gw_mpo_unit_formula_has_packing_dtl_materials')) {
    function gw_mpo_unit_formula_has_packing_dtl_materials($conn, $unitFormulaId) {
        $id = (int)$unitFormulaId;
        if ($id <= 0) {
            return false;
        }
        $sql = "SELECT COUNT(*) AS c FROM unitformula_pm_dtl upd
                INNER JOIN unitformula_packing_materials upm ON upm.unit_formula_dtl_id = upd.id
                WHERE upd.unit_formula_id = $id
                  AND TRIM(IFNULL(upm.material_code,'')) <> ''";
        $res = $conn->query($sql);
        return $res && ($r = $res->fetch_assoc()) && (int)$r['c'] > 0;
    }
}

if (!function_exists('gw_mpo_get_approved_unit_formula_row')) {
    /** Same scope as marketing getProductsss — approved unit formula for product + manufacturing plant. */
    function gw_mpo_get_approved_unit_formula_row($conn, $productCode, $plantId) {
        $productCode = trim((string)$productCode);
        $plantId = trim((string)$plantId);
        if ($productCode === '' || $plantId === '') {
            return null;
        }
        $pcEsc = $conn->real_escape_string($productCode);
        $plEsc = $conn->real_escape_string($plantId);
        $sql = "SELECT id, mfr_no, raw_materials, primary_pm_list, consumeableMaterial, packing_materials, additional_materials
                FROM unitformula
                WHERE product_code = '$pcEsc'
                  AND plant_id = '$plEsc'
                  AND LOWER(TRIM(IFNULL(status,''))) = 'approve'
                ORDER BY id DESC
                LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('gw_mpo_unit_formula_json_columns_nonempty')) {
    function gw_mpo_unit_formula_json_columns_nonempty($row) {
        if (!is_array($row)) {
            return false;
        }
        foreach (array('raw_materials', 'primary_pm_list', 'consumeableMaterial', 'packing_materials', 'additional_materials') as $field) {
            $s = trim((string)($row[$field] ?? ''));
            if ($s !== '' && $s !== 'null' && $s !== '[]' && $s !== '{}') {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('gw_mpo_unit_formula_row_has_materials')) {
    function gw_mpo_unit_formula_row_has_materials($conn, $row) {
        if (!is_array($row)) {
            return false;
        }
        if (gw_mpo_unit_formula_json_columns_nonempty($row)) {
            foreach (array('raw_materials', 'primary_pm_list', 'consumeableMaterial', 'packing_materials', 'additional_materials') as $field) {
                if (gw_mpo_json_list_has_materials($row[$field] ?? null)) {
                    return true;
                }
            }
        }
        $id = (int)($row['id'] ?? 0);
        return $id > 0 && gw_mpo_unit_formula_has_packing_dtl_materials($conn, $id);
    }
}

if (!function_exists('gw_mpo_get_marketing_unit_formula_row')) {
    /**
     * Same scope as common.php getProductsss — approved unit formula for product at manufacturing plant.
     */
    function gw_mpo_get_marketing_unit_formula_row($conn, $productCode, $plantId = '') {
        $productCode = trim((string)$productCode);
        $plantId = trim((string)$plantId);
        if ($productCode === '') {
            return null;
        }
        $pcEsc = $conn->real_escape_string($productCode);
        $plantWhere = '';
        if ($plantId !== '') {
            $plantWhere = " AND uf.plant_id = '" . $conn->real_escape_string($plantId) . "'";
        }
        $sql = "SELECT uf.id, uf.mfr_no, uf.plant_id, uf.status,
                       uf.raw_materials, uf.primary_pm_list, uf.consumeableMaterial,
                       uf.packing_materials, uf.additional_materials
                FROM unitformula uf
                INNER JOIN product p ON p.product_code = uf.product_code AND p.plant_id = uf.plant_id
                WHERE uf.product_code = '$pcEsc'" . $plantWhere . "
                  AND (" . gw_marketing_unit_formula_status_sql('uf') . ")
                ORDER BY
                  CASE
                    WHEN LOWER(TRIM(IFNULL(uf.status,''))) IN ('approve', 'approved') THEN 0
                    WHEN TRIM(IFNULL(uf.approve_by,'')) <> '' AND TRIM(IFNULL(uf.checked_by,'')) <> '' THEN 1
                    ELSE 2
                  END,
                  uf.id DESC
                LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('gw_mpo_marketing_formula_allows_po')) {
    /** PO/MRP gate — aligned with marketing product dropdown (approved unit formula at plant). */
    function gw_mpo_marketing_formula_allows_po($conn, $productCode, $plantId = '') {
        $row = gw_mpo_get_marketing_unit_formula_row($conn, $productCode, $plantId);
        if (!$row) {
            return array('ok' => false, 'row' => null);
        }
        $hasMaterials = gw_mpo_unit_formula_row_has_materials($conn, $row)
            || gw_mpo_unit_formula_json_columns_nonempty($row);
        return array('ok' => $hasMaterials, 'row' => $row);
    }
}

if (!function_exists('gw_mpo_get_work_order_material_codes')) {
    /** Material codes from latest unit formula (same source as MRP / factory order WO). */
    function gw_mpo_get_work_order_material_codes($conn, $productCode) {
        $productCode = trim((string)$productCode);
        if ($productCode === '') {
            return array();
        }
        $esc = $conn->real_escape_string($productCode);
        $sql = "SELECT raw_materials, primary_pm_list, consumeableMaterial
                FROM unitformula
                WHERE product_code = '" . $esc . "'
                ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $codes = array();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $codes = array_merge(
                $codes,
                gw_mpo_collect_material_codes_from_json_list($row['raw_materials'] ?? null),
                gw_mpo_collect_material_codes_from_json_list($row['primary_pm_list'] ?? null),
                gw_mpo_collect_material_codes_from_json_list($row['consumeableMaterial'] ?? null)
            );
        }
        $bfrSql = "SELECT bfr_no FROM batch_formula_info
                   WHERE product_code = '" . $esc . "'
                   ORDER BY id DESC LIMIT 1";
        $bfrRes = $conn->query($bfrSql);
        if ($bfrRes && $bfrRes->num_rows > 0) {
            $bfrNo = trim((string)($bfrRes->fetch_assoc()['bfr_no'] ?? ''));
            if ($bfrNo !== '') {
                $bfrEsc = $conn->real_escape_string($bfrNo);
                $bmSql = "SELECT DISTINCT material_code FROM batch_materials WHERE bfr_no = '" . $bfrEsc . "'";
                $bmRes = $conn->query($bmSql);
                if ($bmRes) {
                    while ($bm = $bmRes->fetch_assoc()) {
                        $code = trim((string)($bm['material_code'] ?? ''));
                        if ($code !== '') {
                            $codes[] = $code;
                        }
                    }
                }
            }
        }
        return array_values(array_unique($codes));
    }
}

if (!function_exists('gw_check_product_formula_for_po_impl')) {
    /**
     * Marketing PO product gate — unit formula with materials is enough to add a line.
     * Handles JSON material lists, legacy unit_materials.mfr_no (numeric id or MFR string),
     * unitformula_packing_materials, and batch_formula_info linked by product_code or mfr_no.
     */
    function gw_check_product_formula_for_po_impl($conn, $productCode, $plantId = '') {
        $productCode = trim((string)$productCode);
        $plantId = trim((string)$plantId);
        $pcEsc = $conn->real_escape_string($productCode);
        $plantWhere = '';
        if ($plantId !== '') {
            $plantWhere = " AND plant_id = '" . $conn->real_escape_string($plantId) . "'";
        }

        // Fast path — matches marketing product dropdown (getProductsss).
        if ($plantId !== '') {
            $marketing = gw_mpo_marketing_formula_allows_po($conn, $productCode, $plantId);
            if ($marketing['ok']) {
                return array(
                    'status' => 'success',
                    'can_add' => true,
                    'has_unit_formula' => true,
                    'has_unit_materials' => true,
                    'has_batch_formula' => false,
                    'has_batch_materials' => false,
                    'plant_id' => $plantId,
                    'message' => 'Formula available',
                    'source' => 'approved_unit_formula',
                    'unit_formula_id' => (int)($marketing['row']['id'] ?? 0),
                );
            }
        }

        $unitRows = array();
        $ufSql = "SELECT id, mfr_no, raw_materials, primary_pm_list, consumeableMaterial, packing_materials, additional_materials
                  FROM unitformula
                  WHERE product_code = '$pcEsc'" . $plantWhere . "
                  ORDER BY id DESC";
        $ufRes = $conn->query($ufSql);
        if ($ufRes) {
            while ($r = $ufRes->fetch_assoc()) {
                $unitRows[] = $r;
            }
        }

        $hasUnitFormula = count($unitRows) > 0;
        $hasUnitMaterials = false;
        $unitIds = array();
        $mfrNos = array();

        foreach ($unitRows as $row) {
            $unitIds[] = (int)$row['id'];
            $mfr = trim((string)($row['mfr_no'] ?? ''));
            if ($mfr !== '') {
                $mfrNos[] = $mfr;
            }
            if (gw_mpo_unit_formula_row_has_materials($conn, $row)) {
                $hasUnitMaterials = true;
                break;
            }
        }

        if (!$hasUnitMaterials && $hasUnitFormula) {
            $joinPlant = $plantId !== '' ? " AND uf.plant_id = '" . $conn->real_escape_string($plantId) . "'" : '';
            $umJoinSql = "SELECT COUNT(*) AS c FROM unit_materials um
                          INNER JOIN unitformula uf ON (
                              um.mfr_no = CAST(uf.id AS CHAR)
                              OR um.mfr_no = uf.mfr_no
                          )
                          WHERE uf.product_code = '$pcEsc'" . $joinPlant . "
                            AND TRIM(IFNULL(um.material_code,'')) <> ''";
            $umRes = $conn->query($umJoinSql);
            if ($umRes && ($r = $umRes->fetch_assoc()) && (int)$r['c'] > 0) {
                $hasUnitMaterials = true;
            }
        }

        if (!$hasUnitMaterials && (count($unitIds) > 0 || count($mfrNos) > 0)) {
            $keys = array();
            foreach ($unitIds as $id) {
                $keys[] = "'" . $conn->real_escape_string((string)$id) . "'";
            }
            foreach ($mfrNos as $mfr) {
                $keys[] = "'" . $conn->real_escape_string($mfr) . "'";
            }
            $keys = array_values(array_unique($keys));
            if (count($keys) > 0) {
                $keyList = implode(',', $keys);
                $umRes = $conn->query("SELECT COUNT(*) AS c FROM unit_materials
                                       WHERE mfr_no IN ($keyList)
                                         AND TRIM(IFNULL(material_code,'')) <> ''");
                if ($umRes && ($r = $umRes->fetch_assoc()) && (int)$r['c'] > 0) {
                    $hasUnitMaterials = true;
                }
            }
        }

        $bfrPlantWhere = $plantId !== '' ? " AND plant_id = '" . $conn->real_escape_string($plantId) . "'" : '';
        $bfrNos = array();
        $bfrInfoIds = array();
        $bfrRes = $conn->query("SELECT id, bfr_no FROM batch_formula_info
                                WHERE product_code = '$pcEsc'" . $bfrPlantWhere . "
                                  AND TRIM(IFNULL(bfr_no,'')) <> ''");
        if ($bfrRes) {
            while ($r = $bfrRes->fetch_assoc()) {
                $bfrNos[] = trim((string)$r['bfr_no']);
                $bfrInfoIds[] = (int)$r['id'];
            }
        }
        if (count($mfrNos) > 0) {
            $mfrList = "'" . implode("','", array_map(array($conn, 'real_escape_string'), array_unique($mfrNos))) . "'";
            $bfrRes2 = $conn->query("SELECT id, bfr_no FROM batch_formula_info
                                     WHERE mfr_no IN ($mfrList)" . $bfrPlantWhere . "
                                       AND TRIM(IFNULL(bfr_no,'')) <> ''");
            if ($bfrRes2) {
                while ($r = $bfrRes2->fetch_assoc()) {
                    $bfrNos[] = trim((string)$r['bfr_no']);
                    $bfrInfoIds[] = (int)$r['id'];
                }
            }
        }
        $bfrNos = array_values(array_unique(array_filter($bfrNos)));
        $bfrInfoIds = array_values(array_unique(array_filter($bfrInfoIds)));
        $hasBatchFormula = count($bfrNos) > 0;

        $hasBatchMaterials = false;
        $bmPlantWhere = $plantId !== '' ? " AND plant_id = '" . $conn->real_escape_string($plantId) . "'" : '';
        if ($hasBatchFormula) {
            $bfrList = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $bfrNos)) . "'";
            $bmRes = $conn->query("SELECT COUNT(*) AS c FROM batch_materials
                                   WHERE bfr_no IN ($bfrList)" . $bmPlantWhere . "
                                     AND TRIM(IFNULL(material_code,'')) <> ''");
            if ($bmRes && ($r = $bmRes->fetch_assoc()) && (int)$r['c'] > 0) {
                $hasBatchMaterials = true;
            }
            if (!$hasBatchMaterials && $bmPlantWhere !== '') {
                $bmRes2 = $conn->query("SELECT COUNT(*) AS c FROM batch_materials
                                        WHERE bfr_no IN ($bfrList)
                                          AND TRIM(IFNULL(material_code,'')) <> ''");
                if ($bmRes2 && ($r = $bmRes2->fetch_assoc()) && (int)$r['c'] > 0) {
                    $hasBatchMaterials = true;
                }
            }
        }
        if (!$hasBatchMaterials && count($bfrInfoIds) > 0) {
            $idList = implode(',', $bfrInfoIds);
            $bmRes = $conn->query("SELECT COUNT(*) AS c FROM batch_materials
                                   WHERE `no` IN ($idList)" . $bmPlantWhere . "
                                     AND TRIM(IFNULL(material_code,'')) <> ''");
            if ($bmRes && ($r = $bmRes->fetch_assoc()) && (int)$r['c'] > 0) {
                $hasBatchMaterials = true;
            }
        }
        if (!$hasBatchMaterials && ($hasBatchFormula || count($bfrInfoIds) > 0)) {
            if (gw_mpo_product_batch_formula_json_has_materials($conn, $productCode, $plantId, $bfrNos)) {
                $hasBatchMaterials = true;
            }
        }

        $canAdd = ($hasUnitFormula && $hasUnitMaterials) || ($hasBatchFormula && $hasBatchMaterials);

        if (!$canAdd && $plantId !== '') {
            $marketing = gw_mpo_marketing_formula_allows_po($conn, $productCode, $plantId);
            if ($marketing['ok']) {
                $canAdd = true;
                $hasUnitFormula = true;
                $hasUnitMaterials = true;
            }
        }

        $reasons = array();
        if (!$hasUnitFormula && !$hasBatchFormula) {
            $reasons[] = 'no unit formula or batch formula for this plant';
        } else {
            if ($hasUnitFormula && !$hasUnitMaterials) {
                $reasons[] = 'unit formula has no materials';
            }
            if ($hasBatchFormula && !$hasBatchMaterials) {
                $reasons[] = 'batch formula has no materials';
            }
            if (!$hasUnitFormula && $hasBatchFormula && !$hasBatchMaterials) {
                $reasons = array('batch formula has no materials');
            }
        }
        if (count($reasons) === 0 && !$canAdd) {
            $reasons[] = 'formula not available';
        }

        $message = $canAdd
            ? 'Formula available'
            : ('Cannot add the product — ' . implode(' and ', $reasons)
                . '. Please create the formula from the FO / master first.');

        return array(
            'status' => 'success',
            'can_add' => $canAdd,
            'has_unit_formula' => $hasUnitFormula,
            'has_unit_materials' => $hasUnitMaterials,
            'has_batch_formula' => $hasBatchFormula,
            'has_batch_materials' => $hasBatchMaterials,
            'plant_id' => $plantId,
            'message' => $message,
        );
    }
}

if (!function_exists('gw_gwo_decode_json_list')) {
    function gw_gwo_decode_json_list($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '' || trim($value) === 'null') {
            return array();
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : array();
    }
}

if (!function_exists('gw_gwo_bfr_qty_ratio')) {
    function gw_gwo_bfr_qty_ratio($woBatchSize, $bfrFormulaWeight)
    {
        $wo = floatval($woBatchSize);
        $bf = floatval($bfrFormulaWeight);
        if ($wo <= 0) {
            return 1.0;
        }
        if ($bf <= 0) {
            return 1.0;
        }
        return $wo / $bf;
    }
}

if (!function_exists('gw_gwo_pick_scaled_qty')) {
    function gw_gwo_pick_scaled_qty(array $item, $ratio)
    {
        if (isset($item['batch_qty']) && floatval($item['batch_qty']) > 0) {
            return round(floatval($item['batch_qty']) * $ratio, 4);
        }
        if (isset($item['total_qty']) && floatval($item['total_qty']) > 0) {
            return round(floatval($item['total_qty']) * $ratio, 4);
        }
        if (isset($item['qty']) && floatval($item['qty']) > 0) {
            return round(floatval($item['qty']) * $ratio, 4);
        }
        return 0;
    }
}

if (!function_exists('gw_gwo_get_bfr_info_row')) {
    function gw_gwo_get_bfr_info_row($conn, $bfrNo)
    {
        $bfrNo = trim((string)$bfrNo);
        if ($bfrNo === '') {
            return null;
        }
        $bfrEsc = $conn->real_escape_string($bfrNo);
        $res = $conn->query("SELECT bfr_no, raw_materials, packing_materials, batch_formula_weight, rm_batch_size_unit
                             FROM batch_formula_info WHERE bfr_no = '$bfrEsc' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('gw_gwo_resolve_available_qty')) {
    /**
     * Available qty for Generate WO stock check.
     * Prefer vw_total_available_stock; if zero/missing, fall back to Approved stock_book
     * minus material_issue rows that are tied to a work order (orphan issues ignored).
     */
    function gw_gwo_resolve_available_qty($conn, $material_code)
    {
        $code = trim((string)$material_code);
        if ($code === '' || !($conn instanceof mysqli)) {
            return 0.0;
        }
        $codeEsc = $conn->real_escape_string($code);
        $vw = @$conn->query("SELECT CAST(IFNULL(available_qty,0) AS DECIMAL(18,4)) AS available_qty
            FROM vw_total_available_stock WHERE material_code = '$codeEsc' LIMIT 1");
        if ($vw && $vw->num_rows > 0) {
            $avail = floatval($vw->fetch_assoc()['available_qty'] ?? 0);
            if ($avail > 0) {
                return round($avail, 4);
            }
        }
        $approved = 0.0;
        $res = @$conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(18,4))),0) AS qty
            FROM stock_book WHERE material_code = '$codeEsc' AND status = 'Approved'");
        if ($res && ($row = $res->fetch_assoc())) {
            $approved = floatval($row['qty']);
        }
        $issued = 0.0;
        $res2 = @$conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(18,4))),0) AS qty
            FROM material_issue
            WHERE material_code = '$codeEsc'
              AND TRIM(IFNULL(workorder_no,'')) <> ''");
        if ($res2 && ($row2 = $res2->fetch_assoc())) {
            $issued = floatval($row2['qty']);
        }
        return round(max(0, $approved - $issued), 4);
    }
}

if (!function_exists('gw_gwo_append_rm_stock_pools')) {
    function gw_gwo_append_rm_stock_pools($conn, array $rm, array &$rm_stock, array &$MC_rm_stock)
    {
        if (!empty($rm['isBulk']) && $rm['isBulk']) {
            $rm_stock[] = array('material_code' => $rm['material_code'] ?? '', 'available_qty' => 0);
            $MC_rm_stock[] = array('material_code' => $rm['material_code'] ?? '', 'available_qty' => 0);
            return;
        }
        $code = trim((string)($rm['material_code'] ?? ''));
        $motherCode = trim((string)($rm['mother_material_code'] ?? $rm['material_code'] ?? ''));
        $rm_stock[] = array(
            'material_code' => $code,
            'available_qty' => gw_gwo_resolve_available_qty($conn, $code),
        );
        $MC_rm_stock[] = array(
            'material_code' => $motherCode,
            'available_qty' => gw_gwo_resolve_available_qty($conn, $motherCode),
        );
    }
}

if (!function_exists('gw_gwo_build_rm_from_json_item')) {
    function gw_gwo_build_rm_from_json_item($conn, array $item, $ratio)
    {
        $code = trim((string)($item['material_code'] ?? ''));
        if ($code === '' || $code === '-' || strtoupper($code) === 'N/A') {
            return null;
        }
        $subtype = strtolower(trim((string)($item['material_subtype'] ?? '')));
        if ($subtype !== '' && (strpos($subtype, 'premix') !== false || strpos($subtype, 'primix') !== false)) {
            return null;
        }
        $batchQty = gw_gwo_pick_scaled_qty($item, $ratio);
        if ($batchQty <= 0) {
            return null;
        }
        $codeEsc = $conn->real_escape_string($code);
        $matRes = $conn->query("SELECT material_type, material_subtype, mother_code FROM material WHERE material_code = '$codeEsc' LIMIT 1");
        $matRow = ($matRes && $matRes->num_rows > 0) ? $matRes->fetch_assoc() : array();
        $bulkRes = $conn->query("SELECT bulkCode FROM bulkMaster WHERE bulkCode = '$codeEsc' LIMIT 1");
        $isBulk = ($bulkRes && $bulkRes->num_rows > 0)
            || (strtolower(trim((string)($matRow['material_type'] ?? ''))) === 'bulk');
        $mother = trim((string)($matRow['mother_code'] ?? $code));
        $rm = array_merge(array(
            'material_code' => $code,
            'material_name' => gw_resolve_material_name($conn, $code) ?: trim((string)($item['material_name'] ?? '')),
            'unit' => trim((string)($item['unit'] ?? $item['Converted_unit'] ?? 'Nos')),
            'batch_qty' => $batchQty,
            'mother_material_code' => $mother,
            'isBulk' => $isBulk,
        ), gw_get_shortages_stock_fields($conn, $code));
        if ($isBulk) {
            $rm['bulkCode'] = $code;
            $bulkStockResult = $conn->query("SELECT SUM(COALESCE(mfg_qty, 0) - COALESCE(used_qty, 0)) AS available_qty
                FROM bulk_stock WHERE bulkCode = '$codeEsc' GROUP BY bulkCode");
            $bulkAvail = ($bulkStockResult && $bulkStockResult->num_rows > 0)
                ? floatval($bulkStockResult->fetch_assoc()['available_qty'])
                : 0;
            $rm['bulkStock'] = $bulkAvail;
            $rm['available_Stock_qty'] = $bulkAvail;
            $rm['available_qty'] = $bulkAvail;
            $rm['rm_balance'] = $bulkAvail;
        }
        return $rm;
    }
}

if (!function_exists('gw_gwo_hydrate_materials_from_bfr_json')) {
    /**
     * When batch_materials table rows are missing, load RM / PM / consumables from
     * batch_formula_info JSON (same source as Batch Formula master screen).
     */
    function gw_gwo_hydrate_materials_from_bfr_json(
        $conn,
        $bfrNo,
        array $woRow,
        array &$raw_materials,
        array &$packing_materials,
        array &$primary_pm_list,
        array &$consumeableMaterial,
        array &$packing_configuration,
        array &$rm_stock,
        array &$MC_rm_stock,
        array &$pm_stock,
        array &$MC_pm_stock,
        array &$consumable_stock,
        array &$packing_config_stock
    ) {
        $bfr = gw_gwo_get_bfr_info_row($conn, $bfrNo);
        if (!$bfr) {
            return;
        }
        $ratio = gw_gwo_bfr_qty_ratio($woRow['batch_size'] ?? 0, $bfr['batch_formula_weight'] ?? 0);

        if (count($raw_materials) === 0) {
            foreach (gw_gwo_decode_json_list($bfr['raw_materials'] ?? '') as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $rm = gw_gwo_build_rm_from_json_item($conn, $item, $ratio);
                if (!$rm) {
                    continue;
                }
                $raw_materials[] = $rm;
                gw_gwo_append_rm_stock_pools($conn, $rm, $rm_stock, $MC_rm_stock);
            }
        }

        foreach (gw_gwo_decode_json_list($bfr['packing_materials'] ?? '') as $group) {
            if (!is_array($group)) {
                continue;
            }
            $pmType = strtolower(trim((string)($group['pm_type'] ?? $group['packing_type'] ?? '')));
            $packList = $group['packing_list'] ?? array();
            if (!is_array($packList)) {
                continue;
            }

            if (strpos($pmType, 'primary') !== false && count($primary_pm_list) === 0) {
                foreach ($packList as $pm) {
                    if (!is_array($pm)) {
                        continue;
                    }
                    $code = trim((string)($pm['material_code'] ?? ''));
                    if ($code === '' || $code === '-') {
                        continue;
                    }
                    $qty = floatval($pm['qty'] ?? 0);
                    $overages = floatval($pm['overages'] ?? 0);
                    $totalQty = gw_gwo_pick_scaled_qty($pm, $ratio);
                    if ($totalQty <= 0 && $qty > 0) {
                        $totalQty = round($qty * (1 + ($overages / 100)) * $ratio, 4);
                    }
                    $primary_pm_list[] = array_merge(array(
                        'material_code' => $code,
                        'material_name' => gw_resolve_material_name($conn, $code) ?: trim((string)($pm['material_name'] ?? '')),
                        'gradeName' => is_array($pm['gradeName'] ?? null) ? ($pm['gradeName'][0] ?? '') : ($pm['grade'] ?? $pm['gradeName'] ?? '-'),
                        'qty' => $qty,
                        'unit_name' => $pm['unit_name'] ?? $pm['unit'] ?? 'Nos',
                        'unit' => $pm['unit'] ?? 'Nos',
                        'overages' => $overages,
                        'total_qty' => $totalQty,
                        'batch_qty' => gw_gwo_pick_scaled_qty($pm, $ratio),
                    ), gw_get_material_availability($conn, $code));
                }
                continue;
            }

            if (strpos($pmType, 'consum') !== false && count($consumeableMaterial) === 0) {
                foreach ($packList as $cons) {
                    if (!is_array($cons)) {
                        continue;
                    }
                    $code = trim((string)($cons['material_code'] ?? ''));
                    if ($code === '' || $code === '-') {
                        continue;
                    }
                    $qty = floatval($cons['qty'] ?? 0);
                    $overages = floatval($cons['overages'] ?? 0);
                    $totalQty = gw_gwo_pick_scaled_qty($cons, $ratio);
                    if ($totalQty <= 0 && $qty > 0) {
                        $totalQty = round($qty * (1 + ($overages / 100)) * $ratio, 4);
                    }
                    $consumeableMaterial[] = array_merge(array(
                        'material_code' => $code,
                        'material_name' => gw_resolve_material_name($conn, $code) ?: trim((string)($cons['material_name'] ?? '')),
                        'gradeName' => is_array($cons['gradeName'] ?? null) ? ($cons['gradeName'][0] ?? '') : ($cons['grade'] ?? $cons['gradeName'] ?? '-'),
                        'qty' => $qty,
                        'unit_name' => $cons['unit_name'] ?? $cons['unit'] ?? 'Nos',
                        'unit' => $cons['unit'] ?? 'Nos',
                        'overages' => $overages,
                        'total_qty' => $totalQty,
                        'batch_qty' => gw_gwo_pick_scaled_qty($cons, $ratio),
                    ), gw_get_material_availability($conn, $code));
                    $sCons = $conn->query("SELECT * FROM vw_total_available_stock WHERE material_code = '" . $conn->real_escape_string($code) . "'");
                    $consumable_stock[] = ($sCons && $sCons->num_rows > 0)
                        ? $sCons->fetch_assoc()
                        : array('material_code' => $code, 'available_qty' => 0);
                }
                continue;
            }

            if (strpos($pmType, 'secondary') !== false && count($packing_configuration) === 0) {
                $packing_list = array();
                foreach ($packList as $pm) {
                    if (!is_array($pm)) {
                        continue;
                    }
                    $code = trim((string)($pm['material_code'] ?? ''));
                    if ($code === '' || $code === '-') {
                        continue;
                    }
                    $qty = floatval($pm['qty'] ?? 0);
                    $overages = floatval($pm['overages'] ?? 0);
                    $batchQty = gw_gwo_pick_scaled_qty($pm, $ratio);
                    $totalQty = $batchQty;
                    if ($totalQty <= 0 && $qty > 0) {
                        $totalQty = round($qty * (1 + ($overages / 100)) * $ratio, 4);
                    }
                    $packing_list[] = array_merge(array(
                        'material_code' => $code,
                        'material_name' => gw_resolve_material_name($conn, $code) ?: trim((string)($pm['material_name'] ?? '')),
                        'qty' => $qty,
                        'unit_name' => $pm['unit_name'] ?? $pm['unit'] ?? 'Nos',
                        'overages' => $overages,
                        'total_qty' => $totalQty,
                        'batch_qty' => $batchQty > 0 ? $batchQty : $totalQty,
                    ), gw_get_material_availability($conn, $code));
                    $sPack = $conn->query("SELECT * FROM vw_total_available_stock WHERE material_code = '" . $conn->real_escape_string($code) . "'");
                    $packing_config_stock[] = ($sPack && $sPack->num_rows > 0)
                        ? $sPack->fetch_assoc()
                        : array('material_code' => $code, 'available_qty' => 0);
                }
                if (count($packing_list) > 0) {
                    $packing_configuration[] = array(
                        'batch_size' => floatval($group['batch_size'] ?? $woRow['batch_size'] ?? 0) * $ratio,
                        'pack_size' => $group['pack_size'] ?? '',
                        'unit' => $group['unit'] ?? '',
                        'packing_list' => $packing_list,
                    );
                }
            }
        }
    }
}

if (!function_exists('gw_gwo_collect_bfr_deduction_lines')) {
    /** Flat material lines for WO_deductions backfill from batch_formula_info JSON. */
    function gw_gwo_collect_bfr_deduction_lines($conn, $bfrNo, array $woRow)
    {
        $lines = array();
        $bfr = gw_gwo_get_bfr_info_row($conn, $bfrNo);
        if (!$bfr) {
            return $lines;
        }
        $ratio = gw_gwo_bfr_qty_ratio($woRow['batch_size'] ?? 0, $bfr['batch_formula_weight'] ?? 0);

        foreach (gw_gwo_decode_json_list($bfr['raw_materials'] ?? '') as $item) {
            if (!is_array($item)) {
                continue;
            }
            $code = trim((string)($item['material_code'] ?? ''));
            $qty = gw_gwo_pick_scaled_qty($item, $ratio);
            if ($code === '' || $qty <= 0) {
                continue;
            }
            $lines[] = array(
                'material_code' => $code,
                'mat_type' => 'RM',
                'plan_qty' => $qty,
                'unit' => $item['unit'] ?? $item['Converted_unit'] ?? 'Nos',
            );
        }

        foreach (gw_gwo_decode_json_list($bfr['packing_materials'] ?? '') as $group) {
            if (!is_array($group)) {
                continue;
            }
            $pmType = strtolower(trim((string)($group['pm_type'] ?? $group['packing_type'] ?? '')));
            $packList = $group['packing_list'] ?? array();
            if (!is_array($packList)) {
                continue;
            }
            $matType = 'PM';
            if (strpos($pmType, 'primary') !== false) {
                $matType = 'PRIMARY_PM';
            } elseif (strpos($pmType, 'consum') !== false) {
                $matType = 'CONSUMABLE';
            } elseif (strpos($pmType, 'secondary') !== false) {
                $matType = 'SECONDARY PACKAGING';
            }
            foreach ($packList as $pm) {
                if (!is_array($pm)) {
                    continue;
                }
                $code = trim((string)($pm['material_code'] ?? ''));
                $qty = gw_gwo_pick_scaled_qty($pm, $ratio);
                if ($code === '' || $qty <= 0) {
                    continue;
                }
                $lines[] = array(
                    'material_code' => $code,
                    'mat_type' => $matType,
                    'plan_qty' => $qty,
                    'unit' => $pm['unit_name'] ?? $pm['unit'] ?? 'Nos',
                );
            }
        }
        return $lines;
    }
}

if (!function_exists('gw_check_product_formula_for_po')) {
    function gw_check_product_formula_for_po($conn, $productCode, $plantId = '') {
        $result = gw_check_product_formula_for_po_impl($conn, $productCode, $plantId);
        if ($result['can_add'] || $plantId === '') {
            $result['formula_check_v'] = 2;
            return $result;
        }
        $fallback = gw_check_product_formula_for_po_impl($conn, $productCode, '');
        if ($fallback['can_add']) {
            $fallback['matched_any_plant'] = true;
            $fallback['requested_plant_id'] = $plantId;
            $fallback['formula_check_v'] = 2;
            return $fallback;
        }
        $result['formula_check_v'] = 2;
        return $result;
    }
}

if (!function_exists('gw_mpo_product_stage_value')) {
    function gw_mpo_product_stage_value($row, $key) {
        $map = array(
            'DispensingDate' => array('DispensingDate', 'dispensing_date'),
            'productionDate' => array('productionDate', 'production_date'),
            'Mfg2Date' => array('Mfg2Date', 'mfg2Date', 'mfg2_date', 'productionDate2'),
            'FillingDate' => array('FillingDate', 'filling_date'),
            'inprocessRelease' => array('inprocessRelease', 'inprocess_release'),
            'PackingDate' => array('PackingDate', 'packing_date', 'primary_packing_date'),
            'SecondaryPackingDate' => array('SecondaryPackingDate', 'secondaryPackingDate', 'secondary_packing_date'),
            'fgRelease' => array('fgRelease', 'fg_release'),
            'FgTransferDate' => array('FgTransferDate', 'DispatchDate', 'dispatch_date', 'fg_transfer_date'),
        );
        foreach ($map[$key] ?? array($key) as $field) {
            if (isset($row[$field]) && trim((string)$row[$field]) !== '') {
                return max(0, (int)$row[$field]);
            }
        }
        return 0;
    }
}

if (!function_exists('gw_mpo_get_production_lead_time')) {
    /** Total production lead time from Micro Planning master (product timeline). */
    function gw_mpo_get_production_lead_time($conn, $productCode, $plantId = '') {
        $productCode = trim((string)$productCode);
        $stages = array(
            array('key' => 'DispensingDate', 'label' => 'RM/PM Dispensing'),
            array('key' => 'productionDate', 'label' => 'Manufacturing'),
            array('key' => 'Mfg2Date', 'label' => 'Mfg 2'),
            array('key' => 'FillingDate', 'label' => 'Filling'),
            array('key' => 'inprocessRelease', 'label' => 'Inprocess Release'),
            array('key' => 'PackingDate', 'label' => 'Primary Packing'),
            array('key' => 'SecondaryPackingDate', 'label' => 'Secondary Packing'),
            array('key' => 'fgRelease', 'label' => 'FG Release'),
            array('key' => 'FgTransferDate', 'label' => 'FG Transfer'),
        );
        $empty = array(
            'production_lead_time_days' => 0,
            'production_stages' => array(),
            'production_lead_source' => 'none',
            'product_name' => '',
        );
        if ($productCode === '') {
            return $empty;
        }
        $esc = $conn->real_escape_string($productCode);
        $plantWhere = '';
        if (trim((string)$plantId) !== '') {
            $plantWhere = " AND plant_id = '" . $conn->real_escape_string($plantId) . "'";
        }
        $sql = "SELECT product_name, lead_time,
                       DispensingDate, productionDate, Mfg2Date, FillingDate,
                       inprocessRelease, PackingDate, SecondaryPackingDate, fgRelease, FgTransferDate
                FROM product
                WHERE product_code = '" . $esc . "'" . $plantWhere . "
                ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            return $empty;
        }
        $row = $result->fetch_assoc();
        $stageRows = array();
        $sumStages = 0;
        foreach ($stages as $stage) {
            $days = gw_mpo_product_stage_value($row, $stage['key']);
            $sumStages += $days;
            $stageRows[] = array(
                'stage' => $stage['label'],
                'days' => $days,
            );
        }
        $stored = max(0, (int)($row['lead_time'] ?? 0));
        if ($stored > 0) {
            $total = $stored;
            $source = 'lead_time';
        } elseif ($sumStages > 0) {
            $total = $sumStages;
            $source = 'sum_of_stages';
        } else {
            $total = 0;
            $source = 'none';
        }
        return array(
            'production_lead_time_days' => $total,
            'production_stages' => $stageRows,
            'production_lead_source' => $source,
            'product_name' => trim((string)($row['product_name'] ?? '')),
        );
    }
}

if (!function_exists('gw_mpo_calc_schedule_delivery')) {
    function gw_mpo_calc_schedule_delivery($conn, $productCode, $plantId, $baseDate) {
        $baseDate = trim((string)$baseDate);
        if ($baseDate === '' || strtotime($baseDate) === false) {
            $baseDate = date('Y-m-d');
        } else {
            $baseDate = date('Y-m-d', strtotime($baseDate));
        }
        $materialCodes = gw_mpo_get_work_order_material_codes($conn, $productCode);
        gw_mpo_prefetch_material_lead_rows($conn, $materialCodes);
        $cache = gw_mpo_material_lead_cache();
        $materialRows = array();
        $maxPurchase = 0;
        $maxMaterial = null;
        foreach ($materialCodes as $code) {
            $entry = $cache[$code] ?? array(
                'material_code' => $code,
                'material_name' => '',
                'lead_time_days' => 0,
                'breakdown' => array(
                    'indent_approval' => 0,
                    'po_after_indent' => 0,
                    'payment_after_po' => 0,
                    'vendor_transit' => 0,
                ),
            );
            $materialRows[] = array(
                'material_code' => $entry['material_code'],
                'material_name' => $entry['material_name'],
                'lead_time_days' => (int)$entry['lead_time_days'],
            );
            if ((int)$entry['lead_time_days'] > $maxPurchase) {
                $maxPurchase = (int)$entry['lead_time_days'];
                $maxMaterial = $entry;
            }
        }
        usort($materialRows, function ($a, $b) {
            return ($b['lead_time_days'] ?? 0) <=> ($a['lead_time_days'] ?? 0);
        });
        $production = gw_mpo_get_production_lead_time($conn, $productCode, $plantId);
        $productionDays = (int)($production['production_lead_time_days'] ?? 0);
        $totalDays = $maxPurchase + $productionDays;
        $deliveryTs = strtotime('+' . $totalDays . ' days', strtotime($baseDate));
        return array(
            'status' => 'success',
            'base_date' => $baseDate,
            'product_code' => $productCode,
            'materials_checked_count' => count($materialCodes),
            'purchase_lead_time_days' => $maxPurchase,
            'production_lead_time_days' => $productionDays,
            'total_lead_time_days' => $totalDays,
            'delivery_date' => $deliveryTs ? date('Y-m-d', $deliveryTs) : null,
            'max_purchase_material' => $maxMaterial ? array(
                'material_code' => $maxMaterial['material_code'],
                'material_name' => $maxMaterial['material_name'],
                'lead_time_days' => (int)$maxMaterial['lead_time_days'],
                'breakdown' => $maxMaterial['breakdown'],
            ) : null,
            'purchase_materials' => $materialRows,
            'production_stages' => $production['production_stages'] ?? array(),
            'production_lead_source' => $production['production_lead_source'] ?? 'none',
            'product_name' => $production['product_name'] ?? '',
            'formula' => 'Delivery Date = Base Date + Max Purchase Lead Time + Production Lead Time',
        );
    }
}

medicap_require_helper('mrp_indents_confirmation_helpers.php');
medicap_require_helper('mrp_shortages_log_helpers.php');
medicap_require_helper('mrp_indent_status_log_helpers.php');
medicap_require_helper('mrp_dashboard_helpers.php');
medicap_require_helper('processing_po_helpers.php');
medicap_require_helper('wo_analysis_proceed_helpers.php');
medicap_require_helper('wo_plan_status_helpers.php');
medicap_require_helper('mrp_audit_log_helpers.php');
medicap_require_helper('change_forecast_plan_helpers.php');

$apiToken = $conn->real_escape_string($token);
$_GET['token'] = $apiToken;
$token = $apiToken;

$sql = "SELECT * FROM token WHERE token='".$apiToken."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($apiToken === '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status' => 'error', 'message' => 'Missing session token. Please login again.'));
    $conn->close();
    exit;
}

if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if($_GET["type"]=="getClients") {
        $output = array();
    	$sql = "SELECT id, plant_id, client_code, status, LglNm, TrdNm, client_type, category,
            contactPerson, designation, mobNo, email, address, billingAddress, country, state,
            city, pincode, state_code, gst_no, pan_no, refered_by, agent_no, createGroup, clientGroup
            FROM client
            WHERE LOWER(TRIM(COALESCE(status, ''))) NOT IN ('rejected', 'reject')
            ORDER BY LglNm ASC";
    	$result = $conn->query($sql);
    	if($result && $result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    } else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE status='approve' AND dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      
    else if ($_GET["type"] == "getUnits") {
        $output = Array();
        $sql = "SELECT * FROM leadunit where plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveunit") {
        $output = Array();
        $sql = "INSERT INTO leadunit(unit, plant_id) VALUES ('".$_GET["unit"]."','".$_GET["plant_id"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     } 
    else if ($_GET["type"] == "receivePO") {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = $_POST;
            $pid = $conn->real_escape_string((string)($_GET["plantID"] ?? ''));
            $po = $conn->real_escape_string((string)($input["po_no"] ?? ''));

            $file_name = date("YmdHis", $timestamp);
            $shippingMark = '';
            $artwork = '';
            $otherfile = '';
            $uploadDir = realpath(__DIR__ . '/../../../upload/poentry');
            if ($uploadDir === false) {
                $uploadDir = __DIR__ . '/../../../upload/poentry';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }
            }

            if (isset($_FILES["file"]["name"]) && $_FILES["file"]["name"] !== '') {
                $parts = explode('.', $_FILES['file']['name']);
                $file_ext = strtolower((string)end($parts));
                @move_uploaded_file($_FILES["file"]["tmp_name"], $uploadDir . "/" . $file_name . $po . '.' . $file_ext);
                $file_name = $file_name . $po . '.' . $file_ext;
            }

            if (isset($_FILES["shippingMark"]["name"]) && $_FILES["shippingMark"]["name"] !== '') {
                $parts = explode('.', $_FILES['shippingMark']['name']);
                $file_ext = strtolower((string)end($parts));
                @move_uploaded_file($_FILES["shippingMark"]["tmp_name"], $uploadDir . "/" . $po . $pid . 'shippingMark.' . $file_ext);
                $shippingMark = $po . $pid . 'shippingMark.' . $file_ext;
            }

            if (isset($_FILES["artwork"]["name"]) && $_FILES["artwork"]["name"] !== '') {
                $parts = explode('.', $_FILES['artwork']['name']);
                $file_ext = strtolower((string)end($parts));
                @move_uploaded_file($_FILES["artwork"]["tmp_name"], $uploadDir . "/" . $po . $pid . 'artwork.' . $file_ext);
                $artwork = $po . $pid . 'artwork.' . $file_ext;
            }

            if (isset($_FILES["otherfile"]["name"]) && $_FILES["otherfile"]["name"] !== '') {
                $parts = explode('.', $_FILES['otherfile']['name']);
                $file_ext = strtolower((string)end($parts));
                @move_uploaded_file($_FILES["otherfile"]["tmp_name"], $uploadDir . "/" . $po . $pid . 'otherfile.' . $file_ext);
                $otherfile = $po . $pid . 'otherfile.' . $file_ext;
            }

            $productsJson = $conn->real_escape_string($input["products"] ?? '[]');
            $termsJson = $conn->real_escape_string($input["terms"] ?? '[]');
            $subClient = $conn->real_escape_string($input["subClient"] ?? '');
            $clientCode = $conn->real_escape_string($input["client_code"] ?? '');
            $poType = $conn->real_escape_string($input["po_type"] ?? '');
            $pfiNo = $conn->real_escape_string($input["pfi_no"] ?? '');
            $poDate = $conn->real_escape_string($input["po_date"] ?? '');
            $validTill = $conn->real_escape_string($input["valid_till"] ?? '');
            $exportCountry = $conn->real_escape_string($input["export_country"] ?? '');
            $freightBy = $conn->real_escape_string($input["freight_by"] ?? '');
            $deliveryDate = $conn->real_escape_string($input["delivery_date"] ?? '');
            $country = $conn->real_escape_string($input["country"] ?? '');
            $conisgnee = $conn->real_escape_string($input["conisgnee"] ?? '');
            $epoNo = $conn->real_escape_string($input["epo_no"] ?? '');
            $modeShip = $conn->real_escape_string($input["mode_ship"] ?? '');
            $containerStuffing = $conn->real_escape_string($input["container_stuffing"] ?? '');
            $clientType = $conn->real_escape_string($input["client_type"] ?? '');
            $planType = trim((string)($input["plan_type"] ?? $input["client_type"] ?? ''));
            if ($planType !== '') {
                $clientType = $conn->real_escape_string($planType);
            }
            $forClient = trim((string)($input["for_client"] ?? ''));
            $isForecastEntry = (strcasecmp($planType, 'Forecast') === 0);
            $shippingMarkRemark = $conn->real_escape_string($input["shipping_mark_remark"] ?? '');
            $narration = $conn->real_escape_string($input["narration"] ?? '');
            $plantId = $conn->real_escape_string((string)($_GET["plantID"] ?? ''));
            $userNo = $conn->real_escape_string((string)($_GET["user_no"] ?? ''));
            $empId = $conn->real_escape_string((string)($_GET["emp_id"] ?? ''));
            $fileNameEsc = $conn->real_escape_string($file_name);
            $shippingMarkEsc = $conn->real_escape_string($shippingMark);
            $artworkEsc = $conn->real_escape_string($artwork);
            $otherfileEsc = $conn->real_escape_string($otherfile);

            if ($po !== '') {
                $dupSql = "SELECT id FROM po_entry WHERE po_no='".$po."' AND plant_id='".$plantId."' AND user_no='".$userNo."' LIMIT 1";
                $dupResult = $conn->query($dupSql);
                if ($dupResult && $dupResult->num_rows > 0) {
                    ob_end_clean();
                    echo json_encode(array(
                        'status' => 'error',
                        'message' => 'This PO number has already been submitted. Please use a different PO number.',
                    ));
                    exit;
                }
            } else if (!$isForecastEntry && $forClient !== 'No') {
                ob_end_clean();
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Customer Order No is required.',
                ));
                exit;
            }

            $sql = "INSERT INTO po_entry(plant_id,user_no, client_code,po_type,pfi_no, po_no, po_date, valid_till, products, configurations,
            freight_by, required_date, file, entry_by, entry_date,
            country,conisgnee,epo_no,mode_ship,container_stuffing,client_type,shipping_mark_remark,shippingMark,artwork,narration, terms,otherfile,subClient )
            VALUES ('".$plantId."','".$userNo."','".$clientCode."', '".$poType."',
            '".$pfiNo."','".$po."', '".$poDate."', '".$validTill."', '".$productsJson."', '".$exportCountry."',
             '".$freightBy."', '".$deliveryDate."', '".$fileNameEsc."', '".$empId."',
            '$entry_date','".$country."','".$conisgnee."','".$epoNo."',
            '".$modeShip."','".$containerStuffing."','".$clientType."','".$shippingMarkRemark."',
            '".$shippingMarkEsc."','".$artworkEsc."','".$narration."','".$termsJson."','".$otherfileEsc."','".$subClient."')";

            if (!$conn->query($sql)) {
                ob_end_clean();
                echo json_encode(array('status' => 'error', 'message' => $conn->error));
                exit;
            }

            $insert_id = (int)$conn->insert_id;
            $order_no = '';
            $orderResult = $conn->query("SELECT order_no FROM po_entry WHERE id='" . $insert_id . "'");
            if ($orderResult && $orderResult->num_rows > 0) {
                $orderRow = $orderResult->fetch_assoc();
                $order_no = $orderRow['order_no'] ?? '';
            }

            $products = json_decode($input["products"] ?? '[]', true);
            $lineErrors = array();
            if (is_array($products)) {
                for ($i = 0; $i < count($products); $i++) {
                    $product = $products[$i];
                    $productCode = $conn->real_escape_string((string)($product["product_code"] ?? ''));
                    $unit = $conn->real_escape_string((string)($product["unit"] ?? ''));
                    $quantity = $conn->real_escape_string((string)($product["quantity"] ?? ''));
                    $qtyInPacks = $conn->real_escape_string((string)($product["quantity_inpacks"] ?? ''));
                    $productDeliveryDate = $conn->real_escape_string((string)($product["delivery_date"] ?? ''));
                    $packSizeJson = $conn->real_escape_string(json_encode($product["pack_size"] ?? ''));
                    $detailsJson = $conn->real_escape_string(json_encode($product));
                    $sql1 = "INSERT INTO order_materials (plant_id,order_no, product_code,unit, order_qty, pack_size, details,quantity_inpacks,deliveryDate ) VALUES
                    ('".$plantId."','".$conn->real_escape_string($order_no)."', '".$productCode."', '".$unit."',
                    '".$quantity."', '".$packSizeJson."', '".$detailsJson."','".$qtyInPacks."','".$productDeliveryDate."')";
                    if (!$conn->query($sql1)) {
                        $lineErrors[] = $conn->error;
                    }
                }
            }

            ob_end_clean();
            if (count($lineErrors) > 0) {
                echo json_encode(array(
                    'status' => 'success',
                    'warning' => 'PO saved but some product lines could not be saved',
                    'details' => $lineErrors,
                ));
            } else {
                echo json_encode(array('status' => 'success'));
            }
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    } 
    
else if ($_GET["type"] == "getProductAvailableStock") {
    $output = array('status' => 'error', 'message' => 'Invalid parameters');
    
    if (isset($_GET["product_code"]) && isset($_GET["plant_id"])) {
        $product_code = $conn->real_escape_string($_GET["product_code"]);
        $plant_id = $conn->real_escape_string($_GET["plant_id"]);
        
        // Get total stock from fg_stock_book
        $sql_stock = "SELECT COALESCE(SUM(qty), 0) AS total_stock, MAX(qty_unit) AS unit 
                      FROM fg_stock_book 
                      WHERE material_code = '" . $product_code . "' 
                      AND plant_id = '" . $plant_id . "' 
                      AND status = 'approve'";
        
        $result_stock = $conn->query($sql_stock);
        $total_stock = 0;
        $unit = 'NOS';
        
        if ($result_stock && $result_stock->num_rows > 0) {
            $row_stock = $result_stock->fetch_assoc();
            $total_stock = floatval($row_stock['total_stock']);
            $unit = $row_stock['unit'] ? $row_stock['unit'] : 'NOS';
        }
        
        // Get issued quantity from fg_material_issue
        $sql_issued = "SELECT COALESCE(SUM(qty), 0) AS issued_qty 
                       FROM fg_material_issue 
                       WHERE material_code = '" . $product_code . "' 
                     ";
        
        $result_issued = $conn->query($sql_issued);
        $issued_qty = 0;
        
        if ($result_issued && $result_issued->num_rows > 0) {
            $row_issued = $result_issued->fetch_assoc();
            $issued_qty = floatval($row_issued['issued_qty']);
        }
        
        // Also check material_issue table (if fg_material_issue doesn't have all records)
        $sql_issued2 = "SELECT COALESCE(SUM(qty), 0) AS issued_qty 
                        FROM material_issue 
                        WHERE material_code = '" . $product_code . "' 
                        AND plant_id = '" . $plant_id . "'";
        
        $result_issued2 = $conn->query($sql_issued2);
        if ($result_issued2 && $result_issued2->num_rows > 0) {
            $row_issued2 = $result_issued2->fetch_assoc();
            $issued_qty += floatval($row_issued2['issued_qty']);
        }
        
        // Get booked quantity from mrp_bookedStock
        $sql_booked = "SELECT COALESCE(SUM(qty), 0) AS booked_qty 
                       FROM mrp_bookedStock 
                       WHERE material_code = '" . $product_code . "' 
                       AND plant_id = '" . $plant_id . "'";
        
        $result_booked = $conn->query($sql_booked);
        $booked_qty = 0;
        
        if ($result_booked && $result_booked->num_rows > 0) {
            $row_booked = $result_booked->fetch_assoc();
            $booked_qty = floatval($row_booked['booked_qty']);
        }
        
        // Calculate available quantity
        $available_qty = max(0, $total_stock - $issued_qty - $booked_qty);
        
        $output = array(
            'status' => 'success',
            'total_stock' => $total_stock,
            'issued_qty' => $issued_qty,
            'booked_qty' => $booked_qty,
            'available_qty' => $available_qty,
            'unit' => $unit
        );
    }
    
    echo json_encode($output);
}
    else if ($_GET["type"] == "getScheduleDeliveryDate") {
        header('Content-Type: application/json; charset=utf-8');
        $productCode = trim((string)($_GET['product_code'] ?? ''));
        $plantId = gw_mpo_request_manufacturing_plant_id();
        $baseDate = trim((string)($_GET['base_date'] ?? $_GET['po_date'] ?? $_GET['order_date'] ?? ''));
        if ($productCode === '') {
            echo json_encode(array('status' => 'error', 'message' => 'Product code is required'));
        } else {
            echo json_encode(gw_mpo_calc_schedule_delivery($conn, $productCode, $plantId, $baseDate));
        }
    }
    else if ($_GET["type"] == "calculateForecastDeliveryDate") {
        header('Content-Type: application/json; charset=utf-8');
        $productCode = trim((string)($_GET['product_code'] ?? ''));
        $planUnit = trim((string)($_GET['plan_unit'] ?? ''));
        $plantId = gw_mpo_request_manufacturing_plant_id();
        $baseDate = trim((string)($_GET['order_date'] ?? $_GET['base_date'] ?? $_GET['po_date'] ?? ''));
        if ($productCode === '') {
            echo json_encode(array('status' => 'error', 'message' => 'Product code is required'));
        } else {
            $calc = gw_mpo_calc_schedule_delivery($conn, $productCode, $plantId, $baseDate);
            if (!is_array($calc) || ($calc['status'] ?? '') !== 'success') {
                echo json_encode(is_array($calc) ? $calc : array('status' => 'error', 'message' => 'Unable to calculate delivery date'));
            } else {
                echo json_encode(array(
                    'status' => 'success',
                    'base_date' => $calc['base_date'] ?? $baseDate,
                    'delivery_date' => $calc['delivery_date'] ?? null,
                    'batch_size' => 0,
                    'batch_unit' => $planUnit,
                    'number_of_batches' => 0,
                    'max_material_lead_time_days' => (int)($calc['purchase_lead_time_days'] ?? 0),
                    'production_lead_time_days' => (int)($calc['production_lead_time_days'] ?? 0),
                    'total_lead_time_days' => (int)($calc['total_lead_time_days'] ?? 0),
                    'short_materials' => array(),
                ));
            }
        }
    }

    // Gate for the Marketing PO product dropdown: unit formula with materials is required.
    else if ($_GET["type"] == "checkProductFormula") {
        header('Content-Type: application/json; charset=utf-8');
        $productCode = trim((string)($_GET['product_code'] ?? ''));
        if ($productCode === '') {
            echo json_encode(array('status' => 'error', 'message' => 'Product code is required', 'can_add' => false));
        } else {
            echo json_encode(gw_check_product_formula_for_po(
                $conn,
                $productCode,
                gw_mpo_request_manufacturing_plant_id()
            ));
        }
    }
        else if ($_GET["type"] == "update_Wo_SENDfORaNALYSIS") {

        $analysisAction = strtolower(trim((string)($input['action'] ?? '')));
        if ($analysisAction === 'approve_can_plan') {
            medicap_require_helper('can_planned_wo_helpers.php');
            if (function_exists('ensureWoDeductionShortageQueueColumns')) {
                ensureWoDeductionShortageQueueColumns($conn);
            }
            $result = gw_send_can_plan_batches(
                $conn,
                $input['Worders'] ?? array(),
                $_GET['emp_id'] ?? '',
                $_GET['plant_id'] ?? ''
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit;
        }

        if (!function_exists('convertUnit')) {
        function convertUnit($unit) {
            $u = strtolower(trim($unit));

            // GM / MG → KG
            $toKg = ["g", "gm", "gms", "gram", "grams", "mg", "milligram", "milligrams"];
            if (in_array($u, $toKg)) {
                return "KG";
            }

            // ML / LTR → LTR
            $toLtr = ["ml", "milli litre", "millilitre", "l", "lt", "ltr", "litre", "liter"];
            if (in_array($u, $toLtr)) {
                return "LTR";
            }

            // NOS
            $toNos = ["nos", "no", "pcs", "piece", "pieces"];
            if (in_array($u, $toNos)) {
                return "NOS";
            }

            // Meter
            $toMtr = ["m", "meter", "metre", "meters", "metres"];
            if (in_array($u, $toMtr)) {
                return "MTR";
            }

            // Default: return uppercase unit
            return strtoupper($unit);
        }
        }

        $date = date('Y-m-d H:i:s');
        $entry_date = $date;

        $status1 = true;

        $workOrders = $input["Worders"] ?? [];  // Worders array
        $WOrders = $input["order_no"] ?? [];  // order_no array

        // Update split_planning_qty for each order_no.
        // IMPORTANT: never flip CAN_PLAN / CAN_PLAN_MC_QTY_USED splits here. A forecast can hold
        // both can-plan and cannot-plan batches; only the cannot-plan ones go for analysis.
        // Touching the whole order would drag can-plan batches into the WO Analysis tab.
        foreach ($WOrders as $order_no) {
            $orderNoEsc = $conn->real_escape_string($order_no);
            $sql = "UPDATE split_planning_qty  
                    SET status='Send For Requirement Analysis'
                    WHERE order_no='".$orderNoEsc."'
                      AND status NOT IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')";

            if (!$conn->query($sql)) {
                $status1 = false;
                break;
            }
        }
        
        // Update Work_order_materials for each work order
        foreach ($workOrders as $values) {

            // Cannot-plan batches are being sent for requirement analysis, so both
            // Work_order_materials and split_planning_qty must carry the
            // 'Send For Requirement Analysis' status — this is exactly what the WO
            // Analysis screen fetches. Using the raw incoming 'CANNOT_PLAN' status here
            // would overwrite the analysis status set above and hide the WO from WO Analysis.
            // Can-plan batches (legacy path) keep their own status untouched.
            $woStatusIn = $values["status"] ?? '';
            $isCanPlan = in_array($woStatusIn, array('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED'), true);
            $targetStatus = $isCanPlan ? $woStatusIn : 'Send For Requirement Analysis';
            $targetStatusEsc = $conn->real_escape_string($targetStatus);

            // 1️⃣ UPDATE Work_order_materials
            $sql = "UPDATE Work_order_materials  
                    SET status='$targetStatusEsc', 
                        send_for_analysis_by='" . $_GET["emp_id"] . "',
                        send_for_analysis_date='$entry_date'  
                    WHERE id='" . $values["id"] . "'";

            if (!$conn->query($sql)) {
                $status1 = false;
                break;
            }

            // Update split_planning_qty status if work order status is updated
            if (!empty($values["doc_no"])) {
                $sql_sp = "UPDATE split_planning_qty  
                          SET status='$targetStatusEsc'
                          WHERE id='" . $values["doc_no"] . "'";
                $conn->query($sql_sp);
            }

            if($values['status']=='CAN_PLAN'){
                $sql1 = "UPDATE Work_order_materials  
                        SET send_for_planning_by='" . $_GET["emp_id"] . "',
                            send_for_planning_on='$entry_date'  
                        WHERE id='" . $values["id"] . "'";

                $conn->query($sql1);
                
                // Also update split_planning_qty status if doc_no exists
                if (!empty($values["doc_no"])) {
                    $sql_sp1 = "UPDATE split_planning_qty  
                               SET status='CAN_PLAN'
                               WHERE id='" . $values["doc_no"] . "'";
                    $conn->query($sql_sp1);
                }
            }
            
            // 2️⃣ INSERT deductions for THIS workorder
            if (isset($values["deductions"]) && !empty($values["deductions"])) {

                foreach ($values["deductions"] as $values1) {
                    if($values1["deducted_from_MC"]!='0'){
                        $values1["status1"] ='CAN_PLAN_MC_QTY_USED';
                    }else{
                        $values1["status1"]=  $values1["status1"];
                    }
                    $finalUnit = convertUnit($values1["unit"]);
                                    
                    // Calculate plan_qty (include bulk deduction if present)
                    $planQty = round(
                        (float)($values1["deducted_from_MC"] ?? 0) +
                        (float)($values1["deducted_from_RM"] ?? 0) +
                        (float)($values1["deducted_from_Bulk"] ?? 0) +
                        (float)($values1["shortage"] ?? 0),
                        4
                    );
                    
                    // Prepare indexData JSON for bulk and primix shortages
                    $indexData = null;
                    $indexDataArray = [];
                    
                    // Store related_to field (bulk or primix) if present
                    if (!empty($values1["related_to"])) {
                        $indexDataArray["related_to"] = $values1["related_to"];
                    }
                    
                    // Store premixCode for primix materials
                    if (!empty($values1["premixCode"])) {
                        $indexDataArray["premixCode"] = $values1["premixCode"];
                    }
                    
                    // Store bulkCode if present
                    if (!empty($values1["bulkCode"])) {
                        $indexDataArray["bulkCode"] = $values1["bulkCode"];
                    }
                    
                    // For bulk materials, store additional bulk data
                    if (!empty($values1["bulkShortage"]) || 
                        !empty($values1["deducted_from_Bulk"]) ||
                        (!empty($values1["bulkComponents"]) && is_array($values1["bulkComponents"])) ||
                        (!empty($values1["primixShortages"]) && is_array($values1["primixShortages"]))) {
                        
                        if (!empty($values1["bulkShortage"])) {
                            $indexDataArray["bulkShortage"] = floatval($values1["bulkShortage"]);
                        }
                        if (!empty($values1["deducted_from_Bulk"])) {
                            $indexDataArray["deducted_from_Bulk"] = floatval($values1["deducted_from_Bulk"]);
                        }
                        if (!empty($values1["bulkComponents"]) && is_array($values1["bulkComponents"])) {
                            $indexDataArray["bulkComponents"] = $values1["bulkComponents"];
                        }
                        if (!empty($values1["primixShortages"]) && is_array($values1["primixShortages"])) {
                            $indexDataArray["primixShortages"] = $values1["primixShortages"];
                        }
                    }
                    
                    // If we have any index data, encode it as JSON
                    if (!empty($indexDataArray)) {
                        $indexData = json_encode($indexDataArray);
                    }
                    
                     $sql2 = "INSERT INTO WO_deductions
                            (plant_id, workorder_no, mat_type, status, 
                             plan_qty, unit, entry_by, entry_date, material_code,
                             deducted_from_RM, deducted_from_MC, shortage, indexData,work_order_id)
                         VALUES(
                            '" . $_GET["plant_id"] . "',
                            '" . $values1["workorder_no"] . "',
                            '" . $values1["type"] . "',
                            '" . $values1["status1"] . "',
                           '$planQty',
                            '$finalUnit' ,
                            '" . $_GET["emp_id"] . "',
                            '$entry_date',
                            '" . $values1["material_code"] . "',
                            '" . ($values1["deducted_from_RM"] ?? 0) . "',
                            '" . ($values1["deducted_from_MC"] ?? 0) . "',
                            '" . ($values1["shortage"] ?? 0) . "',
                            " . ($indexData !== null ? "'" . $conn->real_escape_string($indexData) . "'" : "NULL") . ",
                             '" . $values["id"] . "'
                         )";
                   

                    if (!$conn->query($sql2)) {
                        $status1 = false;
                        break 2; // stop both loops
                    }

                    // Audit trail: capture the shortage computed at WO generation with the
                    // on-day available stock snapshot (micro-traceability of the requirement).
                    if (function_exists('gw_mrp_audit_log')) {
                        gw_mrp_audit_log($conn, array(
                            'stage' => 'WO_GENERATED',
                            'event_type' => 'Requirement & shortage computed at WO generation',
                            'source_screen' => 'Generate WO',
                            'workorder_no' => $values1["workorder_no"] ?? '',
                            'work_order_id' => $values["id"] ?? null,
                            'order_no' => $values["order_no"] ?? ($values1["order_no"] ?? ''),
                            'product_code' => $values["product_code"] ?? ($values1["product_code"] ?? ''),
                            'material_code' => $values1["material_code"] ?? '',
                            'material_type' => $values1["type"] ?? '',
                            'qty_unit' => $finalUnit ?? '',
                            'required_qty' => $planQty ?? null,
                            'plan_qty' => $planQty ?? null,
                            'deducted_qty' => $values1["deducted_from_RM"] ?? 0,
                            'shortage_qty' => $values1["shortage"] ?? 0,
                            'status_to' => $values1["status1"] ?? '',
                            'snapshot_stock' => true,
                            'plant_id' => $_GET["plant_id"] ?? '',
                            'emp_id' => $_GET["emp_id"] ?? '',
                        ));
                    }
                }
            }
        }

        // 3️⃣ Final Response
        if ($status1) {
            // Drop the WO Analysis result cache so the batches just sent for
            // requirement analysis appear immediately (without waiting for TTL/Refresh).
            if (function_exists('gw_wap_invalidate_cache')) {
                gw_wap_invalidate_cache($_GET["plant_id"] ?? '');
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
              else if ($_GET["type"] == "getShortages") {
            // New dedicated endpoint for Shortages Component
            // Returns material shortages grouped by Material -> Month -> Work Orders
            // Properly considers booked stock from vw_total_available_stock
            
            $output = [];
            
            $sql = "SELECT 
                        a.*, 
                        b.status AS wo_status, 
                       
                        COALESCE(sp.order_no, b.order_no, '') as order_no,
                        COALESCE(sp.oder_qty, b.plan_qty, 0) as plan_qty,
                        a.plan_qty as batch_plan_qty,
                    
                        -- Material fields (check material table first, then others_material for consumables/other materials)
                        COALESCE(
                            (SELECT material_name FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT material_name FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT bulkName FROM bulkMaster WHERE bulkCode=a.material_code LIMIT 1)
                        ) AS material_name,
                        COALESCE(
                            (SELECT material_type FROM bulkMaster WHERE bulkCode=a.material_code LIMIT 1),
                            (SELECT material_type FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT material_type FROM others_material WHERE material_code=a.material_code LIMIT 1)
                        ) AS material_type,
                        COALESCE(
                            (SELECT material_subtype FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT material_subtype FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            ''
                        ) AS material_subtype,
                        COALESCE(
                            (SELECT unit FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT unit FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            ''
                        ) AS Matunit,
                        '' AS MotherCode, -- mother code not used in this project
                        
                    
                        -- Available stock of main code (includes booked stock deduction from vw_total_available_stock)
                        (SELECT available_qty FROM vw_total_available_stock WHERE material_code=a.material_code LIMIT 1) AS available_Stock_qty,
                        
                        -- Booked stock quantity (for reference) - total for material
                        (SELECT booked_qty FROM vw_total_available_stock WHERE material_code=a.material_code LIMIT 1) AS booked_qty,
                        
                        -- Booked stock quantity for same product_code and workorder_no
                        -- This is the sum of deducted_from_RM + deducted_from_MC for records with same material_code, product_code and workorder_no
                        -- This includes ALL records (including current) to get total booked qty for this specific combination
                        COALESCE((
                            SELECT SUM(CAST(COALESCE(wd.deducted_from_RM, 0) AS DECIMAL(15,4)) + 
                                      CAST(COALESCE(wd.deducted_from_MC, 0) AS DECIMAL(15,4)))
                            FROM WO_deductions wd
                            INNER JOIN Work_order_materials wom ON wd.workorder_no = wom.workorder_no
                            LEFT JOIN split_planning_qty sp2 ON wom.doc_no = sp2.id AND wom.order_no = sp2.order_no AND wom.product_code = sp2.product_code
                            WHERE wd.material_code = a.material_code
                              AND wd.status <> 'Cancel'
                              AND wd.indent_status = 'Not Raised'
                              AND COALESCE(sp2.product_code, wom.product_code, '') = COALESCE(sp.product_code, b.product_code, '')
                              AND wd.workorder_no = a.workorder_no
                        ), 0) AS booked_qty_for_wo_product,
                        
                        -- Total stock quantity (before deductions)
                        (SELECT total_stock_qty FROM vw_total_available_stock WHERE material_code=a.material_code LIMIT 1) AS total_stock_qty,
                    
                        -- Mother code not used in this project — always 0
                        0 AS available_MotherCode_Stock_qty,
                    
                        -- Product fields
                        (SELECT mfr_no FROM unitformula WHERE product_code=COALESCE(sp.product_code, b.product_code) ORDER BY id DESC LIMIT 1) AS bulk_code,
                        COALESCE(sp.product_code, b.product_code, '') AS product_code,
                        (SELECT product_name FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1) AS product_name,
                        
                        -- Plan Month from split
                        CASE 
                            WHEN sp.month IS NOT NULL AND sp.year IS NOT NULL THEN CONCAT(sp.month, '-', sp.year)
                            ELSE NULL
                        END AS planMonth,
                        
                        -- Product stage days (for reverse calculation to indent date)
                        COALESCE((SELECT CAST(work_orderDate AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_work_orderDate,
                        COALESCE((SELECT CAST(batchApprovalDate AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_batchApprovalDate,
                        COALESCE((SELECT CAST(DispensingDate AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_DispensingDate,
                        COALESCE((SELECT CAST(productionDate AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_productionDate,
                        COALESCE((SELECT CAST(FillingDate AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_FillingDate,
                        COALESCE((SELECT CAST(inprocessRelease AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_inprocessRelease,
                        COALESCE((SELECT CAST(PackingDate AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_PackingDate,
                        COALESCE((SELECT CAST(fgRelease AS UNSIGNED) FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1), 0) AS product_fgRelease,
                        
                        -- Material days (for reverse calculation to indent date)
                        COALESCE(
                            (SELECT CAST(indend_prepare_date AS UNSIGNED) FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT CAST(indend_prepare_date AS UNSIGNED) FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            0
                        ) AS material_indend_prepare_date,
                        COALESCE(
                            (SELECT CAST(Purchase_prepare_date AS UNSIGNED) FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT CAST(Purchase_prepare_date AS UNSIGNED) FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            0
                        ) AS material_Purchase_prepare_date,
                        COALESCE(
                            (SELECT CAST(Sampling_prepare_date AS UNSIGNED) FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT CAST(Sampling_prepare_date AS UNSIGNED) FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            0
                        ) AS material_Sampling_prepare_date,
                        COALESCE(
                            (SELECT CAST(release_prepare_date AS UNSIGNED) FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT CAST(release_prepare_date AS UNSIGNED) FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            0
                        ) AS material_release_prepare_date,
                        COALESCE(
                            (SELECT CAST(PurchaseDeliveryTime AS UNSIGNED) FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT CAST(PurchaseDeliveryTime AS UNSIGNED) FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            0
                        ) AS material_PurchaseDeliveryTime,
                        COALESCE(
                            (SELECT CAST(ForPayment AS UNSIGNED) FROM material WHERE material_code=a.material_code LIMIT 1),
                            (SELECT CAST(ForPayment AS UNSIGNED) FROM others_material WHERE material_code=a.material_code LIMIT 1),
                            0
                        ) AS material_ForPayment
                    
                    FROM WO_deductions a 
                    LEFT JOIN Work_order_materials b ON b.id = a.work_order_id
                    LEFT JOIN split_planning_qty sp ON b.doc_no = sp.id AND b.order_no = sp.order_no AND b.product_code = sp.product_code
                    WHERE a.indent_status = 'Not Raised'
                      AND a.status <> 'Cancel'
                      AND (a.shortage > 0 OR a.deducted_from_RM > 0 OR a.deducted_from_MC > 0)
                    ORDER BY sp.order_no, b.workorder_no, a.material_code";
            
            $result = $conn->query($sql);

            if (!$result) {
                header('Content-Type: application/json');
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'getShortages query failed: ' . $conn->error,
                    'data' => array(),
                ));
                return;
            }

            $colors = [
                "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", "#D1C4E9",
                "#FFE0B2", "#F8BBD0", "#B2EBF2", "#E6EE9C", "#FFECB3",
                "#CFD8DC", "#F0F4C3", "#DCEDC8", "#F5F5F5", "#E1BEE7",
                "#BBDEFB", "#FFCDD2", "#D7CCC8", "#FFCC80", "#C8E6C9"
            ];
            $colorMap = [];
            $colorIndex = 0;
            
            if ($result->num_rows > 0) {
                $grouped = [];
                
                while ($row = $result->fetch_assoc()) {
                    $po = $row["order_no"];
                    
                    if (!isset($colorMap[$po])) {
                        $colorMap[$po] = $colors[$colorIndex % count($colors)];
                        $colorIndex++;
                    }
                    
                    $row["bg_color"] = $colorMap[$po];
                    
                    $material_code = $row['material_code'];
                    $material_name = $row['material_name'] ?? '';
                    $mat_type = $row['material_type'] ?? '';
                    $client_code = $row['groupcode'] ?? '';
                    $mainGroupName = $row['mainGroupName'] ?? '';
                    $workorder_no = $row['id'];
                    $required_for = $row['workorder_no'];
                    $indent_type = $row['indent_type'] ?? '';
                    $product_code = $row['product_code'] ?? '';
                    $product_name = $row['product_name'] ?? '';
                    $order_no = $row['order_no'] ?? '';
                    $plan_qty = $row['plan_qty'] ?? 0;
                    
                    // Initialize material if not exists
                    if (!isset($grouped[$material_code])) {
                        $grouped[$material_code] = [
                            'material_code' => $material_code,
                            'material_name' => $material_name,
                            'mat_type' => $mat_type,
                            'MotherCode' => $row['MotherCode'] ?? '',
                            'client_code' => $client_code,
                            'client_name' => $mainGroupName,
                            'openPO' => 0,
                            'openIndent' => 0,
                            'Client' => []
                        ];
                    }
                    
                    // Initialize client inside material
                    if (!isset($grouped[$material_code]['Client'][$client_code])) {
                        $grouped[$material_code]['Client'][$client_code] = [
                            'client_code' => $client_code,
                            'client_name' => $mainGroupName,
                            'workorder_no' => $row['workorder_no'],
                            'material_type' => $row['material_type'] ?? '',
                            'material_subtype' => $row['material_subtype'] ?? '',
                            'MotherCode' => $row['MotherCode'] ?? '',
                            'available_Stock_qty' => (float)($row['available_Stock_qty'] ?? 0),
                            'available_MotherCode_Stock_qty1' => (float)($row['available_MotherCode_Stock_qty'] ?? 0),
                            'available_MotherCode_Stock_qty' => (float)($row['available_MotherCode_Stock_qty'] ?? 0),
                            'booked_qty' => (float)($row['booked_qty'] ?? 0),
                            'total_stock_qty' => (float)($row['total_stock_qty'] ?? 0),
                            'Matunit' => $row['Matunit'] ?? '',
                            'plan_qty' => (float)($row['batch_plan_qty'] ?? 0),
                            'unit' => $row['unit'] ?? '',
                            'shortage' => (float)($row['shortage'] ?? 0),
                            'rm_shortage' => (float)($row['shortage'] ?? 0),
                            'deducted_from_MC' => (float)($row['deducted_from_MC'] ?? 0),
                            'indent_type' => $indent_type,
                            'product_code' => $product_code,
                            'product_name' => $product_name,
                            'client_code' => $client_code,
                            'client_name' => $mainGroupName,
                            'required_for' => $required_for,
                            'material_code' => $material_code,
                            'material_name' => $material_name,
                            'workorder_ID' => $workorder_no,
                            'Client_code_Indent' => 0,
                            'Mother_code_Indent' => 0,
                            'department' => 'Store',
                            'wos' => [],
                            'total_Client_shortage' => 0,
                            'total_Mother_shortage' => 0,
                            'total_shortage' => 0,
                            'total_mc' => 0
                        ];
                    }
                    
                    // Calculate shortage excluding booked stock for same product_code and workorder_no
                    $bookedQtyForWOProduct = (float)($row['booked_qty_for_wo_product'] ?? 0);
                    $originalShortage = (float)($row['shortage'] ?? 0);
                    $deductedRM = (float)($row['deducted_from_RM'] ?? 0);
                    $deductedMC = (float)($row['deducted_from_MC'] ?? 0);
                    
                    // Indent-able shortage = the original WO-generation shortage itself.
                    // required = deducted_from_RM + deducted_from_MC + shortage, so `shortage`
                    // is ALREADY the unmet portion after stock was booked. Subtracting
                    // booked_qty_for_wo_product (= deducted_from_RM + deducted_from_MC) again
                    // double-counts and wrongly zeroes the indent qty, so we must not subtract it.
                    $adjustedShortage = max(0, $originalShortage);
                    
                    // Booked qty from other records (excluding current) for reference
                    $bookedQtyFromOtherRecords = max(0, $bookedQtyForWOProduct - $deductedRM - $deductedMC);
                    
                    // Prepare WO data
                    $woData = [
                        'bulk_code' => $row['bulk_code'] ?? '',
                        'wo_deduction_id' => $row['id'],
                        'workorder_no' => $row['workorder_no'],
                        'material_type' => $row['material_type'] ?? '',
                        'material_subtype' => $row['material_subtype'] ?? '',
                        'MotherCode' => $row['MotherCode'] ?? '',
                        'Matunit' => $row['Matunit'] ?? '',
                        'indent_status' => $row['indent_status'] ?? '',
                        'status' => $row['status'] ?? '',
                        'batch_plan_qty' => (float)($row['batch_plan_qty'] ?? 0),
                        'unit' => $row['unit'] ?? '',
                        'director_approval' => $row['director_approval'] ?? 'pending',
                        'bg_color' => $row['bg_color'],
                        'shortage' => $originalShortage,
                        'adjusted_shortage' => $adjustedShortage,
                        'rm_shortage' => $adjustedShortage,
                        'shortage_source' => 'server',
                        'booked_qty_from_other_records' => $bookedQtyFromOtherRecords,  // Booked qty from other records (excluding current)
                        'deducted_from_RM' => $deductedRM,
                        'deducted_from_MC' => $deductedMC,
                        'indent_type' => $indent_type,
                        'product_code' => $product_code,
                        'product_name' => $product_name,
                        'workorder_ID' => $workorder_no,  // Work order number (e.g., "BO001")
                        'id' => $row['id'],  // CRITICAL: WO_deductions table ID - needed for UPDATE
                        'wo_deduction_id' => $row['id'],  // CRITICAL: WO_deductions table ID - needed for UPDATE
                        'client_code' => $client_code,
                        'client_name' => $mainGroupName,
                        'required_for' => $required_for,
                        'material_code' => $material_code,
                        'material_name' => $material_name,
                        'order_no' => $order_no,
                        'plan_qty' => (float)$plan_qty,
                        'planMonth' => $row['planMonth'] ?? null,
                        'Client_code_Indent' => 0,
                        'Mother_code_Indent' => 0,
                        'department' => 'Store',
                        'bulk' => [],
                        'booked_qty' => (float)($row['booked_qty'] ?? 0),
                        'booked_qty_for_wo_product' => $bookedQtyForWOProduct,  // Booked stock for same product_code and workorder_no
                        'total_stock_qty' => (float)($row['total_stock_qty'] ?? 0),
                        // Product stage days (for reverse calculation to indent date)
                        'product_work_orderDate' => (int)($row['product_work_orderDate'] ?? 0),
                        'product_batchApprovalDate' => (int)($row['product_batchApprovalDate'] ?? 0),
                        'product_DispensingDate' => (int)($row['product_DispensingDate'] ?? 0),
                        'product_productionDate' => (int)($row['product_productionDate'] ?? 0),
                        'product_FillingDate' => (int)($row['product_FillingDate'] ?? 0),
                        'product_inprocessRelease' => (int)($row['product_inprocessRelease'] ?? 0),
                        'product_PackingDate' => (int)($row['product_PackingDate'] ?? 0),
                        'product_fgRelease' => (int)($row['product_fgRelease'] ?? 0),
                        // Material days (for reverse calculation to indent date)
                        'material_indend_prepare_date' => (int)($row['material_indend_prepare_date'] ?? 0),
                        'material_Purchase_prepare_date' => (int)($row['material_Purchase_prepare_date'] ?? 0),
                        'material_Sampling_prepare_date' => (int)($row['material_Sampling_prepare_date'] ?? 0),
                        'material_release_prepare_date' => (int)($row['material_release_prepare_date'] ?? 0),
                        'material_PurchaseDeliveryTime' => (int)($row['material_PurchaseDeliveryTime'] ?? 0),
                        'material_ForPayment' => (int)($row['material_ForPayment'] ?? 0)
                    ];
                    
                    // Check if material_type is 'bulk' and fetch data from bulk_material table
                    if (strtolower($mat_type) == 'bulk') {
                        $bulkQuery = "SELECT * FROM bulkMaterials WHERE bulkCode = '" . $conn->real_escape_string($material_code) . "'";
                        $bulkResult = $conn->query($bulkQuery);
                        
                        $bulkData = [];
                        
                        if ($bulkResult && $bulkResult->num_rows > 0) {
                            while ($bulkRow = $bulkResult->fetch_assoc()) {
                                $bulkItem = $bulkRow;
                                $priCode = $bulkItem['material_code'];
                                
                                // Check if material_type in bulk_material is 'premix'
                                if (isset($bulkRow['material_type']) && strtolower($bulkRow['material_type']) == 'premix') {
                                    // Get data from primix_material table
                                    $primixQuery = "SELECT * FROM primixMaterials WHERE premixCode = '" . $conn->real_escape_string($priCode) . "'";
                                    $primixResult = $conn->query($primixQuery);
                                    
                                    $primixData = [];
                                    
                                    if ($primixResult && $primixResult->num_rows > 0) {
                                        while ($primixRow = $primixResult->fetch_assoc()) {
                                            $primixData[] = $primixRow;
                                        }
                                    }
                                    
                                    $bulkItem['primix'] = $primixData;
                                }
                                
                                $bulkData[] = $bulkItem;
                            }
                        }
                        
                        // Store bulk data inside the WO data
                        $woData['bulk'] = $bulkData;
                    }
                    
                    // Apply indent logic
                    if ($indent_type == 'Client Code') {
                        $woData['Client_code_Indent'] = (float)($row['shortage'] ?? 0);
                    }
                    if ($indent_type == 'Mother Code') {
                        $woData['Mother_code_Indent'] = (float)($row['shortage'] ?? 0);
                    }
                    
                    // Update totals per client
                    $grouped[$material_code]['Client'][$client_code]['total_Client_shortage'] += $woData['Client_code_Indent'];
                    $grouped[$material_code]['Client'][$client_code]['total_Mother_shortage'] += $woData['Mother_code_Indent'];
                    $grouped[$material_code]['Client'][$client_code]['total_shortage'] += (float)($row['shortage'] ?? 0);
                    $grouped[$material_code]['Client'][$client_code]['total_mc'] += (float)($row['deducted_from_MC'] ?? 0);
                    
                    // Push WO into the client array
                    $grouped[$material_code]['Client'][$client_code]['wos'][] = $woData;
                }
                
                // Re-index clients as array and add material-level totals
                foreach ($grouped as $mcode => &$material) {
                    $material['Client'] = array_values($material['Client']);
                    $stockFields = gw_get_shortages_stock_fields($conn, $mcode);
                    $material['openPO'] = $stockFields['openPO'];
                    $material['openIndent'] = $stockFields['openIndent'];
                    $material['releasedBookedQty'] = $stockFields['releasedBookedQty'];
                    $material['effective_available_rm'] = $stockFields['effective_available_rm'];
                    
                    // Initialize material totals
                    $material['total_required'] = 0;
                    $material['total_rm_shortage'] = 0;
                    $material['total_mc_shortage'] = 0;
                    
                    // Sum up totals from all clients
                    foreach ($material['Client'] as $client) {
                        $material['total_required'] += $client['total_shortage'] ?? 0;
                        $material['total_rm_shortage'] += $client['total_Client_shortage'] ?? 0;
                        $material['total_mc_shortage'] += $client['total_Mother_shortage'] ?? 0;
                    }
                }
                unset($material);
                
                // Re-index materials as array
                $output = array_values($grouped);
            }
            
            // Output JSON
            header('Content-Type: application/json');
            echo json_encode($output, JSON_PRETTY_PRINT);
        }
        else if ($_GET["type"] == "getShortagesPlannedWO") {
        
        $output = [];
        medicap_require_helper('mrp_wo_schema_helpers.php');
        if (function_exists('ensureWoDeductionShortageQueueColumns')) {
            ensureWoDeductionShortageQueueColumns($conn);
        }

        $sql = "SELECT 
                    a.*, 
                    b.status AS wo_status, 
                   
                    COALESCE(sp.order_no, b.order_no, '') as order_no,
                    COALESCE(sp.oder_qty, b.plan_qty, 0) as plan_qty,
                    a.plan_qty as batch_plan_qty,
                
                    -- Material fields
                    COALESCE((SELECT material_name FROM material WHERE material_code=a.material_code LIMIT 1),
                             (SELECT bulkName FROM bulkMaster WHERE bulkCode=a.material_code LIMIT 1)) AS material_name,
                    COALESCE((SELECT material_type FROM bulkMaster WHERE bulkCode=a.material_code LIMIT 1),
                             (SELECT material_type FROM material WHERE material_code=a.material_code LIMIT 1)) AS material_type,
                    (SELECT material_subtype FROM material WHERE material_code=a.material_code LIMIT 1) AS material_subtype,
                    (SELECT unit FROM material WHERE material_code=a.material_code LIMIT 1) AS Matunit,
                    (SELECT material_code FROM material WHERE material_code=a.material_code LIMIT 1) AS MotherCode,
                    
                
                    -- Available stock of main code
                    (SELECT available_qty FROM vw_total_available_stock WHERE material_code=a.material_code LIMIT 1) AS available_Stock_qty,
                
                    -- Available stock of mother code
                    (SELECT available_qty 
                     FROM vw_total_available_stock 
                     WHERE material_code = (
                         SELECT material_code 
                         FROM material 
                         WHERE material_code = a.material_code
                         LIMIT 1
                     )
                     LIMIT 1
                    ) AS available_MotherCode_Stock_qty,
                
                    -- Product fields
                    (SELECT mfr_no FROM unitformula WHERE product_code=COALESCE(sp.product_code, b.product_code) ORDER BY id DESC LIMIT 1) AS bulk_code,
                    COALESCE(sp.product_code, b.product_code, '') AS product_code,
                    (SELECT product_name FROM product WHERE product_code=COALESCE(sp.product_code, b.product_code) LIMIT 1) AS product_name,
                    
                    -- Plan Month from split
                    CASE 
                        WHEN sp.month IS NOT NULL AND sp.year IS NOT NULL THEN CONCAT(sp.month, '-', sp.year)
                        ELSE NULL
                    END AS planMonth
                
                FROM WO_deductions a 
                LEFT JOIN Work_order_materials b ON b.id = a.work_order_id
                LEFT JOIN split_planning_qty sp ON b.doc_no = sp.id AND b.order_no = sp.order_no AND b.product_code = sp.product_code
                WHERE a.indent_status = 'Not Raised'
                ORDER BY sp.order_no, b.workorder_no, a.material_code";

        $result = $conn->query($sql);

        $colors = [
            "#FFCCCB", // light red
            "#CCFFCC", // light green
            "#CCE5FF", // light blue
            "#FFFACD", // lemon chiffon
            "#D1C4E9", // lavender
            "#FFE0B2", // light orange
            "#F8BBD0", // pink
            "#B2EBF2", // cyan
            "#E6EE9C", // light lime green
            "#FFECB3", // soft yellow-orange
            "#CFD8DC", // blue grey
            "#F0F4C3", // very light olive
            "#DCEDC8", // pastel green
            "#F5F5F5", // light grey
            "#E1BEE7", // soft purple
            "#BBDEFB", // lighter sky blue
            "#FFCDD2", // light rose
            "#D7CCC8", // light taupe
            "#FFCC80", // peach
            "#C8E6C9"  // mint green
        ];
        $colorMap = [];
        $colorIndex = 0; 

        if ($result->num_rows > 0) {
            $grouped = [];

            while ($row = $result->fetch_assoc()) {
                $po = $row["order_no"];

                if (!isset($colorMap[$po])) {
                    $colorMap[$po] = $colors[$colorIndex % count($colors)];
                    $colorIndex++;
                }

                $row["bg_color"] = $colorMap[$po];
                
                $material_code = $row['material_code'];
                $material_name = $row['material_name'] ?? '';
                $mat_type = $row['material_type'] ?? '';
                $client_code = $row['groupcode'] ?? '';
                $mainGroupName = $row['mainGroupName'] ?? '';
                $workorder_no = $row['id'];
                $required_for = $row['workorder_no'];
                $indent_type = $row['indent_type'] ?? '';
                $product_code = $row['product_code'] ?? '';
                $product_name = $row['product_name'] ?? '';
                $order_no = $row['order_no'] ?? '';
                $plan_qty = $row['plan_qty'] ?? 0;

                // Initialize material if not exists
                if (!isset($grouped[$material_code])) {
                    $grouped[$material_code] = [
                        'material_code' => $material_code,
                        'material_name' => $material_name,
                        'mat_type' => $mat_type,
                        'MotherCode' => $row['MotherCode'] ?? '',
                        'client_code' => $client_code,
                        'client_name' => $mainGroupName,
                        'Client' => []
                    ];
                }

                // Initialize client inside material
                if (!isset($grouped[$material_code]['Client'][$client_code])) {
                    $grouped[$material_code]['Client'][$client_code] = [
                        'client_code' => $client_code,
                        'client_name' => $mainGroupName,
                        'workorder_no' => $row['workorder_no'],
                        'material_type' => $row['material_type'] ?? '',
                        'material_subtype' => $row['material_subtype'] ?? '',
                        'MotherCode' => $row['MotherCode'] ?? '',
                        'available_Stock_qty' => (float)($row['available_Stock_qty'] ?? 0),
                        'available_MotherCode_Stock_qty1' => (float)($row['available_MotherCode_Stock_qty'] ?? 0),
                        'available_MotherCode_Stock_qty' => (float)($row['available_MotherCode_Stock_qty'] ?? 0),
                        'Matunit' => $row['Matunit'] ?? '',
                        'plan_qty' => (float)($row['batch_plan_qty'] ?? 0),
                        'unit' => $row['unit'] ?? '',
                        'shortage' => (float)($row['shortage'] ?? 0),
                        'rm_shortage' => (float)($row['shortage'] ?? 0),
                        'deducted_from_MC' => (float)($row['deducted_from_MC'] ?? 0),
                        'indent_type' => $indent_type,
                        'product_code' => $product_code,
                        'product_name' => $product_name,
                        'client_code' => $client_code,
                        'client_name' => $mainGroupName,
                        'required_for' => $required_for,
                        'material_code' => $material_code,
                        'material_name' => $material_name,
                        'workorder_ID' => $workorder_no,
                        'Client_code_Indent' => 0,
                        'Mother_code_Indent' => 0,
                        'department' => 'Store',
                        'wos' => [],
                        'total_Client_shortage' => 0,
                        'total_Mother_shortage' => 0,
                        'total_shortage' => 0,
                        'total_mc' => 0
                    ];
                }

                // Prepare WO data
                $woData = [
                    'bulk_code' => $row['bulk_code'] ?? '',
                    'wo_deduction_id' => $row['id'],
                    'workorder_no' => $row['workorder_no'],
                    'material_type' => $row['material_type'] ?? '',
                    'material_subtype' => $row['material_subtype'] ?? '',
                    'MotherCode' => $row['MotherCode'] ?? '',
                    'Matunit' => $row['Matunit'] ?? '',
                    'indent_status' => $row['indent_status'] ?? '',
                    'status' => $row['status'] ?? '',
                    'batch_plan_qty' => (float)($row['batch_plan_qty'] ?? 0),
                    'unit' => $row['unit'] ?? '',
                    'director_approval' => $row['director_approval'] ?? '',
                    'bg_color' => $row['bg_color'],
                    'shortage' => (float)($row['shortage'] ?? 0),
                    'deducted_from_MC' => (float)($row['deducted_from_MC'] ?? 0),
                    'indent_type' => $indent_type,
                    'product_code' => $product_code,
                    'product_name' => $product_name,
                    'workorder_ID' => $workorder_no,
                    'client_code' => $client_code,
                    'client_name' => $mainGroupName,
                    'required_for' => $required_for,
                    'material_code' => $material_code,
                    'material_name' => $material_name,
                    'order_no' => $order_no,
                    'plan_qty' => (float)$plan_qty,
                    'planMonth' => $row['planMonth'] ?? null,
                    'Client_code_Indent' => 0,
                    'Mother_code_Indent' => 0,
                    'department' => 'Store',
                    'bulk' => []  // Initialize bulk array inside wos
                ];

                // Check if material_type is 'bulk' and fetch data from bulk_material table
                if (strtolower($mat_type) == 'bulk') {
                    $bulkQuery = "SELECT * FROM bulkMaterials WHERE bulkCode = '" . $conn->real_escape_string($material_code) . "'";
                    $bulkResult = $conn->query($bulkQuery);
                    
                    $bulkData = [];
                    
                    if ($bulkResult && $bulkResult->num_rows > 0) {
                        while ($bulkRow = $bulkResult->fetch_assoc()) {
                            $bulkItem = $bulkRow;
                            $priCode = $bulkItem['material_code'];
                            
                            // Check if material_type in bulk_material is 'premix'
                            if (isset($bulkRow['material_type']) && strtolower($bulkRow['material_type']) == 'premix') {
                                // Get data from primix_material table
                                $primixQuery = "SELECT * FROM primixMaterials WHERE premixCode = '" . $conn->real_escape_string($priCode) . "'";
                                $primixResult = $conn->query($primixQuery);
                                
                                $primixData = [];
                                
                                if ($primixResult && $primixResult->num_rows > 0) {
                                    while ($primixRow = $primixResult->fetch_assoc()) {
                                        $primixData[] = $primixRow;
                                    }
                                }
                                
                                $bulkItem['primix'] = $primixData;
                            }
                            
                            $bulkData[] = $bulkItem;
                        }
                    }
                    
                    // Store bulk data inside the WO data
                    $woData['bulk'] = $bulkData;
                }

                // Apply indent logic
                if ($indent_type == 'Client Code') {
                    $woData['Client_code_Indent'] = (float)($row['shortage'] ?? 0);
                }
                if ($indent_type == 'Mother Code') {
                    $woData['Mother_code_Indent'] = (float)($row['shortage'] ?? 0);
                }

                // Update totals per client
                $grouped[$material_code]['Client'][$client_code]['total_Client_shortage'] += $woData['Client_code_Indent'];
                $grouped[$material_code]['Client'][$client_code]['total_Mother_shortage'] += $woData['Mother_code_Indent'];
                $grouped[$material_code]['Client'][$client_code]['total_shortage'] += (float)($row['shortage'] ?? 0);
                $grouped[$material_code]['Client'][$client_code]['total_mc'] += (float)($row['deducted_from_MC'] ?? 0);

                // Push WO into the client array
                $grouped[$material_code]['Client'][$client_code]['wos'][] = $woData;
            }

            // Re-index clients as array
            foreach ($grouped as $mcode => $material) {
                $grouped[$mcode]['Client'] = array_values($material['Client']);
            }

            // Re-index materials as array
            $output = array_values($grouped);
        }

        // Output JSON
        header('Content-Type: application/json');
        echo json_encode($output, JSON_PRETTY_PRINT);
    }
    else if ($_GET["type"] == "uploadClientAgrement") {
         
        $input = $_POST;
        
            	$clcode = $_GET["client_code"];
            	$agree_name = $input["agree_name"];
            	
        if(isset($_FILES["agreFile"]["name"])) {
            $file_ext=strtolower(end(explode('.',$_FILES['agreFile']['name'])));
            move_uploaded_file($_FILES["agreFile"]["tmp_name"], "../../../upload/client/".$agree_name.$clcode.'.'.$file_ext);
            $file_name = $agree_name.$clcode.'.'.$file_ext;
    	}
    	 
         $sql = "INSERT INTO client_agrements(agree_name, client_code,valid_till,doc_type, file_name) 
        VALUES ('".$input["agree_name"]."', '".$_GET["client_code"]."',
        '".$input["valid_till"]."','".$input["doc_type"]."', '$file_name')";
        
        if ($conn->query($sql)) {
        	echo "{\"status\":\"success\"}";
        } else {
           echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
         else if ($_GET["type"] == "getPendingProcessingPOs") {
              
// Backfill splits for orders already sent from Factory Order (Inprocess)
$conn->query("UPDATE split_planning_qty sp
  INNER JOIN order_materials om 
    ON om.order_no = sp.order_no AND om.product_code = sp.product_code
  SET sp.status = 'Pending',
      sp.unit = IF(sp.unit IS NULL OR TRIM(sp.unit)='', om.unit, sp.unit),
      sp.planUnit = IF(sp.planUnit IS NULL OR TRIM(sp.planUnit)='', om.unit, sp.planUnit),
      sp.plant_id = IF(sp.plant_id IS NULL OR TRIM(sp.plant_id)='', om.plant_id, sp.plant_id)
  WHERE om.reqStatus = 'Inprocess'
    AND (sp.status IS NULL OR TRIM(sp.status)='' OR LOWER(TRIM(sp.status))='pending')");

$output = array();

  $sql = "SELECT a.*,
  om.unit AS om_unit,
  om.reqStatus AS order_req_status,
  (SELECT c1.LglNm FROM client c1 WHERE c1.client_code = c.client_code LIMIT 1) AS clientName,
  (SELECT c1.LglNm FROM client c1 WHERE c1.client_code = c.client_code LIMIT 1) AS mainGroupName,
  (SELECT c2.LglNm FROM client c2 WHERE c2.client_code = c.conisgnee LIMIT 1) AS conisgneeName,
  p.category, 
  p.product_name,
  COALESCE(om.deliveryDate, (SELECT deliveryDate FROM order_materials o WHERE o.order_no=a.order_no AND o.product_code=a.product_code ORDER BY o.id DESC LIMIT 1)) AS deliveryDate,
  c.po_date,
  c.file,
  c.id AS po_entry_id,
  (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(middlename, ''), ' ', IFNULL(lastname, ''))
                   FROM employee 
                   WHERE emp_id = a.entry_by limit 1
                  ) AS emp_name
  FROM split_planning_qty a
  LEFT JOIN product p ON a.product_code = p.product_code
  LEFT JOIN po_entry c ON a.order_no = c.order_no
  LEFT JOIN order_materials om ON om.id = (
    SELECT om2.id FROM order_materials om2
    WHERE om2.order_no = a.order_no AND om2.product_code = a.product_code
    ORDER BY CASE WHEN om2.reqStatus = 'Inprocess' THEN 0 ELSE 1 END, om2.id DESC
    LIMIT 1
  )
  WHERE (a.status IS NULL OR TRIM(IFNULL(a.status,''))='' OR LOWER(TRIM(a.status))='pending')
    AND a.id NOT IN (SELECT DISTINCT doc_no FROM Work_order_materials WHERE doc_no IS NOT NULL AND doc_no != '' AND doc_no != '0')
    AND om.reqStatus = 'Inprocess'";
  
  if (!empty($_GET["plant_id"])) {
      $plant_id = $conn->real_escape_string($_GET["plant_id"]);
      $sql .= " AND (a.plant_id = '".$plant_id."' OR a.plant_id IS NULL OR a.plant_id = '')";
  }
  
  $sql .= " ORDER BY a.id ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {

while ($row = $result->fetch_assoc()) {

  $products = array();

      // Use split_planning_qty.id as order_material_id (main table)
      // Get planUnit from split_planning_qty.unit or planUnit column
      $row['order_material_id'] = $row['id'];
      
      // Resolve plan unit (do not skip row — show in processing even if batch formula missing)
      if (!empty($row['planUnit'])) {
          $planUnit = $row['planUnit'];
      } elseif (!empty($row['unit'])) {
          $planUnit = $row['unit'];
      } elseif (!empty($row['om_unit'])) {
          $planUnit = $row['om_unit'];
          $conn->query("UPDATE split_planning_qty SET planUnit='".$conn->real_escape_string($planUnit)."', unit='".$conn->real_escape_string($planUnit)."' WHERE id='".$row['id']."'");
      } else {
          $sql9 = "SELECT unit FROM order_materials 
                   WHERE order_no='".$conn->real_escape_string($row['order_no'])."'  
                   AND product_code='".$conn->real_escape_string($row['product_code'])."'
                   LIMIT 1";
          $result9 = $conn->query($sql9);
          if ($result9 && $result9->num_rows > 0) {
              $row9 = $result9->fetch_assoc();
              $planUnit = $row9['unit'] ?? 'NOS';
              $conn->query("UPDATE split_planning_qty SET planUnit='".$conn->real_escape_string($planUnit)."', unit='".$conn->real_escape_string($planUnit)."' WHERE id='".$row['id']."'");
          } else {
              $planUnit = 'NOS';
          }
      }
      
      $row['planUnit'] = $planUnit;

      $row['batch_formula'] = po_processing_batch_formula_for_product(
          $conn,
          $row['product_code'] ?? '',
          $planUnit
      );
      $row['products'] = [];


  $output[] = $row;
}
}

echo json_encode($output);

    }

    else if ($_GET["type"] == "getProcessingBatchFormula" || $_GET["type"] == "getBatchFormulasByProduct") {
        // Fresh BOM batch sizes for a single product — used by the Processing entry
        // "Generate Batches" popup Refresh so newly added batch-size formulas in the
        // BOM (batch_formula_info) reflect without reloading the whole pending list.
        header('Content-Type: application/json; charset=utf-8');
        $productCode = $_GET['product_code'] ?? '';
        $planUnit = $_GET['planUnit'] ?? ($_GET['plan_unit'] ?? '');
        $fresh = ($_GET['fresh'] ?? '1') === '1' || ($_GET['fresh_from_bom'] ?? '') === '1';
        $batchFormula = po_processing_batch_formula_for_product($conn, $productCode, $planUnit, array(
            'fresh_from_bom' => $fresh,
            'all_units' => $fresh || ($_GET['all_units'] ?? '') === '1',
        ));
        if ($_GET["type"] == "getBatchFormulasByProduct") {
            echo json_encode($batchFormula);
        } else {
            echo json_encode(array(
                'status' => 'success',
                'product_code' => $productCode,
                'planUnit' => $planUnit,
                'batch_formula' => $batchFormula,
            ));
        }
    }
    
    
    else if ($_GET["type"] == "getClientAgrement") {
        $output = Array();
        $sql = "SELECT *  FROM client_agrements WHERE client_code='".$_GET["client_code"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getPendingPOs") {
        header('Content-Type: application/json; charset=utf-8');
        $userNo = $conn->real_escape_string((string)($_GET["user_no"] ?? ''));
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $poId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // Medicap Angular get() sends plant_id (not user_no). Prefer user_no when present.
        $whereScope = "LOWER(TRIM(IFNULL(p.status,'')))='pending'";
        if ($userNo !== '') {
            $whereScope .= " AND p.user_no='".$userNo."'";
        } elseif ($plantId !== '') {
            $whereScope .= " AND (TRIM(IFNULL(p.plant_id,''))='' OR p.plant_id='".$plantId."')";
        }

        if ($poId > 0) {
            $output = null;
            $sql = "SELECT p.*, c.TrdNm FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code
                WHERE ".$whereScope." AND p.id='".$poId."' LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $row["terms"] = json_decode($row["terms"] ?? '[]');
                $products = array();
                $orderNo = $conn->real_escape_string((string)($row["order_no"] ?? ''));
                $sql1 = "SELECT o.order_no, o.product_code,
                    COALESCE(NULLIF(o.order_qty,''), NULLIF(o.planQty,''), NULLIF(o.plan_qty,'')) AS order_qty,
                    o.pack_size, o.packingStyle, o.packingUnit, o.planUnit, o.unit,
                    o.details, o.deliveryDate, o.planMonth, o.planQty, o.remark,
                    p.product_name, p.product_type, p.grade
                    FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code
                    WHERE o.order_no='".$orderNo."' OR o.po_entry_id='".$poId."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["details"] = json_decode($row1["details"] ?? '{}');
                        if (!empty($row1["pack_size"])) {
                            $row1["pack_size"] = json_decode($row1["pack_size"] ?? '{}');
                        } else {
                            $row1["pack_size"] = array(
                                'pack_size' => $row1['packingStyle'] ?? '',
                                'unit' => $row1['packingUnit'] ?? ($row1['planUnit'] ?? ($row1['unit'] ?? '')),
                            );
                        }
                        $products[] = $row1;
                    }
                }
                // Fallback: products JSON on po_entry when order_materials not yet created
                if (count($products) === 0 && !empty($row['products'])) {
                    $fromJson = json_decode($row['products'], true);
                    if (is_array($fromJson)) {
                        $products = $fromJson;
                    }
                }
                $row["products"] = $products;
                $row['plan_type'] = trim((string)($row['client_type'] ?? ''));
                if (strcasecmp($row['plan_type'], 'Forecast') === 0) {
                    $row['TrdNm'] = 'NA';
                    $row['po_no'] = 'NA';
                }
                $output = $row;
            }
            echo json_encode($output);
        } else {
            $output = array();
            $sql = "SELECT p.id, p.order_no, p.po_no, p.po_date, p.valid_till, p.status, p.po_type, p.file,
                p.entry_date, p.client_type, p.client_code, c.TrdNm
                FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code
                WHERE ".$whereScope." ORDER BY p.id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['plan_type'] = trim((string)($row['client_type'] ?? ''));
                    if (strcasecmp($row['plan_type'], 'Forecast') === 0) {
                        $row['TrdNm'] = 'NA';
                        $row['po_no'] = 'NA';
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
    }

    else if ($_GET["type"] == "getShortagesIndentLog") {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
        $doBackfill = !isset($_GET['backfill']) || $_GET['backfill'] !== '0';
        $output = gw_get_mrp_shortages_indent_log($conn, $limit, $doBackfill);
        header('Content-Type: application/json');
        echo json_encode($output);
    }

    else if ($_GET["type"] == "proceedWoAnalysisToShortagePlanning") {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $payload = is_array($input) ? $input : json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = array();
            }
            if (empty($payload['plant_id']) && !empty($_GET['plant_id'])) {
                $payload['plant_id'] = $_GET['plant_id'];
            }
            $result = gw_record_wo_analysis_proceed($conn, $payload, array(
                'emp_id' => $_GET['emp_id'] ?? '',
                'department' => $_GET['department'] ?? '',
            ));
            // Mirror each proceeded material into the unified audit trail.
            if (function_exists('gw_mrp_audit_log') && !empty($payload['materials']) && is_array($payload['materials'])) {
                foreach ($payload['materials'] as $mat) {
                    if (!is_array($mat)) { continue; }
                    gw_mrp_audit_log($conn, array(
                        'stage' => 'WO_ANALYSIS_PROCEED',
                        'event_type' => 'Proceeded to Shortage Planning',
                        'source_screen' => 'WO Analysis',
                        'material_code' => $mat['material_code'] ?? '',
                        'material_name' => $mat['material_name'] ?? '',
                        'material_type' => $mat['mat_type'] ?? ($mat['material_type'] ?? ''),
                        'qty_unit' => $mat['qty_unit'] ?? ($mat['unit'] ?? ''),
                        'required_qty' => $mat['required_qty'] ?? null,
                        'available_qty_snapshot' => $mat['stock_in_hand_qty'] ?? ($mat['available_stock_qty'] ?? null),
                        'balance_qty' => $mat['balance_qty'] ?? ($mat['rm_remaining'] ?? null),
                        'shortage_qty' => $mat['shortage_qty'] ?? ($mat['clmc_shortage'] ?? null),
                        'order_no' => $mat['forecast_nos'] ?? '',
                        'workorder_no' => $mat['workorder_nos'] ?? '',
                        'status_to' => 'Shortage Planning',
                        'plant_id' => $payload['plant_id'] ?? ($_GET['plant_id'] ?? ''),
                        'emp_id' => $_GET['emp_id'] ?? '',
                        'department' => $_GET['department'] ?? '',
                        'remark' => 'Consolidated batch proceeded for indent raising',
                        'detail' => array('batch_ref' => $result['batch_ref'] ?? ''),
                    ));
                }
            }
            echo json_encode($result);
        } catch (Throwable $e) {
            echo json_encode(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'rows_marked' => 0,
                'materials_logged' => 0,
            ));
        }
    }

    else if ($_GET["type"] == "getWoAnalysisProceedLog") {
        header('Content-Type: application/json; charset=utf-8');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
        $plantId = $_GET['plant_id'] ?? '';
        $output = gw_get_wo_analysis_proceed_log($conn, $limit, $plantId);
        echo json_encode(array(
            'status' => 'success',
            'rows' => $output,
            'total' => count($output),
        ));
    }

    // ---- Unified MRP audit trail (used by the History/Log viewer in every tab) ----
    else if ($_GET["type"] == "getMrpAuditLog") {
        header('Content-Type: application/json; charset=utf-8');
        $filters = array(
            'stage' => $_GET['stage'] ?? '',
            'workorder_no' => $_GET['workorder_no'] ?? '',
            'material_code' => $_GET['material_code'] ?? '',
            'order_no' => $_GET['order_no'] ?? '',
            'product_code' => $_GET['product_code'] ?? '',
            'indent_id' => $_GET['indent_id'] ?? '',
            'po_no' => $_GET['po_no'] ?? '',
            'source_screen' => $_GET['source_screen'] ?? '',
            'plant_id' => $_GET['plant_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
            'limit' => $_GET['limit'] ?? 500,
        );
        $rows = gw_get_mrp_audit_log($conn, $filters);
        echo json_encode(array(
            'status' => 'success',
            'rows' => $rows,
            'total' => count($rows),
        ));
    }

    // ---- Change Forecast Plan ----
    else if ($_GET["type"] == "getChangeForecastPlans") {
        header('Content-Type: application/json; charset=utf-8');
        $plantId = $_GET['plant_id'] ?? '';
        $filters = array(
            'order_no' => $_GET['order_no'] ?? '',
            'product_code' => $_GET['product_code'] ?? '',
            'search' => $_GET['search'] ?? '',
            'page' => $_GET['page'] ?? 1,
            'pageSize' => $_GET['pageSize'] ?? 25,
        );
        $result = gw_cfp_get_processed_plans($conn, $plantId, $filters);
        echo json_encode(array(
            'status' => 'success',
            'plans' => $result['plans'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => $result['totalPages'],
            'changeable_on_page' => $result['changeable_on_page'],
        ));
    }

    else if ($_GET["type"] == "getClubbableForecast") {
        header('Content-Type: application/json; charset=utf-8');
        $plantId = $_GET['plant_id'] ?? '';
        $locks = gw_cfp_compute_plan_locks(
            $conn,
            $_GET['order_no'] ?? '',
            $_GET['product_code'] ?? '',
            $_GET['month'] ?? '',
            $_GET['year'] ?? '',
            $plantId
        );
        $club = gw_cfp_get_clubbable_forecast(
            $conn,
            $_GET['order_no'] ?? '',
            $_GET['product_code'] ?? '',
            $_GET['month'] ?? '',
            $_GET['year'] ?? '',
            $plantId,
            (int)$locks['split_id']
        );
        echo json_encode(array('status' => 'success', 'locks' => $locks, 'clubbable' => $club['rows'], 'clubbable_total' => $club['total']));
    }

    else if ($_GET["type"] == "changeForecastPlan") {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $payload = is_array($input) ? $input : json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = array();
            }
            if (empty($payload['plant_id']) && !empty($_GET['plant_id'])) {
                $payload['plant_id'] = $_GET['plant_id'];
            }
            $result = gw_cfp_change_plan($conn, $payload, array(
                'emp_id' => $_GET['emp_id'] ?? '',
                'department' => $_GET['department'] ?? '',
            ));
            echo json_encode($result);
        } catch (Throwable $e) {
            echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    else if ($_GET["type"] == "getForecastChangeLog") {
        header('Content-Type: application/json; charset=utf-8');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
        $rows = gw_cfp_get_change_log($conn, $limit, $_GET['plant_id'] ?? '');
        echo json_encode(array('status' => 'success', 'rows' => $rows, 'total' => count($rows)));
    }

    // Generic writer so UI-only stage transitions can also be recorded for traceability.
    else if ($_GET["type"] == "logMrpAuditEvent") {
        header('Content-Type: application/json; charset=utf-8');
        $payload = is_array($input) ? $input : json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = array();
        }
        $events = array();
        if (!empty($payload['events']) && is_array($payload['events'])) {
            $events = $payload['events'];
        } else if (!empty($payload['stage'])) {
            $events = array($payload);
        }
        $base = array(
            'plant_id' => $payload['plant_id'] ?? ($_GET['plant_id'] ?? ''),
            'emp_id' => $payload['emp_id'] ?? ($_GET['emp_id'] ?? ''),
            'department' => $payload['department'] ?? ($_GET['department'] ?? ''),
        );
        $saved = gw_mrp_audit_log_bulk($conn, $events, $base);
        echo json_encode(array('status' => 'success', 'saved' => $saved));
    }

    else if ($_GET["type"] == "backfillPlanningMrpIndentsHistory") {
        @set_time_limit(180);
        @ini_set('memory_limit', '512M');
        header('Content-Type: application/json');
        try {
            $batch = isset($_GET['batch']) ? (int)$_GET['batch'] : 200;
            $max = isset($_GET['max']) ? (int)$_GET['max'] : 2000;
            $logInserted = gw_backfill_mrp_shortages_indent_log($conn, $batch, $max);
            $confirmationSynced = gw_backfill_mrp_indents_confirmation($conn, $batch, $max, false);
            echo json_encode([
                'status' => 'success',
                'shortages_log_inserted' => $logInserted,
                'confirmation_synced' => $confirmationSynced,
                'message' => 'Historical planning indents imported.',
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'shortages_log_inserted' => 0,
                'confirmation_synced' => 0,
            ]);
        }
    }

    else if ($_GET["type"] == "getPlanningIndentsConfirmation") {
        @set_time_limit(120);
        $output = gw_get_mrp_indents_confirmation_list($conn, [
            'status' => $_GET['status'] ?? 'ALL',
            'search' => $_GET['search'] ?? '',
            'sync_missing' => true,
        ]);
        header('Content-Type: application/json');
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getPlanningIndentsConfirmationPendingSummary") {
        $output = gw_get_mrp_indents_confirmation_pending_summary($conn);
        header('Content-Type: application/json');
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getPlanningIndentsConfirmationLog") {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 300;
        $output = gw_get_mrp_indents_confirmation_log($conn, $limit);
        header('Content-Type: application/json');
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getPlanningIndentStatusLog") {
        header('Content-Type: application/json; charset=utf-8');
        $output = gw_get_planning_indent_status_log($conn, [
            'search' => $_GET['search'] ?? '',
            'plant_id' => $_GET['plant_id'] ?? '',
            'page' => $_GET['page'] ?? 0,
            'pageSize' => $_GET['pageSize'] ?? 100,
        ]);
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getMrpDashboardStats") {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(gw_get_mrp_dashboard_stats($conn, [
            'plant_id' => $_GET['plant_id'] ?? '',
        ]));
    }

    else if ($_GET["type"] == "applyPlanningIndentConfirmationAction") {
        $input = json_decode(file_get_contents('php://input'), true);
        $confirmationId = (int)($input['confirmation_id'] ?? 0);
        $action = $input['action'] ?? '';
        $remark = $input['remark'] ?? '';
        $raisedByName = '';
        if (!empty($_GET['emp_id'])) {
            $empRes = $conn->query("SELECT COALESCE(emp_name, firstname, '') AS n FROM employee WHERE emp_id = '" . mysqli_real_escape_string($conn, $_GET['emp_id']) . "' LIMIT 1");
            if ($empRes && $empRes->num_rows > 0) {
                $raisedByName = $empRes->fetch_assoc()['n'] ?? '';
            }
        }
        if (empty($raisedByName) && !empty($input['shortages_log']['raised_by_name'])) {
            $raisedByName = $input['shortages_log']['raised_by_name'];
        }
        $result = gw_apply_mrp_indent_confirmation_action($conn, $confirmationId, $action, $remark, [
            'emp_id' => $_GET['emp_id'] ?? '',
            'department' => $_GET['department'] ?? '',
            'raised_by_name' => $raisedByName,
        ]);
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
        else if ($_GET["type"] == "getShortagesPlannedWO_wise") {
        
        @set_time_limit(180);
        $wowiseStartedAt = microtime(true);
        header('Content-Type: application/json; charset=utf-8');
        $output = array();

        // Self-provision the proceed marker column so the scope filter below is valid.
        if (function_exists('gw_ensure_wo_analysis_proceed_column')) {
            gw_ensure_wo_analysis_proceed_column($conn);
        }

        $plantFilter = '';
        $plantIdKey = $_GET['plant_id'] ?? '';
        if (!empty($_GET['plant_id'])) {
            $plantFilter = " AND a.plant_id = '" . $conn->real_escape_string($_GET['plant_id']) . "'";
        }

        $page = max(0, (int)($_GET['page'] ?? 0));
        $pageSize = min(2000, max(10, (int)($_GET['pageSize'] ?? 10)));
        $forceRefresh = ($_GET['refresh'] ?? '') === '1';
        $wowiseScope = gw_wowise_normalize_scope($_GET['scope'] ?? 'current');
        $scopeFilter = gw_wowise_scope_sql($wowiseScope);
        $cachePath = gw_wowise_cache_path($plantIdKey, $wowiseScope);
        $flatOutput = array();
        $cacheHit = false;

        if (!$forceRefresh && is_readable($cachePath) && (time() - filemtime($cachePath)) < 600) {
            $cached = json_decode((string)file_get_contents($cachePath), true);
            if (is_array($cached)) {
                $flatOutput = $cached;
                $cacheHit = true;
            }
        }

        if (!$cacheHit) {
        
        // Join Work_order_materials by primary key (work_order_id), not workorder_no — avoids row explosion.
        $sql = "SELECT 
                    a.*, 
                    b.status AS wo_status,
                    b.groupcode,
                    b.mainGroupName,
                    COALESCE(sp.order_no, b.order_no, '') AS forecast_no,
                    COALESCE(sp.order_no, b.order_no, '') AS order_no,
                    COALESCE(sp.oder_qty, b.plan_qty, 0) AS plan_qty,
                    a.plan_qty AS batch_plan_qty,
                    COALESCE(sp.product_code, b.product_code, '') AS product_code,
                    COALESCE(sp.status, b.status, '') AS plan_status
                FROM WO_deductions a 
                INNER JOIN Work_order_materials b ON b.id = a.work_order_id
                LEFT JOIN split_planning_qty sp ON b.doc_no = sp.id
                WHERE a.status <> 'Cancel'
                  $scopeFilter
                  $plantFilter
                ORDER BY COALESCE(b.send_for_analysis_date, a.id) DESC, sp.order_no, b.workorder_no, a.material_code";

        $result = $conn->query($sql);
        if (!$result) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'WO_deductions query failed: ' . $conn->error,
                'data' => array(),
            ));
            return;
        }

        $rawRows = array();
        $materialCodes = array();
        $productCodes = array();
        $motherCodes = array();
        while ($row = $result->fetch_assoc()) {
            $rawRows[] = $row;
            if (!empty($row['material_code'])) {
                $materialCodes[] = $row['material_code'];
            }
            if (!empty($row['product_code'])) {
                $productCodes[] = $row['product_code'];
            }
        }
        $result->free();

        $materialDisplayMap = gw_prefetch_material_display_map($conn, $materialCodes);
        foreach ($materialDisplayMap as $meta) {
            if (!empty($meta['mother_code'])) {
                $motherCodes[] = $meta['mother_code'];
            }
        }
        $productMeta = gw_prefetch_product_meta_maps($conn, $productCodes);
        $motherStockMap = gw_prefetch_vw_stock_available_map($conn, $motherCodes);
        $factoryOrderMap = gw_wowise_factory_order_map($conn);
        $materialStockExtras = gw_batch_shortages_stock_fields($conn, $materialCodes, true);

        $colors = array("#FFCCCB","#CCFFCC","#CCE5FF","#FFFACD","#D1C4E9","#FFE0B2","#F8BBD0","#B2EBF2","#E6EE9C","#FFECB3","#CFD8DC","#F0F4C3","#DCEDC8","#F5F5F5","#E1BEE7","#BBDEFB","#FFCDD2","#D7CCC8","#FFCC80","#C8E6C9");
        $colorIndex = 0;
        $colorMap = array();
        $groupedOrders = array();
        $materialRmStock = array();
        $flatOutput = array();
        $stockEmpty = array(
            'available_Stock_qty' => 0,
            'openPO' => 0,
            'openIndent' => 0,
            'releasedBookedQty' => 0,
            'effective_available_rm' => 0,
        );

        foreach ($rawRows as $row) {
                $material_code = $row['material_code'] ?? '';
                $matMeta = $materialDisplayMap[$material_code] ?? array();
                $productCode = $row['product_code'] ?? '';
                $motherCode = $matMeta['mother_code'] ?? '';
                $row['material_name'] = $matMeta['material_name'] ?? '';
                $row['material_type'] = $matMeta['material_type'] ?? '';
                $row['material_subtype'] = $matMeta['material_subtype'] ?? '';
                $row['Matunit'] = $matMeta['unit'] ?? ($row['unit'] ?? '');
                $row['MotherCode'] = $motherCode;
                $row['product_name'] = $productMeta['names'][$productCode] ?? '';
                $row['bulk_code'] = $productMeta['bulk'][$productCode] ?? '';
                $row['available_mother_stock_qty'] = $motherCode !== '' ? ($motherStockMap[$motherCode] ?? 0) : 0;

                $forecast_no = $row['forecast_no'] ?? $row['order_no'];
                $order_no = $forecast_no;
                $foKey = ($row['order_no'] ?? '') . '|' . $productCode;
                $factory_order_no = $factoryOrderMap[$foKey] ?? '';
                $wo_no = $row['workorder_no'];
                
                $po = $material_code;

                if (!isset($colorMap[$po])) {
                    $colorMap[$po] = $colors[$colorIndex % count($colors)];
                    $colorIndex++;
                }

                $row["bg_color"] = $colorMap[$po];
                
                $bg_color = $row["bg_color"];

                if (!isset($groupedOrders[$order_no])) {
                    $groupedOrders[$order_no] = array(
                        'order_no' => $forecast_no,
                        'forecast_no' => $forecast_no,
                        'factory_order_no' => $factory_order_no,
                        'client_code' => $row['groupcode'],
                        'client_name' => $row['mainGroupName'],
                        'product_code' => $productCode,
                        'product_name' => $row['product_name'],
                        'WorkOrders' => array()
                    );
                } elseif (!empty($factory_order_no) && empty($groupedOrders[$order_no]['factory_order_no'])) {
                    $groupedOrders[$order_no]['factory_order_no'] = $factory_order_no;
                }

                if (!isset($groupedOrders[$order_no]['WorkOrders'][$wo_no])) {
                    $groupedOrders[$order_no]['WorkOrders'][$wo_no] = array(
                        'workorder_no' => $wo_no,
                        'forecast_no' => $forecast_no,
                        'factory_order_no' => $factory_order_no,
                        'bulk_code' => $row['bulk_code'],
                        'plan_qty' => $row['batch_plan_qty'],
                        'group_code' => $row['groupcode'],
                        'group_name' => $row['mainGroupName'],
                        'Materials' => array(),
                        'cumulative_mcStock' => (float)($row['available_mother_stock_qty'] ?? 0)
                    );
                } elseif (!empty($factory_order_no) && empty($groupedOrders[$order_no]['WorkOrders'][$wo_no]['factory_order_no'])) {
                    $groupedOrders[$order_no]['WorkOrders'][$wo_no]['factory_order_no'] = $factory_order_no;
                }

                $deductedRM = (float)($row['deducted_from_RM'] ?? 0);
                $deductedMC = (float)($row['deducted_from_MC'] ?? 0);
                $shortage = (float)($row['shortage'] ?? 0);
                $planQty = (float)($row['batch_plan_qty'] ?? 0);
                if ($planQty <= 0) {
                    $planQty = $deductedRM + $deductedMC + $shortage;
                }

                $stockExtras = $materialStockExtras[$material_code] ?? $stockEmpty;
                
                $availableRM = isset($materialRmStock[$material_code])
                    ? $materialRmStock[$material_code]
                    : (float)($stockExtras['effective_available_rm'] ?? 0);
                
                $used_from_RM = min($availableRM, max(0, $deductedRM));
                
                $rm_remaining = max(0, $availableRM - $used_from_RM);
                
                $rm_shortage = max(0, $planQty - $used_from_RM);
                
                $materialRmStock[$material_code] = $rm_remaining;

                // Mother code is not used in this project — MC contributes nothing.
                $used_from_MC = 0;
                $mc_remaining = 0;
                $mc_shortage = 0;
                $rm_shortage = max(0, $planQty - $used_from_RM);
                
                $clmc_inhand = (float)($stockExtras['available_Stock_qty'] ?? 0);
                $clmc_remaining = $rm_remaining;
                $clmc_shortage = $rm_shortage;

                $initialAvailableRM = isset($materialRmStock[$material_code])
                    ? $materialRmStock[$material_code] + $used_from_RM
                    : (float)($stockExtras['effective_available_rm'] ?? 0);
                
                $matLine = array(
                    'material_code' => $material_code,
                    'material_name' => $row['material_name'] ?? '',
                    'material_type' => $row['material_type'] ?? '',
                    'material_subtype' => $row['material_subtype'] ?? '',
                    'mat_type' => $row['mat_type'] ?? '',
                    'available_stock_qty' => (float)($stockExtras['available_Stock_qty'] ?? 0),
                    'open_po' => (float)($stockExtras['openPO'] ?? 0),
                    'open_indent' => (float)($stockExtras['openIndent'] ?? 0),
                    'released_booked_qty' => (float)($stockExtras['releasedBookedQty'] ?? 0),
                    'effective_available_stock_qty' => $initialAvailableRM,
                    'available_mother_stock_qty' => (float)($row['available_mother_stock_qty'] ?? 0),
                    'MotherCode' => $row['MotherCode'] ?? '',
                    'Matunit' => $row['Matunit'] ?? '',
                    'unit' => $row['Matunit'] ?? '',
                    'required_qty' => $planQty,
                    'rm_remaining' => $rm_remaining,
                    'rm_shortage' => $rm_shortage,
                    'mc_remaining' => $mc_remaining,
                    'mc_shortage' => $mc_shortage,
                    'used_from_RM' => $used_from_RM,
                    'used_from_MC' => $used_from_MC,
                    'deducted_from_RM' => $deductedRM,
                    'deducted_from_MC' => $deductedMC,
                    'shortage' => $shortage,
                    'clmc_inhand' => $clmc_inhand,
                    'clmc_remaining' => $clmc_remaining,
                    'clmc_shortage' => $clmc_shortage,
                    'bg_color' => $bg_color,
                    'indent_type' => $row['indent_type'] ?? '',
                    'required_for' => $wo_no,
                    'Client_code_Indent' => 0,
                    'Mother_code_Indent' => 0,
                    'forecast_no' => $forecast_no,
                    'order_no' => $forecast_no,
                    'factory_order_no' => $factory_order_no,
                    'client_code' => $row['groupcode'],
                    'client_name' => $row['mainGroupName'],
                    'product_name' => $row['product_name'],
                    'product_code' => $productCode,
                    'workorder_no' => $wo_no,
                    'bulk_code' => $row['bulk_code'],
                    'plan_status' => $row['plan_status'] ?? ($row['wo_status'] ?? ''),
                    'indent_status' => $row['indent_status'] ?? '',
                    'record_scope' => $wowiseScope,
                    'wo_analysis_proceeded_at' => $row['wo_analysis_proceeded_at'] ?? null,
                    'plan_stage_label' => gw_wowise_plan_stage_label(
                        $row['indent_status'] ?? '',
                        $row['plan_status'] ?? ($row['wo_status'] ?? ''),
                        $row['wo_analysis_proceeded_at'] ?? null
                    ),
                );
                $groupedOrders[$order_no]['WorkOrders'][$wo_no]['Materials'][] = $matLine;
                $flatOutput[] = $matLine;
        }

            @file_put_contents($cachePath, json_encode($flatOutput));
        }

        $total = count($flatOutput);
        $summary = gw_wowise_summary_from_flat_output($flatOutput);

        if (($_GET['all'] ?? '') === '1') {
            header('X-WoAnalysis-Ms: ' . (int)((microtime(true) - $wowiseStartedAt) * 1000));
            header('X-WoAnalysis-Rows: ' . $total);
            header('X-WoAnalysis-Cache: ' . ($cacheHit ? 'hit' : 'miss'));
            echo json_encode(array(
                'total' => $total,
                'scope' => $wowiseScope,
                'summary' => $summary,
                'rows' => $flatOutput,
            ));
            return;
        }

        $rows = array_slice($flatOutput, $page * $pageSize, $pageSize);
        header('X-WoAnalysis-Ms: ' . (int)((microtime(true) - $wowiseStartedAt) * 1000));
        header('X-WoAnalysis-Rows: ' . $total);
        header('X-WoAnalysis-Cache: ' . ($cacheHit ? 'hit' : 'miss'));
        header('X-WoAnalysis-Scope: ' . $wowiseScope);
        echo json_encode(array(
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'scope' => $wowiseScope,
            'summary' => $summary,
            'rows' => $rows,
        ));
    }
    else if ($_GET["type"] == "getPendingPOsReview") {
        $output = Array();
        $sql = "SELECT p.*,c.TrdNm FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status='pending' ORDER BY p.id DESC";
        
        
        $result = $conn->query($sql);
 if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row["terms"] = json_decode($row["terms"]);
                $output1 = array();
                 $sql1 = "SELECT o.order_no,o.product_code,o.order_qty,o.pack_size,o.details, p.product_name, 
                 p.product_type, p.grade FROM order_materials o LEFT JOIN product p ON
                 o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                          $row1["details"] = json_decode($row1["details"]);
                          $row1["pack_size"] = json_decode($row1["pack_size"]);
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
       else if ($_GET["type"] == "Get_Processed_Generated_wo") {

header('Content-Type: application/json; charset=utf-8');
medicap_require_helper('mrp_wo_schema_helpers.php');
if (function_exists('ensureWoVerificationColumns')) {
    ensureWoVerificationColumns($conn);
}
medicap_require_helper('can_planned_wo_helpers.php');

if (!function_exists('gw_resolve_material_name')) {
    function gw_resolve_material_name($conn, $material_code) {
        if (empty($material_code)) {
            return '';
        }
        $code = $conn->real_escape_string($material_code);
        $sql = "SELECT COALESCE(
            (SELECT material_name FROM material WHERE material_code = '$code' LIMIT 1),
            (SELECT material_name FROM others_material WHERE material_code = '$code' LIMIT 1)
        ) AS material_name";
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $name = $result->fetch_assoc()['material_name'];
            return !empty($name) ? $name : '';
        }
        return '';
    }
}

if (!function_exists('gw_get_material_availability')) {
    function gw_get_material_availability($conn, $material_code) {
        $empty = [
            'approved_stock' => 0,
            'undertest_or_received_stock' => 0,
            'booked_stock' => 0,
            'open_po' => 0,
            'available_qty' => 0
        ];
        if (empty($material_code)) {
            return $empty;
        }

        $code = $conn->real_escape_string($material_code);

        $approvedGross = 0;
        $result = $conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(15,4))), 0) AS qty
                                FROM stock_book
                                WHERE material_code = '$code' AND status = 'Approved'");
        if ($result && $row = $result->fetch_assoc()) {
            $approvedGross = floatval($row['qty']);
        }

        $dispensing = 0;
        $result = $conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(15,4))), 0) AS qty
                                FROM material_issue
                                WHERE material_code = '$code'");
        if ($result && $row = $result->fetch_assoc()) {
            $dispensing = floatval($row['qty']);
        }

        // Medicap stock_book may not have undertest_qty (Zuma-only); treat as 0 when absent.
        static $hasUndertestQty = null;
        if ($hasUndertestQty === null) {
            $colCheck = @$conn->query("SHOW COLUMNS FROM stock_book LIKE 'undertest_qty'");
            $hasUndertestQty = ($colCheck && $colCheck->num_rows > 0);
        }
        $undertestOnApproved = 0;
        if ($hasUndertestQty) {
            $result = $conn->query("SELECT IFNULL(SUM(CAST(undertest_qty AS DECIMAL(15,4))), 0) AS qty
                                    FROM stock_book
                                    WHERE material_code = '$code' AND status = 'Approved'");
            if ($result && $row = $result->fetch_assoc()) {
                $undertestOnApproved = floatval($row['qty']);
            }
        }

        $approved_stock = max(0, $approvedGross - $dispensing - $undertestOnApproved);

        $undertestStatus = 0;
        $result = $conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(15,4))), 0) AS qty
                                FROM stock_book
                                WHERE material_code = '$code' AND status = 'Under Test'");
        if ($result && $row = $result->fetch_assoc()) {
            $undertestStatus = floatval($row['qty']);
        }

        $receivedPending = 0;
        $result = $conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(15,4))), 0) AS qty
                                FROM stock_book
                                WHERE material_code = '$code'
                                  AND status NOT IN ('Approved', 'Rejected', 'Expired', 'Under Test')");
        if ($result && $row = $result->fetch_assoc()) {
            $receivedPending = floatval($row['qty']);
        }

        $undertest_or_received_stock = $undertestStatus + $receivedPending;

        $booked_stock = 0;
        $result = $conn->query("SELECT IFNULL(CAST(booked_qty AS DECIMAL(15,4)), 0) AS booked_qty
                                FROM vw_total_available_stock
                                WHERE material_code = '$code'
                                LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $booked_stock = floatval($result->fetch_assoc()['booked_qty']);
        } else {
            $result = $conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(15,4))), 0) AS booked_qty
                                    FROM mrp_bookedStock
                                    WHERE material_code = '$code'");
            if ($result && $row = $result->fetch_assoc()) {
                $booked_stock = floatval($row['booked_qty']);
            }
        }

        $open_po = 0;
        $result = $conn->query("SELECT IFNULL(SUM(CAST(pm.qty AS DECIMAL(15,4))), 0) AS open_po
                                FROM po_material pm
                                LEFT JOIN purchaseorder p ON pm.po_no = p.po_no
                                WHERE pm.material_code = '$code'
                                  AND (pm.isreceive = 'No' OR pm.isreceive IS NULL OR pm.isreceive = '')
                                  AND (p.is_security_receive = 'No' OR p.is_security_receive IS NULL OR p.is_security_receive = '')");
        if ($result && $row = $result->fetch_assoc()) {
            $open_po = floatval($row['open_po']);
        }

        $available_qty = max(0, $approved_stock + $undertest_or_received_stock - $booked_stock + $open_po);

        return [
            'approved_stock' => round($approved_stock, 4),
            'undertest_or_received_stock' => round($undertest_or_received_stock, 4),
            'booked_stock' => round($booked_stock, 4),
            'open_po' => round($open_po, 4),
            'available_qty' => round($available_qty, 4)
        ];
    }
}

$output = array();

$gwSplitStatuses = "'Work Order Processed', 'Work Order Preparation Approved', 'CANNOT_PLAN', 'CAN_PLAN', 'CAN_PLAN_MC_QTY_USED', 'Pending'";
$gwWoStatuses = "'Work Order Processed', 'Work Order Preparation Approved', 'CANNOT_PLAN', 'CAN_PLAN', 'CAN_PLAN_MC_QTY_USED', 'Pending'";

/* ============================================================
   = FETCH WORK ORDERS
   ============================================================ */
$sql = "SELECT a.*, COALESCE(b.product_code, a.product_code) AS product_code, b.work_order_planned_qty,  CONCAT(b.month, '-', b.year) as planMonth, a.entryOn as Wo_Generated_on,
      
        (SELECT product_name FROM product c 
         WHERE b.product_code = c.product_code LIMIT 1) AS product_name,
        
        (SELECT po_date FROM po_entry pe WHERE pe.order_no = a.order_no LIMIT 1) AS po_date,
        (SELECT deliveryDate FROM order_materials om WHERE om.order_no = a.order_no AND om.product_code = b.product_code ORDER BY om.id DESC LIMIT 1) AS deliveryDate,
        (SELECT om2.order_no FROM order_materials om2
         WHERE om2.order_no = a.order_no AND om2.product_code = COALESCE(b.product_code, a.product_code)
         ORDER BY CASE WHEN om2.reqStatus = 'Inprocess' THEN 0 WHEN om2.reqStatus = 'pending' THEN 1 ELSE 2 END, om2.id DESC
         LIMIT 1) AS factory_order_no,
        TRIM(CONCAT_WS(' ',
            COALESCE(
                NULLIF(TRIM(a.packingStyle), ''),
                (SELECT NULLIF(TRIM(om3.packingStyle), '') FROM order_materials om3
                  WHERE om3.order_no = a.order_no
                  ORDER BY CASE
                    WHEN om3.product_code = COALESCE(b.product_code, a.product_code) THEN 0
                    ELSE 1 END, om3.id DESC
                  LIMIT 1),
                (SELECT NULLIF(TRIM(bfr.pm_pack_size), '') FROM batch_formula_info bfr
                  WHERE bfr.product_code = COALESCE(b.product_code, a.product_code)
                    AND CAST(bfr.batch_formula_weight AS DECIMAL(18,4)) = CAST(IFNULL(a.batch_size,0) AS DECIMAL(18,4))
                  ORDER BY bfr.id DESC LIMIT 1),
                (SELECT NULLIF(TRIM(bfr2.packing_type), '') FROM batch_formula_info bfr2
                  WHERE bfr2.product_code = COALESCE(b.product_code, a.product_code)
                  ORDER BY bfr2.id DESC LIMIT 1)
            ),
            COALESCE(
                NULLIF(TRIM(a.packingUnit), ''),
                (SELECT NULLIF(TRIM(om4.packingUnit), '') FROM order_materials om4
                  WHERE om4.order_no = a.order_no
                  ORDER BY CASE
                    WHEN om4.product_code = COALESCE(b.product_code, a.product_code) THEN 0
                    ELSE 1 END, om4.id DESC
                  LIMIT 1)
            )
        )) AS packing_type
        
        FROM Work_order_materials a
        INNER JOIN split_planning_qty b ON (
            (
                CAST(NULLIF(TRIM(a.doc_no), '') AS UNSIGNED) = b.id
                AND CAST(NULLIF(TRIM(a.doc_no), '') AS UNSIGNED) > 0
            )
            OR (
                (a.doc_no IS NULL OR TRIM(a.doc_no) = '' OR CAST(NULLIF(TRIM(a.doc_no), '') AS UNSIGNED) = 0)
                AND b.order_no = a.order_no
                AND (
                    b.product_code = a.product_code
                    OR TRIM(IFNULL(a.product_code, '')) = ''
                    OR TRIM(IFNULL(b.product_code, '')) = ''
                )
                AND b.status IN ($gwSplitStatuses)
                AND b.id = (
                    SELECT sp2.id FROM split_planning_qty sp2
                    WHERE sp2.order_no = a.order_no
                      AND (
                          sp2.product_code = a.product_code
                          OR TRIM(IFNULL(a.product_code, '')) = ''
                      )
                      AND sp2.status IN ($gwSplitStatuses)
                    ORDER BY sp2.id DESC
                    LIMIT 1
                )
            )
        )
        WHERE b.status IN ($gwSplitStatuses)
          AND (a.status IS NULL OR TRIM(a.status) = '' OR a.status IN ($gwWoStatuses))
          AND (
              a.status NOT IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')
              OR a.send_for_planning_on IS NULL
              OR TRIM(IFNULL(a.send_for_planning_on, '')) = ''
          )
          AND (a.send_for_analysis_date IS NULL OR TRIM(IFNULL(a.send_for_analysis_date,'')) = '')";
        
        if (!empty($_GET['plant_id'])) {
            $plant_id = $conn->real_escape_string($_GET['plant_id']);
            $sql .= " AND (
                a.plant_id = '$plant_id' OR a.plant_id IS NULL OR TRIM(a.plant_id) = ''
                OR b.plant_id = '$plant_id' OR b.plant_id IS NULL OR TRIM(b.plant_id) = ''
            )";
        }
        
        $sql .= " ORDER BY a.id ASC";

$result = $conn->query($sql);
if ($result === false) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status' => 'error', 'message' => 'Failed to load work orders: ' . $conn->error));
    exit;
}


$colors = [
    "#FFCCCB", // light red
    "#CCFFCC", // light green
    "#CCE5FF", // light blue
    "#FFFACD", // lemon chiffon
    "#D1C4E9", // lavender
    "#FFE0B2", // light orange
    "#F8BBD0", // pink
    "#B2EBF2", // cyan
    "#E6EE9C", // light lime green
    "#FFECB3", // soft yellow-orange
    "#CFD8DC", // blue grey
    "#F0F4C3", // very light olive
    "#DCEDC8", // pastel green
    "#F5F5F5", // light grey
    "#E1BEE7", // soft purple
    "#BBDEFB", // lighter sky blue
    "#FFCDD2", // light rose
    "#D7CCC8", // light taupe
    "#FFCC80", // peach
    "#C8E6C9"  // mint green
];
$colorMap = [];
$colorIndex = 0; 



if ($result->num_rows > 0) {
    $seenWorkOrders = array();
    while ($row = $result->fetch_assoc()) {
        // Collapse accidental duplicate inserts that reused the same workorder_no.
        $woKey = trim((string)($row['workorder_no'] ?? ''));
        if ($woKey !== '') {
            if (isset($seenWorkOrders[$woKey])) {
                continue;
            }
            $seenWorkOrders[$woKey] = true;
        }
        
           $po = $row["order_no"];

    if (!isset($colorMap[$po])) {
        $colorMap[$po] = $colors[$colorIndex % count($colors)];
        $colorIndex++;
    }

    $row["bg_color"] = $colorMap[$po];

        // Ensure packing_type is never blank when OM / WO has style or unit.
        $pt = trim((string)($row['packing_type'] ?? ''));
        if ($pt === '') {
            $style = trim((string)($row['packingStyle'] ?? ''));
            $unit = trim((string)($row['packingUnit'] ?? ''));
            if ($style === '' || $unit === '') {
                $ordEsc = $conn->real_escape_string((string)($row['order_no'] ?? ''));
                $pcEsc = $conn->real_escape_string((string)($row['product_code'] ?? ''));
                if ($ordEsc !== '') {
                    $omPackSql = "SELECT packingStyle, packingUnit FROM order_materials
                                  WHERE order_no = '$ordEsc'
                                  ORDER BY CASE WHEN product_code = '$pcEsc' THEN 0 ELSE 1 END, id DESC
                                  LIMIT 1";
                    $omPackRes = $conn->query($omPackSql);
                    if ($omPackRes && ($omPack = $omPackRes->fetch_assoc())) {
                        if ($style === '') {
                            $style = trim((string)($omPack['packingStyle'] ?? ''));
                        }
                        if ($unit === '') {
                            $unit = trim((string)($omPack['packingUnit'] ?? ''));
                        }
                    }
                }
            }
            $pt = trim($style . ($style !== '' && $unit !== '' ? ' ' : '') . $unit);
            $row['packing_type'] = $pt !== '' ? $pt : '-';
            if ($style !== '' && empty($row['packingStyle'])) {
                $row['packingStyle'] = $style;
            }
            if ($unit !== '' && empty($row['packingUnit'])) {
                $row['packingUnit'] = $unit;
            }
        }

        /* ==================================================================
           = INIT ARRAYS
           ================================================================== */
        $raw_materials = [];
        $packing_materials = [];
        $primary_pm_list = [];
        $consumeableMaterial = [];
        $packing_configuration = [];
        $rm_stock = [];
        $MC_rm_stock = [];
        $pm_stock = [];
        $MC_pm_stock = [];
        $consumable_stock = [];
        $packing_config_stock = [];
        
        /* ============================================================
           = GET BFR_NO (match weight; unit may differ e.g. Nos vs Ltr)
           ============================================================ */
        $bfr_no = gw_resolve_bfr_for_work_order(
            $conn,
            $row['product_code'] ?? '',
            $row['batch_size'] ?? '',
            $row['planUnit'] ?? ''
        );
        $row['bfr_no'] = $bfr_no;
        $bfrEsc = $bfr_no !== '' ? $conn->real_escape_string($bfr_no) : '';

        /* ============================================================
           = RAW MATERIAL LIST
           ============================================================ */
        if ($bfrEsc !== '') {
         $sql1 = "SELECT a.*,
                 m.material_code,
                 (SELECT mother_code FROM material WHERE material_code = a.material_code LIMIT 1) AS mother_material_code,
                 COALESCE(
                     (SELECT material_name FROM material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT material_name FROM others_material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT bulkName FROM bulkMaster WHERE bulkCode = a.material_code LIMIT 1)
                 ) AS material_name,
                 COALESCE(m.material_type, bm.material_type) AS mat_material_type,
                 m.material_subtype AS mat_material_subtype,
                 bm.bulkCode AS bulk_code
                 FROM batch_materials a
                 LEFT JOIN material m ON m.material_code = a.material_code
                 LEFT JOIN bulkMaster bm ON bm.bulkCode = a.material_code
                 WHERE a.material_type = 'Raw Material'
                   AND a.bfr_no = '" . $bfrEsc . "'";
        } else {
         $sql1 = "SELECT a.*,
                 m.material_code,
                 (SELECT mother_code FROM material WHERE material_code = a.material_code LIMIT 1) AS mother_material_code,
                 COALESCE(
                     (SELECT material_name FROM material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT material_name FROM others_material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT bulkName FROM bulkMaster WHERE bulkCode = a.material_code LIMIT 1)
                 ) AS material_name,
                 COALESCE(m.material_type, bm.material_type) AS mat_material_type,
                 m.material_subtype AS mat_material_subtype,
                 bm.bulkCode AS bulk_code
                 FROM batch_materials a
                 LEFT JOIN batch_formula_info b ON a.bfr_no = b.bfr_no
                 LEFT JOIN material m ON m.material_code = a.material_code
                 LEFT JOIN bulkMaster bm ON bm.bulkCode = a.material_code
                 WHERE a.material_type = 'Raw Material'
                   AND b.batch_formula_weight = '" . $row['batch_size'] . "'
                   AND b.rm_batch_size_unit = '" . $row['planUnit'] . "'";
        }

        $result1 = $conn->query($sql1);

        if ($result1) {
        while ($rm = $result1->fetch_assoc()) {
            
            // Check if material type is bulk, then filter Premix materials
            if (!empty($rm['mat_material_type']) && strtolower($rm['mat_material_type']) === 'bulk') {
                $material_subtype = !empty($rm['mat_material_subtype']) ? strtolower($rm['mat_material_subtype']) : '';
                
                // For bulk materials, skip if material_subtype is Premix or Primix
                if (!empty($material_subtype) && 
                    (strpos($material_subtype, 'premix') !== false || strpos($material_subtype, 'primix') !== false)) {
                    continue; // Skip premix materials in raw materials array
                }
            }
            
            // Mark if bulk material - check if material_type is 'Bulk' and bulk_code exists
            $isMaterialBulk = (!empty($rm['mat_material_type']) && strtolower($rm['mat_material_type']) === 'bulk');
            $hasBulkCode = !empty($rm['bulk_code']);
            
            $rm['isBulk'] = ($isMaterialBulk && $hasBulkCode);
            
            // Process bulk materials
            if ($rm['isBulk']) {
                $bulkCode = $rm['bulk_code'];
                $rm['bulkCode'] = $bulkCode;
                
                /* --- FETCH BULK STOCK --- */
                $bulkStockQuery = "SELECT bulkCode, 
                                          SUM(COALESCE(mfg_qty, 0) - COALESCE(used_qty, 0)) as available_qty
                                   FROM bulk_stock
                                   WHERE bulkCode = '" . $bulkCode . "'
                                   GROUP BY bulkCode";
                $bulkStockResult = $conn->query($bulkStockQuery);
                $rm['bulkStock'] = ($bulkStockResult && $bulkStockResult->num_rows > 0)
                    ? floatval($bulkStockResult->fetch_assoc()['available_qty'])
                    : 0;
                
                /* --- FETCH BULK COMPONENTS --- */
                $bulkComponentsQuery = "SELECT * FROM bulkMaterials WHERE bulkCode = '" . $bulkCode . "'";
                $bulkComponentsResult = $conn->query($bulkComponentsQuery);
                $bulkComponents = [];
                
                if ($bulkComponentsResult) {
                while ($comp = $bulkComponentsResult->fetch_assoc()) {
                    $compAvail = gw_get_shortages_stock_fields($conn, $comp['material_code']);
                    $compData = array_merge([
                        'bulkCode' => $comp['bulkCode'],
                        'material_code' => $comp['material_code'],
                        'material_name' => gw_resolve_material_name($conn, $comp['material_code']),
                        'material_type' => $comp['material_type'],
                        'material_subtype' => $comp['material_subtype'],
                        'perQty' => floatval($comp['perQty'])
                    ], $compAvail);
                    
                    // If component is Premix, fetch primix materials
                    if (!empty($comp['material_type']) && 
                        (strtolower($comp['material_type']) === 'premix' || 
                         (!empty($comp['material_subtype']) && strtolower($comp['material_subtype']) === 'premix'))) {
                        
                        $primixQuery = "SELECT * FROM primixMaterials WHERE premixCode = '" . $comp['material_code'] . "'";
                        $primixResult = $conn->query($primixQuery);
                        $primixMaterials = [];
                        
                        while ($primix = $primixResult->fetch_assoc()) {
                            $primixAvail = gw_get_shortages_stock_fields($conn, $primix['material_code']);
                            $primixMaterials[] = array_merge([
                                'premixCode' => $primix['premixCode'],
                                'material_code' => $primix['material_code'],
                                'material_name' => gw_resolve_material_name($conn, $primix['material_code']),
                                'material_type' => $primix['material_type'],
                                'material_subtype' => $primix['material_subtype'],
                                'perQty' => floatval($primix['perQty'])
                            ], $primixAvail);
                        }
                        
                        $compData['primixMaterials'] = $primixMaterials;
                    }
                    
                    $bulkComponents[] = $compData;
                }
                }
                
                $rm['bulkComponents'] = $bulkComponents;
            }

            $resolvedName = trim($rm['material_name'] ?? '');
            if ($resolvedName === '' || $resolvedName === ($rm['material_code'] ?? '')) {
                $resolvedName = gw_resolve_material_name($conn, $rm['material_code'] ?? '');
            }
            if ($resolvedName !== '') {
                $rm['material_name'] = $resolvedName;
            }
            if (empty($rm['mother_material_code'])) {
                $rm['mother_material_code'] = $rm['material_code'] ?? '';
            }

            if (!empty($rm['isBulk']) && $rm['isBulk']) {
                $bulkAvail = floatval($rm['bulkStock'] ?? 0);
                $rm['available_Stock_qty'] = $bulkAvail;
                $rm['available_qty'] = $bulkAvail;
                $rm['rm_balance'] = $bulkAvail;
                $rm['booked_qty'] = 0;
            } else {
                $rm = array_merge($rm, gw_get_shortages_stock_fields($conn, $rm['material_code'] ?? ''));
            }

            $raw_materials[] = $rm;

            /* --- STOCK FOR EXACT RM (only for non-bulk) --- */
            if (empty($rm['isBulk']) || !$rm['isBulk']) {
                $s1 = $conn->query("SELECT * FROM vw_total_available_stock 
                                    WHERE material_code = '" . $rm['material_code'] . "'");

                $rm_stock[] = $s1->num_rows > 0 ?
                              $s1->fetch_assoc() :
                              ["material_code" => $rm['material_code'], "available_qty" => 0];

                /* --- STOCK FOR MOTHER RM --- */
                $motherCode = !empty($rm['mother_material_code']) ? $rm['mother_material_code'] : $rm['material_code'];
                $s2 = $conn->query("SELECT * FROM vw_total_available_stock 
                                    WHERE material_code = '" . $conn->real_escape_string($motherCode) . "'");

                $MC_rm_stock[] = $s2->num_rows > 0 ?
                                 $s2->fetch_assoc() :
                                 ["material_code" => $motherCode, "available_qty" => 0];
            } else {
                // For bulk materials, add empty stock entries (stock will be from bulkStock)
                $rm_stock[] = ["material_code" => $rm['material_code'], "available_qty" => 0];
                $MC_rm_stock[] = ["material_code" => $rm['material_code'], "available_qty" => 0];
            }
        }
        }

        /* ============================================================
           = PACKING MATERIAL LIST
           ============================================================ */
        if ($bfrEsc !== '') {
        $sql2 = "SELECT a.*,
                 (SELECT mother_code FROM material m 
                  WHERE m.material_code = a.material_code LIMIT 1) AS mother_material_code,
                 COALESCE(
                     (SELECT material_name FROM material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT material_name FROM others_material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT bulkName FROM bulkMaster WHERE bulkCode = a.material_code LIMIT 1)
                 ) AS material_name
                 FROM batch_materials a
                 WHERE a.material_type = 'Packing Material'
                   AND a.bfr_no = '" . $bfrEsc . "'";
        } else {
        $sql2 = "SELECT a.*,
                 (SELECT mother_code FROM material m 
                  WHERE m.material_code = a.material_code LIMIT 1) AS mother_material_code,
                 COALESCE(
                     (SELECT material_name FROM material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT material_name FROM others_material WHERE material_code = a.material_code LIMIT 1),
                     (SELECT bulkName FROM bulkMaster WHERE bulkCode = a.material_code LIMIT 1)
                 ) AS material_name
                 FROM batch_materials a
                 LEFT JOIN batch_formula_info b ON a.bfr_no = b.bfr_no
                 WHERE a.material_type = 'Packing Material'
                   AND b.batch_formula_weight = '" . $row['batch_size'] . "'
                   AND b.rm_batch_size_unit = '" . $row['planUnit'] . "'
                   AND a.pack_size = '" . $conn->real_escape_string($row['packingStyle'] ?? $row['packing_type'] ?? '') . "'";
        }

        $result2 = $conn->query($sql2);

        if ($result2) {
        while ($pm = $result2->fetch_assoc()) {
            $pmMotherCode = trim((string)($pm['mother_material_code'] ?? $pm['material_code'] ?? ''));
            if ($pmMotherCode === '') {
                $pmMotherCode = trim((string)($pm['material_code'] ?? ''));
            }
            $pm['mother_material_code'] = $pmMotherCode;
            $pm = array_merge($pm, gw_get_material_availability($conn, $pm['material_code'] ?? ''));
            $packing_materials[] = $pm;

            /* --- STOCK FOR EXACT PM --- */
            $s3 = $conn->query("SELECT * FROM vw_total_available_stock 
                                WHERE material_code = '" . $conn->real_escape_string($pm['material_code'] ?? '') . "'");

            $pm_stock[] = $s3 && $s3->num_rows > 0 ?
                          $s3->fetch_assoc() :
                          ["material_code" => $pm['material_code'] ?? '', "available_qty" => 0];

            /* --- STOCK FOR MOTHER PM --- */
            $s4 = $conn->query("SELECT * FROM vw_total_available_stock 
                                WHERE material_code = '" . $conn->real_escape_string($pmMotherCode) . "'");

            $MC_pm_stock[] = $s4 && $s4->num_rows > 0 ?
                             $s4->fetch_assoc() :
                             ["material_code" => $pmMotherCode, "available_qty" => 0];
        }
        }

        /* ============================================================
           = PRIMARY PACKING MATERIALS LIST
           ============================================================ */
        if ($bfr_no) {
            $sql3 = "SELECT a.*, 
                     m.material_name, 
                     m.grade as gradeName
                     
                     FROM batch_materials a
                     LEFT JOIN material m ON m.material_code = a.material_code
                     WHERE a.bfr_no = '" . $conn->real_escape_string($bfr_no) . "'
                       AND a.pm_type = 'Primary Packing'
                       AND a.material_type = 'Packing Material'";
            
            $result3 = $conn->query($sql3);
            if ($result3 && $result3->num_rows > 0) {
                while ($pm = $result3->fetch_assoc()) {
                    // Calculate total_qty if not present
                    $qty = floatval($pm['qty'] ?? 0);
                    $overages = floatval($pm['overages'] ?? 0);
                    $overage_qty = ($qty * $overages) / 100;
                    $total_qty = $qty + $overage_qty;
                    
                    // Format the data similar to unitformula structure
                    $pm_data = array_merge([
                        'material_code' => $pm['material_code'] ?? '-',
                        'material_name' => $pm['material_name'] ?? '-',
                        'gradeName' => $pm['gradeName'] ?? '-',
                        'qty' => $qty,
                        'unit_name' => $pm['unit_name'] ?? $pm['unit'] ?? 'Nos',
                        'unit' => $pm['unit'] ?? 'Nos',
                        'overages' => $overages,
                        'total_qty' => floatval($pm['total_qty'] ?? $total_qty),
                        'batch_qty' => floatval($pm['batch_qty'] ?? 0)
                    ], gw_get_material_availability($conn, $pm['material_code'] ?? ''));
                    $primary_pm_list[] = $pm_data;
                }
            }
        }

        /* ============================================================
           = CONSUMABLE MATERIALS LIST
           ============================================================ */
        if ($bfr_no) {
            $sql4 = "SELECT a.*, 
                     om.material_name,
                     om.grade as gradeName 
                     FROM batch_materials a
                     LEFT JOIN others_material om ON om.material_code = a.material_code
                     WHERE a.bfr_no = '" . $conn->real_escape_string($bfr_no) . "'
                       AND a.pm_type = 'Consumeable Material'
                       AND a.material_type = 'Consumeable Material'";
            
            $result4 = $conn->query($sql4);
            if ($result4 && $result4->num_rows > 0) {
                while ($cons = $result4->fetch_assoc()) {
                    // Calculate total_qty if not present
                    $qty = floatval($cons['qty'] ?? 0);
                    $overages = floatval($cons['overages'] ?? 0);
                    $overage_qty = ($qty * $overages) / 100;
                    $total_qty = $qty + $overage_qty;
                    
                    // Format the data similar to unitformula structure
                    $cons_data = array_merge([
                        'material_code' => $cons['material_code'] ?? '-',
                        'material_name' => $cons['material_name'] ?? '-',
                        'gradeName' => $cons['gradeName'] ?? '-',
                        'qty' => $qty,
                        'unit_name' => $cons['unit_name'] ?? $cons['unit'] ?? 'Nos',
                        'unit' => $cons['unit'] ?? 'Nos',
                        'overages' => $overages,
                        'total_qty' => floatval($cons['total_qty'] ?? $total_qty),
                        'batch_qty' => floatval($cons['batch_qty'] ?? 0)
                    ], gw_get_material_availability($conn, $cons['material_code'] ?? ''));
                    $consumeableMaterial[] = $cons_data;
                    
                    /* --- STOCK FOR CONSUMABLE MATERIAL --- */
                    $s_cons = $conn->query("SELECT * FROM vw_total_available_stock 
                                            WHERE material_code = '" . $conn->real_escape_string($cons['material_code']) . "'");
                    
                    $consumable_stock[] = $s_cons->num_rows > 0 ?
                                          $s_cons->fetch_assoc() :
                                          ["material_code" => $cons['material_code'], "available_qty" => 0];
                }
            }
        }

        /* ============================================================
           = PACKING CONFIGURATION (Secondary Packing with Configurations)
           ============================================================ */
        if ($bfr_no) {
            // Get distinct pack sizes and batch sizes for secondary packing
            $sql5 = "SELECT DISTINCT a.pack_size, a.batch_size, a.unit, a.pm_type
                     FROM batch_materials a
                     WHERE a.bfr_no = '" . $conn->real_escape_string($bfr_no) . "'
                       AND a.pm_type = 'Secondary Packing'
                       AND a.material_type = 'Packing Material'";
            
            $result5 = $conn->query($sql5);
            if ($result5 && $result5->num_rows > 0) {
                while ($config = $result5->fetch_assoc()) {
                    $pack_size = $config['pack_size'];
                    $batch_size = $config['batch_size'];
                    
                    // Get all materials for this pack size
                    $sql6 = "SELECT a.*, 
                             m.material_name,
                             m.grade as gradeName
                         
                             FROM batch_materials a
                             LEFT JOIN material m ON m.material_code = a.material_code
                             WHERE a.bfr_no = '" . $conn->real_escape_string($bfr_no) . "'
                               AND a.pack_size = '" . $conn->real_escape_string($pack_size) . "'
                               AND a.pm_type = 'Secondary Packing'
                               AND a.material_type = 'Packing Material'";
                    
                    $result6 = $conn->query($sql6);
                    $packing_list = [];
                    if ($result6 && $result6->num_rows > 0) {
                        while ($pm = $result6->fetch_assoc()) {
                            // Calculate total_qty if not present
                            $qty = floatval($pm['qty'] ?? 0);
                            $overages = floatval($pm['overages'] ?? 0);
                            $overage_qty = ($qty * $overages) / 100;
                            $total_qty = $qty + $overage_qty;
                            
                            $packing_list[] = array_merge([
                                'material_code' => $pm['material_code'] ?? '-',
                                'material_name' => $pm['material_name'] ?? '-',
                                'qty' => $qty,
                                'unit_name' => $pm['unit_name'] ?? $pm['unit'] ?? 'Nos',
                                'overages' => $overages,
                                'total_qty' => floatval($pm['total_qty'] ?? $total_qty),
                                'batch_qty' => floatval($pm['batch_qty'] ?? 0)
                            ], gw_get_material_availability($conn, $pm['material_code'] ?? ''));
                            
                            /* --- STOCK FOR PACKING CONFIGURATION MATERIAL --- */
                            $s_pack_config = $conn->query("SELECT * FROM vw_total_available_stock 
                                                           WHERE material_code = '" . $conn->real_escape_string($pm['material_code']) . "'");
                            
                            $packing_config_stock[] = $s_pack_config->num_rows > 0 ?
                                                      $s_pack_config->fetch_assoc() :
                                                      ["material_code" => $pm['material_code'], "available_qty" => 0];
                        }
                    }
                    
                    if (count($packing_list) > 0) {
                        $packing_configuration[] = [
                            'batch_size' => floatval($batch_size),
                            'pack_size' => $pack_size,
                            'unit' => $config['unit'] ?? '',
                            'packing_list' => $packing_list
                        ];
                    }
                }
            }
        }

        if ($bfr_no !== '') {
            gw_gwo_hydrate_materials_from_bfr_json(
                $conn,
                $bfr_no,
                $row,
                $raw_materials,
                $packing_materials,
                $primary_pm_list,
                $consumeableMaterial,
                $packing_configuration,
                $rm_stock,
                $MC_rm_stock,
                $pm_stock,
                $MC_pm_stock,
                $consumable_stock,
                $packing_config_stock
            );
        }

        /* ==================================================================
           = ASSIGN ARRAYS INTO ROW (before stock planning)
           ================================================================== */
        $row['Raw_Material'] = $raw_materials;
        $row['Packing_Material'] = $packing_materials;
        $row['primary_pm_list'] = $primary_pm_list;
        $row['consumeableMaterial'] = $consumeableMaterial;
        $row['packing_configuration'] = $packing_configuration;
        
        // Store batch size info for calculations
        $row['bom_batch_size'] = floatval($row['batch_size']);
        if (count($primary_pm_list) > 0 || count($consumeableMaterial) > 0) {
            // Try to get primary_pm_batch_size from batch_formula_info
            if ($bfr_no) {
                $pm_batch_query = "SELECT batch_formula_weight FROM batch_formula_info WHERE bfr_no = '" . $bfr_no . "' LIMIT 1";
                $pm_batch_result = $conn->query($pm_batch_query);
                if ($pm_batch_result->num_rows > 0) {
                    $pm_batch_row = $pm_batch_result->fetch_assoc();
                    $row['batch_formula_weight'] = floatval($pm_batch_row['batch_formula_weight'] ?? $row['batch_size']);
                } else {
                    $row['primary_pm_batch_size'] = floatval($row['batch_size']);
                }
            } else {
                $row['primary_pm_batch_size'] = floatval($row['batch_size']);
            }
        }

        $row['rm_stock'] = $rm_stock;
        $row['MC_rm_stock'] = $MC_rm_stock;

        $row['pm_stock'] = $pm_stock;
        $row['MC_pm_stock'] = $MC_pm_stock;
        
        $row['consumable_stock'] = $consumable_stock;
        $row['packing_config_stock'] = $packing_config_stock;

        $output[] = $row;
    }
}

/* ======================================================================
   = NOW RUN STOCK CHECK FOR EACH WORK ORDER
   ====================================================================== */
foreach ($output as &$wo) {
    // Normalize stock pools to Medicap-safe available qty (ignore orphan material_issue).
    foreach (array('rm_stock', 'MC_rm_stock', 'pm_stock', 'MC_pm_stock', 'consumable_stock', 'packing_config_stock') as $stockKey) {
        if (empty($wo[$stockKey]) || !is_array($wo[$stockKey])) {
            continue;
        }
        foreach ($wo[$stockKey] as &$stockRow) {
            if (!is_array($stockRow)) {
                continue;
            }
            $code = trim((string)($stockRow['material_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $stockRow['available_qty'] = gw_gwo_resolve_available_qty($conn, $code);
        }
        unset($stockRow);
    }

    $materialLineCount = count($wo['Raw_Material'] ?? array())
        + count($wo['Packing_Material'] ?? array())
        + count($wo['primary_pm_list'] ?? array())
        + count($wo['consumeableMaterial'] ?? array());
    if ($materialLineCount === 0) {
        $wo['can_planned'] = false;
        $wo['message'] = 'No BOM materials found for batch size ' . ($wo['batch_size'] ?? '');
        $wo['rm_status'] = array();
        $wo['pm_status'] = array();
        continue;
    }

    $wo['can_planned'] = true;
    $wo['message'] = "Sufficient stock available";
    $wo['rm_status'] = [];
    $wo['pm_status'] = [];

    /* --- Convert stock arrays into lookup --- */
    $rm_lookup = array_column($wo['rm_stock'], 'available_qty', 'material_code');
    // Mother code is not used in this project — RM stock only (matches Generate WO frontend).
    $MC_rm_lookup = array();
    $pm_lookup = array_column($wo['pm_stock'], 'available_qty', 'material_code');
    $MC_pm_lookup = array();


    /* ==================================================================
       = RAW MATERIAL STOCK CHECK
       ================================================================== */
    foreach (($wo['Raw_Material'] ?? array()) as $rm) {

        $code = $rm['material_code'];
        $mother = trim((string)($rm['mother_material_code'] ?? $rm['material_code'] ?? ''));
        $required = floatval($rm['batch_qty']);

        // Check if this is a bulk material
        if (!empty($rm['isBulk']) && $rm['isBulk']) {
            
            $bulkStock = floatval($rm['bulkStock'] ?? 0);
            $bulkCode = $rm['bulkCode'] ?? $code;
            
            if ($bulkStock >= $required) {
                // Sufficient bulk stock
                $wo['rm_status'][] = [
                    "material" => $code, 
                    "required" => $required, 
                    "from" => "bulk_stock",
                    "bulkCode" => $bulkCode,
                    "bulkStockUsed" => $required
                ];
            } else {
                // Insufficient bulk stock - calculate shortage from components
                $bulkShortage = max(0, $required - $bulkStock);
                $bulkStockUsed = $bulkStock;
                
                $wo['rm_status'][] = [
                    "material" => $code,
                    "required" => $required,
                    "from" => "bulk_stock + components",
                    "bulkCode" => $bulkCode,
                    "bulkStockUsed" => $bulkStockUsed,
                    "bulkShortage" => $bulkShortage
                ];
                
                // Process bulk components to calculate shortages
                if (!empty($rm['bulkComponents']) && is_array($rm['bulkComponents'])) {
                    $totalPerQty = 0;
                    foreach ($rm['bulkComponents'] as $comp) {
                        $totalPerQty += floatval($comp['perQty'] ?? 0);
                    }
                    
                    if ($totalPerQty > 0) {
                        foreach ($rm['bulkComponents'] as $comp) {
                            $compRequiredQty = ($bulkShortage * floatval($comp['perQty'])) / $totalPerQty;
                            $compType = strtolower($comp['material_type'] ?? '');
                            
                            if ($compType === 'premix' && !empty($comp['primixMaterials'])) {
                                // Process Premix components - check primix materials
                                foreach ($comp['primixMaterials'] as $primixMat) {
                                    $primixMaterialCode = $primixMat['material_code'];
                                    $primixPerQty = floatval($primixMat['perQty'] ?? 0);
                                    
                                    // Calculate required quantity for this primix material
                                    $primixTotalPerQty = 0;
                                    foreach ($comp['primixMaterials'] as $pm) {
                                        $primixTotalPerQty += floatval($pm['perQty'] ?? 0);
                                    }
                                    
                                    if ($primixTotalPerQty > 0) {
                                        $primixRequiredQty = ($compRequiredQty * $primixPerQty) / $primixTotalPerQty;
                                        
                                        // Check stock for primix raw material
                                        // Get mother material code for primix material
                                        $primixMotherQuery = $conn->query("SELECT mother_code FROM material WHERE material_code = '" . $conn->real_escape_string($primixMaterialCode) . "' LIMIT 1");
                                        $primixMotherRow = ($primixMotherQuery && $primixMotherQuery->num_rows > 0) ? $primixMotherQuery->fetch_assoc() : array();
                                        $primixMotherCode = trim((string)($primixMotherRow['mother_code'] ?? $primixMaterialCode));
                                        
                                        // Get stock for primix material
                                        $primixStockQuery = $conn->query("SELECT * FROM vw_total_available_stock WHERE material_code = '" . $primixMaterialCode . "'");
                                        $primixStock = $primixStockQuery->num_rows > 0 ? floatval($primixStockQuery->fetch_assoc()['available_qty']) : 0;
                                        
                                        $primixMotherStockQuery = $conn->query("SELECT * FROM vw_total_available_stock WHERE material_code = '" . $primixMotherCode . "'");
                                        $primixMother = $primixMotherStockQuery->num_rows > 0 ? floatval($primixMotherStockQuery->fetch_assoc()['available_qty']) : 0;
                                        
                                        if (($primixStock + $primixMother) < $primixRequiredQty) {
                                            $wo['can_planned'] = false;
                                            $wo['message'] = "Raw Material Short (from Premix in Bulk): $primixMaterialCode";
                                        }
                                    }
                                }
                            } elseif ($compType === 'raw material') {
                                // Process Raw Material components directly
                                $compMaterialCode = $comp['material_code'];
                                $compStock = $rm_lookup[$compMaterialCode] ?? 0;
                                $compMother = $MC_rm_lookup[$compMaterialCode] ?? 0;
                                
                                if (($compStock + $compMother) < $compRequiredQty) {
                                    $wo['can_planned'] = false;
                                    $wo['message'] = "Raw Material Short (from Bulk Components): $compMaterialCode";
                                }
                            }
                        }
                    }
                } else {
                    // No components defined, mark as shortage
                    $wo['can_planned'] = false;
                    $wo['message'] = "Bulk Material Short: $code (No components defined)";
                }
            }
            
        } else {
            // Non-bulk material - use existing logic
            $stock = $rm_lookup[$code] ?? 0;
            $mother_stock = $MC_rm_lookup[$mother] ?? 0;

            if ($stock >= $required) {
                $wo['rm_status'][] = ["material" => $code, "required" => $required, "from" => "rm_stock"];
            }
            elseif (($stock + $mother_stock) >= $required) {
                $wo['rm_status'][] = ["material" => $code, "required" => $required, "from" => "rm_stock + MC_rm_stock"];
            }
            else {
                $wo['can_planned'] = false;
                $wo['message'] = "Raw Material Short: $code";

                $wo['rm_status'][] = ["material" => $code, "required" => $required, "from" => "short"];
            }
        }
    }


    /* ==================================================================
       = PACKING MATERIAL STOCK CHECK
       ================================================================== */
    foreach (($wo['Packing_Material'] ?? array()) as $pm) {

        $code = $pm['material_code'];
        $mother = trim((string)($pm['mother_material_code'] ?? $pm['material_code'] ?? ''));
        $required = floatval($pm['batch_qty']);

        $stock = $pm_lookup[$code] ?? 0;
        $mother_stock = $MC_pm_lookup[$mother] ?? 0;

        if ($stock >= $required) {
            $wo['pm_status'][] = ["material" => $code, "required" => $required, "from" => "pm_stock"];
        }
        elseif (($stock + $mother_stock) >= $required) {
            $wo['pm_status'][] = ["material" => $code, "required" => $required, "from" => "pm_stock + MC_pm_stock"];
        }
        else {
            $wo['can_planned'] = false;
            $wo['message'] = "Packing Material Short: $code";

            $wo['pm_status'][] = ["material" => $code, "required" => $required, "from" => "short"];
        }
    }
}

/* Classify Generate WO rows for UI actions (To Be Plan / To Be Not Plan).
   Stock check above sets can_planned; selection buttons require CAN_PLAN / CANNOT_PLAN. */
foreach ($output as &$wo) {
    $curStatus = trim((string)($wo['status'] ?? ''));
    $alreadyClassified = in_array($curStatus, array('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED', 'CANNOT_PLAN'), true);
    $msg = (string)($wo['message'] ?? '');
    $bomMissing = (stripos($msg, 'No BOM') !== false);
    if ($bomMissing) {
        $wo['bom_missing'] = true;
    }

    if (!$alreadyClassified) {
        if (!empty($wo['can_planned']) && !$bomMissing) {
            $wo['status'] = 'CAN_PLAN';
        } else {
            $wo['status'] = 'CANNOT_PLAN';
        }
    }

    if (empty($wo['deductions']) || !is_array($wo['deductions'])) {
        $wo['deductions'] = array();
        $woNo = (string)($wo['workorder_no'] ?? '');
        $unit = (string)($wo['planUnit'] ?? '');
        foreach (array(
            array('list' => $wo['rm_status'] ?? array(), 'type' => 'RM'),
            array('list' => $wo['pm_status'] ?? array(), 'type' => 'PM'),
        ) as $bucket) {
            foreach ($bucket['list'] as $st) {
                if (!is_array($st)) {
                    continue;
                }
                $from = (string)($st['from'] ?? '');
                $required = floatval($st['required'] ?? 0);
                $isShort = (stripos($from, 'short') !== false);
                $usedMc = (!$isShort && stripos($from, 'MC') !== false);
                $bulkUsed = floatval($st['bulkStockUsed'] ?? 0);
                $dedRm = 0;
                $dedMc = 0;
                if (!$isShort) {
                    if ($usedMc) {
                        $dedMc = $required;
                    } else {
                        $dedRm = $required;
                    }
                }
                $wo['deductions'][] = array(
                    'workorder_no' => $woNo,
                    'material_code' => $st['material'] ?? '',
                    'unit' => $unit,
                    'type' => $bucket['type'],
                    'deducted_from_RM' => $dedRm,
                    'deducted_from_MC' => $dedMc,
                    'deducted_from_Bulk' => $bulkUsed,
                    'shortage' => $isShort ? $required : 0,
                    'status1' => $isShort ? 'CANNOT_PLAN' : ($usedMc ? 'CAN_PLAN_MC_QTY_USED' : 'CAN_PLAN'),
                );
            }
        }
        if (!$bomMissing && !empty($wo['can_planned'])) {
            foreach ($wo['deductions'] as $d) {
                if (floatval($d['deducted_from_MC'] ?? 0) > 0 && floatval($d['shortage'] ?? 0) <= 0) {
                    if ($wo['status'] === 'CAN_PLAN') {
                        $wo['status'] = 'CAN_PLAN_MC_QTY_USED';
                    }
                    break;
                }
            }
        }
    }
}
unset($wo);

echo json_encode($output);


    }
 else   if ($_GET["type"] == "updatePendingPOs") {

        $processingStatuses = array('Work Order Processed', 'Rejected', 'Hold', 'Work Order Preparation Approved');
        $marketingStatuses = array('approve', 'reject', 'Approved', 'Rejected');
        $hasDigitalSignature = !empty($input["digital_signature"]) || !empty($_GET["digital_signature"]);
        $isMrpProcessing = in_array($_GET["status"], $processingStatuses, true)
            || !empty($input["split_id"])
            || !empty($input["batches"])
            || !empty($input["order_no"]);

        // Marketing PO approval with digital signature only (not MRP Processing entry)
        if (in_array($_GET["status"], $marketingStatuses, true) && $hasDigitalSignature && !$isMrpProcessing) {
            if (empty($_GET["id"])) {
                po_processing_json_response(array('status' => 'error', 'message' => 'PO ID is required'));
            }
            if (empty($_GET["remark"]) || trim($_GET["remark"]) === '') {
                po_processing_json_response(array('status' => 'error', 'message' => 'Remark is required'));
            }

            $digital_signature = po_processing_escape($conn, $input["digital_signature"] ?? $_GET["digital_signature"] ?? '');
            $status = po_processing_escape($conn, $_GET["status"]);
            $po_id = po_processing_escape($conn, $_GET["id"]);
            $remark = po_processing_escape($conn, $_GET["remark"]);
            $emp_id = po_processing_escape($conn, $_GET["emp_id"]);
            $entry_date = date('Y-m-d H:i:s');

            if ($status === 'approve') {
                $status = 'Approved';
            }
            if ($status === 'reject') {
                $status = 'Rejected';
            }

            if ($status === 'Approved') {
                $sql = "UPDATE po_entry SET
                        status='Approved',
                        approve_by='$emp_id',
                        approve_date='$entry_date',
                        remark='$remark',
                        approve_digital_signature='$digital_signature',
                        approve_digital_signature_date='$entry_date'
                        WHERE id='$po_id'";
            } else if ($status === 'Rejected') {
                $sql = "UPDATE po_entry SET
                        status='Rejected',
                        approve_by='$emp_id',
                        approve_date='$entry_date',
                        remark='$remark',
                        reject_digital_signature='$digital_signature',
                        reject_digital_signature_date='$entry_date'
                        WHERE id='$po_id'";
            } else {
                po_processing_json_response(array('status' => 'error', 'message' => 'Invalid approval status'));
            }

            if ($conn->query($sql)) {
                // After Marketing Approve → push lines into Factory Order Pending
                $foSync = array('status' => 'skipped');
                if ($status === 'Approved') {
                    require_once __DIR__ . '/../mrp/po_to_factoryorder.php';
                    $foSync = mrp_po_fo_push_approved_po(
                        $conn,
                        (int)$po_id,
                        '',
                        $_GET['plant_id'] ?? '',
                        $_GET['emp_id'] ?? ''
                    );
                }
                po_processing_json_response(array(
                    'status' => 'success',
                    'factory_order' => $foSync,
                ));
            }
            po_processing_json_response(array('status' => 'error', 'message' => $conn->error));
        }

        $entry_date = date('Y-m-d H:i:s');
        $status = $_GET["status"] ?? '';
        if ($status === 'Work Order Processed' && empty($input['batches'])) {
            po_processing_json_response(array('status' => 'error', 'message' => 'Please generate batches before approving.'));
        }
        po_processing_update_split($conn, $input, $status, $_GET['emp_id'] ?? '', $entry_date);
}
    else if ($_GET["type"] == "getProcessingRemark") {
        $order_no = $conn->real_escape_string($_GET["order_no"] ?? '');
        $product_code = $conn->real_escape_string($_GET["product_code"] ?? '');
        $split_id = $conn->real_escape_string($_GET["split_id"] ?? '');

        if ($split_id !== '') {
            $sql = "SELECT a.id, a.order_no, a.product_code, p.product_name, a.remarkText
                    FROM split_planning_qty a
                    LEFT JOIN product p ON p.product_code = a.product_code
                    WHERE a.id = '".$split_id."' LIMIT 1";
        } elseif ($order_no !== '' && $product_code !== '') {
            $sql = "SELECT a.id, a.order_no, a.product_code, p.product_name, a.remarkText
                    FROM split_planning_qty a
                    LEFT JOIN product p ON p.product_code = a.product_code
                    WHERE a.order_no = '".$order_no."' AND a.product_code = '".$product_code."'
                    ORDER BY a.id DESC LIMIT 1";
        } elseif ($order_no !== '') {
            $sql = "SELECT a.id, a.order_no, a.product_code, p.product_name, a.remarkText
                    FROM split_planning_qty a
                    LEFT JOIN product p ON p.product_code = a.product_code
                    WHERE a.order_no = '".$order_no."'
                    ORDER BY a.id DESC LIMIT 1";
        } else {
            echo json_encode(array("status" => "error", "remark" => ""));
            exit;
        }

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(array(
                "status" => "success",
                "remark" => $row["remarkText"] ?? "",
                "split_id" => $row["id"] ?? "",
                "order_no" => $row["order_no"] ?? "",
                "product_code" => $row["product_code"] ?? "",
                "product_name" => $row["product_name"] ?? ""
            ));
        } else {
            echo json_encode(array("status" => "success", "remark" => ""));
        }
    }
    else if ($_GET["type"] == "updateRemark") {
        $remark = $conn->real_escape_string($input["remark"] ?? '');
        $split_id = $conn->real_escape_string($input["split_id"] ?? '');

        if ($split_id === '') {
            echo json_encode(array("status" => "Split id is required"));
            exit;
        }

        $sql = "UPDATE split_planning_qty SET remarkText='".$remark."' WHERE id='".$split_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => $conn->error));
        }
    }
    else if ($_GET["type"] == "update_rate") {
        
        echo  $sql = "UPDATE order_materials SET rate='".$_GET["rate"]."',order_qty='".$_GET["order_qty"]."',amount_inr='".$_GET["amt_inr"]."',amount_usd='".$_GET["amt_usd"]."'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
    	    echo "{\"status\":\"success\"}";
    	    
        } else { 
            
          echo "{\"status\":\"".$conn->error."\"}";
           
        }
        
        
    }
        else if ($_GET["type"] == "getPendingProcessingPOsApproval") {
              
        // ------------------------------------------
// EXCLUDE ALL order_no which already exist in Work_order_materials
// ------------------------------------------

$output = array();

// Fetch all pending POs from split_planning_qty (main table)
// Try to get additional fields from Work_order_materials if they exist
$sql = "SELECT a.*,
  a.id as doc_no,
  a.oder_qty as plan_qty,
 
  CONCAT(a.month, '-', a.year) as planMonth,
  
  c.po_date,
  c.file,
  c.client_code,
  c.po_no,
  c.po_type,
  c.valid_till,
  c.client_type,
  c.serviceCategory,
  
  (SELECT deliveryDate FROM order_materials o WHERE o.order_no = a.order_no AND o.product_code = a.product_code ORDER BY o.id DESC LIMIT 1) AS deliveryDate,
  
  p.category, 
  p.product_name as product_name_from_product,
  (SELECT c1.LglNm FROM client c1 WHERE c1.client_code = c.client_code LIMIT 1) AS clientName,
  (SELECT c1.LglNm FROM client c1 WHERE c1.client_code = c.client_code LIMIT 1) AS mainGroupName,
  (SELECT c2.LglNm FROM client c2 WHERE c2.client_code = c.conisgnee LIMIT 1) AS conisgneeName,
  (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(middlename, ''), ' ', IFNULL(lastname, ''))
                   FROM employee 
                   WHERE emp_id = a.entry_by limit 1
                  ) AS emp_name,
  (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(middlename, ''), ' ', IFNULL(lastname, ''))
                   FROM employee 
                   WHERE emp_id = a.proceed_approved_by limit 1
                  ) AS proceed_approved_by_name
  FROM split_planning_qty  a
  LEFT JOIN product p ON a.product_code = p.product_code
  LEFT JOIN po_entry c ON a.order_no = c.order_no
  
  WHERE a.status='Work Order Processed'
  ORDER BY a.id ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
while ($row = $result->fetch_assoc()) {
    
   // Decode batches JSON if it exists
   if (!empty($row["batches"])) {
       $row["batches"] = json_decode($row["batches"], true);
   } else {
       $row["batches"] = array();
   }
   
   // Ensure plan_qty is set (use InQty if plan_qty is empty)
   if (empty($row["plan_qty"]) && !empty($row["InQty"])) {
       $row["plan_qty"] = $row["InQty"];
   }
   
   // Ensure planUnit is set (use unit if planUnit is empty)
   if (empty($row["planUnit"]) && !empty($row["unit"])) {
       $row["planUnit"] = $row["unit"];
   }
   
   // Use product_name from product table if available, otherwise use from split_planning_qty
   if (!empty($row["product_name_from_product"])) {
       $row["product_name"] = $row["product_name_from_product"];
   }
   
   // Set default values for missing fields
   if (empty($row["leftover"])) {
       $row["leftover"] = 0;
   }
   if (empty($row["excess"])) {
       $row["excess"] = 0;
   }
   if (empty($row["work_order_planned_qty"])) {
       $row["work_order_planned_qty"] = $row["plan_qty"];
   }
   
   // Ensure file path is set
   if (!empty($row["file"])) {
       $row["file"] = "upload/poentry/".$row["file"];
   } else {
       $row["file"] = "NA";
   }
   
   // Format planMonth if it's a date string
   if (!empty($row["planMonth"]) && strpos($row["planMonth"], '-') !== false) {
       // Already formatted
   } elseif (!empty($row["month"]) && !empty($row["year"])) {
       $row["planMonth"] = $row["month"] . "-" . $row["year"];
   }
   
   $output[] = $row;
}
}

echo json_encode($output);

    }
 
    else if ($_GET["type"] == "updatePendingPOs1") {
                $input = $_POST;

        
            $sql = "UPDATE po_entry SET status='".$_GET["status"]."',products = '".$input["products"]."', terms = '".$input["terms"]."' , approve_by='".$_GET["emp_id"]."',  remark='".$_GET["remark"]."', 
          approve_date='$entry_date'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
    	    echo "{\"status\":\"success\"}";
    	    
    	    
    	       $sql1 =   "delete from order_materials where order_no = '".$_GET["order_no"]."'";
    	      $conn->query($sql1);
    	    
    	    
    	    
    	    	$products = json_decode($input["products"], true);
        	for ($i = 0; $i < count($products); $i++) {
        	    $product = $products[$i];
         	    $sql2 = "INSERT INTO order_materials (plant_id,user_no,order_no, product_code, rate, currency, order_qty, unit, amount_inr, amount_usd,
        	    usd_rate, packing_configuration ,packing_style) VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$_GET["order_no"]."', '".$product["product_code"]."', 
        	    '".$product["rate"]."', '".$product["currency"]."', '".$product["qty"]."', '".$product["unit"]."', '".$product["amount_inr"]."',
        	    '".$product["amount_usd"]."', '".$product["currancy_rate"]."', '".$product["packing_configuration"]."' ,'".$product["packing_style"]."')";
        	    $conn->query($sql2);
        	}
    	     
        } else { 
            
           echo "{\"status\":\"".$conn->error."\"}";
           
        }
        
        
    }
    else if ($_GET["type"] == "receiveSo") {
         $flag = 0;
    for ($i = 0; $i < count($input); $i++) {
        $temp = $input[$i];
         $sql = "UPDATE po_entry SET status='Received', received_date='$entry_date',company_unit='".$temp["company_unit"]."',plan_no='01' WHERE order_no='".$temp["order_no"]."'";
        if ($conn->query($sql) === FALSE) {
            $flag = 1;
            break;
        }
    }
    if ($flag == 0) {
         $sql1 = "INSERT INTO consoladated(user_no,client_code,po_type, po_no, po_date,work_order_no, entry_by, entry_date,products) VALUES ('".$temp["user_no"]."','".$temp['client_code']."', '".$temp["po_type"]."', '".$temp['po_no']."', '".$temp['po_date']."','".$temp["order_no"]."','".$_GET["emp_id"]."', '$entry_date','".json_encode($temp["products"])."')";
            $conn->query($sql1);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 




else if ($_GET["type"] == "getPOsLog") {      
         
        $output = Array();
        // $sql = "SELECT p.*, c.TrdNm FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
      //  p.user_no='".$_GET["user_no"]."' AND p.status !='pending' ORDER BY p.id DESC";
        $sql = "SELECT q.*, c.TrdNm, c.address, c.state, c.city, c.pincode, c.country 
                FROM po_entry q 
                LEFT JOIN client c ON q.client_code = c.client_code 
                WHERE q.user_no = '" . $_GET["user_no"] . "' 
                  AND q.status != 'pending' 
                ORDER BY q.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row["terms"] = json_decode($row["terms"]);
                $output1 = array();
                 $sql1 = "SELECT o.order_no,o.product_code,o.order_qty,o.pack_size,o.details, p.product_name, 
                 p.product_type, p.grade FROM order_materials o LEFT JOIN product p ON
                 o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                          $row1["details"] = json_decode($row1["details"]);
                          $row1["pack_size"] = json_decode($row1["pack_size"]);
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
}

else if ($_GET["type"] == "getPOsLogForReqAnalysis") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.raw_materials,e.id as unit_formula_id FROM 
      order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
      LEFT JOIN client c ON b.client_code=c.client_code
        LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN 
            (
                SELECT 
                    product_code,
                    MAX(id) as max_id
                FROM 
                    unitformula
                GROUP BY 
                    product_code
            ) latest_unitformula ON a.product_code = latest_unitformula.product_code
        LEFT JOIN 
            unitformula e ON latest_unitformula.max_id = e.id
      WHERE a.reqStatus = 'Pending'   AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC ";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                 
                 $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $output1[] = $row1;
                    }
                    
                }
                                          $row["pack_size"] = json_decode($row["pack_size"]);

             
                    $row['packing_configuration'] =$output1;
                    $rawMaterials = json_decode($row["raw_materials"], true);
                    $row["raw_materials"] = $rawMaterials;
                    $output[] = $row;
                    
            
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getPOsLogForReqAnalysisSplit") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.raw_materials,e.id as unit_formula_id,a.order_qty as qty_to_prepare FROM 
      order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
      LEFT JOIN client c ON b.client_code=c.client_code
        LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN 
            (
                SELECT 
                    product_code,
                    MAX(id) as max_id
                FROM 
                    unitformula
                GROUP BY 
                    product_code
            ) latest_unitformula ON a.product_code = latest_unitformula.product_code
        LEFT JOIN 
            unitformula e ON latest_unitformula.max_id = e.id
      WHERE   a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC ";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                 
                 $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $output1[] = $row1;
                    }
                    
                }
                 $output2 = Array();
                 
                 $sql2 = "select *,a.oder_qty as Qty,a.balance_qty as bal_qty from split_planning_qty a where a.order_no='".$row["order_no"]."' AND a.product_code='".$row["product_code"]."' ";
               
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                        $output2[] = $row2;
                    }
                    
                }
                                          $row["pack_size"] = json_decode($row["pack_size"]);

             
                    $row['splits'] =$output2;
                    $row['packing_configuration'] =$output1;
                    $rawMaterials = json_decode($row["raw_materials"], true);
                    $row["raw_materials"] = $rawMaterials;
                    $output[] = $row;
                    
            
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getpoStatusLog") {
      
        $output = Array();
        
                   $sql = "SELECT a.id as pid, b.required_date, a.unit, a.order_no, c.TrdNm, b.po_no, b.po_type, b.valid_till,a.deliveryDate,b.status as orderStatus,
                        b.po_date, a.product_code, a.order_qty, a.pack_size, d.product_name, d.grade as product_grade 
                        FROM order_materials a 
                        LEFT JOIN po_entry b ON a.order_no = b.order_no 
                        LEFT JOIN client c ON b.client_code = c.client_code 
                        LEFT JOIN product d ON a.product_code = d.product_code  
                        where b.status='approve'
                        ORDER BY a.id DESC";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $output = array();
                    $current_date = new DateTime();
                    
                    while ($row = $result->fetch_assoc()) {
                        $required_date = new DateTime($row['deliveryDate']);
                        $remaining_days = $current_date->diff($required_date)->days;
                
                        // Determine if the required date is in the past
                        if ($required_date < $current_date) {
                            $remaining_days = -$remaining_days; // Make remaining days negative if the date is in the past
                        }
                
                        // Add remaining_days to the row
                        $row['rem_days'] = $remaining_days;
                
                        $output[] = $row;
                    }
                }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getCompleteReqAnalysis") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.order_no,b.file,c.TrdNm,b.po_no,b.valid_till,b.po_date,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.raw_materials,e.id as unit_formula_id FROM 
      order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
       LEFT JOIN client c ON b.client_code=c.client_code
        LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN unitformula e ON a.product_code=e.product_code
      WHERE a.reqStatus = 'Complete'  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                 
                 $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $output1[] = $row1;
                    }
                    
                }
                
              $row["pack_size"] = json_decode($row["pack_size"]);
                    $row['packing_configuration'] =$output1;
                    $rawMaterials = json_decode($row["raw_materials"], true);
                    $row["raw_materials"] = $rawMaterials;
                    $output[] = $row;
                    
            
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getInprocessReqAnalysis") {
      
        $output = Array();
        // Callers that omit &status= mean the in-process queue.
        $reqStatus = isset($_GET['status']) && trim($_GET['status']) !== ''
            ? mysqli_real_escape_string($conn, trim($_GET['status']))
            : 'Inprocess';
        
      $sql = "SELECT a.id as pid,a.unit as ord_unit,a.order_no,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.batch_size,d.genericProductCode,
      e.raw_materials,e.id as unit_formula_id,e.primary_pm_list,e.consumeableMaterial,
      (select po_no from po_entry po where po.order_no=a.order_no limit 1) as po_no,
      (select sum(oder_qty) from split_planning_qty sp where sp.order_no=a.order_no limit 1) as order_qty
      FROM 
      order_materials a LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN unitformula e ON a.product_code=e.product_code
      WHERE a.reqStatus = '".$reqStatus."' AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                
                
                   $sql00 = "SELECT batch_formula_weight FROM `batch_formula_info` WHERE product_code='".$row["product_code"]."' ORDER by batch_formula_weight desc limit 1; ";
                $result00 = $conn->query($sql00);
                if ($result00->num_rows > 0) {
                    while ($row00 = $result00->fetch_assoc()) {
                       
                         $row["batch_formula_weight"] = number_format((float)$row00["batch_formula_weight"], 2, '.', '');
                      
                    }
                }
                    
                   
                
                
                
                
                
                
                
                 $output1 = Array();
                                    $pack_size = json_decode($row["pack_size"]);

                  $row["pack_size"] = json_decode($row["pack_size"]);
                 
                  $sql1 = "SELECT a.*,a.unit_name as required_qty_unit ,m.inventory as min_inventory ,uom, m.order_qty as min_order_qty, 
                  a.total_qty as required_qty,'Packing Material' as material_type, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id  left join material m ON m.material_code = a.material_code where b.unit_formula_id ='".$row["unit_formula_id"]."' 
                AND b.pack_size = '" . $pack_size->pack_size . "' AND b.unit = '" . $pack_size->unit . "'";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                      $row1["batch_formula_weight"]=$row["batch_formula_weight"];
                      $row1["unitF_batch_size"]=$row["batch_size"];
                        
                        
                        
                         

                $sql11 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$row1["material_code"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        
                         $row1["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                         
                    }
                }
                
                 $sql12 = "SELECT  IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code= '".$row1["material_code"]."'";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                       
                         $row1["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                      
                    }
                }
                
                 $sql13 = "SELECT  IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE
                 material_code= '".$row["material_code"]."'";
                 
                $result13 = $conn->query($sql13);
                if ($result13->num_rows > 0) {
                    while ($row13 = $result13->fetch_assoc()) {
                        
                         $row1["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                      
                    }
                }
                
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE  status = 'Rejected' AND
                material_code= '".$row1["material_code"]."'";
                
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row1["totalRejectdQty"] = number_format((float)$row132["totalRejectdQty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(expiredQty), 0) as totalExpiredQty   FROM stock_book WHERE  status = 'Expired' 
                AND material_code= '".$row1["material_code"]."'";
                
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row1["totalExpiredQty"] = number_format((float)$row132["totalExpiredQty"], 2, '.', '');
                          
                    }
                }
                
                 $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row1["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $row1["unit"] = $row14["unit"];
                    }
                }
 
                
                $row1["balance_qty"] = number_format(max(0, $row1["received_qty"]- ( $row1["dispensing_qty"] + $row1["undertest_qty"] + $row1["totalRejectdQty"] +  $row1["totalExpiredQty"])), 3, '.', '');
 
                        $output1[] = $row1;
                    }
                    
                }
                
              $output2 = Array();
                 
                 $sql2 = "select *,a.oder_qty as Qty,a.balance_qty as bal_qty from split_planning_qty a where a.order_no='".$row["order_no"]."' 
                 AND a.product_code='".$row["product_code"]."' ";
               
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                     $row2['number_of_batches'] = ceil(floatval($row2['InQty']) / floatval($row['batch_formula_weight']));



                        $output2[] = $row2;
                    }
                    
                } 
                
                
                
                    $row['packing_configuration'] =$output1;
                    $primary_pm_list = json_decode($row["primary_pm_list"], true);
                    $consumeableMaterial = json_decode($row["consumeableMaterial"], true);
                    $rawMaterials = json_decode($row["raw_materials"], true);
                       $row["splits"] = $output2;
                    
                    
                    
                foreach ($rawMaterials as &$rawMat) {
                    
                    
                        
                        $rawMat["order_material_id"] =  $row['pid'];
                    
                      $rawMat["batch_formula_weight"]=$row["batch_formula_weight"];
                      $rawMat["unitF_batch_size"]=$row["batch_size"];
                      
                      
                    // Fetch dispensing_qty
                    $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$rawMat["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $rawMat["totalDays"] =  $row11["total_days"] ;
                        }
                    }
                  
                    else { $rawMat["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$rawMat["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $rawMat["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $rawMat["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $rawMat["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $rawMat["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $rawMat["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $rawMat["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$rawMat["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $rawMat["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $rawMat["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$rawMat["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $rawMat["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $rawMat["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$rawMat["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $rawMat["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$rawMat["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $rawMat["min_inventory"] = $row14["min_inventory"];
                          $rawMat["min_order_qty"] = $row14["min_order_qty"];
                          $rawMat["uom"] = $row14["uom"];
                          $rawMat["moisture"] = $row14["moisture"];
                    }
                }
                    // Calculate balance_qty
                    $rawMat["balance_qty"] = number_format( max(0, $rawMat["received_qty"] - ( $rawMat["dispensing_qty"] + $rawMat["undertest_qty"] + $rawMat["totalRejectdQty"] + $rawMat["totalExpiredQty"] )), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $rawMat["qty_overages_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
$converted_balance =  $rawMat["balance_qty"];
 

if (strtolower($rawMat["stock_unit"]) == "ml") {
    // ml → L
    $converted_balance = $converted_balance / 1000;
    
} elseif (strtolower($rawMat["stock_unit"]) == "mg" || strtolower($rawMat["stock_unit"]) == "gm" || strtolower($rawMat["stock_unit"]) == "g" || $rawMat["stock_unit"] == "GM") {
    // mg / gm → Kg
    if (strtolower($rawMat["stock_unit"]) == "mg") {
        $converted_balance = $converted_balance / 1000000; // mg → Kg
    } else {
        $converted_balance = $converted_balance / 1000; // gm → Kg
    }
     
}

// Store final values
 $rawMat["balance_qty"] = number_format($converted_balance, 3, '.', '');
 
     
     
               
 
 $required_qty_unit = 'Kg';
 
                if ($rawMat["unit"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($rawMat["unit"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($rawMat["unit"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($rawMat["unit"] == 'ml'){
                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }
               
              else if($rawMat["unit"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
               
               
               
                $rawMat["required_qty"] =  number_format($finQty , 4, '.', '');
                $rawMat["required_qty_unit"] =  $required_qty_unit;
                
                
                
                
                
                //$rawMat["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $rawMat["material_type"] = 'Raw Material';
               
               
                }
                 foreach ($primary_pm_list as &$pr_pm) {
                         $pr_pm["order_material_id"] =  $row['pid'];
                     $pr_pm["batch_formula_weight"]=$row["batch_formula_weight"];
                      $pr_pm["unitF_batch_size"]=$row["batch_size"];
                     
                            $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $pr_pm["totalDays"] =  $pr_pm["total_days"] ;
                        }
                    } else { $pr_pm["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $pr_pm["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $pr_pm["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $pr_pm["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $pr_pm["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $pr_pm["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $pr_pm["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$pr_pm["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $pr_pm["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $pr_pm["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$pr_pm["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $pr_pm["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $pr_pm["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$pr_pm["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $pr_pm["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$pr_pm["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $pr_pm["min_inventory"] = $row14["min_inventory"];
                          $pr_pm["min_order_qty"] = $row14["min_order_qty"];
                          $pr_pm["uom"] = $row14["uom"];
                          $pr_pm["moisture"] = $row14["moisture"];
                    }
                }
                    // Calculate balance_qty
                    $pr_pm["balance_qty"] = number_format( max(0, $pr_pm["received_qty"] - ( $pr_pm["dispensing_qty"] + $pr_pm["undertest_qty"] + $pr_pm["totalRejectdQty"] + $pr_pm["totalExpiredQty"] )), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $pr_pm["total_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
     
               
 
             $required_qty_unit = 'Kg';
 
                if ($pr_pm["unit"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($pr_pm["unit"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($pr_pm["unit"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($pr_pm["unit"] == 'ml'){                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }

               
              else if($pr_pm["unit"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
              else{
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = $pr_pm["unit"];

              }
               
               
               
                $pr_pm["required_qty"] =  number_format($finQty , 4, '.', '');
                $pr_pm["required_qty_unit"] =  $required_qty_unit;
                
                
                
                
                
                //$pr_pm["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $pr_pm["material_type"] = 'Primary Packing Material';
               
               
                }
                 foreach ($consumeableMaterial as &$cons) {
                      $cons["batch_formula_weight"]=$row["batch_formula_weight"];
                      $cons["unitF_batch_size"]=$row["batch_size"];
                     
                            $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$cons["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $cons["totalDays"] =  $cons["total_days"] ;
                        }
                    } else { $cons["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$cons["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $cons["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $cons["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$cons["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $cons["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $cons["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$cons["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $cons["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $cons["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$cons["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $cons["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $cons["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$cons["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $cons["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $cons["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$cons["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $cons["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$cons["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $cons["min_inventory"] = $row14["min_inventory"];
                          $cons["min_order_qty"] = $row14["min_order_qty"];
                          $cons["uom"] = $row14["uom"];
                          $cons["moisture"] = $row14["moisture"];
                    }
                }
                
                    // Calculate balance_qty
                    $cons["balance_qty"] = number_format( max(0, $cons["received_qty"] - ( $cons["dispensing_qty"] + $cons["undertest_qty"] + $cons["totalRejectdQty"] + $cons["totalExpiredQty"] )), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $cons["total_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
     
               
 
             $required_qty_unit = 'Kg';
 
                if ($cons["unit_name"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($cons["unit_name"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($cons["unit_name"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($cons["unit_name"] == 'ml'){                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }

               
              else if($cons["unit_name"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
              else{
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = $cons["unit_name"];

              }
               
               
               
                $cons["required_qty"] =  number_format($finQty , 4, '.', '');
                $cons["required_qty_unit"] =  $required_qty_unit;
                $cons["order_material_id"] =  $row['pid'];
                
                
                
                
                
                //$cons["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $cons["material_type"] = 'Consumeable Material';
               
               
                }
                    
                    
                    
                     
                    $row["primary_pm_list"] = $primary_pm_list;
                    $row["consumeableMaterial"] = $consumeableMaterial;
                    $row["raw_materials"] = $rawMaterials;
                 
                    $output[] = $row;
//                     if (
//     !empty($row['raw_materials']) && is_array($row['raw_materials']) &&
//     !empty($row['packing_configuration']) && is_array($row['packing_configuration'])
// ) {
//     $output[] = $row;
// }

                    
            
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getInprocessReqAnalysisForIndnedn") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.send_on,a.unit as ord_unit,a.order_no,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.batch_size,d.genericProductCode,
      e.raw_materials,e.id as unit_formula_id,e.primary_pm_list,e.consumeableMaterial,
      (select po_no from po_entry po where po.order_no=a.order_no limit 1) as po_no,
      (select sum(oder_qty) from split_planning_qty sp where sp.order_no=a.order_no limit 1) as order_qty
      FROM 
      order_materials a LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN unitformula e ON a.product_code=e.product_code
      WHERE a.reqStatus = '".$_GET['status']."' AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                
                
                   $sql00 = "SELECT batch_formula_weight FROM `batch_formula_info` WHERE product_code='".$row["product_code"]."' ORDER by batch_formula_weight desc limit 1; ";
                $result00 = $conn->query($sql00);
                if ($result00->num_rows > 0) {
                    while ($row00 = $result00->fetch_assoc()) {
                       
                         $row["batch_formula_weight"] = number_format((float)$row00["batch_formula_weight"], 2, '.', '');
                      
                    }
                }
                    
                   
                
                
                
                
                
                
                
                 $output1 = Array();
                                    $pack_size = json_decode($row["pack_size"]);

                  $row["pack_size"] = json_decode($row["pack_size"]);
                 
                  $sql1 = "SELECT a.*,a.unit_name as required_qty_unit ,m.inventory as min_inventory ,uom, m.order_qty as min_order_qty, 
                  a.total_qty as required_qty,'Packing Material' as material_type, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id  left join material m ON m.material_code = a.material_code where b.unit_formula_id ='".$row["unit_formula_id"]."' 
                AND b.pack_size = '" . $pack_size->pack_size . "' AND b.unit = '" . $pack_size->unit . "'";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                      $row1["batch_formula_weight"]=$row["batch_formula_weight"];
                      $row1["unitF_batch_size"]=$row["batch_size"];
                        
                        
                        
                         

                $sql11 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$row1["material_code"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        
                         $row1["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                         
                    }
                }
                
                 $sql12 = "SELECT  IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code= '".$row1["material_code"]."'";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                       
                         $row1["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                      
                    }
                }
                
                 $sql13 = "SELECT  IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE
                 material_code= '".$row["material_code"]."'";
                 
                $result13 = $conn->query($sql13);
                if ($result13->num_rows > 0) {
                    while ($row13 = $result13->fetch_assoc()) {
                        
                         $row1["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                      
                    }
                }
                
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE  status = 'Rejected' AND
                material_code= '".$row1["material_code"]."'";
                
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row1["totalRejectdQty"] = number_format((float)$row132["totalRejectdQty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(expiredQty), 0) as totalExpiredQty   FROM stock_book WHERE  status = 'Expired' 
                AND material_code= '".$row1["material_code"]."'";
                
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row1["totalExpiredQty"] = number_format((float)$row132["totalExpiredQty"], 2, '.', '');
                          
                    }
                }
                
                 $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row1["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $row1["unit"] = $row14["unit"];
                    }
                }
 
                
                $row1["balance_qty"] = number_format(max(0, $row1["received_qty"]- ( $row1["dispensing_qty"] + $row1["undertest_qty"] + $row1["totalRejectdQty"] +  $row1["totalExpiredQty"])), 3, '.', '');
 
                        $output1[] = $row1;
                    }
                    
                }
                
              $output2 = Array();
                 
                 $sql2 = "select *,a.oder_qty as Qty,a.balance_qty as bal_qty from split_planning_qty a where a.order_no='".$row["order_no"]."' 
                 AND a.product_code='".$row["product_code"]."' ";
               
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                     $row2['number_of_batches'] = ceil(floatval($row2['InQty']) / floatval($row['batch_formula_weight']));



                        $output2[] = $row2;
                    }
                    
                } 
                
                
                
                    $row['packing_configuration'] =$output1;
                    $primary_pm_list = json_decode($row["primary_pm_list"], true);
                    $consumeableMaterial = json_decode($row["consumeableMaterial"], true);
                    $rawMaterials = json_decode($row["raw_materials"], true);
                       $row["splits"] = $output2;
                    
                    
                    
                foreach ($rawMaterials as &$rawMat) {
                    
                    
                        
                        $rawMat["order_material_id"] =  $row['pid'];
                    
                      $rawMat["batch_formula_weight"]=$row["batch_formula_weight"];
                      $rawMat["unitF_batch_size"]=$row["batch_size"];
                      
                      
                    // Fetch dispensing_qty
                    $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$rawMat["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $rawMat["totalDays"] =  $row11["total_days"] ;
                        }
                    }
                  
                    else { $rawMat["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$rawMat["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $rawMat["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $rawMat["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $rawMat["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $rawMat["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $rawMat["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $rawMat["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$rawMat["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $rawMat["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $rawMat["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$rawMat["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $rawMat["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $rawMat["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$rawMat["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $rawMat["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$rawMat["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $rawMat["min_inventory"] = $row14["min_inventory"];
                          $rawMat["min_order_qty"] = $row14["min_order_qty"];
                          $rawMat["uom"] = $row14["uom"];
                          $rawMat["moisture"] = $row14["moisture"];
                    }
                }
                    // Calculate balance_qty
                    $rawMat["balance_qty"] = number_format( max(0, $rawMat["received_qty"] - ( $rawMat["dispensing_qty"] + $rawMat["undertest_qty"] + $rawMat["totalRejectdQty"] + $rawMat["totalExpiredQty"] )), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $rawMat["qty_overages_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
$converted_balance =  $rawMat["balance_qty"];
 

if (strtolower($rawMat["stock_unit"]) == "ml") {
    // ml → L
    $converted_balance = $converted_balance / 1000;
    
} elseif (strtolower($rawMat["stock_unit"]) == "mg" || strtolower($rawMat["stock_unit"]) == "gm" || strtolower($rawMat["stock_unit"]) == "g" || $rawMat["stock_unit"] == "GM") {
    // mg / gm → Kg
    if (strtolower($rawMat["stock_unit"]) == "mg") {
        $converted_balance = $converted_balance / 1000000; // mg → Kg
    } else {
        $converted_balance = $converted_balance / 1000; // gm → Kg
    }
     
}

// Store final values
 $rawMat["balance_qty"] = number_format($converted_balance, 3, '.', '');
 
     
     
               
 
 $required_qty_unit = 'Kg';
 
                if ($rawMat["unit"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($rawMat["unit"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($rawMat["unit"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($rawMat["unit"] == 'ml'){
                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }
               
              else if($rawMat["unit"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
               
               
               
                $rawMat["required_qty"] =  number_format($finQty , 4, '.', '');
                $rawMat["required_qty_unit"] =  $required_qty_unit;
                
                
                
                
                
                //$rawMat["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $rawMat["material_type"] = 'Raw Material';
               
               
                }
                 foreach ($primary_pm_list as &$pr_pm) {
                         $pr_pm["order_material_id"] =  $row['pid'];
                     $pr_pm["batch_formula_weight"]=$row["batch_formula_weight"];
                      $pr_pm["unitF_batch_size"]=$row["batch_size"];
                     
                            $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $pr_pm["totalDays"] =  $pr_pm["total_days"] ;
                        }
                    } else { $pr_pm["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $pr_pm["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $pr_pm["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $pr_pm["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $pr_pm["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$pr_pm["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $pr_pm["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $pr_pm["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$pr_pm["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $pr_pm["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $pr_pm["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$pr_pm["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $pr_pm["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $pr_pm["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$pr_pm["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $pr_pm["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$pr_pm["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $pr_pm["min_inventory"] = $row14["min_inventory"];
                          $pr_pm["min_order_qty"] = $row14["min_order_qty"];
                          $pr_pm["uom"] = $row14["uom"];
                          $pr_pm["moisture"] = $row14["moisture"];
                    }
                }
                    // Calculate balance_qty
                    $pr_pm["balance_qty"] = number_format( max(0, $pr_pm["received_qty"] - ( $pr_pm["dispensing_qty"] + $pr_pm["undertest_qty"] + $pr_pm["totalRejectdQty"] + $pr_pm["totalExpiredQty"] )), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $pr_pm["total_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
     
               
 
             $required_qty_unit = 'Kg';
 
                if ($pr_pm["unit"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($pr_pm["unit"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($pr_pm["unit"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($pr_pm["unit"] == 'ml'){                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }

               
              else if($pr_pm["unit"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
              else{
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = $pr_pm["unit"];

              }
               
               
               
                $pr_pm["required_qty"] =  number_format($finQty , 4, '.', '');
                $pr_pm["required_qty_unit"] =  $required_qty_unit;
                
                
                
                
                
                //$pr_pm["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $pr_pm["material_type"] = 'Primary Packing Material';
               
               
                }
                 foreach ($consumeableMaterial as &$cons) {
                      $cons["batch_formula_weight"]=$row["batch_formula_weight"];
                      $cons["unitF_batch_size"]=$row["batch_size"];
                     
                            $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$cons["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $cons["totalDays"] =  $cons["total_days"] ;
                        }
                    } else { $cons["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$cons["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $cons["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $cons["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$cons["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $cons["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $cons["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$cons["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $cons["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $cons["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$cons["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $cons["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $cons["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$cons["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $cons["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $cons["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$cons["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $cons["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$cons["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $cons["min_inventory"] = $row14["min_inventory"];
                          $cons["min_order_qty"] = $row14["min_order_qty"];
                          $cons["uom"] = $row14["uom"];
                          $cons["moisture"] = $row14["moisture"];
                    }
                }
                
                    // Calculate balance_qty
                    $cons["balance_qty"] = number_format( max(0, $cons["received_qty"] - ( $cons["dispensing_qty"] + $cons["undertest_qty"] + $cons["totalRejectdQty"] + $cons["totalExpiredQty"] )), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $cons["total_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
     
               
 
             $required_qty_unit = 'Kg';
 
                if ($cons["unit_name"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($cons["unit_name"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($cons["unit_name"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($cons["unit_name"] == 'ml'){                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }

               
              else if($cons["unit_name"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
              else{
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = $cons["unit_name"];

              }
               
               
               
                $cons["required_qty"] =  number_format($finQty , 4, '.', '');
                $cons["required_qty_unit"] =  $required_qty_unit;
                $cons["order_material_id"] =  $row['pid'];
                
                
                
                
                
                //$cons["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $cons["material_type"] = 'Consumeable Material';
               
               
                }
                    
                    
                    
                     
                    $row["primary_pm_list"] = $primary_pm_list;
                    $row["consumeableMaterial"] = $consumeableMaterial;
                    $row["raw_materials"] = $rawMaterials;
                 
                    $output[] = $row;
//                     if (
//     !empty($row['raw_materials']) && is_array($row['raw_materials']) &&
//     !empty($row['packing_configuration']) && is_array($row['packing_configuration'])
// ) {
//     $output[] = $row;
// }

                    
            
            }
 

// Reindex to numeric array
 
        }
        echo json_encode($output);
    }
// else if ($_GET["type"] == "getInprocessReqAnalysis") {
    
//     // Loop over materials
// foreach ($materials as $mKey => $material) {
//     $material_code = $conn->real_escape_string($material['material_code']);
//     $material_name = $conn->real_escape_string($material['material_name']);
//     $material_type = $conn->real_escape_string($material['material_type']);

//     if (isset($material['splistCal']) && is_array($material['splistCal'])) {
//         foreach ($material['splistCal'] as $sKey => $splist) {
//             $year = $conn->real_escape_string($splist['year']);
//             $month = $conn->real_escape_string($splist['month']);

//             // ---- Stock Calculations ----
//             $sql_dispense = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code='$material_code'";
//             $row1 = $conn->query($sql_dispense)->fetch_assoc();
//             $dispensing_qty = (float)$row1['dispensing_qty'];

//           echo  $sql_received = "SELECT IFNULL(SUM(qty), 0) as received_qty, IFNULL(SUM(undertest_qty),0) as undertest_qty, 
//                                     IFNULL(SUM(CASE WHEN status='Rejected' THEN qty ELSE 0 END),0) as totalRejectedQty,
//                                     IFNULL(SUM(CASE WHEN status='Expired' THEN expiredQty ELSE 0 END),0) as totalExpiredQty
//                              FROM stock_book WHERE material_code='$material_code'";
//             $row2 = $conn->query($sql_received)->fetch_assoc();

//             $received_qty = (float)$row2['received_qty'];
//             $undertest_qty = (float)$row2['undertest_qty'];
//             $totalRejectedQty = (float)$row2['totalRejectedQty'];
//             $totalExpiredQty = (float)$row2['totalExpiredQty'];

//             // ---- Calculate balance quantity ----
//             $balance_qty = $received_qty - ($dispensing_qty + $undertest_qty + $totalRejectedQty + $totalExpiredQty);
//             $balance_qty = max(0, $balance_qty);

//             // ---- Ordered and booked quantities from MRP table ----
//             $sql_mrp = "SELECT IFNULL(SUM(ordered_qty),0) as ordered_qty,
//                               IFNULL((SELECT SUM(ordered_qty) 
//                                       FROM mrp_raised_indnd_qty b 
//                                       WHERE b.material_code=a.material_code 
//                                          AND (status='indend Created' OR status='Approve')),0) as booked_qty
//                         FROM mrp_raised_indnd_qty a
//                         WHERE a.year='$year'
//                           AND a.month='$month'
//                           AND a.material_type='$material_type'
//                           AND a.material_name='$material_name'
//                           AND a.material_code='$material_code'";
//             $row3 = $conn->query($sql_mrp)->fetch_assoc();

//             $ordered_qty = isset($row3['ordered_qty']) ? (float)$row3['ordered_qty'] : 0;
//             $booked_qty = isset($row3['booked_qty']) ? (float)$row3['booked_qty'] : 0;

//             // ---- Update calculated value ----
//             $calculatedValue = (float)$materials[$mKey]['splistCal'][$sKey]['calculatedValue'];
//             $newCalculatedValue = $calculatedValue - $ordered_qty;

//             // ---- Push results to array ----
//             $materials[$mKey]['splistCal'][$sKey]['calculatedValue'] = $newCalculatedValue;
//             $materials[$mKey]['splistCal'][$sKey]['found'] = 1;
//             $materials[$mKey]['booked_qty'] = $booked_qty;
//             $materials[$mKey]['balance_qty'] = $balance_qty;
//         }
//     }
// }

// // Final output
// echo json_encode($materials);

// }
else if ($_GET["type"] == "AcceptReqAnalysis") {
      
      $status1 = false;
      
          $json_obj = json_encode($input["filtersFO"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                $sql = "UPDATE order_materials  SET reqStatus = 'Inprocess' where id = '".$values['pid']."' ";
                if ($conn->query($sql)) {
                     $status1 = true;
                } else {
                    $status1 = false;
                }
                    
                }
        
        if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
        
        
        
    }




else if ($_GET["type"] == "getSalesOrder") {
        $output = Array();
        // $sql = "SELECT p.*, c.company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.client_code LIKE '%".$_GET["client_code"]."%' AND p.status LIKE '%".$_GET["status"]."%' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id DESC";
                $sql = "SELECT p.*, c.TrdNm as company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.status='approve' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/po_entry/".$row["file"];
                 $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                
                
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name, p.product_type FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getSalesOrderLog") {
        $output = Array();
         $sql = "SELECT p.*, c.TrdNm as company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE 
         p.plant_id='".$_GET["plant_id"]."'  ORDER BY p.id DESC";
               
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name,p.dosage_form,p.grade, p.product_type FROM order_materials o 
                LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getMarketingPoPlan") {
        $output1 = Array();
         $sql1 = "SELECT pe.po_type as plan_for_market,o.order_no,o.order_qty,o.id as pid,p.dosage_form,p.product_code,p.product_type,p.product_name,p.grade,p.pack_sizes FROM order_materials o 
         left join product p ON p.product_code = o.product_code 
         left join po_entry pe ON pe.order_no = o.order_no
         where o.plant_id='".$_GET["plant_id"]."'  ORDER BY o.id DESC";
               
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
               
    
                 $output3 = Array();
                 $sql3="Select id,mfr_no,batch_size from unitformula where product_code = 
                 '".$row1["product_code"]."'    ";
                
                 $result3 = $conn->query($sql3);
                 if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output4 = Array();
                        $sql4="Select id,mfr_no,bfr_no,batch_formula_weight,raw_materials,packing_materials 
                        from batch_formula_info  where mfr_no = '".$row3["mfr_no"]."' and status='Approve' ";
                        $result4 = $conn->query($sql4);
                              if ($result4->num_rows > 0) {
                                    while ($row4 = $result4->fetch_assoc()) {
                                         $output4[]=$row4;   
                                    }
                              }
                            $row3["bfr_records"] = $output4;
                            $output3[]=$row3;   
                    }
                     
                 }
                 
                 $row1["mfr_records"] = $output3;
               $output1[] = $row1;
            }
        }
        echo json_encode($output1);
    }
    
    
    
    
    
    else if ($_GET["type"] == "getRejectedPOs") {
        $output = Array();
        $sql = "SELECT p.*, c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code 
        WHERE p.status = 'reject' ";//ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
            
                             $output1 = array();
                 $sql1 = "SELECT o.*, p.product_name, p.product_code, p.grade FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getOrdersChart") {
        $months = array();
        $months[] = date("Y-m");
        for ($i = 1; $i < 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        $months = array_reverse($months);
        
        $series = array();
        $labels = array();
        for ($i = 0; $i < count($months); $i++) {
            $month = $months[$i];
            $temp = explode("-",$month);
            $sql = "SELECT count(id) as id FROM po_entry WHERE user_no='".$_GET["user_no"]."' AND MONTH(entry_date) = '".$temp[1]."' AND YEAR(entry_date) = '".$temp[0]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $labels[] = $month;
                    $series[] = +$row["id"];
                    break;
                }
            } else {
                $labels[] = $month;
                $series[] = 0;
            }
        }
        
        $orders = 0;
        $sql1 = "SELECT COUNT(id) as total_orders FROM order WHERE user_no='".$_GET["user_no"]."' AND MONTH(entry_date)=MONTH(CURRENT_DATE()) AND YEAR(entry_date) = YEAR(CURRENT_DATE())";
        $result = $conn->query($sql1);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders = $row["total_orders"];
            }
        } else {
            $orders = 0;
        }
        
        $result = array();
        $result["series"] = $series;
        $result["labels"] = $labels;
        $result["orders"] = $orders;
        
        echo json_encode($result);
    }
   
     else if ($_GET["type"] == "downloadPO") {
        $_GET['filename'] = 'MEDIA STOCK REGISTER'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Received Purchase Order Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;"> Sr.</td>
                    <td style="width:20%;">	Order No</td>
                    <td style="width:20%;">	Client Name</td>
                    <td style="width:10%;">	Po No</td>
                    <td style="width:10%;">	Po Date</td>
                    <td style="width:15%;">	Valid till</td>
                    <td style="width:15%;">Status</td>
                </tr>
            </thead>';
            $i=1;
          $sql = "SELECT p.*, c.c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status !='pending' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/po_entry/".$row["file"];
                $row["products"] = json_decode($row["products"]);
                $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i++.'.</td>
                         <td style="width: 20%;">'.$row['order_no'].'</td>
                          <td style="width: 20%;">'.$row['c_name'].'</td>
                        <td style="width: 10%;">'.$row['po_no'].'</td>
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row['valid_till'])).'</td>
                        <td style="width: 15%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
     }
    else if($_GET["type"] == "receivedPOpdf") {
        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] ='onlyheader'; include("../pdfimp2.php");
        $sql = "SELECT p.*, c.company, c.address, c.phone, c.email, c.gst_no FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/poentry/".$row["file"];
                $row["products"] = json_decode($row["products"]);
                $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                $html.='
                <table>
                <h2 style="text-align:center">Purchase Order</h2>
            <tr>
                <td>
                    Vendor: '.$row['company'].'<br>
                    Address: '.$row['address'].'<br>
                    Mobile No: '.$row['phone'].'
                    &nbsp;&nbsp;&nbsp; Fax: 0 
                    &nbsp;&nbsp;&nbsp; Email: '.$row['email'].'<br>
                    GSTIN No: '.$row['gst_no'].'
                </td>
                <td>
                    PO No,: '.$row['po_no'].'<br>
                    PO Date: '.$row['po_date'].'
                </td>
            </tr>
        </table>
        <table cellpadding="5">
            <tr style="text-align:center; font-weight:bold;">
                <td rowspan="2" style="border:solid 1px BCBBBA;">Item Code</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Item</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Order Quantity (No.)</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Rate (INR)</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Amount (withoput tax)</td>
                <td colspan="3" style="border:solid 1px BCBBBA;">GST(%)</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Total Amount</td>
            </tr>
            <tr style="text-align:center; font-weight:bold;">
                <td style="border:solid 1px BCBBBA;">SGST ()</td>
                <td style="border:solid 1px BCBBBA;">CGST ()</td>
                <td style="border:solid 1px BCBBBA;">IGST ()</td>
            </tr>';
            
            $total = 0;
            $products = $row["products"];
                for ($i = 0; $i < count($products); $i++) {
                    $product = $products[$i];
                    $sql1 = "SELECT * FROM product WHERE product_code='".$product->product_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $product->product_name = $row1["product_name"];
                            $product->dosage_form = $row1["dosage_form"];
                            $product->grade = $row1["grade"];
                        }
                    }
                    $products[$i] = $product;
               
                    $html.='
                    <tr>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->product_code.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->product_name.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->qty.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->rate.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->amount.'</td>
                        <td style="border:solid 1px BCBBBA;"></td>
                        <td style="border:solid 1px BCBBBA;"></td>
                        <td style="border:solid 1px BCBBBA;"></td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->amount.'</td>
                    </tr>
                    ';
                    $total += +$products[$i]->amount;
                }
            $html.='
            <tr>
                <td style="border:solid 1px BCBBBA;" colspan="7"></td>
                <td style="border:solid 1px BCBBBA;">Total</td>
                <td style="border:solid 1px BCBBBA;">'.round($total, 2).'</td>
            </tr>
        </table>
        <table cellpadding="5">
            <tr>
                <td></td>
            </tr>
            <tr>
                <td>(Signature of Authorised Signatory)<br>Seal and Stamp<br></td>
            </tr>
            <tr>
                <td>
                 Copy to: 
                 1. Supplier copy<br>2. Acceptance copy by Supplier<br>3. Store copy<br>4. Accouts copy<br>5. Master copy
                </td>
            </tr>
        </table>
                ';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PO.pdf', 'I');
    } else if ($_GET["type"] == "getPODetails") {    
        
        $output = Array();
        $sql = "SELECT p.*, c.c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.id = '".$_GET["id"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["terms"] = json_decode($row["terms"]);

                $output1 = array();
                 $sql1 = "SELECT o.*, p.product_name, p.product_type, p.grade FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else if ($_GET["type"] == "editPO") {
        // $sql = "UPDATE po_entry SET status='amend',products='".json_encode($input["products"])."' WHERE id='".$_GET["id"]."'";
         $sql = "UPDATE po_entry SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' , company_unit = '".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadLog") {
        $_GET['filename'] = 'Received Purchase Order'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        
            $i=1;
               $sql = "SELECT p.*, c.c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status !='pending' and  p.po_no='".$_GET["po_no"]."' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html= "";
        
        $html.='
        <h2 style="text-align:center">Received Purchase Order</h2>
        <table cellpadding="5" border="1">
       

                   <tr>
                        <td style="width:30%; text-align:centre;"><b>Client Name:</b></td>
                        <td style="width:70%;">'.$row['c_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>PO No.:</b></td>
                        <td style="width:70%;">'.$row['po_no'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>PO Date:</b></td>
                        <td style="width:70%;">'.$row['po_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>PO Type:</b></td>
                        <td style="width:70%;">'.$row['po_type'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>Valid Till:</b></td>
                        <td style="width:70%;">'.$row['valid_till'].'</td>
                    </tr>
                </table>
                <h3>Products:</h3>
                <table cellpadding="5" border="1">
                    <tr>
                        <td style="width:5%; text-align:centre;"><b>Sr</b></td>
                        <td style="width:10%; text-align:centre;"><b>Dosage Form</b></td>
                        <td style="width:10%; text-align:centre;"><b>Product Name</b></td>
                        <td style="width:10%; text-align:centre;"><b>Grade</b></td>
                        <td style="width:10%; text-align:centre;"><b>Packing Style</b></td>
                        <td style="width:10%; text-align:centre;"><b>Rate</b></td>
                        <td style="width:5%; text-align:centre;"><b>Qty</b></td>
                        <td style="width:20%; text-align:centre;"><b>Packing Configuration</b></td>
                        <td style="width:10%; text-align:centre;"><b>Amount INR</b></td>
                        <td style="width:10%; text-align:centre;"><b>Amount USD</b></td>
                    </tr>';
                    
                  $json_obj = $row['products'];
$array = json_decode($json_obj, true);
$i = 1;

foreach ($array as $values) {
    $html .= '
        
        <tr>
            <td>' . $i . '</td>
            <td>' . $values['dosage_form'] . '</td>
            <td>' . $values['product_code'] . '</td>
            <td>' . $values['product_name'] . '</td>
            <td>' . $values['grade'] . '</td>
            <td>' . $values['packing_style'] . '</td>
            <td>' . $values['rate'] . '</td>
            <td>' . $values['order_qty'] . $values['unit'] . '</td>
            <td>' . $values['packing_configuration'] . '</td>
            <td>' . $values['amount_usd'] . '</td>
            <td>' . $values['amount_inr'] . '</td>
        </tr>';
    $i++;
}

$html .= '
    </table>
    <h3>Payment Terms & Conditions:</h3>
    <table cellpadding="5" border="1">
        <tr>
            <td style="width:30%; text-align:center;"><b>Sr</b></td>
            <td style="width:70%; text-align:center;"><b>Payment Terms</b></td>
        </tr>';

$json_obj = $row['terms'];
$array = json_decode($json_obj, true);
$i = 1;

foreach ($array as $values) {
    $html .= '
     
            <tr>
                        <td style="width:30%; text-align:center;">' . $i . '</td>
                        <td style="width:70%;">' . $values['term'] . '</td>
                    </tr> 
        
        
        ';
    $i++;
}

$html .= '
    </table>';
                
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Received Purchase Order.pdf', 'I');
     }
     else if ($_GET["type"] == "getCanPlannedWO") {
        medicap_require_helper('mrp_wo_schema_helpers.php');
        if (function_exists('ensureWoVerificationColumns')) {
            ensureWoVerificationColumns($conn);
        }
        medicap_require_helper('can_planned_wo_helpers.php');
        gw_json_response(gw_get_can_planned_wo($conn, $_GET));
    }
    else if ($_GET["type"] == "getWorkOrderPlanStatus") {
        // Real-time status of every generated WO grouped by plan.
        gw_json_response(gw_get_wo_plan_status($conn, $_GET['plant_id'] ?? ''));
    }
    else if ($_GET["type"] == "autoAdvanceWorkOrderPlanStatus") {
        // Promote shortage WOs whose material is received into the Can Plan stage.
        gw_json_response(gw_auto_advance_wo_plan_status(
            $conn,
            $_GET['plant_id'] ?? '',
            $_GET['emp_id'] ?? ''
        ));
    }
    else if ($_GET["type"] == "sendCanPlanBatches") {
        medicap_require_helper('can_planned_wo_helpers.php');
        // Request body is already read into $input at top of file; php://input cannot be read twice.
        $payload = is_array($input) ? $input : (json_decode($rawInput, true) ?: []);
        if (!is_array($payload)) {
            $payload = [];
        }
        $result = gw_send_can_plan_batches(
            $conn,
            $payload['Worders'] ?? [],
            $_GET['emp_id'] ?? '',
            $_GET['plant_id'] ?? ''
        );
        gw_json_response($result);
    }

    
    
    else if ($_GET["type"] == "bookStockForCanPlanWO") {
        // Book stock for work orders that can be planned (all materials complete, no shortage)
        $bookPayload = is_array($input) ? $input : (json_decode($rawInput, true) ?: []);
        $workorder_no = $bookPayload['workorder_no'] ?? '';
        $deductions = $bookPayload['Deductions'] ?? [];
        
        if (empty($workorder_no)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
            exit;
        }
        
        if (empty($deductions) || !is_array($deductions)) {
            echo json_encode(['status' => 'error', 'message' => 'Deductions array is required']);
            exit;
        }
        
        // Get work_order_id from Work_order_materials table
        $woSql = "SELECT id FROM Work_order_materials WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."' LIMIT 1";
        $woResult = $conn->query($woSql);
        $work_order_id = null;
        if ($woResult && $woResult->num_rows > 0) {
            $woRow = $woResult->fetch_assoc();
            $work_order_id = $woRow['id'] ?? null;
        }
        
        if (!$work_order_id) {
            echo json_encode(['status' => 'error', 'message' => 'Work order not found']);
            exit;
        }
        
        $entry_date = date("Y-m-d H:i:s");
        $success = true;
        $errors = [];
        
        // Insert or update each deduction into WO_deductions table
        foreach ($deductions as $deduction) {
            $material_code = $deduction['material_code'] ?? '';
            $mat_type = $deduction['mat_type'] ?? '';
            $plan_qty = floatval($deduction['plan_qty'] ?? $deduction['requiredQty'] ?? 0);
            $deducted_from_RM = floatval($deduction['deducted_from_RM'] ?? 0);
            $deducted_from_MC = floatval($deduction['deducted_from_MC'] ?? 0);
            $shortage = floatval($deduction['shortage'] ?? 0);
            $unit = $deduction['unit'] ?? '';
            
            // Skip if material_code is empty or invalid
            if (empty($material_code) || trim($material_code) === '-' || trim($material_code) === 'N/A') {
                continue;
            }
            
            // Check if entry already exists for this workorder_no and material_code
            $checkSql = "SELECT id FROM WO_deductions 
                        WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."' 
                        AND material_code = '".$conn->real_escape_string($material_code)."'
                        AND work_order_id = '".$conn->real_escape_string($work_order_id)."'
                        LIMIT 1";
            $checkResult = $conn->query($checkSql);
            $existingId = null;
            if ($checkResult && $checkResult->num_rows > 0) {
                $checkRow = $checkResult->fetch_assoc();
                $existingId = $checkRow['id'] ?? null;
            }
            
            // Determine status based on deductions
            $status = 'CAN_PLAN';
            if ($deducted_from_MC > 0) {
                $status = 'CAN_PLAN_MC_QTY_USED';
            }
            
            // Since all materials are complete (no shortage), qty_status should be 'Booked'
            $qty_status = 'Booked';
            
            if ($existingId) {
                // Update existing entry
                $updateSql = "UPDATE WO_deductions SET
                    mat_type = '".$conn->real_escape_string($mat_type)."',
                    status = '".$conn->real_escape_string($status)."',
                    plan_qty = '".$conn->real_escape_string($plan_qty)."',
                    unit = '".$conn->real_escape_string($unit)."',
                    deducted_from_RM = '".$conn->real_escape_string($deducted_from_RM)."',
                    deducted_from_MC = '".$conn->real_escape_string($deducted_from_MC)."',
                    shortage = '".$conn->real_escape_string($shortage)."',
                    qty_status = '".$conn->real_escape_string($qty_status)."'
                    WHERE id = '".$conn->real_escape_string($existingId)."'";
                
                if (!$conn->query($updateSql)) {
                    $success = false;
                    $errors[] = "Failed to update deduction for material {$material_code}: " . $conn->error;
                }
            } else {
                // Insert new entry
                $insertSql = "INSERT INTO WO_deductions
                    (plant_id, workorder_no, work_order_id, mat_type, status, 
                     plan_qty, unit, entry_by, entry_date, material_code,
                     deducted_from_RM, deducted_from_MC, shortage, qty_status, indent_status)
                VALUES(
                    '".$conn->real_escape_string($_GET["plant_id"])."',
                    '".$conn->real_escape_string($workorder_no)."',
                    '".$conn->real_escape_string($work_order_id)."',
                    '".$conn->real_escape_string($mat_type)."',
                    '".$conn->real_escape_string($status)."',
                    '".$conn->real_escape_string($plan_qty)."',
                    '".$conn->real_escape_string($unit)."',
                    '".$conn->real_escape_string($_GET["emp_id"] ?? '')."',
                    '".$conn->real_escape_string($entry_date)."',
                    '".$conn->real_escape_string($material_code)."',
                    '".$conn->real_escape_string($deducted_from_RM)."',
                    '".$conn->real_escape_string($deducted_from_MC)."',
                    '".$conn->real_escape_string($shortage)."',
                    '".$conn->real_escape_string($qty_status)."',
                    'Not Raised'
                )";
                
                if (!$conn->query($insertSql)) {
                    $success = false;
                    $errors[] = "Failed to insert deduction for material {$material_code}: " . $conn->error;
                }
            }

            // Audit trail: record the can-plan booking with an on-day stock snapshot.
            if (function_exists('gw_mrp_audit_log')) {
                gw_mrp_audit_log($conn, array(
                    'stage' => 'CAN_PLAN_BOOK',
                    'event_type' => 'Stock booked for Can-Plan WO',
                    'source_screen' => 'Can Planned WO',
                    'workorder_no' => $workorder_no,
                    'work_order_id' => $work_order_id,
                    'material_code' => $material_code,
                    'material_type' => $mat_type,
                    'qty_unit' => $unit,
                    'required_qty' => $plan_qty,
                    'plan_qty' => $plan_qty,
                    'deducted_qty' => $deducted_from_RM,
                    'shortage_qty' => $shortage,
                    'status_to' => $status,
                    'snapshot_stock' => true,
                    'plant_id' => $_GET['plant_id'] ?? '',
                    'emp_id' => $_GET['emp_id'] ?? '',
                ));
            }
        }
        
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Stock booked successfully for work order: ' . $workorder_no]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Some deductions failed to book', 'errors' => $errors]);
        }
    }
    
    else if ($_GET["type"] == "sendForBatchAllocation") {
    // Send work order for Batch No Allocation - insert into batch_planning, batch_planning_materials, mfg_work_order_hdr and mfg_work_order_dtl
    if (!is_array($input)) {
        $input = array();
    }
    $workorder_no = trim((string)($input['workorder_no'] ?? ''));
    $order_no = $input['order_no'] ?? '';
    $product_code = $input['product_code'] ?? '';
    $product_name = $input['product_name'] ?? '';
    $batch_size = floatval($input['batch_size'] ?? 0);
    $batch_unit = $input['planUnit'] ?? '';
    $packing_type = $input['packing_type'] ?? '';
    $work_order_planned_qty = floatval($input['work_order_planned_qty'] ?? 0);
    $planMonth = $input['planMonth'] ?? '';
    $mainGroupName = $input['mainGroupName'] ?? '';
    $deductions = $input['Deductions'] ?? [];
    
    if (empty($workorder_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
        exit;
    }

    $woLive = $conn->query("SELECT * FROM Work_order_materials WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."' ORDER BY id DESC LIMIT 1");
    $woLiveRow = ($woLive && $woLive->num_rows > 0) ? $woLive->fetch_assoc() : null;
    if ($woLiveRow) {
        if ($order_no === '') {
            $order_no = $woLiveRow['order_no'] ?? '';
        }
        if ($product_code === '') {
            $product_code = $woLiveRow['product_code'] ?? '';
        }
        if ($batch_size <= 0) {
            $batch_size = floatval($woLiveRow['batch_size'] ?? 0);
        }
        if ($batch_unit === '') {
            $batch_unit = $woLiveRow['planUnit'] ?? '';
        }
        if ($packing_type === '') {
            $packing_type = $woLiveRow['packingStyle'] ?? ($woLiveRow['packing_type'] ?? '');
        }
        if ($work_order_planned_qty <= 0) {
            $work_order_planned_qty = floatval($woLiveRow['plan_qty'] ?? $woLiveRow['planQty'] ?? $batch_size);
        }
        if ($planMonth === '') {
            $planMonth = $woLiveRow['planMonth'] ?? '';
        }
        if ($mainGroupName === '') {
            $mainGroupName = $woLiveRow['mainGroupName'] ?? '';
        }
    }
    
    if (empty($product_code)) {
        echo json_encode(['status' => 'error', 'message' => 'Product code is required']);
        exit;
    }
    
    // Check if work order is verified before sending for batch allocation
    $checkStatusSql = "SELECT status FROM Work_order_materials 
                      WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."' LIMIT 1";
    $statusResult = $conn->query($checkStatusSql);
    
    if ($statusResult && $statusResult->num_rows > 0) {
        $statusRow = $statusResult->fetch_assoc();
        $currentStatus = $statusRow['status'] ?? '';
        
        // Only allow if status is 'Verified - Ready for Batch Allocation'
        if ($currentStatus !== 'Verified - Ready for Batch Allocation') {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Work order must be verified before sending for batch allocation. Current status: ' . $currentStatus
            ]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Work order not found']);
        exit;
    }
    
    // Get order_materials and po_entry data for batch_planning fields
    $orderDataSql = "SELECT a.*, b.po_no, b.po_date, b.client_code, c.LglNm as client_name, c.TrdNm as client_name_full
                     FROM order_materials a
                     LEFT JOIN po_entry b ON a.order_no = b.order_no
                     LEFT JOIN client c ON b.client_code = c.client_code
                     WHERE a.order_no = '".$conn->real_escape_string($order_no)."' 
                     AND a.product_code = '".$conn->real_escape_string($product_code)."'
                     AND a.plant_id = '".$conn->real_escape_string($_GET["plant_id"])."'
                     LIMIT 1";
    $orderDataResult = $conn->query($orderDataSql);
    $orderData = null;
    if ($orderDataResult && $orderDataResult->num_rows > 0) {
        $orderData = $orderDataResult->fetch_assoc();
    }
    
    // Get product data
    $productSql = "SELECT * FROM product WHERE product_code = '".$conn->real_escape_string($product_code)."' 
                   AND plant_id = '".$conn->real_escape_string($_GET["plant_id"])."' LIMIT 1";
    $productResult = $conn->query($productSql);
    $productData = null;
    if ($productResult && $productResult->num_rows > 0) {
        $productData = $productResult->fetch_assoc();
    }
    
    // Calculate number of batches
    $number_of_batches = ($batch_size > 0 && $work_order_planned_qty > 0) ? ceil($work_order_planned_qty / $batch_size) : 1;
    
    // Get mfr_no from unitformula
    $mfrSql = "SELECT mfr_no FROM unitformula WHERE product_code = '".$conn->real_escape_string($product_code)."' 
               AND status = 'Approve' ORDER BY id DESC LIMIT 1";
    $mfrResult = $conn->query($mfrSql);
    $mfr_no = null;
    if ($mfrResult && $mfrResult->num_rows > 0) {
        $mfrRow = $mfrResult->fetch_assoc();
        $mfr_no = $mfrRow['mfr_no'] ?? null;
    }
    if (!$mfr_no) {
        $mfrSql2 = "SELECT mfr_no FROM unitformula WHERE product_code = '".$conn->real_escape_string($product_code)."' ORDER BY id DESC LIMIT 1";
        $mfrResult2 = $conn->query($mfrSql2);
        if ($mfrResult2 && $mfrResult2->num_rows > 0) {
            $mfr_no = $mfrResult2->fetch_assoc()['mfr_no'] ?? null;
        }
    }

    medicap_require_helper('can_planned_wo_helpers.php');
    if (function_exists('stp_ensure_batch_planning_schema')) {
        stp_ensure_batch_planning_schema($conn);
    }
    $resolvedBfr = '';
    if (function_exists('gw_resolve_bfr_for_work_order')) {
        $resolvedBfr = gw_resolve_bfr_for_work_order($conn, $product_code, $batch_size, $batch_unit);
    }
    
    if ($batch_size <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot find MFR or batch formula for product: ' . $product_code]);
        exit;
    }
    
    // Get bfr_no from batch_formula_info
    $bfr_no = $resolvedBfr;
    if ($bfr_no === '' && $mfr_no) {
        $bfrSql = "SELECT bfr_no FROM batch_formula_info 
              WHERE mfr_no = '".$conn->real_escape_string($mfr_no)."' 
              AND batch_formula_weight = '".$conn->real_escape_string($batch_size)."'
              AND rm_batch_size_unit = '".$conn->real_escape_string($batch_unit)."'
              AND status = 'Approve'
              LIMIT 1";
        $bfrResult = $conn->query($bfrSql);
        if ($bfrResult && $bfrResult->num_rows > 0) {
            $bfr_no = $bfrResult->fetch_assoc()['bfr_no'] ?? '';
        }
    }
    
    if (!$bfr_no) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot find batch formula record (bfr_no)']);
        exit;
    }
    if (!$mfr_no) {
        $mfrFromBfr = $conn->query("SELECT mfr_no FROM batch_formula_info WHERE bfr_no = '".$conn->real_escape_string($bfr_no)."' LIMIT 1");
        if ($mfrFromBfr && $mfrFromBfr->num_rows > 0) {
            $mfr_no = $mfrFromBfr->fetch_assoc()['mfr_no'] ?? '';
        }
    }
    
    $entry_date = date('Y-m-d H:i:s');
    $planned_for_year = date('Y');
    $planned_for_month = !empty($planMonth) ? $planMonth : date('m');
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Insert into batch_planning table
        $batchPlanSql = "INSERT INTO batch_planning (
            plant_id, material_type, plan_for, plan_based_on, plan_type, 
            plan_for_market, plan_client_name, plan_client_code, 
            client_po_no, client_po_date, client_po_qty, client_po_unit,
            product_type, product_code, product_name, grade, bfr_no, mfr_no,
            batch_size, pack_size, pack_unit, total_batches, planned_qty,
            qty_can_planned, no_of_batches_can_planned, status, entry_by, entry_date,
            planned_for_year, planned_for_month, start_date, plan_no
        ) VALUES (
            '".$conn->real_escape_string($_GET["plant_id"])."',
            'RM',
            'Total Qty Wise',
            'Proposed Qty',
            'Total Qty Wise',
            '".($orderData ? $conn->real_escape_string($orderData['plan_for_market'] ?? '') : '')."',
            '".($orderData ? $conn->real_escape_string($orderData['client_name_full'] ?? $orderData['client_name'] ?? '') : '')."',
            '".($orderData ? $conn->real_escape_string($orderData['client_code'] ?? '') : '')."',
            '".($orderData ? $conn->real_escape_string($orderData['po_no'] ?? '') : '')."',
            '".($orderData ? $conn->real_escape_string($orderData['po_date'] ?? '') : '')."',
            '".($orderData ? $conn->real_escape_string($orderData['order_qty'] ?? $work_order_planned_qty) : $work_order_planned_qty)."',
            '".($orderData ? $conn->real_escape_string($orderData['unit'] ?? $batch_unit) : $batch_unit)."',
            '".($productData ? $conn->real_escape_string($productData['product_type'] ?? '') : '')."',
            '".$conn->real_escape_string($product_code)."',
            '".$conn->real_escape_string($product_name)."',
            '".($productData ? $conn->real_escape_string($productData['grade'] ?? '') : '')."',
            '".$conn->real_escape_string($bfr_no)."',
            '".$conn->real_escape_string($mfr_no)."',
            '".$conn->real_escape_string($batch_size)."',
            '".$conn->real_escape_string($packing_type)."',
            '".$conn->real_escape_string($batch_unit)."',
            '".$number_of_batches."',
            '".$work_order_planned_qty."',
            '".$work_order_planned_qty."',
            '".$number_of_batches."',
            'pending',
            '".$conn->real_escape_string($_GET["emp_id"])."',
            '".$entry_date."',
            '".$planned_for_year."',
            '".$planned_for_month."',
            '".$entry_date."',
            '".$conn->real_escape_string($workorder_no)."'
        )";
        
        if (!$conn->query($batchPlanSql)) {
            throw new Exception('Failed to insert batch_planning: ' . $conn->error);
        }
        
        $batch_plan_id = $conn->insert_id;
        @$conn->query("UPDATE batch_planning SET
                plan_no = IF(TRIM(IFNULL(plan_no,''))='', '".$conn->real_escape_string($workorder_no)."', plan_no),
                workorder_no = '".$conn->real_escape_string($workorder_no)."'
            WHERE id = ".intval($batch_plan_id)." LIMIT 1");
        
        // 2. Insert materials into batch_planning_materials
        $allMaterialsForDetail = [];
        
        // Fetch Raw Materials from batch_materials
        $rmSql = "SELECT a.*,
                 COALESCE((SELECT material_name FROM material WHERE material_code=a.material_code),
                         (SELECT bulkName FROM bulkMaster WHERE bulkCode=a.material_code)) AS material_name,
                 (SELECT COUNT(*) FROM bulkMaster WHERE bulkCode=a.material_code) AS is_bulk
                 FROM batch_materials a
                 WHERE a.material_type = 'Raw Material'
                 AND a.bfr_no = '".$conn->real_escape_string($bfr_no)."'";
        
        $rmResult = $conn->query($rmSql);
        if ($rmResult && $rmResult->num_rows > 0) {
            while ($rmRow = $rmResult->fetch_assoc()) {
                $material_code = $rmRow['material_code'] ?? '';
                $qty_per_batch = floatval($rmRow['qty'] ?? 0);
                $required_qty = $qty_per_batch * $number_of_batches;
                $isBulk = intval($rmRow['is_bulk'] ?? 0) > 0;
                
                if ($material_code && $required_qty > 0) {
                    // Get material grade
                    $gradeSql = "SELECT grade FROM material WHERE material_code = '".$conn->real_escape_string($material_code)."' 
                                AND plant_id = '".$conn->real_escape_string($_GET["plant_id"])."' LIMIT 1";
                    $gradeResult = $conn->query($gradeSql);
                    $material_grade = '1';
                    if ($gradeResult && $gradeResult->num_rows > 0) {
                        $gradeRow = $gradeResult->fetch_assoc();
                        $material_grade = $gradeRow['grade'] ?? '1';
                    }
                    
                    // Insert into batch_planning_materials
                    $batchPlanMatSql = "INSERT INTO batch_planning_materials (
                        plant_id, batch_plan_id, material_type, pack_size, pack_unit,
                        mf_batch_size, bfr_no, material_code, overages, qty, unit, grade,
                        batch_qty, total_qty, plan_qty, batches_can_plan, shortage_qty, disp_id, dispensing_status
                    ) VALUES (
                        '".$conn->real_escape_string($_GET["plant_id"])."',
                        '".$batch_plan_id."',
                        'Raw Material',
                        '',
                        '".$conn->real_escape_string($batch_unit)."',
                        '".$conn->real_escape_string($batch_size)."',
                        '".$conn->real_escape_string($bfr_no)."',
                        '".$conn->real_escape_string($material_code)."',
                        '0',
                        '".$qty_per_batch."',
                        '".$conn->real_escape_string($rmRow['unit'] ?? '')."',
                        '".$conn->real_escape_string($material_grade)."',
                        '".$qty_per_batch."',
                        '".$required_qty."',
                        '".$required_qty."',
                        '".$number_of_batches."',
                        '0',
                        '0',
                        ' '
                    )";
                    
                    if (!$conn->query($batchPlanMatSql)) {
                        throw new Exception('Failed to insert batch_planning_materials (RM): ' . $conn->error);
                    }
                    
                    $allMaterialsForDetail[] = [
                        'material_code' => $material_code,
                        'material_name' => $rmRow['material_name'] ?? $material_code,
                        'mat_type' => $isBulk ? 'Bulk' : 'RM',
                        'required_qty' => $required_qty,
                        'batch_qty' => $qty_per_batch,
                        'unit' => $rmRow['unit'] ?? '',
                        'grade' => $material_grade
                    ];
                }
            }
        }
        
        // Fetch Packing Materials from batch_materials
        $pmSql = "SELECT a.*,
                 COALESCE((SELECT material_name FROM material WHERE material_code=a.material_code),
                         (SELECT bulkName FROM bulkMaster WHERE bulkCode=a.material_code)) AS material_name
                 FROM batch_materials a
                 WHERE a.material_type = 'Packing Material'
                 AND a.bfr_no = '".$conn->real_escape_string($bfr_no)."'";
        
        if (!empty($packing_type)) {
            $pmSql .= " AND a.pack_size = '".$conn->real_escape_string($packing_type)."'";
        }
        
        $pmResult = $conn->query($pmSql);
        if ($pmResult && $pmResult->num_rows > 0) {
            while ($pmRow = $pmResult->fetch_assoc()) {
                $material_code = $pmRow['material_code'] ?? '';
                $qty_per_batch = floatval($pmRow['qty'] ?? 0);
                $required_qty = $qty_per_batch * $number_of_batches;
                
                if ($material_code && $required_qty > 0) {
                    // Get material grade
                    $gradeSql = "SELECT grade FROM material WHERE material_code = '".$conn->real_escape_string($material_code)."' 
                                AND plant_id = '".$conn->real_escape_string($_GET["plant_id"])."' LIMIT 1";
                    $gradeResult = $conn->query($gradeSql);
                    $material_grade = '1';
                    if ($gradeResult && $gradeResult->num_rows > 0) {
                        $gradeRow = $gradeResult->fetch_assoc();
                        $material_grade = $gradeRow['grade'] ?? '1';
                    }
                    
                    // Insert into batch_planning_materials
                    $batchPlanMatSql = "INSERT INTO batch_planning_materials (
                        plant_id, batch_plan_id, material_type, pack_size, pack_unit,
                        mf_batch_size, bfr_no, material_code, overages, qty, unit, grade,
                        batch_qty, total_qty, plan_qty, batches_can_plan, shortage_qty, disp_id, dispensing_status
                    ) VALUES (
                        '".$conn->real_escape_string($_GET["plant_id"])."',
                        '".$batch_plan_id."',
                        'Packing Material',
                        '".$conn->real_escape_string($packing_type)."',
                        '".$conn->real_escape_string($batch_unit)."',
                        '".$conn->real_escape_string($batch_size)."',
                        '".$conn->real_escape_string($bfr_no)."',
                        '".$conn->real_escape_string($material_code)."',
                        '0',
                        '".$qty_per_batch."',
                        '".$conn->real_escape_string($pmRow['unit'] ?? '')."',
                        '".$conn->real_escape_string($material_grade)."',
                        '".$qty_per_batch."',
                        '".$required_qty."',
                        '".$required_qty."',
                        '".$number_of_batches."',
                        '0',
                        '0',
                        ' '
                    )";
                    
                    if (!$conn->query($batchPlanMatSql)) {
                        throw new Exception('Failed to insert batch_planning_materials (PM): ' . $conn->error);
                    }
                    
                    $allMaterialsForDetail[] = [
                        'material_code' => $material_code,
                        'material_name' => $pmRow['material_name'] ?? $material_code,
                        'mat_type' => 'PM',
                        'required_qty' => $required_qty,
                        'batch_qty' => $qty_per_batch,
                        'unit' => $pmRow['unit'] ?? '',
                        'grade' => $material_grade
                    ];
                }
            }
        }

        if (count($allMaterialsForDetail) === 0) {
            $fallbackRows = [];
            $woDedSql = "SELECT d.material_code, d.mat_type, d.plan_qty, d.unit,
                    COALESCE(
                        (SELECT material_name FROM material WHERE material_code = d.material_code LIMIT 1),
                        (SELECT bulkName FROM bulkMaster WHERE bulkCode = d.material_code LIMIT 1),
                        d.material_code
                    ) AS material_name
                FROM WO_deductions d
                WHERE d.workorder_no = '".$conn->real_escape_string($workorder_no)."'
                  AND TRIM(IFNULL(d.material_code,'')) NOT IN ('', '-', 'N/A')";
            $woDedRes = $conn->query($woDedSql);
            if ($woDedRes && $woDedRes->num_rows > 0) {
                while ($d = $woDedRes->fetch_assoc()) {
                    $fallbackRows[] = $d;
                }
            }
            if (count($fallbackRows) === 0 && function_exists('gw_gwo_collect_bfr_deduction_lines')) {
                foreach (gw_gwo_collect_bfr_deduction_lines($conn, $bfr_no, $woLiveRow ?: []) as $line) {
                    $fallbackRows[] = [
                        'material_code' => $line['material_code'] ?? '',
                        'mat_type' => $line['mat_type'] ?? 'RM',
                        'plan_qty' => $line['plan_qty'] ?? 0,
                        'unit' => $line['unit'] ?? $batch_unit,
                        'material_name' => $line['material_name'] ?? ($line['material_code'] ?? ''),
                    ];
                }
            }
            if (count($fallbackRows) === 0 && is_array($deductions)) {
                foreach ($deductions as $d) {
                    $fallbackRows[] = [
                        'material_code' => $d['material_code'] ?? '',
                        'mat_type' => $d['mat_type'] ?? ($d['type'] ?? 'RM'),
                        'plan_qty' => $d['plan_qty'] ?? ($d['requiredQty'] ?? 0),
                        'unit' => $d['unit'] ?? $batch_unit,
                        'material_name' => $d['material_name'] ?? ($d['material_code'] ?? ''),
                    ];
                }
            }
            foreach ($fallbackRows as $fb) {
                $material_code = trim((string)($fb['material_code'] ?? ''));
                $required_qty = floatval($fb['plan_qty'] ?? 0);
                if ($material_code === '' || $required_qty <= 0) {
                    continue;
                }
                $qty_per_batch = $number_of_batches > 0 ? round($required_qty / $number_of_batches, 4) : $required_qty;
                $matTypeLabel = (stripos((string)($fb['mat_type'] ?? ''), 'P') === 0) ? 'Packing Material' : 'Raw Material';
                $unit = $fb['unit'] ?? $batch_unit;
                $batchPlanMatSql = "INSERT INTO batch_planning_materials (
                        plant_id, batch_plan_id, material_type, pack_size, pack_unit,
                        mf_batch_size, bfr_no, material_code, overages, qty, unit, grade,
                        batch_qty, total_qty, plan_qty, batches_can_plan, shortage_qty, disp_id, dispensing_status
                    ) VALUES (
                        '".$conn->real_escape_string($_GET["plant_id"])."',
                        '".$batch_plan_id."',
                        '".$conn->real_escape_string($matTypeLabel)."',
                        '".$conn->real_escape_string($packing_type)."',
                        '".$conn->real_escape_string($batch_unit)."',
                        '".$conn->real_escape_string($batch_size)."',
                        '".$conn->real_escape_string($bfr_no)."',
                        '".$conn->real_escape_string($material_code)."',
                        '0',
                        '".$qty_per_batch."',
                        '".$conn->real_escape_string($unit)."',
                        '1',
                        '".$qty_per_batch."',
                        '".$required_qty."',
                        '".$required_qty."',
                        '".$number_of_batches."',
                        '0',
                        '0',
                        ' '
                    )";
                if (!$conn->query($batchPlanMatSql)) {
                    throw new Exception('Failed to insert batch_planning_materials (WO deductions): ' . $conn->error);
                }
                $allMaterialsForDetail[] = [
                    'material_code' => $material_code,
                    'material_name' => $fb['material_name'] ?? $material_code,
                    'mat_type' => $fb['mat_type'] ?? 'RM',
                    'required_qty' => $required_qty,
                    'batch_qty' => $qty_per_batch,
                    'unit' => $unit,
                    'grade' => '1'
                ];
            }
        }
        
        if (count($allMaterialsForDetail) === 0) {
            throw new Exception('No materials found to insert. Check WO deductions / BFR for '.$workorder_no);
        }

        // Manufacturing WO header is created later in Production → Batch Planning
        // (Prepare Work Order). Inserting it here skipped that stage and landed
        // the batch on QA Approved Batches.
        $updateStatusSql = "UPDATE Work_order_materials SET status = 'Sent for Batch Allocation'
                           WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."'";
        if (!$conn->query($updateStatusSql)) {
            throw new Exception('Failed to update work order status: ' . $conn->error);
        }

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Work order sent for line booking. After line approval, continue at Production → Batch Planning.',
            'batch_plan_id' => $batch_plan_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ]);
    }
}

// ============================================
// NEW ENDPOINTS FOR VERIFICATION FLOW
// ============================================

// Send work order from canplan to verify-stock
else if ($_GET["type"] == "sendForVerification") {
    $input = json_decode(file_get_contents('php://input'), true);
    $workorder_no = $input['workorder_no'] ?? '';
    $wo_id = $input['id'] ?? '';
    
    if (empty($workorder_no) && empty($wo_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Work order number or ID is required']);
        exit;
    }
    
    $entry_date = date('Y-m-d H:i:s');
    
    // Update Work_order_materials status to 'Pending Verification'
    $whereClause = '';
    if (!empty($wo_id)) {
        $whereClause = "id = '".$conn->real_escape_string($wo_id)."'";
    } else {
        $whereClause = "workorder_no = '".$conn->real_escape_string($workorder_no)."'";
    }
    
    $sql = "UPDATE Work_order_materials 
            SET status = 'Pending Verification',
                send_for_verification_by = '".$conn->real_escape_string($_GET["emp_id"])."',
                send_for_verification_on = '".$entry_date."'
            WHERE ".$whereClause;
    
    if ($conn->query($sql)) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Work order sent for verification successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to send work order for verification: ' . $conn->error
        ]);
    }
}

// Get work orders pending verification (for verify-stock component)
else if ($_GET["type"] == "getPendingVerificationWO") {
    
    
$output = [];
$shortageInfo = [];
medicap_require_helper('mrp_wo_schema_helpers.php');
if (function_exists('ensureWoVerificationColumns')) {
    ensureWoVerificationColumns($conn);
}

/* ---------------- MAIN QUERY ---------------- */
$sql = "
SELECT 
    a.*,
    b.product_code,
    b.work_order_planned_qty,
    COALESCE(
        NULLIF(TRIM(a.entryOn), ''),
        NULLIF(TRIM(a.Wo_Generated_on), ''),
        NULLIF(TRIM(a.wo_generated_by_digi_sign_date), '')
    ) AS Wo_Generated_on,
    COALESCE(
        NULLIF(TRIM(a.deliveryDate), ''),
        NULLIF(TRIM(b.deliveryDate), ''),
        (SELECT NULLIF(TRIM(om.deliveryDate), '') FROM order_materials om
          WHERE om.order_no = a.order_no
          ORDER BY CASE
            WHEN om.product_code = COALESCE(a.product_code, b.product_code) THEN 0
            ELSE 1 END, om.id DESC
          LIMIT 1)
    ) AS deliveryDate,
    COALESCE(NULLIF(TRIM(b.packingStyle), ''), NULLIF(TRIM(a.packingStyle), '')) AS packing_type,

    (SELECT LglNm 
        FROM client cl 
        LEFT JOIN po_entry po ON po.client_code = cl.client_code 
        WHERE po.order_no = a.order_no 
        LIMIT 1
    ) AS mainGroupName,

    (SELECT product_name 
        FROM product c 
        WHERE c.product_code = COALESCE(b.product_code, a.product_code) 
        LIMIT 1
    ) AS product_name

FROM Work_order_materials a
LEFT JOIN order_materials b 
    ON a.order_no = b.order_no
   AND (b.product_code = a.product_code OR TRIM(IFNULL(a.product_code,'')) = '')
WHERE a.status IN ('Pending Verification', 'Verified - Ready for Batch Allocation')
ORDER BY a.workorder_no DESC
";

$result = $conn->query($sql);

/* ---------------- COLORS ---------------- */
$colors = [
    "#FFCCCB","#CCFFCC","#CCE5FF","#FFFACD","#D1C4E9",
    "#FFE0B2","#F8BBD0","#B2EBF2","#E6EE9C","#FFECB3",
    "#CFD8DC","#F0F4C3","#DCEDC8","#F5F5F5","#E1BEE7"
];

$colorMap = [];
$colorIndex = 0;

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $po = $row['order_no'];

        if (!isset($colorMap[$po])) {
            $colorMap[$po] = $colors[$colorIndex % count($colors)];
            $colorIndex++;
        }

        $row['bg_color'] = $colorMap[$po];

        if (empty($row['Wo_Generated_on'])) {
            $row['Wo_Generated_on'] = $row['entryOn'] ?? ($row['wo_generated_by_digi_sign_date'] ?? '');
        }
        if (empty($row['deliveryDate'])) {
            $row['deliveryDate'] = $row['delivery_date'] ?? '';
        }

        /* ---------------- DEDUCTIONS + AVAILABLE STOCK ---------------- */
        $deductionsSql = "
        SELECT 
            d.*,
            COALESCE(
                (SELECT material_name FROM material WHERE material_code = d.material_code LIMIT 1),
                (SELECT bulkName FROM bulkMaster WHERE bulkCode = d.material_code LIMIT 1),
                ''
            ) AS material_name,
            COALESCE(v.available_qty, 0) AS available_qty
        FROM WO_deductions d
        LEFT JOIN vw_total_available_stock v
            ON v.material_code = d.material_code
        WHERE d.workorder_no = '".$conn->real_escape_string($row['workorder_no'])."'
        ";

        $deductionsResult = $conn->query($deductionsSql);
        $deductions = [];

        if ($deductionsResult && $deductionsResult->num_rows > 0) {
            while ($dedRow = $deductionsResult->fetch_assoc()) {
                $idx = json_decode($dedRow['indexData'] ?? '', true);
                if (is_array($idx) && isset($idx['stock_verification']) && is_array($idx['stock_verification'])) {
                    $dedRow['stock_verification'] = $idx['stock_verification'];
                }
                $deductions[] = $dedRow;
            }
        }

        $row['Deductions'] = $deductions;

        /* ---------------- SHORTAGE CALCULATION ---------------- */
        $hasShortage = false;
        $woShortageMaterials = [];

        foreach ($deductions as $ded) {
            if($ded['material_code']!=''){
                
         

            $requiredQty  = floatval($ded['plan_qty'] ?? 0);
            $availableQty = floatval($ded['available_qty'] ?? 0);

            $shortage = max(0, $requiredQty - $availableQty);

            if ($shortage > 0) {
                $hasShortage = true;

                $woShortageMaterials[] = [
                    'material_code'  => $ded['material_code'] ?? '',
                    'material_name'  => $ded['material_name'] ?? '',
                    'required_qty'   => $requiredQty,
                    'available_qty'  => $availableQty,
                    'shortage'       => $shortage,
                    'mat_type'       => $ded['mat_type'] ?? '',
                    'reason'         => 'Insufficient stock'
                ];
            }
            }
        }

        if ($hasShortage) {
            $shortageInfo[] = [
                'workorder_no'        => $row['workorder_no'],
                'order_no'            => $row['order_no'],
                'product_code'        => $row['product_code'],
                'product_name'        => $row['product_name'],
                'shortage_materials'  => $woShortageMaterials
            ];
        }

        $row['has_shortage'] = $hasShortage;
        $output[] = $row;
    }
}

/* ---------------- RESPONSE ---------------- */
echo json_encode([
    'work_orders'   => $output,
    'shortage_info' => $shortageInfo
]);
}

// Verify stock for a work order - Enhanced with real-time stock calculation
else if ($_GET["type"] == "verifyStockForWO") {
    if (!is_array($input)) {
        $input = array();
    }
    $workorder_no = trim((string)($input['workorder_no'] ?? $input['workOrderNo'] ?? ''));
    if ($workorder_no === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Work order number is missing. Close this popup, open View again, and click Verify Stock.'
        ]);
        exit;
    }

    medicap_require_helper('mrp_wo_schema_helpers.php');
    if (function_exists('ensureWoVerificationColumns')) {
        ensureWoVerificationColumns($conn);
    }
    
    $entry_date = date('Y-m-d H:i:s');
    
    // Get work order details
    $woSql = "SELECT a.*, COALESCE(b.product_code, a.product_code) AS product_code, b.work_order_planned_qty 
              FROM Work_order_materials a
              LEFT JOIN order_materials b ON a.order_no = b.order_no
                AND (b.product_code = a.product_code OR TRIM(IFNULL(a.product_code,'')) = '')
              WHERE a.workorder_no = '".$conn->real_escape_string($workorder_no)."'
              ORDER BY a.id DESC
              LIMIT 1";
    $woResult = $conn->query($woSql);
    
    if (!$woResult || $woResult->num_rows == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Work order '.$workorder_no.' was not found. Refresh Verify Stock and try again.'
        ]);
        exit;
    }
    
    $woRow = $woResult->fetch_assoc();
    $currentStatus = trim((string)($woRow['status'] ?? ''));
    if ($currentStatus === 'Sent for Batch Allocation') {
        echo json_encode([
            'status' => 'error',
            'message' => $workorder_no.' is already sent for batch allocation. Open Line Booking / For Plan to continue.'
        ]);
        exit;
    }
    if ($currentStatus === 'Rejected' || $currentStatus === 'Cancel' || $currentStatus === 'Hold') {
        echo json_encode([
            'status' => 'error',
            'message' => $workorder_no.' is '.$currentStatus.'. It cannot be verified. Return to Process Plan if it must be planned again.'
        ]);
        exit;
    }
    if ($currentStatus !== '' && $currentStatus !== 'Pending Verification' && $currentStatus !== 'Verified - Ready for Batch Allocation') {
        echo json_encode([
            'status' => 'error',
            'message' => $workorder_no.' is in status "'.$currentStatus.'". Send it from Process Plan using Send for Verification first, then click Verify Stock.'
        ]);
        exit;
    }
    $batch_size = floatval($woRow['batch_size'] ?? 0);
    $batch_unit = $woRow['planUnit'] ?? '';
    $product_code = $woRow['product_code'] ?? '';
    $work_order_planned_qty = floatval($woRow['work_order_planned_qty'] ?? 0);
    $plant_id =   $_GET["plant_id"] ?? '';
    
    // Calculate number of batches
    $number_of_batches = ($batch_size > 0 && $work_order_planned_qty > 0) ? ($work_order_planned_qty / $batch_size) : 1;

    if ($currentStatus === 'Verified - Ready for Batch Allocation') {
        $deductions = [];
        $dedRes = $conn->query(
            "SELECT d.*, COALESCE(
                (SELECT material_name FROM material WHERE material_code = d.material_code LIMIT 1),
                (SELECT bulkName FROM bulkMaster WHERE bulkCode = d.material_code LIMIT 1),
                d.material_code
            ) AS material_name
            FROM WO_deductions d
            WHERE d.workorder_no = '".$conn->real_escape_string($workorder_no)."'
            ORDER BY d.id ASC"
        );
        if ($dedRes) {
            while ($ded = $dedRes->fetch_assoc()) {
                $deductions[] = $ded;
            }
        }
        echo json_encode([
            'status' => 'success',
            'message' => 'Stock already verified for '.$workorder_no.'. Click Send for Batch Allocation.',
            'has_shortage' => false,
            'stock_booked' => true,
            'verification_status' => 'Verified',
            'shortage_count' => 0,
            'shortage_info' => [],
            'deductions' => $deductions,
            'work_order_details' => [
                'workorder_no' => $workorder_no,
                'product_code' => $product_code,
                'batch_size' => $batch_size,
                'batch_unit' => $batch_unit,
                'work_order_planned_qty' => $work_order_planned_qty,
                'number_of_batches' => round($number_of_batches, 2)
            ]
        ]);
        exit;
    }
    
    // Get mfr_no and bfr_no
    $mfrSql = "SELECT mfr_no FROM unitformula WHERE product_code = '".$conn->real_escape_string($product_code)."' AND status = 'Approve' ORDER BY id DESC LIMIT 1";
    $mfrResult = $conn->query($mfrSql);
    $mfr_no = null;
    if ($mfrResult && $mfrResult->num_rows > 0) {
        $mfrRow = $mfrResult->fetch_assoc();
        $mfr_no = $mfrRow['mfr_no'] ?? null;
    }
    
    if (!$mfr_no || $batch_size <= 0 || empty($batch_unit)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Cannot find MFR or batch formula for product: ' . $product_code
        ]);
        exit;
    }
    
    // Get bfr_no
    $bfrSql = "SELECT bfr_no FROM batch_formula_info 
              WHERE mfr_no = '".$conn->real_escape_string($mfr_no)."' 
              AND batch_formula_weight = '".$conn->real_escape_string($batch_size)."'
              AND rm_batch_size_unit = '".$conn->real_escape_string($batch_unit)."'
              AND status = 'Approve'
              LIMIT 1";
    $bfrResult = $conn->query($bfrSql);
    $bfr_no = null;
    if ($bfrResult && $bfrResult->num_rows > 0) {
        $bfrRow = $bfrResult->fetch_assoc();
        $bfr_no = $bfrRow['bfr_no'] ?? null;
    }
    
    if (!$bfr_no) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Cannot find batch formula record (bfr_no)'
        ]);
        exit;
    }
    
    // Get all materials and check stock in real-time
    $deductions = [];
    $hasShortage = false;
    $shortageInfo = [];
    $allMaterialsComplete = true;
    
    // Fetch Raw Materials from batch_materials
    $rmSql = "SELECT a.*,
             COALESCE((SELECT material_name FROM material WHERE material_code=a.material_code),
                     (SELECT bulkName FROM bulkMaster WHERE bulkCode=a.material_code)) AS material_name,
             (SELECT mother_code FROM material WHERE material_code=a.material_code) AS mother_code,
             (SELECT COUNT(*) FROM bulkMaster WHERE bulkCode=a.material_code) AS is_bulk
             FROM batch_materials a
             WHERE a.material_type = 'Raw Material'
             AND a.bfr_no = '".$conn->real_escape_string($bfr_no)."'";
    
    $rmResult = $conn->query($rmSql);
    if ($rmResult && $rmResult->num_rows > 0) {
        while ($rmRow = $rmResult->fetch_assoc()) {
            $material_code = $rmRow['material_code'] ?? '';
            $qty_per_batch = floatval($rmRow['qty'] ?? 0);
            $required_qty = $qty_per_batch * $number_of_batches;
            $unit = $rmRow['unit'] ?? '';
            $isBulk = intval($rmRow['is_bulk'] ?? 0) > 0;
            
            if ($material_code && $required_qty > 0) {
                $availableRM = 0;
                $availableMC = 0;
                $total_available = 0;
                $shortage = 0;
                // Amounts actually consumed (RM first, then mother code) — stored as deductions
                // so the invariant plan_qty = deducted_RM + deducted_MC + shortage always holds.
                $usedRM = 0;
                $usedMC = 0;
                $material_name = $rmRow['material_name'] ?? $material_code;
                
                if ($isBulk) {
                    // For bulk materials, drill down to RM components
                    $bulkComponentsSql = "SELECT * FROM bulkMaterials WHERE bulkCode = '".$conn->real_escape_string($material_code)."'";
                    $bulkComponentsResult = $conn->query($bulkComponentsSql);
                    
                    if ($bulkComponentsResult && $bulkComponentsResult->num_rows > 0) {
                        $rmRequirements = [];
                        
                        while ($comp = $bulkComponentsResult->fetch_assoc()) {
                            $compMaterialCode = $comp['material_code'] ?? '';
                            $compPerQty = floatval($comp['perQty'] ?? 0);
                            $compType = strtolower($comp['material_type'] ?? '');
                            $compSubType = strtolower($comp['material_subtype'] ?? '');
                            $compRequiredQty = ($required_qty * $compPerQty) / 100;
                            
                            if ($compType === 'premix' || $compSubType === 'premix') {
                                $primixSql = "SELECT * FROM primixMaterials WHERE premixCode = '".$conn->real_escape_string($compMaterialCode)."'";
                                $primixResult = $conn->query($primixSql);
                                
                                if ($primixResult && $primixResult->num_rows > 0) {
                                    while ($primixMat = $primixResult->fetch_assoc()) {
                                        $primixMaterialCode = $primixMat['material_code'] ?? '';
                                        $primixPerQty = floatval($primixMat['perQty'] ?? 0);
                                        $primixRequiredQty = ($compRequiredQty * $primixPerQty) / 100;
                                        
                                        if ($primixMaterialCode && $primixRequiredQty > 0) {
                                            if (!isset($rmRequirements[$primixMaterialCode])) {
                                                $rmRequirements[$primixMaterialCode] = 0;
                                            }
                                            $rmRequirements[$primixMaterialCode] += $primixRequiredQty;
                                        }
                                    }
                                }
                            } else if ($compType === 'raw material' || $compType === 'rm') {
                                if ($compMaterialCode && $compRequiredQty > 0) {
                                    if (!isset($rmRequirements[$compMaterialCode])) {
                                        $rmRequirements[$compMaterialCode] = 0;
                                    }
                                    $rmRequirements[$compMaterialCode] += $compRequiredQty;
                                }
                            }
                        }
                        
                        // Check stock for all required RMs
                        foreach ($rmRequirements as $rmCode => $rmRequiredQty) {
                            // Skip if material code is empty or invalid
                            if (empty($rmCode) || $rmCode === '-' || trim($rmCode) === '') {
                                continue;
                            }
                            
                            $rmStock = 0;
                            $mcStock = 0;
                            
                            $rmStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                         WHERE material_code = '".$conn->real_escape_string($rmCode)."' 
                                         AND plant_id = '".$conn->real_escape_string($plant_id)."' LIMIT 1";
                            $rmStockResult = $conn->query($rmStockSql);
                            if ($rmStockResult && $rmStockResult->num_rows > 0) {
                                $rmStockRow = $rmStockResult->fetch_assoc();
                                $rmStock = floatval($rmStockRow['available_qty'] ?? 0);
                            }
                            
                            // Mother code is not used in this project — RM stock only.
                            $compUsedRM = min($rmRequiredQty, $rmStock);
                            $compUsedMC = 0;
                            $rmTotalAvailable = $compUsedRM;
                            $rmShortage = max($rmRequiredQty - $compUsedRM, 0);
                            
                            if ($rmShortage > 0) {
                                $hasShortage = true;
                                $allMaterialsComplete = false;
                                
                                $rmNameSql = "SELECT COALESCE((SELECT material_name FROM material WHERE material_code='".$conn->real_escape_string($rmCode)."'), (SELECT bulkName FROM bulkMaster WHERE bulkCode='".$conn->real_escape_string($rmCode)."')) AS material_name";
                                $rmNameResult = $conn->query($rmNameSql);
                                $rmMaterialName = $rmCode;
                                if ($rmNameResult && $rmNameResult->num_rows > 0) {
                                    $rmNameRow = $rmNameResult->fetch_assoc();
                                    $rmMaterialName = $rmNameRow['material_name'] ?? $rmCode;
                                }
                                
                                $shortageInfo[] = [
                                    'material_code' => $rmCode,
                                    'material_name' => $rmMaterialName,
                                    'required_qty' => round($rmRequiredQty, 4),
                                    'available_RM' => round($rmStock, 4),
                                    'available_MC' => round($mcStock, 4),
                                    'total_available' => round($rmTotalAvailable, 4),
                                    'shortage' => round($rmShortage, 4),
                                    'mat_type' => 'Raw Material',
                                    'unit' => $unit,
                                    'source' => 'Bulk: ' . $material_code,
                                    'reason' => 'Insufficient stock for bulk component'
                                ];
                            }
                            
                            // Aggregate consumed amounts and real shortage for the bulk line.
                            $usedRM += $compUsedRM;
                            $usedMC += $compUsedMC;
                            $shortage += $rmShortage;
                            $availableRM += $rmStock;
                            $availableMC += $mcStock;
                        }
                        
                        $total_available = $usedRM + $usedMC;
                    }
                } else {
                    // Regular RM material - check stock directly
                    $rmStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                  WHERE material_code = '".$conn->real_escape_string($material_code)."' 
                                  AND plant_id = '".$conn->real_escape_string($plant_id)."' LIMIT 1";
                    $rmStockResult = $conn->query($rmStockSql);
                    if ($rmStockResult && $rmStockResult->num_rows > 0) {
                        $rmStockRow = $rmStockResult->fetch_assoc();
                        $availableRM = floatval($rmStockRow['available_qty'] ?? 0);
                    }
                    
                    // Mother code is not used in this project — RM stock only.
                    $availableMC = 0;
                    $usedRM = min($required_qty, $availableRM);
                    $usedMC = 0;
                    $total_available = $usedRM;
                    $shortage = max($required_qty - $usedRM, 0);
                    
                    if ($shortage > 0 && !empty($material_code)) {
                        $hasShortage = true;
                        $allMaterialsComplete = false;
                        
                        $shortageInfo[] = [
                            'material_code' => $material_code,
                            'material_name' => $material_name,
                            'required_qty' => round($required_qty, 4),
                            'available_RM' => round($availableRM, 4),
                            'available_MC' => round($availableMC, 4),
                            'total_available' => round($total_available, 4),
                            'shortage' => round($shortage, 4),
                            'mat_type' => 'Raw Material',
                            'unit' => $unit,
                            'reason' => 'Insufficient stock'
                        ];
                    }
                }
                
                // Add to deductions array with detailed info. Store the amounts actually
                // consumed (RM first, then mother code), not full stock, so that
                // plan_qty = deducted_from_RM + deducted_from_MC + shortage.
                $deductions[] = [
                    'material_code' => $material_code,
                    'material_name' => $material_name,
                    'mat_type' => 'Raw Material',
                    'plan_qty' => round($required_qty, 4),
                    'unit' => $unit,
                    'deducted_from_RM' => round($usedRM, 4),
                    'deducted_from_MC' => round($usedMC, 4),
                    'shortage' => round($shortage, 4),
                    'qty_per_batch' => round($qty_per_batch, 4),
                    'number_of_batches' => round($number_of_batches, 2),
                    'is_bulk' => $isBulk
                ];
            }
        }
    }
    
    // Fetch Packing Materials
    $pmSql = "SELECT a.*,
             COALESCE((SELECT material_name FROM material WHERE material_code=a.material_code),
                     (SELECT bulkName FROM bulkMaster WHERE bulkCode=a.material_code)) AS material_name
             FROM batch_materials a
             WHERE a.material_type = 'Packing Material'
             AND a.bfr_no = '".$conn->real_escape_string($bfr_no)."'";
    
    $pmResult = $conn->query($pmSql);
    if ($pmResult && $pmResult->num_rows > 0) {
        while ($pmRow = $pmResult->fetch_assoc()) {
            $material_code = $pmRow['material_code'] ?? '';
            $qty_per_batch = floatval($pmRow['qty'] ?? 0);
            $required_qty = $qty_per_batch * $number_of_batches;
            $unit = $pmRow['unit'] ?? '';
            
            if ($material_code && $required_qty > 0) {
                $availableRM = 0;
                $availableMC = 0;
                
                 $pmStockSql = "SELECT available_qty FROM vw_total_available_stock 
                              WHERE material_code = '".$conn->real_escape_string($material_code)."' 
                              AND plant_id = '".$conn->real_escape_string($plant_id)."' LIMIT 1";
                $pmStockResult = $conn->query($pmStockSql);
                if ($pmStockResult && $pmStockResult->num_rows > 0) {
                    $pmStockRow = $pmStockResult->fetch_assoc();
                    $availableRM = floatval($pmStockRow['available_qty'] ?? 0);
                }
                
                // Packing material has no mother code; consume from its own stock only.
                $usedRM = min($required_qty, $availableRM);
                $total_available = $usedRM;
                $shortage = max($required_qty - $usedRM, 0);
                
                if ($shortage > 0 && !empty($material_code)) {
                    $hasShortage = true;
                    $allMaterialsComplete = false;
                    
                    $shortageInfo[] = [
                        'material_code' => $material_code,
                        'material_name' => $pmRow['material_name'] ?? $material_code,
                        'required_qty' => round($required_qty, 4),
                        'available_RM' => round($availableRM, 4),
                        'available_MC' => round($availableMC, 4),
                        'total_available' => round($total_available, 4),
                        'shortage' => round($shortage, 4),
                        'mat_type' => 'Packing Material',
                        'unit' => $unit,
                        'reason' => 'Insufficient packing material stock'
                    ];
                }
                
                $deductions[] = [
                    'material_code' => $material_code,
                    'material_name' => $pmRow['material_name'] ?? $material_code,
                    'mat_type' => 'Packing Material',
                    'plan_qty' => round($required_qty, 4),
                    'unit' => $unit,
                    'deducted_from_RM' => round($usedRM, 4),
                    'deducted_from_MC' => round($availableMC, 4),
                    'shortage' => round($shortage, 4),
                    'qty_per_batch' => round($qty_per_batch, 4),
                    'number_of_batches' => round($number_of_batches, 2)
                ];
            }
        }
    }
    
    // Filter out any entries with empty material_code
    $shortageInfo = array_filter($shortageInfo, function($item) {
        return !empty($item['material_code']) && $item['material_code'] !== '-';
    });
    // Re-index array after filtering
    $shortageInfo = array_values($shortageInfo);
    
    // Update hasShortage flag based on filtered shortageInfo
    $hasShortage = count($shortageInfo) > 0;
    
    // Update work order status based on whether there are shortages
    if ($hasShortage) {
        // Keep as Pending Verification if there are shortages
        $status = 'Pending Verification';
        $message = 'Stock verification completed. ' . count($shortageInfo) . ' material(s) have shortages.';
    } else {
        // Update to Verified - Ready for Batch Allocation if no shortages
        $status = 'Verified - Ready for Batch Allocation';
        $message = 'Stock verification completed. All materials available.';
        
        $updateSql = "UPDATE Work_order_materials 
                     SET status = '".$status."',
                         stock_verified_by = '".$conn->real_escape_string($_GET["emp_id"])."',
                         stock_verified_on = '".$entry_date."'
                     WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."'";
        if (!$conn->query($updateSql)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Stock checked but failed to update work order: ' . $conn->error
            ]);
            exit;
        }
    }
    
    // Audit trail: snapshot every material verified for this WO (required vs on-day
    // available vs shortage), plus a WO-level verification outcome event.
    if (function_exists('gw_mrp_audit_log') && is_array($deductions)) {
        foreach ($deductions as $d) {
            if (!is_array($d) || empty($d['material_code'])) { continue; }
            gw_mrp_audit_log($conn, array(
                'stage' => 'VERIFY_STOCK',
                'event_type' => 'Stock verified for WO',
                'source_screen' => 'Verify Stock',
                'workorder_no' => $workorder_no,
                'order_no' => $woRow['order_no'] ?? '',
                'product_code' => $product_code,
                'material_code' => $d['material_code'],
                'material_name' => $d['material_name'] ?? '',
                'material_type' => $d['mat_type'] ?? '',
                'qty_unit' => $d['unit'] ?? '',
                'required_qty' => $d['plan_qty'] ?? null,
                'deducted_qty' => $d['deducted_from_RM'] ?? null,
                'shortage_qty' => $d['shortage'] ?? null,
                'status_to' => $status,
                'snapshot_stock' => true,
                'plant_id' => $plant_id,
                'emp_id' => $_GET['emp_id'] ?? '',
                'remark' => (($d['shortage'] ?? 0) > 0) ? 'Shortage detected at verification' : 'Sufficient stock at verification',
            ));
        }
        gw_mrp_audit_log($conn, array(
            'stage' => 'VERIFY_STOCK',
            'event_type' => $hasShortage ? 'WO verification - shortage' : 'WO verification - ready',
            'source_screen' => 'Verify Stock',
            'workorder_no' => $workorder_no,
            'order_no' => $woRow['order_no'] ?? '',
            'product_code' => $product_code,
            'status_to' => $status,
            'plant_id' => $plant_id,
            'emp_id' => $_GET['emp_id'] ?? '',
            'remark' => $message,
        ));
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'has_shortage' => $hasShortage,
        'stock_booked' => !$hasShortage,
        'verification_status' => $hasShortage ? 'Verified - Shortage' : 'Verified',
        'shortage_count' => count($shortageInfo),
        'shortage_info' => $shortageInfo,
        'deductions' => $deductions,
        'work_order_details' => [
            'workorder_no' => $workorder_no,
            'product_code' => $product_code,
            'batch_size' => $batch_size,
            'batch_unit' => $batch_unit,
            'work_order_planned_qty' => $work_order_planned_qty,
            'number_of_batches' => round($number_of_batches, 2)
        ]
    ]);
}

// Save per-material stock verification (verify + discrepancy) from Verify Stock modal
else if ($_GET["type"] == "saveWoMaterialStockVerification") {
    $input = json_decode(file_get_contents('php://input'), true);
    $workorder_no = trim($input['workorder_no'] ?? '');
    $material_code = trim($input['material_code'] ?? '');
    $discrepancy = strtoupper(trim($input['discrepancy'] ?? ''));
    $remark = trim($input['remark'] ?? '');

    if ($workorder_no === '' || $material_code === '') {
        echo json_encode(['status' => 'error', 'message' => 'Work order and material code are required']);
        exit;
    }
    if (!in_array($discrepancy, ['YES', 'NO'], true)) {
        $discrepancy = 'NO';
    }
    if ($discrepancy === 'YES' && $remark === '') {
        echo json_encode(['status' => 'error', 'message' => 'Remark is required when discrepancy is Yes']);
        exit;
    }

    $entry_date = date('Y-m-d H:i:s');
    $verified_by = trim($_GET['emp_id'] ?? $_GET['user_no'] ?? '');

    $dedSql = "SELECT id, indexData FROM WO_deductions
        WHERE workorder_no = '".$conn->real_escape_string($workorder_no)."'
        AND material_code = '".$conn->real_escape_string($material_code)."'
        LIMIT 1";
    $dedResult = $conn->query($dedSql);
    if (!$dedResult || $dedResult->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Material line not found for this work order']);
        exit;
    }

    $dedRow = $dedResult->fetch_assoc();
    $indexData = json_decode($dedRow['indexData'] ?? '', true);
    if (!is_array($indexData)) {
        $indexData = [];
    }
    $indexData['stock_verification'] = [
        'verified' => true,
        'discrepancy' => $discrepancy,
        'remark' => $discrepancy === 'YES' ? $remark : '',
        'verified_by' => $verified_by,
        'verified_on' => $entry_date,
    ];
    $indexJson = $conn->real_escape_string(json_encode($indexData));

    $updateSql = "UPDATE WO_deductions SET indexData = '$indexJson' WHERE id = ".(int)$dedRow['id'];
    if (!$conn->query($updateSql)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save material verification']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Material verified successfully',
        'stock_verification' => $indexData['stock_verification'],
    ]);
}
else if ($_GET["type"] == "getReconciliationHub") {
    if (!function_exists('ensureMrpRaisedIndentColumns')) {
        function ensureMrpRaisedIndentColumns($conn) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $columns = array(
            'order_no' => 'VARCHAR(150) DEFAULT NULL',
            'product_code' => 'VARCHAR(100) DEFAULT NULL',
        );
        foreach ($columns as $col => $def) {
            $chk = $conn->query("SHOW COLUMNS FROM mrp_raised_indnd_qty LIKE '".$col."'");
            if ($chk && $chk->num_rows === 0) {
                $conn->query("ALTER TABLE mrp_raised_indnd_qty ADD COLUMN `".$col."` ".$def);
            }
        }
    }
        function getActiveForecastIndentQty($conn, $plantId, $materialCode, $orderNo = '', $strictOrder = false) {
        ensureMrpRaisedIndentColumns($conn);
        $plantId = mysqli_real_escape_string($conn, $plantId);
        $materialCode = mysqli_real_escape_string($conn, $materialCode);
        $orderClause = '';
        if (trim((string)$orderNo) !== '') {
            $orderEsc = mysqli_real_escape_string($conn, trim((string)$orderNo));
            if ($strictOrder) {
                $orderClause = " AND (m.order_no = '".$orderEsc."' OR m.order_no LIKE '%".$orderEsc."%')";
            } else {
                $orderClause = " AND (m.order_no = '".$orderEsc."' OR m.order_no LIKE '%".$orderEsc."%' OR m.order_no IS NULL OR TRIM(m.order_no) = '')";
            }
        }
        $sql = "SELECT COALESCE(SUM(CAST(NULLIF(TRIM(m.ordered_qty), '') AS DECIMAL(15,3))), 0) AS qty
            FROM mrp_raised_indnd_qty m
            LEFT JOIN indend_raw ir ON ir.id = m.indend_id
            WHERE m.material_code = '".$materialCode."'
            ".$orderClause."
            AND (m.WO_deductions_id IS NULL OR CAST(m.WO_deductions_id AS UNSIGNED) = 0)
            AND (ir.id IS NULL OR ir.plant_id = '".$plantId."')
            AND (ir.id IS NULL OR LOWER(TRIM(ir.status)) NOT IN ('reject', 'rejected', 'cancel', 'cancelled'))";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            return floatval($res->fetch_assoc()['qty'] ?? 0);
        }
        return 0;
    }
    }

            if (!function_exists('reconNormalizeMonthKey')) {
            function reconMonthNameToNum($month) {
                $m = strtolower(trim((string)$month));
                $map = array(
                    'january' => 1, 'jan' => 1, 'february' => 2, 'feb' => 2,
                    'march' => 3, 'mar' => 3, 'april' => 4, 'apr' => 4,
                    'may' => 5, 'june' => 6, 'jun' => 6, 'july' => 7, 'jul' => 7,
                    'august' => 8, 'aug' => 8, 'september' => 9, 'sep' => 9, 'sept' => 9,
                    'october' => 10, 'oct' => 10, 'november' => 11, 'nov' => 11,
                    'december' => 12, 'dec' => 12,
                );
                if (isset($map[$m])) {
                    return $map[$m];
                }
                if (is_numeric($m) && (int)$m >= 1 && (int)$m <= 12) {
                    return (int)$m;
                }
                return 0;
            }
            function reconNormalizeMonthKey($month, $year) {
                $y = (int)preg_replace('/\D/', '', (string)$year);
                $mn = reconMonthNameToNum($month);
                if ($y > 0 && $mn > 0) {
                    return $y . '-' . str_pad((string)$mn, 2, '0', STR_PAD_LEFT);
                }
                return '';
            }
            function reconNormalizePlanMonth($planMonth) {
                $pm = trim((string)$planMonth);
                if ($pm === '') {
                    return '';
                }
                if (preg_match('/^(\d{4})-(\d{1,2})$/', $pm, $m)) {
                    return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
                }
                if (preg_match('/^(\d{4})-(\d{1,2})-\d{1,2}$/', $pm, $m)) {
                    return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
                }
                if (preg_match('/(\w+)\s+(\d{4})/', $pm, $m)) {
                    return reconNormalizeMonthKey($m[1], $m[2]);
                }
                $ts = strtotime($pm);
                if ($ts !== false) {
                    return date('Y-m', $ts);
                }
                return '';
            }
            function reconGetForecastIndentQty($conn, $plantId, $materialCode, $month = '', $year = '', $orderNo = '') {
                if ($month === '' && $year === '' && $orderNo !== '') {
                    return getActiveForecastIndentQty($conn, $plantId, $materialCode, $orderNo);
                }
                ensureMrpRaisedIndentColumns($conn);
                $plantId = mysqli_real_escape_string($conn, $plantId);
                $materialCode = mysqli_real_escape_string($conn, $materialCode);
                $monthClause = '';
                if ($month !== '' && $year !== '') {
                    $monthEsc = mysqli_real_escape_string($conn, $month);
                    $yearEsc = mysqli_real_escape_string($conn, $year);
                    $monthClause = " AND m.month = '".$monthEsc."' AND m.year = '".$yearEsc."'";
                }
                $orderClause = '';
                if (trim((string)$orderNo) !== '') {
                    $orderEsc = mysqli_real_escape_string($conn, trim((string)$orderNo));
                    $orderClause = " AND (m.order_no = '".$orderEsc."' OR m.order_no LIKE '%".$orderEsc."%' OR m.order_no IS NULL OR TRIM(m.order_no) = '')";
                }
                $sql = "SELECT COALESCE(SUM(CAST(NULLIF(TRIM(m.ordered_qty), '') AS DECIMAL(15,3))), 0) AS qty
                    FROM mrp_raised_indnd_qty m
                    LEFT JOIN indend_raw ir ON ir.id = m.indend_id
                    WHERE m.material_code = '".$materialCode."'
                    ".$monthClause.$orderClause."
                    AND (m.WO_deductions_id IS NULL OR CAST(m.WO_deductions_id AS UNSIGNED) = 0)
                    AND (ir.id IS NULL OR ir.plant_id = '".$plantId."')
                    AND (ir.id IS NULL OR LOWER(TRIM(ir.status)) NOT IN ('reject', 'rejected', 'cancel', 'cancelled'))";
                $res = $conn->query($sql);
                if ($res && $res->num_rows > 0) {
                    return floatval($res->fetch_assoc()['qty'] ?? 0);
                }
                return 0;
            }
            function reconMatchStatusLabel($splitQty, $woQty, $hasSplit, $hasWo) {
                $splitQty = floatval($splitQty);
                $woQty = floatval($woQty);
                if ($hasSplit && !$hasWo) {
                    return 'Unmatched Forecast';
                }
                if (!$hasSplit && $hasWo) {
                    return 'Confirmed Only (No Forecast)';
                }
                if (!$hasSplit && !$hasWo) {
                    return 'Unknown';
                }
                if ($woQty <= 0) {
                    return 'WO Pending';
                }
                if (abs($woQty - $splitQty) < 0.001) {
                    return 'Full Match';
                }
                if ($woQty < $splitQty) {
                    return 'Partial Match';
                }
                return 'WO Exceeds Forecast';
            }
        }

        $tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'report';
        $plantId = mysqli_real_escape_string($conn, $_GET['plant_id'] ?? '');
        $yearFilter = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';
        $monthFilter = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : '';
        $orderFilter = isset($_GET['order_no']) ? mysqli_real_escape_string($conn, $_GET['order_no']) : '';

        $output = array('tab' => $tab, 'rows' => array(), 'summary' => array());

        if ($tab === 'report') {
            ensureMrpRaisedIndentColumns($conn);
            $reportAsOf = date('d M Y, h:i A');
            $bucket = array();

            $ensureReport = function ($key) use (&$bucket) {
                if ($key === '' || isset($bucket[$key])) {
                    return;
                }
                $bucket[$key] = array(
                    'order_no' => '',
                    'product_code' => '',
                    'product_name' => '',
                    'month_year' => '',
                    'forecast_month' => '',
                    'forecast_year' => '',
                    'forecast_qty' => 0,
                    'forecast_indent_qty' => 0,
                    'forecast_indent_lines' => 0,
                    'forecast_po_status' => '—',
                    'wo_qty' => 0,
                    'wo_count' => 0,
                    'workorder_nos' => array(),
                    'wo_status' => '',
                    'order_status' => '',
                    'gross_wo_shortage' => 0,
                    'forecast_offset' => 0,
                    'net_shortage' => 0,
                    'confirmed_indent_qty' => 0,
                    'issued_qty' => 0,
                    'match_status' => '',
                    'lifecycle_stage' => '',
                    'current_situation' => '',
                    'report_as_of' => '',
                );
            };

            $patchReport = function ($key, $patch) use (&$bucket, $ensureReport) {
                $ensureReport($key);
                foreach ($patch as $k => $v) {
                    if ($k === 'workorder_nos' && is_array($v)) {
                        $bucket[$key]['workorder_nos'] = array_values(array_unique(array_merge(
                            $bucket[$key]['workorder_nos'],
                            $v
                        )));
                    } elseif (in_array($k, array('forecast_qty', 'forecast_indent_qty', 'forecast_indent_lines',
                        'wo_qty', 'wo_count', 'gross_wo_shortage', 'forecast_offset', 'net_shortage',
                        'confirmed_indent_qty', 'issued_qty'), true)) {
                        $bucket[$key][$k] = floatval($bucket[$key][$k] ?? 0) + floatval($v);
                    } elseif ($v !== '' && $v !== null && ($bucket[$key][$k] === '' || $bucket[$key][$k] === '—' || $bucket[$key][$k] === null)) {
                        $bucket[$key][$k] = $v;
                    }
                }
            };

            $splitSql = "SELECT sp.order_no, sp.product_code,
                    MAX(COALESCE(p.product_name, sp.product_name)) AS product_name,
                    sp.month, sp.year, SUM(sp.oder_qty) AS split_qty,
                    MAX(om.status) AS order_status
                FROM split_planning_qty sp
                LEFT JOIN product p ON sp.product_code = p.product_code
                LEFT JOIN order_materials om ON sp.order_no = om.order_no AND sp.product_code = om.product_code
                    AND om.plant_id = sp.plant_id
                WHERE sp.plant_id = '".$plantId."'";
            if ($yearFilter !== '') {
                $splitSql .= " AND sp.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $splitSql .= " AND sp.month = '".$monthFilter."'";
            }
            if ($orderFilter !== '') {
                $splitSql .= " AND sp.order_no = '".$orderFilter."'";
            }
            $splitSql .= " GROUP BY sp.order_no, sp.product_code, sp.month, sp.year
                ORDER BY sp.year DESC, sp.month, sp.order_no, sp.product_code";
            $splitRes = $conn->query($splitSql);
            if ($splitRes && $splitRes->num_rows > 0) {
                while ($sp = $splitRes->fetch_assoc()) {
                    $monthYear = trim(($sp['month'] ?? '') . ' ' . ($sp['year'] ?? ''));
                    $key = ($sp['order_no'] ?? '') . '|' . ($sp['product_code'] ?? '') . '|' . $monthYear;
                    $patchReport($key, array(
                        'order_no' => $sp['order_no'] ?? '',
                        'product_code' => $sp['product_code'] ?? '',
                        'product_name' => $sp['product_name'] ?? '',
                        'month_year' => $monthYear,
                        'forecast_month' => $sp['month'] ?? '',
                        'forecast_year' => $sp['year'] ?? '',
                        'forecast_qty' => floatval($sp['split_qty'] ?? 0),
                        'order_status' => $sp['order_status'] ?? '',
                    ));
                }
            }

            $fIndentSql = "SELECT m.order_no, m.product_code, m.month, m.year,
                    SUM(CAST(NULLIF(TRIM(m.ordered_qty), '') AS DECIMAL(15,3))) AS indent_qty,
                    COUNT(*) AS line_count,
                    GROUP_CONCAT(DISTINCT COALESCE(ir.status, m.status) SEPARATOR ', ') AS indent_statuses,
                    GROUP_CONCAT(DISTINCT po.status SEPARATOR ', ') AS po_statuses
                FROM mrp_raised_indnd_qty m
                LEFT JOIN indend_raw ir ON ir.id = m.indend_id
                LEFT JOIN purchaseorder po ON ir.indend_no = po.indent_no
                WHERE (ir.plant_id = '".$plantId."' OR ir.id IS NULL)
                AND (m.WO_deductions_id IS NULL OR CAST(m.WO_deductions_id AS UNSIGNED) = 0)
                AND (ir.id IS NULL OR LOWER(TRIM(ir.status)) NOT IN ('reject', 'rejected', 'cancel', 'cancelled'))";
            if ($yearFilter !== '') {
                $fIndentSql .= " AND m.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $fIndentSql .= " AND m.month = '".$monthFilter."'";
            }
            if ($orderFilter !== '') {
                $fIndentSql .= " AND m.order_no = '".$orderFilter."'";
            }
            $fIndentSql .= " GROUP BY m.order_no, m.product_code, m.month, m.year";
            $fIndentRes = $conn->query($fIndentSql);
            if ($fIndentRes && $fIndentRes->num_rows > 0) {
                while ($fi = $fIndentRes->fetch_assoc()) {
                    $monthYear = trim(($fi['month'] ?? '') . ' ' . ($fi['year'] ?? ''));
                    $key = ($fi['order_no'] ?? '') . '|' . ($fi['product_code'] ?? '') . '|' . $monthYear;
                    $poStat = trim($fi['po_statuses'] ?? '');
                    $patchReport($key, array(
                        'order_no' => $fi['order_no'] ?? '',
                        'product_code' => $fi['product_code'] ?? '',
                        'month_year' => $monthYear,
                        'forecast_month' => $fi['month'] ?? '',
                        'forecast_year' => $fi['year'] ?? '',
                        'forecast_indent_qty' => floatval($fi['indent_qty'] ?? 0),
                        'forecast_indent_lines' => intval($fi['line_count'] ?? 0),
                        'forecast_po_status' => $poStat !== '' ? $poStat : trim($fi['indent_statuses'] ?? 'Indent Raised'),
                    ));
                }
            }

            $woSql = "SELECT wom.order_no, wom.product_code, wom.planMonth,
                    MAX(COALESCE(p.product_name, wom.product_name)) AS product_name,
                    SUM(wom.work_order_planned_qty) AS wo_qty,
                    COUNT(DISTINCT wom.workorder_no) AS wo_count,
                    GROUP_CONCAT(DISTINCT wom.workorder_no ORDER BY wom.workorder_no SEPARATOR ', ') AS workorder_nos,
                    GROUP_CONCAT(DISTINCT wom.status SEPARATOR ', ') AS wo_status,
                    MAX(om.status) AS order_status
                FROM Work_order_materials wom
                LEFT JOIN product p ON wom.product_code = p.product_code
                LEFT JOIN order_materials om ON wom.order_no = om.order_no AND wom.product_code = om.product_code
                    AND om.plant_id = wom.plant_id
                WHERE wom.plant_id = '".$plantId."'
                AND wom.send_for_analysis_by IS NOT NULL
                AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')";
            if ($orderFilter !== '') {
                $woSql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $woSql .= " GROUP BY wom.order_no, wom.product_code, wom.planMonth";
            $woRes = $conn->query($woSql);
            $woMonthNames = array('', 'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December');
            if ($woRes && $woRes->num_rows > 0) {
                while ($wo = $woRes->fetch_assoc()) {
                    $planKey = reconNormalizePlanMonth($wo['planMonth'] ?? '');
                    $forecastMonth = '';
                    $forecastYear = '';
                    if ($planKey !== '' && strpos($planKey, '-') !== false) {
                        $parts = explode('-', $planKey);
                        $forecastYear = $parts[0];
                        $forecastMonth = $woMonthNames[(int)$parts[1]] ?? $parts[1];
                    }
                    $monthYear = trim($forecastMonth . ' ' . $forecastYear);
                    if ($monthYear === '') {
                        $monthYear = trim($wo['planMonth'] ?? '');
                    }
                    if ($yearFilter !== '' && $forecastYear !== '' && $forecastYear !== $yearFilter) {
                        continue;
                    }
                    if ($monthFilter !== '' && $forecastMonth !== '' && strcasecmp($forecastMonth, $monthFilter) !== 0) {
                        continue;
                    }
                    $key = ($wo['order_no'] ?? '') . '|' . ($wo['product_code'] ?? '') . '|' . $monthYear;
                    $woNos = array_filter(array_map('trim', explode(',', $wo['workorder_nos'] ?? '')));
                    $patchReport($key, array(
                        'order_no' => $wo['order_no'] ?? '',
                        'product_code' => $wo['product_code'] ?? '',
                        'product_name' => $wo['product_name'] ?? '',
                        'month_year' => $monthYear,
                        'forecast_month' => $forecastMonth,
                        'forecast_year' => $forecastYear,
                        'wo_qty' => floatval($wo['wo_qty'] ?? 0),
                        'wo_count' => intval($wo['wo_count'] ?? 0),
                        'workorder_nos' => $woNos,
                        'wo_status' => $wo['wo_status'] ?? '',
                        'order_status' => $wo['order_status'] ?? '',
                    ));
                }
            }

            $dedSql = "SELECT wom.order_no, wom.product_code, wom.planMonth, wom.workorder_no,
                    wd.material_code, wd.plan_qty AS batch_plan_qty, wd.shortage AS stored_shortage
                FROM WO_deductions wd
                INNER JOIN Work_order_materials wom ON wd.workorder_no = wom.workorder_no AND wd.plant_id = wom.plant_id
                WHERE wd.plant_id = '".$plantId."'
                AND wom.send_for_analysis_by IS NOT NULL
                AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')
                AND wd.status NOT IN ('Cancel', 'Hold', 'Rejected')";
            if ($orderFilter !== '') {
                $dedSql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $dedRes = $conn->query($dedSql);
            $shortageByKey = array();
            if ($dedRes && $dedRes->num_rows > 0) {
                while ($ded = $dedRes->fetch_assoc()) {
                    $planKey = reconNormalizePlanMonth($ded['planMonth'] ?? '');
                    $fMonth = '';
                    $fYear = '';
                    if ($planKey !== '' && strpos($planKey, '-') !== false) {
                        $parts = explode('-', $planKey);
                        $fYear = $parts[0];
                        $fMonth = $woMonthNames[(int)$parts[1]] ?? $parts[1];
                    }
                    $monthYear = trim($fMonth . ' ' . $fYear);
                    if ($monthYear === '') {
                        $monthYear = trim($ded['planMonth'] ?? '');
                    }
                    $rKey = ($ded['order_no'] ?? '') . '|' . ($ded['product_code'] ?? '') . '|' . $monthYear;
                    if (!isset($shortageByKey[$rKey])) {
                        $shortageByKey[$rKey] = array('gross' => 0, 'offset' => 0, 'net' => 0);
                    }
                    $live = shortagesCalcDeductionLine($conn, $plantId, $ded);
                    $gross = shortagesNonNeg($live['shortage'] ?? $ded['stored_shortage'] ?? 0);
                    $offset = reconGetForecastIndentQty(
                        $conn, $plantId, $ded['material_code'] ?? '', $fMonth, $fYear, $ded['order_no'] ?? ''
                    );
                    if ($offset <= 0) {
                        $offset = getActiveForecastIndentQty($conn, $plantId, $ded['material_code'] ?? '', $ded['order_no'] ?? '', true);
                    }
                    $net = shortagesNonNeg($gross - $offset);
                    $shortageByKey[$rKey]['gross'] += $gross;
                    $shortageByKey[$rKey]['offset'] += $offset;
                    $shortageByKey[$rKey]['net'] += $net;
                }
            }
            foreach ($shortageByKey as $rKey => $sh) {
                $patchReport($rKey, array(
                    'gross_wo_shortage' => $sh['gross'],
                    'forecast_offset' => $sh['offset'],
                    'net_shortage' => $sh['net'],
                ));
            }

            $cIndentSql = "SELECT m.order_no, m.product_code, m.month, m.year,
                    SUM(CAST(NULLIF(TRIM(m.ordered_qty), '') AS DECIMAL(15,3))) AS indent_qty
                FROM mrp_raised_indnd_qty m
                LEFT JOIN indend_raw ir ON ir.id = m.indend_id
                WHERE (ir.plant_id = '".$plantId."' OR ir.id IS NULL)
                AND CAST(m.WO_deductions_id AS UNSIGNED) > 0
                AND (ir.id IS NULL OR LOWER(TRIM(ir.status)) NOT IN ('reject', 'rejected', 'cancel', 'cancelled'))";
            if ($yearFilter !== '') {
                $cIndentSql .= " AND m.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $cIndentSql .= " AND m.month = '".$monthFilter."'";
            }
            if ($orderFilter !== '') {
                $cIndentSql .= " AND m.order_no = '".$orderFilter."'";
            }
            $cIndentSql .= " GROUP BY m.order_no, m.product_code, m.month, m.year";
            $cIndentRes = $conn->query($cIndentSql);
            if ($cIndentRes && $cIndentRes->num_rows > 0) {
                while ($ci = $cIndentRes->fetch_assoc()) {
                    $monthYear = trim(($ci['month'] ?? '') . ' ' . ($ci['year'] ?? ''));
                    $key = ($ci['order_no'] ?? '') . '|' . ($ci['product_code'] ?? '') . '|' . $monthYear;
                    $patchReport($key, array(
                        'order_no' => $ci['order_no'] ?? '',
                        'product_code' => $ci['product_code'] ?? '',
                        'month_year' => $monthYear,
                        'confirmed_indent_qty' => floatval($ci['indent_qty'] ?? 0),
                    ));
                }
            }

            $issueSql = "SELECT wom.order_no, wom.product_code, wom.planMonth,
                    SUM(mi.qty) AS issued_qty
                FROM material_issue mi
                INNER JOIN Work_order_materials wom ON wom.workorder_no = mi.workorder_no AND wom.plant_id = mi.plant_id
                WHERE mi.plant_id = '".$plantId."'";
            if ($orderFilter !== '') {
                $issueSql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $issueSql .= " GROUP BY wom.order_no, wom.product_code, wom.planMonth";
            $issueRes = $conn->query($issueSql);
            if ($issueRes && $issueRes->num_rows > 0) {
                while ($is = $issueRes->fetch_assoc()) {
                    $planKey = reconNormalizePlanMonth($is['planMonth'] ?? '');
                    $fMonth = '';
                    $fYear = '';
                    if ($planKey !== '' && strpos($planKey, '-') !== false) {
                        $parts = explode('-', $planKey);
                        $fYear = $parts[0];
                        $fMonth = $woMonthNames[(int)$parts[1]] ?? $parts[1];
                    }
                    $monthYear = trim($fMonth . ' ' . $fYear);
                    if ($monthYear === '') {
                        $monthYear = trim($is['planMonth'] ?? '');
                    }
                    $key = ($is['order_no'] ?? '') . '|' . ($is['product_code'] ?? '') . '|' . $monthYear;
                    $patchReport($key, array(
                        'order_no' => $is['order_no'] ?? '',
                        'product_code' => $is['product_code'] ?? '',
                        'month_year' => $monthYear,
                        'issued_qty' => floatval($is['issued_qty'] ?? 0),
                    ));
                }
            }

            foreach ($bucket as $key => &$row) {
                $row['workorder_nos'] = implode(', ', $row['workorder_nos'] ?? array());
                $row['forecast_qty'] = round(floatval($row['forecast_qty'] ?? 0), 3);
                $row['forecast_indent_qty'] = round(floatval($row['forecast_indent_qty'] ?? 0), 3);
                $row['wo_qty'] = round(floatval($row['wo_qty'] ?? 0), 3);
                $row['gross_wo_shortage'] = round(floatval($row['gross_wo_shortage'] ?? 0), 3);
                $row['forecast_offset'] = round(floatval($row['forecast_offset'] ?? 0), 3);
                $row['net_shortage'] = round(floatval($row['net_shortage'] ?? 0), 3);
                $row['confirmed_indent_qty'] = round(floatval($row['confirmed_indent_qty'] ?? 0), 3);
                $row['issued_qty'] = round(floatval($row['issued_qty'] ?? 0), 3);
                $row['qty_gap'] = round($row['forecast_qty'] - $row['wo_qty'], 3);
                $row['report_as_of'] = $reportAsOf;

                $hasForecast = $row['forecast_qty'] > 0;
                $hasWo = $row['wo_qty'] > 0 || $row['workorder_nos'] !== '';
                $row['match_status'] = reconMatchStatusLabel(
                    $row['forecast_qty'],
                    $row['wo_qty'],
                    $hasForecast,
                    $hasWo
                );

                $stage = 'Unknown';
                if ($hasForecast && !$hasWo) {
                    if ($row['forecast_indent_qty'] > 0) {
                        $stage = 'Awaiting Confirmed Order';
                    } else {
                        $stage = 'Forecast Saved — No Indent';
                    }
                } elseif (!$hasForecast && $hasWo) {
                    $stage = 'Confirmed Only (No Forecast)';
                } elseif ($hasForecast && $hasWo) {
                    if ($row['issued_qty'] > 0) {
                        $stage = 'In Production';
                    } elseif ($row['net_shortage'] > 0) {
                        $stage = 'WO Received — Shortage Pending';
                    } elseif ($row['confirmed_indent_qty'] > 0 || $row['forecast_indent_qty'] > 0) {
                        $stage = 'WO Received — Procured';
                    } else {
                        $stage = 'WO Received — In Process';
                    }
                } elseif ($hasForecast) {
                    $stage = 'Forecast Only';
                }
                $row['lifecycle_stage'] = $stage;

                $parts = array();
                if ($hasForecast) {
                    $parts[] = 'Forecasted ' . $row['forecast_qty'] . ' units for ' . $row['month_year'];
                }
                if ($row['forecast_indent_qty'] > 0) {
                    $parts[] = 'forecast indent ' . $row['forecast_indent_qty'] . ' (' . $row['forecast_indent_lines'] . ' material line(s))';
                }
                if ($hasWo) {
                    $parts[] = 'confirmed WO qty ' . $row['wo_qty'] . ($row['workorder_nos'] !== '' ? ' [' . $row['workorder_nos'] . ']' : '');
                } elseif ($hasForecast) {
                    $parts[] = 'no confirmed work order received yet';
                }
                if ($row['forecast_offset'] > 0) {
                    $parts[] = 'forecast indent offset ' . $row['forecast_offset'] . ' against WO shortage';
                }
                if ($row['net_shortage'] > 0) {
                    $parts[] = 'net shortage today ' . $row['net_shortage'];
                } elseif ($row['gross_wo_shortage'] > 0 && $row['forecast_offset'] > 0) {
                    $parts[] = 'WO shortage fully covered by forecast';
                }
                if ($row['confirmed_indent_qty'] > 0) {
                    $parts[] = 'confirmed indent ' . $row['confirmed_indent_qty'];
                }
                if ($row['issued_qty'] > 0) {
                    $parts[] = 'material issued ' . $row['issued_qty'];
                }
                if (empty($parts)) {
                    $parts[] = 'No forecast or confirmed activity';
                }
                $row['current_situation'] = implode('. ', $parts) . '.';
            }
            unset($row);

            $reportRows = array_values($bucket);
            usort($reportRows, function ($a, $b) {
                $c = strcmp((string)($b['forecast_year'] ?? ''), (string)($a['forecast_year'] ?? ''));
                if ($c !== 0) {
                    return $c;
                }
                return strcmp((string)($a['order_no'] ?? ''), (string)($b['order_no'] ?? ''));
            });

            $output['rows'] = $reportRows;
            $output['summary'] = array(
                'total' => count($reportRows),
                'report_as_of' => $reportAsOf,
                'awaiting_confirmed' => count(array_filter($reportRows, function ($r) {
                    return ($r['lifecycle_stage'] ?? '') === 'Awaiting Confirmed Order';
                })),
                'forecast_only' => count(array_filter($reportRows, function ($r) {
                    return strpos($r['lifecycle_stage'] ?? '', 'Forecast') !== false
                        && floatval($r['wo_qty'] ?? 0) <= 0;
                })),
                'wo_received' => count(array_filter($reportRows, function ($r) {
                    return floatval($r['wo_qty'] ?? 0) > 0 || ($r['workorder_nos'] ?? '') !== '';
                })),
                'shortage_pending' => count(array_filter($reportRows, function ($r) {
                    return floatval($r['net_shortage'] ?? 0) > 0;
                })),
                'in_production' => count(array_filter($reportRows, function ($r) {
                    return floatval($r['issued_qty'] ?? 0) > 0;
                })),
                'full_match' => count(array_filter($reportRows, function ($r) {
                    return ($r['match_status'] ?? '') === 'Full Match';
                })),
                'unmatched' => count(array_filter($reportRows, function ($r) {
                    return strpos($r['match_status'] ?? '', 'Unmatched') !== false;
                })),
            );
        }
        else if ($tab === 'map') {
            $where = " WHERE sp.plant_id = '".$plantId."'";
            if ($yearFilter !== '') {
                $where .= " AND sp.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $where .= " AND sp.month = '".$monthFilter."'";
            }
            if ($orderFilter !== '') {
                $where .= " AND sp.order_no = '".$orderFilter."'";
            }

            $sql = "SELECT sp.id AS split_id, sp.order_no, sp.product_code, sp.product_name,
                    sp.month AS forecast_month, sp.year AS forecast_year, sp.oder_qty AS split_qty,
                    om.status AS order_status, om.planMonth AS order_plan_month, om.planQty AS order_qty,
                    wom.workorder_no, wom.work_order_planned_qty AS wo_qty, wom.planMonth AS wo_plan_month,
                    wom.status AS wo_status, wom.Fo_code,
                    COALESCE(p.product_name, sp.product_name) AS product_name_ref
                FROM split_planning_qty sp
                LEFT JOIN order_materials om ON sp.order_no = om.order_no AND sp.product_code = om.product_code
                    AND om.plant_id = sp.plant_id
                LEFT JOIN product p ON sp.product_code = p.product_code
                LEFT JOIN Work_order_materials wom ON sp.order_no = wom.order_no AND sp.product_code = wom.product_code
                    AND wom.plant_id = sp.plant_id
                    AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')
                ".$where."
                ORDER BY sp.year DESC, sp.month, sp.order_no, sp.product_code, wom.workorder_no";

            $grouped = array();
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $key = ($row['order_no'] ?? '') . '|' . ($row['product_code'] ?? '') . '|'
                        . ($row['forecast_month'] ?? '') . '|' . ($row['forecast_year'] ?? '');
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = $row;
                        $grouped[$key]['wo_nos'] = array();
                        $grouped[$key]['wo_qty_total'] = 0;
                    }
                    if (!empty($row['workorder_no'])) {
                        $grouped[$key]['wo_nos'][] = $row['workorder_no'];
                        $grouped[$key]['wo_qty_total'] += floatval($row['wo_qty'] ?? 0);
                    }
                }
            }

            foreach ($grouped as $g) {
                $splitKey = reconNormalizeMonthKey($g['forecast_month'] ?? '', $g['forecast_year'] ?? '');
                $woMonthKey = reconNormalizePlanMonth($g['wo_plan_month'] ?? $g['order_plan_month'] ?? '');
                $monthAligned = ($splitKey !== '' && $woMonthKey !== '' && $splitKey === $woMonthKey)
                    || ($woMonthKey === '' && !empty($g['wo_nos']));
                $g['month_key'] = $splitKey;
                $g['wo_month_key'] = $woMonthKey;
                $g['month_aligned'] = $monthAligned ? 'Yes' : 'No';
                $g['wo_nos'] = array_values(array_unique($g['wo_nos']));
                $g['workorder_nos'] = implode(', ', $g['wo_nos']);
                $g['match_status'] = reconMatchStatusLabel(
                    $g['split_qty'] ?? 0,
                    $g['wo_qty_total'] ?? 0,
                    true,
                    !empty($g['wo_nos'])
                );
                $output['rows'][] = $g;
            }

            $woOnlySql = "SELECT wom.order_no, wom.product_code, wom.workorder_no, wom.work_order_planned_qty AS wo_qty,
                    wom.planMonth AS wo_plan_month, wom.status AS wo_status, wom.Fo_code, wom.mainGroupName AS client_name,
                    p.product_name AS product_name_ref
                FROM Work_order_materials wom
                LEFT JOIN product p ON wom.product_code = p.product_code
                LEFT JOIN split_planning_qty sp ON wom.order_no = sp.order_no AND wom.product_code = sp.product_code
                    AND wom.plant_id = sp.plant_id
                WHERE wom.plant_id = '".$plantId."'
                AND sp.id IS NULL
                AND wom.send_for_analysis_by IS NOT NULL
                AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')";
            if ($orderFilter !== '') {
                $woOnlySql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $woOnlySql .= " ORDER BY wom.order_no, wom.product_code, wom.workorder_no";
            $woRes = $conn->query($woOnlySql);
            if ($woRes && $woRes->num_rows > 0) {
                while ($row = $woRes->fetch_assoc()) {
                    $row['forecast_month'] = '';
                    $row['forecast_year'] = '';
                    $row['split_qty'] = 0;
                    $row['wo_qty_total'] = floatval($row['wo_qty'] ?? 0);
                    $row['workorder_nos'] = $row['workorder_no'] ?? '';
                    $row['month_key'] = reconNormalizePlanMonth($row['wo_plan_month'] ?? '');
                    $row['month_aligned'] = 'N/A';
                    $row['match_status'] = 'Confirmed Only (No Forecast)';
                    $output['rows'][] = $row;
                }
            }

            $output['summary'] = array(
                'total' => count($output['rows']),
                'full_match' => count(array_filter($output['rows'], function ($r) {
                    return ($r['match_status'] ?? '') === 'Full Match';
                })),
                'unmatched' => count(array_filter($output['rows'], function ($r) {
                    return strpos($r['match_status'] ?? '', 'Unmatched') !== false
                        || strpos($r['match_status'] ?? '', 'No Forecast') !== false;
                })),
            );
        }
        else if ($tab === 'forecast') {
            $where = " WHERE (ir.plant_id = '".$plantId."' OR ir.plant_id IS NULL)";
            if ($yearFilter !== '') {
                $where .= " AND m.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $where .= " AND m.month = '".$monthFilter."'";
            }
            $sql = "SELECT m.id, m.material_code, m.material_name, m.material_type, m.month, m.year,
                    m.order_no, m.product_code,
                    m.ordered_qty, m.status AS mrp_line_status, m.entry_date, m.entry_by,
                    m.indend_id, m.WO_deductions_id,
                    ir.indend_no, ir.request_no, ir.status AS indent_status, ir.entry_date AS indent_date,
                    ir.purpose, ir.department,
                    po.po_no, po.status AS po_status
                FROM mrp_raised_indnd_qty m
                LEFT JOIN indend_raw ir ON m.indend_id = ir.id
                LEFT JOIN purchaseorder po ON ir.indend_no = po.indent_no
                ".$where."
                AND (m.WO_deductions_id IS NULL OR CAST(m.WO_deductions_id AS UNSIGNED) = 0)
                ORDER BY m.year DESC, m.month, m.material_code, m.id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['indent_type'] = 'Forecast MRP';
                    $row['month_year'] = trim(($row['month'] ?? '') . '-' . ($row['year'] ?? ''));
                    $stock = forecastGetMaterialStockBreakdown($conn, $plantId, $row['material_code'] ?? '');
                    $row['net_available_qty'] = $stock['net_available_qty'];
                    $row['booked_qty'] = $stock['booked_qty'];
                    $output['rows'][] = $row;
                }
            }
            $output['summary'] = array('total' => count($output['rows']));
        }
        else if ($tab === 'confirmed') {
            $sql = "SELECT wd.id AS deduction_id, wd.material_code, wd.workorder_no, wd.plan_qty AS batch_plan_qty,
                    wd.shortage AS stored_shortage, wd.indent_status, wd.indent_no, wd.indent_id,
                    wom.order_no, wom.product_code, wom.planMonth, wom.Fo_code, wom.mainGroupName AS client_name,
                    wom.status AS wo_status, wd.mat_type,
                    COALESCE(
                        NULLIF(TRIM(wd.material_name), ''),
                        (SELECT material_name FROM material WHERE material_code = wd.material_code),
                        (SELECT bulkName FROM bulkmaster WHERE bulkCode = wd.material_code),
                        (SELECT product_name FROM product WHERE product_code = wd.material_code)
                    ) AS material_name,
                    COALESCE(NULLIF(TRIM(wom.product_name), ''), p.product_name) AS product_name
                FROM WO_deductions wd
                INNER JOIN Work_order_materials wom ON wd.workorder_no = wom.workorder_no AND wd.plant_id = wom.plant_id
                LEFT JOIN product p ON wom.product_code = p.product_code
                WHERE wd.plant_id = '".$plantId."'
                AND wom.send_for_analysis_by IS NOT NULL
                AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')
                AND wd.status NOT IN ('Cancel', 'Hold', 'Rejected')";
            if ($orderFilter !== '') {
                $sql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $sql .= " ORDER BY wom.order_no, wd.material_code, wd.workorder_no";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $live = shortagesCalcDeductionLine($conn, $plantId, $row);
                    $grossShortage = shortagesNonNeg($live['shortage'] ?? $row['stored_shortage'] ?? 0);
                    $required = shortagesNonNeg($live['required'] ?? $row['batch_plan_qty'] ?? 0);

                    $planKey = reconNormalizePlanMonth($row['planMonth'] ?? '');
                    $forecastMonth = '';
                    $forecastYear = '';
                    if ($planKey !== '' && strpos($planKey, '-') !== false) {
                        $parts = explode('-', $planKey);
                        $forecastYear = $parts[0];
                        $monthNames = array('', 'January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December');
                        $forecastMonth = $monthNames[(int)$parts[1]] ?? $parts[1];
                    }

                    $forecastOffset = reconGetForecastIndentQty(
                        $conn, $plantId, $row['material_code'] ?? '', $forecastMonth, $forecastYear, $row['order_no'] ?? ''
                    );
                    if ($forecastOffset <= 0) {
                        $forecastOffset = getActiveForecastIndentQty($conn, $plantId, $row['material_code'] ?? '', $row['order_no'] ?? '', true);
                    }
                    if ($forecastOffset <= 0) {
                        $forecastOffset = getActiveForecastIndentQty($conn, $plantId, $row['material_code'] ?? '', '', false);
                    }

                    $stock = forecastGetMaterialStockBreakdown($conn, $plantId, $row['material_code'] ?? '');
                    $netShortage = shortagesNonNeg($grossShortage - $forecastOffset);

                    $row['gross_wo_shortage'] = round($grossShortage, 3);
                    $row['wo_required_qty'] = round($required, 3);
                    $row['forecast_indent_offset'] = round($forecastOffset, 3);
                    $row['net_confirmed_shortage'] = round($netShortage, 3);
                    $row['net_available_qty'] = $stock['net_available_qty'];
                    $row['consumption_rule'] = $forecastOffset > 0
                        ? 'Forecast indent eaten first'
                        : 'No forecast indent — full WO shortage';
                    $row['action'] = $netShortage > 0 ? 'Raise Confirmed Indent' : 'Covered by Forecast';

                    if ($grossShortage > 0 || $required > 0) {
                        $output['rows'][] = $row;
                    }
                }
            }
            $output['summary'] = array(
                'total' => count($output['rows']),
                'net_shortage_count' => count(array_filter($output['rows'], function ($r) {
                    return floatval($r['net_confirmed_shortage'] ?? 0) > 0;
                })),
                'covered_by_forecast' => count(array_filter($output['rows'], function ($r) {
                    return floatval($r['net_confirmed_shortage'] ?? 0) <= 0
                        && floatval($r['forecast_indent_offset'] ?? 0) > 0;
                })),
            );
        }
        else if ($tab === 'double') {
            $sql = "SELECT
                    wd.material_code,
                    MAX(COALESCE(mat.material_name, bm.bulkName, pr.product_name)) AS material_name,
                    wom.order_no,
                    GROUP_CONCAT(DISTINCT wom.workorder_no ORDER BY wom.workorder_no SEPARATOR ', ') AS workorder_nos,
                    SUM(GREATEST(COALESCE(wd.shortage, 0), 0)) AS wo_shortage_total,
                    MAX(wd.indent_status) AS wo_indent_status,
                    MAX(wd.indent_no) AS wo_indent_no,
                    (SELECT COALESCE(SUM(CAST(NULLIF(TRIM(m2.ordered_qty), '') AS DECIMAL(15,3))), 0)
                     FROM mrp_raised_indnd_qty m2
                     LEFT JOIN indend_raw ir2 ON ir2.id = m2.indend_id
                     WHERE m2.material_code = wd.material_code
                     AND (m2.WO_deductions_id IS NULL OR CAST(m2.WO_deductions_id AS UNSIGNED) = 0)
                     AND (ir2.id IS NULL OR LOWER(TRIM(ir2.status)) NOT IN ('reject', 'rejected', 'cancel', 'cancelled'))
                    ) AS forecast_indent_qty,
                    (SELECT GROUP_CONCAT(DISTINCT CONCAT(m3.month, '-', m3.year) ORDER BY m3.year, m3.month SEPARATOR ', ')
                     FROM mrp_raised_indnd_qty m3
                     WHERE m3.material_code = wd.material_code
                     AND (m3.WO_deductions_id IS NULL OR CAST(m3.WO_deductions_id AS UNSIGNED) = 0)
                    ) AS forecast_months
                FROM WO_deductions wd
                INNER JOIN Work_order_materials wom ON wd.workorder_no = wom.workorder_no AND wd.plant_id = wom.plant_id
                LEFT JOIN material mat ON mat.material_code = wd.material_code
                LEFT JOIN bulkmaster bm ON bm.bulkCode = wd.material_code
                LEFT JOIN product pr ON pr.product_code = wd.material_code
                WHERE wd.plant_id = '".$plantId."'
                AND wom.send_for_analysis_by IS NOT NULL
                AND COALESCE(wd.shortage, 0) > 0
                AND wd.indent_status IN ('Raised', 'Indent Sent')
                GROUP BY wd.material_code, wom.order_no
                HAVING forecast_indent_qty > 0";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $overlap = min(floatval($row['forecast_indent_qty'] ?? 0), floatval($row['wo_shortage_total'] ?? 0));
                    $row['overlap_qty'] = round($overlap, 3);
                    $row['risk'] = $overlap > 0 ? 'Double Indent Risk' : 'Review';
                    $row['resolve_action'] = 'Net forecast indent against WO shortage before next purchase';
                    $output['rows'][] = $row;
                }
            }
            $output['summary'] = array('total' => count($output['rows']));
        }
        else if ($tab === 'month') {
            $sql = "SELECT sp.year, sp.month, sp.product_code,
                    MAX(sp.product_name) AS product_name, sp.order_no,
                    SUM(sp.oder_qty) AS forecast_qty
                FROM split_planning_qty sp
                WHERE sp.plant_id = '".$plantId."'";
            if ($yearFilter !== '') {
                $sql .= " AND sp.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $sql .= " AND sp.month = '".$monthFilter."'";
            }
            $sql .= " GROUP BY sp.year, sp.month, sp.product_code, sp.order_no
                ORDER BY sp.year DESC, sp.month, sp.product_code";
            $forecastMap = array();
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $key = reconNormalizeMonthKey($row['month'] ?? '', $row['year'] ?? '')
                        . '|' . ($row['product_code'] ?? '') . '|' . ($row['order_no'] ?? '');
                    $forecastMap[$key] = $row;
                    $forecastMap[$key]['confirmed_qty'] = 0;
                    $forecastMap[$key]['wo_count'] = 0;
                }
            }

            $woSql = "SELECT wom.order_no, wom.product_code, wom.planMonth,
                    SUM(wom.work_order_planned_qty) AS confirmed_qty,
                    COUNT(DISTINCT wom.workorder_no) AS wo_count
                FROM Work_order_materials wom
                WHERE wom.plant_id = '".$plantId."'
                AND wom.send_for_analysis_by IS NOT NULL
                AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')
                GROUP BY wom.order_no, wom.product_code, wom.planMonth";
            $woRes = $conn->query($woSql);
            if ($woRes && $woRes->num_rows > 0) {
                while ($row = $woRes->fetch_assoc()) {
                    $planKey = reconNormalizePlanMonth($row['planMonth'] ?? '');
                    $key = $planKey . '|' . ($row['product_code'] ?? '') . '|' . ($row['order_no'] ?? '');
                    if (isset($forecastMap[$key])) {
                        $forecastMap[$key]['confirmed_qty'] = floatval($row['confirmed_qty'] ?? 0);
                        $forecastMap[$key]['wo_count'] = (int)($row['wo_count'] ?? 0);
                    } else {
                        $parts = ($planKey !== '' && strpos($planKey, '-') !== false) ? explode('-', $planKey) : array('', '');
                        $monthNames = array('', 'January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December');
                        $forecastMap[$key] = array(
                            'year' => $parts[0] ?? '',
                            'month' => $monthNames[(int)($parts[1] ?? 0)] ?? '',
                            'product_code' => $row['product_code'] ?? '',
                            'product_name' => '',
                            'order_no' => $row['order_no'] ?? '',
                            'forecast_qty' => 0,
                            'confirmed_qty' => floatval($row['confirmed_qty'] ?? 0),
                            'wo_count' => (int)($row['wo_count'] ?? 0),
                        );
                    }
                }
            }

            foreach ($forecastMap as $row) {
                $forecastQty = floatval($row['forecast_qty'] ?? 0);
                $confirmedQty = floatval($row['confirmed_qty'] ?? 0);
                $row['gap_qty'] = round($forecastQty - $confirmedQty, 3);
                $row['coverage_pct'] = $forecastQty > 0
                    ? round(min(100, ($confirmedQty / $forecastQty) * 100), 1)
                    : ($confirmedQty > 0 ? 100 : 0);
                $row['month_year'] = trim(($row['month'] ?? '') . ' ' . ($row['year'] ?? ''));
                if ($forecastQty > 0 && $confirmedQty >= $forecastQty) {
                    $row['status'] = 'Fully Confirmed';
                } elseif ($confirmedQty > 0) {
                    $row['status'] = 'Partially Confirmed';
                } elseif ($forecastQty > 0) {
                    $row['status'] = 'Forecast Only';
                } else {
                    $row['status'] = 'Confirmed Only';
                }
                $output['rows'][] = $row;
            }
            $output['summary'] = array('total' => count($output['rows']));
        }
        else if ($tab === 'log') {
            $bucket = array();
            $addLog = function ($key, $patch) use (&$bucket) {
                if ($key === '') {
                    return;
                }
                if (!isset($bucket[$key])) {
                    $bucket[$key] = array(
                        'order_no' => '',
                        'product_code' => '',
                        'product_name' => '',
                        'material_code' => '',
                        'material_name' => '',
                        'month_year' => '',
                        'forecast_split_qty' => 0,
                        'forecast_indent_qty' => 0,
                        'wo_required_qty' => 0,
                        'wo_gross_shortage' => 0,
                        'forecast_consumed' => 0,
                        'net_confirmed_shortage' => 0,
                        'wo_indent_qty' => 0,
                        'actual_issued_qty' => 0,
                        'order_status' => '',
                        'wo_status' => '',
                        'workorder_nos' => array(),
                        'events' => array(),
                    );
                }
                foreach ($patch as $k => $v) {
                    if ($k === 'workorder_nos' && is_array($v)) {
                        $bucket[$key]['workorder_nos'] = array_values(array_unique(array_merge(
                            $bucket[$key]['workorder_nos'],
                            $v
                        )));
                    } elseif ($k === 'events' && is_array($v)) {
                        $bucket[$key]['events'] = array_merge($bucket[$key]['events'], $v);
                    } elseif ($k === 'forecast_split_qty' || $k === 'forecast_indent_qty' || $k === 'wo_required_qty'
                        || $k === 'wo_gross_shortage' || $k === 'forecast_consumed' || $k === 'net_confirmed_shortage'
                        || $k === 'wo_indent_qty' || $k === 'actual_issued_qty') {
                        $bucket[$key][$k] = max(floatval($bucket[$key][$k] ?? 0), floatval($v));
                    } elseif ($v !== '' && $v !== null && ($bucket[$key][$k] === '' || $bucket[$key][$k] === null)) {
                        $bucket[$key][$k] = $v;
                    }
                }
            };

            $splitSql = "SELECT sp.order_no, sp.product_code, MAX(sp.product_name) AS product_name,
                    sp.month, sp.year, SUM(sp.oder_qty) AS split_qty
                FROM split_planning_qty sp
                WHERE sp.plant_id = '".$plantId."'";
            if ($yearFilter !== '') {
                $splitSql .= " AND sp.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $splitSql .= " AND sp.month = '".$monthFilter."'";
            }
            if ($orderFilter !== '') {
                $splitSql .= " AND sp.order_no = '".$orderFilter."'";
            }
            $splitSql .= " GROUP BY sp.order_no, sp.product_code, sp.month, sp.year";
            $splitRes = $conn->query($splitSql);
            if ($splitRes && $splitRes->num_rows > 0) {
                while ($sp = $splitRes->fetch_assoc()) {
                    $monthYear = trim(($sp['month'] ?? '') . ' ' . ($sp['year'] ?? ''));
                    $key = ($sp['order_no'] ?? '') . '|__product__|' . $monthYear;
                    $addLog($key, array(
                        'order_no' => $sp['order_no'] ?? '',
                        'product_code' => $sp['product_code'] ?? '',
                        'product_name' => $sp['product_name'] ?? '',
                        'month_year' => $monthYear,
                        'forecast_split_qty' => floatval($sp['split_qty'] ?? 0),
                        'events' => array('Forecast split saved: ' . round(floatval($sp['split_qty'] ?? 0), 3) . ' units'),
                    ));
                }
            }

            ensureMrpRaisedIndentColumns($conn);
            $fSql = "SELECT m.material_code, m.material_name, m.month, m.year, m.ordered_qty,
                    m.order_no, m.product_code, m.WO_deductions_id,
                    ir.indend_no, ir.request_no, ir.status AS indent_status, ir.entry_date
                FROM mrp_raised_indnd_qty m
                LEFT JOIN indend_raw ir ON ir.id = m.indend_id
                WHERE (ir.plant_id = '".$plantId."' OR ir.id IS NULL)";
            if ($yearFilter !== '') {
                $fSql .= " AND m.year = '".$yearFilter."'";
            }
            if ($monthFilter !== '') {
                $fSql .= " AND m.month = '".$monthFilter."'";
            }
            if ($orderFilter !== '') {
                $fSql .= " AND (m.order_no = '".$orderFilter."' OR m.order_no LIKE '%".$orderFilter."%')";
            }
            $fSql .= " AND (ir.id IS NULL OR LOWER(TRIM(ir.status)) NOT IN ('reject', 'rejected', 'cancel', 'cancelled'))";
            $fRes = $conn->query($fSql);
            if ($fRes && $fRes->num_rows > 0) {
                while ($fr = $fRes->fetch_assoc()) {
                    $monthYear = trim(($fr['month'] ?? '') . ' ' . ($fr['year'] ?? ''));
                    $key = ($fr['order_no'] ?? '') . '|' . ($fr['material_code'] ?? '') . '|' . $monthYear;
                    $isWo = intval($fr['WO_deductions_id'] ?? 0) > 0;
                    $qty = floatval($fr['ordered_qty'] ?? 0);
                    $indentRef = trim($fr['indend_no'] ?? $fr['request_no'] ?? '');
                    if ($isWo) {
                        $addLog($key, array(
                            'order_no' => $fr['order_no'] ?? '',
                            'material_code' => $fr['material_code'] ?? '',
                            'material_name' => $fr['material_name'] ?? '',
                            'month_year' => $monthYear,
                            'wo_indent_qty' => $qty,
                            'events' => array('Confirmed WO indent ' . $qty . ' (' . $indentRef . ')'),
                        ));
                    } else {
                        $addLog($key, array(
                            'order_no' => $fr['order_no'] ?? '',
                            'material_code' => $fr['material_code'] ?? '',
                            'material_name' => $fr['material_name'] ?? '',
                            'month_year' => $monthYear,
                            'forecast_indent_qty' => $qty,
                            'events' => array('Forecast MRP indent ' . $qty . ' (' . $indentRef . ', ' . ($fr['indent_status'] ?? 'pending') . ')'),
                        ));
                    }
                }
            }

            $woSql = "SELECT wd.*, wom.order_no, wom.product_code, wom.planMonth, wom.status AS wo_status,
                    wom.workorder_no, wd.plan_qty AS batch_plan_qty, wd.shortage AS stored_shortage,
                    COALESCE(mat.material_name, bm.bulkName, pr.product_name) AS material_name,
                    p.product_name
                FROM WO_deductions wd
                INNER JOIN Work_order_materials wom ON wd.workorder_no = wom.workorder_no AND wd.plant_id = wom.plant_id
                LEFT JOIN material mat ON mat.material_code = wd.material_code
                LEFT JOIN bulkmaster bm ON bm.bulkCode = wd.material_code
                LEFT JOIN product pr ON pr.product_code = wd.material_code
                LEFT JOIN product p ON p.product_code = wom.product_code
                WHERE wd.plant_id = '".$plantId."'
                AND wom.send_for_analysis_by IS NOT NULL
                AND wom.status NOT IN ('Cancel', 'Hold', 'Rejected')";
            if ($orderFilter !== '') {
                $woSql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $woRes = $conn->query($woSql);
            if ($woRes && $woRes->num_rows > 0) {
                while ($wr = $woRes->fetch_assoc()) {
                    $live = shortagesCalcDeductionLine($conn, $plantId, $wr);
                    $grossShortage = shortagesNonNeg($live['shortage'] ?? $wr['stored_shortage'] ?? 0);
                    $required = shortagesNonNeg($live['required'] ?? $wr['batch_plan_qty'] ?? 0);
                    $planKey = reconNormalizePlanMonth($wr['planMonth'] ?? '');
                    $forecastMonth = '';
                    $forecastYear = '';
                    if ($planKey !== '' && strpos($planKey, '-') !== false) {
                        $parts = explode('-', $planKey);
                        $forecastYear = $parts[0];
                        $monthNames = array('', 'January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December');
                        $forecastMonth = $monthNames[(int)$parts[1]] ?? $parts[1];
                    }
                    $monthYear = trim($forecastMonth . ' ' . $forecastYear);
                    if ($monthYear === '') {
                        $monthYear = trim($wr['planMonth'] ?? '');
                    }
                    $forecastConsumed = reconGetForecastIndentQty(
                        $conn, $plantId, $wr['material_code'] ?? '', $forecastMonth, $forecastYear, $wr['order_no'] ?? ''
                    );
                    if ($forecastConsumed <= 0) {
                        $forecastConsumed = getActiveForecastIndentQty($conn, $plantId, $wr['material_code'] ?? '', $wr['order_no'] ?? '', true);
                    }
                    $netShort = shortagesNonNeg($grossShortage - $forecastConsumed);
                    $key = ($wr['order_no'] ?? '') . '|' . ($wr['material_code'] ?? '') . '|' . $monthYear;
                    $addLog($key, array(
                        'order_no' => $wr['order_no'] ?? '',
                        'product_code' => $wr['product_code'] ?? '',
                        'product_name' => $wr['product_name'] ?? '',
                        'material_code' => $wr['material_code'] ?? '',
                        'material_name' => $wr['material_name'] ?? '',
                        'month_year' => $monthYear,
                        'wo_required_qty' => $required,
                        'wo_gross_shortage' => $grossShortage,
                        'forecast_consumed' => $forecastConsumed,
                        'net_confirmed_shortage' => $netShort,
                        'wo_status' => $wr['wo_status'] ?? '',
                        'workorder_nos' => array($wr['workorder_no'] ?? ''),
                        'events' => array(
                            'WO ' . ($wr['workorder_no'] ?? '') . ': required ' . round($required, 3)
                            . ', gross shortage ' . round($grossShortage, 3)
                            . ', forecast offset ' . round($forecastConsumed, 3)
                            . ', net ' . round($netShort, 3)
                        ),
                    ));
                }
            }

            $issueSql = "SELECT mi.material_code, mi.workorder_no, SUM(mi.qty) AS issued_qty,
                    wom.order_no, wom.product_code
                FROM material_issue mi
                LEFT JOIN Work_order_materials wom ON wom.workorder_no = mi.workorder_no AND wom.plant_id = mi.plant_id
                WHERE mi.plant_id = '".$plantId."'";
            if ($orderFilter !== '') {
                $issueSql .= " AND wom.order_no = '".$orderFilter."'";
            }
            $issueSql .= " GROUP BY mi.material_code, mi.workorder_no, wom.order_no, wom.product_code";
            $issueRes = $conn->query($issueSql);
            if ($issueRes && $issueRes->num_rows > 0) {
                while ($ir = $issueRes->fetch_assoc()) {
                    $key = ($ir['order_no'] ?? '') . '|' . ($ir['material_code'] ?? '') . '|';
                    $addLog($key, array(
                        'order_no' => $ir['order_no'] ?? '',
                        'product_code' => $ir['product_code'] ?? '',
                        'material_code' => $ir['material_code'] ?? '',
                        'actual_issued_qty' => floatval($ir['issued_qty'] ?? 0),
                        'workorder_nos' => array($ir['workorder_no'] ?? ''),
                        'events' => array('Material issued ' . round(floatval($ir['issued_qty'] ?? 0), 3) . ' on WO ' . ($ir['workorder_no'] ?? '')),
                    ));
                }
            }

            $omSql = "SELECT order_no, product_code, status FROM order_materials WHERE plant_id = '".$plantId."'";
            if ($orderFilter !== '') {
                $omSql .= " AND order_no = '".$orderFilter."'";
            }
            $omRes = $conn->query($omSql);
            $orderStatusMap = array();
            if ($omRes && $omRes->num_rows > 0) {
                while ($om = $omRes->fetch_assoc()) {
                    $orderStatusMap[$om['order_no'] ?? ''] = $om['status'] ?? '';
                }
            }

            foreach ($bucket as $key => &$row) {
                if (strpos($key, '|__product__|') !== false) {
                    continue;
                }
                $row['workorder_nos'] = implode(', ', $row['workorder_nos'] ?? array());
                $row['order_status'] = $orderStatusMap[$row['order_no'] ?? ''] ?? '';
                $row['forecast_split_qty'] = round(floatval($row['forecast_split_qty'] ?? 0), 3);
                $row['forecast_indent_qty'] = round(floatval($row['forecast_indent_qty'] ?? 0), 3);
                $row['wo_required_qty'] = round(floatval($row['wo_required_qty'] ?? 0), 3);
                $row['wo_gross_shortage'] = round(floatval($row['wo_gross_shortage'] ?? 0), 3);
                $row['forecast_consumed'] = round(floatval($row['forecast_consumed'] ?? 0), 3);
                $row['net_confirmed_shortage'] = round(floatval($row['net_confirmed_shortage'] ?? 0), 3);
                $row['wo_indent_qty'] = round(floatval($row['wo_indent_qty'] ?? 0), 3);
                $row['actual_issued_qty'] = round(floatval($row['actual_issued_qty'] ?? 0), 3);
                $pipeline = round($row['forecast_indent_qty'] + $row['wo_indent_qty'], 3);
                $variance = round($pipeline - $row['actual_issued_qty'], 3);
                $row['pipeline_indent_qty'] = $pipeline;
                $row['variance_qty'] = $variance;

                $parts = array();
                if ($row['forecast_indent_qty'] > 0) {
                    $parts[] = 'Forecast indent ' . $row['forecast_indent_qty'];
                }
                if ($row['wo_required_qty'] > 0) {
                    $parts[] = 'WO need ' . $row['wo_required_qty'];
                }
                if ($row['forecast_consumed'] > 0) {
                    $parts[] = 'forecast consumed ' . $row['forecast_consumed'] . ' before WO shortage';
                }
                if ($row['net_confirmed_shortage'] > 0) {
                    $parts[] = 'net confirmed shortage ' . $row['net_confirmed_shortage'];
                } elseif ($row['wo_gross_shortage'] > 0 && $row['forecast_consumed'] > 0) {
                    $parts[] = 'WO shortage fully covered by forecast indent';
                }
                if ($row['wo_indent_qty'] > 0) {
                    $parts[] = 'confirmed indent ' . $row['wo_indent_qty'];
                }
                if ($row['actual_issued_qty'] > 0) {
                    $parts[] = 'physically issued ' . $row['actual_issued_qty'];
                }
                if ($variance > 0.001) {
                    $parts[] = 'pipeline exceeds issue by ' . $variance;
                } elseif ($variance < -0.001) {
                    $parts[] = 'issued exceeds pipeline by ' . abs($variance);
                }
                if (empty($parts)) {
                    $parts[] = 'No forecast/confirmed activity yet';
                }
                $row['log_statement'] = implode('; ', $parts) . '.';
                if (!empty($row['events'])) {
                    $row['event_trail'] = implode(' → ', array_slice($row['events'], 0, 5));
                } else {
                    $row['event_trail'] = $row['log_statement'];
                }
                unset($row['events']);
            }
            unset($row);

            $materialRows = array_values(array_filter($bucket, function ($r, $k) {
                return strpos((string)$k, '|__product__|') === false;
            }, ARRAY_FILTER_USE_BOTH));

            usort($materialRows, function ($a, $b) {
                $c = strcmp((string)($a['order_no'] ?? ''), (string)($b['order_no'] ?? ''));
                if ($c !== 0) {
                    return $c;
                }
                return strcmp((string)($a['material_code'] ?? ''), (string)($b['material_code'] ?? ''));
            });

            $output['rows'] = $materialRows;
            $output['summary'] = array(
                'total' => count($materialRows),
                'with_forecast_indent' => count(array_filter($materialRows, function ($r) {
                    return floatval($r['forecast_indent_qty'] ?? 0) > 0;
                })),
                'with_net_shortage' => count(array_filter($materialRows, function ($r) {
                    return floatval($r['net_confirmed_shortage'] ?? 0) > 0;
                })),
                'with_actual_issue' => count(array_filter($materialRows, function ($r) {
                    return floatval($r['actual_issued_qty'] ?? 0) > 0;
                })),
                'double_pipeline' => count(array_filter($materialRows, function ($r) {
                    return floatval($r['forecast_indent_qty'] ?? 0) > 0 && floatval($r['wo_indent_qty'] ?? 0) > 0;
                })),
            );
        }

        echo json_encode($output);
    }




      else if ($_GET["type"] == "getPendingProcessingPOsReceiving") {
              
                $output = array();
                if (function_exists('po_ensure_order_materials_receive_cols')) {
                    po_ensure_order_materials_receive_cols($conn);
                }
                $plannerCtx = po_receive_planner_filter_context($conn, $_GET['plant_id'], $_GET['emp_id']);
                $hasWoGenCol = false;
                $woColCheck = $conn->query("SHOW COLUMNS FROM order_materials LIKE 'Wo_Generated_by'");
                if ($woColCheck && $woColCheck->num_rows > 0) {
                    $hasWoGenCol = true;
                }
                $woGenFilter = $hasWoGenCol
                    ? " AND (o.Wo_Generated_by IS NULL OR TRIM(IFNULL(o.Wo_Generated_by,'')) = '') "
                    : "";
                
                // Planning Receive queue: Marketing Approval done → status Approved
                // + order_materials Work Order Preparation Approved / Approved.
                // Batches / WO rows are optional so newly approved FOs still appear.
                $sql = "SELECT a.*,
                        (SELECT c.LglNm FROM client c WHERE c.client_code = a.client_code LIMIT 1) AS clientName,
                        (SELECT c2.LglNm FROM client c2 WHERE c2.client_code = a.conisgnee LIMIT 1) AS conisgneeName,
                        COALESCE(
                            (SELECT p.category FROM product p WHERE p.product_code = a.parent_product_code AND p.plant_id = a.plant_id ORDER BY p.id DESC LIMIT 1),
                            (SELECT p.category FROM product p WHERE p.product_code = a.parent_product_code ORDER BY p.id DESC LIMIT 1)
                        ) AS category,
                        COALESCE(
                            (SELECT p.product_name FROM product p WHERE p.product_code = a.parent_product_code AND p.plant_id = a.plant_id ORDER BY p.id DESC LIMIT 1),
                            (SELECT p.product_name FROM product p WHERE p.product_code = a.parent_product_code ORDER BY p.id DESC LIMIT 1)
                        ) AS product_name,
                        a.parent_product_code AS product_code,
                        ".poEmpFirstNameIdSql('a.entry_by')." AS emp_name
                        FROM po_entry a
                        WHERE LOWER(TRIM(IFNULL(a.status,'')))='approved'
                          AND TRIM(IFNULL(a.plant_id,''))='".mysqli_real_escape_string($conn, (string)($_GET['plant_id'] ?? ''))."'
                          AND (
                                LOWER(TRIM(IFNULL(a.billing_type,''))) IN ('forcast','forecast')
                                OR TRIM(IFNULL(a.billing_type,'')) = ''
                              )
                        ORDER BY a.id DESC";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                
                    while ($row = $result->fetch_assoc()) {
                        if (!po_receive_fo_visible_for_planner(
                            $plannerCtx,
                            $row['client_code'] ?? '',
                            $row['groupcode'] ?? ''
                        )) {
                            continue;
                        }
                
                        $products = array();
                        $handledAsCombi = false;
                        $poEntryIdEsc = mysqli_real_escape_string($conn, (string)$row["id"]);
                
                        // CASE 1: COMBI — SFG children after Marketing Approval (not CombiMaster)
                        if (isCombiCategory($row['category'] ?? '')) {
                
                             $sqlMat = "SELECT o.*, o.id AS order_material_id,
                                COALESCE(
                                  (SELECT p.product_name FROM product p WHERE p.product_code = o.product_code AND p.plant_id = o.plant_id ORDER BY p.id DESC LIMIT 1),
                                  (SELECT p.product_name FROM product p WHERE p.product_code = o.product_code ORDER BY p.id DESC LIMIT 1)
                                ) AS product_name
                             FROM order_materials o 
                            WHERE (o.po_entry_id = '".$poEntryIdEsc."'
                                   OR (IFNULL(o.order_no,'') != '' AND o.order_no = '".mysqli_real_escape_string($conn, (string)($row['order_no'] ?? ''))."'))
                              AND o.status IN ('Work Order Preparation Approved','Approved')
                              ".$woGenFilter."
                            ORDER BY o.id ASC";
                
                            $resMat = $conn->query($sqlMat);
                
                            if ($resMat && $resMat->num_rows > 0) {
                                while ($mat = $resMat->fetch_assoc()) {
                                    $mat["batches"] = json_decode($mat["batches"] ?? '[]', true);
                                    if (!is_array($mat["batches"])) {
                                        $mat["batches"] = [];
                                    }
                                    poEnrichCombiChildForProcessing($conn, $mat, $row['plant_id'] ?? '');
                                    $mat['batch_formula'] = fetchBatchFormulasForProduct($conn, $mat['product_code'], $mat['planUnit']);
                                    $products[] = $mat;
                                    if (empty($row['groupcode']) && !empty($mat['groupcode'])) {
                                        $row['groupcode'] = $mat['groupcode'];
                                    }
                                }
                            }
                            if (!empty($products)) {
                                $row['products'] = $products;
                                $row['batch_formula'] = [];
                                $row['is_combi'] = true;
                                $handledAsCombi = true;
                                if (empty($row['doc_no'])) {
                                    $row['doc_no'] = $row['id'] ?? '';
                                }
                                if (empty($row['planMonth'])) {
                                    foreach ($products as $pLine) {
                                        if (!empty($pLine['planMonth'])) {
                                            $row['planMonth'] = $pLine['planMonth'];
                                            break;
                                        }
                                    }
                                }
                                if (empty($row['planMonth'])) {
                                    $srcDate = $row['po_date'] ?? ($row['entry_date'] ?? '');
                                    if (!empty($srcDate) && ($ts = strtotime((string)$srcDate)) !== false) {
                                        $row['planMonth'] = date('m-Y', $ts);
                                    }
                                }
                                foreach ($row['products'] as &$pRef) {
                                    if (empty($pRef['doc_no'])) {
                                        $pRef['doc_no'] = $row['doc_no'];
                                    }
                                    if (empty($pRef['planMonth']) && !empty($row['planMonth'])) {
                                        $pRef['planMonth'] = $row['planMonth'];
                                    }
                                }
                                unset($pRef);
                            }
                        }
                
                        // CASE 2: NORMAL FO ready for planning receive
                        if (!$handledAsCombi) {
                
                            $sql9 = "SELECT o.id, o.batches, o.deliveryDate, o.planUnit, o.packingUnit, o.packingStyle, o.planQty,
                                     o.planMonth, o.product_code, o.order_no, o.doc_no, o.leftover, o.excess, o.work_order_planned_qty,
                                     o.mainGroupName, o.parent_product_code, o.status, o.groupcode,
                                     COALESCE(
                                       (SELECT p1.product_name FROM product p1 WHERE p1.product_code = o.product_code LIMIT 1),
                                       ''
                                     ) AS product_name
                                     FROM order_materials o
                                     WHERE (o.po_entry_id='".$poEntryIdEsc."'
                                            OR (IFNULL(o.order_no,'') != '' AND o.order_no = '".mysqli_real_escape_string($conn, (string)($row['order_no'] ?? ''))."'))
                                       AND o.status IN ('Work Order Preparation Approved','Approved')
                                       ".$woGenFilter."
                                     ORDER BY o.id DESC
                                     LIMIT 1";
                
                            $result9 = $conn->query($sql9);
                            if ($result9 && $result9->num_rows > 0) {
                                $row9 = $result9->fetch_assoc();
                                $headerBatchesRaw = $row['batches'] ?? '';
                                $row['order_material_id'] = $row9['id'];
                                $row["batches"] = json_decode($row9["batches"] ?? '[]', true);
                                if (!is_array($row["batches"])) {
                                    $row["batches"] = [];
                                }
                                // Fall back to header batches saved during Marketing Processing
                                if (empty($row['batches'])) {
                                    if (is_array($headerBatchesRaw)) {
                                        $row['batches'] = $headerBatchesRaw;
                                    } elseif (is_string($headerBatchesRaw) && $headerBatchesRaw !== '' && $headerBatchesRaw !== 'null') {
                                        $decodedHdr = json_decode($headerBatchesRaw, true);
                                        $row['batches'] = is_array($decodedHdr) ? $decodedHdr : [];
                                    }
                                }
                                $row['deliveryDate'] = $row9['deliveryDate'] ?? '';
                                $row['planMonth'] = $row9['planMonth'] ?? ($row['planMonth'] ?? '');
                                $row['leftover'] = $row9['leftover'] ?? ($row['leftover'] ?? 0);
                                $row['excess'] = $row9['excess'] ?? ($row['excess'] ?? 0);
                                $row['work_order_planned_qty'] = $row9['work_order_planned_qty'] ?? null;
                                $row['status'] = $row9['status'] ?? $row['status'];
                                if (!empty($row9['planUnit'])) {
                                    $row['planUnit'] = $row9['planUnit'];
                                }
                                if (!empty($row9['packingUnit'])) {
                                    $row['packingUnit'] = $row9['packingUnit'];
                                }
                                if (!empty($row9['packingStyle'])) {
                                    $row['packingStyle'] = $row9['packingStyle'];
                                }
                                if (!empty($row9['planQty'])) {
                                    $row['plan_qty'] = $row9['planQty'];
                                    $row['planQty'] = $row9['planQty'];
                                }
                                if (!empty($row9['product_code'])) {
                                    $row['product_code'] = $row9['product_code'];
                                }
                                if (!empty($row9['product_name'])) {
                                    $row['product_name'] = $row9['product_name'];
                                }
                                if (!empty($row9['order_no'])) {
                                    $row['order_no'] = $row9['order_no'];
                                }
                                if (!empty($row9['doc_no'])) {
                                    $row['doc_no'] = $row9['doc_no'];
                                }
                                if (!empty($row9['mainGroupName'])) {
                                    $row['mainGroupName'] = $row9['mainGroupName'];
                                }
                                if (!empty($row9['groupcode'])) {
                                    $row['groupcode'] = $row9['groupcode'];
                                }
                            } else {
                                continue;
                            }

                            // Entry No = po_entry id when line doc_no was never set
                            if (empty($row['doc_no'])) {
                                $row['doc_no'] = $row['id'] ?? '';
                            }
                            // Plan Month fallback from delivery / FO date
                            if (empty($row['planMonth'])) {
                                $srcDate = $row['deliveryDate'] ?? ($row['po_date'] ?? ($row['entry_date'] ?? ''));
                                if (!empty($srcDate) && ($ts = strtotime((string)$srcDate)) !== false) {
                                    $row['planMonth'] = date('m-Y', $ts);
                                }
                            }
                
                            // Show even if BFR list is empty (user can still receive / split)
                            $formulaCode = $row['product_code'] ?? ($row['parent_product_code'] ?? '');
                            $row['batch_formula'] = fetchBatchFormulasForProduct($conn, $formulaCode, $row['planUnit'] ?? '');
                            $row['products'] = [];
                            $row['is_combi'] = false;
                        }

                        if (!po_receive_fo_visible_for_planner(
                            $plannerCtx,
                            $row['client_code'] ?? '',
                            $row['groupcode'] ?? ''
                        )) {
                            continue;
                        }
                        po_attach_fo_planner_info($conn, $row, $row['plant_id'] ?? $_GET['plant_id']);
                       
                        $output[] = $row;
                    }
                }
                
                echo json_encode($output);

    }

      else if ($_GET["type"] == "saveOrderSplitPlan") {
        $orderNo = trim((string)($input['order_no'] ?? ''));
        $productCode = trim((string)($input['product_code'] ?? ''));
        $productName = trim((string)($input['product_name'] ?? ''));
        $orderQty = floatval($input['order_qty'] ?? 0);
        $useFgStock = !empty($input['use_fg_stock']);
        $subtractInProcess = !empty($input['subtract_in_process']);
        $splits = $input['splits'] ?? [];

        if ($orderNo === '' || $productCode === '') {
            echo json_encode(['status' => 'error', 'message' => 'Order no and product code are required']);
            exit;
        }
        if (!is_array($splits) || count($splits) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'No split rows provided']);
            exit;
        }

        if ($productName === '') {
            $pnSql = "SELECT product_name FROM product
                      WHERE product_code = '".mysqli_real_escape_string($conn, $productCode)."'
                      LIMIT 1";
            $pnRes = $conn->query($pnSql);
            if ($pnRes && $pnRes->num_rows > 0) {
                $pnRow = $pnRes->fetch_assoc();
                $productName = trim((string)($pnRow['product_name'] ?? ''));
            }
        }

        $plantId = mysqli_real_escape_string($conn, (string)($_GET['plant_id'] ?? ''));
        $empId = mysqli_real_escape_string($conn, (string)($_GET['emp_id'] ?? ''));
        $orderEsc = mysqli_real_escape_string($conn, $orderNo);
        $productEsc = mysqli_real_escape_string($conn, $productCode);
        $productNameEsc = mysqli_real_escape_string($conn, $productName);
        $splitDate = date('Y-m-d');

        $avblStock = 0;
        if ($useFgStock && $plantId !== '') {
            $fgSql = "SELECT
                        IFNULL(SUM(a.qty), 0) AS stock_qty,
                        IFNULL((SELECT SUM(qty) FROM material_issue mi WHERE mi.material_code = a.material_code), 0) AS issued_qty,
                        IFNULL((SELECT SUM(qty) FROM mrp_bookedStock mb WHERE mb.material_code = a.material_code AND mb.plant_id = '".$plantId."'), 0) AS booked_qty
                      FROM fg_stock_book a
                      WHERE a.plant_id = '".$plantId."'
                      AND a.material_code = '".$productEsc."'
                      GROUP BY a.material_code
                      LIMIT 1";
            $fgRes = $conn->query($fgSql);
            if ($fgRes && $fgRes->num_rows > 0) {
                $fgRow = $fgRes->fetch_assoc();
                $avblStock = floatval($fgRow['stock_qty'] ?? 0)
                    - floatval($fgRow['issued_qty'] ?? 0)
                    - floatval($fgRow['booked_qty'] ?? 0);
                if ($avblStock < 0) {
                    $avblStock = 0;
                }
            }
        }

        $conn->query("DELETE FROM split_planning_qty
                      WHERE order_no = '".$orderEsc."'
                      AND product_code = '".$productEsc."'");

        $inserted = 0;
        $failed = [];
        $totalProduce = 0;

        foreach ($splits as $idx => $split) {
            if (!is_array($split)) {
                $failed[] = ['row' => $idx + 1, 'message' => 'Invalid split row'];
                continue;
            }

            $inhouse = floatval($split['inhouse'] ?? 0);
            $outsource = floatval($split['outsource'] ?? 0);
            $produce = floatval($split['produce'] ?? ($inhouse + $outsource));
            $month = trim((string)($split['month'] ?? ''));
            $year = trim((string)($split['year'] ?? ''));

            if ($produce <= 0) {
                $failed[] = ['row' => $idx + 1, 'message' => 'Produce qty must be greater than zero'];
                continue;
            }
            if ($month === '' || $year === '') {
                $failed[] = ['row' => $idx + 1, 'message' => 'Month and year are required'];
                continue;
            }

            $totalProduce += $produce;
            $monthEsc = mysqli_real_escape_string($conn, $month);
            $yearEsc = mysqli_real_escape_string($conn, $year);
            $produceEsc = mysqli_real_escape_string($conn, (string)$produce);
            $inhouseEsc = mysqli_real_escape_string($conn, (string)$inhouse);
            $outsourceEsc = mysqli_real_escape_string($conn, (string)$outsource);
            $avblStockEsc = mysqli_real_escape_string($conn, (string)$avblStock);

            $insSql = "INSERT INTO split_planning_qty (
                            order_no, plant_id, product_name, product_code, date, month, year,
                            oder_qty, outQty, InQty, balance_qty, avbl_stock, status, entry_by
                        ) VALUES (
                            '".$orderEsc."', '".$plantId."', '".$productNameEsc."', '".$productEsc."',
                            '".$splitDate."', '".$monthEsc."', '".$yearEsc."',
                            '".$produceEsc."', '".$outsourceEsc."', '".$inhouseEsc."', '".$produceEsc."',
                            '".$avblStockEsc."', 'Planned', '".$empId."'
                        )";

            if ($conn->query($insSql)) {
                $inserted++;
            } else {
                $failed[] = ['row' => $idx + 1, 'message' => $conn->error];
            }
        }

        if ($inserted === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to save order split plan',
                'failed' => $failed,
            ]);
            exit;
        }

        if ($orderQty > 0 && abs($totalProduce - $orderQty) > 0.001) {
            // Allow save when user confirmed partial allocation on the UI.
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Order split plan saved',
            'inserted' => $inserted,
            'failed_count' => count($failed),
            'failed' => $failed,
            'total_produce' => $totalProduce,
            'avbl_stock' => $avblStock,
            'use_fg_stock' => $useFgStock,
            'subtract_in_process' => $subtractInProcess,
        ]);
    }

      else if ($_GET["type"] == "getOrderSplitPlan") {
        $orderNo = trim((string)($_GET['order_no'] ?? ''));
        $productCode = trim((string)($_GET['product_code'] ?? ''));

        if ($orderNo === '' || $productCode === '') {
            echo json_encode(['status' => 'error', 'message' => 'Order no and product code are required', 'splits' => []]);
            exit;
        }

        $orderEsc = mysqli_real_escape_string($conn, $orderNo);
        $productEsc = mysqli_real_escape_string($conn, $productCode);

        $splits = array();
        $useFgStock = false;
        $sql = "SELECT id, month, year, oder_qty, InQty, outQty, avbl_stock
                FROM split_planning_qty
                WHERE order_no = '".$orderEsc."'
                AND product_code = '".$productEsc."'
                ORDER BY id ASC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $inhouse = floatval($row['InQty'] ?? 0);
                $outsource = floatval($row['outQty'] ?? 0);
                $produce = floatval($row['oder_qty'] ?? 0);
                if ($inhouse == 0 && $outsource == 0 && $produce > 0) {
                    $inhouse = $produce;
                }
                if (floatval($row['avbl_stock'] ?? 0) > 0) {
                    $useFgStock = true;
                }
                $splits[] = array(
                    'id' => $row['id'],
                    'inhouse' => $inhouse,
                    'outsource' => $outsource,
                    'produce' => $produce,
                    'month' => $row['month'] ?? '',
                    'year' => $row['year'] ?? '',
                );
            }
        }

        echo json_encode(array(
            'status' => 'success',
            'splits' => $splits,
            'use_fg_stock' => $useFgStock,
        ));
    }

      else if ($_GET["type"] == "Get_Generated_wo") {
        $output = Array();
        $sql = "SELECT a.*,b.product_code,b.work_order_planned_qty,(select product_name from product c where b.product_code=c.product_code limit 1) as product_name 
        FROM Work_order_materials a left join order_materials b on a.order_no=b.order_no where a.order_no = '".$_GET["order_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row; 
            }
        }
        echo json_encode($output);
    }

      else if ($_GET["type"] == "updateProceedPendingPOs") {
        $targetPlantId = isset($input['target_plant_id']) ? trim((string)$input['target_plant_id']) : (isset($_GET['target_plant_id']) ? trim((string)$_GET['target_plant_id']) : '');
        if ($targetPlantId === '' && !empty($input['plant_id'])) {
            $targetPlantId = trim((string)$input['plant_id']);
        }
        $resolved = plant_db_resolve_temp_client_target($_GET['plant_id'], $targetPlantId, $conn);
        if (!$resolved['ok']) {
            echo json_encode(array('status' => $resolved['error']));
            exit;
        }
        $conn = $resolved['conn'];
        $poOwnConn = !empty($resolved['own_connection']);
        
            $entry_date = date('Y-m-d H:i:s');

            $excess = $input['excess'] ?? 0;
            $leftover = $input['leftover'] ?? 0;
            
            // Base plan quantity
            $planQty = $input['plan_qty'];
            
            // Adjust planQty based on leftover/excess
            if ($excess > 0) {
                $planQty += $excess; // add excess to planQty
            } elseif ($leftover > 0) {
                $planQty -= $leftover; // subtract leftover from planQty
            }
            // if both are 0, planQty stays as original
            $batches= json_encode($input["batches"]) ;
 
        // Update status; stamp approve user only on actual approval
            $statusEsc = mysqli_real_escape_string($conn, (string)$_GET["status"]);
            $empEsc = mysqli_real_escape_string($conn, (string)$_GET["emp_id"]);
            $idEsc = mysqli_real_escape_string($conn, (string)$input["order_material_id"]);
            if ($statusEsc === 'Work Order Preparation Approved') {
                $sql = "UPDATE order_materials 
                    SET status='".$statusEsc."', 
                        proceed_approved_by='".$empEsc."', 
                        proceed_approved_on='$entry_date'  
                    WHERE id='".$idEsc."'";
            } else {
                $sql = "UPDATE order_materials 
                    SET status='".$statusEsc."'
                    WHERE id='".$idEsc."'";
            }
    
            if ($conn->query($sql)) {
                echo json_encode(array('status' => 'success'));
            } else {
                echo json_encode(array('status' => $conn->error));
            }
            if ($poOwnConn && $conn) {
                $conn->close();
            }
            
    }

      else if ($_GET["type"] == "updateProceed_Wo_Gen") {
        if (function_exists('po_ensure_order_materials_receive_cols')) {
            po_ensure_order_materials_receive_cols($conn);
        }
        medicap_require_helper('processing_po_helpers.php');
        $orders = $input["orders"];   // Array of order_no values: ["FO1000035","FO1000036B"]
        $entry_date = date("Y-m-d H:i:s");
        $statusEsc = mysqli_real_escape_string($conn, (string)($input["status"] ?? 'Work Order Processed'));
        $empEsc = mysqli_real_escape_string($conn, (string)($_GET["emp_id"] ?? ''));
        
        foreach ($orders as $orderno) {
            $orderEsc = mysqli_real_escape_string($conn, (string)$orderno);
        
            $sql = "UPDATE order_materials 
                    SET 
                        status='".$statusEsc."',
                        Wo_Generated_by='".$empEsc."',
                        Wo_Generated_on='$entry_date'
                    WHERE order_no='".$orderEsc."'";
        
            $conn->query($sql);

            // If WO rows are missing (Receive stamped but insert never ran), create them now.
            $woCntRes = $conn->query("SELECT COUNT(*) AS c FROM Work_order_materials WHERE order_no='".$orderEsc."'");
            $woCnt = ($woCntRes && ($wr = $woCntRes->fetch_assoc())) ? (int)$wr['c'] : 0;
            if ($woCnt === 0 && function_exists('po_processing_insert_work_orders')) {
                $omRes = $conn->query("SELECT o.*, pe.batches AS pe_batches, pe.id AS pe_id, pe.plan_qty AS pe_plan_qty,
                        pe.parent_product_code AS pe_parent_product_code, pe.client_code, pe.conisgnee, pe.billing_type AS pe_billing_type
                    FROM order_materials o
                    LEFT JOIN po_entry pe ON (pe.order_no = o.order_no OR pe.id = o.po_entry_id)
                    WHERE o.order_no='".$orderEsc."'
                    ORDER BY o.id DESC LIMIT 1");
                if ($omRes && ($om = $omRes->fetch_assoc())) {
                    $batches = array();
                    if (!empty($om['batches'])) {
                        $decoded = is_string($om['batches']) ? json_decode($om['batches'], true) : $om['batches'];
                        if (is_array($decoded)) {
                            $batches = $decoded;
                        }
                    }
                    if (count($batches) === 0 && !empty($om['pe_batches'])) {
                        $decoded = is_string($om['pe_batches']) ? json_decode($om['pe_batches'], true) : $om['pe_batches'];
                        if (is_array($decoded)) {
                            $batches = $decoded;
                        }
                    }
                    if (count($batches) === 0) {
                        $pq = floatval($om['planQty'] ?? ($om['plan_qty'] ?? ($om['pe_plan_qty'] ?? ($om['order_qty'] ?? 0))));
                        if ($pq > 0) {
                            $batches = array(array(
                                'count' => 1,
                                'size' => $pq,
                                'unit' => $om['planUnit'] ?? ($om['unit'] ?? ''),
                            ));
                        }
                    }
                    if (count($batches) > 0) {
                        $productCode = trim((string)($om['product_code'] ?? ($om['pe_parent_product_code'] ?? '')));
                        $productEsc = mysqli_real_escape_string($conn, $productCode);
                        $productName = '';
                        $pnRes = $conn->query("SELECT product_name FROM product WHERE product_code='".$productEsc."' LIMIT 1");
                        if ($pnRes && ($pn = $pnRes->fetch_assoc())) {
                            $productName = $pn['product_name'] ?? '';
                        }
                        $planMonthRaw = trim((string)($om['planMonth'] ?? ''));
                        $month = date('F');
                        $year = date('Y');
                        if (preg_match('/^(\d{4})-(\d{2})/', $planMonthRaw, $m)) {
                            $month = date('F', mktime(0, 0, 0, (int)$m[2], 1, (int)$m[1]));
                            $year = $m[1];
                        } elseif (preg_match('/^(\d{2})-(\d{4})$/', $planMonthRaw, $m)) {
                            $month = date('F', mktime(0, 0, 0, (int)$m[1], 1, (int)$m[2]));
                            $year = $m[2];
                        }
                        $splitId = 0;
                        $spRes = $conn->query("SELECT id FROM split_planning_qty
                            WHERE order_no='".$orderEsc."' AND product_code='".$productEsc."'
                            ORDER BY id DESC LIMIT 1");
                        if ($spRes && ($sp = $spRes->fetch_assoc())) {
                            $splitId = (int)$sp['id'];
                            $conn->query("UPDATE split_planning_qty SET status='".$statusEsc."' WHERE id=".$splitId);
                        } else {
                            $planQtyVal = floatval($om['planQty'] ?? ($om['plan_qty'] ?? ($om['pe_plan_qty'] ?? ($om['order_qty'] ?? 0))));
                            $plantEsc2 = mysqli_real_escape_string($conn, (string)($om['plant_id'] ?? ($_GET['plant_id'] ?? '')));
                            $nameEsc = mysqli_real_escape_string($conn, $productName);
                            $monthEsc = mysqli_real_escape_string($conn, $month);
                            $yearEsc = mysqli_real_escape_string($conn, $year);
                            $qtyEsc = mysqli_real_escape_string($conn, (string)$planQtyVal);
                            $unitEsc = mysqli_real_escape_string($conn, (string)($om['planUnit'] ?? ($om['unit'] ?? '')));
                            $batchesJson = mysqli_real_escape_string($conn, json_encode($batches));
                            if ($conn->query("INSERT INTO split_planning_qty (
                                    order_no, plant_id, product_name, product_code, date, month, year,
                                    oder_qty, outQty, InQty, balance_qty, avbl_stock, status, entry_by,
                                    work_order_planned_qty, batches, planUnit, unit
                                ) VALUES (
                                    '".$orderEsc."', '".$plantEsc2."', '".$nameEsc."', '".$productEsc."',
                                    '".date('Y-m-d')."', '".$monthEsc."', '".$yearEsc."',
                                    '".$qtyEsc."', '0', '".$qtyEsc."', '".$qtyEsc."', '0', '".$statusEsc."', '".$empEsc."',
                                    '".$qtyEsc."', '".$batchesJson."', '".$unitEsc."', '".$unitEsc."'
                                )")) {
                                $splitId = (int)$conn->insert_id;
                            }
                        }
                        if ($splitId > 0) {
                            $conn->query("UPDATE order_materials SET doc_no='".$splitId."', batches='".mysqli_real_escape_string($conn, json_encode($batches))."'
                                WHERE order_no='".$orderEsc."'");
                            $woInput = array(
                                'po_entry_id' => $om['po_entry_id'] ?? ($om['pe_id'] ?? ''),
                                'plant_id' => $om['plant_id'] ?? ($_GET['plant_id'] ?? ''),
                                'order_no' => $orderno,
                                'doc_no' => $splitId,
                                'product_code' => $productCode,
                                'planMonth' => $month . '-' . $year,
                                'planUnit' => $om['planUnit'] ?? ($om['unit'] ?? ''),
                                'packingStyle' => $om['packingStyle'] ?? '',
                                'packingUnit' => $om['packingUnit'] ?? '',
                                'deliveryDate' => $om['deliveryDate'] ?? '',
                                'mainGroupName' => $om['mainGroupName'] ?? '',
                                'groupcode' => $om['groupcode'] ?? ($om['client_code'] ?? ''),
                                'subClient' => $om['subClient'] ?? '',
                                'parent_product_code' => $om['parent_product_code'] ?? ($om['pe_parent_product_code'] ?? $productCode),
                                'billing_type' => $om['pe_billing_type'] ?? 'Forcast',
                                'excess' => $om['excess'] ?? 0,
                                'leftover' => $om['leftover'] ?? 0,
                                'plan_qty' => $om['planQty'] ?? ($om['plan_qty'] ?? ($om['pe_plan_qty'] ?? 0)),
                                'planQty' => $om['planQty'] ?? ($om['plan_qty'] ?? ($om['pe_plan_qty'] ?? 0)),
                            );
                            po_processing_insert_work_orders(
                                $conn,
                                $woInput,
                                $batches,
                                $statusEsc,
                                $_GET['emp_id'] ?? '',
                                $entry_date,
                                floatval($woInput['plan_qty'])
                            );
                        }
                    }
                }
            }
            
            $sql1 = "UPDATE Work_order_materials 
                    SET 
                        status='".$statusEsc."',
                        Wo_Generated_by='".$empEsc."',
                        Wo_Generated_on='$entry_date'
                    WHERE order_no='".$orderEsc."'";
        
            $conn->query($sql1);
            
            
            
        }
        
        echo json_encode(["status" => "success"]);

    }

      else if ($_GET["type"] == "getCancelledGeneratedWOs") {
    header("Content-Type: application/json");
    $plantEsc = mysqli_real_escape_string($conn, $_GET["plant_id"] ?? '');
    $plantFilter = $plantEsc !== '' ? " AND a.plant_id = '".$plantEsc."'" : '';

    $sql = "SELECT a.id, a.workorder_no, a.order_no, a.doc_no, a.mainGroupName,
                a.planMonth, a.product_code, a.batch_size, a.planQty, a.plan_qty,
                a.planUnit, a.packingStyle AS packing_type, a.billing_type,
                a.status, a.Wo_Generated_on, a.cancel_hold_by, a.cancel_hold_on,
                (SELECT product_name FROM product c WHERE c.product_code = a.product_code LIMIT 1) AS product_name,
                (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(lastname, ''))
                    FROM employee WHERE emp_id = a.cancel_hold_by) AS cancelled_by_name
            FROM Work_order_materials a
            WHERE a.status = 'Cancelled' ".$plantFilter."
            ORDER BY a.id DESC";

    $output = array();
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

      else if ($_GET["type"] == "proceed_cancelled_wo") {
    $id = mysqli_real_escape_string($conn, $input['id'] ?? ($_GET['id'] ?? ''));
    $woNo = mysqli_real_escape_string($conn, $input['workorder_no'] ?? ($_GET['workorder_no'] ?? ''));
    $plantEsc = mysqli_real_escape_string($conn, $_GET['plant_id'] ?? '');

    if ($id === '' && $woNo === '') {
        echo json_encode(["status" => "error", "message" => "id or workorder_no required"]);
        return;
    }

    $where = $id !== '' ? "id = '".$id."'" : "workorder_no = '".$woNo."'";
    if ($plantEsc !== '') {
        $where .= " AND plant_id = '".$plantEsc."'";
    }

    // Bring the cancelled work order back into the main Generate WO log.
    $sql = "UPDATE Work_order_materials
        SET status = 'Work Order Generation Done',
            cancel_hold_by = NULL,
            cancel_hold_on = NULL
        WHERE ".$where." AND status = 'Cancelled'";

    if ($conn->query($sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Work order moved back to Generate WO"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}

      else if ($_GET["type"] == "getGenerateWoPlanMaterials") {
        ensureWoVerificationColumns($conn);
        $woId = intval($_GET['wo_id'] ?? 0);
        $plantId = trim((string)($_GET['plant_id'] ?? ''));
        if ($woId <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Work order id is required.'));
            exit;
        }

        $row = generateWoFetchWorkOrderContextRow($conn, $woId, $plantId);
        if (!$row) {
            echo json_encode(array('status' => 'error', 'message' => 'Work order not found.'));
            exit;
        }

        $parentCode = trim((string)($row['parent_product_code'] ?? ''));
        $prodCode = trim((string)($row['product_code'] ?? ''));
        $row['is_combi_child'] = ($parentCode !== '' && $prodCode !== '' && strcasecmp($parentCode, $prodCode) !== 0);
        if (isCombiCategory($row['product_category'] ?? '') && !$row['is_combi_child']) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Combi parent has no BFR — open each SFG child work order.',
            ));
            exit;
        }

        $bom = generateWoFetchBomForWorkOrderRow($conn, $row);
        $planMaterials = generateWoBuildPlanMaterialsForDisplay($conn, $bom, $row['plant_id'] ?? $plantId);
        $resolvedProduct = trim((string)($row['wo_product_code'] ?? ''));
        if ($resolvedProduct === '') {
            $resolvedProduct = trim((string)($row['product_code'] ?? ''));
        }

        echo json_encode(array(
            'status' => 'success',
            'id' => $row['id'] ?? $woId,
            'workorder_no' => $row['workorder_no'] ?? '',
            'order_no' => $row['order_no'] ?? '',
            'product_code' => $resolvedProduct,
            'product_name' => $row['product_name'] ?? '',
            'batch_size' => floatval($row['batch_size'] ?? 0),
            'planUnit' => $row['planUnit'] ?? '',
            'bfr_no' => $bom['bfr_no'] ?? '',
            'bfr_info_id' => intval($bom['bfr_info_id'] ?? 0),
            'mfr_no' => $bom['mfr_no'] ?? '',
            'bfr_pack_sizes_id' => $bom['bfr_pack_sizes_id'] ?? null,
            'bom_resolved' => !empty($bom['bom_resolved']),
            'bom_missing' => !empty($bom['bom_missing']),
            'rm_line_count' => count($bom['raw_materials'] ?? array()),
            'pm_line_count' => count($bom['packing_materials'] ?? array()),
            'plan_materials' => $planMaterials,
            'plan_message' => empty($planMaterials)
                ? 'No materials on BFR '.$bom['bfr_no'].' — check Prepare Batch Formula for product '.$resolvedProduct.' at batch size '.$row['batch_size']
                : '',
        ));
    }

      else if ($_GET["type"] == "getSendForVerificationLog") {
    $plantId = $_GET['plant_id'] ?? '';
    $plantEsc = mysqli_real_escape_string($conn, $plantId);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(200, intval($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    $search = trim((string)($_GET['search'] ?? ''));
    $empId = $_GET['emp_id'] ?? '';
    $plannerCtx = po_receive_planner_filter_context($conn, $plantId, $empId);

    // Backfill legacy rows sent before send_for_verification_on was stamped.
    ensureWoVerificationColumns($conn);
    if ($plantEsc !== '') {
        $conn->query("UPDATE Work_order_materials SET send_for_verification_on = COALESCE(
                NULLIF(TRIM(send_for_verification_on), ''),
                NULLIF(TRIM(snef_for_planning_on), ''),
                NULLIF(TRIM(Wo_Generated_on), ''),
                NOW()
            )
            WHERE plant_id = '".$plantEsc."'
            AND status = 'Pending Verification'
            AND TRIM(IFNULL(send_for_verification_on, '')) = ''");
    }

    // Log: WOs sent from Process Plan — include current verify queue + batch-allocation history.
    // Legacy rows may have status Pending Verification without send_for_verification_on stamped.
    $where = "(
        a.status IN ('Pending Verification', 'Sent for Batch Allocation')
        OR TRIM(IFNULL(a.send_for_verification_on, '')) != ''
        OR TRIM(IFNULL(a.send_for_verification_by, '')) != ''
    )
    AND a.status NOT IN ('Rejected', 'Cancel', 'Hold')";
    if ($plantEsc !== '') {
        $where .= " AND a.plant_id = '".$plantEsc."'";
    }
    if ($search !== '') {
        $s = mysqli_real_escape_string($conn, $search);
        $where .= " AND (
            a.workorder_no LIKE '%".$s."%'
            OR a.order_no LIKE '%".$s."%'
            OR a.product_code LIKE '%".$s."%'
            OR b.mainGroupName LIKE '%".$s."%'
            OR COALESCE(NULLIF(TRIM(a.groupcode), ''), NULLIF(TRIM(b.groupcode), '')) LIKE '%".$s."%'
            OR COALESCE(NULLIF(TRIM(b.product_name), ''), (SELECT product_name FROM product c
                WHERE c.product_code = a.product_code LIMIT 1)) LIKE '%".$s."%'
        )";
    }

    $sql = "SELECT a.*, b.product_code AS om_product_code, b.work_order_planned_qty, b.planMonth,
            b.mainGroupName, b.packingStyle AS packing_type,
            COALESCE(
                NULLIF(TRIM(a.entryOn), ''),
                NULLIF(TRIM(a.Wo_Generated_on), ''),
                NULLIF(TRIM(a.wo_generated_by_digi_sign_date), '')
            ) AS Wo_Generated_on,
            COALESCE(
                NULLIF(TRIM(a.deliveryDate), ''),
                NULLIF(TRIM(b.deliveryDate), '')
            ) AS deliveryDate,
            COALESCE(NULLIF(TRIM(b.product_name), ''), (SELECT product_name FROM product c
                WHERE c.product_code = a.product_code LIMIT 1)) AS product_name"
            . po_wo_planner_client_select_sql('a', 'b') . "
            FROM Work_order_materials a
            " . po_wo_om_join_sql('a', 'b') . "
            WHERE ".$where."
            ORDER BY a.send_for_verification_on DESC, a.workorder_no DESC";

    $colors = array(
        "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", "#D1C4E9", "#FFE0B2",
        "#F8BBD0", "#B2EBF2", "#E6EE9C", "#FFECB3", "#CFD8DC", "#F0F4C3",
        "#DCEDC8", "#F5F5F5", "#E1BEE7", "#BBDEFB", "#FFCDD2", "#D7CCC8",
        "#FFCC80", "#C8E6C9"
    );
    $colorMap = array();
    $colorIndex = 0;
    $allRows = array();
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!po_wo_planner_row_visible($plannerCtx, $row, $empId)) {
                continue;
            }
            $po = $row['order_no'] ?? '';
            if ($po !== '' && !isset($colorMap[$po])) {
                $colorMap[$po] = $colors[$colorIndex % count($colors)];
                $colorIndex++;
            }
            $row['bg_color'] = $colorMap[$po] ?? '#F5F5F5';
            $woPlantId = $row['plant_id'] ?? $plantId;
            po_attach_fo_planner_info($conn, $row, $woPlantId);
            if ($search !== '') {
                $sLower = strtolower($search);
                $plannerLabel = strtolower(trim((string)($row['planner_to_display'] ?? '')));
                $matched = (
                    stripos($row['workorder_no'] ?? '', $search) !== false
                    || stripos($row['order_no'] ?? '', $search) !== false
                    || stripos($row['product_code'] ?? '', $search) !== false
                    || stripos($row['mainGroupName'] ?? '', $search) !== false
                    || stripos($row['groupcode'] ?? '', $search) !== false
                    || stripos($row['product_name'] ?? '', $search) !== false
                    || ($plannerLabel !== '' && strpos($plannerLabel, $sLower) !== false)
                );
                if (!$matched) {
                    continue;
                }
            }
            $allRows[] = $row;
        }
    }

    $total = count($allRows);
    $output = array_slice($allRows, $offset, $limit);

    echo json_encode(array(
        'status' => 'success',
        'data' => $output,
        'can_plan_work_orders' => $output,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
    ));
}

      else if ($_GET["type"] == "rejectCanPlanWorkOrder") {
    $input = json_decode(file_get_contents('php://input'), true);
    $workorder_no = trim($input['workorder_no'] ?? '');

    if ($workorder_no === '') {
        echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
        exit;
    }

    $plantId = $_GET['plant_id'] ?? '';
    $woEsc = mysqli_real_escape_string($conn, $workorder_no);

    $checkSql = "SELECT workorder_no, status FROM Work_order_materials
        WHERE workorder_no = '".$woEsc."' AND plant_id = '".mysqli_real_escape_string($conn, $plantId)."'
        LIMIT 1";
    $checkRes = $conn->query($checkSql);
    if (!$checkRes || $checkRes->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Work order not found']);
        exit;
    }

    $woRow = $checkRes->fetch_assoc();
    if (($woRow['status'] ?? '') === 'Rejected') {
        echo json_encode(['status' => 'error', 'message' => 'Work order is already rejected']);
        exit;
    }

    if (($woRow['status'] ?? '') === 'Pending Verification') {
        echo json_encode(['status' => 'error', 'message' => 'Cannot reject — work order is already sent for verification']);
        exit;
    }

    releaseWorkOrderBookedStock($conn, $workorder_no, $plantId, $_GET['emp_id'], $entry_date);

    $logEntryDate = date('Y-m-d H:i:s');
    $actionWithWO = 'rejectCanPlanWorkOrder: ' . $woEsc;
    $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url)
        VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."',
        '".mysqli_real_escape_string($conn, $_GET['department'])."', '".mysqli_real_escape_string($conn, $_GET['emp_id'])."',
        '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $_GET['description'] ?? '')."')";
    $conn->query($logSql);

    echo json_encode([
        'status' => 'success',
        'message' => 'Work order rejected. Booked/hold quantities released for other work orders.',
    ]);
}

      else if ($_GET["type"] == "getSendForBatchAllocationLog") {
    $plantId = $_GET['plant_id'] ?? '';
    $plantEsc = mysqli_real_escape_string($conn, $plantId);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    $search = trim((string)($_GET['search'] ?? ''));
    $allRows = array();
    $shortageInfo = array();

    $where = "a.status = 'Sent for Batch Allocation'";
    if ($plantEsc !== '') {
        $where .= " AND a.plant_id = '".$plantEsc."'";
    }
    if ($search !== '') {
        $s = mysqli_real_escape_string($conn, $search);
        $where .= " AND (
            a.workorder_no LIKE '%".$s."%'
            OR a.order_no LIKE '%".$s."%'
            OR a.product_code LIKE '%".$s."%'
            OR b.mainGroupName LIKE '%".$s."%'
            OR COALESCE(NULLIF(TRIM(a.groupcode), ''), NULLIF(TRIM(b.groupcode), '')) LIKE '%".$s."%'
            OR COALESCE(NULLIF(TRIM(b.product_name), ''), (SELECT product_name FROM product c
                WHERE c.product_code = a.product_code LIMIT 1)) LIKE '%".$s."%'
        )";
    }

    $sql = "SELECT a.*, b.product_code, b.work_order_planned_qty, b.planMonth,
            b.mainGroupName, b.packingStyle AS packing_type,
            b.deliveryDate, b.planQty AS order_qty, b.planUnit,
            COALESCE(NULLIF(TRIM(b.product_name), ''), (SELECT product_name FROM product c
                WHERE c.product_code = a.product_code LIMIT 1)) AS product_name
            FROM Work_order_materials a
            " . po_wo_om_join_sql('a', 'b') . "
            WHERE ".$where."
            ORDER BY a.Wo_Generated_on DESC, a.workorder_no DESC";

    $result = $conn->query($sql);

    $colors = array(
        "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", "#D1C4E9", "#FFE0B2",
        "#F8BBD0", "#B2EBF2", "#E6EE9C", "#FFECB3", "#CFD8DC", "#F0F4C3",
        "#DCEDC8", "#F5F5F5", "#E1BEE7", "#BBDEFB", "#FFCDD2", "#D7CCC8",
        "#FFCC80", "#C8E6C9"
    );
    $colorMap = array();
    $colorIndex = 0;

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            po_enrich_verification_wo_row($conn, $row, $plantId, $colorMap, $colors, $colorIndex, $shortageInfo);
            $allRows[] = $row;
        }
    }

    $total = count($allRows);
    $output = array_slice($allRows, $offset, $limit);

    echo json_encode(array(
        'status' => 'success',
        'can_plan_work_orders' => $output,
        'data' => $output,
        'work_orders' => $output,
        'shortage_info' => $shortageInfo,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
    ));
}

      else if ($_GET["type"] == "rejectPendingVerificationWO") {
    $input = json_decode(file_get_contents('php://input'), true);
    $workorder_no = trim($input['workorder_no'] ?? '');

    if ($workorder_no === '') {
        echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
        exit;
    }

    $plantId = $_GET['plant_id'] ?? '';
    $woEsc = mysqli_real_escape_string($conn, $workorder_no);

    $checkSql = "SELECT workorder_no, status FROM Work_order_materials
        WHERE workorder_no = '".$woEsc."' AND plant_id = '".mysqli_real_escape_string($conn, $plantId)."'
        LIMIT 1";
    $checkRes = $conn->query($checkSql);
    if (!$checkRes || $checkRes->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Work order not found']);
        exit;
    }

    $woRow = $checkRes->fetch_assoc();
    if (($woRow['status'] ?? '') === 'Rejected') {
        echo json_encode(['status' => 'error', 'message' => 'Work order is already rejected']);
        exit;
    }

    if (($woRow['status'] ?? '') !== 'Pending Verification' && !in_array(($woRow['status'] ?? ''), array('Verified', 'Verified - Shortage'), true)) {
        echo json_encode(['status' => 'error', 'message' => 'Only work orders pending verification can be rejected here']);
        exit;
    }

    releaseWorkOrderBookedStock($conn, $workorder_no, $plantId, $_GET['emp_id'], $entry_date);

    $logEntryDate = date('Y-m-d H:i:s');
    $actionWithWO = 'rejectPendingVerificationWO: ' . $woEsc;
    $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url)
        VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."',
        '".mysqli_real_escape_string($conn, $_GET['department'])."', '".mysqli_real_escape_string($conn, $_GET['emp_id'])."',
        '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $_GET['description'] ?? '')."')";
    $conn->query($logSql);

    echo json_encode([
        'status' => 'success',
        'message' => 'Work order rejected. Booked/hold quantities released.',
    ]);
}

      else if ($_GET["type"] == "getShortagesPlannedWOSummary") {
    header('Content-Type: application/json');
    medicap_require_helper('mrp_wo_schema_helpers.php');
    ensureWoDeductionShortageQueueColumns($conn);
    $plantId = $_GET['plant_id'] ?? '';
    $raisedOnly = isset($_GET['raised']) && ($_GET['raised'] === '1' || $_GET['raised'] === 'true');
    echo json_encode(shortagesBuildPlannedWoSummary($conn, $plantId, $raisedOnly));
}

      else if ($_GET["type"] == "sendMaterialForDirectorApproval") {
        
        $orders = $input["orders"];   // Array of order_no values: ["FO1000035","FO1000036B"]
$entry_date = date("Y-m-d H:i:s");

 

     $sql = "UPDATE WO_deductions  
            SET 
                director_approval='For Approval',
                Director_data='".json_encode($input)."' ,
                Send_director_by='".$_GET['emp_id']."',
                Send_director_On='$entry_date'
            WHERE id='".$input['workorder_ID']."'";

    $conn->query($sql);
   
    
    
    
 

echo json_encode(["status" => "success"]);

    }

      else if ($_GET["type"] == "getCanPlannedWOPlaning_STP") {
      
        $output = Array();
        
                   $sql = "SELECT 
                                a.*,
                            
                                (
                                    SELECT COUNT(*) 
                                    FROM WO_deductions b
                                    WHERE b.workorder_no = a.workorder_no
                                ) AS total_deductions,
                            
                                (
                                    SELECT COUNT(*) 
                                    FROM WO_deductions b
                                    WHERE b.workorder_no = a.workorder_no 
                                      AND (b.status='CAN_PLAN' and b.indent_status = 'Not Raised') or (b.status='Indent Sent' and b.indent_status = 'Raised')
                                ) AS indend_raised,
                                (select product_name from product p where a.product_code=p.product_code) as product_name,
                                (select product_code from product p where a.product_code=p.product_code) as product_code
                            
                            FROM 
                                Work_order_materials a
                            WHERE 
                                a.status NOT IN ('Cancel', 'Hold')
                            HAVING 
                                total_deductions > 0;";
                
                $result = $conn->query($sql);
                $colors = [
                "#FFCCCB", // light red
                "#CCFFCC", // light green
                "#CCE5FF", // light blue
                "#FFFACD", // lemon chiffon
                "#D1C4E9", // lavender
                "#FFE0B2", // light orange
                "#F8BBD0", // pink
                "#B2EBF2", // cyan
                "#E6EE9C", // light lime green
                "#FFECB3", // soft yellow-orange
                "#CFD8DC", // blue grey
                "#F0F4C3", // very light olive
                "#DCEDC8", // pastel green
                "#F5F5F5", // light grey
                "#E1BEE7", // soft purple
                "#BBDEFB", // lighter sky blue
                "#FFCDD2", // light rose
                "#D7CCC8", // light taupe
                "#FFCC80", // peach
                "#C8E6C9"  // mint green
            ];
            $colorMap = [];
            $colorIndex = 0; 

                if ($result->num_rows > 0) {
                 
                    
                    while ($row = $result->fetch_assoc()) {
                               $po = $row["order_no"];
                                if (!isset($colorMap[$po])) {
                                    $colorMap[$po] = $colors[$colorIndex % count($colors)];
                                    $colorIndex++;
                                }
                                $row["bg_color"] = $colorMap[$po];
                             $output1 = Array();
                        $sql1 = "SELECT a.*,b.material_name FROM WO_deductions a left join material b on a.material_code=b.material_code WHERE a.workorder_no='".$row["workorder_no"]."'   ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                 $row['materials'] =$output1;
                 $row['Deductions'] =$output1;
                 
                 
                  $output1 = Array();
                        $sql1 = "SELECT a.* FROM linemaster a left join  linemaster_mapped_Product b on a.id = b.linemaster_id WHERE b.product_code='".$row["product_code"]."' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                          
                       
                            		     $output11 = Array();
                                                $sql11 = "SELECT * FROM  linemaster_mapped_Equipment   WHERE linemaster_id='".$row1["id"]."' ";
                                                
                                                $result11 = $conn->query($sql11);
                                                if ($result11->num_rows > 0) {
                                                    while ($row11 = $result11->fetch_assoc()) {
                                                        $output11[] = $row11;
                                                    }
                                                }
                            		    
                            		    
                            	 
                            		     $row1["equipmentList"] = $output11;
                            		     
                                
                                
                                
                                
                                $output1[] = $row1;
                            }
                        }
                 
                 
                 $row["selectedLines"] = json_decode($row["selectedLines"]); 
                   $row["Lines"] = $output1;
                        $output[] = $row;
                    }
                }
        echo json_encode($output);
    }

      else if ($_GET["type"] == "update_SelectedLines") {
      
 
                 $sql = "UPDATE Work_order_materials  SET selectedLines = '".json_encode($input['lineList'])."'  where id = '".$input['id']."' ";
                if ($conn->query($sql)) {
                     $status1 = true;
                } else {
                    $status1 = false;
              
                    
                }
        
        if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
        
        
        
    }

} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status' => 'error', 'message' => 'Invalid session token. Please login again.'));
}
$conn->close();
