<?php 
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
$html1 = '
<style>
td {
    border:solid 1px BCBBBA;
}
</style>

<table border="0" cellpadding="5" style="text-align:center; vertical-align:middle;">
    <tr>
        <td>Master Copy Stamp</td>
        <td><img src="../../assets/logo.png" style="height:50px;"></td>
        <td>Controlled Copy Stamp</td>
    </tr>
</table>

<div></div>
1.0   DISPENSING OF RAW MATERIAL
<div></div>
<table cellpadding="5" style="text-align:left;">
    <tr>
        <td style="width:60%">Previous Product</td>
        <td style="width:40%">Batch No. </td>
    </tr>
    <tr>
        <td>Area Cleaning Done By</td>
        <td>Checked By :</td>
    </tr>
    <tr>
        <td>Line Clearance Given By QA</td>
        <td>Date :</td>
    </tr>
    <tr>
        <td>Line Clearance SOP Ref No : </td>
        <td>Time :</td>
    </tr>
    <tr>
        <td><b>Pressure differential in Reverse laminar air flow</b><br>(Limit 10.0 to 25.00 mm of W.G.)</td>
        <td> :</td>
    </tr>
</table>
<div></div>
1.2   DISPENSING RECORD (BMR COPY)
<div></div>
<table cellpadding="5" style="text-align:left;">
    <tr>
        <td style="width:28%">Dispensing Date</td>
        <td style="width:36%">Time</td>
        <td style="width:36%">RLAF</td>
    </tr>
    <tr>
        <td style="width:28%"></td>
        <td style="width:8%">Start Time</td>
        <td style="width:10%"></td>
        <td style="width:8%">End Time</td>
        <td style="width:10%"></td>
        <td style="width:8%">Start Time</td>
        <td style="width:10%"></td>
        <td style="width:8%">End Time</td>
        <td style="width:10%"></td>
    </tr>
</table>
<div></div>
<table cellpadding="5" style="text-align:left;">
    <tr>
        <td style="background-color:#DDDAD9;">Active Material Dispensing Record : </td>
    </tr>
    <tr>
        <td rowspan="2" style="width:8%">RM Code</td>
        <td rowspan="2" style="width:12%">Material Name</td>
        <td rowspan="2" style="width:8%">SPC</td>
        <td rowspan="2" style="width:5%">Lot</td>
        <td rowspan="2" style="width:8%">Batch Qty.</td>
        <td rowspan="2" style="width:8%">A.R. No</td>
        
        <td style="width:21%">Weight in KG </td>
        
        <td rowspan="2" style="width:10%">Done By Stores</td>
        <td rowspan="2" style="width:10%">Checked By Prod</td>
        <td rowspan="2" style="width:10%">Verified By QA</td>
    </tr>
    <tr>
        <td style="width:7%">Gross Wt</td>
        <td style="width:7%">Tare Wt</td>
        <td style="width:7%">Net Wt </td>
    </tr>
    <tr>
        <td rowspan="4"></td>
        <td rowspan="4"></td>
        <td rowspan="4"></td>
        
        <td>I</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        
        <td rowspan="4" style="width:10%"></td>
        <td rowspan="4" style="width:10%"></td>
        <td rowspan="4" style="width:10%"></td>
    </tr>
    <tr>
        <td>II</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>III</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>IV</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td style="width:33%;"><b>Total Quantity of Active</b></td>
        <td style="width:8%;"></td>
        <td style="width:22%;"><b>Total Quantity Dispensed </b></td>
        <td style="width:7%;"></td>
        <td style="width:10%"></td>
        <td style="width:10%"></td>
        <td style="width:10%"></td>
    </tr>
</table>
<div></div>


