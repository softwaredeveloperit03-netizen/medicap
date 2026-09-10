<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
require '../phpmailer/class.phpmailer.php';

$token = $_GET["token"];
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
    
    $sql = "SELECT * FROM raw_material WHERE id='".$_GET["id"]."'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $file = $row['grn_no'].'.pdf';
            $grn_details = json_decode($row['grn_details']);
            $inword_details = json_decode($row['inword_details']);
            $sql2 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result2 = $conn->query($sql2);
            $row2 = $result2->fetch_assoc();
            include("pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'headerlandscape';
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
            $pdf->AddPage('L', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5" style="text-align:center;">
                <tr>
                    <td style="background-color:#DDDAD9;">Annexure I : Goods Receipt Note</td>
                </tr>
            </table>
            <br><br>
            <table cellpadding="5" style="text-align:left;">
                <tr>
                    <td style="width:15%">GRN No</td>
                    <td style="width:3%">:</td>
                    <td style="width:32%"> GRN-'.$row['id'].'</td>
                    <td style="width:15%">GRN Date</td>
                    <td style="width:3%">:</td>
                    <td style="width:32%">'.date("d/m/Y", strtotime($row['entry_date'])).'</td>
                </tr>
                <tr>
                    <td>Name of Manufacture</td>
                    <td>:</td>
                    <td>'.$row5['manufacturer'].'</td>
                    <td>Date of Receipt</td>
                    <td>:</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Name of Supplier</td>
                    <td>:</td>
                    <td>'.$row2['vendor_name'].'</td>
                    <td>Gate inward No</td>
                    <td>:</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Challan Number</td>
                    <td>:</td>
                    <td>'.$inword_details->challan_no.'</td>
                    <td>Date</td>
                    <td>:</td>
                    <td>'.$inword_details->challan_date.'</td>
                </tr>
                <tr>
                    <td>P.O Number</td>
                    <td>:</td>
                    <td>'.$inword_details->po_no.'</td>
                    <td>Date</td>
                    <td>:</td>
                    <td>'.$inword_details->po_date.'</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="4" style="text-align:center;">
                <tr>
                    <td style="width:7%">Item code</td>
                    <td style="width:20%">Material Description</td>
                    <td style="width:7%">Unit</td>
                    <td style="width:7%">Mfg Batch No.</td>
                    <td style="width:7%">No. of Containers</td>
                    <td style="width:8%">Challan Qty.</td>
                    <td style="width:8%">Received Qty.</td>
                    <td style="width:8%">Short Receipt Qty.</td>
                    <td style="width:7%">Rejected Qty.</td>
                    <td style="width:7%">Accepted Qty.</td>
                    <td style="width:7%">Manufacturing Date</td>
                    <td style="width:7%">Expiry Date</td>
                </tr>
                <tr>
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row['unit'].'</td>
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row['containers'].'</td>
                    <td>'.$row['challan_qty'].'</td>
                    <td>'.$row['received_qty'].'</td>
                    <td>'.$grn_details->short_qty.'</td>
                    <td>'.$grn_details->reject_qty.'</td>
                    <td>'.$row['accept_qty'].'</td>
                    <td>'.date('m/Y', strtotime($row['mfg_date'])).'</td>
                    <td>'.date('m/Y', strtotime($row['exp_date'])).'</td>
                </tr>
            </table>
            <div><br>Analytical Details<br></div>
            <table cellpadding="4" style="text-align:center;">
                <tr>
                    <td style="width:7%">Mfg Batch No.</td>
                    <td style="width:15%">No. of Containers</td>
                    <td style="width:7%">Unit</td>
                    <td style="width:10%">Received Qty.</td>
                    <td style="width:10%">Rejected Qty.</td>
                    <td style="width:10%">Accepted Qty.</td>
                    <td style="width:10%">A.R.No</td>
                    <td style="width:10%">Status Apporved / Rejected</td>
                    <td style="width:10%">Date</td>
                    <td style="width:11%">Remark if Any</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
            <div><br><br></div>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        if($_GET['type'] == 'sendmail'){
            $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
            $email = $_GET['email'];
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
            $mail->Subject = "GRN PDF";
            $mail->Body = "Please Find Attachment";
            $mail->AddAttachment($file);
            $mail->AddAddress($email);
            if ($mail->Send()) {
                echo "{\"status\":\"success\"}";
                unlink($file);
            } else {
                echo "{\"status\":\"failed\"}";
            }
        }else{
            $pdf->Output($file, 'I');
        }
    }else{
        echo "Invalid GRN No.";
    }
}else {
    echo "Invalid Token";
}
?>