<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF);

$token = isset($_GET['token']) ? $_GET['token'] : '';
$timestamp = time();
$entry_date = date('Y-m-d H:i:s', $timestamp);

$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = isset($string[1]) ? $string[1] : '';
        break;
    }
}

function qc_audit_is_qc_department($dept)
{
    $norm = strtolower(preg_replace('/\s+/', '', trim((string)$dept)));
    return $norm === 'qualitycontrol' || $norm === 'qc';
}

function qc_audit_route_module($frontendUrl)
{
    $route = trim(strtolower((string)$frontendUrl));
    $route = preg_replace('#^/#', '', $route);
    if ($route === '' || $route === '-') {
        return 'QC';
    }
    if (strpos($route, 'qc-audit-trail') === 0 || $route === 'qc-audit-trail') {
        return 'QC / Audit Trail';
    }
    if (strpos($route, 'qc-calibration') === 0 || strpos($route, 'calibration') !== false) {
        return 'QC / Calibration';
    }
    if (strpos($route, 'qc-sampling') === 0 || strpos($route, 'sampling') !== false) {
        return 'QC / Sampling';
    }
    if ($route === 'qc' || strpos($route, 'qc-dashboard') === 0 || $route === 'dashboard') {
        return 'QC / Dashboard';
    }
    if (strpos($route, 'qc-') === 0) {
        $tail = substr($route, 3);
        $tail = trim(str_replace('-', ' ', $tail));
        if ($tail !== '') {
            return 'QC / ' . ucwords($tail);
        }
    }
    return 'QC';
}

function qc_audit_form_label($frontendUrl, $action)
{
    $route = trim(strtolower((string)$frontendUrl));
    $route = preg_replace('#^/#', '', $route);
    $map = array(
        'qc-audit-trail' => 'Control Hub',
        'qc' => 'Enterprise',
        'qc-dashboard' => 'Dashboard',
    );
    if (isset($map[$route])) {
        return $map[$route];
    }
    if (strpos($route, 'qc-calibration-') === 0) {
        $eq = substr($route, strlen('qc-calibration-'));
        return 'Eq / ' . ucwords(str_replace('-', ' ', $eq));
    }
    if ($route !== '' && $route !== '-') {
        return ucwords(str_replace('-', ' ', $route));
    }
    $action = trim((string)$action);
    if ($action === '') {
        return 'Unknown';
    }
    return ucwords(str_replace('_', ' ', $action));
}

function qc_audit_activity_label($method, $action, $isExit = false)
{
    if ($isExit) {
        return 'Form Exit';
    }
    $actionLower = strtolower(trim((string)$action));
    if (strpos($actionLower, 'login') !== false) {
        return 'Login';
    }
    if (strpos($actionLower, 'logout') !== false) {
        return 'Logout';
    }
    $methodUpper = strtoupper(trim((string)$method));
    if ($methodUpper === 'POST' || $methodUpper === 'PUT' || $methodUpper === 'PATCH') {
        return 'Form Save / Submit';
    }
    return 'Form Open / View';
}

function qc_audit_duration_seconds($fromTime, $toTime)
{
    $from = strtotime((string)$fromTime);
    $to = strtotime((string)$toTime);
    if ($from === false || $to === false) {
        return 0;
    }
    return max(0, $to - $from);
}

