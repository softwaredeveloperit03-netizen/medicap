<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
  ini_set('display_errors', 1);
    error_reporting(E_ALL); 
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

    if($_GET["type"]=="changecontrol"){
        $sql = "SELECT * FROM changecontrol";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product_details = json_decode($row['product_details']);

                $_GET['filename']=''; $_GET['pdftype']='landscape'; include("../pdfimp.php");
               
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
        	    <table cellpadding="5">
            	    <tr>
            	        <td style="width:100%; text-align:center; background-color:#DDDAD9;"><b></b></td>
            	    </tr>
        	    </table>
        	    <div></div>
        	    <table cellpadding="5">
        	        <tr>
        	            <td style="width:70%;"><b>Change Control Form No:</b> '.$row["ctrl_no"].'<br>(To be filled by QA Dept.)</td>
        	            <td style="width:30%;"><b>Date:</b></td>
        	        </tr>
        	        <tr>
                        <td style="width:30%;"><b>Originating Department</b></td>
        	            <td style="width:70%;"> '.$row['department'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Originator</b></td>
        	            <td> '.$row['entry_by'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Change Related to</b></td>
        	            <td> '.$row['change_related'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Change Title</b></td>
        	            <td> '.$row['change_title'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Existing Procedure</b></td>
        	            <td> '.$row['existing_procedure'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Proposed change</b> </td>
        	            <td> '.$row['proposed_change'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Reason For Changes</b></td>
        	            <td> '.$row['change_reason'].'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Product Name</b><br>( If  product related )</td>
        	            <td> '.$product_details->product_name.'</td>
        	        </tr>
        	        <tr>
        	            <td><b>Market Details</b></td>
        	            <td>';
        	            if($row['export'] == 'yes'){
        	                $html.='Export';
        	            }
        	            if($row['domastic'] == 'yes'){
        	                $html.='Domastic';
        	            }
        	            $html.='</td>
        	        </tr>
        	        <tr>
        	            <td><b>Probable Impact on Quality of product :</b><br>If  yes Description</td>
        	            <td></td>
        	        </tr>
        	        <tr>
        	            <td><b>Primary Review and comments By Department Head :</b><br><br><b>This proposal is</b></td>
        	            <td></td>
        	        </tr>
        	        <tr>
        	            <td style="background-color:#DDDAD9;"><b>Research and development Department:</b></td>
        	            <td></td>
        	        </tr>
        	        <tr>
        	            <td><b>Evaluation by R&D (tick(√) whatever applicable)</b></td>
        	            <td></td>
        	        </tr>
        	        <tr>
        	            <td style="width:45%;">Validation required</td>
        	            <td style="width:5%;"></td>
        	            <td style="width:45%;">Validation not required</td>
        	            <td style="width:5%;"></td>
        	        </tr>
        	        <tr>
        	            <td>Market approval required</td>
        	            <td></td>
        	            <td>Market approval not required</td>
        	            <td></td>
        	        </tr>
        	        <tr>
        	            <td style="width:100%;"><b>Comment :<br></b></td>
        	        </tr>
        	        <tr>
        	            <td style="background-color:#DDDAD9;"><b>Review by Additional Departments:</b></td>
        	        </tr>
        	        <tr style="text-align:center;">
        	            <td rowspan="2" style="width:25%;">&nbsp;<br><b>Department</b></td>
        	            <td rowspan="2" style="width:35%;">&nbsp;<br><b>Comments</b></td>
        	            <td colspan="3" style="width:40%;"><b>Approval</b></td>
        	        </tr>
        	        <tr style="text-align:center;">
        	            <td style="width:13%;"><b>Name</b></td>
        	            <td style="width:13%;"><b>Signature</b></td>
        	            <td style="width:14%;"><b>Date</b></td> 
        	        </tr>';
        	        $sql2 = "SELECT * FROM change_comments WHERE ctrl_no='".$row["ctrl_no"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
            	        $html.='
            	        <tr>
            	            <td style="width:25%;">'.$row2['department'].'</td>
            	            <td style="width:35%;">'.$row2['comment'].'</td>
            	            <td style="width:13%;">'.$row2['entry_by'].'</td>
            	            <td style="width:13%;">'.$row2['entry_by'].'</td>
            	            <td style="width:14%;">'.$row2['entry_date'].'</td>
            	        </tr>';
        		        }
        		    }
        		    $html.='
        		    <tr><td colspan="5" style="border:none;"></td></tr>
        		    <tr style="background-color:#DDDAD9;">
        		        <td style="width:100%;"><b>Review by Customer/Contract Manufacturing Party : (If Applicable)</b></td>
        		    </tr>
        		    <tr><td><b>Comments : </b></td></tr>
        		    <tr><td><b>Quality Assurance Department :</b><br>'.$row['risk_assessment'].'<br><br></td></tr>
        		    <tr><td><b>Training required :</b></td></tr>
        		    <tr><td><b>Training to be imparted to departments :</b></td></tr>
        		    <tr><td><b>The change request is Approved :</b></td></tr>
        		    <tr><td><b>Whether the change proposal is :</b></td></tr>
        		    <tr><td><b>Information send to Customer :</b></td></tr>
        		    <tr><td><b>Comments :</b></td></tr>
        		    <tr><td><b>Final Review and Approval :</b></td></tr>
        		    <tr><td><b>Implementation Details :</b></td></tr>
        		    <tr><td><b>Change implemented on  :</b></td></tr>
        		    <tr style="background-color:#DDDAD9;">
        		        <td style="width:100%;"><b>Closure of Change Control Form: (To be filled By QA)</b></td>
        		    </tr>
        		    <tr><td><b>Related Documents Revised as per change</b></td></tr>
        		    <tr><td><b>If Yes Document No.:</b></td></tr>
        		    <tr><td><b>Version No.:</b></td></tr>
        	    </table>';
            }
    	    
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('changecontrol.pdf', 'I');
        }else{
            echo 'Invalid Change Control No.';
        }
    }
    
        else if($_GET["type"]=="1changecontrol"){
        
            $sql = "SELECT * FROM changecontrol ORDER by id DESC";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $_GET['type'] = 'empdetail';
            include("../pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'headerlandscape';
                    include("../pdfimp.php");
                }
                public function Footer() {
                }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
            $html.='<table>
          
          
          <table border="1">
  <tr>
    <td>CC No.</td>
    <td>Control Number</td>
    <td>Change Control Issued By</td>
    <td>Issued By / Department Name</td>
  </tr>
  <tr>
    <td>Initiated By</td>
    <td>Initiator Name</td>
    <td>Date Of Issuance</td>
    <td>Date (DD-MM-YYYY)</td>
  </tr>
  <tr>
    <td>Department</td>
    <td>Department Name</td>
    <td>Room</td>
    <td>Room Name</td>
  </tr>
  <tr>
    <td>Name Of Product / Document</td>
    <td>Product/Document Name</td>
    <td>Batch No/Document No</td>
    <td>Batch/Document Number</td>
  </tr>
  <tr>
    <td>Changed Requested For</td>
    <td colspan="3">Requested Change Details</td>
  </tr>
  <tr>
    <td colspan="3">Standard Current Procedure / Document or Existing Procedure / Document</td>
    <td>
      Existing Procedure or "N/A" 
      <div>
        <button>VIEW REF. DOC</button>
        <span>N/A</span>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="3">Details Of Change Proposed</td>
    <td>
      Proposed Change Details or "N/A" 
      <div>
        <button>VIEW REF. DOC</button>
        <span>N/A</span>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="3">Justification Proposed Change</td>
    <td>
      Justification Details or "N/A"
      <div>
        <button>VIEW REF. DOC</button>
        <span>N/A</span>
      </div>
    </td>
  </tr>
  <tr>
    <td>Tentative Date Of Closing</td>
    <td>Date</td>
    <td>Remark</td>
    <td>Remark Details</td>
  </tr>
</table>

          
          
          
          
          
          
          ';
            $html.='</table>';
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('changecontrol.pdf', 'I');
        }
        else{
            echo "No Records Found";
        }
    }

    
    
    else if($_GET["type"]=="changecontrollog"){
        if(isset($_GET['fromdate']) && $_GET['change_related'] != ''){
            $sql = "SELECT * FROM changecontrol WHERE change_related = '".$_GET['change_related']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        } else if(isset($_GET['fromdate']) && $_GET['change_related'] == ''){
            $sql = "SELECT * FROM changecontrol WHERE  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        } else{
            $sql = "SELECT * FROM changecontrol ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $_GET['type'] = 'empdetail';
            include("../pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'headerlandscape';
                    include("../pdfimp.php");
                }
                public function Footer() {
                }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
            $html.='
            <style>
                td { border:solid 1px BCBBBA;}
            </style>
                <table cellpadding="5" style="text-align:center;">
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:100%; text-align:center;"><b>Annexure: Change Control Log</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>From Date :</b> '.$_GET['fromdate'].'</td>
                        <td><b>Department &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </b>';
                            if($_GET['getdepartment'] != ''){
                                $html.=''.$_GET['getdepartment'].'';
                            }else{
                                $html.='All Department';
                            }
                            $html.='
                        </td>
                    </tr>
                    <tr>
                        <td><b>To Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> '.$_GET['todate'].'</td>
                        
                        <td><b>Change Related To : </b>';
                            if($_GET['change_related'] != ''){
                                $html.=''.$_GET['change_related'].'';
                            }else{
                                $html.='All';
                            }
                            $html.='
                        </td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:3%;">Sr</td>
                        <td style="width:12%;">Department</td>
                        <td style="width:8%;">Change Control No</td>
                        <td style="width:20%;">Proposed Change</td>
                        <td style="width:10%;">Initiated By</td>
                        <td style="width:10%;">Initiated Date</td>
                        <td style="width:7%;">Status</td>
                        <td style="width:10%;">Approved By (QA Head)</td>
                        <td style="width:10%;">Date of Approve</td>
                        <td style="width:10%;">Date of closure</td>
                    </tr>';
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td>'.$counter++.'</td>
                        <td>'.$row['department'].'</td>
                        <td>'.$row['ctrl_no'].'</td>
                        <td>'.$row['proposed_change'].'</td>
                        <td>'.$row["entry_by"].'</td>
                        <td>'.date('d/m/Y', strtotime($row['entry_date'])).'</td>
                        <td>'.$row["status"].'</td>
                        <td>'.$row["approve_by"].'</td>
                        <td>';
                            if($row['approve_date'] != null){
                            $html.=''.date('d/m/Y', strtotime($row['approve_date'])).'';
                            }
                            $html.='</td>
                        <td>';
                            if($row['close_date'] != null){
                            $html.=''.date('d/m/Y', strtotime($row['close_date'])).'';
                            }
                            $html.='</td>
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
    else if($_GET["type"]=="changecontrolDeptlog"){
      $_GET['filename'] = 'Change Control Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");   
           $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>No.</b></td>
                <td style="width:20%; text-align:centre;"><b>Change Title</b></td>
                <td style="width:20%; text-align:centre;"><b>Department</b></td>
                <td style="width:20%; text-align:centre;"><b>Change Related</b></td>
                <td style="width:20%; text-align:centre;"><b>Entry Date</b></td>
            </tr>';
            if(isset($_GET['fromdate']) && $_GET['change_related'] != ''){
        $sql = "SELECT * FROM changecontrol WHERE department='".$_GET["department"]."' AND change_related = '".$_GET['change_related']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
    }else if(isset($_GET['fromdate']) && $_GET['change_related'] == ''){
        $sql = "SELECT * FROM changecontrol WHERE department='".$_GET["department"]."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
    } else{
        $sql = "SELECT * FROM changecontrol WHERE department='".$_GET["department"]."' ORDER by id DESC";
    }
    
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $i=1;
        while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['ctrl_no'].'</td>
                <td style="width:20%;">'.$row['change_title'].'</td>
                <td style="width:20%;">'.$row['department'].'</td>
                <td style="width:20%;">'.$row['change_related'].'</td>
                <td style="width:20%;">'.date('d-m-Y h:i:sa',strtotime($row['entry_date'])).'</td>
            </tr>';
            $i++;
        }
    }
           $html.='</table>';
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('changecontrol.pdf', 'I');
    }
    
    else if($_GET["type"]=="changecontroldigital"){
        $_GET['filename'] = 'Change Control';
        $sql = "SELECT * FROM changecontrol WHERE ctrl_no='".$_GET["ctrl_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product_details = json_decode($row['product_details']);
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['headertype'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
            
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
    	    <table cellpadding="5">
    	        <tr>
    	            <td style="width:70%;"><b>Change Control Form No:</b> '.$row["ctrl_no"].'<br>(To be filled by QA Dept.)</td>
    	            <td style="width:30%;"><b>Date:</b></td>
    	        </tr>
    	        <tr>
                    <td style="width:30%;"><b>Originating Department</b></td>
    	            <td style="width:70%;"> '.$row['department'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Originator</b></td>
    	            <td> '.$row['entry_by'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Change Related to</b></td>
    	            <td> '.$row['change_related'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Change Title</b></td>
    	            <td> '.$row['change_title'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Existing Procedure</b></td>
    	            <td> '.$row['existing_procedure'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Proposed change</b> </td>
    	            <td> '.$row['proposed_change'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Reason For Changes</b></td>
    	            <td> '.$row['change_reason'].'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Product Name</b><br>( If  product related )</td>
    	            <td> '.$product_details->product_name.'</td>
    	        </tr>
    	        <tr>
    	            <td><b>Market Details</b></td>
    	            <td>';
    	            if($row['export'] == 'yes'){
    	                $html.='Export';
    	            }
    	            if($row['domastic'] == 'yes'){
    	                $html.='Domastic';
    	            }
    	            $html.='</td>
    	        </tr>
    	        <tr>
    	            <td><b>Probable Impact on Quality of product :</b><br>If  yes Description</td>
    	            <td></td>
    	        </tr>
    	        <tr>
    	            <td><b>Primary Review and comments By Department Head :</b><br><br><b>This proposal is</b></td>
    	            <td></td>
    	        </tr>
    	        <tr>
    	            <td style="background-color:#DDDAD9;"><b>Research and development Department:</b></td>
    	            <td></td>
    	        </tr>
    	        <tr>
    	            <td><b>Evaluation by R&D (tick(√) whatever applicable)</b></td>
    	            <td></td>
    	        </tr>
    	        <tr>
    	            <td style="width:45%;">Validation required</td>
    	            <td style="width:5%;"></td>
    	            <td style="width:45%;">Validation not required</td>
    	            <td style="width:5%;"></td>
    	        </tr>
    	        <tr>
    	            <td>Market approval required</td>
    	            <td></td>
    	            <td>Market approval not required</td>
    	            <td></td>
    	        </tr>
    	        <tr>
    	            <td style="width:100%;"><b>Comments:</b></td>
    	        </tr>
    	        <tr>
    	            <td style="border:none;"></td>
    	        </tr>
    	        <tr>
    	            <td style="background-color:#DDDAD9;"><b>Review by Additional Departments:</b></td>
    	        </tr>
    	        <tr style="text-align:center;">
    	            <td rowspan="2" style="width:25%;">&nbsp;<br><b>Department</b></td>
    	            <td rowspan="2" style="width:35%;">&nbsp;<br><b>Comments</b></td>
    	            <td colspan="3" style="width:40%;"><b>Approval</b></td>
    	        </tr>
    	        <tr style="text-align:center;">
    	            <td style="width:13%;"><b>Name</b></td>
    	            <td style="width:13%;"><b>Signature</b></td>
    	            <td style="width:14%;"><b>Date</b></td> 
    	        </tr>';
    	        $sql2 = "SELECT * FROM change_comments WHERE ctrl_no='".$row["ctrl_no"]."'";
    		    $result2 = $conn->query($sql2);
    		    if ($result2->num_rows > 0) {
    		        while ($row2 = $result2->fetch_assoc()) {
        	        $html.='
        	        <tr>
        	            <td style="width:25%;">'.$row2['department'].'</td>
        	            <td style="width:35%;">'.$row2['comment'].'</td>
        	            <td style="width:13%;">'.$row2['entry_by'].'</td>
        	            <td style="width:13%;">'.$row2['entry_by'].'</td>
        	            <td style="width:14%;">'.$row2['entry_date'].'</td>
        	        </tr>';
    		        }
    		    }
    		    $html.='
    		    <tr><td colspan="5" style="border:none;"></td></tr>
    		    <tr nobr="true" style="background-color:#DDDAD9;">
    		        <td style="width:100%;"><b>Review by Customer/Contract Manufacturing Party : (If Applicable)</b></td>
    		    </tr>
    		    <tr nobr="true"><td><b>Comments : </b></td></tr>
    		    <tr nobr="true"><td><b>Quality Assurance Department :</b><br>'.$row['risk_assessment'].'<br><br></td></tr>
    		    <tr nobr="true"><td><b>Training required :</b></td></tr>
    		    <tr nobr="true"><td><b>Training to be imparted to departments :</b></td></tr>
    		    <tr nobr="true"><td><b>The change request is Approved :</b></td></tr>
    		    <tr nobr="true"><td><b>Whether the change proposal is :</b></td></tr>
    		    <tr nobr="true"><td><b>Information send to Customer :</b></td></tr>
    		    <tr nobr="true"><td><b>Comments :</b></td></tr>
    		    <tr nobr="true"><td><b>Final Review and Approval :</b></td></tr>
    		    <tr nobr="true"><td><b>Implementation Details :</b></td></tr>
    		    <tr nobr="true"><td><b>Change implemented on  :</b></td></tr>
    		    <tr style="background-color:#DDDAD9;">
    		        <td style="width:100%;"><b>Closure of Change Control Form: (To be filled By QA)</b></td>
    		    </tr>
    		    <tr nobr="true"><td><b>Related Documents Revised as per change</b></td></tr>
    		    <tr nobr="true"><td><b>If Yes Document No.:</b></td></tr>
    		    <tr nobr="true"><td><b>Version No.:</b></td></tr>
    	    </table>';
        }
    	    EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('changecontrol.pdf', 'I');
        }else{
            echo 'Invalid Change Control No.';
        }
    }else if ($_GET["type"] == "withdrawallog") {
        $_GET['filename'] = 'Withdrawall Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr</td>
                    <td style="width:10%;">Date</td>
                    <td style="width:15%;">Control Sample ID</td>
                    <td style="width:10%;">Product Name</td>
                    <td style="width:10%;">Batch No</td>
                    <td style="width:15%;">Quantity Withdrawal</td>
                    <td style="width:10%;">Checked By</td>
                    <td style="width:15%;">Request By Dpt</td>
                    <td style="width:10%;">Balance Quantity</td>
                  
                </tr>';
                $output = Array();
                $sql = "SELECT s.*, DATE(s.check_date) as check_date, m.material_type, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='approve' AND m.material_type='Packing Material' AND DATE(s.check_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row["sampling_qty"] = +$row["identification_qty"] + +$row["composite_qty"] + +$row["reserve_qty"];
                        $row["containersList"] = json_decode($row["containersList"]);
            
                         $output[] = $row;
                    
                    $html.='<tr>
                                <td style="width:5%;">'.$i.'</td>
                                <td style="width:10%;">'.$row['date'].'</td>
                                <td style="width:15%;">'.$row['control_sample_id'].'</td>
                                <td style="width:10%;">'.$row['product_name'].'</td>
                                <td style="width:10%;">'.$row['batch_no'].'</td>
                                <td style="width:15%;">'.$row['qty'].'</td>
                                <td style="width:10%;">'.$row['checked_by'].'</td>
                                <td style="width:15%;">'.$row['request_by'].'</td>
                                <td style="width:10%;">'.$row['balance_qty'].'</td>
                              
                            </tr>';
                            $i++;
                            }
                        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('withdrawallog.pdf', 'I');
    }else if ($_GET["type"] == "controlsamplelog") {
        $_GET['filename'] = 'Control Sample Review'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:10%; text-align:centre;"><b>Material Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Batch No</b></td>
                <td style="width:5%; text-align:centre;"><b>A.R No</b></td>
                <td style="width:5%; text-align:centre;"><b>Grade</b></td>
                <td style="width:10%; text-align:centre;"><b>Analyasis Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Release Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample Qty</b></td>
                <td style="width:5%; text-align:centre;"><b>Rack No	</b></td>
                <td style="width:10%; text-align:centre;"><b>Checked By	</b></td>
                <td style="width:10%; text-align:centre;"><b>Status</b></td>
            </tr>
            <tr>
                <td style="width:5%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:5%;"></td>
                <td style="width:5%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:5%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
            </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ControlSampleReview.pdf', 'I');
    }



}
?>