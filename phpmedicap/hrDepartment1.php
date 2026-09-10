<?php
    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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
    
   if($_GET["type"]=="get_att_data") {
    $output = Array();
	$sql = "SELECT * FROM machine_attendance WHERE status='at matchine attendance'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 

else if($_GET["type"]=="intimeEmployee") {
    
    
    // $sql="";
    
    
    $emp_id = $_GET['emp_id'];
    
       $sql = "SELECT status FROM attendence WHERE emp_id = '".$emp_id."' and indate='$entry_date' ORDER BY id DESC LIMIT 1";

    $result = $conn->query($sql);
       if ($result->num_rows == 0) {
  
        
        
        $sql00 = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '$entry_date' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$emp_id."' and a.hr_status='Approved' AND a.dept_head_status='Approved'";
         $result00 = $conn->query($sql00);
        if($result00->num_rows > 0) {
                                    // echo "{\"status\":\"Shift Is  Present\"}";

           $Jadu=1;
        }else{
                        // echo "{\"status\":\"Shift Is Not Present\"}";

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

     if ($hours_difference < 07) {
    //  echo "Error: The time difference less 7 hours.";
       $sql = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark','$week')";
   if($conn->query($sql)) {    
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
    
             exit;
           
}else{
      
         $sql = "INSERT INTO attendence (emp_id,indate,outtime,outentry_by,shift,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$week')";
   $conn->query($sql);
 
            exit;
           

}
    	  
    if ($hours > 16) {
    // echo "Error: The time difference exceeds 16 hours.";
                             $sql = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,week_off) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark','$week')";
   $conn->query($sql);
 

            exit;
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

    	    
    	     if ($hours_difference < 07) {
                   $sql = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,week_off,plant_id) VALUES ('".$emp_id."','".$entry_date."','".$entry_time."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark','$week','".$_GET["plant_id"]."')";
    	     }
    	}
                
                
      
      
      
      
           $conn->query($sql);
 
                
                
            }
                                 
                                 


echo json_encode([
    "status" => true,
    "message" => "shift is present",
    "details" => "You are $late_time minutes late"
]);
              
        }else{
   echo json_encode([
    "status" => false,
    "message" => "Shift Is Not Present",
   
]);
                                  
                                    

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
           $sql = "UPDATE attendence SET work_hrs='".$row['wh']."',outdate='".$entry_date."',outtime='".$entry_time."',outentry_by='".$_GET["emp_id"]."', status='exit' WHERE emp_id ='".$_GET["emp_id"]."' AND status='pending'"; 
              if($conn->query($sql)) {    
                 
                    echo json_encode([
    "status" => true,
    "message" => "your work Hours are '".$row['wh']."' m t",
   
]);
                 
                 
                 
               
            } else {
                 echo json_encode([
    "status" => false,
    "message" => "your work Hours are ".$row['wh']." m t",
   
]);
              
            }
                
                
            }
              
        }
            
            
     
            
           
    
    }

}







else if($_GET["type"]=="getEmployeeMonthSalary") {
//          ini_set('display_errors', 1);
// error_reporting(E_ALL);
    if($_GET['plant_id']=='96' || $_GET['plant_id']=='84'){
       
        
   $date = $_GET['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
    $output = Array(); 
          
               $sql="SELECT   a.plant_id, a.branch,    a.isAppointment,      a.operator_category,      a.id,      a.emp_id,      department,      designation,      CONCAT(firstname, ' ', middlename, ' ', lastname) AS emp_name,      b.take_home_salary,     b.gross_salary,     b.total_deductions,     b.status AS status,     b.salary_annexure_id FROM 
    employee a 
LEFT JOIN 
    attendence c ON c.emp_id = a.emp_id 
JOIN 
    shift_schedule s ON c.shift = s.id 
LEFT JOIN 
    (
        SELECT 
            emp_id,     take_home_salary,     gross_salary,     total_deductions,     status,     id AS salary_annexure_id FROM 
            salary_annexure         where status='pending'        ORDER BY             id DESC     ) b ON b.emp_id = a.emp_id  
            WHERE     c.send_to_clearance = '".$_GET['status']."' AND a.plant_id = '".$_GET["plant_id"]."'  AND  c.indate   Like'%".$year.'-'.$month."%'    and (a.emp_id like '%".$_GET["empid"]."%'  or a.firstname like '%".$_GET["empid"]."%' or a.lastname like '%".$_GET["empid"]."%')
GROUP BY  a.plant_id,  a.branch,  a.isAppointment,  a.id,  a.emp_id,  department,  designation,  emp_name,  take_home_salary,  gross_salary,  total_deductions,  status,  operator_category, 
    salary_annexure_id";
   
    
    
    $result = $conn->query($sql);
     if ($result->num_rows > 0) {
        while($rowx = $result->fetch_assoc()) {
            $row=[];
            $pkg=$rowx["gross_salary"]*12;
            $row['emp_id']=$rowx["emp_id"];
            $row['emp_name']=$rowx["emp_name"];
            $row['department']=$rowx["department"];
            $row['designation']=$rowx["designation"];
            $row['take_home_salary']=$rowx["take_home_salary"];
            $row['salary_slip_id']=$rowx["salary_slip_id"];
             $row['total_deductions']=$rowx["deduction"];
             $row['operator_category']=$rowx["operator_category"];
             $row['work_hrs']=$rowx["work_hrs"];
             $row['gross_salary11']=$rowx["gross_salary"];
             $row['salary_annexure_id']=$rowx["salary_annexure_id"];
             $row['branch']=$rowx["branch"];

             
        // ///////////////////////////////////////////////////////////////////////////
        
        
        
        
        
        
$sql2222 = "SELECT * FROM salary_annexure a 
            LEFT JOIN salary_annexure_details b 
            ON a.id=b.salary_annexure_id 
            WHERE a.emp_id='".$row["emp_id"]."' 
            AND b.salary_group='earnings' 
            AND (b.description LIKE '%Basic Salary%' OR b.description LIKE '%Da%')";

$result2222 = $conn->query($sql2222);
$totalPerMonthded = 0;
$tax1 = 0;

if ($result2222->num_rows > 0) {
    while ($row21222 = $result2222->fetch_assoc()) {
        $tax1 += $row21222["per_month"];
    }
    $row['basic_da'] = $tax1;
}
$sql2222 = "SELECT * FROM employee_tds a
            WHERE a.emp_id='".$row["emp_id"]."'  ORDER BY a.id DESC limit 1";
          

$result2222 = $conn->query($sql2222);
if ($result2222->num_rows > 0) {
    while ($row21222 = $result2222->fetch_assoc()) {
        $row['emp_tds'] = $row21222['monthly_tds_old'];
        
        // echo $row['emp_tds'];
    }
   
}else{
     $row['emp_tds'] =0;
}

  
                $sql2 = "select * from monthly_leave_card where month='$month' and  year='$year' and emp_id='".$row['emp_id']."' limit 1";
            //   $sql2 = "SELECT COALESCE(SUM(no_day), 0) AS no_day 
            //                         FROM leaveform 
            //                         WHERE   leave_from like '%".$year.'-'.$month."%' and emp_id='".$row['emp_id']."'";
                                  
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["leaves_of_month"]= $row2["leave_taken"];
                        $row["last_month_total_leave"]= $row2["last_month_total_leave"];
                        $leaves0=$row["last_month_total_leave"]-$row["leaves_of_month"];
                        $leaves1 = abs($leaves0);
                        
                        if($leaves0 >=0){
                            $Bleave='No Deduction';
                            // echo '1';
                        }else{
                              $Bleave='$Bleave';
                                // echo '2';
                        }
                        
                    }
                } else {
                    $days = 0;
                      $row["leaves_of_month"] = 0;
                }
                
                ////////////////////////////////////////
                
                
              $sql2 = "SELECT SUM(a.monthly_emi) as EMI
                                FROM emp_loan a 
                                WHERE 
                                    a.emp_id = '".$row['emp_id']."' 
                                    AND '".$year."-".$month."-01'  BETWEEN a.emi_start_from AND a.emi_end
                                ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        if($row2["EMI"]!=''){
                            $row["EMI"]= $row2["EMI"];
                        }else{
                            $row["EMI"]= 0;
                        }
                        
                    }
                }  else{
                     $row["EMI"]= 0;
                }
        
        
     
        
        
         
             
            //  /////////////////////////////////////////////////
              $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $row['month_days'] =$month_days; 
             
                  $output1234 = Array();
                  
                  $sql2="SELECT SUM(CASE
                                   WHEN count_of_2hrs_each_in_hours = '01' THEN 0
                                   ELSE count_of_2hrs_each_in_hours
                               END) AS total_count_of_2hrs_each
                    FROM (
                        SELECT TIME_FORMAT(TIMEDIFF(a.outtime, b.end_time), '%H') AS count_of_2hrs_each_in_hours
                        FROM attendence a 
                        LEFT JOIN shift_schedule b ON a.shift = b.id where
                          a.emp_id = '".$row['emp_id']."' 
                                                            AND SUBSTRING(a.indate, 6, 2) = '$month'
                                                            AND SUBSTRING(a.indate, 1, 4) = '$year'
                    ) AS subquery";
                  
                  
        
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    
                    while ($row2 = $result2->fetch_assoc()) {
         if ($month >= '08' && $year >= '2024') {
         
                    if($pkg > 400000){
                               $row['total'] =0;
                               $row['ot_total']=0;
                           
                        }
                        else{
                             $row['total'] =$row2['total_count_of_2hrs_each'];
                             
                        
                        if($rowx["operator_category"]=='Staff'){
                            
                           
                            // if ($row['total'] > 8) {
                                    $row['ot_total'] = $row['total'] * 1.5;
                                    if($row['ot_total'] > 0){
                                        $row['ot_total']=$row['ot_total'];
                                    }else{
                                        $row['ot_total']=0;
                                    }
                                // }else{
                                //     $row['ot_total']=0;
                                // }
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                             
                            //  if ($row['total'] > 11) {
                                     $row['ot_total']=$row['total'] * 1.5;
                                      if($row['ot_total'] > 0){
                                        $row['ot_total']=$row['ot_total'];
                                    }else{
                                        $row['ot_total']=0;
                                    }
                                // }else{
                                //      $row['ot_total']=0;
                                // }
                             
                        }  
                        }
        
        
        
        
    } else {
        
                             $row['total'] =$row2['total_count_of_2hrs_each'];
                        
                        if($rowx["operator_category"]=='Staff'){
                           
                        
                            // if ($row['total'] > 8) {
                                    $row['ot_total'] = $row['total'] * 1.5;
                                     if($row['ot_total'] > 0){
                                        $row['ot_total']=$row['ot_total'];
                                    }else{
                                        $row['ot_total']=0;
                                    }
                                // }else{
                                //      $row['ot_total']=0;
                                // }
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                            
                            //  if ($row['total'] > 11) {
                                     $row['ot_total']=$row['total'] * 1.5;
                                      if($row['ot_total'] > 0){
                                        $row['ot_total']=$row['ot_total'];
                                    }else{
                                        $row['ot_total']=0;
                                    }
                                // }else{
                                //      $row['ot_total']=0;
                                // }
                             
                        }  
                        }
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
            
                     
                         
                    }
                } else {
                    $row['total'] = 0;
                    $row['ot_total'] = 0;
                }
                
                
                
                
                
                
                
                  $sql2 = "SELECT * from leave_card WHERE emp_id='".$rowx["emp_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                        $row['bal_leav'] = $row2["leave_balance"];
                        
                    }
                } else {
                    $row['bal_leav'] = 0;
                        
                }
                
                $sql22 = "SELECT * FROM employee_tds a   WHERE a.emp_id='".$rowx["id"]."'  order by id desc limit 1";
                 $result22 = $conn->query($sql22);
                   $totalPerMonthded = 0;
                 if ($result22->num_rows > 0) {
                     while ($row212 = $result22->fetch_assoc()) {
                        if($row212['regime_type']=='Old Regime'){
                            $tds=$row212['monthly_old'];
                        }else{
                            $tds=$row212['monthly_new'];
                        }
                         
                     }
                 }
                 
                $row['tds']=$tds;
                
                
                
                
                
                
                
                
                
                
                
                
                $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                 $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                 $row['working_days'] =$working_days;
                 
               
                $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$rowx["emp_id"]."' AND leave_type='Short Leave' AND status='pending'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['shortleave'] = $row2["shortleave"];
                    }
                } else {
                    $shortleave = 0;
                }
                 $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                 $sql2 = "SELECT COUNT(id) as halfday 
         FROM leaveform 
         WHERE emp_id='".$row["emp_id"]."' 
         AND LOWER(leave_type) = LOWER('Half Day') 
         AND status='approve' 
         AND leave_from LIKE '%".$year."-".$month."%'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['halfday'] = $row2["halfday"];
                    }
                } else {
                    $row['halfday'] = 0;
                }
        
                // $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowx["emp_id"]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                  $sql2 = "SELECT COALESCE(SUM(no_day), 0) AS total_leave 
                                    FROM leaveform 
                                    WHERE  leave_from like '%".$year.'-'.$month."%' and emp_id='".$row['emp_id']."' AND leave_type NOT IN ('Short Leave', 'Half Day')
                ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['total_leave'] = $row2["total_leave"];
                    }
                } else {
                    $row['total_leave'] = 0;
                }
              
                 $row['weekly_off1']=$row['month_days']-$row['working_days'];
                
                $month1=$month+1;
                
                   $sql2holiday = "SELECT COUNT(DISTINCT holiday_date) as holidays FROM mst_holidays WHERE holiday_date like '%$year-$month%' ";
                
                $result2holiday = $conn->query($sql2holiday);
                if ($result2holiday->num_rows > 0) {
                    while ($row2holiday = $result2holiday->fetch_assoc()) {
                        $row['holidays']=$row2holiday['holidays'];
                    }
                }
           
           
//                 ini_set('display_errors', 1);
// error_reporting(E_ALL);
                 $emp_id = $row['emp_id'];

 $sql_week = "SELECT   a.emp_id,   a.joining_date,   b.Shift_Id,   b.Start_date,   b.End_date,   b.weekly_off     FROM   employee a     LEFT JOIN  
shift_allocation b   ON a.emp_id = b.Empolyee_Id     LEFT JOIN   shift_schedule c   ON b.Shift_Id = c.id     WHERE   a.emp_id = '$emp_id'  
AND a.joining_date <= LAST_DAY('$year-$month-01')  AND b.Start_date LIKE '%$year-$month%'   AND (b.End_date LIKE '%$year-$month%' OR b.End_date LIKE '%$year-$month1%')
AND b.Start_date >= a.joining_date";

$result_week = $conn->query($sql_week);

$week_off_count = 0;
if ($result_week->num_rows > 0) {
    while ($row_week = $result_week->fetch_assoc()) {
        $week_off_count++;
    }
}
                
                   $row['weekoof']=$week_off_count;
                
                
                
                
                
                
                
                
                
                  $sql2 = "SELECT COUNT(DISTINCT indate) as present_days1 FROM attendence WHERE emp_id='".$rowx["emp_id"]."'
                         and month(indate)=$month and year(indate)=$year  and (intime!='' and outtime!='') ";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                        
                        
                        
                        
                        
                        
                        
                        // if($row['bal_leav'] > 0){
                            
                        // // $row['present_days'] = $row['working_days'];
                        //     $row2['present_days1'] = $row2['present_days1']+$row['total_leave']+$row['holidays'];
                        //      if($row2['present_days1'] >= $row['working_days'] ){
                        //         $row['present_days']=$row['working_days'] ;
                        //      }else{
                        //         $row['present_days']=$row2['present_days1']+$row['total_leave']+$row['holidays'] ;
                        //      }
                        //   }
                          
                          
                          
                        if($Bleave=='No Deduction'){
                            
                     
                            $row2['present_days1'] = $row2['present_days1']+$row["leaves_of_month"]+$row['holidays'];
                             if($row2['present_days1'] >= $row['working_days'] ){
                                $row['present_days']=$row['working_days'] ;
                             }else{
                                $row['present_days']=$row2['present_days1']+$row['leaves_of_month']+$row['holidays'] ;
                             }
                             
                             
                             
                            //  echo '1';
                          }
                          
                                              
                         
                         
                          else{
                            //  echo '2'; 
                              $row['present_days']=$row2['present_days1']+$row['holidays'] +$row["last_month_total_leave"];
                              
                              
                        // $row['present_days1'] = $row2['present_days1'];
                        //      if($row['present_days1'] > $row['month_days'] or $row['present_days1'] >= $row['working_days'] ){
                        //           $row['present_days']=$row['working_days'];
                        //       }else{
                        //             $row['present_days'] = $row2['present_days1']+$row['holidays'];
        
                        //             }
                        }
                    }
                } else {
                    $row['present_days'] = 0;
                }
                
               
                    if($row['weekoof']!=0){
                        // $row['weekly_off']=$row['weekoof'];
                                                $row['weekly_off']= $row['weekly_off1'];

                    }else{
                        // $row['weekly_off']= $row['weekoof'];
                        $row['weekly_off']= $row['weekly_off1'];
                    }
                
                
                
                
               
               
             
                $paid_days = $row['present_days']*1 + $row['leaves_of_month']*1 ;
                $row['paid_days'] = $paid_days;
                
                $total_salary_days = $paid_days+$working_days;
            //   $row['absent_days'] = $row['working_days'] - $row['present_days'] -$row['bal_leav'];
            
            
                // $row['absent_days'] =   $row['working_days']+$row['total_leave']  - $row['present_days']  ;
                $row['absent_days1'] =   $row['month_days']-$row['present_days']  - $row['weekly_off']  ;
                if( $row['absent_days1'] <0){
                     $row['absent_days']=0;
                }else{
                     $row['absent_days']= $row['absent_days1'];
                }

                
                $earnings =0;
                $deductions=0;
                $earningsList= Array();
                $deductionsList= Array();
                 $sql2 = "select * from salary_annexure_details WHERE salary_annexure_id='".$rowx["salary_annexure_id"]."' order by salary_group ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row21 = $result2->fetch_assoc()) 
                        {
                            $temp=[];
                             $temp1=[];
                          if($row21['salary_group']!='Earnings'){        
                               $deductionsList[$row21['description']]  = $row21["per_month"];
                            //   $deductions=$deductions+ $row21["per_month"];
                            $deductions = $deductions + (float)$row21["per_month"];
                          }else{
                              $amount = (($row21["per_month"]));
                            //   $amount = (($row21["per_month"]/$working_days)*$paid_days);
                               $earningsList[$row21['description']]  = number_format((float)$amount, 2, '.', '');
                              $earnings=$earnings+(number_format((float)$amount, 2, '.', ''));
                          }
                     
                        
                        }
              
                } 
                
                
                                           
                  $calc_pf_day= $row['basic_da']/$row['month_days'];
              $prent_day=$row['month_days']-$row['absent_days'];
              $pf_amt=$prent_day*$calc_pf_day;
              
              

