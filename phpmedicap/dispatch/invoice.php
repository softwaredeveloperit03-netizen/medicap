<?php
require '../db.php';
//require_once  './phpqrcode/qrlib.php';
    require '../tcpdf/tcpdf.php';

// ini_set('display_errors', 1);
//  error_reporting(E_ALL);

require '../token.php';
require 'dispatch_serial.php';
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
    
    if ($_GET["type"] == "getPendingInvoices") {
        
        $output = array();
        dispatch_ensure_sales_challan_column($conn);
        $sql = "SELECT s.*,c.LglNm as clientName FROM sales s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."' AND s.status = 'Approved' AND s.invoice = 'Pending' ORDER BY s.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $output2 = array();  
            $sql1 = "SELECT * FROM sales_product  WHERE plant_id = '".$_GET["plant_id"]."' AND orderId = '".$row["id"]."' ";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output2[] = $row1;
                }
            }
                $row["products"] = $output2;
                $row = dispatch_ensure_sales_dispatch_numbers($conn, $_GET["plant_id"], $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } else if ($_GET["type"] == "getSalesOrderForInvoice") {
        dispatch_ensure_sales_challan_column($conn);
        $salesIdEsc = $conn->real_escape_string(trim((string)($_GET['id'] ?? '')));
        $output = array();
        $sql = "SELECT s.*, c.LglNm AS clientName FROM sales s
            LEFT JOIN client c ON s.client_code = c.client_code
            WHERE s.id = '$salesIdEsc' AND s.plant_id = '".$_GET["plant_id"]."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $output2 = array();
            $sql1 = "SELECT * FROM sales_product WHERE plant_id = '".$_GET["plant_id"]."' AND orderId = '".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1 && $result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output2[] = $row1;
                }
            }
            $row['products'] = $output2;
            $row = dispatch_ensure_sales_dispatch_numbers($conn, $_GET["plant_id"], $row);
            $output = $row;
        } else {
            $output = array('status' => 'failed', 'msg' => 'Sales order not found.');
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($output);
        exit;
        
    } else if ($_GET["type"] == "saveInvoice") {

        $eWayBillNo = trim((string)($input["eWayBillNo"] ?? ''));
        if (!preg_match('/^[0-9]{12}$/', $eWayBillNo)) {
            echo json_encode(array("status" => "failed", "msg" => "E-Way Bill No must be exactly 12 digits."));
            exit;
        }

        dispatch_ensure_sales_challan_column($conn);
        $plantId = $_GET['plant_id'];
        $salesIdEsc = $conn->real_escape_string(trim((string)($input['id'] ?? '')));
        $salesChallan = '';
        $salesInvoice = '';
        $salesInvoiceStatus = '';
        if ($salesIdEsc !== '') {
            $salesRes = $conn->query("SELECT challan_no, Invoice_no, invoice FROM sales WHERE id = '$salesIdEsc' LIMIT 1");
            if ($salesRes && $salesRes->num_rows > 0) {
                $salesRow = $salesRes->fetch_assoc();
                $salesChallan = trim((string)($salesRow['challan_no'] ?? ''));
                $salesInvoice = trim((string)($salesRow['Invoice_no'] ?? ''));
                $salesInvoiceStatus = trim((string)($salesRow['invoice'] ?? ''));
            }
        }

        if ($salesInvoiceStatus === 'Done') {
            echo json_encode(array('status' => 'failed', 'msg' => 'Invoice already created for this sales order.'));
            exit;
        }

        $invoiceFromChallan = dispatch_invoice_from_challan($salesChallan);
        if ($invoiceFromChallan !== '') {
            $input['Invoice_no'] = $invoiceFromChallan;
        } elseif ($salesInvoice !== '') {
            $input['Invoice_no'] = $salesInvoice;
        } else {
            $nextNums = dispatch_find_next_available_numbers($conn, $plantId);
            $input['Invoice_no'] = $nextNums['invoice_no'];
            if ($salesChallan === '' && $salesIdEsc !== '') {
                $challanNoEsc = $conn->real_escape_string($nextNums['challan_no']);
                $conn->query("UPDATE sales SET challan_no = '$challanNoEsc', Invoice_no = '".$conn->real_escape_string($nextNums['invoice_no'])."' WHERE id = '$salesIdEsc'");
            }
        }

        $invoiceNoEsc = $conn->real_escape_string(trim((string)$input['Invoice_no']));
        if (dispatch_invoice_exists($conn, $plantId, $invoiceNoEsc, (int)($input['id'] ?? 0))) {
            echo json_encode(array('status' => 'failed', 'msg' => 'Invoice number ' . $input['Invoice_no'] . ' already exists.'));
            exit;
        }
        if ($salesChallan !== '' && dispatch_challan_exists($conn, $plantId, $salesChallan, (int)($input['id'] ?? 0))) {
            echo json_encode(array('status' => 'failed', 'msg' => 'Challan number conflict detected.'));
            exit;
        }
          
         $sql = "INSERT INTO `tax_invoice`(`plant_id`, `client_code`, `gst_type`, `orderNo`, `orderDate`, `Invoice_no`, `Invoice_date`, `transporter`, 
        `creditDays`, `vehicleNo`, `transReceipt`, `dueDate`, `eWayBillNo`, `broker`, `agent`, `banker`, `dispatch`, `BillTo`, `BillToAddress`,
        `BillToGSTIN`, `BillToCity`, `BillToState`, `BillToPincode`, `BillToDlNo`, `BillToEmail`, `BillToScode`, `BillToPanNo`, `ShipTo`, 
        `ShipToAddress`, `ShipToGSTIN`, `ShipToCity`, `ShipToState`, `ShipToPincode`, `ShipToDlNo`, `ShipToEmail`, `ShipToScode`, `ShipToPanNo`, 
        `taxableTotal`, `taxAmtTotal`, `netTotal`, `igstTotal`, `cgstTotal`, `sgstTotal`, `status`, `entry_by`, `entry_date`,`salesId`) VALUES 
        ('".$_GET["plant_id"]."','".$input["client_code"]."', '".$input["gst_type"]."', '".$input["orderNo"]."','".$input["orderDate"]."', 
        '".$invoiceNoEsc."', '".$input["Invoice_date"]."', '".$input["transporter"]."', '".$input["creditDays"]."','".$input["vehicleNo"]."', 
        '".$input["transReceipt"]."', '".$input["dueDate"]."', '".$conn->real_escape_string($eWayBillNo)."', '".$input["broker"]."','".$input["agent"]."', 
        '".$input["banker"]."', '".$input["dispatch"]."', '".$input["BillTo"]."', '".$input["BillToAddress"]."','".$input["BillToGSTIN"]."',
        '".$input["BillToCity"]."', '".$input["BillToState"]."', '".$input["BillToPincode"]."', '".$input["BillToDlNo"]."','".$input["BillToEmail"]."', 
        '".$input["BillToScode"]."', '".$input["BillToPanNo"]."', '".$input["ShipTo"]."','".$input["ShipToAddress"]."','".$input["ShipToGSTIN"]."', 
        '".$input["ShipToCity"]."', '".$input["ShipToState"]."', '".$input["ShipToPincode"]."', '".$input["ShipToDlNo"]."','".$input["ShipToEmail"]."', 
        '".$input["ShipToScode"]."', '".$input["ShipToPanNo"]."','".$input["taxableTotal"]."','".$input["taxAmtTotal"]."', '".$input["netTotal"]."', 
        '".$input["igstTotal"]."', '".$input["cgstTotal"]."','".$input["sgstTotal"]."', 
        'Pending','".$_GET["emp_id"]."', '$entry_date','".$input["id"]."')";
        
        if ($conn->query($sql)) {

            $last_id = $conn->insert_id;  
 
            $array = $input["products"];
            //$array = json_decode($json_obj, true);
            foreach ($array as $values)
            {
                
                $sql1 = "INSERT INTO `tax_invoice_product`(`plant_id`, `invoiceId`, `product_code`, `product_name`, `hsn`, `batch_no`, `ar_no`, `mfg_date`, 
                `exp_date`, `pack_size`, `requiredQty`, `unit`, `rate`, `gst`, `taxable`, `taxAmt`, `netAmt`, `igst`, `cgst`, `sgst`,`spId`) VALUES (
                '".$_GET["plant_id"]."', '$last_id', '".$values["product_code"]."','".$values["product_name"]."', '".$values["hsn"]."', 
                '".$values["batch_no"]."','".$values["ar_no"]."', '".$values["mfg_date"]."', '".$values["exp_date"]."','".$values["pack_size"]."', 
                '".$values["requiredQty"]."', '".$values["unit"]."','".$values["rate"]."', '".$values["gst"]."', '".$values["taxable"]."',
                '".$values["taxAmt"]."', '".$values["netAmt"]."', '".$values["igst"]."','".$values["cgst"]."','".$values["sgst"]."','".$values["id"]."')";
           
                $conn->query($sql1);
                
            }
             
            echo json_encode(array('status' => 'success', 'invoice_no' => $input['Invoice_no'], 'challan_no' => $salesChallan));
            
            $invQuery = "UPDATE sales SET invoice = 'Done', Invoice_no = '".$invoiceNoEsc."' WHERE id = '".$input["id"]."'";
            $conn->query($invQuery);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
    
    
        
    else if ($_GET["type"] == "getPendingInvoicesForApproval") {
        
        $output = array();
        $sql = "SELECT s.*,c.LglNm as clientName FROM tax_invoice s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."' AND s.status = 'Pending' ORDER BY s.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $output2 = array();  
            $sql1 = "SELECT * FROM tax_invoice_product  WHERE plant_id = '".$_GET["plant_id"]."' AND invoiceId = '".$row["id"]."' ";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output2[] = $row1;
                }
            }
                $row["products"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }

    
    else if ($_GET["type"] == "approveInvoice") {
        
        $sql = "UPDATE tax_invoice SET status = '".$_GET["status"]."', approveBy = '".$_GET["emp_id"]."', approve_date = '$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }

    
    
    else if ($_GET["type"] == "getInprocessInvoices") {
        $output = array();
        $sql = "SELECT * FROM tax_invoice WHERE user_no='".$_GET["user_no"]."' AND status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM tax_invoice_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["product_name"] = $row2['product_name'];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                 $row["sales_data"] = json_decode($row["sales_data"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateInvoice") {
        $sql = "UPDATE tax_invoice SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            if ($_GET["status"] == "approve") {
                $id = 0;
                $sql = "SELECT COUNT(id) as id FROM tax_invoice WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    $id = +$row["id"];
                }
                
                $id++;
                if ($count($id) == 1) {
                    $challan_no = "C1000".$id;
                } else if ($count($id) == 2) {
                    $challan_no = "C100".$id;
                } else if ($count($id) == 3) {
                    $challan_no = "C10".$id;
                } else if ($count($id) == 4) {
                    $challan_no = "C1".$id;
                }
                
                $sql = "UPDATE tax_invoice SET challan_no='$challan_no', challan_date='$entry_date' WHERE id='".$_GET["id"]."'";
                $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "getMasterGstInvoice")
    {



    $url = "https://api.mastergst.com/einvoice/type/GENERATE/version/V1_03?email=softwaredeveloperit03@gmail.com";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$headers = array(
	   "accept: */*",
  "ip_address: 103.178.207.63",
  "client_id: f87f9058-7d2c-48e1-9416-d9fffc68149f	",
  "client_secret: 4c173799-0cf0-4685-8158-28ab7d46c7ee",
  "username: mastergst",
  "auth-token: LBOisOzHexwuM3SKcZ9kVSDpB",
  "gstin: 29AABCT1332L000",
  "Content-Type: application/json"
		);
		
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


$data = '{"Version":"1.1","TranDtls":{"TaxSch":"GST","SupTyp":"B2B","EcmGstin":null,"IgstOnIntra":"N"},"DocDtls":{"Typ":"INV","No":"GMP324","Dt": "24/06/2024"},"SellerDtls":{"Gstin":"29AABCT1332L000","LglNm":"ABC company pvt ltd","TrdNm":"NIC Industries","Addr1":"5th block, kuvempu layout","Addr2":"kuvempu layout","Loc":"GANDHINAGAR","Pin":560001,"Stcd":"29","Ph":"9000000000","Em":"abc@gmail.com"},"BuyerDtls":{"Gstin":"29AWGPV7107B1Z1","LglNm":"XYZ company pvt ltd","TrdNm":"XYZ Industries","Pos":"37","Addr1":"7th block, kuvempu layout","Addr2":"kuvempu layout","Loc":"GANDHINAGAR","Pin":560004,"Stcd":"29","Ph":"9000000000","Em":"abc@gmail.com"},"DispDtls":{"Nm":"ABC company pvt ltd","Addr1":"7th block, kuvempu layout","Addr2":"kuvempu layout","Loc":"Banagalore","Pin":518360,"Stcd":"37"},"ShipDtls":{"Gstin":"29AWGPV7107B1Z1","LglNm":"CBE company pvt ltd","TrdNm":"kuvempu layout","Addr1":"7th block, kuvempu layout","Addr2":"kuvempu layout","Loc":"Banagalore","Pin":518360,"Stcd":"37"},"ItemList":[{"SlNo":"1","IsServc":"N","PrdDesc":"Rice","HsnCd":"1001","Barcde":"123456","BchDtls":{"Nm":"123456","Expdt":"01/08/2023","wrDt":"01/09/2023"},"Qty":100.345,"FreeQty":10,"Unit":"NOS","UnitPrice":99.545,"TotAmt":9988.84,"Discount":10,"PreTaxVal":1,"AssAmt":9978.84,"GstRt":12,"SgstAmt":0,"IgstAmt":1197.46,"CgstAmt":0,"CesRt":5,"CesAmt":498.94,"CesNonAdvlAmt":10,"StateCesRt":12,"StateCesAmt":1197.46,"StateCesNonAdvlAmt":5,"OthChrg":10,"TotItemVal":12897.7,"OrdLineRef":"3256","OrgCntry":"AG","PrdSlNo":"12345","AttribDtls":[{"Nm":"Rice","Val":"10000"}]}],"ValDtls":{"AssVal":9978.84,"CgstVal":0,"SgstVal":0,"IgstVal":1197.46,"CesVal":508.94,"StCesVal":1202.46,"Discount":10,"OthChrg":20,"RndOffAmt":0.3,"TotInvVal":12908,"TotInvValFc":12897.7},"PayDtls":{"Nm":"ABCDE","Accdet":"5697389713210","Mode":"Cash","Fininsbr":"SBIN11000","Payterm":"100","Payinstr":"Gift","Crtrn":"test","Dirdr":"test","Crday":100,"Paidamt":10000,"Paymtdue":5000},"RefDtls":{"InvRm":"TEST","DocPerdDtls":{"InvStDt":"02/10/2022","InvEndDt":"02/11/2022"},"PrecDocDtls":[{"InvNo":"INVTWC/101","InvDt":"02/10/2022","OthRefNo":"123456"}],"ContrDtls":[{"RecAdvRefr":"DOC/002","RecAdvDt":"01/10/2022","Tendrefr":"Abc001","Contrrefr":"Co123","Extrefr":"Yo456","Projrefr":"Doc-456","Porefr":"Doc-789","PoRefDt":"01/10/2022"}]},"AddlDocDtls":[{"Url":"https://einv-apisandbox.nic.in","Docs":"Test Doc","Info":"Document Test"}],"ExpDtls":{"ShipBNo":"A-248","ShipBDt":"01/08/2020","Port":"INABG1","RefClm":"N","ForCur":"AED","CntCode":"AE"},"EwbDtls":{"Transid":"12AWGPV7107B1Z1","Transname":"XYZ EXPORTS","Distance":100,"Transdocno":"DOC01","TransdocDt":"01/08/2020","Vehno":"ka123456","Vehtype":"R","TransMode":"1"}}';


curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

//for debug only!
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);
 var_dump($resp);
