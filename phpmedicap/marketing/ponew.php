<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';



// ini_set('display_errors', 1);
// error_reporting(E_ALL);

 
 
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

function escPo($conn, $value) {
    return $conn->real_escape_string(isset($value) ? $value : '');
}

function ensurePoTableColumns($conn, $table, $cols) {
    foreach ($cols as $col => $def) {
        $check = @$conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$col."'");
        if ($check && $check->num_rows == 0) {
            @$conn->query("ALTER TABLE `".$table."` ADD COLUMN `".$col."` ".$def);
        }
    }
}

function ensurePoEntryServiceColumns($conn) {
    ensurePoTableColumns($conn, 'po_entry', array(
        'billing_type' => 'VARCHAR(100) NULL',
        'serviceCategory' => 'TEXT NULL',
        'serviceDescription' => 'TEXT NULL',
        'serviceDescriptionData' => 'LONGTEXT NULL',
        'selectedProductCodes' => 'TEXT NULL',
        'parent_product_code' => 'VARCHAR(100) NULL',
        'plan_qty' => 'VARCHAR(100) NULL',
        'groupcode' => 'VARCHAR(100) NULL',
        'subClient' => 'VARCHAR(255) NULL',
        'mainGroupName' => 'VARCHAR(255) NULL'
    ));
    ensurePoTableColumns($conn, 'order_materials', array(
        'po_entry_id' => 'INT NULL',
        'parent_product_code' => 'VARCHAR(100) NULL',
        'mainGroupName' => 'VARCHAR(255) NULL',
        'groupcode' => 'VARCHAR(100) NULL',
        'subClient' => 'VARCHAR(255) NULL'
    ));
}

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
    
    if($_GET["type"]=="getClients") {
        $output = array();
     	$sql = "SELECT id,plant_id,status,client_code,LglNm FROM client WHERE status = 'Active'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    } else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE status='approve' AND dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getUnits") {
        $output = Array();
        $sql = "SELECT * FROM leadunit where plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveunit") {
        $output = Array();
        $sql = "INSERT INTO leadunit(unit, plant_id) VALUES ('".$_GET["unit"]."','".$_GET["plant_id"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     } 
    
    // else if ($_GET["type"] == "receivePO") {
         
    //     $input = $_POST;
        
    //     $po = $input["po_no"];
    //     $aug = date("YmdHis", $timestamp);
        
    //     $file_name = '';
        
    //     if (isset($_FILES['fileUp']['name']) && $_FILES['fileUp']['error'] == 0) {
    //         $original_name = $_FILES['fileUp']['name'];
    //         $temp_path = $_FILES['fileUp']['tmp_name'];
    //         $file_parts = explode('.', $original_name);
    //         $file_ext = strtolower(end($file_parts));
    //         $file_name = $aug . 'entrypo' . $po . '.' . $file_ext;
    //         $upload_path = "../../../upload/poentry/" . $file_name; 
    //         move_uploaded_file($temp_path, $upload_path);
    //     }else{
    //         $file_name = 'NA';
    //     }

    	 
      
    //     $sql = "INSERT INTO `po_entry`(`plant_id`, `po_type`, `client_code`, `conisgnee`, `po_no`, `po_date`, `file`, `status`, `entry_by`, `entry_date`,mainGroupName,groupcode,subClient) VALUES
    //     ('".$_GET["plant_id"]."','".$input["po_type"]."', '".$input["client_code"]."','".$input["conisgnee"]."','".$input["po_no"]."', '".$input["po_date"]."', 
    //     '$file_name', 'Pending',  '".$_GET["emp_id"]."','$entry_date', '".$input["mainGroupName"]."', '".$input["groupcode"]."', '".$input["subClient"]."' )";
        
    //     if ($conn->query($sql)) {
    //         $insert_id = $conn->insert_id;
            
    //         $order_no = "";
    //         $sql = "SELECT order_no FROM po_entry WHERE id = '$insert_id' order by id desc";
    //         $result = $conn->query($sql);
    //         if ($result->num_rows > 0) {
    //             while ($row = $result->fetch_assoc()) {
    //                 $order_no = $row["order_no"];
    //             }
    //         }
        	
    //     	$products = json_decode($input["products"], true);
    //     	for ($i = 0; $i < count($products); $i++) {
        	    
    //     	    $product = $products[$i];
        	    
    //       	     $sql1 = "INSERT INTO `order_materials`(`plant_id`, `order_no`, `planMonth`, `product_code`, `packingStyle`, `packingUnit`, `planQty`, `planUnit`, 
    //       	     `deliveryDate`,`remark`, `status`, `entryBy`, `entryOn`,mainGroupName,groupcode,subClient) VALUES ('".$_GET["plant_id"]."','$order_no', '".$product["planMonth"]."', 
    //       	     '".$product["product_code"]."','".$product["packingStyle"]."', '".$product["packingUnit"]."','".$product["planQty"]."','".$product["planUnit"]."',
    //       	     '".$product["deliveryDate"]."','".$product["remark"]."', 'Pending', '".$_GET["emp_id"]."','$entry_date' , '".$input["mainGroupName"]."', '".$input["groupcode"]."', '".$input["subClient"]."' )";
    //     	    $conn->query($sql1);
        	    
    //     	}
        	
    //     	echo "{\"status\":\"success\"}";
        	
    //     } else {
    //       echo "{\"status\":\"".$conn->error."\"}";
    //     }
         
    // } 
    else if ($_GET["type"] == "receivePO") {
        header('Content-Type: application/json; charset=utf-8');
        ensurePoEntryServiceColumns($conn);
        $input = $_POST;
        $billing_type = escPo($conn, isset($input['billing_type']) ? $input['billing_type'] : '');
        $serviceCategory = escPo($conn, isset($input['serviceCategory']) ? $input['serviceCategory'] : '');
        $serviceDescription = escPo($conn, isset($input['serviceDescription']) ? $input['serviceDescription'] : '');
        $serviceDescriptionData = escPo($conn, isset($input['serviceDescriptionData']) ? $input['serviceDescriptionData'] : '');
        $selectedProductCodes = escPo($conn, isset($input['selectedProductCodes']) ? $input['selectedProductCodes'] : '');
$aug = date("YmdHis", $timestamp);

// Handle file upload
$file_name = 'NA';
if (isset($_FILES['fileUp']['name']) && $_FILES['fileUp']['error'] == 0) {
    $original_name = $_FILES['fileUp']['name'];
    $temp_path = $_FILES['fileUp']['tmp_name'];
    $file_parts = explode('.', $original_name);
    $file_ext = strtolower(end($file_parts));
    $file_name = $aug . 'entrypo' . ($input["po_no"] ?? '') . '.' . $file_ext;
    $upload_path = "../../../upload/poentry/" . $file_name; 
    @move_uploaded_file($temp_path, $upload_path);
}

// Decode products array
$products = json_decode(isset($input["products"]) ? $input["products"] : '[]', true);
if (!is_array($products)) {
    $products = array();
}

$foEntryType = escPo($conn, $input['fo_entry_type'] ?? $input['client_type'] ?? '');
if ($foEntryType === '') {
    $foEntryType = count($products) > 0 ? 'FO Product' : 'FO Service';
}
if ($foEntryType === 'FO Service') {
    $products = array();
}

if (count($products) === 0) {
    $poType = escPo($conn, $input["po_type"] ?? '');
    $clientCode = escPo($conn, $input["client_code"] ?? '');
    $consignee = escPo($conn, $input["conisgnee"] ?? '');
    $poDate = escPo($conn, $input["po_date"] ?? '');
    $mainGroupName = escPo($conn, $input["mainGroupName"] ?? '');
    $groupcode = escPo($conn, $input["groupcode"] ?? '');
    $subClient = escPo($conn, $input["subClient"] ?? '');
    $poNo = escPo($conn, $input["po_no"] ?? '');

    $sql = "INSERT INTO `po_entry`(
                `plant_id`, `po_type`, `client_code`, `conisgnee`, 
                `po_date`, `file`, `status`, `entry_by`, `entry_date`,
                `mainGroupName`, `groupcode`, `subClient`, `po_no`, `client_type`,
                `billing_type`, `serviceCategory`, `serviceDescription`, `serviceDescriptionData`, `selectedProductCodes`
            ) VALUES (
                '".$_GET["plant_id"]."', '$poType', '$clientCode', '$consignee',
                '$poDate', '$file_name', 'Entered', '".$_GET["emp_id"]."', '$entry_date',
                '$mainGroupName', '$groupcode', '$subClient', '$poNo', '$foEntryType',
                '$billing_type', '$serviceCategory', '$serviceDescription', '$serviceDescriptionData', '$selectedProductCodes'
            )";

    if (!$conn->query($sql)) {
        $sql = "INSERT INTO `po_entry`(
                    `plant_id`, `po_type`, `client_code`, `conisgnee`, 
                    `po_no`, `po_date`, `file`, `status`, `entry_by`, `entry_date`,
                    `mainGroupName`, `groupcode`, `subClient`
                ) VALUES (
                    '".$_GET["plant_id"]."', '$poType', '$clientCode', '$consignee',
                    '$poNo', '$poDate', '$file_name', 'Entered', '".$_GET["emp_id"]."', '$entry_date',
                    '$mainGroupName', '$groupcode', '$subClient'
                )";
        if (!$conn->query($sql)) {
            echo json_encode(array("status" => "failed", "message" => $conn->error));
            exit;
        }
    }

    echo json_encode(array("status" => "success"));
    exit;
}

