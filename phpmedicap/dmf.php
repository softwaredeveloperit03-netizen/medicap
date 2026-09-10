<?php 
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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

    
    
    if ($_GET["type"] == "SaveDocument") {
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
        /******* Get CH-ID for  enter in new record  */
        $ch="Doc-";
        $sql = "Select id  from master_index where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $ch_id = $ch."0". ++$row['id']; 
      
        
        
        $sql = "INSERT INTO master_index (plant_id,dossier_dmf,country_name,ch_id) 
                                             VALUES ('".$_GET["plant_id"]."','".$input["dossier_dmf"]."','".$input["country_name"]."','".$ch_id."')";
        
         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["documentData"] as $checkDtlData){
    		
    	$sql="INSERT INTO mst_documentData (chklist_id ,particular ,index,document,upload,multi_upload)
                                value(".$last_id.",
                                      '".$checkDtlData["particular"]."',
                                      '".$checkDtlData["index"]."',
                                      '".$checkDtlData["document"]."',
                                      '".$checkDtlData["upload"]."',
                                      '".$checkDtlData["multi_upload"]."')";
    	 $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }

        else if ($_GET["type"] == "delCheck") {
            
            
         
         $sql= " Update mst_documentData set status = 'inactive' where id='".$_GET["id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
            
        }
        
    else if ($_GET["type"] == "getMastercheckList") { 

       
        $output = array();
        $sql = "SELECT * FROM master_index";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { $row["checkList"]=[];
                $sqlChkDtl = "select * from mst_documentData where chklist_id=".$row["id"];
                $resultChkDtl = $conn->query($sqlChkDtl);
                while ($rowChkDtl = $resultChkDtl->fetch_assoc()) {  $row["checkList"][] = $rowChkDtl;}
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }


    else if ($_GET["type"] == "getCheckPointByForm") { 
  
        $output = array();
        $sql = "SELECT cd.*,mc.module,mc.form_name,mc.department FROM mst_chlist_dtl cd 
                left join master_checklist mc on cd.chklist_id = mc.id 
                where mc.module='".$_GET["module"]."' and form_name='".$_GET["form"]."'";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getChkListByTranID") { 

      
        $output = array();
        $sql = "SELECT * from checklist_transaction where trans_id ='".$_GET["tranId"]."'";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_rec_ChkListByTranID") { 


        $output = array();
        $sql = "SELECT * from checklist_transaction where trans_id ='".$_GET["rec_no"]."' and correction=''";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_wgh_ChkListByTranID") { 


        $output = array();
        $sql = "SELECT * from checklist_transaction where trans_id ='".$_GET["tranId"]."'";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveChecklist") {
        
        
      
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
       
        /******* Get CH-ID for  enter in new record  */
        $ch="CH-";
        $sql = "Select id  from appraisal_checklist_master where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $ch_id = $ch."0". ++$row['id']; 
      
        
        
        $sql = "INSERT INTO appraisal_checklist_master (plant_id,appraisal_type,department,designation,checklist_heading,ch_id) 
                                             VALUES ('".$_GET["plant_id"]."','".$input["appraisal_type"]."','".$input["department"]."','".$input["designation"]."','".$input["checklist_heading"]."','".$ch_id."')";
        
         if($conn->query($sql)){
    	
        $last_id = $conn->insert_id;

        foreach($input["checklistList"] as $checkDtlData) {
    		
    	$sql="INSERT INTO appraisal_checklist_details (chklist_id ,checklist_particulars ,description,evualation_parameter,evualation_type,goal_type)
                                value(".$last_id.",
                                      '".$checkDtlData["checklist_particulars"]."',
                                      '".$checkDtlData["description"]."',
                                      '".$checkDtlData["evualation_parameter"]."',
                                      '".$checkDtlData["evualation_type"]."',
                                      '".$checkDtlData["goal_type"]."')";
    	 $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    
    }
}
    else {
        echo "{\"status\":\"invalid\"}";
    }
    $conn->close();
    ?>