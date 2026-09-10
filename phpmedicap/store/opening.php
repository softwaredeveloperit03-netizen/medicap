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
  
    if ($_GET["type"] == "saveOpendingStock") {
        $sql = "INSERT INTO stock_book (stock_type,user_no, vendor_no, material_code, batch_no, qty, unit, mfg_date, exp_date, ar_no, grn_no, assay, status, entry_by, entry_date) VALUES ('Opening','".$_GET["user_no"]."','".$input["vendor_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["ar_no"]."', '".$input["grn_no"]."', '".$input["assay"]."', 'Approved', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStock") {
        $mat_type= $input["material_type"];
       
        if($mat_type!='Finish Product'){
         
            $batches = $input["material_list"];
        for ($i = 0; $i < count($batches); $i++) {
            $material = $batches[$i];
            $sql = "INSERT INTO stock_book (plant_id,vendor_no, product_code,inword_no, inword_date,stock_type, material_code,
            batch_no, qty, unit, ar_no, grn_no, grn_date, mfg_date, exp_date, status,pack_size,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["vendor_no"]."',
            '".$input["product_code"]."', '".$input["inword_no"]."', '".$input["inword_date"]."','Opening', '".$material["material_code"]."', 
            '".$material["batch_no"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["ar_no"]."', '".$material["grn_no"]."', 
            '".$material["grn_date"]."', '".$material["mfg_date"]."', '".$material["exp_date"]."', '".$material["status"]."', '".$material["pack_size"]."','$entry_date')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
        }
        else if($mat_type=='Finish Product'){
            $batches = $input["material_list"];
        for ($i = 0; $i < count($batches); $i++) {
            $material = $batches[$i];
            $sql = "INSERT INTO fg_stock_book (plant_id,vendor_no, product_code,inword_no, inword_date,stock_type, material_code,
            batch_no, qty, unit, ar_no, grn_no, grn_date, mfg_date, exp_date, status,pack_size,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["vendor_no"]."',
            '".$input["product_code"]."', '".$input["inword_no"]."', '".$input["inword_date"]."','Opening', '".$material["material_code"]."', 
            '".$material["batch_no"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["ar_no"]."', '".$material["grn_no"]."', 
            '".$material["grn_date"]."', '".$material["mfg_date"]."', '".$material["exp_date"]."', '".$material["status"]."', '".$material["pack_size"]."','$entry_date')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
        }
        
    }  
    else if ($_GET["type"] == "HOgetStockBook") {
        $output = array();
          $sql = "SELECT  material_code,material_name,material_type,material_subtype  from  material  where plant_id = '".$_GET["plant_id"]."' 
          AND material_type = '".$_GET["Material_type"]."'";
                            
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
                
                
                $grnCount = 0;
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                          $grnCount++;
                          $row["unit"] = $row14["unit"];
                    }
                }
                
             
                $sql14 = "SELECT  artwork_no,version_no  FROM artwork WHERE  material_code= '".$row["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                         
                          $row["artwork_no"] = $row14["artwork_no"].'-'.$row14["version_no"];
                    }
                }
                
            
                 $row["grnCount"] = $grnCount;
                 
                 
                $row["balance_qty"] = number_format($row["received_qty"]- ( $row["dispensing_qty"] + $row["undertest_qty"] + $row["totalRejectdQty"] +  $row["totalExpiredQty"]), 3, '.', '');
               
               
               $Stock_value =0;
               
               
                $sql15 = "SELECT id,material_code,qty,undertest_qty FROM stock_book WHERE  material_code= '".$row["material_code"]."'";
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
                        
                        
                      $sqlx = "SELECT rate FROM challan_materials WHERE   grn_no= '".$row15["grn_no"]."' AND material_code= '".$row15["material_code"]."'";
                        $resultx = $conn->query($sqlx);
                        if ($resultx->num_rows > 0) {
                            while ($rowx = $resultx->fetch_assoc()) {
                                 $row15["rate"] = number_format((float)$rowx["rate"], 2, '.', '');
                            }
                        }else{
                            $row15["rate"]  = 0.00;
                        }
                        
                        
                        
                        $Stock_value = number_format($Stock_value + ($row15["balance_qty"] *  $row15["rate"] ), 2, '.', '');

                         
                    }
                }
               
                $row['Stock_value'] = $Stock_value;
               
               
                $output[] = $row;
            }
        }
        
         
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getStockBook") {
        
        $output = array();
        
        $sql = "SELECT s.grn_no, s.ar_no, s.batch_no,
        MAX(COALESCE(NULLIF(TRIM(sb.batch_no), ''), s.batch_no)) as medicap_lot_no,
        s.material_code, SUM(s.qty) as totalQty,SUM(s.qty) as totalAvalQty, MAX(s.unit) as unit,MAX(s.tax_invoice) as tax_invoice, s.mfg_date, s.exp_date, MAX(s.retest_date) as retest_date, s.status,
        m.material_type,m.material_subtype,m.material_name,m.grade,v.vendor_name,
        (select c.LglNm from client c where c.client_code = s.clientGrpCode limit 1) as clientGrpCodeName,
        (select c.LglNm from client c where c.client_code = s.clientSubGrpCode limit 1) as clientSubGrpCodeName
        from  stock_book s 
        LEFT JOIN material m ON s.material_code = m.material_code  
        LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
        LEFT JOIN sampling_batches sb ON sb.material_code = s.material_code AND sb.grn_no = s.grn_no AND sb.ar_no = s.ar_no
        where  m.material_type = '".$_GET["Material_type"]."' group by s.material_code,s.batch_no,s.grn_no, s.ar_no,s.mfg_date, s.exp_date,s.status";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                
                $output[] = $row;
            }
        }
        
         
        
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getStockBookOld") {
        
        $output = array();
        
        
          $sql = "SELECT  material_code,material_name,material_type,material_subtype  from  material  where plant_id = '".$_GET["plant_id"]."' 
          AND material_type = '".$_GET["Material_type"]."'";
                            
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
                
                
                $grnCount = 0;
                $sql14 = "SELECT  id,unit FROM stock_book WHERE  material_code= '".$row["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                          $grnCount++;
                          $row["unit"] = $row14["unit"];
                    }
                }
                
             
                $sql14 = "SELECT  artwork_no,version_no  FROM artwork WHERE  material_code= '".$row["material_code"]."'";
                $result14 = $conn->query($sql14);
                if ($result14->num_rows > 0) {
                    while ($row14 = $result14->fetch_assoc()) {
                         
                          $row["artwork_no"] = $row14["artwork_no"].'-'.$row14["version_no"];
                    }
                }
                
            
                 $row["grnCount"] = $grnCount;
                 
                 
                $row["balance_qty"] = number_format($row["received_qty"]- ( $row["dispensing_qty"] + $row["undertest_qty"] + $row["totalRejectdQty"] +  $row["totalExpiredQty"]), 3, '.', '');
               
               
               $Stock_value =0;
               
               
                $sql15 = "SELECT id,material_code,qty,undertest_qty FROM stock_book WHERE  material_code= '".$row["material_code"]."'";
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
                        
                        
                      $sqlx = "SELECT rate FROM challan_materials WHERE   grn_no= '".$row15["grn_no"]."' AND material_code= '".$row15["material_code"]."'";
                        $resultx = $conn->query($sqlx);
                        if ($resultx->num_rows > 0) {
                            while ($rowx = $resultx->fetch_assoc()) {
                                 $row15["rate"] = number_format((float)$rowx["rate"], 2, '.', '');
                            }
                        }else{
                            $row15["rate"]  = 0.00;
                        }
                        
                        
                        
                        $Stock_value = number_format($Stock_value + ($row15["balance_qty"] *  $row15["rate"] ), 2, '.', '');

                         
                    }
                }
               
                $row['Stock_value'] = $Stock_value;
               
               
                $output[] = $row;
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

                         
                        
                         
                        
                        $output1[] = $row15;
                        
                    }
                }
              
        
        echo json_encode($output1);
        
    }
    else if ($_GET["type"] == "getCommonlog") {
        $output = array();
          $sql = "SELECT s.*,m.material_name from stock_book s left join material m on m.material_code = s.material_code 
          where s.plant_id = '".$_GET["plant_id"]."'  AND m.material_type = '".$_GET["Material_type"]."'  order by s.id desc";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
                
                
                 $sql1 = "SELECT  IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no = '".$row["ar_no"]."' AND  material_code= '".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["pRASAD"] = $row1["m_qty"];
                      
                    }
                }
                
                
                 $output1 = array();
                   $sql2 = "SELECT  MAX(i.id) as max_id , i.prod_batch_code, i.product_code,i.material_code,i.ar_no,sum(i.qty) as batch_qty ,m.material_name,p.product_name FROM material_issue i left join material m on i.material_code=m.material_code
                left join product p on p.product_code = i.product_code  WHERE i.ar_no = '".$row["ar_no"]."' AND  i.material_code= '".$row["material_code"]."' 
                GROUP BY i.prod_batch_code, m.material_name, p.product_name ,i.product_code,i.material_code,i.ar_no ORDER BY max_id  DESC";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
                
                 $row["despensing_data"] = $output1;
                
                $row["Issue"] = $row["qty"]- $row["pRASAD"];
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
        else if($_GET['type'] == 'downloadLog') {
       $_GET['filename'] =''; $_GET['pdftype']='landscape'; include("../pdfimp2.php");
       
//   $sql = "SELECT  material_code,material_name,material_type,material_subtype  from  material  where plant_id = '".$_GET["plant_id"]."' 
//           AND material_type = '".$_GET["Material_type"]."'";
     
$sql = "SELECT 
            m.material_code,
            m.material_name,
            m.material_type,
            m.material_subtype,
            IFNULL(SUM(mi.qty), 0) AS dispensing_qty,
            IFNULL(SUM(sb.qty), 0) AS received_qty,
            IFNULL(SUM(sb.undertest_qty), 0) AS undertest_qty,
            IFNULL(SUM(CASE WHEN sb.status = 'Under Test' THEN sb.qty ELSE 0 END), 0) AS totalUnderTestQty,
            IFNULL(SUM(CASE WHEN sb.status = 'Quarantine' THEN sb.qty ELSE 0 END), 0) AS totalQuarantineQty,
            IFNULL(SUM(CASE WHEN sb.status = 'Rejected' THEN sb.qty ELSE 0 END), 0) AS totalRejectedQty,
            IFNULL(SUM(CASE WHEN sb.status = 'Expired' THEN sb.expiredQty ELSE 0 END), 0) AS totalExpiredQty,
            IFNULL(SUM(CASE WHEN sb.status = 'Approved' THEN sb.qty ELSE 0 END), 0) AS totalApprovedQty,
            IFNULL(SUM(sb.qty * sb.rate), 0) AS stock_value  -- Add Stock Value Calculation
        FROM material m
        LEFT JOIN material_issue mi ON m.material_code = mi.material_code
        LEFT JOIN stock_book sb ON m.material_code = sb.material_code
        WHERE m.plant_id = '" . $_GET["plant_id"] . "'
        AND m.material_type = '" . $_GET["Material_type"] . "'
        GROUP BY m.material_code";

    $html = '<table border="2" cellpadding="8">            
                <tr>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Material Code</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Material Name</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Total Qty.</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Quarantine Qty</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Under Test Qty</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Approved Qty</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Dispensed Qty.</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Rejected Qty</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Expired Qty</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Closing Stock</th>
                    <th style="text-align: left; font-weight: bold;background-color: #004a70;color: white;">Stock Value</th>
                </tr>';

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { 
            $html .= '<tr>
                <td style="text-align: left; font-weight: bold;">' . $row['material_code'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['material_name'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['received_qty'] . ' - ' . $row['unit'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['totalQuarantineQty'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['totalUnderTestQty'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['totalApprovedQty'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['dispensing_qty'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['totalRejectedQty'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['totalExpiredQty'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['balance_qty'] . ' - ' . $row['unit'] . '</td>
                <td style="text-align: left; font-weight: bold;">' . $row['Stock_value'] . '</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="11" style="text-align: center; font-weight: bold;">No records found</td></tr>';
    }

    $html .= '</table>'; // Closing table tag after loop

    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('Common Log.pdf', 'I');
}
    
    else if($_GET['type'] == 'getCommonlogPDF') {
       $_GET['filename'] =''; $_GET['pdftype']='onlyheader'; include("../pdfimp2.php");
       
     $sql = "SELECT s.*,m.material_name from stock_book s left join material m on m.material_code = s.material_code 
          where s.plant_id = '".$_GET["plant_id"]."'  AND m.material_type = '".$_GET["Material_type"]."' AND s.ar_no = '".$_GET["arNo"]."' ";
                            
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
       
       
                           $html.= '
           <table>
                            <tr>
                                <td style="background-color:#DDDAD9; width:540px; text-align:center;">Common Log</td>
                            </tr>
                            
                        </table>
                    <div></div>
             <table cellpadding="2">
                            <tr>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Material Name-'.$row['material_name'].'</td>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Material Code-'.$row['material_code'].'</td>
                            </tr>
                            <tr>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Medicap Lot No-'.$row['batch_no'].'</td>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Receiving No-'.$row['grn_no'].'</td>
                            </tr>
                            <tr>
                                <td style="line-height:20px;width: 270px;text-align:left;"> QTY-'.$row['qty'].'Kg</td>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Remaining Stock- '.$_GET['remQty'].'Kg</td>
                            </tr>
                            <tr>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Mfg Date-'.$row['mfg_date'].'</td>
                                <td style="line-height:20px;width: 270px;text-align:left;"> Expiry Date-'.$row['exp_date'].'</td>
                            </tr>
                        </table>
                        <div></div>
                    
                <table cellpadding="2">
                        <tr>
                            <td style="line-height:20px;width: 540px;text-align:center;"> Despencing History</td>
                        </tr>
                       
                        <tr>
                            <td style="line-height:20px;width: 60px;text-align:center;"> Sr</td>
                            <td style="line-height:20px;width: 120px;text-align:center;"> Product Code</td>
                            <td style="line-height:20px;width: 120px;text-align:center;"> Product Name</td>
                            <td style="line-height:20px;width: 120px;text-align:center;"> Production Batch No.</td>
                            <td style="line-height:20px;width: 120px;text-align:center;"> Batch Qty</td>
                        </tr>';
                            $i=1;
             $sql1 = "SELECT  MAX(i.id) as max_id , i.prod_batch_code, i.product_code,i.material_code,i.ar_no,sum(i.qty) as batch_qty ,m.material_name,p.product_name FROM material_issue i left join material m on i.material_code=m.material_code
                left join product p on p.product_code = i.product_code  WHERE i.ar_no = '".$row["ar_no"]."' AND  i.material_code= '".$row["material_code"]."' 
                GROUP BY i.prod_batch_code, m.material_name, p.product_name ,i.product_code,i.material_code,i.ar_no ";
                       $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
           
            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:60px; text-align:center;">'.$i++.'</td>
                                <td style="width:120px; text-align:center;"> '.$row1['product_code'].'</td>
                                <td style="width:120px; text-align:center;">'.$row1['product_name'].'</td>
                                <td style="width:120px; text-align:center;">'.$row1['prod_batch_code'].'</td>
                                <td style="width:120px; text-align:center;">'.$row1['batch_qty'].'</td>
                            </tr>
                            ';
            }
        }
                            $html.='
                        </table>
                        ';
              }
        }
                        
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Common Log.pdf', 'I');
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