if (preg_match('/NPF/', $row['branch'])) {
    $pf = 'Not Applicable';
} else {
    $pf = 'Applicable';
}




if( $pf == 'Not Applicable'){
    $tax=0;
} else {
    if($pf_amt > 15000){
         $tax=1800;
     } else {
         $tax=($pf_amt * 12)/100;
     }
}

                  
              $row['pf']=$pf;
              $row['pf_amt']=$pf_amt;
              $row['pfff']=$tax;
            //   $row['ded']=$tax+$row['emp_tds'];
            $tax = (float)$tax;
            $emp_tds = (float)$row['emp_tds'];
            $row['ded'] = $tax + $emp_tds;

              
                                 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                
                 
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] = (float)$row['pfff'] + (float)$row["EMI"] + (float)$row['emp_tds']; // + (float)$row['pf'] + (float)$row['esic'] + (float)$row['p_tax'] + (float)$row['canteen'] + (float)$row['other'];

                // $row['deduction'] =$row['pfff']+$row["EMI"]+$row['emp_tds'];// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                // $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                // $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $output[] = $row;
          
        }
    }
    echo json_encode($output);

    }
  else{
       
        
   $date = $_GET['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
    $output = Array(); 
          
              $sql="SELECT   a.plant_id, a.branch,    a.isAppointment,      a.operator_category,      a.id,      a.emp_id,      department,      designation,      CONCAT(firstname, ' ', middlename, ' ', lastname) AS emp_name,      b.take_home_salary,     b.gross_salary,     b.total_deductions,     b.status AS status,     b.salary_annexure_id FROM 
    employee a 
LEFT JOIN 
    attendence c ON c.emp_id = a.emp_id 
JOIN 
    shift_schedule s ON c.shift = s.id 
LEFT JOIN 
    (
        SELECT 
            emp_id,     take_home_salary,     gross_salary,     total_deductions,     status,     id AS salary_annexure_id FROM 
            salary_annexure         where status='pending'        ORDER BY             id DESC     ) b ON b.emp_id = a.emp_id  
            WHERE     c.send_to_clearance = '".$_GET['status']."' AND a.plant_id = '".$_GET["plant_id"]."'  AND  c.indate   Like'%".$year.'-'.$month."%'    and (a.emp_id like '%".$_GET["empid"]."%'  or a.firstname like '%".$_GET["empid"]."%' or a.lastname like '%".$_GET["empid"]."%')
GROUP BY  a.plant_id,  a.branch,  a.isAppointment,  a.id,  a.emp_id,  department,  designation,  emp_name,  take_home_salary,  gross_salary,  total_deductions,  status,  operator_category, 
    salary_annexure_id";
    //               echo             $sql="SELECT
                          
    //                         a.plant_id,
    //                         a.isAppointment,
    //                         a.operator_category,
    //                         a.id,
    //                         a.emp_id,
    //                         department,
    //                         designation,
    //                         CONCAT(firstname, ' ', middlename, ' ', lastname) AS emp_name,
    //                         b.take_home_salary,
    //                         b.gross_salary,
    //                         b.total_deductions,
    //                         b.status,
    //                         b.id AS salary_annexure_id
                          
    //                     FROM
    //                         employee a
    //                     JOIN
    //                         salary_annexure b ON a.emp_id = b.emp_id
    //                     LEFT JOIN
    //                         attendence c ON c.emp_id = a.emp_id
                           
    // JOIN shift_schedule s ON c.shift = s.id
    //                     WHERE c.send_to_clearance= '".$_GET["status"]."' and
    //                         c.indate  Like'%".$year.'-'.$month."%'  group by a.plant_id, a.isAppointment, a.id, a.emp_id,
    //                         department, designation,emp_name, b.take_home_salary, b.gross_salary, b.total_deductions, b.status,a.operator_category,b.id ";
                              
    
    
    
    $result = $conn->query($sql);
     if ($result->num_rows > 0) {
        while($rowx = $result->fetch_assoc()) {
            $row=[];
            $pkg=$rowx["gross_salary"]*12;
            $row['emp_id']=$rowx["emp_id"];
            $row['emp_name']=$rowx["emp_name"];
            $row['department']=$rowx["department"];
            $row['designation']=$rowx["designation"];
            $row['take_home_salary']=$rowx["take_home_salary"];
            $row['salary_slip_id']=$rowx["salary_slip_id"];
             $row['total_deductions']=$rowx["deduction"];
             $row['operator_category']=$rowx["operator_category"];
             $row['work_hrs']=$rowx["work_hrs"];
             $row['gross_salary11']=$rowx["gross_salary"];
             $row['salary_annexure_id']=$rowx["salary_annexure_id"];
             $row['branch']=$rowx["branch"];

             
        // ///////////////////////////////////////////////////////////////////////////
        
        
        
        
        
        
$sql2222 = "SELECT * FROM salary_annexure a 
            LEFT JOIN salary_annexure_details b 
            ON a.id=b.salary_annexure_id 
            WHERE a.emp_id='".$row["emp_id"]."' 
            AND b.salary_group='earnings' 
            AND (b.description LIKE '%Basic Salary%' OR b.description LIKE '%Da%')";

$result2222 = $conn->query($sql2222);
$totalPerMonthded = 0;
$tax1 = 0;

if ($result2222->num_rows > 0) {
    while ($row21222 = $result2222->fetch_assoc()) {
        $tax1 += $row21222["per_month"];
    }
    $row['basic_da'] = $tax1;
}
$sql2222 = "SELECT * FROM employee_tds a
            WHERE a.emp_id='".$row["emp_id"]."'  ORDER BY a.id DESC limit 1";
          

$result2222 = $conn->query($sql2222);
if ($result2222->num_rows > 0) {
    while ($row21222 = $result2222->fetch_assoc()) {
        $row['emp_tds'] = $row21222['monthly_old'];
    }
   
}else{
     $row['emp_tds'] =0;
}


                                   
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
                $sql2 = "select * from monthly_leave_card where month='$month' and  year='$year' and emp_id='".$row['emp_id']."' limit 1";
            //   $sql2 = "SELECT COALESCE(SUM(no_day), 0) AS no_day 
            //                         FROM leaveform 
            //                         WHERE   leave_from like '%".$year.'-'.$month."%' and emp_id='".$row['emp_id']."'";
                                  
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["leaves_of_month"]= $row2["leave_taken"];
                        $row["last_month_total_leave"]= $row2["last_month_total_leave"];
                        $leaves0=$row["last_month_total_leave"]-$row["leaves_of_month"];
                        $leaves1 = abs($leaves0);
                        
                        if($leaves0 >=0){
                            $Bleave='No Deduction';
                            // echo '1';
                        }else{
                              $Bleave='$Bleave';
                                // echo '2';
                        }
                        
                    }
                } else {
                    $days = 0;
                      $row["leaves_of_month"] = 0;
                }
                
                ////////////////////////////////////////
                
                
              $sql2 = "SELECT SUM(a.monthly_emi) as EMI
                                FROM emp_loan a 
                                WHERE 
                                    a.emp_id = '".$row['emp_id']."' 
                                    AND '".$year."-".$month."-01'  BETWEEN a.emi_start_from AND a.emi_end
                                ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        if($row2["EMI"]!=''){
                            $row["EMI"]= $row2["EMI"];
                        }else{
                            $row["EMI"]= 0;
                        }
                        
                    }
                }  else{
                     $row["EMI"]= 0;
                }
        
        
     
        
        
         
             
            //  /////////////////////////////////////////////////
              $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $row['month_days'] =$month_days; 
             
                  $output1234 = Array();
                  
                  $sql2="SELECT SUM(CASE
                                   WHEN count_of_2hrs_each_in_hours = '01' THEN 0
                                   ELSE count_of_2hrs_each_in_hours
                               END) AS total_count_of_2hrs_each
                    FROM (
                        SELECT TIME_FORMAT(TIMEDIFF(a.outtime, b.end_time), '%H') AS count_of_2hrs_each_in_hours
                        FROM attendence a 
                        LEFT JOIN shift_schedule b ON a.shift = b.id where
                          a.emp_id = '".$row['emp_id']."' 
                                                            AND SUBSTRING(a.indate, 6, 2) = '$month'
                                                            AND SUBSTRING(a.indate, 1, 4) = '$year'
                    ) AS subquery";
                  
                  
        
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    
                    while ($row2 = $result2->fetch_assoc()) {
         if ($month >= '08' && $year >= '2024') {
         
                    if($pkg > 400000){
                               $row['total'] =0;
                               $row['ot_total']=0;
                           
                        }
                        else{
                             $row['total'] =$row2['total_count_of_2hrs_each'];
                             
                        
                        if($rowx["operator_category"]=='Staff'){
                            
                           
                            // if ($row['total'] > 8) {
                                    $row['ot_total'] = $row['total'] * 1.5;
                                // }else{
                                //     $row['ot_total']=0;
                                // }
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                             
                            //  if ($row['total'] > 11) {
                                     $row['ot_total']=$row['total'] * 1;
                                // }else{
                                //      $row['ot_total']=0;
                                // }
                             
                        }  
                        }
        
        
        
        
    } else {
        
                             $row['total'] =$row2['total_count_of_2hrs_each'];
                        
                        if($rowx["operator_category"]=='Staff'){
                           
                        
                            // if ($row['total'] > 8) {
                                    $row['ot_total'] = $row['total'] * 1.5;
                                // }else{
                                //      $row['ot_total']=0;
                                // }
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                            
                            //  if ($row['total'] > 11) {
                                     $row['ot_total']=$row['total'] * 1;
                                // }else{
                                //      $row['ot_total']=0;
                                // }
                             
                        }  
                        }
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
            
                     
                         
                    }
                } else {
                    $row['total'] = 0;
                    $row['ot_total'] = 0;
                }
                
                
                
                
                
                
                
                  $sql2 = "SELECT * from leave_card WHERE emp_id='".$rowx["emp_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                        $row['bal_leav'] = $row2["leave_balance"];
                        
                    }
                } else {
                    $row['bal_leav'] = 0;
                        
                }
                
                $sql22 = "SELECT * FROM employee_tds a   WHERE a.emp_id='".$rowx["id"]."'  order by id desc limit 1";
                 $result22 = $conn->query($sql22);
                   $totalPerMonthded = 0;
                 if ($result22->num_rows > 0) {
                     while ($row212 = $result22->fetch_assoc()) {
                        if($row212['regime_type']=='Old Regime'){
                            $tds=$row212['monthly_old'];
                        }else{
                            $tds=$row212['monthly_new'];
                        }
                         
                     }
                 }
                 
                $row['tds']=$tds;
                
                
                
                
                
                
                
                
                
                
                
                
                $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                 $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                 $row['working_days'] =$working_days;
                 
               
                $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$rowx["emp_id"]."' AND leave_type='Short Leave' AND status='pending'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['shortleave'] = $row2["shortleave"];
                    }
                } else {
                    $shortleave = 0;
                }
                 $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                 $sql2 = "SELECT COUNT(id) as halfday 
         FROM leaveform 
         WHERE emp_id='".$row["emp_id"]."' 
         AND LOWER(leave_type) = LOWER('Half Day') 
         AND status='approve' 
         AND leave_from LIKE '%".$year."-".$month."%'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['halfday'] = $row2["halfday"];
                    }
                } else {
                    $row['halfday'] = 0;
                }
        
                // $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowx["emp_id"]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                  $sql2 = "SELECT COALESCE(SUM(no_day), 0) AS total_leave 
                                    FROM leaveform 
                                    WHERE  leave_from like '%".$year.'-'.$month."%' and emp_id='".$row['emp_id']."' AND leave_type NOT IN ('Short Leave', 'Half Day')
                ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['total_leave'] = $row2["total_leave"];
                    }
                } else {
                    $row['total_leave'] = 0;
                }
              
                 $row['weekly_off1']=$row['month_days']-$row['working_days'];
                
                $month1=$month+1;
                
                   $sql2holiday = "SELECT COUNT(DISTINCT holiday_date) as holidays FROM mst_holidays WHERE holiday_date like '%$year-$month%' ";
                
                $result2holiday = $conn->query($sql2holiday);
                if ($result2holiday->num_rows > 0) {
                    while ($row2holiday = $result2holiday->fetch_assoc()) {
                        $row['holidays']=$row2holiday['holidays'];
                    }
                }
           
           
//                 ini_set('display_errors', 1);
// error_reporting(E_ALL);
                 $emp_id = $row['emp_id'];

 $sql_week = "SELECT   a.emp_id,   a.joining_date,   b.Shift_Id,   b.Start_date,   b.End_date,   b.weekly_off     FROM   employee a     LEFT JOIN  
shift_allocation b   ON a.emp_id = b.Empolyee_Id     LEFT JOIN   shift_schedule c   ON b.Shift_Id = c.id     WHERE   a.emp_id = '$emp_id'  
AND a.joining_date <= LAST_DAY('$year-$month-01')  AND b.Start_date LIKE '%$year-$month%'   AND (b.End_date LIKE '%$year-$month%' OR b.End_date LIKE '%$year-$month1%')
AND b.Start_date >= a.joining_date";

$result_week = $conn->query($sql_week);

