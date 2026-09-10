<?php
/**
 * Ensure Master/Medicap users have emp_rights rows for every Medicap launcher department.
 * Call: .../tools/ensure_full_launcher_rights.php?key=zuma-maint-2026&plant_id=1126
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

$plantId = $conn->real_escape_string((string)$_GET['plant_id']);
$empIds = array('Master', 'master', 'Medicap');
$departments = array(
    'Exports', 'Management', 'master', 'Admin', 'Reception', 'Account', 'Marketing', 'Purchase',
    'Human Resource', 'Regulatory', 'Security', 'Planning', 'Production', 'Packing',
    'Quality Control', 'Quality Assurance', 'Engineering', 'IT', 'EHS', 'Store',
    'Enginering Store', 'Dispatch', 'Plant Head', 'R AND D', 'NPD', 'E Logs', 'Q-Head',
);

$actions = array();
$inserted = 0;
$updated = 0;

foreach ($empIds as $empIdRaw) {
    $empEsc = $conn->real_escape_string($empIdRaw);
    $exists = $conn->query("SELECT emp_id FROM employee WHERE plant_id='".$plantId."' AND emp_id='".$empEsc."' LIMIT 1");
    if (!$exists || $exists->num_rows === 0) {
        continue;
    }

    foreach ($departments as $dept) {
        $deptEsc = $conn->real_escape_string($dept);
        $chk = $conn->query(
            "SELECT id FROM emp_rights WHERE plant_id='".$plantId."' AND emp_id='".$empEsc."' AND department='".$deptEsc."' LIMIT 1"
        );
        if ($chk && $chk->num_rows > 0) {
            $conn->query(
                "UPDATE emp_rights SET isuser='Yes', ischecker='Yes', isapprover='Yes',
                 qms_approver='Yes', dept_head='Yes', plant_head='Yes', status='approve'
                 WHERE plant_id='".$plantId."' AND emp_id='".$empEsc."' AND department='".$deptEsc."'"
            );
            $updated++;
        } else {
            $ok = $conn->query(
                "INSERT INTO emp_rights
                 (plant_id, emp_id, department, isuser, ischecker, isapprover, qms_approver, dept_head, plant_head, status)
                 VALUES
                 ('".$plantId."', '".$empEsc."', '".$deptEsc."', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'approve')"
            );
            if ($ok) {
                $inserted++;
            } else {
                // Fallback without optional columns
                $ok2 = $conn->query(
                    "INSERT INTO emp_rights (plant_id, emp_id, department, isuser, ischecker, isapprover, status)
                     VALUES ('".$plantId."', '".$empEsc."', '".$deptEsc."', 'Yes', 'Yes', 'Yes', 'approve')"
                );
                if ($ok2) {
                    $inserted++;
                } else {
                    $actions[] = 'fail '.$empIdRaw.'/'.$dept.': '.$conn->error;
                }
            }
        }
    }
    $actions[] = 'processed '.$empIdRaw;
}

echo json_encode(array(
    'status' => 'success',
    'plant_id' => $plantId,
    'inserted' => $inserted,
    'updated' => $updated,
    'actions' => $actions,
));
