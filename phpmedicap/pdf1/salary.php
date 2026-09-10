<?php
require '../db.php';
require '../token.php';
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
                <h3 style="text-align:center;">Annexure II: Salary Sheet</h3>
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
    } else if ($_GET["type"] == "downloadsalary") {
        $_GET['filename'] = 'Employee List'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html.="";
        $html.='';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary.pdf', 'I');
    }

} else {
    echo "[]";
}
