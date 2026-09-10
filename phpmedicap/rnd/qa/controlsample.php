<?php 
require '../../db.php';
require '../../token.php';
$output = Array();
$token = $_GET["token"];
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
    
    if ($_GET["type"] == "saveRack") {
        $sql = "INSERT INTO rnd_controlsample_rack (rack_type, rack_no, subrack, entry_by, entry_date) VALUES ('".$input["material_type"]."', '".$input["rack_no"]."', '".json_encode($input["rackList"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingRacks") {
        $output = Array();
        $sql = "SELECT * FROM rnd_controlsample_rack WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subrack"] = json_decode($row["subrack"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateRack") {
        $sql = "UPDATE rnd_controlsample_rack SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRacksLog") {
        $output = Array();
        $sql = "SELECT * FROM rnd_controlsample_rack";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subrack"] = json_decode($row["subrack"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveFinishControlSample") {
        $sql = "INSERT INTO rnd_control_sample (material_type, material_code, batch_no, sampling_date, mfg_date, exp_date, sample_quantity, unit, sample_by, pack_no, rack_no, room_temp, humidity, entry_by, entry_date) VALUES ('Finish Product', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["sampling_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sample_quantity"]."', '".$input["unit"]."', '".$input["sample_by"]."', '".$input["pack_no"]."', '".$input["rack_no"]."', '".$input["room_temp"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveRawControlSample") {
        $sql = "INSERT INTO rnd_control_sample (material_type, material_code, batch_no, ar_no, analysis_date, release_date, sampling_date, mfg_date, exp_date, sample_quantity, unit, sample_by, pack_no, rack_no, room_temp, humidity, entry_by, entry_date) VALUES ('Raw Material', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["ar_no"]."', '".$input["analysis_date"]."', '".$input["release_date"]."', '".$input["sampling_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sample_quantity"]."', '".$input["unit"]."', '".$input["sample_by"]."', '".$input["pack_no"]."', '".$input["rack_no"]."', '".$input["room_temp"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "savePackingControlSample") {
        $sql = "INSERT INTO rnd_control_sample (material_type, material_code, batch_no, ar_no, analysis_date, release_date, sampling_date, mfg_date, exp_date, sample_quantity, unit, sample_by, pack_no, rack_no, room_temp, humidity, entry_by, entry_date) VALUES ('Packing Material', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["ar_no"]."', '".$input["analysis_date"]."', '".$input["release_date"]."', '".$input["sampling_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sample_quantity"]."', '".$input["unit"]."', '".$input["sample_by"]."', '".$input["pack_no"]."', '".$input["rack_no"]."', '".$input["room_temp"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRawControlSamples") {
        $output = Array();
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM rnd_control_sample c 
        LEFT JOIN material m ON c.material_code=m.material_code WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' ";
        //AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPackingControlSamples") {
        $output = Array();
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM rnd_control_sample c LEFT 
        JOIN material m ON c.material_code=m.material_code WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getFinishControlSamples") {
        $output = Array();
        $sql = "SELECT c.*, p.dosage_form, p.product_name, p.grade FROM rnd_control_sample c LEFT JOIN product p ON c.material_code=p.product_code WHERE c.user_no='".$_GET["user_no"]."' AND c.material_type='Finish Product' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadRawControlSamples") {
      
   $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.=' 
        <table cellpadding="5" border="0.1">
          <tr>
              <td style="width:5%;text-align:center"><b>Sr.</b></td>
                 <td style="width:10%;text-align:center"><b>Material Name</b></td>
                 <td style="width:10%;text-align:center"><b>Material type</b></td>
                  <td style="width:5%;text-align:center"><b>Material Code</b></td>
                  <td style="width:10%;text-align:center"><b>Batch No</b></td>
                  <td style="width:5%;text-align:center"><b>Grade</b></td>
                  <td style="width:5%;text-align:center"><b>A.R. No</b></td>
                 <td style="width:10%;text-align:center"><b>Mfg Date</b></td>
                 <td style="width:10%;text-align:center"><b>Exp Date</b></td>
                  <td style="width:10%;text-align:center"><b>Stored Qty</b></td>
                   <td style="width:10%;text-align:center"><b>Available Qty</b></td>
                    <td style="width:10%;text-align:center"><b>Withdrawal Qty</b></td>
                     </tr>';
          $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM rnd_control_sample c 
        LEFT JOIN material m ON c.material_code=m.material_code WHERE user_no='".$_GET["user_no"]."' 
        AND c.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' ";
        //AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                 <tr>
              <td style="width:5%;text-align:center"><b>'.$i.'</b></td>
                 <td style="width:10%;text-align:center">'.$row[""].'</td>
                 <td style="width:10%;text-align:center">'.$row[""].'</td>
                  <td style="width:5%;text-align:center">'.$row[""].'</td>
                  <td style="width:10%;text-align:center">'.$row[""].'</td>
                  <td style="width:5%;text-align:center">'.$row[""].'</td>
                  <td style="width:5%;text-align:center">'.$row[""].'</td>
                 <td style="width:10%;text-align:center">'.$row[""].'</td>
                 <td style="width:10%;text-align:center">'.$row[""].'</td>
                  <td style="width:10%;text-align:center">'.$row[""].'</td>
                   <td style="width:10%;text-align:center">'.$row[""].'</td>
                    <td style="width:10%;text-align:center">'.$row[""].'</td>
                     </tr>';
                    
                $i++;
            }
        }
        $html.="</table>";
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw Material Control Sample Register.pdf', 'I');
    
        
    } else if ($_GET["type"] == "downloadPackingControlSamples") {
      
       $_GET['filename'] = 'Environment'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM rnd_control_sample c LEFT JOIN material m ON c.material_code=m.material_code WHERE user_no='".$_GET["user_no"]."' AND c.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                    <tr>
                        <td style="border:solid 1px BCBBBA;">'.$i.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["sampling_date"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["product_name"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["batch_no"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["grade"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["mfg_date"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["exp_date"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["sample_quantity"].' '.$row["unit"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["sample_by"].'</td>
                    </tr>
                    ';
                $i++;
            }
        }
        $html.="</table>";
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Control Sample Register.pdf', 'I');
    } else if ($_GET["type"] == "downloadFinishControlSamples") {
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Finish Product Control Sample Register'; $_GET['pdftype'] ='landscape'; include("../pdfimp.php");
        
        $html = '<table cellpadding="5">
            <tr style="text-align:center; font-weight:bold;">
                <td style="border:solid 1px BCBBBA; width: 4%;">Sr.</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Date</td>
                <td style="border:solid 1px BCBBBA; width: 26%;">Product Name</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Batch No.</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Grade</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Mfg. Date</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Exp. Date</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Qty</td>
                <td style="border:solid 1px BCBBBA; width: 10%;">Sampling By</td>
            </tr>';
        $output = Array();
        $sql = "SELECT c.*, p.dosage_form, p.product_name, p.grade FROM rnd_control_sample c LEFT JOIN product p ON c.material_code=p.product_code WHERE c.user_no='".$_GET["user_no"]."' AND c.material_type='Finish Product' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                    <tr>
                        <td style="border:solid 1px BCBBBA;">'.$i.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["sampling_date"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["product_name"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["batch_no"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["grade"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["mfg_date"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["exp_date"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["sample_quantity"].' '.$row["unit"].'</td>
                        <td style="border:solid 1px BCBBBA;">'.$row["sample_by"].'</td>
                    </tr>
                    ';
                $i++;
            }
        }
        $html.="</table>";
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Finish Product Control Sample Register.pdf', 'I');
    }

}

$conn->close();
?>