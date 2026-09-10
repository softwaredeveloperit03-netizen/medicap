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
    
    if($_GET["type"] == "getCriticalFilter"){
        $output = Array();
        $sql ="SELECT * FROM ventfilter WHERE category='Critical'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "getNonCriticalFilter"){
        $output = Array();
        $sql ="SELECT * FROM ventfilter WHERE category='Non Critical'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] =="saveReplacementRecord"){
        $sql="INSERT INTO ventfilter_replacement(filter_id, remark , entry_by , entry_date ,next_date , check_by,check_date)VALUES('".$input["filter_id"]."' ,'".$input["remark"]."' ,'".$_GET["emp_id"]."' ,'$entry_date' ,'".$input["next_date"]."','','')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
             echo "{\"status\":\"failed\"}";
        }
    }else if($_GET["type"] =="getReplacementRecord"){
        $output=Array();
        $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "downloadCriticalFilter") {
        $_GET['filename'] = 'List of Vent Filter - Critical'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 10%;">Sr.</td>
                        <td rowspan="2" style="width: 10%;">Plant Name.</td>
                        <td rowspan="2" style="width: 10%;">Location</td>
                         <td rowspan="2" style="width: 10%;">Filter Id</td>
                        <td style="width: 30%;text-align:center;">MOC</td>
                       <td style="width: 30%;text-align:center;">Filter Cartridge Details</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Housing</td>
                        <td style="width:15%;">Cartridge</td>
                         <td style="width:10%;">Micron</td>
                          <td style="width:10%;">Diameter</td>
                           <td style="width:10%;">Length</td>
                    </tr>
                </thead>';
            $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
            
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                    
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
    }
     else if ($_GET["type"] == "downloadNonCriticalFilter") {
        $_GET['filename'] = 'List of Vent Filter - Non Critical'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 10%;">Sr.</td>
                        <td rowspan="2" style="width: 10%;">Plant Name.</td>
                        <td rowspan="2" style="width: 10%;">Location</td>
                         <td rowspan="2" style="width: 10%;">Filter Id</td>
                        <td style="width: 30%;text-align:center;">MOC</td>
                       <td style="width: 30%;text-align:center;">Filter Cartridge Details</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Housing</td>
                        <td style="width:15%;">Cartridge</td>
                         <td style="width:10%;">Micron</td>
                          <td style="width:10%;">Diameter</td>
                           <td style="width:10%;">Length</td>
                    </tr>
                </thead>';
            $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
     }
    else if ($_GET["type"] == "downloadReplacementRecord") {
        $_GET['filename'] = 'Vent Filter Replacement Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 20%;">Filter Id No.</td>
                        <td rowspan="2" style="width: 25%;">Date Of Filter Replacement</td>
                        <td style="width: 30%;text-align:center;">Department</td>
                        <td rowspan="2" style="width: 25%;">Remark (If Any)</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Done By</td>
                        <td style="width:15%;">Check By</td>
                    </tr>
                </thead>';
            $sql ="SELECT * FROM ventfilter_replacement WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$row['filter_id'].'</td>
                        <td style="width: 25%;">'.date('d-m-Y',strtotime($row['next_date'])).'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                        <td style="width: 15%;">'.$row['check_by'].'</td>
                        <td style="width: 25%;">'.$row['remark'].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vent Filter Replacement Record', 'I');
    
    }
    
}

$conn->close();
?>