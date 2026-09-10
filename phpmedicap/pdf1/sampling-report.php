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
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    if($_GET['type'] == 'samplingReport'){
        include("pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'header';
                    include("pdfimp.php");
                }
                public function Footer() {}
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 45, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 05);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            $pdf->AddPage('L', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $html.='
        <table cellpadding="5" style="text-align:center;">
            <tr>
                <td style="background-color:#DDDAD9;"><h3>SAMPLING REPORT</h3></td>
            </tr>
        </table>
        <div></div>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:9%;">Date</td>
                <td style="width:19%;">Material Name</td>
                <td style="width:9%;">GRN No.</td>
                <td style="width:9%;">Total Containers</td>
                <td style="width:9%;">Sampled Containers</td>
                <td style="width:9%;">Sampled Qty</td>
                <td style="width:9%;">From Time</td>
                <td style="width:9%;">To Time</td>
                <td style="width:9%;">Done By</td>
                <td style="width:9%;">Check By</td>
            </tr>';
            
            $sql = "SELECT * FROM sampling WHERE status='approve'";
            $output = Array();
            $result = $qc->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
                            $html.='
                            <tr>
                                <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                                <td style="text-align:left;">'.$row["material_name"].'</td>
                                <td>'.$row["grn_no"].'</td>
                                <td>'.$row['total_containers'].'</td>
                                <td>'.$row['sampled_containers'].'</td>
                                <td>'.$row['total_qty'].'</td>
                                <td>'.date('d/m/Y', strtotime($row['from_date'])).'</td>
                                <td>'.date('d/m/Y', strtotime($row['to_date'])).'</td>
                                <td>'.$row['entry_by'].'</td>
                                <td>'.$row['check_by'].'</td>
                            </tr>';
                        }
                    }
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PurchaseOrder.pdf', 'I');
    }
    else if($_GET['type'] == 'purchaseReport'){
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
        <table cellpadding="5" style="text-align:center;">
            <tr>
                <td style="background-color:#DDDAD9;"><h3>Annexure I:  Purchase order Report</h3></td>
            </tr>
        </table>
        <div></div>';
        $sql = "SELECT * FROM purchaseorder WHERE po_no = '".$_GET['pono']."'";
        $result = $purchase->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                $result1 = $purchase->query($sql1);
                if ($result1->num_rows > 0) {
                    while($row1 = $result1->fetch_assoc()) {
                        $row["vendor_name"] = $row1["vendor_name"];
                    }
                }
                $html.='
                <table cellpadding="5">
                    <tr>
                        <td style="width:30%;">Vendor Name:</td>
                        <td style="width:70%;">'.$row["vendor_name"].'</td>
                    </tr>
                    <tr>
                        <td>Discount (A.P. Quotation):</td>
                        <td>'.$row["discount"].'</td>
                    </tr>
                    <tr>
                        <td>GST Type:</td>
                        <td>'.$row["gst_type"].'</td>
                    </tr>
                    <tr>
                        <td>Requirement Days:</td>
                        <td>'.$row["requirement_days"].'</td>
                    </tr>
                    <tr>
                        <td>Delivery Schedule Date:</td>
                        <td>'.date('d/m/Y', strtotime($row["delivery_schedule_date"])).'</td>
                    </tr>
                    <tr>
                        <td>Delivery Address:</td>
                        <td>'.$row["delivery_address"].'</td>
                    </tr>
                </table>
                <h4>Materials:</h4>';
                
                $sql1 = "SELECT * FROM po_material WHERE po_no='".$row["po_no"]."'";
                $result1 = $purchase->query($sql1);
                $output1 = Array();
                if ($result1->num_rows > 0) {
                    $html.='
                    <table cellpadding="5" style="text-align:center;">
                        <tr style="background-color:#DDDAD9;">
                            <td>Material Type</td>
                            <td>Material Name</td>
                            <td>Grade</td>
                            <td>Required Qty.</td>
                            <td>Unit</td>
                            <td>Requirement</td>
                            <td>Quotation No</td>
                        </tr>';
                    while($row1 = $result1->fetch_assoc()) {
                        if ($row["isgeneral"] == 'yes') {
                            $sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["material_name"] = $row2["material_name"];
                                    $row1["material_type"] = $row2["material_type"];
                                    $row1["grade"] = "-";
                                    $row1["unit"] = "-";
                                }
                            }
                        } else {
                            $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["material_name"] = $row2["material_name"];
                                    $row1["material_type"] = $row2["material_type"];
                                    $row1["material_subtype"] = $row2["material_subtype"];
                                    $row1["grade"] = $row2["grade"];
                                    $row1["unit"] = $row2["unit"];
                                }
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$row1["material_type"].'</td>
                            <td>'.$row1["material_name"].'</td>
                            <td>'.$row1["grade"].'</td>
                            <td>'.$row1["required_qty"].'</td>
                            <td>'.$row1["unit"].'</td>
                            <td>'.$row1["requirement"].'</td>
                            <td>'.$row1["quotation"].'</td>
                        </tr>
                        ';
                    }
                    $html.='
                    </table>
                    <h4>Terms & Conditions:</h4>
                    <table cellpadding="5">
                        <tr>
                            <td>'.$row["terms_conditions"].'</td>
                        </tr>
                    </table>
                    <div></div>
                    ';
                    require '../footer.php';
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('PurchaseOrder.pdf', 'I');
        }
        else{
            echo "no record";
        }
    }
    else if($_GET['type'] == 'quotationAllReport'){
        $_GET['type'] = 'empdetail';
            include("pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'header';
                    include("pdfimp.php");
                }
            public function Footer() {}
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
        
        <table cellpadding="5" style="text-align:center;">
            <thead>
                <tr>
                    <td style="background-color:#DDDAD9;"><h3>QUOTATION REPORT</h3></td>
                </tr>
            </thead>
            <thead>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:13%;">Quotation No.</td>
                    <td style="width:48%;">Vendor Name</td>
                    <td style="width:13%;">No. Of Item</td>
                    <td style="width:13%;">Entry By</td>
                    <td style="width:13%;">Prepare Date</td>
                </tr>
            </thead>
            <tbody>
            ';
            if($_GET['from'] == '' && $_GET['to'] == ''){
                $sql = "SELECT * FROM quotation WHERE status='approve'";
            }else{
                $sql = "SELECT * FROM quotation WHERE entry_date BETWEEN '" . $_GET['from'] . "' AND  '" . $_GET['to'] . "' AND status='approve'";
            }
            $result = $purchase->query($sql);
            $output = Array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                    $result1 = $purchase->query($sql1);
                    if ($result1->num_rows > 0) {
                        while($row1 = $result1->fetch_assoc()) {
                            $row["vendor_name"] = $row1["vendor_name"];
                        }
                    }
                    $html.='
                    <tr nobr="true">
                        <td style="width:13%;">'.$row["quotation_no"].'</td>
                        <td style="width:48%; text-align:left;">'.$row["vendor_name"].'</td>
                        <td style="width:13%;">'.$row["total_items"].'</td>
                        <td style="width:13%;">'.$row['entry_by'].'</td>
                        <td style="width:13%;">'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                    </tr>';
                }
            }
            $html.='
            <tbody>
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuotationReport.pdf', 'I');
    }
}else {
    echo "Invalid Token";
}
?>