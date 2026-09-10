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
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
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
    
 if($_GET["type"]=="makePayment") {
      
        
        
        $sql = "INSERT INTO invoice_entry_history( pay_mode, invoice_no, po_no, received_amt, transaction_no, vendor_no,entry_by) 
        VALUES ('".$input["paymentm_ode"]."','".$input["invoice_no"]."','".$input["po_no"]."',
        '".$input["amount"]."','".$input["transaction_no"]."','".$input["vendor_no"]."','".$_GET["emp_id"]."' )";
    
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
            
            
            $sql1= "select * from invoice_entry_history where invoice_no = '".$input["invoice_no"]."' AND 
            po_no = '".$input["po_no"]."'";
            $result1 = $conn->query($sql1);
             if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {

                $amount = $amount + $row1['received_amt'];
             }
            }
            
            $currentDateTime = new DateTime('now');
            $currentDate = $currentDateTime->format('d-m-Y');
                        
            $sql2= "update invoice_entry set payment_recieved = '".$input["amount"]."' , payment_recived_dt =
            '$currentDate' , invoice_balance = '$amount' where invoice_no = '".$input["invoice_no"]."' AND po_no = '".$input["po_no"]."'";
            $result2 = $conn->query($sql2);
          
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

 }
 else if($_GET["type"]=="makeSalesPayment") {
      
        
        
        $sql = "INSERT INTO voucherentry( plant_id, client_code,particular,credit_type,credit,entry_by, entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["client_code"]."','".$input["Invoice_no"]."','Cr','".$input["amount"]."','".$_GET["emp_id"]."','$entry_date' )";
    
            
            
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
            
            $balance_amt=0;
            $balance_amt = $input["balance_amt"]  - $input["amount"];    
            
            
            
            $sql2= "update  sales set balance_amt = '$balance_amt'  where id = '".$input["id"]."' ";
            
            $result2 = $conn->query($sql2);
            
            
            
            
          
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

 }
 else if ($_GET["type"] == "getDuePayments") {
        $output = Array();
        
        // $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Loan' ORDER BY id DESC";
        $sql = "SELECT * FROM sales WHERE pay_mode='due'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    
    
   }
   
    else if ($_GET["type"] == "getavaliablestockfinished") {
        	$output = Array();
     	  // $sql = "SELECT IFNULL(SUM(qty), 0) as qty1  FROM fg_stock_book  where material_code ='".$_GET["product_code"]."'";
     	    $sql = "SELECT *   FROM fg_stock_book  where material_code ='".$_GET["product_code"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		           //$sql1 = "SELECT IFNULL(SUM(qty), 0) as qty2  FROM fg_material_issue  where material_code ='".$_GET["product_code"]."'";
    		           $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty2  FROM fg_material_issue  where batch_no ='".$row["batch_no"]."'";
                    	$result1 = $conn->query($sql1);
                    	if($result1->num_rows > 0){
                    		while($row1 = $result1->fetch_assoc()){
                    		    $row['qty2'] =  $row1['qty2'];
                    		}
                    	}
    		    $avl_qty = $row['qty'] - $row['qty2'];
    		    $avl_qty_formatted = number_format($avl_qty, 2);
    		    $row['avl_qty'] = $avl_qty_formatted;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }

   
   
   
   else if ($_GET["type"] == "getVendors") {
        $output = Array();
        
        // $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Loan' ORDER BY id DESC";
         $sql = "SELECT v.*, i.id as i_id FROM invoice_entry i LEFT join purchaseorder c on i.po_no=c.po_no left join
        vendor v on c.vendor_no = v.vendor_no where i.plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    
    
   }
   else if ($_GET["type"] == "gettaxInvoices") {
        $output = Array();
        
            $sql = "SELECT i.*,v.vendor_no,v.vendor_name FROM invoice_entry i LEFT join purchaseorder  c on i.po_no=c.po_no left join
        vendor v on c.vendor_no = v.vendor_no where c.vendor_no = '".$_GET["vendor_no"]."' AND i.plant_id = '".$_GET["plant_id"]."'";
        
      
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                 $output1 = Array();
                        $sql1 = "SELECT * FROM invoice_entry_history 
                        WHERE invoice_no = '".$row["invoice_no"]."' and po_no='".$row["po_no"]."' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        
                
                
                $row["history"] = $output1;

                $output[] = $row;
            }
        }
         echo json_encode($output);
    
    
    
   
   }
   else if ($_GET["type"] == "getSelestaxInvoices") {
        $output = Array();
        
         $sql = "SELECT i.*, c.client_code , c.TrdNm FROM sales i LEFT join  client  c on i.client_code=c.client_code where c.client_code = '".$_GET["client_code"]."' 
        AND i.plant_id = '".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
         echo json_encode($output);
   
   }
   else if ($_GET["type"] == "gettaxInvoices1") {
        $output = Array();
        
        // $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Loan' ORDER BY id DESC";
        $sql = "SELECT i.*,v.vendor_no,v.vendor_name FROM invoice_entry i LEFT join challan c on i.po_no=c.po_no left join
        vendor v on c.vendor_no = v.vendor_no where v.vendor_no = '".$_GET["vendor_no"]."'";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    
    
   
   }
   else if ($_GET["type"] == "gettaxInvoices2611") {
        $output = Array();
        
           $sql = "SELECT i.*,v.vendor_no,v.vendor_name FROM purchaseorder p LEFT join invoice_entry i on i.po_no = p.po_no left join
        vendor v on p.vendor_no = v.vendor_no where v.vendor_no = '".$_GET["vendor_no"]."' AND i.plant_id = '".$_GET["plant_id"]."' ";
        
        $closing = 0;
      
         $i=0;
         
         $result = $conn->query($sql);
         
          if ($result->num_rows > 0) {
              
          while ($row = $result->fetch_assoc()) {
              
            $closing =  $closing + $row['net_amt'];
            $row['closing'] = $closing;
            $row['po_no1'] = "Purchase Order ".$row['po_no'];
            
            
            $row['received_amt'] = '';
             
            $output[$i] =   $row;
            
            $i++;
  
        $sql1 = "SELECT * FROM invoice_entry_history WHERE invoice_no = '".$row["invoice_no"]."' and po_no='".$row["po_no"]."' ";
                        
        $result1 = $conn->query($sql1);
        
        if ($result1->num_rows > 0) {
            
            while ($row1 = $result1->fetch_assoc()) {
                 $closing =  $closing - $row1['received_amt'];
                 $row1['closing'] = $closing;
                 $row1['invoice_date'] = $row1['entry_date'];
                 $row1['po_no'] = '';
                
                $row1['net_amt'] = '';
                
                 $output[$i] =   $row1;
                 
                 $i++;
                 
             }
             
        }  
        
        
        
        
        
        
        
          }
          }
        
        
        
        
        
        
        
        echo json_encode($output);
        
   }
    else if ($_GET["type"] == "getAwaitingGRNRawLabels") {
         
      $output = Array();
      
         $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name, 
        DATE(c.receiving_date) as receiving_date FROM challan_materials c LEFT JOIN material m 
        ON c.material_code=m.material_code  WHERE 
        m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $batches = json_decode($row["batches"]);
                // for ($i = 0; $i < count($batches); $i++) {
                //     $batch = $batches[$i];
                //     $batch->id = $row["id"];
                //     $batch->inward_no = $row["inward_no"];
                //     $batch->material_name = $row["material_name"];
                //     $batch->material_code = $row["material_code"];
                //     $batch->grade = $row["grade"];
                //     $batch->manufacturer = $row["manufacturer"];
                //     $batch->vendor_no = $row["vendor_no"];
                //     $batch->receiving_date = $row["receiving_date"];
                //   echo  $sql1 = "SELECT FROM label WHERE label_type='GRN' AND material_code='".$row["material_code"]."'  AND batch_no='".$batch->batch_no."'";
                //     $result1 = $conn->query($sql1);
                //     if ($result1->num_rows == 0) {
                //         $output[] = $batch;
                //     }
                // }
                
                                        $output[] = $row;

            }
        }
        echo json_encode($output);
         
         
        
        
        
     
    }
   else if ($_GET["type"] == "gettaxInvoicesHistory") {
        $output = Array();
    
                         $sql1 = "SELECT * FROM invoice_entry_history WHERE 
                        vendor_no = '".$_GET["vendor_no"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output[] = $row1;
                            }
                        }
                        

        echo json_encode($output);
    
    
    
   }
   else if ($_GET["type"] == "gettaxInHistory") {
      $output = Array();
    
                           $sql1 = "SELECT h.*,e.invoice_date FROM invoice_entry_history h left join invoice_entry e ON h.po_no = e.po_no WHERE 
                        h.invoice_no = '".$_GET["ino"]."' AND e.plant_id = '".$_GET["plant_id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output[] = $row1;
                            }
                        }
                        

        echo json_encode($output);
    
    
    
   }
   else     if ($_GET["type"] == "salesReportPDF") {
        $_GET['filename'] = 'Loan Details'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <thead>
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:15%; text-align:centre;"><b>Client name</b></td>
            <td style="width:15%; text-align:centre;"><b>Client code</b></td>
            <td style="width:15%; text-align:centre;"><b>Branch</b></td>
            <td style="width:15%; text-align:centre;"><b>Order No</b></td>
            <td style="width:15%; text-align:centre;"><b>Payment Mode</b></td>
            <td style="width:15%; text-align:centre;"><b>Status</b></td>
        </tr>
        </thead>
        <tbody>';
        $i=1;
        $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Loan' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    $html.='<tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:15%;">'.$row['client_name'].'</td>
            <td style="width:15%;">'.$row['client_code'].'</td>
            <td style="width:15%;">'.$row['branch'].'</td>
            <td style="width:15%;">'.$row['order_no'].'</td>
            <td style="width:15%;">'.$row['payment_mode'].'</td>
            <td style="width:15%;">'.$row['status'].'</td>
        </tr>
        </tbody>';
        $i++;
            }
        }
    $html.='</table>';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salesReportPDF.pdf', 'I');
        
    }else if ($_GET["type"] == "downloadorders") {
        $_GET['filename'] = 'Tax Invoice'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <thead>
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:20%; text-align:centre;"><b>Division Name</b></td>
            <td style="width:20%; text-align:centre;"><b>Contact</b></td>
            <td style="width:20%; text-align:centre;"><b>Email</b></td>
            <td style="width:15%; text-align:centre;"><b>state Code</b></td>
            <td style="width:15%; text-align:centre;"><b>Status</b></td>
        </tr>
        </thead>
        <tbody>';
        $html.='<tr>
            <td style="width:10%;"></td>
            <td style="width:20%;"></td>
            <td style="width:20%;"></td>
            <td style="width:20%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
        </tr>
        </tbody>';
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadorders.pdf', 'I');
        
    }else if ($_GET["type"] == "downloadSalesOrder") {
        $_GET['filename'] = 'Tax Invoice'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
        <thead>
            <tr>
                <td style="width:5%;"><b>Sr.</b></td>
                <td style="width:15%;"><b>Invoice No.</b></td>
                <td style="width:15%;"><b>Invoice Date</b></td>
                <td style="width:10%;"><b>PO NO.</b></td>
                <td style="width:15%;"><b>Client name</b></td>
                <td style="width:10%;"><b>Client code</b></td>
                <td style="width:15%;"><b>Branch</b></td>
                <td style="width:15%;"><b>Payment Mode</b></td>
            </tr>
        </thead>
        <tbody>';
        $html.='
        <tr>
            <td style="width:5%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
        </tr>
        </tbody>';
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadorders.pdf', 'I');
        
    }
    
    
    
    
    
}

$conn->close();
?>