$week_off_count = 0;
if ($result_week->num_rows > 0) {
    while ($row_week = $result_week->fetch_assoc()) {
        $week_off_count++;
    }
}
                
                   $row['weekoof']=$week_off_count;
                
                
                
                
                
                
                
                
                
                  $sql2 = "SELECT COUNT(DISTINCT indate) as present_days1 FROM attendence WHERE emp_id='".$rowx["emp_id"]."'
                         and month(indate)=$month and year(indate)=$year  and (intime!='' and outtime!='') ";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                        
                        
                        
                        
                        
                        
                        
                        // if($row['bal_leav'] > 0){
                            
                        // // $row['present_days'] = $row['working_days'];
                        //     $row2['present_days1'] = $row2['present_days1']+$row['total_leave']+$row['holidays'];
                        //      if($row2['present_days1'] >= $row['working_days'] ){
                        //         $row['present_days']=$row['working_days'] ;
                        //      }else{
                        //         $row['present_days']=$row2['present_days1']+$row['total_leave']+$row['holidays'] ;
                        //      }
                        //   }
                          
                          
                          
                        if($Bleave=='No Deduction'){
                            
                     
                            $row2['present_days1'] = $row2['present_days1']+$row["leaves_of_month"]+$row['holidays'];
                             if($row2['present_days1'] >= $row['working_days'] ){
                                $row['present_days']=$row['working_days'] ;
                             }else{
                                $row['present_days']=$row2['present_days1']+$row['leaves_of_month']+$row['holidays'] ;
                             }
                             
                             
                             
                            //  echo '1';
                          }
                          
                                              
                         
                         
                          else{
                            //  echo '2'; 
                              $row['present_days']=$row2['present_days1']+$row['holidays'] +$row["last_month_total_leave"];
                              
                              
                        // $row['present_days1'] = $row2['present_days1'];
                        //      if($row['present_days1'] > $row['month_days'] or $row['present_days1'] >= $row['working_days'] ){
                        //           $row['present_days']=$row['working_days'];
                        //       }else{
                        //             $row['present_days'] = $row2['present_days1']+$row['holidays'];
        
                        //             }
                        }
                    }
                } else {
                    $row['present_days'] = 0;
                }
                
               
                    if($row['weekoof']!=0){
                        // $row['weekly_off']=$row['weekoof'];
                                                $row['weekly_off']= $row['weekly_off1'];

                    }else{
                        // $row['weekly_off']= $row['weekoof'];
                        $row['weekly_off']= $row['weekly_off1'];
                    }
                
                
                
                
               
               
             
                $paid_days = $row['present_days']*1 + $row['leaves_of_month']*1 ;
                $row['paid_days'] = $paid_days;
                
                $total_salary_days = $paid_days+$working_days;
            //   $row['absent_days'] = $row['working_days'] - $row['present_days'] -$row['bal_leav'];
            
            
                // $row['absent_days'] =   $row['working_days']+$row['total_leave']  - $row['present_days']  ;
                $row['absent_days1'] =   $row['month_days']-$row['present_days']  - $row['weekly_off']  ;
                if( $row['absent_days1'] <0){
                     $row['absent_days']=0;
                }else{
                     $row['absent_days']= $row['absent_days1'];
                }

                
                $earnings =0;
                $deductions=0;
                $earningsList= Array();
                $deductionsList= Array();
                 $sql2 = "select * from salary_annexure_details WHERE salary_annexure_id='".$rowx["salary_annexure_id"]."' order by salary_group ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row21 = $result2->fetch_assoc()) 
                        {
                            $temp=[];
                             $temp1=[];
                          if($row21['salary_group']!='Earnings'){        
                               $deductionsList[$row21['description']]  = $row21["per_month"];
                            //   $deductions=$deductions+ $row21["per_month"];
                            $deductions = $deductions + (float)$row21["per_month"];
                          }else{
                              $amount = (($row21["per_month"]));
                            //   $amount = (($row21["per_month"]/$working_days)*$paid_days);
                               $earningsList[$row21['description']]  = number_format((float)$amount, 2, '.', '');
                              $earnings=$earnings+(number_format((float)$amount, 2, '.', ''));
                          }
                     
                        
                        }
              
                } 
                
                
                                           
                  $calc_pf_day= $row['basic_da']/$row['month_days'];
              $prent_day=$row['month_days']-$row['absent_days'];
              $pf_amt=$prent_day*$calc_pf_day;
              
              

if (preg_match('/NPF/', $row['branch'])) {
    $pf = 'Not Applicable';
} else {
    $pf = 'Applicable';
}




if( $pf == 'Not Applicable'){
    $tax=0;
} else {
    if($pf_amt > 15000){
         $tax=1800;
     } else {
         $tax=($pf_amt * 12)/100;
     }
}

                  
              $row['pf']=$pf;
              $row['pf_amt']=$pf_amt;
              $row['pfff']=$tax;
            //   $row['ded']=$tax+$row['emp_tds'];
            $tax = (float)$tax;
            $emp_tds = (float)$row['emp_tds'];
            $row['ded'] = $tax + $emp_tds;

              
                                 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                
                 
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] = (float)$row['pfff'] + (float)$row["EMI"] + (float)$row['emp_tds']; // + (float)$row['pf'] + (float)$row['esic'] + (float)$row['p_tax'] + (float)$row['canteen'] + (float)$row['other'];

                // $row['deduction'] =$row['pfff']+$row["EMI"]+$row['emp_tds'];// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                // $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                // $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $output[] = $row;
          
        }
    }
    echo json_encode($output);

    }
   
    
  
}



























else if($_GET["type"]=="getApprovedDepartments") {
    $output = array();
	$sql = "SELECT * FROM department WHERE status='active' ORDER by department_name ASC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getDesignations"){
    $output = Array();
	$sql = "SELECT * FROM designation";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row["responsibilities"] = json_decode($row["responsibilities"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getPendingDesignations"){
	$sql = "SELECT * FROM designation WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedDesignations") {
    $output = array();
	$sql = "SELECT * FROM designation WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="newDesignation") {
    $sql = "INSERT INTO designation (designation, responsibilities, entry_by, entry_date, status) VALUES ('".$input["designation"]."', '".json_encode($input["responsibilities"])."','".$_GET["emp_id"]."','".$entry_date."', 'active')";
    if($conn->query($sql)){
    	echo "{\"status\":\"success\"}";
    } else {
    	echo "{\"status\":\"failed\"}";
    }
}
else if($_GET["type"]=="updateDesignation"){
	$sql = "UPDATE designation SET status='active',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		$sql = "UPDATE pendingdocument SET status='checked' WHERE entry_id='".$_GET["id"]."' AND formname='Designation Master'";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
} else if($_GET["type"]=="getQualifications"){
	$sql = "SELECT * FROM qualification";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedQualifications"){
	$sql = "SELECT * FROM qualification WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingQualifications"){
	$sql = "SELECT * FROM qualification WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addQualification"){
	$sql = "INSERT INTO qualification (qualification,entry_by,entry_date, status) VALUES ('".$input["qualification"]."','".$_GET["emp_id"]."','".$entry_date."', 'active')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	} else {
	    echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updateQualification"){
	$sql = "UPDATE qualification SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		$sql = "UPDATE pendingdocument SET status='checked' WHERE entry_id='".$_GET["id"]."' AND formname='Qualification Master'";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getSections"){
	$sql = "SELECT * FROM section";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedSections"){
	$sql = "SELECT * FROM section WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingSections"){
	$sql = "SELECT * FROM section WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getDepartmentSection"){
	$sql = "SELECT * FROM section WHERE status='active' and department='".$_GET["selectedDepartment"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getselectedDepartmentSection"){
	$sql = "SELECT * FROM section WHERE status='active' and department='".$_GET["selectedDepartment"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addSection"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO section (section_name,department,section_code,building_no,room_no,entry_by,entry_date) VALUES ('".$input["section_name"]."','".$input["department"]."','".$input["section_code"]."','".$input["building_no"]."','".$input["room_no"]."','".$_GET["emp_id"]."','$entry_date')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Section Master','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getEmploymentAgencies"){
	$sql = "SELECT * FROM employmentagency ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addEmployeeAgency"){
	$input = json_decode(file_get_contents('php://input'),true);
	$entry_id = "";
	$sql = "INSERT INTO employmentagency (agency_name,address,contact_no,email,contact_person,entry_by,entry_date) VALUES ('".$input["agency_name"]."','".$input["address"]."','".$input["contact_no"]."','".$input["email"]."','".$input["contact_person"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "SELECT * FROM employmentagency ORDER BY id DESC";
		$result = $conn->query($sql);
		$output = Array();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$output[] = $row;
			}
			echo json_encode($output);
		}
		else {
			echo "[]";
		}
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Employment Agency','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
	}
}
else if($_GET["type"]=="updateAgencyStatus") {
	$sql = "UPDATE employmentagency SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	$conn->query($sql);

	$sql = "UPDATE pendingdocument SET status='checked' WHERE entry_id='".$_GET["id"]."' AND formname='Employment Agency' AND purpose='Approval' AND department='".$_GET["department"]."'";
	$conn->query($sql);
}
else if($_GET["type"]=="getPhisicians"){
	$sql = "SELECT * FROM phisicians";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedPhisicians"){
	$sql = "SELECT * FROM phisicians WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingPhisicians"){
	$sql = "SELECT * FROM phisicians WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addPhisician"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO phisicians (doctor_name,qualification,clinic_address,address,contact_no,email_id,entry_by,entry_date) VALUES ('".$input["doctor_name"]."','".$input["qualification"]."','".$input["clinic_address"]."','".$input["address"]."','".$input["contact_no"]."','".$input["email"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Phisicians Master','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getGovagencys"){
	$sql = "SELECT * FROM govagency ORDER BY ID DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedGovagencys"){
	$sql = "SELECT * FROM govagency WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingGovagencys"){
	$sql = "SELECT * FROM govagency WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addGovagency"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO govagency (authority_name,address,office_name,phone_no,email_id,entry_by,entry_date) VALUES ('".$input["authority_name"]."','".$input["address"]."','".$input["office_name"]."','".$input["phone_no"]."','".$input["email_id"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getLocations"){
	$sql = "SELECT * FROM location";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedLocations"){
	$sql = "SELECT * FROM location WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingLocations"){
	$sql = "SELECT * FROM location WHERE status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addLocation"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO location (place,district,state,entry_by,entry_date) VALUES ('".$input["place"]."','".$input["district"]."','".$input["state"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Location Master','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="save_employee_salary") {
        $sql ="INSERT INTO emp_salary_details(plant_id, month, year, emp_code, salary_details, entry_by, entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["month"]."','".$input["year"]."','".$input["emp_id"]."',
        '".json_encode($input["salary_info"])."','".$_GET["emp_id"]."','".$entry_date."')";
      
        if($conn->query($sql)===TRUE){
		    echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
}
else if($_GET["type"]=="save_employee_salary") {
        $sql ="INSERT INTO emp_salary_details(plant_id, month, year, emp_code, salary_details, entry_by, entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["month"]."','".$input["year"]."','".$input["emp_id"]."',
        '".json_encode($input["salary_info"])."','".$_GET["emp_id"]."','".$entry_date."')";
      
        if($conn->query($sql)===TRUE){
		    echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
}
else if($_GET["type"]=="update_salary_clearance_info") {
        $sql ="Update emp_salary_details set send_to_clearance ='Yes' where send_to_clearance='No'
        and month='".$input["month"]."' and year = '".$input["year"]."' ";
        
        if($conn->query($sql)===TRUE){
		    echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
}
else if($_GET["type"]=="getCandidateFormNo"){
	$sql = "SELECT IFNULL(max(id),1) as id FROM candidate LIMIT 1";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$id = $row["id"] + 1;
            echo "{\"form_no\":\"".$id."\"}";
            break;
		}
	}
	else {
		echo "{\"form_no\":\"1\"}";
	}
}
else if($_GET["type"]=="getPendingCandidates"){
	$sql = "SELECT * FROM candidate WHERE status='pending' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getCandidates"){
	$sql = "SELECT * FROM candidate WHERE status='active' AND isScheduleInteview='No' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingCandidates"){
	$sql = "SELECT * FROM candidate WHERE status='pending' AND isScheduleInteview='No' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getScheduledCandidates"){
	$sql = "SELECT * FROM interviewers WHERE employee='".$_GET["emp_id"]."' AND emp_status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$sql1 = "SELECT * FROM candidate WHERE id='".$row["candidate_id"]."'";
			$result1 = $conn->query($sql1);
			if($result->num_rows > 0){
				while($row1 = $result1->fetch_assoc()){
					$output[] = $row1;
				}
			}
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingAcceptanceCandidates"){
	$sql = "SELECT * FROM candidate WHERE isInterviewCompleted='Yes' AND dept_remark='selected' AND isemployee='No' AND offeracceptance=''";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedAcceptanceCandidates"){
	$sql = "SELECT * FROM candidate WHERE offeracceptance='yes' AND medicalcheckup='yes' AND isemployee='No' AND ischeckup='No'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="offerAcceptance"){
	$input = json_decode(file_get_contents('php://input'),true);
	if(!isset($input["medicalcheckup"])) {
	    $input["medicalcheckup"] = "";
	}
	if($input["offeracceptance"] == "yes") {
	    if($input["medicalcheckup"]=="yes") {
	        $sql = "UPDATE candidate SET offeracceptance='".$input["offeracceptance"]."',medicalcheckup='".$input["medicalcheckup"]."',tentative_joining_date='".$input["tentative_joining_date"]."' WHERE id='".$input["candidate"]."'";
	    } else {
	        $sql = "UPDATE candidate SET offeracceptance='".$input["offeracceptance"]."',medicalcheckup='".$input["medicalcheckup"]."',tentative_joining_date='".$input["tentative_joining_date"]."',checkup_status='yes' WHERE id='".$input["candidate"]."'";
	    }
	    
	} else {
	    $sql = "UPDATE candidate SET offeracceptance='".$input["offeracceptance"]."',status='rejected' WHERE id='".$input["candidate"]."'";
	}
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="addCandiate"){
    
    
    
    
    $sql = "SELECT * FROM candidate WHERE mobile_no='".$input["mobile_no"]."' OR email_id='".$input["email_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo "{\"status\":\"Candiate Already Exit in List\"}";
    } else {
        
         
        $sql = "INSERT INTO candidate (candidate_name,qualification,place,mobile_no,email_id,register_by,registration_date,gender) VALUES ('".$input["candidate_name"]."','".$input["qualification"]."','".$input["address"]."','".$input["mobile_no"]."','".$input["email_id"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["gender"]."')";
    	if($conn->query($sql)===TRUE){
    		$entry_id = $conn->insert_id;
    		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Candiate Master','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
    		$conn->query($sql);
    		echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
}
else if($_GET["type"]=="getApprovedEmployees"){
	$sql = "SELECT * FROM employee WHERE status='active' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getEmployees"){
	$sql = "SELECT * FROM employee ORDER BY id ASC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $row["photo"] = "http://paperlessgmp.com/gmptotal/upload/".$row["photo"];
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getEmployeeDetails") {
	$sql = "SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $row["photo"] = "http://paperlessgmp.com/gmptotal/upload/".$row["photo"];
		    $sql1 = "SELECT * FROM shift WHERE emp_id='".$row["emp_id"]."' AND status='active' ORDER BY id DESC";
        	$result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["shift"] = $row1["shift"];
        		    $sql2 = "SELECT * FROM shiftschedule WHERE shift_name='".$row["shift"]."'";
        		    $result2 = $conn->query($sql2);
                	if($result2->num_rows > 0){
                		while($row2 = $result2->fetch_assoc()){
                		    $row["in_time"] = $row2["in_time"];
                		    $row["out_time"] = $row2["out_time"];
                		}
                	}
        		}
        	} else {
        	    $row["shift"] = "";
        	}
			echo json_encode($row);
		}
	} else {
	    echo "{}";
	}
}
else if($_GET["type"]=="getNoRoleEmployee"){
	$sql = "SELECT * FROM employee WHERE status='active' AND role_status='No' AND designation NOT IN ('Manager', 'Assistant Manager') ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingAppointmentLetters"){
	$sql = "SELECT * FROM employee WHERE isAppointment='pending' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getEmployeeShiftInfo"){
    $sql = "SELECT emp_id,emp_name,department, designation FROM employee WHERE status='active'";
    $result = $conn->query($sql);
    $output = Array();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
        	$sql1 = "SELECT * FROM shift WHERE emp_id='".$row["emp_id"]."' AND status='active' ORDER BY id DESC";
        	$result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["shift"] = $row1["shift"];
        		    $sql2 = "SELECT * FROM shiftschedule WHERE shift_name='".$row["shift"]."'";
        		    $result2 = $conn->query($sql2);
                	if($result2->num_rows > 0){
                		while($row2 = $result2->fetch_assoc()){
                		    $row["in_time"] = $row2["in_time"];
                		    $row["out_time"] = $row2["out_time"];
                		}
                	}
        		}
        	}
        	$output[] = $row;
        }
    }
    echo json_encode($output);
}
else if($_GET["type"]=="getDeptEmployees"){
	$sql = "SELECT * FROM employee WHERE status='active' AND department='".$_GET["fromdept"]."' AND designation='".$_GET["designation"]."' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getEmployeesByDepartment"){
    $date_now = date("Y-m-d");
	$sql = "SELECT * FROM employee WHERE status='active'  AND emp_id !='".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
	}
	echo json_encode($output);
}

