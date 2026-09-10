<?php 
require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';



//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 
 

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
    

    
        if ($_GET["type"] == "saveInvestigation") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $sql = "update deviation set investigation_cause='".$input['investigation']."',immediate_action='".$input['immediate_action']."',investigation_immediate_action_by='".$_GET['emp_id']."',investigation_immediate_action_date='$entry_date',status='investigate' where deviation_no='".$input['deviation_no']."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     if ($_GET["type"] == "get_dev_no") {
   
        $output = array();
        $sql = "SELECT count(id)+1 as id FROM deviation";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                // $row['dev_no'] = $row['id'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     
    if ($_GET["type"] == "get_deviations") {
   
        $output = array();
         $sql = "SELECT *  ,
                (SELECT firstname from employee e WHERE a.investigation_immediate_action_by=e.emp_id)as investigation_immediate_action_by_name,
                (SELECT firstname from employee e WHERE a.risk_assessment_by=e.emp_id)as risk_assessment_by_name,
                (SELECT firstname from employee e WHERE a.department_approved_by=e.emp_id)as department_approved_by_name,
                (SELECT firstname from employee e WHERE a.qa_approved_by=e.emp_id)as qa_approved_by_name
                FROM `deviation`  a where a.departments like '%".$_GET["dept"]."%' and a.status='".$_GET["status1"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                // $row['dev_no'] = $row['id'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
        else if($_GET["type"] == "DeviationLogMehaPdf") {$_GET['filename'] = '';
        $_GET['pdftype'] = 'onlyheader';
        include("./pdfimp2.php");
        
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
                    <table class="table table-border" border="1" cellpadding="4">
                        <tr><th colspan="2">Evaluation by QA Manager</th></tr>
                        <tr><td>Reoccurrence:</td><td>' . htmlspecialchars($row['reoccurrence']) . '</td></tr>
                        <tr><td>Recurrence Details:</td><td>' . htmlspecialchars($row['recurrenceDetails']) . '</td></tr>
                        <tr><td>Detailed Investigation Required:</td><td>' . htmlspecialchars($row['detailedInvestigationRequired']) . '</td></tr>
                        <tr><td>Review of Immediate/Proposed Corrective Action:</td><td>' . htmlspecialchars($row['ProCorrAct']) . '</td></tr>
                        <tr><td>Potential Impact on Quality:</td><td>' . htmlspecialchars($row['deviationImpact']) . '</td></tr>
                        <tr><td>Change Control Required:</td><td>' . htmlspecialchars($row['changeControlRequired']) . '</td></tr>
                        <tr><td>Risk Assessment Required:</td><td>' . htmlspecialchars($row['riskAssessmentRequired']) . '</td></tr>
                        <tr><td>Process Validation Required:</td><td>' . htmlspecialchars($row['processValidationRequired']) . '</td></tr>
                        <tr><td>Cleaning Validation Required:</td><td>' . htmlspecialchars($row['cleaningValidationRequired']) . '</td></tr>
                        <tr><td>Stability Study Required:</td><td>' . htmlspecialchars($row['stabilityStudyRequired']) . '</td></tr>
                        <tr><td>CAPA Required:</td><td>' . htmlspecialchars($row['capaRequired']) . '</td></tr>
                    </table><br pagebreak="true"/> ';
        
                    $html .= '
                    <table class="table table-border" border="1" cellpadding="4">
                        <tr><th colspan="4">Closure by QA (Tick as Appropriate)</th></tr>
                        <tr>
                            <th>Closure Actions</th>
                            <th>Yes</th>
                            <th>No</th>
                            <th>NA</th>
                        </tr>
                        <tr><td>Corrective and Preventive Action Implemented</td><td colspan="3">' . htmlspecialchars($row['capaImplemented']) . '</td></tr>
                        <tr><td>New Document Prepared</td><td colspan="3">' . htmlspecialchars($row['newDocumentPrepared']) . '</td></tr>
                        <tr><td>Document Revised</td><td colspan="3">' . htmlspecialchars($row['documentRevised']) . '</td></tr>
                        <tr><td>Training Imparted</td><td colspan="3">' . htmlspecialchars($row['trainingImparted']) . '</td></tr>
                        <tr><td>Relevant Documents Attached</td><td colspan="3">' . htmlspecialchars($row['documentsAttached']) . '</td></tr>
                        <tr><td>Implemented CAPA is Effective</td><td colspan="3">' . htmlspecialchars($row['capaEffective']) . '</td></tr>
                        <tr><td>Closure Comments:</td><td colspan="3">' . htmlspecialchars($row['capaEffective']) . '</td></tr>
                    </table>';
        
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('deviation.pdf', 'I');
                }
            }}}
        
            
             //-----------------------------------------------------------------------------------------------//
                else if($_GET["type"] == "DeviationLogPdf") {
                      $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");
                         $html= "";
                         
                                    
                    $sql = "SELECT * FROM deviation WHERE id='" . $_GET["id"] . "' And plant_id= '".$_GET["plant_id"]."'";
                    
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                         
                         $html.='
                         <table class="table table-border"  cellpadding="3"  border="1">
                         <tr>
                         <th colspan="4"  style="background-color:  #BCBCBC; text-align: center;font-weight: bolder;">
                         DEVIATION FORM
                         </th>
                         </tr>
                            <tr>
                                <td style="text-align: center; font-weight: bolder;">Deviation No.</td>
                                <td style="text-align: center;">' . $row['deviation_no'] . '</td>
                                <td style="text-align: center; font-weight: bolder;">Deviation Issued By (Name/Dept.) </td>
                                <td style="text-align: center;">' . $row['identifiedBy'] . '</td>
                            </tr>
                        </table>
                         <div></div>
                         
                         <table class="table table-border"  cellpadding="3" border="1">
                          <tr>
                         <th colspan="4"  cellpadding="3" style="background-color:  #BCBCBC; text-align: center;font-weight: bolder;">
                         INITIATION OF DEVIATION (Description)
                         </th>
                         </tr>
            <tr>
                <td style="text-align: left; font-weight: bolder;">Identified By</td>
                <td style="text-align: left;">' . $row['identifiedBy'] . '</td>
                <td style="text-align: left; font-weight: bolder;">Deviation Occurred Date</td>
                <td style="text-align: left;">' . $row['devOccuredDate'] . '</td>
            </tr>
            <tr>
                <td style="text-align: left; font-weight: bolder;">Deviation Occurred In Dept.</td>
                <td style="text-align: left;">' . $row['devOccuredDept'] . '</td>
                <td style="text-align: left; font-weight: bolder;">Deviation Identified Date</td>
        <td style="text-align: left;">' . $row['devIdentifiedDate'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left; font-weight: bolder;">Time Of Deviation</td>
        <td colspan="3" style="text-align: left;">' . $row['timeOfDev'] . '</td>
    </tr>
</table>

<div></div>

<table class="table table-border"  border="1"  cellpadding="3">
    <tr>
        <td style="text-align: left; font-weight: bolder;">Type Of Deviation</td>
        <td style="text-align: left; font-weight: bolder;">' . $row['typeOfDev'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left; font-weight: bolder;">Deviation Scope</td>
        <td style="text-align: left; font-weight: bolder;">' . $row['devScope'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left; font-weight: bolder;"><b>Details Of The Deviation</b></td>
        <td style="text-align: left;"> ' . $row['detailsOfDev'] . '
        </td>
    </tr>
    <tr>
        <td style="text-align: left; font-weight: bolder;"><b>Standard Procedure / System</b></td>
        <td style="text-align: left;">  ' . $row['standProcedureSystem'] . '
        </td>
    </tr>
</table>

<div></div>

<table class="table table-border"  border="1"  cellpadding="4">
    <tr>
        <td style="text-align: left; font-weight: bolder;">Concern HOD Comment:</td>
        <td style="text-align: left;">  ' . $row['concernHodComment'] . '</td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: left; font-weight: bolder;">Immediate Actions:</td>
    </tr>
    <tr>
        <td style="text-align: left;"><b>Specify Details Below By HOD:</b> </td>
        <td style="text-align: left;">' . $row['detbyHod'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"><b>Need To Verify:</b> </td>
        <td style="text-align: left;">' . $row['needToVerify'] . '</td>
    </tr>
    <tr>
        <td style="width: 60%; text-align: left;"><b>Description of Immediate Actions:</b><br> </td>
        <td style="width: 40%; text-align: left;">
      ' . $row['descOfImmAction'] . '
        </td>
    </tr>
    <tr>
        <td style="text-align: left;"><b>Justification For Deviation (If Planned Deviation)</b></td>
        <td style="text-align: left; font-weight: bolder;">' . $row['justForDeviation'] . '</td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: left;"><b>Justification In Brief(If Justification Deviation Yes):</b>' . $row['berifJustification'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left; font-weight: bolder;">Reviewed By QA :</td>
        <td style="text-align: left;"> ' . $row['reviewOfQA'] . ' </td>
    </tr>
    <tr>
        <td style="text-align: left; font-weight: bolder;">Other Deatils ( If Any ) :</td>
        <td style="text-align: left;">  ' . $row['otherDetetails'] . ' </td>
    </tr>
</table>
<div></div>
                <table class="table table-border" border="1"  cellpadding="4">
                    <tr>
                        <td colspan="3" style="background-color:  #BCBCBC; text-align: center; font-weight: bolder; font-size: 13px;">Deviation Consent & Review By Dept.</td>
                    </tr>
                    <tr>
                        <td style=" text-align: left; font-weight: bolder; background-color:  #BCBCBC;">Department</td>
                        <td style=" text-align: left; font-weight: bolder; background-color:  #BCBCBC;">Consent & Review</td>
                        <td style=" text-align: left; font-weight: bolder; background-color:  #BCBCBC;"> Reviewed By</td>

                    </tr>';
                    
           $sql1 = "SELECT * FROM deviationConsentReview WHERE ( deviationID= '".$row["deviation_no"]."' OR deviationID= '".$row["id"]."' ) 
            AND plant_id= '".$_GET["plant_id"]."'";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                    $html.='
                    <tr>
                        <td style="text-align: left;"> ' . $row1['department'] . '</td>
                        <td style="text-align: left;">' . $row1['consentAndReview'] . '</td>
                        <td style="text-align: left;">' . $row1['reviewBy'] . '</td>
                    </tr>';
                        }
            } 
               $html.=' </table>
                <div></div>
                <table class="table"  border="1"  cellpadding="4">
                    <tr>
                        <td style="text-align: left;"><b>Review And Approval Of External Agency / MAH QP</b></td>
                        <td style="text-align: left;"> ' . $row['externalRevAndApproval'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;"><b>Comment By External Agency / MAH QP :</b><br>
                        </td>
                        <td style="text-align: left;"> ' . $row['commentByExternalAgency'] . ' <br>  ' . $row['approvalByExternalAgency'] . '</td>
                    </tr>
                </table>
                <div></div>  
                <table class="table"  border="1"  cellpadding="4">
                <tr>
                 <th colspan="2"  style="background-color:  #BCBCBC; text-align: center;font-weight: bolder;">
                 <b>Deviation Assessment By QA</b>
                 </th>
                 </tr>
    <tr>
        <td style="text-align: left;" rowspan="2"> <b>History Evaluation Of Deviation</b></td>
        <td style="text-align: left;">' . $row['evaluHistoryOfDev'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;">
            <table class="table table-border">
                <tr>
                    <td style="text-align: left;"> <b>Ref. Dev. No.</b></td>
                    <td style="text-align: left;">' . $row['refDevNo'] . '</td>
                </tr>
                <tr>
                    <td style="text-align: left;"> <b>Status</b></td>
                    <td style="text-align: left;">' . $row['devStatus'] . '</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Classification Of Deviation</b></td>
        <td style="text-align: left;">' . $row['classificationOfDevi'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Investigation Required</b><br> Yes </td>
        <td style="text-align: left;">
            <table class="table table-border">
                <tr>
                    <td style="text-align: left;"> <b>Impact Assessment Required:</b></td>
                    <td style="text-align: left;">' . $row['impactAssessmentReq'] . '</td>
                </tr>
                <tr>
                    <td style="text-align: left;"> <b>* Risk Assessment Required:</b></td>
                    <td style="text-align: left;">' . $row['riskAssessmentReq'] . '</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Approval Recommendations</b></td>
        <td style="text-align: left;">' . $row['approvalRecomm'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Relational Justification For Rejection(If Rejected)</b></td>
        <td style="text-align: left;">' . $row['rejectionJustification'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Comment By QA:</b><br> ' . $row['commentByQaAssessment'] . '</td>
        <td style="text-align: left;"> <b>Target Date Of Completion:</b><br>' . $row['targetDataOfCompletion'] . '</td>
    </tr>
