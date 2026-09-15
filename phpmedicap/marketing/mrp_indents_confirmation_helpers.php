<?php

if (!function_exists('gw_ensure_mrp_indents_confirmation_tables')) {
    function gw_ensure_mrp_indents_confirmation_tables($conn) {
        $conn->query("CREATE TABLE IF NOT EXISTS mrp_indents_confirmation (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shortages_log_id INT DEFAULT NULL,
            indent_id INT DEFAULT NULL,
            indent_no VARCHAR(80) DEFAULT NULL,
            request_no VARCHAR(80) DEFAULT NULL,
            confirmation_status VARCHAR(40) NOT NULL DEFAULT 'PENDING',
            order_no VARCHAR(100) DEFAULT NULL,
            forecast_no VARCHAR(100) DEFAULT NULL,
            workorder_no VARCHAR(100) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            material_type VARCHAR(120) DEFAULT NULL,
            material_code VARCHAR(80) DEFAULT NULL,
            material_name VARCHAR(255) DEFAULT NULL,
            plan_month VARCHAR(50) DEFAULT NULL,
            required_qty DECIMAL(18,4) DEFAULT NULL,
            shortage_qty DECIMAL(18,4) DEFAULT NULL,
            raised_indent_qty DECIMAL(18,4) DEFAULT NULL,
            cancelled_qty DECIMAL(18,4) DEFAULT 0,
            qty_unit VARCHAR(30) DEFAULT NULL,
            indent_raised_date DATETIME DEFAULT NULL,
            indent_raised_by VARCHAR(50) DEFAULT NULL,
            indent_raised_by_name VARCHAR(120) DEFAULT NULL,
            remark TEXT,
            updated_by VARCHAR(50) DEFAULT NULL,
            updated_by_name VARCHAR(120) DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_confirmation_status (confirmation_status),
            INDEX idx_indent_id (indent_id),
            INDEX idx_material_code (material_code),
            INDEX idx_workorder_no (workorder_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS mrp_indents_confirmation_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            confirmation_id INT DEFAULT NULL,
            shortages_log_id INT DEFAULT NULL,
            indent_id INT DEFAULT NULL,
            indent_no VARCHAR(80) DEFAULT NULL,
            action_type VARCHAR(50) NOT NULL,
            action_status VARCHAR(40) NOT NULL,
            material_code VARCHAR(80) DEFAULT NULL,
            material_name VARCHAR(255) DEFAULT NULL,
            workorder_no VARCHAR(100) DEFAULT NULL,
            order_no VARCHAR(100) DEFAULT NULL,
            qty DECIMAL(18,4) DEFAULT NULL,
            qty_unit VARCHAR(30) DEFAULT NULL,
            action_by VARCHAR(50) DEFAULT NULL,
            action_by_name VARCHAR(120) DEFAULT NULL,
            department VARCHAR(120) DEFAULT NULL,
            remark TEXT,
            event_detail LONGTEXT,
            event_datetime DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_datetime (event_datetime),
            INDEX idx_action_status (action_status),
            INDEX idx_material_code (material_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Indent Lock columns (Phase 4) — additive; check before ALTER (mysqli throws on duplicate).
        $lockCols = [
            'is_locked' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER confirmation_status",
            'locked_by' => "VARCHAR(50) DEFAULT NULL AFTER is_locked",
            'locked_by_name' => "VARCHAR(120) DEFAULT NULL AFTER locked_by",
            'locked_at' => "DATETIME DEFAULT NULL AFTER locked_by_name",
            'lock_remark' => "TEXT AFTER locked_at",
            'lock_revision_no' => "INT NOT NULL DEFAULT 0 AFTER lock_remark",
        ];
        foreach ($lockCols as $colName => $colDef) {
            $col = @$conn->query("SHOW COLUMNS FROM mrp_indents_confirmation LIKE '" . $conn->real_escape_string($colName) . "'");
            if (!$col || $col->num_rows === 0) {
                try {
                    $conn->query("ALTER TABLE mrp_indents_confirmation ADD COLUMN `$colName` $colDef");
                } catch (Throwable $e) {
                    // ignore race / already exists
                }
            }
        }
        $idx = @$conn->query("SHOW INDEX FROM mrp_indents_confirmation WHERE Key_name = 'idx_mic_is_locked'");
        if (!$idx || $idx->num_rows === 0) {
            try {
                $conn->query("ALTER TABLE mrp_indents_confirmation ADD INDEX idx_mic_is_locked (is_locked)");
            } catch (Throwable $e) {
                // ignore
            }
        }
    }
}

if (!function_exists('gw_sync_mrp_indent_confirmation_from_shortages_log')) {
    function gw_sync_mrp_indent_confirmation_from_shortages_log($conn, $shortagesLogId, $indentMeta = []) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $shortagesLogId = (int)$shortagesLogId;
        if ($shortagesLogId <= 0) {
            return 0;
        }

        $chk = $conn->query("SELECT id FROM mrp_indents_confirmation WHERE shortages_log_id = $shortagesLogId LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            return (int)$chk->fetch_assoc()['id'];
        }

        $logRes = $conn->query("SELECT * FROM mrp_shortages_indent_log WHERE id = $shortagesLogId LIMIT 1");
        if (!$logRes || $logRes->num_rows === 0) {
            return 0;
        }
        $log = $logRes->fetch_assoc();

        $indentId = (int)($indentMeta['indent_id'] ?? $log['indent_id'] ?? 0);
        if ($indentId > 0) {
            $byIndent = $conn->query("SELECT id FROM mrp_indents_confirmation WHERE indent_id = $indentId LIMIT 1");
            if ($byIndent && $byIndent->num_rows > 0) {
                return (int)$byIndent->fetch_assoc()['id'];
            }
        }
        $indentNo = mysqli_real_escape_string($conn, $indentMeta['indent_no'] ?? $log['indent_no'] ?? '');
        $requestNo = mysqli_real_escape_string($conn, $indentMeta['request_no'] ?? $log['request_no'] ?? '');

        $materialType = mysqli_real_escape_string($conn, $log['material_type'] ?? '');
        if ($materialType === '' && !empty($log['material_code'])) {
            $mc = mysqli_real_escape_string($conn, $log['material_code']);
            $mtRes = $conn->query("SELECT COALESCE(
                (SELECT material_type FROM material WHERE material_code='$mc' LIMIT 1),
                (SELECT material_type FROM others_material WHERE material_code='$mc' LIMIT 1),
                (SELECT material_type FROM bulkMaster WHERE bulkCode='$mc' LIMIT 1),
                ''
            ) AS material_type");
            if ($mtRes && $mtRes->num_rows > 0) {
                $materialType = mysqli_real_escape_string($conn, $mtRes->fetch_assoc()['material_type'] ?? '');
            }
        }

        $fields = [
            'shortages_log_id' => $shortagesLogId,
            'indent_id' => $indentId,
            'indent_no' => $indentNo,
            'request_no' => $requestNo,
            'confirmation_status' => 'PENDING',
            'order_no' => mysqli_real_escape_string($conn, $log['order_no'] ?? ''),
            'forecast_no' => mysqli_real_escape_string($conn, $log['forecast_no'] ?? $log['order_no'] ?? ''),
            'workorder_no' => mysqli_real_escape_string($conn, $log['workorder_no'] ?? ''),
            'product_code' => mysqli_real_escape_string($conn, $log['product_code'] ?? ''),
            'product_name' => mysqli_real_escape_string($conn, $log['product_name'] ?? ''),
            'material_type' => $materialType,
            'material_code' => mysqli_real_escape_string($conn, $log['material_code'] ?? ''),
            'material_name' => mysqli_real_escape_string($conn, $log['material_name'] ?? ''),
            'plan_month' => mysqli_real_escape_string($conn, $log['plan_month'] ?? ''),
            'required_qty' => (float)($log['required_qty'] ?? 0),
            'shortage_qty' => (float)($log['consolidated_shortage_qty'] ?? $log['rm_shortage'] ?? 0),
            'raised_indent_qty' => (float)($log['raised_indent_qty'] ?? 0),
            'cancelled_qty' => 0,
            'qty_unit' => mysqli_real_escape_string($conn, $log['qty_unit'] ?? ''),
            'indent_raised_date' => mysqli_real_escape_string($conn, $log['event_datetime'] ?? date('Y-m-d H:i:s')),
            'indent_raised_by' => mysqli_real_escape_string($conn, $log['raised_by'] ?? ''),
            'indent_raised_by_name' => mysqli_real_escape_string($conn, $log['raised_by_name'] ?? ''),
            'remark' => mysqli_real_escape_string($conn, $log['remark'] ?? ''),
        ];

        $sql = "INSERT INTO mrp_indents_confirmation (
            shortages_log_id, indent_id, indent_no, request_no, confirmation_status,
            order_no, forecast_no, workorder_no, product_code, product_name, material_type,
            material_code, material_name, plan_month, required_qty, shortage_qty,
            raised_indent_qty, cancelled_qty, qty_unit, indent_raised_date,
            indent_raised_by, indent_raised_by_name, remark
        ) VALUES (
            {$fields['shortages_log_id']}, {$fields['indent_id']}, '{$fields['indent_no']}', '{$fields['request_no']}', '{$fields['confirmation_status']}',
            '{$fields['order_no']}', '{$fields['forecast_no']}', '{$fields['workorder_no']}', '{$fields['product_code']}', '{$fields['product_name']}', '{$fields['material_type']}',
            '{$fields['material_code']}', '{$fields['material_name']}', '{$fields['plan_month']}', {$fields['required_qty']}, {$fields['shortage_qty']},
            {$fields['raised_indent_qty']}, {$fields['cancelled_qty']}, '{$fields['qty_unit']}', '{$fields['indent_raised_date']}',
            '{$fields['indent_raised_by']}', '{$fields['indent_raised_by_name']}', '{$fields['remark']}'
        )";
        if (!$conn->query($sql)) {
            error_log('gw_sync_mrp_indent_confirmation_from_shortages_log failed: ' . $conn->error . ' | SQL: ' . $sql);
            return 0;
        }
        $confirmationId = (int)$conn->insert_id;

        gw_write_mrp_indent_confirmation_log($conn, [
            'confirmation_id' => $confirmationId,
            'shortages_log_id' => $shortagesLogId,
            'indent_id' => $indentId,
            'indent_no' => $fields['indent_no'],
            'action_type' => 'RAISE_INDENT',
            'action_status' => 'PENDING',
            'material_code' => $log['material_code'] ?? '',
            'material_name' => $log['material_name'] ?? '',
            'workorder_no' => $log['workorder_no'] ?? '',
            'order_no' => $log['order_no'] ?? '',
            'qty' => $fields['raised_indent_qty'],
            'qty_unit' => $log['qty_unit'] ?? '',
            'remark' => 'Indent raised from Shortages',
        ], $indentMeta);

        return $confirmationId;
    }
}

if (!function_exists('gw_sync_mrp_indent_confirmation_from_indend_raw')) {
    /**
     * Create confirmation row from indend_raw when shortages log sync was missed.
     */
    function gw_sync_mrp_indent_confirmation_from_indend_raw($conn, $indentRow) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        if (!is_array($indentRow)) {
            return 0;
        }

        $indentId = (int)($indentRow['id'] ?? 0);
        if ($indentId <= 0) {
            return 0;
        }

        $existing = $conn->query("SELECT id FROM mrp_indents_confirmation WHERE indent_id = $indentId LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            return (int)$existing->fetch_assoc()['id'];
        }

        if (function_exists('gw_ensure_mrp_shortages_indent_log_table')) {
            gw_ensure_mrp_shortages_indent_log_table($conn);
            $logRes = $conn->query("SELECT id, indent_no, request_no FROM mrp_shortages_indent_log
                WHERE indent_id = $indentId
                ORDER BY id DESC LIMIT 1");
            if ($logRes && $logRes->num_rows > 0) {
                $logRow = $logRes->fetch_assoc();
                return gw_sync_mrp_indent_confirmation_from_shortages_log($conn, (int)$logRow['id'], [
                    'indent_id' => $indentId,
                    'indent_no' => $logRow['indent_no'] ?? ($indentRow['no'] ?? ''),
                    'request_no' => $logRow['request_no'] ?? ($indentRow['request_no'] ?? ''),
                    'emp_id' => $indentRow['entry_by'] ?? '',
                    'department' => $indentRow['department'] ?? '',
                ]);
            }
        }

        $requiredFor = json_decode($indentRow['required_for'] ?? '[]', true);
        if (!is_array($requiredFor)) {
            $requiredFor = [];
        }
        $req = !empty($requiredFor) && is_array($requiredFor[0]) ? $requiredFor[0] : [];

        $materialCode = mysqli_real_escape_string($conn, $req['material_code'] ?? ($indentRow['material_code'] ?? ''));
        $materialName = mysqli_real_escape_string($conn, $req['material_name'] ?? ($indentRow['material_name'] ?? ''));
        $workorderNo = mysqli_real_escape_string($conn, $req['work_order_no'] ?? ($req['workorder_no'] ?? ''));
        $orderNo = mysqli_real_escape_string($conn, $req['order_no'] ?? '');
        $productCode = mysqli_real_escape_string($conn, $req['product_code'] ?? '');
        $productName = mysqli_real_escape_string($conn, $req['product_name'] ?? '');
        $raisedQty = (float)($req['reqQty'] ?? ($req['Client_code_Indent'] ?? ($req['Mother_code_Indent'] ?? ($indentRow['req_qty'] ?? 0))));
        $shortageQty = (float)($req['rm_shortage'] ?? ($req['base_shortage'] ?? $raisedQty));
        $requiredQty = (float)($req['required_qty'] ?? $raisedQty);
        $qtyUnit = mysqli_real_escape_string($conn, $req['Matunit'] ?? ($indentRow['unit'] ?? ''));
        $indentNo = mysqli_real_escape_string($conn, $indentRow['no'] ?? '');
        $requestNo = mysqli_real_escape_string($conn, $indentRow['request_no'] ?? '');
        $raisedBy = mysqli_real_escape_string($conn, $indentRow['entry_by'] ?? '');
        $raisedByName = '';
        if ($raisedBy !== '') {
            $empRes = $conn->query("SELECT COALESCE(emp_name, firstname, '') AS n FROM employee WHERE emp_id = '$raisedBy' LIMIT 1");
            if ($empRes && $empRes->num_rows > 0) {
                $raisedByName = mysqli_real_escape_string($conn, $empRes->fetch_assoc()['n'] ?? '');
            }
        }
        $department = mysqli_real_escape_string($conn, $indentRow['department'] ?? '');
        $remark = mysqli_real_escape_string($conn, $indentRow['planning_reamrk'] ?? 'Backfilled from previous indent');
        $raisedDate = mysqli_real_escape_string($conn, $indentRow['entry_date'] ?? date('Y-m-d H:i:s'));

        $materialType = '';
        if ($materialCode !== '') {
            $mc = $materialCode;
            $mtRes = $conn->query("SELECT COALESCE(
                (SELECT material_type FROM material WHERE material_code='$mc' LIMIT 1),
                (SELECT material_type FROM others_material WHERE material_code='$mc' LIMIT 1),
                (SELECT material_type FROM bulkMaster WHERE bulkCode='$mc' LIMIT 1),
                ''
            ) AS material_type");
            if ($mtRes && $mtRes->num_rows > 0) {
                $materialType = mysqli_real_escape_string($conn, $mtRes->fetch_assoc()['material_type'] ?? '');
            }
        }

        $sql = "INSERT INTO mrp_indents_confirmation (
            shortages_log_id, indent_id, indent_no, request_no, confirmation_status,
            order_no, forecast_no, workorder_no, product_code, product_name, material_type,
            material_code, material_name, plan_month, required_qty, shortage_qty,
            raised_indent_qty, cancelled_qty, qty_unit, indent_raised_date,
            indent_raised_by, indent_raised_by_name, remark
        ) VALUES (
            NULL, $indentId, '$indentNo', '$requestNo', 'PENDING',
            '$orderNo', '$orderNo', '$workorderNo', '$productCode', '$productName', '$materialType',
            '$materialCode', '$materialName', '', $requiredQty, $shortageQty,
            $raisedQty, 0, '$qtyUnit', '$raisedDate',
            '$raisedBy', '$raisedByName', '$remark'
        )";
        if (!$conn->query($sql)) {
            error_log('gw_sync_mrp_indent_confirmation_from_indend_raw failed: ' . $conn->error . ' | SQL: ' . $sql);
            return 0;
        }

        $confirmationId = (int)$conn->insert_id;
        gw_write_mrp_indent_confirmation_log($conn, [
            'confirmation_id' => $confirmationId,
            'shortages_log_id' => 0,
            'indent_id' => $indentId,
            'indent_no' => $indentNo,
            'action_type' => 'RAISE_INDENT',
            'action_status' => 'PENDING',
            'material_code' => $materialCode,
            'material_name' => $materialName,
            'workorder_no' => $workorderNo,
            'order_no' => $orderNo,
            'qty' => $raisedQty,
            'qty_unit' => $qtyUnit,
            'remark' => 'Backfilled from Planning MRP indent',
        ], [
            'emp_id' => $raisedBy,
            'raised_by_name' => $raisedByName,
            'department' => $department,
        ]);

        return $confirmationId;
    }
}

