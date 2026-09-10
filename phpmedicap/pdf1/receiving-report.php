<?php 
require '../db.php';
require '../tcpdf/tcpdf.php';
require '../token.php';

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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 45, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 52);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            $pdf->AddPage('P', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $sql = "SELECT * FROM material_received";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
        $html.='
        <table cellpadding="5" style="text-align:center;">
            <tr>
                <td style="background-color:#DDDAD9;">Annexure I :  Record For Receiving of Raw Material /Packing Material</td>
            </tr>
        </table>
        <div></div>
        <table cellpadding="5" style="text-align:center;">
            <tr>
                <td>Date</td>
                <td style="width:20%;">Name of Material</td>
                <td style="width:9%;">B. No.</td>
                <td style="width:9%;">Received Qty.</td>
                <td>No of Containers Received</td>
                <td>Reference No. of Check list</td>
                <td>Received By</td>
                <td>Checked By</td>
            </tr>';
    	while($row = $result->fetch_assoc()){
    	    $sql2 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
            $result2 = $conn->query($sql2);
            $row2 = $result2->fetch_assoc();
        	    
    	    $sql6 = "SELECT * FROM employee WHERE emp_id='".$row["entry_by"]."'";
            $result6 = $conn->query($sql6);
            $row6 = $result6->fetch_assoc();
            
    	    $sql7 = "SELECT * FROM employee WHERE emp_id='".$row["check_by"]."'";
            $result7 = $conn->query($sql7);
            $row7 = $result7->fetch_assoc();
    	    $html.='
    	    <tr>
                <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                <td>'.$row2['material_name'].'</td>
                <td>'.$row['batch_no'].'</td>
                <td>'.$row['qty_received'].'</td>
                <td>'.$row['total_containers'].'</td>
                <td>'.$row['receiving_no'].'</td>
                <td>'.$row6['emp_name'].'</td>
                <td>'.$row7['emp_name'].'</td>
            </tr>';
    	}
    	$html.='
    	</table>';
    }
    EOD;
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('receiving-report.pdf', 'I');
}
else{
    echo "Invalid Token";
}
?>