<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);

$_GET["emp_id"] = "";
$_GET["department"] = "";


if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);


    if($_GET["type"]=="generateDispatchPDF") {
        
        $regd_address = 'R.O:A-1201, Mondeal, Nr. Wide Angle, S.G. Highway, Ahmedabad';
        $regd_state = 'Gujarat';
        $regd_phone = '(+91)-(79)-49030903';
        $regd_website = 'www.finecurepharma.com';
        
        $address = "Plot No 25, Avas Vikas, Rudrapur -- 263153";
        $state = "Uttarakhand";
        $state_code = "05";
        
        $dl_no1 = "20B-OBW-9/USN/FEB/2008";
        $dl_no2 = "21B-BW-9/USN/FEB/2008";
        $gstin = "05AAACF9157A1ZZ";
        $pan = "AAACF9157A";
        $iec = "0806018437";
        $cin = "U24230GJ2005PLC45724";
        
        
        $BUYER = "PROFORMA INVOICE 303, THIRD EYE ONE";
        $CITY = "AHMEDABAD - 38006";
        $STATE = "Gujarat";
        
        $PHONENO = "";
        $BUYER_DLNO1 = "";
        $BUYER_DLNO2 = "";
        $BUYER_GSTIN = "";
        $BUYER_PAN = "";
        $STATE_CODE = 24;
        
        $CONSIGNEE = "PROFORMA INVOICE";
        
        $SO_NO = "1010031680";
        $SO_DATE = "04.09.2019";
        $PO_NO = "PROFORMA INVOICE";
        $PO_DATE = "04.06.2019";
        
        $TAX_TYPE = "IGST";
        $INCO_TERMS = "FOB";
        $SALES_TYPE = "Direct Sales Order";
        $PAYT_TERMS = "Immediate Payment";
        
        
        class MYPDF extends TCPDF {
            
            public function Header() {
                $regd_address = 'R.O:A-1201, Mondeal, Nr. Wide Angle, S.G. Highway, Ahmedabad';
                $regd_state = 'Gujarat';
                $regd_phone = '(+91)-(79)-49030903';
                $regd_website = 'www.finecurepharma.com';
                
                $address = "Plot No 25, Avas Vikas, Rudrapur -- 263153";
                $state = "Uttarakhand";
                $state_code = "05";
                
                $dl_no1 = "20B-OBW-9/USN/FEB/2008";
                $dl_no2 = "21B-BW-9/USN/FEB/2008";
                $gstin = "05AAACF9157A1ZZ";
                $pan = "AAACF9157A";
                $iec = "0806018437";
                $cin = "U24230GJ2005PLC45724";
                
                
                $BUYER = "PROFORMA INVOICE 303, THIRD EYE ONE";
                $CITY = "AHMEDABAD - 38006";
                $STATE = "Gujarat";
                
                $PHONENO = "";
                $BUYER_DLNO1 = "";
                $BUYER_DLNO2 = "";
                $BUYER_GSTIN = "";
                $BUYER_PAN = "";
                $STATE_CODE = 24;
                
                $CONSIGNEE = "PROFORMA INVOICE";
                
                $SO_NO = "1010031680";
                $SO_DATE = "04.09.2019";
                $PO_NO = "PROFORMA INVOICE";
                $PO_DATE = "04.06.2019";
                
                $TAX_TYPE = "IGST";
                $INCO_TERMS = "FOB";
                $SALES_TYPE = "Direct Sales Order";
                $PAYT_TERMS = "Immediate Payment";
        
        
                $html = '
                    <table border="1" cellpadding="5">
                        <tr>
                            <td rowspan="2">
                                <img src="header.png" style="height: 35px;"><br>
                                <b>Regd. Office:</b><br>
                                '.$regd_address.'<br>
                                State: '.$regd_state.'<br>
                                Phone: '.$regd_phone.'<br>
                                Website: '.$regd_website.'
                            </td>
                            <td style="text-align:center;"><h1>SALES ORDER</h1></td>
                            <td rowspan="2">
                                <table>
                                    <tbody style="width: 100%;">
                                        <tr>
                                            <td style="width: 20%;">DL NO.</td>
                                            <td style="width: 10%;">:</td>
                                            <td style="width: 70%;">'.$dl_no1.'</td>
                                        </tr>
                                        
                                        <tr>
                                            <td style="width: 20%;">DL NO.</td>
                                            <td style="width: 10%;">:</td>
                                            <td style="width: 70%;">'.$dl_no2.'</td>
                                        </tr>
                                        
                                        <tr>
                                            <td style="width: 20%;">GSTIN</td>
                                            <td style="width: 10%;">:</td>
                                            <td style="width: 70%;">'.$gstin.'</td>
                                        </tr>
                                        
                                        <tr>
                                            <td style="width: 20%;">PAN</td>
                                            <td style="width: 10%;">:</td>
                                            <td style="width: 70%;">'.$pan.'</td>
                                        </tr>
                                        
                                        <tr>
                                            <td style="width: 20%;">IEC</td>
                                            <td style="width: 10%;">:</td>
                                            <td style="width: 70%;">'.$iec.'</td>
                                        </tr>
                                        
                                        <tr>
                                            <td style="width: 20%;">CIN</td>
                                            <td style="width: 10%;">:</td>
                                            <td style="width: 70%;">'.$cin.'</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td><b>SUPPLYING LOCATION: <br><br>'.$address.'<br>State:'.$state.' &nbsp;&nbsp;&nbsp;State Code: '.$state_code.'
                                </b>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table>
                                    <tr>
                                        <td><b>BUYER:</b><br>'.$BUYER.'<br><br>City: '.$CITY.'<br><br>State: '.$STATE.'
                                        </td>
                                        <td>
                                            <table>
                                                <tr>
                                                    <td>Ph No</td>
                                                    <td>:</td>
                                                    <td>'.$PHONENO.'</td>
                                                </tr>
                                                <tr>
                                                    <td>DL No</td>
                                                    <td>:</td>
                                                    <td>'.$BUYER_DLNO1.'</td>
                                                </tr>
                                                <tr>
                                                    <td>DL No</td>
                                                    <td>:</td>
                                                    <td>'.$BUYER_DLNO2.'</td>
                                                </tr>
                                                <tr>
                                                    <td>GSTIN</td>
                                                    <td>:</td>
                                                    <td>'.$BUYER_GSTIN.'</td>
                                                </tr>
                                                <tr>
                                                    <td>PAN</td>
                                                    <td>:</td>
                                                    <td>'.$BUYER_PAN.'</td>
                                                </tr>
                                                <tr><td></td></tr>
                                                <tr>
                                                    <td>State Code</td>
                                                    <td>:</td>
                                                    <td>'.$STATE_CODE.'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            
                            </td>
                            <td>
                                <b>CONSIGNEE:</b><br>
                                '.$CONSIGNEE.'
                            </td>
                            <td>
                                <table>
                                    <tr>
                                    <td>
                                        <table>
                                        
                
                                            <tr>
                                                <td style="width: 30%;">SO No.</td>
                                                <td style="width: 10%;">:</td>
                                                <td style="width: 60%;">'.$SO_NO.'</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;">SO Date</td>
                                                <td style="width: 10%;">:</td>
                                                <td style="width: 60%;">'.$SO_DATE.'</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;">PO No.</td>
                                                <td style="width: 10%;">:</td>
                                                <td style="width: 60%;">'.$PO_NO.'</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;">PO Date</td>
                                                <td style="width: 10%;">:</td>
                                                <td style="width: 60%;">'.$PO_DATE.'</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table>
                                            <tr>
                                                <td style="width: 40%;">Tax Type</td>
                                                <td style="width: 5%;">:</td>
                                                <td style="width: 55%;">'.$TAX_TYPE.'</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 40%;">Inco Terms</td>
                                                <td style="width: 5%;">:</td>
                                                <td style="width: 55%;">'.$INCO_TERMS.'</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 40%;">Sales Type</td>
                                                <td style="width: 5%;">:</td>
                                                <td style="width: 55%;">'.$SALES_TYPE.'</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 40%;">Pymt Terms</td>
                                                <td style="width: 5%;">:</td>
                                                <td style="width: 55%;">'.$PAYT_TERMS.'</td>
                                            </tr>
                                        </table>
                                    </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                ';
                
                $this->SetFont('times', '', 8);
                $this->SetY(5);
                $this->SetX(15);
                $this->MultiCell(0, 50, $html, 1, '', 0, 0, '', '', true, 0, true, true, 40, 'T');
            
                
            }
            public function Footer() {
                
                $html = '
                    <table border="1" cellpadding="3">
                      <tr>
                        <th style="width: 5%; text-align: center;" rowspan="2">TAX TYPE</th>
                        <th style="width: 15%; text-align: center;" colspan="2">TAX ON TAXABLE GOODS</th>
                        <th style="width: 15%; text-align: center;" colspan="2">TAX ON FREE GOODS</th>
                        <th style="width: 15%; text-align: center;" colspan="2">TAX ON INCIDENTAL CHGS</th>
                        <th style="width: 6.90%; text-align: center;" rowspan="2">Total Tax</th>
                        <th style="width: 19%; text-align: left;" rowspan="5">WHETHER TAX IS PAYABLE ON REVERSE CHARGE BASIS: NO</th>
                        <td style="width: 15%; text-align: center;" rowspan="6"></td>
                        <td rowspan="6"></td>
                      </tr>
                      <tr>
                      	<th style="text-align: center;">Value</th>
                      	<th style="text-align: center;">Tax</th>
                        <th style="text-align: center;">Value</th>
                      	<th style="text-align: center;">Tax</th>
                        <th style="text-align: center;">Value</th>
                      	<th style="text-align: center;">Tax</th>
                      </tr>
                      <tr>
                        <th>CGST</th>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                      </tr>
                      <tr>
                        <th>SGST</th>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                      </tr>
                      <tr>
                        <th>IGST</th>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                      </tr>
                      <tr>
                        <td colspan="9">NOTE:</td>
                      </tr>
                      <tr>
                        <td colspan="9"></td>
                        <td>SO VALUE</td>
                        <td></td>
                      </tr>
                      <tr>
                      <td style="text-align: right;" colspan="11">For, Fineure Pharmaceuticals Ltd.<br><br>Authrised Signatory.
                      </td>
                      </tr>
                      
                    </table>
                ';
                $this->SetFont('times', '', 8);
                $this->SetY(-55);
                
                $this->MultiCell(0, 0, $html, 1, '', 0, 0, '', '', true, 0, true, true, 40, 'T');
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' OF '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }
        }
                
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 65, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(55);
        
        
        $pdf->SetAutoPageBreak(TRUE, 55);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->SetFont('times', '', 8);
        $pdf->AddPage('L', 'A4');
        
        $sql = "SELECT * FROM sales_order WHERE status='active'";
        $result = $conn->query($sql);
        
        if ($result-> num_rows > 0) {
            $html='
            <table border="1" style="width: 100%;">
            <tbody>
                <tr style="text-align: center; background-color: #ddd;">
                    <th style="width: 3%;" rowspan="2"><b>Sr</b></th>
                    <th style="width: 4%;" rowspan="2"><b>HSN Code</b></th>
                    <th style="width: 16.33%;" rowspan="2"><b>Description</b></th>
                    <th style="width: 10%;" rowspan="2"><b>New/Old Material</b></th>
                    <th style="width: 10%;" rowspan="2"><b>Total Qty.</b></th>
                    <th style="width: 3.5%;" rowspan="2"><b>Rate</b></th>
                    <th style="width: 5%;" rowspan="2"><b>Value</b></th>
                    <th style="width: 3.5%;" rowspan="2"><b>Disc<br>%</b></th>
                    <th rowspan="2"><b>Discount</b></th>
                    <th rowspan="2"><b>Taxable Value</b></th>
                    <th style="width: 9%;" colspan="2"><b>SGST</b></th>
                    <th style="width: 9%;" colspan="2"><b>CGST</b></th>
                    <th style="width: 9%;" colspan="2"><b>IGST</b></th>
                    <th rowspan="2"><b>Total Value</b></th>
                </tr>
                <tr style="text-align: center; background-color: #ddd;">
                    <th style="width: 3.5%;"><b>Rate</b></th>
                    <th style="width: 5.5%;"><b>Amount</b></th>
                    <th style="width: 3.5%;"><b>Rate</b></th>
                    <th style="width: 5.5%;"><b>Amount</b></th>
                    <th style="width: 3.5%;"><b>Rate</b></th>
                    <th style="width: 5.5%;"><b>Amount</b></th>
                </tr>
                ';
                
            while($row = $result-> fetch_assoc()) {
                
                $html.='
                    <tr nobr="true">
                        <td style="text-align: center;">'.$row["id"].'</td>
                        <td>'.$row["hsn_code"].'</td>
                        <td>'.$row["description"].'</td>
                        <td>'.$row["new_material"].'</td>
                        <td>'.$row["total_qty"].'</td>
                        <td style="text-align: right;">'.$row["rate"].'</td>
                        <td style="text-align: right;">'.$row["value"].'</td>
                        <td style="text-align: right;">'.$row["disc_per"].'</td>
                        <td style="text-align: right;">'.$row["discount"].'</td>
                        <td style="text-align: right;">'.$row["taxable"].'</td>
                        <td style="text-align: right;">'.$row["sgst_rate"].'</td>
                        <td style="text-align: right;">'.$row["sgst_amount"].'</td>
                        <td style="text-align: right;">'.$row["cgst_rate"].'</td>
                        <td style="text-align: right;">'.$row["cgst_amount"].'</td>
                        <td style="text-align: right;">'.$row["igst_rate"].'</td>
                        <td style="text-align: right;">'.$row["igst_amount"].'</td>
                        <td style="text-align: right;">'.$row["total_value"].'</td>
                    </tr>
                    ';
            }

            $html.='
                </tbody>
            </table>
            ';
        }
        
        $pdf->writeHTML($html, true, false, true, false, '');
        //$pdf->MultiCell(0, 0, $html, 1, '', 0, 0, '', '', true, 0, true, true, 40, 'T');
        $file = 'sdf.pdf';
        $pdf->Output($file, 'I');
    }
    else if($_GET['type'] == 'dispatchReport'){
        $datalist = '[
            {"product":"Product 1","workorder_no":"1","type":"Type 1","batch_no":"BATCH01","batch_size":"10","entry_date":"2020/05/03","completion_date":"2020/10/03","dispatch_date":"2020/10/03","self_life":"Self Life","yield_percent":"90 %","transport":"Transport 1","country":""},
            {"product":"Product 2","workorder_no":"2","type":"Type 2","batch_no":"BATCH02","batch_size":"10","entry_date":"2020/05/03","completion_date":"2020/10/03","dispatch_date":"2020/10/03","self_life":"Self Life","yield_percent":"90 %","transport":"Transport 2","country":""}
          ]';
        $_GET['filename'] = 'Dispatch Report'; $_GET['pdftype'] = 'headfoot'; include("../pdfimp.php");
    
        $html.='
	    <table cellpadding="5">
	        <tr style="background-color:#DDDAD9;font-weight:bold;">
	            <td style="width:5%;">Sr.</td>
	            <td style="width:12%;">Date</td>
	            <td style="width:13%;">Work Order No.</td>
	            <td style="width:15%;">Type</td>
	            <td style="width:15%;">Product</td>
	            <td style="width:15%;">Batch No.</td>
	            <td style="width:15%;">Dispatch Date</td>
	            <td style="width:10%;">Transport</td>
	        </tr>';
            for($i=0; $i < json_encode($datalist); $i++) {
                $row= json_encode($datalist)[$i];
                $html.='
    	        <tr>
    	            <td>'.$counter++.'</td>
    	            <td>'.$row['capa_no'].'</td>
    	            <td>'.$row['category'].'</td>
    	            <td>'.$row['document_no'].'</td>
    	            <td>'.$row['required_for'].'</td>
    	            <td>'.$row['request_by'].'</td>
    	            <td>'.$row['status'].'</td>
    	        </tr>';
            }
        $html.='</table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();

?>