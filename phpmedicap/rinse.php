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
    
    if ($_GET["type"] == "getEnvironment") {
        $output = Array();
        $sql = "SELECT * FROM Environment";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEnvironment") {
        $sql = "INSERT INTO Environment (plant_id,sampling_date,medium_used, media_no, incubation_temp, incubation_id, incubation_start, incubation_end) 
        VALUES ('".$_GET['plant_id']."','".$input["sampling_date"]."', '".$input["medium_used"]."', '".$input["media_no"]."', '".$input["incubation_temp"]."', '".$input["incubation_id"]."', '".$input["incubation_start"]."', '".$input["incubation_end"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     else if ($_GET["type"] == "saveRinse") {
            $sql = "INSERT INTO rinse_analysis (plant_id,equip_name,batch_code,DOA,DOR) 
              VALUES ('".$_GET["plant_id"]."','".$input["equip_name"]."','".$input["batch_code"]."','".$input["DOA"]."','".$input["DOR"]."')";
           if($conn->query($sql)){
        	
            $last_id = $conn->insert_id;
    
            foreach($input["analysislist"] as $Data){
        		
        	$sql="INSERT INTO rinse_analysis_dtl (micro_organism ,observation_control ,incubation_period)
                                    value('".$Data["micro_organism"]."',
                                          '".$Data["observation_control"]."',
                                          '".$Data["incubation_period"]."')";
        	 $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getRinse") { 

       
        $output = array();
        $sql = "SELECT * FROM rinse_analysis";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { $row["analysis"]=[];
                $sqlChkDtl = "select * from rinse_analysis_dtl where chklist_id=".$row["id"];
                $resultChkDtl = $conn->query($sqlChkDtl);
                while ($rowChkDtl = $resultChkDtl->fetch_assoc()) {  $row["analysis"][] = $rowChkDtl;}
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    // else if ($_GET["type"] == "saveRinse") {
      
    //     $json = file_get_contents('php://input');
    //     $input = json_decode($json,true);
       
    //     /******* Get CH-ID for  enter in new record  */
    //     $ch="CH-";
    //     $sql = "Select id  from rinse_analysis where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
    //     $result = $conn->query($sql);
    //     $row = $result->fetch_assoc();
    //     $ch_id = $ch."0". ++$row['id']; 
      
        
        
    //     $sql = "INSERT INTO rinse_analysis (plant_id,equip_name,batch_code,DOA,DOR,ch_id) 
    //                                          VALUES ('".$_GET["plant_id"]."','".$input["equip_name"]."','".$input["batch_code"]."','".$input["DOA"]."',
    //                                                   '".$input["DOR"]."','".$ch_id."')";
        
    //      if($conn->query($sql)){
    	
    //     $last_id = $conn->insert_id;

    //     foreach($input["analysislist"] as $checkDtlData){
    		
    // 	$sql="INSERT INTO rinse_analysis_dtl (chklist_id ,micro_organism ,observation_control ,incubation_period)
    //                             value(".$last_id.",
    //                                   '".$checkDtlData["micro_organism"]."',
    //                                   '".$checkDtlData["observation_control"]."',
    //                                   '".$checkDtlData["incubation_period"]."')";
    // 	 $conn->query($sql);
    //     }
    
    
    //      echo "{\"status\":\"success\"}";	
    // 	} else {
    // 		echo "{\"status\":\"".$conn->error."\"}";
    // 	}
    // } 



}
$conn->close();
?>