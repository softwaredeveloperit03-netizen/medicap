<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
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
    
    // Helper function to build WHERE clause for step/substep filtering
    function build_step_where_clause($conn, $stage_id, $step_id, $substep_id = null) {
        $stage_id_escaped = safe_escape($conn, $stage_id);
        $step_id_escaped = safe_escape($conn, $step_id);
        $where = "stage='".$stage_id_escaped."' AND step_id='".$step_id_escaped."'";
        
        if ($substep_id !== null && $substep_id != '') {
            $substep_id_escaped = safe_escape($conn, $substep_id);
            $where .= " AND substep_id='".$substep_id_escaped."'";
        } else {
            $where .= " AND (substep_id IS NULL OR substep_id='')";
        }
        
        return $where;
    }
    
    $token = isset($_GET["token"]) ? $_GET["token"] : "";
    $currentUrl = isset($_GET["description"]) ? $_GET["description"] : "";
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    $token_escaped = safe_escape($conn, $token);
    $sql = "SELECT * FROM token WHERE token='".$token_escaped."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token_escaped."','".$type_escaped."','".$entry_date."','".$department_escaped."','".$emp_id_escaped."','".$method_escaped."','".$remote_addr_escaped."','".$currentUrl_escaped."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "getManufacturingStages") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? safe_escape($conn, $_GET["plant_id"]) : "";
        $sql="select * from product where plant_id='".$plant_id."' order by id desc";
    //   echo  $sql = "SELECT u.*, p.dosage_form, p.product_name, p.grade, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code  where plant_id='".$_GET["plant_id"]."'";
       
    //   $sql="select p.*,u.mfr_no FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code where u.plant_id='29' order by p.id desc";
       $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["instructions"] = json_decode($row["instructions"]);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]); 
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                
                $equipments = array();
                $output1 = array();
                $product_code = safe_escape($conn, $row["product_code"]);
                $sql1 = "SELECT * FROM stages WHERE product_code='".$product_code."' and plant_id='".$plant_id."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        if ($row1["isinitial"] == 'YES') {
                            $row1["initial_checks"] = json_decode($row1["initial_checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                
                  $output2 = Array();
                        $product_code_escaped = isset($row["product_code"]) ? safe_escape($conn, $row["product_code"]) : "";
                        $plant_id_escaped = isset($_GET["plant_id"]) ? safe_escape($conn, $_GET["plant_id"]) : "";
                        $sql1 = "SELECT * FROM unitformula WHERE product_code='".$product_code_escaped."' and plant_id='".$plant_id_escaped."' ";
                          $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                
                $row["equipments"] = $equipments;
                $row["abbreviation"] = json_decode($row["abbreviation"]);
                $row["bmr_checklist"] = json_decode($row["bmr_checklist"]);
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["stages"] = $output1;
                 $row["unitformula"] = $output2;
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getManufacturingStages_formulation") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? safe_escape($conn, $_GET["plant_id"]) : "";
        $sql="SELECT * FROM bmr_products a left join product b on a.product_code=b.product_code where a.plant_id='".$plant_id."' order by a.id desc";
    //   echo  $sql = "SELECT u.*, p.dosage_form, p.product_name, p.grade, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code  where plant_id='".$_GET["plant_id"]."'";
       
    //   $sql="select p.*,u.mfr_no FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code where u.plant_id='29' order by p.id desc";
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["instructions"] = json_decode($row["instructions"]);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]); 
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                
                $equipments = array();
                $output1 = array();
                $product_code = safe_escape($conn, $row["product_code"]);
                $sql1 = "SELECT * FROM stages WHERE product_code='".$product_code."' and plant_id='".$plant_id."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        if ($row1["isinitial"] == 'YES') {
                            $row1["initial_checks"] = json_decode($row1["initial_checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                
                  $output2 = Array();
                        $product_code_escaped = safe_escape($conn, $row["product_code"]);
                        $sql1 = "SELECT * FROM unitformula WHERE product_code='".$product_code_escaped."' and plant_id='".$plant_id."' ";
                          $result2 = $conn->query($sql1);
                        if ($result2 && $result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                
                $row["equipments"] = $equipments;
                $row["abbreviation"] = json_decode($row["abbreviation"]);
                $row["bmr_checklist"] = json_decode($row["bmr_checklist"]);
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["stages"] = $output1;
                 $row["unitformula"] = $output2;
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveProcedure") {
        //     ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? $input["stage_id"] : "";
        $step_id = isset($input["step_id"]) ? $input["step_id"] : "";
        $substep_id = isset($input["substep_id"]) ? $input["substep_id"] : null;
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        $where_clause = build_step_where_clause($conn, $stage_id, $step_id, $substep_id);
        
        if($input["type"]=='save'){
            $procedure_json = safe_json_encode($conn, $input["procedures"]);
            $sql = "UPDATE stages SET `procedure`='".$procedure_json."' WHERE ".$where_clause;
        }
        else if($input["type"]=='final'){
            $sql = "UPDATE stages SET isprocedure='1.5' WHERE ".$where_clause;
        }
        else if($input["type"]=='conf'){
            $sql = "UPDATE stages SET isprocedure='2',procedure_confirm_by='".$emp_id."',procedure_confirm_on='".$entry_date."' WHERE ".$where_clause;
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "SaveisInprocessChecks") {
        //     ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='final'){
         $sql = "UPDATE stages SET isInprocessChecks='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isInprocessChecks='2',InprocessChecks_confirm_by='".$emp_id."',InprocessChecks_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "saveWeighing_Variation_Recoed") {
        //     ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? $input["stage_id"] : "";
        $step_id = isset($input["step_id"]) ? $input["step_id"] : "";
        $substep_id = isset($input["substep_id"]) ? $input["substep_id"] : null;
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        $where_clause = build_step_where_clause($conn, $stage_id, $step_id, $substep_id);
        
        if($input["type"]=='save'){
            $procedures_json = safe_json_encode($conn, $input["procedures"]);
            $sql = "UPDATE stages SET `Weighing_Variation_Recoed`='".$procedures_json."' WHERE ".$where_clause;
        }
        else if($input["type"]=='final'){
          $sql = "UPDATE stages SET isWeighing_Variation_Recoed='1.5' WHERE ".$where_clause;
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isWeighing_Variation_Recoed='2',Weighing_Variation_Recoed_confirm_by='".$emp_id."',Weighing_Variation_Recoed_confirm_on='".$entry_date."' WHERE ".$where_clause;
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "saveDispensing") {
        //     ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $procedures_json = isset($input["procedures"]) ? safe_json_encode($conn, $input["procedures"]) : "[]";
            $sql = "UPDATE stages SET `Dispensing`='".$procedures_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='final'){
            $sql = "UPDATE stages SET isDispensing='1.5'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
            $sql = "UPDATE stages SET isDispensing='2',Dispensing_confirm_by='".$emp_id."',Dispensing_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."'  and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "saveCOMPRESSION_PARAMETERS") {
        //     ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $procedures_json = safe_json_encode($conn, $input["procedures"]);
            $sql = "UPDATE stages SET `COMPRESSION_PARAMETERS`='".$procedures_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='final'){
          $sql = "UPDATE stages SET isCOMPRESSION_PARAMETERS='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isCOMPRESSION_PARAMETERS='2',COMPRESSION_PARAMETERS_confirm_by='".$emp_id."',COMPRESSION_PARAMETERS_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "saveLineClearance") {
        //     ini_set('display_errors', 1);
        //  error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $procedures_json = safe_json_encode($conn, $input["procedures"]);
            $sql = "UPDATE stages SET `LineClearance`='".$procedures_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='final'){
            $sql = "UPDATE stages SET isLineClearance='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
            $sql = "UPDATE stages SET isLineClearance='2',LineClearance_confirm_by='".$emp_id."',LineClearance_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
     else if ($_GET["type"] == "saveLogbook") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $form_no_list_json = safe_json_encode($conn, $input["form_no_list"]);
            $sql = "UPDATE stages SET `Logbook`='".$form_no_list_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
            
         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isLogbook='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isLogbook='2',Logbook_confirm_by='".$emp_id."',Logbook_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    
    }
    else if ($_GET["type"] == "saveBmrRoom") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $room_json = safe_json_encode($conn, $input["bmrRoomList"]);
            $sql = "UPDATE stages SET `room`='".$room_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
            
         }
        else if($input["type"]=='final'){
            $sql = "UPDATE stages SET isroom='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
            $sql = "UPDATE stages SET isroom='2',room_confirm_by='".$emp_id."',room_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "saveBmrRoomAction") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $roomActions_json = safe_json_encode($conn, $input["roomActions"]);
            $sql = "UPDATE stages SET `roomActions`='".$roomActions_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
            
         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isroomActions='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isroomActions='2',roomActions_confirm_by='".$emp_id."',roomActions_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "delstage") {
            $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
            $sql = "DELETE FROM bmr_stages where id='".$id."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".mysqli_real_escape_string($conn, $conn->error)."\"}";
        }
    }
    else if ($_GET["type"] == "saveInstruction") {
        // $sql = "SELECT instructions FROM stages WHERE id='".$input["id"]."'";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
        //         $instructions = array();
        //         if ($row["instructions"] == '') {
        //             $instructions = array();
        //         } else {
        //             $temp = json_decode($row["instructions"]);
        //             for ($i = 0; $i < count($temp); $i++) {
        //                 $temp1 = $temp[$i];
        //                 $instructions[] = $temp1;
        //             }
        //         }
            $instructions = isset($input["instruction"]) ? $input["instruction"] : array();
            $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
            $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
            $instructions_json = safe_json_encode($conn, $instructions);
            $sql = "UPDATE stages SET instructions='".$instructions_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"Instruction saved successfully!", "instructions"=>$instructions));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
        //     }
        // } else {
        //     echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        // }
    }
    else if ($_GET["type"] == "saveQALINE") {
        $Qa_LINE = isset($input["Qa_LINE"]) ? $input["Qa_LINE"] : array();
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $qa_line_json = safe_json_encode($conn, $Qa_LINE);
       $sql = "UPDATE stages SET Qa_LINE_clearance='".$qa_line_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>" saved successfully!"));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
    } 
    else if ($_GET["type"] == "saveprodLINE") {
        $prod_LINE = isset($input["prod_LINE"]) ? $input["prod_LINE"] : array();
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $prod_line_json = safe_json_encode($conn, $prod_LINE);
       $sql = "UPDATE stages SET prod_LINE_clearance='".$prod_line_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>" saved successfully!"));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
    } 
    else if ($_GET["type"] == "saveEquipment") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $equipment_json = safe_json_encode($conn, $input["equipment"]);
            $sql = "UPDATE stages SET equipment='".$equipment_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
            
         }
        else if($input["type"]=='final'){
          $sql = "UPDATE stages SET isequipment='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isequipment='2',equipment_confirm_by='".$emp_id."',equipment_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveYeilds") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $equipment = isset($input["equipment"]) ? $input["equipment"] : [];
            $equipment_json = safe_json_encode($conn, $equipment);
            $sql = "UPDATE stages SET Yield='".$equipment_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
            
         }
        else if($input["type"]=='final'){
          $sql = "UPDATE stages SET isYield='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isYield='2',Yield_confirm_by='".$emp_id."',Yield_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasReactorCapacity") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isReactorCapacity='2',ReactorCapacity_confirm_by='".$emp_id."',ReactorCapacity_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasMATERIAL_CONSUMPTION_DETAILS") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isMATERIAL_CONSUMPTION_DETAILS='2',MATERIAL_CONSUMPTION_DETAILS_confirm_by='".$emp_id."',MATERIAL_CONSUMPTION_DETAILS_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasTIME_CYCLES_DETAILS") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isTIME_CYCLES_DETAILS='2',TIME_CYCLES_DETAILS_confirm_by='".$emp_id."',TIME_CYCLES_DETAILS_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasEQUIPMENT_CLEANING_RECORD") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isEQUIPMENT_CLEANING_RECORD='2',EQUIPMENT_CLEANING_RECORD_confirm_by='".$emp_id."',EQUIPMENT_CLEANING_RECORD_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasBLENDING_SECTION") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isBLENDING_SECTION='2',BLENDING_SECTION_confirm_by='".$emp_id."',BLENDING_SECTION_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasYIELD_RECONCILIATION") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isYIELD_RECONCILIATION='2',YIELD_RECONCILIATION_confirm_by='".$emp_id."',YIELD_RECONCILIATION_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasDosingPh") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isDosingPh='2',DosingPh_confirm_by='".$emp_id."',DosingPh_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasWIPReport") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isWIPReport='2',WIPReport_confirm_by='".$emp_id."',WIPReport_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasPPT_Transfer") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isPPT_Transfer='2',PPT_Transfer_confirm_by='".$emp_id."',PPT_Transfer_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDatasINP_Transfer") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
            
        $sql = "UPDATE stages SET isINP_Transfer='2',INP_Transfer_confirm_by='".$emp_id."',INP_Transfer_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveSieveInteggity") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $equipment = isset($input["equipment"]) ? $input["equipment"] : [];
            $equipment_json = safe_json_encode($conn, $equipment);
            $sql = "UPDATE stages SET SieveInteggity='".$equipment_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
            
         }
        else if($input["type"]=='final'){
          $sql = "UPDATE stages SET isSieveInteggity='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
          $sql = "UPDATE stages SET isSieveInteggity='2',SieveInteggity_confirm_by='".$emp_id."',SieveInteggity_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveCleaningChecks") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $CleaningChecksdatas2_json = safe_json_encode($conn, $input["CleaningChecksdatas2"]);
            $sql = "UPDATE stages SET CleaningChecks='".$CleaningChecksdatas2_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
               
         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isCleaningChecks='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isCleaningChecks='2',CleaningChecks_confirm_by='".$emp_id."',CleaningChecks_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "save_Sequence") {
        $seq=$input["list"];
        $stage_id = mysqli_real_escape_string($conn, $input["stage_id"]);
        $step_id = mysqli_real_escape_string($conn, $input["step_id"]);
        $sequence_json = mysqli_real_escape_string($conn, json_encode($seq));
        $sql = "UPDATE stages SET sequence='".$sequence_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } 
    else if ($_GET["type"] == "saveFormSeq") {
        $seq=$input["list"];
        $stage_id = mysqli_real_escape_string($conn, $input["stage_id"]);
        $step_id = mysqli_real_escape_string($conn, $input["step_id"]);
        $forms_list_json = mysqli_real_escape_string($conn, json_encode($seq));
        $sql = "UPDATE stages SET forms_list='".$forms_list_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } 
    else if ($_GET["type"] == "deleteEquipment") {
        $id = mysqli_real_escape_string($conn, $_GET["id"]);
        $equipments_json = mysqli_real_escape_string($conn, json_encode($input));
        $sql = "UPDATE stages SET equipments='".$equipments_json."' WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Equipment deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } 
    else if ($_GET["type"] == "saveClearance") {
        $id = isset($input["id"]) ? safe_escape($conn, $input["id"]) : "";
        $isclerance = isset($input["isclerance"]) ? safe_escape($conn, $input["isclerance"]) : "";
        $prod_clearances_json = safe_json_encode($conn, $input["prod_clearances"]);
        $qa_clearances_json = safe_json_encode($conn, $input["qa_clearances"]);
        $sql = "UPDATE stages SET isclerance='".$isclerance."', prod_clearances='".$prod_clearances_json."',qa_clearances='".$qa_clearances_json."' WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } 
    else if ($_GET["type"] == "saveWeighing") {
        
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET weighing='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isweighing='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isweighing='2' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "savemillinsifting") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET millinsifting='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET ismillinsifting='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET ismillinsifting='2',millinsifting_confirm_by='".$emp_id."',millinsifting_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveBlendLubrication") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET BlendLubrication='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isBlendLubrication='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isBlendLubrication='2',BlendLubrication_confirm_by='".$emp_id."',BlendLubrication_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDry_SIFTING_MILLING") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET Dry_SIFTING_MILLING='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isDry_SIFTING_MILLING='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isDry_SIFTING_MILLING='2',Dry_SIFTING_MILLING_confirm_by='".$emp_id."',Dry_SIFTING_MILLING_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveSIFTING_Lubrication") {
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = isset($input["weighings"]) ? safe_json_encode($conn, $input["weighings"]) : "[]";
            $sql = "UPDATE stages SET SIFTING_Lubrication='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='final'){
            $sql = "UPDATE stages SET isSiftLubrication='1.5'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
            $sql = "UPDATE stages SET isSiftLubrication='2',SIFTING_Lubrication_confirm_by='".$emp_id."',SIFTING_Lubrication_confirm_on='".$entry_date."'  WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveMixing") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET Mixing='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isMixing='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isMixing='2',Mixing_confirm_by='".$emp_id."',Mixing_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveYIELD_RECONCILIATION") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET YIELD_RECONCILIATION='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isYIELD_RECONCILIATION='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isYIELD_RECONCILIATION='2',YIELD_RECONCILIATION_confirm_by='".$emp_id."',YIELD_RECONCILIATION_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveINPROCESSYIELD") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET INPROCESS_YIELD='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isINPROCESS_YIELD='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isINPROCESS_YIELD='2',INPROCESS_YIELD_confirm_by='".$emp_id."',INPROCESS_YIELD_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveDrying") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $weighings_json = safe_json_encode($conn, $input["weighings"]);
            $sql = "UPDATE stages SET Drying='".$weighings_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isDrying='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isDrying='2',Drying_confirm_by='".$emp_id."',Drying_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveQcSample") {
        
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        
        if($input["type"]=='save'){
            $qcSAMPS_json = safe_json_encode($conn, $input["QcSAMPS"]);
            $sql = "UPDATE stages SET QcSample='".$qcSAMPS_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";

         }
        else if($input["type"]=='final'){
         $sql = "UPDATE stages SET isQcSample='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
         $sql = "UPDATE stages SET isQcSample='2',QcSample_confirm_by='".$emp_id."',QcSample_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    } 
    else if ($_GET["type"] == "saveEnvironment") {
    //   echo  $sql = "UPDATE stages SET environments='".json_encode($input["checkpoint"])."', env_frequency='".$input["env_frequency"]."', env_freq_unit='".$input["env_freq_unit"]."', env_qa='".$input["env_qa"]."' WHERE id='".$input["id"]."'";
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $checkpoint_json = safe_json_encode($conn, $input["checkpoint"]);
        $sql = "UPDATE stages SET environments='".$checkpoint_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveCheck") {
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $inprocessList_json = safe_json_encode($conn, $input["inprocessList"]);
        $sql = "UPDATE stages SET inprocess='".$inprocessList_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveInitialCheck") {
        $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
        $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
        $checkpoint_json = safe_json_encode($conn, $input["checkpoint"]);
        $sql = "UPDATE stages SET initial_checks='".$checkpoint_json."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveSieveCheck") {
        $id = isset($input["id"]) ? safe_escape($conn, $input["id"]) : "";
        $sieve_json = safe_json_encode($conn, $input["Sieve"]);
        $sql = "UPDATE stages SET sieve='".$sieve_json."' WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } 
    else if ($_GET["type"] == "saveScreenCheck") {
        $id = isset($input["id"]) ? safe_escape($conn, $input["id"]) : "";
        $screen_json = safe_json_encode($conn, $input["Screen"]);
        $sql = "UPDATE stages SET screen='".$screen_json."' WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveTable") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $input_data=$input["tables"];
        //  $sanitized_data = strip_tags($input_data);
        // $sanitized_data = mysqli_real_escape_string($conn, $sanitized_data);
            // $sanitized_data = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $input_data);


        
        if($input["type"]=='save'){
          $stage_id = mysqli_real_escape_string($conn, $input["stage_id"]);
          $step_id = mysqli_real_escape_string($conn, $input["step_id"]);
          $tables_data = mysqli_real_escape_string($conn, $input_data);
          $sql = "UPDATE stages SET tables='".$tables_data."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
                
             
         }
        else if($input["type"]=='final'){
            $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
            $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
          $sql = "UPDATE stages SET istable='1.5' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        else if($input["type"]=='conf'){
            $stage_id = isset($input["stage_id"]) ? safe_escape($conn, $input["stage_id"]) : "";
            $step_id = isset($input["step_id"]) ? safe_escape($conn, $input["step_id"]) : "";
            $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
          $sql = "UPDATE stages SET istable='2',tables_confirm_by='".$emp_id."',tables_confirm_on='".$entry_date."' WHERE stage='".$stage_id."' and step_id='".$step_id."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
        
    }
    else if ($_GET["type"] == "addRoomCheck") {
        $checkpoint = isset($input["checkpoint"]) ? safe_escape($conn, $input["checkpoint"]) : "";
        $evaluation_parameter = isset($input["evaluation_parameter"]) ? safe_escape($conn, $input["evaluation_parameter"]) : "";
        $Form_no = isset($input["Form_no"]) ? safe_escape($conn, $input["Form_no"]) : "";
        $version_no = isset($input["version_no"]) ? safe_escape($conn, $input["version_no"]) : "";
        $specification_no = isset($input["specification_no"]) ? safe_escape($conn, $input["specification_no"]) : "";
        $effective_date = isset($input["effective_date"]) ? safe_escape($conn, $input["effective_date"]) : "";
        $sql = "Insert into room_clearance_checklist(checkpoint,parameter,Form_no,version_no,specification_no,effective_date)
        Values('".$checkpoint."','".$evaluation_parameter."','".$Form_no."','".$version_no."','".$specification_no."','".$effective_date."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "addLineCheck") {
        $checkpoint = isset($input["checkpoint"]) ? safe_escape($conn, $input["checkpoint"]) : "";
        $evaluation_parameter = isset($input["evaluation_parameter"]) ? safe_escape($conn, $input["evaluation_parameter"]) : "";
        $Form_no = isset($input["Form_no"]) ? safe_escape($conn, $input["Form_no"]) : "";
        $version_no = isset($input["version_no"]) ? safe_escape($conn, $input["version_no"]) : "";
        $specification_no = isset($input["specification_no"]) ? safe_escape($conn, $input["specification_no"]) : "";
        $effective_date = isset($input["effective_date"]) ? safe_escape($conn, $input["effective_date"]) : "";
        $sql = "Insert into line_clearance_checklist(checkpoint,parameter,Form_no,version_no,specification_no,effective_date)
        Values('".$checkpoint."','".$evaluation_parameter."','".$Form_no."','".$version_no."','".$specification_no."','".$effective_date."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    
    else if ($_GET["type"] == "getRoomChecklist") {
        
        $output = Array();
        $sql = "SELECT * FROM room_clearance_checklist";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getSpec_date") {
        
        $output = Array();
        $material_code = isset($_GET["material_code"]) ? safe_escape($conn, $_GET["material_code"]) : "";
        $plant_id = isset($_GET["plant_id"]) ? safe_escape($conn, $_GET["plant_id"]) : "";
        $sql = "SELECT * FROM specification where (material_code='".$material_code."' ) ";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output2 = Array();
                    $specification_no = safe_escape($conn, $row["specification_no"]);
                    $sql2 = "SELECT * FROM spec_tests where specification_no='".$specification_no."' and plant_id='".$plant_id."'";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                $output2[] = $row2;
                            }
                        }
                    $row["tests"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getLineChecklist") {
        
        $output = Array();
        $sql = "SELECT * FROM line_clearance_checklist";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "saveWashCheck") {
        $id = isset($input["id"]) ? safe_escape($conn, $input["id"]) : "";
        $sql = "SELECT wash_rinse FROM stages WHERE id='".$id."'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $instructions = array();
                if ($row["wash_rinse"] == '') {
                    $instructions = array();
                } else {
                    $temp = json_decode($row["wash_rinse"]);
                    for ($i = 0; $i < count($temp); $i++) {
                        $temp1 = $temp[$i];
                        $instructions[] = $temp1;
                    }
                }
                $temp = array();
                $temp['checkpoint'] = isset($input['checkpoint']) ? $input['checkpoint'] : "";
                $instructions[] = $temp;
                
                $wash_rinse_json = safe_json_encode($conn, $instructions);
                $sql = "UPDATE stages SET wash_rinse='".$wash_rinse_json."' WHERE id='".$id."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"Wash Rinse Sample Table saved successfully!"));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
            }
        } else {
            echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        }
    } 
    else if ($_GET["type"] == "newStage") {
        
        // Escape all input variables
        $stage_id = isset($input['stage_id']) ? safe_escape($conn, $input['stage_id']) : "";
        $step_id = isset($input['step_id']) ? safe_escape($conn, $input['step_id']) : "";
        $plant_id = isset($_GET['plant_id']) ? safe_escape($conn, $_GET['plant_id']) : "";
        
        $sql4 = "SELECT * FROM stages a WHERE a.stage='".$stage_id."' AND a.step_id='".$step_id."'";
        $result4 = $conn->query($sql4);

        if ($result4 && $result4->num_rows > 0) {
            // Escape all input fields for UPDATE
            $isprocedure = isset($input['isprocedure']) ? safe_escape($conn, $input['isprocedure']) : "";
            $isroom = isset($input['isroom']) ? safe_escape($conn, $input['isroom']) : "";
            $isequipment = isset($input['isequipment']) ? safe_escape($conn, $input['isequipment']) : "";
            $isCleaningChecks = isset($input['isCleaningChecks']) ? safe_escape($conn, $input['isCleaningChecks']) : "";
            $isroomActions = isset($input['isroomActions']) ? safe_escape($conn, $input['isroomActions']) : "";
            $isEquipmemntCleaning = isset($input['isEquipmemntCleaning']) ? safe_escape($conn, $input['isEquipmemntCleaning']) : "";
            $istable = isset($input['istable']) ? safe_escape($conn, $input['istable']) : "";
            $isweighing = isset($input['isweighing']) ? safe_escape($conn, $input['isweighing']) : "";
            $isQcSample = isset($input['isQcSample']) ? safe_escape($conn, $input['isQcSample']) : "";
            $isFormats = isset($input['isFormats']) ? safe_escape($conn, $input['isFormats']) : "";
            $isQaReview = isset($input['isQaReview']) ? safe_escape($conn, $input['isQaReview']) : "";
            $isLogbook = isset($input['isLogbook']) ? safe_escape($conn, $input['isLogbook']) : "";
            $isFraction = isset($input['isFraction']) ? safe_escape($conn, $input['isFraction']) : "";
            $isLineClearance = isset($input['isLineClearance']) ? safe_escape($conn, $input['isLineClearance']) : "";
            $isInprocessChecks = isset($input['isInprocessChecks']) ? safe_escape($conn, $input['isInprocessChecks']) : "";
            $isSieveInteggity = isset($input['isSieveInteggity']) ? safe_escape($conn, $input['isSieveInteggity']) : "";
            $isYield = isset($input['isYield']) ? safe_escape($conn, $input['isYield']) : "";
            $ismillinsifting = isset($input['ismillinsifting']) ? safe_escape($conn, $input['ismillinsifting']) : "";
            $isDrying = isset($input['isDrying']) ? safe_escape($conn, $input['isDrying']) : "";
            $isBlendLubrication = isset($input['isBlendLubrication']) ? safe_escape($conn, $input['isBlendLubrication']) : "";
            $isCOMPRESSION_PARAMETERS = isset($input['isCOMPRESSION_PARAMETERS']) ? safe_escape($conn, $input['isCOMPRESSION_PARAMETERS']) : "";
            $isWeighing_Variation_Recoed = isset($input['isWeighing_Variation_Recoed']) ? safe_escape($conn, $input['isWeighing_Variation_Recoed']) : "";
            $isDispensing = isset($input['isDispensing']) ? safe_escape($conn, $input['isDispensing']) : "";
            $isMixing = isset($input['isMixing']) ? safe_escape($conn, $input['isMixing']) : "";
            $isDry_SIFTING_MILLING = isset($input['isDry_SIFTING_MILLING']) ? safe_escape($conn, $input['isDry_SIFTING_MILLING']) : "";
            $isSiftLubrication = isset($input['isSiftLubrication']) ? safe_escape($conn, $input['isSiftLubrication']) : "";
            $isYIELD_RECONCILIATION = isset($input['isYIELD_RECONCILIATION']) ? safe_escape($conn, $input['isYIELD_RECONCILIATION']) : "";
            $isINPROCESS_YIELD = isset($input['isINPROCESS_YIELD']) ? safe_escape($conn, $input['isINPROCESS_YIELD']) : "";
            
            $sql="update stages set isprocedure='".$isprocedure."',isroom='".$isroom."',isequipment='".$isequipment."',
            isCleaningChecks='".$isCleaningChecks."',isroomActions='".$isroomActions."',isEquipmemntCleaning='".$isEquipmemntCleaning."',
            istable='".$istable."',isweighing='".$isweighing."',isQcSample='".$isQcSample."',isFormats='".$isFormats."',
            isQaReview='".$isQaReview."',isLogbook='".$isLogbook."',isFraction='".$isFraction."',isLineClearance='".$isLineClearance."'
            ,isInprocessChecks='".$isInprocessChecks."',isSieveInteggity='".$isSieveInteggity."',isYield='".$isYield."',ismillinsifting='".$ismillinsifting."',isDrying='".$isDrying."',isBlendLubrication='".$isBlendLubrication."',isCOMPRESSION_PARAMETERS='".$isCOMPRESSION_PARAMETERS."'
            ,isWeighing_Variation_Recoed='".$isWeighing_Variation_Recoed."',isDispensing='".$isDispensing."',isMixing='".$isMixing."',isDry_SIFTING_MILLING='".$isDry_SIFTING_MILLING."',isSiftLubrication='".$isSiftLubrication."',isYIELD_RECONCILIATION='".$isYIELD_RECONCILIATION."',isINPROCESS_YIELD='".$isINPROCESS_YIELD."'
            where stage='".$stage_id."' and step_id='".$step_id."'";
            if ($conn->query($sql)) {
                echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
            } else {
                echo json_encode(array("status"=>"failed","msg"=>$conn->error));
            }
        }else{
            // Escape all input fields for INSERT
            $isprocedure = isset($input['isprocedure']) ? safe_escape($conn, $input['isprocedure']) : "";
            $isroom = isset($input['isroom']) ? safe_escape($conn, $input['isroom']) : "";
            $isequipment = isset($input['isequipment']) ? safe_escape($conn, $input['isequipment']) : "";
            $isCleaningChecks = isset($input['isCleaningChecks']) ? safe_escape($conn, $input['isCleaningChecks']) : "";
            $isroomActions = isset($input['isroomActions']) ? safe_escape($conn, $input['isroomActions']) : "";
            $isEquipmemntCleaning = isset($input['isEquipmemntCleaning']) ? safe_escape($conn, $input['isEquipmemntCleaning']) : "";
            $istable = isset($input['istable']) ? safe_escape($conn, $input['istable']) : "";
            $isweighing = isset($input['isweighing']) ? safe_escape($conn, $input['isweighing']) : "";
            $isQcSample = isset($input['isQcSample']) ? safe_escape($conn, $input['isQcSample']) : "";
            $isFormats = isset($input['isFormats']) ? safe_escape($conn, $input['isFormats']) : "";
            $isQaReview = isset($input['isQaReview']) ? safe_escape($conn, $input['isQaReview']) : "";
            $isLogbook = isset($input['isLogbook']) ? safe_escape($conn, $input['isLogbook']) : "";
            $isFraction = isset($input['isFraction']) ? safe_escape($conn, $input['isFraction']) : "";
            $isLineClearance = isset($input['isLineClearance']) ? safe_escape($conn, $input['isLineClearance']) : "";
            $isSieveInteggity = isset($input['isSieveInteggity']) ? safe_escape($conn, $input['isSieveInteggity']) : "";
            $isYield = isset($input['isYield']) ? safe_escape($conn, $input['isYield']) : "";
            $isInprocessChecks = isset($input['isInprocessChecks']) ? safe_escape($conn, $input['isInprocessChecks']) : "";
            $ismillinsifting = isset($input['ismillinsifting']) ? safe_escape($conn, $input['ismillinsifting']) : "";
            $isDrying = isset($input['isDrying']) ? safe_escape($conn, $input['isDrying']) : "";
            $isBlendLubrication = isset($input['isBlendLubrication']) ? safe_escape($conn, $input['isBlendLubrication']) : "";
            $isCOMPRESSION_PARAMETERS = isset($input['isCOMPRESSION_PARAMETERS']) ? safe_escape($conn, $input['isCOMPRESSION_PARAMETERS']) : "";
            $isWeighing_Variation_Recoed = isset($input['isWeighing_Variation_Recoed']) ? safe_escape($conn, $input['isWeighing_Variation_Recoed']) : "";
            $isDispensing = isset($input['isDispensing']) ? safe_escape($conn, $input['isDispensing']) : "";
            $isMixing = isset($input['isMixing']) ? safe_escape($conn, $input['isMixing']) : "";
            $isDry_SIFTING_MILLING = isset($input['isDry_SIFTING_MILLING']) ? safe_escape($conn, $input['isDry_SIFTING_MILLING']) : "";
            $isSiftLubrication = isset($input['isSiftLubrication']) ? safe_escape($conn, $input['isSiftLubrication']) : "";
            $isYIELD_RECONCILIATION = isset($input['isYIELD_RECONCILIATION']) ? safe_escape($conn, $input['isYIELD_RECONCILIATION']) : "";
            $isINPROCESS_YIELD = isset($input['isINPROCESS_YIELD']) ? safe_escape($conn, $input['isINPROCESS_YIELD']) : "";
            
            $forms_lists_json = isset($input['forms_listssss']) ? safe_json_encode($conn, $input['forms_listssss']) : "[]";
            
            $sql = "INSERT INTO stages (
            plant_id, stage, step_id, isprocedure, isroom, isequipment, isCleaningChecks, 
            isroomActions, isEquipmemntCleaning, istable, isweighing, isQcSample, isFormats, 
            isQaReview, isLogbook, isFraction, isLineClearance, isSieveInteggity, isYield, 
            isInprocessChecks, ismillinsifting, isDrying, isBlendLubrication, isCOMPRESSION_PARAMETERS, 
            isWeighing_Variation_Recoed, isDispensing, isMixing, isDry_SIFTING_MILLING, isSiftLubrication, isYIELD_RECONCILIATION, isINPROCESS_YIELD,
            `procedure`, room, equipment, 
            CleaningChecks, roomActions, EquipmemntCleaning, weighing, QcSample, Formats, 
            QaReview, Logbook, Fraction, LineClearance, SieveInteggity, InprocessChecks, Yield, 
            millinsifting, Drying, BlendLubrication, COMPRESSION_PARAMETERS, Weighing_Variation_Recoed, 
            Dispensing, Mixing, Dry_SIFTING_MILLING, SIFTING_Lubrication, YIELD_RECONCILIATION, INPROCESS_YIELD, forms_list
        ) VALUES (
            '".$plant_id."', '".$stage_id."', '".$step_id."', '".$isprocedure."', 
            '".$isroom."', '".$isequipment."', '".$isCleaningChecks."', 
            '".$isroomActions."', '".$isEquipmemntCleaning."', '".$istable."', 
            '".$isweighing."', '".$isQcSample."', '".$isFormats."', 
            '".$isQaReview."', '".$isLogbook."', '".$isFraction."', 
            '".$isLineClearance."', '".$isSieveInteggity."', '".$isYield."', 
            '".$isInprocessChecks."', '".$ismillinsifting."', '".$isDrying."', 
            '".$isBlendLubrication."', '".$isCOMPRESSION_PARAMETERS."', 
            '".$isWeighing_Variation_Recoed."', '".$isDispensing."', '".$isMixing."', 
            '".$isDry_SIFTING_MILLING."', '".$isSiftLubrication."', '".$isYIELD_RECONCILIATION."', '".$isINPROCESS_YIELD."',
            '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', 
            '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '".$forms_lists_json."'
            )";
      
            if ($conn->query($sql)) {
                $step_id_escaped = safe_escape($conn, $input["step_id"]);
                $sql2="update manufacturing_process_step set process_stage='done' where id='".$step_id_escaped."'";
                $conn->query($sql2);
                echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
            } else {
                echo json_encode(array("status"=>"failed","msg"=>$conn->error));
            }
        }
    }
    else if ($_GET["type"] == "newStageMeha") {
        
        $stage_id = isset($input['stage_id']) ? safe_escape($conn, $input['stage_id']) : "";
        $step_id = isset($input['step_id']) ? safe_escape($conn, $input['step_id']) : "";
        $plant_id = isset($_GET['plant_id']) ? safe_escape($conn, $_GET['plant_id']) : "";
        
        $sql4 = "SELECT * FROM stages a WHERE a.stage='".$stage_id."' AND a.step_id='".$step_id."'";
        $result4 = $conn->query($sql4);

        if ($result4 && $result4->num_rows > 0) {
            $isprocedure = isset($input['isprocedure']) ? safe_escape($conn, $input['isprocedure']) : "";
            $isCheckpoints = isset($input['isCheckpoints']) ? safe_escape($conn, $input['isCheckpoints']) : "";
            $isequipment = isset($input['isequipment']) ? safe_escape($conn, $input['isequipment']) : "";
            $isCleaningChecks = isset($input['isCleaningChecks']) ? safe_escape($conn, $input['isCleaningChecks']) : "";
            $isroomActions = isset($input['isroomActions']) ? safe_escape($conn, $input['isroomActions']) : "";
            $isEquipmemntCleaning = isset($input['isEquipmemntCleaning']) ? safe_escape($conn, $input['isEquipmemntCleaning']) : "";
            $istable = isset($input['istable']) ? safe_escape($conn, $input['istable']) : "";
            $isweighing = isset($input['isweighing']) ? safe_escape($conn, $input['isweighing']) : "";
            $isQcSample = isset($input['isQcSample']) ? safe_escape($conn, $input['isQcSample']) : "";
            $isFormats = isset($input['isFormats']) ? safe_escape($conn, $input['isFormats']) : "";
            $isQaReview = isset($input['isQaReview']) ? safe_escape($conn, $input['isQaReview']) : "";
            $isLogbook = isset($input['isLogbook']) ? safe_escape($conn, $input['isLogbook']) : "";
            $isFraction = isset($input['isFraction']) ? safe_escape($conn, $input['isFraction']) : "";
            $isLineClearance = isset($input['isLineClearance']) ? safe_escape($conn, $input['isLineClearance']) : "";
            $isInprocessChecks = isset($input['isInprocessChecks']) ? safe_escape($conn, $input['isInprocessChecks']) : "";
            $isSieveInteggity = isset($input['isSieveInteggity']) ? safe_escape($conn, $input['isSieveInteggity']) : "";
            $isYield = isset($input['isYield']) ? safe_escape($conn, $input['isYield']) : "";
            $ismillinsifting = isset($input['ismillinsifting']) ? safe_escape($conn, $input['ismillinsifting']) : "";
            $isDrying = isset($input['isDrying']) ? safe_escape($conn, $input['isDrying']) : "";
            $isBlendLubrication = isset($input['isBlendLubrication']) ? safe_escape($conn, $input['isBlendLubrication']) : "";
            $isCOMPRESSION_PARAMETERS = isset($input['isCOMPRESSION_PARAMETERS']) ? safe_escape($conn, $input['isCOMPRESSION_PARAMETERS']) : "";
            $isWeighing_Variation_Recoed = isset($input['isWeighing_Variation_Recoed']) ? safe_escape($conn, $input['isWeighing_Variation_Recoed']) : "";
            $isDispensing = isset($input['isDispensing']) ? safe_escape($conn, $input['isDispensing']) : "";
            $isMixing = isset($input['isMixing']) ? safe_escape($conn, $input['isMixing']) : "";
            $isDry_SIFTING_MILLING = isset($input['isDry_SIFTING_MILLING']) ? safe_escape($conn, $input['isDry_SIFTING_MILLING']) : "";
            $isSiftLubrication = isset($input['isSiftLubrication']) ? safe_escape($conn, $input['isSiftLubrication']) : "";
            $isYIELD_RECONCILIATION = isset($input['isYIELD_RECONCILIATION']) ? safe_escape($conn, $input['isYIELD_RECONCILIATION']) : "";
            $isINPROCESS_YIELD = isset($input['isINPROCESS_YIELD']) ? safe_escape($conn, $input['isINPROCESS_YIELD']) : "";
            $isBLENDING_SECTION = isset($input['isBLENDING_SECTION']) ? safe_escape($conn, $input['isBLENDING_SECTION']) : "";
            $isDosingPh = isset($input['isDosingPh']) ? safe_escape($conn, $input['isDosingPh']) : "";
            $isEQUIPMENT_CLEANING_RECORD = isset($input['isEQUIPMENT_CLEANING_RECORD']) ? safe_escape($conn, $input['isEQUIPMENT_CLEANING_RECORD']) : "";
            $isINP_Transfer = isset($input['isINP_Transfer']) ? safe_escape($conn, $input['isINP_Transfer']) : "";
            $isMATERIAL_CONSUMPTION_DETAILS = isset($input['isMATERIAL_CONSUMPTION_DETAILS']) ? safe_escape($conn, $input['isMATERIAL_CONSUMPTION_DETAILS']) : "";
            $isPPT_Transfer = isset($input['isPPT_Transfer']) ? safe_escape($conn, $input['isPPT_Transfer']) : "";
            $isReactorCapacity = isset($input['isReactorCapacity']) ? safe_escape($conn, $input['isReactorCapacity']) : "";
            $isTIME_CYCLES_DETAILS = isset($input['isTIME_CYCLES_DETAILS']) ? safe_escape($conn, $input['isTIME_CYCLES_DETAILS']) : "";
            $isWIPReport = isset($input['isWIPReport']) ? safe_escape($conn, $input['isWIPReport']) : "";
            
            $sql="update stages set isprocedure='".$isprocedure."',isCheckpoints='".$isCheckpoints."',isequipment='".$isequipment."',
            isCleaningChecks='".$isCleaningChecks."',isroomActions='".$isroomActions."',isEquipmemntCleaning='".$isEquipmemntCleaning."',
            istable='".$istable."',isweighing='".$isweighing."',isQcSample='".$isQcSample."',isFormats='".$isFormats."',
            isQaReview='".$isQaReview."',isLogbook='".$isLogbook."',isFraction='".$isFraction."',isLineClearance='".$isLineClearance."'
            ,isInprocessChecks='".$isInprocessChecks."',isSieveInteggity='".$isSieveInteggity."',isYield='".$isYield."',ismillinsifting='".$ismillinsifting."',isDrying='".$isDrying."',isBlendLubrication='".$isBlendLubrication."',isCOMPRESSION_PARAMETERS='".$isCOMPRESSION_PARAMETERS."'
            ,isWeighing_Variation_Recoed='".$isWeighing_Variation_Recoed."',isDispensing='".$isDispensing."',isMixing='".$isMixing."',isDry_SIFTING_MILLING='".$isDry_SIFTING_MILLING."',isSiftLubrication='".$isSiftLubrication."',isYIELD_RECONCILIATION='".$isYIELD_RECONCILIATION."',isINPROCESS_YIELD='".$isINPROCESS_YIELD."',
            isBLENDING_SECTION='".$isBLENDING_SECTION."',isDosingPh='".$isDosingPh."',isEQUIPMENT_CLEANING_RECORD='".$isEQUIPMENT_CLEANING_RECORD."',
            isINP_Transfer='".$isINP_Transfer."',isMATERIAL_CONSUMPTION_DETAILS='".$isMATERIAL_CONSUMPTION_DETAILS."',isPPT_Transfer='".$isPPT_Transfer."',
            isReactorCapacity='".$isReactorCapacity."',isTIME_CYCLES_DETAILS='".$isTIME_CYCLES_DETAILS."',isWIPReport='".$isWIPReport."'
            where stage='".$stage_id."' and step_id='".$step_id."'";
       if ($conn->query($sql)) {
            
           
            echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
        }else{
            $isprocedure = isset($input['isprocedure']) ? safe_escape($conn, $input['isprocedure']) : "";
            $isCheckpoints = isset($input['isCheckpoints']) ? safe_escape($conn, $input['isCheckpoints']) : "";
            $isequipment = isset($input['isequipment']) ? safe_escape($conn, $input['isequipment']) : "";
            $isCleaningChecks = isset($input['isCleaningChecks']) ? safe_escape($conn, $input['isCleaningChecks']) : "";
            $isroomActions = isset($input['isroomActions']) ? safe_escape($conn, $input['isroomActions']) : "";
            $isEquipmemntCleaning = isset($input['isEquipmemntCleaning']) ? safe_escape($conn, $input['isEquipmemntCleaning']) : "";
            $istable = isset($input['istable']) ? safe_escape($conn, $input['istable']) : "";
            $isweighing = isset($input['isweighing']) ? safe_escape($conn, $input['isweighing']) : "";
            $isQcSample = isset($input['isQcSample']) ? safe_escape($conn, $input['isQcSample']) : "";
            $isFormats = isset($input['isFormats']) ? safe_escape($conn, $input['isFormats']) : "";
            $isQaReview = isset($input['isQaReview']) ? safe_escape($conn, $input['isQaReview']) : "";
            $isLogbook = isset($input['isLogbook']) ? safe_escape($conn, $input['isLogbook']) : "";
            $isFraction = isset($input['isFraction']) ? safe_escape($conn, $input['isFraction']) : "";
            $isLineClearance = isset($input['isLineClearance']) ? safe_escape($conn, $input['isLineClearance']) : "";
            $isInprocessChecks = isset($input['isInprocessChecks']) ? safe_escape($conn, $input['isInprocessChecks']) : "";
            $isSieveInteggity = isset($input['isSieveInteggity']) ? safe_escape($conn, $input['isSieveInteggity']) : "";
            $isYield = isset($input['isYield']) ? safe_escape($conn, $input['isYield']) : "";
            $ismillinsifting = isset($input['ismillinsifting']) ? safe_escape($conn, $input['ismillinsifting']) : "";
            $isDrying = isset($input['isDrying']) ? safe_escape($conn, $input['isDrying']) : "";
            $isBlendLubrication = isset($input['isBlendLubrication']) ? safe_escape($conn, $input['isBlendLubrication']) : "";
            $isCOMPRESSION_PARAMETERS = isset($input['isCOMPRESSION_PARAMETERS']) ? safe_escape($conn, $input['isCOMPRESSION_PARAMETERS']) : "";
            $isWeighing_Variation_Recoed = isset($input['isWeighing_Variation_Recoed']) ? safe_escape($conn, $input['isWeighing_Variation_Recoed']) : "";
            $isDispensing = isset($input['isDispensing']) ? safe_escape($conn, $input['isDispensing']) : "";
            $isMixing = isset($input['isMixing']) ? safe_escape($conn, $input['isMixing']) : "";
            $isDry_SIFTING_MILLING = isset($input['isDry_SIFTING_MILLING']) ? safe_escape($conn, $input['isDry_SIFTING_MILLING']) : "";
            $isSiftLubrication = isset($input['isSiftLubrication']) ? safe_escape($conn, $input['isSiftLubrication']) : "";
            $isYIELD_RECONCILIATION = isset($input['isYIELD_RECONCILIATION']) ? safe_escape($conn, $input['isYIELD_RECONCILIATION']) : "";
            $isINPROCESS_YIELD = isset($input['isINPROCESS_YIELD']) ? safe_escape($conn, $input['isINPROCESS_YIELD']) : "";
            $isBLENDING_SECTION = isset($input['isBLENDING_SECTION']) ? safe_escape($conn, $input['isBLENDING_SECTION']) : "";
            $isDosingPh = isset($input['isDosingPh']) ? safe_escape($conn, $input['isDosingPh']) : "";
            $isEQUIPMENT_CLEANING_RECORD = isset($input['isEQUIPMENT_CLEANING_RECORD']) ? safe_escape($conn, $input['isEQUIPMENT_CLEANING_RECORD']) : "";
            $isINP_Transfer = isset($input['isINP_Transfer']) ? safe_escape($conn, $input['isINP_Transfer']) : "";
            $isMATERIAL_CONSUMPTION_DETAILS = isset($input['isMATERIAL_CONSUMPTION_DETAILS']) ? safe_escape($conn, $input['isMATERIAL_CONSUMPTION_DETAILS']) : "";
            $isPPT_Transfer = isset($input['isPPT_Transfer']) ? safe_escape($conn, $input['isPPT_Transfer']) : "";
            $isReactorCapacity = isset($input['isReactorCapacity']) ? safe_escape($conn, $input['isReactorCapacity']) : "";
            $isTIME_CYCLES_DETAILS = isset($input['isTIME_CYCLES_DETAILS']) ? safe_escape($conn, $input['isTIME_CYCLES_DETAILS']) : "";
            $isWIPReport = isset($input['isWIPReport']) ? safe_escape($conn, $input['isWIPReport']) : "";
            
            $forms_lists_json = isset($input['forms_listssss']) ? safe_json_encode($conn, $input['forms_listssss']) : "[]";
            
            $sql = "INSERT INTO stages (
            plant_id, stage, step_id, isprocedure, isCheckpoints, isequipment, isCleaningChecks, 
            isroomActions, isEquipmemntCleaning, istable, isweighing, isQcSample, isFormats, 
            isQaReview, isLogbook, isFraction, isLineClearance, isSieveInteggity, isYield, 
            isInprocessChecks, ismillinsifting, isDrying, isBlendLubrication, isCOMPRESSION_PARAMETERS, 
            isWeighing_Variation_Recoed, isDispensing, isMixing,isDry_SIFTING_MILLING,isSiftLubrication,isYIELD_RECONCILIATION,isINPROCESS_YIELD,
            isBLENDING_SECTION,isDosingPh,isEQUIPMENT_CLEANING_RECORD,isINP_Transfer,isMATERIAL_CONSUMPTION_DETAILS,isPPT_Transfer,isReactorCapacity,isTIME_CYCLES_DETAILS,isWIPReport,
            `procedure`, Checkpoints, equipment, 
            CleaningChecks, roomActions, EquipmemntCleaning, weighing, QcSample, Formats, 
            QaReview, Logbook, Fraction, LineClearance, SieveInteggity, InprocessChecks, Yield, 
            millinsifting, Drying, BlendLubrication, COMPRESSION_PARAMETERS, Weighing_Variation_Recoed, 
            Dispensing,Mixing,Dry_SIFTING_MILLING,SIFTING_Lubrication,YIELD_RECONCILIATION,INPROCESS_YIELD,forms_list,
            BLENDING_SECTION,DosingPh,EQUIPMENT_CLEANING_RECORD,INP_Transfer,MATERIAL_CONSUMPTION_DETAILS,PPT_Transfer,ReactorCapacity,TIME_CYCLES_DETAILS,WIPReport
            ) VALUES (
            '".$plant_id."', '".$stage_id."', '".$step_id."', '".$isprocedure."', 
            '".$isCheckpoints."', '".$isequipment."', '".$isCleaningChecks."', 
            '".$isroomActions."', '".$isEquipmemntCleaning."', '".$istable."', 
            '".$isweighing."', '".$isQcSample."', '".$isFormats."', 
            '".$isQaReview."', '".$isLogbook."', '".$isFraction."', 
            '".$isLineClearance."', '".$isSieveInteggity."', '".$isYield."', 
            '".$isInprocessChecks."', '".$ismillinsifting."', '".$isDrying."', 
            '".$isBlendLubrication."', '".$isCOMPRESSION_PARAMETERS."', 
            '".$isWeighing_Variation_Recoed."', '".$isDispensing."', '".$isMixing."', 
            '".$isDry_SIFTING_MILLING."', '".$isSiftLubrication."', '".$isYIELD_RECONCILIATION."','".$isINPROCESS_YIELD."',
            '".$isBLENDING_SECTION."','".$isDosingPh."','".$isEQUIPMENT_CLEANING_RECORD."','".$isINP_Transfer."',
            '".$isMATERIAL_CONSUMPTION_DETAILS."','".$isPPT_Transfer."','".$isReactorCapacity."','".$isTIME_CYCLES_DETAILS."',
            '".$isWIPReport."',
            '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', 
            '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]'
            ,'[]','[]','[]','[]','[]','[]','[]','[]','[]','".$forms_lists_json."' 
            )";
      
            if ($conn->query($sql)) {
                $step_id_escaped = safe_escape($conn, $step_id);
                $sql2="update manufacturing_process_step set process_stage='done' where id='".$step_id_escaped."'";
                $conn->query($sql2);
                echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
            } else {
                echo json_encode(array("status"=>"failed","msg"=>$conn->error));
            }
        }
    
    }
    else if ($_GET["type"] == "newStage_saipro") {
        $plant_id = isset($_GET["plant_id"]) ? safe_escape($conn, $_GET["plant_id"]) : "";
        $product_code = isset($input["product_code"]) ? safe_escape($conn, $input["product_code"]) : "";
        $mfr_no = isset($input["mfr_no"]) ? safe_escape($conn, $input["mfr_no"]) : "";
        $process = isset($input["process"]) ? safe_escape($conn, $input["process"]) : "";
        $stage = isset($input["stage"]) ? safe_escape($conn, $input["stage"]) : "";
        $isprocedure = isset($input['isprocedure']) ? safe_escape($conn, $input['isprocedure']) : "";
        $isinstruction = isset($input['isinstruction']) ? safe_escape($conn, $input['isinstruction']) : "";
        $isequipment = isset($input['isequipment']) ? safe_escape($conn, $input['isequipment']) : "";
        $isclerance = isset($input['isclerance']) ? safe_escape($conn, $input['isclerance']) : "";
        $isweighing = isset($input['isweighing']) ? safe_escape($conn, $input['isweighing']) : "";
        $isenvironment = isset($input['isenvironment']) ? safe_escape($conn, $input['isenvironment']) : "";
        $ischeck = isset($input['ischeck']) ? safe_escape($conn, $input['ischeck']) : "";
        $isinitial = isset($input['isinitial']) ? safe_escape($conn, $input['isinitial']) : "";
        $issieve = isset($input['issieve']) ? safe_escape($conn, $input['issieve']) : "";
        $isscreen = isset($input['isscreen']) ? safe_escape($conn, $input['isscreen']) : "";
        $isdrying = isset($input['isdrying']) ? safe_escape($conn, $input['isdrying']) : "";
        $iswash = isset($input['iswash']) ? safe_escape($conn, $input['iswash']) : "";
        $isinprocess = isset($input["isinprocess"]) ? safe_escape($conn, $input["isinprocess"]) : "";
        $isqcchecks = isset($input["isqcchecks"]) ? safe_escape($conn, $input["isqcchecks"]) : "";
        $sql = "INSERT INTO bmr_stages (plant_id,product_code, mfr_no, process, stage, isprocedure, isinstruction, isequipment, isclerance, isweighing, isenvironment, ischeck, isinitial, issieve, isscreen, isdrying, iswash ,isinprocess ,isqcchecks) VALUES ('".$plant_id."','".$product_code."', '".$mfr_no."', '".$process."', '".$stage."', '".$isprocedure."', '".$isinstruction."', '".$isequipment."', '".$isclerance."', '".$isweighing."', '".$isenvironment."', '".$ischeck."', '".$isinitial."', '".$issieve."', '".$isscreen."', '".$isdrying."', '".$iswash."' ,'".$isinprocess."' ,'".$isqcchecks."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "send_cehck") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $emp_id = isset($_GET["emp_id"]) ? safe_escape($conn, $_GET["emp_id"]) : "";
        $stage_id = isset($_GET["stage_id"]) ? safe_escape($conn, $_GET["stage_id"]) : "";
        $sql = "update bmr_stages set saved_by='".$emp_id."',saved_date='".$entry_date."' where id='".$stage_id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveInstruction2") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $instructions = isset($input["instructon"]) ? $input["instructon"] : array();
        $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
        $instructions_json = safe_json_encode($conn, $instructions);
        $sql = "UPDATE stages SET instructions='".$instructions_json."',instructions_status='Checked' where id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveProcedure2") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $procedures = isset($input["procedures"]) ? $input["procedures"] : array();
        $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
        $procedures_json = safe_json_encode($conn, $procedures);
        $sql = "UPDATE stages SET `procedure`='".$procedures_json."',procedure_status='Checked' where id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveequipment2") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $equipments = isset($input["equipments"]) ? $input["equipments"] : array();
        $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
        $equipments_json = safe_json_encode($conn, $equipments);
        $sql = "UPDATE stages SET equipments='".$equipments_json."',equipments_status='Checked' where id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveWeighing2") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $weighings = isset($input["weighings"]) ? $input["weighings"] : array();
        $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
        $weighings_json = safe_json_encode($conn, $weighings);
        $sql = "UPDATE stages SET weighings='".$weighings_json."',weighings_status='Checked' where id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveinitial_checks2") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $initial_checks = isset($input["initial_checks"]) ? $input["initial_checks"] : array();
        $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
        $initial_checks_json = safe_json_encode($conn, $initial_checks);
        $sql = "UPDATE stages SET initial_checks='".$initial_checks_json."',initial_checks_status='Checked' where id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveinprocess2") {
        $inprocess = isset($input["inprocess"]) ? $input["inprocess"] : array();
        $id = isset($_GET["id"]) ? safe_escape($conn, $_GET["id"]) : "";
        $inprocess_json = safe_json_encode($conn, $inprocess);
        $sql = "UPDATE stages SET inprocess='".$inprocess_json."',inprocess_status='Checked' where id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "GET_bmr_stages_steps") {
//             ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $work_order_no = isset($_GET["work_order_no"]) ? safe_escape($conn, $_GET["work_order_no"]) : "";
        $Stage_id = isset($_GET["Stage_id"]) ? safe_escape($conn, $_GET["Stage_id"]) : "";
        $stpe_id = isset($_GET["stpe_id"]) ? safe_escape($conn, $_GET["stpe_id"]) : "";
        $substep_id = isset($_GET["substep_id"]) ? safe_escape($conn, $_GET["substep_id"]) : "";

        // Build WHERE clause for step/substep filtering
        $where_clause = "a.stage='".$Stage_id."' AND a.step_id='".$stpe_id."'";
        
        // If substep_id is provided, filter by substep; otherwise, only get steps (substep_id IS NULL)
        if ($substep_id != '') {
            $where_clause .= " AND a.substep_id='".$substep_id."'";
        } else {
            $where_clause .= " AND (a.substep_id IS NULL OR a.substep_id='')";
        }

        if($work_order_no != ''){
            $output = Array();
            $sql = "SELECT a.*,(SELECT checking_in FROM manufacturing_process_step m WHERE b.step_id=m.id ) AS checking_in,
            (SELECT substep_name FROM manufacturing_process_substep s WHERE s.id=a.substep_id) AS substep_name,
            b.saved_by AS bmr_saved_by,b.id AS bmr_stages_id,b.table_status AS bmr_table_status,b.procedure_status AS bmr_procedure_status,b.LineClearance_status AS bmr_LineClearance_status ,b.equipment_status AS bmr_equipment_status,b.CleaningChecks_status AS bmr_CleaningChecks_status
            ,b.room_status AS bmr_room_status ,b.roomActions_status AS bmr_roomActions_status,b.weighing_status AS bmr_weighing_status,
            b.procedure AS bmr_pocedure,b.LineClearance AS bmr_LineClearance,b.equipment AS bmr_equipment,b.CleaningChecks AS bmr_CleaningChecks,b.room AS bmr_room,b.weighing AS bmr_weighing,b.tables AS bmr_table,b.roomActions AS bmr_roomActions,
            b.procedure_entry_by,b.procedure_approved_by,b.equipment_entry_by,b.equipment_approved_by,b.CleaningChecks_entry_by,b.CleaningChecks_approved_by,b.room_entry_by,b.room_approved_by,
            b.roomActions_entry_by,b.roomActions_approved_by,b.weighing_entry_by,b.weighing_approved_by,
            b.procedure_entry_date,b.procedure_approved_date,b.equipment_entry_date,b.equipment_approved_date,b.CleaningChecks_entry_date,b.CleaningChecks_approved_date,b.room_entry_date,b.room_approved_date,
            b.roomActions_entry_date,b.roomActions_approved_date,b.weighing_entry_date,b.weighing_approved_date
            FROM stages a LEFT JOIN bmr_stages b ON a.stage=b.stage AND a.step_id=b.step_id AND a.id=b.stages_id 
            AND (a.substep_id = b.substep_id OR (a.substep_id IS NULL AND b.substep_id IS NULL) OR (a.substep_id = '' AND b.substep_id = ''))
            WHERE ".$where_clause." AND (b.work_order_no IS NULL OR b.work_order_no LIKE '%".$work_order_no."%')";
        }else{
            $output = Array();
            $sql = "SELECT a.*,
            (SELECT substep_name FROM manufacturing_process_substep s WHERE s.id=a.substep_id) AS substep_name,
            b.id AS bmr_stages_id,b.table_status AS bmr_table_status,b.procedure_status AS bmr_procedure_status,b.LineClearance_status AS bmr_LineClearance_status ,b.equipment_status AS bmr_equipment_status,b.CleaningChecks_status AS bmr_CleaningChecks_status
            ,b.room_status AS bmr_room_status ,b.roomActions_status AS bmr_roomActions_status,b.weighing_status AS bmr_weighing_status,
            b.procedure AS bmr_pocedure,b.LineClearance AS bmr_LineClearance,b.equipment AS bmr_equipment,b.CleaningChecks AS bmr_CleaningChecks,b.room AS bmr_room,b.weighing AS bmr_weighing,b.tables AS bmr_table,b.roomActions AS bmr_roomActions,
            b.procedure_entry_by,b.procedure_approved_by,b.equipment_entry_by,b.equipment_approved_by,b.CleaningChecks_entry_by,b.CleaningChecks_approved_by,b.room_entry_by,b.room_approved_by,
            b.roomActions_entry_by,b.roomActions_approved_by,b.weighing_entry_by,b.weighing_approved_by,
            b.procedure_entry_date,b.procedure_approved_date,b.equipment_entry_date,b.equipment_approved_date,b.CleaningChecks_entry_date,b.CleaningChecks_approved_date,b.room_entry_date,b.room_approved_date,
            b.roomActions_entry_date,b.roomActions_approved_date,b.weighing_entry_date,b.weighing_approved_date,b.LineClearance_entry_by,b.LineClearance_entry_date,b.LineClearance_approved_by,b.LineClearance_approved_date
            FROM stages a LEFT JOIN bmr_stages b ON a.stage=b.stage AND a.step_id=b.step_id AND a.id=b.stages_id 
            AND (a.substep_id = b.substep_id OR (a.substep_id IS NULL AND b.substep_id IS NULL) OR (a.substep_id = '' AND b.substep_id = ''))
            WHERE ".$where_clause;
        }



            
        	$result = $conn->query($sql);
    	if(!$result){
    	    echo json_encode(array("status"=>"error","msg"=>"Query failed: ".$conn->error,"sql"=>substr($sql, 0, 500)));
    	    exit;
    	}
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
    		      $row["procedures"] = json_decode($row["procedure"]);
    		      $row["bmr_pocedure"] = json_decode($row["bmr_pocedure"]);
    		      
    		      $row["equipment"] = json_decode($row["equipment"]);
    		      $row["bmr_equipment"] = json_decode($row["bmr_equipment"]);
    		      
    		      $row["CleaningChecks"] = json_decode($row["CleaningChecks"]);
    		      $row["bmr_CleaningChecks"] = json_decode($row["bmr_CleaningChecks"]);
    		      
    		      $row["room"] = json_decode($row["room"]);
    		      $row["bmr_room"] = json_decode($row["bmr_room"]);
    		      
    		      $row["weighing"] = json_decode($row["weighing"]);
    		      $row["bmr_weighing"] = json_decode($row["bmr_weighing"]);
    		      
    		      $row["table"] =  $row["table"];
        		      $row["bmr_table"] = $row["bmr_table"];
    		      
    		      $row["roomActions"] = json_decode($row["roomActions"]);
    		      $row["bmr_roomActions"] = json_decode($row["bmr_roomActions"]);
    		      $row["instructions"] = json_decode($row["instructions"]);
    		      $row["initial_checks"] = json_decode($row["initial_checks"]);
    		      $row["environments"] = json_decode($row["environments"]);
    		      $row["inprocess"] = json_decode($row["inprocess"]);
    		      $row["EquipmemntCleaning"] = json_decode($row["EquipmemntCleaning"]);
    		      $row["QcSample"] = json_decode($row["QcSample"]);
    		      $row["Logbook"] = json_decode($row["Logbook"]);
    		      $row["LineClearance"] = json_decode($row["LineClearance"]);
    		      $row["bmr_LineClearance"] = json_decode($row["bmr_LineClearance"]);
    		      
    $array = $row['Logbook'];


    $logbook_length = count($array);
    // Loop over each entry in the Logbook array
    $output1 = []; // Initialize $output1 as an empty array
    for ($i = 0; $i < $logbook_length; $i++) {
     
        $values = $array[$i];
        $form_no = isset($values->form_no) ? safe_escape($conn, $values->form_no) : "";
        $stage_escaped = isset($row["stage"]) ? safe_escape($conn, $row["stage"]) : "";
        $step_id_escaped = isset($row["step_id"]) ? safe_escape($conn, $row["step_id"]) : "";
        $sql1 = "SELECT a.*,JSON_LENGTH(b.PrepareMaster) AS prepare_master_length,JSON_LENGTH(b.RoomLogin) AS RoomLogin_length,JSON_LENGTH(b.RoomLogbook) AS RoomLogbook_length
        ,JSON_LENGTH(b.RoomLogout) AS RoomLogout_length,JSON_LENGTH(b.EquipmentCleaning) AS EquipmentCleaning_length,JSON_LENGTH(b.majorClean)AS majorClean_length 
        ,b.PrepareMaster as bmr_PrepareMaster,b.RoomLogin as bmr_RoomLogin,b.RoomLogbook as bmr_RoomLogbook,b.RoomLogout as bmr_RoomLogout,
        b.EquipmentCleaning as bmr_EquipmentCleaning,b.majorClean as bmr_majorClean
        FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE b.stage='".$stage_escaped."'  and b.step_id='".$step_id_escaped."' and  a.form_no='".$form_no."'";
        // FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE  a.form_no='".$form_no."'";
        $result1 = $conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            
            // Loop through each row of the result set
            while ($row1 = $result1->fetch_assoc()) {
                 
                $output1[] = $row1; // Append each row to the output array
                
              
            }
        }
    } 

    		
