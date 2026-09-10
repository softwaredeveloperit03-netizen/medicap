<?php
    require '../../db.php';
    require '../../token.php';
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


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
} else if ($_GET["type"] == "getMitigationRisk") {
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
    error_reporting(0);
    
    $details = '[{"type": "assessment","status": "pending","name": "","description": "","score": "","justification": ""},{"type": "analysis","status": "pending","name": "","description": "","score": "","justification": ""},{"type": "evaluation","status": "pending","name": "","description": "","score": "","justification": ""} ]';
    $sql = "INSERT INTO rnd_risk_identification (department, section, identification, product_code, batch_no, lot_no, equipment_code, usages_for, risk_form, description, justification, qty_impact, impact_strength, human_impact, risk_strength, entry_by, entry_date, details) VALUES ('".$input["department"]."', '".$input["section"]."', '".$input["identification"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["lot_no"]."', '".$input["equipment_code"]."', '".$input["usages_for"]."', '".$input["risk_form"]."', '".$input["description"]."', '".$input["justification"]."', '".$input["qty_impact"]."', '".$input["impact_strength"]."', '".$input["human_impact"]."', '".$input["risk_strength"]."', '".$_GET["emp_id"]."', '$entry_date', '$details')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getIdentification") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingAssessment") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND assessment='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveAssessment") {
    $sql = "UPDATE rnd_risk_identification SET details='".file_get_contents('php://input')."', assessment='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingAnalysis") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND analysis='pending' AND assessment='done'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveAnalysis") {
    $sql = "UPDATE rnd_risk_identification SET details='".file_get_contents('php://input')."', analysis='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingEvaluation") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND evaluation='pending' AND analysis='done'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveEvaluation") {
    $sql = "UPDATE rnd_risk_identification SET details='".file_get_contents('php://input')."', evaluation='done' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingControl") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND control='pending' AND evaluation='done'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveControl") {
    $sql = "UPDATE rnd_risk_identification SET control='done', status='active' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getAssessmentLog") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getRiskLog") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingRiskReviews") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND control='done' AND review='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveRiskReview") {
    $sql = "UPDATE rnd_risk_identification SET review='inprocess', review_by='".$_GET["emp_id"]."', review_date='".$entry_date."', comment='".$input["comment"]."', capa='".$input["capa"]."', risk_control='".$input["risk_control"]."', impact_quality='".$input["impact_quality"]."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        $departments = $input["departments"];
        for ($i = 0; $i < count($departments); $i++) {
            $department = $departments[$i];
            $sql1 = "INSERT INTO rnd_risk_departments (risk_no, department) VALUES ('".$input["id"]."', '".$department["department_name"]."')";
            $conn->query($sql1);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingDeptRiskReviews") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND review='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            
            $sql1 = "SELECT * FROM rnd_risk_departments WHERE risk_no='".$row["id"]."' AND department='".$_GET["department"]."' AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDeptRiskReview") {
    $sql = "UPDATE rnd_risk_departments SET comment='".$input["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='active' WHERE risk_no='".$input["id"]."' AND department='".$_GET["department"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
        
        $sql = "SELECT * FROM rnd_risk_departments WHERE risk_no='".$input["id"]."' AND status ='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $sql = "UPDATE rnd_risk_identification SET review='checked' WHERE id='".$input["id"]."'";
            $conn->query($sql);
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDeptRiskLog") {
    $output = Array();
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM rnd_risk_departments WHERE risk_no='".$row["id"]."'";
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
    $sql = "SELECT * FROM rnd_risk_identification WHERE user_no='".$_GET["user_no"]."' AND review='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["details"] = json_decode($row["details"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM rnd_risk_departments WHERE risk_no='".$row["id"]."'";
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
    $sql = "UPDATE rnd_risk_identification SET review='".$input["status"]."', close_comment='".$input["comment"]."', close_by='".$_GET["emp_id"]."', close_date='$entry_date' WHERE id='".$input["id"]."'";
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