else if($_GET["type"]=="getReportingEmployees"){
    $date_now = date("Y-m-d");
	$sql = "SELECT emp_id,designation,emp_name FROM employee WHERE status='active' AND reporting_to='".$_GET["emp_id"]."' AND emp_id != '".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM shift WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    if($date_now >= $row1["todate"]) {
        		        $output[] = $row;
        		    }
        		}
        	} else {
        	    $output[] = $row;
        	}
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getEmployeeLeaveStatus"){
	$totalcasual = 0;
	$totalsick = 0;
	$totalpl = 0;
	$totalother = 0;
	$totaltaken = 0;
	$totaltakencasual = 0;
	$totaltakensick = 0;
	$totaltakenpl = 0;
	$totaltakenother = 0;
	$sql = "SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$totalcasual = $row["casual"];
			$totalsick = $row["sick"];
			$totalpl = $row["pl"];
			$totalother = $row["other"];
		}
	}

	$sql = "SELECT * FROM leave_application WHERE emp_id='".$_GET["emp_id"]."' AND status='approved'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$totalcasual = $row["casual"];
			$totalsick = $row["sick"];
			$totalpl = $row["pl"];
			$totalother = $row["other"];
		}
	}

}
else if($_GET["type"]=="getEmployeeLeaves"){
	$sql = "SELECT * FROM leave_application WHERE emp_id='".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="submitLeaveRequest"){
	if(isset($_FILES["document"]["name"])){
	    $target_dir = "upload/";
		$target_file = $target_dir."".$_GET["emp_id"]."-leave-".basename($_FILES["document"]["name"]);
		$document = $_GET["emp_id"]."-leave-".basename($_FILES["document"]["name"]);
		$file1 = basename($_FILES["document"]["name"]);
		move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
	}
	$sql = "INSERT INTO leave_application (emp_id,emp_dept,leave_type,leavefrom,leaveto,emergency_contact,reason,document,chargeto,chargedesc, entry_date) VALUES ('".$_GET["emp_id"]."','".$_GET["department"]."','".$_POST["leave_type"]."','".$_POST["leavefrom"]."','".$_POST["leaveto"]."','".$_POST["emergency_contact"]."','".$_POST["reason"]."','".$document."','".$_POST["chargeto"]."','".$_POST["chargedesc"]."','$entry_date')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Leave Application','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="saveCurrentEmployee"){
    $input = $_POST;
	$target_dir = "upload/";
	$emp_id = "";
	$_POST["user"]= "false";
	$_POST["checker"]= "false";
	$_POST["approver"]= "false";

	$password = dec_enc('encrypt', $input["password"]);

	if($_POST["telephone"]=="true"){
		$_POST["telephone"]=true;
	}
	if($_POST["transport"]=="true"){
		$_POST["transport"]=true;
	}
	if($_POST["cantine"]=="true"){
		$_POST["cantine"]=true;
	}

	$emp_id1;
	$sql = "SELECT MAX(emp_id1) as emp_id FROM employee";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$emp_id1 = $row["emp_id"];
		}
	}
	$emp_id1++;
	$test_id = "";
	if (strlen((string)$emp_id1) >= 3) {
		$test_id = $emp_id1;
	} else if (strlen((string)$emp_id1) == 2) {
		$test_id = "0".$emp_id1;
	} else if (strlen((string)$emp_id1) == 1) {
		$test_id = "00".$emp_id1;
	}
	if($input["department"] == "Human Resource") {
	    $emp_id = "SBHR".$test_id;
	} else if($input["department"] == "Quality Control") {
	    $emp_id = "SBQC".$test_id;
	} else if($input["department"] == "Administrator") {
	    $emp_id = "SBAD".$test_id;
	} else if($input["department"] == "Account") {
	    $emp_id = "SBAC".$test_id;
	} else if($input["department"] == "Security") {
	    $emp_id = "SBSE".$test_id;
	} else if($input["department"] == "Resource") {
	    $emp_id = "SBRE".$test_id;
	} else if($input["department"] == "Purchase") {
	    $emp_id = "SBPR".$test_id;
	} else if($input["department"] == "Production") {
	    $emp_id = "SBPD".$test_id;
	} else if($input["department"] == "Quality Assurance") {
	    $emp_id = "SBQA".$test_id;
	} else if($input["department"] == "Microbiology") {
	    $emp_id = "SBMB".$test_id;
	} else if($input["department"] == "Engineering") {
	    $emp_id = "SBEG".$test_id;
	} else if($input["department"] == "Store") {
	    $emp_id = "SBST".$test_id;
	} else if($input["department"] == "Packing") {
	    $emp_id = "SBPK".$test_id;
	} else if($input["department"] == "Management") {
	    $emp_id = "SBMN".$test_id;
	} else if($input["department"] == "Planning") {
	    $emp_id = "SBPL".$test_id;
	} else if($input["department"] == "Admin") {
	    $emp_id = "SBAD".$test_id;
	} else if($input["department"] == "IPQA") {
	    $emp_id = "SBIP".$test_id;
	} else if($input["department"] == "Marketing") {
	    $emp_id = "SBMR".$test_id;
	}

	if(isset($_FILES["photo"]["name"])) {
    	$target_file = $target_dir."".$emp_id.basename($_FILES["photo"]["name"]);
    	$userphoto = $emp_id.basename($_FILES["photo"]["name"]);
    	$file3 = basename($_FILES["photo"]["name"]);
    	move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
	}
	
	if(isset($_FILES["resume"]["name"])) {
    	$target_file = $target_dir."".$emp_id.basename($_FILES["resume"]["name"]);
    	$userphoto = $emp_id.basename($_FILES["resume"]["name"]);
    	$file3 = basename($_FILES["resume"]["name"]);
    	move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file);
	}
	
	$sql = "INSERT INTO employee (emp_id,emp_name,emp_contact,emp_password,department,section,designation,emp_email,qualification,emp_id1, isuser, ischecker, isapprover, status, details) VALUES ('$emp_id', '".$input["employee_name"]."', '".$input["mobile_no"]."', '$password', '".$input["department"]."', '".$input["section"]."', '".$input["designation"]."', '".$input["email_id"]."', '".$input["qualification"]."',$emp_id1,'".$_POST["user"]."','".$_POST["checker"]."','".$_POST["approver"]."','pending', '".json_encode($input)."')";
	if($conn->query($sql)){
		echo "{\"status\":\"success\",\"emp_id\":\"$emp_id\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="scheduleInterview"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "UPDATE candidate SET schedule_by='".$input["schedule_by"]."',interview_date='".$input["interview_date"]."',interview_time='".$input["interview_time"]."',venue='".$input["venue"]."',contact_person='".$input["contact_person"]."',isScheduleInteview='Yes',department='".$input["department"]."',designation='".$input["designation"]."' WHERE id='".$input["candidate_id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="addInterviewers"){
	$data = json_decode(file_get_contents('php://input'),true);
	$flag = 0;
	$len = count($data);
	for($i = 0; $i<$len; $i++){
		$input = $data[$i];
		$sql = "INSERT INTO interviewers (candidate_id,department,designation,employee) VALUES ('".$_GET["candidate_id"]."','".$input["department"]."','".$input["designation"]."','".$input["employee"]."')";
		if($conn->query($sql) === TRUE) {
			$flag = 1;
		} else {
			$flag = 0;
		}
	}
	if($flag == 1) {
	    $sql = "UPDATE candidate SET interview_allocated='Yes' WHERE id=".$_GET["candidate_id"];
	    $conn->query($sql);
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="interviewform"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "UPDATE interviewers SET college_name='".$input["college_name"]."', passing_year='".$input["passing_year"]."', experience_year='".$input["experience_year"]."', experience_month='".$input["experience_month"]."', key_role='".$input["key_role"]."', no_of_members='".$input["no_of_members"]."', family_occupation='".$input["family_occupation"]."', financial_background='".$input["financial_background"]."', attitude='".$input["attitude"]."', confidence='".$input["confidence"]."', willingness='".$input["willingness"]."', remark='".$input["remark"]."',emp_status='active' WHERE candidate_id='".$input["form_no"]."' AND department= '".$_GET["department"]."' AND employee='".$_GET["emp_id"]."'";
	if($conn->query($sql)===TRUE){
		$id= $conn->insert_id;
		echo "{\"status\":\"success\"}";

		$flag = 0;
		$sql1 = "SELECT * FROM interviewers WHERE candidate_id='".$input["form_no"]."'";
		$result = $conn->query($sql1);
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				if($row["emp_status"]=="pending") {
					$flag = 1;
				}
			}
		}
		if($flag == 0) {
			$sql = "UPDATE candidate SET isInterviewCompleted='Yes' WHERE id='".$input["form_no"]."'";
			$conn->query($sql);
		}
		}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getAwaitingCandidates"){
	$sql = "SELECT * FROM candidate WHERE isInterviewCompleted='Yes' AND dept_status='pending' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getCandidateInterviewers"){
	$sql = "SELECT * FROM interviewers WHERE candidate_id='".$_GET["candidate_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPrimaryRoundCandidates"){
    	$output = Array();
	$sql = "SELECT * FROM candidate WHERE interview_allocated='Yes' and  is_interview_completed='No' order by 1 desc";
	$result = $conn->query($sql);


	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		      $row['resume_path'] = '/upload/candidate/'.$row['resume'];
		    // $row['resume_path'] = '/upload/candidate/'.$row['resume'];
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getWaitiningCandidateInterviewers"){
	$sql = "SELECT * FROM interviewers WHERE employee='".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getSelectedCandidates"){
	$sql = "SELECT * FROM candidate WHERE isInterviewCompleted='Yes' AND dept_status='active' AND dept_remark='selected' AND isemployee='No' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getSelectedCandidates1"){
	$sql = "SELECT * FROM candidate WHERE isInterviewCompleted='Yes' AND dept_status='active' AND isemployee='No' AND isAppintment='pending' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
/* else if($_GET["type"]=="getSalaryAnnexure"){
	$sql = "SELECT * FROM candidate WHERE isInterviewCompleted='Yes' AND dept_remark='selected' AND isSalary='Yes' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT * FROM salary_annexure WHERE emp_id='".$row["id"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["salary"] = $row1;
		            break;
		        }
		    }
			$output[] = $row;
		}
	}
	echo json_encode($output);
} */
else if($_GET["type"]=="updateAwaitingCandidate"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "UPDATE candidate SET dept_status='active',dept_remark='".$input["remark"]."',finaldepartment='".$input["department"]."',finaldesignation='".$input["designation"]."' WHERE id='".$input["candidate_id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getPendingJoiningCandidates"){
	$sql = "SELECT * FROM candidate WHERE dept_status='active' AND isemployee='No' ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT emp_id, designation FROM employee WHERE department='".$row["department"]."' AND status='active'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
		    if($result1->num_rows > 0){
		        while($row1 = $result1->fetch_assoc()){
		            $output1[] = $row1;
		        }
		    }
		    $row["employees"] = $output1;
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="addNewEmployee"){
	$target_dir = "upload/";

	$file1 = "";
	$file2 = "";
	$emp_id;
	$qualification;
	$mobile_no;
	$email_id;
	$interview_date;

	$emp_name = $_POST["employee_name"];
	$department = $_POST["department"];
	$designation = $_POST["designation"];
	$address_permanent = $_POST["address_permanent"];
	$permanant_state = $_POST["permanant_state"];
	$permanant_district = $_POST["permanant_district"];
	$permanant_taluka = $_POST["permanant_taluka"];
	$address_temporary = $_POST["address_temporary"];
	$temporary_state = $_POST["temporary_state"];
	$temporary_district = $_POST["temporary_district"];
	$temporary_taluka = $_POST["temporary_taluka"];
	$joining_date = $_POST["joining_date"];
	$birth_date = $_POST["dob"];
	$gender = $_POST["gender"];
	$emergency_no = $_POST["emergency_no"];
	$casual = $_POST["casual"];
	$sick = $_POST["sick"];
	$pl = $_POST["pl"];
	$other = $_POST["other"];
	$total = $_POST["total"];
	$transport = $_POST["transport"];
	$cantine = $_POST["cantine"];
	$telephone = $_POST["telephone"];
	$resume = "";
	$userphoto = "";
	$training = $_POST["training"];
	$reporting_to = $_POST["reporting_to"];

	$password = dec_enc('encrypt',"123");

	if($_POST["training"]=="Yes"){
		$_POST["training"]=true;
	}
	if($_POST["telephone"]=="true"){
		$_POST["telephone"]=true;
	}
	if($_POST["transport"]=="true"){
		$_POST["transport"]=true;
	}
	if($_POST["cantine"]=="true"){
		$_POST["cantine"]=true;
	}

	$emp_id1;
	$sql = "SELECT MAX(emp_id1) as emp_id FROM employee";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$emp_id1 = $row["emp_id"];
		}
	}
	$emp_id1++;
	$test_id = "";
	if (strlen((string)$emp_id1) >= 3) {
		$test_id = $emp_id1;
	} else if (strlen((string)$emp_id1) == 2) {
		$test_id = "0".$emp_id1;
	} else if (strlen((string)$emp_id1) == 1) {
		$test_id = "00".$emp_id1;
	}
	if($department == "Human Resource") {
	    $emp_id = "SBHR".$test_id;
	} else if($department == "Quality Control") {
	    $emp_id = "SBQC".$test_id;
	} else if($department == "Administrator") {
	    $emp_id = "SBAD".$test_id;
	} else if($department == "Accounts") {
	    $emp_id = "SBAC".$test_id;
	} else if($department == "Security") {
	    $emp_id = "SBSE".$test_id;
	} else if($department == "Resource") {
	    $emp_id = "SBRE".$test_id;
	} else if($department == "Purchase") {
	    $emp_id = "SBPR".$test_id;
	} else if($department == "Production") {
	    $emp_id = "SBPD".$test_id;
	} else if($department == "Quality Assurance") {
	    $emp_id = "SBQA".$test_id;
	} else if($department == "Microbiology") {
	    $emp_id = "SBMB".$test_id;
	} else if($department == "Engineering") {
	    $emp_id = "SBEG".$test_id;
	} else if($department == "Store") {
	    $emp_id = "SBST".$test_id;
	} else if($department == "Packing") {
	    $emp_id = "SBPK".$test_id;
	}

	$sql = "SELECT * FROM candidate WHERE id='".$_POST["candidate_id"]."'";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$qualification = $row["qualification"];
			$mobile_no = $row["mobile_no"];
			$email_id = $row["email_id"];
			$interview_date = $row["interview_date"];
		}
	}

	if(isset($_FILES["resume"]["name"])){
	$target_file = $target_dir."".$emp_id."-resume-".basename($_FILES["resume"]["name"]);
	$resume = $emp_id."-resume-".basename($_FILES["resume"]["name"]);
	$file1 = basename($_FILES["resume"]["name"]);
	move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file);
	}
	if(isset($_FILES["userphoto"]["name"])){
	$target_file = $target_dir."".$emp_id."-userphoto-".basename($_FILES["userphoto"]["name"]);
	$userphoto = $emp_id."-userphoto-".basename($_FILES["userphoto"]["name"]);
	$file3 = basename($_FILES["userphoto"]["name"]);
	move_uploaded_file($_FILES["userphoto"]["tmp_name"], $target_file);
	}

    $password = "ckl4K2FLaG1kNXVmalhiaWJXOFkrUT09";
	$sql = "INSERT INTO employee (emp_id,emp_name,emp_contact,emp_password,department,designation,address_permanent,permanant_state,permanant_district,permanant_taluka,address_temporary,temporary_state,temporary_district,temporary_taluka,birth_date,gender,emp_email,emergency_no,qualification,interview_date,joining_date,casual,sick,pl,other,total,transport,canteen,telephone,resume,induction_training,emp_id1,photo,reporting_to) VALUES ('$emp_id','$emp_name','$mobile_no','$password','$department','$designation','$address_permanent','$permanant_state','$permanant_district','$permanant_taluka','$address_temporary','$temporary_state','$temporary_district','$temporary_taluka','$birth_date','$gender','$email_id','$emergency_no','$qualification','$interview_date','$joining_date','$casual','$sick','$pl','$other','$total','$transport','$cantine','$telephone','$resume','$training','$emp_id1','$userphoto','$reporting_to')";
	if($conn->query($sql)===TRUE){
		$sql = "UPDATE candidate SET isemployee='Yes' WHERE id='".$_POST["candidate_id"]."'";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"An error has occurred, Please try again.\"}";
	}
}
else if($_GET["type"]=="getDeductions"){
	$sql = "SELECT * FROM deduction ORDER BY deduction";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedDeductions"){
	$sql = "SELECT * FROM deduction WHERE status='active' ORDER BY deduction";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="addDeduction"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO deduction (deduction,entry_by,entry_date) VALUES ('".$input["deduction"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Deduction Master','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="updateDeduction"){
	$sql = "UPDATE deduction SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["deduction_id"]."'";
	$conn->query($sql);
	$sql = "SELECT * FROM deduction ORDER BY deduction";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingAttendance"){
	$sql = "SELECT id,emp_id,indate,intime,outdate,outtime,inentry_by,outentry_by,if(outtime='',false,true) as status FROM attendence where status = 'pending'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getAttendence"){
	$sql = "SELECT id,emp_id,indate,intime,outdate,outtime,inentry_by,outentry_by FROM attendence where status = 'pending'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getLabourAttendence"){
	$sql = "SELECT * FROM labour_attendance WHERE DATE(in_time) >= '".$_GET["from_date"]."' AND DATE(in_time) <= '".$_GET["to_date"]."' AND labour_id LIKE'%".$_GET["labour_id"]."%'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql2 = "SELECT * FROM labour WHERE contractor_name LIKE'%".$_GET["contractor"]."%' AND labour_id LIKE'%".$_GET["labour_id"]."%'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while($row2 = $result2->fetch_assoc()) {
		            $row["contractor"] = $row2["contractor_name"];
		            if ($row["out_time"] !== '') {
		                $seconds = strtotime($row["out_time"]) - strtotime($row["in_time"]);
                        $row["total_hours"] = getHoursFormat( $seconds );
                        $row["ot_hours"] = 0;
                        $row["ot_total"] = 0;
		            } else {
		                $row["total_hours"] = 0;
                        $row["ot_hours"] = 0;
                        $row["ot_total"] = 0;
		            }
		            $output[] = $row;
		            break;
		        }
		    }
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="updateLabourAttendence") {
    $input = json_decode(file_get_contents('php://input'),true);
    $temp = explode('/',$input["entry_date"]);
    $entry_date = $temp[2]."-".$temp[1]."-".$temp[0]." ".$input["entry_time"];
    
    $temp = explode('/',$input["out_date"]);
    $out_date = $temp[2]."-".$temp[1]."-".$temp[0]." ".$input["out_time"];
    $sql = "UPDATE labour_attendance SET in_time='".$entry_date."', out_time='".$out_date."', correction_reason='".$input["reason"]."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"successs\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if($_GET["type"]=="updateAttendance"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "UPDATE attendence SET original_intime='".$input["original_intime"]."', original_outtime='".$input["original_outtime"]."', intime='".$input["intime"]."', outtime='".$input["outtime"]."',update_by='".$_GET["emp_id"]."',update_date='".$entry_date."' WHERE id='".$input["id"]."'";
	if($conn->query($sql)===TRUE){	
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="addEmployeeIntime"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "SELECT * FROM attendence WHERE emp_id='".$input["emp_id"]."' AND status='pending' AND outtime=''";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		echo "{\"status\":\"filled\"}";
	}
	else {
    	$sql = "INSERT INTO attendence (emp_id,indate,intime,shift,inentry_by) VALUES ('".$input["emp_id"]."','".$entry_date."','".$entry_date."','".$input["shift"]."','".$_GET["emp_id"]."')";
    	if($conn->query($sql)===TRUE){	
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
    	}
	}
}
else if($_GET["type"]=="addEmployeeOuttime"){
	$sql = "UPDATE attendence SET outdate='".$entry_date."',outtime='".$entry_date."',outentry_by='".$_GET["emp_id"]."' WHERE id ='".$_GET["id"]."'"; 
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	} else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getShifts") {
	$sql = "SELECT emp_id,department,designation FROM employee WHERE status='active'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM shift WHERE emp_id='".$row["emp_id"]."' AND status='active'";
		    $result1 = $conn->query($sql1);
		    if($result1->num_rows > 0){
		        while($row1 = $result1->fetch_assoc()){
		            $row["fromdate"] = $row1["fromdate"];
		            $row["todate"] = $row1["todate"];
		            $row["shift"] = $row1["shift"];
		            $row["isshift"] = "yes";
		        }
		    } else {
		        $row["isshift"] = "no";
		    }
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getShiftsByDepartment") {
	$sql = "SELECT * FROM shift WHERE status='active' AND department='".$_GET["department"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="allocateShift"){
	$input = json_decode(file_get_contents('php://input'),true);
	$employees = $input["employeeshift"];
	foreach ($employees as $key => $value) {
		if($value == "true") {
			$temp = explode("@",$key);
			$sql = "INSERT INTO shift (emp_id,fromdate,todate,shift,entry_by,entry_date) VALUES ('".$temp[0]."','".$input["fromdate"]."','".$input["todate"]."','".$temp[1]."','".$_GET["emp_id"]."','".$entry_date."')";
			$conn->query($sql);
		}
	}
}
else if($_GET["type"]=="fiterShiftData") {
	$input = json_decode(file_get_contents('php://input'),true);
	if($input["employee"]!="" && $input["shift"]=="" && $input["fromdate"]=="" && $input["todate"]=="") {
		$sql = "SELECT * FROM shift WHERE status='active' AND department='".$input["department"]."' AND emp_id='".$input["employee"]."'";
		$result = $conn->query($sql);
		$output = Array();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$output[] = $row;
			}
			echo json_encode($output);
		}
		else {
			echo "[]";
		}
	}
	else if($input["employee"]=="" && $input["shift"]!="" && $input["fromdate"]=="" && $input["todate"]=="") {
		$sql = "SELECT * FROM shift WHERE status='active' AND department='".$input["department"]."' AND shift='".$input["shift"]."'";
		$result = $conn->query($sql);
		$output = Array();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$output[] = $row;
			}
			echo json_encode($output);
		}
		else {
			echo "[]";
		}
	}
	else if($input["employee"]=="" && $input["shift"]=="" && $input["fromdate"]!="" && $input["todate"]!="") {
		$sql = "SELECT * FROM shift WHERE status='active' AND department='".$input["department"]."' AND fromdate between '".$input["fromdate"]."' and '".$input["todate"]."'";
		$result = $conn->query($sql);
		$output = Array();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$output[] = $row;
			}
			echo json_encode($output);
		}
		else {
			echo "[]";
		}
	}
	else {
		$sql = "SELECT * FROM shift WHERE status='active'";
		$result = $conn->query($sql);
		$output = Array();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$output[] = $row;
			}
			echo json_encode($output);
		}
		else {
			echo "[]";
		}
	}
}
else if($_GET["type"]=="getNotices") {
	$sql = "SELECT * FROM notice";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getNoticeById") {
	$sql = "SELECT * FROM notice WHERE entry_by='".$_GET["emp_id"]."'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="sendNoticeRemark"){
	$sql = "UPDATE notice SET remark='".$_GET["remark"]."',status='active',check_by='".$_GET["emp_id"]."',check_date='".$entry_date."' WHERE id='".$_GET["notice_id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="sendNotice"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO notice (emp_id,subject,description,entry_by,entry_dept,entry_date) VALUES ('".$input["employee"]."','".$input["subject"]."','".$input["description"]."','".$_GET["emp_id"]."','".$_GET["department"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		$entry_id = $conn->insert_id;
		$sql = "INSERT INTO pendingdocument (entry_id,formname,purpose,department,entry_by,entry_date) VALUES ('".$entry_id."','Notice','Approval','".$_GET["department"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
/* else if($_GET["type"]=="createSalaryAnnexure"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO salary (emp_id,basic,conveyance,educational_allowance,hra,total_monthly,deduction_pt,net_income,entry_by,entry_date) VALUES ('".$input["candidate"]."','".$input["basic"]."','".$input["conveyance"]."','".$input["educational_allowance"]."','".$input["hra"]."','".$input["total_monthly"]."','".$input["deduction_pt"]."','".$input["net_income"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		$sql1 = "UPDATE candidate SET isSalary='Yes' WHERE id='".$input["candidate"]."'";
		$conn->query($sql1);
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
} */ 
else if($_GET["type"]=="saveSalary") {
    $sql = "INSERT INTO salary_annexure (emp_id, isMetro, isPF, isESIC, CTC, basic, HRA, conveyance, medical, specialallowance, educationalallowance, PF, ESIC, gross, PT, canteen, other, inhand, bonus, entry_by, entry_date) VALUES ('".$input["emp_id"]."','".$input["isMetro"]."','".$input["isPF"]."','".$input["isESIC"]."','".$input["ctc"]."','".$input["basic"]."','".$input["hra"]."','".$input["conveyance"]."','".$input["medical"]."','".$input["specialallowance"]."','".$input["educationalallowance"]."','".$input["pf"]."','".$input["esic"]."','".$input["gross"]."','".$input["pt"]."','".$input["canteen"]."','".$input["other"]."','".$input["inhand"]."','".$input["bonus"]."','".$_GET["emp_id"]."','$entry_date')";
	if ($conn->query($sql) === TRUE) {
	    echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="updateEmployeeSalary") {
    $input = json_decode(file_get_contents('php://input'),true);
    $sql = "INSERT INTO salary_annexure (emp_id, isMetro, isPF, isESIC, CTC, basic, HRA, conveyance, medical, specialallowance, educationalallowance, PF, ESIC, gross_total, PT, canteen, other, net_total, bonus, entry_by, entry_date) VALUES ('".$input["emp_id"]."','".$input["isMetro"]."','".$input["isPF"]."','".$input["isESIC"]."','".$input["ctc"]."','".$input["basic"]."','".$input["hra"]."','".$input["conveyance"]."','".$input["medical"]."','".$input["specialallowance"]."','".$input["educationalallowance"]."','".$input["pf"]."','".$input["esic"]."','".$input["gross_total"]."','".$input["pt"]."','".$input["canteen"]."','".$input["other"]."','".$input["net_total"]."','".$input["bonus"]."','".$_GET["emp_id"]."','$entry_date')";
	if ($conn->query($sql) === TRUE) {
	    echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getLabourDetails") {
	$sql = "SELECT * FROM labour";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getApprovedLabors") {
    $output = Array();
	$sql = "SELECT * FROM labour WHERE status='approve'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="saveLabour"){
	$target_dir = "upload/";
	$file1 = "";
	$file2 = "";
	$aadhar_card = "";
	$photo = "";
	$emp_id;
	$labour_name = $_POST["labour_name"];
	$dob = $_POST["dob"];
	$address = $_POST["address"];
	$contractor_name = $_POST["contractor_name"];
	$category = $_POST["category"];
	$daily_wages = $_POST["daily_wages"];
	$gender = $_POST["gender"];
	
	$sql = "SELECT MAX(id) as labour_id FROM labour";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$emp_id1 = $row["labour_id"];
		}
	}
	$emp_id1++;
	if (strlen((string)$emp_id1)>6) {
		$emp_id = "SBLB-".$emp_id1;
	} else if (strlen((string)$emp_id1) == 5) {
		$emp_id = "SBLB-0".$emp_id1;
	} else if (strlen((string)$emp_id1) == 4) {
		$emp_id = "SBLB-00".$emp_id1;
	} else if (strlen((string)$emp_id1) == 3) {
		$emp_id = "SBLB-000".$emp_id1;
	} else if (strlen((string)$emp_id1) == 2) {
		$emp_id = "SBLB-0000".$emp_id1;
	} else if (strlen((string)$emp_id1) == 1) {
		$emp_id = "SBLB-00000".$emp_id1;
	}


	if(isset($_FILES["aadhar_card"]["name"])){
	$target_file = $target_dir."".$emp_id."-aadhar_card-".basename($_FILES["aadhar_card"]["name"]);
	$aadhar_card = $emp_id."-aadhar_card-".basename($_FILES["aadhar_card"]["name"]);
	$file1 = basename($_FILES["aadhar_card"]["name"]);
	move_uploaded_file($_FILES["aadhar_card"]["tmp_name"], $target_file);
	}
	
	if(isset($_FILES["photo"]["name"])){
	$target_file = $target_dir."".$emp_id."-photo-".basename($_FILES["photo"]["name"]);
	$photo = $emp_id."-photo-".basename($_FILES["photo"]["name"]);
	$file2 = basename($_FILES["photo"]["name"]);
	move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
	}

	$sql = "INSERT INTO labour (labour_id,labour_name,dob,address,contractor_name,daily_wages,gender,scan_upload,photo,category,entry_by,entry_date) VALUES ('$emp_id','$labour_name','$dob','$address','$contractor_name','$daily_wages','$gender','$aadhar_card','$photo','$category','".$_GET["emp_id"]."','$entry_date')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="addMedicalRecord"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "INSERT INTO medicalcheckup (emp_id,phisician_name,General_Checkup,BP,Sugar,ECG,Blood_Test,HB,Eye_Checkup,Color_Blindness,HIV,other_test,entry_by,entry_date) VALUES ('".$input["emp_id"]."','".$input["phisician"]."','".$input["General_Checkup"]."','".$input["BP"]."','".$input["Sugar"]."','".$input["ECG"]."','".$input["Blood_Test"]."','".$input["HB"]."','".$input["Eye_Checkup"]."','".$input["Color_Blindness"]."','".$input["HIV"]."','".$input["other_test"]."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
		$sql1 = "UPDATE candidate SET ischeckup='yes' WHERE id='".$input["emp_id"]."'";
		$conn->query($sql1);
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getLeavesDept"){
	$sql = "SELECT id, emp_id, leave_type, leavefrom, leaveto, emergency_contact, reason, document, chargeto, chargedesc, chargestatus, entry_date, DATEDIFF(leaveto, leavefrom)+1 as no_of_days FROM leave_application WHERE emp_dept='".$_GET["department"]."' AND status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getLeavesReport"){
	$sql = "SELECT id, emp_id, leave_type, leavefrom, leaveto, emergency_contact, reason, document, chargeto, chargedesc, chargestatus, entry_date, DATEDIFF(leaveto, leavefrom)+1 as no_of_days, approve_from, approve_to FROM leave_application WHERE emp_dept='".$_GET["department"]."' AND status<>'pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="updateLeaveApplication"){
    $input = json_decode(file_get_contents('php://input'),true);
	$sql = "UPDATE leave_application SET status='".$input["status"]."',remark='".$input["remark"]."', approve_from='".$input["approve_from"]."', approve_to='".$input["approve_to"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id = '".$input["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updateSections"){
	$sql = "UPDATE section SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		$sql = "UPDATE pendingdocument SET status='checked' WHERE entry_id='".$_GET["id"]."' AND formname='Qualification Master'";
		$conn->query($sql);
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updatePhisicians"){
	$sql = "UPDATE phisicians SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updateLocations"){
	$sql = "UPDATE location SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updateLabours"){
	$sql = "UPDATE labour SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updateGovts"){
	$sql = "UPDATE govagency SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getCandidatesDetails"){
	$sql = "SELECT * FROM candidate WHERE user_no='".$_GET["user_no"]."' ORDER BY id desc";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);} 
else if($_GET["type"]=="saveScheduleInterview") {
    $sql = "UPDATE candidate SET schedule_by='".$_GET["emp_id"]."',interview_date='".$input["interview_date"]."',interview_time='".$input["interview_time"]."',venue='".$input["venue"]."',isScheduleInteview='Yes',department='".$input["department"]."',designation='".$input["designation"]."' WHERE id='".$_GET["candidate_id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="updateCandidates"){
	$sql = "UPDATE candidate SET status='".$_GET["status"]."',approve_by='".$_GET["emp_id"]."',approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="assignRole"){
    $input = json_decode(file_get_contents('php://input'),true);
    if(!isset($input["official_mail"])) {
        $input["official_mail"] = "";
    }
    if(!isset($input["official_phone"])) {
        $input["official_phone"] = "";
    }
    if($input["user"]==true) {
        $input["user"] = "true";
    } else {
        $input["user"] = "false";
    }
    if($input["checker"]==true) {
        $input["checker"] = "true";
    } else {
        $input["checker"] = "false";
    }
    if($input["approver"]==true) {
        $input["approver"] = "true";
    } else {
        $input["approver"] = "false";
    }
	$sql = "UPDATE employee SET role_status='Yes',isUser='".$input["user"]."',isChecker='".$input["checker"]."',isApprover='".$input["approver"]."',office_mail='".$input["official_mail"]."',office_phone='".$input["official_phone"]."' WHERE emp_id='".$input["employee_id"]."'";
	if($conn->query($sql)===TRUE){
	    $sql1 = "";
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="correctionFilter") {
    $sql = "SELECT * FROM attendence WHERE emp_id='".$_GET["employee_id"]."' AND status='pending'";
    $result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getShifts") {
    $sql = "SELECT * FROM shiftschedule WHERE status='active'";
    $result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getCurrentShift") {
    $sql = "SELECT shift FROM shift WHERE status='active' AND todate>=CURDATE() AND emp_id='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			echo "{\"shift\":\"".$row["shift"]."\"}";
		}
	}
	else {
		echo "{\"shift\":\"General\"}";
	}
}
else if($_GET["type"]=="getTodaysAttendance") {
    $sql = "SELECT * FROM attendence WHERE status='pending' AND outtime='' AND emp_id='".$_GET["emp_id"]."' ORDER BY id DESC";
    $result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			echo json_encode($row);
			break;
		}
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="shiftChangeRequest"){
    $input = json_decode(file_get_contents('php://input'),true);
    $reporting_to = "";
    $sql = "SELECT reporting_to FROM employee WHERE emp_id='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $reporting_to = $row["reporting_to"];
		}
	}
	$sql = "INSERT INTO shift_change (emp_id,current_shift,required_shift,from_date,to_date,entry_date,reporting_to,type) VALUES ('".$_GET["emp_id"]."','".$input["current_shift"]."','".$input["required_shift"]."','".$input["from_date"]."','".$input["to_date"]."','$entry_date','$reporting_to',,'".$input["type"]."')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="updateTodaysAttendance"){
    $input = json_decode(file_get_contents('php://input'),true);
    $reporting_to = "";
    $sql = "SELECT reporting_to FROM employee WHERE emp_id='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $reporting_to = $row["reporting_to"];
		}
	}
	$sql = "INSERT INTO shift_change (emp_id,current_shift,required_shift,entry_date,reporting_to,type) VALUES ('".$_GET["emp_id"]."','".$input["shift"]."','".$input["required_shift"]."','$entry_date','$reporting_to','".$input["type"]."')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getActiveLabours") {
    $sql = "SELECT * FROM labour WHERE status='approve' ORDER BY labour_name DESC";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getTodaysLabors") {
    $sql = "SELECT * FROM labour_attendance WHERE status='pending' AND date(in_time)=CURDATE()";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM labour WHERE labour_no='".$row["labour_id"]."'";
		    $result1 = $conn->query($sql1);
            $output1 = Array();
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["labour_no"] = $row1["labour_no"];
        		    $row["labour_name"] = $row1["labour_name"];
        		    $row["category"] = $row1["category"];
        		    break;
        		}
        	}
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="labourEntry"){
	$sql = "INSERT INTO labour_attendance (labour_id, in_time, entry_by, entry_date) VALUES ('".$_GET["labour_id"]."','".$entry_date."','".$_GET["emp_id"]."','".$entry_date."')";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="exitLabour"){
	$sql = "UPDATE labour_attendance SET out_time='".$entry_date."',out_by='".$_GET["emp_id"]."',out_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="assignTask"){
    $input = json_decode(file_get_contents('php://input'),true);
    $data = $input["task"];
	for($i = 0; $i < count($data); $i++) {
	    $sql1 = "INSERT INTO task (emp_id, assign_by, assign_date, task) VALUES ('".$input["emp_id"]."','".$_GET["emp_id"]."','".$entry_date."','$data[$i]')";
	    $conn->query($sql1);
	}
	echo "{\"status\":\"success\"}";
}
else if($_GET["type"]=="getDailyTask") {
    $sql = "SELECT * FROM task WHERE employee='".$_GET["emp_id"]."' ORDER BY id DESC";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="sendTaskReport"){
	$sql = "UPDATE task SET emp_remark='".$_GET["emp_remark"]."',emp_status='".$_GET["emp_status"]."',completed_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="submitFinalTaskReport"){
	$sql = "UPDATE task SET remark='".$input["remark"]."',completed_date='".$entry_date."',task_status='".$input["status"]."', status='sent' WHERE id='".$input["id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getAssignedTasks") {
    $sql = "SELECT * FROM task WHERE DATE(assign_date)=CURDATE()";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
	}
	echo json_encode($output);} 
