<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);


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
   
    if ($_GET["type"] == "saveQuotation") {
        
        
        $flag = false;
        
        $sql = "INSERT INTO `client_quotation`(`plant_id`,`client_code`, `branch`,`terms`, `status`, `accepted_by_client`, `entry_by`, `entry_date`, `licAmount`, 
        `currency`, `lic_data`, `generalTerm`, `dossierTerm`, `packingTerm`, `regulatoryTerm`,`selectedBranch`, `market`, `offer_type`, `dossier`, `reference_product`,
        `grade`) VALUES ('".$_GET["plant_id"]."','".$input["client_code"]."', 'NA', '".json_encode($input["terms"])."', 'Pending','NA',  '".$_GET["emp_id"]."', 
        '$entry_date','".$input["licAmount"]."','".$input["currency"]."', '".json_encode($input["lic_data"])."','".json_encode($input["generalTerm"])."', 
        '".json_encode($input["dossierTerm"])."', '".json_encode($input["packingTerm"])."' , '".json_encode($input["regulatoryTerm"])."' , 'NA','".$input["market"]."',
        '".$input["offer_type"]."','".$input["dossier"]."','".$input["reference_product"]."','".$input["grade"]."')";
        
        if ($conn->query($sql)) {


                $qoatId = $conn->insert_id;
                
                $array =  $input["productList"] ;
                
                foreach($array as $values) {
                     
                    $plant_id = $_GET["plant_id"];
                    $product_code       = $conn->real_escape_string($values["product_code"]);
                    $qty    = $conn->real_escape_string($values["qty"]);
                    $rate                = $conn->real_escape_string($values["rate"]);
                    $quotation_per          = $conn->real_escape_string($values["quotation_per"]);
                    $pack_qty      = $conn->real_escape_string($values["pack_qty"]);
                    $moq    = $conn->real_escape_string($values["moq"]);
                    $batch_size  = $conn->real_escape_string($values["batch_size"]);
                
                    // Build safe SQL query
                    $sql1 = "INSERT INTO quotProduct (plant_id, status, quoatId, product_code, qty, rate, quotation_per, pack_qty, moq, batch_size, entryBy, entryOn)
                    VALUES ('$plant_id', 'Pending', '$qoatId','$product_code', '$qty', '$rate', '$quotation_per', '$pack_qty', '$moq', '$batch_size',
                    '".$_GET["emp_id"]."', '$entry_date' )";
                     
                    if($conn->query($sql1)) {
                        $flag = true;
                    }else{
                        $flag = false;
                    }
                    
                }
                
            }   
                
            if($flag) {  
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
            
            
        
    }
    
    
    else if ($_GET["type"] == "saveQuotationMeha") {
        
         $sql = "INSERT INTO `client_quotation`(`dateQuote`, `revNo`, `revDate`, `agentName`, `commissionPer`, 
         `commissionBasis`, `priceDiffPerKg`, `buyerName`, `buyerEmail`, `buyerEnqDate`, `buyerCountry`, `endCustName`, `endCustCountry`, 
         `destCountry`, `destiPort`, `remark`, `modeOfTransport`, `deliveryUpto`, `deliveryTime`, `paymentTerms`, `priceBasisFob`, 
         `buyerSpecsRec`, `cdaNda`, `cpa`, `validity`,`marketType`, `currency`, `productList`, `terms`, `status`, `entry_by`, `entry_date`) VALUES 
         ( '".$input["dateQuote"]."', '".$input["revNo"]."','".$input["revDate"]."','".$input["agentName"]."',
         '".$input["commissionPer"]."','".$input["commissionBasis"]."','".$input["priceDiffPerKg"]."','".$input["buyerName"]."',
         '".$input["buyerEmail"]."','".$input["buyerEnqDate"]."','".$input["buyerCountry"]."','".$input["endCustName"]."',
         '".$input["endCustCountry"]."','".$input["destCountry"]."','".$input["destiPort"]."','".$input["remark"]."',
         '".$input["modeOfTransport"]."','".$input["deliveryUpto"]."','".$input["deliveryTime"]."','".$input["paymentTerms"]."',
         '".$input["priceBasisFob"]."','".$input["buyerSpecsRec"]."','".$input["cdaNda"]."','".$input["cpa"]."','".$input["validity"]."',
         '".$input["marketType"]."','".$input["currency"]."','".json_encode($input["productList"])."','".json_encode($input["add_term"])."',
         'Pending','".$_GET["emp_id"]."','$entry_date')";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    } 
    else if ($_GET["type"] == "updateQuotationMeha") {
        
        $sql = "UPDATE `client_quotation` SET `dateQuote` = '".$input["dateQuote"]."', `revNo` = '".$input["revNo"]."', `revDate` = '".$input["revDate"]."',
        `agentName` = '".$input["agentName"]."', `commissionPer` = '".$input["commissionPer"]."',`commissionBasis` = '".$input["commissionBasis"]."', 
        `priceDiffPerKg` = '".$input["priceDiffPerKg"]."', `buyerName` = '".$input["buyerName"]."',`buyerEmail` = '".$input["buyerEmail"]."', 
        `buyerEnqDate` = '".$input["buyerEnqDate"]."', `buyerCountry` = '".$input["buyerCountry"]."',`endCustName` = '".$input["endCustName"]."', 
        `endCustCountry` = '".$input["endCustCountry"]."',`destCountry` = '".$input["destCountry"]."', `destiPort` = '".$input["destiPort"]."', 
        `remark` = '".$input["remark"]."', `modeOfTransport` = '".$input["modeOfTransport"]."',`deliveryUpto` = '".$input["deliveryUpto"]."', 
        `deliveryTime` = '".$input["deliveryTime"]."', `paymentTerms` = '".$input["paymentTerms"]."',`priceBasisFob` = '".$input["priceBasisFob"]."', 
        `buyerSpecsRec` = '".$input["buyerSpecsRec"]."', `cdaNda` = '".$input["cdaNda"]."', `cpa` = '".$input["cpa"]."', `validity` = '".$input["validity"]."',
        `marketType` = '".$input["marketType"]."', `currency` = '".$input["currency"]."', `productList` = '".json_encode($input["productList"])."', 
        `terms` = '".json_encode($input["add_term"])."' WHERE id = '".$_GET["id"]."' ";
         
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    } 
    else if ($_GET["type"] == "saveProforma") {
        
        $sql = "UPDATE `client_quotation` SET `discountPer`= '".$input["discountPer"]."',`discAmt`= '".$input["discAmt"]."',
        `subTotalAfterDisc`= '".$input["subTotalAfterDisc"]."',`insurancePer`= '".$input["insurancePer"]."',
        `insuranceAmt`= '".$input["insuranceAmt"]."',`subTotalAfterInsurance`= '".$input["subTotalAfterInsurance"]."',
        `gstPer`= '".$input["gstPer"]."',`gstAmt`= '".$input["gstAmt"]."',`freightAmt`= '".$input["freightAmt"]."',
        `tcsPer`= '".$input["tcsPer"]."',`tcsAmt`= '".$input["tcsAmt"]."',`finalAmt`= '".$input["finalAmt"]."',
        `orderAcceptanceNo`= '".$input["orderAcceptanceNo"]."',`proformaData`= '".$input["proformaData"]."',
        `buyerOrderNo`= '".$input["buyerOrderNo"]."',`buyerOrderDate`= '".$input["buyerOrderDate"]."',
        `indentOrderNo`= '".$input["indentOrderNo"]."',`indentOrderDate`= '".$input["indentOrderDate"]."',
        `agentNamePf`= '".$input["agentNamePf"]."',`otherRef`= '".$input["agentNamePf"]."',`consignee`= '".$input["consignee"]."',
        `buyerNamePf`= '".$input["buyerNamePf"]."',`notifyParty`= '".$input["notifyParty"]."',
        `modeOfTransportpf`= '".$input["modeOfTransportpf"]."',`originOfGoods`= '".$input["originOfGoods"]."',
        `portOfLoading`= '".$input["portOfLoading"]."',`finalDestination`= '".$input["finalDestination"]."',
        `portOfDischarge`= '".$input["portOfDischarge"]."',`palletoatim`= '".$input["palletoatim"]."',
        `termsOfDelivery`= '".$input["termsOfDelivery"]."',`termsOfPaymentPf`= '".$input["termsOfPaymentPf"]."',
        `priceBasisFobPf`= '".$input["priceBasisFobPf"]."',`insuranceBasis`= '".$input["insuranceBasis"]."',`marks`= '".$input["marks"]."',
        `otherInstructionData` = '".json_encode($input["otherInstructionData"])."' ,`qtStatus` = 'CreatedProforma' WHERE id = '".$input["id"]."'";
          
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    } 
     
    else if ($_GET["type"] == "getPendingQuotations") {
        $output = array();
        $sql = "SELECT q.*, c.LglNm  FROM client_quotation q LEFT JOIN client c ON q.client_code = c.client_code WHERE  q.status = 'Pending' AND  q.plant_id = '".$_GET["plant_id"]."' ";// GROUP BY q.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["terms"] = json_decode($row["terms"]);
                $row["selectedBranch"] = json_decode($row["selectedBranch"]);
                $row["regulatoryTerm"] = json_decode($row["regulatoryTerm"]);
                $row["packingTerm"] = json_decode($row["packingTerm"]);
                $row["lic_data"] = json_decode($row["lic_data"]);
                $row["dossierTerm"] = json_decode($row["dossierTerm"]);
                $row["generalTerm"] = json_decode($row["generalTerm"]);
                
                $output1 = array();
                $sql1 = "SELECT qp.*,p.product_name,p.product_type FROM quotProduct qp LEFT JOIN product p ON qp.product_code = p.product_code WHERE  quoatId = '".$row["id"]."' "; 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }
                
                $row["productList"] = $output1;
                $output[] = $row; 
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPlantData") {
        $output = array();
        $sql = "SELECT * FROM plant WHERE plant_id = '".$_GET["plant_id"]."' "; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row; 
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateQuotation") {
        $sql = "UPDATE client_quotation SET status='".$_GET["status"]."', approve_by = '".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "acceptQuatation") {
        $sql = "UPDATE client_quotation SET qtStatus = 'Accepted'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "acceptQuatation") {
        $sql = "UPDATE client_quotation SET accepted_by_client = 'Accepted'  WHERE quotation_no='".$_GET["quotation_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getQuotationsLog") {
         
        $output = array();
        
        
        if($_GET['quotFor'] == 'Yes'){
            $sql = "SELECT q.*, c.LglNm  FROM client_quotation q LEFT JOIN client c ON q.client_code = c.client_code 
            WHERE q.plant_id = '".$_GET["plant_id"]."' "; 
        }else{
            $sql = "SELECT q.*, c.LglNm  FROM client_quotation q LEFT JOIN client c ON q.client_code = c.client_code 
            WHERE q.plant_id = '".$_GET["plant_id"]."' AND q.entry_by = '".$_GET["emp_id"]."' "; 
        }
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["terms"] = json_decode($row["terms"]);
                $row["selectedBranch"] = json_decode($row["selectedBranch"]);
                $row["regulatoryTerm"] = json_decode($row["regulatoryTerm"]);
                $row["packingTerm"] = json_decode($row["packingTerm"]);
                $row["lic_data"] = json_decode($row["lic_data"]);
                $row["dossierTerm"] = json_decode($row["dossierTerm"]);
                $row["generalTerm"] = json_decode($row["generalTerm"]);
                
                $output1 = array();
                $sql1 = "SELECT qp.*,p.product_name,p.product_type FROM quotProduct qp LEFT JOIN product p ON qp.product_code = p.product_code WHERE  quoatId = '".$row["id"]."' "; 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1; 
                    }
                }
                
                $row["productList"] = $output1;
                $output[] = $row; 
            }
        }
        echo json_encode($output);
     
    } 
    else if ($_GET["type"] == "downloadQuotation") {
        $_GET['filename'] = 'Quatation Details'; 
        $_GET['pdftype'] = 'onlyheader'; 
        include("../pdfimp2.php");
        
        $html= "";
        
        $sql = "SELECT q.*, c.LglNm  FROM client_quotation q LEFT JOIN client c ON q.client_code = c.client_code WHERE 
        q.plant_id = '".$_GET["plant_id"]."' AND q.id='".$_GET["id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
        	$html= "";
			$html.='<table>
                        <tr>
                            <td style="width: 540px;text-align:center;font-size:25px;">Quotation</td>
                            </td>
                        </tr>
                     </table>
                     <div></div>
                    <table  border="1" cellpadding="2" style="border:solid rgb(14, 67, 112) 1px ;">
                        <tr>
                            <td style="width:20%; text-align:left;"><b>Client Name</b></td>
                            <td style="width:80%; text-align:left;"> '.$row['LglNm'].'</td>
                        </tr>
                        <tr>
                            <td style="width:20%; text-align:left;"><b>Client Name</b></td>
                            <td style="width:80%; text-align:left;"> '.$row['client_code'].'</td>
                        </tr>
                    </table>  
                    <div></div> 
                    
                    <table border="1" cellpadding="3">
                        <tr>
                          <td style="text-align: center; font-weight:bolder;">Markets</td>
                          <td style="text-align: center; font-weight:bolder;">Offer Type</td>
                          <td style="text-align: center; font-weight:bolder;">Dossier</td>
                        </tr> 
                        <tr>
                          <td style="text-align: center; ">' . $row['market'] . '  </td>
                          <td style="text-align: center; ">' . $row['offer_type'] . ' </td>
                          <td style="text-align: center; ">' . $row['dossier'] . '</td>
                        </tr>
                    </table>
      
                    
                    <div></div>
                      
                     <table  border="1" cellpadding="2" style="border:solid rgb(14, 67, 112) 1px ;">
                        <tr style="background-color: black; color: white;">
                            <td style="text-align: left; padding: 10px;">Product Name</td>
                            <td style="text-align: left; padding: 10px;">Rate (ex-work)</td>
                            <td style="text-align: left; padding: 10px;">Qty</td>
                            <td style="text-align: left; padding: 10px;">Quotation per</td>
                            <td style="text-align: left; padding: 10px;">Pack Qty.</td>
                            <td style="text-align: left; padding: 10px;">MOQ</td>
                            <td style="text-align: left; padding: 10px;">Batch Size (million)</td>
                        </tr>';
                        
                $sql1 = "SELECT qp.*,p.product_name,p.product_type FROM quotProduct qp LEFT JOIN product p ON qp.product_code = p.product_code WHERE  quoatId = '".$row["id"]."' "; 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $couter = $i + 1;
                        $html.='
                            <tr>
                                <td>'.$row1['product_name'].' ( '.$row1['product_code'].' )</td>
                                <td>'.$row1['rate'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['quotation_per'].'</td>
                                <td>'.$row1['pack_qty'].'</td>
                                <td>'.$row1['moq'].'</td>
                                <td>'.$row1['batch_size'].'</td>
                            </tr>';
                    }
                }
            
            
        	$html.=' 
       
                </table> 
               
                <div></div> 
        
            <h3>License Fees:</h3>
     
            <div></div> 
            
            <table border="1" cellpadding="3"> ';
          $html.='  ';
                $json_obj = $row['lic_data'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                    
                  $html.='
                  
                    <tr>
                      <td style="width:15%; text-align: center; ">' . $values['peramt'] . ' % </td>
                      <td style="width:70%; text-align: left; ">' . $values['term'] . ' </td>
                      <td style="width:15%; text-align: center; ">' . $values['amt'] . ' ' . $values['curr'] . '</td>
                    </tr>';
                }
                $html.='
                    
                   
        
                  </table>
                  
            <div></div>
        <table border="1" cellpadding="2">
            <tr style="background-color:black; color:white;">
                  <td style="width: 540px;">   General Terms of License and Supply</td>
              </tr>';
          $html.='  ';
                $json_obj = $row['generalTerm'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $term = $values['term'];
                  $html.='
                  
                  <tr>
                  <td style="width: 54px; text-align:center"> ' . $k++ . '</td>
                  <td style="width: 486px;text-align:left;"> ' . $term . '</td>
                  </tr>';
                }
                $html.='
              
             </table>
         <div></div>
        <table border="1" cellpadding="2">
            <tr style="background-color:black; color:white;">
                  <td style="width: 540px;"> Dossier/ IP related considerations</td>
              </tr>';
          $html.='  ';
                $json_obj = $row['dossierTerm'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $term = $values['term'];
                  $html.='
                  
                  <tr>
                  <td style="width: 54px; text-align:center"> ' . $k++ . '</td>
                  <td style="width: 486px;text-align:left;"> ' . $term . '</td>
                  </tr>';
                }
                $html.='
              
             </table>
         <div></div>
         <table border="1" cellpadding="2">
            <tr style="background-color:black; color:white;">
                  <td style="width: 540px;"> Packing Conditions & Packaging Material Considerations </td>
              </tr>';
          $html.='  ';
                $json_obj = $row['packingTerm'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $term = $values['term'];
                  $html.='
                  
                  <tr>
                  <td style="width: 54px; text-align:center"> ' . $k++ . '</td>
                  <td style="width: 486px;text-align:left;"> ' . $term . '</td>
                  </tr>';
                }
                $html.='
              
             </table>
         <div></div>
         <table border="1" cellpadding="2">
            <tr style="background-color:black; color:white;">
                  <td style="width: 540px;"> Other Regulatory Costs  </td>
              </tr>';
          $html.='  ';
                $json_obj = $row['regulatoryTerm'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $term = $values['term'];
                  $html.='
                  
                  <tr>
                  <td style="width: 54px; text-align:center"> ' . $k++ . '</td>
                  <td style="width: 486px;text-align:left;"> ' . $term . '</td>
                  </tr>';
                }
                $html.='
              
             </table>
         <div></div>

              <table border="1">';
              
        //   $term_heading = "";
                // $hdr_printed = false;
               $html.='   <tr style="background-color:black; color:white;">
                  <td style="width: 540px;">    Terms & Conditions </td>
                 </tr>';
                $json_obj = $row['generalTerm'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $term = $values['term'];
                  $html.='
                  
                  <tr>
                  <td style="width: 54px; text-align:center"> ' . $k++ . '</td>
                  <td style="width: 486px;text-align:left;"> ' . $term . '</td>
                  </tr>';
                  }}}
     $html.=' </table>';
      $html.='
     <div></div>
        ';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadQuotation.pdf', 'I');
        
  
    }
    else if ($_GET["type"] == "downloadQuotationLog") {}
    
}

$conn->close();
?>