</table>
<div></div>
<table class="table"  border="1"  cellpadding="4">
                 <tr>
                 <th colspan="2"  style="background-color:  #BCBCBC; text-align: center;font-weight: bolder;">
                  Approval Of Deviation By QA Head
                 </th>
                 </tr>
    
    <tr>
        <td style="text-align: left; "> <b>Comment By QA Head</b></td>
        <td style="text-align: left;">' . $row['commentByQaHeadApproval'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Relational Justification For Rejection By QA Head</b></td>
        <td style="text-align: left;">' . $row['rejectionJustificationByQaHeadApproval'] . '</td>
    </tr>
</table>
<div></div>
<table class="table" border="1"  cellpadding="4">

    <tr>
        <td style="text-align: left;" colspan="2"><b>Corrective And Preventive Actions</b></td>
    </tr>
    <tr>
        <td style="text-align: left;"><b>Root Cause Identified</b></td>
        <td style="text-align: left;">' . $row['rootCauseIdentified'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;" colspan="2">
            <table class="table table-border">
                <tr>
                    <td style="text-align: left;"><b>Tools Used To Identify Root Cause</b></td>
                    <td style="text-align: left;"><b>Name Of Tools Used</b></td>
                </tr>
                <tr>
                    <td style="text-align: left;">' . $row['toolUsedToIdentified'] . '</td>
                    <td style="text-align: left;">' . $row['nameOfTool'] . '</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="text-align: left;" colspan="2"><b>Brief Details Of Root Cause:</b><br>
           ' . $row['berifDetailsOfRootCause'] . '
        </td>
    </tr>
    <tr>
        <td style="text-align: left;"><b>CAPA Required</b></td>
        <td style="text-align: left;"> ' . $row['capaRequired'] . '</td>
    </tr>
</table>
<div></div>';
                        $html.='   <table class="table table-border" border="1"  cellpadding="4">
                                    <tr>
                                        <td style="text-align: left;background-color:  #BCBCBC;"><b>Description Of Action/Task</b></td>
                                        <td style="text-align: left;background-color:  #BCBCBC;"><b>Responsible</b></td>
                                        <td style="text-align: left;background-color:  #BCBCBC;"><b>Target Comp. Data (TCD)</b></td>
                                     </tr>';
                                     
                                        $json_obj = $row['capaData'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $discrption = $values['discriptionOfAction'];
                    $emp_name = $values['empName'];
                    $tcd = $values['tcd'];

             $html.='<tr>
                    <td> ' . $discrption . ' </td>
                    <td> ' . $emp_name . ' </td>
                    <td> ' . $tcd . ' </td>
             </tr>';
              }
              
                          $html.=' 
                                  </table>
                                  <div></div>
<table class="table table-border"  border="1"  cellpadding="4">
    <tr>
        <td style="text-align: left;" colspan="2"><b>Concern HOD Comment:</b><br>
        ' . $row['concernHodCommentAfterCapa'] . '
        </td>
    </tr>
</table>

<div></div>

<table class="table table-border"  border="1"  cellpadding="4">
    <tr>
        <td style="text-align: left;" colspan="2"><b>Reviewed By QA:</b><br>
             ' . $row['qaReviewOnCapa'] . '
        </td>
    </tr>
</table>

<div></div>


