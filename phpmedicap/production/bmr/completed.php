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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
     if ($_GET["type"] == "saveCompletedBatches") {
        $stages = $input["stages"];
        for ($i = 0; $i < count($stages); $i++) {
            $stage = $stages[$i];
            $stage["status"] = "pending";
            $stages[$i] = $stage;
        }
        $input["stages"] = $stages;
        
        $sql = "INSERT INTO bmr (user_no, company_unit, product_code, bom_no, raw_materials, packing_materials, stages, batch_size, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["company_unit"]."', '".$input["product_code"]."', '".$input["bom_no"]."', '".json_encode($input["raw_materials"])."', '".json_encode($input["packing_materials"])."', '".json_encode($input["stages"])."', '".$input["batch_size"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
     } else if ($_GET["type"] == "getCompletedBatches") {
         $output = array();
         $sql = "SELECT b.*, DATE(b.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM bmr b LEFT JOIN product p 
         ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."'ORDER BY id DESC";
   	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
        
    }else if ($_GET["type"] == "downloadCompletedBatches") {
        $_GET['filename'] = 'BMR Production Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:cenetr">BMR Production Report</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                   <td style="width:7%;"><b>BMR NO</b></td>
                <td style="width:10%;"><b>Product Type</b></td>
                <td style="width:10%;"><b>Product Name</b></td>
                <td style="width:7%;"><b>Batch No.</b></td>
                <td style="width:10%;"><b>Batch Qty (Kg)</b></td>
                <td style="width:10%;"><b>No. of Lots</b></td>
                <td style="width:8%;"><b>Yield (Kg)</b></td>
                <td style="width:10%;"><b>Yield %</b></td>
                <td style="width:8%;"><b>Start Date</b></td>
                <td style="width:10%;"><b>Completed Date</b></td>
                  <td style="width:10%;"><b>Uploaded BMR</b></td>

                   
                </tr>
            </thead>';
            $i=1;
            $output = array();
            $sql = "SELECT b.*, DATE(b.entry_date) as entry_date, p.product_type, p.product_name, p.grade FROM bmr b LEFT JOIN product p 
         ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."'ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                $html.='<tr nobr="true">
                  
                <td style="width:7%;">'.$row['bmr_no'].'</td>
                <td style="width:10%;">'.$row['product_type'].'</td>
                <td style="width:10%;">'.$row['product_name'].'</td>
                <td style="width:7%;">'.$row['batch_no'].'</td>
                <td style="width:10%;">'.$row['batch_size'].'</td>
                <td style="width:10%;">'.$row['lots'].'</td>
                <td style="width:8%;">'.$row['yield_qty'].'</td>
                <td style="width:10%;">'.$row['yield_per'].'</td>
                <td style="width:8%;">'.$row['start_date'].'</td>
                <td style="width:10%;">'.$row['complete_date'].'</td>
                 <td style="width:10%;">'.$row[''].'</td>
                            
                        </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('General Materials.pdf', 'I');
    }
    }

$conn->close();
?>