if (!function_exists('gw_backfill_mrp_indents_confirmation')) {
    function gw_backfill_mrp_indents_confirmation($conn, $batchSize = 200, $maxTotal = 5000, $runLogBackfill = true) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $batchSize = max(1, min(500, (int)$batchSize));
        $maxTotal = max($batchSize, min(5000, (int)$maxTotal));
        $totalSynced = 0;
        $maxRounds = min(20, (int)ceil($maxTotal / $batchSize));
        $filter = function_exists('gw_planning_indent_indend_raw_filter_sql')
            ? gw_planning_indent_indend_raw_filter_sql($conn, 'ir')
            : "ir.requirement = 'Planning MRP'";

        if ($runLogBackfill && function_exists('gw_backfill_mrp_shortages_indent_log')) {
            gw_backfill_mrp_shortages_indent_log($conn, $batchSize, $maxTotal);
        }

        for ($round = 0; $round < $maxRounds; $round++) {
            $synced = 0;

            if (function_exists('gw_ensure_mrp_shortages_indent_log_table')) {
                gw_ensure_mrp_shortages_indent_log_table($conn);
                $logSql = "SELECT l.id, l.indent_id, l.indent_no, l.request_no
                    FROM mrp_shortages_indent_log l
                    LEFT JOIN mrp_indents_confirmation c ON c.shortages_log_id = l.id
                    WHERE l.source_screen = 'Shortages'
                      AND l.event_type = 'RAISE_INDENT'
                      AND c.id IS NULL
                    ORDER BY l.event_datetime DESC, l.id DESC
                    LIMIT $batchSize";
                $logResult = $conn->query($logSql);
                if ($logResult) {
                    while ($bf = $logResult->fetch_assoc()) {
                        $id = gw_sync_mrp_indent_confirmation_from_shortages_log($conn, (int)$bf['id'], [
                            'indent_id' => (int)($bf['indent_id'] ?? 0),
                            'indent_no' => $bf['indent_no'] ?? '',
                            'request_no' => $bf['request_no'] ?? '',
                        ]);
                        if ($id > 0) {
                            $synced++;
                        }
                    }
                }
            }

            $irSql = "SELECT ir.*
                FROM indend_raw ir
                LEFT JOIN mrp_indents_confirmation c ON CAST(c.indent_id AS CHAR) = CAST(ir.id AS CHAR)
                WHERE c.id IS NULL
                  AND $filter
                ORDER BY ir.entry_date DESC, ir.id DESC
                LIMIT $batchSize";
            $irResult = $conn->query($irSql);
            if ($irResult) {
                while ($ir = $irResult->fetch_assoc()) {
                    $id = gw_sync_mrp_indent_confirmation_from_indend_raw($conn, $ir);
                    if ($id > 0) {
                        $synced++;
                    }
                }
            }

            $totalSynced += $synced;
            if ($synced <= 0) {
                break;
            }
            if ($totalSynced >= $maxTotal) {
                break;
            }
        }

        return $totalSynced;
    }
}

