<?php
try {
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    
    
    
    
    //attendanceLog
    
    
    
    
    
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL); 

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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
  
      
	if($_GET["type"] == "trainingLogPdf") {

      $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
      //$_GET['pdftype']= 'onlyheader';
      
      
         $html= "";
         
         
         
    
    		
    $sql11 = "SELECT t.*,e.firstname, e.middlename, e.lastname, e.joining_date ,e.department,e.designation from training_induction t
          LEFT JOIN employee e ON t.emp_id=e.emp_id 
          WHERE t.id='". $_GET["id"] ."' limit 1";
            
           $result11 = $conn->query($sql11);
                     if($result11->num_rows > 0) {
                        while($row = $result11->fetch_assoc()) {
         
         

         $html.='
          <table style="width: 785px" border:none;>
 <tr>
      <td style="width: 785px;text-align:center; border:none;"><h2><b>Induction Training Schedule</b></h2></td>
  </tr>
  <br><br>
   <tr>
      <td style="width: 200px;text-align:left; border:none;height:20px;"><b>Name of New Employee:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;">' . $row['firstname'] . ' ' . $row['lastname'] . '</td>
      <td style="width: 195px;text-align:left; border:none; height:20px;"><b>Department:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;">' . $row['department'] . '</td>
  </tr>
  <tr>
      <td style="width: 200px;text-align:left; border:none; height:20px;"><b>Date of Joining:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;">' . $row['joining_date'] . '</td>
      <td style="width: 195px;text-align:left; border:none; height:20px;"><b>Designation:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;"> ' . $row['designation'] . '</td>
  </tr>
  </table>
  
  <div></div>
  
  <table style="width: 785px" border="1">
 
   <tr>
      <td style="width: 785px;text-align:left;height:20px;font-weight:bold;"><b> Day 1:</b></td>
 
  </tr>
 <tr>
    <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">Time</td>
    <td style="width: 120px;text-align:center; height:20px; font-weight:bold;">Department</td>
    <td style="width: 375px;text-align:center; height:20px; font-weight:bold;">Subject to be Covered</td>
    <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Responsibility</td>
    <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Date</td>
    </tr>';
    
$json_obj1 = $row['adminChecklist'];
$adminChecklist = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($adminChecklist['checklist'])) {
    $json_obj = $adminChecklist['checklist'];
    $cheklist = $json_obj['cheklist'];
 
        $html .= '
            <tr>
                <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td> 
                <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                    <ul>';
        
         foreach ($cheklist as $check) {
            $html .= '<li>' . $check['subject_covered'] . '</li>';
        }
        
        $html .= '</ul>
                </td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                <td style="width: 100px;text-align:center; height:20px;">  
                 <br>  
                 
                 <b>Submitted By : </b> ' . $row['adminBy'] . '  <br> 
                 <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['adminOn'])) . ' / ' . date('H:i:s', strtotime($row['adminOn'])) . '   
                
                </td>
             </tr>';
            
} 

$json_obj1 = $row['qaChecklist'];
$qaChecklist = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($qaChecklist['checklist'])) {
    $json_obj = $qaChecklist['checklist'];
    $cheklist = $json_obj['cheklist'];
 
        $html .= '
            <tr>
                <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td> 
                <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                    <ul>';
        
         foreach ($cheklist as $check) {
            $html .= '<li>' . $check['subject_covered'] . '</li>';
        }
        
        $html .= '</ul>
                </td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                <td style="width: 100px;text-align:center; height:20px;">  
                 <br>  
                 
                 <b>Submitted By : </b> ' . $row['qaBy'] . '  <br> 
                 <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['qaOn'])) . ' / ' . date('H:i:s', strtotime($row['qaOn'])) . '   
                
                </td>
             </tr>';
            
}              

    $html .= '
    <tr>
      <td style="width: 785px;text-align:center;height:20px;"><b>Lunch Break</b></td>
  </tr>';

