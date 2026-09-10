<?php
    require '../db.php';
    require '../token.php';
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
    
    if ($_GET["type"] == "getAwaitingPacking") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM packing b LEFT JOIN product p 
        ON b.product_code=p.product_code WHERE b.status='PENDING'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "receiveProduct") {
        $sql = "UPDATE packing SET status='RECEIVED', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success", "msg"=>"Semi Finished Goods Received Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getReceivingLog") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM packing b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='RECEIVED' AND b.receive_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getReceivedMaterials") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM packing b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='RECEIVED'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadReceivingLog") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Received Semi-Finished Goods Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.= "";
        
        $html.='
        <h2 style="text-align:center">Received Semi-Finished Goods Report</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width: 34%;">PRODUCT NAME: '.$_GET["product_name"].'</td>
                <td style="width: 33%;">FROM DATE: '.$_GET["from_date"].'</td>
                <td style="width: 33%;">TO DATE: '.$_GET["to_date"].'</td>
            </tr>
            </table>
            <div></div>
            <table cellpadding="5" border="1">
            <tr style="font-weight:bold; border: solid 1px black">
                <td style="width: 5%;">Sr.</td>
                <td style="width: 8%;">Date</td>
                <td style="width: 15%;">Packing For</td>
                <td style="width: 20%;">Product Name</td>
                <td style="width: 10%;">Batch No.</td>
                <td style="width: 10%;">BMR No.</td>
                <td style="width: 10%;">Received Qty</td>
                <td style="width: 12%;">Send By</td>
                <td style="width: 10%;">Received By</td>
            </tr>';
            
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM packing b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='RECEIVED' AND b.receive_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                $packing_for = "";
                if ($row["manufacturer_for"] == "OWN") {
                    $packing_for = "OWN";
                } else {
                    $packing_for = $row["client_code"];
                }
                $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'.</td>
                    <td style="width: 8%;">'.date('d-m-Y',strtotime($row['receive_date'])).'</td>
                    <td style="width: 15%;">'.$packing_for.'</td>
                    <td style="width: 20%;">'.$row['product_name'].'</td>
                    <td style="width: 10%;">'.$row['batch_no'].'</td>
                    <td style="width: 10%;">'.$row['bmr_no'].'</td>
                    <td style="width: 10%;">'.$row['yield_qty'].' '.$row['unit'].'</td>
                    <td style="width: 12%;">'.$row['send_by'].' '.date('d-m-Y',strtotime($row['send_date'])).'</td>
                    <td style="width: 10%;">'.$row['receive_by'].'</td>
                </tr>';
                $i++;
            }
        }
        
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Challan Report.pdf', 'I');
    }


}

$conn->close();
?>