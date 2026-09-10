<?php
/**
 * BMR Steps & Substeps API
 * Clean, dedicated API for step and substep configuration operations
 * 
 * Endpoints:
 * - GET_step_config: Get step configuration (without substep_id)
 * - GET_substep_config: Get substep configuration (with substep_id)
 * - SAVE_step_data: Save step data (procedure, equipment, etc.)
 * - SAVE_substep_data: Save substep data
 * - GET_steps_list: Get list of steps for a stage
 * - GET_substeps_list: Get list of substeps for a step
 */

// ini_set('display_errors', 1);
// error_reporting(E_ALL);
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

// Helper function to safely escape strings for SQL
function safe_escape($conn, $value) {
    if (is_null($value)) {
        return 'NULL';
    }
    return mysqli_real_escape_string($conn, $value);
}

// Helper function to safely escape and encode JSON
function safe_json_encode($conn, $data) {
    return mysqli_real_escape_string($conn, json_encode($data));
}

// Helper function to safely decode JSON (handles null/empty values)
function safe_json_decode($value, $default = null) {
    if ($value === null || $value === '' || $value === 'null') {
        return $default !== null ? $default : array();
    }
    $decoded = json_decode($value);
    return ($decoded === null && json_last_error() !== JSON_ERROR_NONE) ? ($default !== null ? $default : array()) : $decoded;
}

// Helper function to build WHERE clause for step/substep filtering
// Uses table alias 'a' to avoid ambiguity when joining with bmr_stages
function build_where_clause($conn, $stage_id, $step_id, $substep_id = null) {
    $stage_id_escaped = safe_escape($conn, $stage_id);
    $step_id_escaped = safe_escape($conn, $step_id);
    $where = "a.stage='".$stage_id_escaped."' AND a.step_id='".$step_id_escaped."'";
    
    if ($substep_id !== null && $substep_id != '') {
        $substep_id_escaped = safe_escape($conn, $substep_id);
        $where .= " AND a.substep_id='".$substep_id_escaped."'";
    } else {
        $where .= " AND (a.substep_id IS NULL OR a.substep_id='' OR a.substep_id='0')";
    }
    
    return $where;
}

// Helper function to return JSON error response
function json_error($conn, $message, $sql = '') {
    $response = array(
        "status" => "error",
        "msg" => $message,
        "sql" => substr($sql, 0, 500)
    );
    if ($conn->error) {
        $response["db_error"] = $conn->error;
    }
    echo json_encode($response);
    exit;
}

// Helper function to return JSON success response
function json_success($message, $data = null) {
    $response = array("status" => "success", "msg" => $message);
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
    exit;
}

// Token validation
$token = isset($_GET["token"]) ? $_GET["token"] : "";
$currentUrl = isset($_GET["description"]) ? $_GET["description"] : "";
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
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

// Log the request
$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token_escaped."','".$type_escaped."','".$entry_date."','".$department_escaped."','".$emp_id_escaped."','".$method_escaped."','".$remote_addr_escaped."','".$currentUrl_escaped."')";
$conn->query($sql);

$action = isset($_GET["type"]) ? $_GET["type"] : "";

