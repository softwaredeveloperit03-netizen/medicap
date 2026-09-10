<?php
require '../db.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$entry_date = date('Y-m-d H:i:s');
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    $txt = '{"process":"FRONTEND","token":"' . $token . '","action":"' . ($_GET['type'] ?? '') . '","actiontime":"' . $entry_date . '","department":"' . $_GET['department'] . '","emp_id":"' . $_GET['emp_id'] . '","method":"' . $_SERVER['REQUEST_METHOD'] . '","REMOTE_ADDR":"' . $_SERVER['REMOTE_ADDR'] . '"}';
    @file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    function esc($conn, $value)
    {
        return $conn->real_escape_string($value ?? '');
    }

    function ensureDossierWorkflowColumns($conn)
    {
        $check = $conn->query("SHOW COLUMNS FROM dossier_entry LIKE 'wf_status'");
        if (!$check || $check->num_rows === 0) {
            $conn->query("ALTER TABLE dossier_entry ADD COLUMN wf_status VARCHAR(50) DEFAULT 'pending_compilation'");
            $conn->query("UPDATE dossier_entry SET wf_status='pending_compilation' WHERE wf_status IS NULL OR wf_status=''");
        }
        $check2 = $conn->query("SHOW COLUMNS FROM dossier_entry LIKE 'wf_updated_on'");
        if (!$check2 || $check2->num_rows === 0) {
            $conn->query("ALTER TABLE dossier_entry ADD COLUMN wf_updated_on DATETIME DEFAULT NULL");
        }
        $check3 = $conn->query("SHOW COLUMNS FROM dossier_entry LIKE 'wf_updated_by'");
        if (!$check3 || $check3->num_rows === 0) {
            $conn->query("ALTER TABLE dossier_entry ADD COLUMN wf_updated_by VARCHAR(100) DEFAULT NULL");
        }
        $check4 = $conn->query("SHOW COLUMNS FROM dossier_entry LIKE 'renewal_date'");
        if (!$check4 || $check4->num_rows === 0) {
            $conn->query("ALTER TABLE dossier_entry ADD COLUMN renewal_date DATE DEFAULT NULL");
        }
    }

    function queueFilter($queue)
    {
        $queue = strtolower(trim((string) $queue));
        if ($queue === 'compilation' || $queue === 'dossiercompl') {
            return "COALESCE(wf_status,'pending_compilation') IN ('pending_compilation','draft','')";
        }
        if ($queue === 'review' || $queue === 'dossierreview') {
            return "wf_status = 'pending_review'";
        }
        if ($queue === 'approval' || $queue === 'dossierapproval') {
            return "wf_status = 'pending_approval'";
        }
        if ($queue === 'log' || $queue === 'dossierlog') {
            return "wf_status IN ('approved','rejected','completed')";
        }
        if ($queue === 'reminders' || $queue === 'reminder') {
            return "(renewal_date IS NOT NULL OR expected_date IS NOT NULL)";
        }
        // calendar / submission history — all with a date
        return '1=1';
    }

    function decodeRow($row)
    {
        if (isset($row['apis']) && is_string($row['apis'])) {
            $decoded = json_decode($row['apis'], true);
            $row['apis'] = $decoded !== null ? $decoded : $row['apis'];
        }
        return $row;
    }

    ensureDossierWorkflowColumns($conn);

    if (($_GET['type'] ?? '') === 'getDossierQueue') {
        $output = array();
        $queue = $_GET['queue'] ?? 'log';
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $dateFilter = '';
        if ($fromDate !== '' && $toDate !== '') {
            $dateFilter = " AND DATE(COALESCE(expected_date, renewal_date, entry_date)) BETWEEN '$fromDate' AND '$toDate'";
        }
        $reminderExtra = '';
        if (strtolower($queue) === 'reminders' || strtolower($queue) === 'reminder') {
            $reminderExtra = " AND DATE(COALESCE(renewal_date, expected_date)) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
        }

        $sql = "SELECT * FROM dossier_entry
                WHERE " . queueFilter($queue) . " $dateFilter $reminderExtra
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = decodeRow($row);
            }
        }
        echo json_encode($output);
        exit;
    }

    if (($_GET['type'] ?? '') === 'getDossierById') {
        $id = (int) ($_GET['id'] ?? 0);
        $sql = "SELECT * FROM dossier_entry WHERE id='$id' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode(decodeRow($result->fetch_assoc()));
        } else {
            echo json_encode(array());
        }
        exit;
    }

    if (($_GET['type'] ?? '') === 'updateDossierWorkflowStatus') {
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $status = esc($conn, $input['wf_status'] ?? $_GET['wf_status'] ?? '');
        $allowed = array('pending_compilation', 'pending_review', 'pending_approval', 'approved', 'rejected', 'completed');
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            echo json_encode(array('status' => 'Invalid request'));
            exit;
        }
        $sql = "UPDATE dossier_entry SET
            wf_status='$status',
            wf_updated_by='" . esc($conn, $_GET['emp_id']) . "',
            wf_updated_on='$entry_date'
            WHERE id='$id'";
        echo json_encode(array('status' => $conn->query($sql) ? 'success' : $conn->error));
        exit;
    }
}

echo json_encode(array('status' => 'failed', 'message' => 'Unauthorized or unknown request'));
$conn->close();