// $data = json_decode($resp, true);

// echo $data['data']['SignedQRCode'];



// header('Content-Type: image/png');

// QRcode::png($data['data']['SignedQRCode'], 'qrcode.png');




// $url = "https://api.mastergst.com/einvoice/authenticate?email=softwaredeveloperit03@gmail.com";

// 		$curl = curl_init($url);
// 		curl_setopt($curl, CURLOPT_URL, $url);
// 		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
// 		$headers = array(
// 		   "accept: */*",
// 		   "username: mastergst",
// 		   "password: Malli#123",
// 		   "ip_address: 103.178.207.63",
// 		   "client_id: f87f9058-7d2c-48e1-9416-d9fffc68149f",
// 		   "client_secret: 	4c173799-0cf0-4685-8158-28ab7d46c7ee",
// 		   "gstin: 29AABCT1332L000",
// 		);
// 		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
// 		//for debug only!
// 		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
// 		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
// 		$resp = curl_exec($curl);
// 		curl_close($curl);
// 		var_dump($resp);






  


    }
       else if ($_GET['type'] == 'downloadE_InvoiceLog') {
           require './../tcpdf/tcpdf.php';
        $_GET['filename'] = 'E_Invcoie  Log'; $_GET['pdftype'] = 'landscape'; include("./../pdfimp2.php");

       
             $html.='
      
    <table border="1">
    
        <tr style="background-color:gray;">
       
        <td style="text-align:center;width:70px; line-height:30px;">Material Code</td>
        <td style="text-align:center;width:80px; line-height:30px;">Material Name</td>
        <td style="text-align:center;width:60px; line-height:30px;">Department</td>
        <td style="text-align:center;width:60px; line-height:30px;">Indent Qty</td>
        <td style="text-align:center;width:40px; line-height:30px;">Unit</td>
        <td style="text-align:center;width:50px; line-height:30px;">Order Qty</td>
        <td style="text-align:center;width:50px; line-height:30px;">Grade</td>
        <td style="text-align:center;width:80px; line-height:30px;">Quotation Amount</td>
        <td style="text-align:center;width:50px; line-height:30px;">GST %</td>
        <td style="text-align:center;width:60px; line-height:30px;">Gross</td>
        <td style="text-align:center;width:60px; line-height:30px;">GST Amt</td>
        <td style="text-align:center;width:60px; line-height:30px;">Net</td>
        <td style="text-align:center;width:50px; line-height:30px;">Status</td>
        </tr>';
        
        
      $sql="SELECT i.*,m.material_code,m.material_name,g.material_name as gm_material ,m.grade  FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code  left join general_material g ON i.material_code = g.material_code ";
             

                
                                 
                $i=1;
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // echo $row;
                
            $html.='
    <tr> 
       
        <td style="text-align:center;width:70px; line-height:30px;">'.$row['material_code'].' </td>
        <td style="text-align:center;width:80px; line-height:30px;">'.$row['material_name'].'  '.$row['gm_material'].'</td>
        <td style="text-align:center;width:60px; line-height:30px;">'.$row['department'].' </td>
        <td style="text-align:center;width:60px; line-height:30px;">'.$row['req_qty'].' </td>
        <td style="text-align:center;width:40px; line-height:30px;">'.$row['unit'].' </td>
        <td style="text-align:center;width:50px; line-height:30px;">'.$row['order_qty'].' </td>
        <td style="text-align:center;width:50px; line-height:30px;">'.$row['grade'].'</td>
         <td style="text-align:center;width:80px; line-height:30px;"> '.$row['quotation_amt'].'</td>
        <td style="text-align:center;width:50px; line-height:30px;">'.$row['gst'].' </td>
        <td style="text-align:center;width:60px; line-height:30px;">'.$row['gross_total'].'</td>
        <td style="text-align:center;width:60px; line-height:30px;"> '.$row['gst_total'].'</td>
        <td style="text-align:center;width:60px; line-height:30px;">'.$row['net_total'].'</td>
        <td style="text-align:center;width:50px; line-height:30px;">'.$row['status'].'</td>

    </tr>';
    }
}
$html.= '</table>';

                 
       




    $pdf->writeHTML($html, true, false, false, false, '');
      $pdf->Output('E_Invoice.pdf', 'I');
}
    
    else if ($_GET["type"] == "getInvoicesLog") {
        
        $output = array();
        $sql = "SELECT s.*,c.LglNm as clientName FROM tax_invoice s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."'  ORDER BY s.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $output2 = array();  
            $sql1 = "SELECT * FROM tax_invoice_product  WHERE plant_id = '".$_GET["plant_id"]."' AND invoiceId = '".$row["id"]."' ";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output2[] = $row1;
                }
            }
                $row["products"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getinvoiceforacc") {
      
        
         $output = array();
        
           $sql = "SELECT s.*,t.invoice_no, c.c_pincode, c.LglNm,c.c_permanent_state, c.c_address, c.cr_address, c.c_city, c.gst_no, s1.state_code
        FROM sales s LEFT JOIN client c ON s.client_code=c.client_code LEFT JOIN tax_invoice t ON t.order_type=s.order_no  LEFT JOIN state s1 ON
        c.state_code=s1.state_code  ORDER BY s.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                 $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' 
                AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    echo    $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["product_name"] = $row2['product_name'];
                                $row1["product_type"] = $row2['product_type'];
                                $row1["product_code"] = $row2['product_code'];
                            }
                        }
                       
                       $output1[] = $row1;
                    }
                }
                $row["client_name"] ='';
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                 $row["sales_data"] = json_decode($row["sales_data"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else  if ($_GET["type"] == "invoicePDF") { 
        header('Access-Control-Allow-Origin: *');

        if (!class_exists('MYPDFTaxInvoice', false)) {
            class MYPDFTaxInvoice extends TCPDF {
                public function Header() {}
                public function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                    $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
                }
            }
        }

        $pdf = new MYPDFTaxInvoice(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(8, 5, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage('P');

        $id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
        $plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
        
        $plant_name = '';
        $plant_full_name = '';
        $plant_full_address = '';
        $mobNo = '7778885053';
        $email = '';
        $gst_no = '';
        $panNo = '';
        $dlNo = '';
        $plant_state = 'Gujarat';
        $state_code = '24';
        $logoSrc = '';
        $mfg_lic = 'G/25/2552';
        $bank_account_name = 'SHREE HARIKRISHNA PHARMACEUTICALS PVT LTD';
        $bank_ac_no = '8052271706';
        $bank_name = 'KOTAK MAHINDRA BANK';
        $bank_branch = 'BAVLA';
        $bank_ifsc = 'KKBK0002565';
        
        $resolvePlantLogo = function ($logoPath) {
            $candidates = array();
            if ($logoPath !== null && trim((string) $logoPath) !== '') {
                $lp = str_replace('\\', '/', trim((string) $logoPath));
                $base = basename($lp);
                $candidates[] = __DIR__ . '/../logos/' . $base;
                $candidates[] = __DIR__ . '/../logos/' . ltrim($lp, '/');
                $candidates[] = __DIR__ . '/../' . ltrim($lp, '/');
            }
            $candidates[] = __DIR__ . '/../logos/apistar.jpg';
            $candidates[] = __DIR__ . '/../logos/apistar.png';
            $candidates[] = __DIR__ . '/../upload/User/logo.png';
            $candidates[] = __DIR__ . '/hk-logo.png';
            foreach ($candidates as $candidate) {
                if (is_readable($candidate)) {
                    return str_replace('\\', '/', $candidate);
                }
            }
            return '';
        };
        
        $sql1 = "SELECT * FROM plant WHERE plant_id = '".$plant_id."' LIMIT 1";
        $result1 = $conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            $row1 = $result1->fetch_assoc();
            $plant_name = isset($row1['plant_name']) ? $row1['plant_name'] : '';
            $plant_full_name = !empty($row1['plant_full_name']) ? $row1['plant_full_name'] : $plant_name;
            $plant_full_address = isset($row1['plant_full_address']) ? $row1['plant_full_address'] : '';
            $email = isset($row1['email']) ? $row1['email'] : '';
            $gst_no = isset($row1['gst_no']) ? $row1['gst_no'] : '';
            $panNo = isset($row1['panNo']) ? $row1['panNo'] : '';
            $dlNo = isset($row1['dlNo']) ? $row1['dlNo'] : '';
            $logoSrc = $resolvePlantLogo(isset($row1['logo_path']) ? $row1['logo_path'] : '');
        }
        
        $sqlCo = "SELECT * FROM company WHERE plant_id = '".$plant_id."' LIMIT 1";
        $resCo = $conn->query($sqlCo);
        if ($resCo && $resCo->num_rows > 0) {
            $co = $resCo->fetch_assoc();
            if (!empty($co['company_name'])) {
                $plant_full_name = trim($co['company_name']);
            }
            if (!empty($co['address'])) {
                $plant_full_address = trim($co['address']);
            }
            if (!empty($co['gst_no'])) {
                $gst_no = trim($co['gst_no']);
            }
            if (!empty($co['pan_no'])) {
                $panNo = trim($co['pan_no']);
            }
            if (!empty($co['present_state'])) {
                $plant_state = trim($co['present_state']);
            }
            if (!empty($co['scode'])) {
                $state_code = trim($co['scode']);
            }
            if (!empty($co['bank_name'])) {
                $bank_name = trim($co['bank_name']);
            }
            if (!empty($co['branch_name'])) {
                $bank_branch = trim($co['branch_name']);
            }
            if (!empty($co['ac_no'])) {
                $bank_ac_no = trim($co['ac_no']);
            }
        }
        
        $sqlBk = "SELECT * FROM bank_master WHERE plant_id = '".$plant_id."' ORDER BY id ASC LIMIT 1";
        $resBk = $conn->query($sqlBk);
        if ($resBk && $resBk->num_rows > 0) {
            $bk = $resBk->fetch_assoc();
            if (!empty($bk['bank_name'])) {
                $bank_name = trim($bk['bank_name']);
            }
            if (!empty($bk['branch_name'])) {
                $bank_branch = trim($bk['branch_name']);
            }
            if (!empty($bk['account_no'])) {
                $bank_ac_no = trim($bk['account_no']);
            }
            if (!empty($bk['ifsc_code'])) {
                $bank_ifsc = trim($bk['ifsc_code']);
            }
        }
        
        if ($logoSrc === '') {
            $logoSrc = $resolvePlantLogo('');
        }
        
        if ($gst_no === '' || $gst_no === null) {
            $nameKey = $conn->real_escape_string(trim(substr($plant_full_name !== '' ? $plant_full_name : $plant_name, 0, 20)));
            if ($nameKey !== '') {
                $sqlCo2 = "SELECT * FROM company WHERE company_name LIKE '%".$nameKey."%' AND gst_no IS NOT NULL AND gst_no != '' LIMIT 1";
                $resCo2 = $conn->query($sqlCo2);
                if ($resCo2 && $resCo2->num_rows > 0) {
                    $co2 = $resCo2->fetch_assoc();
                    if (!empty($co2['gst_no'])) {
                        $gst_no = trim($co2['gst_no']);
                    }
                    if (!empty($co2['pan_no'])) {
                        $panNo = trim($co2['pan_no']);
                    }
                    if (!empty($co2['present_state'])) {
                        $plant_state = trim($co2['present_state']);
                    }
                    if (!empty($co2['scode'])) {
                        $state_code = trim($co2['scode']);
                    }
                    if (!empty($co2['address']) && ($plant_full_address === '' || $plant_full_address === null)) {
                        $plant_full_address = trim($co2['address']);
                    }
                }
            }
        }
        
        if ($bank_account_name === 'SHREE HARIKRISHNA PHARMACEUTICALS PVT LTD') {
            $bank_account_name = strtoupper($plant_full_name !== '' ? $plant_full_name : $plant_name);
        }
        
        if ($gst_no !== '' && strlen($gst_no) >= 12) {
            if ($state_code === '' || $state_code === null) {
                $state_code = substr($gst_no, 0, 2);
            }
            if ($panNo === '' || $panNo === null) {
                $panNo = substr($gst_no, 2, 10);
            }
        }
        
        $displayName = $plant_full_name !== '' ? $plant_full_name : $plant_name;
        $displayNameUpper = strtoupper($displayName);
        $phoneDisplay = $mobNo;
        
        $bd = 'border:1px solid #000000;';
        $cell = $bd . ' font-size:8px; padding:2px 3px; vertical-align:middle;';
        $boldCell = $cell . ' font-weight:bold;';
        
        $logoHtml = '';
        if ($logoSrc !== '') {
            $logoHtml = '<img src="'.$logoSrc.'" style="width:52px;height:52px;" alt="">';
        }
        
        $html = '';
        $sql = "SELECT * FROM tax_invoice WHERE id = '".$id."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $tax_invoice = $result->fetch_assoc();
            $output1 = array();
            $productRowsHtml = '';
            $productCount = 0;
        
            $sql2 = "SELECT * FROM tax_invoice_product WHERE invoiceId = '".$conn->real_escape_string($tax_invoice['id'])."' ORDER BY id ASC";
                $result2 = $conn->query($sql2);
            if ($result2 && $result2->num_rows > 0) {
                $sr = 1;
                while ($row = $result2->fetch_assoc()) {
                    $productCount++;
                    $lineAmount = $row['taxable'];
                    if ($lineAmount === '' || $lineAmount === null) {
                        $lineAmount = (float) $row['rate'] * (float) $row['requiredQty'];
                    }
                    $productDesc = trim((string) $row['product_name']);
                    $batchNo = trim((string) ($row['batch_no'] ?? ''));
                    if ($batchNo !== '') {
                        $productDesc =   $productDesc;
                    }
                    $productRowsHtml .= '
                        <tr>
                            <td style="'.$cell.' text-align:center; width:5%;">'.$sr.'</td>
                            <td style="'.$cell.' text-align:left; width:30%;">'.htmlspecialchars($productDesc).' <br>Batch No: '.htmlspecialchars($batchNo).'                             </td>
                            <td style="'.$cell.' text-align:center; width:10%;">'.htmlspecialchars($row['hsn']).'</td>
                            <td style="'.$cell.' text-align:center; width:8%;">'.htmlspecialchars($row['gst']).'</td>
                            <td style="'.$cell.' text-align:center; width:10%;">'.htmlspecialchars($row['requiredQty']).'</td>
                            <td style="'.$cell.' text-align:center; width:8%;">'.htmlspecialchars($row['unit']).'</td>
                            <td style="'.$cell.' text-align:right; width:12%;">'.formatInvoicePdfAmount($row['rate'], 4).'</td>
                            <td style="'.$cell.' text-align:right; width:17%;">'.formatInvoicePdfAmount($lineAmount, 2).'</td>
                        </tr>';
                         
                        $taxPer = $row['gst'];
                    $igst = (float) $row['igst'];
                    $cgst = (float) $row['cgst'];
                    $sgst = (float) $row['sgst'];
                    $taxable = (float) $row['taxable'];
                        $found = false;
                        foreach ($output1 as &$item) {
                            if ($item['taxPer'] == $taxPer) {
                                $item['igst'] += $igst;
                                $item['cgst'] += $cgst;
                                $item['sgst'] += $sgst;
                                $item['taxable'] += $taxable;
                                $found = true;
                                break;
                            }
                        }
                    unset($item);
                        if (!$found) {
                        $output1[] = array(
                            'taxPer' => $taxPer,
                            'igst' => $igst,
                            'cgst' => $cgst,
                            'sgst' => $sgst,
                            'taxable' => $taxable
                        );
                    }
                    $sr++;
                }
            }
        
            $emptyRowCount = max(0, 10 - $productCount);
            for ($i = 0; $i < $emptyRowCount; $i++) {
                $productRowsHtml .= '
                    <tr>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$cell.'">&nbsp;</td>
                    </tr>';
            }
        
            $taxRowsHtml = '';
            if (count($output1) === 0) {
                $output1[] = array(
                    'taxPer' => '',
                    'igst' => (float) $tax_invoice['igstTotal'],
                    'cgst' => (float) $tax_invoice['cgstTotal'],
                    'sgst' => (float) $tax_invoice['sgstTotal'],
                    'taxable' => (float) $tax_invoice['taxableTotal']
                );
            }
            foreach ($output1 as $item) {
                $cgstPct = ((float) $item['cgst'] > 0) ? ((float) $item['taxPer'] / 2) : '';
                $sgstPct = ((float) $item['sgst'] > 0) ? ((float) $item['taxPer'] / 2) : '';
                $igstPct = ((float) $item['igst'] > 0) ? $item['taxPer'] : '';
                $taxAmt = (float) $item['igst'] + (float) $item['cgst'] + (float) $item['sgst'];
                $taxRowsHtml .= '
                    <tr>
                        <td style="'.$cell.' text-align:right; width:28%;">'.formatInvoicePdfAmount($item['taxable']).'</td>
                        <td style="'.$cell.' text-align:center; width:18%;">'.$cgstPct.'</td>
                        <td style="'.$cell.' text-align:center; width:18%;">'.$sgstPct.'</td>
                        <td style="'.$cell.' text-align:center; width:18%;">'.$igstPct.'</td>
                        <td style="'.$cell.' text-align:right; width:18%;">'.formatInvoicePdfAmount($taxAmt).'</td>
                    </tr>';
            }
        
            $gstType = strtoupper(trim($tax_invoice['gst_type']));
            $taxSummaryHtml = '';
            if ((float) $tax_invoice['igstTotal'] > 0) {
                foreach ($output1 as $item) {
                    if ((float) $item['igst'] > 0) {
                        $taxSummaryHtml .= '
                            <tr>
                                <td style="'.$cell.' text-align:left;">IGST '.$item['taxPer'].' %</td>
                                <td style="'.$cell.' text-align:right; width:35%;">'.formatInvoicePdfAmount($item['igst']).'</td>
                            </tr>';
                    }
                }
            }
            if ((float) $tax_invoice['cgstTotal'] > 0) {
                foreach ($output1 as $item) {
                    if ((float) $item['cgst'] > 0) {
                        $taxSummaryHtml .= '
                            <tr>
                                <td style="'.$cell.' text-align:left;">CGST '.((float) $item['taxPer'] / 2).' %</td>
                                <td style="'.$cell.' text-align:right; width:35%;">'.formatInvoicePdfAmount($item['cgst']).'</td>
                            </tr>';
                    }
                }
            }
            if ((float) $tax_invoice['sgstTotal'] > 0) {
                foreach ($output1 as $item) {
                    if ((float) $item['sgst'] > 0) {
                        $taxSummaryHtml .= '
                            <tr>
                                <td style="'.$cell.' text-align:left;">SGST '.((float) $item['taxPer'] / 2).' %</td>
                                <td style="'.$cell.' text-align:right; width:35%;">'.formatInvoicePdfAmount($item['sgst']).'</td>
                            </tr>';
                    }
                }
            }
            if ($taxSummaryHtml === '' && (float) $tax_invoice['taxAmtTotal'] > 0) {
                $taxSummaryHtml = '
                    <tr>
                        <td style="'.$cell.' text-align:left;">'.$gstType.' Tax</td>
                        <td style="'.$cell.' text-align:right; width:35%;">'.formatInvoicePdfAmount($tax_invoice['taxAmtTotal']).'</td>
                    </tr>';
            }
        
            $transportAmt = 0;
            $subTotal = (float) $tax_invoice['taxableTotal'];
            $netTotal = (float) $tax_invoice['netTotal'];
            $taxTotal = (float) $tax_invoice['taxAmtTotal'];
            $roundOff = round($netTotal - ($subTotal + $taxTotal + $transportAmt), 2);
        
            $billBlock = trim(
                ($tax_invoice['BillTo'] ? 'M/s: '.$tax_invoice['BillTo']."\n" : '').
                ($tax_invoice['BillToAddress'] ? $tax_invoice['BillToAddress']."\n" : '').
                ($tax_invoice['BillToCity'] ? 'Dist - '.$tax_invoice['BillToCity']."\n" : '').
                ($tax_invoice['BillToCity'] || $tax_invoice['BillToPincode'] ? trim($tax_invoice['BillToCity'].' - '.$tax_invoice['BillToPincode'])."\n" : '').
                ($tax_invoice['BillToState'] ? $tax_invoice['BillToState']."\n" : '').
                ($tax_invoice['BillToGSTIN'] ? 'GSTIN: '.$tax_invoice['BillToGSTIN']."\n" : '').
                ($tax_invoice['BillToPanNo'] ? 'PAN: '.$tax_invoice['BillToPanNo'] : '')
            );
            $shipBlock = trim(
                ($tax_invoice['ShipTo'] ? $tax_invoice['ShipTo']."\n" : '').
                ($tax_invoice['ShipToAddress'] ? $tax_invoice['ShipToAddress']."\n" : '').
                ($tax_invoice['ShipToCity'] ? 'Dist - '.$tax_invoice['ShipToCity']."\n" : '').
                ($tax_invoice['ShipToCity'] || $tax_invoice['ShipToPincode'] ? trim($tax_invoice['ShipToCity'].' - '.$tax_invoice['ShipToPincode'])."\n" : '').
                ($tax_invoice['ShipToState'] ? $tax_invoice['ShipToState']."\n" : '').
                ($tax_invoice['ShipToGSTIN'] ? 'GSTIN: '.$tax_invoice['ShipToGSTIN']."\n" : '').
                ($tax_invoice['ShipToPanNo'] ? 'PAN: '.$tax_invoice['ShipToPanNo'] : '')
            );
        
            $modeTransport = !empty($tax_invoice['dispatch']) ? $tax_invoice['dispatch'] : 'Road';
            $eWayBillNo = formatInvoicePdfDate($tax_invoice['eWayBillNo']);

            $invoiceDate = formatInvoicePdfDate($tax_invoice['Invoice_date']);
            $amountWords = invoiceAmountInWords($tax_invoice['netTotal']);

            $authSignEmpId = !empty($tax_invoice['approveBy']) ? $tax_invoice['approveBy']
                : (!empty($tax_invoice['approve_by']) ? $tax_invoice['approve_by']
                : (!empty($tax_invoice['entry_by']) ? $tax_invoice['entry_by'] : $_GET['emp_id']));
            $authSignSrc = resolveInvoicePdfSignatureImage($authSignEmpId, $conn);
            $authSignName = getInvoiceEmployeeDisplayName($authSignEmpId, $conn);
            $authSignDateRaw = !empty($tax_invoice['approve_date']) ? $tax_invoice['approve_date'] : ($tax_invoice['entry_date'] ?? '');
            $authSignDate = formatInvoicePdfDate($authSignDateRaw);
            $authSignImgHtml = $authSignSrc !== '' ? '<img src="'.$authSignSrc.'" style="width:80px;height:35px;" alt="">' : '';
            $authSignNameHtml = $authSignName !== '' ? htmlspecialchars($authSignName).'<br>' : '';
            $authSignDateHtml = $authSignDate !== '' ? htmlspecialchars($authSignDate).'<br>' : '';
        
            $html = '
            <table cellpadding="2" cellspacing="0" border="1" style="width:100%; border-collapse:collapse; table-layout:fixed;">
                <tr>
                    <td rowspan="3" style="'.$cell.' width:12%; text-align:center; vertical-align:middle;">'.$logoHtml.'</td>
                    <td colspan="7" style="'.$boldCell.' text-align:center; color:#0000cc; font-size:13px;">'.htmlspecialchars($displayName).'</td>
                            </tr>
                            <tr>
                    <td colspan="7" style="'.$cell.' text-align:center;">'.htmlspecialchars($plant_full_address).'</td>
                            </tr>
                            <tr>
                    <td colspan="7" style="'.$cell.' text-align:center;">'.htmlspecialchars($plant_state).' - India. Phone : '.htmlspecialchars($phoneDisplay).'</td>
                            </tr>
                            <tr>
                    <td colspan="4" style="'.$cell.' width:50%; vertical-align:top; border-right:1px solid #000;">
                        Mobile No. :- '.htmlspecialchars($mobNo).'<br>
                        <b>GST No. :- '.htmlspecialchars($gst_no).'</b><br>
                        State :- '.htmlspecialchars($plant_state).'&nbsp;&nbsp;Code :- '.htmlspecialchars($state_code).'<br>
                        P. A. N. :- '.htmlspecialchars($panNo).'
                    </td>
                    <td colspan="4" style="'.$cell.' width:50%; vertical-align:top;">
                        Original / Duplicate / Triplicate<br>
                        Invoice No. :- '.htmlspecialchars($tax_invoice['Invoice_no']).'<br>
                        Invoice Date :- '.htmlspecialchars($invoiceDate).'<br>
                        E-Way Bill No. :- '.htmlspecialchars($eWayBillNo).'
                    </td>
                            </tr>
                            <tr>
                    <td colspan="8" style="'.$boldCell.' text-align:center; font-size:11px;">TAX INVOICE</td>
                            </tr>
                            <tr>
                    <td colspan="4" style="'.$boldCell.' width:50%; border-right:1px solid #000;">Details of Receiver ( Billed to )</td>
                    <td colspan="4" style="'.$boldCell.' width:50%;">Details of Consignee ( Shipped to )</td>
                            </tr>
                            <tr>
                    <td colspan="4" style="'.$cell.' width:50%; height:70px; vertical-align:top; border-right:1px solid #000;">'.nl2br(htmlspecialchars($billBlock)).'</td>
                    <td colspan="4" style="'.$cell.' width:50%; height:70px; vertical-align:top;">'.nl2br(htmlspecialchars($shipBlock)).'</td>
                            </tr>
                            <tr>
                    <td colspan="8" style="padding:0;">
                        <table cellpadding="2" cellspacing="0" border="1" style="width:100%; border-collapse:collapse; table-layout:fixed;">
                            <tr>
                                <td style="'.$boldCell.' text-align:center; width:5%;">Sr No.</td>
                                <td style="'.$boldCell.' text-align:center; width:30%;">Description</td>
                                <td style="'.$boldCell.' text-align:center; width:10%;">HSN / SAC</td>
                                <td style="'.$boldCell.' text-align:center; width:8%;">GST %</td>
                                <td style="'.$boldCell.' text-align:center; width:10%;">Qty</td>
                                <td style="'.$boldCell.' text-align:center; width:8%;">Unit</td>
                                <td style="'.$boldCell.' text-align:center; width:12%;">Rate</td>
                                <td style="'.$boldCell.' text-align:center; width:17%;">Amount</td>
                            </tr>
                            '.$productRowsHtml.'
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="'.$cell.' vertical-align:top; width:37.5%;">
                        Mode of transport :- '.htmlspecialchars($modeTransport).'<br>
                        Vehicle No. :- '.htmlspecialchars($tax_invoice['vehicleNo']).'<br>
                        Transporter :- '.htmlspecialchars($tax_invoice['transporter']).'
                    </td>
                    <td colspan="3" style="'.$cell.' vertical-align:top; width:37.5%; padding:0;">
                        <table cellpadding="2" cellspacing="0" border="1" style="width:100%; border-collapse:collapse; table-layout:fixed;">
                            <tr>
                                <td style="'.$boldCell.' text-align:center; width:28%;">Taxable Value</td>
                                <td style="'.$boldCell.' text-align:center; width:18%;">% CGST</td>
                                <td style="'.$boldCell.' text-align:center; width:18%;">% SGST</td>
                                <td style="'.$boldCell.' text-align:center; width:18%;">% IGST</td>
                                <td style="'.$boldCell.' text-align:center; width:18%;">Amount</td>
                            </tr>
                            '.$taxRowsHtml.'
                        </table>
                    </td>
                    <td colspan="2" style="'.$cell.' vertical-align:top; width:25%; padding:0;">
                        <table cellpadding="2" cellspacing="0" border="1" style="width:100%; border-collapse:collapse; table-layout:fixed;">
                            <tr>
                                <td style="'.$cell.' text-align:left; width:65%;">Sub Total</td>
                                <td style="'.$cell.' text-align:right; width:35%;">'.formatInvoicePdfAmount($subTotal).'</td>
                            </tr>
                            '.$taxSummaryHtml.'
                            <tr>
                                <td style="'.$cell.' text-align:left;">Transportation</td>
                                <td style="'.$cell.' text-align:right;">'.formatInvoicePdfAmount($transportAmt).'</td>
                            </tr>
                            <tr>
                                <td style="'.$cell.' text-align:left;">Round off</td>
                                <td style="'.$cell.' text-align:right;">'.formatInvoicePdfAmount($roundOff).'</td>
                            </tr>
                            <tr>
                                <td style="'.$boldCell.' text-align:left;">Total Amount</td>
                                <td style="'.$boldCell.' text-align:right;">'.formatInvoicePdfAmount($netTotal).'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                            <tr>
                    <td colspan="8" style="'.$boldCell.'"><b>In Word :- '.htmlspecialchars($amountWords).'</b></td>
                            </tr>
                            <tr>
                    <td colspan="8" style="'.$boldCell.' text-align:left; padding:3px 5px;">MFG. LIC. NO.: G/25/2552</td>
                            </tr>
                            <tr>
                    <td colspan="8" style="'.$cell.' vertical-align:top;">
                        <b>Term and Conditions</b><br>
                        1.If there are any quality issues, kindly inform us within seven days.<br>
                        2.If all drum seals are found open, no return will be accepted.<br>
                        3.Kindly conduct analysis before dispatching the material for export. After dispatch, we will not be responsible for any queries or claims.
                    </td>
                            </tr>
                            <tr>
                    <td colspan="8" style="'.$cell.' font-size:7px;">
                        You are requested to pay by Account Payee Cheque only in the name of
                        <b>'.htmlspecialchars($displayNameUpper).' RTGS / NEFT in following Account</b>
                    </td>
                            </tr>
                            <tr>
                    <td colspan="5" style="'.$cell.' vertical-align:top; width:58%;">
                        <b>Name of Account :- '.htmlspecialchars($bank_account_name).'</b><br>
                        <b>A / C No. :- '.htmlspecialchars($bank_ac_no).'</b><br>
                        <b>Bank :- '.htmlspecialchars($bank_name).'</b>&nbsp;&nbsp;<b>Branch :- '.htmlspecialchars($bank_branch).'</b><br>
                        <b>IFS Code :- '.htmlspecialchars($bank_ifsc).'</b><br><br>
                        <span style="font-size:7px;">Declaration: We declare that this invoice shows the actual price if the goods describe and that all particulars are true and correct.</span>
                    </td>
                    <td colspan="3" style="'.$cell.' vertical-align:top; width:42%; text-align:center;">
                        <b>For '.htmlspecialchars($displayNameUpper).'</b><br><br>
                        '.$authSignImgHtml.'<br>
                        '.$authSignNameHtml.'
                        '.$authSignDateHtml.'
                        <span style="font-size:7px;">Authorised signature</span>
                    </td>
                            </tr>
                            <tr>
                    <td colspan="5" style="'.$cell.'">&nbsp;</td>
                    <td colspan="3" style="'.$cell.' text-align:right; font-size:7px;">Recever\'s Signature</td>
                            </tr>
              </table>';
        }
        
        if (ob_get_length()) {
            ob_end_clean();
        }
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('invoice.pdf', 'I');
        exit;
    }
    else  if ($_GET["type"] == "materialStatusQrCode") { 
        header('Access-Control-Allow-Origin: *');

        include 'barcode/phpqrcode/qrlib.php'; 
        $randomNumber = rand(100, 999);
       echo   $id = 5;
          $token = $_GET["token"];
          $plant_id = $_GET["plant_id"];
         
         $text = "https://cpplgmp.com/php/phpdevlop/gmptotal/dispatch/invoice.php?type=invoicePDF&id=$id&token=$token&user_no=gmpdemo1&plant_id=$plant_id";
         
         $file = "barcode/$randomNumber.png";
        // Other parameters
        $ecc = 'H';
        $pixel_size = 20;
        $frame_size = 5;
        
        QRcode::png($text, $file, $ecc, $pixel_size, $frame_size);



        class MYPDF extends TCPDF {
            // Page header
            public function Header() {
                // Leave empty for now or customize it further.
            }
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
            }
        }

        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(10, 5, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage('p');
        
        $qtyRq = (int)$_GET["qty"];
        $html = '';
            
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name 
        FROM stock_book s LEFT JOIN my_view m ON s.material_code=m.material_code 
        LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.ar_no='".$_GET["ar_no"]."' AND s.batch_no='".$_GET["batch_no"]."' AND s.material_code='".$_GET["material_code"]."'
        AND s.plant_id='".$_GET["plant_id"]."' LIMIT 1";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
        		$q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')"; $resQ = $conn->query($q); $prodLatest = $resQ->fetch_assoc(); 
        		$row['gradeName'] = $prodLatest['gradeName'];
         
        	 
        		for($i = 0; $i < $qtyRq; $i++){
        		    $html .= '
                      <table cellpadding="2" border="1">
                          <tr>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Material Name :- <span style="color: blue;">'.$row['material_name'].'</span></td>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Material Code :- <span style="color: blue;">'.$row['material_code'].'</span></td>
                              <td rowspan="5" style="text-align: center;">
                                <img src="'.$file.'" width="100" alt="QR Code">
                              </td>
                          </tr>
                          <tr>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">GRN No. :- <span style="color: blue;">'.$row['grn_no'].'</span></td>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">GRN Date :- <span style="color: blue;">'.$row['grn_date'].'</span></td>
                          </tr>
                          <tr>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Rack Loc. :- <span style="color: blue;">'.$row['RackLocation'].'</span></td>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">A.R.No. :- <span style="color: blue;">'.$row['ar_no'].'</span></td>
                          </tr>
                          <tr>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Vendor Name :- <span style="color: blue;">'.$row['vendor_name'].'</span></td>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Grade :- <span style="color: blue;">'.$row['gradeName'].'</span></td>
                          </tr>
                          <tr>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Batch No. :- <span style="color: blue;">'.$row['batch_no'].'</span></td>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Qty. :- <span style="color: blue;">'.$row['qty'].' '.$row['unit'].'</span></td>
                          </tr>
                          <tr>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Pack Size :- <span style="color: blue;">'.$row['pack_size'].'  '.$row['unit'].'</span></td>
                              <td style="text-align: left; font-weight: bolder; font-size: 9px;">Status :- <span style="color: blue;font-size: 12px;">'.strtoupper($row['status']).'</span></td>
                          </tr>
                      </table>
                      <div></div>
                ';
        		    
        		    
        		}
        		   
    		}
    	}
                     
 
                   
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('invoice.pdf', 'I');
                    
    }
}