// ============================================================================
// GET STEP CONFIGURATION (without substep)
// ============================================================================
if ($action == "GET_step_config") {
    $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
    $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
    $work_order_no = isset($_GET["work_order_no"]) ? safe_escape($conn, $_GET["work_order_no"]) : "";
    
    if (empty($stage_id) || empty($step_id)) {
        json_error($conn, "Stage ID and Step ID are required");
    }
    
    $where_clause = build_where_clause($conn, $stage_id, $step_id, null);
    
    if ($work_order_no != '') {
        $sql = "SELECT a.*,
            (SELECT checking_in FROM manufacturing_process_step m WHERE b.step_id=m.id) AS checking_in,
            b.saved_by AS bmr_saved_by, b.id AS bmr_stages_id,
            b.table_status AS bmr_table_status, b.procedure_status AS bmr_procedure_status,
            b.LineClearance_status AS bmr_LineClearance_status, b.equipment_status AS bmr_equipment_status,
            b.CleaningChecks_status AS bmr_CleaningChecks_status, b.room_status AS bmr_room_status,
            b.roomActions_status AS bmr_roomActions_status, b.weighing_status AS bmr_weighing_status,
            b.procedure AS bmr_pocedure, b.LineClearance AS bmr_LineClearance, b.equipment AS bmr_equipment,
            b.CleaningChecks AS bmr_CleaningChecks, b.room AS bmr_room, b.weighing AS bmr_weighing,
            b.tables AS bmr_table, b.roomActions AS bmr_roomActions,
            b.procedure_entry_by, b.procedure_approved_by, b.equipment_entry_by, b.equipment_approved_by,
            b.CleaningChecks_entry_by, b.CleaningChecks_approved_by, b.room_entry_by, b.room_approved_by,
            b.roomActions_entry_by, b.roomActions_approved_by, b.weighing_entry_by, b.weighing_approved_by,
            b.procedure_entry_date, b.procedure_approved_date, b.equipment_entry_date, b.equipment_approved_date,
            b.CleaningChecks_entry_date, b.CleaningChecks_approved_date, b.room_entry_date, b.room_approved_date,
            b.roomActions_entry_date, b.roomActions_approved_date, b.weighing_entry_date, b.weighing_approved_date
            FROM stages a 
            LEFT JOIN bmr_stages b ON a.stage=b.stage AND a.step_id=b.step_id AND a.id=b.stages_id 
                AND (COALESCE(a.substep_id, '') = COALESCE(b.substep_id, ''))
            WHERE ".$where_clause." AND (b.work_order_no IS NULL OR b.work_order_no LIKE '%".$work_order_no."%')";
    } else {
        $sql = "SELECT a.*,
            b.id AS bmr_stages_id, b.table_status AS bmr_table_status, b.procedure_status AS bmr_procedure_status,
            b.LineClearance_status AS bmr_LineClearance_status, b.equipment_status AS bmr_equipment_status,
            b.CleaningChecks_status AS bmr_CleaningChecks_status, b.room_status AS bmr_room_status,
            b.roomActions_status AS bmr_roomActions_status, b.weighing_status AS bmr_weighing_status,
            b.procedure AS bmr_pocedure, b.LineClearance AS bmr_LineClearance, b.equipment AS bmr_equipment,
            b.CleaningChecks AS bmr_CleaningChecks, b.room AS bmr_room, b.weighing AS bmr_weighing,
            b.tables AS bmr_table, b.roomActions AS bmr_roomActions,
            b.procedure_entry_by, b.procedure_approved_by, b.equipment_entry_by, b.equipment_approved_by,
            b.CleaningChecks_entry_by, b.CleaningChecks_approved_by, b.room_entry_by, b.room_approved_by,
            b.roomActions_entry_by, b.roomActions_approved_by, b.weighing_entry_by, b.weighing_approved_by,
            b.procedure_entry_date, b.procedure_approved_date, b.equipment_entry_date, b.equipment_approved_date,
            b.CleaningChecks_entry_date, b.CleaningChecks_approved_date, b.room_entry_date, b.room_approved_date,
            b.roomActions_entry_date, b.roomActions_approved_date, b.weighing_entry_date, b.weighing_approved_date,
            b.LineClearance_entry_by, b.LineClearance_entry_date, b.LineClearance_approved_by, b.LineClearance_approved_date
            FROM stages a 
            LEFT JOIN bmr_stages b ON a.stage=b.stage AND a.step_id=b.step_id AND a.id=b.stages_id 
                AND (COALESCE(a.substep_id, '') = COALESCE(b.substep_id, ''))
            WHERE ".$where_clause;
    }
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $output = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields safely (handle null/empty values)
            $row["procedures"] = safe_json_decode(isset($row["procedure"]) ? $row["procedure"] : null);
            $row["bmr_pocedure"] = safe_json_decode(isset($row["bmr_pocedure"]) ? $row["bmr_pocedure"] : null);
            $row["equipment"] = safe_json_decode(isset($row["equipment"]) ? $row["equipment"] : null);
            $row["bmr_equipment"] = safe_json_decode(isset($row["bmr_equipment"]) ? $row["bmr_equipment"] : null);
            $row["CleaningChecks"] = safe_json_decode(isset($row["CleaningChecks"]) ? $row["CleaningChecks"] : null);
            $row["bmr_CleaningChecks"] = safe_json_decode(isset($row["bmr_CleaningChecks"]) ? $row["bmr_CleaningChecks"] : null);
            $row["room"] = safe_json_decode(isset($row["room"]) ? $row["room"] : null);
            $row["bmr_room"] = safe_json_decode(isset($row["bmr_room"]) ? $row["bmr_room"] : null);
            $row["weighing"] = safe_json_decode(isset($row["weighing"]) ? $row["weighing"] : null);
            $row["bmr_weighing"] = safe_json_decode(isset($row["bmr_weighing"]) ? $row["bmr_weighing"] : null);
            $row["table"] = isset($row["table"]) ? $row["table"] : null;
            $row["bmr_table"] = isset($row["bmr_table"]) ? $row["bmr_table"] : null;
            $row["roomActions"] = safe_json_decode(isset($row["roomActions"]) ? $row["roomActions"] : null);
            $row["bmr_roomActions"] = safe_json_decode(isset($row["bmr_roomActions"]) ? $row["bmr_roomActions"] : null);
            $row["instructions"] = safe_json_decode(isset($row["instructions"]) ? $row["instructions"] : null);
            $row["initial_checks"] = safe_json_decode(isset($row["initial_checks"]) ? $row["initial_checks"] : null);
            $row["environments"] = safe_json_decode(isset($row["environments"]) ? $row["environments"] : null);
            $row["inprocess"] = safe_json_decode(isset($row["inprocess"]) ? $row["inprocess"] : null);
            $row["EquipmemntCleaning"] = safe_json_decode(isset($row["EquipmemntCleaning"]) ? $row["EquipmemntCleaning"] : null);
            $row["QcSample"] = safe_json_decode(isset($row["QcSample"]) ? $row["QcSample"] : null);
            $row["Logbook"] = safe_json_decode(isset($row["Logbook"]) ? $row["Logbook"] : null);
            $row["LineClearance"] = safe_json_decode(isset($row["LineClearance"]) ? $row["LineClearance"] : null);
            $row["bmr_LineClearance"] = safe_json_decode(isset($row["bmr_LineClearance"]) ? $row["bmr_LineClearance"] : null);
            
            // Decode sequence and forms_list fields
            $row["sequence"] = safe_json_decode(isset($row["sequence"]) ? $row["sequence"] : null);
            $row["forms_list"] = safe_json_decode(isset($row["forms_list"]) ? $row["forms_list"] : null);
            
            // Process Logbook forms
            $array = is_array($row['Logbook']) ? $row['Logbook'] : array();
            $logbook_length = count($array);
            $output1 = array();
            for ($i = 0; $i < $logbook_length; $i++) {
                $values = is_object($array[$i]) ? $array[$i] : (is_array($array[$i]) ? (object)$array[$i] : null);
                if (!$values) continue;
                $form_no = isset($values->form_no) ? safe_escape($conn, $values->form_no) : "";
                $stage_escaped = isset($row["stage"]) ? safe_escape($conn, $row["stage"]) : "";
                $step_id_escaped = isset($row["step_id"]) ? safe_escape($conn, $row["step_id"]) : "";
                
                $sql1 = "SELECT * FROM form_master WHERE form_no='".$form_no."' AND plant_id='".safe_escape($conn, $_GET["plant_id"])."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
            }
            $row["form_no_list"] = $output1;
            
            $output[] = $row;
        }
    }
    
    echo json_encode($output);
    exit;
}

