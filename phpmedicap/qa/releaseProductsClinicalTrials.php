<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
$entry_date = date('Y-m-d H:i:s');

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    $txt = '{"process":"FRONTEND","token":"' . $token . '","action":"' . ($_GET['type'] ?? '') . '","actiontime":"' . $entry_date . '","department":"' . $_GET['department'] . '","emp_id":"' . $_GET['emp_id'] . '","method":"' . $_SERVER['REQUEST_METHOD'] . '","REMOTE_ADDR":"' . $_SERVER['REMOTE_ADDR'] . '"}';
    file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    function esc($conn, $value)
    {
        return $conn->real_escape_string($value ?? '');
    }

    function rpctEffectiveDept($tokenDept)
    {
        $override = trim($_GET['dep_name'] ?? '');
        if ($override !== '') {
            return $override;
        }
        return $tokenDept;
    }

    function rpctIsRegulatoryDept($d)
    {
        return (
            strpos($d, 'regulatory') !== false ||
            $d === 'ra' ||
            strpos($d, 'ra ') === 0 ||
            substr($d, -3) === ' ra' ||
            strpos($d, ' dra') !== false ||
            strpos($d, 'dra ') === 0 ||
            $d === 'dra' ||
            strpos($d, 'regulatory affairs') !== false ||
            strpos($d, 'regulatory department') !== false ||
            strpos($d, 'regulatory officer') !== false
        );
    }

    function rpctDeptRoles($dept)
    {
        $d = strtolower(trim($dept ?? ''));
        $roles = ['viewer'];
        if (rpctIsRegulatoryDept($d)) {
            $roles[] = 'regulatory_affairs';
        }
        if (
            strpos($d, 'quality assurance') !== false ||
            strpos($d, 'qa &') !== false ||
            strpos($d, 'qa and') !== false ||
            preg_match('/\bqa\b/', $d) ||
            (strpos($d, 'compliance') !== false && strpos($d, 'regulatory') === false)
        ) {
            $roles[] = 'qa_compliance';
        }
        if (strpos($d, 'material management') !== false || strpos($d, 'warehouse') !== false || $d === 'security' || strpos($d, 'day store') !== false || $d === 'store' || (strpos($d, 'material') !== false && strpos($d, 'information') === false)) {
            $roles[] = 'material_management';
        }
        if (strpos($d, 'project management') !== false || strpos($d, 'project') !== false || strpos($d, 'marketing') !== false) {
            $roles[] = 'project_management';
        }
        return array_values(array_unique($roles));
    }

    function rpctAssertDeptAction($dept, $step)
    {
        $roles = rpctDeptRoles($dept);
        $required = [
            'new_request' => ['regulatory_affairs'],
            'qa_warehouse' => ['qa_compliance'],
            'warehouse_issue' => ['material_management'],
            'pm_shipment' => ['project_management'],
            'offsite_release' => ['qa_compliance'],
        ];
        if (!isset($required[$step])) {
            return '';
        }
        foreach ($required[$step] as $role) {
            if (in_array($role, $roles, true)) {
                return '';
            }
        }
        $labels = [
            'new_request' => 'Regulatory Affairs',
            'qa_warehouse' => 'QA & Compliance',
            'warehouse_issue' => 'Material Management',
            'pm_shipment' => 'Project Management',
            'offsite_release' => 'QA & Compliance',
        ];
        return 'This action is only for ' . ($labels[$step] ?? 'authorized department');
    }

    function rpctPrimaryRole($dept)
    {
        $roles = rpctDeptRoles($dept);
        if (in_array('regulatory_affairs', $roles, true)) return 'regulatory_affairs';
        if (in_array('qa_compliance', $roles, true)) return 'qa_compliance';
        if (in_array('material_management', $roles, true)) return 'material_management';
        if (in_array('project_management', $roles, true)) return 'project_management';
        return 'viewer';
    }

    function rpctNextActionDept($status)
    {
        switch ($status) {
            case 'Submitted':
            case 'active':
                return 'QA & Compliance';
            case 'QA Requested to Warehouse':
                return 'Material Management';
            case 'Units Issued to PM':
                return 'Project Management';
            case 'Shipped to Clinical Facility':
                return 'Completed';
            default:
                return '';
        }
    }

    function rpctEnrichRow($row)
    {
        if (($row['status'] ?? '') === 'active') {
            $row['status'] = 'Submitted';
        }
        $row['next_action_dept'] = rpctNextActionDept($row['status']);
        return $row;
    }

    function rpctMyPendingForDept($conn, $plantId, $dept)
    {
        $primary = rpctPrimaryRole($dept);
        $count = 0;
        if ($primary === 'qa_compliance') {
            $r = $conn->query("SELECT COUNT(*) AS c FROM sample_withdrawal_request WHERE plant_id='" . esc($conn, $plantId) . "' AND status IN ('Submitted','active')");
            $count += intval($r->fetch_assoc()['c']);
        }
        if ($primary === 'material_management') {
            $r = $conn->query("SELECT COUNT(*) AS c FROM sample_withdrawal_request WHERE plant_id='" . esc($conn, $plantId) . "' AND status='QA Requested to Warehouse'");
            $count += intval($r->fetch_assoc()['c']);
        }
        if ($primary === 'project_management') {
            $r = $conn->query("SELECT COUNT(*) AS c FROM sample_withdrawal_request WHERE plant_id='" . esc($conn, $plantId) . "' AND status='Units Issued to PM'");
            $count += intval($r->fetch_assoc()['c']);
        }
        return $count;
    }

    function ensureSampleWithdrawalTables($conn)
    {
        $headerSql = "CREATE TABLE IF NOT EXISTS sample_withdrawal_request (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-046-A',
            product_name VARCHAR(255) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_id VARCHAR(100) DEFAULT NULL,
            product_lot VARCHAR(100) DEFAULT NULL,
            request_date DATE DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($headerSql);
        $conn->query("ALTER TABLE sample_withdrawal_request ADD COLUMN IF NOT EXISTS product_id VARCHAR(100) DEFAULT NULL AFTER product_code");
        $alterCols = [
            "qa_requested_by VARCHAR(255) DEFAULT NULL",
            "qa_request_date DATE DEFAULT NULL",
            "qa_remarks TEXT DEFAULT NULL",
            "units_removed VARCHAR(50) DEFAULT NULL",
            "shippers_sealed VARCHAR(10) DEFAULT NULL",
            "warehouse_handoff_by VARCHAR(255) DEFAULT NULL",
            "warehouse_handoff_date DATE DEFAULT NULL",
            "warehouse_remarks TEXT DEFAULT NULL",
            "pm_received_by VARCHAR(255) DEFAULT NULL",
            "pm_received_date DATE DEFAULT NULL",
            "withdrawal_log_ref VARCHAR(100) DEFAULT NULL",
            "shipment_coordinated_by VARCHAR(255) DEFAULT NULL",
            "shipment_date DATE DEFAULT NULL",
            "clinical_facility VARCHAR(255) DEFAULT NULL",
            "pm_remarks TEXT DEFAULT NULL",
            "submitted_dept VARCHAR(255) DEFAULT NULL",
        ];
        foreach ($alterCols as $col) {
            $conn->query("ALTER TABLE sample_withdrawal_request ADD COLUMN IF NOT EXISTS $col");
        }

        $offsiteSql = "CREATE TABLE IF NOT EXISTS clinical_trial_offsite_release (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_lot VARCHAR(100) DEFAULT NULL,
            manufacturer VARCHAR(255) DEFAULT NULL,
            coa_received_date DATE DEFAULT NULL,
            coa_reviewed_by VARCHAR(255) DEFAULT NULL,
            qa_informed_date DATE DEFAULT NULL,
            documentation_received_date DATE DEFAULT NULL,
            qc_sample_requested_date DATE DEFAULT NULL,
            qc_id_test_completed_date DATE DEFAULT NULL,
            stability_commitment VARCHAR(10) DEFAULT 'Yes',
            batch_docs_received VARCHAR(10) DEFAULT 'Yes',
            shipment_arrival_date DATE DEFAULT NULL,
            qa_release_by VARCHAR(255) DEFAULT NULL,
            qa_release_date DATE DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Released',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($offsiteSql);

        $lineSql = "CREATE TABLE IF NOT EXISTS sample_withdrawal_request_line (
            id INT(11) NOT NULL AUTO_INCREMENT,
            request_id INT(11) NOT NULL,
            line_no INT(11) DEFAULT 1,
            unit_package_size VARCHAR(255) DEFAULT NULL,
            dosage_strength_form VARCHAR(255) DEFAULT NULL,
            number_of_units VARCHAR(50) DEFAULT NULL,
            requested_by VARCHAR(255) DEFAULT NULL,
            date_requested DATE DEFAULT NULL,
            initials VARCHAR(50) DEFAULT NULL,
            received_by VARCHAR(255) DEFAULT NULL,
            received_date DATE DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_request_id (request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $conn->query($lineSql);
    }

    function getSampleWithdrawalLines($conn, $requestId)
    {
        $lines = [];
        $sql = "SELECT * FROM sample_withdrawal_request_line
                WHERE request_id='" . esc($conn, $requestId) . "'
                ORDER BY line_no ASC, id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lines[] = $row;
            }
        }
        return $lines;
    }

    function validateSampleWithdrawalLines($lines)
    {
        if (!is_array($lines) || count($lines) === 0) {
            return 'At least one Regulatory Affairs line is required';
        }

        foreach ($lines as $idx => $line) {
            if (!is_array($line)) {
                return 'Invalid line item at row ' . ($idx + 1);
            }
            if (empty(trim($line['unit_package_size'] ?? ''))) {
                return 'Unit Package Size is required at row ' . ($idx + 1);
            }
            if (empty(trim($line['dosage_strength_form'] ?? ''))) {
                return 'Dosage Strength / Form is required at row ' . ($idx + 1);
            }
            $units = trim($line['number_of_units'] ?? '');
            if ($units === '' || !preg_match('/^\d+$/', $units) || intval($units) <= 0) {
                return 'Number of Units must be a whole number greater than 0 at row ' . ($idx + 1);
            }
            if (empty(trim($line['requested_by'] ?? ''))) {
                return 'Requested By is required at row ' . ($idx + 1);
            }
            if (empty($line['date_requested'])) {
                return 'Date Requested is required at row ' . ($idx + 1);
            }
            $receivedBy = trim($line['received_by'] ?? '');
            $receivedDate = trim($line['received_date'] ?? '');
            if ($receivedBy !== '' && $receivedDate === '') {
                return 'Received Date is required when Received By is filled at row ' . ($idx + 1);
            }
            if ($receivedDate !== '' && $receivedBy === '') {
                return 'Received By is required when Received Date is filled at row ' . ($idx + 1);
            }
        }
        return '';
    }

    ensureSampleWithdrawalTables($conn);

    $rpctDept = rpctEffectiveDept($_GET['department']);

    if ($_GET['type'] == 'saveSampleWithdrawalRequest') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        if (empty($input['product_name']) || empty($input['product_lot']) || empty($input['request_date'])) {
            echo json_encode(['status' => 'Product Name, Product Lot # and Date are required']);
            exit;
        }

        $deny = rpctAssertDeptAction($rpctDept, 'new_request');
        if ($deny !== '') {
            echo json_encode(['status' => $deny]);
            exit;
        }

        $lines = $input['lines'] ?? [];
        $lineError = validateSampleWithdrawalLines($lines);
        if ($lineError !== '') {
            echo json_encode(['status' => $lineError]);
            exit;
        }

        $requestDate = $input['request_date'];
        if ($requestDate > date('Y-m-d')) {
            echo json_encode(['status' => 'Request date cannot be in the future']);
            exit;
        }

        $headerSql = "INSERT INTO sample_withdrawal_request (
            plant_id, form_no, product_name, product_code, product_id, product_lot,
            request_date, submitted_dept, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-046-A') . "',
            '" . esc($conn, $input['product_name']) . "',
            '" . esc($conn, $input['product_code']) . "',
            '" . esc($conn, $input['product_id'] ?? '') . "',
            '" . esc($conn, $input['product_lot']) . "',
            '" . esc($conn, $requestDate) . "',
            '" . esc($conn, $rpctDept) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'Submitted'
        )";

        if (!$conn->query($headerSql)) {
            echo json_encode(['status' => $conn->error]);
            exit;
        }

        $requestId = $conn->insert_id;
        $lineNo = 1;
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }
            $dateRequested = !empty($line['date_requested']) ? "'" . esc($conn, $line['date_requested']) . "'" : 'NULL';
            $receivedDate = !empty($line['received_date']) ? "'" . esc($conn, $line['received_date']) . "'" : 'NULL';

            $lineSql = "INSERT INTO sample_withdrawal_request_line (
                request_id, line_no, unit_package_size, dosage_strength_form, number_of_units,
                requested_by, date_requested, initials, received_by, received_date
            ) VALUES (
                '$requestId',
                '$lineNo',
                '" . esc($conn, $line['unit_package_size']) . "',
                '" . esc($conn, $line['dosage_strength_form']) . "',
                '" . esc($conn, $line['number_of_units']) . "',
                '" . esc($conn, $line['requested_by']) . "',
                $dateRequested,
                '" . esc($conn, $line['initials']) . "',
                '" . esc($conn, $line['received_by']) . "',
                $receivedDate
            )";
            $conn->query($lineSql);
            $lineNo++;
        }

        echo json_encode(['status' => 'success', 'id' => $requestId]);
    } else if ($_GET['type'] == 'getSampleWithdrawalLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';

        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(r.request_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT r.*,
                (SELECT COUNT(*) FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS line_count,
                (SELECT COALESCE(SUM(CAST(l.number_of_units AS UNSIGNED)), 0)
                 FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS total_units
                FROM sample_withdrawal_request r
                WHERE r.plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY r.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = rpctEnrichRow($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getSampleWithdrawalById') {
        $output = [];
        $sql = "SELECT * FROM sample_withdrawal_request
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = rpctEnrichRow($result->fetch_assoc());
            $output['lines'] = getSampleWithdrawalLines($conn, $output['id']);
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadSampleWithdrawalLog') {
        $_GET['filename'] = 'Sample Withdrawal Request Log';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(r.request_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $html = '<h3 style="text-align:center;">SAMPLE WITHDRAWAL REQUEST LOG</h3>
        <p style="text-align:center;font-size:10px;">SOP-QA-046 | Form No.: FQA-046-A</p>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:9px;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:10%;">Date</td>
                <td style="width:22%;">Product Name</td>
                <td style="width:12%;">Product Code</td>
                <td style="width:12%;">Product Lot #</td>
                <td style="width:8%;">Lines</td>
                <td style="width:10%;">Total Units</td>
                <td style="width:12%;">Entry By</td>
            </tr>';

        $sql = "SELECT r.*,
                (SELECT COUNT(*) FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS line_count,
                (SELECT COALESCE(SUM(CAST(l.number_of_units AS UNSIGNED)), 0)
                 FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS total_units
                FROM sample_withdrawal_request r
                WHERE r.plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY r.request_date ASC, r.id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . (!empty($row['request_date']) ? date('d-m-Y', strtotime($row['request_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['product_name']) . '</td>
                    <td>' . htmlspecialchars($row['product_code']) . '</td>
                    <td>' . htmlspecialchars($row['product_lot']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['line_count']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['total_units']) . '</td>
                    <td>' . htmlspecialchars($row['entry_by']) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Sample_Withdrawal_Request_Log.pdf', 'I');
    } else if ($_GET['type'] == 'downloadSampleWithdrawalForm') {
        $_GET['filename'] = 'Sample Withdrawal Request Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM sample_withdrawal_request
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lines = getSampleWithdrawalLines($conn, $row['id']);

            $html = '<h3 style="text-align:center;">SAMPLE WITHDRAWAL REQUEST FORM</h3>
            <p style="text-align:center;font-size:10px;">Ref: SOP-QA-046 | Form No.: FQA-046-A | Revision: 00 | Effective: APR 16 2025</p>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:10px;">
                <tr>
                    <td style="width:18%;"><b>Product Name</b></td>
                    <td style="width:32%;">' . htmlspecialchars($row['product_name']) . '</td>
                    <td style="width:18%;"><b>Product Code</b></td>
                    <td style="width:32%;">' . htmlspecialchars($row['product_code']) . '</td>
                </tr>
                <tr>
                    <td><b>Product Lot #</b></td>
                    <td>' . htmlspecialchars($row['product_lot']) . '</td>
                    <td><b>Date</b></td>
                    <td>' . (!empty($row['request_date']) ? date('d-m-Y', strtotime($row['request_date'])) : '') . '</td>
                </tr>
            </table>
            <p style="font-size:9px;margin-top:6px;"><b>Note:</b> The following information will be completed by the Regulatory Affairs:</p>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:9px;">
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                    <td>Unit Package Size</td>
                    <td>Dosage Strength / Form</td>
                    <td>Number of Units</td>
                    <td>Requested By</td>
                    <td>Date Requested</td>
                    <td>Initials</td>
                    <td>Received By</td>
                    <td>Date</td>
                </tr>';

            if (count($lines) > 0) {
                foreach ($lines as $line) {
                    $html .= '<tr nobr="true">
                        <td>' . htmlspecialchars($line['unit_package_size']) . '</td>
                        <td>' . htmlspecialchars($line['dosage_strength_form']) . '</td>
                        <td style="text-align:center;">' . htmlspecialchars($line['number_of_units']) . '</td>
                        <td>' . htmlspecialchars($line['requested_by']) . '</td>
                        <td style="text-align:center;">' . (!empty($line['date_requested']) ? date('d-m-Y', strtotime($line['date_requested'])) : '') . '</td>
                        <td style="text-align:center;">' . htmlspecialchars($line['initials']) . '</td>
                        <td>' . htmlspecialchars($line['received_by']) . '</td>
                        <td style="text-align:center;">' . (!empty($line['received_date']) ? date('d-m-Y', strtotime($line['received_date'])) : '') . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="8" style="text-align:center;">No line items</td></tr>';
            }
            $html .= '</table>
            <p style="font-size:8px;font-style:italic;text-align:center;margin-top:8px;">Confidential Information: Do Not Disclose</p>';

            $pdf->writeHTML($html, true, false, false, false, '');
        }
        $pdf->Output('Sample_Withdrawal_Request_Form.pdf', 'I');
    } else if ($_GET['type'] == 'getWorkflowPendingCounts') {
        $plant = esc($conn, $_GET['plant_id']);
        $primary = rpctPrimaryRole($rpctDept);
        $qa = 0;
        $wh = 0;
        $pm = 0;

        $r1 = $conn->query("SELECT COUNT(*) AS c FROM sample_withdrawal_request WHERE plant_id='$plant' AND status IN ('Submitted','active')");
        if ($r1) { $qa = intval($r1->fetch_assoc()['c']); }
        $r2 = $conn->query("SELECT COUNT(*) AS c FROM sample_withdrawal_request WHERE plant_id='$plant' AND status='QA Requested to Warehouse'");
        if ($r2) { $wh = intval($r2->fetch_assoc()['c']); }
        $r3 = $conn->query("SELECT COUNT(*) AS c FROM sample_withdrawal_request WHERE plant_id='$plant' AND status='Units Issued to PM'");
        if ($r3) { $pm = intval($r3->fetch_assoc()['c']); }

        echo json_encode([
            'qa_pending' => $primary === 'qa_compliance' ? $qa : 0,
            'warehouse_pending' => $primary === 'material_management' ? $wh : 0,
            'pm_pending' => $primary === 'project_management' ? $pm : 0,
            'my_pending' => rpctMyPendingForDept($conn, $_GET['plant_id'], $rpctDept),
        ]);
    } else if ($_GET['type'] == 'getPendingQaWarehouseRequests') {
        $deny = rpctAssertDeptAction($rpctDept, 'qa_warehouse');
        if ($deny !== '') {
            echo json_encode(['error' => $deny, 'results' => []]);
            exit;
        }
        $output = [];
        $sql = "SELECT r.*,
                (SELECT COALESCE(SUM(CAST(l.number_of_units AS UNSIGNED)), 0)
                 FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS total_units
                FROM sample_withdrawal_request r
                WHERE r.plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND r.status IN ('Submitted','active')
                ORDER BY r.id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = rpctEnrichRow($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'saveQaWarehouseRequest') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $deny = rpctAssertDeptAction($rpctDept, 'qa_warehouse');
        if ($deny !== '') {
            echo json_encode(['status' => $deny]);
            exit;
        }
        $id = intval($input['id']);
        $sql = "UPDATE sample_withdrawal_request SET
            status='QA Requested to Warehouse',
            qa_requested_by='" . esc($conn, $input['qa_requested_by']) . "',
            qa_request_date=" . (empty($input['qa_request_date']) ? 'NULL' : "'" . esc($conn, $input['qa_request_date']) . "'") . ",
            qa_remarks='" . esc($conn, $input['qa_remarks']) . "'
            WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'
            AND status IN ('Submitted','active')";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'Record not found or already processed']);
        }
    } else if ($_GET['type'] == 'getPendingWarehouseIssueRequests') {
        $deny = rpctAssertDeptAction($rpctDept, 'warehouse_issue');
        if ($deny !== '') {
            echo json_encode(['error' => $deny, 'results' => []]);
            exit;
        }
        $output = [];
        $sql = "SELECT r.*,
                (SELECT COALESCE(SUM(CAST(l.number_of_units AS UNSIGNED)), 0)
                 FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS total_units
                FROM sample_withdrawal_request r
                WHERE r.plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND r.status='QA Requested to Warehouse'
                ORDER BY r.qa_request_date ASC, r.id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = rpctEnrichRow($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'saveWarehouseIssue') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $deny = rpctAssertDeptAction($rpctDept, 'warehouse_issue');
        if ($deny !== '') {
            echo json_encode(['status' => $deny]);
            exit;
        }
        $units = trim($input['units_removed'] ?? '');
        if ($units === '' || !preg_match('/^\d+$/', $units) || intval($units) <= 0) {
            echo json_encode(['status' => 'Units removed must be a positive whole number']);
            exit;
        }
        $id = intval($input['id']);
        $sql = "UPDATE sample_withdrawal_request SET
            status='Units Issued to PM',
            units_removed='$units',
            shippers_sealed='" . esc($conn, $input['shippers_sealed'] ?? 'Yes') . "',
            warehouse_handoff_by='" . esc($conn, $input['warehouse_handoff_by']) . "',
            warehouse_handoff_date=" . (empty($input['warehouse_handoff_date']) ? 'NULL' : "'" . esc($conn, $input['warehouse_handoff_date']) . "'") . ",
            warehouse_remarks='" . esc($conn, $input['warehouse_remarks']) . "'
            WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'
            AND status='QA Requested to Warehouse'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'Record not found or already processed']);
        }
    } else if ($_GET['type'] == 'getPendingPmShipmentRequests') {
        $deny = rpctAssertDeptAction($rpctDept, 'pm_shipment');
        if ($deny !== '') {
            echo json_encode(['error' => $deny, 'results' => []]);
            exit;
        }
        $output = [];
        $sql = "SELECT r.*,
                (SELECT COALESCE(SUM(CAST(l.number_of_units AS UNSIGNED)), 0)
                 FROM sample_withdrawal_request_line l WHERE l.request_id = r.id) AS total_units
                FROM sample_withdrawal_request r
                WHERE r.plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND r.status='Units Issued to PM'
                ORDER BY r.warehouse_handoff_date ASC, r.id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = rpctEnrichRow($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'savePmShipment') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $deny = rpctAssertDeptAction($rpctDept, 'pm_shipment');
        if ($deny !== '') {
            echo json_encode(['status' => $deny]);
            exit;
        }
        if (empty($input['withdrawal_log_ref']) || empty($input['clinical_facility'])) {
            echo json_encode(['status' => 'Withdrawal log reference and clinical facility are required']);
            exit;
        }
        $id = intval($input['id']);
        $sql = "UPDATE sample_withdrawal_request SET
            status='Shipped to Clinical Facility',
            pm_received_by='" . esc($conn, $input['pm_received_by']) . "',
            pm_received_date=" . (empty($input['pm_received_date']) ? 'NULL' : "'" . esc($conn, $input['pm_received_date']) . "'") . ",
            withdrawal_log_ref='" . esc($conn, $input['withdrawal_log_ref']) . "',
            shipment_coordinated_by='" . esc($conn, $input['shipment_coordinated_by']) . "',
            shipment_date=" . (empty($input['shipment_date']) ? 'NULL' : "'" . esc($conn, $input['shipment_date']) . "'") . ",
            clinical_facility='" . esc($conn, $input['clinical_facility']) . "',
            pm_remarks='" . esc($conn, $input['pm_remarks']) . "'
            WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'
            AND status='Units Issued to PM'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'Record not found or already processed']);
        }
    } else if ($_GET['type'] == 'getProcessFlowLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(r.request_date) BETWEEN '$fromDate' AND '$toDate'";
        }
        $sql = "SELECT r.* FROM sample_withdrawal_request r
                WHERE r.plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY r.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = rpctEnrichRow($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'saveOffsiteRelease') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['product_name']) || empty($input['product_lot'])) {
            echo json_encode(['status' => 'Product Name and Lot # are required']);
            exit;
        }
        $deny = rpctAssertDeptAction($rpctDept, 'offsite_release');
        if ($deny !== '') {
            echo json_encode(['status' => $deny]);
            exit;
        }
        if (empty($input['coa_reviewed_by']) || empty($input['qc_id_test_completed_date']) || empty($input['qa_release_by'])) {
            echo json_encode(['status' => 'COA reviewed by, QC ID test date and QA release by are required']);
            exit;
        }
        $sql = "INSERT INTO clinical_trial_offsite_release (
            plant_id, product_name, product_code, product_lot, manufacturer,
            coa_received_date, coa_reviewed_by, qa_informed_date, documentation_received_date,
            qc_sample_requested_date, qc_id_test_completed_date, stability_commitment, batch_docs_received,
            shipment_arrival_date, qa_release_by, qa_release_date, remarks, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['product_name']) . "',
            '" . esc($conn, $input['product_code']) . "',
            '" . esc($conn, $input['product_lot']) . "',
            '" . esc($conn, $input['manufacturer']) . "',
            " . (empty($input['coa_received_date']) ? 'NULL' : "'" . esc($conn, $input['coa_received_date']) . "'") . ",
            '" . esc($conn, $input['coa_reviewed_by']) . "',
            " . (empty($input['qa_informed_date']) ? 'NULL' : "'" . esc($conn, $input['qa_informed_date']) . "'") . ",
            " . (empty($input['documentation_received_date']) ? 'NULL' : "'" . esc($conn, $input['documentation_received_date']) . "'") . ",
            " . (empty($input['qc_sample_requested_date']) ? 'NULL' : "'" . esc($conn, $input['qc_sample_requested_date']) . "'") . ",
            '" . esc($conn, $input['qc_id_test_completed_date']) . "',
            '" . esc($conn, $input['stability_commitment'] ?? 'Yes') . "',
            '" . esc($conn, $input['batch_docs_received'] ?? 'Yes') . "',
            " . (empty($input['shipment_arrival_date']) ? 'NULL' : "'" . esc($conn, $input['shipment_arrival_date']) . "'") . ",
            '" . esc($conn, $input['qa_release_by']) . "',
            " . (empty($input['qa_release_date']) ? 'NULL' : "'" . esc($conn, $input['qa_release_date']) . "'") . ",
            '" . esc($conn, $input['remarks']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'Released'
        )";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getOffsiteReleaseLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(qa_release_date) BETWEEN '$fromDate' AND '$toDate'";
        }
        $sql = "SELECT * FROM clinical_trial_offsite_release
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}
