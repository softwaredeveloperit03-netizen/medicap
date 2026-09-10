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

    if ($_GET["type"] == "saveRequirement") {
        $sql = "INSERT INTO consoladated (user_no, work_order_no, po_no, po_date, po_type, product_code, unit, estimate_qty, available_qty, short_qty, batches, suggested, materials, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$_GET["work_order_no"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["po_type"]."', '".$input["product_code"]."','".$input["unit"]."', '".$input["estimate_qty"]."', '".$input["available_qty"]."', '".$input["short_qty"]."', '".json_encode($input["batches"])."', '".json_encode($input["suggested"])."', '".json_encode($input["materials"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingRequirements") {
        $output = array();
        $sql = "SELECT c.*, p.product_name, p.grade, p.dosage_form FROM consoladated c LEFT JOIN product p ON c.product_code=p.product_code WHERE c.user_no='".$_GET["user_no"]."' AND c.status='pending' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["suggested"] = json_decode($row["suggested"]);
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateRequirement") {
        $sql = "UPDATE consoladated SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRequirementsLog") {
        $output = array();
        $sql = "SELECT c.*, p.product_name, p.grade, p.dosage_form FROM consoladated c LEFT JOIN product p ON c.product_code=p.product_code WHERE c.user_no='".$_GET["user_no"]."' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["suggested"] = json_decode($row["suggested"]);
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadRequirementsLog") {
        $_GET['filename'] = 'Consolidated Requirement'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Consolidated Requirement</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Workorder No</b></td>
            <td style="width:10%; text-align:centre;"><b>Client Code</b></td>
            <td style="width:10%; text-align:centre;"><b>PO NO</b></td>
            <td style="width:10%; text-align:centre;"><b>PO date</b></td>
            <td style="width:10%; text-align:centre;"><b>PO Type</b></td>
            <td style="width:10%; text-align:centre;"><b>Product Code</b></td>
            <td style="width:10%; text-align:centre;"><b>Product Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Grade</b></td>
            <td style="width:10%; text-align:centre;"><b>Order Qty</b></td>
        </tr>';
        $sql = "SELECT c.*, p.product_name, p.grade, p.dosage_form FROM consoladated c LEFT JOIN product p ON c.product_code=p.product_code WHERE c.user_no='".$_GET["user_no"]."' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["suggested"] = json_decode($row["suggested"]);
                $row["materials"] = json_decode($row["materials"]);
        $html.='<tr>
            <td style="width:10%;">'.$i.'</td>
            <td style="width:10%;">'.$row['work_order_no'].'</td>
            <td style="width:10%;">'.$row['client_code'].'</td>
            <td style="width:10%;">'.$row['po_no'].'</td>
            <td style="width:10%;">'.date('d-m-Y h:i:sa',strtotime($row['po_date'])).'</td>
            <td style="width:10%;">'.$row['po_type'].'</td>
            <td style="width:10%;">'.$row['product_code'].'</td>
            <td style="width:10%;">'.$row['product_name'].'</td>
            <td style="width:10%;">'.$row['grade'].'</td>
            <td style="width:10%;">'.$row['estimate_qty'].''.$row['unit'].'</td>
        </tr>';
        $i++;
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Consolidated Requirement.pdf', 'I');
    }

}

$conn->close();
?>