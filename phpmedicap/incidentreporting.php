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

if($_GET["type"]=="saveUserForm") {
    $no1 = 0;
    $sql = "SELECT IFNULL(MAX(no1), 0) as no1 FROM incidentreport";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $no1 = $row["no1"];
        }
    }
    $no1++;
    $report_no = "IR-00".$no1;
	$sql = "INSERT INTO incidentreport (report_no, report_date, incident_description,immediate_cause, department, reported_by, no1) VALUES ('$report_no','$entry_date','".$input["incident_description"]."','".$input["immediate_cause"]."','".$_GET['department']."','".$_GET['emp_id']."', '$no1')";
	if($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
	}
} else if($_GET["type"]=="getUserForms"){
	$sql = "SELECT * FROM incidentreport";
    $result = $qa->query($sql);
    $output = Array();
      if($result->num_rows > 0){
    	  while($row = $result->fetch_assoc()){
    	  $output[] = $row;
        }
        echo json_encode($output);
      }
} else if ($_GET["type"] == "saveIncident") {
    $sql = "SELECT IFNULL(MAX(i_no), 0) as  i_no FROM incident";
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
        $dev_no = 'IR-00'.$i_no;
    }else if($num == '2'){
        $dev_no = 'IR-0'.$i_no;
    }else{
        $dev_no = 'IR-'.$i_no;
    }
    $mfg_date = date('Y-m-d', strtotime($input['mfg_date']));
    $expiry_date = date('Y-m-d', strtotime($input['exp_date']));
    $sql = "INSERT INTO incident (incident_no,i_no,department,related_to,type,incident_date,justification,cause,description,batch_no,mfg_date,expiry_date,entry_by,entry_date) VALUES ('$dev_no','$i_no','".$input["department"]."','".$input["related_to"]."', '".$input["type"]."', '".$input["incident_date"]."', '".$input["justification"]."','".$input["cause"]."','".$input["description"]."','".$input["batch_no"]."','$mfg_date','$expiry_date','".$_GET["emp_id"]."','$entry_date' )";
    if ($conn->query($sql) === TRUE) {
        $checkBox = count($input["departmentlist"]);
        if(count($checkBox) > 0){
            $data = $input["departmentlist"];
            for ($i = 0; $i < $checkBox; $i++) {
                $temp = $data[$i];
                $sql = "INSERT INTO incident_comments (incident_no,department) VALUES ('$dev_no','$temp')";
                $conn->query($sql);
            }
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingIncidents") {
    $output = Array();
    $sql = "SELECT * FROM incident_comments WHERE department='".$_GET["department"]."' AND status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM incident WHERE incident_no='".$row["incident_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output[] = $row1;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateIncident") {
    $sql = "UPDATE incident_comments SET comment='".$input["comment"]."', status='".$input["status"]."' WHERE incident_no='".$input["incident_no"]."' AND department='".$_GET["department"]."'";
    if ($conn->query($sql)) {
        $sql = "SELECT COUNT(id) as id FROM incident_comments WHERE status='pending' AND incident_no='".$input["incident_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
        } else {
            $sql = "UPDATE incident SET status='done' WHERE incident_no='".$input["incident_no"]."'";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
} else {
    echo "[]";
}

$conn->close();
$qc->close();
$store->close();
$purchase->close();
$security->close();
$qa->close();
$hr->close();
?>