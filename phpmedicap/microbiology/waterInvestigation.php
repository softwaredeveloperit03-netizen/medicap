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
    
    if ($_GET["type"] == "saveWaterInvestigation") {
        $sql = "INSERT INTO water_investigation (emreport_no, intimation_date, sampling_point ,batch_no ,description,given_by ,issue_by,status,close_date,remark) VALUES ('".$input["emreport_no"]."','".$entry_date."', '".$input["sampling_point"]."' ,'".$input["batch_no"]."', '".$input["description"]."' ,  '".$_GET["emp_id"]."' ,'' ,'".$input["status"]."' ,'".$input["close_date"]."' ,'".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getWaterInvestigation"){
        $output=Array();
        $sql="SELECT * FROM water_investigation WHERE DATE(intimation_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    } else if($_GET['type'] == 'downloadWaterInvestigation'){
        $_GET['formatno'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
 
        $html.='<h3 style="text-align:center;">WATER INVESTIGATION LOG</h3>
        <table cellpadding="3" border="1">
            <thead>
                <tr style="font-weight:bold; border: solid 1px black">
                    <td style="width:5%" rowspan="2"><b>Sr. No.</b></td>
                    <td style="width:10%" rowspan="2"><b>Initiation Date.</b></td>
                    <td style="width:10%" rowspan="2"><b>Investigation Report No.</b></td>
                    <td style="width:20%" ><b>Detail of Water Investigation</b></td>
                    <td style="width:10%" rowspan="2"><b>Status</b></td>
                    <td style="width:15%" rowspan="2"><b>Investigation Closing Date	</b></td>
                    <td style="width:10%" rowspan="2"><b>Remark</b></td>
                    <td style="width:10%" rowspan="2"><b>Form Requisition Given By</b></td>
                    <td style="width:10%" rowspan="2"><b>Form Issued By</b></td>
                </tr>
                <tr style="font-weight:bold; border: solid 1px black">
                    <td style="width:7%">Water sampling point	</td>
                    <td style="width:7%">Product Batch No.	</td>
                    <td style="width:6%">Brief Description of Investigation</td>
                </tr>
            </thead>';
        $i=1;
        $sql="SELECT * FROM water_investigation ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='
            <tbody>
                <tr>
                   <td style="width:5%">'.$i++.'</td>
                   <td style="width:10%">'.date('d-m-Y',strtotime($row['intimation_date'])).'</td>
                   <td style="width:10%">'.$row['emreport_no'].'</td>
                   <td style="width:20%">'.$row['sampling_point'].'</td>
                   <td style="width:10%">'.$row['batch_no'].'</td>
                   <td style="width:15%">'.$row['description'].'</td>
                   <td style="width:10%">'.$row['status'].'</td>
                   <td style="width:10%">'.date('d-m-Y',strtotime($row['close_date'])).'</td>
                   <td style="width:10%"></td> 
                </tr>
            </tbody>';
            
            }
        }
        $html.='
        </table>';
                 
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('WaterInvestigation.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
