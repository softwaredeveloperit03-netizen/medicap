<?php
/**
 * Shared credential verification for e-sign, PIN auth, and credential changes.
 * Accepts login password OR authorization PIN (mpin). Default PIN is 1234 when mpin is unset.
 */

if (!defined('ZUMA_DEFAULT_AUTH_PIN')) {
    define('ZUMA_DEFAULT_AUTH_PIN', '1234');
}

if (!function_exists('zuma_auth_effective_mpin')) {
    function zuma_auth_effective_mpin($storedMpin)
    {
        $mpin = trim((string) ($storedMpin ?? ''));
        // Empty or legacy 6-digit default → current default 1234
        if ($mpin === '' || $mpin === '111111') {
            return ZUMA_DEFAULT_AUTH_PIN;
        }
        return $mpin;
    }
}

if (!function_exists('zuma_auth_password_matches')) {
    /**
     * @return array{ok:bool,method:string} method is 'password' or 'pin'
     */
    function zuma_auth_password_matches($storedPassword, $inputPassword, $storedMpin = '')
    {
        $stored = (string) ($storedPassword ?? '');
        $input = trim((string) ($inputPassword ?? ''));
        if ($input === '') {
            return array('ok' => false, 'method' => '');
        }

        if ($stored !== '' && hash_equals($stored, $input)) {
            return array('ok' => true, 'method' => 'password');
        }

        $mpin = zuma_auth_effective_mpin($storedMpin);
        if (hash_equals($mpin, $input)) {
            return array('ok' => true, 'method' => 'pin');
        }

        if (function_exists('encrypt')) {
            $enc = encrypt('encrypt', $input);
            if ($enc !== false && $enc !== '' && $stored !== '' && hash_equals($stored, (string) $enc)) {
                return array('ok' => true, 'method' => 'password');
            }
        }
        if (function_exists('dec_enc')) {
            $dec = dec_enc('decrypt', $stored);
            if ($dec !== false && $dec !== '' && hash_equals((string) $dec, $input)) {
                return array('ok' => true, 'method' => 'password');
            }
        }

        return array('ok' => false, 'method' => '');
    }
}

if (!function_exists('zuma_auth_method_label')) {
    function zuma_auth_method_label($method)
    {
        if ($method === 'pin') {
            return 'Authorization PIN + User ID (21 CFR Part 11)';
        }
        return 'Password + User ID (21 CFR Part 11)';
    }
}

if (!function_exists('zuma_auth_employee_lookup_sql')) {
    /**
     * Prefer active employee rows; match emp_id or emp_id1; optional plant scope.
     */
    function zuma_auth_employee_lookup_sql($conn, $empId, $plantId = '')
    {
        $eid = $conn->real_escape_string(trim((string) $empId));
        if ($eid === '') {
            return '';
        }
        $hasEmpId1 = false;
        $colRes = $conn->query("SHOW COLUMNS FROM employee LIKE 'emp_id1'");
        if ($colRes && $colRes->num_rows > 0) {
            $hasEmpId1 = true;
        }
        $where = $hasEmpId1
            ? "(emp_id='$eid' OR emp_id1='$eid')"
            : "emp_id='$eid'";
        $plant = trim((string) $plantId);
        if ($plant !== '' && $plant !== '0') {
            $plantEsc = $conn->real_escape_string($plant);
            $hasPlant = false;
            $pRes = $conn->query("SHOW COLUMNS FROM employee LIKE 'plant_id'");
            if ($pRes && $pRes->num_rows > 0) {
                $hasPlant = true;
            }
            if ($hasPlant) {
                $where .= " AND (plant_id='$plantEsc' OR IFNULL(plant_id,'')='' OR plant_id='0')";
            }
        }
        return "SELECT emp_id, firstname, lastname, middlename, password, mpin, status
            FROM employee
            WHERE $where
            ORDER BY CASE WHEN LOWER(IFNULL(status,''))='active' THEN 0 ELSE 1 END, id DESC
            LIMIT 1";
    }
}

if (!function_exists('zuma_auth_verify_password_only')) {
    /** Verify login password only (for PIN change / sensitive credential updates). */
    function zuma_auth_verify_password_only($conn, $empId, $password, $plantId = '')
    {
        $sql = zuma_auth_employee_lookup_sql($conn, $empId, $plantId);
        if ($sql === '') {
            return array('ok' => false, 'row' => null, 'message' => 'Employee ID is required.');
        }
        // password-only select shape
        $sql = str_replace(
            'SELECT emp_id, firstname, lastname, middlename, password, mpin, status',
            'SELECT emp_id, firstname, lastname, password, status',
            $sql
        );
        $res = $conn->query($sql);
        if (!$res || $res->num_rows === 0) {
            return array('ok' => false, 'row' => null, 'message' => 'Employee not found.');
        }
        $row = $res->fetch_assoc();
        if (strtolower((string)($row['status'] ?? 'active')) !== 'active') {
            return array('ok' => false, 'row' => $row, 'message' => 'User account is not active.');
        }
        $match = zuma_auth_password_matches($row['password'] ?? '', $password, '');
        if (!$match['ok'] || $match['method'] !== 'password') {
            return array('ok' => false, 'row' => $row, 'message' => 'Current login password verification failed.');
        }
        return array('ok' => true, 'row' => $row, 'message' => '');
    }
}

if (!function_exists('zuma_auth_verify_employee')) {
    /**
     * Verify credentials for a specific employee id.
     * @return array{ok:bool,method:string,row:array|null,message:string}
     */
    function zuma_auth_verify_employee($conn, $empId, $credential, $plantId = '')
    {
        if ($plantId === '' && isset($_GET['plant_id'])) {
            $plantId = trim((string) $_GET['plant_id']);
        }
        $sql = zuma_auth_employee_lookup_sql($conn, $empId, $plantId);
        if ($sql === '') {
            return array('ok' => false, 'method' => '', 'row' => null, 'message' => 'Employee ID is required.');
        }
        $res = $conn->query($sql);
        if (!$res || $res->num_rows === 0) {
            return array('ok' => false, 'method' => '', 'row' => null, 'message' => 'Employee not found.');
        }
        $row = $res->fetch_assoc();
        if (strtolower((string)($row['status'] ?? 'active')) !== 'active') {
            return array('ok' => false, 'method' => '', 'row' => $row, 'message' => 'User account is not active.');
        }
        $match = zuma_auth_password_matches($row['password'] ?? '', $credential, $row['mpin'] ?? '');
        if (!$match['ok']) {
            return array('ok' => false, 'method' => '', 'row' => $row, 'message' => 'Invalid password or PIN. Signature rejected.');
        }
        return array('ok' => true, 'method' => $match['method'], 'row' => $row, 'message' => '');
    }
}
