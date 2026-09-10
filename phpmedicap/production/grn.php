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
    if($_GET["type"]=="grn") {
         $_GET['filename'] = 'GOODS RECEIVED NOTE';
                include("../pdf1/pdfimp2.php");
                class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'header';
                    include("../pdf1/pdfimp2.php");
                }
                public function Footer() { }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(13, 70, 13, 15);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->AddPage('P', 'A4');
            $pdf->SetY(60);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html='
                <table cellpadding="1">
                    <tr>
                        <td><b>Material Name :</b></td>
                        <td>VIAL - 7.5ML CLEAR GLASS USP TYPE - I-USP</td>
                        <td></td>
                        <td></td>
                        <td><b>Location : </b></td>
                        <td>BPL </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><b>Company : </b></td>
                        <td>BPL </td>
                    </tr>
                    <tr>
                        <td><b>Material Code : </b></td>
                        <td>PMG00158</td>
                        <td></td>
                        <td></td>
                        <td><b>Inward Type : </b></td>
                        <td>Purchase </td>
                    </tr>
                    <tr>
                        <td><b>Supplier Name :</b></td>
                        <td>SGD PHARMA INDIA LIMITED</td>
                        <td></td>
                        <td></td>
                        <td><b>G.R.N. No.  : </b></td>
                        <td>PBPL20213169</td>
                    </tr>
                    <tr>
                        <td><b>Supplier Code :</b></td>
                        <td>SGD.001</td>
                        <td></td>
                        <td></td>
                        <td><b>Date of Rec  : </b></td>
                        <td>13-03-2021</td>
                    </tr>
                    <tr>
                        <td><b>Challan No :</b></td>
                        <td>2020-21/JD/0907</td>
                        <td><b>Chl. Date</b></td>
                        <td>11-03-2021</td>
                        <td><b>Truck No.  : </b></td>
                        <td>GJ36T5201</td>
                    </tr>
                    <tr>
                        <td><b>L.R. No.  :</b></td>
                        <td>716145</td>
                        <td><b>L.R. Date</b></td>
                        <td>11-03-2021</td>
                        <td> </td>
                        <td> </td>
                    </tr>
                    <tr>
                        <td><b>Transport  :</b></td>
                        <td>PRIVATE VEHICLE/PARTY VEHICLE</td>
                        <td><b></b></td>
                        <td></td>
                        <td> <b>Inward No : </b></td>
                        <td>: BBTBPPM2004861 </td>
                    </tr>
                    <tr>
                        <td><b>Excise G.P. No.  :</b></td>
                        <td>2020-21/JD/0907</td>
                        <td><b>Excise G.P. Date:</b></td>
                        <td>11-03-2021</td>
                        <td> <b>Gate Inw. No: </b></td>
                        <td>G-IN20214819 </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td> <b>Vendor COA Rec : </b></td>
                        <td>Yes</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><u><b>Material Status </b></u></td>
                        <td><b>Mfg. By :</b> </td>
                        <td> SGD PHARMA INDIA LIMITED</td>
                        <td> </td>
                    </tr>
                    <tr>
                        <td><b> Challan Qty: </b></td>
                        <td>3,00,160.000   NOS</td>
                        <td></td>
                        <td> </td>
                        <td> </td>
                        <td> </td>
                    </tr>
                    <tr>
                        <td><b>+ Excess Qty : </b></td>
                        <td> </td>
                        <td><b>Packing : </b></td>
                        <td> 335 X 896 NOS</td>
                        <td> </td>
                        <td> </td>
                    </tr>
                    <tr>
                        <td><b>- Short Qty. : </b></td>
                        <td> </td>
                        <td><b>COA Received: </b></td>
                        <td> YES</td>
                        <td> <b> Container : </b></td>
                        <td> 335 NOS</td>
                    </tr>
                    <tr>
                        <td><b>- Damage Qty : </b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>= Total Qty : </b></td>
                        <td>3,00,160.000 NOS</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Stock Qty. : </b></td>
                        <td>3,00,160.000 NOS</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Sampled By : </b></td>
                        <td>JAYESH PARVATSINH CHAVAD</td>
                        <td><b> <u>Q.C. Details</u></b></td>
                        <td></td>
                        <td><b>Sampled Qty :  </b></td>
                        <td>100.000 NOS</td>
                    </tr>
                    <tr>
                        <td><b>A.R.No. : </b></td>
                        <td>QBBTBPPM2004861</td>
                        <td><b>A.R. Date : </b></td>
                        <td>15-03-2021</td>
                        <td><b>Containers Sampled :  </b></td>
                        <td>335</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><b>Status  :  </b></td>
                        <td>APPROVED</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><b>Approved By  :  </b></td>
                        <td>JAYESH PARVATSINH CHAVAD</td>
                    </tr><br>
                    <tr>
                        <td><b> Qty. Approved : </b></td>
                        <td>3,00,060.000 NOS</td>
                        <td></td>
                        <td></td>
                        <td><b>Partly Qty. Rejected :  </b></td>
                        <td>0.000</td>
                    </tr>
                    <tr>
                        <td><b>Storage Condition : </b></td>
                        <td>Not applicable</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><u><b>Purchase Order Details : </b></u></td>
                        <td></td>
                        <td><b> P.O. Qty : </b></td>
                        <td>11,37,500.000 NOS </td>
                    </tr>
                    <tr>
                        <td><b> P.O. No : </b></td>
                        <td> PGPO19202511,PGPO20210071</td>
                        <td></td>
                        <td></td>
                        <td><b>Pending Qty : </b></td>
                        <td><b>NILL </b></td>
                    </tr>
                    <tr>
                        <td><b>P.O. Date : </b></td>
                        <td>17-03-20,08-04-20</td>
                        <td></td>
                        <td></td>
                        <td><b>Excess Qty : </b></td>
                        <td> 28,002.000 NOS</td>
                    </tr>
                    <tr>
                        <td><b>P.O. Rate : </b></td>
                        <td>1.60</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Other Charges Information : </b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
    
                        <td colspan="6"><b> Please refer original purchase order for other information .</u></td>
                    </tr>
                </table> 
                <table>
                    <tr> 
                     
                        <td colspan="4" align="center"><u><b> Purchase Requisition Details </b></u></td>
                        
                    </tr>
                    <tr>
                        <td><b>Purchase Req. No:</b></td>
                        <td>IPBPL19201232,IPBPL19201234,IPBPL20210003,IPBPL202100</td>
                        <td><b> P.R. Qty. </b></td>
                        <td>11,37,500.000 NOS </td>
                    </tr>
                    <tr>
                        <td><b>Purchase Req. Date:</b></td>
                        <td>2-MAR-20,12-MAR-20,01-APR-20,06-APR-20,06-APR-20,06-AP</td>
                        <td><b>Pending Qty. </b></td>
                        <td>0.000 NOS </td>
                    </tr>
                    
                </table>
                <table>
                    <tr> 
                        <td><b>Bill No. : </b></td>
                        <td> </td>
                        <td><u><b> Billing Details </b></u></td>   
                        <td> </td>
                        <td><b>Bill Rate </b></td>
                        <td> </td>
                    </tr> 
                    <tr> 
                        <td><b>Bill Date : : </b></td>
                        <td></td>
                        <td><b>Due Date  </b></td>   
                        <td></td>
                        <td><b>Cost (NOM) :</b> </td>
                        <td> </td>
                    </tr> 
                </table>
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="2">
                    <tr> 
                        <td><b> HSN Code : </b></td>
                         <td> </td>
                        <td colspan="2"><b> <u>GST Details </u></b></td>
                       
                    </tr>
                    <tr> 
                        <td><b>Purchased From: </b></td>
                        <td> </td>
                        <td> </td>
                        <td> </td>
                    </tr>
                    <tr> 
                        <td><b>GST No. : </b></td>
                        <td> </td>
                        <td> </td>
                        <td> </td>
                    </tr>
                    <tr> 
                        <td><b>State : </b></td>
                        <td> </td>
                        <td> </td>
                        <td> </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td colspan="3"></td>
                    </tr>
                    <tr>
                    <td style="width:30%"><b>Prepared By</b></td>
                    <td style="width:30%"><b> Checked By</b></td>
                    <td style="width:30%"><b>Approved By </b></td>
                    </tr>
                </table>
            ';
            EOD;
            $pdf->writeHTML($html, true, false, false, false);
            $pdf->Output('grn.pdf', 'I'); 
        
}
     else if($_GET["type"]=="purchasevoucher") {
         $_GET['filename'] = 'PURCHASE VOUCHER';
                include("../pdf1/pdfimp2.php");
                class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'header';
                    include("../pdf1/pdfimp2.php");
                }
                public function Footer() { }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(15, 30, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->AddPage('P', 'A4');
            $pdf->SetY(60);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html='
                
                <table cellpadding="2">
                <style>td { border:solid 1px BCBBBA;}</style>
                    <tr>
                        <td  width="40%" rowspan="4"><b>LUCENT DRUGS PRIVATE LIMITED</b><br>
                        1 W - 109 S.V.S.S NIVAS<br>
                        STREET NO 1 , C ZENCH COLONY<br>
                        SANATH NAGAR , HYDRABAD<br>
                        HYDRABAD <br><br>Tel: 91-2667-251680, Fax: 2667-251679<br>
                        <b>State </b> : Telangana, 36<br>
                        </td>
                        <td width="10%"><b> GSTIN : </b> </td>
                        <td width="10%">36AADCL3750A1ZY,Registered</td>
                        <td width="10%"><b> Bill No : </b> </td>
                        <td width="10%">457/2020-21</td>
                        <td width="10%"><b>Voucher No: </b> </td>
                        <td width="10%">RM-2021-1179</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><b>Bill Date:</b></td>
                        <td> 25-02-2021</td>
                        <td><b>Bill Rec. Dt. : </b></td>
                        <td>11-03-2021</td>
                    </tr>
                     <tr>
                        <td><b>Domestic/Import : </b></td>
                        <td> Domestic</td>
                        <td><b>Due Date:</b></td>
                        <td> 26-05-2021</td>
                        <td><b>Bill Type.: </b></td>
                        <td>Bill Type</td>
                    </tr>
                    <tr>
                        <td colspan="2"><b>Debited To : </b></td>
                        <td colspan="4">RAW MATERIAL PURCHASE A/C </td>
                    </tr>
                </table>
                <table>
                    <tr>   
                        <td><b> Sr. </b></td>
                        <td><b>code item name </b> </td>
                        <td><b> P.O. No.</b> </td>
                        <td><b>HSN / SAC </b> </td>
                        <td><b> Batch No./ Pack</b></td>
                        <td><b>Chl. No.GR. No </b> </td>
                        <td><b>Inward No GR. Date </b> </td>
                        <td><b>Challan Qty. UOM Rec.Qty. UOM </b></td>
                        <td><b>Rate INR. </b></td>
                        <td><b>Amount INR</b> </td>
                    </tr>
                    <tr>   
                        <td> 1. </td>
                        <td>RMA000392 TRAMADOL HYDROCHLORIDE BP </td>
                        <td>RGPO20210968 </td>
                        <td> </td>
                        <td>LD/TM/0080121 2922 8X25 </td>
                        <td> 457/2020-21 RBPL20211577 </td>
                        <td>BBTBPRM2002072 02-03-2021 </td>
                        <td> 200.000 KG</td>
                        <td> 1,650.0000 </td>
                        <td> 3,30,000.00</td>
                    </tr>
                    <tr>   
                        <td  colspan="8">  </td>
                        <td> + IGST 18.00 %</td>
                        <td>  59,400.00</td>
                    </tr>
                    <tr>   
                        <td  colspan="8">  </td>
                        <td><b> Total Value :</b> %</td>
                        <td>  3,89,400.00</td>
                    </tr>
                    <tr>   
                        <td> 2. </td>
                        <td>RMA000392 TRAMADOL HYDROCHLORIDE BP </td>
                        <td>RGPO20210968 </td>
                        <td> </td>
                        <td>LD/TM/0090121 2922 12X25 </td>
                        <td> 457/2020-21 RBPL20211577 </td>
                        <td>BBTBPRM2002073 02-03-2021 </td>
                        <td>  300.000 KG</td>
                        <td> 1,650.0000 </td>
                        <td> 4,95,000.00</td>
                    </tr>
                     <tr>   
                        <td  colspan="8">  </td>
                        <td><b> + IGST 18.00 % :</b> %</td>
                        <td>   89,100.00</td>
                    </tr>
                    <tr>   
                        <td  colspan="8">  </td>
                        <td><b> Total Value :</b> %</td>
                        <td>  5,84,100.00</td>
                    </tr>
                    <tr>   
                        <td  colspan="8">  </td>
                        <td> <b> Total Sub Value :</b> %</td>
                        <td>  9,73,500.00</td>
                    </tr>
                   
                    <tr>
                
                        <td  colspan="10"> Rupees : Nine Lakh Seventy-Three Thousand Five Hundred Only Total Value : 9,73,500.00 </td>
                    </tr>
                  
                    <tr>
                    <td  colspan="7">  </td>
                    <td style="width:30%"><b>Prepared By</b></td>
                    <td style="width:30%"><b> Checked By</b></td>
                    <td style="width:40%"><b>Approved By </b></td>
                    </tr>
    
                </table>
                    
            ';
              EOD;
            $pdf->writeHTML($html, true, false, false, false);
            $pdf->Output('grn.pdf', 'I'); 
}
}
    
$conn->close();
?>