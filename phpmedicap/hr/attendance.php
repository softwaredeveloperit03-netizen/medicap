<?php

// echo('hiiiii');
//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 
    require '../db.php';
    // require 'SimpleXLSX.php';
    require '../token.php';
     require '../tcpdf/tcpdf.php';
    require '../phpmailer/class.phpmailer.php';
    require '../PHPExcel/Classes/PHPExcel.php';
    
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $entry_date1 = date("Y-m-d");
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
  //  $_GET["plant_id"] = "";
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
        // echo('hiiii33');
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	     $string = explode("$",$string);
	  //  $_GET["plant_id"] = $string[0];
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
    
    
    // echo('hiiii33');

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
    function checkHoliday($date){
          if(date('l', strtotime($date)) == 'Saturday'){
            return "Saturday";
          }else if(date('l', strtotime($date)) == 'Sunday'){
            return "Sunday";
          }else{
            $receivedDate = date('d M', strtotime($date));
        
            $holiday = array(
              '01 Jan' => 'New Year Day',
              '18 Jan' => 'Martin Luther King Day',
              '22 Feb' => 'Washington\'s Birthday',
              '05 Jul' => 'Independence Day',
              '11 Nov' => 'Veterans Day',
              '24 Dec' => 'Christmas Eve',
              '25 Dec' => 'Christmas Day',
              '31 Dec' => 'New Year Eve'
            );
        
            foreach($holiday as $key => $value){
              if($receivedDate == $key){
                return $value;
              }
            }
          }
    }
    
    
    
    if ($_GET["type"] == "getAttendance") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $entry_date = date("Y-m-d", $timestamp);
                $curdate=strtotime(date("d", $timestamp)."-".date("m", $timestamp)."-".date("Y", $timestamp));
                $days = cal_days_in_month(CAL_GREGORIAN,date("m", $timestamp),date("Y", $timestamp));
                for ($i = 1; $i <= $days; $i++) {
                    $temp = Array();
                    $temp["date"] = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    $temp["holiday"] = checkHoliday($i + "/" + date("m", $timestamp) + "/"+ date("Y", $timestamp));
                    
                    $today = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    
                    $mydate=strtotime($i."-".date("m", $timestamp)."-".date("Y", $timestamp));
                    
                    
                    if($curdate > $mydate) {
                        $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $temp["indate"] = $row1["indate"];
                                $temp["outdate"] = $row1["outdate"];
                                $temp["attendance"] = "P";
                            }
                        } else {
                            $temp["attendance"] = "A";
                        }
                    } else {
                        $temp["attendance"] = "";
                    }
                    
                    $output1[] = $temp;
                }
                $row["attendance"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
     else if ($_GET["type"] == "saveMachineAttendence") {
                 $json_obj = json_encode($input["records"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO machine_attendance (plant_id, emp_id, indate, intime,status)
       VALUES ('".$_GET["plant_id"]."','".$values["enrollId"]."','".$values["entry_date"]."',
       '".$values["entry_time"]."','".$values["iOStatus"]."')";
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
     
     else if ($_GET["type"] == "saveIndividualAttendance") {
         
         
    $timestamp = time();
    $entry_date = date("Y-m-d", $timestamp);
    $entry_time = date("H:i:s", $timestamp);
         
         
         
         
         
         
       
         if($input["pay_type"] == 'Present'){
             
             
  $sql00 = "SELECT *, a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a
          LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
          WHERE '".$input["date"]."' BETWEEN a.start_date AND a.end_date 
          AND a.Empolyee_Id = '".$_GET["empid"]."' 
          AND a.hr_status = 'Approved' 
          AND a.dept_head_status = 'Approved'";

$result00 = $conn->query($sql00);

if ($result00->num_rows > 0) {
    $Jadu = 11;
              $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '".$input["date"]."' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$_GET["empid"]."'";

} else {
    $Jadu = 0;
             $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '".$input["date"]."' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$_GET["empid"]."'";

}
 
             
             
             
             
             
             
             
             
             
             
             
             
            //  $sql = "SELECT *,a.Shift_Id as a_Shift_Id FROM shift_allocation a left join shift_schedule b on a.Shift_Id=b.id WHERE '".$input["date"]."' BETWEEN a.start_date AND a.end_date and a.Empolyee_Id='".$_GET["empid"]."'";
             
            
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
             $start_time = new DateTime($row["start_time"]);
            $intime = new DateTime($input["in_time"]);
            // $intime = new DateTime($row["intime"]);
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
            
            $inDateTime = new DateTime($input["in_date"] . ' ' . $input["in_time"]);
            $outDateTime = new DateTime($input["outdate"] . ' ' . $input["out_time"]);
            $interval = $inDateTime->diff($outDateTime);
            $wh= $interval->format('%H:%I');
             $shift=$row['a_Shift_Id'];
    //   echo $wh;
       
                
                
                 $shift=$row['a_Shift_Id'];
                // $output[] = $row;
                if($input["status"]=='A'){
                    
                       $sql1 = "INSERT INTO attendence (emp_id,indate,intime,inentry_by,shift,early_time,late_time,late_mark,
                      work_hrs,outdate,outtime,outentry_by,status) VALUES ('".$_GET["empid"]."','".$input["date"]."','".$input["in_time"]."','".$_GET["emp_id"]."','$shift','$early_time','$late_time','$latemark'
                      ,'$wh','".$input["date"]."','".$input["out_time"]."','".$_GET["emp_id"]."','Exit')";
                }else{
                     $sql1="update attendence set intime='".$input["in_time"]."',outtime='".$input["out_time"]."',outdate='".$input["outdate"]."',work_hrs='$wh',early_time='$early_time',late_time='$late_time',late_mark='$late_mark'  where id='".$input["id"]."'";
                }
            ($conn->query($sql1));    
            // if($conn->query($sql)) {    
            //     echo "{\"status\":\"success\"}";
            // } else {
            //     echo "{\"status\":\"failed\"}";
            // }
                
                
            }
              
        }
            
            
 
          
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
             
    }
     }
     
    else if ($_GET["type"] == "getMonthlyAttendance")
    {

        if ($_GET["search"] == "Day") {

        $output = Array();
       $sql = "SELECT * FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) 
        {
            while ($row = $result->fetch_assoc()) {
                $temp = Array();
                $temp["Emp Id"] = $row["emp_id"];
                $temp["Employee Name"] = $row["firstname"]." ".$row["lastname"];
                $mon =  $_GET["month"];
                $currentYear = date("Y");
                $currentMonth = date("m");
                $currentDay = date("d");

                    $today = $mon[0] . "-" . $mon[1] . "-" . $i;

                         $sql1 = "SELECT a.*,s.shift_name  FROM attendence a left join shift_schedule s ON a.shift = s.id WHERE a.indate='$mon' AND a.emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
            
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                   
                                $temp[$mon] = "P";
                                // Store the intime for the current day
                                $temp[$i . 'Intime'] = $row1['intime'];
                                $temp[$i . 'Outtime'] = $row1['outtime'];
                                $temp[$i . 'Work Hrs'] = $row1['work_hrs'];
                               $temp[$i . 'Shift Name'] = $row1['shift_name'];
                   
                            }
                        } else {
                            $temp[$mon] = "A";
                     
                        }

                $output[] = $temp;
            }
        }
        echo json_encode($output);
        
        
         
        }
    
        else if ($_GET["search"] == "Month") {
             
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                $temp = Array();
                $temp["Emp Id"] = $row["emp_id"];
                $temp["Employee Name"] = $row["firstname"]." ".$row["lastname"];
      
                
                $mon = explode("-", $_GET["month"]);
                $currentYear = date("Y");
                $currentMonth = date("m");
                $currentDay = date("d");
               
                if ($currentYear == $mon[0] && $currentMonth == $mon[1]) {
                  
                    $maxDay = $currentDay;
                } else {
                     
                    $maxDay = cal_days_in_month(CAL_GREGORIAN, $mon[1], $mon[0]);
                }
            
                for ($i = 1; $i <= $maxDay; $i++) {
                     
                        switch($i){
                             case 1:
                             case 2:
                             case 3:
                             case 4:
                             case 5:
                             case 6:
                             case 7:
                             case 8:
                             case 9:
                             $i = '0'.$i;
                             break;
                        } 
                     
                    
                    $today = $mon[0] . "-" . $mon[1] . "-" . $i;
                    $mydate = strtotime($i . "-" . $mon[1] . "-" . $mon[0]);
                    $intime='Intime';
            
                    if ($mydate <= strtotime(date("Y-m-d"))) {
                         $sql1 = "SELECT a.*,s.shift_name  FROM attendence a left join shift_schedule s ON a.shift = s.id WHERE a.indate='$today' AND a.emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
            
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                     if ($row1['day_type'] == 0) {
                    $temp[$today] = "P";
                    // Store the intime for the current day
                    $temp[$i . '_Intime'] = $row1['intime'];
                    $temp[$i . '_Outtime'] = $row1['outtime'];
                    $temp[$i . '_Work Hrs'] = $row1['work_hrs'];
                    $temp[$i . '_Shift Name'] = $row1['shift_name'];
                } else if ($row1['day_type'] == 1) {
                    $temp[$today] = "PL";
                } else if ($row1['day_type'] == 2) {
                    $temp[$today] = "WPL";
                }
                                
                            }
                        } else {
                            $temp[$today] = "A";
                     
                        }
                    } else {
                        $temp[$today] = "";
                    }
                }
                 
                $output[] = $temp;
                 
            }
        }
        echo json_encode($output);
        
        
        }
        else if ($_GET["search"] == "byEmp") {
             
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active' AND emp_id = '".$_GET["employee"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $temp = Array();
                $temp["Emp Id"] = $row["emp_id"];
                $temp["Employee Name"] = $row["firstname"]." ".$row["lastname"];
                
                
                
                
                
                                
if (isset($_GET["month"])) {
    
    
                $mon =  $_GET["month"];
                $currentYear = date("Y");
                $currentMonth = date("m");
                $currentDay = date("d");
                    
                    $today = $mon[0] . "-" . $mon[1] . "-" . $i;
                    
                         $sql1 = "SELECT a.*,s.shift_name  FROM attendence a left join shift_schedule s ON a.shift = s.id WHERE a.indate='$mon' AND a.emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
            
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                   
                                $temp[$mon] = "P";
                                // Store the intime for the current day
                                $temp[$i . 'Intime'] = $row1['intime'];
                                $temp[$i . 'Outtime'] = $row1['outtime'];
                                $temp[$i . 'Work Hrs'] = $row1['work_hrs'];
                               $temp[$i . 'Shift Name'] = $row1['shift_name'];
                   
                            }
                        } else {
                            $temp[$mon] = "A";
                     
                        }
    
    
 
} else {
    
    
     
                $mon = explode("-", $_GET["month"]);
                $currentYear = date("Y");
                $currentMonth = date("m");
                $currentDay = date("d");
               
                if ($currentYear == $mon[0] && $currentMonth == $mon[1]) {
                  
                    $maxDay = $currentDay;
                } else {
                     
                    $maxDay = cal_days_in_month(CAL_GREGORIAN, $mon[1], $mon[0]);
                }
            
                for ($i = 1; $i <= $maxDay; $i++) {
                    
                    
                    
                        switch($i){
                             case 1:
                             case 2:
                             case 3:
                             case 4:
                             case 5:
                             case 6:
                             case 7:
                             case 8:
                             case 9:
                             $i = '0'.$i;
                             break;
                        } 
                    
                    
                    
                    
                    
                    $today = $mon[0] . "-" . $mon[1] . "-" . $i;
                    $mydate = strtotime($i . "-" . $mon[1] . "-" . $mon[0]);
                    $intime='Intime';
            
                    if ($mydate <= strtotime(date("Y-m-d"))) {
                        
                         $sql1 = "SELECT a.*,s.shift_name  FROM attendence a left join shift_schedule s ON a.shift = s.id WHERE 
                         a.indate='$today' AND a.emp_id='".$row["emp_id"]."'";
                         
                        $result1 = $conn->query($sql1);
            
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                    
                    $temp[$today] = "P";
                    // Store the intime for the current day
                    $temp[$i . '_Intime'] = $row1['intime'];
                    $temp[$i . '_Outtime'] = $row1['outtime'];
                    $temp[$i . '_Work Hrs'] = $row1['work_hrs'];
                    $temp[$i . '_Shift Name'] = $row1['shift_name'];
                
                                     
                                
                                
                            }
                        } else {
                            $temp[$today] = "A";
                     
                        }
                    } else {
                        $temp[$today] = "";
                    }
                }
                
                
                
                
                
                
                
}
                
                
                
                
            
                $output[] = $temp;
            }
        }
        echo json_encode($output);
        
        
        }
        
        
        
        
        
        
        
        
        
    }
    else if ($_GET["type"] == "getMonthlyAttendance1") {
$output = array();

$department_name = $_GET["department_name"] ?? '';
$employeeId = $_GET["employeeId"] ?? '';
$month = $_GET["month"] ?? date('Y-m'); // Default to current month if not provided
$start_date = $_GET["start_date"] ?? ''; // From date filter
$end_date = $_GET["end_date"] ?? ''; // To date filter

   $sql = "SELECT * FROM employee WHERE status='active'  AND plant_id = '".$_GET["plant_id"]."'";

if (!empty($department_name)) {
    $sql .= " AND department LIKE '%$department_name%'";
}

if (!empty($employeeId)) {
    $sql .= " AND emp_id = '$employeeId'";
}

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        $temp["Emp Id"] = $row["emp_id"];
        $temp["Employee Name"] = $row["firstname"] . " " . $row["lastname"];

        // Construct the start and end dates based on the provided from date and to date filters
        $start_date = !empty($start_date) ? date("Y-m-d", strtotime($start_date)) : date("Y-m-01", strtotime($month));
        $end_date = !empty($end_date) ? date("Y-m-d", strtotime($end_date)) : date("Y-m-t", strtotime($month));

        // Query attendance data based on the provided filters and current month
        $sql_attendance = "SELECT a.*, s.shift_name  
                           FROM attendence a 
                           LEFT JOIN shift_schedule s ON a.shift = s.id 
                           WHERE a.emp_id='{$row['emp_id']}' 
                           AND a.indate BETWEEN '$start_date' AND '$end_date'";

        $result_attendance = $conn->query($sql_attendance);

        // Initialize attendance array for the employee
        $attendance = array();

        // If attendance data is found, store it in the attendance array
        if ($result_attendance->num_rows > 0) {
            while ($row_attendance = $result_attendance->fetch_assoc()) {
                $attendance[$row_attendance['indate']] = $row_attendance;
            }
        }

        // Loop through each day within the specified date range and populate the temp array with attendance data
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $date = date("Y-m-d", strtotime($current_date));

            // Check if attendance entry exists for the current date
            if (isset($attendance[$date])) {
                // If attendance entry exists, mark present and include additional information
                if($attendance[$date]['intime']==' ' or $attendance[$date]['outtime']==' '){
                    
                $temp[$date] = "A"; // Present
                }else{
                    
                $temp[$date] = "P"; // Present
                }
                $temp[$date . ' Intime'] = $attendance[$date]['intime'] ?? ''; // Intime
                $temp[$date . ' Outtime'] = $attendance[$date]['outtime'] ?? ''; // Outtime
                $temp[$date . ' Shift Name'] = $attendance[$date]['shift_name'] ?? ''; // Shift Name
            } else {
                // If no attendance entry exists, mark absent and leave other fields empty
                $temp[$date] = "A"; // Absent
                $temp[$date . ' Intime'] = '-'; // Intime
                $temp[$date . ' Outtime'] = '-'; // Outtime
                $temp[$date . ' Shift Name'] = '-'; // Shift Name
            }

            // Move to the next date
            $current_date = date("Y-m-d", strtotime($current_date . "+1 day"));
        }

        // Add temp array to output array
        $output[] = $temp;
    }
}

