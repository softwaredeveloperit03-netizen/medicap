<?php
 
//  ini_set('display_errors', 1);
// error_reporting(E_ALL);


require '../db.php';
require '../token.php';
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
    
    if($_GET["type"]=="SaveMRP") {
     
      
$sql = "INSERT INTO po_Entry_MRP (
            plant_id,`date`,orderFor,product_name,product_code,amt_given,production,
    brak_production,remaining_qty,order_qty,avbl_fg_stock,brak_packing,ec,qty_to_prepare,
    entry_by,entry_date,approve_by,approve_date
) VALUES ('".$input['plant_id']."', '".$input['date']."', '".$input['orderFor']."', '".$input['product_name']."',
 '".$input['product_code']."', '".$input['amt_given']."', '".$input['production']."',
  '".$input['brak_production']."', '".$input['remaining_qty']."', '".$input['order_qty']."',
   '".$input['avbl_fg_stock']."', '".$input['brak_packing']."', '".$input['ec']."',
    '".$input['qty_to_prepare']."', '".$input['entry_by']."', 
    '".$input['entry_date']."', '".$input['approve_by']."', '".$input['approve_date']."'
)"; 
          
 
          
        if($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
      
    } 
    else if($_GET["type"]=="getPendingClients") {
        $output = array();
    	$sql = "SELECT c.*, a.agent_name, s.state_name FROM client c LEFT JOIN agent a ON c.agent_no=a.agent_no LEFT JOIN state s ON c.state_code=s.state_code WHERE c.status='pending' AND c.user_no='".$_GET["user_no"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["branch"] = json_decode($row["branch"]);
    		    $row["divisions"] = json_decode($row["divisions"]);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getEntryData") {
        $output = array();
    	$sql = "select * from po_Entry_MRP";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getWOEntryData") {
        $output = array();
    	$sql = "SELECT 
                        product_code,
                        product_name,
                        IFNULL(SUM(order_qty), 0) AS order_qty,
                        IFNULL(SUM(qty_to_prepare), 0) AS qty_to_prepare
                    FROM 
                        po_Entry_MRP
                    GROUP BY 
                        product_code, product_name;
                    ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    
    		     $output1 = Array();
                        $sql1 = "SELECT *,oder_qty as Qty,balance_qty as bal_qty FROM split_planning_qty  WHERE product_code='".$row["product_code"]."' order by id asc ";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
    		    
    		  $row["splits"] = $output1;
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    
    
     else if($_GET["type"]=="saveWOEntryData") {
         
          
       
  $sql = "INSERT INTO split_planning_qty (product_name, product_code, month, year, 
        oder_qty,balance_qty,plant_id,entry_by)
       VALUES ('".$input["product_name"]."','".$input["product_code"]."','".$input["month"]."',
       '".$input["year"]."','".$input["Qty"]."','".$input["bal_qty"]."','".$_GET["plant_id"]."',
       '".$_GET["emp_id"]."')";
        if ($conn->query($sql)) {
       
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
     }
     
     
         if($_GET["type"]=="DeleteWOEntryData") {
     
      
$sql = "delete from split_planning_qty where id='".$_GET['id']."'"; 
          
 
          
        if($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
      
    } 
     
 
}

$conn->close();
?>