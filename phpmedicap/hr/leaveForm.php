<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"]; 

// ini_set('display_errors', 1);
// error_reporting(E_ALL);


 $entry_month = date("m");
 $entry_year = date("Y");

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
    
    if ($_GET["type"] == "saveLeaveForm") {
        
        
        
        $sql = "INSERT INTO leaveform (plant_id,department_name,emp_id,leave_from,leave_to,no_day,leave_type,reason_leave,approver,charge_handover,
        alternate_person,contact_no,pendingList,entry_by,entry_date,status)
        VALUES ('".$_GET["plant_id"]."','".$input["department_name"]."','".$input["emp_id"]."','".$input["leave_from"]."','".$input["leave_to"]."',
        '".$input["no_day"]."','".$input["leave_type"]."','".$input["reason_leave"]."','".$input["approver"]."','".$input["charge_handover"]."',
        '".$input["alternate_person"]."','".$input["contact_no"]."','".json_encode($input["pendingList"])."','".$_GET["emp_id"]."','$entry_date','Pending_Dept_Head_Approval')";
        
      
      
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    }
    
    
    
   else if ($_GET["type"] == "saveLeaveForm_coff") {
        $sql = "INSERT INTO leaveform_coff (plant_id,department_name,emp_id,leave_from,leave_to,no_day,leave_type,reason_leave,approver,charge_handover,
        alternate_person,contact_no,pendingList,entry_by,entry_date,status)
        VALUES ('".$_GET["plant_id"]."','".$input["department_name"]."','".$input["emp_id"]."','".$input["leave_from"]."','".$input["leave_to"]."',
        '".$input["no_day"]."','".$input["leave_type"]."','".$input["reason_leave"]."','".$input["approver"]."','".$input["charge_handover"]."',
        '".$input["alternate_person"]."','".$input["contact_no"]."','".json_encode($input["pendingList"])."','".$_GET["emp_id"]."','$entry_date','Pending_Dept_Head_Approval')";
        
      
      
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "getLeaveForm") {
        $output = array();
        $sql = "SELECT * FROM leaveform WHERE status ='pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["pendingList"] = json_decode($row["pendingList"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateLeaveForm") {
        
         
        if($_GET["status"]=='modified'){
            
               $sql = "UPDATE leaveform SET status='".$_GET["status"]."',entry_by='".$_GET["emp_id"]."', 
        entry_date='$entry_date', remark = '".$_GET["remark"]."', modi_no_day = '".$_GET["modi_no_day"]."',
        modi_leave_from = '".$_GET["modi_leave_from"]."' ,modi_leave_to = '".$_GET["modi_leave_to"]."' WHERE id='".$_GET["id"]."'";
            
        }else{
              $sql = "UPDATE leaveform SET remark='".$_GET["remark"]."',status='".$_GET["status"]."',entry_by='".$_GET["emp_id"]."', 
        entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        }
         
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
             $sql = "SELECT l.emp_id, l.no_day, l.modi_no_day, e.firstname , lp.total_leave,l.leave_type FROM leaveform l left join employee e ON 
            e.emp_id = l.emp_id left join leavepolicy lp ON e.designation = lp.designation_heading  WHERE l.id='".$_GET["id"]."'";
            
            $result = $conn->query($sql);
             if ($result->num_rows > 0) {
                 while ($row1 = $result->fetch_assoc()) {
                     $empid       = $row1["emp_id"];
                     $no_day      = $row1["no_day"];
                     $modi_no_day = $row1["modi_no_day"];
                     $firstname   = $row1["firstname"];
                     $total_leave = $row1["total_leave"];
                     $leave_type = $row1["leave_type"];
                     
                    }
                }
            
            
            if($modi_no_day > 0){
                 $num_days = $modi_no_day;
            }else{
                 if (strcasecmp($leave_type, 'Half Day')) {
                       $num_days = $no_day;
                    //   echo('hi');
                 }
                 else{
                      $num_days = 0.5;
              
                // echo('hello');
                 }
            }
            
         $sql11 = "SELECT emp_id,leave_taken FROM leave_card WHERE emp_id ='$empid'";
            
            $result = $conn->query($sql11);
             if ($result->num_rows > 0) {
                 while ($row2 = $result->fetch_assoc()) {
                       $leave_taken   = $row2["leave_taken"];
                    }
                
                $new_taken = $leave_taken + $num_days;
                
                $bal = $total_leave - $new_taken;
                
               if (strcasecmp($leave_type, 'Half Day') == 0) {
                         $sql1 = "UPDATE leave_card SET leave_taken = '$new_taken' , leave_balance = '$bal'  WHERE emp_id ='$empid'";
            $conn->query($sql1);
                }else{
                      $sql1 = "UPDATE leave_card SET leave_taken = '$new_taken' , leave_balance = '$bal'  WHERE emp_id ='$empid'";
            $conn->query($sql1);
                }
                
           
                
                
             }else{
                  $bal = $total_leave - $num_days;
                    if (strcasecmp($leave_type, 'Half Day') == 0) {
                   $sql1 = "INSERT INTO leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' )";
                }else{
                    $sql1 = "INSERT INTO leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' )";
            
            $conn->query($sql1);
                }
                 
            
             }
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
        
        
        
    }
    else if ($_GET["type"] == "updateLeaveFormHOD") {
        
          
               $sql = "UPDATE leaveform SET remark='".$_GET["remark"]."',status='".$_GET["status"]."',hod_approvar='".$_GET["emp_id"]."', 
        hod_approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
       
         
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
        
         
        
    }
    else if ($_GET["type"] == "updateLeaveFormHODCoff") {
        
          
               $sql = "UPDATE leaveform_coff SET remark='".$_GET["remark"]."',status='".$_GET["status"]."',hod_approvar='".$_GET["emp_id"]."', 
        hod_approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
       
         
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
        
         
        
    }
    else if ($_GET["type"] == "updateLeaveFormplantHead") {
        if($_GET["leave_typesss"]=='C oFF'){
               $sql = "UPDATE leaveform_coff SET remark='".$_GET["remark"]."',status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }else{
            
        
          
        $sql = "UPDATE leaveform SET remark='".$_GET["remark"]."',status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
         
        // if ($conn->query($sql)) {
        //     echo "{\"status\":\"success\"}";
        // } else {
        //     echo "{\"status\":\"".$conn->error."\"}";
        // }
         if ($conn->query($sql)) {
             
         
             
            echo "{\"status\":\"success\"}";
            
            
             $sql = "SELECT l.emp_id, l.no_day, l.modi_no_day, e.firstname , lp.total_leave,l.leave_type FROM leaveform l left join employee e ON 
            e.emp_id = l.emp_id left join leavepolicy lp ON e.designation = lp.designation_heading  WHERE l.id='".$_GET["id"]."'";
            
            $result = $conn->query($sql);
             if ($result->num_rows > 0) {
                 while ($row1 = $result->fetch_assoc()) {
                     $empid       = $row1["emp_id"];
                     $no_day      = $row1["no_day"];
                     $modi_no_day = $row1["modi_no_day"];
                     $firstname   = $row1["firstname"];
                     $total_leave = $row1["total_leave"];
                     $leave_type = $row1["leave_type"];
                //   $entry_month1 = $row1["leave_from"];  // Assuming $row1["leave_from"] has the value '2024-07-14'
                    // $month11 = date('m', strtotime($row1["leave_from"]));  // Extracts the month part
                     
                     
                    }
                }
            
            
            if($modi_no_day > 0){
                 $num_days = $modi_no_day;
            }else{
                 if (strcasecmp($leave_type, 'Half Day')) {
                       $num_days = $no_day;
                    //   echo('hi');
                 }
                 else{
                      $num_days = 0.5;
              
                // echo('hello');
                 }
            }
            
         $sql11 = "SELECT emp_id,leave_taken FROM leave_card WHERE emp_id ='$empid'";
            
            $result = $conn->query($sql11);
             if ($result->num_rows > 0) {
                 while ($row2 = $result->fetch_assoc()) {
                       $leave_taken   = $row2["leave_taken"];
                    }
                
                $new_taken = $leave_taken + $num_days;
                
                $bal = $total_leave - $new_taken;
                
               if (strcasecmp($leave_type, 'Half Day') == 0) {
                         $sql1 = "UPDATE leave_card SET leave_taken = '$new_taken' , leave_balance = '$bal'  WHERE emp_id ='$empid'";
            $conn->query($sql1);
                }else{
                      $sql1 = "UPDATE leave_card SET leave_taken = '$new_taken' , leave_balance = '$bal'  WHERE emp_id ='$empid'";
            $conn->query($sql1);
                }
                
           
                
                
             }else{
                  $bal = $total_leave - $num_days;
                    if (strcasecmp($leave_type, 'Half Day') == 0) {
                   $sql1 = "INSERT INTO leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' )";
                }else{
                    $sql1 = "INSERT INTO leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' )";
            
            $conn->query($sql1);
                }
                 
            
             }
             
             
            $last_month='0'.$entry_month-1;
            
            
            
            
            
            
            
                       $sql="select * from monthly_leave_card where  year='$entry_year' and emp_id ='$empid' order by id desc limit 1";
                 
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if($row['leave_balance']>=0){
                            
                        $lastMonthleavtake = $row['leave_balance'];
                        
                            break;
                         }
                        else{
                             $lastMonthleavtake=0;
                        }
                    }
                    
                }else{
                      $lastMonthleavtake=$total_leave;

                }
            
            
            
            
             

             
             
             
                 $sql="select * from monthly_leave_card where month='$entry_month' and  year='$entry_year' and emp_id ='$empid' limit 1";
             
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $leavtake=$row['leave_taken']+$num_days;
                
                
                
                // $leave_balance=$row['leave_balance']-$num_days;
                $leave_balance=$row['last_month_total_leave']-$leavtake;
                
                $bal1=$row['total_leave']-$row['last_month_total_leave'];
               // if($row['total_bal_leaves']=='0'){
                $leav_bal=$bal1-$row['leave_taken'];
             //   }
                
                 $month11 = date('m', strtotime($input["leave_from"]));
                
                 $sql1="update monthly_leave_card set leave_taken='$leavtake',leave_balance='$leave_balance' where emp_id='$empid' and  month='$month11' and  year='$entry_year'";
                $conn->query($sql1);
            }
        }else{
            
         $month11 = date('m', strtotime($input["leave_from"]));  // Extracts the month part
            
               $sql1 = "INSERT INTO monthly_leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance,month,year,last_month_total_leave)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' ,'$month11','$entry_year','$lastMonthleavtake')";
            $conn->query($sql1);
        }
             
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
        
    }
    else if ($_GET["type"] == "updateLeaveFormplantHeadMeha") {
        if($_GET["leave_typesss"]=='C oFF'){
               $sql = "UPDATE leaveform_coff SET remark='".$_GET["remark"]."',status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }else{
            
        
          
        $sql = "UPDATE leaveform SET remark='".$_GET["remark"]."',status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
         
        // if ($conn->query($sql)) {
        //     echo "{\"status\":\"success\"}";
        // } else {
        //     echo "{\"status\":\"".$conn->error."\"}";
        // }
         if ($conn->query($sql)) {
             
         
             
            echo "{\"status\":\"success\"}";
            
            
             $sql = "SELECT l.emp_id, l.no_day, l.modi_no_day, e.firstname , lp.total_leave,l.leave_type FROM leaveform l left join employee e ON 
            e.emp_id = l.emp_id left join leavepolicy lp ON l.leave_type = lp.leave_title  WHERE l.id='".$_GET["id"]."' and e.plant_id='".$_GET["plant_id"]."' ";
            
            $result = $conn->query($sql);
             if ($result->num_rows > 0) {
                 while ($row1 = $result->fetch_assoc()) {
                     $empid       = $row1["emp_id"];
                     $no_day      = $row1["no_day"];
                     $modi_no_day = $row1["modi_no_day"];
                     $firstname   = $row1["firstname"];
                     $total_leave = $row1["total_leave"];
                     $leave_type = $row1["leave_type"];
                //   $entry_month1 = $row1["leave_from"];  // Assuming $row1["leave_from"] has the value '2024-07-14'
                    // $month11 = date('m', strtotime($row1["leave_from"]));  // Extracts the month part
                     
                     
                    }
                }
            
            
            if($modi_no_day > 0){
                 $num_days = $modi_no_day;
            }else{
                 if (strcasecmp($leave_type, 'Half Day')) {
                       $num_days = $no_day;
                    //   echo('hi');
                 }
                 else{
                      $num_days = 0.5;
              
                // echo('hello');
                 }
            }
            
         $sql11 = "SELECT emp_id,leave_taken FROM leave_card WHERE emp_id ='$empid'";
            
            $result = $conn->query($sql11);
             if ($result->num_rows > 0) {
                 while ($row2 = $result->fetch_assoc()) {
                       $leave_taken   = $row2["leave_taken"];
                    }
                
                $new_taken = $leave_taken + $num_days;
                
                $bal = $total_leave - $new_taken;
                
               if (strcasecmp($leave_type, 'Half Day') == 0) {
                         $sql1 = "UPDATE leave_card SET leave_taken = '$new_taken' , leave_balance = '$bal'  WHERE emp_id ='$empid'";
            $conn->query($sql1);
                }else{
                      $sql1 = "UPDATE leave_card SET leave_taken = '$new_taken' , leave_balance = '$bal'  WHERE emp_id ='$empid'";
            $conn->query($sql1);
                }
                
           
                
                
             }else{
                  $bal = $total_leave - $num_days;
                    if (strcasecmp($leave_type, 'Half Day') == 0) {
                   $sql1 = "INSERT INTO leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' )";
                }else{
                    $sql1 = "INSERT INTO leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' )";
            
            $conn->query($sql1);
                }
                 
            
             }
             
             
            $last_month='0'.$entry_month-1;
            
            
            
            
            
            
            
                       $sql="select * from monthly_leave_card where  year='$entry_year' and emp_id ='$empid' order by id desc limit 1";
                 
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if($row['leave_balance']>=0){
                            
                        $lastMonthleavtake = $row['leave_balance'];
                        
                            break;
                         }
                        else{
                             $lastMonthleavtake=0;
                        }
                    }
                    
                }else{
                      $lastMonthleavtake=$total_leave;

                }
            
            
            
            
             

             
             
             
                 $sql="select * from monthly_leave_card where month='$entry_month' and  year='$entry_year' and emp_id ='$empid' limit 1";
             
              $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $leavtake=$row['leave_taken']+$num_days;
                
                
                
                // $leave_balance=$row['leave_balance']-$num_days;
              $leave_balance = (int)$row['last_month_total_leave'] - (int)$leavtake;

                
                $bal1=(int)$row['total_leave']- (int)$row['last_month_total_leave'];
               // if($row['total_bal_leaves']=='0'){
                $leav_bal=$bal1-$row['leave_taken'];
             //   }
                
                 $month11 = date('m', strtotime($input["leave_from"]));
                
                 $sql1="update monthly_leave_card set leave_taken='$leavtake',leave_balance='$leave_balance' where emp_id='$empid' and  month='$month11' and  year='$entry_year'";
                $conn->query($sql1);
            }
        }else{
            
         $month11 = date('m', strtotime($input["leave_from"]));  // Extracts the month part
            
               $sql1 = "INSERT INTO monthly_leave_card ( plant_id,firstname, emp_id, total_leave , leave_taken, leave_balance,month,year,last_month_total_leave)
            VALUES ('".$_GET["plant_id"]."','$firstname','$empid','$total_leave','$num_days','$bal' ,'$month11','$entry_year','$lastMonthleavtake')";
            $conn->query($sql1);
        }
             
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
        
    }
   
    else if ($_GET["type"] == "getApprovedLeave") {
        $output = array();
        $sql = "SELECT l.*,e.firstname as afirstname,e.lastname as alastname ,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.emp_id ) as fnempname,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.emp_id ) as lnempname,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.charge_handover ) as fnchange_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.charge_handover ) as lnchange_hand,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.alternate_person ) as fnalt_change_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.alternate_person ) as lnalt_change_hand
        FROM leaveform l left join employee e ON e.emp_id = l.approver
        WHERE l.status='pending' AND  l.hod_status ='Approved'  ";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
     
         
    } 
    else if ($_GET["type"] == "getPendingLeaveForDept") {
        $output = array();
        $sql = " SELECT distinct l.id, l.*,e.firstname as afirstname,e.lastname as alastname ,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.emp_id limit 1) as fnempname,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.emp_id limit 1) as lnempname,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.charge_handover limit 1) as fnchange_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.charge_handover limit 1) as lnchange_hand,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.alternate_person limit 1) as fnalt_change_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.alternate_person limit 1) as lnalt_change_hand
        FROM leaveform l left join employee e ON e.emp_id = l.approver
        WHERE l.status='Pending_Dept_Head_Approval' AND department_name = '".$_GET["department_name"]."' AND approver = '".$_GET["approver"]."' ";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
     
         
    } 
    else if ($_GET["type"] == "getPendingLeaveForDeptcoff") {
        $output = array();
         $sql = "SELECT l.*,e.firstname as afirstname,e.lastname as alastname ,
         (SELECT e.firstname FROM employee e WHERE e.emp_id=l.emp_id limit 1) as fnempname,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.emp_id limit 1) as lnempname,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.charge_handover limit 1) as fnchange_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.charge_handover limit 1) as lnchange_hand,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.alternate_person limit 1) as fnalt_change_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.alternate_person limit 1) as lnalt_change_hand
        FROM leaveform_coff l left join employee e ON e.emp_id = l.approver
        WHERE l.status='Pending_Dept_Head_Approval' AND department_name = '".$_GET["department_name"]."' AND approver = '".$_GET["approver"]."'  group by l.id,afirstname,alastname";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
     
         
    } 
    
    else if ($_GET["type"] == "getPendingLeaveForPlantHead") {
        $output = array();
        
    $sql = "SELECT distinct l.id, l.*, e.firstname as fnempname, e.lastname as lnempname,  approver.firstname as lapprover,  approver.lastname as fapprover FROM 
    leaveform l LEFT JOIN employee e ON e.emp_id = l.emp_id LEFT JOIN employee approver ON approver.emp_id = l.approver
    WHERE l.status='Pending_Plant_Head_Approval' ORDER BY l.id DESC";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
        echo json_encode($output);
    } 
   
    
    else if ($_GET["type"] == "getPendingLeaveForPlantHeadCoff") {
        $output = array();
         $sql = "SELECT 
    l.*, 
    e.firstname as fnempname,
    e.lastname as lnempname, 
    approver.firstname as lapprover, 
    approver.lastname as fapprover
FROM 
    leaveform_coff l 
LEFT JOIN 
    employee e ON e.emp_id = l.emp_id
LEFT JOIN 
    employee approver ON approver.emp_id = l.approver
WHERE 
    l.status='Pending_Plant_Head_Approval'  group by l.id,fnempname,lnempname,lapprover,fapprover
ORDER BY 
    l.id DESC;
";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
     
         
    } 
    else if ($_GET["type"] == "getPendingLeaveForDeptNotification") {
        $output = array();
        $sql = "SELECT count(id) as pending_leaveForm FROM leaveform WHERE status='Pending_Dept_Head_Approval' AND department_name = '".$_GET["department_name"]."'";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output = $row;
                }
            }
             
         
          //  $output['pending_leaveForm'] =3;
            $test = "You Have '".$output['pending_leaveForm']."' Leave Form Pending For Approval";
            $output['text'] = $test;
    
    echo json_encode($output);
     
         
    } 
    else if ($_GET["type"] == "getLeaveLog") {
        $output = array();
         $sql = "SELECT l.*,e.firstname as fnempname,e.lastname as lnempname,
         (select CONCAT(firstname, ' ', lastname) from employee where emp_id = l.hod_approvar AND plant_id = '".$_GET["plant_id"]."') as approver_name 
         FROM leaveform l left join
         employee e ON e.emp_id = l.emp_id WHERE l.plant_id = '".$_GET["plant_id"]."' and e.plant_id = '".$_GET["plant_id"]."'  order by l.id desc";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "HOgetLeaveLog") {
        $output = array();
         $sql = "SELECT l.*,e.firstname as fnempname,e.lastname as lnempname,
         (select CONCAT(firstname, ' ', lastname) from employee where emp_id = l.hod_approvar AND plant_id = '".$_GET["plantID"]."') as approver_name 
         FROM leaveform l left join
         employee e ON e.emp_id = l.emp_id WHERE l.plant_id = '".$_GET["plantID"]."'  order by l.id desc";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedLeaveLog") {
        $output = array();
         $sql = "SELECT l.*,e.firstname as fnempname,e.lastname as lnempname,
         (select CONCAT(firstname, ' ', lastname) from employee where emp_id = l.hod_approvar AND plant_id = '".$_GET["plant_id"]."') as approver_name 
         FROM leaveform l left join
         employee e ON e.emp_id = l.emp_id WHERE  l.plant_id = '".$_GET["plant_id"]."'  order by l.id desc";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getLeaveData") {
        
        $currentDate = date('Y-m-d');
        $last7DaysDate = date('Y-m-d', strtotime('-7 days'));
        $lastMonthDate = date('Y-m-d', strtotime('-30 days'));
        
       $output = array(
            "last_7_days" => 0,
            "current_day" => 0,
            "last_month" => 0
        );


$sqlLast7Days = "SELECT COUNT(*) as count FROM leaveform  
                 WHERE status = 'approve' 
                 AND plant_id = '".$_GET["plant_id"]."' 
                 AND (leave_from BETWEEN '$last7DaysDate' AND '$currentDate'
                      OR leave_to BETWEEN '$last7DaysDate' AND '$currentDate'
                      OR (leave_from <= '$currentDate' AND leave_to >= '$currentDate'))";

// Count leaves for the current day
$sqlCurrentDay = "SELECT COUNT(*) as count FROM leaveform  
                  WHERE status = 'approve' 
                  AND plant_id = '".$_GET["plant_id"]."'
                  AND ('$currentDate' BETWEEN leave_from AND leave_to)";

// Count leaves for the last one month
$sqlLastMonth = "SELECT COUNT(*) as count FROM leaveform  
                 WHERE status = 'approve' 
                 AND plant_id = '".$_GET["plant_id"]."'
                 AND (leave_from BETWEEN '$lastMonthDate' AND '$currentDate'
                      OR leave_to BETWEEN '$lastMonthDate' AND '$currentDate'
                      OR (leave_from <= '$currentDate' AND leave_to >= '$currentDate'))";
       
      // Query for last 7 days count
$result = $conn->query($sqlLast7Days);
if ($result && $row = $result->fetch_assoc()) {
    $output['last_7_days'] = $row['count'];
}

// Query for current day count
$result = $conn->query($sqlCurrentDay);
if ($result && $row = $result->fetch_assoc()) {
    $output['current_day'] = $row['count'];
}

// Query for last month count
$result = $conn->query($sqlLastMonth);
if ($result && $row = $result->fetch_assoc()) {
    $output['last_month'] = $row['count'];
}

        echo json_encode($output);
    }
    else if ($_GET["type"] == "HOgetLeaveData") {
        
        $currentDate = date('Y-m-d');
        $last7DaysDate = date('Y-m-d', strtotime('-7 days'));
        $lastMonthDate = date('Y-m-d', strtotime('-30 days'));
        
       $output = array(
            "last_7_days" => 0,
            "current_day" => 0,
            "last_month" => 0
        );


$sqlLast7Days = "SELECT COUNT(*) as count FROM leaveform  
                 WHERE status = 'approve' 
                 AND plant_id = '".$_GET["plantID"]."' 
                 AND (leave_from BETWEEN '$last7DaysDate' AND '$currentDate'
                      OR leave_to BETWEEN '$last7DaysDate' AND '$currentDate'
                      OR (leave_from <= '$currentDate' AND leave_to >= '$currentDate'))";

// Count leaves for the current day
$sqlCurrentDay = "SELECT COUNT(*) as count FROM leaveform  
                  WHERE status = 'approve' 
                  AND plant_id = '".$_GET["plantID"]."'
                  AND ('$currentDate' BETWEEN leave_from AND leave_to)";

// Count leaves for the last one month
$sqlLastMonth = "SELECT COUNT(*) as count FROM leaveform  
                 WHERE status = 'approve' 
                 AND plant_id = '".$_GET["plantID"]."'
                 AND (leave_from BETWEEN '$lastMonthDate' AND '$currentDate'
                      OR leave_to BETWEEN '$lastMonthDate' AND '$currentDate'
                      OR (leave_from <= '$currentDate' AND leave_to >= '$currentDate'))";
       
      // Query for last 7 days count
$result = $conn->query($sqlLast7Days);
if ($result && $row = $result->fetch_assoc()) {
    $output['last_7_days'] = $row['count'];
}

// Query for current day count
$result = $conn->query($sqlCurrentDay);
if ($result && $row = $result->fetch_assoc()) {
    $output['current_day'] = $row['count'];
}

// Query for last month count
$result = $conn->query($sqlLastMonth);
if ($result && $row = $result->fetch_assoc()) {
    $output['last_month'] = $row['count'];
}

        echo json_encode($output);
    }
    else if ($_GET["type"] == "chargeacceptanceLog") {
     $output = array();
        $sql = "SELECT l.*,e.firstname as afirstname,e.lastname as alastname ,
          (SELECT e.firstname FROM employee e WHERE e.emp_id=l.charge_handover ) as fnchange_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.charge_handover ) as lnchange_hand,
        (SELECT e.firstname FROM employee e WHERE e.emp_id=l.alternate_person ) as fnalt_change_hand,
        (SELECT e.lastname FROM employee e WHERE e.emp_id=l.alternate_person ) as lnalt_change_hand
        FROM leaveform l left join employee e ON e.emp_id = l.emp_id
        WHERE l.status !='pending' ";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
    else if ($_GET["type"] == "leave_status") {
     $output = array();
        $sql = "SELECT l.*,e.firstname as afirstname,e.lastname as alastname FROM leaveform l left join employee e ON e.emp_id = l.emp_id where e.emp_id='".$_GET["emp_id"]."' and l.plant_id='".$_GET["plant_id"]."' and e.plant_id='".$_GET["plant_id"]."'";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
    else if ($_GET["type"] == "leave_statusForDept") {
     $output = array();
        $sql = "SELECT l.*,e.firstname as afirstname,e.lastname as alastname FROM leaveform l left join 
        employee e ON e.emp_id = l.emp_id where l.department_name='".$_GET["departmentName"]."'   and l.plant_id='".$_GET["plant_id"]."' and e.plant_id='".$_GET["plant_id"]."'";
        
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
    else if ($_GET["type"] == "update_charge") {
           $sql = "UPDATE leaveform SET charge_status = '".$_GET["status"]."', charge_accepted_by = '".$_GET["emp_id"]."' 
        WHERE  id = '".$_GET["lid"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getweekoff") {
         $output = array();
         $from_date=$_GET['start'];
         $to_date=$_GET['too'];
         $EMP_ID=$_GET['EMP_ID'];
         $sql = "SELECT 
                        COALESCE(SUM(count), 0) AS total_count
                    FROM (
                        SELECT 
                            CASE
                                WHEN weekly_off = DAYNAME(date) THEN 1
                                ELSE 0
                            END AS count
                        FROM shift_allocation_view
                        WHERE date BETWEEN '$from_date' AND '$to_date'
                          AND Empolyee_Id = '$EMP_ID'
                    ) AS counted_days; ";  
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
    else if ($_GET["type"] == "getbalanceleave") {
         $output = array();
         $sql = "SELECT * from leave_card where emp_id= '".$_GET["empid1"]."' AND plant_id  = '".$_GET["plant_id"]."' ";  
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
    else if ($_GET["type"] == "getHandover") {
        $output = array();
        $sql = "SELECT * FROM leaveform WHERE charge_handover='".$_GET["emp_id"]."'";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["pendingList"] = json_decode($row["pendingList"]);
            //$row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAlternate") {
        $output = array();
        $sql = "SELECT * FROM leaveform WHERE alternate_person='".$_GET["emp_id"]."'";
        //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["pendingList"] = json_decode($row["pendingList"]);
            //$row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadLeaveLog") {
        $_GET['filename'] = 'Leave Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Leave Record</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr</td>
                    <td style="width: 20%;">Department</td>
                    <td style="width: 20%;">Leave From</td>
                    <td style="width: 20%;">Leave To</td>
                    <td style="width: 20%;">Leave Type</td>
                    <td style="width: 15%;">Status</td>
                   
                </tr>
            </thead>';
             $i=1;
        $sql = "SELECT * FROM leaveform WHERE status !='pending'";
    //   echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0){  
            $i = 1;
            while($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 20%;">'.$row['department_name'].'</td>
                        <td style="width: 20%;">'.$row['leave_from'].'</td>
                        <td style="width: 20%;">'.$row['leave_to'].'</td>
                        <td style="width: 20%;">'.$row['leave_type'].'</td>
                        <td style="width: 15%;">'.$row['status'].'</td>
                       
                    </tr>';
                $i++;
            }
        }
       
             $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Leave Record.pdf', 'I');
    }

}

$conn->close();
?>