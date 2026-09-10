<?php
// ini_set('display_errors', 1);
//  error_reporting(E_ALL);

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

    if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE status='Approved'";
    //   echo  $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."'AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $row["label_claim"] = json_decode($row["label_claim"]);
                $output1 = Array();
                $sql1 = "SELECT * FROM batch_formula_info  WHERE product_code='".$row["product_code"]."'";// AND status='approve'";
                // $sql1 = "SELECT * FROM batch_formula WHERE material_code='".$row["product_code"]."'";// AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM batch_materials WHERE bfr_no='".$row1["bfr_no"]."'";
                        // $sql2 = "SELECT * FROM batch_materials WHERE no='".$row1["id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row2["amount"] = rand(100,999);
                                $output2[] = $row2;
                            }
                        }
                        $row1["materials"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getActualProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM batch_formula WHERE product_code='".$row["product_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * FROM bmr WHERE bom_no='".$row1["id"]."' WHERE status='complete'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT * FROM batch_materials WHERE no='".$row2["id"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $row3["amount"] = rand(100,999);
                                        $output3[] = $row3;
                                    }
                                }
                                $row2["materials"] = $output3;
                                
                                $output2[] = $row2;
                            }
                        }
                        $row1["materials"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    

    
        else if ($_GET["type"] == "saveCostingform") {
        $sql = "INSERT INTO costing (costing_type, product_for, product_code, batch_size, doller_value,
        materials, analytical_cost, ccpc_cost, fright_cost, other_cost, batch_cost, unit_cost, pack_cost, 
        entry_by, entry_date,currency,rate)
        VALUES ('".$input["costing_type"]."', '".$input["product_for"]."', '".$input["product_code"]."', 
        '".$input["batch_size"]."', '".$input["doller_value"]."', '".json_encode($input["materials"])."', 
         '".$input["analytical_cost"]."', '".$input["ccpc_cost"]."', '".$input["fright_cost"]."', '".$input["other_cost"]."', 
         '".$input["batch_cost"]."', '".$input["unit_cost"]."', '".$input["pack_cost"]."', '".$_GET["emp_id"]."', '$entry_date',
         '".$input["currency"]."', '".$input["rates"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveActualCosting") {
        $sql = "INSERT INTO costing (costing_type, product_for, product_code, batch_size, doller_value,
        materials, analytical_cost, ccpc_cost, fright_cost, other_cost, batch_cost, unit_cost, pack_cost, 
        entry_by, entry_date,currency,rate)
        VALUES ('".$input["costing_type"]."', '".$input["product_for"]."', '".$input["product_code"]."', 
        '".$input["batch_size"]."', '".$input["doller_value"]."', '".json_encode($input["materials"])."', 
         '".$input["analytical_cost"]."', '".$input["ccpc_cost"]."', '".$input["fright_cost"]."', '".$input["other_cost"]."', 
         '".$input["batch_cost"]."', '".$input["unit_cost"]."', '".$input["pack_cost"]."', '".$_GET["emp_id"]."', '$entry_date',
         '".$input["currency"]."', '".$input["rates"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

else if($_GET["type"] == "downloadCostingReport") {

      $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
           $sql = "SELECT c.*, p.product_name, p.grade FROM costing c LEFT JOIN product p ON c.product_code=p.product_code 
        WHERE c.costing_type LIKE '%".$_GET["costing_type"]."%' AND c.product_for LIKE '%".$_GET["product_for"]."%' 
        AND c.product_code LIKE '%".$_GET["product_code"]."' AND c.id = '".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
         $row = $result->fetch_assoc();{
        $html.='
        
        <h1 style="text-align:center;"><b>Costing Report</b></h1>
        <table  border="0.1" cellpadding="2">
    <tr>
        <td style=" width: 25%; text-align: left; font-weight: bold;">Product Name:</td>
        <td style=" width: 75%;text-align: left;">' . $row['product_name'] . '</td>
    </tr>
    <tr>
        <td style=" width: 25%; text-align: left; font-weight: bold;">Costing Type:</td>
        <td style=" width: 75%; text-align: left;">' . $row['costing_type'] . '</td>
    </tr>
    <tr>
        <td style=" width: 25%; text-align: left; font-weight: bold;">Grade:</td>
        <td style="width: 75%;text-align: left;">' . $row['grade'] . '</td>
    </tr>
    <tr>
        <td  style=" width: 25%;text-align: left; font-weight: bold;">Product For:</td>
        <td style="width: 75%;text-align: left;">' . $row['product_for'] . '</td>
    </tr>
    <tr>
        <td style="width: 25%; text-align: left; font-weight: bold;">Batch Size:</td>
        <td style=" width: 75%; text-align: left;">' . $row['batch_size'] . '</td>
    </tr> ';
     $k=1;
        $html.='</table>';
        
        $html.='<h3>Materials Details:</h3>
        <table  border="0.1" cellpadding="2">
    <tr>
        <th>Sr.</th>
        <th>Material Code</th>
        <th>Batch Size</th>
        <th>Purchase Rate Avg</th>
        <th>Purchase Rate</th>
        <th>Batch Unit</th>
        <th>Unit</th>
        <th>Amount</th>
    </tr>';
     $json_obj = $row['materials'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                    $material_code = $values['material_code'];
                    $batch_size = $values['batch_size'];
                    $purchase_rate_avg = $values['purchase_rate_avg'];
                    $purchase_rate = $values['purchase_rate'];
                    $b_unit = $values['b_unit'];
                    $unit = $values['unit'];
                    $rate = $values['rate'];
    $html.=' <tr>
        <td>' . $k++ . '</td>
        <td>' . $material_code . '</td>
        <td>' . $batch_size . '</td>
        <td>' . $purchase_rate_avg . '</td>
        <td>' . $purchase_rate . '</td>
        <td>' . $b_unit . '</td>
        <td>' . $unit . '</td>
         <td>' . $rate . '</td>


    </tr>';}
    $html.=' </table>';

  $html.=' <h3>Costing Details:</h3>
<table  border="0.1" cellpadding="2">
    <tr>
        <td>Analytical Cost:</td>
        <td>' . $row['analytical_cost'] . '</td>
        <td>CCPC:</td>
        <td>' . $row['ccpc_cost'] . '</td>
    </tr>
    <tr>
        <td>Freight Cost:</td>
        <td>' . $row['fright_cost'] . '</td>
        <td>Other Cost:</td>
        <td>' . $row['other_cost'] . '</td>
    </tr>
</table>

<h3>Total Costing</h3>
<table  border="0.1" cellpadding="2">
    <tr>
        <td>Total:</td>
        <td>' . $row['batch_cost'] . '</td>
    </tr>
    <tr>
        <td>Per Unit:</td>
        <td>' . $row['unit_cost'] . '</td>
    </tr>
    <tr>
        <td>Per Pack:</td>
        <td>' . $row['pack_cost'] . '</td>
    </tr>
</table>


';
            }}  
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
}



    else if ($_GET["type"] == "saveCosting1") {
        $sql = "INSERT INTO costing (costing_type, product_for, product_code, batch_size, doller_value,
        materials, analytical_cost, ccpc_cost, fright_cost, other_cost, batch_cost, unit_cost, pack_cost, 
        entry_by, entry_date,currency,rate)
        VALUES ('".$input["costing_type"]."', '".$input["product_for"]."', '".$input["product_code"]."', '".$input["batch_size"]."', '".$input["doller_value"]."', '".json_encode($input["materials"])."', '".$input["analytical_cost"]."', '".$input["ccpc_cost"]."', '".$input["fright_cost"]."', '".$input["other_cost"]."', '".$input["batch_cost"]."', '".$input["unit_cost"]."', '".$input["pack_cost"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["currency"]."', '".$input["rates"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getCostingReport") {
        $output = Array();
        $sql = "SELECT c.*, p.product_name, p.grade FROM costing c LEFT JOIN product p ON c.product_code=p.product_code 
        WHERE c.costing_type LIKE '%".$_GET["costing_type"]."%' AND c.product_for LIKE '%".$_GET["product_for"]."%' 
        AND c.product_code LIKE '%".$_GET["product_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>