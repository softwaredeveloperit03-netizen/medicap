<?php

if (!function_exists('gw_ensure_mrp_shortages_indent_log_table')) {
    function gw_ensure_mrp_shortages_indent_log_table($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS mrp_shortages_indent_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL DEFAULT 'RAISE_INDENT',
            event_status VARCHAR(20) NOT NULL DEFAULT 'success',
            source_screen VARCHAR(50) NOT NULL DEFAULT 'Shortages',
            raised_by VARCHAR(50) DEFAULT NULL,
            raised_by_name VARCHAR(120) DEFAULT NULL,
            department VARCHAR(120) DEFAULT NULL,
            plant_id VARCHAR(50) DEFAULT NULL,
            event_datetime DATETIME NOT NULL,
            plan_month VARCHAR(50) DEFAULT NULL,
            material_code VARCHAR(80) DEFAULT NULL,
            material_name VARCHAR(255) DEFAULT NULL,
            forecast_no VARCHAR(100) DEFAULT NULL,
            order_no VARCHAR(100) DEFAULT NULL,
            workorder_no VARCHAR(100) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            client_code VARCHAR(80) DEFAULT NULL,
            client_name VARCHAR(255) DEFAULT NULL,
            required_qty DECIMAL(18,4) DEFAULT NULL,
            raised_indent_qty DECIMAL(18,4) DEFAULT NULL,
            qty_unit VARCHAR(30) DEFAULT NULL,
            qty_source VARCHAR(30) DEFAULT NULL,
            rm_shortage DECIMAL(18,4) DEFAULT NULL,
            rm_balance DECIMAL(18,4) DEFAULT NULL,
            consolidated_shortage_qty DECIMAL(18,4) DEFAULT NULL,
            base_shortage DECIMAL(18,4) DEFAULT NULL,
            booked_qty_for_wo DECIMAL(18,4) DEFAULT NULL,
            indent_no VARCHAR(80) DEFAULT NULL,
            indent_id INT DEFAULT NULL,
            request_no VARCHAR(80) DEFAULT NULL,
            bulk_raise TINYINT(1) NOT NULL DEFAULT 0,
            remark TEXT,
            event_detail LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_datetime (event_datetime),
            INDEX idx_material_code (material_code),
            INDEX idx_workorder_no (workorder_no),
            INDEX idx_order_no (order_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
    }
}

if (!function_exists('gw_should_save_mrp_shortages_indent_log')) {
    function gw_should_save_mrp_shortages_indent_log($input) {
        if (!is_array($input)) {
            return false;
        }
        if (!empty($input['log_source']) && $input['log_source'] === 'Shortages') {
            return true;
        }
        return !empty($input['shortages_log']) && is_array($input['shortages_log']);
    }
}

if (!function_exists('gw_table_exists')) {
    function gw_table_exists($conn, $tableName) {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        if ($tableName === '') {
            return false;
        }
        $res = $conn->query("SHOW TABLES LIKE '$tableName'");
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('gw_ensure_mrp_shortages_indent_log_columns')) {
    function gw_ensure_mrp_shortages_indent_log_columns($conn) {
        @$conn->query("ALTER TABLE mrp_shortages_indent_log ADD COLUMN rm_balance DECIMAL(18,4) DEFAULT NULL AFTER rm_shortage");
        @$conn->query("ALTER TABLE mrp_shortages_indent_log ADD COLUMN consolidated_shortage_qty DECIMAL(18,4) DEFAULT NULL AFTER rm_balance");
        @$conn->query("ALTER TABLE mrp_shortages_indent_log ADD INDEX idx_indent_id (indent_id)");
    }
}

if (!function_exists('gw_normalize_log_datetime')) {
    function gw_normalize_log_datetime($conn, $value) {
        $raw = trim((string)$value);
        if ($raw === '' || $raw === '0000-00-00 00:00:00' || $raw === '0000-00-00') {
            return date('Y-m-d H:i:s');
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', $ts);
    }
}

if (!function_exists('gw_planning_indent_indend_raw_filter_sql')) {
    /** SQL fragment: indend_raw rows raised from Planning / Shortages MRP flow. */
    function gw_planning_indent_indend_raw_filter_sql($conn, $alias = 'ir') {
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        if ($a === '') {
            $a = 'ir';
        }
        $parts = [
            "{$a}.requirement = 'Planning MRP'",
            "({$a}.purpose = 'Work Order' AND COALESCE({$a}.required_for, '') <> '')",
            "{$a}.requirement LIKE '%MRP%'",
        ];
        if (gw_table_exists($conn, 'mrp_raised_indnd_qty')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM mrp_raised_indnd_qty m
                WHERE CAST(m.indend_id AS CHAR) = CAST({$a}.id AS CHAR)
            )";
        }
        if (gw_table_exists($conn, 'WO_deductions')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM WO_deductions wd
                WHERE CAST(wd.indent_id AS CHAR) = CAST({$a}.id AS CHAR)
                  AND wd.indent_status IN ('Raised', 'Indent Sent')
            )";
        }
        return '(' . implode(' OR ', $parts) . ')';
    }
}

if (!function_exists('gw_build_shortages_log_input_from_indend_raw')) {
    function gw_build_shortages_log_input_from_indend_raw($ir) {
        $requiredFor = json_decode($ir['required_for'] ?? '[]', true);
        if (!is_array($requiredFor)) {
            $requiredFor = [];
        }
        if (empty($requiredFor)) {
            $requiredFor = [[]];
        }

        $planMonth = '';
        if (!empty($requiredFor[0]['plan_month'])) {
            $planMonth = $requiredFor[0]['plan_month'];
        }

        return [
            'log_source' => 'Shortages',
            'material_code' => $ir['material_code'] ?? '',
            'material_name' => $ir['material_name'] ?? '',
            'client_code' => $ir['client_code'] ?? '',
            'remark' => trim(($ir['planning_reamrk'] ?? '') !== '' ? $ir['planning_reamrk'] : 'Backfilled from previous indent'),
            'required_for' => $requiredFor,
            'shortages_log' => [
                'plan_month' => $planMonth,
                'raised_indent_qty' => $ir['req_qty'] ?? 0,
                'qty_unit' => $ir['unit'] ?? '',
                'remark' => trim(($ir['planning_reamrk'] ?? '') !== '' ? $ir['planning_reamrk'] : 'Backfilled from previous indent'),
            ],
        ];
    }
}

if (!function_exists('gw_insert_mrp_shortages_indent_log_direct')) {
    function gw_insert_mrp_shortages_indent_log_direct($conn, $fields, $meta = []) {
        gw_ensure_mrp_shortages_indent_log_table($conn);
        gw_ensure_mrp_shortages_indent_log_columns($conn);

        $indentId = (int)($fields['indent_id'] ?? ($meta['indent_id'] ?? 0));
        if ($indentId > 0) {
            $exists = $conn->query("SELECT id FROM mrp_shortages_indent_log WHERE indent_id = $indentId LIMIT 1");
            if ($exists && $exists->num_rows > 0) {
                return (int)$exists->fetch_assoc()['id'];
            }
        }

        date_default_timezone_set('Asia/Kolkata');
        $eventDatetime = mysqli_real_escape_string(
            $conn,
            gw_normalize_log_datetime($conn, $fields['event_datetime'] ?? ($meta['event_datetime'] ?? ''))
        );
        $raisedBy = mysqli_real_escape_string($conn, $fields['raised_by'] ?? ($meta['emp_id'] ?? ($_GET['emp_id'] ?? '')));
        $raisedByName = mysqli_real_escape_string($conn, $fields['raised_by_name'] ?? ($meta['raised_by_name'] ?? ''));
        $department = mysqli_real_escape_string($conn, $fields['department'] ?? ($meta['department'] ?? ($_GET['department'] ?? '')));
        $plantId = mysqli_real_escape_string($conn, $fields['plant_id'] ?? ($meta['plant_id'] ?? ($_GET['plant_id'] ?? '')));
        $planMonth = mysqli_real_escape_string($conn, $fields['plan_month'] ?? '');
        $materialCode = mysqli_real_escape_string($conn, $fields['material_code'] ?? '');
        $materialName = mysqli_real_escape_string($conn, $fields['material_name'] ?? '');
        $forecastNo = mysqli_real_escape_string($conn, $fields['forecast_no'] ?? ($fields['order_no'] ?? ''));
        $orderNo = mysqli_real_escape_string($conn, $fields['order_no'] ?? '');
        $workorderNo = mysqli_real_escape_string($conn, $fields['workorder_no'] ?? '');
        $productCode = mysqli_real_escape_string($conn, $fields['product_code'] ?? '');
        $productName = mysqli_real_escape_string($conn, $fields['product_name'] ?? '');
        $clientCode = mysqli_real_escape_string($conn, $fields['client_code'] ?? '');
        $clientName = mysqli_real_escape_string($conn, $fields['client_name'] ?? '');
        $requiredQty = (float)($fields['required_qty'] ?? 0);
        $raisedQty = (float)($fields['raised_indent_qty'] ?? 0);
        $qtyUnit = mysqli_real_escape_string($conn, $fields['qty_unit'] ?? '');
        $qtySource = mysqli_real_escape_string($conn, $fields['qty_source'] ?? 'shortage');
        $rmShortage = (float)($fields['rm_shortage'] ?? 0);
        $rmBalance = (float)($fields['rm_balance'] ?? 0);
        $consolidatedShortage = (float)($fields['consolidated_shortage_qty'] ?? $rmShortage);
        $baseShortage = (float)($fields['base_shortage'] ?? $consolidatedShortage);
        $bookedQty = (float)($fields['booked_qty_for_wo'] ?? 0);
        $indentNo = mysqli_real_escape_string($conn, $fields['indent_no'] ?? ($meta['indent_no'] ?? ''));
        $requestNo = mysqli_real_escape_string($conn, $fields['request_no'] ?? ($meta['request_no'] ?? ''));
        $bulkRaise = !empty($fields['bulk_raise']) ? 1 : 0;
        $remark = mysqli_real_escape_string($conn, $fields['remark'] ?? '');
        $detailEsc = mysqli_real_escape_string($conn, json_encode(['backfill' => !empty($fields['backfill'])], JSON_UNESCAPED_UNICODE));

        $sql = "INSERT INTO mrp_shortages_indent_log (
            event_type, event_status, source_screen, raised_by, raised_by_name, department, plant_id,
            event_datetime, plan_month, material_code, material_name, forecast_no, order_no, workorder_no,
            product_code, product_name, client_code, client_name, required_qty, raised_indent_qty, qty_unit,
            qty_source, rm_shortage, rm_balance, consolidated_shortage_qty, base_shortage, booked_qty_for_wo,
            indent_no, indent_id, request_no, bulk_raise, remark, event_detail
        ) VALUES (
            'RAISE_INDENT', 'success', 'Shortages', '$raisedBy', '$raisedByName', '$department', '$plantId',
            '$eventDatetime', '$planMonth', '$materialCode', '$materialName', '$forecastNo', '$orderNo', '$workorderNo',
            '$productCode', '$productName', '$clientCode', '$clientName', '$requiredQty', '$raisedQty', '$qtyUnit',
            '$qtySource', '$rmShortage', '$rmBalance', '$consolidatedShortage', '$baseShortage', '$bookedQty',
            '$indentNo', '$indentId', '$requestNo', '$bulkRaise', '$remark', '$detailEsc'
        )";

        if (!$conn->query($sql)) {
            error_log('gw_insert_mrp_shortages_indent_log_direct failed: ' . $conn->error);
            return 0;
        }

        $logId = (int)$conn->insert_id;
        if ($logId > 0 && function_exists('gw_sync_mrp_indent_confirmation_from_shortages_log')) {
            gw_sync_mrp_indent_confirmation_from_shortages_log($conn, $logId, array_merge($meta, [
                'indent_id' => $indentId,
                'indent_no' => $fields['indent_no'] ?? ($meta['indent_no'] ?? ''),
                'request_no' => $fields['request_no'] ?? ($meta['request_no'] ?? ''),
            ]));
        }
        return $logId;
    }
}

