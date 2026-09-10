<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('response_token: test123456');

$output = Array();
$token = $_GET["token"];
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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveVolumetric") {
        $sql = "INSERT INTO solutions (solution_type, solution_code, solution_name, strength, unit) VALUES ('Volumetric Solution', '".$input["solution_code"]."', '".$input["solution_name"]."', '".$input["strength"]."', '".$input["unit"]."')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getVolumetricSolutions") {
        $output = Array();
        $sql = "SELECT * FROM solutions WHERE solution_type='Volumetric Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveIndicator") {
        $sql = "INSERT INTO solutions (solution_type, solution_code, solution_name) VALUES ('Indicator Solution', '".$input["solution_code"]."', '".$input["solution_name"]."')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getIndicatorSolutions") {
        $output = Array();
        $sql = "SELECT * FROM solutions WHERE solution_type='Indicator Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveStandard") {
        $sql = "INSERT INTO solutions (solution_type, solution_code, solution_name) VALUES ('Standard Solution', '".$input["solution_code"]."', '".$input["solution_name"]."')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getStandardSolutions") {
        $output = Array();
        $sql = "SELECT * FROM solutions WHERE solution_type='Standard Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveGeneral") {
        $sql = "INSERT INTO solutions (solution_type, solution_code, solution_name) VALUES ('General Solution', '".$input["solution_code"]."', '".$input["solution_name"]."')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getGeneralSolutions") {
        $output = Array();
        $sql = "SELECT * FROM solutions WHERE solution_type='General Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadIndicatorSolutions") {
        $_GET['filename'] = 'IndicatorSolutions'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">IndicatorSolutions</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 33%;">Sr.</td>
                    <td style="width: 34%;">Solution Code</td>
                    <td style="width: 33%;">Solution Name</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM solutions WHERE solution_type='Standard Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $html.='<tr nobr="true">
                        <td style="width: 33%;">'.$i.'.</td>
                        <td style="width: 34%;">'.$row['solution_code'].'</td>
                        <td style="width: 33%;">'.$row['solution_name'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IndicatorSolutions.pdf', 'I');
    }else if ($_GET["type"] == "downloadStandardSolutions") {
        $_GET['filename'] = 'StandardSolutions'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">StandardSolutions</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 33%;">Sr.</td>
                    <td style="width: 34%;">Solution Code</td>
                    <td style="width: 33%;">Solution Name</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM solutions WHERE solution_type='Indicator Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $html.='<tr nobr="true">
                        <td style="width: 33%;">'.$i.'.</td>
                        <td style="width: 34%;">'.$row['solution_code'].'</td>
                        <td style="width: 33%;">'.$row['solution_name'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('StandardSolutions.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadVolumetricSolutions") {
        $_GET['filename'] = 'VolumetricSolutions'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">VolumetricSolutions</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 25%;">Sr.</td>
                    <td style="width: 25%;">Solution Code</td>
                    <td style="width: 25%;">Solution Name</td>
                    <td style="width: 25%;">Strength</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM solutions WHERE solution_type='Volumetric Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $html.='<tr nobr="true">
                        <td style="width: 25%;">'.$i.'.</td>
                        <td style="width: 25%;">'.$row['solution_code'].'</td>
                        <td style="width: 25%;">'.$row['solution_name'].'</td>
                        <td style="width: 25%;">'.$row['strength'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('VolumetricSolutions.pdf', 'I');
    }else if ($_GET["type"] == "downloadGeneralSolutions") {
        $_GET['filename'] = 'GeneralSolutions'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">GeneralSolutions</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 33%;">Sr.</td>
                    <td style="width: 34%;">Solution Code</td>
                    <td style="width: 33%;">Solution Name</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM solutions WHERE solution_type='General Solution' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $html.='<tr nobr="true">
                        <td style="width: 33%;">'.$i.'.</td>
                        <td style="width: 34%;">'.$row['solution_code'].'</td>
                        <td style="width: 33%;">'.$row['solution_name'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('GeneralSolutions.pdf', 'I');
    }

}

$conn->close();
?>