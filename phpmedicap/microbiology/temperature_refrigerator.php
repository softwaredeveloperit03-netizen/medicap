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
        $sql = "INSERT INTO temp_refrigerator (ref_id,thermo_id,entry_time,entry_by,observe_temp,min_temp,max_temp ,done_by,entry_date) VALUES
        ('".$input["ref_id"]."','".$input["thermo_id"]."', '$entry_time' , '".$_GET["emp_id"]."', '".$input["observe_temp"]."','".$input["min_temp"]."', '".$input["max_temp"]."','".$_GET["emp_id"]."' ,'$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getTemperature"){
        $output=Array();
        $sql="SELECT * FROM temp_refrigerator WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
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
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 11%;">Date</td>
                        <td rowspan="2" style="width: 9%;">Time</td>
                        <td rowspan="2" style="width: 14%;">Referigerator Id No</td>
                        <td rowspan="2" style="width: 15%;">Thermometer ID No</td>
                        <td rowspan="2" style="width: 13%;">Observed Temperature of chember(oc)</td>
                        <td style="width:18%;">Temperature</td>
                        <td rowspan="2" style="width: 10%;">Done By</td>
                        <td rowspan="2" style="width: 10%;">Checked By</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:9%;">Min</td>
                        <td style="width:9%;">Max</td>
                    </tr>
                </thead>';
        $sql="SELECT * FROM temp_refrigerator WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 11%;">'.$row['entry_date'].'</td>
                    <td style="width: 9%;">'.$row['entry_time'].'</td>
                    <td style="width: 14%;">'.$row['ref_id'].'</td>
                    <td style="width: 15%;">'.$row['thermo_id'].'</td>
                    <td style="width: 13%;">'.$row['observe_temp'].'</td>
                    <td style="width: 9%;">'.$row['min_temp'].'</td>
                    <td style="width: 9%;">'.$row['max_temp'].'</td>
                    <td style="width: 10%;">'.$row['done_by'].'</td>
                    <td style="width: 10%;">'.$row['entry_by'].'</td>
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