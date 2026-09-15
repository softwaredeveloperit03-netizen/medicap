<?php
/**
 * MRP consolidated shortage approval (gate before indent raise).
 * Self-provisioning tables + submit / approve / reject / status helpers.
 */

if (!function_exists('gw_ensure_mrp_shortage_approval_tables')) {
    function gw_ensure_mrp_shortage_approval_tables($conn) {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        $conn->query("CREATE TABLE IF NOT EXISTS mrp_shortage_approval (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) DEFAULT NULL,
            material_code VARCHAR(80) NOT NULL,
            material_name VARCHAR(255) DEFAULT NULL,
            material_type VARCHAR(120) DEFAULT NULL,
            mother_code VARCHAR(80) DEFAULT NULL,
            uom VARCHAR(30) DEFAULT NULL,
            client_code VARCHAR(80) DEFAULT NULL,
            client_name VARCHAR(255) DEFAULT NULL,
            required_qty DECIMAL(18,4) DEFAULT NULL,
            available_store_qty DECIMAL(18,4) DEFAULT NULL,
            reserved_qty DECIMAL(18,4) DEFAULT NULL,
            under_test_qty DECIMAL(18,4) DEFAULT NULL,
            open_po_qty DECIMAL(18,4) DEFAULT NULL,
            transit_po_qty DECIMAL(18,4) DEFAULT NULL,
            open_indent_qty DECIMAL(18,4) DEFAULT NULL,
            net_shortage_qty DECIMAL(18,4) DEFAULT NULL,
            wo_count INT DEFAULT 0,
            revision_no INT NOT NULL DEFAULT 1,
            approval_status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
            submitted_by VARCHAR(50) DEFAULT NULL,
            submitted_by_name VARCHAR(120) DEFAULT NULL,
            submitted_at DATETIME DEFAULT NULL,
            submitted_remark TEXT,
            approved_by VARCHAR(50) DEFAULT NULL,
            approved_by_name VARCHAR(120) DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            approval_remark TEXT,
            rejected_by VARCHAR(50) DEFAULT NULL,
            rejected_by_name VARCHAR(120) DEFAULT NULL,
            rejected_at DATETIME DEFAULT NULL,
            rejection_remark TEXT,
            snapshot_json LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_msa_material (material_code),
            INDEX idx_msa_status (approval_status),
            INDEX idx_msa_plant (plant_id),
            INDEX idx_msa_submitted_at (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS mrp_shortage_approval_line (
            id INT AUTO_INCREMENT PRIMARY KEY,
            approval_id INT NOT NULL,
            wo_deduction_id INT DEFAULT NULL,
            workorder_no VARCHAR(100) DEFAULT NULL,
            order_no VARCHAR(100) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            client_code VARCHAR(80) DEFAULT NULL,
            client_name VARCHAR(255) DEFAULT NULL,
            required_qty DECIMAL(18,4) DEFAULT NULL,
            shortage_qty DECIMAL(18,4) DEFAULT NULL,
            reserved_qty DECIMAL(18,4) DEFAULT NULL,
            INDEX idx_msal_approval (approval_id),
            INDEX idx_msal_material_wo (workorder_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS mrp_shortage_approval_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            approval_id INT DEFAULT NULL,
            material_code VARCHAR(80) DEFAULT NULL,
            action_type VARCHAR(40) NOT NULL,
            from_status VARCHAR(30) DEFAULT NULL,
            to_status VARCHAR(30) DEFAULT NULL,
            revision_no INT DEFAULT NULL,
            action_by VARCHAR(50) DEFAULT NULL,
            action_by_name VARCHAR(120) DEFAULT NULL,
            department VARCHAR(120) DEFAULT NULL,
            remark TEXT,
            event_detail LONGTEXT,
            event_datetime DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_msalog_approval (approval_id),
            INDEX idx_msalog_material (material_code),
            INDEX idx_msalog_event (event_datetime)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $done = true;
    }
}

if (!function_exists('gw_msa_emp_display_name')) {
    function gw_msa_emp_display_name($conn, $empId) {
        $empId = trim((string)$empId);
        if ($empId === '' || !($conn instanceof mysqli)) {
            return '';
        }
        $esc = $conn->real_escape_string($empId);
        $res = @$conn->query(
            "SELECT CASE
                WHEN TRIM(IFNULL(firstname,'')) <> '' THEN CONCAT(TRIM(firstname), ' (', emp_id, ')')
                ELSE emp_id END AS nm
             FROM employee WHERE emp_id = '$esc' LIMIT 1"
        );
        if ($res && ($row = $res->fetch_assoc())) {
            return (string)($row['nm'] ?? $empId);
        }
        return $empId;
    }
}

if (!function_exists('gw_msa_log')) {
    function gw_msa_log($conn, $approvalId, $materialCode, $action, $from, $to, $revision, $by, $byName, $dept, $remark = '', $detail = null) {
        gw_ensure_mrp_shortage_approval_tables($conn);
        $approvalId = (int)$approvalId;
        $esc = function ($v) use ($conn) {
            return "'" . $conn->real_escape_string((string)($v ?? '')) . "'";
        };
        $detailSql = 'NULL';
        if ($detail !== null) {
            $detailSql = $esc(is_string($detail) ? $detail : json_encode($detail));
        }
        $rev = $revision === null ? 'NULL' : (int)$revision;
        $sql = "INSERT INTO mrp_shortage_approval_log (
                    approval_id, material_code, action_type, from_status, to_status, revision_no,
                    action_by, action_by_name, department, remark, event_detail, event_datetime
                ) VALUES (
                    $approvalId, ".$esc($materialCode).", ".$esc($action).", ".$esc($from).", ".$esc($to).", $rev,
                    ".$esc($by).", ".$esc($byName).", ".$esc($dept).", ".$esc($remark).", $detailSql, NOW()
                )";
        @$conn->query($sql);
    }
}

if (!function_exists('gw_msa_latest_by_materials')) {
    /** Latest approval row per material_code (any status). */
    function gw_msa_latest_by_materials($conn, array $materialCodes, $plantId = '') {
        gw_ensure_mrp_shortage_approval_tables($conn);
        $map = array();
        $codes = array_values(array_unique(array_filter(array_map(function ($c) {
            return trim((string)$c);
        }, $materialCodes))));
        if (empty($codes)) {
            return $map;
        }
        $in = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $codes)) . "'";
        $plantClause = '';
        $plant = trim((string)$plantId);
        if ($plant !== '') {
            $plantClause = " AND TRIM(IFNULL(plant_id,'')) = '" . $conn->real_escape_string($plant) . "'";
        }
        $sql = "SELECT a.*
                FROM mrp_shortage_approval a
                INNER JOIN (
                    SELECT material_code, MAX(id) AS max_id
                    FROM mrp_shortage_approval
                    WHERE material_code IN ($in) $plantClause
                    GROUP BY material_code
                ) x ON a.id = x.max_id";
        $res = @$conn->query($sql);
        if (!$res) {
            return $map;
        }
        while ($row = $res->fetch_assoc()) {
            $map[$row['material_code']] = $row;
        }
        return $map;
    }
}

if (!function_exists('gw_msa_get_status_for_material')) {
    function gw_msa_get_status_for_material($conn, $materialCode, $plantId = '') {
        $map = gw_msa_latest_by_materials($conn, array($materialCode), $plantId);
        $row = $map[$materialCode] ?? null;
        if (!$row) {
            return array(
                'approval_status' => 'NONE',
                'approval_id' => 0,
                'revision_no' => 0,
                'can_raise_indent' => false,
                'can_submit' => true,
            );
        }
        $status = strtoupper(trim((string)($row['approval_status'] ?? 'NONE')));
        return array(
            'approval_status' => $status,
            'approval_id' => (int)$row['id'],
            'revision_no' => (int)($row['revision_no'] ?? 1),
            'submitted_by' => (string)($row['submitted_by'] ?? ''),
            'submitted_by_name' => (string)($row['submitted_by_name'] ?? ''),
            'submitted_at' => (string)($row['submitted_at'] ?? ''),
            'approved_by' => (string)($row['approved_by'] ?? ''),
            'approved_at' => (string)($row['approved_at'] ?? ''),
            'net_shortage_qty' => floatval($row['net_shortage_qty'] ?? 0),
            'can_raise_indent' => ($status === 'APPROVED'),
            'can_submit' => in_array($status, array('NONE', 'REJECTED', 'CANCELLED'), true) || $status === '',
        );
    }
}

if (!function_exists('gw_submit_mrp_shortage_approval')) {
    function gw_submit_mrp_shortage_approval($conn, $input, $ctx = array()) {
        gw_ensure_mrp_shortage_approval_tables($conn);
        $materialCode = trim((string)($input['material_code'] ?? ''));
        if ($materialCode === '') {
            return array('status' => 'error', 'message' => 'material_code is required');
        }

        $plantId = trim((string)($ctx['plant_id'] ?? ($input['plant_id'] ?? '')));
        $empId = trim((string)($ctx['emp_id'] ?? ''));
        $dept = trim((string)($ctx['department'] ?? ''));
        $empName = gw_msa_emp_display_name($conn, $empId);

        $latest = gw_msa_get_status_for_material($conn, $materialCode, $plantId);
        if (!empty($latest['approval_id']) && strtoupper($latest['approval_status']) === 'PENDING') {
            return array(
                'status' => 'error',
                'message' => 'A pending shortage approval already exists for this material.',
                'approval_id' => (int)$latest['approval_id'],
            );
        }
        if (!empty($latest['approval_id']) && strtoupper($latest['approval_status']) === 'APPROVED') {
            return array(
                'status' => 'error',
                'message' => 'Shortage already approved. Raise indent or cancel/revise before re-submitting.',
                'approval_id' => (int)$latest['approval_id'],
            );
        }

        $revision = 1;
        if (!empty($latest['revision_no'])) {
            $revision = (int)$latest['revision_no'] + 1;
        }

        $lines = is_array($input['lines'] ?? null) ? $input['lines'] : array();
        $netShortage = floatval($input['net_shortage_qty'] ?? $input['total_shortage'] ?? 0);
        if ($netShortage <= 0 && count($lines)) {
            foreach ($lines as $ln) {
                $netShortage += floatval($ln['shortage_qty'] ?? $ln['required_qty'] ?? 0);
            }
        }
        if ($netShortage <= 0) {
            return array('status' => 'error', 'message' => 'Net shortage quantity must be greater than zero');
        }

        // Enrich stock snapshot from shared availability helper when present.
        $avail = array();
        if (function_exists('gw_get_mrp_material_availability_detail')) {
            $avail = gw_get_mrp_material_availability_detail($conn, $materialCode, array(
                'plant_id' => $plantId,
                'required_qty' => $netShortage,
            ));
        }

        $esc = function ($v) use ($conn) {
            return $conn->real_escape_string((string)($v ?? ''));
        };
        $num = function ($v) {
            return number_format(max(0, floatval($v)), 4, '.', '');
        };

        $materialName = trim((string)($input['material_name'] ?? ($avail['material_name'] ?? '')));
        $materialType = trim((string)($input['material_type'] ?? ($input['mat_type'] ?? ($avail['material_type'] ?? ''))));
        $motherCode = trim((string)($input['mother_code'] ?? ($input['MotherCode'] ?? ($avail['mother_code'] ?? ''))));
        $uom = trim((string)($input['uom'] ?? ($input['Matunit'] ?? ($avail['uom'] ?? ''))));
        $clientCode = trim((string)($input['client_code'] ?? ''));
        $clientName = trim((string)($input['client_name'] ?? ''));
        $remark = trim((string)($input['remark'] ?? $input['submitted_remark'] ?? ''));
        $woCount = (int)($input['wo_count'] ?? count($lines));

        $availableStore = floatval($input['available_store_qty'] ?? ($avail['available_store_qty'] ?? 0));
        $reserved = floatval($input['reserved_qty'] ?? ($avail['reserved_qty'] ?? 0));
        $underTest = floatval($input['under_test_qty'] ?? ($avail['under_test_qty'] ?? 0));
        $openPo = floatval($input['open_po_qty'] ?? ($avail['open_po_qty'] ?? 0));
        $transit = floatval($input['transit_po_qty'] ?? ($avail['transit_po_qty'] ?? 0));
        $openIndent = floatval($input['open_indent_qty'] ?? ($avail['open_indent_qty'] ?? 0));

        $snapshot = array(
            'input' => $input,
            'availability' => $avail,
            'submitted_at' => date('Y-m-d H:i:s'),
        );
        $snapshotJson = $esc(json_encode($snapshot));

        $sql = "INSERT INTO mrp_shortage_approval (
                    plant_id, material_code, material_name, material_type, mother_code, uom,
                    client_code, client_name, required_qty, available_store_qty, reserved_qty,
                    under_test_qty, open_po_qty, transit_po_qty, open_indent_qty, net_shortage_qty,
                    wo_count, revision_no, approval_status, submitted_by, submitted_by_name,
                    submitted_at, submitted_remark, snapshot_json, updated_at
                ) VALUES (
                    '".$esc($plantId)."', '".$esc($materialCode)."', '".$esc($materialName)."', '".$esc($materialType)."',
                    '".$esc($motherCode)."', '".$esc($uom)."', '".$esc($clientCode)."', '".$esc($clientName)."',
                    '".$num($netShortage)."', '".$num($availableStore)."', '".$num($reserved)."',
                    '".$num($underTest)."', '".$num($openPo)."', '".$num($transit)."', '".$num($openIndent)."', '".$num($netShortage)."',
                    $woCount, $revision, 'PENDING', '".$esc($empId)."', '".$esc($empName)."',
                    NOW(), '".$esc($remark)."', '$snapshotJson', NOW()
                )";
        if (!$conn->query($sql)) {
            return array('status' => 'error', 'message' => 'Failed to submit shortage approval: ' . $conn->error);
        }
        $approvalId = (int)$conn->insert_id;

        foreach ($lines as $ln) {
            if (!is_array($ln)) {
                continue;
            }
            $woDedId = (int)($ln['wo_deduction_id'] ?? $ln['id'] ?? 0);
            $conn->query(
                "INSERT INTO mrp_shortage_approval_line (
                    approval_id, wo_deduction_id, workorder_no, order_no, product_code, product_name,
                    client_code, client_name, required_qty, shortage_qty, reserved_qty
                 ) VALUES (
                    $approvalId, $woDedId,
                    '".$esc($ln['workorder_no'] ?? '')."',
                    '".$esc($ln['order_no'] ?? $ln['factory_order_no'] ?? '')."',
                    '".$esc($ln['product_code'] ?? '')."',
                    '".$esc($ln['product_name'] ?? '')."',
                    '".$esc($ln['client_code'] ?? '')."',
                    '".$esc($ln['client_name'] ?? '')."',
                    '".$num($ln['required_qty'] ?? 0)."',
                    '".$num($ln['shortage_qty'] ?? $ln['rm_shortage'] ?? 0)."',
                    '".$num($ln['reserved_qty'] ?? 0)."'
                 )"
            );
        }

        gw_msa_log(
            $conn,
            $approvalId,
            $materialCode,
            'SUBMIT',
            (string)($latest['approval_status'] ?? 'NONE'),
            'PENDING',
            $revision,
            $empId,
            $empName,
            $dept,
            $remark,
            array('net_shortage_qty' => $netShortage, 'wo_count' => $woCount)
        );

        if (function_exists('gw_mrp_audit_log')) {
            gw_mrp_audit_log($conn, array(
                'stage' => 'SHORTAGE_APPROVAL_SUBMIT',
                'material_code' => $materialCode,
                'material_name' => $materialName,
                'shortage_qty' => $netShortage,
                'emp_id' => $empId,
                'remark' => $remark,
                'extra' => array('approval_id' => $approvalId, 'revision_no' => $revision),
            ));
        }

        return array(
            'status' => 'success',
            'message' => 'Shortage submitted for approval',
            'approval_id' => $approvalId,
            'revision_no' => $revision,
            'approval_status' => 'PENDING',
        );
    }
}

