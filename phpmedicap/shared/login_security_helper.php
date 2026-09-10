<?php
/**
 * Login lockout, password policy, session token helpers.
 */

if (!defined('ZUMA_LOGIN_MAX_ATTEMPTS')) {
    define('ZUMA_LOGIN_MAX_ATTEMPTS', 3);
}
if (!defined('ZUMA_LOGIN_LOCK_SECONDS')) {
    define('ZUMA_LOGIN_LOCK_SECONDS', 180);
}
if (!defined('ZUMA_SESSION_MAX_SECONDS')) {
    define('ZUMA_SESSION_MAX_SECONDS', 3 * 3600);
}
if (!defined('ZUMA_IDLE_REAUTH_SECONDS')) {
    define('ZUMA_IDLE_REAUTH_SECONDS', 600);
}

if (!function_exists('zuma_login_security_column_exists')) {
    function zuma_login_security_column_exists($conn, $table, $column)
    {
        $tableEsc = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        $colEsc = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
        if ($tableEsc === '' || $colEsc === '') {
            return false;
        }
        $res = $conn->query("SHOW COLUMNS FROM `$tableEsc` LIKE '$colEsc'");
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('zuma_login_security_ensure_column')) {
    function zuma_login_security_ensure_column($conn, $table, $column, $definition)
    {
        if (zuma_login_security_column_exists($conn, $table, $column)) {
            return true;
        }
        $tableEsc = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        if ($tableEsc === '') {
            return false;
        }
        $ok = (bool) $conn->query("ALTER TABLE `$tableEsc` ADD COLUMN $definition");
        if (!$ok && function_exists('error_log')) {
            @error_log('zuma_login_security: ALTER TABLE `' . $tableEsc . '` ADD COLUMN failed: ' . $conn->error);
        }
        return $ok;
    }
}

if (!function_exists('zuma_login_security_required_pin_columns')) {
    /** @return string[] */
    function zuma_login_security_required_pin_columns()
    {
        return array('mpin', 'temp_mpin', 'pinStatus', 'pinModifiedOn', 'pinApproveOn', 'pinApproveBy');
    }
}

if (!function_exists('zuma_login_security_schema_status')) {
    /** @return array{ready:bool,missing_employee:string[],missing_token:string[],employee:array<string,bool>,token:array<string,bool>} */
    function zuma_login_security_schema_status($conn)
    {
        $employeeCols = array_merge(
            array('login_fail_count', 'login_locked_until'),
            zuma_login_security_required_pin_columns()
        );
        $tokenCols = array('last_activity', 'session_expires_at');
        $employee = array();
        $token = array();
        $missingEmployee = array();
        $missingToken = array();
        foreach ($employeeCols as $col) {
            $exists = zuma_login_security_column_exists($conn, 'employee', $col);
            $employee[$col] = $exists;
            if (!$exists) {
                $missingEmployee[] = $col;
            }
        }
        foreach ($tokenCols as $col) {
            $exists = zuma_login_security_column_exists($conn, 'token', $col);
            $token[$col] = $exists;
            if (!$exists) {
                $missingToken[] = $col;
            }
        }
        return array(
            'ready' => empty($missingEmployee),
            'missing_employee' => $missingEmployee,
            'missing_token' => $missingToken,
            'employee' => $employee,
            'token' => $token,
        );
    }
}

if (!function_exists('zuma_login_security_ensure_schema')) {
    function zuma_login_security_ensure_schema($conn)
    {
        $employeeCols = array(
            'mpin' => "`mpin` VARCHAR(10) NULL DEFAULT NULL",
            'login_fail_count' => "`login_fail_count` INT NOT NULL DEFAULT 0",
            'login_locked_until' => "`login_locked_until` DATETIME NULL DEFAULT NULL",
            'temp_mpin' => "`temp_mpin` VARCHAR(10) NULL DEFAULT NULL",
            'pinStatus' => "`pinStatus` VARCHAR(32) NULL DEFAULT NULL",
            'pinModifiedOn' => "`pinModifiedOn` DATETIME NULL DEFAULT NULL",
            'pinApproveOn' => "`pinApproveOn` DATETIME NULL DEFAULT NULL",
            'pinApproveBy' => "`pinApproveBy` VARCHAR(80) NULL DEFAULT NULL",
        );
        $tokenCols = array(
            'last_activity' => "`last_activity` DATETIME NULL DEFAULT NULL",
            'session_expires_at' => "`session_expires_at` DATETIME NULL DEFAULT NULL",
        );

        $ok = true;
        foreach ($employeeCols as $col => $def) {
            if (!zuma_login_security_ensure_column($conn, 'employee', $col, $def)) {
                $ok = false;
            }
        }
        foreach ($tokenCols as $col => $def) {
            if (!zuma_login_security_ensure_column($conn, 'token', $col, $def)) {
                $ok = false;
            }
        }

        return $ok && zuma_login_security_schema_status($conn)['ready'];
    }
}

if (!function_exists('zuma_login_security_pin_columns_ready')) {
    function zuma_login_security_pin_columns_ready($conn)
    {
        zuma_login_security_ensure_schema($conn);
        return zuma_login_security_schema_status($conn)['ready'];
    }
}

if (!function_exists('zuma_login_security_require_pin_schema')) {
    /**
     * Ensure PIN-change columns exist; return error payload when not ready.
     * @return array{ok:bool,message:string,status?:array}
     */
    function zuma_login_security_require_pin_schema($conn)
    {
        zuma_login_security_ensure_schema($conn);
        $status = zuma_login_security_schema_status($conn);
        if ($status['ready']) {
            return array('ok' => true, 'message' => '');
        }
        $missing = implode(', ', $status['missing_employee']);
        return array(
            'ok' => false,
            'message' => 'PIN reset database columns are not available on this server. Missing on employee table: '
                . ($missing !== '' ? $missing : 'unknown')
                . '. Run server migrations for this environment (master/server_migrations.php?type=run).',
            'status' => $status,
        );
    }
}

if (!function_exists('zuma_login_security_friendly_db_error')) {
    function zuma_login_security_friendly_db_error($connError)
    {
        $msg = trim((string) $connError);
        if ($msg === '') {
            return 'Database error. Please try again or contact IT.';
        }
        if (stripos($msg, 'pinModifiedOn') !== false
            || stripos($msg, 'pinApproveOn') !== false
            || stripos($msg, 'pinStatus') !== false
            || stripos($msg, 'temp_mpin') !== false
            || stripos($msg, 'pinApproveBy') !== false) {
            return 'PIN reset database columns are not available on this server. Please contact IT to run server migrations.';
        }
        return $msg;
    }
}

if (!function_exists('zuma_password_policy_validate')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function zuma_password_policy_validate($password, $username = '')
    {
        $pwd = (string) $password;
        if (strlen($pwd) < 8) {
            return array('ok' => false, 'message' => 'Password must be at least 8 characters.');
        }
        if (strlen($pwd) > 64) {
            return array('ok' => false, 'message' => 'Password must not exceed 64 characters.');
        }
        if (preg_match('/\s/', $pwd)) {
            return array('ok' => false, 'message' => 'Password must not contain spaces.');
        }
        if (!preg_match('/[a-z]/', $pwd)) {
            return array('ok' => false, 'message' => 'Password must include at least one lowercase letter.');
        }
        if (!preg_match('/[A-Z]/', $pwd)) {
            return array('ok' => false, 'message' => 'Password must include at least one uppercase letter.');
        }
        if (!preg_match('/[0-9]/', $pwd)) {
            return array('ok' => false, 'message' => 'Password must include at least one number.');
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $pwd)) {
            return array('ok' => false, 'message' => 'Password must include at least one special character.');
        }
        $user = strtolower(trim((string) $username));
        if ($user !== '' && strtolower($pwd) === $user) {
            return array('ok' => false, 'message' => 'Password must not be the same as your username.');
        }
        return array('ok' => true, 'message' => '');
    }
}

