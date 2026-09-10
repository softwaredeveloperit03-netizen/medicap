<?php
/**
 * Unified MRP audit trail.
 *
 * One table (`mrp_audit_log`) captures a point-in-time snapshot at EVERY stage of
 * the WO processing / shortage-analysis lifecycle, for full traceability and future
 * data analytics. Each row records, for the moment the event happened:
 *   - what stage / event it was and which screen raised it,
 *   - the work order / order / product / material context,
 *   - required qty, the available qty on that day, deducted qty, and the shortage,
 *   - the indent / PO references the action relates to,
 *   - status transition (from -> to), who did it, when, and a raw JSON payload.
 *
 * Design goals:
 *   - Central writer gw_mrp_audit_log() — defensive, never throws, never breaks the
 *     calling endpoint (failures are logged to error_log only).
 *   - Self-provisioning schema (CREATE TABLE IF NOT EXISTS / guarded ALTER) — no
 *     manual migration, matching mrp_shortages_log_helpers.php / wo_analysis_proceed_helpers.php.
 *   - Generic reader gw_get_mrp_audit_log() with filters for any tab's history view.
 */

if (!function_exists('gw_audit_table_exists')) {
    function gw_audit_table_exists($conn, $tableName) {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tableName);
        if ($tableName === '') {
            return false;
        }
        $res = $conn->query("SHOW TABLES LIKE '$tableName'");
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('gw_ensure_mrp_audit_log_table')) {
    function gw_ensure_mrp_audit_log_table($conn) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $sql = "CREATE TABLE IF NOT EXISTS mrp_audit_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_datetime DATETIME NOT NULL,
            stage VARCHAR(60) NOT NULL,
            event_type VARCHAR(80) DEFAULT NULL,
            event_status VARCHAR(20) NOT NULL DEFAULT 'success',
            source_screen VARCHAR(80) DEFAULT NULL,
            order_no VARCHAR(100) DEFAULT NULL,
            workorder_no VARCHAR(100) DEFAULT NULL,
            work_order_id INT DEFAULT NULL,
            doc_no VARCHAR(100) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            material_code VARCHAR(80) DEFAULT NULL,
            material_name VARCHAR(255) DEFAULT NULL,
            material_type VARCHAR(80) DEFAULT NULL,
            qty_unit VARCHAR(30) DEFAULT NULL,
            plan_month VARCHAR(50) DEFAULT NULL,
            required_qty DECIMAL(18,4) DEFAULT NULL,
            plan_qty DECIMAL(18,4) DEFAULT NULL,
            available_qty_snapshot DECIMAL(18,4) DEFAULT NULL,
            deducted_qty DECIMAL(18,4) DEFAULT NULL,
            shortage_qty DECIMAL(18,4) DEFAULT NULL,
            balance_qty DECIMAL(18,4) DEFAULT NULL,
            indent_id INT DEFAULT NULL,
            indent_no VARCHAR(80) DEFAULT NULL,
            po_no VARCHAR(80) DEFAULT NULL,
            status_from VARCHAR(80) DEFAULT NULL,
            status_to VARCHAR(80) DEFAULT NULL,
            emp_id VARCHAR(50) DEFAULT NULL,
            emp_name VARCHAR(120) DEFAULT NULL,
            department VARCHAR(120) DEFAULT NULL,
            plant_id VARCHAR(50) DEFAULT NULL,
            remark TEXT,
            event_detail LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_audit_datetime (event_datetime),
            INDEX idx_audit_stage (stage),
            INDEX idx_audit_workorder (workorder_no),
            INDEX idx_audit_material (material_code),
            INDEX idx_audit_order (order_no),
            INDEX idx_audit_indent (indent_id),
            INDEX idx_audit_product (product_code),
            INDEX idx_audit_plant (plant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @$conn->query($sql);
    }
}

if (!function_exists('gw_audit_available_qty')) {
    /**
     * Snapshot of the available stock for a material "as of now" (on that day),
     * so the audit row preserves the on-day availability for micro-traceability.
     * Returns null when no stock row is found (kept NULL rather than 0 to distinguish
     * "unknown" from "genuinely zero").
     */
    function gw_audit_available_qty($conn, $materialCode, $plantId = '') {
        $materialCode = trim((string)$materialCode);
        if ($materialCode === '' || !gw_audit_table_exists($conn, 'vw_total_available_stock')) {
            return null;
        }
        $mc = $conn->real_escape_string($materialCode);
        $sql = "SELECT available_qty FROM vw_total_available_stock WHERE material_code = '$mc'";
        if ($plantId !== '' && $plantId !== null) {
            $sql .= " AND plant_id = '" . $conn->real_escape_string((string)$plantId) . "'";
        }
        $sql .= " LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return (float)($row['available_qty'] ?? 0);
        }
        return null;
    }
}

