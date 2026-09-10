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
     if ($_GET["type"] == "downloadSalesOrderLog") {
        $_GET['filename'] = 'Sales Orders Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Sales Orders Log</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Date</b></td>
            <td style="width:20%; text-align:centre;"><b>Sales Confirmation No.</b></td>
            <td style="width:20%; text-align:centre;"><b>Dispatch To</b></td> 
            <td style="width:20%; text-align:centre;"><b>Invoice To</b></td>
            <td style="width:20%; text-align:centre;"><b>Packing</b></td>
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:20%;"></td>
            <td style="width:20%;"></td> 
            <td style="width:20%;"></td>
            <td style="width:20%;"></td>
        </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadSalesOrderLog.pdf', 'I');
    }
    
    }

$conn->close();
?>