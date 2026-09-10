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
    
    if ($_GET["type"] == "saveAgenda") {
        $sql ="INSERT INTO mgmt_meeting (meeting_in, meeting_date, meeting_time,director, managers, agenda, entry_by, entry_date, representative) VALUES ('".$input["meeting_in"]."', '".$input["meeting_date"]."', '".$input["meeting_time"]."', '".json_encode($input["director"])."', '".json_encode($input["managers"])."', '".json_encode($input["agendas"])."', '".$_GET["emp_id"]."', '$entry_date','".$input["representative"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingMeetings") {
        $output = Array();
        $sql = "SELECT * FROM mgmt_meeting WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["managers"] = json_decode($row["managers"]);
                $row["agenda"] = json_decode($row["agenda"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startMeeting") {
        $output = Array();
        $sql = "UPDATE mgmt_meeting SET status='start', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getInprocessMeetings") {
        $output = Array();
        $sql = "SELECT * FROM mgmt_meeting WHERE status='start'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["managers"] = json_decode($row["managers"]);
                $row["agenda"] = json_decode($row["agenda"]);
                
                $managers = $row["managers"];
                for ($i = 0; $i < count($managers); $i++) {
                    $manager = $managers[$i];
                    $sql1 = "SELECT * FROM employee WHERE emp_id='".$manager->emp_id."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows> 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $manager->emp_name = $row1["emp_name"];
                        }
                    }
                    $managers[$i] = $manager;
                }
                $row["managers"] = $managers;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "completeMeeting") {
        $output = Array();
        $sql = "UPDATE mgmt_meeting SET status='complete', managers='".json_encode($input['managers'])."', remark='".$input["remark"]."', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "cancelMeeting") {
        $output = Array();
        $sql = "UPDATE mgmt_meeting SET status='cancel', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getMeetingslog") {
        $output = Array();
        $sql = "SELECT * FROM mgmt_meeting";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["managers"] = json_decode($row["managers"]);
                $row["agenda"] = json_decode($row["agenda"]);
                
                $managers = $row["managers"];
                for ($i = 0; $i < count($managers); $i++) {
                    $manager = $managers[$i];
                    $sql1 = "SELECT * FROM employee WHERE emp_id='".$manager->emp_id."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows> 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $manager->emp_name = $row1["emp_name"];
                        }
                    }
                    $managers[$i] = $manager;
                }
                $row["managers"] = $managers;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getManagers") {
        $output = Array();
    //  echo   $sql = "SELECT emp_id, firstname, department, designation FROM employee WHERE designation='manager' AND status='active'";
        $sql = "SELECT emp_id, firstname, department, designation FROM employee WHERE designation='manager' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "MeetingslogPDF") {
        $_GET['filename'] = 'MEeeting Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">MEeeting Log</h2>
        <table cellpadding="3" border="1" >
            <tr style="font-weight:bold;">
                <td>Meeting In</td>
                <td>Meeting Date</td>
                <td>Meeting Time</td>
                <td>Meeting Representative</td>
            </tr>';
        $output = Array();
        $sql = "SELECT * FROM mgmt_meeting";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tr>
                    <td>'.$row['meeting_in'].'</td>
                    <td>'.date('d-m-Y',strtotime($row['meeting_date'])).'</td>
                    <td>'.$row['meeting_time'].'</td>
                    <td>'.$row['representative'].'</td>
                </tr>';
            }
        }
        $html.='</table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    }else if ($_GET["type"] == "MeetingslogViewPDF") {
        $_GET['filename'] = 'Meeting Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $sql = "SELECT * FROM mgmt_meeting WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='
        <h2 style="text-align:center">Meeting Log</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%; text-align:centre;"><b>Meeting In:</b></td>
                <td style="width:25%; text-align:centre;">'.$row['meeting_in'].'</td>
                <td style="width:25%; text-align:centre;"><b>Meeting Date:</b></td>
                <td style="width:25%; text-align:centre;">'.date('d-m-Y',strtotime($row['meeting_date'])).'</td>
            </tr>
            <tr>
                <td style="width:25%; text-align:centre;"><b>Meeting Time:</b></td>
                <td style="width:25%; text-align:centre;">'.$row['meeting_time'].'</td>
                <td style="width:25%; text-align:centre;"><b>Meeting Representative:</b></td>
                <td style="width:25%; text-align:centre;">'.$row['representative'].'</td>
            </tr>';
        $html.='</table>
        <h3>Managers:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:25%; text-align:centre;"><b>Name Of Manager</b></td>
                <td style="width:25%; text-align:centre;"><b>Department</b></td>
                <td style="width:20%; text-align:centre;"><b>Designation</b></td>
                <td style="width:20%; text-align:centre;"><b>Attendance</b></td>
            </tr>';
            $ii=1;
            $row["managers"] = json_decode($row["managers"]);
            $managers = $row["managers"];
            for ($i = 0; $i < count($managers); $i++) {
                $manager = $managers[$i];
                $sql1 = "SELECT * FROM employee WHERE emp_id='".$manager->emp_id."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows> 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $manager->firstname = $row1["firstname"];
                    $managers[$i] = $manager;
                    $html.='<tr>
                        <td style="width:10%;">'.$ii.'</td>
                        <td style="width:25%;">'.$manager->firstname.'</td>
                        <td style="width:25%;">'.$manager->department.'</td>
                        <td style="width:20%;">'.$manager->designation.'</td>
                        <td style="width:20%;">'.$manager->attendance.'</td>
                    </tr>';
                    $ii++;
                    }
                }
            }
            $html.='</table>
            <h3>Meeting Agenda:</h3>
            <table cellpadding="5" border="1">
                <tr>
                    <td style="width:20%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width:40%; text-align:centre;"><b>Agenda</b></td>
                    <td style="width:40%; text-align:centre;"><b>Details</b></td>
                </tr>';
                $iii=1;
                $row["agenda"] = json_decode($row["agenda"]);
                $agenda = $row["agenda"];
                for ($i = 0; $i < count($agenda); $i++) {
                    $agendas = $agenda[$i];
                    $html.='
                    <tr>
                        <td style="width:20%;">'.$iii.'.</td>
                        <td style="width:40%;">'.$agendas->agenda.'</td>
                        <td style="width:40%;">'.$agendas->detail.'</td>
                    </tr>';
                    $iii++;
                }
            $html.='</table>
            <div></div>
            <table cellpadding="5" border="1">
                <tr>
                    <td style="width:50%; text-align:centre;"><b>Remark:</b></td>
                    <td style="width:50%;">'.$row['remark'].'</td>
                </tr>';
            $html.='</table>';
            }
                }
            $html.='</table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MeetingslogView.pdf', 'I');
    }
}

$conn->close();
?>