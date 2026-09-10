<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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

    if($_GET["type"] == "saveOperation"){
        $sql="INSERT INTO luxmeter_monitoring (plant_name,luxmeter_id ,location,l1,l2 ,l3 ,l4,l5,average, entry_by ,entry_date)VALUES('".$input["plant_name"]."' , '".$input["luxmeter_id"]."' , '".$input["location"]."','".$input["l1"]."' ,'".$input["l2"]."' ,'".$input["l3"]."' ,'".$input["l4"]."' , '".$input["l5"]."' ,'".$input["average"]."' ,'".$_GET["emp_id"]."' , '$entry_date')";
         if($conn->query($sql)){
           echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
        }
        
    }else if($_GET["type"] == "getOperation"){
        $output=Array();
        $sql="SELECT * FROM luxmeter_monitoring WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadOperation") {
        $_GET['filename'] = 'Lux level Monitoring Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 10%;">Sr.</td>
                   
                    <td rowspan="2" style="width: 10%;">Lux mater ID No</td>
                    <td rowspan="2" style="width: 10%;">Location Name</td>
                    <td style="width: 35%;">Observed Reading From Location2</td>
                    <td rowspan="2" style="width: 15%;">Average Reading (Lux)</td>
                    <td rowspan="2" style="width: 10%;">Checked By</td>
                    <td rowspan="2" style="width: 10%;">Verified By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:7%;">L1</td>
                    <td style="width:7%;">L2</td>
                    <td style="width:7%;">L3</td>
                    <td style="width:7%;">L4</td>
                    <td style="width:7%;">L5</td>
                </tr>
            </thead>';
            $i=1;
        $sql="SELECT * FROM luxmeter_monitoring WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$i.'.</td>
                   
                    <td style="width: 10%;">'.$row['luxmeter_id'].'</td>
                    <td style="width: 10%;">'.$row['location'].'</td>
                    <td style="width: 7%;">'.$row['l1'].'</td>
                    <td style="width: 7%;">'.$row['l2'].'</td>
                    <td style="width: 7%;">'.$row['l3'].'</td>
                    <td style="width: 7%;">'.$row['l4'].'</td>
                    <td style="width: 7%;">'.$row['l5'].'</td>
                    <td style="width: 15%;">'.$row['average'].'</td>
                    <td style="width: 10%;">'.$row['check_by'].'</td>
                    <td style="width: 10%;">'.$row['entry_by'].'</td>
                </tr>';
            $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Workorder.pdf', 'I');
    }

    
}

$conn->close();
?>