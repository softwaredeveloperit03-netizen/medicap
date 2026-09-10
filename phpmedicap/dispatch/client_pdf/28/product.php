<?php 
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php';
header('response_token: test123456');
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
      if ($_GET["type"] == "saveProduct_old") {
          
           $sql1 = "SELECT IFNULL(COUNT(id), 0) as id FROM product ORDER BY id DESC LIMIT 1";
              $result1 = $conn->query($sql1);
             $row1 = $result1->fetch_assoc();
              $id=$row1["id"]+1; 
          
        $sql = "INSERT INTO product (plant_id,product_type,product_code,product_name,grade,product_apperance,manufactured_under,
        manufactured_for,micro,storage_condition,entry_by,entry_date,type,product_nature) 
        VALUES ('".$input["plant_id"]."','".$input["product_type"]."','".$input["product_code"].'-'.$id."','".$input["product_name"]."',
        '".$input["grade"]."','".$input["product_apperance"]."','".$input["manufactured_under"]."','".$input["manufactured_for"]."',
        '".$input["micro"]."','".$input["storage_condition"]."','".$_GET["emp_id"]."','$entry_date','".$input["type"]."',
        '".$input["material_nature"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "saveProduct") {
        try{
            $input = $_POST;        
            $data =  json_encode($input[data]);
          $data = json_decode($input[data], true);
          
           $prod =$data["product_type"];
           
           $success = false;
           $id=1;
           $sql1 = "SELECT  COUNT(id)+1 as id FROM product wher product_type='".$prod."'";
          
           $result = $conn->query($sql);
           while($row = $result->fetch_assoc()){
                  $id = $row['id'];
           }
           if($id==0){
            $id=1;
           } 
           $length = 4;
           $structure_file;
           $msds_file;
           $prod_license_file;
           $who_copp_file;
           $ce_certificate_file;
           $number = substr(str_repeat(0, $length).$id, - $length);
           $target_dir = "../upload/product/";
           if(isset($_FILES["structure_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["structure_file"]["name"]);
            	$structure_file = $id."_".basename($_FILES["structure_file"]["name"]);
        	    move_uploaded_file($_FILES["structure_file"]["tmp_name"], $target_file);
        	    echo $structure_file;
           }
           if(isset($_FILES["msds_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["msds_file"]["name"]);
            	$msds_file = $id."_".basename($_FILES["msds_file"]["name"]);
        	    move_uploaded_file($_FILES["msds_file"]["tmp_name"], $target_file);
        	      echo $msds_file;
           }
           if(isset($_FILES["prod_license_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["prod_license_file"]["name"]);
            	$prod_license_file = $id."_".basename($_FILES["prod_license_file"]["name"]);
        	    move_uploaded_file($_FILES["prod_license_file"]["tmp_name"], $target_file);
        	     echo $prod_license_file;
           }
           if(isset($_FILES["who_copp_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["who_copp_file"]["name"]);
            	$who_copp_file = $id."_".basename($_FILES["who_copp_file"]["name"]);
        	    move_uploaded_file($_FILES["who_copp_file"]["tmp_name"], $target_file);
        	       echo $who_copp_file;
           }
           if(isset($_FILES["ce_certificate_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["ce_certificate_file"]["name"]);
            	$ce_certificate_file = $id."_".basename($_FILES["ce_certificate_file"]["name"]);
        	    move_uploaded_file($_FILES["ce_certificate_file"]["tmp_name"], $target_file);
        	     echo $ce_certificate_file;
           }
           
           
           $sql = "INSERT INTO product (plant_id,product_type,product_code,product_name,grade,product_apperance,manufactured_under,manufactured_for,
                                        micro,storage_condition,entry_by,entry_date,type,product_nature,pack_sizes,mrp_list,selling_price_list) VALUES 
                                        ('".$_GET["plant_id"]."','".$data["product_type"]."',
                                        '".$data["product_type"].$number."','".$data["product_name"]."','".$data["grade"]."','".$data["product_apperance"]."',
                                        '".$data["manufactured_under"]."','".$data["manufactured_for"]."','".$data["micro"]."','".$data["storage_condition"]."',
                                        '".$_GET["emp_id"]."','$entry_date','".$data["market_type"]."','".$data["product_nature"]."',
                                        '".json_encode($data["pack_sizes"])."','".json_encode($data["mrp_list"])."','".json_encode($data["selling_price_list"])."')";
         
            if($conn->query($sql)){
                    $product_id = $conn->insert_id;                                         
                   // echo $product_id ;  
                    $sql = "INSERT INTO product_other_information_api(product_id,product_code,cas_number,structure_file_path,molecular_weight,molecular_formula,storage_condition,safety_instructions,
                                                product_apperance,other_description,ce_number,color_index,msds_file_path,product_license_path,who_copp_path,
                                                ce_certificate_path,qc_lead_time,pka_value,market_type,storage_location,material_apperance) 
                                                VALUES ('".$product_id."','".$data["product_type"].$number."','".$data["cas_number"]."','".$structure_file."',
                                                '".$data["molecular_weight"]."','".$data["molecular_formula"]."','".$data["storage_condition"]."',
                                                '".$data["safety_instructions"]."','".$data["product_apperance"]."','".$data["other_description"]."','".$data["ce_number"]."',
                                                '".$data["color_index"]."','".$msds_file."','".$prod_license_file."','".$who_copp_file."',
                                                '".$ce_certificate_file."','".$data["qc_lead_time"]."','".$data["pka_value"]."','".$data["market_type"]."',
                                                '".$data["storage_location"]."','".$data["material_apperance"]."')";
                   
                   if($conn->query($sql)){
                       $success = true;
                   }else{
                       echo "{\"status\":\"".$conn->error."\"}";
                       $sql ="Delete from product where id= '".$product_id."'";
                       $conn->query($sql);
                       
                   }
                   if($success){
                       	echo "{\"status\":\"success\"}";
                   }else{
                       echo "{\"status\":\"".$conn->error."\"}";
                   }
            
          
            }else{
                echo "{\"status4\":\"".$conn->error."\"}";
            }
                                        
     
        } catch (\Throwable $e) {
             //  echo "{\"statuse\":\"".$e."\"}";
             	echo "{\"status\":\"exception\"}";
            }
    }
    else if ($_GET["type"] == "getProductsLog") {
        $output = Array();
            $sql = "SELECT p.*,c.company FROM product p LEFT JOIN client c ON p.manufactured_for=c.client_code WHERE p.status='approve'";
          $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveGrades") {
        $sql = "INSERT INTO grade (grade,entry_by,entry_date) VALUES ('".$input["grade"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if ($_GET["type"] == "getGrades") {
        $output = array();
        $sql = "SELECT * FROM grade WHERE status='active' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveGenericProduct") {
        $input = $_POST;
        
        $id = date("YmdHis", $timestamp);
        
        $product_lic = "";
        $fsc = "";
        $copp = "";
        $artwork = "";
        if(isset($_FILES['product_lic'])) {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $id."product_lic.".$file_ext;
            $product_lic = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        $sql = "INSERT INTO generic_product (user_no,product_code, grade,tshape,tsize, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, sale_ts, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, cess, category, mfg_lic, pack_desc, apperance, storage_condition, mrp, other_expn, invt_depo_Ref, cylinder_cost, packing_design, permission_cost, status) VALUES
        ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["grade"]."','".$input["tshape"]."','".$input["tsize"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["sale_ts"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["cess"]."', '".$input["category"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["mrp"]."', '".$input["other_expn"]."', '".$input["invt_depo_Ref"]."', '".$input["cylinder_cost"]."', '".$input["packing_design"]."', '".$input["permission_cost"]."', 'approve')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getGenericProductsLog") {
        $output = Array();
        $sql = "SELECT * FROM generic_product WHERE user_no='".$_GET["user_no"]."' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%' AND status LIKE '%".$_GET["status"]."%' AND generic_name LIKE '%".$_GET["generic_name"]."%' ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getProductsByDosageForm") {
          $output = Array();
            $sql = "SELECT *  FROM product WHERE  dosage_form='".$_GET["product_type"]."' 
            AND  plant_id='".$_GET["plant_id"]."' order by product_name";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["mrp"] = $row1["mrp"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
    }
    else if ($_GET["type"] == "get_dosage_types") {
        $output = Array();
        //$sql = "SELECT distinct dosage_form FROM product WHERE plant_id = '".$_GET["plant_id"]."' ORDER BY dosage_form";
        $sql = "Select DISTINCT dosage_form_type as dosage_form from master_fg_types WHERE plant_id = '".$_GET["plant_id"]."' ORDER BY dosage_form_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedGenericProducts") {
        if (!isset($_GET["grade"])) {
            $_GET["grade"] = "";
        }
        if (!isset($_GET["dosage_type"])) {
            $_GET["dosage_type"] = "";
        }
        if (!isset($_GET["dosage_form"])) {
            $_GET["dosage_form"] = "";
        }
        $output = Array();
        $sql = "SELECT * FROM generic_product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_name"] = $row["label_claim"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLabelClaims") {
        if (!isset($_GET["grade"])) {
            $_GET["grade"] = "";
        }
        if (!isset($_GET["dosage_type"])) {
            $_GET["dosage_type"] = "";
        }
        if (!isset($_GET["dosage_form"])) {
            $_GET["dosage_form"] = "";
        }
        $output = Array();
        $sql = "SELECT * FROM product WHERE category='Generic' AND status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_name"] = $row["label_claim"];
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }else if ($_GET["type"] == "saveBrandProduct") {
        $input = $_POST;
        
        $id = date("YmdHis", $timestamp);
        
        
          $id=1;
           $sql1 = "SELECT  COUNT(id)+1 as id FROM product";
          
           $result = $conn->query($sql);
           while($row = $result->fetch_assoc()){
                  $id = $row['id'];
           }
           if($id==0){
            $id=1;
           } 
          
        $number = str_pad($id, 4, '0', STR_PAD_LEFT);
        $prod_code = "P".$number; 
        
        
        $product_lic = "";
        $fsc = "";
        $copp = "";
        $artwork = "";
        if(isset($_FILES['product_lic'])) {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $id."product_lic.".$file_ext;
            $product_lic = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        $sql = "INSERT INTO product (product_code,category, product_name, grade,  dosage_type, dosage_form, generic_name,
      dosage_sub_type, dosage_size,dosage_shape, packing_style,
        packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under,
        manufactured_for, manufactured_type, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, mfg_lic, pack_desc,
        apperance, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,plant,sale_type,expiry,product_group,market_group,asin_no,
        nrv_value,sku_no,packing_charges,retain_qty,retest,max_qty,min_qty,mfg_charges,unit,configuration,primary_packing,primary_subtype,ismono
        ,primary_qty,unit_wt,mono_qty,isouter,outer_qty,shipper_qty,plant_id,dose_unit_type,dose_unit_qty,dose_unit_qty_unit,punch_tool,cp_machine_name,change_part) VALUES
        ('".$prod_code."','".$input["category"]."', '".$input["product_name"]."', '".$input["grade"]."',
        '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_title"]."',
         '".$input["dosage_sub_type"]."', '".$input["dosage_size"]."', '".$input["dosage_shape"]."',
        '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', 
        '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."',
        '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', 
        '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc', '$copp', 
        '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."',
        '".$input["gst"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."',
        '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."', '".$input["similar_name"]."',
        '".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,'".$input["plant"]."',
        '".$input["sale_type"]."','".$input["expiry"]."','".$input["product_group"]."','".$input["market_group"]."',
        '".$input["asin_no"]."','".$input["nrv_value"]."','".$input["sku_no"]."','".$input["packing_charges"]."',
        '".$input["retain_qty"]."','".$input["retest"]."','".$input["max_qty"]."','".$input["min_qty"]."',
        '".$input["mfg_charges"]."','".$input["unit"]."','".$input["configuration"]."','".$input["primary_packing"]."',
        '".$input["primary_subtype"]."','".$input["ismono"]."','".$input["primary_qty"]."','".$input["unit_wt"]."',
        '".$input["mono_qty"]."','".$input["isouter"]."','".$input["outer_qty"]."','".$input["shipper_qty"]."','".$_GET["plant_id"]."',
        '".$input["dose_unit_type"]."','".$input["dose_unit_qty"]."','".$input["dose_unit_qty_unit"]."',
         '".$input["punch_tool"]."','".$input["cp_machine_name"]."','".$input["change_part"]."')";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    // else if ($_GET["type"] == "saveBrandProduct") {
    //     $input = $_POST;
        
    //     $id = date("YmdHis", $timestamp);
        
    //     $product_lic = "";
    //     $fsc = "";
    //     $copp = "";
    //     $artwork = "";
    //     if(isset($_FILES['product_lic'])) {
    //         $file_tmp =$_FILES['product_lic']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
    //         $file_name = $id."product_lic.".$file_ext;
    //         $product_lic = $file_name;
    //         move_uploaded_file($file_tmp,"../upload/product/".$file_name);
    //     }
        
    //     if(isset($_FILES['fsc'])) {
    //         $file_tmp =$_FILES['fsc']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
    //         $file_name = $id."fsc.".$file_ext;
    //         $fsc = $file_name;
    //         move_uploaded_file($file_tmp,"../upload/product/".$file_name);
    //     }
        
    //     if(isset($_FILES['copp'])) {
    //         $file_tmp =$_FILES['copp']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
    //         $file_name = $id."copp.".$file_ext;
    //         $copp = $file_name;
    //         move_uploaded_file($file_tmp,"../upload/product/".$file_name);
    //     }
        
    //     if(isset($_FILES['artwork'])) {
    //         $file_tmp =$_FILES['artwork']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
    //         $file_name = $id."artwork.".$file_ext;
    //         $artwork = $file_name;
    //         move_uploaded_file($file_tmp,"../upload/product/".$file_name);
    //     }
        
    //     $sql = "INSERT INTO product (category,product_code, product_name, grade, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under, manufactured_for, manufactured_type, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, mfg_lic, pack_desc, apperance, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,plant) VALUES
    //     ('".$input["category"]."','".$input["product_code"]."', '".$input["product_name"]."', '".$input["grade"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."', '".$input["similar_name"]."','".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,'".$input["plant"]."')";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    else if ($_GET["type"] == "getBrandProductsLog") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  order by 1 desc";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_label_claim_product") {
        $sql = "SELECT label_claim FROM product WHERE id='".$_GET["id"]."' and plant_id='".$_GET["plant_id"]."' limit 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output = $row['label_claim'];
         
            }
        }
        $json_string= json_encode($output);
        echo json_decode($json_string,TRUE);

    }
    else if ($_GET["type"] == "getProductDetails") {
        $sql = "SELECT * FROM product WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    }
    else if ($_GET["type"] == "get_product_each_unit_types") {
        $sql = "SELECT distinct dose_unit_type FROM product WHERE plant_id='".$_GET["plant_id"]."'";
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row; 
            }
        }  echo json_encode($output);
    }
    else if ($_GET["type"] == "editProduct") {
        $sql = "UPDATE product SET product_name='".$input["product_name"]."',product_type='".$input["product_type"]."', grade='".$input["grade"]."', product_apperance='".$input["product_apperance"]."', storage_condition='".$input["storage_condition"]."', manufactured_under='".$input["manufactured_under"]."', manufactured_for='".$input["manufactured_for"]."',type='".$input["type"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "deleteProduct") {
        $sql = "UPDATE product SET status='DELETED' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadProductsLog") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">Products</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:10%;">Product Type</td>
                    <td style="width:10%;">Product Code</td>
                    <td style="width:12%;">Type</td>
                    <td style="width:17%;">Product Name</td>
                    <td style="width:8%;">Grade</td>
                    <td style="width:20%;">product Apperance</td>
                    <td style="width:18%;">Storage Condition</td>
                </tr>
            </thead>';
             $sql = "SELECT * FROM product  WHERE status='approve' AND grade LIKE '%".$_GET["grade"]."%'AND product_type LIKE '%".$_GET["product_type"]."%'AND product_name LIKE '%".$_GET["product_name"]."%'AND product_code LIKE '%".$_GET["product_code"]."%'";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width:5%;">'.$i.'.</td>
                            <td style="width:10%;">'.$row['product_type'].'</td>
                            <td style="width:10%;">'.$row['product_code'].'</td>
                            <td style="width:12%;">'.$row['type'].'</td>
                            <td style="width:17%;">'.$row['product_name'].'</td>
                            <td style="width:8%;">'.$row['grade'].'</td>
                            <td style="width:20%;">'.$row['product_apperance'].'</td>
                            <td style="width:18%;">'.$row['storage_condition'].'</td>
                        </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BrandProductLog.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadBrandProductLog") {
        
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Products</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%; ">Sr No</td>
                 
                    <td style="width: 10%; ">Product Name</td>
                    <td style="width: 10%; ">Grade</td>
                    <td style="width: 10%; ">MRP</td>
                    <td style="width: 10%; ">Shelf Life</td>
                    <td style="width: 10%; ">Packing Style</td>
                </tr>
            </thead>';
             $sql = "SELECT * FROM product  WHERE status='approve'";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                            <td style="width: 10%; ">'.$i.'.</td>
                            <td style="width: 10%; ">'.$row['product_name'].'</td>
                            <td style="width: 10%; ">'.$row['grade'].'</td>
                            <td style="width: 10%; ">'.$row['mrp'].'</td>
                            <td style="width: 10%; ">'.$row['shelf_life'].'</td>
                            <td style="width: 10%; ">'.$row['packing_style'].'</td>
                        </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BrandProductLog.pdf', 'I');
    } 

}

$conn->close();
?>