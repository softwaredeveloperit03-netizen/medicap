<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('response_token: test123456');
;
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$output = Array(); 
$token = $_GET["token"];
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
    
    
    if ($_GET["type"] == "getProductBatchNuber") {
        
        $output = Array();
      $sql = "SELECT b.batch_number FROM batch_planning a left join mfg_work_order_hdr b on a.id=b.batch_plan_id WHERE b.batch_number!='' and a.product_code='".$_GET["prod_code"]."'";
        if($result = $conn->query($sql))
        {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output[] = $row;
            }
        }
        }
        echo json_encode($output);
     
    }
    if ($_GET["type"] == "getMaterialSubType") {
        
        $output = Array();
      $sql = "SELECT material_subtype from material  where material_type='Raw Material' group by material_subtype";
        if($result = $conn->query($sql))
        {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output[] = $row;
            }
        }
        }
        echo json_encode($output);
     
    }
          else if ($_GET["type"] == "get_dosage_typesMFR") {
        $output = Array();
        $sql = "Select   dosage_form   from product  group by dosage_form";// ORDER BY dosage_form_type";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
      else if ($_GET["type"] == "get_dosage_typesBMRMaster") {
        $output = Array();
       $sql = "Select   dosage_form as dosage_form from product WHERE plant_id = '".$_GET["plant_id"]."' group by dosage_form";// ORDER BY dosage_form_type";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if (isset($_GET['type']) && $_GET['type'] == 'updateProductStageDays') {
    $plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }
    $product_code = isset($input['product_code']) ? $conn->real_escape_string($input['product_code']) : '';
    $plant = isset($input['plant_id']) ? $conn->real_escape_string($input['plant_id']) : $plant_id;
    $stages = isset($input['stages']) && is_array($input['stages']) ? $input['stages'] : [];
    if ($product_code === '') {
        echo json_encode(['status' => 'error', 'message' => 'product_code required']);
        exit;
    }
    $conn->query("DELETE FROM product_stage_days WHERE product_code = '" . $product_code . "' AND (plant_id = '" . $plant . "' OR '" . $plant . "' = '')");
    foreach ($stages as $s) {
        $sid = isset($s['stage_id']) ? (int) $s['stage_id'] : (isset($s['id']) ? (int) $s['id'] : 0);
        $days = isset($s['days']) ? (int) $s['days'] : 0;
        if ($sid <= 0) {
            continue;
        }
        $conn->query("INSERT INTO product_stage_days (product_code, stage_id, plant_id, days) VALUES ('" . $product_code . "', " . $sid . ", '" . $plant . "', " . $days . ")");
    }
    echo json_encode(['status' => 'success']);
    exit;
}

 else if (isset($_GET['type']) && $_GET['type'] == 'getProductStagesWithDays') {

    $output = Array();

    $plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
    $product_code = isset($_GET['product_code']) ? $conn->real_escape_string($_GET['product_code']) : '';

    if ($product_code == '') {
        echo json_encode($output);
        exit;
    }

    $sql_process = "SELECT id 
                    FROM manufacturing_process 
                    WHERE product_code='".$product_code."' 
                    AND (plant_id='".$plant_id."' OR '".$plant_id."'='') 
                    ORDER BY id DESC 
                    LIMIT 1";

    $res_process = $conn->query($sql_process);

    if ($res_process && $res_process->num_rows > 0) {

        $proc = $res_process->fetch_assoc();
        $process_id = $proc['id'];

        $sql = "SELECT 
                    b.id AS stage_id,
                    b.stages AS stage_name,
                    COALESCE(psd.days,0) AS days
                FROM manufacturing_process_stages b
                LEFT JOIN product_stage_days psd 
                    ON psd.stage_id=b.id 
                    AND psd.product_code='".$product_code."'
                    AND (psd.plant_id='".$plant_id."' OR psd.plant_id='')
                WHERE b.manufacturing_process_id='".$process_id."'
                AND (b.plant_id='".$plant_id."' OR '".$plant_id."'='')
                ORDER BY b.id ASC";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

            $seq = 0;

            while ($row = $result->fetch_assoc()) {

                $row['id'] = (int)$row['stage_id'];
                $row['sequence_order'] = $seq++;

                $output[] = $row;
            }
        }
    }

    echo json_encode($output);
    exit;
}
        else if ($_GET["type"] == "saveBrandProductZuma") {
      
           $input = $_POST;
         
        
     
          $sql1 = "SELECT  COUNT(id)+1 as id FROM product";
          $result1 = $conn->query($sql1);
          $row1 = $result1->fetch_assoc();
          $last_id=$row1["id"] + 1; 
     
        
        $padded_id = str_pad($last_id, 5, '0', STR_PAD_LEFT);
 
        // $prod_code = "FP".$padded_id;   //working as sop
        
        $prod_code=$input["ProductCode"];
        $Gen_prod_code=$input["genericProductCode"];
        
        $plant_id=$_GET['plant_id'];
        $pcm_id = (isset($input["packing_configuration_master_id"]) && intval($input["packing_configuration_master_id"]) > 0) ? intval($input["packing_configuration_master_id"]) : 0;
        $pcm_sql = $pcm_id > 0 ? "'".$pcm_id."'" : "NULL";
        $color_index = '';
        if (isset($input["color_index"]) && $input["color_index"] !== '') {
            $color_index = $input["color_index"];
        } else if (isset($input["apperance"])) {
            $color_index = $input["apperance"];
        }
        $product_lic = "";
        $fsc = "";
        $copp = "";
        $artwork = "";
        if(isset($_FILES['product_lic'])) {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $plant_id.$id."product_lic.".$file_ext;
            $product_lic = $file_name;
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
        
       
          
        $sql = "INSERT INTO product (product_code,category, product_name, grade,  dosage_type, dosage_form, generic_name,
      dosage_sub_type, dosage_size,dosage_shape, packing_style,
        packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under,
        manufactured_for, manufactured_type, product_lic, fsc, copp, photo, entry_by, entry_date, gtin, mfg_lic, pack_desc,
        apperance, color_index, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,plant,sale_type,expiry,product_group,market_group,asin_no,
        nrv_value,sku_no,packing_charges,retain_qty,retest,max_qty,min_qty,mfg_charges,unit,configuration,primary_packing,primary_subtype,ismono
        ,primary_qty,unit_wt,mono_qty,isouter,outer_qty,fssai_number,shipper_qty,packing_configuration_master_id,plant_id,dose_unit_type,dose_unit_qty,dose_unit_qty_unit,
        punch_tool,cp_machine_name,change_part,artwork,mopcup,artwork_file,mopcup_file,short_code,batch_type,brand_generic,capsule_size,capsule_printing,
        genericProductCode,Strength,Fillvolumn,safety_instructions,other_description,product_type,pv_blister,
Combination,
Blister) VALUES
        ('".$prod_code."','".$input["category"]."', '".$input["product_name"]."', '".$input["grade"]."',
        '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."',
        '".$input["dosage_sub_type"]."', '".$input["dosage_size"]."', '".$input["dosage_shape"]."',
        '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', 
        '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."',
        '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', 
        '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc','$copp', '$photo',
        '".$_GET["emp_id"]."', '$entry_date', '".$input["gtin"]."',
        '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$color_index."',
        '".$input["storage_condition"]."', '".$input["type"]."', '".$input["mrp"]."', '".$input["similar_name"]."',
        '".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,'".$input["plant"]."',
        '".$input["sale_type"]."','".$input["expiry"]."','".$input["product_group"]."','".$input["market_group"]."',
        '".$input["asin_no"]."','".$input["nrv_value"]."','".$input["sku_no"]."','".$input["packing_charges"]."',
        '".$input["retain_qty"]."','".$input["retest"]."','".$input["max_qty"]."','".$input["min_qty"]."',
        '".$input["mfg_charges"]."','".$input["unit"]."','".$input["configuration"]."','".$input["primary_packing"]."',
        '".$input["primary_subtype"]."','".$input["ismono"]."','".$input["primary_qty"]."','".$input["unit_wt"]."',
        '".$input["mono_qty"]."','".$input["isouter"]."','".$input["outer_qty"]."','".$input["fssai_number"]."','".$input["shipper_qty"]."',".$pcm_sql.",'".$_GET["plant_id"]."',
        '".$input["dose_unit_type"]."','".$input["dose_unit_qty"]."','".$input["dose_unit_qty_unit"]."',
        '".$input["punch_tool"]."','".$input["cp_machine_name"]."','".$input["change_part"]."','".$input["artworkList"]."',
        '".$input["list"]."', '$artwork_file', '$mopcup_file','".$input["short_code"]."','".$input["batch_type"]."',
        '".$input["brand_generic"]."','".$input["capsule_size"]."','".$input["capsule_printing"]."','".$input["genericProductCode"]."',
        '".$input["Strength"]."','".$input["Fillvolumn"]."','".$input["Precautions"]."','".$input["Description"]."','".$input["Product_type"]."','".$input["pv_blister"]."','".$input["Combination"]."','".$input["Blister"]."')";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getMfrs") {
        
        $output = Array();
        $sql = "SELECT product_code,mfr_no FROM unitformula WHERE plant_id = '".$_GET["plant_id"]."'";
        if($result = $conn->query($sql))
        {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        }
        echo json_encode($output);
    }
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
      else if ($_GET["type"] == "getBrandProductsNameZuma") {
    
                
                
                $output1 = Array();
                        $sql1 = "SELECT * FROM product_brand_name  WHERE product_code='".$_GET["product_code"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
          
        echo json_encode($output1);
    } 
     else if ($_GET["type"] == "getBrandProductsLogZuma") {
        $output = Array();
        $sql = "SELECT p.*, pcm.configuration_title AS packing_config_master_title 
            FROM product p 
            LEFT JOIN packing_configuration_master pcm ON p.packing_configuration_master_id = pcm.id 
            WHERE p.plant_id='".$_GET["plant_id"]."' ORDER BY 1 DESC";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output1 = Array();
                        $sql1 = "SELECT * FROM product_brand_name  WHERE product_code='".$row["product_code"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                
                
                
                $row["equivalents"] = json_decode($row["equivalent"]);
                foreach ($row as $k => $v) {
                    // Avoid utf8 warnings for null/non-string values; only normalize plain strings.
                    if (is_string($v)) {
                        $row[$k] = utf8_encode($v);
                    }
                }
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["artwork"] = json_decode($row["artwork"]);
                $row["mopcup"] = json_decode($row["mopcup"]);
                  $row["brand_name"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "saveBioProduct") {
        
        $sql = "INSERT INTO product ( plant_id, category,product_code,product_code1,product_name,structure,material,counting,size_usp, needle_attachment,tensile_strength, 
        absorption_profile,entry_by, entry_date) values ( '".$_GET["plant_id"]."','".$input["category"]."','".$input["product_code"]."','".$input["product_code"]."',
        '".$input["product_name"]."','".$input["structure"]."','".$input["material"]."','".$input["counting"]."','".$input["size_usp"]."',
        '".$input["needle_attachment"]."','".$input["tensile_strength"]."','".$input["absorption_profile"]."','".$_GET["emp_id"]."','$entry_date')";
     
        if($conn->query($sql)){
                   	echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status4\":\"".$conn->error."\"}";
        }
        
    }
        else if($_GET["type"] == "check_material_code") {
    $material_code =  $_GET['material_code'];

    // Query to check if the material code exists
    $sql = "SELECT * FROM product WHERE product_code='$material_code'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // If rows are found, return "already exists"
        echo "{\"status\":\"already exists\"}";
    } else if ($result) {
        // If no rows are found, return success
        echo "{\"status\":\"success\"}";
    } else {
        // If there is a query error, return the error
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
    
    
    
        else if ($_GET["type"] == "saveBioProduct") {
               
           
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
          
          $result = $conn->query($sql1);
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
        else if ($_GET["type"] == "getProductForSalesOrder") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  order by product_code ASC";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row['productName'] =  $row['product_name']." ".$row['product_code']." ".$row['color_index'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

    
    
    else if ($_GET["type"] == "getProductsLog") {
        $output = Array();
           $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'   ORDER BY `id` DESC";
            //  $sql = "SELECT p.*,c.LglNm as company ,c.client_code FROM product p LEFT JOIN client c ON p.manufactured_for=c.client_code 
            //                   WHERE p.status='approved'";
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
    else if ($_GET["type"] == "getRole") {
        $output = Array();
        $sql = "SELECT * FROM rational_role";
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_staps") {
        $output = Array();
        $sql = "SELECT * FROM manufacturing_process where plant_id = '".$_GET["plant_id"]."'";
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveStages") {
        
                    
     $sql = "INSERT INTO pr_stages (plant_id,entry_by,entry_date,dosage_form,stage_for,stage) VALUES
     ('".$_GET["plant_id"]."','".$_GET["emp_id"]."','$entry_date','".$input["dosage_form"]."','".$input["dosage_for"]."','".$input["stage"]."')";
     
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    	
    }
    else if ($_GET["type"] == "get_stages") {
        $output = Array();
        $sql = "SELECT * FROM pr_stages where plant_id = '".$_GET["plant_id"]."'";
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "Save_Role") {
               
               
     $sql = "INSERT INTO rational_role (role,entry_by,entry_date,plant_id) VALUES ('".$input["role"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    
    
    else if ($_GET["type"] == "saveGrades") {
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
        $sql = "SELECT * FROM grade WHERE status='active' and plant_id = '".$_GET["plant_id"]."' order by grade";
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
    // else if ($_GET["type"] == "getProductsByDosageFormMeha") {
    //       $output = Array();
    //          $sql = "SELECT *  FROM product WHERE  product_type='".$_GET["product_type"]."' 
    //         AND  plant_id='".$_GET["plant_id"]."' order by product_name";
            
    //         $result = $conn->query($sql);
    //         if ($result->num_rows > 0) {
    //             while ($row = $result->fetch_assoc()) {
                    
    //                 $output1 = array();
    //                 $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC LIMIT 1";
    //                 $result1 = $conn->query($sql1);
    //                 if ($result1->num_rows > 0) {
    //                     while ($row1 = $result1->fetch_assoc()) {
    //                         $row["mrp"] = $row1["mrp"];
    //                     }
    //                 }
    //                 $output[] = $row;
    //             }
    //         }
    //         echo json_encode($output);
    // }
    else if ($_GET["type"] == "getProductsByDosageFormMeha") {
          $output = Array();
             $sql = "SELECT *  FROM product WHERE    plant_id='".$_GET["plant_id"]."' order by product_name";
            
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
    else if ($_GET["type"] == "get_dosage_Form") {
        $output = Array();
       $sql = "SELECT dosage_form FROM `product`  WHERE plant_id = '".$_GET["plant_id"]."' group by dosage_form ";// ORDER BY dosage_form_type";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_dosage_Form") {
        $output = Array();
       $sql = "Select   dosage_form from product WHERE dosage_form = '".$_GET["plant_id"]."' group by dosage_form";// ORDER BY dosage_form_type";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
       else if ($_GET["type"] == "getBrandProductsMarktingLog") {
        $output = Array();
         $sql = "SELECT *
                        FROM product
                        WHERE plant_id = '".$_GET["plant_id"]."'
                          AND manufactured_under = '".$_GET["client_code"]."'
                          AND product_code IN (
                                SELECT a.product_code
                                FROM batch_formula_info a
                                WHERE a.product_code = product.product_code
                                  AND a.status = 'Approve'
                             )
                              ORDER BY id DESC;
                                    ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["pack_sizes"] = json_decode($row["pack_sizes"]); 
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
    else if ($_GET["type"] == "getApprovedGenericProductsList") {
        $output = Array();
        $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string(trim($_GET["plant_id"])) : '';
        if ($plant_id === '') {
            echo json_encode($output);
        } else {
        $sql = "SELECT id, product_code, product_name, generic_name, genericProductCode, grade, dosage_type, dosage_form, product_type, brand_generic
                FROM product
                WHERE plant_id='".$plant_id."'
                AND (status IS NULL OR (status != 'DELETED' AND status != 'Absolute' AND status != 'In-Active'))
                AND (
                    product_type = 'Generic Product'
                    OR ((product_type IS NULL OR product_type = '') AND brand_generic LIKE '%Generic%')
                )
                ORDER BY product_name, generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        }
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
        if (!isset($_GET["market_type"])) {
            $_GET["market_type"] = "";
        }
        if (!isset($_GET["product_type"])) {
            $_GET["product_type"] = "";
        }
        $output = Array();
        $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string(trim($_GET["plant_id"])) : '';
        $sql = "SELECT * FROM product WHERE plant_id='".$plant_id."' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%'";
        if ($_GET["grade"] !== "") {
            $sql .= " AND grade LIKE '%".$_GET["grade"]."%'";
        }
        if ($_GET["brand_generic"] !== "") {
            $sql .= " AND brand_generic LIKE '%".$_GET["brand_generic"]."%'";
        }
        if ($_GET["market_type"] !== "") {
            $sql .= " AND market LIKE '%".$_GET["market_type"]."%'";
        }
        if ($_GET["product_type"] !== "") {
            $legacyBrandGeneric = ($_GET["product_type"] === "Brand") ? "Brand" : "Generic";
            $sql .= " AND (product_type LIKE '%".$_GET["product_type"]."%' OR ((product_type IS NULL OR product_type = '') AND brand_generic LIKE '%".$legacyBrandGeneric."%'))";
        }
        $sql .= " ORDER BY product_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
          
        $sql1 = "SELECT AUTO_INCREMENT as next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product'";
        $result1 = $conn->query($sql1);
        $row1 = $result1->fetch_assoc();
        $next_id = $row1["next_id"];
        
        // Pad the numeric part to 4 digits
        $number = str_pad($next_id, 4, '0', STR_PAD_LEFT);
        
        // Build product code: Example => PABC0007
        $product_code = "P"  . $number;
         
    $input = $_POST;
         $sql = "INSERT INTO `product`(`plant_id`, `product_code`, `product_type`, `product_name`, `generic_name`,`copy_from`,`market_type`, `category`, `dosage_form`, `dosage_type`, 
        `manufactured_under`, `manufactured_for`, `shelf_life`,`unit`, `apperance`, `fregrence`, `hsn`, `gst`, `fillVolWeight`, `fillVolWeightUnit`, `pack_sizes`, 
        `storage_condition`, `status`, `entry_by`, `entry_date`,`product_nature`) VALUES ('".$_GET["plant_id"]."','".$product_code."','".$input["product_type"]."', 
        '".$input["product_name"]."', '".$input["generic_name"]."', '".$input["copy_from"]."', '".$input["market_type"]."', '".$input["category"]."', '".$input["dosage_form"]."', '".$input["dosage_type"]."',
        '".$input["manufactured_under"]."', '".$input["manufactured_for"]."', '".$input["shelf_life"]."','".$input["unit"]."', '".$input["apperance"]."','".$input["fregrence"]."', 
        '".$input["hsn"]."','".$input["gst"]."', '".$input["fillVolWeight"]."', '".$input["fillVolWeightUnit"]."', '".$input["pack_sizes"]."',
        '".$input["storage_condition"]."', 'Pending', '".$_GET["emp_id"]."', '$entry_date','".$input["dosage_type"]."' )";
    
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
     else if ($_GET["type"] == "approveProduct") {
         
       $sql="UPDATE product SET approve_by = '".$_GET["emp_id"]."',approve_date = '$entry_date',status = 'For_Account_Approval' 
       WHERE id ='".$_GET["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "approveProductFromAccounts") {
         
        $sql="UPDATE product SET accApprovedBy = '".$_GET["emp_id"]."',accApprovedOn = '$entry_date',status = 'For_QC_Approval' 
        WHERE id ='".$_GET["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "approveProductFromQc") {
         
        $sql="UPDATE product SET qcApprovedBy = '".$_GET["emp_id"]."',qcApprovedOn = '$entry_date',status = 'For_QA_Approval' 
        WHERE id ='".$_GET["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "approveProductFromQa") {
         
        $sql="UPDATE product SET qaApprovedBy = '".$_GET["emp_id"]."',qaApprovedOn = '$entry_date',status = 'Approved' 
        WHERE id ='".$_GET["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     
   
  
    else if ($_GET["type"] == "getBrandProductsLog") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["pack_sizes"] = json_decode($row["pack_sizes"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getBrandProductsLogForAccountApproval") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."' AND status = 'For_Account_Approval' order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["pack_sizes"] = json_decode($row["pack_sizes"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getBrandProductsLogForQcApproval") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  AND status = 'For_QC_Approval' order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["pack_sizes"] = json_decode($row["pack_sizes"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getBrandProductsLogForQaApproval") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."'  AND status = 'For_QA_Approval' order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["pack_sizes"] = json_decode($row["pack_sizes"]); 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    
    else if ($_GET["type"] == "getBrandProductsLogByClient") {
        $output = Array();
        $sql = "SELECT p.*,(select LglNm from client where client_code = p.manufactured_under ) as manufactured_underName,
        (select LglNm from client where client_code = p.manufactured_for ) as manufactured_forName
        FROM product p  
        WHERE p.plant_id='".$_GET["plant_id"]."' AND p.status = 'Approved' AND p.manufactured_under = '".$_GET["emp_id"]."'  order by p.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductForApproval") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."' AND status = 'Pending'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductApprovedProduct") {
        $output = Array();
        $sql = "SELECT id,plant_id,product_name,product_code FROM product  WHERE plant_id='".$_GET["plant_id"]."' AND status = 'Approved'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductByDosageTypeForm_uom") {
        $output = Array();
         $sql = "SELECT * FROM product  WHERE   dosage_type = '".$_GET["dosage_type"]."' 
        AND product_type = '".$_GET["dosage_form"]."' 
        AND category = '".$_GET["category"]."' AND plant_id = '".$_GET["plant_id"]."'  order by product_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductByDosageTypeForm") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE product_type = '".$_GET["product_type"]."' AND dosage_type = '".$_GET["dosage_type"]."' 
        AND dosage_form = '".$_GET["dosage_form"]."' 
        AND category = '".$_GET["category"]."' AND plant_id = '".$_GET["plant_id"]."'  order by product_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductByCategory") {
        $output = Array();
        $sql = "SELECT id,plant_id,product_code,product_name FROM product  WHERE category = '".$_GET["category"]."' AND plant_id = '".$_GET["plant_id"]."'  order by product_name ASC";
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
    else if ($_GET["type"] == "getProductsForMaterialsMaster") {
        $output = Array();
         $sql = "SELECT id,plant_id,product_code,product_name FROM product  WHERE status = 'Approved' AND plant_id='".$_GET["plant_id"]."' order by product_name ASC";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
    else if ($_GET["type"] == "getProductCopyList") {
        $output = Array();
        $sql = "SELECT id, product_code, product_name, generic_name, dosage_type, dosage_form, grade
                FROM product
                WHERE plant_id='".$_GET["plant_id"]."' AND status!='DELETED'
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getProductCopyTemplate") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE id='".$_GET["id"]."' AND plant_id='".$_GET["plant_id"]."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["label_claim"] = json_decode($row["label_claim"]);
                $output = $row;
                break;
            }
        }
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
    // else if ($_GET["type"] == "editProduct") {
    // echo    $sql = "UPDATE product SET product_name='".$input["product_name"]."',product_type='".$input["product_type"]."', grade='".$input["grade"]."', product_apperance='".$input["product_apperance"]."', storage_condition='".$input["storage_condition"]."', manufactured_under='".$input["manufactured_under"]."', manufactured_for='".$input["manufactured_for"]."',type='".$input["type"]."' WHERE id='".$input["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    
    
    else if ($_GET["type"] == "editProduct") {
         $input = $_POST;
        $pcm_edit = (isset($input["packing_configuration_master_id"]) && intval($input["packing_configuration_master_id"]) > 0) ? intval($input["packing_configuration_master_id"]) : 0;
        $pcm_edit_sql = $pcm_edit > 0 ? "'".$pcm_edit."'" : "NULL";
        
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
          $plant_id=$_GET['plant_id'];
        $number = str_pad($id, 4, '0', STR_PAD_LEFT);
        $prod_code = "P".$number; 
        
        
        // $product_lic = "";
        // $fsc = "";
        // $copp = "";
        // $artwork = "";
        $product_lic = '';
        $artwork_file = '';
        $mopcup_file = '';

        if(isset($_FILES['product_lic']) && isset($_FILES['product_lic']['name']) && $_FILES['product_lic']['name'] !== '') {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $plant_id.$id."product_lic.".$file_ext;
            $product_lic = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        // if(isset($_FILES['fsc'])) {
        //     $file_tmp =$_FILES['fsc']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
        //     $file_name = $id."fsc.".$file_ext;
        //     $fsc = $file_name;
        //     move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        // }
        
        // if(isset($_FILES['copp'])) {
        //     $file_tmp =$_FILES['copp']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
        //     $file_name = $plant_id.$id."copp.".$file_ext;
        //     $copp = $file_name;
        //     move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        // }
        // if(isset($_FILES['photo'])) {
        //     $file_tmp =$_FILES['photo']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
        //     $file_name = $plant_id.$id."photo.".$file_ext;
        //     $photo = $file_name;
        //     move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        // }
        // if(isset($_FILES['artwork_file'])) {
        //     $file_tmp =$_FILES['artwork_file']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['artwork_file']['name'])));
        //     $file_name = $plant_id.$id."artwork_file.".$file_ext;
        //     $artwork_file = $file_name;
        //     move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        // }
        // if(isset($_FILES['mopcup_file'])) {
        //     $file_tmp =$_FILES['mopcup_file']['tmp_name'];
        //     $file_ext=strtolower(end(explode('.',$_FILES['mopcup_file']['name'])));
        //     $file_name = $plant_id.$id."mopcup_file.".$file_ext;
        //     $mopcup_file = $file_name;
        //     move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        // }
        
        
     
     $sql = "UPDATE  product SET product_lic = IF('".$product_lic."'='', product_lic, '".$product_lic."'), pack_desc='".$input["pack_desc"]."',
        unit='".$input["unit"]."',configuration='".$input["configuration"]."',primary_packing='".$input["primary_packing"]."',
        primary_subtype='".$input["primary_subtype"]."',ismono='".$input["ismono"]."'
        ,primary_qty='".$input["primary_qty"]."',unit_wt='".$input["unit_wt"]."',mono_qty='".$input["mono_qty"]."',
        isouter='".$input["isouter"]."',outer_qty='".$input["outer_qty"]."',fssai_number='".$input["fssai_number"]."',
        shipper_qty='".$input["shipper_qty"]."',plant_id='".$_GET["plant_id"]."',dose_unit_type='".$input["dose_unit_type"]."',
        dose_unit_qty='".$input["dose_unit_qty"]."',dose_unit_qty_unit='".$input["dose_unit_qty_unit"]."',punch_tool='".$input["punch_tool"]."',
        cp_machine_name='".$input["cp_machine_name"]."',change_part='".$input["change_part"]."',artwork='".$input["artworkList"]."',
        mopcup='".$input["list"]."',artwork_file=IF('".$artwork_file."'='', artwork_file, '".$artwork_file."'),mopcup_file=IF('".$mopcup_file."'='', mopcup_file, '".$mopcup_file."'),short_code='".$input["short_code"]."',
        batch_type='".$input["batch_type"]."',packing_type='".$input["packing_type"]."', 	secondary_packing='".$input["secondary_packing"]."',
        pack_sizes='".$input["pack_size"]."',nos_pouch='".$input["no_pouch"]."',capsule_size='".$input["capsule_size"]."',
        mono_cartain='".$input["mono_cartain"]."',master_mono_qty='".$input["master_mono_qty"]."' ,tertiary_packing ='".$input["tertiary_packing"]."',
        tertiary_packing_data='".$input["tertiary_packing_data"]."',scoop_add='".$input["scoop_add"]."' ,Shrink='".$input["shrink"]."',
        wad_sealing='".$input["wad_sealing"]."', 	master_cartain='".$input["master_cartain"]."',
        packing_configuration_master_id=".$pcm_edit_sql."
        WHERE id='".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
    
    else if ($_GET["type"] == "saveBrand") {
        
                $json_obj = json_encode($input["brand_name_list"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql = "INSERT INTO product_brand_name (product_code, brand_name,brand_product_code,plant_id)
       VALUES ('".$input["product_code"]."','".$values["brand_name"]."','".$values["product_code"]."','".$_GET["plant_id"]."')";
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
    else if ($_GET["type"] == "deleteProduct") {
        $sql = "Update product SET status='DELETED' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "downloadProductsLog") {
        $_GET['filename'] = 'Products'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:cenetr">Products</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:10%;">Product Type</td>
                    <td style="width:13%;">Product Code</td>
                    <td style="width:12%;">Type</td>
                    <td style="width:17%;">Product Name</td>
                    <td style="width:8%;">Grade</td>
                    <td style="width:17%;">product Apperance</td>
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
                            <td style="width:10%;">'.$row['dosage_type'].'</td>
                            <td style="width:13%;">'.$row['product_code'].'</td>
                            <td style="width:12%;">'.$row['type'].'</td>
                            <td style="width:17%;">'.$row['product_name'].'</td>
                            <td style="width:8%;">'.$row['grade'].'</td>
                            <td style="width:17%;">'.$row['brand_generic'].'</td>
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
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $_GET['filename'] = 'Products';
        $_GET['pdftype'] = 'onlyheader';
        include("../pdfimp2.php");

        $esc = function ($value) {
            $text = trim((string)$value);
            if ($text === '') {
                return 'NA';
            }
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        };
        $fmtDate = function ($value) use ($esc) {
            if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return 'NA';
            }
            $ts = strtotime($value);
            return $ts ? date('d-m-Y', $ts) : $esc($value);
        };

        $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string(trim((string)$_GET['plant_id'])) : '';
        $sql = "SELECT p.*,
                pl.plant_full_name,
                TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))) AS entry_by_name
                FROM product p
                LEFT JOIN plant pl ON CAST(pl.plant_id AS CHAR) = CAST(p.plant_id AS CHAR)
                LEFT JOIN employee e ON CAST(e.emp_id AS CHAR) = CAST(p.entry_by AS CHAR)
                WHERE (p.status IS NULL OR (p.status != 'DELETED' AND p.status != 'Absolute' AND p.status != 'In-Active'))";
        if ($plantId !== '') {
            $sql .= " AND p.plant_id = '".$plantId."'";
        }
        $sql .= " ORDER BY p.id DESC";

        $html = '
        <h2 style="text-align:center">Finished Products</h2>
        <table border="1" cellpadding="4" cellspacing="0" width="100%">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <td width="6%" align="center">Sr No</td>
                    <td width="11%" align="center">Product Code</td>
                    <td width="16%" align="center">Product Name</td>
                    <td width="10%" align="center">Nature of Dose</td>
                    <td width="10%" align="center">Dosage Form</td>
                    <td width="8%" align="center">Shape</td>
                    <td width="8%" align="center">Size</td>
                    <td width="8%" align="center">Grade</td>
                    <td width="8%" align="center">Shelf Life</td>
                    <td width="8%" align="center">Entry Date</td>
                    <td width="7%" align="center">Entry By</td>
                </tr>
            </thead>';

        $result = @$conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $entryBy = trim(isset($row['entry_by_name']) ? $row['entry_by_name'] : '');
                if ($entryBy === '' || preg_match('/^\d+$/', $entryBy)) {
                    $entryBy = isset($row['entry_by']) ? $row['entry_by'] : '';
                }
                $html .= '<tr nobr="true">
                    <td width="6%" align="center">'.$i.'.</td>
                    <td width="11%">'.$esc(isset($row['product_code']) ? $row['product_code'] : '').'</td>
                    <td width="16%">'.$esc(isset($row['product_name']) ? $row['product_name'] : '').'</td>
                    <td width="10%">'.$esc(isset($row['dosage_type']) ? $row['dosage_type'] : '').'</td>
                    <td width="10%">'.$esc(isset($row['dosage_form']) ? $row['dosage_form'] : '').'</td>
                    <td width="8%">'.$esc(isset($row['dosage_size']) ? $row['dosage_size'] : '').'</td>
                    <td width="8%">'.$esc(isset($row['dosage_sub_type']) ? $row['dosage_sub_type'] : '').'</td>
                    <td width="8%">'.$esc(isset($row['grade']) ? $row['grade'] : '').'</td>
                    <td width="8%">'.$esc(isset($row['shelf_life']) ? $row['shelf_life'] : '').'</td>
                    <td width="8%">'.$fmtDate(isset($row['entry_date']) ? $row['entry_date'] : '').'</td>
                    <td width="7%">'.$esc($entryBy).'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="11" align="center">No product records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BrandProductLog.pdf', 'I');
        exit;
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