<?php
require '../db.php';
require '../token.php';
require_once '../tcpdf/tcpdf.php';
require '../phpmailer/class.phpmailer.php';
require_once '../PHPExcel/Classes/PHPExcel.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

//   ini_set('display_errors', 1);
//     error_reporting(E_ALL);

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);


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

    // Offer Letter
    if($_GET['type']=='downloadofferletter'){
        $id = $_GET['id'];
        $sql = "SELECT * FROM candidate WHERE id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql2 = " SELECT * from salary_annexure WHERE emp_id = '$id'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        class MYPDF extends TCPDF {
                            public function Header() {
                                
                            }
                            public function Footer() {
                                
                            }
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle('Salary Annexure');
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
                        $pdf->SetFont ('times', '', '12');
                        $html='
                        <br><br><br>
                        <h3 style="text-align:center;">OFFER LETTER</h3>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                                <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                            </tr>
                            <tr>
                                <td colspan="3"><b>To,</b></td>
                            </tr>
                            <tr>
                                <td style="width:50%;"><b>'.$title.' '.$row['candidate_name'].' <br>'.$row['address'].'</b></td>
                            <tr>
                        </table>
                        <h3>Dear '.$title.' '.$row['candidate_name'].' , </h3><br>
                        <table>
                            <tr>
                                <td style="width:5%;"></td>
                                <td style="width:95%; text-align:justify;">This has reference to your application for employment in our Company; we are pleased to offer you an employment with us as an <b>'.$row2['finaldesignation'].'</b> on '.$newDate.' in <b> GMP Software Pvt ltd based in Pune HQ</b><br>Please note that this is merely an Offer Letter. <br>You are requested to carry the following documents at the time of joining: -<br>&nbsp; &nbsp; 1.	Academic Certificates / Passing Certificate (Original).<br>&nbsp; &nbsp; 2.	Two Passport size photographs.<br>&nbsp; &nbsp; 3.	ID Proof Xerox (Pan Card/Driving License/ Aadhar Card). <br>You are requested to join within 7 days from receipt of this Letter, failing, which this offer of employment stands withdrawn after completion of this period.<br>If employee’s performance found poor, company may ask to extend training period or ask to leave.<br>Kindly confirm your acceptance on the duplicate copy of this letter/or Return Email.<br>Other employment terms will be as per your appointment letter and will be informed within 7 days from your joining.<br></td>
                            </tr>
                        </table>
                        <br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                              </tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                              </tr>
                              <tr><td></td></tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h4 style="text-align:center;">Salary Annexure : </h4>
                        <br></br><br>
                        <table>
                            <tr>
                                <td style="text-align:right; width:100%;">Date: </td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Name: </td>
                                <td style="width:70%;">'.$row['candidate_name'].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Designation:</td>
                                <td style="width:70%;">'.$row['finaldesignation'].'</td>
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
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['basic'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['basic']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">2</td>
                                <td border="1">HRA</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['hra'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['hra']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">3</td>
                                <td border="1">Conveyance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['conveyance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['conveyance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">4</td>
                                <td border="1">Medical</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['medical'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['medical']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">5</td>
                                <td border="1">Special Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['specialallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['specialallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">6</td>
                                <td border="1">Education Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['educationalallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['educationalallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['gross'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['gross'], 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Deductions :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PT</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['p_tax'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['p_tax']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">3.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['deduction'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['deduction']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Company Contribution :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['c_ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['c_ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['contribution'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['contribution']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td border="1" colspan="4"></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['inhand'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['inhand']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">CTC ( A + C )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ctc'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ctc']*12, 2, '.', '').'</td>
                            </tr>
                        </table>
                        <br><br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b></b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$row2['type'].')</td>
                          </tr>
                        </table>';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $id.'.pdf';
                        $pdf->Output('Offerletter'.$file, 'I');
                    }
                }
            }
        }
    }
    else if($_GET['type']=='emailofferletter'){
        $id = $_GET['id'];
        $sql = "SELECT * FROM candidate WHERE id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $email = $row['email_id'];
                $sql2 = " SELECT * from salary_annexure WHERE emp_id = '$id'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        class MYPDF extends TCPDF {
                            public function Header() {
                                
                            }
                            public function Footer() {
                                
                            }
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle('Salary Annexure');
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
                        $pdf->SetFont ('times', '', '12');
                        $html='
                        <br><br><br>
                        <h3 style="text-align:center;">OFFER LETTER</h3>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                                <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                            </tr>
                            <tr>
                                <td colspan="3"><b>To,</b></td>
                            </tr>
                            <tr>
                                <td style="width:50%;"><b>'.$title.' '.$row['candidate_name'].' <br>'.$row['address'].'</b></td>
                            <tr>
                        </table>
                        <h3>Dear '.$title.' '.$row['candidate_name'].' , </h3><br>
                        <table>
                            <tr>
                                <td style="width:5%;"></td>
                                <td style="width:95%; text-align:justify;">This has reference to your application for employment in our Company; we are pleased to offer you an employment with us as an <b>'.$row2['finaldesignation'].'</b> on '.$newDate.' in <b> GMP Software Pvt ltd based in Pune HQ</b><br>Please note that this is merely an Offer Letter. <br>You are requested to carry the following documents at the time of joining: -<br>&nbsp; &nbsp; 1.	Academic Certificates / Passing Certificate (Original).<br>&nbsp; &nbsp; 2.	Two Passport size photographs.<br>&nbsp; &nbsp; 3.	ID Proof Xerox (Pan Card/Driving License/ Aadhar Card). <br>You are requested to join within 7 days from receipt of this Letter, failing, which this offer of employment stands withdrawn after completion of this period.<br>If employee’s performance found poor, company may ask to extend training period or ask to leave.<br>Kindly confirm your acceptance on the duplicate copy of this letter/or Return Email.<br>Other employment terms will be as per your appointment letter and will be informed within 7 days from your joining.<br></td>
                            </tr>
                        </table>
                        <br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                              </tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                              </tr>
                              <tr><td></td></tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h4 style="text-align:center;">Salary Annexure : </h4>
                        <br></br><br>
                        <table>
                            <tr>
                                <td style="text-align:right; width:100%;">Date: </td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Name: </td>
                                <td style="width:70%;">'.$row['candidate_name'].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Designation:</td>
                                <td style="width:70%;">'.$row['finaldesignation'].'</td>
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
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['basic'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['basic']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">2</td>
                                <td border="1">HRA</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['hra'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['hra']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">3</td>
                                <td border="1">Conveyance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['conveyance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['conveyance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">4</td>
                                <td border="1">Medical</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['medical'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['medical']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">5</td>
                                <td border="1">Special Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['specialallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['specialallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">6</td>
                                <td border="1">Education Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['educationalallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['educationalallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['gross'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['gross'], 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Deductions :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PT</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['p_tax'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['p_tax']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">3.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['deduction'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['deduction']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Company Contribution :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['c_ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['c_ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['contribution'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['contribution']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td border="1" colspan="4"></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['inhand'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['inhand']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">CTC ( A + C )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ctc'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ctc']*12, 2, '.', '').'</td>
                            </tr>
                        </table>
                        <br><br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b></b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$row2['type'].')</td>
                          </tr>
                        </table>';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $id.'.pdf';
                        $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
                        
                        $mail = new PHPMailer();
                        $mail->IsSMTP();  
                        $mail->Mailer = "smtp";
                        $mail->SMTPDebug = 1;
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = 'ssl';
                        $mail->Host = "mail.paperlessgmp.com";
                        $mail->Port = 465; // or 587
                        $mail->IsHTML(true);
                        $mail->Username = "demo@paperlessgmp.com";
                        $mail->Password = "2424@Cyclone";
                        $mail->SetFrom("demo@paperlessgmp.com", "Paperless GMP");
                        $mail->Subject = "Offer Letter";
                        $mail->Body = "If the offer letter is accepted please click on below link <br><br>https://paperlessgmp.com/gmptotal/offerletter.php?id=$id<br>Please find Attachment";
                        $mail->AddAttachment( $file, 'OfferLetter.pdf' );
                        $mail->AddAddress($email);
                        if ($mail->Send()) {
                            echo "{\"status\":\"success\"}";
                            unlink($file);
                        } else {
                            echo "{\"status\":\"failed\"}";
                        }
                    }
                }
            }
        }
    }
    
   // Appointment Letter
    else if($_GET['type']=='downloadappointment'){
        
        class MYPDF extends TCPDF {
            public function Header() {
                
            }
            public function Footer() {
                
            }
        }
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Appointment Letter');
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
        $pdf->SetFont ('times', '', '12');
        
        $html = "";
        $id = $_GET['id'];
        $sql = "SELECT * FROM employee WHERE emp_id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <br><br><br>
                <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                <table cellpadding="5" style="text-align:left; width:100%;">
                    <tr>
                        <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                        <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$row['joining_date'].'</label></td>
                    </tr>
                    <tr>
                        <td colspan="3"><b>To,</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;"><b>'.$title.' '.$row['firstname'].' &nbsp;&nbsp; '.$row['lastname'].'</b></td>
                    <tr>
                </table>
                <h3>Dear '.$title.' '.$row['firstname'].' &nbsp;&nbsp;'.$row['lastname'].' , </h3><br>
                 <table>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; text-align:justify;">We are very pleased to inform you that you have selected to work as “'.$row["department"].'-'.$row['designation'].'” with us. The terms & conditions are as follows.</td>
                    </tr>
                </table>
                
                <ol>
                    <li>You will work as “'.$row["department"].' '.$row['designation'].'” looking after all the documentation of plant, auditing the process caried in plant on daily basis, ensuring the process been followed from start to end, report preparation daily as and when required.</li>
                    <li>You will provide solution to respective dept post conducting the required audits, whenever it is necessary; at the same time, you will provide process technology for assuring the process been adhered in the plant.</li>
                    <li>You will be working in accordance with policies, which are made by management.
                    <li>You are posted at present at DNS Fine Chemicals & Laboratories Pvt Ltd situated at W-15, MIDC Badlapur(E), Thane-421503.
                    <li>Initially, you will be based in the Badlapur Thane area. The job is transferable, and depending on the exigencies of business, we may transfer or relocate you elsewhere in the country. The job will involve touring in India if required abroad to prompt business of company.
                    <li>You will provide complete technology for Quality Department of the all the products. You will generate & use it for company’s benefit.
                    <li>This is composite job. While you will be in employment of this company, we shall be free to loan your services to or concurrently utilize them otherwise for any of the establishments of our associate, subsidiary or sister concerns.
                    <li>You shall devote the whole of your time, energy and attention to our business, as directed by us and you shall not devote or apply yourself to any other work or activity either as a source of income or so as to interfere in any way the performance of your duties. In particular, you shall not in any way associate yourself with any political activity, local or otherwise.
                    <li>In all your communication with the outside world, you will represent us only to the extent you have been specifically authorized. When expressing outside your personal views on any matter concerning/ affecting us, you will abundantly clarify that your views may not necessarily our thinking or views in that behalf. 
                    <li>With your confirmation, the employer-employee relationship shall be subject to termination by either side giving to the other written notice of not less than 30 days, provided that, at our option, any Privilege Leave then due shall be adjusted with notice period. Besides, we shall have the further option of paying you in lieu of notice.
                    <li>In case any difference/ dispute arising out of or in connection with the terms and conditions of your service, leads to litigation. It shall be subject to jurisdiction of the appropriate Court exclusively in the district of Thane & State of Maharashtra.
                    <li>The company will pay Rs. 10,500/Month (all inclusive) as remuneration for your services to company. Your salary will be paid in every 7th to 10thday of month. Your joining date in this organization will be from '.$row["joining_date"].'. We look forward to your joining in our company.
                    <li> You will be strictly liable & entitled to keep all the company information confidential. If anyone found any sharing such information with any third party the company have all the rights to terminate his or her service & you will be liable to legal action at any point of time without any prior notice.
                </ol>
                <table>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%; text-align:justify;">We hope you will give us your fullest co-operation & we hope to have long lasting & fruit full relationship with you. </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">Please sign one copy of the letter as acknowledgment of appointment</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:95%;">Thanking you,</td>
                    </tr>
                    
                </table>
                <br><br><br>
                <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:40%; text-align:center;"><b>Sudhir D. Sawant</b></td>
                              </tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>Managing Director </b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%;">	</td>
                              </tr>
                              
                </table>
                <div></div>
                ';
                
                /*$sql2 = " SELECT * from salary_annexure WHERE emp_id = '".$row["emp_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                    }
                }*/
            }
        }
        
        $sql = "SELECT * FROM salary_annexure WHERE emp_id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<br pagebreak="true"/>
                <h4 style="text-align:center;">Salary Annexure : </h4>
                <br></br>
                <table border="1" cellpadding="5">
                    <tr style="background-color:light-gray;">
                        <td style="width:70%;">Components In Salary</td>
                        <td style="width:15%;">Per Month</td>
                        <td style="width:15%;">Per Annum</td>
                    </tr>
                     <tr>
                        <td style="width:70%;">Basic Salary</td>
                        <td style="width:15%;text-align: right;">'.round($row["basic"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["basic"] * 12).'.00</td>
                    </tr>
                     <tr>
                        <td style="width:70%;">HRA</td>
                        <td style="width:15%;text-align: right;">'.round($row["hra"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["hra"] * 12).'.00</td>
                    </tr>
                    <tr>
                        <td style="width:70%;">conveyance</td>
                        <td style="width:15%;text-align: right;">'.round($row["conveyance"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["conveyance"] * 12).'.00</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:70%;">Other Allowances</td>
                        <td style="width:15%;text-align: right;">'.round($row["specialallowance"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["specialallowance"] * 12).'.00</td>
                    </tr>
                     <tr>
                        <td style="width:70%;">Gross</td>
                        <td style="width:15%;text-align: right;">'.round($row["gross"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["gross"] * 12).'.00</td>
                    </tr>
                     <tr>
                        <td style="width:70%;">PF Contribution By Employee</td>
                        <td style="width:15%;text-align: right;">'.round($row["PF_EMP"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["PF_EMP"] * 12).'.00</td>
                    </tr>
                    
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:70%;">ESIC Contribution by employee</td>
                        <td style="width:15%;text-align: right;">'.round($row["ESIC"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["c_ESIC"] * 12).'.00</td>
                    </tr>
                     <tr>
                        <td style="width:70%;">Professional Tax</td>
                        <td style="width:15%;text-align: right;">'.round($row["p_tax"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["p_tax"] * 12).'.00</td>
                    </tr>
                     <tr>
                        <td style="width:70%;">Total Deductions</td>
                        <td style="width:15%;text-align: right;">'.round($row["deduction"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["deduction"] * 12).'.00</td>
                    </tr>
                    <tr>
                        <td style="width:70%;">Net Salary(Goss-Deduction)</td>
                        <td style="width:15%;text-align: right;">'.round($row["inhand"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["inhand"] * 12).'.00</td>
                    </tr>
                    
                </table>
                <div></div>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:70%;">CTC Calculation</td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:70%;">Employee PF Contribution</td>
                        <td style="width:15%;text-align: right;">'.round($row["c_PF"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["c_PF"] * 12).'.00</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:70%;">Employee ESIc Contribution</td>
                        <td style="width:15%;text-align: right;">'.round($row["c_ESIC"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["c_ESIC"] * 12).'.00</td>
                    </tr>
                    <tr>
                        <td style="width:70%;">Employee Medical Ins Contribution</td>
                        <td style="width:15%;text-align: right;">'.round($row["medical"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["medical"] * 12).'.00</td>
                    </tr>
                    <tr>
                        <td style="width:70%;">Gratuity</td>
                        <td style="width:15%;text-align: right;">'.$row["gratuity"].'</td>
                        <td style="width:15%;text-align: right;">'.round($row["gratuity"] / 12).'.00</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:70%;">Bonus</td>
                        <td style="width:15%;text-align: right;">'.round($row["bonus"] / 12).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["bonus"]).'.00</td>
                    </tr>
                    <tr>
                        <td style="width:70%;"></td>
                        <td style="width:15%;text-align: right;">'.round($row["contribution"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["contribution_annual"]).'.00</td>
                    </tr>
                    <tr>
                        <td style="width:70%;">CTC=Goss+(Employee PF+ESIC+Ins)</td>
                        <td style="width:15%;text-align: right;">'.round($row["ctc"]).'.00</td>
                        <td style="width:15%;text-align: right;">'.round($row["ctc_annual"]).'.00</td>
                    </tr>
                </table>';
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $file = $id.'.pdf';
        $pdf->Output('Appointment_Letter_'.$file, 'I');
    }
    else if($_GET['type']=='emailappointment'){
        $id = $_GET['id'];
        $sql = "SELECT * FROM employee WHERE emp_id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $email = $row['emp_email'];
                $sql2 = " SELECT * from salary_annexure WHERE emp_id = '$id'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        class MYPDF extends TCPDF {
                            public function Header() {
                                
                            }
                            public function Footer() {
                                
                            }
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle('Salary Annexure');
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
                        $pdf->SetFont ('times', '', '12');
                        $html='
                        <br><br><br>
                        <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                                <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                            </tr>
                            <tr>
                                <td colspan="3"><b>To,</b></td>
                            </tr>
                            <tr>
                                <td style="width:50%;"><b>'.$title.' '.$row['emp_name'].' <br>'.$row['address'].'</b></td>
                            <tr>
                        </table>
                        <h3>Dear '.$title.' '.$row['emp_name'].' , </h3><br>
                        <table>
                            <tr>
                                <td style="width:5%;"></td>
                                <td style="width:95%; text-align:justify;">This has reference to your application for employment in our Company; we are pleased to offer you an employment with us as an <b>'.$row2['finaldesignation'].'</b> on '.$newDate.' in <b> GMP Software Pvt ltd based in Pune HQ</b><br>Please note that this is merely an Offer Letter. <br>You are requested to carry the following documents at the time of joining: -<br>&nbsp; &nbsp; 1.	Academic Certificates / Passing Certificate (Original).<br>&nbsp; &nbsp; 2.	Two Passport size photographs.<br>&nbsp; &nbsp; 3.	ID Proof Xerox (Pan Card/Driving License/ Aadhar Card). <br>You are requested to join within 7 days from receipt of this Letter, failing, which this offer of employment stands withdrawn after completion of this period.<br>If employee’s performance found poor, company may ask to extend training period or ask to leave.<br>Kindly confirm your acceptance on the duplicate copy of this letter/or Return Email.<br>Other employment terms will be as per your appointment letter and will be informed within 7 days from your joining.<br></td>
                            </tr>
                        </table>
                        <br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                              </tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                              </tr>
                              <tr><td></td></tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%; text-align:center;">(Signature of an Employee)</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h4 style="text-align:center;">Salary Annexure : </h4>
                        <br></br><br>
                        <table>
                            <tr>
                                <td style="text-align:right; width:100%;">Date: </td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Name: </td>
                                <td style="width:70%;">'.$row['candidate_name'].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Designation:</td>
                                <td style="width:70%;">'.$row['finaldesignation'].'</td>
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
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['basic'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['basic']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">2</td>
                                <td border="1">HRA</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['hra'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['hra']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">3</td>
                                <td border="1">Conveyance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['conveyance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['conveyance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">4</td>
                                <td border="1">Medical</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['medical'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['medical']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">5</td>
                                <td border="1">Special Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['specialallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['specialallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">6</td>
                                <td border="1">Education Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['educationalallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['educationalallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['gross'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $row2['gross'], 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Deductions :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PT</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['p_tax'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['p_tax']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">3.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['deduction'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['deduction']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Company Contribution :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['c_ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['c_ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['contribution'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['contribution']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td border="1" colspan="4"></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['inhand'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['inhand']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">CTC ( A + C )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ctc'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['ctc']*12, 2, '.', '').'</td>
                            </tr>
                        </table>
                        <br><br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b></b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$row2['type'].')</td>
                          </tr>
                        </table>';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $id.'.pdf';
                        $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
                        
                        $mail = new PHPMailer();
                        $mail->IsSMTP();  
                        $mail->Mailer = "smtp";
                        $mail->SMTPDebug = 1;
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = 'ssl';
                        $mail->Host = "mail.paperlessgmp.com";
                        $mail->Port = 465; // or 587
                        $mail->IsHTML(true);
                        $mail->Username = "demo@paperlessgmp.com";
                        $mail->Password = "2424@Cyclone";
                        $mail->SetFrom("demo@paperlessgmp.com", "Paperless GMP");
                        $mail->Subject = "Appointment Letter";
                        $mail->Body = "Please find Attachment ";
                        $mail->AddAttachment( $file, 'Appointment.pdf' );
                        $mail->AddAddress($email);
                        if ($mail->Send()) {
                            echo "{\"status\":\"success\"}";
                            unlink($file);
                        } else {
                            echo "{\"status\":\"failed\"}";
                        }
                    }
                }
            }
        }
    }
    
    
    // Increment / Promotion Letter
    else if($_GET['type']=='downloadincrementpromotion'){
        $id = $_GET['id'];
        $sql = "SELECT * FROM employee WHERE emp_id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql2 = " SELECT * from increment_promotion WHERE emp_id = '$id'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $monthname=date("M Y",strtotime($row2['effective']));
                        $lastmonth = date('Y-m-d', strtotime("".$row2['effective']." -1 month"));
                        $lastmonth = date("M Y",strtotime($lastmonth));
                        class MYPDF extends TCPDF {
                            public function Header() {}
                            public function Footer() {}
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle($row2['letter_type']);
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
                        $pdf->SetFont ('times', '', '12');
                        if($row2['letter_type'] == 'Promotion Letter'){
                            $html='
                            <br><br><br>
                            <h3 style="text-align:center;">PROMOTION LETTER</h3>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Management has decided to upgrade your post to give you more opportunity to show your abilities<br>
                                Current Designation: '.$input['current_designation'].'<br>
                                Promoted Designation: '.$promoted_designation.'<br>
                                Your roles and Responsibilities will be inform you by Management<br><br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd </b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;"></td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of Employee)</td>
                        </tr>
                    </table>';
                }
                        else if($row2['letter_type'] == 'Increment Letter'){
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">INCREMENT LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$row2['emp_name'].' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Also management has decided to revise your salary structure which will be effective from '.$monthname.' please find Annexure attached with this Letter.<br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <br><br></br>
                    <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                    <br></br><br>
                    <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%; text-align:center;">1</td>
                            <td border="1" style="width:40%;">Basic</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $updated_gross*0.40, 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($updated_gross*0.40)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.20, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.20)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Medical allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">4</td>
                            <td border="1">Education allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Travelling allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4">Deductions</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $net, 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $netannual, 2, '.', '').'</b></td>
                        </tr>
                    </table> 
                    <br><br><br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                        </tr>
                        <br><br><br>
                        <tr>
                            <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an Employee)</td>
                        </tr>
                    </table>';
                }
                        else {
                            $html='
                            <br><br><br>
                            <h3 style="text-align:center;">INCREMENT AND PROMOTION LETTER</h3>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                                <tr>
                                    <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                                    <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                                </tr>
                                <tr>
                                    <td colspan="3"><b>To,</b></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;"><b>'.$row['emp_name'].' <br>'.$row['address_permanent'].'</b></td>
                                <tr>
                            </table>
                            <h3>Dear '.$row['emp_name'].' , </h3><br>
                            <table>
                                <tr>
                                    <td style="width:5%;"></td>
                                    <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>Also management has decided to revise your salary structure which will be effective from 01 '.$monthname.' please find Annexure attached with this Letter.<br>Along with this management has decided to upgrade your post to give you more opportunity to show your abilities<br>Current Designation: '.$row['first_designation'].'<br>Promoted Designation: '.$row['designation'].'<br>Your roles and Responsibilities will be inform you by Management<br><br>Best of Luck for your Bright Future<br>Thanks and Regards,
                                    </td>
                                </tr>
                            </table>
                            <br><br><br>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                                <tr>
                                    <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                                  </tr>
                                  <tr>
                                    <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                                  </tr>
                                  <tr><td></td></tr>
                                  <tr>
                                    <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%; text-align:center;">Signature of an Employee</td>
                                </tr>
                            </table>
                            <br pagebreak="true"/>
                            <br><br></br>
                            <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                            <br></br><br>
                            <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                                <tr style="background-color:#DCDCDC;">
                                    <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                                    <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                                    <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                                    <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                                </tr>
                                <tr>
                                    <td border="1" style="width:10%; text-align:center;">1</td>
                                    <td border="1" style="width:40%;">Basic</td>
                                    <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['salary']*0.40, 2, '.', '').'</td>
                                    <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($row2['salary']*0.40)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" style="text-align:center;">2</td>
                                    <td border="1">HRA</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.20, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.20)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" style="text-align:center;">3</td>
                                    <td border="1">Conveyance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1"style="text-align:center;">5</td>
                                    <td border="1">Medical allowance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1"style="text-align:center;">4</td>
                                    <td border="1">Education allowance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1"style="text-align:center;">5</td>
                                    <td border="1">Travelling allowance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="4">Deductions</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                                    <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['salary'], 2, '.', '').'</b></td>
                                    <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['salary'], 2, '.', '').'</b></td>
                                </tr>
                            </table> 
                            <br><br><br><br><br><br>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                                <tr>
                                    <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%; text-align:center;">Accepted</td>
                                </tr>
                                <br><br><br>
                                <tr>
                                    <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%; text-align:center;">Signature of an Employee</td>
                                </tr>
                            </table>';
                        }
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $id.'.pdf';
                        $pdf->Output('Appointment_Letter_'.$file, 'I');
                    }
                }
            }
        }
    }
     else if($_GET['type'] == 'downloadempsalaryreport'){
        $objPHPExcel = new PHPExcel();
        $style1 = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, )
        );
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'DNS Fine CHemicals & laboretories Pvt Ltd' );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Salary Report');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'ID');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Name');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Total Days');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Present Days');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Approved leaves');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Unapproved leaves');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G3', 'Gross Salary ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H3', 'Salary/Days');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I3', 'Basic');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J3', 'HRA');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K3', 'Conveyance');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L3', 'Medical');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M3', 'Special Allowance');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N3', 'Educational Allowance');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O3', 'PF');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P3', 'ESIC');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q3', 'PT');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P3', 'Canteen');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('S3', 'Other');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('T3', 'Total');
        
        $b='C';
        for ($i =1; $i <20; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($b++. + 1);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:'.$b. + 1);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:'.$b. + 1)->applyFromArray($style1);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:'.$b. + 2);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:'.$b. + 3)->getFont()->setBold( true );
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2:'.$b. + 2)->applyFromArray($style);
        }
        $ii=4;
        $date = $_GET['month'];
        $time=strtotime($date);
        $month=date("m",$time);
        $year=date("Y",$time);
        $output = Array();
        $sql = "SELECT emp_id,firstname FROM employee WHERE status='active' AND isAppointment = 'active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM salary_annexure WHERE emp_id='".$row["emp_id"]."' AND status='active'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["gross"] = $row1['gross'];
                        $row["p_tax"] = $row1['p_tax'];
                        $row["canteen"] = $row1['canteen'];
                        $row["other"] = $row1['other'];
    
                    $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$row["emp_id"]."' AND status='pending'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row['present_days'] = $row2["present_days"];
                        }
                    } else {
                        $row['present_days'] = 0;
                    }
    
                    $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Short Leave' AND status='pending'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row['shortleave'] = $row2["shortleave"];
                        }
                    } else {
                        $shortleave = 0;
                    }
                    $sql2 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Half Day' AND status='pending'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row['halfday'] = $row2["halfday"];
                        }
                    } else {
                        $row['halfday'] = 0;
                    }
            
                    $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='SBHR001' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row['total_leave'] = $row2["total_leave"];
                        }
                    } else {
                        $row['total_leave'] = 0;
                    }
                    
                    $date = strtotime($_GET['month']);
                    $month=date("m",$date);
                    $year=date("Y",$date);
                    $row['month_days'] = cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                    $row['working_days'] = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                    $row['absent_days'] = $row['working_days'] - $row['present_days'];
                    $row['salary_per_day'] = $row["gross"] / $row['month_days'];
                    $row['paid_days'] = $row['present_days']*1 + $row['total_leave']*1 ;
                    $row['earned_gross'] = $row['salary_per_day'] * $row['paid_days'];
                    
                    $row['basic'] = $row['earned_gross'] * 0.40 ;
                    $row['hra'] = $row['basic'] * 0.40;
                   
                    $row['conveyance'] = $row['earned_gross'] * 0.10;
                    $row['medical'] = $row['earned_gross'] * 0.10;
                    $row['educational'] = $row['earned_gross'] * 0.10;
                    $row['special'] = $row['earned_gross'] * 0.10;
                    if ($row["isPF"] == 'Yes') {
                        $row['pf'] = $row['basic'] * 0.12;
                    } else {
                        $row['pf'] = 0;
                    }
                    if ($row["isESIC"] == 'Yes') {
                        $row['esic'] = $row['earned_gross'] * 0.075;
                    } else {
                        $row['esic'] = 0;
                    }
                    $row['deduction'] = $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                    $row['inhand'] = $row['earned_gross'] - $row['deduction'];
                    $row['total']=$row['gross']+$row['pf']+$row['esic']+$row['medical'];
                    $output[] = $row;
            
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$ii, $row["emp_id"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$ii, $row["firstname"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$ii, $row["working_days"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$ii, $row["present_days"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$ii, $row["total_leave"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$ii, $row["total_leave"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$ii, $row["gross"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$ii, $row["salary_per_day"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.$ii, $row["basic"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.$ii, $row["hra"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K'.$ii, $row["conveyance"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L'.$ii, $row["medical"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M'.$ii, $row["special"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N'.$ii, $row["educational"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O'.$ii, $row["pf"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P'.$ii, $row["esic"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q'.$ii, $row["pT"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('R'.$ii, $row["canteen"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('S'.$ii, $row["other"]);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('T'.$ii, $row["total"]);
                    $ii++;
                    }
                }
            }
        
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(__DIR__."/SalaryReport.xls");
        
        $file = 'SalaryReport.xls';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink('SalaryReport.xls');
    }
}
    else if($_GET['type']=='emailincrementpromotion'){
        $id = $_GET['id'];
        $sql = "SELECT * FROM employee WHERE emp_id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql2 = " SELECT * from increment_promotion WHERE emp_id = '$id'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $monthname=date("M Y",strtotime($row2['effective']));
                        $lastmonth = date('Y-m-d', strtotime("".$row2['effective']." -1 month"));
                        $lastmonth = date("M Y",strtotime($lastmonth));
                        class MYPDF extends TCPDF {
                            public function Header() {}
                            public function Footer() {}
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle($row2['letter_type']);
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
                        $pdf->SetFont ('times', '', '12');
                        if($row2['letter_type'] == 'Promotion Letter'){
                            $html='
                            <br><br><br>
                            <h3 style="text-align:center;">PROMOTION LETTER</h3>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Management has decided to upgrade your post to give you more opportunity to show your abilities<br>
                                Current Designation: '.$input['current_designation'].'<br>
                                Promoted Designation: '.$promoted_designation.'<br>
                                Your roles and Responsibilities will be inform you by Management<br><br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd </b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;"></td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of Employee)</td>
                        </tr>
                    </table>';
                }
                        else if($row2['letter_type'] == 'Increment Letter'){
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">INCREMENT LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$row2['emp_name'].' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Also management has decided to revise your salary structure which will be effective from '.$monthname.' please find Annexure attached with this Letter.<br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <br><br></br>
                    <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                    <br></br><br>
                    <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%; text-align:center;">1</td>
                            <td border="1" style="width:40%;">Basic</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $updated_gross*0.40, 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($updated_gross*0.40)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.20, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.20)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Medical allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">4</td>
                            <td border="1">Education allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Travelling allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4">Deductions</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $net, 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $netannual, 2, '.', '').'</b></td>
                        </tr>
                    </table> 
                    <br><br><br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                        </tr>
                        <br><br><br>
                        <tr>
                            <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an Employee)</td>
                        </tr>
                    </table>';
                }
                        else {
                            $html='
                            <br><br><br>
                            <h3 style="text-align:center;">INCREMENT AND PROMOTION LETTER</h3>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                                <tr>
                                    <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                                    <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                                </tr>
                                <tr>
                                    <td colspan="3"><b>To,</b></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;"><b>'.$row['emp_name'].' <br>'.$row['address_permanent'].'</b></td>
                                <tr>
                            </table>
                            <h3>Dear '.$row['emp_name'].' , </h3><br>
                            <table>
                                <tr>
                                    <td style="width:5%;"></td>
                                    <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>Also management has decided to revise your salary structure which will be effective from 01 '.$monthname.' please find Annexure attached with this Letter.<br>Along with this management has decided to upgrade your post to give you more opportunity to show your abilities<br>Current Designation: '.$row['first_designation'].'<br>Promoted Designation: '.$row['designation'].'<br>Your roles and Responsibilities will be inform you by Management<br><br>Best of Luck for your Bright Future<br>Thanks and Regards,
                                    </td>
                                </tr>
                            </table>
                            <br><br><br>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                                <tr>
                                    <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                                  </tr>
                                  <tr>
                                    <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                                  </tr>
                                  <tr><td></td></tr>
                                  <tr>
                                    <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%; text-align:center;">Signature of an Employee</td>
                                </tr>
                            </table>
                            <br pagebreak="true"/>
                            <br><br></br>
                            <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                            <br></br><br>
                            <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                                <tr style="background-color:#DCDCDC;">
                                    <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                                    <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                                    <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                                    <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                                </tr>
                                <tr>
                                    <td border="1" style="width:10%; text-align:center;">1</td>
                                    <td border="1" style="width:40%;">Basic</td>
                                    <td border="1" style="width:25%; text-align:right;">'.number_format((float) $row2['salary']*0.40, 2, '.', '').'</td>
                                    <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($row2['salary']*0.40)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" style="text-align:center;">2</td>
                                    <td border="1">HRA</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.20, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.20)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" style="text-align:center;">3</td>
                                    <td border="1">Conveyance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1"style="text-align:center;">5</td>
                                    <td border="1">Medical allowance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1"style="text-align:center;">4</td>
                                    <td border="1">Education allowance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1"style="text-align:center;">5</td>
                                    <td border="1">Travelling allowance</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*0.10, 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) ($row2['salary']*0.10)*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary']*12, 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="4">Deductions</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                    <td border="1" style="text-align:right;">'.number_format((float) $row2['salary'], 2, '.', '').'</td>
                                </tr>
                                <tr>
                                    <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                                    <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['salary'], 2, '.', '').'</b></td>
                                    <td border="1" style="text-align:right;"><b>'.number_format((float) $row2['salary'], 2, '.', '').'</b></td>
                                </tr>
                            </table> 
                            <br><br><br><br><br><br>
                            <table cellpadding="5" style="text-align:left; width:100%;">
                                <tr>
                                    <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%; text-align:center;">Accepted</td>
                                </tr>
                                <br><br><br>
                                <tr>
                                    <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                                    <td style="width:10%;"></td>
                                    <td style="width:60%; text-align:center;">Signature of an Employee</td>
                                </tr>
                            </table>';
                        }
                        EOD;
                        
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $id.'.pdf';
                        $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
                        
                        $mail = new PHPMailer();
                        $mail->IsSMTP();  
                        $mail->Mailer = "smtp";
                        $mail->SMTPDebug = 1;
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = 'ssl';
                        $mail->Host = "mail.paperlessgmp.com";
                        $mail->Port = 465; // or 587
                        $mail->IsHTML(true);
                        $mail->Username = "demo@paperlessgmp.com";
                        $mail->Password = "2424@Cyclone";
                        $mail->SetFrom("demo@paperlessgmp.com", "Paperless GMP");
                        $mail->Subject = $row2['letter_type'];
                        $mail->Body = "Please find Attachment ";
                        $mail->AddAttachment( $file, $row2['letter_type'] );
                        $mail->AddAddress($row['emp_email']);
                        if ($mail->Send()) {
                            echo "{\"status\":\"success\"}";
                            unlink($file);
                        } else {
                            echo "{\"status\":\"failed\"}";
                        }
                    }
                }
            }
        }
    }
    
    // All Employee / Labour Salary
    else if($_GET['type']=='empsalaryreport'){
        $date = strtotime($_GET['month']);
        $month=date("m",$date);
        $year=date("Y",$date);
        
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Shri Bhavani Pharmaceuticals")
    		->setLastModifiedBy("Shri Bhavani Pharmaceuticals")
    		->setTitle("Shri Bhavani Pharmaceuticals")
    		->setSubject("Shri Bhavani Pharmaceuticals")
    		->setDescription("Shri Bhavani Pharmaceuticals")
    		->setKeywords("Shri Bhavani Pharmaceuticals")
    		->setCategory("Shri Bhavani Pharmaceuticals");
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->getStyle("A2:J2")->getFont()->setBold( true );
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:J1');
        $objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:J1")->applyFromArray($style);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Salary Report - '.$month.'-'.$year );
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A2', 'Employee Id')
            ->setCellValue('B2', 'Employee Name')
            ->setCellValue('C2', 'Month days')
            ->setCellValue('D2', 'Working days')
            ->setCellValue('E2', 'Present Days')
            ->setCellValue('F2', 'Absent Days')
            ->setCellValue('G2', 'Paid Days')
            ->setCellValue('H2', 'Per Day Salary')
            ->setCellValue('I2', 'Deduction')
            ->setCellValue('J2', 'Net Pay');
        $i = 3;
        $sql = "SELECT emp_id, emp_name FROM employee WHERE status='active' AND isSalaryAnnexure = 'active'";
        $result = $hr->query($sql);
        if ($result->num_rows > 0) {
            
            while($row = $result->fetch_assoc()) {
                $per_day = 0;
                $gross = 0;
                $sql1 = "SELECT * FROM salary_annexure WHERE emp_id='".$row["emp_id"]."' ";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $gross = $row1["gross"];
                        $canteen = $row1["canteen"];
                        $other = $row1["other"];
                        $p_tax = $row1["p_tax"];
                        $ctc = $row1["ctc"];
                        $isMetro = $row1["isMetro"];
                        $isPF = $row1["isPF"];
                        $isESIC = $row1["isESIC"];
                    }
                }
            
                $sql1 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$row["emp_id"]."' AND status='pending' AND MONTH(intime) = $month AND YEAR(intime) = $year";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $present_days = $row1["present_days"];
                    }
                } else {
                    $present_days = 0;
                }
            
                $sql1 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Short Leave' AND status='pending'";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["shortleave"] = $row1["shortleave"];
                    }
                } else {
                    $row["shortleave"] = 0;
                }
                
                $sql1 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Half Day' AND status='pending'";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["halfday"] = $row1["halfday"];
                    }
                } else {
                    $row["halfday"] = 0;
                }
            
                $sql1 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='SBHR001' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $total_leave = $row1["total_leave"];
                    }
                } else {
                    $total_leave = 0;
                }
                $month_days = cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                $absent_days = $working_days - $present_days;
                $per_day = number_format((float)$gross / $month_days, 2, '.', '');
                $paid_days = $present_days + $total_leave;
                $gorss_pay = $paid_days * $per_day;
                $pf = 0;
                $esic = 0;
                $deduction = $pf + $p_tax + $esic;
                $net_pay = $gorss_pay - $deduction; 
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $row["emp_id"])
                ->setCellValue('B'.$i, $row["emp_name"])
                ->setCellValue('C'.$i, $month_days)
                ->setCellValue('D'.$i, $working_days)
                ->setCellValue('E'.$i, $present_days)
                ->setCellValue('F'.$i, $absent_days)
                ->setCellValue('G'.$i, $paid_days)
                ->setCellValue('H'.$i, $per_day)
                ->setCellValue('I'.$i, $deduction)
                ->setCellValue('J'.$i, $net_pay);
                $i = $i+1;
            }
        
            $objPHPExcel->getActiveSheet()->setTitle('Simple');
            $objPHPExcel->setActiveSheetIndex(0);
            
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save(__DIR__."/employeereport.xls");
            
            $file = 'employeereport.xls';
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink('employeereport.xls');
        }
    
    } 
    else if($_GET['type']=='getlaboursalaryreport'){
        $date = strtotime($_GET['month']);
        $month=date("m",$date);
        $year=date("Y",$date);
        
        $objPHPExcel = new PHPExcel();
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->getStyle("A2:G2")->getFont()->setBold( true );
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:G1');
        $objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:G1")->applyFromArray($style);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Salary Report - '.$month.'-'.$year );
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A2', 'Labour Id')
            ->setCellValue('B2', 'Labour Name')
            ->setCellValue('C2', 'Month days')
            ->setCellValue('D2', 'Present Days')
            ->setCellValue('E2', 'Absent Days')
            ->setCellValue('F2', 'Per Day Salary')
            ->setCellValue('G2', 'Net Pay');
        $i = 3;
        $sql = "SELECT * FROM labour WHERE status='active'";
        $result = $hr->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $per_day = 0;
                $net_pay = 0;
                $per_day = $row['daily_wages'];
            
                $sql1 = "SELECT COUNT(id) as present_days FROM labour_attendance WHERE labour_id='".$row["labour_id"]."' AND MONTH(in_time) = $month AND YEAR(in_time) = $year";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $present_days = $row1["present_days"];
                    }
                } else {
                    $present_days = 0;
                }
            
                $sql1 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Short Leave' ";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["shortleave"] = $row1["shortleave"];
                    }
                } else {
                    $row["shortleave"] = 0;
                }
                
                $sql1 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Half Day'";
                $result1 = $hr->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["halfday"] = $row1["halfday"];
                    }
                } else {
                    $row["halfday"] = 0;
                }
            
                
                $month_days = cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $absent_days = $month_days - $present_days;
                $net_pay = $per_day * $present_days ; 
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $row["labour_id"])
                ->setCellValue('B'.$i, $row["labour_name"])
                ->setCellValue('C'.$i, $month_days)
                ->setCellValue('D'.$i, $present_days)
                ->setCellValue('E'.$i, $absent_days)
                ->setCellValue('F'.$i, $per_day)
                ->setCellValue('G'.$i, $net_pay);
                $i = $i+1;
            }
        
            $objPHPExcel->getActiveSheet()->setTitle('Simple');
            $objPHPExcel->setActiveSheetIndex(0);
            
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save(__DIR__."/report.xls");
            
            $file = 'report.xls';
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink('report.xls');
        }
    
    } 
    
    // Single Employee / Labour Salary 
    else if($_GET['type']=='downloadempsalary'){
        $sql = "SELECT emp_name FROM employee WHERE emp_id = '".$_GET['id']."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){

            $date = $_GET['month'];
            $time=strtotime($date);
            $month=date("m",$time);
            $year=date("Y",$time);
            $monthdays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $sql1 = "SELECT COUNT(id) as present_days FROM attendance WHERE emp_id = '".$_GET['id']."' AND MONTH(indate) = $month AND YEAR(indate) = 2020";
            $result1 = $hr->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $present = $row1["present_days"];
                }
            } else {
                $present = 0;
            }
            $absent = $monthdays - $present;
            class MYPDF extends TCPDF {
                public function Header() {
                    
                }
                public function Footer() {
                    
                }
            }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle('Salary Report');
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
                        $pdf->SetFont ('times', '', '12');
                        $html='
                        <br><br><br>
                        <h3 style="text-align:center;">Salary Calculation</h3>
                        <h4 style="text-align:center;">Month - '.$month.' / '.$year.'</h4>
                        <h3>'.$row['emp_name'].'</h3><br>
                        <table style="text-align:center;" cellpadding="5">
                            <tr>
                                <td border="1">Particulars</td>
                                <td border="1"></td>
                            </tr>
                            <tr>
                                <td border="1">Total Days in month</td>
                                <td border="1">'.$monthdays.'</td>
                            </tr>
                            <tr>
                                <td border="1">Present Days</td>
                                <td border="1">'.$present.'</td>
                            </tr>
                            <tr>
                                <td border="1">Absent Days</td>
                                <td border="1">'.$absent.'</td>
                            </tr>
                            <tr>
                                <td border="1">Total Earning</td>
                                <td border="1"></td>
                            </tr>
                        </table>
                        ';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $_GET['id'].'.pdf';
                        $pdf->Output('Salary_'.$file, 'D');
            }
        }
    }
    else if ($_GET["type"] == "downloadlaboursalaryreport") {
        $_GET['filename'] = 'Salary/Payroll'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>ID</b></td>
            <td style="width:10%; text-align:centre;"><b>Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Total Days</b></td>
            <td style="width:10%; text-align:centre;"><b>Approved Leaves	</b></td>
            <td style="width:20%; text-align:centre;"><b>UnApproved Leaves	</b></td>
            <td style="width:20%; text-align:centre;"><b>Salary/Day	</b></td>
            <td style="width:20%; text-align:centre;"><b>Total Earning</b></td>
           
            
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:20%;"></td>
            <td style="width:20%;"></td>
            <td style="width:20%;"></td>
           
           
            
        </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Batch Manufacturing Status.pdf', 'I');
    }
    else if($_GET['type']=='downloadlaboursalary'){
        $sql = "SELECT labour_name, daily_wages FROM labour WHERE labour_id = '".$_GET['id']."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){

            $date = $_GET['month'];
            $time=strtotime($date);
            $month=date("m",$time);
            $year=date("Y",$time);
            $monthdays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $sql1 = "SELECT COUNT(id) as present_days FROM labour_attendance WHERE labour_id = '".$_GET['id']."' AND MONTH(entry_date) = $month AND YEAR(entry_date) = 2020";
            $result1 = $hr->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $present = $row1["present_days"];
                }
            } else {
                $present = 0;
            }
            $absent = $monthdays - $present;
            $earning = $present * $row['daily_wages'];
            class MYPDF extends TCPDF {
                public function Header() {
                    
                }
                public function Footer() {
                    
                }
            }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle('Salary Report');
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
                        $pdf->SetFont ('times', '', '12');
                        $html='
                        <br><br><br>
                        <h3 style="text-align:center;">Salary Calculation</h3>
                        <h4 style="text-align:center;">Month - '.$month.' / '.$year.'</h4>
                        <h3>'.$row['labour_name'].'</h3><br>
                        <table style="text-align:center;" cellpadding="5">
                            <tr>
                                <td border="1">Particulars</td>
                                <td border="1"></td>
                            </tr>
                            <tr>
                                <td border="1">Total Days in month</td>
                                <td border="1">'.$monthdays.'</td>
                            </tr>
                            <tr>
                                <td border="1">Present Days</td>
                                <td border="1">'.$present.'</td>
                            </tr>
                            <tr>
                                <td border="1">Absent Days</td>
                                <td border="1">'.$absent.'</td>
                            </tr>
                            <tr>
                                <td border="1">Total Earning</td>
                                <td border="1">'.$earning.'</td>
                            </tr>
                        </table>
                        ';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $_GET['id'].'.pdf';
                        $pdf->Output('Salary_'.$file, 'D');
            }
        }
    }
    
} else {
    echo "[]";
}
function calculateWorkingDaysInMonth($year = '', $month = ''){
	if ($year == ''){$year = date('Y'); }
	if ($month == ''){$month = date('m');}	
	$startdate = strtotime($year . '-' . $month . '-01');
	$enddate = strtotime('+' . (date('t',$startdate) - 1). ' days',$startdate);
	$currentdate = $startdate;
	$return = intval((date('t',$startdate)),10);
	while ($currentdate <= $enddate)
	{ if (date('D',$currentdate) == 'Sun') { $return = $return - 1;}
		$currentdate = strtotime('+1 day', $currentdate);
	}
	return $return;
}
?>