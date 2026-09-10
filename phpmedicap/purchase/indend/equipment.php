<?php
    require '../../db.php';
    require '../../token.php';
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveIndend") {
        $flag = 0;
        for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            $sql = "INSERT INTO indend_equipment (user_no, equipment_type, equipment_name, capacity, req_qty, unit, requirement, purpose, expected_vendor, entry_by, entry_date, department) VALUES 
            ('".$_GET["user_no"]."','".$temp["equipment_type"]."','".$temp["equipment_name"]."','".$temp["capacity"]."','".$temp["qty"]."','".$temp["unit"]."','".$temp["requirement"]."','".$temp["purpose"]."','".$temp["vendor"]."','".$_GET["emp_id"]."','".$entry_date."','".$_GET["department"]."')";
            if($conn->query($sql)) {
                $flag = 0;
            } else {
                $flag = 1;
                break;
            }
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingIndends") {
        $output = array();
        $sql = "SELECT * FROM indend_equipment WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIndend") {
        $sql = "UPDATE indend_equipment SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIndendsLog") {
        $output = array();
        $sql = "SELECT * FROM indend_equipment WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET['type'] == 'downloadIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indend Of Equipment'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";
        $html.='
        <h2 style="text-align:center">Indend Of Equipment</h2>
        <table cellpadding="5" border="1">
                    <thead>
                        <tr>
                            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                            <td style="width:15%; text-align:centre;"><b>Indend No</b></td>
                            <td style="width:15%; text-align:centre;"><b>Vendor No</b></td>
                            <td style="width:15%; text-align:centre;"><b>Equipment Type</b></td>
                            <td style="width:15%; text-align:centre;"><b>Equipment Name</b></td>
                            <td style="width:15%; text-align:centre;"><b>Req.Qty</b></td>
                            <td style="width:15%; text-align:centre;"><b>Requirement</b></td>
                        </tr>
                    </thead>
                    <tbody>';
                    $i=1;
                    $sql = "SELECT * FROM indend_equipment WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr>
                            <td style="width:10%;">'.$i.'.</td>
                            <td style="width:15%;">'.$row['indend_no'].'</td>
                            <td style="width:15%;">'.$row['vendor_no'].'</td>
                            <td style="width:15%;">'.$row['equipment_type'].'</td>
                            <td style="width:15%;">'.$row['equipment_name'].'</td>
                            <td style="width:15%;">'.$row['qty'].'</td>
                            <td style="width:15%;">'.$row['requirement'].'</td>
                        </tr>
                    </tbody>';
                    $i++;
                }
            }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IndendsLog.pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>