if (!function_exists('gw_build_shortages_log_fields_from_indend_raw')) {
    function gw_build_shortages_log_fields_from_indend_raw($ir, $extra = []) {
        $requiredFor = json_decode($ir['required_for'] ?? '[]', true);
        if (!is_array($requiredFor)) {
            $requiredFor = [];
        }
        $req = !empty($requiredFor) && is_array($requiredFor[0]) ? $requiredFor[0] : [];
        $raisedQty = (float)($req['reqQty'] ?? ($req['Client_code_Indent'] ?? ($req['Mother_code_Indent'] ?? ($ir['req_qty'] ?? 0))));

        return array_merge([
            'indent_id' => (int)($ir['id'] ?? 0),
            'indent_no' => $ir['no'] ?? ($extra['indent_no'] ?? ''),
            'request_no' => $ir['request_no'] ?? '',
            'event_datetime' => $extra['event_datetime'] ?? ($ir['entry_date'] ?? ''),
            'raised_by' => $ir['entry_by'] ?? ($extra['raised_by'] ?? ''),
            'department' => $ir['department'] ?? '',
            'plant_id' => $ir['plant_id'] ?? '',
            'material_code' => $req['material_code'] ?? ($ir['material_code'] ?? ''),
            'material_name' => $req['material_name'] ?? ($ir['material_name'] ?? ''),
            'order_no' => $req['order_no'] ?? '',
            'forecast_no' => $req['order_no'] ?? '',
            'workorder_no' => $req['work_order_no'] ?? ($req['workorder_no'] ?? ($extra['workorder_no'] ?? '')),
            'product_code' => $req['product_code'] ?? '',
            'product_name' => $req['product_name'] ?? '',
            'client_code' => $req['client_code'] ?? ($ir['client_code'] ?? ''),
            'required_qty' => (float)($req['required_qty'] ?? $raisedQty),
            'raised_indent_qty' => $raisedQty,
            'rm_shortage' => (float)($req['rm_shortage'] ?? ($req['base_shortage'] ?? $raisedQty)),
            'consolidated_shortage_qty' => (float)($req['rm_shortage'] ?? ($req['base_shortage'] ?? $raisedQty)),
            'base_shortage' => (float)($req['base_shortage'] ?? $raisedQty),
            'qty_unit' => $req['Matunit'] ?? ($ir['unit'] ?? ''),
            'qty_source' => $req['indent_qty_source'] ?? 'shortage',
            'remark' => trim($ir['planning_reamrk'] ?? '') !== '' ? $ir['planning_reamrk'] : 'Backfilled from previous indent',
            'backfill' => 1,
        ], $extra);
    }
}