if (!function_exists('gw_get_mrp_indents_confirmation_pending_summary')) {
    function gw_get_mrp_indents_confirmation_pending_summary($conn) {
        gw_backfill_mrp_indents_confirmation($conn, 50, 200, true);
        $pendingCount = 0;
        $latestId = 0;
        $sql = "SELECT COUNT(*) AS pending_count, COALESCE(MAX(id), 0) AS latest_id
            FROM mrp_indents_confirmation
            WHERE confirmation_status = 'PENDING'";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $pendingCount = (int)($row['pending_count'] ?? 0);
            $latestId = (int)($row['latest_id'] ?? 0);
        }
        $text = $pendingCount > 0
            ? "You have $pendingCount new indent(s) for proceeding in Indents Confirmation."
            : '';
        return [
            'pending_count' => $pendingCount,
            'latest_id' => $latestId,
            'text' => $text,
        ];
    }
}

if (!function_exists('gw_write_mrp_indent_confirmation_log')) {
    function gw_write_mrp_indent_confirmation_log($conn, $data, $meta = []) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        date_default_timezone_set('Asia/Kolkata');
        $eventDatetime = date('Y-m-d H:i:s');
        $actionBy = mysqli_real_escape_string($conn, $meta['emp_id'] ?? ($_GET['emp_id'] ?? ''));
        $actionByName = mysqli_real_escape_string($conn, $meta['raised_by_name'] ?? ($data['action_by_name'] ?? ''));
        $department = mysqli_real_escape_string($conn, $meta['department'] ?? ($_GET['department'] ?? ''));

        $detail = json_encode($data, JSON_UNESCAPED_UNICODE);
        $detailEsc = mysqli_real_escape_string($conn, $detail);

        $sql = "INSERT INTO mrp_indents_confirmation_log (
            confirmation_id, shortages_log_id, indent_id, indent_no, action_type, action_status,
            material_code, material_name, workorder_no, order_no, qty, qty_unit,
            action_by, action_by_name, department, remark, event_detail, event_datetime
        ) VALUES (
            " . (int)($data['confirmation_id'] ?? 0) . ",
            " . (int)($data['shortages_log_id'] ?? 0) . ",
            " . (int)($data['indent_id'] ?? 0) . ",
            '" . mysqli_real_escape_string($conn, $data['indent_no'] ?? '') . "',
            '" . mysqli_real_escape_string($conn, $data['action_type'] ?? 'ACTION') . "',
            '" . mysqli_real_escape_string($conn, $data['action_status'] ?? '') . "',
            '" . mysqli_real_escape_string($conn, $data['material_code'] ?? '') . "',
            '" . mysqli_real_escape_string($conn, $data['material_name'] ?? '') . "',
            '" . mysqli_real_escape_string($conn, $data['workorder_no'] ?? '') . "',
            '" . mysqli_real_escape_string($conn, $data['order_no'] ?? '') . "',
            " . (float)($data['qty'] ?? 0) . ",
            '" . mysqli_real_escape_string($conn, $data['qty_unit'] ?? '') . "',
            '$actionBy',
            '$actionByName',
            '$department',
            '" . mysqli_real_escape_string($conn, $data['remark'] ?? '') . "',
            '$detailEsc',
            '$eventDatetime'
        )";
        $conn->query($sql);
    }
}

