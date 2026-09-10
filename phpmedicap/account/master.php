<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
     if ($_GET["type"] == "downloadgst") {
        $_GET['filename'] = 'GST Percentage'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:25%; text-align:centre;"><b>CGST</b></td>
                <td style="width:25%; text-align:centre;"><b>SGST</b></td>
                <td style="width:20%; text-align:centre;"><b>IGST</b></td>
                <td style="width:20%; text-align:centre;"><b>Description</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM gst_per";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
    $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:25%;">'.$row['cgst'].'</td>
                <td style="width:25%;">'.$row['sgst'].'</td>
                <td style="width:20%;">'.$row['igst'].'</td>
                <td style="width:20%;">'.$row['description'].'</td>
            </tr>';
            $i++;
                }
            }
       $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadgst.pdf', 'I');
        
    } else if ($_GET["type"] == "downloadCESS") {
        $_GET['filename'] = 'CESS Percentage'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:50%; text-align:centre;"><b>Sr</b></td>
                <td style="width:50%; text-align:centre;"><b>Cess%</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM cess_per";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
        $html.='<tr>
                <td style="width:50%;">'.$i++.'.</td>
                <td style="width:50%;">'.$row['cess'].'</td>
            </tr>';
                }
            }
        $html.='</table>';
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadCESS.pdf', 'I');
        
    } else if ($_GET["type"] == "downloadstatecode") {
        $_GET['filename'] = 'State Codes'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:40%; text-align:centre;"><b>State Code</b></td>
                <td style="width:50%; text-align:centre;"><b>State Name</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM state";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
        $html.='<tr>
                <td style="width:10%;">'.$i++.'.</td>
                <td style="width:40%;">'.$row['state_code'].'</td>
                <td style="width:50%;">'.$row['state_name'].'</td>
            </tr>';
                }
            }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadstatecode.pdf', 'I');
        
    } else if ($_GET["type"] == "downloadunits") {
        $_GET['filename'] = 'Units'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:40%; text-align:centre;"><b>unit code</b></td>
                <td style="width:50%; text-align:centre;"><b>Description</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM units";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
        $html.='<tr>
                <td style="width:10%;">'.$i++.'.</td>
                <td style="width:40%;">'.$row['unit_code'].'</td>
                <td style="width:50%;">'.$row['description'].'</td>
            </tr>';
                }
            }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadunits.pdf', 'I');
        
    } else if ($_GET["type"] == "downloadCountryCode") {
        $_GET['filename'] = 'Country Code'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:40%; text-align:centre;"><b>Contry code</b></td>
                <td style="width:50%; text-align:centre;"><b>Contry Name</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM countries";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
        $html.='<tr>
                <td style="width:10%;">'.$i++.'.</td>
                <td style="width:40%;">'.$row['country_code'].'</td>
                <td style="width:50%;">'.$row['country_name'].'</td>
            </tr>';
                }
            }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadCountryCode.pdf', 'I');
        
    } else if ($_GET["type"] == "downloadPortsCode") {
        $_GET['filename'] = 'Ports Code'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:40%; text-align:centre;"><b>Port Code</b></td>
                <td style="width:50%; text-align:centre;"><b>Port Name</b></td>
            </tr>';
            // $i=1;
            // $sql = "SELECT * FROM ports";
            // $result = $conn->query($sql);
            // if ($result->num_rows > 0) {
            //     while ($row = $result->fetch_assoc()) {
            //         //echo $row["port_code"]."<br>";
        $html.='<tr>
                <td style="width:10%;">'.$i++.'.</td>
                <td style="width:40%;">'.$row['port_code'].'</td>
                <td style="width:50%;">'.$row['port_name'].'</td>
            </tr>';
            //     }
            // }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadPortsCode.pdf', 'I');
        
    } 
    
    
    
    
    
}

$conn->close();
?>