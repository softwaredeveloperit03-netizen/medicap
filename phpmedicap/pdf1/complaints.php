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

    if($_GET['type']=='marketcomplaint'){
        $sql = "SELECT * FROM market_complaint WHERE complaint_no='".$_GET['complaintno']."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
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
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            while($row = $result->fetch_assoc()){
                $html.='
                <table cellpadding="5" style="text-align:center;">
                    <tr>
                        <td style="background-color:#DDDAD9;"><b>Annexure I :   Market Complaint Investigation Report</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td style="background-color:#DDDAD9; text-align:center;">Market Complaint Investigation Report </td>
                    </tr>
                    <tr>
                        <td style="width:20%;">Compliant No:</td>
                        <td style="width:30%;">'.$row['complaint_no'].'</td>
                        <td style="width:20%;">Received On :</td>
                        <td style="width:30%;">'.date('d/m/Y', strtotime($row['received_date'])).'</td>
                    </tr>
                    <tr>
                        <td>Complaint Received By </td>
                        <td>'.$row['entry_by'].'</td>
                        <td>Forwarded to QA Department On</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Nature of Complaint:</b></td>
                        <td style="width:80%;">'.$row['complaint_nature'].'</td>
                    </tr>
                    <tr>
                        <td rowspan="2"><b>Compliant Details</b></td>
                        <td style="border:none; width:50%;"><b>Name of Product:</b> '.$row['product_name'].'</td>
                        <td style="border:none; width:30%; border-right:1px solid gray;"><b>Batch No:</b> '.$row['batch_no'].'</td>
                    </tr>
                    <tr>
                        <td style="border:none;"><b>Mfg. Date:</b> '.$row['mfg_date'].'</td>
                        <td style="border:none; border-right:1px solid gray;"><b>Exp. Date:</b> '.$row['exp_date'].'</td>
                    </tr>
                    <tr>
                        <td><b>Inspection of Complaint and Control  Sample</b></td>
                        <td style="width:80%;"><b>Inspection Remark Complaint Sample:</b>'.$row['exp_date'].'<br><br><b>Inspection Remark Control Sample:</b><br><b>Head QA/Designee Sign & Date</b></td>
                    </tr>
                    <tr>
                        <td><b>Primary Observation by Head QA</b></td>
                        <td><b>Remark:'.$row['exp_date'].'</b></td>
                    </tr>
                    <tr>
                        <td><b>Product Review :</b></td>
                        <td><b>Following Manufacturing and Analytical Documents Reviewed :</b></td>
                    </tr>
                    <tr>
                        <td rowspan="2"><b>Cross Functional Technical Review and Remark </b></td>
                        <td style="width:26%">Department</td>
                        <td style="width:27%">Remark</td>
                        <td style="width:27%">Sign /Date</td>
                    </tr>
                    <tr>
                        <td>'.$row['department'].'</td>
                        <td>'.$row['remark'].'</td>
                        <td>'.$row['date'].'</td>
                    </tr>
                    <tr>
                        <td><b>Confirmation of Complaint</b></td>
                        <td style="width:80%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;"><b>Complaint Closed Recommended For Further Investigation<br>Remark:</b></td>
                    </tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td style="background-color:#DDDAD9; text-align:center;">Market Complaint Investigation Report (PART II)</td>
                    </tr>
                    <tr>
                        <td style="width:20%;"></td>
                        <td style="width:30%;"><b>Impact Element</b></td>
                        <td style="width:50%;"><b>Impact Nature</b></td>
                    </tr>
                    <tr>
                        <td rowspan="5"><b>Impact Assessment</b></td>
                        <td><b>Previous Batches</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Next batches</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Similar Product</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Health and Safety</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Environmental Impact</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Risk Assessment (If Any)</b></td>
                        <td style="width:80%;"></td>
                    </tr>
                    <tr nobr="true">
                        <td><b>Immediate Corrective Action Taken</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Preventive Action</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Remark of Medical Department /Expert</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="width:100%;"><b>Complaint Closed<br>Remark:</b></td>
                    </tr>
                </table>
                <div></div>';
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MarketComplaint.pdf', 'I');
    }
    else if($_GET["type"]=="marketComplaintLog"){
        $sql = "SELECT * FROM market_complaint";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
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
            <style>
                td { border:solid 1px BCBBBA;}
            </style>
                <table cellpadding="5" style="text-align:center;">
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:100%; text-align:center;"><b>Market Complaint Log</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:10%;">Sr</td>
                        <td style="width:20%;">Received Date</td>
                        <td style="width:20%;">Complaint Nature</td>
                        <td style="width:20%;">Complaint Sample</td>
                        <td style="width:20%;">Product Name</td>
                        <td style="width:10%;">Batch No</td>
                    </tr>';
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td>'.$counter++.'</td>
                        <td>'.date('d/m/Y', strtotime($row['received_date'])).'</td>
                        <td>'.$row['complaint_nature'].'</td>
                        <td>'.$row['complaint_sample'].'</td>
                        <td>'.$row["product_name"].'</td>
                        <td>'.$row["batch_no"].'</td>
                    </tr>
            ';
            }
            $html.='
                </table>
            ';
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('changecontrol.pdf', 'I');
        }
        else{
            echo "No Records Found";
        }
    }
    else if($_GET["type"]=="medicalComplaintLog"){
        $sql = "SELECT * FROM medical_complaint";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
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
            $pdf->AddPage('L', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html.='
            <style>
                td { border:solid 1px BCBBBA;}
            </style>
                <table cellpadding="5" style="text-align:center;">
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:100%; text-align:center;"><b>Medical Complaint Log</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:10%;">Sr</td>
                        <td style="width:20%;">Received Date</td>
                        <td style="width:20%;">Complaint Nature</td>
                        <td style="width:20%;">Complaint Sample</td>
                        <td style="width:20%;">Product Name</td>
                        <td style="width:10%;">Batch No</td>
                    </tr>';
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td>'.$counter++.'</td>
                        <td>'.date('d/m/Y', strtotime($row['received_date'])).'</td>
                        <td>'.$row['complaint_nature'].'</td>
                        <td>'.$row['complaint_sample'].'</td>
                        <td>'.$row["product_name"].'</td>
                        <td>'.$row["batch_no"].'</td>
                    </tr>
            ';
            }
            $html.='
                </table>
            ';
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('changecontrol.pdf', 'I');
        }
        else{
            echo "No Records Found";
        }
    }

} else {
    echo "[]";
}
?>