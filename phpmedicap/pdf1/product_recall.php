<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);

$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);


    if($_GET["type"]=="generateRecallPDF") {
        $sql = "SELECT * FROM product_recall WHERE id = ".$_GET["id"];
        $result = $conn->query($sql);
        
        if ($result -> num_rows > 0) {
            while ($row = $result -> fetch_assoc()) {
                
                class MYPDF extends TCPDF {
                    public function Header() {
                    }
                    public function Footer() {
                        $this->SetY(-18);
                        $this->SetFont('Times', '', 10);
                        $this->Cell(0, 10, '', 0, false, 'L', 0, '', 0, false, 'T', 'M');
                        $this->Cell(0, 10, 'Page No. : '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 15, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->SetFont('times', '', 12);
                $pdf->AddPage();
                
                $image1 = '<img src="1.png" style="width:15px; height:15px;">';
                $image2 = '<img src="2.png" style="width:15px; height:15px;">';
                
                if ($row["mock_recall"] == "true") {
                    $mock_recall = $image1;
                } else {
                    $mock_recall = $image2;
                }
                
                if ($row["public_recall"] == "true") {
                    $public_recall = $image1;
                } else {
                    $public_recall = $image2;
                }
                
                if ($row["professional_recall"] == "true") {
                    $professional_recall = $image1;
                } else {
                    $professional_recall = $image2;
                }
                
                if ($row["enforcement_auth_recall"] == "true") {
                    $enforcement_auth_recall = $image1;
                } else {
                    $enforcement_auth_recall = $image2;
                }
                
                if ($row["television"] == "true") {
                    $television = $image1;
                } else {
                    $television = $image2;
                }
                
                if ($row["radio"] == "true") {
                    $radio = $image1;
                } else {
                    $radio = $image2;
                }
                
                if ($row["newspaper"] == "true") {
                    $newspaper = $image1;
                } else {
                    $newspaper = $image2;
                }
                
                if ($row["cfagents"] == "true") {
                    $cfagents = $image1;
                } else {
                    $cfagents = $image2;
                }
                
                if ($row["stockiest"] == "true") {
                    $stockiest = $image1;
                } else {
                    $stockiest = $image2;
                }
                
                if ($row["distributors"] == "true") {
                    $distributors = $image1;
                } else {
                    $distributors = $image2;
                }
                
                if ($row["distributors"] == "true") {
                    $distributors = $image1;
                } else {
                    $distributors = $image2;
                }
                
                if ($row["mfg_error"] == "true") {
                    $mfg_error = $image1;
                } else {
                    $mfg_error = $image2;
                }
                
                if ($row["packing_error"] == "true") {
                    $packing_error = $image1;
                } else {
                    $packing_error = $image2;
                }
                
                if ($row["side_effect"] == "true") {
                    $side_effect = $image1;
                } else {
                    $side_effect = $image2;
                }
                
                if ($row["degradation"] == "true") {
                    $degradation = $image1;
                } else {
                    $degradation = $image2;
                }
                
                if ($row["abnormal_stability"] == "true") {
                    $abnormal_stability = $image1;
                } else {
                    $abnormal_stability = $image2;
                }
                
                if ($row["contamination_product"] == "true") {
                    $contamination_product = $image1;
                } else {
                    $contamination_product = $image2;
                }
                
                if ($row["release_change"] == "true") {
                    $release_change = $image1;
                } else {
                    $release_change = $image2;
                }
                
                $html='
                <style>
                    th { border:solid 1px BCBBBA; }
                    td { border:solid 1px BCBBBA; }
                    .td1 { width:5%;  border:none; }
                    .td2 { width:95%;  border:none; }
                </style>
                
                <table border="0" cellpadding="5" style="text-align:left; vertical-align:middle;">
                    <tbody>
                    
                        <tr>
                            <th></th>
                            <th><img src="header.png" style="height:50px;"></th>
                            <th></th>
                        </tr>
                        <tr><br></tr>
                        <tr>
                            <th style="background-color:#DDDAD9; width:100%; text-align:center;">PRODUCT RECALL</th>
                        </tr>
                        <tr><br></tr>
                        
                        <tr>
                            <th style="width:40%;"><b>Details of product to be recalled</b></th>
                            <th style="width:60%;"></th>
                        </tr>
                        <tr>
                            <th style="width:40%;"><b>Name of the product to be recalled</b></th>
                            <th style="width:60%;">'.$row["product_name"].'</th>
                        </tr>
                        <tr>
                            <th style="width:40%;"><b>Batch No</b></th>
                            <th style="width:60%;">'.$row["batch_no"].'</th>
                        </tr>
                        <tr>
                            <th style="width:15%;"><b>Date of MFG</b></th>
                            <th style="width:25%;">'.$row["mfg_date"].'</th>
                            <th style="width:20%;"><b>Date of expiry</b></th>
                            <th style="width:40%;">'.$row["exp_date"].'</th>
                        </tr>
                        <tr>
                            <th style="width:40%;"><b>Recall Co-ordinator</b></th>
                            <th style="width:60%;">'.$row["coordinator"].'</th>
                        </tr>
                        <tr>
                            <th style="width:40%;"><b>Company Person Nominated</b></th>
                            <th style="width:60%;">'.$row["company_person"].'</th>
                        </tr>
                        <tr>
                            <th style="width:40%;"><b>Person nominated on behalf of client</b></th>
                            <th style="width:60%;">'.$row["clients_person"].'</th>
                        </tr>
                        <tr>
                            <th style="width:15%;"><b>Email</b></th>
                            <th style="width:25%;">'.$row["email"].'</th>
                            <th style="width:20%;"><b>Phone</b></th>
                            <th style="width:40%;">'.$row["mobile"].'</th>
                        </tr>
                        
                        <tr><br></tr>
                        
                        <tr>
                            <th style="width:100%;"><b>Type of Recall</b></th>
                        </tr>
                        
                        <tr>
                            <th style="width:5%; text-align: center;">'.$mock_recall.'</th>
                            <th style="width:15%;">Mock Recall</th>
                            
                            <th style="width:5%; text-align: center;">'.$public_recall.'</th>
                            <th style="width:15%;">Public Recall</th>
                            
                            <th style="width:5%; text-align: center;">'.$professional_recall.'</th>
                            <th style="width:20%;">Professional Recall</th>
                            
                            <th style="width:5%; text-align: center;">'.$enforcement_auth_recall.'</th>
                            <th style="width:30%;">Recall as directed by enforcement authorities</th>
                        </tr>
                        
                        <tr><br></tr>
                        
                        <tr>
                            <th style="width:100%;"><b>Implementation Method Adopted</b></th>
                        </tr>
                        <tr>
                            <th style="width:50%; text-align: center;"><b>Urgent</b></th>
                            <th style="width:50%; text-align: center;"><b>Restricted</b></th>
                        </tr>
                        <tr>
                            <th style="width:5%; text-align: center;">'.$television.'</th>
                            <th style="width:12%;">Television</th>
                            
                            <th style="width:5%; text-align: center;">'.$radio.'</th>
                            <th style="width:10%;">Radio</th>
                            
                            <th style="width:5%; text-align: center;">'.$newspaper.'</th>
                            <th style="width:13%;">Newspaper</th>
                            
                            <th style="width:4%; text-align: center;">'.$cfagents.'</th>
                            <th style="width:14%;">C&F agents</th>
                            
                            <th style="width:4%; text-align: center;">'.$stockiest.'</th>
                            <th style="width:11%;">Stockiest</th>
                            
                            <th style="width:4%; text-align: center;">'.$distributors.'</th>
                            <th style="width:13%;">Distributors</th>
                        </tr>
                        
                        <tr><br></tr>
                        <tr>
                            <th style="width:100%;"><b>Reason for Recall</b></th>
                        </tr>
                        <tr>
                            <th style="width:5%;">'.$mfg_error.'</th>
                            <th style="width:20%;">Manufacturing error</th>
                            
                            <th style="width:5%;">'.$packing_error.'</th>
                            <th style="width:20%;">Packaging error</th>
                            
                            <th style="width:5%;">'.$side_effect.'</th>
                            <th style="width:20%;">Unforseen side effect</th>
                            
                            <th style="width:5%;">'.$abnormal_stability.'</th>
                            <th style="width:20%;">Dosage Error</th>
                        </tr>
                        <tr>
                            <th style="width:5%;">'.$degradation.'</th>
                            <th style="width:20%;">Degradation of active ingredient</th>
                            
                            <th style="width:5%;">'.$abnormal_stability.'</th>
                            <th style="width:20%;">Abnormal stability results</th>
                            
                            <th style="width:5%;">'.$contamination_product.'</th>
                            <th style="width:20%;">Contamination of product</th>
                            
                            <th style="width:5%;">'.$release_change.'</th>
                            <th style="width:20%;">Change in Release pattern</th>
                        </tr>
                        <tr>
                            <th style="width:5%;">'.$television.'</th>
                            <th style="width:20%;">Defect of packaging</th>
                            
                            <th style="width:5%;">'.$television.'</th>
                            <th style="width:20%;">Overprinting error - price</th>
                            
                            <th style="width:5%;">'.$television.'</th>
                            <th style="width:20%;">Overprinting error - Batch No.</th>
                            
                            <th style="width:5%;">'.$television.'</th>
                            <th style="width:20%;">Overprinting error - Date of expiry</th>
                        </tr>
                        
                        <tr><br></tr>
                        <tr>
                            <th style="width:40%;"><b>Any Other Reason</b></th>
                            <th style="width:60%;">'.$row["other_reason"].'</th>
                        </tr>
                        
                        <tr>
                            <th style="width:40%;"><b>Recalled Product to be receive at</b></th>
                            <th style="width:60%;">attached below</th>
                        </tr>
                        
                        <tr>
                            <th style="width:40%;"><b>Quantities received against distributed location-wise</b></th>
                            <th style="width:60%;">attached below</th>
                        </tr>
                        
                        <tr>
                            <th style="width:40%;"><b>Recalled goods received examination report</b></th>
                            <th style="width:60%;">attached below</th>
                        </tr>
                        
                        <tr>
                            <th style="width:40%;"><b>Recalled goods destruction report as per current SOP for ‘Destruction of Finished Products’</b></th>
                            <th style="width:60%;">attached below</th>
                        </tr>
                        
                        <tr><br></tr>
                        
                        <tr>
                            <th style="width:33.33%; text-align:center;">
                                <b>Recall coordinator Name</b><br>
                                '.$row["coordinator"].'
                            </th>
                            <th style="width:33.33%; text-align:center;">Sign<br><br></th>
                            <th style="width:33.33%; text-align:center;">Date<br><br></th>
                        </tr>
                        
                        <tr>
                            <th style="width:33.33%; text-align:center;"><b>QA Manager Name</b></th>
                            <th style="width:33.33%; text-align:center;">Sign<br><br></th>
                            <th style="width:33.33%; text-align:center;">Date<br><br></th>
                        </tr>
                        
                        <tr>
                            <th style="width:33.33%; text-align:center;"><b>Director</b></th>
                            <th style="width:33.33%; text-align:center;">Sign<br><br></th>
                            <th style="width:33.33%; text-align:center;">Date<br><br></th>
                        </tr>
                        
                        <tr><br></tr>';
                        $html.='
                    </tbody>
                </table>
                ';
                $pdf->writeHTML($html, true, false, true, false, '');
                $file = $file_no.'.pdf';
                $pdf->Output(dirname(__FILE__).'/upload/recall/'.$file, 'I');
                
                
            }
        }
    } else if($_GET["type"]=="generateAdvicePDF") {
        $sql = "SELECT * FROM marketing_advice WHERE id = ".$_GET["id"];
        $result = $conn->query($sql);
        
        if ($result -> num_rows > 0) {
            while ($row = $result -> fetch_assoc()) {
                
                class MYPDF extends TCPDF {
                    public function Header() {
                    }
                    public function Footer() {
                        $this->SetY(-18);
                        $this->SetFont('Times', '', 10);
                        $this->Cell(0, 10, '', 0, false, 'L', 0, '', 0, false, 'T', 'M');
                        $this->Cell(0, 10, 'Page No. : '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 15, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->SetFont('times', '', 12);
                $pdf->AddPage();
                
                $html = '
                <style>
                    p, ol {
                        font-size:14px;
                        font-weight: 200;
                    }
                </style>
                
                <br><br><br>
                
                <h3>
                    Date,<br><label>'.$date.'
                </h3>
                
                <h3>
                    To,
                    <br>
                    <label>'.$row["name"].'</label>
                    <br>
                </h3>
                
                <br>
                
                <h3 style="padding-left: 20px;">This is to inform you to URGENTLY</h3> <br>
                <ol>
                    
                    <li> STOP sales/distribution of Batch No. '.$row["batch_no"].' </li>
                    <li> Inform us immediately the quantity held by you.</li>
                    <li> Urgently inform your customers to STOP sales / distribution of this batch and obtain confirmation. Find out the quantity held by your customer and immediately inform us.</li>
                    <li> Please ensure stocks held by you and your customers are secure. Await further written instructions from us.</li> 
                    <li> Please acknowledge receipt of this message and request your prompt and complete co-operation.</li> 
                </ol> 
                
                <br>
                <p style="padding-top:15px;">
                    Authorized Signatory <br>
                </p> ';
                
                $pdf->writeHTML($html, true, false, true, false, '');
                $file = 'demo.pdf';
                $pdf->Output(dirname(__FILE__).'/upload/'.$file, 'I');
                
                
            }
        }
    }
    
} else {
    echo "[]";
}
?>