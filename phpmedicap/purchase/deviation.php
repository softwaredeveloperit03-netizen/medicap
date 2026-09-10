<?php
require '../db.php';
require '../token.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
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

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    $myfile = file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveRiskAssessment") 
    {
        $sql = "UPDATE deviation SET proposed_further_action='" . $input['proposed_further_action'] . "', 
            effect_upon_quality='" . $input['effect_upon_quality'] . "', 
            ran_reference='" . $input['ran_reference'] . "', 
            justification_risk_level='" . $input['risk_level_asssigned'] . "', 
            assigned_severity='" . $input['Assigned_Severty'] . "', 
            assigned_probability='" . $input['Assigned_Probability'] . "', 
            assigned_detectability='" . $input['Assigned_Detectability'] . "', 
            initial_risk_quality_assessment='" . json_encode($input['risk']) . "', 
            risk_assessment_by='" . $_GET['emp_id'] . "', 
            risk_assessment_date='$entry_date', 
            status='risk_assessment' 
            WHERE deviation_no='" . $input['deviation_no'] . "'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
   if ($_GET["type"] == "qaconditional_final_approval") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $sql = "update deviation set qa_approved_by='".$_GET['emp_id']."',qa_approved_date='$entry_date',status='complete' where deviation_no='".$input['deviation_no']."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
//     
 if ($_GET["type"] == "conditional_final_approval") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $sql = "update deviation set conditional_approvel='".$input['conditional_approval']."',final_approvel='".$input['Final_approval']."',department_approved_by='".$_GET['emp_id']."',department_approved_date='$entry_date',status='conditional_final' where deviation_no='".$input['deviation_no']."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    if ($_GET["type"] == "get_deviations") {
        $output = array();
        $sql = "SELECT * FROM `deviation`  a where a.departments like '%" . $_GET["dept"] . "%' and a.status='" . $_GET["status1"] . "'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    if ($_GET["type"] == "get_dev_no") {
        $output = array();
        $sql = "SELECT count(id)+1 as id FROM deviation";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output['dev_no'] = $row['id'];
            }
        }
        echo json_encode($output);
    }
    if ($_GET["type"] == "save_deviation_Attachment") {
        $sql = "insert into ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
