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
    
    if ($_GET["type"] == "saveEnvironment") {
        $sql = "INSERT INTO environment (isolates,organism ,entry_by,entry_date) VALUES('".$input["isolates"]."','".$input["organism"]."', '".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getEnvironment"){
        $output=Array();
        $sql="SELECT * FROM environment";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }
    else if($_GET["type"] == "getAutoclaves"){
        $output=Array();
       echo  $sql="SELECT id,equipment_code,equipment_name FROM equipment WHERE equipment_type='Autoclave' and plant_id = '".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
   
   
    }else if ($_GET["type"] == "downloadCalendar") {
        $_GET['filename'] = 'CALIBRATION SCHEDULE FOR INTERNAL CALIBRATION: QC012/F/01-04'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">CALIBRATION SCHEDULE FOR INTERNAL CALIBRATION</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 6%;">Instrument</td>
                    <td style="width: 6%;">Make</td>
                    <td style="width: 6%;">Model</td>
                     <td style="width: 4%;">ID	</td>
                    <td style="width: 6%;">Frequency</td>
                    <td style="width: 6%;">JAN</td>
                     <td style="width: 6%;">FEB</td>
                    <td style="width: 6%;">MAR</td>
                    <td style="width: 6%;">APR</td>
                     <td style="width: 6%;">MAY</td>
                    <td style="width: 6%;">JUN</td>
                    <td style="width: 6%;">JUL</td>
                     <td style="width: 6%;">AUG</td>
                    <td style="width: 6%;">SEP	</td>
                    <td style="width: 6%;">OCT</td>
                    <td style="width: 6%;">NOV</td>
                    <td style="width: 6%;">DEC</td>
                   
                </tr>
            </thead>';
        $i=1;
        $sql="SELECT * FROM environment";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                       
                        <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 4%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row[''].'</td>
                         <td style="width: 6%;">'.$row[''].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Workorder.pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>