// Return JSON response
echo json_encode($output);
        
    }
    
else if ($_GET["type"] == "getMonthlyAttendance1ForBuddhiJivi") { 
    
    
    
    
    $output = array();

$department_name = $_GET["department_name"] ?? '';
$employeeId = $_GET["employeeId"] ?? '';
$month = $_GET["month"] ?? date('Y-m');  
$start_date = $_GET["start_date"] ?? '';  
$end_date = $_GET["end_date"] ?? ''; 
$plant_id = $_GET["plant_id"] ?? '';

if (empty($plant_id)) {
    echo json_encode(['error' => 'Plant ID is required.']);
    exit;
}
 
// Construct the start and end dates based on the provided from date and to date filters
$start_date = !empty($start_date) ? date("Y-m-d", strtotime($start_date)) : date("Y-m-01", strtotime($month));
$end_date = !empty($end_date) ? date("Y-m-d", strtotime($end_date)) : date("Y-m-t", strtotime($month));

 

// Loop through each day within the specified date range
$current_date = $start_date;
while ($current_date <= $end_date) {
    $date = date("Y-m-d", strtotime($current_date));

    // Fetch attendance data for all employees for the current date
    $sql_attendance = "SELECT a.*, e.firstname, e.lastname,e.department ,s.shift_name  
                       FROM attendence a 
                       JOIN employee e ON a.emp_id = e.emp_id
                       LEFT JOIN shift_schedule s ON a.shift = s.id 
                       WHERE a.indate = '$date' AND e.status='active'  AND e.plant_id = '$plant_id'";
                       
                       
                    if (!empty($department_name)) {
                        $sql_attendance .= " AND e.department LIKE '%$department_name%'";
                    }
                    
                    if (!empty($employeeId)) {
                        $sql_attendance .= " AND e.emp_id = '$employeeId'";
                    }

    $result_attendance = $conn->query($sql_attendance);

    // Populate attendance data for each employee
    while ($row_attendance = $result_attendance->fetch_assoc()) {
        $emp_id = $row_attendance['emp_id'];
        $employee_name = $row_attendance['firstname'] . " " . $row_attendance['lastname'];

        $attendance_entry = [
            "status" => empty($row_attendance['intime']) || empty($row_attendance['outtime']) ? "A" : "P",
            "date" => $date,
            "empName" => "$employee_name ($emp_id)",
            "intime" => $row_attendance['intime'] ?? '-',
            "outtime" => $row_attendance['outtime'] ?? '-',
            "shift_name" => $row_attendance['shift_name'] ?? '-',
            "department" => $row_attendance['department'] ?? '-'
        ];

        // Append to the output array
        $output[] = $attendance_entry;
    }

    // Move to the next date
    $current_date = date("Y-m-d", strtotime($current_date . "+1 day"));
}

// Return JSON response
echo json_encode($output);

    
    
    
    
    
}
else if ($_GET["type"] == "HOgetMonthlyAttendance1ForBuddhiJivi") { 
    
    
    
    
    $output = array();

$department_name = $_GET["department_name"] ?? '';
$employeeId = $_GET["employeeId"] ?? '';
$month = $_GET["month"] ?? date('Y-m');  
$start_date = $_GET["start_date"] ?? '';  
$end_date = $_GET["end_date"] ?? ''; 
$plant_id = $_GET["plantID"] ?? '';

if (empty($plant_id)) {
    echo json_encode(['error' => 'Plant ID is required.']);
    exit;
}
 
// Construct the start and end dates based on the provided from date and to date filters
$start_date = !empty($start_date) ? date("Y-m-d", strtotime($start_date)) : date("Y-m-01", strtotime($month));
$end_date = !empty($end_date) ? date("Y-m-d", strtotime($end_date)) : date("Y-m-t", strtotime($month));

 

// Loop through each day within the specified date range
$current_date = $start_date;
while ($current_date <= $end_date) {
    $date = date("Y-m-d", strtotime($current_date));

    // Fetch attendance data for all employees for the current date
    $sql_attendance = "SELECT a.*, e.firstname, e.lastname,e.department ,s.shift_name  
                       FROM attendence a 
                       JOIN employee e ON a.emp_id = e.emp_id
                       LEFT JOIN shift_schedule s ON a.shift = s.id 
                       WHERE a.indate = '$date' AND e.status='active'  AND e.plant_id = '$plant_id'";
                       
                       
                    if (!empty($department_name)) {
                        $sql_attendance .= " AND e.department LIKE '%$department_name%'";
                    }
                    
                    if (!empty($employeeId)) {
                        $sql_attendance .= " AND e.emp_id = '$employeeId'";
                    }

    $result_attendance = $conn->query($sql_attendance);

    // Populate attendance data for each employee
    while ($row_attendance = $result_attendance->fetch_assoc()) {
        $emp_id = $row_attendance['emp_id'];
        $employee_name = $row_attendance['firstname'] . " " . $row_attendance['lastname'];

        $attendance_entry = [
            "status" => empty($row_attendance['intime']) || empty($row_attendance['outtime']) ? "A" : "P",
            "date" => $date,
            "empName" => "$employee_name ($emp_id)",
            "intime" => $row_attendance['intime'] ?? '-',
            "outtime" => $row_attendance['outtime'] ?? '-',
            "shift_name" => $row_attendance['shift_name'] ?? '-',
            "department" => $row_attendance['department'] ?? '-'
        ];

        // Append to the output array
        $output[] = $attendance_entry;
    }

    // Move to the next date
    $current_date = date("Y-m-d", strtotime($current_date . "+1 day"));
}

// Return JSON response
echo json_encode($output);

    
    
    
    
    
}




    else if ($_GET["type"] == "HOgetMonthlyAttendance1") {
$output = array();

$department_name = $_GET["department_name"] ?? '';
$employeeId = $_GET["employeeId"] ?? '';
$month = $_GET["month"] ?? date('Y-m'); // Default to current month if not provided
$start_date = $_GET["start_date"] ?? ''; // From date filter
$end_date = $_GET["end_date"] ?? ''; // To date filter

$sql = "SELECT * FROM employee WHERE status='active' AND plant_id = '".$_GET["plantID"]."'";

if (!empty($department_name)) {
    $sql .= " AND department LIKE '%$department_name%'";
}

if (!empty($employeeId)) {
    $sql .= " AND emp_id = '$employeeId'";
}

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        $temp["Emp Id"] = $row["emp_id"];
        $temp["Employee Name"] = $row["firstname"] . " " . $row["lastname"];

        // Construct the start and end dates based on the provided from date and to date filters
        $start_date = !empty($start_date) ? date("Y-m-d", strtotime($start_date)) : date("Y-m-01", strtotime($month));
        $end_date = !empty($end_date) ? date("Y-m-d", strtotime($end_date)) : date("Y-m-t", strtotime($month));

        // Query attendance data based on the provided filters and current month
        $sql_attendance = "SELECT a.*, s.shift_name  
                           FROM attendence a 
                           LEFT JOIN shift_schedule s ON a.shift = s.id 
                           WHERE a.emp_id='{$row['emp_id']}' 
                           AND a.indate BETWEEN '$start_date' AND '$end_date'";

        $result_attendance = $conn->query($sql_attendance);

        // Initialize attendance array for the employee
        $attendance = array();

        // If attendance data is found, store it in the attendance array
        if ($result_attendance->num_rows > 0) {
            while ($row_attendance = $result_attendance->fetch_assoc()) {
                $attendance[$row_attendance['indate']] = $row_attendance;
            }
        }

        // Loop through each day within the specified date range and populate the temp array with attendance data
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $date = date("Y-m-d", strtotime($current_date));

            // Check if attendance entry exists for the current date
            if (isset($attendance[$date])) {
                // If attendance entry exists, mark present and include additional information
                if($attendance[$date]['intime']==' ' or $attendance[$date]['outtime']==' '){
                    
                $temp[$date] = "A"; // Present
                }else{
                    
                $temp[$date] = "P"; // Present
                }
                $temp[$date . ' Intime'] = $attendance[$date]['intime'] ?? ''; // Intime
                $temp[$date . ' Outtime'] = $attendance[$date]['outtime'] ?? ''; // Outtime
                $temp[$date . ' Shift Name'] = $attendance[$date]['shift_name'] ?? ''; // Shift Name
            } else {
                // If no attendance entry exists, mark absent and leave other fields empty
                $temp[$date] = "A"; // Absent
                $temp[$date . ' Intime'] = '-'; // Intime
                $temp[$date . ' Outtime'] = '-'; // Outtime
                $temp[$date . ' Shift Name'] = '-'; // Shift Name
            }

            // Move to the next date
            $current_date = date("Y-m-d", strtotime($current_date . "+1 day"));
        }

        // Add temp array to output array
        $output[] = $temp;
    }
}

