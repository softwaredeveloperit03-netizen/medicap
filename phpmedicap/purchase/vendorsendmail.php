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
    
///mail sernd script and save vendor/////////////////////
    
     if($_GET["type"]=="sendVendorMail") {
    	
        $input = $_POST;
    	$target_dir = "../upload/vendor/";
    	$gst_certificate = "";
    	$mfg_lic_file = "";
    	
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
    	
        $sql = "INSERT INTO vendor(vendor_type,material_type, vendor_for, vendor_name,manufacturer_code,country,state_code,gst_registered,gst_no,pan_no,mfg_lic,email,mobile_no, tel_no1, tel_no2, fax, website, contact_person, address, gst_certificate, mfg_lic_file, vendor_status,entry_by,entry_date,password) VALUES ('".$input["vendor_type"]."','".$input["material_type"]."' ,'".$input["vendor_for"]."','".$input["vendor_name"]."','".$input["manufacturer_code"]."','".$input["country"]."','".$input["state_code"]."','".$input["gst_registered"]."','".$input["gst_no"]."','".$input["pan_no"]."','".$input["mfg_lic"]."','".$input["email"]."','".$input["mobile_no"]."','".$input["tel_no1"]."', '".$input["tel_no2"]."', '".$input["fax"]."', '".$input["website"]."', '".$input["contact_person"]."', '".$input["address"]."', '$gst_certificate', '$mfg_lic_file', '".$input["vendor_status"]."','".$_GET["emp_id"]."','".$entry_date."' ,'123')";
        if($conn->query($sql)){		
		echo "{\"status\":\"success\"}";
		
            $email = $input["email"];
    		$vendor_name = $input["vendor_name"];

		    if ($email !== '') {
		     
    		    require '../phpmailer/class.phpmailer.php';
        		$mail = new PHPMailer();
                $mail->IsSMTP();  
                $mail->Mailer = "smtp";
                $mail->SMTPDebug = 1;
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = 'ssl';
                $mail->Host = "mail.paperlessgmp.live";
                $mail->Port = 587; // or 587
                $mail->IsHTML(true);
                $mail->Username = "vendor@paperlessgmp.live";
                $mail->Password = "Vendor@123";
                $mail->SetFrom("vendor@paperlessgmp.live", "Vendor Portal");
                $mail->Subject = "New Visitor";
                $mail->Body = "Dear sir/Madam , <br>  welcome to Vendor Management Portal of <b> West Coast Pharmaceuticals </b> <br>We Request Please Click Link and Login to Our Vendor Portal. <br>
                              Link: https://wc.paperlessgmp.online/#/ <br>
                              Vendor Name: $vendor_name<br>
                              Login ID: $vendor_no <br>
                              Password:123 <br>
                              Please do remember to update your profile and product list.<br>
                              Purchase Manager ,<br>
                              ( West Coast Pharmaceuticals)<br>
                              Mobile No.: <b>
                              " ;
                $mail->addAddress($email);
                $mail->Send();
		}
		
		$api_key = '3603A19FD7CB99';
        $contacts = $input["phoneNumber"];
        $from = 'GMPSOF';
        $sms_text = urlencode('Welcome to West Coast Pharmaceuticals Vendor Portal. Thanks');
        
        //Submit to server
        
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, "http://sms.nationalsms.in/app/smsapi/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&entity=1201161443246142266&tempid=1207162202188669391&routeid=459&type=text&contacts=".$contacts."&senderid=".$from."&msg=".$sms_text);
        $response = curl_exec($ch);
        curl_close($ch);

		/* $api_key = '35BED730028942';
		$contacts = $input["phoneNumber"];
		$from = 'CSRETL';
		$sms_text = urlencode('Welcome to Shri Bhavani Pharmaceuticals. Your Meeting with '.$input["meeting"].' has been scheduled.');
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, "http://sms.sunstechit.com/app/smsapi/index.php");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&campaign=0&routeid=13&type=text&contacts=".$contacts."&senderid=".$from."&msg=".$sms_text);
		$response = curl_exec($ch);
		curl_close($ch); */
		
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
    }else if ($_GET["type"] == "getVendors") {
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE vendor_no = '".$_GET["emp_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["units"] = json_decode($row["units"]);
                echo json_encode($row);
            }
        }
    }
}

$conn->close();

?>