<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);


   require '../db.php';
    require '../token.php';
 //  require 'db.php';


date_default_timezone_set("Asia/Kolkata");
$timestamp = time();
$entry_date = date("Y-m-d", $timestamp);
$day_of_week = date("l", $timestamp);
$entry_time = date("H:i", $timestamp);

$timestamp22 = strtotime($entry_date);
$yesterday = date('Y-m-d', $timestamp22 - 86400);

header('Content-Type: application/json'); // Ensure JSON response

$input = json_decode(file_get_contents('php://input'),true);

    
    
    
    
    $emp_id = $_GET['emp_id1'];
    
    
    
    
    // Selecting status from the attendance table for the last entry
      $sql = "SELECT status FROM attendence WHERE emp_id = '".$emp_id."' and indate='$entry_date' ORDER BY id DESC LIMIT 1";

    $result = $conn->query($sql);
       if ($result->num_rows == 0) {
  
        
        
        
        
        
        
        
        
              $sql00 = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '$entry_date' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$emp_id."' and a.hr_status='Approved' AND a.dept_head_status='Approved'";
          $result00 = $conn->query($sql00);
        if($result00->num_rows > 0) {
           $Jadu=1;
        }else{
           $Jadu=0;
        }
        
          
        
          $sql = "SELECT * FROM attendence WHERE emp_id='".$emp_id."' AND status='pending' AND Date(indate)= '$entry_date'";

        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            echo "{\"status\":\"failed\"}";
        }
        else {
            if($Jadu==1){
              $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '$entry_date' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$emp_id."'";
            }else if($Jadu==0){
              $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '$entry_date' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$emp_id."'";
            }
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
        //   echo($row['weekly_off']);
                
                
                
                
                   $start_time = new DateTime($row["start_time"]);
            $intime = new DateTime($row["intime"]);
            $time_difference = $start_time->diff($intime);
            $minutes_difference = $time_difference->format('%i');
           
           if ($intime < $start_time) {
            // Employee arrived early
            $early_time = $time_difference->format('%H : %i : %s ');
        } elseif ($intime > $start_time) {
            // Employee arrived late
            $late_time = $time_difference->format('%H : %i : %s ');
                        if ($minutes_difference > 20) {
                                    $latemark = 1;
                                } else {
                                    $latemark = 0;
                                }
        }
        
        if($row['weekly_off']==$day_of_week){
            $week=1;
        }else{
            $week=0;
        }
                 $shift=$row['a_Shift_Id'];
                // $output[] = $row;
                
                
                $sql22="SELECT * FROM `attendence` where emp_id='".$emp_id."' and outtime='' and indate='".$yesterday."'";
                 	$result222 = $conn->query($sql22);
    	if($result222->num_rows > 0){
    	    $row222 = $result222->fetch_assoc();
    	    
    	  
    	  
    	  
    	  
    	  
    	  $indate =$row222['indate'];
    	  $intime =$row222['intime'];
 
$datetime = $indate . ' ' . $intime;
 $current_date_time = new DateTime();
    $specific_date_time = new DateTime($datetime);

    // Calculate the difference
    $interval = $current_date_time->diff($specific_date_time);

    // Convert the difference to total hours
    $total_hours = $interval->days * 24 + $interval->h + $interval->i / 60;

    // Separate the integer part and the fractional part
    $hours = floor($total_hours);
    $late_time_components = explode(':', $late_time);
$hours_difference = $late_time_components[0];
// echo '6545646565====='.$hours_difference;

     if ($hours_difference < 07) {
     echo "Error: The time difference less 7 hours.";
     echo  $sql = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark','$week')";
   if($conn->query($sql)) {    
                // echo "{\"status\":\"success\"}";
                                       $query = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
        $result03 = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result03) > 0) {
            $employee = mysqli_fetch_assoc($result03);
            echo json_encode([
                "message" => "Entry time recorded for $emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                 
            ]);
        }
            } else {
                echo "{\"status\":\"failed\"}";
            }
    
//  echo('hiiiiiiiiiiiiiiiii'); 
            exit;
           
}else{
      
    //  echo "Error: The time difference more 7 hours.";
        $sql = "INSERT INTO attendence (emp_id,indate,outtime,outentry_by,shift,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$week')";
   if($conn->query($sql)) {    
                // echo "{\"status\":\"success\"}";
                                       $query = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
        $result03 = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result03) > 0) {
            $employee = mysqli_fetch_assoc($result03);
            echo json_encode([
                "message" => "Entry time recorded for $emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                 
            ]);
        }
            } else {
                echo "{\"status\":\"failed\"}";
            }
    
