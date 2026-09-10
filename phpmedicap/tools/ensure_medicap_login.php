<?php
/**
 * One-shot: ensure plant 1126 can login with Id Medicap / Password Medicap@47#.
 * Also unlocks Master/master lockouts.
 *
 * Call:
 *   .../tools/ensure_medicap_login.php?key=zuma-maint-2026&plant_id=1126
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$key = (string)($_GET['key'] ?? '');
if (!hash_equals('zuma-maint-2026', $key)) {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    exit;
}

$_GET['plant_id'] = isset($_GET['plant_id']) && $_GET['plant_id'] !== '' ? $_GET['plant_id'] : '1126';
require dirname(__DIR__) . '/db1.php';
require_once dirname(__DIR__) . '/shared/login_security_helper.php';
require_once dirname(__DIR__) . '/shared/emp_rights_helpers.php';

if (function_exists('zuma_login_security_ensure_schema')) {
    zuma_login_security_ensure_schema($conn);
}

$plantId = $conn->real_escape_string((string)$_GET['plant_id']);
$loginId = 'Medicap';
$password = 'Medicap@47#';
$actions = array();

// Unlock common admin accounts from failed-attempt lockouts.
$conn->query(
    "UPDATE employee SET login_fail_count=0, login_locked_until=NULL
     WHERE plant_id='".$plantId."' AND LOWER(emp_id) IN ('master','medicap')"
);
$actions[] = 'unlocked master/medicap fail counters';

$hasEmpId1 = false;
$col = @$conn->query("SHOW COLUMNS FROM employee LIKE 'emp_id1'");
if ($col && $col->num_rows > 0) {
    $hasEmpId1 = true;
}

$res = $conn->query(
    "SELECT emp_id, status, password, department FROM employee
     WHERE plant_id='".$plantId."' AND LOWER(emp_id)='medicap' LIMIT 1"
);

if ($res && $res->num_rows > 0) {
    $pwEsc = $conn->real_escape_string($password);
    $ok = $conn->query(
        "UPDATE employee SET password='".$pwEsc."', status='active', ISNEW='NO',
         login_fail_count=0, login_locked_until=NULL
         WHERE plant_id='".$plantId."' AND LOWER(emp_id)='medicap'"
    );
    $actions[] = $ok ? 'updated existing Medicap password/status' : ('update failed: '.$conn->error);
    $empId = 'Medicap';
} else {
    // Prefer cloning rights profile from Master if present.
    $master = null;
    $mRes = $conn->query(
        "SELECT * FROM employee WHERE plant_id='".$plantId."' AND LOWER(emp_id)='master' LIMIT 1"
    );
    if ($mRes && $mRes->num_rows > 0) {
        $master = $mRes->fetch_assoc();
        if ($hasEmpId1) {
            $conn->query(
                "UPDATE employee SET emp_id1='Medicap' WHERE plant_id='".$plantId."' AND LOWER(emp_id)='master'
                 AND (emp_id1 IS NULL OR emp_id1='' OR LOWER(emp_id1)='medicap')"
            );
            $actions[] = 'set Master.emp_id1=Medicap alias';
        }
    }

    $empIdEsc = $conn->real_escape_string($loginId);
    $pwEsc = $conn->real_escape_string($password);
    $dept = $conn->real_escape_string($master['department'] ?? 'Master');
    $desig = $conn->real_escape_string($master['designation'] ?? 'Master Administrator');
    $first = $conn->real_escape_string($master['firstname'] ?? 'Medicap');
    $sql = "INSERT INTO employee
        (plant_id, emp_id, firstname, department, designation, password, status, ISNEW, mpin, entry_by, entry_date)
        VALUES
        ('".$plantId."', '".$empIdEsc."', '".$first."', '".$dept."', '".$desig."', '".$pwEsc."',
         'active', 'NO', '1234', 'system', NOW())";
    $ok = $conn->query($sql);
    if (!$ok) {
        // Minimal column set may differ — try alternate.
        $sql2 = "INSERT INTO employee (plant_id, emp_id, firstname, department, password, status)
                 VALUES ('".$plantId."', '".$empIdEsc."', 'Medicap', 'Master', '".$pwEsc."', 'active')";
        $ok = $conn->query($sql2);
        $actions[] = $ok ? 'inserted Medicap (minimal columns)' : ('insert failed: '.$conn->error);
    } else {
        $actions[] = 'inserted Medicap employee';
    }
    $empId = $loginId;

    // Also keep Master password usable if it was Master@47#.
    if ($master) {
        // Do not overwrite Master password; only clear lock.
        $actions[] = 'left Master password unchanged';
    }
}

if (function_exists('gw_ensure_hr_emp_rights')) {
    gw_ensure_hr_emp_rights($conn, $empId, $plantId, $empId);
    $actions[] = 'ensured emp_rights for '.$empId;
}
if (function_exists('gw_ensure_employee_department_rights')) {
    gw_ensure_employee_department_rights($conn, $empId, $plantId, $empId);
}
if (function_exists('gw_upgrade_checker_rights_for_active_users')) {
    gw_upgrade_checker_rights_for_active_users($conn, $empId, $plantId);
}

// Elevate Medicap like a plant admin when Master rights exist.
$rights = $conn->query(
    "SELECT * FROM emp_rights WHERE plant_id='".$plantId."' AND LOWER(emp_id)='master' LIMIT 1"
);
if ($rights && $rights->num_rows > 0) {
    $r = $rights->fetch_assoc();
    $cols = array('isuser','ischecker','isapprover','qms_approver','dept_head');
    $sets = array();
    foreach ($cols as $c) {
        if (array_key_exists($c, $r)) {
            $sets[] = "`".$c."`='".$conn->real_escape_string((string)$r[$c])."'";
        }
    }
    if ($sets) {
        $conn->query(
            "UPDATE emp_rights SET ".implode(',', $sets)."
             WHERE plant_id='".$plantId."' AND emp_id='".$conn->real_escape_string($empId)."'"
        );
        $actions[] = 'copied Master rights flags onto Medicap';
    }
}

$verify = $conn->query(
    "SELECT emp_id, status, password FROM employee
     WHERE plant_id='".$plantId."' AND LOWER(emp_id)='medicap' LIMIT 1"
);
$info = ($verify && $verify->num_rows > 0) ? $verify->fetch_assoc() : null;

echo json_encode(array(
    'status' => ($info && ($info['password'] ?? '') === $password && strtolower($info['status']) === 'active')
        ? 'success' : 'partial',
    'plant_id' => $plantId,
    'login_id' => 'Medicap',
    'password_set' => $password,
    'employee' => $info ? array(
        'emp_id' => $info['emp_id'],
        'status' => $info['status'],
        'password_matches' => (($info['password'] ?? '') === $password),
    ) : null,
    'actions' => $actions,
));
