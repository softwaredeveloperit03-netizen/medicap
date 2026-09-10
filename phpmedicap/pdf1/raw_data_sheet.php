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
    $sql = "SELECT * FROM specification WHERE specification_no='".$_GET["specification_no"]."'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
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
            $html.='
            <table border="0" cellpadding="5" style="text-align:center;">
                <tr>
                    <td style="background-color:#DDDAD9;">RAW DATA SHEET</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5" style="text-align:left;">
                <tr>
                    <td style="width:20%"><b>Department</b></td>
                    <td style="width:30%"><b>Quality Control Department</b></td>
                    <td style="width:20%"><b>Material Code</b></td>
                    <td style="width:30%">'.$row['material_code'].'</td>
                </tr>
                <tr>
                    <td><b>Name of Material</b></td>
                    <td>'.$row['material_name'].'</td>
                    <td><b>A.R No</b></td>
                    <td>'.$row['supersede_no'].'</td>
                </tr>
                <tr>
                    <td><b>Sample Qty.</b></td>
                    <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                    <td><b>Shelf Life</b></td>
                    <td>'.$row['shelf_life'].'</td>
                </tr>
                <tr>
                    <td><b>Effective Date</b></td>
                    <td>'.$row['entry_date'].'</td>
                    <td><b>Specification Type</b></td>
                    <td>'.$row['spec_type'].'</td>
                </tr>
                <tr>
                    <td><b>Review Date</b></td>
                    <td></td>
                    <td><b>Storage</b></td>
                    <td>'.$row['storage'].'</td>
                </tr>
                <tr>
                    <td><b>Safety Precaution</b></td>
                    <td style="width:80%;">'.$row['safety_precaution'].'</td>
                </tr>
            </table>
            <div></div>
            <p style="text-align:center;"><b>Raw Material Specification</b></p>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:10%;">Sr No</td>
                    <td style="width:30%;">Test</td>
                    <td style="width:60%;">Specification</td>
                </tr>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$_GET["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
                    $counter = 1;
                    while($row1 = $result1->fetch_assoc()){
                        $html.='
                        <tr>
                            <td>'.$counter++.'</td>
                            <td>'.$row1['test'].'</td>
                            <td>'.$row1['description'].'</td>
                        </tr>';
                    }
                }
                $html.='
            </table>
            <div></div>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('specification.pdf', 'I');
    } else {
        echo "{\"status\":\"invalid\"}";
    }
}else{
    echo "Invalid Token";
}
?>