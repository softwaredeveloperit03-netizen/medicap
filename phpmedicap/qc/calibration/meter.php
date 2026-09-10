<?php
require '../../db.php';
require '../../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = array();
}

$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $esc = function ($value) use ($conn) {
        return $conn->real_escape_string(trim((string)($value ?? '')));
    };
    $plantId = $esc(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');

    $conn->query("CREATE TABLE IF NOT EXISTS ph_daily_calibration (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) DEFAULT NULL,
        date DATE DEFAULT NULL,
        buffer VARCHAR(255) DEFAULT NULL,
        remark VARCHAR(255) DEFAULT NULL,
        acceptance VARCHAR(255) DEFAULT NULL,
        done_by VARCHAR(255) DEFAULT NULL,
        checked_by VARCHAR(255) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        entry_by VARCHAR(50) DEFAULT NULL,
        entry_date DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ph_buffer_readymade (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) DEFAULT NULL,
        received_date DATE DEFAULT NULL,
        manufactured_name VARCHAR(255) DEFAULT NULL,
        batch_no VARCHAR(100) DEFAULT NULL,
        lot_no VARCHAR(100) DEFAULT NULL,
        received_qty VARCHAR(100) DEFAULT NULL,
        issued_qty VARCHAR(100) DEFAULT NULL,
        balance_qty VARCHAR(100) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        entry_by VARCHAR(50) DEFAULT NULL,
        entry_date DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ($_GET["type"] == "saveDailyPh") {
        $sql = "INSERT INTO ph_daily_calibration
                (plant_id, date, buffer, remark, acceptance, done_by, checked_by, status, entry_by, entry_date)
                VALUES (
                    '".$plantId."',
                    '".$esc($input['date'] ?? '')."',
                    '".$esc($input['buffer'] ?? '')."',
                    '".$esc($input['remark'] ?? '')."',
                    '".$esc($input['acceptance'] ?? '')."',
                    '".$esc($input['done'] ?? '')."',
                    '".$esc($input['checked'] ?? '')."',
                    'pending',
                    '".$esc($_GET['emp_id'])."',
                    '$entry_date'
                )";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'failed', 'message' => $conn->error));
        }
    } else if ($_GET["type"] == "getDailyPh") {
        $output = array();
        $sql = "SELECT id, date, buffer, remark, acceptance, done_by AS done, checked_by AS checked, status, entry_date
                FROM ph_daily_calibration
                WHERE 1=1";
        if ($plantId !== '') {
            $sql .= " AND plant_id='".$plantId."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveReadymadeBuffer") {
        $sql = "INSERT INTO ph_buffer_readymade
                (plant_id, received_date, manufactured_name, batch_no, lot_no, received_qty, issued_qty, balance_qty, status, entry_by, entry_date)
                VALUES (
                    '".$plantId."',
                    '".$esc($input['received_date'] ?? '')."',
                    '".$esc($input['manufactured_name'] ?? '')."',
                    '".$esc($input['batch_no'] ?? '')."',
                    '".$esc($input['lot_no'] ?? '')."',
                    '".$esc($input['received_qty'] ?? '')."',
                    '".$esc($input['issued_qty'] ?? '')."',
                    '".$esc($input['balance_qty'] ?? '')."',
                    'pending',
                    '".$esc($_GET['emp_id'])."',
                    '$entry_date'
                )";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'failed', 'message' => $conn->error));
        }
    } else if ($_GET["type"] == "getReadymadeBuffer") {
        $output = array();
        $sql = "SELECT id,
                       received_date AS date,
                       manufactured_name AS name,
                       batch_no,
                       lot_no,
                       received_qty,
                       issued_qty,
                       balance_qty,
                       status,
                       entry_date
                FROM ph_buffer_readymade
                WHERE 1=1";
        if ($plantId !== '') {
            $sql .= " AND plant_id='".$plantId."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>
