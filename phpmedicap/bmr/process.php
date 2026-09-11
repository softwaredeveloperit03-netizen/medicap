<?php 

require '../db.php';
require '../token.php';

    //  ini_set('display_errors', 1);
    //     error_reporting(E_ALL); 
    
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];


$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
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

    @$conn->query("CREATE TABLE IF NOT EXISTS `linemaster_mapped_Equipment` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `linemaster_id` INT NULL DEFAULT NULL,
        `equipment_name` VARCHAR(255) NULL DEFAULT NULL,
        `equipment_code` VARCHAR(100) NULL DEFAULT NULL,
        `capacity` VARCHAR(100) NULL DEFAULT NULL,
        `from_range` VARCHAR(100) NULL DEFAULT NULL,
        `to_range` VARCHAR(100) NULL DEFAULT NULL,
        `unit` VARCHAR(50) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_lme_line` (`linemaster_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @$conn->query("CREATE TABLE IF NOT EXISTS `linemaster_mapped_Product` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `linemaster_id` INT NULL DEFAULT NULL,
        `product_code` VARCHAR(100) NULL DEFAULT NULL,
        `category` VARCHAR(100) NULL DEFAULT NULL,
        `dosage_form` VARCHAR(100) NULL DEFAULT NULL,
        `generic_name` VARCHAR(255) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_lmp_line` (`linemaster_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Line Master workflow status (Pending → Checked → Approved)
    $lmStatusCol = @$conn->query("SHOW COLUMNS FROM linemaster LIKE 'status'");
    if (!$lmStatusCol || $lmStatusCol->num_rows == 0) {
        @$conn->query("ALTER TABLE linemaster ADD COLUMN `status` VARCHAR(50) NULL DEFAULT 'Pending'");
        @$conn->query("UPDATE linemaster SET status='Approved' WHERE status IS NULL OR TRIM(IFNULL(status,'')) = ''");
    }

    if ($_GET["type"] == "getProcesses") {
        
        

        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT * FROM manufacturing_process where  plant_id ='".$_GET["plant_id"]."' and id='".$_GET['id']."' order by id desc ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM manufacturing_process_stages where manufacturing_process_id =  '".$row["id"]."' and plant_id ='".$_GET["plant_id"]."' order by id asc";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                
                                $row2["forms_list"] = json_decode($row2["forms_list"]); 
                                $output3 = Array();
                                $sql3 = "SELECT * FROM manufacturing_process_step where manufacturing_process_stages_id =  '".$row2["id"]."'  and plant_id ='".$_GET["plant_id"]."' ";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                           $output4 = Array();
                                           
                                           // Get substeps for this step
                                           $sql_substeps = "SELECT * FROM manufacturing_process_step_substep WHERE manufacturing_process_step_id = '".$row3["id"]."' AND plant_id ='".$_GET["plant_id"]."' ORDER BY id ASC";
                                           $result_substeps = $conn->query($sql_substeps);
                                           if ($result_substeps && $result_substeps->num_rows > 0) {
                                               while ($substep_row = $result_substeps->fetch_assoc()) {
                                                   $output4[] = $substep_row;
                                               }
                                           }
                                           $row3["Substeps"] = $output4;
                                           
                                            $total_sum_c = 0;

// Query to get the sequence from stages
     $sql4 = "SELECT id,sequence FROM stages a WHERE a.stage='".$row2["id"]."' AND a.step_id='".$row3["id"]."'";
$result4 = $conn->query($sql4);

if ($result4->num_rows > 0) {
    while ($row4 = $result4->fetch_assoc()) {
    $sequence = json_decode($row4['sequence'], true);


        // Check if 'sequence' is an array and contains items
        if (is_array($sequence)) {
            foreach ($sequence as $seq_item) {
              
                if (isset($seq_item['list'])) {
                    $list = $seq_item['list'];

                    
                     $sql_list = "SELECT * FROM stages WHERE $list = '2' AND id='".$row4["id"]."'";
                    $result_list = $conn->query($sql_list);

                    // Initialize $c to 0 for this iteration
                    $c = 0;

                    // Process the result of the query
                    if ($result_list->num_rows > 0) {
                        // If rows are found, set $c to 1
                        $c = 1;
                    }

                    // Add $c to total sum
                    $total_sum_c += $c;
                }
            }
        }
    }
     $length = count($sequence);
    if($total_sum_c==2){
        $total_sum_c==1;
    }
    else if($total_sum_c==4){
        $total_sum_c==2;
    }else{
        if($length !==0 && $length==$total_sum_c){
            
         $total_sum_c=$total_sum_c;
        }else if ($length !==0 && $length < $total_sum_c){
            
            $total_sum_c=$total_sum_c-2;
        }
        
    }
    $length = count($sequence);
 $row3['$total_sum_c'] = $total_sum_c;
                $row3['sequence'] = $sequence;
                $row3['length'] = $length;
    if($length !==0 && $row3['length']==$row3['$total_sum_c']){
        $row3['View']='true';
    }else{
        $row3['View']='false';
        
    }
                                            }
                                            else{
                                                  $row3['View']='false';
                                            }
                                   
                                    // Ensure Substeps array exists even if empty
                                    if (!isset($row3["Substeps"])) {
                                        $row3["Substeps"] = Array();
                                    }
                                   
                                    $output3[] = $row3;
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
     else if ($_GET["type"] == "getProcesses_BMRviewProceed") {
             
             // Initialize the main output array
$output = array();

// SQL query to fetch manufacturing process data based on plant_id and id
$sql = "SELECT * FROM manufacturing_process WHERE plant_id ='" . $_GET["plant_id"] . "' AND product_code='" . $_GET['product_code'] . "' ORDER BY id DESC limit 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Array to hold the data for stages
        $output2 = array();

        // SQL query to fetch stages associated with the manufacturing process
        $sql2 = "SELECT * FROM manufacturing_process_stages WHERE manufacturing_process_id = '" . $row["id"] . "' AND plant_id ='" . $_GET["plant_id"] . "' ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                // Array to hold the data for steps within each stage
                $output3 = array();

                // SQL query to fetch steps associated with each stage
                $sql3 = "SELECT * FROM manufacturing_process_step WHERE manufacturing_process_stages_id =  '" . $row2["id"] . "'  AND plant_id ='" . $_GET["plant_id"] . "'";
                $result3 = $conn->query($sql3);

                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        
                        
                        $output3[] = $row3;
 
                    }
                }

                // Assigning the output3 array to the current stage
                $row2["Steps"] = $output3;
                $output2[] = $row2; // Adding the current stage to the output2 array
            }
        }

        $row["Stages"] = $output2; // Assigning the output2 array to the current manufacturing process
        $output[] = $row; // Adding the current manufacturing process to the main output array
    }
}

// Encode the output array into JSON format
echo json_encode($output);

         } 
        else if ($_GET["type"] == "getProcesses_viewZuma") {
             
           

$output = array();

// Get plant_id and id from GET parameters safely
$plant_id = $_GET["plant_id"];
$id = $_GET["id"];
$status = $_GET["status"];

// Main process query
  $sql = "SELECT * FROM bmr_process WHERE    id = '$id' AND status='$status' ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output2 = array(); // Stages array

        // Fetch stages
         $sql2 = "SELECT * FROM bmr_process_stage WHERE bmr_process_id ='".$row["id"]."'   ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output3 = array(); // Steps array

                // Fetch steps
                $sql3 = "SELECT * FROM bmr_process_stages_step WHERE bmr_process_stage_id = '".$row2["id"]."'  ";
                $result3 = $conn->query($sql3);

                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output4 = array(); // Substeps array

                        // Fetch substeps
                        $sql4 = "SELECT * FROM bmr_process_stages_step_substep 
                                 WHERE bmr_process_stages_step_id = '".$row3["id"]."'
                                 AND bmr_process_stage_id ='".$row2["id"]."'";
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

                $row2["Steps"] = $output3;
                $output2[] = $row2;
            }
        }

        $row["Stages"] = $output2;
        $output[] = $row;
    }
}