//  echo('hiiiiiiiiiiiiiiiii'); 
            exit;
           

}
    	  
    if ($hours > 16) {
    // echo "Error: The time difference exceeds 16 hours.";
                             $sql = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark','$week')";
   if($conn->query($sql)) {    
                // echo "{\"status\":\"success\"}";
                                       $query = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
        $result03 = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result03) > 0) {
            $employee = mysqli_fetch_assoc($result03);
            echo json_encode([
                "message" => "Entry time recorded for $emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                 
            ]);
        }
            } else {
                echo "{\"status\":\"failed\"}";
            }
            //  echo('Biiiiiiiiiiiiiiiii'); 

            exit;
}



    	   
    	   //  echo "{\"status\":\"Success\"}";
    	                          $query = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
        $result03 = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result03) > 0) {
            $employee = mysqli_fetch_assoc($result03);
            echo json_encode([
                "message" => "Entry time recorded for $emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                 
            ]);
        }
    	         $sql = "UPDATE attendence SET work_hrs='$hours',outdate='".$entry_date."',outtime='".$entry_time."',outentry_by='".$_GET["emp_id"]."', status='exit' WHERE emp_id ='".$_GET["emp_id"]."' AND status='pending'"; 

    	}else{
    	    
    	    
    	     $indate =$row222['indate'];
    	  $intime =$row222['intime'];
 
$datetime = $indate . ' ' . $intime;
 $current_date_time = new DateTime();
    $specific_date_time = new DateTime($datetime);

    // Calculate the difference
    $interval = $current_date_time->diff($specific_date_time);

    // Convert the difference to total hours
    $total_hours = $interval->days * 24 + $interval->h + $interval->i / 60;

    // Separate the integer part and the fractional part
    $hours = floor($total_hours);
    $late_time_components = explode(':', $late_time);
$hours_difference = $late_time_components[0];
// echo '6545646565====='.$hours_difference;
    	    
    	    
    	     if ($hours_difference < 07) {
                   $sql = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark','$week')";
        echo('kiiiiiiiiiiiiiiiii'); 
    	     }
    	}
                
                
      
      
      
      
           if($conn->query($sql)) {    
                // echo "{\"status\":\"success\"}";
                                       $query = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
        $result03 = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result03) > 0) {
            $employee = mysqli_fetch_assoc($result03);
            echo json_encode([
                "message" => "Entry time recorded for $emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                 
            ]);
        }
            } else {
                echo "{\"status\":\"failed\"}";
            }
                
                
            }
              
        }
            
            

        
        }
        
        
        
        

  } else {
        
    
    
    
    
    
    
        

   $sql00 = "SELECT *, a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a
          LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
          WHERE '$entry_date' BETWEEN a.start_date AND a.end_date 
          AND a.Empolyee_Id = '".$emp_id."'
          AND a.hr_status = 'Approved' 
          AND a.dept_head_status = 'Approved'";

$result00 = $conn->query($sql00);

if ($result00->num_rows > 0) {
    $Jadu = 11;
               $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a left join shift_schedule b on a.Shift_Id=b.id left join attendence c on c.emp_id=a.Empolyee_Id  WHERE '$entry_date' BETWEEN a.start_date AND a.end_date and c.indate='$entry_date' and a.Empolyee_Id='".$emp_id."'";

} else {
    $Jadu = 0;
                $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_allocation a left join shift_schedule b on a.Shift_Id=b.id left join
         attendence c on c.emp_id=a.Empolyee_Id  WHERE '$entry_date' BETWEEN a.start_date AND a.end_date 
         and c.indate='$entry_date' and a.Empolyee_Id='".$emp_id."'";

}
 
            
         $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
               $time1 = new DateTime($row["intime"]);
            $time2 = new DateTime($row["outtime"]);
            $interval = $time1->diff($time2);
            $row['wh'] = $interval->format('%H:%I');
           
      
                 $shift=$row['a_Shift_Id'];
                // $output[] = $row;
         $sql = "UPDATE attendence SET work_hrs='".$row['wh']."',outdate='".$entry_date."',outtime='".$entry_time."',outentry_by='".$_GET["emp_id"]."', status='exit' WHERE emp_id ='".$_GET["emp_id"]."' AND status='pending'"; 
              if($conn->query($sql)) {  
                       $query = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
        $result03 = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result03) > 0) {
            $employee = mysqli_fetch_assoc($result03);
            echo json_encode([
                "message" => "Entry time recorded for $emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                 
            ]);
        }
                 
                  
                  
                // echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
                
                
            }
              
        }
            
            
     
           
    
    }




?>
