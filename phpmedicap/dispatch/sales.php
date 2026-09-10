<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

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
    
    if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT f.*, p.product_name, p.grade, p.gst, p.mrp FROM finish_product f LEFT JOIN product p ON f.product_code=p.product_code GROUP BY f.product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT batch_no, IFNULL(SUM(qty), 0) as qty, unit FROM finish_product WHERE product_code='".$row["product_code"]."' GROUP BY batch_no";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    if ($_GET["type"] == "saveBulkOrder1") {
        if ($input["gst_type"] == "IGST") {
            $input["SgstVal"] = 0;
            $input["CgstVal"] = 0;
        } else {
            $input["IgstVal"] = 0;
        }
       // $json_data =  json_encode($input["sales_data"]);
        
          $sql = "INSERT INTO sales (plant_id,order_type,user_no, client_code, order_qty, gst_app, gst_type, bill_curr, branch, curr_rate, disc_percent, val_in_inr, pay_mode, po_no, po_date, gross_total, disc_total, tax_total, net_total, final_total, sales_data, entry_by, entry_date, taxable, CgstVal, SgstVal, IgstVal, OthChrg, RndOffAmt, TotInvVal, other)
        VALUES ('".$_GET["plant_id"]."','RAW','".$_GET["user_no"]."', '".$input["client_code"]."', '".$input["order_qty"]."', '".$input["gst_app"]."',
        '".$input["gst_type"]."', '".$input["bill_curr"]."', '".$input["branch"]."', '".$input["curr_rate"]."', '".$input["disc_percent"]."',
        '".$input["val_in_inr"]."', '".$input["pay_mode"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["gross_total"]."', 
        '".$input["disc_total"]."', '".$input["tax_total"]."', '".$input["net_total"]."', '".$input["final_total"]."', '".json_encode($input["sales_data"])."', 
        '".$_GET["emp_id"]."', '$entry_date', '".$input["taxable"]."', '".$input["CgstVal"]."', '".$input["SgstVal"]."', '".$input["IgstVal"]."',
        '".$input["other_charges"]."', '".$input["RndOffAmt"]."', '".$input["TotInvVal"]."', '".$input["other"]."')";
 
    
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $order_no = "";
            $sql1 = "SELECT order_no FROM sales WHERE id='$last_id'";
            $result1 = $conn->query($sql1);
           while ($row = $result1->fetch_assoc()) {
                $order_no = $row["order_no"];
            }
          $materials = $input["sales_data"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                
                if ($input["gst_type"] == "IGST") {
                    $material["sgst_amt"] = 0;
                    $material["cgst_amt"] = 0;
                } else {
                    $material["igst_amt"] = 0;
                }

                $sql = "INSERT INTO sales_product_dtl (order_no, singleProduct, dosage_form, product_name, product_code, hsn, qty, requiredQty, packing_style,
                            gst_per,IgstAmt,gst,gross_total,net_total)
                VALUES ('$order_no','".json_encode($material["singleProduct"])."','".$material["material_type"]."','".$material["material_name"]."',
                '".$material["material_code"]."','".$material["hsn"]."','".$material["sale_qty"]."','".$material["requiredQty"]."',
                '".$material["packing_style"]."','".$material["gst_amt"]."','".$material["Igst_amt"]."','".$material["gst"]."',
                '".$material["gross_total"]."','".$material["net_total"]."')";
            $conn->query($sql);
            
            
            
                
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getNextDispatchNumbers") {
        header('Content-Type: application/json; charset=utf-8');
        dispatch_ensure_sales_challan_column($conn);
        $nums = dispatch_get_next_numbers($conn, $_GET["plant_id"]);
        echo json_encode(array(
            'status' => 'success',
            'invoice_serial' => $nums['invoice_serial'],
            'invoice_no' => $nums['invoice_no'],
            'challan_no' => $nums['challan_no'],
        ));
        exit;
    }
    else if ($_GET["type"] == "saveBulkOrder") {
        dispatch_ensure_sales_challan_column($conn);
        $plantId = $_GET["plant_id"];
        $nums = dispatch_find_next_available_numbers($conn, $plantId);

        if (dispatch_challan_exists($conn, $plantId, $nums['challan_no'])) {
            echo json_encode(array('status' => 'failed', 'msg' => 'Challan number ' . $nums['challan_no'] . ' already exists.'));
            exit;
        }
        if (dispatch_invoice_exists($conn, $plantId, $nums['invoice_no'])) {
            echo json_encode(array('status' => 'failed', 'msg' => 'Invoice number ' . $nums['invoice_no'] . ' already exists.'));
            exit;
        }

        $challanNo = $conn->real_escape_string($nums['challan_no']);
        $invoiceNo = $conn->real_escape_string($nums['invoice_no']);
        $poNo = $conn->real_escape_string(trim((string)($input["po_no"] ?? $input["orderNo"] ?? '')));
        $buyerOrderNo = $conn->real_escape_string(trim((string)($input["orderNo"] ?? '')));
          
         $sql = "INSERT INTO `sales`(`plant_id`, `client_code`, `gst_type`, `orderNo`, `orderDate`, `Invoice_no`, `Invoice_date`, `transporter`, 
        `creditDays`, `vehicleNo`, `transReceipt`, `dueDate`, `eWayBillNo`, `broker`, `agent`, `banker`, `dispatch`, `BillTo`, `BillToAddress`,
        `BillToGSTIN`, `BillToCity`, `BillToState`, `BillToPincode`, `BillToDlNo`, `BillToEmail`, `BillToScode`, `BillToPanNo`, `ShipTo`, 
        `ShipToAddress`, `ShipToGSTIN`, `ShipToCity`, `ShipToState`, `ShipToPincode`, `ShipToDlNo`, `ShipToEmail`, `ShipToScode`, `ShipToPanNo`, 
        `taxableTotal`, `taxAmtTotal`, `netTotal`, `igstTotal`, `cgstTotal`, `sgstTotal`, `status`, `entry_by`, `entry_date`, `challan_no`, `po_no`, `invoice`) VALUES 
        ('".$_GET["plant_id"]."','".$input["client_code"]."', '".$input["gst_type"]."', '".$buyerOrderNo."','".$input["orderDate"]."', 
        '".$invoiceNo."', '".$input["Invoice_date"]."', '".$input["transporter"]."', '".$input["creditDays"]."','".$input["vehicleNo"]."', 
        '".$input["transReceipt"]."', '".$input["dueDate"]."', '".$input["eWayBillNo"]."', '".$input["broker"]."','".$input["agent"]."', 
        '".$input["banker"]."', '".$input["dispatch"]."', '".$input["BillTo"]."', '".$input["BillToAddress"]."','".$input["BillToGSTIN"]."',
        '".$input["BillToCity"]."', '".$input["BillToState"]."', '".$input["BillToPincode"]."', '".$input["BillToDlNo"]."','".$input["BillToEmail"]."', 
        '".$input["BillToScode"]."', '".$input["BillToPanNo"]."', '".$input["ShipTo"]."','".$input["ShipToAddress"]."','".$input["ShipToGSTIN"]."', 
        '".$input["ShipToCity"]."', '".$input["ShipToState"]."', '".$input["ShipToPincode"]."', '".$input["ShipToDlNo"]."','".$input["ShipToEmail"]."', 
        '".$input["ShipToScode"]."', '".$input["ShipToPanNo"]."','".$input["taxableTotal"]."','".$input["taxAmtTotal"]."', '".$input["netTotal"]."', 
        '".$input["igstTotal"]."', '".$input["cgstTotal"]."','".$input["sgstTotal"]."', 
        'Pending','".$_GET["emp_id"]."', '$entry_date', '$challanNo', '$poNo', 'Pending')";
        
        if ($conn->query($sql)) {
             
            $last_id = $conn->insert_id;  
 
            $array = $input["products"];
            //$array = json_decode($json_obj, true);
            foreach ($array as $values)
            {
                
                $sql1 = "INSERT INTO `sales_product`(`plant_id`, `orderId`, `product_code`, `product_name`, `hsn`, `batch_no`, `ar_no`, `mfg_date`, 
                `exp_date`, `pack_size`, `requiredQty`, `unit`, `rate`, `gst`, `taxable`, `taxAmt`, `netAmt`, `igst`, `cgst`, `sgst`) VALUES (
                '".$_GET["plant_id"]."', '$last_id', '".$values["product_code"]."','".$values["product_name"]."', '".$values["hsn"]."', 
                '".$values["batch_no"]."','".$values["ar_no"]."', '".$values["mfg_date"]."', '".$values["exp_date"]."','".$values["pack_size"]."', 
                '".$values["requiredQty"]."', '".$values["unit"]."','".$values["rate"]."', '".$values["gst"]."', '".$values["taxable"]."',
                '".$values["taxAmt"]."', '".$values["netAmt"]."', '".$values["igst"]."','".$values["cgst"]."','".$values["sgst"]."')";
           
                $conn->query($sql1);
                
            }
             
            echo json_encode(array(
                'status' => 'success',
                'challan_no' => $nums['challan_no'],
                'invoice_no' => $nums['invoice_no'],
            ));
        } else {
            echo json_encode(array('status' => 'failed', 'msg' => $conn->error));
        }
        
    }
    else if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE product_type='".$_GET["product_type"]."'";
       
        // $sql = "SELECT * FROM product WHERE product_type='".$_GET["product_type"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT s.*,v.vendor_name FROM stock_book s LEFT JOIN vendor v ON s.vendor_no=v.vendor_no 
                WHERE s.product_code='".$row["product_code"]."' AND s.status IN ('Under Test','Approved')";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $used_qty = 0;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM material_issue WHERE ar_no='".$row1["ar_no"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                                $issue_qty += +$row2["qty"];
                                $used_qty += +$row2["qty"];
                            }
                        }
                        $row1["issued"] = $output2;
                        $row1["issue_qty"] = $used_qty;
                        $balance_qty = +$row1["qty"] - $used_qty;
                        $balance_qty = round($balance_qty, 2);
                        $row1["balance_qty"] = $balance_qty;
                        $row1["issues"] = $output2;
                        $output1[] = $row1;
                        
                        $received_qty += +$row1["qty"];
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["grns"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingOrders") {
        $output = array();
         $sql = "SELECT s.*, c.c_pincode as Pin, c.LglNm, c.c_address as Addr1, c.cr_address as Addr2, c.c_city as city, 
        c.gst_no, c.c_permanent_state as state_code FROM sales s LEFT JOIN 
        client c ON s.client_code=c.client_code   WHERE s.user_no='".$_GET["user_no"]."' 
        AND s.status='pending' ORDER BY id DESC";
        
             $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                                 

                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' 
                AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               // $row1["product_name"] = $row2['product_name'];
                                //$row1["product_type"] = $row2['product_type'];
                            }
                        }
                    //   $row1['sales_data'] =  json_decode($row1["sales_data"]);
                       $output1[] = $row1;
                    }
                }
                $row["client_name"] ='';
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        //$row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                
                    
                  $row['sales_data'] = json_decode($row["sales_data"], true);

                 

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingOrdersRaw") {
        $output = array();
        $sql = "SELECT s.*, c.c_pincode as Pin, c.LglNm, c.c_address as Addr1, c.cr_address as Addr2, c.c_city as city, 
        c.gst_no, c.c_permanent_state as state_code FROM sales s LEFT JOIN 
        client c ON s.client_code=c.client_code   WHERE s.order_type = 'RAW' AND s.user_no='".$_GET["user_no"]."' 
        AND s.status='pending' ORDER BY id DESC";
        
             $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' 
                AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               // $row1["product_name"] = $row2['product_name'];
                                //$row1["product_type"] = $row2['product_type'];
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
                        //$row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getCheckedOrders") {
        $output = array();
        $sql = "SELECT s.*, c.c_pincode as Pin, c.LglNm, c.c_address as Addr1, c.cr_address as Addr2, c.c_city as city, c.gst_no, c.c_permanent_state as state_code 
        FROM sales s LEFT JOIN client c ON s.client_code=c.client_code 
        WHERE s.user_no='".$_GET["user_no"]."' AND s.status='checking' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["product_name"] = $row2['product_name'];
                                  $row1["product_type"] = $row2['product_type'];
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
    else if ($_GET["type"] == "getCheckedOrdersRaw") {
        $output = array();
        $sql = "SELECT s.*, c.c_pincode as Pin, c.LglNm, c.c_address as Addr1, c.cr_address as Addr2, c.c_city as city, c.gst_no, c.c_permanent_state as state_code 
        FROM sales s LEFT JOIN client c ON s.client_code=c.client_code 
        WHERE s.order_type = 'RAW' AND s.user_no='".$_GET["user_no"]."' AND s.status='checking' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["product_name"] = $row2['product_name'];
                                  $row1["product_type"] = $row2['product_type'];
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateOrder") {
        
    echo     $sql = "UPDATE sales SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
           
            
                if ($_GET["status"] == "approve") {
                    
                    $json_obj = json_encode($input["wholeProducts"]);
                    $array = json_decode($json_obj, true);
                         
                    foreach ($array as $values)
                    {
                        
                       $sql1 = "INSERT INTO fg_material_issue (plant_id, material_code, batch_no, issue_for, qty, entry_by, entry_date,Invoice_no,client_no
                    ) VALUES ( '".$_GET["plant_id"]."', '".$values["product_code"]."', '".$values["batch_no"]."','Sales Order',
                    '".$values["sale_qty"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["Invoice_no"]."','".$input["client_no"]."')";
                   
                    $conn->query($sql1);
                        
                    }
                    
                    
                }
                
                 echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "updateOrderraw") {
        
        $sql = "UPDATE sales SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
            if ($_GET["status"] == "approve") {
                
                
                
                
                    $ars = $input["available_ars"];
            for ($i = 0; $i < count($ars); $i++) {
                $ar = $ars[$i];
               echo $sql1 = "INSERT INTO material_issue (plant_id,ar_no,grn_no, material_code, batch_no, issue_for, qty, unit, entry_by, entry_date)
                VALUES ( '".$_GET["plant_id"]."', '".$ar["ar_no"]."', '".$ar["grn_no"]."','".$ar["material_code"]."', '".$ar["batch_no"]."', 
                'Raw Sales Order', '".$ar["despensedQty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
                //echo $sql1;
                $conn->query($sql1);
            }
               
               
                
                
            }
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
    } 
    else if ($_GET["type"] == "getARNO") {
        
         $sql = "SELECT IFNULL(sum(qty),0) as qty1,pack_size,grn_date,exp_date,mfg_date,unit,material_code,batch_no,ar_no,grn_no
      FROM stock_book  WHERE material_code = '".$_GET["material_code"]."' GROUP BY  qty,pack_size,grn_date,exp_date,mfg_date,unit,material_code,batch_no,ar_no,grn_no";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                   $sql1 = "SELECT IFNULL(sum(qty),0) as issueqty FROM  material_issue   WHERE ar_no = '".$row["ar_no"]."'";
                      $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row['issueqty'] = $row1['issueqty'];
                        }
                    }
                
                $balance_qty = $row['qty1'] - $row['issueqty']; 
                 $formatted_balance_qty = number_format((float) $balance_qty, 2, '.', '');
                $row['balance_qty'] = $formatted_balance_qty;
                
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getARNOfinished") {
        
         $sql = "SELECT IFNULL(sum(qty),0) as qty1,exp_date,mfg_date,unit,material_code,batch_no,ar_no
      FROM fg_stock_book  WHERE batch_no = '".$_GET["batch_no"]."' GROUP BY  qty,exp_date,mfg_date,unit,material_code,batch_no,ar_no";
          $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                   $sql1 = "SELECT IFNULL(sum(qty),0) as issueqty FROM  fg_material_issue   WHERE batch_no = '".$row["batch_no"]."'";
                      $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row['issueqty'] = $row1['issueqty'];
                        }
                    }
                
                $balance_qty = $row['qty1'] - $row['issueqty']; 
                 $formatted_balance_qty = number_format((float) $balance_qty, 2, '.', '');
                $row['balance_qty'] = $formatted_balance_qty;
                
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    
    else if ($_GET["type"] == "getOrdersLog") {
        $output = array();
        $sql = "SELECT s.*,c.LglNm as clientName FROM sales s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."' ORDER BY s.id DESC";
        
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
        else if ($_GET["type"] == "getSalesOrderForChecking") {
        $output = array();
        $sql = "SELECT s.*,c.LglNm as clientName FROM sales s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."' AND s.status = 'Pending' ORDER BY s.id DESC";
        
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getSalesOrderForApproval") {
        $output = array();
        $sql = "SELECT s.*,c.LglNm as clientName FROM sales s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."' AND s.status = 'Checked' ORDER BY s.id DESC";
        
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
        else if ($_GET["type"] == "correctionOfSalesOrder") {
        
        $sql = "UPDATE sales SET status='".$_GET["status"]."', taxableTotal = '".$input["taxableTotal"]."',
        taxAmtTotal = '".$input["taxAmtTotal"]."', netTotal = '".$input["netTotal"]."',igstTotal = '".$input["igstTotal"]."',
        sgstTotal = '".$input["sgstTotal"]."', cgstTotal = '".$input["cgstTotal"]."',correctionBy = '".$_GET["emp_id"]."',
        correctionOn = '$entry_date' WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            
            $array = $input["products"];
            foreach ($array as $values)
            {
                $sql1 = "UPDATE `sales_product` SET `requiredQty` = '".$values["requiredQty"]."', `rate` = '".$values["rate"]."', 
                `taxable` = '".$values["taxable"]."',`taxAmt` = '".$values["taxAmt"]."', `netAmt` = '".$values["netAmt"]."', 
                `igst` = '".$values["igst"]."',`cgst` = '".$values["cgst"]."', `sgst` = '".$values["sgst"]."' WHERE id = '".$values["id"]."'";
           
                $conn->query($sql1);
            }
            
                 echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 

    
        else if ($_GET["type"] == "getSalesOrderForCorrection") {
        $output = array();
        $sql = "SELECT s.*,c.LglNm as clientName FROM sales s LEFT JOIN client c ON s.client_code = c.client_code WHERE
        s.plant_id='".$_GET["plant_id"]."' AND s.status = 'Correction' ORDER BY s.id DESC";
        
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
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    
        else if ($_GET["type"] == "approveSalesOrder") {
        
         $sql = "UPDATE sales SET status='".$_GET["status"]."', approveBy='".$_GET["emp_id"]."', approve_date = '$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
                if ($_GET["status"] == "Approved") {
                    
                    $array = $input["products"];
                    foreach ($array as $values)
                    {
                        
                        $sql1 = "INSERT INTO `fg_material_issue`(`plant_id`, `ar_no`, `material_code`, `batch_no`, `issue_for`, `qty`, `unit`, 
                        `entry_by`, `entry_date`, `Invoice_no`, `client_no`) VALUES ('".$_GET["plant_id"]."', '".$values["ar_no"]."',
                        '".$values["product_code"]."', '".$values["batch_no"]."','Sales','".$values["requiredQty"]."', '".$values["unit"]."',
                        '".$_GET["emp_id"]."','$entry_date','".$input["Invoice_no"]."', '".$input["client_code"]."')";
                   
                        $conn->query($sql1);
                         
                    }
                }
                 echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 


    else if ($_GET["type"] == "CheckSalesOrder") {
         $sql = "UPDATE sales SET status='".$_GET["status"]."', checkBy = '".$_GET["emp_id"]."', checkOn = '$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
                 echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 

    
    
    else if ($_GET["type"] == "getOrdersLograw") {
        $output = array();
        
        $sql = "SELECT s.*, c.c_pincode, c.LglNm, c.c_address, c.cr_address, c.c_city, c.gst_no, c.c_permanent_state FROM sales s 
        LEFT JOIN client c ON s.client_code=c.client_code LEFT JOIN state s1 ON c.state_code=s1.state_code 
        WHERE s.order_type = 'RAW' AND s.user_no='".$_GET["user_no"]."' ORDER BY id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' 
                AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                               // $row1["product_name"] = $row2['product_name'];
                                //$row1["product_type"] = $row2['product_type'];
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
                        //$row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getApprovedOrdersLog") {
        $output = array();
        
        $sql = "SELECT s.*, c.Pin, c.LglNm, c.Addr1, c.Addr2, c.Loc, c.gst_no, s1.state_name 
        FROM sales s LEFT JOIN client c ON s.client_code=c.client_code LEFT JOIN state s1 ON
        c.state_code=s1.state_code WHERE s.status='approve' and s.user_no='".$_GET["user_no"]."' 
        AND s.client_code LIKE '%".$_GET['client_code']."' AND DATE(s.entry_date) 
        BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."' ORDER BY s.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["product_name"] = $row2['product_name'];
                                $row1["product_type"] = $row2['product_type'];
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
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "saveOrder") {
        $sql = "INSERT INTO sales (user_no, client_code, branch, pay_mode, po_no, po_date, gross_total, disc_total, tax_total, net_total, entry_by, entry_date) VALUES 
        ('".$_GET["user_no"]."', '".$input["client_code"]."', '".$input["branch"]."', '".$input["pay_mode"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["gross_total"]."', '".$input["disc_total"]."', '".$input["tax_total"]."', '".$input["net_total"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $order_no = "";
            $sql1 = "SELECT order_no FROM sales WHERE id='$last_id'";
            $result1 = $conn->query($sql1);
            while ($row = $result1->fetch_assoc()) {
                $order_no = $row["order_no"];
            }
           
            $materials = $input["materials"];
            
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql = "INSERT INTO sales_product (user_no, order_no, product_code, batch_no, qty, f_qty, sale_rate, mrp, disc_per, gst_per, gross_total, disc_total, tax_total, net_total) VALUES ('".$_GET["user_no"]."', '$order_no', '".$material["product_code"]."', '".$material["batch_no"]."', '".$material["purchase_qty"]."', '".$material["f_qty"]."', '".$material["sale_rate"]."', '".$material["mrp"]."', '".$material["disc_per"]."', '".$material["gst_per"]."', '".$material["gross_total"]."', '".$material["disc_total"]."', '".$material["tax_total"]."', '".$material["net_total"]."')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveLoan") {
        $sql = "INSERT INTO sales (user_no, client_code,branch, pay_mode, materials, entry_by, entry_date,order_type,gross_total, disc_total, tax_total, net_total) VALUES ('".$_GET["user_no"]."', '".$input["client_code"]."','".$input["branch"]."', '".$input["pay_mode"]."', '".json_encode($input["materials"])."', '".$_GET["emp_id"]."', '$entry_date','Loan','".$input["gross_total"]."', '".$input["disc_total"]."', '".$input["tax_total"]."', '".$input["net_total"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $order_no = "";
            $sql1 = "SELECT order_no FROM sales WHERE id='$last_id'";
            $result1 = $conn->query($sql1);
            while ($row = $result1->fetch_assoc()) {
                $order_no = $row["order_no"];
            }
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql = "INSERT INTO sales_product (user_no, order_no, product_code, batch_no, qty, f_qty, sale_rate, mrp, disc_per, gst_per, gross_total, disc_total, tax_total, net_total) VALUES ('".$_GET["user_no"]."', '$order_no', '".$material["product_code"]."', '".$material["batch_no"]."', '".$material["purchase_qty"]."', '".$material["f_qty"]."', '".$material["sale_rate"]."', '".$material["mrp"]."', '".$material["disc_per"]."', '".$material["gst_per"]."', '".$material["gross_total"]."', '".$material["disc_total"]."', '".$material["tax_total"]."', '".$material["net_total"]."')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingLoan") {
        $output = array();
        $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Loan' AND status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
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
                $row["client_name"] ='';
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLoanLog") {
        $output = array();
        $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Loan' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
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
                $row["client_name"] ='';
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveThirdParty") {
        $sql = "INSERT INTO sales (user_no, client_code,branch, pay_mode, materials, entry_by, entry_date,order_type,gross_total, disc_total, tax_total, net_total) VALUES ('".$_GET["user_no"]."', '".$input["client_code"]."','".$input["branch"]."', '".$input["pay_mode"]."', '".json_encode($input["materials"])."', '".$_GET["emp_id"]."', '$entry_date','Third','".$input["gross_total"]."', '".$input["disc_total"]."', '".$input["tax_total"]."', '".$input["net_total"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $order_no = "";
            $sql1 = "SELECT order_no FROM sales WHERE id='$last_id'";
            $result1 = $conn->query($sql1);
            while ($row = $result1->fetch_assoc()) {
                $order_no = $row["order_no"];
            }
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql = "INSERT INTO sales_product (user_no, order_no, product_code, batch_no, qty, f_qty, sale_rate, mrp, disc_per, gst_per, gross_total, disc_total, tax_total, net_total) VALUES ('".$_GET["user_no"]."', '$order_no', '".$material["product_code"]."', '".$material["batch_no"]."', '".$material["purchase_qty"]."', '".$material["f_qty"]."', '".$material["sale_rate"]."', '".$material["mrp"]."', '".$material["disc_per"]."', '".$material["gst_per"]."', '".$material["gross_total"]."', '".$material["disc_total"]."', '".$material["tax_total"]."', '".$material["net_total"]."')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    } else if ($_GET["type"] == "getPendingThirdParty") {
        $output = array();
        $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Third' AND status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
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
                $row["client_name"] ='';
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getThirdPartyLog") {
        $output = array();
        $sql = "SELECT * FROM sales WHERE user_no='".$_GET["user_no"]."' AND order_type='Third' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
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
                $row["client_name"] ='';
                $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["client_name"] = $row2['company'];
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }else if($_GET['type'] == "jadu") {
        $_GET["order_no"] = '';
        $_GET["order_date"] = '';
        $_GET["po_no"] = "";
        $_GET["po_date"] = "";
        $_GET["net_total"] = "";

        $_GET["user_company"] = "";
        
        $sql = "SELECT * FROM user WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET["user_company"] = $row["company_name"];
            }
        }

        $sql = "SELECT *, DATE(entry_date) as entry_date, DATE(po_date) as po_date FROM sales WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result-> num_rows > 0) {
            while ($row = $result-> fetch_assoc()) {
                $_GET["order_no"] = $row["order_no"];
                $_GET["order_date"] = $row["entry_date"];
                $_GET["po_no"] = $row["po_no"];
                $_GET["po_date"] = $row["po_date"];
                $_GET["net_total"] = $row["net_total"];
                $sql2 = "SELECT * FROM client WHERE client_code = '".$row["client_code"]."' LIMIT 1";
                $result2 = $conn->query($sql2);
                if ($result2-> num_rows > 0) {
                    while ($row2 = $result2-> fetch_assoc()) {
                        $row["person"] = $row2["person"];
                        $row["phone"] = $row2["phone"];
                        $row["email"] = $row2["email"];
                        $row["gst_type"] = $row2["gst_type"];
                    }
                }

                $_GET['regd_address'] = 'R.O:A-1201, Mondeal, Nr. Wide Angle, S.G. Highway, Ahmedabad';
                $_GET['regd_state'] = 'Gujarat';
                $_GET['regd_phone'] = '(+91)-(79)-49030903';
                $_GET['regd_website'] = 'www.finecurepharma.com';
                
                $_GET['address'] = "Plot No 25, Avas Vikas, Rudrapur -- 263153";
                $_GET['state'] = "Uttarakhand";
                $_GET['state_code'] = "05";
                
                $_GET['dl_no1'] = "20B-OBW-9/USN/FEB/2008";
                $_GET['dl_no2'] = "21B-BW-9/USN/FEB/2008";
                $_GET['gstin'] = "05AAACF9157A1ZZ";
                $_GET['pan'] = "AAACF9157A";
                $_GET['iec'] = "0806018437";
                $_GET['cin'] = "U24230GJ2005PLC45724";
        
        
                $_GET['BUYER'] = "PROFORMA INVOICE 303, THIRD EYE ONE";
                $_GET['CITY'] = "AHMEDABAD - 38006";
                $_GET['STATE'] = "Gujarat";
                
                $_GET['PHONENO'] = "";
                $_GET['BUYER_DLNO1'] = "";
                $_GET['BUYER_DLNO2'] = "";
                $_GET['BUYER_GSTIN'] = "";
                $_GET['BUYER_PAN'] = "";
                $_GET['STATE_CODE'] = 24;
                $_GET['CONSIGNEE'] = "PROFORMA INVOICE";
                $_GET['SO_NO'] = "1010031680";
                $_GET['SO_DATE'] = "04.09.2019";
                $_GET['PO_NO'] = "PROFORMA INVOICE";
                $_GET['PO_DATE'] = "04.06.2019";
                $_GET['TAX_TYPE'] = "IGST";
                $_GET['INCO_TERMS'] = "FOB";
                $_GET['SALES_TYPE'] = "Direct Sales Order";
                $_GET['PAYT_TERMS'] = "Immediate Payment";
        
                class MYPDF extends TCPDF {
                    public function Header() {
                        $html = '
                            <table border="1" cellpadding="5">
                                <tr>
                                    <td rowspan="2">
                                        <img src="header.png" style="height: 35px;"><br>
                                        <b>Regd. Office:</b><br>
                                        '.$_GET['regd_address'].'<br>
                                        State: '.$_GET['regd_state'].'<br>
                                        Phone: '.$_GET['regd_phone'].'<br>
                                        Website: '.$_GET['regd_website'].'
                                    </td>
                                    <td style="text-align:center;"><h1>SALES ORDER</h1></td>
                                    <td rowspan="2">
                                        <table>
                                            <tbody style="width: 100%;">
                                                <tr>
                                                    <td style="width: 20%;">DL NO.</td>
                                                    <td style="width: 10%;">:</td>
                                                    <td style="width: 70%;">'.$_GET['dl_no1'].'</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td style="width: 20%;">DL NO.</td>
                                                    <td style="width: 10%;">:</td>
                                                    <td style="width: 70%;">'.$_GET['dl_no2'].'</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td style="width: 20%;">GSTIN</td>
                                                    <td style="width: 10%;">:</td>
                                                    <td style="width: 70%;">'.$_GET['gstin'].'</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td style="width: 20%;">PAN</td>
                                                    <td style="width: 10%;">:</td>
                                                    <td style="width: 70%;">'.$_GET['pan'].'</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td style="width: 20%;">IEC</td>
                                                    <td style="width: 10%;">:</td>
                                                    <td style="width: 70%;">'.$_GET['iec'].'</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td style="width: 20%;">CIN</td>
                                                    <td style="width: 10%;">:</td>
                                                    <td style="width: 70%;">'.$_GET['cin'].'</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td><b>SUPPLYING LOCATION: <br><br>'.$_GET['address'].'<br>State:'.$_GET['state'].' &nbsp;&nbsp;&nbsp;State Code: 27
                                        </b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table>
                                            <tr>
                                                <td><b>BUYER:</b><br>'.$_GET['BUYER'].'<br><br>City: '.$_GET['CITY'].'<br><br>State: '.$_GET['STATE'].'
                                                </td>
                                                <td>
                                                    <table>
                                                        <tr>
                                                            <td>Ph No</td>
                                                            <td>:</td>
                                                            <td>'.$_GET['PHONENO'].'</td>
                                                        </tr>
                                                        <tr>
                                                            <td>DL No</td>
                                                            <td>:</td>
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            <td>DL No</td>
                                                            <td>:</td>
                                                            <td>'.$_GET['BUYER_DLNO2'].'</td>
                                                        </tr>
                                                        <tr>
                                                            <td>GSTIN</td>
                                                            <td>:</td>
                                                            <td>'.$_GET['BUYER_GSTIN'].'</td>
                                                        </tr>
                                                        <tr>
                                                            <td>PAN</td>
                                                            <td>:</td>
                                                            <td>'.$_GET['BUYER_PAN'].'</td>
                                                        </tr>
                                                        <tr><td></td></tr>
                                                        <tr>
                                                            <td>State Code</td>
                                                            <td>:</td>
                                                            <td>'.$_GET['STATE_CODE'].'</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    
                                    </td>
                                    <td>
                                        <b>CONSIGNEE:</b><br>
                                        '.$_GET['CONSIGNEE'].'
                                    </td>
                                    <td>
                                        <table>
                                            <tr>
                                            <td>
                                                <table>
                                                
                        
                                                    <tr>
                                                        <td style="width: 30%;">SO No.</td>
                                                        <td style="width: 10%;">:</td>
                                                        <td style="width: 60%;">'.$_GET['order_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 30%;">SO Date</td>
                                                        <td style="width: 10%;">:</td>
                                                        <td style="width: 60%;">'.$_GET['order_date'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 30%;">PO No.</td>
                                                        <td style="width: 10%;">:</td>
                                                        <td style="width: 60%;">'.$_GET['po_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 30%;">PO Date</td>
                                                        <td style="width: 10%;">:</td>
                                                        <td style="width: 60%;">'.$_GET['po_date'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td>
                                                <table>
                                                    <tr>
                                                        <td style="width: 40%;">Tax Type</td>
                                                        <td style="width: 5%;">:</td>
                                                        <td style="width: 55%;">'.$_GET['TAX_TYPE'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 40%;">Inco Terms</td>
                                                        <td style="width: 5%;">:</td>
                                                        <td style="width: 55%;">'.$_GET['INCO_TERMS'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 40%;">Sales Type</td>
                                                        <td style="width: 5%;">:</td>
                                                        <td style="width: 55%;">'.$_GET['SALES_TYPE'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 40%;">Pymt Terms</td>
                                                        <td style="width: 5%;">:</td>
                                                        <td style="width: 55%;">'.$_GET['PAYT_TERMS'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        ';
                        
                        $this->SetFont('times', '', 8);
                        $this->SetY(5);
                        $this->SetX(15);
                        $this->MultiCell(0, 50, $html, 1, '', 0, 0, '', '', true, 0, true, true, 40, 'T');
                    }
                    public function Footer() {
                        
                        $html = '
                            <table border="1" cellpadding="3">
                              <tr>
                                <th style="width: 5%; text-align: center;" rowspan="2">TAX TYPE</th>
                                <th style="width: 15%; text-align: center;" colspan="2">TAX ON TAXABLE GOODS</th>
                                <th style="width: 15%; text-align: center;" colspan="2">TAX ON FREE GOODS</th>
                                <th style="width: 15%; text-align: center;" colspan="2">TAX ON INCIDENTAL CHGS</th>
                                <th style="width: 6.90%; text-align: center;" rowspan="2">Total Tax</th>
                                <th style="width: 19%; text-align: left;" rowspan="5">WHETHER TAX IS PAYABLE ON REVERSE CHARGE BASIS: NO</th>
                                <td style="width: 15%; text-align: center;" rowspan="6"></td>
                                <td rowspan="6"></td>
                              </tr>
                              <tr>
                                <th style="text-align: center;">Value</th>
                                <th style="text-align: center;">Tax</th>
                                <th style="text-align: center;">Value</th>
                                <th style="text-align: center;">Tax</th>
                                <th style="text-align: center;">Value</th>
                                <th style="text-align: center;">Tax</th>
                              </tr>
                              <tr>
                                <th>CGST</th>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                              </tr>
                              <tr>
                                <th>SGST</th>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                              </tr>
                              <tr>
                                <th>IGST</th>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                              </tr>
                              <tr>
                                <td colspan="9">NOTE:</td>
                              </tr>
                              <tr>
                                <td colspan="9"></td>
                                <td>SO VALUE</td>
                                <td style="text-align: right;">'.$_GET["net_total"].'</td>
                              </tr>
                              <tr>
                              <td style="text-align: right;" colspan="11">For, '.$_GET["user_company"].'<br><br>Authrised Signatory.
                              </td>
                              </tr>
                              
                            </table>';
                        $this->SetFont('times', '', 8);
                        $this->SetY(-55);
                        
                        $this->MultiCell(0, 0, $html, 1, '', 0, 0, '', '', true, 0, true, true, 40, 'T');
                        //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' OF '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(PDF_MARGIN_LEFT, 65, PDF_MARGIN_RIGHT);
                $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                $pdf->SetFooterMargin(55);
                $pdf->SetAutoPageBreak(TRUE, 55);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->SetFont('times', '', 8);
                $pdf->AddPage('L', 'A4');
                $html='
                <table border="1" style="width: 100%;" cellpadding="5">
                    <tbody>
                    <tr style="text-align: center; background-color: #ddd;">
                        <th style="width:3%;" rowspan="2"><b>Sr</b></th>
                        <th style="width:4%;" rowspan="2"><b>HSN Code</b></th>
                        <th style="width:26.33%;" rowspan="2"><b>Description</b></th>
                        <th style="width:10%;" rowspan="2"><b>Total Qty.</b></th>
                        <th style="width:3.5%;" rowspan="2"><b>Rate</b></th>
                        <th style="width:5%;" rowspan="2"><b>Value</b></th>
                        <th style="width:3.5%;" rowspan="2"><b>Disc<br>%</b></th>
                        <th rowspan="2"><b>Discount</b></th>
                        <th rowspan="2"><b>Taxable Value</b></th>
                        <th style="width: 9%;" colspan="2"><b>SGST</b></th>
                        <th style="width: 9%;" colspan="2"><b>CGST</b></th>
                        <th style="width: 9%;" colspan="2"><b>IGST</b></th>
                        <th rowspan="2"><b>Total Value</b></th>
                    </tr>
                    <tr style="text-align: center; background-color: #ddd;">
                        <th style="width: 3.5%;"><b>Rate</b></th>
                        <th style="width: 5.5%;"><b>Amount</b></th>
                        <th style="width: 3.5%;"><b>Rate</b></th>
                        <th style="width: 5.5%;"><b>Amount</b></th>
                        <th style="width: 3.5%;"><b>Rate</b></th>
                        <th style="width: 5.5%;"><b>Amount</b></th>
                    </tr>';
                    $counter = 1;
                $sql2 = "SELECT * FROM sales_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["order_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2-> fetch_assoc()) {
                        $sql3 = "SELECT * FROM product WHERE product_code='".$row2["product_code"]."'";
                        $result3 = $conn->query($sql3);
                        if ($result3->num_rows > 0) {
                            while ($row3 = $result3->fetch_assoc()) {
                                $row2["product_name"] = $row3['product_name'];
                                $row2["hsn_code"] = $row3['hsn'];
                            }
                        }
                        $html.='
                            <tr nobr="true">
                                <td style="text-align: center;">'.$counter++.'</td>
                                <td>'.$row2["hsn_code"].'</td>
                                <td>'.$row2["product_name"].'</td>
                                <td>'.$row2["qty"].'</td>
                                <td style="text-align: right;">'.$row2["sale_rate"].'</td>
                                <td style="text-align: right;">'.$row2["gross_total"].'</td>
                                <td style="text-align: right;">'.$row2["disc_per"].'</td>
                                <td style="text-align: right;">'.$row2["disc_total"].'</td>
                                <td style="text-align: right;">'.$row2["taxable"].'</td>
                                <td style="text-align: right;">'.($row2["gst_per"] / 2).'</td>
                                <td style="text-align: right;">'.$row2["SgstAmt"].'</td>
                                <td style="text-align: right;">'.($row2["gst_per"] / 2).'</td>
                                <td style="text-align: right;">'.$row2["CgstAmt"].'</td>
                                <td style="text-align: right;">'.$row2["gst_per"].'</td>
                                <td style="text-align: right;">'.$row2["IgstAmt"].'</td>
                                <td style="text-align: right;">'.$row2["net_total"].'</td>
                            </tr>';
                    }
                }
                    $html.='
                    </tbody>
                </table>';
                $pdf->writeHTML($html, true, false, true, false, '');
                $file = 'invoice.pdf';
                $pdf->Output($file, 'I');
            }
        } 
                else if ($_GET["type"] == "downloadOrder1") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='<h3 style="text-align:center;">Sales Orders</h3>
            <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width:15%; text-align:centre;"><b>Order No</b></td>
                    <td style="width:15%; text-align:centre;"><b>For Client/Party</b></td>
                    <td style="width:20%; text-align:centre;"><b>Deleivery To Address</b></td>
                    <td style="width:15%; text-align:centre;"><b>	Qty To be Dispatch</b></td>
                    <td style="width:15%; text-align:centre;"><b>Amount</b></td>
                    <td style="width:15%; text-align:centre;"><b>Status</b></td>	
                </tr>';
            
        $html.='</table>';
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('sales orders.pdf', 'I');
    }

        else if ($_GET["type"] == "downloadOrder") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='<h3 style="text-align:center;">Sales Orders</h3>
            <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width:15%; text-align:centre;"><b>Order No</b></td>
                    <td style="width:15%; text-align:centre;"><b>For Client/Party</b></td>
                    <td style="width:20%; text-align:centre;"><b>Deleivery To Address</b></td>
                    <td style="width:15%; text-align:centre;"><b>	Qty To be Dispatch</b></td>
                    <td style="width:15%; text-align:centre;"><b>Amount</b></td>
                    <td style="width:15%; text-align:centre;"><b>Status</b></td>	
                </tr>
            </thead>';
            $i=1;
                $output = Array();
                
               //$sql = "SELECT * FROM sales";
                $sql = "SELECT s.*, c.Pin, c.LglNm, c.Addr1, c.Addr2, c.Loc, c.gst_no, s1.state_name FROM sales s LEFT JOIN client c ON s.client_code=c.client_code LEFT JOIN state s1 ON c.state_code=s1.state_code WHERE s.user_no='".$_GET["user_no"]."' AND s.client_code LIKE '%".$_GET['client_code']."' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."' ORDER BY s.id DESC";
               // echo $sql;
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                            <td style="width: 5%;">'.$i.'</td>
                            <td style="width: 15%;">'.$row['order_no'].'</td>
                            <td style="width: 15%;">'.$row['for_client'].'</td>
                            <td style="width: 20%;">'.$row['deleivery_address'].'</td>
                            <td style="width: 15%;">'.$row['qty'].'</td>
                            <td style="width: 15%;">'.$row['amount'].'</td>
                            <td style="width: 15%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.='</table>';
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('sales orders.pdf', 'I');
    }
    }/*else(){
        echo '$result';
    }*/
}  
$conn->close();
?>