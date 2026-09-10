<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require '../db.php';

require '../token.php';
require '../tcpdf/tcpdf.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
		$string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
		$string = explode("$", $string);
		$_GET["emp_id"] = $string[0];
		$_GET["department"] = $string[1];
		break;
	}
}

$type = $_GET["type"] ?? '';
if ($type === '') {
	echo "{\"status\":\"invalid\",\"message\":\"type required\"}";
	$conn->close();
	exit;
}

if ($type === "saveOutpass") {
	if (!$input || !is_array($input)) {
		echo "{\"status\":\"error\",\"message\":\"Invalid input\"}";
		exit;
	}
	$emp_id = $conn->real_escape_string(trim($input['emp_id'] ?? $_GET['emp_id'] ?? ''));
	$emp_name = $conn->real_escape_string(trim($input['emp_name'] ?? ''));
	$department = $conn->real_escape_string(trim($input['department'] ?? ''));
	$reason = $conn->real_escape_string(trim($input['reason'] ?? ''));
	$reason_details = $conn->real_escape_string(trim($input['reason_details'] ?? ''));
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? $input['plant_id'] ?? '');
	if ($emp_id === '' || $emp_name === '' || $department === '' || $reason === '') {
		echo "{\"status\":\"error\",\"message\":\"emp_id, emp_name, department, reason required\"}";
		exit;
	}
	$createdBy = $conn->real_escape_string($_GET['emp_id'] ?? $emp_id);
	$createdOn = date("Y-m-d H:i:s");
	$pass_no = 'OP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
	$sql = "INSERT INTO outpass (plant_id, emp_id, emp_name, department, reason, reason_details, status, pass_no, createdBy, createdOn) VALUES ('" . $plant_id . "','" . $emp_id . "','" . $emp_name . "','" . $department . "','" . $reason . "','" . $reason_details . "','PENDING_DEPT_HEAD','" . $pass_no . "','" . $createdBy . "','" . $createdOn . "')";
	if ($conn->query($sql)) {
		echo "{\"status\":\"success\",\"id\":" . $conn->insert_id . ",\"pass_no\":\"" . $pass_no . "\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"" . $conn->real_escape_string($conn->error) . "\"}";
	}
} else if ($type === "getOutpassForDeptHead") {
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$dept = $conn->real_escape_string(trim($_GET['deptName'] ?? ''));
	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "' AND status = 'PENDING_DEPT_HEAD'";
	if ($dept !== '') {
		$sql .= " AND department = '" . $dept . "'";
	}
	$sql .= " ORDER BY createdOn DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($type === "deptHeadApproveOutpass") {
	$id = $conn->real_escape_string(trim($_GET['id'] ?? ''));
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$action = isset($_GET['action']) && $_GET['action'] === 'reject' ? 'reject' : 'approve';
	$now = date("Y-m-d H:i:s");
	if ($action === 'approve') {
		$sql = "UPDATE outpass SET status = 'PENDING_HR_HEAD', deptHeadApprovalBy = '" . $emp_id . "', deptHeadApprovalOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_DEPT_HEAD'";
	} else {
		$sql = "UPDATE outpass SET status = 'REJECTED_DEPT_HEAD', deptHeadRejectedBy = '" . $emp_id . "', deptHeadRejectedOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_DEPT_HEAD'";
	}
	if ($conn->query($sql) && $conn->affected_rows > 0) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
	}
} else if ($type === "getOutpassForHrHead") {
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "' AND status = 'PENDING_HR_HEAD' ORDER BY deptHeadApprovalOn DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($type === "hrHeadApproveOutpass") {
	$id = $conn->real_escape_string(trim($_GET['id'] ?? ''));
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$hrCols = array('hrHeadApprovalBy', 'hrHeadApprovalOn', 'hrHeadRejectedBy', 'hrHeadRejectedOn');
	foreach ($hrCols as $col) {
		$chk = $conn->query("SHOW COLUMNS FROM outpass LIKE '" . $col . "'");
		if ($chk && $chk->num_rows === 0) {
			$colType = strpos($col, 'On') !== false ? 'DATETIME NULL' : 'VARCHAR(50) NULL';
			@$conn->query("ALTER TABLE outpass ADD `" . $col . "` " . $colType);
		}
	}
	$action = isset($_GET['action']) && $_GET['action'] === 'reject' ? 'reject' : 'approve';
	$now = date("Y-m-d H:i:s");
	if ($action === 'approve') {
		$sql = "UPDATE outpass SET status = 'PENDING_SECURITY_EXIT', hrHeadApprovalBy = '" . $emp_id . "', hrHeadApprovalOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_HR_HEAD'";
	} else {
		$sql = "UPDATE outpass SET status = 'REJECTED_HR_HEAD', hrHeadRejectedBy = '" . $emp_id . "', hrHeadRejectedOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_HR_HEAD'";
	}
	if ($conn->query($sql) && $conn->affected_rows > 0) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
	}
} else if ($type === "getOutpassForPlantHead") {
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "' AND status = 'PENDING_PLANT_HEAD' ORDER BY hrHeadApprovalOn DESC, deptHeadApprovalOn DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($type === "plantHeadApproveOutpass") {
	$id = $conn->real_escape_string(trim($_GET['id'] ?? ''));
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$action = isset($_GET['action']) && $_GET['action'] === 'reject' ? 'reject' : 'approve';
	$now = date("Y-m-d H:i:s");
	if ($action === 'approve') {
		$sql = "UPDATE outpass SET status = 'PENDING_SECURITY_EXIT', plantHeadApprovalBy = '" . $emp_id . "', plantHeadApprovalOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_PLANT_HEAD'";
	} else {
		$sql = "UPDATE outpass SET status = 'REJECTED_PLANT_HEAD', plantHeadRejectedBy = '" . $emp_id . "', plantHeadRejectedOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_PLANT_HEAD'";
	}
	if ($conn->query($sql) && $conn->affected_rows > 0) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
	}
} else if ($type === "getOutpassForSecurity") {
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$from_date = $conn->real_escape_string($_GET['from_date'] ?? '');
	$to_date = $conn->real_escape_string($_GET['to_date'] ?? '');
	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "'";
	if ($from_date !== '' && $to_date !== '') {
		$sql .= " AND (DATE(createdOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "' OR DATE(hrHeadApprovalOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "' OR DATE(plantHeadApprovalOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "' OR DATE(securityExitOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "')";
	}
	$sql .= " AND status IN ('PENDING_SECURITY_EXIT','EXIT') ORDER BY id DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($type === "securityExitOutpass") {
	$id = $conn->real_escape_string(trim($_GET['id'] ?? ''));
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	if ($id === '') {
		echo "{\"status\":\"error\",\"message\":\"id required\"}";
		exit;
	}
	$now = date("Y-m-d H:i:s");
	$sql = "UPDATE outpass SET status = 'EXIT', securityExitBy = '" . $emp_id . "', securityExitOn = '" . $now . "' WHERE id = '" . $id . "' AND status = 'PENDING_SECURITY_EXIT'";
	if ($conn->query($sql) && $conn->affected_rows > 0) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"error\",\"message\":\"Failed or already processed\"}";
	}
} else if ($type === "getOutpassLogForExport") {
	$output = array();
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	$from_date = $conn->real_escape_string($_GET['from_date'] ?? '');
	$to_date = $conn->real_escape_string($_GET['to_date'] ?? '');
	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "'";
	if ($from_date !== '' && $to_date !== '') {
		$sql .= " AND (DATE(createdOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "' OR DATE(hrHeadApprovalOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "' OR DATE(plantHeadApprovalOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "' OR DATE(securityExitOn) BETWEEN '" . $from_date . "' AND '" . $to_date . "')";
	}
	$sql .= " ORDER BY id DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($type === "getMyOutpass") {
	$output = array();
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');
	if ($emp_id === '') {
		echo json_encode($output);
		$conn->close();
		exit;
	}
	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "' AND emp_id = '" . $emp_id . "' ORDER BY createdOn DESC";
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($type === "downloadMyOutpassPdf") {
	$emp_id = $conn->real_escape_string($_GET['emp_id'] ?? '');
	$plant_id = $conn->real_escape_string($_GET['plant_id'] ?? '');

	if ($emp_id === '') {
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'Employee not found. Please log in again.';
		exit;
	}

	if (ob_get_level()) {
		ob_end_clean();
	}

	$_GET['filename'] = 'Outpass Log';
	$_GET['pdftype'] = 'landscape';
	include('../pdfimp2.php');

	$html = '';
	$html .= '
	<table cellpadding="5" border="0.1">
	  <tr>
	    <td style="width:100%; text-align:center; font-weight:bold; background-color:#DDDAD9;">MY OUTPASS LOG</td>
	  </tr>
	</table>
	<div></div>
	<table cellpadding="4" border="0.1">
	  <tr style="text-align:center; background-color:#DDDAD9;">
	    <td style="width:6%;"><b>Sr.</b></td>
	    <td style="width:12%;"><b>Pass No</b></td>
	    <td style="width:14%;"><b>Employee</b></td>
	    <td style="width:12%;"><b>Department</b></td>
	    <td style="width:18%;"><b>Reason</b></td>
	    <td style="width:14%;"><b>Created On</b></td>
	    <td style="width:14%;"><b>Status</b></td>
	    <td style="width:10%;"><b>Exit On</b></td>
	  </tr>';

	$sql = "SELECT * FROM outpass WHERE plant_id = '" . $plant_id . "'";
	if ($emp_id !== '') {
		$sql .= " AND emp_id = '" . $emp_id . "'";
	}
	$sql .= " ORDER BY id DESC";
	$result = $conn->query($sql);
	$sr = 0;
	$statusMap = array(
		'PENDING_DEPT_HEAD' => 'Pending Dept Head',
        'PENDING_HR_HEAD' => 'Pending HR Head',
		'PENDING_PLANT_HEAD' => 'Pending Plant Head',
		'PENDING_SECURITY_EXIT' => 'Awaiting Exit',
		'EXIT' => 'Completed',
		'REJECTED_DEPT_HEAD' => 'Rejected (Dept)',
		'REJECTED_HR_HEAD' => 'Rejected (HR)',
		'REJECTED_PLANT_HEAD' => 'Rejected (Plant)'
	);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$sr++;
			$status = isset($statusMap[$row['status']]) ? $statusMap[$row['status']] : $row['status'];
			$reason = htmlspecialchars((string)($row['reason'] ?: 'NA'), ENT_QUOTES, 'UTF-8');
			if (!empty($row['reason_details'])) {
				$reason .= '<br>' . htmlspecialchars((string)$row['reason_details'], ENT_QUOTES, 'UTF-8');
			}
			$created = !empty($row['createdOn']) ? date('d-m-Y H:i', strtotime($row['createdOn'])) : 'NA';
			$exitOn = !empty($row['securityExitOn']) ? date('d-m-Y H:i', strtotime($row['securityExitOn'])) : 'NA';
			$html .= '
	  <tr>
	    <td style="width:6%; text-align:center;">' . $sr . '</td>
	    <td style="width:12%;">' . htmlspecialchars((string)($row['pass_no'] ?: 'NA'), ENT_QUOTES, 'UTF-8') . '</td>
	    <td style="width:14%;">' . htmlspecialchars((string)($row['emp_name'] ?: 'NA'), ENT_QUOTES, 'UTF-8') . '</td>
	    <td style="width:12%;">' . htmlspecialchars((string)($row['department'] ?: 'NA'), ENT_QUOTES, 'UTF-8') . '</td>
	    <td style="width:18%;">' . $reason . '</td>
	    <td style="width:14%; text-align:center;">' . $created . '</td>
	    <td style="width:14%;">' . htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') . '</td>
	    <td style="width:10%; text-align:center;">' . $exitOn . '</td>
	  </tr>';
		}
	} else {
		$html .= '
	  <tr>
	    <td colspan="8" style="width:100%; text-align:center;">No Record Found!</td>
	  </tr>';
	}
	$html .= '
	</table>';

	$pdf->writeHTML($html, true, false, false, false, '');
	$pdf->Output('Outpass_Log.pdf', 'I');
	exit;
} else {
	echo "{\"status\":\"invalid\",\"message\":\"Unknown type\"}";
}

$conn->close();