<table class="table table-border"  border="1"  cellpadding="2">
                <tr>
                 <th colspan="4"  style="background-color:  #BCBCBC; text-align: center;font-weight: bolder;">
                 Monitoring, Follow Up And Closure Of Deviation
                 </th>
                 </tr>
    <tr>
        <td style="text-align: left;" colspan="4"><b>Extension Details:</b><br>
 ' . $row['extensionDetails'] . '        </td>
    </tr>
    <tr>
        <td style="text-align: center; background-color:  #BCBCBC;" colspan="4"><b>Closure Of Action</b></td>
    </tr>
    <tr>
        <td style="text-align: left; background-color:  #BCBCBC;"><b>Action Desc. For Closure</b></td>
        <td style="text-align: left; background-color:  #BCBCBC;"><b>Responsible</b></td>
        <td style="text-align: left; background-color:  #BCBCBC;"><b>Closed Date</b></td>
        <td style="text-align: left; background-color:  #BCBCBC;"><b>Remark</b></td>
    </tr>';
    
       $json_obj = $row['closureData'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $remark = $values['remark'];
                    $closedDate = $values['closedDate'];
                    $empName = $values['empName'];
                    $actionDesc = $values['actionDesc'];
             $html.='<tr>
                    <td> ' . $actionDesc . ' </td>
                    <td> ' . $empName . ' </td>
                      <td> ' . $closedDate . ' </td>
                    <td> ' . $remark . ' </td>
             </tr>';
              }
     $html.='
</table>

<div></div>
  <br pagebreak="true"/> 
<table class="table table-border"  border="1"  cellpadding="4">
    <tr>
        <td style="text-align: left;"> <b>CAPA Status </b> </td>
        <td style="text-align: left;"> ' . $row['capaImple'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Impacted Documents Are revised as per the change Control SOP </b></td>
        <td style="text-align: left;"> ' . $row['impactedDocumentRevised'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Training To Relevant Personnel Imparted</b></td>
        <td style="text-align: left;"> ' . $row['trainingToRelevent'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Impacted Product Name / Document Name</b></td>
        <td style="text-align: left;"> ' . $row['impactedDocumentRevised'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Impacted Batch No./Equipment/Instrument id. No.</b></td>
        <td style="text-align: left;"> ' . $row['impactedBEINo'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Impacted Activity / Process / Others</b></td>
        <td style="text-align: left;"> ' . $row['impactedActiProOth'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Other Impacted Product Name / Document Name</b></td>
        <td style="text-align: left;"> ' . $row['othImpactedProdDoc'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Risk Assessment / Impact Assessment Status</b></td>
        <td style="text-align: left;"> ' . $row['riskAssImpAsssStatus'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Risk Assessment / Impact Assessment Post Monitoring</b></td>
        <td style="text-align: left;"> ' . $row['postMonitoringOfAss'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;"> <b>Deviation Closer Date (Within 90 Days)</b></td>
        <td style="text-align: left;"> ' . $row['devCloserDate'] . '</td>
    </tr>
    <tr>
        <td style="text-align: left;" colspan="2"> <b>Closure Comment By QA Head</b> <br>
              ' . $row['closureCommentByQaHead'] . '        </td>
    </tr>
