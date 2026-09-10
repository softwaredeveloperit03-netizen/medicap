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
    
    if ($_GET["type"] == "saveFogging") {
        $sql = "INSERT INTO fogging (fumigation_agent,batch_no,	conentration,volume_fumigent,volime_water,final_volume,area,laf_room,priamry_room,airlock1,airlock2,in_room,de_room,st_room,media_room,corridor,secondary_room,airlock,due_date,done_by,ahu5_of,ahu5_on,ahu6_of,ahu6_on,fumigation_time,remark,entry_by,entry_date) VALUES ('".$input["fumigation_agent"]."', '".$input["batch_no"]."', '".$input["conentration"]."', '".$input["volume_fumigent"]."', '".$input["volime_water"]."','".$input["final_volume"]."','".$input["area"]."','".$input["laf_room"]."', '".$input["priamry_room"]."','".$input["airlock1"]."','".$input["airlock2"]."','".$input["in_room"]."','".$input["de_room"]."','".$input["st_room"]."','".$input["media_room"]."','".$input["corridor"]."','".$input["secondary_room"]."','".$input["airlock"]."','".$input["due_date"]."','".$input["done_by"]."','".$input["ahu5_of"]."','".$input["ahu5_on"]."','".$input["ahu6_of"]."','".$input["ahu6_on"]."','".$input["fumigation_time"]."','".$input["remark"]."','".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getFogging"){
        $output=Array();
        $sql="SELECT *FROM fogging  WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
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
         $sql="SELECT * FROM  lab_cleaning WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 9%;">'.$row['entry_date'].'.</td>
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
    }else if ($_GET["type"] == "downloadLogFogging") {
        $_GET['filename'] = 'LogFogging'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">LogFogging</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Id</td>
                        <td style="width:45%;">Date</td>
                        <td style="width:45%;">Name of Fumigation agent</td>
                    </tr>
                </thead>';
        $i=1;
        $sql="SELECT * FROM fogging  WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 45%;">'.$row['entry_date'].'</td>
                        <td style="width: 45%;">'.$row['fumigation_agent'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('LogFogging.pdf', 'I');
    }else if ($_GET["type"] == "downloadFogging") {
        $_GET['filename'] = ""; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $sql="SELECT *FROM fogging  WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<h3 style="text-align:center;">FOGGING RECORD OF MICROBIOLOGY LABORATORY</h3>
                <table border="1" cellpadding="3">
                    <tr>
                        <td style="width:40%;text-align:right; font-weight:bold;">Date </td>
                        <td style="width:60%;">'.$row['entry_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Name of Fumigation agent</td>
                        <td style="width:20%;">'.$row['fumigation_agent'].'</td>
                        <td style="width:40%;text-align:center;font-weight:bold;">AHU Operation</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Batch no of Fumigation agent</td>
                        <td style="width:20%;">'.$row['batch_no'].'</td>
                        <td style="width:10%;font-weight:bold;">AHU No.</td>
                        <td style="width:15%;font-weight:bold;">Off Time/Date</td>
                        <td style="width:15%;font-weight:bold;">ON Time/Date</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Concentration of Fumigant</td>
                        <td style="width:20%;">'.$row['conentration'].'</td>
                        <td style="width:10%;font-weight:bold;">AHU-05</td>
                        <td style="width:15%;">'.$row['ahu5_of'].'</td>
                        <td style="width:15%;">'.$row['ahu5_on'].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Volume of Fumigant</td>
                        <td style="width:20%;">'.$row['volume_fumigent'].'</td>
                        <td style="width:10%;font-weight:bold;text-align:center;">AHU-06</td>
                        <td style="width:15%;">'.$row['ahu6_of'].'</td>
                        <td style="width:15%;">'.$row['ahu6_on'].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Volume of Purified water</td>
                        <td style="width:20%;">'.$row['volime_water'].'</td>
                        <td style="width:40%;font-weight:bold;">Fumigation time:'.$row['fumigation_time'].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Final Volume</td>
                        <td style="width:20%;">'.$row['final_volume'].'</td>
                        <td rowspan="14" style="width:40%;"><b>Remarks:</b></td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="width:40%;text-align:right;font-weight:bold;">Area Name</td>
                        <td style="width:20%;font-weight:bold;">Time of Fumigation in Minutes</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">Standard</td>
                        <td style="width:10%;font-weight:bold;">Actual</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">LAF Room</td>
                        <td style="width:10%;">'.$row['laf_room'].'</td>
                        <td style="width:10%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Secondary Change Room</td>
                        <td style="width:10%;">'.$row['secondary_room'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Airlock-02</td>
                        <td style="width:10%;">'.$row['airlock2'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Airlock-01</td>
                        <td style="width:10%;">'.$row['airlock1'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Incubator Room</td>
                        <td style="width:10%;">'.$row['in_room'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Decontamination Room</td>
                        <td style="width:10%;">'.$row['de_room'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Sterilization Room</td>
                        <td style="width:10%;">'.$row['st_room'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Media Preparation Room</td>
                        <td style="width:10%;">'.$row['media_room'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Corridor</td>
                        <td style="width:10%;">'.$row['corridor'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Primary Change Room</td>
                        <td style="width:10%;">'.$row['priamry_room'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:40%;text-align:right;font-weight:bold;">Airlock</td>
                        <td style="width:10%;">'.$row['airlock'].'</td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:60%;text-align:right;font-weight:bold;">Next Fumigation Due Date</td>
                        <td style="width:40%;">'.$row['due_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;text-align:right;font-weight:bold;">Fumigation done by </td>
                        <td style="width:40%;">'.$row['done_by'].'</td>
                    </tr>
                    <tr>
                        <td style="width:60%;text-align:right;font-weight:bold;">Checked by </td>
                        <td style="width:40%;">'.$row['entry_by'].'</td>
                    </tr>
                </table>
                ';
            }
        }
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Fogging.pdf', 'I');
    }
    
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>