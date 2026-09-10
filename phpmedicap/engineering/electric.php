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
        $output = Array();
        $sql ="INSERT INTO electric_operation(start_time ,stop_time,meter01 ,meter02 ,electrician,remark,entry_by,entry_date)VALUES('".$input["start_time"]."' ,'".$input["stop_time"]."' ,'".$input["meter01"]."' ,'".$input["meter02"]."','".$input["electrician"]."','".$input["remark"]."' ,'".$_GET["emp_id"]."' ,'$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getOperation"){
        $sql="SELECT * FROM electric_operation";
          $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadOperation") {
        $_GET['filename'] = 'Filter Replacement Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td  rowspan="2" style="width: 10%;">Date</td>
                        <td style="width: 30%;">D.G. Set Operation</td>
                        <td style="width: 30%;">Energy Meter Reading	</td>
                        <td  rowspan="2" style="width: 20%;">Name of Electrician</td>
                        <td  rowspan="2" style="width: 10%;">Remarks</td>

                    </tr>
                     <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Start Time</td>
                        <td style="width:15%;">Stop Time</td>
                        <td style="width:15%;">DG Set No. 01 500 KVA</td>
                        <td style="width:15%;">DG Set No. 02 910 KVA</td>
                    </tr>
                    
                </thead>';
            $sql="SELECT * FROM electric_operation";
          $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                      
                        <td style="width: 10%;">'.$row['entry_date'].'</td>
                        <td style="width: 15%;">'.$row['start_time'].'</td>
                         <td style="width: 15%;">'.$row['stop_time'].'</td>
                        <td style="width: 15%;">'.$row['meter01'].'</td>
                        <td style="width: 15%;">'.$row['meter02'].'</td>
                        <td style="width: 20%;">'.$row['electrician'].'</td>
                        <td style="width: 10%;">'.$row['remark'].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Filter Replacement Record.pdf', 'I');
    }
    
else if ($_GET["type"] == "downloadElectricityLog") {
        $_GET['filename'] = 'Filter Replacement Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td  rowspan="2" style="width: 10%;">Date</td>
                        <td style="width: 30%;">D.G. Set Operation</td>
                        <td style="width: 30%;">Energy Meter Reading	</td>
                        <td  rowspan="2" style="width: 20%;">Name of Electrician</td>
                        <td  rowspan="2" style="width: 10%;">Remarks</td>

                    </tr>
                     <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Start Time	</td>
                        <td style="width:15%;">Stop Time	</td>
                        <td style="width:15%;">DG Set No. 01 500 KVA		</td>
                        <td style="width:15%;">DG Set No. 02 910 KVA	</td>
                    </tr>
                    
                </thead>';
            $sql="SELECT * FROM aircompressor_filterreplce WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 10%;">'.$row['filter_id'].'</td>
                        <td style="width: 25%;">'.$row['next_date'].'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                        <td style="width: 15%;">'.$row['check_by'].'</td>
                        <td style="width: 15%;">'.$row['remark'].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Filter Replacement Record.pdf', 'I');
}
}

$conn->close();
?>