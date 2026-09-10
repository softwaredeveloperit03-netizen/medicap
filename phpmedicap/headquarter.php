<?php
    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
    
//      ini_set('display_errors', 1);
// error_reporting(E_ALL);
    
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

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
    
    
    
    if ($_GET["type"] == "downloadRawPurchaseOrders") {
        $_GET['filename'] = 'Raw Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
        </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EquipmentUsagesLog.pdf', 'I');
    }
     else if ($_GET["type"] == "getGlasswarePurchaseOrders") {
        $_GET['filename'] = 'Glassware Material selectedResult Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>';
        
         $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Glassware Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
         $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:10%;">'.$row['po_no'].'</td>
            <td style="width:15%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['discount'].'</td>
            <td style="width:15%;">'.$row['gross_total'].'</td>
            <td style="width:10%;">'.$row['gst_total'].'</td>
            <td style="width:15%;">'.$row['net_total'].'</td>
            <td style="width:15%;">'.$row['entry_by'].'</td>
        </tr>';
         $i++;
       
            }
        }
        $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Purchase Order Log.pdf', 'I');
     }
     
     else if ($_GET["type"] == "getChemicalPurchaseOrders") {
        $_GET['filename'] = 'Chemical Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
        </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Purchase Order Log.pdf', 'I');
         
     }
     else if ($_GET["type"] == "getChemicalPurchaseOrders") {
        $_GET['filename'] = 'Packing Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>';
        
         $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Chemical Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
         $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:10%;">'.$row['po_no'].'</td>
            <td style="width:15%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['discount'].'</td>
            <td style="width:15%;">'.$row['gross_total'].'</td>
            <td style="width:10%;">'.$row['gst_total'].'</td>
            <td style="width:15%;">'.$row['net_total'].'</td>
            <td style="width:15%;">'.$row['entry_by'].'</td>
        </tr>';
         $i++;
       
            }
        }
        $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Purchase Order Log.pdf', 'I');
     }
    else if ($_GET["type"] == "getGeneralPurchaseOrders") {
        $_GET['filename'] = 'General Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
         </tr>';
        
         $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='General Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
         $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:10%;">'.$row['po_no'].'</td>
            <td style="width:15%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['discount'].'</td>
            <td style="width:15%;">'.$row['gross_total'].'</td>
            <td style="width:10%;">'.$row['gst_total'].'</td>
            <td style="width:15%;">'.$row['net_total'].'</td>
            <td style="width:15%;">'.$row['entry_by'].'</td>
        </tr>';
         $i++;
       
            }
        }
        $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Purchase Order Log.pdf', 'I');
    }
    else if ($_GET["type"] == "getPackingPurchaseOrders") {
       $_GET['filename'] = 'Packing Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>';
        
         $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Packing Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
         $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:10%;">'.$row['po_no'].'</td>
            <td style="width:15%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['discount'].'</td>
            <td style="width:15%;">'.$row['gross_total'].'</td>
            <td style="width:10%;">'.$row['gst_total'].'</td>
            <td style="width:15%;">'.$row['net_total'].'</td>
            <td style="width:15%;">'.$row['entry_by'].'</td>
        </tr>';
         $i++;
       
            }
        }
        $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Purchase Order Log.pdf', 'I');
        }
        else if ($_GET["type"] == "getRawPurchaseOrders") {
        $_GET['filename'] = 'Raw Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>';
        
         $sql="SELECT DISTINCT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         po_type='Raw Material' ORDER BY p.id DESC";
        $result = $conn->query($sql);
         $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:10%;">'.$row['po_no'].'</td>
            <td style="width:15%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['discount'].'</td>
            <td style="width:15%;">'.$row['gross_total'].'</td>
            <td style="width:10%;">'.$row['gst_total'].'</td>
            <td style="width:15%;">'.$row['net_total'].'</td>
            <td style="width:15%;">'.$row['entry_by'].'</td>
        </tr>';
         $i++;
       
            }
        }
        $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw Material Purchase Order Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadPackingPurchaseOrders") {
        $_GET['filename'] = 'Packing Material Purchase Order Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Po No</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Discount</b></td>
            <td style="width:15%; text-align:centre;"><b>Gross Total</b></td>
            <td style="width:10%; text-align:centre;"><b>Tax Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Net Total</b></td>
            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
        </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Purchase Order Log.pdf', 'I');
    }else if ($_GET["type"] == "downloadGlasswarePurchaseOrders") {
        $_GET['filename'] = 'Glassware Material selectedResult Purchase Order Log'; $_GET['pdftype'] = 'landscape'; include("pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Sr.</td>
                        <td style="width:10%;">Po No</td>
                        <td style="width:10%;">Vendor Name</td>
                        <td style="width:10%;">Discount</td>
                        <td style="width:15%;">Gross Total</td>
                        <td style="width:15%;">Tax Total</td>
                        <td style="width:15%;">Net Total</td>
                        <td style="width:15%;">Prepared By</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadGlasswarePurchaseOrders.pdf', 'I');
    }else if ($_GET["type"] == "downloadChemicalPurchaseOrders") {
        $_GET['filename'] = 'Chemical Material Purchase Order Log'; $_GET['pdftype'] = 'landscape'; include("pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Sr.</td>
                        <td style="width:10%;">Po No</td>
                        <td style="width:10%;">Vendor Name</td>
                        <td style="width:10%;">Discount</td>
                        <td style="width:15%;">Gross Total</td>
                        <td style="width:15%;">Tax Total</td>
                        <td style="width:15%;">Net Total</td>
                        <td style="width:15%;">Prepared By</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadChemicalPurchaseOrders.pdf', 'I');
    }
}

$conn->close();
?>