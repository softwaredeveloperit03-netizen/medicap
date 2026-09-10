<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);
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


if ($_GET["type"] == "jobseremployee") {
    $output = Array();
     $sql = "SELECT a.*, b.firstname, b.lastname, b.department, b.designation, (SELECT j.id FROM job_responsibilities j WHERE j.emp_id = a.emp_id) AS job_id FROM training_induction a LEFT JOIN employee b ON a.emp_id = b.emp_id WHERE a.status = 'Complete' AND (SELECT j.id FROM job_responsibilities j WHERE j.emp_id = a.emp_id) IS NOT NULL";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
 
                $output[] = $row;
          
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "getDataJOB") {
    $output = Array();
     $sql = "select * from job_responsibilities";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
       $row["Primary_responsibilities"] = json_decode($row["Primary_responsibilities"]); 
          $row["Secondary_responsibilities"] = json_decode($row["Secondary_responsibilities"]); 
             $row["QMS_responsibilities"] = json_decode($row["QMS_responsibilities"]); 
                $output[] = $row;
          
        }
    }
    echo json_encode($output);
 } 
else if ($_GET["type"] == "savejob_responsibilities") {
    $emp_id = $conn->real_escape_string($input['emp_id'] ?? '');
    $emp_name = $conn->real_escape_string(
        isset($input['emp_name']) && $input['emp_name'] !== ''
            ? $input['emp_name']
            : trim(($input['firstname'] ?? '') . ' ' . ($input['lastname'] ?? ''))
    );
    $department = $conn->real_escape_string($input['department'] ?? '');
    $designation = $conn->real_escape_string($input['designation'] ?? '');
    $primary = $conn->real_escape_string(json_encode($input['Primary_responsibilities'] ?? []));
    $secondary = $conn->real_escape_string(json_encode($input['Secondary_responsibilities'] ?? []));
    $qms = $conn->real_escape_string(json_encode($input['QMS_responsibilities'] ?? []));
    $entry_by = $conn->real_escape_string($_GET['emp_id']);

    $check = $conn->query("SELECT id FROM job_responsibilities WHERE emp_id='".$emp_id."'");
    if ($check && $check->num_rows > 0) {
        $sql = "UPDATE job_responsibilities SET emp_name='".$emp_name."', department='".$department."', designation='".$designation."',
            Primary_responsibilities='".$primary."', Secondary_responsibilities='".$secondary."', QMS_responsibilities='".$qms."'
            WHERE emp_id='".$emp_id."'";
    } else {
        $sql = "INSERT INTO job_responsibilities (emp_id,emp_name,department,designation,Primary_responsibilities,Secondary_responsibilities,QMS_responsibilities,entry_by,entry_date)
            VALUES ('".$emp_id."','".$emp_name."','".$department."','".$designation."','".$primary."','".$secondary."','".$qms."','".$entry_by."','$entry_date')";
    }

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
 }
