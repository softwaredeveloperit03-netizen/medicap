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
    
    if ($_GET["type"] == "saveWorkorder") {
        $products = $input["products"];
        for ($i = 0; $i < count($products); $i++) {
            $product = $products[$i];
            $sql = "INSERT INTO workorder (order_no,order_from, client_code, po_no, po_date, valid_till, 
            product_code, generic_name, qty, unit, entry_by, entry_date) VALUES ('".$input["order_no"]."','PO', '".$input["client_code"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["valid_till"]."', '".$product["product_code"]."', '".$product["label_claim"]."', '".$product["order_qty"]."', '".$product["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else if ($_GET["type"] == "getPendingPO") {
        $output = Array();
        $sql = "SELECT p.*, c.company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.status='approve' AND p.order_no NOT IN (SELECT order_no FROM workorder GROUP BY order_no)";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name, p.dosage_form, p.grade, p.packing_style, p.label_claim FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getWorkorders") {
        $output = array();
        $sql = "SELECT w.*, c.LglNm, p.product_name FROM workorder w LEFT JOIN product p ON w.product_code=p.product_code LEFT JOIN client c ON w.client_code=c.client_code WHERE w.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingWorkorders") {
        $output = array();
        $sql = "SELECT w.generic_name, SUM(w.qty) as qty, w.unit, p.product_name FROM workorder w LEFT JOIN product p ON w.product_code=p.product_code WHERE w.status='pending' GROUP BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT w.*, p.product_name, c.LglNm FROM workorder w LEFT JOIN product  p ON w.product_code=p.product_code LEFT JOIN client c ON w.client_code=c.client_code  WHERE w.status='pending' AND w.generic_name='".$row["generic_name"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output1 = array();
                $sql1 = "SELECT m.*, u.formula_for, m1.material_name,m1.material_subtype, m1.grade FROM unit_materials m LEFT JOIN unitformula u ON m.mfr_no=u.id LEFT JOIN material m1 ON m.material_code=m1.material_code WHERE m.mfr_no='1' AND m.material_type='Raw Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["available_stock"] = $row2["qty"];
                            $row1["stock_unit"] = $row2["unit"];
                        }
                        $output1[] = $row1;
                    }
                }
                $row["raw_materials"] = $output1;
                
                $output1 = array();
                $sql1 = "SELECT m.*, u.formula_for, m1.material_name,m1.material_subtype, m1.grade FROM unit_materials m LEFT JOIN unitformula u ON m.mfr_no=u.id LEFT JOIN material m1 ON m.material_code=m1.material_code WHERE m.mfr_no='1' AND m.material_type='Packing Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as qty, unit FROM stock_book WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["available_stock"] = $row2["qty"];
                            $row1["stock_unit"] = $row2["unit"];
                        }
                        $output1[] = $row1;
                    }
                }
                $row["packing_materials"] = $output1;
                
                $output1 = array();
                $row["finish_products"] = $output1;
                $output[] = $row;
            }
            
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadWorkorderLog") {
        $_GET['filename'] = 'Workorders'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Workorders</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 10%;">Order No</td>
                    <td style="width: 10%;">Client Name</td>
                    <td style="width: 10%;">Po No.</td>
                    <td style="width: 10%;">Po Date</td>
                    <td style="width: 10%;">Valid till</td>
                    <td style="width: 10%;">Product Name</td>
                    <td style="width: 12%;">Generic Name</td>
                    <td style="width: 8%;">Quantity</td>
                    <td style="width: 5%;">Unit</td>
                </tr>
            </thead>';
        $i=1;
      	$sql = "SELECT w.*, c.LglNm, p.product_name FROM workorder w LEFT JOIN product p ON w.product_code=p.product_code LEFT JOIN client c ON w.client_code=c.client_code WHERE w.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['entry_date'].'</td>
                        <td style="width: 10%;">'.$row['order_no'].'</td>
                        <td style="width: 10%;">'.$row['LglNm'].'</td>
                        <td style="width: 10%;">'.$row['po_no'].'</td>
                        <td style="width: 10%;">'.$row['po_date'].'</td>
                        <td style="width: 10%;">'.$row['valid_till'].'</td>
                        <td style="width: 10%;">'.$row['product_name'].'</td>
                        <td style="width: 12%;">'.$row['generic_name'].'</td>
                        <td style="width: 8%;">'.$row['qty'].'</td>
                        <td style="width: 5%;">'.$row['unit'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Workorder.pdf', 'I');
    }

}

$conn->close();
?>