if (!function_exists('zuma_password_policy_message')) {
    function zuma_password_policy_message()
    {
        return 'Minimum 8 characters with uppercase, lowercase, number, and special character. No spaces.';
    }
}

if (!function_exists('zuma_pin_validate')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function zuma_pin_validate($pin)
    {
        $p = trim((string) $pin);
        if (!preg_match('/^\d{4}$/', $p)) {
            return array('ok' => false, 'message' => 'PIN must be exactly 4 digits.');
        }
        return array('ok' => true, 'message' => '');
    }
}

if (!function_exists('zuma_pin_is_available')) {
    /**
     * Ensure PIN is unique — not used by another employee (or pending IT reset).
     * @return array{ok:bool,message:string}
     */
    function zuma_pin_is_available($conn, $pin, $excludeEmpId = '')
    {
        zuma_login_security_ensure_schema($conn);
        if (!defined('ZUMA_DEFAULT_AUTH_PIN')) {
            require_once __DIR__ . '/auth_helper.php';
        }
        $pinCheck = zuma_pin_validate($pin);
        if (!$pinCheck['ok']) {
            return $pinCheck;
        }
        $p = trim((string) $pin);
        if ($p === ZUMA_DEFAULT_AUTH_PIN) {
            return array('ok' => false, 'message' => 'PIN is not available to set. Choose a different 4-digit PIN.');
        }
        $pinEsc = $conn->real_escape_string($p);
        $exclude = $conn->real_escape_string(trim((string) $excludeEmpId));
        $excludeClause = $exclude !== '' ? " AND emp_id != '$exclude'" : '';

        $res = $conn->query("SELECT emp_id FROM employee WHERE TRIM(IFNULL(mpin,''))='$pinEsc' $excludeClause LIMIT 1");
        if ($res && $res->num_rows > 0) {
            return array('ok' => false, 'message' => 'PIN is not available to set. This PIN is already assigned to another user.');
        }
        $res2 = $conn->query("SELECT emp_id FROM employee WHERE pinStatus='Inprocess' AND TRIM(IFNULL(temp_mpin,''))='$pinEsc' $excludeClause LIMIT 1");
        if ($res2 && $res2->num_rows > 0) {
            return array('ok' => false, 'message' => 'PIN is not available to set. This PIN is pending approval for another user.');
        }
        return array('ok' => true, 'message' => '');
    }
}

