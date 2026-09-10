<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');
// ini_set('display_errors', 1);
// error_reporting(E_ALL);



$token = $_GET['token'] ?? '';
$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
$entry_date = date('Y-m-d H:i:s');
$input = json_decode(file_get_contents('php://input'), true);

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

    function formHeaderHtml($title, $formNo)
    {
        return '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr>
                <td colspan="4" style="text-align:center; font-size:14px; font-weight:bold; background-color:#e8eef5;">' . htmlspecialchars($title) . '</td>
            </tr>
            <tr>
                <td style="width:18%; background-color:#f5f7fa;"><b>FORM NO.:</b></td>
                <td style="width:32%;">' . htmlspecialchars($formNo) . '</td>
                <td style="width:18%; background-color:#f5f7fa;"><b>REF:</b></td>
                <td style="width:32%;">SOP-QA-042</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>REVISION NO.:</b></td>
                <td>00</td>
                <td style="background-color:#f5f7fa;"><b>EFFECTIVE DATE:</b></td>
                <td>16 APR 2025</td>
            </tr>
        </table><br/>';
    }

    function cell($text)
    {
        return htmlspecialchars((string) ($text ?? ''));
    }

    function fieldVal($item, $key)
    {
        if (is_object($item)) {
            return $item->$key ?? '';
        }
        if (is_array($item)) {
            return $item[$key] ?? '';
        }
        return '';
    }

    function enrichManagers($conn, $managers)
    {
        if (!is_array($managers)) {
            return [];
        }
        $output = [];
        foreach ($managers as $manager) {
            if (is_object($manager)) {
                $manager = (array) $manager;
            }
            if (!is_array($manager)) {
                continue;
            }
            $empId = $manager['emp_id'] ?? '';
            if ($empId !== '') {
                $sql1 = "SELECT firstname, lastname, department, designation FROM employee WHERE emp_id='" . esc($conn, $empId) . "' LIMIT 1";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    $row1 = $result1->fetch_assoc();
                    $manager['firstname'] = $manager['firstname'] ?? ($row1['firstname'] ?? '');
                    $manager['lastname'] = $manager['lastname'] ?? ($row1['lastname'] ?? '');
                    $manager['emp_name'] = trim(($manager['firstname'] ?? '') . ' ' . ($manager['lastname'] ?? ''));
                    $manager['department'] = $manager['department'] ?? ($row1['department'] ?? '');
                    $manager['designation'] = $manager['designation'] ?? ($row1['designation'] ?? '');
                }
            }
            $output[] = $manager;
        }
        return $output;
    }

    function decodeJsonField($value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        $decodedObj = json_decode($value);
        if (is_array($decodedObj)) {
            $arr = [];
            foreach ($decodedObj as $item) {
                $arr[] = is_object($item) ? (array) $item : $item;
            }
            return $arr;
        }
        return [];
    }

    function decodeRow($row)
    {
        $row['participants'] = decodeJsonField($row['participants'] ?? null);
        $row['agenda'] = decodeJsonField($row['agenda'] ?? null);
        $row['mom_discussions'] = decodeJsonField($row['mom_discussions'] ?? null);
        $row['action_items'] = decodeJsonField($row['action_items'] ?? null);
        return $row;
    }

    function nextMeetingNo($conn)
    {
        $year = date('Y');
        $prefix = 'MR-' . $year . '-';
        $sql = "SELECT meeting_no FROM qa_mgmt_quality_review WHERE meeting_no LIKE '" . esc($conn, $prefix) . "%' ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $next = 1;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $parts = explode('-', $row['meeting_no']);
            $last = intval(end($parts));
            $next = $last + 1;
        }
        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    function ensureTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS qa_mgmt_quality_review (
            id INT(11) NOT NULL AUTO_INCREMENT,
            meeting_no VARCHAR(50) DEFAULT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-042-A',
            meeting_in VARCHAR(255) DEFAULT NULL,
            meeting_date DATE DEFAULT NULL,
            meeting_time VARCHAR(20) DEFAULT NULL,
            meeting_venue VARCHAR(255) DEFAULT NULL,
            review_period VARCHAR(100) DEFAULT NULL,
            meeting_chairperson VARCHAR(255) DEFAULT NULL,
            qa_representative VARCHAR(255) DEFAULT NULL,
            representative VARCHAR(255) DEFAULT NULL,
            participants LONGTEXT DEFAULT NULL,
            agenda LONGTEXT DEFAULT NULL,
            mom_discussions LONGTEXT DEFAULT NULL,
            action_items LONGTEXT DEFAULT NULL,
            remark TEXT DEFAULT NULL,
            conclusions TEXT DEFAULT NULL,
            next_review_date DATE DEFAULT NULL,
            mom_prepared_by VARCHAR(255) DEFAULT NULL,
            qa_verified_by VARCHAR(255) DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            start_by VARCHAR(100) DEFAULT NULL,
            start_date DATETIME DEFAULT NULL,
            complete_by VARCHAR(100) DEFAULT NULL,
            complete_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);

        $conn->query("CREATE TABLE IF NOT EXISTS qa_mgmt_review_notifications (
            id INT(11) NOT NULL AUTO_INCREMENT,
            meeting_id INT(11) DEFAULT NULL,
            meeting_no VARCHAR(50) DEFAULT NULL,
            emp_id VARCHAR(100) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            is_read VARCHAR(10) DEFAULT 'No',
            entry_date DATETIME DEFAULT NULL,
            read_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_emp_read (emp_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS qa_mgmt_assessment (
            id INT(11) NOT NULL AUTO_INCREMENT,
            assessment_no VARCHAR(50) DEFAULT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-042-B',
            review_period VARCHAR(100) DEFAULT NULL,
            review_year VARCHAR(20) DEFAULT NULL,
            period_from DATE DEFAULT NULL,
            period_to DATE DEFAULT NULL,
            prepared_by VARCHAR(255) DEFAULT NULL,
            prepared_date DATETIME DEFAULT NULL,
            indicators LONGTEXT DEFAULT NULL,
            capa_actions LONGTEXT DEFAULT NULL,
            overall_conclusion TEXT DEFAULT NULL,
            recommendations TEXT DEFAULT NULL,
            compliance_notes TEXT DEFAULT NULL,
            mrt_comment TEXT DEFAULT NULL,
            mrt_reviewed_by VARCHAR(255) DEFAULT NULL,
            mrt_reviewed_date DATETIME DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'pending_mrt',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return true;
    }

    function nextAssessmentNo($conn)
    {
        $year = date('Y');
        $prefix = 'ASM-' . $year . '-';
        $sql = "SELECT assessment_no FROM qa_mgmt_assessment WHERE assessment_no LIKE '" . esc($conn, $prefix) . "%' ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        $next = 1;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $parts = explode('-', $row['assessment_no']);
            $last = intval(end($parts));
            $next = $last + 1;
        }
        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    function decodeAssessmentRow($row)
    {
        $row['indicators'] = decodeJsonField($row['indicators'] ?? null);
        $row['capa_actions'] = decodeJsonField($row['capa_actions'] ?? null);
        return $row;
    }

    function createParticipantNotifications($conn, $meetingId, $meetingNo, $meetingDate, $meetingTime, $meetingIn, $participants, $entryDate)
    {
        if (!is_array($participants)) {
            return;
        }
        foreach ($participants as $p) {
            if (is_object($p)) {
                $p = (array) $p;
            }
            $empId = $p['emp_id'] ?? '';
            if ($empId === '') {
                continue;
            }
            $msg = "You have been invited to Management Review Meeting ($meetingNo) on "
                . date('d-m-Y', strtotime($meetingDate ?: date('Y-m-d')))
                . (!empty($meetingTime) ? " at $meetingTime" : '')
                . (!empty($meetingIn) ? " (" . $meetingIn . ")" : '')
                . ". Please check QA → Management Review of Quality Systems.";
            $sql = "INSERT INTO qa_mgmt_review_notifications (meeting_id, meeting_no, emp_id, message, is_read, entry_date)
                    VALUES (
                        '" . esc($conn, $meetingId) . "',
                        '" . esc($conn, $meetingNo) . "',
                        '" . esc($conn, $empId) . "',
                        '" . esc($conn, $msg) . "',
                        'No',
                        '$entryDate'
                    )";
            $conn->query($sql);
        }
    }

    ensureTable($conn);

    if (($_GET['type'] ?? '') === 'saveAgenda') {
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $meetingNo = nextMeetingNo($conn);
        $participants = json_encode($input['participants'] ?? []);
        $agendas = json_encode($input['agendas'] ?? []);
        $sql = "INSERT INTO qa_mgmt_quality_review (
            meeting_no, form_no, meeting_in, meeting_date, meeting_time, meeting_venue, review_period,
            meeting_chairperson, qa_representative, representative, participants, agenda, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $meetingNo) . "',
            'FQA-042-A',
            '" . esc($conn, $input['meeting_in'] ?? '') . "',
            '" . esc($conn, $input['meeting_date'] ?? '') . "',
            '" . esc($conn, $input['meeting_time'] ?? '') . "',
            '" . esc($conn, $input['meeting_venue'] ?? '') . "',
            '" . esc($conn, $input['review_period'] ?? '') . "',
            '" . esc($conn, $input['meeting_chairperson'] ?? '') . "',
            '" . esc($conn, $input['qa_representative'] ?? '') . "',
            '" . esc($conn, $input['representative'] ?? '') . "',
            '" . esc($conn, $participants) . "',
            '" . esc($conn, $agendas) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'pending'
        )";
        $ok = $conn->query($sql);
        if ($ok) {
            $newId = $conn->insert_id;
            createParticipantNotifications(
                $conn,
                $newId,
                $meetingNo,
                $input['meeting_date'] ?? '',
                $input['meeting_time'] ?? '',
                $input['meeting_in'] ?? '',
                $input['participants'] ?? [],
                $entry_date
            );
            echo json_encode(['status' => 'success', 'meeting_no' => $meetingNo, 'id' => $newId]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } elseif (($_GET['type'] ?? '') === 'getPendingMeetings') {
        $output = [];
        try {
            $sql = "SELECT * FROM qa_mgmt_quality_review WHERE status='pending' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result === false) {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
                exit;
            }
            while ($row = $result->fetch_assoc()) {
                $row = decodeRow($row);
                $row['participants'] = enrichManagers($conn, $row['participants']);
                $output[] = $row;
            }
            echo json_encode($output);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif (($_GET['type'] ?? '') === 'startMeeting') {
        $id = esc($conn, $_GET['id'] ?? '');
        $sql = "UPDATE qa_mgmt_quality_review SET status='start', start_by='" . esc($conn, $_GET['emp_id']) . "', start_date='$entry_date' WHERE id='$id'";
        echo $conn->query($sql) ? json_encode(['status' => 'success']) : json_encode(['status' => $conn->error]);
    } elseif (($_GET['type'] ?? '') === 'getInprocessMeetings') {
        $output = [];
        try {
            $sql = "SELECT * FROM qa_mgmt_quality_review WHERE status='start' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result === false) {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
                exit;
            }
            while ($row = $result->fetch_assoc()) {
                $row = decodeRow($row);
                $row['participants'] = enrichManagers($conn, $row['participants']);
                $output[] = $row;
            }
            echo json_encode($output);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif (($_GET['type'] ?? '') === 'completeMeeting') {
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id'] ?? '');
        $participants = json_encode($input['participants'] ?? []);
        $momDiscussions = json_encode($input['mom_discussions'] ?? []);
        $actionItems = json_encode($input['action_items'] ?? []);
        $sql = "UPDATE qa_mgmt_quality_review SET
            status='complete',
            form_no='FQA-042-B',
            participants='" . esc($conn, $participants) . "',
            mom_discussions='" . esc($conn, $momDiscussions) . "',
            action_items='" . esc($conn, $actionItems) . "',
            remark='" . esc($conn, $input['remark'] ?? '') . "',
            conclusions='" . esc($conn, $input['conclusions'] ?? '') . "',
            next_review_date='" . esc($conn, $input['next_review_date'] ?? '') . "',
            mom_prepared_by='" . esc($conn, $input['mom_prepared_by'] ?? getEmpStamp($conn, $_GET['emp_id'])) . "',
            complete_by='" . esc($conn, $_GET['emp_id']) . "',
            complete_date='$entry_date'
            WHERE id='$id'";
        echo $conn->query($sql) ? json_encode(['status' => 'success']) : json_encode(['status' => $conn->error]);
    } elseif (($_GET['type'] ?? '') === 'getMeetingsLog') {
        $output = [];
        try {
            $fromDate = esc($conn, $_GET['from_date'] ?? '');
            $toDate = esc($conn, $_GET['to_date'] ?? '');
            $dateSql = '';
            if ($fromDate !== '' && $toDate !== '') {
                $dateSql = " AND DATE(meeting_date) BETWEEN '$fromDate' AND '$toDate'";
            }
            $sql = "SELECT * FROM qa_mgmt_quality_review WHERE 1=1 $dateSql ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result === false) {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
                exit;
            }
            while ($row = $result->fetch_assoc()) {
                $row = decodeRow($row);
                $row['participants'] = enrichManagers($conn, $row['participants']);
                $output[] = $row;
            }
            echo json_encode($output);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif (($_GET['type'] ?? '') === 'getManagers') {
        // All active employees for Participants / Responsible Person dropdowns
        $output = [];
        $sql = "SELECT emp_id, firstname, lastname, department, designation, status
                FROM employee
                WHERE status IS NULL OR status='' OR LOWER(status)='active'
                ORDER BY department ASC, firstname ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } elseif (($_GET['type'] ?? '') === 'getMyMeetingNotifications') {
        $output = [];
        $empId = esc($conn, $_GET['emp_id'] ?? '');
        $sql = "SELECT * FROM qa_mgmt_review_notifications WHERE emp_id='$empId' AND is_read='No' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } elseif (($_GET['type'] ?? '') === 'ackMeetingNotification') {
        $id = esc($conn, $_GET['id'] ?? ($input['id'] ?? ''));
        $empId = esc($conn, $_GET['emp_id'] ?? '');
        $sql = "UPDATE qa_mgmt_review_notifications SET is_read='Yes', read_date='$entry_date'
                WHERE id='$id' AND emp_id='$empId'";
        echo $conn->query($sql) ? json_encode(['status' => 'success']) : json_encode(['status' => $conn->error]);
    } elseif (($_GET['type'] ?? '') === 'getMyMomMeetings') {
        $output = [];
        $empId = esc($conn, $_GET['emp_id'] ?? '');
        $sql = "SELECT * FROM qa_mgmt_quality_review WHERE status='complete' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $decoded = decodeRow($row);
                $decoded['participants'] = enrichManagers($conn, $decoded['participants']);
                $isParticipant = false;
                foreach ($decoded['participants'] as $p) {
                    $pid = is_array($p) ? ($p['emp_id'] ?? '') : (is_object($p) ? ($p->emp_id ?? '') : '');
                    if ((string) $pid === (string) $empId) {
                        $isParticipant = true;
                        break;
                    }
                }
                if ($isParticipant || $empId === '') {
                    $output[] = $decoded;
                }
            }
        }
        echo json_encode($output);
    } elseif (($_GET['type'] ?? '') === 'stampQaVerified') {
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id'] ?? '');
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $sql = "UPDATE qa_mgmt_quality_review SET qa_verified_by='" . esc($conn, $stamp) . "' WHERE id='$id'";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'qa_verified_by' => $stamp]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } elseif (($_GET['type'] ?? '') === 'saveAssessment') {
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $assessmentNo = nextAssessmentNo($conn);
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $indicators = json_encode($input['indicators'] ?? []);
        $capa = json_encode($input['capa_actions'] ?? []);
        $sql = "INSERT INTO qa_mgmt_assessment (
            assessment_no, form_no, review_period, review_year, period_from, period_to,
            prepared_by, prepared_date, indicators, capa_actions, overall_conclusion,
            recommendations, compliance_notes, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $assessmentNo) . "',
            'FQA-042-B',
            '" . esc($conn, $input['review_period'] ?? '') . "',
            '" . esc($conn, $input['review_year'] ?? date('Y')) . "',
            '" . esc($conn, $input['period_from'] ?? '') . "',
            '" . esc($conn, $input['period_to'] ?? '') . "',
            '" . esc($conn, $input['prepared_by'] ?? $stamp) . "',
            '$entry_date',
            '" . esc($conn, $indicators) . "',
            '" . esc($conn, $capa) . "',
            '" . esc($conn, $input['overall_conclusion'] ?? '') . "',
            '" . esc($conn, $input['recommendations'] ?? '') . "',
            '" . esc($conn, $input['compliance_notes'] ?? '') . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'pending_mrt'
        )";
        echo $conn->query($sql)
            ? json_encode(['status' => 'success', 'assessment_no' => $assessmentNo, 'id' => $conn->insert_id])
            : json_encode(['status' => $conn->error]);
    } elseif (($_GET['type'] ?? '') === 'getPendingAssessments') {
        $output = [];
        $sql = "SELECT * FROM qa_mgmt_assessment WHERE status='pending_mrt' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = decodeAssessmentRow($row);
            }
        }
        echo json_encode($output);
    } elseif (($_GET['type'] ?? '') === 'approveAssessment') {
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id'] ?? '');
        $action = strtolower($input['action'] ?? 'approve');
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $status = $action === 'return' ? 'returned' : 'approved';
        $sql = "UPDATE qa_mgmt_assessment SET
            status='$status',
            mrt_comment='" . esc($conn, $input['mrt_comment'] ?? '') . "',
            mrt_reviewed_by='" . esc($conn, $stamp) . "',
            mrt_reviewed_date='$entry_date'
            WHERE id='$id'";
        echo $conn->query($sql) ? json_encode(['status' => 'success', 'new_status' => $status]) : json_encode(['status' => $conn->error]);
    } elseif (($_GET['type'] ?? '') === 'getAssessmentLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateSql = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateSql = " AND DATE(entry_date) BETWEEN '$fromDate' AND '$toDate'";
        }
        $sql = "SELECT * FROM qa_mgmt_assessment WHERE 1=1 $dateSql ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = decodeAssessmentRow($row);
            }
        }
        echo json_encode($output);
    } elseif (($_GET['type'] ?? '') === 'downloadAssessmentPdf') {
        $id = esc($conn, $_GET['id'] ?? '');
        $_GET['filename'] = 'Management Review Assessment FQA-042-B';
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $sql = "SELECT * FROM qa_mgmt_assessment WHERE id='$id' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $row = decodeAssessmentRow($result->fetch_assoc());
        $html = formHeaderHtml('MANAGEMENT REVIEW FORM - ASSESSMENT OF PERFORMANCE INDICATORS', 'FQA-042-B');
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:20%; background-color:#f5f7fa;"><b>Assessment No.</b></td>
                <td style="width:30%;">' . cell($row['assessment_no']) . '</td>
                <td style="width:20%; background-color:#f5f7fa;"><b>Review Period</b></td>
                <td style="width:30%;">' . cell($row['review_period']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Review Year</b></td>
                <td>' . cell($row['review_year']) . '</td>
                <td style="background-color:#f5f7fa;"><b>Status</b></td>
                <td>' . cell($row['status']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Period From</b></td>
                <td>' . (!empty($row['period_from']) ? date('d-m-Y', strtotime($row['period_from'])) : '') . '</td>
                <td style="background-color:#f5f7fa;"><b>Period To</b></td>
                <td>' . (!empty($row['period_to']) ? date('d-m-Y', strtotime($row['period_to'])) : '') . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Prepared By</b></td>
                <td colspan="3">' . cell($row['prepared_by']) . '</td>
            </tr>
        </table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr><td colspan="5" style="text-align:center; font-weight:bold; background-color:#e8eef5;">PERFORMANCE INDICATORS ASSESSMENT</td></tr>
            <tr style="background-color:#f5f7fa;">
                <td style="width:6%; text-align:center;"><b>Sr.</b></td>
                <td style="width:24%;"><b>Indicator</b></td>
                <td style="width:14%;"><b>Status</b></td>
                <td style="width:28%;"><b>Summary / Trend</b></td>
                <td style="width:28%;"><b>Remarks / Action</b></td>
            </tr>';
        $indicators = is_array($row['indicators']) ? $row['indicators'] : [];
        for ($i = 0; $i < count($indicators); $i++) {
            $ind = $indicators[$i];
            $html .= '<tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td>' . cell(fieldVal($ind, 'name')) . '</td>
                <td style="text-align:center;">' . cell(fieldVal($ind, 'status')) . '</td>
                <td>' . cell(fieldVal($ind, 'summary')) . '</td>
                <td>' . cell(fieldVal($ind, 'remarks')) . '</td>
            </tr>';
        }
        $html .= '</table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr><td colspan="4" style="text-align:center; font-weight:bold; background-color:#e8eef5;">CORRECTIVE / PREVENTIVE ACTIONS</td></tr>
            <tr style="background-color:#f5f7fa;">
                <td style="width:8%;"><b>Sr.</b></td>
                <td style="width:42%;"><b>Action</b></td>
                <td style="width:30%;"><b>Responsible</b></td>
                <td style="width:20%;"><b>Target Date</b></td>
            </tr>';
        $actions = is_array($row['capa_actions']) ? $row['capa_actions'] : [];
        if (count($actions) === 0) {
            $html .= '<tr><td colspan="4" style="text-align:center;">No CAPA actions</td></tr>';
        }
        for ($i = 0; $i < count($actions); $i++) {
            $act = $actions[$i];
            $td = fieldVal($act, 'target_date');
            $html .= '<tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td>' . cell(fieldVal($act, 'action')) . '</td>
                <td>' . cell(fieldVal($act, 'responsible')) . '</td>
                <td style="text-align:center;">' . cell($td ? date('d-m-Y', strtotime($td)) : '') . '</td>
            </tr>';
        }
        $html .= '</table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr><td style="width:25%; background-color:#f5f7fa;"><b>Overall Conclusion</b></td><td colspan="3">' . cell($row['overall_conclusion']) . '</td></tr>
            <tr><td style="background-color:#f5f7fa;"><b>Recommendations</b></td><td colspan="3">' . cell($row['recommendations']) . '</td></tr>
            <tr><td style="background-color:#f5f7fa;"><b>Compliance Notes</b></td><td colspan="3">' . cell($row['compliance_notes']) . '</td></tr>
            <tr><td style="background-color:#f5f7fa;"><b>MRT Comment</b></td><td colspan="3">' . cell($row['mrt_comment']) . '</td></tr>
            <tr><td style="background-color:#f5f7fa;"><b>MRT Reviewed By</b></td><td colspan="3">' . cell($row['mrt_reviewed_by']) . '</td></tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Assessment-' . preg_replace('/[^A-Za-z0-9\\-]/', '_', $row['assessment_no'] ?? $id) . '.pdf', 'I');
    } elseif (($_GET['type'] ?? '') === 'downloadMeetingsLogPdf') {
        $_GET['filename'] = 'Management Review Meeting Log';
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $html = formHeaderHtml('MANAGEMENT REVIEW OF QUALITY SYSTEMS - MEETING LOG', 'FQA-042-B');
        $html .= '<table cellpadding="3" border="1"><tr style="font-weight:bold;">
            <td>Meeting No.</td><td>Meeting In</td><td>Date</td><td>Time</td><td>Review Period</td><td>Status</td></tr>';
        $sql = "SELECT * FROM qa_mgmt_quality_review ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($row['meeting_no']) . '</td>
                    <td>' . htmlspecialchars($row['meeting_in']) . '</td>
                    <td>' . date('d-m-Y', strtotime($row['meeting_date'])) . '</td>
                    <td>' . htmlspecialchars($row['meeting_time']) . '</td>
                    <td>' . htmlspecialchars($row['review_period']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    } elseif (($_GET['type'] ?? '') === 'downloadMomPdf') {
        $id = esc($conn, $_GET['id'] ?? '');
        $_GET['filename'] = 'Minutes of Meeting FQA-042-B';
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $sql = "SELECT * FROM qa_mgmt_quality_review WHERE id='$id' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $row = decodeRow($result->fetch_assoc());
        $row['participants'] = enrichManagers($conn, $row['participants']);
        $meetingDate = !empty($row['meeting_date']) ? date('d-m-Y', strtotime($row['meeting_date'])) : '';
        $nextDate = !empty($row['next_review_date']) ? date('d-m-Y', strtotime($row['next_review_date'])) : '';

        $html = formHeaderHtml('MINUTES OF MANAGEMENT REVIEW MEETING', 'FQA-042-B');
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:20%; background-color:#f5f7fa;"><b>Meeting No.</b></td>
                <td style="width:30%;">' . cell($row['meeting_no']) . '</td>
                <td style="width:20%; background-color:#f5f7fa;"><b>Meeting Date</b></td>
                <td style="width:30%;">' . cell($meetingDate) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Meeting Time</b></td>
                <td>' . cell($row['meeting_time']) . '</td>
                <td style="background-color:#f5f7fa;"><b>Review Period</b></td>
                <td>' . cell($row['review_period']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Meeting In</b></td>
                <td>' . cell($row['meeting_in']) . '</td>
                <td style="background-color:#f5f7fa;"><b>Venue</b></td>
                <td>' . cell($row['meeting_venue']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Chairperson</b></td>
                <td>' . cell($row['meeting_chairperson']) . '</td>
                <td style="background-color:#f5f7fa;"><b>QA Representative</b></td>
                <td>' . cell($row['qa_representative']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Representative</b></td>
                <td colspan="3">' . cell($row['representative']) . '</td>
            </tr>
        </table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr><td colspan="5" style="text-align:center; font-weight:bold; background-color:#e8eef5;">PARTICIPANTS / ATTENDANCE</td></tr>
            <tr style="background-color:#f5f7fa;">
                <td style="width:8%; text-align:center;"><b>Sr.</b></td>
                <td style="width:28%;"><b>Name</b></td>
                <td style="width:24%;"><b>Department</b></td>
                <td style="width:24%;"><b>Designation</b></td>
                <td style="width:16%;"><b>Attendance</b></td>
            </tr>';
        $participants = is_array($row['participants']) ? $row['participants'] : [];
        if (count($participants) === 0) {
            $html .= '<tr><td colspan="5" style="text-align:center;">No participants</td></tr>';
        }
        for ($i = 0; $i < count($participants); $i++) {
            $p = $participants[$i];
            $name = trim(fieldVal($p, 'firstname') . ' ' . fieldVal($p, 'lastname'));
            if ($name === '') {
                $name = fieldVal($p, 'emp_name');
            }
            $html .= '<tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td>' . cell($name) . '</td>
                <td>' . cell(fieldVal($p, 'department')) . '</td>
                <td>' . cell(fieldVal($p, 'designation')) . '</td>
                <td style="text-align:center;">' . cell(fieldVal($p, 'attendance')) . '</td>
            </tr>';
        }
        $html .= '</table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr><td colspan="5" style="text-align:center; font-weight:bold; background-color:#e8eef5;">AGENDA DISCUSSION &amp; MINUTES</td></tr>
            <tr style="background-color:#f5f7fa;">
                <td style="width:6%; text-align:center;"><b>Sr.</b></td>
                <td style="width:20%;"><b>Agenda</b></td>
                <td style="width:20%;"><b>Details</b></td>
                <td style="width:27%;"><b>Discussion / Minutes</b></td>
                <td style="width:27%;"><b>Decision</b></td>
            </tr>';
        $agenda = is_array($row['agenda']) ? $row['agenda'] : [];
        $discussions = is_array($row['mom_discussions']) ? $row['mom_discussions'] : [];
        if (count($agenda) === 0) {
            $html .= '<tr><td colspan="5" style="text-align:center;">No agenda items</td></tr>';
        }
        for ($i = 0; $i < count($agenda); $i++) {
            $a = $agenda[$i];
            $d = $discussions[$i] ?? null;
            $html .= '<tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td>' . cell(fieldVal($a, 'agenda')) . '</td>
                <td>' . cell(fieldVal($a, 'detail')) . '</td>
                <td>' . cell(fieldVal($d, 'discussion')) . '</td>
                <td>' . cell(fieldVal($d, 'decision')) . '</td>
            </tr>';
        }
        $html .= '</table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr><td colspan="4" style="text-align:center; font-weight:bold; background-color:#e8eef5;">ACTION ITEMS</td></tr>
            <tr style="background-color:#f5f7fa;">
                <td style="width:8%; text-align:center;"><b>Sr.</b></td>
                <td style="width:42%;"><b>Action</b></td>
                <td style="width:30%;"><b>Responsible Person</b></td>
                <td style="width:20%;"><b>Target Date</b></td>
            </tr>';
        $actions = is_array($row['action_items']) ? $row['action_items'] : [];
        if (count($actions) === 0) {
            $html .= '<tr><td colspan="4" style="text-align:center;">No action items</td></tr>';
        }
        for ($i = 0; $i < count($actions); $i++) {
            $act = $actions[$i];
            $target = fieldVal($act, 'target_date');
            $targetFmt = $target ? date('d-m-Y', strtotime($target)) : '';
            $html .= '<tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td>' . cell(fieldVal($act, 'action')) . '</td>
                <td>' . cell(fieldVal($act, 'responsible')) . '</td>
                <td style="text-align:center;">' . cell($targetFmt) . '</td>
            </tr>';
        }
        $html .= '</table><br/>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:25%; background-color:#f5f7fa;"><b>Conclusions</b></td>
                <td colspan="3">' . cell($row['conclusions']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Remarks</b></td>
                <td colspan="3">' . cell($row['remark']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>Next Review Date</b></td>
                <td style="width:25%;">' . cell($nextDate) . '</td>
                <td style="width:25%; background-color:#f5f7fa;"><b>Status</b></td>
                <td style="width:25%;">' . cell($row['status']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>MOM Prepared By</b></td>
                <td colspan="3">' . cell($row['mom_prepared_by']) . '</td>
            </tr>
            <tr>
                <td style="background-color:#f5f7fa;"><b>QA Verified By</b></td>
                <td colspan="3">' . cell($row['qa_verified_by']) . '</td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MOM-' . preg_replace('/[^A-Za-z0-9\\-]/', '_', $row['meeting_no'] ?? $id) . '.pdf', 'I');
    }
}

$conn->close();
?>
