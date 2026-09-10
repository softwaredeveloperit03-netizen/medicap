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
    
    if ($_GET["type"] == "saveColony") {
        $sql = "INSERT INTO colony (activity,batch_no,from_batch,to_batch,done_by,remark,entry_by,entry_date,colony_name,colony_id) 
        VALUES('".$input["activity"]."','".$input["batch_no"]."','".$input["from_batch"]."','".$input["to_batch"]."','".$input["done_by"]."','".$input["remark"]."', '".$_GET["emp_id"]."','$entry_date','".$input["colony_name"]."','".$input["colony_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getColony"){
        $output=Array();
        $sql="SELECT * FROM colony"; //WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveCalibration") {
        $sql = "INSERT INTO colony_calibration (equipment_code, details, conclusion, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', '".json_encode($input["details"])."', '".$input["conclusion"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCalibrationRecords") {
        $output=Array();
        $sql="SELECT * FROM colony_calibration"; 
        //WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
   
    } else if ($_GET["type"] == "downloadColony") {
        $_GET['filename'] = 'Colony'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">USAGE RECORD OF COLONY COUNTER</h3>
            <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 15%;">Date</td>
                    <td style="width: 15%;">Activity</td>
                    <td style="width: 10%;">Batch No</td>
                    <td style="width: 10%;">From</td>
                    <td style="width: 10%;">To</td>
                    <td style="width: 10%;">Done By</td>
                    <td style="width: 10%;">Checked By</td>
                    <td style="width: 10%;">Remark</td>
                </tr>
            </thead>';
        $sql="SELECT * FROM colony WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 15%;">'.date("d/m/Y",($row['entry_date'])).'</td>
                        <td style="width: 15%;">'.$row['activity'].'</td>
                        <td style="width: 10%;">'.$row['batch_no'].'</td>
                        <td style="width: 10%;">'.$row['from_batch'].'</td>
                        <td style="width: 10%;">'.$row['to_batch'].'</td>
                        <td style="width: 10%;">'.$row['done_by'].'</td>
                        <td style="width: 10%;">'.$row['entry_by'].'</td>
                        <td style="width: 10%;">'.$row['remark'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Colony', 'I');
    }else if ($_GET["type"] == "downloadCalibrationRecord") {
        $_GET['filename'] = 'CalibrationRecord'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $sql="SELECT * FROM colony_calibration WHERE id='".$_GET["id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
        $html.='
        <h2 style="text-align:center">CalibrationRecord</h2>
        <table border="1" cellpadding="3">
                    <tr>
                        <td style="width:25%; font-weight:bold;">Date</td>
                        <td style="width:25%;">'.date("d-m-Y", strtotime($row['entry_date'])).'</td>
                        <td style="width:25%;font-weight:bold;">Instrument Id</td>
                        <td style="width:25%;">'.$row['equipment_code'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Conclusion</td>
                        <td style="width:25%;">'.$row['conclusion'].'</td>
                        <td style="width:25%;font-weight:bold;">Status</td>
                        <td style="width:25%;">'.$row['status'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Entry By</td>
                        <td style="width:25%;">'.$row['entry_by'].'</td>
                        <td style="width:25%;font-weight:bold;"></td>
                        <td style="width:25%;"></td>
                    </tr>
                </table>
                <div></div>
                <table border="1" cellpadding="5">
                    <tr>
                        <td rowspan="3" style="width:11%;font-weight:bold;">Sr No</td>
                        <td style="width:90%;Text-align:center;font-weight:bold;">No of time Pressed</td>
                    </tr>
                    <tr>
                        <td style="width:30%;text-align:center;font-weight:bold;">(10 Time)</td>
                        <td style="width:30%;text-align:center;font-weight:bold;font-weight:bold;">(50Time)</td>
                        <td style="width:30%;text-align:center;font-weight:bold;">(100times)</td>
                    </tr>
                    <tr>
                        <td style="width:15%;font-weight:bold;">Manually</td>
                        <td style="width:15%;font-weight:bold;">Display</td>
                        <td style="width:15%;font-weight:bold;">Manually</td>
                        <td style="width:15%;font-weight:bold;">Display</td>
                        <td style="width:15%;font-weight:bold;">Manually</td>
                        <td style="width:15%;font-weight:bold;">Display</td>
                    </tr>';
                    $row["details"] = json_decode($row["details"]);
                    $details=$row["details"];
                    $j=1;
                    for($i=0; $i<count($details);$i++){
                         $detail=$details[$i];
           $html.=' <tr>
                        <td style="width:11%;">'.$j++.'</td>
                        <td style="width:15%;">'.$detail->manually10.'</td>
                        <td style="width:15%;">'.$detail->display10.'</td>
                        <td style="width:15%;">'.$detail->manually50.'</td>
                        <td style="width:15%;">'.$detail->display50.'</td>
                        <td style="width:15%;">'.$detail->manually100.'</td>
                        <td style="width:15%;">'.$detail->display100.'</td>
                    </tr>';
                    }
            $html.='<tr>
                        <td style="width:11%;font-weight:bold;">Acceptance Ceritria Calibration</td>
                        <td style="width:90%;">Extract No should be displayed on mark available pletri plate</td>
                    </tr>
                </table>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CalibrationRecord.pdf', 'I');
    }else if ($_GET["type"] == "downloadCalibrationRecords") {
        $_GET['filename'] = 'Calibration Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Calibration Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Date</td>
                    <td style="width: 20%;">Instrument Id</td>
                    <td style="width: 20%;">Conculsion</td>
                    <td style="width: 20%;">Status</td>
                    <td style="width: 20%;">Entry By</td>
                </tr>
            </thead>';
        
      	$sql="SELECT * FROM colony_calibration WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$row['entry_date'].'.</td>
                        <td style="width: 20%;">'.$row['equipment_code'].'</td>
                        <td style="width: 20%;">'.$row['conclusion'].'</td>
                        <td style="width: 20%;">'.$row['status'].'</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Calibration Log.pdf', 'I');
    }
    
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>