if (!function_exists('zuma_login_employee_select_sql')) {
    function zuma_login_employee_select_sql($plantId)
    {
        $plantEsc = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $plantId);
        return "SELECT e.*,p.is_corporate,IFNULL(r.isuser,false) as isuser,IFNULL(r.ischecker,false) as ischecker,
        IFNULL(r.isapprover,false) as isapprover,IFNULL(r.qms_approver,false) as qms_approver, IFNULL(r.dept_head,dept_head)
        as dept_head,p.licence_no,p.logo_path FROM employee e
        LEFT JOIN plant p ON p.plant_id='" . $plantEsc . "'
        LEFT JOIN emp_rights r on e.emp_id = r.emp_id AND r.plant_id='" . $plantEsc . "'";
    }
}

if (!function_exists('zuma_login_dedupe_employee_rows')) {
    /** Collapse JOIN duplicates to one row per emp_id. */
    function zuma_login_dedupe_employee_rows(array $rows)
    {
        $by = array();
        foreach ($rows as $row) {
            $id = trim((string) ($row['emp_id'] ?? ''));
            if ($id !== '') {
                $by[$id] = $row;
            }
        }
        return array_values($by);
    }
}

if (!function_exists('zuma_login_effective_pin_match_sql')) {
    /** SQL predicate: employee effective PIN (empty mpin => default) equals $pinEsc. */
    function zuma_login_effective_pin_match_sql($conn, $pinEsc)
    {
        if (!defined('ZUMA_DEFAULT_AUTH_PIN')) {
            require_once __DIR__ . '/auth_helper.php';
        }
        $defaultEsc = $conn->real_escape_string(ZUMA_DEFAULT_AUTH_PIN);
        return "(TRIM(IFNULL(e.mpin,''))='$pinEsc' OR (TRIM(IFNULL(e.mpin,''))='' AND '$pinEsc'='$defaultEsc'))";
    }
}

