<?php

    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);


    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
    
    function get_value($row, $key) {
      return isset($row[$key]) && !empty($row[$key]) ? $row[$key] : 'NA';
}





function getGrdeValue($grade , $conn){
    
            if ($grade == 'NA') {
                $grd = [0]; // Default value as an array containing 0
            } else {
                $grd = $grade;
            }
            
            // Ensure $grd is properly formatted as a comma-separated list
            if (!is_array($grd)) {
                $grd = explode(',', $grd); // Convert to an array if it is a string
            }
            
            // Validate $grd to contain only integers
            $grd = array_filter($grd, function($value) {
                return is_numeric($value) && intval($value) > 0; // Allow only positive integers
            });
            
            // Convert back to a comma-separated string for SQL
            $grdList = implode(',', $grd);
            
            if (!empty($grdList)) {
                // Only execute the query if $grdList is not empty
                $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ($grdList)";
               // echo $q; // Debugging: Display the query
                
                $resQ = $conn->query($q);
                if ($resQ) {
                    $prodLatest = $resQ->fetch_assoc();
                    $gradeName = $prodLatest['gradeName'];
                } else {
                    // Handle SQL query errors
                    echo "SQL Error: " . $conn->error;
                }
            } else {
                // Handle case where $grdList is empty
                $gradeName = "NA"; // Set a default value or handle it appropriately
              //  echo "No valid grades to fetch.";
            }     
            
            
            return $gradeName;
            
            
}



    

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
    
    if ($_GET["type"] == "getVendors") {    
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRejection") {    
        $output = Array();
         $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,
        m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN 
        material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
        WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND material_subtype='".$_GET["material_type"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getClientList") {
        $output = Array();
        $sql = "SELECT * FROM client WHERE status = 'approve' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getClients") {
        $output = Array();
        $sql = "SELECT * FROM client WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUnits") {
        $output = Array();
        $sql  = "SELECT * FROM unit WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveChallan") {
      $input = $_POST;
        $challan_file = "";
            if (isset($_FILES["challan_file"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/challan/".$rand_no.basename($_FILES["challan_file"]["name"]);
                //$file = "../upload/challan/".$rand_no.basename($_FILES["challan_file"]["name"]);
                move_uploaded_file($_FILES["challan_file"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["challan_file"]["name"]);
                //$file = $rand_no.basename($_FILES["challan_file"]["name"]);
                $challan_file = "/upload/challan/".$file;
            } else {
                $flag = 1;
            }
        $materials=$input["materials"];
       // echo $materials;
     
        
       $sql = "UPDATE challan set remark='".$_GET["remark"]."' ,status='".$_GET["status"]."',
       weighing_procedure='".$_GET["weighing_procedure"]."',challan_file = '$challan_file' WHERE id='".$_GET["id"]."'";
     
         if ($conn->query($sql)) {
             
            
           // $materials=$input["materials"];
               $materials =json_decode($input["materials"],true);
                
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO challan_materials (user_no,inward_no,inward_date,challan_no, material_subtype, material_code,qty,challan_qty, unit, rate, gst,gross_total,net_total,vendor_type,vendor_no,manufacturer_no) VALUES('".$_GET["user_no"]."','".$input["inward_no"]."','$entry_date','".$input["challan_no"]."', '".$material["material_subtype"]."','".$material["material_code"]."', '".$material["qty"]."','".$material["challan_qty"]."', '".$material["unit"]."', '".$material["rate"]."', '".$material["gst"]."','".$material["gross_total"]."','".$material["net_total"]."','".$material["vendor_type"]."','".$material["vendor_no"]."','".$material["manufacturer_no"]."')";
                //echo $sql1;
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } 
        else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } else if ($_GET["type"] == "saveDirectChallan") {
        $input = $_POST;
        $id = date("YmdHis", $timestamp);
        $upload_challan = "";
        if(isset($_FILES['upload_challan'])) {
            $file_tmp =$_FILES['upload_challan']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['upload_challan']['name'])));
            $file_name = $id."upload_challan.".$file_ext;
            $upload_challan = $file_name;
            move_uploaded_file($file_tmp,"../upload/challan/".$file_name);
        }
        $materials = json_decode($input["materials"], true);
        for ($i = 0; $i < count($materials); $i++) {
            $material = $materials[$i];
            $material["status"] = "pending";
            $materials[$i] = $material;
        }
        $sql = "INSERT INTO challan (user_no, material_type, challan_no,inward_no,inward_date, challan_date, vendor_no, tax_invoice, transport, entry_by, entry_date,
        gross_total, gst_total, net_total, po_no, po_date, challan_file,remark) VALUES ('".$_GET["user_no"]."', '".$input["material_type"]."','".$input["challan_no"]."',
        '".$input["inward_no"]."','$entry_date','".$input["challan_date"]."', '".$input["vendor_no"]."', '".$input["tax_invoice"]."', '".$input["transport"]."',
        '".$_GET["emp_id"]."', '$entry_date', '".$input["gross_total"]."', '".$input["gst_total"]."', '".$input["net_total"]."', '".$input["po_no"]."',
        '".$input["po_date"]."', '".$upload_challan."' ,'".$input["remark"]."')";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO challan_materials (user_no,inward_date,challan_no, material_type, material_code, qty, unit, rate, gst, required_for, gross_total, gst_total, net_total) VALUES ('".$_GET["user_no"]."','$entry_date','".$input["challan_no"]."', '".$input["material_type"]."', '".$material["material_code"]."', '".$material["qty"]."', 'Nos', '".$material["rate"]."', '".$material["gst"]."', 'Own', '".$material["gross_total"]."','".$material["gst_total"]."', '".$material["net_total"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "getPendingChallans") {
        $output = Array();
       
           
         $sql = "SELECT a.*, b.vendor_name, b.gst_no, p.id AS purId FROM challan a LEFT JOIN vendor b ON a.vendor_no = b.vendor_no LEFT JOIN purchaseorder p ON a.po_no = p.po_no
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Pending' AND ( a.material_type = 'Raw Material' OR a.material_type = 'Packing Material' ) ORDER BY a.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

    		    $output1 = Array();
    		    $isOpen = false;
    		    
        	   	$sql1 = "SELECT p.*,p.id as purMatId, p.quotation_amt as rate, p.tax_total as gst_total, m.id, m.material_name, m.grade,
        	   	m.material_subtype, m.material_type FROM po_material p left join my_view m on p.material_code = m.material_code WHERE 
        	   	p.po_no='".$row["purId"]."' AND p.isMatIn = 'NO'  ";
        	   	
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         
                        if($row1['openQty'] != 'NO'){
                            $isOpen = true;
                        }
                        $output1[] = $row1;
                    }
                }
                 
                    if($isOpen){
                        $row['isOpenPo'] = 'OPEN';
                    }else{
                        $row['isOpenPo'] = 'NA';
                    }
                
                    $row["materials"] = $output1;
                    $output[] = $row;
            }
        } 
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingChallansGeneralMaterial") {
        $output = Array();
       
           
         $sql = "SELECT a.*, b.vendor_name, b.gst_no, p.id AS purId FROM challan a LEFT JOIN vendor b ON a.vendor_no = b.vendor_no LEFT JOIN purchaseorder p ON a.po_no = p.po_no
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Pending' AND  a.material_type != 'Raw Material' AND a.material_type != 'Packing Material'  ORDER BY a.id DESC";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

    		    $output1 = Array();
    		    $isOpen = false;
    		    
        	   	$sql1 = "SELECT p.*,p.id as purMatId, p.quotation_amt as rate, p.tax_total as gst_total, m.id, m.material_name, m.grade,
        	   	m.material_subtype, m.material_type FROM po_material p left join my_view m on p.material_code = m.material_code WHERE 
        	   	p.po_no='".$row["purId"]."' AND p.isMatIn = 'NO'  ";
        	   	
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         
                        if($row1['openQty'] != 'NO'){
                            $isOpen = true;
                        }
                        $output1[] = $row1;
                    }
                }
                 
                    if($isOpen){
                        $row['isOpenPo'] = 'OPEN';
                    }else{
                        $row['isOpenPo'] = 'NA';
                    }
                
                    $row["materials"] = $output1;
                    $output[] = $row;
            }
        } 
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApproveChallans") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status NOT IN ('pending', 'HOLD' , 'Rejected') order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getProduct") {
        $output = Array();
         $sql = "SELECT id,product_code,product_name,grade from product where plant_id= '".$_GET["plant_id"]."' order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getDocumengtsByProduct") {
        $output = Array();
           $sql = "SELECT m.product_code, MAX(p.product_name) AS product_name FROM master_documents m left join product p ON m.product_code = p.product_code 
         where m.plant_id= '".$_GET["plant_id"]."' group by m.product_code";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
        $output1 = Array();
        $sql1 = "SELECT m.*,p.product_name FROM master_documents m left join product p ON m.product_code = p.product_code 
         where m.product_code= '".$row["product_code"]."'  order by m.id desc";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
                
                
                $row['doc_data'] = $output1;
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getReceivedChallans") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.next_stage = 'Checking' AND ( c.material_type = 'Raw Material' OR c.material_type = 'Packing Material' ) order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                 $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                                
                $row["documentsChecklist"] = json_decode($row["documentsChecklist"]);
                $row["vehicleChecklist"] = json_decode($row["vehicleChecklist"]);
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getReceivedChallansGeneralMaterials") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.next_stage = 'Checking' AND  c.material_type != 'Raw Material' AND c.material_type != 'Packing Material'   order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                 $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                                
                $row["documentsChecklist"] = json_decode($row["documentsChecklist"]);
                $row["vehicleChecklist"] = json_decode($row["vehicleChecklist"]);
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllApproveChallans") {
        $output = Array();
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='approve' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		   $row = array_map('utf8_encode', $row);
                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $row1["received_rate"] = $row1["rate"];
                        $row1["diff"] = 0;
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingChallansLocal") {
        $output = Array();
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no 
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='pending' 
        AND c.type='Local' AND inward_type != 'Returnable Outwards' AND (c.material_type='Raw Material' OR c.material_type='Packing Material') 
        ORDER BY c.id DESC ";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $output[] = $row;
            }
        }
        echo json_encode($output);
     
    } 
    else if ($_GET["type"] == "getChallanForHrAppp") {
        $output = Array();
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date FROM challan c WHERE c.status='pending' 
        AND c.type='Local' AND inward_type != 'Returnable Outwards' AND c.plant_id='".$_GET["plant_id"]."' ORDER BY c.id DESC ";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $output[] = $row;
            }
        }
        echo json_encode($output);
     
    } 
    else if ($_GET["type"] == "approve_challan") {
         $next_step='Pending';
         if($_GET["status"]=='approve'){
             $next_step='Verification';
         }
          $sql = "UPDATE challan SET status='".$_GET["status"]."',next_stage='".$next_step."' WHERE id='".$_GET["id"]."'";
        //  echo $sql;
        if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
     }
     
     
    else if ($_GET["type"] == "verifyChallan") {
        
         $next_step = 'Checking';
        
         if($_GET["status"] == 'approve'){
             $next_step='Receiving';
         }
         
        $sql = "UPDATE challan SET status = '".$_GET["status"]."', next_stage = '".$next_step."', approve_by = '".$_GET["emp_id"]."' , approve_date = '$entry_date' WHERE id = '".$_GET["id"]."'";
    
        if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    }
    else if ($_GET["type"] == "updateChallanFromHold") {
 
         
        $sql = "UPDATE challan SET status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."'";
    
        if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        
    }
     
    else if ($_GET["type"] == "CycloneUpdateChallan") {
        
      
            $input  = $_POST;
            $chid= $_GET["id"];
            $pId= $_GET["plant_id"];
            $nsc= $_GET["notSelectedCount"];
             
        if (isset($_FILES["challan_file"])) {
            $file_tmp = $_FILES['challan_file']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['challan_file']['name'])));
            $challan_file = $pId.$chid.$nsc.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/challan/" . $challan_file);
        }else{
            $challan_file = 'NA';
        }
        
        $sql = "UPDATE challan SET next_stage='Checking', status='".$_GET["status"]."',challan_file = '$challan_file', 
        approve_by='".$_GET["emp_id"]."',  approve_date='$entry_date', gross_total='".$input["gross_total"]."', gst_total='".$input["gst_total"]."', 
        net_total='".$input["net_total"]."',e_way='".$input["e_way"]."',eway_bill_no='".$input["eway_bill_no"]."', weighing_procedure='".$_GET["weighing_procedure"]."' WHERE id='".$_GET["id"]."'";
     
     
        if ($conn->query($sql)) {
            
            
              $materials =json_decode($input["materials"],true);
              $invoice_data =json_decode($input["invoice_data"],true);
             
            if($invoice_data!=''){
                
                for ($i = 0; $i < count($invoice_data); $i++) {
                    $material = $invoice_data[$i];
            
                   $sql4="INSERT INTO invoice_entry( invoice_no, invoice_date, invoice_amt, tax_amt, net_amt,po_no,plant_id) VALUES 
                  ('".$material["tax_invoice"]."','".$material["tax_invoice_date"]."','".$material["invoice_amt"]."','".$material["tax_amt"]."','".$material["net_amt"]."',
                  '".$_GET["po_no"]."','".$_GET["plant_id"]."')";
                  
                 $conn->query($sql4);
                 
                 
                }
            }
             
            $inward_no = '';
            $sql = "SELECT inward_no FROM challan WHERE id='".$_GET["id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $inward_no = $row["inward_no"];
                }
            }
             
            // if($materials!=''){
                
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    
                    if($material['openQty'] != "NO"){
                
                
                $sql1 = "INSERT INTO challan_materials (plant_id,user_no, inward_no,inward_date, challan_no,ch_no, material_subtype,
                material_code, qty,unit,gst,quotation_no,quotation_amt, rate, required_for, client_code,disc_per,disc_amt,gross_total, gst_total,
                net_total,weight,tax_invoice,received_rate,diff,isOPenPO,materialFor,materialForName)VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '$inward_no','$entry_date',
                '".$_GET["challan_no"]."','".$_GET["ch_no"]."','".$material["material_subtype"]."', '".$material["material_code"]."',
                '".$material["openQty"]."', '".$material["unit"]."', '".$material["gst"]."','".$material["quotation_no"]."',
                '".$material["quotation_amt"]."','".$material["quotation_amt"]."', '".$material["required_for"]."',
                '".$material["client_code"]."','0','0',
                '0','0','".$material["tax_invoice"]."','".$material["received_rate"]."',
                '".$material["diff"]."','YES','".$input["materialFor"]."','".$input["materialForName"]."')";
                        
                    }else{
                        
                    $sql1 = "INSERT INTO challan_materials (plant_id,user_no, inward_no,inward_date, challan_no,ch_no, material_subtype,
                    material_code, qty,unit,gst,quotation_no,quotation_amt, rate, required_for, client_code, gross_total, gst_total,
                    net_total,weight,tax_invoice,received_rate,diff,materialFor,materialForName)VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '$inward_no','$entry_date',
                    '".$_GET["challan_no"]."','".$_GET["ch_no"]."','".$material["material_subtype"]."', '".$material["material_code"]."',
                    '".$material["qty"]."', '".$material["unit"]."', '".$material["gst"]."','".$material["quotation_no"]."',
                    '".$material["quotation_amt"]."','".$material["quotation_amt"]."', '".$material["required_for"]."',
                    '".$material["client_code"]."','".$material["disc_per"]."','".$material["disc_amt"]."','".$material["gross_total"]."','".$material["gst_total"]."',
                    '".$material["net_total"]."','0','".$material["tax_invoice"]."','".$material["received_rate"]."',
                    '".$material["diff"]."','".$input["materialFor"]."','".$input["materialForName"]."')";
                        
                    }
                     

                
                

                
                    if ($conn->query($sql1)) {
                             $sql10 = "UPDATE  po_material SET isMatIn = 'YES' WHERE id='".$material["purMatId"]."'";
                            $conn->query($sql10);
                    }
                
                }
            // }
            
            
            if($_GET["plant_id"] == '149'){
                    if (!empty($challan_file)) {
                        $uploadChallanSQL = "INSERT INTO upload_challan (challan_id, file_name, upload_date, uploaded_by) 
                                             VALUES ('".$_GET["id"]."', '".$challan_file."', '".$entry_date."', '".$_GET["emp_id"]."')";
                        $conn->query($uploadChallanSQL);
                    }
                
            }
            
              

            
          
             
            if($_GET["status"] == "reject") {
                 $sql = "UPDATE purchaseorder SET is_security_receive='No' WHERE po_no='".$_GET["po_no"]."'";
                 $conn->query($sql);
            }else if($_GET["notSelectedCount"] > 0){
                 $sql = "UPDATE purchaseorder SET is_security_receive='No' WHERE po_no='".$_GET["po_no"]."'";
                 $conn->query($sql);
            } 
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "updateChallan") {
          
      
        $input  = $_POST;
  
        $sql = "UPDATE challan SET next_stage='Checking', status = '".$_GET["status"]."',  approve_by='".$_GET["emp_id"]."',  approve_date='$entry_date',  vehicleChecklist = '".$input["vehicleChecklist"]."',
        e_way = '".$input["e_way"]."', eway_bill_no = '".$input["eway_bill_no"]."', weighing_procedure = '".$input["weighing_procedure"]."', documentsChecklist = '".$input["documentsChecklist"]."' WHERE id='".$_GET["id"]."'";
     
     
        if ($conn->query($sql)) {
            
            
            $materials =json_decode($input["materials"],true);
              
            $inward_no = '';
            $sql = "SELECT inward_no FROM challan WHERE id='".$_GET["id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $inward_no = $row["inward_no"];
                }
            }
             
             $gross_total = 0;
             $taxable_total = 0;
             $gst_total = 0;
             $net_total = 0;
             $sgst_total = 0;
             $cgst_total = 0;
             $igst_total = 0;
     
                
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                
                
                if($material['openQty'] != "NO"){
               
            
                $sql1 = "INSERT INTO `challan_materials`(`plant_id`,`inward_no`, `inward_date`, `challan_no`, `ch_no`, `material_subtype`, `material_code`, `vendor_no`, `qty`, `unit`, `gst`, `quotation_no`, 
                `quotation_amt`, `rate`, `received_rate`, `diff`, `tax_invoice`, `tax_invoice_date`, `required_for`, `clientGrpCode`, `clientSubGrpCode`,`gross_total`, `taxable_amt`, `gst_total`, `net_total`, 
                `cgst`, `cgstPer`, `sgst`, `sgstPer`, `igst`, `igstPer`, `isOPenPO`,`coaReceived`) VALUES ('".$_GET["plant_id"]."','$inward_no','$entry_date','".$input["challan_no"]."','".$input["ch_no"]."',
                '".$material["material_subtype"]."', '".$material["material_code"]."','".$material["vendor_no"]."','".$material["openQty"]."', '".$material["unit"]."', '".$material["gst"]."',
                '".$material["quotation_no"]."', '".$material["quotation_amt"]."','".$material["quotation_amt"]."', '".$material["received_rate"]."', '".$material["diff"]."','".$material["tax_invoice"]."',
                '".$material["tax_invoice_date"]."','".$material["required_for"]."','".$material["clientGrpCode"]."','".$material["clientSubGrpCode"]."','".$material["gross_total"]."','".$material["taxable_amt"]."','".$material["gst_total"]."',
                '".$material["net_total"]."','".$material["cgst"]."','".$material["cgstPer"]."','".$material["sgst"]."' ,'".$material["sgstPer"]."','".$material["igst"]."','".$material["igstPer"]."','YES','".$material["coaReceived"]."')";
                      
                }else{
                    
                $sql1 = "INSERT INTO `challan_materials`(`plant_id`,`inward_no`, `inward_date`, `challan_no`, `ch_no`, `material_subtype`, `material_code`, `vendor_no`, `qty`, `unit`, `gst`, `quotation_no`, 
                `quotation_amt`, `rate`, `received_rate`, `diff`, `tax_invoice`, `tax_invoice_date`, `required_for`, `clientGrpCode`, `clientSubGrpCode`, `gross_total`, `taxable_amt`, `gst_total`, `net_total`, 
                `cgst`, `cgstPer`, `sgst`, `sgstPer`, `igst`, `igstPer`, `isOPenPO`,`coaReceived`) VALUES ('".$_GET["plant_id"]."',  '$inward_no','$entry_date','".$input["challan_no"]."','".$input["ch_no"]."',
                '".$material["material_subtype"]."', '".$material["material_code"]."','".$material["vendor_no"]."','".$material["qty"]."', '".$material["unit"]."', '".$material["gst"]."',
                '".$material["quotation_no"]."', '".$material["quotation_amt"]."','".$material["quotation_amt"]."', '".$material["received_rate"]."', '".$material["diff"]."','".$material["tax_invoice"]."',
                '".$material["tax_invoice_date"]."','".$material["required_for"]."','".$material["clientGrpCode"]."','".$material["clientSubGrpCode"]."','".$material["gross_total"]."','".$material["taxable_amt"]."','".$material["gst_total"]."',
                '".$material["net_total"]."','".$material["cgst"]."','".$material["cgstPer"]."','".$material["sgst"]."' ,'".$material["sgstPer"]."','".$material["igst"]."','".$material["igstPer"]."','NO','".$material["coaReceived"]."')";
                    
                }
                
                $gross_total  += (float) ($material['gross_total']  ?? 0);
                $taxable_total += (float) ($material['taxable_amt'] ?? 0);
                $gst_total    += (float) ($material['gst_total']    ?? 0);
                $net_total    += (float) ($material['net_total']    ?? 0);
                $sgst_total   += (float) ($material['sgst']         ?? 0);
                $cgst_total   += (float) ($material['cgst']         ?? 0);
                $igst_total   += (float) ($material['igst']         ?? 0);


            
                if ($conn->query($sql1)) {
                    $sql10 = "UPDATE  po_material SET isMatIn = 'YES' WHERE id='".$material["purMatId"]."'";
                    $conn->query($sql10);
                } 
            
            }
     
        
             
            if($_GET["status"] == "reject") {
                 $sql = "UPDATE purchaseorder SET is_security_receive='No' WHERE po_no='".$input["po_no"]."'";
                 $conn->query($sql);
            }else if($input["notSelectedCount"] > 0){
                 $sql = "UPDATE purchaseorder SET is_security_receive='No' WHERE po_no='".$input["po_no"]."'";
                 $conn->query($sql);
            } 
            
            
            
            $sql001 = "UPDATE challan SET gross_total = '$gross_total', taxable_total = '$taxable_total', gst_total = '$gst_total', 
            net_total = '$net_total', sgst_total = '$sgst_total', cgst_total = '$cgst_total', igst_total = '$igst_total'  WHERE id='".$_GET["id"]."'";
            $conn->query($sql001);
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
     else if ($_GET["type"] == "getUploadedChallans") {
         
        $output = array();
        
        $sql = "SELECT * FROM UploadChallan WHERE ch_no = '" .$_GET["ch_no"]. "' AND po_no = '" .$_GET["po_no"]. "' AND vendor_no = '" .$_GET["vendor_no"]. "' AND plant_id = '" .$_GET["plant_id"]. "' ";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row; 
            }
        }
   
        echo json_encode($output);
    }
 

    else if ($_GET["type"] == "uploadChallan") {
        
        $input = $_POST;
 
        $ch = $input["ch_no"];
        $po_no = $input["po_no"];
        $pid = $_GET["plant_id"];
        $rand =  mt_rand(1000, 9999);   
         
        $ch = str_replace(['/', '\\'], '-', $ch);
        $po_no = str_replace(['/', '\\'], '-', $po_no);
        
        $str = $ch.$po_no.$rand.$pid;
        $ChallanFile = 'NA';
        
        if (isset($_FILES["docFIle"])) {
            $file_tmp = $_FILES['docFIle']['tmp_name'];
            $file_name = $_FILES['docFIle']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $ChallanFile = $str.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/challan/" . $ChallanFile);
        }else{
            $ChallanFile = 'NA';
        }
         
        $sql = "INSERT INTO `UploadChallan`(`plant_id`, `docName`, `docNo`, `file`, `ch_no`, `po_no`, `vendor_no`, `entryBy`, `entryOn`) VALUES 
        ('".$_GET["plant_id"]."', '".$input["docName"]."', '".$input["docNo"]."', '$ChallanFile',  '".$input["ch_no"]."', '".$input["po_no"]."', '".$input["vendor_no"]."', '".$_GET["emp_id"]."', '$entry_date' )";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "deluploadChallan") {
        
        $sql = "DELETE FROM `UploadChallan` where id = '".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
     else if ($_GET["type"] == "saveDocuments") {
 
     $target_dir = "../../../upload/masterDocuments/";
            
            $input  = $_POST;
            $plant_id = $_GET["plant_id"];
            $productCode = $input["product_code"];
            $doc_name = $input["doc_name"];
            $doc_no = $input["doc_no"];
             
      $docFile = 'Pending';
        
           if(isset($_FILES["document"]["name"])) {
            	$target_file = $target_dir.$plant_id.$doc_no.$doc_name.$productCode."_".basename($_FILES["document"]["name"]);
            	$docFile = $plant_id.$doc_no.$doc_name.$productCode."_".basename($_FILES["document"]["name"]);
           }
        
        
    $sql = "insert into master_documents (plant_id,product_code,doc_type,doc_name,doc_no,ver_no,valid_till,requirement,document,entry_by,entry_date) 
    VALUES ('".$_GET["plant_id"]."','".$input["product_code"]."','".$input["doc_type"]."','".$input["doc_name"]."',
    '".$input["doc_no"]."','".$input["ver_no"]."','".$input["valid_till"]."','".$input["requirement"]."','$docFile','".$_GET["emp_id"]."','$entry_date')";
    
        if ($conn->query($sql)) {
             move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
             
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "local_purchase") { 
        $_POST=$input;
           
       echo  $sql = "UPDATE challan SET  vendor_no = '".$input["vendor_no"]."', gross_total = '".$input["grossTotal"]."', gst_total = '".$input["taxTotal"]."',
        net_total = '".$input["netTotal"]."', status = 'waiting_for_checking',type = 'Direct PO' ,is_tanker = 'NO',next_stage = 'Checking',
        weighing_procedure = '".$input["weighing_procedure"]."' WHERE id = '".$input["id"]."'"; 
        
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
             
                $materials =  $input["materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    echo('dsfsdfsdfsdfsdf');
                    $material = $materials[$i];
                    
                     $sql1 = "INSERT INTO challan_materials (plant_id,user_no, inward_no,inward_date, challan_no,ch_no, material_subtype, material_code,
                    qty,unit,gst,quotation_no,quotation_amt, rate, required_for, client_code, gross_total, gst_total,net_total,weight,tax_invoice,
                    received_rate,diff,vendor_no)VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '".$input["inward_no"]."','$entry_date',
                    '".$input["challan_no"]."','".$input["ch_no"]."','".$material["material_type"]."', '".$material["material_code"]."',
                    '".$material["challan_qty"]."', '".$material["unit"]."', '".$material["gst"]."','".$material["quotation_no"]."','".$material["rate"]."',
                    '".$material["rate"]."', '".$material["required_for"]."','NA','".$material["gross_total"]."','".$material["tax_total"]."',
                    '".$material["net_total"]."','0','".$input["tax_invoice"]."','".$material["rate"]."','0','".$input["vendor_no"]."')";
                        
                    $conn->query($sql1);
  
                }
      
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
           
    }
    
    else if ($_GET["type"] == "updateRejectedPO") {
    $sql = "UPDATE challan SET challan_no='".$input["challan_no"]."', challan_date='".$input["challan_date"]."', tax_invoice='".$input["tax_invoice"]."', vehicle_no='".$input["vehicle_no"]."', driver_name='".$input["driver_name"]."', driver_contact='".$input["driver_contact"]."', transport='".$input["transport"]."', transport_company='".$input["transport_company"]."', status='pending' WHERE id='".$_GET["id"]."' AND po_no='".$_GET["po_no"]."'";
    if ($conn->query($sql)) {
        $materials = json_encode($input["materials"], true); 
        
        // Convert JSON string to PHP array
        if ($materials ) { // Check if $materials is not null
            foreach ($materials as $material) {
                $sql1 = "INSERT INTO challan_materials (user_no, inward_no, inward_date, challan_no, material_subtype, material_code, qty, challan_qty, unit, rate, gst, gross_total, net_total, vendor_type, vendor_no, manufacturer_no) VALUES ('".$_GET["user_no"]."', '".$input["inward_no"]."', '$entry_date', '".$input["challan_no"]."', '".$material["material_subtype"]."', '".$material["material_code"]."', '".$material["qty"]."', '".$material["challan_qty"]."', '".$material["unit"]."', '".$material["rate"]."', '".$material["gst"]."', '".$material["gross_total"]."', '".$material["net_total"]."', '".$material["vendor_type"]."', '".$material["vendor_no"]."', '".$material["manufacturer_no"]."')";
                if ($conn->query($sql1)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"error\"}";
                }
            }
            // Update status here
            $sql_update_status = "UPDATE challan SET status='pending' WHERE id='".$_GET["id"]."' AND po_no='".$_GET["po_no"]."'";
            if ($conn->query($sql_update_status)) {
                echo "{\"status\":\"status updated\"}";
            } else {
                echo "{\"status\":\"error updating status\"}";
            }
        } else {
            echo "{\"status\":\"error\", \"message\":\"Materials array is null or empty\"}";
        }
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}

    else if ($_GET["type"] == "getAllChallansLog") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status NOT IN ('pending', 'HOLD' , 'WAITING_FOR_CHECKING') order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getChallansLog") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status NOT IN ('pending', 'HOLD' , 'waiting_for_checking') AND ( c.material_type = 'Raw Material' OR  c.material_type = 'Packing Material' ) order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["documentsChecklist"] = json_decode($row["documentsChecklist"]);
                $row["vehicleChecklist"] = json_decode($row["vehicleChecklist"]);
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getChallansLogGeneralMaterials") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status NOT IN ('pending', 'HOLD' , 'WAITING_FOR_CHECKING') AND  c.material_type != 'Raw Material' AND c.material_type != 'Packing Material' order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["documentsChecklist"] = json_decode($row["documentsChecklist"]);
                $row["vehicleChecklist"] = json_decode($row["vehicleChecklist"]);
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getHoldChallan") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status = 'HOLD' AND  ( c.material_type = 'Raw Material' OR c.material_type = 'Packing Material' ) order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getHoldChallanGeneralMaterial") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status = 'HOLD' AND  c.material_type != 'Raw Material' AND c.material_type != 'Packing Material' order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllHoldChallan") {
        $output = Array();
 
        $sql = "SELECT  c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no
        FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        WHERE c.plant_id = '".$_GET["plant_id"]."' AND c.status = 'HOLD' order by id desc"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $output1 = Array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code  
                WHERE c.challan_no ='".$row["challan_no"]."' AND c.plant_id = '".$_GET["plant_id"]."' "; // and c.status='pending'
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRejectedChallans") {
        $output = Array();
          $sql = "SELECT c.*,cm.challan_no,cm.material_code,m.material_name,cm.container_condition,cm.storage_condition,
        cm.storage_condition,cm.vehicle_cleanliness,cm.tanker_cleaning,cm.challan_qty,cm.received_qty,cm.containers,cm.damage,v.vendor_name
        FROM challan c JOIN challan_materials cm ON c.challan_no = cm.challan_no  left join vendor v on v.vendor_no=c.vendor_no LEFT JOIN material m on cm.material_code = m.material_code 
        WHERE c.user_no='".$_GET["user_no"]."' AND c.status='reject'";
       // $sql = "SELECT * FROM challan WHERE user_no='".$_GET["user_no"]."' AND status='reject'";
               $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                 $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
                // $output2 = array();
                // $sql1 = "SELECT * FROM challan_materials WHERE challan_no = '".$row["challan_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //          $output2[] = $row1;
                //     }
                // }
        
                $row["batches"] = $output1;
                $row["receiving_details"]= $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);

       // ***************old code
       
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
                
        //         $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
    		  //  $result1 = $conn->query($sql1);
    		  //  if ($result1->num_rows > 0) {
    		  //      while ($row1 = $result1->fetch_assoc()) {
    		  //          $row["vendor_name"] = $row1["vendor_name"];
    		  //          $row["email"] = $row1["email"];
    		  //          $row["gst_no"] = $row1["gst_no"];
    		  //      }
    		  //  }
    		    
        //         $output1 = Array();
        //         $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        //                 $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
    		  //          $result2 = $conn->query($sql2);
    		  //          if ($result2->num_rows > 0) {
    		  //              while ($row2 = $result2->fetch_assoc()) {
    		  //                  $row1["material_type"] = $row2["material_type"];
    		  //                  $row1["material_subtype"] = $row2["material_subtype"];
    		  //                  $row1["material_name"] = $row2["material_name"];
    		  //                  $row1["grade"] = $row2["grade"];
    		  //              }
    		  //          }
        //                 $output1[] = $row1;
        //             }
        //         }
        //         $row["materials"] = $output1;
        //         $output[] = $row;
        //     }
        // }
        // echo json_encode($output);
    }
    
    else if ($_GET["type"] == "edits") {
                $up="UP-";
        $sql = "Select id  from edits where  plant_id = '".$_GET["plant_id"]."'  order by id desc  limit 0 , 1"; 
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $up_id = $up."0". ++$row['id']; 
        
         $sql = "INSERT INTO edits ( plant_id, receiving_edit,update_id) 
        VALUES ('".$_GET["plant_id"]."', 
    '".json_encode($input["result"])."','".$up_id."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if ($_GET["type"] == "Non_registration") {
        
        
              $last_id=0;
            $sql = "Select count(*)+1 as count from Non_registration where   plant_id = '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()){
                  $last_id = $row['count'];
            }
            if($last_id==0){
                $last_id=1;
            }
            
            $length = 4;
            $number = substr(str_repeat(0, $length).$last_id, - $length);
        
    
                $vendor_no = "V-00".$last_id;
            
        
        
        $sql = "INSERT INTO Non_registration ( plant_id,vendor_no,reg_vendor,reg_pan,reg_gst,reg_address  ) 
        VALUES ('".$_GET["plant_id"]."','$vendor_no','".$input["reg_vendor"]."','".$input["reg_pan"]."','".$input["reg_gst"]."','".$input["reg_address"]."')";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "GET_Non_registration") {
        $output = Array();
        $sql = "SELECT * FROM Non_registration ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getReturnableList") {
        $output = Array();
        // $sql = "SELECT * FROM challan WHERE challan_no LIKE '%".$_GET["challan_no"]."%' AND po_no LIKE '%".$_GET["po_no"]."%' AND vendor_no LIKE '%".$_GET["vendor_no"]."%'";
        $sql = "SELECT entry_date,material_type, transport, po_date, po_no, tax_invoice, challan_date ,challan_no FROM challan WHERE inward_type='Returnable Outwards' ";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                // $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $row["vendor_name"] = $row1["vendor_name"];
                //         $row["email"] = $row1["email"];
                //         $row["gst_no"] = $row1["gst_no"];
                //     }
                // }
                
                $output1 = Array();

                $sql1 = "";
                if ($row["material_type"] == "Raw Material" || $row["material_type"] == "Packing Material") {
                    
                     $sql1 = "SELECT p.qty,p.unit, m.material_name,m.material_code, m.grade, m.material_subtype, m.material_type FROM challan_materials p LEFT JOIN material m 
                ON p.material_code=m.material_code WHERE p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                
                
                
               
                } else if ($row["material_type"] == "General Material") {
                    
                    $sql1 = "SELECT p.*, m.material_type, m.material_name FROM challan_materials p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
               
                } else if ($row["material_type"] == "Chemicals") {
                    
                    $sql1 = "SELECT p.*, m.chemical_name, m.molecular_wt, m.grade, p.material_code as chemical_no FROM challan_materials p LEFT JOIN chemical m ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
               
                } else if ($row["material_type"] == "Glasswares") {
                    
                    $sql1 = "SELECT p.*, m.name, m.capacity, m.unit, m.glassware_class, m.description, p.material_code as glassware_no FROM challan_materials p LEFT JOIN glassware m ON p.material_code=m.glassware_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                
                    
                }
                if ($sql1 !== "") {
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    
 else if ($_GET["type"] == "downloadChallanLog") {
        $_GET['filename'] = 'On Hold'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">On Hold</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;  text-align:center;">Sr</td>
                    <td style="width: 8%; text-align:center;">Inward No</td>
                    <td style="width: 8%;   text-align:center;">Inward Date</td>
                    <td style="width: 8%; text-align:center;">Material Type</td>
                    <td style="width: 7%; text-align:center;"> Vendor Name</td>
                    <td style="width: 7%; text-align:center;">Vendor Location</td>
                    <td style="width: 10%; text-align:center;">Challan No.</td>
                    <td style="width: 8%; text-align:center;">Challan Date</td>
                    <td style="width: 5%;  text-align:center;">PO No.</td>
                    <td style="width: 8%; text-align:center;">PO Date</td>
                    <td style="width: 8%; text-align:center;">Invoice No.</td>
                    <td style="width: 10%; text-align:center;">Prepared Date</td>
                    <td style="width: 8%; text-align:center;">Prepared By</td>
                </tr>
            </thead>';
            $i=1;
           // $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='hold' GROUP BY c.id ORDER BY c.id DESC ";
           
           $sql = "SELECT c.id, MAX(DATE(c.entry_date)) AS entry_date, MAX(v.vendor_name) AS vendor_name, MAX(v.email) AS email, MAX(v.gst_no) AS gst_no
                    FROM challan c
                    LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
                    WHERE c.status = 'hold'
                    GROUP BY c.id
                    ORDER BY c.id DESC;
                ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='
                <tbody>
                    <tr>
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 8%;">'.$row['inward_no'].'</td>
                        <td style="width: 8%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                        <td style="width: 8%;">'.$row['material_type'].'</td>
                        <td style="width: 7%;">'.$row['vendor_name'].'</td>
                        <td style="width: 7%;">'.$row['vendor_location'].'</td>
                        <td style="width: 10%;">'.$row['challan_no'].'</td>
                        <td style="width: 8%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                        <td style="width: 5%;">'.$row['po_no'].'</td>
                        <td style="width: 8%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                        <td style="width: 8%;">'.$row['tax_invoice'].'</td>
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 8%;">'.$row['entry_by'].'</td>
                    </tr>
                </tbody>';
                $i++;
            }
        }
            $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
 }
 else if ($_GET["type"] == "DownloadInwordPDF") {
        $_GET['filename'] = 'On Hold'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">MATERIAL INCOMING REGISTER FOR PACKAGING MATERIALS</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="text-align:center;font-size:6px;">Inward Sr. No..</td>
            <td style="text-align:center;font-size:6px;">Challan No./Date</td>
            <td style="text-align:center;font-size:6px;">Vehicle No./LR No.</td>
            <td style="text-align:center;font-size:6px;">Name of Transporter</td>
            <td style="text-align:center;font-size:6px;">Inward Time</td>
            <td style="text-align:center;font-size:6px;">Name of the Supplier</td>
            <td style="text-align:center;font-size:6px;">Material Name</td>
            <td style="text-align:center;font-size:6px;">Qty. & Pack</td>
            <td style="text-align:center;font-size:6px;">Sign</td>
            <td style="text-align:center;font-size:6px;">Out Time/Sign</td>
            <td style="text-align:center;font-size:6px;">Remarks</td>

           
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT c.*, DATE(c.entry_date)  AS entry_date, v.vendor_name, v.email, v.gst_no, cm.* FROM challan c LEFT JOIN vendor v ON c.vendor_no = v.vendor_no LEFT JOIN challan_materials cm ON c.challan_no = cm.challan_no WHERE c.status = 'approve'";
           // $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='hold' GROUP BY c.id ORDER BY c.id DESC ";
            // $sql = "SELECT * FROM challan_materials";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='
                <tbody>
                <tr>
                 <td >'.$i.'.</td>
                 <td>'.$row['challan_no'].'</td>
                 <td >'.get_value($row, 'vehicle_no').'</td>
                 <td >'.get_value($row, 'transporter_name').'</td>
                 <td>'.$row['inward_date'].'</td>
                 <td >'.$row['vendor_name'].'</td>
                 <td>'.$row['material_name'].'</td>
                 <td >'.get_value($row, 'qty').'</td>
                 <td >'.get_value($row, 'entry_by').'</td>
                 <td ></td>
                 <td > '.get_value($row, 'remark').'</td>


                </tr>
                </tbody>';
                $i++;
            }
        }
            $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
 }
 
  else if ($_GET["type"] == "DownloadInwordPDF1") {
        $_GET['filename'] = 'On Hold'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">MATERIAL INCOMING REGISTER FOR PACKAGING MATERIALS</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5.88%; text-align: center;">Sr</td>
                    <td style="width: 5.88%; text-align: center;">Inward Date</td>
                    <td style="width: 5.88%; text-align: center;">GRN No.</td>
                    <td style="width: 5.88%; text-align: center;">Item Code</td>
                    <td style="width: 5.88%; text-align: center;">Material Name</td>
                     <td style="width: 5.88%; text-align: center;">Ch. No</td>
                         <td style="width: 5.88%; text-align: center;">Ch. Date</td>
                    <td style="width: 5.88%; text-align: center;">Name of Supplier</td>
                    <td style="width: 5.88%; text-align: center;">Name of Manufacturer</td>
                    <td style="width: 5.88%; text-align: center;">C. Ex Invoice no. & Date</td>
                    <td style="width: 5.88%; text-align: center;">Pack Size</td>
                    <td style="width: 5.88%; text-align: center;">Total Qty</td>
                    <td style="width: 5.88%; text-align: center;">Sing/Date</td>
                    <td style="width: 5.88%; text-align: center;">Status/A.R No/Date</td>
                    <td style="width: 5.88%; text-align: center;">Ratest Date</td>
                    <td style="width: 5.88%; text-align: center;">Sign/Date</td>
                    <td style="width: 5.88%; text-align: center;">Remarks</td>
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT c.*, DATE(c.entry_date) AS entry_date, v.vendor_name, v.email, v.gst_no, cm.* FROM challan c LEFT JOIN vendor v ON c.vendor_no = v.vendor_no LEFT JOIN challan_materials cm ON c.challan_no = cm.challan_no WHERE c.status = 'approve'";
           // $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='hold' GROUP BY c.id ORDER BY c.id DESC ";
            // $sql = "SELECT * FROM challan_materials";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='
                <tbody>
                    <tr>
                        <td style="width: 5.88%;">'.$i.'.</td>
                        <td style="width: 5.88%;">'.$row['inward_date'].'</td>
                        <td style="width: 5.88%;">'.$row['grn_no'].'</td>
                        <td style="width: 5.88%;">'.$row['material_code'].'</td>
                        <td style="width: 5.88%;">'.$row['material_name'].'</td>
                        <td style="width: 5.88%;">'.$row['challan_no'].'</td>
                        <td style="width: 5.88%;">'.$row['challan_date'].'</td>
                        <td style="width: 5.88%;">'.$row['vendor_name'].'</td>
                   
                        <td style="width: 5.88%;"> </td>
                        <td style="width: 5.88%;">'.$row['po_date'].'</td>
                        <td style="width: 5.88%;">'.$row['pack_size'].'</td>
                        <td style="width: 5.88%;">'.$row['qty'].'</td>
                        <td style="width: 5.88%;">'.$row['entry_date'].'</td>
                        <td style="width: 5.88%;"></td>
                        <td style="width: 5.88%;"></td>
                        <td style="width: 5.88%;"></td>
                    </tr>
                </tbody>';
                $i++;
            }
        }
            $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
 }
 
 
 else if ($_GET["type"] == "downloadChallanLog1") {
     if($_GET["plant_id"] == 59){
    $_GET['filename'] = 'Challans Log'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
    $html= "";
    $html.='
     <table border="1">
  
 <tr>
    <td style="line-height:20px;width: 780px;text-align:center;">Warehouse</td>
</tr>
<tr>
    <td style="line-height:20px;width: 780px;text-align:center;"> RAW MATERIAL AND PACKING MATERIAL INWARD REGISTER</td>
</tr>
<tr>
     <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Format No.: </td>
     <td style="line-height:20px;width: 390px;text-align:left;"> Change Control No.: </td>
 </tr>
 <tr>
     <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Effective Date:</td>
     <td style="line-height:20px;width: 390px;text-align:left;"> Review Date:</td>
 </tr>
 <tr>
    <td style="line-height:20px;width: 780px;text-align:left;"> Reference SOP No.: </td>
</tr>
</table>
<div>
</div>
     <table cellpadding="5" border="1">
    <thead>
      <tr>
     <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> GIR No.</td>
     <td style="line-height:20px;width: 43px;text-align:center;"rowspan="2";> Date</td>
     <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Name of Party</td>
     <td style="line-height:20px;width: 63px;text-align:left;"colspan="2";>Challan No/Invoice No.</td>
     <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> Po No & Date</td>
     <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> GRN No.</td>
     <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Description</td>
     <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Pack</td>
     <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Quantity</td>
     <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> Rate</td>
     <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> GST</td>
     <td style="line-height:20px;width: 93px;text-align:center;"colspan="3";> Mode of Transport</td>
     <td style="line-height:20px;width: 54px;text-align:center;"rowspan="2";> remarks</td>
</tr>
 <tr>
<td style="line-height:20px;width: 63px;text-align:center;"> No.</td>
 <td style="line-height:20px;width: 40px;text-align:center;"> Name</td>
<td style="line-height:20px;width: 25px;text-align:center;"> LR No.</td>
<td style="line-height:20px;width: 28px;text-align:center;"> Vehicle No.</td>
</tr>
    </thead>';
    $i=1;
    
    $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no,c1.pack_size,c1.grn_no,c1.qty,c1.rate FROM   challan_materials c1 LEFT JOIN challan c ON c.challan_no=c1.challan_no LEFT JOIN vendor v on c.vendor_no=v.vendor_no WHERE c.status='approve'";

    
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                // $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //             $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                //             $result2 = $conn->query($sql2);
                //             if ($result2->num_rows > 0) {
                //                 while ($row2 = $result2->fetch_assoc()) {
                          
        $html.='
        <tbody>
           <tr>
     <td style="line-height:20px;width: 53px;text-align:center;">  '.$row[" "].'</td>
     <td style="line-height:20px;width: 43px;text-align:center;"> '.$row["challan_date"].' </td>
     <td style="line-height:20px;width: 78px;text-align:center;">  '.$row["vendor_name"].'</td>
     <td style="line-height:20px;width: 63px;text-align:left;"> '.$row["challan_no"].'</td>
      <td style="line-height:20px;width: 58px;text-align:center;">'.$row["po_no"].' </td>
     <td style="line-height:20px;width: 58px;text-align:center;"> '.$row["grn_no"].'</td>
     <td style="line-height:20px;width: 78px;text-align:center;"> '.$row["inward_type"].' </td>
     <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["pack_size"].'</td>
     <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["qty"].'</td>
     <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["rate"].'</td>
     <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["gst_total"].'</td>
     <td style="line-height:20px;width: 40px;text-align:center;">'.$row["driver_name"].' </td>
     <td style="line-height:20px;width: 25px;text-align:center;"> '.$row["lr_no"].'</td>
     <td style="line-height:20px;width: 28px;text-align:center;"> '.$row["vehicle_no"].'</td>
     <td style="line-height:20px;width: 54px;text-align:center;"> '.$row['remark'].' </td>
</tr>
        </tbody>';
        $i++;
             
                //                 }
                //             }
                           
                //     }
                // }
               
            }
        }
    $html.='</table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('ChallansLog.pdf', 'I');
 }
         
         
     
     else{
     
    $_GET['filename'] = 'Challans Log'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
    $html= "";
    $html.='
    <h2 style="text-align:center">Challans Log</h2>
    <table cellpadding="5" border="1">
    <thead>
       <tr style="text-align: center; background-color:#DDDAD9;">
            <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:7%; text-align:centre;"><b>Inward No</b></td>
            <td style="width:7%; text-align:centre;"><b>Inward Date</b></td>
            <td style="width:9%; text-align:centre;"><b>Material Type</b></td>
            <td style="width:7%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:5%; text-align:centre;"><b>Challan No.</b></td>
            <td style="width:10%;text-align:centre;"><b>Challan Date</b></td>
            <td style="width:15%; text-align:centre;"><b>PO No.</b></td>
            <td style="width:10%; text-align:centre;"><b>PO Date</b></td>
            <td style="width:5%; text-align:centre;"><b>Invoice No.</b></td>
            <td style="width:10%; text-align:centre;"><b>Prepared Date</b></td>
            <td style="width:10%; text-align:centre;"><b>Prepared By</b></td>
        </tr>
    </thead>';
    $i=1;
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='approve' AND c.plant_id= '".$_GET["plant_id"]."' ORDER BY c.id DESC ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                // $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //             $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                //             $result2 = $conn->query($sql2);
                //             if ($result2->num_rows > 0) {
                //                 while ($row2 = $result2->fetch_assoc()) {
                          
        $html.='
        <tbody>
            <tr>
                <td style="width:5%;">'.$i.'.</td>
                <td style="width:7%;">'.$row['inward_no'].'</td>
                <td style="width:7%;">'.date('d-m-Y',strtotime($row['inward_date'])).'</td>
                <td style="width:9%;">'.$row['material_type'].'</td>
                <td style="width:7%;">'.$row['vendor_name'].'</td>
                <td style="width:5%;">'.$row['challan_no'].'</td>
                <td style="width:10%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                <td style="width:15%;">'.$row['po_no'].'</td>
                <td style="width:10%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                <td style="width:5%;">'.$row['tax_invoice'].'</td>
                <td style="width:10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                <td style="width:10%;">'.$row['entry_by'].'</td>
            </tr>
        </tbody>';
        $i++;
             
                //                 }
                //             }
                           
                //     }
                // }
               
            }
        }
     }
    $html.='</table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('ChallansLog.pdf', 'I');
 }