function formatInvoicePdfDate($date) {
    if ($date === null || $date === '') {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : $date;
}

function getInvoiceEmployeeDisplayName($empId, $conn) {
    if ($empId === null || trim((string) $empId) === '') {
        return '';
    }
    $empIdEsc = $conn->real_escape_string(trim((string) $empId));
    $sql = "SELECT TRIM(CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))) AS emp_name
            FROM employee WHERE emp_id = '".$empIdEsc."' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = trim($row['emp_name']);
        if ($name !== '') {
            return $name;
        }
    }
    return trim((string) $empId);
}

function resolveInvoicePdfSignatureImage($empId, $conn) {
    $candidates = array();
    if ($empId !== null && trim((string) $empId) !== '') {
        $empIdEsc = $conn->real_escape_string(trim((string) $empId));
        $sql = "SELECT sign FROM employee WHERE emp_id = '".$empIdEsc."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $signFile = trim((string) ($row['sign'] ?? ''));
            if ($signFile !== '') {
                $candidates[] = __DIR__ . '/../upload/employee/' . $signFile;
                $candidates[] = __DIR__ . '/../upload/employee/' . basename($signFile);
            }
        }
    }
    $candidates[] = __DIR__ . '/../upload/pdf/sign.jpg';
    $candidates[] = __DIR__ . '/../upload/pdf/sign.png';
    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            return str_replace('\\', '/', $candidate);
        }
    }
    return '';
}