else if($_GET["type"]=="employeeTaskRemark") {
    $sql = "UPDATE task SET reporting_remark='".$_GET["remark"]."' WHERE id=". $_GET["task_id"];
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
// Resignation 
else if($_GET["type"]=="submitResignation"){
	$sql = "INSERT INTO resignation (emp_id,subject,expected_notice,expected_releaving,reason,entry_date) VALUES ('".$_GET["emp_id"]."','".$input["subject"]."','".$input["expected_notice"]."','".$input["expected_releaving"]."','".$input["reason"]."','".$entry_date."')";
	if($conn->query($sql)){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getPendingResignations") {
    $sql = "SELECT * FROM resignation WHERE status='pending' AND resignation_status='Pending'";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT emp_name, department, designation FROM employee WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["emp_name"] = $row1["emp_name"];
        		    $row["department"] = $row1["department"];
        		    $row["designation"] = $row1["designation"];
        		    break;
        		}
        	}
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="submitResignationReport"){
	$sql = "UPDATE resignation SET resignation_status='".$input["resignation_status"]."', resignation_remark='".$input["resignation_remark"]."', releaving_date='".$input["releaving_date"]."' WHERE id='".$input["resignation_id"]."'";
	if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
		$data = $input["clerance_department"];
		foreach ($data as $key => $value) {
		    if($value == true) {
    		    $sql1 = "INSERT INTO resignation_clearnace (resignation_id, department) VALUES ('".$input["resignation_id"]."','".$key."')";
    		    $conn->query($sql1);
		    }
		}
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getResignationCleanrance") {
    $sql = "SELECT * FROM resignation_clearnace WHERE department='".$_GET["department"]."' AND status='pending'";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM resignation WHERE id='".$row["resignation_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["emp_id"] = $row1["emp_id"];
        		    $row["department"] = $row1["department"];
        		    $row["releaving_date"] = $row1["releaving_date"];
        		    $row["subject"] = $row1["subject"];
        		    break;
        		}
        	}
        	$sql1 = "SELECT emp_name, department, designation FROM employee WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["emp_name"] = $row1["emp_name"];
        		    $row["department"] = $row1["department"];
        		    $row["designation"] = $row1["designation"];
        		    break;
        		}
        	}
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="appoveDepartmentClearence") {
    $sql = "UPDATE resignation_clearnace SET status='approve' WHERE resignation_id='".$input["reg_id"]."' AND department ='".$input["department"]."' ";
    if($conn->query($sql)===TRUE){
        $sql1 = "SELECT * FROM resignation_clearnace WHERE resignation_id='".$input["reg_id"]."' AND status='pending' ";
        $result1 = $conn->query($sql1);
        if($result1->num_rows > 0){
            return;
        } else {
            $sql2 = "UPDATE resignation SET status='approve' WHERE id ='".$input["reg_id"]."' "; 
            $conn->query($sql2);
        }
		 echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getPendingExitInterview") {
    $sql = "SELECT * FROM resignation WHERE status='approve' AND exitinterview_by=''";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="saveExitInterview") {
    $input = json_decode(file_get_contents('php://input'),true);
    $sql = "UPDATE resignation SET is_real_reason='".$input["is_real_reason"]."', interpersonal_disputes='".$input["interpersonal_disputes"]."', other_reason='".$input["other_reason"]."', retain_service='".$input["retain_service"]."',exitinterview_by='".$_GET["emp_id"]."',exitinterview_date='".$entry_date."' WHERE id='".$input["id"]."'";
    if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getPendingFullSettlement") {
    $sql = "SELECT * FROM resignation WHERE status='approve' AND exitinterview_by!=''";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT emp_name, department, designation, joining_date FROM employee WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["emp_name"] = $row1["emp_name"];
        		    $row["department"] = $row1["department"];
        		    $row["designation"] = $row1["designation"];
        		    $row["joining_date"] = $row1["joining_date"];
        		    break;
        		}
        	}
        	
        	$sql1 = "SELECT total_monthly FROM salary WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["total_monthly"] = $row1["total_monthly"];
        		    break;
        		}
        	}
        	
        	$sql1 = "SELECT * FROM resignation_clearnace WHERE resignation_id='".$row["id"]."'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $output1[] = $row1;
        		}
        	}
        	$row["dues"] = $output1;
        	
        	if($row["gross"]!="") {
        	    $sql1 = "SELECT * FROM finalsettlement WHERE resignation_id='".$row["id"]."'";
    		    $result1 = $conn->query($sql1);
    		    $output1 = Array();
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    $output1[] = $row1;
            		}
            		$row["settlement"] = $output1;
            	}
        	}
        	
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getPendingResignationLetters") {
    $sql = "SELECT id,emp_id,date(entry_date) as entry_date,date(releaving_date) as releaving_date FROM resignation WHERE status='pending' AND exitinterview_by!=''";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT emp_name, department, designation, joining_date FROM employee WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["emp_name"] = $row1["emp_name"];
        		    $row["department"] = $row1["department"];
        		    $row["designation"] = $row1["designation"];
        		    $row["joining_date"] = $row1["joining_date"];
        		    break;
        		}
        	}
        	
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="submitSettlement") {
    $input = json_decode(file_get_contents('php://input'),true);
    $data = $input["particulars"];
    $data1 = $input["deduction"];
    for($i = 0; $i < count($data); $i++) {
        $temp = $data[$i];
        $sql = "INSERT INTO finalsettlement (resignation_id, type, particular, amount) VALUES ('".$input["resignation_id"]."','".$temp["type"]."','".$temp["particular"]."','".$temp["amount"]."')";
        $conn->query($sql);
    }
    for($i = 0; $i < count($data1); $i++) {
        $temp = $data1[$i];
        $sql = "INSERT INTO finalsettlement (resignation_id, type, particular, amount) VALUES ('".$input["resignation_id"]."','".$temp["type"]."','".$temp["particular"]."','".$temp["amount"]."')";
        $conn->query($sql);
    }
    
    $sql = "UPDATE resignation SET gross_total='".$input["gross_total"]."', deduction_total='".$input["deduction_total"]."', net_total='".$input["net_total"]."' WHERE id='".$input["resignation_id"]."'";
    if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="getResponsibilities") {
    $sql = "SELECT emp_id, emp_name,department,designation,reporting_to,isresponsibility,qualification, joining_date FROM employee";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    if($row["isresponsibility"]=='active') {
		    $sql1 = "SELECT * FROM responsibility WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $output1[] = $row1;
        		}
        	}
        	$row["responsiblities"] = $output1;
		    }
        	
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getEmployeeResponsibilities") {
    $sql = "SELECT emp_id, emp_name,department,designation,reporting_to,isresponsibility,qualification, joining_date FROM employee WHERE department='".$_GET["department"]."' AND emp_id='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    if($row["isresponsibility"]=='active') {
		    $sql1 = "SELECT * FROM responsibility WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $output1[] = $row1;
        		}
        	}
        	$row["responsiblities"] = $output1;
		    }
		    break;
		}
		echo json_encode($row);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="saveResponsibility") {
    $input = json_decode(file_get_contents('php://input'),true);
    $data = $input["responsibility"];
    for($i = 0; $i < count($data); $i++) {
        $sql = "INSERT INTO responsibility (emp_id, responsibility) VALUES ('".$input["emp_id"]."','".$data[$i]."')";
        $conn->query($sql);
    }
    
    $sql = "UPDATE employee SET isresponsibility='active' WHERE emp_id='".$input["emp_id"]."'";
    if($conn->query($sql)===TRUE){
		echo "{\"status\":\"success\"}";
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
}
else if($_GET["type"]=="suspendEmployee") {
    $sql = "UPDATE employee SET status='suspend' WHERE emp_id='".$_GET["emp_code"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }} 
else if($_GET["type"]=="saveCharges") {
    $sql = "INSERT INTO additional_charges (emp_id, current_post, additional_designation, department, additional_responsibility, entry_date) VALUES ('".$input["emp_name"]."','".$input["current_post"]."','".$input["additional_designation"]."','".$input["deaprtment"]."','".$input["additional_responsibility"]."','$entry_date')";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }} 
