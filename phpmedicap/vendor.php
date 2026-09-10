<?php


// ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()) {
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
      $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "saveVendor") {
        $vendorName = trim($input["vendor_name"]);
        $contactNumber = trim($input["contact_number"]);
        $plantId = $conn->real_escape_string($_GET["plant_id"]);
        $escapedName = $conn->real_escape_string($vendorName);
        $escapedPhone = $conn->real_escape_string($contactNumber);

        $sqlDupName = "SELECT id FROM vendor WHERE plant_id='".$plantId."' AND LOWER(TRIM(vendor_name)) = LOWER(TRIM('".$escapedName."')) LIMIT 1";
        $resultDupName = $conn->query($sqlDupName);
        if ($resultDupName && $resultDupName->num_rows > 0) {
            echo "{\"status\":\"Vendor with this name already exists\"}";
        } else {
        $sqlDupPhone = "SELECT id FROM vendor WHERE plant_id='".$plantId."' AND TRIM(contact_number) = TRIM('".$escapedPhone."') LIMIT 1";
        $resultDupPhone = $conn->query($sqlDupPhone);
        if ($resultDupPhone && $resultDupPhone->num_rows > 0) {
            echo "{\"status\":\"Vendor with this phone number already exists\"}";
        } else {

        $sql = "INSERT INTO `vendor`(`plant_id`,`material_type`, `vendor_type`, `vendor_name`, `contact_person`, `contact_number`, `contact_email`,`address`, `country`, `permanent_state`, `city`, `pincode`,`qualifiedBy`,`client_code`, 
        `gst_applicable`, `scode`, `gst_no`,`panNo`,`other_contact`, `c_unit_name`, `c_address`, `c_country`, `c_state`,`c_city`, `c_pincode`, `c_mobile_no`, `c_gst_applicable`, `c_scode`, `c_gst_no`,`c_panNo`, `status`, 
        `password`,`vendorFor`, `bank_name`, `branch_address`, `account_holder`, `account_number`, `ifsc_code`, `payment_mode`, `entry_by`, `entry_date`,`currency`,`vendor_Is`,`parentVendor`) VALUES ('".$_GET["plant_id"]."','".$input["material_type"]."','".$input["vendor_type"]."' ,
        '".$input["vendor_name"]."','".$input["contact_person"]."','".$input["contact_number"]."','".$input["contact_email"]."','".$input["address"]."','".$input["country"]."','".$input["permanent_state"]."','".$input["city"]."', 
        '".$input["pincode"]."','".$input["qualifiedBy"]."','".$input["client_code"]."','".$input["gst_applicable"]."','".$input["scode"]."','".$input["gst_no"]."','".$input["panNo"]."','".json_encode($input["other_contact"])."',
        '".$input["c_unit_name"]."','".$input["c_address"]."','".$input["c_country"]."','".$input["c_state"]."','".$input["c_city"]."','".$input["c_pincode"]."','".$input["c_mobile_no"]."','".$input["c_gst_applicable"]."','".$input["c_scode"]."',
        '".$input["c_gst_no"]."','".$input["c_panNo"]."','Pending','VHN@2026','".$input["vendorFor"]."','".$input["bank_name"]."','".$input["branch_address"]."','".$input["account_holder"]."','".$input["account_number"]."','".$input["ifsc_code"]."',
        '".$input["payment_mode"]."','".$_GET["emp_id"]."','".$entry_date."' ,'".json_encode($input["selectedCurrencies"])."' ,'".$input["vendor_Is"]."' ,'".$input["parentVendor"]."')";
 
     
    	if($conn->query($sql)) {
    		echo "{\"status\":\"success\"}";
    	}else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}

        }
        }
    	
    } 
    else if ($_GET["type"] == "getVendorLog") {
        $output = Array();
        $sql = "SELECT * FROM vendor  WHERE plant_id='".$_GET["plant_id"]."'  order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getVendorByVendorNO") {
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE vendor_no='".$_GET["emp_id"]."' AND  plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["other_contact"] = json_decode($row["other_contact"]);
                $output = $row;
            }
        }
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
    else if ($_GET["type"] == "getApprovedVendors") {
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
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Approved' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getProvisionalVendors") {
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
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Provisional' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getVendorUnit") {
        // error_reporting(0);
        $sql = "SELECT * FROM vendor WHERE user_no='".$_GET["user_no"]."' AND vendor_no='".$_GET["vendor_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                break;
            }
        } else {
            echo "{}";
        }
    } 
    
    
       else if ($_GET["type"] == "saveVendordiv") {
    
    	//$input = $_POST;
       $sql1 = "SELECT MAX(id) as id FROM vendor";
        
        $result1 = $conn->query($sql1);
        $row1 = $result1->fetch_assoc();
        $last_id=$row1["id"]+1; 
        
        $zeros_needed = 4 - strlen($last_id);

        $zeros = str_repeat('0', $zeros_needed);
        $vendor_no = "V1" . $zeros . $last_id;
 


        
        
    $sql = "INSERT INTO vendor(plant_id,vendor_no,pr_id,vendor_type,  vendor_name,country,state_name,mobile_no, contact_person,
    contact_number,contact_email,email,address,vendor_status,status,entry_by,entry_date,city,unit_name,pincode,c_unit_name,c_address,
    c_country,c_state,c_city,c_pincode, c_mobile_no,c_gst_applicable,gst_applicable) VALUES ('".$_GET["plant_id"]."',
    '$vendor_no','".$input["pr_id"]."','".$input["vendor_type"]."','".$input["vendor_name"]."','".$input["country"]."',
    '".$input["permanent_state"]."','".$input["contact_number"]."','".$input["contact_person"]."','".$input["contact_number"]."',
    '".$input["contact_email"]."','".$input["contact_email"]."', '".$input["address"]."','Checking','Checking','".$_GET["emp_id"]."',
    '".$entry_date."','".$input["city"]."','".$input["vendor_name"]."','".$input["pincode"]."','".$input["c_unit_name"]."',
    '".$input["c_address"]."','".$input["c_country"]."','".$input["c_state"]."','".$input["c_city"]."','".$input["c_pincode"]."',
    '".$input["contact_number"]."','No','No')";
    
    	if($conn->query($sql)) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    } 
    else if ($_GET["type"] == "saveTermsAndConditons") {
        
        $sql = "UPDATE vendor SET terms_conditions = '".json_encode($input["terms_conditions"])."' , tandCBy = '".$_GET['emp_id']."' , tandCOn = '$entry_date' WHERE vendor_no = '".$input['vendor_no']."'";
        
    	if($conn->query($sql)) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    }
    else if ($_GET["type"] == "savePaymentTerms") {
        
        $sql = "UPDATE vendor SET paymentTerms = '".json_encode($input["paymentTerms"])."' , payTandCBy = '".$_GET['emp_id']."' , payTandCOn = '$entry_date' WHERE vendor_no = '".$input['vendor_no']."'";
        
    	if($conn->query($sql)) {
    		echo "{\"status\":\"success\"}";
    		
            	$sql = "INSERT INTO `vendorTerms`(`plant_id`, `term_heading`, `term`, `termValidDate`, `vendor_no`, `status`, `entry_by`, `entry_date`) VALUES  ('".$_GET["plant_id"]."', 'Payment Terms', 
                '".json_encode($input["paymentTerms"])."' , '".$input["termValidDate"]."' ,  '".$input["vendor_no"]."', 'Pay_Term', '".$_GET["emp_id"]."', '$entry_date' )";
            	$conn->query($sql);
            	
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    }
    else if ($_GET["type"] == "getPendingVendorIdentifications") {
        $output = array();
        $sql = "SELECT * FROM vendor_identification WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIdentification") {
        $sql = "UPDATE vendor_identification SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getVendorIdentificationLog") {
        $output = array();
        $sql = "SELECT * FROM vendor_identification WHERE user_no='".$_GET["user_no"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "blacklistVendor") {
        $sql = "UPDATE vendor SET status='Blacklisted' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
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
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='Blacklisted' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      else if ($_GET["type"] == "downloadApprovedVendors") {
        $_GET['filename'] = 'Approved vendors Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp.php");
        $html= "";
        
        $html.='<table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:5%;">Sr No</td>
                        <td style="width:15%;">Vendor Type</td>
                        <td style="width:15%;">Vendor For</td>
                        <td style="width:15%;">Vendor No</td>
                        <td style="width:15%;">Vendor Name</td>
                        <td style="width:20%;">Email</td>
                        <td style="width:10%;">State</td>
                    </tr>
                        ';
            $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Approved' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
            $html.='<tr>
                        <td style="width:5%;">'.$i++.'</td>
                        <td style="width:15%;">'.$row['vendor_type'].'</td>
                        <td style="width:15%;">'.$row['vendor_for'].'</td>
                        <td style="width:15%;">'.$row['vendor_no'].'</td>
                        <td style="width:15%;">'.$row['vendor_name'].'</td>
                        <td style="width:20%;">'.$row['email'].'</td>
                        <td style="width:10%;">'.$row['state_name'].'</td>
                    </tr>';
                }
            }
        $html.="</table>";
      

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Approved vendor Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadProvisionalVendors") {
        $_GET['filename'] = 'Provisional vendors Log'; $_GET['pdftype'] = 'onlyheader'; include("pdfimp.php");
        $html= "";
        
        $html.='<table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:5%;">Sr No</td>
                        <td style="width:15%;">Vendor Type</td>
                        <td style="width:15%;">Vendor For</td>
                        <td style="width:15%;">Vendor No</td>
                        <td style="width:15%;">Vendor Name</td>
                        <td style="width:20%;">Email</td>
                        <td style="width:15%;">State</td>
                    </tr>
                        ';
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Provisional' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
            $html.='<tr>
                        <td style="width:5%;">'.$i++.'</td>
                        <td style="width:15%;">'.$row['vendor_type'].'</td>
                        <td style="width:15%;">'.$row['vendor_for'].'</td>
                        <td style="width:15%;">'.$row['vendor_no'].'</td>
                        <td style="width:15%;">'.$row['vendor_name'].'</td>
                        <td style="width:20%;">'.$row['email'].'</td>
                        <td style="width:15%;">'.$row['state_name'].'</td>
                    </tr>';
                }
            }
        $html.="</table>";
      

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Approved vendor Log.pdf', 'I');
    }

}

$conn->close();
?>