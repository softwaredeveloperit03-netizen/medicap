<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    
    $invoice = $_GET['invoice'];
    $invoice_no = $_GET['invoice_no'];
    $company = $input['company'];
    if($invoice == 'quotation'){
        $INVOICE = 'QUOTATION';
        $name = 'Quotation';
        $short = 'QT';
    }
    if($invoice == 'proforma'){
        $INVOICE = 'PROFORMA';
        $name = 'Proforma';
        $short = 'PF';
    }
    if($invoice == 'taxinvoice'){
        $INVOICE = 'TAX INVOICE';
        $name = 'Tax Invoice';
        $short = 'TI';
    }
    

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

        if($_GET["type"]=="printproposal"){
            $sql = "SELECT * FROM quotation WHERE quotation_no='".$_GET["id"]."'";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                $date = date("d M Y", strtotime($row["date"]));
            
                $sql1 = "SELECT * FROM client WHERE client_no='".$row["client_no"]."'";
                $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        if($row['company'] == "GMP"){
                            class MYPDF extends TCPDF {
                                public function Header() {
                                    $this->Image('@'.file_get_contents('../assets/gmp.png'),15,6,50,18);
                                    $this->SetY(10); $this->SetX(38);
                                    $this->Cell(0, 15, 'GMP Software Pvt Ltd', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(15); $this->SetX(38);
                                    $this->Cell(0, 25, 'www.gmpsoftware.in | info@gmpsoftware.in', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(20); $this->SetX(38);
                                    $this->Cell(0, 15, '+91 7875351001 / 7711000550', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(25);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(25.2);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(26);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                }
                                public function Footer() {
                                    $this->SetY(-12);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(-8);
                                    $this->SetFont('times', 'B', 10);
                                    $this->Cell(0, 15, '202, Sai Heritage, Lane No 6, Tingare Nagar, Pune - 411015', 0, false, 'C', 0, '', 0, false, 'M', 'M');
                                        $this->SetY(-13);
                                        $this->SetFont('times', 'B', 10);
                                        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                                    }
                            }
                        }
                        if($row['company'] == "CPPL"){
                            class MYPDF extends TCPDF {
                                public function Header() {
                                    $this->Image('@'.file_get_contents('../assets/cyclone.png'),15,6,0,13);
                                    $this->SetY(10); $this->SetX(38);
                                    $this->Cell(0, 15, 'Cyclone Pharmaceuticals Pvt. Ltd', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(15); $this->SetX(38);
                                    $this->Cell(0, 15, '202, Sai Heritage, Lane No 6, Tingare Nagar, Pune', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(20);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(20.2);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(21);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                }
                            }
                        }
                        if($row['company'] == "Factory"){
                            class MYPDF extends TCPDF {
                                public function Header() {
                                    $this->Image('@'.file_get_contents('../assets/cyclone.png'),15,6,0,13);
                                    $this->SetY(10); $this->SetX(38);
                                    $this->Cell(0, 15, 'Cyclone Pharmaceuticals Pvt. Ltd', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(15); $this->SetX(38);
                                    $this->Cell(0, 15, '202, Sai Heritage, Lane No 6, Tingare Nagar, Pune', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                                    $this->SetY(20);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(20.2);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                    $this->SetY(21);
                                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                                }
                            }
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetTitle('QUOTATION');
                        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                            require_once(dirname(__FILE__).'/lang/eng.php');
                            $pdf->setLanguageArray($l);
                        }
                        $pdf->AddPage();
                        $pdf->SetFont('Times', '', 11);
                        $pdf->SetY(30);
                $html1 ='
                <h1 style="text-align:center; color:#FF0000;">QUOTATION</h1>
                <table  border="0" cellpadding="6" cellspacing="0"  style="border:solid 1px black; width:100%;  text-align:left; ">
                <tr style="background-color:#B0C4DE;">
                    <td colspan="2" border="1"><b><lable>CLIENT DETAILS</lable></b></td>
                </tr>
                <tr>
                    <td style="width:18%;"><b>Company Name</b></td>
                    <td style="width:43%;"><b>: '.$row1["company"].'</b></td>
                    <td style="width:15%;"><b>Date</b></td>
                    <td style="width:24%;"><b>: '.$date.'</b></td>
                </tr>
                <tr>
                    <td><b>Customer Name</b></td>
                    <td><b>: '.$row1["person"].'</b></td>
                    <td><b>Quotation No</b></td>
                    <td><b>: '.$row["quotation_no"].'</b></td>
                </tr>
                <tr>
                    <td><b>Contact No</b></td>
                    <td><b>: '.$row1["contact1"].'</b></td>
                    <td><b>GST No.</b></td>
                    <td><b>: '.$row1["gst_no"].'</b></td>
                </tr>
                <tr>
                    <td><b>Email Id</b></td>
                    <td><b>: '.$row1["email"].'</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5" style="width:100%; text-align:center;">';
            if ($row1["gst_type"] == 'IGST') {
                $html1.='
                <tr style="background-color:#B0C4DE;">
                    <td border="1" style="width:10%;"><b>HSN / SAC</b></td>
                    <td border="1" style="width:36%;"><b>Description</b></td>
                    <td border="1" style="width:12%;"><b>Rate</b></td>
                    <td border="1" style="width:10%;"><b>Qty</b></td>
                    <td border="1" style="width:12%;"><b>Taxable Amount</b></td>
                    <td border="1" style="width:8%;"><b>IGST (%)</b></td>
                    <td border="1" style="width:12%;"><b>IGST Amount</b></td>
                </tr>';
            }
            if ($row1["gst_type"] == 'GST') {
                $html1.='
                <tr style="background-color:#B0C4DE;">
                    <td border="1" rowspan="2" style="width:8%;"><b>HSN / SAC</b></td>
                    <td border="1" rowspan="2" style="width:29%;"><b>Description</b></td>
                    <td border="1" rowspan="2" style="width:12%;"><b>Rate</b></td>
                    <td border="1" rowspan="2" style="width:6%;"><b>Qty</b></td>
                    <td border="1" rowspan="2" style="width:12%;"><b>Taxable Amount</b></td>
                    <td border="1" colspan="2" style="width:22%;"><b>GST (%)</b></td>
                    <td border="1" rowspan="2" style="width:11%;"><b>GST Amount</b></td>
                </tr>
                <tr style="background-color:#B0C4DE;">
                    <td border="1" style="width:11%">CGST</td>
                    <td border="1" style="width:11%">SGST</td>
                </tr>';
            }
                $sql2 = "SELECT * FROM inv_quotation_services WHERE quotation_no='".$_GET["id"]."'";
                $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
                    while($row2 = $result2->fetch_assoc()){
                    $cgst = $row['gst_percent']/2;
                    $sgst = $row['gst_percent']/2;
                    $cgst_amt = $row2['total'] * ($cgst/100);
                    $sgst_amt = $row2['total'] * $sgst/100;
                    $igst_amount = $row2['total'] * $row['gst_percent']/100;
                    if ($row1["gst_type"] === 'IGST') {
                        $html1.='
                        <tr>
                            <td border="1" style="text-align:center;">'.$row2["hsn"].'</td>
                            <td border="1" style="text-align:left;">'.$row2["description"].'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $row2["rate"], 2, '.', '').'</td>
                            <td border="1" style="text-align:center;">'.$row2["qty"].'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $row2["total"], 2, '.', '').'</td>
                            <td border="1" style="text-align:center;">'.$row['gst_percent'].'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $igst_amount, 2, '.', '').'</td>
                        </tr>';
                    }
                    if ($row1["gst_type"] === 'GST') {
                        $html1.='
                        <tr>
                            <td border="1" style="text-align:center;">'.$row2["hsn"].'</td>
                            <td border="1" style="text-align:left;">'.$row2["description"].'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $row2["rate"], 2, '.', '').'</td>
                            <td border="1" style="text-align:center;">'.$row2["qty"].'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $row2["total"], 2, '.', '').'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $cgst_amt, 2, '.', '').'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $sgst_amt, 2, '.', '').'</td>
                            <td border="1" style="text-align:center;">'.number_format((float) $row['gst_amount'], 2, '.', '').' </td>
                        </tr>';
                    }
                    }
                }
                $html1.='
                <tr>
                    <td border="1"></td>
                    <td border="1"></td>
                    <td border="1"></td>
                    <td border="1"></td>
                    <td border="1"></td>
                    <td border="1"></td>
                    <td border="1"></td>
                    <td border="1"></td>
                </tr>
                <tr style="text-align:right;">
                    <td border="1" style="width:82%;"><b>Taxable Total Amount</b></td>
                    <td border="1" style="width:18%;">'.number_format((float) $row["total_amount"], 2, '.', '').'</td>
                </tr>
                <tr style="text-align:right;">
                    <td border="1"><b>Discount Total</b></td>
                    <td border="1">'.number_format((float) $row["disc_amount"], 2, '.', '').'</td>
                </tr>
               <tr style="text-align:right;">
                    <td border="1"><b>GST Total</b></td>
                    <td border="1">'.number_format((float) $row["gst_amount"], 2, '.', '').'</td>
                </tr>
                <tr nobr="true" style="text-align:right;">
                    <td border="1" style="text-align:right;"><b>Net Payble Amount</b></td>
                    <td border="1">'.number_format((float) $row["net_amount"], 2, '.', '').'</td>
                </tr>';
                $number = $row["net_amount"];$no = round($number);$point = round($number - $no, 2) * 100;$hundred = null;$digits_1 = strlen($no);$i = 0;$str = array();$words = array('0'=>'','1'=>'One','2'=>'Two','3'=>'Three','4'=>'Four','5'=>'Five','6'=>'Six','7'=>'Seven','8'=>'Eight','9'=>'Nine','10'=>'Ten','11'=>'Eleven','12'=>'Twelve','13'=>'Thirteen','14'=>'Fourteen','15'=>'Fifteen','16'=>'sixteen','17'=>'Seventeen','18' =>'Eighteen','19'=>'Nineteen','20'=>'Twenty','30'=>'Thirty','40'=>'Forty','50'=>'Fifty','60'=>'Sixty','70'=>'Seventy','80'=>'Eighty','90'=>'Ninety');$digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');while ($i < $digits_1) {$divider = ($i == 2) ? 10 : 100; $number = floor($no % $divider);$no = floor($no / $divider);$i += ($divider == 10) ? 1 : 2;if ($number) {$plural = (($counter = count($str)) && $number > 9) ? 's' : null;$hundred = ($counter == 1 && $str[0]) ? ' and ' : null;$str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " ". $digits[$counter] . $plural . " " . $hundred;} else $str[] = null;}$str = array_reverse($str);$result = implode('', $str);$points = ($point) ? "." . $words[$point / 10] . " " . $words[$point = $point % 10] : '';
                $html1.='
                <tr>
                    <td border="1" style="width:44%; text-align:right;"><b>In Words</b></td>
                    <td border="1" style="width:56%; text-align:right;">'.$result.'</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5" style="border: 1px solid #000; text-align:left; ">
                <tr style="background-color:#B0C4DE;">
                    <td colspan="2" border="1"><b><lable>TERMS AND CONDITIONS</lable></b></td>
                </tr>'; 
                $terms = "";
                $sqlterm = "SELECT * FROM inv_quotation_terms WHERE quotation_no='".$_GET["id"]."'";
                $resultterm = $conn->query($sqlterm);
                if($resultterm->num_rows > 0){
                    $counter = 0;
                while($rowterm = $resultterm->fetch_assoc()){
                    $id = $rowterm["id"];
                    $terms = $rowterm["terms"];
                    $html1.='
                <tr>
                    <td style="width:3%;">'.++$counter.'</td>
                    <td style="width:97%;"><label> '.$terms.' </label></td>
                </tr>';
                }
                }
                $html1.='
            </table>
            <div></div>
            <table nobr="true" cellpadding="5" style="border: 1px solid #000 ; text-align:center; ">
                <tr style="background-color:#B0C4DE;">
                    <td border="1" colspan="8" style="width:100%; text-align:left;"><b><lable>PAYMENT TERMS</lable></b></td>
                </tr>
                <tr>
                  <td border="1">Amount</td>
                  <td border="1">Advance (%)</td>
                  <td border="1">Advance Amount</td>
                  <td border="1">Installment (%)</td>
                  <td border="1">Installment Amount</td>
                  <td border="1">Balance (%)</td>
                  <td border="1">Balance Amount</td>
                  <td border="1">No of Installment</td>
                </tr>
                <tr>
                  <td border="1">'.number_format((float) $row['net_amount'], 2, '.', '').'</td>
                  <td border="1">'.$row['adv_percent'].'</td>
                  <td border="1"> '.number_format((float) $row['advance_amount'], 2, '.', '').' </td>
                  <td border="1">'.$row['instpercent'].'</td>
                  <td border="1">'.number_format((float) $row['instamount'], 2, '.', '').'</td>
                  <td border="1">'.$row['bal_percent'].'</td>
                  <td border="1">'.number_format((float) $row['balance_amount'], 2, '.', '').'</td>
                  <td border="1">'.$row["instmonth"].'</td>
                </tr>
            </table> 
            <div></div>
                <table nobr="true" cellpadding="5" style="border: 1px solid #000 ; text-align:center; ">
                    <tr style="background-color:#B0C4DE;">
                        <td border="1" colspan="8" style="width:100%; text-align:left;"><b><lable>PAYMENT INSTALLMENT DETAILS</lable></b></td>
                    </tr>
                    <tr>
                        <td border="1" style="width:6%;">Sr. No</td>
                        <td border="1" style="width:40%;"><b>Installment Detail</b></td>
                        <td border="1" style="width:9%;"><b>Percent (%)</b></td>
                        <td border="1" style="width:12%;"><b>Amount</b></td>
                        <td border="1" style="width:9%;"><b>GST (%)</b></td>
                        <td border="1" style="width:12%;"><b>GST Amount</b></td>
                        <td border="1" style="width:12%;"><b>Total Amount</b></td>
                      </tr>
                      <tr>
                        <td border="1">1</td>
                        <td border="1" style="text-align:left;">'.$row['adv_detail'].'</td>
                        <td border="1">'.$row['adv_percent'].'</td>
                        <td border="1">'.number_format((float) $row['adv_amount'], 2, '.', '').'</td>
                        <td border="1">'.$row['gst_percent'].'</td>
                        <td border="1">'.number_format((float) $row['adv_gst_amount'], 2, '.', '').'</td>
                        <td border="1">'.number_format((float) $row['adv_total'], 2, '.', '').'</td>
                      </tr>'; 
                        $sqlinstall = "SELECT * inv_FROM quotation_install WHERE quotation_no='".$_GET["id"]."'";
                        $resultinstall = $conn->query($sqlinstall);
                        if($resultinstall->num_rows > 0){
                        $counter1 = 1;
                        while($rowinstall = $resultinstall->fetch_assoc()){
                            $html1.='
                        <tr>
                            <td border="1">'.++$counter1.'</td>
                            <td border="1" style="text-align:left;">'.$rowinstall['details'].'</td>
                            <td border="1">'.$rowinstall['percentage'].'</td>
                            <td border="1">'.number_format((float) $rowinstall['amount'], 2, '.', '').'</td>
                            <td border="1">'.$row['gst_percent'].'</td>
                            <td border="1">'.number_format((float) $rowinstall['gst_amount'], 2, '.', '').'</td>
                            <td border="1">'.number_format((float) $rowinstall['final_amount'], 2, '.', '').'</td>
                        </tr>';
                        }
                        }
                    $html1.='
                      <tr>
                        <td>';if(++$counter1 == 1){$counter1 = 1;} else {--$counter1;}$html1.=''.++$counter1.'</td>
                        <td border="1" style="text-align:left;">'.$row['bal_detail'].'</td>
                        <td border="1">'.$row['bal_percent'].'</td>
                        <td border="1">'.number_format((float) $row['bal_amount'], 2, '.', '').'</td>
                        <td border="1">'.$row['gst_percent'].'</td>
                        <td border="1">'.number_format((float) $row['bal_gst_amount'], 2, '.', '').'</td>
                        <td border="1">'.number_format((float) $row['bal_total'], 2, '.', '').'</td>
                      </tr>
                </table>
            <div></div>
            <table nobr="true" cellpadding="5" style="border: 1px solid #000 ; text-align:left; ">
                <tr>
                    <td><b><label>Accepted By</label></b></td>
                    <td></td>
                </tr>
                <br><br>
                <tr>
                  <td>Authorised Signatory</td>
                  <td style="text-align:right;">Authorised Signatory &nbsp; &nbsp;</td>
                </tr>
                <tr>
                    <td><b>'.$row1['company'].'</b></td>
                    <td style="text-align:right;"><b>';
                    if($row['company'] == 'GMP'){
                        $html1.='GMP Software Pvt Ltd';
                    } if($row['company'] == 'CPPL'){
                        $html1.='Cyclone Pharmaceutical Pvt Ltd';
                    } if($row['company'] == 'Factory'){
                        $html1.='Cyclone Pharmaceutical Pvt Ltd';
                    }
                    $html1.='
                    </b></td> 
                </tr>
            </table>';
        EOD;
        $pdf->writeHTML($html1, true, false, false, false, '');
        $pdf->Output($quotation_no.'.pdf', 'I');
        } } } } 
        }
        else if($_GET["type"]=="printinvoice"){
            $sql = "SELECT * FROM ac_$invoice WHERE invoice_no='".$_GET["invoice_no"]."'";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $date = date("d M Y", strtotime($row["entry_date"]));
                    
                    $row['disc_amount'] = number_format((float) ($row['total_amount']*$row['disc_percent']/100), 2, '.', '');
        		    $row['final_amount'] = number_format((float) ($row['total_amount']- $row['disc_amount']), 2, '.', '');
        		    $row['gst_amount'] = number_format((float) ($row['final_amount']*$row['gst_percent']/100), 2, '.', '');
        		    $row['net_amount'] = number_format((float) ($row['final_amount']*1+$row['gst_amount']), 2, '.', '');
        		    $row['advance_amount'] = number_format((float) $row['net_amount']*$row['advance_percent']/100, 2, '.', '');
        		    $row['balance_amount'] = number_format((float) $row['net_amount']*$row['balance_percent']/100, 2, '.', '');
                
                    $sql1 = "SELECT * FROM clientcompany WHERE client_code='".$row["client_code"]."'";
                    $result1 = $conn->query($sql1);
                    if($result1->num_rows > 0){
                        while($row1 = $result1->fetch_assoc()){
                            require 'header.php';
                            $html.='
                            <h1 style="text-align:center; color:#FF0000;">'.$INVOICE.'</h1>
                            <table  border="0" cellpadding="6" cellspacing="0"  style="border:solid 1px black; width:100%;  text-align:left; ">
                                <tr style="background-color:#B0C4DE;">
                                    <td colspan="2" border="1"><b><lable>CLIENT DETAILS</lable></b></td>
                                </tr>
                                <tr>
                                    <td style="width:18%;"><b>Company Name</b></td>
                                    <td style="width:43%;"><b>'.$row1["company_name"].'</b></td>
                                    <td style="width:15%;"><b>Date</b></td>
                                    <td style="width:24%;"><b>'.$date.'</b></td>
                                </tr>
                                <tr>
                                    <td><b>Customer Name</b></td>
                                    <td><b>'.$row1["contact_person"].'</b></td>
                                    <td><b>Quotation No</b></td>
                                    <td><b>'.$row["invoice_no"].'</b></td>
                                </tr>
                                <tr>
                                    <td><b>Contact No</b></td>
                                    <td><b>'.$row1["phone_no"].'</b></td>
                                    <td><b>GST No.</b></td>
                                    <td><b>'.$row1["gst_no"].'</b></td>
                                </tr>
                                <tr>
                                    <td><b>Email Id</b></td>
                                    <td><b>'.$row1["email"].'</b></td>
                                </tr>
                            </table>
                            <div></div>
                            <table cellpadding="5" style="width:100%; text-align:center;">';
                            if ($row1["gst_type"] == 'IGST') {
                                $html.='
                                <tr style="background-color:#B0C4DE;">
                                    <td border="1" style="width:10%;"><b>HSN / SAC</b></td>
                                    <td border="1" style="width:36%;"><b>Description</b></td>
                                    <td border="1" style="width:12%;"><b>Rate</b></td>
                                    <td border="1" style="width:10%;"><b>Qty</b></td>
                                    <td border="1" style="width:12%;"><b>Taxable Amount</b></td>
                                    <td border="1" style="width:8%;"><b>IGST (%)</b></td>
                                    <td border="1" style="width:12%;"><b>IGST Amount</b></td>
                                </tr>';
                            }
                            if ($row1["gst_type"] == 'GST') {
                                $html.='
                                <tr style="background-color:#B0C4DE;">
                                    <td border="1" rowspan="2" style="width:8%;"><b>HSN / SAC</b></td>
                                    <td border="1" rowspan="2" style="width:29%;"><b>Description</b></td>
                                    <td border="1" rowspan="2" style="width:12%;"><b>Rate</b></td>
                                    <td border="1" rowspan="2" style="width:6%;"><b>Qty</b></td>
                                    <td border="1" rowspan="2" style="width:12%;"><b>Taxable Amount</b></td>
                                    <td border="1" colspan="2" style="width:22%;"><b>GST (%)</b></td>
                                    <td border="1" rowspan="2" style="width:11%;"><b>GST Amount</b></td>
                                </tr>
                                <tr style="background-color:#B0C4DE;">
                                    <td border="1" style="width:11%">CGST</td>
                                    <td border="1" style="width:11%">SGST</td>
                                </tr>';
                            }
                            $sql2 = "SELECT * FROM ac_inv_services WHERE invoice_no='".$_GET["invoice_no"]."'";
                            $result2 = $conn->query($sql2);
                            while($row2 = $result2->fetch_assoc()){
                                $row2['unit_total'] = $row2['rate'] * $row2['quantity'];
                                $cgst = $row['gst_percent']/2;
                                $sgst = $row['gst_percent']/2;
                                $cgst_amt = $row2['unit_total'] * ($cgst/100);
                                $sgst_amt = $row2['unit_total'] * $sgst/100;
                                $igst_amount = $row2['unit_total'] * $row['gst_percent']/100;
                                if ($row1["gst_type"] === 'IGST') {
                                    $html.='
                                    <tr>
                                        <td border="1" style="text-align:center;">'.$row2["hsn_sac"].'</td>
                                        <td border="1" style="text-align:left;">'.$row2["description"].'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $row2["rate"], 2, '.', '').'</td>
                                        <td border="1" style="text-align:center;">'.$row2["quantity"].'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $row2["unit_total"], 2, '.', '').'</td>
                                        <td border="1" style="text-align:center;">'.$row['gst_percent'].'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $igst_amount, 2, '.', '').'</td>
                                    </tr>';
                                }
                                if ($row1["gst_type"] === 'GST') {
                                    $html.='
                                    <tr>
                                        <td border="1" style="text-align:center;">'.$row2["hsn_sac"].'</td>
                                        <td border="1" style="text-align:left;">'.$row2["description"].'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $row2["rate"], 2, '.', '').'</td>
                                        <td border="1" style="text-align:center;">'.$row2["quantity"].'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $row2["unit_total"], 2, '.', '').'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $cgst_amt, 2, '.', '').'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $sgst_amt, 2, '.', '').'</td>
                                        <td border="1" style="text-align:center;">'.number_format((float) $row['gst_amount'], 2, '.', '').' </td>
                                    </tr>';
                                }
                            }
                            $html.='
                                <tr>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                    <td border="1"></td>
                                </tr>
                                <tr style="text-align:right;">
                                    <td border="1" style="width:82%;"><b>Taxable Total Amount</b></td>
                                    <td border="1" style="width:18%;">'.number_format((float) $row["total_amount"], 2, '.', '').'</td>
                                </tr>
                                <tr style="text-align:right;">
                                    <td border="1"><b>Discount Total</b></td>
                                    <td border="1">'.number_format((float) $row["disc_amount"], 2, '.', '').'</td>
                                </tr>
                                <tr style="text-align:right;">
                                    <td border="1"><b>GST Total</b></td>
                                    <td border="1">'.number_format((float) $row["gst_amount"], 2, '.', '').'</td>
                                </tr>
                                <tr nobr="true" style="text-align:right;">
                                    <td border="1" style="text-align:right;"><b>Net Payble Amount</b></td>
                                    <td border="1">'.number_format((float) $row["net_amount"], 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" style="width:44%; text-align:right;"><b>In Words</b></td>
                                    <td border="1" style="width:56%; text-align:right;">'.$result.'</td>
                                </tr>
                            </table>
                            <div></div>'; 
                            if($invoice != taxinvoice)
                            $sqlterm = "SELECT * FROM ac_inv_terms WHERE invoice_no='".$_GET["invoice_no"]."'";
                            $resultterm = $conn->query($sqlterm);
                            if($resultterm->num_rows > 0){
                                $counter = 0;
                                $html.'
                            <table cellpadding="5" style="border: 1px solid #000; text-align:left; ">
                                <tr style="background-color:#B0C4DE;">
                                    <td colspan="2" border="1"><b><lable>TERMS AND CONDITIONS</lable></b></td>
                                </tr>';
                            while($rowterm = $resultterm->fetch_assoc()){
                                $html.='
                                <tr>
                                    <td style="width:3%;">'.++$counter.'</td>
                                    <td style="width:97%;"><label> '.$rowterm['term_detail'].' </label></td>
                                </tr>';
                            }
                            $html.='
                            </table>
                            <div></div>';
                            }
                            $html.='
                            <table nobr="true" cellpadding="5" style="border: 1px solid #000 ; text-align:center; ">
                                <tr style="background-color:#B0C4DE;">
                                    <td border="1" colspan="8" style="width:100%; text-align:left;"><b><lable>PAYMENT TERMS</lable></b></td>
                                </tr>
                                <tr>
                                  <td border="1">Amount</td>
                                  <td border="1">Advance (%)</td>
                                  <td border="1">Advance Amount</td>
                                  <td border="1">Installment (%)</td>
                                  <td border="1">Installment Amount</td>
                                  <td border="1">Balance (%)</td>
                                  <td border="1">Balance Amount</td>
                                  <td border="1">No of Installment</td>
                                </tr>
                                <tr>
                                  <td border="1">'.number_format((float) $row['net_amount'], 2, '.', '').'</td>
                                  <td border="1">'.$row['advance_percent'].'</td>
                                  <td border="1"> '.number_format((float) $row['advance_amount'], 2, '.', '').' </td>
                                  <td border="1">'.$row['installment_percent'].'</td>
                                  <td border="1">'.number_format((float) $row['installment_amount'], 2, '.', '').'</td>
                                  <td border="1">'.$row['balance_percent'].'</td>
                                  <td border="1">'.number_format((float) $row['balance_amount'], 2, '.', '').'</td>
                                  <td border="1">'.$row["installment_month"].'</td>
                                </tr>
                            </table> 
                            <div></div>
                            <table nobr="true" cellpadding="5" style="border: 1px solid #000 ; text-align:center; ">
                                <tr style="background-color:#B0C4DE;">
                                    <td colspan="8" style="width:100%; text-align:left;"><b><lable>PAYMENT INSTALLMENT DETAILS</lable></b></td>
                                </tr>
                                <tr>
                                    <td style="width:6%;">Sr. No</td>
                                    <td style="width:40%;"><b>Installment Detail</b></td>
                                    <td style="width:9%;"><b>Percent (%)</b></td>
                                    <td style="width:12%;"><b>Amount</b></td>
                                    <td style="width:9%;"><b>GST (%)</b></td>
                                    <td style="width:12%;"><b>GST Amount</b></td>
                                    <td style="width:12%;"><b>Total Amount</b></td>
                                  </tr>
                                  <tr>
                                    <td>1</td>
                                    <td style="text-align:left;">'.$row['adv_detail'].'</td>
                                    <td>'.$row['advance_percent'].'</td>
                                    <td>'.number_format((float) $row['advance_amount'], 2, '.', '').'</td>
                                    <td>'.$row['gst_percent'].'</td>
                                    <td>'.number_format((float) $row['adv_gst_amount'], 2, '.', '').'</td>
                                    <td>'.number_format((float) $row['adv_total'], 2, '.', '').'</td>
                                  </tr>'; 
                                    $sqlinstall = "SELECT * FROM ac_inv_installment WHERE invoice_no='".$_GET["invoice_no"]."'";
                                    $resultinstall = $conn->query($sqlinstall);
                                    if($resultinstall->num_rows > 0){
                                        $counter1 = 1;
                                        while($rowinstall = $resultinstall->fetch_assoc()){
                                        $html.='
                                        <tr>
                                            <td>'.++$counter1.'</td>
                                            <td style="text-align:left;">'.$rowinstall['details'].'</td>
                                            <td>'.$rowinstall['percentage'].'</td>
                                            <td>'.number_format((float) $rowinstall['amount'], 2, '.', '').'</td>
                                            <td>'.$row['gst_percent'].'</td>
                                            <td>'.number_format((float) $rowinstall['gst_amount'], 2, '.', '').'</td>
                                            <td>'.number_format((float) $rowinstall['final_amount'], 2, '.', '').'</td>
                                        </tr>';
                                        }
                                    }
                                    $html.='
                                  <tr>
                                    <td>';if(++$counter1 == 1){$counter1 = 1;} else {--$counter1;}$html.=''.++$counter1.'</td>
                                    <td style="text-align:left;">'.$row['bal_detail'].'</td>
                                    <td>'.$row['balance_percent'].'</td>
                                    <td>'.number_format((float) $row['bal_amount'], 2, '.', '').'</td>
                                    <td>'.$row['gst_percent'].'</td>
                                    <td>'.number_format((float) $row['bal_gst_amount'], 2, '.', '').'</td>
                                    <td>'.number_format((float) $row['bal_total'], 2, '.', '').'</td>
                                  </tr>
                            </table>
                            <div></div>
                            <table nobr="true" cellpadding="5" style="border: 1px solid #000 ; text-align:left; ">
                                <tr>
                                    <td style="border:none;"><b><label>Accepted By</label></b></td>
                                    <td style="border:none;"></td>
                                </tr>
                                <br><br>
                                <tr>
                                  <td style="border:none;">Authorised Signatory</td>
                                  <td style="border:none; text-align:right;">Authorised Signatory &nbsp; &nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="border:none;"><b>'.$row1['company_name'].'</b></td>
                                    <td style="border:none; text-align:right;"><b>GMP Software Pvt Ltd</b></td> 
                                </tr>
                            </table>';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $pdf->Output($quotation_no.'.pdf', 'I');
                        } 
                    } 
                } 
            }else{
                echo "Invoice No. Not Found";
            }
        }
        else if($_GET["type"]=="printvoucher"){
            $sql = "SELECT * FROM ac_voucher WHERE voucher='".$_GET["number"]."'";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $voucher =$row["voucher"];
                $company = $row["company"];
                $originalDate =$row["date"];
                $payto =$row["payto"];
                $total_amount =$row["amount"];
            }
            };
            $originalDate;
            $newDate = date("d-m-Y", strtotime($originalDate));
        
        
           class MYPDF extends TCPDF {
                public function Header() {
              
                    $image_file = 'assets/logo.png';
                    $this->Image($image_file, 15, 8, 45, '15', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                    $tDate = date("F j, Y, g:i a");
                    $this->SetFont('helvetica', '', 10);
                    $this->SetY(10);
                    $this->SetX(120);
                    $this->Cell(0, 15, 'GMP Software Pvt Ltd ', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    $this->SetY(15);
                    $this->SetX(120);
                    $this->Cell(0, 15, '202, Sai Heritage, lane No 6, AirPort Road,', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    $this->SetY(20);
                    $this->SetX(120);
                    $this->Cell(0, 15, 'Tingare Nagar, Vishrantwadi, Pune- 411015', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    $this->SetY(25);
                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 100, '', 'T', 100, 'L');
                }
                public function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                }
            } 
        
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        
        $pdf->AddPage();
        $pdf->SetY(30);
        $pdf->SetFont ('helvetica', '', '10' , '', 'default', true );
        $html1 = '
        
        <table cellpadding="5" style="border: 1px solid #b0e0e6 ; text-align:left; width:100%;">
          <tr>
            <td style="width:15%;"><b>Voucher No :</b></td>
            <td style="width:65%;"><b><label>'.$voucher.'</label></b></td>
            <td style="width:20%;"><b>Date : </b><label>'.$newDate.'</label></td>
          </tr>
          <tr>
            <td><b>Pay To : </b></td>
            <td colspan="2"><b><label>'.$payto.'</label></b></td>
          </tr>
        </table>
        
        <div></div>
        <table cellpadding="5" style="border: 1px solid #b0e0e6 ; text-align:left; ">
            <tr style="background-color:#b0e0e6;">
                <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                <td  border="1" style="width:50%; text-align:center;"><b>Particulars</b></td>
                <td  border="1" style="width:20%; text-align:center;"><b>Type</b></td>
                <td border="1" style="width:20%; text-align:center;"><b>Amount</b></td>
            </tr>';
            $sql = "SELECT * FROM voucher_detail WHERE voucher='".$_GET["number"]."'";
            $result = $conn->query($sql);
            $counter = 0;
            if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $particular =$row["detail"];
                $type =$row["type"];
                $amount =$row["amount"];
            $html.='
            <tr>
                <td border="1"><label>'.++$counter.'</label></td>
                <td border="1"><label>'.$particular.'</label></td>
                <td border="1" style="text-align:center;"><label>'.$type.'</label></td>
                <td border="1" style="text-align:center;"><label>'.number_format((float) $amount, 2, '.', '').' &#47; &#45;</label></td>
            </tr>';
             }
            }
                $html.='
                <tr>
                    <td border="1" colspan="3" style="text-align:right;"><label><b>Total Amount</b></label></td>
                    <td border="1" style="text-align:center;"><label><b>'.number_format((float) $total_amount, 2, '.', '').' &#47; &#45;</b></label></td>
                </tr>';
                $number = $total_amount;
                $no = round($number);
                $point = round($number - $no, 2) * 100;
                $hundred = null;
                $digits_1 = strlen($no);
                $i = 0;
                $str = array();
                $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
           $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
           while ($i < $digits_1) {
             $divider = ($i == 2) ? 10 : 100;
             $number = floor($no % $divider);
             $no = floor($no / $divider);
             $i += ($divider == 10) ? 1 : 2;
             if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number] .
                    " " . $digits[$counter] . $plural . " " . $hundred
                    :
                    $words[floor($number / 10) * 10]
                    . " " . $words[$number % 10] . " "
                    . $digits[$counter] . $plural . " " . $hundred;
             } else $str[] = null;
          }
          $str = array_reverse($str);
          $result = implode('', $str);
          $points = ($point) ?
            "." . $words[$point / 10] . " " . 
                  $words[$point = $point % 10] : '';
                  
                $html.='
                <tr>
                    <td border="1">In Words</td>
                    <td border="1"colspan="3">'.$result.' Only</td>
                </tr>
            </table> ';
            $html1.='
        
            <table cellpadding="5" style="border: 1px solid #000000 ; text-align:left; width:100%;">
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="width: 5%;"></td>
                    <td style="width: 25%;">Received Above Sum of Rs.</td>
                    <td style="width: 20%; border: 1px solid #000000;">'.number_format((float) $total_amount, 2, '.', '').' &#47; &#45;</td>
                    <td style="width: 20%; text-align:right;">Sign</td>
                    <td style="width: 20%; border: 1px solid #000000;"></td>
                    <td style="width: 5%;"></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
        ';
        
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('voucher'.$_GET['number'].'.pdf', 'I');
        }
        else if($_GET["type"]=="printreceipt"){
            $sql = "SELECT * FROM ac_receipt WHERE receipt_no='".$_GET["no"]."'";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $voucher =$row["voucher"];
                $company = $row["company"];
                $originalDate =$row["date"];
                $payto =$row["payto"];
                $total_amount =$row["amount"];
            }
            };
            $originalDate;
            $newDate = date("d-m-Y", strtotime($originalDate));
        
        
           class MYPDF extends TCPDF {
                public function Header() {
              
                    $image_file = 'assets/logo.png';
                    $this->Image($image_file, 15, 8, 45, '15', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                    $tDate = date("F j, Y, g:i a");
                    $this->SetFont('helvetica', '', 10);
                    $this->SetY(10);
                    $this->SetX(120);
                    $this->Cell(0, 15, 'GMP Software Pvt Ltd ', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    $this->SetY(15);
                    $this->SetX(120);
                    $this->Cell(0, 15, '202, Sai Heritage, lane No 6, AirPort Road,', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    $this->SetY(20);
                    $this->SetX(120);
                    $this->Cell(0, 15, 'Tingare Nagar, Vishrantwadi, Pune- 411015', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    $this->SetY(25);
                    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 100, '', 'T', 100, 'L');
                }
                public function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                }
            } 
        
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setPrintFooter(false);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
        
            $pdf->AddPage();
            $pdf->SetY(30);
            $pdf->SetFont ('helvetica', '', '10' , '', 'default', true );
            $html= '
        
            <table cellpadding="5" style="border: 1px solid #b0e0e6 ; text-align:left; width:100%;">
              <tr>
                <td style="width:15%;"><b>Voucher No :</b></td>
                <td style="width:65%;"><b><label>'.$voucher.'</label></b></td>
                <td style="width:20%;"><b>Date : </b><label>'.$newDate.'</label></td>
              </tr>
              <tr>
                <td><b>Pay To : </b></td>
                <td colspan="2"><b><label>'.$payto.'</label></b></td>
              </tr>
            </table>
        
            <div></div>
            <table cellpadding="5" style="border: 1px solid #b0e0e6 ; text-align:left; ">
                <tr style="background-color:#b0e0e6;">
                    <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                    <td  border="1" style="width:50%; text-align:center;"><b>Particulars</b></td>
                    <td  border="1" style="width:20%; text-align:center;"><b>Type</b></td>
                    <td border="1" style="width:20%; text-align:center;"><b>Amount</b></td>
                </tr>';
                $sql = "SELECT * FROM voucher_detail WHERE voucher='".$_GET["number"]."'";
                $result = $conn->query($sql);
                $counter = 0;
                if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $particular =$row["detail"];
                    $type =$row["type"];
                    $amount =$row["amount"];
                $html1.='
                <tr>
                    <td border="1"><label>'.++$counter.'</label></td>
                    <td border="1"><label>'.$particular.'</label></td>
                    <td border="1" style="text-align:center;"><label>'.$type.'</label></td>
                    <td border="1" style="text-align:center;"><label>'.number_format((float) $amount, 2, '.', '').' &#47; &#45;</label></td>
                </tr>';
                 }
                }
                    $html1.='
                    <tr>
                        <td border="1" colspan="3" style="text-align:right;"><label><b>Total Amount</b></label></td>
                        <td border="1" style="text-align:center;"><label><b>'.number_format((float) $total_amount, 2, '.', '').' &#47; &#45;</b></label></td>
                    </tr>';
                    $number = $total_amount;
                    $no = round($number);
                    $point = round($number - $no, 2) * 100;
                    $hundred = null;
                    $digits_1 = strlen($no);
                    $i = 0;
                    $str = array();
                    $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
               $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
               while ($i < $digits_1) {
                 $divider = ($i == 2) ? 10 : 100;
                 $number = floor($no % $divider);
                 $no = floor($no / $divider);
                 $i += ($divider == 10) ? 1 : 2;
                 if ($number) {
                    $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                    $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                    $str [] = ($number < 21) ? $words[$number] .
                        " " . $digits[$counter] . $plural . " " . $hundred
                        :
                        $words[floor($number / 10) * 10]
                        . " " . $words[$number % 10] . " "
                        . $digits[$counter] . $plural . " " . $hundred;
                 } else $str[] = null;
              }
              $str = array_reverse($str);
              $result = implode('', $str);
              $points = ($point) ?
                "." . $words[$point / 10] . " " . 
                      $words[$point = $point % 10] : '';
                      
                    $html1.='
                    <tr>
                        <td border="1">In Words</td>
                        <td border="1"colspan="3">'.$result.' Only</td>
                    </tr>
                </table> ';
                $html1.='
            
                <table cellpadding="5" style="border: 1px solid #000000 ; text-align:left; width:100%;">
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="width: 5%;"></td>
                        <td style="width: 25%;">Received Above Sum of Rs.</td>
                        <td style="width: 20%; border: 1px solid #000000;">'.number_format((float) $total_amount, 2, '.', '').' &#47; &#45;</td>
                        <td style="width: 20%; text-align:right;">Sign</td>
                        <td style="width: 20%; border: 1px solid #000000;"></td>
                        <td style="width: 5%;"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            ';
            require 'footer.php';
        
        EOD;
        $pdf->writeHTML($html1, true, false, false, false, '');
        $pdf->Output('voucher'.$_GET['number'].'.pdf', 'I');
        }
        else{
            echo "not Found";
        }
} else {
    echo "{\"status\":\"invalid\"}";
}

?>