function formatInvoicePdfAmount($value, $decimals = 2) {
    if ($value === null || $value === '') {
        return number_format(0, $decimals, '.', '');
    }
    return number_format((float) $value, $decimals, '.', '');
}

function invoiceAmountInWords($number) {
    $words = convertNumberToIndianCurrencyWords($number);
    if (!$words) {
        return '';
    }
    $words = preg_replace('/\s*(rupees and|rupees|paise)\s*$/i', '', $words);
    $words = preg_replace('/\s+paise\s*$/i', '', $words);
    return ucwords(trim($words)) . ' Only';
}

function convertNumberToIndianCurrencyWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' rupees and ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'forty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        100000              => 'lakh',
        10000000            => 'crore'
    );
    
    if (!is_numeric($number)) {
        return false;
    }
    
    // Handle negative numbers
    if ($number < 0) {
        return $negative . convertNumberToIndianCurrencyWords(abs($number));
    }
    
    // Split integer and decimal parts
    $integerPart = (int) $number;
    $fractionalPart = null;
    if (strpos((string)$number, '.') !== false) {
        $fractionalPart = explode('.', (string)$number)[1];
    }

    $string = '';
    
    // Handle zero case
    if ($integerPart == 0) {
        $string = $dictionary[0];
    }
    
    // Convert integer part
    else {
        $string = convertIntegerToIndianWords($integerPart, $dictionary, $hyphen, $conjunction, $separator);
    }
    
    // Convert fractional part (if exists)
    if ($fractionalPart !== null && is_numeric($fractionalPart)) {
        $string .= $decimal;
        $fractionalDigits = str_split($fractionalPart);
        foreach ($fractionalDigits as $digit) {
            $string .= $dictionary[$digit] . ' ';
        }
        $string .= 'paise';
    } else {
        $string .= ' rupees';
    }
    
    return trim($string);
}

