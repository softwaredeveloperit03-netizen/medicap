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


     if ($_GET["type"] == "saveTarget") {
  $sql = "INSERT INTO targetform ( plant_id,target_type,product_code,target,from_date,to_date) VALUES ( '".$_GET["plant_id"]."','".$input["target_type"]."', 
        '".$input["product_code"]."', '".$input["target"]."', '".$input["from_date"]."', '".$input["to_date"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }





else if ($_GET["type"] == "getPendingDoctors") {
    $output = array();
    //  $sql = "SELECT * FROM emtryfrom ";
		$sql = "  SELECT * FROM emtryfrom where status = 'pending' ORDER BY `id` DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc())  {
             $output[] = $row;
        }
    }
    echo json_encode($output);
}   
else if($_GET["type"]=="updateDoctor") {
  	$sql = "UPDATE emtryfrom SET status= '".$_GET["status"]."' WHERE id ='".$_GET["id"]."'";

	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	} else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if ($_GET["type"] == "getTargetsLog") {
    $output = array();
         $sql = "SELECT * FROM targetform ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc())  {
             $output[] = $row;
        }
    }
    echo json_encode($output);
}   
 

}

$conn->close();
?>