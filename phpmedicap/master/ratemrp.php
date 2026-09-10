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
    
    if ($_GET["type"] == "saveRateMrp") {
         $sql = "INSERT INTO ratemrp (plant_id,rate_unit,rate_for,unit,product_code,category,client_code,rate,entry_by,entry_date)
      VALUES ('".$_GET["plant_id"]."','".$input["rate_unit"]."','".$input["rate_for"]."','".$input["unit"]."','".$input["product_code"]."','".$input["product_type"]."','".$input["client_code"]."','".$input["rates"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "getPendingRateMrp") {
        $output = array();
        $sql = "SELECT r.*,c.LglNm,p.product_name FROM ratemrp r LEFT JOIN  client c ON r.client_code=c.client_code LEFT JOIN product p ON r.product_code=p.product_code WHERE r.status = 'pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "updateRateMrp"){
        $sql = "UPDATE ratemrp SET status = '".$_GET["status"]."' , approve_by='".$_GET["emp_id"]."' , approve_date='".$entry_date."' WHERE id ='".$_GET["id"]."' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if ($_GET["type"] == "getRateMrp") {
        $output = array();
        $plant=$_GET['plant_id'];
       
        
          $sql = "SELECT r.*,c.LglNm,p.product_name FROM ratemrp r LEFT JOIN client c ON r.client_code=c.client_code 
         LEFT JOIN product p ON r.product_code=p.product_code where r.status ='approve' AND r.plant_id='".$_GET["plant_id"]."' 
         ORDER BY r.id DESC";  
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "editRateMrp") {
        $sql = "UPDATE ratemrp SET client_code='".$input['client_code']."',rate='".$input['rates']."', entry_by='".$_GET["emp_id"]."',
        entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        
        //echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadRateMrp") {
        $_GET['filename'] = 'Rate'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Rate</h2>
        <table border="1" cellpadding="3">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr.No.</td>
                    <td style="width:30%;">Client  Name</td>
                    <td style="width:20%;">Product Name</td>
                    <td style="width:20%;">Sale Category</td>
                    <td style="width:10%;">Rate</td>
                    <td style="width:15%;">Status</td>
                </tr>
            </thead>';
            $i=1;
            $sql = "SELECT r.*,c.LglNm,p.product_name FROM ratemrp r LEFT JOIN  client c ON r.client_code=c.client_code LEFT JOIN product p ON r.product_code=p.product_code WHERE r.client_code LIKE '%".$_GET["client_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width:5%;">'.$i++.'.</td>
                    <td style="width:30%;">'.$row['LglNm'].'</td>
                    <td style="width:20%;">'.$row['product_name'].'</td>
                    <td style="width:20%;">'.$row['category'].'</td>
                    <td style="width:10%;">'.$row['rate'].'</td>
                    <td style="width:15%;">'.$row['status'].'</td>
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