$json_obj1 = $row['qcChecklist'];
$qcChecklist = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($qcChecklist['checklist'])) {
    $json_obj = $qcChecklist['checklist'];
    $cheklist = $json_obj['cheklist'];
 
        $html .= '
            <tr>
                <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td> 
                <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                    <ul>';
        
         foreach ($cheklist as $check) {
            $html .= '<li>' . $check['subject_covered'] . '</li>';
        }
        
        $html .= '</ul>
                </td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                <td style="width: 100px;text-align:center; height:20px;">  
                 <br>  
                 
                 <b>Submitted By : </b> ' . $row['qcBy'] . '  <br> 
                 <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['qcOn'])) . ' / ' . date('H:i:s', strtotime($row['qcOn'])) . '   
                
                </td>
             </tr>';
            
} 
    
      
  $html.='  </table>
  <div style="page-break-before: always;"></div>
  ';
     
          
  $html.='  
     
    <table style="width: 785px" border="1">
 
    <tr>
      <td style="width: 785px;text-align:left;height:20px;font-weight:bold;"><b> Day 2:</b></td>
 
    </tr>
    <tr>
        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">Time</td>
        <td style="width: 120px;text-align:center; height:20px; font-weight:bold;">Department</td>
        <td style="width: 375px;text-align:center; height:20px; font-weight:bold;">Subject to be Covered</td>
        <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Responsibility</td>
        <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Date</td>
    </tr>';
    
        $json_obj1 = $row['productionChecklist'];
        $prodChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($prodChecklist['checklist'])) {
            $json_obj = $prodChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                        <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td> 
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;">  
                         <br> 
                         
                         <b>Submitted By : </b> ' . $row['prodBy'] . '  <br> 
                         <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['prodOn'])) . ' / ' . date('H:i:s', strtotime($row['prodOn'])) . '   
                        
                        </td>
                     </tr>';
                    
        } 

    
       
        $json_obj1 = $row['storeChecklist'];
        $storeChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($storeChecklist['checklist'])) {
            $json_obj = $storeChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                        <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td> 
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;">  
                         <br>  
                         
                         <b>Submitted By : </b> ' . $row['storeBy'] . '  <br> 
                         <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['storeOn'])) . ' / ' . date('H:i:s', strtotime($row['storeOn'])) . '   
                        
                        </td>
                     </tr>';
                    
        } 
        
     $html .= '
            <tr>
              <td style="width: 785px;text-align:center;height:20px;"><b>Lunch Break</b></td>
          </tr>';
    
           
        $json_obj1 = $row['engineeringChecklist'];
        $enggChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($enggChecklist['checklist'])) {
            $json_obj = $enggChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                        <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td> 
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;"> <b>Training Date</b>  
                         <br>  
                         
                         <b>Submitted By : </b> ' . $row['enggBy'] . '  <br> 
                         <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['enggOn'])) . ' / ' . date('H:i:s', strtotime($row['enggOn'])) . '  
                        
                        </td>
                     </tr>';
                    
        } 
           
        $json_obj1 = $row['itChecklist'];
        $itChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($itChecklist['checklist'])) {
            $json_obj = $itChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '
                         <br> <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;"> 
                         <br> 
                         
                         <b>Submitted By : </b> ' . $row['itBy'] . '  <br> 
                         <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['itOn'])) . ' / ' . date('H:i:s', strtotime($row['itOn'])) . '   
                        
                        </td>
                     </tr>';
                    
        } 
        $json_obj1 = $row['quality_headChecklist'];
        $qheadChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($qheadChecklist['checklist'])) {
            $json_obj = $qheadChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . ' <br>
                        <b>Training Date</b> <br> ' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;"> 
                         
                         <b>Submitted By : </b> ' . $row['qhBy'] . '  <br> 
                         <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['qhOn'])) . ' / ' . date('H:i:s', strtotime($row['qhOn'])) . '  
                        
                        </td>
                     </tr>';
                    
        } 
    
      $html.='  </table>
  <div style="page-break-before: always;"></div>
  ';
     
  $html.='  
  
  
 <table style="width: 785px" border="1">
 
   <tr>
      <td style="width: 655px;text-align:left;height:20px;font-weight:bold;"><b >Day 3:</b></td>
        <td style="width: 130px;text-align:left; height:20px; font-weight:bold;">Date</td>
  </tr>
  
    <tr>
      <td style="width: 655px;text-align:left;height:20px;font-weight:bold;"><b>Handing over to the Department HOD by Admin and Personal.</b> </td>
      <td style="width: 130px;text-align:center; height:20px; font-weight:bold;">Sign/Date</td>
    </tr>
    
    
    <tr>
        <td style="width: 655px;text-align:left; height:20px; ;"> <b>Report Writing on Induction : </b><br> 
       <b> (To be submitted by the candidate) </b><br><br> ' . $row['emp_report'] . '<br>
        </td>
         <td style="width: 130px;text-align:center; height:20px;  ">
            <br>
             <b>Submitted By : </b> ' . $row['empRepBy'] . '  <br> 
             <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['empRepOn'])) . ' / ' . date('H:i:s', strtotime($row['empRepOn'])) . '  
                        
        </td>
    </tr>
    <tr>
        <td style="width: 655px;text-align:left; height:20px; "><b>Evaluation of induction Report by Dept. Head : </b>
        <br><br> ' . $row['eval_dept_head'] . '<br></td>
         <td style="width: 130px;text-align:center; height:20px;  ">
            <br>
             <b>Submitted By : </b> ' . $row['eval_dept_head_by'] . '  <br> 
             <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['eval_dept_head_on'])) . ' / ' . date('H:i:s', strtotime($row['eval_dept_head_on'])) . '   
                        
        </td>
    </tr>
    <tr>
        <td style="width: 655px;text-align:left; height:20px; "><b> Evaluation of induction Report by Admin. And Personnel : </b>
        <br><br> ' . $row['eval_hr'] . '<br></td>
        <td style="width: 130px;text-align:center; height:20px;  ">
            <br>
             <b>Submitted By : </b> ' . $row['eval_hr_by'] . '  <br> 
             <b>Date/Time : </b> ' . date('d-m-Y', strtotime($row['eval_hr_on'])) . ' / ' . date('H:i:s', strtotime($row['eval_hr_on'])) . '   
                        
        </td>
    </tr>