//  else if ($_GET["type"] == "downloadChallanLog2") {
//     $_GET['filename'] = 'Challans Log'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
//     $html= "";
//     $html.='
//      <table border="1">
  
//  <tr>
//     <td style="line-height:20px;width: 780px;text-align:center;">Warehouse</td>
// </tr>
// <tr>
//     <td style="line-height:20px;width: 780px;text-align:center;"> RAW MATERIAL AND PACKING MATERIAL INWARD REGISTER</td>
// </tr>
// <tr>
//      <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Format No.: </td>
//      <td style="line-height:20px;width: 390px;text-align:left;"> Change Control No.: </td>
//  </tr>
//  <tr>
//      <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Effective Date:</td>
//      <td style="line-height:20px;width: 390px;text-align:left;"> Review Date:</td>
//  </tr>
//  <tr>
//     <td style="line-height:20px;width: 780px;text-align:left;"> Reference SOP No.: </td>
// </tr>
// </table>
// <div>
// </div>
//      <table cellpadding="5" border="1">
//     <thead>
//       <tr>
//      <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> GIR No.</td>
//      <td style="line-height:20px;width: 43px;text-align:center;"rowspan="2";> Date</td>
//      <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Name of Party</td>
//      <td style="line-height:20px;width: 63px;text-align:left;"colspan="2";>Challan No/Invoice No.</td>
//      <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> Po No & Date</td>
//      <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> GRN No.</td>
//      <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Description</td>
//      <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Pack</td>
//      <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Quantity</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> Rate</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> GST</td>
//      <td style="line-height:20px;width: 93px;text-align:center;"colspan="3";> Mode of Transport</td>
//      <td style="line-height:20px;width: 54px;text-align:center;"rowspan="2";> remarks</td>
// </tr>
//  <tr>
// <td style="line-height:20px;width: 63px;text-align:center;"> No.</td>
//  <td style="line-height:20px;width: 40px;text-align:center;"> Name</td>
// <td style="line-height:20px;width: 25px;text-align:center;"> LR No.</td>
// <td style="line-height:20px;width: 28px;text-align:center;"> Vehicle No.</td>
// </tr>
//     </thead>';
//     $i=1;
    
