<?php
/**
 * BMR Configuration API - Clean RESTful API
 * Handles all BMR step/substep configuration operations
 * 
 * Endpoints:
 * GET  /bmr_config_api.php?type=get_config&stage_id=X&step_id=Y&substep_id=Z
 * POST /bmr_config_api.php?type=save_config
 * GET  /bmr_config_api.php?type=get_sequence&stage_id=X&step_id=Y&substep_id=Z
 * POST /bmr_config_api.php?type=save_sequence
 * POST /bmr_config_api.php?type=update_field
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

function safe_json_encode($conn, $data) {
    return mysqli_real_escape_string($conn, json_encode($data));
}

function safe_json_decode($value, $default = null) {
    if ($value === null || $value === '' || $value === 'null') {
        return $default !== null ? $default : [];
    }
    $decoded = json_decode($value, true);
    return ($decoded === null && json_last_error() !== JSON_ERROR_NONE) ? ($default !== null ? $default : []) : $decoded;
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
// GET CONFIGURATION
// ============================================================================
if ($action == "get_config") {
    $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
    $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
    $substep_id = isset($_GET["substep_id"]) ? safe_escape($conn, $_GET["substep_id"]) : "";
    $work_order_no = isset($_GET["work_order_no"]) ? safe_escape($conn, $_GET["work_order_no"]) : "";
    
    if (empty($stage_id) || empty($step_id) || empty($plant_id)) {
        json_error($conn, "Stage ID, Step ID, and Plant ID are required");
    }
    
    $substep_condition = ($substep_id && $substep_id != '') 
        ? "AND substep_id='".safe_escape($conn, $substep_id)."'" 
        : "AND (substep_id IS NULL OR substep_id='' OR substep_id='0')";
    
    $work_order_condition = ($work_order_no && $work_order_no != '') 
        ? "AND (work_order_no='".safe_escape($conn, $work_order_no)."' OR work_order_no IS NULL)" 
        : "";
    
    $sql = "SELECT * FROM bmr_step_configurations 
            WHERE stage_id='".$stage_id."' 
            AND step_id='".$step_id."' 
            ".$substep_condition."
            ".$work_order_condition."
            AND plant_id='".$plant_id."'
            LIMIT 1";
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Decode JSON fields
        $json_fields = ['procedure', 'room', 'equipment', 'CleaningChecks', 'roomActions', 
                        'EquipmemntCleaning', 'weighing', 'LineClearance', 'QcSample', 
                        'Logbook', 'Formats', 'QaReview', 'instructions', 'initial_checks', 
                        'environments', 'inprocess'];
        
        foreach ($json_fields as $field) {
            if (isset($row[$field])) {
                $row[$field] = safe_json_decode($row[$field]);
            }
        }
        
        json_success("Configuration retrieved", $row);
    } else {
        // Return empty configuration structure
        $empty_config = [
            'stage_id' => $stage_id,
            'step_id' => $step_id,
            'substep_id' => $substep_id ?: null,
            'work_order_no' => $work_order_no ?: null,
            'plant_id' => $plant_id
        ];
        json_success("No configuration found", $empty_config);
    }
}

// ============================================================================
// SAVE CONFIGURATION
// ============================================================================
else if ($action == "save_config") {
    $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
    $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
    $substep_id = isset($input["substep_id"]) ? ($input["substep_id"] ? safe_escape($conn, $input["substep_id"]) : null) : null;
    $work_order_no = isset($input["work_order_no"]) ? safe_escape($conn, $input["work_order_no"]) : null;
    
    if (empty($stage_id) || empty($step_id) || empty($plant_id)) {
        json_error($conn, "Stage ID, Step ID, and Plant ID are required");
    }
    
    // Check if configuration exists
    $substep_condition = ($substep_id && $substep_id != '') 
        ? "AND substep_id='".$substep_id."'" 
        : "AND (substep_id IS NULL OR substep_id='' OR substep_id='0')";
    
    $work_order_condition = ($work_order_no && $work_order_no != '') 
        ? "AND work_order_no='".$work_order_no."'" 
        : "AND (work_order_no IS NULL OR work_order_no='')";
    
    $check_sql = "SELECT id FROM bmr_step_configurations 
                  WHERE stage_id='".$stage_id."' 
                  AND step_id='".$step_id."' 
                  ".$substep_condition."
                  ".$work_order_condition."
                  AND plant_id='".$plant_id."'";
    
    $check_result = $conn->query($check_sql);
    $exists = $check_result && $check_result->num_rows > 0;
    
    // Prepare update/insert data
    $fields = [];
    $values = [];
    
    // Handle all configuration fields
    $config_fields = [
        'isprocedure', 'isroom', 'isequipment', 'isCleaningChecks', 'ismillinsifting',
        'isDispensing', 'isMixing', 'isDry_SIFTING_MILLING', 'isSiftLubrication',
        'isYIELD_RECONCILIATION', 'isINPROCESS_YIELD', 'isDrying', 'isBlendLubrication',
        'isCOMPRESSION_PARAMETERS', 'isWeighing_Variation_Recoed', 'isroomActions',
        'isSieveInteggity', 'isweighing', 'isQcSample', 'isYield', 'isLineClearance',
        'isLogbook', 'isFormats', 'isQaReview'
    ];
    
    $json_fields = [
        'procedure', 'room', 'equipment', 'CleaningChecks', 'roomActions',
        'EquipmemntCleaning', 'weighing', 'LineClearance', 'QcSample',
        'Logbook', 'Formats', 'QaReview', 'instructions', 'initial_checks',
        'environments', 'inprocess'
    ];
    
    foreach ($config_fields as $field) {
        if (isset($input[$field])) {
            $fields[] = "`".$field."`";
            $values[] = "'".safe_escape($conn, $input[$field])."'";
        }
    }
    
    foreach ($json_fields as $field) {
        if (isset($input[$field])) {
            $fields[] = "`".$field."`";
            $json_value = is_array($input[$field]) || is_object($input[$field]) 
                ? safe_json_encode($conn, $input[$field]) 
                : "'".safe_escape($conn, $input[$field])."'";
            $values[] = $json_value;
        }
    }
    
    if ($exists) {
        // UPDATE
        $row = $check_result->fetch_assoc();
        $config_id = $row['id'];
        
        $update_fields = [];
        for ($i = 0; $i < count($fields); $i++) {
            $update_fields[] = $fields[$i]."=".$values[$i];
        }
        $update_fields[] = "`updated_at`='".$entry_date."'";
        $update_fields[] = "`updated_by`='".$emp_id_escaped."'";
        
        $sql = "UPDATE bmr_step_configurations SET ".implode(", ", $update_fields)." WHERE id='".$config_id."'";
    } else {
        // INSERT
        $fields[] = "`stage_id`";
        $fields[] = "`step_id`";
        $fields[] = "`substep_id`";
        $fields[] = "`work_order_no`";
        $fields[] = "`plant_id`";
        $fields[] = "`created_at`";
        $fields[] = "`created_by`";
        
        $values[] = "'".$stage_id."'";
        $values[] = "'".$step_id."'";
        $values[] = ($substep_id ? "'".$substep_id."'" : "NULL");
        $values[] = ($work_order_no ? "'".$work_order_no."'" : "NULL");
        $values[] = "'".$plant_id."'";
        $values[] = "'".$entry_date."'";
        $values[] = "'".$emp_id_escaped."'";
        
        $sql = "INSERT INTO bmr_step_configurations (".implode(", ", $fields).") VALUES (".implode(", ", $values).")";
    }
    
    if ($conn->query($sql)) {
        json_success("Configuration saved successfully");
    } else {
        json_error($conn, "Failed to save configuration", $sql);
    }
}

// ============================================================================
// GET SEQUENCE
// ============================================================================
else if ($action == "get_sequence") {
    $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
    $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
    $substep_id = isset($_GET["substep_id"]) ? safe_escape($conn, $_GET["substep_id"]) : "";
    $sequence_type = isset($_GET["sequence_type"]) ? safe_escape($conn, $_GET["sequence_type"]) : "priority";
    
    if (empty($stage_id) || empty($step_id) || empty($plant_id)) {
        json_error($conn, "Stage ID, Step ID, and Plant ID are required");
    }
    
    $substep_condition = ($substep_id && $substep_id != '') 
        ? "AND substep_id='".safe_escape($conn, $substep_id)."'" 
        : "AND (substep_id IS NULL OR substep_id='' OR substep_id='0')";
    
    $sql = "SELECT sequence_data FROM bmr_priority_sequences 
            WHERE stage_id='".$stage_id."' 
            AND step_id='".$step_id."' 
            ".$substep_condition."
            AND sequence_type='".$sequence_type."'
            AND plant_id='".$plant_id."'
            LIMIT 1";
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sequence = safe_json_decode($row['sequence_data'], []);
        json_success("Sequence retrieved", $sequence);
    } else {
        json_success("No sequence found", []);
    }
}

// ============================================================================
// SAVE SEQUENCE
// ============================================================================
else if ($action == "save_sequence") {
    $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
    $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
    $substep_id = isset($input["substep_id"]) ? ($input["substep_id"] ? safe_escape($conn, $input["substep_id"]) : null) : null;
    $sequence_data = isset($input["sequence_data"]) ? $input["sequence_data"] : [];
    $sequence_type = isset($input["sequence_type"]) ? safe_escape($conn, $input["sequence_type"]) : "priority";
    
    if (empty($stage_id) || empty($step_id) || empty($plant_id)) {
        json_error($conn, "Stage ID, Step ID, and Plant ID are required");
    }
    
    if (!is_array($sequence_data)) {
        json_error($conn, "sequence_data must be an array");
    }
    
    $sequence_json = safe_json_encode($conn, $sequence_data);
    
    // Check if sequence exists
    $substep_condition = ($substep_id && $substep_id != '') 
        ? "AND substep_id='".$substep_id."'" 
        : "AND (substep_id IS NULL OR substep_id='' OR substep_id='0')";
    
    $check_sql = "SELECT id FROM bmr_priority_sequences 
                  WHERE stage_id='".$stage_id."' 
                  AND step_id='".$step_id."' 
                  ".$substep_condition."
                  AND sequence_type='".$sequence_type."'
                  AND plant_id='".$plant_id."'";
    
    $check_result = $conn->query($check_sql);
    $exists = $check_result && $check_result->num_rows > 0;
    
    if ($exists) {
        // UPDATE
        $row = $check_result->fetch_assoc();
        $sql = "UPDATE bmr_priority_sequences 
                SET sequence_data='".$sequence_json."',
                    updated_at='".$entry_date."',
                    updated_by='".$emp_id_escaped."'
                WHERE id='".$row['id']."'";
    } else {
        // INSERT
        $sql = "INSERT INTO bmr_priority_sequences 
                (stage_id, step_id, substep_id, plant_id, sequence_type, sequence_data, created_at, created_by) 
                VALUES (
                    '".$stage_id."',
                    '".$step_id."',
                    ".($substep_id ? "'".$substep_id."'" : "NULL").",
                    '".$plant_id."',
                    '".$sequence_type."',
                    '".$sequence_json."',
                    '".$entry_date."',
                    '".$emp_id_escaped."'
                )";
    }
    
    if ($conn->query($sql)) {
        json_success("Sequence saved successfully");
    } else {
        json_error($conn, "Failed to save sequence", $sql);
    }
}

// ============================================================================
// INVALID ACTION
// ============================================================================
else {
    json_error($conn, "Invalid action: ".$action);
}
