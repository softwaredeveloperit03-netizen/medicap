<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

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

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

if ($_GET["type"] == "saveElectricity") {
    $sql = "INSERT INTO electricity (meter_reading, last_reading, difference, max_demand, power_factor, entry_by, entry_date) VALUES ('".$input["meter_reading"]."', '".$input["last_reading"]."', '".$input["difference"]."', '".$input["max_demand"]."', '".$input["power_factor"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getElectricityLog") {
    $output = array();
    $sql = "SELECT * FROM electricity WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET['type'] == 'downloadElectricityLog'){
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indend Of General Material'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html.= "";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 15%;">Date	</td>
                    <td style="width: 15%;">Meter Reading		</td>
                    <td style="width: 15%;">Last Reading		</td>
                    <td style="width: 15%;"> Total Difference		</td>
                    <td style="width: 15%;">Maximum Demand	</td>
                    <td style="width: 15%;">Power Factor</td>
                    
                </tr>
            </thead>';
            $sql = "SELECT * FROM electricity WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html.='
            <tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td> 
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        
                    </tr>';
                     $i++;
            }
        }
            $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Indend Of General Material.pdf', 'I');
}

}

$conn->close();
?>