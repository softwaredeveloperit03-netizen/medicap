<?php
/**
 * MRP Phase 6 — versioned line-booking history (append-only; does not replace live line_booking).
 */

if (!function_exists('gw_ensure_mrp_line_booking_history_table')) {
    function gw_ensure_mrp_line_booking_history_table($conn) {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS mrp_line_booking_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                workorder_no VARCHAR(100) DEFAULT NULL,
                linemaster_id INT DEFAULT NULL,
                line_no VARCHAR(50) DEFAULT NULL,
                version_no INT NOT NULL DEFAULT 1,
                action_type VARCHAR(40) NOT NULL DEFAULT 'SNAPSHOT',
                product_code VARCHAR(100) DEFAULT NULL,
                product_name VARCHAR(255) DEFAULT NULL,
                booking_start_date DATE DEFAULT NULL,
                booking_start_time VARCHAR(20) DEFAULT NULL,
                booking_end_date DATE DEFAULT NULL,
                booking_end_time VARCHAR(20) DEFAULT NULL,
                responsible_person VARCHAR(255) DEFAULT NULL,
                selected_equipments LONGTEXT,
                capacity_required VARCHAR(80) DEFAULT NULL,
                no_of_hours_required DECIMAL(12,2) DEFAULT NULL,
                booking_status VARCHAR(40) DEFAULT NULL,
                plant_id VARCHAR(50) DEFAULT NULL,
                change_remark TEXT,
                changed_by VARCHAR(50) DEFAULT NULL,
                changed_by_name VARCHAR(120) DEFAULT NULL,
                changed_on DATETIME NOT NULL,
                snapshot_json LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_mlb_booking (booking_id),
                INDEX idx_mlb_wo (workorder_no),
                INDEX idx_mlb_version (booking_id, version_no),
                INDEX idx_mlb_changed_on (changed_on)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            // ignore
        }
        $done = true;
    }
}

if (!function_exists('gw_mrp_next_line_booking_version')) {
    function gw_mrp_next_line_booking_version($conn, $bookingId) {
        $bookingId = (int)$bookingId;
        if ($bookingId <= 0) {
            return 1;
        }
        $res = @$conn->query(
            "SELECT IFNULL(MAX(version_no), 0) AS m FROM mrp_line_booking_history WHERE booking_id = $bookingId"
        );
        if ($res && ($row = $res->fetch_assoc())) {
            return ((int)$row['m']) + 1;
        }
        return 1;
    }
}

