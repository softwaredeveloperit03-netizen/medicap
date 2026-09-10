<?php
/**
 * Employee rights helpers used by login / master access.
 */

if (!function_exists('gw_has_master_emp_access')) {
    function gw_has_master_emp_access($conn, $empId, $plantId)
    {
        $emp = $conn->real_escape_string(trim((string)$empId));
        $plant = $conn->real_escape_string(trim((string)$plantId));
        if ($emp === '' || $plant === '') {
            return false;
        }
        // Explicit Master department rights, or plant/quality head.
        $sql = "SELECT id FROM emp_rights
            WHERE emp_id='".$emp."' AND plant_id='".$plant."'
            AND (
                LOWER(department)='master'
                OR plant_head='Yes'
                OR quality_head='Yes'
                OR (isuser='Yes' AND ischecker='Yes' AND isapprover='Yes' AND dept_head='Yes')
            )
            LIMIT 1";
        $res = $conn->query($sql);
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('gw_ensure_employee_department_rights')) {
    function gw_ensure_employee_department_rights($conn, $empId, $plantId, $entryBy = 'master')
    {
        $emp = $conn->real_escape_string(trim((string)$empId));
        $plant = $conn->real_escape_string(trim((string)$plantId));
        $by = $conn->real_escape_string(trim((string)$entryBy));
        if ($emp === '' || $plant === '') {
            return false;
        }
        $deptRes = $conn->query("SELECT department FROM employee WHERE emp_id='".$emp."' LIMIT 1");
        $dept = 'Master';
        if ($deptRes && ($row = $deptRes->fetch_assoc()) && trim((string)$row['department']) !== '') {
            $dept = $conn->real_escape_string($row['department']);
        }
        $check = $conn->query("SELECT id FROM emp_rights WHERE emp_id='".$emp."' AND plant_id='".$plant."' AND department='".$dept."' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            return true;
        }
        $entryDate = date('Y-m-d H:i:s');
        $sql = "INSERT INTO emp_rights (user_no, emp_id, department, plant_id, isuser, ischecker, isapprover, qms_approver, dept_head, quality_head, isauditor, plant_head, status, entry_by, entry_date, main, additional)
            VALUES ('GMP22052','".$emp."','".$dept."','".$plant."','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','approve','".$by."','".$entryDate."','Yes','[]')";
        return (bool)$conn->query($sql);
    }
}

if (!function_exists('gw_ensure_hr_emp_rights')) {
    function gw_ensure_hr_emp_rights($conn, $empId, $plantId, $entryBy = 'master')
    {
        // Ensure home-department rights exist (same as department ensure).
        return gw_ensure_employee_department_rights($conn, $empId, $plantId, $entryBy);
    }
}

if (!function_exists('gw_upgrade_checker_rights_for_active_users')) {
    function gw_upgrade_checker_rights_for_active_users($conn, $empId, $plantId)
    {
        $emp = $conn->real_escape_string(trim((string)$empId));
        $plant = $conn->real_escape_string(trim((string)$plantId));
        if ($emp === '' || $plant === '') {
            return false;
        }
        // Active users with isuser=Yes should also be able to check.
        $conn->query("UPDATE emp_rights SET ischecker='Yes'
            WHERE emp_id='".$emp."' AND plant_id='".$plant."'
            AND (isuser='Yes' OR isuser='yes' OR isuser='true')
            AND (ischecker IS NULL OR ischecker='' OR ischecker='No' OR ischecker='no')");
        return true;
    }
}
