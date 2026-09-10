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

    function ensureEmployeeInitialsSignatureTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS employee_initials_signature (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-006-A',
            record_date DATE DEFAULT NULL,
            employee_id VARCHAR(100) DEFAULT NULL,
            employee_name VARCHAR(255) DEFAULT NULL,
            initials VARCHAR(50) DEFAULT NULL,
            signature VARCHAR(255) DEFAULT NULL,
            date_of_employment DATE DEFAULT NULL,
            dept_position VARCHAR(255) DEFAULT NULL,
            comment TEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $conn->query($sql);
    }

    ensureEmployeeInitialsSignatureTable($conn);

    if ($_GET['type'] == 'saveEmployeeInitialsSignature') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $recordDate = !empty($input['record_date']) ? $input['record_date'] : date('Y-m-d');
        $employmentDate = !empty($input['date_of_employment']) ? $input['date_of_employment'] : null;
        $employmentSql = $employmentDate ? "'" . esc($conn, $employmentDate) . "'" : 'NULL';

        $sql = "INSERT INTO employee_initials_signature (
            plant_id, form_no, record_date, employee_id, employee_name, initials, signature,
            date_of_employment, dept_position, comment, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-006-A') . "',
            '" . esc($conn, $recordDate) . "',
            '" . esc($conn, $input['employee_id']) . "',
            '" . esc($conn, $input['employee_name']) . "',
            '" . esc($conn, $input['initials']) . "',
            '" . esc($conn, $input['signature']) . "',
            $employmentSql,
            '" . esc($conn, $input['dept_position']) . "',
            '" . esc($conn, $input['comment']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getEmployeeInitialsSignatureLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';

        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(record_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $sql = "SELECT * FROM employee_initials_signature
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getEmployeeInitialsSignatureById') {
        $output = [];
        $sql = "SELECT * FROM employee_initials_signature
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = $result->fetch_assoc();
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadEmployeeInitialsSignatureLog') {
        $_GET['filename'] = 'Employee Initials and Signature Record';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(record_date) BETWEEN '$fromDate' AND '$toDate'";
        }

        $html = '<h3 style="text-align:center;">EMPLOYEE INITIALS AND SIGNATURE RECORD</h3>
        <p style="text-align:center;font-size:10px;">SOP-QA-006 | Form No.: FQA-006-A</p>
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:10%;">Date</td>
                <td style="width:18%;">Employee Name (Print)</td>
                <td style="width:8%;">Initials</td>
                <td style="width:18%;">Signature</td>
                <td style="width:12%;">Date of Employment</td>
                <td style="width:18%;">Dept/ Position</td>
                <td style="width:16%;">Comment</td>
            </tr>';

        $sql = "SELECT * FROM employee_initials_signature
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $dateFilter
                ORDER BY record_date ASC, id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr nobr="true">
                    <td style="text-align:center;">' . (!empty($row['record_date']) ? date('d-m-Y', strtotime($row['record_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['employee_name']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['initials']) . '</td>
                    <td>' . htmlspecialchars($row['signature']) . '</td>
                    <td style="text-align:center;">' . (!empty($row['date_of_employment']) ? date('d-m-Y', strtotime($row['date_of_employment'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['dept_position']) . '</td>
                    <td>' . htmlspecialchars($row['comment']) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employee_Initials_Signature_Record.pdf', 'I');
    } else if ($_GET['type'] == 'downloadEmployeeInitialsSignatureForm') {
        $_GET['filename'] = 'Employee Initials and Signature Record';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM employee_initials_signature
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $html = '<h3 style="text-align:center;">EMPLOYEE INITIALS AND SIGNATURE RECORD</h3>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td style="width:15%;"><b>FORM NO.:</b></td>
                    <td style="width:35%;">' . htmlspecialchars($row['form_no']) . '</td>
                    <td style="width:15%;"><b>REVISION NO.:</b></td>
                    <td style="width:35%;">00</td>
                </tr>
                <tr>
                    <td><b>EFFECTIVE DATE:</b></td>
                    <td>APR 01 2025</td>
                    <td><b>RECORD DATE:</b></td>
                    <td>' . (!empty($row['record_date']) ? date('d-m-Y', strtotime($row['record_date'])) : '') . '</td>
                </tr>
            </table>
            <br>
            <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                    <td style="width:10%;">Date</td>
                    <td style="width:18%;">Employee Name (Print)</td>
                    <td style="width:8%;">Initials</td>
                    <td style="width:18%;">Signature</td>
                    <td style="width:12%;">Date of Employment</td>
                    <td style="width:18%;">Dept/ Position</td>
                    <td style="width:16%;">Comment</td>
                </tr>
                <tr>
                    <td style="text-align:center;">' . (!empty($row['record_date']) ? date('d-m-Y', strtotime($row['record_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['employee_name']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($row['initials']) . '</td>
                    <td>' . htmlspecialchars($row['signature']) . '</td>
                    <td style="text-align:center;">' . (!empty($row['date_of_employment']) ? date('d-m-Y', strtotime($row['date_of_employment'])) : '') . '</td>
                    <td>' . htmlspecialchars($row['dept_position']) . '</td>
                    <td>' . htmlspecialchars($row['comment']) . '</td>
                </tr>
            </table>';

            $pdf->writeHTML($html, true, false, false, false, '');
        }
        $pdf->Output('Employee_Initials_Signature_Form.pdf', 'I');
    }
}
