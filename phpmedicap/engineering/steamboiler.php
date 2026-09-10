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

    if ($_GET["type"] == "saveSteamOperation") {
        $sql = "INSERT INTO steam_operation (boiler_no, meter_reading, diff, pump_no, pressure, RPM1, RPM2, BED1, BED2, draught_pr, tank_level, steam_pr, entry_by, entry_date, entry_time) VALUES ('".$input["boiler_no"]."', '".$input["meter_reading"]."', '".$input["diff"]."', '".$input["pump_no"]."', '".$input["pressure"]."', '".$input["RPM1"]."', '".$input["RPM2"]."', '".$input["BED1"]."', '".$input["BED2"]."', '".$input["draught_pr"]."', '".$input["tank_level"]."', '".$input["steam_pr"]."','".$_GET["emp_id"]."','$entry_date','$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getSteamOperations") {
        $output = array();
        $sql = "SELECT * FROM steam_operation WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadSteamOperation") {
        $_GET['filename'] = 'Log Book For Steam Generation Boiler'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 10%;">Date</td>
                    <td rowspan="2" style="width: 5%;">Time</td>
                    <td rowspan="2" style="width: 9%;">Boiler No</td>
                    <td style="width: 10%;">Feed W Flow</td>
                    <td style="width: 10%;">Feed Water pumps Details</td>
                    <td style="width: 10%;">RPM of Fulel Feeder</td>
                    <td style="width: 10%;">Bed Temperature</td>
                    <td rowspan="2" style="width: 9%;">Draught pressure</td>
                    <td rowspan="2" style="width: 9%;">Feed Water Tank level in cm</td>
                    <td rowspan="2" style="width: 9%;">Boiler Steam Pressure</td>
                    <td rowspan="2" style="width: 9%;">Reading Taken By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Mater Reading</td>
                    <td style="width:5%;">Diff</td>
                    <td style="width:5%;">Pump No</td>
                    <td style="width:5%;">pr.(kg/cm2)</td>
                    <td style="width:5%;">No.1</td>
                    <td style="width:5%;">No2</td>
                    <td style="width:5%;">Bed:1</td>
                    <td style="width:5%;">Bed:2</td>
                </tr>
            </thead>';
            $i=1;
        $sql = "SELECT * FROM steam_operation WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 5%;">'.$row['entry_time'].'</td>
                        <td style="width: 9%;">'.$row['boiler_no'].'</td>
                        <td style="width: 5%;">'.$row['meter_reading'].'</td>
                        <td style="width: 5%;">'.$row['diff'].'</td>
                        <td style="width: 5%;">'.$row['pump_no'].'</td>
                        <td style="width: 5%;">'.$row['pressure'].'</td>
                        <td style="width: 5%;">'.$row['RPM1'].'</td>
                        <td style="width: 5%;">'.$row['RPM2'].'</td>
                        <td style="width: 5%;">'.$row['BED1'].'</td>
                        <td style="width: 5%;">'.$row['BED2'].'</td>
                        <td style="width: 9%;">'.$row['draught_pr'].'</td>
                        <td style="width: 9%;">'.$row['tank_level'].'</td>
                        <td style="width: 9%;">'.$row['steam_pr'].'</td>
                        <td style="width: 9%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Log Book For Steam Generation Boiler.pdf', 'I');
    }

else if ($_GET["type"] == "save_boiler_parameter") 
    {
          $sql = "INSERT INTO boiler_request (plant_id , boiler_id, test_type, location, test_date, test_time, 
        pressure,temperature,corre_date,corre_time,corre_point,  
        volume,treat_chemical,add_comments,req_name,req_polition,req_email,req_phone)
     VALUES ('".$_GET["plant_id"]."','".$input["boiler_id"]."', '".$input["test_type"]."', '".$input["location"]."', '".$input["test_date"]."', '".$input["test_time"]."',
        '".$input["pressure"]."', '".$input["temperature"]."', '".$input["corre_date"]."', '".$input["corre_time"]."', '".$input["corre_point"]."',
        '".$input["volume"]."','".$input["treat_chemical"]."','".$input["add_comments"]."','".$input["req_name"]."','".$input["req_polition"]."','".$input["req_email"]."','".$input["req_phone"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_boiler_test") {
    
            $sql = "SELECT * FROM boiler_new order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
}


}

$conn->close();
?>