if (!function_exists('gw_audit_emp_name')) {
    function gw_audit_emp_name($conn, $empId) {
        $empId = trim((string)$empId);
        if ($empId === '') {
            return '';
        }
        static $cache = array();
        if (isset($cache[$empId])) {
            return $cache[$empId];
        }
        $name = '';
        $res = $conn->query("SELECT COALESCE(emp_name, firstname, '') AS n FROM employee WHERE emp_id = '" . $conn->real_escape_string($empId) . "' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $name = (string)($res->fetch_assoc()['n'] ?? '');
        }
        $cache[$empId] = $name;
        return $name;
    }
}

if (!function_exists('gw_mrp_audit_log')) {
    /**
     * Central audit writer. Defensive: any failure is swallowed (error_log only) so it
     * can never break the calling MRP endpoint.
     *
     * @param array $e Event fields. Recognised keys:
     *   stage (required), event_type, event_status, source_screen,
     *   order_no, workorder_no, work_order_id, doc_no,
     *   product_code, product_name, material_code, material_name, material_type, qty_unit,
     *   plan_month, required_qty, plan_qty, available_qty_snapshot, deducted_qty,
     *   shortage_qty, balance_qty, indent_id, indent_no, po_no,
     *   status_from, status_to, emp_id, emp_name, department, plant_id, remark, detail
     *   - If 'snapshot_stock' is truthy and material_code is set, available_qty_snapshot
     *     is auto-filled from vw_total_available_stock when not supplied.
     * @return int inserted id (0 on failure)
     */
    function gw_mrp_audit_log($conn, $e) {
        if (!is_array($e)) {
            return 0;
        }
        $stage = trim((string)($e['stage'] ?? ''));
        if ($stage === '') {
            return 0;
        }
        gw_ensure_mrp_audit_log_table($conn);

        date_default_timezone_set('Asia/Kolkata');
        $dt = trim((string)($e['event_datetime'] ?? ''));
        if ($dt === '' || $dt === '0000-00-00 00:00:00' || $dt === '0000-00-00') {
            $dt = date('Y-m-d H:i:s');
        } else {
            $ts = strtotime($dt);
            $dt = ($ts === false) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $ts);
        }

        $plantId = (string)($e['plant_id'] ?? ($_GET['plant_id'] ?? ''));
        $empId = (string)($e['emp_id'] ?? ($_GET['emp_id'] ?? ''));
        $empName = (string)($e['emp_name'] ?? '');
        if ($empName === '' && $empId !== '') {
            $empName = gw_audit_emp_name($conn, $empId);
        }

        $materialCode = (string)($e['material_code'] ?? '');
        $availSnap = $e['available_qty_snapshot'] ?? null;
        if (($availSnap === null || $availSnap === '') && !empty($e['snapshot_stock']) && $materialCode !== '') {
            $availSnap = gw_audit_available_qty($conn, $materialCode, $plantId);
        }

        $detail = $e['detail'] ?? ($e['event_detail'] ?? null);
        if (is_array($detail) || is_object($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_UNICODE);
        }

        // Helpers for nullable numeric / string columns.
        $num = function ($v) use ($conn) {
            if ($v === null || $v === '') {
                return 'NULL';
            }
            return "'" . $conn->real_escape_string((string)(float)$v) . "'";
        };
        $intv = function ($v) use ($conn) {
            if ($v === null || $v === '') {
                return 'NULL';
            }
            return (string)(int)$v;
        };
        $str = function ($v) use ($conn) {
            if ($v === null) {
                return 'NULL';
            }
            return "'" . $conn->real_escape_string((string)$v) . "'";
        };

        $cols = array(
            'event_datetime' => $str($dt),
            'stage' => $str($stage),
            'event_type' => $str($e['event_type'] ?? $stage),
            'event_status' => $str($e['event_status'] ?? 'success'),
            'source_screen' => $str($e['source_screen'] ?? ''),
            'order_no' => $str($e['order_no'] ?? ''),
            'workorder_no' => $str($e['workorder_no'] ?? ''),
            'work_order_id' => $intv($e['work_order_id'] ?? null),
            'doc_no' => $str($e['doc_no'] ?? ''),
            'product_code' => $str($e['product_code'] ?? ''),
            'product_name' => $str($e['product_name'] ?? ''),
            'material_code' => $str($materialCode),
            'material_name' => $str($e['material_name'] ?? ''),
            'material_type' => $str($e['material_type'] ?? ''),
            'qty_unit' => $str($e['qty_unit'] ?? ($e['unit'] ?? '')),
            'plan_month' => $str($e['plan_month'] ?? ''),
            'required_qty' => $num($e['required_qty'] ?? null),
            'plan_qty' => $num($e['plan_qty'] ?? null),
            'available_qty_snapshot' => $num($availSnap),
            'deducted_qty' => $num($e['deducted_qty'] ?? null),
            'shortage_qty' => $num($e['shortage_qty'] ?? null),
            'balance_qty' => $num($e['balance_qty'] ?? null),
            'indent_id' => $intv($e['indent_id'] ?? null),
            'indent_no' => $str($e['indent_no'] ?? ''),
            'po_no' => $str($e['po_no'] ?? ''),
            'status_from' => $str($e['status_from'] ?? ''),
            'status_to' => $str($e['status_to'] ?? ''),
            'emp_id' => $str($empId),
            'emp_name' => $str($empName),
            'department' => $str($e['department'] ?? ($_GET['department'] ?? '')),
            'plant_id' => $str($plantId),
            'remark' => $str($e['remark'] ?? ''),
            'event_detail' => ($detail === null ? 'NULL' : $str($detail)),
        );

        $sql = 'INSERT INTO mrp_audit_log (' . implode(', ', array_keys($cols)) . ') VALUES (' . implode(', ', array_values($cols)) . ')';
        if (!@$conn->query($sql)) {
            error_log('gw_mrp_audit_log failed: ' . $conn->error);
            return 0;
        }
        return (int)$conn->insert_id;
    }
}

