<?php
// ini_set('display_errors', 1);
//  error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';

    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    header('Content-Type: application/json');

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


if ($_GET["type"] == "getQuantitativeRisk") {
    $output = Array();
    $temp = Array();
    $temp["name"] = "Critical";
    $temp["description"] = "Very significant and having catastrophic impact on product";
    $temp["score"] = 1;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "High";
    $temp["description"] = "Significant losses and require timely addressable management intervention required";
    $temp["score"] = 2;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Moderate";
    $temp["description"] = "Loss of operating capability, long term of problem causes adverse effect";
    $temp["score"] = 3;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Minor";
    $temp["description"] = "Impact on operations and efficiency, but limited effect";
    $temp["score"] = 4;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Insignificant";
    $temp["description"] = "Very minor or no impact on operational efficiency";
    $temp["score"] = 5;
    $output[] = $temp;

    echo json_encode($output);
} else if ($_GET["type"] == "getLikelihoodRisk") {
    $output = Array();
    $temp = Array();
    $temp["name"] = "Almost certain";
    $temp["description"] = "Is expected to occur in most circumstances";
    $temp["score"] = 1;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Likely";
    $temp["description"] = "Loss of operating capability, long term of problem causes adverse effect";
    $temp["score"] = 2;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Possible";
    $temp["description"] = "Will probably occur at some time";
    $temp["score"] = 3;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Unlikely";
    $temp["description"] = "Could occur at some time";
    $temp["score"] = 4;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Rare";
    $temp["description"] = "May occur in exceptional circumstances";
    $temp["score"] = 5;
    $output[] = $temp;

    echo json_encode($output);
}


	  else if ($_GET["type"] == "downloadLog"){

	    $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
    $sql11 = "SELECT * FROM risk_identification"; 
           $result11 = $conn->query($sql11);
                     if($result11->num_rows > 0) {
                        while($row = $result11->fetch_assoc()) {
                $html.='
    <table border="1" cellpadding="2" cellspacing="0" style="border-collapse: collapse; width: 100%;">
        <tr>
            <td colspan="6" style="text-align:center; font-size:21px; font-weight:bold;background-color:#DDDAD9;" >Risk Management</td>
        </tr>
        <tr>
            <td> Department</td>
            <td>' . $row['department'] . '</td>
            <td>Section</td>
            <td></td>
            <td>Risk Identification For</td>
            <td></td>
        </tr>
        <tr>
            <td>Product Name</td>
            <td></td>
            <td>Batch No.</td>
            <td></td>
            <td>Lot No.</td>
            <td></td>
        </tr>
        <tr>
            <td>Equipment Code</td>
            <td></td>
            <td>Usages For Stage / Step</td>
            <td></td>
            <td>Risk Identified</td>
            <td></td>
        </tr>
        <tr>
            <td>Description of Risk</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td>Justification of Risk</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td>Is it going to impact on Product Quantity?</td>
            <td></td>
            <td>Is it going to impact on Product Quality?</td>
            <td></td>
            <td>Impact Strength</td>
            <td></td>
        </tr>
        <tr>
            <td>Is it going to make risk to Human Life?</td>
            <td></td>
            <td>Human Life Risk Strength</td>
            <td  colspan="3"></td>
        </tr>
    </table>
        <div></div>

        <table border="1" cellpadding="2" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <tr>
                <td colspan="6" style="text-align:left; font-size:15px; font-weight:bold;background-color:#DDDAD9;">Risk Assessment</td>
            </tr>
            <tr>
                <td colspan="6">Risk Identified as Critical will be Very significant and having catastrophic impact on product
                <br>
                Justification of Risk Analysis</td>
                </tr>
            <tr>
                <td colspan="6"> </td>
            </tr>
            <tr>
                <th colspan="6" style="text-align:left; font-size:15px; font-weight:bold;background-color:#DDDAD9;">Risk Analysis:</th>
            </tr>
            <tr>
                <td colspan="6">Risk Identified as Likely will be Loss of operating capability, long term of problem causes adverse effect
                <br>
                Justification of Risk Analysis</td>
                </tr>
            <tr>
                <td colspan="6"></td>
            </tr>
        </table>
        <table border="1" cellpadding="2" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <tr>
                <td colspan="6" style="text-align:left; font-size:15px; font-weight:bold;background-color:#DDDAD9;">Risk Evaluation:</td>
            </tr>
            <tr>
                <td colspan="6">Risk Identified as 0 will be 0
                <br>
                Justification of Risk evaluation:
                </td>
                </tr>
            <tr>
                <td colspan="6"> </td>
            </tr>
        </table>
        
        <h3>Risk Priority & Control:</h3>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr style="background-color:#DDDAD9;">
                    <th>Identification of Risk Involved</th>
                    <th>Analyzing the Risk</th>
                    <th>Evaluation of Risk</th>
                    <th>Risk Reduction</th>
                    <th>Quantitative Evaluation</th>
                    <th rowspan="2" style="vertical-align: middle;">Risk Identification No.</th>
                </tr>
                <tr style="background-color:#DDDAD9;">
                    <th>Description</th>
                    <th>Severity of Risk Impact</th>
                    <th>Likelihood Risk</th>
                    <th>Mitigation Plan (Control)</th>
                    <th>Total Score</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td rowspan="2" style="vertical-align: middle;"></td>
                    <td rowspan="2" style="vertical-align: middle;"></td>
                </tr>
                <tr>
                    <td>Priority No.:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>';
                        }
                     } 
		$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
        }
	   

