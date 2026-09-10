<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
    
     if ($_GET["type"] == "downloadChecklistMaster") {
        $_GET['filename'] = 'ChecklistMaster'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Primary Round</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%;"><b>Sr</b></td>
                <td style="width:10%;"><b>Candidate Name</b></td>
                <td style="width:10%;"><b>Qualification</b></td>
                <td style="width:10%;"><b>Mobile No.</b></td>
                <td style="width:10%;"><b>Email ID</b></td>
                <td style="width:10%;"><b>Location</b></td>
                <td style="width:10%;"><b>Department</b></td>
                <td style="width:10%;"><b>Designation</b></td>
                <td style="width:10%;"><b>Address</b></td>
                <td style="width:10%;"><b>Gender</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM candidate WHERE interview_allocated='Yes' and is_interview_completed='No' and primary_int_comp='no' order by 1 desc";
            // $sql = "SELECT * FROM candidate";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['candidate_name'].'</td>
                <td style="width:10%;">'.$row['qualification'].'</td>
                <td style="width:10%;">'.$row['mobile_no'].'</td>
                <td style="width:10%;">'.$row['email_id'].'</td>
                <td style="width:10%;">'.$row['location'].'</td>
                <td style="width:10%;">'.$row['department'].'</td>
                <td style="width:10%;">'.$row['designation'].'</td>
                <td style="width:10%;">'.$row['address'].'</td>
                <td style="width:10%;">'.$row['gender'].'</td>
              
            </tr>';
            $i++;
            }
            }
        $html.='</table>';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ChecklistMaster.pdf', 'I');
    }
  else  if ($_GET["type"] == "downloadChecklistMaster1") {
        $_GET['filename'] = 'ChecklistMaster'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Final Round</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%;"><b>Sr</b></td>
                <td style="width:10%;"><b>Candidate Name</b></td>
                <td style="width:10%;"><b>Qualification</b></td>
                <td style="width:10%;"><b>Mobile No.</b></td>
                <td style="width:10%;"><b>Email ID</b></td>
                <td style="width:10%;"><b>Location</b></td>
                <td style="width:10%;"><b>Department</b></td>
                <td style="width:10%;"><b>Designation</b></td>
                <td style="width:10%;"><b>Address</b></td>
                <td style="width:10%;"><b>Gender</b></td>
            </tr>';
            $i=1;
	$sql = "SELECT * FROM candidate WHERE interviewer_int_comp='yes' and final_int_comp='no' order by 1 desc";
            // $sql = "SELECT * FROM candidate";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['candidate_name'].'</td>
                <td style="width:10%;">'.$row['qualification'].'</td>
                <td style="width:10%;">'.$row['mobile_no'].'</td>
                <td style="width:10%;">'.$row['email_id'].'</td>
                <td style="width:10%;">'.$row['location'].'</td>
                <td style="width:10%;">'.$row['department'].'</td>
                <td style="width:10%;">'.$row['designation'].'</td>
                <td style="width:10%;">'.$row['address'].'</td>
                <td style="width:10%;">'.$row['gender'].'</td>
              
            </tr>';
            $i++;
            }
            }
        $html.='</table>';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ChecklistMaster.pdf', 'I');
    }

    
    
    
    
    
    
    } else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>