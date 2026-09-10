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

    function decodeJsonField($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value ?? '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    function formatDateDisplay($value)
    {
        if (empty($value) || $value === '0000-00-00') {
            return '';
        }
        return date('d-m-Y', strtotime($value));
    }

    function dateFilterSql($fromDate, $toDate, $column)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE($column) BETWEEN '$fromDate' AND '$toDate'";
        }
        return '';
    }

    function getEmpName($conn, $empId)
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
        return $empName;
    }

    function formHeaderHtml($title, $formNo, $sopRef = 'SOP-QA-023', $effectiveDate = 'APR 16 2025')
    {
        return '<h3 style="text-align:center;">' . htmlspecialchars($title) . '</h3>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:15%;"><b>FORM NO.:</b></td>
                <td style="width:35%;">' . htmlspecialchars($formNo) . '</td>
                <td style="width:15%;"><b>REF:</b></td>
                <td style="width:35%;">' . htmlspecialchars($sopRef) . '</td>
            </tr>
            <tr>
                <td><b>REVISION NO.:</b></td>
                <td>00</td>
                <td><b>EFFECTIVE DATE:</b></td>
                <td>' . htmlspecialchars($effectiveDate) . '</td>
            </tr>
        </table><br>';
    }

    function destructionCriteria()
    {
        return [
            'All expired, short-dated and outdated products',
            'Soiled, damaged product',
            'Partial units',
            'Incomplete packaging space (missing cartons, inserts)',
            'Products stored in improper conditions in customer storage area or during shipping',
        ];
    }

    function returnStockCriteria()
    {
        return [
            'Refused Shipments',
            'Items in unopened cases',
            'High cost/demand units in unopened cases',
        ];
    }

    function salvagingCriteria()
    {
        return [
            'Laboratory tests show product meets identity, strength, quality and purity',
            'Inspection of premises: drug product and packaging not compromised in improper storage conditions',
            'Accumulate returns into BIO-MED waste shippers. BIO-MED disposes waste shippers',
        ];
    }

    function ensureReturnMerchandiseTable($conn)
    {
        $conn->query("CREATE TABLE IF NOT EXISTS return_merchandise_report (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-023-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-023',
            return_merchandise_no VARCHAR(30) DEFAULT NULL,
            received_date DATE DEFAULT NULL,
            customer_name VARCHAR(255) DEFAULT NULL,
            customer_address VARCHAR(500) DEFAULT NULL,
            product_description VARCHAR(255) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_id VARCHAR(50) DEFAULT NULL,
            lot_number VARCHAR(100) DEFAULT NULL,
            expiry_date DATE DEFAULT NULL,
            po_number VARCHAR(100) DEFAULT NULL,
            original_po_date DATE DEFAULT NULL,
            invoice_number VARCHAR(100) DEFAULT NULL,
            invoice_date DATE DEFAULT NULL,
            credit_number VARCHAR(100) DEFAULT NULL,
            credit_date DATE DEFAULT NULL,
            narcotic_type VARCHAR(20) DEFAULT NULL,
            qty_received VARCHAR(50) DEFAULT NULL,
            inspected_by VARCHAR(255) DEFAULT NULL,
            comments_receiving TEXT DEFAULT NULL,
            comments_qa TEXT DEFAULT NULL,
            assessment_checklist LONGTEXT DEFAULT NULL,
            disposition VARCHAR(50) DEFAULT NULL,
            qa_initial VARCHAR(255) DEFAULT NULL,
            qa_initial_date DATE DEFAULT NULL,
            director_designate VARCHAR(255) DEFAULT NULL,
            director_date DATE DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Pending QA',
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            qa_reviewed_by VARCHAR(100) DEFAULT NULL,
            qa_reviewed_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function formatReturnRow($row)
    {
        $row['assessment_checklist'] = decodeJsonField($row['assessment_checklist'] ?? '{}');
        return $row;
    }

    function generateReturnMerchandiseNo($conn, $plantId)
    {
        $year = date('y');
        $prefix = 'R-';
        $suffix = '/' . $year;
        $sql = "SELECT return_merchandise_no FROM return_merchandise_report
                WHERE plant_id='" . esc($conn, $plantId) . "'
                AND return_merchandise_no LIKE 'R-%" . esc($conn, $suffix) . "'
                ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $nextSeq = 1;
        if ($result && $result->num_rows > 0) {
            $lastNo = $result->fetch_assoc()['return_merchandise_no'];
            if (preg_match('/R-(\d{3})\/' . preg_quote($year, '/') . '$/', $lastNo, $m)) {
                $nextSeq = intval($m[1]) + 1;
            }
        }
        return $prefix . str_pad((string)$nextSeq, 3, '0', STR_PAD_LEFT) . $suffix;
    }

    function buildCriteriaHtml($title, $criteria, $selected)
    {
        $html = '<p style="font-weight:bold;">' . htmlspecialchars($title) . '</p><ul style="font-size:8px;">';
        foreach ($criteria as $index => $label) {
            $mark = !empty($selected[$index]) ? '[X]' : '[ ]';
            $html .= '<li>' . $mark . ' ' . htmlspecialchars($label) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    function buildReportFormHtml($row)
    {
        $checklist = is_array($row['assessment_checklist']) ? $row['assessment_checklist'] : [];
        $destruction = decodeJsonField($checklist['destruction'] ?? []);
        $returnStock = decodeJsonField($checklist['return_to_stock'] ?? []);
        $salvaging = decodeJsonField($checklist['salvaging'] ?? []);

        $html = formHeaderHtml('RETURN OF MERCHANDISE REPORT FORM', $row['form_no'] ?? 'FQA-023-A', $row['sop_ref'] ?? 'SOP-QA-023');
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:8px;">
            <tr><td><b>Return of Merchandise #:</b></td><td>' . htmlspecialchars($row['return_merchandise_no'] ?? '') . '</td></tr>
            <tr><td><b>Received Date:</b></td><td>' . formatDateDisplay($row['received_date'] ?? '') . '</td>
                <td><b>Inspected by:</b></td><td>' . htmlspecialchars($row['inspected_by'] ?? '') . '</td></tr>
            <tr><td><b>Customer Name:</b></td><td>' . htmlspecialchars($row['customer_name'] ?? '') . '</td>
                <td><b>Product Code:</b></td><td>' . htmlspecialchars($row['product_code'] ?? '') . '</td></tr>
            <tr><td><b>Address:</b></td><td colspan="3">' . htmlspecialchars($row['customer_address'] ?? '') . '</td></tr>
            <tr><td><b>Product Description/Strength:</b></td><td>' . htmlspecialchars($row['product_description'] ?? '') . '</td>
                <td><b>Expiry Date:</b></td><td>' . formatDateDisplay($row['expiry_date'] ?? '') . '</td></tr>
            <tr><td><b>Lot Number:</b></td><td>' . htmlspecialchars($row['lot_number'] ?? '') . '</td>
                <td><b>Original Date of P.O.:</b></td><td>' . formatDateDisplay($row['original_po_date'] ?? '') . '</td></tr>
            <tr><td><b>P.O Number:</b></td><td>' . htmlspecialchars($row['po_number'] ?? '') . '</td>
                <td><b>Invoice Date:</b></td><td>' . formatDateDisplay($row['invoice_date'] ?? '') . '</td></tr>
            <tr><td><b>Invoice Number:</b></td><td>' . htmlspecialchars($row['invoice_number'] ?? '') . '</td>
                <td><b>Credit Date:</b></td><td>' . formatDateDisplay($row['credit_date'] ?? '') . '</td></tr>
            <tr><td><b>Credit Number:</b></td><td>' . htmlspecialchars($row['credit_number'] ?? '') . '</td>
                <td><b>Narcotic / Non-Narcotic:</b></td><td>' . htmlspecialchars($row['narcotic_type'] ?? '') . '</td></tr>
            <tr><td><b>QTY Received:</b></td><td colspan="3">' . htmlspecialchars($row['qty_received'] ?? '') . '</td></tr>
            <tr><td><b>Comments - Receiving:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['comments_receiving'] ?? '')) . '</td></tr>
            <tr><td><b>Comments - QA:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['comments_qa'] ?? '')) . '</td></tr>
        </table><br>';

        $html .= buildCriteriaHtml('If Goods for Destruction', destructionCriteria(), $destruction);
        $html .= buildCriteriaHtml('If Goods for Return to Stock Consideration', returnStockCriteria(), $returnStock);
        $html .= buildCriteriaHtml('If Goods are for Return to Salvaging Operations', salvagingCriteria(), $salvaging);

        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:8px;">
            <tr><td><b>Disposition:</b></td><td>' . htmlspecialchars($row['disposition'] ?? '') . '</td></tr>
            <tr><td><b>QA Initial / Date:</b></td><td>' . htmlspecialchars($row['qa_initial'] ?? '') . ' / ' . formatDateDisplay($row['qa_initial_date'] ?? '') . '</td></tr>
            <tr><td><b>Director/Designate / Date:</b></td><td>' . htmlspecialchars($row['director_designate'] ?? '') . ' / ' . formatDateDisplay($row['director_date'] ?? '') . '</td></tr>
        </table>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose</p>';
        return $html;
    }

    function buildMerchandiseLogHtml($rows)
    {
        $html = formHeaderHtml('RETURN OF MERCHANDISE LOG', 'FQA-023-B');
        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:7px;table-layout:fixed;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td rowspan="2" style="width:10%;">Return of Merchandise # R-XXX/YY</td>
                <td rowspan="2" style="width:16%;">Name of Product / Strength</td>
                <td rowspan="2" style="width:10%;">Lot Number</td>
                <td rowspan="2" style="width:8%;">Quantity</td>
                <td rowspan="2" style="width:16%;">Reason for Return</td>
                <td colspan="2" style="width:14%;">Inspected</td>
                <td rowspan="2" style="width:16%;">Comments</td>
                <td rowspan="2" style="width:10%;">Disposition</td>
            </tr>
            <tr style="background-color:#EEE;font-weight:bold;text-align:center;font-size:6px;">
                <td style="width:7%;">By</td>
                <td style="width:7%;">Date</td>
            </tr>';

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . htmlspecialchars($row['return_merchandise_no'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['product_description'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['lot_number'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['qty_received'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['comments_receiving'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['inspected_by'] ?? '') . '</td>
                    <td style="text-align:center;">' . formatDateDisplay($row['received_date'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['comments_qa'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['disposition'] ?? $row['status'] ?? '') . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose</p>';
        return $html;
    }

    ensureReturnMerchandiseTable($conn);

    if ($_GET['type'] == 'saveReturnMerchandiseReport') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $productDesc = trim($input['product_description'] ?? '');
        $lotNumber = trim($input['lot_number'] ?? '');
        if ($productDesc === '' || $lotNumber === '') {
            echo json_encode(['status' => 'Product and Lot Number are required']);
            exit;
        }

        $returnNo = generateReturnMerchandiseNo($conn, $_GET['plant_id']);
        $checklist = isset($input['assessment_checklist']) ? json_encode($input['assessment_checklist']) : '{}';

        $sql = "INSERT INTO return_merchandise_report (
            plant_id, form_no, sop_ref, return_merchandise_no, received_date, customer_name, customer_address,
            product_description, product_code, product_id, lot_number, expiry_date, po_number, original_po_date,
            invoice_number, invoice_date, credit_number, credit_date, narcotic_type, qty_received, inspected_by,
            comments_receiving, comments_qa, assessment_checklist, disposition, qa_initial, qa_initial_date,
            director_designate, director_date, status, entry_by, entry_date
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-023-A') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-023') . "',
            '$returnNo',
            " . (empty($input['received_date']) ? 'NULL' : "'" . esc($conn, $input['received_date']) . "'") . ",
            '" . esc($conn, $input['customer_name'] ?? '') . "',
            '" . esc($conn, $input['customer_address'] ?? '') . "',
            '" . esc($conn, $productDesc) . "',
            '" . esc($conn, $input['product_code'] ?? '') . "',
            '" . esc($conn, $input['product_id'] ?? '') . "',
            '" . esc($conn, $lotNumber) . "',
            " . (empty($input['expiry_date']) ? 'NULL' : "'" . esc($conn, $input['expiry_date']) . "'") . ",
            '" . esc($conn, $input['po_number'] ?? '') . "',
            " . (empty($input['original_po_date']) ? 'NULL' : "'" . esc($conn, $input['original_po_date']) . "'") . ",
            '" . esc($conn, $input['invoice_number'] ?? '') . "',
            " . (empty($input['invoice_date']) ? 'NULL' : "'" . esc($conn, $input['invoice_date']) . "'") . ",
            '" . esc($conn, $input['credit_number'] ?? '') . "',
            " . (empty($input['credit_date']) ? 'NULL' : "'" . esc($conn, $input['credit_date']) . "'") . ",
            '" . esc($conn, $input['narcotic_type'] ?? '') . "',
            '" . esc($conn, $input['qty_received'] ?? '') . "',
            '" . esc($conn, $input['inspected_by'] ?? '') . "',
            '" . esc($conn, $input['comments_receiving'] ?? '') . "',
            '" . esc($conn, $input['comments_qa'] ?? '') . "',
            '" . esc($conn, $checklist) . "',
            NULL, NULL, NULL, NULL, NULL,
            'Pending QA',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'return_merchandise_no' => $returnNo]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'completeReturnMerchandiseQaReview') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $id = intval($input['id'] ?? 0);
        $disposition = esc($conn, $input['disposition'] ?? '');
        if ($id <= 0 || $disposition === '') {
            echo json_encode(['status' => 'Disposition is required']);
            exit;
        }

        $checkSql = "SELECT status FROM return_merchandise_report WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        if ($checkResult->fetch_assoc()['status'] !== 'Pending QA') {
            echo json_encode(['status' => 'Record is not pending QA review']);
            exit;
        }

        $checklist = isset($input['assessment_checklist']) ? json_encode($input['assessment_checklist']) : '{}';
        $qaName = esc($conn, getEmpName($conn, $_GET['emp_id']));
        $today = date('Y-m-d');

        $sql = "UPDATE return_merchandise_report SET
            comments_qa='" . esc($conn, $input['comments_qa'] ?? '') . "',
            assessment_checklist='" . esc($conn, $checklist) . "',
            disposition='$disposition',
            qa_initial='$qaName',
            qa_initial_date='$today',
            director_designate='" . esc($conn, $input['director_designate'] ?? '') . "',
            director_date=" . (empty($input['director_date']) ? 'NULL' : "'" . esc($conn, $input['director_date']) . "'") . ",
            status='Completed',
            qa_reviewed_by='" . esc($conn, $_GET['emp_id']) . "',
            qa_reviewed_date='$entry_date'
            WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getReturnMerchandiseReportLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'received_date');

        $sql = "SELECT id, return_merchandise_no, received_date, customer_name, product_description, product_code,
                       lot_number, qty_received, narcotic_type, status, disposition, entry_by, entry_date
                FROM return_merchandise_report
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getReturnMerchandiseLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'received_date');

        $sql = "SELECT id, return_merchandise_no, product_description, lot_number, qty_received,
                       comments_receiving, comments_qa, inspected_by, received_date, disposition, status
                FROM return_merchandise_report
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND status='Completed' $filter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getPendingReturnMerchandiseQa') {
        $output = [];
        $sql = "SELECT id, return_merchandise_no, received_date, product_description, lot_number, qty_received, customer_name
                FROM return_merchandise_report
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Pending QA'
                ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getReturnMerchandiseById') {
        $output = [];
        $sql = "SELECT * FROM return_merchandise_report
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatReturnRow($result->fetch_assoc());
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadReturnMerchandiseReportForm') {
        $_GET['filename'] = 'Return of Merchandise Report Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM return_merchandise_report WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = formatReturnRow($result->fetch_assoc());
            $pdf->writeHTML(buildReportFormHtml($row), true, false, true, false, '');
            $pdf->Output('Return_Merchandise_Report_' . ($row['return_merchandise_no'] ?: $row['id']) . '.pdf', 'I');
        }
    } else if ($_GET['type'] == 'downloadReturnMerchandiseLog') {
        $_GET['filename'] = 'Return of Merchandise Log';
        $_GET['pdftype'] = 'landscape';
        $_GET['pdffonts'] = 8;
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'received_date');
        $rows = [];
        $sql = "SELECT * FROM return_merchandise_report
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Completed' $filter
                ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $pdf->writeHTML(buildMerchandiseLogHtml($rows), true, false, true, false, '');
        $pdf->Output('Return_Merchandise_Log.pdf', 'I');
    }
}
