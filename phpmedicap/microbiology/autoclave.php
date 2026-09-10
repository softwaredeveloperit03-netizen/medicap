<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];


// error_reporting(E_ALL);
// ini_set('display_errors', 1);



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
    
    if ($_GET["type"] == "saveAutoclave") {
        $sql = "INSERT INTO autoclave ( plant_id,cycle_no,material,cycle_start ,achieved_time , steam_pressure,cycle_end,
        hold_time,indicator,remark,done_by,entry_by,entry_date) VALUES
        ( '".$_GET["plant_id"]."' ,'".$input["cycle_no"]."', '".$input["media_name"]."', '".$input["cycle_start"]."', '".$input["achieved_time"]."' ,
        '".$input["steam_pressure"]."', '".$input["cycle_end"]."', '".$input["hold_time"]."', '".$input["indicator"]."',
        '".$input["remark"]."' , '".$input["done_by"]."',  '".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getPendingAutoclave"){
        $output=Array();
        $sql="SELECT * FROM autoclave";// WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' 
        //AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if ($_GET["type"] == "downloadPendingAutoclave") {
        $_GET['filename'] = 'PendingAutoclave'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">PendingAutoclave</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 10%;">Date</td>
                        <td style="width: 10%;">Autocave cycle No</td>
                        <td style="width: 8%;">Material</td>
                        <td style="width: 8%;">Cycle Started at</td>
                        <td style="width: 8%;">121c Achieved Time</td>
                        <td style="width: 8%;">Chamber Steam Pressure(1.5lbs)</td>
                        <td style="width: 8%;">Cycle End Time</td>
                        <td style="width: 8%;">Hold Time</td>
                        <td style="width: 8%;">Chemical Indicator</td>
                        <td style="width: 8%;">Done By</td>
                        <td style="width: 8%;">Checked_by</td>
                        <td style="width: 8%;">Remarks</td>
                    </tr>
                </thead>';
        $sql="SELECT * FROM autoclave WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row['entry_date'].'.</td>
                        <td style="width: 10%;">'.$row['cycle_no'].'</td>
                        <td style="width: 8%;">'.$row['material'].'</td>
                        <td style="width: 8%;">'.$row['cycle_start'].'</td>
                        <td style="width: 8%;">'.$row['achieved_time'].'</td>
                        <td style="width: 8%;">'.$row['steam_pressure'].'</td>
                        <td style="width: 8%;">'.$row['cycle_end'].'</td>
                        <td style="width: 8%;">'.$row['hold_time'].'</td>
                        <td style="width: 8%;">'.$row['indicator'].'</td>
                        <td style="width: 8%;">'.$row['done_by'].'</td>
                        <td style="width: 8%;">'.$row['entry_by'].'</td>
                        <td style="width: 8%;">'.$row['remark'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PendingAutoclave.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>