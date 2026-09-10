<?php
    ini_set('display_errors', 1);
error_reporting(E_ALL);

    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
   
       if($_GET["type"]=="savesalary_slip") {
        $sql ="INSERT INTO emp_salary_details (plant_id, month, year, emp_code, salary_details, entry_by, entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["month"]."','".$input["year"]."','".$input["emp_id"]."',
        '".json_encode($input["salary_details"])."','".$_GET["emp_id"]."','".$entry_date."')";
      
        if($conn->query($sql)===TRUE){
		    echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
    	}else if($_GET["type"]=="getsalary_slip"){
	$sql = "SELECT * FROM emp_salary_details  ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		     $row["salary_details"] = json_decode($row["salary_details"]);
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
	} 
	else if($_GET['type'] == 'payslip'){
// 	    ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
         $html= "";
         

       
        
   $date = $_GET['month'];
    $time=strtotime($date);
    $month=date("m",$time);
    $monthText = date("F", mktime(0, 0, 0, $month, 1)); // Convert numeric month to text
    $year=date("Y",$time);
    $output = Array(); 
                                  $sql="SELECT a.bank_name, a.plant_id, a.isAppointment, a.operator_category, a.id, a.emp_id, 
                                  a.department, a.designation, CONCAT(a.firstname, ' ', a.middlename, ' ', a.lastname) AS emp_name,
                                  b.netPayMonthly, b.grossSalAMonthly, b.total_deductions, b.status, b.id AS salary_annexure_id, lv.leave_balance, lv.leave_taken 
FROM employee a 
JOIN salary_annexure b ON a.emp_id = b.emp_id 
LEFT JOIN attendence c ON c.emp_id = a.emp_id 
JOIN shift_schedule s ON c.shift = s.id 
LEFT JOIN leave_card lv ON lv.emp_id=a.emp_id 
WHERE c.indate Like'%".$year.'-'.$month."%' and a.emp_id='".$_GET["id"]."'  and b.status='Pending'
GROUP BY a.bank_name,a.plant_id, a.isAppointment, a.id, a.emp_id, a.department, a.designation, emp_name, b.netPayMonthly, b.grossSalAMonthly, b.total_deductions, b.status, a.operator_category, b.id, lv.leave_balance, lv.leave_taken 
LIMIT 0, 25;
";
                              
//                                   $sql="SELECT a.bank_name,acc_name, a.plant_id, a.isAppointment, a.operator_category, a.id, a.emp_id, a.department, a.designation, CONCAT(a.firstname, ' ', a.middlename, ' ', a.lastname) AS emp_name, b.take_home_salary, b.gross_salary, b.total_deductions, b.status, b.id AS salary_annexure_id, lv.leave_balance, lv.leave_taken 
// FROM employee a 
// JOIN salary_annexure b ON a.emp_id = b.emp_id 
// LEFT JOIN attendence c ON c.emp_id = a.emp_id 
// JOIN shift_schedule s ON c.shift = s.id 
// LEFT JOIN leave_card lv ON lv.emp_id=a.emp_id 
// WHERE c.send_to_clearance= 'Yes' 
// AND c.indate Like'%".$year.'-'.$month."%' and a.emp_id='".$_GET["id"]."'  and b.status='Pending'
// GROUP BY a.bank_name,acc_name,a.plant_id, a.isAppointment, a.id, a.emp_id, a.department, a.designation, emp_name, b.take_home_salary, b.gross_salary, b.total_deductions, b.status, a.operator_category, b.id, lv.leave_balance, lv.leave_taken 
// LIMIT 0, 25;
// ";
                              
    
    
    
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
                             $row['ot_total']=$row['total'] /1.5;
                        }
                        else if($rowx["operator_category"]=='Worker / Operator'){
                              $row['ot_total']=$row['total'] * 1;
                        }  
                        }
                     
                        
                        
                        
                      
                    }
                } 
