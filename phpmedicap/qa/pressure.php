<?php 

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getPendingSections") {
        $output = array();
        $sql = "SELECT * FROM section WHERE section_code NOT IN (SELECT section_code FROM pressure_master WHERE status IN ('pending', 'approve'))";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePressure") {
        $sql = "INSERT INTO pressure_master (user_no, section_code, department, lower_limit, upper_limit, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["section_code"]."', '".$input["department"]."', '".$input["lower_limit"]."', '".$input["upper_limit"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingPressures") {
        $output = array();
        $sql = "SELECT p.*, s.section_name FROM pressure_master p LEFT JOIN section s ON p.section_code=s.section_code WHERE p.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["range"] = $row["lower_limit"] ." - ". $row["upper_limit"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updatePressure") {
        $sql = "UPDATE pressure_master SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPressureLog") {
        $output = array();
        // $sql = "SELECT p.*, s.section_name FROM pressure_master p LEFT JOIN section s ON p.section_code=s.section_code WHERE department LIKE '%".$_GET["department"]."%'";
        
    $sql = "SELECT r.*, s.section_name FROM pressure_master r LEFT JOIN section s ON r.section_code = s.section_code WHERE r.status = 'approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["range"] = $row["lower_limit"] ." - ". $row["upper_limit"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDailyPressure") {
        $sql = "INSERT INTO pressure (user_no, department, section, pressure, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["department"]."', '".$input["section"]."', '".$input["pressure"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDailyPressures") {
        $output = array();
        $sql = "SELECT p.*, s.section_name, DATE(p.entry_date) as entry_date, TIME(p.entry_date) as entry_time FROM pressure p 
        LEFT JOIN section s ON p.section=s.section_code WHERE p.user_no='".$_GET["user_no"]."' 
       ";
        // AND DATE(p.entry_date) BETWEEN ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET['type'] == 'downloadDailyPressures'){
        $_GET['filename'] = 'Questions'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='<h2 style="text-align:center;color:brown">Pressure Log</h2>
        <table cellpadding="5" border="0.1">
        <tr style="text-align:center;background-color:#DDDAD9">
        <td style="width:5%;"><b>Sr No.</b></td>
        <td style="width:15%;"><b>Date</b></td>
        <td style="width:15%;"><b>Time</b></td>
        <td style="width:20%;"><b>Department	</b></td>
        <td style="width:15%;"><b>Section</b></td>
        <td style="width:15%;"><b>Pressure</b></td>
        <td style="width:15%;"><b>Entry By</b></td>
        </tr>';
        $i=1;
      $sql = "SELECT p.*, s.section_name, DATE(p.entry_date) as entry_date, TIME(p.entry_date) 
      as entry_time FROM pressure p 
        LEFT JOIN section s ON p.section=s.section_code WHERE p.user_no='".$_GET["user_no"]."' 
        AND DATE(p.entry_date) BETWEEN";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
        <td style="width:5%;">'.$i.'</td>
        <td style="width:15%;">'.$row['entry_date'].'</td>
        <td style="width:15%;">'.$row['entry_time'].'</td>
        <td style="width:20%;">'.$row['department'].'</td>
        <td style="width:15%;">'.$row['section'].'</td>
        <td style="width:15%;">'.$row['pressure'].'</td>
        <td style="width:15%;">'.$row['entry_by'].'</td>
        </tr>';
        $i++;
 
            }
        }
        $html.=' </table>';
  $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('dailypressure.pdf', 'I');
    

}else if($_GET['type'] == 'questionaries'){
        $_GET['filename'] = 'Questions'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DCDCDC;">
                <td style="text-align:center;">Training Questionaries Log</td>
            </tr>
        </table>
        <div></div>
        <table cellpadding="5">
            <tr style="font-weight:bold;">
                <td style="width:20%;">Department</td>
                <td style="width:20%;">Subject</td>
                <td style="width:20%;">Trainer Name</td>
                <td style="width:20%;">Date</td>
                <td style="width:10%;">Time</td>
                <td style="width:10%;">Venue</td>
            </tr>
        ';

        $sql = "SELECT * FROM training_needs WHERE questionaries='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["trainer_name"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                $row["questions"] = json_decode($row["questions"]);
                $html.='
                <tr>
                    <td>'.$row['department'].'</td>
                    <td>'.$row['subject'].'</td>
                    <td>'.$row["trainer_name"].'</td>
                    <td>'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                    <td>'.$row['training_time'].'</td>
                    <td>'.$row['venue'].'</td>
                </tr>';
            }
        }
        $html.='</table>
        <div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
}
}

$conn->close();
?>