// Iterate products array — one PO_ENTRY per product index
foreach ($products as $index => $product) {
    if (!is_array($product)) {
        continue;
    }

    $poType = escPo($conn, $input["po_type"] ?? '');
    $clientCode = escPo($conn, $input["client_code"] ?? '');
    $consignee = escPo($conn, $input["conisgnee"] ?? '');
    $poDate = escPo($conn, $input["po_date"] ?? '');
    $mainGroupName = escPo($conn, $input["mainGroupName"] ?? '');
    $groupcode = escPo($conn, $input["groupcode"] ?? '');
    $subClient = escPo($conn, $input["subClient"] ?? '');
    $productCode = escPo($conn, $product["product_code"] ?? '');
    $poNo = escPo($conn, $input["po_no"] ?? '');
    $planQty = escPo($conn, $product["planQty"] ?? '');
    $planMonth = escPo($conn, $product["planMonth"] ?? '');
    $packingStyle = escPo($conn, $product["packingStyle"] ?? '');
    $packingUnit = escPo($conn, $product["packingUnit"] ?? '');
    $planUnit = escPo($conn, $product["planUnit"] ?? '');
    $deliveryDate = escPo($conn, $product["deliveryDate"] ?? '');
    $remark = escPo($conn, $product["remark"] ?? '');

    // 1️⃣ Insert a new PO_ENTRY without order_no (trigger will set it)
    $sql = "INSERT INTO `po_entry`(
                `plant_id`, `po_type`, `client_code`, `conisgnee`, 
                `po_date`, `file`, `status`, `entry_by`, `entry_date`,
                `mainGroupName`, `groupcode`, `subClient`, `parent_product_code`, `po_no`, `plan_qty`, `client_type`,
                `billing_type`, `serviceCategory`, `serviceDescription`, `serviceDescriptionData`, `selectedProductCodes`
            ) VALUES (
                '".$_GET["plant_id"]."', '$poType', '$clientCode', '$consignee',
                '$poDate', '$file_name', 'Entered', '".$_GET["emp_id"]."', '$entry_date',
                '$mainGroupName', '$groupcode', '$subClient', '$productCode','$poNo','$planQty', 'FO Product',
                '$billing_type', '$serviceCategory', '$serviceDescription', '$serviceDescriptionData', '$selectedProductCodes'
            )";

    if (!$conn->query($sql)) {
        $sql = "INSERT INTO `po_entry`(
                    `plant_id`, `po_type`, `client_code`, `conisgnee`, 
                    `po_no`, `po_date`, `file`, `status`, `entry_by`, `entry_date`,
                    `mainGroupName`, `groupcode`, `subClient`
                ) VALUES (
                    '".$_GET["plant_id"]."', '$poType', '$clientCode', '$consignee',
                    '$poNo', '$poDate', '$file_name', 'Entered', '".$_GET["emp_id"]."', '$entry_date',
                    '$mainGroupName', '$groupcode', '$subClient'
                )";
        if (!$conn->query($sql)) {
            echo json_encode(array("status" => "failed", "message" => $conn->error));
            exit;
        }
    }

    $po_entry_id = $conn->insert_id;

    // Fetch the order_no generated by trigger
    $order_no = '';
    $sqlOrder = "SELECT order_no FROM po_entry WHERE id = '$po_entry_id' LIMIT 1";
    $resultOrder = @$conn->query($sqlOrder);
    if ($resultOrder && ($rowOrder = $resultOrder->fetch_assoc())) {
        $order_no = $rowOrder['order_no'] ?? '';
    }
    if ($order_no === '') {
        $order_no = 'PO-' . $po_entry_id;
        @$conn->query("UPDATE po_entry SET order_no='".$conn->real_escape_string($order_no)."' WHERE id='$po_entry_id'");
    }
    $order_no = escPo($conn, $order_no);

    // 2️⃣ Insert product into order_materials
    $sql1 = "INSERT INTO `order_materials`(
                `plant_id`, `po_entry_id`, `order_no`, `planMonth`, `product_code`,
                `packingStyle`, `packingUnit`, `planQty`, `planUnit`,
                `deliveryDate`, `remark`, `status`, `entryBy`, `entryOn`,
                `mainGroupName`, `groupcode`, `subClient`, `parent_product_code`
            ) VALUES (
                '".$_GET["plant_id"]."', '$po_entry_id', '$order_no', '$planMonth', '$productCode',
                '$packingStyle', '$packingUnit', '$planQty', '$planUnit',
                '$deliveryDate', '$remark', 'Entered', '".$_GET["emp_id"]."', '$entry_date',
                '$mainGroupName', '$groupcode', '$subClient', '$productCode'
            )";
    if (!@$conn->query($sql1)) {
        $sql1 = "INSERT INTO `order_materials`(
                    `plant_id`, `order_no`, `planMonth`, `product_code`,
                    `packingStyle`, `packingUnit`, `planQty`, `planUnit`,
                    `deliveryDate`, `remark`, `status`, `entryBy`, `entryOn`
                ) VALUES (
                    '".$_GET["plant_id"]."', '$order_no', '$planMonth', '$productCode',
                    '$packingStyle', '$packingUnit', '$planQty', '$planUnit',
                    '$deliveryDate', '$remark', 'Entered', '".$_GET["emp_id"]."', '$entry_date'
                )";
        @$conn->query($sql1);
    }
}

