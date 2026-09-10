<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0){
while($row = $result->fetch_assoc()){
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

    if ($_GET["type"] == "saveInstrument") {
        $sql = "INSERT INTO instruments(department,instrument_name ,location , tag_no , instrument_from , instrument_to ,operating_from ,operating_to ,tolerance_from,tolerance_to,frequency,calibration_mode , entry_by,entry_date)VALUES('".$input["department"]."' , '".$input["instrument_name"]."','".$input["location"]."','".$input["tag_no"]."','".$input["instrument_from"]."','".$input["instrument_to"]."' ,'".$input["operating_from"]."','".$input["operating_to"]."','".$input["tolerance_from"]."','".$input["tolerance_to"]."','".json_encode($input["frequency"])."','".$input["calibration_mode"]."' ,'".$_GET["emp_id"]."','$entry_date')";
          if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getInstrumentsLog"){
        $output = Array();
        $sql = "SELECT * FROM instruments WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["frequency"] = json_decode($row["frequency"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadInstrumentsLog") {
        $_GET['filename'] = 'Instruments Log'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 10%;">Instrument Name</td>
                    <td style="width: 5%;">Tag No</td>
                    <td style="width: 10%;">Department</td>
                    <td style="width: 10%;">Location </td>
                    <td style="width: 10%;">Calibration Frequency</td>
                    <td style="width: 10%;">Calibration Mode</td>
                    <td style="width: 10%;">Instrument Range</td>
                    <td style="width: 10%;">Operating Range</td>
                    <td style="width: 10%;">Tolerance Range</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM instruments WHERE status='approve'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["frequency"] = json_decode($row["frequency"]);
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 10%;">'.$row['instrument_name'].'</td>
                        <td style="width: 5%;">'.$row['tag_no'].'</td>
                        <td style="width: 10%;">'.$row['department'].'</td>
                        <td style="width: 10%;">'.$row['location'].'</td>
                        <td style="width: 10%;">'.$row['frequency'].'</td>
                        <td style="width: 10%;">'.$row['calibration_mode'].'</td>
                        <td style="width: 10%;">'.$row['instrument_from'].'*'.$row['instrument_to'].'</td>
                        <td style="width: 10%;">'.$row['operating_from'].'*'.$row['operating_to'].'</td>
                        <td style="width: 10%;">'.$row['tolerance_from'].'*'.$row['tolerance_to'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Instrument Log.pdf', 'I');
    } 
    
}

$conn->close();
?>