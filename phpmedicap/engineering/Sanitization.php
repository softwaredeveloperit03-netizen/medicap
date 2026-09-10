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

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";

$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
    [$emp_id, $department] = explode("$", $string);

    $conn->query("INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR)
        VALUES ('FRONTEND', '$token', '".$_GET["type"]."', '$entry_date', '$department', '$emp_id',
        '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."')");

    $type = $_GET["type"];

    // Save record
    if ($type === "saveSanitizationRecord") {
        
        $sql = "INSERT INTO sanitization_log (date, agent_used, ar_no, start_time, end_time,temp, done_by, qc_ar_no, checked_by, remarks, sanitization_of ) VALUES 
        ('".$input["date"]."', '".$input["agent_used"]."', '".$input["ar_no"]."', '".$input["start_time"]."', '".$input["end_time"]."','".$input["temp"]."', 
        '".$input["done_by"]."', '".$input["qc_ar_no"]."', '".$_GET["emp_id"]."', '".$input["remarks"]."', '".$input["SanitizationOf"]."')";

        echo $conn->query($sql)
            ? "{\"status\":\"success\"}"
            : "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
    }
else if ($type === "saveRecordUV") {

    $input = json_decode(file_get_contents("php://input"), true);

    // First, insert the main record into uv_record
    $sql = "INSERT INTO uv_record (equipment_id, location, cleaning_done, next_due, created_by) VALUES (
        '".$input["equipment_id"]."', 
        '".$input["location"]."', 
        '".$input["cleaning_done"]."', 
        '".$input["next_due"]."', 
        '".$_GET["emp_id"]."'
    )";

    if ($conn->query($sql)) {
        $uv_record_id = $conn->insert_id;

        // Insert each log into uv_log_entry
        foreach ($input["logs"] as $log) {
            $sqlLog = "INSERT INTO uv_log_entry (uv_record_id, log_date, uv_reading, total_hours, remark) VALUES (
                '$uv_record_id',
                '".$log["date"]."',
                '".$log["uv_reading"]."',
                '".$log["total_hours"]."',
                '".$log["remark"]."'
            )";

            $conn->query($sqlLog);
        }

        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
    }
}

else if ($type === "getUVRecords") {
    $records = [];

    // Get all UV records
    $mainQuery = "SELECT * FROM uv_record ORDER BY id DESC";
    $mainResult = $conn->query($mainQuery);

    while ($row = $mainResult->fetch_assoc()) {
        $recordId = $row['id'];

        // Get log entries for this record
        $logQuery = "SELECT * FROM uv_log_entry WHERE uv_record_id = $recordId";
        $logResult = $conn->query($logQuery);

        $logs = [];
        while ($logRow = $logResult->fetch_assoc()) {
            $logs[] = $logRow;
        }

        $row['logs'] = $logs;
        $records[] = $row;
    }

    echo json_encode($records);
}

    // Get records
    
    // Get records
    else if ($type === "getSanitizationRecords") {
        $output = [];
        $result = $conn->query("SELECT * FROM sanitization_log ORDER BY date DESC");
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEquiptment") {
        $output = [];
        $sql = "SELECT * FROM equipment";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }

else if($_GET["type"] == "getSanitizationOf") {
         $output = [];
            $sql = "SELECT * FROM others_material WHERE material_name Like '%Tank%'order by 1 desc";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
    // Download PDF
    else if ($type === "downloadSanitizationPDF") {
        $_GET['filename'] = 'Sanitization Log';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">RECORD FOR SANITIZATION OF PURIFIED WATER STORAGE TANK, PIPE LINES AND LOOPS</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <th>Sr. No.</th>
                    <th>Sanitization Of</th>
                    <th>Date</th>
                    <th>Sanitizing Agent Used</th>
                    <th>Medicap lot no</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Water Temp</th>
                    <th>Done By</th>
                    <th>Medicap lot no</th>
                    <th>Checked By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>';

        $result = $conn->query("SELECT * FROM sanitization_log ORDER BY date DESC");
        $i=1;
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>'.$i++.'</td>
                <td>'.$row["sanitization_of"].'</td>
                <td>'.$row["date"].'</td>
                <td>'.$row["agent_used"].'</td>
                <td>'.$row["ar_no"].'</td>
                <td>'.$row["start_time"].'</td>
                <td>'.$row["end_time"].'</td>
                <td>'.$row["temp"].'</td>
                <td>'.$row["done_by"].'</td>
                <td>'.$row["qc_ar_no"].'</td>
                <td>'.$row["checked_by"].'</td>
                <td>'.$row["remarks"].'</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Sanitization_Log.pdf', 'I');
    }

} else {
    echo "{\"status\":\"Invalid Token\"}";
}

$conn->close();
?>