echo json_encode(array("status" => "success"));

    } 
    else if ($_GET["type"] == "receivePOFromClient") {
         
        $input = $_POST;
        
        $po = $input["po_no"];
        $aug = date("YmdHis", $timestamp);
        
        $file_name = '';
        
        if (isset($_FILES['fileUp']['name']) && $_FILES['fileUp']['error'] == 0) {
            $original_name = $_FILES['fileUp']['name'];
            $temp_path = $_FILES['fileUp']['tmp_name'];
            $file_parts = explode('.', $original_name);
            $file_ext = strtolower(end($file_parts));
            $file_name = $aug . 'entrypo' . $po . '.' . $file_ext;
            $upload_path = "../../../upload/poentry/" . $file_name; 
            move_uploaded_file($temp_path, $upload_path);
        }else{
            $file_name = 'NA';
        }

    	 
      
        $sql = "INSERT INTO `po_entry`(`plant_id`, `po_type`, `client_code`, `conisgnee`, `po_no`, `po_date`, `file`, `status`, `entry_by`, `entry_date`) VALUES
        ('".$_GET["plant_id"]."','".$input["po_type"]."', '".$input["client_code"]."','".$input["conisgnee"]."','".$input["po_no"]."', '".$input["po_date"]."', 
        '$file_name', 'Entered',  'Client','$entry_date' )";
        
        if ($conn->query($sql)) {
            $insert_id = $conn->insert_id;
            
            $order_no = "";
            $sql = "SELECT order_no FROM po_entry WHERE id = '$insert_id' order by id desc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $order_no = $row["order_no"];
                }
            }
        	
        	$products = json_decode($input["products"], true);
        	for ($i = 0; $i < count($products); $i++) {
        	    
        	    $product = $products[$i];
        	    
          	     $sql1 = "INSERT INTO `order_materials`(`plant_id`, `order_no`, `planMonth`, `product_code`, `packingStyle`, `packingUnit`, `planQty`, `planUnit`, 
          	     `deliveryDate`,`remark`, `status`, `entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."','$order_no', '".$product["planMonth"]."', 
          	     '".$product["product_code"]."','".$product["packingStyle"]."', '".$product["packingUnit"]."','".$product["planQty"]."','".$product["planUnit"]."',
          	     '".$product["deliveryDate"]."','".$product["remark"]."', 'Entered', 'Client','$entry_date' )";
        	    $conn->query($sql1);
        	    
        	}
        	
        	echo "{\"status\":\"success\"}";
        	
        } else {
           echo "{\"status\":\"".$conn->error."\"}";
        }
         
    } 
 
    // else if ($_GET["type"] == "getPendingPOs") {
        
    //     $output = Array();
        
    //     $sql = "SELECT 
    //             p.*,
    //             (SELECT c.LglNm 
    //              FROM client c 
    //              WHERE c.client_code = p.client_code 
    //              LIMIT 1) AS clientName,
    //             (SELECT c2.LglNm 
    //              FROM client c2 
    //              WHERE c2.client_code = p.conisgnee 
    //              LIMIT 1) AS conisgneeName
    //         FROM po_entry p
    //         WHERE 
    //             p.plant_id = '".$_GET["plant_id"]."' 
    //             AND p.status = 'pending'
    //         ORDER BY 
    //             p.id DESC";
        
        
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
                
    //             $output1 = array();
    //             $sql1 = "SELECT o.*, p.product_name FROM order_materials o LEFT JOIN product p ON o.product_code = p.product_code 
    //             WHERE o.po_entry_id = '".$row["id"]."' ";
                
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                      $output1[] = $row1;
    //                 }
    //             }
                
    //             $row["products"] = $output1;
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // } 
    else if ($_GET["type"] == "getPendingPOs") {
        
        $output = Array();
        
        $sql = "SELECT a.*,(SELECT c.LglNm 
                 FROM client c 
                 WHERE c.client_code = b.client_code 
                 LIMIT 1) AS clientName,
                (SELECT c2.LglNm 
                 FROM client c2 
                 WHERE c2.client_code = b.conisgnee 
                 LIMIT 1) AS conisgneeName, b.po_no,b.po_date,b.entry_by,b.entry_date,c.category,d.product_name 
                 FROM order_materials a left join po_entry b on a.po_entry_id=b.id left join product c on b.parent_product_code=c.product_code
                 left join product d on a.product_code=d.product_code where b.status='Pending'";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name FROM order_materials o LEFT JOIN product p ON o.product_code = p.product_code 
                WHERE o.po_entry_id = '".$row["id"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
 
    else if ($_GET["type"] == "getPendingPOsReview") {
        $output = Array();
        $sql = "SELECT p.*,c.TrdNm FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status='pending' ORDER BY p.id DESC";
        
        
        $result = $conn->query($sql);
 if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row["terms"] = json_decode($row["terms"]);
                $output1 = array();
                 $sql1 = "SELECT o.order_no,o.product_code,o.order_qty,o.pack_size,o.details, p.product_name, 
                 p.product_type, p.grade FROM order_materials o LEFT JOIN product p ON
                 o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                          $row1["details"] = json_decode($row1["details"]);
                          $row1["pack_size"] = json_decode($row1["pack_size"]);
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "updatePendingPOs") {
        
        $sql = "UPDATE po_entry SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date'  WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
    	    echo "{\"status\":\"success\"}";
        } else {
           echo "{\"status\":\"".$conn->error."\"}";
           
        }
        
    }
    else if ($_GET["type"] == "update_rate") {
        
        echo  $sql = "UPDATE order_materials SET rate='".$_GET["rate"]."',order_qty='".$_GET["order_qty"]."',amount_inr='".$_GET["amt_inr"]."',amount_usd='".$_GET["amt_usd"]."'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
    	    echo "{\"status\":\"success\"}";
    	    
        } else { 
            
          echo "{\"status\":\"".$conn->error."\"}";
           
        }
        
        
    }
    else if ($_GET["type"] == "updatePendingPOs") {
        
           $sql = "UPDATE po_entry SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."',  remark='".$_GET["remark"]."', 
          approve_date='$entry_date'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
    	    echo "{\"status\":\"success\"}";
    	    
        } else { 
            
           echo "{\"status\":\"".$conn->error."\"}";
           
        }
        
        
    }
    else if ($_GET["type"] == "updatePendingPOs1") {
                $input = $_POST;

        
            $sql = "UPDATE po_entry SET status='".$_GET["status"]."',products = '".$input["products"]."', terms = '".$input["terms"]."' , approve_by='".$_GET["emp_id"]."',  remark='".$_GET["remark"]."', 
          approve_date='$entry_date'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
    	    echo "{\"status\":\"success\"}";
    	    
    	    
    	       $sql1 =   "delete from order_materials where order_no = '".$_GET["order_no"]."'";
    	      $conn->query($sql1);
    	    
    	    
    	    
    	    	$products = json_decode($input["products"], true);
        	for ($i = 0; $i < count($products); $i++) {
        	    $product = $products[$i];
         	    $sql2 = "INSERT INTO order_materials (plant_id,user_no,order_no, product_code, rate, currency, order_qty, unit, amount_inr, amount_usd,
        	    usd_rate, packing_configuration ,packing_style) VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$_GET["order_no"]."', '".$product["product_code"]."', 
        	    '".$product["rate"]."', '".$product["currency"]."', '".$product["qty"]."', '".$product["unit"]."', '".$product["amount_inr"]."',
        	    '".$product["amount_usd"]."', '".$product["currancy_rate"]."', '".$product["packing_configuration"]."' ,'".$product["packing_style"]."')";
        	    $conn->query($sql2);
        	}
    	     
        } else { 
            
           echo "{\"status\":\"".$conn->error."\"}";
           
        }
        
        
    }
    else if ($_GET["type"] == "receiveSo") {
         $flag = 0;
    for ($i = 0; $i < count($input); $i++) {
        $temp = $input[$i];
         $sql = "UPDATE po_entry SET status='Received', received_date='$entry_date',company_unit='".$temp["company_unit"]."',plan_no='01' WHERE order_no='".$temp["order_no"]."'";
        if ($conn->query($sql) === FALSE) {
            $flag = 1;
            break;
        }
    }
    if ($flag == 0) {
         $sql1 = "INSERT INTO consoladated(user_no,client_code,po_type, po_no, po_date,work_order_no, entry_by, entry_date,products) VALUES ('".$temp["user_no"]."','".$temp['client_code']."', '".$temp["po_type"]."', '".$temp['po_no']."', '".$temp['po_date']."','".$temp["order_no"]."','".$_GET["emp_id"]."', '$entry_date','".json_encode($temp["products"])."')";
            $conn->query($sql1);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 




else if ($_GET["type"] == "getPOsLogForClientWise") {
        
        $output = Array();
        
        $sql = "SELECT a.entryOn as order_recivedDate,a.id as pid,a.planUnit   ,a.order_no,a.product_code,a.planQty,a.Fo_code ,a.packingStyle as pack_size,a.packingUnit as pack_size_unit,d.product_name, e.average_weight as batch_size,e.mfr_no,a.packingUnit ,
      e.id as unit_formula_id  ,a.mainGroupName,a.groupcode,a.deliveryDate,a.planMonth,a.subClient,(select z.batch_formula_weight   FROM batch_formula_info z where z.product_code=a.product_code  order by batch_formula_weight desc limit 1) as batch_formula_weight FROM 
      order_materials a LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN unitformula e ON a.product_code=e.product_code
      left join po_entry po on a.order_no = po.order_no 
      WHERE a.status = 'send for client wise order'  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC"
      ;
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
// else if ($_GET["type"] == "getPOsLogForCheckedBox") {
        
//         $output = Array();
        
//         $sql = "SELECT a.*,(SELECT c.LglNm 
//                  FROM client c 
//                  WHERE c.client_code = b.client_code 
//                  LIMIT 1) AS clientName,
//                 (SELECT c2.LglNm 
//                  FROM client c2 
//                  WHERE c2.client_code = b.conisgnee 
//                  LIMIT 1) AS conisgneeName, b.po_no,b.po_date,b.entry_by,b.entry_date,c.category,d.product_name ,b.order_no as po_order_no,c.product_name as parent_name,b.order_no as po_order_no
//                  FROM order_materials a left join po_entry b on a.po_entry_id=b.id left join product c on b.parent_product_code=c.product_code
//                  left join product d on a.product_code=d.product_code    
//             WHERE 
//                 a.plant_id = '".$_GET["plant_id"]."' and a.status='Pending'
//             ORDER BY 
//                 b.id DESC";
        
        
//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
//                  $output1 = Array();
//                         $sql1 = "SELECT b.batch_formula_weight,b.batch_formula_weight_unit
//         FROM unitformula a
//         LEFT JOIN batch_formula_info b ON a.mfr_no = b.mfr_no
//         WHERE a.mfr_no = (
//             SELECT MAX(mfr_no) 
//             FROM unitformula 
//             WHERE product_code = '".$row['product_code']."'
//         )
//         AND LOWER(b.batch_formula_weight_unit) = LOWER('".$row['planUnit']."')";

                        
//                         $result1 = $conn->query($sql1);
//                         if ($result1->num_rows > 0) {
//                             while ($row1 = $result1->fetch_assoc()) {
//                                 $output1[] = $row1;
//                             }
//                         }
//             $row['batch_formula'] = $output1;
                
               
//                 $output[] = $row;
//             }
//         }
//         echo json_encode($output);
//     }
else if ($_GET["type"] == "getPOsLogForCheckedBox") {
        
        $output = Array();
        
        $sql = "SELECT a.*,(SELECT c.LglNm 
                 FROM client c 
                 WHERE c.client_code = b.client_code 
                 LIMIT 1) AS clientName,
                (SELECT c2.LglNm 
                 FROM client c2 
                 WHERE c2.client_code = b.conisgnee 
                 LIMIT 1) AS conisgneeName, b.po_no,b.po_date,b.entry_by,b.entry_date,c.category,d.product_name ,b.order_no as po_order_no,c.product_name as parent_name,b.order_no as po_order_no
                 FROM Forcast_order_materials a left join po_entry b on a.po_entry_id=b.id left join product c on b.parent_product_code=c.product_code
                 left join product d on a.product_code=d.product_code    
            WHERE 
                a.plant_id = '".$_GET["plant_id"]."' and a.status='ACTIVE'
            ORDER BY 
                a.id asc";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                        $sql1 = "SELECT b.batch_formula_weight,b.batch_formula_weight_unit
        FROM unitformula a
        LEFT JOIN batch_formula_info b ON a.mfr_no = b.mfr_no
        WHERE a.mfr_no = (
            SELECT MAX(mfr_no) 
            FROM unitformula 
            WHERE product_code = '".$row['product_code']."'
        )
        AND LOWER(b.batch_formula_weight_unit) = LOWER('".$row['planUnit']."')";

                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
            $row['batch_formula'] = $output1;
                
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "saveForcastPlan") {
    
                    $json_obj = json_encode($input["plannedProducts"]);
                $allBatches = json_decode($json_obj, true);
                
                $status1 = false;
                
                foreach ($allBatches as $batch) {
                    foreach ($batch as $values) {
                
                        $sql = "INSERT INTO `Forcast_order_materials`
                        (`po_entry_id`, `order_materials_id`, `plant_id`, `mainGroupName`, `groupcode`,
                        `subClient`, `order_no`, `Fo_code`, `planMonth`, `product_code`, `packingStyle`,
                        `packingUnit`, `planQty`, `planUnit`, `deliveryDate`, `remark`, `status`, `entryBy`,
                        `entryOn`, `parent_product_code`, `plan_qty`, `CombiMaster_dtl_qty`,batchId)
                        VALUES (
                            '".$values['po_entry_id']."',
                            '".$values['OrderMaterials_id']."',
                            '".$values['plant_id']."',
                            '".$values['mainGroupName']."',
                            '".$values['groupcode']."',
                            '".$values['subClient']."',
                            '".$values['order_no']."',
                            '".$values['Fo_code']."',
                            '".$values['planMonth']."',
                            '".$values['product_code']."',
                            '".$values['packingStyle']."',
                            '".$values['packingUnit']."',
                            '".$values['planQty']."',
                            '".$values['planUnit']."',
                            '".$values['deliveryDate']."',
                            '".$values['remark']."',
                            'ACTIVE',
                            '".$values['entryBy']."',
                            NOW(),
                            '".$values['parent_product_code']."',
                            '".$values['plan_qty']."',
                            '".$values['CombiMaster_dtl_qty']."',
                            '".$values['batchId']."'
                        )";
                
                        if ($conn->query($sql)) {
                            $status1 = true;
                        } else {
                            $status1 = false;
                        }
                    }
                }
                
                if ($status1) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }

}
else if ($_GET["type"] == "getPOsLogForcast") {
        
        $output = Array();
        
        $sql = "SELECT 
                p.*,
                (SELECT c.LglNm 
                 FROM client c 
                 WHERE c.client_code = p.client_code 
                 LIMIT 1) AS clientName,
                (SELECT c2.LglNm 
                 FROM client c2 
                 WHERE c2.client_code = p.conisgnee 
                 LIMIT 1) AS conisgneeName,
                 (select pp.category from product pp where pp.product_code=p.parent_product_code limit 1 ) as category
            FROM po_entry p
            WHERE 
                p.plant_id = '".$_GET["plant_id"]."'
            ORDER BY 
                p.id DESC";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name FROM order_materials o LEFT JOIN product p ON o.product_code = p.product_code 
                WHERE o.po_entry_id = '".$row["id"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                
               
                
                          $output2 = array();
                                
                                // STEP 1: Fetch all rows for this po_entry_id in one query
                                $sql2 = "SELECT  o.*, p.product_name FROM Forcast_order_materials o LEFT JOIN product p ON o.product_code = p.product_code 
                WHERE o.po_entry_id = '".$row["id"]."' 
                                         ORDER BY batchId ASC";
                                
                                $result2 = $conn->query($sql2);
                                
                                // Temporary grouping array
                                $tempGroup = array();
                                
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                
                                        $batchId = $row2["batchId"];
                                
                                        // Create array index if not exists
                                        if (!isset($tempGroup[$batchId])) {
                                            $tempGroup[$batchId] = array();
                                        }
                                
                                        // Push row into the correct batch array
                                        $tempGroup[$batchId][] = $row2;
                                    }
                                }
                                
                                // Convert associative batch array into sequential array
                                $output2 = array_values($tempGroup);
                                
                                // Attach to main row
                                $row["PlannedOrders"] = $output2;

                
                $row["PlannedOrders"] = $output2;
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getPOsLog") {
        
        $output = Array();
        
        $sql = "SELECT 
                p.*,
                (SELECT c.LglNm 
                 FROM client c 
                 WHERE c.client_code = p.client_code 
                 LIMIT 1) AS clientName,
                (SELECT c2.LglNm 
                 FROM client c2 
                 WHERE c2.client_code = p.conisgnee 
                 LIMIT 1) AS conisgneeName
            FROM po_entry p
            WHERE 
                p.plant_id = '".$_GET["plant_id"]."'
            ORDER BY 
                p.id DESC";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name FROM order_materials o LEFT JOIN product p ON o.product_code = p.product_code 
                WHERE o.po_entry_id = '".$row["id"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
// else if ($_GET["type"] == "getPOsLog") {
        
//         $output = Array();
        
//         $sql = "SELECT a.*,(SELECT c.LglNm 
//                  FROM client c 
//                  WHERE c.client_code = b.client_code 
//                  LIMIT 1) AS clientName,
//                 (SELECT c2.LglNm 
//                  FROM client c2 
//                  WHERE c2.client_code = b.conisgnee 
//                  LIMIT 1) AS conisgneeName, b.po_no,b.po_date,b.entry_by,b.entry_date,c.category,d.product_name ,c.product_name as parent_name,
//                  b.order_no as po_order_no,b.entry_by,b.entry_date,b.approve_by,b.approve_date
//                  FROM order_materials a left join po_entry b on a.po_entry_id=b.id left join product c on b.parent_product_code=c.product_code
//                  left join product d on a.product_code=d.product_code order by b.id asc ";
        
        
//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
 
//                 $output[] = $row;
//             }
//         }
//         echo json_encode($output);
//     }
else if ($_GET["type"] == "getPOsLogSummery") {
    
    $output = [];

$sql = "SELECT a.*,
        (SELECT c.LglNm FROM client c WHERE c.client_code = b.client_code LIMIT 1) AS clientName,
        (SELECT c2.LglNm FROM client c2 WHERE c2.client_code = b.conisgnee LIMIT 1) AS conisgneeName,
        b.po_no,b.po_date,b.entry_by,b.entry_date,
        c.category,d.product_name,
        c.product_name AS parent_name,
        b.order_no AS po_order_no,b.approve_by,b.approve_date
        FROM order_materials a 
        LEFT JOIN po_entry b ON a.po_entry_id=b.id 
        LEFT JOIN product c ON b.parent_product_code=c.product_code
        LEFT JOIN product d ON a.product_code=d.product_code 
        ORDER BY b.id ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
   
    
            
           
                       $colors = [
                                                "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", 
                                                "#D1C4E9", "#FFE0B2", "#F8BBD0", "#B2EBF2"
                                            ];
                                        
                                            $colorMap = []; // map po_order_no => color
                                            $colorIndex = 0;

            while ($row = $result->fetch_assoc()) {
                
                
                   
                                          
                                                
                                                $po = $row["po_order_no"];
                                        
                                                // assign color only first time
                                                if (!isset($colorMap[$po])) {
                                                    $colorMap[$po] = $colors[$colorIndex % count($colors)];
                                                    $colorIndex++;
                                                }
                                        
                                                // add bg_color to row
                                                $row["bg_color"] = $colorMap[$po];

        // Now get last formula for this product
 $output1 = Array();
                        $sql1 = "SELECT b.batch_formula_weight,b.batch_formula_weight_unit
        FROM unitformula a
        LEFT JOIN batch_formula_info b ON a.mfr_no = b.mfr_no
        WHERE a.mfr_no = (
            SELECT MAX(mfr_no) 
            FROM unitformula 
            WHERE product_code = '".$row['product_code']."'
        )
        AND LOWER(b.batch_formula_weight_unit) = LOWER('".$row['planUnit']."')";

                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
            $row['batch_formula'] = $output1;

        $output[] = $row;
    }
}

echo json_encode($output);

}
else if ($_GET["type"] == "getPOsLogForClient") {
        
        $output = Array();
        
        $sql = "SELECT 
                p.*,
                (SELECT c.LglNm 
                 FROM client c 
                 WHERE c.client_code = p.client_code 
                 LIMIT 1) AS clientName,
                (SELECT c2.LglNm 
                 FROM client c2 
                 WHERE c2.client_code = p.conisgnee 
                 LIMIT 1) AS conisgneeName
            FROM po_entry p
            WHERE 
                p.client_code = '".$_GET["emp_id"]."'
            ORDER BY 
                p.id DESC";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name FROM order_materials o LEFT JOIN product p ON o.product_code = p.product_code 
                WHERE o.order_no = '".$row["order_no"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

else if ($_GET["type"] == "getPOsLogForReqAnalysis") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.planUnit as unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.po_date,a.product_code,a.planQty as order_qty,a.packingUnit,
      a.packingStyle as pack_size,d.product_name,e.id as unit_formula_id ,a.mainGroupName,a.groupcode,a.deliveryDate,a.planMonth,d.category FROM 
      order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
      LEFT JOIN client c ON b.client_code=c.client_code
        LEFT JOIN product d ON a.product_code = d.product_code 
        LEFT JOIN unitformula e ON e.product_code = a.product_code
                AND e.id = (
                    SELECT MAX(id) 
                    FROM unitformula 
                    WHERE product_code = a.product_code
                )
       
      WHERE a.status = 'send for Shotages'   AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC ";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                 
                 $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $output1[] = $row1;
                    }
                    
                }
                                          $row["pack_size"] = json_decode($row["pack_size"]);

             
                    $row['packing_configuration'] =$output1;
                    
                    
                    
                    
                    
                    
                    
                      $output11 = Array();
    $sql11 = "select b.* from unitformula a left join unitFormulaMaterial b on a.mfr_no=b.mfr_no where a.product_code='".$row["product_code"]."'";
    $result11 = $conn->query($sql11);
            if ($result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                     
                    $output11[] = $row11;
                }
            }
                
                
                
                
                 $row['raw_materials'] =$output11;
                    // $rawMaterials = json_decode($row["raw_materials"], true);
                    // $row["raw_materials"] = $rawMaterials;
                    $output[] = $row;
                    
            
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getpoStatusLog") {
      
        $output = Array();
        
                   $sql = "SELECT a.id as pid, b.required_date, a.unit, a.order_no, c.TrdNm, b.po_no, b.po_type, b.valid_till,
                        b.po_date, a.product_code, a.order_qty, a.pack_size, d.product_name, d.grade as product_grade 
                        FROM order_materials a 
                        LEFT JOIN po_entry b ON a.order_no = b.order_no 
                        LEFT JOIN client c ON b.client_code = c.client_code 
                        LEFT JOIN product d ON a.product_code = d.product_code  
                        ORDER BY a.id DESC";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $output = array();
                    $current_date = new DateTime();
                    
                    while ($row = $result->fetch_assoc()) {
                        $required_date = new DateTime($row['required_date']);
                        $remaining_days = $current_date->diff($required_date)->days;
                
                        // Determine if the required date is in the past
                        if ($required_date < $current_date) {
                            $remaining_days = -$remaining_days; // Make remaining days negative if the date is in the past
                        }
                
                        // Add remaining_days to the row
                        $row['rem_days'] = $remaining_days;
                
                        $output[] = $row;
                    }
                }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getCompleteReqAnalysis") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.order_no,b.file,c.TrdNm,b.po_no,b.valid_till,b.po_date,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.raw_materials,e.id as unit_formula_id FROM 
      order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
       LEFT JOIN client c ON b.client_code=c.client_code
        LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN unitformula e ON a.product_code=e.product_code
      WHERE a.reqStatus = 'Complete'  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                 
                 $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $output1[] = $row1;
                    }
                    
                }
                
              $row["pack_size"] = json_decode($row["pack_size"]);
                    $row['packing_configuration'] =$output1;
                    $rawMaterials = json_decode($row["raw_materials"], true);
                    $row["raw_materials"] = $rawMaterials;
                    $output[] = $row;
                    
            
            }
        }
        echo json_encode($output);
    }
 
// else if ($_GET["type"] == "getInprocessReqAnalysis") {
    
      
//         $output = Array();
        
//       $sql = "SELECT a.entryOn as order_recivedDate,a.id as pid,a.planUnit   as ord_unit,a.order_no,a.product_code,a.planQty as order_qty,a.packingStyle as pack_size,a.packingUnit as pack_size_unit,d.product_name, e.average_weight as batch_size,e.mfr_no,a.packingUnit ,
//       e.id as unit_formula_id  ,
//       po.order_no as po_order_no,
//       (select category from product pp where pp.product_code=po.parent_product_code limit 1) as category
//       ,a.mainGroupName,a.groupcode,a.deliveryDate,a.planMonth,a.subClient,(select z.batch_formula_weight   FROM batch_formula_info z where z.product_code=a.product_code  order by batch_formula_weight desc limit 1) as batch_formula_weight FROM 
//       order_materials a LEFT JOIN product d ON a.product_code = d.product_code 
//       left join po_entry po on po.id = a.po_entry_id
//       LEFT JOIN unitformula e ON a.product_code=e.product_code
//       WHERE a.status = 'Inprocess'  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
//     //   WHERE (a.status = 'Inprocess' or a.status = 'Pending'  )  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
        
    
      
//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
                                              
            
            
//             $output1 = Array();
//                       $colors = [
//                                                 "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", 
//                                                 "#D1C4E9", "#FFE0B2", "#F8BBD0", "#B2EBF2"
//                                             ];
                                        
//                                             $colorMap = []; // map po_order_no => color
//                                             $colorIndex = 0;

//             while ($row = $result->fetch_assoc()) {
                
                
                   
                                          
                                                
//                                                 $po = $row["po_order_no"];
                                        
//                                                 // assign color only first time
//                                                 if (!isset($colorMap[$po])) {
//                                                     $colorMap[$po] = $colors[$colorIndex % count($colors)];
//                                                     $colorIndex++;
//                                                 }
                                        
//                                                 // add bg_color to row
//                                                 $row["bg_color"] = $colorMap[$po];
                                        
                                                 
                                 
            
                
                
                
                
                
//                 if($row['packingUnit']=='GM'){
//                     $row['reqQty']=$row['order_qty']/1000;
//                 }
//                 else if($row['packingUnit']=='MG'){
//                     $row['reqQty']=$row['order_qty']/1000000;
//                 }
//                     $output11 = Array();
//                 $sql11 = "select a.*,b.material_name from unitFormulaMaterial a left join material b on a.material_code=b.material_code where mfr_no ='".$row["mfr_no"]."' ";
               
//                 $result11 = $conn->query($sql11);
//                 if ($result11->num_rows > 0) {
//                     while ($row11 = $result11->fetch_assoc()) {
                        
//                          $output11[] = $row11;
//                     }
//                 }
                
                
                
                
//                  $row['raw_materials'] =$output11;
                
                
//                 //   $sql00 = "SELECT max(batch_formula_weight) FROM `batch_formula_info` WHERE product_code='".$row["product_code"]."' ORDER by batch_formula_weight desc limit 1; ";
//                 // $result00 = $conn->query($sql00);
//                 // if ($result00->num_rows > 0) {
//                 //     while ($row00 = $result00->fetch_assoc()) {
                       
//                 //          $row["batch_formula_weight"] = number_format((float)$row00["batch_formula_weight"], 2, '.', '');
                      
//                 //     }
//                 // }
                    
                   
                
                
                
                
                
                
                
//                  $output1 = Array();
//                 //                     $pack_size = json_decode($row["pack_size"]);

//                 //   $row["pack_size"] = json_decode($row["pack_size"]);
                 
//                   $sql1 = "SELECT a.*,a.unit_name as required_qty_unit ,m.inventory as min_inventory ,uom, m.order_qty as min_order_qty, 
//                   a.total_qty as required_qty,'Packing Material' as material_type, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
//                 b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
//                 a.unit_formula_dtl_id = b.id  left join material m ON m.material_code = a.material_code where b.unit_formula_id ='".$row["unit_formula_id"]."' 
//                 AND b.pack_size = '" .$row["pack_size"] . "' AND b.unit = '" .$row["unit"]. "'";
               
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
                        
//                       $row1["batch_formula_weight"]=$row["batch_formula_weight"];
//                       $row1["unitF_batch_size"]=$row["batch_size"];
                        
                        
                        
                         

//                 $sql11 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$row1["material_code"]."'";
//                 $result11 = $conn->query($sql11);
//                 if ($result11->num_rows > 0) {
//                     while ($row11 = $result11->fetch_assoc()) {
                        
//                          $row1["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                         
//                     }
//                 }
                
//                  $sql12 = "SELECT  IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code= '".$row1["material_code"]."'";
//                 $result12 = $conn->query($sql12);
//                 if ($result12->num_rows > 0) {
//                     while ($row12 = $result12->fetch_assoc()) {
                       
//                          $row1["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                      
//                     }
//                 }
                
//                  $sql13 = "SELECT  IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE
//                  material_code= '".$row["material_code"]."'";
                 
//                 $result13 = $conn->query($sql13);
//                 if ($result13->num_rows > 0) {
//                     while ($row13 = $result13->fetch_assoc()) {
                        
//                          $row1["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                      
//                     }
//                 }
                
                
//                 $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE  status = 'Rejected' AND
//                 material_code= '".$row1["material_code"]."'";
                
//                 $result132 = $conn->query($sql132);
//                 if ($result132->num_rows > 0) {
//                     while ($row132 = $result132->fetch_assoc()) {
//                          $row1["totalRejectdQty"] = number_format((float)$row132["totalRejectdQty"], 2, '.', '');
//                     }
//                 }
                
//                 $sql132 = "SELECT  IFNULL(SUM(expiredQty), 0) as totalExpiredQty   FROM stock_book WHERE  status = 'Expired' 
//                 AND material_code= '".$row1["material_code"]."'";
                
//                 $result132 = $conn->query($sql132);
//                 if ($result132->num_rows > 0) {
//                     while ($row132 = $result132->fetch_assoc()) {
//                          $row1["totalExpiredQty"] = number_format((float)$row132["totalExpiredQty"], 2, '.', '');
                          
//                     }
//                 }
                
//                  $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row1["material_code"]."'";
//                 $result14 = $conn->query($sql14);
//                 if ($result14->num_rows > 0) {
//                     while ($row14 = $result14->fetch_assoc()) {
                        
//                           $row1["unit"] = $row14["unit"];
//                     }
//                 }
 
                
//                 $row1["balance_qty"] = number_format($row1["received_qty"]- ( $row1["dispensing_qty"] + $row1["undertest_qty"] + $row1["totalRejectdQty"] +  $row1["totalExpiredQty"]), 3, '.', '');
 
//                         $output1[] = $row1;
//                     }
                    
//                 }
                
 
                
                
                
//                     $row['packing_configuration'] =$output1;
//                     $primary_pm_list = json_decode($row["primary_pm_list"], true);
//                     $consumeableMaterial = json_decode($row["consumeableMaterial"], true);
//                     $rawMaterials = $row["raw_materials"];
                    
                    
                    
//                 foreach ($rawMaterials as &$rawMat) {
                    
//                       $rawMat["batch_formula_weight"]=$row["batch_formula_weight"];
//                       $rawMat["unitF_batch_size"]=$row["batch_size"];
                      
                      
//                     // Fetch dispensing_qty
//                     $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$rawMat["material_code"]."'";
//                     $result11 = $conn->query($sql11);
//                     if ($result11->num_rows > 0) {
//                         while ($row11 = $result11->fetch_assoc()) {
//                             $rawMat["totalDays"] =  $row11["total_days"] ;
//                         }
//                     }
                  
//                     else { $rawMat["totalDays"] =  0 ; }
//                     // Fetch dispensing_qty
//                     $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$rawMat["material_code"]."'";
//                     $result11 = $conn->query($sql11);
//                     if ($result11->num_rows > 0) {
//                         while ($row11 = $result11->fetch_assoc()) {
//                             $rawMat["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
//                         }
//                     } else { $rawMat["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
//                     // Fetch received_qty
//                     $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
//                     $result12 = $conn->query($sql12);
//                     if ($result12->num_rows > 0) {
//                         while ($row12 = $result12->fetch_assoc()) {
//                             $rawMat["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
//                         }
//                     } else { $rawMat["received_qty"] = number_format(0, 2, '.', ''); }
                    
//                     // Fetch undertest_qty
//                     $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
//                     $result13 = $conn->query($sql13);
//                     if ($result13->num_rows > 0) {
//                         while ($row13 = $result13->fetch_assoc()) {
//                             $rawMat["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
//                         }
//                     } else { $rawMat["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
//                     // Fetch totalRejectdQty
//                     $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$rawMat["material_code"]."'";
//                     $result14 = $conn->query($sql14);
//                     if ($result14->num_rows > 0) {
//                         while ($row14 = $result14->fetch_assoc()) {
//                             $rawMat["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
//                         }
//                     } else {  $rawMat["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
//                     // Fetch totalExpiredQty
//                     $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$rawMat["material_code"]."'";
//                     $result15 = $conn->query($sql15);
//                     if ($result15->num_rows > 0) {
//                         while ($row15 = $result15->fetch_assoc()) {
//                             $rawMat["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
//                         }
//                     } else { $rawMat["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
//                 $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$rawMat["material_code"]."'";
//                 $result14 = $conn->query($sql14);
//                 if ($result14->num_rows > 0) {
//                     while ($row14 = $result14->fetch_assoc()) {
                        
//                           $rawMat["stock_unit"] = $row14["unit"];
//                     }
//                 }
//                 $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$rawMat["material_code"]."'";
//                 $result14 = $conn->query($sql14);
//                 if ($result14->num_rows > 0) {
//                     while ($row14 = $result14->fetch_assoc()) {
                        
//                           $rawMat["min_inventory"] = $row14["min_inventory"];
//                           $rawMat["min_order_qty"] = $row14["min_order_qty"];
//                           $rawMat["uom"] = $row14["uom"];
//                           $rawMat["moisture"] = $row14["moisture"];
//                     }
//                 }
//                     // Calculate balance_qty
//                     $rawMat["balance_qty"] = number_format( $rawMat["received_qty"] - ( $rawMat["dispensing_qty"] + $rawMat["undertest_qty"] + $rawMat["totalRejectdQty"] + $rawMat["totalExpiredQty"] ), 3,  '.', '');
               
//               //unit;
               
//                 $unitfmatQty =    $rawMat["qty_overages_qty"] ;
               
//                  $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
// $converted_balance =  $rawMat["balance_qty"];
 

// if (strtolower($rawMat["stock_unit"]) == "ml") {
//     // ml → L
//     $converted_balance = $converted_balance / 1000;
    
// } elseif (strtolower($rawMat["stock_unit"]) == "mg" || strtolower($rawMat["stock_unit"]) == "gm" || strtolower($rawMat["stock_unit"]) == "g" || $rawMat["stock_unit"] == "GM") {
//     // mg / gm → Kg
//     if (strtolower($rawMat["stock_unit"]) == "mg") {
//         $converted_balance = $converted_balance / 1000000; // mg → Kg
//     } else {
//         $converted_balance = $converted_balance / 1000; // gm → Kg
//     }
     
// }

// // Store final values
//  $rawMat["balance_qty"] = number_format($converted_balance, 3, '.', '');
 
     
     
               
 
//  $required_qty_unit = 'Kg';
 
//                 if ($rawMat["unit"] == 'gm') {
                    
                    
//                     $order_qty = (float)$order_qty;
//                     $unitfmatQty = (float)$unitfmatQty;
                    
//                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
//                 } else if ($rawMat["unit"] == 'mg') {
                    
                    
//                      $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
//                 }

//                 else if($rawMat["unit"] == 'Kg'){
                      
//                       $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
//                 }
                
//              else if($rawMat["unit"] == 'ml'){
                 
//                   $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
//                      $required_qty_unit = 'ml';
//               }
               
//               else if($rawMat["unit"] == 'Ltr'){
                  
//                   $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
//                     $required_qty_unit = 'Ltr';
//               }
               
               
               
//                 $rawMat["required_qty"] =  number_format($finQty , 4, '.', '');
//                 $rawMat["required_qty_unit"] =  $required_qty_unit;
                
                
                
                
                
//                 //$rawMat["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
//                 $rawMat["material_type"] = 'Raw Material';
               
               
//                 }
              
                    
                    
                    
                     
 
//                     $row["raw_materials"] = $rawMaterials;
//                     $row["splits"] = $output2;
//                     $output[] = $row;
 
                    
            
//             }
//         }
//         echo json_encode($output);
    
// }
else if ($_GET["type"] == "getInprocessReqAnalysis") {
    
      
        $output = Array();
        
       $sql = "SELECT a.batchId, a.entryOn as order_recivedDate,a.id as pid,a.planUnit   as ord_unit,a.order_no,a.product_code,a.planQty as order_qty,a.packingStyle as pack_size,a.packingUnit as pack_size_unit,d.product_name, e.average_weight as batch_size,e.mfr_no,a.packingUnit ,
      e.id as unit_formula_id  ,
      po.order_no as po_order_no,
      po.parent_product_code,
      (select product_name from product pp where pp.product_code=po.parent_product_code limit 1) as parent_product_name,
      (select category from product pp where pp.product_code=po.parent_product_code limit 1) as category
      ,a.mainGroupName,a.groupcode,a.deliveryDate,a.planMonth,a.subClient,(select z.batch_formula_weight   FROM batch_formula_info z where z.product_code=a.product_code  order by batch_formula_weight desc limit 1) as batch_formula_weight FROM 
       Forcast_order_materials  a LEFT JOIN product d ON a.product_code = d.product_code 
      left join po_entry po on po.id = a.po_entry_id
      LEFT JOIN unitformula e ON a.product_code=e.product_code
      WHERE a.status = 'Inprocess'  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
    //   WHERE (a.status = 'Inprocess' or a.status = 'Pending'  )  AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                                              
            
            
            $output1 = Array();
                       $colors = [
                                                "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", 
                                                "#D1C4E9", "#FFE0B2", "#F8BBD0", "#B2EBF2"
                                            ];
                                        
                                            $colorMap = []; // map po_order_no => color
                                            $colorIndex = 0;

            while ($row = $result->fetch_assoc()) {
                
                
                   
                                          
                                                
                                               $po = $row["po_order_no"];
                                                $batchId = $row["batchId"];
                                                
                                                // Use po + batchId as unique key
                                                $key = $po . '_' . $batchId;
                                                
                                                // assign color only first time for this key
                                                if (!isset($colorMap[$key])) {
                                                    $colorMap[$key] = $colors[$colorIndex % count($colors)];
                                                    $colorIndex++;
                                                }
                                                
                                                // add bg_color to row
                                                $row["bg_color"] = $colorMap[$key];

                                        
                                                 
                                 
            
                
                
                
                
                
                if($row['packingUnit']=='GM'){
                    $row['reqQty']=$row['order_qty']/1000;
                }
                else if($row['packingUnit']=='MG'){
                    $row['reqQty']=$row['order_qty']/1000000;
                }
                    $output11 = Array();
                $sql11 = "select a.*,b.material_name from unitFormulaMaterial a left join material b on a.material_code=b.material_code where mfr_no ='".$row["mfr_no"]."' ";
               
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        
                         $output11[] = $row11;
                    }
                }
                
                
                
                
                 $row['raw_materials'] =$output11;
                
                
                //   $sql00 = "SELECT max(batch_formula_weight) FROM `batch_formula_info` WHERE product_code='".$row["product_code"]."' ORDER by batch_formula_weight desc limit 1; ";
                // $result00 = $conn->query($sql00);
                // if ($result00->num_rows > 0) {
                //     while ($row00 = $result00->fetch_assoc()) {
                       
                //          $row["batch_formula_weight"] = number_format((float)$row00["batch_formula_weight"], 2, '.', '');
                      
                //     }
                // }
                    
                   
                
                
                
                
                
                
                
                 $output1 = Array();
                //                     $pack_size = json_decode($row["pack_size"]);

                //   $row["pack_size"] = json_decode($row["pack_size"]);
                 
                   $sql1 = "SELECT a.*,a.unit_name as required_qty_unit ,m.inventory as min_inventory ,uom, m.order_qty as min_order_qty, 
                  a.total_qty as required_qty,'Packing Material' as material_type, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id  left join material m ON m.material_code = a.material_code where b.unit_formula_id ='".$row["unit_formula_id"]."' 
                AND b.pack_size = '" .$row["pack_size"] . "' AND b.unit = '" .$row["unit"]. "'";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                      $row1["batch_formula_weight"]=$row["batch_formula_weight"];
                      $row1["unitF_batch_size"]=$row["batch_size"];
                        
                        
                        
                         

                $sql11 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$row1["material_code"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        
                         $row1["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                         
                    }
                }
                
                 $sql12 = "SELECT  IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code= '".$row1["material_code"]."'";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                       
                         $row1["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                      
                    }
                }
                
                 $sql13 = "SELECT  IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE
                 material_code= '".$row["material_code"]."'";
                 
                $result13 = $conn->query($sql13);
                if ($result13->num_rows > 0) {
                    while ($row13 = $result13->fetch_assoc()) {
                        
                         $row1["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                      
                    }
                }
                
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE  status = 'Rejected' AND
                material_code= '".$row1["material_code"]."'";
                
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row1["totalRejectdQty"] = number_format((float)$row132["totalRejectdQty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(expiredQty), 0) as totalExpiredQty   FROM stock_book WHERE  status = 'Expired' 
                AND material_code= '".$row1["material_code"]."'";
                
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row1["totalExpiredQty"] = number_format((float)$row132["totalExpiredQty"], 2, '.', '');
                          
                    }
                }
                
                 $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row1["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $row1["unit"] = $row14["unit"];
                    }
                }
 
                
                $row1["balance_qty"] = number_format($row1["received_qty"]- ( $row1["dispensing_qty"] + $row1["undertest_qty"] + $row1["totalRejectdQty"] +  $row1["totalExpiredQty"]), 3, '.', '');
 
                        $output1[] = $row1;
                    }
                    
                }
                
 
                
                
                
                    $row['packing_configuration'] =$output1;
                    $primary_pm_list = json_decode($row["primary_pm_list"], true);
                    $consumeableMaterial = json_decode($row["consumeableMaterial"], true);
                    $rawMaterials = $row["raw_materials"];
                    
                    
                    
                foreach ($rawMaterials as &$rawMat) {
                    
                      $rawMat["batch_formula_weight"]=$row["batch_formula_weight"];
                      $rawMat["unitF_batch_size"]=$row["batch_size"];
                      
                      
                    // Fetch dispensing_qty
                    $sql11 = "SELECT total_days   FROM material WHERE material_code = '".$rawMat["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $rawMat["totalDays"] =  $row11["total_days"] ;
                        }
                    }
                  
                    else { $rawMat["totalDays"] =  0 ; }
                    // Fetch dispensing_qty
                    $sql11 = "SELECT IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE material_code = '".$rawMat["material_code"]."'";
                    $result11 = $conn->query($sql11);
                    if ($result11->num_rows > 0) {
                        while ($row11 = $result11->fetch_assoc()) {
                            $rawMat["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                        }
                    } else { $rawMat["dispensing_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch received_qty
                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $rawMat["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                        }
                    } else { $rawMat["received_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch undertest_qty
                    $sql13 = "SELECT IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE material_code = '".$rawMat["material_code"]."'";
                    $result13 = $conn->query($sql13);
                    if ($result13->num_rows > 0) {
                        while ($row13 = $result13->fetch_assoc()) {
                            $rawMat["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                        }
                    } else { $rawMat["undertest_qty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalRejectdQty
                    $sql14 = "SELECT IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE status = 'Rejected' AND material_code = '".$rawMat["material_code"]."'";
                    $result14 = $conn->query($sql14);
                    if ($result14->num_rows > 0) {
                        while ($row14 = $result14->fetch_assoc()) {
                            $rawMat["totalRejectdQty"] = number_format((float)$row14["totalRejectdQty"], 2, '.', '');
                        }
                    } else {  $rawMat["totalRejectdQty"] = number_format(0, 2, '.', ''); }
                    
                    // Fetch totalExpiredQty
                    $sql15 = "SELECT IFNULL(SUM(expiredQty), 0) as totalExpiredQty FROM stock_book WHERE status = 'Expired' AND material_code = '".$rawMat["material_code"]."'";
                    $result15 = $conn->query($sql15);
                    if ($result15->num_rows > 0) {
                        while ($row15 = $result15->fetch_assoc()) {
                            $rawMat["totalExpiredQty"] = number_format((float)$row15["totalExpiredQty"], 2, '.', '');
                        }
                    } else { $rawMat["totalExpiredQty"] = number_format(0, 2, '.', '');    }
                
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$rawMat["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $rawMat["stock_unit"] = $row14["unit"];
                    }
                }
                $sql14 = "SELECT inventory as min_inventory ,uom, order_qty as min_order_qty,   IFNULL(moisture, 0) AS moisture  FROM material WHERE  material_code= '".$rawMat["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                        
                          $rawMat["min_inventory"] = $row14["min_inventory"];
                          $rawMat["min_order_qty"] = $row14["min_order_qty"];
                          $rawMat["uom"] = $row14["uom"];
                          $rawMat["moisture"] = $row14["moisture"];
                    }
                }
                    // Calculate balance_qty
                    $rawMat["balance_qty"] = number_format( $rawMat["received_qty"] - ( $rawMat["dispensing_qty"] + $rawMat["undertest_qty"] + $rawMat["totalRejectdQty"] + $rawMat["totalExpiredQty"] ), 3,  '.', '');
               
              //unit;
               
                $unitfmatQty =    $rawMat["qty_overages_qty"] ;
               
                 $order_qty =   $row['order_qty']; // /  $row['pack_size'];
               
               
               
$converted_balance =  $rawMat["balance_qty"];
 

if (strtolower($rawMat["stock_unit"]) == "ml") {
    // ml → L
    $converted_balance = $converted_balance / 1000;
    
} elseif (strtolower($rawMat["stock_unit"]) == "mg" || strtolower($rawMat["stock_unit"]) == "gm" || strtolower($rawMat["stock_unit"]) == "g" || $rawMat["stock_unit"] == "GM") {
    // mg / gm → Kg
    if (strtolower($rawMat["stock_unit"]) == "mg") {
        $converted_balance = $converted_balance / 1000000; // mg → Kg
    } else {
        $converted_balance = $converted_balance / 1000; // gm → Kg
    }
     
}

// Store final values
 $rawMat["balance_qty"] = number_format($converted_balance, 3, '.', '');
 
     
     
               
 
 $required_qty_unit = 'Kg';
 
                if ($rawMat["unit"] == 'gm') {
                    
                    
                    $order_qty = (float)$order_qty;
                    $unitfmatQty = (float)$unitfmatQty;
                    
                    $finQty = number_format(($order_qty * $unitfmatQty) / 1000, 4, '.', '');
                     
                     
                     
                    
                } else if ($rawMat["unit"] == 'mg') {
                    
                    
                     $finQty = number_format(($order_qty * $unitfmatQty) / 1000000, 4, '.', '');
 
                     
                }

                else if($rawMat["unit"] == 'Kg'){
                      
                      $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
         
                }
                
             else if($rawMat["unit"] == 'ml'){
                 
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                     $required_qty_unit = 'ml';
              }
               
              else if($rawMat["unit"] == 'Ltr'){
                  
                  $finQty = number_format($order_qty * $unitfmatQty, 4, '.', '');
                    
                    $required_qty_unit = 'Ltr';
              }
               
               
               
                $rawMat["required_qty"] =  number_format($finQty , 4, '.', '');
                $rawMat["required_qty_unit"] =  $required_qty_unit;
                
                
                
                
                
                //$rawMat["required_qty"] =  number_format(($percent_qty / 100) * $order_qty_kg, 3, '.', '');
                
                
                
                
                $rawMat["material_type"] = 'Raw Material';
               
               
                }
              
                    
                    
                    
                     
 
                    $row["raw_materials"] = $rawMaterials;
                    $row["splits"] = $output2;
                    $output[] = $row;
 
                    
            
            }
        }
        echo json_encode($output);
    
}
else if ($_GET["type"] == "AcceptReqAnalysis") {
      
      $status1 = false;
      
          $json_obj = json_encode($input["filtersFO"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                $sql = "UPDATE order_materials  SET status = 'Inprocess' where id = '".$values['pid']."' ";
                if ($conn->query($sql)) {
                     $status1 = true;
                } else {
                    $status1 = false;
                }
                    
                }
        
        if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
        
        
        
    }




else if ($_GET["type"] == "getSalesOrder") {
        $output = Array();
        // $sql = "SELECT p.*, c.company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.client_code LIKE '%".$_GET["client_code"]."%' AND p.status LIKE '%".$_GET["status"]."%' AND DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY p.id DESC";
                $sql = "SELECT p.*, c.TrdNm as company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.status='approve' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/po_entry/".$row["file"];
                 $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                
                
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name, p.product_type FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getSalesOrderLog") {
        $output = Array();
         $sql = "SELECT p.*, c.TrdNm as company FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE 
         p.plant_id='".$_GET["plant_id"]."'  ORDER BY p.id DESC";
               
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output1 = array();
                $sql1 = "SELECT o.*, p.product_name,p.dosage_form,p.grade, p.product_type FROM order_materials o 
                LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getMarketingPoPlan") {
        $output1 = Array();
         $sql1 = "SELECT pe.po_type as plan_for_market,o.order_no,o.order_qty,o.id as pid,p.dosage_form,p.product_code,p.product_type,p.product_name,p.grade,p.pack_sizes FROM order_materials o 
         left join product p ON p.product_code = o.product_code 
         left join po_entry pe ON pe.order_no = o.order_no
         where o.plant_id='".$_GET["plant_id"]."'  ORDER BY o.id DESC";
               
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
               
    
                 $output3 = Array();
                 $sql3="Select id,mfr_no,batch_size from unitformula where product_code = 
                 '".$row1["product_code"]."'    ";
                
                 $result3 = $conn->query($sql3);
                 if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output4 = Array();
                        $sql4="Select id,mfr_no,bfr_no,batch_formula_weight,raw_materials,packing_materials 
                        from batch_formula_info  where mfr_no = '".$row3["mfr_no"]."' and status='Approve' ";
                        $result4 = $conn->query($sql4);
                              if ($result4->num_rows > 0) {
                                    while ($row4 = $result4->fetch_assoc()) {
                                         $output4[]=$row4;   
                                    }
                              }
                            $row3["bfr_records"] = $output4;
                            $output3[]=$row3;   
                    }
                     
                 }
                 
                 $row1["mfr_records"] = $output3;
               $output1[] = $row1;
            }
        }
        echo json_encode($output1);
    }
    
    
    
    
    
    else if ($_GET["type"] == "getRejectedPOs") {
        $output = Array();
        $sql = "SELECT p.*, c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code 
        WHERE p.status = 'reject' ";//ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
            
                             $output1 = array();
                 $sql1 = "SELECT o.*, p.product_name, p.product_code, p.grade FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getOrdersChart") {
        $months = array();
        $months[] = date("Y-m");
        for ($i = 1; $i < 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        $months = array_reverse($months);
        
        $series = array();
        $labels = array();
        for ($i = 0; $i < count($months); $i++) {
            $month = $months[$i];
            $temp = explode("-",$month);
            $sql = "SELECT count(id) as id FROM po_entry WHERE user_no='".$_GET["user_no"]."' AND MONTH(entry_date) = '".$temp[1]."' AND YEAR(entry_date) = '".$temp[0]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $labels[] = $month;
                    $series[] = +$row["id"];
                    break;
                }
            } else {
                $labels[] = $month;
                $series[] = 0;
            }
        }
        
        $orders = 0;
        $sql1 = "SELECT COUNT(id) as total_orders FROM order WHERE user_no='".$_GET["user_no"]."' AND MONTH(entry_date)=MONTH(CURRENT_DATE()) AND YEAR(entry_date) = YEAR(CURRENT_DATE())";
        $result = $conn->query($sql1);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders = $row["total_orders"];
            }
        } else {
            $orders = 0;
        }
        
        $result = array();
        $result["series"] = $series;
        $result["labels"] = $labels;
        $result["orders"] = $orders;
        
        echo json_encode($result);
    }
   
     else if ($_GET["type"] == "downloadPO") {
        $_GET['filename'] = 'MEDIA STOCK REGISTER'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Received Purchase Order Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;"> Sr.</td>
                    <td style="width:20%;">	Order No</td>
                    <td style="width:20%;">	Client Name</td>
                    <td style="width:10%;">	Po No</td>
                    <td style="width:10%;">	Po Date</td>
                    <td style="width:15%;">	Valid till</td>
                    <td style="width:15%;">Status</td>
                </tr>
            </thead>';
            $i=1;
          $sql = "SELECT p.*, c.c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status !='pending' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/po_entry/".$row["file"];
                $row["products"] = json_decode($row["products"]);
                $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i++.'.</td>
                         <td style="width: 20%;">'.$row['order_no'].'</td>
                          <td style="width: 20%;">'.$row['c_name'].'</td>
                        <td style="width: 10%;">'.$row['po_no'].'</td>
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row['valid_till'])).'</td>
                        <td style="width: 15%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
     }
    else if($_GET["type"] == "receivedPOpdf") {
        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] ='onlyheader'; include("../pdfimp2.php");
        $sql = "SELECT p.*, c.company, c.address, c.phone, c.email, c.gst_no FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/poentry/".$row["file"];
                $row["products"] = json_decode($row["products"]);
                $row["configurations"] = json_decode($row["configurations"]);
                $row["terms"] = json_decode($row["terms"]);
                $html.='
                <table>
                <h2 style="text-align:center">Purchase Order</h2>
            <tr>
                <td>
                    Vendor: '.$row['company'].'<br>
                    Address: '.$row['address'].'<br>
                    Mobile No: '.$row['phone'].'
                    &nbsp;&nbsp;&nbsp; Fax: 0 
                    &nbsp;&nbsp;&nbsp; Email: '.$row['email'].'<br>
                    GSTIN No: '.$row['gst_no'].'
                </td>
                <td>
                    PO No,: '.$row['po_no'].'<br>
                    PO Date: '.$row['po_date'].'
                </td>
            </tr>
        </table>
        <table cellpadding="5">
            <tr style="text-align:center; font-weight:bold;">
                <td rowspan="2" style="border:solid 1px BCBBBA;">Item Code</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Item</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Order Quantity (No.)</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Rate (INR)</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Amount (withoput tax)</td>
                <td colspan="3" style="border:solid 1px BCBBBA;">GST(%)</td>
                <td rowspan="2" style="border:solid 1px BCBBBA;">Total Amount</td>
            </tr>
            <tr style="text-align:center; font-weight:bold;">
                <td style="border:solid 1px BCBBBA;">SGST ()</td>
                <td style="border:solid 1px BCBBBA;">CGST ()</td>
                <td style="border:solid 1px BCBBBA;">IGST ()</td>
            </tr>';
            
            $total = 0;
            $products = $row["products"];
                for ($i = 0; $i < count($products); $i++) {
                    $product = $products[$i];
                    $sql1 = "SELECT * FROM product WHERE product_code='".$product->product_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $product->product_name = $row1["product_name"];
                            $product->dosage_form = $row1["dosage_form"];
                            $product->grade = $row1["grade"];
                        }
                    }
                    $products[$i] = $product;
               
                    $html.='
                    <tr>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->product_code.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->product_name.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->qty.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->rate.'</td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->amount.'</td>
                        <td style="border:solid 1px BCBBBA;"></td>
                        <td style="border:solid 1px BCBBBA;"></td>
                        <td style="border:solid 1px BCBBBA;"></td>
                        <td style="border:solid 1px BCBBBA;">'.$products[$i]->amount.'</td>
                    </tr>
                    ';
                    $total += +$products[$i]->amount;
                }
            $html.='
            <tr>
                <td style="border:solid 1px BCBBBA;" colspan="7"></td>
                <td style="border:solid 1px BCBBBA;">Total</td>
                <td style="border:solid 1px BCBBBA;">'.round($total, 2).'</td>
            </tr>
        </table>
        <table cellpadding="5">
            <tr>
                <td></td>
            </tr>
            <tr>
                <td>(Signature of Authorised Signatory)<br>Seal and Stamp<br></td>
            </tr>
            <tr>
                <td>
                 Copy to: 
                 1. Supplier copy<br>2. Acceptance copy by Supplier<br>3. Store copy<br>4. Accouts copy<br>5. Master copy
                </td>
            </tr>
        </table>
                ';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PO.pdf', 'I');
    } else if ($_GET["type"] == "getPODetails") {    
        
        $output = Array();
        $sql = "SELECT p.*, c.c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE p.id = '".$_GET["id"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["terms"] = json_decode($row["terms"]);

                $output1 = array();
                 $sql1 = "SELECT o.*, p.product_name, p.product_type, p.grade FROM order_materials o LEFT JOIN product p ON o.product_code=p.product_code WHERE o.order_no='".$row["order_no"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else if ($_GET["type"] == "editPO") {
        // $sql = "UPDATE po_entry SET status='amend',products='".json_encode($input["products"])."' WHERE id='".$_GET["id"]."'";
         $sql = "UPDATE po_entry SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' , company_unit = '".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadLog") {
        $_GET['filename'] = 'Received Purchase Order'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        
            $i=1;
               $sql = "SELECT p.*, c.c_name FROM po_entry p LEFT JOIN client c ON p.client_code=c.client_code WHERE
        p.user_no='".$_GET["user_no"]."' AND p.status !='pending' and  p.po_no='".$_GET["po_no"]."' ORDER BY p.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html= "";
        
        $html.='
        <h2 style="text-align:center">Received Purchase Order</h2>
        <table cellpadding="5" border="1">
       

                   <tr>
                        <td style="width:30%; text-align:centre;"><b>Client Name:</b></td>
                        <td style="width:70%;">'.$row['c_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>PO No.:</b></td>
                        <td style="width:70%;">'.$row['po_no'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>PO Date:</b></td>
                        <td style="width:70%;">'.$row['po_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>PO Type:</b></td>
                        <td style="width:70%;">'.$row['po_type'].'</td>
                    </tr>
                    <tr>
                        <td style="width:30%; text-align:centre;"><b>Valid Till:</b></td>
                        <td style="width:70%;">'.$row['valid_till'].'</td>
                    </tr>
                </table>
                <h3>Products:</h3>
                <table cellpadding="5" border="1">
                    <tr>
                        <td style="width:5%; text-align:centre;"><b>Sr</b></td>
                        <td style="width:10%; text-align:centre;"><b>Dosage Form</b></td>
                        <td style="width:10%; text-align:centre;"><b>Product Name</b></td>
                        <td style="width:10%; text-align:centre;"><b>Grade</b></td>
                        <td style="width:10%; text-align:centre;"><b>Packing Style</b></td>
                        <td style="width:10%; text-align:centre;"><b>Rate</b></td>
                        <td style="width:5%; text-align:centre;"><b>Qty</b></td>
                        <td style="width:20%; text-align:centre;"><b>Packing Configuration</b></td>
                        <td style="width:10%; text-align:centre;"><b>Amount INR</b></td>
                        <td style="width:10%; text-align:centre;"><b>Amount USD</b></td>
                    </tr>';
                    
                  $json_obj = $row['products'];
$array = json_decode($json_obj, true);
$i = 1;

foreach ($array as $values) {
    $html .= '
        
        <tr>
            <td>' . $i . '</td>
            <td>' . $values['dosage_form'] . '</td>
            <td>' . $values['product_code'] . '</td>
            <td>' . $values['product_name'] . '</td>
            <td>' . $values['grade'] . '</td>
            <td>' . $values['packing_style'] . '</td>
            <td>' . $values['rate'] . '</td>
            <td>' . $values['order_qty'] . $values['unit'] . '</td>
            <td>' . $values['packing_configuration'] . '</td>
            <td>' . $values['amount_usd'] . '</td>
            <td>' . $values['amount_inr'] . '</td>
        </tr>';
    $i++;
}

$html .= '
    </table>
    <h3>Payment Terms & Conditions:</h3>
    <table cellpadding="5" border="1">
        <tr>
            <td style="width:30%; text-align:center;"><b>Sr</b></td>
            <td style="width:70%; text-align:center;"><b>Payment Terms</b></td>
        </tr>';

$json_obj = $row['terms'];
$array = json_decode($json_obj, true);
$i = 1;

foreach ($array as $values) {
    $html .= '
     
            <tr>
                        <td style="width:30%; text-align:center;">' . $i . '</td>
                        <td style="width:70%;">' . $values['term'] . '</td>
                    </tr> 
        
        
        ';
    $i++;
}

$html .= '
    </table>';
                
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Received Purchase Order.pdf', 'I');
     }
}

$conn->close();
?>