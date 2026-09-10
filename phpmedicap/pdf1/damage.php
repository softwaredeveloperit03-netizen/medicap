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
    if($_GET["type"]=="damagelog"){
        $sql = "SELECT * FROM raw_material WHERE damage NOT IN ('pending', 'no')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
            $_GET['type'] = 'empdetail';
            include("pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'header';
                    include("pdfimp.php");
                }
                public function Footer() {
                    $_GET['type'] = 'footer';
                    include("pdfimp.php");
                }
            }
            
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(15, 45, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 52);
            $pdf->AddPage('L', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
    	    <tr>
    	        <td style="width:100%; text-align:center; background-color:#DDDAD9;"><b>Annexure III : Batch Release Record</b></td>
    	    </tr>
	    </table>
	    <div></div>
	    <table cellpadding="5" style="text-align:center;">
	        <tr style="background-color:#DDDAD9; font-weight:bold;">
	            <td style="width:12%;">Date</td>
	            <td style="width:25%;">Product Name</td>
	            <td style="width:12%;">Batch No.</td>
	            <td>Batch Release Date</td>
	            <td>No of Shippers/Drums</td>
	            <td>Batch Released By</td>
	        </tr>';
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row['product_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
            $html.='
                <tr>
    	            <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
    	            <td>'.$row1['product_name'].'</td>
    	            <td>'.$row['batch_no'].'</td>
    	            <td>'.date('d/m/Y', strtotime($row['release_date'])).'</td>
    	            <td>'.$row['shippers'].'</td>
    	            <td>'.$row['entry_by'].'</td>
	            </tr>
            ';
            }
            $html.='</table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BatchRelease.pdf', 'I');
    }
} else {
    echo "[]";
}
?>