// ============================================================================
// GET SUBSTEP CONFIGURATION (with substep_id)
// ============================================================================
else if ($action == "GET_substep_config") {
    $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
    $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
    $substep_id = isset($_GET["substep_id"]) ? safe_escape($conn, $_GET["substep_id"]) : "";
    $work_order_no = isset($_GET["work_order_no"]) ? safe_escape($conn, $_GET["work_order_no"]) : "";
    
    if (empty($stage_id) || empty($step_id) || empty($substep_id)) {
        json_error($conn, "Stage ID, Step ID, and Substep ID are required");
    }
    
    $where_clause = build_where_clause($conn, $stage_id, $step_id, $substep_id);
    
    // Get substep name
    $substep_name = "";
    $sql_substep = "SELECT substep_name FROM manufacturing_process_substep WHERE id='".$substep_id."'";
    $result_substep = $conn->query($sql_substep);
    if ($result_substep && $result_substep->num_rows > 0) {
        $row_substep = $result_substep->fetch_assoc();
        $substep_name = $row_substep['substep_name'];
    }
    
    if ($work_order_no != '') {
        $sql = "SELECT a.*,
            (SELECT checking_in FROM manufacturing_process_step m WHERE b.step_id=m.id) AS checking_in,
            '".$substep_name."' AS substep_name,
            b.saved_by AS bmr_saved_by, b.id AS bmr_stages_id,
            b.table_status AS bmr_table_status, b.procedure_status AS bmr_procedure_status,
            b.LineClearance_status AS bmr_LineClearance_status, b.equipment_status AS bmr_equipment_status,
            b.CleaningChecks_status AS bmr_CleaningChecks_status, b.room_status AS bmr_room_status,
            b.roomActions_status AS bmr_roomActions_status, b.weighing_status AS bmr_weighing_status,
            b.procedure AS bmr_pocedure, b.LineClearance AS bmr_LineClearance, b.equipment AS bmr_equipment,
            b.CleaningChecks AS bmr_CleaningChecks, b.room AS bmr_room, b.weighing AS bmr_weighing,
            b.tables AS bmr_table, b.roomActions AS bmr_roomActions,
            b.procedure_entry_by, b.procedure_approved_by, b.equipment_entry_by, b.equipment_approved_by,
            b.CleaningChecks_entry_by, b.CleaningChecks_approved_by, b.room_entry_by, b.room_approved_by,
            b.roomActions_entry_by, b.roomActions_approved_by, b.weighing_entry_by, b.weighing_approved_by,
            b.procedure_entry_date, b.procedure_approved_date, b.equipment_entry_date, b.equipment_approved_date,
            b.CleaningChecks_entry_date, b.CleaningChecks_approved_date, b.room_entry_date, b.room_approved_date,
            b.roomActions_entry_date, b.roomActions_approved_date, b.weighing_entry_date, b.weighing_approved_date
            FROM stages a 
            LEFT JOIN bmr_stages b ON a.stage=b.stage AND a.step_id=b.step_id AND a.id=b.stages_id 
                AND (COALESCE(a.substep_id, '') = COALESCE(b.substep_id, ''))
            WHERE ".$where_clause." AND (b.work_order_no IS NULL OR b.work_order_no LIKE '%".$work_order_no."%')";
    } else {
        $sql = "SELECT a.*,
            '".$substep_name."' AS substep_name,
            b.id AS bmr_stages_id, b.table_status AS bmr_table_status, b.procedure_status AS bmr_procedure_status,
            b.LineClearance_status AS bmr_LineClearance_status, b.equipment_status AS bmr_equipment_status,
            b.CleaningChecks_status AS bmr_CleaningChecks_status, b.room_status AS bmr_room_status,
            b.roomActions_status AS bmr_roomActions_status, b.weighing_status AS bmr_weighing_status,
            b.procedure AS bmr_pocedure, b.LineClearance AS bmr_LineClearance, b.equipment AS bmr_equipment,
            b.CleaningChecks AS bmr_CleaningChecks, b.room AS bmr_room, b.weighing AS bmr_weighing,
            b.tables AS bmr_table, b.roomActions AS bmr_roomActions,
            b.procedure_entry_by, b.procedure_approved_by, b.equipment_entry_by, b.equipment_approved_by,
            b.CleaningChecks_entry_by, b.CleaningChecks_approved_by, b.room_entry_by, b.room_approved_by,
            b.roomActions_entry_by, b.roomActions_approved_by, b.weighing_entry_by, b.weighing_approved_by,
            b.procedure_entry_date, b.procedure_approved_date, b.equipment_entry_date, b.equipment_approved_date,
            b.CleaningChecks_entry_date, b.CleaningChecks_approved_date, b.room_entry_date, b.room_approved_date,
            b.roomActions_entry_date, b.roomActions_approved_date, b.weighing_entry_date, b.weighing_approved_date,
            b.LineClearance_entry_by, b.LineClearance_entry_date, b.LineClearance_approved_by, b.LineClearance_approved_date
            FROM stages a 
            LEFT JOIN bmr_stages b ON a.stage=b.stage AND a.step_id=b.step_id AND a.id=b.stages_id 
                AND (COALESCE(a.substep_id, '') = COALESCE(b.substep_id, ''))
            WHERE ".$where_clause;
    }
    
    $result = $conn->query($sql);
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $output = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields safely (handle null/empty values)
            $row["procedures"] = safe_json_decode(isset($row["procedure"]) ? $row["procedure"] : null);
            $row["bmr_pocedure"] = safe_json_decode(isset($row["bmr_pocedure"]) ? $row["bmr_pocedure"] : null);
            $row["equipment"] = safe_json_decode(isset($row["equipment"]) ? $row["equipment"] : null);
            $row["bmr_equipment"] = safe_json_decode(isset($row["bmr_equipment"]) ? $row["bmr_equipment"] : null);
            $row["CleaningChecks"] = safe_json_decode(isset($row["CleaningChecks"]) ? $row["CleaningChecks"] : null);
            $row["bmr_CleaningChecks"] = safe_json_decode(isset($row["bmr_CleaningChecks"]) ? $row["bmr_CleaningChecks"] : null);
            $row["room"] = safe_json_decode(isset($row["room"]) ? $row["room"] : null);
            $row["bmr_room"] = safe_json_decode(isset($row["bmr_room"]) ? $row["bmr_room"] : null);
            $row["weighing"] = safe_json_decode(isset($row["weighing"]) ? $row["weighing"] : null);
            $row["bmr_weighing"] = safe_json_decode(isset($row["bmr_weighing"]) ? $row["bmr_weighing"] : null);
            $row["table"] = isset($row["table"]) ? $row["table"] : null;
            $row["bmr_table"] = isset($row["bmr_table"]) ? $row["bmr_table"] : null;
            $row["roomActions"] = safe_json_decode(isset($row["roomActions"]) ? $row["roomActions"] : null);
            $row["bmr_roomActions"] = safe_json_decode(isset($row["bmr_roomActions"]) ? $row["bmr_roomActions"] : null);
            $row["instructions"] = safe_json_decode(isset($row["instructions"]) ? $row["instructions"] : null);
            $row["initial_checks"] = safe_json_decode(isset($row["initial_checks"]) ? $row["initial_checks"] : null);
            $row["environments"] = safe_json_decode(isset($row["environments"]) ? $row["environments"] : null);
            $row["inprocess"] = safe_json_decode(isset($row["inprocess"]) ? $row["inprocess"] : null);
            $row["EquipmemntCleaning"] = safe_json_decode(isset($row["EquipmemntCleaning"]) ? $row["EquipmemntCleaning"] : null);
            $row["QcSample"] = safe_json_decode(isset($row["QcSample"]) ? $row["QcSample"] : null);
            $row["Logbook"] = safe_json_decode(isset($row["Logbook"]) ? $row["Logbook"] : null);
            $row["LineClearance"] = safe_json_decode(isset($row["LineClearance"]) ? $row["LineClearance"] : null);
            $row["bmr_LineClearance"] = safe_json_decode(isset($row["bmr_LineClearance"]) ? $row["bmr_LineClearance"] : null);
            
            // Decode sequence and forms_list fields
            $row["sequence"] = safe_json_decode(isset($row["sequence"]) ? $row["sequence"] : null);
            $row["forms_list"] = safe_json_decode(isset($row["forms_list"]) ? $row["forms_list"] : null);
            
            // Process Logbook forms
            $array = is_array($row['Logbook']) ? $row['Logbook'] : array();
            $logbook_length = count($array);
            $output1 = array();
            for ($i = 0; $i < $logbook_length; $i++) {
                $values = is_object($array[$i]) ? $array[$i] : (is_array($array[$i]) ? (object)$array[$i] : null);
                if (!$values) continue;
                $form_no = isset($values->form_no) ? safe_escape($conn, $values->form_no) : "";
                $stage_escaped = isset($row["stage"]) ? safe_escape($conn, $row["stage"]) : "";
                $step_id_escaped = isset($row["step_id"]) ? safe_escape($conn, $row["step_id"]) : "";
                
                $sql1 = "SELECT * FROM form_master WHERE form_no='".$form_no."' AND plant_id='".safe_escape($conn, $_GET["plant_id"])."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
            }
            $row["form_no_list"] = $output1;
            
            $output[] = $row;
        }
    } else {
        // If no data found for substep, return empty structure matching step configuration
        // This ensures frontend always receives consistent structure
        $output[] = array(
            "procedures" => array(),
            "LineClearance" => array(),
            "Logbook" => array(),
            "room" => array(),
            "equipment" => array(),
            "weighing" => array(),
            "CleaningChecks" => array(),
            "roomActions" => array(),
            "EquipmemntCleaning" => array(),
            "tables" => null,
            "QcSample" => array(),
            "Formats" => array(),
            "QaReview" => array(),
            "sequence" => array(),
            "forms_list" => array(),
            "form_no_list" => array(),
            "isprocedure" => "",
            "isroom" => "",
            "isequipment" => "",
            "isCleaningChecks" => "",
            "isroomActions" => "",
            "isEquipmemntCleaning" => "",
            "istable" => "",
            "isFraction" => "",
            "isInprocessChecks" => "",
            "ismillinsifting" => "",
            "isDispensing" => "",
            "isMixing" => "",
            "isSiftLubrication" => "",
            "isDry_SIFTING_MILLING" => "",
            "isLineClearance" => "",
            "isSieveInteggity" => "",
            "isYield" => "",
            "isweighing" => "",
            "isDrying" => "",
            "isBlendLubrication" => "",
            "isWeighing_Variation_Recoed" => "",
            "isCOMPRESSION_PARAMETERS" => "",
            "isINPROCESS_YIELD" => "",
            "isYIELD_RECONCILIATION" => "",
            "isQcSample" => "",
            "isLogbook" => "",
            "isFormats" => "",
            "isQaReview" => ""
        );
    }
    
    echo json_encode($output);
    exit;
}

