<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getPendingCalibrations") {
        $output = Array();
        // $sql = "SELECT * FROM equipment WHERE equipment_type='Balance' AND department='".$_GET["department"]."'";
        $sql = "SELECT * FROM equipment WHERE equipment_name like '%Balance%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               // $row["weights"] = json_decode($row["weights"]);
                
                //$calibration_frequency = json_decode($row["calibration_frequency"], true);
               // if (in_array('Daily', $calibration_frequency)) 
               //{
                    $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' 
                    AND DATE(entry_date)=CURDATE() AND status !='reject'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows == 0) {
                        $output[] = $row;
                    }
                } //else if (in_array('Monthly', $calibration_frequency)) {
                    //$sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."'
                    //AND MONTH(entry_date)=MONTH(CURDATE()) AND status !='reject'";
                    //$result1 = $conn->query($sql1);
                   //if ($result1->num_rows == 0) {
                       // $output[] = $row;
                    //}
                //}
           // }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveCalibration") {
        $deviation = "";
        $weights = $input["weights"];
        $sql = "INSERT INTO calibration (equipment_code, weights,deviation, entry_by, entry_date, check_by, status) VALUES ('".$input["equipment_code"]."', '".json_encode($input["weights"])."', '".$deviation."', '".$input["calibration_by"]."', '$entry_date', '".$_GET["emp_id"]."', 'approve')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getCalibrationLog") {
        $output = array();
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, e.equipment_name FROM calibration c LEFT JOIN equipment e ON c.equipment_code=e.equipment_code WHERE DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["weights"] = json_decode($row["weights"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_bal_dtl") {
        $output = array();
        $sql = "SELECT * FROM std_weight_dtl where department='".$_GET["department1"]."' and trolly='".$_GET["trolly_no"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
   
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "getCalibrationLog") {
        $output = array();
        $sql = "";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["weights"] = json_decode($row["weights"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "downloadCalibrationLog") {
        $_GET['filename'] = 'Calibration Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr.No</td>
                    <td style="width: 20%; ">Date</td>
                    <td style="width: 20%; ">Balance ID</td>
                    <td style="width: 20%; ">Balance Name</td>
                    <td style="width: 20%; ">Done BY</td>
                    <td style="width: 15%; ">Check By</td>
                </tr>
            </thead>';
        $output = array();
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, e.equipment_name FROM calibration c LEFT JOIN equipment e ON c.equipment_code=e.equipment_code WHERE DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i=1;
                $row["weights"] = json_decode($row["weights"]);
                $output[] = $row;
                $html.='<tr nobr="true">
                            <td style="width: 5%; ">'.$i.'.</td>
                            <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                            <td style="width: 20%;">'.$row['equipment_code'].'</td>
                            <td style="width: 20%; ">'.$row['equipment_name'].'</td>
                            <td style="width: 20%;">'.$row['entry_by'].'</td>
                            <td style="width: 15%; ">'.$row['check_by'].'</td>
                            
                        </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Calibration Log.pdf', 'I');
    }
    
}

$conn->close();
?>