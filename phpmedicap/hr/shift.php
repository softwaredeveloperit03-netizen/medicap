<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();


// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);

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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", 
    "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "save_shift") { 


        //  $sql = 'INSERT INTO  shift( shift_name, night_shift, start_time, end_time, duration, date_change, half_day, full_day, default_shift)
        // VALUES( "'.$input["shift_name"]. '", "'.$input["night_shift"]. '", "'.$input["start_time"]. '", "'.$input["end_time"]. '", 
        // "'.$input["duration"]. '", "'.$input["date_change"]. '", "'.$input["half_day"]. '", "'.$input["full_day"]. '"
        // , "'.$input["default_shift"]. '" )'; 
        
        
        // $sql = 'INSERT INTO shift_schedule ( start_time, end_time, lunch_start_time,
        // lunch_end_time, short_leave, short_leave_hrs, half_day_hrs, entry_by, entry_date,plant_id,shift_name,night_shift,duration,date_change,
        // full_day,default_shift,lunch_start_time,
        // lunch_end_time, short_leave)
        // VALUES  ( "'.$input["start_time"]. '", "'. $input["end_time"]. '", 
        // "'.$input["lunch_start_time"]. '", "'.$input["lunch_end_time"]. '", "'.$input["short_leave"]. '", "'.$input["half_day"]. '",
        // "'.$input["half_day_hrs"]. '", "'.$_GET["id"].'", "'.$entry_date.'", "'.$_GET["plant_id"].'", "'.$input["shift_name"]. '", "'.$input["night_shift"]. '"
        // ,  "'.$input["duration"]. '", "'.$input["date_change"]. '", "'.$input["full_day"]. '", "'.$input["default_shift"]. '", 
        // "'.$input["lunch_start_time"]. '", "'.$input["lunch_end_time"]. '", "'.$input["short_leave"]. '" )';
        
        $sql = 'INSERT INTO shift_schedule (start_time, end_time, lunch_start_time,
        lunch_end_time, short_leave, short_leave_hrs, half_day_hrs, entry_by, entry_date, plant_id, shift_name, night_shift, duration, date_change,
        full_day, default_shift)
        VALUES ("'.$input["start_time"].'", "'.$input["end_time"].'", 
        "'.$input["lunch_start_time"].'", "'.$input["lunch_end_time"].'", "'.$input["short_leave"].'", "'.$input["half_day"].'",
        "'.$input["half_day"].'", "'.$_GET["id"].'", "'.$entry_date.'", "'.$_GET["plant_id"].'", "'.$input["shift_name"].'", "'.$input["night_shift"].'",
        "'.$input["duration"].'", "'.$input["date_change"].'", "'.$input["full_day"].'", "'.$input["default_shift"].'")';

        
        
        
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    //  else if ($_GET["type"] == "save_shiftAllocate") { 

    //   $sql = 'INSERT INTO  shift_allocation (Empolyee_Id, Shift_Id,Start_date,End_date,Is_change_request)
    //         VALUES("'.$data['emp_id'].'","'.$data['shift'].'","'.$_GET['from_date'].'","'.$_GET['to_date'].'",0)';
    // 	if($conn->query($sql)){
    // 		echo "{\"status\":\"success\"}";
    // 	} else {
    // 		echo "{\"status\":\"".$conn->error."\"}";
    // 	}
    	
    	
    	
    // }
    	
    
    else if($_GET["type"] == "save_shiftAllocate") {
        // print_r($input);exit;
   
      $prasad = 0;
      $prasad1 = 0;
   
        foreach($input as $data)
        {
         $sql = "Select * from shift_allocation where Empolyee_Id ='".$data['employeeId']."' and Shift_Id = '".$data['shift']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Shift Name Already Exists. Duplicate Values are not allowed\"}";
        }else{
            
            if($data['shift']!='')
            {
            
             $sql = 'INSERT INTO  shift_allocation (Empolyee_Id, Shift_Id,weekly_off,Start_date,End_date,Is_change_request)
            VALUES("'.$data['emp_id'].'","'.$data['shift'].'","'.$data['weekly_off'].'","'.$data['from_date'].'","'.$data['to_date'].'",0)';
            if($conn->query($sql)){
    	//	echo "{\"status\":\"success\"}";
    		
    		$prasad = 1;
                //  echo $sql1 = "UPDATE `attendence` SET `shift` = '".$data['shift']."' WHERE `emp_id` = '".$data['employeeId']."' AND indate BETWEEN '".$_GET['from_date']."' AND '".$_GET['from_date']."'";exit;
                //   $conn->query($sql1);
               
            } else {
               // echo "{\"status\":\"".$conn->error."\"}";
                 $prasad = 0;
            }

          
            }


        
        if($_GET['checkBox']=='true' || $_GET['checkBox1']=='true')
        {
           
             $sql = 'INSERT INTO  shift_allocation (Empolyee_Id, Shift_Id,weekly_off,Start_date,End_date,Is_change_request)
            VALUES("'.$data['employeeId'].'","'.$_GET['shift'].'","'.$data['weekly_off'].'","'.$_GET['from_date'].'","'.$_GET['end_date'].'",0)';
            if($conn->query($sql)){
               // echo "{\"status\":\"success\"}";
                 $prasad1 = 1;
            } else {
               // echo "{\"status\":\"".$conn->error."\"}";
                 $prasad1 = 0;
            }
            
        }
        }
        }
        
            if($prasad == 1 || $prasad1 == 1){
                echo "{\"status\":\"success\"}";
                  
            } else {
                echo "{\"status\":\"".$conn->error."\"}";       
            }
    }
    else if ($_GET["type"] == "get_shift_list") {
        $output = Array();
        $sql = "SELECT * FROM shift_schedule WHERE plant_id='".$_GET["plant_id"]."' order by id desc "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    } 
    else if ($_GET["type"] == "getShiftByPlant") {
        $output = Array();
        $sql = "SELECT * FROM shift_schedule WHERE plant_id='".$_GET["plantID"]."' order by id desc "; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    } 
    else if($_GET["type"] == "getShiftList")
    {
        $output = Array();
        $sql = "SELECT id ,shift_name,start_time,end_time FROM shift_schedule WHERE plant_id='".$_GET["plant_id"]."'"; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if($_GET["type"] == "shift_chnge_log")
    {
        $output = Array();
         $sql = "SELECT a.*,b.shift_name FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id WHERE   a.Empolyee_Id='".$_GET['emp_id']."'"; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if($_GET["type"] == "shift_chnge_Approval")
    {
        $output = Array();
        
        if($_GET['approval_from']=='HR'){
          $sql = "SELECT c.*,c.firstname,a.*,b.shift_name FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id left join employee c on a.Empolyee_Id = c.emp_id where a.hr_status='pending'"; 
        }else if($_GET['approval_from']=='dept_head'){
        $sql = "SELECT a.*,b.shift_name,c.firstname,c.department FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id left join employee c on a.Empolyee_Id = c.emp_id  where dept_head_status='pending' and c.department='".$_GET["department"]."'"; 

        //  $sql = "SELECT a.*,b.shift_name FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id  where dept_head_status='pending'"; 
        }
        
        
        
        
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if($_GET["type"] == "shift_chnge_Approval_log")
    {
        $output = Array();
        
        if($_GET['approval_from']=='HR'){
         $sql = "SELECT a.*,b.entry_by,c.firstname,c.lastname,b.shift_name FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id left join employee c on a.Empolyee_Id=c.emp_id where a.hr_status!='pending'"; 
        }else if($_GET['approval_from']=='dept_head'){
         $sql = "SELECT a.*,b.shift_name,b.entry_by,c.department,c.firstname,c.lastname FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id left join employee c on a.Empolyee_Id = c.emp_id  where dept_head_status!='pending' and c.department='".$_GET["department"]."'"; 
        }
        
        
        
        
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
    else if($_GET["type"] == "getSearchLog")
    {
        $output = Array();
        
        if($_GET['approval_from']=='HR'){
         $sql = "SELECT a.*,c.firstname,c.lastname,b.shift_name FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id left join employee c on a.Empolyee_Id=c.emp_id where a.hr_status!='pending' AND
         (c.firstname LIKE '%".$_GET["value"]."%' OR c.lastname  LIKE '%".$_GET["value"]."%',OR b.shift_name  LIKE '%".$_GET["value"]."%'OR c.emp_id  LIKE '%".$_GET["value"]."%')" ; 
        }else if($_GET['approval_from']=='dept_head'){
         $sql = "SELECT a.*,b.shift_name,c.department,c.firstname,c.lastname FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id left join employee c on a.Empolyee_Id = c.emp_id  where dept_head_status!='pending' and c.department='".$_GET["department"]."' AND
         (c.firstname LIKE '%".$_GET["value"]."%' OR c.lastname  LIKE '%".$_GET["value"]."%',OR b.shift_name  LIKE '%".$_GET["value"]."%'OR c.emp_id  LIKE '%".$_GET["value"]."%')"; 
        }
        
        
        
        
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
     else if ($_GET["type"] == "changeStatusByManagement") {
         $sql = "UPDATE candidate SET manage_approval = '".$_GET["status1"]."' WHERE id = '".$_GET["cid"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "change_week_off") {
         $sql = "UPDATE shift_allocation SET weekly_off = '".$_GET["Weekoff"]."' WHERE id = '".$_GET["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
          } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "shift_chnge_log")
    {
        $output = Array();
         $sql = "SELECT a.*,b.shift_name FROM shift_chnge_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id WHERE   a.Empolyee_Id='".$_GET['emp_id']."'"; 
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "save_shift_schedule") { 

        $sql = 'INSERT INTO shift_schedule ( start_time, end_time, lunch_start_time,
        lunch_end_time, short_leave, short_leave_hrs, half_day_hrs, entry_by, entry_date,plant_id,shift_name)
        VALUES  ( "'.$input["start_time"]. '", "'. $input["end_time"]. '", 
        "'.$input["lunch_start_time"]. '", "'.$input["lunch_end_time"]. '", "'.$input["short_leave"]. '", "'.$input["half_day_hrs"]. '",
        "'.$input["half_day_hrs"]. '", "'.$_GET["id"].'", "'.$entry_date.'", "'.$_GET["plant_id"].'", "'.$input["shift_name"]. '")';
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    	
    }
    else if ($_GET["type"] == "save_shift_change_request") { 

        $sql = 'INSERT INTO shift_schedule ( start_time, end_time, lunch_start_time,
        lunch_end_time, short_leave, short_leave_hrs, half_day_hrs, entry_by, entry_date,plant_id,shift_name)
        VALUES  ( "'.$input["start_time"]. '", "'. $input["end_time"]. '", 
        "'.$input["lunch_start_time"]. '", "'.$input["lunch_end_time"]. '", "'.$input["short_leave"]. '", "'.$input["half_day_hrs"]. '",
        "'.$input["half_day_hrs"]. '", "'.$_GET["id"].'", "'.$entry_date.'", "'.$_GET["plant_id"].'", "'.$input["shift_name"]. '")';
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    	
    }
    else if ($_GET["type"] == "update_chnge_shift_status") { 


                if($input['app_from']=='HR'){
       $sql = "update shift_chnge_allocation set hr_status='".$input['status']."',hr_approved_by= '".$input["username"]."'  ,hr_approved_date= '.$entry_date.' ,hr_approved_by_id='".$_GET['emp_id']."', approvedFrom='".$input['app_from']."' where id='".$input['id']."'";
                }
            if($input['app_from']=='dept_head'){
        $sql = "update shift_chnge_allocation set dept_head_status='".$input['status']."',dept_head_approved_by=  '".$input["username"]."',dept_head_approved_by_id='".$_GET['emp_id']."',dept_head_approved_date='.$entry_date.',approvedFrom='".$input['app_from']."'   where id='".$input['id']."'";
                }
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    	
    }
    else if ($_GET["type"] == "save_shift_change_request_New") { 

       $sql = 'INSERT INTO shift_chnge_allocation (entry_date, Request_reason, plant_id, Empolyee_Id, Shift_Id, weekly_off, Start_date, End_date, Is_change_request)
        VALUES ("'.$entry_date.'", "'.$input['Request_reason'].'", "'.$_GET['plant_id'].'", "'.$input['employee'].'", "'.$input['selected_shift_id'].'", "'.$input['selected_shift_weekly_off'].'", "'.$input['from_date'].'", "'.$input['to_date'].'", 0)';

       	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    	
    }
    else if ($_GET["type"] == "getcurrentShift") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
         $output = Array();
         $sql = "SELECT a.*,b.shift_name FROM shift_allocation a left JOIN shift_schedule b on a.Shift_Id=b.id WHERE CURRENT_DATE BETWEEN a.Start_date AND a.End_date and a.Empolyee_Id='".$_GET['empid']."' ";
 
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    }
    else if ($_GET["type"] == "getcurrentShift_emp") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
         $output = Array();
         
          $sql = "SELECT a.*, b.shift_name, b.start_time, b.end_time 
FROM shift_allocation a 
LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
WHERE a.Empolyee_Id  = '".$_GET['emp_id']."' 
ORDER BY a.id DESC 
LIMIT 1;
";
 
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    }
    else if ($_GET["type"] == "getcurrentShift_emp_change") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
         $output = Array();
         
          $sql = "SELECT a.*, b.shift_name, b.start_time, b.end_time 
FROM shift_chnge_allocation a 
LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
WHERE a.Empolyee_Id  = '".$_GET['emp_id']."' 
ORDER BY a.id DESC 
LIMIT 1;
";
 
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    }
    else if ($_GET["type"] == "emp_personal_dtl") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
       
        $sql = "select * from employee where emp_id='".$_GET['emp_id']."' ";
 
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $joiningDate = new DateTime($row['joining_date']);
    $currentDate = new DateTime(); // Current date
    
    // Calculate the difference in months
    $interval = $joiningDate->diff($currentDate);
    $monthsDiff = $interval->y * 12 + $interval->m;

    // Check if the difference is 6 months or more
    if ($monthsDiff >= 6) {
        $empStatus = 'old';
    } else {
        $empStatus = 'new';
    }
    $row['emp_stat']=$empStatus;
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    }
    
    
    
    else if ($_GET["type"] == "saveLetter") { 

       $sql = 'insert into letter ( plant_id, letter_type,department,designation, emp_name, emp_id, subject, body, goverment, letter_to, address,letter_date,entry_by) 
	VALUES("'.$_GET["plant_id"].'","'.$input["letter_type"].'","'.$input["department"].'","'.$input["designation"].'","'.$input["employee"].'","'.$input["emp_id"].'","'.$input["subject"].'"
	,"'.$input["body"].'","'.$input["goverment"].'","'.$input["to"].'","'.$input["address"].'","'.$entry_date.'", "'.$_GET["id"].'")';
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    	
    }
    else if ($_GET["type"] == "getGovagency") {
       
       $sql = "select * from govagency";
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    }
    else if ($_GET["type"] == "getEmployees") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);

        $output = Array();
      
      if($_GET["department1"] == 'ALL Employee'){
          
       $sql = "SELECT a.shift_name,b.emp_id,CONCAT(b.firstname,' ',b.lastname) as emp_name,b.department,b.designation 
       FROM employee b left join shift a on a.emp_id=b.emp_id where b.status = 'active'  AND b.plant_id='".$_GET["plant_id"]."' "; 

      }else{
       $sql = "SELECT a.shift_name,b.emp_id,CONCAT(b.firstname,' ',b.lastname) as emp_name,b.department,b.designation 
       FROM employee b left join shift a on a.emp_id=b.emp_id where b.status = 'active' AND b.department='".$_GET["department1"]."'
       AND b.plant_id='".$_GET["plant_id"]."'"; 
       
      }
       
     	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		  //    $output1 = Array();
    		  //   $sql1 = "select * from shift_allocation where Empolyee_Id='".$row["emp_id"]."' limit 1 order by id desc";
    		  //  	$result1 = $conn->query($sql1);
        //                 	if($result1->num_rows > 0){
        //                 		while($row1 = $result1->fetch_assoc()){
                        		    
        //                 		   	$output1[] = $row1;
        //                 		}
        //                 	}
    		    
    		    
    		  // // $row['weekly_off'] = '';
    		  // $row['allo']=$output1[];
    		   $output1 = Array();
                        $sql1 = "select * from shift_allocation where Empolyee_Id='".$row["emp_id"]."'  order by id desc limit 1";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                               
                                $row['Start_date'] = $row1['Start_date'];
                                $row['End_date'] = $row1['End_date'];
                            }
                        }
                        
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }
    
    
     else if ($_GET["type"] == "ReviewShedule") {
        
    	        $sql = "update shift_allocation set allocationStatus='pendingAndReview' where id='".$_GET["Id"]."'";
    	        $result = $conn->query($sql);
    	          if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    }
    
    
     
    
     else if ($_GET["type"] == "ReviewSheduleAll") {
                     $status1 = false;
         
              $json_obj = json_encode($input["data"]);
              $array = json_decode($json_obj, true);
                
                foreach ($array as $values)
                {
                         $sql = "update shift_allocation set allocationStatus='pendingAndReview' where id='".$values["a_id"]."' and allocationStatus='Pending'";
                    
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
    
    
     else if ($_GET["type"] == "ApproveShedule") {
    	        $sql = "update shift_allocation set allocationStatus='Approved' where id='".$_GET["Id"]."'";
    	        $result = $conn->query($sql);
    	          if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    	
    }
    
    
     else if ($_GET["type"] == "ApproveAllShiftShedule") {
         
         
         
         $jadugar = false;
         
         
           $json_obj = json_encode($input["data"]);
              $array = json_decode($json_obj, true);
              
              
                foreach ($array as $values)
                {
         
         
                     $sql = "update shift_allocation set allocationStatus='Approved' where id='".$values["a_id"]."'";
                 
                	if ($conn->query($sql)) {
                         $jadugar = true;
                    } else {
                        $jadugar = false;
                    }
         
         
                }
    
    
    	if ($jadugar) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    	
    }
    
    else if ($_GET["type"] == "getShiftSchedule") {
        $output = Array();
        
         if($_GET["department_name"] == 'ALL Employee'){
          
       $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,
         a.End_date as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where  a.allocationStatus = 'Pending' order by a.id desc "; 

      }else{
         $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,
         a.End_date as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where e.department LIKE '%".$_GET["department_name"]."' 
        AND a.allocationStatus = 'Pending' order by a.id desc";
      }
        //WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "getAbsentShiftScheduleLog") {
        $output = Array();
        
   
       
          $sql = "SELECT a.id, a.firstname, a.lastname, a.department, a.category, b.Start_date, b.End_date,a.emp_id,a.operator_category
                    FROM employee a
                    LEFT JOIN shift_allocation b ON a.emp_id = b.Empolyee_Id
                    LEFT JOIN shift_schedule c ON b.Shift_Id = c.id
                    WHERE a.department='".$_GET["department_name"]."' AND a.plant_id='".$_GET["plant_id"]."'
                      AND NOT EXISTS (
                        SELECT 1
                        FROM shift_allocation
                        WHERE Empolyee_Id = a.emp_id
                          AND '".$_GET["selDate"]."' BETWEEN start_date AND end_date
                      )
                      
                    GROUP BY a.id, b.Start_date, b.End_date
                    ORDER BY a.address_permanent ASC;";
        //   $sql = "SELECT a.* FROM employee a LEFT JOIN shift_allocation b ON a.emp_id = b.Empolyee_Id LEFT JOIN shift_schedule c ON b.Shift_Id = c.id 
        //   WHERE a.department='".$_GET["department_name"]."'  AND NOT ('".$_GET["selDate"]."' BETWEEN b.start_date AND b.end_date) group by a.id ORDER BY `a`.`id` ASC;";
      
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "HOgetAbsentShiftScheduleLog") {
        $output = Array();
        
   
       
          $sql = "SELECT a.id, a.firstname, a.lastname, a.department, a.category, b.Start_date, b.End_date,a.emp_id,a.operator_category
                    FROM employee a
                    LEFT JOIN shift_allocation b ON a.emp_id = b.Empolyee_Id
                    LEFT JOIN shift_schedule c ON b.Shift_Id = c.id
                    WHERE a.department='".$_GET["department_name"]."' AND a.plant_id='".$_GET["plantID"]."'
                      AND NOT EXISTS (
                        SELECT 1
                        FROM shift_allocation
                        WHERE Empolyee_Id = a.emp_id
                          AND '".$_GET["selDate"]."' BETWEEN start_date AND end_date
                      )
                      
                    GROUP BY a.id, b.Start_date, b.End_date
                    ORDER BY a.address_permanent ASC;";
        //   $sql = "SELECT a.* FROM employee a LEFT JOIN shift_allocation b ON a.emp_id = b.Empolyee_Id LEFT JOIN shift_schedule c ON b.Shift_Id = c.id 
        //   WHERE a.department='".$_GET["department_name"]."'  AND NOT ('".$_GET["selDate"]."' BETWEEN b.start_date AND b.end_date) group by a.id ORDER BY `a`.`id` ASC;";
      
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "getShiftScheduleLog") {
        $output = Array();
        
        if($_GET['department_name']==='IT'){
                $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,a.End_date as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where e.department LIKE '%".$_GET["department_name"]."' 
        AND a.allocationStatus != 'Pending' AND e.plant_id = '".$_GET["plant_id"]."' order by a.id desc";
       
        }else if($_GET["department_name"] == 'ALL Employee'){
          
       $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,a.End_date as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where  a.allocationStatus != 'Pending' AND e.plant_id = '".$_GET["plant_id"]."'  order by a.id desc";

      }else{
            
       
          $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,a.End_date as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where e.department LIKE '%".$_GET["department_name"]."%' 
        AND a.allocationStatus != 'Pending' AND e.plant_id = '".$_GET["plant_id"]."'  order by a.id desc";
        //WHERE status='active'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "HOgetShiftScheduleLog") {
        $output = Array();
        
        if($_GET['department_name']==='IT'){
                $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,a.End_date
                as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where e.department LIKE '%".$_GET["department_name"]."' AND
        a.allocationStatus != 'Pending'  AND e.plant_id = '".$_GET["plantID"]."' order by a.id desc";
       
        }else{
            
       
          $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.department,e.operator_category,a.id as a_id,a.Start_date as aaaa,
          a.End_date as bbb FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id  where e.department LIKE '%".$_GET["department_name"]."%' 
        AND a.allocationStatus != 'Pending' AND e.plant_id = '".$_GET["plantID"]."' order by a.id desc";
        //WHERE status='active'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "getShiftScheduleBydept") {
        $output = Array();
         $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.operator_category,a.id as a_id FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id AND e.department = '".$_GET["deptName"]."' order by a_id desc";
        //WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if ($_GET["type"] == "getShiftSchedule_aapr") {
        $output = Array();
        $sql = "SELECT a.*,b.*,e.firstname,e.lastname,e.operator_category,a.id as a_id FROM shift_allocation a left join shift_schedule b on b.id=a.shift_id 
        left join employee e on a.Empolyee_Id = e.emp_id and a.allocationStatus='pendingAndReview' ";
        //WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    	
    	
    }
    else if($_GET["type"] == "editShift"){
        $sql = "UPDATE shift SET shift_name='".$input["shift_name"]."', night_shift='".$input["night_shift"]."' ,
        start_time='".$input["start_time"]."',end_time='".$input["end_time"]."',
        duration='".$input["duration"]."',date_change='".$input["date_change"]."',half_day='".$input["half_day"]."',
        full_day='".$input["full_day"]."',default_shift='".$input["default_shift"]."' WHERE id ='".$_GET["id"]."' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if ($_GET["type"] == "deleteShift") {
        //$sql = "UPDATE pricelist SET status='Deleted' WHERE id='".$_GET["id"]."'";
         $sql="DELETE FROM shift_schedule WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getShiftLog") {
        $output = Array();
        $sql = "SELECT  FROM shift WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
      
    
}
else if ($_GET["type"] == "allocateShift") {
        $output = Array();
        $sql = "SELECT * FROM shift  WHERE shift='allocate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
}

$conn->close();
?>