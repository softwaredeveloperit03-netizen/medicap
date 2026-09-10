<?php

require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';

//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);


if($result->num_rows > 0) {
        
    while($row = $result-> fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR) VALUES ('FRONTEND', '".$token."', '".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "addClient") {
        
        $sql = "SELECT IFNULL(MAX(i_no), 0) as i_no FROM client";
        $result = $conn->query($sql);
        $i_no = 0;
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
            }
        }
        
        $i_no++;
        $num = strlen($i_no);
        if ($num == 1) {
            $client_code = "client0".$i_no;
        } else if ($num == 2) {
            $client_code = "client".$i_no;
        }
        
        $sql = "INSERT INTO client (client_code, company, person, email, phone, gst_type, gst_no, address, country, enquiry, website, state_code, pan_no, contact_person, contact_phone, i_no) VALUES
        ('$client_code','".$_POST["company"]."','".$_POST["person"]."','".$_POST["email"]."','".$_POST["phone"]."','".$_POST["gst_type"]."',
        '".$_POST["gst_no"]."','".$_POST["address"]."','".$_POST["country"]."','".$_POST["enquiry"]."','".$_POST["website"]."', 
        '".$_POST["state_code"]."', '".$_POST["pan_no"]."', '".$_POST["contact_person"]."', '".$_POST["contact_phone"]."', '$i_no')";
        
        if($conn->query($sql)===TRUE) {
            echo "{\"status\":\"success\"}";
        }
        else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getClients") {
        
        $sql = "SELECT * FROM client";
        $result = $conn->query($sql);
        $data = array();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                $data[] = $row;
            }
        }
        echo json_encode($data);
        
   } else if ($_GET["type"] == "getSalesOrderData") {
        
        $sql = "SELECT * FROM sales_order_products";
        $result = $conn->query($sql);
        $data = array();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                $data[] = $row;
            }
        }
        echo json_encode($data);
        
 } 
         else if ($_GET["type"] == "getStockProducts") {
             
                  $data = array();
                $sql = "SELECT id,product_code,product_type,product_name  FROM product  WHERE plant_id='".$_GET["plant_id"]."'  order by 1 desc";
                $result = $conn->query($sql);
                if ($result -> num_rows > 0) {
                    while ($row = $result-> fetch_assoc()) {
                        $total_avaliable = 0;
                        $data1 = array();
                          $sql1 = "SELECT *  FROM fg_stock_book  WHERE material_code='".$row["product_code"]."'";
                            $result1 = $conn->query($sql1);
                            if ($result1 -> num_rows > 0) {
                                while ($row1 = $result1-> fetch_assoc()) {
                                         $sql12 = "SELECT  IFNULL(sum(qty),0) as  qty1 FROM  fg_material_issue  WHERE batch_no='".$row1["batch_no"]."' AND material_code='".$row1["product_code"]."'";
                                            $result12 = $conn->query($sql12);
                                            if ($result12 -> num_rows > 0) {
                                                while ($row12 = $result12-> fetch_assoc()) {
                                                    
                                                    $row1["isue_qty"] = $row12["qty1"];
                                                    
                                                }
                                            
                                    $row1["avaliable_qty"] = $row1["qty"] - $row1["isue_qty"];
                                    $total_avaliable += $row1["avaliable_qty"];
                                    $data1[] = $row1;
                                    
                                }
                                
                                $row['mfg_date'] = $row1['mfg_date'];
                                $row['exp_date'] = $row1['exp_date'];
                            }
                        
                    }else{
                        $row1["avaliable_qty"] = 0;
                        $row1["mfg_date"] = 'NA';
                        $row1["exp_date"] = 'NA';
                    }
                    
                        $row["data"] =  $data1;
                        $row["total_avaliable"] =  $total_avaliable;
                        $data[] = $row;
                }
                
                echo json_encode($data);
                
            } 
         }
    else if ($_GET["type"] == "getStockBy_Batch") {
        $output = Array();
        $sql12 = "SELECT *  FROM  fg_material_issue  WHERE material_code='".$_GET["product_code"]."' AND batch_no='".$_GET["batch_no"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result12 = $conn->query($sql12);
        if ($result12 -> num_rows > 0) {
            while ($row12 = $result12-> fetch_assoc()) {
                    $output[] = $row12;                                                    
            }
        }
        echo json_encode($output);
    } 
      else if ($_GET["type"] == "getDispatchProducts") {
        $output = Array();
        $sql = "SELECT * FROM product order by id desc"; 
        //GROUP BY product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               // $received_qty = 0;
                //$issue_qty = 0;
                //$balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT * FROM fg_stock_book"; 
                //$sql1 = "SELECT s.*,v.vendor_name FROM fg_stock_book s LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.product_code='".$row["product_code"]."' AND s.status IN ('Under Test','Approved')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        //$used_qty = 0;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM sales_product WHERE product_code='".$row1["product_code"]."' 
                        AND batch_no='".$row1["batch_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                //$output2[] = $row2;
                               // $issue_qty += +$row2["order_qty"];
                               // $used_qty += +$row2["order_qty"];
                            }
                        }
                      // $row1["issued"] = $output2;
                      // $row1["issue_qty"] = $used_qty;
                       // $balance_qty = +$row1["qty"] - $used_qty;
                        //$balance_qty = round($balance_qty, 2);
                       // $row1["balance_qty"] = $balance_qty;
                       // $row1["issues"] = $output2;
                        $output1[] = $row1;
                        
                        //$received_qty += +$row1["qty"];
                    }
                  // $balance_qty = $received_qty - $issue_qty;
                  // $balance_qty = round($balance_qty, 2);
                   // $row["received_qty"] = $received_qty;
                    //$row["issue_qty"] = $issue_qty;
                    //$row["balance_qty"] = $balance_qty;
                    //$row["grns"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
 else if ($_GET["type"] == "getDispatchProductsByDate") {
     
        $sql = "SELECT fp.*, p.product_name, p.generic_name, p.label_claim FROM finish_product fp JOIN product p ON fp.product_code = p.product_code";
        $result = $conn->query($sql);
        $data = array();
         
        if ($result -> num_rows > 0) {
            while ($row = $result-> fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        echo json_encode($data);
        
 }
 else if ($_GET["type"] == "saveDataLogger") {
     
     $sql="INSERT INTO Data_Logger (dataLoggerNumber,numberOfChannels,plant_id,entry_by,entry_date) 
    Values('".$input["dataLoggerNumber"]."','".$input["numberOfChannels"]."','".$_GET["plant_id"]."','".$_GET["emp_id"]."','$entry_date')";
	if($conn->query($sql)) 
	{
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"failed\"}";
    }
        
 }
  else if ($_GET["type"] == "getDataLogger") {
     
     $sql = "SELECT * from Data_Logger";
        $result = $conn->query($sql);
        $data = array();
         
        if ($result -> num_rows > 0) {
            while ($row = $result-> fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        echo json_encode($data);
        
 }
  else if ($_GET["type"] == "deleteDataLogger") {
     
     $sql = "delete from Data_Logger where id='".$_GET["ID"]."'";
     if($conn->query($sql)) 
	{
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"failed\"}";
    }
        
        

 }
  else if ($_GET["type"] == "saveDataLoggerMaster") {
     
     $sql="INSERT INTO Data_Logger_Master (tax_invoice_number,vehicle_number,destination,data_logger_number,placed_by,timing_of_insertion,vehicle_start_time,operation_condition_confirm,entry_by,entry_date,plant_id) 
    Values('".$input["tax_invoice_number"]."','".$input["vehicle_number"]."','".$input["destination"]."','".$input["data_logger_number"]."','".$input["placed_by"]."','".$input["timing_of_insertion"]."',
    '".$input["vehicle_start_time"]."','".$input["operation_condition_confirm"]."','$entry_date','".$_GET["emp_id"]."','".$_GET["plant_id"]."')";
	if($conn->query($sql)) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"failed\"}";
    }
        
 }
 else if ($_GET["type"] == "getDataLoggerMaster") {
     
       $sql = "SELECT * from Data_Logger_Master";
        $result = $conn->query($sql);
        $data = array();
         
        if ($result -> num_rows > 0) {
            while ($row = $result-> fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        echo json_encode($data);
        
 }
 else if ($_GET["type"] == "deleteDataLoggerMaster") {
     
     $sql = "delete from Data_Logger_Master where id='".$_GET["ID"]."'";
     if($conn->query($sql)) 
	{
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"failed\"}";
    }
        
        

 }
 else if($_GET["type"] == "saveSalesOrder") {
        $sql = "SELECT IFNULL(MAX(id), 0) as id FROM sales_order LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result-> num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row["id"];
            }
        }
        
        $id++;
        
        $length = strlen($id);
        if ($length == 3) {
            $order_id = "OD0".$id;
        } else if ($length == 2) {
            $order_id = "OD00".$id;
        } else {
            $order_id = "OD000".$id;
        }
        
        $taxable = 0;
        $tax = 0;
        $total = 0;
        
        $sql = "INSERT INTO sales_order (order_id, client_code, emp_id, entry_date, taxable, tax, total) VALUES 
        ('$order_id', '".$_POST["client_code"]."', '".$_GET["emp_id"]."', '$entry_date', '$taxable', '$tax', '$total')";
        
        if ($conn->query($sql) === TRUE) {
            
            $flag = 0;
            $products = json_decode($_POST["products"], true);
            $length = sizeof($products);
        	
        	for($i = 0; $i < $length; $i++) {
        	    $data = $products[$i];
        	    
        	    $sql = "INSERT INTO sales_order_products(order_id, product_code, hsn, description, new_material, total_qty, rate, value, disc_per, discount, taxable, sgst_rate, sgst_amount, cgst_rate, cgst_amount, igst_rate, igst_amount, total_value) VALUES 
                ('$order_id', '".$data["product_code"]."', '".$data["hsn"]."', '".$data["description"]."', '".$data["new_material"]."', ".$data["total_qty"].", ".$data["rate"].", ".$data["value"].", ".$data["disc_per"].", ".$data["discount"].", ".$data["taxable"].", ".$data["sgst_rate"].", ".$data["sgst_amount"].", ".$data["cgst_rate"].", ".$data["cgst_amount"].", ".$data["igst_rate"].", ".$data["igst_amount"].", ".$data["total_value"].")";
            
        	    if ($conn->query($sql) === TRUE) {
        	        $flag = 0;
        	    } else {
        	        $flag = 1;
        	    }
        	}
        	
        	if ($flag == 0) {
                echo "{\"status\":\"success\"}";
        	} else {
        	    echo "{\"status\":\"".$conn->error."\"}";
        	}
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } else if ($_GET["type"] == "getSalesOrders") {
        $output = array();
        
        $sql = "SELECT s.*, c.c_pincode, c.LglNm, c.c_address, c.cr_address, c.c_city, c.gst_no, c.c_permanent_state ,c.email,c.phone,c.person FROM sales s 
        LEFT JOIN client c ON s.client_code=c.client_code LEFT JOIN state s1 ON c.state_code=s1.state_code 
        WHERE s.plant_id='".$_GET["plant_id"]."' AND s.status = 'approve'  ORDER BY id DESC";
        
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
    
} else {
    echo "{\"status\":\"invalid\"}";
}
// $qc->close();
// $store->close();
// $purchase->close();
// $security->close();
// $qa->close();
// $hr->close();
$conn->close();

?>