</table>


<div></div>
<div></div>
 


     
        
<table style="width: 785px" border="1" cellpadding="3">
      <tr style="background-color:black; color:white;">
          <td style="width: 370px;text-align:center;"><b>Prepared By A & P Dept.</b></td>
          <td style="width: 45px;text-align:center;"><img src="../upload/pdf/sign.jpg" style="width:20px;height:20px;"></td>
          <td style="width: 370px;text-align:center;"><b>Approved By Manager QA</b></td>
      </tr>
  <tr>
      <td style="width: 392px;" >
        <table>
            <tr>
                  <td style="width:100px;font-weight:bold;">Sign</td>
                  <td style="width:292px; ">:' . $row['entry_by'] . '</td>
            </tr>
            <tr>
                <td style="width:100px;font-weight:bold;">Date</td>
                <td style="width:96px; ">:' . date('d-m-Y', strtotime($row['entry_on'])) . '</td>
                <td style="width: 100px;font-weight:bold;">Time</td>
                <td style="width: 96px; ">:' . date('H:i:s', strtotime($row['entry_on'])) . '</td>
            </tr>
        </table>
      </td>
      <td style="width: 392px;">
        <table >
            <tr>
                <td style="width:100px;font-weight:bold;"> Sign :</td>
                <td style="width:292px;">' . $row['qaManagerBy'] . '</td>
            </tr>
            <tr>
                <td style="width:100px;font-weight:bold;"> Date :</td>
                <td style="width:96px;">' . date('d-m-Y', strtotime($row['qaManagerOn'])) . '</td>
                <td style="width: 100px;font-weight:bold;">Time</td>
                <td style="width: 96px;">' . date('H:i:s', strtotime($row['qaManagerOn'])) . '</td>
            </tr>
        
        </table>
      </td>
  </tr>
 
