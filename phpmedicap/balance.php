<?php
    require 'db.php';
    require 'token.php';
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

if ($_GET["type"] == "getBalances") {
    $output = Array();
    $sql = "SELECT e.*, c.weights, c.frequency FROM equipment e INNER JOIN calibration_methods c ON e.equipment_code=c.equipment_code WHERE e.equipment_type='Balance'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["weights"] = json_decode($row["weights"]);
            $output1 = Array();
            $sql1 = "SELECT * FROM calibration WHERE equipment_code ='".$row["equipment_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["weights"] = json_decode($row1["weights"]);
                    $output1[] = $row1;
                }
            }
            $row["calibrations"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingDailyBalances") {
    $output = Array();
    $sql = "SELECT e.*, c.weights FROM equipment e INNER JOIN calibration_methods c ON e.equipment_code=c.equipment_code WHERE e.equipment_type='Balance' AND e.department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["weights"] = json_decode($row["weights"]);
            $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND DATE(entry_date)=CURDATE() AND status !='reject'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows == 0) {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "saveDailyVerification") {
    $deviation = "";
    $weights = $input["weights"];
    $sql = "INSERT INTO calibration (equipment_code, weights,deviation, entry_by, entry_date,status) VALUES
    ('".$input["equipment_code"]."', '".json_encode($input["weights"])."', '".$deviation."', '".$_GET["emp_id"]."',
    '$entry_date','approve')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 


else if ($_GET["type"] == "getCheckingDailyBalances") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE equipment_type='Balance' AND department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND DATE(entry_date)=CURDATE() AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["deviation"] = $row1["deviation"];
                    $row["weights"] = json_decode($row1["weights"]);
                    $row["daily_id"] = $row1["id"];
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateDaily") {
    $sql = "UPDATE calibration SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingMonthlyBalances") {
    $output = Array();
    $sql = "SELECT e.*, c.weights FROM equipment e INNER JOIN calibration_methods c ON e.equipment_code=c.equipment_code WHERE e.equipment_type='Balance' AND c.frequency='Monthly' AND e.department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["weights"] = json_decode($row["weights"]);
            $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE()) AND status !='reject'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows == 0) {
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getCheckingMonthlyBalances") {
    $output = Array();
    $sql = "SELECT e.*, c.weights FROM equipment e INNER JOIN calibration_methods c ON e.equipment_code=c.equipment_code WHERE e.equipment_type='Balance' AND c.frequency='Monthly' AND e.department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE()) AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["deviation"] = $row1["deviation"];
                    $row["weights"] = json_decode($row1["weights"]);
                    $row["daily_id"] = $row1["id"];
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getCleaningReport") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE equipment_type='Balance' AND department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM equipment_cleaning WHERE equipment_code='".$row["equipment_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["equipment_name"] = $row["equipment_name"];
                    $output[] = $row1;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "downloadCleaningReport") {
    @ini_set('display_errors', '0');
    error_reporting(E_ERROR | E_PARSE);
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    $from = $conn->real_escape_string(trim((string)($_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days')))));
    $to = $conn->real_escape_string(trim((string)($_GET['to_date'] ?? date('Y-m-d'))));
    $equipmentCode = $conn->real_escape_string(trim((string)($_GET['equipment_code'] ?? '')));
    $equipmentName = $conn->real_escape_string(trim((string)($_GET['equipment_name'] ?? '')));
    $equipmentType = $conn->real_escape_string(trim((string)($_GET['equipment_type'] ?? '')));

    $_GET['filename'] = 'Equipment Cleaning Log';
    $_GET['pdftype'] = 'onlyheader';
    $_GET['pdfpage'] = 'L';
    include('pdfimp2.php');

    $html .= '
        <h2 style="text-align:center; font-size:12px;">Equipment Cleaning Log</h2>
        <table cellpadding="2" cellspacing="0" border="1" width="100%" style="border-collapse:collapse;">
            <tr style="text-align:center; background-color:#DDDAD9; font-weight:bold;">
                <td width="6%" style="font-size:7px;">Sr.</td>
                <td width="18%" style="font-size:7px;">Equipment Name</td>
                <td width="14%" style="font-size:7px;">Equipment Code</td>
                <td width="14%" style="font-size:7px;">Clean To</td>
                <td width="14%" style="font-size:7px;">Clean From</td>
                <td width="14%" style="font-size:7px;">Clean By</td>
                <td width="20%" style="font-size:7px;">Cleaning Type</td>
            </tr>';

    $sql = " SELECT e.*, e1.equipment_name, e1.equipment_type
             FROM equipment_cleaning e
             LEFT JOIN equipment e1 ON e.equipment_code = e1.equipment_code
             WHERE DATE(e.entry_date) BETWEEN '".$from."' AND '".$to."'";
    if ($equipmentCode !== '') {
        $sql .= " AND e.equipment_code LIKE '%".$equipmentCode."%'";
    }
    if ($equipmentName !== '') {
        $sql .= " AND e1.equipment_name LIKE '%".$equipmentName."%'";
    }
    if ($equipmentType !== '') {
        $sql .= " AND e1.equipment_type LIKE '%".$equipmentType."%'";
    }
    $sql .= " ORDER BY e.entry_date DESC, e.id DESC";

    $i = 1;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cleanBy = trim((string)($row['clean_by'] ?? ''));
            if ($cleanBy === '') {
                $cleanBy = trim((string)($row['entry_by'] ?? ''));
            }
            $html .= '<tr>
                <td style="font-size:7px;">'.$i.'.</td>
                <td style="font-size:7px;">'.htmlspecialchars((string)($row['equipment_name'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                <td style="font-size:7px;">'.htmlspecialchars((string)($row['equipment_code'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                <td style="font-size:7px;">'.htmlspecialchars((string)($row['clean_to'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                <td style="font-size:7px;">'.htmlspecialchars((string)($row['clean_from'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                <td style="font-size:7px;">'.htmlspecialchars($cleanBy, ENT_QUOTES, 'UTF-8').'</td>
                <td style="font-size:7px;">'.htmlspecialchars((string)($row['cleaning_type'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
            </tr>';
            $i++;
        }
    } else {
        $html .= '<tr><td colspan="7" style="font-size:7px; text-align:center;">No records found for selected period.</td></tr>';
    }
    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Equipment_Cleaning_Log.pdf', 'I');
    exit;
} else if ($_GET["type"] == "getQCBalances") {
    $output = Array();
    $sql = "SELECT e.*, c.frequency FROM equipment e INNER JOIN calibration_methods c ON e.equipment_code=c.equipment_code WHERE e.equipment_type='Balance' AND e.status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "";
            if ($row["frequency"] == "Daily") {
                $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND DATE(entry_date)=CURDATE()";
            } else {
                $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE())";
            }
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["deviation"] = $row1["deviation"];
                    $row["weights"] = json_decode($row1["weights"]);
                    $row["daily_id"] = $row1["id"];
                    if ($row1["status"] == "pending") {
                        $row["status"] = "inprocess";
                    } else {
                        $row["status"] = $row1["status"];
                    }
                }
            } else {
                $row["status"] = "pending";
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "new_getSamplingBalances") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE equipment_name LIKE '%Balance%' AND department='Store'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        //     // $sql1 = "";
        //     // if ($row["calibration_frequency"] == "Daily") {
        //     $calibration_frequency = json_decode($row["calibration_frequency_inhouse"], true);
        //     if (in_array('Daily', $calibration_frequency)){
        //   echo     $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND DATE(entry_date)=CURDATE()";
        //     } else {
        //   echo      $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE())";
        //     }
        
$calibration_frequency_inhouse = $row["calibration_frequency_inhouse"];
// var_dump($calibration_frequency_inhouse);  // Dump the initial value
$calibration_frequency_array = json_decode(trim($calibration_frequency_inhouse), true);
// var_dump($calibration_frequency_array);  // Dump the decoded array

$lowercase_array = array_map('strtolower', array_column($calibration_frequency_array, 'name'));
if (in_array(strtolower('Daily Calibration'), $lowercase_array)) {
    // If the condition is met, echo the SQL query
    $sql1 = "SELECT * FROM daily_caibration WHERE equipment_id='" . $row["equipment_code"] . "' AND DATE(date)=CURDATE()";
   
}
else{
                $sql1 = "SELECT * FROM monthly_calibration WHERE equipment_code='".$row["equipment_code"]."' AND MONTH(entry_date)=MONTH(CURDATE())";
}

            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["deviation"] = $row1["deviation"];
                    $row["weights"] = json_decode($row1["weights"]);
                    $row["daily_id"] = $row1["id"];
                    if ($row1["status"] == "pending") {
                        $row["status"] = "inprocess";
                    } else {
                        $row["status"] = $row1["status"];
                    }
                    $row["calibration_by"] = $row1["entry_by"];
                    $row["calibration_date"] = $row1["entry_date"];
                    $row["calibration_check_by"] = $row1["check_by"];
                    $row["calibration_check_date"] = $row1["check_date"];
                }
            } else {
                $row["weights"] = json_decode($row["weights"]);
                $row["status"] = "pending";
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getSamplingBalances") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE equipment_name LIKE '%Balance%' AND (department='Quality Control' or department='Store')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "";
        
                $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' order by id desc limit 1";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["deviation"] = $row1["deviation"];
                    $row["weights"] = json_decode($row1["weights"]);
                    $row["daily_id"] = $row1["id"];
                    
                    if ($row1["status"] == "pending") {
                        $row["status"] = "inprocess";
                    } else {
                        $row["status"] = $row1["status"];
                    }
                    $row["calibration_by"] = $row1["entry_by"];
                    $row["calibration_date"] = $row1["entry_date"];
                    $row["calibration_check_by"] = $row1["check_by"];
                    $row["calibration_check_date"] = $row1["check_date"];
                }
            } else {
                $row["weights"] = json_decode($row["weights"]);
                $row["status"] = "pending";
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getDeptBalances") {
    $output = Array();
        $sql = "SELECT * FROM equipment WHERE equipment_type LIKE 'Balance(Weighing)' AND status='Active' AND department='Store'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "download") {}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>