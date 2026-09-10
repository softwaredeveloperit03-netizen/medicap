<?php
/**
 * Process Configuration API
 * Handles process type, stage, step, and substep operations
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

// Helper functions
function safe_escape($conn, $value) {
    if (is_null($value)) return 'NULL';
    return mysqli_real_escape_string($conn, $value);
}

function json_response($status, $message, $data = null, $error = null) {
    $response = ['status' => $status, 'msg' => $message];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

function json_error($conn, $message, $sql = '') {
    json_response('error', $message, null, $conn->error ?: $sql);
}

function json_success($message, $data = null) {
    json_response('success', $message, $data);
}

// Token validation
$token = isset($_GET["token"]) ? $_GET["token"] : "";
$currentUrl = isset($_GET["description"]) ? $_GET["description"] : "";
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$token_escaped = safe_escape($conn, $token);
$sql = "SELECT * FROM token WHERE token='".$token_escaped."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if (!$result || $result->num_rows == 0) {
    json_error($conn, "Invalid token");
}

while($row = $result->fetch_assoc()){
    $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
    $string = explode("$", $string);
    $_GET["emp_id"] = isset($string[0]) ? $string[0] : "";
    $_GET["department"] = isset($string[1]) ? $string[1] : "";
    break;
}

$type_escaped = isset($_GET["type"]) ? safe_escape($conn, $_GET["type"]) : "";
$emp_id_escaped = safe_escape($conn, $_GET["emp_id"]);
$department_escaped = safe_escape($conn, $_GET["department"]);
$method_escaped = isset($_SERVER['REQUEST_METHOD']) ? safe_escape($conn, $_SERVER['REQUEST_METHOD']) : "";
$remote_addr_escaped = isset($_SERVER['REMOTE_ADDR']) ? safe_escape($conn, $_SERVER['REMOTE_ADDR']) : "";
$currentUrl_escaped = safe_escape($conn, $currentUrl);

// Log request
$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token_escaped."','".$type_escaped."','".$entry_date."','".$department_escaped."','".$emp_id_escaped."','".$method_escaped."','".$remote_addr_escaped."','".$currentUrl_escaped."')";
$conn->query($sql);

$action = isset($_GET["type"]) ? $_GET["type"] : "";
$plant_id = isset($_GET["plant_id"]) ? safe_escape($conn, $_GET["plant_id"]) : "";

// ============================================================================
// GET PROCESS TYPES
// ============================================================================
if ($action == "get_process_types") {
    $sql = "SELECT DISTINCT process_type, id 
            FROM manufacturing_process 
            WHERE plant_id='".$plant_id."' 
            ORDER BY process_type ASC";
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $processTypes = [];
    while ($row = $result->fetch_assoc()) {
        $processTypes[] = [
            'id' => $row['id'],
            'process_type' => $row['process_type'],
            'description' => $row['process_type']
        ];
    }
    
    json_success("Process types retrieved", $processTypes);
}

// ============================================================================
// GET STAGES
// ============================================================================
else if ($action == "get_stages") {
    $process_type_id = isset($_GET["process_type_id"]) ? safe_escape($conn, $_GET["process_type_id"]) : "";
    
    if (empty($process_type_id)) {
        json_error($conn, "Process type ID is required");
    }
    
    $sql = "SELECT DISTINCT s.id, s.stage, s.stages 
            FROM manufacturing_process_stages s
            INNER JOIN manufacturing_process p ON s.manufacturing_process_id = p.id
            WHERE p.id='".$process_type_id."' 
            ORDER BY s.id ASC";
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $stages = [];
    while ($row = $result->fetch_assoc()) {
        $stages[] = [
            'id' => $row['id'],
            'stages' => $row['stages'] ?: $row['stage'],
            'stage_name' => $row['stages'] ?: $row['stage']
        ];
    }
    
    json_success("Stages retrieved", $stages);
}

// ============================================================================
// GET STEPS
// ============================================================================
else if ($action == "get_steps") {
    $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
    
    if (empty($stage_id)) {
        json_error($conn, "Stage ID is required");
    }
    
    $sql = "SELECT id, step, View, process_stage 
            FROM manufacturing_process_step 
            WHERE manufacturing_process_stages_id='".$stage_id."' 
            ORDER BY id ASC";
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $steps = [];
    while ($row = $result->fetch_assoc()) {
        $steps[] = [
            'id' => $row['id'],
            'step' => $row['step'],
            'Step' => $row['step'],
            'View' => $row['View'] ?? 'false',
            'process_stage' => $row['process_stage'] ?? ''
        ];
    }
    
    json_success("Steps retrieved", $steps);
}

// ============================================================================
// GET SUBSTEPS
// ============================================================================
else if ($action == "get_substeps") {
    $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
    
    if (empty($step_id)) {
        json_error($conn, "Step ID is required");
    }
    
    $sql = "SELECT id, substep_name as Substep, substep_name as substep 
            FROM manufacturing_process_step_substep 
            WHERE manufacturing_process_step_id='".$step_id."' 
            ORDER BY id ASC";
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $substeps = [];
    while ($row = $result->fetch_assoc()) {
        $substeps[] = [
            'id' => $row['id'],
            'Substep' => $row['Substep'],
            'substep' => $row['substep']
        ];
    }
    
    json_success("Substeps retrieved", $substeps);
}

// ============================================================================
// INVALID ACTION
// ============================================================================
else {
    json_error($conn, "Invalid action: ".$action);
}