if (!function_exists('gw_ensure_shortages_log_for_raise')) {
    function gw_ensure_shortages_log_for_raise($conn, $input, $meta = []) {
        $indentId = (int)($meta['indent_id'] ?? 0);
        gw_save_mrp_shortages_indent_log($conn, $input, $meta);

        if ($indentId > 0) {
            $logRes = $conn->query("SELECT id FROM mrp_shortages_indent_log WHERE indent_id = $indentId LIMIT 1");
            if (!$logRes || $logRes->num_rows === 0) {
                $req = (!empty($input['required_for'][0]) && is_array($input['required_for'][0])) ? $input['required_for'][0] : [];
                $raisedQty = (float)($req['reqQty'] ?? ($input['shortages_log']['raised_indent_qty'] ?? 0));
                gw_insert_mrp_shortages_indent_log_direct($conn, [
                    'indent_id' => $indentId,
                    'indent_no' => $meta['indent_no'] ?? '',
                    'request_no' => $meta['request_no'] ?? '',
                    'material_code' => $req['material_code'] ?? ($input['material_code'] ?? ''),
                    'material_name' => $req['material_name'] ?? ($input['material_name'] ?? ''),
                    'order_no' => $req['order_no'] ?? '',
                    'workorder_no' => $req['work_order_no'] ?? ($req['workorder_no'] ?? ''),
                    'product_code' => $req['product_code'] ?? '',
                    'product_name' => $req['product_name'] ?? '',
                    'client_code' => $req['client_code'] ?? ($input['client_code'] ?? ''),
                    'required_qty' => (float)($req['required_qty'] ?? $raisedQty),
                    'raised_indent_qty' => $raisedQty,
                    'rm_shortage' => (float)($req['rm_shortage'] ?? ($input['shortages_log']['consolidated_shortage_qty'] ?? $raisedQty)),
                    'consolidated_shortage_qty' => (float)($input['shortages_log']['consolidated_shortage_qty'] ?? ($req['rm_shortage'] ?? $raisedQty)),
                    'rm_balance' => (float)($input['shortages_log']['rm_balance'] ?? 0),
                    'qty_unit' => $req['Matunit'] ?? ($input['shortages_log']['qty_unit'] ?? ''),
                    'qty_source' => $req['indent_qty_source'] ?? ($input['shortages_log']['qty_source'] ?? 'shortage'),
                    'plan_month' => $input['shortages_log']['plan_month'] ?? '',
                    'remark' => $input['remark'] ?? ($input['shortages_log']['remark'] ?? ''),
                    'raised_by' => $meta['emp_id'] ?? '',
                    'raised_by_name' => $meta['raised_by_name'] ?? '',
                    'department' => $meta['department'] ?? '',
                ], $meta);
            }
        }

        if (function_exists('gw_resolve_shortages_indent_raise_refs')) {
            return gw_resolve_shortages_indent_raise_refs($conn, $indentId, $meta);
        }
        return ['shortages_log_id' => 0, 'confirmation_id' => 0];
    }
}

