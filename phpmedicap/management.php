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
while($row = $result->fetch_assoc()){
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

if($_GET["type"]=="getemployee"){
	$sql = "SELECT * FROM employee WHERE status='active'";
	$result = $hr->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if($_GET["type"]=="getEmployeeDetails") {
    
	$sql = "SELECT * FROM employee WHERE department='".$_POST["department"]."'";
	$result = $conn->query($sql);
	$output = array();
	
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    
		    $sql2 = "SELECT * FROM attendence WHERE emp_id='".$row["emp_id"]."' AND (intime BETWEEN '".$_POST["from_date"]."' AND '".$_POST["to_date"]."')";
		    
	        $result2 = $conn->query($sql2);
	        $working_hours = 0;
	  
	        if($result2-> num_rows > 0) {
        		while($row2 = $result2->fetch_assoc()){
        	
                    $date1 = strtotime($row2["intime"]);  
                    $date2 = strtotime($row2["outtime"]);
                    $diff = abs($date2 - $date1);
                    $years = floor($diff / (365*60*60*24));
                    $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                    $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)); 
                    $hours = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24) / (60*60));
                    $minutes = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);  
                    $seconds = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60));  
                    $working_hours = $working_hours + $hours;
        		}
        	}
        	$row["working_hours"] = $working_hours;
        	if ($working_hours > 0) {
        	    $output[] = $row;
        	}
		}
	}
	echo json_encode($output);
	
} else if($_GET["type"]=="getEmployeeData") {
    
    $date1=date_create($_POST["fromdate"]);
    $date2=date_create($_POST["todate"]);
    $diff=date_diff($date1,$date2);
    $alldays = $diff->format("%a");
    
	$sql = "SELECT department, count(emp_id) as emp_count FROM employee WHERE department='".$_POST["department"]."'";
	$result = $hr->query($sql);
	$data = array();
	$output = array();
	
	if($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$output["department"] = $row["department"];
			$output["emp_count"] = $row["emp_count"];
		}
	}
	
	$sql = "SELECT count(a.id) as days FROM attendence a JOIN employee e ON e.emp_id = a.emp_id WHERE e.department = '".$_POST["department"]."'";
    $result = $hr->query($sql);
    
    $days = 0;
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $percentage = 100 * $row["days"] / $alldays;
			$output["percentage"] = round($percentage);
        }
    }
    
    $data[] = $output;
	
	echo json_encode($data);
	
} else if ($_GET["type"] == "getEmployeeData1") {
    $output = array();
    $sql = "SELECT department, count(emp_id) as emp_count FROM employee WHERE department='".$_POST["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM attendence WHERE emp_id='".$row["emp_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["emp_name"] = $row1["emp_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDetailsEmployee") {
    $output = array();
    $sql = "SELECT * FROM employee" ;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row = array_map('utf8_encode', $row);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getIndividualAttendance") {
    
    $sql = "SELECT a.*, e.emp_name FROM attendence a
    JOIN employee e ON e.emp_id = a.emp_id
    WHERE a.emp_id='".$_POST["emp_id"]."'
    AND a.intime BETWEEN '".$_POST["fromdate"]."' AND '".$_POST["todate"]."' 
    AND a.outtime BETWEEN '".$_POST["fromdate"]."' AND '".$_POST["todate"]."'
    ";
    
	$result = $hr->query($sql);
	$output = array();
	
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $hourdiff = 0;
		    
		    if ($row["outtime"] != '' && $row["intime"] != '') {
		        $hourdiff = round((strtotime($row["outtime"]) - strtotime($row["intime"]))/3600, 1);
		    }
		    $row["work_hours"] = $hourdiff;
		    
			$output[] = $row;
		}
	}
	echo json_encode($output);
	
} else if ($_GET["type"] == "getAllAttendance") {
    
    $sql = "SELECT a.*, e.emp_name FROM attendence a JOIN employee e ON e.emp_id = a.emp_id";
    
	$result = $hr->query($sql);
	$output = array();
	
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $hourdiff = 0;
		    
		    if ($row["outtime"] != '' && $row["intime"] != '') {
		        $hourdiff = round((strtotime($row["outtime"]) - strtotime($row["intime"]))/3600, 1);
		    }
		    
		    $row["work_hours"] = $hourdiff;
		    
			$output[] = $row;
		}
	}
	echo json_encode($output);
	
} else if ($_GET["type"] == "getCompletedBatches") {
            $output = array();
            $data = json_decode(file_get_contents("bmr.json"), true);
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                if (($temp['status'] == 'completed' || $temp['current_stage'] == 'PACKING') && $temp["dosage_form"] == $_POST["dosage_form"]) {
                    if (($_POST["to_date"] >= $temp['complete_date']) && ($_POST["from_date"] <= $temp['complete_date'])){
                        $output[] = $temp;
                    } else {
                        echo "date not match";
                    }
                }
            }
            echo json_encode($output);
 } else if ($_GET["type"] == "getYeildStatement") {
            $output = array();
            $data = json_decode(file_get_contents("bmr.json"), true);
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                if (($temp['status'] == 'completed' || $temp['current_stage'] == 'PACKING') && $temp["product_name"] == $_POST["product_name"]) {
                        $output[] = $temp;
                }
            }
            echo json_encode($output);
            
 }  else if ($_GET["type"]=="saveAnnouncement") {
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM management_announcement";
    $i_no1 = 0;
    $i_no = "";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"];
            break;
        }
    }
    $i_no1++;
    $no = strlen($i_no1);
    if ($no == 1) {
        $i_no = "AID00".$i_no1;
    } else if ($no == 2) {
        $i_no = "AID0".$i_no1;
    } else if ($no >= 3) {
        $i_no = "AID".$i_no1;
    }
    $sql = "INSERT INTO management_announcement(announcement_id, entry_date, department_name, emp_name, subject, description, special_instruction, i_no1) VALUES ('$i_no', '$entry_date', '".$input["department_name"]."', '".$input["emp_name"]."', '".$input["subject"]."', '".$input["description"]."', '".$input["special_instruction"]."', '$i_no1' )";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\" ".$conn->error."\"}";
    }
}  else if ($_GET["type"] == "getAnnouncement") {
    $output = array();
    $sql = "SELECT * FROM management_announcement";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row; 
        }
    } 
    echo json_encode($output);
}  else if ($_GET["type"] == "getPurchaseOrderChartData") {
     
    $sql1 = "SELECT *  FROM po_material pom 
    JOIN purchaseorder po ON po.po_no = pom.po_no 
    WHERE po.status='approve'";
    $result = $purchase->query($sql1);
    $output = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            
            if ($row["isgeneral"] == "yes") {
                
                $mysql = "SELECT * FROM general_material WHERE material_no='".$row["material_code"]."'";
                $myresult = $conn->query($mysql);
                if ($myresult->num_rows > 0) {
                    while ($myrow = $myresult->fetch_assoc()) {
                        $row["material_name"] = $myrow["material_name"];
                        $row["material_type"] = $myrow["material_type"];
                    }
                }
            } else {
                $mysql = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $myresult = $conn->query($mysql);
                if ($myresult->num_rows > 0) {
                    while ($myrow = $myresult->fetch_assoc()) {
                        $row["material_name"] = $myrow["material_name"];
                        $row["material_type"] = $myrow["material_type"];
                        $row["material_subtype"] = $myrow["material_subtype"];
                        $row["grade"] = $myrow["grade"];
                    }
                }
                
            }
            
            $output[] = $row;
        }
    }
    
    echo json_encode($output);
 }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
$qc->close();
$store->close();
$purchase->close();
$security->close();
$qa->close();
$hr->close();
?>