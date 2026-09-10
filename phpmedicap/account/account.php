<?php
require '../db.php';
require '../token.php';
//   ini_set('display_errors', 1);
//  error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if($_GET["type"]=="getaccheadlog") {
        $output = array();
    	$sql = "SELECT * FROM accounthead ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }else if($_GET["type"]=="getjournallist") {
       
             $output1 = array();
     	$sql1 = "SELECT client_code ,LglNm , TrdNm FROM ledgers  ";
    	$result1 = $conn->query($sql1);
    	if($result1->num_rows > 0){
    		while($row1 = $result1->fetch_assoc()) {
       
                $output = array();
             	$sql = "SELECT v.*,a.accheadname FROM voucherentry v left join accounthead a  ON v.accHead=a.accheadno where client_code = '".$row1['client_code']."' ";
            	$result = $conn->query($sql);
            	if($result->num_rows > 0){
            		while($row = $result->fetch_assoc()) {
            		    $output[] = $row;
            		}
            	}	
            	
            	$row1['jouralEntry']  = $output;
            	 $output1[] = $row1;
            	
    		}
    	}	
    	
    	
    	
    	
        echo json_encode($output1);
    
        
    }  else  if($_GET["type"]=="addacchead") {
      
          $sql = "INSERT INTO accounthead (accheadname,plant_id,user_no) 
       VALUES ('".$input["accheadname"]."','".$_GET["plant_id"]."','".$_GET["user_no"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     	
        
    }
        else if ($_GET["type"] == "getInvoicesLog") {
        // $output = array();
        // $sql = "SELECT t.*, DATE(t.entry_date) as entry_date, c.Pin, c.LglNm, c.Addr1, c.Addr2, c.Loc, c.gst_no,s1.state_name FROM tax_invoice t LEFT JOIN client c ON t.client_code=c.client_code LEFT JOIN state s1 ON c.state_code=s1.state_code WHERE t.user_no='".$_GET["user_no"]."' AND t.client_code LIKE '%".$_GET['client_code']."' AND DATE(t.entry_date) BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."' ORDER BY t.id DESC";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
        //         $output1 = array();
        //         $sql1 = "SELECT * FROM tax_invoice_product WHERE user_no='".$_GET["user_no"]."' AND order_no='".$row["id"]."'";
        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        //                 $sql2 = "SELECT * FROM product WHERE product_code='".$row1["product_code"]."'";
        //                 $result2 = $conn->query($sql2);
        //                 if ($result2->num_rows > 0) {
        //                     while ($row2 = $result2->fetch_assoc()) {
        //                         $row1["product_name"] = $row2['product_name'];
        //                     }
        //                 }
        //                 $output1[] = $row1;
        //             }
        //         }
        //         $row["client_name"] ='';
        //         $sql2 = "SELECT * FROM client WHERE client_code='".$row["client_code"]."'";
        //         $result2 = $conn->query($sql2);
        //         if ($result2->num_rows > 0) {
        //             while ($row2 = $result2->fetch_assoc()) {
        //                 $row["client_name"] = $row2['company'];
        //             }
        //         }
        //         $row["materials"] = $output1;
        //         $output[] = $row;
        //     }
        // }
        // echo json_encode($output);
        
         $output = array();
        
         $sql = "SELECT s.*, c.Pin, c.LglNm, c.Addr1, c.Addr2, c.city, c.gst_no, s1.state_code
        FROM sales s LEFT JOIN client c ON s.client_code=c.client_code LEFT JOIN state s1 ON
        c.state_code=s1.state_code WHERE  s.user_no='".$_GET["user_no"]."' 
        AND s.client_code LIKE '%".$_GET['client_code']."' AND DATE(s.entry_date) 
        BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."' ORDER BY s.id DESC";
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

    else  if($_GET["type"]=="addvoucherentry") {
        
        
        
               $json_obj = json_encode($input["particular"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
   $sql = "INSERT INTO voucherentry (plant_id, user_no, client_code, accHead, 
        particular,narration,credit_type, credit,debit,amount,entry_by)
       VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$input["client_name"]."',
       '".$input["accHead"]."','".$values["particular"]."','".$values["narration"]."','".$values["credit_type"]."',
       '".$values["credit"]."','".$values["debit"]."','".$values["amount"]."','".$_GET["emp_id"]."')";
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
        
        
        
        
        
    //   echo $sql = "INSERT INTO voucherentry (plant_id, user_no, client_code, accHead, 
    //     particular,narration,credit_type, credit,debit,amount,entry_by)
    //   VALUES ('".$input["accheadname"]."','".$_GET["plant_id"]."','".$_GET["user_no"]."','".$input["client_name"]."',
    //   '".$input["accHead"]."','".json_encode($input["particular"])."','".$input["narration"]."','".$input["credit_type"]."',
    //   '".$input["credit"]."','".$input["debit"]."','".$input["amount"]."','".$_GET["emp_id"]."')";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
     	
        
    } 
   
  
   
   
}
else{
    echo 'Invalid Token';
}
$conn->close();
?>