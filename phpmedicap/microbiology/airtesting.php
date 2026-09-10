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
    
    if ($_GET["type"] == "saveAirtesting") {
        $sql = "INSERT INTO airtesting (type,point_no,media_lot_no ,saline_lot_no,entry_by,entry_date) VALUES ('".$input["type"]."','".$input["point_no"]."' ,'".$input["media_lot_no"]."', '".$input["saline_lot_no"]."','".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getAirtestingLog") {
        $output = array();
        $sql="SELECT * FROM airtesting WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadAirtestingLog") {
        $_GET['filename'] = 'AirtestingLog'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">AirtestingLog</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 10%;">Sr.</td>
                        <td style="width: 15%;">Date</td>
                        <td style="width: 15%;">Product Name</td>
                        <td style="width: 15%;">Sampling Point No</td>
                        <td style="width: 15%;">SCDA Media Lot No</td>
                        <td style="width: 15%;">0.9% Normal Saline Lot No</td>
                        <td style="width: 15%;">Entry By</td>
                    </tr>
                </thead>';
        $i=1;
      	$sql="SELECT * FROM airtesting WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 15%;">'.$row['type'].'</td>
                        <td style="width: 15%;">'.$row['point_no'].'</td>
                        <td style="width: 15%;">'.$row['media_lot_no'].'</td>
                        <td style="width: 15%;">'.$row['saline_lot_no'].'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('AirtestingLog.pdf', 'I');
    }

    
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>