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

    $sql = "SELECT * FROM stock_book";
    $result = $conn->query($sql);
    $output = Array();
    if($result->num_rows > 0){
        $_GET['filename'] = 'STOCK BOOK'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 6%;">Sr.</td>
                    <td style="width: 34%;">Material Name</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 15%;">Quantity</td>
                    <td style="width: 20%;">Status</td>
                </tr>
            </thead>
            <tbody>';
            $i = 1;
    	while ($row = $result->fetch_assoc()) {
    	    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
    	    $result1 = $conn->query($sql1);
    	    $row1 = $result1->fetch_assoc();
            $html.='<tr>
                        <td style="width: 6%;">'.$i++.'</td>
                        <td style="width: 34%;">'.$row1["material_name"].'</td>
                        <td style="width: 15%;">'.$row1["material_code"].'</td>
                        <td style="width: 10%;">'.$row1["grade"].'</td>
                        <td style="width: 15%;">'.$row["qty"].'</td>
                        <td style="width: 20%;">'.$row["status"].'</td>
                    </tr>';
    	}
    	$html.='
    	    </tbody>
    	</table>';
    }
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('stockbook.pdf', 'I');
}
?>