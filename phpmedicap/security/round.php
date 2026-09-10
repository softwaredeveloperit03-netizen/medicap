<?php
require '../db.php';
require '../token.php';
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

if($_GET["type"]=="getSecurityDetails"){
	$output = Array();
	$sql = "SELECT * FROM security_round";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="saveSecurityRound") {
	error_reporting(0);
	$input = json_decode(file_get_contents('php://input'),true);
	define('UPLOAD_DIR', 'images/');
    $image_parts = explode(";base64,", $input['photo']);
    $image_type_aux = explode("images/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    $file = UPLOAD_DIR . uniqid() . '.png';
	file_put_contents($file, $image_base64);
	$photo = $file;

 	$sql = "INSERT INTO security_round (security_person,from_time,to_time,department,observation,abnormalities,description,report_to,entry_date,entry_by) 
	VALUES ('".$input["security_person"]."','".$input["from_time"]."','".$input["to_time"]."','".$input["department"]."','".$input["observation"]."','".$input["abnormalities"]."','".$input["description"]."','".$input["report_to"]."','".$entry_date."','".$_GET["emp_id"]."')";
	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="exitVechile"){
	$sql = "UPDATE vechile_entry SET out_time='".$entry_date."',outentry_by='".$_GET["emp_id"]."', status='inprocess' WHERE id ='".$_GET["id"]."'";
	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	} else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getMaterialOutList"){
	$sql = "SELECT * FROM materialout WHERE DATE(entry_date)=CURDATE()";
	$result = $conn->query($sql);
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
}
else if($_GET["type"]=="saveMaterialOutForm"){
	if(!isset($input["vehicle_no"])){
		$input["vehicle_no"] = '';
	}
	$sql = "INSERT INTO materialout (invoice_no,material_name,vendor_name,reason,quantity,returnable,request_by,transport,driverName,driverMobile,vehicle_no,entry_by,entry_date) 
	VALUES ('".$input["invoiceNo"]."','".$input["materialName"]."','".$input["vendorName"]."','".$input["reason"]."','".$input["quantity"]."','".$input["returnable"]."','".$input["request_by"]."','".$input["transportCompany"]."','".$input["driverName"]."','".$input["driverMobile"]."','".$input["vehicle_no"]."','".$_GET["emp_id"]."','$entry_date')"; 

	if($conn->query($sql)===TRUE){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getDepartmentEmployees"){
	$sql = "SELECT * FROM employee WHERE department='".$_GET["selectedDepartment"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getCandidates"){
	$sql = "SELECT * FROM employee WHERE status='active'";
	$result = $conn->query($sql);
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
}

else if($_GET["type"]=="getVisitors") {
	$sql = "SELECT name FROM gatepass";
	$result = $conn->query($sql);
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
}else if ($_GET["type"] == "downloadRound") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Security Round Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Security Round Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 8%;">Sr.No.</td>
                    <td style="width: 10%;">Security Person</td>
                    <td style="width: 8%;">From Time</td>
                    <td style="width: 10%;">To Time	</td>
                    <td style="width: 13%;">Department</td>
                    <td style="width: 13%;">Observation	</td>
                    <td style="width: 15%;">Abnormalities</td>
                    <td style="width: 10%;">Report To	</td>
                    <td style="width: 13%;">Description	</td>
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT * FROM security_round ";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                    

            <td style="width: 8%; ">'.$i.'</td>
                <td style="width: 10%; ">'.$row['security_person'].'</td>
                <td style="width: 8%; ">'.$row['from_time'].'</td>
                <td style="width: 10%; ">'.$row['to_time'].'</td>
                <td style="width: 13%; ">'.$row['department'].'</td>
                <td style="width: 13%; ">'.$row['observation'].'</td>
                <td style="width: 15%; ">'.$row['abnormalities'].'</td>
                <td style="width: 10%; ">'.$row['report_to'].'</td>
                <td style="width: 13%; ">'.$row['description'].'</td>
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Security Round.pdf', 'I');
        
} else if($_GET["type"] == "downloadInwordLog"){
        $_GET['filename'] = 'MATERIAL INWARD REGISTER'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp6.php');
        $html.="";
            $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadInwordLog','I');

    }

}



$conn->close();
?>