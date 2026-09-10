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

    function ensureGmpInspectionClinicalSiteTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS gmp_inspection_clinical_site (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-011-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-011',
            audit_date DATE DEFAULT NULL,
            clinical_site_name VARCHAR(255) DEFAULT NULL,
            facility_address TEXT DEFAULT NULL,
            city_province VARCHAR(255) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            telephone VARCHAR(100) DEFAULT NULL,
            email_address VARCHAR(255) DEFAULT NULL,
            auditors VARCHAR(255) DEFAULT NULL,
            audit_objectives TEXT DEFAULT NULL,
            proposed_agenda TEXT DEFAULT NULL,
            site_contacts LONGTEXT DEFAULT NULL,
            checklist_data LONGTEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $conn->query($sql);
    }

    function formatAuditRow($row)
    {
        $row['site_contacts'] = decodeJsonField($row['site_contacts'] ?? '[]');
        $row['checklist_data'] = decodeJsonField($row['checklist_data'] ?? '[]');
        return $row;
    }

    function buildChecklistHtml($checklistData)
    {
        $html = '';
        if (!is_array($checklistData)) {
            return $html;
        }

        foreach ($checklistData as $page) {
            $pageNo = htmlspecialchars($page['page_no'] ?? '');
            $html .= '<h4 style="margin-top:12px;">Page ' . $pageNo . '</h4>';

            $sections = $page['sections'] ?? [];
            if (!is_array($sections)) {
                continue;
            }

            foreach ($sections as $section) {
                $sectionTitle = htmlspecialchars($section['section_title'] ?? '');
                $html .= '<p style="font-weight:bold;margin:8px 0 4px;">' . $sectionTitle . '</p>';
                $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                    <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                        <td style="width:5%;">Item</td>
                        <td style="width:55%;">ITEM</td>
                        <td style="width:5%;">Y</td>
                        <td style="width:5%;">N</td>
                        <td style="width:30%;">Comments</td>
                    </tr>';

                $items = $section['items'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $response = $item['response'] ?? '';
                        $html .= '<tr nobr="true">
                            <td style="text-align:center;">' . htmlspecialchars($item['item_no'] ?? '') . '</td>
                            <td>' . htmlspecialchars($item['check_point'] ?? '') . '</td>
                            <td style="text-align:center;">' . ($response === 'Y' ? '✓' : '') . '</td>
                            <td style="text-align:center;">' . ($response === 'N' ? '✓' : '') . '</td>
                            <td>' . htmlspecialchars($item['comments'] ?? '') . '</td>
                        </tr>';
                    }
                }
                $html .= '</table>';
            }
        }
        return $html;
    }

    ensureGmpInspectionClinicalSiteTable($conn);

    if ($_GET['type'] == 'saveGmpInspectionClinicalSite') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $auditDate = !empty($input['audit_date']) ? $input['audit_date'] : date('Y-m-d');
        $contacts = isset($input['site_contacts']) ? json_encode($input['site_contacts']) : '[]';
        $checklist = isset($input['checklist_data']) ? json_encode($input['checklist_data']) : '[]';

        $sql = "INSERT INTO gmp_inspection_clinical_site (
            plant_id, form_no, sop_ref, audit_date, clinical_site_name, facility_address,
            city_province, country, telephone, email_address, auditors, audit_objectives,
            proposed_agenda, site_contacts, checklist_data, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-011-A') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-011') . "',
            '" . esc($conn, $auditDate) . "',
            '" . esc($conn, $input['clinical_site_name']) . "',
            '" . esc($conn, $input['facility_address']) . "',
            '" . esc($conn, $input['city_province']) . "',
            '" . esc($conn, $input['country']) . "',
            '" . esc($conn, $input['telephone']) . "',
            '" . esc($conn, $input['email_address']) . "',
            '" . esc($conn, $input['auditors']) . "',
            '" . esc($conn, $input['audit_objectives']) . "',
            '" . esc($conn, $input['proposed_agenda']) . "',
            '" . esc($conn, $contacts) . "',
            '" . esc($conn, $checklist) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getGmpInspectionClinicalSiteLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';

        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(audit_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT id, form_no, sop_ref, audit_date, clinical_site_name, city_province, country,
                       auditors, entry_by, entry_date
                FROM gmp_inspection_clinical_site
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getGmpInspectionClinicalSiteById') {
        $output = [];
        $sql = "SELECT * FROM gmp_inspection_clinical_site
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatAuditRow($result->fetch_assoc());
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadGmpInspectionClinicalSiteLog') {
        $_GET['filename'] = 'Clinical Laboratory Audit Checklist Log';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(audit_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $html = '<h3 style="text-align:center;">CLINICAL LABORATORY AUDIT CHECKLIST LOG</h3>
        <p style="text-align:center;font-size:10px;">SOP-QA-011 | Form No.: FQA-011-A</p>
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:5%;">Sr.</td>
                <td style="width:12%;">Audit Date</td>
                <td style="width:25%;">Clinical Site</td>
                <td style="width:15%;">City/Province</td>
                <td style="width:12%;">Country</td>
                <td style="width:15%;">Auditor(s)</td>
                <td style="width:11%;">Entry By</td>
            </tr>';

        $sql = "SELECT * FROM gmp_inspection_clinical_site
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY audit_date DESC, id DESC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . $i++ . '</td>
                    <td style="text-align:center;">' . (!empty($row['audit_date']) ? date('d-m-Y', strtotime($row['audit_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['clinical_site_name']) . '</td>
                    <td>' . htmlspecialchars($row['city_province']) . '</td>
                    <td>' . htmlspecialchars($row['country']) . '</td>
                    <td>' . htmlspecialchars($row['auditors']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['entry_by']) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Clinical_Laboratory_Audit_Checklist_Log.pdf', 'I');
    } else if ($_GET['type'] == 'downloadGmpInspectionClinicalSiteForm') {
        $_GET['filename'] = 'Clinical Laboratory Audit Checklist';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM gmp_inspection_clinical_site
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = formatAuditRow($result->fetch_assoc());
            $contacts = $row['site_contacts'];

            $html = '<h3 style="text-align:center;">CLINICAL LABORATORY AUDIT CHECKLIST</h3>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td style="width:15%;"><b>FORM NO.:</b></td>
                    <td style="width:35%;">' . htmlspecialchars($row['form_no']) . '</td>
                    <td style="width:15%;"><b>REF:</b></td>
                    <td style="width:35%;">' . htmlspecialchars($row['sop_ref']) . '</td>
                </tr>
                <tr>
                    <td><b>REVISION NO.:</b></td>
                    <td>00</td>
                    <td><b>EFFECTIVE DATE:</b></td>
                    <td>APR 16 2025</td>
                </tr>
                <tr>
                    <td><b>AUDIT DATE:</b></td>
                    <td colspan="3">' . (!empty($row['audit_date']) ? date('d-m-Y', strtotime($row['audit_date'])) : '') . '</td>
                </tr>
            </table>
            <br>
            <h4>Audit Preparation</h4>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:30%;"><b>Clinical Site Name</b></td><td>' . htmlspecialchars($row['clinical_site_name']) . '</td></tr>
                <tr><td><b>Facility Address</b></td><td>' . htmlspecialchars($row['facility_address']) . '</td></tr>
                <tr><td><b>City/ Province</b></td><td>' . htmlspecialchars($row['city_province']) . '</td></tr>
                <tr><td><b>Country</b></td><td>' . htmlspecialchars($row['country']) . '</td></tr>
                <tr><td><b>Telephone</b></td><td>' . htmlspecialchars($row['telephone']) . '</td></tr>
                <tr><td><b>E-mail Address</b></td><td>' . htmlspecialchars($row['email_address']) . '</td></tr>
                <tr><td><b>Auditor(s)</b></td><td>' . htmlspecialchars($row['auditors']) . '</td></tr>
                <tr><td><b>Audit Objectives</b></td><td>' . htmlspecialchars($row['audit_objectives']) . '</td></tr>
                <tr><td><b>Proposed Agenda</b></td><td>' . htmlspecialchars($row['proposed_agenda']) . '</td></tr>
            </table>
            <br>
            <h4>Site Contacts for the Audit</h4>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                    <td style="width:50%;">Name</td>
                    <td style="width:50%;">Title</td>
                </tr>';

            if (is_array($contacts)) {
                foreach ($contacts as $contact) {
                    $html .= '<tr>
                        <td>' . htmlspecialchars($contact['name'] ?? '') . '</td>
                        <td>' . htmlspecialchars($contact['title'] ?? '') . '</td>
                    </tr>';
                }
            }

            $html .= '</table>';
            $html .= buildChecklistHtml($row['checklist_data']);
            $html .= '<p style="font-size:9px;font-style:italic;text-align:right;">*Confidential Information: Do Not Disclose</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        $pdf->Output('Clinical_Laboratory_Audit_Checklist.pdf', 'I');
    }
}
