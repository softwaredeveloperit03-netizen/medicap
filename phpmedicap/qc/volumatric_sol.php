<?php

 ini_set('display_errors', 1);
 error_reporting(E_ALL);

    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
        $currentUrl =$_GET["description"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    function vol_json_response($payload) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        exit;
    }

    function vol_esc($conn, $value) {
        return $conn->real_escape_string((string)$value);
    }

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
  

    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

        if ($_GET["type"] == "saveVolumetricMaster") {
            
            $input = $_POST;

            if (empty($input["solution_no"])) {
                vol_json_response(array("status" => "Please select volumetric solution."));
            }
        
            $solution_no = vol_esc($conn, $input["solution_no"]);
            $storage_condition = vol_esc($conn, isset($input["storage_condition"]) ? $input["storage_condition"] : '');
            $expirary = vol_esc($conn, isset($input["Expirary"]) ? $input["Expirary"] : '');
            $disposal = vol_esc($conn, isset($input["disposal"]) ? $input["disposal"] : '');
            $chemicals = vol_esc($conn, isset($input['chemicals']) ? $input['chemicals'] : '[]');
            $safty = vol_esc($conn, isset($input['Safty']) ? $input['Safty'] : '[]');
            $equipment = vol_esc($conn, isset($input["Equipment"]) ? $input["Equipment"] : '[]');
            $reagent = vol_esc($conn, isset($input['Reagent']) ? $input['Reagent'] : '[]');
            $procedures = vol_esc($conn, isset($input['procedures']) ? $input['procedures'] : '[]');

            $sql = "UPDATE volumetric_solution SET
                storage_condition = '".$storage_condition."',
                Expirary = '".$expirary."',
                chemicals = '".$chemicals."',
                disposal = '".$disposal."',
                Safty = '".$safty."',
                Equipment = '".$equipment."',
                Reagent = '".$reagent."',
                procedures = '".$procedures."'
                WHERE solution_no = '".$solution_no."'";

            if ($conn->query($sql)) {
                if ($conn->affected_rows > 0) {
                    vol_json_response(array("status" => "success"));
                }
                $chk = $conn->query("SELECT id FROM volumetric_solution WHERE solution_no='".$solution_no."' LIMIT 1");
                if ($chk && $chk->num_rows > 0) {
                    vol_json_response(array("status" => "success"));
                }
                vol_json_response(array("status" => "No matching volumetric solution found."));
            }
            vol_json_response(array("status" => $conn->error ? $conn->error : "Update failed."));
        }
        
      
        


} else {
    vol_json_response(array("status" => "invalid"));
}

$conn->close();
?>
