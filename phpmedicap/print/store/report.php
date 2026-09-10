<?php

//include library
include('library/tcpdf.php');

//make TCPDF object
$pdf = new TCPDF('P','mm','A4');



//remove default headere and footer
$pdf->setPrintHeader(false);
$pdf->setPrintHeader(false);


//add page
$pdf->AddPage();


//add content
$pdf->Cell(190,10,'Manufacturing Work Order',1,1,'C');




//output


//make the table
$html = "
	<table>
		<tr>
			<th>PRODUCT NAME : </th>
			<th>PRODUCT CODE : </th>
		</tr>

		<tr>
		<th>WORK OREDR NO : </th>
		<th>BATCH SIZE : </th>
	</tr>

<tr>
	<th>MRF No  : </th>
	<th> </th>
</tr>

<div >
<h2>Raw Material</h2>
</div>

<tr>
	<th>PRODUCT NAME</th>
</tr>




</table>

<style>
	
	table {
		border-collapse:collapse;
		padding:10px;
	}
	th,td {
		border:1px solid red;
	
	}
	table tr th {
		color:black;
		font-weight:bold;
		
	}
</style>
	
	";


//WriteHTMLCell
$pdf->WriteHTMLCell(192,0,9,'',$html,0);	


//output
$pdf->Output();


?>