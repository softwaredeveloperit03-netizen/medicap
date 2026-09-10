<?php
/**
 * Installation Qualification (IQ) APIs for equipment_iq workflow.
 * Used by /engineering/equip-qualification/iq/*
 */
require '../db.php';
require '../token.php';

header('Content-Type: application/json; charset=UTF-8');

$token = isset($_GET["token"]) ? $_GET["token"] : '';
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);

if (!isset($input) || $input === null || $input === '') {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    } else {
        $input = array();
    }
}

$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = isset($string[1]) ? $string[1] : '';
        break;
    }

    if (!isset($_GET["user_no"]) || $_GET["user_no"] === '' || $_GET["user_no"] === null) {
        $_GET["user_no"] = 'GMP22052';
    }

    $plantId = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
    $userNo = $conn->real_escape_string($_GET["user_no"]);
    $empId = $conn->real_escape_string($_GET["emp_id"]);
    $type = isset($_GET["type"]) ? $_GET["type"] : '';

    // Ensure equipment_iq table exists (additive; safe on live).
    $conn->query("CREATE TABLE IF NOT EXISTS equipment_iq (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_no VARCHAR(50) NULL,
        plant_id VARCHAR(20) NULL,
        request_id INT NULL,
        equipment_name VARCHAR(255) NULL,
        department_name VARCHAR(255) NULL,
        section_name VARCHAR(255) NULL,
        make VARCHAR(255) NULL,
        capacity VARCHAR(255) NULL,
        department LONGTEXT NULL,
        equipment LONGTEXT NULL,
        utility LONGTEXT NULL,
        installation_check LONGTEXT NULL,
        machine_check LONGTEXT NULL,
        blank_check LONGTEXT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        entry_by VARCHAR(50) NULL,
        entry_date DATETIME NULL,
        check_by VARCHAR(50) NULL,
        check_date DATETIME NULL,
        approve_by VARCHAR(50) NULL,
        approve_date DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    function iq_decode_row($row) {
        foreach (array('department', 'equipment', 'utility', 'installation_check', 'machine_check', 'blank_check') as $key) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                $decoded = json_decode($row[$key], true);
                if ($decoded !== null) {
                    $row[$key] = $decoded;
                }
            }
        }
        return $row;
    }

    function iq_esc_json($conn, $value) {
        if ($value === null) {
            return 'null';
        }
        return "'" . $conn->real_escape_string(json_encode($value)) . "'";
    }

    if ($type == "getRequest") {
        // Pending qualification requests available to start IQ.
        $output = array();
        $sql = "SELECT * FROM qualificationreq WHERE plant_id='" . $plantId . "' ORDER BY id DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        // Fallback: approved site-acceptance equipment waiting for IQ file stage.
        if (count($output) === 0) {
            $sql2 = "SELECT e.*, e.equipment_name AS equip_name, e.department AS department_name,
                     '' AS section_name, '' AS make, e.capacity
                     FROM equipment_requirement e
                     WHERE (e.site_status='Approved' OR e.site_status='approve')
                     AND (e.eiqr_status='Pending' OR e.eiqr_status IS NULL OR e.eiqr_status='' OR e.eiqr_status='pending')
                     ORDER BY e.id DESC";
            $res2 = $conn->query($sql2);
            if ($res2 && $res2->num_rows > 0) {
                while ($row = $res2->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($type == "saveIq") {
        $dept = isset($input["department"]) ? $input["department"] : array();
        $equip = isset($input["equipment"]) ? $input["equipment"] : array();
        $utility = isset($input["utility"]) ? $input["utility"] : array();
        $install = isset($input["installation_check"]) ? $input["installation_check"] : array();
        $machine = isset($input["machine_check"]) ? $input["machine_check"] : array();
        $blank = isset($input["blank_check"]) ? $input["blank_check"] : array();

        $equipment_name = $conn->real_escape_string(isset($input["equipment_name"]) ? $input["equipment_name"] : (isset($equip["equipment_name"]) ? $equip["equipment_name"] : ''));
        $department_name = $conn->real_escape_string(isset($input["department_name"]) ? $input["department_name"] : (isset($dept["dept"]) ? $dept["dept"] : ''));
        $section_name = $conn->real_escape_string(isset($input["section_name"]) ? $input["section_name"] : '');
        $make = $conn->real_escape_string(isset($equip["make"]) ? $equip["make"] : '');
        $capacity = $conn->real_escape_string(isset($equip["capacity"]) ? $equip["capacity"] : '');
        $request_id = isset($input["request_id"]) ? intval($input["request_id"]) : 0;

        $sql = "INSERT INTO equipment_iq
            (user_no, plant_id, request_id, equipment_name, department_name, section_name, make, capacity,
             department, equipment, utility, installation_check, machine_check, blank_check,
             status, entry_by, entry_date)
            VALUES (
             '" . $userNo . "', '" . $plantId . "', " . ($request_id > 0 ? $request_id : "NULL") . ",
             '" . $equipment_name . "', '" . $department_name . "', '" . $section_name . "',
             '" . $make . "', '" . $capacity . "',
             " . iq_esc_json($conn, $dept) . ",
             " . iq_esc_json($conn, $equip) . ",
             " . iq_esc_json($conn, $utility) . ",
             " . iq_esc_json($conn, $install) . ",
             " . iq_esc_json($conn, $machine) . ",
             " . iq_esc_json($conn, $blank) . ",
             'pending', '" . $empId . "', '" . $entry_date . "')";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->real_escape_string($conn->error) . "\"}";
        }
    } else if ($type == "getPendingIq") {
        $output = array();
        $sql = "SELECT * FROM equipment_iq WHERE status='pending' AND plant_id='" . $plantId . "' ORDER BY id DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = iq_decode_row($row);
            }
        }
        echo json_encode($output);
    } else if ($type == "saveCheckpointIq") {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
        $status = isset($_GET["status"]) ? $conn->real_escape_string($_GET["status"]) : 'checked';
        if ($status === '' || $status === 'check' || $status === 'submit') {
            $status = 'checked';
        }
        $install = isset($input["installation_check"]) ? $input["installation_check"] : array();
        $machine = isset($input["machine_check"]) ? $input["machine_check"] : array();
        $blank = isset($input["blank_check"]) ? $input["blank_check"] : array();

        $sql = "UPDATE equipment_iq SET
            installation_check=" . iq_esc_json($conn, $install) . ",
            machine_check=" . iq_esc_json($conn, $machine) . ",
            blank_check=" . iq_esc_json($conn, $blank) . ",
            status='" . $status . "',
            check_by='" . $empId . "',
            check_date='" . $entry_date . "'
            WHERE id=" . $id;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->real_escape_string($conn->error) . "\"}";
        }
    } else if ($type == "getCheckedIq") {
        $output = array();
        $sql = "SELECT * FROM equipment_iq WHERE status='checked' AND plant_id='" . $plantId . "' ORDER BY id DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = iq_decode_row($row);
            }
        }
        echo json_encode($output);
    } else if ($type == "updateIq") {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
        $status = isset($_GET["status"]) ? $conn->real_escape_string($_GET["status"]) : 'approve';
        if ($status === 'approve' || $status === 'Approved') {
            $status = 'approve';
        }
        $sql = "UPDATE equipment_iq SET status='" . $status . "', approve_by='" . $empId . "', approve_date='" . $entry_date . "' WHERE id=" . $id;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->real_escape_string($conn->error) . "\"}";
        }
    } else if ($type == "getLogIq") {
        $output = array();
        $sql = "SELECT * FROM equipment_iq WHERE status='approve' AND plant_id='" . $plantId . "' ORDER BY id DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = iq_decode_row($row);
            }
        }
        echo json_encode($output);
    } else {
        echo "{\"status\":\"unknown type\"}";
    }
} else {
    echo "Invalid Token";
}

$conn->close();
?>
