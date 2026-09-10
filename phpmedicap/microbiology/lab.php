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
    
    if ($_GET["type"] == "saveLab") {
        $sql = "INSERT INTO lab_cleaning (laf_id, pass_box1,pass_box2 ,ph_meter ,balance_id ,colony_counter ,autoclave,microscope,floor,work_benches,door_window ,light,clean_by ,entry_by,entry_date) VALUES ('".$input["laf_id"]."', '".$input["pass_box1"]."', '".$input["pass_box2"]."', '".$input["ph_meter"]."', '".$input["balance_id"]."', '".$input["colony_counter"]."', '".$input["autoclave"]."', '".$input["microscope"]."', '".$input["floor"]."', '".$input["work_benches"]."','".$input["door_window"]."','".$input["light"]."', '".$input["clean_by"]."', '".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getPendingLab"){
        $output=Array();
        $sql="SELECT l.*, e.firstname FROM lab_cleaning l LEFT JOIN employee e ON l.clean_by=e.emp_id WHERE DATE(l.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if ($_GET["type"] == "downloadPendingLab") {
        $_GET['filename'] = 'PendingLab'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">PendingLab</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 9%;">Date</td>
                        <td style="width: 50%; text-align:center;">Instrument</td>
                        <td rowspan="2" style="width: 6%;">Floor</td>
                        <td rowspan="2" style="width: 6%;">Work benches & Platform</td>
                        <td rowspan="2" style="width: 10%;">Walls,Cellings,Doors and Window</td>
                        <td rowspan="2" style="width: 6%;">Light Fixture</td>
                        <td rowspan="2" style="width: 6%;">Clean By</td>
                        <td rowspan="2" style="width: 7%;">Checked By</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 6%;">LAF</td>
                        <td style="width: 6%;">Pass Bax1</td>
                        <td style="width: 6%;">Pass Bax2</td>
                        <td style="width: 6%;">pH Meter</td>
                        <td style="width: 6%;">Balance</td>
                        <td style="width: 6%;">Colony Counter</td>
                        <td style="width: 8%;">Autoclave</td>
                        <td style="width: 6%;">Microscope</td>
                    </tr>
                </thead>';
         $sql="SELECT * FROM  lab_cleaning ";
         //WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 9%;">'.date("d/m/Y",($row['entry_date'])).'</td>
                        <td style="width: 6%;">'.$row['laf_id'].'</td>
                        <td style="width: 6%;">'.$row['pass_box1'].'</td>
                        <td style="width: 6%;">'.$row['pass_box2'].'</td>
                        <td style="width: 6%;">'.$row['ph_meter'].'</td>
                        <td style="width: 6%;">'.$row['balance_id'].'</td>
                        <td style="width: 6%;">'.$row['colony_counter'].'</td>
                        <td style="width: 8%;">'.$row['autoclave'].'</td>
                        <td style="width: 6%;">'.$row['microscope'].'</td>
                        <td style="width: 6%;">'.$row['floor'].'</td>
                        <td style="width: 6%;">'.$row['work_benches'].'</td>
                        <td style="width: 10%;">'.$row['door_window'].'</td>
                        <td style="width: 6%;">'.$row['light'].'</td>
                        <td style="width: 6%;">'.$row['clean_by'].'</td>
                        <td style="width: 7%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PendingLab.pdf', 'I');
    }
    
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>