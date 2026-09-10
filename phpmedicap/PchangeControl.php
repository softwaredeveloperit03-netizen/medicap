<?php 
require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';

 
//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);




 






 $timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);
  
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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
if ($_GET["type"] == "saveQmsChangeControl") {
                    $input    = $_POST;
                    $target_dir = "../../upload/changeControl/pritam/";
                $ic =1;    
            $sql = "SELECT max(id) as Key_Id  FROM p_changeControl   ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                   $ic = $row['Key_Id'] + 1;
                }
            }else{
                 $ic =1;  
            }
                    $rationalChangeFile = "";
                    if(isset($_FILES["rationalChangeFile"]["name"])){
                        $target_file = $target_dir.$ic."rationalChangeFile".basename($_FILES["rationalChangeFile"]["name"]);
                        $rationalChangeFile = $ic."rationalChangeFile".basename($_FILES["rationalChangeFile"]["name"]).$file_ext;
                        move_uploaded_file($_FILES["rationalChangeFile"]["tmp_name"], $target_file);
                    }
                   
                      if($input['isMajor']==true){
                        $devType='Major';
                    }else{
                         $devType='Minor';
                    }
                   
                     $sql = "INSERT INTO `p_changeControl`( `plant_id`, `entry_by`, `entry_date`,   `initiateDepartment`, 
                    `initiateDate`, `SpecifyDetail`, `prodMatStageDoc`, `batch_no`, `descriptionProposedChange`, 
                    `presentProcedure`, `rationalChange`, `rationalChangeFile`,ChangeRetaedto) VALUES ( '".$_GET['plant_id']."',
                    '".$_GET['emp_id']."','$entry_date','".$input['initiateDepartment']."','".$input['initiateDate']."','".$input['SpecifyDetail']."','".$input['prodMatStageDoc']."',
                    '".$input['batch_no']."','".$input['descriptionProposedChange']."','".$input['presentProcedure']."','".$input['rationalChange']."','$rationalChangeFile','".$input['ChangeRetaedto']."')";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
