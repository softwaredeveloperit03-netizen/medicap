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
    
    if ($_GET["type"] == "saveEm") {
        $sql = "INSERT INTO em_investigation (emreport_no, intimation_date,batch_no ,description,given_by ,issue_by,status,inv_date,remark) VALUES ('".$input["emreport_no"]."','".$entry_date."','".$input["batch_no"]."', '".$input["description"]."' ,  '".$_GET["emp_id"]."' ,'' ,'".$input["status"]."' ,'".$input["inv_date"]."' ,'".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getEmInvestigation"){
        $output=Array();
        $sql="SELECT * FROM em_investigation WHERE DATE(intimation_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if ($_GET["type"] == "downloadEmInvestigation") {
        $_GET['filename'] = 'EmInvestigation'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">EmInvestigation</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 10%;">Sr.</td>
                    <td rowspan="2" style="width: 10%;">initiation Date</td>
                    <td style="width: 30%; text-align:center;">Details of investigation</td>
                    <td rowspan="2" style="width: 10%;">Status</td>
                    <td rowspan="2" style="width: 10%;">Investigation Closing Date</td>
                    <td rowspan="2" style="width: 10%;">Remarks</td>
                    <td rowspan="2" style="width: 10%;">From Requestion Given By</td>
                    <td rowspan="2" style="width: 10%;">From Issued By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Investigation Report No</td>
                    <td style="width: 10%;">Product Batch No</td>
                    <td style="width: 10%;">Breif Description of Investigation</td>
                </tr>
            </thead>';
        $i=1;
        $sql="SELECT * FROM em_investigation WHERE DATE(intimation_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['intimation_date'].'</td>
                        <td style="width: 10%;">'.$row['emreport_no'].'</td>
                        <td style="width: 10%;">'.$row['batch_no'].'</td>
                        <td style="width: 10%;">'.$row['description'].'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                        <td style="width: 10%;">'.$row['inv_date'].'</td>
                        <td style="width: 10%;">'.$row['remark'].'</td>
                        <td style="width: 10%;">'.$row['given_by'].'</td>
                        <td style="width: 10%;">'.$row['issue_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EmInvestigation.pdf', 'I');
    } 
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>