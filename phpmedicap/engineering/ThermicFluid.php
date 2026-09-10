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

// Authenticate token
$sql = "SELECT * FROM token WHERE token='$token'";
$result = $conn->query($sql);
$emp_id = '';
$department = '';

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
    $string = explode("$", $string);
    $_GET["emp_id"] = $string[0];
    $_GET["department"] = $string[1];

    // Log action
    $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR)
               VALUES ('FRONTEND', '$token', '" . $_GET["type"] . "', '$entry_date', '" . $_GET["department"] . "', '" . $_GET["emp_id"] . "', '" . $_SERVER['REQUEST_METHOD'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')";
    $conn->query($logSql);

    // Save record
    if ($_GET["type"] === "saveThermicRecord") {
        $sql = "INSERT INTO thermic_fluid_log (
            date, start_time, stop_time, total_runtime, check_by, verified_by, entry_by, entry_date, entry_time
        ) VALUES (
            '" . $input["date"] . "',
            '" . $input["start_time"] . "',
            '" . $input["stop_time"] . "',
            '" . $input["total_runtime"] . "',
            '" . $input["check_by"] . "',
            '" . $input["verified_by"] . "',
            '" . $_GET["emp_id"] . "',
            '$entry_date',
            '$entry_time'
        )";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => $conn->error]);
        }
    }
 else if ($_GET["type"] === "saveVerifyEntry") {
        $sql = "UPDATE thermic_fluid_log SET 
         verified_by  = '" . $_GET["emp_id"] . "',
         verified_on  = '$entry_date'
        WHERE id = '" . $_GET['id'] . "'";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}
    // Fetch records
    else if ($_GET["type"] === "getThermicRecords") {
        $output = [];
        $sql = "SELECT * FROM thermic_fluid_log ORDER BY date DESC";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }

    // Download PDF
    else if ($_GET["type"] === "downloadThermic") {
        $_GET['filename'] = 'Thermic Fluid Heater Log Book';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;">Thermic Fluid Heater Log Book</h3>
                <table border="1" cellpadding="5">
                    <thead>
                      <tr style="background-color:#DDDAD9;">
                            <th>Sr. No</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>Stop Time</th>
                            <th>Total Running Time</th>
                             <th>Done By</th>
                            <th>Verified By</th>
                     
                        </tr>
                    </thead><tbody>';

        $sql = "SELECT * FROM thermic_fluid_log ORDER BY date DESC";
        $result = $conn->query($sql);
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>
                        <td>" . $i++ . "</td>
                        <td>{$row['date']}</td>
                        <td>{$row['start_time']}</td>
                        <td>{$row['stop_time']}</td>
                        <td>{$row['total_runtime']}</td>
                        <td>{$row['entry_by']}</td>
                        <td>{$row['verified_by']}</td>
                
                    </tr>";
        }

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Thermic-Fluid-Heater-Log.pdf', 'I');
    }

} else {
    echo json_encode(["status" => "Invalid Token"]);
}

$conn->close();
?>
