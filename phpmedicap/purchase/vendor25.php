<?php
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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()) {
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
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
    	
        $sql = "INSERT INTO vendor(user_no,vendor_no,vendor_type, vendor_for, vendor_name,address_corporate,
        address_factory,location,city,state_code,email,contact_person,gst_no,mfg_lic,pan_no,entry_by,entry_date, 
        gst_certificate, mfg_lic_file, supplier_lic, incorporation_certificate, pan_card, units, due_days, 
        vendor_status, gst_applicable,pincode) VALUES ('".$_GET["user_no"]."','$vendor_no',
        '".$_POST["vendor_type"]."', '".$_POST["vendor_for"]."','".$_POST["vendor_name"]."',
        '".$_POST["address_corporate"]."','".$_POST["address_factory"]."','".$_POST["location"]."',
        '".$_POST["city"]."','".$_POST["state_code"]."','".$_POST["email"]."','".$_POST["contact_person"]."',
        '".$_POST["gst_no"]."','".$_POST["mfg_lic"]."','".$_POST["pan_no"]."','".$_GET["emp_id"]."',
        '".$entry_date."', '$gst_certificate', '$mfg_lic_file', '$supplier_lic', '$incorporation_certificate', 
        '$pan_card',  '".json_encode($input["units"])."', '".$_POST["due_days"]."', '".$_POST["vendor_status"]."', '".$_POST["gst_applicable"]."','".$_POST["pincode"]."')";
    	if($conn->query($sql)) {
    	    $last_id = $conn->insert_id;
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "approve_vendor") {
        $sql = "UPDATE vendor SET status='Approved'
           WHERE id='".$_GET["id"]."'";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "update_vendor_status") {
        $sql = "UPDATE vendor SET  status ='".$_GET["status"]."'  
           WHERE id='".$_GET["id"]."'   ";
           
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "updateVendor") {
        $sql = "UPDATE vendor SET vendor_type='".$input["vendor_type"]."', vendor_for='".$input["vendor_for"]."', 
        vendor_name='".$input["vendor_name"]."', vendor_status='".$input["vendor_status"]."', 
        address_corporate='".$input["address_corporate"]."', location='".$input["location"]."', 
        city='".$input["city"]."', state_code='".$input["state_code"]."', email='".$input["email"]."', 
        contact_person='".$input["contact_person"]."', gst_no='".$input["gst_no"]."', mfg_lic='".$input["mfg_lic"]."',
        pan_no='".$input["pan_no"]."', due_days='".$input["due_days"]."' ";
       // WHERE vendor_no='".$input["vendor_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingVendors") {
        $output = Array();
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getVendors") {
        $output = Array();
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
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
                // $row = array_map('utf8_encode', $row["units"]);
                echo $row["units"];
                break;
            }
        } else {
            echo "{}";
        }
    } else if ($_GET["type"] == "getVendorLog") {
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
           WHERE v.material_type LIKE '%".$_GET["material_type"]."%' and v.plant_id = '".$_GET["plant_id"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
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
          $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
        WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Approved' 
            AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' 
            AND v.state_code LIKE '%".$_GET["state_code"]."%' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
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
        $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
        WHERE v.user_no='".$_GET["user_no"]."' AND v.status='approve' AND v.vendor_status='Provisional' AND v.vendor_type LIKE '%".$_GET["vendor_type"]."%' AND v.vendor_for LIKE '%".$_GET["vendor_for"]."%' AND v.state_code LIKE '%".$_GET["state_code"]."%'";
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
    else if($_GET['type'] == 'downloadVendorLog'){
        $_GET['filename'] = 'Vendor Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:cenetr">Vendor Log</h2>
        <table cellpadding="5" style="text-align:center;">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">Sr.No</td>
                <td style="width:15%;">Date</td>
                <td style="width:12%;">Vendor For</td>
                <td style="width:10%;">Vendor No.</td>
                <td style="width:13%;">Vendor Type</td>
                <td style="width:15%;">Vendor Name</td>
                <td style="width:15%;">Email</td>
                <td style="width:15%;">State</td>
            </tr>';
            $sql = "SELECT v.*, s.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.material_type LIKE '%".$_GET["material_type"]."%'  ORDER BY id DESC";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                
                $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'.</td>
                    <td style="width: 15%;">'.date('d-m-y',strtotime($row['entry_date'])).'</td>
                    <td style="width: 12%;">'.$row['vendor_for'].'</td>
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
    }  else if($_GET['type'] == 'downloadApprovedVendors'){
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
    }  else if($_GET['type'] == 'downloadBlacklistedVendors'){
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

$conn->close();

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