// Output JSON
echo json_encode($output);

         } 
    else if ($_GET["type"] == "saveGroupsAndGetStages") {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $dosage_form = $input['dosage_form'] ?? '';
        $selectedGroups = $input['selectedGroups'] ?? [];
        
        if (empty($dosage_form) || empty($selectedGroups)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Dosage form and selected groups are required'
            ]);
            exit;
        }
        
        // Fetch stages from process_stages table where dosage_form matches selected groups
        // Only fetch dosage_form and stage columns
        $stages = [];
        
        // Build WHERE clause to match any of the selected groups
        $whereConditions = [];
        foreach ($selectedGroups as $group) {
            $escapedGroup = mysqli_real_escape_string($conn, $group);
            $whereConditions[] = "dosage_form = '" . $escapedGroup . "'";
            // Also check if dosage_form contains the group (for comma-separated values)
            $whereConditions[] = "FIND_IN_SET('" . $escapedGroup . "', dosage_form) > 0";
        }
        
        $whereClause = implode(' OR ', $whereConditions);
        $fetchSql = "SELECT DISTINCT dosage_form, stage FROM process_stages WHERE " . $whereClause . " ORDER BY stage ASC";
        
        $fetchResult = $conn->query($fetchSql);
        
        if ($fetchResult) {
            while ($row = $fetchResult->fetch_assoc()) {
                $stages[] = [
                    'dosage_form' => $row['dosage_form'] ?? '',
                    'stage' => $row['stage'] ?? ''
                ];
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database query error: ' . $conn->error
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Stages fetched successfully',
            'stages' => $stages,
            'dosage_form' => $dosage_form
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
    if ($_GET["type"] == "getProcessesmaster") {
    
    $output = Array();

    $sql = "SELECT * FROM process_types 
            WHERE plant_id = '".$_GET["plant_id"]."' 
            AND dosage_form LIKE '%".$_GET['dosage_form']."%'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $output1 = Array();

            $sql1 = "SELECT * FROM process_stages 
                     WHERE process_type_id='".$row["id"]."'";

            $result1 = $conn->query($sql1);

            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {

                    $output11 = Array();

                    $sql11 = "SELECT * FROM process_stages_steps 
                              WHERE process_stages_id='".$row1["id"]."'";

                    $result11 = $conn->query($sql11);

                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {

                            // 🔁 SUBSTEP QUERY (NEW)
                            $output111 = Array();

                            $sql111 = "SELECT * FROM process_stages_steps_substep 
                                       WHERE process_stages_steps_id='".$row11["id"]."'";

                            $result111 = $conn->query($sql111);

                            if ($result111->num_rows > 0) {
                                while ($row111 = $result111->fetch_assoc()) {
                                    $output111[] = $row111;
                                }
                            }

                            // attach substeps
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
      if ($_GET["type"] == "getProcessesmasterMeha") {
          
          
          $output = Array();

$sql = "SELECT * FROM process_types WHERE plant_id = '".$_GET["plant_id"]."'   ";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output1 = Array();
        $sql1 = "SELECT * FROM process_stages WHERE process_type_id='".$row["id"]."'";

        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output11 = Array();
                $sql11 = "SELECT * FROM process_stages_steps WHERE process_stages_id='".$row1["id"]."'";

                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
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
         else if ($_GET["type"] == "getProcesses_view") {
        
        // Initialize the main output array
$output = array();

// SQL query to fetch manufacturing process data based on plant_id and id
$sql = "SELECT * FROM manufacturing_process WHERE plant_id ='" . $_GET["plant_id"] . "' AND id='" . $_GET['id'] . "' ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Array to hold the data for stages
        $output2 = array();

        // SQL query to fetch stages associated with the manufacturing process
        $sql2 = "SELECT * FROM manufacturing_process_stages WHERE manufacturing_process_id = '" . $row["id"] . "' AND plant_id ='" . $_GET["plant_id"] . "' ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                // Array to hold the data for steps within each stage
                $output3 = array();

                // SQL query to fetch steps associated with each stage
                $sql3 = "SELECT * FROM manufacturing_process_step WHERE manufacturing_process_stages_id =  '" . $row2["id"] . "'  AND plant_id ='" . $_GET["plant_id"] . "'";
                $result3 = $conn->query($sql3);

                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        // Array to hold the data for entries within each step
                        $output4 = array();
                        
                        // Get substeps for this step
                        $sql_substeps = "SELECT * FROM manufacturing_process_step_substep WHERE manufacturing_process_step_id = '" . $row3["id"] . "' AND plant_id ='" . $_GET["plant_id"] . "' ORDER BY id ASC";
                        $result_substeps = $conn->query($sql_substeps);
                        $substeps_array = array();
                        if ($result_substeps && $result_substeps->num_rows > 0) {
                            while ($substep_row = $result_substeps->fetch_assoc()) {
                                $substeps_array[] = $substep_row;
                            }
                        }
                        $row3["Substeps"] = $substeps_array;

                        // SQL query to fetch entries from the stages table
                        $sql4 = "SELECT a.*,b.step FROM stages a left join  manufacturing_process_step b on a.step_id=b.id and a.stage=b.manufacturing_process_stages_id WHERE a.stage='" . $row2["id"] . "' AND a.step_id='" . $row3["id"] . "'";
                        $result4 = $conn->query($sql4);

                        if ($result4->num_rows > 0) {
                            while ($row4 = $result4->fetch_assoc()) {
                                
                                 $row4["procedures"] = json_decode($row4["procedure"]);
                    		 
                    		      
                    		      $row4["equipment"] = json_decode($row4["equipment"]);
                    		    
                    		      
                    		      $row4["CleaningChecks"] = json_decode($row4["CleaningChecks"]);
                    		  
                    		      
                    		      $row4["room"] = json_decode($row4["room"]);
                    		    
                    		      
                    		      $row4["weighing"] = json_decode($row4["weighing"]);
                    		   
                    		      
                    		      $row4["table"] =  $row4["table"];
                        	
                    		      
                    		      $row4["roomActions"] = json_decode($row4["roomActions"]);
                    		   
                    		      $row4["instructions"] = json_decode($row4["instructions"]);
                    		      $row4["initial_checks"] = json_decode($row4["initial_checks"]);
                    		      $row4["environments"] = json_decode($row4["environments"]);
                    		      $row4["inprocess"] = json_decode($row4["inprocess"]);
                    		      $row4["EquipmemntCleaning"] = json_decode($row4["EquipmemntCleaning"]);
                    		      $row4["QcSample"] = json_decode($row4["QcSample"]);
                    		      $row4["Logbook"] = json_decode($row4["Logbook"]);
                    		      //$row4["LineClearance"] = json_decode($row4["LineClearance"]);
                    		      $row4["sequence"] = json_decode($row4["sequence"]);
                    		      // Add substeps from step to BMR stage entry
                    		      $row4["Substeps"] = isset($row3["Substeps"]) ? $row3["Substeps"] : array();
                    		  
                                
                                
                                
                                
                                
                                $output3[] = $row4; // Adding each entry to the output4 array
                            }
                        } else {
                            // If no BMR data in stages table, still add the step with substeps
                            $row3["Substeps"] = isset($substeps_array) ? $substeps_array : array();
                            $output3[] = $row3;
                        }

                        
                    }
                }

                $row2["Steps"] = $output3; // Assigning the output3 array to the current stage
                $output2[] = $row2; // Adding the current stage to the output2 array
            }
        }

        $row["Stages"] = $output2; // Assigning the output2 array to the current manufacturing process
        $output[] = $row; // Adding the current manufacturing process to the main output array
    }
}

// Encode the output array into JSON format
echo json_encode($output);
    } 
         else if ($_GET["type"] == "getProcesses_BMRview") {
             
             // Initialize the main output array
$output = array();

// SQL query to fetch manufacturing process data based on plant_id and id
$sql = "SELECT * FROM manufacturing_process WHERE plant_id ='" . $_GET["plant_id"] . "' AND product_code='" . $_GET['product_code'] . "' ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Array to hold the data for stages
        $output2 = array();

        // SQL query to fetch stages associated with the manufacturing process
        $sql2 = "SELECT * FROM manufacturing_process_stages WHERE manufacturing_process_id = '" . $row["id"] . "' AND plant_id ='" . $_GET["plant_id"] . "' ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                // Array to hold the data for steps within each stage
                $output3 = array();

                // SQL query to fetch steps associated with each stage
                $sql3 = "SELECT * FROM manufacturing_process_step WHERE manufacturing_process_stages_id =  '" . $row2["id"] . "'  AND plant_id ='" . $_GET["plant_id"] . "'";
                $result3 = $conn->query($sql3);

                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        
                        
                        
                    $other_data = json_decode($_GET['other_data'], true); // true to convert to an associative array
 
                
                                            $sql4 = "
                                SELECT 
                                    a.*, 
                                    b.step,
                                    
                                    -- lineclearance1 Column
                                    (
                                        SELECT JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'id', l.id,'work_order_id', l.work_order_id,'pleasure_diff_reading', l.pleasure_diff_reading,'laf_eqip_code',
                                                 l.laf_eqip_code,'entry_by', l.entry_by,'previous_data', JSON_EXTRACT(l.prev_product, '$')
                                            )
                                        )
                                        FROM lineclearance l 
                                        WHERE l.work_order_id = '".$other_data['work_order_id']."'   ORDER BY l.id DESC
                                      
                                    ) AS dispensing_lineclearance,
                            
                                    -- Dispending Column
                                    (
                                        SELECT JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'material_code', a.material_code,'grade', a.grade,'density', a.density,'uom', a.uom,'alternate_uom',
                                                 a.alternate_uom,
                                                 'material_type', a.material_type,
                                                 'material_name', a.material_name,'avbl_stock', b.avbl_stock,'batch_qty', a.batch_qty,'dispensed_qty', dispensed_qty,'dispensed_by', dispensed_by
                                            )
                                        )
                                        FROM 
                                            (
                                                SELECT 
                                                    a.*,  
                                                    b.grade, b.density, b.uom, b.alternate_uom, b.material_nature, b.unit_conversion, 
                                                     b.category,  b.material_name, 
                                                    wd.lod_status, wd.assay_status, 
                                                    IFNULL(dd.id, 0) AS dispence_id, dd.gross_total as dispensed_qty,dd.entry_by as dispensed_by,
                                                    dd.qa_status, dd.prod_status, dd.qa_checking, dd.prod_checking 
                                                FROM work_order_batch_lots a
                                                JOIN mfg_work_order_hdr c ON a.work_order_id = c.id
                                                LEFT JOIN mfg_work_order_dtl wd ON wd.work_order_id = c.id AND wd.material_code = a.material_code
                                                LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id
                                                LEFT JOIN dispensing_details_hdr dd ON a.id = dd.lot_id
                                               
                                                WHERE a.work_order_id = '".$other_data['work_order_id']."'
                                            ) AS a
                                        LEFT JOIN 
                                            (
                                                SELECT material_code, balance_qty AS avbl_stock 
                                                FROM vw_stock_summary 
                                                WHERE plant_id='".$_GET["plant_id"]."' 
                                                AND material_code IN (
                                                    SELECT material_code 
                                                    FROM work_order_batch_lots 
                                                    WHERE work_order_id ='".$other_data['work_order_id']."'
                                                )
                                            ) AS b 
                                        ON a.material_code = b.material_code
                                    ) AS Dispensing
                                    
                                FROM stages a 
                                LEFT JOIN manufacturing_process_step b 
                                ON a.step_id = b.id 
                                AND a.stage = b.manufacturing_process_stages_id 
                                WHERE a.stage = '" . $row2["id"] . "' 
                                AND a.step_id = '" . $row3["id"] . "'
                            ";

                        $result4 = $conn->query($sql4);

                        if ($result4 && $result4->num_rows > 0) {
                            while ($row4 = $result4->fetch_assoc()) {
                                // Decode JSON fields
                                 $row4["procedures"] = json_decode($row4["procedure"]);
                                 $row4["Dispensing"] = json_decode($row4["Dispensing"]);
                                $row4["equipment"] = json_decode($row4["equipment"]);
                                $row4["CleaningChecks"] = json_decode($row4["CleaningChecks"]);
                                $row4["room"] = json_decode($row4["room"]);
                                $row4["weighing"] = json_decode($row4["weighing"]);
                                $row4["table"] = $row4["table"];
                                $row4["roomActions"] = json_decode($row4["roomActions"]);
                                $row4["instructions"] = json_decode($row4["instructions"]);
                                $row4["initial_checks"] = json_decode($row4["initial_checks"]);
                                $row4["environments"] = json_decode($row4["environments"]);
                                $row4["inprocess"] = json_decode($row4["inprocess"]);
                                $row4["EquipmemntCleaning"] = json_decode($row4["EquipmemntCleaning"]);
                                $row4["QcSample"] = json_decode($row4["QcSample"]);
                                $row4["Logbook"] = json_decode($row4["Logbook"]);
                                 $row4["Dispensning_lineclearance"] = json_decode($row4["dispensing_lineclearance"]);
                                $row4["sequence"] = json_decode($row4["sequence"]);

                                // Add the data to the output array
                               

                                // SQL query to check if the record already exists in bmr_stages table
                                $sql9 = "SELECT * FROM bmr_stages 
                                         WHERE stages_id = '" . $row4["id"] . "' 
                                         AND stage = '" . $row2["id"] . "' 
                                         AND step_id = '" . $row3["id"] . "'";
                                
                                $result9 = $conn->query($sql9);
                                
                                if ($result9 && $result9->num_rows > 0) {
                                    // Fetch and decode bmr_stages data
                                    while ($row9 = $result9->fetch_assoc()) {
                                        $row4["bmr_procedures"] = json_decode($row9["procedure"]);
                                        $row4["bmr_equipment"] = json_decode($row9["equipment"]);
                                        $row4["bmr_CleaningChecks"] = json_decode($row9["CleaningChecks"]);
                                        $row4["bmr_room"] = json_decode($row9["room"]);
                                        $row4["bmr_weighing"] = json_decode($row9["weighing"]);
                                        $row4["bmr_SIFTING_Lubrication"] = json_decode($row9["SIFTING_Lubrication"]);
                                        $row4["bmr_YIELD_RECONCILIATION"] = json_decode($row9["YIELD_RECONCILIATION"]);
                                        $row4["bmr_BlendLubrication"] = json_decode($row9["BlendLubrication"]);
                                                 $row4["bmr_COMPRESSION_PARAMETERS"] = json_decode($row9["COMPRESSION_PARAMETERS"]);
                                                 $row4["bmr_Weighing_Variation_Recoed"] = json_decode($row9["Weighing_Variation_Recoed"]);
                                                 $row4["bmr_millinsifting"] = json_decode($row9["millinsifting"]);
                                                 $row4["bmr_INPROCESS_YIELD"] = json_decode($row9["INPROCESS_YIELD"]);
                                                 $row4["bmr_Mixing"] = json_decode($row9["Mixing"]);
                                                 $row4["bmr_Drying"] = json_decode($row9["Drying"]);
                                                 $row4["bmr_Dispensing"] = json_decode($row9["Dispensing"]);
                                                 $row4["bmr_Dry_SIFTING_MILLING"] = json_decode($row9["Dry_SIFTING_MILLING"]);
                                                 $row4["bmr_QcSample"] = json_decode($row9["QcSample"]);
                                                 $row4["bmr_SieveInteggity"] = json_decode($row9["SieveInteggity"]);
                                                $row4["step_status"] = $row9["step_status"];
                                    }
                                     $output4 = $row4;
                                } else {
                                    $sequence = $row4["sequence"];
                                
                                // Check if the sequence is a JSON string, then decode it
                                if (is_string($sequence)) {
                                    $sequence = json_decode($sequence, true);
                                }
                                
                                // Convert the sequence back to JSON if needed (for insertion into the database)
                                $sequence = json_encode($sequence);
                                    // Insert the data into bmr_stages table
                                    $sql10 = "INSERT INTO bmr_stages (stage, step_id, product_code, work_order_no, stages_id,sequence) 
                                              VALUES ('" . $row2["id"] . "', '" . $row3["id"] . "', '" . $_GET["product_code"] . "', '" . $_GET["work_order_no"] . "', '" . $row4["id"] . "', '$sequence')";
                                    
                                    if ($conn->query($sql10)) {
                                        // Re-fetch the newly inserted data
                                        $result9 = $conn->query($sql9);
                                        
                                        if ($result9 && $result9->num_rows > 0) {
                                            while ($row9 = $result9->fetch_assoc()) {
                                                 $row4["bmr_procedures"] = json_decode($row9["procedure"]);
                                                $row4["bmr_equipment"] = json_decode($row9["equipment"]);
                                                $row4["bmr_CleaningChecks"] = json_decode($row9["CleaningChecks"]);
                                                $row4["bmr_room"] = json_decode($row9["room"]);
                                                // $row4["bmr_weighing"] = json_decode($row9["weighing"]);
                                                //  $row4["bmr_SIFTING_Lubrication"] = json_decode($row9["SIFTING_Lubrication"]);
                                                //  $row4["bmr_YIELD_RECONCILIATION"] = json_decode($row9["YIELD_RECONCILIATION"]);
                                                //  $row4["bmr_COMPRESSION_PARAMETERS"] = json_decode($row9["COMPRESSION_PARAMETERS"]);
                                                //  $row4["bmr_Weighing_Variation_Recoed"] = json_decode($row9["Weighing_Variation_Recoed"]);
                                                //  $row4["bmr_millinsifting"] = json_decode($row9["millinsifting"]);
                                                //  $row4["bmr_INPROCESS_YIELD"] = json_decode($row9["INPROCESS_YIELD"]);
                                                //  $row4["bmr_Mixing"] = json_decode($row9["Mixing"]);
                                                //  $row4["bmr_Drying"] = json_decode($row9["Drying"]);
                                                //  $row4["bmr_Dispensing"] = json_decode($row9["Dispensing"]);
                                                //  $row4["bmr_Dry_SIFTING_MILLING"] = json_decode($row9["Dry_SIFTING_MILLING"]);
                                                //  $row4["bmr_QcSample"] = json_decode($row9["QcSample"]);
                                                //  $row4["bmr_SieveInteggity"] = json_decode($row9["SieveInteggity"]);
                                                //  $row4["bmr_Yield"] = json_decode($row9["Yield"]);
                                                
                                                
                                             
                                            }
                                        }
                                    } else {
                                        // Handle insertion error
                                        echo "Error inserting data: " . $conn->error;
                                    }
                                }
                
                            }
                        }

                        // Add the $output4 data to $output3
                        $output3[] = $output4;
                    }
                }

                // Assigning the output3 array to the current stage
                $row2["Steps"] = $output3;
                $output2[] = $row2; // Adding the current stage to the output2 array
            }
        }

        $row["Stages"] = $output2; // Assigning the output2 array to the current manufacturing process
        $output[] = $row; // Adding the current manufacturing process to the main output array
    }
}

