<?php
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

    if ($_GET["type"] == "saveThermicOperation") {
        $sql = "INSERT INTO thermic_operation (pressure_inlet, pressure_outlet, temp_inlet, temp_outlet, stack_temp, entry_by, entry_date, entry_time) VALUES ('".$input["pressure_inlet"]."', '".$input["pressure_outlet"]."', '".$input["temp_inlet"]."', '".$input["temp_outlet"]."', '".$input["stack_temp"]."','".$_GET["emp_id"]."','$entry_date','$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getThermicOperations") {
        $output = array();
        $sql = "SELECT * FROM thermic_operation WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadThermicOperations") {
        $_GET['filename'] = 'Log Book For operation for Thermic Fluid Boiler '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 15%;">Date</td>
                    <td rowspan="2" style="width: 15%;">Time</td>
                    <td style="width: 20%;text-align:center;">Oil Pressure</td>
                    <td style="width: 20%;text-align:center;">Oil Temperature</td>
                    <td rowspan="2" style="width: 15%;">Stack Temp.(140c-170c)</td>
                    <td rowspan="2" style="width: 15%;">Reading Taken By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Inlet</td>
                    <td style="width:10%;">Outlet</td>
                    <td style="width:10%;">Inlet</td>
                    <td style="width:10%;">Outlet(220c-260c)</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM thermic_operation WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 15%;">'.$row['entry_time'].'</td>
                        <td style="width: 10%;">'.$row['pressure_inlet'].'</td>
                        <td style="width: 10%;">'.$row['pressure_outlet'].'</td>
                        <td style="width: 10%;">'.$row['temp_inlet'].'</td>
                        <td style="width: 10%;">'.$row['temp_outlet'].'</td>
                        <td style="width: 15%;">'.$row['stack_temp'].'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Log Book For operation for Thermic Fluid Boiler.pdf', 'I');
    }



}

$conn->close();
?>