if (!function_exists('gw_get_mrp_indents_confirmation_list')) {
    function gw_get_mrp_indents_confirmation_list($conn, $filters = []) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $status = mysqli_real_escape_string($conn, $filters['status'] ?? '');
        $search = mysqli_real_escape_string($conn, $filters['search'] ?? '');
        $where = "WHERE 1=1";
        if ($status !== '' && strtoupper($status) !== 'ALL') {
            $where .= " AND c.confirmation_status = '$status'";
        }
        if ($search !== '') {
            $where .= " AND (
                c.material_code LIKE '%$search%' OR c.material_name LIKE '%$search%' OR
                c.workorder_no LIKE '%$search%' OR c.order_no LIKE '%$search%' OR
                c.forecast_no LIKE '%$search%' OR c.indent_no LIKE '%$search%' OR
                c.product_code LIKE '%$search%' OR c.product_name LIKE '%$search%'
            )";
        }

        $sql = "SELECT c.*,
                COALESCE(ir.status, '') AS indent_raw_status,
                COALESCE(ir.po_indend, 'pending') AS po_indend_status
            FROM mrp_indents_confirmation c
            LEFT JOIN indend_raw ir ON CAST(ir.id AS CHAR) = CAST(c.indent_id AS CHAR)
            $where
            ORDER BY c.indent_raised_date DESC, c.id DESC
            LIMIT 2000";
        $output = [];
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }
}

