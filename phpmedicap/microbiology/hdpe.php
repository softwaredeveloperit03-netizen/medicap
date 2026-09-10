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
    
    if ($_GET["type"] == "saveRecord") {
        $sql = "INSERT INTO hdpe_cleaning (rinse, cleaning_time, cleaning_by, remark, entry_by, entry_date, entry_time) VALUES ('".$input["rinse"]."', '".$input["cleaning_time"]."', '".$input["cleaning_by"]."', '".$input["remark"]."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRecords") {
        $output = array();
        $sql = "SELECT * FROM hdpe_cleaning"; 
        //WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPassboxes") {
        $output = array();
        $sql = "SELECT * FROM equipment WHERE department='Microbiology' AND equipment_name LIKE 'Dynamic Pass Box%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadRecords") {
        $_GET['filename'] = 'PersonnelHygine'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Personnel Hygine</h3>
                <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 5%;">Sr.</td>
                        <td rowspan="2" style="width: 15%;">Date</td>
                        <td rowspan="2" style="width: 15%;">Time OF Cleaning</td>
                        <td rowspan="2" style="width: 15%;">Rinse with Purified water for 5 times</td>
                        <td style="width: 35%; text-align:center;">Cleaning</td>
                        <td rowspan="2" style="width: 15%;">Remark</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Done By</td>
                        <td style="width:20%;">Checked By</td>
                    </tr>
                </thead>';
                $i=1;
        $sql = "SELECT * FROM hdpe_cleaning WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'.</td>
                    <td style="width: 15%;">'.date("d/m/Y",strtotime($row['entry_date'])).'</td>
                    <td style="width: 15%;">'.$row['cleaning_time'].'</td>
                    <td style="width: 15%;">'.$row['rinse'].'</td>
                    <td style="width: 15%;">'.$row['cleaning_by'].'</td>
                    <td style="width: 20%;">'.$row['entry_by'].'</td>
                    <td style="width: 15%;">'.$row['remark'].'</td>
                </tr>';
            $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadRecords.pdf', 'I');
    
}else if ($_GET["type"] == "downloadMoniteringRecords") {
        $_GET['filename'] = 'PersonnelHygine'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Personnel Hygine</h3>
                <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 5%;">Sr.</td>
                        <td rowspan="2" style="width: 15%;">Date</td>
                        <td rowspan="2" style="width: 15%;">Time OF Cleaning</td>
                        <td rowspan="2" style="width: 15%;">Rinse with Purified water for 5 times</td>
                        <td style="width: 35%; text-align:center;">Cleaning</td>
                        <td rowspan="2" style="width: 15%;">Remark</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Done By</td>
                        <td style="width:20%;">Checked By</td>
                    </tr>
                </thead>';
                $i=1;
        $sql = "SELECT * FROM hdpe_cleaning WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'.</td>
                    <td style="width: 15%;">'.date("d/m/Y",strtotime($row['entry_date'])).'</td>
                    <td style="width: 15%;">'.$row['cleaning_time'].'</td>
                    <td style="width: 15%;">'.$row['rinse'].'</td>
                    <td style="width: 15%;">'.$row['cleaning_by'].'</td>
                    <td style="width: 20%;">'.$row['entry_by'].'</td>
                    <td style="width: 15%;">'.$row['remark'].'</td>
                </tr>';
            $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadRecords.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
