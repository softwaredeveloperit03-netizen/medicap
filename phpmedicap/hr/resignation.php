<?php



// ini_set('display_errors', 1);
// error_reporting(E_ALL);






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
    
    
  if ($_GET["type"] == "saveResignation") {
        
         $sql = "INSERT INTO resignation (department,description,designation,emp_id,emp_name,expected_releaving,resignation_date,resignation_type) VALUES 
         ('".$input["department"]."', '".$input["description"]."', '".$input["designation"]."', '".$input["emp_id"]."', '".$input["emp_name"]."', 
         '".$input["expected_releaving"]."', '".$input["resignation_date"]."', '".$input["resignation_type"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } 
 else if ($_GET["type"] == "saveResignation_dept") { 
        
        $sql = "update  resignation set dept_resignation_remark='".$input["dept_remark"]."',dept_resignation_status='".$input["dept_status"]."' where id='".$_GET["id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } 
   
    else if ($_GET["type"] == "generateexpletter") {
        
         $sql = "INSERT INTO generateexpletter (emp_name,id,resignation_date,releaving_date,responsibilities,key_skill,currInhand,lastInhand,TotalPendingSal,joining_date)
        VALUES ('".$input['emp_name']."','".$input["emp_id"]."', '".$input["resignation_date"]."', '".$input["expected_releaving"]."',
        '".$input["responsibilities"]."', '".$input["key_skill"]."', '".$input["currInhand"]."', '".$input["lastInhand"]."', '".$input["TotalPendingSal"]."', '".$input["joining_date"]."' )";
        if ($conn->query($sql)) {
            
            
         $sql1 = "update  resignation set exp_letter='Inprocess' where id='".$input["id"]."' ";
        $conn->query($sql1);

            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }

    

    
    
    
 else if ($_GET["type"] == "saveExitInterview") { 
        
        
        $sql = "update  resignation set other_reason= '".json_encode($input["checkListData"])."', resignation_status='Completed' where id='".$_GET["id"]."' ";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } 
 else if ($_GET["type"] == "submitResignationReport") { 
        
        $sql = "update  resignation set status='Accepted'  where id='".$_GET["id"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } 
 else if ($_GET["type"] == "saveDirectExit") { 
        
        $sql = "update  employee  set status='Exit',resign_date='".$input["resignation_date"]."' where emp_id='".$_GET["emp_id1234"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } 
    
    
   else if ($_GET["type"] == "getDirectExitEmp") {
       
        $output = Array();
        $sql = "SELECT *  FROM employee   WHERE  status='Exit'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
    
   else if ($_GET["type"] == "get_resignation") {
       
        $output = Array();
        $sql = "SELECT r.*,e.joining_date FROM resignation r left join employee e ON r.emp_id = e.emp_id WHERE r.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "get_resignation_by_emp_id") {
       
        $output = Array();
        $sql = "SELECT r.*,e.joining_date FROM resignation r left join employee e ON r.emp_id = e.emp_id WHERE  r.emp_id = '".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "get_resignation_by_deparetment") {
       
        $output = Array();
        $sql = "SELECT r.*,e.joining_date FROM resignation r left join employee e ON r.emp_id = e.emp_id
        WHERE r.status='pending' AND  r.department = '".$_GET["deptName"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "get_resignation_by_deparetmentNotification") {
       
        $output = Array();
        $sql = "SELECT count(id) as pending_resignation  FROM resignation WHERE status='pending' AND  department = '".$_GET["deptName"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
            
            $test = "You Have '".$output['pending_resignation']."' Resignation Pending For Approval";
            $output['text'] = $test;
    
    echo json_encode($output);
    
       
   }
   
   else if ($_GET["type"] == "getsalaryDetails") {
         
   $date = $_GET['expected_releaving'];
    $time=strtotime($date);
    $month=date("m",$time);
    $year=date("Y",$time);
 
    $output = Array(); 
                               $sql="SELECT
                          
                            a.plant_id,
                            a.isAppointment,
                            a.operator_category,
                            a.id,
                            a.emp_id,
                            department,
                            designation,
                            CONCAT(firstname, ' ', middlename, ' ', lastname) AS emp_name,
                            b.take_home_salary,
                            b.gross_salary,
                            b.total_deductions,
                            b.status,
                            b.id AS salary_annexure_id,
                            c.work_hrs
                        FROM
                            employee a
                        JOIN
                            salary_annexure b ON a.emp_id = b.emp_id
                        LEFT JOIN
                            attendence c ON c.emp_id = a.emp_id
                           
    JOIN shift_schedule s ON c.shift = s.id
                        WHERE   a.emp_id= '".$_GET["emp_id11"]."' and
                            c.indate  Like'%".$year.'-'.$month."%'  group by a.plant_id, a.isAppointment, a.id, a.emp_id,
                            department, designation,emp_name, b.take_home_salary, b.gross_salary, b.total_deductions, b.status,a.operator_category,b.id,c.work_hrs ";
                              
    
    
    
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

             
         
        
        
        
             $sql2 = "SELECT no_day 
                                    FROM leaveform 
                                    WHERE NOT EXISTS (
                                        SELECT 1 
                                        FROM attendence 
                                        WHERE indate = leave_from
                                    ) and leave_from like '%".$year.'-'.$month."%'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["leaves_of_month"]= $row2["no_day"];
                    }
                } else {
                    $days = 0;
                }
                
                ////////////////////////////////////////
                
                
              $sql2 = "SELECT SUM(a.monthly_emi) as EMI
                                FROM emp_loan a 
                                WHERE 
                                    a.emp_id = '".$row['emp_id']."' 
                                    AND '".$year."-".$month."'  BETWEEN a.emi_start_from AND a.emi_end
                                ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["EMI"]= $row2["EMI"];
                    }
                }  
        
        
     
        
        
         
             
            //  /////////////////////////////////////////////////
             
             
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
                        if($pkg > 400000){
                               $row['total'] =0;
                               $row['ot_total']=0;
                        }
                        else{
                             $row['total'] =$row2['total_count_of_2hrs_each'];
                        
                        if($rowx["operator_category"]=='Staff'){
                            if ($row['work_hrs'] > 8) {
                                    $row['ot_total'] = $row['total'] / 1.5;
                                }else{
                                     $row['ot_total']=0;
                                }
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                             if ($row['work_hrs'] > 11) {
                                     $row['ot_total']=$row['total'] * 1;
                                }else{
                                     $row['ot_total']=0;
                                }
                             
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
                
                
                
                $date = strtotime($_GET['expected_releaving']);
                $month=date("m",$date);
                $year=date("Y",$date);
                
                
                
                
                
                 $working_days = calculateWorkingDaysInMonth(date("Y", $year),date("m", $month));
                 $row['working_days'] =$working_days;
                
                $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$rowx["emp_id"]."'
                         and month(indate)=$month and year(indate)=$year ";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        if($row['bal_leav'] > 0){
                            
                        $row['present_days'] = $row['working_days'];
                        }else{
                        $row['present_days'] = $row2["present_days"];

                        }
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
                 $date = strtotime($_GET['expected_releaving']);
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
        
                $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowx["emp_id"]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['total_leave'] = $row2["total_leave"];
                    }
                } else {
                    $row['total_leave'] = 0;
                }
              
                
                 
                
                $date = strtotime($_GET['expected_releaving']);
                $month=date("m",$date);
                $year=date("Y",$date);
                
          
                $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $month), date("Y", $year));
                $row['month_days'] =$month_days; 
               
             
                $paid_days = $row['present_days']*1 + $row['total_leave']*1 ;
                $row['paid_days'] = $paid_days;
                
                $total_salary_days = $paid_days+$working_days;
            //   $row['absent_days'] = $row['working_days'] - $row['present_days'] -$row['bal_leav'];
            
            
                $row['absent_days'] = max(0, $row['working_days'] - $row['present_days'] - $row['bal_leav']);

                
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
                     
                        
                        }
              
                } 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                
                 
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                // $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $output[] = $row;
          
        }
    }
    echo json_encode($output);

    }
  
  else if($_GET["type"]=="getEmployeePriviousMonthSalary") {
     
        
    
    $date = isset($_GET['expected_releaving']) ? $_GET['expected_releaving'] : date('Y-m-d'); 
$time = strtotime($date);
$month = date("m", strtotime("-1 month", $time));
$year = date("Y", strtotime("-1 month", $time));

if ($month == 12) {
    $year--;
}


  
 
    $output = Array(); 
                               $sql="SELECT
                          
                            a.plant_id,
                            a.isAppointment,
                            a.operator_category,
                            a.id,
                            a.emp_id,
                            department,
                            designation,
                            CONCAT(firstname, ' ', middlename, ' ', lastname) AS emp_name,
                            b.take_home_salary,
                            b.gross_salary,
                            b.total_deductions,
                            b.status,
                            b.id AS salary_annexure_id,
                            c.work_hrs
                        FROM
                            employee a
                        JOIN
                            salary_annexure b ON a.emp_id = b.emp_id
                        LEFT JOIN
                            attendence c ON c.emp_id = a.emp_id
                           
    JOIN shift_schedule s ON c.shift = s.id
                        WHERE   a.emp_id= '".$_GET["emp_id11"]."' and
                            c.indate  Like'%".$year.'-'.$month."%'  group by a.plant_id, a.isAppointment, a.id, a.emp_id,
                            department, designation,emp_name, b.take_home_salary, b.gross_salary, b.total_deductions, b.status,a.operator_category,b.id,c.work_hrs ";
                              
    
    
    
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

             
         
        
        
        
             $sql2 = "SELECT no_day 
                                    FROM leaveform 
                                    WHERE NOT EXISTS (
                                        SELECT 1 
                                        FROM attendence 
                                        WHERE indate = leave_from
                                    ) and leave_from like '%".$year.'-'.$month."%'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["leaves_of_month"]= $row2["no_day"];
                    }
                } else {
                    $days = 0;
                }
                
                ////////////////////////////////////////
                
                
              $sql2 = "SELECT SUM(a.monthly_emi) as EMI
                                FROM emp_loan a 
                                WHERE 
                                    a.emp_id = '".$row['emp_id']."' 
                                    AND '".$year."-".$month."'  BETWEEN a.emi_start_from AND a.emi_end
                                ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["EMI"]= $row2["EMI"];
                    }
                }  
        
        
     
        
        
         
             
            //  /////////////////////////////////////////////////
             
             
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
                        if($pkg > 400000){
                               $row['total'] =0;
                               $row['ot_total']=0;
                        }
                        else{
                             $row['total'] =$row2['total_count_of_2hrs_each'];
                        
                        if($rowx["operator_category"]=='Staff'){
                            if ($row['work_hrs'] > 8) {
                                    $row['ot_total'] = $row['total'] / 1.5;
                                }else{
                                     $row['ot_total']=0;
                                }
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                             if ($row['work_hrs'] > 11) {
                                     $row['ot_total']=$row['total'] * 1;
                                }else{
                                     $row['ot_total']=0;
                                }
                             
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
                
                
                
                    $date = isset($_GET['expected_releaving']) ? $_GET['expected_releaving'] : date('Y-m-d'); 
$time = strtotime($date);
$month = date("m", strtotime("-1 month", $time));
$year = date("Y", strtotime("-1 month", $time));

if ($month == 12) {
    $year--;
}
 
                
                
                
                 $working_days = calculateWorkingDaysInMonth(date("Y", $year),date("m", $month));
                 $row['working_days'] =$working_days;
                
                $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$rowx["emp_id"]."'
                         and month(indate)=$month and year(indate)=$year ";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        if($row['bal_leav'] > 0){
                            
                        $row['present_days'] = $row['working_days'];
                        }else{
                        $row['present_days'] = $row2["present_days"];

                        }
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
                
                
                
    $date = isset($_GET['expected_releaving']) ? $_GET['expected_releaving'] : date('Y-m-d'); 
$time = strtotime($date);
$month = date("m", strtotime("-1 month", $time));
$year = date("Y", strtotime("-1 month", $time));

if ($month == 12) {
    $year--;
} 
                
                
                
                
                
                
                
                
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
        
                $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowx["emp_id"]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row['total_leave'] = $row2["total_leave"];
                    }
                } else {
                    $row['total_leave'] = 0;
                }
              
                
                
                
                
        
                
         
         
              $date = isset($_GET['expected_releaving']) ? $_GET['expected_releaving'] : date('Y-m-d'); 
