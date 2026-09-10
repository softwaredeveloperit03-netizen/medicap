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

    function ensureMedicalInformationTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS medical_information_request (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-043-A',
            request_date DATE DEFAULT NULL,
            caller_name VARCHAR(255) DEFAULT NULL,
            dob_day VARCHAR(10) DEFAULT NULL,
            dob_month VARCHAR(10) DEFAULT NULL,
            dob_year VARCHAR(10) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            postal_code VARCHAR(50) DEFAULT NULL,
            telephone VARCHAR(100) DEFAULT NULL,
            contact_mode VARCHAR(255) DEFAULT NULL,
            referred_by VARCHAR(255) DEFAULT NULL,
            biz_tel_self VARCHAR(100) DEFAULT NULL,
            biz_tel_family_doctor VARCHAR(100) DEFAULT NULL,
            biz_tel_pharmacist VARCHAR(100) DEFAULT NULL,
            biz_tel_sales_rep VARCHAR(100) DEFAULT NULL,
            biz_tel_other VARCHAR(100) DEFAULT NULL,
            biz_tel_other_specify VARCHAR(255) DEFAULT NULL,
            purpose VARCHAR(500) DEFAULT NULL,
            purpose_others_specify VARCHAR(255) DEFAULT NULL,
            urgency VARCHAR(255) DEFAULT NULL,
            expected_response_timeframe VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            response_notes TEXT DEFAULT NULL,
            prepared_by VARCHAR(255) DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $conn->query($sql);
    }

    ensureMedicalInformationTable($conn);

    if ($_GET['type'] == 'saveMedicalInformation') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $requestDate = !empty($input['request_date']) ? $input['request_date'] : date('Y-m-d');

        $sql = "INSERT INTO medical_information_request (
            plant_id, form_no, request_date, caller_name, dob_day, dob_month, dob_year,
            address, postal_code, telephone, contact_mode, referred_by,
            biz_tel_self, biz_tel_family_doctor, biz_tel_pharmacist, biz_tel_sales_rep,
            biz_tel_other, biz_tel_other_specify, purpose, purpose_others_specify,
            urgency, expected_response_timeframe, description, response_notes,
            prepared_by, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-043-A') . "',
            '" . esc($conn, $requestDate) . "',
            '" . esc($conn, $input['caller_name']) . "',
            '" . esc($conn, $input['dob_day']) . "',
            '" . esc($conn, $input['dob_month']) . "',
            '" . esc($conn, $input['dob_year']) . "',
            '" . esc($conn, $input['address']) . "',
            '" . esc($conn, $input['postal_code']) . "',
            '" . esc($conn, $input['telephone']) . "',
            '" . esc($conn, $input['contact_mode']) . "',
            '" . esc($conn, $input['referred_by']) . "',
            '" . esc($conn, $input['biz_tel_self']) . "',
            '" . esc($conn, $input['biz_tel_family_doctor']) . "',
            '" . esc($conn, $input['biz_tel_pharmacist']) . "',
            '" . esc($conn, $input['biz_tel_sales_rep']) . "',
            '" . esc($conn, $input['biz_tel_other']) . "',
            '" . esc($conn, $input['biz_tel_other_specify']) . "',
            '" . esc($conn, $input['purpose']) . "',
            '" . esc($conn, $input['purpose_others_specify']) . "',
            '" . esc($conn, $input['urgency']) . "',
            '" . esc($conn, $input['expected_response_timeframe']) . "',
            '" . esc($conn, $input['description']) . "',
            '" . esc($conn, $input['response_notes']) . "',
            '" . esc($conn, $input['prepared_by']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getMedicalInformationLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';

        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(request_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT * FROM medical_information_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getMedicalInformationById') {
        $output = [];
        $sql = "SELECT * FROM medical_information_request
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = $result->fetch_assoc();
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadMedicalInformationLog') {
        $_GET['filename'] = 'Medical Information Request Log';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(request_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $html = '<h3 style="text-align:center;">MEDICAL INFORMATION REQUEST LOG</h3>
        <p style="text-align:center;font-size:10px;">SOP-QA-043 | Form No.: FQA-043-A</p>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:9px;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:10%;">Request Date</td>
                <td style="width:16%;">Name</td>
                <td style="width:12%;">Telephone</td>
                <td style="width:12%;">Mode of Contact</td>
                <td style="width:14%;">Referred By</td>
                <td style="width:14%;">Purpose</td>
                <td style="width:10%;">Urgency</td>
                <td style="width:12%;">Entry By</td>
            </tr>';

        $sql = "SELECT * FROM medical_information_request
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY request_date ASC, id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . (!empty($row['request_date']) ? date('d-m-Y', strtotime($row['request_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['caller_name']) . '</td>
                    <td>' . htmlspecialchars($row['telephone']) . '</td>
                    <td>' . htmlspecialchars($row['contact_mode']) . '</td>
                    <td>' . htmlspecialchars($row['referred_by']) . '</td>
                    <td>' . htmlspecialchars($row['purpose']) . '</td>
                    <td>' . htmlspecialchars($row['urgency']) . '</td>
                    <td>' . htmlspecialchars($row['entry_by']) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Medical_Information_Request_Log.pdf', 'I');
    } else if ($_GET['type'] == 'downloadMedicalInformationForm') {
        $_GET['filename'] = 'Medical Information Request Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM medical_information_request
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dob = trim(implode('/', array_filter([$row['dob_day'], $row['dob_month'], $row['dob_year']])));
            $otherBiz = trim(($row['biz_tel_other_specify'] ?? '') . ' ' . ($row['biz_tel_other'] ?? ''));

            $html = '<h3 style="text-align:center;">MEDICAL INFORMATION REQUEST FORM</h3>
            <p style="text-align:center;font-size:10px;">Ref: SOP-QA-043 | Form No.: FQA-043-A | Revision: 00 | Effective: APR 15 2025</p>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:10px;">
                <tr>
                    <td style="width:22%;"><b>Request Date</b></td>
                    <td style="width:28%;">' . (!empty($row['request_date']) ? date('d-m-Y', strtotime($row['request_date'])) : '') . '</td>
                    <td style="width:22%;"><b>Name</b></td>
                    <td style="width:28%;">' . htmlspecialchars($row['caller_name']) . '</td>
                </tr>
                <tr>
                    <td><b>Date of Birth</b></td>
                    <td>' . htmlspecialchars($dob) . '</td>
                    <td><b>Telephone</b></td>
                    <td>' . htmlspecialchars($row['telephone']) . '</td>
                </tr>
                <tr>
                    <td><b>Address</b></td>
                    <td colspan="3">' . htmlspecialchars($row['address']) . '</td>
                </tr>
                <tr>
                    <td><b>Postal Code</b></td>
                    <td>' . htmlspecialchars($row['postal_code']) . '</td>
                    <td><b>Mode of Contact</b></td>
                    <td>' . htmlspecialchars($row['contact_mode']) . '</td>
                </tr>
                <tr>
                    <td><b>Who Referred</b></td>
                    <td colspan="3">' . htmlspecialchars($row['referred_by']) . '</td>
                </tr>
                <tr>
                    <td><b>Biz Tel - Self</b></td>
                    <td>' . htmlspecialchars($row['biz_tel_self']) . '</td>
                    <td><b>Biz Tel - Family Doctor</b></td>
                    <td>' . htmlspecialchars($row['biz_tel_family_doctor']) . '</td>
                </tr>
                <tr>
                    <td><b>Biz Tel - Pharmacist</b></td>
                    <td>' . htmlspecialchars($row['biz_tel_pharmacist']) . '</td>
                    <td><b>Biz Tel - Sales Rep</b></td>
                    <td>' . htmlspecialchars($row['biz_tel_sales_rep']) . '</td>
                </tr>
                <tr>
                    <td><b>Biz Tel - Other</b></td>
                    <td colspan="3">' . htmlspecialchars($otherBiz) . '</td>
                </tr>
                <tr>
                    <td><b>Purpose of the Request</b></td>
                    <td colspan="3">' . htmlspecialchars($row['purpose']) . '</td>
                </tr>
                <tr>
                    <td><b>How Urgent</b></td>
                    <td>' . htmlspecialchars($row['urgency']) . '</td>
                    <td><b>Expected Response Timeframe</b></td>
                    <td>' . htmlspecialchars($row['expected_response_timeframe']) . '</td>
                </tr>
                <tr>
                    <td><b>Description of the Request</b></td>
                    <td colspan="3">' . nl2br(htmlspecialchars($row['description'])) . '</td>
                </tr>
                <tr>
                    <td><b>Response / Notes</b></td>
                    <td colspan="3">' . nl2br(htmlspecialchars($row['response_notes'])) . '</td>
                </tr>
                <tr>
                    <td><b>Prepared By</b></td>
                    <td>' . htmlspecialchars($row['prepared_by']) . '</td>
                    <td><b>Entry By</b></td>
                    <td>' . htmlspecialchars($row['entry_by']) . '</td>
                </tr>
            </table>
            <p style="font-size:9px;margin-top:8px;">Note: Requests made by Sales Representatives are to be made on the Medical Information Request Form and sent by fax / e-mail to the Medical Affairs Officer or Designate.</p>';

            $pdf->writeHTML($html, true, false, false, false, '');
        }
        $pdf->Output('Medical_Information_Request_Form.pdf', 'I');
    }
}