else if ($_GET["type"] == "getMitigationRisk") {
    $output = Array();
    $temp = Array();
    $temp["name"] = "Not aware";
    $temp["description"] = "Insufficient information to adequately assess and rate the control";
    $temp["score"] = 1;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Not existent";
    $temp["description"] = "No mitigation plans in place";
    $temp["score"] = 2;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Not effective";
    $temp["description"] = "mitigation plans though in place do not ensure adequate control over risk occurrence/impact";
    $temp["score"] = 3;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Effective";
    $temp["description"] = "mitigation plans involve is effective against the identified risk of HVAC, although chances of occurrence minimize, but there is very less possibility of occurrence of identified risk";
    $temp["score"] = 4;
    $output[] = $temp;

    $temp = Array();
    $temp["name"] = "Very Effective";
    $temp["description"] = "mitigation plans involve high degree of control on the operational procedure and is effective against all identified risk";
    $temp["score"] = 5;
    $output[] = $temp;

    echo json_encode($output);
} else if ($_GET["type"] == "getDepartments") {
    $output = Array();
    $sql = "SELECT * FROM department";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM section WHERE department='".$row["department_name"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["sections"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output); 
} else if ($_GET["type"] == "saveIdentification") {

    // $details = '[{"type": "assessment","status": "pending","name": "","description": "","score": "","justification": ""},{"type": "analysis","status": "pending","name": "","description": "","score": "","justification": ""},{"type": "evaluation","status": "pending","name": "","description": "","score": "","justification": ""} ]';
  $assessment_details = '[{
    "type": "assessment",
    "status": "pending",
    "name": "0",
    "description": "0",
    "score": "0",
    "justification": "0"
  }]';
  $analysis_details = '[{
    "type": "analysis",
    "status": "pending",
    "name": "0",
    "description": "0",
    "score": "0",
    "justification": "0"
  }]';
  $evaluation_details = '[{
    "type": "evaluation",
    "status": "pending",
    "name": "0",
    "description": "0",
    "score": "0",
    "justification": "0"
}]';

    $sql = "INSERT INTO risk_identification (department, section, identification, product_code, batch_no, lot_no, equipment_code, usages_for, 
    risk_form, description, justification, qty_impact,qly_impact, impact_strength, human_impact, risk_strength, entry_by, entry_date, assessment_details,analysis_details,evaluation_details) 
    VALUES ('".$input["department"]."', '".$input["section"]."', '".$input["identification"]."', '".$input["product_code"]."', 
    '".$input["batch_no"]."', '".$input["lot_no"]."', '".$input["equipment_code"]."', '".$input["usages_for"]."', '".$input["risk_form"]."',
    '".$input["description"]."', '".$input["justification"]."', '".$input["qty_impact"]."', '".$input["qly_impact"]."','".$input["impact_strength"]."',
    '".$input["human_impact"]."', '".$input["risk_strength"]."', '".$_GET["emp_id"]."', '$entry_date', '$assessment_details','$analysis_details','$evaluation_details')";
    
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 
else if ($_GET["type"] == "getIdentification") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getIdentification_review") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' and identification_status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getPendingAssessment") {
    $output = Array();
    // $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND assessment='pending' and assessment_status='pending'";
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND identification_status='Approve' and assessment_status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["assessment_details"] = json_decode($row["assessment_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "saveidenreview") {
    $sql = "UPDATE risk_identification SET identification_status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "saveassreview") {
    $sql = "UPDATE risk_identification SET assessment_status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "saveanaysisreview") {
    $sql = "UPDATE risk_identification SET analysis_status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "saveEvaluationreview") {
    $sql = "UPDATE risk_identification SET evaluation_status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "saveAssessment") {
    $details=$input;
    $sql = "UPDATE risk_identification SET assessment_details='[".json_encode($details)."]', assessment='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getPendingAnalysis") {
    $output = Array();
    // $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND analysis='pending' AND assessment='done'";
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."'  AND assessment_status='Approve' and analysis_status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "saveAnalysis") {
     $details=$input;
     
     $sql = "UPDATE risk_identification SET analysis_details='[".json_encode($details)."]', analysis='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getPendingEvaluation") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND evaluation_status='pending' AND analysis_status='Approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
           $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
$output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveEvaluation") {
     $details=$input;
     
     $sql = "UPDATE risk_identification SET evaluation_details='[".json_encode($details)."]', evaluation='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 
else if ($_GET["type"] == "getPendingControl") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND control='pending' AND evaluation='done'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
           $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getPendingDepReview") {
    $output = Array();
    $sql = "SELECT a.*, b.id as risk_cmt_id,b.department as review_dep, b.status as reviewStatus FROM risk_identification a left join risk_comments b on a.id=b.risk_identification_id WHERE a.user_no='".$_GET["user_no"]."' AND (a.depReview='sent' or a.depReview='Approve') AND b.department='".$_GET["dep_name"]."' and b.status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
           $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getPendingDepHeadReview") {
    $output = Array();
    $sql = "SELECT a.*,b.comment, b.id as risk_cmt_id,b.department as review_dep, b.status as reviewStatus FROM risk_identification a left join risk_comments b on a.id=b.risk_identification_id WHERE a.user_no='".$_GET["user_no"]."' AND  b.department='".$_GET["dep_name"]."' and b.status='Sent to Department Head'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
           $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getPendingQAHEAD") {
    $output = Array();
    $sql = "SELECT a.*, 
                       (SELECT COUNT(b.department) 
                        FROM risk_comments b 
                        WHERE b.risk_identification_id = a.id) AS dep_count,
                       (SELECT COUNT(b.status) 
                        FROM risk_comments b 
                        WHERE b.risk_identification_id = a.id 
                          AND b.status = 'Approved By Department Head') AS status_count
                FROM risk_identification a
                WHERE a.user_no = '".$_GET["user_no"]."' 
                  AND a.depReview = 'Sent' and a.qaHead='pending';
                ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
                     $output1 = array();
                $sql1 = "select * from risk_comments where risk_identification_id='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       
                        $output1[] = $row1;
                    }
                }
                $row["risk_comments"] = $output1;
            
            
            
            
           $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getControlDone") {
    $output = Array();
     $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND control='done'  and depReview='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
           $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "saveDepReview") {
    $sql = "UPDATE risk_identification SET depReview='sent' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	        
	        $departments = $input["departments"];
	        for ($i = 0; $i < count($departments); $i++) {
	            $department = $departments[$i];
	            $sql = "INSERT INTO risk_comments (user_no, risk_identification_id, department,selectedDeptCount) VALUES ('".$_GET["user_no"]."', '".$_GET["id"]."', '".$department."','".$input["selectedDeptCount"]."')";
	            $conn->query($sql);
	        }
	    } else {
        echo "{\"status\":\"failed\"}";
    }
 }