//     $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no,c1.pack_size,c1.grn_no,c1.qty,c1.rate FROM   challan_materials c1 LEFT JOIN challan c ON c.challan_no=c1.challan_no LEFT JOIN vendor v on c.vendor_no=v.vendor_no WHERE c.status='approve'";

    
 
//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
              
//                 // $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
//                 // $result1 = $conn->query($sql1);
//                 // if ($result1->num_rows > 0) {
//                 //     while ($row1 = $result1->fetch_assoc()) {
//                 //             $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
//                 //             $result2 = $conn->query($sql2);
//                 //             if ($result2->num_rows > 0) {
//                 //                 while ($row2 = $result2->fetch_assoc()) {
                          
//         $html.='
//         <tbody>
//           <tr>
//      <td style="line-height:20px;width: 53px;text-align:center;">  '.$row[" "].'</td>
//      <td style="line-height:20px;width: 43px;text-align:center;"> '.$row["challan_date"].' </td>
//      <td style="line-height:20px;width: 78px;text-align:center;">  '.$row["vendor_name"].'</td>
//      <td style="line-height:20px;width: 63px;text-align:left;"> '.$row["challan_no"].'</td>
//       <td style="line-height:20px;width: 58px;text-align:center;">'.$row["po_no"].' </td>
//      <td style="line-height:20px;width: 58px;text-align:center;"> '.$row["grn_no"].'</td>
//      <td style="line-height:20px;width: 78px;text-align:center;"> '.$row["inward_type"].' </td>
//      <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["pack_size"].'</td>
//      <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["qty"].'</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["rate"].'</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["gst_total"].'</td>
//      <td style="line-height:20px;width: 40px;text-align:center;">'.$row["driver_name"].' </td>
//      <td style="line-height:20px;width: 25px;text-align:center;"> '.$row["lr_no"].'</td>
//      <td style="line-height:20px;width: 28px;text-align:center;"> '.$row["vehicle_no"].'</td>
//      <td style="line-height:20px;width: 54px;text-align:center;"> '.$row['remark'].' </td>
// </tr>
//         </tbody>';
//         $i++;
             