if (!function_exists('gw_mrp_audit_log_bulk')) {
    /** Convenience: write many events sharing common base fields. */
    function gw_mrp_audit_log_bulk($conn, $events, $base = array()) {
        if (!is_array($events)) {
            return 0;
        }
        $count = 0;
        foreach ($events as $ev) {
            if (!is_array($ev)) {
                continue;
            }
            if (gw_mrp_audit_log($conn, array_merge($base, $ev)) > 0) {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('gw_get_mrp_audit_log')) {
    /**
     * Generic reader for any tab's history view.
     * @param array $f Filters: stage, workorder_no, material_code, order_no, product_code,
     *                 indent_id, po_no, plant_id, source_screen, date_from, date_to, search, limit.
     */
    function gw_get_mrp_audit_log($conn, $f = array()) {
        gw_ensure_mrp_audit_log_table($conn);
        if (!is_array($f)) {
            $f = array();
        }
        $where = array('1=1');

        $eq = function ($col, $key) use (&$where, $conn, $f) {
            if (isset($f[$key]) && trim((string)$f[$key]) !== '') {
                $where[] = "$col = '" . $conn->real_escape_string(trim((string)$f[$key])) . "'";
            }
        };
        // 'stage' may be a comma-separated list.
        if (isset($f['stage']) && trim((string)$f['stage']) !== '') {
            $stages = array_filter(array_map('trim', explode(',', (string)$f['stage'])));
            if (!empty($stages)) {
                $escaped = array_map(function ($s) use ($conn) {
                    return "'" . $conn->real_escape_string($s) . "'";
                }, $stages);
                $where[] = 'stage IN (' . implode(',', $escaped) . ')';
            }
        }
        $eq('workorder_no', 'workorder_no');
        $eq('material_code', 'material_code');
        $eq('order_no', 'order_no');
        $eq('product_code', 'product_code');
        $eq('po_no', 'po_no');
        $eq('source_screen', 'source_screen');
        $eq('plant_id', 'plant_id');
        if (isset($f['indent_id']) && (int)$f['indent_id'] > 0) {
            $where[] = 'indent_id = ' . (int)$f['indent_id'];
        }
        if (isset($f['date_from']) && trim((string)$f['date_from']) !== '') {
            $where[] = "event_datetime >= '" . $conn->real_escape_string(trim((string)$f['date_from'])) . " 00:00:00'";
        }
        if (isset($f['date_to']) && trim((string)$f['date_to']) !== '') {
            $where[] = "event_datetime <= '" . $conn->real_escape_string(trim((string)$f['date_to'])) . " 23:59:59'";
        }
        if (isset($f['search']) && trim((string)$f['search']) !== '') {
            $s = $conn->real_escape_string(trim((string)$f['search']));
            $where[] = "(workorder_no LIKE '%$s%' OR material_code LIKE '%$s%' OR material_name LIKE '%$s%'"
                . " OR order_no LIKE '%$s%' OR product_code LIKE '%$s%' OR indent_no LIKE '%$s%'"
                . " OR po_no LIKE '%$s%' OR emp_name LIKE '%$s%')";
        }

        $limit = isset($f['limit']) ? (int)$f['limit'] : 500;
        $limit = max(1, min(5000, $limit));

        $output = array();
        $sql = 'SELECT * FROM mrp_audit_log WHERE ' . implode(' AND ', $where)
            . ' ORDER BY event_datetime DESC, id DESC LIMIT ' . $limit;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }
}
