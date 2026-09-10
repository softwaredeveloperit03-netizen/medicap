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

    function ensureTrainingTables($conn)
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS employee_training_general (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-003-A',
                employee_name VARCHAR(255) DEFAULT NULL,
                employee_id VARCHAR(100) DEFAULT NULL,
                employee_signature VARCHAR(255) DEFAULT NULL,
                position VARCHAR(255) DEFAULT NULL,
                department VARCHAR(255) DEFAULT NULL,
                training_rows LONGTEXT DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS employee_training_gmp (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-003-B',
                trainer VARCHAR(255) DEFAULT NULL,
                training_date DATE DEFAULT NULL,
                aids_used TEXT DEFAULT NULL,
                attendance_rows LONGTEXT DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS employee_training_group (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-003-C',
                document_type VARCHAR(100) DEFAULT NULL,
                document_type_other VARCHAR(255) DEFAULT NULL,
                document_code_revision VARCHAR(255) DEFAULT NULL,
                document_title VARCHAR(255) DEFAULT NULL,
                note TEXT DEFAULT NULL,
                employee_rows LONGTEXT DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS employee_training_ojt (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-003-D',
                employee_name VARCHAR(255) DEFAULT NULL,
                employee_id VARCHAR(100) DEFAULT NULL,
                employee_signature VARCHAR(255) DEFAULT NULL,
                position VARCHAR(255) DEFAULT NULL,
                department VARCHAR(255) DEFAULT NULL,
                content_of_training TEXT DEFAULT NULL,
                related_sop VARCHAR(255) DEFAULT NULL,
                equipment_id VARCHAR(100) DEFAULT NULL,
                reference_document VARCHAR(255) DEFAULT NULL,
                training_steps LONGTEXT DEFAULT NULL,
                other_specify TEXT DEFAULT NULL,
                adequate_knowledge VARCHAR(10) DEFAULT NULL,
                areas_of_concern VARCHAR(10) DEFAULT NULL,
                assessment_comment TEXT DEFAULT NULL,
                reviewed_by VARCHAR(255) DEFAULT NULL,
                reviewed_date VARCHAR(255) DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($tables as $tableSql) {
            $conn->query($tableSql);
        }
        $colCheck = $conn->query("SHOW COLUMNS FROM employee_training_general LIKE 'employee_id'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE employee_training_general ADD COLUMN employee_id VARCHAR(100) DEFAULT NULL AFTER employee_name");
        }
        $ojtColCheck = $conn->query("SHOW COLUMNS FROM employee_training_ojt LIKE 'employee_id'");
        if ($ojtColCheck && $ojtColCheck->num_rows === 0) {
            $conn->query("ALTER TABLE employee_training_ojt ADD COLUMN employee_id VARCHAR(100) DEFAULT NULL AFTER employee_name");
        }
    }

    function dateFilterSql($fromDate, $toDate)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE(entry_date) BETWEEN '$fromDate' AND '$toDate'";
        }
        return '';
    }

    function formHeaderHtml($title, $formNo)
    {
        return '
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:15%;"><b>TITLE:</b></td>
                <td style="width:85%; text-align:center;"><b>' . htmlspecialchars($title) . '</b></td>
            </tr>
            <tr>
                <td><b>FORM NO.:</b></td>
                <td>' . htmlspecialchars($formNo) . '</td>
            </tr>
            <tr>
                <td><b>REVISION NO.:</b></td>
                <td>00</td>
            </tr>
            <tr>
                <td><b>EFFECTIVE DATE:</b></td>
                <td>MAR 24 2025</td>
            </tr>
            <tr>
                <td><b>PAGE NO.:</b></td>
                <td>1 OF 1</td>
            </tr>
        </table><br>';
    }

    ensureTrainingTables($conn);
    $type = $_GET['type'] ?? '';

    if ($type === 'saveGeneralTraining') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $rows = json_encode($input['training_rows'] ?? []);
        $sql = "INSERT INTO employee_training_general (
            plant_id, form_no, employee_name, employee_id, employee_signature, position, department,
            training_rows, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            'FQA-003-A',
            '" . esc($conn, $input['employee_name']) . "',
            '" . esc($conn, $input['employee_id']) . "',
            '" . esc($conn, $input['employee_signature']) . "',
            '" . esc($conn, $input['position']) . "',
            '" . esc($conn, $input['department']) . "',
            '" . esc($conn, $rows) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getGeneralTrainingByEmployee') {
        $output = [];
        $empId = esc($conn, $_GET['emp_id'] ?? '');
        $empName = esc($conn, $_GET['employee_name'] ?? '');
        if ($empId === '' && $empName === '') {
            echo json_encode($output);
            exit;
        }
        $match = "employee_id='$empId'";
        if ($empName !== '') {
            $match = "(employee_id='$empId' OR employee_name='" . $empName . "')";
        }
        $sql = "SELECT * FROM employee_training_general
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND $match
                ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['training_rows'] = json_decode($row['training_rows'], true);
            $output = $row;
        }
        echo json_encode($output);
    } else if ($type === 'getGeneralTrainingLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''));
        $sql = "SELECT * FROM employee_training_general WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['training_rows'] = json_decode($row['training_rows'], true);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateGeneralTrainingStamp') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id']) || !isset($input['row_index']) || empty($input['field'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $rowIndex = (int)$input['row_index'];
        $field = $input['field'] === 'reviewed_by' ? 'reviewed_by' : 'read_understood_by';
        $sql = "SELECT training_rows FROM employee_training_general WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $record = $result->fetch_assoc();
        $rows = json_decode($record['training_rows'], true);
        if (!is_array($rows) || !isset($rows[$rowIndex])) {
            echo json_encode(['status' => 'Invalid row']);
            exit;
        }
        if (!empty($rows[$rowIndex][$field])) {
            echo json_encode(['status' => 'already_stamped', 'value' => $rows[$rowIndex][$field]]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $rows[$rowIndex][$field] = $stamp;
        $updateSql = "UPDATE employee_training_general SET training_rows='" . esc($conn, json_encode($rows)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        echo $conn->query($updateSql)
            ? json_encode(['status' => 'success', 'value' => $stamp, 'training_rows' => $rows])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'downloadGeneralTrainingForm') {
        $_GET['filename'] = 'Employee Training Record General';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM employee_training_general WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $rows = json_decode($row['training_rows'], true);
            if (!is_array($rows)) $rows = [];
            $html = formHeaderHtml('EMPLOYEE TRAINING RECORD - GENERAL', 'FQA-003-A');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>Name</b><br>' . htmlspecialchars($row['employee_name']) . '</td>
                <td style="width:25%;"><b>Signature</b><br>' . htmlspecialchars($row['employee_signature']) . '</td>
                <td style="width:25%;"><b>Position</b><br>' . htmlspecialchars($row['position']) . '</td>
                <td style="width:25%;"><b>Department</b><br>' . htmlspecialchars($row['department']) . '</td></tr></table><br>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:15%;"><b>SOP/POLICY #</b></td>
                    <td style="width:10%;"><b>Rev. #</b></td>
                    <td style="width:30%;"><b>Procedure</b></td>
                    <td style="width:22%;"><b>Read and Understood by Initial/Date</b></td>
                    <td style="width:23%;"><b>Reviewed by Area Manager/Designate Initial/date</b></td>
                </tr>';
            foreach ($rows as $tr) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($tr['sop_policy_no'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['rev_no'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['procedure'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['read_understood_by'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['reviewed_by'] ?? '') . '</td>
                </tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Employee_Training_Record_General.pdf', 'I');
        }
    } else if ($type === 'saveGmpTraining') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $rows = json_encode($input['attendance_rows'] ?? []);
        $sql = "INSERT INTO employee_training_gmp (
            plant_id, form_no, trainer, training_date, aids_used, attendance_rows, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            'FQA-003-B',
            '" . esc($conn, $input['trainer']) . "',
            '" . esc($conn, $input['training_date']) . "',
            '" . esc($conn, $input['aids_used']) . "',
            '" . esc($conn, $rows) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getGmpTrainingLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''));
        $sql = "SELECT * FROM employee_training_gmp WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['attendance_rows'] = json_decode($row['attendance_rows'], true);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateGmpTrainingStamp') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id']) || !isset($input['row_index'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $rowIndex = (int)$input['row_index'];
        $sql = "SELECT attendance_rows FROM employee_training_gmp WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $record = $result->fetch_assoc();
        $rows = json_decode($record['attendance_rows'], true);
        if (!is_array($rows) || !isset($rows[$rowIndex])) {
            echo json_encode(['status' => 'Invalid row']);
            exit;
        }
        if (!empty($rows[$rowIndex]['signature'])) {
            echo json_encode(['status' => 'already_stamped', 'value' => $rows[$rowIndex]['signature']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $rows[$rowIndex]['signature'] = $stamp;
        $rows[$rowIndex]['attendance_date'] = date('Y-m-d');
        $updateSql = "UPDATE employee_training_gmp SET attendance_rows='" . esc($conn, json_encode($rows)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        echo $conn->query($updateSql)
            ? json_encode(['status' => 'success', 'value' => $stamp, 'attendance_rows' => $rows])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'downloadGmpTrainingForm') {
        $_GET['filename'] = 'GMP Training';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM employee_training_gmp WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $rows = json_decode($row['attendance_rows'], true);
            if (!is_array($rows)) $rows = [];
            $html = formHeaderHtml('GMP TRAINING', 'FQA-003-B');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr>
                    <td style="width:33%;"><b>Trainer</b><br>' . htmlspecialchars($row['trainer']) . '</td>
                    <td style="width:33%;"><b>Date</b><br>' . (!empty($row['training_date']) ? date('d-m-Y', strtotime($row['training_date'])) : '') . '</td>
                    <td style="width:34%;"><b>Aids Used</b><br>' . nl2br(htmlspecialchars($row['aids_used'])) . '</td>
                </tr></table><br>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:30%;"><b>Employee Name</b></td>
                    <td style="width:30%;"><b>Signature</b></td>
                    <td style="width:15%;"><b>Date</b></td>
                    <td style="width:25%;"><b>Comments</b></td>
                </tr>';
            foreach ($rows as $tr) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($tr['employee_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['signature'] ?? '') . '</td>
                    <td>' . (!empty($tr['attendance_date']) ? date('d-m-Y', strtotime($tr['attendance_date'])) : '') . '</td>
                    <td>' . htmlspecialchars($tr['comments'] ?? '') . '</td>
                </tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('GMP_Training.pdf', 'I');
        }
    } else if ($type === 'saveGroupTraining') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $rows = json_encode($input['employee_rows'] ?? []);
        $sql = "INSERT INTO employee_training_group (
            plant_id, form_no, document_type, document_type_other, document_code_revision,
            document_title, note, employee_rows, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            'FQA-003-C',
            '" . esc($conn, $input['document_type']) . "',
            '" . esc($conn, $input['document_type_other']) . "',
            '" . esc($conn, $input['document_code_revision']) . "',
            '" . esc($conn, $input['document_title']) . "',
            '" . esc($conn, $input['note']) . "',
            '" . esc($conn, $rows) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getGroupTrainingLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''));
        $sql = "SELECT * FROM employee_training_group WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['employee_rows'] = json_decode($row['employee_rows'], true);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateGroupTrainingStamp') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id']) || !isset($input['row_index'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $rowIndex = (int)$input['row_index'];
        $sql = "SELECT employee_rows FROM employee_training_group WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $record = $result->fetch_assoc();
        $rows = json_decode($record['employee_rows'], true);
        if (!is_array($rows) || !isset($rows[$rowIndex])) {
            echo json_encode(['status' => 'Invalid row']);
            exit;
        }
        if (!empty($rows[$rowIndex]['read_understood'])) {
            echo json_encode(['status' => 'already_stamped', 'value' => $rows[$rowIndex]['read_understood']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $rows[$rowIndex]['read_understood'] = $stamp;
        $updateSql = "UPDATE employee_training_group SET employee_rows='" . esc($conn, json_encode($rows)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        echo $conn->query($updateSql)
            ? json_encode(['status' => 'success', 'value' => $stamp, 'employee_rows' => $rows])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'downloadGroupTrainingForm') {
        $_GET['filename'] = 'Group Training';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM employee_training_group WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $rows = json_decode($row['employee_rows'], true);
            if (!is_array($rows)) $rows = [];
            $docType = htmlspecialchars($row['document_type']);
            if ($row['document_type'] === 'Other' && !empty($row['document_type_other'])) {
                $docType .= ' (' . htmlspecialchars($row['document_type_other']) . ')';
            }
            $html = formHeaderHtml('GROUP TRAINING', 'FQA-003-C');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>Document Type</b></td><td>' . $docType . '</td></tr>
                <tr><td><b>Document code and Revision No.</b></td><td>' . htmlspecialchars($row['document_code_revision']) . '</td></tr>
                <tr><td><b>Document Title</b></td><td>' . htmlspecialchars($row['document_title']) . '</td></tr>
                <tr><td><b>Note</b></td><td>' . nl2br(htmlspecialchars($row['note'])) . '</td></tr>
            </table><br>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:8%;"><b>No.</b></td>
                    <td style="width:42%;"><b>Employee Name (Print)</b></td>
                    <td colspan="2" style="width:50%; text-align:center;"><b>Read &amp; Understood by</b></td>
                </tr>
                <tr style="background-color:#f3e8e8;">
                    <td></td><td></td>
                    <td style="width:25%;"><b>Signature</b></td>
                    <td style="width:25%;"><b>Date</b></td>
                </tr>';
            foreach ($rows as $i => $tr) {
                $parts = explode(' - ', $tr['read_understood'] ?? '', 2);
                $html .= '<tr>
                    <td style="text-align:center;">' . ($i + 1) . '</td>
                    <td>' . htmlspecialchars($tr['employee_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['read_understood'] ?? '') . '</td>
                    <td>' . htmlspecialchars($parts[1] ?? '') . '</td>
                </tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Group_Training.pdf', 'I');
        }
    } else if ($type === 'saveOjtTraining') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $steps = json_encode($input['training_steps'] ?? []);
        $sql = "INSERT INTO employee_training_ojt (
            plant_id, form_no, employee_name, employee_id, employee_signature, position, department,
            content_of_training, related_sop, equipment_id, reference_document,
            training_steps, other_specify, adequate_knowledge, areas_of_concern,
            assessment_comment, reviewed_by, reviewed_date, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            'FQA-003-D',
            '" . esc($conn, $input['employee_name']) . "',
            '" . esc($conn, $input['employee_id']) . "',
            '" . esc($conn, $input['employee_signature']) . "',
            '" . esc($conn, $input['position']) . "',
            '" . esc($conn, $input['department']) . "',
            '" . esc($conn, $input['content_of_training']) . "',
            '" . esc($conn, $input['related_sop']) . "',
            '" . esc($conn, $input['equipment_id']) . "',
            '" . esc($conn, $input['reference_document']) . "',
            '" . esc($conn, $steps) . "',
            '" . esc($conn, $input['other_specify']) . "',
            '" . esc($conn, $input['adequate_knowledge']) . "',
            '" . esc($conn, $input['areas_of_concern']) . "',
            '" . esc($conn, $input['assessment_comment']) . "',
            '" . esc($conn, $input['reviewed_by']) . "',
            '" . esc($conn, $input['reviewed_date']) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } else if ($type === 'getOjtTrainingByEmployee') {
        $output = [];
        $empId = esc($conn, $_GET['emp_id'] ?? '');
        $empName = esc($conn, $_GET['employee_name'] ?? '');
        if ($empId === '' && $empName === '') {
            echo json_encode($output);
            exit;
        }
        $match = "employee_id='$empId'";
        if ($empName !== '') {
            $match = "(employee_id='$empId' OR employee_name='" . $empName . "')";
        }
        $sql = "SELECT * FROM employee_training_ojt
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND $match
                ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['training_steps'] = json_decode($row['training_steps'], true);
            $output = $row;
        }
        echo json_encode($output);
    } else if ($type === 'getOjtTrainingLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''));
        $sql = "SELECT * FROM employee_training_ojt WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['training_steps'] = json_decode($row['training_steps'], true);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateOjtTrainingStamp') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $stampType = $input['stamp_type'] ?? '';
        $sql = "SELECT * FROM employee_training_ojt WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $record = $result->fetch_assoc();
        $stamp = getEmpStamp($conn, $_GET['emp_id']);

        if ($stampType === 'reviewed') {
            if (!empty($record['reviewed_by'])) {
                echo json_encode(['status' => 'already_stamped', 'value' => $record['reviewed_by']]);
                exit;
            }
            $updateSql = "UPDATE employee_training_ojt SET reviewed_by='" . esc($conn, $stamp) . "', reviewed_date='" . esc($conn, date('Y-m-d')) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
            echo $conn->query($updateSql)
                ? json_encode(['status' => 'success', 'value' => $stamp])
                : json_encode(['status' => $conn->error]);
        } else if ($stampType === 'step') {
            $rowIndex = (int)($input['row_index'] ?? -1);
            $field = ($input['field'] ?? '') === 'trainer' ? 'trainer_stamp' : 'trainee_stamp';
            $steps = json_decode($record['training_steps'], true);
            if (!is_array($steps) || !isset($steps[$rowIndex])) {
                echo json_encode(['status' => 'Invalid row']);
                exit;
            }
            if ($field === 'trainer_stamp' && (!empty($steps[$rowIndex]['trainer_na']) || ($steps[$rowIndex]['trainer_stamp'] ?? '') === 'N/A')) {
                echo json_encode(['status' => 'Trainer N/A for this step']);
                exit;
            }
            if (!empty($steps[$rowIndex][$field])) {
                echo json_encode(['status' => 'already_stamped', 'value' => $steps[$rowIndex][$field]]);
                exit;
            }
            $steps[$rowIndex][$field] = $stamp;
            $updateSql = "UPDATE employee_training_ojt SET training_steps='" . esc($conn, json_encode($steps)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
            echo $conn->query($updateSql)
                ? json_encode(['status' => 'success', 'value' => $stamp, 'training_steps' => $steps])
                : json_encode(['status' => $conn->error]);
        } else {
            echo json_encode(['status' => 'Invalid stamp type']);
        }
    } else if ($type === 'downloadOjtTrainingForm') {
        $_GET['filename'] = 'Employee Training Record OJT';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM employee_training_ojt WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $steps = json_decode($row['training_steps'], true);
            if (!is_array($steps)) $steps = [];
            $html = formHeaderHtml('EMPLOYEE TRAINING RECORD-ON-THE-JOB', 'FQA-003-D');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>Name</b><br>' . htmlspecialchars($row['employee_name']) . '</td>
                <td style="width:25%;"><b>Signature</b><br>' . htmlspecialchars($row['employee_signature']) . '</td>
                <td style="width:25%;"><b>Position</b><br>' . htmlspecialchars($row['position']) . '</td>
                <td style="width:25%;"><b>Department</b><br>' . htmlspecialchars($row['department']) . '</td></tr>
                <tr><td colspan="2"><b>Content of Training</b><br>' . nl2br(htmlspecialchars($row['content_of_training'])) . '</td>
                <td colspan="2"><b>Related SOP/Work Instruction</b><br>' . htmlspecialchars($row['related_sop']) . '</td></tr>
                <tr><td colspan="2"><b>Equipment ID</b><br>' . htmlspecialchars($row['equipment_id']) . '</td>
                <td colspan="2"><b>Reference Document# (if applicable)</b><br>' . htmlspecialchars($row['reference_document']) . '</td></tr>
            </table><br>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:40%;"><b>Content of Training</b></td>
                    <td style="width:30%;"><b>Trainee (Initial/Date)</b></td>
                    <td style="width:30%;"><b>Trainer (Initial/Date)</b></td>
                </tr>';
            foreach ($steps as $tr) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($tr['content'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['trainee_stamp'] ?? '') . '</td>
                    <td>' . htmlspecialchars($tr['trainer_stamp'] ?? 'N/A') . '</td>
                </tr>';
            }
            if (!empty($row['other_specify'])) {
                $html .= '<tr><td colspan="3"><b>Other (specify):</b> ' . nl2br(htmlspecialchars($row['other_specify'])) . '</td></tr>';
            }
            $html .= '</table><br>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td colspan="2"><b>Assessment</b></td></tr>
                <tr><td colspan="2">Has the trainee shown adequate knowledge to complete the functions independently? <b>' . htmlspecialchars($row['adequate_knowledge']) . '</b></td></tr>
                <tr><td colspan="2">Are there any area of concern or deficiencies which require further attention /training? <b>' . htmlspecialchars($row['areas_of_concern']) . '</b></td></tr>
                <tr><td colspan="2"><b>Comment:</b><br>' . nl2br(htmlspecialchars($row['assessment_comment'])) . '</td></tr>
                <tr><td style="width:50%;"><b>Reviewed By Area Manager/ Designate</b><br>' . htmlspecialchars($row['reviewed_by']) . '</td>
                <td style="width:50%;"><b>Date</b><br>' . (!empty($row['reviewed_date']) ? date('d-m-Y', strtotime($row['reviewed_date'])) : '') . '</td></tr>
            </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Employee_Training_Record_OJT.pdf', 'I');
        }
    }
} else {
    echo 'Invalid Token';
}

$conn->close();
?>