// Encode the output array into JSON format
echo json_encode($output);

         } 
         else if ($_GET["type"] == "getProcesses_BMRviewAllocationMeha") {
             
             // Initialize the main output array
$output = array();

// SQL query to fetch manufacturing process data based on plant_id and id
$sql = "SELECT id FROM manufacturing_process WHERE plant_id ='" . $_GET["plant_id"] . "' AND product_code='" . $_GET['product_code'] . "' ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Array to hold the data for stages
        $output2 = array();

        // SQL query to fetch stages associated with the manufacturing process
        $sql2 = "SELECT * FROM manufacturing_process_stages WHERE manufacturing_process_id = '" . $row["id"] . "' AND plant_id ='" . $_GET["plant_id"] . "' ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                // Array to hold the data for steps within each stage
                $output3 = array();

               
                 $sql3 = "SELECT *,a.id as idd  FROM manufacturing_process_step a WHERE a.manufacturing_process_stages_id =  '" . $row2["id"] . "'  AND a.plant_id ='" . $_GET["plant_id"] . "'";
                $result3 = $conn->query($sql3);

                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        
                        
                        
                    $other_data = json_decode($_GET['other_data'], true); // true to convert to an associative array
 
                
                                       $sql4 = "
                                SELECT 
                                    a.*, 
                                    b.step,
                                    
                                    -- lineclearance1 Column
                                    (
                                        SELECT JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'id', l.id,'work_order_id', l.work_order_id,'pleasure_diff_reading', l.pleasure_diff_reading,'laf_eqip_code',
                                                 l.laf_eqip_code,'entry_by', l.entry_by,'previous_data', JSON_EXTRACT(l.prev_product, '$')
                                            )
                                        )
                                        FROM lineclearance l 
                                        WHERE l.work_order_id = '".$other_data['work_order_id']."'   ORDER BY l.id DESC
                                      
                                    ) AS dispensing_lineclearance,
                            
                                    -- Dispending Column
                                    (
                                        SELECT JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'material_code', a.material_code,'grade', a.grade,'density', a.density,'uom', a.uom,'alternate_uom',
                                                 a.alternate_uom,
                                                 'material_type', a.material_type,
                                                 'material_name', a.material_name,'avbl_stock', b.avbl_stock,'batch_qty', a.batch_qty,'dispensed_qty', dispensed_qty,'dispensed_by', dispensed_by
                                            )
                                        )
                                        FROM 
                                            (
                                                SELECT 
                                                    a.*,  
                                                    b.grade, b.density, b.uom, b.alternate_uom, b.material_nature, b.unit_conversion, 
                                                     b.category, COALESCE(b.material_name, p.product_name) AS material_name,
                                                    wd.lod_status, wd.assay_status, 
                                                    IFNULL(dd.id, 0) AS dispence_id, dd.gross_total as dispensed_qty,dd.entry_by as dispensed_by,
                                                    dd.qa_status, dd.prod_status, dd.qa_checking, dd.prod_checking 
                                                FROM work_order_batch_lots a
                                                JOIN mfg_work_order_hdr c ON a.work_order_id = c.id
                                                LEFT JOIN mfg_work_order_dtl wd ON wd.work_order_id = c.id AND wd.material_code = a.material_code
                                                LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id
                                                LEFT JOIN product p ON a.material_code = p.product_code AND c.plant_id = p.plant_id
                                                LEFT JOIN dispensing_details_hdr dd ON a.id = dd.lot_id
                                               
                                                WHERE a.work_order_id = '".$other_data['work_order_id']."'
                                            ) AS a
                                        LEFT JOIN 
                                            (
                                                SELECT material_code, balance_qty AS avbl_stock 
                                                FROM vw_stock_summary 
                                                WHERE plant_id='".$_GET["plant_id"]."' 
                                                AND material_code IN (
                                                    SELECT material_code 
                                                    FROM work_order_batch_lots 
                                                    WHERE work_order_id ='".$other_data['work_order_id']."'
                                                )
                                            ) AS b 
                                        ON a.material_code = b.material_code
                                    ) AS Dispensing
                                    
                                FROM stages a 
                                LEFT JOIN manufacturing_process_step b 
                                ON a.step_id = b.id 
                                AND a.stage = b.manufacturing_process_stages_id 
                                WHERE a.stage = '" . $row2["id"] . "' 
                                AND a.step_id = '" . $row3["id"] . "'
                            ";

                        $result4 = $conn->query($sql4);

                        if ($result4 && $result4->num_rows > 0) {
                            while ($row4 = $result4->fetch_assoc()) {
                                // Decode JSON fields
                                //  $row4["procedures"] = json_decode($row4["procedure"]);
                                //  $row4["Dispensing"] = json_decode($row4["Dispensing"]);
                                // $row4["equipment"] = json_decode($row4["equipment"]);
                                // $row4["CleaningChecks"] = json_decode($row4["CleaningChecks"]);
                                // $row4["room"] = json_decode($row4["room"]);
                                // $row4["weighing"] = json_decode($row4["weighing"]);
                                // $row4["table"] = $row4["table"];
                                // $row4["roomActions"] = json_decode($row4["roomActions"]);
                                // $row4["instructions"] = json_decode($row4["instructions"]);
                                // $row4["initial_checks"] = json_decode($row4["initial_checks"]);
                                // $row4["environments"] = json_decode($row4["environments"]);
                                // $row4["inprocess"] = json_decode($row4["inprocess"]);
                                // $row4["EquipmemntCleaning"] = json_decode($row4["EquipmemntCleaning"]);
                                // $row4["QcSample"] = json_decode($row4["QcSample"]);
                                // $row4["Logbook"] = json_decode($row4["Logbook"]);
                                //  $row4["Dispensning_lineclearance"] = json_decode($row4["dispensing_lineclearance"]);
                                $row4["sequence"] = json_decode($row4["sequence"]);

                                // Add the data to the output array
                               

                                // SQL query to check if the record already exists in bmr_stages table
                                $sql9 = "SELECT * FROM bmr_stages 
                                         WHERE stages_id = '" . $row4["id"] . "' 
                                         AND stage = '" . $row2["id"] . "' 
                                         AND step_id = '" . $row3["id"] . "'";
                                
                                $result9 = $conn->query($sql9);
                                
                                if ($result9 && $result9->num_rows > 0) {
                                    // Fetch and decode bmr_stages data
                                    while ($row9 = $result9->fetch_assoc()) {
                                        // $row4["bmr_procedures"] = json_decode($row9["procedure"]);
                                        // $row4["bmr_equipment"] = json_decode($row9["equipment"]);
                                        // $row4["bmr_CleaningChecks"] = json_decode($row9["CleaningChecks"]);
                                        // $row4["bmr_room"] = json_decode($row9["room"]);
                                        // $row4["bmr_weighing"] = json_decode($row9["weighing"]);
                                        // $row4["bmr_SIFTING_Lubrication"] = json_decode($row9["SIFTING_Lubrication"]);
                                        // $row4["bmr_YIELD_RECONCILIATION"] = json_decode($row9["YIELD_RECONCILIATION"]);
                                        // $row4["bmr_BlendLubrication"] = json_decode($row9["BlendLubrication"]);
                                        //          $row4["bmr_COMPRESSION_PARAMETERS"] = json_decode($row9["COMPRESSION_PARAMETERS"]);
                                        //          $row4["bmr_Weighing_Variation_Recoed"] = json_decode($row9["Weighing_Variation_Recoed"]);
                                        //          $row4["bmr_millinsifting"] = json_decode($row9["millinsifting"]);
                                        //          $row4["bmr_INPROCESS_YIELD"] = json_decode($row9["INPROCESS_YIELD"]);
                                        //          $row4["bmr_Mixing"] = json_decode($row9["Mixing"]);
                                        //          $row4["bmr_Drying"] = json_decode($row9["Drying"]);
                                        //          $row4["bmr_Dispensing"] = json_decode($row9["Dispensing"]);
                                        //          $row4["bmr_Dry_SIFTING_MILLING"] = json_decode($row9["Dry_SIFTING_MILLING"]);
                                        //          $row4["bmr_QcSample"] = json_decode($row9["QcSample"]);
                                        //          $row4["bmr_SieveInteggity"] = json_decode($row9["SieveInteggity"]);
                                                $row4["step_status"] = $row9["step_status"];
                                    }
                                     $output4 = $row4;
                                } else {
                                    $sequence = $row4["sequence"];
                                
                                // Check if the sequence is a JSON string, then decode it
                                if (is_string($sequence)) {
                                    $sequence = json_decode($sequence, true);
                                }
                                
                                // Convert the sequence back to JSON if needed (for insertion into the database)
                                $sequence = json_encode($sequence);
                                    // Insert the data into bmr_stages table
                                    $sql10 = "INSERT INTO bmr_stages (stage, step_id, product_code, work_order_no, stages_id,sequence) 
                                              VALUES ('" . $row2["id"] . "', '" . $row3["id"] . "', '" . $_GET["product_code"] . "', '" . $_GET["work_order_no"] . "', '" . $row4["id"] . "', '$sequence')";
                                    
                                    if ($conn->query($sql10)) {
                                        // Re-fetch the newly inserted data
                                        $result9 = $conn->query($sql9);
                                        
                                        if ($result9 && $result9->num_rows > 0) {
                                            while ($row9 = $result9->fetch_assoc()) {
                                                 $row4["bmr_procedures"] = json_decode($row9["procedure"]);
                                                $row4["bmr_equipment"] = json_decode($row9["equipment"]);
                                                $row4["bmr_CleaningChecks"] = json_decode($row9["CleaningChecks"]);
                                                $row4["bmr_room"] = json_decode($row9["room"]);
                                               
                                                
                                                
                                             
                                            }
                                        }
                                    } else {
                                        // Handle insertion error
                                        echo "Error inserting data: " . $conn->error;
                                    }
                                }
                
                            }
                        }

                        // Add the $output4 data to $output3
                        $output3[] = $output4;
                    }
                }

                // Assigning the output3 array to the current stage
                $row2["Steps"] = $output3;
                $output2[] = $row2; // Adding the current stage to the output2 array
            }
        }

        $row["Stages"] = $output2; // Assigning the output2 array to the current manufacturing process
        $output[] = $row; // Adding the current manufacturing process to the main output array
    }
}