$output1_length = count($output1);
// echo "Length of output1: " . $output1_length;
    		    
    		   
    		   if($output1_length==0){
    		         for ($i = 0; $i < $logbook_length; $i++) {
     
        $values = $array[$i];
        $form_no = isset($values->form_no) ? safe_escape($conn, $values->form_no) : "";
        $sql2 = "SELECT a.*,JSON_LENGTH(b.PrepareMaster) AS prepare_master_length,JSON_LENGTH(b.RoomLogin) AS RoomLogin_length,JSON_LENGTH(b.RoomLogbook) AS RoomLogbook_length
        ,JSON_LENGTH(b.RoomLogout) AS RoomLogout_length,JSON_LENGTH(b.EquipmentCleaning) AS EquipmentCleaning_length,JSON_LENGTH(b.majorClean)AS majorClean_length 
        ,b.PrepareMaster as bmr_PrepareMaster,b.RoomLogin as bmr_RoomLogin,b.RoomLogbook as bmr_RoomLogbook,b.RoomLogout as bmr_RoomLogout,
        b.EquipmentCleaning as bmr_EquipmentCleaning,b.majorClean as bmr_majorClean
        FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE  a.form_no='".$form_no."'";
        // FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE b.stage='".$stage_escaped."'  and b.step_id='".$step_id_escaped."' and  a.form_no='".$form_no."'";
        $result2 = $conn->query($sql2);
        if ($result2 && $result2->num_rows > 0) {
            
            // Loop through each row of the result set
            while ($row2 = $result2->fetch_assoc()) {
                    $row2["PrepareMaster"] = json_decode($row2["PrepareMaster"]);
                    $row2["bmr_PrepareMaster"] = json_decode($row2["bmr_PrepareMaster"]);
                    $row2["RoomLogin"] = json_decode($row2["RoomLogin"]);
                    $row2["bmr_RoomLogin"] = json_decode($row2["bmr_RoomLogin"]);
                    $row2["RoomLogbook"] = json_decode($row2["RoomLogbook"]);
                    $row2["bmr_RoomLogbook"] = json_decode($row2["bmr_RoomLogbook"]);
                    $row2["EquipmentCleaning"] = json_decode($row2["EquipmentCleaning"]);
                    $row2["bmr_EquipmentCleaning"] = json_decode($row2["bmr_EquipmentCleaning"]);
                    $row2["majorClean"] = json_decode($row2["majorClean"]);
                    $row2["bmr_majorClean"] = json_decode($row2["bmr_majorClean"]);
                    $row2["RoomLogout"] = json_decode($row2["RoomLogout"]);
                    $row2["bmr_RoomLogout"] = json_decode($row2["bmr_RoomLogout"]);
                $output2[] = $row2; // Append each row to the output array
                
              
            }
        }
    } 
        $row["equipments_cleaning"] = $output2;
    		   } 
    		   else{
    		         for ($i = 0; $i < $logbook_length; $i++) {
     
        $values = $array[$i];
        $form_no = isset($values->form_no) ? safe_escape($conn, $values->form_no) : "";
        $stage_escaped = isset($row["stage"]) ? safe_escape($conn, $row["stage"]) : "";
        $step_id_escaped = isset($row["step_id"]) ? safe_escape($conn, $row["step_id"]) : "";
        $sql2 = "SELECT a.*,JSON_LENGTH(b.PrepareMaster) AS prepare_master_length,JSON_LENGTH(b.RoomLogin) AS RoomLogin_length,JSON_LENGTH(b.RoomLogbook) AS RoomLogbook_length
        ,JSON_LENGTH(b.RoomLogout) AS RoomLogout_length,JSON_LENGTH(b.EquipmentCleaning) AS EquipmentCleaning_length,JSON_LENGTH(b.majorClean)AS majorClean_length 
        ,b.PrepareMaster as bmr_PrepareMaster,b.RoomLogin as bmr_RoomLogin,b.RoomLogbook as bmr_RoomLogbook,b.RoomLogout as bmr_RoomLogout,
        b.EquipmentCleaning as bmr_EquipmentCleaning,b.majorClean as bmr_majorClean,
        b.majorClean_approved_by,b.EquipmentCleaning_approved_by,b.RoomLogout_approved_by,b.RoomLogbook_approved_by,b.RoomLogin_approved_by,b.PrepareMaster_approved_by
        FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE b.stage='".$stage_escaped."'  and b.step_id='".$step_id_escaped."' and  a.form_no='".$form_no."'";
        // FROM equipment_cleaning a left join bmr_logbook b on a.id=b.equipment_cleaning_id  WHERE  a.form_no='".$form_no."'";
        $result2 = $conn->query($sql2);
        if ($result2 && $result2->num_rows > 0) {
            
            // Loop through each row of the result set
            while ($row2 = $result2->fetch_assoc()) {
                    $row2["PrepareMaster"] = json_decode($row2["PrepareMaster"]);
                    $row2["bmr_PrepareMaster"] = json_decode($row2["bmr_PrepareMaster"]);
                    $row2["RoomLogin"] = json_decode($row2["RoomLogin"]);
                    $row2["bmr_RoomLogin"] = json_decode($row2["bmr_RoomLogin"]);
                    $row2["RoomLogbook"] = json_decode($row2["RoomLogbook"]);
                    $row2["bmr_RoomLogbook"] = json_decode($row2["bmr_RoomLogbook"]);
                    $row2["EquipmentCleaning"] = json_decode($row2["EquipmentCleaning"]);
                    $row2["bmr_EquipmentCleaning"] = json_decode($row2["bmr_EquipmentCleaning"]);
                    $row2["majorClean"] = json_decode($row2["majorClean"]);
                    $row2["bmr_majorClean"] = json_decode($row2["bmr_majorClean"]);
                    $row2["RoomLogout"] = json_decode($row2["RoomLogout"]);
                    $row2["bmr_RoomLogout"] = json_decode($row2["bmr_RoomLogout"]);
                $output2[] = $row2; // Append each row to the output array
                
              
            }
        }
    } 
        $row["equipments_cleaning"] = $output2;
    		   } 
    		    
    		  //  $row["equipments_cleaning"] = $output1;
    
    		 
    		    
    		    
                
    		    
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "GET_bmr_stages_stepsMeha") {
//             ini_set('display_errors', 1);
// error_reporting(E_ALL);


        $work_order_no = isset($_GET["work_order_no"]) ? safe_escape($conn, $_GET["work_order_no"]) : "";
        $Stage_id = isset($_GET["Stage_id"]) ? safe_escape($conn, $_GET["Stage_id"]) : "";
        $stpe_id = isset($_GET["stpe_id"]) ? safe_escape($conn, $_GET["stpe_id"]) : "";
        $substep_id = isset($_GET["substep_id"]) ? safe_escape($conn, $_GET["substep_id"]) : "";

        // Build WHERE clause for step/substep filtering
        $where_clause = "a.stage = '".$Stage_id."' AND a.step_id = '".$stpe_id."'";
        
        // If substep_id is provided, filter by substep; otherwise, only get steps (substep_id IS NULL)
        if ($substep_id != '') {
            $where_clause .= " AND a.substep_id='".$substep_id."'";
        } else {
            $where_clause .= " AND (a.substep_id IS NULL OR a.substep_id='')";
        }

        if($work_order_no != ''){
            $output = Array();
            $sql = "
    SELECT 
        a.*, 
        (SELECT checking_in FROM manufacturing_process_step m WHERE b.step_id = m.id) AS checking_in, 
        b.saved_by AS bmr_saved_by, 
        b.id AS bmr_stages_id, 
        b.table_status AS bmr_table_status, 
        b.procedure_status AS bmr_procedure_status, 
        b.LineClearance_status AS bmr_LineClearance_status, 
        b.equipment_status AS bmr_equipment_status, 
        b.CleaningChecks_status AS bmr_CleaningChecks_status, 
        b.room_status AS bmr_room_status, 
        b.roomActions_status AS bmr_roomActions_status, 
        b.weighing_status AS bmr_weighing_status, 
        b.ReactorCapacity_status AS bmr_ReactorCapacity_status, 
        b.Observation_status AS bmr_Observation_status, 
        b.INP_Transfer_status AS bmr_INP_Transfer_status, 
        b.PPT_Transfer_status AS bmr_PPT_Transfer_status, 
        b.WIPReport_status AS bmr_WIPReport_status, 
        b.DosingPh_status AS bmr_DosingPh_status, 
        b.BLENDING_SECTION_status AS bmr_BLENDING_SECTION_status, 
        b.EQUIPMENT_CLEANING_RECORD_status AS bmr_EQUIPMENT_CLEANING_RECORD_status, 
        b.TIME_CYCLES_DETAILS_status AS bmr_TIME_CYCLES_DETAILS_status, 
        b.MATERIAL_CONSUMPTION_DETAILS_status AS bmr_MATERIAL_CONSUMPTION_DETAILS_status, 
        
        b.procedure AS bmr_procedure, 
        b.LineClearance AS bmr_LineClearance, 
        b.equipment AS bmr_equipment, 
        b.CleaningChecks AS bmr_CleaningChecks, 
        b.room AS bmr_room, 
        b.weighing AS bmr_weighing, 
        b.tables AS bmr_table, 
        b.roomActions AS bmr_roomActions, 
        b.ReactorCapacity AS bmr_ReactorCapacity, 
        b.Observation AS bmr_Observation, 
        b.INP_Transfer AS bmr_INP_Transfer, 
        b.PPT_Transfer AS bmr_PPT_Transfer, 
        b.WIPReport AS bmr_WIPReport, 
        b.DosingPh AS bmr_DosingPh, 
        b.BLENDING_SECTION AS bmr_BLENDING_SECTION, 
        b.EQUIPMENT_CLEANING_RECORD AS bmr_EQUIPMENT_CLEANING_RECORD, 
        b.TIME_CYCLES_DETAILS AS bmr_TIME_CYCLES_DETAILS, 
        b.MATERIAL_CONSUMPTION_DETAILS AS bmr_MATERIAL_CONSUMPTION_DETAILS,
        
        b.procedure_entry_by, 
        b.procedure_approved_by, 
        b.equipment_entry_by, 
        b.equipment_approved_by, 
        b.CleaningChecks_entry_by, 
        b.CleaningChecks_approved_by, 
        b.room_entry_by, 
        b.room_approved_by, 
        b.roomActions_entry_by, 
        b.roomActions_approved_by, 
        b.weighing_entry_by, 
        b.weighing_approved_by, 
        b.ReactorCapacity_entry_by,
        b.ReactorCapacity_approved_by,
        b.Observation_entry_by,
        b.Observation_approved_by,
        b.INP_Transfer_entry_by,
        b.INP_Transfer_approved_by,
        b.PPT_Transfer_entry_by,
        b.PPT_Transfer_approved_by,
        b.WIPReport_entry_by,
        b.WIPReport_approved_by,
        b.DosingPh_entry_by,
        b.DosingPh_approved_by,
        b.BLENDING_SECTION_entry_by,
        b.BLENDING_SECTION_approved_by,
        b.EQUIPMENT_CLEANING_RECORD_entry_by,
        b.EQUIPMENT_CLEANING_RECORD_approved_by,
        b.TIME_CYCLES_DETAILS_entry_by,
        b.TIME_CYCLES_DETAILS_approved_by,
        b.MATERIAL_CONSUMPTION_DETAILS_entry_by,
        b.MATERIAL_CONSUMPTION_DETAILS_approved_by,
        
        b.procedure_entry_date, 
        b.procedure_approved_date, 
        b.equipment_entry_date, 
        b.equipment_approved_date, 
        b.CleaningChecks_entry_date, 
        b.CleaningChecks_approved_date, 
        b.room_entry_date, 
        b.room_approved_date, 
        b.roomActions_entry_date, 
        b.roomActions_approved_date, 
        b.weighing_entry_date, 
        b.weighing_approved_date,
        b.ReactorCapacity_entry_on,
        b.ReactorCapacity_approved_on,
        b.Observation_entry_on,
        b.Observation_approved_on,
        b.INP_Transfer_entry_on,
        b.INP_Transfer_approved_on,
        b.PPT_Transfer_entry_on,
        b.PPT_Transfer_approved_on,
        b.WIPReport_entry_on,
        b.WIPReport_approved_on,
        b.DosingPh_entry_on,
        b.DosingPh_approved_on,
        b.BLENDING_SECTION_entry_on,
        b.BLENDING_SECTION_approved_on,
        b.EQUIPMENT_CLEANING_RECORD_entry_on,
        b.EQUIPMENT_CLEANING_RECORD_approved_on,
        b.TIME_CYCLES_DETAILS_entry_on,
        b.TIME_CYCLES_DETAILS_approved_on,
        b.MATERIAL_CONSUMPTION_DETAILS_entry_on,
        b.MATERIAL_CONSUMPTION_DETAILS_approved_on

        
    FROM stages a 
    LEFT JOIN bmr_stages b 
        ON a.stage = b.stage 
        AND a.step_id = b.step_id 
        AND a.id = b.stages_id
        AND (a.substep_id = b.substep_id OR (a.substep_id IS NULL AND b.substep_id IS NULL) OR (a.substep_id = '' AND b.substep_id = ''))
    WHERE 
        ".$where_clause."
        AND (b.work_order_no IS NULL OR b.work_order_no LIKE '%".$work_order_no."%') 
";
        }else{
            $output = Array();
            $sql = "SELECT 
                        a.*,
                        (SELECT substep_name FROM manufacturing_process_substep s WHERE s.id=a.substep_id) AS substep_name,
                        b.id AS bmr_stages_id, 
                        b.table_status AS bmr_table_status, 
                        b.procedure_status AS bmr_procedure_status, 
                        b.LineClearance_status AS bmr_LineClearance_status, 
                        b.equipment_status AS bmr_equipment_status, 
                        b.CleaningChecks_status AS bmr_CleaningChecks_status, 
                        b.room_status AS bmr_room_status, 
                        b.roomActions_status AS bmr_roomActions_status, 
                        b.weighing_status AS bmr_weighing_status, 
                        b.ReactorCapacity_status AS bmr_ReactorCapacity_status, 
                        b.Observation_status AS bmr_Observation_status, 
                        b.INP_Transfer_status AS bmr_INP_Transfer_status, 
                        b.PPT_Transfer_status AS bmr_PPT_Transfer_status, 
                        b.WIPReport_status AS bmr_WIPReport_status, 
                        b.DosingPh_status AS bmr_DosingPh_status, 
                        b.BLENDING_SECTION_status AS bmr_BLENDING_SECTION_status, 
                        b.EQUIPMENT_CLEANING_RECORD_status AS bmr_EQUIPMENT_CLEANING_RECORD_status, 
                        b.TIME_CYCLES_DETAILS_status AS bmr_TIME_CYCLES_DETAILS_status, 
                        b.MATERIAL_CONSUMPTION_DETAILS_status AS bmr_MATERIAL_CONSUMPTION_DETAILS_status, 
                        b.procedure AS bmr_procedure, 
                        b.LineClearance AS bmr_LineClearance, 
                        b.equipment AS bmr_equipment, 
                        b.CleaningChecks AS bmr_CleaningChecks, 
                        b.room AS bmr_room, 
                        b.weighing AS bmr_weighing, 
                        b.tables AS bmr_table, 
                        b.roomActions AS bmr_roomActions, 
                        b.ReactorCapacity AS bmr_ReactorCapacity, 
                        b.Observation AS bmr_Observation, 
                        b.INP_Transfer AS bmr_INP_Transfer, 
                        b.PPT_Transfer AS bmr_PPT_Transfer, 
                        b.WIPReport AS bmr_WIPReport, 
                        b.DosingPh AS bmr_DosingPh, 
                        b.BLENDING_SECTION AS bmr_BLENDING_SECTION, 
                        b.EQUIPMENT_CLEANING_RECORD AS bmr_EQUIPMENT_CLEANING_RECORD, 
                        b.TIME_CYCLES_DETAILS AS bmr_TIME_CYCLES_DETAILS, 
                        b.MATERIAL_CONSUMPTION_DETAILS AS bmr_MATERIAL_CONSUMPTION_DETAILS, 
                        b.ReactorCapacity_entry_by, 
                        b.ReactorCapacity_approved_by, 
                        b.ReactorCapacity_entry_on, 
                        b.ReactorCapacity_approved_on, 
                        b.Observation_entry_by, 
                        b.Observation_approved_by, 
                        b.Observation_entry_on, 
                        b.Observation_approved_on, 
                        b.INP_Transfer_entry_by, 
                        b.INP_Transfer_approved_by, 
                        b.INP_Transfer_entry_on, 
                        b.INP_Transfer_approved_on, 
                        b.PPT_Transfer_entry_by, 
                        b.PPT_Transfer_approved_by, 
                        b.PPT_Transfer_entry_on, 
                        b.PPT_Transfer_approved_on, 
                        b.WIPReport_entry_by, 
                        b.WIPReport_approved_by, 
                        b.WIPReport_entry_on, 
                        b.WIPReport_approved_on, 
                        b.DosingPh_entry_by, 
                        b.DosingPh_approved_by, 
                        b.DosingPh_entry_on, 
                        b.DosingPh_approved_on, 
                        b.BLENDING_SECTION_entry_by, 
                        b.BLENDING_SECTION_approved_by, 
                        b.BLENDING_SECTION_entry_on, 
                        b.BLENDING_SECTION_approved_on, 
                        b.EQUIPMENT_CLEANING_RECORD_entry_by, 
                        b.EQUIPMENT_CLEANING_RECORD_approved_by, 
                        b.EQUIPMENT_CLEANING_RECORD_entry_on, 
                        b.EQUIPMENT_CLEANING_RECORD_approved_on, 
                        b.TIME_CYCLES_DETAILS_entry_by, 
                        b.TIME_CYCLES_DETAILS_approved_by, 
                        b.TIME_CYCLES_DETAILS_entry_on, 
                        b.TIME_CYCLES_DETAILS_approved_on, 
                        b.MATERIAL_CONSUMPTION_DETAILS_entry_by, 
                        b.MATERIAL_CONSUMPTION_DETAILS_approved_by, 
                        b.MATERIAL_CONSUMPTION_DETAILS_entry_on, 
                        b.MATERIAL_CONSUMPTION_DETAILS_approved_on,  
                        b.procedure_entry_by, 
                        b.procedure_approved_by, 
                        b.equipment_entry_by, 
                        b.equipment_approved_by, 
                        b.CleaningChecks_entry_by, 
                        b.CleaningChecks_approved_by, 
                        b.room_entry_by, 
                        b.room_approved_by, 
                        b.roomActions_entry_by, 
                        b.roomActions_approved_by, 
                        b.weighing_entry_by, 
                        b.weighing_approved_by, 
                        b.procedure_entry_date, 
                        b.procedure_approved_date, 
                        b.equipment_entry_date, 
                        b.equipment_approved_date, 
                        b.CleaningChecks_entry_date, 
                        b.CleaningChecks_approved_date, 
                        b.room_entry_date, 
                        b.room_approved_date, 
                        b.roomActions_entry_date, 
                        b.roomActions_approved_date, 
                        b.weighing_entry_date, 
                        b.weighing_approved_date, 
                        b.LineClearance_entry_by, 
                        b.LineClearance_entry_date, 
                        b.LineClearance_approved_by, 
                        b.LineClearance_approved_date 
                    FROM 
                        stages a 
                    LEFT JOIN 
                        bmr_stages b 
                    ON 
                        a.stage = b.stage 
                        AND a.step_id = b.step_id 
                        AND a.id = b.stages_id
                        AND (a.substep_id = b.substep_id OR (a.substep_id IS NULL AND b.substep_id IS NULL) OR (a.substep_id = '' AND b.substep_id = ''))
                    WHERE 
                        ".$where_clause ."
                        AND a.step_id = '".$stpe_id."' 
                        ";
        }



            
        	$result = $conn->query($sql);
    	if(!$result){
    	    echo json_encode(array("status"=>"error","msg"=>"Query failed: ".$conn->error,"sql"=>substr($sql, 0, 500)));
    	    exit;
    	}
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
    		      $row["procedures"] = json_decode($row["procedure"]);
    		      $row["bmr_pocedure"] = json_decode($row["bmr_pocedure"]);
    		      
    		      $row["equipment"] = json_decode($row["equipment"]);
    		      $row["bmr_equipment"] = json_decode($row["bmr_equipment"]);
    		      
    		      $row["CleaningChecks"] = json_decode($row["CleaningChecks"]);
    		      $row["bmr_CleaningChecks"] = json_decode($row["bmr_CleaningChecks"]);
    		      
    		      $row["room"] = json_decode($row["room"]);
    		      $row["bmr_room"] = json_decode($row["bmr_room"]);
    		      
    		      $row["weighing"] = json_decode($row["weighing"]);
    		      $row["bmr_weighing"] = json_decode($row["bmr_weighing"]);
    		      
    		      $row["table"] =  $row["table"];
        		      $row["bmr_table"] = $row["bmr_table"];
    		      
    		      $row["roomActions"] = json_decode($row["roomActions"]);
    		      $row["bmr_roomActions"] = json_decode($row["bmr_roomActions"]);
    		      $row["instructions"] = json_decode($row["instructions"]);
    		      $row["initial_checks"] = json_decode($row["initial_checks"]);
    		      $row["environments"] = json_decode($row["environments"]);
    		      $row["inprocess"] = json_decode($row["inprocess"]);
    		      $row["EquipmemntCleaning"] = json_decode($row["EquipmemntCleaning"]);
    		      $row["QcSample"] = json_decode($row["QcSample"]);
    		      $row["Logbook"] = json_decode($row["Logbook"]);
    		      $row["LineClearance"] = json_decode($row["LineClearance"]);
    		      $row["bmr_LineClearance"] = json_decode($row["bmr_LineClearance"]);
    		      
    
    
    		 
    		    
    		    
                
    		    
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
     else if ($_GET["type"] == "getSpecificationsLog") {
        $output = Array();
        $dosage_form = isset($_GET["dosage_form"]) ? safe_escape($conn, $_GET["dosage_form"]) : "";
        $grade = isset($_GET["grade"]) ? safe_escape($conn, $_GET["grade"]) : "";
        $status = isset($_GET["status"]) ? safe_escape($conn, $_GET["status"]) : "";
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Inprocess%' AND p.dosage_form LIKE '%".$dosage_form."%' AND p.grade LIKE '%".$grade."%' AND s.status LIKE '%".$status."%'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $specification_no = isset($row["specification_no"]) ? safe_escape($conn, $row["specification_no"]) : "";
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$specification_no."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['tests'] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$specification_no."'";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['revision_history'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "get_logform_no") {
        $output = Array();
        $activity_type = isset($_GET['activity_type']) ? safe_escape($conn, $_GET['activity_type']) : "";
        $sql = "select * from equipment_cleaning where activity_type='".$activity_type."'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    // New endpoint: Get substeps for a specific step
    else if ($_GET["type"] == "GET_substeps_for_step") {
        $output = Array();
        $step_id = isset($_GET["step_id"]) ? safe_escape($conn, $_GET["step_id"]) : "";
        
        if ($step_id != '') {
            $sql = "SELECT * FROM manufacturing_process_substep WHERE step_id='".$step_id."' AND is_active=1 ORDER BY substep_order ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    // New endpoint: Get all steps (without substeps) for a stage
    else if ($_GET["type"] == "GET_steps_only") {
        $output = Array();
        $Stage_id = isset($_GET["Stage_id"]) ? safe_escape($conn, $_GET["Stage_id"]) : "";
        
        if ($Stage_id != '') {
            $sql = "SELECT DISTINCT a.step_id, a.stage, 
                    (SELECT step_name FROM manufacturing_process_step m WHERE m.id=a.step_id) AS step_name,
                    (SELECT checking_in FROM manufacturing_process_step m WHERE m.id=a.step_id) AS checking_in
                    FROM stages a 
                    WHERE a.stage='".$Stage_id."' AND (a.substep_id IS NULL OR a.substep_id='')
                    ORDER BY a.step_id ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Get substeps for this step
                    $step_id_escaped = safe_escape($conn, $row["step_id"]);
                    $substeps_sql = "SELECT * FROM manufacturing_process_substep WHERE step_id='".$step_id_escaped."' AND is_active=1 ORDER BY substep_order ASC";
                    $substeps_result = $conn->query($substeps_sql);
                    $substeps = Array();
                    if ($substeps_result && $substeps_result->num_rows > 0) {
                        while ($substep_row = $substeps_result->fetch_assoc()) {
                            $substeps[] = $substep_row;
                        }
                    }
                    $row["substeps"] = $substeps;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>