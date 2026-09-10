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
    

    if($_GET["type"] == "saveOos") {
    
        $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM oos";
        $i_no1 = 0;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
                break;
            }
        }
        
        $i_no1++;
        $num_length = strlen((string)$i_no1);
        
        if($num_length == 1) {
            $oos_id = "OOS_ID00".$i_no1;
        } else if($num_length == 2) {
           $oos_id = "OOS_ID0".$i_no1;
        } else {
           $oos_id = "OOS_ID".$i_no1; 
        }
        
        $sql = "INSERT INTO oos(oos_id, entry_date, timezone, product_name, batch_no, grn_no, analytical_no, mfg_stage, mfg_date, exp_date, i_no1)
        VALUES ('$oos_id', '$entry_date', '$timezone', '".$_POST["product_name"]."', '".$_POST["batch_no"]."', '".$_POST["grn_no"]."', '".$_POST["analytical_no"]."',  '".$_POST["mfg_stage"]."', '".$_POST["mfg_date"]."', '".$_POST["exp_date"]."',  $i_no1)";
        if ($conn->query($sql) === TRUE) {
            
            $flag = 0;
            $oos = json_decode($_POST["oos"], true);
            $length = sizeof($oos);
                
            for($i = 0; $i < $length; $i++) {
                $data = $oos[$i];
                
                $sql = "INSERT INTO oos_tests(oos_id, oos_test, results, limits) VALUES 
                ('$oos_id', '".$data["oos_test"]."', '".$data["results"]."', '".$data["limits"]."')";
            
                if ($conn->query($sql) === TRUE) {
                    $flag = 0;
                } else {
                    $flag = 1;
                }
            }
            
            $flag = 0;
            $questionDetails = json_decode($_POST["questions"], true);
            $length = sizeof($questionDetails);
                
            for($i = 0; $i < $length; $i++) {
                $data = $questionDetails[$i];
                
                $sql = "INSERT INTO oos_questions(oos_id, ques, ans) VALUES 
                ('$oos_id', '".$data["ques"]."', '".$data["ans"]."')";
            
                if ($conn->query($sql) === TRUE) {
                    $flag = 0;
                } else {
                    $flag = 1;
                }
            }	
            if ($flag == 0) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
           echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    
    else if ($_GET["type"]=="saveoosform") {
        
           if($input['cause_find']=='Yes'){
            $cause='Reanalysis';
        }else{
            $cause='Hypothesis Study';
            
        }
        $sql = "SELECT IFNULL(MAX(id), 0) as  i_no1 FROM newoos where plant_id = '".$_GET["plant_id"]."'";
        $i_no1 = 0;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
                break;
            }
        }
        
        $i_no1++;
        $num_length = strlen((string)$i_no1);
        
        if($num_length == 1) {
            $oos_id = "OOS_ID00".$i_no1;
        } else if($num_length == 2) {
           $oos_id = "OOS_ID0".$i_no1;
        } else {
           $oos_id = "OOS_ID".$i_no1; 
        }
         
        $sql = "INSERT INTO newoos(oos_no, plant_id, batch_no,material_name,material_code,testing_no,specification_no,test_method_no,test,oos_data,
        test_id,description_oos,additional_observation,status,previous_add_observation,just_delay_ivest,cause_find)
        VALUES ('$oos_id', '".$_GET["plant_id"]."','".$input['batch_no']."','".$input['material_name']."','".$input['material_code']."',
        '".$input['testing_no']."','".$input['specification_no']."','".$input['test_method_no']."','".$input['test']."',
        '".json_encode($input['ooschecklist_data'])."','".$input['id']."' ,'".$input['description']."','".$input['additional_observation']."','$cause','".$input['additional_observation']."','".$input['just_delay_ivest']."','".$input['cause_find']."')";
           
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
             
            $sql2 = "UPDATE testing_tests SET oos_status='1' , incident_oos_no = '$oos_id'  WHERE id=".$input["id"];
           $conn->query($sql2);
            
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"]=="completeoos") {
        
        
        $sql = "SELECT IFNULL(MAX(id), 0) as  i_no1 FROM newoos where plant_id = '".$_GET["plant_id"]."'";
        $i_no1 = 0;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
                break;
            }
        }
        
        $i_no1++;
        $num_length = strlen((string)$i_no1);
        
        if($num_length == 1) {
            $oos_id = "OOS_ID00".$i_no1;
        } else if($num_length == 2) {
           $oos_id = "OOS_ID0".$i_no1;
        } else {
           $oos_id = "OOS_ID".$i_no1; 
        }
         
         $sql = "INSERT INTO newoos(oos_no, plant_id, batch_no,material_name,material_code,testing_no,specification_no,test_method_no,test,
         oos_data,test_id,status,description_oos)
        VALUES ('$oos_id', '".$_GET["plant_id"]."','".$input['batch_no']."','".$input['material_name']."','".$input['material_code']."',
        '".$input['testing_no']."','".$input['specification_no']."','".$input['test_method_no']."','".$input['test']."',
        '".json_encode($input['ooschecklist_data'])."' ,'".$input['id']."','checked','".$input['description_oos']."')";
           
         if ($conn->query($sql)===TRUE) {
            echo "{\"status\":\"success\"}";
            
            
     
            $sql2 = "UPDATE testing_tests SET oos_status='1' , incident_oos_no = '$oos_id'  WHERE id=".$input["id"];
           $conn->query($sql2);
                
            
            
            
            
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
        
    } 
    
    
    

    
    else if ($_GET["type"]=="ooschecking") {
        
        
         $sql = "UPDATE newoos SET oos_data= '".json_encode($input['ClosedOOS'])."' , status = 'for_check'  WHERE id=".$_GET["oosid"];
        if ($conn->query($sql)===TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    else if ($_GET["type"]=="oosreview") {
          if($input['cause_find']=='Yes'){
            $cause='Initiate CAPA';
        }else{
            $cause='Hypothesis Study';
            
        }
        
        
          $sql = "UPDATE newoos SET just_delay_ivest= '".$input['just_delay_ivest']."' , status = '$cause'  WHERE id=".$_GET["oosid"];
        //   $sql = "UPDATE newoos SET info_concern_contor= '".$input['info_concern_contor']."' ,cause_of_analysis= '".$input['cause_of_analysis']."' ,Hypothesis_study= '".$input['Hypothesis_study']."' ,repeat_analysis= '".$input['repeat_analysis']."' ,supervisor= '".$input['supervisor']."' ,just_delay_ivest= '".$input['just_delay_ivest']."' , status = '$cause'  WHERE id=".$_GET["oosid"];
        //   $sql = "UPDATE newoos SET supervisor= '".$input['supervisor']."' ,just_delay_ivest= '".$input['just_delay_ivest']."' , status = 'checked'  WHERE id=".$_GET["oosid"];
        //  $sql = "UPDATE newoos SET oos_data= '".json_encode($input['ClosedOOS'])."' , status = 'checked'  WHERE id=".$_GET["oosid"];
        if ($conn->query($sql)===TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
        else if ($_GET["type"]=="closedoos") {
            
            if($input['observation_new']=='complies'){
                $status='CAPA';
            }else if($input['hypothesis_cause_find']=='Yes' && $input['observation_new']=='non-complies'){
                 $status='Proceed For Phase 3';
            }
            else{
                $status='Hypothesis Study';
            }
        
        $analysis_count=$input['analysis_count']+1;
        
         $analysis_count;
        $reanlysis ='reanalysis'.$analysis_count;
    //   echo $reanlysis;
      
         $sql = "UPDATE newoos SET analysis_count='$analysis_count', $reanlysis= '".json_encode($input['reanalysisList'])."' ,Conclusion= '".$input['Conclusion']."' ,corrective_action= '".$input['corrective_action']."',status= '$status'  WHERE id=".$_GET["oosid"];
        if ($conn->query($sql)===TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
        else if ($_GET["type"]=="closedoos1") {
            
            if($input['hypo_cause_find']=='Yes'){
                $status='Reanalysis';
                $oos_sampling='No Action';
            }
            else if($input['hypo_cause_find']=='No' && $input['sampling_error']=='Yes') {
              
                $status='Proceed Sampling';
                $oos_sampling='Pending';
            }
            else if($input['hypo_cause_find']=='No' && $input['sampling_error']=='No') {
              
                $status='Proceed For Phase 3';
                $oos_sampling='No Action';
            }
        
      
    //   echo $reanlysis;
      
        
         $sql = "UPDATE newoos SET hypothesis_cause_find= '".$input['hypo_cause_find']."' ,sampling_error= '".$input['sampling_error']."' ,status= '$status'  WHERE id=".$_GET["oosid"];
        //  $sql = "UPDATE newoos SET analysis_count='$analysis_count', $reanlysis= '".json_encode($input['reanalysisList'])."' ,Conclusion= '".$input['Conclusion']."' ,corrective_action= '".$input['corrective_action']."',hypothesis_cause_find= '".$input['hypo_cause_find']."' ,status= '$status'  WHERE id=".$_GET["oosid"];
        if ($conn->query($sql)===TRUE) {
            
            $sql1="update sampling set oos_sampling='$oos_sampling' where material_code='".$input['material_code']."' and batch_no='".$input['batch_no']."'";
          $conn->query($sql1);
          echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    else if ($_GET["type"]=="updateOos") {
        $sql = "UPDATE oos SET status='".$_GET["status"]."' WHERE id=".$_GET["id"];
        if ($conn->query($sql)===TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    
    else if($_GET["type"] == "getoosforreview") {
            
            $sql = "SELECT * from oos WHERE status = 'pending' ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $sql1 = "select oos_id, oos_test, results, limits from oos_tests where oos_id = '".$row['oos_id']."'";
                    $result1 = $conn->query($sql1);
                    $data = array();
                    if($result1->num_rows > 0){
                        while($row1 = $result1->fetch_assoc()){
                            $data[] = $row1;
                        }
                    }
                    
                    $row["Oos"] = $data;
                    
                    $sql1 = "select ques, ans, oos_id from oos_questions where oos_id = '".$row['oos_id']."'";
                    $result1 = $conn->query($sql1);
                    $data = array();
                    
                    if($result1->num_rows > 0) {
                        while($row1 = $result1-> fetch_assoc()) {
                            $data[] = $row1;
                        }
                    }
                    $row["questionDetails"] = $data;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     }
     
    else if($_GET["type"] == "getoosforreview1") {
            
            $sql = "SELECT * from newoos WHERE status = 'Hypothesis Study' AND plant_id='".$_GET["plant_id"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $jsonString = $row["oos_data"];
                    
                    // Remove control characters from the JSON string
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    
                    
                    $oos_data_array = json_decode($jsonString, true);
                    
                    // Check if JSON decoding was successful
                    if ($oos_data_array === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Failed to decode JSON string: " . json_last_error_msg());
                    } elseif ($oos_data_array === null) {
                        throw new Exception("Failed to decode JSON string for an unknown reason.");
                    }
                    
                    
                    $row["oos_data"] = $oos_data_array;
                     
                    $sql2="select a.id,a.observation,a.remark,a.result,a.end_time,a.end_time,b.limit_type,b.limits from testing_tests a left join spec_tests b on a.spec_test_id=b.id where a.incident_oos_no='".$row["oos_no"]."' order by a.id desc limit 1";
                     $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                     $row['reanlysis_data']=$output1;
                     $row["reanalysis1"] = json_decode($row["reanalysis1"]); 
                     $row["reanalysis2"] = json_decode($row["reanalysis2"]); 
                     $row["reanalysis3"] = json_decode($row["reanalysis3"]); 
                     $row["reanalysis4"] = json_decode($row["reanalysis4"]); 
                     $row["reanalysis5"] = json_decode($row["reanalysis5"]); 
                     $row["reanalysis6"] = json_decode($row["reanalysis6"]); 
                     $row["reanalysis7"] = json_decode($row["reanalysis7"]); 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     }
    else if($_GET["type"] == "getoosforreview11") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
            
            $sql = "SELECT * from newoos WHERE (status = 'Reanalysis') AND plant_id='".$_GET["plant_id"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $jsonString = $row["oos_data"];
                    
                    // Remove control characters from the JSON string
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    
                    
                    $oos_data_array = json_decode($jsonString, true);
                    
                    // Check if JSON decoding was successful
                    if ($oos_data_array === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Failed to decode JSON string: " . json_last_error_msg());
                    } elseif ($oos_data_array === null) {
                        throw new Exception("Failed to decode JSON string for an unknown reason.");
                    }
                    
                    
                    $row["oos_data"] = $oos_data_array;
                    
                    $sql2="select a.id,a.observation,a.remark,a.result,a.end_time,a.end_time,b.limit_type,b.limits from testing_tests a left join spec_tests b on a.spec_test_id=b.id where a.incident_oos_no='".$row["oos_no"]."' order by a.id desc limit 1";
                     $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                    $row['reanlysis_data']=$output1;
                    $row["reanalysis1"] = json_decode($row["reanalysis1"]); 
                     $row["reanalysis2"] = json_decode($row["reanalysis2"]); 
                     $row["reanalysis3"] = json_decode($row["reanalysis3"]); 
                     $row["reanalysis4"] = json_decode($row["reanalysis4"]); 
                     $row["reanalysis5"] = json_decode($row["reanalysis5"]); 
                     $row["reanalysis6"] = json_decode($row["reanalysis6"]); 
                     $row["reanalysis7"] = json_decode($row["reanalysis7"]); 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     }
    else if($_GET["type"] == "getooslog") {
            
            $sql = "SELECT * from newoos WHERE status = 'closed' AND plant_id='".$_GET["plant_id"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $jsonString = $row["oos_data"];
                    
                    // Remove control characters from the JSON string
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    
                    
                    $oos_data_array = json_decode($jsonString, true);
                    
                    // Check if JSON decoding was successful
                    if ($oos_data_array === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Failed to decode JSON string: " . json_last_error_msg());
                    } elseif ($oos_data_array === null) {
                        throw new Exception("Failed to decode JSON string for an unknown reason.");
                    }
                    
                    
                    $row["oos_data"] = $oos_data_array;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     }
    else if($_GET["type"] == "getinviestigatingoos") {
            
            $sql = "SELECT * from newoos WHERE status = 'pending' AND plant_id='".$_GET["plant_id"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $jsonString = $row["oos_data"];
                    
                    // Remove control characters from the JSON string
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    
                    
                    $oos_data_array = json_decode($jsonString, true);
                    
                    // Check if JSON decoding was successful
                    if ($oos_data_array === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Failed to decode JSON string: " . json_last_error_msg());
                    } elseif ($oos_data_array === null) {
                        throw new Exception("Failed to decode JSON string for an unknown reason.");
                    }
                    
                    
                    $row["oos_data"] = $oos_data_array;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     }
    else if($_GET["type"] == "getoos_forchecking") {
            
            $sql = "SELECT * from newoos WHERE status = 'for_check' AND plant_id='".$_GET["plant_id"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $jsonString = $row["oos_data"];
                    
                    // Remove control characters from the JSON string
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    
                    
                    $oos_data_array = json_decode($jsonString, true);
                    
                    // Check if JSON decoding was successful
                    if ($oos_data_array === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Failed to decode JSON string: " . json_last_error_msg());
                    } elseif ($oos_data_array === null) {
                        throw new Exception("Failed to decode JSON string for an unknown reason.");
                    }
                    
                    
                    $row["oos_data"] = $oos_data_array;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     }
 
     
     else if($_GET["type"] == "getCheckedOosData") {
            
             $sql = "SELECT * FROM oos";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $sql1 = "select oos_id, oos_test, results, limits from oos_tests where oos_id = '".$row['oos_id']."'";
                    $result1 = $conn->query($sql1);
                    $data = array();
                    if($result1->num_rows > 0){
                        while($row1 = $result1->fetch_assoc()){
                            $data[] = $row1;
                        }
                    }
                    
                    $row["Oos"] = $data;
                    
                    $sql1 = "select ques, ans, oos_id from oos_questions where oos_id = '".$row['oos_id']."'";
                    $result1 = $conn->query($sql1);
                    $data = array();
                    
                    if($result1->num_rows > 0) {
                        while($row1 = $result1-> fetch_assoc()) {
                            $data[] = $row1;
                        }
                    }
                    $row["questionDetails"] = $data;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
     } else if($_GET["type"] == "getOosData") {
            
            $sql = "SELECT * from oos ORDER BY id DESC";
            $result = $conn->query($sql);
            $output = array(); 
            
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $sql1 = "select oos_id, oos_test, results, limits from oos_tests where oos_id = '".$row['oos_id']."'";
                    $result1 = $conn->query($sql1);
                    $data = array();
                    if($result1->num_rows > 0){
                        while($row1 = $result1->fetch_assoc()){
                            $data[] = $row1;
                        }
                    }
                    
                    $row["Oos"] = $data;
                    
                    $sql1 = "select ques, ans, oos_id from oos_questions where oos_id = '".$row['oos_id']."'";
                    $result1 = $conn->query($sql1);
                    $data = array();
                    
                    if($result1->num_rows > 0) {
                        while($row1 = $result1-> fetch_assoc()) {
                            $data[] = $row1;
                        }
                    }
                    $row["questionDetails"] = $data;
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>