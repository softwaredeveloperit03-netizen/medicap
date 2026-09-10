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

    function getEmpStamp($conn, $empId)
    {
        $empName = $empId;
        $nameSql = "SELECT firstname, lastname FROM employee WHERE emp_id='" . esc($conn, $empId) . "' LIMIT 1";
        $nameResult = $conn->query($nameSql);
        if ($nameResult && $nameResult->num_rows > 0) {
            $empRow = $nameResult->fetch_assoc();
            $fullName = trim(($empRow['firstname'] ?? '') . ' ' . ($empRow['lastname'] ?? ''));
            if ($fullName !== '') {
                $empName = $fullName;
            }
        }
        return $empName . ' (' . $empId . ') - ' . date('d-m-Y H:i');
    }

    function dateFilterSql($fromDate, $toDate, $column)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE($column) BETWEEN '$fromDate' AND '$toDate'";
        }
        return '';
    }

    function formHeaderHtml($title, $formNo)
    {
        return '<h3 style="text-align:center;">' . htmlspecialchars($title) . '</h3>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:15%;"><b>FORM NO.:</b></td>
                <td style="width:35%;">' . htmlspecialchars($formNo) . '</td>
                <td style="width:15%;"><b>REF:</b></td>
                <td style="width:35%;">SOP-QA-008</td>
            </tr>
            <tr>
                <td><b>REVISION NO.:</b></td>
                <td>00</td>
                <td><b>EFFECTIVE DATE:</b></td>
                <td>APR 16 2025</td>
            </tr>
        </table><br>';
    }

    function ensureRetentionTables($conn)
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS retention_receipt_storage (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-008-A',
                record_date DATE DEFAULT NULL,
                product_name VARCHAR(255) DEFAULT NULL,
                product_code VARCHAR(100) DEFAULT NULL,
                batch_no VARCHAR(100) DEFAULT NULL,
                mfg_date DATE DEFAULT NULL,
                exp_date DATE DEFAULT NULL,
                pack_size VARCHAR(100) DEFAULT NULL,
                no_of_packs VARCHAR(50) DEFAULT NULL,
                quantity_retained VARCHAR(50) DEFAULT NULL,
                unit VARCHAR(50) DEFAULT NULL,
                storage_condition VARCHAR(255) DEFAULT NULL,
                storage_location VARCHAR(255) DEFAULT NULL,
                rack_shelf_no VARCHAR(100) DEFAULT NULL,
                label_details VARCHAR(255) DEFAULT NULL,
                received_by VARCHAR(255) DEFAULT NULL,
                qa_verified_by VARCHAR(255) DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS retention_periodic_review (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-008-B',
                review_date DATE DEFAULT NULL,
                product_name VARCHAR(255) DEFAULT NULL,
                product_code VARCHAR(100) DEFAULT NULL,
                batch_no VARCHAR(100) DEFAULT NULL,
                storage_location VARCHAR(255) DEFAULT NULL,
                rack_shelf_no VARCHAR(100) DEFAULT NULL,
                quantity_on_hand VARCHAR(50) DEFAULT NULL,
                unit VARCHAR(50) DEFAULT NULL,
                pack_condition VARCHAR(50) DEFAULT NULL,
                label_condition VARCHAR(50) DEFAULT NULL,
                seal_intact VARCHAR(20) DEFAULT NULL,
                storage_condition_maintained VARCHAR(20) DEFAULT NULL,
                action_taken TEXT DEFAULT NULL,
                comments TEXT DEFAULT NULL,
                reviewed_by VARCHAR(255) DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS retention_disposal (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-008-C',
                disposal_date DATE DEFAULT NULL,
                product_name VARCHAR(255) DEFAULT NULL,
                product_code VARCHAR(100) DEFAULT NULL,
                batch_no VARCHAR(100) DEFAULT NULL,
                mfg_date DATE DEFAULT NULL,
                exp_date DATE DEFAULT NULL,
                quantity_disposed VARCHAR(50) DEFAULT NULL,
                unit VARCHAR(50) DEFAULT NULL,
                retention_period_completed VARCHAR(20) DEFAULT NULL,
                disposal_method VARCHAR(100) DEFAULT NULL,
                reason_for_disposal TEXT DEFAULT NULL,
                witnessed_by VARCHAR(255) DEFAULT NULL,
                approved_by_qa VARCHAR(255) DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($tables as $tableSql) {
            $conn->query($tableSql);
        }
    }

    function nullableDateSql($conn, $value)
    {
        return !empty($value) ? "'" . esc($conn, $value) . "'" : 'NULL';
    }

    ensureRetentionTables($conn);

    $type = $_GET['type'] ?? '';

    if ($type === 'saveRetentionReceipt') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $sql = "INSERT INTO retention_receipt_storage (
            plant_id, form_no, record_date, product_name, product_code, batch_no, mfg_date, exp_date,
            pack_size, no_of_packs, quantity_retained, unit, storage_condition, storage_location,
            rack_shelf_no, label_details, received_by, remarks, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "', 'FQA-008-A',
            '" . esc($conn, $input['record_date'] ?? date('Y-m-d')) . "',
            '" . esc($conn, $input['product_name']) . "',
            '" . esc($conn, $input['product_code']) . "',
            '" . esc($conn, $input['batch_no']) . "',
            " . nullableDateSql($conn, $input['mfg_date'] ?? '') . ",
            " . nullableDateSql($conn, $input['exp_date'] ?? '') . ",
            '" . esc($conn, $input['pack_size']) . "',
            '" . esc($conn, $input['no_of_packs']) . "',
            '" . esc($conn, $input['quantity_retained']) . "',
            '" . esc($conn, $input['unit']) . "',
            '" . esc($conn, $input['storage_condition']) . "',
            '" . esc($conn, $input['storage_location']) . "',
            '" . esc($conn, $input['rack_shelf_no']) . "',
            '" . esc($conn, $input['label_details']) . "',
            '" . esc($conn, $input['received_by']) . "',
            '" . esc($conn, $input['remarks']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date', 'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getRetentionReceiptLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'record_date');
        $sql = "SELECT * FROM retention_receipt_storage WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateRetentionReceiptQaVerified') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT qa_verified_by FROM retention_receipt_storage WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        if (!empty($existing['qa_verified_by'])) {
            echo json_encode(['status' => 'already_stamped', 'qa_verified_by' => $existing['qa_verified_by']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $updateSql = "UPDATE retention_receipt_storage SET qa_verified_by='" . esc($conn, $stamp) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        echo $conn->query($updateSql)
            ? json_encode(['status' => 'success', 'qa_verified_by' => $stamp])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'downloadRetentionReceiptForm') {
        $_GET['filename'] = 'Retention Sample Receipt and Storage';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM retention_receipt_storage WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $html = formHeaderHtml('RETENTION SAMPLE RECEIPT AND STORAGE RECORD', 'FQA-008-A');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>Date</b></td><td>' . (!empty($row['record_date']) ? date('d-m-Y', strtotime($row['record_date'])) : '') . '</td>
                <td style="width:25%;"><b>Product Name</b></td><td>' . htmlspecialchars($row['product_name']) . '</td></tr>
                <tr><td><b>Product Code</b></td><td>' . htmlspecialchars($row['product_code']) . '</td>
                <td><b>Batch/Lot No.</b></td><td>' . htmlspecialchars($row['batch_no']) . '</td></tr>
                <tr><td><b>Mfg. Date</b></td><td>' . (!empty($row['mfg_date']) ? date('d-m-Y', strtotime($row['mfg_date'])) : '') . '</td>
                <td><b>Exp. Date</b></td><td>' . (!empty($row['exp_date']) ? date('d-m-Y', strtotime($row['exp_date'])) : '') . '</td></tr>
                <tr><td><b>Pack Size</b></td><td>' . htmlspecialchars($row['pack_size']) . '</td>
                <td><b>No. of Packs</b></td><td>' . htmlspecialchars($row['no_of_packs']) . '</td></tr>
                <tr><td><b>Quantity Retained</b></td><td>' . htmlspecialchars($row['quantity_retained']) . ' ' . htmlspecialchars($row['unit']) . '</td>
                <td><b>Storage Condition</b></td><td>' . htmlspecialchars($row['storage_condition']) . '</td></tr>
                <tr><td><b>Storage Location</b></td><td>' . htmlspecialchars($row['storage_location']) . '</td>
                <td><b>Rack / Shelf No.</b></td><td>' . htmlspecialchars($row['rack_shelf_no']) . '</td></tr>
                <tr><td><b>Received By</b></td><td>' . htmlspecialchars($row['received_by']) . '</td>
                <td><b>QA Verified By</b></td><td>' . htmlspecialchars($row['qa_verified_by']) . '</td></tr>
                <tr><td><b>Remarks</b></td><td colspan="3">' . htmlspecialchars($row['remarks']) . '</td></tr>
            </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Retention_Receipt_Storage.pdf', 'I');
        }
    } else if ($type === 'saveRetentionReview') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $sql = "INSERT INTO retention_periodic_review (
            plant_id, form_no, review_date, product_name, product_code, batch_no, storage_location,
            rack_shelf_no, quantity_on_hand, unit, pack_condition, label_condition, seal_intact,
            storage_condition_maintained, action_taken, comments, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "', 'FQA-008-B',
            '" . esc($conn, $input['review_date'] ?? date('Y-m-d')) . "',
            '" . esc($conn, $input['product_name']) . "',
            '" . esc($conn, $input['product_code']) . "',
            '" . esc($conn, $input['batch_no']) . "',
            '" . esc($conn, $input['storage_location']) . "',
            '" . esc($conn, $input['rack_shelf_no']) . "',
            '" . esc($conn, $input['quantity_on_hand']) . "',
            '" . esc($conn, $input['unit']) . "',
            '" . esc($conn, $input['pack_condition']) . "',
            '" . esc($conn, $input['label_condition']) . "',
            '" . esc($conn, $input['seal_intact']) . "',
            '" . esc($conn, $input['storage_condition_maintained']) . "',
            '" . esc($conn, $input['action_taken']) . "',
            '" . esc($conn, $input['comments']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date', 'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getRetentionReviewLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'review_date');
        $sql = "SELECT * FROM retention_periodic_review WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateRetentionReviewStamp') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT reviewed_by FROM retention_periodic_review WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        if (!empty($existing['reviewed_by'])) {
            echo json_encode(['status' => 'already_stamped', 'reviewed_by' => $existing['reviewed_by']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $updateSql = "UPDATE retention_periodic_review SET reviewed_by='" . esc($conn, $stamp) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        echo $conn->query($updateSql)
            ? json_encode(['status' => 'success', 'reviewed_by' => $stamp])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'downloadRetentionReviewForm') {
        $_GET['filename'] = 'Retention Sample Periodic Review';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM retention_periodic_review WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $html = formHeaderHtml('RETENTION SAMPLE PERIODIC REVIEW RECORD', 'FQA-008-B');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>Review Date</b></td><td>' . (!empty($row['review_date']) ? date('d-m-Y', strtotime($row['review_date'])) : '') . '</td>
                <td style="width:25%;"><b>Product Name</b></td><td>' . htmlspecialchars($row['product_name']) . '</td></tr>
                <tr><td><b>Batch/Lot No.</b></td><td>' . htmlspecialchars($row['batch_no']) . '</td>
                <td><b>Qty on Hand</b></td><td>' . htmlspecialchars($row['quantity_on_hand']) . ' ' . htmlspecialchars($row['unit']) . '</td></tr>
                <tr><td><b>Pack Condition</b></td><td>' . htmlspecialchars($row['pack_condition']) . '</td>
                <td><b>Label Condition</b></td><td>' . htmlspecialchars($row['label_condition']) . '</td></tr>
                <tr><td><b>Seal Intact</b></td><td>' . htmlspecialchars($row['seal_intact']) . '</td>
                <td><b>Storage Maintained</b></td><td>' . htmlspecialchars($row['storage_condition_maintained']) . '</td></tr>
                <tr><td><b>Action Taken</b></td><td colspan="3">' . htmlspecialchars($row['action_taken']) . '</td></tr>
                <tr><td><b>Comments</b></td><td colspan="3">' . htmlspecialchars($row['comments']) . '</td></tr>
                <tr><td><b>Reviewed By</b></td><td colspan="3">' . htmlspecialchars($row['reviewed_by']) . '</td></tr>
            </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Retention_Periodic_Review.pdf', 'I');
        }
    } else if ($type === 'saveRetentionDisposal') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $sql = "INSERT INTO retention_disposal (
            plant_id, form_no, disposal_date, product_name, product_code, batch_no, mfg_date, exp_date,
            quantity_disposed, unit, retention_period_completed, disposal_method, reason_for_disposal,
            witnessed_by, remarks, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "', 'FQA-008-C',
            '" . esc($conn, $input['disposal_date'] ?? date('Y-m-d')) . "',
            '" . esc($conn, $input['product_name']) . "',
            '" . esc($conn, $input['product_code']) . "',
            '" . esc($conn, $input['batch_no']) . "',
            " . nullableDateSql($conn, $input['mfg_date'] ?? '') . ",
            " . nullableDateSql($conn, $input['exp_date'] ?? '') . ",
            '" . esc($conn, $input['quantity_disposed']) . "',
            '" . esc($conn, $input['unit']) . "',
            '" . esc($conn, $input['retention_period_completed']) . "',
            '" . esc($conn, $input['disposal_method']) . "',
            '" . esc($conn, $input['reason_for_disposal']) . "',
            '" . esc($conn, $input['witnessed_by']) . "',
            '" . esc($conn, $input['remarks']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date', 'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getRetentionDisposalLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'disposal_date');
        $sql = "SELECT * FROM retention_disposal WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateRetentionDisposalApproved') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT approved_by_qa FROM retention_disposal WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        if (!empty($existing['approved_by_qa'])) {
            echo json_encode(['status' => 'already_stamped', 'approved_by_qa' => $existing['approved_by_qa']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $updateSql = "UPDATE retention_disposal SET approved_by_qa='" . esc($conn, $stamp) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        echo $conn->query($updateSql)
            ? json_encode(['status' => 'success', 'approved_by_qa' => $stamp])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'downloadRetentionDisposalForm') {
        $_GET['filename'] = 'Retention Sample Disposal';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM retention_disposal WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $html = formHeaderHtml('RETENTION SAMPLE DISPOSAL RECORD', 'FQA-008-C');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>Disposal Date</b></td><td>' . (!empty($row['disposal_date']) ? date('d-m-Y', strtotime($row['disposal_date'])) : '') . '</td>
                <td style="width:25%;"><b>Product Name</b></td><td>' . htmlspecialchars($row['product_name']) . '</td></tr>
                <tr><td><b>Batch/Lot No.</b></td><td>' . htmlspecialchars($row['batch_no']) . '</td>
                <td><b>Qty Disposed</b></td><td>' . htmlspecialchars($row['quantity_disposed']) . ' ' . htmlspecialchars($row['unit']) . '</td></tr>
                <tr><td><b>Retention Period Completed</b></td><td>' . htmlspecialchars($row['retention_period_completed']) . '</td>
                <td><b>Disposal Method</b></td><td>' . htmlspecialchars($row['disposal_method']) . '</td></tr>
                <tr><td><b>Reason for Disposal</b></td><td colspan="3">' . htmlspecialchars($row['reason_for_disposal']) . '</td></tr>
                <tr><td><b>Witnessed By</b></td><td>' . htmlspecialchars($row['witnessed_by']) . '</td>
                <td><b>Approved By QA</b></td><td>' . htmlspecialchars($row['approved_by_qa']) . '</td></tr>
                <tr><td><b>Remarks</b></td><td colspan="3">' . htmlspecialchars($row['remarks']) . '</td></tr>
            </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Retention_Disposal.pdf', 'I');
        }
    }
}