if (!function_exists('gw_mrp_snapshot_line_booking')) {
    /**
     * Append a versioned snapshot of a live line_booking row.
     */
    function gw_mrp_snapshot_line_booking($conn, $bookingId, $actionType = 'SNAPSHOT', $remark = '', $meta = []) {
        gw_ensure_mrp_line_booking_history_table($conn);
        $bookingId = (int)$bookingId;
        if ($bookingId <= 0) {
            return array('status' => 'error', 'message' => 'booking_id is required');
        }

        $res = @$conn->query("SELECT * FROM line_booking WHERE id = $bookingId LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return array('status' => 'error', 'message' => 'Booking not found');
        }
        $row = $res->fetch_assoc();
        $version = gw_mrp_next_line_booking_version($conn, $bookingId);

        date_default_timezone_set('Asia/Kolkata');
        $now = date('Y-m-d H:i:s');
        $empId = $conn->real_escape_string((string)($meta['emp_id'] ?? ($_GET['emp_id'] ?? '')));
        $empName = $conn->real_escape_string((string)($meta['emp_name'] ?? ''));
        $remarkEsc = $conn->real_escape_string((string)$remark);
        $actionEsc = $conn->real_escape_string(strtoupper(trim((string)$actionType)) ?: 'SNAPSHOT');
        $snap = $conn->real_escape_string(json_encode($row, JSON_UNESCAPED_UNICODE));

        $eq = $row['selected_equipments'] ?? '[]';
        if (is_array($eq)) {
            $eq = json_encode($eq);
        }
        $eqEsc = $conn->real_escape_string((string)$eq);
        $hours = isset($row['no_of_hours_required']) ? (float)$row['no_of_hours_required'] : 'NULL';
        if ($hours !== 'NULL') {
            $hours = (string)$hours;
        }

        $sql = "INSERT INTO mrp_line_booking_history (
            booking_id, workorder_no, linemaster_id, line_no, version_no, action_type,
            product_code, product_name, booking_start_date, booking_start_time,
            booking_end_date, booking_end_time, responsible_person, selected_equipments,
            capacity_required, no_of_hours_required, booking_status, plant_id,
            change_remark, changed_by, changed_by_name, changed_on, snapshot_json
        ) VALUES (
            $bookingId,
            '".$conn->real_escape_string((string)($row['workorder_no'] ?? ''))."',
            ".(int)($row['linemaster_id'] ?? 0).",
            '".$conn->real_escape_string((string)($row['line_no'] ?? ''))."',
            $version,
            '$actionEsc',
            '".$conn->real_escape_string((string)($row['product_code'] ?? ''))."',
            '".$conn->real_escape_string((string)($row['product_name'] ?? ''))."',
            ".(($row['booking_start_date'] ?? '') !== '' ? "'".$conn->real_escape_string($row['booking_start_date'])."'" : 'NULL').",
            '".$conn->real_escape_string((string)($row['booking_start_time'] ?? ''))."',
            ".(($row['booking_end_date'] ?? '') !== '' ? "'".$conn->real_escape_string($row['booking_end_date'])."'" : 'NULL').",
            '".$conn->real_escape_string((string)($row['booking_end_time'] ?? ''))."',
            '".$conn->real_escape_string((string)($row['responsible_person'] ?? ''))."',
            '$eqEsc',
            '".$conn->real_escape_string((string)($row['capacity_required'] ?? ''))."',
            $hours,
            '".$conn->real_escape_string((string)($row['status'] ?? ''))."',
            '".$conn->real_escape_string((string)($row['plant_id'] ?? ($_GET['plant_id'] ?? '')))."',
            '$remarkEsc',
            '$empId',
            '$empName',
            '$now',
            '$snap'
        )";

        try {
            if (!$conn->query($sql)) {
                return array('status' => 'error', 'message' => $conn->error ?: 'Insert history failed');
            }
        } catch (Throwable $e) {
            return array('status' => 'error', 'message' => $e->getMessage());
        }

        return array(
            'status' => 'success',
            'booking_id' => $bookingId,
            'workorder_no' => $row['workorder_no'] ?? '',
            'version_no' => $version,
            'action_type' => strtoupper(trim((string)$actionType)) ?: 'SNAPSHOT',
            'history_id' => (int)$conn->insert_id,
        );
    }
}

if (!function_exists('gw_mrp_get_line_booking_versions')) {
    function gw_mrp_get_line_booking_versions($conn, $params = []) {
        gw_ensure_mrp_line_booking_history_table($conn);
        $bookingId = (int)($params['booking_id'] ?? 0);
        $workorderNo = trim((string)($params['workorder_no'] ?? ''));
        $limit = max(1, min(500, (int)($params['limit'] ?? 100)));

        $where = '1=1';
        if ($bookingId > 0) {
            $where .= " AND booking_id = $bookingId";
        }
        if ($workorderNo !== '') {
            $where .= " AND workorder_no = '".$conn->real_escape_string($workorderNo)."'";
        }

        $rows = array();
        try {
            $sql = "SELECT * FROM mrp_line_booking_history
                    WHERE $where
                    ORDER BY booking_id ASC, version_no DESC, id DESC
                    LIMIT $limit";
            $res = $conn->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if (!empty($row['selected_equipments']) && is_string($row['selected_equipments'])) {
                        $decoded = json_decode($row['selected_equipments'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $row['selected_equipments'] = $decoded;
                        }
                    }
                    $rows[] = $row;
                }
            }
        } catch (Throwable $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'rows' => array(),
                'total' => 0,
            );
        }

        return array(
            'status' => 'success',
            'rows' => $rows,
            'total' => count($rows),
        );
    }
}
