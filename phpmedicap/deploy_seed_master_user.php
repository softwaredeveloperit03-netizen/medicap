<?php
/**
 * Seed Master login user for Medicap.
 * Login user id: Master
 * Password: Master@47#
 * Name: Master User
 * Employee ID / code: M001
 *
 * GET: ?key=MedicapSeed1126&plant_id=1126
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'MedicapSeed1126') {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    exit;
}

$plantId = isset($_GET['plant_id']) ? trim($_GET['plant_id']) : '1126';
$loginId = 'Master';
$employeeCode = 'M001';
$fullName = 'Master User';
$password = 'Master@47#';
$mpin = '1234';
$department = 'Master';
$userNo = 'GMP22052';

try {
    require_once __DIR__ . '/db.config.php';
    require_once __DIR__ . '/schema_tables.php';
    require_once __DIR__ . '/shared/emp_rights_helpers.php';

    $cfg = cyclone_get_db_config();
    $dbname = cyclone_resolve_dbname($plantId);
    $conn = new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], $dbname);
    if ($conn->connect_error) {
        throw new Exception('DB connect failed: '.$conn->connect_error);
    }

    if (function_exists('medicap_ensure_schema')) {
        medicap_ensure_schema($conn);
    }
    // Ensure core HR tables exist.
    $conn->query("CREATE TABLE IF NOT EXISTS employee (
        id int(11) NOT NULL AUTO_INCREMENT,
        plant_id varchar(20) DEFAULT NULL,
        emp_id varchar(40) DEFAULT NULL,
        firstname varchar(100) DEFAULT NULL,
        middlename varchar(50) DEFAULT NULL,
        lastname varchar(50) DEFAULT NULL,
        contact_no varchar(30) DEFAULT NULL,
        emp_email varchar(120) DEFAULT NULL,
        email varchar(120) DEFAULT NULL,
        department varchar(80) DEFAULT NULL,
        designation varchar(120) DEFAULT NULL,
        password varchar(120) DEFAULT NULL,
        mpin varchar(20) DEFAULT '1234',
        status varchar(20) DEFAULT 'active',
        ISNEW varchar(10) DEFAULT 'NO',
        entry_by varchar(40) DEFAULT NULL,
        entry_date varchar(40) DEFAULT NULL,
        approve_by varchar(40) DEFAULT NULL,
        approve_date varchar(40) DEFAULT NULL,
        emp_id1 varchar(40) DEFAULT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS emp_rights (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_no text,
        emp_id text,
        department text,
        plant_id text,
        isuser text DEFAULT 'No',
        ischecker text DEFAULT 'No',
        isapprover text DEFAULT 'No',
        qms_approver text DEFAULT 'No',
        dept_head text DEFAULT 'No',
        quality_head text DEFAULT 'No',
        isauditor text DEFAULT 'No',
        plant_head text DEFAULT 'No',
        trainig_cordinator text DEFAULT 'No',
        task_assigner varchar(10) DEFAULT 'No',
        shift_allocator text DEFAULT 'No',
        status text DEFAULT 'approve',
        entry_by text,
        entry_date text,
        main text,
        additional text,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Best-effort add columns used by seed on older schemas.
    $empCols = array();
    $colRes = $conn->query("SHOW COLUMNS FROM employee");
    if ($colRes) {
        while ($col = $colRes->fetch_assoc()) {
            $empCols[$col['Field']] = true;
        }
    }
    $extraEmp = array(
        'emp_id1' => "ALTER TABLE employee ADD COLUMN emp_id1 varchar(40) DEFAULT NULL",
        'email' => "ALTER TABLE employee ADD COLUMN email varchar(120) DEFAULT NULL",
        'mpin' => "ALTER TABLE employee ADD COLUMN mpin varchar(20) DEFAULT '1234'",
        'ISNEW' => "ALTER TABLE employee ADD COLUMN ISNEW varchar(10) DEFAULT 'NO'",
        'designation' => "ALTER TABLE employee ADD COLUMN designation varchar(120) DEFAULT NULL",
    );
    foreach ($extraEmp as $c => $sql) {
        if (!isset($empCols[$c])) {
            @$conn->query($sql);
            $empCols[$c] = true;
        }
    }

    // Fix missing autoincrement / PK on bare tables.
    @$conn->query("ALTER TABLE employee MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
    @$conn->query("ALTER TABLE emp_rights MODIFY id INT(11) NOT NULL AUTO_INCREMENT");

    $plant = $conn->real_escape_string($plantId);
    $empEsc = $conn->real_escape_string($loginId);
    $codeEsc = $conn->real_escape_string($employeeCode);
    $nameEsc = $conn->real_escape_string($fullName);
    $passEsc = $conn->real_escape_string($password);
    $mpinEsc = $conn->real_escape_string($mpin);
    $deptEsc = $conn->real_escape_string($department);
    $userNoEsc = $conn->real_escape_string($userNo);
    $entryDate = date('Y-m-d H:i:s');

    // Upsert by login id Master; keep Employee ID M001 in emp_id1.
    // Prefer the oldest Master row as the single active login identity.
    $existing = $conn->query("SELECT id FROM employee WHERE plant_id='".$plant."' AND emp_id='".$empEsc."' ORDER BY id ASC LIMIT 1");
    if (!$existing || $existing->num_rows === 0) {
        $existing = $conn->query("SELECT id FROM employee WHERE emp_id='".$empEsc."' OR emp_id='".$codeEsc."' OR emp_id1='".$codeEsc."' ORDER BY id ASC LIMIT 1");
    }
    $action = 'inserted';
    if ($existing && $existing->num_rows > 0) {
        $action = 'updated';
        $keepRow = $existing->fetch_assoc();
        $keepId = (int)$keepRow['id'];
        $sets = array(
            "emp_id='".$empEsc."'",
            "firstname='".$nameEsc."'",
            "password='".$passEsc."'",
            "status='active'",
            "department='".$deptEsc."'",
            "plant_id='".$plant."'",
            "ISNEW='NO'",
            "designation='Master Administrator'",
            "mpin='".$mpinEsc."'"
        );
        if (isset($empCols['emp_id1'])) {
            $sets[] = "emp_id1='".$codeEsc."'";
        }
        if (isset($empCols['lastname'])) {
            $sets[] = "lastname=''";
        }
        if (isset($empCols['email'])) {
            $sets[] = "email='master@medicap.local'";
        }
        if (isset($empCols['emp_email'])) {
            $sets[] = "emp_email='master@medicap.local'";
        }
        if (isset($empCols['login_fail_count'])) {
            $sets[] = "login_fail_count=0";
        }
        if (isset($empCols['login_locked_until'])) {
            $sets[] = "login_locked_until=NULL";
        }
        $sql = "UPDATE employee SET ".implode(', ', $sets)." WHERE id=".$keepId;
        if (!$conn->query($sql)) {
            throw new Exception('employee update failed: '.$conn->error);
        }
        // Extra Master/M001 rows must stay inactive so PIN-only login stays unique.
        $conn->query("UPDATE employee SET status='inactive', mpin='', login_fail_count=0, login_locked_until=NULL WHERE plant_id='".$plant."' AND id<>".$keepId." AND (emp_id='".$empEsc."' OR emp_id='".$codeEsc."')");
    } else {
        $fields = array('plant_id', 'emp_id', 'firstname', 'password', 'status', 'department', 'designation', 'mpin', 'ISNEW', 'entry_by', 'entry_date', 'approve_by', 'approve_date');
        $values = array("'".$plant."'", "'".$empEsc."'", "'".$nameEsc."'", "'".$passEsc."'", "'active'", "'".$deptEsc."'", "'Master Administrator'", "'".$mpinEsc."'", "'NO'", "'".$empEsc."'", "'".$entryDate."'", "'".$empEsc."'", "'".$entryDate."'");
        if (isset($empCols['emp_id1'])) {
            $fields[] = 'emp_id1';
            $values[] = "'".$codeEsc."'";
        }
        if (isset($empCols['lastname'])) {
            $fields[] = 'lastname';
            $values[] = "''";
        }
        if (isset($empCols['email'])) {
            $fields[] = 'email';
            $values[] = "'master@medicap.local'";
        }
        if (isset($empCols['emp_email'])) {
            $fields[] = 'emp_email';
            $values[] = "'master@medicap.local'";
        }
        // Only keep columns that exist.
        $useF = array();
        $useV = array();
        for ($i = 0; $i < count($fields); $i++) {
            if (isset($empCols[$fields[$i]]) || in_array($fields[$i], array('plant_id','emp_id','firstname','password','status','department'), true)) {
                if (isset($empCols[$fields[$i]])) {
                    $useF[] = $fields[$i];
                    $useV[] = $values[$i];
                }
            }
        }
        // Refresh columns after ALTERs
        $empCols = array();
        $colRes = $conn->query("SHOW COLUMNS FROM employee");
        if ($colRes) {
            while ($col = $colRes->fetch_assoc()) {
                $empCols[$col['Field']] = true;
            }
        }
        $useF = array();
        $useV = array();
        for ($i = 0; $i < count($fields); $i++) {
            if (isset($empCols[$fields[$i]])) {
                $useF[] = $fields[$i];
                $useV[] = $values[$i];
            }
        }
        $sql = "INSERT INTO employee (".implode(',', $useF).") VALUES (".implode(',', $useV).")";
        if (!$conn->query($sql)) {
            throw new Exception('employee insert failed: '.$conn->error.' SQL='.$sql);
        }
    }

    // Full rights across key departments.
    $departments = array(
        'Master', 'Quality Control', 'Quality Assurance', 'Store', 'Purchase',
        'Production', 'Engineering', 'Human Resource', 'Microbiology', 'IT', 'Management'
    );
    $rightsAdded = 0;
    $rightsUpdated = 0;
    foreach ($departments as $deptName) {
        $d = $conn->real_escape_string($deptName);
        $check = $conn->query("SELECT id FROM emp_rights WHERE emp_id='".$empEsc."' AND plant_id='".$plant."' AND department='".$d."' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE emp_rights SET
                isuser='Yes', ischecker='Yes', isapprover='Yes', qms_approver='Yes',
                dept_head='Yes', quality_head='Yes', isauditor='Yes', plant_head='Yes',
                trainig_cordinator='Yes', task_assigner='Yes', shift_allocator='Yes',
                status='approve', main='Yes', update_by='".$empEsc."', update_date='".$entryDate."'
                WHERE emp_id='".$empEsc."' AND plant_id='".$plant."' AND department='".$d."'");
            $rightsUpdated++;
        } else {
            $ok = $conn->query("INSERT INTO emp_rights
                (user_no, emp_id, department, plant_id, isuser, ischecker, isapprover, qms_approver, dept_head, quality_head, isauditor, plant_head, trainig_cordinator, task_assigner, shift_allocator, status, entry_by, entry_date, main, additional)
                VALUES
                ('".$userNoEsc."','".$empEsc."','".$d."','".$plant."','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','approve','".$empEsc."','".$entryDate."','Yes','[]')");
            if ($ok) {
                $rightsAdded++;
            }
        }
    }

    // M001 is ONLY an emp_id1 alias on the Master row — do NOT keep a second active
    // employee with the same default PIN (blocks PIN-only login).
    if (isset($empCols['emp_id1'])) {
        $conn->query("UPDATE employee SET emp_id1='".$codeEsc."' WHERE emp_id='".$empEsc."'");
    }
    $alias = $conn->query("SELECT id FROM employee WHERE emp_id='".$codeEsc."' AND emp_id<>'".$empEsc."' LIMIT 1");
    if ($alias && $alias->num_rows > 0) {
        $conn->query("UPDATE employee SET status='inactive', mpin='' WHERE emp_id='".$codeEsc."' AND emp_id<>'".$empEsc."'");
        $conn->query("DELETE FROM emp_rights WHERE emp_id='".$codeEsc."' AND plant_id='".$plant."'");
    }

    $verify = $conn->query("SELECT emp_id, emp_id1, firstname, department, status, plant_id FROM employee WHERE emp_id='".$empEsc."' LIMIT 1");
    $row = ($verify && $verify->num_rows) ? $verify->fetch_assoc() : null;

    echo json_encode(array(
        'status' => 'success',
        'action' => $action,
        'login_user_id' => $loginId,
        'employee_id' => $employeeCode,
        'name' => $fullName,
        'password_set' => true,
        'plant_id' => $plantId,
        'rights_added' => $rightsAdded,
        'rights_updated' => $rightsUpdated,
        'employee' => $row,
        'login_hint' => 'Login with User ID: Master  Password: Master@47#  (or Employee ID M001)'
    ), JSON_PRETTY_PRINT);

    $conn->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