// Return JSON response
echo json_encode($output);
        
    }

     
     
     
     else if ($_GET["type"] == "deleteIndividualAttendance") {
        $sql = "DELETE FROM attendence WHERE id = '".$input["id"]."'";

             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
     }
     else if ($_GET["type"] == "getIndividualAttendace") {
         $output = Array();
$originalEarlier = new DateTime($_GET["from_date"]); // Store the original start date
$later = new DateTime($_GET["to_date"]);
$diff = $later->diff($originalEarlier)->days;
$fromdate = $originalEarlier->format('Y-m-d');
$todate = $later->format('Y-m-d');

for ($i = 0; $i <= $diff; $i++) {
    $temp = Array();
    $earlier = clone $originalEarlier; 
    $currentDate = $earlier->add(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
    
    
    
    
       $sql00 = "SELECT *, a.Shift_Id as a_Shift_Id FROM shift_chnge_allocation a
          LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
          WHERE '$currentDate' BETWEEN a.start_date AND a.end_date 
          AND a.Empolyee_Id = '".$_GET["emp_code"]."' 
          AND a.hr_status = 'Approved' 
          AND a.dept_head_status = 'Approved'";

$result00 = $conn->query($sql00);

if ($result00->num_rows > 0) {
    $Jadu = 11;
                 $sql = "SELECT *,a.Shift_Id as a_Shift_Id,c.id as att_id FROM shift_chnge_allocation a left join shift_schedule b on a.Shift_Id=b.id left join attendence c on c.emp_id=a.Empolyee_Id  WHERE '$currentDate' BETWEEN a.start_date AND a.end_date and   c.indate='$currentDate'  and a.Empolyee_Id='".$_GET["emp_code"]."'";

} else {
    $Jadu = 0;
                    $sql = "SELECT *,a.Shift_Id as a_Shift_Id,c.id as att_id FROM shift_allocation a left join shift_schedule b on a.Shift_Id=b.id left join attendence c on c.emp_id=a.Empolyee_Id  WHERE '$currentDate' BETWEEN a.start_date AND a.end_date  and   c.indate='$currentDate' AND a.end_date and a.Empolyee_Id='".$_GET["emp_code"]."'";

}
    
     
    
    
        // $sql = "SELECT *,attendence.id as a_id FROM attendence LEFT JOIN shift_schedule s ON attendence.shift = s.id
        //     WHERE attendence.indate like '%".$currentDate."%' AND attendence.emp_id = '".$_GET["emp_code"]."'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row1 = $result->fetch_assoc()) {
            $temp["id"] = $row1["a_id"];
            $temp["intime"] = $row1["intime"];
            $temp["outtime"] = $row1["outtime"];
            $temp["att_id"] = $row1["att_id"];
            $temp["date"] = $currentDate;   
            $temp["outdate"] = $row1["outdate"];   
            if( $temp["intime"]==' ' or  $temp["outtime"]==' '){
                
            $temp["status"] = "A";
            }else{
                
            $temp["status"] = "P";
            }
            $temp["shift"] = $row1["shift_name"];
            $temp["shift_start"] = $row1["start_time"];
            $temp["shift_end"] = $row1["end_time"];
             $temp["duration1"] = $row1["duration"];
             $temp['wh'] = $row1["work_hrs"];
$duration = $row1["duration"]; // Assuming this is a string in the format "HH:MM"
list($hours, $minutes) = explode(':', $duration);

// Convert hours and minutes to integers
$hours = intval($hours);
$minutes = intval($minutes);

// Convert hours and minutes to seconds
$totalSeconds = ($hours * 3600) + ($minutes * 60);

// Now you can use $totalSeconds as needed


// Calculate hours, minutes, and seconds
$hours = floor($totalSeconds / 3600);
$minutes = floor(($totalSeconds % 3600) / 60);
$seconds = $totalSeconds % 60;

// Format the duration
$formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

$temp["duration"] = $formattedDuration;



            
            
            $start_time = new DateTime($row1["start_time"]);
            $intime = new DateTime($row1["intime"]);
            
            
            $time1 = new DateTime($temp["intime"]);
            $time2 = new DateTime($temp["outtime"]);
            $interval = $time1->diff($time2);
            // $temp['wh'] = $interval->format('%H:%I:%S');
       
       
       
       
        $temp["early_arrival"] =  $row1["early_time"];;
        $temp["late_arrival"] = $row1["late_time"];
              
              $outTime = new DateTime($temp["outtime"]);
              $endTime = new DateTime($temp["shift_end"]);      
              
              $ot_diff = $outTime->diff($endTime);
               
              
            $temp["ot_hrs1"] = $ot_diff->format('%H:%I:%S');
            
            if($temp['wh'] > $temp["duration"]){
                $temp["ot_hrs"]=$temp["ot_hrs1"];
            }else{
                $temp["ot_hrs"]=0;
            }
            
            
            
            $time1 = new DateTime($temp["intime"]);
            $time2 = new DateTime($temp["outtime"]);
            $interval = $time1->diff($time2);
            // $temp['wh'] = $interval->format('%H:%I:%S');
            $output[] = $temp;
        }
    } else {
        
    //      $sql2 = "SELECT * from holiday_date  WHERE holiday_date like '%".$currentDate."%'";
    
    // $result2 = $conn->query($sql2);
    
    // if ($result2->num_rows > 0) {
    //     while ($row12 = $result2->fetch_assoc()) {
    //         $temp["id"] = $row12["a_id"];
    //         $temp["intime"] = $row12["intime"];
    //         $temp["outtime"] = $row12["outtime"];
    //         $temp["date"] = $currentDate;   
        
        
        // No matching records found, add an empty record with status "A"
        $temp = Array();
        $temp["id"] = "";
        $temp["intime"] = "";
        $temp["outtime"] = "";
        $temp["date"] = $currentDate;
        $temp["status"] = "A";
        $temp["shift"] = "";
        $temp["shift_start"] = "";
        $temp["shift_end"] = "";
        $temp["wh"] = "";
        $output[] = $temp;
    //     }
    // }
    }
}
echo json_encode($output);
} 
    
