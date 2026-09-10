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
        $sql = "INSERT INTO uv_burning (equipment_code, pressure, burning_hr, cleaned_by, entry_by, entry_date, entry_time) VALUES ('".$input["equipment_code"]."', '".$input["pressure"]."', '".$input["burning_hr"]."', '".$input["cleaned_by"]."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRecords") {
        $output = array();
        $sql = "SELECT * FROM uv_burning";
        // WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPassboxes") {
        $output = array();
        $sql = "SELECT * FROM equipment WHERE department='Microbiology' AND equipment_name 
        LIKE 'Dynamic Pass Box%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadRecords") {
        $_GET['filename'] = 'Records'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Records</h2>
        <table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Date</td>
                        <td style="width:10%;">Time</td>
                        <td style="width:10%;">Diynamic pass box</td>
                        <td style="width:15%;">Differential Pressure of dynamic pass box</td>
                        <td style="width:15%;">Total Burning Hours of UV Light</td>
                        <td style="width:15%;">Cleaned By</td>
                        <td style="width:10%;">Recorded By</td>
                        <td style="width:10%;">Checked By</td>
                    </tr>';
        $sql = "SELECT * FROM uv_burning WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                        <td style="width:15%;">'.$row['entry_date'].'</td>
                        <td style="width:10%;">'.$row['entry_time'].'</td>
                        <td style="width:10%;">'.$row['equipment_code'].'</td>
                        <td style="width:15%;">'.$row['pressure'].'</td>
                        <td style="width:15%;">'.$row['burning_hr'].'</td>
                        <td style="width:15%;">'.$row['cleaned_by'].'</td>
                        <td style="width:10%;">'.$row['entry_by'].'</td>
                        <td style="width:10%;">'.$row['entry_by'].'</td>
                    </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Records.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
