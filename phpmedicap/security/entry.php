<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

function ensureVehicleEntryColumns($conn) {
    $columns = array(
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'out_time' => 'DATETIME NULL',
        'outentry_by' => 'VARCHAR(50) NULL',
    );
    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM vechile_entry LIKE '".$column."'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE vechile_entry ADD COLUMN ".$column." ".$definition);
        }
    }
}

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

if($_GET["type"]=="getVechileDetails"){
	ensureVehicleEntryColumns($conn);
	$output = Array();
	$sql = "SELECT * FROM vechile_entry ORDER BY id DESC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="saveVechileEntry") {
	ensureVehicleEntryColumns($conn);
	error_reporting(0);
	$input = json_decode(file_get_contents('php://input'),true);
	if (!empty($input['photo'])) {
	define('UPLOAD_DIR', 'images/');
    $image_parts = explode(";base64,", $input['photo']);
    $image_type_aux = explode("images/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    $file = UPLOAD_DIR . uniqid() . '.png';
	file_put_contents($file, $image_base64);
	}

	$sql = "INSERT INTO vechile_entry (vechile_type,vechile_no,visitor_name,driver_name,entry_date,inentry_by,status) 
	VALUES ('".$conn->real_escape_string($input["vechile_type"])."','".$conn->real_escape_string($input["vechile_no"])."','".$conn->real_escape_string($input["visitor_name"])."','".$conn->real_escape_string($input["driver_name"])."','".$entry_date."','".$_GET["emp_id"]."','pending')";
	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="exitVechile"){
	ensureVehicleEntryColumns($conn);
	$id = $conn->real_escape_string($_GET["id"]);
	$check = $conn->query("SELECT id, out_time FROM vechile_entry WHERE id='".$id."'");
	if(!$check || $check->num_rows === 0) {
		echo "{\"status\":\"failed\"}";
	} else {
		$row = $check->fetch_assoc();
		if(!empty($row['out_time']) && $row['out_time'] !== '0000-00-00 00:00:00') {
			echo "{\"status\":\"filled\"}";
		} else {
			$sql = "UPDATE vechile_entry SET out_time='".$entry_date."',outentry_by='".$_GET["emp_id"]."', status='exit' WHERE id ='".$id."'";
			if($conn->query($sql)) {
				echo "{\"status\":\"success\"}";
			} else {
				echo "{\"status\":\"failed\"}";
			}
		}
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
}else if ($_GET["type"] == "downloadLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Vehicle Entry Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center"> Vehicle  Entry Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 10%;">Vehicle Type</td>
                    <td style="width: 10%;">Vehicle No	</td>
                    <td style="width: 10%;">Entry Time	</td>
                    <td style="width: 10%;">Exit Time	</td>
                    <td style="width: 20%;">Visitors/ Employees Name	</td>
                    <td style="width: 10%;">Driver Name		</td>
                    <td style="width: 10%;">Entry By	</td>
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT * FROM vechile_entry";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                    

    <td style="width: 10%; ">'.$i.'</td>
                <td style="width: 10%; ">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                <td style="width: 10%; ">'.$row['vechile_type'].'</td>
                <td style="width: 10%; ">'.$row['vechile_no'].'</td>
                <td style="width: 10%; ">'.date('H:i',strtotime($row['entry_date'])).'</td>
                <td style="width: 10%; ">'.date('H:i',strtotime($row['out_time'])).'</td>
                <td style="width: 20%; ">'.$row['visitor_name'].'</td>
                <td style="width: 10%; ">'.$row['driver_name'].'</td>
                <td style="width: 10%; ">'.$row['inentry_by'].'</td>
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vehicle.pdf', 'I');
    }
}

 else {
    echo "{\"status\":\"invalid\"}";
}


$conn->close();
?>