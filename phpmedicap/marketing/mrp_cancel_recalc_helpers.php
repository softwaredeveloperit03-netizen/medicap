<?php
/**
 * MRP Phase 7 — Cancel / reduce recalculation snapshot (shortage & availability after indent cancel).
 */

if (!function_exists('gw_ensure_mrp_cancel_recalc_log')) {
    function gw_ensure_mrp_cancel_recalc_log($conn) {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS mrp_cancel_recalc_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(40) NOT NULL DEFAULT 'INDENT_CANCEL',
                confirmation_id INT DEFAULT NULL,
                indent_id INT DEFAULT NULL,
                workorder_no VARCHAR(100) DEFAULT NULL,
                order_no VARCHAR(100) DEFAULT NULL,
                material_code VARCHAR(80) DEFAULT NULL,
                material_name VARCHAR(255) DEFAULT NULL,
                cancelled_qty DECIMAL(18,4) DEFAULT NULL,
                open_indent_qty_after DECIMAL(18,4) DEFAULT NULL,
                cancelled_indent_qty_total DECIMAL(18,4) DEFAULT NULL,
                store_available_qty DECIMAL(18,4) DEFAULT NULL,
                net_shortage_qty DECIMAL(18,4) DEFAULT NULL,
                remark TEXT,
                recalc_json LONGTEXT,
                action_by VARCHAR(50) DEFAULT NULL,
                action_by_name VARCHAR(120) DEFAULT NULL,
                department VARCHAR(120) DEFAULT NULL,
                plant_id VARCHAR(50) DEFAULT NULL,
                event_datetime DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_mcrl_material (material_code),
                INDEX idx_mcrl_conf (confirmation_id),
                INDEX idx_mcrl_event (event_datetime)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            // ignore
        }
        $done = true;
    }
}

