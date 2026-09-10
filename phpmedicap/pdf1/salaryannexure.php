<?php
require '../db.php';
require '../token.php';
require_once('../tcpdf/tcpdf.php');
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
    
    if($_GET["type"]=="printsalaryannexure"){
        $sql = "SELECT * FROM salary_annexure WHERE emp_id='".$_GET["emp"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                class MYPDF extends TCPDF {
                    public function Header() {
                        
                    }
                    public function Footer() {
                       $table='
                <table border="1" cellpadding="3">
                    <tr>
                        <td style="width:25%;"></td>
                        <td style="width:25%;font-weight:bold;">HR Incharge</td>
                        <td style="width:25%;font-weight:bold;">Departmental Incharge</td>
                        <td sstyle="width:25%;font-weight:bold;">Candidate Signature</td>
                    </tr>
                </table>';
                $this->SetY(-40);
                $this->SetFont('Times', '', 10);
                $this->writeHTML($table, true, false, false, false, '');
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10,5,10,10);
                $pdf->SetAutoPageBreak(TRUE, 15);
                $pdf->AddPage($_GET['pdfpage']);
                $pdf->SetY(130);
                $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
                $html.="";
                $html='
                <h4 style="text-align:center;">Salary Annexure : </h4>
                <br></br><br>
                <table>
                    <tr>
                        <td style="text-align:right; width:100%;">Date: </td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Name:</td>
                        <td style="width:70%;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%;">Designation:</td>
                        <td style="width:70%;"></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5" style="border: 1px solid #DCDCDC; text-align:left; ">
                    <tr style="background-color:#DCDCDC;">
                        <td border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                        <td border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                        <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                        <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                    </tr>
                    <tr>
                        <td border="1" colspan="4"><b>Earnings :</b></td>
                    </tr>
                    <tr>
                        <td border="1" style="text-align:center;">1</td>
                        <td border="1">Basic</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['basic'], 2, '.', '').'</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['basic']*12, 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" style="text-align:center;">2</td>
                        <td border="1">HRA</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['HRA'], 2, '.', '').'</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['HRA']*12, 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" style="text-align:center;">3</td>
                        <td border="1">Conveyance</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['convence'], 2, '.', '').'</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['annualconvence'], 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1"style="text-align:center;">4</td>
                        <td border="1">Education allowance</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['education'], 2, '.', '').'</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['annualeducation'], 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['salary'], 2, '.', '').'</td>
                        <td border="1" style="text-align:right;">'.number_format((float) $row['annualsalary'], 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td colspan="4" border="1"><b>Deductions :</b></td>
                    </tr>
                    <tr>
                        <td border="1" style="width:10%;">1.</td>
                        <td border="1" style="width:40%;">PT</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['PT'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['PT']*12, 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" style="width:10%;">2.</td>
                        <td border="1" style="width:40%;">PF</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['PF'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['PF']*12, 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" style="width:10%;">3.</td>
                        <td border="1" style="width:40%;">ESIC</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['ESIC'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['ESIC']*12, 2, '.', '').'</td>
                    </tr>
                     <tr>
                        
                        <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                        <td border="1" style="text-align:right;"><b>'.number_format((float) $row['net'], 2, '.', '').'</b></td>
                        <td border="1" style="text-align:right;"><b>'.number_format((float) $row['netannual'], 2, '.', '').'</b></td>
                    </tr>
                    <tr>
                        <td colspan="4" border="1"><b>Company Contribution :</b></td>
                    </tr>
                    <tr>
                        <td border="1" style="width:10%;">1.</td>
                        <td border="1" style="width:40%;">PF</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['PF'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['PF']*12, 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" style="width:10%;">2.</td>
                        <td border="1" style="width:40%;">ESIC</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['c_ESIC'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['c_ESIC']*12, 2, '.', '').'</td>
                    </tr>
                     <tr>
                        
                        <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                        <td border="1" style="text-align:right;"><b>'.number_format((float) $row['total_contribution'], 2, '.', '').'</b></td>
                        <td border="1" style="text-align:right;"><b>'.number_format((float) $row['total_contribution']*12, 2, '.', '').'</b></td>
                    </tr>
                    <tr>
                        <td border="1" colspan="4"></td>
                    </tr>
                    <tr>
                        <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['inhand'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['inhand']*12, 2, '.', '').'</td>
                    </tr>
                    <tr>
                        <td border="1" style="width:50%;">CTC ( A + C )</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['CTC'], 2, '.', '').'</td>
                        <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row['CTC']*12, 2, '.', '').'</td>
                    </tr>
                </table>
                <br><br><br><br>
                <br>
                <table cellpadding="5" style="text-align:left; width:100%;">
                  <tr>
                    <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt. Ltd.</b></td>
                    <td style="width:10%;"></td>
                    <td style="width:60%; text-align:center;">Accepted</td>
                  </tr>
                  <tr>
                    <td style="width:40%; text-align:center;"><b>Ms. Kanchan Rajput</b></td>
                    <td style="width:10%;"></td>
                    <td style="width:60%; text-align:center;">(Signature of an Employee)</td>
                  </tr>
                </table>';
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('OfferLetter.pdf', 'I');
            }
            
        }
    }else if($_GET["type"]=="printsalary"){
        $sql = "SELECT * FROM salary_annexure WHERE emp_id='".$_GET["emp"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                class MYPDF extends TCPDF {
                    public function Header() {
                        $table='
                        <table cellpadding="3">
                            <tr>
                                <td style="width:40%;font-weight:bold;">HRD DEPARTMENT</td>
                                <td style="width:40%;"></td>
                                <td style="width:20%;">'; $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/wastcost.png'),155,7,35);
                                $table.='</td>
                            </tr>
                        </table>';
                        $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                    }
                    public function Footer() {
                       $table='
                        <table cellpadding="3">
                            <tr>
                                <td style="width:33%;font-weight:bold;">HR Incharge</td>
                                <td style="width:34%;font-weight:bold;">Departmental Incharge</td>
                                <td style="width:33%;font-weight:bold;">Candidate Signature</td>
                            </tr>
                        </table>';
                        $this->SetY(-30);
                        $this->SetFont('Times', '', 10);
                        $this->writeHTML($table, true, false, false, false, '');
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10,20,10,10);
                $pdf->SetAutoPageBreak(TRUE, 40);
                $pdf->AddPage($_GET['pdfpage']);
                $pdf->SetY(15);
                $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
                $html.="";
                $html='
                <h1 style="text-align:center;">Annexure II: Salary Sheet </h1>
                <table>
                    <tr>
                        <td style="text-align:left; width:100%;">Dear Sir/Madam, </td>
                    </tr>
                    <div></div>
                    <tr>
                        <td style="width:100%;">Please find your<b> Salary Break-Up and Cost-To-Company </b>as incumbent of position of <b>Senior Executive </b>as under: </td>
                    </tr>
                    <div></div>
                    <tr>
                        <td style="text-align:left; width:50%;">NAME:<b><u></u></b> </td>
                        <td style="text-align:left; width:50%;">DEPARTMENT:<b><u></u></b> </td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:50%;">DESIGNATION:<b><u></u></b> </td>
                        <td style="text-align:left; width:50%;"> TIMING:<u></u></td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:50%;">CTC PER ANNUM:<u></u></td>
                        <td style="text-align:left; width:50%;"> LUNCH TIME:<u></u></td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:50%;">CTC PER MONTH:<b><u></u></b> </td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:50%;"><b>(CTC including Bonus, P.F, C.L) </b></td>
                    </tr>
                    </table>
                <table style="width:100%;  border:1px solid black;">
                        <tr>
                         <th style="border:1px solid black; text-align:center; width:10%; "><b>SR.No.</b></th>
                         <th style="border:1px solid black; text-align:center; width:60%; "><b>DETAILS</b></th>
                         <th style="border:1px solid black; text-align:center; width:30%; "></th>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">BASIC SALARY (including D.A)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:40%; ">Basic</td>
                          <td style="border:1px solid black; text-align:center; width:20%; "></td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:40%; ">HRA</td>
                          <td style="border:1px solid black; text-align:center; width:20%; "></td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:40%; ">Convey</td>
                          <td style="border:1px solid black; text-align:center; width:20%; "></td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:40%; ">Medical </td>
                          <td style="border:1px solid black; text-align:center; width:20%; "></td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:40%; cellspacing:10%; "><b>Total</b></td>
                          <td style="border:1px solid black; text-align:center; width:20%; "></td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Bonus (8.33%) (Paid on Diwali)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Leaves  Salary (Paid on April) (Yr.30 - M2.5)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">EPF/FPF (13.61%) Company contribution (Paid monthly)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%;  "><b>Monthly  Salary </b></td>
                          <td style="border:1px solid black; text-align:center; width:30%; background-color: green; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Mobile Expense Rs.<u></u> (if any)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Retention </td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Prof.Tax.</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">EPF/FPF (12%) Employee contribution (monthly)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Travelling Expenses Local Rs.<u></u> (if any)  </td>
                          <td style="border:1px solid black; text-align:center; width:30%; ">Per Km.</td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; ">Travelling Expenses Outside Ahmedabad Rs.<u></u>(if any)</td>
                          <td style="border:1px solid black; text-align:center; width:30%; ">Per Km.</td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:left; width:60%; "></td>
                          <td style="border:1px solid black; text-align:center; width:30%; "></td>
                        </tr>
                        <tr>
                          <td style="border:1px solid black; text-align:center; width:10%; "></td>
                          <td style="border:1px solid black; text-align:right; width:60%; "><b>TAKE HOME SALARY </b></td>
                          <td style="border:1px solid black; text-align:center; width:30%; background-color: green;"></td>
                        </tr>
                    </table>
                    <div>
                    </div>
                     <h3 style="text-align:left;">General Terms & Conditions: </h3>
                <table>
                    <tr>
                        <td style="text-align:left; width:5%;"> 1.</td>
                        <td style="text-align:left; width:95%;">Above Salary calculation is based on monthly basis. </td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:5%;"> 2.</td>
                        <td style="text-align:left; width:95%;">Your contribution for provident fund if any will be deducted from above salary.	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 3.</td>
                        <td style="text-align:left; width:95%;">Leave Encashment Non utilized Leave if any will be encashed  at the end of the financial year only on basis of your attendance data 	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 4.</td>
                        <td style="text-align:left; width:95%;">If your mobile bill amount is more than sanctioned amount, then excess amount will be deducted from your salary.  	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;">5.</td>
                        <td style="text-align:left; width:95%;">Probation period will be extended if required & it shall depend on your performance. 	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 6.</td>
                        <td style="text-align:left; width:95%;">Company has full rights to make changes in salary structure from time to time or any changes due to government rules & regulation. </td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 7.</td>
                        <td style="text-align:left; width:95%;">O.T. amount inclusive in salary and 7:00pm timing is Compulsory, O.T. calculation (Wherever applicable) after 7:00 pm. </td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 8.</td>
                        <td style="text-align:left; width:95%;">Inclusive in gross salary, Bonus, increment, PF, ESI, CL, Overtime as per company’s policy. 	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 9.</td>
                        <td style="text-align:left; width:95%;">If you leave the company without serving notice period in that case your two month notice salary will be adjusted from your bonus & leave encashment amount. 	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 10.</td>
                        <td style="text-align:left; width:95%;">Leaves during probation period. If member leaves within probation period she / he is not eligible for leave encashment amount	</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 11.</td>
                        <td style="text-align:left; width:95%;">Pending Incentive will not applicable if you resign from your Job.	</td>
                    </tr>
                    </table>
                    <div></div>
                       <h3 style="text-align:left;"><u>ENDORSEMENT OF ACCEPTANCE: </u></h3>
                    <table>
                     <tr>
                        <td style="text-align:left; width:100%;">I have read above salary clarification detail and agree with clarification and I do not have any objection and I am bound for all above term & condition and any changes in further by the Company.</td>
                    </tr>
                    </table>
                    <div></div>
                    <table>
                     <tr>
                        <td style="text-align:left; width:50%;"><b>Signature:__________</b></td>
                        <td style="text-align:left; width:50%;"><b>Date & Place:___________</b></td>
                    </tr>
                    </table>
                    <div></div>
                    <table>
                     <tr>
                        <td style="text-align:left; width:25%;">__________</td>
                        <td style="text-align:left; width:25%;">___________</td>
                        <td style="text-align:left; width:25%;">__________</td>
                        <td style="text-align:left; width:25%;">___________</td>
                    </tr>
                    <div></div>
                     <tr>
                        <td style="text-align:left; width:25%;" >HRD INCHARGE</td>
                        <td style="text-align:left; width:25%;" >C.E.O. HRD HEAD</td>
                        <td style="text-align:left; width:25%;" >C.E.O. PHARMA</td>
                        <td style="text-align:left; width:25%;" >ACCOUNT</td>
                    </tr>
                    </table>
                    <br pagebreak="true"/>
                    <h1 style="text-align:center;"><u>Annexure III: General Terms of Company </u></h1>
                    <table>
                    <tr>
                        <td style="text-align:left; width:100%;">Please read and understand the following terms and conditions carefully. Please sign all pages of the attached sheets as a token of your acceptance of our norms, terms and conditions. Please sign this page as well.</td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:5%;"> 1.</td>
                        <td style="text-align:left; width:95%;"><b>Place/ Transfer:</b>Your present place work will be at Ahmedabad, but during the course of the service, you shall be liable to be posted / transferred anywhere to serve any of the Company’s Projects or any other establishment in India, at the sole discretion of the Management.</td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:5%;"> 2.</td>
                        <td style="text-align:left; width:95%;">Your services can be terminated with one month’s notice or one month’s notice pay on either side.</td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:5%;"> 3.</td>
                        <td style="text-align:left; width:95%;">You will not be entitled to automatic promotions or increment.  Your promotion and increment will depend on the recommendations of your superiors as well as it will be decided by the management at its sole discretion on the basis of merits, performance and other factors.</td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:5%;"> 4.</td>
                        <td style="text-align:left; width:95%;">Absence for a continuous period of three days without prior approval of your superior, (including overstay on leave) would result in your losing your lien on the service and the same shall automatically come to an end without any notice or intimation.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 5.</td>
                        <td style="text-align:left; width:95%;">Your leave, bonus and all other benefits have already been considered in the present package. No additional benefits are going to be considered.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 6.</td>
                        <td style="text-align:left; width:95%;">During the period of your employment with the Company, you will devote full time to the work of the Company. Further, you will not take up any other employment or assignment or any office, honorary or for any consideration, in cash or in kind or otherwise, without the prior written permission of the Company</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 7.</td>
                        <td style="text-align:left; width:95%;">You will not (except in the normal course of the Company’s business) publish any article or statement, deliver any lecture or broadcast or make any communication to the press, including magazine publication relating to the Company’s products or to any matter with which the Company may be concerned, unless you have previously applied to and obtained the written permission from the authorised person of company.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 8.</td>
                        <td style="text-align:left; width:95%;">You shall be true faithful to the company in all your accounts, business dealings and transactions whatsoever and if required, would not hesitate to render a true and just account thereof to the company or to such persons as may be authorized by the Company.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 9.</td>
                        <td style="text-align:left; width:95%;">At the time of reporting for duty you have to produce the following documents:</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> a.</td>
                        <td style="text-align:left; width:90%;">Certificate of Fitness from the Competent Medical Authority viz. Staff Surgeon/ Medical Officer of any recognized District general Hospital. The medical examination fee, if any, will be paid by you and it will not be reimbursed by the Company.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> b.</td>
                        <td style="text-align:left; width:90%;">Dependant’s details in the enclosed format for ‘Nomination of Dependants for medical Benefits on Inpatient Treatment’.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> c.</td>
                        <td style="text-align:left; width:90%;">Passport size photographs duly signed on the front side with name written in block letters on the reverse (6 Nos.).</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> d.</td>
                        <td style="text-align:left; width:90%;">You should produce the following certificates.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:10%;"> </td>
                        <td style="text-align:left; width:90%;"><ul><li>Educational Qualification Certificates</li></ul></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:10%;"> </td>
                        <td style="text-align:left; width:90%;"><ul><li>Birth Certificate </li></ul></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:10%;"> </td>
                        <td style="text-align:left; width:90%;"><ul><li>Experience Certificate </li></ul></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:10%;"> </td>
                        <td style="text-align:left; width:90%;"><ul><li>Last three companies Appointment Letter Copy </li></ul></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:10%;"> </td>
                        <td style="text-align:left; width:90%;"><ul><li>Character and Conduct Certificate from two respectable persons.</li></ul></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:10%;"> </td>
                        <td style="text-align:left; width:90%;"><ul><li>Candidates belonging to Schedule Caste/ Scheduled Tribe/ Other backward Classes should produce a Proper Community Certificate in original from the concerned Revenue Authorities</li></ul></td>
                    </tr>
                    
                    <tr>
                        <td style="text-align:left; width:5%;"> 10.</td>
                        <td style="text-align:left; width:95%;">In case of any change in your residential address you shall intimate us immediately</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 11.</td>
                        <td style="text-align:left; width:95%;">You will be required to maintain utmost secrecy in respect of documents, commercial offer, product formulations and designs, marketing plans, strategic goals, costs & estimation, technology, software packages, licenses and any other information otherwise deemed sensitive and/or important by the Company. </td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 12.</td>
                        <td style="text-align:left; width:95%;">You will be required to comply with all such rules and regulations which the Company may frame from time to time.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 13.</td>
                        <td style="text-align:left; width:95%;">Any of our technical or other important information which might come into your possession during the continuance of your service with us shall not be disclosed, divulged or made public by you even thereafter.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 14.</td>
                        <td style="text-align:left; width:95%;">If at any time in our opinion, which is final in this matter you are found non- performer or guilty of fraud, dishonest, disobedience, disorderly behaviour, negligence, indiscipline, absence from duty without permission or any other conduct considered by us deterrent to our interest or of violation of one or more terms of this letter, your services may be terminated without notice and on account of reason of any of the acts or omission the company shall be entitled to recover the damages from you.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 15.</td>
                        <td style="text-align:left; width:95%;">You will not accept any present, commission or any sort of gratification in cash or kind from any person, party or firm or Company having dealing with the company and if you are offered any, you should immediately report the same to the Management.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 16.</td>
                        <td style="text-align:left; width:95%;">This appointment letter is being issued to you on the basis of the information and particulars furnished by you in your application (including bio-data), at the time of your interview and subsequent discussions. If it transpires that you have made a false statement (or have not disclosed a material fact) resulting in your being offered this appointment, the Management may take such action as it deems fit in its sole discretion, including termination of your employment.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 17.</td>
                        <td style="text-align:left; width:95%;">If you conceive any news or advanced methods of improving processes / formulae / systems in relation to the operation of the Company, such developments will be fully communicated to the Company and will remain the sole right / property of the Company.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 18.</td>
                        <td style="text-align:left; width:95%;">Your retirement age is 60 years unless and until otherwise specified by a special order to you by the Management.</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; width:5%;"> 19.</td>
                        <td style="text-align:left; width:95%;">Your company believes in investing its best in the Human Resources. You being an integral part, your medical and physical fitness are of utmost concern and hence, you may be required to appear before the Medical Officer / Panel as may be specified by the Management from time to time.</td>
                    </tr>
                     <tr>
                        <td style="text-align:left; width:5%;"> 20.</td>
                        <td style="text-align:left; width:95%;">In case of parting or discontinuation of services, under normal circumstances it would be required to give / serve one month notice period.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">21. </td>
                        <td style="text-align:left; width:95%;">In unfortunate circumstances, specified as loss of confidence or breach of commitment or involvement in any act which is inconsistent with the service conditions of your appointment or any practice which violate the interest of the Company and its business credibility, the Management reserves its rights to discontinue your services immediately, without giving any notice or notice pay to that effect.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;">22. </td>
                        <td style="text-align:left; width:95%;">In the event for discontinuation / separation in service, you shall be required to handover your charge to such person as may be nominated for this purpose by The Company and deliver such articles and effects of the Company, movable or Immoveable as may be in your possession including notes, notebooks and all correspondence either addressed to you by the Company or received by you for and on behalf of the Company. The Company will be entitled to adjust outstanding dues from you against any amount payable to you at the time of full and final settlement.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;">23. </td>
                        <td style="text-align:left; width:95%;">Your services are liable to be terminated or dismissed with immediate effect for the Following reasons.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> a.</td>
                        <td style="text-align:left; width:90%;">On your self-reaching the age of super annuity (completion   of 60 years. The age of super Annuity is fixed at 60) and/or on account of the reason, physical, mental or otherwise Resulting in lowering of your efficiency (where managements decision will be final & Binding).</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> b.</td>
                        <td style="text-align:left; width:90%;">If you are convicted for any act committed by you or if you are arrested for any act Involving moral turpitude.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> c.</td>
                        <td style="text-align:left; width:90%;">If you are found guilty of change of Assigned work/or absent at working without Sanctioned and/or eligible leave and without prior written permission from the Management.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> d.</td>
                        <td style="text-align:left; width:90%;">If at any time because of your acts, or omission in connection with your duties, employment, Business of the company etc.  The management loses confidence in you.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> e.</td>
                        <td style="text-align:left; width:90%;">If you are found to be suffering from long illness or any contagious or infectious Disease.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> f.</td>
                        <td style="text-align:left; width:90%;">If it is found at any time during the tenure of your employment that any information Furnished by you to the management at the time of appointment and thereafter is incorrect, False or misleading.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> g.</td>
                        <td style="text-align:left; width:90%;">If you are found engaged in other business, trade or profession directly or indirectly during your appointment.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> h.</td>
                        <td style="text-align:left; width:90%;">If you are found committing breach of any of the clauses of this agreement.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> i.</td>
                        <td style="text-align:left; width:90%;">If you are found making statements orally or in writing or allegations   against the company or its executives knowing the same to be false which in the opinion of the management will have the effect of lowering the prestige of the company or its executives.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;"></td> 
                        <td style="text-align:left; width:5%;"> j.</td>
                        <td style="text-align:left; width:90%;">In case any information furnished by you in connection with your appointment or asked from time to time is found incorrect or false at any stage or correct information is found suppressed, your services are liable to be terminated at any time without any notice.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;">24. </td>
                        <td style="text-align:left; width:95%;">The management of the company reserves the right to change/add/delete any/all of the above terms & conditions without prior notice.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;">25. </td>
                        <td style="text-align:left; width:95%;">Fulfilment of targets assigned to you will be your prime responsibility.All future increments/promotions will be directly linked to your sales achievement. You will appropriately use promotional tools like Samples, Gifts, literatures, posters, banners etc. and submit monthly account of them.  Any misuse will lead to strict actions against you.  You are supposed to provide complete account of samples, gift article at time to time or time of leaving company.</td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;">26. </td>
                        <td style="text-align:left; width:95%;">While claiming fares in your expense Statement.  You will approved by your superior and any new addition of places should have the concern of your superior.</td>
                    </tr>
                    </table>
                    <div></div>
                    <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>Commencement Terms</u></b></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;">1. </td>
                        <td style="text-align:left; width:95%;"><b>Timing:</b>You will observe working hours relevant to the department you are placed in. Your timing shall be from 9:00a.m. up to7:00p.m. During your tenure the timings may change at the discretion of the company. </td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">2. </td>
                        <td style="text-align:left; width:95%;"><b>You have to stay in the office till your work is completed and/ or your senior are in office regarding your work.</b></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">3. </td>
                        <td style="text-align:left; width:95%;">You will be required to undergo training & trade tests from time to time. these will be integral  to your job requirements, failing to undergo, complete , pass or get certified as per  the process specified for the purpose, will attract review of your continuation in employment with the organization.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">4. </td>
                        <td style="text-align:left; width:95%;">You will report for work punctually at the appointed time and duly record your attendance as per the company norms. Failure to record your attendance will be treated as unauthorized absence from duty, rendering you liable for disciplinary action and salary deduction. </td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">5. </td>
                        <td style="text-align:left; width:95%;">Late coming is liable for a proportionate deduction in the day’s wages if late coming is for more than 2 days in a month. Or as per company policy of late coming.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">6. </td>
                        <td style="text-align:left; width:95%;">You are required to be at your work spot during office hours failing which you will be treated as absent and subject to disciplinary action.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">7. </td>
                        <td style="text-align:left; width:95%;">You shall not pledge the Company’s credit and / or make representation unless you are specifically and dully authorized in that behalf.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">8. </td>
                        <td style="text-align:left; width:95%;">You will abide by the Rules and Regulations/Standing Orders/Code of Conduct of the Company in force, at present, and as verified from time to time.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">9. </td>
                        <td style="text-align:left; width:95%;">Your appointment is subject to satisfactory replies being received from the references / previous employers mentioned in your Employment Application Form.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">10. </td>
                        <td style="text-align:left; width:95%;">The company will except  you to discharge  the responsibilities  entrusted  to  you with  the highest  standards of initiative , efficiency  and economy. </td>
                    </tr>
                    </table>
                    <div></div>
                     <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>Remuneration:</u></b></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">1. </td>
                        <td style="text-align:left; width:95%;">Over Time: Not applicable.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">2. </td>
                        <td style="text-align:left; width:95%;">Inclusive in gross (CTC) salary, Bonus, increment, PF, E.S.I.C., leave salary as per company’s policy</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">3. </td>
                        <td style="text-align:left; width:95%;">As per rules deduction of P.F Contribution, Professional tax & Income tax as & when applicable will be implied to the above-mentioned salary.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">4. </td>
                        <td style="text-align:left; width:95%;">Salary and other prerequisites that may have been mentioned in your salary structure are subject to taxes where applicable. </td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">5. </td>
                        <td style="text-align:left; width:95%;">At sole discretion the company may consider either change of heads or to redistribute the total emoluments under various heads. </td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">6. </td>
                        <td style="text-align:left; width:95%;">Bonus will be paid as per financial year (previous year’s bonus will be paid on current year’s Diwali) and it will be not paid at the time of leaving company or employee requirements. Bonus payment will be paid to members completing one year in the organization</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">7. </td>
                        <td style="text-align:left; width:95%;">Any compensation given by the company is confidential in nature and strictly between you and the management. </td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">8. </td>
                        <td style="text-align:left; width:95%;">Any queries regarding this will be discussed with the appropriate authority, viz your department head and the designated HR officer. </td>
                    </tr>
                    </table>
                    <div></div>
                    <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>Mobile</u></b></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">1. </td>
                        <td style="text-align:left; width:95%;">Personal mobile are not allowed and any illegal activity on mobile is not allowed.</td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:5%;">2. </td>
                        <td style="text-align:left; width:95%;">Any illegal activity done by you will not be allowed. Disciplinary action shall be taken if any such activity is found to be done by you.</td>
                    </tr>
                    </table>
                    <div></div>
                    <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>Uniform</u></b></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:100%;"><ul><li>Uniform is deemed as company property which facilitates team identity and brand building of our business. Unauthorized usage or usage of the uniform in places other than the company and offices is strictly not permitted. </li></ul></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:100%;"><ul><li>Uniform during working hours is compulsory and shall be provided and /or prescribed by the company at employee cost.</li></ul></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:100%;"><ul><li>Uniform is provided by the company against a refundable deposit of Rs. 500/-. The deposit shall be deducted from your first two salaries. The deposit is not refundable if you resign within a period of 3 months of joining the organization and or in case of lost uniform.</li></ul></td>
                    </tr>
                    </table>
                    <div></div>
                    <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>Other terms and conditions of services:</u></b></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:100%;"><ul><li>You agree and undertake that during the continuation of your employment with us, you shall devote your whole time & attention to the work of the company & shall not associate, in any capacity directly or through anyone else either part time or full time, with or without remuneration or honorary basis with any business of an individual, company or partnership firm or concern without obtaining prior written permission from the company. </li></ul></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:100%;"><ul><li>You shall treat all the business and other information and secrets of and about the company which may become known to you directly or indirectly during the course of your services, as strictly confidential and shall not divulge the same directly or indirectly without the written consent of the management.</li></ul></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:100%;"><ul><li>Your appointment is and shall be subject to the rules and regulations of the company in force from time to time.</li></ul></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:100%;"><ul><li>The company has adopted a conflict of interest policy in respect of his employees. This policy  is intended  to avoid conflict  between  the  personal  interests of an employees and the interests of the Company  in dealing with suppliers customers  and  all other organizations of individuals doing or seeking to do business with the company .</li></ul></td>
                    </tr>
                     <tr>
                         <td style="text-align:left; width:100%;"><ul><li>Noted below are a few examples of conflict of interest –</li></ul></td>
                    </tr>
                     <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul  style="list-style-type:circle;"><li>For an employee to serve in or be associated with any company or organization doing or seeking to do business with the company or affiliate in any capacity.</li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul  style="list-style-type:circle;"><li>For any employee to use  or release  to a third party, any  data on decisions, plans, competitive  bids or any other  information  concerning  the   Company   </li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul  style="list-style-type:circle;"><li>For an employee or any dependent member of his family,</li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:10%;"></td>
                         <td style="text-align:left; width:90%;"><ul><li>To accept  commission, a share  in profits  or other  payments, loans ,(other than with established banking  or financial  institutions ) services  form any individual or organization which has  business with the Company.</li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:10%;"></td>
                         <td style="text-align:left; width:90%;"><ul><li>To have an interest in any organization which has business dealing s with the company where there is an opportunity for preferential treatment to be given or received.</li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:10%;"></td>
                         <td style="text-align:left; width:90%;"><ul><li>To have any dealing with the Company or any affiliate or with any company, firm or individual who is seeking to become a contractor, suppliers or customer.</li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul  style="list-style-type:circle;"><li>You shall conduct yourself with discipline and professional ethics within the company as well as outside and shall make your best efforts to protect and further interest of the company to the maximum extent possible.</li></ul></td>
                    </tr>
                    </table>
                     <div></div>
                    <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>General</u></b></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>Your appointment and your continuation in employment are subject to your being found medically fit by the company appointed doctor & reference check.</li></ul></td>
                    </tr>
                     <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>You will abide by all the rules & regulations of the organization, which are in force from time to time.</li></ul></td>
                    </tr>
                     <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>You will keep us informed of any change in your residential address.</li></ul></td>
                    </tr>
                     <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>Your growth in the company will depend solely upon your performance and contribution to the company. </li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>This appointment letter is being issued to you on the basis of the information and particulars furnished by you in your application (including bio-data), at the time of your interview and subsequent discussions. If it transpires that you have made  a false statement ( or have not disclosed a material fact ) resulting  in your  being  offered  this  appointment, the Management  may take such action as it  deems  fit  in  its  sole  discretion, including  termination your employment .</li></ul></td>
                    </tr>
                     <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>The foregoing  constitutes the entire agreement  as regards the  terms  and conditions of  your service  with   the Company and they  shall be subject to such  modification s and amendments as may  be introduced  from  time to time  as per the Company’s Rule &  regulation.</li></ul></td>
                    </tr>
                     <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;"><ul><li>In the  event of any dispute  ‘Ahmedabad’ will be  treated as the place  where the dispute  has arisen , hence  the dispute  will be  subject to Ahmedabad  jurisdiction  only, irrespective  of the  place  of your posting  as the  arising of dispute .</li></ul></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;">If you are agreeable to the above-mentioned terms & conditions, please intimate your acceptance to us by returning a copy of this letter, duly signed by you, within seven days of receipt. In case of no confirmation is received within the above-mentioned period the appointment letter shall be deemed to have been withdrawn.</td>
                    </tr>
                    </table>
                     <div></div>
                    <table>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b><u>ENDORSEMENT OF ACCEPTANCE:</u></b></td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;">I have read and understood all terms and conditions and accept the same. </td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b>Signature:_______________</b></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b>Date:_______________</b></td>
                    </tr>
                    <tr>
                         <td style="text-align:left; width:5%;"></td>
                        <td style="text-align:left; width:95%;"><b>Place:_______________</b></td>
                    </tr>
                    </table>
                    <br pagebreak="true"/>
                    <h1 style="text-align:center;"><u>Annexure VII: MIS Reporting Details and Formats</u></h1>
                    <table>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;">Dear Sir/Madam,  </td>
                    </tr>
                    <tr>
                          <td style="text-align:left; width:5%;"></td>
                         <td style="text-align:left; width:95%;">Please find <b>MIS Reporting Details </b>for the current incumbent post of <b>Senior Executive.</b>  </td>
                    </tr>
                    </table>
                     <br pagebreak="true"/>
                    <h1 style="text-align:center;"><u>Annexure VIII: Job Responsibility </u></h1>';
                
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('OfferLetter.pdf', 'I');
            }
            
        }
    }

} else {
    echo "[]";
}
