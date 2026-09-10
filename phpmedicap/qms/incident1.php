<?php
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

    if ($_GET["type"] == "saveIncident") {
		$sql = "INSERT INTO incident (user_no, department, related_to, category, type, classification, description, immediate_action, initiate_by, initiate_date) VALUES ('".$_GET["user_no"]."', '".$_GET["department"]."', '".$input["related_to"]."', '".$input["category"]."', '".$input["type"]."', '".$input["classification"]."', '".$input["description"]."', '".$input["immediate_action"]."', '".$_GET["emp_id"]."', '$entry_date')";
		if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}

    else if ($_GET["type"] == "downloadIncidentReport") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Incident Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html.= "";
        
         $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

        $html.='<h3 style="text-align:center;">Incident Reporting Form</h3>
                <table cellpadding="5">
                    <tr>
                        <td style="width: 20%;"><b>Date</b>&nbsp;'.$row['initiate_date'].'</td>
                        <td style="width: 40%;"><b>Initiating Department:</b>&nbsp;'.$row['department'].'</td>
                        <td style="width: 40%;"><b>Initiated By:</b>&nbsp;'.$row['initiate_by'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 20%;"><b>Incident Number:</b></td>
                        <td style="width: 80%;">'.$row['incident_no'].'</td>
                    </tr>
                     <tr >
                        <td style="width: 100%;"><b>Description of Incident:</b>&nbsp;'.$row['description'].'</td>
                    </tr>
                     <tr >
                        <td style="width: 100%;"><b>Immediate Action:</b>&nbsp;'.$row['immediate_action'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Initiator:</b><br>
                                                <b>Sign/Date:</b><br></td>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Initiating Dept.Head:</b><br>
                                                <b>Sign/Date:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Classification Of Incident:</b>Quality Impacting/Non-Quality Impacting(Tick as applicable)</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Investigation:</b><br>&nbsp;'.$row['investigation'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Impact Assessment:</b>&nbsp;'.$row['impact_assessment'].'</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"><b>Corrective Actions:</b>&nbsp;'.$row['corrective_action'].'
                        <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Preventive Actions:</b><br>&nbsp;'.$row['preventive_action'].'
                                                 <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;">&nbsp;&nbsp;<b>Notification to respective Customers(in Case of Quality Impacting Incident): YES/NO</b><br>
                                                <b>Attach Customer Approval as Annexure:</b>_____________________________</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Initiating Department Head Comments:</b><br>
                                                 <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>QA Comments:</b>&nbsp;'.$row['qa_decision'].'
                                                 <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Incident Extension:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Extension Required In:</b><br></td>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Justification In Extension:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Revised Target Completion Date:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Initiating Dept.Head:</b><br>
                                                <b>Sign/Date:</b><br></td>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Head QA/Designe</b><br>
                                                <b>Sign/Date:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Evalution by Corporate Quality Assurance:</b>
                                                 <div></div>
                                                 <span style=" width:100%; text-align:right;"><b>Sign/Date:</b></span><br></td>
                    </tr>
                     <tr>
                        <td style="width: 100%;"><b>Evalution by Head Quality/Designee:</b>
                                                 <div></div>
                                                 <span style="width:100%; text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">&nbsp;&nbsp;<b>Incident Closed By</b>&nbsp;'.$row['close_by'].'<br>
                                                <b>Sign/Date:</b>______________________&nbsp;'.$row['close_date'].' <br></td>                  
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>List of Attachments</b><br>
                                                 <table border="1" cellpadding="5">
                                                 <tr>
                                                    <td style="width:20%;"><b>Attachment No</b></td>
                                                    <td style="width:80%;"><b>Particular</b></td>
                                                 </tr>
                                                 <tr>
                                                    <td style="width:20%;"></td>
                                                    <td style="width:80%;"></td>
                                                 </tr>
                                                 <tr>
                                                    <td style="width:20%;"></td>
                                                    <td style="width:80%;"></td>
                                                 </tr>
                                                
                                                 </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%"><b>Attach Additional Sheets if required</b></td>
                    </tr>
            ';
            }
        }
		
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IncidentReport.pdf', 'I');
    }

}

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
?>