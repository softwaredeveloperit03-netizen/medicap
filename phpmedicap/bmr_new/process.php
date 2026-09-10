<?php 
require '../db.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$output = Array();
$token = $_GET["token"];
$currentUrl = $_GET["description"] ?? '';

$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
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
   $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // Get Process Stages from productionstage module (process_types table) or legacy manufacturing_process
    if ($_GET["type"] == "getProcesses") {
        
        $output = array();

$sql = "SELECT * FROM manufacturing_process 
        WHERE plant_id = '" . $_GET["plant_id"] . "' 
        AND id = '" . $_GET['id'] . "' 
        ORDER BY id DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output2 = array();

        $sql2 = "SELECT * FROM manufacturing_process_stages 
                 WHERE manufacturing_process_id = '" . $row["id"] . "' 
                 AND plant_id = '" . $_GET["plant_id"] . "' 
                 ORDER BY id ASC";

        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $row2["forms_list"] = json_decode($row2["forms_list"]);
                $output3 = array();

                $sql3 = "SELECT * FROM manufacturing_process_step 
                         WHERE manufacturing_process_stages_id = '" . $row2["id"] . "' 
                         AND plant_id = '" . $_GET["plant_id"] . "'";

                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output4 = array();

                        $sql4 = "SELECT * FROM manufacturing_process_step_substep 
                                 WHERE manufacturing_process_step_id = '" . $row3["id"] . "' 
                                 AND plant_id = '" . $_GET["plant_id"] . "'";

                        $result4 = $conn->query($sql4);
                        if ($result4->num_rows > 0) {
                            while ($row4 = $result4->fetch_assoc()) {
                                $output4[] = $row4;
                            }
                        }

                        $row3["Substeps"] = $output4;
                        $output3[] = $row3;
                    }
                }

                $row2["steps"] = $output3;
                $output2[] = $row2;
            }
        }

        $row["Stages"] = $output2;
        $output[] = $row;
    }
}

