<?php 
require '../db.php';
require_once('../tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}
$pdf->AddPage();
$pdf->SetFont ('Times', '', '10' , '', 'default', true );
$html1 ='<style>
td {
    border:solid 1px BCBBBA;
}
</style>
<table border="0" cellpadding="5" style="text-align:center; vertical-align:middle;margin-top:20px;">
    <tr>
        <td colspan="3" style="width: 80%;">CYCLONE PHARMACEUTICALS PVT. LTD.</td>
        <td style="width: 20%;" rowspan="2"><img src="../../assets/logo.png" style="height:50px;"></td>
    </tr>
    <tr><td colspan="3" style="width: 80%;">202,Sai Heritage, Lane No 06,Adarsh Nagar/Tingare Nagar,Vishrantwadi to Air Port, near Air Port, Pune, 411015</td></tr>
    <tr><td colpsan="4" style="width: 100%;">RAW DATA SHEET</td></tr>
</table>
<br><br>';

$department = "";
$material_name = "";
$document_no = "";
$version_no = "";
$batch_no = "";
$supersede = "";
$mfg_date = "";
$exp_date = "";
$sample_qty = "";
$spec_no = "";
$reference_no = "";
$sap_no = "";
$sample_by = "";
$analysis_date = "";
$reference = "";
$effective_date = "";

$sql = "SELECT * FROM testing WHERE testing_no='".$_GET['testing_no']."'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html1 .= '<table cellpadding="5" style="text-align:left;">
                <tr>
                    <td style="width:15%">Department</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:47%">Quality Control Department</td>
                    <td style="width:15%">Material Code:</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:17%">'.$row["material_code"].'</td>
                </tr>';
        
        $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $html1.='<tr>
                    <td style="width:15%" rowspan="2">Name of Material / Product</td>
                    <td style="width:3%;vertical-align: middle;" rowspan="2"> :</td>
                    <td style="width:47%;vertical-align: middle;" rowspan="2">'.$row1["material_name"].'</td>
                    <td style="width:15%">SAP No.</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:17%">Jul,2018</td>
                </tr>';
            }
        }
        $html1.='
        <tr>
            <td style="width:15%">Version No.</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">Jul,2018</td>
        </tr>
        <tr>
            <td style="width:15%" rowspan="2">Batch Size</td>
            <td style="width:3%;vertical-align: middle;" rowspan="2"> :</td>
            <td style="width:47%;vertical-align: middle;" rowspan="2">ATP003</td>
            <td style="width:15%">Mfg Date</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">Jul,2018</td>
        </tr>
        <tr>
            <td style="width:15%">Exp. Date</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">Jul,2018</td>
        </tr>
        <tr>
            <td style="width:15%" rowspan="2">Sample Quantity</td>
            <td style="width:3%;vertical-align: middle;" rowspan="2"> :</td>
            <td style="width:47%;vertical-align: middle;" rowspan="2">ATP003</td>
            <td style="width:15%">Specification Refrence No.</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">Jul,2018</td>
        </tr>
        <tr>
            <td style="width:15%">SAP Reference No.</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">Jul,2018</td>
        </tr>
        <tr>
            <td style="width:15%">Sample By / Date</td>
            <td style="width:3%;"> :</td>
            <td style="width:47%"></td>
            <td style="width:15%">Analysis Completion Date</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">20/08/2016</td>
        </tr>
        <tr>
            <td style="width:15%">Reference</td>
            <td style="width:3%;"> :</td>
            <td style="width:47%"></td>
            <td style="width:15%">Effective Date</td>
            <td style="width:3%;"> :</td>
            <td style="width:17%">20/08/2016</td>
        </tr>
    </table>
    <h2 style="text-align: center;">ANALYTICAL REPORT SUMMARY</h2>
    <table cellpadding="5">
        <tr>
            <td style="width:10%;">Sr No.</td>
            <td style="width:30%;">Test</td>
            <td style="width:30%;">Specification</td>
            <td style="width:30%;">Result</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>';
    }
}

$sql = "SELECT * FROM testing_tests WHERE testing_no='T-01' GROUP BY test";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $i = 1;
    $alphabet = range('A', 'Z');
    while ($row = $result->fetch_assoc()) {
        $html1 .= '<h3>'.$i.'. '.$row["test"].'</h3>';
        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='T-01' WHERE test='".$row["test"]."' AND subtest !=''";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $html1.='<span><b>'.$alphabet[$i].'. Observation:</b> '.$row["result"].'</span><br>';
                $html1.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                if ($row["status"] == "approve") {
                    $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                } else {
                    $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                }
            }
        } else {
            $html1.='<span><b>Observation:</b> '.$row["result"].'</span><br>';
            $html1.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
            if ($row["status"] == "approve") {
                $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
            } else {
                $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
            }
        }
        $i++;
    }
}
EOD;

$pdf->writeHTML($html1, true, false, false, false, '');
$pdf->Output('samplingsopannexure.pdf', 'I');


?>