function convertIntegerToIndianWords($number, $dictionary, $hyphen, $conjunction, $separator) {
    if ($number < 21) {
        return $dictionary[$number];
    } elseif ($number < 100) {
        $tens   = ((int) ($number / 10)) * 10;
        $units  = $number % 10;
        $string = $dictionary[$tens];
        if ($units) {
            $string .= $hyphen . $dictionary[$units];
        }
        return $string;
    } elseif ($number < 1000) {
        $hundreds  = $number / 100;
        $remainder = $number % 100;
        $string = $dictionary[(int)$hundreds] . ' ' . $dictionary[100];
        if ($remainder) {
            $string .= $conjunction . convertIntegerToIndianWords($remainder, $dictionary, $hyphen, $conjunction, $separator);
        }
        return $string;
    } else {
        foreach (array_reverse($dictionary, true) as $value => $word) {
            if ($number >= $value) {
                $numberOfWords = (int)($number / $value);
                $remainder = $number % $value;
                $string = convertIntegerToIndianWords($numberOfWords, $dictionary, $hyphen, $conjunction, $separator) . ' ' . $word;
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= convertIntegerToIndianWords($remainder, $dictionary, $hyphen, $conjunction, $separator);
                }
                return $string;
            }
        }
    }
}
 


$conn->close();
?>