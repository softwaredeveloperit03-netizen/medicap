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
 if($_GET["type"]=="printworkorder"){
    include 'empdetail.php';
            class MYPDF extends TCPDF {
                public function Header() {
                    $table= '
                    <style>
                        td { border:solid 1px BCBBBA;}
                    </style>
                    <table border="0" cellpadding="5">
                        <tr>
                            <td style="width:30%;"></td>
                            <td style="width:40%; text-align:center;">
                               <img src="../../assets/logo.png" style="height:65px;">
                            </td>
                            <td style="width:30%;"></td>
                        </tr>
                    </table>';
                    $this->SetY(15);
                    $this->writeHTML($table, true, false, false, false, '');
                }
                public function Footer() {
                    $table='
                        <style>
                            td { border:solid 1px BCBBBA;}
                        </style>
                        <table cellpadding="5">
                            <tr style="text-align:center;background-color:#DDDAD9;">
                                <td style="width:33.33%">Prepared by</td>
                                <td style="width:33.33%">Checked By</td>
                                <td style="width:33.33%">Approved By</td>
                            </tr>
                            <tr>
                                <td style="width:10.33%">Dept.</td>
                                <td style="width:23%">'.$_GET['emp_department'].'</td>
                                <td style="width:10.33%">Dept.</td>
                                <td style="width:23%">'.$_GET['emp_department1'].'</td>
                                <td style="width:10.33%">Dept.</td>
                                <td style="width:23%">'.$_GET['emp_department2'].'</td>
                            </tr>
                            <tr>
                                <td style="width:10.33%">Name.</td>
                                <td style="width:23%">'.$_GET['emp_name'].'</td>
                                <td style="width:10.33%">Name.</td>
                                <td style="width:23%">'.$_GET['emp_name1'].'</td>
                                <td style="width:10.33%">Name.</td>
                                <td style="width:23%">'.$_GET['emp_name2'].'</td>
                            </tr>
                            <tr>
                                <td style="width:10.33%">Sign.</td>
                                <td style="width:23%">';
                                    if($_GET['emp_name'] != ''){
                                        $table.='<img src="1.png">';
                                    }else{
                                        $table.='<img src="2.png">';
                                    }
                                $table.='</td>
                                <td style="width:10.33%">Sign.</td>
                                <td style="width:23%">';
                                    if($_GET['emp_name1'] != ''){
                                        $table.='<img src="1.png">';
                                    }else{
                                        $table.='<img src="2.png">';
                                    }
                                $table.='</td>
                                <td style="width:10.33%">Sign.</td>
                                <td style="width:23%">';
                                    if($_GET['emp_name2'] != ''){
                                        $table.='<img src="1.png">';
                                    }else{
                                        $table.='<img src="2.png">';
                                    }
                                $table.='</td>
                            </tr>
                            <tr>
                                <td style="width:10.33%">Date.</td>
                                <td style="width:23%">';
                                    if($_GET['emp_name'] != ''){
                                        $table.=''.date('d/m/Y', strtotime($_GET['emp_entry'])).'';
                                    }
                                $table.='</td>
                                <td style="width:10.33%">Date.</td>
                                <td style="width:23%">';
                                    if($_GET['emp_name1'] != ''){
                                        $table.=''.date('d/m/Y', strtotime($_GET['emp_check1'])).'';
                                    }
                                $table.='
                                </td>
                                <td style="width:10.33%">Date.</td>
                                <td style="width:23%">';
                                    if($_GET['emp_name2'] != ''){
                                        $table.=''.date('d/m/Y', strtotime($_GET['emp_approve'])).'';
                                    }
                                $table.='</td>
                            </tr>
                        </table>';
                        $this->SetY(-50);
                        $this->SetFont('helvetica', 'N', 10);
                        $this->writeHTML($table, true, false, false, false, '');
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
        $pdf->SetLineStyle( array( 'width' => 0.5, 'color' => array(0,0,0)));
    $pdf->SetMargins(5, 5, 5, true);
    $pdf->SetFont ('Times', '', '11' , '', 'default', true );
       
    
    
    $sql = "SELECT * FROM workorder WHERE work_order_id='".$_GET['no']."' ";
    $result = $conn->query($sql);
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        $html.='
        <style>td { border:solid 1px BCBBBA;}</style>
        <h3 style="text-align:center;">Work Order Report</h3>  <br>
            <table cellpadding="5">
            
                 <tr>
                    <td style="width:20%;"><b>Work Order ID :</b></td>
                    <td style="width:30%;">'.$row['work_order_id'].'</td>
                    <td ><b>Client Code :</b></td>
                    <td >'.$row['client_code'].'</td>
                </tr> 
            
                <tr>
                    <td style=" width:20%;"><b>Order Date :</b></td>
                    <td style=" width:30%;"> '.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                    <td ><b>Client Company :</b></td>
                    <td >'.$row['company'].'</td>
                </tr> 
                <tr>
                    <td style=" width:20%;"><b>Email Id :</b></td>
                    <td style=" width:30%;">'.$row['email'].'</td>
                    <td><b> Phone No :</b></td>
                    <td>'.$row['phone'].'</td>
                </tr>
                 <tr>
                    <td style=" width:20%;"><b>Address :</b></td>
                    <td style=" width:80%;">'.$row['address'].'</td>
                </tr>
               
            </table>
            <div></div>
             
             <h4 style="text-align:center;">Work Order For Products</h4>  <br>
            <table cellpadding="5">
                <tr style="text-align:center;">
                  
                    <td><b>Required Date</b></td>
                    <td><b>Product Name</b></td>
                    <td><b>Quantity</b></td>
                    <td><b>Unit</b></td>
                    <td><b>Packing</b></td>
                    <td><b>Dispatch By</b></td>
                    <td><b>Transporter</b></td>
                    <td><b>Country</b></td>
                 
                </tr>';
                
                $sql1 = "SELECT * FROM workorder_products WHERE work_order_id = '".$row["work_order_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                        <tr>
                            <td>'.date('d/m/Y', strtotime($row1['required_date'])).'</td>
                            <td>'.$row1['product_name'].'</td>
                            <td>'.$row1['qty'].'</td>
                            <td>'.$row1['unit'].'</td>
                            <td>'.$row1['packing_style'].'</td>
                            <td>'.$row1['dispatch_by'].'</td>
                            <td>'.$row1['transporter'].'</td>
                            <td>'.$row1['for_country'].'</td>
                        </tr>';
                    }
                }
                $html.='
            </table>
        ';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EnquiryReport.pdf', 'I');
    }
 }

} else {
    echo "[]";
}
?>