<?php

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$pdf = new TCPDF('P','mm','A4');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();


 $html='

<table border="1"  >
        <tr>
         <td style="width: 100px;  height: 20px;  text-align: center; vertical-align: baseline;"  rowspan="2">logo</td>
             <td style="width: 330px;  text-align: center;" rowspan="2"><b>  NOVO EXCIPIENTS PVT. LTD,NAVI MUMBAI QUALITY CONTROL MICROBIOLOGY DEPARTMENT</b></td>
         <td style="width: 50px; "><b> Issued  by/on</b></td>
         <td style="width: 60px;"></td> 
        </tr>
        <tr>
        <td style="width: 50px;"><b> No. of Copies</b></td>
        <td style="width: 60px;"></td>
       </tr>
       <tr>
            <td><b> Format Title :</b></td>
            <td colspan="3"> Media Preparation Record-Soya bean Casein Digest Agar</td>
            </tr>

        <tr>
            <td><b> Format No:</b></td>
            <td style="width:300px"> F/SOP/QM/009/07-03</td>
            <td style="width:80px" > Page No.:</td>
            <td>1 of 1</td>
        </tr>
</table><div></div>

<table border="1"  >
  <tr style="">
    <td style="width:450px"> Bottle/in-house lot No:SCDA. :</td>
    <td style="width:90px;" > Date:</td>

  </tr>
</table>
<div></div>

<table border="1">
<tr>
<td style="width:60px; text-align: center;">M-Code</td>
<td style="width:100px;text-align: center;">Lot/ B.No.</td>
<td style="width:80px;text-align: center;">Use before</td>
<td style="width:60px;text-align: center;">.g / 1</td>
<td style="width:110px;text-align: center;">Volume(ml)</td>
<td style="width:130px;text-align: center;">Media Weight(g)</td>
</tr>
<tr >
<td style="width:60px; text-align: center;height:50px;">MH 290</td>
<td style="width:100px;text-align: center;height:50px;"></td>
<td style="width:80px;text-align: center;height:50px;"></td>
<td style="width:60px;text-align: center;height:50px;">40.0g</td>
<td style="width:110px;text-align: center;height:50px;"></td>
<td style="width:130px;text-align: center;height:50px;"> </td>
</tr>
</table>
<div></div>


<table  border="1">
<tr>
<td style="width:180px;text-align: center; "  colspan="2">pH (Limit:7.10-7.50)</td>
<td style="width:360px;text-align: center;text-align: center;"  colspan="3">Distribution</td>



</tr>
<tr>

<td style="width:90px;text-align: center;" rowspan="2">After</td>
<td style="width:90px;text-align: center;" rowspan="2"></td>
<td style="width:120px;text-align: center;">Container Type</td>
<td style="width:120px;text-align: center;">No. of container  </td>
<td style="width:120px;text-align: center;">ml/container</td>
</tr>
<tr>
<td style="width:120px;height:60px;text-align: center;"></td>
<td style="width:120px;height:60px;text-align: center;"></td>
<td style="width:120px;height:60px;text-align: center;"></td>
</tr>



</table ><div></div>

<table border="1">
<tr>
<td style="width:160px; text-align: center;" rowspan="2">Autoclave No./Load</td>
<td style="width:140px;text-align: center;" colspan="2">Sterlization</td>
<td style="width:80px;text-align: center;" rowspan="2">Duration</td>
<td style="width: 80px;text-align: center;" rowspan="2">Prepared by</td>
<td style="width: 80px;text-align: center;" rowspan="2">Remarks</td>

</tr>
<tr>

<td style="width: 70px;;text-align: center;">Temp &deg;C</td>
<td style="width:70px;text-align: center;">Pressure psi</td>


</tr>
<tr>

<td style="width: 160px;;text-align: center;"></td>
<td style="width:70px;text-align: center;">121.5&deg;C</td>
<td style="width:70px;text-align: center;">15 psi</td>
<td style="width:80px;text-align: center;"></td>
<td style="width:80px;text-align: center;"></td>
</tr>

</table><div></div>

<table border="1">
<tr>
  <td colspan="7" style="width:540px; text-align:center;">Positive Control / Growth Promotion Test</td>
