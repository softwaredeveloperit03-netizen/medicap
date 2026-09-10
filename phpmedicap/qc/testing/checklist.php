<?php
    require '../../db.php';
    require '../../token.php';
     require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);

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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     if ($_GET["type"] == "saveOOSChecklist") {
         
              $json_obj = json_encode($input["checklistList"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql = "INSERT INTO oos_checklist (plant_id, parameter, result, comments,point,subpoint)
       VALUES ('".$_GET["plant_id"]."','".$values["Parameter"]."','".$values["Result"]."','".json_encode($values["Comments"])."','".$values["point"]."','".$values["subpoint"]."')";
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
        
     }else if ($_GET["type"] == "getooschecklistmaster") {


        $output = Array();
        $sql = " select * from oos_checklist order by id asc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["comments"] = json_decode($row["comments"]); 
             
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    
}

$conn->close();
?>