else if($_GET["type"]=="getAdditionalCharges") {
    $sql = "SELECT * FROM additional_charges";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["emp_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["emp_name"] = $row1["emp_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);} 
else if($_GET["type"]=="updateCharges") {
    $sql = "UPDATE additional_charges SET status='active' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }} 
else if($_GET["type"]=="approveEmployee") {
    $sql = "UPDATE employee SET status='active' WHERE emp_id='".$_GET["emp_code"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }} 
else if($_GET["type"]=="getQCEmployees") {
    $sql = "SELECT * FROM employee WHERE department='Quality Control'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);} 
else if($_GET["type"]=="getCompanyAddresses") {
    $output = Array();
    $sql = "SELECT * FROM addresses";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);} 
else if($_GET["type"]=="getpreviousInterviews") {
    $output = Array();
    $sql = "SELECT * FROM interviewers WHERE employee='".$_GET["emp_id"]."' ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $sql2 = "SELECT * FROM candidate WHERE id='".$row["candidate_id"]."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $row["candidate_name"] = $row2["candidate_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);} 
else if($_GET["type"]=="getContractors") {
    $sql = "SELECT * FROM contractor";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);} 
else if($_GET["type"]=="getlabourlist") {
    $sql = "SELECT * FROM labour ORDER BY id DESC";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
	        $output[] = $row;
        }
		echo json_encode($output);
	}
	else {
        echo "[]";
    }
} 
else if($_GET["type"]=="getPendingSalaryCandidates") {
    $output = Array();
    $sql = "SELECT * FROM candidate WHERE isInterviewCompleted='Yes' AND dept_remark='selected' AND isSalary='No' ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}


// Offer Letter
else if($_GET['type']=='generatedoffer'){
        $sql = "SELECT * FROM candidate WHERE isSalary = 'Yes' AND isemployee='No' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    		echo json_encode($output);
    	} else {
    	   echo "[]";
    	}
    }
else if($_GET['type']=='pendingoffer'){
        $sql = "SELECT id, candidate_name, isSalary FROM candidate WHERE isSalary = 'No' AND dept_remark = 'selected' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    		echo json_encode($output);
    	} else {
    	    echo "[]";
    	}
    }
else if($_GET['type']=='generatenewoffer'){
        $input = json_decode(file_get_contents('php://input'),true);
        $id = $input['emp_id'];
        $sql1 = "INSERT INTO salary_annexure (emp_id,type,isMetro,isPF,isESIC,gross,deduction,contribution,inhand,ctc,basic,hra,conveyance,medical,specialallowance,educationalallowance)VALUE('$id','".$input['type']."','".$input['isMetro']."','".$input['isPF']."','".$input['isESIC']."','".$input['gross']."','".$input['deduction']."','".$input['contribution']."','".$input['inhand']."','".$input['ctc']."','".$input['basic']."','".$input['hra']."','".$input['conveyance']."','".$input['medical']."','".$input['specialallowance']."','".$input['educationalallowance']."')"; 
        if($conn->query($sql1)===TRUE){
            $sql2 = "SELECT * FROM candidate WHERE id='$id'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $email = $row2['email_id'];
                    class MYPDF extends TCPDF {
                        public function Header() {
                            
                        }
                        public function Footer() {
                            
                        }
                    }
                    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                    $pdf->SetCreator(PDF_CREATOR);
                    $pdf->SetTitle('Salary Annexure');
                    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
                    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                    $pdf->setPrintFooter(false);
                    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                        require_once(dirname(__FILE__).'/lang/eng.php');
                        $pdf->setLanguageArray($l);
                    }
                    $pdf->AddPage();
                    $pdf->SetY(30);
                    $pdf->SetFont ('times', '', '12');
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">OFFER LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['candidate_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%; text-align:justify;">This has reference to your application for employment in our Company; we are pleased to offer you an employment with us as an <b>'.$row2['finaldesignation'].'</b> on '.$newDate.' in <b> GMP Software Pvt ltd based in Pune HQ</b><br>Please note that this is merely an Offer Letter. <br>You are requested to carry the following documents at the time of joining: -<br>&nbsp; &nbsp; 1.	Academic Certificates / Passing Certificate (Original).<br>&nbsp; &nbsp; 2.	Two Passport size photographs.<br>&nbsp; &nbsp; 3.	ID Proof Xerox (Pan Card/Driving License/ Aadhar Card). <br>You are requested to join within 7 days from receipt of this Letter, failing, which this offer of employment stands withdrawn after completion of this period.<br>If employee’s performance found poor, company may ask to extend training period or ask to leave.<br>Kindly confirm your acceptance on the duplicate copy of this letter/or Return Email.<br>Other employment terms will be as per your appointment letter and will be informed within 7 days from your joining.<br></td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <h4 style="text-align:center;">Salary Annexure : </h4>
                    <br></br><br>
                    <table>
                        <tr>
                            <td style="text-align:right; width:100%;">Date: </td>
                        </tr>
                        <tr>
                            <td style="width:30%;">Name: '.$row2['candidate_name'].'</td>
                            <td style="width:70%;"></td>
                        </tr>
                        <tr>
                            <td style="width:30%;">Designation:</td>
                            <td style="width:70%;">'.$row2['finaldesignation'].'</td>
                        </tr>
                    </table>
                    <div></div>
                    <table cellpadding="5" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4"><b>Earnings :</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">1</td>
                            <td border="1">Basic</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['basic'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['basic']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['hra'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['hra']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['conveyance'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['conveyance']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">4</td>
                            <td border="1">Medical</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['medical'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['medical']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">5</td>
                            <td border="1">Special Allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['specialallowance'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['specialallowance']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">6</td>
                            <td border="1">Education Allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['educationalallowance'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['educationalallowance']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['gross'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['gross'], 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td colspan="4" border="1"><b>Deductions :</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">1.</td>
                            <td border="1" style="width:40%;">PT</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['p_tax'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['p_tax']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">2.</td>
                            <td border="1" style="width:40%;">PF</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">3.</td>
                            <td border="1" style="width:40%;">ESIC</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ESIC'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ESIC']*12, 2, '.', '').'</td>
                        </tr>
                         <tr>
                            
                            <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['deduction'], 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['deduction']*12, 2, '.', '').'</b></td>
                        </tr>
                        <tr>
                            <td colspan="4" border="1"><b>Company Contribution :</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">1.</td>
                            <td border="1" style="width:40%;">PF</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">2.</td>
                            <td border="1" style="width:40%;">ESIC</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['c_ESIC'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['c_ESIC']*12, 2, '.', '').'</td>
                        </tr>
                         <tr>
                            
                            <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['contribution'], 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['contribution']*12, 2, '.', '').'</b></td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4"></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['inhand'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['inhand']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:50%;">CTC ( A + C )</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ctc'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ctc']*12, 2, '.', '').'</td>
                        </tr>
                    </table>
                    <br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                      <tr>
                        <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                        <td style="width:10%;"></td>
                        <td style="width:60%; text-align:center;">Accepted</td>
                      </tr>
                      <tr>
                        <td style="width:40%; text-align:center;"><b></b></td>
                        <td style="width:10%;"></td>
                        <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                      </tr>
                    </table>';
                    EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $file = $id.'.pdf';
                    $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
                
                    $mail = new PHPMailer();
                    $mail->IsSMTP();  
                    $mail->Mailer = "smtp";
                    $mail->SMTPDebug = 1;
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = 'ssl';
                    $mail->Host = "mail.paperlessgmp.com";
                    $mail->Port = 465; // or 587
                    $mail->IsHTML(true);
                    $mail->Username = "demo@paperlessgmp.com";
                    $mail->Password = "2424@Cyclone";
                    $mail->SetFrom("demo@paperlessgmp.com", "Paperless GMP");
                    $mail->Subject = "Offer Letter";
                    $mail->Body = "If the offer letter is accepted please click on below link <br><br>https://paperlessgmp.com/gmptotal/offerletter.php?id=$id<br>Please find Attachment";
                    $file_to_attach = $id.'.pdf';
                    $mail->AddAttachment( $file_to_attach , 'OfferLetter.pdf' );
                    $mail->AddAddress($email);
                    if ($mail->Send()) {
                        $sql = "UPDATE candidate SET isSalary = 'Yes' WHERE id = '$id'"; 
                        $conn->query($sql);
                        echo "{\"status\":\"success\"}";
                        unlink($id.'.pdf');
                    } else {
                        echo "{\"status\":\"failed\"}";
                    }
                }
            }
        }
    }

// Appointment Letter 
else if($_GET['type']=='generatedappointment'){
    $sql = "SELECT * FROM employee WHERE isAppointment = 'active' ORDER BY id DESC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $output[] = $row; 
		}
		echo json_encode($output);
	} else {
	   echo "[]";
	}
}
else if($_GET['type']=='pendingappointment'){
        $sql = "SELECT emp_id, emp_name, isAppointment FROM employee WHERE isAppointment = 'pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    		echo json_encode($output);
    	} else {
    	    echo "[]";
    	}
    }
