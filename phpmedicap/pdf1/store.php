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

    if ($_GET["type"] == "stationaryStockLog") {
        $_GET['filename'] = 'STATIONARY STOCK BOOK'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");

        if($_GET['from_date'] != '' && $_GET['material_code'] != ''){
            $sql = "SELECT * FROM stock_book WHERE material_type='Stationary' AND material_code = '".$_GET['material_code']."' AND  DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER by id DESC";
        }else if($_GET['from_date'] != '' && $_GET['material_code'] == ''){
            $sql = "SELECT * FROM stock_book WHERE material_type='Stationary' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER by id DESC";
        }else if($_GET['from_date'] == '' && $_GET['material_code'] != ''){
            $sql = "SELECT * FROM stock_book WHERE material_type='Stationary' AND material_code = '".$_GET['material_code']."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM stock_book WHERE material_type='Stationary'";
        }
    	$result = $conn->query($sql);
    	$html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 40%;">Material Name</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 15%;">Received Qty.</td>
                    <td style="width: 20%;">Unit</td>
                </tr>
            </thead>
            <tbody>';
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
        		    $sql1 = "SELECT * FROM general_material WHERE material_code='".$row["material_code"]."'";
        		    $result1 = $conn->query($sql1);
        		    if ($result1->num_rows > 0) {
        		         while ($row1 = $result1->fetch_assoc()) {
        		            $row["material_name"] = $row1["material_name"];
        		         }
        		    }
                    $html.='
                    <tr>
                        <td style="width: 10%;">'.$counter++.'</td>
                        <td style="width: 40%;">'.$row["material_name"].'</td>
                        <td style="width: 15%;">'.$row["material_code"].'</td>
                        <td style="width: 15%;">'.$row["qty"].'</td>
                        <td style="width: 20%;">'.$row["unit"].'</td>
                    </tr>';
    		        
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('stockbook.pdf', 'I');
    }
    else if ($_GET["type"] == "packingStockLog") {
       $_GET['filename'] = 'Indend Of General Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <table cellpadding="5" border="1">
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">GRN No.</td>
                    <td style="width: 10%;">Vendor No.</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 15%;">Material Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Batch No.</td>
                    <td style="width: 10%;">Received Qty.</td>
                    <td style="width: 10%;">Issued Qty.</td>
                </tr> ';
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    	    $i=1;
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['grn_no'].'</td>
                        <td style="width: 10%;">'.$row['vendor_no'].'</td>
                        <td style="width: 15%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;">'.$row['grade'].'</td>
                        <td style="width: 10%;">'.$row['batch_no'].'</td>
                        <td style="width: 10%;">'.$row['qty'].'</td>
                        <td style="width: 10%;">'.$row['unit'].'</td>
                    </tr>';
                    $i++;
    		    }
    	    }
    	  $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('stockbook.pdf', 'I');
    }
    else if ($_GET["type"] == "rawStockLog") {
        $_GET['filename'] = 'RAW MATERIAL STOCK BOOK'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
    	$html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">GRN No.</td>
                    <td style="width: 10%;">Vendor No.</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 15%;">Material Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Batch No.</td>
                    <td style="width: 10%;">Available Qty</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    	    $i=1;
    		while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width: 10%;">'.$i.'</td>
                        <td style="width: 10%;">'.$row['grn_no'].'</td>
                        <td style="width: 10%;">'.$row['vendor_no'].'</td>
                        <td style="width: 10%;">'.$row['material_type'].'</td>
                        <td style="width: 15%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;">'.$row['grade'].'</td>
                        <td style="width: 10%;">'.$row['batch_no'].'</td>
                        <td style="width: 10%;">'.$row['qty'].'</td>
                    </tr>';
                    $i++;
    		}
    	}
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('stockbook.pdf', 'I');
    }
    else if($_GET['type'] == 'dedustingMaterialLog'){
        $_GET['filename'] = 'Dedusting Material Log'; 
        $_GET['pdftype'] = 'landscape'; 
        include('../pdfimp.php');
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 35%;">Material Name</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 15%;">Grade</td>
                    <td style="width: 15%;">Containers</td>
                    <td style="width: 10%;">Status</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.dedusting !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["receiving_details"] = json_decode($row["receiving_details"]);
                    $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                    $html.='
                    <tr>
                        <td style="width: 10%;" align="center">'.$counter++.'</td>
                        <td style="width: 35%;">'.$row["material_name"].'</td>
                        <td style="width: 15%;" align="center">'.$row["material_code"].'</td>
                        <td style="width: 15%;" align="center">'.$row["grade"].'</td>
                        <td style="width: 15%;" align="center">'.$row["containers"].'</td>
                        <td style="width: 10%;" align="center">'.$row["status"].'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET['type'] == 'receivingMaterialLog'){
        $_GET['filename'] = 'Receiving Material Log'; $_GET['pdftype'] = 'landscape';  include('../pdfimp.php');
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 15%;">Challan No</td>
                    <td style="width: 10%;">Challan Date</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 25%;">Material Name</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">PO No</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr>
                        <td style="width: 5%;" align="center">'.$counter++.'</td>
                        <td style="width: 10%;">'.$row["vendor_name"].'</td>
                        <td style="width: 15%;" align="center">'.$row["chalan_no"].'</td>
                        <td style="width: 10%;" align="center">'.$row["chalan_date"].'</td>
                        <td style="width: 15%;" align="center">'.$row["material_code"].'</td>
                        <td style="width: 25%;" align="center">'.$row["material_name"].'</td>
                        <td style="width: 10%;" align="center">'.$row["material_type"].'</td>
                        <td style="width: 10%;" align="center">'.$inword_details->po_no.'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET['type'] == 'damagecontainerLog'){
        $_GET['filename'] = 'Damage Container Inspection Log'; $_GET['pdftype'] = 'landscape';  include('../pdfimp.php');
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 15%;">Challan No</td>
                    <td style="width: 25%;">Challan Date</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 10%;">Material Name</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">PO No</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage NOT IN ('pending', 'no') AND m.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr>
                        <td style="width: 5%;" align="center">'.$counter++.'</td>
                        <td style="width: 10%;">'.$row["vendor_name"].'</td>
                        <td style="width: 15%;" align="center">'.$row["chalan_no"].'</td>
                        <td style="width: 25%;" align="center">'.$row["chalan_date"].'</td>
                        <td style="width: 15%;" align="center">'.$row["material_code"].'</td>
                        <td style="width: 10%;" align="center">'.$row["material_name"].'</td>
                        <td style="width: 10%;" align="center">'.$row["material_type"].'</td>
                        <td style="width: 10%;" align="center">'.$inword_details->po_no.'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET['type'] == 'weighingMaterialLog'){
        $_GET['filename'] = 'Weighing Material Log'; $_GET['pdftype'] = 'landscape';  include('../pdfimp.php');
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width:5%;">Sr.</td>
                    <td style="width:10%;">Vendor Name</td>
                    <td style="width:15%;">Challan No</td>
                    <td style="width:25%;">Challan Date</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:10%;">Material Name</td>
                    <td style="width:10%;">Material Type</td>
                    <td style="width:10%;">PO No</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='inprocess' AND weighing !='pending' AND c.material_type IN ('API', 'Excipient', 'Liquid')";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr align="center">
                        <td style="width:5%;">'.$counter++.'</td>
                        <td style="width:10%;" align="left">'.$row["vendor_name"].'</td>
                        <td style="width:15%;">'.$row["chalan_no"].'</td>
                        <td style="width:25%;">'.$row["chalan_date"].'</td>
                        <td style="width:15%;">'.$row["material_code"].'</td>
                        <td style="width:10%;">'.$row["material_name"].'</td>
                        <td style="width:10%;">'.$row["material_type"].'</td>
                        <td style="width:10%;">'.$inword_details->po_no.'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET['type'] == 'GRNLog'){
        $_GET['filename'] = 'Goods Receipts Notes Log'; $_GET['pdftype'] = 'landscape';  include('../pdfimp.php');
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 15%;">Challan No</td>
                    <td style="width: 25%;">Challan Date</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 10%;">Material Name</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">PO No</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.grn !='pending' AND m.material_type ='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr align="center">
                        <td style="width:5%;">'.$counter++.'</td>
                        <td style="width:10%;" align="left">'.$row["vendor_name"].'</td>
                        <td style="width:15%;">'.$row["chalan_no"].'</td>
                        <td style="width:25%;">'.$row["chalan_date"].'</td>
                        <td style="width:15%;">'.$row["material_code"].'</td>
                        <td style="width:10%;">'.$row["material_name"].'</td>
                        <td style="width:10%;">'.$row["material_type"].'</td>
                        <td style="width:10%;">'.$inword_details->po_no.'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET['type'] == 'challanLog'){
        $_GET['filename'] = 'Challan Log'; $_GET['pdftype'] = 'headfootlog';  include('../pdfimp.php');
        $html.='
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 15%;">Challan No</td>
                    <td style="width: 25%;">Challan Date</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 10%;">Material Name</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">PO No</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.grn !='pending' AND m.material_type ='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr align="center">
                        <td style="width:5%;">'.$counter++.'</td>
                        <td style="width:10%;" align="left">'.$row["vendor_name"].'</td>
                        <td style="width:15%;">'.$row["chalan_no"].'</td>
                        <td style="width:25%;">'.$row["chalan_date"].'</td>
                        <td style="width:15%;">'.$row["material_code"].'</td>
                        <td style="width:10%;">'.$row["material_name"].'</td>
                        <td style="width:10%;">'.$row["material_type"].'</td>
                        <td style="width:10%;">'.$inword_details->po_no.'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
}

?>