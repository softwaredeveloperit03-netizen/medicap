<?php





ini_set('display_errors', 1);
error_reporting(E_ALL);
 

    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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
    
    
    
    
    
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

  $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    
    
    
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    
    if ($_GET["type"] == "saveDirectChallan") {
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
        
        $sql = "INSERT INTO challan (user_no, material_type, challan_no, challan_date, vendor_no, tax_invoice, entry_by, entry_date, gross_total, gst_total, net_total, po_no, po_date, challan_file) VALUES ('".$_GET["user_no"]."','Engineering','".$input["challan_no"]."', '".$input["challan_date"]."', '".$input["vendor_no"]."', '".$input["tax_invoice"]."', '".$_GET["emp_id"]."', '$entry_date', '".$input["gross_total"]."', '".$input["gst_total"]."', '".$input["net_total"]."', '".$input["po_no"]."', '".$input["po_date"]."', '$upload_challan')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $materials = json_decode($input["materials"], true);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO challan_materials (user_no, challan_no, material_type, material_code, qty, unit, rate, gst, required_for, gross_total, gst_total, net_total) VALUES ('".$_GET["user_no"]."','".$input["challan_no"]."','".$material["material_type"]."', '".$material["material_code"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["rate"]."', '".$material["gst"]."', 'Own', '".$material["gross_total"]."','".$material["gst_total"]."', '".$material["net_total"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getChallansLog") {
        $output = Array();
        $sql = "SELECT c.*, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.material_type='Engineering' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id ORDER BY c.id DESC ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		    $output1 = array();
                $sql1 = "SELECT p.*, m.material_name, m.material_type, m.material_code,m.hsn FROM challan_materials p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."' GROUP BY p.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["received_rate"] = $row1["quotation_amt"];
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
    
    else if ($_GET["type"] == "jaduunder") {
        $output = Array();
         $sql = "SELECT * FROM challan WHERE   status Like '%pending%' 
         AND (material_type='General Material' OR material_type='Engineering' OR material_type='Chemical Material' ) ORDER BY id DESC";
        //$sql = "SELECT * FROM challan WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["vendor_name"] = $row1["vendor_name"];
    		            $row["email"] = $row1["email"];
    		            $row["gst_no"] = $row1["gst_no"];
    		        }
    		    }
                $output1 = Array();
                if($row["material_type"]!="Chemical Material"){
                    $sql2 = "SELECT p.*, m.material_subtype, m.material_name FROM challan_materials p LEFT JOIN general_material m 
                    ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."'";
                }
                else if($row["material_type"]=="Chemical Material"){
                     $sql2 = "SELECT p.*, m.chemical_name    FROM challan_materials p LEFT JOIN chemical m 
                    ON p.material_code=m.chemical_no WHERE p.user_no='".$_GET["user_no"]."' AND p.challan_no='".$row["challan_no"]."'";
                }
                    
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output1[] = $row2;
                        }
                    }
                    $row["materials"] = $output1;
                    $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
        else if ($_GET["type"] == "getPendingChallans") {
        $output = Array();
 
          $sql = "SELECT DISTINCT c.id,   c.*, DATE(c.entry_date) as entry_date, v.vendor_name,  v.gst_no,
      
         s.file as challanFile
        FROM challan c
        LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
        LEFT JOIN UploadChallan s ON c.challan_no = s.ch_no
        WHERE c.plant_id= '".$_GET["plant_id"]."' AND c.status='waiting_for_checking' AND c.material_type!='Raw Material' and c.material_type!='Packing Material'; "; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		   $row = array_map('utf8_encode', $row);
                $output1 = Array();
                
                
                 $sql1 = "SELECT DISTINCT m.id,  c.*, m.material_type, m.material_subtype, m.grade, m.material_name,v.vendor_name as 
                Supplier,v1.vendor_name  as Manufacturer  FROM challan_materials c LEFT JOIN my_view m ON 
                c.material_code=m.material_code
                LEFT JOIN vendor v on c.vendor_no = v.vendor_no
                LEFT JOIN vendor v1 on c.manufacturer_no = v1.vendor_no   WHERE c.challan_no='".$row["challan_no"]."'  "; // and c.status='pending'
      
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $row1["received_rate"] = $row1["rate"];
                        $row1["diff"] = 0;
                           $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc();
          $row1['gradeName'] = $prodLatest['gradeName'];
                        $output1[] = $row1;
                        
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

     else if ($_GET["type"] == "getPendingGRNMeha") {
        
                $output = Array();
        
        
        $sql="select *, (SELECT COUNT(id) FROM challan_materials c WHERE c.status = 'inprocess' AND c.weighing = 'pending' AND c.grn = 'pending' AND c.challan_no = c1.challan_no) AS counts
        ,
        (SELECT  v.vendor_name  FROM challan_materials c LEFT JOIN 
       challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m ON c.material_code=m.material_code LEFT JOIN vendor v 
       ON c1.vendor_no=v.vendor_no left join purchaseorder p on p.po_no=c1.po_no left join indend_raw i on p.indent_no=i.indend_no and i.material_code=c.material_code
       WHERE    c.status='inprocess' AND weighing='pending' AND grn='pending' AND   c1.material_type LIKE 'Miscellaneous'  AND    c.challan_no = c1.challan_no limit 1) as vendor_name
        from challan c1  WHERE c1.status='approve' AND c1.material_type LIKE 'Miscellaneous'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                        $sql1 = "SELECT c.*,c1.challan_date,c1.tax_invoice,c1.transport_company,c1.transport_frieght, c1.po_no,c1.po_date, 
       c1.vendor_no, v.vendor_name,v.vendor_type, m.material_type, m.material_subtype,m.material_name,i.department FROM challan_materials c LEFT JOIN 
       challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m ON c.material_code=m.material_code LEFT JOIN vendor v 
       ON c1.vendor_no=v.vendor_no left join purchaseorder p on p.po_no=c1.po_no left join indend_raw i on p.indent_no=i.indend_no and i.material_code=c.material_code
       WHERE   c.user_no='".$_GET["user_no"]."'
        AND c.status='inprocess' AND weighing='pending' AND grn='pending' AND   c1.material_type LIKE 'Miscellaneous'  AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' and c1.id='".$row["id"]."' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                if (!empty($output1)) {  
    $row["Materials"] = $output1;
    $output[] = $row;
}
             
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingChallans2611") {
        $output = Array();
 
         $sql = "SELECT DISTINCT c.id,   c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no s.file as ChallanFile
        FROM challan
        c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        s LEFT JOIN UploadChallan c ON c.challan_no=s.ch_no 
        WHERE c.plant_id= '".$_GET["plant_id"]."' AND c.status='waiting_for_checking' AND c.material_type = 'Miscellaneous' "; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		   $row = array_map('utf8_encode', $row);
                $output1 = Array();
                
                
                 $sql1 = "SELECT DISTINCT m.id,  c.*, m.material_type, m.material_subtype, m.grade, m.material_name,v.vendor_name as 
                Supplier,v1.vendor_name  as Manufacturer  FROM challan_materials c LEFT JOIN my_view m ON 
                c.material_code=m.material_code
                LEFT JOIN vendor v on c.vendor_no = v.vendor_no
                LEFT JOIN vendor v1 on c.manufacturer_no = v1.vendor_no   WHERE c.challan_no='".$row["challan_no"]."'  "; // and c.status='pending'
      
      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $row1["received_rate"] = $row1["rate"];
                        $row1["diff"] = 0;
                           $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row1['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc();
          $row1['gradeName'] = $prodLatest['gradeName'];
                        $output1[] = $row1;
                        
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getApproveChallans") {
        $output = Array();
        $sql = "SELECT distinct c.id, c.*, DATE(c.entry_date) as entry_date, v.vendor_name,   v.gst_no FROM challan c LEFT JOIN vendor
        v ON c.vendor_no=v.vendor_no WHERE c.status='approve' AND   c.material_type='Miscellaneous' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		   $row = array_map('utf8_encode', $row);
                $output1 = Array();
                $sql1 = "SELECT c.*,m.material_name,m.grade, m.material_type, m.material_subtype FROM challan_materials c LEFT JOIN my_view m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."'";
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
    }else if ($_GET["type"] == "getPendingChallansLocal") {
        $output = Array();
        $sql = "SELECT c.*, DATE(c.entry_date) as entry_date, v.vendor_name, v.email, v.gst_no FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.status='pending' AND c.type='Local' AND c.material_type='General Material' ORDER BY c.id DESC ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $output[] = $row;
            }
        }
        echo json_encode($output);
 
    }
    
        
         
        else if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
         $sql = "SELECT c.*,c1.challan_date,c1.transport_company,c1.transport_frieght, c1.po_no,c1.po_date, 
       c1.vendor_no, v.vendor_name,v.vendor_type, m.material_type, m.material_subtype,m.material_name FROM challan_materials c LEFT JOIN 
       challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m ON c.material_code=m.material_code LEFT JOIN vendor v 
       ON c1.vendor_no=v.vendor_no WHERE c1.status='approve' AND c.status='pending' AND c.receiving='pending' AND c1.material_type!='Raw Material' and c1.material_type!='Packing Material';";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
      }
        else if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
         $sql = "SELECT c.*,c1.challan_date,c1.tax_invoice,c1.transport_company,c1.transport_frieght, c1.po_no,c1.po_date, 
       c1.vendor_no, v.vendor_name,v.vendor_type, m.material_type, m.material_subtype,m.material_name FROM challan_materials c LEFT JOIN 
       challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m ON c.material_code=m.material_code LEFT JOIN vendor v 
       ON c1.vendor_no=v.vendor_no WHERE c1.status='approve' AND c.status='pending' AND c.receiving='pending' AND c1.material_type LIKE 'Miscellaneous'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
      }
        else if ($_GET["type"] == "getPendingReceivingsMeha") {
        $output = Array();
        
        
        $sql="select * from challan c1  WHERE c1.status='approve' AND c1.material_type LIKE 'Miscellaneous'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                        $sql1 = "SELECT c.*,c1.challan_date,c1.tax_invoice,c1.transport_company,c1.transport_frieght, c1.po_no,c1.po_date, 
       c1.vendor_no, v.vendor_name,v.vendor_type, m.material_type, m.material_subtype,m.material_name,i.department FROM challan_materials c LEFT JOIN 
       challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m ON c.material_code=m.material_code LEFT JOIN vendor v 
       ON c1.vendor_no=v.vendor_no
       left join purchaseorder p on p.po_no=c1.po_no left join indend_raw i on p.indent_no=i.indend_no and i.material_code=c.material_code
       WHERE c1.status='approve' AND c.status='pending' AND c.receiving='pending' AND c1.material_type LIKE 'Miscellaneous' and c1.id='".$row["id"]."' ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                if (!empty($output1)) { // Check if $output1 (Materials) array has elements
    $row["Materials"] = $output1;
    $output[] = $row;
}
                //       $row["Materials"] = $output1;

                // $output[] = $row;
            }
        }
        echo json_encode($output);
      }
        else if ($_GET["type"] == "updateChallanold") {
            
            
         $sql = "UPDATE challan SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', gross_total='".$input["gross_total"]."', gst_total='".$input["gst_total"]."', net_total='".$input["net_total"]."', weighing_procedure='".$_GET["weighing_procedure"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
            $materialsJson = $_POST["materials_list"];
            $materials = json_decode($materialsJson, true);
            if (is_array($materials)) {
    for ($i = 0; $i < count($materials); $i++) {
 $material = $materials[$i];
                
                 $sql1 = "UPDATE challan_materials SET rate='".$material["received_rate"]."', diff='".$material["diff"]."', gross_total='".$material["gross_total"]."', gst_total='".$material["gst_total"]."', net_total='".$material["net_total"]."' WHERE id='".$material["id"]."'";
                $conn->query($sql1);
    }
} else {
    echo "{\"status\":\"".$conn->error."\"}";
}
            if ($_GET["status"] == "reject") {
                 $sql = "UPDATE purchaseorder SET is_security_receive='No' WHERE po_no='".$_GET["po_no"]."'";
                 $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    }
    
    else if ($_GET["type"] == "updateChallan") {
        
      
            
            $input  = $_POST;
             $chid= $_GET["id"];
        	if(isset($_FILES["challan_file"])) {
            $file_tmp =$_FILES['challan_file']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['challan_file']['name'])));
            $file_name = $chid.basename($_FILES["challan_file"]["name"]);
            $challan_file = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/challan/".$file_name);
        }
        
         $sql = "UPDATE challan SET next_stage='Checking1', status='".$_GET["status"]."',challan_file = '$challan_file', approve_by='".$_GET["emp_id"]."', 
        approve_date='$entry_date', gross_total='".$input["gross_total"]."', gst_total='".$input["gst_total"]."', 
        net_total='".$input["net_total"]."', weighing_procedure='".$_GET["weighing_procedure"]."' WHERE id='".$_GET["id"]."'";
       // echo $sql;
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
           
            
            
            if($materials!=''){
                
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                
               $sql1 = "UPDATE challan_materials SET tax_invoice='".$material["tax_invoice"]."', received_rate='".$material["received_rate"]."', diff='".$material["diff"]."', gross_total='".$material["gross_total"]."', gst_total='".$material["gst_total"]."', net_total='".$material["net_total"]."' WHERE id='".$material["id"]."'";
               
                $conn->query($sql1);
            }
            }
            
          
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    else if($_GET["type"]=="receiveMaterial") {
        
        
         $sql = "UPDATE challan_materials SET  manufacturer='".$input["manufacturer"]."', received_qty='".$input["received_qty"]."', 
        containers='".$input["containers"]."', status='inprocess', receiving='approve', pack_size='".$input["pack_size"]."', 
        challan_qty='".$input["challan_qty"]."', container_type='".$input["container_type"]."', vehicle_condition='".$input["vehicle_condition"]."',
        packing_condition='".$input["packing_condition"]."', outer_packing='".$input["outer_packing"]."', received_by='".$_GET["emp_id"]."', 
        receiving_date='$entry_date' WHERE id='".$input["id"]."'";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\",\"error\":\"".$conn->error."\"}";
        }
        
        
    }
    else if($_GET["type"]=="receiveMaterialMeha") {
        
        $json_obj = json_encode($input["materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
          $sql = "UPDATE challan_materials SET  manufacturer='".$values["manufacturer"]."', received_qty='".$values["received_qty"]."', 
        containers='".$values["packs"]."', status='inprocess', receiving='approve', pack_size='".$values["pack_size"]."', 
        challan_qty='".$values["challan_qty"]."', container_type='".$values["container_type"]."', vehicle_condition='".$values["vehicle_condition"]."',
        packing_condition='".$values["packing_condition"]."', outer_packing='".$values["outer_packing"]."', received_by='".$_GET["emp_id"]."',batch_no='".$values["batch_no"]."', 
        receiving_date='$entry_date' , department='".$values["department"]."' WHERE id='".$values["id"]."'";
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
    else if ($_GET["type"] == "getReceivingLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.receiving !='pending' AND c1.material_type!='Raw Material' and c1.material_type!='Packing Material'  AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getReceivingLogMeha") {
        
        
$output = Array();
        
        
        $sql="select * from challan c1  WHERE c1.status='approve' AND c1.material_type LIKE 'Miscellaneous'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                        $sql1 = "SELECT c.*,c1.challan_date,c1.tax_invoice,c1.transport_company,c1.transport_frieght, c1.po_no,c1.po_date, 
       c1.vendor_no, v.vendor_name,v.vendor_type, m.material_type, m.material_subtype,m.material_name,i.department FROM challan_materials c LEFT JOIN 
       challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m ON c.material_code=m.material_code LEFT JOIN vendor v 
       ON c1.vendor_no=v.vendor_no
       left join purchaseorder p on p.po_no=c1.po_no left join indend_raw i on p.indent_no=i.indend_no and i.material_code=c.material_code
 WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.receiving !='pending' AND c1.material_type LIKE 'Miscellaneous'  AND v.vendor_no LIKE '%".$_GET["vendor_no"]."'  and c1.id='".$row["id"]."' ";                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                 $row1["batches"] = json_decode($row1["batches"]);
                $row1["receiving_details"] = json_decode($row1["receiving_details"]);
                $row1["dedusting_details"] = json_decode($row1["dedusting_details"]);
                                $output1[] = $row1;
                            }
                        }
                if (!empty($output1)) { // Check if $output1 (Materials) array has elements
    $row["Materials"] = $output1;
    $output[] = $row;
}
              
            }
        }
        echo json_encode($output);
      
    } 
    else if ($_GET["type"] == "getPendingGRN") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_type as mt_type,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='inprocess' AND c.weighing='pending' AND c.grn='pending' AND    c1.material_type!='Raw Material' and c1.material_type!='Packing Material'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getapprovalPendingGRN") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN general_material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."'AND c.status='grn checking' AND weighing='pending' AND grn='checking' AND c1.material_type='General Material' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveGRN") {
        
        
        
        $grn_no = "GRN-00".$input["id"];
        
        $sql = "UPDATE challan_materials SET grn='checking', status='grn checking', grn_no='$grn_no', grn_by='".$_GET["emp_id"]."', 
        grn_date = '".$input["grn_date"]."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
            if ($_GET["mt_type"] != "QC Material") {
            
            
            
            $sql1 = "INSERT INTO engi_stock (material_type, material_code, grn_no, pack_size, containers, qty, unit, entry_by, 
            entry_date,plant_id) VALUES ('".$input["material_type"]."','".$input["material_code"]."','$grn_no',
            '".$input["pack_size"]."', '".$input["containers"]."', '".$input["received_qty"]."', '".$input["unit"]."', 
            '".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."')";
            $conn->query($sql1);
            
            
            }
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if ($_GET["type"] == "saveGRNMeha") {
        
                $json_obj = json_encode($input["Materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
                    $grn_no = "GRN-00".$values["id"];
    $sql = "UPDATE challan_materials SET grn='checking', status='grn checking', grn_no='$grn_no', grn_by='".$_GET["emp_id"]."', 
        grn_date='$entry_date' WHERE id='".$values["id"]."'";
        if ($conn->query($sql)) {
             $sql1 = "INSERT INTO engi_stock (material_type, material_code, grn_no, pack_size, containers, qty, unit, entry_by, 
            entry_date,plant_id) VALUES ('".$values["material_type"]."','".$values["material_code"]."','$grn_no',
            '".$values["pack_size"]."', '".$values["containers"]."', '".$values["received_qty"]."', '".$values["unit"]."', 
            '".$_GET["emp_id"]."', '$entry_date','".$_GET["plant_id"]."')";
            $conn->query($sql1);
            
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
    else if ($_GET["type"] == "saveGRN_approval") {
        $grn_no = "GRN-".$input["id"];
        $sql = "UPDATE challan_materials SET grn='approve', status='approve', grn_no='$grn_no', grn_by='".$_GET["emp_id"]."', grn_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            // $sql1 = "INSERT INTO engi_stock (material_type, material_code, grn_no, pack_size, containers, qty, unit, entry_by, entry_date) VALUES ('".$input["material_type"]."','".$input["material_code"]."','$grn_no','".$input["pack_size"]."', '".$input["containers"]."', '".$input["received_qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
            // $conn->query($sql1);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "receivingMaterialLogPDF") {
      $_GET['filename'] = 'receivingMaterialLog'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;text-align:center;"><b>Sr.</b></td>
                    <td style="width: 13%;text-align:center;"><b>Material Type</b></td>
                    <td style="width: 13%;text-align:center;"><b>vendor Name</b></td>
                    <td style="width: 10%;text-align:center;"><b>Challan No</b></td>
                    <td style="width: 10%;teext-align:center;"><b>Challan Date</b></td>
                    <td style="width: 13%;text-align:center;"><b>Material Name</b></td>
                    <td style="width: 13%;text-align:center;"><b>Material Code</b></td>
                    <td style="width: 13%;text-align:center;"><b>Receiving Date</b></td>
                    <td style="width: 10%;text-align;center;"><b>Qty</b></td>
                       
                    </tr>
                </thead>';
                
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN general_material m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.receiving !='pending' AND (c1.material_type LIKE '%General Material%' 
       OR c1.material_type LIKE '%Engineering%' ) AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' ";
        $result = $conn->query($sql);
    	$output = Array();
    		$j=1;
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		                $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$j.'.</td>
                        <td style="width: 13%;">'.$row['material_subtype'].'</td>
                        <td style="width: 13%;">'.$row['vendor_name'].'</td>
                        <td style="width: 10%;">'.$row['challan_no'].'</td>
                        <td style="width: 10%;">'.$row['challan_date'].'</td>
                        <td style="width: 13%;">'.$row['material_name'].'</td>
                        <td style="width: 13%;">'.$row['material_code'].'</td>
                        <td style="width: 13%;">'.$row['receiving_date'].'</td>
                        <td style="width: 10%;">'.$row['received_qty'].'</td>
                        
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('receivingMaterialLog.pdf', 'I');
    }
    else if ($_GET["type"] == "rawStockLog") {
        $_GET['filename'] = 'Stock'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 10%;text-align:center;">Sr.</td>
                        <td style="width: 10%;text-align:center;">GRN No.</td>
                        <td style="width: 15%;text-align:center;">Material Type</td>
                        <td style="width: 15%;text-align:center;">Material Code</td>
                        <td style="width: 15%;text-align:center;">Material Name</td>
                        <td style="width: 10%;text-align:center;">Pack Size</td>
                        <td style="width: 15%;text-align:center;">Available Qty</td>
                        <td style="width: 10%;text-align:center;"> Balance Qty</td>
                       
                    </tr>
                </thead>';
                
        $sql = "SELECT s.*,m.material_name,m.material_subtype FROM engi_stock s LEFT JOIN general_material m ON 
        s.material_code=m.material_code WHERE s.status='Approved' AND s.plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
    	$output = Array();
    	$j=1;
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		                $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$j.'.</td>
                        <td style="width: 10%;">'.$row['grn_no'].'</td>
                        <td style="width: 15%;">'.$row['material_subtype'].'</td>
                        <td style="width: 15%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;">'.$row['pack_size'].'</td>
                        <td style="width: 15%;">'.$row['qty'].'</td>
                        <td style="width: 10%;">'.$row['avl_qty'].'</td>
                        
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Stock.pdf', 'I');
    }
    else if ($_GET["type"] == "GRNLogPDF") {
        $_GET['filename'] = 'GRNLog'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;text-align:center;"><b>Sr.</b></td>
                    <td style="width: 10%;text-align:center;"><b>GRN No </b></td>
                    <td style="width: 13%;text-align:center;"><b>Material Type</b></td>
                    <td style="width: 13%;text-align:center;"><b>Material Code</b></td>
                    <td style="width: 13%;teext-align:center;"><b>Material Name</b></td>
                    <td style="width: 13%;text-align:center;"><b>Vendor Name</b></td>
                    <td style="width: 13%;text-align:center;"><b>Containers</b></td>
                    <td style="width: 10%;text-align:center;"><b>Receiving Date</b></td>
                    <td style="width: 10%;text-align;center;"><b>Qty</b></td>
                    </tr>
                </thead>';
    //     $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,
    //     m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN general_material m 
    //     ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' 
    //     AND c.grn !='pending' AND (c1.material_type LIKE '%General Material%' 
    //   OR c1.material_type LIKE '%Engineering%' ) AND DATE(c1.entry_date) BETWEEN '".$_GET["from_date"]."' 
    //   AND '".$_GET["to_date"]."' ";
    
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_type,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.grn !='pending' AND c1.material_type LIKE 'Miscellaneous'  AND  m.material_type !='QC Material' AND m.material_type != 'Microbiology Materials' ";
        $result = $conn->query($sql);
        $j=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    		                $output[] = $row;
                    
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$j.'.</td>
                        <td style="width: 10%;">'.$row['grn_no'].'</td>
                        <td style="width: 13%;">'.$row['material_subtype'].'</td>
                        <td style="width: 13%;">'.$row['material_code'].'</td>
                        <td style="width: 13%;">'.$row['material_name'].'</td>
                        <td style="width: 13%;">'.$row['vendor_name'].'</td>
                        <td style="width: 13%;">'.$row['containers'].'</td>
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['receiving_date'])).'</td>
                        <td style="width: 10%;">'.$row['qty'].'</td>
                    </tr>';
                            
                $j++;
                             
                        
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('GRNLog.pdf', 'I');
}
    
    else if ($_GET["type"] == "getGRNLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, v.vendor_type,m.material_name,m.material_type,
        m.material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN my_view m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.grn !='pending' AND c1.material_type LIKE 'Miscellaneous'  AND  m.material_type !='QC Material' AND m.material_type != 'Microbiology Materials' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="getStock") {
        
        $sql = "SELECT s.*,m.material_name,m.material_subtype,m.material_type FROM engi_stock s LEFT JOIN my_view m ON
        s.material_code=m.material_code WHERE s.status='Approved' AND     s.plant_id= '".$_GET["plant_id"]."'";
        // s.material_code=m.material_code WHERE s.status='Approved' AND  m.material_type !='QC Material' AND m.material_type != 'Microbiology Materials' AND   s.plant_id= '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }

}

$conn->close();
?>