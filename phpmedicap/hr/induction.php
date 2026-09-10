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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "saveInduction") {
        $sql = "INSERT INTO induction (entry_by, entry_date,activities,department) VALUES ('".$_GET["emp_id"]."','".$_GET["department"]."', '$entry_date','".json_encode($input["activities"])."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } else if ($_GET["type"] == "getInductionHRLog") {
        $output = Array();
        $sql = "SELECT i.*,e.firstname,e.middlename,e.lastname,e.joining_date FROM induction i LEFT JOIN employee e ON 
        e.emp_id=i.emp_id AND i.status='pending'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
    } else if ($_GET["type"] == "getEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='".$_GET["department_name"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingInductions") {
        $output = Array();
        $sql = "SELECT * FROM induction WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equipments"] = json_decode($row["equipments"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getInductionLog") {
        $output = Array();
       $sql = "SELECT i.*, e.firstname FROM induction i LEFT JOIN employee e ON i.entry_by=e.emp_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["activities"] = json_decode($row["activities"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "updateInduction") {
        $sql = "UPDATE induction SET status='".$_GET["status"]."', approve_by='".$_GET["approve_by"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET['type'] == 'downloadInductionTrainingLog') {
        $_GET['filename'] = 'Induction Training';$_GET['sop']='';$_GET['annexure']='';$_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.="";
        $html.='
        <h2 style="text-align:center">Induction Training</h2>
        <table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:20%;">Sr.</td>
                        <td style="width:20%;">Emp ID</td>
                        <td style="width:20%;">Name</td>
                        <td style="width:20%;">Training Date</td>
                        <td style="width:20%;">Status</td>
                    </tr>';
                    $sql = "SELECT i.*, e.firstname FROM induction i LEFT JOIN employee e ON i.entry_by=e.emp_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                    $html.='<tr>
                        <td style="width:20%;">'.$i.'.</td>
                        <td style="width:20%;">'.$row['entry_by'].'</td>
                        <td style="width:20%;">'.$row['firstname'].'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width:20%;">'.$row['status'].'</td>
                    </tr>';
                    $i++;
            }
        }
                $html.='
                </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Induction Training.pdf', 'I');
    }else if ($_GET['type'] == 'downloadInduction') {
        $_GET['filename'] = 'Induction Training';$_GET['sop']='';$_GET['annexure']='';$_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.="";
        $html.='
        <h2 style="text-align:center">Induction Training</h2>
        <table cellpadding="5" border="1">
        <tr style="background-color:#DDDAD9">
            <td style="width:15%;"><b>Employee Id</b></td>
            <td style="width:20%;"><b>Employee Name</b></td>
            <td style="width:20%;"><b>Joined In Department</b></td>
            <td style="width:20%;"><b>Induction Training Start Date</b></td>
            <td style="width:25%;"><b>Induction Training End Date</b></td>
        </tr>';
        
         $sql = "SELECT i.*,e.firstname,e.middlename,e.lastname,e.joining_date FROM induction i LEFT JOIN employee e ON 
        e.emp_id=i.emp_id AND i.status='pending'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='  <tr>
            <td style="width:15%;">'.$row['emp_id'].'</td>
            <td style="width:20%;">'.$row['firstname'].' '.$row['middlename'].'
            '.$row['lastname'].'</td>
            <td style="width:20%;">'.$row[''.$row['firstname'].' '.$row['middlename'].'
            '.$row['lastname'].''].' </td>
            <td style="width:20%;">'.$row['start_time'].'</td>
            <td style="width:25%;">'.$row['end_time'].'</td>
        </tr>';
       
            }
        }
          $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Induction Training.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>