if (!function_exists('gw_backfill_mrp_shortages_indent_log_batch')) {
    function gw_backfill_mrp_shortages_indent_log_batch($conn, $limit = 200) {
        gw_ensure_mrp_shortages_indent_log_table($conn);
        gw_ensure_mrp_shortages_indent_log_columns($conn);

        $limit = max(1, min(500, (int)$limit));
        $filter = gw_planning_indent_indend_raw_filter_sql($conn, 'ir');
        $sql = "SELECT ir.*
            FROM indend_raw ir
            LEFT JOIN mrp_shortages_indent_log l ON l.indent_id = ir.id
            WHERE l.id IS NULL
              AND $filter
            ORDER BY ir.entry_date DESC, ir.id DESC
            LIMIT $limit";
        $result = $conn->query($sql);
        if (!$result) {
            error_log('gw_backfill_mrp_shortages_indent_log_batch SQL error: ' . $conn->error);
            $sql = "SELECT ir.*
                FROM indend_raw ir
                LEFT JOIN mrp_shortages_indent_log l ON l.indent_id = ir.id
                WHERE l.id IS NULL
                  AND (ir.requirement = 'Planning MRP' OR ir.purpose = 'Work Order')
                ORDER BY ir.entry_date DESC, ir.id DESC
                LIMIT $limit";
            $result = $conn->query($sql);
        }
        if (!$result || $result->num_rows <= 0) {
            return 0;
        }

        $inserted = 0;
        while ($ir = $result->fetch_assoc()) {
            $fields = gw_build_shortages_log_fields_from_indend_raw($ir);
            $logId = gw_insert_mrp_shortages_indent_log_direct($conn, $fields, [
                'indent_id' => (int)($ir['id'] ?? 0),
                'indent_no' => $ir['no'] ?? '',
                'request_no' => $ir['request_no'] ?? '',
                'emp_id' => $ir['entry_by'] ?? '',
                'department' => $ir['department'] ?? '',
                'plant_id' => $ir['plant_id'] ?? '',
                'event_datetime' => $ir['entry_date'] ?? '',
            ]);
            if ($logId > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }
}

if (!function_exists('gw_backfill_mrp_shortages_indent_log_from_wo')) {
    /** Backfill log rows from WO_deductions when indend_raw link exists but log row is missing. */
    function gw_backfill_mrp_shortages_indent_log_from_wo($conn, $limit = 200) {
        gw_ensure_mrp_shortages_indent_log_table($conn);
        $limit = max(1, min(500, (int)$limit));

        $sql = "SELECT wd.id AS wo_deduction_id, wd.workorder_no, wd.material_code, wd.shortage,
                wd.indent_id, wd.indent_no, wd.indent_raised_by, wd.indent_raised_on,
                ir.*
            FROM WO_deductions wd
            INNER JOIN indend_raw ir ON CAST(ir.id AS CHAR) = CAST(wd.indent_id AS CHAR)
            LEFT JOIN mrp_shortages_indent_log l ON l.indent_id = ir.id
            WHERE wd.indent_status IN ('Raised', 'Indent Sent')
              AND wd.indent_id IS NOT NULL
              AND CAST(wd.indent_id AS UNSIGNED) > 0
              AND l.id IS NULL
            ORDER BY COALESCE(wd.indent_raised_on, ir.entry_date) DESC, wd.id DESC
            LIMIT $limit";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows <= 0) {
            return 0;
        }

        $inserted = 0;
        while ($ir = $result->fetch_assoc()) {
            $fields = gw_build_shortages_log_fields_from_indend_raw($ir, [
                'workorder_no' => $ir['workorder_no'] ?? '',
                'indent_no' => $ir['no'] ?? ($ir['indent_no'] ?? ''),
                'event_datetime' => $ir['indent_raised_on'] ?? ($ir['entry_date'] ?? ''),
                'raised_by' => $ir['entry_by'] ?? ($ir['indent_raised_by'] ?? ''),
                'rm_shortage' => (float)($ir['shortage'] ?? 0),
            ]);
            $logId = gw_insert_mrp_shortages_indent_log_direct($conn, $fields, [
                'indent_id' => (int)($ir['id'] ?? 0),
                'indent_no' => $ir['no'] ?? ($ir['indent_no'] ?? ''),
                'request_no' => $ir['request_no'] ?? '',
                'emp_id' => $ir['entry_by'] ?? ($ir['indent_raised_by'] ?? ''),
                'department' => $ir['department'] ?? '',
                'plant_id' => $ir['plant_id'] ?? '',
            ]);
            if ($logId > 0) {
                $inserted++;
            }
        }

        return $inserted;
    }
}

if (!function_exists('gw_backfill_mrp_shortages_indent_log_from_wo_direct')) {
    function gw_backfill_mrp_shortages_indent_log_from_wo_direct($conn, $limit = 200) {
        if (!gw_table_exists($conn, 'WO_deductions')) {
            return 0;
        }
        gw_ensure_mrp_shortages_indent_log_table($conn);
        $limit = max(1, min(500, (int)$limit));
        $sql = "SELECT wd.workorder_no, wd.material_code, wd.shortage, wd.indent_id, wd.indent_no,
                wd.indent_raised_by, wd.indent_raised_on
            FROM WO_deductions wd
            LEFT JOIN mrp_shortages_indent_log l ON (
                (wd.indent_id > 0 AND l.indent_id = wd.indent_id)
                OR (l.workorder_no = wd.workorder_no AND l.material_code = wd.material_code AND l.indent_no = wd.indent_no)
            )
            WHERE wd.indent_status IN ('Raised', 'Indent Sent')
              AND wd.indent_id IS NOT NULL
              AND CAST(wd.indent_id AS UNSIGNED) > 0
              AND l.id IS NULL
            ORDER BY wd.indent_raised_on DESC, wd.id DESC
            LIMIT $limit";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows <= 0) {
            return 0;
        }
        $inserted = 0;
        while ($wd = $result->fetch_assoc()) {
            $indentId = (int)($wd['indent_id'] ?? 0);
            $ir = null;
            if ($indentId > 0) {
                $irRes = $conn->query("SELECT * FROM indend_raw WHERE id = $indentId LIMIT 1");
                if ($irRes && $irRes->num_rows > 0) {
                    $ir = $irRes->fetch_assoc();
                }
            }
            if (is_array($ir)) {
                $fields = gw_build_shortages_log_fields_from_indend_raw($ir, [
                    'workorder_no' => $wd['workorder_no'] ?? '',
                    'indent_no' => $wd['indent_no'] ?? '',
                    'event_datetime' => $wd['indent_raised_on'] ?? '',
                    'raised_by' => $wd['indent_raised_by'] ?? '',
                ]);
            } else {
                $fields = [
                    'indent_id' => $indentId,
                    'indent_no' => $wd['indent_no'] ?? '',
                    'workorder_no' => $wd['workorder_no'] ?? '',
                    'material_code' => $wd['material_code'] ?? '',
                    'raised_indent_qty' => (float)($wd['shortage'] ?? 0),
                    'rm_shortage' => (float)($wd['shortage'] ?? 0),
                    'consolidated_shortage_qty' => (float)($wd['shortage'] ?? 0),
                    'event_datetime' => $wd['indent_raised_on'] ?? '',
                    'raised_by' => $wd['indent_raised_by'] ?? '',
                    'remark' => 'Backfilled from WO_deductions',
                    'backfill' => 1,
                ];
            }
            $logId = gw_insert_mrp_shortages_indent_log_direct($conn, $fields, [
                'indent_id' => $indentId,
                'indent_no' => $wd['indent_no'] ?? '',
            ]);
            if ($logId > 0) {
                $inserted++;
            }
        }
        return $inserted;
    }
}