// Encode the output array into JSON format
echo json_encode($output);

         } 
                 else if ($_GET["type"] == "saveallocarion") {
        ini_set('display_errors', 1);
error_reporting(E_ALL);
           
                 $sql = "UPDATE stages SET `person1`='".$input["producton_ofc"]."' ,person2='".$input["alt_ofc"]."' ,Checker='".$input["supervisor"]."'  where stage='".$input["stageId"]."' and step_id='".$input["stepId"]."'";
            

        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
         else if ($_GET["type"] == "getProcesses_BMRviewMeha") {
             
             // Initialize the main output array
$output = array();

// SQL query to fetch manufacturing process data based on plant_id and id
$sql = "SELECT * FROM manufacturing_process WHERE plant_id ='" . $_GET["plant_id"] . "' AND product_code='" . $_GET['product_code'] . "' ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Array to hold the data for stages
        $output2 = array();

        // SQL query to fetch stages associated with the manufacturing process
        $sql2 = "SELECT * FROM manufacturing_process_stages WHERE manufacturing_process_id = '" . $row["id"] . "' AND plant_id ='" . $_GET["plant_id"] . "' ORDER BY id ASC";
        $result2 = $conn->query($sql2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                // Array to hold the data for steps within each stage
                $output3 = array();

                // SQL query to fetch steps associated with each stage
                $sql3 = "SELECT * FROM manufacturing_process_step WHERE manufacturing_process_stages_id =  '" . $row2["id"] . "'  AND plant_id ='" . $_GET["plant_id"] . "'";
                $result3 = $conn->query($sql3);

                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        
                        
                        
                    $other_data = json_decode($_GET['other_data'], true); // true to convert to an associative array
 
                
                                       $sql4 = "
                                SELECT 
                                    a.*, 
                                    b.step,
                                    
                                    -- lineclearance1 Column
                                    (
                                        SELECT JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'id', l.id,'work_order_id', l.work_order_id,'pleasure_diff_reading', l.pleasure_diff_reading,'laf_eqip_code',
                                                 l.laf_eqip_code,'entry_by', l.entry_by,'previous_data', JSON_EXTRACT(l.prev_product, '$')
                                            )
                                        )
                                        FROM lineclearance l 
                                        WHERE l.work_order_id = '".$other_data['work_order_id']."'   ORDER BY l.id DESC
                                      
                                    ) AS dispensing_lineclearance,
                            
                                    -- Dispending Column
                                    (
                                        SELECT JSON_ARRAYAGG(
                                            JSON_OBJECT(
                                                'material_code', a.material_code,'grade', a.grade,'density', a.density,'uom', a.uom,'alternate_uom',
                                                 a.alternate_uom,
                                                 'material_type', a.material_type,
                                                 'material_name', a.material_name,'avbl_stock', b.avbl_stock,'batch_qty', a.batch_qty,'dispensed_qty', dispensed_qty,'dispensed_by', dispensed_by
                                            )
                                        )
                                        FROM 
                                            (
                                                SELECT 
                                                    a.*,  
                                                    b.grade, b.density, b.uom, b.alternate_uom, b.material_nature, b.unit_conversion, 
                                                     b.category, COALESCE(b.material_name, p.product_name) AS material_name,
                                                    wd.lod_status, wd.assay_status, 
                                                    IFNULL(dd.id, 0) AS dispence_id, dd.gross_total as dispensed_qty,dd.entry_by as dispensed_by,
                                                    dd.qa_status, dd.prod_status, dd.qa_checking, dd.prod_checking 
                                                FROM work_order_batch_lots a
                                                JOIN mfg_work_order_hdr c ON a.work_order_id = c.id
                                                LEFT JOIN mfg_work_order_dtl wd ON wd.work_order_id = c.id AND wd.material_code = a.material_code
                                                LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id
                                                LEFT JOIN product p ON a.material_code = p.product_code AND c.plant_id = p.plant_id
                                                LEFT JOIN dispensing_details_hdr dd ON a.id = dd.lot_id
                                               
                                                WHERE a.work_order_id = '".$other_data['work_order_id']."'
                                            ) AS a
                                        LEFT JOIN 
                                            (
                                                SELECT material_code, balance_qty AS avbl_stock 
                                                FROM vw_stock_summary 
                                                WHERE plant_id='".$_GET["plant_id"]."' 
                                                AND material_code IN (
                                                    SELECT material_code 
                                                    FROM work_order_batch_lots 
                                                    WHERE work_order_id ='".$other_data['work_order_id']."'
                                                )
                                            ) AS b 
                                        ON a.material_code = b.material_code
                                    ) AS Dispensing
                                    
                                FROM stages a 
                                LEFT JOIN manufacturing_process_step b 
                                ON a.step_id = b.id 
                                AND a.stage = b.manufacturing_process_stages_id 
                                WHERE a.stage = '" . $row2["id"] . "' 
                                AND a.step_id = '" . $row3["id"] . "'
                            ";

                        $result4 = $conn->query($sql4);

                        if ($result4 && $result4->num_rows > 0) {
                            while ($row4 = $result4->fetch_assoc()) {
                                // Decode JSON fields
                                 $row4["procedures"] = json_decode($row4["procedure"]);
                                 $row4["Dispensing"] = json_decode($row4["Dispensing"]);
                                $row4["equipment"] = json_decode($row4["equipment"]);
                                $row4["CleaningChecks"] = json_decode($row4["CleaningChecks"]);
                                $row4["room"] = json_decode($row4["room"]);
                                $row4["weighing"] = json_decode($row4["weighing"]);
                                $row4["table"] = $row4["table"];
                                $row4["roomActions"] = json_decode($row4["roomActions"]);
                                $row4["instructions"] = json_decode($row4["instructions"]);
                                $row4["initial_checks"] = json_decode($row4["initial_checks"]);
                                $row4["environments"] = json_decode($row4["environments"]);
                                $row4["inprocess"] = json_decode($row4["inprocess"]);
                                $row4["EquipmemntCleaning"] = json_decode($row4["EquipmemntCleaning"]);
                                $row4["QcSample"] = json_decode($row4["QcSample"]);
                                $row4["Logbook"] = json_decode($row4["Logbook"]);
                                 $row4["Dispensning_lineclearance"] = json_decode($row4["dispensing_lineclearance"]);
                                $row4["sequence"] = json_decode($row4["sequence"]);

                                // Add the data to the output array
                               

                                // SQL query to check if the record already exists in bmr_stages table
                                $sql9 = "SELECT * FROM bmr_stages 
                                         WHERE stages_id = '" . $row4["id"] . "' 
                                         AND stage = '" . $row2["id"] . "' 
                                         AND step_id = '" . $row3["id"] . "'";
                                
                                $result9 = $conn->query($sql9);
                                
                                if ($result9 && $result9->num_rows > 0) {
                                    // Fetch and decode bmr_stages data
                                    while ($row9 = $result9->fetch_assoc()) {
                                        $row4["bmr_procedures"] = json_decode($row9["procedure"]);
                                        $row4["bmr_equipment"] = json_decode($row9["equipment"]);
                                        $row4["bmr_CleaningChecks"] = json_decode($row9["CleaningChecks"]);
                                        $row4["bmr_room"] = json_decode($row9["room"]);
                                        $row4["bmr_weighing"] = json_decode($row9["weighing"]);
                                        $row4["bmr_SIFTING_Lubrication"] = json_decode($row9["SIFTING_Lubrication"]);
                                        $row4["bmr_YIELD_RECONCILIATION"] = json_decode($row9["YIELD_RECONCILIATION"]);
                                        $row4["bmr_BlendLubrication"] = json_decode($row9["BlendLubrication"]);
                                                 $row4["bmr_COMPRESSION_PARAMETERS"] = json_decode($row9["COMPRESSION_PARAMETERS"]);
                                                 $row4["bmr_Weighing_Variation_Recoed"] = json_decode($row9["Weighing_Variation_Recoed"]);
                                                 $row4["bmr_millinsifting"] = json_decode($row9["millinsifting"]);
                                                 $row4["bmr_INPROCESS_YIELD"] = json_decode($row9["INPROCESS_YIELD"]);
                                                 $row4["bmr_Mixing"] = json_decode($row9["Mixing"]);
                                                 $row4["bmr_Drying"] = json_decode($row9["Drying"]);
                                                 $row4["bmr_Dispensing"] = json_decode($row9["Dispensing"]);
                                                 $row4["bmr_Dry_SIFTING_MILLING"] = json_decode($row9["Dry_SIFTING_MILLING"]);
                                                 $row4["bmr_QcSample"] = json_decode($row9["QcSample"]);
                                                 $row4["bmr_SieveInteggity"] = json_decode($row9["SieveInteggity"]);
                                                $row4["step_status"] = $row9["step_status"];
                                                $row4['bmr_procedure_status']=$row9['procedure_status'];
                                                $row4['bmr_weighing_status']=$row9['weighing_status'];
                                                $row4['bmr_equipment_status']=$row9['equipment_status'];
                                                $row4['bmr_Dispensing_status']=$row9['Dispensing_status'];
                                                $row4['bmr_QcSample_status']=$row9['QcSample_status'];
                                                $row4['bmr_LineClearance_status']=$row9['LineClearance_status'];
                                                $row4['bmr_ReactorCapacity_status']=$row9['ReactorCapacity_status'];
                                                $row4['bmr_INP_Transfer_status']=$row9['INP_Transfer_status'];
                                                $row4['bmr_PPT_Transfer_status']=$row9['PPT_Transfer_status'];
                                                $row4['bmr_WIPReport_status']=$row9['WIPReport_status'];
                                                $row4['bmr_DosingPh_status']=$row9['DosingPh_status'];
                                                $row4['bmr_Yield_status']=$row9['Yield_status'];
                                                $row4['bmr_YIELD_RECONCILIATION_status']=$row9['YIELD_RECONCILIATION_status'];
                                                $row4['bmr_BLENDING_SECTION_status']=$row9['BLENDING_SECTION_status'];
                                                $row4['bmr_EQUIPMENT_CLEANING_RECORD_status']=$row9['EQUIPMENT_CLEANING_RECORD_status'];
                                                $row4['bmr_TIME_CYCLES_DETAILS_status']=$row9['TIME_CYCLES_DETAILS_status'];
                                                $row4['bmr_MATERIAL_CONSUMPTION_DETAILS_status']=$row9['MATERIAL_CONSUMPTION_DETAILS_status'];
                                                $row4['bmr_Observation_status']=$row9['Observation_status'];
                                                $row4['bmr_ReactorCapacity']=$row9['ReactorCapacity'];
                                                $row4['bmr_INP_Transfer'] = json_decode($row9['INP_Transfer'], true) ?? [];
                                              $row4['bmr_PPT_Transfer'] = json_decode($row9['PPT_Transfer'], true) ?? [];
                                              $row4['bmr_WIPReport'] = json_decode($row9['WIPReport'], true) ?? [];
                                              $row4['bmr_DosingPh'] = json_decode($row9['DosingPh'], true) ?? [];
                                              $row4['bmr_Yield'] = json_decode($row9['YIELD_RECONCILIATION'], true) ?? [];
                                              $row4['bmr_YIELD_RECONCILIATION'] = json_decode($row9['Yield'], true) ?? [];
                                              $row4['bmr_BLENDING_SECTION'] = json_decode($row9['BLENDING_SECTION'], true) ?? [];
                                              $row4['bmr_EQUIPMENT_CLEANING_RECORD'] = json_decode($row9['EQUIPMENT_CLEANING_RECORD'], true) ?? [];
                                              $row4['bmr_TIME_CYCLES_DETAILS'] = json_decode($row9['TIME_CYCLES_DETAILS'], true) ?? [];
                                              $row4['bmr_MATERIAL_CONSUMPTION_DETAILS'] = json_decode($row9['MATERIAL_CONSUMPTION_DETAILS'], true) ?? [];
                                              $row4['bmr_Observation'] = json_decode($row9['Observation'], true) ?? [];

                                                
                                    }
                                     $output4 = $row4;
                                } else {
                                    $sequence = $row4["sequence"];
                                
                                // Check if the sequence is a JSON string, then decode it
                                if (is_string($sequence)) {
                                    $sequence = json_decode($sequence, true);
                                }
                                
                                // Convert the sequence back to JSON if needed (for insertion into the database)
                                $sequence = json_encode($sequence);
                                    // Insert the data into bmr_stages table
                                    $sql10 = "INSERT INTO bmr_stages (stage, step_id, product_code, work_order_no, stages_id,sequence) 
                                              VALUES ('" . $row2["id"] . "', '" . $row3["id"] . "', '" . $_GET["product_code"] . "', '" . $_GET["work_order_no"] . "', '" . $row4["id"] . "', '$sequence')";
                                    
                                    if ($conn->query($sql10)) {
                                        // Re-fetch the newly inserted data
                                        $result9 = $conn->query($sql9);
                                        
                                        if ($result9 && $result9->num_rows > 0) {
                                            while ($row9 = $result9->fetch_assoc()) {
                                                 $row4["bmr_procedures"] = json_decode($row9["procedure"]);
                                                $row4["bmr_equipment"] = json_decode($row9["equipment"]);
                                                // $row4["bmr_ReactorCapacity"] = json_decode($row9["ReactorCapacity"]);
                                                $row4["bmr_CleaningChecks"] = json_decode($row9["CleaningChecks"]);
                                                $row4["bmr_room"] = json_decode($row9["room"]);
                                               
                                                
                                                
                                             
                                            }
                                        }
                                    } else {
                                        // Handle insertion error
                                        echo "Error inserting data: " . $conn->error;
                                    }
                                }
                
                            }
                        }

                        // Add the $output4 data to $output3
                        $output3[] = $output4;
                    }
                }

                // Assigning the output3 array to the current stage
                $row2["Steps"] = $output3;
                $output2[] = $row2; // Adding the current stage to the output2 array
            }
        }

        $row["Stages"] = $output2; // Assigning the output2 array to the current manufacturing process
        $output[] = $row; // Adding the current manufacturing process to the main output array
    }
}

