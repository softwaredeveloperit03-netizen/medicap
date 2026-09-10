<?php

//   ini_set('display_errors', 1);
// error_reporting(E_ALL);

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

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
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    

    
 if($_GET["type"] == "saveFilterCleaning") {
    $sql = "INSERT INTO FilterCleaning (
                plant_id,
                date,
                from_date,
                to_date,
                check_by,
                filter_type,
                location,
                cleaned_by,
                status,
                remark,
                entry_by,
                entry_date,
                entry_time
            ) VALUES (
                '".$_GET["plant_id"]."',
                '".$input["date"]."','".$input["from"]."','".$input["to"]."','".$input["checked_by"]."',
                '".$input["filter_type"]."',
                '".$input["location"]."',
                '".$input["cleaned_by"]."',
                '".$input["status"]."',
                '".$input["remark"]."',
                '".$_GET["emp_id"]."',
                '$entry_date',
                '$entry_time'
            )";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}

else if($_GET["type"] == "getFilterCleaning") {
    $output = array();
    $sql = "SELECT * FROM FilterCleaning";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"] == "downloadFilterCleaning") {
    $_GET['filename'] = 'Filter Cleaning Log'; 
    $_GET['pdftype'] = 'landscape'; 
    include("../pdfimp2.php");
    $html = "";

    $html .= '<h3 style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center">RECORD OF DUST COLLECTOR</h3>
    <table border="1" cellpadding="5" class="table table-bordered">
    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
     
                            <th rowspan="2">DATE</th>
                            <th colspan="2">CLEANING</th>
                            <th rowspan="2">FILTER STATUS<br> DAMAGED / OK</th>
                            <th rowspan="2">CLEANED BY</th>
                            <th rowspan="2">CHECKED BY</th>
                            <th rowspan="2">REMARK</th>
                        </tr>
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <th>FROM</th>
                            <th>TO</th>
                         </tr>';

    $sql = "SELECT * FROM FilterCleaning";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>'.date('d-m-Y', strtotime($row['date'])).'</td>
                <td>'.$row['from_date'].'</td>
                <td>'.$row['to_date'].'</td>
                <td>'.$row['status'].'</td>
                <td>'.$row['cleaned_by'].'</td>
                <td>'.$row['entry_by'].'</td>
                <td>'.$row['remark'].'</td>
            </tr>';
        }
    }
 
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('FilterCleaning.pdf', 'I');



} else {
    echo "{\"status\":\"Invalid Token\"}";
}}

$conn->close();
?>