if (!function_exists('gw_get_mrp_indents_confirmation_log')) {
    function gw_get_mrp_indents_confirmation_log($conn, $limit = 300) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $limit = max(1, min(500, (int)$limit));
        $output = [];
        $sql = "SELECT * FROM mrp_indents_confirmation_log ORDER BY event_datetime DESC, id DESC LIMIT $limit";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }
}

if (!function_exists('gw_get_planning_sent_open_indent_qty')) {
    /** Open indent from planning confirmation (sent for purchase, PO not yet raised). */
    function gw_get_planning_sent_open_indent_qty($conn, $material_code, $exclude_plan_month = '') {
        if (empty($material_code)) {
            return 0;
        }
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $code = $conn->real_escape_string($material_code);
        $excludeClause = '';
        if (!empty($exclude_plan_month)) {
            $exclude = $conn->real_escape_string($exclude_plan_month);
            $excludeClause = " AND COALESCE(c.plan_month, '') <> '$exclude'";
        }
        $sql = "SELECT IFNULL(SUM(CAST(COALESCE(c.raised_indent_qty, 0) AS DECIMAL(15,4))), 0) AS open_qty
                FROM mrp_indents_confirmation c
                LEFT JOIN indend_raw ir ON CAST(ir.id AS CHAR) = CAST(c.indent_id AS CHAR)
                WHERE c.material_code = '$code'
                  AND c.confirmation_status = 'SENT_FOR_PURCHASE'
                  AND COALESCE(c.cancelled_qty, 0) = 0
                  AND (ir.id IS NULL OR LOWER(COALESCE(ir.status, '')) NOT IN ('rejected', 'cancelled'))
                  AND COALESCE(ir.po_indend, 'pending') = 'pending'
                  $excludeClause";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['open_qty']);
        }
        return 0;
    }
}

