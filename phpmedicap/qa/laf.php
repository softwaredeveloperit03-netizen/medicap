<?php 
// ini_set('display_errors', 1);
//  error_reporting(E_ALL);
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
        $sql = "SELECT p.*, s.section_name FROM pressure_master p LEFT JOIN section s ON p.section_code=s.section_code WHERE department LIKE '%".$_GET["department"]."%'";
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
        $sql = "SELECT p.*, s.section_name, DATE(p.entry_date) as entry_date, TIME(p.entry_date) as entry_time FROM pressure p LEFT JOIN section s ON p.section=s.section_code WHERE p.user_no='".$_GET["user_no"]."' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

else if($_GET['type'] == 'downloadActivities'){
        $_GET['filename'] = 'Laminar Air Flow'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
    
            <table cellpadding="2">
                <tr style="font-weight:bold;">
                <td style="width:25%;">FORMAT NO </td>
                <td style="width:25%;">VERSION NO</td>
                <td style="width:25%;">EFFECTIVE DATE</td>
                <td style="width:25%;">NEXT REVIEW DATE</td>
            </tr>
            <table><div></div>
            <table cellpadding="2">
                <tr style="font-weight:bold;">
                <td style="width:7%;">Sr.No.</td>
                <td style="width:13%;">Date</td>
                <td style="width:15%;">Start time </td>
                <td style="width:15%;">End time </td>
                <td style="width:15%;">Done By</td>
                <td style="width:20%;">Purpose/Remark</td>
                <td style="width:15%;">Verified By</td>
            </tr>
        ';

          $sql = "SELECT * FROM laf WHERE equipment_code='M-02' AND entry_date=CURDATE()";
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $temp = array();
            $temp["equipment_code"] = "M-02";
            $temp["entry_date"] = $entry_date;
            $temp["status"] = "PENDING";
            $output[] = $temp;
        
        
        $sql = "SELECT * FROM laf WHERE equipment_code='M-02' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            

                $html.='
                <tr>
                    <td>'.$row[''].'</td>
                    <td>'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td>'.$row[''].'</td>
                    <td>'.$row[''].'</td>
                    <td>'.$row[''].'</td>
                    <td>'.$row[''].'</td>
                    <td>'.$row[''].'</td>
                </tr>';
            }
        }
        }
        $html.='</table>
        <div></div>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
}

} 

$conn->close();
?>