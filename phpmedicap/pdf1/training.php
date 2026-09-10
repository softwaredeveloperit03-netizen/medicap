<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = $_GET["token"];
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
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
    if($_GET['type'] == 'trainingneedslog') {
        $_GET['filename'] = 'Training Needs Log'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html.='
        <table cellpadding="5">
            <tr style="font-weight:bold;">
                <td style="width:6%;">Sr.</td>
                <td style="width:20%">Department</td>
                <td style="width:15%">Proposed Date</td>
                <td style="width:16%">Subject</td>
                <td style="width:15%">Proposed Trainer</td>
                <td style="width:15%">Prepared By</td>
                <td style="width:15%">Approved By</td>
            </tr>';
            $sql = "SELECT * FROM training_needs";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                        <td>'.$row['department'].'</td>
                        <td>'.$row['proposed_date'].'</td>
                        <td>'.$row['subject'].'</td>
                        <td>'.$row1["trainer_name"].'</td>
                        <td>'.$row['entry_by'].'</td>
                        <td>'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='
        </table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'identificationneedslog') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
            // $sql = "SELECT * FROM training_needs";
                    $sql = "SELECT * FROM training_needs WHERE plant_id = '".$_GET["plant_id"]."'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
    // $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
    $sql1 = "SELECT * FROM externaltrainer  where id =".$row["proposed_trainer"];
                // $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'questionaries') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
            // $sql = "SELECT * FROM training_needs";
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'Need-Base Training'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
    // $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                // $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'traininglogr') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
        $sql = "SELECT * FROM training_needs WHERE department = 'Management'  AND plant_id = '".$_GET["plant_id"]."'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
                
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'traininglogrd') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
        $sql = "SELECT * FROM training_needs WHERE  department = 'Human Resource'  AND plant_id = '".$_GET["plant_id"]."'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'downloadtraininglog') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
        $sql = "SELECT * FROM training_needs WHERE status='active' AND department = 'Human Resource'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'traininglogrd') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
        $sql = "SELECT * FROM training_needs WHERE status='active' AND department = 'Human Resource'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'identificationneedslogs') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
               $sql = "SELECT * FROM training_needs WHERE subject = 'Need-Base Training'  AND plant_id = '".$_GET["plant_id"]."'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
 $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'identificationneedsonjobtrainglog') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
        $sql = "SELECT * FROM training_needs WHERE subject = 'On Job Training'  AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
 $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'trainingschedulelogr') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Department</b></td>
           <td style="width:20%;text-align:center"><b>Subject</b></td>
            <td style="width:15%;text-align:center"><b>Proposed Trainer</b></td>
             <td style="width:15%;text-align:center"><b>Prepared By</b></td>
             <td style="width:15%;text-align:center"><b>Approved By</b></td>
             <td style="width:15%;text-align:center"><b>Proposed Date </b></td>
         </tr>';
         
               $sql = "SELECT * FROM training_needs WHERE subject = 'Need-Base Training'  AND plant_id = '".$_GET["plant_id"]."'";

 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
 $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['department'].'</td>
           <td style="width:20%;text-align:center">'.$row['subject'].'</td>
            <td style="width:15%;text-align:center">'.$row['trainer_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['approve_by'].'</td>
             <td style="width:15%;text-align:center">'.$row['proposed_date'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
   else if($_GET['type'] == 'identificationoftrainingneeds') { 
    
        $_GET['filename'] = 'Identification of Training Needs'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

            $sql = "SELECT * FROM training_needs WHERE   id='".$_GET['id']."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2['firstname'].''.$row2['lastname'].'</td>
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
    else if($_GET['type'] == 'trainingneedsviewsre') { 
    
        $_GET['filename'] = 'Identification of Training Needs'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

            $sql = "SELECT * FROM training_needs WHERE   id='".$_GET['id']."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2['firstname'].''.$row2['lastname'].'</td>
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
   
   
     
    else if($_GET['type'] == 'trainingneedsviews') { 
    
        $_GET['filename'] = 'Identification of Training Needs'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

            $sql = "SELECT * FROM training_needs WHERE   id='".$_GET['id']."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT firstname,lastname,department,designation FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2['firstname'].''.$row2['lastname'].'</td>
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
    else if($_GET['type'] == 'trainingneedsviewrd') { 
    
        $_GET['filename'] = 'Training Questionaries'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

        $sql = "SELECT * FROM training_needs WHERE department = 'Marketing'  AND plant_id = '".$_GET["plant_id"]."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2['emp_name'].'</td>
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
    else if($_GET['type'] == 'qmstrainingneedsview') { 
    
        $_GET['filename'] = 'Training Questionaries'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

        $sql = "SELECT * FROM training_needs WHERE subject = 'QMS Training'  AND plant_id = '".$_GET["plant_id"]."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM  employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2['firstname'].'</td>
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
    else if($_GET['type'] == 'trainingneedsviewdetails') { 
    
        $_GET['filename'] = 'Training Questionaries'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

            $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND id='".$_GET['id']."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer  WHERE id=".$row["trainer_name"];
                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row1["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2["firstname"].' '.$row2["lastname"].'</td>
 
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
    
    else if($_GET['type'] == 'trainingneedsview') { 
    
        $_GET['filename'] = 'Training Questionaries'; 
        $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

            $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND id='".$_GET['id']."'";
           $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer  WHERE id=".$row["trainer_name"];
                    $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                   }
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:30%;"><b>Training Subject:</b></td>
                            <td style="width:70%">'.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td><b>Reference Document:</b></td>
                            <td>'.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Trainer:</b></td>
                            <td>'.$row1["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td><b>Proposed Training Date:</b></td>
                            <td>'.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td><b>Justification for Training Needs:</b></td>
                            <td>'.$row['training_need'].'</td>
                        </tr>
                        </table>';
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                    
                    $html.='<br><br><table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:10%;">Sr.</td>
                            <td style="width:24%;">Name of Employee</td>
                            <td style="width:22%;">Employee Code</td>
                            <td style="width:22%;">Department</td>
                            <td style="width:22%;">Designation</td>
                        </tr>';
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                            $result2 = $conn->query($sql2);
                            $row2 = $result2->fetch_assoc();
                            $html.='
                            <tr>
                                <td style="width:10%;">'.$counter++.'</td>
                                <td style="width:24%;">'.$row2["firstname"].' '.$row2["lastname"].'</td>
 
                                <td style="width:22%;">'.$row1['emp_id'].'</td>
                                <td style="width:22%;">'.$row2['department'].'</td>
                                <td style="width:22%;">'.$row2['designation'].'</td>
                            </tr>';
                        }
                        $html.='</table>';
                    }
                }
                $html.='<div></div>';
                
               EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
                    
            } else{
                echo 'No Record';
            }
    }
    else if($_GET['type'] == 'qsmtrainingschedulelog') {
        $_GET['filename'] = 'Training Schedule Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                 <td style="width:10%">Proposed Date</td>
                <td style="width:23%">Subject</td>
                <td style="width:15%">Proposed Trainer</td>
                <td style="width:10%">Training Date</td>
                <td style="width:10%">Training Time</td>
                <td style="width:10%">Prepared By</td>
                <td style="width:10%">Approved By</td>
                 <td style="width:7%">Status</td>
            </tr>';
        $sql = "SELECT * FROM training_needs WHERE status='active' AND subject = 'QMS Training'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                         <td>'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td>'.$row['subject'].' / '.$row['reference_document'].'</td>
                        <td>'.$row1["trainer_name"].'</td>
                        <td>'.date('d-m-Y',strtotime($row['training_date'])).'</td>
                        <td> '.date("g:i A", strtotime($row['training_time'])).'</td>
                        <td>'.$row['entry_by'].'</td>
                        <td>'.$row['approve_by'].'</td>
                         <td>'.$row['status'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'trainingschedulelog') {
        $_GET['filename'] = 'Training Schedule Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                 <td style="width:10%">Proposed Date</td>
                <td style="width:23%">Subject</td>
                <td style="width:15%">Proposed Trainer</td>
                <td style="width:10%">Training Date</td>
                <td style="width:10%">Training Time</td>
                <td style="width:10%">Prepared By</td>
                <td style="width:10%">Approved By</td>
                 <td style="width:7%">Status</td>
            </tr>';
        $sql = "SELECT * FROM training_needs WHERE status='active' AND department = 'Purchase'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT trainer_name FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                         <td>'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td>'.$row['subject'].' / '.$row['reference_document'].'</td>
                        <td>'.$row1["trainer_name"].'</td>
                        <td>'.date('d-m-Y',strtotime($row['training_date'])).'</td>
                        <td> '.date("g:i A", strtotime($row['training_time'])).'</td>
                        <td>'.$row['entry_by'].'</td>
                        <td>'.$row['approve_by'].'</td>
                         <td>'.$row['status'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    
    else if($_GET['type'] == 'trainingRetraininglog'){
        $_GET['filename'] = 'Reraining Schedule Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                 <td style="width:10%">Proposed Date</td>
                <td style="width:23%">Subject</td>
                <td style="width:15%">Proposed Trainer</td>
                <td style="width:10%">Training Date</td>
                <td style="width:10%">Training Time</td>
                <td style="width:10%">Prepared By</td>
                <td style="width:10%">Approved By</td>
                 <td style="width:7%">Status</td>
            </tr>';
            // $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'";
                    $sql = "SELECT * FROM training_needs WHERE status='active' AND retraining_status = 'YES'   AND department = 'Admin' ";

            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT trainer_name FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                         <td>'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td>'.$row['subject'].' / '.$row['reference_document'].'</td>
                        <td>'.$row1["trainer_name"].'</td>
                        <td>'.date('d-m-Y',strtotime($row['training_date'])).'</td>
                        <td> '.date("g:i A", strtotime($row['training_time'])).'</td>
                        <td>'.$row['entry_by'].'</td>
                        <td>'.$row['approve_by'].'</td>
                         <td>'.$row['status'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            // EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Retrainingneeds.pdf', 'I');
    }
    
    
    
    else if($_GET['type'] == 'attendencelog'){
        $_GET['filename'] = 'Training Attendance Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:6%;text-align:center;"><b>Sr.</b></td>
                <td style="width:18%;text-align:center;"><b>Department<b></td>
                <td style="width:15%;text-align:center;"><b>Subject<b></td>
                <td style="width:16%;text-align:center;"><b>Trainer Name<b></td>
                <td style="width:15%;text-align:center;"><b>Date<b></td>
                <td style="width:15%;text-align:center;"><b>Time<b></td>
                <td style="width:15%;text-align:center;"><b>Venue<b></td>
            </tr>';
        $counter=1;
        $sql = "SELECT * FROM training_needs WHERE attendance='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["trainer_name"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                $html.='
                <tr>
                    <td>'.$counter++.'</td>
                    <td>'.$row['department'].'</td>
                    <td>'.$row['subject'].'</td>
                    <td>'.$row["trainer_name"].'</td>
                    <td>'.date('d-m-Y',strtotime($row["training_date"])).'</td>
                    <td>'.$row['training_time'].'</td>
                    <td>'.$row['venue'].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'attendenceview'){
        $_GET['filename'] = 'Training Attendance Record'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");

            $sql = "SELECT * FROM training_needs WHERE attendance='active' AND id='".$_GET['id']."'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <table cellpadding="5">
                        <tr>
                            <td style="width:40%;">Name of Department</td>
                            <td style="width:60%;">: '.$row['department'].'</td>
                        </tr>
                        <tr>
                            <td>Date of training</td>
                            <td>: '.$row['proposed_date'].'</td>
                        </tr>
                        <tr>
                            <td>Trainers Name</td>
                            <td>: '.$row1["trainer_name"].'</td>
                        </tr>
                        <tr>
                            <td>Venue of Training</td>
                            <td>: '.$row['venue'].'</td>
                        </tr>
                        <tr>
                            <td>Training Subject</td>
                            <td>: '.$row['subject'].'</td>
                        </tr>
                        <tr>
                            <td>Reference Document No./System</td>
                            <td>: '.$row['reference_document'].'</td>
                        </tr>
                        <tr>
                            <td>Training Tools Used</td>
                            <td>: '.$row['training_tool'].'</td>
                        </tr>
                        <tr>
                            <td>Training Start Time</td>
                            <td>: '.$row['training_start_time'].'</td>
                        </tr>
                        <tr>
                            <td>Training End Time</td>
                            <td>: '.$row['training_end_time'].'</td>
                        </tr>
                    </table>
                    <div></div>
                    <table cellpadding="5">
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width:8%;">Sr.</td>
                            <td style="width:23%">Name of the Trainee</td>
                            <td style="width:23%">Department</td>
                            <td style="width:23%">Designation</td>
                            <td style="width:23%">Attendance</td>
                        </tr>';
                    
                    $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter=1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM employee WHERE emp_id='".$row1["emp_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                                    $html.='
                                        <tr>
                                            <td>'.$counter++.'</td>
                                            <td>'.$row1["firstname"].' '.$row1["lastname"].'</td>
                                            <td>'.$row1["department"].'</td>
                                            <td>'.$row1["designation"].'</td>
                                            <td>'.$row1["attendance"].'</td>
                                        </tr>'  ;
                        }
                    }
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'trainingrecords'){
        $_GET['filename'] = 'Training Record'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
       
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">			
                <td style="width:5%;">Sr.</td>
                <td style="width:12%;">Date</td>
                <td style="width:15%;">Subject</td>
                <td style="width:11%;">Venue</td>
                <td style="width:11%;">Duration</td>
                <td style="width:15%;">Trainers Name</td>
                <td style="width:11%;">Attendance</td>
                <td style="width:9%;">Marks</td>
                <td style="width:11%;">Feedback</td>
            </tr>';
        $output = Array();
        $sql = "SELECT * FROM tn_employees WHERE emp_id='".$_GET["employee"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $counter = 1;
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM training_needs WHERE id='".$row["tn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["attendance"] = $row["attendance"];
                        $row1["marks"] = $row["marks"];
                        $row1["feedback"] = $row["feedback"];
                    
                        $start = strtotime($row1["training_start_time"]);
                        $end = strtotime($row1["training_end_time"]);
                        $elapsed = $end - $start;
                        
                     $sql2 = "SELECT * FROM externaltrainer WHERE id=".$row1["proposed_trainer"];
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["trainer_name"] = $row2["trainer_name"];
                            }
                        }
                        $html.='<tr>			
                            <td>'.$counter++.'</td>
                            <td>'.$row1["proposed_date"].'</td>
                            <td>'.$row1["subject"].'</td>
                            <td>'.$row1["venue"].'</td>
                            <td>'.date("H:i", $elapsed).'</td>
                            <td>'.$row1["trainer_name"].'</td>
                            <td>'.$row["attendance"].'</td>
                            <td>'.$row["marks"].'</td>
                            <td>'.$row["feedback"].'</td>
                        </tr>';
                    }
                }
            }
        }
        $html.='</table>
        <div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
        
    }
    else if($_GET['type'] == 'questionariesrem') {
        $_GET['filename'] = 'Training Questionaries Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        
        <div></div>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department</td>
                <td style="width:20%;">Subject</td>
                <td style="width:20%;">Trainer Name</td>
                <td style="width:20%;">Evaluator</td>
                <td style="width:10%;">Duration</td>
                <td style="width:10%;">Marks</td>
            </tr>';
        $i=1;
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'On Job Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                $row["questions"] = json_decode($row["questions"]);
                $html.='
                <tr> 
                    <td style="width:5%;">'.$i++.'</td>
                    <td style="width:15%;">'.$row['department'].'</td>
                    <td style="width:20%;">'.$row['subject'].'</td>
                    <td style="width:20%;">'.$row["trainer_name"].'</td>
                    <td style="width:20%;">'.$row['evaluator_name'].'</td>
                    <td style="width:10%;">'.$row['duration'].' Mins.</td>
                    <td style="width:10%;">'.$row['marks'].'</td>
                </tr>';
            }
        }
        $html.='</table>
        <div></div>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Training Questionaries Log.pdf', 'I');
    }
    
    
    
    
    
    
    
    
    
    
    else if($_GET['type'] == 'questionaries') {
        $_GET['filename'] = 'Training Questionaries Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        
        <div></div>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department</td>
                <td style="width:20%;">Subject</td>
                <td style="width:20%;">Trainer Name</td>
                <td style="width:20%;">Evaluator</td>
                <td style="width:10%;">Duration</td>
                <td style="width:10%;">Marks</td>
            </tr>';
        $i=1;
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'Documentation Training'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                $row["questions"] = json_decode($row["questions"]);
                $html.='
                <tr> 
                    <td style="width:5%;">'.$i++.'</td>
                    <td style="width:15%;">'.$row['department'].'</td>
                    <td style="width:20%;">'.$row['subject'].'</td>
                    <td style="width:20%;">'.$row["trainer_name"].'</td>
                    <td style="width:20%;">'.$row['evaluator_name'].'</td>
                    <td style="width:10%;">'.$row['duration'].' Mins.</td>
                    <td style="width:10%;">'.$row['marks'].'</td>
                </tr>';
            }
        }
        $html.='</table>
        <div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Training Questionaries Log.pdf', 'I');
    }
    else if($_GET['type'] == 'questionariesview') {
        $_GET['filename'] = 'Training Questionaries';
        $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()){
                $_GET['filename'] = 'Training Questions'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $html.='
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:20%;">Department</td>
                        <td style="width:20%;">Subject</td>
                        <td style="width:20%;">Trainer Name</td>
                        <td style="width:20%;">Evaluator</td>
                        <td style="width:10%;">Duration</td>
                        <td style="width:10%;">Marks</td>
                    </tr>
                    <tr>
                        <td>'.$row['department'].'</td>
                        <td>'.$row['subject'].'</td>
                        <td>'.$row["trainer_name"].'</td>
                        <td>'.$row['evaluator_name'].'</td>
                        <td>'.$row['duration'].'</td>
                        <td>'.$row['marks'].'</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">';
                $row["questions"] = json_decode($row["questions"]);
                $questions = $row["questions"];
                    for($i=0; $i < count($questions); $i++){
                        $question = $questions[$i];
                        $j = $i + 1;
                    $html.='
                    <tr>
                        <td style="border:none; width:10%;"><b>Que. - '.$j.'</b></td>
                        <td style="border:none; width:90%;"><b>'.$question->question.'</b></td>
                    </tr>
                    <tr>
                        <td style="border:none;"></td>
                        <td style="border:none;"><b>Option 1 -</b> '.$question->option1.'</td>
                    </tr>
                    <tr>
                        <td style="border:none;"></td>
                        <td style="border:none;"><b>Option 2 -</b> '.$question->option2.'</td>
                    </tr>
                    <tr>
                        <td style="border:none;"></td>
                        <td style="border:none;"><b>Option 3 -</b> '.$question->option3.'</td>
                    </tr>
                    <tr>
                        <td style="border:none;"></td>
                        <td style="border:none;"><b>Option 4 -</b> '.$question->option4.'</td>
                    </tr>
                    <tr>
                        <td style="border:none;"></td>
                        <td style="border:none;"><b>Answer - '.$question->answer.'</b></td>
                    </tr>';						
                    }
                $html.='
                </table>';
            }
        }
        $html.='<div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'traininglogdocumentlog') {
        $_GET['filename'] = 'Identification of Training Needs'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:10%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
         $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'Documentation Training'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:10%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'qmstraininglog') {
        $_GET['filename'] = 'Identification of QMS Training'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:15%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
            // $sql = "SELECT * FROM training_needs";
        $sql = "SELECT * FROM training_needs WHERE subject = 'QMS Training'  AND plant_id = '".$_GET["plant_id"]."'";

            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:10%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    
    else if($_GET['type'] == 'qmstraiinglogrecored') {
        $_GET['filename'] = 'Qms traiing Log Record'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Qms traiing Log Record</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:15%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
            // $sql = "SELECT * FROM training_needs";
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND questionaries = 'active' AND subject = 'QMS Training'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:10%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'traininglogdm') {
        $_GET['filename'] = 'Identification of Training Needs'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:15%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
            // $sql = "SELECT * FROM training_needs";
        $sql = "SELECT * FROM training_needs WHERE department = 'Purchase'  AND plant_id = '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:15%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
         
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    
    else if($_GET['type'] == 'traininglog') {
        $_GET['filename'] = 'Identification of Training Needs'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:10%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
            // $sql = "SELECT * FROM training_needs";
                // $sql = "SELECT * FROM training_needs WHERE attendance='active' AND questionaries = 'active' AND department = 'Admin'";
                        // $sql = "SELECT * FROM training_needs WHERE questionaries='active' AND subject = 'Need-Base Training'";
                                $sql = "SELECT * FROM training_needs WHERE attendance='active' AND questionaries = 'active' AND department = 'Purchase'";


            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:10%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
             $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'traininglog07') {
        $_GET['filename'] = 'Identification of Training Needs'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:10%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
         
        $sql = "SELECT * FROM training_needs WHERE department = 'Production'  AND plant_id = '".$_GET["plant_id"]."'";


            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:10%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
             $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'traininglogrwe') {
        $_GET['filename'] = 'Identification of Training Needs'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:15%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
            // $sql = "SELECT * FROM training_needs";
        $sql = "SELECT * FROM training_needs WHERE attendance='active' AND questionaries = 'active' AND subject = 'Documentation Training'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:15%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'trainingdocumentlog') {
        $_GET['filename'] = 'Identification of Training Needs'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h4 style="text-align:center">Identification of training Needs</h4>
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <td style="width:5%;">Sr.</td>
                <td style="width:15%;">Department.</td>
                <td style="width:20%">Proposed Date	</td>
                <td style="width:15%">Subject</td>
                <td style="width:15%">Proposed Trainer	</td>
                <td style="width:15%">Prepared By	</td>
                <td style="width:15%">Approved By	</td>
               
            </tr>';
            // $sql = "SELECT * FROM training_needs";
                $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM tn_employees WHERE tn_no='".$row["id"]."'";

                    $result1 = $conn->query($sql1);
                    $row1 = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:15%">'.$row['department'].'</td>
                        <td style="width:20%">'.date('d-m-Y',strtotime($row['proposed_date'])).'</td>
                        <td style="width:15%">'.$row['subject'].'</td>
                        <td style="width:10%">'.$row1["trainer_name"].'</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                        <td style="width:15%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='</table><div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('trainingneeds.pdf', 'I');
    }
     
    
    else if($_GET['type'] == 'inductiontraining'){
        $_GET['filename'] = 'Induction Training'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:6%;">Sr.</td>
                <td style="width:18%">Employee</td>
                <td style="width:15%">Learning</td>
                <td style="width:16%">Equipment Seen</td>
                <td style="width:15%">Training Start Date</td>
                <td style="width:15%">Training End Date</td>
                <td style="width:15%">status</td>
            </tr>';
            $sql = "SELECT * FROM induction_training";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT emp_name FROM employee WHERE emp_id='".$row["entry_by"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["emp_name"] = $row1["emp_name"];
                        }
                    }
                    $html.='
                    <tr>
                        <td style="width:6%">'.$counter++.'</td>
                        <td style="width:18%">'.$row1['emp_name'].'</td>
                        <td style="width:15%">'.$row1['learning'].'</td>
                        <td style="width:16%">'.$row1['equipmentseen'].'</td>
                        <td style="width:15%">'.$row1['trainingfrom'].'</td>
                        <td style="width:15%">'.$row1['trainingto'].'</td>
                        <td style="width:15%">'.$row1['status'].'</td>
                    </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
    }
    else if($_GET['type'] == 'selfcertificatereport'){
        $_GET['filename'] = 'Self Certification Training Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                <td style="width:10%">Date</td>
                <td style="width:11%">Employee Name</td>
                <td style="width:11%">SOP No./ Receiving no</td>
                <td style="width:21%">Title of SOP / Document</td>
                <td style="width:11%">Department</td>
                <td style="width:11%">Outcome</td>
                <td style="width:10%">Status</td>
                <td style="width:10%">Approve by</td>
            </tr>';
            $sql = "SELECT * FROM self_certificate";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM sops WHERE sop_no='".$row["sop_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows >0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["sop_name"] = $row1["sop_name"];
                            $row["department"] = $row1["department"];
                            break;
                        }
                    }
                    $sql1 = "SELECT * FROM employee WHERE emp_id='".$row["entry_by"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows >0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["emp_name"] = $row1["emp_name"];
                            break;
                        }
                    }
                    $html.='
                    <tr>
                        <td style="width:5%">'.$counter++.'</td>
                        <td style="width:10%">'.$row['entry_date'].'</td>
                        <td style="width:11%">'.$row["emp_name"].'</td>
                        <td style="width:11%">'.$row['sop_no'].'</td>
                        <td style="width:21%">'.$row["sop_name"].'</td>
                        <td style="width:11%">'.$row['department'].'</td>
                        <td style="width:11%">'.$row['outcome'].'</td>
                        <td style="width:10%">'.$row['status'].'</td>
                        <td style="width:10%">'.$row['approve_by'].'</td>
                    </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('trainingneeds.pdf', 'I');
    }
}else{
    echo "Invalid Token";
}
?>