$time = strtotime($date);
$month = date("m", strtotime("-1 month", $time));
$year = date("Y", strtotime("-1 month", $time));

if ($month == 12) {
    $year--;
}       
                
                
                
                
                $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $month), date("Y", $year));
                $row['month_days'] =$month_days; 
               
             
                $paid_days = $row['present_days']*1 + $row['total_leave']*1 ;
                $row['paid_days'] = $paid_days;
                
                $total_salary_days = $paid_days+$working_days;
            //   $row['absent_days'] = $row['working_days'] - $row['present_days'] -$row['bal_leav'];
            
            
                $row['absent_days'] = max(0, $row['working_days'] - $row['present_days'] - $row['bal_leav']);

                
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
                     
                        
                        }
              
                } 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                
                 
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                // $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                $output[] = $row;
          
        }
    }
    echo json_encode($output);

    
   
   
    
  
}

   
   
   
   else if ($_GET["type"] == "get_resignation_dept") {
       
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE status='pending' and  department='".$_GET["department1"]."' and dept_resignation_status=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "getPendingResignations") {
       
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE status='pending' and dept_resignation_status!=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "getResignationCleanrance") {
       
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE status='pending' and dept_resignation_status!='' and resignation_status!=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "getPendingExitInterview") {
       
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE status='pending' and dept_resignation_status!='' and resignation_status!='Completed' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   else if ($_GET["type"] == "getPendingExperience") {
       
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE  resignation_status='Completed' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
       
   }
   
   



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















$conn->close();
?>