if (!function_exists('zuma_login_find_employees_by_effective_pin')) {
    /**
     * Active employees in a plant whose effective PIN matches.
     * @return array<int,array>
     */
    function zuma_login_find_employees_by_effective_pin($conn, $plantId, $pin)
    {
        $plantEsc = $conn->real_escape_string(trim((string) $plantId));
        $pinEsc = $conn->real_escape_string(trim((string) $pin));
        $pinMatch = zuma_login_effective_pin_match_sql($conn, $pinEsc);
        $sql = zuma_login_employee_select_sql($plantEsc) . "
        WHERE e.status='active' AND (e.plant_id='$plantEsc' OR r.plant_id='$plantEsc')
        AND $pinMatch
        ORDER BY e.id ASC";
        $rows = array();
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return zuma_login_dedupe_employee_rows($rows);
    }
}

if (!function_exists('zuma_login_find_employee_by_pin')) {
    /**
     * Resolve active employee by unique 4-digit PIN within a plant (PIN-only login).
     * @return array{ok:bool,emp_id:string,row:array|null,message:string,count:int}
     */
    function zuma_login_find_employee_by_pin($conn, $plantId, $pin)
    {
        if (!defined('ZUMA_DEFAULT_AUTH_PIN')) {
            require_once __DIR__ . '/auth_helper.php';
        }
        $pinCheck = zuma_pin_validate($pin);
        if (!$pinCheck['ok']) {
            return array('ok' => false, 'emp_id' => '', 'row' => null, 'message' => $pinCheck['message'], 'count' => 0);
        }

        $matches = zuma_login_find_employees_by_effective_pin($conn, $plantId, $pin);
        if (count($matches) === 1) {
            return array(
                'ok' => true,
                'emp_id' => $matches[0]['emp_id'],
                'row' => $matches[0],
                'message' => '',
                'count' => 1,
            );
        }
        if (count($matches) > 1) {
            $defaultMsg = ($pin === ZUMA_DEFAULT_AUTH_PIN)
                ? 'Default PIN is shared by multiple users. Enter your login password in Change PIN to confirm your account, or sign in with username and password first.'
                : 'PIN is not unique. Select your name from the user list and try password login.';
            return array(
                'ok' => false,
                'emp_id' => '',
                'row' => null,
                'message' => $defaultMsg,
                'count' => count($matches),
            );
        }

        return array(
            'ok' => false,
            'emp_id' => '',
            'row' => null,
            'message' => 'Invalid PIN or user not found for this plant.',
            'count' => 0,
        );
    }
}

if (!function_exists('zuma_login_resolve_employee_for_pin_change')) {
    /**
     * Identify employee for pre-login PIN change using current PIN + login password.
     * Falls back to password disambiguation when default PIN is shared.
     * @return array{ok:bool,emp_id:string,message:string}
     */
    function zuma_login_resolve_employee_for_pin_change($conn, $plantId, $currentPin, $password)
    {
        if (!defined('ZUMA_DEFAULT_AUTH_PIN')) {
            require_once __DIR__ . '/auth_helper.php';
        }
        $pinCheck = zuma_pin_validate($currentPin);
        if (!$pinCheck['ok']) {
            return array('ok' => false, 'emp_id' => '', 'message' => $pinCheck['message']);
        }
        $password = trim((string) $password);
        if ($password === '') {
            return array('ok' => false, 'emp_id' => '', 'message' => 'Current login password is required.');
        }

        $lookup = zuma_login_find_employee_by_pin($conn, $plantId, $currentPin);
        if ($lookup['ok']) {
            $auth = zuma_auth_verify_password_only($conn, $lookup['emp_id'], $password);
            if ($auth['ok']) {
                return array('ok' => true, 'emp_id' => $lookup['emp_id'], 'message' => '');
            }
            return array(
                'ok' => false,
                'emp_id' => '',
                'message' => $auth['message'] ?: 'Current login password verification failed.',
            );
        }

        $candidates = zuma_login_find_employees_by_effective_pin($conn, $plantId, $currentPin);
        $passwordMatches = array();
        foreach ($candidates as $row) {
            $auth = zuma_auth_verify_password_only($conn, $row['emp_id'], $password);
            if ($auth['ok']) {
                $passwordMatches[] = $row;
            }
        }
        if (count($passwordMatches) === 1) {
            return array('ok' => true, 'emp_id' => $passwordMatches[0]['emp_id'], 'message' => '');
        }
        if (count($passwordMatches) > 1) {
            return array(
                'ok' => false,
                'emp_id' => '',
                'message' => 'Multiple users matched this PIN and password. Contact IT for assistance.',
            );
        }

        if ($lookup['count'] > 1) {
            return array(
                'ok' => false,
                'emp_id' => '',
                'message' => 'Default PIN is shared by multiple users. Enter your correct login password to change PIN.',
            );
        }

        return array(
            'ok' => false,
            'emp_id' => '',
            'message' => 'Current PIN or login password is incorrect for the selected plant.',
        );
    }
}