</tr>
<tr>
    <td style="width:60px;text-align:center;">Plates used</td>
    <td style="width:100px;text-align:center;">Container used</td>
    <td style="width:80px;text-align:center;">incubated at/ For </td>
    <td style="width:60px;text-align:center;">cfu/ml added</td>
    <td style="width:60px;text-align:center;">cfu/ml recovered</td>
    <td style="width:90px;text-align:center;">%of recovery (50%-200%)</td>
    <td style="width:90px;text-align:center;">Remark/Sign (C/NC)</td>

   
</tr>
<tr>
    <td style="width:60px;text-align:center;">1 plate</td>
    <td style="width:100px;text-align:center;">Saureus</td>
    <td style="width:80px;text-align:center;"rowspan="2">30-35&deg;C for 3days  </td>
    <td style="width:60px;text-align:center; "></td>
    <td style="width:60px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>

   
</tr>
<tr>
    <td style="width:60px;text-align:center;">1 Plate</td>
    <td style="width:100px;text-align:center;">P.aeruginosa</td>
    <td style="width:60px;text-align:center;"></td>
    <td style="width:60px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>

   
</tr>
<tr>
    <td style="width:60px;text-align:center;">1 Plate</td>
    <td style="width:100px;text-align:center;">C.albicans</td>
    <td style="width:80px;text-align:center;"rowspan="2">20-25&deg;C for  5days  </td>
    <td style="width:60px;text-align:center; "></td>
    <td style="width:60px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>

   
</tr>
<tr>
    <td style="width:60px;text-align:center;">1 Plate</td>
    <td style="width:100px;text-align:center;">A.brasiliensis</td>
    <td style="width:60px;text-align:center;"></td>
    <td style="width:60px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>
    <td style="width:90px;text-align:center;"></td>

   
</tr>

</table><div></div>

<table border="1">
<tr>
  <td colspan="7" style="width:540px; text-align:center;">Sterility result of prepared media / Negative Control</td>
</tr>
<tr>
    <td style="text-align:center;width:80px;">Date</td>
    <td style="text-align:center;width:90px;">Container Used</td>
    <td style="text-align:center;width:80px;">Incubated at / For</td>
    <td style="text-align:center;width:110px;">Obervation</td>
    <td style="text-align:center;width:90px;">Remarks (C/NC)</td>
    <td style="text-align:center;width:90px;">Sign</td>


</tr>
<tr>
    <td style="text-align:center;width:80px;"></td>
    <td style="text-align:center;width:90px;"> All plates / 1Plate</td>
    <td style="text-align:center;width:80px;"> 30-35 &deg;C for 5 days</td>
    <td style="text-align:center;width:110px;"></td>
    <td style="text-align:center;width:90px;"></td>
    <td style="text-align:center;width:90px;"></td>


</tr>
</table><div></div>

<table border="1">
<tr>
      <td style="text-align:center;width: 270px;height:20px;"></td>
      <td style="text-align:center;width: 270px;height:20px;"></td>
</tr>
<tr>
      <td style="text-align:center;width: 270px;height:30px;"><b>ANALYSED BY/ON</b></td>
      <td style="text-align:center;width: 270px;height:30px;"><b>CHECKED BY/ON</b></td>
</tr>
</table><div></div>



<table border="1">
<tr>
      <td style="text-align:center;width: 90px;"></td>
      <td style="text-align:center;width: 150px;"><b>PREPARED BY</b></td>
      <td style="text-align:center;width: 150px;"><b>REVIEWED BY</b></td>
      <td style="text-align:center;width: 150px;"><b>APPROVED BY</b></td>
</tr>
<tr>
      <td style="text-align:center;width: 90px;">Name</td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
</tr>
<tr >
      <td style="text-align:center;width: 90px; height:30px; ">Sign/Date</td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
</tr>
<tr>
      <td style="text-align:center;width: 90px;">Designation</td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
</tr>
<tr>
      <td style="text-align:center;width: 90px;">Department</td>
      <td style="text-align:center;width: 150px; "></td>
      <td style="text-align:center;width: 150px;"></td>
      <td style="text-align:center;width: 150px;"></td>
</tr>

</table>







 ';


$pdf->WriteHTMLcell(19,0,9,'',$html,0);

$pdf->Output();
?>