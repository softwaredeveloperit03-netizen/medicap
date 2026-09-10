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
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    

    if($_GET['type'] == 'purchaseAllReport'){
        $_GET['filename'] = 'PURCHASE ORDER REPORT'; 
        $_GET['pdftype'] = 'landscape'; 
        include('../pdfimp.php');
        $html.='
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:13%;">PO No.</td>
                <td style="width:48%;">Vendor Name</td>
                <td style="width:13%;">GST Type</td>
                <td style="width:13%;">Prepare By</td>
                <td style="width:13%;">Prepare Date</td>
            </tr>';
            if($_GET['from'] != '' && $_GET['to'] != ''){
                $sql = "SELECT * FROM purchaseorder WHERE entry_date BETWEEN '" . $_GET['from'] . "' AND  '" . $_GET['to'] . "' ORDER by id DESC";
            }else{
                $sql = "SELECT * FROM purchaseorder ORDER by id DESC";
            }
            $result = $conn->query($sql);
            $output = Array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while($row1 = $result1->fetch_assoc()) {
                            $row["vendor_name"] = $row1["vendor_name"];
                        }
                    }
                    $html.='
                    <tr>
                        <td>'.$row["po_no"].'</td>
                        <td style="text-align:left;">'.$row["vendor_name"].'</td>
                        <td>'.$row["gst_type"].'</td>
                        <td>'.$row['entry_by'].'</td>
                        <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                    </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PurchaseOrder.pdf', 'I');
    }
    else if($_GET['type'] == 'purchaseReport'){
        $_GET['filename'] = 'PURCHASE-ORDER'; 
        $_GET['pdftype'] = 'headfoot'; 
        include('../pdfimp.php');
        $sql = "SELECT * FROM purchaseorder WHERE po_no = '".$_GET['pono']."'";
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sum = $row['net_total'];
                $sql1 = "SELECT vendor_name, address_corporate FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while($row1 = $result1->fetch_assoc()) {
                        $row["vendor_name"] = $row1["vendor_name"];
                        $row["vendor_address"] = $row1["address_corporate"];
                    }
                }
                $html.='
                <table border="0" cellpadding="5">
                    <tr>
                        <td style="border:none;">Ref.No: SBP-PO/'.$row["id"].'</td>
                        <td style="border:none; text-align:right;">Date: '.date('d/m/Y', strtotime($row["entry_date"])).'</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="8">
                    <tr>
                        <td>To,<br><b>'.$row["vendor_name"].'</b><br>'.$row["vendor_address"].'</td>
                        <td>Delivery At,<br>'.$row['delivery_address'].'<br></td>
                    </tr>
                </table>
                <table cellpadding="5" style="border:solid 1px BCBBBA;">
                    <tr>
                        <td style="width:10%;">Sr. No.</td>
                        <td style="width:25%;">Description</td>
                        <td style="width:10%;">HSN Code</td>
                        <td style="width:10%;">Qty</td>
                        <td style="width:15%;">Gross Total</td>
                        <td style="width:15%;">Tax Total</td>
                        <td style="width:15%;">Net Total</td>
                    </tr>';
                    $sql1 = "SELECT * FROM po_material WHERE po_no='".$row["po_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    $rowcount = $result1->num_rows;
                    if ($result1->num_rows > 0) {
                        $couter = 1;
                        while($row1 = $result1->fetch_assoc()) {
                            if ($row["isgeneral"] == 'yes') {
                                $sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while($row2 = $result2->fetch_assoc()) {
                                        $row1["material_name"] = $row2["material_name"];
                                        $row1["material_type"] = $row2["material_type"];
                                        $row1["grade"] = "-";
                                        $row1["unit"] = "-";
                                    }
                                }
                            } else {
                                $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row1["material_name"] = $row2["material_name"];
                                        $row1["material_type"] = $row2["material_type"];
                                        $row1["material_subtype"] = $row2["material_subtype"];
                                        $row1["grade"] = $row2["grade"];
                                        $row1["unit"] = $row2["unit"];
                                    }
                                }
                            }
                            $html.='
                            <tr>
                                <td>'.$couter++.'</td>
                                <td>'.$row1["material_name"].'</td>
                                <td></td>
                                <td>'.$row1["required_qty"].' '.$row1["unit"].'</td>
                                <td>'.$row['gross_total'].'</td>
                                <td>'.$row['gst_total'].'</td>
                                <td>'.$row['net_total'].'</td>
                            </tr>
                            ';
                        }
                    }
                    $rowspan = 14-$rowcount;
                    $html.='<tr>
                                <td rowspan="'.$rowspan.'+1"></td>
                                <td rowspan="'.$rowspan.'+1"></td>
                                <td rowspan="'.$rowspan.'+1"></td>
                                <td rowspan="'.$rowspan.'+1"></td>
                                <td rowspan="'.$rowspan.'+1"></td>
                                <td rowspan="'.$rowspan.'+1"></td>
                                <td rowspan="'.$rowspan.'+1"></td>
                            </tr>';
                    for($i =1; $i <= $rowspan; $i++){
                        $html.='
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>';
                    }
                $html.='
                    <tr>
                        <td colspan="6" style="text-align:right;">Sub Total</td>
                        <td>'.$row['gross_total'].'</td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align:right;">IGST </td>
                        <td>'.$row['gst_total'].'</td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align:right;">GST AS APPLICABLE</td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align:right;">R/f</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align:right;">Total</td>
                        <td>'.$sum.'</td>
                    </tr>
                </table>
                <table cellpadding="7" nobr="true">
                    <tr>
                        <td style="border:none;"><h4>Terms & Conditions:</h4></td>
                    </tr>';
                $terms = json_decode($row["terms_conditions"]);
                for($i=1; $i<=count($terms); $i++){
                    $data = $terms[$i-1];
                   $html.='<tr>
                        <td style="width:5%">'.$i.'</td>
                        <td style="width:95%;">'.$data->term.'</td>
                    </tr>';
                }
                $html.='
                </table>
                <b style="text-align:right;">For GMP Software Pvt Ltd</b>
                <div></div>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('PurchaseOrder.pdf', 'I');
        }
        else{
            echo "no record";
        }
    }
    else if($_GET['type'] == 'quotationAllReport'){
        $_GET['filename'] = 'QUOTATION REPORT'; 
        $_GET['pdftype'] = 'headfoot'; 
        include('../pdfimp.php');
        if($_GET['from'] == '' && $_GET['to'] == ''){
            $sql = "SELECT * FROM quotation ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM quotation WHERE entry_date BETWEEN '" . $_GET['from'] . "' AND  '" . $_GET['to'] . "'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <table cellpadding="5" style="text-align:center;">
                <thead>
                    <tr><td style="border:none;"></td></tr>
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:13%;">Quotation No.</td>
                        <td style="width:48%;">Vendor Name</td>
                        <td style="width:13%;">No. Of Item</td>
                        <td style="width:13%;">Entry By</td>
                        <td style="width:13%;">Prepare Date</td>
                    </tr>
                </thead>
                <tbody>';
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while($row1 = $result1->fetch_assoc()) {
                            $row["vendor_name"] = $row1["vendor_name"];
                        }
                    }
                    $html.='
                    <tr>
                        <td style="width:13%;">'.$row["quotation_no"].'</td>
                        <td style="width:48%; text-align:left;">'.$row["vendor_name"].'</td>
                        <td style="width:13%;">'.$row["total_items"].'</td>
                        <td style="width:13%;">'.$row['entry_by'].'</td>
                        <td style="width:13%;">'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                    </tr>';
                }
                $html.='
                </tbody>
            </table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuotationReport.pdf', 'I');
    }
}else {
    echo "Invalid Token";
}
?>