if ($_GET["type"] == "Update_CC_ForConsentAndReview") {
                   
                   
                     $sql = "update   p_changeControl  set hod_cmt='".$input["hod_cmt"]."' ,status='For QA Executive / Officer' ,
                 hod_review_by='".$_GET["emp_id"]."',hod_review_on='$entry_date' where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
 else if($_GET['type'] == 'get_CC_ForConsentAndReview'){ 
            $output = Array();
            $sql = "SELECT * FROM  p_changeControl  WHERE  status= 'For Department Head' and initiateDepartment='".$_GET["deptName"]."' AND plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                       $row["ChangeRetaedto"] = json_decode($row["ChangeRetaedto"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
if ($_GET["type"] == "Update_QA_manager_cmts_Deviation") {
                   
                   
                     $sql = "update  p_changeControl  set status='For QA Head' ,QA_manager_cmts='".$input["QA_manager_cmts"]."',
                 qa_manager_by='".$_GET["emp_id"]."',qa_manager_on='$entry_date' where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
if ($_GET["type"] == "Update_QA_Head_cmts_Deviation") {
                   
                   
                     $sql = "update  p_changeControl  set status='Complete' ,QA_head_cmts='".$input["QA_head_cmts"]."',
                 qa_head_by='".$_GET["emp_id"]."',qa_head_on='$entry_date' where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}

       else if($_GET['type'] == 'get_CC_ForConsentAndReview_For_QA_Office'){ 
            $output = Array();
            $sql = "SELECT * FROM p_changeControl  WHERE  status= 'For QA Executive / Officer' and  plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["ChangeRetaedto"] = json_decode($row["ChangeRetaedto"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDeviationForConsentAndReview_For_QA_Manager'){ 
            $output = Array();
            $sql = "SELECT * FROM p_changeControl  WHERE  status= 'For QA Manager' and  plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["ChangeRetaedto"] = json_decode($row["ChangeRetaedto"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDeviationForConsentAndReview_For_QA_Head'){ 
            $output = Array();
            $sql = "SELECT * FROM p_changeControl  WHERE  status= 'For QA Head' and  plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["ChangeRetaedto"] = json_decode($row["ChangeRetaedto"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDEviationLog'){ 
            $output = Array();
            $sql = "SELECT * FROM p_changeControl  WHERE   plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["ChangeRetaedto"] = json_decode($row["ChangeRetaedto"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDeviationForConsentAndReview_For_Impact_departments'){
           $output = array();

$sql = "
SELECT pd.*, 
       JSON_ARRAYAGG(
           JSON_OBJECT(
               'department_name', jt.department_name,
               'status', jt.status,
               'remarks', jt.remarks,
               'name', jt.name,
               'date', jt.date
           )
       ) AS matching_departments
FROM p_changeControl  pd
JOIN JSON_TABLE(
    pd.ChangeRetaedto, '$[*]' 
    COLUMNS(
        department_name VARCHAR(100) PATH '$.department_name',
        status VARCHAR(20) PATH '$.status',
        remarks VARCHAR(255) PATH '$.remarks',
        name VARCHAR(100) PATH '$.name',
        date VARCHAR(50) PATH '$.date'
    )
) AS jt
WHERE jt.department_name = '".$_GET["deptName"]."' 
  AND jt.status = 'Pending'
  AND pd.plant_id = '".$_GET["plant_id"]."'
GROUP BY pd.id
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Decode filtered impactDepartments JSON array
        $row["matching_departments"] = json_decode($row["matching_departments"]);
        // Decode original impactDepartments if you want
        $row["ChangeRetaedto"] = json_decode($row["ChangeRetaedto"]);
        $output[] = $row;
    }
}

echo json_encode($output);
}
else if ($_GET["type"] == "Update_impact_departments") {
                   
                   
                     $sql = "update  p_changeControl  set ChangeRetaedto= '".json_encode($input["ChangeRetaedto"])."'    where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
      
   else if($_GET['type'] == 'downloadDeviation'){
         $_GET['filename'] = '';
        $_GET['pdftype'] = 'onlyheader';
        include("./pdfimp2.php");
        
$id = $_GET['id'] ?? 0;

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Company');
$pdf->SetTitle('Deviation Report');
$pdf->SetHeaderData('', 0, 'Deviation Report', '');
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->SetFont('dejavusans', '', 10);
$pdf->AddPage();

// Fetch deviation data
$sql = "SELECT * FROM p_changeControl  WHERE id = '".$id."'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Title
    $html = '<h2 style="text-align:center; color:#0B3D91; margin-bottom:10px;">Deviation Report</h2>';

    // Section 1: Basic Info
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 10pt;">
        <tr style="background-color:#D6EAF8;">
            <td><b>Initiating Department</b></td>
            <td colspan="3">'.$row['initiateDepartment'].'</td>
            </tr>
        <tr>
            <td><b>Deviation No</b></td>
            <td>'.$row['deviation_no'].'</td>
        
            <td><b>Date</b></td>
            <td>'.date('d-m-Y', strtotime($row['initiateDate'])).'</td>
            </tr>
        <tr>
            <td><b>Product/Material Name/Stage/Document</b></td>
            <td>'.$row['prodMatStageDoc'].'</td>
        
            <td><b>Batch No</b></td>
            <td>'.$row['batch_no'].'</td>
            </tr>
        <tr>
            <td><b>MFG Date</b></td>
            <td>'.date('d-m-Y', strtotime($row['mfg_date'])).'</td>
        
            <td><b>Exp Date</b></td>
            <td>'.date('d-m-Y', strtotime($row['exp_date'])).'</td>
             
        </tr>
    </table><br>';

    // Section 2: Deviation Details
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 10pt;">
        <tr style="background-color:#E8F6F3;">
            <td width="30%"><b>1. Type of Deviation</b></td>
            <td width="70%">'.$row['deviation_type'].'</td>
        </tr>
        <tr>
            <td colspan="2"><b>2. Actual Process</b><br>'.$row['actualProcedure'].'</td>
        </tr>
        <tr>
            <td colspan="2"><b>3. Deviation Observed</b><br>'.$row['deviationObserved'].'</td>
        </tr>
        <tr  >
            <td   colspan="2"><b>4. Root Cause, if Observed</b><br>'.$row['rootCause'].'</td>
            
        </tr>
        <tr>
            <td colspan="2"><b>5. Risk Assessment</b><br>'.$row['RiskAssessment'].'</td>
        </tr>
        <tr>
            <td colspan="2"><b>6. Action Taken / To Be Taken</b><br>'.$row['ActionTaken'].'</td>
        </tr>
        <tr>
            <td colspan="2"><b>7. Initiated By</b><br>'.$row['entry_by'].' : '.date('d-m-Y H:i', strtotime($row['entry_date'])).' / '.$row['initiateDepartment'].'</td>
        </tr>
        <tr>
            <td colspan="2"><b>8. HOD / Designee of Initiator</b><br>'.$row['hod_review_by'].' : '.date('d-m-Y H:i', strtotime($row['hod_review_on'])).'</td>
        </tr>
   ';

    // Section 3: Impacting Departments
    $html .= '<tr>
            <td colspan="2"><b>9. Comments by Impacting Department</b></td>
        </tr> </table>';
    $impactingDepartments = json_decode($row['ChangeRetaedto'], true);
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 10pt;">
        <tr style="background-color:#FADBD8;">
            <th>Department Name</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Name</th>
            <th>Date</th>
        </tr>';
    if($impactingDepartments) {
        foreach($impactingDepartments as $dept) {
            $html .= '<tr>
                <td>'.$dept['department_name'].'</td>
                <td>'.$dept['status'].'</td>
                <td>'.$dept['remarks'].'</td>
                <td>'.$dept['name'].'</td>
                <td>'.$dept['date'].'</td>
            </tr>';
        }
    }
    $html .= '</table><br>';

    // Section 4: Manager / Client / QA Comments
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 10pt;">
        <tr style="background-color:#D5F5E3;">
            <td colspan="2"><b>10. Comments from Manager QA</b><br>'.$row['QA_manager_cmts'].'</td>
        </tr>
        <tr style="background-color:#D5F5E3;">
            <td colspan="2"><b>11. Comments from Principle Company (if required)</b><br>'.$row['clintCmt'].'</td>
        </tr>
        <tr style="background-color:#D5F5E3;">
            <td colspan="2"><b>12. Review Comments</b><br>'.$row['QA_head_cmts'].'</td>
        </tr>
    </table>';

    // Write HTML to PDF
    $pdf->writeHTML($html, true, false, false, false, '');
}

// Output PDF
$pdf->Output('deviation_report.pdf', 'I');

   }
    
    
    
    
    
    
} else {
    echo "Invalid Token";
}

$conn->close();
?>