if (!function_exists('gw_action_mrp_shortage_approval')) {
    function gw_action_mrp_shortage_approval($conn, $input, $ctx = array()) {
        gw_ensure_mrp_shortage_approval_tables($conn);
        $approvalId = (int)($input['approval_id'] ?? $input['id'] ?? 0);
        $action = strtoupper(trim((string)($input['action'] ?? '')));
        $remark = trim((string)($input['remark'] ?? $input['approval_remark'] ?? $input['rejection_remark'] ?? ''));
        $empId = trim((string)($ctx['emp_id'] ?? ''));
        $dept = trim((string)($ctx['department'] ?? ''));
        $empName = gw_msa_emp_display_name($conn, $empId);

        if ($approvalId <= 0) {
            return array('status' => 'error', 'message' => 'approval_id is required');
        }
        if (!in_array($action, array('APPROVE', 'REJECT', 'CANCEL'), true)) {
            return array('status' => 'error', 'message' => 'action must be APPROVE, REJECT or CANCEL');
        }

        $res = $conn->query("SELECT * FROM mrp_shortage_approval WHERE id = $approvalId LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return array('status' => 'error', 'message' => 'Approval record not found');
        }
        $row = $res->fetch_assoc();
        $from = strtoupper(trim((string)($row['approval_status'] ?? '')));
        if ($from !== 'PENDING' && $action !== 'CANCEL') {
            return array('status' => 'error', 'message' => 'Only pending approvals can be approved/rejected');
        }

        // Segregation of duties: submitter cannot approve/reject own submission.
        if (in_array($action, array('APPROVE', 'REJECT'), true)
            && $empId !== ''
            && strcasecmp((string)($row['submitted_by'] ?? ''), $empId) === 0) {
            return array(
                'status' => 'error',
                'message' => 'You cannot approve or reject a shortage you submitted (segregation of duties).',
            );
        }

        $esc = function ($v) use ($conn) {
            return $conn->real_escape_string((string)($v ?? ''));
        };

        if ($action === 'APPROVE') {
            $to = 'APPROVED';
            $sql = "UPDATE mrp_shortage_approval SET
                        approval_status='APPROVED',
                        approved_by='".$esc($empId)."',
                        approved_by_name='".$esc($empName)."',
                        approved_at=NOW(),
                        approval_remark='".$esc($remark)."',
                        updated_at=NOW()
                    WHERE id=$approvalId AND approval_status='PENDING'";
        } elseif ($action === 'REJECT') {
            $to = 'REJECTED';
            if ($remark === '') {
                return array('status' => 'error', 'message' => 'Rejection remark is required');
            }
            $sql = "UPDATE mrp_shortage_approval SET
                        approval_status='REJECTED',
                        rejected_by='".$esc($empId)."',
                        rejected_by_name='".$esc($empName)."',
                        rejected_at=NOW(),
                        rejection_remark='".$esc($remark)."',
                        updated_at=NOW()
                    WHERE id=$approvalId AND approval_status='PENDING'";
        } else {
            $to = 'CANCELLED';
            $sql = "UPDATE mrp_shortage_approval SET
                        approval_status='CANCELLED',
                        updated_at=NOW(),
                        submitted_remark=CONCAT(IFNULL(submitted_remark,''), ' | Cancelled: ".$esc($remark)."')
                    WHERE id=$approvalId AND approval_status IN ('PENDING','APPROVED')";
        }

        if (!$conn->query($sql) || $conn->affected_rows < 1) {
            return array('status' => 'error', 'message' => 'Failed to update approval status');
        }

        gw_msa_log(
            $conn,
            $approvalId,
            $row['material_code'] ?? '',
            $action,
            $from,
            $to,
            (int)($row['revision_no'] ?? 1),
            $empId,
            $empName,
            $dept,
            $remark
        );

        if (function_exists('gw_mrp_audit_log')) {
            gw_mrp_audit_log($conn, array(
                'stage' => 'SHORTAGE_APPROVAL_' . $action,
                'material_code' => $row['material_code'] ?? '',
                'material_name' => $row['material_name'] ?? '',
                'shortage_qty' => $row['net_shortage_qty'] ?? null,
                'emp_id' => $empId,
                'remark' => $remark,
                'extra' => array('approval_id' => $approvalId, 'from' => $from, 'to' => $to),
            ));
        }

        return array(
            'status' => 'success',
            'message' => 'Shortage approval ' . strtolower($to),
            'approval_id' => $approvalId,
            'approval_status' => $to,
        );
    }
}

