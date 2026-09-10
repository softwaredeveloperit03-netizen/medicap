<?php
//   ini_set('display_errors', 1);
//     error_reporting(E_ALL); 
    
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
 $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
$conn->query($sql);
    if($_GET["type"]=="changecontrol"){
       $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php"); 
       $id=$_GET["ctrl_no"];
        $sql = "SELECT * FROM changecontrol where ctrl_no= '$id' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product_details = json_decode($row['product_details']);
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
        	    <table cellpadding="8">
            	    <tr>
            	        <td colspan="4" style=" text-align:center; background-color:#DDDAD9;"><b>Change Control</b></td>
            	    </tr>
            	    <tr>
        	            <td><b>Change Control  No:</b> </td>
        	            <td>'.$row["ctrl_no"].'</td>
        	        
                        <td><b>Change Control Issued By</b></td>
        	            <td> '.$row['entryBy'].'</td>
        	        </tr>
        	         <tr>
            	        <td colspan="4" style=" text-align:center; background-color:#DDDAD9;"><b>Initiator Department</b></td>
            	    </tr>
        	        <tr>
                        <td>Initiated By</td>
                        <td>'.$row['entryBy'].'</td>
                        <td>Date Of Issuance</td>
                        <td>'.$row['dateOfIssuance'].'</td>
                    </tr>
                    <tr>
                        <td>Department</td>
                        <td>'.$row['department_name'].'</td>
                        <td>Section</td>
                        <td>'.$row['section'].'</td>
                    </tr>
                    <tr>
                        <td>Name Of Product / Document</td>
                        <td>'.$row['nameOfProductDoc'].'</td>
                        <td>Batch No/Document No</td>
                        <td>'.$row['batchNoDocNo'].'</td>
                    </tr>
                     <tr>
            	        <td>Changed Requested For</td>
            	         <td colspan="3">'.$row['changeReqFor'].'</td>
            	    </tr>
            	    <tr>
            	        <td>Standard Current Procedure / Document or existing procedure / document</td>
            	         <td colspan="3">'.$row['existingProcedure'].'</td>
            	    </tr>
            	    <tr>
            	        <td>Details Of Change Proposed</td>
            	         <td colspan="3">'.$row['changedDetails'].'</td>
            	    </tr>
            	    <tr>
            	        <td>Justification Proposed Change</td>
            	         <td colspan="3">'.$row['justificationOfChange'].'</td>
            	    </tr>
        	       
        	       <tr>';
        	        $json_obj = $row['changeAffDoc'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                    $k=1;
                    $docNo = $values['docNo'];
                    $docTitle = $values['docTitle'];
                    $effectiveDate = $values['effectiveDate'];
                    $typeOfImpact = $values['typeOfImpact'];
                    $tcdImplemantation = $values['tcdImplemantation'];

        	  $html.='  
        	  <table cellpadding="2">
                        <tr> <br>
                            <td colspan="4"><b>Change Affected Documents :</b></td>
                        </tr>
                        <tr>
                            <td><b>Sr. No.</b></td>
                            <td><b>Document No.</b></td>
                            <td><b>Document Title</b></td>
                            <td><b>Effective Date</b></td>
                            <td><b>Type Of Impact Amended / Cancelled</b></td>
                            <td><b>TDC Implementation</b></td>
                        </tr>
                        <tr>
                            <td>'. $k++ .'</td>
                            <td>'.$docNo.'</td>
                            <td>'.$docTitle.'</td>
                            <td>'.$effectiveDate.'</td>
                            <td>'.$typeOfImpact.'</td>
                            <td>'.$tcdImplemantation.'</td>
                            
                        </tr>
                    </table><br>';}
        	      $html.='   </tr>';
        	      $html.=' <br><tr>
                        <td>Tentative Date Of Closing</td>
                        <td>'.$row['tentativeDateClosing'].'</td>
                        <td>Remark</td>
                        <td>'.$row['remark'].'</td>
                    </tr>
                    <tr>
                        <td>Consern HOD Comment :</td>
                        <td  colspan="3">'.$row['concernHodComment'].'</td>
                    </tr>
                     <tr>
                        <td>Scope Of Change :</td>
                        <td  colspan="3">'.$row['scopeOfChange'].'</td>
                    </tr>
                     <tr><br>
                       <table cellpadding="6">
                        <tr>
                            <td colspan="5"><b>CHANGE CONTROL ASSESSMENT BY QA</b></td>
                        </tr>
                        <tr>
                            <td colspan="4"><b>Type - A (Minor):</b> Minor Changes Who do not have any detectable impact on the quality attributes of the product and having no regulatory impact.</td>
                            <td> '.$row['typeMinor'].'</td>
                        </tr>
                        <tr>
                            <td colspan="4"><b>Type - A (Major):</b> Changes that are likely or may have minor impact on the quality attributes of the product like Major changes BPCR, MFR Specification addition & deletion of any test requires intimation to regulatory agency and marketing authority / customer.</td>
                            <td> '.$row['typeMajor'].'</td>
                        </tr>
                        <tr>
                            <td colspan="4"><b>Type - A (Critical):</b> Changes that are having significant impact on the quality attributes of the product like major changes BPCR, MFR Specification addition & deletion of any test change in validated parameter and requires Pre-Approval from the registration agency and marketing authority / customer.</td>
                            <td> '.$row['typeCritical'].'</td>
                        </tr>
                    </table>
                    </tr>
                    <tr>
                            <td colspan="5" style="text-align: center;"><b>Impact On Regulatory Affairs & Marketing Authority / Customer</b></td>
                        </tr>
                        <tr>
                            <td ><b>Review Comment</b> <br>
                            </td>   <td  colspan="4">'.$row['reviewCommmentByRegu'].'</td>
                        </tr>
                        <tr>
                            <td ><b>Conclusion</b></td>
                            <td colspan="4">'.$row['impactReguConclusion'].'</td>
                        </tr>';
                             $html.='
                             <tr><br>
                          <table cellpadding="6">
                            <tr>
                                <td colspan="5"><b>Action To Be Carried Out for the Proposed Change</b></td>
                            </tr>
                            <tr>
                                <td style=" text-align:center;"><b>Sr.No.</b></td>
                                <td style=" text-align:center;" colspan="2"><b>Action</b></td>
                                <td style=" text-align:center;" colspan="2"><b>Tentative Date Of Completion</b></td>
                            </tr>';
                           $json_obj = $row['actionData'];
                            $array = json_decode($json_obj, true);
                              $k=1;
                            foreach ($array as $values)
                            {
                              
                                $action = $values['action'];
                                $tcd = $values['tcd'];
                    	  $html.='  
                            <tr>
                                <td style=" text-align:center;">' . $k++ . '</td>
                                <td style=" text-align:left;" colspan="2">'.$action.'</td>
                                <td style=" text-align:center;" colspan="2">'.$tcd.'</td>
                            </tr> 
                        </table>';}
                       $html.='</tr>
                          <tr>
                            <td colspan="4"><b> Whether all related documents are drafted and evaluated:</b></td>
                            <td> '.$row['action1'].'</td>
                          </tr>
                          <tr>
                            <td colspan="4"><b> All related departments are intimated:</b></td>
                            <td> '.$row['action2'].'</td>
                          </tr>
                           <tr>
                            <td colspan="5"><b> QA Representative Comment:</b><br>
                            '.$row['qaRepresentativeCommentOnQaReviewed'].' </td>
                          </tr>
                          <tr>
                            <td colspan="5"><b> Approval Of Change By QA Head:</b> </td>
                          </tr>
                          <tr>
                            <td colspan="4"><b> Approval Of Change:</b></td>
                            <td>'.$row['approvalOfChange'].' </td>
                          </tr>
                          <tr>
                            <td ><b> Comment By Head QA/Designee:</b></td>
                            <td colspan="4">'.$row['commentByHeadQaOnAPprovalOfChange'].' </td>
                          </tr>
                          <tr>
                            <td ><b> Justification if rejected CCF:</b></td>
                            <td colspan="4">'.$row['justificationOfRejection'].' </td>
                          </tr>
                           <tr>
                            <td colspan="5"><b> Monitoring, Follow Up And Closure Of Change:</b> </td>
                          </tr>
                          <tr>
                            <td ><b> Justification if rejected CCF:</b></td>
                            <td colspan="4">'.$row['justificationOfRejection'].' </td>
                          </tr>
                          <tr>
                            <td colspan="4"><b> Extenssion On tentative close date (TCD):</b></td>
                            <td>'.$row['extTcd'].' </td>
                          </tr>
                            <tr>
                            <td colspan="4"><b> Extenssion On Alternate tentative close date (ATCD):</b></td>
                            <td>'.$row['extAtcd'].' </td>
                          </tr>
                           <tr>
                            <td colspan="4"><b> The Change is Implemented as proposed:</b></td>
                            <td>'.$row['impCheck1'].' </td>
                          </tr>
                           <tr>
                            <td colspan="4"><b>Training Is Completed & Evaluated To all concerned:</b></td>
                            <td>'.$row['impCheck2'].' </td>
                          </tr>
                           <tr>
                            <td colspan="4"><b> Others, Specify:</b></td>
                            <td>'.$row['impCheck3'].' </td>
                          </tr> <tr>
                            <td colspan="4"><b> Change effective from & Date:</b></td>
                            <td>'.$row['impCheck4'].' </td>
                          </tr>
                          <tr>
                          <table cellpadding="3">
                            <tr>
                                <td colspan="5"><b>QA Assessment Checklist</b></td>
                            </tr>
                            <tr>
                                <td><b>Sr.No.</b></td>
                                <td><b>Document Type</b></td>
                                <td><b>Review</b></td>
                                <td><b>Existing Ref. No.</b></td>
                                <td><b>Revised Ref. No.</b></td>
                            </tr>';
                           $json_obj = $row['checlistData'];
                            $array = json_decode($json_obj, true);
                              $k=1;
                            foreach ($array as $values)
                            {
                              
                                $a = $values['docType'];
                                $b = $values['review'];
                                 $c = $values['extRefNo'];
                                $d = $values['revRefNo'];
                    	  $html.='  
                            <tr>
                                <td style=" text-align:center;">' . $k++ . '</td>
                                <td style=" text-align:center;">' . $a . '</td>
                                <td style=" text-align:center;">' . $b . '</td>
                                <td style=" text-align:center;">' . $c . '</td>
                                <td style=" text-align:center;">' . $d . '</td>
                                
                            </tr> 
                        </table>';}
                             $html.='</tr><br> 
                             <tr>
                          <table cellpadding="3">
                            <tr>
                                <td colspan="5"><b>Closin Checklist</b></td>
                            </tr>
                            <tr>
                                <td style=" text-align:center;"><b>Sr.No.</b></td>
                                <td style=" text-align:center;" colspan="2"><b>Action</b></td>
                                <td style=" text-align:center;" colspan="2"><b>Check Point</b></td>
                            </tr>';
                           $json_obj = $row['closinChecklist'];
                            $array = json_decode($json_obj, true);
                              $k=1;
                            foreach ($array as $values)
                            {
                                $action = $values['action'];
                                $checkPoint = $values['checkPoint'];
                    	  $html.='  
                            <tr>
                                <td style=" text-align:center;">' . $k++ . '</td>
                                <td style=" text-align:left;" colspan="2">'.$action.'</td>
                                <td style=" text-align:left;" colspan="2">'.$checkPoint.'</td>
                            </tr> ';}
                       $html.='  </table>
                      </tr><br>
                      <tr>
                      <table cellpadding="3">
                      <tr>
                        <td></td>
                        <td><b>Change Reviewed QA</b></td>
                        <td><b>Change Closed By Head QA</b></td>
                      </tr>
                     
                      <tr>
                        <td style="text-align: left;"><b>Signature</b></td>
                        <td><b> '.$row['changeReviewedBy'].'</b></td>
                        <td ><b> '.$row['closedBy'].'</b></td>
                      </tr>
                      <tr>
                        <td style="text-align: left;"><b>Date</b></td>
                        <td >
                          <b> '.$row['changeReviewedOn'].'</b>
                        </td>
                        <td >
                          <b> '.$row['closedOn'].'</b>
                        </td>
                      </tr>
                    </table>
                    </tr>
        	    </table>
        	    ';
            }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('changecontrol.pdf', 'I');
        }else{
            echo 'Invalid Change Control No.';
        }
    }

else if($_GET["type"]=="ComplaintConclusion"){
      $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php"); 
            //   $sql = "SELECT * FROM complaint where status = 'TO_LOG' AND plant_id = '".$_GET['plant_id']."'"; 
            //     $result = $conn->query($sql);
            //     $row = $result->fetch_assoc();
                     $sql = "SELECT * FROM complaint where status = 'TO_LOG' AND plant_id = '".$_GET['plant_id']."'"; 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            $sql1 = "SELECT 
            CONCAT(e1.firstname, ' ', e1.lastname) AS entryByName, 
            CONCAT(e2.firstname, ' ', e2.lastname) AS qaUseByName,
            CONCAT(e3.firstname, ' ', e3.lastname) AS qaHeadReccByName,
            CONCAT(e4.firstname, ' ', e4.lastname) AS qaConclusionByName,
            CONCAT(e5.firstname, ' ', e5.lastname) AS qaRemarkByName,
            CONCAT(e6.firstname, ' ', e6.lastname) AS qaHeadByName
            FROM 
                employee e1
            LEFT JOIN 
                employee e2 ON e2.emp_id = '".$row["qaUseBy"]."' AND e2.plant_id = '".$_GET["plant_id"]."'
            LEFT JOIN 
                employee e3 ON e3.emp_id = '".$row["qaHeadReccBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                LEFT JOIN 
                employee e4 ON e4.emp_id = '".$row["InvestigationBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                LEFT JOIN 
                employee e5 ON e5.emp_id = '".$row["qaHeadBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                 LEFT JOIN 
                employee e6 ON e6.emp_id = '".$row["qaHeadAppvlBy"]."' AND e3.plant_id = '".$_GET["plant_id"]."'
                
            WHERE 
                e1.emp_id = '".$row["entry_by"]."' AND e1.plant_id = '".$_GET["plant_id"]."' ";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["entryByName"] =  $row1["entryByName"];
                        $row["qaUseByName"] =  $row1["qaUseByName"];
                        $row["qaHeadReccByName"] =  $row1["qaHeadReccByName"];
                         $row["qaConclusionByName"] =  $row1["qaConclusionByName"];
                           $row["qaRemarkByName"] =  $row1["qaRemarkByName"];
                            $row["qaHeadByName"] =  $row1["qaHeadByName"];
                    }
                }
                 
                
                $output[] = $row;
          
           $html.='
    <h2  style=" text-align:centre;">MARKET COMPLAINT REPORT</h2>
    <table cellpadding="5"  border="1">
        <tr>
            <td class="bold">Name of the Product</td>
            <td style="color:blue;">' . $row['product_name'] . '</td>
            <td class="bold">Batch No.</td>
            <td style="color:blue;">' . $row['batch_no'] . '</td>
        </tr>
        <tr>
            <td class="bold">Mfg. Date</td>
            <td style="color:blue;">' . $row['mfg_date'] . '</td>
            <td class="bold">Expiry Date</td>
            <td style="color:blue;">' . $row['exp_date'] . '</td>
        </tr>
        <tr>
            <td class="bold">Date of Complaint</td>
            <td colspan="3" style="color:blue;"> ' . $row['dateOfComplaint'] . '</td>
        </tr>
        <tr>
            <td class="bold">Complaint Received from</td>
            <td colspan="3" style="color:blue;">
               ' . $row['complaintReceivedFrom'] . '
            </td>
        </tr>
        <tr>
            <td class="bold">Name, Address, and Contact Details of Complainer</td>
            <td colspan="3" style="color:blue;">' . $row['detailsOfComplainer'] . '</td>
        </tr>
        <tr>
            <td class="bold">Description/Nature of Complaint(Attach written communication as applicable)</td>
            <td colspan="3" style="color:blue;">' . $row['descNatureOdComplaint'] . '</td>
        </tr>
        <tr>
            <td class="bold">Type of Complaint</td>
            <td colspan="3" style="color:blue;">
               ' . $row['complaintType'] . '
            </td>
        </tr>
        <tr>
            <td class="bold">Complaint Sample Received</td>
            <td colspan="3" style="color:blue;">
               ' . $row['complaintSampleReceived'] . '
            </td>
        </tr>
        <tr>
            <td class="bold">Immediate Action Taken (if any)By whom & what:</td>
            <td colspan="3" style="color:blue;">' . $row['immediateAction'] . '</td>
        </tr>
        <tr>
         <td colspan="4" ><b>For QA Use Only</b></td>
         </tr>
        <tr>
         <td><p>Market Complaint Category:</p></td>
            <td colspan="3" style="color:blue;">
              ' . $row['marketComplentCategory'] . '</td>
        </tr>
        <tr>
         <td><p>Historical Repetition:</p></td><td colspan="3" style="color:blue;">
                ' . $row['histRepetition'] . '
            </td>
            
            </tr>
        <tr>
            <td class="bold">Market Complaint No.</td>
            <td colspan="3" style="color:blue;">' . $row['complaintNo'] . '</td>
        </tr>
        <tr>
            <td class="bold">Complaint Received & Logged(QA Sign & Date)</td>
            <td colspan="3" style="color:blue;">' . $row['qaUseByName'] . '</td>
        </tr>
        <tr>
            <td class="bold">Recommendations from Head QA</td>
            <td colspan="3" style="color:blue;">' . $row['qaHeadRecc'] . '</td>
        </tr>
        <tr>
            <td class="bold">Head QA Sign & Date</td>
            <td colspan="3" style="color:blue;">' . $row['qaHeadReccByName'] . '</td>
        </tr>
        <tr>
        <td colspan="4">  <p><b>Note:</b> Acknowledgement shall be given to the complainant after allotment of the market complaint.</p></td>
        </tr>
    </table>
    
  ';  }
        }
           $html.='</table>';
               
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Complaint.pdf', 'I');
    }

 if($_GET["type"]=="complaint"){
      $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php"); 
               $sql = "SELECT * FROM complaint where status = 'TO_LOG' AND plant_id = '".$_GET['plant_id']."'"; 
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
           $html.='
           <div></div>
    <h2  style=" text-align:centre;">MARKET COMPLAINT INVESTIGATION CONCLUSION REPORT</h2>
    <table cellpadding="5"  border="1">
     <tr>
            <td style=" text-size:12px;" colspan="4"><b>Complaint Details</b></td>
            
        </tr>
        <tr>
            <td><b>Product Name:</b></td>
            <td colspan="3"  style="color:blue;">' . $row['product_name'] . '</td>
        </tr>
        <tr>
            <td><b>Reference Complaint No.:</b></td>
            <td  style="color:blue;">' . $row['complaintNo'] . '</td>
            <td><b>Market Complaint Report No.:</b></td>
            <td  style="color:blue;">' . $row['MarketCompNO'] . '</td>
        </tr>
        <tr>
            <td><b>Batch No.:</b></td>
            <td  style="color:blue;">' . $row['batch_no'] . '</td>
            <td><b>Date of Receipt:</b></td>
            <td  style="color:blue;">' . $row['dateOfComplaint'] . '</td>
        </tr>
        <tr>
            <td rowspan="2" ><b>Pack Details:</b></td>
            <td  style="color:blue;">' . $row['PackDetails'] . '</td>
            <td><b>Mfg. Dt.:</b></td>
            <td  style="color:blue;">' . $row['mfg_date'] . '</td>
        </tr>
        <tr>
            <td></td>
            <td><b>Exp. Dt.:</b></td>
            <td  style="color:blue;">' . $row['exp_date'] . '</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4"><b>Description / Nature of Complaint:</b><br>
             <p  style="color:blue;">  ' . $row['descNatureOdComplaint'] . ' </p>
            </td>
        </tr>
        <tr>
            <td><b>Is the complaint repeated?</b></td>
            <td colspan="3" class="checkbox-group">
            <p  style="color:blue;">    ' . $row['complaintSampleRepeated'] . ' </p>
            </td>
        </tr>

        <tr>
            <td colspan="4"><b>Summary and Conclusion of Investigation report:</b><br>
              <p  style="color:blue;"> ' . $row['SummaryReport'] . ' </p>
            </td>
        </tr>
        <tr>
            <td><b>Investigation Details:</b></td>
            <td></td>
            <td><b>Investigation No.:</b></td>
            <td <p  style="color:blue;">>' . $row['InvestigationNo'] . '</td>
        </tr>
        <tr>
            <td colspan="2"><b>Investigation Outcome Analysis and Summary & Conclusion:</b></td>
            <td colspan="2" <p  style="color:blue;">>' . $row['InvestigationSummary'] . '</td>
        </tr>
        <tr>
            <td><b>Complaint belongs to:</b></td>
            <td colspan="3"  style="color:blue;">
              ' . $row['ComplaintBelongs'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4"><b>Root cause:</b><br>
                <p  style="color:blue;" >' . $row['RootCause'] . '</p>
            </td>
        </tr>
        <tr>
            <td><b>Summary and Conclusion:</b></td>
            <td colspan="3"  style="color:blue;">
             ' . $row['SummaryAndConclusion'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4"  style="color:blue;">
             ' . $row['SummaryConclusion'] . '
            </td>
        </tr>
        <tr>
            <td colspan="4"><b>CAPA:</b></td>
        </tr>
        <tr>
            <td colspan="4"><b>Corrective Action:</b><br>
              <p  style="color:blue;">  ' . $row['CorrectiveAction'] . ' </p>
            </td>
        </tr>
        <tr>
            <td colspan="4"><b>Preventive Action:</b><br>
            <p  style="color:blue;">   ' . $row['PreventiveAction'] . ' </p>
            </td>
        </tr>
        <tr>
            
            <td colspan="4"> <b>CAPA No. (If any):</b></td>
        </tr>
         <tr>
            <td><b>Remarks:</b></td>
            <td colspan="3"  style="color:blue;"> ' . $row['Remark'] . '</td>
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
            <td  style="color:blue;"><b>Sign : </b> ' . $row['qaHeadBy'] . '</td>
            <td  style="color:blue;"><b>Sign : </b>  ' . $row['qaHeadAppvlBy'] . ' </td>
        </tr>
        <tr>
            <td  style="color:blue;"><b>Date: </b>   ' . $row['qaHeadOn'] . '</td>
            <td  style="color:blue;"><b>Date: </b>   ' . $row['qaHeadAppvlOn'] . '</td>
        </tr>';
           $html.='</table>';
               
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Complaint.pdf', 'I');
    }


}
?>