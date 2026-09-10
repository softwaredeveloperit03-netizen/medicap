<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveBactorial") {
        $sql = "INSERT INTO bactorial (equipment_code,disinfect_cleaning,clean_by ,entry_time, remark,check_by,entry_by,entry_date) VALUES ('".$input["equipment_code"]."','".$input["disinfect_cleaning"]."' ,'".$input["clean_by"]."', '$entry_time', '".$input["remark"]."'  , '' ,'".$_GET["emp_id"]."' ,  '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getPendingBactorial") {
        $output = array();
          $sql="SELECT b.*, e.firstname FROM bactorial b LEFT JOIN employee e ON b.clean_by=e.emp_id ";
        //$sql="SELECT b.*, e.firstname FROM bactorial b LEFT JOIN employee e ON b.clean_by=e.emp_id WHERE DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET['type'] == 'downloadPendingBactorial'){
        $_GET['formatno'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
 
        $html.='<h3 style="text-align:center;">CLEANING RECORD OF BACTERIOLOGICAL INCUBATOR</h3>
        <table cellpadding="3" border="1">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%"><b>Date</b></td>
                    <td style="width:15%"><b>Time</b></td>
                    <td style="width:15%"><b>Bacteriological Incubator Id</b></td>
                    <td style="width:15%"><b>Disinfectant Use for Cleaning</b></td>
                    <td style="width:15%"><b>Remarks</b></td>
                    <td style="width:15%"><b>Cleaned By</b></td>
                    <td style="width:15%"><b>Checked By</b></td>
                </tr>
            </thead>';
        $sql="SELECT b.*, e.firstname FROM bactorial b LEFT JOIN employee e ON b.clean_by=e.emp_id ";
        //$sql="SELECT b.*, e.firstname FROM bactorial b LEFT JOIN employee e ON b.clean_by=e.emp_id WHERE DATE(b.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tbody>
                    <tr>
                        <td style="width:10%">'.date("d/m/Y",strtotime($row['entry_date'])).'</td>
                        <td style="width:15%">'.date("h:i",($row['entry_time'])).'</td>
                        <td style="width:15%">'.$row['equipment_code'].'</td>
                        <td style="width:15%">'.$row['disinfect_cleaning'].'</td>
                        <td style="width:15%">'.$row['remark'].'</td>
                        <td style="width:15%">'.$row['firstname'].'('.$row['clean_by'].')</td>
                        <td style="width:15%">'.$row['entry_by'].'</td>
                    </tr>
                </tbody>';
                }
            }
            
        $html.='
        </table>';
                 
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PendingBactorial.pdf', 'I');
    }
    
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>