else {
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
                // $working_days = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                $working_days = count(array_filter(range(1, cal_days_in_month(CAL_GREGORIAN, date("m", strtotime($_GET['month'])), date("Y", strtotime($_GET['month'])))), function($day) { return !(date('N', strtotime($_GET['month'] . '-' . $day)) >= 6); }));

                 $row['working_days'] =$working_days;
             
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
                              
                                                             if ($working_days != 0) {
                                    $amount = (($row21["per_month"] / $working_days) * $paid_days);
                                } else {
                                    // Handle the case when $working_days is zero
                                    $amount = 0; // Or any other appropriate value or action
                                }
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
                  $html.='
<table style="border: 1px solid black;">
  <tr>
    <td style="width: 540px;text-align:center;">
     Pay Slip oFor The Month of '.$monthText.'-'.$year.' 
    </td>
  </tr>
</table>

  <table style="border: 1px solid black;">
  <tr>
    <td style="width: 135px; border: none;">Emp Code:'.$rowx["emp_id"].'</td>
    <td style="width: 135px; border: none;">Emp Name:'.$rowx["emp_name"].'</td>
  </tr>
  <tr>
    <td style="width: 135px; border: none;">Branch:</td>
    <td style="width: 135px; border: none;">Department:'.$rowx["department"].'</td>
    <td style="width: 135px; border: none;">Grade:</td>
    <td style="width: 135px; border: none;">Designation:'.$rowx["designation"].'</td>
  </tr>
  <tr>
    <td style="width: 135px; border: none;">Pf No.:</td>
    <td style="width: 135px; border: none;">Esic No.:</td>
    <td style="width: 135px; border: none;">Pan No:</td>
    <td style="width: 135px; border: none;">Standard Basic Salary:</td>
  </tr>
</table>
  <table style="border: 1px solid black;">
  
  <tr>
    <td style="width: 135px; border: none;">Days Paid:'.$working_days.'</td>
    <td style="width: 135px; border: none;">Days Present:'.$paid_days.'</td>
    <td style="width: 135px; border: none;">Paid Holidays:'.$working_days-$paid_days.' </td>
    <td style="width: 135px; border: none;">LWP/Absent:</td>
  </tr>
  <tr>
    <td style="width: 135px; border: none;">Leave Availed.:'.$rowx["leave_taken"].'</td>
    <td style="width: 135px; border: none;">Leave Balance.:'.$rowx["leave_balance"].'</td>
    <td style="width: 135px; border: none;">Loan Op Bal:</td>
    <td style="width: 135px; border: none;">Loan Cl. Bal:</td>
  </tr>
</table>
  <table style="border: 1px solid black;">
  <tr>
    <td>
    <table  >
         
          <tr>
              <td style="width: 135px; border: none;">Earnings</td>
              <td style="width: 135px; border: none;">Amount</td>
          </tr>';
          
              $sql2 = "SELECT * FROM salary_annexure_details WHERE salary_annexure_id='".$rowx["salary_annexure_id"]."' AND salary_group='Earnings' And description!='Bonus'";
$result2 = $conn->query($sql2);
        $totalPerMonth = 0;
if ($result2->num_rows > 0) {
    while ($row21 = $result2->fetch_assoc()) {
         $totalPerMonth += $row21["per_month"];
        $html.= '
        <tr>
            <td style="width: 135px; border: none;">'.$row21["description"].'</td>
            <td style="width: 135px; border: none;">'.number_format($row21["per_month"], 2).'</td>
        </tr>';
    }
}

         $html.='
        
      </table>
    </td>
    <td>
    <table  >
         
         <tr>
             <td style="width: 135px; border: none;">Deductions</td>
             <td style="width: 135px; border: none;">Amount</td>
         </tr>
         
         
         
         <br><br>';
        
              
               $sql2 = "SELECT sum(monthly_emi) as loan_emi FROM emp_loan where  emp_id='".$_GET["id"]."'";
$result2 = $conn->query($sql2);
  $totalPerMonthded = 0;
if ($result2->num_rows > 0) {
    while ($row21 = $result2->fetch_assoc()) {
        if($row21["loan_emi"] ==''){
            
        $loan_emi=0;
        }else{
            
        $loan_emi=$row21["loan_emi"];
        }
        
        $html.= '
        <tr>
             <td style="width: 135px; border: none;">Loan EMI</td>
             <td style="width: 135px; border: none;">'.$loan_emi.'</td>

        </tr>';
    }
}
               $sql2 = "SELECT * FROM salary_annexure a LEFT JOIN salary_annexure_details b on a.id=b.salary_annexure_id WHERE a.emp_id='".$_GET["id"]."'   and b.salary_group='earnings' AND (b.description LIKE '%Basic Salary%' OR b.description LIKE '%Da%')";
$result2 = $conn->query($sql2);
  $totalPerMonthded = 0;
if ($result2->num_rows > 0) {
    while ($row21 = $result2->fetch_assoc()) {
        $tax1 +=$row21["per_month"];
        
    }
}
               $sql22 = "SELECT * FROM employee_tds a   WHERE a.emp_id='".$_GET["id"]."'  order by id desc limit 1";
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




            $sql4 = "SELECT * FROM employee WHERE emp_id='".$row["emp_id"]."' and branch like '%NPF%'  order by id desc limit 1";
                     
                       $result4 = $conn->query($sql4);
                    if ($result4->num_rows > 0) {
                        $pf='Not Applicable';
                        
                    }else{
                             $pf='Applicable';
                    }
if( $pf=='Not Applicable'){
    $tax=0;
    
}else{
    if($tax1 > 15000){
         $tax=1800;
        
     }else{
         $tax=($tax1 * 12)/100;
       
     }
}
 
 
  $html.= '
        <tr>
             <td style="width: 135px; border: none;">PF</td>
             <td style="width: 135px; border: none;">'.$tax.'</td>

        </tr>
        <tr>
             <td style="width: 135px; border: none;">TDS</td>
             <td style="width: 135px; border: none;">'.$tds.'</td>

        </tr>
        ';
$totoal_deduct=$loan_emi+$tax+$tds;
         $html.='
       
     </table>
    </td>
  </tr>
  
</table>
<table style="border: 1px solid black;">
    <tr>
        <td style="width:135px;">
        Total Earning: 
        </td>
        <td style="width:135px;">
         '.$totalPerMonth.'
        </td>
        <td style="width:135px;">
        Total Deductions: 
        </td>
        <td style="width:135px;">
         '.$totoal_deduct.'
        </td>
    </tr>
</table>';
 $netPay=$totalPerMonth-$totoal_deduct;
 
 $take_home=$row['take_home_salary'];
 $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
$netPayText = $formatter->format($netPay);
$html.='
<table style="border: 1px solid black;">
<tr>
    <td style="width:540px;text-align:right;">Net Pay :'.$netPay.' </td>
</tr>
</table>
<table style="border: 1px solid black;">
<tr>
    <td style="width:540px;">Ruppes :- '.$netPayText.' only </td>
</tr>
</table>
<table style="border: 1px solid black;">
<tr>
    <td style="width:540px;">Bank Details:-     Salary HAs been Credited to '.$rowx["acc_name"].' , '.$rowx["bank_name"].'</td>
</tr>
<br>
<tr>
<td style="text-align:center;font-weight:bold;">Note:- This is a computerised payslip ,hence no signature is required</td>
</tr>

</table><br>

            
            ';
          
        }
    }

    
 
   
    
  

         
         
         
        
 
       $html.='';
       
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary.pdf', 'I');
	
	 
   } 
	else if($_GET['type'] == 'loan_application'){
// 	    ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
          
         

        $sql="select a.*,b.firstname,b.lastname,b.designation,b.joining_date from emp_loan a left join employee b on a.emp_id=b.emp_id  where a.id='".$_GET['id']."'";
 
     $result = $conn->query($sql);
      $result->num_rows > 0;
           $row = $result->fetch_assoc();
  $joiningDate = new DateTime($row['joining_date']);
$entryDate = new DateTime($entry_date);

$interval = $joiningDate->diff($entryDate);
$exp = $interval->format('%y years, %m months, %d days');

           $sql11="select *  from salary_annexure a where a.emp_id='".$row['emp_id']."' order by id desc";
 
     $result11 = $conn->query($sql11);
      $result11->num_rows > 0;
           $row11 = $result11->fetch_assoc();
         
         
        
 
       $html.='
<table style=" border: 1px solid white; border-collapse: collapse">
    
   
    <br>

    <tr >
        <td style="width: 540; text-align:center;border: 1px solid white; border-collapse: collapse"><h3>Sub: Application for sanction of loan.</h3></td>
    </tr>
    <br>

    <tr>
        <td style="text-align:right; width:400;border: 1px solid white; border-collapse: collapse">Date:</td>
    </tr>    <br>

    <tr>
    <td style="width:540;border: 1px solid white; border-collapse: collapse">To,</td>
    </tr>

    <tr>
    <td style="width:540;border: 1px solid white; border-collapse: collapse">The Partner,</td>
    </tr>
    <tr>
    <td style="width:540;border: 1px solid white; border-collapse: collapse">Olive Healthcare <br>Mumbai</td>
    </tr><br>
    
    <br>

    <tr>
        <td style="width:540;border: 1px solid white; border-collapse: collapse">Dear Sir,</td>
    </tr>

    <br>

    <tr>
        <td style="width:540;border: 1px solid white; border-collapse: collapse">I, Mr./Miss./Mrs.'.$row['firstname'].'  '.$row['lastname'].' working as an '.$row['designation'].' since past '.$exp.'   hereby request to grant me a loan of Rs. '.$row['loan_amt'].' Reason: NA
        I hereby give request you to deduct Rs '.$row['monthly_emi'].'/- per month from my salary with effect from next month’s salary i.e. '.$row11['take_home_salary'].' 
        </td>
    </tr>
    <br>

    <tr>
        <td style="width:540; ">My previous loan details are as under:</td>
    </tr><br>

    </table>
    <table border="1">
    <tr>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>

    </tr> 
    </table>

    <table>
    <tr>
        <td style="width:540;text-align: left;">______________</td>
    </tr> <br>
    <tr>
        <td style="width:540;text-align: left;">Loan Received by </td>
    </tr>    <br>

    <tr>
        <td style="width:540; text-align: left; font-weight: bold;">Sign: ________________</td>
        
    </tr> <br>
    <tr>
        <td style="width:540; text-align: left; font-weight: bold;">Name: ________________</td>
        
    </tr> <br>
    </table>
';
       
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary.pdf', 'I');
	
	 
   } 
   else if($_GET['type'] == 'salarycertificate'){ 
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
         $html= "";
        $html.='
      <h1 style="text-align:center;color:#a52a2a;">SALARY CERTIFICATE</h1>
      <div></div>
        <table style="background-color:#C0C0C0;"><div></div><div></div>
        <tr>
        <td style="width:85%;text-align:center"><b>Certificate that Sri_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _(Designation and Office Address)</b></td>
         </tr><br>
          <tr>
        <td style="width:90%;text-align:center">_ _ _ _ _ _ _ _ _ _ _ _ _ __ _ _ _ _ _  _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _  _ _  _ _ _  __  _</td>
         </tr><br>
          <tr>
        <td style="width:90%;text-align:center">_ _ _ _ _ _ _ _ _ _ _ _ _ __ _ _ _ _ _  _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _  _ _  _ _ _  __  _</td>
         </tr><br>
         <tr>
         <td style="width:80%; text-align:right">  Whose Signature is given below is a regular employee of the office / deparment with
         a total of
        </td>
       </tr><br>
        <tr>
        <td style="width:90%;text-align:center">_ _ _ _ _ _ _ _ _ _ _ _ _ __ _ _ _ _ _  _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _  _ _  _ _ _  __  _
         </td>
        </tr><br>
       <tr>
        <td style="width:80%;text-align:center"> Years of service and drawing pay and allownce as a follows. His date of Birth is</td>
        </tr><br>
        <tr>
      <td style="width:85%;text-align:center">    _ _ _ _ _ _ _ _ _ _ __ _ _ _ _ _  _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _  _ _  _ _ _  __  _</td>
         </td><div></div><div></div>
        </tr>
        </table>
        <table  style="background-color:#C0C0C0;">
         <tr>
        <td style="width:19%;text-align:right">Pay</td>
         <td style="width:81%;text-align:center">Rs.</td>
        </tr>
         <tr>
       <td style="width:19%;text-align:right">DA</td>
       <td style="width:81%;text-align:center">Rs.</td>
        </tr>
        <tr >
        <td style="width:20%;text-align:right">HRA</td>
        <td style="width:80%;text-align:center">Rs.</td>
        </tr>
         <tr>
        <td style="width:28%;text-align:right">Other Allownce</td>
         <td style="width:64%;text-align:center">Rs. </td>
         <td style="width:8%;"> </td>
        </tr>
         <tr>
        <td style="width:20%;text-align:right">Total</td>
        <td style="width:80%;text-align:center">Rs.</td>
        </tr><div></div>
        </table>
        <table style="background-color:#C0C0C0;"><div></div>
        <tr>
        <td style="width:90%;text-align:right">For(Company Name)</td>
         <td style="width:10%;text-align:right"></td>
        </tr><div></div>
         <tr>
        <td style="width:90%;text-align:right">Authorized Signatory</td>
        <td style="width:10%;text-align:right"></td>
        </tr>
         <tr>
        <td style="width:90%;text-align:right"></td>
        <td style="width:10%;text-align:right"></td>
        </tr>
         <tr>
        <td style="width:90%;text-align:right"></td>
        <td style="width:10%;text-align:right"></td>
        </tr>
         <tr>
        <td style="width:90%;text-align:right"></td>
        <td style="width:10%;text-align:right"></td>
        </tr>
         <tr>
        <td style="width:90%;text-align:right"></td>
        <td style="width:10%;text-align:right"></td>
        </tr>
         <tr>
        <td style="width:90%;text-align:right"></td>
        <td style="width:10%;text-align:right"></td>
        </tr>
        </table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary.pdf', 'I');
       
} else {
    echo "{\"status\":\"invalid\"}";
}
}
$conn->close();
?>









