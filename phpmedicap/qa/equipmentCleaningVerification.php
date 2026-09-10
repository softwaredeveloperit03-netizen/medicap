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

    function ensureEquipmentCleaningVerificationTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS equipment_cleaning_verification (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-002-01-A',
            equipment_name VARCHAR(255) DEFAULT NULL,
            equipment_id VARCHAR(100) DEFAULT NULL,
            equipment_code VARCHAR(100) DEFAULT NULL,
            previous_product_name VARCHAR(255) DEFAULT NULL,
            cleaning_date DATE DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            qc_lab_sample_no VARCHAR(100) DEFAULT NULL,
            lot_no VARCHAR(100) DEFAULT NULL,
            sample_type VARCHAR(100) DEFAULT NULL,
            sample_type_specify VARCHAR(255) DEFAULT NULL,
            sample_rows LONGTEXT DEFAULT NULL,
            water_temp VARCHAR(100) DEFAULT NULL,
            duration_flush VARCHAR(100) DEFAULT NULL,
            volume_30ml VARCHAR(10) DEFAULT NULL,
            volume_50ml VARCHAR(10) DEFAULT NULL,
            sample_taken_time VARCHAR(50) DEFAULT NULL,
            sample_taken_date DATE DEFAULT NULL,
            sampled_by_date VARCHAR(255) DEFAULT NULL,
            checked_by_date VARCHAR(255) DEFAULT NULL,
            expiry_date DATE DEFAULT NULL,
            comments TEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $conn->query($sql);
    }

    function esc($conn, $value)
    {
        return $conn->real_escape_string($value ?? '');
    }

    ensureEquipmentCleaningVerificationTable($conn);

    if ($_GET['type'] == 'saveEquipmentCleaningVerification') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $cleaningDate = !empty($input['cleaning_date']) ? $input['cleaning_date'] : date('Y-m-d');
        $expiryDate = !empty($input['expiry_date']) ? $input['expiry_date'] : date('Y-m-d', strtotime($cleaningDate . ' +30 days'));

        $sampleRows = isset($input['sample_rows']) ? json_encode($input['sample_rows']) : '[]';

        $sql = "INSERT INTO equipment_cleaning_verification (
            plant_id, form_no, equipment_name, equipment_id, equipment_code, previous_product_name,
            cleaning_date, product_code, qc_lab_sample_no, lot_no, sample_type, sample_type_specify,
            sample_rows, water_temp, duration_flush, volume_30ml, volume_50ml, sample_taken_time,
            sample_taken_date, sampled_by_date, checked_by_date, expiry_date, comments,
            entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-002-01-A') . "',
            '" . esc($conn, $input['equipment_name']) . "',
            '" . esc($conn, $input['equipment_id']) . "',
            '" . esc($conn, $input['equipment_code']) . "',
            '" . esc($conn, $input['previous_product_name']) . "',
            '" . esc($conn, $cleaningDate) . "',
            '" . esc($conn, $input['product_code']) . "',
            '" . esc($conn, $input['qc_lab_sample_no']) . "',
            '" . esc($conn, $input['lot_no']) . "',
            '" . esc($conn, $input['sample_type']) . "',
            '" . esc($conn, $input['sample_type_specify']) . "',
            '" . esc($conn, $sampleRows) . "',
            '" . esc($conn, $input['water_temp']) . "',
            '" . esc($conn, $input['duration_flush']) . "',
            '" . esc($conn, $input['volume_30ml']) . "',
            '" . esc($conn, $input['volume_50ml']) . "',
            '" . esc($conn, $input['sample_taken_time']) . "',
            '" . esc($conn, $input['sample_taken_date']) . "',
            '" . esc($conn, $input['sampled_by_date']) . "',
            '" . esc($conn, $input['checked_by_date']) . "',
            '" . esc($conn, $expiryDate) . "',
            '" . esc($conn, $input['comments']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'updateEquipmentCleaningVerificationChecked') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $id = esc($conn, $input['id']);
        $checkSql = "SELECT checked_by_date FROM equipment_cleaning_verification
                     WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }

        $existing = $checkResult->fetch_assoc();
        if (!empty($existing['checked_by_date'])) {
            echo json_encode([
                'status' => 'already_checked',
                'checked_by_date' => $existing['checked_by_date'],
            ]);
            exit;
        }

        $empName = $_GET['emp_id'];
        $nameSql = "SELECT firstname, lastname FROM employee
                    WHERE emp_id='" . esc($conn, $_GET['emp_id']) . "' LIMIT 1";
        $nameResult = $conn->query($nameSql);
        if ($nameResult && $nameResult->num_rows > 0) {
            $empRow = $nameResult->fetch_assoc();
            $fullName = trim(($empRow['firstname'] ?? '') . ' ' . ($empRow['lastname'] ?? ''));
            if ($fullName !== '') {
                $empName = $fullName;
            }
        }

        $checkedByDate = $empName . ' (' . $_GET['emp_id'] . ') - ' . date('d-m-Y H:i');
        $updateSql = "UPDATE equipment_cleaning_verification
                      SET checked_by_date='" . esc($conn, $checkedByDate) . "'
                      WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";

        if ($conn->query($updateSql)) {
            echo json_encode([
                'status' => 'success',
                'checked_by_date' => $checkedByDate,
            ]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getEquipmentCleaningVerificationLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';

        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(entry_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT * FROM equipment_cleaning_verification
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['sample_rows'] = json_decode($row['sample_rows'], true);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getEquipmentCleaningVerificationById') {
        $output = [];
        $sql = "SELECT * FROM equipment_cleaning_verification
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['sample_rows'] = json_decode($row['sample_rows'], true);
            $output = $row;
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadEquipmentCleaningVerificationLog') {
        $_GET['filename'] = 'Equipment Cleaning Verification Log';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(entry_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $html = '<h3 style="text-align:center;">Equipment Cleaning Verification Sample Collection Log</h3>
        <table border="1" cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:5%;">Sr.</td>
                <td style="width:12%;">Date</td>
                <td style="width:15%;">Equipment Name</td>
                <td style="width:10%;">Equipment ID</td>
                <td style="width:12%;">Previous Product</td>
                <td style="width:10%;">Sample Type</td>
                <td style="width:10%;">QC Sample #</td>
                <td style="width:10%;">Lot</td>
                <td style="width:8%;">Expiry</td>
                <td style="width:8%;">Entry By</td>
            </tr>';

        $sql = "SELECT * FROM equipment_cleaning_verification
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . $i++ . '</td>
                    <td style="text-align:center;">' . date('d-m-Y', strtotime($row['entry_date'])) . '</td>
                    <td>' . htmlspecialchars($row['equipment_name']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['equipment_id']) . '</td>
                    <td>' . htmlspecialchars($row['previous_product_name']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['sample_type']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['qc_lab_sample_no']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['lot_no']) . '</td>
                    <td style="text-align:center;">' . (!empty($row['expiry_date']) ? date('d-m-Y', strtotime($row['expiry_date'])) : '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['entry_by']) . '</td>
                </tr>';
            }
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Equipment_Cleaning_Verification_Log.pdf', 'I');
    } else if ($_GET['type'] == 'downloadEquipmentCleaningVerificationForm') {
        $_GET['filename'] = 'Equipment Cleaning Verification Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM equipment_cleaning_verification
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sampleRows = json_decode($row['sample_rows'], true);
            if (!is_array($sampleRows)) {
                $sampleRows = [];
            }

            $sampleTypeText = htmlspecialchars($row['sample_type']);
            if (!empty($row['sample_type_specify'])) {
                $sampleTypeText .= ' (' . htmlspecialchars($row['sample_type_specify']) . ')';
            }

            $html = '
            <style>
                th, td { border: 1px solid #000; }
                .no-border td { border: none; }
            </style>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td style="width:15%;"><b>TITLE:</b></td>
                    <td style="width:85%; text-align:center;"><b>EQUIPMENT CLEANING VERIFICATION: SAMPLE COLLECTION FORM</b></td>
                </tr>
                <tr>
                    <td><b>FORM NO.:</b></td>
                    <td>' . htmlspecialchars($row['form_no']) . '</td>
                </tr>
                <tr>
                    <td><b>REVISION NO.:</b></td>
                    <td>00</td>
                </tr>
                <tr>
                    <td><b>EFFECTIVE DATE:</b></td>
                    <td>APR 01 2025</td>
                </tr>
            </table>
            <br>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td style="width:50%;"><b>Equipment Name</b><br>' . htmlspecialchars($row['equipment_name']) . '</td>
                    <td style="width:50%;"><b>Equipment ID#</b><br>' . htmlspecialchars($row['equipment_id']) . '</td>
                </tr>
                <tr>
                    <td><b>Previous Product Name</b><br>' . htmlspecialchars($row['previous_product_name']) . '</td>
                    <td><b>Date of Cleaning</b><br>' . (!empty($row['cleaning_date']) ? date('d-m-Y', strtotime($row['cleaning_date'])) : '') . '</td>
                </tr>
                <tr>
                    <td><b>Product Code</b><br>' . htmlspecialchars($row['product_code']) . '</td>
                    <td><b>QC Lab Sample #</b><br>' . htmlspecialchars($row['qc_lab_sample_no']) . '</td>
                </tr>
                <tr>
                    <td colspan="2"><b>Lot</b><br>' . htmlspecialchars($row['lot_no']) . '</td>
                </tr>
                <tr>
                    <td colspan="2"><b>Sample Type:</b> ' . $sampleTypeText . '</td>
                </tr>
            </table>
            <br>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:12%; text-align:center;" rowspan="8"><b>Sample Information</b></td>
                    <td style="width:5%; text-align:center;"><b>#</b></td>
                    <td style="width:28%; text-align:center;"><b>Sample Detail</b></td>
                    <td style="width:22%; text-align:center;"><b>Parameter</b></td>
                    <td style="width:33%; text-align:center;"><b>Value</b></td>
                </tr>';

            $vol = [];
            if ($row['volume_30ml'] === 'Yes') $vol[] = '30 ml';
            if ($row['volume_50ml'] === 'Yes') $vol[] = '50 ml';
            $volumeText = implode(' / ', $vol);

            $sampleParams = [
                ['label' => '1. Water Temp', 'value' => htmlspecialchars($row['water_temp'])],
                ['label' => '2. Duration of Flush', 'value' => htmlspecialchars($row['duration_flush']) . '<br><small>5 minutes (N/A for swab sample)</small>'],
                ['label' => '3. Volume of Final Rinse', 'value' => htmlspecialchars($volumeText) . '<br><small>(N/A for swab sample)</small>'],
                ['label' => '4. Sample Taken', 'value' => 'Time: ' . htmlspecialchars($row['sample_taken_time']) . ' Date: ' . (!empty($row['sample_taken_date']) ? date('d-m-Y', strtotime($row['sample_taken_date'])) : '')],
                ['label' => '&nbsp;', 'value' => '&nbsp;'],
                ['label' => 'Sampled By &amp; Date', 'value' => htmlspecialchars($row['sampled_by_date'])],
            ];

            for ($i = 0; $i < 6; $i++) {
                $sampleInfo = isset($sampleRows[$i]['sample_info']) ? htmlspecialchars($sampleRows[$i]['sample_info']) : '';
                $param = $sampleParams[$i];

                $html .= '<tr>
                    <td style="text-align:center;">' . ($i + 1) . '.</td>
                    <td>' . $sampleInfo . '</td>
                    <td><b>' . $param['label'] . '</b></td>
                    <td>' . $param['value'] . '</td>
                </tr>';
            }

            $html .= '<tr>
                    <td style="text-align:center;">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><b>Checked By &amp; Date</b></td>
                    <td>' . htmlspecialchars($row['checked_by_date']) . '</td>
                </tr>
                <tr>
                    <td colspan="2"><b>Expiry Date (30 days from date of cleaning)</b></td>
                    <td colspan="2">' . (!empty($row['expiry_date']) ? date('d-m-Y', strtotime($row['expiry_date'])) : '') . '</td>
                </tr>
            </table>
            <br>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td><b>Comments:</b><br>' . nl2br(htmlspecialchars($row['comments'])) . '</td>
                </tr>
            </table>';

            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Equipment_Cleaning_Verification_Form.pdf', 'I');
        }
    }
}
?>
