<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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



    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    if ($_GET['type'] == 'downloadIndendsLog'){
        $_GET['filename'] = 'Indend Of Spare'; $_GET['pdftype'] = 'landscape'; include("../../pdfimp2.php");
        $html.= "";
        $html.='
        <h2 style="text-align:center">Indend Of Spare</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>Indend No</b></td>
                <td style="width:10%; text-align:centre;"><b>Vendor No</b></td>
                <td style="width:10%; text-align:centre;"><b>Equipment Type</b></td>
                <td style="width:10%; text-align:centre;"><b>Equipment Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Equipment Code</b></td>
                <td style="width:10%; text-align:centre;"><b>Spare Part Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Req.Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Requirement</b></td>
                <td style="width:10%; text-align:centre;"><b>Status</b></td>
            </tr>
            <tr>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
            </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IndendsLog.pdf', 'I');
    }
    
    
    
    
    
    
    
    } else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>