if (!function_exists('gw_list_mrp_shortage_approvals')) {
    function gw_list_mrp_shortage_approvals($conn, $filters = array()) {
        gw_ensure_mrp_shortage_approval_tables($conn);
        $where = array('1=1');
        if (!empty($filters['status'])) {
            $st = strtoupper(trim((string)$filters['status']));
            if ($st !== 'ALL') {
                $where[] = "approval_status = '" . $conn->real_escape_string($st) . "'";
            }
        }
        if (!empty($filters['plant_id'])) {
            $where[] = "TRIM(IFNULL(plant_id,'')) = '" . $conn->real_escape_string(trim((string)$filters['plant_id'])) . "'";
        }
        if (!empty($filters['material_code'])) {
            $where[] = "material_code = '" . $conn->real_escape_string(trim((string)$filters['material_code'])) . "'";
        }
        $sql = "SELECT * FROM mrp_shortage_approval
                WHERE " . implode(' AND ', $where) . "
                ORDER BY id DESC
                LIMIT 300";
        $out = array();
        $res = @$conn->query($sql);
        if (!$res) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $aid = (int)$row['id'];
            $lines = array();
            $lr = @$conn->query("SELECT * FROM mrp_shortage_approval_line WHERE approval_id=$aid ORDER BY id ASC");
            if ($lr) {
                while ($ln = $lr->fetch_assoc()) {
                    $lines[] = $ln;
                }
            }
            $row['lines'] = $lines;
            $out[] = $row;
        }
        return $out;
    }
}
