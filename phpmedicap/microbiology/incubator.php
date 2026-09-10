<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

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
    
    if ($_GET["type"] == "saveIncubator") {
        $sql = "INSERT INTO bod_cleaning (equipment_code,disinfectant,clean_by ,entry_time, remark,entry_by,entry_date) VALUES('".$input["equipment_code"]."','".$input["disinfectant"]."','".$input["clean_by"]."', '$entry_time', '".$input["remark"]."' ,'".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getPendingIncubator"){
        $output=Array();
         $sql="SELECT * FROM bod_cleaning ";
        //$sql="SELECT i.*, e.firstname FROM bod_cleaning i LEFT JOIN employee e ON i.clean_by=e.emp_id WHERE DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    } else if ($_GET["type"] == "saveIncubatorUsage") {
        $sql = "INSERT INTO incubator_usage(equipment_code, activity,batch_no ,time_on, date_on,remark,entry_by,entry_date) VALUES('".$input["equipment_code"]."','".$input["activity"]."','".$input["batch_no"]."','".$input["time_on"]."','".$input["date_on"]."','".$input["remark"]."' ,'".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "completeIncubatorUsage") {
        $sql = "UPDATE incubator_usage SET complete_by='".$_GET["emp_id"]."', complete_date='$entry_date', complete_time='$entry_time', status='COMPLETE' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getIncubatorUsage"){
        $output=Array();
        $sql="SELECT * FROM incubator_usage  ";
        //$sql="SELECT * FROM incubator_usage WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    } else if ($_GET["type"] == "saveIncubatorBodUsage") {
        $sql = "INSERT INTO incubator_bod_usage (time_complete,date_complete,done_complete,remark1,equipment_code,activity,batch_no ,time_on, date_on,remark,entry_by,entry_date) 
        VALUES( '".$input["time_complete"]."','".$input["date_complete"]."','".$input["done_complete"]."','".$input["remark1"]."','".$input["equipment_code"]."','".$input["activity"]."','".$input["batch_no"]."','".$input["time_on"]."','".$input["date_on"]."','".$input["remark"]."' ,'".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "completeBodUsage") {
        $sql = "UPDATE incubator_bod_usage SET complete_by='".$_GET["emp_id"]."', complete_date='$entry_date', complete_time='$entry_time', status='COMPLETE' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getIncubatorBodUsage"){
        $output=Array();
       $sql="SELECT * FROM incubator_bod_usage  ";
        //$sql="SELECT * FROM incubator_bod_usage WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if ($_GET["type"] == "downloadIncubatorUsage") {
        $_GET['filename'] = 'IncubatorUsage'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">IncubatorUsage</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 10%;">Date</td>
                        <td rowspan="2" style="width: 8%;">Activity</td>
                        <td rowspan="2" style="width: 10%;">Batch No</td>
                        <td style="width: 21%;">Incubation on</td>
                        <td rowspan="2" style="width: 10%;">Done By</td>
                        <td style="width: 21%;">Incubation completed on</td>
                        <td rowspan="2" style="width: 10%;">Done By</td>
                        <td rowspan="2" style="width: 10%;">Remarks</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Time</td>
                        <td style="width:11%;">Date</td>
                        <td style="width:10%;">Time</td>
                        <td style="width:11%;">Date</td>
                    </tr>
                </thead>';
                $sql="SELECT * FROM incubator_usage  ";
        //$sql="SELECT * FROM incubator_usage WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row['entry_date'].'</td>
                        <td style="width: 8%;">'.$row['activity'].'</td>
                        <td style="width: 10%;">'.$row['batch_no'].'</td>
                        <td style="width: 10%;">'.$row['time_on'].'</td>
                        <td style="width: 11%;">'.$row['date_on'].'</td>
                        <td style="width: 10%;">'.$row['done_on'].'</td>
                        <td style="width: 10%;">'.$row['time_complete'].'</td>
                        <td style="width: 11%;">'.$row['date_complete'].'</td>
                        <td style="width: 10%;">'.$row['done_complete'].'</td>
                        <td style="width: 10%;">'.$row['remark'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IncubatorUsage.pdf', 'I');
    }else if ($_GET["type"] == "downloadPendingIncubator") {
        $_GET['filename'] = 'PendingIncubator'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">PendingIncubator</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 17%;">Date</td>
                        <td style="width: 17%;">Time</td>
                        <td style="width: 17%;">Disinfectant use for cleaning</td>
                        <td style="width: 17%;">Cleaned By</td>
                        <td style="width: 17%;">Checked By</td>
                        <td style="width: 15%;">Remark</td>
                    </tr>
                </thead>';
                 $sql="SELECT * FROM bod_cleaning ";
        //$sql="SELECT * FROM bod_cleaning WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 17%;">'.$row['entry_date'].'.</td>
                    <td style="width: 17%;">'.$row['entry_time'].'</td>
                    <td style="width: 17%;">'.$row['disinfectant'].'</td>
                    <td style="width: 17%;">'.$row['clean_by'].'</td>
                    <td style="width: 17%;">'.$row['entry_by'].'</td>
                    <td style="width: 15%;">'.$row['remark'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PendingIncubator.pdf', 'I');
    }else if ($_GET["type"] == "downloadIncubatorBodUsage") {
        $_GET['filename'] = 'IncubatorBodUsage'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">IncubatorBodUsage</h2>
            <table border="1" cellpadding="2">
                <tr style="font-weight:bold;">
                <td style="width:25%;">FORMAT NO </td>
                <td style="width:25%;">VERSION NO</td>
                <td style="width:25%;">EFFECTIVE DATE</td>
                <td style="width:25%;">NEXT REVIEW DATE</td>
            </tr>
            <table><div></div>
            <table border="1" cellpadding="2">
                <tr style="font-weight:bold;">
                <td style="width:15%;">Date</td>
                <td style="width:15%;">Incubation Start </td>
                <td style="width:15%;">Incubation End </td>
                <td style="width:15%;">Done By</td>
                <td style="width:25%;">Remark</td>
                <td style="width:15%;">Verified By</td>
            </tr>';
                $sql="SELECT * FROM incubator_bod_usage  ";
        //$sql="SELECT * FROM incubator_bod_usage WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 11%;">'.$row['entry_date'].'.</td>
                    <td style="width: 10%;">'.$row['activity'].'</td>
                    <td style="width: 9%;">'.$row['batch_no'].'</td>
                    <td style="width: 9%;">'.$row['time_on'].'</td>
                    <td style="width: 11%;">'.$row['date_on'].'</td>
                    <td style="width: 10%;">'.$row['done_on'].'</td>
                    <td style="width: 9%;">'.$row['time_complete'].'</td>
                    <td style="width: 11%;">'.$row['date_complete'].'</td>
                    <td style="width: 10%;">'.$row['done_complete'].'</td>
                    <td style="width: 10%;">'.$row['remark'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IncubatorBodUsage.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>