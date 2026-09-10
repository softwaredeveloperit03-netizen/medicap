<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);
    if($_GET["type"]=="balanceLog"){
        $_GET['filename'] = 'Balance Logbook'; $_GET['pdftype'] = 'landscape';  include("../../pdfimp.php");
        $sql = "SELECT * FROM equipments WHERE status='approve' AND category='Balance'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
        $html.='
    	    <table cellpadding="5">
    	        <tr style="background-color:#DDDAD9;font-weight:bold;">
    	            <td style="width:10%;">Sr.</td>
    	            <td style="width:15%;">Capacity</td>
    	            <td style="width:15%;">Make</td>
    	            <td style="width:15%;">Receiving no</td>
    	            <td style="width:15%;">Required For</td>
    	            <td style="width:15%;">Request By</td>
    	            <td style="width:15%;">Status</td>
    	        </tr>';
    	   $counter= 1;
            while ($row = $result->fetch_assoc()) {
                $html.='
    	        <tr>
    	            <td>'.$counter++.'</td>
    	            <td>'.$row['capacity'].'</td>
    	            <td>'.$row['make'].'</td>
    	            <td>'.$row['document_no'].'</td>
    	            <td>'.$row['required_for'].'</td>
    	            <td>'.$row['request_by'].'</td>
    	            <td>'.$row['status'].'</td>
    	        </tr>';
            }
            $html.='</table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CAPA Log.pdf', 'I');
    }
} else {
    echo "[]";
}
?>