function qc_audit_build_rows($conn, $fromDate, $toDate, $view = 'hub')
{
    $exclude = array(
        'getQcAuditTrailHub',
        'getAuditTrails',
        'get_audit_log',
        'getrights',
        'getRights',
    );
    $excludeSql = array();
    foreach ($exclude as $ex) {
        $excludeSql[] = "l.action != '" . $conn->real_escape_string($ex) . "'";
    }

    $deptSql = "REPLACE(REPLACE(l.department, '\t', ''), ' ', '') LIKE '%QualityControl%'";
    $dateSql = "DATE(l.actiontime) BETWEEN '" . $conn->real_escape_string($fromDate) . "' AND '" . $conn->real_escape_string($toDate) . "'";

    $viewSql = '';
    if ($view === 'login') {
        $viewSql = " AND (LOWER(l.action) LIKE '%login%' OR LOWER(l.action) LIKE '%logout%')";
    } elseif ($view === 'archive') {
        return array();
    }

    $sql = "SELECT l.action, l.actiontime, l.REMOTE_ADDR, l.emp_id, l.department, l.token, l.method,
            COALESCE(l.frontend_url, '') AS frontend_url,
            TRIM(CONCAT(COALESCE(e.firstname,''), ' ', COALESCE(e.lastname,''))) AS emp_name
            FROM log l
            LEFT JOIN employee e ON l.emp_id = e.emp_id
            WHERE " . $deptSql . "
            AND " . $dateSql . "
            AND (" . implode(' AND ', $excludeSql) . ")" . $viewSql . "
            ORDER BY l.actiontime DESC
            LIMIT 2500";

    $result = $conn->query($sql);
    $raw = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!qc_audit_is_qc_department($row['department'])) {
                continue;
            }
            $raw[] = $row;
        }
    }

    $rows = array();
    $openByKey = array();
    foreach (array_reverse($raw) as $row) {
        $frontendUrl = trim((string)$row['frontend_url']);
        $key = $row['emp_id'] . '|' . ($frontendUrl !== '' ? $frontendUrl : $row['action']);
        $fromTime = $row['actiontime'];
        $toTime = '';
        $durationSec = 0;
        $isExit = false;

        if (isset($openByKey[$key])) {
            $isExit = true;
            $toTime = $fromTime;
            $durationSec = qc_audit_duration_seconds($openByKey[$key]['from_time'], $toTime);
            unset($openByKey[$key]);
        } else {
            $openByKey[$key] = array('from_time' => $fromTime);
        }

        $empName = trim((string)$row['emp_name']);
        if ($empName === '') {
            $empName = trim((string)$row['emp_id']);
        }
        $userLabel = $empName . ' (' . trim((string)$row['emp_id']) . ')';

        $rows[] = array(
            'event_date' => substr($fromTime, 0, 10),
            'module' => qc_audit_route_module($frontendUrl),
            'form' => qc_audit_form_label($frontendUrl, $row['action']),
            'from_time' => $fromTime,
            'to_time' => $isExit ? $toTime : '',
            'activity' => qc_audit_activity_label($row['method'], $row['action'], $isExit),
            'user_label' => $userLabel,
            'ip_address' => $row['REMOTE_ADDR'],
            'duration_sec' => $durationSec,
            'action' => $row['action'],
            'frontend_url' => $frontendUrl,
        );
    }

    return array_reverse($rows);
}

function qc_audit_build_kpis($conn, $fromDate, $toDate, $rows)
{
    $deptSql = "REPLACE(REPLACE(l.department, '\t', ''), ' ', '') LIKE '%QualityControl%'";
    $dateSql = "DATE(l.actiontime) BETWEEN '" . $conn->real_escape_string($fromDate) . "' AND '" . $conn->real_escape_string($toDate) . "'";
    $today = date('Y-m-d');

    $events = 0;
    $todayCount = 0;
    $users = array();
    $modules = array();

    $sql = "SELECT l.emp_id, l.actiontime, COALESCE(l.frontend_url,'') AS frontend_url
            FROM log l
            WHERE " . $deptSql . " AND " . $dateSql . " AND l.action NOT IN ('getQcAuditTrailHub','getAuditTrails','get_audit_log','getrights','getRights')";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events++;
            if (substr($row['actiontime'], 0, 10) === $today) {
                $todayCount++;
            }
            $emp = trim((string)$row['emp_id']);
            if ($emp !== '') {
                $users[$emp] = true;
            }
            $mod = qc_audit_route_module($row['frontend_url']);
            $modules[$mod] = true;
        }
    }

    $openSessions = 0;
    foreach ($rows as $r) {
        if ($r['activity'] === 'Form Open / View' && $r['to_time'] === '') {
            $openSessions++;
        }
    }

    return array(
        'events' => $events,
        'today' => $todayCount,
        'users' => count($users),
        'modules' => count($modules),
        'open_sessions' => $openSessions,
        'archived_reports' => 0,
    );
}

if (isset($_GET['type']) && $_GET['type'] === 'getQcAuditTrailHub') {
    $fromDate = isset($_GET['from_date']) ? trim((string)$_GET['from_date']) : date('Y-m-d', strtotime('-30 days'));
    $toDate = isset($_GET['to_date']) ? trim((string)$_GET['to_date']) : date('Y-m-d');
    $view = isset($_GET['view']) ? trim((string)$_GET['view']) : 'hub';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        $fromDate = date('Y-m-d', strtotime('-30 days'));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $toDate = date('Y-m-d');
    }

    $rows = qc_audit_build_rows($conn, $fromDate, $toDate, $view);
    $kpis = qc_audit_build_kpis($conn, $fromDate, $toDate, $rows);
    $recent = array_slice($rows, 0, 50);

    echo json_encode(array(
        'status' => 'success',
        'department' => 'Quality Control',
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'view' => $view,
        'kpis' => $kpis,
        'rows' => $rows,
        'recent' => $recent,
    ));
    exit;
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request'));