</table>
          





  ';
        }
         
     }
  
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('induction.pdf', 'I');
     
}

	else if($_GET["type"] == "onJobSchedule") {

      $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
      //$_GET['pdftype']= 'onlyheader';
      
      
        //  $html= ""; 
         
          
    		
     $sql11 = "SELECT t.proposed_trainer,t.qaApprovedOn,t.qaApprovedBy,t.hodApproveOn,t.hodApproveBy,t.hodRemark,t.entry_date,t.entry_by,t.qHeadBy,t.qHeadOn,
     tn.*,e.firstname, e.middlename, e.lastname, e.joining_date ,e.department,e.designation from training_needs t
          LEFT JOIN tn_employees tn ON tn.tn_no=t.id 
          LEFT JOIN employee e ON tn.emp_id=e.emp_id 
          WHERE tn.tn_no='". $_GET["id"] ."' ";
            $html = "";
          $result11 = $conn->query($sql11);
                     if ($result11->num_rows > 0) {
                            while ($row = $result11->fetch_assoc()) {
                                
                                
                                
                                
                   $sql22 = "SELECT 
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["entry_by"]."' LIMIT 1) AS entry_by_name,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["hodApproveBy"]."' LIMIT 1) AS hodApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qaApprovedBy"]."' LIMIT 1) AS qaApproveName,
                        (SELECT CONCAT(firstname, ' ', lastname) FROM employee WHERE emp_id = '".$row["qHeadBy"]."' LIMIT 1) AS qHeadname
                    FROM dual";
                    
                        $result22 = $conn->query($sql22);
                        if ($result22->num_rows > 0) {
                            while ($row22 = $result22->fetch_assoc()) {
                            $row["entry_by_name"] = $row22["entry_by_name"];
                            $row["hodApproveName"] = $row22["hodApproveName"];
                            $row["qaApproveName"] = $row22["qaApproveName"];
                            $row["qHeadname"] = $row22["qHeadname"];
                             }
                        }
                                
                             

 
     
  $html.='  
  
  
 <table style="width: 785px" border:none;>
 <tr>
      <td style="width: 785px;text-align:center; border:none;"><h2><b>On-Job Training Schedule</b></h2></td>
  </tr>
  
</table>
<div></div>
<table style="width: 785px" border="1">
 
   <tr>
      <td style="width: 45px;text-align:center;height:20px;font-weight:bold;" rowspan="2">Sr.No.</td>
      <td style="width: 175px;text-align:center; height:20px; font-weight:bold;" rowspan="2">Subject</td>
      <td style="width: 70px;text-align:center; height:20px; font-weight:bold;" rowspan="2">Training required</td>
      <td style="width: 100px;text-align:center; height:20px; font-weight:bold;" rowspan="2">Responsibility</td>
      <td style="width: 130px;text-align:center; height:20px; font-weight:bold;">Date</td>
      <td style="width: 50px;text-align:center; height:20px; font-weight:bold;" rowspan="2">No.of Days</td>
      <td style="width: 100px;text-align:center; height:20px; font-weight:bold;" rowspan="2">Training Mode</td>
      <td style="width: 65px;text-align:center; height:20px; font-weight:bold;" rowspan="2">Trainer Signature</td>
      <td style="width: 50px;text-align:center; height:20px; font-weight:bold;" rowspan="2">Remark</td>

  </tr>
  <tr>

      <td style="width: 65px;text-align:center; height:20px; font-weight:bold;">From</td>
      <td style="width: 65px;text-align:center; height:20px; font-weight:bold;">To</td>
      
  </tr>';
  
  
  
  $json_obj1 = $row['otherDet'];
$otherDet = json_decode($json_obj1, true);

  $i = 1;
    foreach ($otherDet as $check) {
        
        
        
        
    $start_date = new DateTime($check['training_start_date']);
    $end_date = new DateTime($check['training_end_date']);
    $interval = $start_date->diff($end_date);
    $no_of_days = $interval->days + 1;
        
        
        
        
        $html .= ' 
        
        
    <tr>
      <td style="width: 45px;text-align:center;height:20px;font-weight:bold;">'.$i.'.</td>
      <td style="width: 175px;text-align:left; height:20px;"><b>'.$check['subject'].'</b><br>'.$check['sub_type'].'</td>
      <td style="width: 70px;text-align:center; height:20px;">Yes</td>
      <td style="width: 100px;text-align:center; height:20px;">'.$row['attendance_by'].'</td>
      <td style="width: 65px;text-align:center; height:20px;">'.date('d-m-Y', strtotime($check['training_start_date'])) .'</td>
      <td style="width: 65px;text-align:center; height:20px;">'.date('d-m-Y', strtotime($check['training_end_date'])) .'</td>
      <td style="width: 50px;text-align:center; height:20px;">'.$no_of_days.'</td>
      <td style="width: 100px;text-align:center; height:20px;">'.$check['type'].'</td>
      <td style="width: 65px;text-align:center; height:20px;">'.$row['proposed_trainer'].'</td>
      <td style="width: 50px;text-align:center; height:20px;">'.$check['remark'].'</td>
    </tr>
        
        
        
        
        ';
        
        $i++;
    }
     
 
  
  
    $html .='  
  
  
 
  
 </table>
 

</table>
    <div></div> 

 <table style="width: 785px" border:none;>
 <tr>
      <td style="width: 785px;text-align:left; border:none;">This is to certify that <b>'.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</b>
      has successfully completed On the Job Training from dated  <b>'.date('d-m-Y', strtotime($row['empTraningDateFrom'])) .'</b> to  <b>'.date('d-m-Y', strtotime($row['empTraningDateTo'])) .'</b> .</td>
  </tr>
  <br>
   <tr>
      <td style="width: 785px;text-align:left; border:none; height:20px;"><b>His / Her Training evaluation was found satisfactory.</b></td>
  </tr>
 
</table>

   <div></div>   <div></div> 
<table style="width: 785px; margin-bottom:10px;" border="1">
    <tr style="text-align:center; font-weight: bold;">
        <th style="width:85px; height: 20px;"></th>
        <th style="width:175px; height: 20px;">Prepared By (Concern Department Training coordinator)</th>
        <th style="width:175px; height: 20px;">Checked By (Concern HOD)</th>
        <th style="width:175px; height: 20px;">Reviewed  By (QA Head / Designee)</th>
        <th style="width:175px; height: 20px;">Approved by (Head Quality)</th>
        
    
    </tr>
    <tr style="text-align:center; font-weight: bold;">
        <th style="width:85px; height: 20px;">Name</th>
        <td style="width:175px; height: 20px;">'.$row['entry_by_name'].'</td>
        <td style="width:175px; height: 20px;">'.$row['hodApproveName'].'</td>
        <td style="width:175px; height: 20px;">'.$row['qaApproveName'].'</td>
        <td style="width:175px; height: 20px;">'.$row['qHeadname'].'</td>
    </tr>
     <tr style="text-align:center; font-weight: bold;">
        <th style="width:85px; height: 20px;">User Id</th>
        <td style="width:175px; height: 20px;">'.$row['entry_by'].'</td>
        <td style="width:175px; height: 20px;">'.$row['hodApproveBy'].'</td>
        <td style="width:175px; height: 20px;">'.$row['qaApprovedBy'].'</td>
        <td style="width:175px; height: 20px;">'.$row['qHeadBy'].'</td>
    </tr>
     <tr style="text-align:center; font-weight: bold;">
        <th style="width:85px; height: 20px;">Date/Time</th>
        <td>'.date('d-m-Y', strtotime($row['entry_date'])) .' / ' . date('H:i:s', strtotime($row['entry_date'])) . '</td>
        <td>'.date('d-m-Y', strtotime($row['hodApproveOn'])) .' / ' . date('H:i:s', strtotime($row['hodApproveOn'])) . '</td>
        <td>'.date('d-m-Y', strtotime($row['qaApprovedOn'])) .' / ' . date('H:i:s', strtotime($row['qaApprovedOn'])) . '</td>
        <td>'.date('d-m-Y', strtotime($row['qHeadOn'])) .' / ' . date('H:i:s', strtotime($row['qHeadOn'])) . '</td>
    </tr>
</table>

  <div style="page-break-before: always;"></div>



  ';
        }
         
     }
  
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('induction.pdf', 'I');
        
        
        
        
        
        
//         <div></div>
//  <table style="width: 785px" border:none;>
//   <tr>
//       <td style="width: 261px;text-align:left; border:none;height:20px;"><b>HOD</b></td>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Plant Head</b></td>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Quality Head</b></td>
//   </tr>
//     <tr>
//       <td style="width: 261px;text-align:left; border:none;height:20px;"><b>Signature</b></td>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Signature</b></td>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Signature</b></td>
//   </tr>
//     <tr>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Comment:_____________________</b></td>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Comment:_____________________</b></td>
//       <td style="width: 261px;text-align:left; border:none; height:20px;"><b>Comment:_____________________</b></td>
//   </tr>
// </table>
// <div></div>
     
}

	else if($_GET["type"] == "attendanceLog") {

      $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
        
        $html = "";
  $html.='  
  
  
 <table style="width: 540px" border:none;>
 <tr>
      <td style="width: 540px;text-align:center; border:none;"><h2><b>Training Attendance Sheet</b></h2></td>
  </tr>
  
</table>
<div></div>
<table style="width: 540px" border="1">
 
   <tr>
      <td style="width: 40px;text-align:center;height:20px;font-weight:bold;" >Sr.No.</td>
      <td style="width: 200px;text-align:left; height:20px; font-weight:bold;"  > Name Of Trainee</td>
      <td style="width: 100px;text-align:center; height:20px; font-weight:bold;" >Designation</td>
      <td style="width: 100px;text-align:center; height:20px; font-weight:bold;"  >Department</td>
      <td style="width: 100px;text-align:center; height:20px; font-weight:bold;" >Sign. Of Trainee</td>
  </tr>
   ';
   
   
    $sql11 = "SELECT t.proposed_trainer,t.qaApprovedOn,t.qaApprovedBy,t.hodApproveOn,t.hodApproveBy,t.hodRemark,t.entry_date,
     t.entry_by,t.qHeadBy,t.qHeadOn,tn.*,CONCAT(e.firstname, ' ', e.middlename, ' ', e.lastname)  as empName ,e.department,e.designation from training_needs t
          LEFT JOIN tn_employees tn ON tn.tn_no=t.id 
          LEFT JOIN employee e ON tn.emp_id=e.emp_id 
          WHERE tn.tn_no='". $_GET["id"] ."' AND tn.attendance='Present' ";
            $i = 1;
          $result11 = $conn->query($sql11);
                     if ($result11->num_rows > 0) {
                            while ($row = $result11->fetch_assoc()) {
                        
                            $html .='          
                                <tr>
                                  <td style="width: 40px;text-align:center;height:20px; " >'.$i.'</td>
                                  <td style="width: 200px;text-align:left; height:20px;  "  >'.$row['empName'].'</td>
                                  <td style="width: 100px;text-align:center; height:20px;  " >'.$row['designation'].'</td>
                                  <td style="width: 100px;text-align:center; height:20px;  "  >'.$row['department'].'</td>
                                  <td style="width: 100px;text-align:center; height:20px;  " >'.$row['emp_id'].'</td>
                                </tr>
                               ';   
                           $i++;     
                }
         }
  
   
  
    $html .='  
   
 </table>
 
  
  ';
        
  
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('ATTENDANCE sHEET.pdf', 'I');
 
     
}
	  
 



    $conn->close();
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
?>	