//                 //                 }
//                 //             }
                           
//                 //     }
//                 // }
               
//             }
//         }
//     $html.='</table>';
//     $pdf->writeHTML($html, true, false, false, false, '');
//     $pdf->Output('ChallansLog.pdf', 'I');
//  }








///////////////////////////////////////////
//   else if($_GET["type"] == "downloadChallanLog2") { //amerdeep
//         $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
//         //  $html= "";
           
      
     
//         //         $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='approve' AND c.plant_id= '".$_GET["plant_id"]."' ORDER BY c.id DESC ";

//         // $result = $conn->query($sql);
//         // if ($result->num_rows > 0) {
//         //     while ($row = $result->fetch_assoc()) {
                 
          
//     		       $html='  

//  <table border="1">
  
//  <tr>
//     <td style="line-height:20px;width: 780px;text-align:center;">Warehouse</td>
// </tr>
// <tr>
//     <td style="line-height:20px;width: 780px;text-align:center;"> RAW MATERIAL AND PACKING MATERIAL INWARD REGISTER</td>
// </tr>
// <tr>
//      <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Format No.: </td>
//      <td style="line-height:20px;width: 390px;text-align:left;"> Change Control No.: </td>
//  </tr>
//  <tr>
//      <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Effective Date:</td>
//      <td style="line-height:20px;width: 390px;text-align:left;"> Review Date:</td>
//  </tr>
//  <tr>
//     <td style="line-height:20px;width: 780px;text-align:left;"> Reference SOP No.: </td>
// </tr>
// </table>
// <div>
// </div>
// <table border="1">
// <tr>
//      <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> GIR No.</td>
//      <td style="line-height:20px;width: 43px;text-align:center;"rowspan="2";> Date</td>
//      <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Name of Party</td>
//      <td style="line-height:20px;width: 63px;text-align:left;"colspan="2";>Challan No/Invoice No.</td>
//      <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> Po No & Date</td>
//      <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> GRN No.</td>
//      <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Description</td>
//      <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Pack</td>
//      <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Quantity</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> Rate</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> GST</td>
//      <td style="line-height:20px;width: 93px;text-align:center;"colspan="3";> Mode of Transport</td>
//      <td style="line-height:20px;width: 54px;text-align:center;"rowspan="2";> remarks</td>
// </tr>
// <tr>
// <td style="line-height:20px;width: 31px;text-align:center;"> No.</td>
// <td style="line-height:20px;width: 32px;text-align:center;"> Date</td>
// <td style="line-height:20px;width: 40px;text-align:center;"> Name</td>
// <td style="line-height:20px;width: 25px;text-align:center;"> LR No.</td>
// <td style="line-height:20px;width: 28px;text-align:center;"> Vehicle No.</td>
// </tr>
//  </table>';

