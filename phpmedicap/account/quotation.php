<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    
 
    
    
    if ($_GET["type"] == "downloadProformaMeha") {
        $_GET['filename'] = 'Quatation Details'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
         $sql = "SELECT q.*, c.TrdNm  FROM client_quotation q LEFT JOIN client c ON q.buyerName = c.client_code  WHERE q.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  
        
        $html.='
        
            <div><span style="font-weight: bold; text-align: center;">QUOTE '.$row['marketType'].'</span></div>
        
            <div></div>
        
            <table cellpadding="2" border="1" >
              <tr>
                <td style="text-align: left; font-size:8px;" colspan="2"><b>MANUFACTURER</b></td>
                <td style="text-align: left; font-size:8px;"><b>Order Acceptance # : <span style="color: blue;">'.$row['orderAcceptanceNo'].'</span></b></td>
                <td style="text-align: left; font-size:8px;"></td>
                <td style="text-align: left; font-size:8px;"><b>DATE : <span style="color: blue;">'.$row['proformaData'].'</span></b></td>
                <td style="text-align: left; font-size:8px;"></td>
              </tr>
              <tr>
                <td style="text-align: left; font-size:8px;color: blue;" colspan="2">
                <b>MEHA PHARMA Private Limited</b> <br>
                281 Kundaim Industrial Estate,<br>
                Kundaim, Goa 403115, India<br>
                <b>GST NO:  30AAQCM7471C1ZI</b><br>
                HO:  Office No. 1104, 11th Floor,<br>
                Accord Classic, Above Anupam Stationery,<br>
                Station Road, Goregaon (East), <br>
                Mumbai 400063, India
                </td>
                <td style="text-align: center; font-size:8px;"><b>Buyers order No & Date <br>
                    <span style="color: blue;">'.$row['buyerOrderNo'].'</span><br>
                    <span style="color: blue;">'.$row['buyerOrderDate'].'</span>
                    </b>
                </td>
                <td style="text-align: center; font-size:8px;"><b>Indent Order # & Date <br>
                    <span style="color: blue;">'.$row['indentOrderNo'].'</span><br>
                    <span style="color: blue;">'.$row['indentOrderDate'].'</span>
                    </b>
                </td>
                <td style="text-align: center; font-size:8px;"><b>Agent Details <br>
                    <span style="color: blue;">'.$row['agentNamePf'].'</span>  </b>
                </td>
                <td style="text-align: center; font-size:8px;"><b>Other References <br>
                    <span style="color: blue;">'.$row['otherRef'].'</span>  </b>
                </td>
               </tr>
               <tr>
                <td style="text-align: center; font-size:8px;" colspan="2"><b>CONSIGNEE</b></td>
                <td style="text-align: center; font-size:8px;" colspan="2"><b>BUYER</b></td>
                <td style="text-align: center; font-size:8px;" colspan="2"><b>NOTIFY PARTY</b></td>
               </tr>
               <tr>
                <td style="text-align: center; font-size:8px;" colspan="2"><b><span style="color: blue;">'.$row['consignee'].'</span></b></td>
                <td style="text-align: center; font-size:8px;" colspan="2"><b><span style="color: blue;">'.$row['buyerNamePf'].'</span></b></td>
                <td style="text-align: center; font-size:8px;" colspan="2"><b><span style="color: blue;">'.$row['notifyParty'].'</span></b></td>
               </tr>
               <tr>
                <td style="text-align: center; font-size:8px;"><b>MODE OF TRANSPORT</b></td>
                <td style="text-align: center; font-size:8px;"><b>ORIGIN OF GOODS</b></td>
                <td style="text-align: center; font-size:8px;"><b>DELIVERY UPTO</b></td>
                <td style="text-align: center; font-size:8px;"><b>FINAL DESTINATION</b></td>
                <td style="text-align: center; font-size:8px;"><b>PORT OF DISCHARGE</b></td>
                <td style="text-align: center; font-size:8px;"><b>Palletoatiom </b></td>
               </tr>
               <tr>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['modeOfTransportpf'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['originOfGoods'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['portOfLoading'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['finalDestination'].'</span></b></td>';
                
                 
                
             if($row['marketType'] == 'DOMESTIC'){      
      $html.='  <td style="text-align: center; font-size:8px;"></td>
                <td style="text-align: center; font-size:8px;"></td>';
                 
             }else{
      $html.='  <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['portOfDischarge'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['palletoatim'].'</span> </b></td>';
                 
             }
                
                
                
                
                
                
                
                
                
                
           $html.='    </tr>
               <tr>
                 <td style="text-align: center; font-size:8px;"><b>TERMS OF DELIVERY</b></td>
                <td style="text-align: center; font-size:8px;"><b>TERMS OF PAYMENT</b></td>
                <td style="text-align: center; font-size:8px;"><b>PRICE BASIS</b></td>
                <td style="text-align: center; font-size:8px;"><b>INSURANCE BASIS</b></td>
                <td style="text-align: center; font-size:8px;" colspan="2"><b>MARKS </b></td>
               </tr>
               <tr>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['termsOfDelivery'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['termsOfPaymentPf'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['priceBasisFobPf'].'</span></b></td>
                <td style="text-align: center; font-size:8px;"><b><span style="color: blue;">'.$row['insuranceBasis'].'</span></b></td>
                <td style="text-align: center; font-size:8px;" colspan="2"><b><span style="color: blue;">'.$row['marks'].'</span> </b></td>
               </tr>
               <tr>
                <td style="text-align: center; font-size:8px;"><b>PRODUCT & GRADE N PHARMACOPEIA</b></td>
                <td style="text-align: center; font-size:8px;"><b>HS CODE</b></td>
                <td style="text-align: center; font-size:8px;"><b>PACKING</b></td>
                <td style="text-align: center; font-size:8px;"><b>QUANTITY</b></td>
                <td style="text-align: center; font-size:8px;"><b>RATE PER KG-'.$row['currency'].'</b></td>
                <td style="text-align: center; font-size:8px;"><b>AMOUNT '.$row['currency'].'</b></td>
               </tr>
           ';
                          $cleanedJson = preg_replace('/[\x00-\x1F\x7F]/', '', $row["productList"]); 
                $productArray = json_decode($cleanedJson, true);
                
                
                  
                foreach ($productArray as $product)
                {
                         
            $html.='  <tr>
                <td style="text-align: left;  color: blue;font-size:7px;font-weight: bold;">'.$product['product_name'].' - '.$product['spes'].' ('.$product['grade'].')</td>
                <td style="text-align: center;color: blue;font-size:7px;font-weight: bold;">'.$product['hsCode'].'</td>
                <td style="text-align: center;color: blue;font-size:7px;font-weight: bold;">'.$product['packing'].'</td>
                <td style="text-align: center;color: blue;font-size:7px;font-weight: bold;">'.$product['qty'].'</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$product['rate'].'</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$product['qty'] * $product['rate'].' </td>
              </tr>';
              
                }
                
    if($row['marketType'] == 'DOMESTIC'){      
                
    $html.='
            <tr>
                <td style="text-align: right;font-size:7px;font-weight: bold;" colspan="4">Discount</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['discountPer'].'</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['discAmt'].'</td>
            </tr>
            <tr>
                <td style="text-align: right;font-size:7px;font-weight: bold;" colspan="5">SUBTOTAL</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['subTotalAfterDisc'].'</td>
            </tr>
            <tr>
                <td style="text-align: right;  font-size:7px;font-weight: bold;" colspan="4">Insurance</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['insurancePer'].'</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['insuranceAmt'].'</td>
            </tr>
            <tr>
                <td style="text-align: right;font-size:7px;font-weight: bold;" colspan="5">SUBTOTAL</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['subTotalAfterInsurance'].'</td>
            </tr>
            <tr>
                <td style="text-align: right; font-size:7px;font-weight: bold;" colspan="4">GST</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['gstPer'].'</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['gstAmt'].'</td>
            </tr>
            <tr>
                <td style="text-align: right;font-size:7px;font-weight: bold;" colspan="4">Freight</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;"></td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['freightAmt'].'</td>
            </tr>
            <tr>
                <td style="text-align: right;font-size:7px;font-weight: bold;" colspan="4">TCS</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['tcsPer'].'</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['tcsAmt'].'</td>
            </tr>';
            
    }
        $html.='    <tr>
                <td style="text-align: right;font-size:7px;font-weight: bold;" colspan="5">TOTAL</td>
                <td style="text-align: right;color: blue;font-size:7px;font-weight: bold;">'.$row['finalAmt'].'</td>
            </tr>
    
    
    
    
        </table>
        
        
        
        <table border="1" cellpadding="2">
              <tr>
                <td style="text-align: left;font-size:7px;font-weight: bold;">OTHER INSTRUCTIONS</td>
              </tr>
              <tr>
                <td style="text-align: left;font-size:7px;font-weight: bold;">
                    <ul>';
            
            
                $termList = $row['otherInstructionData'];
                $termArray = json_decode($termList, true);
                foreach ($termArray as $term)
                {
                         
                   $html.='<li style="text-align: left;font-size:7px;font-weight: bold;">'.$term['otherInstruction'].'</li> ';
              
                }
        
            $html.='</ul>
                <br><br>
                </td>
              </tr>
              <tr> ';
              
              
              
                    if($row['marketType'] == 'DOMESTIC'){
                        
                                            $html.='
                    <td style="text-align: center;" >
                    
                        <table border="1" cellpadding="2" style="width:70%;">
                            <tr>
                                <td style="text-align: left;font-size:7px;font-weight: bold;">Beneficiary Name</td>
                                <td style="text-align: left;font-size:7px;font-weight: bold;color:blue;">Meha Pharma Pvt. Ltd</td>
                            </tr>
                            <tr>
                                <td style="text-align: left;font-size:7px;font-weight: bold;">Name Of Bank & Branch </td>
                                <td style="text-align: left;font-size:7px;font-weight: bold;color:blue;">Indian Overseas Bank, Goregaon – West, Mumbai - 90 </td>
                            </tr>
                            <tr>
                                <td style="text-align: left;font-size:7px;font-weight: bold;">Bank Accocunt No. </td>
                                <td style="text-align: left;font-size:7px;font-weight: bold;color:blue;">207033000000083</td>
                            </tr>
                            <tr>
                                <td style="text-align: left;font-size:7px;font-weight: bold;">Bank IFSC Code </td>
                                <td style="text-align: left;font-size:7px;font-weight: bold;color:blue;">IOBA0002070 </td>
                            </tr>
                            <tr>
                                <td style="text-align: left;font-size:7px;font-weight: bold;">MICR Code</td>
                                <td style="text-align: left;font-size:7px;font-weight: bold;color:blue;">400020054</td>
                            </tr>
                        </table>
                            
                    </td> ';
                        
                    }else{
                                            $html.='
                    <td style="text-align: center;" >
                    
                        <table  cellpadding="1"  >
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">Account Number : <span style="color: blue;">207033000000083</span></td>
                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">Swift Code : <span style="color: blue;">IOBAINBB209</span></td>
                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">Bank Branch : <span style="color: blue;">Goregaon West, Mumbai, India</span></td>
                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">To be routed through</td>
                                                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">WELLS FARGO BANK N.A.</td>                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">CHIPS ABA: <span style="color: blue;">0509</span>, FED ROUTING NUMBER:  <span style="color: blue;">026005092</span></td>                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">FOR CREDIT TO ACCOUNT NUMBER:  <span style="color: blue;">2000191061710 OF INDIAN OVERSEAS BANK.</span> ,  Swift Code:  <span style="color: blue;">IOBAINBB209</span></td>                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">FOR FINAL CREDIT TO ACCOUNT NO: <span style="color: blue;">207033000000083</span></td>                            </tr>
                            <tr>
                                <td style="text-align: center;font-size:7px;font-weight: bold;">OF MEHA PHARMA PRIVATE LIMITED</td>
                            </tr>
                        
                       
                        </table>
                            
                    </td> ';
                    }
              
              
              
              
 
              
              
              
              
              
              
             $html.='  </tr>
              
              <tr>
                <td style="text-align: center;font-size:7px;">THIS DOCUMENT IS VALID WITHOUT SIGNATURE IF SENT FROM SALES@MEHAPHARMA.COM OR MLOGISTICS@MEHAPHARMA.COM</td>
              </tr>
        </table>
             
        ';
        
        
        
        
            }
        }
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadQuotation.pdf', 'I');
        
    }
    
    else if ($_GET["type"] == "downloadQuotations") {
        $_GET['filename'] = 'Quotation Details'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      $sql = "SELECT q.*, c.TrdNm ,c.address ,c.state ,c.city ,c.pincode ,c.country FROM client_quotation q LEFT JOIN client c ON q.client_code=c.client_code  where q.status = 'Approved'";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = json_decode($row["materials"]);
                 $selectedBranch = json_decode($row["selectedBranch"]);
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
                <td style="width:80%; text-align:left;"> '.$row['TrdNm'].'</td>
            </tr>
            <tr>
                <td style="width:20%; text-align:left;"><b>Client Name</b></td>
                <td style="width:80%; text-align:left;"> '.$row['client_code'].'</td>
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
            
            $materials = $row["materials"];
                    $rowspan = 5 - count($materials);
                    for ($i = 0; $i < count($materials); $i++) {
                        $material = $materials[$i];
                        
                        $couter = $i + 1;
                        $html.='
                            <tr>
                                 <td>'.$material->product_name.' ( '.$material->product_code.' )</td>
                                <td>'.$material->rate.'</td>
                                <td>'.$material->qty.'</td>
                                <td>'.$material->quotation_per.'</td>
                                <td>'.$material->pack_qty.'</td>
                                <td>'.$material->moq.'</td>
                                <td>'.$material->batch_size.'</td>
                            </tr>';
                    }
            
            
        	$html.=' 
       
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
                      <td style="text-align: center; ">' . $values['peramt'] . ' % </td>
                      <td style="text-align: left; ">' . $values['term'] . ' </td>
                      <td style="text-align: center; ">' . $values['amt'] . ' ' . $values['curr'] . '</td>
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
    
}

$conn->close();
?>