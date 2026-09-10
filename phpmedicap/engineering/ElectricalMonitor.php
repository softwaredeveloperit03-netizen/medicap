<?php

 // ini_set('display_errors', 1);
    // error_reporting(E_ALL); 

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);

// Auth
$sql = "SELECT * FROM token WHERE token='$token'";
$result = $conn->query($sql);
$emp_id = '';
$department = '';

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
    [$emp_id, $department] = explode("$", $string);

    $conn->query("INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR)
        VALUES ('FRONTEND','$token','".$_GET["type"]."','$entry_date','$department','$emp_id',
        '".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')");

    $type = $_GET["type"];

    // Save record
    if ($type === "saveRecord") {
        $sql = "INSERT INTO electrical_monitor (
            date_time, input_voltage_r, input_voltage_y, input_voltage_b,
            output_voltage_r, output_voltage_y, output_voltage_b,
            output_current_r, output_current_y, output_current_b,
            battery_voltage, battery_current, checked_by, remark,
            entry_by, entry_date
        ) VALUES (
            '".$input["date_time"]."',
            '".$input["input_voltage_r"]."', '".$input["input_voltage_y"]."', '".$input["input_voltage_b"]."',
            '".$input["output_voltage_r"]."', '".$input["output_voltage_y"]."', '".$input["output_voltage_b"]."',
            '".$input["output_current_r"]."', '".$input["output_current_y"]."', '".$input["output_current_b"]."',
            '".$input["battery_voltage"]."', '".$input["battery_current"]."',
            '".$input["checked_by"]."', '".$input["remark"]."',
            '$emp_id', '$entry_date'
        )";

        echo $conn->query($sql) ? "{\"status\":\"success\"}" : "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
    }

    // Get records
    else if ($type === "getRecords") {
        $output = [];
        $result = $conn->query("SELECT * FROM electrical_monitor ORDER BY date_time DESC");
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }

    // Download as PDF
    else if ($type === "downloadPDF") {
        $_GET['filename'] = 'Electrical Monitoring Report';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">ELECTRICAL MONITORING LOG</h3>
        <table border="1" cellpadding="5">
        <thead>
        <tr style="background-color:#DDDAD9;font-weight:bold;">
            <th>Date & Time</th>
            <th>Input Voltage R</th>
            <th>Input Voltage Y</th>
            <th>Input Voltage B</th>
            <th>Output Voltage R</th>
            <th>Output Voltage Y</th>
            <th>Output Voltage B</th>
            <th>Output Current R</th>
            <th>Output Current Y</th>
            <th>Output Current B</th>
            <th>Battery Voltage</th>
            <th>Battery Current</th>
            <th>Checked By</th>
            <th>Remark</th>
        </tr>
        </thead><tbody>';

        $result = $conn->query("SELECT * FROM electrical_monitor ORDER BY date_time DESC");
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>'.$row["date_time"].'</td>
                <td>'.$row["input_voltage_r"].'</td>
                <td>'.$row["input_voltage_y"].'</td>
                <td>'.$row["input_voltage_b"].'</td>
                <td>'.$row["output_voltage_r"].'</td>
                <td>'.$row["output_voltage_y"].'</td>
                <td>'.$row["output_voltage_b"].'</td>
                <td>'.$row["output_current_r"].'</td>
                <td>'.$row["output_current_y"].'</td>
                <td>'.$row["output_current_b"].'</td>
                <td>'.$row["battery_voltage"].'</td>
                <td>'.$row["battery_current"].'</td>
                <td>'.$row["entry_by"].'</td>
                <td>'.$row["remark"].'</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Electrical_Monitoring_Report.pdf', 'I');
    }

} else {
    echo "{\"status\":\"Invalid Token\"}";
}

$conn->close();
?>