// ============================================================================
// SAVE STEP/SUBSTEP DATA (Generic save function)
// ============================================================================
else if ($action == "SAVE_step_data" || $action == "SAVE_substep_data") {
    $stage_id = isset($input["stage_id"]) ? $input["stage_id"] : "";
    $step_id = isset($input["step_id"]) ? $input["step_id"] : "";
    $substep_id = isset($input["substep_id"]) ? ($input["substep_id"] ? $input["substep_id"] : null) : null;
    $field_name = isset($input["field_name"]) ? $input["field_name"] : "";
    $field_value = isset($input["field_value"]) ? $input["field_value"] : null;
    $save_type = isset($input["type"]) ? $input["type"] : "save"; // save, final, conf
    $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
    
    if (empty($stage_id) || empty($step_id) || empty($field_name)) {
        json_error($conn, "Stage ID, Step ID, and Field Name are required");
    }
    
    $where_clause = build_where_clause($conn, $stage_id, $step_id, $substep_id);
    
    // Map field names to database columns
    $field_mapping = array(
        'procedure' => 'procedure',
        'equipment' => 'equipment',
        'CleaningChecks' => 'CleaningChecks',
        'room' => 'room',
        'weighing' => 'weighing',
        'table' => 'table',
        'roomActions' => 'roomActions',
        'LineClearance' => 'LineClearance',
        'Weighing_Variation_Recoed' => 'Weighing_Variation_Recoed',
        'Dispensing' => 'Dispensing',
        'Mixing' => 'Mixing',
        'SiftLubrication' => 'SiftLubrication',
        'Dry_SIFTING_MILLING' => 'Dry_SIFTING_MILLING',
        'BlendLubrication' => 'BlendLubrication',
        'Drying' => 'Drying',
        'COMPRESSION_PARAMETERS' => 'COMPRESSION_PARAMETERS',
        'INPROCESS_YIELD' => 'INPROCESS_YIELD',
        'YIELD_RECONCILIATION' => 'YIELD_RECONCILIATION',
        'QcSample' => 'QcSample',
        'Formats' => 'Formats',
        'QaReview' => 'QaReview',
        'Logbook' => 'Logbook'
    );
    
    if (!isset($field_mapping[$field_name])) {
        json_error($conn, "Invalid field name: ".$field_name);
    }
    
    $db_field = $field_mapping[$field_name];
    $is_field = 'is'.$field_name;
    
    // For UPDATE queries, we need to remove table alias 'a.' from WHERE clause
    $where_clause_update = str_replace('a.', '', $where_clause);
    
    if ($save_type == 'save') {
        $field_json = safe_json_encode($conn, $field_value);
        $sql = "UPDATE stages SET `".$db_field."`='".$field_json."' WHERE ".$where_clause_update;
    } else if ($save_type == 'final') {
        $sql = "UPDATE stages SET ".$is_field."='1.5' WHERE ".$where_clause_update;
    } else if ($save_type == 'conf') {
        $confirm_field = $field_name."_confirm_by";
        $confirm_date_field = $field_name."_confirm_on";
        $sql = "UPDATE stages SET ".$is_field."='2', ".$confirm_field."='".$emp_id."', ".$confirm_date_field."='".$entry_date."' WHERE ".$where_clause_update;
    } else {
        json_error($conn, "Invalid save type: ".$save_type);
    }
    
    if ($conn->query($sql)) {
        json_success("Record updated successfully!");
    } else {
        json_error($conn, "Failed to update record", $sql);
    }
}