if (!function_exists('gw_backfill_mrp_shortages_indent_log')) {
    /**
     * Rebuild missing log rows from all previous Planning MRP / Shortages indents.
     */
    function gw_backfill_mrp_shortages_indent_log($conn, $batchSize = 200, $maxTotal = 3000) {
        $batchSize = max(1, min(500, (int)$batchSize));
        $maxTotal = max($batchSize, min(5000, (int)$maxTotal));
        $totalInserted = 0;
        $maxRounds = min(20, (int)ceil($maxTotal / $batchSize));

        for ($round = 0; $round < $maxRounds; $round++) {
            $inserted = gw_backfill_mrp_shortages_indent_log_batch($conn, $batchSize);
            $inserted += gw_backfill_mrp_shortages_indent_log_from_wo($conn, $batchSize);
            $inserted += gw_backfill_mrp_shortages_indent_log_from_wo_direct($conn, $batchSize);
            $totalInserted += $inserted;
            if ($inserted <= 0) {
                break;
            }
            if ($totalInserted >= $maxTotal) {
                break;
            }
        }

        return $totalInserted;
    }
}

if (!function_exists('gw_save_mrp_shortages_indent_log')) {
    function gw_save_mrp_shortages_indent_log($conn, $input, $meta = []) {
        if (!gw_should_save_mrp_shortages_indent_log($input)) {
            return false;
        }

        gw_ensure_mrp_shortages_indent_log_table($conn);

        // Add columns for older installs (ignore duplicate column errors).
        @$conn->query("ALTER TABLE mrp_shortages_indent_log ADD COLUMN rm_balance DECIMAL(18,4) DEFAULT NULL AFTER rm_shortage");
        @$conn->query("ALTER TABLE mrp_shortages_indent_log ADD COLUMN consolidated_shortage_qty DECIMAL(18,4) DEFAULT NULL AFTER rm_balance");

        date_default_timezone_set('Asia/Kolkata');
        $eventDatetime = !empty($meta['event_datetime'])
            ? mysqli_real_escape_string($conn, $meta['event_datetime'])
            : date('Y-m-d H:i:s');
        $raisedBy = mysqli_real_escape_string($conn, $meta['emp_id'] ?? ($_GET['emp_id'] ?? ''));
        $department = mysqli_real_escape_string($conn, $meta['department'] ?? ($_GET['department'] ?? ''));
        $plantId = mysqli_real_escape_string($conn, $meta['plant_id'] ?? ($_GET['plant_id'] ?? ''));
        $raisedByName = mysqli_real_escape_string($conn, $meta['raised_by_name'] ?? ($input['shortages_log']['raised_by_name'] ?? ''));
        if ($raisedByName === '' && $raisedBy !== '') {
            $empRes = $conn->query("SELECT COALESCE(emp_name, firstname, '') AS n FROM employee WHERE emp_id = '$raisedBy' LIMIT 1");
            if ($empRes && $empRes->num_rows > 0) {
                $raisedByName = mysqli_real_escape_string($conn, $empRes->fetch_assoc()['n'] ?? '');
            }
        }
        $indentNo = mysqli_real_escape_string($conn, $meta['indent_no'] ?? '');
        $indentId = (int)($meta['indent_id'] ?? 0);
        $requestNo = mysqli_real_escape_string($conn, $meta['request_no'] ?? '');

        $baseLog = is_array($input['shortages_log'] ?? null) ? $input['shortages_log'] : [];
        $bulkRaise = !empty($baseLog['bulk_raise']) ? 1 : 0;
        $planMonth = mysqli_real_escape_string($conn, $baseLog['plan_month'] ?? '');

        $rows = [];
        if (!empty($input['required_for']) && is_array($input['required_for'])) {
            $rows = $input['required_for'];
        } else {
            $rows = [[]];
        }

        $savedAny = false;
        foreach ($rows as $req) {
            if (!is_array($req)) {
                continue;
            }

            $materialCode = mysqli_real_escape_string($conn, $req['material_code'] ?? ($input['material_code'] ?? ''));
            $materialName = mysqli_real_escape_string($conn, $req['material_name'] ?? ($input['material_name'] ?? ''));
            $forecastNo = mysqli_real_escape_string($conn, $req['order_no'] ?? ($baseLog['forecast_no'] ?? ''));
            $orderNo = mysqli_real_escape_string($conn, $req['order_no'] ?? ($baseLog['order_no'] ?? ''));
            $workorderNo = mysqli_real_escape_string($conn, $req['work_order_no'] ?? ($req['workorder_no'] ?? ''));
            $productCode = mysqli_real_escape_string($conn, $req['product_code'] ?? ($baseLog['product_code'] ?? ''));
            $productName = mysqli_real_escape_string($conn, $req['product_name'] ?? ($baseLog['product_name'] ?? ''));
            $clientCode = mysqli_real_escape_string($conn, $req['client_code'] ?? ($input['client_code'] ?? ''));
            $clientName = mysqli_real_escape_string($conn, $req['client_name'] ?? ($input['client_name'] ?? ''));
            $requiredQty = (float)($req['required_qty'] ?? ($baseLog['required_qty'] ?? 0));
            $raisedQty = (float)($req['reqQty'] ?? ($baseLog['raised_indent_qty'] ?? 0));
            $qtyUnit = mysqli_real_escape_string($conn, $req['Matunit'] ?? ($baseLog['qty_unit'] ?? ''));
            $qtySource = mysqli_real_escape_string($conn, $req['indent_qty_source'] ?? ($baseLog['qty_source'] ?? 'shortage'));
            $rmShortage = (float)($req['rm_shortage'] ?? ($baseLog['consolidated_shortage_qty'] ?? 0));
            $rmBalance = (float)($baseLog['rm_balance'] ?? 0);
            $consolidatedShortage = (float)($baseLog['consolidated_shortage_qty'] ?? $rmShortage);
            if ($raisedQty <= 0 && !empty($baseLog['raised_indent_qty'])) {
                $raisedQty = (float)$baseLog['raised_indent_qty'];
            }
            if ($raisedQty <= 0 && !empty($req['Client_code_Indent'])) {
                $raisedQty = (float)$req['Client_code_Indent'];
            }
            if ($raisedQty <= 0 && !empty($req['Mother_code_Indent'])) {
                $raisedQty = (float)$req['Mother_code_Indent'];
            }
            $baseShortage = (float)($req['base_shortage'] ?? ($baseLog['consolidated_shortage_qty'] ?? 0));
            $bookedQty = (float)($req['booked_qty_for_wo_product'] ?? 0);
            $remark = mysqli_real_escape_string($conn, $req['indent_remark'] ?? ($input['remark'] ?? ($baseLog['remark'] ?? '')));

            $detail = json_encode([
                'input' => [
                    'category' => $input['category'] ?? '',
                    'delivery_deadline_date' => $req['delivery_deadline_date'] ?? ($input['delivery_deadline_date'] ?? ''),
                    'indent_raise_deadline_date' => $req['indent_raise_deadline_date'] ?? '',
                    'indent_approval_deadline_date' => $req['indent_approval_deadline_date'] ?? '',
                    'moq' => $req['moq'] ?? '',
                    'deducted_from_RM' => $req['deducted_from_RM'] ?? 0,
                    'deducted_from_MC' => $req['deducted_from_MC'] ?? 0,
                ],
                'response' => [
                    'indent_no' => $meta['indent_no'] ?? '',
                    'indent_id' => $meta['indent_id'] ?? '',
                    'request_no' => $meta['request_no'] ?? '',
                ],
            ], JSON_UNESCAPED_UNICODE);

            $detailEsc = mysqli_real_escape_string($conn, $detail);

            $sql = "INSERT INTO mrp_shortages_indent_log (
                event_type, event_status, source_screen, raised_by, raised_by_name, department, plant_id,
                event_datetime, plan_month, material_code, material_name, forecast_no, order_no, workorder_no,
                product_code, product_name, client_code, client_name, required_qty, raised_indent_qty, qty_unit,
                qty_source, rm_shortage, rm_balance, consolidated_shortage_qty, base_shortage, booked_qty_for_wo, indent_no, indent_id, request_no,
                bulk_raise, remark, event_detail
            ) VALUES (
                'RAISE_INDENT', 'success', 'Shortages', '$raisedBy', '$raisedByName', '$department', '$plantId',
                '$eventDatetime', '$planMonth', '$materialCode', '$materialName', '$forecastNo', '$orderNo', '$workorderNo',
                '$productCode', '$productName', '$clientCode', '$clientName', '$requiredQty', '$raisedQty', '$qtyUnit',
                '$qtySource', '$rmShortage', '$rmBalance', '$consolidatedShortage', '$baseShortage', '$bookedQty', '$indentNo', '$indentId', '$requestNo',
                '$bulkRaise', '$remark', '$detailEsc'
            )";
            if ($conn->query($sql)) {
                $savedAny = true;
                $logId = (int)$conn->insert_id;
                if ($logId > 0 && function_exists('gw_sync_mrp_indent_confirmation_from_shortages_log')) {
                    gw_sync_mrp_indent_confirmation_from_shortages_log($conn, $logId, $meta);
                }
            } else {
                error_log('gw_save_mrp_shortages_indent_log failed: ' . $conn->error . ' | SQL: ' . $sql);
            }
        }

        return $savedAny;
    }
}