// echo('hiiiii2');
      if ($_GET["type"] == "saveMultipleAttendanceMeha"){
        echo('hi');
        $employeeData = json_decode(json_encode($input['employeeData']), true); // Ensure it's an array

foreach ($employeeData as $employee) {
    echo('Bi');
    $nameParts = explode("-", $employee['name'], 2);
    $emp_id = trim($nameParts[0]);
    $emp_name = isset($nameParts[1]) ? trim($nameParts[1]) : '';

    foreach ($employee['records'] as $record) {
        $date = date("Y-m-d", strtotime($record['date'])); // Convert to MySQL date format
        $inTime = $record['inTime'];
        $outTime = $record['outTime'];

        // Check shift change allocation
        $sql00 = "SELECT *, a.Shift_Id as a_Shift_Id 
                  FROM shift_chnge_allocation a 
                  LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
                  WHERE '$date' BETWEEN a.start_date AND a.end_date 
                  AND a.Empolyee_Id = '$emp_id' 
                  AND a.hr_status = 'Approved' 
                  AND a.dept_head_status = 'Approved'";

        $result00 = $conn->query($sql00);
        $Jadu = ($result00->num_rows > 0) ? 1 : 0;

        if ($Jadu == 1) {
            $sql = "SELECT *, a.Shift_Id as a_Shift_Id 
                    FROM shift_chnge_allocation a 
                    LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
                    WHERE '$date' BETWEEN a.start_date AND a.end_date 
                    AND a.Empolyee_Id = '$emp_id'";
        } else {
            $sql = "SELECT *, a.Shift_Id as a_Shift_Id 
                    FROM shift_allocation a 
                    LEFT JOIN shift_schedule b ON a.Shift_Id = b.id 
                    WHERE '$date' BETWEEN a.start_date AND a.end_date 
                    AND a.Empolyee_Id = '$emp_id'";
        }

        $result = $conn->query($sql);
        $late_time = "00:00:00";
        $early_time = "00:00:00";
        $latemark = 0;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                 $shift=$row['a_Shift_Id'];
                $start_time = new DateTime($row["start_time"]);
                $intime = new DateTime($inTime);

                $time_difference = $start_time->diff($intime);
                $minutes_difference = $time_difference->h * 60 + $time_difference->i;

                if ($intime < $start_time) {
                    $early_time = $time_difference->format('%H:%I:%S');
                } elseif ($intime > $start_time) {
                    $late_time = $time_difference->format('%H:%I:%S');
                    $latemark = ($minutes_difference > 20) ? 1 : 0;
                }
            }
        }

        // Insert into attendance
        $sqlInsert = "INSERT INTO attendence (emp_id, emp_name, indate, intime, outtime, late_time, early_time, late_mark,outdate,shift) 
                      VALUES ('$emp_id', '$emp_name', '$date', '$inTime', '$outTime', '$late_time', '$early_time', '$latemark', '$date','$shift')";

        if ($conn->query($sqlInsert) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sqlInsert . "<br>" . $conn->error;
        }
    }
}

    }
    else if ($_GET["type"] == "saveMultipleAttendance"){
        $input = $_POST;
        // print_r($input);
    // print_r($_GET);exit;
        
        //not getting plant_id that's why static for now
        $_GET["plant_id"] = '28';
        $returnArr = array();
        $output = array();
            if (isset($_FILES["attendance_file"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/attendance_excel/".$rand_no.basename($_FILES["attendance_file"]["name"]);
                move_uploaded_file($_FILES["attendance_file"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["attendance_file"]["name"]);
                $excelFile = "/upload/attendance_excel/".$file;
    
                $inputFileName = "../upload/attendance_excel/".$file;
        
        		//  Read your Excel workbook
        		try {
        		    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        		    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        		    $objPHPExcel = $objReader->load($inputFileName);
        		} catch(Exception $e) {
        		    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
        		}
        		$sheet = $objPHPExcel->getSheet(0); 
        		$highestRow = $sheet->getHighestRow(); 
        		$highestColumn = $sheet->getHighestColumn();
        		$Exceldata =  $objPHPExcel->getActiveSheet()->toArray();
        		
        		array_shift($Exceldata);
                $Exceldata = array_values($Exceldata);
                // print_r($Exceldata);exit;
                
        		foreach($Exceldata as $rowdata) {
        		    $empId=$rowdata[0];
        		  //  print_r($rowdata);exit;
            	    $indate = $rowdata[1];
                    $newIndate = date("Y-m-d", strtotime($indate));
                    $outdate = $rowdata[6];
                    $newOutdate = date("Y-m-d", strtotime($outdate));
                    $month=date("m",strtotime($newIndate));
                    // print_r($month);exit;
                    $year=date("Y",strtotime($newIndate));
                    $newOutdate = date("Y-m-d", strtotime($outdate));
                    $sql1 = "SELECT * FROM employee WHERE id='".$rowdata[0]."'";
        		   $sql = "INSERT INTO attendence (emp_id,indate,outdate,intime,outtime,inentry_by,outentry_by) VALUES 
                ('".$rowdata[0]."','".$newIndate."','".$newOutdate."','".$rowdata[2]."','".$rowdata[3]."','".$rowdata[4]."','".$rowdata[5]."')"; 
                    if ($conn->query($sql) === TRUE) {
                      $last_id = $conn->insert_id;
                        $sql2 = " SELECT attendence.*,employee.emp_name,firstname,department FROM attendence LEFT JOIN employee ON attendence.emp_id = employee.emp_id WHERE attendence.id =".$last_id."";                  
                        $result = $conn->query($sql2);
                        
                      if ($result->num_rows > 0){
                          array_push($returnArr,$result->fetch_assoc());
                          
                           
                              $sql1="select x.* from (SELECT a.plant_id,a.isAppointment,a.id,a.emp_id,department,designation, CONCAT( firstname,' ',middlename,' ', lastname ) AS emp_name,b.take_home_salary,b.id as salary_annexure_id  FROM employee a join salary_annexure b on a.id = b.emp_id  WHERE a.status='active' AND a.plant_id = '28' AND a.emp_id='".$rowdata[0]."') x";
                              $result = $conn->query($sql1);
                                if ($result->num_rows > 0) 
                                {
                                    while($rowx = $result->fetch_assoc()) {
                                        $row=[];
                                        $row['emp_id']=$rowx["emp_id"];
                                        $row['emp_name']=$rowx["emp_name"];
                                        $row['department']=$rowx["department"];
                                        $row['designation']=$rowx["designation"];
                                        $row['take_home_salary']=$rowx["take_home_salary"];
                                        $row['salary_slip_id']=$rowx["salary_slip_id"];
                                        $row['salary_annexure_id']=$rowx["salary_annexure_id"];
                                    }
                                }
                              $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$rowdata[0]."'
                        and month(indate)=$month and year(indate)=$year ";
               
              $result2 = $conn->query($sql2);
              if ($result2->num_rows > 0) {
                  while ($row2 = $result2->fetch_assoc()) {
                      $row['present_days'] = $row2["present_days"];
                  }
              } else {
                  $row['present_days'] = 0;
              }
              $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$rowdata[0]."' AND leave_type='Short Leave' AND status='pending'";
              $result2 = $conn->query($sql2);
              if ($result2->num_rows > 0) {
                  while ($row2 = $result2->fetch_assoc()) {
                      $row['shortleave'] = $row2["shortleave"];
                  }
              } else {
                  $shortleave = 0;
              }
              $sql2 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$rowdata[0]."' AND leave_type='Half Day' AND status='pending'";
              $result2 = $conn->query($sql2);
              if ($result2->num_rows > 0) {
                  while ($row2 = $result2->fetch_assoc()) {
                      $row['halfday'] = $row2["halfday"];
                  }
              } else {
                  $row['halfday'] = 0;
              }
               
              $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowdata[0]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
              $result2 = $conn->query($sql2);
              if ($result2->num_rows > 0) {
                  while ($row2 = $result2->fetch_assoc()) {
                      $row['total_leave'] = $row2["total_leave"];
                  }
              } else {
                  $row['total_leave'] = 0;
              }
              $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $row['month_days'] =$month_days; 
                $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                 $row['working_days'] =$working_days;
             
                 $total_salary_days = $paid_days+$working_days;
                 $row['absent_days'] = $row['working_days'] - $row['present_days'];

                $paid_days = $working_days - $row['absent_days'] ;
                $row['paid_days'] = $paid_days;
                
                $earnings =0;
                $deductions=0;
                $earningsList= Array();
                $deductionsList= Array();
                 $sql2 = "select * from salary_annexure_details WHERE salary_annexure_id='".$row['salary_annexure_id']."' order by salary_group ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row21 = $result2->fetch_assoc()) 
                        {
                            // print_r($row21["per_month"]);print_r($working_days);print_r($paid_days);exit;
                            $temp=[];
                             $temp1=[];
                          if($row21['salary_group']!='Earnings'){        
                              $deductionsList[$row21['description']]  = $row21["per_month"];
                              $deductions=$deductions+ $row21["per_month"];
                          }else{
                              $amount = (($row21["per_month"]/$working_days)*$paid_days);
                            //   print_r($amount);exit;
                              $earningsList[$row21['description']]  = number_format((float)$amount, 2, '.', '');
                              $earnings=$earnings+(number_format((float)$amount, 2, '.', ''));
                          }
                        }
                   
                } 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                 $salArr = 
                    array('emp_id'=>$rowdata[0],
                    'emp_name'=>$row['emp_name'],
                    'department'=> $row['department'],
                    'designation'=> $row['designation'],
                    'present_days'=>$row['present_days'],
                    'absent_days'=>$row['absent_days'],
                    'take_home_salary'=>$row['take_home_salary'],
                    'salary_slip_id'=>$row['salary_slip_id'],
                    'Basic Salary'=>$earningsList['Basic Salary'],
                    'HRA'=>$earningsList['HRA'],
                    'other'=>$earningsList['other'],
                    'Special Allowance'=>$earningsList['Special Allowance'],
                    'earned_gross'=>$row['earned_gross'],
                    'PF Contribution By Employer'=>$deductionsList['PF Contribution By Employer'],
                    'ESI Contribution By Employer'=>$deductionsList['ESI Contribution By Employer'],
                    'Bonus'=>$deductionsList['Bonus'], 
                    'Gratuity'=>$deductionsList['Gratuity'],
                    'mediclaim'=>'300.00',
                    'Professional Tax'=>$deductionsList['Professional Tax'],
                    'PF Contribution By Employee'=>$deductionsList['PF Contribution By Employee'],
                    'ESI Contribution By Employee'=>$deductionsList['ESI Contribution By Employee'],
                    'deduction'=>$row['deduction'],
                    'inhand'=> $row['inhand']);
                    //  print_r($salArr);exit;
         $sql1 = "SELECT * FROM emp_salary_details WHERE month = '".$month."' AND emp_code = '".$rowdata[0]."'";
         $result = $conn->query($sql1);
        //  print_r($result->num_rows);exit;
        if($result->num_rows > 0)
        {
             $sql ="UPDATE emp_salary_details SET plant_id ='".$_GET["plant_id1"]."', month ='".$month."', year='".$year."', emp_code='".$rowdata[0]."', salary_details='".json_encode($salArr)."', entry_by='".$_GET["emp_id"]."', entry_date='".$newIndate."' WHERE month ='".$month."'AND year='".$year."'";
       
        if($conn->query($sql)===TRUE){
            $ouput['empSalStatus']="success";
    	}
    	else {
    		$ouput['empSalStatus']="Failed";
    	}
      
            
        }
        else
        {
         $sql ="INSERT INTO emp_salary_details(plant_id, month, year, emp_code, salary_details, entry_by, entry_date) 
        VALUES ('".$_GET["plant_id1"]."','".$month."','".$year."','".$rowdata[0]."',
        '".json_encode($salArr)."','".$_GET["emp_id"]."','".$newIndate."')";
      
        if($conn->query($sql)===TRUE){
		   $ouput['empSalStatus']="success";
    	}
    	else {
    		$ouput['empSalStatus']="Failed";
    	}
        }
        
                      }
                    }
                    array_push($output,$row);
                    
        		}
                
        
                if (!empty($returnArr)) {
                    
                    $output['status'] == "success";
                    $output['data'] = $returnArr;
                      
                   
                } else {
                    // echo "{\"status\":\"".$conn->error."\"}";
                    $res['status'] = $conn->error;
                }
            } else {
                // echo "{\"status\":\"error:file not found\"}";
                $res['status'] = 'error:file not found';
            }
            
            echo json_encode($output);

    }
    else if ($_GET["type"] == "saveMultipleAttendance_amardeep"){
        $input = $_POST;
        // print_r($input);
    // print_r($_GET);exit;
        
        //not getting plant_id that's why static for now
        // $_GET["plant_id"] = '28';
        $returnArr = array();
        $output = array();
            if (isset($_FILES["attendance_file"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/attendance_excel/".$rand_no.basename($_FILES["attendance_file"]["name"]);
                move_uploaded_file($_FILES["attendance_file"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["attendance_file"]["name"]);
                $excelFile = "/upload/attendance_excel/".$file;
    
                $inputFileName = "../upload/attendance_excel/".$file;
        
        		//  Read your Excel workbook
        		try {
        		    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        		    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        		    $objPHPExcel = $objReader->load($inputFileName);
        		} catch(Exception $e) {
        		    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
        		}
        		$sheet = $objPHPExcel->getSheet(0); 
        		$highestRow = $sheet->getHighestRow(); 
        		$highestColumn = $sheet->getHighestColumn();
        		$Exceldata =  $objPHPExcel->getActiveSheet()->toArray();
        		
        		array_shift($Exceldata);
                $Exceldata = array_values($Exceldata);
                // print_r($Exceldata);exit;
                
        		foreach($Exceldata as $rowdata) {
        		    $empId=$rowdata[0];
        		  //  print_r($rowdata);exit;
            	    $indate = $rowdata[1];
                    $newIndate = date("Y-m-d", strtotime($indate));
                    $outdate = $rowdata[6];
                    $newOutdate = date("Y-m-d", strtotime($outdate));
                    $month=date("m",strtotime($newIndate));
                    // print_r($month);exit;
                    $year=date("Y",strtotime($newIndate));
                    $newOutdate = date("Y-m-d", strtotime($outdate));
                    $sql1 = "SELECT * FROM employee WHERE id='".$rowdata[0]."'";
        		   $sql = "INSERT INTO attendence (emp_id,indate,outdate,intime,outtime,inentry_by,outentry_by) VALUES 
                ('".$rowdata[0]."','".$newIndate."','".$newOutdate."','".$rowdata[2]."','".$rowdata[3]."','".$rowdata[4]."','".$rowdata[5]."')"; 
                    if ($conn->query($sql) === TRUE) {
                      $last_id = $conn->insert_id;
                        $sql2 = " SELECT attendence.*,employee.emp_name,firstname,department FROM attendence LEFT JOIN employee ON attendence.emp_id = employee.emp_id WHERE attendence.id =".$last_id."";                  
                        $result = $conn->query($sql2);
                        
                      if ($result->num_rows > 0){
                          array_push($returnArr,$result->fetch_assoc());
                          
                           
                               $sql1="select x.* from (SELECT a.plant_id,a.isAppointment,a.id,a.emp_id,department,designation, CONCAT( firstname,' ',middlename,' ', lastname ) AS emp_name,b.take_home_salary,b.id as salary_annexure_id  FROM employee a join salary_annexure b on a.id = b.emp_id  WHERE a.status='active' AND a.plant_id = '28' AND a.emp_id='".$rowdata[0]."') x";
                               $result = $conn->query($sql1);
                                if ($result->num_rows > 0) 
                                {
                                    while($rowx = $result->fetch_assoc()) {
                                        $row=[];
                                        $row['emp_id']=$rowx["emp_id"];
                                        $row['emp_name']=$rowx["emp_name"];
                                        $row['department']=$rowx["department"];
                                        $row['designation']=$rowx["designation"];
                                        $row['take_home_salary']=$rowx["take_home_salary"];
                                        $row['salary_slip_id']=$rowx["salary_slip_id"];
                                        $row['salary_annexure_id']=$rowx["salary_annexure_id"];
                                    }
                                }
                              $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$rowdata[0]."'
                        and month(indate)=$month and year(indate)=$year ";
               
               $result2 = $conn->query($sql2);
               if ($result2->num_rows > 0) {
                   while ($row2 = $result2->fetch_assoc()) {
                       $row['present_days'] = $row2["present_days"];
                   }
               } else {
                   $row['present_days'] = 0;
               }
               $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$rowdata[0]."' AND leave_type='Short Leave' AND status='pending'";
               $result2 = $conn->query($sql2);
               if ($result2->num_rows > 0) {
                   while ($row2 = $result2->fetch_assoc()) {
                       $row['shortleave'] = $row2["shortleave"];
                   }
               } else {
                   $shortleave = 0;
               }
               $sql2 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$rowdata[0]."' AND leave_type='Half Day' AND status='pending'";
               $result2 = $conn->query($sql2);
               if ($result2->num_rows > 0) {
                   while ($row2 = $result2->fetch_assoc()) {
                       $row['halfday'] = $row2["halfday"];
                   }
               } else {
                   $row['halfday'] = 0;
               }
               
               $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='".$rowdata[0]."' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
               $result2 = $conn->query($sql2);
               if ($result2->num_rows > 0) {
                   while ($row2 = $result2->fetch_assoc()) {
                       $row['total_leave'] = $row2["total_leave"];
                   }
               } else {
                   $row['total_leave'] = 0;
               }
               $month_days=cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                $row['month_days'] =$month_days; 
                $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                 $row['working_days'] =$working_days;
             
                 $total_salary_days = $paid_days+$working_days;
                 $row['absent_days'] = $row['working_days'] - $row['present_days'];

                $paid_days = $working_days - $row['absent_days'] ;
                $row['paid_days'] = $paid_days;
                
                $earnings =0;
                $deductions=0;
                $earningsList= Array();
                $deductionsList= Array();
                 $sql2 = "select * from salary_annexure_details WHERE salary_annexure_id='".$row['salary_annexure_id']."' order by salary_group ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row21 = $result2->fetch_assoc()) 
                        {
                            // print_r($row21["per_month"]);print_r($working_days);print_r($paid_days);exit;
                            $temp=[];
                             $temp1=[];
                          if($row21['salary_group']!='Earnings'){        
                               $deductionsList[$row21['description']]  = $row21["per_month"];
                               $deductions=$deductions+ $row21["per_month"];
                          }else{
                              $amount = (($row21["per_month"]/$working_days)*$paid_days);
                            //   print_r($amount);exit;
                               $earningsList[$row21['description']]  = number_format((float)$amount, 2, '.', '');
                               $earnings=$earnings+(number_format((float)$amount, 2, '.', ''));
                          }
                        }
                   
                } 
                $row['earningsList'] = $earningsList;
                $row['deductions'] = $deductionsList;
                $row['earned_gross'] =number_format((float)$earnings, 2, '.', '');
                $row['deduction'] =number_format((float)$deductions, 2, '.', '');// $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                $row['inhand'] = number_format((float)$earnings -$deductions, 2, '.', '');// $row['earned_gross'] - $row['deduction'];
                 $salArr = 
                    array('emp_id'=>$rowdata[0],
                    'emp_name'=>$row['emp_name'],
                    'department'=> $row['department'],
                    'designation'=> $row['designation'],
                    'present_days'=>$row['present_days'],
                    'absent_days'=>$row['absent_days'],
                    'take_home_salary'=>$row['take_home_salary'],
                    'salary_slip_id'=>$row['salary_slip_id'],
                    'Basic Salary'=>$earningsList['Basic Salary'],
                    'HRA'=>$earningsList['HRA'],
                    'other'=>$earningsList['other'],
                    'Special Allowance'=>$earningsList['Special Allowance'],
                    'earned_gross'=>$row['earned_gross'],
                    'PF Contribution By Employer'=>$deductionsList['PF Contribution By Employer'],
                    'ESI Contribution By Employer'=>$deductionsList['ESI Contribution By Employer'],
                    'Bonus'=>$deductionsList['Bonus'], 
                    'Gratuity'=>$deductionsList['Gratuity'],
                    'mediclaim'=>'300.00',
                    'Professional Tax'=>$deductionsList['Professional Tax'],
                    'PF Contribution By Employee'=>$deductionsList['PF Contribution By Employee'],
                    'ESI Contribution By Employee'=>$deductionsList['ESI Contribution By Employee'],
                    'deduction'=>$row['deduction'],
                    'inhand'=> $row['inhand']);
                    //  print_r($salArr);exit;
         $sql1 = "SELECT * FROM emp_salary_details WHERE month = '".$month."' AND emp_code = '".$rowdata[0]."'";
         $result = $conn->query($sql1);
        //  print_r($result->num_rows);exit;
        if($result->num_rows > 0)
        {
             $sql ="UPDATE emp_salary_details SET plant_id ='".$_GET["plant_id1"]."', month ='".$month."', year='".$year."', emp_code='".$rowdata[0]."', salary_details='".json_encode($salArr)."', entry_by='".$_GET["emp_id"]."', entry_date='".$newIndate."' WHERE month ='".$month."'AND year='".$year."'";
       
        if($conn->query($sql)===TRUE){
            $ouput['empSalStatus']="success";
    	}
    	else {
    		$ouput['empSalStatus']="Failed";
    	}
      
            
        }
        else
        {
         $sql ="INSERT INTO emp_salary_details(plant_id, month, year, emp_code, salary_details, entry_by, entry_date) 
        VALUES ('".$_GET["plant_id1"]."','".$month."','".$year."','".$rowdata[0]."',
        '".json_encode($salArr)."','".$_GET["emp_id"]."','".$newIndate."')";
      
        if($conn->query($sql)===TRUE){
		   $ouput['empSalStatus']="success";
    	}
    	else {
    		$ouput['empSalStatus']="Failed";
    	}
        }
        
                      }
                    }
                    array_push($output,$row);
                    
        		}
                
        
                if (!empty($returnArr)) {
                    $output['status'] = "success";
                    $output['data'] = $returnArr;
                      
                   
                } else {
                    // echo "{\"status\":\"".$conn->error."\"}";
                    $res['status'] = $conn->error;
                }
            } else {
                // echo "{\"status\":\"error:file not found\"}";
                $res['status'] = 'error:file not found';
            }
            
            echo json_encode($output);

    }
    /*********************************************************/
    
    
    
    else if ($_GET["type"] == "getAllMonthlyAttendance") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $temp = Array();
                $temp["emp_id"] = $row["emp_id"];
                $mon = explode("-", $_GET["month"]);
                $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
                $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
                for ($i = 1; $i <= $days; $i++) {
                   
                    
                    $today = $mon[0]."-".$mon[1]."-".$i;
                    
                    $mydate=strtotime($i."-".$mon[1]."-".$mon[0]);
                    
                    if($curdate > $mydate) {
                        $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                            
                                $temp[$today] = "P";
                            }
                        } else {
                            $temp[$today] = "Ab";
                        }
                    } else {
                        $temp[$today] = "";
                    }
                }
                $output[] = $temp;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMonthlyChart") {
        $output = Array();
        $sql = "SELECT department_name FROM department";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT IFNULL(count(id), 0) as id FROM employee WHERE status='active' AND department_name='".$row["department_name"]."'";
                $result1 = $conn->query($sql1);
                while ($row1 = $result1->fetch_assoc()) {
                    $row["employees"] = $row1["id"];
                    break;
                }
                $output[] = $row;
            }
        }
    } 
    
    
        else if ($_GET["type"] == "misspunch") {
         $date = $_GET['date'];

// Create a DateTime object from the retrieved date
$dateTime = new DateTime($date);

// Format the date as "Y-m" (year and month)
$date1 = $dateTime->format('Y-m');

         $output = array();
          $sql = "SELECT * FROM attendence a left JOIN employee b on a.emp_id=b.emp_id where a.indate!='".$entry_date1."' 
          and (a.outtime='' or a.intime='') and a.indate like '%".$date1."%'  AND b.plant_id='".$_GET["plant_id"]."' order by indate desc ";  
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
        else if ($_GET["type"] == "HOmisspunch") {
         $date = $_GET['date'];

// Create a DateTime object from the retrieved date
$dateTime = new DateTime($date);

// Format the date as "Y-m" (year and month)
$date1 = $dateTime->format('Y-m');

         $output = array();
          $sql = "SELECT * FROM attendence a left JOIN employee b on a.emp_id=b.emp_id where a.indate!='".$entry_date1."' 
          and (a.outtime='' or a.intime='') and a.indate like '%".$date1."%' AND b.plant_id='".$_GET["plantID"]."' order by indate desc ";  
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    }
    
    
    
    
    
    
    
    
    else if ($_GET["type"] == "getDepartmentEmployees") {
        $entry_month1=date("Y-m");
        $output = Array();
        //   echo $sql = "SELECT department,designation,emp_id, firstname,lastname FROM employee WHERE status='active' AND plant_id='".$_GET["plant_id"]."' AND department='".$_GET["department_name"]."'";
             $sql = "SELECT * FROM employee WHERE status='active' AND department='".$_GET["department_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                  $output22 = Array();
                      $sql22 = " SELECT IFNULL(SUM(b.week_off), 0)  AS see_OFF
                            FROM    attendence b  
                               where   b.indate like '%$entry_month1%' and
                              b.emp_id = '".$row["emp_id"]."'";
                      
                    $result22 = $conn->query($sql22);
                    if ($result22->num_rows > 0) {
                        while ($row22 = $result22->fetch_assoc()) {
                            $row["see_OFF"]  = $row22['see_OFF'];
                        }
                    }
                  $output233 = Array();
                      $sql233 = " SELECT indate  AS see_OFF_date
                            FROM    attendence b  
                               where   b.indate like '%$entry_month1%' and
                              b.emp_id = '".$row["emp_id"]."'";
                      
                    $result233 = $conn->query($sql233);
                    if ($result233->num_rows > 0) {
                        while ($row233 = $result233->fetch_assoc()) {
                             $output233[] = $row233;
                        }
                    }
                
                      $sql222 = " SELECT IFNULL(SUM(b.no_day), 0) AS total_see_OFF
                                FROM leaveform b
                                WHERE MONTH(b.leave_from) = MONTH(CURDATE())
                                AND YEAR(b.leave_from) = YEAR(CURDATE())
                                and b.emp_id='".$row["emp_id"]."' and b.leave_type='C OFF' ";
                      
                    $result222 = $conn->query($sql222);
                    if ($result222->num_rows > 0) {
                        while ($row222 = $result222->fetch_assoc()) {
                            $row["taken_see_OFF"]  = $row222['total_see_OFF'];
                        }
                    }
                
                 $row["bal_see_OFF"]=$row["see_OFF"]-$row["taken_see_OFF"];
                
                
                
                
                $row["coff_dates"]=$output233;
                
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'downloadAttendance'){
        
        $date = strtotime($_GET['month']);
        $month=date("m",$date);
        $year=date("Y",$date);
        
        $objPHPExcel = new PHPExcel();
        $style1 = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, )
        );
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'GMP Software Pvt Ltd' );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Attendance Report - '.$month.'-'.$year );
        
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Employee Id');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Employee Name');
        $mon = explode("-", $_GET["month"]);
        $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
        $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
        $b = 'C'; 
        $counter = 1;
        for ($i =1; $i <= $days; $i++) {
            $datenum = $counter++;
            $today = date("$year-$month-$datenum", strtotime($_GET['month']));
            $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue($b++. + 3, $today);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:'.$b. + 1);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:'.$b. + 1)->applyFromArray($style1);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:'.$b. + 2);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:'.$b. + 3)->getFont()->setBold( true );
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2:'.$b. + 2)->applyFromArray($style);
        }
        $ii = 4;
        $sql = "SELECT * FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$ii, $row["emp_id"]);
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B'.$ii, $row["emp_name"]);
                $b1 = 'C';
                
                $temp = Array();
                $temp["emp_id"] = $row["emp_id"];
                for ($i = 1; $i <= $days; $i++) {
                    $today = $mon[0]."-".$mon[1]."-".$i;
                    $mydate=strtotime($i."-".$mon[1]."-".$mon[0]);
                    if($curdate > $mydate) {
                        $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$row["emp_id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $temp[$today] = "P";
                            }
                        } else {
                            $temp[$today] = "A";
                        }
                    } else {
                        $temp[$today] = "";
                    }
                    $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($b1++. + $ii, $temp[$today]);
                }
                $ii++;
            }
        }
                
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(__DIR__."/employeesalaryreport.xls");
        
        $file = 'employeesalaryreport.xls';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink('employeereport.xls');
    }
    else if ($_GET["type"] == "downloadIndividualAttendace") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='<h3 style="text-align:center;">Individual Attendance</h3>
        <table style="background-color:#DCDCDC;"cellpadding="5" border="1">
            <tr>
                <td style="width:20%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:20%; text-align:centre;"><b>Date</b></td>
                <td style="width:20%; text-align:centre;"><b>In Date</b></td>
                <td style="width:20%; text-align:centre;"><b>Out Date</b></td>
                <td style="width:20%; text-align:centre;"><b>Status</b></td>
           
            </tr>';
        $i=1;
        $output = Array();
        $earlier = new DateTime($_GET["from_date"]);
        $later = new DateTime($_GET["to_date"]);
        $diff = $later->diff($earlier)->format("%a");
        $fromdate = $earlier->format('Y-m-d');

        for ($i=1; $i <= $diff; $i++) {
            $temp = Array();
            
            $sql1 = "SELECT * FROM attendence WHERE DATE(intime)='$fromdate' AND emp_id='".$_GET["emp_id"]."'";
           
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                     $temp["indate"] = $row1["indate"];
                    $temp["outdate"] = $row1["outdate"];
                    $temp["date"] = $fromdate;
                    $temp["status"] = "P";
                    $html.='<tr>
                        <td style="width:20%;">'.$i.'</td>
                        <td style="width:20%;">'.$temp["date"].'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($temp["indate"])).'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($temp["outdate"])).'</td>
                        <td style="width:20%;">'.$temp["status"].'</td>
                    </tr>';
                    $i++;
                }
            } else {
                $temp["indate"] =  $row1["indate"];
                 $temp["outdate"] =  $row1["outdate"];
                 $temp["date"] = $fromdate;
                $temp["status"] = "A";
               $html.='<tr>
                    <td style="width:20%;">'.$i.'</td>
                    <td style="width:20%;">'.$temp["date"].'</td>
                    <td style="width:20%;">'.date('d-m-Y',strtotime($temp["indate"])).'</td>
                    <td style="width:20%;">'.date('d-m-Y',strtotime($temp["outdate"])).'</td>
                    <td style="width:20%;">'.$temp["status"].'</td>
               </tr>';
                $i++;
            } 
            
            // $output[] = $temp;
            // $fromdate = date('Y-m-d', strtotime($fromdate. ' + 1 days'));
        
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
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