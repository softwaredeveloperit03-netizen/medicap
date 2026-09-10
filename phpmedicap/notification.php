<?php
    require 'db.php';
    require 'token.php';
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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
 
   
    
   
     if ($_GET["type"] == "getRequisitionrevisionNotification") {
        $output = array();
        $sql = "SELECT count(id) as Pending_req  FROM manpower where status='revision' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        
        // $output['Pending_req'] =3;
              $test = "You Have '".$output['Pending_req']."' Manpower Requisition Pending For Revision";
            $output['text'] = $test;
    
    echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingInterviews") {
        $output = Array();
 
   $sql=" SELECT count(id) as Pending_interview FROM candidate    WHERE isInterviewCompleted='no' and primary_int_comp='yes' 
   AND interviewer_int_comp='no' ORDER BY id desc";
   
  
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
    
    //$output['Pending_interview'] =3;
    
         $test = "You Have '".$output['Pending_interview']."' Awaiting Interview Form";
            $output['text'] = $test;
    
    echo json_encode($output);
    } 
    else if ($_GET["type"] == "pendingChallanVeri") {
        $output = Array();
 
 
    $sql = "SELECT  count(id) as pendingChallanVeri  FROM challan 
        WHERE plant_id= '".$_GET["plant_id"]."' AND next_stage='Checking' AND material_type != 'Miscellaneous' AND user_no='".$_GET["user_no"]."'"; 
   
  
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
    
 
         $test = "You Have '".$output['pendingChallanVeri']."' Challan Pending For Verification";
            $output['text'] = $test;
    
    echo json_encode($output);
    } 
    
     else if ($_GET["type"] == "getInprocessReceivingsLeveragesNotification") {  
        $output = Array();
                $sql = "SELECT  count(c.id) as Pending_Levarage  FROM challan_materials c left join challan c1 On c.challan_no = c1.challan_no 
            WHERE c1.plant_id= '".$_GET["plant_id"]."' AND c.status='TO_PLANT_HEAD' ";

     
      	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
    
    
    
         $test = "You Have '".$output['Pending_Levarage']."' Pending  Leverages For Approval ";
            $output['text'] = $test;
    
    echo json_encode($output);
         
    } 
    else if ($_GET["type"] == "getIndentForApprovalPlantHeadNotification") {
 
        $output = Array();
        
          $sql = "SELECT   count(id) as Pending_indent   FROM indend_raw
        WHERE   plant_id='".$_GET["plant_id"]."'   AND status = 'TO_PlantHead'";
      
      	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
     // $output['Pending_indent'] =3;
     
    
         $test = "You Have '".$output['Pending_indent']."' Pending  Indent For Approval ";
            $output['text'] = $test;
    
    echo json_encode($output);
    
    }
    else if ($_GET["type"] == "DirectorIndentNotification") {
 
        $output = Array();
        
          $sql = "SELECT   count(id) as Pending_indent   FROM indend_raw
        WHERE   plant_id='".$_GET["plant_id"]."'   AND status = 'TO_Director'";
      
      	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
     // $output['Pending_indent'] =3;
     
    
         $test = "'".$output['Pending_indent']."' Indent Pending  For Director Approval ";
            $output['text'] = $test;
    
    echo json_encode($output);
    
    }
    else if ($_GET["type"] == "VpindentNotification") {
 
        $output = Array();
        
          $sql = "SELECT   count(id) as Pending_indent   FROM indend_raw
        WHERE   plant_id='".$_GET["plant_id"]."'   AND status = 'TO_VP'";
      
      	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output = $row;
    		}
    	}
     // $output['Pending_indent'] =3;
     
    
         $test = " '".$output['Pending_indent']."' Indent Pending  For Vp Approval ";
            $output['text'] = $test;
    
    echo json_encode($output);
    
    }
    else if ($_GET["type"] == "ExpiryNotification") {
 
    
        
                   
$output = array();
$materials = array();

$plant_id = $_GET["plant_id"];
$months = 1; // Default to 1 month if not provided

// Calculate the future date based on the provided months
$future_date = new DateTime();
$future_date->modify("+{$months} months");

$sql = "SELECT s.material_code, s.ar_no, s.qty, s.exp_date, s.batch_no, s.id, m.material_name, m.material_type 
        FROM stock_book s 
        LEFT JOIN material m ON s.material_code = m.material_code  
        WHERE m.plant_id = '$plant_id' AND s.status = 'Approved'";
$result = $conn->query($sql);
$total_expired_count=0;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $current_date = new DateTime();
        $exp_date = new DateTime($row['exp_date']);
        $interval = $current_date->diff($exp_date);
        $days_left = (int)$interval->format("%r%a");

        if ($days_left < 0) {
            $row['days_left_to_expiry'] = 'Expired';
        } else {
            $row['days_left_to_expiry'] = $days_left;
        }

        if ($exp_date <= $future_date) {
            $output[] = $row;
            $total_expired_count++;
        }
    }
}

  
                        
                        // Append the total expired material count to the output array
                        $output['Pending_expiry'] = $total_expired_count;
                        
                         
     
    
         $test = "You Have '".$output['Pending_expiry']."' Material Expiring In One Month ";
            $output['text'] = $test;
    
    echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getCheckedIndendsForNotification") {
 
        $output = Array();
        
          $sql = "SELECT count(request_no) as Pending_indent   FROM indend_raw WHERE status ='pending' and plant_id='".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
   
                $output = $row;
            }
        }
         $test = "You Have '".$output['Pending_indent']."' Indent Pending For Approval";
            $output['text'] = $test;
    
    echo json_encode($output);
    }
        else if ($_GET["type"] == "getPendingVendorsNotification") {
        $output = Array();
          $sql = "SELECT count(*) as Pending_Vendor FROM vendor   WHERE  plant_id='".$_GET["plant_id"]."'  AND status = 'checked'   order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
         $test = "You Have '".$output['Pending_Vendor']."' Vendor Pending For Approval";
            $output['text'] = $test;
    
    echo json_encode($output);
    } 

    else if ($_GET["type"] == "getPendingQuotationsForNotification") {
        $output = Array();
    
            $sql= "SELECT count(*) as Pending_quatation  FROM quotation_hdr  where status='pending' and plant_id = '".$_GET["plant_id"]."' ORDER BY id DESC";     
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output = $row;
                }
            }
            
            $test = "You Have '".$output['Pending_quatation']."' Quatation Pending For Approval";
            $output['text'] = $test;
        
        echo json_encode($output);
        
    }    else if ($_GET["type"] == "getAllPendingPOForNotification") {
       $output = array();
     
           $sql="SELECT count(*) as Pending_Po  FROM purchaseorder  WHERE  plant_id= '".$_GET["plant_id"]."' AND status='Pending' ORDER by id desc";
        
        $result = $conn->query($sql);
        // print_r($result);exit;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        
        
   $test = "You Have '".$output['Pending_Po']."' Po Pending For Approval";
        
         $output['text'] = $test;
    
    echo json_encode($output);
    
        
        
        
        
    }
        else if ($_GET["type"] == "getPendingFromPoNotification") {
       $output = array();
     
           $sql="SELECT count(*) as Pending_FromPo  FROM challan  WHERE plant_id= '".$_GET["plant_id"]."' and type NOT in('Local') and (status='pending' or status='')  ORDER by id desc";
        
        $result = $conn->query($sql);
        // print_r($result);exit;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        
        
   $test = "You Have '".$output['Pending_FromPo']."' Inword Pending In From Po";
        
         $output['text'] = $test;
    
    echo json_encode($output);
     
    }




    
     
     

 }else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>