echo json_encode($output);
    }
    // Get Process Types for dropdown/selection (from productionstage)
    else if ($_GET["type"] == "getProcessTypes") {
        $output = Array();
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $dosage_form = isset($_GET["dosage_form"]) ? mysqli_real_escape_string($conn, $_GET["dosage_form"]) : '';
        
        $sql = "SELECT id, process_type, dosage_form FROM process_types WHERE plant_id = '".$plant_id."'";
        
        if (!empty($dosage_form)) {
            $sql .= " AND dosage_form LIKE '%".$dosage_form."%'";
        }
        
        $sql .= " ORDER BY id DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = array(
                    'id' => $row['id'],
                    'process_type' => $row['process_type'],
                    'dosage_form' => $row['dosage_form'],
                    'manufacturing_process_id' => $row['id'] // Alias for compatibility
                );
            }
        }
        
        echo json_encode($output);
    }
    // Get Process Stages structure (for bmrdash to display stages/steps/substeps)
    else if ($_GET["type"] == "getProcessStages") {
        $output = Array();
        $process_type_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        
        if (empty($process_type_id) || empty($plant_id)) {
            echo json_encode(['error' => 'Process type ID and plant ID are required']);
            exit;
        }
        
        // Get process_type info
        $sql = "SELECT * FROM process_types WHERE id = '".$process_type_id."' AND plant_id = '".$plant_id."'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $process_row = $result->fetch_assoc();
            
            $output1 = Array();
            
            // Get stages
            $sql1 = "SELECT * FROM process_stages WHERE process_type_id = '".$process_type_id."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
            $result1 = $conn->query($sql1);
            
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output11 = Array();
                    
                    // Get steps
                    $sql11 = "SELECT * FROM process_stages_steps WHERE process_stages_id = '".$row1["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                    $result11 = $conn->query($sql11);
                    
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $output111 = Array();
                            
                            // Get substeps - process_stages_steps_id is INT, use numeric comparison
                            $step_id_int = intval($row11["id"]);
                            $sql111 = "SELECT * FROM process_stages_steps_substep WHERE process_stages_steps_id = ".$step_id_int." AND (TRIM(plant_id) = '".mysqli_real_escape_string($conn, trim($plant_id))."' OR plant_id = ".intval($plant_id).") ORDER BY id ASC";
                            $result111 = $conn->query($sql111);
                            
                            if ($result111 && $result111->num_rows > 0) {
                                while ($row111 = $result111->fetch_assoc()) {
                                    // Ensure all substep fields are included
                                    $substep_data = array(
                                        'id' => $row111['id'],
                                        'process_stages_id' => isset($row111['process_stages_id']) ? $row111['process_stages_id'] : '',
                                        'process_stages_steps_id' => isset($row111['process_stages_steps_id']) ? $row111['process_stages_steps_id'] : '',
                                        'plant_id' => isset($row111['plant_id']) ? $row111['plant_id'] : '',
                                        'Substep' => isset($row111['Substep']) ? $row111['Substep'] : '',
                                        'substep' => isset($row111['Substep']) ? $row111['Substep'] : (isset($row111['substep']) ? $row111['substep'] : '')
                                    );
                                    $output111[] = $substep_data;
                                }
                            }
                            
                            // Always include Substeps array, even if empty
                            $row11["Substeps"] = $output111;
                            $output11[] = $row11;
                        }
                    }
                    
                    $row1["steps"] = $output11;
                    $output1[] = $row1;
                }
            }
            
            $process_row["stages"] = $output1;
            $output[] = $process_row;
        }
        
        echo json_encode($output);
    }
    // Get Process Stages for View (with BMR data from stages table)
    else if ($_GET["type"] == "getProcesses_view") {
        $output = array();
        $process_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        
        if (empty($process_id) || empty($plant_id)) {
            echo json_encode(['error' => 'Process ID and plant ID are required']);
            exit;
        }
        
        // First try to get from process_types table (from productionstage)
        $sql = "SELECT * FROM process_types WHERE id = '".$process_id."' AND plant_id = '".$plant_id."'";
        $result = $conn->query($sql);
        $use_productionstage = ($result && $result->num_rows > 0);
        
        // If not found in process_types, try manufacturing_process (legacy)
        if (!$use_productionstage) {
            $sql = "SELECT * FROM manufacturing_process WHERE id = '".$process_id."' AND plant_id = '".$plant_id."' ORDER BY id DESC";
            $result = $conn->query($sql);
        }
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output2 = array();
                
                if ($use_productionstage) {
                    // Get stages from process_stages table (productionstage)
                    $sql2 = "SELECT * FROM process_stages WHERE process_type_id = '".$row["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                } else {
                    // Get stages from manufacturing_process_stages table (legacy)
                    $sql2 = "SELECT * FROM manufacturing_process_stages WHERE manufacturing_process_id = '".$row["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                }
                
                $result2 = $conn->query($sql2);
                
                if ($result2 && $result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output3 = array();
                        
                        if ($use_productionstage) {
                            // Get steps from process_stages_steps table (productionstage)
                            $sql3 = "SELECT * FROM process_stages_steps WHERE process_stages_id = '".$row2["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                        } else {
                            // Get steps from manufacturing_process_step table (legacy)
                            $sql3 = "SELECT * FROM manufacturing_process_step WHERE manufacturing_process_stages_id = '".$row2["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                        }
                        
                        $result3 = $conn->query($sql3);
                        
                        if ($result3 && $result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $output4 = array();
                                
                                // Get BMR data from stages table (works for both productionstage and legacy)
                                if ($use_productionstage) {
                                    // For productionstage, use process_stages.id as stage reference
                                    $sql4 = "SELECT a.*, b.step FROM stages a 
                                            LEFT JOIN process_stages_steps b ON a.step_id = b.id AND a.stage = b.process_stages_id 
                                            WHERE a.stage = '".$row2["id"]."' AND a.step_id = '".$row3["id"]."'";
                                } else {
                                    // Legacy: use manufacturing_process_stages.id as stage reference
                                    $sql4 = "SELECT a.*, b.step FROM stages a 
                                            LEFT JOIN manufacturing_process_step b ON a.step_id = b.id AND a.stage = b.manufacturing_process_stages_id 
                                            WHERE a.stage = '".$row2["id"]."' AND a.step_id = '".$row3["id"]."'";
                                }
                                
                                $result4 = $conn->query($sql4);
                                
                                if ($result4 && $result4->num_rows > 0) {
                                    while ($row4 = $result4->fetch_assoc()) {
                                        // Decode JSON fields
                                        $row4["procedures"] = isset($row4["procedure"]) ? json_decode($row4["procedure"]) : null;
                                        $row4["equipment"] = isset($row4["equipment"]) ? json_decode($row4["equipment"]) : null;
                                        $row4["CleaningChecks"] = isset($row4["CleaningChecks"]) ? json_decode($row4["CleaningChecks"]) : null;
                                        $row4["room"] = isset($row4["room"]) ? json_decode($row4["room"]) : null;
                                        $row4["weighing"] = isset($row4["weighing"]) ? json_decode($row4["weighing"]) : null;
                                        $row4["table"] = isset($row4["table"]) ? $row4["table"] : null;
                                        $row4["roomActions"] = isset($row4["roomActions"]) ? json_decode($row4["roomActions"]) : null;
                                        $row4["instructions"] = isset($row4["instructions"]) ? json_decode($row4["instructions"]) : null;
                                        $row4["initial_checks"] = isset($row4["initial_checks"]) ? json_decode($row4["initial_checks"]) : null;
                                        $row4["environments"] = isset($row4["environments"]) ? json_decode($row4["environments"]) : null;
                                        $row4["inprocess"] = isset($row4["inprocess"]) ? json_decode($row4["inprocess"]) : null;
                                        $row4["EquipmemntCleaning"] = isset($row4["EquipmemntCleaning"]) ? json_decode($row4["EquipmemntCleaning"]) : null;
                                        $row4["QcSample"] = isset($row4["QcSample"]) ? json_decode($row4["QcSample"]) : null;
                                        $row4["Logbook"] = isset($row4["Logbook"]) ? json_decode($row4["Logbook"]) : null;
                                        $row4["sequence"] = isset($row4["sequence"]) ? json_decode($row4["sequence"]) : null;
                                        
                                        $output4[] = $row4;
                                    }
                                }
                                
                                // If no BMR data found, still include the step
                                if (empty($output4)) {
                                    $step_data = $row3;
                                    $step_data['step'] = isset($row3['step']) ? $row3['step'] : '';
                                    $output4[] = $step_data;
                                }
                                
                                $output3 = array_merge($output3, $output4);
                            }
                        }
                        
                        $row2["Steps"] = $output3;
                        $output2[] = $row2;
                    }
                }
                
                $row["Stages"] = $output2;
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    // Get Process Master (from productionstage - process_types)
    else if ($_GET["type"] == "getProcessesmaster") {
        $output = Array();
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $dosage_form = isset($_GET["dosage_form"]) ? mysqli_real_escape_string($conn, $_GET["dosage_form"]) : '';
        
        if (empty($plant_id)) {
            echo json_encode(['error' => 'Plant ID is required']);
            exit;
        }
        
        // Get from process_types table (productionstage)
        $sql = "SELECT * FROM process_types WHERE plant_id = '".$plant_id."'";
        
        if (!empty($dosage_form)) {
            $sql .= " AND dosage_form LIKE '%".$dosage_form."%'";
        }
        
        $sql .= " ORDER BY id DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                
                // Get stages from process_stages table
                $sql1 = "SELECT * FROM process_stages WHERE process_type_id = '".$row["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                $result1 = $conn->query($sql1);
                
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output11 = Array();
                        
                        // Get steps from process_stages_steps table
                        $sql11 = "SELECT * FROM process_stages_steps WHERE process_stages_id = '".$row1["id"]."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                        $result11 = $conn->query($sql11);
                        
                        if ($result11 && $result11->num_rows > 0) {
                            while ($row11 = $result11->fetch_assoc()) {
                                $output111 = Array();
                                
                                // Get substeps from process_stages_steps_substep table - process_stages_steps_id is INT
                                $step_id_int = intval($row11["id"]);
                                $sql111 = "SELECT * FROM process_stages_steps_substep WHERE process_stages_steps_id = ".$step_id_int." AND (TRIM(plant_id) = '".mysqli_real_escape_string($conn, trim($plant_id))."' OR plant_id = ".intval($plant_id).") ORDER BY id ASC";
                                $result111 = $conn->query($sql111);
                                
                                if ($result111 && $result111->num_rows > 0) {
                                    while ($row111 = $result111->fetch_assoc()) {
                                        // Ensure all substep fields are included
                                        $substep_data = array(
                                            'id' => $row111['id'],
                                            'process_stages_id' => isset($row111['process_stages_id']) ? $row111['process_stages_id'] : '',
                                            'process_stages_steps_id' => isset($row111['process_stages_steps_id']) ? $row111['process_stages_steps_id'] : '',
                                            'plant_id' => isset($row111['plant_id']) ? $row111['plant_id'] : '',
                                            'Substep' => isset($row111['Substep']) ? $row111['Substep'] : '',
                                            'substep' => isset($row111['Substep']) ? $row111['Substep'] : (isset($row111['substep']) ? $row111['substep'] : '')
                                        );
                                        $output111[] = $substep_data;
                                    }
                                }
                                
                                // Always include Substeps array, even if empty
                                $row11["Substeps"] = $output111;
                                $output11[] = $row11;
                            }
                        }
                        
                        $row1["steps"] = $output11;
                        $output1[] = $row1;
                    }
                }
                
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    // Save Manufacturing Process - Link product to process_type from productionstage
    else if ($_GET["type"] == "saveMFGProcess") {
        $input = json_decode(file_get_contents('php://input'), true);
        $plant_id = isset($_GET["plant_id"]) ? mysqli_real_escape_string($conn, $_GET["plant_id"]) : '';
        $product_code = isset($input["product_code"]) ? mysqli_real_escape_string($conn, $input["product_code"]) : '';
        $process_type_id = isset($input["process_type_id"]) ? mysqli_real_escape_string($conn, $input["process_type_id"]) : '';
        $ProcessTitle = isset($input["ProcessTitle"]) ? mysqli_real_escape_string($conn, $input["ProcessTitle"]) : '';
        $DocumentNo = isset($input["DocumentNo"]) ? mysqli_real_escape_string($conn, $input["DocumentNo"]) : '';
        
        if (empty($plant_id) || empty($product_code) || empty($process_type_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Plant ID, Product Code, and Process Type ID are required']);
            exit;
        }
        
        // Check if process_type exists in productionstage
        $sql_check = "SELECT * FROM process_types WHERE id = '".$process_type_id."' AND plant_id = '".$plant_id."'";
        $result_check = $conn->query($sql_check);
        
        if (!$result_check || $result_check->num_rows == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Process Type not found in productionstage']);
            exit;
        }
        
        $process_type_row = $result_check->fetch_assoc();
        
        // Check if manufacturing_process already exists for this product and process_type
        $sql_existing = "SELECT * FROM manufacturing_process WHERE product_code = '".$product_code."' AND plant_id = '".$plant_id."' 
                         AND (DocumentTitle = '".$ProcessTitle."' OR DocumentNo = '".$DocumentNo."')";
        $result_existing = $conn->query($sql_existing);
        
        if ($result_existing && $result_existing->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Manufacturing Process already exists for this product']);
            exit;
        }
        
        // Create manufacturing_process record linking product to process_type
        $entry_date = date('Y-m-d H:i:s');
        $sql = "INSERT INTO manufacturing_process (plant_id, product_code, DocumentTitle, DocumentNo, process_type_id, dosage_form, process_type, entry_by, entry_date) 
                VALUES ('".$plant_id."', '".$product_code."', '".$ProcessTitle."', '".$DocumentNo."', '".$process_type_id."', 
                        '".$process_type_row['dosage_form']."', '".$process_type_row['process_type']."', '".$_GET["emp_id"]."', '".$entry_date."')";
        
        if ($conn->query($sql)) {
            $manufacturing_process_id = $conn->insert_id;
            
            // Copy stages from process_stages (productionstage) to manufacturing_process_stages
            $sql_stages = "SELECT * FROM process_stages WHERE process_type_id = '".$process_type_id."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
            $result_stages = $conn->query($sql_stages);
            
            if ($result_stages && $result_stages->num_rows > 0) {
                while ($stage_row = $result_stages->fetch_assoc()) {
                    $sql_insert_stage = "INSERT INTO manufacturing_process_stages (plant_id, manufacturing_process_id, stages, forms_list, entry_by, entry_date) 
                                        VALUES ('".$plant_id."', '".$manufacturing_process_id."', '".$stage_row['stage']."', 
                                                '".$stage_row['forms_list']."', '".$_GET["emp_id"]."', '".$entry_date."')";
                    
                    if ($conn->query($sql_insert_stage)) {
                        $manufacturing_stage_id = $conn->insert_id;
                        
                        // Copy steps from process_stages_steps to manufacturing_process_step
                        $sql_steps = "SELECT * FROM process_stages_steps WHERE process_stages_id = '".$stage_row['id']."' AND plant_id = '".$plant_id."' ORDER BY id ASC";
                        $result_steps = $conn->query($sql_steps);
                        
                        if ($result_steps && $result_steps->num_rows > 0) {
                            while ($step_row = $result_steps->fetch_assoc()) {
                                $sql_insert_step = "INSERT INTO manufacturing_process_step (plant_id, manufacturing_process_stages_id, step, checking_in, split_lot, entry_by, entry_date) 
                                                   VALUES ('".$plant_id."', '".$manufacturing_stage_id."', '".$step_row['step']."', 
                                                           '".$step_row['inprocess_checks']."', '', '".$_GET["emp_id"]."', '".$entry_date."')";
                                $conn->query($sql_insert_step);
                            }
                        }
                    }
                }
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Manufacturing Process created successfully', 'id' => $manufacturing_process_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
    else {
        echo json_encode(['error' => 'Invalid request type']);
    }
} else {
    echo json_encode(['error' => 'Invalid token']);
}
?>
