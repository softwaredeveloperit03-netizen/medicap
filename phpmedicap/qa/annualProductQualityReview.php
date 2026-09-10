<?php
require '../db.php';
require '../token.php';
$pdfTypes = ['downloadInhouseApqrForm', 'downloadThirdPartyReviewForm'];
if (in_array($_GET['type'] ?? '', $pdfTypes, true)) {
    require '../tcpdf/tcpdf.php';
}
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
        $decoded = json_decode($value ?? '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    function formatDateDisplay($value)
    {
        if (empty($value) || $value === '0000-00-00') {
            return '';
        }
        return date('d-m-Y', strtotime($value));
    }

    function dateFilterSql($conn, $fromDate, $toDate, $column)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE($column) BETWEEN '" . esc($conn, $fromDate) . "' AND '" . esc($conn, $toDate) . "'";
        }
        return '';
    }

    function safeQueryRows($conn, $sql)
    {
        $result = @$conn->query($sql);
        $rows = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    function tableExists($conn, $tableName)
    {
        $result = @$conn->query("SHOW TABLES LIKE '" . esc($conn, $tableName) . "'");
        return $result && $result->num_rows > 0;
    }

    function formHeaderHtml($title, $formNo, $sopRef = 'SOP-QA-027', $effectiveDate = 'APR 09 2025')
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

    function ensureApqrTables($conn)
    {
        $conn->query("CREATE TABLE IF NOT EXISTS apqr_inhouse_report (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'APQR Template',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-027',
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            product_id VARCHAR(50) DEFAULT NULL,
            grade VARCHAR(100) DEFAULT NULL,
            generic_name VARCHAR(255) DEFAULT NULL,
            regulatory_ref VARCHAR(100) DEFAULT NULL,
            reporting_from DATE DEFAULT NULL,
            reporting_to DATE DEFAULT NULL,
            report_title VARCHAR(255) DEFAULT NULL,
            report_data LONGTEXT DEFAULT NULL,
            overall_rating VARCHAR(100) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Draft',
            prepared_by VARCHAR(255) DEFAULT NULL,
            qa_manager_by VARCHAR(255) DEFAULT NULL,
            qa_manager_date DATETIME DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS apqr_third_party_review (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-027-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-027',
            third_party_name VARCHAR(255) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            product_id VARCHAR(50) DEFAULT NULL,
            regulatory_ref VARCHAR(100) DEFAULT NULL,
            reporting_from DATE DEFAULT NULL,
            reporting_to DATE DEFAULT NULL,
            report_received_date DATE DEFAULT NULL,
            quality_agreement_review TEXT DEFAULT NULL,
            quality_agreement_status VARCHAR(100) DEFAULT NULL,
            completeness_assessment TEXT DEFAULT NULL,
            compliance_assessment TEXT DEFAULT NULL,
            additional_info_required VARCHAR(10) DEFAULT 'No',
            additional_info_details TEXT DEFAULT NULL,
            final_disposition VARCHAR(100) DEFAULT NULL,
            file_reference VARCHAR(255) DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            qa_reviewer VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Completed',
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function formatInhouseRow($row)
    {
        $row['report_data'] = decodeJsonField($row['report_data'] ?? '{}');
        return $row;
    }

    function productMatchSql($conn, $productCode, $productName, $columnCode, $columnName)
    {
        $parts = [];
        if (trim($productCode) !== '') {
            $parts[] = "$columnCode='" . esc($conn, $productCode) . "'";
        }
        if (trim($productName) !== '') {
            $parts[] = "$columnName LIKE '%" . esc($conn, $productName) . "%'";
        }
        return count($parts) ? '(' . implode(' OR ', $parts) . ')' : '1=0';
    }

    function fetchApqrSourceData($conn, $plantId, $productCode, $productName, $fromDate, $toDate)
    {
        $output = [
            'status' => 'success',
            'manufacturing_batches' => [],
            'investigations' => [],
            'recalls' => [],
            'returns' => [],
            'change_controls' => [],
            'quality_complaints_summary' => '',
            'manufacturing_history_notes' => '',
            'manufacturing_yield_notes' => '',
        ];

        $productMatchB = productMatchSql($conn, $productCode, $productName, 'b.product_code', 'p.product_name');
        $released = 0;
        $rejected = 0;
        $total = 0;

        if (tableExists($conn, 'batch_planning')) {
            $planSql = "SELECT b.batch_no, b.mfg_date, b.exp_date, b.planned_batch_size, b.planned_qty, b.qty, b.status, p.product_name
                        FROM batch_planning b
                        LEFT JOIN product p ON b.product_code = p.product_code AND b.plant_id = p.plant_id
                        WHERE b.plant_id='" . esc($conn, $plantId) . "'
                        AND $productMatchB "
                . dateFilterSql($conn, $fromDate, $toDate, 'b.entry_date')
                . " ORDER BY b.entry_date DESC LIMIT 100";
            foreach (safeQueryRows($conn, $planSql) as $row) {
                $total++;
                $status = strtolower(trim($row['status'] ?? ''));
                if (strpos($status, 'reject') !== false) {
                    $rejected++;
                } elseif (strpos($status, 'complete') !== false || strpos($status, 'release') !== false || strpos($status, 'approved') !== false) {
                    $released++;
                }
                $output['manufacturing_batches'][] = [
                    'batch_no' => $row['batch_no'] ?? $row['plan_no'] ?? '',
                    'mfg_date' => $row['mfg_date'] ?? '',
                    'exp_date' => $row['exp_date'] ?? '',
                    'batch_size' => $row['planned_batch_size'] ?? '',
                    'qty_produced' => $row['qty'] ?? $row['planned_qty'] ?? '',
                    'yield_percent' => $row['yield_percent'] ?? '',
                    'status' => $row['status'] ?? '',
                    'remarks' => '',
                ];
            }
        }

        if (!count($output['manufacturing_batches']) && tableExists($conn, 'bmr')) {
            $bmrSql = "SELECT b.batch_no, b.mfg_date, b.exp_date, b.batch_size, b.qty, b.yield_percent, b.status, p.product_name
                       FROM bmr b
                       LEFT JOIN product p ON b.product_code = p.product_code
                       WHERE $productMatchB "
                . dateFilterSql($conn, $fromDate, $toDate, 'b.entry_date')
                . " ORDER BY b.entry_date DESC LIMIT 100";
            foreach (safeQueryRows($conn, $bmrSql) as $row) {
                $total++;
                $status = strtolower(trim($row['status'] ?? ''));
                if (strpos($status, 'reject') !== false) {
                    $rejected++;
                } elseif (strpos($status, 'complete') !== false || strpos($status, 'release') !== false || strpos($status, 'approved') !== false) {
                    $released++;
                }
                $output['manufacturing_batches'][] = [
                    'batch_no' => $row['batch_no'] ?? '',
                    'mfg_date' => $row['mfg_date'] ?? '',
                    'exp_date' => $row['exp_date'] ?? '',
                    'batch_size' => $row['batch_size'] ?? '',
                    'qty_produced' => $row['qty'] ?? '',
                    'yield_percent' => $row['yield_percent'] ?? '',
                    'status' => $row['status'] ?? '',
                    'remarks' => '',
                ];
            }
        }

        $output['manufacturing_history_notes'] = "Total batches in period: $total. Released/Completed: $released. Rejected: $rejected.";

        $yields = array_values(array_filter(array_column($output['manufacturing_batches'], 'yield_percent'), function ($v) {
            return trim((string)$v) !== '';
        }));
        if (count($yields)) {
            $avg = round(array_sum(array_map('floatval', $yields)) / count($yields), 2);
            $output['manufacturing_yield_notes'] = 'Average yield for reporting period: ' . $avg . '% across ' . count($yields) . ' batch(es) with yield data.';
        } else {
            $output['manufacturing_yield_notes'] = 'No yield data available for the selected product and period.';
        }

        $complaintSummary = [];
        if (tableExists($conn, 'complaint')) {
            $complaintSql = "SELECT * FROM complaint WHERE plant_id='" . esc($conn, $plantId) . "'"
                . dateFilterSql($conn, $fromDate, $toDate, 'entry_date');
            if (trim($productName) !== '') {
                $complaintSql .= " AND product_name LIKE '%" . esc($conn, $productName) . "%'";
            }
            $complaintSql .= ' ORDER BY entry_date DESC LIMIT 50';
            foreach (safeQueryRows($conn, $complaintSql) as $row) {
                $complaintSummary[] = formatDateDisplay($row['entry_date'] ?? $row['dateOfComplaint'] ?? $row['complaint_date'] ?? '')
                    . ' — ' . ($row['id'] ?? '')
                    . ': ' . ($row['complaint_nature'] ?? $row['status'] ?? '');
            }
        }
        if (!count($complaintSummary) && tableExists($conn, 'market_complaint')) {
            $marketSql = "SELECT * FROM market_complaint WHERE 1=1" . dateFilterSql($conn, $fromDate, $toDate, 'entry_date');
            if (trim($productName) !== '') {
                $marketSql .= " AND product_name LIKE '%" . esc($conn, $productName) . "%'";
            }
            $marketSql .= ' ORDER BY entry_date DESC LIMIT 50';
            foreach (safeQueryRows($conn, $marketSql) as $row) {
                $complaintSummary[] = formatDateDisplay($row['entry_date'] ?? $row['complaint_date'] ?? '')
                    . ' — ' . ($row['complaint_no'] ?? '')
                    . ': ' . ($row['nature_of_complaint'] ?? '');
            }
        }
        $output['quality_complaints_summary'] = count($complaintSummary)
            ? implode("\n", $complaintSummary)
            : 'No product quality complaints recorded for the selected product and period.';

        if (tableExists($conn, 'product_recall')) {
            $recallSql = "SELECT * FROM product_recall WHERE 1=1" . dateFilterSql($conn, $fromDate, $toDate, 'entry_date');
            if (trim($productName) !== '') {
                $recallSql .= " AND product_name LIKE '%" . esc($conn, $productName) . "%'";
            }
            $recallSql .= ' ORDER BY entry_date DESC LIMIT 50';
            foreach (safeQueryRows($conn, $recallSql) as $row) {
                $output['recalls'][] = [
                    'classification' => $row['mock_recall'] ?? $row['public_recall'] ?? 'Recall',
                    'lot_numbers' => $row['batch_no'] ?? '',
                    'reason' => $row['reason'] ?? $row['remarks'] ?? '',
                    'units_distributed' => $row['qty'] ?? '',
                    'percent_returned' => '',
                    'disposition' => $row['status'] ?? '',
                    'ncr_ref' => '',
                ];
            }
        }

        if (tableExists($conn, 'return_merchandise_report')) {
            $returnSql = "SELECT * FROM return_merchandise_report WHERE plant_id='" . esc($conn, $plantId) . "' AND status='Completed'"
                . dateFilterSql($conn, $fromDate, $toDate, 'received_date');
            if (trim($productCode) !== '' || trim($productName) !== '') {
                $returnSql .= ' AND ' . productMatchSql($conn, $productCode, $productName, 'product_code', 'product_description');
            }
            $returnSql .= ' ORDER BY received_date DESC LIMIT 50';
            foreach (safeQueryRows($conn, $returnSql) as $row) {
                $output['returns'][] = [
                    'return_no' => $row['return_merchandise_no'] ?? '',
                    'lot_number' => $row['lot_number'] ?? '',
                    'investigation' => $row['comments_qa'] ?? '',
                    'result' => $row['disposition'] ?? '',
                    'disposition' => $row['disposition'] ?? '',
                ];
            }
        }

        if (tableExists($conn, 'changecontrol')) {
            $ccSql = "SELECT * FROM changecontrol WHERE 1=1" . dateFilterSql($conn, $fromDate, $toDate, 'entry_date') . " ORDER BY entry_date DESC LIMIT 100";
            foreach (safeQueryRows($conn, $ccSql) as $row) {
                $details = decodeJsonField($row['product_details'] ?? '[]');
                $match = trim($productName) === '' && trim($productCode) === '';
                if (!$match && is_array($details)) {
                    foreach ($details as $detail) {
                        $pName = is_array($detail) ? ($detail['product_name'] ?? '') : '';
                        if ($pName !== '' && stripos($pName, $productName) !== false) {
                            $match = true;
                            break;
                        }
                    }
                }
                if (!$match && trim($productName) !== '' && stripos($row['change_title'] ?? '', $productName) !== false) {
                    $match = true;
                }
                if (!$match && trim($productCode) !== '' && stripos($row['change_title'] ?? '', $productCode) !== false) {
                    $match = true;
                }
                if ($match) {
                    $output['change_controls'][] = [
                        'ctrl_no' => $row['ctrl_no'] ?? '',
                        'change_title' => $row['change_title'] ?? '',
                        'change_related' => $row['change_related'] ?? '',
                        'status' => $row['status'] ?? '',
                        'remarks' => $row['comments'] ?? $row['dept_remark'] ?? '',
                    ];
                }
            }
        }

        return $output;
    }

    function buildInhousePdfHtml($row)
    {
        $data = is_array($row['report_data']) ? $row['report_data'] : decodeJsonField($row['report_data'] ?? '{}');
        $html = formHeaderHtml($row['report_title'] ?? 'Annual Product Quality Review', $row['form_no'] ?? 'APQR Template', $row['sop_ref'] ?? 'SOP-QA-027');
        $html .= '<table border="1" cellpadding="4" style="width:100%;font-size:8px;">
            <tr><td><b>Product:</b></td><td>' . htmlspecialchars($row['product_name'] ?? '') . '</td>
                <td><b>Grade:</b></td><td>' . htmlspecialchars($row['grade'] ?? '') . '</td></tr>
            <tr><td><b>Generic Name:</b></td><td>' . htmlspecialchars($row['generic_name'] ?? '') . '</td>
                <td><b>NDA/ANDA:</b></td><td>' . htmlspecialchars($row['regulatory_ref'] ?? '') . '</td></tr>
            <tr><td><b>Reporting Period:</b></td><td colspan="3">' . formatDateDisplay($row['reporting_from'] ?? '') . ' to ' . formatDateDisplay($row['reporting_to'] ?? '') . '</td></tr>
            <tr><td><b>Overall Rating:</b></td><td>' . htmlspecialchars($data['overall_rating'] ?? $row['overall_rating'] ?? '') . '</td>
                <td><b>Status:</b></td><td>' . htmlspecialchars($row['status'] ?? '') . '</td></tr>
        </table><br>';

        $sections = [
            'Executive Summary' => $data['executive_summary'] ?? '',
            'Introduction' => $data['introduction'] ?? '',
            'Manufacturing History' => $data['manufacturing_history_notes'] ?? '',
            'Master Formula Review' => $data['master_formula_review'] ?? '',
            'Manufacturing Yield' => $data['manufacturing_yield_notes'] ?? '',
            'Packaging Components' => $data['packaging_components'] ?? '',
            'Master Packaging Review' => $data['master_packaging_review'] ?? '',
            'Packaging Summary' => $data['packaging_summary'] ?? '',
            'Equipment Qualification' => $data['equipment_qualification'] ?? '',
            'Quality Complaints' => $data['quality_complaints_summary'] ?? '',
            'Adverse Drug Events' => $data['adverse_drug_events'] ?? '',
            'Complaint Trends' => $data['complaint_trends'] ?? '',
            'Testing — Physical' => $data['inprocess_physical_testing'] ?? '',
            'Testing — Chemical' => $data['inprocess_chemical_testing'] ?? '',
            'Statistical Analysis' => $data['testing_statistical_analysis'] ?? '',
            'Stability Summary' => $data['stability_summary'] ?? '',
            'Validation Summary' => $data['validation_summary'] ?? '',
            'Quality Agreements' => $data['agreements_review'] ?? '',
            'Retained Samples' => $data['retained_samples_evaluation'] ?? '',
            'Previous Report Actions' => $data['previous_report_actions'] ?? '',
            'Recommendations' => $data['recommendations'] ?? '',
            'CAPA Completion Dates' => $data['capa_completion_dates'] ?? '',
        ];

        foreach ($sections as $title => $text) {
            if (trim((string)$text) === '') {
                continue;
            }
            $html .= '<p style="font-weight:bold;font-size:9px;">' . htmlspecialchars($title) . '</p>';
            $html .= '<p style="font-size:8px;">' . nl2br(htmlspecialchars($text)) . '</p>';
        }

        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose Without Authorization</p>';
        return $html;
    }

    function buildThirdPartyPdfHtml($row)
    {
        $html = formHeaderHtml('APQR/APR SUMMARY OF 3RD PARTY GENERATED REPORTS', $row['form_no'] ?? 'FQA-027-A', $row['sop_ref'] ?? 'SOP-QA-027');
        $html .= '<table border="1" cellpadding="4" style="width:100%;font-size:8px;">
            <tr><td><b>Third Party:</b></td><td colspan="3">' . htmlspecialchars($row['third_party_name'] ?? '') . '</td></tr>
            <tr><td><b>Product:</b></td><td>' . htmlspecialchars($row['product_name'] ?? '') . '</td>
                <td><b>NDA/ANDA:</b></td><td>' . htmlspecialchars($row['regulatory_ref'] ?? '') . '</td></tr>
            <tr><td><b>Reporting Period:</b></td><td colspan="3">' . formatDateDisplay($row['reporting_from'] ?? '') . ' to ' . formatDateDisplay($row['reporting_to'] ?? '') . '</td></tr>
            <tr><td><b>Report Received:</b></td><td>' . formatDateDisplay($row['report_received_date'] ?? '') . '</td>
                <td><b>Final Disposition:</b></td><td>' . htmlspecialchars($row['final_disposition'] ?? '') . '</td></tr>
            <tr><td><b>Quality Agreement Review:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['quality_agreement_review'] ?? '')) . '</td></tr>
            <tr><td><b>Agreement Status:</b></td><td>' . htmlspecialchars($row['quality_agreement_status'] ?? '') . '</td>
                <td><b>Additional Info Required:</b></td><td>' . htmlspecialchars($row['additional_info_required'] ?? '') . '</td></tr>
            <tr><td><b>Completeness:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['completeness_assessment'] ?? '')) . '</td></tr>
            <tr><td><b>Compliance:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['compliance_assessment'] ?? '')) . '</td></tr>
            <tr><td><b>Additional Details:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['additional_info_details'] ?? '')) . '</td></tr>
            <tr><td><b>File Reference:</b></td><td colspan="3">' . htmlspecialchars($row['file_reference'] ?? '') . '</td></tr>
            <tr><td><b>Remarks:</b></td><td colspan="3">' . nl2br(htmlspecialchars($row['remarks'] ?? '')) . '</td></tr>
            <tr><td><b>QA Reviewer:</b></td><td>' . htmlspecialchars($row['qa_reviewer'] ?? '') . '</td>
                <td><b>Date:</b></td><td>' . formatDateDisplay(substr($row['entry_date'] ?? '', 0, 10)) . '</td></tr>
        </table>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose Without Authorization</p>';
        return $html;
    }

    ensureApqrTables($conn);

    if ($_GET['type'] == 'getApqrSourceData') {
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $productCode = $_GET['product_code'] ?? '';
        $productName = $_GET['product_name'] ?? '';
        echo json_encode(fetchApqrSourceData($conn, $_GET['plant_id'] ?? '', $productCode, $productName, $fromDate, $toDate));
    } else if ($_GET['type'] == 'saveInhouseApqr') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }

        $reportData = isset($input['report_data']) ? json_encode($input['report_data']) : '{}';
        $overallRating = esc($conn, $input['report_data']['overall_rating'] ?? '');
        $status = esc($conn, $input['status'] ?? 'Draft');
        $id = intval($input['id'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE apqr_inhouse_report SET
                product_code='" . esc($conn, $input['product_code'] ?? '') . "',
                product_name='" . esc($conn, $input['product_name'] ?? '') . "',
                product_id='" . esc($conn, $input['product_id'] ?? '') . "',
                grade='" . esc($conn, $input['grade'] ?? '') . "',
                generic_name='" . esc($conn, $input['generic_name'] ?? '') . "',
                regulatory_ref='" . esc($conn, $input['regulatory_ref'] ?? '') . "',
                reporting_from=" . (empty($input['reporting_from']) ? 'NULL' : "'" . esc($conn, $input['reporting_from']) . "'") . ",
                reporting_to=" . (empty($input['reporting_to']) ? 'NULL' : "'" . esc($conn, $input['reporting_to']) . "'") . ",
                report_title='" . esc($conn, $input['report_title'] ?? 'Annual Product Quality Review') . "',
                report_data='" . esc($conn, $reportData) . "',
                overall_rating='$overallRating',
                status='$status',
                prepared_by='" . esc($conn, $input['prepared_by'] ?? '') . "'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        } else {
            $sql = "INSERT INTO apqr_inhouse_report (
                plant_id, form_no, sop_ref, product_code, product_name, product_id, grade, generic_name,
                regulatory_ref, reporting_from, reporting_to, report_title, report_data, overall_rating,
                status, prepared_by, entry_by, entry_date
            ) VALUES (
                '" . esc($conn, $_GET['plant_id']) . "',
                'APQR Template', 'SOP-QA-027',
                '" . esc($conn, $input['product_code'] ?? '') . "',
                '" . esc($conn, $input['product_name'] ?? '') . "',
                '" . esc($conn, $input['product_id'] ?? '') . "',
                '" . esc($conn, $input['grade'] ?? '') . "',
                '" . esc($conn, $input['generic_name'] ?? '') . "',
                '" . esc($conn, $input['regulatory_ref'] ?? '') . "',
                " . (empty($input['reporting_from']) ? 'NULL' : "'" . esc($conn, $input['reporting_from']) . "'") . ",
                " . (empty($input['reporting_to']) ? 'NULL' : "'" . esc($conn, $input['reporting_to']) . "'") . ",
                '" . esc($conn, $input['report_title'] ?? 'Annual Product Quality Review') . "',
                '" . esc($conn, $reportData) . "',
                '$overallRating',
                '$status',
                '" . esc($conn, $input['prepared_by'] ?? '') . "',
                '" . esc($conn, $_GET['emp_id']) . "',
                '$entry_date'
            )";
        }

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $id > 0 ? $id : $conn->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getInhouseApqrLog') {
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $sql = "SELECT id, product_name, grade, regulatory_ref, reporting_from, reporting_to, overall_rating, status, prepared_by, entry_date
                FROM apqr_inhouse_report
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'"
            . dateFilterSql($conn, $fromDate, $toDate, 'entry_date')
            . ' ORDER BY id DESC';
        $result = $conn->query($sql);
        $output = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getInhouseApqrById') {
        $id = intval($_GET['id'] ?? 0);
        $sql = "SELECT * FROM apqr_inhouse_report WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode(formatInhouseRow($result->fetch_assoc()));
        } else {
            echo json_encode(['error' => 'Not found']);
        }
    } else if ($_GET['type'] == 'approveInhouseApqr') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $sql = "UPDATE apqr_inhouse_report SET status='Approved',
                qa_manager_by='" . esc($conn, $_GET['emp_id']) . "',
                qa_manager_date='$entry_date'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Pending Review'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not approve record']);
        }
    } else if ($_GET['type'] == 'saveThirdPartyReview') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }

        $sql = "INSERT INTO apqr_third_party_review (
            plant_id, form_no, sop_ref, third_party_name, product_code, product_name, product_id, regulatory_ref,
            reporting_from, reporting_to, report_received_date, quality_agreement_review, quality_agreement_status,
            completeness_assessment, compliance_assessment, additional_info_required, additional_info_details,
            final_disposition, file_reference, remarks, qa_reviewer, status, entry_by, entry_date
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            'FQA-027-A', 'SOP-QA-027',
            '" . esc($conn, $input['third_party_name'] ?? '') . "',
            '" . esc($conn, $input['product_code'] ?? '') . "',
            '" . esc($conn, $input['product_name'] ?? '') . "',
            '" . esc($conn, $input['product_id'] ?? '') . "',
            '" . esc($conn, $input['regulatory_ref'] ?? '') . "',
            " . (empty($input['reporting_from']) ? 'NULL' : "'" . esc($conn, $input['reporting_from']) . "'") . ",
            " . (empty($input['reporting_to']) ? 'NULL' : "'" . esc($conn, $input['reporting_to']) . "'") . ",
            " . (empty($input['report_received_date']) ? 'NULL' : "'" . esc($conn, $input['report_received_date']) . "'") . ",
            '" . esc($conn, $input['quality_agreement_review'] ?? '') . "',
            '" . esc($conn, $input['quality_agreement_status'] ?? '') . "',
            '" . esc($conn, $input['completeness_assessment'] ?? '') . "',
            '" . esc($conn, $input['compliance_assessment'] ?? '') . "',
            '" . esc($conn, $input['additional_info_required'] ?? 'No') . "',
            '" . esc($conn, $input['additional_info_details'] ?? '') . "',
            '" . esc($conn, $input['final_disposition'] ?? '') . "',
            '" . esc($conn, $input['file_reference'] ?? '') . "',
            '" . esc($conn, $input['remarks'] ?? '') . "',
            '" . esc($conn, $input['qa_reviewer'] ?? '') . "',
            'Completed',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getThirdPartyReviewLog') {
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $sql = "SELECT * FROM apqr_third_party_review WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'"
            . dateFilterSql($conn, $fromDate, $toDate, 'entry_date')
            . ' ORDER BY id DESC';
        $result = $conn->query($sql);
        $output = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadInhouseApqrForm') {
        $id = intval($_GET['id'] ?? 0);
        $sql = "SELECT * FROM apqr_inhouse_report WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo 'Record not found';
            exit;
        }
        $row = formatInhouseRow($result->fetch_assoc());
        $_GET['filename'] = 'APQR_' . ($row['product_name'] ?? 'Report');
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $html = buildInhousePdfHtml($row);
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output($_GET['filename'] . '.pdf', 'I');
    } else if ($_GET['type'] == 'downloadThirdPartyReviewForm') {
        $id = intval($_GET['id'] ?? 0);
        $sql = "SELECT * FROM apqr_third_party_review WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo 'Record not found';
            exit;
        }
        $row = $result->fetch_assoc();
        $_GET['filename'] = 'FQA-027-A_' . ($row['product_name'] ?? 'Review');
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $html = buildThirdPartyPdfHtml($row);
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output($_GET['filename'] . '.pdf', 'I');
    }
}

$conn->close();