<table cellpadding="5" style="text-align:left;">
    <tr>
        <td style="background-color:#DDDAD9;">Inactive Material Dispensing Record  : </td>
    </tr>
    <tr>
        <td rowspan="2" style="width:8%">RM Code</td>
        <td rowspan="2" style="width:12%">Material Name</td>
        <td rowspan="2" style="width:8%">SPC</td>
        <td rowspan="2" style="width:5%">Lot</td>
        <td rowspan="2" style="width:8%">Batch Qty.</td>
        <td rowspan="2" style="width:8%">A.R. No</td>
        
        <td colspan="3" style="width:21%">Weight in KG </td>
        
        <td rowspan="2" style="width:10%">Done By Stores</td>
        <td rowspan="2" style="width:10%">Checked By Prod</td>
        <td rowspan="2" style="width:10%">Verified By QA</td>
    </tr>
    <tr>
        <td style="width:7%">Gross Wt</td>
        <td style="width:7%">Tare Wt</td>
        <td style="width:7%">Net Wt </td>
    </tr>
    <tr>
        <td colspan="12" style="background-color:#DDDAD9;">Drug Core: </td>
    </tr>
    <tr>
        <td rowspan="4"></td>
        <td rowspan="4"></td>
        <td rowspan="4"></td>
        
        <td>I</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        
        <td rowspan="4" style="width:10%"></td>
        <td rowspan="4" style="width:10%"></td>
        <td rowspan="4" style="width:10%"></td>
    </tr>
    <tr>
        <td>II</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>III</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>IV</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="4"><b>Total Quantity of Active</b></td>
        <td></td>
        <td colspan="3"><b>Total Quantity Dispensed </b></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    
    
    
    <tr nobr="true">
        <td colspan="12" style="background-color:#DDDAD9;">Binder : </td>
    </tr>
    <tr>
        <td rowspan="4"></td>
        <td rowspan="4"></td>
        <td rowspan="4"></td>
        
        <td>I</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        
        <td rowspan="4" style="width:10%"></td>
        <td rowspan="4" style="width:10%"></td>
        <td rowspan="4" style="width:10%"></td>
    </tr>
    <tr>
        <td>II</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>III</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>IV</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="4"><b>Total Quantity of Active</b></td>
        <td></td>
        <td colspan="3"><b>Total Quantity Dispensed </b></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    
    
    <tr>
        <td colspan="12" style="background-color:#DDDAD9;">SR Coating : </td>
    </tr>
    <tr>
        <td rowspan="2"></td>
        <td rowspan="2"></td>
        <td rowspan="2"></td>
        
        <td>I</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        
        <td rowspan="2" style="width:10%"></td>
        <td rowspan="2" style="width:10%"></td>
        <td rowspan="2" style="width:10%"></td>
    </tr>
    <tr>
        <td>II</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="4"><b>Total Quantity of Active</b></td>
        <td></td>
        <td colspan="3"><b>Total Quantity Dispensed </b></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    
    
    <tr nobr="true">
        <td colspan="12" style="background-color:#DDDAD9;">Blending: : </td>
    </tr>
    <tr>
        <td style="width:10%;"></td>
        <td style="width:10%;"></td>
        <td style="width:10%;"></td>
        <td style="width:11%;"></td>
        <td style="width:8%;"></td>
        <td style="width:7%;"></td>
        <td style="width:7%;"></td>
        <td style="width:7%;"></td>
        <td style="width:10%;"></td>
        <td style="width:10%"></td>
        <td style="width:10%"></td>
    </tr>
</table>
<div></div>
<table cellpadding="5">
    <tr>
        <td style="border:none; background-color:#DDDAD9; width:100%;">Digitally Signed By</td>
    </tr>
    <tr>
        <td style="width:20%; border:none; border-bottom: 1px dashed blue;">Prepared By</td>
        <td style="width:7%; border:none; border-bottom: 1px dashed blue;"><img src="1.png" style="width:15px; height:15px;"></td>
        <td style="width:20%; border:none; border-bottom: 1px dashed blue;">Sudan</td>
        <td style="width:20%; border:none; border-bottom: 1px dashed blue;">Enginner</td>
        <td style="width:13%; border:none; border-bottom: 1px dashed blue;">QA</td>
        <td style="width:20%;border:none; border-bottom: 1px dashed blue;">07/06/2020 16:01:56</td>
    </tr>
    <tr>
        <td style="border:none; border-bottom: 1px dashed blue;">Checked By</td>
        <td style="border:none; border-bottom: 1px dashed blue;"><img src="1.png" style="width:15px; height:15px;"></td>
        <td style="border:none; border-bottom: 1px dashed blue;"></td>
        <td style="border:none; border-bottom: 1px dashed blue;"></td>
        <td style="border:none; border-bottom: 1px dashed blue;"></td>
        <td style="border:none; border-bottom: 1px dashed blue;"></td>
    </tr>
    <tr>
        <td style="border:none;">Approved By</td>
        <td style="border:none;"><img src="2.png" style="width:15px; height:15px;"></td>
        <td style="border:none;"></td>
        <td style="border:none;"></td>
        <td style="border:none;"></td>
        <td style="border:none;"></td>
    </tr>
</table>

';

EOD;
$pdf->writeHTML($html1, true, false, false, false, '');
$pdf->Output('despensingofmaterial.pdf', 'I');


?>