// ============================================================================
// GET STEPS LIST FOR A STAGE
// ============================================================================
else if ($action == "GET_steps_list") {
    $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
    
    if (empty($stage_id)) {
        json_error($conn, "Stage ID is required");
    }
    
    $sql = "SELECT * FROM manufacturing_process_step WHERE manufacturing_process_stages_id='".$stage_id."' ORDER BY id ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $output = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get substeps for this step
            $step_id = $row['id'];
            $sql_substeps = "SELECT * FROM manufacturing_process_step_substep WHERE manufacturing_process_step_id='".$step_id."' ORDER BY id ASC";
            $result_substeps = $conn->query($sql_substeps);
            
            $substeps = array();
            if ($result_substeps && $result_substeps->num_rows > 0) {
                while ($row_substep = $result_substeps->fetch_assoc()) {
                    $substeps[] = $row_substep;
                }
            }
            $row['Substeps'] = $substeps;
            
            $output[] = $row;
        }
    }
    
    echo json_encode($output);
    exit;
}

// ============================================================================
// GET SUBSTEPS LIST FOR A STEP
// ============================================================================
else if ($action == "GET_substeps_list") {
    $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
    
    if (empty($step_id)) {
        json_error($conn, "Step ID is required");
    }
    
    $sql = "SELECT * FROM manufacturing_process_step_substep WHERE manufacturing_process_step_id='".$step_id."' ORDER BY id ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        json_error($conn, "Query failed", $sql);
    }
    
    $output = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    
    echo json_encode($output);
    exit;
}

