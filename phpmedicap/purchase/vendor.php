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

    function ensureVendorCorrectionLogColumn($conn) {
        $r = $conn->query("SHOW COLUMNS FROM vendor LIKE 'correction_log'");
        if ($r && $r->num_rows === 0) {
            $conn->query("ALTER TABLE vendor ADD correction_log TEXT NULL DEFAULT NULL");
        }
    }

    function ensureVendorStatusRemarkColumn($conn) {
        $r = $conn->query("SHOW COLUMNS FROM vendor LIKE 'status_remark'");
        if ($r && $r->num_rows === 0) {
            $conn->query("ALTER TABLE vendor ADD status_remark TEXT NULL DEFAULT NULL");
        }
    }

    function vendorJsonDecode($value) {
        if ($value === null || $value === '') {
            return array();
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        return array();
    }

    function vendorCorrectionFieldLabels() {
        return array(
            'vendor_Is' => 'Vendor / Division',
            'parentVendor' => 'Parent vendor',
            'vendor_type' => 'Vendor type',
            'material_type' => 'Material type',
            'vendor_name' => 'Vendor name',
            'contact_person' => 'Contact person',
            'contact_number' => 'Phone',
            'contact_email' => 'Email',
            'address' => 'Vendor address',
            'country' => 'Country',
            'permanent_state' => 'State / province',
            'city' => 'City',
            'pincode' => 'Postal code',
            'qualifiedBy' => 'Qualified by',
            'client_code' => 'Client code',
            'vendorFor' => 'Vendor for',
            'gst_applicable' => 'GST applicable',
            'scode' => 'State code (GST)',
            'gst_no' => 'GST no.',
            'panNo' => 'PAN no.',
            'c_unit_name' => 'Corporate unit name',
            'c_address' => 'Corporate address',
            'c_country' => 'Corporate country',
            'c_state' => 'Corporate state',
            'c_city' => 'Corporate city',
            'c_pincode' => 'Corporate postal code',
            'c_mobile_no' => 'Corporate phone',
            'c_gst_applicable' => 'Corporate GST applicable',
            'c_scode' => 'Corporate state code',
            'c_gst_no' => 'Corporate GST no.',
            'c_panNo' => 'Corporate PAN no.',
            'other_contact' => 'Other contacts',
            'selectedCurrencies' => 'Trading currencies',
        );
    }

    function vendorCorrectionNormalizeValue($value) {
        if ($value === null) {
            return '';
        }
        if (is_string($value) && $value !== '' && ($value[0] === '[' || $value[0] === '{')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return trim((string)$value);
    }

    function buildVendorCorrectionLog($oldRow, $input) {
        $labels = vendorCorrectionFieldLabels();
        $changes = array();
        foreach ($labels as $key => $label) {
            $oldVal = isset($oldRow[$key]) ? $oldRow[$key] : '';
            $newVal = isset($input[$key]) ? $input[$key] : '';
            if ($key === 'selectedCurrencies') {
                $oldVal = isset($oldRow['currency']) ? $oldRow['currency'] : '';
                $newVal = isset($input['selectedCurrencies']) ? $input['selectedCurrencies'] : '';
            }
            $oldNorm = vendorCorrectionNormalizeValue($oldVal);
            $newNorm = vendorCorrectionNormalizeValue($newVal);
            if ($oldNorm !== $newNorm) {
                $changes[] = array(
                    'field' => $key,
                    'label' => $label,
                    'old' => $oldNorm,
                    'new' => $newNorm,
                );
            }
        }
        return $changes;
    }

    
    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()) {
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
      $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    if ($_GET["type"] == "saveVendor") {
        $input = $_POST;
        if ($input["gst_applicable"] == "Applicable") {
            $sql = "SELECT id FROM vendor WHERE gst_no='".$input["gst_no"]."'";
            $result = $conn->query($sql);
        	if($result->num_rows > 0){
        	    echo "{\"status\":\"failed\"}";
        		return;
        	}
        }
    	
        $id = 0;
        $sql = "SELECT MAX(id) as id FROM vendor";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = array();
    		while($row = $result->fetch_assoc()){
    		    $id = $row["id"];
    		}
    	}
    	$id += 1;
    	$vendor_no= "V-00".$id;
    	
    	$target_dir = "../upload/vendor/";
    	$gst_certificate = "";
    	$mfg_lic_file = "";
    	$supplier_lic = "";
    	$incorporation_certificate = "";
    	$pan_card = "";
    	
    	if (isset($_FILES["gst_certificate"]["name"])) {
        	$target_file = $target_dir.$vendor_no."-gst_certificate.".pathinfo(basename($_FILES["gst_certificate"]["name"]), PATHINFO_EXTENSION);
        	$gst_certificate = $vendor_no."-gst_certificate.".pathinfo(basename($_FILES["gst_certificate"]["name"]), PATHINFO_EXTENSION);
        	move_uploaded_file($_FILES["gst_certificate"]["tmp_name"], $target_file);
    	}
    	if (isset($_FILES["mfg_lic_file"]["name"])) {
        	$target_file = $target_dir.$vendor_no."-mfg_lic_file.".pathinfo(basename($_FILES["mfg_lic_file"]["name"]), PATHINFO_EXTENSION);
        	$mfg_lic_file = $vendor_no."-mfg_lic_file.".pathinfo(basename($_FILES["mfg_lic_file"]["name"]), PATHINFO_EXTENSION);
        	move_uploaded_file($_FILES["mfg_lic_file"]["tmp_name"], $target_file);
    	}
    	if (isset($_FILES["supplier_lic"]["name"])) {
        	$target_file = $target_dir.$vendor_no."supplier_lic.".pathinfo(basename($_FILES["supplier_lic"]["name"]), PATHINFO_EXTENSION);
        	$supplier_lic = $vendor_no."supplier_lic.".pathinfo(basename($_FILES["supplier_lic"]["name"]), PATHINFO_EXTENSION);
        	move_uploaded_file($_FILES["supplier_lic"]["tmp_name"], $target_file);
    	}
    	if (isset($_FILES["incorporation_certificate"]["name"])) {
        	$target_file = $target_dir.$vendor_no."-incorporation_certificate.".pathinfo(basename($_FILES["incorporation_certificate"]["name"]), PATHINFO_EXTENSION);
        	$incorporation_certificate = $vendor_no."-incorporation_certificate.".pathinfo(basename($_FILES["incorporation_certificate"]["name"]), PATHINFO_EXTENSION);
        	move_uploaded_file($_FILES["incorporation_certificate"]["tmp_name"], $target_file);
    	}
    	if (isset($_FILES["pan_card"]["name"])) {
        	$target_file = $target_dir.$vendor_no."-pan_card.".pathinfo(basename($_FILES["pan_card"]["name"]), PATHINFO_EXTENSION);
        	$pan_card = $vendor_no."-pan_card.".pathinfo(basename($_FILES["pan_card"]["name"]), PATHINFO_EXTENSION);
        	move_uploaded_file($_FILES["pan_card"]["tmp_name"], $target_file);
    	}
     
        
       $sql = "INSERT INTO vendor(plant_id,vendor_type,material_type, vendor_for, vendor_name,manufacturer_code,country,state_code,state_name,
       gst_registered,gst_type,gst_no,pan_no,mfg_lic,email,mobile_no, tel_no1, tel_no2, fax, website, contact_person,contact_number,
       contact_email,comp_pan_no,address, gst_certificate, mfg_lic_file,vendor_status,entry_by,entry_date,city,unit_name,pincode,units_data,
       c_unit_name,c_address,c_country,c_state,c_city,c_pincode,c_mobile_no,c_gst_applicable,c_gst_type,c_gst_no,scode) VALUES (
       '".$_GET["plant_id"]."','".$input["vendor_type"]."','".$input["material_type"]."' ,'".$input["vendor_for"]."',
        '".$input["vendor_name"]."','".$input["manufacturer_code"]."','".$input["country"]."','".$input["state_name"]."',
        '".$input["permanent_state"]."','".$input["gst_type"]."','".$input["gst_type"]."','".$input["gst_no"]."','".$input["pan_no"]."',
        '".$input["mfg_lic"]."','".$input["email"]."','".$input["mobile_no"]."','".$input["tel_no1"]."','".$input["tel_no2"]."',
        '".$input["fax"]."', '".$input["website"]."', '".$input["contact_person"]."','".$input["contact_no"]."','".$input["contact_email"]."',
        '".$input["comp_pan_no"]."', '".$input["address"]."', '$gst_certificate','$mfg_lic_file', '".$input["vendor_status"]."',
        '".$_GET["emp_id"]."','".$entry_date."','".$input["city"]."','".$input["unit_name"]."','".$input["pincode"]."','".$input["addl_units"]."',
        '".$input["c_unit_name"]."','".$input["c_address"]."','".$input["c_country"]."','".$input["c_state"]."','".$input["c_city"]."',
        '".$input["c_pincode"]."','".$input["c_mobile_no"]."','".$input["c_gst_applicable"]."','".$input["c_gst_type"]."',
        '".$input["c_gst_no"]."',".$input["scode_gst"].")";
     
            if($conn->query($sql)) {
	            $last_id = $conn->insert_id;
		        echo "{\"status\":\"success\"}";
            } else {
		        echo "{\"status\":\"".$conn->error."\"}";
            }
            
    	            
    } 
                    
    else if ($_GET["type"] == "update_vendor") {
        
        
        $vendor_no = $_GET["vendor_no"];
        $plant_id = $_GET["plant_id"];
        
        if(isset($_FILES["pan_card"])) {
            $file_tmp =$_FILES['pan_card']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['pan_card']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."pan_card.".$file_ext;
            $pan_card = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["gst_certificate"])) {
            $file_tmp =$_FILES['gst_certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['gst_certificate']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."gst_certificate.".$file_ext;
            $gst_certificate = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["compReg_certificate"])) {
            $file_tmp =$_FILES['compReg_certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['compReg_certificate']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."compReg_certificate.".$file_ext;
            $compReg_certificate = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["usfda_certificate"])) {
            $file_tmp =$_FILES['usfda_certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['usfda_certificate']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."usfda_certificate.".$file_ext;
            $usfda_certificate = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["eu_certificate"])) {
            $file_tmp =$_FILES['eu_certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['eu_certificate']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."eu_certificate.".$file_ext;
            $eu_certificate = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["iso_certificate"])) {
            $file_tmp =$_FILES['iso_certificate']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['iso_certificate']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."iso_certificate.".$file_ext;
            $iso_certificate = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["drug_lic"])) {
            $file_tmp =$_FILES['drug_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['drug_lic']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."drug_lic.".$file_ext;
            $drug_lic = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $vendor_no.'-'.$plant_id."mfg_lic.".$file_ext;
            $mfg_lic = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/vendor/".$file_name);
        }
        
        
      
        
        
        $sql = "UPDATE  vendor  SET   gst_certificate ='$gst_certificate', mfg_lic_file ='$mfg_lic',
                compReg_certificate ='$compReg_certificate', usfda_certificate ='$usfda_certificate', eu_certificate ='$eu_certificate',
                iso_certificate ='$iso_certificate', drug_lic ='$drug_lic', pan_card ='$pan_card'  
                WHERE vendor_no ='".$_GET["vendor_no"]."' AND plant_id ='".$_GET["plant_id"]."'  ";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
         else if ($_GET["type"] == "changeVendorStatus") {
        if (!isset($input["change_status"]) || !isset($input["id"])) {
            echo "{\"status\":\"invalid\"}";
            return;
        }
        $st = $conn->real_escape_string($input["change_status"]);
        $id = $conn->real_escape_string($input["id"]);
        $remark = isset($input["status_remark"]) ? $conn->real_escape_string($input["status_remark"]) : '';
        $sql = "UPDATE vendor SET status='".$st."'";
        if ($remark !== '') {
            $sql .= ", status_remark='".$remark."'";
        }
        $sql .= " WHERE id='".$id."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    } 
    else if ($_GET["type"] == "UploadDocuments") {
        
        
        $input = $_POST;
        
        
        $vid = $input["id"];
        $pid = $_GET["plant_id"];
        $docName = $input["docName"];
        
        
        $vendorResponse = '';
        if (isset($_FILES["docFile"])) {
            $file_tmp = $_FILES['docFile']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['docFile']['name'])));
            $vendorResponse = $pid.$vid.$docName.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/vendor/" . $vendorResponse);
        }else{
            $vendorResponse = 'NA';
        }
         
        
         $sql = "UPDATE docUploadByVendor SET docFile = '$vendorResponse' , entryBy = '$entry_date', entryOn = '".$_GET["emp_id"]."'
        WHERE id='".$input["id"]."'";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
        
    } 
    else if ($_GET["type"] == "getPendingVendorsQA1") {
        $output = Array();
          $sql = "SELECT * FROM vendor   WHERE  plant_id='".$_GET["plant_id"]."'  AND status = 'Checking'   order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);}
    else if ($_GET["type"] == "getPendingVendorsQA") {
    
        $output = Array();
          $sql = "SELECT * FROM vendor   WHERE  plant_id='".$_GET["plant_id"]."'   AND status = 'Checking'   order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingVendorsQAForUpload") {
    
        $output = Array();
          $sql = "SELECT * FROM vendor   WHERE  plant_id='".$_GET["plant_id"]."' AND vendorResponse = 'Done'  AND status = 'Checking'   order by 1 desc";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingDocToUpload") {
    
        $output = Array();
          $sql = "SELECT * FROM docUploadByVendor   WHERE  plant_id = '".$_GET["plant_id"]."' AND docFile = 'NA'  AND vId = '".$_GET["vid"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getUploadedDoc") {
    
        $output = Array();
          $sql = "SELECT * FROM docUploadByVendor   WHERE  plant_id = '".$_GET["plant_id"]."' AND docFile != 'NA'  AND vId = '".$_GET["vid"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "saveVendorDocument") {
        
        
  $input=$_POST;
                $plant_id = $_GET["plant_id"];
                $vendor_no = $input['vendor_no'];
                $document_name = $input['document_name'];
            
          $target_dir = "../../../upload/vendor_document/";
    
    $file_name = "";
    if(isset($_FILES["photo"]["name"])){
        $target_file = $target_dir.$vendor_no."_".$document_name."_".basename($_FILES["photo"]["name"]);
        $structure_file = $vendor_no."_".$document_name."_".basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
    }
        
        
        
        $sql="INSERT INTO `vendor_uploaded_documents`( `plant_id`,vendor_no ,`valid_date`, `Document_name`, `vendor_type`, `file`) 
                 VALUES ( '$plant_id','".$input['vendor_no']."','".$input['valid_date']."','".$input['document_name']."',
                 '".$input['vendor_type']."','$structure_file')";
                 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    else if ($_GET["type"] == "save_quotation") {
        
 
    
     $sql = "SELECT count(*)+1 as quotation_no  FROM quotation_hdr where plant_id= '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['quotation_no'];
        }
        if($last_id==null){
            $last_id=1;
        } 
        $number = substr(str_repeat(0, 4).$last_id, - 4);
        $qoute_no = "Q-".$number;
       
        $sql = "INSERT INTO quotation_hdr (plant_id,quotation_no,vendor_quotation_no,material_type, vendor_id, entry_by, entry_date)
            VALUES ('".$_GET["plant_id"]."','".$qoute_no."','".$_POST["vendor_quotation_no"]."','".$input["material_type"]."',
            '".$input["vendor_id"]."','".$_GET["emp_id"]."','".$entry_date."')";
    
     if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        
       //echo json_encode($_POST["materials"]); exit;
        $materials = $input["material_list"];
        
       // print_r($materials["igstPer"]); exit;
        // if($materials["igstPer"]=="Others") $gstPer = $materials["otheGST"] ;
        // else  $gstPer = $materials["igstPer"] ;
        for ($i = 0; $i < count($materials); $i++) {
            $material = $materials[$i];
 

         $sql1 = "INSERT INTO quotation_dtl (quotation_hdr_id,quotation_type, material_id,material_code,quotation_amt, tax_applicable, gst_per, pack_size, pack_unit, quotation_per,vendor_material_request_id) VALUES 
             ('".$last_id."','".$material["quotation_type"]."','".$material["material_id"]."','".$material["material_code"]."', '".$material["quote_amt"]."', 
            '".$material["tax_applicable"]."',  '".$material["gst"]."', '".$material["pack_size"]."', '".$material["pack_size_unit"]."', '".$material["quotation_per"]."','".$material["vendor_material_request_id"]."')"; 
            
            $conn->query($sql1);
        }
        for ($i = 0; $i < count($materials); $i++) {
            $material = $materials[$i];
 

         $sql11 = "update  vendor_material_request set status='Completed' where id='".$material["vendor_material_request_id"]."'"; 
            
            $conn->query($sql11);
        }
        echo "{\"status\":\"success\"}";
        
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }

    }
    else if ($_GET["type"] == "save_notice") {
        
                 $input = $_POST;

        $vendor_no = $input["vendor_no"];
        
        
        
        
        $sql = "SELECT COUNT(id) as ID FROM vendor_notice    WHERE    plant_id =  '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		$output = array();
    		while($row = $result->fetch_assoc()){
    		    $id = $row["ID"];
    		}
    	}
        
        
        
        $id = $id+1;
        
        
         
        if(isset($_FILES["notice_doc"])) {
            $file_tmp =$_FILES['notice_doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['notice_doc']['name'])));
            $file_name = $vendor_no.'-'.$id."NOTICE.".$file_ext;
            $notice = $file_name;
           move_uploaded_file($file_tmp,"../../../upload/vendor/notice/".$file_name);
        }
   
        
          $sql = "INSERT INTO vendor_notice(plant_id, vendor_no, notice_subject, special_note, notice_doc) VALUES (
             '".$_GET["plant_id"]."','".$input["vendor_no"]."','".$input["notice_subject"]."','".$input["special_note"]."' ,'$notice' )  ";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } 
    else if ($_GET["type"] == "approveVendorFromPurchase") {
        
        $sql = "UPDATE vendor SET status = 'TO_QA' , approve_date = '$entry_date', approve_by = '".$_GET["emp_id"]."' WHERE id='".$_GET["id"]."'";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "updateVendorStatusFromQa") {
        if (!isset($input["status"]) || !isset($input["id"])) {
            echo "{\"status\":\"invalid\"}";
            return;
        }
        $st = $conn->real_escape_string($input["status"]);
        $vid = $conn->real_escape_string($input["id"]);
        $sql = "UPDATE vendor SET status = '".$st."' , qaApproveOn = '".$entry_date."', qaApproveBy = '".$_GET["emp_id"]."'";
        if (isset($input["status_remark"]) && $input["status_remark"] !== '') {
            $sql .= ", status_remark='".$conn->real_escape_string($input["status_remark"])."'";
        }
        $sql .= " WHERE id = '".$vid."' AND status = 'TO_QA'";

        if ($conn->query($sql)) {
            if ($conn->affected_rows === 0) {
                echo "{\"status\":\"no rows updated\"}";
            } else {
                echo "{\"status\":\"success\"}";
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }

    } 
    
    else if ($_GET["type"] == "delete_vendor") {
        $sql = "DELETE FROM vendor    WHERE id='".$_GET["id"]."'";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "deleteVendor") {
        $sql = "UPDATE vendor SET status='Approved'
           WHERE id='".$_GET["id"]."'";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "update_vendor_status") {
      echo  $sql = "DELETE FROM vendor WHERE 
           WHERE id='".$_GET["id"]."'   ";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "updateVendor") {
        
        
        
     $sql = "UPDATE vendor SET vendor_type='".$input["vendor_type"]."',vendor_name='".$input["vendor_name"]."',
    contact_person='".$input["contact_person"]."', contact_number='".$input["contact_number"]."',email='".$input["email"]."',
    address='".$input["address"]."',country='".$input["country"]."', state_name='".$input["state_name"]."',city='".$input["city"]."',
    pincode='".$input["pincode"]."',c_unit_name='".$input["c_unit_name"]."',c_address='".$input["c_address"]."', c_country='".$input["c_country"]."',
    c_state='".$input["c_state"]."',c_city='".$input["c_city"]."',status = 'checked' ,c_pincode='".$input["c_pincode"]."',c_mobile_no='".$input["c_mobile_no"]."'
    WHERE vendor_no='".$_GET["vendor_no"]."'";
  
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
    } 
    else if ($_GET["type"] == "changeVendorStatus") {
        
      $sql = "UPDATE vendor SET status='".$input["change_status"]."' WHERE id='".$input["id"]."'";
  
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "updateblock") {
         $sql="UPDATE vendor SET blacklist='".$_GET["status"]."'where vendor_no='".$_GET["vendor_no"]."'";
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     else if ($_GET["type"] == "updateunblock") {
            $sql="UPDATE vendor SET blacklist='".$_GET["status"]."'where vendor_no='".$_GET["vendor_no"]."'";
            if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getPendingVendorsForPurchaseApproval") {
        
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE  plant_id='".$_GET["plant_id"]."'  AND status IN ('Pending', 'Corrected Details')   order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['other_contact'] = json_decode($row['other_contact']);
                $row['selectedCurrencies'] = (isset($row['currency']) && $row['currency'] !== null && $row['currency'] !== '')
                    ? json_decode($row['currency'])
                    : array();
                if (isset($row['correction_log']) && $row['correction_log'] !== null && $row['correction_log'] !== '') {
                    $row['correction_log'] = json_decode($row['correction_log'], true);
                } else {
                    $row['correction_log'] = array();
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getPendingVendorForQaApproval") {
        
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE  plant_id='".$_GET["plant_id"]."'  AND status = 'TO_QA'   order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['other_contact'] = json_decode($row['other_contact']);
                $row['selectedCurrencies'] = (isset($row['currency']) && $row['currency'] !== null && $row['currency'] !== '')
                    ? json_decode($row['currency'])
                    : array();
                if (isset($row['correction_log']) && $row['correction_log'] !== null && $row['correction_log'] !== '') {
                    $row['correction_log'] = json_decode($row['correction_log'], true);
                } else {
                    $row['correction_log'] = array();
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getVendorsReturnedByQaCount") {
        $cnt = 0;
        $sql = "SELECT COUNT(*) AS c FROM vendor WHERE plant_id='".$_GET["plant_id"]."' AND status='RETURN_FOR_PURCHASE_EDIT'";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $cnt = (int)$row["c"];
        }
        echo json_encode(array("count" => $cnt));
    }
    else if ($_GET["type"] == "getVendorsReturnedByQa") {
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE plant_id='".$_GET["plant_id"]."' AND status='RETURN_FOR_PURCHASE_EDIT' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['other_contact'] = json_decode($row['other_contact']);
                $row['selectedCurrencies'] = (isset($row['currency']) && $row['currency'] !== null && $row['currency'] !== '')
                    ? json_decode($row['currency'])
                    : array();
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveVendorQaReturn") {
        if (!isset($input["id"])) {
            echo "{\"status\":\"invalid\"}";
            return;
        }
        ensureVendorCorrectionLogColumn($conn);
        $id = $conn->real_escape_string($input["id"]);
        $oldRow = null;
        $oldSql = "SELECT * FROM vendor WHERE id='".$id."' AND status='RETURN_FOR_PURCHASE_EDIT' LIMIT 1";
        $oldResult = $conn->query($oldSql);
        if ($oldResult && $oldResult->num_rows > 0) {
            $oldRow = $oldResult->fetch_assoc();
        }
        if ($oldRow === null) {
            echo "{\"status\":\"no rows updated\"}";
            return;
        }
        $correctionChanges = buildVendorCorrectionLog($oldRow, $input);
        $correctionPayload = json_encode(array(
            'corrected_by' => $_GET["emp_id"],
            'corrected_on' => $entry_date,
            'qa_remark' => isset($oldRow['status_remark']) ? $oldRow['status_remark'] : '',
            'changes' => $correctionChanges,
        ));
        $correctionEsc = $conn->real_escape_string($correctionPayload);
        $fv = function($key) use ($input, $conn) {
            return isset($input[$key]) ? $conn->real_escape_string($input[$key]) : '';
        };
        $oc = '';
        if (isset($input["other_contact"])) {
            if (is_array($input["other_contact"])) {
                $oc = $conn->real_escape_string(json_encode($input["other_contact"]));
            } else {
                $oc = $conn->real_escape_string($input["other_contact"]);
            }
        }
        $sc = '';
        if (isset($input["selectedCurrencies"])) {
            if (is_array($input["selectedCurrencies"])) {
                $sc = $conn->real_escape_string(json_encode($input["selectedCurrencies"]));
            } else {
                $sc = $conn->real_escape_string($input["selectedCurrencies"]);
            }
        }
        $sql = "UPDATE vendor SET vendor_Is='".$fv('vendor_Is')."', parentVendor='".$fv('parentVendor')."',
        vendor_type='".$fv('vendor_type')."', material_type='".$fv('material_type')."', vendor_name='".$fv('vendor_name')."',
        contact_person='".$fv('contact_person')."', contact_number='".$fv('contact_number')."', contact_email='".$fv('contact_email')."',
        address='".$fv('address')."', country='".$fv('country')."', permanent_state='".$fv('permanent_state')."', city='".$fv('city')."', pincode='".$fv('pincode')."',
        qualifiedBy='".$fv('qualifiedBy')."', client_code='".$fv('client_code')."', vendorFor='".$fv('vendorFor')."',
        gst_applicable='".$fv('gst_applicable')."', scode='".$fv('scode')."', gst_no='".$fv('gst_no')."', panNo='".$fv('panNo')."',
        c_unit_name='".$fv('c_unit_name')."', c_address='".$fv('c_address')."', c_country='".$fv('c_country')."', c_state='".$fv('c_state')."',
        c_city='".$fv('c_city')."', c_pincode='".$fv('c_pincode')."', c_mobile_no='".$fv('c_mobile_no')."',
        c_gst_applicable='".$fv('c_gst_applicable')."', c_scode='".$fv('c_scode')."', c_gst_no='".$fv('c_gst_no')."', c_panNo='".$fv('c_panNo')."',
        status='Corrected Details', correction_log='".$correctionEsc."' ";
        if ($oc !== '') {
            $sql .= ", other_contact='".$oc."' ";
        }
        if ($sc !== '') {
            $sql .= ", currency='".$sc."' ";
        }
        $sql .= "WHERE id='".$id."' AND status='RETURN_FOR_PURCHASE_EDIT'";
        if ($conn->query($sql)) {
            if ($conn->affected_rows === 0) {
                echo "{\"status\":\"no rows updated\"}";
            } else {
                echo "{\"status\":\"success\"}";
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "updateVendorBankDetails") {
        if (!isset($input["id"])) {
            echo "{\"status\":\"invalid\"}";
            return;
        }
        $id = $conn->real_escape_string($input["id"]);
        $pid = $conn->real_escape_string($_GET["plant_id"]);
        $fv = function($key) use ($input, $conn) {
            return isset($input[$key]) ? $conn->real_escape_string($input[$key]) : '';
        };
        $sql = "UPDATE vendor SET bank_name='".$fv('bank_name')."', branch_address='".$fv('branch_address')."', account_holder='".$fv('account_holder')."',
        account_number='".$fv('account_number')."', ifsc_code='".$fv('ifsc_code')."', payment_mode='".$fv('payment_mode')."'
        WHERE id='".$id."' AND plant_id='".$pid."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
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
    
    
    else if ($_GET["type"] == "getVendors") {
        $output = Array();
      
         $sql = "SELECT * FROM vendor WHERE plant_id='".$_GET["plant_id"]."' AND status='Approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "getMaterial_vendor") {
        $output = Array();
    
        $sql = "SELECT * FROM mst_vendor_materials  WHERE plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDocumentsByType") {
 
        $output = Array();
        $sql = "SELECT * FROM vendor_documents    WHERE plant_id='".$_GET["plant_id"]."'  and vendor_type like '%".$_GET['doc_type']."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['button']=0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDocumentsallDoc") {
 
        $output = Array();
        $sql = "SELECT b.vendor_name,a.vendor_no FROM vendor_uploaded_documents a left join vendor b on a.vendor_no=b.vendor_no WHERE a.plant_id='".$_GET["plant_id"]."' group by b.vendor_name,a.vendor_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output2 = Array();
        $sql2 = "SELECT * FROM vendor_uploaded_documents WHERE vendor_no='".$row['vendor_no']."'";
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                 $output2[] = $row2;
            }
        }
                 $row['files']=$output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    
    
    
    else if ($_GET["type"] == "getnoticeLog") {
        $output = Array();
        $sql = "SELECT v.* ,vr.vendor_name FROM vendor_notice v LEFT JOIN vendor vr ON v.vendor_no = vr.vendor_no  WHERE v.plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    // else if ($_GET["type"] == "getBlacklistLog") {
    //     $output = Array();
    //     // $sql = "SELECT v.* ,vr.vendor_name FROM vendor_notice v LEFT JOIN vendor vr ON v.vendor_no = vr.vendor_no  WHERE v.plant_id = '".$_GET["plant_id"]."' ";
    //     // $result = $conn->query($sql);
    //           $sql = "SELECT distinct v.id, v.* FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
    //       WHERE   v.plant_id = '".$_GET["plant_id"]."' and v.status='Approved' and v.blacklist='block'  ORDER BY id DESC";
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //              $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    else if ($_GET["type"] == "getBlacklistLog") {
       if (!isset($_GET["vendor_type"])) {
            $_GET["vendor_type"] = "";
        }
        if (!isset($_GET["vendor_for"])) {
            $_GET["vendor_for"] = "";
        }
        if (!isset($_GET["state_code"])) {
            $_GET["state_code"] = "";
        }
        $output = Array();
            $sql = "SELECT distinct v.id, v.* FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
           WHERE  v.plant_id = '".$_GET["plant_id"]."' and v.status='Approved' and v.blacklist='block'  ORDER BY id DESC";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getnoticeBYVendor") {
        $output = Array();
        $sql = "SELECT v.* ,vr.vendor_name FROM vendor_notice v LEFT JOIN vendor vr ON v.vendor_no = vr.vendor_no  
        WHERE v.vendor_no =  '".$_GET["vendor_no"]."' AND  v.plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "Get_material_for_quote") {
        $output = Array();
        $sql = "SELECT *,vr.id as v_id,v.order_qty as v_order_qty,v.unit as order_unit,c.id as m_id,v.id as vendor_material_request_id FROM vendor_material_request v LEFT 
        JOIN vendor vr ON v.vendor_no = vr.vendor_no   left join material c on v.material_code=c.material_code
        WHERE v.vendor_no =  '".$_GET["vendor_no"]."' AND  v.plant_id = '".$_GET["plant_id"]."' and c.material_type like '%".$_GET["mat_type"]."' and v.status like '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_vendor_quote_data") {
        $output = Array();
         $sql = "select * from quotation_hdr a  left join quotation_dtl b on a.id=b.quotation_hdr_id where a.vendor_id='".$_GET["vendor_id"]."'
         and b.material_code='".$_GET["material_code"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "Get_material_for_bulk_quote") {
        $output = Array();
        $sql = "SELECT *,vr.id as v_id,v.order_qty as v_order_qty,v.unit as order_unit,c.id as m_id,v.id as vendor_material_request_id FROM vendor_material_request v LEFT 
        JOIN vendor vr ON v.vendor_no = vr.vendor_no   left join material c on v.material_code=c.material_code
        WHERE    v.plant_id = '".$_GET["plant_id"]."' and c.material_type like '%".$_GET["mat_type"]."' and v.status like '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                     $output1 = Array();
                $sql1="SELECT *,a.id as quote_hdr_id FROM quotation_hdr a left JOIN vendor b on a.vendor_id=b.id LEFT JOIN quotation_dtl c on a.id=c.quotation_hdr_id WHERE
                 c.material_code='".$row["material_code"]."' and a.status='approve'"; 
            
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                          $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                          $row["vendors"] = $output1;
                        $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "Get_material_for_bulk_quote_vendor") {
        $output = Array();
        $sql = "SELECT *,vr.id as v_id,v.order_qty as v_order_qty,v.unit as order_unit,c.id as m_id,v.id as vendor_material_request_id FROM vendor_material_request v LEFT 
        JOIN vendor vr ON v.vendor_no = vr.vendor_no   left join material c on v.material_code=c.material_code
        WHERE    v.plant_id = '".$_GET["plant_id"]."' and c.material_type like '%".$_GET["mat_type"]."' and v.status like '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                     $output1 = Array();
                $sql1="SELECT b.vendor_name,b.vendor_no,a.vendor_id  FROM quotation_hdr a left JOIN vendor b on a.vendor_id=b.id LEFT JOIN quotation_dtl c on a.id=c.quotation_hdr_id WHERE
                 c.material_code='".$row["material_code"]."' and a.status='approve' group by b.vendor_name,b.vendor_no,a.vendor_id"; 
            
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                          $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                          $row["vendors"] = $output1;
                        $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "Get_vendor_for_bulk_po_prepare") {
        $output = Array();
$sql = "SELECT b.vendor_name,b.vendor_no,a.vendor_id FROM quotation_hdr a LEFT JOIN vendor b ON a.vendor_id=b.id LEFT JOIN quotation_dtl c ON a.id=c.quotation_hdr_id 
WHERE a.status='approve' AND a.plant_id='" . $_GET["plant_id"] . "' GROUP BY b.vendor_name, b.vendor_no, a.vendor_id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output1 = Array();
        $sql1 = "SELECT *,a.id as bulk_po_material_id  FROM bulk_po_material a left join material b on a.material_code=b.material_code left join quotation_hdr c on a.quote_hdr_id=c.id WHERE a.status='pending' and a.vendor_id='" . $row["vendor_id"] . "'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        // Check if $output1 is not empty before adding $row to $output
        if (!empty($output1)) {
            $row["materials"] = $output1;
            $output[] = $row;
        }
    }
}

echo json_encode($output);
}
    
    
    else if ($_GET["type"] == "getVendorUnit") {
        // error_reporting(0);
        $sql = "SELECT * FROM vendor WHERE user_no='".$_GET["user_no"]."' AND vendor_no='".$_GET["vendor_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row = array_map('utf8_encode', $row["units"]);
                echo $row["units"];
                break;
            }
        } else {
            echo "{}";
        }
    } 
    else if ($_GET["type"] == "saveTermsAndConditons") {
        header('Content-Type: application/json; charset=utf-8');
        if (!is_array($input)) {
            $input = array();
        }
        $vendorNo = $conn->real_escape_string(isset($input['vendor_no']) ? $input['vendor_no'] : '');
        $plantId = $conn->real_escape_string(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
        $json = $conn->real_escape_string(json_encode(isset($input['terms_conditions']) ? $input['terms_conditions'] : array()));
        if ($vendorNo === '') {
            echo '{"status":"Vendor number missing"}';
            exit;
        }
        $sql = "UPDATE vendor SET terms_conditions = '".$json."' WHERE vendor_no = '".$vendorNo."'";
        if ($plantId !== '') {
            $sql .= " AND plant_id='".$plantId."'";
        }
        $ok = false;
        try {
            $ok = $conn->query($sql);
        } catch (Throwable $e) {
            $ok = false;
        }
        echo json_encode(array('status' => $ok ? 'success' : ($conn->error ? $conn->error : 'update failed')));
        exit;
    }
    else if ($_GET["type"] == "savePaymentTerms") {
        header('Content-Type: application/json; charset=utf-8');
        if (!is_array($input)) {
            $input = array();
        }
        $vendorNo = $conn->real_escape_string(isset($input['vendor_no']) ? $input['vendor_no'] : '');
        $plantId = $conn->real_escape_string(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
        $json = $conn->real_escape_string(json_encode(isset($input['paymentTerms']) ? $input['paymentTerms'] : array()));
        $validDate = $conn->real_escape_string(isset($input['termValidDate']) ? $input['termValidDate'] : '');
        if ($vendorNo === '') {
            echo '{"status":"Vendor number missing"}';
            exit;
        }
        $sql = "UPDATE vendor SET paymentTerms = '".$json."' WHERE vendor_no = '".$vendorNo."'";
        if ($plantId !== '') {
            $sql .= " AND plant_id='".$plantId."'";
        }
        $ok = false;
        try {
            $ok = $conn->query($sql);
        } catch (Throwable $e) {
            $ok = false;
        }
        if ($ok) {
            $termSql = "INSERT INTO vendorTerms (`plant_id`, `term_heading`, `term`, `termValidDate`, `vendor_no`, `status`, `entry_by`, `entry_date`) VALUES ('".$plantId."', 'Payment Terms', '".$json."', '".$validDate."', '".$vendorNo."', 'Pay_Term', '".$conn->real_escape_string(isset($_GET['emp_id']) ? $_GET['emp_id'] : '')."', '".$entry_date."')";
            try {
                $conn->query($termSql);
            } catch (Throwable $e) {
            }
        }
        echo json_encode(array('status' => $ok ? 'success' : ($conn->error ? $conn->error : 'update failed')));
        exit;
    }
    else if ($_GET["type"] == "getVendorLog") {
        ensureVendorStatusRemarkColumn($conn);
        ensureVendorCorrectionLogColumn($conn);
        $output = array();
        $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $sql = "SELECT * FROM vendor WHERE plant_id='".$plantId."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $vendorNo = $conn->real_escape_string(isset($row["vendor_no"]) ? $row["vendor_no"] : '');
                $sql1 = "SELECT `id`, `plant_id`, `vendor_Is`, `status`, `status_remark`, `vendor_no`, `parentVendor`, `material_type`, `vendor_type`, `vendor_name`, `entry_by`, `entry_date` FROM vendor  
                WHERE parentVendor = '".$vendorNo."' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["divisions"] = $output1;
                $row['other_contact'] = vendorJsonDecode(isset($row['other_contact']) ? $row['other_contact'] : '');
                $row['terms_conditions'] = vendorJsonDecode(isset($row['terms_conditions']) ? $row['terms_conditions'] : '');
                $row['paymentTerms'] = vendorJsonDecode(isset($row['paymentTerms']) ? $row['paymentTerms'] : '');
                $row['selectedCurrencies'] = vendorJsonDecode(isset($row['currency']) ? $row['currency'] : '');
                $remark = isset($row['status_remark']) ? trim((string)$row['status_remark']) : '';
                if ($remark === '' && !empty($row['correction_log'])) {
                    $clog = vendorJsonDecode($row['correction_log']);
                    if (isset($clog['qa_remark'])) {
                        $remark = trim((string)$clog['qa_remark']);
                    }
                    $row['correction_log'] = $clog;
                }
                $row['status_remark'] = $remark;
                $output[] = $row;
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($output, $jsonFlags);
        echo ($json === false) ? '[]' : $json;
    }
          
else if ($_GET["type"] == "savedocument") {

        $json_obj = json_encode($input["data"]);
        $array = json_decode($json_obj, true);
        foreach ($array as $values)
        {
            $sql = "INSERT INTO vendor_documents(plant_id,  vendor_type,document_name,validity,entry_date,entry_by	)
            VALUES ('".$_GET["plant_id"]."','".$values["vendor_type"]."','".$values["document_name"]."','".$values["validity"]."',
            '".$_GET["emp_id"]."','$entry_date')";
        
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
            
            
            
            
            
            
            
//          else if ($_GET["type"] == "getVendor_no1") {
//     $output = array();
    
//      $sql = "SELECT * FROM Vendor WHERE vendor_name='".$_GET["vendor_name"]."' and vendor_no='".$_GET["vendor_no"]."'";
//     $result = $conn->query($sql);
//     if ($result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $output[] = $row;
//         }
//     }
//     echo json_encode($output);
// }
                   else if ($_GET["type"] == "getVendor_no")  {  
            $output = array();
            
            $sql = "SELECT * FROM vendor WHERE status='approved' AND plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            
            echo json_encode($output);
                   }
            
                else if ($_GET["type"] == "getvendordocument") {
            	    $output = array();
            	    $sql = "SELECT * FROM vendor_documents WHERE  plant_id =  '".$_GET["plant_id"]."' ";
            	    
             	    $result = $conn->query($sql);
            	    if ($result->num_rows > 0) {
            	        while ($row = $result->fetch_assoc())
            	            {
             	            $output[] = $row;
            	        }
            	    }
            	    echo json_encode($output);
	            } 
                else if ($_GET["type"] == "getvendordocumentByVendorType") {
            	    $output = array();
            	    $sql = "SELECT * FROM vendor_documents WHERE vendor_type = '".$_GET["vendor_type"]."' AND  plant_id =  '".$_GET["plant_id"]."' ";
            	    
             	    $result = $conn->query($sql);
            	    if ($result->num_rows > 0) {
            	        while ($row = $result->fetch_assoc())
            	            {
             	            $output[] = $row;
            	        }
            	    }
            	    echo json_encode($output);
	            } 

    else if ($_GET["type"] == "getVendorbYiD") {
      
        $output = Array();
            $sql = "SELECT distinct v.id, v.* FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
           WHERE   v.plant_id = '".$_GET["plant_id"]."'  and v.vendor_no = '".$_GET["vendor_no"]."'";
    
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingVendors") {
       if (!isset($_GET["vendor_type"])) {
            $_GET["vendor_type"] = "";
        }
        if (!isset($_GET["vendor_for"])) {
            $_GET["vendor_for"] = "";
        }
        if (!isset($_GET["state_code"])) {
            $_GET["state_code"] = "";
        }
        $output = Array();
           $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
           WHERE v.material_type LIKE '%".$_GET["material_type"]."%' and v.plant_id = '".$_GET["plant_id"]."' 
           And v.status='Pending' 
           ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "getStoresupplier") {
       
        $output = Array();
           $sql = "select  vendor_type,vendor_name from vendor where vendor_type='Supplier' ORDER BY id DESC;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedVendors") {
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $q = isset($_GET["q"]) ? trim((string)$_GET["q"]) : '';
        $vendorType = isset($_GET["vendor_type"]) ? trim((string)$_GET["vendor_type"]) : '';
        $materialType = isset($_GET["material_type"]) ? trim((string)$_GET["material_type"]) : '';
        $page = max(1, (int)($_GET["page"] ?? 1));
        $pageSize = (int)($_GET["pageSize"] ?? 10);
        if (!in_array($pageSize, array(10, 20, 50, 100), true)) {
            $pageSize = 10;
        }
        $exportAll = isset($_GET["export"]) && (string)$_GET["export"] === '1';

        $where = "v.plant_id = '".$plantId."' AND v.status='Approved'";
        if ($vendorType !== '') {
            $where .= " AND v.vendor_type = '".$conn->real_escape_string($vendorType)."'";
        }
        if ($materialType !== '') {
            $where .= " AND v.material_type = '".$conn->real_escape_string($materialType)."'";
        }
        if ($q !== '') {
            $like = "%".$conn->real_escape_string($q)."%";
            $where .= " AND (
                v.vendor_no LIKE '".$like."'
                OR v.vendor_name LIKE '".$like."'
                OR v.vendor_Is LIKE '".$like."'
                OR v.vendor_type LIKE '".$like."'
                OR v.material_type LIKE '".$like."'
                OR v.contact_person LIKE '".$like."'
                OR v.contact_number LIKE '".$like."'
                OR v.contact_email LIKE '".$like."'
                OR v.qualifiedBy LIKE '".$like."'
                OR v.vendorFor LIKE '".$like."'
                OR v.address LIKE '".$like."'
                OR v.country LIKE '".$like."'
                OR v.permanent_state LIKE '".$like."'
                OR v.city LIKE '".$like."'
                OR v.pincode LIKE '".$like."'
                OR v.status LIKE '".$like."'
            )";
        }

        $total = 0;
        $countRes = $conn->query("SELECT COUNT(*) AS cnt FROM vendor v WHERE ".$where);
        if ($countRes && ($c = $countRes->fetch_assoc())) {
            $total = (int)$c['cnt'];
        }

        $select = "SELECT v.id, v.status, v.vendor_Is, v.vendor_type, v.material_type, v.vendor_no, v.vendor_name, v.parentVendor, v.contact_person, v.contact_number, v.contact_email, v.other_contact, v.address, v.country,v.permanent_state, v.city, v.pincode,
        v.qualifiedBy, v.client_code, v.gst_applicable, v.scode, v.gst_no, v.panNo, v.vendorFor, v.currency,
        v.c_unit_name, v.c_address, v.c_country, v.c_state, v.c_city, v.c_pincode, v.c_mobile_no, v.c_gst_applicable, v.c_scode, v.c_gst_no, v.c_panNo,
        v.entry_by, v.entry_date, v.approve_by, v.approve_date, v.qaApproveBy, v.qaApproveOn,
        (select vm.vendor_name from vendor vm where vm.vendor_no = v.parentVendor limit 1) as parentVenName,
        (select c.LglNm from client c where c.client_code = v.client_code limit 1) as client_name
        FROM vendor v
        WHERE ".$where." ORDER BY v.vendor_name ASC";

        if (!$exportAll) {
            $offset = ($page - 1) * $pageSize;
            $select .= " LIMIT ".$offset.", ".$pageSize;
        }

        $rows = array();
        $result = $conn->query($select);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['other_contact'] = json_decode($row['other_contact']);
                $row['selectedCurrencies'] = (isset($row['currency']) && $row['currency'] !== null && $row['currency'] !== '')
                    ? json_decode($row['currency'])
                    : array();
                $rows[] = $row;
            }
        }

        $vendorTypes = array();
        $materialTypes = array();
        $vtRes = $conn->query("SELECT DISTINCT vendor_type FROM vendor WHERE plant_id='".$plantId."' AND status='Approved' AND vendor_type IS NOT NULL AND TRIM(vendor_type)<>'' ORDER BY vendor_type");
        if ($vtRes) {
            while ($r = $vtRes->fetch_assoc()) {
                $vendorTypes[] = $r['vendor_type'];
            }
        }
        $mtRes = $conn->query("SELECT DISTINCT material_type FROM vendor WHERE plant_id='".$plantId."' AND status='Approved' AND material_type IS NOT NULL AND TRIM(material_type)<>'' ORDER BY material_type");
        if ($mtRes) {
            while ($r = $mtRes->fetch_assoc()) {
                $materialTypes[] = $r['material_type'];
            }
        }

        echo json_encode(array(
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'vendor_types' => $vendorTypes,
            'material_types' => $materialTypes,
        ));
    } 
    else if ($_GET["type"] == "getProvisionalVendors") {
        if (!isset($_GET["vendor_type"])) {
            $_GET["vendor_type"] = "";
        }
        if (!isset($_GET["vendor_for"])) {
            $_GET["vendor_for"] = "";
        }
        if (!isset($_GET["state_code"])) {
            $_GET["state_code"] = "";
        }
        if (!isset($_GET["plant_id"])) {
            $_GET["plant_id"] = "";
        }
        if (!isset($_GET["user_no"])) {
            $_GET["user_no"] = "";
        }
        $output = Array();
        $sql = "SELECT v.*, s.state_name,
            (SELECT COUNT(1) FROM quotation_hdr q WHERE q.vendor_id = v.id AND (q.status='approve' OR q.status='Approved')) AS quotation_count,
            (SELECT COUNT(1) FROM purchaseorder p WHERE p.vendor_no = v.vendor_no
                AND (('".$_GET["plant_id"]."' = '') OR p.plant_id='".$_GET["plant_id"]."')) AS po_count
            FROM vendor v
            LEFT JOIN state s ON v.state_code=s.state_code
            WHERE (('".$_GET["user_no"]."' = '') OR v.user_no='".$_GET["user_no"]."')
            AND (('".$_GET["plant_id"]."' = '') OR v.plant_id='".$_GET["plant_id"]."')
            AND (UPPER(v.status)='APPROVE' OR UPPER(v.status)='APPROVED')
            AND UPPER(IFNULL(v.vendor_status,'')) LIKE 'PROVISIONAL%'
            AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%'
            AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%'
            AND v.state_code LIKE '%".$_GET["state_code"]."%'
            ORDER BY v.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "blacklistVendor") {
        $sql="SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."' AND plant_id ='".$_GET["plant_id"]."'
        and password = '".$_GET["password"]."' ";
        
        $result =$conn->query($sql);
        if ($result->num_rows == 0) {
          	echo "{\"status\":\"Invalid Password\"}";
        }
        else {
        $sql = "UPDATE vendor SET status='Blacklisted' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
        }
    } else if ($_GET["type"] == "getBlacklistedVendors") {
        if (!isset($_GET["vendor_type"])) {
            $_GET["vendor_type"] = "";
        }
        if (!isset($_GET["vendor_for"])) {
            $_GET["vendor_for"] = "";
        }
        if (!isset($_GET["state_code"])) {
            $_GET["state_code"] = "";
        }
        $output = Array();
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
        WHERE v.user_no='".$_GET["user_no"]."' AND v.status='Blacklisted' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "sendChecklistQa"){
        $sql = "INSERT INTO send_checklist(vendor_no,checklist_No ,checklist_type ,effective_date,entry_by ,
        entry_date)VALUES('".$input["vendor_no"]."' ,'".$input["checklist_No"]."' ,
        '".$input["checklist_type"]."' ,'".$input["effective_date"]."' ,'".$_GET["emp_id"]."' ,
        '".$entry_date."')";
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
        }
    } 
    else if($_GET['type'] == 'downloadVendorLog') {
        $_GET['filename'] = 'Vendor Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:cenetr">Vendor Log</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:7%;">Sr.No</td>
                <td style="width:15%;">Date</td>
                <td style="width:12%;">Vendor No.</td>
                <td style="width:15%;">Vendor Type</td>
                <td style="width:26%;">Vendor Name</td>
                <td style="width:25%;">Email</td>
            </tr>';
        $material_type = isset($_GET["material_type"]) ? $_GET["material_type"] : '';
        $vendor_name = isset($_GET["vendor_name"]) ? $_GET["vendor_name"] : '';
        $sql = "SELECT DISTINCT v.id, v.* FROM vendor v
           WHERE v.material_type LIKE '%".$material_type."%' AND v.plant_id = '".$_GET["plant_id"]."' AND v.status='Approved' AND v.vendor_name LIKE '%".$vendor_name."%' ORDER BY id DESC";
        $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $email = !empty($row['contact_email']) ? $row['contact_email'] : (isset($row['email']) ? $row['email'] : '');
                $html.='<tr nobr="true">
                    <td style="width: 7%;">'.$i.'.</td>
                    <td style="width: 15%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td style="width: 12%;">'.$row['vendor_no'].'</td>
                    <td style="width: 15%;">'.$row['vendor_type'].'</td>
                    <td style="width: 26%;">'.$row['vendor_name'].'</td>
                    <td style="width: 25%;">'.$email.'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vendor Log.pdf', 'I');
        
        
    } else if($_GET['type'] == 'downloadVendorApprovalLog') {
        $_GET['filename'] = 'Vendor Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:cenetr">NON-GMP Vendor For Approval</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:7%;">Sr.No</td>
                <td style="width:13%;">Date</td>
                <td style="width:12%;">Vendor For</td>
                <td style="width:10%;">Vendor No.</td>
                <td style="width:13%;">Vendor Type</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:15%;">Email</td>
                <td style="width:15%;">Status</td>
            </tr>';
        // $sql = "SELECT *FROM vendor v  WHERE v.user_no='".$_GET["user_no"]."' AND v.plant_id='".$_GET["plant_id"]."'  order by 1 desc";// AND v.status='pending'
  //$sql = "SELECT distinct v.id, v.* FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
          // WHERE v.material_type LIKE '%".$_GET["material_type"]."%' and v.plant_id = '".$_GET["plant_id"]."' and v.status='Approved' and v.vendor_name like '%".$_GET["vendor_name"]."%' ORDER BY id DESC";           
     $sql = "SELECT * FROM vendor   WHERE  plant_id='".$_GET["plant_id"]."'  AND status = 'Checked'   order by 1 desc";      
           $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                    <td style="width: 7%;">'.$i.'.</td>
                    <td style="width: 13%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td style="width: 12%;">'.$row['vendor_for'].'</td>
                    <td style="width: 10%;">'.$row['vendor_no'].'</td>
                    <td style="width: 13%;">'.$row['vendor_type'].'</td>
                    <td style="width: 15%;">'.$row['vendor_name'].'</td>
                    <td style="width: 15%;">'.$row['email'].'</td>
                    <td style="width: 15%;">'.$row['status'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vendor Log.pdf', 'I');
        
        
    }
    else if($_GET['type'] == 'getVendorsApprovalQA') {
        $_GET['filename'] = 'Vendor Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:cenetr">Vendor Approval</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:7%;">Sr.No</td>
                <td style="width:15%;">Date</td>
                <td style="width:10%;">Vendor No.</td>
                <td style="width:16%;">Vendor Type</td>
                <td style="width:18%;">Vendor Name</td>
                <td style="width:18%;">Email</td>
                <td style="width:15%;">Status</td>
            </tr>';
        
     $sql = "SELECT * FROM vendor   WHERE  plant_id='".$_GET["plant_id"]."'  AND status = 'Checking'   order by 1 desc";      
           $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                    <td style="width: 7%;">'.$i.'.</td>
                    <td style="width: 15%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td style="width: 10%;">'.$row['vendor_no'].'</td>
                    <td style="width: 16%;">'.$row['vendor_type'].'</td>
                    <td style="width: 18%;">'.$row['vendor_name'].'</td>
                    <td style="width: 18%;">'.$row['email'].'</td>
                    <td style="width: 15%;">'.$row['status'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vendor Approval QA Log.pdf', 'I');
        
        
    }
    else if($_GET['type'] == 'blacklist_log') {
        $_GET['filename'] = 'Vendor Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:cenetr">Blacklist Log</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:7%;">Sr.No</td>
                <td style="width:13%;">Date</td>
                <td style="width:12%;">Vendor For</td>
                <td style="width:10%;">Vendor No.</td>
                <td style="width:13%;">Vendor Type</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:15%;">Email</td>
                <td style="width:15%;">State</td>
            </tr>';
        // $sql = "SELECT *FROM vendor v  WHERE v.user_no='".$_GET["user_no"]."' AND v.plant_id='".$_GET["plant_id"]."'  order by 1 desc";// AND v.status='pending'
 $sql = "SELECT distinct v.id, v.* FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
           WHERE  v.plant_id = '".$_GET["plant_id"]."' and v.status='Approved' and v.blacklist='block'  ORDER BY id DESC";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                    <td style="width: 7%;">'.$i.'.</td>
                    <td style="width: 13%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td style="width: 12%;">'.$row['material_type'].'</td>
                    <td style="width: 10%;">'.$row['vendor_no'].'</td>
                    <td style="width: 13%;">'.$row['vendor_type'].'</td>
                    <td style="width: 15%;">'.$row['vendor_name'].'</td>
                    <td style="width: 15%;">'.$row['email'].'</td>
                    <td style="width: 15%;">'.$row['state_name'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vendor Log.pdf', 'I');
        
        
    }
    else if($_GET['type'] == 'downloadApprovedVendors'){
        $_GET['filename'] = 'Approved Vendors'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Approved Vendors</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:10%;">Sr.No</td>
                <td style="width:15%;">Vendor No.</td>
                <td style="width:15%;">Vendor Type</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:15%;">Vendor For</td>
                <td style="width:15%;">Email</td>
                <td style="width:15%;">State</td>
            </tr>';
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
        WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Approved' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$i.'.</td>
                    <td style="width: 15%;">'.$row['vendor_no'].'</td>
                    <td style="width: 15%;">'.$row['vendor_type'].'</td>
                    <td style="width: 15%;">'.$row['vendor_name'].'</td>
                    <td style="width: 15%;">'.$row['vendor_for'].'</td>
                    <td style="width: 15%;">'.$row['email'].'</td>
                    <td style="width: 15%;">'.$row['state_name'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Approved Vendors.pdf', 'I');
    } else if($_GET['type'] == 'downloadProvisionalVendors'){
        $_GET['filename'] = 'Provisional Vendors'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Provisional Vendors</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:10%;">Sr.No</td>
                <td style="width:15%;">Vendor No.</td>
                <td style="width:15%;">Vendor Type</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:15%;">Vendor For</td>
                <td style="width:15%;">Email</td>
                <td style="width:15%;">State</td>
            </tr>';
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Provisional' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$i.'.</td>
                    <td style="width: 15%;">'.$row['vendor_no'].'</td>
                    <td style="width: 15%;">'.$row['vendor_type'].'</td>
                    <td style="width: 15%;">'.$row['vendor_name'].'</td>
                    <td style="width: 15%;">'.$row['vendor_for'].'</td>
                    <td style="width: 15%;">'.$row['email'].'</td>
                    <td style="width: 15%;">'.$row['state_name'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Provisional Vendors.pdf', 'I');
    } 
    else if ($_GET["type"] == "get_supplier_log") {
        
        
          $_GET['filename'] = 'Service List'; 	$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
        $html.="";
        $html.='<table cellpadding="5" border="1">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:10%;">Sr No.</td>
                    <td style="width:15%;">Vendor Code</td>
                    <td style="width:25%;">Vendor Type</td>
                    <td style="width:32%;">Vendor Name</td>
                    <td style="width:18%;">No of Product</td>
                </tr>
            </thead>';
            $i=1;
    $sql = "SELECT *, (SELECT COUNT(material_code) FROM mst_vendor_materials b WHERE a.vendor_no = b.supplier_code) AS no_of_products FROM 
                 vendor a having no_of_products !='0' AND a.plant_id = '".$_GET["plant_id"]."' ";

        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row = array_map('utf8_encode', $row);
            $html.='
                <tbody>
                    <tr nobr="true">
                        <td style="width:10%;">'.$i.'.</td>
                        <td style="width:15%;">'.$row['vendor_no'].'</td>
                        <td style="width:25%;">'.$row['vendor_type'].'</td>
                        <td style="width:32%;">'.$row['vendor_name'].'</td>
                        <td style="width:18%;">'.$row['no_of_products'].'</td>
                    </tr>
                </tbody>';
                $i++;
                }
            }
            $html.='
        </table>';
       // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('supplier_log.pdf', 'I');
        
        
        
    }

    else if ($_GET["type"] == "download_material_map_pdf") {
        $_GET['filename'] = 'Vendor Material Mapping';
        $_GET['pdftype'] = 'noheader';
        include('../pdfimp2.php');

        $pdfSafe = function ($value) {
            if ($value === null) {
                return '';
            }
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        };

        $matTypeShort = function ($type) {
            $t = strtolower(trim((string)$type));
            if ($t === '') {
                return '-';
            }
            if ($t === 'raw material' || $t === 'rm') {
                return 'RM';
            }
            if ($t === 'packing material' || $t === 'pm') {
                return 'PM';
            }
            if ($t === 'general material' || $t === 'gm') {
                return 'GM';
            }
            if ($t === 'others material' || $t === 'other material' || $t === 'others') {
                return 'OT';
            }
            if ($t === 'service' || $t === 'services') {
                return 'SVC';
            }
            if (strpos($t, 'rm/pm') !== false) {
                return 'RM/PM';
            }
            return strtoupper(substr(preg_replace('/[^A-Za-z0-9\/]/', '', (string)$type), 0, 6)) ?: '-';
        };

        $logoUrl = (isset($logo) && $logo !== '')
            ? 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/'.$logo
            : '';

        // ---- Company header (Medicap Laboratories) ----
        $html .= '<table cellpadding="3" border="0"><tr>';
        if ($logoUrl !== '') {
            $html .= '<td style="width:22%; text-align:center;"><img src="'.$logoUrl.'" width="90" height="50" /></td>
                      <td style="width:78%; text-align:center;">';
        } else {
            $html .= '<td style="width:100%; text-align:center;">';
        }
        $html .= '
                <span style="font-family:times; font-size:18px; font-weight:bold; color:#0E4370;">Medicap Laboratories</span><br>
                <span style="font-size:9px;">30 Worcester Rd, Etobicoke, ON M9W 5X2, Canada</span><br>
                <span style="font-size:9px;">Tel: (416) 675-4343 &nbsp;|&nbsp; Web: medicaplab.com</span>
            </td>
            </tr>
        </table>
        <h3 style="text-align:center; color:#0E4370;">Vendor Material Mapping Report</h3>';

        // ---- Table: one vendor row; materials nested in last cell ----
        $html .= '<table cellpadding="4" border="1">
            <thead>
                <tr style="background-color:#0E4370; color:#FFFFFF; font-weight:bold; text-align:center;">
                    <td style="width:6%;">Sr No.</td>
                    <td style="width:12%;">Vendor Code</td>
                    <td style="width:14%;">Vendor Type</td>
                    <td style="width:22%;">Vendor Name</td>
                    <td style="width:46%;">Materials</td>
                </tr>
            </thead>
            <tbody>';

        $plantId = $conn->real_escape_string($_GET["plant_id"]);
        $sql = "SELECT a.supplier_code AS vendor_no, b.vendor_type, b.vendor_name
                FROM mst_vendor_materials a
                LEFT JOIN vendor b ON a.supplier_code = b.vendor_no AND b.plant_id = a.plant_id
                WHERE a.plant_id = '".$plantId."'
                GROUP BY a.supplier_code, b.vendor_type, b.vendor_name
                ORDER BY b.vendor_name ASC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vendor_no = $conn->real_escape_string($row['vendor_no']);

                $sqlm = "SELECT vm.material_code, vm.material_type,
                         MAX(COALESCE(mat.material_name, om.material_name, gm.material_name, svc.service_title, '')) AS material_name
                         FROM mst_vendor_materials vm
                         LEFT JOIN material mat ON mat.material_code = vm.material_code
                         LEFT JOIN others_material om ON om.material_code = vm.material_code AND om.plant_id = vm.plant_id
                         LEFT JOIN general_material gm ON gm.material_code = vm.material_code
                         LEFT JOIN service svc ON svc.service_code = vm.material_code
                         WHERE vm.plant_id='".$plantId."'
                           AND (vm.manufacturer_code='".$vendor_no."' OR vm.supplier_code='".$vendor_no."')
                         GROUP BY vm.material_code, vm.material_type
                         ORDER BY vm.material_code ASC";
                $resm = $conn->query($sqlm);
                $mats = array();
                if ($resm && $resm->num_rows > 0) {
                    while ($rm = $resm->fetch_assoc()) {
                        $mats[] = $rm;
                    }
                }

                $inner = '-';
                if (count($mats) > 0) {
                    $inner = '<table cellpadding="1" cellspacing="0" border="1" width="100%">
                        <tr style="background-color:#E8EEF5; font-weight:bold; text-align:center; font-size:8px;">
                            <td width="8%">Sr</td>
                            <td width="12%">Type</td>
                            <td width="28%">Code</td>
                            <td width="52%">Name</td>
                        </tr>';
                    $mi = 1;
                    foreach ($mats as $m) {
                        $inner .= '<tr style="font-size:8px;">
                            <td width="8%" align="center">'.$mi.'</td>
                            <td width="12%" align="center">'.$pdfSafe($matTypeShort(isset($m['material_type']) ? $m['material_type'] : '')).'</td>
                            <td width="28%">'.$pdfSafe(!empty($m['material_code']) ? $m['material_code'] : '-').'</td>
                            <td width="52%">'.$pdfSafe(!empty($m['material_name']) ? $m['material_name'] : '-').'</td>
                        </tr>';
                        $mi++;
                    }
                    $inner .= '</table>';
                }

                $html .= '<tr>
                    <td style="width:6%; text-align:center; vertical-align:top;">'.$i.'.</td>
                    <td style="width:12%; vertical-align:top;">'.$pdfSafe($row['vendor_no']).'</td>
                    <td style="width:14%; vertical-align:top;">'.$pdfSafe($row['vendor_type']).'</td>
                    <td style="width:22%; vertical-align:top;">'.$pdfSafe($row['vendor_name']).'</td>
                    <td style="width:46%; vertical-align:top;">'.$inner.'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align:center;">No vendor material mappings found.</td></tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vendor_Material_Mapping.pdf', 'I');
        exit;
    }
    
    else if($_GET['type'] == 'downloadBlacklistedVendors'){
        $_GET['filename'] = 'Blacklisted Vendors'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Blacklisted Vendors</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:10%;">Sr.No</td>
                <td style="width:15%;">Vendor No.</td>
                <td style="width:15%;">Vendor Type</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:15%;">Vendor For</td>
                <td style="width:15%;">Email</td>
                <td style="width:15%;">State</td>
            </tr>';
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='Blacklisted' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$i.'.</td>
                    <td style="width: 15%;">'.$row['vendor_no'].'</td>
                    <td style="width: 15%;">'.$row['vendor_type'].'</td>
                    <td style="width: 15%;">'.$row['vendor_name'].'</td>
                    <td style="width: 15%;">'.$row['vendor_for'].'</td>
                    <td style="width: 15%;">'.$row['email'].'</td>
                    <td style="width: 15%;">'.$row['state_name'].'</td>
                </tr>';
                $i++;
            }
            
        }
  
           $html.='</table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Blacklisted Vendors.pdf', 'I');
    }else if ($_GET["type"] == "getGSTNos") {
        $data = array();
        $output = array();
        $sql = "SELECT DISTINCT(gst_no) as gst_no FROM vendor WHERE gst_no !==''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        $data["gstno"] = $output;
        
        $output = array();
        $sql = "SELECT DISTINCT(pan_no) as pan_no FROM vendor WHERE pan_no NOT IN ('NA', '')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        $data["pan_no"] = $output;
        echo json_encode($data);
    }

}

