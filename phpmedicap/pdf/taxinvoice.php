<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "downloadTaxInvoice") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";
        $sql = "SELECT t.*, c.company,c.address,c.gst_no,c.dl_no,c.phone,c.email FROM tax_invoice t LEFT JOIN client c ON t.client_code=c.client_code WHERE t.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM tax_invoice_product WHERE order_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        // $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        // $result2 = $conn->query($sql2);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {

                        $html.='<h3 style="text-align:center;">Tax Invoice</h3>
                                <table border="1" cellpadding="3">
                                    <tr>
                                        <td rowspan="3" style="width:60%;">
                                            <table>
                                                <tr>
                                                    <td style="width:100%;"><b>To<br>'.$row['company'].'</b><br>'.$row['address'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;"><b>Phone:</b>'.$row['phone'].', <b>Email:</b>'.$row['email'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width:10%; font-weight:bold;">Inv. No.</td>
                                        <td style="width:10%;">:'.$row['invoice_no'].'</td>
                                        <td style="width:10%;font-weight:bold;">Inv. Date</td>
                                        <td style="width:10%;">:'.$row['entry_date'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="width:10%; font-weight:bold;">DC. No.</td>
                                        <td style="width:10%;">:</td>
                                        <td style="width:10%;font-weight:bold;">DC. Date</td>
                                        <td style="width:10%;">:</td>
                                    </tr>
                                    <tr>
                                        <td style="width:10%; font-weight:bold;">Ref. No.</td>
                                        <td style="width:30%;">:</td>
                                    </tr>
                                    <tr>
                                        <td style="width:60%;"><b>GST No:</b>'.$row['gst_no'].'</td>
                                        <td style="width:10%;font-weight:bold;">ERP Order No </td>
                                        <td style="width:10%;">:</td>
                                        <td style="width:10%;font-weight:bold;">Order Date:</td>
                                        <td style="width:10%;">:</td>
                                    </tr>
                                    <tr>
                                        <td style="width:60%;"><b>CST No:</b></td>
                                        <td style="width:10%;font-weight:bold;">R.C.No. </td>
                                        <td style="width:30%;">:</td>
                                    </tr>
                                    <tr>
                                        <td style="width:60%;"><b>D.L.No:</b>'.$row['dl_no'].'</td>
                                        <td style="width:10%;font-weight:bold;">Direct</td>
                                        <td style="width:30%;">:</td>
                                    </tr>
                                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                                        <td style="width:3%;">Sr</td>
                                        <td style="width:6%;">Product Name</td>
                                        <td style="width:5%;">Scheme</td>
                                        <td style="width:4%;">HSN No</td>
                                        <td style="width:6%;">Packing</td>
                                        <td style="width:5%;">Batch No</td>
                                        <td style="width:8%;">Mfg. Dt</td>
                                        <td style="width:8%;">Exp. Dt.</td>
                                        <td style="width:6%;">Quantity</td>
                                        <td style="width:4%;">Rate (Rs.)</td>
                                        <td style="width:4%;">P.T.R. (Rs.)</td>
                                        <td style="width:5%;">M.R.P. (Rs.)</td>
                                        <td style="width:4%;">Amt (Rs.)</td>
                                        <td style="width:6%;">Discount (Rs.)</td>
                                        <td style="width:5%;">Total (Amt - Disc.)</td>
                                        <td style="width:5%;">CGST (Rs.)</td>
                                        <td style="width:5%;">SGST (Rs.)</td>
                                        <td style="width:5%;">IGST (Rs.)</td>
                                        <td style="width:6%;">Total Amt(Rs.)</td>
                                    </tr>';
                                $i=1;
                                $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                            $html.='<tr>
                                        <td style="width:3%;">'.$i++.'</td>
                                        <td style="width:6%;">'.$row2['product_name'].'</td>
                                        <td style="width:5%;"></td>
                                        <td style="width:4%;">'.$row2['hsn'].'</td>
                                        <td style="width:6%;">'.$row2['packing_style'].'</td>
                                        <td style="width:5%;">'.$row1['batch_no'].'</td>
                                        <td style="width:8%;">'.$row1['mfg_date'].'</td>
                                        <td style="width:8%;">'.$row1['exp_date'].'</td>
                                        <td style="width:6%;">'.$row1['qty'].'</td>
                                        <td style="width:4%;">'.$row1['sale_rate'].'</td>
                                        <td style="width:4%;"></td>
                                        <td style="width:5%;">'.$row1['mrp'].'</td>
                                        <td style="width:4%;"></td>
                                        <td style="width:6%;">'.$row1['disc_per'].'</td>
                                        <td style="width:5%;">'.$row1['disc_total'].'</td>
                                        <td style="width:5%;">'.$row1['CgstAmt'].'</td>
                                        <td style="width:5%;">'.$row1['SgstAmt'].'</td>
                                        <td style="width:5%;">'.$row1['IgstAmt'].'</td>
                                        <td style="width:6%;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    }
                                }
                            $html.='<tr>
                                        <td style="width:90%;text-align:right;font-weight:bold;">Basic Amount Total :</td>
                                        <td style="width:10%;">'.$row['net_total'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="width:30%;">
                                            <table>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">CGST 6% ON SALES </td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">SGST 6% ON SALES</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width:40%;">
                                            <table>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">M.T.R.No:</td>
                                                    <td style="width:50%;font-weight:bold;">Date:</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">No of Cases :</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">Transport :</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">Our D.L.No :</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">Our GST No. :</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">FSSAI No :</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">\'C\' From No. :</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">Pan No. :</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width:30%;">
                                            <table>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">Sub Total</td>
                                                    <td style="width:50%;">:</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">- Discount:</td>
                                                    <td style="width:50%;">:'.$row['Discount'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">+ Other Charges</td>
                                                    <td style="width:50%;">:'.$row['OthChrg'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">- C.N</td>
                                                    <td style="width:50%;">:</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">+ Packing & For</td>
                                                    <td style="width:50%;">:</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">- M.T.R.Amt</td>
                                                    <td style="width:50%;">:</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">-/+ RoundOff:</td>
                                                    <td style="width:50%;">:'.$row['RndOffAmt'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;font-weight:bold;">NET AMT:</td>
                                                    <td style="width:50%;">:'.$row['net_total'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>';
                            function getIndianCurrency(float $number)
                            {
                                $decimal = round($number - ($no = floor($number)), 2) * 100;
                                $hundred = null;
                                $digits_length = strlen($no);
                                $i = 0;
                                $str = array();
                                $words = array(0 => '', 1 => 'one', 2 => 'two',
                                    3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
                                    7 => 'seven', 8 => 'eight', 9 => 'nine',
                                    10 => 'ten', 11 => 'eleven', 12 => 'twelve',
                                    13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
                                    16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
                                    19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
                                    40 => 'fourty', 50 => 'fifty', 60 => 'sixty',
                                    70 => 'seventy', 80 => 'eighty', 90 => 'ninety');
                                $digits = array('', 'hundred','thousand','lakh', 'crore');
                                while( $i < $digits_length ) {
                                    $divider = ($i == 2) ? 10 : 100;
                                    $number = floor($no % $divider);
                                    $no = floor($no / $divider);
                                    $i += $divider == 10 ? 1 : 2;
                                    if ($number) {
                                        $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                                        $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                                        $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
                                    } else $str[] = null;
                                }
                                $Rupees = implode('', array_reverse($str));
                                $paise = ($decimal > 0) ? "." . ($words[$decimal] . " " . $words[$decimal % 10]) . ' Paise' : '';
                                return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
                            }
                            $html.='<tr>
                                        <td style="width:100%;font-weight:bold;">Rs.:'.getIndianCurrency($row['net_total']).'</td>
                                    </tr>
                                    <tr>
                                        <td style="width:100%;">
                                            <table>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">General Warrany U/S 19(3) of Drug And Cosmetic Act 1940 : We M/s.WEST COAST PHARMACEUTICAL WORKS LTD , being a resident of India, carrying on business at ahmedabad under the name of M/s.WEST COAST PHARMACEUTICAL WORKS LTD thereby give this warraty that goods specified and contained in this invoice do not contravene in any way the provision of section 18 of the drug & cosmetics Act,1940.</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;font-weight:bold;">Terms:
                                                        <ol>
                                                            <li> Subject to Ahmedabad Jurisdriction.</li>
                                                            <li> Payment by cheque is subject to realization</li>
                                                            <li> Interest @ 12 % per annum would be charges on all account unpaid 21 days after date of despatch</li>
                                                        </ol>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="width:60%;"></td>
                                                    <td style="width:40%;font-weight:bold;text-align:right;"> WEST COAST PHARMACEUTICAL WORKS LTD<br>Authorised  signatory<div></div></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>';
                        //     }
                        // }
        			}
        		}
			}
		}
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('TaxInvoice.pdf', 'I');
    }
}
$conn->close();
?>