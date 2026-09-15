<?php
/**
 * Minimal schema + shortage helpers for Medicap MRP Generate WO / Shortages.
 * No business-flow changes — only ensure missing columns/functions exist.
 */

if (!function_exists('medicap_ensure_table_columns')) {
    function medicap_ensure_table_columns($conn, $table, array $columns) {
        if (!($conn instanceof mysqli) || $table === '' || count($columns) === 0) {
            return;
        }
        $tCheck = @$conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($table)."'");
        if (!$tCheck || $tCheck->num_rows === 0) {
            return;
        }
        foreach ($columns as $colName => $colDef) {
            $col = @$conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$conn->real_escape_string($colName)."'");
            if (!$col || $col->num_rows === 0) {
                try {
                    $conn->query("ALTER TABLE `".$table."` ADD COLUMN `".$colName."` ".$colDef);
                } catch (Throwable $e) {
                    // mysqli exception mode: duplicate column / race
                }
            }
        }
    }
}

if (!function_exists('ensureWoVerificationColumns')) {
    function ensureWoVerificationColumns($conn) {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        medicap_ensure_table_columns($conn, 'Work_order_materials', array(
            'send_for_planning_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'send_for_planning_on' => "VARCHAR(50) NULL DEFAULT NULL",
            'send_for_verification_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'send_for_verification_on' => "VARCHAR(50) NULL DEFAULT NULL",
            'send_for_analysis_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'send_for_analysis_date' => "VARCHAR(50) NULL DEFAULT NULL",
            'Wo_Generated_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'Wo_Generated_on' => "VARCHAR(50) NULL DEFAULT NULL",
            'wo_generated_by_digi_sign_date' => "VARCHAR(50) NULL DEFAULT NULL",
            'stock_verified_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'stock_verified_on' => "VARCHAR(50) NULL DEFAULT NULL",
            'snef_for_planning_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'snef_for_planning_on' => "VARCHAR(50) NULL DEFAULT NULL",
            'cancel_hold_by' => "VARCHAR(100) NULL DEFAULT NULL",
            'cancel_hold_on' => "VARCHAR(50) NULL DEFAULT NULL",
        ));
        // Keep typo legacy column in sync when new column is empty
        @$conn->query("UPDATE Work_order_materials
            SET send_for_planning_on = snef_for_planning_on
            WHERE (send_for_planning_on IS NULL OR TRIM(IFNULL(send_for_planning_on,'')) = '')
              AND snef_for_planning_on IS NOT NULL AND TRIM(IFNULL(snef_for_planning_on,'')) <> ''");
        @$conn->query("UPDATE Work_order_materials
            SET send_for_planning_by = snef_for_planning_by
            WHERE (send_for_planning_by IS NULL OR TRIM(IFNULL(send_for_planning_by,'')) = '')
              AND snef_for_planning_by IS NOT NULL AND TRIM(IFNULL(snef_for_planning_by,'')) <> ''");
        // Sync digi-sign date from Wo_Generated_on when blank
        @$conn->query("UPDATE Work_order_materials
            SET wo_generated_by_digi_sign_date = Wo_Generated_on
            WHERE (wo_generated_by_digi_sign_date IS NULL OR TRIM(IFNULL(wo_generated_by_digi_sign_date,'')) = '')
              AND Wo_Generated_on IS NOT NULL AND TRIM(IFNULL(Wo_Generated_on,'')) <> ''");
        // Empty stub so existing SQL that joins/lookups bulkMaster do not fatal on Medicap DB
        $bm = @$conn->query("SHOW TABLES LIKE 'bulkMaster'");
        if (!$bm || $bm->num_rows === 0) {
            @$conn->query("CREATE TABLE IF NOT EXISTS `bulkMaster` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `bulkCode` VARCHAR(100) NULL DEFAULT NULL,
                `bulkName` VARCHAR(255) NULL DEFAULT NULL,
                `material_type` VARCHAR(100) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_bulkCode` (`bulkCode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            medicap_ensure_table_columns($conn, 'bulkMaster', array(
                'bulkCode' => "VARCHAR(100) NULL DEFAULT NULL",
                'bulkName' => "VARCHAR(255) NULL DEFAULT NULL",
                'material_type' => "VARCHAR(100) NULL DEFAULT NULL",
            ));
        }
        $done = true;
    }
}

if (!function_exists('ensureWoDeductionShortageQueueColumns')) {
    function ensureWoDeductionShortageQueueColumns($conn) {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        medicap_ensure_table_columns($conn, 'WO_deductions', array(
            'indent_status' => "VARCHAR(50) NULL DEFAULT 'Not Raised'",
            'shortage' => "DECIMAL(18,6) NULL DEFAULT 0",
            'deducted_from_RM' => "DECIMAL(18,6) NULL DEFAULT 0",
            'deducted_from_MC' => "DECIMAL(18,6) NULL DEFAULT 0",
            'mat_type' => "VARCHAR(50) NULL DEFAULT NULL",
            'unit' => "VARCHAR(50) NULL DEFAULT NULL",
            'plan_qty' => "DECIMAL(18,6) NULL DEFAULT 0",
            'work_order_id' => "INT NULL DEFAULT NULL",
            'workorder_no' => "VARCHAR(100) NULL DEFAULT NULL",
            'material_code' => "VARCHAR(100) NULL DEFAULT NULL",
            'plant_id' => "VARCHAR(20) NULL DEFAULT NULL",
            'indexData' => "LONGTEXT NULL DEFAULT NULL",
            'qty_status' => "VARCHAR(50) NULL DEFAULT 'Pending'",
            'status' => "VARCHAR(100) NULL DEFAULT NULL",
        ));
        ensureWoVerificationColumns($conn);
        $done = true;
    }
}

if (!function_exists('shortagesBuildPlannedWoSummary')) {
    /**
     * Summary rows for /planning/Shortages (material-wise shortage queue).
     * Matches frontend createSummaryMaterial() field expectations.
     */
    function shortagesBuildPlannedWoSummary($conn, $plantId = '', $raisedOnly = false) {
        $output = array();
        if (!($conn instanceof mysqli)) {
            return $output;
        }
        ensureWoDeductionShortageQueueColumns($conn);
        $plantEsc = $conn->real_escape_string(trim((string)$plantId));
        $indentFilter = $raisedOnly
            ? " AND LOWER(TRIM(IFNULL(a.indent_status,''))) NOT IN ('not raised','')"
            : " AND (a.indent_status IS NULL OR TRIM(IFNULL(a.indent_status,'')) = '' OR LOWER(TRIM(IFNULL(a.indent_status,''))) = 'not raised')";
        $plantFilter = $plantEsc !== ''
            ? " AND (a.plant_id = '{$plantEsc}' OR b.plant_id = '{$plantEsc}' OR a.plant_id IS NULL OR TRIM(IFNULL(a.plant_id,'')) = '')"
            : '';

        $sql = "SELECT
                    a.material_code,
                    COALESCE(
                        (SELECT material_name FROM material WHERE material_code = a.material_code LIMIT 1),
                        a.material_code
                    ) AS material_name,
                    COALESCE(a.mat_type,
                        (SELECT material_type FROM material WHERE material_code = a.material_code LIMIT 1)
                    ) AS mat_type,
                    COALESCE(a.unit, (SELECT unit FROM material WHERE material_code = a.material_code LIMIT 1), '') AS uom,
                    COUNT(DISTINCT a.work_order_id) AS wo_count,
                    SUM(CAST(IFNULL(a.shortage,0) AS DECIMAL(18,6))) AS total_shortage
                FROM WO_deductions a
                LEFT JOIN Work_order_materials b ON b.id = a.work_order_id
                WHERE CAST(IFNULL(a.shortage,0) AS DECIMAL(18,6)) > 0
                  {$indentFilter}
                  {$plantFilter}
                GROUP BY a.material_code
                HAVING total_shortage > 0
                ORDER BY a.material_code ASC";
        $res = @$conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $avail = 0;
                $codeEsc = $conn->real_escape_string((string)($row['material_code'] ?? ''));
                if ($codeEsc !== '') {
                    $avRes = @$conn->query("SELECT IFNULL(SUM(CAST(qty AS DECIMAL(18,6))),0) AS q
                        FROM stock_book WHERE material_code='{$codeEsc}' AND status='Approved'");
                    if ($avRes && ($av = $avRes->fetch_assoc())) {
                        $avail = floatval($av['q']);
                    }
                }
                $row['available_in_hand'] = $avail;
                $row['Matunit'] = $row['uom'] ?? '';
                $row['material_type'] = $row['mat_type'] ?? '';
                $row['openPO'] = 0;
                $row['mother_available_qty'] = $avail;
                $row['planning_class'] = 'rm';
                $output[] = $row;
            }
        }
        return $output;
    }
}

if (!function_exists('releaseWorkOrderBookedStock')) {
    function releaseWorkOrderBookedStock($conn, $workorder_no, $plantId = '', $empId = '', $entryDate = '') {
        // Soft no-op if booking tables/rows are absent — reject path should not fatal.
        if (!($conn instanceof mysqli)) {
            return false;
        }
        $woEsc = $conn->real_escape_string((string)$workorder_no);
        if ($woEsc === '') {
            return false;
        }
        $t = @$conn->query("SHOW TABLES LIKE 'mrp_bookedStock'");
        if ($t && $t->num_rows > 0) {
            @$conn->query("DELETE FROM mrp_bookedStock WHERE workorder_no='{$woEsc}'");
        }
        return true;
    }
}

if (!function_exists('generateWoFetchWorkOrderContextRow')) {
    function generateWoFetchWorkOrderContextRow($conn, $woId, $plantId = '') {
        $woId = intval($woId);
        if ($woId <= 0 || !($conn instanceof mysqli)) {
            return null;
        }
        $plantEsc = $conn->real_escape_string(trim((string)$plantId));
        $sql = "SELECT a.*,
                    COALESCE(
                      (SELECT product_name FROM product p WHERE p.product_code = a.product_code LIMIT 1),
                      ''
                    ) AS product_name,
                    COALESCE(
                      (SELECT category FROM product p WHERE p.product_code = a.product_code LIMIT 1),
                      ''
                    ) AS product_category,
                    a.product_code AS wo_product_code
                FROM Work_order_materials a
                WHERE a.id = {$woId}";
        if ($plantEsc !== '') {
            $sql .= " AND (a.plant_id = '{$plantEsc}' OR a.plant_id IS NULL OR TRIM(IFNULL(a.plant_id,'')) = '')";
        }
        $sql .= " LIMIT 1";
        $res = $conn->query($sql);
        return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    }
}

if (!function_exists('generateWoFetchBomForWorkOrderRow')) {
    function generateWoFetchBomForWorkOrderRow($conn, $row) {
        $out = array(
            'bfr_no' => '',
            'bfr_info_id' => 0,
            'mfr_no' => '',
            'bfr_pack_sizes_id' => null,
            'bom_resolved' => false,
            'bom_missing' => true,
            'raw_materials' => array(),
            'packing_materials' => array(),
        );
        if (!is_array($row) || !($conn instanceof mysqli)) {
            return $out;
        }
        $code = trim((string)($row['product_code'] ?? ($row['wo_product_code'] ?? '')));
        if ($code === '') {
            return $out;
        }
        $pc = $conn->real_escape_string($code);
        $plantEsc = $conn->real_escape_string(trim((string)($row['plant_id'] ?? '')));
        $sql = "SELECT id, bfr_no, mfr_no, raw_materials, packing_materials, batch_formula_weight, rm_batch_size_unit
                FROM batch_formula_info
                WHERE product_code='{$pc}'
                  AND LOWER(TRIM(IFNULL(status,''))) IN ('approve','approved')";
        if ($plantEsc !== '') {
            $sql .= " AND (plant_id='{$plantEsc}' OR plant_id IS NULL OR TRIM(IFNULL(plant_id,''))='')";
        }
        $sql .= " ORDER BY id DESC LIMIT 1";
        $res = $conn->query($sql);
        if ($res && ($bfr = $res->fetch_assoc())) {
            $out['bfr_no'] = $bfr['bfr_no'] ?? '';
            $out['bfr_info_id'] = intval($bfr['id'] ?? 0);
            $out['mfr_no'] = $bfr['mfr_no'] ?? '';
            $rm = json_decode($bfr['raw_materials'] ?? '[]', true);
            $pm = json_decode($bfr['packing_materials'] ?? '[]', true);
            $out['raw_materials'] = is_array($rm) ? $rm : array();
            $out['packing_materials'] = is_array($pm) ? $pm : array();
            $out['bom_resolved'] = (count($out['raw_materials']) + count($out['packing_materials'])) > 0;
            $out['bom_missing'] = !$out['bom_resolved'];
        }
        return $out;
    }
}

if (!function_exists('generateWoBuildPlanMaterialsForDisplay')) {
    function generateWoBuildPlanMaterialsForDisplay($conn, $bom, $plantId = '') {
        $lines = array();
        if (!is_array($bom)) {
            return $lines;
        }
        foreach (array('raw_materials' => 'RM', 'packing_materials' => 'PM') as $key => $cat) {
            $list = $bom[$key] ?? array();
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $m) {
                if (!is_array($m)) {
                    continue;
                }
                $code = trim((string)($m['material_code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $req = floatval($m['batch_qty'] ?? ($m['total_qty'] ?? ($m['qty'] ?? 0)));
                $avail = 0;
                if (function_exists('gw_get_material_availability')) {
                    $av = gw_get_material_availability($conn, $code);
                    $avail = floatval($av['available_qty'] ?? 0);
                }
                $lines[] = array(
                    'material_code' => $code,
                    'material_name' => $m['material_name'] ?? $code,
                    'material_type' => $m['material_subtype'] ?? ($m['material_type'] ?? ''),
                    'mat_category' => $cat,
                    'unit' => $m['unit'] ?? ($m['unit_name'] ?? ''),
                    'required_qty' => $req,
                    'available_qty' => $avail,
                    'short_qty' => max(0, $req - $avail),
                );
            }
        }
        return $lines;
    }
}
