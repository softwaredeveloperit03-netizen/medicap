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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    

      if($_GET["type"]=="getDesignations") {
        $output = Array();
	 $sql = "SELECT * FROM designation a LEFT JOIN department b on a.dept_id=b.id order by a.id desc";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["responsibilities"] = json_decode($row["responsibilities"]);
    			$output[] = $row;
    		}
    	}
	    echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveDesignation") {
        $sql = "INSERT INTO designation (plant_id,dept_id,designation_heading,designation, responsibilities, entry_by, entry_date, status)
        VALUES ('".$_GET["plant_id"]."','".$input["dept_id"]."','".$input["designation_heading"]."','".$input["designation"]."','".json_encode($input["responsibilities"])."','".$_GET["emp_id"]."','".$entry_date."','active' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     else if ($_GET["type"] == "saveResponsibilities") {
        // $sql = "INSERT INTO designation (plant_id,dept_id,designation_heading,designation, responsibilities, entry_by, entry_date)
        // VALUES ('".$_GET["plant_id"]."','".$input["dept_id"]."','".$input["designation_heading"]."','".$input["designation"]."','".json_encode($input["responsibilities"])."','".$_GET["emp_id"]."','".$entry_date."' )";
        
           $sql = "UPDATE `designation`  SET responsibilities = '".json_encode($input["responsibilities"])." where id = '".$input['id']."' "; 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveDesignationMeha") {
        
           $json_obj = json_encode($input["list"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $input)
                {
  $sql = "INSERT INTO designation (plant_id,dept_id,designation_heading,designation, responsibilities, entry_by, entry_date,grade)
        VALUES ('".$_GET["plant_id"]."','".$input["dept_id"]."','".$input["designation_heading"]."','".$input["designation"]."','".json_encode($input["responsibilities"])."','".$_GET["emp_id"]."','".$entry_date."','".$input["emp_type"]."' )";
   
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
 
    else if($_GET["type"]=="getPendingDesignations") {
        $output = Array();
    	$sql = "SELECT * FROM designation ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["responsibilities"] = json_decode($row["responsibilities"]);
    			$output[] = $row;
    		}
    	}
	    echo json_encode($output);
    } 
   


}

$conn->close();
?>