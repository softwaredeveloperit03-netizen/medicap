<?php 
require '../db.php';
require '../token.php';
$output = Array();
$token = $_GET["token"];
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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveExternalTrainer") {
        $input = $_POST;
        
        $id = 0;
        $sql = "SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'externaltrainer' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row["auto_increment"];
                break;
            }
        }
        
        
        $certificate = "";
        $resume = "";
        if(isset($_FILES['certificate'])) {
            $file_tmp =$_FILES['certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['certificate']['name'])));
            $file_name = $id."certificate.".$file_ext;
            $certificate = $file_name;
            move_uploaded_file($file_tmp,"upload/".$file_name);
        }
        if(isset($_FILES['resume'])) {
            $file_tmp =$_FILES['resume']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['resume']['name'])));
            $file_name = $id."resume.".$file_ext;
            $resume = $file_name;
            move_uploaded_file($file_tmp,"upload/".$file_name);
        }
        
        $sql = "INSERT INTO externaltrainer (user_no, trainer_name, organisation, designation, qualification, experience, contact_no, email, certificate, resume, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["trainer_name"]."', '".$input["organisation"]."', '".$input["designation"]."', '".$input["qualification"]."', '".$input["experience"]."', '".$input["contact_no"]."', '".$input["email"]."', '$certificate', '$resume', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingExternalTrainers") {
        $output = Array();
        $sql = "SELECT * FROM externaltrainer WHERE  trainer_type='external' AND status='pending' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["certificate"] = "upload/".$row["certificate"];
                $row["resume"] = "upload/".$row["resume"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateExternalTrainer") {
        $sql = "UPDATE externaltrainer SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getExternalTrainersLog") {
        $output = Array();
        $sql = "SELECT * FROM externaltrainer WHERE trainer_type='external'   AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["certificate"] = "upload/".$row["certificate"];
                $row["resume"] = "upload/".$row["resume"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    
    }
	

}

$conn->close();
?>