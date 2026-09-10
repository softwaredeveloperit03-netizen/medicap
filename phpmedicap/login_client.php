<?php
/**
 * Login-only client/plant data for Medicap (Zuma-compatible API).
 */
require __DIR__ . '/db1.php';

header('Content-Type: application/json; charset=utf-8');

function medicap_plant_display_name($row) {
    $name = trim((string)($row['plant_name'] ?? $row['plant_full_name'] ?? $row['client_name'] ?? ''));
    if ($name === '') {
        $name = 'Plant ' . (string)($row['plant_id'] ?? '');
    }
    return $name;
}

$type = isset($_GET['type']) ? (string)$_GET['type'] : '';

if ($type === 'getPlants') {
    $showCorporate = (string)($_GET['show_corporate'] ?? '1') === '1';
    $sql = $showCorporate ? 'SELECT * FROM plant' : "SELECT * FROM plant WHERE is_corporate=0";
    $output = array();
    $result = @$conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['display_name'] = medicap_plant_display_name($row);
            $output[] = $row;
        }
    }
    echo json_encode($output);
    $conn->close();
    exit;
}

if ($type === 'getClientInfo') {
    $plantId = $conn->real_escape_string(trim((string)($_GET['plant_id'] ?? '')));
    $output = array(array());
    if ($plantId !== '') {
        $result = @$conn->query("SELECT * FROM plant WHERE plant_id='$plantId' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $output = array(array(
                'id' => $row['plant_id'] ?? $plantId,
                'plant_type' => 'Manufacturing',
                'software_license_no' => $row['licence_no'] ?? '',
                'software_type' => 'PaperLess GMP Platinum (Regulated)',
                'unit_name' => medicap_plant_display_name($row),
                'logo_rect' => $row['logo_path'] ?? '',
                'software_version' => $row['version_no'] ?? '00',
                'version_change_summary' => '',
                'version_label' => 'MASTER COPY',
                'deploy_env' => trim((string)($_GET['deploy_env'] ?? 'live')),
            ));
        }
    }
    echo json_encode($output);
    $conn->close();
    exit;
}

if ($type === 'getLoginUsers') {
    $plantId = $conn->real_escape_string(trim((string)($_GET['plant_id'] ?? '')));
    $output = array();
    if ($plantId !== '') {
        $sql = "SELECT DISTINCT e.emp_id, e.firstname, e.lastname, e.middlename, e.designation
            FROM employee e
            LEFT JOIN emp_rights r ON e.emp_id = r.emp_id AND r.plant_id='$plantId'
            WHERE e.status='active' AND (e.plant_id='$plantId' OR r.plant_id='$plantId')
            ORDER BY e.firstname ASC, e.lastname ASC, e.emp_id ASC";
        $result = @$conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $name = trim(preg_replace('/\s+/', ' ', trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''))));
                if ($name === '') { $name = (string)($row['emp_id'] ?? ''); }
                $designation = trim((string)($row['designation'] ?? ''));
                $label = $name . ' (' . ($row['emp_id'] ?? '') . ')';
                if ($designation !== '') { $label .= ' — ' . $designation; }
                $output[] = array(
                    'emp_id' => $row['emp_id'],
                    'display_name' => $label,
                    'full_name' => $name,
                    'designation' => $designation,
                );
            }
        }
    }
    echo json_encode($output);
    $conn->close();
    exit;
}

if ($type === 'checkPinAvailable') {
    require_once __DIR__ . '/shared/auth_helper.php';
    require_once __DIR__ . '/shared/login_security_helper.php';
    zuma_login_security_ensure_schema($conn);
    $pin = trim((string)($_GET['pin'] ?? ''));
    $empId = trim((string)($_GET['emp_id'] ?? ''));
    $plantId = trim((string)($_GET['plant_id'] ?? ''));
    $currentPin = trim((string)($_GET['current_pin'] ?? ''));
    if ($empId === '' && $currentPin !== '' && $plantId !== '') {
        $lookup = zuma_login_find_employee_by_pin($conn, $plantId, $currentPin);
        if ($lookup['ok']) { $empId = $lookup['emp_id']; }
    }
    $avail = zuma_pin_is_available($conn, $pin, $empId);
    echo json_encode(array(
        'status' => $avail['ok'] ? 'available' : 'unavailable',
        'message' => $avail['message'],
    ));
    $conn->close();
    exit;
}

echo json_encode(array('status' => 'invalid', 'message' => 'Unknown type'));
$conn->close();
