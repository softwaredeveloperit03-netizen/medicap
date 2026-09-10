<?php




// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require '../db.php';

require '../token.php';

require '../whatsappSms.php';
require '../tcpdf/tcpdf.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_time = date("h:i:s", $timestamp);
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

if($_GET["type"]=="getGatepassDetails"){
	$output = Array();
	$dept_raw = isset($_GET['deptName']) ? $_GET['deptName'] : (isset($_GET['department_name']) ? $_GET['department_name'] : '');
	if ($dept_raw === 'undefined') $dept_raw = '';
	$dept_filter = $conn->real_escape_string(trim($dept_raw));
	$from_date = $conn->real_escape_string($_GET['from_date'] ?? '');
	$to_date = $conn->real_escape_string($_GET['to_date'] ?? '');
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT g.*, CONCAT(e.firstname,' ',e.lastname) AS meetingwithName FROM gatepass g LEFT JOIN employee e ON g.meetingwith = e.emp_id 
	WHERE g.plant_id = '".$plant_id."' AND  g.visitDate IS NOT NULL AND g.visitDate != '' AND DATE(g.visitDate) BETWEEN '".$from_date."' AND '".$to_date."'  ";
	if ($dept_filter !== '') {
		$sql .= " AND g.department_name LIKE '%".$dept_filter."%'  ";
	}
	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
}
else if($_GET["type"]=="getGatepassDetailsForSecurityLog"){
	$output = array();
	$from_date = isset($_GET['from_date']) ? $conn->real_escape_string(trim($_GET['from_date'])) : '';
	$to_date = isset($_GET['to_date']) ? $conn->real_escape_string(trim($_GET['to_date'])) : '';
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT g.*, CONCAT(e.firstname,' ',e.lastname) AS meetingwithName FROM gatepass g LEFT JOIN employee e ON g.meetingwith = e.emp_id WHERE g.plant_id = '".$plant_id."' AND ( (g.visitDate IS NOT NULL AND g.visitDate != '' AND DATE(g.visitDate) BETWEEN '".$from_date."' AND '".$to_date."') OR ((g.visitDate IS NULL OR g.visitDate = '') AND g.entryOn IS NOT NULL AND g.entryOn != '' AND DATE(g.entryOn) BETWEEN '".$from_date."' AND '".$to_date."') ) ORDER BY g.id DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getGatepassDetailsForReceptionLog"){
	// Reception log: only status = 'VISITOR_IN' (when status changes, entry no longer here), ordered by date
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT g.*, CONCAT(e.firstname,' ',e.lastname) AS meetingwithName FROM gatepass g LEFT JOIN employee e ON g.meetingwith = e.emp_id
	WHERE g.plant_id = '".$plant_id."' AND g.status = 'VISITOR_IN'
	ORDER BY g.in_time DESC, g.id DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="updateReceptionStatus"){
	// Update status column (no visit_status col). After update, entry leaves reception log (only VISITOR_IN shown).
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	$status = isset($_GET['status']) ? $conn->real_escape_string(trim($_GET['status'])) : '';
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	if ($emp_id !== '') {
		// Also store who acknowledged (requires DB col: acknowledgeBy). If column doesn't exist yet, fall back to status update only.
		$sql = "UPDATE gatepass SET status = '".$status."', acknowledgeBy = '".$emp_id."' WHERE id = '".$id."'";
		if ($conn->query($sql)) {
			echo "{\"status\":\"success\"}";
		} else {
			$err = $conn->error;
			if (stripos($err, "Unknown column") !== false && stripos($err, "acknowledgeBy") !== false) {
				$sql2 = "UPDATE gatepass SET status = '".$status."' WHERE id = '".$id."'";
				if ($conn->query($sql2)) {
					echo "{\"status\":\"success\",\"message\":\"acknowledgeBy column missing (status updated only)\"}";
				} else {
					echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
				}
			} else {
				echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($err)."\"}";
			}
		}
	} else {
		$sql = "UPDATE gatepass SET status = '".$status."' WHERE id = '".$id."'";
		if ($conn->query($sql)) {
			echo "{\"status\":\"success\"}";
		} else {
			echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
		}
	}
}
else if($_GET["type"]=="visitorIn"){
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	$sql = "UPDATE gatepass SET in_time = '".$entry_date."', inentry_by = '".$emp_id."', status = 'VISITOR_IN' WHERE id = '".$id."'";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}
else if($_GET["type"]=="printGatepassById"){
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	if ($id === '') {
		header('Content-Type: text/html; charset=utf-8');
		echo '<!DOCTYPE html><html><body><p>Invalid or missing pass ID.</p></body></html>';
		exit;
	}
	$sql = "SELECT g.*, CONCAT(e.firstname,' ',e.lastname) AS meetingwithName FROM gatepass g LEFT JOIN employee e ON g.meetingwith = e.emp_id WHERE g.id = '".$id."' LIMIT 1";
	$result = $conn->query($sql);
	if (!$result || $result->num_rows === 0) {
		header('Content-Type: text/html; charset=utf-8');
		echo '<!DOCTYPE html><html><body><p>Gate pass not found.</p></body></html>';
		exit;
	}
	$row = $result->fetch_assoc();
	$pass_no = isset($row['passNo']) && $row['passNo'] !== '' ? htmlspecialchars($row['passNo']) : 'GP-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
	$name = htmlspecialchars($row['visitorName'] ?? $row['name'] ?? '');
	$phone = htmlspecialchars($row['phoneNumber'] ?? $row['mobile'] ?? '');
	$email = htmlspecialchars($row['email'] ?? '');
	$category = htmlspecialchars($row['category'] ?? '');
	$company = htmlspecialchars($row['company'] ?? '');
	$dept = htmlspecialchars($row['department_name'] ?? $row['department'] ?? '');
	$meetingWith = htmlspecialchars($row['meetingwithName'] ?? $row['meetingWithName'] ?? $row['firstname'] ?? '');
	$purpose = htmlspecialchars($row['purpose'] ?? '');
	$visitDate = !empty($row['visitDate']) ? date('d-M-Y', strtotime($row['visitDate'])) : '-';
	$country = htmlspecialchars($row['country'] ?? '');
	$state = htmlspecialchars($row['state'] ?? '');
	$city = htmlspecialchars($row['city'] ?? '');
	$inTime = !empty($row['in_time']) ? date('d-M-Y H:i', strtotime($row['in_time'])) : '-';
	$outTime = !empty($row['out_time']) ? date('d-M-Y H:i', strtotime($row['out_time'])) : '-';
	$entryOn = !empty($row['entryOn']) ? date('d-M-Y H:i', strtotime($row['entryOn'])) : '-';
	$photoSrc = '';
	if (!empty($row['photo'])) {
		$photoSrc = (strpos($row['photo'], 'data:') === 0) ? $row['photo'] : 'data:image/jpeg;base64,' . $row['photo'];
	}
	header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gate Pass - <?php echo $pass_no; ?></title>
	<style>
		* { box-sizing: border-box; }
		body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; margin: 0; padding: 8px; font-size: 10px; color: #222; background: #fff; }
		.print-sheet { }
		@media print {
			html, body { margin: 0 !important; padding: 0 !important; height: 100% !important; overflow: hidden !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
			body { padding: 5mm !important; }
			.no-print { display: none !important; }
			/* Single page: card fits in top-left; cut to fit ID slot. Omit @page size so browser uses one sheet. */
			.print-sheet { width: 90mm !important; height: 58mm !important; max-height: 58mm !important; overflow: hidden !important; margin: 0 !important; page-break-inside: avoid !important; page-break-after: avoid !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
			.gatepass { box-shadow: none !important; border-radius: 2px !important; page-break-inside: avoid !important; break-inside: avoid !important; height: 58mm !important; max-height: 58mm !important; overflow: hidden !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
			.gatepass-header { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
			.gatepass-footer { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
		}
		.gatepass { width: 90mm; height: 58mm; max-height: 58mm; margin: 0 auto; border: 1px solid #1a5276; border-radius: 3px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.15); }
		.gatepass-header { background: #1a5276; color: #fff; padding: 2px 6px; text-align: center; line-height: 1.15; }
		.gatepass-header .title { font-size: 9px; font-weight: 700; letter-spacing: 0.5px; }
		.gatepass-header .pass-no { font-size: 7px; opacity: 0.95; }
		.gatepass-body { padding: 3px 6px; display: flex; gap: 3px; align-items: flex-start; }
		.photo-wrap { flex-shrink: 0; }
		.photo-wrap img { width: 26mm; height: 28mm; object-fit: cover; border: 1px solid #ccc; border-radius: 2px; display: block; }
		.details { flex: 1; min-width: 0; }
		.details table { width: 100%; border-collapse: collapse; font-size: 8px; }
		.details td { padding: 1px 4px 1px 0; vertical-align: top; line-height: 1.25; }
		.details .label { color: #555; font-size: 7px; text-transform: uppercase; width: 32%; }
		.details .value { font-weight: 500; word-break: break-word; }
		.gatepass-footer { padding: 2px 6px; background: #f0f2f4; border-top: 1px solid #ddd; font-size: 6px; color: #555; text-align: center; }
		.print-btn { position: fixed; top: 6px; right: 6px; padding: 6px 12px; background: #1a5276; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; }
		.print-btn:hover { background: #2471a3; }
	</style>
</head>
<body>
	<button class="print-btn no-print" onclick="window.print();">Print</button>
	<div class="print-sheet">
	<div class="gatepass">
		<div class="gatepass-header">
			<div class="title">VISITOR GATE PASS</div>
			<div class="pass-no"><?php echo $pass_no; ?> 路 <?php echo $visitDate; ?></div>
		</div>
		<div class="gatepass-body">
			<?php if ($photoSrc !== ''): ?>
			<div class="photo-wrap">
				<img src="<?php echo $photoSrc; ?>" alt="Photo" />
			</div>
			<?php endif; ?>
			<div class="details">
				<table>
					<tr><td class="label">Name</td><td class="value"><?php echo $name; ?></td></tr>
					<tr><td class="label">Contact</td><td class="value"><?php echo $phone; ?></td></tr>
					<tr><td class="label">Company</td><td class="value"><?php echo $company; ?></td></tr>
					<tr><td class="label">Dept</td><td class="value"><?php echo $dept; ?></td></tr>
					<tr><td class="label">Meeting</td><td class="value"><?php echo $meetingWith; ?></td></tr>
					<tr><td class="label">Location</td><td class="value"><?php echo $city . ($state ? ', ' . $state : '') . ($country ? ', ' . $country : ''); ?></td></tr>
				</table>
			</div>
		</div>
		<div class="gatepass-footer">Carry this pass while on premises</div>
	</div>
	</div>
	<script>
		window.onload = function() {
			window.print();
		};
	</script>
</body>
</html><?php
	exit;
}
else if($_GET["type"]=="getVisitorsByMettingWith"){
	// Employee dashboard: show all entries where meetingwith = login user emp_id, exclude only EXIT status
	$output = Array();
	$emp_id = isset($_GET['emp_id']) ? $conn->real_escape_string(trim($_GET['emp_id'])) : '';
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	if ($emp_id === '') {
		echo json_encode($output);
		exit;
	}
	$sql = "SELECT g.*, CONCAT(e.firstname,' ',e.lastname) AS meetingwithName FROM gatepass g LEFT JOIN employee e ON g.meetingwith = e.emp_id 
	WHERE g.plant_id = '".$plant_id."' AND g.meetingwith = '".$emp_id."' AND (g.status IS NULL OR g.status = '' OR g.status != 'EXIT')
	ORDER BY g.entryOn DESC, g.id DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="startMeeting"){
	// Employee starts meeting: move status VISITOR_AT_RECEPTION -> MEETING_STARTED
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$sql = "UPDATE gatepass SET status = 'MEETING_STARTED' WHERE id = '".$id."'";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}
else if($_GET["type"]=="exitvisitor"){
	// Mark visitor exit: set out_time/outentry_by and status = 'EXIT'
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	$sql = "UPDATE gatepass SET out_time = '".$entry_date."', outentry_by = '".$emp_id."', status = 'EXIT' WHERE id = '".$id."'";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}
else if($_GET["type"]=="visitCompllete"){
	// Mark meeting complete
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$sql = "UPDATE gatepass SET status = 'MEETING_COMPLETE' WHERE id = '".$id."'";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}



else if($_GET["type"]=="saveGatepassForm"){
	// Save new gatepass form and generate passNo
	$input = json_decode(file_get_contents('php://input'), true);
	if (!$input) {
		echo "{\"status\":\"error\",\"message\":\"invalid input\"}";
		exit;
	}
	$e = function($v) use ($conn) { return $conn->real_escape_string(isset($v) ? trim($v) : ''); };
	$visitDate = $e($input['visitDate'] ?? '');
	$phoneNumber = $e($input['phoneNumber'] ?? '');
	$visitorName = $e($input['visitorName'] ?? '');
	$email = $e($input['email'] ?? '');
	$gatepassType = $e($input['gatepassType'] ?? 'GATEPASS');
	$category = $e($input['category'] ?? '');
	$company = $e($input['company'] ?? '');
	$department_name = $e($input['department_name'] ?? '');
	$meetingwith = $e($input['meetingwith'] ?? '');
	$purpose = $e($input['purpose'] ?? '');
	$country = $e($input['country'] ?? '');
	$state = $e($input['state'] ?? '');
	$city = $e($input['city'] ?? '');
	$photo = '';
	if (!empty($input['photo'])) {
		if (is_string($input['photo'])) {
			$photo = $conn->real_escape_string($input['photo']);
		} elseif (is_array($input['photo']) && !empty($input['photo']['imageAsDataUrl'])) {
			$photo = $conn->real_escape_string($input['photo']['imageAsDataUrl']);
		}
	}
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$entryBy = $conn->real_escape_string($_GET['emp_id'] ?? '');
	
	// Generate simple passNo: Use max ID + 1, format as GP-XXXX
	$passNo = '';
	$maxIdSql = "SELECT MAX(id) as max_id FROM gatepass";
	$maxResult = $conn->query($maxIdSql);
	if ($maxResult && $maxResult->num_rows > 0) {
		$maxRow = $maxResult->fetch_assoc();
		$nextId = ($maxRow['max_id'] ?? 0) + 1;
		$passNo = 'GP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
	} else {
		$passNo = 'GP-0001';
	}
	
	// Get meetingwithName
	$meetingwithName = '';
	if ($meetingwith !== '') {
		$r = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS nm FROM employee WHERE emp_id='".$conn->real_escape_string($meetingwith)."' LIMIT 1");
		if ($r && $r->num_rows > 0) {
			$row = $r->fetch_assoc();
			$meetingwithName = $conn->real_escape_string($row['nm'] ?? '');
		}
	}
	
	$sql = "INSERT INTO gatepass (plant_id, gatepassType, passNo, visitDate, phoneNumber, visitorName, email, category, company, department_name, meetingwith, meetingwithName, purpose, country, state, city, entryBy, entryOn, photo, status) VALUES "
		."('".$plant_id."','".$gatepassType."','".$passNo."','".$visitDate."','".$phoneNumber."','".$visitorName."','".$email."','".$category."','".$company."','".$department_name."','".$meetingwith."','".$meetingwithName."','".$purpose."','".$country."','".$state."','".$city."','".$entryBy."','".$entry_date."','".$photo."','Pending')";
	if ($conn->query($sql)) {
		if ($phoneNumber !== '' && $meetingwithName !== '') {
			gatePassWhatsAppSms($phoneNumber, $meetingwithName);
		}
		// Notify meeting-with person about awaiting visitor
		if ($meetingwith !== '') {
			awaitingVisitorNOtification($visitDate, $visitorName, $meetingwith, $company);
		}
		echo "{\"status\":\"success\",\"passNo\":\"".$passNo."\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}

else if($_GET["type"]=="saveKeymaster"){

    $input = json_decode(file_get_contents('php://input'), true);
    $entry_date = date('Y-m-d H:i:s');

    $sql = "INSERT INTO key_master 
    (plant_id, department, key_no, section, date)
    VALUES (
        '".$_GET['plant_id']."',
        '".$input["department"]."',
        '".$input["key_no"]."',
        '".$input["section"]."',
        '".$entry_date."'
    )";

    if($conn->query($sql)){
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>$conn->error]);
    }
}

else if($_GET["type"]=="getKeymaster"){

    $output = array();

    $sql = "SELECT * FROM key_master 
            WHERE plant_id = '".$_GET['plant_id']."'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }

    echo json_encode($output);
}


else if($_GET["type"]=="getVisitorData"){
	// Get visitor data by phone number for auto-fill form
	$output = array();
	$phoneNumber = isset($_GET['phoneNumber']) ? $conn->real_escape_string(trim($_GET['phoneNumber'])) : '';
	if ($phoneNumber !== '') {
		$sql = "SELECT * FROM gatepass WHERE phoneNumber = '".$phoneNumber."' ORDER BY id DESC LIMIT 1";
		$result = $conn->query($sql);
		if($result && $result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$output[] = $row;
			}
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="saveKeyregister"){
 
 
        $sql = "INSERT INTO key_register (plant_id,key_no,activity,department,emp_name,date,time,entry_date,status,entryBy)

        VALUES  ('".$_GET['plant_id']."','".$input["key_no"]."','".$input["activity"]."','".$input["department"]."',

        '".$input["emp_name"]."','".$input["date"]."','".$input["time"]."','$entry_date','Pending','".$_GET['emp_id']."')";

    	if($conn->query($sql)){

    		echo "{\"status\":\"success\"}";

    	} else {

    		echo "{\"status\":\"".$conn->error."\"}";

    	}

    }
    
    else if($_GET["type"]=="getKeyregister"){
        $output = Array();
       $sql = "SELECT 
                    kr.*, 
                    e.firstname, 
                    e.lastname 
                FROM 
                    key_register kr 
                LEFT JOIN 
                    employee e 
                ON 
                    kr.emp_name = e.emp_id  
                WHERE 
                    kr.status = 'Pending';
                ";
 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
 
 else if($_GET["type"]=="getKeyRegisterpdf"){

    $_GET['filename'] = 'KEY ISSUANCE REGISTER'; 
    $_GET['pdftype'] = 'landscape';  

    include('../pdfimp2.php'); // ✅ This already adds logo from header

    $html = '';

    // ✅ TITLE ONLY (logo comes from header)
    $html .= '
    <h2 style="text-align:center;">KEY ISSUANCE REGISTER</h2>
    <br>
    ';

    // ✅ TABLE
    $html .= '
    <table cellpadding="5" border="1">
        <thead>
            <tr style="background-color:#DDDAD9; font-weight:bold;">
                <th width="5%">Sr. No.</th>
                <th width="8%">Date</th>
                <th width="20%">Employee Name</th>
                <th width="10%">Dept.</th>
                <th width="20%">Key Room No</th>
                <th width="10%">Taken Time</th>
                <th width="17%">Issued By</th>
            </tr>
        </thead>
        <tbody>
    ';

    $i = 1;

    $sql = "SELECT kr.*, e.firstname, e.lastname 
            FROM key_register kr 
            LEFT JOIN employee e ON kr.emp_name = e.emp_id 
            ORDER BY kr.id DESC";

    $result = $conn->query($sql);

    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){

            $html .= '
            <tr>
                <td width="5%">'.$i++.'</td>
                <td width="8%">'.date('d-m-Y', strtotime($row['date'])).'</td>
                <td width="20%">'.$row['emp_name'].' '.$row['lastname'].'</td>
                <td width="10%">'.$row['department'].'</td>
                <td width="20%">'.$row['activity'].'</td>
                <td width="10%">'.$row['time'].'</td>
                <td width="17%">'.$row['entryBy'].'<br>'.date('d-m-Y H:i', strtotime($row['entry_date'])).'</td>
            </tr>';
        }
    } else {
        $html .= '
        <tr>
            <td colspan="7" style="text-align:center;">No Data Found</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('Key_Issuance_Register.pdf', 'I');
}
 
else if($_GET["type"]=="getAllGatepassDetails"){
	// Get all gatepass records (no date filter)
	$output = Array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT g.*, CONCAT(e.firstname,' ',e.lastname) AS meetingwithName FROM gatepass g LEFT JOIN employee e ON g.meetingwith = e.emp_id WHERE g.plant_id = '".$plant_id."' ORDER BY g.id DESC";
	$result = $conn->query($sql);
	if($result && $result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="savePublicVisitorForm"){
	// Public visitor form submission (no meetingwith, status = 'PENDING')
	$input = json_decode(file_get_contents('php://input'), true);
	if (!$input) {
		echo "{\"status\":\"error\",\"message\":\"invalid input\"}";
		exit;
	}
	$e = function($v) use ($conn) { return $conn->real_escape_string(isset($v) ? trim($v) : ''); };
	$visitDate = $e($input['visitDate'] ?? '');
	$phoneNumber = $e($input['phoneNumber'] ?? '');
	$visitorName = $e($input['visitorName'] ?? '');
	$email = $e($input['email'] ?? '');
	$gatepassType = $e($input['gatepassType'] ?? 'GATEPASS');
	$category = $e($input['category'] ?? '');
	$company = $e($input['company'] ?? '');
	$department_name = $e($input['department_name'] ?? '');
	$purpose = $e($input['purpose'] ?? '');
	$country = $e($input['country'] ?? '');
	$state = $e($input['state'] ?? '');
	$city = $e($input['city'] ?? '');
	$photo = '';
	if (!empty($input['photo'])) {
		if (is_string($input['photo'])) {
			$photo = $conn->real_escape_string($input['photo']);
		} elseif (is_array($input['photo']) && !empty($input['photo']['imageAsDataUrl'])) {
			$photo = $conn->real_escape_string($input['photo']['imageAsDataUrl']);
		}
	}
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	
	// Generate simple passNo: Use max ID + 1
	$passNo = '';
	$maxIdSql = "SELECT MAX(id) as max_id FROM gatepass";
	$maxResult = $conn->query($maxIdSql);
	if ($maxResult && $maxResult->num_rows > 0) {
		$maxRow = $maxResult->fetch_assoc();
		$nextId = ($maxRow['max_id'] ?? 0) + 1;
		$passNo = 'GP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
	} else {
		$passNo = 'GP-0001';
	}
	
	$sql = "INSERT INTO gatepass (plant_id, gatepassType, passNo, visitDate, phoneNumber, visitorName, email, category, company, department_name, purpose, country, state, city, entryOn, photo, status) VALUES "
		."('".$plant_id."','".$gatepassType."','".$passNo."','".$visitDate."','".$phoneNumber."','".$visitorName."','".$email."','".$category."','".$company."','".$department_name."','".$purpose."','".$country."','".$state."','".$city."','".$entry_date."','".$photo."','PENDING')";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\",\"passNo\":\"".$passNo."\",\"message\":\"Visitor form submitted. Security will assign meeting person.\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}
else if($_GET["type"]=="getPendingVisitors"){
	// Get pending visitors (status = 'PENDING', no meetingwith assigned)
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT g.* FROM gatepass g WHERE g.plant_id = '".$plant_id."' AND g.status = 'PENDING' AND (g.meetingwith IS NULL OR g.meetingwith = '') ORDER BY g.id DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="assignMeetingWith"){
	// Security assigns meeting with person to pending visitor
	$id = isset($_GET['id']) ? $conn->real_escape_string(trim($_GET['id'])) : '';
	$meetingwith = isset($_GET['meetingwith']) ? $conn->real_escape_string(trim($_GET['meetingwith'])) : '';
	$department_name = isset($_GET['department_name']) ? $conn->real_escape_string(trim($_GET['department_name'])) : '';
	if ($id === '' || $meetingwith === '' || $department_name === '') {
		echo "{\"status\":\"error\",\"message\":\"id, meetingwith and department_name required\"}";
		exit;
	}
	// Get meetingwithName
	$meetingwithName = '';
	$r = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS nm FROM employee WHERE emp_id='".$conn->real_escape_string($meetingwith)."' LIMIT 1");
	if ($r && $r->num_rows > 0) {
		$row = $r->fetch_assoc();
		$meetingwithName = $conn->real_escape_string($row['nm'] ?? '');
	}
	// Update meetingwith, meetingwithName, department_name, and status
	$sql = "UPDATE gatepass SET meetingwith = '".$meetingwith."', meetingwithName = '".$meetingwithName."', department_name = '".$department_name."', status = 'Pending' WHERE id = '".$id."'";
	if ($conn->query($sql)) {
		$row = $conn->query("SELECT phoneNumber, visitDate, visitorName, company FROM gatepass WHERE id = '".$id."' LIMIT 1");
		if ($row && $row->num_rows > 0) {
			$visitor = $row->fetch_assoc();
			$visitorPhone = trim($visitor['phoneNumber'] ?? '');
			if ($visitorPhone !== '' && $meetingwithName !== '') {
				gatePassWhatsAppSms($visitorPhone, $meetingwithName);
			}
			// Notify meeting-with person about awaiting visitor
			$avVisitDate = $visitor['visitDate'] ?? '';
			$avVisitorName = $visitor['visitorName'] ?? '';
			$avCompany = $visitor['company'] ?? '';
			awaitingVisitorNOtification($avVisitDate, $avVisitorName, $meetingwith, $avCompany);
		}
		echo "{\"status\":\"success\",\"message\":\"Meeting person and department assigned\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}
else if($_GET["type"]=="generateQRCode"){
	// Generate QR Code PDF for Visitor Form
	$plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string(trim($_GET['plant_id'])) : '1';
	
	// Get visitor form URL from request parameter (passed from DataAccessService domain variable)
	// The form_url should be: domain + 'visitor-form.php?plant_id=X'
	// Example: https://paperlessgmp.in/phpWonder/php/phpDevelopWonder/visitor-form.php?plant_id=181
	$visitorFormUrl = '';
	if (isset($_GET['form_url']) && !empty($_GET['form_url'])) {
		$visitorFormUrl = urldecode($_GET['form_url']);
		// Ensure it's a valid URL
		if (!filter_var($visitorFormUrl, FILTER_VALIDATE_URL)) {
			$visitorFormUrl = '';
		}
	}
	
	// Fallback: construct from domain variable path if not provided
	if (empty($visitorFormUrl)) {
		// Use the same domain structure as DataAccessService
		// Default to production-like path, but should come from frontend
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		// Try to detect the PHP path from current script location
		$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
		$visitorFormUrl = $protocol . '://' . $host . $scriptPath . '/visitor-form.php?plant_id=' . $plant_id;
	}
	
	// Create new TCPDF instance
	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
	
	// Set document information
	$pdf->SetCreator('Visitor Management System');
	$pdf->SetAuthor('Security');
	$pdf->SetTitle('Visitor Form QR Code');
	$pdf->SetSubject('QR Code for Visitor Registration');
	
	// Remove default header/footer
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	
	// Set margins
	$pdf->SetMargins(20, 20, 20);
	$pdf->SetAutoPageBreak(true, 20);
	
	// Add a page
	$pdf->AddPage();
	
	// Set font
	$pdf->SetFont('helvetica', 'B', 20);
	
	// Title
	$pdf->Cell(0, 15, 'Visitor Registration QR Code', 0, 1, 'C');
	$pdf->Ln(5);
	
	// Instructions
	$pdf->SetFont('helvetica', '', 12);
	$pdf->Cell(0, 10, 'Scan this QR code to register as a visitor', 0, 1, 'C');
	$pdf->Ln(10);
	
	// Generate QR Code
	// QR code size: 60mm x 60mm
	$qrSize = 60;
	$pageWidth = $pdf->getPageWidth() - 40; // Account for margins
	$qrX = ($pageWidth - $qrSize) / 2 + 20; // Center horizontally
	
	// Style for QR code
	$style = array(
		'border' => 1,
		'vpadding' => 'auto',
		'hpadding' => 'auto',
		'fgcolor' => array(0,0,0),
		'bgcolor' => false,
		'module_width' => 1,
		'module_height' => 1
	);
	
	// Debug: Log the visitor form URL being used (remove in production)
	// error_log("QR Code - form_url received: " . (isset($_GET['form_url']) ? $_GET['form_url'] : 'NOT SET'));
	// error_log("QR Code - visitorFormUrl final: " . $visitorFormUrl);
	
	// Generate QR code
	$pdf->write2DBarcode($visitorFormUrl, 'QRCODE,L', $qrX, $pdf->GetY(), $qrSize, $qrSize, $style, 'N');
	
	$pdf->Ln($qrSize + 10);
	
	// Display URL text below QR code
	$pdf->SetFont('helvetica', '', 10);
	$pdf->SetTextColor(100, 100, 100);
	$pdf->Cell(0, 8, 'URL: ' . $visitorFormUrl, 0, 1, 'C', false, '', 0, false, 'T', 'M');
	
	$pdf->Ln(5);
	
	// Additional information
	$pdf->SetFont('helvetica', '', 9);
	$pdf->SetTextColor(150, 150, 150);
	$pdf->Cell(0, 6, 'Plant ID: ' . $plant_id, 0, 1, 'C');
	$pdf->Cell(0, 6, 'Generated on: ' . date('d-M-Y H:i:s'), 0, 1, 'C');
	
	// Output PDF
	$pdf->Output('Visitor_Form_QR_Code.pdf', 'I');
	exit;
}
else if($_GET["type"]=="getPlantDetails"){
	$plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string(trim($_GET['plant_id'])) : '';
	if ($plant_id === '') {
		echo "{\"status\":\"error\",\"message\":\"plant_id required\"}";
		exit;
	}
	$sql = "SELECT plant_id, plant_full_name, saftyInstruction, GMPInstruction, visitVideoLink FROM plant WHERE plant_id = '".$plant_id."' LIMIT 1";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		$row = $result->fetch_assoc();
		echo json_encode($row);
	} else {
		echo "{}";
	}
}
else if ($_GET["type"] == "getKeyMasterpdf") {

    $_GET['filename'] = 'KEY MASTER RECORD';
    $_GET['pdftype'] = 'onlyheader';

    include("../pdfimp2.php"); // ✅ Only this is enough

    $html = '<h2 style="text-align:center">Key Master Record</h2>';

    $html .= '<table border="1" cellpadding="4">
        <thead>
            <tr style="background-color:#DDDAD9; font-weight:bold;">
                <th>Sr</th>
                <th>Date</th>
                <th>Department</th>
                <th>Key No</th>
                <th>Section</th>
            </tr>
        </thead>
        <tbody>';

    $i = 1;

    $sql = "SELECT * FROM key_master 
            WHERE plant_id = '".$_GET['plant_id']."' 
            ORDER BY id DESC";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
            <td>'.$i.'</td>
            <td>'.date('d-m-Y', strtotime($row['date'])).'</td>
            <td>'.$row['department'].'</td>
            <td>'.$row['key_no'].'</td>
            <td>'.$row['section'].'</td>
        </tr>';
        $i++;
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('Key_Master_Record.pdf', 'I');
}
else if($_GET["type"]=="updatePlantDetails"){
	$input = json_decode(file_get_contents('php://input'), true);
	if (!$input) {
		$input = $_GET;
	}
	$plant_id = isset($input['plant_id']) ? $conn->real_escape_string(trim($input['plant_id'])) : '';
	$saftyInstruction = isset($input['saftyInstruction']) ? $conn->real_escape_string($input['saftyInstruction']) : '';
	$GMPInstruction = isset($input['GMPInstruction']) ? $conn->real_escape_string($input['GMPInstruction']) : '';
	$visitVideoLink = isset($input['visitVideoLink']) ? $conn->real_escape_string($input['visitVideoLink']) : '';
	if ($plant_id === '') {
		echo "{\"status\":\"error\",\"message\":\"plant_id required\"}";
		exit;
	}
	$sql = "UPDATE plant SET saftyInstruction = '".$saftyInstruction."', GMPInstruction = '".$GMPInstruction."', visitVideoLink = '".$visitVideoLink."' WHERE plant_id = '".$plant_id."'";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\",\"message\":\"Plant details updated\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
	}
}
else {
    echo "{\"status\":\"invalid\"}";
}


$conn->close();
?>
