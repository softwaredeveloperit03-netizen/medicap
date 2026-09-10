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
    
    if ($_GET["type"] == "saveRefrigerator") {
         $sql = "INSERT INTO refrigerator_cleaning (start_time,end_time,disinfect,clean_by ,entry_time, remark,entry_by,entry_date,Refrigerator_name,Refrigerator_id) VALUES
        ('".$input["start_time"]."','".$input["end_time"]."','".$input["disinfect"]."','".$input["clean_by"]."', '$entry_time', '".$input["remark"]."' ,'".$_GET["emp_id"]."' ,'$entry_date', '".$input["Refrigerator_name"]."', '".$input["Refrigerator_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getPendingRefrigerator"){
        $output=Array();
        $sql="SELECT * FROM refrigerator_cleaning";
        //WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if ($_GET["type"] == "downloadPendingRefrigerator") {
        $_GET['filename'] = 'PendingRefrigerator'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">PendingRefrigerator</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 15%;">Date</td>
                        <td style="width: 15%;">Time</td>
                        <td style="width: 25%;">Disinfectant use for cleaning</td>
                        <td style="width: 15%;">Remarks</td>
                        <td style="width: 15%;">Cleaned By</td>
                        <td style="width: 15%;">Checked By</td>
                    </tr>
                </thead>';
        $sql="SELECT * FROM refrigerator_cleaning WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 15%;">'.$row['entry_time'].'</td>
                        <td style="width: 25%;">'.$row['disinfect'].'</td>
                        <td style="width: 15%;">'.$row['remark'].'</td>
                        <td style="width: 15%;">'.$row['clean_by'].'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                    </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PendingRefrigerator.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>