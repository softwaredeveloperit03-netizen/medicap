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

// Token validation
$sql = "SELECT * FROM token WHERE token='$token'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
    [$emp_id, $department] = explode("$", $string);

    $type = $_GET["type"];

    if ($type === "saveTankCleaningRecord") {
        $sql = "INSERT INTO tank_cleaning_log (
            date, tools_used, cleaning_agent,
            raw_70kl_start, raw_70kl_stop, raw_70kl_status,
            dm_raw_start, dm_raw_stop, dm_raw_status,
            dm_ug_start, dm_ug_stop, dm_ug_status,
            overhead25_start, overhead25_stop, overhead25_status,
            overhead40_start, overhead40_stop, overhead40_status,
            done_by, checked_by, verified_by
        ) VALUES (
            '".$input["date"]."', '".$input["tools_used"]."', '".$input["cleaning_agent"]."',
            '".$input["raw_70kl_start"]."', '".$input["raw_70kl_stop"]."', '".$input["raw_70kl_status"]."',
            '".$input["dm_raw_start"]."', '".$input["dm_raw_stop"]."', '".$input["dm_raw_status"]."',
            '".$input["dm_ug_start"]."', '".$input["dm_ug_stop"]."', '".$input["dm_ug_status"]."',
            '".$input["overhead25_start"]."', '".$input["overhead25_stop"]."', '".$input["overhead25_status"]."',
            '".$input["overhead40_start"]."', '".$input["overhead40_stop"]."', '".$input["overhead40_status"]."',
            '".$input["done_by"]."', '".$input["checked_by"]."', '".$input["verified_by"]."'
        )";

        echo $conn->query($sql)
            ? "{\"status\":\"success\"}"
            : "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
    }
else if ($type === "getTankCleaningRecords") {
        $output = [];
        $result = $conn->query("SELECT * FROM tank_cleaning_log ORDER BY date DESC");
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else if ($type === "downloadTankCleaningPDF") {
        $_GET['filename'] = 'Tank Cleaning Log';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;">TANK CLEANING LOG</h3><table border="1" cellpadding="3"><thead>
            <tr>
                <th>Date</th><th>Tools Used</th><th>Agent</th>
                <th>70KL Start</th><th>Stop</th><th>Status</th>
                <th>DM RW Start</th><th>Stop</th><th>Status</th>
                <th>DM UG Start</th><th>Stop</th><th>Status</th>
                <th>25KL Start</th><th>Stop</th><th>Status</th>
                <th>40KL Start</th><th>Stop</th><th>Status</th>
                <th>Done By</th><th>Checked By</th><th>Verified By</th>
            </tr></thead><tbody>';

        $result = $conn->query("SELECT * FROM tank_cleaning_log ORDER BY date DESC");
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>'.$row["date"].'</td><td>'.$row["tools_used"].'</td><td>'.$row["cleaning_agent"].'</td>
                <td>'.$row["raw_70kl_start"].'</td><td>'.$row["raw_70kl_stop"].'</td><td>'.$row["raw_70kl_status"].'</td>
                <td>'.$row["dm_raw_start"].'</td><td>'.$row["dm_raw_stop"].'</td><td>'.$row["dm_raw_status"].'</td>
                <td>'.$row["dm_ug_start"].'</td><td>'.$row["dm_ug_stop"].'</td><td>'.$row["dm_ug_status"].'</td>
                <td>'.$row["overhead25_start"].'</td><td>'.$row["overhead25_stop"].'</td><td>'.$row["overhead25_status"].'</td>
                <td>'.$row["overhead40_start"].'</td><td>'.$row["overhead40_stop"].'</td><td>'.$row["overhead40_status"].'</td>
                <td>'.$row["done_by"].'</td><td>'.$row["checked_by"].'</td><td>'.$row["verified_by"].'</td>
            </tr>';
        }

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Tank_Cleaning_Log.pdf', 'I');
    }

} else {
    echo "{\"status\":\"Invalid Token\"}";
}
$conn->close();
?>