</table>';
                    
                }
                $html.='';
             $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('deviation.pdf', 'I');
        }
        }
        //-----------------------------------------------------------------------------------------------//
    if ($_GET["type"] == "saveQmsDeviations") {
                  
                    $input    = $_POST;
                    $target_dir = "../../upload/deviation/";
                    $ic = $input['issue_code'];
                
                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$ic."initialFile".basename($_FILES["jugad"]["name"]);
                        $file_name = $ic."initialFile".basename($_FILES["jugad"]["name"]);
                         move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }
                          
    $sql = "insert into deviation(departments ,entry_by, entry_date,location, dev_no, dev_title, deviation_for, referred_issue, issue_code,
    product, date_id,date_initial, lot_no, batch_no, Immediate_action, `system`, date_target, stage, investigation, extended_invest,
    description ,occurnc_date,reference,material_type,equipment_name,other_detail,customer,event_to,file_inital_dept,market,plant_id,
    equipment_no)Values('".$input['department_name']."','".$_GET['emp_id']."','$entry_date','".$input['location']."','$dev_no',
    '".$input['dev_title']."','".$input['deviation_for']."','".$input['referred_issue']."','".$input['issue_code']."','".$input['product']."',
    '".$input['date_id']."','".$input['date_initial']."','".$input['lot_no']."','".$input['batch_no']."','".$input['Immediate_action']."',
    '".$input['system']."','".$input['date_target']."','".$input['stage']."','".$input['investigation']."','".$input['extended_invest']."' ,
    '".$input['description']."','".$input['date_occurrence']."','".$input['reference']."','".$input['material_type']."',
    '".$input['equipment_name']."','".$input['other_detail']."','".$input['customer']."','".$input['event_to']."', '$file_name' ,
    '".$input['market']."','".$_GET['plant_id']."' ,'".$input['equipment_no']."') ";
                
    
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
            
            
    } 
    //----------------------------------------------------------------------------------------------//
    
    // if ($_GET["type"] == "saveQmsDeviations1") {
    
    //     $sql = "UPDATE deviation SET  status='pending',
           
    //         departments = '".$input['department_name']."',
    //         entry_by = '".$_GET['emp_id']."',
    //         entry_date = '$entry_date',
    //         location = '".$input['location']."',
    //         dev_title = '".$input['dev_title']."',
    //         deviation_for = '".$input['deviation_for']."',
    //         referred_issue = '".$input['referred_issue']."',
    //         issue_code = '".$input['issue_code']."',
    //         product = '".$input['product']."',
    //         date_id = '".$input['date_id']."',
    //         date_initial = '".$input['date_initial']."',
    //         lot_no = '".$input['lot_no']."',
    //         batch_no = '".$input['batch_no']."',
    //         Immediate_action = '".$input['Immediate_action']."',
    //         `system` = '".$input['system']."',
    //         date_target = '".$input['date_target']."',
    //         stage = '".$input['stage']."',
    //         investigation = '".$input['investigation']."',
    //         extended_invest = '".$input['extended_invest']."',
    //         description = '".$input['description']."',
    //         occurnc_date = '".$input['date_occurrence']."',
    //         reference = '".$input['reference']."',
    //         material_type = '".$input['material_type']."',
    //         equipment_name = '".$input['equipment_name']."',
    //         other_detail = '".$input['other_detail']."',
    //         customer = '".$input['customer']."',
    //         event_to = '".$input['event_to']."'  WHERE id='".$_GET["id"]."' ";
    
    
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // } 
    
      if ($_GET["type"] == "saveQmsDeviations1") {
            
                   $sql = "UPDATE deviation SET 
                        departments = '".$input['department_name']."',
                        status = 'pending',
                        entry_by = '".$_GET['emp_id']."',
                        entry_date = '".$entry_date."',
                        location = '".$input['location']."',
                        dev_title = '".$input['dev_title']."',
                        deviation_for = '".$input['deviation_for']."',
                        referred_issue = '".$input['referred_issue']."',
                        issue_code = '".$input['issue_code']."',
                        product = '".$input['product']."',
                        date_id = '".$input['date_id']."',
                        date_initial = '".$input['date_initial']."',
                        lot_no = '".$input['lot_no']."',
                        batch_no = '".$input['batch_no']."',
                        Immediate_action = '".$input['Immediate_action']."',
                        `system` = '".$input['system']."',
                        date_target = '".$input['date_target']."',
                        stage = '".$input['stage']."',
                        investigation = '".$input['investigation']."',
                        extended_invest = '".$input['extended_invest']."',
                        description = '".$input['description']."',
                        occurnc_date = '".$input['date_occurrence']."',
                        reference = '".$input['reference']."',
                        material_type = '".$input['material_type']."',
                        equipment_name = '".$input['equipment_name']."',
                        other_detail = '".$input['other_detail']."',
                        customer = '".$input['customer']."',
                        event_to = '".$input['event_to']."',
                        market = '".$input['market']."',
                        plant_id = '".$_GET['plant_id']."',
                        equipment_no = '".$input['equipment_no']."'
                          WHERE id = ".$_GET['id'];
                
    
                    if ($conn->query($sql)) {
                        echo "{\"status\":\"success\"}";
                        
                        
                    } else {
                        echo "{\"status\":\"".$conn->error."\"}";
                    }
                } 
                
                
    if ($_GET["type"] == "save_deviation_Attachment") {
        
        $input=$_POST;
          $target_dir = "../../../upload/deviation/";
    
    $file_name = "";
    if(isset($_FILES["document"]["name"])){
        $target_file = $target_dir."Devialtion-".basename($_FILES["document"]["name"]);
        $file_name = "Devialtion-".basename($_FILES["document"]["name"]);
        move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
    }
        
        $sql = "insert into deviation_atachment(type,plant_id,title,file,deviation_no,department)Values('".$input['type']."','".$_GET['plant_id']."','".$input['document_title']."','$file_name','".$input['dev_id']."','".$input['department']."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } 
     if ($_GET["type"] == "get_deviation_Attachment") {
   
        $output = array();
         $sql = "SELECT * FROM deviation_atachment where department='".$_GET["department1"]."' and deviation_no='".$_GET['deviation_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                // $row['dev_no'] = $row['id'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    // other_action_plan_details
    // other_action_plan
    // preventive_action_plan_details
    // preventive_action_plan
    // Corrective_p
    // corrective_action_plan
    // investigation
    // comments
    //Preventive
    //document_hod
    
    
     else if($_GET["type"] == "saveDevaitonDeptHod"){
             //  $sql = "UPDATE deviation SET ";
             if($_GET["deptName"]=='Microbiology'){
         $dept='microbiology';
             }else  if($_GET["deptName"]=='Store'){
         $dept='warehouse';
             }
            else  if($_GET["deptName"]=='Marketing'){
         $dept='bd';
             } else  if($_GET["deptName"]=='Regulatory'){
         $dept='ra';
             }else{
         $dept=$_GET["deptName"];
             }
           $dept = str_replace(' ', '_', $dept);
           $count=$input['count']+1;
             $sql=" UPDATE deviation SET $dept='done',count='$count' WHERE id='" . $_GET["id"] . "'";
        //                $sql = "UPDATE deviation SET ";
        //                 if ($_GET["deptName"] == 'Purchase') {
        //                     $sql .= "purchase='done' WHERE id='" . $_GET["id"] . "'";
        //                 } else if ($_GET["deptName"] == 'Human Resource') {
        //                     $sql .= "hr='done' WHERE id='" . $_GET["id"] . "'";
        //                 } else if ($_GET["deptName"] == 'Quality Assurance') {
        //                     $sql .= "qc='done' WHERE id='" . $_GET["id"] . "'";
        //                 } else if ($_GET["deptName"] == 'Quality Control') {
        //                     $sql .= "qa='done' WHERE id='" . $_GET["id"] . "'";
        //                 } else if ($_GET["deptName"] == 'Production') {
        //                     $sql .= "production='done' WHERE id='" . $_GET["id"] . "'";
        //                 }
        //                 else if ($_GET["deptName"] == 'Engineering') {
        //                     $sql .= "engineering='done' WHERE id='" . $_GET["id"] . "'";
        //                 }
        //                 else if ($_GET["deptName"] == 'IT') {
        //                     $sql .= "it='done' WHERE id='" . $_GET["id"] . "'";
        //                 }
                                                
                          if($conn->query($sql)){
                            echo "{\"status\":\"success\"}";
                        }else {
                            echo "{\"status\":\"failed\"}";
                        }
                            }
    else if($_GET["type"] == "DeptReturnDeviation"){
             //  $sql = "UPDATE deviation SET ";
            
            $sql=" UPDATE deviation SET status='DeptReturn' WHERE id='" . $_GET["id"] . "'";

            if($conn->query($sql)){
             echo "{\"status\":\"success\"}";
             }else {
              echo "{\"status\":\"failed\"}";
           }
          }

                            
    else if($_GET["type"] == "saveDevaitonDeptHodImpact"){
                            //  $dept=$_GET["deptName"];
                            //   $dept = str_replace(' ', '_', $dept);
                            //  $sql=" UPDATE deviation SET $dept='done_impact',
                            //             Quality_Assurance='done_impact',
                            //       reason_decision = '".$input['reason_decision']."',
                            //       hod_decision = '".$input['hod_decision']."'
                            //       WHERE id='" . $_GET["id"] . "'";


            if($_GET["deptName"]=='Microbiology'){
                 $dept='microbiology';
            }else  if($_GET["deptName"]=='Store'){
                 $dept='warehouse';
            }
            else  if($_GET["deptName"]=='Marketing'){
                 $dept='bd';
            } else  if($_GET["deptName"]=='Regulatory'){
                 $dept='ra';
            }else{
                 $dept=$_GET["deptName"];
            }
                $dept = str_replace(' ', '_', $dept);
                $count=$input['count']+1;
                              
           
            $sql=" UPDATE deviation SET status='done_impact', $dept='done_impact',count='$count' WHERE id='" . $_GET["id"] . "'";   
                                   
                          // echo    $sql = "UPDATE deviation SET ";
                        //  if ($_GET["deptName"] == 'Marketing') {
                        //     $sql.= "reason_marketing = '".$input['reason_marketing']."', decison_marketing = '".$input['decison_marketing']."' WHERE id='" . $_GET["id"] . "'";
                        // } else if ($_GET["deptName"] == 'Human Resource') {
                        //     $sql.= "reason_hr = '".$input['reason_hr']."',decison_hr = '".$input['decison_hr']."' WHERE id='" . $_GET["id"] . "'";
                        //  } else if ($_GET["deptName"] == 'Quality Assurance') {
                        //      $sql.= "reason_qa = '".$input['reason_qa']."', decison_qa = '".$input['decison_qa']."' WHERE id='" . $_GET["id"] . "'";
                        //  } else if ($_GET["deptName"] == 'Quality Control') {
                        //      $sql.= "reason_qc = '".$input['reason_qc']."',decison_qc = '".$input['decison_qc']."' WHERE id='" . $_GET["id"] . "'";
                        //  } else if ($_GET["deptName"] == 'Production') {
                        //      $sql.= "reason_prod = '".$input['reason_prod']."',decison_prod = '".$input['decison_prod']."' WHERE id='" . $_GET["id"] . "'";
                        //  }
                        //  else if ($_GET["deptName"] == 'Engineering') {
                        //      $sql.= " reason_engg = '".$input['reason_engg']."',decison_engg = '".$input['decison_engg']."' WHERE id='" . $_GET["id"] . "'";
                        //  }
                        //  else if ($_GET["deptName"] == 'IT') {
                        //      $sql.= "reason_it = '".$input['reason_it']."', decison_it = '".$input['decison_it']."' WHERE id='" . $_GET["id"] . "'";
                        //  }
                         
            if($conn->query($sql)){
                echo "{\"status\":\"success\"}";
            }else {
                echo "{\"status\":\"failed\"}";
            }
    }
                            
    else if($_GET["type"] == "saveDevaitonDeptFinal"){
                             $dept=$_GET["deptName"];
                              $dept = str_replace(' ', '_', $dept);
                             $sql=" UPDATE deviation SET $dept='dept_final',
                             status = 'approved',
                                   reason_decision = '".$input['reason_decision']."',
                                   hod_decision = '".$input['hod_decision']."'
                                   WHERE id='" . $_GET["id"] . "'";

                                  if($conn->query($sql)){
                                    echo "{\"status\":\"success\"}";
                                }else {
                                    echo "{\"status\":\"failed\"}";
                                }
                            }
                            
    else if($_GET["type"] == "saveDevaitonReviewFinal111"){
                                //   ini_set('display_errors', 1);
                                //     error_reporting(E_ALL);
                        $input = $_POST;
                                 
                                 
                        if($_GET["deptName"]=='Microbiology'){
                            $dept='microbiology';
                        }else if($_GET["deptName"]=='Store'){
                            $dept='warehouse';
                        }else if($_GET["deptName"]=='Marketing'){
                            $dept='bd';
                        } else if($_GET["deptName"]=='Regulatory'){
                            $dept='ra';
                        }else{
                            $dept=$_GET["deptName"];
                        }
                        
                           $dept = str_replace(' ', '_', $dept);
                           $count=$input['count']+1;
                          $col_head='comment_'.$dept;
                          $col_head_file='comment_file_'.$dept;
                          
                          
                    $target_dir = "../../upload/deviation/";
                    
                    
                    $id = $_GET["id"] ;
                    $dep = $col_head_file;
                
                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }
                          
                          
                 
                          
                       
                $sql=" UPDATE deviation SET  status='Ra_final', $dept='dept_final',  $col_head = '".$input['Commentsss']."',count='$count', $col_head_file='$file_name'
                WHERE id='" . $_GET["id"] . "'";

                if($conn->query($sql)){
                                     
                    echo "{\"status\":\"success\"}";
                }else {
                    echo "{\"status\":\"failed\"}";
                }
                            
    }
    
      else if($_GET["type"] == "saveDevaitonReviewFinal2"){
                                 
                        $input = $_POST;
                                 
                                 
                        if($_GET["deptName"]=='Microbiology'){
                            $dept='microbiology';
                        }else if($_GET["deptName"]=='Store'){
                            $dept='warehouse';
                        }else if($_GET["deptName"]=='Marketing'){
                            $dept='bd';
                        } else if($_GET["deptName"]=='Regulatory'){
                            $dept='ra';
                        }else{
                            $dept=$_GET["deptName"];
                        }
                        
                           $dept = str_replace(' ', '_', $dept);
                           $count=$input['count']+1;
                          $col_head='comment_'.$dept;
                          $col_head_file='comment_file_'.$dept;
                          
                          
                    $target_dir = "../../upload/deviation/";
                    
                    
                    $id = $_GET["id"] ;
                    $dep = $col_head_file;
                
                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }
                          
                $sql=" UPDATE deviation SET 
                status='dept_final', $dept='dept_final', 
                $col_head = '".$input['Commentsss']."',
                count='$count', $col_head_file='$file_name'
                WHERE id='" . $_GET["id"] . "'";

                if($conn->query($sql)){
                                     
                    echo "{\"status\":\"success\"}";
                }else {
                    echo "{\"status\":\"failed\"}";
                }
                            
    }
    
    
    
                    else if($_GET["type"] == "saveDevaitonRa"){
                                 
                        $input = $_POST;
                                 
                                 
                        if($_GET["deptName"]=='Microbiology'){
                            $dept='microbiology';
                        }else if($_GET["deptName"]=='Store'){
                            $dept='warehouse';
                        }else if($_GET["deptName"]=='Marketing'){
                            $dept='bd';
                        } else if($_GET["deptName"]=='Regulatory'){
                            $dept='ra';
                        }else{
                            $dept=$_GET["deptName"];
                        }
                        
                        $dept = str_replace(' ', '_', $dept);
                        $count=$input['count']+1;

                    $target_dir = "../../upload/deviation/";
                    $id = $_GET["id"] ;

                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }

                $sql=" UPDATE deviation SET status='Ra_final', 
                doc_req = '".$input['doc_req']."',
                Comments_reg_req = '".$input['Comments_reg_req']."',
                change_customer_req = '".$input['change_customer_req']."',
                change_annual = '".$input['change_annual']."',
                Change_status = '".$input['Change_status']."',
                Change_re = '".$input['Change_re']."',
                Comments_ra = '".$input['Comments_ra']."',
                count='$count', RA_file='$file_name'
                WHERE id='" . $_GET["id"] . "'";

                if($conn->query($sql)){
                                     
                    echo "{\"status\":\"success\"}";
                }else {
                    echo "{\"status\":\"failed\"}";
                }
                            
    }
           else if($_GET["type"] == "saveDevaitonQA"){
                                 
                        $input = $_POST;
                                 
                                 
                        if($_GET["deptName"]=='Microbiology'){
                            $dept='microbiology';
                        }else if($_GET["deptName"]=='Store'){
                            $dept='warehouse';
                        }else if($_GET["deptName"]=='Marketing'){
                            $dept='bd';
                        } else if($_GET["deptName"]=='Regulatory'){
                            $dept='ra';
                        }else{
                            $dept=$_GET["deptName"];
                        }
                        
                        $dept = str_replace(' ', '_', $dept);
                        $count=$input['count']+1;

                    $target_dir ="../../upload/deviation/";
                    $id = $_GET["id"] ;

                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }
              
                    $intt = 'NO';
                    $na = 'NO';
                    $ca = 'NO';
                    $minor = 'NO'; 
                    $major = 'NO';
                    $critical = 'NO';
      
                    
            if($input["intt"] == 'true'){  $intt = 'YES';    } ;
            if($input["na"] == 'true'){  $na = 'YES';    } ;
            if($input["ca"] == 'true'){  $ca = 'YES';    } ;
            if($input["minor"] == 'true'){  $minor = 'YES';    } ;
            if($input["major"] == 'true'){  $major = 'YES';    } ;
            if($input["critical"] == 'true'){  $critical = 'YES';    } ;


                $sql=" UPDATE deviation SET status='QA_final', 
                actionplanQA=  '".json_encode($input['actionplanQA'])."',
                comment_customer= '".$input['comment_customer']."', 
                customer_aprvl= '".$input['customer_aprvl']."', 
                intt= '$intt', 
                na= '$na', 
                ca= '$ca', 
                minor= '$minor', 
                major= '$major', 
                critical= '$critical', 
                decision1= '".$input['decision1']."', 
                decision_cc= '".$input['decision_cc']."', 
                reason1= '".$input['reason1']."', 
                review_com= '".$input['review_com']."', 
                review_comment= '".$input['review_comment']."', 
                risk_management= '".$input['risk_management']."' , final_file='$file_name'
                WHERE id='" . $_GET["id"] . "'";

                if($conn->query($sql)){
                                     
                    echo "{\"status\":\"success\"}";
                }else {
                    echo "{\"status\":\"failed\"}";
                }
                            
    }  
    
    
    //--------------------------------------------------------------------------------------------------------------//
 else if($_GET["type"] == "saveDevaitonDeptQAReview"){
      $input    = $_POST;      
      $target_dir = "../../upload/deviation/";
                    $id = $_GET["id"] ;
                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }

            if($input["microbiology"] == 'true'){  $microbiology = 'qa';    } ;
            if($input["qa"] == 'true'){  $qa = 'qa';    } ;
            if($input["qc"] == 'true'){  $qc = 'qa';    } ;
            if($input["production"] == 'true'){  $production = 'qa';    } ;
            if($input["warehouse"] == 'true'){  $warehouse = 'qa';    } ;
            if($input["engineering"] == 'true'){  $engineering = 'qa';    } ;
            if($input["it"] == 'true'){  $it = 'qa';    } ;
            // if($input["sc"] == 'true'){  $sc = 'impact';    } ;
            if($input["hr"] == 'true'){  $hr = 'qa';    } ;
            if($input["ra"] == 'true'){  $ra = 'qa';    } ;
            if($input["packing"] == 'true'){  $packing = 'qa';    } ;
            if($input["bd"] == 'true'){  $bd = 'qa';    } ;
    
        
          $sql = "UPDATE deviation SET status='to_dept' ,
               rcomment = '".$input['rcomment']."',
               rea_qa = '".$input['rea_qa']."',
               isDropped ='".$input['isDropped']."',
               isReturned ='".$input['isReturned']."',
               isApproved ='".$input['isApproved']."',
               
            Quality_Control = '$qc', 
            ra = '$ra', 
            IT = '$it', 
            Production = '$production', 
            Human_Resource = '$hr',
            bd = '$bd', 
            Quality_Assurance = '$qa', 
            Engineering = '$engineering', 
            microbiology = '$microbiology', 
            packing = '$packing',  
            count = '0',  
            warehouse = '$warehouse',dept_count='".$input['total_count']."', deviationDecisionFile='$file_name'
            WHERE id='".$_GET["id"]."'";
            
            
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    //-----------------------------------------------------------------------------------------------------------------//
     else if($_GET["type"] == "saveDevaitonDeptQAHod"){
  
        $qa = 'done_impact';
        $qc = 'done_impact';
        $production = 'done_impact';
        $engineering = 'done_impact';
        $it = 'done_impact';
        $hr = 'done_impact';
        $microbiology = 'done_impact';
        // $sc = 'done';
        $ra = 'done_impact';
        $packing = 'done_impact';
        $bd = 'done_impact';
        $warehouse = 'done_impact';
        $dept_count = '0';

           if($input["microbiology"] == 'true'){  $microbiology = 'impact';    } ;
        if($input["qa"] == 'true'){  $qa = 'impact';    } ;
        if($input["qc"] == 'true'){  $qc = 'impact';    } ;
        if($input["production"] == 'true'){  $production = 'impact';    } ;
        if($input["warehouse"] == 'true'){  $warehouse = 'impact';    } ;
        if($input["engineering"] == 'true'){  $engineering = 'impact';    } ;
        if($input["it"] == 'true'){  $it = 'impact';    } ;
        // if($input["sc"] == 'true'){  $sc = 'impact';    } ;
        if($input["hr"] == 'true'){  $hr = 'impact';    } ;
        if($input["ra"] == 'true'){  $ra = 'impact';    } ;
        if($input["packing"] == 'true'){  $packing = 'impact';    } ;
        if($input["bd"] == 'true'){  $bd = 'impact';    } ;
    
    
        
        $sql = "UPDATE deviation SET status='impact' ,
               comments_qa_dept = '".$input['comments_qa_dept']."',
               additional_dept = '".$input['additional_dept']."',
               count = '0',
               actionplans = '".json_encode($input['actionplans'])."',
            
          Quality_Control = '$qc', 
            ra = '$ra', 
            IT = '$it', 
            Production = '$production', 
            Human_Resource = '$hr',
            bd = '$bd', 
            Quality_Assurance = '$qa', 
            Engineering = '$engineering', 
            microbiology = '$microbiology', 
            packing = '$packing', 
            warehouse = '$warehouse',dept_count='".$input['total_count']."'
            WHERE id='".$_GET["id"]."'";
            
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
//    -------------------------------------------------------------------------------------------------------------------------------------------------
  else if($_GET["type"] == "saveDevaitonDeptH"){
                    $input    = $_POST;      
                      $qa = 'NO';
                    $qc = 'NO';
                    $production = 'NO';
                    $engineering = 'NO';
                    $it = 'NO';
                    $hr = 'NO';
                    $microbiology = 'NO';
                    // $sc = 'NO';
                    $ra = 'NO';
                    $packing = 'NO';
                    $bd = 'NO';
                    $warehouse = 'NO';      
                    $target_dir = "../../upload/deviation/";
                    $col_head='comment_'.$dept;
                    $col_head_file='deviationHodFile';
                    $id = $_GET["id"] ;
                    $dep = $col_head_file;
                    $file_name = "";
                    if(isset($_FILES["jugad"]["name"])){
                        $target_file = $target_dir.$id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        $file_name = $id.$dep."comment".basename($_FILES["jugad"]["name"]);
                        move_uploaded_file($_FILES["jugad"]["tmp_name"], $target_file);
                    }
                  
                    $investigation_hod = str_replace(["'", '"'], '', $input['investigation_hod']);
                    $comments = str_replace(["'", '"'], '', $input['comments']);
                    $Corrective_p = str_replace(["'", '"'], '', $input['Corrective_p']);
                    $preventive_action_plan_details = str_replace(["'", '"'], '', $input['preventive_action_plan_details']);
                    $preventive_action_plan = str_replace(["'", '"'], '', $input['preventive_action_plan']);
                    $other_action_plan = str_replace(["'", '"'], '', $input['other_action_plan']);
                    $other_action_plan_details = str_replace(["'", '"'], '', $input['other_action_plan_details']);
                    $corrective_action_plan = str_replace(["'", '"'], '', $input['corrective_action_plan']);
                    
                    
                    if($input["microbiology"] == 'true'){  $microbiology = 'Pending';    } ;
                    if($input["qa"] == 'true'){  $qa = 'Pending';    } ;
                    if($input["qc"] == 'true'){  $qc = 'Pending';    } ;
                    if($input["production"] == 'true'){  $production = 'Pending';    } ;
                    if($input["warehouse"] == 'true'){  $warehouse = 'Pending';    } ;
                    if($input["engineering"] == 'true'){  $engineering = 'Pending';    } ;
                    if($input["it"] == 'true'){  $it = 'Pending';    } ;
                    if($input["hr"] == 'true'){  $hr = 'Pending';    } ;
                    if($input["ra"] == 'true'){  $ra = 'Pending';    } ;
                    if($input["packing"] == 'true'){  $packing = 'Pending';    } ;
                    if($input["bd"] == 'true'){  $bd = 'Pending';    } ;
                    
                        $sql = "UPDATE deviation SET status='approve',
                        comments= '$comments',
                        document_hod= '".$input['document_hod']."',
                        investigation_hod= '$cleaned_input',
                        corrective_action_plan= '".$input['corrective_action_plan']."',
                        Corrective_p= '$Corrective_p',
                        preventive_action_plan= '$preventive_action_plan',
                        preventive_action_plan_details= '$preventive_action_plan_details',
                        other_action_plan= '$other_action_plan',
                        other_action_plan_details= '$other_action_plan_details',
                        
                        Quality_Control = '$qc', 
                        ra = '$ra', 
                        IT = '$it', 
                        Production = '$production', 
                        Human_Resource = '$hr',
                        bd = '$bd', 
                        Quality_Assurance = '$qa', 
                        Engineering = '$engineering', 
                        microbiology = '$microbiology', 
                        packing = '$packing', 
                        warehouse = '$warehouse',dept_count='".$input['total_count']."', $col_head_file='$file_name'
                        WHERE id='".$_GET["id"]."'";
                        
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    // --------------------------------------------------------------------------------------------------------------------------------------------------------
  
    else if($_GET["type"] == "getProducts")
    {
          $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
            $sql = "SELECT * ,a.product_code as a_product_code FROM product a LEFT JOIN ratemrp b ON a.manufactured_for = b.client_code
                WHERE a.manufactured_under != 'Own' and a.product_code NOT IN (SELECT product_code FROM ratemrp) and a.manufactured_for='".$_GET["client_code"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // $output1 = array();
                    // $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC LIMIT 1";
                    // $result1 = $conn->query($sql1);
                    // if ($result1->num_rows > 0) {
                    //     while ($row1 = $result1->fetch_assoc()) {
                    //         $row["mrp"] = $row1["mrp"];
                    //         $row["rate"] = $row1["rate"];
                    //     }
                    // }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    
    if($_GET["type"] == "saveDeviations"){
        $sql = "SELECT IFNULL(MAX(i_no), 0) as  i_no FROM deviation";
        $i_no = 0;
        $invoice_no = "";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
                break;
            }
        }
        $i_no++;
        $num = strlen($i_no);
        if($num == '1'){
            $dev_no = 'DEV-00'.$i_no;
        }else if($num == '2'){
            $dev_no = 'DEV-0'.$i_no;
        }else{
            $dev_no = 'DEV-'.$i_no;
        }
        $mfg_date = date('Y-m-d', strtotime($input['mfg_date']));
        $expiry_date = date('Y-m-d', strtotime($input['exp_date']));

        $product = Array();
        if ($input["affecting_product"] == "yes") {
            $product['product_code'] = $input["product_code"];
            $product['batch_no'] = $input["batch_no"];
            $product['mfg_date'] = $input["mfg_date"];
            $product['exp_date'] = $input["exp_date"];
        }

        $equipment = Array();
        if ($input["affecting_equipment"] == "yes") {
            $equipment['equipment_name'] = $input["equipment_name"];
        }

        $sql = "INSERT INTO deviation (dev_no,i_no,deviation_for,observed_in,description,immediate_action,impact_assessment,impact_other,materials,products,equipments,departments,entry_by,entry_date,mfg_date) VALUES ('$dev_no','$i_no','".$input["deviation_for"]."','".json_encode($input["observed_in"])."','".$input["description"]."', '".$input["immediate_action"]."', '".$input["impact_assessment"]."','".$input["impact_other"]."','".json_encode($input["materials"])."','".json_encode($input["products"])."','".json_encode($input["equipments"])."','".json_encode($input["departments"])."','".$_GET["emp_id"]."','$entry_date','".$input["mfg_date"]."' )";
       //echo $sql;
        if ($conn->query($sql)) {
            $data = $input["departmentlist"];
            for ($i = 0; $i <  ($data); $i++) {
                $sql = "INSERT INTO deviation_comments (dev_no,departments,status) VALUES ('$dev_no','".$data[$i]."','pending')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }} 
    else if($_GET["type"] == "DeviationList"){
        $output = Array();
        if(isset($_GET['dep'])){
            if(isset($_GET['fromdate'])){
                $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
            }else{
                $sql = "SELECT * FROM deviation WHERE status = '".$_GET['status']."' ORDER by id DESC";   
            }
        }else{
            $sql = "SELECT * FROM deviation WHERE status = '".$_GET['status']."' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["material_type"] == "material") {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row["material_type"] = $row2["material_type"];
        		            $row["material_name"] = $row2["material_name"];
        		        }
        		    }
                }
    		    
    		    $sql2 = "SELECT * FROM z_forms WHERE id='".$row["format_no"]."'";
    		    $result2 = $conn->query($sql2);
    		    if ($result2->num_rows > 0) {
    		        while ($row2 = $result2->fetch_assoc()) {
    		            $row["form_name"] = $row2["form_name"];
    		        }
    		    }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "DeviationListUR"){
        $output = Array();
        $sql = "SELECT * FROM deviation ORDER by id DESC"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $commentslist = [];
    	        $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row['dev_no']."' AND department='".$_GET['department']."'";
            	$result1 = $conn->query($sql1);
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    $commentslist[] = $row1;
                    }
                    $row['commentslist'] = $commentslist;
                    $output[] = $row;
            	}
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "approveQAhead"){
        $sql = "UPDATE deviation SET status='approve',approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE dev_no='".$input["devno"]."'";
        if($conn->query($sql) === TRUE){
            $sql1 = "UPDATE deviation_comments SET comment= '".$input['comment']."',entry_date='".$entry_date."' WHERE department = '".$input['dep']."' AND dev_no = '".$input['devno']."'";
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if($_GET['type'] == "rejectQAhead"){
        $sql = "UPDATE deviation SET status='reject', reject_reason='".$input['reason']."' WHERE dev_no='".$input["devno"]."'";
        if($conn->query($sql) === TRUE){
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if($_GET["type"] == "updateDeviation"){
        $sql = "UPDATE deviation SET status='pending', entry_by='".$_GET["emp_id"]."', entry_date='".$entry_date."' WHERE dev_no='".$input["devno"]."'";
        if($conn->query($sql) === TRUE){
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if($_GET['type'] == "approvedDeviations"){
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status = 'approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["material_type"] == "material") {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row["material_type"] = $row2["material_type"];
        		            $row["material_name"] = $row2["material_name"];
        		        }
        		    }
                }
    		    
    		    $sql2 = "SELECT * FROM z_forms WHERE id='".$row["format_no"]."'";
    		    $result2 = $conn->query($sql2);
    		    if ($result2->num_rows > 0) {
    		        while ($row2 = $result2->fetch_assoc()) {
    		            $row["form_name"] = $row2["form_name"];
    		        }
    		    }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getDeviation") {
        $output = Array();
        if(isset($_GET['fromdate']) && $_GET['category'] != ''){
            $sql = "SELECT * FROM deviation WHERE category = '".$_GET['category']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate']) && $_GET['category'] == ''){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;

                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getDeviationDept") {
        $output = Array();
        if(isset($_GET['fromdate']) && $_GET['category'] != ''){
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND category = '".$_GET['category']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate']) && $_GET['category'] == ''){
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        } else{
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getPendingDeviations") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='pending' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);
                $row["departments"] = json_decode($row["departments"]);
                $row["observed_in"] = json_decode($row["observed_in"]);
                // $output1 = Array();
                // $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $output1[] = $row1;
                //     }
                // }
                // $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    
    else if ($_GET["type"] == "getDeviationsLog1") {
	    $output = array();
	    $sql = "SELECT * FROM deviation ";
	    
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	              $row["actionplans"] = json_decode($row["actionplans"]);
	                $row["actionplanQA"] = json_decode($row["saveDevaitonQA"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output); 
	} 
//---------------------------------------------------------------------------------------------------------------------------//
	 else if ($_GET["type"] == "getDeviationAprvl_deparetment") {
       
        $output = Array();
        $dept=$_GET["deptName"];
          $dept = str_replace(' ', '_', $dept);
          
   // $sql = "SELECT * FROM `deviation`where dept_count=count AND plant_id = '".$_GET['plant_id']."'";
  
  
  
     $sql = "SELECT * FROM deviation  WHERE warehouse !='Pending' AND packing !='Pending' AND microbiology !='Pending' AND
     Quality_Assurance!='Pending' AND Quality_Control !='Pending' AND Production !='Pending' AND ra !='Pending'AND Engineering !='Pending'
    AND IT !='Pending' AND bd !='Pending'AND Human_Resource !='Pending' AND plant_id = '".$_GET['plant_id']."'  AND status = 'approve'" ;
     
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
	 else if ($_GET["type"] == "getDeviationDessionByRaisedDept") {
       
        $output = Array();
        $dept=$_GET["deptName"];
          $dept = str_replace(' ', '_', $dept);
          
   // $sql = "SELECT * FROM `deviation`where dept_count=count AND plant_id = '".$_GET['plant_id']."'";
  
  
  
     $sql = "SELECT * FROM deviation  WHERE warehouse  ='done_impact' AND packing ='done_impact' AND microbiology ='done_impact' AND
     Quality_Assurance ='done_impact' AND Quality_Control  ='done_impact' AND Production ='done_impact' AND ra ='done_impact'AND
     Engineering ='done_impact' AND departments = '".$_GET['deptName']."'
    AND IT ='done_impact' AND bd ='done_impact'AND Human_Resource ='done_impact' AND plant_id = '".$_GET['plant_id']."'  AND status = 'done_impact'" ;
     
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
  //--------------------------------------------------------------------------------------------------------------------------//

   else if ($_GET["type"] == "getDeviationImpact") {

  	        if($_GET["deptName"]=='Store'){
         $dept='warehouse';
             }else
             if($_GET["deptName"]=='Marketing'){
         $dept='bd';}else
           if($_GET["deptName"]=='Microbiology'){
         $dept='microbiology';
             }else
             if($_GET["deptName"]=='Regulatory'){
         $dept='ra';
             }else{
         $dept=$_GET["deptName"];
             }
	    
	   // $dept=$_GET["deptName"];
	      $dept = str_replace(' ', '_', $dept);
	    
    $sql = "SELECT * FROM deviation  WHERE $dept = 'impact'  AND plant_id = '".$_GET['plant_id']."'  ";
  
  
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
            $data[] = $row; // Store the fetched row into the data array
             
        }
    }
    echo json_encode($data);
}
 //--------------------------------------------------------------------------------------------------------------------------//

else if ($_GET["type"] == "getDeviationImpactQA") {
    
     $sql = "SELECT * FROM deviation  WHERE status = 'DecisionDone' AND plant_id = '".$_GET['plant_id']."' ";
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
            $data[] = $row; // Store the fetched row into the data array
        }
    }
    echo json_encode($data);
    
}
//---------------------------------------------------------------------------------------------------------------------------------//
else if ($_GET["type"] == "getDeviationRA_QA") {
     $sql = "SELECT * FROM deviation  WHERE status ='Ra_final' ";
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
            $data[] = $row; // Store the fetched row into the data array
        }
    }
    echo json_encode($data);
}
//=-------------------------------------------------------------------------------------------------------------------------------//
else if ($_GET["type"] == "getDeviationRa") {
     $sql = "SELECT * FROM deviation WHERE dept_count = count AND status='Ra_final' ";
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
            $data[] = $row; // Store the fetched row into the data array
        }
    }
    echo json_encode($data);
}
else if ($_GET["type"] == "getDeviationQa") {
     $sql = "SELECT * FROM deviation WHERE status='Ra_final' ";
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
            $data[] = $row; // Store the fetched row into the data array
        }
    }
    echo json_encode($data);
}
else if ($_GET["type"] == "getDeviationFinalComment") {
    if($_GET["deptName"]=='Store'){
         $dept='warehouse';
             }else
             if($_GET["deptName"]=='Marketing'){
         $dept='bd';
             }else
             if($_GET["deptName"]=='Regulatory'){
         $dept='ra';
             }else
             if($_GET["deptName"]=='Human Resource'){
         $dept='Human_Resource';
             }else{
         $dept=$_GET["deptName"];
             }
	    
	   // $dept=$_GET["deptName"];
	      $dept = str_replace(' ', '_', $dept);
	    
    $sql = "SELECT * FROM deviation  WHERE $dept = 'qa'";
    // $sql = "SELECT * FROM deviation  WHERE status='to_dept' AND $dept = 'qa'";
   
    
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $row["actionplans"] = json_decode($row["actionplans"]);
            $data[] = $row; // Store the fetched row into the data array
             
        }
    }
    echo json_encode($data);
}
//-------------------------------------------------------------------------------------------------------------------------//
	else if ($_GET["type"] == "getDeviationAprvl") {
	        if($_GET["deptName"]=='Store'){
         $dept='warehouse';
             }else
             if($_GET["deptName"]=='Marketing'){
         $dept='bd';
             }else
             if($_GET["deptName"]=='Regulatory'){
         $dept='ra';
             }else{
         $dept=$_GET["deptName"];
             }
	    
	   // $dept=$_GET["deptName"];
	      $dept = str_replace(' ', '_', $dept);
	    
    $sql = "SELECT * FROM deviation  WHERE status='approve' AND $dept = 'Pending'  AND plant_id = '".$_GET['plant_id']."' ";
   
    
    $result = $conn->query($sql);
    $data = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row; // Store the fetched row into the data array
        }
    }
    echo json_encode($data);
}
//----------------------------------------------------------------------------------------------------------------------------//

    else if($_GET["type"] == "getDeviationsLog") {
        $output = Array();
         $sql = "SELECT * FROM `deviation`  WHERE status='QA_final' ORDER BY `id` DESC";  //WHERE status='checked' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["products"] = json_decode($row["products"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["departments"] = json_decode($row["departments"]);
                $row["observed_in"] = json_decode($row["observed_in"]);
                $row["materials"] = json_decode($row["materials"]);
                $row["actionplans"] = json_decode($row["actionplans"]);
                $row["actionplanQA"] = json_decode($row["actionplanQA"]);
                // $output1 = Array();
                // $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $output1[] = $row1;
                //     }
                // }
                // $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    } 
      // --------------------------------------------------------------------------------------------------------------------//
      else if ($_GET["type"] == "get_return_deviationhod") {
        
         $dept=$_GET["deptName"];
    
	      $dept = str_replace(' ', '_', $dept);
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='DeptReturn'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
       }
       // --------------------------------------------------------------------------------------------------------------------//
      else if ($_GET["type"] == "get_return_deviation") {
        
         $dept=$_GET["deptName"];
    
	      $dept = str_replace(' ', '_', $dept);
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='return'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
       }
   
   //---------------------------------------------------------------------------------------------------------------------//
   else if ($_GET["type"] == "ReturnDevaiton") {
        
         $dept=$_GET["deptName"];
    
	      $dept = str_replace(' ', '_', $dept);
        $output = Array();
        $sql = "UPDATE deviation SET status='return' WHERE id='".$_GET["id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
       }
     // --------------------------------------------------------------------------------------------------------------------//
      else if ($_GET["type"] == "get_deviation_by_deparetment") {
        
         $dept=$_GET["deptName"];
    
	   //   $dept = str_replace(' ', '_', $dept);
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='pending' AND  departments = '$dept' AND extended_invest ='Yes'  ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
   }
   
   //---------------------------------------------------------------------------------------------------------------------//
   else if($_GET["type"] == "DeviationDec") {
       
        $sql = "UPDATE deviation SET reasonHod= '".$input['reasonHod']."', decHod= '".$input['decHod']."' ,status = 'DecisionDone' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "returnDeviation") {
    
      echo  $sql = "UPDATE deviation
            SET status='return',
            other_action_plan_details = '".$input['other_action_plan_details']."',
            other_action_plan = '".$input['other_action_plan']."',
            preventive_action_plan_details = '".$input['preventive_action_plan_details']."',
            preventive_action_plan = '".$input['preventive_action_plan']."',
            Corrective_p = '".$input['Corrective_p']."',
            corrective_action_plan = '".$input['corrective_action_plan']."',
            investigation_hod = '".$input['investigation_hod']."',
            comments = '".$input['comments']."',
            WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if($_GET["type"] == "checkDeviation") {
        $sql = "UPDATE deviation SET status='".$_GET["status"]."', check_remark='".$_GET["remark"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if($_GET["type"] == "getInprocessDeviations") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='inprocess' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "verifyDeviation") {
        $sql = "UPDATE deviation SET status='".$_GET["status"]."', verify_remark='".$_GET["remark"]."', verify_by='".$_GET["emp_id"]."', verify_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
   else if($_GET["type"] == "getPendingReview") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='pending' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["products"] = json_decode($row["products"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["materials"] = json_decode($row["materials"]);
                $row["departments"] = json_decode($row["departments"]);
                $row["observed_in"] = json_decode($row["observed_in"]);

                 $output1 = Array();
                 $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                     $output1[] = $row1;
                    }
                 }
                 $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "saveReview") {
        $sql = "UPDATE deviation_comments SET status='active', comment='".$_GET["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE dev_no='".$_GET["dev_no"]."' and id ='".$_GET["cmt_id"]."'";
      //  echo $sql;
        if ($conn->query($sql)) {
        //     $sql1 = "SELECT * FROM deviation_comments WHERE status='pending' AND dev_no='".$_GET["dev_no"]."'";
        //   // echo $sql1;
        //     $result1 = $conn->query($sql1);
        //     if ($result1->num_rows == 0) {
                $sql2 = "UPDATE deviation SET initiate_dept='Quality Assurance' WHERE deviation_no='".$_GET["dev_no"]."'";
                $conn->query($sql2);
        //       // echo $sql2;
        //     }
            echo "{\"status\": true}";
        } else {
            echo "{\"status\": false}";
        }
    } 
    // else if($_GET["type"] == "saveReview") {
    //     $sql = "UPDATE deviation_comments SET status='active', comment='".$_GET["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE dev_no='".$_GET["dev_no"]."'";
    //   //  echo $sql;
    //     if ($conn->query($sql)) {
    //         $sql1 = "SELECT * FROM deviation_comments WHERE status='pending' AND dev_no='".$_GET["dev_no"]."'";
    //       // echo $sql1;
    //         $result1 = $conn->query($sql1);
    //         if ($result1->num_rows == 0) {
    //             $sql2 = "UPDATE deviation SET status='checked' WHERE dev_no='".$_GET["dev_no"]."'";
    //             $conn->query($sql2);
    //           // echo $sql2;
    //         }
    //         echo "{\"status\": true}";
    //     } else {
    //         echo "{\"status\": false}";
    //     }
    // } 
    else if($_GET["type"] == "getCapaDeviations") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE capa='yes' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation WHERE capa='yes' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getCapaDeviationsDept") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND capa='yes' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND capa='yes' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getCheckedDeviations") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE status='checked' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation WHERE status='checked' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."' AND department !='Quality Assurance'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "saveQAApproval") {
        $capa_no = "";
        if ($input['capa'] == 'yes') {
            $id = 0;
            $sql = "SELECT IFNULL(MAX(id), 0) as id FROM capa";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $id = $row["id"];
                    break;
                }
            }
            $id++;
            $capa_no = "CAPA-".$id;

            $sql = "INSERT INTO capa (capa_no, department, required_to, category, form_no, entry_by, entry_date) VALUES ('$capa_no', '".$input["department"]."', '".$input["related_to"]."', 'Deviation', '".$input["dev_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
            $conn->query($sql);
        }
        
        $sql = "UPDATE deviation SET status='".$input["status"]."', capa='".$input["capa"]."', capa_no='$capa_no', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', approve_remark='".$input["comment"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\": true}";
        } else {
            echo "{\"status\": false}";
        }
    }
    else if($_GET['type'] == 'getcategorychart'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, category  FROM deviation GROUP BY category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["category"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getcategorychartDept'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, category  FROM deviation WHERE department = '".$_GET['department']."' GROUP BY category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["category"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getdepartmentchart'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, department  FROM deviation GROUP BY department";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["department"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
} else {
    echo "Invalid Token";
}

$conn->close();
?>