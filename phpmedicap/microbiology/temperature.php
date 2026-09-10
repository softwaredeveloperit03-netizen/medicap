<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveTemperature") {
        $sql = "INSERT INTO temperature (department, section, thermo_hygrometer, temperature, humidity, entry_by, entry_date, entry_time) VALUES ('Microbiology', '".$input["section"]."', '".$input["thermo_hygrometer"]."', '".$input["temperature"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTemperature") {
        $output = array();
        $sql = "SELECT * FROM temperature WHERE department='Microbiology'"; 
        //AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getThermohygrometers") {
        $output = array();
        $sql = "SELECT * FROM equipment WHERE department='Microbiology' AND equipment_name='Thermo hygrometer'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadTemperature") {
        $_GET['filename'] = 'Temperature'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Temperature</h2>
        <table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Date</td>
                        <td style="width:10%;">Time</td>
                        <td style="width:10%;">Area</td>
                        <td style="width:15%;">ThermoHygrometer</td>
                        <td style="width:15%;">Temperature</td>
                        <td style="width:15%;">Relative Humidity</td>
                        <td style="width:10%;">Done By</td>
                        <td style="width:10%;">Checked By</td>
                    </tr>';
        $sql = "SELECT * FROM temperature WHERE department='Microbiology' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                        <td style="width:15%;">'.$row['entry_date'].'</td>
                        <td style="width:10%;">'.$row['entry_time'].'</td>
                        <td style="width:10%;">'.$row['section'].'</td>
                        <td style="width:15%;">'.$row['thermo_hygrometer'].'</td>
                        <td style="width:15%;">'.$row['temperature'].'</td>
                        <td style="width:15%;">'.$row['humidity'].'</td>
                        <td style="width:10%;">'.$row['entry_by'].'</td>
                        <td style="width:10%;">'.$row['checked_by'].'</td>
                    </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Temperature.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
