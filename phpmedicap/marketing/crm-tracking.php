<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../db.php';
require '../token.php';

// Set CORS headers - must be set before any output
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"] ?? '';
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='".$token."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.($_GET["type"] ?? '').'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    @file_put_contents('../../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);
    
    function escCrm($conn, $value) {
        return $conn->real_escape_string(isset($value) ? $value : '');
    }

    function ensureCrmEmailTrackingColumns($conn) {
        $cols = array(
            'alternate_email' => 'VARCHAR(255) NULL',
            'alternate_phone' => 'VARCHAR(50) NULL',
            'hr_remark' => 'TEXT NULL'
        );
        foreach ($cols as $col => $def) {
            $check = $conn->query("SHOW COLUMNS FROM `crm_email_tracking` LIKE '".$col."'");
            if ($check && $check->num_rows == 0) {
                $conn->query("ALTER TABLE `crm_email_tracking` ADD COLUMN `".$col."` ".$def);
            }
        }
    }

    // ============================================
    // EMAIL TRACKING OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveEmail") {
        ensureCrmEmailTrackingColumns($conn);
        $data = array();
        if(!empty($_POST)) {
            $data = $_POST;
        } elseif(!empty($input)) {
            $data = $input;
        }
        
        $plant_id = escCrm($conn, $_GET["plant_id"] ?? '');
        $classification = escCrm($conn, $data['classification'] ?? '');
        $client_name = escCrm($conn, $data['client_name'] ?? '');
        $contact_person = escCrm($conn, $data['contact_person'] ?? '');
        $email_id = escCrm($conn, $data['email_id'] ?? '');
        $alternate_email = escCrm($conn, $data['alternate_email'] ?? '');
        $phone_number = escCrm($conn, $data['phone_number'] ?? '');
        $alternate_phone = escCrm($conn, $data['alternate_phone'] ?? '');
        $hr_remark = escCrm($conn, $data['hr_remark'] ?? '');
        $website = escCrm($conn, $data['website'] ?? '');
        $country = escCrm($conn, $data['country'] ?? '');
        $state = escCrm($conn, $data['state'] ?? '');
        $place = escCrm($conn, $data['place'] ?? '');
        $emp_id = escCrm($conn, $_GET["emp_id"] ?? '');
        $ip_logged = escCrm($conn, $_SERVER['REMOTE_ADDR'] ?? '');
        
        $sql = "INSERT INTO `crm_email_tracking`(
            `plant_id`, `classification`, `client_name`, `contact_person`, `email_id`,
            `alternate_email`, `phone_number`, `alternate_phone`, `hr_remark`,
            `website`, `country`, `state`, `place`,
            `created_by`, `created_date_time`, `ip_logged`
        ) VALUES (
            '$plant_id', '$classification', '$client_name',
            '$contact_person', '$email_id',
            '$alternate_email', '$phone_number', '$alternate_phone', '$hr_remark',
            '$website', '$country', '$state', '$place',
            '$emp_id', '$entry_date', '$ip_logged'
        )";
        
        if($conn->query($sql)) {
            echo json_encode(array("status" => "success", "message" => "Email tracking saved successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
        exit();
    }
    
    if($_GET["type"] == "getEmails") {
        $where = "1=1";
        if(!empty($_GET["plant_id"])) {
            $plant_id = $conn->real_escape_string($_GET["plant_id"]);
            $where .= " AND plant_id = '$plant_id'";
        }
        if(!empty($_GET["classification"])) {
            $classification = $conn->real_escape_string($_GET["classification"]);
            $where .= " AND classification = '$classification'";
        }
        if(!empty($_GET["status"])) {
            $status = $conn->real_escape_string($_GET["status"]);
            $where .= " AND status = '$status'";
        }
        
        $sql = "SELECT * FROM crm_email_tracking WHERE $where ORDER BY created_date_time DESC";
        $result = $conn->query($sql);
        
        $emails = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $emails[] = $row;
            }
        }
        
        echo json_encode($emails);
        exit();
    }
    
    if($_GET["type"] == "getFollowupEmails") {
        $sql = "SELECT * FROM crm_email_tracking WHERE followup_required = 'Yes' AND (followup_date >= CURDATE() OR followup_date IS NULL) ORDER BY followup_date ASC, followup_time ASC";
        $result = $conn->query($sql);
        
        $emails = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $emails[] = $row;
            }
        }
        
        echo json_encode($emails);
        exit();
    }
    
    if($_GET["type"] == "updateEmailFollowup") {
        $id = $conn->real_escape_string($input['id'] ?? '');
        $followup_date = $conn->real_escape_string($input['followup_date'] ?? '');
        $followup_time = $conn->real_escape_string($input['followup_time'] ?? '');
        $followup_notes = $conn->real_escape_string($input['followup_notes'] ?? '');
        $emp_id = $conn->real_escape_string($_GET["emp_id"] ?? '');
        
        $sql = "UPDATE crm_email_tracking SET 
            followup_date = '$followup_date',
            followup_time = '$followup_time',
            remarks = CONCAT(IFNULL(remarks, ''), '\nFollowup: ', '$followup_notes'),
            last_modified_by = '$emp_id',
            modified_date_time = '$entry_date'
            WHERE id = '$id'";
        
        if($conn->query($sql)) {
            echo json_encode(array("status" => "success", "message" => "Followup updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
        exit();
    }
    
    // ============================================
    // PHONE CALL TRACKING OPERATIONS
    // ============================================
    
    if($_GET["type"] == "savePhoneCall") {
        $data = array();
        if(!empty($_POST)) {
            $data = $_POST;
        } elseif(!empty($input)) {
            $data = $input;
        }
        
        // Escape input data
        $plant_id = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $classification = $conn->real_escape_string($data['classification'] ?? '');
        $client_name = $conn->real_escape_string($data['client_name'] ?? '');
        $contact_person = $conn->real_escape_string($data['contact_person'] ?? '');
        $phone_number = $conn->real_escape_string($data['phone_number'] ?? '');
        $call_type = $conn->real_escape_string($data['call_type'] ?? 'Outgoing');
        $call_date = $conn->real_escape_string($data['call_date'] ?? '');
        $call_time = $conn->real_escape_string($data['call_time'] ?? '');
        $call_duration = $conn->real_escape_string($data['call_duration'] ?? '');
        $called_by = $conn->real_escape_string($data['called_by'] ?? '');
        $status = $conn->real_escape_string($data['status'] ?? 'Completed');
        $response_received = $conn->real_escape_string($data['response_received'] ?? 'No');
        $response_details = $conn->real_escape_string($data['response_details'] ?? '');
        $followup_required = $conn->real_escape_string($data['followup_required'] ?? 'No');
        $followup_date = $conn->real_escape_string($data['followup_date'] ?? '');
        $followup_time = $conn->real_escape_string($data['followup_time'] ?? '');
        $remarks = $conn->real_escape_string($data['remarks'] ?? '');
        $emp_id = $conn->real_escape_string($_GET["emp_id"] ?? '');
        $ip_logged = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
        
        $sql = "INSERT INTO `crm_phone_tracking`(
            `plant_id`, `classification`, `client_name`, `contact_person`, `phone_number`,
            `call_type`, `call_date`, `call_time`, `call_duration`, `called_by`, `status`,
            `response_received`, `response_details`, `followup_required`, `followup_date`,
            `followup_time`, `remarks`, `created_by`, `created_date_time`, `ip_logged`
        ) VALUES (
            '$plant_id', '$classification', '$client_name',
            '$contact_person', '$phone_number',
            '$call_type', '$call_date', '$call_time', '$call_duration',
            '$called_by', '$status',
            '$response_received', '$response_details',
            '$followup_required', '$followup_date',
            '$followup_time', '$remarks',
            '$emp_id', '$entry_date', '$ip_logged'
        )";
        
        if($conn->query($sql)) {
            $insert_id = $conn->insert_id;
            echo json_encode(array("status" => "success", "message" => "Phone call tracking saved successfully", "id" => $insert_id));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
        exit();
    }
    
    if($_GET["type"] == "updatePhoneCall") {
        $id = $conn->real_escape_string($input['id'] ?? '');
        $call_type = $conn->real_escape_string($input['call_type'] ?? 'Outgoing');
        $call_date = $conn->real_escape_string($input['call_date'] ?? '');
        $call_time = $conn->real_escape_string($input['call_time'] ?? '');
        $call_duration = $conn->real_escape_string($input['call_duration'] ?? '');
        $called_by = $conn->real_escape_string($input['called_by'] ?? '');
        $status = $conn->real_escape_string($input['status'] ?? 'Completed');
        $response_received = $conn->real_escape_string($input['response_received'] ?? 'No');
        $response_details = $conn->real_escape_string($input['response_details'] ?? '');
        $followup_required = $conn->real_escape_string($input['followup_required'] ?? 'No');
        $followup_date = $conn->real_escape_string($input['followup_date'] ?? '');
        $followup_time = $conn->real_escape_string($input['followup_time'] ?? '');
        $remarks = $conn->real_escape_string($input['remarks'] ?? '');
        $emp_id = $conn->real_escape_string($_GET["emp_id"] ?? '');
        
        $sql = "UPDATE crm_phone_tracking SET 
            call_type = '$call_type',
            call_date = '$call_date',
            call_time = '$call_time',
            call_duration = '$call_duration',
            called_by = '$called_by',
            status = '$status',
            response_received = '$response_received',
            response_details = '$response_details',
            followup_required = '$followup_required',
            followup_date = '$followup_date',
            followup_time = '$followup_time',
            remarks = '$remarks',
            last_modified_by = '$emp_id',
            modified_date_time = '$entry_date'
            WHERE id = '$id'";
        
        if($conn->query($sql)) {
            echo json_encode(array("status" => "success", "message" => "Call details updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
        exit();
    }
    
    if($_GET["type"] == "getPhoneCalls") {
        $where = "1=1";
        if(!empty($_GET["id"])) {
            $id = $conn->real_escape_string($_GET["id"]);
            $where .= " AND id = '$id'";
        }
        if(!empty($_GET["plant_id"])) {
            $plant_id = $conn->real_escape_string($_GET["plant_id"]);
            $where .= " AND plant_id = '$plant_id'";
        }
        if(!empty($_GET["classification"])) {
            $classification = $conn->real_escape_string($_GET["classification"]);
            $where .= " AND classification = '$classification'";
        }
        if(!empty($_GET["status"])) {
            $status = $conn->real_escape_string($_GET["status"]);
            $where .= " AND status = '$status'";
        }
        
        $sql = "SELECT * FROM crm_phone_tracking WHERE $where ORDER BY created_date_time DESC";
        $result = $conn->query($sql);
        
        $calls = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $calls[] = $row;
            }
        }
        
        echo json_encode($calls);
        exit();
    }
    
    if($_GET["type"] == "getFollowupPhoneCalls") {
        $sql = "SELECT * FROM crm_phone_tracking WHERE followup_required = 'Yes' AND (followup_date >= CURDATE() OR followup_date IS NULL) ORDER BY followup_date ASC, followup_time ASC";
        $result = $conn->query($sql);
        
        $calls = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $calls[] = $row;
            }
        }
        
        echo json_encode($calls);
        exit();
    }
    
    if($_GET["type"] == "updatePhoneFollowup") {
        $id = $conn->real_escape_string($input['id'] ?? '');
        $followup_date = $conn->real_escape_string($input['followup_date'] ?? '');
        $followup_time = $conn->real_escape_string($input['followup_time'] ?? '');
        $followup_notes = $conn->real_escape_string($input['followup_notes'] ?? '');
        $emp_id = $conn->real_escape_string($_GET["emp_id"] ?? '');
        
        $sql = "UPDATE crm_phone_tracking SET 
            followup_date = '$followup_date',
            followup_time = '$followup_time',
            remarks = CONCAT(IFNULL(remarks, ''), '\nFollowup: ', '$followup_notes'),
            last_modified_by = '$emp_id',
            modified_date_time = '$entry_date'
            WHERE id = '$id'";
        
        if($conn->query($sql)) {
            echo json_encode(array("status" => "success", "message" => "Followup updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
        exit();
    }
    
    // If no matching type found, return error
    if(empty($_GET["type"])) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Missing request type parameter"));
    } else {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Invalid request type: " . $_GET["type"]));
    }
} else {
    // If token validation fails
    http_response_code(401);
    echo json_encode(array("status" => "error", "message" => "Invalid or missing token"));
}

$conn->close();