if (!function_exists('gw_get_cancelled_planning_indent_qty')) {
    /** Cancelled planning indent qty — added back to effective available stock. */
    function gw_get_cancelled_planning_indent_qty($conn, $material_code) {
        if (empty($material_code)) {
            return 0;
        }
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $code = $conn->real_escape_string($material_code);
        $sql = "SELECT IFNULL(SUM(CAST(COALESCE(c.cancelled_qty, c.raised_indent_qty, 0) AS DECIMAL(15,4))), 0) AS cancelled_qty
                FROM mrp_indents_confirmation c
                WHERE c.material_code = '$code'
                  AND c.confirmation_status = 'CANCELLED'";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['cancelled_qty']);
        }
        return 0;
    }
}

if (!function_exists('gw_apply_mrp_indent_confirmation_action')) {
    function gw_apply_mrp_indent_confirmation_action($conn, $confirmationId, $action, $remark = '', $meta = []) {
        gw_ensure_mrp_indents_confirmation_tables($conn);
        $confirmationId = (int)$confirmationId;
        $action = strtoupper(trim($action));
        $allowed = ['SENT_FOR_PURCHASE', 'CANCEL', 'ON_HOLD', 'LOCK', 'UNLOCK'];
        if (!in_array($action, $allowed, true)) {
            return ['status' => 'error', 'message' => 'Invalid action'];
        }

        $res = $conn->query("SELECT * FROM mrp_indents_confirmation WHERE id = $confirmationId LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return ['status' => 'error', 'message' => 'Confirmation record not found'];
        }
        $row = $res->fetch_assoc();
        $isLocked = (int)($row['is_locked'] ?? 0) === 1
            || strtoupper(trim((string)($row['confirmation_status'] ?? ''))) === 'LOCKED';

        // Locked indents cannot be silently modified — only UNLOCK (with remark) or CANCEL with remark.
        if ($isLocked && !in_array($action, ['UNLOCK', 'CANCEL'], true)) {
            return [
                'status' => 'error',
                'message' => 'Indent is locked. Unlock (with remark) or cancel via revision workflow before changing.',
            ];
        }
        if ($action === 'UNLOCK' && !$isLocked) {
            return ['status' => 'error', 'message' => 'Indent is not locked'];
        }
        if ($action === 'LOCK' && $isLocked) {
            return ['status' => 'error', 'message' => 'Already locked'];
        }
        if (in_array($action, ['LOCK', 'UNLOCK', 'CANCEL'], true) && trim((string)$remark) === '') {
            // LOCK can use a default remark; UNLOCK/CANCEL require reason.
            if ($action !== 'LOCK') {
                return ['status' => 'error', 'message' => 'Remark/reason is required for ' . $action];
            }
        }

        if ($action === 'SENT_FOR_PURCHASE' && $row['confirmation_status'] === 'SENT_FOR_PURCHASE') {
            return ['status' => 'error', 'message' => 'Already sent for purchase'];
        }
        if ($action === 'CANCEL' && $row['confirmation_status'] === 'CANCELLED') {
            return ['status' => 'error', 'message' => 'Already cancelled'];
        }

        date_default_timezone_set('Asia/Kolkata');
        $now = date('Y-m-d H:i:s');
        $empId = mysqli_real_escape_string($conn, $meta['emp_id'] ?? ($_GET['emp_id'] ?? ''));
        $empName = mysqli_real_escape_string($conn, $meta['raised_by_name'] ?? '');
        $remarkEsc = mysqli_real_escape_string($conn, $remark);
        $dept = mysqli_real_escape_string($conn, $meta['department'] ?? ($_GET['department'] ?? ''));

        if ($action === 'LOCK') {
            $newStatus = 'LOCKED';
        } elseif ($action === 'UNLOCK') {
            // Return to SENT_FOR_PURCHASE if previously sent, else PENDING.
            $prev = strtoupper(trim((string)($row['confirmation_status'] ?? '')));
            $newStatus = ($prev === 'LOCKED') ? 'SENT_FOR_PURCHASE' : 'PENDING';
            if ($prev === 'LOCKED' && (int)($row['is_locked'] ?? 0) === 1) {
                // Prefer last non-lock status from log if available.
                $lr = @$conn->query(
                    "SELECT action_status FROM mrp_indents_confirmation_log
                     WHERE confirmation_id = $confirmationId
                       AND action_type NOT IN ('LOCK','UNLOCK')
                     ORDER BY id DESC LIMIT 1"
                );
                if ($lr && $lr->num_rows > 0) {
                    $last = strtoupper(trim((string)($lr->fetch_assoc()['action_status'] ?? '')));
                    if (in_array($last, ['PENDING', 'SENT_FOR_PURCHASE', 'ON_HOLD'], true)) {
                        $newStatus = $last;
                    }
                }
            }
        } elseif ($action === 'CANCEL') {
            $newStatus = 'CANCELLED';
        } elseif ($action === 'ON_HOLD') {
            $newStatus = 'ON_HOLD';
        } else {
            $newStatus = 'SENT_FOR_PURCHASE';
        }

        $cancelledQty = $action === 'CANCEL' ? (float)($row['raised_indent_qty'] ?? 0) : 0;
        $lockRev = (int)($row['lock_revision_no'] ?? 0);
        if ($action === 'LOCK' || $action === 'UNLOCK') {
            $lockRev++;
        }

        $conn->begin_transaction();
        try {
            if ($action === 'LOCK') {
                $sql = "UPDATE mrp_indents_confirmation SET
                    confirmation_status = 'LOCKED',
                    is_locked = 1,
                    locked_by = '$empId',
                    locked_by_name = '$empName',
                    locked_at = '$now',
                    lock_remark = '$remarkEsc',
                    lock_revision_no = $lockRev,
                    remark = '$remarkEsc',
                    updated_by = '$empId',
                    updated_by_name = '$empName',
                    updated_at = '$now'
                    WHERE id = $confirmationId";
            } elseif ($action === 'UNLOCK') {
                $sql = "UPDATE mrp_indents_confirmation SET
                    confirmation_status = '$newStatus',
                    is_locked = 0,
                    lock_remark = CONCAT(IFNULL(lock_remark,''), ' | Unlock: $remarkEsc'),
                    lock_revision_no = $lockRev,
                    remark = '$remarkEsc',
                    updated_by = '$empId',
                    updated_by_name = '$empName',
                    updated_at = '$now'
                    WHERE id = $confirmationId";
            } else {
                $lockClear = $action === 'CANCEL' ? ", is_locked = 0" : "";
                $sql = "UPDATE mrp_indents_confirmation SET
                    confirmation_status = '$newStatus',
                    cancelled_qty = $cancelledQty,
                    remark = '$remarkEsc',
                    updated_by = '$empId',
                    updated_by_name = '$empName',
                    updated_at = '$now'
                    $lockClear
                    WHERE id = $confirmationId";
            }
            if (!$conn->query($sql)) {
                throw new Exception($conn->error);
            }

            $indentId = (int)($row['indent_id'] ?? 0);
            $woNo = mysqli_real_escape_string($conn, $row['workorder_no'] ?? '');
            $matCode = mysqli_real_escape_string($conn, $row['material_code'] ?? '');

            if ($action === 'CANCEL') {
                if ($indentId > 0) {
                    $conn->query("UPDATE indend_raw SET status = 'Rejected', po_indend = 'pending' WHERE id = $indentId");
                }
                if ($woNo !== '' && $matCode !== '') {
                    $conn->query("UPDATE WO_deductions SET
                        indent_status = 'Not Raised',
                        indent_id = NULL,
                        indent_no = NULL,
                        status = 'Pending'
                        WHERE workorder_no = '$woNo' AND material_code = '$matCode'");
                }
            } elseif ($action === 'SENT_FOR_PURCHASE' && $indentId > 0) {
                $conn->query("UPDATE indend_raw SET status = 'pending' WHERE id = $indentId");
            }

            gw_write_mrp_indent_confirmation_log($conn, [
                'confirmation_id' => $confirmationId,
                'shortages_log_id' => (int)($row['shortages_log_id'] ?? 0),
                'indent_id' => $indentId,
                'indent_no' => $row['indent_no'] ?? '',
                'action_type' => $action,
                'action_status' => $newStatus,
                'material_code' => $row['material_code'] ?? '',
                'material_name' => $row['material_name'] ?? '',
                'workorder_no' => $row['workorder_no'] ?? '',
                'order_no' => $row['order_no'] ?? '',
                'qty' => $action === 'CANCEL' ? $cancelledQty : (float)($row['raised_indent_qty'] ?? 0),
                'qty_unit' => $row['qty_unit'] ?? '',
                'remark' => $remark,
                'action_by' => $meta['emp_id'] ?? ($_GET['emp_id'] ?? ''),
                'action_by_name' => $meta['raised_by_name'] ?? '',
                'department' => $meta['department'] ?? ($_GET['department'] ?? ''),
                'event_detail' => json_encode([
                    'is_locked' => ($action === 'LOCK') ? 1 : 0,
                    'lock_revision_no' => $lockRev,
                    'previous_status' => $row['confirmation_status'] ?? '',
                ]),
            ]);

            $conn->commit();
            $result = [
                'status' => 'success',
                'message' => 'Indent ' . strtolower($action) . ' applied',
                'confirmation_id' => $confirmationId,
                'confirmation_status' => $newStatus,
                'is_locked' => ($action === 'LOCK') ? 1 : 0,
                'lock_revision_no' => $lockRev,
            ];
            if ($action === 'CANCEL' && function_exists('gw_mrp_recalc_after_indent_cancel')) {
                $recalc = @gw_mrp_recalc_after_indent_cancel($conn, $confirmationId, array_merge($meta, [
                    'remark' => $remark,
                    'plant_id' => $_GET['plant_id'] ?? ($meta['plant_id'] ?? ''),
                ]));
                if (is_array($recalc)) {
                    $result['cancel_recalc'] = $recalc;
                }
            }
            return $result;
        } catch (Exception $e) {
            $conn->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
