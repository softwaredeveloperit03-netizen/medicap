<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
        $currentUrl =$_GET["description"];
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

 $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    
    // if ($_GET["type"] == "addInstruction") {
    //     $sql = "SELECT instructions FROM unitformula WHERE mfr_no='".$input["mfr_no"]."'";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $instructions = array();
    //             if ($row["instructions"] == '') {
    //                 $instructions = array();
    //             } else {
    //                 $temp = json_decode($row["instructions"]);
    //                 for ($i = 0; $i < count($temp); $i++) {
    //                     $temp1 = $temp[$i];
    //                     $instructions[] = $temp1;
    //                 }
    //             }
    //             $instructions[] = $input["instruction"];
                
    //          echo   $sql = "UPDATE unitformula SET instructions='".json_encode($instructions)."' WHERE mfr_no='".$input["mfr_no"]."'";
    //             if ($conn->query($sql)) {
    //                 echo json_encode(array("status"=>"success","msg"=>"Instruction saved successfully!", "instructions"=>$instructions));
    //             } else {
    //                 echo json_encode(array("status"=>"failed","msg"=>$conn->error));
    //             }
    //         }
    //     } else {
    //         echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
    //     }
    // } 
     if ($_GET["type"] == "addInstruction") {
        $sql = "insert into unitformula_instructions(plant_id,instruction,manufacturing_process_id,evaluation_parameter) values('".$_GET["plant_id"]."','".$input["instruction"]."','".$input["manufacturing_process_id"]."','".$input["evaluation_parameter"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Instruction deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
     if ($_GET["type"] == "addProcedure") {
        $sql = "insert into unitformula_Procedure(plant_id,instruction,manufacturing_process_id) values('".$_GET["plant_id"]."','".$input["instruction"]."','".$input["manufacturing_process_id"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Instruction deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
     if ($_GET["type"] == "get_Instruction") {
         
         
        $output = Array();
        // $sql = "select * from unitformula_instructions where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."'";
        $sql = "select * from unitformula_instructions";
        // $sql = "select * from unitformula_instructions where product_code like '%".$_GET["product_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
     if ($_GET["type"] == "get_Procedure") {
         
         
        $output = Array();
        // $sql = "select * from unitformula_Procedure where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."'";
        $sql = "select * from unitformula_Procedure";
        // $sql = "select * from unitformula_instructions where product_code like '%".$_GET["product_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
    else if ($_GET["type"] == "deleteInstruction") {
        $sql = "delete from  unitformula_instructions where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Instruction deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "deleteProceduresss") {
        $sql = "delete from  unitformula_Procedure where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Procedure deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
   else if ($_GET["type"] == "addAbbreviation") {
       
       
        $sql = "insert into unitformula_abbrivation(plant_id,manufacturing_process_id,short_form,full_form) values('".$_GET["plant_id"]."','".$input["manufacturing_process_id"]."','".$input["short_form"]."','".$input["full_form"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
   }
   else  if ($_GET["type"] == "getAbbrivation") {
         
         
        $output = Array();
        // $sql = "select * from unitformula_abbrivation  where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."' ";
        $sql = "select * from unitformula_abbrivation  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
   else if ($_GET["type"] == "addbmr_Equipments") {
       
       
        $sql = "insert into bmr_equipment(plant_id,manufacturing_process_id,equipment_name,equipment_code) values('".$_GET["plant_id"]."','".$input["manufacturing_process_id"]."','".$input["equipment_name"]."','".$input["equipment_code"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
   }
   else if ($_GET["type"] == "addbmr_EquipmentsMeha") {
       
       
        $sql = "insert into bmr_equipment(plant_id,manufacturing_process_id,equipment_name,equipment_code,product_code) values('".$_GET["plant_id"]."','".$input["manufacturing_process_id"]."','".$input["equipment_name"]."','".$input["equipment_code"]."','".$input["product_code"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
   }
   else  if ($_GET["type"] == "getbmrEquipments") {
         
         
        $output = Array();
        // $sql = "select * from bmr_equipment  where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."' ";
        $sql = "select * from bmr_equipment  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
   else  if ($_GET["type"] == "getbmrEquipmentsMeha") {
         
         
        $output = Array();
        // $sql = "select * from bmr_equipment  where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."' ";
        $sql = "select * from bmr_equipment  where product_code= '".$_GET["product_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
   else if ($_GET["type"] == "addbmr_CheckpintsMeha") {
       
       
        $sql = "insert into bmr_Checkpints (plant_id,`procedure`,evaluation_parameter,product_code) values('".$_GET["plant_id"]."','".$input["procedure"]."','".$input["evaluation_parameter"]."','".$input["product_code"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
   }
   else if ($_GET["type"] == "addGenInstruction") {
       
       
        $sql = "insert into bmr_GenInstruction  (plant_id,`procedure`,product_code) values('".$_GET["plant_id"]."','".$input["procedure"]."','".$input["product_code"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
   }
   else  if ($_GET["type"] == "getbmr_GenInstruction") {
         
         
        $output = Array();
        // $sql = "select * from bmr_equipment  where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."' ";
        $sql = "select * from bmr_GenInstruction  where product_code= '".$_GET["product_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
      else if ($_GET["type"] == "deletebmr_Checkpints") {
        $sql = "delete from  bmr_Checkpints where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
      else if ($_GET["type"] == "deletebmrEquipments") {
        $sql = "delete from  bmr_equipment where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
   else if ($_GET["type"] == "addbmrRooms") {
       
       
        $sql = "insert into bmrRooms(plant_id,manufacturing_process_id,room_name,room_code,log_no) values('".$_GET["plant_id"]."','".$input["manufacturing_process_id"]."','".$input["section_name"]."','".$input["section_code"]."','".$input["log_no"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    
   }
   else  if ($_GET["type"] == "getbmrRooms") {
         
         
        $output = Array();
        // $sql = "select * from bmr_equipment  where manufacturing_process_id = '".$_GET["manufacturing_process_id"]."' ";
        $sql = "select * from bmrRooms  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
     }
      else if ($_GET["type"] == "deleteBmrRoom") {
        $sql = "delete from  bmrRooms where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
      else if ($_GET["type"] == "delRoomClaCheck") {
        $sql = "delete from  room_clearance_checklist where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
      else if ($_GET["type"] == "delLineClaCheck") {
        $sql = "delete from  line_clearance_checklist where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>" deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    
    
//   else if ($_GET["type"] == "addAbbreviation") {
//         $sql = "SELECT abbreviation FROM unitformula WHERE mfr_no='".$input["mfr_no"]."'";
//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
//                 $abbreviation = array();
//                 if ($row["abbreviation"] == '') {
//                     $abbreviation = array();
//                 } else {
//                     $temp = json_decode($row["abbreviation"]);
//                     for ($i = 0; $i < count($temp); $i++) {
//                         $temp1 = $temp[$i];
//                         $abbreviation[] = $temp1;
//                     }
//                 }
//                 $abbreviation[] = $input["abbreviation"];
                
//                 $sql = "UPDATE unitformula SET abbreviation='".json_encode($abbreviation)."' WHERE mfr_no='".$input["mfr_no"]."'";
//                 if ($conn->query($sql)) {
//                     echo json_encode(array("status"=>"success","msg"=>"abbreviation saved successfully!", "abbreviation"=>$abbreviation));
//                 } else {
//                     echo json_encode(array("status"=>"failed","msg"=>$conn->error));
//                 }
//             }
//         } else {
//             echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
//         }
//     }
    else if ($_GET["type"] == "save_map_bmr") {      
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $json_obj = json_encode($input["stage_list"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
          $sql = "insert into bmr_map(plant_id,manufacturing_process_id,product_code) values('".$_GET["plant_id"]."','".$values["Process_id"]."','".$values["product_code"]."')";

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
    else if ($_GET["type"] == "deleteAbbreviation") {
        $sql = "delete from  unitformula_abbrivation where id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if($_GET["type"] =="saveBMRchecklist"){
        $sql = "SELECT bmr_checklist FROM unitformula WHERE mfr_no='".$input["mfr_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bmr_checklist = array();
                if ($row["bmr_checklist"] == '') {
                    $bmr_checklist = array();
                } else {
                    $temp = json_decode($row["bmr_checklist"]);
                    for ($i = 0; $i < count($temp); $i++) {
                        $temp1 = $temp[$i];
                        $bmr_checklist[] = $temp1;
                    }
                }
                $bmr_checklist[] = $input["bmr_checklist"];
                
                $sql = "UPDATE unitformula SET bmr_checklist='".json_encode($bmr_checklist)."' WHERE mfr_no='".$input["mfr_no"]."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"BMR Checklist saved successfully!", "bmr_checklist"=>$bmr_checklist));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
            }
        } else {
            echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        }
    }else if($_GET["type"] == "deletebmrChecklist"){
         $sql = "UPDATE unitformula SET bmr_checklist='.json_encode($input).' WHERE mfr_no='".$_GET["mfr_no"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"checklist deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }


}

$conn->close();
?>