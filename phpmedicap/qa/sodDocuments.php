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

    function formHeaderHtml($title, $formNo, $sopRef = 'SOP-QA-013', $effectiveDate = 'MAR 24 2025')
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

    function ensureSodTables($conn)
    {
        $conn->query("CREATE TABLE IF NOT EXISTS sod_distribution_reconciliation (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-013-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-013',
            sop_number VARCHAR(100) DEFAULT NULL,
            department_rows LONGTEXT DEFAULT NULL,
            reconciliations LONGTEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS sod_biennial_review_log (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-013-B',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-013',
            sop_number VARCHAR(100) DEFAULT NULL,
            sop_title VARCHAR(255) DEFAULT NULL,
            issued_by_qa_initial_date VARCHAR(255) DEFAULT NULL,
            review_rows LONGTEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function formatDistributionRow($row)
    {
        $row['department_rows'] = decodeJsonField($row['department_rows'] ?? '[]');
        $row['reconciliations'] = decodeJsonField($row['reconciliations'] ?? '[]');
        return $row;
    }

    function formatBiennialRow($row)
    {
        $row['review_rows'] = decodeJsonField($row['review_rows'] ?? '[]');
        return $row;
    }

    function buildDistributionHtml($row)
    {
        $departments = $row['department_rows'];
        $reconciliations = $row['reconciliations'];
        $html = formHeaderHtml('SOP DISTRIBUTION AND RECONCILIATION FORM', $row['form_no'], $row['sop_ref']);
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr><td style="width:20%;"><b>SOP #:</b></td><td>' . htmlspecialchars($row['sop_number']) . '</td></tr>
        </table><br>';

        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:8px;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td rowspan="2" style="width:12%;">Department</td>
                <td colspan="4">Rev</td><td colspan="4">Rev</td><td colspan="4">Rev</td><td colspan="4">Rev</td><td colspan="4">Rev</td>
            </tr>
            <tr style="background-color:#EEE;font-weight:bold;text-align:center;">
                <td>E.D.</td><td>Dist.</td><td>Recov.</td><td>CC#</td>
                <td>E.D.</td><td>Dist.</td><td>Recov.</td><td>CC#</td>
                <td>E.D.</td><td>Dist.</td><td>Recov.</td><td>CC#</td>
                <td>E.D.</td><td>Dist.</td><td>Recov.</td><td>CC#</td>
                <td>E.D.</td><td>Dist.</td><td>Recov.</td><td>CC#</td>
            </tr>';

        if (is_array($departments)) {
            foreach ($departments as $deptRow) {
                $html .= '<tr nobr="true"><td>' . htmlspecialchars($deptRow['department'] ?? '') . '</td>';
                $revs = $deptRow['revisions'] ?? [];
                if (is_array($revs)) {
                    foreach ($revs as $cell) {
                        $html .= '<td style="text-align:center;">' . formatDateDisplay($cell['effective_date'] ?? '') . '</td>';
                        $html .= '<td style="text-align:center;">' . htmlspecialchars($cell['distributed'] ?? '') . '</td>';
                        $html .= '<td style="text-align:center;">' . htmlspecialchars($cell['recovered'] ?? '') . '</td>';
                        $html .= '<td style="text-align:center;">' . htmlspecialchars($cell['cc_no'] ?? '') . '</td>';
                    }
                }
                $html .= '</tr>';
            }
        }

        $footerLabels = [
            'No. of Copies Recovered & Destroyed' => 'copies_recovered_destroyed',
            'Date Destroyed' => 'date_destroyed',
            'Destroyed By' => 'destroyed_by',
        ];
        foreach ($footerLabels as $label => $key) {
            $html .= '<tr><td><b>' . htmlspecialchars($label) . '</b></td>';
            if (is_array($reconciliations)) {
                foreach ($reconciliations as $rec) {
                    $val = $rec[$key] ?? '';
                    if ($key === 'date_destroyed') {
                        $val = formatDateDisplay($val);
                    }
                    $html .= '<td colspan="4" style="text-align:center;">' . htmlspecialchars($val) . '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<p style="font-size:8px;font-style:italic;">Make Copies of this form for each SOP or procedure</p>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose</p>';
        return $html;
    }

    function formatReviewedCell($item)
    {
        $dept = trim((string)($item['review_dept'] ?? ''));
        $by = trim((string)($item['review_by'] ?? ''));
        if ($dept !== '' || $by !== '') {
            if ($dept !== '' && $by !== '') {
                return $dept . ' / ' . $by;
            }
            return $dept !== '' ? $dept : $by;
        }
        return trim((string)($item['reviewed_by_dept'] ?? ''));
    }

    function buildBiennialHtml($row)
    {
        $html = formHeaderHtml('BIENNIAL REVIEW LOG', $row['form_no'], $row['sop_ref']);
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr><td style="width:25%;"><b>SOP Number:</b></td><td>' . htmlspecialchars($row['sop_number']) . '</td></tr>
            <tr><td><b>SOP Title:</b></td><td>' . htmlspecialchars($row['sop_title']) . '</td></tr>
            <tr><td><b>SOP Revision Form Issued by QA - Initial/Date:</b></td><td>' . htmlspecialchars($row['issued_by_qa_initial_date']) . '</td></tr>
        </table><br>';

        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:8%;">Revision No.</td>
                <td style="width:12%;">Effective Date</td>
                <td style="width:12%;">Review Dept</td>
                <td style="width:14%;">Review By</td>
                <td style="width:12%;">Change Control No.</td>
                <td style="width:26%;">Comment</td>
                <td style="width:16%;">Initial/Date</td>
            </tr>';

        $rows = $row['review_rows'] ?? [];
        if (is_array($rows)) {
            foreach ($rows as $item) {
                $hasData = trim((string)($item['revision_no'] ?? '')) !== ''
                    || trim((string)($item['effective_date'] ?? '')) !== ''
                    || trim((string)($item['review_dept'] ?? '')) !== ''
                    || trim((string)($item['review_by'] ?? '')) !== ''
                    || trim((string)($item['reviewed_by_dept'] ?? '')) !== ''
                    || trim((string)($item['change_control_no'] ?? '')) !== ''
                    || trim((string)($item['comment'] ?? '')) !== ''
                    || trim((string)($item['initial_date'] ?? '')) !== '';
                if (!$hasData) {
                    continue;
                }
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . htmlspecialchars($item['revision_no'] ?? '') . '</td>
                    <td style="text-align:center;">' . formatDateDisplay($item['effective_date'] ?? '') . '</td>
                    <td>' . htmlspecialchars($item['review_dept'] ?? '') . '</td>
                    <td>' . htmlspecialchars($item['review_by'] ?? formatReviewedCell($item)) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($item['change_control_no'] ?? '') . '</td>
                    <td>' . htmlspecialchars($item['comment'] ?? '') . '</td>
                    <td style="text-align:center;">' . formatDateDisplay($item['initial_date'] ?? '') . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose without Authorization</p>';
        return $html;
    }

    ensureSodTables($conn);

    if ($_GET['type'] == 'saveDistributionReconciliation') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $departments = isset($input['department_rows']) ? json_encode($input['department_rows']) : '[]';
        $reconciliations = isset($input['reconciliations']) ? json_encode($input['reconciliations']) : '[]';

        $sql = "INSERT INTO sod_distribution_reconciliation (
            plant_id, form_no, sop_ref, sop_number, department_rows, reconciliations,
            entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-013-A') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-013') . "',
            '" . esc($conn, $input['sop_number']) . "',
            '" . esc($conn, $departments) . "',
            '" . esc($conn, $reconciliations) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getDistributionReconciliationLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(entry_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT id, form_no, sop_ref, sop_number, entry_by, entry_date
                FROM sod_distribution_reconciliation
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getDistributionReconciliationById') {
        $output = [];
        $sql = "SELECT * FROM sod_distribution_reconciliation
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatDistributionRow($result->fetch_assoc());
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadDistributionReconciliationForm') {
        $_GET['filename'] = 'SOP Distribution and Reconciliation Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM sod_distribution_reconciliation
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = formatDistributionRow($result->fetch_assoc());
            $pdf->writeHTML(buildDistributionHtml($row), true, false, false, false, '');
        }
        $pdf->Output('SOP_Distribution_Reconciliation_Form.pdf', 'I');
    } else if ($_GET['type'] == 'saveBiennialReviewLog') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $reviewRows = isset($input['review_rows']) ? json_encode($input['review_rows']) : '[]';

        $sql = "INSERT INTO sod_biennial_review_log (
            plant_id, form_no, sop_ref, sop_number, sop_title, issued_by_qa_initial_date,
            review_rows, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-013-B') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-013') . "',
            '" . esc($conn, $input['sop_number']) . "',
            '" . esc($conn, $input['sop_title']) . "',
            '" . esc($conn, $input['issued_by_qa_initial_date']) . "',
            '" . esc($conn, $reviewRows) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getBiennialReviewLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(entry_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT id, form_no, sop_ref, sop_number, sop_title, entry_by, entry_date
                FROM sod_biennial_review_log
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getBiennialReviewLogById') {
        $output = [];
        $sql = "SELECT * FROM sod_biennial_review_log
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatBiennialRow($result->fetch_assoc());
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadBiennialReviewLogForm') {
        $_GET['filename'] = 'Biennial Review Log';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM sod_biennial_review_log
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = formatBiennialRow($result->fetch_assoc());
            $pdf->writeHTML(buildBiennialHtml($row), true, false, false, false, '');
        }
        $pdf->Output('Biennial_Review_Log.pdf', 'I');
    }
}
