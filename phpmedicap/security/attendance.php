<?php
//   ini_set('display_errors', 1);
//     error_reporting(E_ALL); 
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d", $timestamp);
    $entry_time = date("H:i", $timestamp);
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
    
    if ($_GET["type"] == "getEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee where status='active'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingAttendance") {
        $output = Array();
        $sql = "SELECT a.id,a.emp_id,a.intime,a.outtime,a.inentry_by,a.outentry_by, a.status, e.firstname,e.middlename,e.lastname FROM attendence a LEFT JOIN employee e ON a.emp_id=e.emp_id WHERE a.status='pending' OR (a.status IS NULL AND (a.outtime IS NULL OR a.outtime='')) ORDER BY a.id DESC";
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "addEmployeeOuttime") {
        $id = isset($_GET["id"]) ? $conn->real_escape_string($_GET["id"]) : '';
        if ($id === '') {
            echo "{\"status\":\"failed\",\"message\":\"Attendance id is required\"}";
        } else {
            // Direct exit — do not require shift allocation
            $sqlFind = "SELECT id, intime, status FROM attendence WHERE id='".$id."' LIMIT 1";
            $resultFind = $conn->query($sqlFind);
            if (!$resultFind || $resultFind->num_rows === 0) {
                echo "{\"status\":\"failed\",\"message\":\"Attendance record not found\"}";
            } else {
                $row = $resultFind->fetch_assoc();
                $workHrs = '';
                if (!empty($row["intime"])) {
                    try {
                        $time1 = new DateTime($row["intime"]);
                        $time2 = new DateTime($entry_time);
                        $interval = $time1->diff($time2);
                        $workHrs = $interval->format('%H:%I');
                    } catch (Exception $e) {
                        $workHrs = '';
                    }
                }
                $sql = "UPDATE attendence SET
                    work_hrs='".$conn->real_escape_string($workHrs)."',
                    outdate='".$entry_date."',
                    outtime='".$entry_time."',
                    outentry_by='".$conn->real_escape_string($_GET["emp_id"])."',
                    status='exit'
                    WHERE id='".$id."'";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
                }
            }
        }
    } else if($_GET["type"]=="addEmployeeIntime") {
        // Direct form save — NO shift required, never return no_shift
        if (empty($input) || empty($input["emp_id"])) {
            echo "{\"status\":\"failed\",\"message\":\"Employee is required\"}";
        } else {
            $emp_id = $conn->real_escape_string($input["emp_id"]);

            $sql = "SELECT id FROM attendence WHERE emp_id='".$emp_id."' AND status='pending' AND Date(indate)='".$entry_date."' LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                echo "{\"status\":\"filled\"}";
            } else {
                $shift = '';
                $early_time = '';
                $late_time = '';
                $latemark = 0;

                // Shift is optional only — never block save
                $sqlShift = "SELECT a.Shift_Id AS a_Shift_Id, b.start_time
                    FROM shift_allocation a
                    LEFT JOIN shift_schedule b ON a.Shift_Id = b.id
                    WHERE '".$entry_date."' BETWEEN a.start_date AND a.end_date
                      AND a.Empolyee_Id = '".$emp_id."'
                    LIMIT 1";
                $resShift = $conn->query($sqlShift);
                if ($resShift && $resShift->num_rows > 0) {
                    $row = $resShift->fetch_assoc();
                    $shift = isset($row['a_Shift_Id']) ? $row['a_Shift_Id'] : '';
                    if (!empty($row['start_time'])) {
                        $start_time = new DateTime($row["start_time"]);
                        $intime = new DateTime($entry_time);
                        $time_difference = $start_time->diff($intime);
                        $minutes_difference = ((int)$time_difference->format('%h') * 60) + (int)$time_difference->format('%i');
                        if ($intime < $start_time) {
                            $early_time = $time_difference->format('%H : %i : %s ');
                        } elseif ($intime > $start_time) {
                            $late_time = $time_difference->format('%H : %i : %s ');
                            $latemark = ($minutes_difference > 15) ? 1 : 0;
                        }
                    }
                }

                $shiftValue = ($shift !== '') ? "'".$conn->real_escape_string($shift)."'" : "NULL";
                $sql = "INSERT INTO attendence (emp_id, indate, intime, inentry_by, shift, early_time, late_time, late_mark, status)
                    VALUES ('".$emp_id."', '".$entry_date."', '".$entry_time."', '".$conn->real_escape_string($_GET["emp_id"])."', ".$shiftValue.",
                    '".$conn->real_escape_string($early_time)."', '".$conn->real_escape_string($late_time)."', '".$latemark."', 'pending')";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\",\"message\":\"".$conn->real_escape_string($conn->error)."\"}";
                }
            }
        }
    } else if ($_GET["type"] == "getAttendanceLog") {
        $output = array();
        $sql = "SELECT a.id,a.emp_id,a.indate,a.intime,a.outtime,a.inentry_by,a.outentry_by,a.status,
                e.firstname,e.middlename,e.lastname,e.department
                FROM attendence a
                LEFT JOIN employee e ON a.emp_id=e.emp_id
                ORDER BY a.id DESC";

        $result = $conn->query($sql);
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }

        echo json_encode($output);

    } else if ($_GET["type"] == "getAllAttendanceLog") {
        $output = array();
        $sql = "SELECT a.id,a.emp_id,a.intime,a.outtime,a.inentry_by,a.outentry_by, a.status, e.firstname,e.middlename,e.lastname, e.department FROM attendence a LEFT JOIN employee e ON a.emp_id=e.emp_id ";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadAttendance") {
        include '../tcpdf/tcpdf.php';

        $from_date = !empty($_GET['from_date']) ? $conn->real_escape_string($_GET['from_date']) : '';
        $to_date = !empty($_GET['to_date']) ? $conn->real_escape_string($_GET['to_date']) : '';
        $department_name = isset($_GET['department_name']) ? $conn->real_escape_string($_GET['department_name']) : '';

        $_GET['filename'] = 'Employee Entry / Exit Log';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        $_GET['pdfy'] = 26;
        $_GET['pdftop'] = 26;
        $_GET['pdfpagebr'] = 12;
        $_GET['pdffonts'] = 8;
        include("../pdfimp2.php");
        $pdf->SetAutoPageBreak(TRUE, 12);

        $filterText = 'All Records';
        if ($from_date !== '' && $to_date !== '') {
            $filterText = 'From '.date('d/m/Y', strtotime($from_date)).' To '.date('d/m/Y', strtotime($to_date));
        }
        if ($department_name !== '') {
            $filterText .= ($filterText === 'All Records' ? '' : ' | ').'Department: '.$department_name;
        }

        $colWidths = array('5', '12', '12', '12', '22', '12', '12', '13');
        $colspan = count($colWidths);

        $html .= '
        <table width="100%" cellpadding="2" cellspacing="0" border="1" style="font-size:8px;">
            <tr>
                <td colspan="'.$colspan.'" style="text-align:center;font-weight:bold;font-size:11px;border:none;">Employee Entry / Exit Log</td>
            </tr>
            <tr>
                <td colspan="'.$colspan.'" style="text-align:right;font-size:8px;border:none;">Generated: '.date('d/m/Y H:i').' | '.$filterText.'</td>
            </tr>
            <tr style="text-align:center;background-color:#0e4370;color:#ffffff;font-weight:bold;">
                <td width="'.$colWidths[0].'%">Sr.</td>
                <td width="'.$colWidths[1].'%">Department</td>
                <td width="'.$colWidths[2].'%">Date</td>
                <td width="'.$colWidths[3].'%">Employee Id</td>
                <td width="'.$colWidths[4].'%">Employee Name</td>
                <td width="'.$colWidths[5].'%">In Time</td>
                <td width="'.$colWidths[6].'%">Out Time</td>
                <td width="'.$colWidths[7].'%">Status</td>
            </tr>';

        $sql = "SELECT a.id,a.emp_id,a.indate,a.intime,a.outtime,a.status,
                e.firstname,e.middlename,e.lastname,e.department
                FROM attendence a
                LEFT JOIN employee e ON a.emp_id=e.emp_id
                WHERE 1=1";
        if ($department_name !== '') {
            $sql .= " AND e.department LIKE '%$department_name%'";
        }
        $sql .= " ORDER BY a.id DESC";

        $result = $conn->query($sql);
        $j = 1;
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $empName = trim(($row['firstname'] ?? '').' '.($row['middlename'] ?? '').' '.($row['lastname'] ?? ''));
                $indate = !empty($row['indate']) ? date('d/m/Y', strtotime($row['indate'])) : '-';
                $intime = !empty($row['intime']) && trim($row['intime']) !== '' ? $row['intime'] : '-';
                $outtime = !empty($row['outtime']) && trim($row['outtime']) !== '' ? $row['outtime'] : '-';
                $status = !empty($row['status']) ? $row['status'] : '-';

                $html .= '<tr nobr="true">
                    <td width="'.$colWidths[0].'%" align="center">'.$j++.'</td>
                    <td width="'.$colWidths[1].'%">'.htmlspecialchars($row['department'] ?? '-').'</td>
                    <td width="'.$colWidths[2].'%">'.$indate.'</td>
                    <td width="'.$colWidths[3].'%">'.htmlspecialchars($row['emp_id'] ?? '-').'</td>
                    <td width="'.$colWidths[4].'%">'.htmlspecialchars($empName !== '' ? $empName : '-').'</td>
                    <td width="'.$colWidths[5].'%" align="center">'.$intime.'</td>
                    <td width="'.$colWidths[6].'%" align="center">'.$outtime.'</td>
                    <td width="'.$colWidths[7].'%" align="center">'.htmlspecialchars($status).'</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="'.$colspan.'" align="center">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attendance.pdf', 'I');
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>