if (!function_exists('zuma_login_check_lock')) {
    /**
     * @return array{locked:bool,seconds_remaining:int,message:string}
     */
    function zuma_login_check_lock($row)
    {
        $until = trim((string) ($row['login_locked_until'] ?? ''));
        if ($until === '' || $until === '0000-00-00 00:00:00') {
            return array('locked' => false, 'seconds_remaining' => 0, 'message' => '');
        }
        $untilTs = strtotime($until);
        if ($untilTs === false || $untilTs <= time()) {
            return array('locked' => false, 'seconds_remaining' => 0, 'message' => '');
        }
        $remaining = max(1, $untilTs - time());
        return array(
            'locked' => true,
            'seconds_remaining' => (int) $remaining,
            'message' => 'Account locked due to multiple failed login attempts. Try again after ' . (int) $remaining . ' seconds.',
        );
    }
}

if (!function_exists('zuma_login_record_failure')) {
    /**
     * @return array{locked:bool,seconds_remaining:int,attempts_remaining:int,message:string}
     */
    function zuma_login_record_failure($conn, $empId)
    {
        zuma_login_security_ensure_schema($conn);
        $eid = $conn->real_escape_string(trim((string) $empId));
        if ($eid === '') {
            return array('locked' => false, 'seconds_remaining' => 0, 'attempts_remaining' => 0, 'message' => 'Invalid user.');
        }

        $res = $conn->query("SELECT login_fail_count, login_locked_until FROM employee WHERE emp_id='$eid' LIMIT 1");
        $count = 0;
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $lock = zuma_login_check_lock($row);
            if ($lock['locked']) {
                return array(
                    'locked' => true,
                    'seconds_remaining' => $lock['seconds_remaining'],
                    'attempts_remaining' => 0,
                    'message' => $lock['message'],
                );
            }
            $count = (int) ($row['login_fail_count'] ?? 0);
        }

        $count++;
        $lockedUntil = '';
        $locked = false;
        $seconds = 0;
        if ($count >= ZUMA_LOGIN_MAX_ATTEMPTS) {
            $locked = true;
            $seconds = ZUMA_LOGIN_LOCK_SECONDS;
            $lockedUntil = date('Y-m-d H:i:s', time() + ZUMA_LOGIN_LOCK_SECONDS);
            $count = 0;
        }

        if ($lockedUntil !== '') {
            $conn->query("UPDATE employee SET login_fail_count='$count', login_locked_until='$lockedUntil' WHERE emp_id='$eid'");
        } else {
            $remaining = max(0, ZUMA_LOGIN_MAX_ATTEMPTS - $count);
            $conn->query("UPDATE employee SET login_fail_count='$count', login_locked_until=NULL WHERE emp_id='$eid'");
            return array(
                'locked' => false,
                'seconds_remaining' => 0,
                'attempts_remaining' => $remaining,
                'message' => $remaining > 0
                    ? 'Invalid credentials. ' . $remaining . ' attempt(s) remaining before lockout.'
                    : 'Invalid credentials.',
            );
        }

        return array(
            'locked' => true,
            'seconds_remaining' => $seconds,
            'attempts_remaining' => 0,
            'message' => 'Account locked for ' . ZUMA_LOGIN_LOCK_SECONDS . ' seconds after ' . ZUMA_LOGIN_MAX_ATTEMPTS . ' failed attempts.',
        );
    }
}

if (!function_exists('zuma_login_clear_failures')) {
    function zuma_login_clear_failures($conn, $empId)
    {
        zuma_login_security_ensure_schema($conn);
        $eid = $conn->real_escape_string(trim((string) $empId));
        if ($eid === '') {
            return;
        }
        $conn->query("UPDATE employee SET login_fail_count=0, login_locked_until=NULL WHERE emp_id='$eid'");
    }
}

