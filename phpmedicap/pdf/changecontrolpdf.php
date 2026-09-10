<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
 // ini_set('display_errors', 1);
    // error_reporting(E_ALL); 
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
 $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
$conn->query($sql);
    if($_GET["type"]=="changecontrol"){
        $sql = "SELECT * FROM changecontrol";
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
                $_GET['filename']='Change Control'; $_GET['pdftype']='headfoot'; include("../pdfimp.php");
               
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

 if($_GET["type"]=="complaint"){
      $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php"); 
               $sql = "SELECT * FROM complaint where status = 'TO_LOG' AND plant_id = '".$_GET['plant_id']."'"; 
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
           $html.='
    <h2  style=" text-align:centre;">MARKET COMPLAINT INVESTIGATION CONCLUSION REPORT</h2>
    <table cellpadding="5"  border="1">
     <tr>
            <td style=" text-size:12px;" colspan="4"><b>Complaint Details</b></td>
            
        </tr>
        <tr>
            <td><b>Product Name:</b></td>
            <td colspan="3">' . $row['product_name'] . '</td>
        </tr>
        <tr>
            <td><b>Reference Complaint No.:</b></td>
            <td>' . $row['complaintNo'] . '</td>
            <td><b>Market Complaint Report No.:</b></td>
            <td>' . $row['MarketCompNO'] . '</td>
        </tr>
        <tr>
            <td><b>Batch No.:</b></td>
            <td>' . $row['batch_no'] . '</td>
            <td><b>Date of Receipt:</b></td>
            <td>' . $row['dateOfComplaint'] . '</td>
        </tr>
        <tr>
            <td rowspan="2" ><b>Pack Details:</b></td>
            <td>' . $row['PackDetails'] . '</td>
            <td><b>Mfg. Dt.:</b></td>
            <td>' . $row['mfg_date'] . '</td>
        </tr>
        <tr>
            <td></td>
            <td><b>Exp. Dt.:</b></td>
            <td>' . $row['exp_date'] . '</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4"><b>Description / Nature of Complaint:</b><br>
               ' . $row['descNatureOdComplaint'] . '
            </td>
        </tr>
        <tr>
            <td><b>Is the complaint repeated?</b></td>
            <td colspan="3" class="checkbox-group">
                ' . $row['complaintSampleRepeated'] . '
            </td>
        </tr>

        <tr>
            <td colspan="4"><b>Summary and Conclusion of Investigation report:</b><br>
               ' . $row['SummaryReport'] . '
            </td>
        </tr>
        <tr>
            <td><b>Investigation Details:</b></td>
            <td></td>
            <td><b>Investigation No.:</b></td>
            <td>' . $row['InvestigationNo'] . '</td>
        </tr>
        <tr>
            <td colspan="2"><b>Investigation Outcome Analysis and Summary & Conclusion:</b></td>
            <td colspan="2">' . $row['InvestigationSummary'] . '</td>
        </tr>
        <tr>
            <td><b>Complaint belongs to:</b></td>
            <td colspan="3" class="checkbox-group">
              ' . $row['ComplaintBelongs'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4"><b>Root cause:</b><br>
                <textarea>' . $row['RootCause'] . '</textarea>
            </td>
        </tr>
        <tr>
            <td><b>Summary and Conclusion:</b></td>
            <td colspan="3" class="checkbox-group">
             ' . $row['SummaryAndConclusion'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4">
             ' . $row['SummaryConclusion'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4"><b>CAPA:</b></td>
        </tr>
        <tr>
            <td colspan="4"><b>Corrective Action:</b><br>
                ' . $row['CorrectiveAction'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4"><b>Preventive Action:</b><br>
               ' . $row['PreventiveAction'] . '
            </td>
        </tr>
        <tr>
            
            <td colspan="4"> <b>CAPA No. (If any):</b></td>
        </tr>
         <tr>
            <td><b>Remarks:</b></td>
            <td colspan="3"> ' . $row['Remark'] . '</td>
        </tr>
    </table>
  <br pagebreak="true"/> 
    <div></div>
    <b>Note:</b>
    <ul>
        <li>Response shall be sent to Complainant as per format “Reply to Market Complaint”.</li>
        <li>If no further correspondence on the complaint is received within 30 days of sending the Market Complaint Investigation Report, the complaint and response will be considered as closed.</li>
        <li>If further correspondence and/or feedback is received after closing of the complaint from the complainant, write the details in the remarks section.</li>
    </ul>
</div>

    <table  border="1" cellpadding="5">
        <tr>
            <td><b>Production Head:</b></td>
            <td><b>Q.A. Head:</b></td>
        </tr>
        <tr>
            <td><b>Sign : </b> ' . $row['qaHeadBy'] . '</td>
            <td><b>Sign : </b>  ' . $row['qaHeadAppvlBy'] . ' </td>
        </tr>
        <tr>
            <td><b>Date: </b>   ' . $row['qaHeadOn'] . '</td>
            <td><b>Date: </b>   ' . $row['qaHeadAppvlOn'] . '</td>
        </tr>';
           $html.='</table>';
               
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Complaint.pdf', 'I');
    }


}
?>