// Encode the output array into JSON format
echo json_encode($output);

         } 
    else if ($_GET["type"] == "savebmrproducts") {
        
        
         $sql="SELECT * FROM bmr_products WHERE product_code='".$input["product"]."'
          AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"product Already Exists. Duplicate Values are not allowed\"}";
        }else{
          $sql = "INSERT INTO bmr_products (plant_id,product_code, dosage_form)values('".$_GET["plant_id"]."','".$input["product"]."','".$input["dosage_form"]."')";

            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
        
    }
    else if ($_GET["type"] == "getProcess_types") {
	$output = Array();

     	      $sql="SELECT process_type FROM manufacturing_process WHERE dosage_form='".$_GET["dosage_form"]."'
          AND plant_id ='".$_GET["plant_id"]."' group by process_type ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    else if ($_GET["type"] == "getProcess_stage") {
	$output = Array();

     	      $sql="SELECT stage FROM manufacturing_process WHERE dosage_form='".$_GET["dosage_form"]."' and process_type='".$_GET["process_type"]."'
          AND plant_id ='".$_GET["plant_id"]."' group by stage ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    else if ($_GET["type"] == "getProcess_step") {
	$output = Array();

     	      $sql="SELECT step FROM manufacturing_process WHERE dosage_form='".$_GET["dosage_form"]."' and process_type='".$_GET["process_type"]."'
        and stage='".$_GET["stage"]."' AND plant_id ='".$_GET["plant_id"]."' group by step ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
    // else if ($_GET["type"] == "savebmrproducts") {
        
    //      $sql = "INSERT INTO bmr_products (plant_id,product_code, dosage_form)values('".$_GET["plant_id"]."','".$input["product"]."','".$input["dosage_form"]."')";
    //           if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //      }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
   
    // }
         
    
    else if ($_GET["type"] == "saveProcessmaster") {
        
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
   $sql="SELECT * FROM process_types WHERE process_type='".$input["process_type"]."' and dosage_form='".$input["dosage_form"]."' 
          AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Process Type Already Exists. Duplicate Values are not allowed\"}";
        }else{


$entery_date = date('Y-m-d H:i:s');

$sql = "INSERT INTO process_types(plant_id, process_type, dosage_form, entry_by, entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["process_type"]."','".$input["dosage_form"]."','".$_GET["emp_id"]."','$entery_date')";

if ($conn->query($sql)) {
    $process_type_id = $conn->insert_id;    
    
    for ($i = 0; $i < count($input['stage']); $i++) {
        $data = $input['stage'][$i];
        
        $sql1 = "INSERT INTO process_stages(forms_list,plant_id,process_type_id, process_type, dosage_form, stage, entry_by, entry_date) 
                 VALUES ('".json_encode($data["forms"])."','".$_GET["plant_id"]."','$process_type_id','".$input["process_type"]."','".$input["dosage_form"]."','".$data["stage"]."','".$_GET["emp_id"]."','$entery_date')";
        
        if ($conn->query($sql1)) {
            $process_stage_id = $conn->insert_id;
            
            for ($j = 0; $j < count($data['step']); $j++) {
                $data1 = $data['step'][$j];
                
                $sql2 = "INSERT INTO process_stages_steps(process_stages_id, plant_id, step, ipqc_test, inprocess_checks, line_clearance) 
                         VALUES ('$process_stage_id','".$_GET['plant_id']."','".$data1['step']."','".$data1['ipqc_test']."','".$data1['inprocess_checks']."','".$data1['line_clearance']."')";
                
                $conn->query($sql2);
            }
        }
    }
    
    echo "{\"status\":\"success\"}";
} else {
    echo "{\"status\":\"".$conn->error."\"}";
}

    }
    }
    else if ($_GET["type"] == "saveProcessmasterZuma") {$conn->begin_transaction();

try {

    $sql="SELECT * FROM process_types 
          WHERE process_type='".$input["process_type"]."' 
          AND dosage_form='".$input["dosage_form"]."' 
          AND plant_id ='".$_GET["plant_id"]."'";

    $result =$conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode(["status"=>"Process Type Already Exists"]);
        exit;
    }

    $entery_date = date('Y-m-d H:i:s');

    $sql = "INSERT INTO process_types(plant_id, process_type, dosage_form, entry_by, entry_date) 
            VALUES ('".$_GET["plant_id"]."','".$input["process_type"]."','".$input["dosage_form"]."','".$_GET["emp_id"]."','$entery_date')";

    if (!$conn->query($sql)) {
        throw new Exception($conn->error);
    }

    $process_type_id = $conn->insert_id;

    // 🔁 STAGE LOOP
    foreach ($input['stage'] as $data) {

        $forms = json_encode($data["forms"] ?? []);

        $sql1 = "INSERT INTO process_stages(forms_list,plant_id,process_type_id, process_type, dosage_form, stage, entry_by, entry_date) 
                 VALUES ('$forms','".$_GET["plant_id"]."','$process_type_id','".$input["process_type"]."','".$input["dosage_form"]."','".$data["stage"]."','".$_GET["emp_id"]."','$entery_date')";

        if (!$conn->query($sql1)) {
            throw new Exception($conn->error);
        }

        $process_stage_id = $conn->insert_id;

        // 🔁 STEP LOOP
        foreach ($data['steps'] as $data1) {

            $step = $data1['step'] ?? '';
            $ipqc = $data1['ipqc_test'] ?? '';
            $inprocess = $data1['inprocess_checks'] ?? '';
            $line = $data1['line_clearance'] ?? '';

            $sql2 = "INSERT INTO process_stages_steps(process_stages_id, plant_id, step, ipqc_test, inprocess_checks, line_clearance) 
                     VALUES ('$process_stage_id','".$_GET['plant_id']."','$step','$ipqc','$inprocess','$line')";

            if (!$conn->query($sql2)) {
                throw new Exception($conn->error);
            }

            $process_stage_step_id = $conn->insert_id;

            // 🔁 SUBSTEP LOOP
            if (!empty($data1['Substeps'])) {

                foreach ($data1['Substeps'] as $sub) {

                    $substep = $sub['Substep'] ?? '';

                    $sql3 = "INSERT INTO process_stages_steps_substep(
                                process_stages_id,
                                process_stages_steps_id,
                                plant_id,
                                Substep,
                                data_for
                            ) VALUES (
                                '$process_stage_id',
                                '$process_stage_step_id',
                                '".$_GET['plant_id']."',
                                '$substep',
                                'BMR'
                            )";

                    if (!$conn->query($sql3)) {
                        throw new Exception($conn->error);
                    }
                }
            }
        }
    }

    // ✅ ALL GOOD
    $conn->commit();

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {

    // ❌ ROLLBACK ON ERROR
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}}
    else if ($_GET["type"] == "update_bmr_satgesProduction") {
        $status='is'.$input['substep'];
        $column=$input['substep'];
        $entry_by=$input['substep'].'_entry_by';
        $entryDate=$input['substep'].'_entry_on';
        $approve_by=$input['substep'].'_approved_by';
        $approve_date=$input['substep'].'_approved_on';
        $Actualstatus=$input['substep'].'_status';
        $Data=json_encode($input['Data']);
        
        if($input['type']=='Draft'){
       echo  $sql="update bmr_stages set $Actualstatus=0, $status=0,`$column`='$Data',$entry_by='".$_GET['emp_id']."',$entryDate='$entry_date' where stage='".$input['stage']."' and step_id='".$input['step_id']."'";
        }else{
        echo $sql="update bmr_stages set $Actualstatus=1, $status=1,`$column`='$Data',$approve_by='".$_GET['emp_id']."',$approve_date='$entry_date' where stage='".$input['stage']."' and step_id='".$input['step_id']."'";
        }
     
         
if ($conn->query($sql)) {
    
    
    echo "{\"status\":\"success\"}";
} else {
    echo "{\"status\":\"".$conn->error."\"}";
}
    }
    else if ($_GET["type"] == "update_bmr_satges") {
         $sql = "UPDATE bmr_stages
SET `step_status`='saved',
    `procedure` = '".json_encode($input['procedures'])."',
    `LineClearance` = '".json_encode($input['lineclearance'])."',
    `room` = '".json_encode($input['room'])."',
    `equipment` = '".json_encode($input['equipmentsss'])."',
    `CleaningChecks` = '".json_encode($input['CleaningChecks'])."',
    `roomActions` = '".json_encode($input['roomActions'])."',
    `EquipmemntCleaning` = '".json_encode($input['EquipmemntCleaning'])."',
    `weighing` = '".json_encode($input['weighings'])."',
    `QcSample` = '".json_encode($input['isQcSample_list'])."',
    `YIELD_RECONCILIATION` = '".json_encode($input['YIELD_RECONCILIATION_List'])."',
    `INPROCESS_YIELD` = '".json_encode($input['isINPROCESS_YIELD_List'])."',
    `SIFTING_Lubrication` = '".json_encode($input['SiftLubrication_List'])."',
    `Dry_SIFTING_MILLING` = '".json_encode($input['SIFTING_MILLING_details'])."',
    `Mixing` = '".json_encode($input['mixing_details'])."',
    `Dispensing` = '".json_encode($input['Dispensing'])."',
    `Weighing_Variation_Recoed` = '".json_encode($input['Weighing_Variation_Recoed_List'])."',
    `COMPRESSION_PARAMETERS` = '".json_encode($input['COMPRESSION_PARAMETERS_List'])."',
    `BlendLubrication` = '".json_encode($input['BlendLubrication_List'])."',
    `Drying` = '".json_encode($input['isDrying_List'])."',
    `millinsifting` = '".json_encode($input['ismillinsifting_List'])."',
    `SieveInteggity` = '".json_encode($input['isSieveInteggity_List'])."',
    `Yield` = '".json_encode($input['isYield_List'])."',
    `entry_by`='".$_GET['emp_id']."',
    `entry_date`='$entry_date'
 WHERE stage = '".$input['stage_id']."' and step_id = '".$input['step_id']."'";

if ($conn->query($sql)) {
    
    
    echo "{\"status\":\"success\"}";
} else {
    echo "{\"status\":\"".$conn->error."\"}";
}
    }
  else if ($_GET["type"] == "saveProcess") {
        
        
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        
         $sql="SELECT * FROM manufacturing_process WHERE DocumentTitle='".$input["ProcessTitle"]."'
          AND DocumentNo='".$input["DocumentNo"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
      // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Process Type Already Exists. Duplicate Values are not allowed\"}";
        }else{
        $flag = 0;
         $sql = "INSERT INTO manufacturing_process (plant_id,DocumentTitle,DocumentNo,product_code,Forms) VALUES ('".$_GET["plant_id"]."','".$input["ProcessTitle"]."','".$input["DocumentNo"]."','".$input["product_code"]."','".json_encode($input["Forms"])."')";
            if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $stagess=$input['stages'];
        for ($i = 0; $i < count($stagess); $i++) {
            $data = $stagess[$i];
             $sql = "INSERT INTO manufacturing_process_stages (forms_list,plant_id,manufacturing_process_id, stages,for_department)
                        VALUES ('".$data['forms_list']."','".$_GET["plant_id"]."','$last_id','".$data["stage"]."','".$input["for_department"]."')";
            if ($conn->query($sql)) {
                $stage_id = $conn->insert_id; // Get the last inserted stage ID
        
        // Assuming $data["steps"] is an array of steps
        foreach ($data["steps"] as $step) {
            $sql = "INSERT INTO manufacturing_process_step (plant_id, manufacturing_process_stages_id, step,checking_in,split_lot) VALUES ('".$_GET["plant_id"]."', '$stage_id', '".$step["step"]."','".$step["Checking_in"]."', '".$step["split_lot"]."')";
            if (!$conn->query($sql)) {
                $flag = 0;
                break; // Exit the loop if there's an error
            }

            $step_id = $conn->insert_id;
            $substeps = isset($step["Substeps"]) && is_array($step["Substeps"]) ? $step["Substeps"] : [];

            foreach ($substeps as $substepData) {
                $substepName = "";
                if (is_array($substepData)) {
                    if (isset($substepData["substep"])) {
                        $substepName = trim($substepData["substep"]);
                    } else if (isset($substepData["Substep"])) {
                        $substepName = trim($substepData["Substep"]);
                    }
                } else if (is_string($substepData)) {
                    $substepName = trim($substepData);
                }

                if ($substepName === "") {
                    continue;
                }

                $sqlSub = "INSERT INTO  manufacturing_process_step_substep (manufacturing_process_stages_id, manufacturing_process_step_id, plant_id, Substep, entry_by, entry_date)
                           VALUES ('$stage_id', '$step_id', '".$_GET["plant_id"]."', '".$substepName."', '".$_GET["emp_id"]."', '$entry_date')";
                if (!$conn->query($sqlSub)) {
                    $flag = 0;
                    break;
                }
            }

            if ($flag == 0) {
                break;
            }
        }
                
                $flag = 1;
            } else {
                $flag = 0;
                break;
            }
        }
            }
        if ($flag == 1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
        }
    
        
 
    }
    else if ($_GET["type"] == "get_stage_step") {
        
          $output = Array();
          $sql="SELECT stage FROM manufacturing_process WHERE  plant_id ='".$_GET["plant_id"]."' and inprocess_checks='Applicable'  group by stage";
        $result =$conn->query($sql);

        if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT step FROM manufacturing_process WHERE stage='".$row["stage"]."'  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                       
                        $row["step"] = $output1;
                        $output[] = $row;
                }
            }   echo json_encode($output);
    }
    else if ($_GET["type"] == "GET_SAVEgEN_INSTRUCTION") {
        
	$output = Array();

     	  $sql = "select * from ebmr_genral_instruction";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "GET_adddisp_chek") {
        
	$output = Array();

     	  $sql = "select * from pm_disp_checklist";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "GET_quality_sample_cheklist") {
        
	$output = Array();

     	  $sql = "select * from quality_sample_cheklist";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "SAVEgEN_INSTRUCTION_demo") {      
        $json_obj = json_encode($input["instruction"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO ebmr_genral_instruction( plant_id, instruction,product_code) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "SAVEgEN_INSTRUCTION") {
    
            $sql = "INSERT INTO ebmr_genral_instruction( plant_id, instruction) VALUES ('".$_GET["plant_id"]."','".$input["Particular"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "adddisp_chek") {
    
            $sql = "INSERT INTO pm_disp_checklist( plant_id, checkpoint,remark) VALUES ('".$_GET["plant_id"]."','".$input["checkpoint"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "addquality_sample_chek") {
          
            $sql = "INSERT INTO quality_sample_cheklist( plant_id, parameter) VALUES ('".$_GET["plant_id"]."','".$input["checkpoint"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "deldisp_chek") {
    
            $sql = "DELETE FROM pm_disp_checklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "delquality_sample_cheklist") {
    
            $sql = "DELETE FROM quality_sample_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
   
    }
    else if ($_GET["type"] == "DelSAVEgEN_INSTRUCTION") {
    
            $sql = "DELETE FROM ebmr_genral_instruction where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "GET_SAVEequipment") {
        
	$output = Array();

     	  $sql = "select * from bmr_Equipment_data";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "SAVE_bmr_Equipment_data") {
    
            $sql = "INSERT INTO bmr_Equipment_data( plant_id, capacity,equipment_code,equipment_name) VALUES ('".$_GET["plant_id"]."','".$input["capacity"]."','".$input["equipment_code"]."','".$input["equipment_name"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
        else if ($_GET["type"] == "Delbmr_Equipment_data") {
    
            $sql = "DELETE FROM bmr_Equipment_data where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "GET_warehouse_dis") {
        
	$output = Array();

     	  $sql = "select * from bmr_warehouse_dispensing_checklist";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    }
    else if ($_GET["type"] == "SAVEwarehouse_dis") {
    
            $sql = "INSERT INTO bmr_warehouse_dispensing_checklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["Description"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
//         else if ($_GET["type"] == "saveLinemaster") {
            
//             $json_obj = json_encode($input["groupList"]);
// $array = json_decode($json_obj, true);

// $status1 = false;

// foreach ($array as $values) {

//     // Insert into linemaster
//     $sql = "INSERT INTO linemaster (line_no, Section, group_name, LineCapacity,
//             Output, batchOperations, equipmentList, enrty_by, plant_id, Stage,lineType)
//             VALUES (
//             '".$values["line_no"]."',
//             '".$values["Section"]."',
//             '".$values["group"]."',
//             '".$values["LineCapacity"]."',
//             '".$values["Output"]."',
//             '".$values["batchOperations"]."',
//             '".json_encode($values["equipmentList"])."',
//             '".$_GET["emp_id"]."',
//             '".$_GET["plant_id"]."',
//             '".$values["Stage"]."'
//             '".$values["Type"]."'
//         )";

//     if ($conn->query($sql)) {

//         // GET LAST INSERTED ID (Very Important!)
//         $last_id = $conn->insert_id;

//         // Insert mapped equipment
//         foreach ($values["equipmentList"] as $values2) {

//              $sql2 = "INSERT INTO linemaster_mapped_Equipment 
//             (`linemaster_id`, equipment_name, equipment_code, capacity, from_range, to_range, unit) 
//             VALUES (
//                 '".$last_id."',
//                 '".$values2['equipment_name']."',
//                 '".$values2['equipment_code']."',
//                 '".$values2['capacity']."',
//                 '".$values2['from_range']."',
//                 '".$values2['to_range']."',
//                 '".$values2['unit']."'
//             )";

//             $conn->query($sql2);
//         }

//         $status1 = true;

//     } else {
//         $status1 = false;
//         break; // stop on first error
//     }
// }

// if ($status1) {
//     echo "{\"status\":\"success\"}";
// } else {
//     echo "{\"status\":\"".$conn->error."\"}";
// }

//         }
else if ($_GET["type"] == "saveLinemaster") {
    
    $input = json_decode(file_get_contents('php://input'), true);
$array = isset($input["groupList"]) ? $input["groupList"] : [];

$status1 = false;

foreach ($array as $values) {

    // ---------------- BASIC FIELDS ----------------
    $line_no   = mysqli_real_escape_string($conn, $values["line_no"] ?? '');
    $line_name = mysqli_real_escape_string($conn, $values["line_name"] ?? '');
    $section   = mysqli_real_escape_string($conn, $values["Section"] ?? '');
    $group     = mysqli_real_escape_string($conn, $values["group"] ?? '');
    $batchOps  = mysqli_real_escape_string($conn, $values["batchOperations"] ?? '');
    $stage     = mysqli_real_escape_string($conn, $values["Stage"] ?? '');
    $lineType  = mysqli_real_escape_string($conn, $values["Type"] ?? '');

    // capacities
    $mfgMin  = mysqli_real_escape_string($conn, $values["MfgLineMinCapacity"] ?? '');
    $mfgMax  = mysqli_real_escape_string($conn, $values["MfgLineMaxCapacity"] ?? '');
    $fillMin = mysqli_real_escape_string($conn, $values["FillingLineMinCapacity"] ?? '');
    $fillMax = mysqli_real_escape_string($conn, $values["FillingLineMaxCapacity"] ?? '');

    // optional old fields
    $lineCapacity = mysqli_real_escape_string($conn, $values["LineCapacity"] ?? '');
    $output       = mysqli_real_escape_string($conn, $values["Output"] ?? '');

    // ---------------- EQUIPMENT ----------------
    $equipmentList = isset($values["equipmentList"]) ? $values["equipmentList"] : [];
    $equipmentJson = mysqli_real_escape_string($conn, json_encode($equipmentList));

    // ---------------- STAGES (FIXED) ----------------
    // NOTE: payload key is "Stages"
    $selectedStages = isset($values["selectedStages"]) ? $values["selectedStages"] : [];

    // ---------------- INSERT LINEMASTER ----------------
    $sql = "
        INSERT INTO linemaster (
            line_no,
            line_name,
            Section,
            group_name,
            LineCapacity,
            Output,
            batchOperations,
            equipmentList,
            MfgLineMinCapacity,
            MfgLineMaxCapacity,
            FillingLineMinCapacity,
            FillingLineMaxCapacity,
            Stage,
            enrty_by,
            plant_id,
            lineType,
            status
        ) VALUES (
            '$line_no',
            '$line_name',
            '$section',
            '$group',
            '$lineCapacity',
            '$output',
            '$batchOps',
            '$equipmentJson',
            '$mfgMin',
            '$mfgMax',
            '$fillMin',
            '$fillMax',
            '$stage',
            '".$_GET["emp_id"]."',
            '".$_GET["plant_id"]."',
            '$lineType',
            'Pending'
        )
    ";

    if (!$conn->query($sql)) {
        echo json_encode(["status" => $conn->error]);
        exit;
    }

    $last_id = $conn->insert_id;

    // ---------------- INSERT EQUIPMENT MAPPING ----------------
    foreach ($equipmentList as $eq) {

        $eq_name   = mysqli_real_escape_string($conn, $eq['equipment_name'] ?? '');
        $eq_code   = mysqli_real_escape_string($conn, $eq['equipment_code'] ?? '');
        $capacity2 = mysqli_real_escape_string($conn, $eq['capacity'] ?? '');
        $from_rng  = mysqli_real_escape_string($conn, $eq['from_range'] ?? '');
        $to_rng    = mysqli_real_escape_string($conn, $eq['to_range'] ?? '');
        $unit      = mysqli_real_escape_string($conn, $eq['unit'] ?? '');

        $sql2 = "
            INSERT INTO linemaster_mapped_Equipment
                (linemaster_id, equipment_name, equipment_code, capacity, from_range, to_range, unit)
            VALUES
                ('$last_id', '$eq_name', '$eq_code', '$capacity2', '$from_rng', '$to_rng', '$unit')
        ";

        $conn->query($sql2);
    }

    // ---------------- INSERT GROUP-STAGE MAPPING (FIXED) ----------------
    foreach ($selectedStages as $st) {

        $dosage_form = mysqli_real_escape_string($conn, $st['dosage_form'] ?? '');
        $stageName   = mysqli_real_escape_string($conn, $st['stage'] ?? '');

        if ($dosage_form === '' || $stageName === '') {
            continue;
        }

        $sqlS = "
            INSERT INTO linemaster_groups_stages
                (linemaster_id, dosage_form, stage)
            VALUES
                ('$last_id', '$dosage_form', '$stageName')
        ";

        $conn->query($sqlS);
    }

    $status1 = true;
}

if ($status1) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "failed"]);
}
}
        else if ($_GET["type"] == "Delbmr_SAVEwarehouse_dis") {
    
            $sql = "DELETE FROM bmr_warehouse_dispensing_checklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    }
    else if ($_GET["type"] == "updateMapedProduct") {
        
      

    $linemaster_id = $input["id"];
    $newArray = $input["mappedProduct"]; // Already array in Angular

    // ---------------------------------------
    // 1. Convert new array product_codes into simple array
    // ---------------------------------------
    $newCodes = [];
    foreach ($newArray as $item) {
        $newCodes[] = "'" . $item["product_code"] . "'";
    }

    $newCodeList = implode(",", $newCodes);
    if ($newCodeList == "") {
        $newCodeList = "''"; // avoid SQL error
    }

    // ---------------------------------------
    // 2. DELETE records NOT present in new list
    // ---------------------------------------
    $deleteSQL = "
        DELETE FROM linemaster_mapped_Product
        WHERE linemaster_id = '$linemaster_id'
        AND product_code NOT IN ($newCodeList)
    ";
    $conn->query($deleteSQL);

    // ---------------------------------------
    // 3. Fetch existing mapped product codes
    // ---------------------------------------
    $existingCodes = [];
    $sqlGet = "SELECT product_code FROM linemaster_mapped_Product 
               WHERE linemaster_id = '$linemaster_id'";
    $resultGet = $conn->query($sqlGet);
    if ($resultGet->num_rows > 0) {
        while ($row = $resultGet->fetch_assoc()) {
            $existingCodes[] = $row["product_code"];
        }
    }

    // ---------------------------------------
    // 4. INSERT new ones (only those not already in DB)
    // ---------------------------------------
    $insertStatus = true;

    foreach ($newArray as $values) {

        if (!in_array($values["product_code"], $existingCodes)) {

            $sqlInsert = "
                INSERT INTO linemaster_mapped_Product
                (linemaster_id, product_name, product_code, category, dosage_form, generic_name)
                VALUES (
                    '$linemaster_id',
                    '".$values["product_name"]."',
                    '".$values["product_code"]."',
                    '".$values["category"]."',
                    '".$values["dosage_form"]."',
                    '".$values["generic_name"]."'
                )
            ";

            if (!$conn->query($sqlInsert)) {
                $insertStatus = false;
            }
        }
    }

    // ---------------------------------------
    // 5. Response
    // ---------------------------------------
    if ($insertStatus) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }


    }
 else if ($_GET["type"] == "stageLinemasterLog") {

    $output = [];

    // Get all lines
    $sql = "SELECT * FROM linemaster ORDER BY id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            $id = $row['id'];

            /* ---------------------------------
               1️⃣ EQUIPMENT LIST
            --------------------------------- */
            $equipmentList = [];
            $sqlEq = "SELECT * FROM linemaster_mapped_Equipment 
                      WHERE linemaster_id = '".$id."'";
            $resEq = $conn->query($sqlEq);
            if ($resEq && $resEq->num_rows > 0) {
                while ($rowEq = $resEq->fetch_assoc()) {
                    $equipmentList[] = $rowEq;
                }
            }

            /* ---------------------------------
               2️⃣ MAPPED PRODUCTS (IF USED)
            --------------------------------- */
            $mappedProduct = [];
            $sqlMp = "SELECT * FROM linemaster_mapped_Product 
                      WHERE linemaster_id = '".$id."'";
            $resMp = $conn->query($sqlMp);
            if ($resMp && $resMp->num_rows > 0) {
                while ($rowMp = $resMp->fetch_assoc()) {
                    $mappedProduct[] = $rowMp;
                }
            }

            /* ---------------------------------
               3️⃣ PRODUCT LIST BY DOSAGE FORM
            --------------------------------- */
            $product_list = [];
            $dosageForm = $row["group_name"];
            if ($dosageForm != '') {
                $sqlPl = "SELECT * FROM product a left join linemaster_groups_stages b on a.dosage_form=b.dosage_form WHERE b.linemaster_id= '".$id."'";
            
                $resPl = $conn->query($sqlPl);
                if ($resPl && $resPl->num_rows > 0) {
                    while ($rowPl = $resPl->fetch_assoc()) {
                        $product_list[] = $rowPl;
                    }
                }
            }

            /* ---------------------------------
               4️⃣ STAGES FROM linemaster_groups_stages ✅
            --------------------------------- */
            $stages = [];
            $sqlSt = "
                SELECT dosage_form, stage 
                FROM linemaster_groups_stages 
                WHERE linemaster_id = '".$id."'
                ORDER BY dosage_form, stage
            ";
            $resSt = $conn->query($sqlSt);
            if ($resSt && $resSt->num_rows > 0) {
                while ($rowSt = $resSt->fetch_assoc()) {
                    
                                  $product_list = [];

                                    
                                        $sqlPl = "
                                            SELECT product_code, dosage_form, category, generic_name, product_name 
                                            FROM product  
                                            WHERE dosage_form = '".$rowSt['dosage_form']."'
                                        ";
                                        $resPl = $conn->query($sqlPl);
                                        if ($resPl && $resPl->num_rows > 0) {
                                            while ($rowPl = $resPl->fetch_assoc()) {
                                                $product_list[] = $rowPl;
                                            }
                                        }
                                  
                    
                    $stages[] = $rowSt;
                }
            }

            /* ---------------------------------
               ATTACH ALL DATA
            --------------------------------- */
            $row["equipmentList"] = $equipmentList;
            $row["mappedProduct"] = $mappedProduct;
            $row["product_list"]  = $product_list;
            $row["stages"]        = $stages;

            $output[] = $row;
        }
    }

    echo json_encode($output);
}

    else if ($_GET["type"] == "getLinemasterForChecking" || $_GET["type"] == "getLinemasterForApproval") {
        $output = [];
        $wantStatus = ($_GET["type"] == "getLinemasterForChecking") ? "Pending" : "Checked";
        $sql = "SELECT * FROM linemaster WHERE status = '".mysqli_real_escape_string($conn, $wantStatus)."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $equipmentList = [];
                $sqlEq = "SELECT * FROM linemaster_mapped_Equipment WHERE linemaster_id = '".$id."'";
                $resEq = $conn->query($sqlEq);
                if ($resEq && $resEq->num_rows > 0) {
                    while ($rowEq = $resEq->fetch_assoc()) {
                        $equipmentList[] = $rowEq;
                    }
                }
                $stages = [];
                $sqlSt = "SELECT dosage_form, stage FROM linemaster_groups_stages WHERE linemaster_id = '".$id."' ORDER BY dosage_form, stage";
                $resSt = $conn->query($sqlSt);
                if ($resSt && $resSt->num_rows > 0) {
                    while ($rowSt = $resSt->fetch_assoc()) {
                        $stages[] = $rowSt;
                    }
                }
                $row["equipmentList"] = $equipmentList;
                $row["stages"] = $stages;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "updateLinemasterStatus") {
        $id = intval($_GET["id"] ?? 0);
        $status = mysqli_real_escape_string($conn, $_GET["status"] ?? '');
        $allowed = array("Pending", "Checked", "Approved", "Rejected");
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            echo "{\"status\":\"invalid\"}";
        } else {
            $sql = "UPDATE linemaster SET status = '".$status."' WHERE id = '".$id."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }

    else if ($_GET["type"] == "GET_disp_chek") {
        	$output = Array();
     	  $sql = "select * from bmr_dispensing_cheklist";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "SAVE_disp_chek") {
            $sql = "INSERT INTO bmr_dispensing_cheklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["checkpoint"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "Del_disp_chek") {
            $sql = "DELETE FROM bmr_dispensing_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getStagemasterLine") {
        	$output = Array();
     	  $sql = "select * from process_stages where dosage_form='".$_GET["dosage_form"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "GET_line_chek") {
        	$output = Array();
     	  $sql = "select * from bmr_lineclearance_cheklist";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "SAVE_line_chek") {
            $sql = "INSERT INTO bmr_lineclearance_cheklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["Description"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "Del_line_chek") {
            $sql = "DELETE FROM bmr_lineclearance_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "GET_line_chek_process") {
        	$output = Array();
     	  $sql = "select * from bmr_lineclearance_process_cheklist";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "SAVE_line_chek_process") {
            $sql = "INSERT INTO bmr_lineclearance_process_cheklist( plant_id, checkpoint	,remark) VALUES ('".$_GET["plant_id"]."','".$input["Description"]."','".$input["Remark"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "Del_line_chek_process") {
            $sql = "DELETE FROM bmr_lineclearance_process_cheklist where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "SAVE_oprp_chek") {
            $sql = "INSERT INTO oprp_room( plant_id, room) VALUES ('".$_GET["plant_id"]."','".$input["room"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
            $sql1 = "INSERT INTO oprp_room_details(oprp_room_id,temp,humidity) VALUES ('$product_id','".$input["temp"]."','".$input["humidity"]."')";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        else if ($_GET["type"] == "GET_oprp_chek") {
        	$output = Array();
     	  $sql = "select * from oprp_room";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM oprp_room_details WHERE oprp_room_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprp_room_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
        else if ($_GET["type"] == "GET_oprp_chek2") {
        	$output = Array();
     	  $sql = "select * from oprpccp_equip2 where work_order_id='".$_GET["work_id"]."' and section='".$_GET["section"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM oprpccp_equip_dtl2 WHERE oprpccp_equip2_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprpccp_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
      else if ($_GET["type"] == "Del_room") {
            $sql = "DELETE FROM oprp_room where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
             $sql1 = "DELETE FROM oprp_room_details where oprp_room_id='".$_GET["id"]."'";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "delete_oprp_ccp2") {
            $sql = "DELETE FROM oprpccp_equip2 where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
             $sql1 = "DELETE FROM oprpccp_equip_dtl2 where oprpccp_equip2_id='".$_GET["id"]."'";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "Del_room11") {
            $sql = "DELETE FROM oprpccp_equip where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
             $sql1 = "DELETE FROM oprpccp_equip_dtl where oprpccp_equip_id='".$_GET["id"]."'";
             $conn->query($sql1);
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "getblender") {
        	$output = Array();
     	  $sql = "SELECT * FROM equipment WHERE equipment_name like '%blender%'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
     else if ($_GET["type"] == "getsifterr") {
        	$output = Array();
     	  $sql = "SELECT * FROM equipment WHERE equipment_name like '%sifter%'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
     else if ($_GET["type"] == "saveoprpccp2") {
                $sql = "INSERT INTO oprpccp_equip2( plant_id, equipment,work_order_id,section,sp_bmr_sifting_id	) VALUES ('".$_GET["plant_id"]."','".$input["equipment"]."','".$_GET["work_id"]."','".$_GET["section"]."','".$_GET["sift_id"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
 
                     $sql1 = "INSERT INTO oprpccp_equip_dtl2(oprpccp_equip2_id, airpressure, cleanliness_discharge_channel, cleanliness_hoper, film_folds, heater_working,
        humidity, seal_cleanliness, seal_strength, sensitivity, tmep, wad_film_folds, wad_heater_working, wad_seal_cleanliness, wad_seal_strength, time,
        obervation, remark, fe, non_fe, ss) VALUES ( '$product_id','".$input["airpressure"]."',
        '".$input["cleanliness_discharge_channel"]."','".$input["cleanliness_hoper"]."','".$input["film_folds"]."','".$input["heater_working"]."',
'".$input["humidity"]."','".$input["seal_cleanliness"]."','".$input["seal_strength"]."','".$input["sensitivity"]."','".$input["temp"]."',
'".$input["wad_film_folds"]."','".$input["wad_heater_working"]."','".$input["wad_seal_cleanliness"]."','".$input["wad_seal_strength"]."',
'".$input["time"]."','".$input["observation"]."','".$input["remark"]."','".$input["FE"]."','".$input["NON_FE"]."','".$input["SS"]."')";
             $conn->query($sql1);
                
           
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "saveoprpccp") {
            $sql = "INSERT INTO oprpccp_equip( plant_id, blender,sifter,checkpoint,cleanliness) VALUES ('".$_GET["plant_id"]."','".$input["blender"]."','".$input["sifter"]."','".$input["checkpoint"]."','".$input["Cleanliness"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
             $json_obj = json_encode($input["ccrp_Checklist"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                     $sql1 = "INSERT INTO oprpccp_equip_dtl(oprpccp_equip_id,sieves,mesh_size) VALUES ('$product_id','".$values["sieves"]."','".$values["mesh_size"]."')";
             $conn->query($sql1);
                }
           
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "get_saveoprpccp") {
        	$output = Array();
     	  $sql = "select * from oprpccp_equip";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM oprpccp_equip_dtl WHERE oprpccp_equip_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprpccp_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
      else if ($_GET["type"] == "SAVE_bmr_sifting") {
            $sql = "INSERT INTO  bmr_sifting( plant_id, sift_end_time	,sift_start_time,	siftter_equip) VALUES ('".$_GET["plant_id"]."','".$input["sift_end_time"]."','".$input["sift_start_time"]."','".$input["sifter_equip"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "SAVE_bmr_blending") {
            $sql = "INSERT INTO  bmr_blending( plant_id, blend_end_time	,blend_start_time,	blender,Processing) VALUES ('".$_GET["plant_id"]."','".$input["blend_end_time"]."','".$input["blend_start_time"]."','".$input["blender"]."','".$input["Processing"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    //   else if ($_GET["type"] == "SAVE_bmr_blending") {
    //         $sql = "INSERT INTO  bmr_blending( plant_id, blend_end_time	,blend_start_time,	blender,Processing) VALUES ('".$_GET["plant_id"]."','".$input["blend_end_time"]."','".$input["blend_start_time"]."','".$input["blender"]."','".$input["Processing"]."')";
    //      if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //      }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
      else if ($_GET["type"] == "get_savebmr_blend") {
        	$output = Array();
     	  $sql = "SELECT * FROM bmr_blending ORDER BY Processing ASC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
      else if ($_GET["type"] == "get_savebmr_sift") {
        	$output = Array();
     	  $sql = "SELECT * FROM bmr_sifting ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
         else if ($_GET["type"] == "del_blend") {
            //  echo('hello');
            $sql = "DELETE FROM bmr_blending where id='".$_GET["id"]."' " ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
         else if ($_GET["type"] == "del_sift") {
            //  echo('hello');
            $sql = "DELETE FROM bmr_blending where id='".$_GET["id"]."' " ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    // else if ($_GET["type"] == "SAVEgEN_INSTRUCTION") {
        
        
    //      $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //         $sql = "update ebmr set genral_instruction='".json_encode($input["instruction"])."' where product_code='".$_GET["product_code"]."'";
    //     }else{
    
    //         $sql = "INSERT INTO ebmr( plant_id, product_code,genral_instruction) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["instruction"])."')";
    //      if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //      }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    // }
    else if ($_GET["type"] == "SAVEequipments") {
        
        
         $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
            $sql = "update ebmr set equipments='".json_encode($input["equipments"])."' where product_code='".$_GET["product_code"]."'";
             if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             }else {
            echo "{\"status\":\"".$conn->error."\"}";
            }
        }else{
    
            $sql = "INSERT INTO ebmr( plant_id, product_code,equipments) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["equipments"])."')";
                 if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                 }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    }
    else if ($_GET["type"] == "savewarehouse_dispensing") {      
        $json_obj = json_encode($input["warehouse_dispensing"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO bmr_warehouse_dispensing_checklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "savedispensing_checklist") {      
        $json_obj = json_encode($input["dispensing_checklist"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO bmr_dispensing_checklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "savedispensing_checklist_LC") {      
        $json_obj = json_encode($input["lineCleance_checklist"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
    $sql = "INSERT INTO bmr_lineclearance_cheklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "savedispensing_checklist_LC_process") {      
        $json_obj = json_encode($input["lineCleance_Checklist_for_processing"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
    $sql = "INSERT INTO bmr_lineclearance_process_cheklist( plant_id, checkpoint,product_code,remark) VALUES ('".$_GET["plant_id"]."','".$values["check_point"]."','".$_GET["product_code"]."','".$values["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    // else if ($_GET["type"] == "savewarehouse_dispensing") {
        
        
    //      $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //         $sql = "update ebmr set warehouse_dispensing='".json_encode($input["warehouse_dispensing"])."' where product_code='".$_GET["product_code"]."'";
    //          if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //          }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //         }
    //     }else{
    
    //         $sql = "INSERT INTO ebmr( plant_id, product_code,warehouse_dispensing) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["warehouse_dispensing"])."')";
    //              if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //              }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    // }
    // else if ($_GET["type"] == "savedispensing_checklist") {
        
        
    //      $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
    //     $result =$conn->query($sql);
    //   // echo $sql;
    //     if ($result->num_rows > 0) {
    //         $sql = "update ebmr set dispensing_checklist='".json_encode($input["dispensing_checklist"])."' where product_code='".$_GET["product_code"]."'";
    //          if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //          }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //         }
    //     }else{
    
    //         $sql = "INSERT INTO ebmr( plant_id, product_code,dispensing_checklist) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["dispensing_checklist"])."')";
    //              if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //              }else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    // }
    else if ($_GET["type"] == "Saveline_chek") {
        
        
         $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
            $sql = "update ebmr set lineCleance_checklist='".json_encode($input["lineCleance_checklist"])."' where product_code='".$_GET["product_code"]."'";
             if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             }else {
            echo "{\"status\":\"".$conn->error."\"}";
            }
        }else{
    
            $sql = "INSERT INTO ebmr( plant_id, product_code,lineCleance_checklist) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["lineCleance_checklist"])."')";
                 if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                 }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    }
    else if ($_GET["type"] == "Savelinedisp_chek") {
        
        
         $sql="SELECT product_code FROM ebmr WHERE product_code='".$_GET["product_code"]."'";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
            $sql = "update ebmr set lineCleance_Checklist_for_processing='".json_encode($input["lineCleance_Checklist_for_processing"])."' where product_code='".$_GET["product_code"]."'";
             if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             }else {
            echo "{\"status\":\"".$conn->error."\"}";
            }
        }else{
    
            $sql = "INSERT INTO ebmr( plant_id, product_code,lineCleance_Checklist_for_processing) VALUES ('".$_GET["plant_id"]."','".$_GET["product_code"]."','".json_encode($input["lineCleance_Checklist_for_processing"])."')";
                 if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
                 }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    }
    else if ($_GET["type"] == "saveEbmrProcess") {    
        $json_obj = json_encode($input["processes1"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql="INSERT INTO manufacturing_process(plant_id, user_no, dosage_form, process_type, stage, step,  entry_by,entry_date) VALUES (
   '".$_GET["plant_id"]."','".$_GET["user_no"]."','".$_GET["dosage_form"]."','".$values["process_type"]."','".$values["stage"]."',
   '".$values["step"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }

}

$conn->close();
?>