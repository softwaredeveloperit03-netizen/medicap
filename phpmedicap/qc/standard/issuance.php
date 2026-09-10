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

    if ($_GET["type"] == "saveIssuance") {
        $sql = "INSERT INTO issuance (category,standard_name,req_qty,req_unit,qty_issued,issued_unit,issued_by,issued_to,issued_for, entry_by, entry_date) VALUES ('".$input["category"]."','".$input["standard_name"]."','".$input["req_qty"]."','".$input["req_unit"]."', '".$input["qty_issued"]."', '".$input["issued_unit"]."','".$input["issued_by"]."', '".$input["issued_to"]."', '".$input["issued_for"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIssuance") {
        $output = Array();
        $sql = "SELECT i.*,e.firstname,e1.firstname as issuedname,p.product_name FROM issuance i LEFT JOIN employee e ON e.emp_id=i.issued_by LEFT JOIN employee e1 ON e1.emp_id=i.issued_to LEFT JOIN product p ON p.product_code=i.issued_for WHERE i.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }if ($_GET["type"] == "downloadStandardIssuanceLog") {
        $_GET['filename'] = 'StandardIssuanceLog'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Standard Issuance Log</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr No.</b></td>
            <td style="width:15%; text-align:centre;"><b>Category</b></td>
            <td style="width:15%; text-align:centre;"><b>Standard Name</b></td>
            <td style="width:15%; text-align:centre;"><b>Quantity Required</b></td>
            <td style="width:15%; text-align:centre;"><b>Quantity Issued</b></td>
            <td style="width:10%; text-align:centre;"><b>Issued By</b></td>
            <td style="width:10%; text-align:centre;"><b>Issued To</b></td>
            <td style="width:10%; text-align:centre;"><b>Issued For</b></td>
        </tr>';
        $i=1;
        $sql = "SELECT i.*,e.firstname,e1.firstname as issuedname,p.product_name FROM issuance i LEFT JOIN employee e ON e.emp_id=i.issued_by LEFT JOIN employee e1 ON e1.emp_id=i.issued_to LEFT JOIN product p ON p.product_code=i.issued_for WHERE i.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:15%;">'.$row['category'].'</td>
            <td style="width:15%;">'.$row['standard_name'].'</td>
            <td style="width:15%;">'.$row['req_qty'].'</td>
            <td style="width:15%;">'.$row['qty_issued'].'</td>
            <td style="width:10%;">'.$row['issued_by'].'</td>
            <td style="width:10%;">'.$row['issued_to'].'</td>
            <td style="width:10%;">'.$row['issued_for'].'</td>
        </tr>';
        $i++;
            }
        }
        $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('StandardIssuanceLog.pdf', 'I');
    }
}
$conn->close();
?>