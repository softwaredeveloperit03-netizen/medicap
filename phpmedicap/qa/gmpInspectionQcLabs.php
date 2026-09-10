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

    function ensureGmpInspectionQcLabTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS gmp_inspection_qc_lab (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-007-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-007',
            audit_date DATE DEFAULT NULL,
            company_name VARCHAR(255) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city_province VARCHAR(255) DEFAULT NULL,
            postal_code VARCHAR(50) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            telephone VARCHAR(100) DEFAULT NULL,
            fax_no VARCHAR(100) DEFAULT NULL,
            email_address VARCHAR(255) DEFAULT NULL,
            company_contacts LONGTEXT DEFAULT NULL,
            approx_employees VARCHAR(100) DEFAULT NULL,
            approx_sq_footage VARCHAR(100) DEFAULT NULL,
            regulatory_inspection VARCHAR(20) DEFAULT NULL,
            regulatory_inspection_details TEXT DEFAULT NULL,
            building_interior_appearance TEXT DEFAULT NULL,
            building_exterior_appearance TEXT DEFAULT NULL,
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
        $row['company_contacts'] = decodeJsonField($row['company_contacts'] ?? '[]');
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
            $title = htmlspecialchars($page['title'] ?? '');
            $html .= '<h4 style="margin-top:12px;">Page ' . $pageNo . ' - ' . $title . '</h4>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                    <td style="width:8%;">Item</td>
                    <td style="width:42%;">Check Point</td>
                    <td style="width:10%;">Response</td>
                    <td style="width:40%;">Remarks</td>
                </tr>';

            $items = $page['items'] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $html .= '<tr nobr="true">
                        <td style="text-align:center;">' . htmlspecialchars($item['item_no'] ?? '') . '</td>
                        <td>' . htmlspecialchars($item['check_point'] ?? '') . '</td>
                        <td style="text-align:center;">' . htmlspecialchars($item['response'] ?? '') . '</td>
                        <td>' . htmlspecialchars($item['remarks'] ?? '') . '</td>
                    </tr>';
                }
            }
            $html .= '</table>';
        }
        return $html;
    }

    ensureGmpInspectionQcLabTable($conn);

    if ($_GET['type'] == 'saveGmpInspectionQcLab') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $auditDate = !empty($input['audit_date']) ? $input['audit_date'] : date('Y-m-d');
        $contacts = isset($input['company_contacts']) ? json_encode($input['company_contacts']) : '[]';
        $checklist = isset($input['checklist_data']) ? json_encode($input['checklist_data']) : '[]';

        $sql = "INSERT INTO gmp_inspection_qc_lab (
            plant_id, form_no, sop_ref, audit_date, company_name, address, city_province,
            postal_code, country, telephone, fax_no, email_address, company_contacts,
            approx_employees, approx_sq_footage, regulatory_inspection, regulatory_inspection_details,
            building_interior_appearance, building_exterior_appearance, checklist_data,
            entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-007-A') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-007') . "',
            '" . esc($conn, $auditDate) . "',
            '" . esc($conn, $input['company_name']) . "',
            '" . esc($conn, $input['address']) . "',
            '" . esc($conn, $input['city_province']) . "',
            '" . esc($conn, $input['postal_code']) . "',
            '" . esc($conn, $input['country']) . "',
            '" . esc($conn, $input['telephone']) . "',
            '" . esc($conn, $input['fax_no']) . "',
            '" . esc($conn, $input['email_address']) . "',
            '" . esc($conn, $contacts) . "',
            '" . esc($conn, $input['approx_employees']) . "',
            '" . esc($conn, $input['approx_sq_footage']) . "',
            '" . esc($conn, $input['regulatory_inspection']) . "',
            '" . esc($conn, $input['regulatory_inspection_details']) . "',
            '" . esc($conn, $input['building_interior_appearance']) . "',
            '" . esc($conn, $input['building_exterior_appearance']) . "',
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
    } else if ($_GET['type'] == 'getGmpInspectionQcLabLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';

        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(audit_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT id, form_no, sop_ref, audit_date, company_name, city_province, country,
                       approx_employees, regulatory_inspection, entry_by, entry_date
                FROM gmp_inspection_qc_lab
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getGmpInspectionQcLabById') {
        $output = [];
        $sql = "SELECT * FROM gmp_inspection_qc_lab
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatAuditRow($result->fetch_assoc());
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadGmpInspectionQcLabLog') {
        $_GET['filename'] = 'QC Department Audit Checklist Log';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(audit_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $html = '<h3 style="text-align:center;">QUALITY CONTROL DEPARTMENT AUDIT CHECKLIST LOG</h3>
        <p style="text-align:center;font-size:10px;">SOP-QA-007 | Form No.: FQA-007-A</p>
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:5%;">Sr.</td>
                <td style="width:12%;">Audit Date</td>
                <td style="width:25%;">Company Name</td>
                <td style="width:15%;">City/Province</td>
                <td style="width:12%;">Country</td>
                <td style="width:10%;">Employees</td>
                <td style="width:10%;">Reg. Inspection</td>
                <td style="width:11%;">Entry By</td>
            </tr>';

        $sql = "SELECT * FROM gmp_inspection_qc_lab
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY audit_date DESC, id DESC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . $i++ . '</td>
                    <td style="text-align:center;">' . (!empty($row['audit_date']) ? date('d-m-Y', strtotime($row['audit_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['company_name']) . '</td>
                    <td>' . htmlspecialchars($row['city_province']) . '</td>
                    <td>' . htmlspecialchars($row['country']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['approx_employees']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['regulatory_inspection']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['entry_by']) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QC_Department_Audit_Checklist_Log.pdf', 'I');
    } else if ($_GET['type'] == 'downloadGmpInspectionQcLabForm') {
        $_GET['filename'] = 'QC Department Audit Checklist';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM gmp_inspection_qc_lab
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = formatAuditRow($result->fetch_assoc());
            $contacts = $row['company_contacts'];

            $html = '<h3 style="text-align:center;">QUALITY CONTROL DEPARTMENT AUDIT CHECKLIST</h3>
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
            <h4>Page 1 - Company Profile</h4>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:30%;"><b>Name of Company</b></td><td>' . htmlspecialchars($row['company_name']) . '</td></tr>
                <tr><td><b>Address</b></td><td>' . htmlspecialchars($row['address']) . '</td></tr>
                <tr><td><b>City/ Province</b></td><td>' . htmlspecialchars($row['city_province']) . '</td></tr>
                <tr><td><b>Postal Code</b></td><td>' . htmlspecialchars($row['postal_code']) . '</td></tr>
                <tr><td><b>Country</b></td><td>' . htmlspecialchars($row['country']) . '</td></tr>
                <tr><td><b>Telephone</b></td><td>' . htmlspecialchars($row['telephone']) . '</td></tr>
                <tr><td><b>Fax #</b></td><td>' . htmlspecialchars($row['fax_no']) . '</td></tr>
                <tr><td><b>E-mail Address</b></td><td>' . htmlspecialchars($row['email_address']) . '</td></tr>
            </table>
            <br>
            <h4>Company Contacts for the Audit</h4>
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

            $html .= '</table>
            <br>
            <h4>Facility Information</h4>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:55%;"><b>Approximate number of employees at the Facility</b></td><td>' . htmlspecialchars($row['approx_employees']) . '</td></tr>
                <tr><td><b>Approximate square footage of the facility</b></td><td>' . htmlspecialchars($row['approx_sq_footage']) . '</td></tr>
                <tr><td><b>Has the facility been inspected by a regulatory agency?</b></td><td>' . htmlspecialchars($row['regulatory_inspection']) . '</td></tr>
                <tr><td><b>If Yes, please provide dates and results</b></td><td>' . htmlspecialchars($row['regulatory_inspection_details']) . '</td></tr>
                <tr><td><b>General Appearance of building(s) interior</b></td><td>' . htmlspecialchars($row['building_interior_appearance']) . '</td></tr>
                <tr><td><b>General appearance of building(s) and surrounding area</b></td><td>' . htmlspecialchars($row['building_exterior_appearance']) . '</td></tr>
            </table>';

            $html .= buildChecklistHtml($row['checklist_data']);
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        $pdf->Output('QC_Department_Audit_Checklist.pdf', 'I');
    }
}
