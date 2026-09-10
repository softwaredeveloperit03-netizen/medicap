<?php
require 'db.php';
require 'token.php';
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
    
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

 if ($_GET["type"]=="saveInspectionPlan") {
     
    $sql = "SELECT IFNULL(MAX(p_id1), 0) as  p_id1 FROM inspection_plan";
    $result = $conn->query($sql);
    $p_id1 = 0;
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $p_id1 = $row["p_id1"];
            break;
        }
    }
    
    $p_id1++;
    
    $num_length = strlen((string)$p_id1);
    
    if($num_length == 1) {
        $p_id = "PID00".$p_id1;
    } else if($num_length == 2) {
        $p_id = "PID0".$p_id1;
    } else {
       $p_id = "PID".$p_id1; 
    }
    
    if ($input['qc'] == 'true') {
        $input['qc'] = "yes";
    } else {
        $input['qc'] = "no";
    }
    
    if ($input['qa'] == 'true') {
        $input['qa'] = "yes";
    } else {
        $input['qa'] = "no";
    }
    
    if ($input['production'] == 'true') {
        $input['production'] = "yes";
    } else {
        $input['production'] = "no";
    }
    
    if ($input['store'] == 'true') {
        $input['store'] = "yes";
    } else {
        $input['store'] = "no";
    }
    
    if ($input['rnd'] == 'true') {
        $input['rnd'] = "yes";
    } else {
        $input['rnd'] = "no";
    }
    
    if ($input['ipqa'] == 'true') {
        $input['ipqa'] = "yes";
    } else {
        $input['ipqa'] = "no";
    }
    
    $sql = "INSERT INTO inspection_plan(p_id, from_date, to_date, inspection_requirement, qc, qa, production, store, rnd, ipqa, proposal_outcome, p_id1) VALUES
    ('$p_id', '".$input['from_date']."', '".$input['to_date']."', '".$input['inspection_requirement']."', '".$input['qc']."', '".$input['qa']."', '".$input['production']."', '".$input['store']."', '".$input['rnd']."', '".$input['ipqa']."', '".$input['proposal_outcome']."', $p_id1)";
    
    if ($conn->query($sql) === TRUE) {
       echo "{\"status\":\"success\"}";
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
    
} else if ($_GET["type"]=="saveInspectionTeam") {
    $sql = "SELECT IFNULL(MAX(t_id1), 0) as  t_id1 FROM inspection_team";
    $t_id1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $t_id1 = $row["t_id1"];
            break;
        }
    }
    
    $t_id1++;
    $num_length = strlen((string)$t_id1);
    
    if($num_length == 1) {
        $t_id = "TID00".$t_id1;
    } else if($num_length == 2) {
        $t_id = "TID0".$t_id1;
    } else {
       $t_id = "TID".$t_id1; 
    }
    
    $sql = "INSERT INTO inspection_team(t_id, p_id, from_date, to_date, department, auditors_name, audit_role, key_expert, assigned_department, t_id1,trainer) VALUES
    ('$t_id', '".$input['p_id']."', '".$input['from_date']."','".$input['to_date']."', '".$input['department_name']."', '".$input['auditors_name']."', '".$input['audit_role']."', '".$input['key_expert']."', '".$input['assigned_department']."', '$t_id1', '".$input['trainer']."')";
    if ($conn->query($sql) === TRUE) {
       echo "{\"status\":\"success\"}";
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"]=="saveInspectionAnnouncement") {
    
    $sql = "SELECT IFNULL(MAX(a_id1), 0) as  a_id1 FROM inspection_announcement";
    $a_id1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $a_id1 = $row["a_id1"];
            break;
        }
    }
    
    $a_id1++;
    $num_length = strlen((string)$a_id1);
    
    if($num_length == 1) {
        $a_id = "AID00".$a_id1;
    } else if($num_length == 2) {
        $a_id = "AID0".$a_id1;
    } else {
       $a_id = "AID".$a_id1; 
    }
    
    if ($input['qc'] == true) {
        $input['qc'] = "yes";
    } else {
        $input['qc'] = "no";
    }
    
    if ($input['qa'] == true) {
        $input['qa'] = "yes";
    } else {
        $input['qa'] = "no";
    }
    
    if ($input['production'] == true) {
        $input['production'] = "yes";
    } else {
        $input['production'] = "no";
    }
    
    if ($input['store'] == true) {
        $input['store'] = "yes";
    } else {
        $input['store'] = "no";
    }
    
    if ($input['rnd'] == true) {
        $input['rnd'] = "yes";
    } else {
        $input['rnd'] = "no";
    }
    
    if ($input['ipqa'] == true) {
        $input['ipqa'] = "yes";
    } else {
        $input['ipqa'] = "no";
    }
    
    $sql = "INSERT INTO inspection_announcement(a_id, p_id, auditors_name, planned_date, qc, qa, production, store, rnd, ipqa, a_id1) VALUES
    ('$a_id', '".$input['p_id']."', '".$input['auditors_name']."','".$input['planned_date']."', '".$input['qc']."', '".$input['qa']."', '".$input['production']."', '".$input['store']."', '".$input['rnd']."', '".$input['ipqa']."',  $a_id1)";
    
    if ($conn->query($sql) === TRUE) {
       echo "{\"status\":\"success\"}";
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getEmployee") {
    
    $sql = "SELECT * FROM employee";
    $output = array();
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getInspectionTeam") {
    
    $sql = "SELECT i.*,e.firstname,e.middlename,e.lastname  FROM inspection_team i left join employee e ON e.emp_id = i.auditors_name where p_id = '".$_GET["p_id"]."' ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getInspectionPlan") {
    
    $sql = "SELECT * FROM inspection_plan ORDER BY id DESC";
    $output = array();
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getInspectionLog") {
    
    $sql = "SELECT i.*,e.firstname,e.middlename,e.lastname  FROM inspection_team i left join employee e ON e.emp_id = i.auditors_name ORDER BY id DESC";
    $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getInspectionLogByDept") {
    
    $sql = "SELECT * FROM inspection_team WHERE department='".$_GET["department"]."' ORDER BY id DESC";
    $result = $conn->query($sql);
    $output = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getInspectionLogByDept1") {
    
    $sql = "SELECT COUNT(p_id) as team_size, p_id, from_date, to_date, department FROM inspection_team WHERE department='".$_GET["department"]."' GROUP BY p_id";
   
    $result = $conn->query($sql);
    $output = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getDepartmentEmp") {
	$sql = "SELECT * FROM employee WHERE emp_name='".$_GET["emp_name"]."' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
}  else if ($_GET["type"] == "getInspectionTeamByid") {
	$sql = "SELECT i.*,e.firstname,e.middlename,e.lastname  FROM inspection_team i left join employee e ON e.emp_id = i.auditors_name WHERE p_id='".$_GET["p_id"]."' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getInspectionAnnouncement") {
    $sql = "SELECT i.*,e.firstname,e.middlename,e.lastname FROM inspection_announcement i left join employee e ON e.emp_id = i.auditors_name
    WHERE p_id='".$_GET["p_id"]."' ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}   else if ($_GET["type"] == "getAuditors") {
    $sql = "SELECT i.*,e.firstname,e.middlename,e.lastname FROM inspection_team i left join employee e ON e.emp_id = i.auditors_name where p_id = '".$_GET["p_id"]."' ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInspecAnnByDept") {
    
    $sql = "SELECT * FROM inspection_team WHERE assigned_department = '".$_GET["dept"]."'";
    $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "getIPQAEmployee") {
    $sql = "SELECT emp_name FROM employee where department='IPQA'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="updateStatusCheck") {
     $sql = "UPDATE inspection_announcement SET ischecker='true' WHERE id=".$_GET["id"];
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="updateStatus") {
    $sql = "UPDATE inspection_announcement SET isapprover='true' WHERE id=".$_GET["id"];
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="updateInspectionPlan") {
    $sql = "UPDATE inspection_plan SET status='".$_GET["action"]."', accept_remark='".$_GET["remark"]."' WHERE id=".$_GET["id"];
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingPlan") {
    $sql = "SELECT * FROM inspection_plan WHERE status='pending'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInspectionReport") {
   $sql = "SELECT c.name, c.mobile, s.address, c.flat, s.name as s_name FROM customers c 
	JOIN society s ON c.s_id = s.s_id WHERE c.cust_id = '".$_POST["cust_id"]."' LIMIT 1";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}  else if ($_GET["type"] == "saveDistruction") {
    
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM stereo_distruction";
    $i_no1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"];
            break;
        }
    }
    
    $i_no1++;
    $num_length = strlen((string)$i_no1);
    
    if($num_length == 1) {
        $distruction_id = "DID00".$i_no1;
    } else if($num_length == 2) {
        $distruction_id = "DID0".$i_no1;
    } else {
       $distruction_id = "DID".$i_no1; 
    }
    
    $sql = "INSERT INTO stereo_distruction(distruction_id, entry_date, dosage_form, product_name, batch_no, department_name, request_by_employee, reason_for_request, equipment, distruction_loc, i_no1) VALUES ('$distruction_id', '$entry_date', '".$_POST['dosage_form']."', '".$_POST['product_name']."', '".$_POST['batch_no']."',  '".$_POST['department_name']."', '".$_POST['request_by_employee']."', '".$_POST['reason_for_request']."', '".$_POST["equipment"]."', '".$_POST["distruction_loc"]."', $i_no1)";
    if ($conn->query($sql) === TRUE) {
        
        $flag = 0;
        $steps = explode(",", $_POST["steps"]);
    	for($i = 0; $i < count($steps); $i++) {
    	    $sql = "INSERT INTO stereo_dist(distruction_id, step_name) VALUES ('$distruction_id', '".$steps[$i]."')";
    	    if ($conn->query($sql) === TRUE) {
    	        $flag = 0;
    	    } else {
    	        $flag = 1;
    	    }
    	}
    	if ($flag == 0) {
            echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
    
} else if ($_GET["type"]=="updateDistruction") {
    $sql = "UPDATE stereo_distruction SET action='".$_GET["action"]."', remark='".$_GET["remark"]."' WHERE id=".$_GET["id"];
    if ($qa->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="destroyEntry") {
    $sql = "UPDATE stereo_distruction SET status='destroy' WHERE id=".$_POST["id"];
    if ($qa->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}else if($_GET["type"]=="getDistructionDetails") {
    
        $sql = "SELECT * from stereo_distruction where status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select step_name, distruction_id from stereo_dist where distruction_id = '".$row['distruction_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["steps"] = $data;
                $output[] = $row;
            }
        }
    
        echo json_encode($output);
}  else if($_GET["type"]=="getDosagebyId") {
    $sql = "SELECT DISTINCT product_name FROM stereo_order WHERE dosage_form='".$_GET["selecteddosage_form"]."' ";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else {
        echo "[]";
    }
} else if($_GET["type"]=="getDistructionLog") {
    
        $sql = "SELECT * from stereo_distruction ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select step_name, distruction_id from stereo_dist where distruction_id = '".$row['distruction_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["steps"] = $data;
                $output[] = $row;
            }
        }
    
        echo json_encode($output);
} else if($_GET["type"]=="getBatchbyId") {
    $sql = "SELECT DISTINCT batch_no FROM stereo_order WHERE product_name='".$_GET["selectedproduct_name"]."' ";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else {
        echo "[]";
    }
} else if ($_GET["type"] == "getDosage") {
    $output = Array();
    $sql = "SELECT DISTINCT dosage_form FROM stereo_order";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getStabilitie") {
    
    $output = Array();
    $sql = "SELECT * FROM stability_study WHERE status='pending'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no=".$row["id"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["product_name"] = $row["product_name"];
                    $row1["batch_total"] = $row["batch_total"];
                    $output[] = $row1;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "inspectionDG") {
    $sql = "SELECT * FROM ";
}

} else {
    echo "[]";
}
?>