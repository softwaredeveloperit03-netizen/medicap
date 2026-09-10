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
        $sql = "SELECT * FROM vendor WHERE status='Approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE material_type='Packing Material' AND material_subtype='".$_GET["material_type"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getClients") {
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
    }if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
         
            $sql="SELECT c.*,v.vendor_type, c1.challan_date,c.inward_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,mm.material_type, 
            mm.material_subtype, mm.material_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no   LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
            JOIN my_view mm on c.material_code = mm.material_code
            WHERE c.status='pending' AND mm.material_type='Packing Material' AND c.receiving='pending' AND c1.plant_id='".$_GET["plant_id"]."'
            Order By c.inward_no desc ";
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                 $output1 = array();
                $sql1 = "SELECT c.*, m.material_type,m.artwork, m.material_subtype, m.material_name FROM challan_materials c 
                LEFT JOIN material m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."' AND c.material_code='".$row["material_code"]."' AND m.plant_id='".$_GET["plant_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        
                        
                        
                $sql12 = "SELECT artwork_no,version_no FROM artwork  WHERE art_status = 'Active' AND material_code = '".$row1["material_code"]."' AND plant_id='".$_GET["plant_id"]."'";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                        $row1['artwork_no'] = $row12['artwork_no'];
                        $row1['version_no'] = $row12['version_no'];
                    }
                }else{
                    
                    if($row1['artwork'] == 'YES'){
                        
                        $row1['artwork_no'] = 'Artwork Not Avaliable';
                        $row1['version_no'] = 'Artwork Not Avaliable';
                        
                    }else{
                        
                        $row1['artwork_no'] = 'NA';
                        $row1['version_no'] = 'NA';
                        
                    }
                     
                }
                          
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveReceivingDetails") {
        
         $last_id = 0;
            $sql = "SELECT count(*)+1 as count FROM challan_materials WHERE receiving='approve'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $last_id = $row["count"];
                }
            }
            
            $status = true;
             
          $length = 4;
          $number = substr(str_repeat(0, $length).$last_id, - $length);
          $receiving_no = 'RC'.$number;
               
        $materials = $input["materials"];
        for ($i = 0; $i < count($materials); $i++) {
            $material = $materials[$i];
            $short_qty = +$material["qty"] - +$material["received_qty"];
            
         $sql = "UPDATE challan_materials SET received_qty='".$material["received_qty"]."', po_status='".$material["po_status"]."', short_qty='$short_qty',
         artwork_no='".$material["artwork_no"]."', version_no='".$material["version_no"]."',dedusting='".$input["dedustingmaterial"]."',
         receiving_no='".$receiving_no."',receiving_date='".$entry_date."', receiving='approve', status='inprocess' WHERE id='".$material["id"]."'";
            
            if ($conn->query($sql)) {
                
                if($material["po_status"] == 'Keep Open( Short Qty)'){
                    $sqPo = "SELECT * FROM purchaseorder WHERE po_no = '".$input["po_no"]."' AND plant_id = '".$_GET["plant_id"]."' LIMIT 1";
                    $Res = $conn->query($sqPo);
                    if ($Res->num_rows > 0) {
                        while ($row = $Res->fetch_assoc()) {
                            $poId = $row['id'];
                            $sql1 = "UPDATE purchaseorder SET is_security_receive = 'No'  WHERE id = '$poId' ";
                            if ($conn->query($sql1)) {
                                $qty = +$material["qty"] - +$material["received_qty"];
                                $sql2 = "UPDATE po_material SET isMatIn = 'NO' , openQty = '$qty'  WHERE po_no = '$poId' AND 
                                material_code = '".$material["material_code"]."'";
                                $conn->query($sql2);
                            }  
                        }
                    }    
                }
    
            } else {
                 $status = false;
            }
        }
        
        
        if ($status) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
         
    }
    
    
    else if ($_GET["type"] == "getReceivingsLog") {
        $output = Array();
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='pending' AND m.material_type='Packing Material' AND c.receiving='pending' ";
               $sql="SELECT c.*,v.vendor_type, c1.challan_date,c.inward_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,mm.material_type, 
            mm.material_subtype, mm.material_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no   LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
            JOIN my_view mm on c.material_code = mm.material_code
            WHERE  mm.material_type='Packing Material' AND c.receiving != 'pending' AND c1.plant_id='".$_GET["plant_id"]."'
             
            Order By c.inward_no desc ";
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                 $output1 = array();
                $sql1 = "SELECT c.*, m.material_type,m.artwork, m.material_subtype, m.material_name FROM challan_materials c 
                LEFT JOIN material m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."' AND c.material_code='".$row["material_code"]."' AND m.plant_id='".$_GET["plant_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
              
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAllReceivingsLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving ='Approved' AND m.material_type ='Packing Material' GROUP BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $output1 = array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.material_name FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPendingDedustings") {
        $output = Array();
           $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,
           m.material_name, m.grade,c.containers as currectio FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no
           LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE 
           c1.plant_id='".$_GET["plant_id"]."' AND c.receiving != 'pending' AND c.dedusting='Yes' AND m.material_type ='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['grade'] = $prodLatest['grade'];
                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $row["batches"] = json_decode($row["batches"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLabours") {
        $output = Array();
        $sql = "SELECT * FROM labour WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND equipment_type='Vaccume Cleaner'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveMaterialDedusting") {
        
        $sql = "UPDATE challan_materials SET dedusting='approve',dedusting_by = '".$_GET["emp_id"]."' , dedusting_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
    } else if ($_GET["type"] == "getInprocessDedustings") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.dedusting='inprocess' AND m.material_type ='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "updateDedustingMaterial") {
        $input["approve_by"] = $_GET["emp_id"];
        $input["approve_date"] = $entry_date;
        $sql = "UPDATE challan_materials SET dedusting='".$_GET["status"]."', dedusting_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 

    else if ($_GET["type"] == "getDedustingsLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,
        m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
        LEFT JOIN material m ON c.material_code=m.material_code
      
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c1.plant_id='".$_GET["plant_id"]."' 
        AND c.dedusting ='approve' AND m.material_type ='Packing Material'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $row["batches"] = json_decode($row["batches"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                
           
       $sql1 = "SELECT firstName as dustBy FROM employee WHERE emp_id='".$row["dedusting_by"]."' limit 1";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $row['dustBy'] = $row1['dustBy'];
            }
        }
           
           
                
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingWeighingMaterials") {
       $output = Array();
       
       
            $sql="SELECT c.*,v.vendor_type, c1.challan_date,c.inward_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,mm.material_type, 
            mm.material_subtype, mm.material_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no   LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
            JOIN my_view mm on c.material_code = mm.material_code
            WHERE  mm.material_type='Packing Material' AND c.receiving != 'pending' AND c.weighing='pending' AND c1.plant_id='".$_GET["plant_id"]."'
             
            Order By c.inward_no desc ";
       
       
       
 
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                 
              $sql11 = "SELECT q.vendor_quotation_no,x.pack_size FROM quotation_hdr  q left join quotation_dtl x ON q.id = x.quotation_hdr_id  WHERE q.quotation_no = '".$row["quotation_no"]."'
               AND x.material_code = '".$row["material_code"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $row["vendor_quotation_no"] = $row11["vendor_quotation_no"];
                        $row["pack_size"] = $row11["pack_size"];
                         
                    }
                }
         
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   
    else if ($_GET["type"] == "saveWeighingMaterials") {
        $sql = "UPDATE challan_materials SET receiving_type= '".$input["receiving_type"]."' , weighing='inprocess', accept_qty='".$input["accepted_qty"]."', weighing_details='".json_encode($input["weighingList"])."', weighing_details_bkp= concat(weighing_details_bkp, ',', '".json_encode($input["weighingList"])."') WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "getCheckingWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,
        m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON
        c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor 
        v ON c1.vendor_no=v.vendor_no WHERE c1.plant_id='".$_GET["plant_id"]."' AND c.weighing='inprocess' 
        AND m.material_type ='Packing Material'  order by id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);

                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["batches"] = json_decode($row["batches"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRejectedWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.weighing='reject' AND m.material_type ='Packing Material' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);

                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["batches"] = json_decode($row["batches"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, 
        m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN 
        challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN 
        vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.weighing !='pending' 
        AND m.material_type ='Packing Material' AND v.vendor_name LIKE '%".$_GET["vendor_name"]."%'AND 
        DATE(c.inward_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getweighingLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,
        m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON
        c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor 
        v ON c1.vendor_no=v.vendor_no WHERE c1.plant_id='".$_GET["plant_id"]."' AND c.weighing='approve' 
        AND m.material_type ='Packing Material'  order by id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);

                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["batches"] = json_decode($row["batches"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getAllWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.weighing !='pending' AND m.material_type ='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    // else if ($_GET["type"] == "updaterejectWeighing") {
    //     $input["approve_by"] = $_GET["emp_id"];
    //     $input["approve_date"] = $entry_date;
    //     $sql = "UPDATE challan_materials SET weighing='reject', weighing_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"failed\"}";
    //     }
    // } 
    else if ($_GET["type"] == "updatapproveeWeighing") {
        $input["approve_by"] = $_GET["emp_id"];
        $input["approve_date"] = $entry_date;
         $sql = "UPDATE challan_materials SET status='approve', weighing='".$_GET["status"]."', grn = 'pending' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "getPendingGRN") {
        
         
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date,m.material_type, m.material_subtype, m.material_name, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name FROM challan_materials c LEFT JOIN challan c1 
        ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
        WHERE c.user_no='".$_GET["user_no"]."' AND m.material_type ='Packing Material' AND c.weighing='approve' AND c.grn='pending' AND
         c1.plant_id='".$_GET["plant_id"]."' ORDER BY c.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output1 = array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.material_name FROM challan_materials c LEFT JOIN material m 
                ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."' AND  m.plant_id='".$_GET["plant_id"]."' AND c.material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
        
    }
    
    else if ($_GET["type"] == "saveGRN") {
        
        
        
        $input["entry_by"] = $_GET["emp_id"];
        $input["entry_date"] = $entry_date;
        
        $grn_no = "GRN/23/P/0".$input["id"];

      
       
        $sql = "UPDATE challan_materials SET grn='inprocess',accept_qty='".$input["accept_qty"]."',
        grn_details='".json_encode($input)."', grn_no='$grn_no' , grn_date = '".$input["grn_date"]."' ,grn_receive_remark = '".$input["remark"]."' WHERE id = '".$input["id"]."'";
 
    
        if ($conn->query($sql)) {
            
            
            //  $sql = "UPDATE challan SET next_stage='GRN_Received' WHERE id='".$input["challan_id"]."'";
            //  $conn->query($sql);
            
            
            echo "{\"status\":\"success\"}";
            
        } else {
                    echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
        
        
        // $grn_no = "GRN-".$input["id"];
        // $materials = $input["materials"];
        // for ($i = 0; $i < count($materials); $i++) {
        //     $material = $materials[$i];
        //     $sql = "UPDATE challan_materials SET grn='Approved', grn_remark='".$input["remark"]."', grn_no='".$grn_no."', grn_date='".$input["grn_date"]."', grn_by='".$_GET["emp_id"]."' WHERE id='".$material["id"]."'";
        //     $conn->query($sql);
            
        //     $ar_no = "AR-".$material["id"];
        //     $sql1 = "INSERT INTO stock_book (user_no, material_type, grn_no,ar_no,vendor_no,material_code,qty,unit,entry_by,entry_date) VALUES ('".$_GET["user_no"]."','Packing Material','$grn_no', '$ar_no','".$input["vendor_no"]."', '".$material["material_code"]."', '".$material["received_qty"]."', '".$material["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
        //     $conn->query($sql1);
            
        //     $sql1 = "INSERT INTO sampling (user_no, material_code, received_qty, received_unit, grn_no, grn_date, grn_by) VALUES ('".$_GET["user_no"]."','".$material["material_code"]."','".$material["received_qty"]."','".$material["unit"]."','$grn_no', '".$input["grn_date"]."', '".$_GET["emp_id"]."')";
        //     $conn->query($sql1);
        // }
        // echo "{\"status\":\"success\"}";
        
        
        
    }
    else if ($_GET["type"] == "updateGRN") {
        
        $sql = "UPDATE challan_materials SET grn = '".$input["status"]."' ,grn_remark = '".$input["remark"]."', status='approve' 
        WHERE id='".$input["id"]."'";
        
        if ($conn->query($sql)) {
             
            if ($input["status"] == "approve") {
                
                   $sql1 = "SELECT IFNULL(COUNT(id), 0) as id FROM stock_book ORDER BY id DESC LIMIT 1";
                   $result1 = $conn->query($sql1);
                   $row1 = $result1->fetch_assoc();
            
                   $last_id=$row1["id"]+1;
                
                   $ar_no="AR-0".$last_id;
                
                   $batch =  "B".$last_id;
                 
                $sql1 = "INSERT INTO stock_book (plant_id, grn_no,ar_no,vendor_no,material_code, batch_no,qty,unit,entry_by,entry_date,grn_date,
                artwork_no,version_no) VALUES ('".$_GET["plant_id"]."','".$input["grn_no"]."', '$ar_no','".$input["vendor_no"]."', 
                '".$input["material_code"]."', '".$batch."','".$input["received_qty"]."', '".$input["unit"]."','".$_GET["emp_id"]."', '$entry_date',
                '".$input["grn_date"]."','".$input["artwork_no"]."','".$input["version_no"]."')";
                
                $conn->query($sql1);
                
                $sql11 = "INSERT INTO sampling (user_no, material_code, received_qty, received_unit, grn_no, grn_date, grn_by,batch_no,ar_no) VALUES 
                ('".$_GET["user_no"]."','".$input["material_code"]."','".$input["received_qty"]."','".$input["unit"]."','".$input["grn_no"]."',
                '".$input["grn_date"]."', '".$_GET["emp_id"]."','$batch','$ar_no')";
                  
                $conn->query($sql11);
             
            }
            
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    } 
    // else if ($_GET["type"] == "getGRNLog") {
    //     $output = Array();
    //     $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no,m.material_name,v.vendor_name FROM challan_materials c 
    //     LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
    //     LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
    //     WHERE  c1.plant_id='".$_GET["plant_id"]."' AND m.material_type ='Packing Material' AND c.receiving != 'pending' AND c.grn='approve'
    //     ORDER BY c.id desc";


    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $row = array_map('utf8_encode', $row);
    //             $output1 = array();
    //             $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.material_name 
    //             FROM challan_materials c 
    //             LEFT JOIN material m ON c.material_code=m.material_code 
    //             WHERE c.challan_no='".$row["challan_no"]."' AND  m.plant_id='".$_GET["plant_id"]."' AND c.material_code='".$row["material_code"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row1 = array_map('utf8_encode', $row1);
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row["materials"] = $output1;
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
        else if ($_GET["type"] == "getGRNLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no,m.material_name,v.vendor_name FROM challan_materials c 
        LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
        WHERE  c1.plant_id='".$_GET["plant_id"]."' AND m.material_type ='Packing Material' 
        ORDER BY c.id desc";
// AND c.receiving != 'pending' AND c.grn='approve'

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output1 = array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.material_name 
                FROM challan_materials c 
                LEFT JOIN material m ON c.material_code=m.material_code 
                WHERE c.challan_no='".$row["challan_no"]."' AND  m.plant_id='".$_GET["plant_id"]."' AND c.material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getPendingCheckingGRN") {
        $output = Array();
                $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no,m.material_name,m.sub_type,
                v.vendor_name FROM challan_materials c 
                LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
                
                LEFT JOIN material m ON c.material_code=m.material_code 
                LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
                WHERE  c1.plant_id='".$_GET["plant_id"]."' AND m.material_type ='Packing Material' 
                AND c.receiving != 'pending' AND c.grn='inprocess' ORDER BY c.id desc";


        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output1 = array();
                $sql1 = "SELECT c.*,m.sub_type, m.material_type, m.material_subtype, m.material_name 
                FROM challan_materials c 
                LEFT JOIN material m ON c.material_code=m.material_code 
                WHERE c.challan_no='".$row["challan_no"]."' AND  m.plant_id='".$_GET["plant_id"]."' AND c.material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getAllGRNLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type ='Packing Material' ORDER BY c.challan_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output1 = array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.material_name FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getproduct") {
        $output = Array();
        $sql = "SELECT * FROM product";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if($_GET["type"] == "downloadGRNLog"){
        $_GET['filename'] = 'Goods Receipt Notes Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Goods Receipt Notes Log</h2>
        <table cellpadding="5" style="text-align:left;">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.No</td>
                <td style="width:14%;">GRN No</td>
                <td style="width:12%;">GRN Date</td>
                <td style="width:10%;">Po No</td>
                <td style="width:12%;">Po Date</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:10%;">Challan No</td>
                <td style="width:12%;">Challan Date</td>
                <td style="width:10%;">Status</td>
            </tr>';
        $i=1;
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type ='Packing Material' AND v.vendor_name LIKE '%".$_GET["vendor_name"]."%' AND DATE(c.inward_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type ='Packing Material' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' GROUP BY c.challan_no";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='
            <tr nobr="true">
                <td style="width:5%;">'.$i.'.</td>
                <td style="width:14%;">'.$row['grn_no'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['grn_date'])).'</td>
                <td style="width:10%;">'.$row['po_no'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                <td style="width:15%;">'.$row['vendor_name'].'</td>
                <td style="width:10%;">'.$row['challan_no'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                <td style="width:10%;">'.$row['weighing'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
      $pdf->Output('Good Recipts.pdf', 'I');
      
    }
    
      else if($_GET["type"] == "downloadRetestsLog"){
        $_GET['filename'] = 'Goods Receipt Notes Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Goods Receipt Notes Log</h2>
        <table cellpadding="5" style="text-align:left;">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.No</td>
                <td style="width:14%;">GRN No</td>
                <td style="width:12%;">GRN Date</td>
                <td style="width:10%;">Po No</td>
                <td style="width:12%;">Po Date</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:10%;">Challan No</td>
                <td style="width:12%;">Challan Date</td>
                <td style="width:10%;">Status</td>
            </tr>';
        $i=1;
                $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name FROM retest r LEFT JOIN material m ON r.material_code=m.material_code LEFT JOIN vendor v ON r.vendor_no=v.vendor_no WHERE r.user_no='".$_GET["user_no"]."' AND r.vendor_no LIKE '%".$_GET["vendor_no"]."' AND DATE(r.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";

         $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='
            <tr nobr="true">
                <td style="width:5%;">'.$i.'.</td>
                <td style="width:14%;">'.$row['grn_no'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['grn_date'])).'</td>
                <td style="width:10%;">'.$row['po_no'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                <td style="width:15%;">'.$row['vendor_name'].'</td>
                <td style="width:10%;">'.$row['challan_no'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                <td style="width:10%;">'.$row['weighing'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
      $pdf->Output('Good Recipts.pdf', 'I');
      
    }
    else if ($_GET["type"] == "grnLabelsPDF"){
        $sql = "UPDATE label SET status='print' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            require '../tcpdf/tcpdf.php';
            class MYPDF extends TCPDF {
                public function Header() {}
                public function Footer() {}
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(10, 10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            for ($i=1; $i <= +$_GET['label_count']; $i++) {
                if($i == +$_GET['label_count'] && $i % 2 !== 0){
                    $html.='&nbsp;<br>
                    <table cellpadding="-5" style="width:100%;">
                        <tr>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:#C4A484">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                        <td style="border:solid 1px BCBBBA; width:74%;">
                                            <table>
                                                <tr>
                                                    <td colspan="2"><b>'.$_GET['material_name'].'</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Grade</b></td>
                                                    <td>'.$_GET['grade'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Batch No</b></td>
                                                    <td>'.$_GET['batch_no'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Container No</b></td>
                                                    <td>'.$i.' /'.$_GET['label_count'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width:2%;"></td>
                            <td style="width:49%;"></td>
                        </tr>
                    </table>';
                }else{
                $html.='&nbsp;<br>
                    <table cellpadding="-5" style="width:100%;">
                        <tr>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:#C4A484">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                        <td style="border:solid 1px BCBBBA; width:74%;">
                                            <table>
                                                <tr>
                                                    <td colspan="2"><b>'.$_GET['material_name'].'</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Grade</b></td>
                                                    <td>'.$_GET['grade'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Batch No</b></td>
                                                    <td>'.$_GET['batch_no'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Container No</b></td>
                                                    <td>'.$i.' /'.$_GET['label_count'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width:2%;"></td>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:#C4A484;">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                        <td style="border:solid 1px BCBBBA; width:74%;">
                                            <table>
                                                <tr>
                                                    <td colspan="2"><b>'.$_GET['material_name'].'</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Grade</b></td>
                                                    <td>'.$_GET['grade'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Batch No</b></td>
                                                    <td>'.$_GET['batch_no'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Container No</b></td>
                                                    <td>'.($i+1).' /'.$_GET['label_count'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>';
                }
                $i++;
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }
        
        
        
       } else if($_GET["type"] == "GRNPDF"){
        $_GET['filename'] = 'Goods Receipt Notes Log'; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
          $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,v.address,m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."' ";
        $result = $conn->query($sql);
       if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
               //$sql = "SELECT c.*,c1.entry_by,c1.approve_by, c1.challan_date, c1.inword_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,m.material_name, m.grade, m.material_subtype as material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' AND c.id='".$_GET["id"]."'";
    //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.grn !='pending' AND m.material_type='Raw Material' AND c.id='".$_GET["id"]."' GROUP BY c.id";
     
                
                // $row["receiving_details"] = json_decode($row["receiving_details"]);
                // $row["weighing_details"] = json_decode($row["weighing_details"]);
                // $row["grn_details"] = json_decode($row["grn_details"]);
                $html.='
                <h3 style="text-align:center;">Good Receipt Note</h3>
                <table cellpadding="3" style="text-align:left;">
                    <tr>
                        <td style="width:21%">Material Name:	</td>
                        <td style="width:79%">'.$row['material_name'].'</td>
                        
                    </tr>
                    <tr>
                        <td style="width:21%">Vendor Name:</td>
                       <td style="width:79%">'.$row["vendor_name"].'</td>
                        
                    </tr>
                    <tr>
                        <td style="width:21%">Vendor Location:</td>
                        <td style="width:32%">'.$row['address'].'</td>
                        <td style="width:15%">Manufacturer:</td>
                        <td style="width:32%">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td>Challan No.:</td>
                        <td>'.$row['challan_no'].'</td>
                        <td>Challan Date:</td>
                        <td>'.date('d/m/Y',strtotime($row['challan_date'])).'</td>
                    </tr>
                    <tr>
                        <td>PO No.:</td>
                        <td>'.$row['po_no'].'</td>
                        <td>PO Date:</td>
                        <td>'.date('d/m/Y',strtotime($row['po_date'])).'</td>
                    </tr>
                    <tr>
                        <td>Material Type:	</td>
                        <td>'.$row['material_type'].'</td>
                        <td>Material Subtype:</td>
                        <td>'.$row['material_subtype'].'</td>
                    </tr>
                    <tr>
                        <td>Material Code:</td>
                        <td>'.$row['material_code'].'</td>
                        <td>Material Grade:</td>
                        <td>'.$row['grade'].'</td>
                    </tr>
                    <tr>
                    <td style="width:21%">PO Qty:</td>
                     <td style="width:79%">'.$row['qty'].' kg</td>
                    </tr>
                </table>
                <div></div>
                 <h3 style="text-align:center;">Labeling Details:</h3>
                <table cellpadding="4" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">Sr</td>
                         <td style="width:10%">Medicap lot no</td>
                        <td style="width:10%;">Batch No	</td>
                        <td style="width:10%;">Qty	</td>
                        <td style="width:20%">No Of Containers	</td>
                        <td style="width:20%">Mfg Date</td>
                        <td style="width:20%">Exp Date</td>
                        
                    </tr>';
                
                          $i=1;
            $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                $row1["weight"] = json_decode($row1["weight"]);
                $html.='
                <tr>
                    <td style="width:10%;">'.$i++.'</td>
                    <td style="width:10%;">'.$row1["ar_no"].'</td>
                    <td style="width:10%;">'.$row1["batch_no"].'</td>
                    <td style="width:10%;">'.$row1["qty_received"].'</td>
                    <td style="width:20%;">'.$row["containers"].'</td>
                    <td style="width:20%;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                    <td style="width:20%;">'.date('M-Y',strtotime($row1["exp_date"])).'</td>
                </tr>';
                }
            }
           
                            
                $html.='</table>
                
                <h3 style="text-align:center;">Batch Details:</h3>
                <table cellpadding="3" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">sr</td>
                        <td style="width:20%;">Batch No	</td>
                        <td style="width:10%;">Received Qty		</td>
                        <td style="width:20%">Containers	</td>
                        <td style="width:20%">MFG Date</td>
                        <td style="width:20%">Exp Date</td>
                        </tr>';
                  $i=1;
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $row1["weight"] = json_decode($row1["weight"]);
                    $html.='
                    <tr>
                       <td >'.$i.'.</td>
                       <td>'.$row1['batch_no'].'</td>
                       <td>'.$row1['qty_received'].'</td>
                        <td>'.$row1['total_containers'].'</td>
                        <td>'.date('d-m-Y',strtotime($row1['mfg_date'])).'</td>
                        <td>'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                    </tr>';
                    $i++;
                    }
                }
                $html.='
                </table>';
                $html.='
                <div></div>
                <h3>Remark:</h3>
                <table cellpadding="3">
                    <tr>
                        <td >'.$row['grn_remark'].'</td>
                       </tr>
                       
                    </table>';
             
                   
           
                  $pdf->writeHTML($html, true, false, false, false, '');
                 $pdf->Output('','I');
             
            }
       }
            
        
        
        
    } else if($_GET["type"]=="getStock") {
        $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
        $sql = "SELECT s.*,
                COALESCE(m.material_type, mv.material_type) AS material_type,
                COALESCE(m.material_subtype, mv.material_subtype) AS material_subtype,
                COALESCE(m.grade, mv.grade) AS grade,
                COALESCE(m.material_name, mv.material_name) AS material_name,
                v.vendor_name
            FROM stock_book s
            LEFT JOIN material m ON s.material_code = m.material_code AND m.plant_id = '".$plantId."'
            LEFT JOIN my_view mv ON s.material_code = mv.material_code
            LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
            WHERE s.plant_id='".$plantId."'
            ORDER BY s.id DESC";
    	$result = $conn->query($sql);
        if (!$result) {
            // Fallback if my_view is unavailable
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name
                FROM stock_book s
                LEFT JOIN material m ON s.material_code = m.material_code AND m.plant_id = '".$plantId."'
                LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
                WHERE s.plant_id='".$plantId."'
                ORDER BY s.id DESC";
            $result = $conn->query($sql);
        }
    	$output = Array();
    	if($result && $result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
                $gradeRaw = isset($row['grade']) ? trim((string)$row['grade']) : '';
                $row['gradeName'] = '';
                if ($gradeRaw !== '') {
                    $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                    if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                        $resQ = $conn->query("SELECT GROUP_CONCAT(grade SEPARATOR ', ') AS gradeName FROM grade WHERE id IN (".$idPart.")");
                        if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['gradeName'])) {
                            $row['gradeName'] = $g['gradeName'];
                        }
                    }
                    if ($row['gradeName'] === '') {
                        $row['gradeName'] = $gradeRaw;
                    }
                } else {
                    $row['gradeName'] = '—';
                }
                if (!isset($row['status']) || $row['status'] === null || $row['status'] === '') {
                    $row['status'] = '—';
                }
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    	
    }else if ($_GET["type"] == "downloadRetestCalendar") {
        $_GET['filename'] = "Retest Calender"; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Retest Calender</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 8%;">Challan For</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">Material code</td>
                    <td style="width: 10%;">Material Name</td>
                    <td style="width: 5%;">Grade</td>
                    <td style="width: 8%;">Batch No</td>
                    <td style="width: 8%;">GRN No</td>
                    <td style="width: 5%;">Medicap lot no</td>
                    <td style="width: 10%;">Approved Date</td>
                    <td style="width: 10%;">Retest Date	</td>
                    <td style="width: 8%;">Mfg Date</td>
                    <td style="width: 8%;">Exp Date</td>
                </tr>
            </thead>';
        $i=1;
      $sql = "SELECT * FROM material_issue WHERE material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 5%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 5%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('stock.pdf', 'I');
    
    }
     else if ($_GET["type"] == "pm_stock_register"){ 
     if($_GET["plant_id"] == 59) {//amardeep 

        require '../tcpdf/tcpdf.php';
        $_GET['formatno'] = 'Format No:'; $_GET['pdftype'] = 'topheader-landscape'; include("../pdfimp.php");
       
        $html.='<h3 style="text-align:center;">MaterialLog List</h3>
            <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 12%;">Matrial type</td>
                    <td style="width: 12%;">Subtype</td>
                    <td style="width: 15%;">Matrial Name</td>
                    <td style="width: 8%;">Grade</td>
                    <td style="width: 11%;">Code</td>
                    <td style="width: 10%;">Received Qty</td>
                    <td style="width: 10%;">Issued Qty</td>
                    <td style="width: 10%;">Balance Qty</td>
                    <td style="width: 7%;">Unit</td>
                </tr>
            </thead>';
             
        $sql = "SELECT * FROM material WHERE status='approve'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT * FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row1["vendor_no"]."'";
                        $result2 = $corporate->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["vendor_name"] = $row2["vendor_name"];
                            }
                        }
                        
                        $issue_qty = 0;
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no='".$row1["ar_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["issue_qty"] = +$row2["issue_qty"];
                            }
                        } else {
                            $row1["issue_qty"] = 0;
                        }
                        $row1["balance_qty"] = +$row1["qty"] - +$row1["issue_qty"];
                        
                        $received_qty += +$row1["qty"];
                        $issue_qty += +$row1["issue_qty"];
                        
                        $output2 = array();
                        $sql2 = "SELECT m.*, p.product_name FROM material_issue m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.ar_no='".$row1["ar_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["issued"] = $output2;
                        $output1[] = $row1;
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["unit"]= $unit;
                    $row["grns"] = $output1;
                    $output[] = $row;
                $html.='<tr>
                    <td style="width: 5%;">'.$i++.'</td>
                    <td style="width: 12%;">'.$row['material_type'].'</td>
                    <td style="width: 12%;">'.$row['material_subtype'].'</td>
                    <td style="width: 15%;">'.$row['material_name'].'</td>
                    <td style="width: 8%;">'.$row['grade'].'</td>
                    <td style="width: 11%;">'.$row['material_code'].'</td>
                    <td style="width: 10%;">'.$row['received_qty'].''.$row['unit'].'</td>
                    <td style="width: 10%;">'.$row['issue_qty'].''.$row['unit'].'</td>
                    <td style="width: 10%;">'.$row['balance_qty'].''.$row['unit'].'</td>
                     <td style="width: 7%;">'.$row['unit'].'</td>
                </tr>
                ';
                }
            }
        }
        $html.="</table>";
           
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Material.pdf', 'I');
   
    
}
  else if($_GET["plant_id"] == 28) {//demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
        ';
            //   $sql = "SELECT * FROM material WHERE id='".$_GET["id"]."'";
          $sql = "SELECT m.*,s.status,s.entry_date,c.tax_invoice,s.batch_no,s.mfg_date,s.exp_date,s.grn_no,s.ar_no,p.product_name,s.qty as balance_qty,mi.qty as issue_qty,v.vendor_name,s.vendor_no,v.vendor_no FROM stock_book s LEFT JOIN material m ON s.user_no=m.user_no LEFT JOIN material_issue mi ON s.product_code=mi.product_code LEFT JOIN vendor v on s.vendor_no=v.vendor_no LEFT JOIN product p ON s.product_code=p.product_code JOIN challan c on m.material_type=c.material_type WHERE m.material_type ='Packing Material' AND s.status='Approved'  and m.id='".$_GET["id"]."'";
          
        // $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
        
        

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
                    
                    $html.='<table border="1">
    
                    
                        <tr>
                        <td style="width: 100px;"> Format Title :</td>
                        <td style="width: 440px;"> Raw material stock register</td>
                        </tr>
                       
                        <tr>
                        <td style="width: 100px;"> Format No.:</td>
                        <td style="width: 170px;"> F/SOP/WR/005/02-01</td>
                        <td style="width: 100px;"> Page No.:</td>
                        <td style="width: 170px;"> 1 of 1
                    
                    </td>
                       
                        </tr>
                        <tr>
                        <td style="width: 100px;"> Ref. SOP No.:</td>
                        <td style="width: 440px;"> SOP/WR/005</td>
                        </tr>
                    </table><div></div>
                     <table>
        <tr>
            <td style="width: 320px;">Raw Material Name:- '.$row["material_name"].'  </td>
            <td style="width: 220px;">  Material Code:- '.$row["material_code"].'</td>
        </tr>
    </table>
    <table border="1" >
          <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width: 31.7px;text-align:center;font-size:8px;">Date of Receipt </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Manufacturer </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Supplier </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;">Inv. No & Date </td>
        <td style="width:26.7px;text-align:center;font-size:8px;">Batch No. </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Mfg. Date </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Exp. Date </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Openi-ng Qty </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">GRN No. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Medicap lot no </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Date of Issue </td>
        <td style="width:56.7px;text-align:center;font-size:8px;">Product name </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Batch No </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Issue Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Balance Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Total Quantity </td>

        <td style="width:31.7px;text-align:center;font-size:8px;">Checked by </td>
        </tr>
        <tr>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["entry_date"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["vendor_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["tax_invoice"].' </td>
        <td style="width:26.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["mfg_date"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["exp_date"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["grn_no"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["ar_no"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:56.7px;text-align:center;font-size:8px;"> '.$row["product_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["issue_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["balance_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        </tr>
        </table>
                    
<div></div>
<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>
                    ';
    		    
    	        
    }  
                
           
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
             }
    	    
    	   
    
            
            else if($_GET["plant_id"] == 64){//novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
        ';
            //   $sql = "SELECT * FROM material WHERE id='".$_GET["id"]."'";
          $sql = "SELECT m.*,s.status,s.entry_date,c.tax_invoice,s.batch_no,s.mfg_date,s.exp_date,s.grn_no,s.ar_no,p.product_name,s.qty as balance_qty,mi.qty as issue_qty,v.vendor_name,s.vendor_no,v.vendor_no FROM stock_book s LEFT JOIN material m ON s.user_no=m.user_no LEFT JOIN material_issue mi ON s.product_code=mi.product_code LEFT JOIN vendor v on s.vendor_no=v.vendor_no LEFT JOIN product p ON s.product_code=p.product_code JOIN challan c on m.material_type=c.material_type WHERE m.material_type ='Packing Material' AND s.status='Approved'  and m.id='".$_GET["id"]."'";
          
        // $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
        
        

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
                    
                    $html.='<table border="1">
    
                    
                        <tr>
                        <td style="width: 100px;"> Format Title :</td>
                        <td style="width: 440px;"> Raw material stock register</td>
                        </tr>
                       
                        <tr>
                        <td style="width: 100px;"> Format No.:</td>
                        <td style="width: 170px;"> F/SOP/WR/005/02-01</td>
                        <td style="width: 100px;"> Page No.:</td>
                        <td style="width: 170px;"> 1 of 1
                    
                    </td>
                       
                        </tr>
                        <tr>
                        <td style="width: 100px;"> Ref. SOP No.:</td>
                        <td style="width: 440px;"> SOP/WR/005</td>
                        </tr>
                    </table><div></div>
                     <table>
        <tr>
            <td style="width: 320px;">Raw Material Name:- '.$row["material_name"].'  </td>
            <td style="width: 220px;">  Material Code:- '.$row["material_code"].'</td>
        </tr>
    </table>
    <table border="1" >
          <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width: 31.7px;text-align:center;font-size:8px;">Date of Receipt </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Manufacturer </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Supplier </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;">Inv. No & Date </td>
        <td style="width:26.7px;text-align:center;font-size:8px;">Batch No. </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Mfg. Date </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Exp. Date </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Openi-ng Qty </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">GRN No. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Medicap lot no </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Date of Issue </td>
        <td style="width:56.7px;text-align:center;font-size:8px;">Product name </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Batch No </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Issue Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Balance Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Total Quantity </td>

        <td style="width:31.7px;text-align:center;font-size:8px;">Checked by </td>
        </tr>
        <tr>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["entry_date"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["vendor_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["tax_invoice"].' </td>
        <td style="width:26.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["mfg_date"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["exp_date"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["grn_no"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["ar_no"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:56.7px;text-align:center;font-size:8px;"> '.$row["product_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["issue_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["balance_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        </tr>
        </table>
                    
<div></div>
<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>
                    ';
    		    
    	        
    }  
                
           
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
             }
        
    }

    
    else if ($_GET["type"] == "downloadStock") {
        $_GET['filename'] = "Packing Material Stock Book"; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");

         $html.='
        <h2 style="text-align:cnter">Packing Material Stock Book</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Challan For</td>
                    <td style="width: 10%;">GRN No</td>
                    <td style="width: 15%;">Material Type</td>
                    <td style="width: 10%;">Material code</td>
                    <td style="width: 15%;">Material Name</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Batch No</td>
                    <td style="width: 15%;">Available Qty</td>
                </tr>
            </thead>';
        $i=1;
      	 $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		       $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
            $resQ = $conn->query($q);
          $prodLatest = $resQ->fetch_assoc();
          $row['gradeName'] = $prodLatest['gradeName'];
              $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['required_for'].'</td>
                        <td style="width: 10%;">'.$row['grn_no'].'</td>
                        <td style="width: 15%;">'.$row['material_subtype'].'</td>
                        <td style="width: 10%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;">'.$row['gradeName'].'</td>
                        <td style="width: 10%;text-align:right;">'.$row['batch_no'].'</td>
                        <td style="width: 15%;text-align:right;">'.$row['qty'].'<td style="text-align:left;">'.$row['unit'].'</td></td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('stock.pdf', 'I');
    } 
    else if ($_GET["type"] == "materiallist") {
        $sql = "SELECT * FROM stock_book WHERE user_no='".$_GET["user_no"]."' AND material_code LIKE 'P%' GROUP BY material_code";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["material_name"] = $row1["material_name"];
    		        }
    		    }
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } 
    else if ($_GET["type"] == "ReceivingLog") {
        if($_GET["plant_id"] ==28){
       $_GET['filename'] = 'PACKING MATERIAL ';  $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
             $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name, 
             m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
             LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
             LEFT JOIN material m ON c.material_code=m.material_code
             LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no
             LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
             AND c.receiving !='pending' AND m.material_type ='Packing Material' 
             ORDER BY c.document_no DESC";
              
            //  echo $sql = " SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name,
            //   m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
            //   LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
            //   LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '72' 
            //     AND m.material_subtype LIKE '%%'";
            // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c1.vendor_no='".$_GET["vendor_no"]."' AND  m.material_type ='Packing Material' ";
           
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["receiving_details"] = json_decode($row["receiving_details"]);
                    $output[] = $row;
               $html.= "";
            
            $html.='
            <h3 style="text-align:center;">PACKING MATERIAL RECEIVING </h3>
            <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Name</b></td>
                    <td class="tdb" style="width:75%;"> Styrene divinylbenzene</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Vendor Name</b></td>
                    <td class="tdb" style="width:75%;"> '.$row["vendor_name"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Challan No.:</b></td>
                    <td class="tdbr" style="width:25%;">'.$row['challan_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>challan Date:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["challan_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>PO No.:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['po_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>PO Date.:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["po_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Type:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['material_type'].'</td>
                    <td class="tdb" style="width:25%;"><b>Material Subtype:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["material_subtype"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Code:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['material_code'].'</td>
                    <td class="tdb" style="width:25%;"><b>Material Grade:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["material_grade"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>PO Qty:</b></td>
                    <td colspan="3"> '.$row['qty'].'</td>
                </tr>
            </table>';
            $html.='
            <p style="text-align:center;"><b>Labeling Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Batch No:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['batch_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>Mfg. Date:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["mfg_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Expiry Date:</b></td>
                    <td colspan="3"> '.$row['expiry_date'].'</td>
                 
                </tr>
            </table>
            
           
         
            <br pagebreak="true"/>
                ';
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false,'');
        $pdf->Output('packing.pdf', 'I');
    }
    else if ($_GET["type"] == "ReceivingLog") {
       $_GET['filename'] = 'PACKING MATERIAL ';  $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
               $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name, 
             m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
             LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
             LEFT JOIN material m ON c.material_code=m.material_code
             LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no
             
             LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
             AND c.receiving !='pending' AND m.material_type ='Packing Material' 
              ";
              
            //  echo $sql = " SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name,
            //   m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
            //   LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
            //   LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '72' 
            //     AND m.material_subtype LIKE '%%'";
            // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c1.vendor_no='".$_GET["vendor_no"]."' AND  m.material_type ='Packing Material' ";
           
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["receiving_details"] = json_decode($row["receiving_details"]);
                    $output[] = $row;
                
            
            $html.='
            <h3 style="text-align:center;">PACKING MATERIAL RECEIVING </h3>
            <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Name</b></td>
                    <td class="tdb" style="width:75%;"> Styrene divinylbenzene</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Vendor Name</b></td>
                    <td class="tdb" style="width:75%;"> '.$row["vendor_name"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Challan No.:</b></td>
                    <td class="tdbr" style="width:25%;">'.$row['challan_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>challan Date:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["challan_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>PO No.:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['po_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>PO Date.:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["po_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Type:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['material_type'].'</td>
                    <td class="tdb" style="width:25%;"><b>Material Subtype:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["material_subtype"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Code:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['material_code'].'</td>
                    <td class="tdb" style="width:25%;"><b>Material Grade:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["material_grade"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>PO Qty:</b></td>
                    <td colspan="3"> '.$row['qty'].'</td>
                </tr>
            </table>';
            $html.='
            <p style="text-align:center;"><b>Labeling Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Batch No:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['batch_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>Mfg. Date:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["mfg_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Expiry Date:</b></td>
                    <td colspan="3"> '.$row['expiry_date'].'</td>
                 
                </tr>
            </table>
            <p style="text-align:center;"><b>Received Quantity Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Pack Size:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['pack_size'].'</td>
                    <td class="tdb" style="width:25%;"><b>Challan Qty: kg</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["challan_qty"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Received Qty: kg:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['received_qty'].'</td>
                    <td class="tdb" style="width:25%;"><b>No. of Containers:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["no_of_containers"].'</td>
                </tr>
            </table>
            <p style="text-align:center;"><b>Packing Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:16.66%;"><b>Packing Intactness / Condition:</b></td>
                    <td class="tdbr" style="width:16.66%;"> '.$row['packing_condition'].'</td>
                    <td class="tdb" style="width:16.66%;"><b>Outer Packing: kg</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["outer_packing"].'</td>
                    <td class="tdb" style="width:16.66%;"><b>Container type: kg</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["container_type"].'</td>
                </tr>
            </table>
            <p style="text-align:center;"><b>Transporter’s Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:16.66%;"><b>Transporter’s Details:</b></td>
                    <td class="tdbr" style="width:16.66%;"> '.$row['transport_details'].'</td>
                    <td class="tdb" style="width:16.66%;"><b>COA Received:</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["coa_recived"].'</td>
                    <td class="tdb" style="width:16.66%;"><b>COA:</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["coa"].'</td>
                </tr>
            </table>
            <br pagebreak="true"/>
                ';
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false,'');
        $pdf->Output('packing.pdf', 'I');
    }
    }
    else if ($_GET["type"] == "ReceivingDigitalLog"){
        $_GET['filename'] = 'PACKING MATERIAL RECEIVING LOG';  $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">PACKING MATERIAL RECEIVING LOG</h2>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Name</b></td>
                    <td class="tdb" style="width:75%;"> Styrene divinylbenzene</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Vendor Name</b></td>
                    <td class="tdb" style="width:75%;"> '.$row["vendor_name"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Challan No.:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['challan_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>challan Date,:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["challan_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>PO No.:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['po_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>PO Date.:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["po_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Type:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['material_type'].'</td>
                    <td class="tdb" style="width:25%;"><b>Material Subtype:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["material_subtype"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Material Code:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['material_code'].'</td>
                    <td class="tdb" style="width:25%;"><b>Material Grade:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["material_grade"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>PO Qty:</b></td>
                    <td colspan="3"> '.$row['po_qty'].'</td>
                </tr>
            </table>
                <p style="text-align:center;"><b>Labeling Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Batch No:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['batch_no'].'</td>
                    <td class="tdb" style="width:25%;"><b>Mfg. Date:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["mfg_date"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Expiry Date:</b></td>
                    <td colspan="3"> '.$row['expiry_date'].'</td>
                 
                </tr>
            </table>
                <p style="text-align:center;"><b>Received Quantity Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Pack Size:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['pack_size'].'</td>
                    <td class="tdb" style="width:25%;"><b>Challan Qty: kg</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["challan_qty"].'</td>
                </tr>
                <tr>
                    <td class="tdb" style="width:25%;"><b>Received Qty: kg:</b></td>
                    <td class="tdbr" style="width:25%;"> '.$row['received_qty'].'</td>
                    <td class="tdb" style="width:25%;"><b>No. of Containers:</b></td>
                    <td class="tdb" style="width:25%;"> '.$row["no_of_containers"].'</td>
                </tr>
            </table>
            <p style="text-align:center;"><b>Packing Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:16.66%;"><b>Packing Intactness / Condition:</b></td>
                    <td class="tdbr" style="width:16.66%;"> '.$row['packing_condition'].'</td>
                    <td class="tdb" style="width:16.66%;"><b>Outer Packing: kg</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["outer_packing"].'</td>
                    <td class="tdb" style="width:16.66%;"><b>Container type: kg</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["container_type"].'</td>
                </tr>
            </table>
            <p style="text-align:center;"><b>Transporter’s Details:</b></p>
            <table>
                <tr>
                    <td class="tdb" style="width:16.66%;"><b>Transporter’s Details:</b></td>
                    <td class="tdbr" style="width:16.66%;"> '.$row['transport_details'].'</td>
                    <td class="tdb" style="width:16.66%;"><b>COA Received:</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["coa_recived"].'</td>
                    <td class="tdb" style="width:16.66%;"><b>COA:</b></td>
                    <td class="tdb" style="width:16.66%;"> '.$row["coa"].'</td>
                </tr>
            </table>';
               
                EOD;
                $pdf->writeHTML($html, true, false, false, false,'');
                $pdf->Output('packing.pdf', 'I');
    }
    else if ($_GET["type"] == "getAvailableMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_type='Packing Material' AND material_subtype='".$_GET["material_type"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["material_code"]."' AND status='Approved' HAVING SUM(qty) > 0";
    		    $result1 = $conn->query($sql1);
    		    while ($row1 = $result1->fetch_assoc()) {
		            $output1 = Array();
		            $sql2 = "SELECT * FROM stock_book WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["material_code"]."' AND status='Approved'";
		            $result2 = $conn->query($sql2);
		            if ($result2->num_rows > 0) {
		                while ($row2 = $result2->fetch_assoc()) {
		                    $output1[] = $row2;
		                }
		            }
		            $row["batches"] = $output1;
		            $output[] = $row;
		        }
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "getRetestsLog") {
        $output = array();
          $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name FROM retest r LEFT JOIN material m ON r.material_code=m.material_code LEFT JOIN vendor v ON r.vendor_no=v.vendor_no WHERE r.user_no='".$_GET["user_no"]."' AND r.vendor_no LIKE '%".$_GET["vendor_no"]."' ";
        // $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name FROM retest r LEFT JOIN material m ON r.material_code=m.material_code LEFT JOIN vendor v ON r.vendor_no=v.vendor_no WHERE r.user_no='".$_GET["user_no"]."' AND r.vendor_no LIKE '%".$_GET["vendor_no"]."' AND DATE(r.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "downloadDedustingsLog"){
        $_GET['filename'] = 'Dedustings LOG';  $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='';
       
        $html.='
        <h2 style="text-align:center">Dedustings LOG</h2>
        <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Sr No</td>
                        <td style="width:15%;">Material Code</td>
                        <td style="width:15%;">Material Name</td>
                        <td style="width:15%;">Vendor Name</td>
                        <td style="width:15%;">Grade</td>
                        <td style="width:15%;">Containers</td>
                        <td style="width:15%;">Status</td>
                    </tr> ';
                     $i=1;
                $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.dedusting !='pending' AND m.material_type ='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row["receiving_details"] = json_decode($row["receiving_details"]);
                        $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                        $output[] = $row;
            $html.='<tr nobr="true">
                        <td style="width:10%;">'.$i.'</td>
                        <td style="width:15%;">'.$row['material_code'].'</td>
                        <td style="width:15%;">'.$row['material_name'].'</td>
                        <td style="width:15%;">'.$row['vendor_name'].'</td>
                        <td style="width:15%;">'.$row['grade'].'</td>
                        <td style="width:15%;">'.$row['containers'].'</td>
                        <td style="width:15%;">'.$row['status'].'</td>
                    </tr>';
                      $i++;
                    }
                }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false,'');
        $pdf->Output('Dedustings Log.pdf', 'I');
    }
    else if ($_GET['type'] == 'downloadPurchaseIndendsLog'){
     
         $_GET['filename'] = 'Purchase LOG';  $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="style="text-align:center">Purchase LOG</h2>
        <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width: 9%; text-align:centre;"><b>Date</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Indend No</b></td>
                    <td style="width: 12%; text-align:centre;"><b>Department</b></td>
                    <td style="width: 8%; text-align:centre;"><b>Vendor No</b></td>
                    <td style="width: 9%; text-align:centre;"><b>Material Type</b></td>
                    <td style="width: 9%; text-align:centre;"><b>Material code</b></td>
                    <td style="width: 9%; text-align:centre;"><b>Material Name</b></td>
                    <td style="width: 7%; text-align:centre;"><b>Req. qty</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Required For</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Requirement</b></td>
                </tr>
            </thead>';
             $i=1;
       $sql = "SELECT i.*, v.vendor_name, v.gst_type, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.expected_vendor=v.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."'  GROUP BY i.no";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
           
            while($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width: 5%;">'.$i.'.</td>
                            <td style="width: 9%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                            <td style="width: 10%;">'.$row['indend_no'].'</td>
                            <td style="width: 12%;">'.$row['department'].'</td>
                            <td style="width: 8%;">'.$row['expected_vendor'].'</td>
                            <td style="width: 9%;">'.$row['material_type'].'</td>
                            <td style="width: 9%;">'.$row['material_code'].'</td>
                            <td style="width: 9%;">'.$row['material_name'].'</td>
                            <td style="width: 7%;">'.$row['req_qty'].'</td>
                            <td style="width: 10%;">'.$row['required_for'].'</td>
                            <td style="width: 10%;">'.$row['requirement'].'</td>
                            
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Purches material', 'I');
    
       }else if ($_GET['type'] == "downloadWeighingMaterialsLog"){
        
          $_GET['filename'] = 'Weighing Materials LOG';  $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="text-align:center">Weighing Materials LOG</h2>
        <table cellpadding="5">
          
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:centre;"><b>Sr.</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Material Name</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Material Type</b></td>
                    <td style="width: 15%; text-align:centre;"><b>Vendor Name</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Batch No.</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Accepted Qty</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Containers</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Mfg. Date</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Exp. Date</b></td>
                    <td style="width: 10%; text-align:centre;"><b>Status</b></td>
                 </tr>';
                 $i=1;
            //     $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,
            //  m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c
            //  LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON 
            //  c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
            //  WHERE c.user_no='".$_GET["user_no"]."' AND c.weighing !='pending' AND 
            //  m.material_type ='Packing Material'  ";

  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name,
        m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.weighing='approve' AND m.material_type ='Packing Material' order by c.id DESC ";
         $result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
			    
			    
			      $fullRangeCalibrationJson = $row['weight'];
$fullRangeCalibration = json_decode($fullRangeCalibrationJson, true);


            $html.='<tr>
                            <td style="width: 5%;">'.$i.'.</td>
                            <td style="width: 10%;">'.$row['material_name'].'</td>
                            <td style="width: 10%;">'.$row['material_subtype'].'</td>
                            <td style="width: 15%;">'.$row['vendor_name'].'</td>
                            <td style="width: 10%;">'.$row['batches'].'</td>
                            <td style="width: 10%;">'.$row['accept_qty'].'</td>
                            <td style="width: 10%;">'.$row['container_no'].'</td>
                            <td style="width: 10%;">'.date('d-m-y',strtotime($row['mfg_date'])).'</td>
                            <td style="width: 10%;">'.$row['exp_date'].'</td>
                            <td style="width: 10%;">'.$row['weighing'].'</td>
                    </tr>';
                    
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Equipment.pdf', 'I');
    
    }else if($_GET['type'] == 'downloadReceivingsLog'){
        
     if($_GET["plant_id"] == 59){ //amardeep
         
         $_GET['filename'] = 'Receiving Of Packing Material Log'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Receiving Of Packing Material Log</h2>
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 10%;">PO No</td>
                    <td style="width: 20%;">Po Date</td>
                    <td style="width: 20%;">Challan no</td>
                    <td style="width: 20%;">Challan Date</td>
                    <td style="width: 10%;">Status</td>
                </tr>
            </thead>
            <tbody>';
              $counter = 1;
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE  c.receiving != 'pending' AND m.material_type ='Packing Material'AND v.vendor_name LIKE '%".$_GET["vendor_name"]."%' AND DATE(c.inward_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            //$sql=" SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving='Approved' AND m.material_type ='Packing Material' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' GROUP BY c.challan_no";
            //$sql = "SELECT c.*,c1.entry_by,c1.approve_by,c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND c.status LIKE '%".$_GET["status"]."%'AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id ORDER BY c.id DESC";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr nobr="true">
                        <td style="width: 10%;">'.$counter++.'</td>
                        <td style="width: 10%;">'.$row['vendor_name'].'</td>
                        <td style="width: 10%;">'.$row['po_no'].'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
                        <td style="width: 20%;">'.$row['challan_no'].'</td>
                        <td style="width:20%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                    </tr>';
                   $counter++;
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');}
                                 else if($_GET["plant_id"] == 142){//demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table>
         <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;">Packing Material Inward Register</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;">F/SOP/WR/002/03-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;">1 of 1

</td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/002</td>
    </tr>
    </table>
        <h2 style="text-align:center">Receiving of Material Log</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                      <td style="width: 28.42px;text-align:center;font-size:6px;">Sr. No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">G.R. N. No & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Code</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Suppli-er Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg.  Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Inv. No. & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Qty</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Pack Size</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Rate</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Net Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">GST</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Freight</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Total Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Batch No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Exp. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:5px;">P.O. NO. & Date</td>
                </tr><tbody>';
              
//  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name, 
//              m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
//              LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
//              LEFT JOIN material m ON c.material_code=m.material_code
//              LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no
             
//              LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
//              AND c.receiving !='pending' AND m.material_type ='Packing Material'  
              
//              ORDER BY c.document_no DESC";   
  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name, 
             m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
             LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
             LEFT JOIN material m ON c.material_code=m.material_code
             LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no
             
             LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
             AND c.receiving !='pending' AND m.material_type ='Packing Material' 
             AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' 
             AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
              
             ORDER BY c.document_no DESC";
             $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr nobr="true">
                <td style="width: 28.42px;font-size:6px;">'.$counter++.'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-y',strtotime($row['received_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row['grn_no'].'<br>'.date('d/m/Y',strtotime($row["grn_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_code"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_name"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row["tax_invoice"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["received_qty"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["pack_size"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["rate"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["net_total"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["gst"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row['batches'].'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d/m/Y',strtotime($row['po_date'])).'<br>'.$row['po_no'].'</td>
                    </tr>';
    		    }
    	    }
    	    
    	    $html.='</table>';
    	    $html.='<br><table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }

             else if($_GET["plant_id"] == 28){//demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table>
         <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;">Packing Material Inward Register</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;">F/SOP/WR/002/03-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;">1 of 1

</td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/002</td>
    </tr>
    </table>
        <h2 style="text-align:center">Receiving of Material Log</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                      <td style="width: 28.42px;text-align:center;font-size:6px;">Sr. No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">G.R. N. No & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Code</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Suppli-er Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg.  Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Inv. No. & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Qty</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Pack Size</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Rate</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Net Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">GST</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Freight</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Total Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Batch No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Exp. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:5px;">P.O. NO. & Date</td>
                </tr><tbody>';
              
//  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name, 
//              m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
//              LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
//              LEFT JOIN material m ON c.material_code=m.material_code
//              LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no
             
//              LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
//              AND c.receiving !='pending' AND m.material_type ='Packing Material'  
              
//              ORDER BY c.document_no DESC";   
  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_type, v.vendor_name,v2.vendor_name as manufacturer_name, 
             m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c 
             LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
             LEFT JOIN material m ON c.material_code=m.material_code
             LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no
             
             LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
             AND c.receiving !='pending' AND m.material_type ='Packing Material' 
             AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' 
             AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
              
             ORDER BY c.document_no DESC";
             $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr nobr="true">
                <td style="width: 28.42px;font-size:6px;">'.$counter++.'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-y',strtotime($row['received_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row['grn_no'].'<br>'.date('d/m/Y',strtotime($row["grn_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_code"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_name"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row["tax_invoice"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["received_qty"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["pack_size"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["rate"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["net_total"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["gst"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row['batches'].'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d/m/Y',strtotime($row['po_date'])).'<br>'.$row['po_no'].'</td>
                    </tr>';
    		    }
    	    }
    	    
    	    $html.='</table>';
    	    $html.='<br><table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
            else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table>
         <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;">Packing Material Inward Register</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;">F/SOP/WR/002/03-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;">1 of 1</td>
    </tr>
    
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/002</td>
    </tr>
    </table>
        <h2 style="text-align:center">Receiving of Material Log</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
            <td style="width: 28.42px;text-align:center;font-size:6px;">Sr. No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">G.R. N. No & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Code</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Suppli-er Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg.  Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Inv. No. & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Qty</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Pack Size</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Rate</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Net Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">GST</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Freight</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Total Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Batch No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Exp. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:5px;">P.O. NO. & Date</td>
                </tr><tbody>';
              
           $sql = "SELECT c.*,c1.entry_by,c1.approve_by,c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade,c1.tax_invoice,c1.net_total,c.batches,c.receiving
           FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE m.material_type ='Packing Material' AND c.receiving != 'pending' order by id desc";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr nobr="true">
                <td style="width: 28.42px;font-size:6px;">'.$counter++.'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-y',strtotime($row['received_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row['grn_no'].'<br>'.date('d/m/Y',strtotime($row["grn_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_code"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_name"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row["tax_invoice"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["received_qty"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["pack_size"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["rate"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["net_total"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["gst"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row['batches'].'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d/m/Y',strtotime($row['po_date'])).'<br>'.$row['po_no'].'</td>
                    </tr>';
    		    }
    	    }
    	    
    	    $html.='</table>';
    	    $html.='<br><table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
			}
			else if($_GET['type'] == 'downloadStockBookLog') {
        $_GET['filename'] = 'Packing Material Approved Stock Book'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Packing Material Approved Stock Book</h2>
        <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:7%; text-align:centre;"><b>Challan For</b></td>
                <td style="width:7%; text-align:centre;"><b>GRN No.</b></td>
                <td style="width:5%; text-align:centre;"><b>Medicap lot no</b></td>
                <td style="width:8%; text-align:centre;"><b>Vendor Name</b></td>
                <td style="width:8%; text-align:centre;"><b>Material Type</b></td>
                <td style="width:8%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:8%; text-align:centre;"><b>Material Name</b></td>
                <td style="width:5%; text-align:centre;"><b>Grade</b></td>
                <td style="width:7%; text-align:centre;"><b>Batch No.</b></td>
                <td style="width:8%; text-align:centre;"><b>Sampling Date</b></td>
                <td style="width:8%; text-align:centre;"><b>Release Date</b></td>
                <td style="width:8%; text-align:centre;"><b>Received Qty</b></td>
                <td style="width:8%; text-align:centre;"><b>Balance Qty</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                <td style="width:5%;">'.$i.'.</td>
                <td style="width:7%;">'.$row['required_for'].'</td>
                <td style="width:7%;">'.$row['grn_no'].'</td>
                <td style="width:5%;">'.$row['ar_no'].'</td>
                <td style="width:8%;">'.$row['vendor_name'].'</td>
                <td style="width:8%;">'.$row['material_subtype'].'</td>
                <td style="width:8%;">'.$row['material_type'].'</td>
                <td style="width:8%;">'.$row['material_name'].'</td>
                <td style="width:5%;">'.$row['grade'].'</td>
                <td style="width:7%;">'.$row['batch_no'].'</td>
                <td style="width:8%;">'.$row['sample_date'].'</td>
                <td style="width:8%;">'.$row['release_date'].'</td>
                <td style="width:8%;">'.$row['qty'].'</td>
                <td style="width:8%;">'.$row['qty'].'</td>
            </tr>';
            $i++;
    		}
    	}
        $html.='</table>';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Approved Stock Book.pdf','I');
    }
    else if($_GET["type"] == "downloadGRN")
    {
        
          if($_GET["plant_id"] == 96) {//Olive
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        // $sql="SELECT a.*, b.material_name 
        // FROM challan_materials a 
        // LEFT JOIN material b 
        // ON a.material_code = b.material_code  
        // ORDER BY `a`.`receiving_details` ASC";
       
  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no,m.material_name,
                v.vendor_name FROM challan_materials c 
                LEFT JOIN challan c1 ON c.challan_no=c1.challan_no
                LEFT JOIN material m ON c.material_code=m.material_code 
                LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
                WHERE c.id='".$_GET["chId"]."' AND m.material_type ='Packing Material' 
                AND c.receiving != 'pending' AND c.grn='approve' ORDER BY c.challan_no";
        
         $html= "";
         	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		      $html.='
    <table border="1" style="width: 785px;">
        <tr>
        <td style="line-height:20px;width: 185px;text-align:left;">Name of Material: 
        '.$row['material_name'].'
        </td>
        <td style="line-height:20px;width: 100px;text-align:center;">P.O.No. & Date</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Challan No. & Date</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Invoice No. & Date</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Transporter Name</td>
        <td style="line-height:20px;width: 80px;text-align:center;">L.R.No. & Date</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Packaging Type</td>
        <td style="line-height:20px;width: 100px;text-align:center;">Pack Configuration(Number * kg)</td>
        </tr>
        
        <tr>
        <td style="line-height:20px;width: 185px;text-align:left;">Item Code:</td>
        <td>'.$row['receiving_date'].'</td>
        <td>'.$row['challan_no'].'</td>
        <td>'.$row['invoice_date'].'</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        
        </tr>
        
        <tr rowspan="2">
        <td style="line-height:20px;width: 185px;text-align:left;">Name & Address of Supplier:</td>
        <td style="line-height:20px;width: 40px;text-align:center;">UOM</td>
        <td style="line-height:20px;width: 60px;text-align:center;">Batch NO.</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Mfg. Date</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Exp./Retest Date</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Total Qty. as per Challan</td>
        <td style="line-height:20px;width: 80px;text-align:center;">Actual Qty.Received</td>
        <td style="line-height:20px;width: 180px;text-align:center;">Remark (If Any)</td>
        </tr>
        <tr style="line-height:20px;width: 250px;text-align:left;" rowspan="2">
        <td>Name & address of Manufacturer:</td>
        <td></td>
        <td>'.$row['batch_no'].'</td>
        <td>'.$row['dedusting_date'].'</td>
        <td>'.$row['grncheck-date'].'</td>
        <td>'.$row['qty'].'</td>
        <td>'.$row['received_qty'].'</td>
        <td>'.$row['grn_remark'].'</td>
        </tr>
        <tr>
        <td style="line-height:20px;width: 100px;text-align:left;">Verify & Tick(✓)</td>
        <td style="line-height:20px;width: 265px;text-align:left;">Approved/Probationary vendor(If Probationary Vendor. Consignment No. 1/2/2-tick applicable)</td>
        <td style="line-height:20px;width: 240px;text-align:left;">▢ Entry in Material inward</td>
        <td style="line-height:20px;width: 180px;text-align:left;">▢ Entry in Bin Card</td>
        </tr>
        <tr>
        <td style="line-height:20px;width: 250px;text-align:left;">Prepared By Sign/Date (WH):</td>
        <td style="line-height:20px;width: 250px;text-align:left;">Checked By Sign/Date (Manager-WH):</td>
        <td style="line-height:20px;width: 285px;text-align:left;">GRN Received by Sign/Date(QC):</td>
        </tr>
        <tr>
        <td style="line-height:20px;width: 250px;text-align:left;"></td>
        <td style="line-height:20px;width: 250px;text-align:left;"></td>
        <td style="line-height:20px;width: 285px;text-align:left;"></td>
        </tr>
        <tr style="width: 785px; line-height:20px">
        <td style="width: 785px;">Below field are to be filled by QC Department</td>
        </tr>
        <tr style="width: 785px; line-height:20px">
        <td style="width: 392px;">Material Disposition:APROVED / REJECTED:</td>
        <td style="width: 393px;">A.R.No.</td>
        </tr>
        <tr style="width: 785px; line-height:20px">
        <td style="width: 785px;">Reason For Rejection(If Rejected):</td>
        </tr>
        <tr>
        <td style="line-height:20px;width: 265px;text-align:left;">Release Date:</td>
        <td style="line-height:20px;width: 170px;text-align:left;">Sign/Date:(Analyst)</td>
        <td style="line-height:20px;width: 170px;text-align:left;">GRN Released by Sign/Date:</td>
        <td style="line-height:20px;width: 180px;text-align:left;">Approved/Rejected Qty:</td>
        </tr>
        <tr>
        <td style="line-height:20px;width: 265px;text-align:left;">#Revolution Date:</td>
        <td style="line-height:20px;width: 170px;text-align:left;"></td>
        <td style="line-height:20px;width: 170px;text-align:left;"></td>
        <td style="line-height:20px;width: 180px;text-align:left;"></td>
        </tr>
        </table>
        <div></div>
        <table style="width: 100%" border="1">
    <tr style="text-align:center; font-weight: bold; ">
        <th></th>
        <th>Prepared By-WH</th>
        <th>Reviewed BY-WH</th>
        <th>Reviewed By-QA</th>
        <th>Approved by</th>
        <th>Authorized By</th>
    </tr>
    <tr style="text-align:center; height: 30px;" >
        <th style="font-weight: bold;">Signature</th>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="text-align:center;">
        <th style="font-weight: bold;">Date</th>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>';
    		    
    		}
    	    
    	}
 
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
        
        $_GET['filename'] = 'Goods Receipt Notes Log'; $_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
	$sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name ,cm.challan_no FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no LEFT JOIN challan_materials cm ON s.material_code=cm.material_code
      	WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";        $result = $conn->query($sql);
       if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
               //$sql = "SELECT c.*,c1.entry_by,c1.approve_by, c1.challan_date, c1.inword_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,m.material_name, m.grade, m.material_subtype as material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' AND c.id='".$_GET["id"]."'";
    //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.grn !='pending' AND m.material_type='Raw Material' AND c.id='".$_GET["id"]."' GROUP BY c.id";
     
                
                // $row["receiving_details"] = json_decode($row["receiving_details"]);
                // $row["weighing_details"] = json_decode($row["weighing_details"]);
                // $row["grn_details"] = json_decode($row["grn_details"]);
                $html.='
<table style="line-height:30px;width: 540px;border:none;">
    <tr>
        <td style="line-height:30px;width: 540px;border:none;text-align:center;font-weight:bold;"> GOODS RECEIPT NOTE</td>
    </tr>
</table>
<div><div>
<table border="1">
    <tr>
        <td style="line-height:20px;width: 270px;"> GRN No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $row['grn_no'] . '</td>
        <td style="line-height:20px;width: 270px;"> GRN Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $row['grn_date'] . '</td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 270px;"> Name of Manufacture : '.$row["manufacturer"].'</td>
        <td style="line-height:20px;width: 270px;"> Date of Receipt &nbsp;&nbsp;&nbsp;: ' . $row['grn_date'] . '</td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 270px;"> Name of Supplier &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $row['vendor_name'] . '</td>
        <td style="line-height:20px;width: 270px;"> Gate inward No &nbsp;&nbsp;&nbsp;: ' . $row['inward_no'] . '</td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 270px;"> Challan Number &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $row['challan_no'] . '</td>
        <td style="line-height:20px;width: 270px;"> Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $row['grn_date'] . ' </td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 540px;"> P.O Number &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $row['po_no'] . '</td>
    </tr>
</table>
<div></div>
<table border="1">
    <tr>
        <td style="text-align:center;font-size:10px;width:77.14px;"><div></div>Item code</td>
        <td style="text-align:center;font-size:10px;width:115.71px;"><div></div>Material Name</td>
        <td style="text-align:center;font-size:10px;width:45px;"><div></div>Unit</td>
        <td style="text-align:center;font-size:10px;width:38.57px;">Mfg <br>Batch No.</td>
        <td style="text-align:center;font-size:10px;width:67.14px;">No. of <br>Containers</td>
        <td style="text-align:center;font-size:10px;width:38.57px;">Order Qty.</td>
        <td style="text-align:center;font-size:9px;width:38.57px;">Received Qty.</td>
        <td style="text-align:center;font-size:10px;width:38.57px;">Short Receipt Qty.</td>
        <td style="text-align:center;font-size:9px;width:40.57px;">Accepted Qty.</td>
        <td style="text-align:center;font-size:10px;width:38.57px;">Challan Qty.</td>
    </tr>
    
   
    
    
    <tr>
    <td style="line-height:25px;text-align:center;font-size:10px;width:77.14px;" rowspan="2"><br>  ' . $row['material_code'] . '</td>
    <td style="text-align:center;font-size:10px;width:115.71px;" rowspan="2"><br> ' . $row['material_name'] . '</td>
    <td style="line-height:25px;text-align:center;font-size:10px;width:45px;" >' . $row['unit'] . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:38.57px;" >' .  $batch_no . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:67.14px;" >' . $row['containers'] . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:38.57px;" >' . $row['ordered_qty'] . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:38.57px;" >' . $row['received_qty'] . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:38.57px;" >' . $row['short_qty'] . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:40.57px;" >' . $row['received_qty'] . '</td>
    <td style="line-height:15px;text-align:center;font-size:10px;width:38.57px;" >' . $row['challan_qty'] . '</td>
    </tr>
    <tr>
    <td style="text-align:center;font-size:10px;width:83.57px;" > Manufacturing Date</td>
    <td style="text-align:center;font-size:10px;width:105.71px;" >' . $mfg_date . '</td>
    <td style="text-align:center;font-size:10px;width:77.14px;" >Expiry Date</td>
    <td style="text-align:center;font-size:10px;width:79.14px;" >' . $exp_date . '</td>
    </tr>
     
      </table>
            <div></div>   
      <table style="line-height:30px;width: 540px;border:none;">
    <tr>
        <td style="line-height:30px;width: 540px;border:none;text-align:left;font-weight:bold;">Analytical Details</td>
    </tr>
    </table>
    <div></div>   
    <table border="1">
    <tr>
        <td style="text-align:center;font-size:9px;width:90px;">Mfg. Batch No</td>
        <td style="text-align:center;font-size:9px;width:50px;">No. of
        Containers</td>
        <td style="text-align:center;font-size:9px;width:38px;">Unit</td>
        <td style="text-align:center;font-size:9px;width:66px;">Received
        Qty.</td>
        <td style="text-align:center;font-size:9px;width:43px;">Rejected Qty</td>
        <td style="text-align:center;font-size:9px;width:43px;">Accepted
        Qty. </td>
        <td style="text-align:center;font-size:9px;width:38px;">A.R. No.</td>
        <td style="text-align:center;font-size:9px;width:76px;">Statue Approved /
        Rejected</td>
        <td style="text-align:center;font-size:9px;width:38px;">Date</td>
        <td style="text-align:center;font-size:9px;width:58px;">Remark
        if any</td>
    </tr>
    <tr>
        <td style="line-height:15px;text-align:center;font-size:9px;width:90px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:50px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:38px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:66px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:43px;"> </td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:43px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:38px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:76px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:38px;"></td>
        <td style="line-height:15px;text-align:center;font-size:9px;width:58px;"></td>
    </tr>

</table>
      <div></div>
<table border="1">
    <tr>
        <td style="width: 135px;"></td>
        <td style="width: 135px;text-align:center;"> Prepared By </td>
        <td style="width: 135px;text-align:center;"> Checked By</td>
        <td style="width: 135px;text-align:center;"> Approved By</td>
    </tr>
    <tr>
        <td style="width: 135px;"> Name</td>
        <td style="width: 135px;text-align:center;">  </td>
        <td style="width: 135px;text-align:center;"> </td>
        <td style="width: 135px;text-align:center;"> </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Designation</td>
        <td style="width: 135px;text-align:center;">  </td>
        <td style="width: 135px;text-align:center;"> </td>
        <td style="width: 135px;text-align:center;"> </td>
    </tr>
    <tr>
        <td style="width: 135px;"> Signature/ Date</td>
        <td style="width: 135px;text-align:center;">  </td>
        <td style="width: 135px;text-align:center;"> </td>
        <td style="width: 135px;text-align:center;"> </td>
    </tr>
</table>';
     
          
                  $pdf->writeHTML($html, true, false, false, false, '');
                 $pdf->Output('','I');
             
            }
       }

    }
    
    
    
    
    
    
    else if($_GET['type'] == 'downloaddedustingofmaterial'){
        $_GET['filename'] = 'Dedusting of Material'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:cenetr">Dedusting of Material</h2>
        <table cellpadding="5" >
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Receiving No.</b></td>
            <td style="width:10%; text-align:centre;"><b>Receiving Date</b></td>
            <td style="width:10%; text-align:centre;"><b>Material Code</b></td>
            <td style="width:15%; text-align:centre;"><b>Material Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Material Type</b></td>
            <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Grade</b></td>
            <td style="width:10%; text-align:centre;"><b>Containers</b></td>
        </tr>';
        $i=1;
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.dedusting ='approve' AND m.material_type ='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:10%;">'.$row['receiving_no'].'</td>
            <td style="width:10%;">'.$row['receiving_date'].'</td>
            <td style="width:10%;">'.$row['material_code'].'</td>
            <td style="width:15%;">'.$row['material_name'].'</td>
            <td style="width:10%;">'.$row['material_type'].'</td>
            <td style="width:15%;">'.$row['vendor_name'].'</td>
            <td style="width:10%;">'.$row['grade'].'</td>
            <td style="width:10%;">'.$row['containers'].'</td>
        </tr>';
        $i++;
            }
        }
        $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dedusting of  Material.pdf','I');
    }
}

$conn->close();
?>