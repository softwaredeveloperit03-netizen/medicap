<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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
    if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    } 

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

 
 
    if($_GET['type'] == 'saveRevisionRequest') {
        
        $sql1 = "INSERT INTO `revisionRequest`( `plant_id`, `refDocNo`, `refDocId`,`refDocName`, `revisionComment`, `reqFor`,
        `entryBy`, `entryOn`) VALUES ('".$_GET['plant_id']."','".$input['sopNo']."','".$input['iniId']."','".$input['sopName']."',
        '".$input['revisionComment']."','SOP Revision Request','".$_GET["emp_id"]."','$entry_date')";
       
        if($conn->query($sql1)) {
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
        
    }
    else if($_GET['type'] == 'getPendingRevisionRequest') {
        $output = array();
        $sql = "SELECT * FROM revisionRequest where status = 'Pending' AND  plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getRevisionRequestLog') {
        $output = array();
        $sql = "SELECT * FROM revisionRequest WHERE plant_id = '".$_GET['plant_id']."'";
        if (isset($_GET['status']) && trim($_GET['status']) != '' && trim($_GET['status']) != 'All') {
            $sql .= " AND status = '".$conn->real_escape_string(trim($_GET['status']))."'";
        }
        if (isset($_GET['req_for']) && trim($_GET['req_for']) != '' && trim($_GET['req_for']) != 'All') {
            $sql .= " AND reqFor = '".$conn->real_escape_string(trim($_GET['req_for']))."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'approveRevisionRequest') {
        
        $sql1 = "UPDATE `revisionRequest`  SET status = 'Accepted' WHERE id = '".$_GET['id']."'";
       
        if($conn->query($sql1)) {
             
            $sql11 = '';
            if($input['reqFor'] == 'SOP Revision Request'){
                $sql11 = "UPDATE `sopinitiation`  SET revisionStatus = 'Accepted' WHERE id = '".$input['refDocId']."'";
            }
            else if($input['reqFor'] == 'Specification Revision Request'){
                $sql11 = '';
            }

            if($sql11 == '' || $conn->query($sql11)) {
                echo "{\"status\":\"success\"}";
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
             
            
        }else{
            echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
        
    }
   


   
    
    
     
	  
 } else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>