else if ($_GET["type"] == "saveReviewFromDEp") {
 echo   $sql = "UPDATE risk_comments SET comment='".$input["remark"]."',status='".$input["status"]."' WHERE id='".$input["risk_cmt_id"]."'";
    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	        
	    } else {
        echo "{\"status\":\"failed\"}";
    }
 }
else if ($_GET["type"] == "saveReviewFromDEpHEAD") {
    $sql = "UPDATE risk_comments SET status='".$input["status"]."' WHERE id='".$input["risk_cmt_id"]."'";
    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	        
	       
    
	    } else {
        echo "{\"status\":\"failed\"}";
    }
 }
else if ($_GET["type"] == "saveReviewFromQAhead") {
    $sql = "UPDATE risk_identification SET qaHead='".$input["status"]."',capa='".$input["capa"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	        
	       
    
	    } else {
        echo "{\"status\":\"failed\"}";
    }
 }
else if ($_GET["type"] == "saveControl") {
    $sql = "UPDATE risk_identification SET control='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
 }
else if ($_GET["type"] == "getAssessmentLog") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE assessment='done' AND  user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getRiskLog") {
    $output = Array();
 //   $sql = "SELECT * FROM risk_identification"; 
 $sql ="SELECT a.*,b.comment FROM risk_identification a left join risk_comments b on a.id=b.risk_identification_id   ;";
    //WHERE user_no='".$_GET["user_no"]."' AND status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // $row["details"] = json_decode($row["details"]);
            $row["assessment_details"] = json_decode($row["assessment_details"]);
            $row["analysis_details"] = json_decode($row["analysis_details"]);
            $row["evaluation_details"] = json_decode($row["evaluation_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingRiskReviews") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND control='done' AND review='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "saveRiskReview") {
    $sql = "UPDATE risk_identification SET review='inprocess', review_by='".$_GET["emp_id"]."', review_date='".$entry_date."', comment='".$input["comment"]."', capa='".$input["capa"]."', risk_control='".$input["risk_control"]."', impact_quality='".$input["impact_quality"]."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        $departments = $input["departments"];
        for ($i = 0; $i < count($departments); $i++) {
            $department = $departments[$i];
            $sql1 = "INSERT INTO risk_departments (risk_no, department) VALUES ('".$input["id"]."', '".$department["department_name"]."')";
            $conn->query($sql1);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getPendingDeptRiskReviews") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND review='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            
            $sql1 = "SELECT * FROM risk_departments WHERE risk_no='".$row["id"]."' AND department='".$_GET["department"]."' AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDeptRiskReview") {
    $sql = "UPDATE risk_departments SET comment='".$input["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='active' WHERE risk_no='".$input["id"]."' AND department='".$_GET["department"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
        
        $sql = "SELECT * FROM risk_departments WHERE risk_no='".$input["id"]."' AND status ='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $sql = "UPDATE risk_identification SET review='checked' WHERE id='".$input["id"]."'";
            $conn->query($sql);
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDeptRiskLog") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM risk_departments WHERE risk_no='".$row["id"]."'";
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
} else if ($_GET["type"] == "getCheckedRisks") {
    $output = Array();
    $sql = "SELECT * FROM risk_identification WHERE review='checked'";
    //user_no='".$_GET["user_no"]."' AND review='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM risk_departments WHERE risk_no='".$row["id"]."'";
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
} else if ($_GET["type"] == "closeRisk") {
    $sql = "UPDATE risk_identification SET review='".$input["status"]."', close_comment='".$input["comment"]."', close_by='".$_GET["emp_id"]."', close_date='$entry_date' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>