// $conn->close();

function notification ($department, $notification_for, $notification, $form, $form_no, $notification_from, $entry_date) {
    $file = json_decode(file_get_contents("../notifications.json"), true);
    
    $dept = $file[$department];
    $user = $dept[$notification_for];
    
    $temp = Array();
    $temp["notification"] = $notification;
    $temp["form"] = $form;
    $temp["form_no"] = $form_no;
    $temp["notification_from"] = $notification_from;
    $temp["notification_date"] = $entry_date;
    $user[] = $temp;
    
    $dept[$notification_for] = $user;
    $file[$department] = $dept;
    
    $file_handle = fopen("../notifications.json", 'w'); 
    fwrite($file_handle, json_encode($file));
    fclose($file_handle);
}

function deletenofication ($department, $notification_for, $form, $form_no) {
    $file = json_decode(file_get_contents("../notifications.json"), true);
    
    $dept = $file[$department];
    $user = $dept[$notification_for];
    $temp = Array();
    for ($i = 0; $i < count($user); $i++) {
        $data = $user[$i];
        if ($data["form"] == $form && $data["form_no"] == $form_no) {
        } else {
            $temp[] = $data;
        }
    }
    
    $dept[$notification_for] = $temp;
    $file[$department] = $dept;
    
    $file_handle = fopen("../notifications.json", 'w'); 
    fwrite($file_handle, json_encode($file));
    fclose($file_handle);
}
$conn->close();
?>