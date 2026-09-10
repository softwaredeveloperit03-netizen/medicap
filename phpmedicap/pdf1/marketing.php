<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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

        if($_GET["type"]=="printenquiry"){
    $_GET['filename'] = 'Lead / Enquiry Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
          
    $sql = "SELECT e.*, c.c_name, c.c_address FROM enquiry e JOIN client c ON e.client_code = c.client_code WHERE e.enquiry_no='".$_GET['no']."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html.='
            <table cellpadding="5">
                <tr>
                    <td style="width:20%;"><b>Enquiry Number :</b></td>
                    <td style="width:30%;">'.$row['enquiry_no'].'</td>
                    <td ><b>Reference :</b></td>
                    <td >'.$row['reference'].'</td>
                </tr> 
                <tr>
                    <td style=" width:20%;"><b>Enquiry Date :</b></td>
                    <td style=" width:30%;"> '.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                    <td ><b>Client Company :</b></td>
                    <td >'.$row['c_name'].'</td>
                </tr> 
                <tr>
                    <td><b> Enquiry Status :</b></td>
                    <td>'.$row['status'].'</td>
                    <td><b>Address :</b></td>
                    <td>'.$row['c_address'].'</td>
                </tr>
                <tr>
                    <td style=" width:20%;"><b>Enquiry Details :</b></td>
                    <td style=" width:80%;">'.$row['details'].'</td>
                </tr>
            </table>
            <div></div>
            <h3>Enquiry For</h3>
            <table cellpadding="5">
                <tr style="text-align:center;">
                    <td style="width:10%;"><b>Sr No.</b></td>
                    <td style="width:50%;"><b>Product Name</b></td>
                    <td style="width:40%;"><b>Grade</b></td>
                </tr>';
                $products = json_decode($row['products']);
                $j = 0;
                for ($i =1; $i <=count($products); $i++) {
                    $data = $products[$j];
                    $html.='
                    <tr>
                        <td>'.$i.'</td>
                        <td>'.$data->product_name.'</td>
                        <td>'.$data->grade.'</td>
                    </tr>';
                    $j++;
                }
                $html.='
            </table>
            <div></div>
            <h4 style="text-align:center;">Lead / Enquiry Action</h4>  <br>
            <table cellpadding="5">
                <tr style="text-align:center;">
                    <td><b>Date</b></td>
                    <td><b>Action</b></td>
                    <td><b>Remark</b></td>
                </tr>';
                $sql1 = "SELECT * FROM enquiry_actions WHERE enquiry_no='".$row['enquiry_no']."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                        <tr>
                            <td>'.date('d/m/Y', strtotime($row1['date'])).'</td>
                            <td>'.$row1['action'].'</td>
                            <td>'.$row1['remark'].'</td>
                        </tr>';
                    }
                }
                $html.='
            </table>';
        }
    }
     
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('EnquiryReport.pdf', 'I');
 }
    else if($_GET["type"]=="printenquirylog"){
    $_GET['type'] = 'empdetail';
    include("pdfimp.php");
    class MYPDF extends TCPDF {
        public function Header() {
            $_GET['type'] = 'headerlandscape';
            include("pdfimp.php");
        }
        public function Footer() {
        }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 45, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }
    $pdf->AddPage('L', 'A4');
    $pdf->SetY(45);
    $pdf->SetFont ('Times', '', '11' , '', 'default', true );
    
        $html.='
        <h3 style="text-align:center;">Lead / Enquiry Report</h3> 
            <table cellpadding="5">
                <tr style="text-align:center;">
                    <td><b>Date</b></td>
                    <td><b>Client Name</b></td>
                    <td><b>Enquiry For</b></td>
                </tr>';
            
                $sql = "SELECT * FROM enquiry WHERE entry_date BETWEEN '".$_GET['from']."' AND  '".$_GET['to']."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    $html.='
                        <tr>
                            <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                            <td>'.$row['company'].'</td>
                            <td>'.$row['enquiry_for'].'</td>
                        </tr>';
                    }
                }
            $html.='
            </table>';
    EOD;
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('EnquiryReport.pdf', 'I');
 }
    else if($_GET["type"]=="incidentlog"){
     $_GET['type'] = 'empdetail';
    include("pdfimp.php");
    class MYPDF extends TCPDF {
        public function Header() {
            $_GET['type'] = 'headerlandscape';
            include("pdfimp.php");
        }
        public function Footer() {
        }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 45, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }
    $pdf->AddPage('L', 'A4');
    $pdf->SetY(45);
    $pdf->SetFont ('Times', '', '11' , '', 'default', true );
    
        $html.='
        <h3 style="text-align:center;">Incident Log</h3> 
            <table cellpadding="5">
                <tr style="text-align:center;">
                    <td><b>Incident No.	</b></td>
                    <td><b>Date</b></td>
                    <td><b>Related To</b></td>
                    <td><b>Incident Type</b></td>
                </tr>';
            
                $sql = "SELECT * FROM incident";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    $html.='
                        <tr>
                            <td>'.$row['incident_no'].'</td>
                            <td>'.$row['company'].'</td>
                            <td>'.$row['incident_date'].'</td>
                            <td>'.$row['related_to'].'</td>
                            <td>'.$row['type'].'</td>
                        </tr>';
                    }
                }
            $html.='
            </table>';
    EOD;
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('IncidentReport.pdf', 'I');
 }
 else if ($_GET["type"] == "downloadMarketComplaint") {
        $_GET['filename'] = 'MARKET COMPLAINT INVESTIGATION REPORT'; $_GET['annexure']='Annexure1';$_GET['pdftype'] = 'headfoot'; include("../pdfimp.php");
        $html= "";
        
        $html.='
        <table cellpadding="3" border="1">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:100%;text-align:center;">Market Complaint Investigation Report </td>
            </tr>
            <tr>
                <td style="width:25%;font-weight:bold;">Complaint No</td>
                <td style="width:25%;"></td>
                <td style="width:25%; font-weight:bold;">Received On</td>
                <td style="width:25%;"></td>
            </tr>
            <tr>
                <td style="width:25%;font-weight:bold;">Complaint Received By </td>
                <td style="width:25%;"></td>
                <td style="width:25%; font-weight:bold;">Forwarded to QA Department On</td>
                <td style="width:25%;"></td>
            </tr>
            <tr>
                <td style="width:25%;font-weight:bold;">Nature of Complaint</td>
                <td style="width:75%;">
                    <table>
                        <tr><td style="width:100%;"></td></tr>
                        <tr><td style="width:100%;">Complaint Sample Receive : </td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width:25%;font-weight:bold;">Compliant Details</td>
                <td style="width:75%;">
                    <table>
                        <tr><td style="width:50%;"><b>Product Name:</b></td><td style="width:50%;"><b>Batch No:</b></td></tr>
                        <tr><td style="width:50%;"><b>Mfg Date:</b></td><td style="width:50%;"><b>Exp Date:</b></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width:25%;font-weight:bold;">Inspection of Complaint and Control Sample</td>
                <td style="width:75%;">
                   <table>
                        <tr><td style="width:100%;">Inspection Remark Complaint Sample:</td></tr>
                        <tr><td style="width:100%;">Inspection Remark Control Sample: </td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;text-align:right;font-weight:bold;">Head QA/Designee Sign & Date </td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                   </table>
                </td>
            </tr>
            <tr>
                <td style="width:25%; font-weight:bold;">Primary Observation by Head QA</td>
                <td style="width:75%;">
                    <table>
                        <tr><td style="width:100%;"><b>Remark:</b></td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;font-weight:bold;"> Head QA/Designee Sign & Date</td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width:25%;font-weight:bold;">Product Review</td>
                <td style="width:75%;">
                    <table>
                        <tr><td style="width:100%;font-weight:bold;">Following Manufacturing and Analytical Documents Reviewed:</td></tr>
                        <tr><td style="width:100%;"><b>Test Report</b></td></tr>
                        <tr><td style="width:100%;"><b>Observation:</b></td></tr>
                        <tr><td style="width:50%;font-weight:bold;">Executive QA Sign/Date</td><td style="width:50%;font-weight:bold;">Head QA Sign/Date </td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td rowspan="4" style="width:25%; font-weight:bold;">Cross Functional Technical Review and Remark </td>
                <td style="width:25%;font-weight:bold;">Department</td>
                <td style="width:25%;font-weight:bold;">Remark</td>
                <td style="width:25%;font-weight:bold;">Sign/Date</td>
            </tr>
            <tr>
                <td style="width:25%;">Production</td>
                <td style="width:25%;"></td>
                <td style="width:25%;"></td>
            </tr>
            <tr>
                <td style="width:25%;">Quality Control</td>
                <td style="width:25%;"></td>
                <td style="width:25%;"></td>
            </tr>
            <tr>
                <td style="width:25%;">Research and Development</td>
                <td style="width:25%;"></td>
                <td style="width:25%;"></td>
            </tr>
            <tr>
                <td rowspan="2" style="width:50%;font-weight:bold;">Cross Functional Technical Review and Remark </td>
                <td style="width:50%;"><b>Complaint is:</b></td>
            </tr>
            <tr>
                <td style="width:50%;"><b>Further Investigation:</b></td>
            </tr>
            <tr
                <td style="width:100%;">
                    <table>
                        <tr><td style="width:50%;"><b>Complaint Closed:</b></td><td style="width:50%;"><b>Recommended For Further Investigation:</b></td></tr>
                        <tr><td style="width:100%;"><b>Remark:</b></td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;">Head QA/Designee </td></tr>
                        <tr><td style="width:50%;"></td><td style="width:50%;"></td></tr>
                    </table>
                </td>
            </tr>
            
            
            
        </table>
        ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MarketComplaint.pdf', 'I');
    }
    else if($_GET["type"]=="phoneCallTrackingLog"){
        $isLogView = !empty($_GET["view"]) && $_GET["view"] === 'log';
        $reportTitle = $isLogView ? 'Phone Call Tracking Log' : 'Phone Call Tracking Report';

        $_GET['filename'] = $reportTitle;
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        $_GET['pdfy'] = 26;
        $_GET['pdftop'] = 26;
        $_GET['pdfpagebr'] = 12;
        $_GET['pdffonts'] = 8;
        include("../pdfimp2.php");
        $pdf->SetAutoPageBreak(TRUE, 12);

        $where = "1=1";
        if(!empty($_GET["plant_id"])) {
            $plant_id = $conn->real_escape_string($_GET["plant_id"]);
            $where .= " AND plant_id = '$plant_id'";
        }
        $filterClassification = !empty($_GET["classification"]) ? $conn->real_escape_string($_GET["classification"]) : '';
        $filterStatus = !empty($_GET["status"]) ? $conn->real_escape_string($_GET["status"]) : '';
        if($filterClassification !== '') {
            $where .= " AND classification = '$filterClassification'";
        }
        if($filterStatus !== '') {
            $where .= " AND status = '$filterStatus'";
        }

        $filterText = 'All Records';
        if($filterClassification !== '' || $filterStatus !== '') {
            $parts = array();
            if($filterClassification !== '') {
                $parts[] = 'Classification: '.$filterClassification;
            }
            if($filterStatus !== '') {
                $parts[] = 'Status: '.$filterStatus;
            }
            $filterText = implode(' | ', $parts);
        }

        if($isLogView) {
            $colspan = 9;
            $logWidths = array('4', '10', '14', '12', '12', '10', '16', '8', '14');
            $html .= '
            <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:8px;">
                <tr>
                    <td colspan="'.$colspan.'" style="text-align:center;font-weight:bold;font-size:11px;border:none;">'.$reportTitle.'</td>
                </tr>
                <tr>
                    <td colspan="'.$colspan.'" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').' | Filter: '.$filterText.'</td>
                </tr>
                <tr style="text-align:center;background-color:#0e4370;color:#ffffff;font-weight:bold;">
                    <td width="'.$logWidths[0].'%">Sr</td>
                    <td width="'.$logWidths[1].'%">Classification</td>
                    <td width="'.$logWidths[2].'%">Client Name</td>
                    <td width="'.$logWidths[3].'%">Contact Person</td>
                    <td width="'.$logWidths[4].'%">Phone Number</td>
                    <td width="'.$logWidths[5].'%">Call Type</td>
                    <td width="'.$logWidths[6].'%">Date &amp; Time</td>
                    <td width="'.$logWidths[7].'%">Duration</td>
                    <td width="'.$logWidths[8].'%">Status</td>
                </tr>';
        } else {
            $colspan = 13;
            $reportWidths = array('3', '7', '11', '9', '9', '6', '7', '6', '5', '7', '7', '7', '16');
            $html .= '
            <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:8px;">
                <tr>
                    <td colspan="'.$colspan.'" style="text-align:center;font-weight:bold;font-size:11px;border:none;">'.$reportTitle.'</td>
                </tr>
                <tr>
                    <td colspan="'.$colspan.'" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').'</td>
                </tr>
                <tr style="text-align:center;background-color:#0e4370;color:#ffffff;font-weight:bold;">
                    <td width="'.$reportWidths[0].'%">Sr</td>
                    <td width="'.$reportWidths[1].'%">Classification</td>
                    <td width="'.$reportWidths[2].'%">Client Name</td>
                    <td width="'.$reportWidths[3].'%">Contact</td>
                    <td width="'.$reportWidths[4].'%">Phone</td>
                    <td width="'.$reportWidths[5].'%">Type</td>
                    <td width="'.$reportWidths[6].'%">Call Date</td>
                    <td width="'.$reportWidths[7].'%">Time</td>
                    <td width="'.$reportWidths[8].'%">Dur.</td>
                    <td width="'.$reportWidths[9].'%">Status</td>
                    <td width="'.$reportWidths[10].'%">Response</td>
                    <td width="'.$reportWidths[11].'%">Followup</td>
                    <td width="'.$reportWidths[12].'%">Created Date</td>
                </tr>';
        }

        $sql = "SELECT * FROM crm_phone_tracking WHERE $where ORDER BY created_date_time DESC";
        $result = $conn->query($sql);
        $sr = 1;
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if($isLogView) {
                    $callDate = !empty($row['call_date']) ? date('d/m/Y', strtotime($row['call_date'])) : 'N/A';
                    $callTime = !empty($row['call_time']) ? $row['call_time'] : '';
                    $dateTime = trim($callDate.' '.$callTime);
                    $duration = !empty($row['call_duration']) ? $row['call_duration'].' min' : 'N/A';
                    $html .= '
                    <tr nobr="true">
                        <td width="'.$logWidths[0].'%" align="center">'.$sr.'</td>
                        <td width="'.$logWidths[1].'%">'.htmlspecialchars($row['classification'] ?: 'N/A').'</td>
                        <td width="'.$logWidths[2].'%">'.htmlspecialchars($row['client_name'] ?: 'N/A').'</td>
                        <td width="'.$logWidths[3].'%">'.htmlspecialchars($row['contact_person'] ?: 'N/A').'</td>
                        <td width="'.$logWidths[4].'%">'.htmlspecialchars($row['phone_number'] ?: 'N/A').'</td>
                        <td width="'.$logWidths[5].'%">'.htmlspecialchars($row['call_type'] ?: 'N/A').'</td>
                        <td width="'.$logWidths[6].'%">'.$dateTime.'</td>
                        <td width="'.$logWidths[7].'%" align="center">'.$duration.'</td>
                        <td width="'.$logWidths[8].'%">'.htmlspecialchars($row['status'] ?: 'N/A').'</td>
                    </tr>';
                } else {
                    $createdDate = !empty($row['created_date_time']) ? date('d/m/Y H:i', strtotime($row['created_date_time'])) : 'N/A';
                    $html .= '
                    <tr nobr="true">
                        <td width="'.$reportWidths[0].'%" align="center">'.$sr.'</td>
                        <td width="'.$reportWidths[1].'%">'.htmlspecialchars($row['classification'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[2].'%">'.htmlspecialchars($row['client_name'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[3].'%">'.htmlspecialchars($row['contact_person'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[4].'%">'.htmlspecialchars($row['phone_number'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[5].'%">'.htmlspecialchars($row['call_type'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[6].'%">'.htmlspecialchars($row['call_date'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[7].'%">'.htmlspecialchars($row['call_time'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[8].'%" align="center">'.htmlspecialchars($row['call_duration'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[9].'%">'.htmlspecialchars($row['status'] ?: 'N/A').'</td>
                        <td width="'.$reportWidths[10].'%" align="center">'.htmlspecialchars($row['response_received'] ?: 'No').'</td>
                        <td width="'.$reportWidths[11].'%" align="center">'.htmlspecialchars($row['followup_required'] ?: 'No').'</td>
                        <td width="'.$reportWidths[12].'%">'.$createdDate.'</td>
                    </tr>';
                }
                $sr++;
            }
        } else {
            $html .= '
            <tr>
                <td colspan="'.$colspan.'" align="center">No phone call records found</td>
            </tr>';
        }
        $html .= '
        </table>';

        $pdfFileName = $isLogView ? 'PhoneCallTrackingLog.pdf' : 'PhoneCallTrackingReport.pdf';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output($pdfFileName, 'I');
    }
    else if($_GET["type"]=="agentLog"){
        $reportTitle = 'Agent Log';

        $_GET['filename'] = $reportTitle;
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        $_GET['pdfy'] = 26;
        $_GET['pdftop'] = 26;
        $_GET['pdfpagebr'] = 12;
        $_GET['pdffonts'] = 8;
        include("../pdfimp2.php");
        $pdf->SetAutoPageBreak(TRUE, 12);

        $colspan = 8;
        $logWidths = array('4', '10', '16', '14', '12', '18', '10', '16');

        $html .= '
        <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:8px;">
            <tr>
                <td colspan="'.$colspan.'" style="text-align:center;font-weight:bold;font-size:11px;border:none;">'.$reportTitle.'</td>
            </tr>
            <tr>
                <td colspan="'.$colspan.'" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').'</td>
            </tr>
            <tr style="text-align:center;background-color:#0e4370;color:#ffffff;font-weight:bold;">
                <td width="'.$logWidths[0].'%">Sr</td>
                <td width="'.$logWidths[1].'%">Agent No</td>
                <td width="'.$logWidths[2].'%">Agent Name</td>
                <td width="'.$logWidths[3].'%">Contact Person</td>
                <td width="'.$logWidths[4].'%">Contact No</td>
                <td width="'.$logWidths[5].'%">Email</td>
                <td width="'.$logWidths[6].'%">Status</td>
                <td width="'.$logWidths[7].'%">Entry Date</td>
            </tr>';

        $sql = "SELECT * FROM agent ORDER BY id DESC";
        $result = $conn->query($sql);
        $sr = 1;
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $agentNo = !empty($row['agent_no']) ? $row['agent_no'] : '-';
                $statusRaw = !empty($row['status']) ? $row['status'] : 'N/A';
                $status = strtolower($statusRaw) === 'approve' ? 'APPROVED' : strtoupper($statusRaw);
                $entryDate = !empty($row['entry_date']) ? date('d/m/Y', strtotime($row['entry_date'])) : 'N/A';
                $phone = !empty($row['phone']) ? $row['phone'] : 'N/A';
                $html .= '
                <tr nobr="true">
                    <td width="'.$logWidths[0].'%" align="center">'.$sr.'</td>
                    <td width="'.$logWidths[1].'%">'.htmlspecialchars($agentNo).'</td>
                    <td width="'.$logWidths[2].'%">'.htmlspecialchars($row['agent_name'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[3].'%">'.htmlspecialchars($row['contact_person'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[4].'%">'.htmlspecialchars($phone).'</td>
                    <td width="'.$logWidths[5].'%">'.htmlspecialchars($row['email'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[6].'%">'.htmlspecialchars($status).'</td>
                    <td width="'.$logWidths[7].'%">'.$entryDate.'</td>
                </tr>';
                $sr++;
            }
        } else {
            $html .= '
            <tr>
                <td colspan="'.$colspan.'" align="center">No agent records found</td>
            </tr>';
        }
        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('AgentLog.pdf', 'I');
    }
    else if($_GET["type"]=="agentLogDetail"){
        $agentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if($agentId <= 0) {
            echo "Invalid agent id";
            exit;
        }

        $_GET['filename'] = 'Agent Details';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'P';
        $_GET['pdfy'] = 30;
        $_GET['pdftop'] = 30;
        $_GET['pdfpagebr'] = 15;
        $_GET['pdffonts'] = 9;
        include("../pdfimp2.php");
        $pdf->SetAutoPageBreak(TRUE, 15);

        $sql = "SELECT * FROM agent WHERE id='".$agentId."' LIMIT 1";
        $result = $conn->query($sql);
        if(!$result || $result->num_rows === 0) {
            echo "Agent not found";
            exit;
        }
        $row = $result->fetch_assoc();
        $statusRaw = !empty($row['status']) ? $row['status'] : 'N/A';
        $status = strtolower($statusRaw) === 'approve' ? 'APPROVED' : strtoupper($statusRaw);
        $entryDate = !empty($row['entry_date']) ? date('d/m/Y', strtotime($row['entry_date'])) : 'N/A';
        $approveDate = !empty($row['approve_date']) ? date('d/m/Y', strtotime($row['approve_date'])) : 'N/A';

        $fields = array(
            array('Agent No', !empty($row['agent_no']) ? $row['agent_no'] : '-'),
            array('Agent Name', $row['agent_name'] ?: 'N/A'),
            array('Contact Person', $row['contact_person'] ?: 'N/A'),
            array('Contact No', $row['phone'] ?: 'N/A'),
            array('Email', $row['email'] ?: 'N/A'),
            array('Address', $row['address'] ?: 'N/A'),
            array('State', $row['state_code'] ?: 'N/A'),
            array('Pan No', $row['pan_no'] ?: 'N/A'),
            array('TAX Type', $row['gst_type'] ?: 'N/A'),
            array('TAX No', $row['gst_no'] ?: 'N/A'),
            array('Percentage On', $row['percentage_on'] ?: 'N/A'),
            array('Percentage (%)', $row['percentage'] !== '' && $row['percentage'] !== null ? $row['percentage'] : 'N/A'),
            array('Status', $status),
            array('Entry By', $row['entry_by'] ?: 'N/A'),
            array('Entry Date', $entryDate),
            array('Approved By', $row['approve_by'] ?: 'N/A'),
            array('Approved Date', $approveDate),
        );

        $html .= '
        <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:9px;">
            <tr>
                <td colspan="2" style="text-align:center;font-weight:bold;font-size:11px;border:none;">Agent Details</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').'</td>
            </tr>';

        foreach($fields as $field) {
            $html .= '
            <tr nobr="true">
                <td width="30%" style="font-weight:bold;background-color:#f5f7fa;">'.htmlspecialchars($field[0]).'</td>
                <td width="70%">'.htmlspecialchars($field[1]).'</td>
            </tr>';
        }
        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('AgentDetails.pdf', 'I');
    }
    else if($_GET["type"]=="emailTrackingLog"){
        $reportTitle = 'Email Tracking Log';

        $_GET['filename'] = $reportTitle;
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        $_GET['pdfy'] = 26;
        $_GET['pdftop'] = 26;
        $_GET['pdfpagebr'] = 12;
        $_GET['pdffonts'] = 7;
        include("../pdfimp2.php");
        $pdf->SetAutoPageBreak(TRUE, 12);

        $where = "1=1";
        if(!empty($_GET["plant_id"])) {
            $plant_id = $conn->real_escape_string($_GET["plant_id"]);
            $where .= " AND plant_id = '$plant_id'";
        }
        $filterClassification = !empty($_GET["classification"]) ? $conn->real_escape_string($_GET["classification"]) : '';
        if($filterClassification !== '') {
            $where .= " AND classification = '$filterClassification'";
        }

        $filterText = $filterClassification !== '' ? 'Classification: '.$filterClassification : 'All Records';

        $colspan = 11;
        $logWidths = array('3', '7', '12', '10', '13', '9', '8', '7', '7', '7', '17');

        $html .= '
        <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:7px;">
            <tr>
                <td colspan="'.$colspan.'" style="text-align:center;font-weight:bold;font-size:11px;border:none;">'.$reportTitle.'</td>
            </tr>
            <tr>
                <td colspan="'.$colspan.'" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').' | Filter: '.$filterText.'</td>
            </tr>
            <tr style="text-align:center;background-color:#0e4370;color:#ffffff;font-weight:bold;">
                <td width="'.$logWidths[0].'%">Sr</td>
                <td width="'.$logWidths[1].'%">Classification</td>
                <td width="'.$logWidths[2].'%">Client Name</td>
                <td width="'.$logWidths[3].'%">Contact Person</td>
                <td width="'.$logWidths[4].'%">Email ID</td>
                <td width="'.$logWidths[5].'%">Phone</td>
                <td width="'.$logWidths[6].'%">Country</td>
                <td width="'.$logWidths[7].'%">State</td>
                <td width="'.$logWidths[8].'%">Place</td>
                <td width="'.$logWidths[9].'%">Website</td>
                <td width="'.$logWidths[10].'%">Created Date &amp; Time</td>
            </tr>';

        $sql = "SELECT * FROM crm_email_tracking WHERE $where ORDER BY created_date_time DESC";
        $result = $conn->query($sql);
        $sr = 1;
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $created = !empty($row['created_date_time']) ? date('d/m/Y H:i', strtotime($row['created_date_time'])) : 'N/A';
                $html .= '
                <tr nobr="true">
                    <td width="'.$logWidths[0].'%" align="center">'.$sr.'</td>
                    <td width="'.$logWidths[1].'%">'.htmlspecialchars($row['classification'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[2].'%">'.htmlspecialchars($row['client_name'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[3].'%">'.htmlspecialchars($row['contact_person'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[4].'%">'.htmlspecialchars($row['email_id'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[5].'%">'.htmlspecialchars($row['phone_number'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[6].'%">'.htmlspecialchars($row['country'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[7].'%">'.htmlspecialchars($row['state'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[8].'%">'.htmlspecialchars($row['place'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[9].'%">'.htmlspecialchars($row['website'] ?: 'N/A').'</td>
                    <td width="'.$logWidths[10].'%">'.$created.'</td>
                </tr>';
                $sr++;
            }
        } else {
            $html .= '
            <tr>
                <td colspan="'.$colspan.'" align="center">No email tracking records found</td>
            </tr>';
        }
        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EmailTrackingLog.pdf', 'I');
    }
    else if($_GET["type"]=="emailTrackingLogDetail"){
        $emailId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if($emailId <= 0) {
            echo "Invalid email id";
            exit;
        }

        $_GET['filename'] = 'Email Tracking Details';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'P';
        $_GET['pdfy'] = 30;
        $_GET['pdftop'] = 30;
        $_GET['pdfpagebr'] = 15;
        $_GET['pdffonts'] = 9;
        include("../pdfimp2.php");
        $pdf->SetAutoPageBreak(TRUE, 15);

        $sql = "SELECT * FROM crm_email_tracking WHERE id='".$emailId."' LIMIT 1";
        $result = $conn->query($sql);
        if(!$result || $result->num_rows === 0) {
            echo "Email record not found";
            exit;
        }
        $row = $result->fetch_assoc();
        $created = !empty($row['created_date_time']) ? date('d/m/Y H:i', strtotime($row['created_date_time'])) : 'N/A';
        $modified = !empty($row['modified_date_time']) ? date('d/m/Y H:i', strtotime($row['modified_date_time'])) : 'N/A';

        $fields = array(
            array('Classification', $row['classification'] ?: 'N/A'),
            array('Client Name', $row['client_name'] ?: 'N/A'),
            array('Contact Person', $row['contact_person'] ?: 'N/A'),
            array('Email ID', $row['email_id'] ?: 'N/A'),
            array('Alternate Email', $row['alternate_email'] ?: 'N/A'),
            array('Phone Number', $row['phone_number'] ?: 'N/A'),
            array('Alternate Phone', $row['alternate_phone'] ?: 'N/A'),
            array('Website', $row['website'] ?: 'N/A'),
            array('Country', $row['country'] ?: 'N/A'),
            array('State', $row['state'] ?: 'N/A'),
            array('Place', $row['place'] ?: 'N/A'),
            array('HR Remark', $row['hr_remark'] ?: 'N/A'),
            array('Subject', $row['subject'] ?: 'N/A'),
            array('Status', $row['status'] ?: 'N/A'),
            array('Email Body', $row['email_body'] ?: 'N/A'),
            array('Remarks', $row['remarks'] ?: 'N/A'),
            array('Followup Required', $row['followup_required'] ?: 'N/A'),
            array('Followup Date', $row['followup_date'] ?: 'N/A'),
            array('Followup Time', $row['followup_time'] ?: 'N/A'),
            array('Response Received', $row['response_received'] ?: 'N/A'),
            array('Response Date', $row['response_date'] ?: 'N/A'),
            array('Response Time', $row['response_time'] ?: 'N/A'),
            array('Response Details', $row['response_details'] ?: 'N/A'),
            array('Created By', $row['created_by'] ?: 'N/A'),
            array('Created Date & Time', $created),
            array('Last Modified By', $row['last_modified_by'] ?: 'N/A'),
            array('Modified Date & Time', $modified),
        );

        $html .= '
        <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:9px;">
            <tr>
                <td colspan="2" style="text-align:center;font-weight:bold;font-size:11px;border:none;">Email Tracking Details</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').'</td>
            </tr>';

        foreach($fields as $field) {
            if($field[1] === 'N/A' && in_array($field[0], array('Alternate Email', 'Alternate Phone', 'HR Remark', 'Subject', 'Status', 'Email Body', 'Remarks', 'Followup Date', 'Followup Time', 'Response Date', 'Response Time', 'Response Details', 'Last Modified By', 'Modified Date & Time'))) {
                continue;
            }
            $html .= '
            <tr nobr="true">
                <td width="30%" style="font-weight:bold;background-color:#f5f7fa;">'.htmlspecialchars($field[0]).'</td>
                <td width="70%">'.htmlspecialchars($field[1]).'</td>
            </tr>';
        }
        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EmailTrackingDetails.pdf', 'I');
    }
} else {
    echo "[]";
}
?>