// ============================================================================
// SAVE SEQUENCE (for steps or substeps)
// ============================================================================
else if ($action == "SAVE_sequence") {
    $stage_id = isset($input["stage_id"]) ? $input["stage_id"] : "";
    $step_id = isset($input["step_id"]) ? $input["step_id"] : "";
    $substep_id = isset($input["substep_id"]) ? ($input["substep_id"] ? $input["substep_id"] : null) : null;
    $sequence = isset($input["sequence"]) ? $input["sequence"] : array();
    $sequence_type = isset($input["sequence_type"]) ? $input["sequence_type"] : "sequence"; // sequence or forms_list
    
    if (empty($stage_id) || empty($step_id)) {
        json_error($conn, "Stage ID and Step ID are required");
    }
    
    $where_clause = build_where_clause($conn, $stage_id, $step_id, $substep_id);
    $sequence_json = safe_json_encode($conn, $sequence);
    
    // For UPDATE queries, we need to remove table alias 'a.' from WHERE clause
    $where_clause_update = str_replace('a.', '', $where_clause);
    
    $field_name = ($sequence_type == "forms_list") ? "forms_list" : "sequence";
    $sql = "UPDATE stages SET `".$field_name."`='".$sequence_json."' WHERE ".$where_clause_update;
    
    if ($conn->query($sql)) {
        json_success("Sequence saved successfully!");
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