//                  $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='approve' AND c.plant_id= '".$_GET["plant_id"]."' ORDER BY c.id DESC ";

//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
                 
          
//     		       $html='    
//     		       <tbody>
// <tr>
//      <td style="line-height:20px;width: 53px;text-align:center;">  '.$row[" "].'</td>
//      <td style="line-height:20px;width: 43px;text-align:center;"> '.$row[" "].' </td>
//      <td style="line-height:20px;width: 78px;text-align:center;">  '.$row[" "].'</td>
//      <td style="line-height:20px;width: 31px;text-align:left;"> '.$row["challan_no"].'</td>
//      <td style="line-height:20px;width: 32px;text-align:left;">'.$row[" "].' </td>
//      <td style="line-height:20px;width: 58px;text-align:center;">'.$row[" "].' </td>
//      <td style="line-height:20px;width: 58px;text-align:center;"> '.$row["grn_no"].'</td>
//      <td style="line-height:20px;width: 78px;text-align:center;"> '.$row[" "].' </td>
//      <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["pack_size"].'</td>
//      <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["qty"].'</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["rate"].'</td>
//      <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["gst"].'</td>
//      <td style="line-height:20px;width: 40px;text-align:center;">'.$row[" "].' </td>
//      <td style="line-height:20px;width: 25px;text-align:center;"> '.$row[" "].'</td>
//      <td style="line-height:20px;width: 28px;text-align:center;"> '.$row[" "].'</td>
//      <td style="line-height:20px;width: 54px;text-align:center;"> '.$fullRangeCalibration['remark'].' </td>
// </tr>
 
//   </tbody>
//          ';
//             }
//         }

//  $html.='</table>
// <div></div>   
// <div></div>   
// <div></div>   

// <tr>
//     <td style="line-height:20px;width: 390px;text-align:left;"> Checked By-</td>
//     <td style="line-height:20px;width: 390px;text-align:center;"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Verified By-</td>
// </tr>
// <tr>
//     <td style="line-height:20px;width: 390px;text-align:left;"> (Sign/Date)</td>
//     <td style="line-height:20px;width: 390px;text-align:center;"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Sign/Date)</td>
// </tr>


// <div></div>   

  

// <table border="1">
// <tr>
//     <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;">Sign/Date</td>
//     <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;">Sign/Date </td>
//     <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;">Sign/Date </td>
// </tr>
// <tr>
//     <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;"> Prepared By Warehouse</td>
//     <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;"> Reviewed By Warehouse</td>
//     <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;"> Approved By QA</td>
// </tr>
// </table>
// ';
 
            
        
     
//      $pdf->writeHTML($html, true, false, false, false, '');
        
//         $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
//   }
}
$conn->close();
?>