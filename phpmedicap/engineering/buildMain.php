<?php
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
   
if ($type === "saveBuildMain") {
    $sql = "INSERT INTO months_data (
        area, April, August, December, February, January, July, June, March, May, November, October, September
    ) VALUES (
        '".$input["area0"]."', '".$input["April0"]."', '".$input["August0"]."', '".$input["December0"]."',
        '".$input["February0"]."', '".$input["January0"]."', '".$input["July0"]."', '".$input["June0"]."',
        '".$input["March0"]."', '".$input["May0"]."', '".$input["November0"]."', '".$input["October0"]."',
        '".$input["September0"]."'
    )";

    echo $conn->query($sql) ? "{\"status\":\"success\"}" : "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
}


    // Get records
    else if ($type === "getRecords") {
        $output = [];
        $result = $conn->query("SELECT * FROM months_data");
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
      else if ($type === "getLocation") {
        $output = [];
        $result = $conn->query("SELECT * FROM section");
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
else if ($type === "downloadMainPDF") {
    $_GET['filename'] = 'Building Maintenance Schedule';
    $_GET['pdftype'] = 'landscape'; // Landscape orientation
    include("../pdfimp2.php"); // Include the PDF library

    // Define the HTML content for the PDF
    $html = '<h3 style="text-align:center; background-color:#DDDAD9; font-weight:bold;">BUILDING MAINTENANCE SCHEDULE</h3>
    <table border="1" cellpadding="5">
        <thead>
            <tr style="background-color:#DDDAD9; font-weight:bold;">
                <th>Sr. No.</th>
                <th>Area / Room Name</th>
                <th>January</th>
                <th>February</th>
                <th>March</th>
                <th>April</th>
                <th>May</th>
                <th>June</th>
                <th>July</th>
                <th>August</th>
                <th>September</th>
                <th>October</th>
                <th>November</th>
                <th>December</th>
            </tr>
        </thead>
        <tbody>';

    // Query to get the maintenance schedule data from the database
    $result = $conn->query("SELECT * FROM months_data ORDER BY area ASC");

    // Loop through the query result and add the data to the table rows
    $srNo = 1; // Serial Number for each record
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
            <td>' . $srNo++ . '</td>
            <td>' . $row["area"] . '</td>
            <td>' . ($row["January"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["February"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["March"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["April"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["May"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["June"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["July"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["August"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["September"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["October"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["November"] == '1' ? 'DONE' : '-') . '</td>
            <td>' . ($row["December"] == '1' ? 'DONE' : '-') . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    // Use the PDF library to write the HTML content to the PDF
    $pdf->writeHTML($html, true, false, false, false, '');

    // Output the PDF to the browser (or save it to the server)
    $pdf->Output('Building_Maintenance_Schedule.pdf', 'I'); // 'I' for inline display in browser, 'D' for download
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
                <td>'.$row["checked_by"].'</td>
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
