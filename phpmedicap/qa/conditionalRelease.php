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

    function getEmpStamp($conn, $empId)
    {
        return getEmpName($conn, $empId) . ' (' . $empId . ') - ' . date('d-m-Y H:i');
    }

    function displayApproverName($value)
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return '';
        }
        if (strpos($value, ' - ') !== false) {
            $value = trim(explode(' - ', $value)[0]);
        }
        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/', $value, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/^(.+?)\s*\([^)]+\)/', $value, $matches)) {
            return trim($matches[1]);
        }
        return $value;
    }

    function pdfStatusShort($row)
    {
        $label = getStatusLabel($row);
        switch ($label) {
            case 'Fully Released':
                return 'Full Release';
            case 'Conditionally Released':
                return 'Conditional';
            case 'Pending QA Head':
                return 'Pending';
            default:
                return $label;
        }
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

    function formHeaderHtml($title, $formNo, $sopRef = 'SOP-QA-020', $effectiveDate = 'APR 09 2025')
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

    function ensureConditionalReleaseTable($conn)
    {
        $conn->query("CREATE TABLE IF NOT EXISTS conditional_release_request (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-020-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-020',
            cr_no VARCHAR(20) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_id VARCHAR(50) DEFAULT NULL,
            lot_no VARCHAR(100) DEFAULT NULL,
            sample_quantity VARCHAR(100) DEFAULT NULL,
            date_required DATE DEFAULT NULL,
            description TEXT DEFAULT NULL,
            justification TEXT DEFAULT NULL,
            requested_by VARCHAR(255) DEFAULT NULL,
            requested_by_date DATE DEFAULT NULL,
            dept_approval_by VARCHAR(255) DEFAULT NULL,
            dept_approval_date DATE DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Pending QA Head',
            proceed_processing VARCHAR(10) DEFAULT NULL,
            qa_approval_by VARCHAR(255) DEFAULT NULL,
            qa_approval_date DATE DEFAULT NULL,
            reject_reason TEXT DEFAULT NULL,
            final_approval_by VARCHAR(255) DEFAULT NULL,
            final_approval_date DATE DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            updated_by VARCHAR(100) DEFAULT NULL,
            updated_date DATETIME DEFAULT NULL,
            resubmit_count INT(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function formatCrRow($row)
    {
        return $row;
    }

    function getStatusLabel($row)
    {
        $status = $row['status'] ?? '';
        if ($status === 'Fully Released' || (!empty($row['final_approval_by']) && in_array($status, ['Approved', 'Fully Released'], true))) {
            return 'Fully Released';
        }
        if ($status === 'Approved') {
            return 'Conditionally Released';
        }
        return $status;
    }

    function formatTrackingLogRow($row)
    {
        $status = $row['status'] ?? '';
        $row['status_label'] = getStatusLabel($row);
        $row['requested_by_display'] = displayApproverName($row['requested_by'] ?? '');
        $row['qa_approval_by_display'] = '';
        $row['qa_approval_date_display'] = formatDateDisplay($row['qa_approval_date'] ?? '');
        $row['final_approval_by_display'] = '';
        $row['final_approval_date_display'] = formatDateDisplay($row['final_approval_date'] ?? '');

        if (in_array($status, ['Approved', 'Fully Released', 'Declined'], true)) {
            $row['qa_approval_by_display'] = displayApproverName($row['qa_approval_by'] ?? '');
        }
        if (!empty($row['final_approval_by'])) {
            $row['final_approval_by_display'] = displayApproverName($row['final_approval_by']);
        }

        if (empty($row['cr_no'])) {
            if ($status === 'Declined') {
                $row['cr_no'] = 'N/A';
            } else {
                $row['cr_no'] = 'Pending';
            }
        }

        $row['can_final_release'] = ($status === 'Approved' && empty($row['final_approval_by']));
        return $row;
    }

    function generateCrNumber($conn, $plantId)
    {
        $year = date('y');
        $prefix = $year . '/';
        $sql = "SELECT cr_no FROM conditional_release_request
                WHERE plant_id='" . esc($conn, $plantId) . "'
                AND cr_no IS NOT NULL AND cr_no != '' AND cr_no != 'N/A'
                AND cr_no LIKE '" . esc($conn, $prefix) . "%'
                ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $nextSeq = 1;
        if ($result && $result->num_rows > 0) {
            $lastCr = $result->fetch_assoc()['cr_no'];
            $parts = explode('/', $lastCr);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextSeq = intval($parts[1]) + 1;
            }
        }
        return $prefix . str_pad((string)$nextSeq, 3, '0', STR_PAD_LEFT);
    }

    function buildRequestFormHtml($row)
    {
        $html = formHeaderHtml('CONDITIONAL RELEASE REQUEST FORM', $row['form_no'], $row['sop_ref']);
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr><td style="width:25%;"><b>CR #:</b></td><td>' . htmlspecialchars($row['cr_no'] ?? '') . '</td></tr>
        </table><br>';

        $html .= '<p><b>1. Request for Conditional Release</b></p>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:25%;"><b>Material/Product Name:</b></td>
                <td style="width:25%;">' . htmlspecialchars($row['product_name'] ?? '') . '</td>
                <td style="width:25%;"><b>Code #:</b></td>
                <td style="width:25%;">' . htmlspecialchars($row['product_code'] ?? '') . '</td>
            </tr>
            <tr>
                <td><b>Lot #:</b></td>
                <td>' . htmlspecialchars($row['lot_no'] ?? '') . '</td>
                <td><b>Sample Quantity:</b></td>
                <td>' . htmlspecialchars($row['sample_quantity'] ?? '') . '</td>
            </tr>
            <tr>
                <td><b>Date Required:</b></td>
                <td colspan="3">' . formatDateDisplay($row['date_required'] ?? '') . '</td>
            </tr>
            <tr>
                <td><b>Description:</b></td>
                <td colspan="3">' . nl2br(htmlspecialchars($row['description'] ?? '')) . '</td>
            </tr>
            <tr>
                <td><b>Justification:</b></td>
                <td colspan="3">' . nl2br(htmlspecialchars($row['justification'] ?? '')) . '</td>
            </tr>
            <tr>
                <td><b>Requested By:</b></td>
                <td>' . htmlspecialchars($row['requested_by'] ?? '') . '</td>
                <td><b>Date:</b></td>
                <td>' . formatDateDisplay($row['requested_by_date'] ?? '') . '</td>
            </tr>
            <tr>
                <td><b>Department Approval:</b></td>
                <td>' . htmlspecialchars($row['dept_approval_by'] ?? '') . '</td>
                <td><b>Date:</b></td>
                <td>' . formatDateDisplay($row['dept_approval_date'] ?? '') . '</td>
            </tr>
        </table><br>';

        $html .= '<p><b>2. QA Approval</b></p>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:35%;"><b>Proceed with Processing:</b></td>
                <td>' . htmlspecialchars($row['proceed_processing'] ?? '') . '</td>
            </tr>
            <tr>
                <td><b>QA Approval:</b></td>
                <td>' . htmlspecialchars($row['qa_approval_by'] ?? '') . ' / ' . formatDateDisplay($row['qa_approval_date'] ?? '') . '</td>
            </tr>';
        if (!empty($row['reject_reason'])) {
            $html .= '<tr><td><b>Rejection Reason:</b></td><td>' . nl2br(htmlspecialchars($row['reject_reason'])) . '</td></tr>';
        }
        $html .= '</table><br>';

        $html .= '<p><b>3. QA Final Approval for Full Release</b></p>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:35%;"><b>QA Approval:</b></td>
                <td>' . htmlspecialchars($row['final_approval_by'] ?? '') . ' / ' . formatDateDisplay($row['final_approval_date'] ?? '') . '</td>
            </tr>
        </table>
        <p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose</p>';
        return $html;
    }

    function buildTrackingLogHtml($rows)
    {
        $html = formHeaderHtml('CONDITIONAL RELEASE TRACKING LOG', 'FQA-020-B');
        $html .= '<table border="1" cellpadding="2" cellspacing="0" style="width:100%;font-size:7px;table-layout:fixed;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td rowspan="2" style="width:6%;">CR#<br/>(YY/XXX)</td>
                <td rowspan="2" style="width:15%;">Material/<br/>Product Name</td>
                <td rowspan="2" style="width:5%;">Code #</td>
                <td rowspan="2" style="width:5%;">Lot #</td>
                <td rowspan="2" style="width:16%;">Description</td>
                <td colspan="2" style="width:15%;">Requested By</td>
                <td colspan="2" style="width:15%;">Approved by</td>
                <td colspan="2" style="width:15%;">Final Approval for<br/>Full Release by</td>
                <td rowspan="2" style="width:8%;">Status</td>
            </tr>
            <tr style="background-color:#EEE;font-weight:bold;text-align:center;font-size:6px;">
                <td style="width:7.5%;">By</td>
                <td style="width:7.5%;">Date</td>
                <td style="width:7.5%;">By</td>
                <td style="width:7.5%;">Date</td>
                <td style="width:7.5%;">By</td>
                <td style="width:7.5%;">Date</td>
            </tr>';

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $formatted = formatTrackingLogRow($row);
                $reqBy = htmlspecialchars($formatted['requested_by_display'] ?? '');
                $qaBy = htmlspecialchars($formatted['qa_approval_by_display'] ?? '');
                $finalBy = htmlspecialchars($formatted['final_approval_by_display'] ?? '');
                $reqDate = htmlspecialchars($formatted['requested_by_date'] ? formatDateDisplay($formatted['requested_by_date']) : '');
                $qaDate = htmlspecialchars($formatted['qa_approval_date_display'] ?? '');
                $finalDate = htmlspecialchars($formatted['final_approval_date_display'] ?? '');
                $status = htmlspecialchars(pdfStatusShort($formatted));
                $desc = htmlspecialchars($formatted['description'] ?? '');
                if (strlen($desc) > 80) {
                    $desc = substr($desc, 0, 77) . '...';
                }

                $html .= '<tr nobr="true">
                    <td style="text-align:center;white-space:nowrap;">' . htmlspecialchars($formatted['cr_no'] ?? '') . '</td>
                    <td style="font-size:6px;">' . htmlspecialchars($formatted['product_name'] ?? '') . '</td>
                    <td style="text-align:center;white-space:nowrap;">' . htmlspecialchars($formatted['product_code'] ?? '') . '</td>
                    <td style="text-align:center;white-space:nowrap;">' . htmlspecialchars($formatted['lot_no'] ?? '') . '</td>
                    <td style="font-size:6px;">' . $desc . '</td>
                    <td style="font-size:6px;">' . $reqBy . '</td>
                    <td style="text-align:center;white-space:nowrap;font-size:6px;">' . $reqDate . '</td>
                    <td style="font-size:6px;">' . $qaBy . '</td>
                    <td style="text-align:center;white-space:nowrap;font-size:6px;">' . $qaDate . '</td>
                    <td style="font-size:6px;">' . $finalBy . '</td>
                    <td style="text-align:center;white-space:nowrap;font-size:6px;">' . $finalDate . '</td>
                    <td style="text-align:center;white-space:nowrap;font-size:6px;">' . $status . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose</p>';
        return $html;
    }

    ensureConditionalReleaseTable($conn);

    if ($_GET['type'] == 'saveConditionalReleaseRequest') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $productName = esc($conn, $input['product_name'] ?? '');
        $lotNo = esc($conn, $input['lot_no'] ?? '');
        if ($productName === '' || $lotNo === '') {
            echo json_encode(['status' => 'Product name and Lot # are required']);
            exit;
        }

        $id = intval($input['id'] ?? 0);
        $isResubmit = $id > 0;

        if ($isResubmit) {
            $checkSql = "SELECT status, entry_by FROM conditional_release_request
                         WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
            $checkResult = $conn->query($checkSql);
            if (!$checkResult || $checkResult->num_rows === 0) {
                echo json_encode(['status' => 'Record not found']);
                exit;
            }
            $existing = $checkResult->fetch_assoc();
            if ($existing['status'] !== 'Rejected') {
                echo json_encode(['status' => 'Only rejected requests can be edited and resubmitted']);
                exit;
            }
            if ($existing['entry_by'] !== $_GET['emp_id']) {
                echo json_encode(['status' => 'Only the original requester can resubmit this request']);
                exit;
            }

            $sql = "UPDATE conditional_release_request SET
                product_name='$productName',
                product_code='" . esc($conn, $input['product_code'] ?? '') . "',
                product_id='" . esc($conn, $input['product_id'] ?? '') . "',
                lot_no='$lotNo',
                sample_quantity='" . esc($conn, $input['sample_quantity'] ?? '') . "',
                date_required=" . (empty($input['date_required']) ? 'NULL' : "'" . esc($conn, $input['date_required']) . "'") . ",
                description='" . esc($conn, $input['description'] ?? '') . "',
                justification='" . esc($conn, $input['justification'] ?? '') . "',
                requested_by='" . esc($conn, $input['requested_by'] ?? '') . "',
                requested_by_date=" . (empty($input['requested_by_date']) ? 'NULL' : "'" . esc($conn, $input['requested_by_date']) . "'") . ",
                dept_approval_by='" . esc($conn, $input['dept_approval_by'] ?? '') . "',
                dept_approval_date=" . (empty($input['dept_approval_date']) ? 'NULL' : "'" . esc($conn, $input['dept_approval_date']) . "'") . ",
                status='Pending QA Head',
                proceed_processing=NULL,
                qa_approval_by=NULL,
                qa_approval_date=NULL,
                reject_reason=NULL,
                final_approval_by=NULL,
                final_approval_date=NULL,
                cr_no=NULL,
                updated_by='" . esc($conn, $_GET['emp_id']) . "',
                updated_date='$entry_date',
                resubmit_count=resubmit_count+1
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";

            if ($conn->query($sql)) {
                echo json_encode(['status' => 'success', 'id' => $id, 'message' => 'Request resubmitted to QA Head']);
            } else {
                echo json_encode(['status' => $conn->error]);
            }
            exit;
        }

        $sql = "INSERT INTO conditional_release_request (
            plant_id, form_no, sop_ref, product_name, product_code, product_id, lot_no,
            sample_quantity, date_required, description, justification,
            requested_by, requested_by_date, dept_approval_by, dept_approval_date,
            status, entry_by, entry_date
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-020-A') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-020') . "',
            '$productName',
            '" . esc($conn, $input['product_code'] ?? '') . "',
            '" . esc($conn, $input['product_id'] ?? '') . "',
            '$lotNo',
            '" . esc($conn, $input['sample_quantity'] ?? '') . "',
            " . (empty($input['date_required']) ? 'NULL' : "'" . esc($conn, $input['date_required']) . "'") . ",
            '" . esc($conn, $input['description'] ?? '') . "',
            '" . esc($conn, $input['justification'] ?? '') . "',
            '" . esc($conn, $input['requested_by'] ?? '') . "',
            " . (empty($input['requested_by_date']) ? 'NULL' : "'" . esc($conn, $input['requested_by_date']) . "'") . ",
            '" . esc($conn, $input['dept_approval_by'] ?? '') . "',
            " . (empty($input['dept_approval_date']) ? 'NULL' : "'" . esc($conn, $input['dept_approval_date']) . "'") . ",
            'Pending QA Head',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getConditionalReleaseRequestLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'entry_date');
        $mineOnly = esc($conn, $_GET['mine_only'] ?? '');

        $mineFilter = '';
        if ($mineOnly === 'Yes') {
            $mineFilter = " AND entry_by='" . esc($conn, $_GET['emp_id']) . "'";
        }

        $sql = "SELECT id, form_no, sop_ref, cr_no, product_name, product_code, lot_no, status,
                       requested_by, requested_by_date, entry_by, entry_date, reject_reason, resubmit_count
                FROM conditional_release_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter $mineFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['status_label'] = getStatusLabel($row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getConditionalReleaseTrackingLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'entry_date');

        $sql = "SELECT id, cr_no, product_name, product_code, lot_no, description,
                       requested_by, requested_by_date, qa_approval_by, qa_approval_date,
                       final_approval_by, final_approval_date, status, reject_reason, proceed_processing
                FROM conditional_release_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = formatTrackingLogRow($row);
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getConditionalReleaseById') {
        $output = [];
        $sql = "SELECT * FROM conditional_release_request
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatTrackingLogRow(formatCrRow($result->fetch_assoc()));
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getPendingConditionalReleaseApprovals') {
        $output = [];
        $sql = "SELECT id, form_no, cr_no, product_name, product_code, lot_no, sample_quantity,
                       date_required, description, justification, requested_by, requested_by_date,
                       dept_approval_by, dept_approval_date, status, entry_by, entry_date, resubmit_count
                FROM conditional_release_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND status='Pending QA Head'
                ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getApprovedConditionalReleaseForFinal') {
        $output = [];
        $sql = "SELECT id, cr_no, product_name, product_code, lot_no, status,
                       qa_approval_by, qa_approval_date, final_approval_by, final_approval_date
                FROM conditional_release_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND status='Approved' AND (final_approval_by IS NULL OR final_approval_by='')
                ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['status_label'] = getStatusLabel($row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'approveConditionalReleaseRequest') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $id = intval($input['id'] ?? 0);
        $proceed = esc($conn, $input['proceed_processing'] ?? 'Yes');
        if ($id <= 0) {
            echo json_encode(['status' => 'Invalid request id']);
            exit;
        }

        $checkSql = "SELECT status FROM conditional_release_request
                     WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        if ($existing['status'] !== 'Pending QA Head') {
            echo json_encode(['status' => 'Request is not pending QA Head approval']);
            exit;
        }

        $approverName = esc($conn, getEmpName($conn, $_GET['emp_id']));
        $today = date('Y-m-d');
        $recordFinal = !empty($input['record_final_release']);

        if ($proceed === 'No') {
            $sql = "UPDATE conditional_release_request SET
                status='Declined',
                proceed_processing='No',
                cr_no='N/A',
                qa_approval_by='$approverName',
                qa_approval_date='$today',
                reject_reason='" . esc($conn, $input['reject_reason'] ?? 'Request declined by QA Head') . "',
                updated_by='" . esc($conn, $_GET['emp_id']) . "',
                updated_date='$entry_date'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        } else {
            $crNo = generateCrNumber($conn, $_GET['plant_id']);
            $finalFields = '';
            $newStatus = 'Approved';
            if ($recordFinal) {
                $newStatus = 'Fully Released';
                $finalFields = ",
                final_approval_by='$approverName',
                final_approval_date='$today'";
            }
            $sql = "UPDATE conditional_release_request SET
                status='$newStatus',
                proceed_processing='Yes',
                cr_no='$crNo',
                qa_approval_by='$approverName',
                qa_approval_date='$today',
                reject_reason=NULL
                $finalFields,
                updated_by='" . esc($conn, $_GET['emp_id']) . "',
                updated_date='$entry_date'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        }

        if ($conn->query($sql)) {
            $crOut = $proceed === 'No' ? 'N/A' : ($crNo ?? '');
            echo json_encode([
                'status' => 'success',
                'cr_no' => $crOut,
                'final_release_recorded' => ($proceed === 'Yes' && $recordFinal) ? 'Yes' : 'No',
            ]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'rejectConditionalReleaseRequest') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $id = intval($input['id'] ?? 0);
        $reason = trim($input['reject_reason'] ?? '');
        if ($id <= 0 || $reason === '') {
            echo json_encode(['status' => 'Rejection reason is required']);
            exit;
        }

        $checkSql = "SELECT status FROM conditional_release_request
                     WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        if ($existing['status'] !== 'Pending QA Head') {
            echo json_encode(['status' => 'Request is not pending QA Head approval']);
            exit;
        }

        $sql = "UPDATE conditional_release_request SET
            status='Rejected',
            proceed_processing='No',
            reject_reason='" . esc($conn, $reason) . "',
            qa_approval_by=NULL,
            qa_approval_date=NULL,
            updated_by='" . esc($conn, $_GET['emp_id']) . "',
            updated_date='$entry_date'
            WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'finalReleaseConditionalRelease') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'Invalid request id']);
            exit;
        }

        $checkSql = "SELECT status, final_approval_by FROM conditional_release_request
                     WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        if ($existing['status'] !== 'Approved') {
            echo json_encode(['status' => 'Only approved conditional releases can receive final release']);
            exit;
        }
        if (!empty($existing['final_approval_by'])) {
            echo json_encode(['status' => 'Final release already recorded']);
            exit;
        }

        $approverName = esc($conn, getEmpName($conn, $_GET['emp_id']));
        $today = date('Y-m-d');
        $sql = "UPDATE conditional_release_request SET
            status='Fully Released',
            final_approval_by='$approverName',
            final_approval_date='$today',
            updated_by='" . esc($conn, $_GET['emp_id']) . "',
            updated_date='$entry_date'
            WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'final_approval_by' => $approverName]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'downloadConditionalReleaseRequestForm') {
        $_GET['filename'] = 'Conditional Release Request Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM conditional_release_request
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = formatCrRow($result->fetch_assoc());
            $pdf->writeHTML(buildRequestFormHtml($row), true, false, true, false, '');
            $pdf->Output('Conditional_Release_Request_' . ($row['cr_no'] ?: $row['id']) . '.pdf', 'I');
        }
    } else if ($_GET['type'] == 'downloadConditionalReleaseTrackingLog') {
        $_GET['filename'] = 'Conditional Release Tracking Log';
        $_GET['pdftype'] = 'landscape';
        $_GET['pdffonts'] = 8;
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'entry_date');
        $rows = [];
        $sql = "SELECT * FROM conditional_release_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter
                ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = formatTrackingLogRow($row);
            }
        }
        $pdf->writeHTML(buildTrackingLogHtml($rows), true, false, true, false, '');
        $pdf->Output('Conditional_Release_Tracking_Log.pdf', 'I');
    }
}
