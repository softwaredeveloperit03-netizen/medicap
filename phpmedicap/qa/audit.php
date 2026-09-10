<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
require '../db.php';
require '../token.php';

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

    if ($_GET["type"] == "save_audit_checklist") {
    $sql = "INSERT INTO audit_checklist(plant_id, entry_by, entry_date, department, check_point, evaluation_parameter) VALUES ('".$_GET["plant_id"]."',
                '".$_GET["emp_id"]."','$entry_date','".$input["department"]."','".$input["check_point"]."','".$input["evaluation_parameter"]."')";
        
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
        
    } 
    else if ($_GET["type"] == "Save_Qualification_Questionnaire") {
        
            $jadi = true; // diasumsikan semua berhasil
            $checklistList = $input["checklistList"];
            $entry_date = date('Y-m-d H:i:s');
            
            // Gunakan prepared statement
            $stmt = $conn->prepare("
                INSERT INTO audit_checklist
                (plant_id, check_heading, perticularType, check_point, evaluation_parameter, entry_by, entry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                echo json_encode(["status" => "error", "message" => $conn->error]);
                exit;
            }
            
            foreach ($checklistList as $value) {
                $plant_id = $_GET["plant_id"];
                $check_heading = $value["check_heading"];
                $perticularType = $value["perticularType"];
                $check_point = $value["check_point"];
                $evaluation_parameter = $value["evaluation_parameter"];
                $entry_by = $_GET["emp_id"];
            
                if (!$stmt->bind_param("issssss", $plant_id, $check_heading, $perticularType, $check_point, $evaluation_parameter, $entry_by, $entry_date)) {
                    $jadi = false;
                    break;
                }
            
                if (!$stmt->execute()) {
                    $jadi = false;
                    break;
                }
            }
            
            $stmt->close();
            
            if ($jadi) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => $conn->error]);
            }
                    
    } 
    else if ($_GET["type"] == "getChecklistByType") {
            
        $output = Array();
         $sql = "SELECT  * FROM audit_checklist where plant_id = '".$_GET["plant_id"]."' AND check_heading = '".$_GET["check_heading"]."' "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
     			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	 
    }
    else if ($_GET["type"] == "getChecklistByLog") {
            
        $output = Array();
        $sql = "SELECT  check_heading FROM audit_checklist where plant_id = '".$_GET["plant_id"]."' group by check_heading"; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
    		    $output1 = Array();
                $sql1 = "SELECT  * FROM audit_checklist where check_heading = '".$row["check_heading"]."' order by id asc "; 
             	$result1 = $conn->query($sql1);
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
             			$output1[] = $row1;
            		}
            	}
            	
            	$row['checklist'] = $output1;
     			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	 
    }
    
    else if ($_GET["type"] == "get_audits") {
            
        $output = Array();
         $sql = "SELECT a.*, e.firstname , e.lastname , v.vendor_name ,v.address FROM audit a left join vendor v On a.vendor_no = v.vendor_no left join employee e on a.audit_by = e.emp_id order by id desc "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row['auditors_team'] = json_decode($row['auditors_team']);
    		    $row['audit_sche'] = json_decode($row['audit_sche']);
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    
            
    }else if ($_GET["type"] == "get_audits_response") {
            
        $output = Array();
         $sql = "SELECT a.*, e.firstname , e.lastname , v.vendor_name ,v.address FROM audit a left join vendor v On a.vendor_no = v.vendor_no 
         left join employee e on a.audit_by = e.emp_id where a.vendor_status != 'pending' order by id desc "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row['auditors_team'] = json_decode($row['auditors_team']);
    		    $row['audit_sche'] = json_decode($row['audit_sche']);
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    
            
    }else if ($_GET["type"] == "updateauditFile") {
            $input = $_POST;
        
        $target_dir = "../../../upload/qa/";

        $id = date("YmdHis", $timestamp);
      $file_name = "";
    	if(isset($_FILES["document"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document']['name'])));
        	$file_name = 'Vendor-Manage'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document"]["tmp_name"], $target_dir.$file_name);
    	}
    	
    	
    	$sql = "INSERT INTO audit_docs (audit_id,doc_name,doc) values('".$input['audit_id']."','".$input['document_name']."','$file_name')";
             if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
        }
        else if ($_GET["type"] == "get_audits_by_vendor") {
            
        $output = Array();
         $sql = "SELECT a.*, e.firstname , e.lastname , v.vendor_name ,v.address FROM audit a left join vendor v On a.vendor_no = v.vendor_no left join 
         employee e on a.audit_by = e.emp_id where a.plant_id = '".$_GET["plant_id"]."' AND a.vendor_no = '".$_GET["vendor_no"]."' order by id desc "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row['auditors_team'] = json_decode($row['auditors_team']);
    		    $row['audit_sche'] = json_decode($row['audit_sche']);
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    
            
        }
        else if ($_GET["type"] == "save_Agenda") {
     
     
    	    $sql = "INSERT INTO audit(plant_id, audit_date, audit_for, vendor_no, audit_by, auditors_team, audit_sche,total_day) VALUES ('".$_GET["plant_id"]."',
                '".$input["audit_date"]."','".$input["audit_for"]."','".$input["vendor_no"]."','".$input["audit_by"]."'
                ,'".json_encode($input["auditors_team"])."','".json_encode($input["audit_sche"])."','".$input["total_day"]."')";
        
            	if($conn->query($sql)){
            		echo "{\"status\":\"success\"}";
            	} else {
            		echo "{\"status\":\"".$conn->error."\"}";
            	}
        
    
            
        }
        else if ($_GET["type"] == "saveAssementChecklist") {
     
     
    	    $sql = "INSERT INTO assesmentChecklist(plant_id, vendor_no, checklist,status, entryBy, entryOn) VALUES ('".$_GET["plant_id"]."',
                '".$input["vendor_no"]."','".$input["checklist"]."','Pending', '".$_GET["emp_id"]."','$entry_date')";
        
            	if($conn->query($sql)){
            		echo "{\"status\":\"success\"}";
            	} else {
            		echo "{\"status\":\"".$conn->error."\"}";
            	}
        
    
            
        }
        else if ($_GET["type"] == "addDocToVendor") {
             
        $json = file_get_contents('php://input');
        $input = json_decode($json,true);
     
        $jadu = 0;

                foreach($input["dacData"] as $checkDtlData){
             
             
            	    $sql = "INSERT INTO docToVendor(plant_id, vendor_no, docName,status, entryBy, entryOn) VALUES ('".$_GET["plant_id"]."',
                        '".$input["vendor_no"]."','".$checkDtlData["document_name"]."','Pending', '".$_GET["emp_id"]."','$entry_date')";
                
                    	if($conn->query($sql)){
                    	$jadu = 0;
                    	} else {
                    		$jadu = 1;
                    	} 
                    	
                }
        
            	if($jadu == 0){
            		echo "{\"status\":\"success\"}";
            	} else {
            		echo "{\"status\":\"".$conn->error."\"}";
            	} 
         
            
        }
        else if ($_GET["type"] == "UpdateAudit") {
            
          
                 $sql = "UPDATE audit SET  vendor_status  = '".$_GET["status"]."'  WHERE  id = '".$_GET["id"]."'";
            
      
            	if($conn->query($sql)){
            		echo "{\"status\":\"success\"}";
            	} else {
            		echo "{\"status\":\"".$conn->error."\"}";
            	}
         
        }
        else if ($_GET["type"] == "update_audit_STATUS") {
            
            if($_GET["status"] == 'change'){
                 $sql = "UPDATE audit SET changed_date = '".$_GET["change_date"]."', vendor_status  ='Accept'  WHERE  id = '".$_GET["id"]."'";
            }else{
                $sql = "UPDATE audit SET  vendor_status  = '".$_GET["status"]."'  WHERE  id = '".$_GET["id"]."'";
            }
      
            	if($conn->query($sql)){
            		echo "{\"status\":\"success\"}";
            	} else {
            		echo "{\"status\":\"".$conn->error."\"}";
            	}
         
        }
        
        else if ($_GET["type"] == "getreviewby_company") {
            
            $output = Array();
           $sql = "SELECT  a.*, v.vendor_name FROM send_checklist_dtl a left join vendor v On a.vendor_no = v.vendor_no 
           where a.vendor_status = 'pending' AND a.plant_id = '".$_GET["plant_id"]."' AND a.vendor_no = '".$_GET["vendor_no"]."' 
           order by a.id desc "; 
         	
         	$result = $conn->query($sql);
        	if($result->num_rows > 0){
        		while($row = $result->fetch_assoc()){
         		    $row['documents_list'] = json_decode($row['documents_list']);
         		    $row['checklist_data'] = json_decode($row['checklist_data']);
        			$output[] = $row;
        		}
        	}
        	
        	echo json_encode($output);
            
        }
        else if ($_GET["type"] == "getPendingAssesment") {
            
            $output = Array();
           $sql = "SELECT  a.*,c.checklistpoints as checklistHeading , v.vendor_name FROM assesmentChecklist a left join checkpoint_list c On a.checklist = c.id 
            left join vendor v On a.vendor_no = v.vendor_no  where a.status = 'Pending' AND a.plant_id = '".$_GET["plant_id"]."' AND a.vendor_no = '".$_GET["vendor_no"]."' 
           order by a.id desc "; 
         	
         	$result = $conn->query($sql);
        	if($result->num_rows > 0){
        		while($row = $result->fetch_assoc()){
        		    
        		    $output1 = array();
                    $sql1 = "SELECT * FROM checkpoint_master where chklist_id ='".$row["checklist"]."' "; 
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    
                    $row['check_points'] = $output1;
         		    
        			$output[] = $row;
        		}
        	}
        	
        	echo json_encode($output);
            
        }
        else if ($_GET["type"] == "getVendors") {
            
            $output = Array();
           $sql = "SELECT id,vendor_no,vendor_name FROM vendor where plant_id = '".$_GET["plant_id"]."'  order by id desc "; 
         	
         	$result = $conn->query($sql);
        	if($result->num_rows > 0){
        		while($row = $result->fetch_assoc()){
         	 
        			$output[] = $row;
        		}
        	}
        	
        	echo json_encode($output);
            
        }

 

    

}

$conn->close();

?>