else if($_GET['type']=='generatenewappointment'){
        $id = $input['emp_id'];
        $sql = "INSERT INTO salary_annexure (emp_id,type,isMetro,isPF,isESIC,gross,deduction,contribution,inhand,ctc,basic,hra,conveyance,medical,specialallowance,educationalallowance)VALUE('$id','".$input['type']."','".$input['isMetro']."','".$input['isPF']."','".$input['isESIC']."','".$input['gross']."','".$input['deduction']."','".$input['contribution']."','".$input['inhand']."','".$input['ctc']."','".$input['basic']."','".$input['hra']."','".$input['conveyance']."','".$input['medical']."','".$input['specialallowance']."','".$input['educationalallowance']."')"; 
        if($conn->query($sql)===TRUE){
            $sql2 = "SELECT * FROM employee WHERE emp_id='$id'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $email = $row2['emp_email'];
                    class MYPDF extends TCPDF {
                        public function Header() {
                            
                        }
                        public function Footer() {
                            
                        }
                    }
                    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                    $pdf->SetCreator(PDF_CREATOR);
                    $pdf->SetTitle('Salary Annexure');
                    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
                    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                    $pdf->setPrintFooter(false);
                    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                        require_once(dirname(__FILE__).'/lang/eng.php');
                        $pdf->setLanguageArray($l);
                    }
                    $pdf->AddPage();
                    $pdf->SetY(30);
                    $pdf->SetFont ('times', '', '12');
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%; text-align:justify;">This has reference to your application for employment in our Company; we are pleased to offer you an employment with us as an <b>'.$row2['finaldesignation'].'</b> on '.$newDate.' in <b> GMP Software Pvt ltd based in Pune HQ</b><br>Please note that this is merely an Offer Letter. <br>You are requested to carry the following documents at the time of joining: -<br>&nbsp; &nbsp; 1.	Academic Certificates / Passing Certificate (Original).<br>&nbsp; &nbsp; 2.	Two Passport size photographs.<br>&nbsp; &nbsp; 3.	ID Proof Xerox (Pan Card/Driving License/ Aadhar Card). <br>You are requested to join within 7 days from receipt of this Letter, failing, which this offer of employment stands withdrawn after completion of this period.<br>If employee’s performance found poor, company may ask to extend training period or ask to leave.<br>Kindly confirm your acceptance on the duplicate copy of this letter/or Return Email.<br>Other employment terms will be as per your appointment letter and will be informed within 7 days from your joining.<br></td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <h4 style="text-align:center;">Salary Annexure : </h4>
                    <br></br><br>
                    <table>
                        <tr>
                            <td style="text-align:right; width:100%;">Date: </td>
                        </tr>
                        <tr>
                            <td style="width:30%;">Name: '.$row2['emp_name'].'</td>
                            <td style="width:70%;"></td>
                        </tr>
                        <tr>
                            <td style="width:30%;">Department:</td>
                            <td style="width:70%;">'.$row2['department'].'</td>
                        </tr>
                    </table>
                    <div></div>
                    <table cellpadding="5" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4"><b>Earnings :</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">1</td>
                            <td border="1">Basic</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['basic'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['basic']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['hra'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['hra']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['conveyance'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['conveyance']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">4</td>
                            <td border="1">Medical</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['medical'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['medical']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">5</td>
                            <td border="1">Special Allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['specialallowance'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['specialallowance']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">6</td>
                            <td border="1">Education Allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['educationalallowance'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['educationalallowance']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['gross'], 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $input['gross'], 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td colspan="4" border="1"><b>Deductions :</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">1.</td>
                            <td border="1" style="width:40%;">PT</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['p_tax'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['p_tax']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">2.</td>
                            <td border="1" style="width:40%;">PF</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">3.</td>
                            <td border="1" style="width:40%;">ESIC</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ESIC'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ESIC']*12, 2, '.', '').'</td>
                        </tr>
                         <tr>
                            
                            <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['deduction'], 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['deduction']*12, 2, '.', '').'</b></td>
                        </tr>
                        <tr>
                            <td colspan="4" border="1"><b>Company Contribution :</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">1.</td>
                            <td border="1" style="width:40%;">PF</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%;">2.</td>
                            <td border="1" style="width:40%;">ESIC</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['c_ESIC'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['c_ESIC']*12, 2, '.', '').'</td>
                        </tr>
                         <tr>
                            
                            <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['contribution'], 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $input['contribution']*12, 2, '.', '').'</b></td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4"></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['inhand'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['inhand']*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="width:50%;">CTC ( A + C )</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ctc'], 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ctc']*12, 2, '.', '').'</td>
                        </tr>
                    </table>
                    <br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                      <tr>
                        <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                        <td style="width:10%;"></td>
                        <td style="width:60%; text-align:center;">Accepted</td>
                      </tr>
                      <tr>
                        <td style="width:40%; text-align:center;"><b></b></td>
                        <td style="width:10%;"></td>
                        <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                      </tr>
                    </table>';
                    EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $file = $id.'.pdf';
                    $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
                
                    $mail = new PHPMailer();
                    $mail->IsSMTP();  
                    $mail->Mailer = "smtp";
                    $mail->SMTPDebug = 1;
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = 'ssl';
                    $mail->Host = "mail.paperlessgmp.com";
                    $mail->Port = 465; // or 587
                    $mail->IsHTML(true);
                    $mail->Username = "demo@paperlessgmp.com";
                    $mail->Password = "2424@Cyclone";
                    $mail->SetFrom("demo@paperlessgmp.com", "Paperless GMP");
                    $mail->Subject = "Appointment Letter";
                    $mail->Body = "Please find Attachment ";
                    $mail->AddAttachment($file);
                    $mail->AddAddress($email);
                    if ($mail->Send()) {
                        $sql = "UPDATE employee SET isAppointment = 'active' WHERE emp_id = '$id'"; 
                        $conn->query($sql);
                        echo "{\"status\":\"success\"}";
                        unlink($id.'.pdf');
                    } else {
                        echo "{\"status\":\"failed\"}";
                    }
                }
            }
        }
    }
    
// Increment / Promotion Letter
else if($_GET['type']=='generatedincremetpromotion'){
    $sql = "SELECT * FROM increment_promotion ORDER BY id DESC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM employee WHERE emp_id = '".$row['emp_id']."'";
		    $result1 = $conn->query($sql1);
	        if($result1->num_rows > 0){
		        while($row1 = $result1->fetch_assoc()){
		            $row['emp_name'] = $row1['emp_name'];
		        }
	        }
		    $output[] = $row; 
		}
		echo json_encode($output);
	} else {
	   echo "[]";
	}
}
else if($_GET['type']=='newincrementpromotion'){
    $id = $input['emp_id'];
    $date = $input['effective'];
    $effective = $date.'-01';
    $monthname=date("M Y",strtotime($effective));
    $lastmonth = date('Y-m-d', strtotime("$effective -1 month"));
    $lastmonth = date("M Y",strtotime($lastmonth));
    if($input['letter_type'] == 'Promotion Letter'){
        $updated_designation = $input['updated_designation'];
        $updated_gross = $input['current_gross'];
    } else if($input['letter_type'] == 'Increment Letter'){
        $updated_designation = $input['current_designation'];
        $updated_gross = $input['updated_gross'];
    } else {
        $updated_designation = $input['updated_designation'];
        $updated_gross = $input['updated_gross'];
    }
    $sql = "INSERT INTO increment_promotion (emp_id,letter_type,effective,designation,salary)VALUE('$id','".$input['letter_type']."' ,'$effective','$updated_designation','$updated_gross')"; 
    if($conn->query($sql)===TRUE){
        $sql2 = "SELECT * FROM employee WHERE emp_id='$id'";
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $email = $row2['emp_email'];
                class MYPDF extends TCPDF {
                        public function Header() {
                            
                        }
                        public function Footer() {
                            
                        }
                    }
                $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetTitle($input['letter_type']);
                $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
                $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                $pdf->setPrintFooter(false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage();
                $pdf->SetY(30);
                $pdf->SetFont ('times', '', '12');
                if($input['letter_type'] == 'Promotion Letter'){
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">PROMOTION LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Management has decided to upgrade your post to give you more opportunity to show your abilities<br>
                                Current Designation: '.$input['current_designation'].'<br>
                                Promoted Designation: '.$promoted_designation.'<br>
                                Your roles and Responsibilities will be inform you by Management<br><br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd </b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;"></td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of Employee)</td>
                        </tr>
                    </table>';
                }
                else if($input['letter_type'] == 'Increment Letter'){
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">INCREMENT LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$row2['emp_name'].' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your
                                performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>
                                Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>
                                Also management has decided to revise your salary structure which will be effective from '.$monthname.' please find Annexure attached with this Letter.<br>
                                Best of Luck for your Bright Future<br>
                                Thanks and Regards
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <br><br></br>
                    <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                    <br></br><br>
                    <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%; text-align:center;">1</td>
                            <td border="1" style="width:40%;">Basic</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $updated_gross*0.40, 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($updated_gross*0.40)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.20, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.20)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Medical allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">4</td>
                            <td border="1">Education allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Travelling allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4">Deductions</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $net, 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $netannual, 2, '.', '').'</b></td>
                        </tr>
                    </table> 
                    <br><br><br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                        </tr>
                        <br><br><br>
                        <tr>
                            <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an Employee)</td>
                        </tr>
                    </table>';
                }
                else {
                    $html='
                    <br><br><br>
                    <h3 style="text-align:center;">INCREMENT AND PROMOTION LETTER</h3>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                            <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                        </tr>
                        <tr>
                            <td colspan="3"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;"><b>'.$row2['emp_name'].' <br>'.$address.'</b></td>
                        <tr>
                    </table>
                    <h3>Dear '.$title.' '.$name.' , </h3><br>
                    <table>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%;">Management of Cyclone Pharmaceuticals Pvt Ltd is happy to inform you that your performance in Month May 2019 to '.$lastmonth.' appreciable .This appreciation Letter is being given for your hard working and Learning attitude.<br>Keep it up and accept this letter along with a small token of Love for your performance from Cyclone Pharmaceuticals Pvt Ltd Management<br>Also management has decided to revise your salary structure which will be effective from 01 '.$monthname.' please find Annexure attached with this Letter.<br>Along with this management has decided to upgrade your post to give you more opportunity to show your abilities<br>Current Designation: '.$input['current_designation'].'<br>Promoted Designation: '.$updated_designation.'<br>Your roles and Responsibilities will be inform you by Management<br><br>Best of Luck for your Bright Future<br>Thanks and Regards,
                            </td>
                        </tr>
                    </table>
                    <br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                          </tr>
                          <tr><td></td></tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Signature of an Employee</td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <br><br></br>
                    <h4 style="text-align:center;">Salary Annexure : '.$row2['emp_name'].' </h4>
                    <br></br><br>
                    <table cellpadding="7" style="border: 1px solid #DCDCDC; text-align:left; ">
                        <tr style="background-color:#DCDCDC;">
                            <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                            <td  border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                            <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                        </tr>
                        <tr>
                            <td border="1" style="width:10%; text-align:center;">1</td>
                            <td border="1" style="width:40%;">Basic</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) $updated_gross*0.40, 2, '.', '').'</td>
                            <td border="1" style="width:25%; text-align:right;">'.number_format((float) ($updated_gross*0.40)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">2</td>
                            <td border="1">HRA</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.20, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.20)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" style="text-align:center;">3</td>
                            <td border="1">Conveyance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Medical allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">4</td>
                            <td border="1">Education allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1"style="text-align:center;">5</td>
                            <td border="1">Travelling allowance</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*0.10, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) ($updated_gross*0.10)*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $updated_gross*12, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="4">Deductions</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;">Prof Tax</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Deduction Total (B)</b></td>
                            <td border="1" style="text-align:right;">'.number_format((float) $deduction, 2, '.', '').'</td>
                            <td border="1" style="text-align:right;">'.number_format((float) $annualdeduction, 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td border="1" colspan="2" style="text-align:right;"><b>Net Income (A - B)</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $net, 2, '.', '').'</b></td>
                            <td border="1" style="text-align:right;"><b>'.number_format((float) $netannual, 2, '.', '').'</b></td>
                        </tr>
                    </table> 
                    <br><br><br><br><br><br>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:40%; text-align:center;"><b>Cyclone Pharmaceuticals Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                        </tr>
                        <br><br><br>
                        <tr>
                            <td style="width:40%; text-align:center;">Mr. Sachin Bhalekar <br>Managing Director</td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Signature of an Employee</td>
                        </tr>
                    </table>';
                }
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $file = $id.'.pdf';
                $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
            
                $mail = new PHPMailer();
                $mail->IsSMTP();  
                $mail->Mailer = "smtp";
                $mail->SMTPDebug = 1;
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = 'ssl';
                $mail->Host = "mail.paperlessgmp.com";
                $mail->Port = 465; // or 587
                $mail->IsHTML(true);
                $mail->Username = "demo@paperlessgmp.com";
                $mail->Password = "2424@Cyclone";
                $mail->SetFrom("demo@paperlessgmp.com", "Paperless GMP");
                $mail->Subject = $input['letter_type'];
                $mail->Body = "Please find Attachment ";
                $mail->AddAttachment($file, 'Increment / Promotion Letter');
                $mail->AddAddress($email);
                $mail->Send();
                echo "{\"status\":\"success\"}";
                unlink($id.'.pdf');
                /* 
                    $sql = "UPDATE employee SET designation = '$updated_designation', current_gross= '$updated_gross' WHERE emp_id = '$id'"; 
                    $conn->query($sql);
                */
            }
        }
    } 
}
    
