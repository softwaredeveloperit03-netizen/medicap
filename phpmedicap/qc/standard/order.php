<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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

    if ($_GET["type"] == "saveOrdering") {
        $sql = "INSERT INTO orders (type,standard_name,req_qty,product_code,client_code,pack_size,unit,vendor_no,std_no, entry_by, entry_date) VALUES ('".$input["type"]."','".$input["standard_name"]."','".$input["req_qty"]."', '".$input["product_code"]."', '".$input["client_code"]."', '".$input["pack_size"]."', '".$input["unit"]."','".$input["vendor_no"]."','".$input["std_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getOrderingLog") {
        $output = Array();
        $sql = "SELECT o.*,v.vendor_name,c.LglNm,p.product_name FROM orders o LEFT JOIN vendor v ON v.vendor_no=o.vendor_no LEFT JOIN client c ON c.client_code=o.client_code LEFT JOIN product p ON p.product_code=o.product_code WHERE o.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadStandardorder") {
        $_GET['filename'] = 'Standard Ordering'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Standard Ordering</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr No.</b></td>
            <td style="width:15%; text-align:centre;"><b>Standard Name</b></td>
            <td style="width:15%; text-align:centre;"><b>Product Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Client Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Required Qty</b></td>
            <td style="width:10%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Pack Size</b></td>
            <td style="width:10%; text-align:centre;"><b>Unit</b></td>
            <td style="width:10%; text-align:centre;"><b>Standard No.</b></td>
        </tr>';
        $i=1;
        $sql = "SELECT o.*,v.vendor_name,c.LglNm,p.product_name FROM orders o LEFT JOIN vendor v ON v.vendor_no=o.vendor_no LEFT JOIN client c ON c.client_code=o.client_code LEFT JOIN product p ON p.product_code=o.product_code WHERE o.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:15%;">'.$row['standard_name'].'</td>
            <td style="width:15%;">'.$row['product_name'].'</td>
            <td style="width:10%;">'.$row['LglNm'].'</td>
            <td style="width:10%;">'.$row['req_qty'].'</td>
            <td style="width:10%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['pack_size'].'</td>
            <td style="width:10%;">'.$row['unit'].'</td>
            <td style="width:10%;">'.$row['std_no'].'</td>
        </tr>';
        $i++;
            }
        }
        $html.='</table>';
        
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('StandardOrdering.pdf', 'I');
    }  
}
$conn->close();
?>