<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php'; 
    
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);

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
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getemployee") {
        $output = Array();
        $sql = "SELECT emp_id,firstname,middlename,lastname,department FROM employee WHERE plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
   else if ($_GET["type"] == "getChat") {
        $output = Array();
         $sql = "SELECT MessageID, SenderID, ReceiverID, Content, Timestamp, Status
            FROM Message
            WHERE (SenderID = '".$_GET["SenderID"]."' AND ReceiverID = '".$_GET["ReceiverID"]."')
               OR (SenderID = '".$_GET["ReceiverID"]."' AND ReceiverID = '".$_GET["SenderID"]."')
            ORDER BY Timestamp ASC
            ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

   
    else if($_GET["type"]=="InsertChat"){
        
        $SenderID = $input['SenderID'];
        $ReceiverID = $input['ReceiverID'];
        $message = $input['Content'];
        
         
        if(!empty($message)){
            $sql ="INSERT INTO Message (SenderID, ReceiverID, Content) VALUES ('".$SenderID."','".$ReceiverID."','".$message."')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }

        }
    }else{
        
    }
    
    }
?>
    