else if ($_GET["type"] == "downloadJobResponsibilityLog" || $_GET["type"] == "downloadJobResponsibility") {
    $jrEsc = function ($value) {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    };
    $jrFormatDate = function ($value) {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('d-m-Y', $ts) : '';
    };
    $jrDecodeList = function ($raw) {
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : array();
    };
    $jrRespRows = function ($list) use ($jrEsc) {
        $rows = '';
        $i = 1;
        foreach ($list as $item) {
            if (is_object($item)) {
                $item = (array) $item;
            }
            $text = '';
            if (is_array($item)) {
                $text = $item['responsibility'] ?? '';
            } else {
                $text = (string) $item;
            }
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            $rows .= '<tr nobr="true">
                <td width="10%" align="center" style="border:1px solid #333;">' . $i . '</td>
                <td width="90%" align="left" style="border:1px solid #333;">' . $jrEsc($text) . '</td>
            </tr>';
            $i++;
        }
        if ($i === 1) {
            $rows .= '<tr nobr="true"><td colspan="2" align="center" style="border:1px solid #333;">No records</td></tr>';
        }
        return $rows;
    };
    $jrRespSection = function ($title, $list) use ($jrRespRows) {
        return '<br /><span style="font-size:11px; font-weight:bold; color:#0b4f5c;">' . $title . '</span><br />
            <table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
            <thead>
            <tr style="background-color:#0b6b7a; color:#ffffff;">
                <th width="10%" align="center" style="border:1px solid #333;"><b>Sr.</b></th>
                <th width="90%" align="left" style="border:1px solid #333;"><b>Responsibility</b></th>
            </tr>
            </thead>
            <tbody>' . $jrRespRows($list) . '</tbody>
            </table>';
    };
    $jrEmployeeBlock = function ($row) use ($jrEsc, $jrFormatDate, $jrDecodeList, $jrRespSection) {
        $primary = $jrDecodeList($row['Primary_responsibilities'] ?? array());
        $secondary = $jrDecodeList($row['Secondary_responsibilities'] ?? array());
        $qms = $jrDecodeList($row['QMS_responsibilities'] ?? array());
        $entryDate = $jrFormatDate($row['entry_date'] ?? '');

        $block = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse; margin-bottom:10px;">
            <tr nobr="true">
                <td width="18%" style="background-color:#eef6f8; font-weight:bold; border:1px solid #333;">Employee Code</td>
                <td width="32%" style="border:1px solid #333;">' . $jrEsc($row['emp_id'] ?? '') . '</td>
                <td width="18%" style="background-color:#eef6f8; font-weight:bold; border:1px solid #333;">Employee Name</td>
                <td width="32%" style="border:1px solid #333;">' . $jrEsc($row['emp_name'] ?? '') . '</td>
            </tr>
            <tr nobr="true">
                <td width="18%" style="background-color:#eef6f8; font-weight:bold; border:1px solid #333;">Department</td>
                <td width="32%" style="border:1px solid #333;">' . $jrEsc($row['department'] ?? '') . '</td>
                <td width="18%" style="background-color:#eef6f8; font-weight:bold; border:1px solid #333;">Designation</td>
                <td width="32%" style="border:1px solid #333;">' . $jrEsc($row['designation'] ?? '') . '</td>
            </tr>
            <tr nobr="true">
                <td width="18%" style="background-color:#eef6f8; font-weight:bold; border:1px solid #333;">Entry Date</td>
                <td width="82%" colspan="3" style="border:1px solid #333;">' . $jrEsc($entryDate) . '</td>
            </tr>
        </table>';
        $block .= $jrRespSection('Primary Responsibilities', $primary);
        $block .= $jrRespSection('Secondary Responsibilities', $secondary);
        $block .= $jrRespSection('QMS Responsibilities', $qms);
        $block .= '<br /><hr /><br />';
        return $block;
    };

    $_GET['filename'] = ($_GET["type"] == "downloadJobResponsibilityLog")
        ? 'Job Responsibility Log'
        : 'Job Responsibility';
    $_GET['pdftype'] = 'onlyheader';
    $_GET['pdffont'] = 'helvetica';
    $_GET['pdffonts'] = 9;
    include('../pdfimp2.php');
    $html = '';

    if ($_GET["type"] == "downloadJobResponsibilityLog") {
        $html .= '<h2 style="text-align:center; margin-bottom:12px;">Job Responsibility Log</h2>';
        $sql = "SELECT * FROM job_responsibilities ORDER BY emp_name ASC, emp_id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= $jrEmployeeBlock($row);
            }
        } else {
            $html .= '<p style="text-align:center;">No job responsibility records found.</p>';
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Job_Responsibility_Log.pdf', 'I');
        exit;
    }

    $recordEmpId = isset($_GET['record_emp_id']) ? $conn->real_escape_string($_GET['record_emp_id']) : '';
    if ($recordEmpId === '') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Missing employee id.';
        exit;
    }

    $sql = "SELECT * FROM job_responsibilities WHERE emp_id='" . $recordEmpId . "' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $html .= '<h2 style="text-align:center; margin-bottom:12px;">Job Responsibility and Accountability</h2>';
        $html .= $jrEmployeeBlock($row);
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Job_Responsibility_' . $recordEmpId . '.pdf', 'I');
        exit;
    }

    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Job responsibility record not found.';
    exit;
}
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>