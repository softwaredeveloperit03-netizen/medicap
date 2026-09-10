<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getCalibrationCalender") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE equipment_name='Balance'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["weights"] = json_decode($row["weights"]);
                
                $calibration_frequency = json_decode($row["calibration_frequency"], true);
                if (in_array('Monthly', $calibration_frequency)) {
                    $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE()) AND status !='reject'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows == 0) {
                        $output[] = $row;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingCalibrations") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE equipment_name='Weight Balance'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["weights"] = json_decode($row["weights"]);
                
                $sql1 = "SELECT * FROM calibration_monthly WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE()) AND YEAR(entry_date)=YEAR(CURDATE()) AND status !='reject'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveCalibration") {
        $sql = "INSERT INTO calibration_monthly (equipment_code,uncertinty, uncertinty_remark,drift, drift_remark, ranges, range_remark, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', '".json_encode($input["uncertinty"])."', '".$input["uncertinty_remark"]."', '".json_encode($input["drift"])."', '".$input["drift_remark"]."', '".json_encode($input["range"])."', '".$input["range_remark"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCalibrationLog") {
        $output = array();
        $sql = "SELECT c.*, e.equipment_name, e.department, e.location, e.capacity, e.unit FROM calibration_monthly c LEFT JOIN equipment e ON c.equipment_code=e.equipment_code WHERE c.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["uncertinty"] = json_decode($row["uncertinty"]);
                $row["drift"] = json_decode($row["drift"]);
                $row["ranges"] = json_decode($row["ranges"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadCalibrationLog") {
        $_GET['filename'] = 'CalibrationLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 15%;">Sr.</td>
                        <td style="width: 15%;">Entry By</td>
                        <td style="width: 20%;">Plant Name</td>
                        <td style="width: 20%;">Equipment Code</td>
                        <td style="width: 15%;">Capacity</td>
                        <td style="width: 15%;">Location</td>
                    </tr>
                </thead>';
        $i=1;
        $sql = "SELECT c.*, e.equipment_name, e.department, e.location, e.capacity, e.unit,e.plant_name FROM calibration_monthly c LEFT JOIN equipment e ON c.equipment_code=e.equipment_code WHERE c.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$i.'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.$row['plant_name'].'</td>
                        <td style="width: 20%;">'.$row['equipment_code'].'</td>
                        <td style="width: 15%;">'.$row['capacity'].'</td>
                        <td style="width: 15%;">'.$row['location'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CalibrationLog.pdf', 'I');
    }else if ($_GET["type"] == "downloadViewCalibration") {
        $_GET['filename'] = 'Calibration'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $sql = "SELECT c.*, e.equipment_name, e.department, e.location, e.capacity, e.unit ,e.plant_name FROM calibration_monthly c LEFT JOIN equipment e ON c.equipment_code=e.equipment_code WHERE c.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["uncertinty"] = json_decode($row["uncertinty"]);
                $uncertinty=$row["uncertinty"];
                $row["drift"] = json_decode($row["drift"]);
                $drift=$row['drift'];
                $row["ranges"] = json_decode($row["ranges"]);
                $ranges=$row["ranges"];
        $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;font-weight:bold;">Plant Name:</td>
                        <td style="width:25%;">'.$row['plant_name'].'</td>
                        <td style="width:25%;font-weight:bold;">Equipment Code</td>
                        <td style="width:25%;">'.$row['equipment_code'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Capacity</td>
                        <td style="width:25%;">'.$row['capacity'].'</td>
                        <td style="width:25%;font-weight:bold;">Location</td>
                        <td style="width:25%;">'.$row['location'].'</td>
                    </tr>
                </table>';
                
        $html.='<h3> Measurment of Repetability And Uncertainty:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td rowspan="2" style="width:15%;font-weight:bold;">Standard Weight</td>
                        <td rowspan="2" style="width:16%;font-weight:bold;">Acceptance Critaria</td>
                        <td style="width:50%; text-align:center;font-weight:bold;">Observed Weight</td>
                        <td rowspan="2" style="width:19%;font-weight:bold;">UNcertainty=(SDx2)/Std.Wt.(NMT 0.10%)</td>
                    </tr>
                    <tr>
                        <td style="width:5%;font-weight:bold;">1</td>
                        <td style="width:5%;font-weight:bold;">2</td>
                        <td style="width:5%;font-weight:bold;">3</td>
                        <td style="width:5%;font-weight:bold;">4</td>
                        <td style="width:5%;font-weight:bold;">5</td>
                        <td style="width:5%;font-weight:bold;">6</td>
                        <td style="width:5%;font-weight:bold;">7</td>
                        <td style="width:5%;font-weight:bold;">8</td>
                        <td style="width:5%;font-weight:bold;">9</td>
                        <td style="width:5%;font-weight:bold;">10</td>
                    </tr>';
               
            $html.='<tr>
                        <td style="width:15%;">'.$uncertinty->standard_weight.'</td>
                        <td style="width:16%;">'.$uncertinty->acceptance.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_1.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_2.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_3.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_4.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_5.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_6.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_7.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_8.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_9.'</td>
                        <td style="width:5%;">'.$uncertinty->obs_10.'</td>
                        <td style="width:19%;">'.$uncertinty->uncertainty_avg.'</td>
                    </tr>';
                
                
        $html.='</table>
                <h3>Drift Measurement</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td rowspan="2" style="width:25%;font-weight:bold;">Standard Weight</td>
                        <td rowspan="2" style="width:25%;font-weight:bold;">Acceptance Critaria</td>
                        <td style="width:50%; text-align:center;font-weight:bold;">Observed Weight</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">1</td>
                        <td style="width:10%;font-weight:bold;">2</td>
                        <td style="width:10%;font-weight:bold;">3</td>
                        <td style="width:10%;font-weight:bold;">4</td>
                        <td style="width:10%;font-weight:bold;">5</td>
                    </tr>';
                  
            $html.='<tr>
                        <td style="width:25%;">'.$drift->standard_weight.'</td>
                        <td style="width:25%;">'.$drift->acceptance.'</td>
                        <td style="width:10%;">'.$drift->obs_1.'</td>
                        <td style="width:10%;">'.$drift->obs_2.'</td>
                        <td style="width:10%;">'.$drift->obs_3.'</td>
                        <td style="width:10%;">'.$drift->obs_3.'</td>
                        <td style="width:10%;">'.$drift->obs_4.'</td>
                    </tr>';
        $html.='</table>';
        $html.='<h3>Whole Range calibration</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:20%;font-weight:bold;">Standard Weight</td>
                        <td style="width:20%;font-weight:bold;">Observed Weight</td>
                        <td style="width:20%;font-weight:bold;">Acceptance Critaria</td>
                        <td style="width:20%;font-weight:bold;">Status</td>
                        <td style="width:20%;font-weight:bold;">Remark</td>
                    </tr>';
                    // for($i=0;$i<count($ranges);$i++){
                    //     $range=$ranges[$i];
                    
            $html.='<tr>
                        <td style="width:20%;">'.$ranges->standard_weight0.'</td>
                        <td style="width:20%;">'.$ranges->observed_weight0.'</td>
                        <td style="width:20%;">'.$ranges->acceptance0.'</td>
                        <td style="width:20%;">'.$ranges->status0.'</td>
                        <td style="width:20%;">'.$ranges->remark0.'</td>
                    </tr>
                    <tr>
                        <td style="width:20%;">'.$ranges->standard_weight1.'</td>
                        <td style="width:20%;">'.$ranges->observed_weight1.'</td>
                        <td style="width:20%;">'.$ranges->acceptance1.'</td>
                        <td style="width:20%;">'.$ranges->status1.'</td>
                        <td style="width:20%;">'.$ranges->remark1.'</td>
                    </tr>
                    <tr>
                        <td style="width:20%;">'.$ranges->standard_weight2.'</td>
                        <td style="width:20%;">'.$ranges->observed_weight2.'</td>
                        <td style="width:20%;">'.$ranges->acceptance2.'</td>
                        <td style="width:20%;">'.$ranges->status2.'</td>
                        <td style="width:20%;">'.$ranges->remark2.'</td>
                    </tr>';
                    // }';
        $html.='</table>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Calibration.pdf', 'I');
    }
    
}

$conn->close();
?>