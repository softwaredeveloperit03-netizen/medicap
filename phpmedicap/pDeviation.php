<?php 
require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';

 
//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);




function downloadPdf($c_cont,$devOccuredDept) {
 
 
          $cid = $_GET["client_no"];
        $fileName = $c_cont;
          
         
        $sms = '*WelCome To Meha QA Department , New Deviation has been Enrolled By '.$_GET['emp_id'].' In dept '.$devOccuredDept.' Please Login to meha.cpplgmp.com for more detailes and approval of deviation*';

         
        $apikey = "4d1e71a175894c17727931890d8d50850e5de95d";
        $instance = "apK9itvfIRxOZEr";
        
        // Construct the URL
     $url = 'https://app.nationalbulksms.com/api/send-media.php?number=+91' . urlencode($c_cont)
    . '&msg=' . urlencode($sms)
    . '&media=' . urlencode('https://mehapharma.com/wp-content/uploads/2024/01/MEHA_PHARMA2-1-1.png')
    . '&apikey=' . urlencode($apikey) 
    . '&instance=' . urlencode($instance);
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // Set options to return the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            // Success
          //  echo 'Response from API: ' . $response;
        }
        
        // Close cURL session
        curl_close($ch);
        
         
 
}






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
    
if ($_GET["type"] == "saveQmsDeviations") {
                    $input    = $_POST;
                    $target_dir = "../../upload/deviation/pritam/";
                $ic =1;    
            $sql = "SELECT max(id) as Key_Id  FROM p_deviation  ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                   $ic = $row['Key_Id'] + 1;
                }
            }else{
                 $ic =1;  
            }
                    $rootCauseFile = "";
                    if(isset($_FILES["rootCauseFile"]["name"])){
                        $target_file = $target_dir.$ic."rootCauseFile".basename($_FILES["rootCauseFile"]["name"]);
                        $rootCauseFile = $ic."rootCauseFile".basename($_FILES["rootCauseFile"]["name"]).$file_ext;
                        move_uploaded_file($_FILES["rootCauseFile"]["tmp_name"], $target_file);
                    }
                   
                      if($input['isMajor']==true){
                        $devType='Major';
                    }else{
                         $devType='Minor';
                    }
                   
                     $sql = "INSERT INTO `p_deviation`(`plant_id`, `entry_by`, `entry_date`, `initiateDepartment`, `initiateDate`,  
                    `prodMatStageDoc`, `batch_no`, `mfg_date`, `exp_date`, `deviation_type`, `actualProcedure`, `deviationObserved`, `detailsOfDev`, 
                    `rootCause`, `RiskAssessment`, `ActionTaken`, `rootCauseFile`) VALUES ('".$_GET['plant_id']."','".$_GET['emp_id']."','$entry_date',
                  '".$input['initiateDepartment']."','".$input['initiateDate']."','".$input['prodMatStageDoc']."',
                    '".$input['batch_no']."','".$input['mfg_date']."','".$input['exp_date']."','$devType',
                    '".$input['actualProcedure']."','".$input['deviationObserved']."','$devType','".$input['rootCauses']."',
                    '".$input['RiskAssessment']."','".$input['ActionTaken']."','$rootCauseFile') ";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
if ($_GET["type"] == "Update_DeviationForConsentAndReview") {
                   
                   
                     $sql = "update  p_deviation set impactDeparments='".json_encode($input["impactDeparments"])."' ,status='For QA Manager' ,
                 hod_review_by='".$_GET["emp_id"]."',hod_review_on='$entry_date' where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
if ($_GET["type"] == "Update_QA_manager_cmts_Deviation") {
                   
                   
                     $sql = "update  p_deviation set status='For QA Head' ,QA_manager_cmts='".$input["QA_manager_cmts"]."',
                 qa_manager_by='".$_GET["emp_id"]."',qa_manager_on='$entry_date' where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
if ($_GET["type"] == "Update_QA_Head_cmts_Deviation") {
                   
                   
                     $sql = "update  p_deviation set status='Complete' ,QA_head_cmts='".$input["QA_head_cmts"]."',
                 qa_head_by='".$_GET["emp_id"]."',qa_head_on='$entry_date' where id ='".$input["id"]."'";
           	if($conn->query($sql))
           	        {echo "{\"status\":\"success\"}";
           	}
           	else {
           	        echo "{\"status\":\"".$conn->error."\"}";
           	    }
}
       else if($_GET['type'] == 'getDeviationForConsentAndReview'){ 
            $output = Array();
            $sql = "SELECT * FROM p_deviation WHERE  status= 'For Department Head' and initiateDepartment='".$_GET["deptName"]."' AND plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDeviationForConsentAndReview_For_QA_Manager'){ 
            $output = Array();
            $sql = "SELECT * FROM p_deviation WHERE  status= 'For QA Manager' and  plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["impactDeparments"] = json_decode($row["impactDeparments"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDeviationForConsentAndReview_For_QA_Head'){ 
            $output = Array();
            $sql = "SELECT * FROM p_deviation WHERE  status= 'For QA Head' and  plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["impactDeparments"] = json_decode($row["impactDeparments"]);
                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
    }
       else if($_GET['type'] == 'getDEviationLog'){ 
            $output = Array();
            $sql = "SELECT * FROM p_deviation WHERE   plant_id= '".$_GET["plant_id"]."'";
            
 
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["impactDeparments"] = json_decode($row["impactDeparments"]);
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
FROM p_deviation pd
JOIN JSON_TABLE(
    pd.impactDeparments, '$[*]' 
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
        $row["impactDeparments"] = json_decode($row["impactDeparments"]);
        $output[] = $row;
    }
}

echo json_encode($output);
}
else if ($_GET["type"] == "Update_impact_departments") {
                   
                   
                     $sql = "update  p_deviation set impactDeparments= '".json_encode($input["impactDeparments"])."'    where id ='".$input["id"]."'";
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
$sql = "SELECT * FROM p_deviation WHERE id = '".$id."'";
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
    $impactingDepartments = json_decode($row['impactDeparments'], true);
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