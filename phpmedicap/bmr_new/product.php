<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
        manufactured_for,micro,storage_condition,entry_by,entry_date,type,product_nature,short_code) 
        VALUES ('".$input["plant_id"]."','".$input["product_type"]."','".$input["product_code"].'-'.$id."','".$input["product_name"]."',
        '".$input["grade"]."','".$input["product_apperance"]."','".$input["manufactured_under"]."','".$input["manufactured_for"]."',
        '".$input["micro"]."','".$input["storage_condition"]."','".$_GET["emp_id"]."','$entry_date','".$input["type"]."',
        '".$input["material_nature"]."' ,'".$input["short_code"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if($_GET["type"] == "upload")
    {
        
      //print_r($_FILES);exit;
        $targetDir="../upload/product/";
        $fileName = $_FILES["file"]["name"];
        $targetFilePath = $targetDir.time()."_".$fileName;
       // $fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
        if(isset($_FILES["file"]["tmp_name"]) && !empty($_FILES["file"]["tmp_name"]))
        {
            
            $filename = $_FILES["file"]["name"];
            $tempname = $_FILES["file"]["tmp_name"];
            $folder = "..//upload/product/".$filename;
            $uploadpath = $folder .$filename;
            
           if (move_uploaded_file($tempname, $targetFilePath))  {
                $response['response_code']= 1;
                $response['response_data']=$targetFilePath;
    
            }
        else
            {
                $response['response_code']= 2;
                $response['response_data']= 'failed';
            }
}
echo json_encode($response);exit;
    }
    else if ($_GET["type"] == "saveProduct") {
        try{
            $input = $_POST;    
          //print_r($input);
          
            $data =  json_encode($input['data']);
            
     $data = json_decode($input['data'], true);

 
           
          $success = false;
          $id=1;
          $sql1 = "SELECT  COUNT(id)+1 as id FROM product wher product_type='".$prod."'";
          
          $result = $conn->query($sql);
          while($row = $result->fetch_assoc()){
                  $id = $row['id'];
          }
        //   if($id==0){
        //     $id=1;
        //   } 
        //   $length = 4;
        
        
        
        
         $sql = "Select count(*)+1 as count from product";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        
        if($last_id==0){
            $last_id=1;
        }else{
            $last_id = $last_id + 1;
        }
        
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
          $structure_file;
          $msds_file;
          $prod_license_file;
          $who_copp_file;
          $ce_certificate_file;
        //   $number = substr(str_repeat(0, $length).$id, - $length);
          $target_dir = "../../../upload/product/";
          if(isset($_FILES["structure_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["structure_file"]["name"]);
            	$structure_file = $id."_".basename($_FILES["structure_file"]["name"]);
        	    move_uploaded_file($_FILES["structure_file"]["tmp_name"], $target_file);
          }
          if(isset($_FILES["msds_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["msds_file"]["name"]);
            	$msds_file = $id."_".basename($_FILES["msds_file"]["name"]);
        	    move_uploaded_file($_FILES["msds_file"]["tmp_name"], $target_file);
          }
          if(isset($_FILES["prod_license_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["prod_license_file"]["name"]);
            	$prod_license_file = $id."_".basename($_FILES["prod_license_file"]["name"]);
        	    move_uploaded_file($_FILES["prod_license_file"]["tmp_name"], $target_file);
          }
          if(isset($_FILES["who_copp_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["who_copp_file"]["name"]);
            	$who_copp_file = $id."_".basename($_FILES["who_copp_file"]["name"]);
        	    move_uploaded_file($_FILES["who_copp_file"]["tmp_name"], $target_file);
          }
          if(isset($_FILES["ce_certificate_file"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["ce_certificate_file"]["name"]);
            	$ce_certificate_file = $id."_".basename($_FILES["ce_certificate_file"]["name"]);
        	    move_uploaded_file($_FILES["ce_certificate_file"]["tmp_name"], $target_file);
          }
          if(isset($_FILES["photo"]["name"])) {
            	$target_file = $target_dir.$id."_".basename($_FILES["photo"]["name"]);
            	$photo = $id."_".basename($_FILES["photo"]["name"]);
        	    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
          }
           
           
                 $sql = "INSERT INTO product ( structure_file, msds_file,prod_license_file,who_copp_file,ce_certificate_file,photo,moq,plant_id, product_type,
    cas_number, molecular_weight, molecular_formula,  product_code,
    product_code1, product_name,grade, product_apperance,ce_number, manufactured_under, manufactured_for, micro,storage_condition, qc_lead_time,pka_value, color_index,  entry_by, entry_date, market_type,product_nature,
    alt_uom,uom,pack_sizes, mrp_list, selling_price_list, safety_instructions, other_description, short_code,batch_type) VALUES ('".$structure_file."',
    '".$msds_file."','".$prod_license_file."','".$who_copp_file."','".$ce_certificate_file."','".$photo."','".$data["moq"]."', '".$_GET["plant_id"]."',
    '".$data["product_type"]."','".$data["cas_number"]."','".$data["molecular_weight"]."','".$data["molecular_formula"]."',
    '".$data["product_type"].$number."','".$data["product_code1"]."',
    '".$data["product_name"]."',
    '".$data["grade"]."',
    '".$data["product_apperance"]."',
    '".$data["ce_number"]."',
    '".$data["manufactured_under"]."',
    '".$data["manufactured_for"]."',
    '".$data["micro"]."',
    '".$data["storage_condition"]."',
    '".$data["qc_lead_time"]."',
    '".$data["pka_value"]."',
    '".$data["color_index"]."',
    '".$_GET["emp_id"]."',
    '".$entry_date."',
    '".$data["market_type"]."',
    '".$data["product_nature"]."',
    '".$data["alt_uom"]."',
    '".$data["uom"]."',
    '".json_encode($data["pack_sizes"][0])."',
    '".json_encode($data["mrp_list"][0])."',
    '".json_encode($data["selling_price_list"][0])."',
    '".$data["safety_instructions"]."',
    '".$data["other_description"]."',
    '".$data["short_code"]."',
    '".$data["batch_type"]."'
)";
         
            if($conn->query($sql)){
                    // $product_id = $conn->insert_id;                                         
                   // echo $product_id ;
                   $sql1 = "DELETE FROM product_other_information_api WHERE product_id ='".$product_id."'";
                   
                   $conn->query($sql1);
                  $structure_file_path= explode("h",$input["structure_file_path"]);
                  $msds_file = explode("h",$input["msds_file_path"]);
                  $prod_license_file = explode("h",$input["product_apperance"]);
                $who_copp_file =  explode("h",$input["who_copp_path"]);
                $ce_certificate_file = explode("h",$input["ce_certificate_path"]);
                        $sql = "INSERT INTO product_other_information_api(product_code,cas_number,structure_file_path,molecular_weight,molecular_formula,storage_condition,safety_instructions,
                                                product_apperance,other_description,ce_number,color_index,msds_file_path,m_photo,product_license_path,who_copp_path,
                                                ce_certificate_path,qc_lead_time,pka_value,market_type,storage_location) 
                                                VALUES ('".$input["product_type"].$number."','".$input["cas_number"]."','".$structure_file_path[1]."',
                                                '".$input["molecular_weight"]."','".$input["molecular_formula"]."','".$input["storage_condition"]."',
                                                '".$input["safety_instructions"]."','".$input["product_apperance"]."','".$input["other_description"]."','".$input["ce_number"]."',
                                                '".$input["color_index"]."','".$msds_file[1]."','".$photo[1]."','".$prod_license_file[1]."','".$who_copp_file[1]."',
                                                '".$ce_certificate_file[1]."','".$input["qc_lead_time"]."','".$input["pka_value"]."','".$input["market_type"]."',
                                                '".$input["storage_location"]."')";
                   
                   if($conn->query($sql)){
                       $success = true;
                   }else{
                       echo "{\"status\":\"".$conn->error."\"}";
                        $sql ="Delete from product_other_information_api where product_id = '".$product_id."'";
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
          $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'   ORDER BY `id` DESC";
           //$sql = "SELECT p.*,c.company ,c.client_code FROM product p LEFT JOIN client c ON p.manufactured_for=c.client_code 
           //WHERE p.status='approve'";
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveGrades") {
         $sql="SELECT * FROM grade WHERE grade='".$input["grade"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Grade Already Exists. Duplicate Values are not allowed\"}";
        }
        else {
        $sql = "INSERT INTO grade (grade,entry_by,entry_date,plant_id) VALUES ('".$input["grade"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
        }
    }else if ($_GET["type"] == "getGrades") {
        $output = array();
        $sql = "SELECT * FROM grade WHERE status='active'  order by grade";
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
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        $sql = "INSERT INTO generic_product (user_no,product_code, grade,tshape,tsize, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, sale_ts, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, cess, category, mfg_lic, pack_desc, apperance, storage_condition, mrp, other_expn, invt_depo_Ref, cylinder_cost, packing_design, permission_cost, status,short_code,batch_type) VALUES
        ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["grade"]."','".$input["tshape"]."','".$input["tsize"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["sale_ts"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["cess"]."', '".$input["category"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["mrp"]."', '".$input["other_expn"]."', '".$input["invt_depo_Ref"]."', '".$input["cylinder_cost"]."', '".$input["packing_design"]."', '".$input["permission_cost"]."', 'approve','".$input["short_code"]."','".$input["batch_type"]."')";
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
       $sql = "Select   dosage_form_type as dosage_form from master_fg_types WHERE plant_id = '".$_GET["plant_id"]."'";// ORDER BY dosage_form_type";
        
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
           if (!isset($_GET["brand_generic"])) {
            $_GET["brand_generic"] = "";
        }
        $output = Array();
    //   echo  $sql = "SELECT * FROM generic_product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $sql = "SELECT * FROM product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' AND brand_generic LIKE '%".$_GET["brand_generic"]."%'  ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["product_name"] = $row["label_claim"];
                $row["label_claim"] = json_decode($row["label_claim"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProducts_copy") {
        if (!isset($_GET["grade"])) {
            $_GET["grade"] = "";
        }
        if (!isset($_GET["dosage_type"])) {
            $_GET["dosage_type"] = "";
        }
        if (!isset($_GET["dosage_form"])) {
            $_GET["dosage_form"] = "";
        }
        if (!isset($_GET["brand_generic"])) {
            $_GET["brand_generic"] = "";
        }
        $output = Array();
    //   echo  $sql = "SELECT * FROM generic_product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $sql = "SELECT * FROM product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%' AND brand_generic LIKE '%".$_GET["brand_generic"]."%'   ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["product_name"] = $row["label_claim"];
                $row["label_claim"] = json_decode($row["label_claim"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLabelClaims") {
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
        $sql = "SELECT * FROM product WHERE  status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_name"] = $row["label_claim"];
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveBrandProduct") {
         $input = $_POST;
 
    $sql="SELECT * FROM product WHERE product_name='".$input["product_name"]."'
          AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
       // echo $sql;
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"product Already Exists. Duplicate Values are not allowed\"}";
        }else{
 
 
 
           $input = $_POST;
 
        
           $sql1 = "SELECT  COUNT(id)+1 as id FROM product";
          
 
          $result1 = $conn->query($sql1);
         $row1 = $result1->fetch_assoc();
         $last_id=$row1["id"]+3; 
          $plant_id=$_GET['plant_id'];
         $number = str_pad($id, 4, '0', STR_PAD_LEFT);
        
    
       
        $prod_code = "P".$last_id; 
        
        $plant_id=$_GET['plant_id'];
        $product_lic = "";
        $fsc = "";
        $copp = "";
        $artwork = "";
        
        $sql = "INSERT INTO product (Export,Country,product_type,product_code,category, product_name, grade,  dosage_type, dosage_form, generic_name,
      dosage_sub_type, dosage_size,dosage_shape, packing_style,
        packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under,
        manufactured_for, manufactured_type, product_lic, fsc, copp, photo, entry_by, entry_date, gtin, mfg_lic, pack_desc,
        apperance, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,sale_type,expiry,product_group,market_group,asin_no,
        nrv_value,sku_no,packing_charges,retain_qty,retest,max_qty,min_qty,mfg_charges,unit,configuration,primary_packing,primary_subtype,ismono
        ,primary_qty,unit_wt,mono_qty,isouter,outer_qty,fssai_number,shipper_qty,plant_id,dose_unit_type,dose_unit_qty,dose_unit_qty_unit,punch_tool,cp_machine_name,change_part,artwork,mopcup,artwork_file,mopcup_file,short_code,batch_type,brand_generic) VALUES
        ('".$input["Export"]."','".$input["Country"]."','".$input["product_type"]."','".$prod_code."','".$input["category"]."', '".$input["product_name"]."', '".$input["grade"]."',
        '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_title"]."',
        '".$input["dosage_sub_type"]."', '".$input["dosage_size"]."', '".$input["dosage_shape"]."',
        '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', 
        '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."',
        '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', 
        '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','NA', 'NA','NA', 'NA',
        '".$_GET["emp_id"]."', '$entry_date', '".$input["gtin"]."',
        '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."',
        '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."', '".$input["similar_name"]."',
        '".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,
        '".$input["sale_type"]."','".$input["expiry"]."','".$input["product_group"]."','".$input["market_group"]."',
        '".$input["asin_no"]."','".$input["nrv_value"]."','".$input["sku_no"]."','".$input["packing_charges"]."',
        '".$input["retain_qty"]."','".$input["retest"]."','".$input["max_qty"]."','".$input["min_qty"]."',
        '".$input["mfg_charges"]."','".$input["unit"]."','".$input["configuration"]."','".$input["primary_packing"]."',
        '".$input["primary_subtype"]."','".$input["ismono"]."','".$input["primary_qty"]."','".$input["unit_wt"]."',
        '".$input["mono_qty"]."','".$input["isouter"]."','".$input["outer_qty"]."','".$input["fssai_number"]."','".$input["shipper_qty"]."','".$_GET["plant_id"]."',
        '".$input["dose_unit_type"]."','".$input["dose_unit_qty"]."','".$input["dose_unit_qty_unit"]."',
        '".$input["punch_tool"]."','".$input["cp_machine_name"]."','".$input["change_part"]."','".$input["artworkList"]."','".$input["list"]."', 'NA', 'NA','".$input["short_code"]."','".$input["batch_type"]."','".$input["brand_generic"]."')";
    
        if ($conn->query($sql)) {
            if($input["product_type"]=='Intermediate Product'){
                   $sql1 = "INSERT INTO material (plant_id,material_code,material_type,  material_name,  material_nature, uom,material_subtype)
       values('".$input["plant_id"]."','".$prod_code."','Raw Material','".$input["product_name"]."','".$input["dosage_type"]."','".$input["unit"]."','".$input["product_type"]."' )           ";
          $conn->query($sql1);
            }
            
            
            
            
            
        $flag = 0;
        //  $sql = "INSERT INTO manufacturing_process (product_code,for_department,batch_lot,plant_id,ProcessTitle,DocumentTitle,DocumentNo,SubTitle,Description) VALUES ('".$prod_code."','".$input["for_department"]."','".$input["batch_lot"]."','".$_GET["plant_id"]."','".$input["ProcessTitle"]."','".$input["DocumentTitle"]."','".$input["DocumentNo"]."','".$input["SubTitle"]."','".$input["Description"]."')";
//             if ($conn->query($sql)) {
//         $last_id = $conn->insert_id;
//     $stagess = json_decode($_POST['stages'], true);

// // Check if $stagess is an array
// if (is_array($stagess)) {
//     foreach ($stagess as $data) {
//         if (isset($data["stage"]) && isset($data["stepss"]) && is_array($data["stepss"])) {
//             $sql = "INSERT INTO manufacturing_process_stages (plant_id, manufacturing_process_id, stages) VALUES ('".$_GET["plant_id"]."','$last_id','".$data["stage"]."')";
//             if ($conn->query($sql)) {
//                 $stage_id = $conn->insert_id; // Get the last inserted stage ID
                
//                 foreach ($data["stepss"] as $step) {
//                     $sql = "INSERT INTO manufacturing_process_step (plant_id, manufacturing_process_stages_id, step, split_lot,tables_DATA) VALUES ('".$_GET["plant_id"]."', '$stage_id', '".$step["step"]."', '".$step["split_lot"]."', '".$step["Scope"]."')";
//                     if (!$conn->query($sql)) {
//                         $flag = 0;
//                         break; // Exit the loop if there's an error
//                     }
//                 }
                
//                 $flag = 1;
//             } else {
//                 $flag = 0;
//                 break;
//             }
//         } else {
//             // Handle the case when data is not in the expected format (e.g., show an error message)
//             echo "Error: Stages data is not in the expected format.";
//             $flag = 0;
//             break;
//         }
//     }
// } else {
//     // Handle the case when $stagess is not an array (e.g., show an error message)
//     echo "Error: Stages data is not in the expected format.";
// }
    
        
            
            
//             echo "{\"status\":\"success\"}";
//         } else {
            // echo "{\"status\":\"".$conn->error."\"}";
        // }
        echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
    } 
        }
    }
    else if ($_GET["type"] == "saveBrandProduct_saipro") {
        $input = $_POST;
        //  $prefix=$data["prefix"];
        // $id = date("YmdHis", $timestamp);
        
        
        //   $id=1;
           $sql1 = "SELECT  COUNT(id)+1 as id FROM product";
          
        //   $result = $conn->query($sql);
        //   while($row = $result->fetch_assoc()){
        //           $id = $row['id'];
        //   }
        //   if($id==0){
        //     $id=1;
        //   } 
          $result1 = $conn->query($sql1);
         $row1 = $result1->fetch_assoc();
         $last_id=$row1["id"]+3; 
          $plant_id=$_GET['plant_id'];
        $prefix=$input["short_code"];
        $number = str_pad($id, 4, '0', STR_PAD_LEFT);
        $prod_code = "P".$prefix.$last_id; 
        
        
        // $product_lic = "";
        // $fsc = "";
        // $copp = "";
        // $artwork = "";
        if(isset($_FILES['product_lic'])) {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $plant_id.$id."product_lic.".$file_ext;
            $product_lic = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $plant_id.$id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        if(isset($_FILES['photo'])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $plant_id.$id."photo.".$file_ext;
            $photo = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        if(isset($_FILES['artwork_file'])) {
            $file_tmp =$_FILES['artwork_file']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork_file']['name'])));
            $file_name = $plant_id.$id."artwork_file.".$file_ext;
            $artwork_file = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        if(isset($_FILES['mopcup_file'])) {
            $file_tmp =$_FILES['mopcup_file']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mopcup_file']['name'])));
            $file_name = $plant_id.$id."mopcup_file.".$file_ext;
            $mopcup_file = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        // if(isset($_FILES['artwork'])) {
        //     $file_tmp =$_FILES['artwork']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
        //     $file_name = $id."artwork.".$file_ext;
        //     $artwork = $file_name;
        //     move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        // }
        
    //     $sql = "INSERT INTO product (product_code,category, product_name, grade,  dosage_type, dosage_form, generic_name,
    //   dosage_sub_type, dosage_size,dosage_shape, packing_style,
    //     packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under,
    //     manufactured_for, manufactured_type, product_lic, fsc, copp, photo, entry_by, entry_date, gtin, mfg_lic, pack_desc,
    //     apperance, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,plant,sale_type,expiry,product_group,market_group,asin_no,
    //     nrv_value,sku_no,packing_charges,retain_qty,retest,max_qty,min_qty,mfg_charges,unit,configuration,primary_packing,primary_subtype,ismono
    //     ,primary_qty,unit_wt,mono_qty,isouter,outer_qty,fssai_number,shipper_qty,plant_id,dose_unit_type,dose_unit_qty,dose_unit_qty_unit,punch_tool,cp_machine_name,change_part,artwork,mopcup,artwork_file,mopcup_file,short_code,batch_type) VALUES
    //     ('".$prod_code."','".$input["category"]."', '".$input["product_name"]."', '".$input["grade"]."',
    //     '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_title"]."',
    //      '".$input["dosage_sub_type"]."', '".$input["dosage_size"]."', '".$input["dosage_shape"]."',
    //     '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', 
    //     '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."',
    //     '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', 
    //     '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc','$copp', '$photo',
    //     '".$_GET["emp_id"]."', '$entry_date', '".$input["gtin"]."',
    //      '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."',
    //     '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."', '".$input["similar_name"]."',
    //     '".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,'".$input["plant"]."',
    //     '".$input["sale_type"]."','".$input["expiry"]."','".$input["product_group"]."','".$input["market_group"]."',
    //     '".$input["asin_no"]."','".$input["nrv_value"]."','".$input["sku_no"]."','".$input["packing_charges"]."',
    //     '".$input["retain_qty"]."','".$input["retest"]."','".$input["max_qty"]."','".$input["min_qty"]."',
    //     '".$input["mfg_charges"]."','".$input["unit"]."','".$input["configuration"]."','".$input["primary_packing"]."',
    //     '".$input["primary_subtype"]."','".$input["ismono"]."','".$input["primary_qty"]."','".$input["unit_wt"]."',
    //     '".$input["mono_qty"]."','".$input["isouter"]."','".$input["outer_qty"]."','".$input["fssai_number"]."','".$input["shipper_qty"]."','".$_GET["plant_id"]."',
    //     '".$input["dose_unit_type"]."','".$input["dose_unit_qty"]."','".$input["dose_unit_qty_unit"]."',
    //      '".$input["punch_tool"]."','".$input["cp_machine_name"]."','".$input["change_part"]."','".$input["artworkList"]."','".$input["list"]."', '$artwork_file', '$mopcup_file','".$input["short_code"]."','".$input["batch_type"]."')";
    
      $sql = "INSERT INTO product (product_code,category, product_name, grade,  dosage_type, dosage_form, generic_name,dosage_sub_type, dosage_size,dosage_shape, packing_style,packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under,
        manufactured_for, manufactured_type, product_lic, fsc, copp, photo, entry_by, entry_date, gtin, mfg_lic, pack_desc,
        apperance, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,plant,sale_type,expiry,product_group,market_group,asin_no,
        nrv_value,sku_no,packing_charges,retain_qty,retest,max_qty,min_qty,mfg_charges,unit,configuration,primary_packing,primary_subtype,ismono
        ,primary_qty,unit_wt,mono_qty,isouter,outer_qty,fssai_number,shipper_qty,plant_id,dose_unit_type,dose_unit_qty,dose_unit_qty_unit,punch_tool,cp_machine_name,change_part,artwork,mopcup,artwork_file,mopcup_file,short_code,batch_type,packing_type, 	secondary_packing,pack_sizes,nos_pouch,capsule_size,mono_cartain,master_mono_qty ,tertiary_packing ,tertiary_packing_data,scoop_add ,Shrink,wad_sealing, 	master_cartain ,brand_generic ,type ) VALUES
        ('".$prod_code."','".$input["category"]."', '".$input["product_name"]."', '".$input["grade"]."',
        '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_title"]."',
         '".$input["dosage_sub_type"]."', '".$input["dosage_size"]."', '".$input["dosage_shape"]."',
        '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', 
        '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."',
        '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', 
        '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc','$copp', '$photo',
        '".$_GET["emp_id"]."', '$entry_date', '".$input["gtin"]."',
         '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."',
        '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."', '".$input["similar_name"]."',
        '".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,'".$input["plant"]."',
        '".$input["sale_type"]."','".$input["expiry"]."','".$input["product_group"]."','".$input["market_group"]."',
        '".$input["asin_no"]."','".$input["nrv_value"]."','".$input["sku_no"]."','".$input["packing_charges"]."',
        '".$input["retain_qty"]."','".$input["retest"]."','".$input["max_qty"]."','".$input["min_qty"]."',
        '".$input["mfg_charges"]."','".$input["unit"]."','".$input["configuration"]."','".$input["primary_packing"]."',
        '".$input["primary_subtype"]."','".$input["ismono"]."','".$input["primary_qty"]."','".$input["unit_wt"]."',
        '".$input["mono_qty"]."','".$input["isouter"]."','".$input["outer_qty"]."','".$input["fssai_number"]."','".$input["shipper_qty"]."','".$_GET["plant_id"]."',
        '".$input["dose_unit_type"]."','".$input["dose_unit_qty"]."','".$input["dose_unit_qty_unit"]."',
         '".$input["punch_tool"]."','".$input["cp_machine_name"]."','".$input["change_part"]."','".$input["artworkList"]."','".$input["list"]."', '$artwork_file', '$mopcup_file','".$input["short_code"]."','".$input["batch_type"]."','".$input["packing_type"]."','".$input["secondary_packing"]."','".$input["pack_size"]."','".$input["no_pouch"]."','".$input["capsule_size"]."','".$input["mono_cartain"]."','".$input["master_mono_qty"]."','".$input["tertiary_packing"]."'
         ,'".$input["tertiary_packing_data"]."','".$input["scoop_add"]."','".$input["shrink"]."',
         '".$input["wad_sealing"]."','".$input["wad_sealing"]."','".$input["brand_generic"]."','".$input["type"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
     else if ($_GET["type"] == "updateProduct") {
       $sql="UPDATE product SET gst='".$input["gst"]."',hsn='".$input["hsn"]."',conversion_cost='".$input["conversion_cost"]."'
       ,packing_cost='".$input["packing_cost"]."',other_charges='".$input["other_charges"]."',fixed_charges='".$input["fixed_charges"]."'
       ,design_charges='".$input["design_charges"]."' ,license_charges='".$input["license_charges"]."',fixed_analytical_cost='".$input["fixed_analytical_cost"]."',dealer_price='".$input["dealer_price"]."'
       ,retailer_margin='".$input["retailer_margin"]."',dealer_margin='".$input["dealer_margin"]."',change_part='".$input["change_part"]."',dossier_charges='".$input["dossier_charges"]."'WHERE product_code='".$_GET["product_code"]."'";
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
    
    else if ($_GET["type"] == "getProductBatchNuber") {
        
        $output = Array();
        $sql = "SELECT b.batch_number FROM batch_planning a left join mfg_work_order_hdr b on a.id=b.batch_plan_id WHERE b.batch_number!='' and a.product_code='".$_GET["prod_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    
        
    }
    
    
    
    else if ($_GET["type"] == "getBrandProductsLog") {
        $output = Array();
        // $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."' and status like '".$_GET["status"]."'  order by 1 desc";
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."' and status like '%".$_GET["status"]."%' and product_type like '%".$_GET["product_type"]."%'  order by 1 desc";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["artwork"] = json_decode($row["artwork"]);
                $row["mopcup"] = json_decode($row["mopcup"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getBrandProductsLogBMR") { 
        $output = Array();
           $sql = "SELECT a.dosage_form,a.product_code,a.product_name,COALESCE(c.id, b.id) AS process_id, COALESCE(b.DocumentTitle, 0) as DocumentTitle,b.DocumentNo,b.effective_date,
           a.bmr_status,a.complete_on,a.complete_by,a.review_on,a.review_by,a.approved_on,a.approved_by FROM product
           a left join manufacturing_process b on a.product_code=b.product_code left join bmr_process c  on a.product_code=c.product_code WHERE a.plant_id='".$_GET["plant_id"]."'   
           and b.DocumentTitle!='0' order by a.id desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductsLog_by_dossage") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."' and dosage_form='".$_GET["dosage_form"]."' order by 1 desc";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["artwork"] = json_decode($row["artwork"]);
                $row["mopcup"] = json_decode($row["mopcup"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_label_claim_product") {
        
        
        $sql = "SELECT label_claim FROM product WHERE id='".$_GET["id"]."' and plant_id='".$_GET["plant_id"]."' LIMIT 1";
                $result = $conn->query($sql);
                
                $output = [];
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Decode the JSON string directly without creating an extra level of nesting
                        $row = json_decode($row["label_claim"], true);
                        $output = $row; // No need to use an array for each row if you only fetch one row
                    }
                }
                
                // Send the JSON response
                echo json_encode($output);
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
          $sql = "UPDATE product SET product_name='".$_POST["product_name"]."'  WHERE id='".$_POST["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "approve_product") {
         $sql = "UPDATE product SET status='".$_GET["status"]."' WHERE product_code='".$_GET["product_code"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    // else if ($_GET["type"] == "editProduct") {
    //      $input = $_POST;
        
    //     $id = date("YmdHis", $timestamp);
        
        
    //       $id=1;
    //       $sql1 = "SELECT  COUNT(id)+1 as id FROM product";
          
    //       $result = $conn->query($sql);
    //       while($row = $result->fetch_assoc()){
    //               $id = $row['id'];
    //       }
    //       if($id==0){
    //         $id=1;
    //       } 
    //       $plant_id=$_GET['plant_id'];
    //     $number = str_pad($id, 4, '0', STR_PAD_LEFT);
    //     $prod_code = "P".$number; 
        
        
    //     // $product_lic = "";
    //     // $fsc = "";
    //     // $copp = "";
    //     // $artwork = "";
    //     if(isset($_FILES['product_lic'])) {
    //         $file_tmp =$_FILES['product_lic']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
    //         $file_name = $plant_id.$id."product_lic.".$file_ext;
    //         $product_lic = $file_name;
    //         move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
    //     }
        
    //     if(isset($_FILES['fsc'])) {
    //         $file_tmp =$_FILES['fsc']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
    //         $file_name = $id."fsc.".$file_ext;
    //         $fsc = $file_name;
    //         move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
    //     }
        
    //     if(isset($_FILES['copp'])) {
    //         $file_tmp =$_FILES['copp']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
    //         $file_name = $plant_id.$id."copp.".$file_ext;
    //         $copp = $file_name;
    //         move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
    //     }
    //     if(isset($_FILES['photo'])) {
    //         $file_tmp =$_FILES['photo']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
    //         $file_name = $plant_id.$id."photo.".$file_ext;
    //         $photo = $file_name;
    //         move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
    //     }
    //     if(isset($_FILES['artwork_file'])) {
    //         $file_tmp =$_FILES['artwork_file']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['artwork_file']['name'])));
    //         $file_name = $plant_id.$id."artwork_file.".$file_ext;
    //         $artwork_file = $file_name;
    //         move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
    //     }
    //     if(isset($_FILES['mopcup_file'])) {
    //         $file_tmp =$_FILES['mopcup_file']['tmp_name'];
    //         $file_ext=strtolower(end(explode('.',$_FILES['mopcup_file']['name'])));
    //         $file_name = $plant_id.$id."mopcup_file.".$file_ext;
    //         $mopcup_file = $file_name;
    //         move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
    //     }
        
        
     
    //  $sql = "UPDATE  product SET product_code = '".$input["product_code"]."',category='".$input["category"]."', product_name='".$input["product_name"]."',
    //   grade = '".$input["grade"]."',  dosage_type = '".$input["dosage_type"]."', dosage_form ='".$input["dosage_form"]."',
    //   generic_name = '".$input["generic_title"]."',dosage_sub_type = '".$input["dosage_sub_type"]."', dosage_size = '".$input["dosage_size"]."',
    //   dosage_shape = '".$input["dosage_shape"]."', packing_style = '".$input["packing_style"]."',packing_mode='".$input["packing_mode"]."',
    //   label_claim ='".$input["label_claim"]."', shelf_life='".$input["shelf_life"]."',min_shelf='".$input["min_shelf"]."', thera='".$input["thera"]."',
    //   testing_time='".$input["testing_time"]."', retest_period='".$input["retest_period"]."', division='".$input["division"]."',
    //   manufactured_under='".$input["manufactured_under"]."',manufactured_for='".$input["manufactured_for"]."', manufactured_type='".$input["manufactured_type"]."',
    //   product_lic = '".$product_lic."', fsc='".$product_lic."', copp='".$copp."', photo='".$photo."', entry_by='".$_GET["emp_id"]."',
    //   entry_date='$entry_date', gtin='".$input["gtin"]."', mfg_lic= '".$input["mfg_lic"]."', pack_desc='".$input["pack_desc"]."',
    //     apperance='".$input["apperance"]."', storage_condition='".$input["storage_condition"]."', market='".$input["market"]."',
    //     mrp='".$input["mrp"]."',similar_name='".$input["similar_name"]."',copy_from='".$input["copy_from"]."',
    //     tsize='".$input["tsize"]."',tshape='".$input["tshape"]."',plant='".$input["plant"]."',sale_type='".$input["sale_type"]."',
    //     expiry='".$input["expiry"]."',product_group='".$input["product_group"]."',market_group='".$input["market_group"]."',
    //     asin_no='".$input["asin_no"]."',nrv_value='".$input["nrv_value"]."',sku_no='".$input["sku_no"]."',
    //     packing_charges='".$input["packing_charges"]."',retain_qty='".$input["retain_qty"]."',retest='".$input["retest"]."',
    //     max_qty='".$input["max_qty"]."',min_qty='".$input["min_qty"]."',mfg_charges='".$input["mfg_charges"]."',
    //     unit='".$input["unit"]."',configuration='".$input["configuration"]."',primary_packing='".$input["primary_packing"]."',
    //     primary_subtype='".$input["primary_subtype"]."',ismono='".$input["ismono"]."'
    //     ,primary_qty='".$input["primary_qty"]."',unit_wt='".$input["unit_wt"]."',mono_qty='".$input["mono_qty"]."',
    //     isouter='".$input["isouter"]."',outer_qty='".$input["outer_qty"]."',fssai_number='".$input["fssai_number"]."',
    //     shipper_qty='".$input["shipper_qty"]."',plant_id='".$_GET["plant_id"]."',dose_unit_type='".$input["dose_unit_type"]."',
    //     dose_unit_qty='".$input["dose_unit_qty"]."',dose_unit_qty_unit='".$input["dose_unit_qty_unit"]."',punch_tool='".$input["punch_tool"]."',
    //     cp_machine_name='".$input["cp_machine_name"]."',change_part='".$input["change_part"]."',artwork='".$input["artworkList"]."',
    //     mopcup='".$input["list"]."',artwork_file='".$artwork_file."',mopcup_file='".$mopcup_file."',short_code='".$input["short_code"]."',
    //     batch_type='".$input["batch_type"]."',packing_type='".$input["packing_type"]."', 	secondary_packing='".$input["secondary_packing"]."',
    //     pack_sizes='".$input["pack_size"]."',nos_pouch='".$input["no_pouch"]."',capsule_size='".$input["capsule_size"]."',
    //     mono_cartain='".$input["mono_cartain"]."',master_mono_qty='".$input["master_mono_qty"]."' ,tertiary_packing ='".$input["tertiary_packing"]."',
    //     tertiary_packing_data='".$input["tertiary_packing_data"]."',scoop_add='".$input["scoop_add"]."' ,Shrink='".$input["shrink"]."',
    //     wad_sealing='".$input["wad_sealing"]."', 	master_cartain='".$input["wad_sealing"]."' ,
    //     brand_generic ='".$input["brand_generic"]."',type='".$input["type"]."' WHERE id='".$input["id"]."' ";
        
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    
    
    
    
    else if ($_GET["type"] == "deleteProduct") {
        $sql = "Update product SET status='DELETED' WHERE id='".$_GET["id"]."'";
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
              $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  order by 1 desc";
             //$sql = "SELECT * FROM product  WHERE status='approve' AND grade LIKE '%".$_GET["grade"]."%'AND product_type LIKE '%".$_GET["product_type"]."%'AND product_name LIKE '%".$_GET["product_name"]."%'AND product_code LIKE '%".$_GET["product_code"]."%'";
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
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Products</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%; text-align: center;">Sr No</td>
                    <td style="width: 10%; text-align: center;">Plant</td>
                    <td style="width: 10%;text-align: center; ">Category</td>
                    <td style="width: 10%;text-align: center; ">Dosage Form</td>
                    <td style="width: 10%;text-align: center; ">Product Code</td>
                    <td style="width: 10%; ">Product Name</td>
                    <td style="width: 10%; text-align: center;">Grade</td>
                    <td style="width: 10%; text-align: center;">MRP</td>
                    <td style="width: 10%; text-align: center;">Shelf Life</td>
                    <td style="width: 10%; text-align: center;">Packing Style</td>
                </tr>
            </thead>';
             $sql = "SELECT * FROM product  WHERE status='approve'";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                            <td style="width: 10%; text-align: center;">'.$i.'.</td>
                            <td style="width: 10%;  text-align: center;">'.$row['plant'].'</td>
                            <td style="width: 10%;  text-align: center;">'.$row['category'].'</td>
                            <td style="width: 10%; text-align: center;">'.$row['dosage_form'].'</td>
                            <td style="width: 10%; text-align: center;">'.$row['product_code'].'</td>
                            <td style="width: 10%; text-align: center;">'.$row['product_name'].'</td>
                            <td style="width: 10%; text-align: center; ">'.$row['grade'].'</td>
                            <td style="width: 10%; text-align: center;">'.$row['mrp'].'</td>
                            <td style="width: 10%; text-align: center;">'.$row['shelf_life'].'</td>
                            <td style="width: 10%; text-align: center;">'.$row['packing_style'].'</td>
                        </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BrandProductLog.pdf', 'I');
    } 
    else if ($_GET["type"] == "download_log") {
        
        $_GET['filename'] = ' product'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center"> Shortages</h2>
        <table border="1" cellpadding="5">
            <thead>
               
     <tr>
        <td style="width: 135px; font-size: 9px; "> PRODUCT NAME : </td>
        <td style="width: 125px; font-size: 9px; font-weight: bold;"> '.$row['product_name'].'</td>
        <td style="width: 150px; font-size: 9px;">Product Type.: </td>
        <td style="width: 130px; font-size: 9px; font-weight: bold; "> '.$row['product_code1'].' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 9px; "> Grade.: </td>
        <td style="width: 125px; font-size: 9px; font-weight: bold;"> '.$row['mrf_no'].' </td>
        <td style="width: 150px; font-size: 9px;"> Select MFR No .: </td>
        <td style="width: 130px; font-size: 9px; font-weight: bold; "> '.$row['refmfr_no'].'</td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 9px; "> Select BFR No: </td>
        <td style="width: 125px; font-size: 9px; font-weight: bold;">'.$row['colour'].' </td>
        <td style="width: 150px; font-size: 9px;"> BFR Batch Size: </td>
        <td style="width: 130px; font-size: 9px; font-weight: bold; "> '.$row['ref_sample_batch_no'].' </td>
    </tr>
    <tr>
        <td style="width: 135px; font-size: 9px; "> No.of Batches: </td>
        <td style="width: 125px; font-size: 9px; font-weight: bold;"> '.$row['shelf_life'].'</td>
        <td style="width: 150px; font-size: 9px;"> STD. LOT SIZE: </td>
        <td style="width: 130px; font-size: 9px; font-weight: bold; "> '.$row['batch_size'].' '.$row['unit'].' </td>
    </tr>
    
    
            </thead>';
            //  $sql = "SELECT * FROM product  WHERE status='approve'";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                // $html.='<tr nobr="true">
                //             <td style="width: 10%; ">'.$i.'.</td>
                //             <td style="width: 10%; ">'.$row['plant'].'</td>
                //             <td style="width: 10%; ">'.$row['category'].'</td>
                //             <td style="width: 10%; ">'.$row['dosage_form'].'</td>
                //             <td style="width: 10%; ">'.$row['product_code'].'</td>
                //             <td style="width: 10%; ">'.$row['product_name'].'</td>
                //             <td style="width: 10%; ">'.$row['grade'].'</td>
                //             <td style="width: 10%; ">'.$row['mrp'].'</td>
                //             <td style="width: 10%; ">'.$row['shelf_life'].'</td>
                //             <td style="width: 10%; ">'.$row['packing_style'].'</td>
                //         </tr>';
                        $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PDF_DATA.pdf', 'I');
    }
        else if ($_GET["type"] == "get_mfg_exp") {
            
        $output = Array();
         $sql = "SELECT * FROM finish_mfg_exp  WHERE plant_id='".$_GET["plant_id"]."'  ";
          
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
            
        }
        else if ($_GET["type"] == "getSpecification_no") {
            
        $output = Array();
         $sql = "SELECT * FROM specification  where material_code='".$_GET['product_no']."' AND spec_type = 'Inprocess Specification'  ";
          
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                         $sql1 = "SELECT * FROM spec_tests    WHERE specification_no='".$row["specification_no"]."'   ";
                                       $result1 = $conn->query($sql1);
                                    if ($result1->num_rows > 0) {
                                        while ($row1 = $result1->fetch_assoc()) {
                                        
                                            $output1[] = $row1;
                                        }
                                    }
                $row['spec_tests']=$output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
            
        }
        else if ($_GET["type"] == "mfg_exp") {

            
        $sql = "INSERT INTO finish_mfg_exp (plant_id,export,exp_criteria,mfg_date) VALUES ('".$_GET["plant_id"]."','".$input["export"]."','".$input["exp_criteria"]."','".$input["mfg_date"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
        
        }

}

$conn->close();
?>