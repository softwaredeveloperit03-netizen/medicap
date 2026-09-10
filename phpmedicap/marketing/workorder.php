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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getPendingPos") {
        $output = Array();
        $sql = "SELECT p.*, c.company, c.type, c.gst_type, c.gst_no, c.country, c.client_type FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.status='approve' AND p.workorder='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["products"] = json_decode($row["products"]);
                $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                $products = $row["products"];
                for ($i = 0; $i < count($products); $i++) {
                    $product = $products[$i];
                    $sql1 = "SELECT * FROM product WHERE product_code='".$product->product_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $product->product_name = $row1["product_name"];
                            $product->grade = $row1["grade"];
                        }
                    }
                    $products[$i] = $product;
                }
                $row["products"] = $products;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePOWorkOrder") {
        $sql = "INSERT INTO workorder (user_no,order_from,client_code, po_type, po_no, po_date, valid_till, products, configurations, terms, file, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','po','".$input["client_code"]."','".$input["po_type"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["valid_till"]."', '".json_encode($input["products"])."', '".json_encode($input["configurations"])."', '".json_encode($input["terms"])."', '".$input["file"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
            $sql = "UPDATE po_entry SET workorder='done' WHERE id='".$input["id"]."'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"]=="saveWorkOrder") {
        $sql = "INSERT INTO workorder (user_no,order_from, client_code, po_type, po_no, po_date, valid_till, products, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','direct','".$input["client_code"]."','".$input["po_type"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["valid_till"]."', '".json_encode($input["products"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingWorkOrders") {
        $output = Array();
        $sql = "SELECT w.*, DATE(w.entry_date) as entry_date, c.company, c.type, c.gst_type, c.gst_no, c.country, c.client_type FROM workorder w LEFT JOIN client c ON w.client_code=c.client_code WHERE user_no='".$_GET["user_no"]."' AND w.status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["products"] = json_decode($row["products"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateWorkOrder") {
        $sql = "UPDATE workorder SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getWorkOrders") {
        $output = Array();
        $sql = "SELECT w.*, DATE(w.entry_date) as entry_date, c.company, c.type, c.gst_type, c.gst_no, c.country, c.client_type FROM workorder w LEFT JOIN client c ON w.client_code=c.client_code WHERE w.user_no='".$_GET["user_no"]."' AND w.client_code LIKE '%".$_GET["client_code"]."' AND w.status LIKE '%".$_GET["status"]."%' AND DATE(w.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["products"] = json_decode($row["products"]);
                $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                $row["file"] = "upload/po_entry/".$row["file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTransporters") {
        $output = Array();
        $sql = "SELECT * FROM transporter_management";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } else if ($_GET["type"] == "printworkorder") {
        $_GET['filename'] = 'Work Order'; $_GET['pdftype'] ='onlyheader'; include("../pdfimp2.php");
        $sql = "SELECT * FROM workorder WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        $row["products"] = json_decode($row["products"]);
        $row["terms"] = json_decode($row["terms"]);
        
        $html.='
        <h2 style="text-align:center">Work Order</h2>
        <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
            </table>
            <table cellpadding="5">
                <tr>
                    <td style="width:5%;">Sr No.</td>
                    <td style="width:10%;">Item Code</td>
                    <td style="width:25%;">Item Description</td>
                    <td style="width:6%;">HSN / SAC</td>
                    <td style="width:9%;">QTY</td>
                    <td style="width:8%;">Unit</td>
                    <td style="width:10%;">MRP</td>
                    <td style="width:10%;">Rate</td>
                    <td style="width:5%;">Disc %</td>
                    <td style="width:12%;">Amount</td>
                </tr>';
                
                $total = 0;
                $products = $row["products"];
                for ($i = 0; $i < count($products); $i++) {
                    $product = $products[$i];
                    $j = $i + 1;
                    $html.='<tr>
                        <td>'.$j.'</td>
                        <td>'.$product->product_code.'</td>
                        <td>'.$product->product_name.'</td>
                        <td>'.$product->hsn.'</td>
                        <td>'.$product->qty.'</td>
                        <td>'.$product->unit.'</td>
                        <td>'.$product->mrp.'</td>
                        <td>'.$product->rate.'</td>
                        <td>'.$product->disc.'</td>
                        <td>'.$product->amount.'</td>
                    </tr>';
                    $total += +$product->amount;
                }
            $html.='</table>
            <table cellpadding="5">
                <tr>
                    <td></td>
                    <td>Total: '.round($total, 2).'</td>
                </tr>
            </table>
            <table cellpadding="2" style="border:solid 1px BCBBBA;">
                <tr>
                    <td><b>Terms & Conditions</b></td>
                </tr>';
                $terms = $row["terms"];
                for ($i = 0; $i < count($terms); $i++) {
                    $term = $terms[$i];
                    $html.='<tr>
                        <td style="border:none;">'.$term->terms.'</td>
                    </tr>';
                }
                $html.='
            </table>
        ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('workOrder.pdf', 'I');
    }
}

$conn->close();
?>