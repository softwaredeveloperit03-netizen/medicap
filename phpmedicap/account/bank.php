<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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

if($result->num_rows > 0) {
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
   
        
        if ($_GET["type"] == "saveBanklog") {
        $sql = "INSERT INTO banks (bank_name, account_no, branch, account_type, ifsc, entry_by, entry_date,
        approve_by,approve_date,cheque_no,book_no) 
        VALUES ('".$input["bank_name"]."','".$input["account_no,"]."', '".$input["branch"]."', '".$input["account_type"]."', 
        '".$input["ifsc"]."',  '".$_GET["emp_id"]."', '$entry_date','".$_GET["emp_id"]."',
        $entry_date '".$input["cheque_no"]."', '".$input["book_no"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
   
        
    }else if ($_GET["type"] == "saveBank") {
        $sql = "INSERT INTO banks (plant_id,bank_name, account_no,branch, account_type, ifsc,entry_by,entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["bank_name"]."','".$input["account_no"]."', '".$input["branch"]."', 
        '".$input["account_type"]."',  '".$input["ifsc"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
   }
   else  if($_GET["type"]=="saveClient1") {
        $input = $_POST;
        $data = json_decode($input["data"], true);
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $data["c_name"]."photo.".$file_ext;
            $photo = $file_name;
            move_uploaded_file($file_tmp,"../upload/gstcertificate/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $data["mobile_no"]."mfg_lic.".$file_ext;
            $mfglic = $file_name;
            move_uploaded_file($file_tmp,"../upload/gstcertificate/".$file_name);
        }
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $data["c_name"]."photo.".$file_ext;
            $photo = $file_name; 
            move_uploaded_file($file_tmp,"../upload/vendor/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $data["mobile_no"]."mfg_lic.".$file_ext;
            $mfglic = $file_name;
            move_uploaded_file($file_tmp,"../upload/vendor/".$file_name);
        }
        $input["company"] = $input["cr_city"];
          $branch =$data["branch"];
        

           $sql = "INSERT INTO ledgers (user_no,refered_by, agent_no, percentage,cl_type, client_subtype, 
  LglNm, TrdNm,company,person,email,phone,phone2,address, website, type,gst_registered,gst_type,tan_no,gst_no, country,
  client_type, dl_no, dl_validity,import_lic_no, branch, status, client_status,telephone_no,
  fax_no, state_code,pan_no, loc, Pin,entry_date,category,c_name,c_address,c_country,ocountry,c_permanent_state,
  c_city,c_pincode,c_mobile_no,c_email,c_gst_applicable,c_scode,c_gst_no,cr_unit_name,cr_address,cr_country,other_country,
  cr_state,cr_city,cr_pincode,cr_mobile_no,cr_gst_applicable,cr_st_code,cr_gst_no,gst_cer,comType,vendor_type,vendor_subtype
  ,material_type,vendor_name,mfglic,cfrom) 
    VALUES ('".$_GET["user_no"]."', '".$data["refered_by"]."', '".$data["agent_no"]."','".$data["percentage"]."', 
    '".$data["cl_type"]."','".$data["subtype"]."', '".$data["LglNm"]."', '".$data["TrdNm"]."','".$data["company"]."',
    '".$data["person"]."','".$data["email"]."','".$data["mobile_no"]."','".$data["mobile_no2"]."','".$data["c_address"]."', 
    '".$data["website"]."', '".$data["type"]."','".$data["gst_registered"]."', '".$data["gst_type"]."',
    '".$data["gst_no"]."', '".$data["tan_no"]."','".$data["country"]."', '".$data["cl_type"]."','".$data["dl_no"]."', 
    '".$data["dl_validity"]."', '".$data["import_lic_no"]."','".$branch."', 'approve', 'active', 
    '".$data["telephone_no"]."','".$data["fax_no"]."',
    '".$data["state_code"]."','".$data["pan_no"]."',  '".$data["state_code"]."', 
    '".$data["c_pincode"]."','".$data["category"]."','$entry_date',
     '".$data["c_name"]."', '".$data["c_address"]."','".$data["c_country"]."',
      '".$data["ocountry"]."', '".$data["c_permanent_state"]."','".$data["c_city"]."',
       '".$data["c_pincode"]."', '".$data["c_mobile_no"]."','".$data["c_email"]."',
        '".$data["c_gst_applicable"]."', '".$data["c_scode"]."','".$data["c_gst_no"]."',
        '".$data["cr_unit_name"]."', '".$data["cr_address"]."','".$data["cr_country"]."',
        '".$data["other_country"]."', '".$data["cr_state"]."','".$data["cr_city"]."',
        '".$data["cr_pincode"]."', '".$data["cr_mobile_no"]."','".$data["cr_gst_applicable"]."',
        '".$data["cr_st_code"]."', '".$data["cr_gst_no"]."','".$photo."',
        '".$data["comType"]."', '".$data["vendor_type"]."','".$data["vendor_subtype"]."',
        '".$data["material_type"]."', '".$data["vendor_name"]."','".$mfglic."','".$data["from"]."')"; 
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
            
      $sql1 = "INSERT INTO vendor(plant_id,vendor_type,material_type, vendor_for, vendor_name,manufacturer_code,
  country,st_code,state_name,gst_registered,gst_type,gst_no,pan_no,mfg_lic,email, tel_no1,
  fax, website, contact_person,contact_number,contact_email,comp_pan_no,address, gst_certificate, mfg_lic_file,
  vendor_status,entry_by,entry_date,city,unit_name,pincode,c_unit_name,c_address,c_country,c_state,
  c_city,c_pincode,c_mobile_no,c_gst_applicable,c_gst_no,scode) VALUES ('".$_GET["plant_id"]."',
  '".$data["vendor_type"]."','".$data["material_type"]."' ,'".$data["vendor_for"]."','".$data["vendor_name"]."'
  ,'".$data["manufacturer_code"]."','".$data["c_country"]."','".$data["c_scode"]."','".$data["c_permanent_state"]."','".$data["gst_registered"]."',
  '".$data["gst_type"]."','".$data["c_gst_no"]."',
  '".$data["pan_no"]."','".$data["mfg_lic"]."','".$data["c_email"]."','".$data["mobile_no2"]."',
   '".$data["fax_no"]."','".$data["website"]."', '".$data["person"]."','".$data["mobile_no"]."','".$data["email"]."','".$data["pan_no"]."',
   '".$data["c_address"]."','".$photo."','".$mfglic."','".$data["vendor_status"]."',
   '".$_GET["emp_id"]."','".$entry_date."','".$data["c_city"]."','".$data["c_name"]."','".$data["c_pincode"]."',
   '".$data["cr_unit_name"]."', '".$data["cr_address"]."','".$data["cr_country"]."','".$data["cr_state"]."',
   '".$data["cr_city"]."','".$data["cr_pincode"]."','".$data["cr_mobile_no"]."','".$data["cr_gst_applicable"]."',
    '".$data["cr_gst_no"]."','".$data["cr_st_code"]."')"; 
   
        $conn->query($sql1);
        
   
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
    else  if($_GET["type"]=="saveClient2") {
        $input = $_POST;
        $data = json_decode($input["data"], true);
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $data["c_name"]."photo.".$file_ext;
            $photo = $file_name;
            move_uploaded_file($file_tmp,"../upload/gstcertificate/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $data["mobile_no"]."mfg_lic.".$file_ext;
            $mfglic = $file_name;
            move_uploaded_file($file_tmp,"../upload/gstcertificate/".$file_name);
        }
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $data["c_name"]."photo.".$file_ext;
            $photo = $file_name; 
            move_uploaded_file($file_tmp,"../upload/vendor/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $data["mobile_no"]."mfg_lic.".$file_ext;
            $mfglic = $file_name;
            move_uploaded_file($file_tmp,"../upload/vendor/".$file_name);
        }
        $input["company"] = $input["cr_city"];
          $branch =$data["branch"];
        

           $sql = "INSERT INTO ledgers (user_no,refered_by, agent_no, percentage,cl_type, client_subtype, 
  LglNm, TrdNm,company,person,email,phone,phone2,address, website, type,gst_registered,gst_type,tan_no,gst_no, country,
  client_type, dl_no, dl_validity,import_lic_no, branch, status, client_status,telephone_no,
  fax_no, state_code,pan_no, loc, Pin,entry_date,category,c_name,c_address,c_country,ocountry,c_permanent_state,
  c_city,c_pincode,c_mobile_no,c_email,c_gst_applicable,c_scode,c_gst_no,cr_unit_name,cr_address,cr_country,other_country,
  cr_state,cr_city,cr_pincode,cr_mobile_no,cr_gst_applicable,cr_st_code,cr_gst_no,gst_cer,comType,vendor_type,vendor_subtype
  ,material_type,vendor_name,mfglic,cfrom) 
    VALUES ('".$_GET["user_no"]."', '".$data["refered_by"]."', '".$data["agent_no"]."','".$data["percentage"]."', 
    '".$data["cl_type"]."','".$data["subtype"]."', '".$data["LglNm"]."', '".$data["TrdNm"]."','".$data["company"]."',
    '".$data["person"]."','".$data["email"]."','".$data["mobile_no"]."','".$data["mobile_no2"]."','".$data["c_address"]."', 
    '".$data["website"]."', '".$data["type"]."','".$data["gst_registered"]."', '".$data["gst_type"]."',
    '".$data["gst_no"]."', '".$data["tan_no"]."','".$data["country"]."', '".$data["cl_type"]."','".$data["dl_no"]."', 
    '".$data["dl_validity"]."', '".$data["import_lic_no"]."','".$branch."', 'approve', 'active', 
    '".$data["telephone_no"]."','".$data["fax_no"]."',
    '".$data["state_code"]."','".$data["pan_no"]."',  '".$data["state_code"]."', 
    '".$data["c_pincode"]."','".$data["category"]."','$entry_date',
     '".$data["c_name"]."', '".$data["c_address"]."','".$data["c_country"]."',
      '".$data["ocountry"]."', '".$data["c_permanent_state"]."','".$data["c_city"]."',
       '".$data["c_pincode"]."', '".$data["c_mobile_no"]."','".$data["c_email"]."',
        '".$data["c_gst_applicable"]."', '".$data["c_scode"]."','".$data["c_gst_no"]."',
        '".$data["cr_unit_name"]."', '".$data["cr_address"]."','".$data["cr_country"]."',
        '".$data["other_country"]."', '".$data["cr_state"]."','".$data["cr_city"]."',
        '".$data["cr_pincode"]."', '".$data["cr_mobile_no"]."','".$data["cr_gst_applicable"]."',
        '".$data["cr_st_code"]."', '".$data["cr_gst_no"]."','".$photo."',
        '".$data["comType"]."', '".$data["vendor_type"]."','".$data["vendor_subtype"]."',
        '".$data["material_type"]."', '".$data["vendor_name"]."','".$mfglic."','".$data["from"]."')"; 
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            
  
        
        
   $sql1 = "INSERT INTO client (user_no,refered_by, agent_no,division, percentage,cl_type, client_subtype, 
  LglNm, TrdNm,company,person,email,phone,address, website, type,gst_registered,gst_type,gst_no, country,
  client_type, dl_no, dl_validity,import_lic_no, branch, status, client_status,telephone_no,
  fax_no, state_code,pan_no, Addr1, Addr2, Pin,entry_date,category,
  c_name,c_address,c_country,ocountry,c_permanent_state,c_city,c_pincode,c_mobile_no,c_email,c_gst_applicable,
  c_scode,c_gst_no,cr_unit_name,cr_address,cr_country,other_country,cr_state,cr_city,cr_pincode,cr_mobile_no,
  cr_gst_applicable,cr_st_code,cr_gst_no,gst_cer,cfrom) 
    VALUES ('".$_GET["user_no"]."', '".$data["refered_by"]."', '".$data["agent_no"]."',
    '".$data["division"]."','".$data["percentage"]."', 
   '".$data["cl_type"]."', '".$data["subtype"]."', '".$data["LglNm"]."', '".$data["TrdNm"]."','".$data["company"]."',
    '".$data["person"]."','".$data["email"]."','".$data["mobile_no"]."','".$data["address"]."', 
    '".$data["website"]."', '".$data["type"]."','".$data["gst_registered"]."', '".$data["gst_type"]."',
    '".$data["gst_no"]."', '".$data["country"]."', '".$data["cl_type"]."','".$data["dl_no"]."', 
    '".$data["dl_validity"]."', '".$data["import_lic_no"]."','".json_encode($data["branch"])."', 'approve', 'active', 
 '".$data["telephone_no"]."', '".$data["fax_no"]."',
    '".$data["state_code"]."','".$data["pan_no"]."','".$data["Addr1"]."', '".$data["Addr2"]."', 
     '".$data["Pin"]."','$entry_date','".$data["category"]."',
'".$data["c_name"]."', '".$data["c_address"]."','".$data["c_country"]."',
'".$data["ocountry"]."', '".$data["c_permanent_state"]."','".$data["c_city"]."',
 '".$data["c_pincode"]."', '".$data["c_mobile_no"]."','".$data["c_email"]."',
  '".$data["c_gst_applicable"]."', '".$data["c_scode"]."','".$data["c_gst_no"]."',
  '".$data["cr_unit_name"]."', '".$data["cr_address"]."','".$data["cr_country"]."',
  '".$data["other_country"]."', '".$data["cr_state"]."','".$data["cr_city"]."',
  '".$data["cr_pincode"]."', '".$data["cr_mobile_no"]."','".$data["cr_gst_applicable"]."',
  '".$data["cr_st_code"]."', '".$data["cr_gst_no"]."','".$photo."','".$data["from"]."')"; 
  
     $conn->query($sql1);
  
  
  
  
  
 
            
            
            
            
            
            
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
    else  if($_GET["type"]=="saveClient3") {
        $input = $_POST;
        $data = json_decode($input["data"], true);
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $data["c_name"]."photo.".$file_ext;
            $photo = $file_name;
            move_uploaded_file($file_tmp,"../upload/gstcertificate/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $data["mobile_no"]."mfg_lic.".$file_ext;
            $mfglic = $file_name;
            move_uploaded_file($file_tmp,"../upload/gstcertificate/".$file_name);
        }
        if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $data["c_name"]."photo.".$file_ext;
            $photo = $file_name; 
            move_uploaded_file($file_tmp,"../upload/vendor/".$file_name);
        }
        if(isset($_FILES["mfg_lic"])) {
            $file_tmp =$_FILES['mfg_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['mfg_lic']['name'])));
            $file_name = $data["mobile_no"]."mfg_lic.".$file_ext;
            $mfglic = $file_name;
            move_uploaded_file($file_tmp,"../upload/vendor/".$file_name);
        }
        $input["company"] = $input["cr_city"];
          $branch =$data["branch"];
        

           $sql = "INSERT INTO ledgers (user_no,refered_by, agent_no, percentage,cl_type, client_subtype, 
  LglNm, TrdNm,company,person,email,phone,phone2,address, website, type,gst_registered,gst_type,tan_no,gst_no, country,
  client_type, dl_no, dl_validity,import_lic_no, branch, status, client_status,telephone_no,
  fax_no, state_code,pan_no, loc, Pin,entry_date,category,c_name,c_address,c_country,ocountry,c_permanent_state,
  c_city,c_pincode,c_mobile_no,c_email,c_gst_applicable,c_scode,c_gst_no,cr_unit_name,cr_address,cr_country,other_country,
  cr_state,cr_city,cr_pincode,cr_mobile_no,cr_gst_applicable,cr_st_code,cr_gst_no,gst_cer,comType,vendor_type,vendor_subtype
  ,material_type,vendor_name,mfglic,cfrom) 
    VALUES ('".$_GET["user_no"]."', '".$data["refered_by"]."', '".$data["agent_no"]."','".$data["percentage"]."', 
    '".$data["cl_type"]."','".$data["subtype"]."', '".$data["LglNm"]."', '".$data["TrdNm"]."','".$data["company"]."',
    '".$data["person"]."','".$data["email"]."','".$data["mobile_no"]."','".$data["mobile_no2"]."','".$data["c_address"]."', 
    '".$data["website"]."', '".$data["type"]."','".$data["gst_registered"]."', '".$data["gst_type"]."',
    '".$data["gst_no"]."', '".$data["tan_no"]."','".$data["country"]."', '".$data["cl_type"]."','".$data["dl_no"]."', 
    '".$data["dl_validity"]."', '".$data["import_lic_no"]."','".$branch."', 'approve', 'active', 
    '".$data["telephone_no"]."','".$data["fax_no"]."',
    '".$data["state_code"]."','".$data["pan_no"]."',  '".$data["state_code"]."', 
    '".$data["c_pincode"]."','".$data["category"]."','$entry_date',
     '".$data["c_name"]."', '".$data["c_address"]."','".$data["c_country"]."',
      '".$data["ocountry"]."', '".$data["c_permanent_state"]."','".$data["c_city"]."',
       '".$data["c_pincode"]."', '".$data["c_mobile_no"]."','".$data["c_email"]."',
        '".$data["c_gst_applicable"]."', '".$data["c_scode"]."','".$data["c_gst_no"]."',
        '".$data["cr_unit_name"]."', '".$data["cr_address"]."','".$data["cr_country"]."',
        '".$data["other_country"]."', '".$data["cr_state"]."','".$data["cr_city"]."',
        '".$data["cr_pincode"]."', '".$data["cr_mobile_no"]."','".$data["cr_gst_applicable"]."',
        '".$data["cr_st_code"]."', '".$data["cr_gst_no"]."','".$photo."',
        '".$data["comType"]."', '".$data["vendor_type"]."','".$data["vendor_subtype"]."',
        '".$data["material_type"]."', '".$data["vendor_name"]."','".$mfglic."','".$data["from"]."')"; 
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
    
    
    
    
    
    
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   else if ($_GET["type"] == "getBanksLog") {
        $output = Array();
        $sql = "SELECT * FROM banks WHERE plant_id='".$_GET["plant_id"]."' AND status != 'pending'" ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        
        $output[] = $row;
            }
        }
        echo json_encode($output);
   }
   else if ($_GET["type"] == "get_approve_bank") {
        $output = Array();
        $sql = "SELECT * FROM banks WHERE plant_id='".$_GET["plant_id"]."' AND status = 'approve'" ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        
        $output[] = $row;
            }
        }
        echo json_encode($output);
   }
   else if ($_GET["type"] == "getCheckbookLog") {
        $output = Array();
        $sql = "SELECT * FROM cheque_book c left join banks b on c.bank_id =  b.id  WHERE c.plant_id='".$_GET["plant_id"]."'" ;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        
        $output[] = $row;
            }
        }
        echo json_encode($output);
   }
   
   else if ($_GET["type"] == "saveCheckbook") {
       
        $sql = "INSERT INTO cheque_book ( plant_id, bank_name, bank_id, book_no, cheque_no_start, cheque_no_end, prefix_no,cheque_leave) 
        VALUES ('".$_GET["plant_id"]."','".$input["bank_name"]."','".$input["bank_id"]."', '".$input["book_no"]."', '".$input["cheque_no_start"]."', 
        '".$input["cheque_no_end"]."','".$input["prefix_no"]."','".$input["cheque_leave"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
   } 
   
   else if ($_GET["type"] == "getCheckbooks") {
        $output = Array();
        $sql = "SELECT * FROM banks WHERE user_no='".$_GET["user_no"]."'";//AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        
        $output[] = $row;
            }
        }
        echo json_encode($output);
   } else if ($_GET["type"] == "getPendingBanks") {
        $output = Array();
        $sql = "SELECT * FROM banks WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        
        $output[] = $row;
            }
        }
        echo json_encode($output);
        
   } else if ($_GET["type"] == "updateBank") {
      $input["approve_by"] = $_GET["emp_id"];
        $input["approve_date"] = $entry_date;
         $sql = "UPDATE banks SET  status= '".$_GET["status"]."' , approve_by = '".$_GET["emp_id"]."' , approve_date = '$entry_date'
       WHERE id='".$_GET["id"]."'";
        
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
     
       }else  if ($_GET["type"] == "downloadBanksLog") {
      $_GET['filename'] = 'Bank Account List'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
       $html= "";
      
        $html.='
        
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:20%; text-align:centre;"><b>Bank Name</b></td>
                <td style="width:15%; text-align:centre;"><b>IFSC Code</b></td>
                <td style="width:20%; text-align:centre;"><b>Account Number</b></td>
                <td style="width:15%; text-align:centre;"><b>Account Type</b></td>
                <td style="width:15%; text-align:centre;"><b>Branch</b></td>
                <td style="width:15%; text-align:centre;"><b>Status</b></td>
            </tr>';
            $sql = "SELECT * FROM banks WHERE user_no='".$_GET["user_no"]."'";//AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             
            while ($row = $result->fetch_assoc()) {
             $html.='  <tr>
                <td style="width:20%;">'.$row['bank_name'].'</td>
                <td style="width:15%;">'.$row['ifsc'].'</td>
                <td style="width:20%;">'.$row['account_no'].'</td>
                <td style="width:15%;">'.$row['account_type'].'</td>
                <td style="width:15%;">'.$row['branch'].'</td>
                <td style="width:15%;">'.$row['status'].'</td>
            </tr>';
            }
            }
           $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadBanksLog.pdf', 'I');
        
    }else if ($_GET["type"] == "downloadCheckbooks") {
       $_GET['filename'] = 'Bank Account List'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <div></div>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:15%; text-align:centre;"><b>Account No</b></td>
                <td style="width:15%; text-align:centre;"><b>Account Type</b></td>
                <td style="width:10%; text-align:centre;"><b>Bank Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Branch</b></td>
                <td style="width:10%; text-align:centre;"><b>IFSC</b></td>
                <td style="width:10%; text-align:centre;"><b>Book No</b></td>
                <td style="width:10%; text-align:centre;"><b>Cheque No</b></td>
                <td style="width:10%; text-align:centre;"><b>Status</b></td>
            </tr>';
             $sql = "SELECT * FROM banks WHERE user_no='".$_GET["user_no"]."'";//AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'</td>
                <td style="width:15%;">'.$row['account_no'].'</td>
                <td style="width:15%;">'.$row['account_type'].'</td>
                <td style="width:10%;">'.$row['bank_name'].'</td>
                <td style="width:10%;">'.$row['branch'].'</td>
                <td style="width:10%;">'.$row['ifsc'].'</td>
                <td style="width:10%;">'.$row['book_no'].'</td>
                <td style="width:10%;">'.$row['cheque_no'].'</td>
                <td style="width:10%;">'.$row['status'].'</td>
            </tr>';
               $i++;
            }
        }
       $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadBanksLog.pdf', 'I');
        
    }
    
}

$conn->close();
?>