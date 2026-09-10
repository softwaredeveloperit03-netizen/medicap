<?php


ini_set('display_errors', 1);
 error_reporting(E_ALL);


    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveIncident") {
        
        $target_dir = "../../../upload/incident/";

        $id = date("YmdHis", $timestamp);
    
        $file_name = "NA";
        $file_name1 = "NA";
        $file_name2 = "NA";
     

        $Date_Of_INR = isset($input["Date_Of_INR"]) ? $input["Date_Of_INR"] : '';
        $Name_of_Department = isset($input["Name_of_Department"]) ? $input["Name_of_Department"] : '';
        $INR_No = isset($input["INR_No"]) ? $input["INR_No"] : '';
        $CAPA_Ref_No = isset($input["CAPA_Ref_No"]) ? $input["CAPA_Ref_No"] : '';
        $Ref_QMS_Document_No = isset($input["Ref_QMS_Document_No"]) ? $input["Ref_QMS_Document_No"] : '';
        $Target_Date = isset($input["Target_Date"]) ? $input["Target_Date"] : '';
        $INR_Details = isset($input["INR_Details"]) ? $input["INR_Details"] : '';
        $Probable_Cause_Root_Cause_for_Incidence = isset($input["Probable_Cause_Root_Cause_for_Incidence"]) ? $input["Probable_Cause_Root_Cause_for_Incidence"] : '';
        $Corrective_Action = isset($input["Corrective_Action"]) ? $input["Corrective_Action"] : '';
        $Preventive_Action = isset($input["Preventive_Action"]) ? $input["Preventive_Action"] : '';
        $Root_Cause_Identified = isset($input["Root_Cause_Identified"]) ? $input["Root_Cause_Identified"] : '';
        $incident_relateds = isset($input["incident_relateds"]) ? $input["incident_relateds"] : '';
        $classification_inr = isset($input["classification_inr"]) ? $input["classification_inr"] : '';
        $potential_impact = isset($input["potential_impact"]) ? $input["potential_impact"] : '';
    	
                   $sql = "INSERT INTO new_incident (
                            Date_Of_INR,
                            Name_of_Department,
                            INR_No,
                            CAPA_Ref_No,
                            Ref_QMS_Document_No,
                            Target_Date,
                            INR_Details,
                            incident_supportive_document,
                            Probable_Cause_Root_Cause_for_Incidence,
                            Corrective_Action,
                            Corrective_Reference_Document,
                            Preventive_Action,
                            Preventive_Reference_Document,
                            Root_Cause_Identified,
                            incident_relateds,
                            classification_inr,
                            potential_impact,
                            entry_by,
                            entry_date
                        ) VALUES (
                            '".$Date_Of_INR."',
                            '".$Name_of_Department."',
                            '".$INR_No."',
                            '".$CAPA_Ref_No."',
                            '".$Ref_QMS_Document_No."',
                            '".$Target_Date."',
                            '".$INR_Details."',
                            '$file_name',
                            '".$Probable_Cause_Root_Cause_for_Incidence."',
                            '".$Corrective_Action."',
                            '$file_name1',
                            '".$Preventive_Action."',
                            '$file_name2',
                            '".$Root_Cause_Identified."',
                            '".$incident_relateds."',
                            '".$classification_inr."',
                            '".$potential_impact."',
                            '".$_GET["emp_id"]."',
                            '$entry_date'
                        )";
		
		if ($conn->query($sql)) {
 
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	} 
 	else if ($_GET["type"] == "getPendingIncidents") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='send for review' and Name_of_Department='".$_GET["dep_name"]."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	          
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
		else if ($_GET["type"] == "getIncidentsLog") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["potential_impact"] = json_decode($row["potential_impact"]);
	            $row["classification_inr"] = json_decode($row["classification_inr"]);
	            $row["incident_relateds"] = json_decode($row["incident_relateds"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
		else if ($_GET["type"] == "getQAIncidents") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='Send to QA Officer' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["potential_impact"] = json_decode($row["potential_impact"]);
	            $row["classification_inr"] = json_decode($row["classification_inr"]);
	            $row["incident_relateds"] = json_decode($row["incident_relateds"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
		else if ($_GET["type"] == "getPendingIncidentsClosure") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='To_Closure' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["potential_impact"] = json_decode($row["potential_impact"]);
	            $row["classification_inr"] = json_decode($row["classification_inr"]);
	            $row["incident_relateds"] = json_decode($row["incident_relateds"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
		else if ($_GET["type"] == "getPendingIncidentsForDept") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='Approve' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["potential_impact"] = json_decode($row["potential_impact"]);
	            $row["classification_inr"] = json_decode($row["classification_inr"]);
	            $row["incident_relateds"] = json_decode($row["incident_relateds"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
	
	else if ($_GET["type"] == "getPendingIncidentsFor_dept_review") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='Send to The Department Head' and Name_of_Department='".$_GET["dep_name"]."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	   
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
	else if ($_GET["type"] == "getPendingIncidentsFor_qa_review") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='Send to QA Dept'  ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           
	            $row["needs_verify"] = json_decode($row["needs_verify"]);
	            $row["specify_details"] = json_decode($row["specify_details"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
	else if ($_GET["type"] == "getPendingIncidentsFor_qa_Head_review") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE status='Send to QA Head' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
 	            $row["needs_verify"] = json_decode($row["needs_verify"]);
	            $row["specify_details"] = json_decode($row["specify_details"]);
	            $row["evaluation"] = json_decode($row["evaluation"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
	else if ($_GET["type"] == "getIncidentsLog") {
	    $output = array();
	    $sql = "SELECT * FROM new_incident WHERE Name_of_Department = '".$_GET["dep_name"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
 	            $row["needs_verify"] = json_decode($row["needs_verify"]);
	            $row["specify_details"] = json_decode($row["specify_details"]);
	            $row["evaluation"] = json_decode($row["evaluation"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
 	else if ($_GET["type"] == "incidentChecking") {
  
	    $sql = "UPDATE new_incident SET status='".$_GET["status"]."',check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
	    
	} 
else if ($_GET["type"] == "incidentCAPA") {
	  
	    $sql = "UPDATE new_incident SET  status='To_Closure', qa_head_by='".$_GET["emp_id"]."', qa_head_on='$entry_date',
	    CAPAAssessment='".$input['CAPAAssessment']."',
        ClosureComments='".$input['ClosureComments']."'
	    WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	
	else if ($_GET["type"] == "incidentClosureQAHead") {
	  
	    $sql = "UPDATE new_incident SET  status='Log', Closure_by='".$_GET["emp_id"]."',Closure_on='$entry_date',
	    ClosureQAHead='".$input['ClosureQAHead']."'
	    WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	else if ($_GET["type"] == "incidentQAHEADREVIEW") {
	  
	    $sql = "UPDATE new_incident SET status='".$_GET["status"]."',qa_head_by='".$_GET["emp_id"]."', qa_head_on='$entry_date',
	    CAPA='".$input['CAPA']."',
        CAPA_Impliment='".$input['CAPA_Impliment']."',
        training='".$input['training']."',
        Closing_date='".$input['Closing_date']."',
        closing_justification='".$input['closing_justification']."',
        Closure_Comments='".$input['Closure_Comments']."'
	    WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	else if ($_GET["type"] == "incidentCheckingReviewDept") {
	    $sql = "UPDATE new_incident SET ImpactAssessment	='".$input['ImpactAssessment']."',ImpactAssessmentType='".$input['ImpactAssessmentType']."',
	    status='Send to QA Officer',dept_head_by='".$_GET["emp_id"]."', dept_head_on='$entry_date' WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	else if ($_GET["type"] == "incidentChecking_dept_review") {
	    $sql = "UPDATE new_incident SET Description_of_Immediate_Action	='".$input['Description_of_Immediate_Action']."',needs_verify='".json_encode($input['needs_verify'])."',Reason_Justification_of_First_Alternate_TCD='".$input['Reason_Justification_of_First_Alternate_TCD']."',
	    Reason_Justification_of_Second_Alternate_tcd='".$input['Reason_Justification_of_Second_Alternate_tcd']."',specify_details='".json_encode($input['specify_details'])."',
	    status='".$_GET["status"]."',dept_head_by='".$_GET["emp_id"]."', dept_head_on='$entry_date' WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	else if ($_GET["type"] == "incidentChecking_dept_HEAD_review") {
	    $sql = "UPDATE new_incident SET evaluation	='".json_encode($input['evaluation'])."',qa_review_by='".$_GET["emp_id"]."', qa_review_on='$entry_date',status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	 
      else if($_GET["type"] == "IncidentLogMehaPdf") {$_GET['filename'] = '';
        $_GET['pdftype'] = 'onlyheader';
        include("../pdfimp2.php");
        $sql = "SELECT * from new_incident";
         $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
            $html = "";
            $html .= '<h1 style="text-align: center;">Incident Report</h1><div></div>
            <table border="1" cellpadding="5" style="  width:100%; border-collapse: collapse; margin-bottom: 16px;">
            <tr>
            <td style="border: 1px solid black;padding: 8px;"><strong>Incident No.:</strong>
            </td>
             <td>' . $row['INR_No'] . '
            </td>
             <td style="border: 1px solid black;padding: 8px;"><strong>Department.:</strong>
            </td>
             <td>' . $row['Name_of_Department'] . '
            </td>
            </tr>
            <tr>
                <td  style="border: 1px solid black;padding: 8px;">
                    <p><strong>1. Incident Observed:</strong></p>
                    
                </td>
                  <td colspan="3">' . $row['Observed'] . '
            </td>
            </tr>
            <tr>
                <td  style="border: 1px solid black;padding: 8px;">
                    <p><strong>2. Probable cause of Incident/ Brief Investigation:</strong></p>
                    
                </td>
                  <td colspan="3">' . $row['Investigation'] . '
            </td>
            </tr>
            <tr>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Reported By</strong></p>
                </td> <td>' . $row['entry_by'] . '
            </td>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Sign / Date</strong></p>
                </td> <td>' . $row['entry_date'] . '
            </td>
            </tr>
            <div></div>
            <tr>
                <td  style="border: 1px solid black;padding: 8px;">
                    <p><strong>3. Immediate corrective action taken:</strong></p>
                    
                </td><td colspan="3">' . $row['corrective'] . '
            </td>
            </tr>
            <tr>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Reporting Dept. Head:</strong></p>
                </td> <td>' . $row['check_by'] . '
            </td>
                <td style="border: 1px solid black;padding: 8px;">
                    <p><strong>Sign / Date</strong></p>
                </td> <td>' . $row['check_date'] . '
            </td>
            </tr>  <div></div>
           
        </table>';
          $html .= '<table  border="1" cellpadding="2" style=" width:100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>4. Impact Assessment by QA::</strong></p>
                
            </td><td colspan="3">' . $row['ImpactAssessment'] . '
            </td>
        </tr>
        <tr>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong> Classification: </strong></p>

            </td><td colspan="3">' . $row['ImpactAssessmentType'] . '
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid black;padding: 8px;">
                <p><strong>QA Manager:</strong></p>
            </td>
<td>' . $row['dept_head_by'] . '</td>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>Sign / Date</strong></p>
            </td>
            <td>' . $row['dept_head_on'] . '</td>

        </tr>  <div></div>
        <tr>
            <td style="border: 1px solid black;padding: 8px;">
                <p><strong>5. CAPA Assigned to the Incident (refer investigation report if applicable):</strong></p>
                
            </td><td colspan="3">' . $row['CAPAAssessment'] . '
            </td>
        </tr>
      
        <tr>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>6. Closure Comments by QA Officer:</strong></p>
                
            </td><td colspan="3">' . $row['ClosureComments'] . '
            </td>

        </tr>
        <tr>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>QA Officer:</strong></p>
            </td><td>' . $row['qa_head_by'] . '</td>
   <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>Sign/ Date:</strong></p>
            </td>
            <td>' . $row['qa_head_on'] . '</td>
        </tr>
  <div></div>
        <tr>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>7. Closure by QA Head:</strong></p>
                
            </td>
        <td colspan="3">' . $row['ClosureQAHead'] . '
            </td>
        </tr>
        <tr>
            <td  style="border: 1px solid black;padding: 8px;">
                <p><strong>QA Head:</strong></p>
            </td><td>' . $row['Closure_by'] . '</td><td  style="border: 1px solid black;padding: 8px;">
                <p><strong>Sign/ Date:</strong></p>
            </td><td>' . $row['Closure_on'] . '</td>

        </tr>
    </table>';
            $html .= '<div></div>'; // Empty div for space if needed
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('', 'I');
}}
       

}

$conn->close();
?>