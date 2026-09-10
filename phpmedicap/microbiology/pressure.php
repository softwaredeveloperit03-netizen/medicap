<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';


// error_reporting(E_ALL);
// ini_set('display_errors', 1);


header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    
    if ($_GET["type"] == "savePressure") {
        $sql = "INSERT INTO micro_pressure (colling_room,media_pripration,airlock3,laf_room, secondary_room, airlock2 ,airlock1 ,record_by,entry_by, entry_date, entry_time) VALUES 
        ('".$input["colling_room"]."','".$input["media_pripration"]."','".$input["airlock3"]."','".$input["laf_room"]."', '".$input["secondary_room"]."' ,'".$input["airlock2"]."', '".$input["airlock1"]."', '".$input["record_by"]."',  '".$_GET["emp_id"]."' ,'$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPressureLog") {
        $output = array();
        $sql = "SELECT * FROM micro_pressure ";
        //WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadPressureLog") {
        $_GET['filename'] = 'PressureLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">PressureLog</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 5%;">Sr.</td>
                    <td rowspan="2" style="width: 11%;">Date</td>
                    <td rowspan="2" style="width: 10%;">Time</td>
                    <td style="width: 55%; text-align:center;">Pressure(in Pascal)</td>
                    <td rowspan="2" style="width: 10%;">Recorded By</td>
                    <td rowspan="2" style="width: 10%;">checked By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:14%;">LAF Room(NLT 6 pa)</td>
                    <td style="width:14%;">Secondary Change Room (NLT 6 pa)</td>
                    <td style="width:14%;">AirLock-II (NLT 6 pa)</td>
                    <td style="width:13%;">Airlock-I (NLT 6 pa)</td>
                </tr>
            </thead>';
        $i=1;
        $sql = "SELECT * FROM micro_pressure WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 11%;">'.$row['entry_date'].'</td>
                        <td style="width: 10%;">'.$row['entry_time'].'</td>
                        <td style="width: 14%;">'.$row['laf_room'].'</td>
                        <td style="width: 14%;">'.$row['secondary_room'].'</td>
                        <td style="width: 14%;">'.$row['airlock2'].'</td>
                        <td style="width: 13%;">'.$row['airlock1'].'</td>
                        <td style="width: 10%;">'.$row['record_by'].'</td>
                        <td style="width: 10%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PressureLog.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