if (!function_exists('gw_resolve_shortages_indent_raise_refs')) {
    /**
     * Ensure shortages log + confirmation rows exist after raise; return their ids.
     */
    function gw_resolve_shortages_indent_raise_refs($conn, $indentId, $indentMeta = []) {
        $indentId = (int)$indentId;
        $shortagesLogId = 0;
        $confirmationId = 0;
        if ($indentId <= 0) {
            return [
                'shortages_log_id' => 0,
                'confirmation_id' => 0,
            ];
        }

        if (function_exists('gw_ensure_mrp_shortages_indent_log_table')) {
            gw_ensure_mrp_shortages_indent_log_table($conn);
            $logRes = $conn->query("SELECT id FROM mrp_shortages_indent_log WHERE indent_id = $indentId ORDER BY id DESC LIMIT 1");
            if ($logRes && $logRes->num_rows > 0) {
                $shortagesLogId = (int)$logRes->fetch_assoc()['id'];
            }
        }

        if ($shortagesLogId > 0 && function_exists('gw_sync_mrp_indent_confirmation_from_shortages_log')) {
            $confirmationId = (int)gw_sync_mrp_indent_confirmation_from_shortages_log($conn, $shortagesLogId, $indentMeta);
        }

        if ($confirmationId <= 0 && function_exists('gw_sync_mrp_indent_confirmation_from_indend_raw')) {
            $irRes = $conn->query("SELECT * FROM indend_raw WHERE id = $indentId LIMIT 1");
            if ($irRes && $irRes->num_rows > 0) {
                $confirmationId = (int)gw_sync_mrp_indent_confirmation_from_indend_raw($conn, $irRes->fetch_assoc());
            }
        }

        if ($confirmationId <= 0 && function_exists('gw_ensure_mrp_indents_confirmation_tables')) {
            gw_ensure_mrp_indents_confirmation_tables($conn);
            $cRes = $conn->query("SELECT id FROM mrp_indents_confirmation WHERE indent_id = $indentId ORDER BY id DESC LIMIT 1");
            if ($cRes && $cRes->num_rows > 0) {
                $confirmationId = (int)$cRes->fetch_assoc()['id'];
            }
        }

        return [
            'shortages_log_id' => $shortagesLogId,
            'confirmation_id' => $confirmationId,
        ];
    }
}

if (!function_exists('gw_get_mrp_shortages_indent_log')) {
    function gw_get_mrp_shortages_indent_log($conn, $limit = 200, $backfill = true) {
        gw_ensure_mrp_shortages_indent_log_table($conn);
        if ($backfill) {
            @gw_backfill_mrp_shortages_indent_log($conn, 50, 200);
        }
        $limit = max(1, min(2000, (int)$limit));
        $output = [];
        $sql = "SELECT * FROM mrp_shortages_indent_log ORDER BY event_datetime DESC, id DESC LIMIT $limit";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }
}
