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
    
    if($_GET['type'] == ''){
        $sql = "SELECT * FROM sop_generate where sop_no='".$_GET['sop_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $training = json_decode($row["training"]);
                $distribution = json_decode($row["distribution"]);
                $revision = json_decode($row["revision"]);
                
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
                        $this->SetY(-10);
                        $this->Cell(0, 10, 'Format No. : '.$sop_no.'-02-F1', 0, false, 'L', 0, '', 0, false, 'T', 'M');
                        $this->Cell(0, 10, 'Page No. : '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                    }
                } 
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 55);
                $pdf->SetFont('times', '', 12);
                $pdf->AddPage();
                $pdf->SetY(45);
                
                $html='
                <style>
                ol{       counter-reset: item     }      ol > li{       display: block     }      ol > li:before {       content: counters(item, ".") " ";       counter-increment: item     }
                th { border:solid 1px BCBBBA; }
                td { border:solid 1px BCBBBA; }
                .td1 { width:5%;  border:none; }
                .td2 { width:95%;  border:none; }
                @use postcss-nested;
                ol {
                    list-style: none;
	                counter-reset: item;
            	    li {
            	        counter-increment: item;
            	        &:before {
                            margin-right: 10px;
                            content: counters(item, ".") " ";
                            display: inline-block;
                        }
                    }
                }
                </style>';
                $html .= '
                <table border="0" cellpadding="5" style="text-align:left; vertical-align:middle;">
                    <thead class="tablehead">
                        <tr>
                            <th style="background-color:#DDDAD9; width:100%; text-align:center;"><b>STANDARD OPERATING PROCEDURE</b></th>
                        </tr>
                        <tr><br></tr>
                        <tr>
                            <th style="width:16.66%;"><b>Department</b></th>
                            <th style="width:33.34%;">'.$row['department'].'</th>
                            <th style="width:16.66%;"><b>Copy No</b></th>
                            <th style="width:33.34%;">'.$row["copy_no"].'</th>
                        </tr>
                        <tr>
                            <th style="width:100%;"><b>Title - '.$row["title"].'</b></th>
                        </tr>
                        <tr>
                            <td style="width:16.66%;"><b>SOP No.</b></td>
                            <td style="width:17.66%;">'.$row['sop_no'].'</td>
                            <td style="width:15.66%;"><b>Revision No.</b></td>
                            <td style="width:16.66%;">'.$row["revision_no"].'</td>
                            <td style="width:16.66%;"><b>Effective Date</b></td>
                            <td style="width:16.66%;">'.$row["effective_date"].'</td>
                        </tr>
                        <tr>
                            <td><b>Supersede No.</b></td>
                            <td>'.$row["supersed_no"].'</td>
                            <td><b>Version No.</b></td>
                            <td>00</td>
                            <td><b>Next Review Date</b></td>
                            <td>'.$row["next_review_date"].'</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><br></tr>
                        <tr>
                            <td style="border:none; text-align:center;"><b>Annexure I : SOP format</b></td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">1.</td>
                            <td class="td2">
                                <b>Purpose :</b><br>
                                '.$row["purpose"].'
                            </td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">2.</td>
                            <td class="td2"><b>Scope :</b><br>'.$row["scope"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">3.</td>
                            <td class="td2"><b>Role and Responsibility :</b><br>'.$row["role"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">4.</td>
                            <td class="td2"><b>Definition :</b><br>'.$row["definition"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">5.</td>
                            <td class="td2"><b>External References And Associated Documents :</b><br>'.$row["reference"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">6.</td>
                            <td class="td2"><b>Process Overview :</b><br>'.$row["process"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">7.</td>
                            <td class="td2"><b>Procedure :</b><br>'.$row["procedures"].'</td>
                        </tr>
                        <tr>
                            <td class="td1">8.</td>
                            <td class="td2"><b>Abbreviation :</b><br>'.$row["abbreviation"].'</td>
                        </tr>
                        <tr nobr="true">
                            <td class="td1">9.</td>
                            <td class="td2"><b>Training Requirement :</b><br><br>
                                <table cellpadding="5">
                                    <tr>
                                        <td style="width:10%">Sr No.</td>
                                        <td style="width:90%">Department</td>
                                    </tr>';
                                    $j = 0;
                                    for($i =1; $i <= count($training);){
                                        $html.='
                                        <tr>
                                            <td>'.$i++.'</td>
                                            <td>'.$training[$j++].'</td>
                                        </tr>';
                                    }
                                    $html.='
                                </table>
                            </td>
                        </tr>
                        <tr nobr="true">
                            <td class="td1">10.</td>
                            <td class="td2"><b>Distribution :</b><br><br>
                                <table cellpadding="5">
                                    <tr>
                                        <td style="width:10%">Sr No.</td>
                                        <td style="width:90%">Department</td>
                                    </tr>';
                                    $j = 0;
                                    for($i =1; $i <= count($distribution);){
                                        $html.='
                                        <tr>
                                            <td>'.$i++.'</td>
                                            <td>'.$distribution[$j++].'</td>
                                        </tr>';
                                    }
                                    $html.='
                                </table>
                            </td>
                        </tr>
                        <tr nobr="true">
                            <td class="td1">11.</td>
                            <td class="td2"><b>Revision History :</b><br><br>
                                <table cellpadding="5">
                                    <tr>
                                        <td style="width:10%;">Sr No.</td>
                                        <td style="width:30%;">Version No</td>
                                        <td style="width:30%;">Change Mode</td>
                                        <td style="width:30%;">Reason for change</td>
                                    </tr>';
                                    $j = 0;
                                    for($i =1; $i <= count($revision);){
                                        $html.='
                                        <tr>
                                            <td>'.$i++.'</td>
                                            <td>'.$revision[$j++]->ver_no.'</td>
                                            <td>'.$revision[$j++]->change_mode.'</td>
                                            <td>'.$revision[$j++]->change_reson.'</td>
                                        </tr>';
                                    }
                                    $html.='
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>';
            }
        }
        EOF;

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('SOP.pdf', 'I');
    }
    // else if ($_GET["type"] == "log") {
    //     $_GET['filename'] = 'Volumetric Master '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
    //     $html= "";

    //     $html.='<table border="1" cellpadding="5">
    //         <thead>
    //             <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    
                   
    //                 <td style="width: 20%; ">SOP No.	</td>
    //                 <td style="width: 20%; ">SOP Title	</td>
    //                 <td style="width: 20%; ">SOP For	</td>
    //                 <td style="width: 20%;">Status	</td>
    //                   <td style="width: 20%;">Prepared By		</td>
    //             </tr>
    //         </thead>';
    //         $i=1;
    //          $output = Array();
    //         	$sql = "SELECT * FROM sop_generate WHERE department='".$_GET["department"]."' ORDER BY id DESC";
    // 	$result = $conn->query($sql);
    // 	$data = array();
    // 	if($result-> num_rows > 0) {
    // 		while($row = $result-> fetch_assoc()) {
    //                 $output[] = $row;
    //             $html.='<tr nobr="true">
                        
                       
    //                     <td style="width: 20%; ">'.$row['sop_no'].'</td>
    //                     <td style="width: 20%; ">'.$row['title'].'</td>
    //                     <td style="width: 20%; ">'.$row['sop_for'].'</td>
    //                     <td style="width: 20%; ">'.$row['status'].'</td>
    //                      <td style="width: 20%; ">'.$row['check_by'].'</td>
    //                 </tr>';
    //             $i++;
    //         }
    //     }
    //     $html.="</table>";

    //     $pdf->writeHTML($html, true, false, false, false, '');
    //     $pdf->Output('Volumetric Master .pdf', 'I');
    // }
    // else if($_GET['type'] == 'log'){
    //     $_GET['type'] = 'empdetail';
    //     include("pdfimp.php");
    //     class MYPDF extends TCPDF {
    //         public function Header() {
    //             $_GET['type'] = 'headerlandscape';
    //             include("pdfimp.php");
    //         }
    //         public function Footer() {
                
    //         }
    //     }
    //     $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    //     $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    //     $pdf->SetMargins(15, 45, 15, 15);
    //     $pdf->SetAutoPageBreak(TRUE, 15);
    //     $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    //     if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    //         require_once(dirname(__FILE__).'/lang/eng.php');
    //         $pdf->setLanguageArray($l);
    //     }
    //     $pdf->AddPage('L', 'A4');
    //     $pdf->SetY(45);
    //     $pdf->SetFont ('Times', '', '11' , '', 'default', true );
    //     $html='
    // <style>
    //     td { border:solid 1px BCBBBA;}
    // </style>
    // <table cellpadding="5" style="text-align:center;">
    //     <tr style="background-color:#DDDAD9;">
    //         <td style="width:100%; text-align:center;"><b>Annexure: SOP Log</b></td>
    //     </tr>
    // </table>
    // <div></div>
    // <table cellpadding="5">
    //     <thead>
    //         <tr style="background-color:#DDDAD9;text-align:center; font-weight:bold;">
    //             <td style="width:6%;">Sr No.</td>
    //             <td style="width:15%;">Department</td>
    //             <td style="width:15%;">SOP No</td>
    //             <td style="width:25%;">SOP Title</td>
    //             <td style="width:15%;">SOP For</td>
    //             <td style="width:12%;">Prepare By</td>
    //             <td style="width:12%;">Approve By</td>
    //         </tr>
    //     </thead>
    //     <tbody>';
    //     if($_GET['todate'] !='' && $_GET['todate'] !=''){
    //         $sql = "SELECT * FROM sop_generate WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
    //     }else{
    //         $sql = "SELECT * FROM sop_generate ORDER by id DESC";
    //     }
    //     $result = $conn->query($sql);
        
    //     if ($result->num_rows > 0) {
            
    //                 $counter = 1;
    //                 while ($row = $result->fetch_assoc()) {
    //                 $html.='<tr nobr="true" style="text-align:center;">
    //                             <td style="width:6%;">'.$counter++.'</td>
    //                             <td style="width:15%;">'.$row['department'].'</td>
    //                             <td style="width:15%;">'.$row['sop_no'].'</td>
    //                             <td style="width:25%; text-align:left;">'.$row['title'].'</td>
    //                             <td style="width:15%; text-align:left;">'.$row['sop_for'].'</td>
    //                             <td style="width:12%;">'.$row['entry_by'].'</td>
    //                             <td style="width:12%;">'.$row['approve_by'].'</td>
    //                         </tr>';
    //                 }
                  
    //     }  $html.='</tbody>
    //             </table>';
    //     EOD;
    //     $pdf->writeHTML($html, true, false, false, false, '');
    //     $pdf->Output('SOP Log log.pdf', 'I');
    // }
    else if($_GET['type'] == 'sopdigital'){
        $sql = "SELECT * FROM sop_generate where sop_no='".$_GET['sop_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $training = json_decode($row["training"]);
                $distribution = json_decode($row["distribution"]);
                $revision = json_decode($row["revision"]);
                
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
                        $this->SetY(-15);
                        $this->Cell(0, 10, 'Format No. : '.$sop_no.'-02-F1', 0, false, 'L', 0, '', 0, false, 'T', 'M');
                        $this->Cell(0, 10, 'Page No. : '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
                $pdf->SetFont('times', '', 12);
                $pdf->AddPage();
                $pdf->SetY(45);
        
                $html='
                <style>
                th { border:solid 1px BCBBBA; }
                td { border:solid 1px BCBBBA; }
                .td1 { width:5%;  border:none; }
                .td2 { width:95%;  border:none; }
                </style>
                <table border="0" cellpadding="5" style="text-align:left; vertical-align:middle;">
                    <thead class="tablehead">
                        <tr>
                            <th style="background-color:#DDDAD9; width:100%; text-align:center;"><b>STANDARD OPERATING PROCEDURE</b></th>
                        </tr>
                        <tr><br></tr>
                        <tr>
                            <th style="width:16.66%;"><b>Department</b></th>
                            <th style="width:33.34%;">'.$row["department"].'</th>
                            <th style="width:16.66%;"><b>Copy No</b></th>
                            <th style="width:33.34%;">'.$row["copy_no"].'</th>
                        </tr>
                        <tr>
                            <th style="width:100%;"><b>Title - '.$row["title"].'</b></th>
                        </tr>
                        <tr>
                            <td style="width:16.66%;"><b>SOP No.</b></td>
                            <td style="width:17.66%;">'.$row['sop_no'].'</td>
                            <td style="width:15.66%;"><b>Revision No.</b></td>
                            <td style="width:16.66%;">'.$row["revision_no"].'</td>
                            <td style="width:16.66%;"><b>Effective Date</b></td>
                            <td style="width:16.66%;">'.$row["effective_date"].'</td>
                        </tr>
                        <tr>
                            <td><b>Supersede No.</b></td>
                            <td>'.$row["supersed_no"].'</td>
                            <td><b>Version No.</b></td>
                            <td>00</td>
                            <td><b>Next Review Date</b></td>
                            <td>'.$row["next_review_date"].'</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><br></tr>
                        <tr>
                            <td style="border:none; text-align:center;"><b>Annexure I : SOP format</b></td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">1.</td>
                            <td class="td2"><b>Purpose :</b><br>'.$row["purpose"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">2.</td>
                            <td class="td2"><b>Scope :</b><br>'.$row["scope"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">3.</td>
                            <td class="td2"><b>Role and Responsibility :</b><br>'.$row["role"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">4.</td>
                            <td class="td2"><b>Definition :</b><br>'.$row["definition"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">5.</td>
                            <td class="td2"><b>External References And Associated Documents :</b><br>'.$row["reference"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">6.</td>
                            <td class="td2"><b>Process Overview :</b><br>'.$row["process"].'</td>
                        </tr>
                        <tr><br></tr>
                        <tr nobr="true">
                            <td class="td1">7.</td>
                            <td class="td2"><b>Procedure :</b><br>'.$row["procedures"].'</td>
                        </tr>
                        <tr>
                            <td class="td1">8.</td>
                            <td class="td2"><b>Abbreviation :</b><br>'.$row["abbreviation"].'</td>
                        </tr>
                        <tr nobr="true">
                            <td class="td1">9.</td>
                            <td class="td2"><b>Training Requirement :</b><br><br>
                                <table cellpadding="5">
                                    <tr>
                                        <td style="width:10%">Sr No.</td>
                                        <td style="width:90%">Department</td>
                                    </tr>';
                                    $j = 0;
                                    for($i =1; $i <= count($training);){
                                        $html.='
                                        <tr>
                                            <td>'.$i++.'</td>
                                            <td>'.$training[$j++].'</td>
                                        </tr>';
                                    }
                                    $html.='
                                </table>
                            </td>
                        </tr>
                        <tr nobr="true">
                            <td class="td1">10.</td>
                            <td class="td2"><b>Distribution :</b><br><br>
                                <table cellpadding="5">
                                    <tr>
                                        <td style="width:10%">Sr No.</td>
                                        <td style="width:90%">Department</td>
                                    </tr>';
                                    $j = 0;
                                    for($i =1; $i <= count($distribution);){
                                        $html.='
                                        <tr>
                                            <td>'.$i++.'</td>
                                            <td>'.$distribution[$j++].'</td>
                                        </tr>';
                                    }
                                    $html.='
                                </table>
                            </td>
                        </tr>
                        <tr nobr="true">
                            <td class="td1">11.</td>
                            <td class="td2"><b>Revision History :</b><br><br>
                                <table cellpadding="5">
                                    <tr>
                                        <td style="width:10%;">Sr No.</td>
                                        <td style="width:30%;">Version No</td>
                                        <td style="width:30%;">Change Mode</td>
                                        <td style="width:30%;">Reason for change</td>
                                    </tr>';
                                    $j = 0;
                                    for($i =1; $i <= count($revision);){
                                        $html.='
                                        <tr>
                                            <td>'.$i++.'</td>
                                            <td>'.$revision[$j++]->ver_no.'</td>
                                            <td>'.$revision[$j++]->change_mode.'</td>
                                            <td>'.$revision[$j++]->change_reson.'</td>
                                        </tr>';
                                    }
                                    $html.='
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>';
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SOP.pdf', 'I');
    }
    // else if ($_GET['type'] == 'sop') {
    //     //$_GET['filename'] = 'TEMPLATE FOR SOP PREPARATION';$_GET['sop']='SOP/QAD/C/001-F01/01';$_GET['annexure']='Annexure-01';$_GET['pdftype'] = 'headfootlog'; include("../pdfimp.php");
    //     $sql = "SELECT * FROM sop_generate WHERE sop_no='".$_GET["sop_no"]."'";
    // 	$result = $conn->query($sql);
    // 	if($result-> num_rows > 0) {
    // 		while($row = $result-> fetch_assoc()) {
    // 		$_GET['sop_no']=$row['sop_no'];
    // 		$_GET['department']=$row['department'];
    // 		$_GET['title']=$row['title'];
    // 		$_GET['entry_by']=$row['entry_by'];
    // 		$_GET['entry_date']=$row['entry_date'];
    // 		$_GET['check_by']=$row['check_by'];
    // 		$_GET['check_date']=$row['check_date'];
    // 		$_GET['approve_by']=$row['approve_by'];
    // 		$_GET['approve_date']=$row['approve_date'];
    //         class MYPDF extends TCPDF {
    //             public function Header() {
    //                 $table='
    //                 <style>td { border:solid 1px BCBBBA;}</style>
    //                 <table cellpadding="2">
    //                     <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
    //                         <td>STANDARD OPERATING PROCEDURE</td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:20%;">';
    //                             $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/wastcost.png'),16,12,33);
    //                         $table.='
    //                         </td>
    //                         <td style="width:80%;text-align:center;font-weight:bold;">
    //                             <span style="font-family:times;font-size:17px;">WEST COAST PHARMACEUTICAL WORKS LTD</span><br>
    //                             <span style="font-size:9px;">Location:Opp Sola Bhagwat, Near Prasang Party Plot,,Near Meldi Mata Temple, Meldi Estate,,GOTA,AHMEDABAD  INDIA-382481.</span>
    //                         </td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:20%;font-weight:bold;">Title</td>
    //                         <td style="width:80%;">'.$_GET['title'].'</td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:20%;font-weight:bold;">Department</td>
    //                         <td style="width:80%;">'.$_GET['department'].'</td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:20%;font-weight:bold;">SOP</td>
    //                         <td style="width:30%;">'.$_GET['sop_no'].'</td>
    //                         <td style="width:20%;font-weight:bold;">Supersede  No.</td>
    //                         <td style="width:30%;"></td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:20%;font-weight:bold;">Effective Date</td>
    //                         <td style="width:30%;"></td>
    //                         <td style="width:20%;font-weight:bold;">Review Date</td>
    //                         <td style="width:30%;"></td>
    //                     </tr>
    //                 </table>';
    //                 $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
    //                 $this->SetY(29); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, '', 0, false, 'L', 0, '', 0, false, 'M', 'M');
    //             }
    //             public function Footer(){
    //                  $table='
    //                 <style>td { border:solid 1px BCBBBA;}</style>
    //                 <table cellpadding="5" border="1" >
    //                     <tr style="text-align:center;background-color:#DDDAD9;">
    //                         <td style="width:25%">Prepared by('.$_GET['emp_department'].')</td>
    //                         <td style="width:25%">Checked By('.$_GET['emp_department1'].')</td>
    //                         <td style="width:25%">Reviewed By('.$_GET['emp_department2'].')</td>
    //                         <td style="width:25%">Approved By('.$_GET['emp_department2'].')</td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:10.50%">ID</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['emp_by'].'</td>
    //                         <td style="width:10.50%">ID</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['emp_by1'].'</td>
    //                         <td style="width:10.50%">ID</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['emp_by2'].'</td>
    //                         <td style="width:10.50%">ID</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['emp_by2'].'</td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:10.50%">NAME</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['entry_by'].'</td>
    //                         <td style="width:10.50%">NAME</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['check_by'].'</td>
    //                         <td style="width:10.50%">NAME</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['emp_by2'].'</td>
    //                         <td style="width:10.50%">NAME</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['approve_by'].'</td>
    //                     </tr>
    //                     <tr>
    //                         <td style="width:10.50%">Sign/Date</td>
    //                         <td style="width:14.50%;text-align:center;">'.date('d-m-Y',strtotime($_GET['entry_date'])).'</td>
    //                         <td style="width:10.50%">Sign/Date</td>
    //                         <td style="width:14.50%;text-align:center;">'.date('d-m-Y',strtotime($_GET['check_date'])).'</td>
    //                         <td style="width:10.50%">Sign/Date</td>
    //                         <td style="width:14.50%;text-align:center;">'.$_GET['emp_by2'].'</td>
    //                         <td style="width:10.50%">Sign/Date</td>
    //                         <td style="width:14.50%;text-align:center;">'.date('d-m-Y',strtotime($_GET['approve_date'])).'</td>
    //                     </tr>
    //                 </table>';
    //                 $this->SetY(-45);
    //                 $this->SetFont('Times', '', 10);
    //                 $this->writeHTML($table, true, false, false, false, ''); 
    //                 $this->SetY(-10); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
    //                 $this->SetY(-10); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0,'SOP/QAD/C/001-F01/01', 0, false, 'R', 0, '', 0, false, 'M', 'M');
                
    //             }
    //         }
    //         $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    //         $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    //         $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    //         $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    //         $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    //         $pdf->SetMargins(PDF_MARGIN_LEFT,50, PDF_MARGIN_RIGHT);
    //         $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    //         $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    //         $pdf->SetAutoPageBreak(TRUE,50);
    //         $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    //         if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    //             require_once(dirname(__FILE__).'/lang/eng.php');
    //             $pdf->setLanguageArray($l);
    //         }
    //         $pdf->SetFont('times', '', 10);
    //         $pdf->AddPage();
    //         $html.="";
            
    //         $html.='
    //         <table cellpadding="3">
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">1.1 OBJECTIVE:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;"></td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">2.0 SCOPE:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;">'.$row['scope'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">3.0	RESPONSIBILITY: </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;font-weight:bold;">3.1 User Department:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.1.1</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;font-weight:bold;">3.2 Department Incharge:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.2.1 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.2.2</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.2.3</td>
    //             </tr>
    //              <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;font-weight:bold;">3.3	Quality Assurance: </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.3.1</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.3.2</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.3.3 </td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.3.4</td>
    //             </tr>
    //              <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;font-weight:bold;">3.4	Quality Head:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:10%;"></td>
    //                 <td style="width:90%;">3.4.1	To approve SOP.</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">4.0 ACCOUNTABILITY:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;font-weight:bold;">4.1 Quality Head</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;font-weight:bold;">4.2 All Department Incharge</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">5.0	DEFINITION '.$row['defination'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:5%;"></td>
    //                 <td style="width:95%;"><b>5.1 Standard Operating Procedure (SOP):</b>SOP is step by step procedures or a written method of controlling a practice in accordance with predetermined specifications to obtain a desired outcome.</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;"><b>6.0 Reference :</b>In house '.$row['reference'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">7.0 PROCESS OVERVIEW '.$row['process'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">8.0 PROCEDURE:'.$row['procedures'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">9.0 ANNEXURE:</td>
    //             </tr>
    //              <tr>
    //                 <td style="width:100%;font-weight:bold;">10.0 ABBREVIATION:</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%;font-weight:bold;">11.0 REVISION HISTORY</td>
    //             </tr>
    //         </table>';
    //         $html.='
    //         <table border="1" cellpadding="2">
    //             <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
    //                 <td style="width:10%;">Sr. No.</td>
    //                 <td style="width:20%;">SOP No.</td>
    //                 <td style="width:20%;">Reason for Revision</td>
    //                 <td style="width:30%;">Reference Document No.</td>
    //                 <td style="width:20%;">Effective Date</td>
    //             </tr>';
    //             $j=1;
    //             $row['revision'] = json_decode($row['revision']);
    //             $revisions=$row['revision'];
    //             for($i=0;$i<count($revisions);$i++){
    //                 $revision=$revisions[$i];
    //                 $html.='
    //                 <tr>
    //                     <td style="width:10%;">'.$j++.'</td>
    //                     <td style="width:20%;">'.$row['sop_no'].'</td>
    //                     <td style="width:20%;">'.$revision->change_reason.'</td>
    //                     <td style="width:30%;"></td>
    //                     <td style="width:20%;">'.$row['effective_date'].'</td>
    //                 </tr>';
    //             }
    //         $html.='
    //         </table>';
    // 		}
    // 	}
        
    //     $pdf->writeHTML($html, true, false, false, false, '');
    //     $pdf->Output('material.pdf', 'I');
    // }
    else if($_GET['type'] == 'sop') {
        if($_GET["plant_id"] == 64) {
            $_GET['filename'] = 'SOPs Index'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
            
            $sql ="SELECT * from sop_generate WHERE sop_no='".$_GET["sop_no"]."'";
        $result = $conn->query($sql);
    $row = $result->fetch_assoc();{

        


            // $sql = "SELECT * FROM sop_generate WHERE sop_no='".$_GET["sop_no"]."' GROUP BY sop_no";
    // 	$result = $conn->query($sql);
    // 	$data = array();
    // 	if($result-> num_rows > 0) {
    // 		while($row = $result-> fetch_assoc()) {
            $html.='
 <table border="1">
 
 <tr>
    <td style="width: 100px;"> Department</td>
    <td style="width: 440px;">'.$row['department'].'</td>	
 </tr>
 <tr>
    <td style="width: 100px;">Title</td>
    <td style="width: 440px;">'.$row['title'].'</td>
 </tr>
 <tr>
    <td style="width: 100px;"> SOP No.</td>
    <td style="width: 100px;">'.$row['sop_no'].'</td>
    <td style="width: 100px;"> Revision No.</td>
    <td style="width: 70px;">'.$row['revision'].'</td>
    <td style="width: 100px;"> Effective Date</td>
    <td style="width: 70px;">'.$row['effective_date'].'</td>
    </tr>
 <tr>
    <td style="width: 100px;"> Supersede No</td>
    <td style="width: 100px;"></td>
    <td style="width: 100px;"> Version No.</td>
    <td style="width: 70px;">	'.$row['version_no'].'</td>
    <td style="width: 100px;"> Review Date</td>
    <td style="width: 70px;"></td>
    </tr>

</table><div></div>
<ol>
    <li>Objective : <br>	'.$row['scope'].'</li>
    <li>SCOPE: <br>	'.$row['scope'].'</li>
    <li>RESPOMSIBILITY</li>
    <li>ACCOUNTABILITY</li>';
       
    //   $result = $conn->query($sql);
    // $row = $result->fetch_assoc();{

    //  $json_obj = $row['definition'];
    //             $array = json_decode($json_obj, true);
    //             $k=1;
    //             foreach ($array as $values)
    //             {
    //               $term = $values['term'];
    
    $html.='<li>DEFINITION :<br>	'.$term.'</li>';
                // }}
    $html.='
    <li>REFERENCE :<br></li>';	
 
     $json_obj = $row['abbreviation'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $short_form = $values['short_form'];
     $html.='   <table >
            <tr>
                <td>	</td>
                <td>'.$short_form.'</td>
            </tr>
        </table>';
                }
        $html.='
    <li>PROCESS OVERVIEW :<br>	'.$row['process'].'</li>
    <li>PROCEDURE :<br>	'.$row['procedures'].'</li>
    <li>DISTRIBUTION :<br>	'.$row['distribution'].'</li>
    <li>TRAINING OF SOP :<br>	'.$row['training'].'</li>
    <li>ANNEXURE </li>';
    $html.='
    <li>ABBREVIATION :<br> </li>';
    $json_obj = $row['abbreviation'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $short_form = $values['short_form'];
                   $full_title = $values['full_title'];
     $html.='   <table >
            <tr>
                <td>'.$short_form.'	</td>
                <td>'.$full_title.'</td>
            </tr>
        </table>';
                }
        
    $html.='<li>REVISION HISTORY</li>
    
</ol>

<table border="1">
    <tr>
        <td style="width: 135px;text-align:center;">Version No.</td>
        <td style="width: 135px;text-align:center;">Effective Date</td>
        <td style="width: 135px;text-align:center;">Reason for Revision</td>
        <td style="width: 135px;text-align:center;">Quality Record Number</td>
    </tr>
    <tr>
        <td style="width: 135px;">'.$row['version_no'].'</td>
        <td style="width: 135px;">	'.$row['effective_date'].'</td>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
    </tr>';
    // $sql ="SELECT * from sop_generate WHERE sop_no='".$_GET["sop_no"]."'";
    //     $result = $conn->query($sql);
    // $row = $result->fetch_assoc();{

    //  $json_obj = $row['abbreviation'];
    //             $array = json_decode($json_obj, true);
    //             $k=1;
    //             foreach ($array as $values)
    //             {
    //               $short_form = $values['short_form'];
                   
    
    $html.='<tr>
        <td style="width: 135px;">'.$short_form.'</td>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
    </tr>';
                // }
    $html.='
</table><div></div><br>




<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;">'.$row["approve_by"].'</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;">'.$row["approve_date"].'</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;">'.$row["department"].'</td>
    </tr>
</table>
';
}
    // 		}
    // 	}
            // $html.='</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Sop.pdf', 'I');
        
        }else if($_GET["plant_id"] == 28) {
            $_GET['filename'] = 'SOPs Index'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
            
            $sql ="SELECT * from sop_generate WHERE sop_no='".$_GET["sop_no"]."'";
        $result = $conn->query($sql);
    $row = $result->fetch_assoc();{

        



            $html.='
 <table border="1">
 
 <tr>
    <td style="width: 100px;"> Department</td>
    <td style="width: 440px;">'.$row['department'].'</td>	
 </tr>
 <tr>
    <td style="width: 100px;">Title</td>
    <td style="width: 440px;">'.$row['title'].'</td>
 </tr>
 <tr>
    <td style="width: 100px;"> SOP No.</td>
    <td style="width: 100px;">'.$row['sop_no'].'</td>
    <td style="width: 100px;"> Revision No.</td>
    <td style="width: 70px;">'.$row['revision'].'</td>
    <td style="width: 100px;"> Effective Date</td>
    <td style="width: 70px;">'.$row['effective_date'].'</td>
    </tr>
 <tr>
    <td style="width: 100px;"> Supersede No</td>
    <td style="width: 100px;"></td>
    <td style="width: 100px;"> Version No.</td>
    <td style="width: 70px;">	'.$row['version_no'].'</td>
    <td style="width: 100px;"> Review Date</td>
    <td style="width: 70px;"></td>
    </tr>

</table><div></div>
<ol>
    <li>Objective : <br>	'.$row['scope'].'</li>
    <li>SCOPE: <br>	'.$row['role'].'</li>
    <li>RESPOMSIBILITY </li>
    <li><ul>
 ';
        $json_obj = $row['role'];
                $array = json_decode($json_obj, true);
                $html.= '<li>---' .count($array). $array[0]["role"] . ' </li>';
                // print_r($array);
                 //$output1 = Array();
                foreach ($array as $values)
                {                 
                   $role = $values['role'];
                    $responsibility = $values['responsibility']; 
         $html.='  
         <li>---' . $role . ' </li>
         ';
                }
       
      
    $html.='</ul></li>
    </ul></li> <li>ACCOUNTABILITY</li>
    <li>DEFINITION :<br>	'.$row['definition'].'</li>
    <li>REFERENCE :<br>	'.$row['reference'].'</li>	
    <li>PROCESS OVERVIEW :<br>	'.$row['process'].'</li>
    <li>PROCEDURE :<br>	'.$row['procedures'].'</li>
    <li>DISTRIBUTION :<br>	'.$row['distribution'].'</li>
    <li>TRAINING OF SOP :<br>	'.$row['training'].'</li>
    <li>ANNEXURE </li>
    <li>ABBREVIATION :<br>	'.$row['abbreviation'].'</li>
    <li>REVISION HISTORY</li>
    
</ol>

<table border="1">
    <tr>
        <td style="width: 135px;text-align:center;">Version No.</td>
        <td style="width: 135px;text-align:center;">Effective Date</td>
        <td style="width: 135px;text-align:center;">Reason for Revision</td>
        <td style="width: 135px;text-align:center;">Quality Record Number</td>
    </tr>
    <tr>
        <td style="width: 135px;"></td>
        <td style="width: 135px;">	'.$row['effective_date'].'</td>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
    </tr>
    <tr>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
        <td style="width: 135px;"></td>
    </tr>
</table><div></div><br>




<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>
';
}

            // $html.='</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Sop.pdf', 'I');
        
        }
        
        }
        
         else if($_GET['type'] == 'log') {
            $_GET['filename'] = 'SOPs Index'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
            $html.='                          
                <h3 style="text-align:center">SOPs Index</h3>
                    <table cellpadding="5" border="1">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                            <td style="width:15%; text-align:centre;"><b>Department</b></td>
                            <td style="width:15%; text-align:centre;"><b>SOP No</b></td>
                            <td style="width:15%; text-align:centre;"><b>SOP Title</b></td>
                            <td style="width:15%; text-align:centre;"><b>SOP For</b></td>
                            <td style="width:15%; text-align:centre;"><b>Status</b></td>
                            <td style="width:15%; text-align:centre;"><b>Prepared By</b></td>
                        </tr>';
                        $i=1;
                        $sql = "SELECT * FROM sop_generate ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
                $html.='<tr>
                            <td style="width:10%;">'.$i.'.</td>
                            <td style="width:15%;">'.$row['department'].'</td>
                            <td style="width:15%;">'.$row['sop_no'].'</td>
                            <td style="width:15%;">'.$row['title'].'</td>
                            <td style="width:15%;">'.$row['sop_for'].'</td>
                            <td style="width:15%;">'.$row['status'].'</td>
                            <td style="width:15%;">'.$row['entry_by'].'</td>
                        </tr>';
                        $i++;
    		            }
    	            }
            $html.='</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('log.pdf', 'I');
        }
        
        
        
}
?>