if (!function_exists('zuma_login_create_token')) {
    /**
     * @return array{ok:bool,passcode:string,message:string}
     */
    function zuma_login_create_token($conn, $empId, $plantId, $department, $entry_date)
    {
        zuma_login_security_ensure_schema($conn);
        $string = $empId . '$' . $department . '$' . $entry_date;
        $key1 = generateRandomString();
        $key2 = generateRandomString();
        $passcode = encrypt('encrypt', $string, $key1, $key2);
        $expiresAt = date('Y-m-d H:i:s', time() + ZUMA_SESSION_MAX_SECONDS);

        $empEsc = $conn->real_escape_string((string) $empId);
        $plantEsc = $conn->real_escape_string((string) $plantId);
        $deptEsc = $conn->real_escape_string((string) $department);
        $tokenEsc = $conn->real_escape_string((string) $passcode);
        $k1Esc = $conn->real_escape_string((string) $key1);
        $k2Esc = $conn->real_escape_string((string) $key2);
        $entryEsc = $conn->real_escape_string((string) $entry_date);

        $sql = "INSERT INTO token (emp_id,plant_id,department,token,key1,key2,entry_date,last_activity,session_expires_at)
            VALUES ('$empEsc','$plantEsc','$deptEsc','$tokenEsc','$k1Esc','$k2Esc','$entryEsc','$entryEsc','$expiresAt')";
        if (!$conn->query($sql)) {
            $sqlFallback = "INSERT INTO token (emp_id,plant_id,department,token,key1,key2,entry_date)
                VALUES ('$empEsc','$plantEsc','$deptEsc','$tokenEsc','$k1Esc','$k2Esc','$entryEsc')";
            if (!$conn->query($sqlFallback)) {
                return array('ok' => false, 'passcode' => '', 'message' => $conn->error);
            }
        }

        $txt = '{"emp_id": "' . $empId . '", "department": "' . $department . '","token": ' . $passcode . ', "key1": "' . $key1 . '", "key2": "' . $key2 . '", "entry_date": "' . $entry_date . '"}';
        @file_put_contents(__DIR__ . '/../token.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

        return array('ok' => true, 'passcode' => $passcode, 'message' => '');
    }
}

if (!function_exists('zuma_token_touch_activity')) {
    function zuma_token_touch_activity($conn, $tokenValue, $entry_date)
    {
        zuma_login_security_ensure_schema($conn);
        $tokenEsc = $conn->real_escape_string((string) $tokenValue);
        $expiresAt = date('Y-m-d H:i:s', time() + ZUMA_SESSION_MAX_SECONDS);
        $entryEsc = $conn->real_escape_string((string) $entry_date);
        $conn->query("UPDATE token SET last_activity='$entryEsc', session_expires_at='$expiresAt' WHERE token='$tokenEsc'");
    }
}

if (!function_exists('zuma_token_session_valid')) {
    /**
     * @return array{ok:bool,message:string,emp_id:string,department:string}
     */
    function zuma_token_session_valid($conn, $tokenValue)
    {
        zuma_login_security_ensure_schema($conn);
        $tokenEsc = $conn->real_escape_string((string) $tokenValue);
        $res = $conn->query("SELECT emp_id, department, token, key1, key2, session_expires_at FROM token WHERE token='$tokenEsc' LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return array('ok' => false, 'message' => 'Session expired. Please login again.', 'emp_id' => '', 'department' => '');
        }
        $row = $res->fetch_assoc();
        $expires = trim((string) ($row['session_expires_at'] ?? ''));
        if ($expires !== '' && $expires !== '0000-00-00 00:00:00') {
            $expTs = strtotime($expires);
            if ($expTs !== false && $expTs < time()) {
                return array('ok' => false, 'message' => 'Session expired after 3 hours. Please login again.', 'emp_id' => '', 'department' => '');
            }
        }
        $string = decrypt('decrypt', $tokenValue, $row['key1'], $row['key2']);
        $parts = explode('$', (string) $string);
        return array(
            'ok' => true,
            'message' => '',
            'emp_id' => $parts[0] ?? ($row['emp_id'] ?? ''),
            'department' => $parts[1] ?? ($row['department'] ?? ''),
        );
    }
}
