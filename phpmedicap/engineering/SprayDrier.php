<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");


 // ini_set('display_errors', 1);
    // error_reporting(E_ALL); 




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
    $string = explode("$", $string);
    $_GET["emp_id"] = $string[0];
    $_GET["department"] = $string[1];

    // Log action
    $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR)
               VALUES ('FRONTEND', '$token', '" . $_GET["type"] . "', '$entry_date', '" . $_GET["department"] . "', '" . $_GET["emp_id"] . "', '" . $_SERVER['REQUEST_METHOD'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')";
    $conn->query($logSql);

    // Handle save
    if ($_GET["type"] == "saveSprayDrierRecord") {
        $sql = "INSERT INTO spray_drier_log (
        equipment_id,
                    sd_start_date,
                    sd_start_time,
                    started_by,
                    inlet_temp,
                    outlet_temp,
                    vacuum,
                    sd_stop_date,
                    sd_stop_time,
                    stopped_by,
                    remarks,
                    entry_by,
                    entry_date,
                    entry_time
                ) VALUES (
                 '" . $input["equipment_id"] . "',
                    '" . $input["sd_start_date"] . "',
                    '" . $input["sd_start_time"] . "',
                    '" . $input["started_by"] . "',
                    '" . $input["inlet_temp"] . "',
                    '" . $input["outlet_temp"] . "',
                    '" . $input["vacuum"] . "',
                    '" . $input["sd_stop_date"] . "',
                    '" . $input["sd_stop_time"] . "',
                    '" . $input["stopped_by"] . "',
                    '" . $input["remarks"] . "',
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

    // Handle fetch
    else if ($_GET["type"] == "getSprayDrierRecords") {
        $output = [];
        $sql = "SELECT * FROM spray_drier_log ORDER BY entry_date DESC";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }

  else if ($_GET["type"] == "getEquiptment") {
        $output = [];
        $sql = "SELECT * FROM equipment where equipment_type Like '%Spray%' AND plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }

    // Handle download PDF
    else if ($_GET["type"] == "downloadSprayDrier") {
        $_GET['filename'] = 'Spray Drier Operation Log Book';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;">Spray Drier Operation Log Book</h3>
                <table border="1" cellpadding="5">
                    <thead>
                      <tr style="background-color:#DDDAD9;">
                            <th>Sr. No</th>
                             <th>Equipment Name</th>
                            <th>Start Date</th>
                            <th>Start Time</th>
                            <th>Started By</th>
                            <th>Inlet Temp</th>
                            <th>Outlet Temp</th>
                            <th>Vacuum</th>
                            <th>Stop Date</th>
                            <th>Stop Time</th>
                            <th>Stopped By</th>
                            <th>Remarks</th>
                            <th>Entry By</th>
                            <th>Entry Date</th>
                        </tr>
                    </thead><tbody>';

        $sql = "SELECT * FROM spray_drier_log ORDER BY entry_date DESC";
        $result = $conn->query($sql);
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>
                        <td>" . $i++ . "</td>
                        <td>{$row['equipment_id']}</td>
                        <td>{$row['sd_start_date']}</td>
                        <td>{$row['sd_start_time']}</td>
                        <td>{$row['started_by']}</td>
                        <td>{$row['inlet_temp']}</td>
                        <td>{$row['outlet_temp']}</td>
                        <td>{$row['vacuum']}</td>
                        <td>{$row['sd_stop_date']}</td>
                        <td>{$row['sd_stop_time']}</td>
                        <td>{$row['stopped_by']}</td>
                        <td>{$row['remarks']}</td>
                        <td>{$row['entry_by']}</td>
                        <td>{$row['entry_date']}</td>
                    </tr>";
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Spray-Drier-Log.pdf', 'I');
    }

} else {
    echo json_encode(["status" => "Invalid Token"]);
}

$conn->close();

?>
