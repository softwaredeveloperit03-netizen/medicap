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
if($_GET["type"]=="capalog"){
      $_GET['filename'] = 'CAPA Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");   
        $html.='    
        <h3 style="text-align:center">CAPA Log</h3>
         <table cellpadding="5" border="1">
           <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:15%; text-align:centre;"><b>CAPA No.</b></td>
            <td style="width:15%; text-align:centre;"><b>Category</b></td>
            <td style="width:15%; text-align:centre;"><b>Receiving no</b></td>
            <td style="width:15%; text-align:centre;"><b>Required For</b></td>
            <td style="width:15%; text-align:centre;"><b>Request By</b></td>
            <td style="width:15%; text-align:centre;"><b>Status</b></td>
        </tr>';
       
        $sql = "SELECT * FROM capa WHERE initiate_date BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
   
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
        $i=1;
        while ($row = $result->fetch_assoc()) {
        $html.='<tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:15%;">'.$row['capa_no'].'</td>
            <td style="width:15%;">'.$row['category'].'</td>
            <td style="width:15%;">'.$row['doc_no'].'</td>
            <td style="width:15%;">'.$row['req_for'].'</td>
            <td style="width:15%;">'.$row['req_by'].'</td>
            <td style="width:15%;">'.$row['status'].'</td>
        </tr>';
        $i++;
        }
    }
        $html.='</table>';
            
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CAPA Log.pdf', 'I');
    }
    else if($_GET['type'] == 'capa'){
        $sql = "SELECT * FROM capa WHERE capa_no='".$_GET['capa_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $html.='
                <style>
                    td { border:solid 1px BCBBBA;}
                </style>
                <table cellpadding="5">
            	    <tr>
            	        <td style="width:100%; text-align:center; background-color:#DDDAD9;"><b>Annexure I :  Corrective Action / Preventive Action Form</b></td>
            	    </tr>
        	    </table>
        	    <div></div>
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:35%;"><b>CAPA NO.:</b> '.$_GET['capa_no'].'</td>
        	            <td style="width:65%;">Requested By:</td>
        	        </tr>
        	        <tr>
        	            <td>Date:</td>
        	            <td><b>Related Department:</b> '.$row['department'].'</td>
        	        </tr>
        	        <tr>
        	            <td style="width:100%;"><b>Originator:</b> '.$_GET['emp_name'].'</td>
        	        </tr>
                    <tr>
                        <td>(Name)<br>(Sign & Date)</td>
                    </tr>
                    <tr>
                        <td>CAPA required in-System  '.$row['required_for'].' </td>
                    </tr>
                    <tr>
                        <td><b>Category:</b> '.$row['capa_category'].'</td>
                    </tr>
                    <tr>
                        <td>
                            Details of Incident/Non conformity/Deviation/Market Complaint:<br><br>Document Number :
                            <br><br>Head of department <br>Sign & Date
                        </td>
                    </tr>
                    <tr>
                        <td>Planned correction: '.$row['planned_correction'].'</td>
                    </tr>
                    <tr>
                        <td>Need to implement corrective action:                 Yes                          No</td>
                    </tr>
                    <tr>
                        <td>Corrective Action :<br>Head of Department Sign & Date </td>
                    </tr>
                    <tr>
                        <td>Planned Preventive Action:</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Preventive Action by Head of Department</b></td>
                    </tr>
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td rowspan="2" style="width:30%;">&nbsp;<br>Department Name</td>
                        <td rowspan="2" style="width:30%;">&nbsp;<br>Preventive Action</td>
                        <td colspan="2" style="width:40%;">Department head</td>
                    </tr>
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Sign</td>
                        <td>Date</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">Quality Assurance Review:</td>
                    </tr>
                    <tr>
                        <td>Whether the preventive proposal is :     Minor                     Major                   Critical</td>
                    </tr>
                    <tr>
                        <td>Regulatory Clearance required  :               Yes                            No                        Not Applicable </td>
                    </tr>
                    <tr>
                        <td>Information Sent to Client :                      Yes                             No                        Not Applicable</td>
                    </tr>
                    <tr>
                        <td>Evaluation of the proposal :<br><br>Head  QA
Sign & Date </td>
                    </tr>
                     <tr>
                        <td>Decision by Quality Assurance :</td>
                    </tr>
                     <tr>
                        <td>Whether consequential changes to any document required :                      Yes                No           NA</td>
                    </tr>
                     <tr>
                        <td>Proposed change :            Approved        /  Not Approved </td>
                    </tr>
                     <tr>
                        <td>Head QA :                                                                    Sign & Date:</td>
                    </tr>
                     <tr>
                        <td>Follow up and close out </td>
                    </tr>
                     <tr>
                        <td>The approved changes have been implemented  hence the CAPA should be treated as closed</td>
                    </tr>
                     <tr>
                        <td>Effective Batch No.:/ Receiving no:</td>
                    </tr> 
                    <tr>
                        <td>Supporting Document Attached </td>
                    </tr>
                     <tr>
                        <td>Head QA :                                                                 Sign & Date:</td>
                    </tr>
        	    </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('CAPA.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'capadigital'){
        $sql = "SELECT * FROM capa WHERE capa_no='".$_GET['capa_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $table='
                        <style>td { border:solid 1px BCBBBA;}</style>
                        <table>
                            <tr>
                                 <td style="width:20%;">';
                                  $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/wastcost.png'),16,12,32);
                                $table.='
                                </td>
                                <td style="width:80%;text-align:center;font-weight:bold;">
                                    <span style="font-family:times;font-size:17px;">WEST COAST PHARMACEUTICAL WORKS LTD</span><br>
                                    <span style="font-size:9px;">Location:Opp Sola Bhagwat, Near Prasang Party Plot,,Near Meldi Mata Temple, Meldi Estate,,GOTA,AHMEDABAD  INDIA-382481.</span>
                                </td>
                            </tr>
                        </table>';
                        $this->SetY('10'); $this->writeHTML($table, true, false, false, false, '');
                        $this->SetY(29); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0,'', 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $html.='
                <style> td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
            	    <tr>
            	        <td style="width:100%; text-align:center; background-color:#DDDAD9;"><b>Annexure I :  Corrective Action / Preventive Action Form</b></td>
            	    </tr>
        	    </table>
        	    <div></div>
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:35%;"><b>CAPA NO.:</b> '.$_GET['capa_no'].'</td>
        	            <td style="width:65%;">Requested By:</td>
        	        </tr>
        	        <tr>
        	            <td>Date:</td>
        	            <td>Related Department:</td>
        	        </tr>
        	        <tr>
        	            <td style="width:100%;"><b>Originator:</b> '.$_GET['emp_name'].'</td>
        	        </tr>
                    <tr>
                        <td>(Name)<br>(Sign & Date)</td>
                    </tr>
                    <tr>
                        <td>CAPA required in-System : '.$row['required_for'].'</td>
                    </tr>
                    <tr>
                        <td><b>Category:</b> '.$row['capa_category'].'</td>
                    </tr>
                    <tr>
                        <td>
                            Details of Incident/Non conformity/Deviation/Market Complaint:<br><br>Document Number :
                            <br><br>Head of department <br>Sign & Date
                        </td>
                    </tr>
                    <tr>
                        <td>Planned correction: '.$row['planned_correction'].'</td>
                    </tr>
                    <tr>
                        <td>Need to implement corrective action:                 Yes                          No</td>
                    </tr>
                    <tr>
                        <td>Corrective Action :<br>Head of Department Sign & Date </td>
                    </tr>
                    <tr>
                        <td>Planned Preventive Action:</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Preventive Action by Head of Department</b></td>
                    </tr>
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td rowspan="2" style="width:30%;">&nbsp;<br>Department Name</td>
                        <td rowspan="2" style="width:30%;">&nbsp;<br>Preventive Action</td>
                        <td colspan="2" style="width:40%;">Department head</td>
                    </tr>
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Sign</td>
                        <td>Date</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">Quality Assurance Review:</td>
                    </tr>
                    <tr>
                        <td>Whether the preventive proposal is :     Minor                     Major                   Critical</td>
                    </tr>
                    <tr>
                        <td>Regulatory Clearance required  :               Yes                            No                        Not Applicable </td>
                    </tr>
                    <tr>
                        <td>Information Sent to Client :                      Yes                             No                        Not Applicable</td>
                    </tr>
                    <tr>
                        <td>Evaluation of the proposal :<br><br>Head  QA
Sign & Date </td>
                    </tr>
                     <tr>
                        <td>Decision by Quality Assurance :</td>
                    </tr>
                     <tr>
                        <td>Whether consequential changes to any document required :                      Yes                No           NA</td>
                    </tr>
                     <tr>
                        <td>Proposed change :            Approved        /  Not Approved </td>
                    </tr>
                     <tr>
                        <td>Head QA :                                                                    Sign & Date:</td>
                    </tr>
                     <tr>
                        <td>Follow up and close out </td>
                    </tr>
                     <tr>
                        <td>The approved changes have been implemented  hence the CAPA should be treated as closed</td>
                    </tr>
                     <tr>
                        <td>Effective Batch No.:/ Receiving no:</td>
                    </tr> 
                    <tr>
                        <td>Supporting Document Attached </td>
                    </tr>
                     <tr>
                        <td>Head QA :                                                                 Sign & Date:</td>
                    </tr>
        	    </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('CAPA.pdf', 'I');
        }
    }
} else {
    echo "[]";
}
?>