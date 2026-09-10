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
        $sql = "INSERT INTO production_report (plant_id,lmr_no,  product_code,  lot_size, prepared_by,started_date, completed_date, entry_by, entry_date)
        VALUES ('".$_GET["plant_id"]."','".$input["lmr_no"]."',  '".$input["product_code"]."',  
        '".$input["lot_size"]."','".$input["prepared_by"]."', '$entry_date', '$completed_date', 
        '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $sql1 = "INSERT INTO unit_materials (user_no,mfr_no,material_type, material_code,  qty, unit, overages,
            role, process, yield_contributing) VALUES ('".$_GET["user_no"]."',
            '".$input["mfr_no"]."', '".$input["material_type"]."', '".$input["material_code"]."', 
            '".$input["qty"]."',  '".$input["unit"]."', '".$input["overages"]."','".$input["role"]."', 
            '".$input["process"]."', '".$input["yield_contributing"]."')";
                $conn->query($sql1);
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
     
   
    
    
    
      }else if($_GET["type"]=="getCompletedBatches") {
    $output = Array();
    $sql = "SELECT p.*,p1.product_name,p1.product_type,p1.product_code,p1.dosage_form,p1.shelf_life,p1.generic_name,p1.grade,
   p1. label_claim,p1.unit,mfg_lic
    FROM production_report p 
    LEFT JOIN product p1  ON p1.product_code=p.product_code  ";
     
	$result = $conn->query($sql);

	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    
		    
		    
			$output[] = $row;
		}
	}
	echo json_encode($output);
   } else if ($_GET["type"] == "downloadCompletedBatches") {
        $_GET['filename'] = 'LMR Production Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
         <h2 style="text-align:cenetr">LMR Production Report</h2>
        <table cellpadding="5" border="1">
       
            <tr>
                <td style="width:10%;"><b>Sr.</b></td>
                <td style="width:10%;"><b>LMR No</b></td>
                <td style="width:10%;"><b>Product Type</b></td>
                <td style="width:10%;"><b>Product Code</b></td>
                <td style="width:10%;"><b>Product Name</b></td>
                <td style="width:10%;"><b>Lot Size</b></td>
                <td style="width:10%;"><b>Prepared By</b></td>
                <td style="width:15%;"><b>Started Date</b></td>
                <td style="width:15%;"><b>Completed Date</b></td>
             </tr>';
             
             $sql = "SELECT * FROM production_report  ";
	$result = $conn->query($sql);
	 $i=1;
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		
		
	
             $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['lmr_no'].'</td>
                <td style="width:10%;">'.$row['product_type'].'</td>
                <td style="width:10%;">'.$row['product_code'].'</td>
                <td style="width:10%;">'.$row['product_name'].'</td>
                <td style="width:10%;">'.$row['lot_size'].'</td>
                <td style="width:10%;">'.$row['prepared_by'].'</td>
                <td style="width:15%;">'.$row['started_date'].'</td>
                 <td style="width:15%;">'.$row['completed_date'].'</td>
             </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('LMR Production Report.pdf', 'I');
    }
    
    }


$conn->close();
?>