<?php
try{
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

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
  
    if ($_GET["type"] == "saveStockTranferRequest") {
        
        $sql = "INSERT INTO `stockTranfer`(`plant_id`, `reqPlantId`,`reqPlantName`, `material_code`, `material_type`, `material_name`, `qty`, `unit`,
        `entry_by`, `entry_date`, `plantHeadApproveal`, `reqplantHeadApproval`,`status`,despensing) VALUES ('".$_GET["plant_id"]."','".$input["reqPlantId"]."',
        '".$input["reqPlantName"]."','".$input["material_code"]."', '".$input["material_type"]."', '".$input["material_name"]."', '".$input["qty"]."',
        '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date','Pending','Pending','Pending','Pending')";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "ApproveStockReqPlantHead") {
        
        $sql = "UPDATE stockTranfer  SET  plantHeadApproveal = '".$_GET["emp_id"]."' , plantHeadApprovalOn = '$entry_date' ,
        status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "ApproveStockReqOtherPlantHead") {
        
        $sql = "UPDATE `stockTranfer`  SET  reqplantHeadApproval = '".$_GET["emp_id"]."' , reqplantHeadAppOn = '$entry_date' ,
        status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "approveFromQA") {
        
        $sql = "UPDATE `stockTranfer`  SET  qa_approv_by = '".$_GET["emp_id"]."' , qa_approv_on = '$entry_date' ,
        status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "securtyApproval") {
        
        $sql = "UPDATE `stockTranfer`  SET  sec_approve_by = '".$_GET["emp_id"]."' , sec_approve_on = '$entry_date' ,
        status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "securtyApprovalinward") {
        
        $sql = "UPDATE `stockTranfer`  SET  secu_in_by = '".$_GET["emp_id"]."' , secu_in_on = '$entry_date' ,
        status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "ApproveMaterialStock") {
        
        $sql = "UPDATE `stock_book`  SET  status = '".$_GET["status"]."' , approve_date = '$entry_date' ,
        approve_by = '".$_GET["emp_id"]."' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "SaveDespensing") {
        
           
        $sql = "UPDATE `stockTranfer`  SET  despensingBy = '".$input["operator"]."' , despensingOn = '$entry_date' ,arData = '".json_encode($input["arData"])."',
        status = 'For_QA_Approval' , despensing = 'Despensed' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
                  
            $batches = $input["arData"];
        for ($i = 0; $i < count($batches); $i++) {
            $material = $batches[$i];
            $sql = "INSERT INTO material_issue (plant_id,grn_no, ar_no,material_code, batch_no,issue_for, qty,unit, entry_by, entry_date) 
            VALUES ('".$_GET["plant_id"]."',   '".$material["grn_no"]."','".$material["ar_no"]."', '".$material["material_code"]."',
            '".$material["batch_no"]."', 'Stock Tranfer', '".$material["disensedpQty"]."','".$material["unit"]."', '".$_GET["emp_id"]."',
            '$entry_date')";
            
            $conn->query($sql);
        }
            
             
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
       
    }  
    else if ($_GET["type"] == "receivedtransferMaterial") {
        
           
        $sql = "UPDATE `stockTranfer`  SET  receivedBy = '".$input["operator"]."' , receivedOn = '$entry_date' ,
        status = '".$_GET["status"]."' , despensing = 'Despensed' WHERE id = '".$_GET["id"]."' ";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
                  
            $batches = $input["arData"];
        for ($i = 0; $i < count($batches); $i++) {
            $material = $batches[$i];
            $sql = "INSERT INTO stock_book (plant_id,grn_no, ar_no,batch_no, stock_type,material_code, qty,unit, mfg_date,
            exp_date,grn_date,status,entry_by,entry_date) 
            VALUES ('".$_GET["plant_id"]."',   '".$material["grn_no"]."','".$material["ar_no"]."', '".$material["batch_no"]."',
              'Stock Tranfer', '".$input["material_code"]."','".$material["disensedpQty"]."','".$material["unit"]."',
              '".$material["mfg_date"]."','".$material["exp_date"]."','".$material["grn_date"]."','PendingST',
              '".$_GET["emp_id"]."','$entry_date')";
            
            $conn->query($sql);
        }
            
             
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
       
    }  


    
    else if ($_GET["type"] == "getreqLog") {
                 
        $output = array();
                
            $sql132 = "SELECT * FROM stockTranfer WHERE  plant_id= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "getMaterials") {
                 
        $output = array();
                
            $sql132 = "SELECT id,material_name,material_code,material_type FROM material WHERE
            material_type =  '".$_GET["material_type"]."' AND  plant_id= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getMaterialForApprovalStock") {
                 
        $output = array();
                
             $sql132 = "SELECT s.*,m.material_name,m.material_type FROM stock_book s left join  material m on s.material_code=m.material_code WHERE
            s.status =  'PendingST' AND  s.plant_id= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "getTranferReqForPlantHeadApprovel") {
                 
        $output = array();
                
            $sql132 = "SELECT * FROM stockTranfer WHERE status = 'Pending' AND  plant_id= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "fromPlantHeadApproval") {
                 
        $output = array();
                
            $sql132 = "SELECT s.*,p.plant_name as reqFrom FROM stockTranfer s left join plant p ON s.plant_id = p.plant_id 
            WHERE ( s.status = 'TO_REQUESTED_PLANT_HEAD' OR s.status = 'Despensed_Plant_Head_Approval' ) AND  s.reqPlantId= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                    $row132['arData'] = json_decode($row132['arData']);

                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "forDespensing") {
                 
        $output = array();
                
            $sql132 = "SELECT s.*,p.plant_name as reqFrom FROM stockTranfer s left join plant p ON s.plant_id = p.plant_id 
            WHERE s.status = 'FOR_DESPENSING' AND  s.reqPlantId= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                     
                    
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "For_QA_Approval") {
                 
        $output = array();
                
            $sql132 = "SELECT s.*,p.plant_name as reqFrom FROM stockTranfer s left join plant p ON s.plant_id = p.plant_id 
            WHERE s.status = 'For_QA_Approval' AND  s.reqPlantId= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                    $row132['arData'] = json_decode($row132['arData']);
                     
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "To_Security_outward") {
                 
        $output = array();
                
            $sql132 = "SELECT s.*,p.plant_name as reqFrom FROM stockTranfer s left join plant p ON s.plant_id = p.plant_id 
            WHERE s.status = 'To_Security_outward' AND  s.reqPlantId= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                    // $row132['arData'] = json_decode($row132['arData']);
                     
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "To_Security_Inward") {
                 
        $output = array();
                
            $sql132 = "SELECT s.*,p.plant_name as reqFrom FROM stockTranfer s left join plant p ON s.plant_id = p.plant_id 
            WHERE s.status = 'To_Security_Inward' AND  s.plant_id= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                    // $row132['arData'] = json_decode($row132['arData']);
                     
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "For_Receiving") {
                 
        $output = array();
                
            $sql132 = "SELECT s.*,p.plant_name as reqFrom FROM stockTranfer s left join plant p ON s.plant_id = p.plant_id 
            WHERE s.status = 'For_Receiving' AND  s.plant_id= '".$_GET["plant_id"]."'";
            $result132 = $conn->query($sql132);
            if ($result132->num_rows > 0) {
                while ($row132 = $result132->fetch_assoc()) {
                     $row132['arData'] = json_decode($row132['arData']);
                     
                     $output[] =  $row132;
                }
            }
            
        echo json_encode($output);
        
    }
    
    
    
    else if ($_GET["type"] == "getStockBookByMaterialCode") {
     
                $sql11 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$_GET["material_code"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                         $dispensing_qty = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                    }
                }
                
                $sql12 = "SELECT  IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE    material_code= '".$_GET["material_code"]."'";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                         $received_qty = number_format((float)$row12["received_qty"], 2, '.', '');
                    }
                }
                
                $sql13 = "SELECT  IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE  material_code= '".$_GET["material_code"]."'";
                $result13 = $conn->query($sql13);
                if ($result13->num_rows > 0) {
                    while ($row13 = $result13->fetch_assoc()) {
                         $undertest_qty = number_format((float)$row13["undertest_qty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE  status = 'Rejected' AND material_code= '".$_GET["material_code"]."'";
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $totalRejectdQty = number_format((float)$row132["totalRejectdQty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(expiredQty), 0) as totalExpiredQty   FROM stock_book WHERE  status = 'Expired' AND material_code= '".$_GET["material_code"]."'";
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $totalExpiredQty = number_format((float)$row132["totalExpiredQty"], 2, '.', '');
                    }
                }
                
                $sql14 = "SELECT  s.id,s.unit,s.artwork_no,s.version_no,m.material_name,m.material_code 
                FROM stock_book s left join material m ON m.material_code = s.material_code
                WHERE  s.material_code= '".$_GET["material_code"]."' order by s.id desc limit 1";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                          $unit = $row14["unit"];
                          $artwork_no = $row14["artwork_no"];
                          $version_no = $row14["version_no"];
                          $material_name = $row14["material_name"];
                          $material_code = $row14["material_code"];
                    }
                }
                
           
                
                
                $sql14 = "SELECT artwork_no,version_no FROM artwork WHERE  material_code= '".$_GET["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                           $curr_version_no = $row14["version_no"];
                    }
                }else{
                    $curr_version_no = "Art Work Not Avaliable";
                    
                }
                
                $balance_qty = number_format($received_qty- ( $dispensing_qty + $undertest_qty + $totalRejectdQty +  $totalExpiredQty), 3, '.', '');
                $output = array();
                
              $output['balance_qty']=$balance_qty;
              $output['unit']=$unit;
              $output['artwork_no']=$artwork_no;
              $output['stock_version_no']=$version_no;
              $output['curr_version_no']=$curr_version_no;
              $output['material_code']=$material_code;
              $output['material_name']=$material_name;
              
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "getarDataByMaterial") {
        
                $output1 = array();
                $sql15 = "SELECT id,plant_id,grn_no,ar_no,batch_no,stock_type,material_code,qty,unit,mfg_date,exp_date,status  ,undertest_qty
                FROM stock_book WHERE  material_code= '".$_GET["material_code"]."'";
                $result15 = $conn->query($sql15);
                if ($result15->num_rows > 0) {
                    while ($row15 = $result15->fetch_assoc()) {
                        
                        $sql113 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   grn_no= '".$row15["grn_no"]."' AND 
                        ar_no= '".$row15["ar_no"]."'";
                        $result113 = $conn->query($sql113);
                        if ($result113->num_rows > 0) {
                            while ($row113 = $result113->fetch_assoc()) {
                                 $row15["dispensing_qty"] = number_format((float)$row113["dispensing_qty"], 2, '.', '');
                            }
                        }           
                        
                        $row15["qty"] = number_format((float) $row15["qty"], 2, '.', '');
                        $row15["undertest_qty"] = number_format((float) $row15["undertest_qty"], 2, '.', '');
                        $row15["balance_qty"] = number_format($row15["qty"] - ($row15["dispensing_qty"] + $row15["undertest_qty"]), 2, '.', '');
                        
                        
                       $sql9 = "SELECT IFNULL(max(rate), 0) as max_rate, IFNULL(min(rate), 0) as min_rate FROM challan_materials WHERE grn = 'approve' AND   material_code= '".$row15["material_code"]."'";
                        $result9 = $conn->query($sql9);
                        if ($result9->num_rows > 0) {
                            while ($row9 = $result9->fetch_assoc()) {
                                 $row15["max_rate"] = number_format((float)$row9["max_rate"], 2, '.', '');
                                 $row15["min_rate"] = number_format((float)$row9["min_rate"], 2, '.', '');
                            }
                        }  
                        
                        
                      $sql = "SELECT rate FROM challan_materials WHERE   grn_no= '".$row15["grn_no"]."' AND material_code= '".$row15["material_code"]."'";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                 $row15["rate"] = number_format((float)$row["rate"], 2, '.', '');
                            }
                        }else{
                            $row15["rate"]  = 0.00;
                        }
                         
                            $row15["Stock_value"] = number_format($row15["balance_qty"] *  $row15["rate"] , 2, '.', '');

                        $row15["selected"]  = false;
                        
                        $output1[] = $row15;
                        
                    }
                }
              
        
        echo json_encode($output1);
        
    }
      
    else if ($_GET["type"] == "getStockBook") {
        $output = array();
          $sql = "SELECT  material_code,material_name,material_type,material_subtype  from  material  where plant_id = '".$_GET["plantID"]."' 
          AND (material_type = 'Raw Material' OR material_type = 'Packing Material')";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
                
                
                $sql11 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$row["material_code"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        
                         $row["dispensing_qty"] = number_format((float)$row11["dispensing_qty"], 2, '.', '');
                         
                    }
                }
                
                 $sql12 = "SELECT  IFNULL(SUM(qty), 0) as received_qty FROM stock_book WHERE    material_code= '".$row["material_code"]."'";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                       
                         $row["received_qty"] = number_format((float)$row12["received_qty"], 2, '.', '');
                      
                    }
                }
                
                 $sql13 = "SELECT  IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE  material_code= '".$row["material_code"]."'";
                $result13 = $conn->query($sql13);
                if ($result13->num_rows > 0) {
                    while ($row13 = $result13->fetch_assoc()) {
                        
                         $row["undertest_qty"] = number_format((float)$row13["undertest_qty"], 2, '.', '');
                      
                    }
                }
                
                
                $sql131 = "SELECT  IFNULL(SUM(qty), 0) as totalUndetestQty FROM stock_book WHERE  status = 'Under Test' AND material_code= '".$row["material_code"]."'";
                $result131 = $conn->query($sql131);
                if ($result131->num_rows > 0) {
                    while ($row131 = $result131->fetch_assoc()) {
                         $row["totalUndetestQty"] = number_format((float)$row131["totalUndetestQty"], 2, '.', '');
                    }
                }
                
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalQuarntineQty FROM stock_book WHERE  status = 'quarantine' AND material_code= '".$row["material_code"]."'";
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row["totalQuarntineQty"] = number_format((float)$row132["totalQuarntineQty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalRejectdQty FROM stock_book WHERE  status = 'Rejected' AND material_code= '".$row["material_code"]."'";
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row["totalRejectdQty"] = number_format((float)$row132["totalRejectdQty"], 2, '.', '');
                    }
                }
                
                $sql132 = "SELECT  IFNULL(SUM(expiredQty), 0) as totalExpiredQty   FROM stock_book WHERE  status = 'Expired' AND material_code= '".$row["material_code"]."'";
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $row["totalExpiredQty"] = number_format((float)$row132["totalExpiredQty"], 2, '.', '');
                          
                    }
                }
                
                
                
                $sql132 = "SELECT  IFNULL(SUM(qty), 0) as totalApprovedQty, IFNULL(SUM(undertest_qty), 0) as undertest_qty FROM stock_book WHERE  status = 'Approved' AND material_code= '".$row["material_code"]."'";
                $result132 = $conn->query($sql132);
                if ($result132->num_rows > 0) {
                    while ($row132 = $result132->fetch_assoc()) {
                         $dispensing_qty =0;   
                         $undertest_qty =0;
                         
                        $row["totalApprovedQty"] = number_format((float)$row132["totalApprovedQty"], 2, '.', '');
                        $undertest_qty = number_format((float)$row132["undertest_qty"], 2, '.', '');
                         
                        $sql1320 = "SELECT  IFNULL(SUM(qty), 0) as dispensing_qty FROM material_issue WHERE   material_code= '".$row["material_code"]."'";
                        $result1320 = $conn->query($sql1320);
                        if ($result11->num_rows > 0) {
                            while ($row1320 = $result1320->fetch_assoc()) {
                                 $dispensing_qty = number_format((float)$row1320["dispensing_qty"], 2, '.', '');
                            }
                        }
                         
                         $row["totalApprovedQty"] = number_format($row["totalApprovedQty"]- ($dispensing_qty + $undertest_qty ), 2, '.', '');
                         
                    }
                }
                
                
             
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                           $row["unit"] = $row14["unit"];
                    }
                }
                
             
        
                 
                 
                $row["balance_qty"] = number_format($row["received_qty"]- ( $row["dispensing_qty"] + $row["undertest_qty"] + $row["totalRejectdQty"] +  $row["totalExpiredQty"]), 3, '.', '');
               
                
                $output[] = $row;
            }
        }
        
         
        
        echo json_encode($output);
        
    }


 
}

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
}
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}
?>