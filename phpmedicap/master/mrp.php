<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveMrp") {
        $sql = "INSERT INTO mrp (mrp,client_code,manufactured_under,product_code,entry_by,entry_date) VALUES ('".$input["mrp"]."','".$input["client_code"]."','".$input["manufactured_under"]."','".$input["product_code"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "getMrp") {
        $output = array();
        $sql = "SELECT *,a.mrp as mrps FROM mrp a left join product b on a.product_code=b.product_code ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getSearchMrp") {
        $output = array();
         $sql = "SELECT a.*,a.mrp as mrps,b.product_name,COALESCE(c.LglNm,c.TrdNm,'NA') AS client_name FROM mrp a left join product b on a.product_code=b.product_code left join client c on a.client_code=c.client_code
        where (b.product_name LIKE   '%".$_GET["value"]."%' OR COALESCE(c.LglNm, c.TrdNm, 'NA') LIKE '%".$_GET["value"]."%') ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    if ($_GET["type"] == "saveSalesMrp") {
        $sql = "INSERT INTO salesmrp (plant_id,product_code,category,client_code,mrp,entry_by,entry_date) VALUES 
        ('".$_GET["plant_id"]."','".$input["product_code"]."','".$input["product_type"]."','".$input["client_code"]."','".$input["mrp"]."','".$_GET["emp_id"]."',
        '$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if ($_GET["type"] == "getPendingSalesMrp") {
        $output = array();
        $sql = "SELECT r.*,c.LglNm,p.product_name FROM salesmrp r LEFT JOIN  client c ON r.client_code=c.client_code 
        LEFT JOIN product p ON r.product_code=p.product_code WHERE r.status = 'pending' ";
        // $sql = "SELECT r.*, c.LglNm, p.product_name, r.category
        // FROM salesmrp r
        // LEFT JOIN client c ON r.client_code = c.client_code
        // LEFT JOIN product p ON r.product_code = p.product_code
        // WHERE r.status = 'pending'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "updateMrp"){
        $sql = "UPDATE salesmrp SET status = '".$_GET["status"]."' , approve_by='".$_GET["emp_id"]."' , approve_date='".$entry_date."' WHERE id ='".$_GET["id"]."' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "getSalesMrp") {
        $output = array();
        $sql = "SELECT r.*,c.LglNm,p.product_name,r.category FROM salesmrp r LEFT JOIN  client c ON r.client_code=c.client_code 
        LEFT JOIN product p ON r.product_code=p.product_code WHERE r.status != 'pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "editSalesMrp") {
          $sql = "UPDATE salesmrp SET product_code='".$input["product_code"]."',category='".$input['category']."',client_code='".$input['client_code']."',mrp='".$input['mrp']."', entry_by='".$input["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadSalesMrp") {
        $_GET['filename'] = 'Sales MRP Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">sales MRP Log</h2>
        <table border="1" cellpadding="3">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.No.</td>
                    <td style="width: 20%;">Product Name</td>
                    <td style="width: 20%;">Client  Name</td>
                    <td style="width: 20%;">Category</td>
                    <td style="width: 15%;">Mrp</td>
                    <td style="width:10%;">Entry Date</td>
                    <td style="width:10%;">Entry By</td>
                </tr>
            </thead>';
            $i=1;
            $sql = "SELECT r.*,c.LglNm,p.product_name FROM salesmrp r LEFT JOIN  client c ON r.client_code=c.client_code LEFT JOIN product p ON r.product_code=p.product_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i++.'.</td>
                    <td style="width: 20%;">'.$row['product_name'].'</td>
                    <td style="width: 20%;">'.$row['LglNm'].'</td>
                    <td style="width: 20%;">'.$row['category'].'</td>
                    <td style="width: 15%;">'.$row['mrp'].'</td>
                     <td style="width:10%;">' . date('d-m-y', strtotime($row['entry_date'])) . '</td>
                    <td style="width:10%;">' . $row['entry_by'] . '</td>
                </tr>';
                }
            }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Rate Mrp.pdf', 'I');
    }

}

$conn->close();
?>