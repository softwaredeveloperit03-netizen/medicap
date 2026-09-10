<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

  require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
 
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
    

      
   if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
 }
 
 else if($_GET["type"] == "getBacterialIncubators"){
         $output=Array();
        $sql="SELECT * FROM bactorial";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
      
 }else if($_GET["type"] == "getColonyCounters"){
         $output=Array();
        $sql="SELECT * FROM colony_calibration"; 
        //WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
       
        
        
 }else if($_GET["type"] == "getAutoclaves"){
        $output=Array();
          $sql="SELECT * FROM equipment where equipment_type LIKE '%Autoclave%' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
 }else if($_GET["type"] == "getBODIncubators"){
        $output=Array();
        $sql="SELECT * FROM incubator_bod_usage ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    } else if($_GET['type'] == 'getDepartmentEquiments'){
        $output = Array();
        $sql = "SELECT * FROM equipments WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else if ($_GET["type"] == "getEquipments1") {
        $output = Array();
        $sql = "SELECT * FROM equipment";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEquipment") {
        $sql = "INSERT INTO equipments1 (department, equipment_code, equipment_name, equipment_sr_no, capacity,unit, location, description, make) VALUES ('".$input["department"]."', '".$input["equipment_code"]."', '".$input["equipment_name"]."', '".$input["equipment_sr_no"]."', '".$input["capacity"]."','".$input["unit"]."', '".$input["location"]."', '".$input["description"]."', '".$input["make"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
            
        }
        
    } else if ($_GET["type"] == "saveBODIncubators") {
        $sql = "INSERT INTO incubator_bod_usage (equipment_code, activity, batch_no, date_on,time_on, remark, ) 
        VALUES ('".$input["equipment_code"]."', '".$input["activity"]."', '".$input["batch_no"]."',
        '".$input["date_on"]."', '".$input["time_on"]."','".$input["remark"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
            
        }
    } else if ($_GET["type"] == "saveAutoclaves") {
        $sql = "INSERT INTO autoclave (cycle_no,material,cycle_start ,achieved_time , steam_pressure,cycle_end,
        hold_time,indicator,remark,done_by,entry_by,entry_date) VALUES
        ('".$input["cycle_no"]."', '".$input["media_name"]."', '".$input["cycle_start"]."', '".$input["achieved_time"]."' ,
        '".$input["steam_pressure"]."', '".$input["cycle_end"]."', '".$input["hold_time"]."', '".$input["indicator"]."',
        '".$input["remark"]."' , '".$input["done_by"]."',  '".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
           
          echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    } else if ($_GET["type"] == "saveMicroscopes") {
        $sql = "INSERT INTO culture_identification (organism_name, atcc_no, feature, feature_gram, 
        microscope,done_by) VALUES ('".$input["organism_name"]."', '".$input["atcc_no"]."', 
        '".$input["feature"]."', '".$input["feature_gram"]."', '".$input["microscope"]."','".$input["done_by"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }   
    }else if ($_GET["type"] == "getQCMicroEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipments1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else if ($_GET["type"] == "getMicroscopes") {
        $output = Array();
        $sql = "SELECT * FROM culture_identification";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    } else if ($_GET["type"] == "getDepartmentEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE status='approve' AND department='".$_GET["department_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSectionEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipments WHERE status='approve' AND department='".$_GET["department"]."' AND section='".$_GET["section"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUnderMaintenanceEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipments WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM maintainance_note WHERE equipment_code='".$row["equipment_code"]."' AND status IN ('pending', 'inprocess')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["breakdown_time"] = $row1["breakdown_time"];
                        $output[] = $row;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLAFEquipments") {
        $output = Array();
        $sql1 = "SELECT * FROM equipment WHERE department='Quality Control'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $sql2 = "SELECT id FROM equipment_usages WHERE equipment_code='".$row1["equipment_code"]."' ORDER BY id DESC LIMIT 1 ";
               
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $sql3 = "SELECT * FROM equipment e  WHERE e.department='Quality Control' AND (e.equipment_name LIKE '%RLAF%' or e.equipment_type = 'LAF') 
                               ";
                                                       
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                           
                                $row1["clean_by"] = $row3["clean_by"];
                                $row1["clean_check_by"] = $row3["approve_by"];
                                $row1["clean_date"] = $row3["entry_date"];
                                $row1["clean"] = "yes";
                            }
                        } else {
                            $row1["clean_by"] = "";
                            $row1["clean_date"] = "";
                            $row1["clean_check_by"] = "";
                            $row1["clean"] = "no";
                        }
                    }
                } else {
                    $sql3 = "SELECT * FROM equipment_cleaning WHERE equipment_code='".$row1["equipment_code"]."' AND status='approve' ORDER BY id DESC LIMIT 1";
                    $result3 = $conn->query($sql3);
                    if ($result3->num_rows > 0) {
                        while ($row3 = $result3->fetch_assoc()) {
                            $row1["clean_by"] = $row3["clean_by"];
                            $row1["clean_check_by"] = $row3["approve_by"];
                            $row1["clean_date"] = $row3["entry_date"];
                            $row1["clean"] = "yes";
                        }
                    } else {
                        $row1["clean_by"] = "";
                        $row1["clean_date"] = "";
                        $row1["clean_check_by"] = "";
                        $row1["clean"] = "no";
                    }
                }

                $q = "select * from activity where equipment_code='".$row1["equipment_code"]."'    order by id desc limit 1";
                $r = $conn->query($q);
                $activityData = $r->fetch_assoc() ;
              
                if(!isset($activityData["end_date"]) || $activityData["end_date"] !="")
                {

                    $row1["activity_start_date"]="";
                }
                else{
                    $row1["activity_start_date"]= $activityData["start_date"];;
                }
                $output[] = $row1;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveGeneralEquipmentUsages") {
        
        $sql = "INSERT INTO equipment_usages (equipment_code, operator, activity, usage_from, usage_to, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', '".$input["operator"]."', '".$input["activity"]."', '".$input["usage_from"]."', '".$input["usage_to"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingGeneralEquipmentUsages") {
        $output = Array();
        $sql = "SELECT e.*, e1.equipment_name FROM equipment_usages e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE e.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateEquipmentUsage") {
        $sql = "UPDATE equipment_usages SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getEquipmentUsagesLog") {
        $output = Array();
         $sql = "SELECT e.*, e1.equipment_name FROM equipment_usages e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE DATE(e.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveGeneralEquipmentCleaning") {
           
        $sql = "INSERT INTO `equipment_cleaning`( `equipment_code`, `equipment_name`, `room_name`, `room_code`, `form_no`, `title`, `entry_by`, `entry_date`, `plant_id`,`activity_type`) VALUES ('".$input['equipment_code']."','".$input['equipment_name']."','".$input['room_name']."','".$input['room_code']."','".$input['form_no']."','".$input['title']."','".$_GET['emp_id']."','$entry_date','".$_GET['plant_id']."','".$input['activity_type']."')";
       
        if ($conn->query($sql)) {
            // $sql = "update bmr_Equipment_data set 	clean_from='".$input["clean_from"]."',clean_to='".$input["clean_to"]."',clean_by='".$input["clean_by"]."',done_by='".$_GET["emp_id"]."',dony_by_date='$entry_date',cleaning_type='".$input["clean_from"]."' where equipment_code='".$input["equipment_code"]."'";
            //  $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingGeneralEquipmentCleaning") {
        $output = Array();
        $sql = "SELECT e.*, e1.equipment_name FROM equipment_cleaning e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE e.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateEquipmentCleaning") {
        $sql = "UPDATE equipment_cleaning SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date',sequence='".json_encode($input["sequence"])."'
                ,PrepareMaster='".json_encode($input["PrepareMasterList"])."',RoomLogin='".json_encode($input["RoomLoginList"])."',RoomLogbook='".json_encode($input["addRoomLogbookList"])."'
                ,RoomLogout ='".json_encode($input["RoomLogoutList"])."',EquipmentCleaning='".json_encode($input["EquipmentCleaningList"])."',majorClean ='".json_encode($input["MajorCleaningheadList"])."'
                    WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            // $sql="update bmr_Equipment_data set  checked_by='".$_GET["emp_id"]."',checked_date='$entry_date' where equipment_code='".$_GET["equipment_code"]."' ";
            // $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getEquipmentCleaningLog") {
        $output = Array();
       $sql = " SELECT e.*, e1.equipment_name,e1.capacity FROM equipment_cleaning e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE DATE(e.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_logbook_data_by_type") {
        $output = Array();
       $sql = " select * from equipment_cleaning where activity_type='".$_GET["activity"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getEquipmentCleaningLog_today") {
        $output = Array();
       $sql = " SELECT e.*, e1.equipment_name,e1.capacity FROM equipment_cleaning e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE  e.entry_date like '%".$_GET["to_date"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_prodEquipments_data") {
        
        
	$output = Array();

     	  $sql = "select * from equipment";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    
        
    }
    else if ($_GET["type"] == "getLabours") {
        
        
	$output = Array();

     	  $sql = "SELECT * FROM employee WHERE department='".$_GET["department1"]."' and operator_category='".$_GET["operator_category"]."'";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

        
    
        
    }
    else if ($_GET["type"] == "getEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment_names WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                  $sql1 = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND equipment_name='".$row["equipment_name"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT id FROM equipment_usages WHERE user_no='".$_GET["user_no"]."' AND equipment_code='".$row1["equipment_code"]."' ORDER BY id DESC LIMIT 1";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $sql3 = "SELECT id FROM equipment_cleaning WHERE user_no='".$_GET["user_no"]."' AND equipment_code='".$row1["equipment_code"]."' AND DATE(entry_date) > DATE(".$row["entry_date"].")";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    $row1["clean"] = "yes";
                                } else {
                                    $row1["clean"] = "no";
                                }
                            }
                        } else {
                            $row1["clean"] = "yes";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["equipments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentCategories") {
        $output = Array();
        $sql = "SELECT equipment_type FROM equipment_names GROUP BY equipment_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM equipment_names WHERE equipment_type='".$row["equipment_type"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["equipments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentsByType") {
        $output = Array();
          $sql = "SELECT DISTINCT(equipment_name) FROM equipment_names";
       // $sql = "SELECT equipment_name FROM equipment_names WHERE equipment_type='".$_GET["equipment_type"]."'";
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipmentTypes") {
        $output = Array();
        $sql = "SELECT DISTINCT(equipment_type) FROM equipment_names";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDeptAllEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='".$_GET["department"]."' 
        AND equipment_type LIKE '%".$_GET["equipment_type"]."%' AND status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE status='approve' ORDER BY equipment_type, equipment_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getallassets") {
        $output = Array(); 
        $sql = "SELECT * FROM equipment where assets_status = '0'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getassets_eqp") {
        $output = Array();
        $sql = "SELECT * FROM equipment where assets_status = '1'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getStoreVacuums") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='Store' AND equipment_name LIKE '%Vaccum%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  else if ($_GET["type"] == "getQCLAfRAF") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='Quality Control' AND (equipment_name LIKE '%RLAF%' or equipment_type = 'LAF')        ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStoreBalance") {
        $output = Array();
        //  $sql = "SELECT * FROM equipment WHERE department='store' AND equipment_name LIKE '%Balance%'";
        $sql="select equipment_code,status from equipment where department='store'";
            //   $sql = "SELECT equipment_code,status FROM equipment WHERE equipment_type='Balance(Weighing)' AND department='Microbiology'";

        //  $sql = "SELECT * FROM equipment WHERE department='store' AND equipment_name LIKE '%Balance%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if($_GET["type"]=="getBalance")
 {
     
     $output = Array();
     
         $sql = "SELECT equipment_code,status FROM equipment WHERE  department='Microbiology' AND plant_id= '".$_GET["plant_id"]."'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     

    else if ($_GET["type"] == "getEquipmentNames") {
        $output = Array();
        $sql = "SELECT * FROM equipment where department like '%".$_GET["department1"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getInstruments") {
        $output = array();
        $sql = "SELECT equipment_name FROM equipment_names WHERE equipment_type='Instrument'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["name"] = $row["equipment_name"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
   
 
    }else  if ($_GET["type"] == "downloadAllEquipments") {
      $_GET['filename'] = 'equipments'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
       $html= "";
         $html.='<table cellpadding="5" border="1">
       <tr>
            <td style="width:10%; text-align:center;"><b>Sr.</b></td>
            <td style="width:20%; text-align:center;"><b>Equipment Type</b></td>
            <td style="width:20%; text-align:center;"><b>Equipment Name</b></td>
            <td style="width:15%; text-align:center;"><b>Equipment code</b></td>
            <td style="width:10%; text-align:center;"><b>Category</b></td>
            <td style="width:15%; text-align:center;"><b>Make</b></td>
            <td style="width:10%; text-align:center;"><b>capacity</b></td>
        </tr>';
        
        $sql = "SELECT * FROM equipment WHERE status='approve' ORDER BY equipment_type, equipment_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                
         $html.=' <tr>
            <td style="width:10%;">'.$i.'</td>
            <td style="width:20%;">'.$row['equipment_type'].'</td>
            <td style="width:20%;">'.$row['equipment_name'].'</td>
            <td style="width:15%;">'.$row['equipment_code'].'</td>
            <td style="width:10%;">'.$row['equipment_category'].'</td>
            <td style="width:15%;">'.$row['make'].'</td>
            <td style="width:10%;">'.$row['capacity'].'</td>
        </tr>';
          $i++;
        }
        }
         $html.='  </table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Equipment Log.pdf', 'I');
        
        
        
     }else if ($_GET["type"] == "downloadEquipmentUsagesLog") {
        $_GET['filename'] = 'Equipment Usages Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";
        $html.='
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:15%; text-align:centre;"><b>Equipment Name</b></td>
                <td style="width:15%; text-align:centre;"><b>Equipment Code	.</b></td>
                <td style="width:10%; text-align:centre;"><b>Activity.</b></td>
                <td style="width:15%; text-align:centre;"><b>Usage From	.</b></td>
                <td style="width:15%; text-align:centre;"><b>Usage To.</b></td>
                <td style="width:10%; text-align:centre;"><b>Operator</b></td>
                <td style="width:10%; text-align:centre;"><b>Check By</b></td>
            </tr>';
            $sql = "SELECT e.*, e1.equipment_name FROM equipment_usages e LEFT JOIN equipment e1 ON e.equipment_code=e1.equipment_code WHERE DATE(e.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'</td>
                <td style="width:15%;">'.$row['equipment_name'].'</td>
                <td style="width:15%;">'.$row['equipment_code'].'</td>
                <td style="width:10%;">'.$row['activity'].'</td>
                <td style="width:15%;">'.$row['usage_from'].'</td>
                <td style="width:15%;">'.$row['usage_to'].'</td>
                <td style="width:10%;">'.$row['operator'].'</td>
                <td style="width:10%;">'.$row['check_by'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EquipmentUsagesLog.pdf', 'I');
   
}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>