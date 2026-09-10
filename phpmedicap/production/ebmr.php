<?php
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "addInstruction") {
        $sql = "SELECT instructions FROM unitformula WHERE mfr_no='".$input["mfr_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $instructions = array();
                if ($row["instructions"] == '') {
                    $instructions = array();
                } else {
                    $temp = json_decode($row["instructions"]);
                    for ($i = 0; $i < count($temp); $i++) {
                        $temp1 = $temp[$i];
                        $instructions[] = $temp1;
                    }
                }
                $instructions[] = $input["instruction"];
                
                $sql = "UPDATE unitformula SET instructions='".json_encode($instructions)."' WHERE mfr_no='".$input["mfr_no"]."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"Instruction saved successfully!", "instructions"=>$instructions));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
            }
        } else {
            echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
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

    else if ($_GET["type"] == "deleteInstruction") {
        $sql = "UPDATE unitformula SET instructions='.json_encode($input).' WHERE mfr_no='".$_GET["mfr_no"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Instruction deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
   else if ($_GET["type"] == "addAbbreviation") {
        $sql = "SELECT abbreviation FROM unitformula WHERE mfr_no='".$input["mfr_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $abbreviation = array();
                if ($row["abbreviation"] == '') {
                    $abbreviation = array();
                } else {
                    $temp = json_decode($row["abbreviation"]);
                    for ($i = 0; $i < count($temp); $i++) {
                        $temp1 = $temp[$i];
                        $abbreviation[] = $temp1;
                    }
                }
                $abbreviation[] = $input["abbreviation"];
                
                $sql = "UPDATE unitformula SET abbreviation='".json_encode($abbreviation)."' WHERE mfr_no='".$input["mfr_no"]."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"abbreviation saved successfully!", "abbreviation"=>$abbreviation));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
            }
        } else {
            echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        }
    }
    else if ($_GET["type"] == "deleteAbbreviation") {
        $sql = "UPDATE unitformula SET abbreviation='.json_encode($input).' WHERE mfr_no='".$_GET["mfr_no"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Abbreviation deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }else if($_GET["type"] =="saveBMRchecklist"){
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