if (!function_exists('gw_mrp_recalc_after_indent_cancel')) {
    /**
     * After indent cancel/reduce: snapshot availability + cancelled qty impact.
     */
    function gw_mrp_recalc_after_indent_cancel($conn, $confirmationId, $meta = []) {
        gw_ensure_mrp_cancel_recalc_log($conn);
        if (function_exists('gw_ensure_mrp_indents_confirmation_tables')) {
            gw_ensure_mrp_indents_confirmation_tables($conn);
        }

        $confirmationId = (int)$confirmationId;
        if ($confirmationId <= 0) {
            return array('status' => 'error', 'message' => 'confirmation_id is required');
        }

        $res = @$conn->query("SELECT * FROM mrp_indents_confirmation WHERE id = $confirmationId LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return array('status' => 'error', 'message' => 'Confirmation not found');
        }
        $row = $res->fetch_assoc();
        $materialCode = trim((string)($row['material_code'] ?? ''));
        if ($materialCode === '') {
            return array('status' => 'error', 'message' => 'material_code missing on confirmation');
        }

        $cancelledQty = (float)($row['cancelled_qty'] ?? 0);
        if ($cancelledQty <= 0) {
            $cancelledQty = (float)($row['raised_indent_qty'] ?? 0);
        }

        $openAfter = function_exists('gw_get_planning_sent_open_indent_qty')
            ? (float)gw_get_planning_sent_open_indent_qty($conn, $materialCode)
            : 0;
        $cancelledTotal = function_exists('gw_get_cancelled_planning_indent_qty')
            ? (float)gw_get_cancelled_planning_indent_qty($conn, $materialCode)
            : $cancelledQty;

        $avail = null;
        if (function_exists('gw_get_mrp_material_availability_detail')) {
            try {
                $avail = gw_get_mrp_material_availability_detail($conn, $materialCode, array(
                    'plant_id' => $meta['plant_id'] ?? ($_GET['plant_id'] ?? ''),
                    'required_qty' => $cancelledQty,
                ));
            } catch (Throwable $e) {
                $avail = array('status' => 'error', 'message' => $e->getMessage());
            }
        }

        $storeQty = null;
        $netShortage = null;
        if (is_array($avail)) {
            $storeQty = $avail['available_store_qty'] ?? null;
            $netShortage = $avail['net_shortage_qty'] ?? null;
        }

        date_default_timezone_set('Asia/Kolkata');
        $now = date('Y-m-d H:i:s');
        $empId = $conn->real_escape_string((string)($meta['emp_id'] ?? ($_GET['emp_id'] ?? '')));
        $empName = $conn->real_escape_string((string)($meta['raised_by_name'] ?? ($meta['emp_name'] ?? '')));
        $dept = $conn->real_escape_string((string)($meta['department'] ?? ($_GET['department'] ?? '')));
        $plant = $conn->real_escape_string((string)($meta['plant_id'] ?? ($_GET['plant_id'] ?? '')));
        $remark = $conn->real_escape_string((string)($meta['remark'] ?? ($row['remark'] ?? 'Indent cancel recalculation')));
        $json = $conn->real_escape_string(json_encode(array(
            'confirmation' => array(
                'id' => $confirmationId,
                'status' => $row['confirmation_status'] ?? '',
                'raised_indent_qty' => $row['raised_indent_qty'] ?? 0,
                'cancelled_qty' => $cancelledQty,
            ),
            'open_indent_qty_after' => $openAfter,
            'cancelled_indent_qty_total' => $cancelledTotal,
            'availability' => $avail,
        ), JSON_UNESCAPED_UNICODE));

        $storeSql = $storeQty === null || $storeQty === '' ? 'NULL' : (float)$storeQty;
        $netSql = $netShortage === null || $netShortage === '' ? 'NULL' : (float)$netShortage;

        $sql = "INSERT INTO mrp_cancel_recalc_log (
            event_type, confirmation_id, indent_id, workorder_no, order_no,
            material_code, material_name, cancelled_qty, open_indent_qty_after,
            cancelled_indent_qty_total, store_available_qty, net_shortage_qty,
            remark, recalc_json, action_by, action_by_name, department, plant_id, event_datetime
        ) VALUES (
            'INDENT_CANCEL',
            $confirmationId,
            ".(int)($row['indent_id'] ?? 0).",
            '".$conn->real_escape_string((string)($row['workorder_no'] ?? ''))."',
            '".$conn->real_escape_string((string)($row['order_no'] ?? ''))."',
            '".$conn->real_escape_string($materialCode)."',
            '".$conn->real_escape_string((string)($row['material_name'] ?? ''))."',
            $cancelledQty,
            $openAfter,
            $cancelledTotal,
            $storeSql,
            $netSql,
            '$remark',
            '$json',
            '$empId',
            '$empName',
            '$dept',
            '$plant',
            '$now'
        )";

        try {
            if (!$conn->query($sql)) {
                return array('status' => 'error', 'message' => $conn->error ?: 'Failed to write recalc log');
            }
        } catch (Throwable $e) {
            return array('status' => 'error', 'message' => $e->getMessage());
        }

        return array(
            'status' => 'success',
            'message' => 'Cancel recalculation snapshot saved',
            'recalc_id' => (int)$conn->insert_id,
            'confirmation_id' => $confirmationId,
            'material_code' => $materialCode,
            'cancelled_qty' => $cancelledQty,
            'open_indent_qty_after' => $openAfter,
            'cancelled_indent_qty_total' => $cancelledTotal,
            'store_available_qty' => $storeQty,
            'net_shortage_qty' => $netShortage,
            'event_datetime' => $now,
        );
    }
}

if (!function_exists('gw_mrp_get_cancel_recalc_log')) {
    function gw_mrp_get_cancel_recalc_log($conn, $params = []) {
        gw_ensure_mrp_cancel_recalc_log($conn);
        $limit = max(1, min(300, (int)($params['limit'] ?? 50)));
        $material = trim((string)($params['material_code'] ?? ''));
        $confId = (int)($params['confirmation_id'] ?? 0);
        $where = '1=1';
        if ($material !== '') {
            $where .= " AND material_code = '".$conn->real_escape_string($material)."'";
        }
        if ($confId > 0) {
            $where .= " AND confirmation_id = $confId";
        }
        $rows = array();
        try {
            $res = $conn->query(
                "SELECT * FROM mrp_cancel_recalc_log WHERE $where ORDER BY id DESC LIMIT $limit"
            );
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
        } catch (Throwable $e) {
            return array('status' => 'error', 'message' => $e->getMessage(), 'rows' => array(), 'total' => 0);
        }
        return array('status' => 'success', 'rows' => $rows, 'total' => count($rows));
    }
}