// Employee Experience Letter
else if($_GET['type']=='getexperienceletter'){
    $sql = "SELECT * FROM resignation WHERE exp_letter='generated' ORDER BY id DESC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $row['entry_date'] = date('d-m-Y', strtotime($row['entry_date']));
		    $row['releaving_date'] = date('d-m-Y', strtotime($row['releaving_date']));
		    $row['exitinterview_date'] = date('d-m-Y', strtotime($row['exitinterview_date']));
		    $sql1 = "SELECT * FROM employee WHERE emp_id = '".$row['emp_id']."'";
		    $result1 = $conn->query($sql1);
	        if($result1->num_rows > 0){
		        while($row1 = $result1->fetch_assoc()){
		            $row['emp_name'] = $row1['emp_name'];
		        }
	        }
		    $output[] = $row; 
		}
		echo json_encode($output);
	} else {
	   echo "[]";
	}
}
else if($_GET["type"]=="getPendingExperience") {
    $sql = "SELECT * FROM resignation WHERE status='approve' AND exp_letter='pending' AND 	exitinterview_by !='' ";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $row['entry_date'] = date('Y-m-d', strtotime($row['entry_date']));
		    $sql1 = "SELECT emp_name, department, designation FROM employee WHERE emp_id='".$row["emp_id"]."'";
		    $result1 = $conn->query($sql1);
        	if($result1->num_rows > 0){
        		while($row1 = $result1->fetch_assoc()){
        		    $row["emp_name"] = $row1["emp_name"];
        		    $row["department"] = $row1["department"];
        		    $row["designation"] = $row1["designation"];
        		    break;
        		}
        	}
		    $output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="generateexpletter") {
    $input = json_decode(file_get_contents('php://input'),true);
    $sql = "UPDATE resignation SET exp_letter='generated', releaving_date = '".$input["releaving_date"]."' WHERE id='".$input["id"]."'";
    if($conn->query($sql)===TRUE){
        echo "{\"status\":\"success\"}";
        $sql1 = "UPDATE employee SET status='inactive' WHERE emp_id='".$input["emp_id"]."'";
        $conn->query($sql1);
    }
    else {
        echo "{\"status\":\"An error has occurred, Please try again.\"}";
    }

} 
else if($_GET["type"]=="printexpletter"){
    $sql = "SELECT * FROM resignation WHERE id='".$_GET["id"]."' AND exp_letter='generated'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $date = date("d/m/Y");
            $end_date = date("d M Y", strtotime($row["releaving_date"]));
            $resign_date = date("d M Y", strtotime($row["entry_date"]));
            
            $sql1 = "SELECT * FROM employee WHERE emp_id='".$row['emp_id']."'";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0){
                while($row1 = $result1->fetch_assoc()){
                    $joining_date = date("d M Y", strtotime($row1["joining_date"]));
                    $Title = '';
                    $title = '';
                    if($row1['gender'] == 'male'){
                        $Title = 'His';
                        $title = 'his';
                    } else if($row1['gender'] == 'female') {
                        $Title = 'Her';
                        $title = 'her';
                    }
                    class MYPDF extends TCPDF {
                        public function Header() {
                        }
                        public function Footer() {
                        }
                    }
                    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                        require_once(dirname(__FILE__).'/lang/eng.php');
                        $pdf->setLanguageArray($l);
                    }
                    $pdf->AddPage();
                    $pdf->SetFont('Times', '', 12);
                    $html= '
                    <div><br><br><br><br><br></div>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"></td>
                            <td style="width:30%; text-align:right;"><b>Date : '.$date.'</b></td>
                        </tr>
                        <tr>
                            <td style="width:100%;"><b>To,</b></td>
                        </tr>
                        <tr>
                            <td style="width:45%;"><b>'.$row1['emp_name'].'<br>'.$row1['address'].'</b></td>
                        </tr>
                        <tr><br></tr>
                        <tr>
                            <td style="width:100%;"><b>Sub: Relieving Letter</b></td>
                        </tr>
                        <tr><br></tr>
                        <tr>
                            <td style="width:100%;">Dear <b>'.$row1['emp_name'].'</b> ,</td>
                        </tr>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%; text-align:justify;">This has reference to your letter of resignation dated '.$resign_date.',
                            wherein you have requested to be relieved from the service of the company on '.$end_date.'. 
                            we would like to inform you that your resignation is hereby accepted and you are being relieved from the 
                            services, with effect from closing office hours of '.$end_date.'. we also certify that your full and final settlement 
                            of account has cleared with the organization.<br><br>Your contribution to the organization and its 
                            success will always be appreciated.<br><br>We at '.$rowcompany['name'].' wish you all the best in your future endeavours.
                            </td>
                        </tr>
                        <tr>
                            <td style="width:100%; text-align:right;">
                                <br><br><br><br><br><br>Authorised Signatory&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br><b>'.$rowcompany['name'].'</b><br>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:5%;"></td>
                            <td style="width:95%; text-align:justify;">I <b>'.$row1['emp_name'].'</b> agreed on above letter and certifying that i 
                            received my full and final settlement with this letter. I will not claim on any function and part of '.$rowcompany['name'].'
                            in future also I am bounded to keep 100% confidentiality for the work and document of '.$rowcompany['name'].'.<br><br><br><br><p style="text-align:right;">Signature of Employee<br><b>'.$row1['emp_name'].'</b></p> 
                                
                            </td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <div><br><br><br><br><br></div>
                    <table cellpadding="5" style="text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"></td>
                            <td style="width:30%; text-align:right;"><b>Date : '.$date.'</b></td>
                        </tr>
                        <tr>
                            <td style="width:100%; text-align:center;"><b>Whomsoever It May Concern</b></td>
                        </tr>
                        <tr><br></tr>
                        <tr><br></tr>
                        <tr>
                            <td style="text-align:justify;">This is to certify that <b>'.$row1['emp_name'].'</b> was employed with our company <b>'.$rowcompany['name'].'</b>
                            from <b>'.$joining_date.'</b> to <b>'.$end_date.'</b> as <b>'.$row1['designation'].'.</b><br>
                            <br>'.$row['responsibilities'].'<br><br>Key Skills : '.$row['key_skills'].'<br><br>'.$Title.' Exposure in these areas is 
                            very good. During '.$title.' tenure with us and found '.$title.' to be hardworking and very productive.<br><br>We have 
                            found '.$title.' to be self starter who is motivated, duty bound, and a highly commited team player with strong 
                            conceptual knowledge.<br><br><br>We Wish '.$title.' all success in '.$title.' future endeavours. 
                            </td>
                        </tr>
                        <br><br><br><br><br><br><br><br><br><br>
                        <tr style="text-align:center;">
                            <td style="width:40%;">
                                Authorised Signatory<br><b>'.$rowcompany['name'].'</b>
                            </td>
                            <td style="width:20%;"></td>
                             <td style="width:30%;">
                               Signature of Employee<br><b>'.$row1['emp_name'].'</b>
                            </td>
                        </tr>
                    </table>
                    <br pagebreak="true"/>
                    <div><br><br><br><br><br></div>
                    <table cellpadding="5" style="font-size:20px; font-weight: bold; text-align:left; width:100%;">
                        <tr>
                            <td style="width:70%;"></td>
                            <td style="width:30%; text-align:right;"><b>Date : '.$date.'</b></td>
                        </tr>
                        <tr>
                            <td style="width:100%; text-align:center;"><b>FULL AND FINAL SETTLEMENT</b></td>
                        </tr>
                        <tr>
                            <td><br><br><br></td>
                        </tr>
                        <tr>    
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Name of Employee</td>
                            <td style="width:50%;">: '.$row1['emp_name'].'</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Joining Date</td>
                            <td style="width:50%;">: '.$joining_date.'</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Relieving Date</td>
                            <td style="width:50%;">: '.$end_date.'</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Monthly Salary</td>
                            <td style="width:50%;">: '.number_format((float) $row1["current_gross"], 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Last Salary Drawn</td>
                            <td style="width:50%;">: '.number_format((float) $row["last_salary"], 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Amount of Salary Due</td>
                            <td style="width:50%;">: '.number_format((float) $row["salary_due"], 2, '.', '').'</td>
                        </tr>
                        <tr>
                            <td style="width:10%;"></td>
                            <td style="width:40%;">Full and Final Amount</td>
                            <td style="width:50%;">: '.number_format((float) $row["final_amount"], 2, '.', '').'</td>
                        </tr>
                    </table>
                    <table>
                        <br><br><br><br><br><br><br><br><br><br><br><br>
                        <tr style="text-align:center;">
                            <td style="width:40%;">
                                Authorised Signatory<br><b>'.$rowcompany['name'].'</b>
                            </td>
                            <td style="width:20%;"></td>
                             <td style="width:30%;">
                               Signature of Employee<br><b>'.$row1['emp_name'].'</b>
                            </td>
                        </tr>
                    </table>
                    '; EOD;
                    $pdf->writeHTML($html, true, false, false, false, '');
                    $pdf->Output('Offer Letter.pdf', 'I');
                }
            }
        }
    };
}
// All Employee / labour Salary Report
else if($_GET["type"]=="get_employee_salary_statement_for_management") {
     $output = Array(); 
    $date = $_GET['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
   /* $sql = "select a.emp_code, CONCAT( firstname,' ',middlename,' ', lastname ) AS emp_name, b.department,b.designation,
    a.salary_details from emp_salary_details a join employee b on a.emp_code = b.emp_id and a.plant_id = b.plant_id
    where a.plant_id = '".$_GET['plant_id']."' and month =$month and year = $year and send_to_clearance = 'Yes' 
    and send_to_finance='No' order by 1 desc ";*/
    
    $sql = "select emp_code, salary_details from emp_salary_details  
    where plant_id = '".$_GET['plant_id']."' and month =$month and year = $year and send_to_clearance = 'Yes' 
    and send_to_finance='No' order by 1 desc ";
    $result = $conn->query($sql);

	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getEmployeeMonthSalary") {
    $date = $_GET['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
    $output = Array(); 
//   echo $sql="select a.*,IFNULL(b.id,0) as salary_slip_id from (SELECT a.id, a.emp_id,department,designation,
//     CONCAT( firstname,' ',middlename,' ', lastname ) AS emp_name,b.take_home_salary,b.id as salary_annexure_id
//     FROM employee a join salary_annexure b on a.emp_id = b.emp_id WHERE a.status='active' AND a.isAppointment = 'active'
//     and a.plant_id = '".$_GET["plant_id"]."')left join 
//     (select id,emp_code from emp_salary_details where month =$month and year = $year and plant_id='".$_GET["plant_id"]."')
//     as b on a.emp_id = b.emp_code";
    
    
    $sql="select x.* from (SELECT c.month, c.year,a.plant_id,a.isAppointment,a.id, a.emp_id,department,designation, CONCAT( firstname,' ',middlename,' ', lastname ) AS emp_name,b.take_home_salary,b.id as salary_annexure_id FROM employee a join salary_annexure b on a.id = b.emp_id left join emp_salary_details c on c.emp_code = a.emp_id ) x";
    
    
    
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($rowx = $result->fetch_assoc()) {
            $row=[];
            $row['emp_id']=$rowx["emp_id"];
             $row['emp_name']=$rowx["emp_name"];
              $row['department']=$rowx["department"];
             $row['designation']=$rowx["designation"];
             $row['take_home_salary']=$rowx["take_home_salary"];
             $row['salary_slip_id']=$rowx["salary_slip_id"];
            /*$sql1 = "SELECT * FROM salary_annex
            ure WHERE emp_id='".$row["emp_id"]."' AND status='active'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["gross"] = $row1['gross'];
                    $row["p_tax"] = $row1['p_tax'];
                    $row["canteen"] = $row1['canteen'];
                    $row["other"] = $row1['other'];*/

              //  $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$row["emp_id"]."' AND status='pending'";
                $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$rowx["emp_id"]."'
                         and month(indate)=$month and year(indate)=$year ";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['present_days'] = $row2["present_days"];
                    }
                } else {
                    $row['present_days'] = 0;
                }

                $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$rowx["emp_id"]."' AND leave_type='Short Leave' AND status='pending'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['shortleave'] = $row2["shortleave"];
                    }
                } else {
                    $shortleave = 0;
                }
                $sql2 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Half Day' AND status='pending'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['halfday'] = $row2["halfday"];
                    }
                } else {
                    $row['halfday'] = 0;
                }
        
                $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowx["emp_id"]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['total_leave'] = $row2["total_leave"];
                    }
                } else {
                    $row['total_leave'] = 0;
                }
                
                
                
                
                
                $date = strtotime($_GET['month']);
                $month=date("m",$date);
                $year=date("Y",$date);
                $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $row['month_days'] =$month_days; 
                $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                 $row['working_days'] =$working_days;
             
                $paid_days = $row['present_days']*1 + $row['total_leave']*1 ;
                $row['paid_days'] = $paid_days;
                
                $total_salary_days = $paid_days+$working_days;
               $row['absent_days'] = $row['working_days'] - $row['present_days'];
                
                
                $earnings =0;
                $deductions=0;
                $earningsList= Array();
                $deductionsList= Array();
                $sql2 = "select * from salary_annexure_details WHERE salary_annexure_id='".$rowx["salary_annexure_id"]."' order by salary_group ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row21 = $result2->fetch_assoc()) 
                        {
                            $temp=[];
                             $temp1=[];
                          if($row21['salary_group']!='Earnings'){        
                               $deductionsList[$row21['description']]  = $row21["per_month"];
                               $deductions=$deductions+ $row21["per_month"];
                          }else{
                              $amount = (($row21["per_month"]/$working_days)*$paid_days);
                               $earningsList[$row21['description']]  = number_format((float)$amount, 2, '.', '');
                               $earnings=$earnings+(number_format((float)$amount, 2, '.', ''));
                          }
                          // $earningsList[]=$temp;
                          // $deductions[]=$temp1;
                        
                        }
                    /*while ($row2 = $result2->fetch_assoc()) {
                         $annesure =  json_encode($row2);
                       // echo  $row2["description"];
                        $row['description'] = $row2;
                          $row['amount'] = $row2["per_month"];
                    }*/
                } 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                
                
                
                
                
                
                
                
                
                 
                
               /*$row['earned_gross'] = $row['salary_per_day'] * $row['paid_days'];
                 $row['salary_per_day'] = $row["gross"] / $row['month_days'];
                
                $row['basic'] = $row['earned_gross'] * 0.40 ;
                $row['hra'] = $row['basic'] * 0.40;
               
                $row['conveyance'] = $row['earned_gross'] * 0.10;
                $row['medical'] = $row['earned_gross'] * 0.10;
                $row['educational'] = $row['earned_gross'] * 0.10;
                $row['special'] = $row['earned_gross'] * 0.10;
                if ($row["isPF"] == 'Yes') {
                    $row['pf'] = $row['basic'] * 13.601;
                } else {
                    $row['pf'] = 0;
                }
                if ($row["isESIC"] == 'Yes') {
                    $row['esic'] = $row['earned_gross'] * 0.075;
                } else {
                    $row['esic'] = 0;
                }*/
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $output[] = $row;
            //}
        //}
        }
    }
    echo json_encode($output);
}
else if($_GET["type"]=="getLabourMonthSalary") {
    $output = Array();
    $sql = "SELECT labour_id, labour_name, daily_wages FROM labour WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {

            $date = strtotime($_GET['month']);
            $month=date("m",$date);
            $year=date("Y",$date);
            
            $number = cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
            $row["total_days"] = $number;
            $row["working_days"] = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
            
            $sql1 = "SELECT COUNT(id) as present_days FROM labour_attendance WHERE labour_id='".$row["labour_id"]."' AND status='pending' AND MONTH(in_time) = $month AND YEAR(in_time) = $year";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["present_days"] = $row1["present_days"];
                }
            } else {
                $row["present_days"] = 0;
            }
            $row["absent_days"] = $row["working_days"] - $row["present_days"];
            
            $sql1 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Short Leave' AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["shortleave"] = $row1["shortleave"];
                }
            } else {
                $row["shortleave"] = 0;
            }
            $sql1 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$row["emp_id"]."' AND leave_type='Half Day' AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["halfday"] = $row1["halfday"];
                }
            } else {
                $row["halfday"] = 0;
            }
            $sql1 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$row["emp_id"]."' 
            AND leave_type='Half Day' AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["halfday"] = $row1["halfday"];
                }
            } else {
                $row["halfday"] = 0;
            }
            
            $sql1 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` 
            WHERE emp_id='SBHR001' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["total_leave"] = $row1["total_leave"];
                }
            } else {
                $row["total_leave"] = 0;
            }
            $row["earning"] = (int) ($row["daily_wages"] * $row["present_days"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

// Single Employee / labour Salary Calculation
else if($_GET["type"]=="calculateempsalary"){
    $input = json_decode(file_get_contents('php://input'),true);
    $date = $input['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
    $monthdays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $sql = "SELECT * FROM attendence WHERE emp_id = '".$input['emp_id']."' AND MONTH(indate) = $month AND YEAR(indate) = $year";
    echo $sql;
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $presentdays = 0;
        while($row = $result->fetch_assoc()){
            $presentdays = mysqli_num_rows($result);
        }
	}
// 	$sql = "SELECT * FROM salary_annexure WHERE emp_id = '".$input['emp_id']."'";
    $sql = "SELECT * FROM `salary_annexure` WHERE emp_id ='EMP001' ";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $monthlysalary = $row['inhand'];
        }
	}
	$output = (array("monthdays"=>"$monthdays","present" => $presentdays,"monthlysalary"=>$monthlysalary));
	echo json_encode($output);
	
}
else if($_GET["type"]=="calculatelaboursalary") {
    $input = json_decode(file_get_contents('php://input'),true);
    $date = $input['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
    $monthdays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $sql1 = "SELECT COUNT(id) as present_days FROM labour_attendance WHERE labour_id = '".$input['labour_id']."' AND MONTH(entry_date) = $month AND YEAR(entry_date) = 2020";
    $result1 = $conn->query($sql1);
    if ($result1->num_rows > 0) {
        while ($row1 = $result1->fetch_assoc()) {
            $present = $row1["present_days"];
        }
    } else {
        $present = 0;
    }
    echo "{\"monthdays\":\"$monthdays\", \"present\":\"$present\"}";
}

} else {
    echo "[]";
}

function calculateWorkingDaysInMonth($year = '', $month = '')
{
	//in case no values are passed to the function, use the current month and year
	if ($year == '')
	{
		$year = date('Y');
	}
	if ($month == '')
	{
		$month = date('m');
	}	
	//create a start and an end datetime value based on the input year 
	$startdate = strtotime($year . '-' . $month . '-01');
	$enddate = strtotime('+' . (date('t',$startdate) - 1). ' days',$startdate);
	$currentdate = $startdate;
	//get the total number of days in the month	
	$return = intval((date('t',$startdate)),10);
	//loop through the dates, from the start date to the end date
	while ($currentdate <= $enddate)
	{
		//if you encounter a Saturday or Sunday, remove from the total days count
		// if ((date('D',$currentdate) == 'Sat') || (date('D',$currentdate) == 'Sun'))
		if (date('D',$currentdate) == 'Sun')
		{
			$return = $return - 1;
		}
		$currentdate = strtotime('+1 day', $currentdate);
	} //end date walk loop
	//return the number of working days
	return $return;
}

function getHoursFormat( $given ){

 $hours = ( $given > 86399 ) ? '0'.floor( ( $given / 86400 ) * 24 )-gmdate( "H", $given ) : gmdate("H", $given );

 $min = gmdate( "i", $given );

 $sec = gmdate( "s", $given );

 $formatted_string = $hours;

 return $formatted_string;

}




$conn->close();
?>