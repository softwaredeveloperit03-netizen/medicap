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
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="getStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";
    	//echo $sql;
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if ($_GET["type"]=="getAllStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }if ($_GET["type"]=="getApprovedStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.status='Approved'ORDER BY id DESC";
    	$result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT material_code, grade FROM material WHERE material_name='".$row["material_name"]."' AND material_code!='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["grades"] = $output1;
                $output[] = $row;
            }
        }
    	echo json_encode($output);
    }
   
   
      if ($_GET["type"] == "downloadPOReport")
    {
        $_GET['filename'] = '';
         $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
       
        $html = "";
         $html .= '
        
         <h3 style="text-align:center;"><b><u>PURCHES ORDER</b></u></h3>
        <table>
       <tr>
       <td style="width:100%;">NEPL/PUR/PO/2022-23/019</td>
       </tr><br>
       <tr>
       <td style="width:30%"> </td>
       </tr>
       <tr>
       <td style="width:50%"></td>
       </tr>
        <tr>
       <td style="width:60%"></td>
       </tr>
         <tr>
       <td style="width:100%;"><b>GST NO. : </b> </td>
       </tr>
       <td style="width:100%;"><b>Tel </b> : </td>
       </tr>
       <td style="width:100%;"><b>Email ID-</b> </td>
       </tr><div></div>
       <tr>
       <td style="width:100%;"><b><u>ATTN :</u></b></td>
       </tr>
       </table><div></div>
       <tr>
       <td style="width:100%">Plese supply us the following on the terms and conditions mentioned here in after:</td>
       </tr>';
         $html .= ' <table cellpadding="5" border="0.1"> 
          
        <tr>
        <td style="width:10%"><b>SR NO.</b></td>
        <td style="width:40%; text-align:center"><b>DESCRIPTION</b></td>
        <td style="width:10%"><b>QTY</b></td>
        <td style="width:20%"><b>RATE(Per kg)</b></td>
        <td style="width:20%"><b>TOTAL AMOUNT</b></td>
        </tr>';
        
   
          $html.='
        <tr>
        <td style="width:10%"></td>
        <td style="width:40%"></td>
        <td style="width:10%"> Kg</td>
        <td style="width:20%"> /</td>
        <td style="width:20%"></td>
        </tr>';
        
           $idx += 1;         
          $html.='<tr>
        <td style="width:10%"></td>
        <td style="width:40%"></td>
        <td style="width:10%"></td>
        <td style="width:20%">GST  %</td>
        <td style="width:20%"></td>
        </tr>
           <tr>
        <td style="width:10%"></td>
        <td style="width:40%"></td>
        <td style="width:10%"></td>
        <td style="width:20%">TOTAL AMOUNT</td>
        <td style="width:20%"></td>
        </tr>';
              
         $html.=' </table><div></div>';
          $html.=' <tr>
        <td style="width:100%;"><b>TOTAL PRICE RS</b></td>
        </tr><div></div>';
      $html.=' <table>
          <tr>
        <td style="width:100%;"><b>Note</b></td>
        </tr>';
        
                    $html .= ' <tr>
                        <td style="width:10%; text-align:center"></td>
                        <td style="width:60%;text-align:left;"></td>
                         </tr>';
                
                
                $html.=' <div></div>
         <tr>
         <td style="width:100%;"><b>PYMENT :</b> 100% Advance</td>
        </tr>
        <tr>
         <td style="width:100%;"><b>Transportation charges extra applicable(Door Delivery).</b></td>
        </tr>
        </table>
             <br pagebreak="true"/>
       
         <h4 ><b><u>TERMS AND CONDITION</b></u></h4>
         <tr>
         <td style="width:100%;">PLEASE MAKE TAX INVOICE AS UNDER:</td>
         </tr><div></div>
         <tr>
         <td style="width:100%;"><b> (BUYER)</b></td>
         </tr><div></div>
         <tr>
         <td style="width:100%;"><b>ADDRESS :</b>   </td>
         </tr>
         <tr>
         <td style="width:40%; text-align:center"><b>GST PROVE ID:</b> </td>
         </tr><div></div>
         <tr>
         <td style="width:100%;"><b>DRUG LICENCE NO. : </b> MH/102937</td>
         </tr><div></div>
         <tr>
         <td style="width:100%;"><b> Delivery :</b> </td>
         </tr><div></div>
         <tr>
          <td style="width:100%;"><b>ADDRESS :</b></td>
         </tr>
         <div></div>
          <tr>
         <td style="width:40%; text-align:center"><b>GST PROVE ID:</b> </td>
         </tr><div></div>';
            
           $html.='<tr>
         <td style="width:100%;">For NOVOEXCIPIENTS PVT.LTD.</td>
         </tr><div></div><div></div>
         <tr>
       <td style="width:100%;"><b>AUTHORIZED SIGNATORY</b></td>
         </tr>
         <hr><hr>
          <h4 ><b><u>TERMS AND CONDITIONS:</b></u></h4>
         <tr>
         <td style="width:100%;">1. We reserve the right to cancel the Purches Order if the terms are not as per our requirement.</td>
         </tr>
          <tr>
         <td style="width:100%;">2. It shall be as per specified specification failing which, the same shall be returned to you at your cost.</td>
         </tr>';
           
         $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report_new.pdf', 'I');
                
       }         
             
   else if ($_GET["type"] == "downloadPO2Report")
    {
        $_GET['filename'] = '';
         $_GET['filename'] = ' '; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
       
        $html = "";
          $html .= '
    
    <table border="1" cellpadding="2">
                                 <tr style="background-color:black; color:white;">
                                    <td style="width:60%;"><b>Supplier Details</b></td> 
                                    <td style="width:20%;"><b>Vendor Type</b></td> 
                                    <td style="width:20%;"><b>' . $row[''] . '</b></td> 
                                    
                                </tr>
                                <tr>
                                    <td style="width:20%;"><b>Vendor Name:</b></td>
                                    <td style="width:80%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;"><b>Address:</b></td>
                                    <td style="width:40%;">' . $row[''] . '</td>
                                    <td style="width:20%;" text-align:right;><b>GSTIN</b></td>
                                    <td style="width:20%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;"><b>State:</b></td>
                                     <td style="width:80%;">' . $row[''] . '</td>
                                </tr>
                                
                            </table>  <div></div>
                
                            <table border="1" cellpadding="2">
                    <tr style="background-color:black; color:white;">
                        <td style="width:33%;text-align:center;"><b>Bill To</b></td>
                        <td style="width:33%;text-align:center;"><b>SHIP TO</b></td>
                        <td style="width:34%;text-align:center;"><b>P.O NUMBER</b></td>
                    </tr>
                    <tr>
                        <td style="width:33%;">
                            <table>
                                <tr>
                                    <td style="width:100%;"><b>Name Of Company:</b></td>
                                    
                                </tr>
                                 <tr>
                                    <td style="width:100%;">' . $row[''] . '</td>
                                </tr>
                                <tr style="margin-top: 100px">
                                    <td style="width:40%;"><b>Address:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr style="margin-top: 10px">
                                    <td style="width:40%;"><b>Email:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Mobile No:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>GST No</b></td>
                                    <td style="width:60%;">' . $row['gst_no'] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Location:</b></td>
                                     <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>State:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Pin:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:34%;">
                            <table>
                                <tr>
                                    <td style="width:100%;"><b>Name Of Company:</b></td>
                                </tr>
                                 <tr>
                                    <td style="width:100%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Address:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Email:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Mobile No:</b></td>
                                    <td style="width:60%;">' . $row['mobile_no1'] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>GST No:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Location:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                 <tr>
                                    <td style="width:40%;"><b>State:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Pin:</b></td>
                                    <td style="width:60%;">' . $row[''] . '</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:33%;">
                            <table>
                               <br> <tr>
                                    <td style="width:30%;font-weight:bold;">PO No:</td>
                                    <td style="width:70%;">' . $row[''] . '</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">PO Type:</td>
                                    <td style="width:70%;">' . $row[''] . '</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">PO Date:</td>
                                    <td style="width:70%;"></td>
                                </tr><br>
                                <tr>
                                    <td style="width:100%;font-weight:bold;">Transport:</td>
                                </tr>
                                 <tr>
                                    
                                    <td style="width:100%;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div></div>  ';
                $html .= '<table border="1" cellpadding="2">
                                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                                    <td style="width:10%;">Sr. No.</td>';
               
                    $html .= '<td style="width:28%;"  >Material Name</td>
                                             <td style="width:8%; text-align: center;">Qty</td>
                                             <td style="width:8%; text-align: center">Unit</td>
                                             <td style="width:8%; text-align: center">Rate  </td>
                                             <td style="width:8%; text-align: center">GST(%)</td>
                                             <td style="width:10%; text-align: center">Taxable Amt </td>
                                             <td style="width:10%; text-align: center">Tax Amt </td>
                                              <td style="width:10%; text-align: center">Total </td>
                                         </tr>
    
                                     <tr>
                                      <td style="width:10%;text-align: center;"></td>
                                      <td style="width:28%;" ></td>
                                      <td style="width:8%;text-align: right;" ></td>
                                                  <td style="width:8%;text-align: center;"></td>
                                                  <td style="width:8%;text-align: right;"></td>
                                                  <td style="width:8%;text-align: right;"></td>
                                                  <td style="width:10%;text-align: right;"></td>
                                                  <td style="width:10%;text-align: right;"></td>
                                                  <td style="width:10%;text-align: right;"></td>
                                                  </tr>
                                                  <tr>
                                      <td style="width:10%;text-align: center;"> </td>
                                      <td style="width:90%;text-align: left;"> 
                                      </td></tr>
                                     <tr>
                                    <td style="width:85%;text-align:right;font-weight:bold;">SUB Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">' . $row[''] . '</td>
                                </tr>
                                  <tr>
                                    <td style="width:85%;text-align:right;font-weight:bold;">Discount</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">' . $row[''] . '</td>
                                </tr>
                                 <tr>
                                  
                                    <td style="width:85%;text-align:right;font-weight:bold;">Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;"></td>
                                </tr> 
                                
                                   <tr><td style="width:70%;">
                                  
                                  
                      
                          <table><tr>
                    <td style="width:10%;font-weight:bold;">  % : </td>
                        <td style="width:15%;font-weight:bold;"> | </td>
                  </tr></table>
               
                 </td>
                 <td style="width:15%;text-align:right;font-weight:bold;">GST Total</td>
                                    <td style="width:15%;text-align: right;font-weight:bold;">' . $row[''] . '</td>
                                </tr>
                              
                                
                                
                                <tr>
                        <td style="width:85%;text-align:right;font-weight:bold;">Shipping & Handling</td>
                        <td style="width:15%;text-align:right;font-weight:bold;">' . $row[''] . '</td>
                    </tr>
                    <tr>
                         <td style="width:85%;text-align:right;font-weight:bold;">Other</td>
                        <td style="width:15%;text-align:right;font-weight:bold;">' . $row[''] . '</td>
                    </tr>
                                
                                <tr>
                                  <td style="width:70%;text-align:left;font-weight:bold;">Value in words</td>
                                    <td style="width:15%;text-align:right;font-weight:bold;">Round off</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">' . $row[''] . '</td>
                                </tr>
                                <tr>
                                      <td style="width:70%;text-align:left;font-weight:bold;"></td>
                                    <td style="width:15%;text-align:right;font-weight:bold;">Net Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">' . $row[''] . '</td>
                                </tr> 
                        </table>
                        
                 <br pagebreak="true"/> ';


                $html .= '
                
                 <table border="1" cellpadding="2">
                  <tr style="background-color:black; color:white;">
                            <td>    Terms & Conditions </td>
                        </tr>
                        <tr>
                            <td>
                                <ul>
                                    <li>Please send two copies of your invoice.</li>
                                    <li>Enter this order in accordance with the prices, terms, delivery method, and specifications listed above</li>
                                    <li>Please notify us immediately if you are unable to ship as specified.</li>
                                    <li>Send all correspondence to:</li>
                                </ul>
                            </td>
                            
                        </tr> </table><div></div>
                         <table border="1" cellpadding="2">
                    <tr style="background-color:black; color:white;">
                        <td style="width:10%;text-align:center;"><b>Sr.No</b></td>
                        <td style="width:30%;text-align:left;"><b>Term Heading</b></td>
                        <td style="width:60%;text-align:left;"><b>Terms</b></td>
                    </tr> 

               
                  <tr>
                        <td style="width:10%; text-align:center"></td>
                        <td style="width:30%;text-align:left;"></td>
                        <td style="width:60%;text-align:left;"></td>
                    </tr>
                

                </table>
              <br><br><table border="1" cellpadding="3">
                    <tr style="background-color:black; color:white;">
                        <td style="width:50%;text-align:center;"><b>Checked By & Digital Signed By</b></td>
                        <td style="width:50%;text-align:center;"><b>Approved By & Digital Signed By</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;" >
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:' . $row[''] . '</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:' . $row[''] . '</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:' . $row[''] . '</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                                <td style="width:100%;text-align:center">Regd. Office :Plot No: A2/8, 1St Phase, G.I.D.C, Vapi, Dist. Valsad - 396195, CIN No.U99999GJ1971PTC109282</td>
                                </tr>
                </table> ';
                
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report_new.pdf', 'I');
    
               $conn->close();
    }
    
?>
