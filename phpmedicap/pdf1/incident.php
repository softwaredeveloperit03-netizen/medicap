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

    if($_GET["type"]=="incidentlog"){
      $_GET['filename'] = 'Incident Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp.php");  
       $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Sr.</td>
                        <td style="width:15%;">Incident No.</td>
                        <td style="width:15%;">Date</td>
                        <td style="width:15%;">Related To</td>
                        <td style="width:15%;">Incident Type</td>
                        <td style="width:15%;">Department</td>
                        <td style="width:15%;">Status</td>
                    </tr>
                </thead>';
            $i=1;
        	if ($_GET["department"] == "Quality Assurance" || $_GET["department"] == "Management") {
            $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' AND category LIKE '".$_GET["category"]."%' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        } else if($_GET["category"] !== '') {
            $sql = "SELECT *, DATE(entry_date) as entry_date, DATE(close_date) as close_date FROM incident WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND category LIKE '%".$_GET["category"]."%' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }
        $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row['incident_no'].'</td>
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 15%;">'.$row['related_to'].'</td>
                        <td style="width: 15%;">'.$row['type'].'</td>
                        <td style="width: 15%;">'.$row['department'].'</td>
                        <td style="width: 15%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
    else if($_GET["type"]=="incident"){
        $sql = "SELECT * FROM incidentreport WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row['product_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                
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
                <table cellpadding="5">
            	    <tr>
            	        <td style="width:100%; text-align:center; background-color:#DDDAD9;"><b>Annexure II :  Batch Release Certificate </b></td>
            	    </tr>
            	    <br><br>
            	    <tr><td style="text-align:center; text-decoration: underline;"><b>BATCH RELEASE CERTIFICATE</b></td></tr>
        	    </table>
        	    <div><br><br></div>
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:25%;"><b>Product Name</b></td>
        	            <td><b>: '.$row1['product_name'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch No.</b></td>
        	            <td><b>: '.$row['batch_no'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Mfg. Date</b></td>
        	            <td><b>: '.$row['mfg_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Exp. Date</b></td>
        	            <td><b>: '.$row['exp_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch Size</b></td>
        	            <td><b>: '.$row['batch_size'].'</b></td>
        	        </tr>
                </table>
                <p style="line-height:1.7; text-align:justify;">Following documents of this Batch has been reviewed by Quality Assurance department of GMP Software Pvt Ltd</p>
                <ol style="font-weight:bold; line-height:1.7;">
                    <li>Batch Manufacturing Record</li>
                    <li>Batch Packing Record</li>
                    <li>Finished product COA and In process analysis reports along with raw data.</li>
                </ol>
                <p style="line-height:1.7; text-align:justify;">This is to be certified that this batch is meeting all the quality parameters as per specification. This Batch can be distributed on or After Date: ____________<br>This certificate is issued by Quality Assurance Department of GMP Software Pvt Ltd.</p>
                <div></div>
                <table>
                    <br><br><br><br><br><br>
                    <tr>
                        <td><b>Head Quality Assurance</b><br>GMP Software Pvt Ltd</td>
                        <td style="text-align:right;">&nbsp;<br><b>Date : </b>____________</td>
                    </tr>
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
        else{
            echo 'Invalid Release Id';
        }
    }
    else if ($_GET["type"] == "incidentlog") {
        $_GET['filename'] = 'Volumetric Master '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    
                   
                    <td style="width: 25%; ">Solution Name</td>
                    <td style="width: 25%; ">Percentage</td>
                    <td style="width: 25%; ">Strength</td>
                    <td style="width: 25%;">Standard Type</td>
                </tr>
            </thead>';
             $output = Array();
            $sql = "SELECT * FROM volumetric_solution ORDER BY solution_name";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                $html.='<tr nobr="true">
                        
                       
                        <td style="width: 25%; ">'.$row['solution_name'].'</td>
                        <td style="width: 25%; ">'.$row['percentage'].'</td>
                        <td style="width: 25%; ">'.$row['strength'].'</td>
                        <td style="width: 25%; ">'.$row['standard_type'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Volumetric Master .pdf', 'I');
    }
    else if($_GET["type"]=="incidentdigital"){
        $sql = "SELECT * FROM batch_release WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
            $html.='
            <style>
                table tr td {
                    border:none;
                }
            </style>
                <table cellpadding="5">
            	    <tr>
            	        <td style="width:100%; text-align:center; background-color:#DDDAD9;"><b>Annexure II :  Batch Release Certificate </b></td>
            	    </tr>
            	    <br><br>
            	    <tr><td style="text-align:center; text-decoration: underline;"><b>BATCH RELEASE CERTIFICATE</b></td></tr>
        	    </table>
        	    <div><br><br></div>
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:25%;"><b>Product Name</b></td>
        	            <td><b>: '.$row['product'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch No.</b></td>
        	            <td><b>: '.$row['batch_no'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Mfg. Date</b></td>
        	            <td><b>: '.$row['mfg_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Exp. Date</b></td>
        	            <td><b>: '.$row['exp_date'].'</b></td>
        	        </tr>
        	        <tr>
        	            <td><b>Batch Size</b></td>
        	            <td><b>: '.$row['batch_size'].'</b></td>
        	        </tr>
                </table>
                <p style="line-height:1.7; text-align:justify;">Following documents of this Batch has been reviewed by Quality Assurance department of GMP Software Pvt Ltd</p>
                <ol style="font-weight:bold; line-height:1.7;">
                    <li>Batch Manufacturing Record</li>
                    <li>Batch Packing Record</li>
                    <li>Finished product COA and In process analysis reports along with raw data.</li>
                </ol>
                <p style="line-height:1.7; text-align:justify;">This is to be certified that this batch is meeting all the quality parameters as per specification. This Batch can be distributed on or After Date: ____________<br>This certificate is issued by Quality Assurance Department of GMP Software Pvt Ltd.</p>
                <div></div>
                <table>
                    <br><br><br><br><br><br>
                    <tr>
                        <td><b>Head Quality Assurance</b><br>GMP Software Pvt Ltd</td>
                        <td style="text-align:right;">&nbsp;<br><b>Date : </b>____________</td>
                    </tr>
                </table>
            ';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
        else{
            echo 'Invalid Release Id';
        }
    }
} else {
    echo "[]";
}
?>