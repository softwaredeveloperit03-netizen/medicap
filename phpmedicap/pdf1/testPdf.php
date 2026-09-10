<?php 



// error_reporting(E_ALL);
// ini_set('display_errors', 1);


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
     if($_GET['type'] == 'LogPDF') {
         $_GET['filename'] = 'Raw Material Specification Report'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
         $html.='
      <table style=" width:100%; border-collapse: collapse; margin-bottom: 16px;">
            <tr>
                <td colspan="2" style="border: 1px solid black;padding: 8px;">
                    <p><strong>1. Incident Observed:</strong></p>
                    <div class="h-32" style="border: 1px solid black; height: 128px;"></div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: 1px solid black;padding: 8px;">
                    <p><strong>2. Probable cause of Incident/ Brief Investigation:</strong></p>
                    <div class="h-32" style="border: 1px solid black; height: 128px;"></div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Reported By</strong></p>
                </td>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Sign / Date</strong></p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: 1px solid black;padding: 8px;">
                    <p><strong>3. Immediate corrective action taken:</strong></p>
                    <div class="h-32" style="border: 1px solid black; height: 128px;"></div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Reporting Dept. Head:</strong></p>
                </td>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Sign / Date</strong></p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Reviewer-Sign and Date</strong></p>
                    <p>21/08/2024</p>
                </td>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Approver-Sign and Date</strong></p>
                    <p>21/08/2024</p>
                </td>
            </tr>
        </table>
        <div style="font-size: 12px;">
            <p>*This document is electronically signed*</p>
            <p>*This document is Master Copy*</p>
            <p>https://mehaem/shy.sharepoint.com/personal/qan_mehapharma_com/Documents/QA/DQA MASTER/SOPs/QAD/SOP/Meha
                Pharma</p>
            <p>SOP/QAD/008 Deviation &amp; Incident Management/SOP/QAD/008 FMT/002 Incident Form.docx</p>
            <p>SOP/QAD/008/FMT/002-1.1</p>
            <p>Page 1 of 2</p>
        </div>
    
    
 
    
    
         
       ';

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               
            </tr>';
            $i++;
            }
        }
     
         $html.='</table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RawMaterialSpecificationReport.pdf', 'I');
    }
    else  if($_GET['type'] == 'newPdf') {
        $_GET['filename'] = 'Raw Material Specification Report'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html = "";

// Validate and sanitize input
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$plant_id = isset($_GET["plant_id"]) ? intval($_GET["plant_id"]) : 0;

if ($id > 0 && $plant_id > 0) {
    $sql = "SELECT * FROM deviation WHERE id = ? AND plant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $plant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '
            <table class="table table-border" cellpadding="5" border="1">
                <tr>
                    <td colspan="2" style="background-color: #BCBCBC; text-align: center; font-weight: bolder;">
                        DEVIATION
                    </td>
                </tr>
                <tr><td colspan="2"><strong>Description of Deviation:</strong> ' . htmlspecialchars($row['detailsOfDev']) . '</td></tr>
                <tr><td colspan="2"><strong>Source Document:</strong> ' . htmlspecialchars($row['sourceDocument']) . '</td></tr>
                <tr><td colspan="2"><strong>Product/Material/Equipment Name:</strong> ' . htmlspecialchars($row['scopeItem']) . '</td></tr>
                <tr><td colspan="2"><strong>Batch/Lot No./Equipment ID:</strong> ' . htmlspecialchars($row['ScopeCode']) . '</td></tr>
                <tr><td colspan="2"><strong>Related to:</strong> ' . htmlspecialchars($row['relatedTo']) . '</td></tr>
                <tr><td colspan="2"><strong>Reason for Deviation:</strong> ' . htmlspecialchars($row['reasonForDeviation']) . '</td></tr>
                <tr><td colspan="2"><strong>Brief Investigation:</strong> ' . htmlspecialchars($row['briefInvestigation']) . '</td></tr>
                <tr><td colspan="2"><strong>Initiator Name:</strong> ' . htmlspecialchars($row['identifiedBy']) . '</td></tr>
            </table>
            
            <table class="table table-border" border="1" cellpadding="3">
                <tr><td colspan="2"><strong>Immediate Corrective Action Taken:</strong> ' . htmlspecialchars($row['ImmCerrAct']) . '</td></tr>
                <tr><td colspan="2"><strong>Proposed Corrective Action:</strong> ' . htmlspecialchars($row['ProCorrAct']) . '</td></tr>
            </table>
            
            <table class="table table-border" border="1" cellpadding="4">
                <tr><th colspan="3" style="background-color: #BCBCBC; text-align: center;">Deviation Consent & Review By Dept.</th></tr>
                <tr>
                    <th>Department</th>
                    <th>Consent & Review</th>
                    <th>Reviewed By</th>
                </tr>';

            // Fetch deviation consent reviews
            $sql1 = "SELECT * FROM deviationConsentReview WHERE (deviationID = ? OR deviationID = ?) AND plant_id = ?";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("iii", $row["deviation_no"], $row["id"], $plant_id);
            $stmt1->execute();
            $result1 = $stmt1->get_result();

            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $html .= '
                    <tr>
                        <td>' . htmlspecialchars($row1['department']) . '</td>
                        <td>' . htmlspecialchars($row1['consentAndReview']) . '</td>
                        <td>' . htmlspecialchars($row1['reviewBy']) . '</td>
                    </tr>';
                }
            }
            $html .= '</table>';
            
            

            // QA Evaluation & Closure Table
            $html .= '
            <table class="table table-border" border="1" cellpadding="3">
                <tr><th colspan="2">Evaluation by QA Manager</th></tr>
                <tr><td>Reoccurrence:</td><td>' . htmlspecialchars($row['reoccurrence']) . '</td></tr>
                <tr><td>Previous Deviation No:</td><td>' . htmlspecialchars($row['selectedDeviation']) . '</td></tr>
                
                <tr><td>Recurrence Details:</td><td>' . htmlspecialchars($row['recurrenceDetails']) . '</td></tr>
                <tr><td>Detailed Investigation Required:</td><td>' . htmlspecialchars($row['detailedInvestigationRequired']) . '</td></tr>
                
                 <tr><td> QA Review:</td><td>' . htmlspecialchars($row['qaReview']) . '</td></tr>
                
                <tr><td> Deviation Impact:</td><td>' . htmlspecialchars($row['deviationImpact']) . '</td></tr>
                
                
                <tr><td>Change Control Required:</td><td>' . htmlspecialchars($row['changeControlRequired']) . '</td></tr>
                
                <tr><td>Risk Assessment Required:</td><td>' . htmlspecialchars($row['riskAssessmentRequired']) . '</td></tr>
                <tr><td>Process Validation Required:</td><td>' . htmlspecialchars($row['processValidationRequired']) . '</td></tr>
                <tr><td>Cleaning Validation Required:</td><td>' . htmlspecialchars($row['cleaningValidationRequired']) . '</td></tr>
                <tr><td>Stability Study Required:</td><td>' . htmlspecialchars($row['stabilityStudyRequired']) . '</td></tr>
                <tr><td>CAPA Required:</td><td>' . htmlspecialchars($row['capaRequired']) . '</td></tr>
                <tr><td>Other Impact Details::</td><td>' . htmlspecialchars($row['otherImpactDetails']) . '</td></tr>
                <tr><td> Classification:</td><td>' . htmlspecialchars($row['classification']) . '</td></tr>
                
                 <tr><th colspan="4">Deviation Consent & Review By Dept.</th> </tr>
                 <tr><td> Department :</td><td>' . htmlspecialchars($row['deptReview']) . '</td></tr>
                 <tr><td> Human Resource :</td><td>' . htmlspecialchars($row['NA']) . '</td></tr>
                 
                 <tr>
                    <td style="  text-align: left;"> <b>The deviation is</b></td>
                    <td>' . htmlspecialchars($row['deviationApproval']) . '</td>
                 </tr>
            </table>';

            $html .= '
            <table class="table table-border" border="1" cellpadding="6">
               
                <tr><td>Corrective and Preventive Action Implemented</td><td >' . htmlspecialchars($row['capaImplemented']) . '</td></tr>
                <tr><td>New Document Prepared</td><td  >' . htmlspecialchars($row['devStatus']) . '</td></tr>
                <tr><td>Document Revised</td><td  >' . htmlspecialchars($row['documentRevised']) . '</td></tr>
                <tr><td>Training Imparted to concerned persons:</td><td  >' . htmlspecialchars($row['trainingImparted']) . '</td></tr>
                <tr><td>Relevant documents attached with deviation</td><td  >' . htmlspecialchars($row['documentsAttached']) . '</td></tr>
                <tr><td>Implemented CAPA is effective for the system</td><td  >' . htmlspecialchars($row['capaEffective']) . '</td></tr>
                <tr><td>Any Other:</td><td >' . htmlspecialchars($row['otherCapa']) . '</td></tr>
                <tr><td>Closure Comments by QA Officer:</td><td  >' . htmlspecialchars($row['ClosureComment']) . '</td></tr>
                 <tr><td>Closure Comments by QA Officer:</td><td  >' . htmlspecialchars($row['commentByQaHeadApproval']) . '</td></tr>
            </table>';

            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('deviation.pdf', 'I');
        }
    }}}
}
else{
    echo "Invalid Token";
}
?>