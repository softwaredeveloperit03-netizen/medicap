<?php
    require 'db.php';
    require 'token.php';
    require './tcpdf/tcpdf.php'; 

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

 if($_GET["type"]=="savelabour"){
        $sql = "INSERT INTO labour(labour_id,labour_name,dob,address,contractor_name,daily_wages,gender,scan_upload,category,entry_by,entry_date) VALUES ('$labour_id','".$input['labour_name']."','".$input['batch_no']."','".$input['	batch_size']."','".$input['	mfg_date']."','".$input['exp_date']."','".$input['shippers']."','".$input['release_date']."','".$input['observation']."','".$_GET['emp_id']."','$entry_date')";
       $result = $conn->query($sql);
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\"}";
        }
        
}
else if ($_GET["type"]=="AddLabourContractor") {
    
    
        $input = $_POST;
        
        $pid = $_GET["plant_id"];
        $rId = random_int(1000, 9999);
        $person = random_int(1000, 9999);


        $file_pf = 'NA';
        if (isset($_FILES["file_pf"]) && $_FILES["file_pf"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_pf']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId."file_pf.". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_pf']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $file_pf = $conn->real_escape_string($fileName);
        }
        
        $file_esic_no = 'NA';
        if (isset($_FILES["file_esic_no"]) && $_FILES["file_esic_no"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_esic_no']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId."file_esic_no.". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_esic_no']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $file_esic_no = $conn->real_escape_string($fileName);
        }
        
        $file_gst_no = 'NA';
        if (isset($_FILES["file_gst_no"]) && $_FILES["file_gst_no"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_gst_no']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId."file_gst_no.". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_gst_no']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $file_gst_no = $conn->real_escape_string($fileName);
        }
        
        $file_labour_lic = 'NA';
        if (isset($_FILES["file_labour_lic"]) && $_FILES["file_labour_lic"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_labour_lic']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId."file_labour_lic.". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_labour_lic']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $file_labour_lic = $conn->real_escape_string($fileName);
        }
        
        $file_labour_pri = 'NA';
        if (isset($_FILES["file_labour_pri"]) && $_FILES["file_labour_pri"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_labour_pri']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId."file_labour_pri.". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_labour_pri']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $file_labour_pri = $conn->real_escape_string($fileName);
        }
        
        $file_other = 'NA';
        if (isset($_FILES["file_other"]) && $_FILES["file_other"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_other']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId."file_other.". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_other']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $file_other = $conn->real_escape_string($fileName);
        }
        
    
        $sql = "INSERT INTO `labour_contractor`(`plant_id`,`contract_firm`, `person`, `address`, `place`, `landmark`, `pincode`, `phone_no`, `email`, `capacity`, `bank`, `branch`, `ac_no`, `ifsc`, `file_pf`, `file_esic_no`, 
        `file_gst_no`, `file_labour_pri`, `file_labour_lic`, `file_other` ,`status`,`entry_by`,`entry_date`) VALUES ('".$_GET['plant_id']."','".$input['contract_firm']."','".$input['person']."','".$input['address']."','".$input['place']."',
        '".$input['landmark']."','".$input['pincode']."','".$input['phone_no']."','".$input['email']."','".$input['capacity']."','".$input['bank']."','".$input['branch']."','".$input['ac_no']."',
        '".$input['ifsc']."','$file_pf','$file_esic_no','$file_gst_no','$file_labour_pri','$file_labour_lic','$file_other','Pending','".$_GET['emp_id']."','$entry_date') ";
   
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    }
    else if ($_GET["type"]=="updateLabourCOntractor") {
     
    
         $sql = "UPDATE `labour_contractor` SET contract_firm = '".$input['contract_firm']."', person = '".$input['person']."', address = '".$input['address']."', place = '".$input['place']."', 
        landmark = '".$input['landmark']."', pincode = '".$input['pincode']."', phone_no = '".$input['phone_no']."', email = '".$input['email']."', capacity = '".$input['capacity']."', bank = '".$input['bank']."', 
        branch = '".$input['branch']."', ac_no = '".$input['ac_no']."', ifsc = '".$input['ifsc']."' where id = '".$input['id']."'";
   
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    }
    
    else if ($_GET["type"]=="uploadFile") {
    
    
        $input = $_POST;
        
        $pid = $_GET["plant_id"];
        $rId = random_int(1000, 9999);
        $person = random_int(10000, 99999);
        $fileType = $input['fileType'];
        

        $uploadFile = 'NA';
        if (isset($_FILES["file_pf"]) && $_FILES["file_pf"]["error"] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['file_pf']['name'], PATHINFO_EXTENSION));
            $fileName = $pid.$person.$rId.$fileType.".". $fileExt;
            $targetPath = "../../upload/contractor/doc/" . basename($fileName);
            if (!move_uploaded_file($_FILES['file_pf']['tmp_name'], $targetPath)) { echo json_encode(["status" => "File upload failed"]); exit; }
            $uploadFile = $conn->real_escape_string($fileName);
        }
         
    
        $sql = "UPDATE `labour_contractor` SET $fileType = '$uploadFile' WHERE id = '".$input['id']."'";
   
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    }
    else if ($_GET["type"] == "getContractorlist") {
        
        $sql = "SELECT * FROM labour_contractor where plant_id = '".$_GET['plant_id']."'  ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    else if ($_GET["type"] == "getContractorlist1") {
        $sql = "SELECT * FROM labour_contractor  where agreement_status='1' ORDER BY id DESC";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "getApprovedContractor") {
    $sql = "SELECT * FROM labour_contractor   ORDER BY id DESC";
    // $sql = "SELECT * FROM labour_contractor where status='approve' ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getApprovedContractoragreement") {
    $sql = "SELECT * FROM labour_contractor where agreement_status='1' ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getcontractor") {
    $sql = "SELECT * FROM labour_contractor ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        }
    echo json_encode($output);
} 
else if ($_GET["type"] == "addcontract") {
             
                    	$target_dir = "../../upload/contractor/doc/";

            $cid = $_GET["cid"];
            
        if(isset($_FILES["bond"])) {
            $file_tmp =$_FILES['bond']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['bond']['name'])));
            $file_name = $cid."bond.".$file_ext;
            $bond = $file_name;
            move_uploaded_file($file_tmp,"$target_dir".$file_name);
        }
    


    $sql = "UPDATE labour_contractor SET agreement_status = '1', bond ='$bond' WHERE id = '$cid'";
    if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":. $conn->error}";
        }
} 
else if ($_GET["type"] == "getLabourDetails") {
    $sql = "SELECT * FROM labour ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
    
}  else if($_GET["type"]=="saveLabours"){

	$target_dir = "upload/labour/";
	$file1 = "";
	$file2 = "";
	$aadhar_card = "";
	$photo = "";
	$emp_id;
	$labour_name = $_POST["labour_name"];
	$dob = $_POST["dob"];
	$address = $_POST["address"];
	$contractor_name = $_POST["contractor_name"];
	$category = $_POST["category"];
	$daily_wages = $_POST["daily_wages"];
	$gender = $_POST["gender"];
	
	$emp_id1++;
	$sql = "SELECT MAX(id) as labour_id FROM labour_management";
	 $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "PID_0".$i_no1;
        } else if ($num == 2) {
            $no = "PID_".$i_no1;
        }

	$sql = "INSERT INTO labour_management(plant_id,labour_id,labour_name, dob,address, contractor_name, daily_wages, gender, scan_upload,photo,category,entry_by,entry_date) 
	VALUES ('".$_GET["plant_id"]."','$emp_id','$labour_name','$dob','$address','$contractor_name','$daily_wages','$gender','$aadhar_card','$photo','$category','".$_GET["emp_id"]."','$entry_date')";
	if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} 
    else if ($_GET["type"]=="updateContractor") {
        
        $sql = "UPDATE labour_contractor SET status = '".$input["status"]."', approveBy = '".$_GET["emp_id"]."', approveOn = '$entry_date'  WHERE id = ".$input["id"];
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
      
    } 
    else if ($_GET["type"]=="updateLabour") {
        $sql = "UPDATE labour_management SET isapprove='Approved', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id=".$_GET["id"];
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"]=="AddTransportor") {

        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM transporter_management";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "TID0".$i_no1;
        } else if ($num == 2) {
            $no = "TID".$i_no1;
        }
        
         if ($_POST['isgoods'] == 'true') {
             $_POST['isgoods'] = "Yes";
         } else {
            $_POST['isgoods'] = "No";
         }
         
         if ($_POST['ispassenger'] == 'true') {
             $_POST['ispassenger'] = "Yes";
         } else {
            $_POST['ispassenger'] = "No";
         }

        $sql = "INSERT INTO transporter_management(transporter_id, entry_date, vendor_company, person, address, pincode, phone_no, email, isgoods, ispassenger, i_no1) VALUES
        ('$no', '$entry_date', '".$_POST['vendor_company']."', '".$_POST['person']."', '".$_POST['address']."', '".$_POST['pincode']."', '".$_POST['phone_no']."', '".$_POST['email']."', '".$_POST['isgoods']."', '".$_POST['ispassenger']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if ($_GET["type"] == "getTransportorslist") {
    $sql = "SELECT * FROM transporter_management ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="AddVehicleDetails") {

        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM vehicle_management";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "VID0".$i_no1;
        } else if ($num == 2) {
            $no = "VID".$i_no1;
        }
        
         if ($_POST['goods'] == true) {
             $_POST['goods'] = "Yes";
         } else {
            $_POST['goods'] = "No";
         }
         
         if ($_POST['passenger'] == true) {
             $_POST['passenger'] = "Yes";
         } else {
            $_POST['passenger'] = "No";
         }

        $sql = "INSERT INTO vehicle_management(vehicle_id, entry_date, vehicle_name, vehicle_model, vehicle_no, fuel_type, capacity, goods, passenger, vendor_name, phone_no, i_no1) VALUES
        ('$no', '$entry_date', '".$_POST['vehicle_name']."', '".$_POST['vehicle_model']."', '".$_POST['vehicle_no']."', '".$_POST['fuel_type']."', '".$_POST['capacity']."', '".$_POST['goods']."', '".$_POST['passenger']."', '".$_POST['vendor_name']."', '".$_POST['phone_no']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if ($_GET["type"] == "getVehiclelist") {
    $sql = "SELECT * FROM vehicle_management ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="addComplaint") {
        $input = json_decode(file_get_contents('php://input'),true);
        $sql = "SELECT IFNULL(MAX(comp_id1), 0) as comp_id1 FROM transport_complaint";
        $result = $conn->query($sql);
        $no = "";
        $comp_id1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $comp_id1 = $row["comp_id1"];
            }
        }
        $comp_id1++;
        $num = strlen($comp_id1);
        if ($num == 1) {
            $no = "CID0".$comp_id1;
        } else if ($num == 2) {
            $no = "CID".$comp_id1;
        }
        
        if ($input['transporter'] == true) {
            $input['transporter'] = "Transporter Related";
        } else {
            $input['transporter'] = "No";
        }
        
        if ($input['vehicle'] == true) {
            $input['vehicle'] = "Vehicle Related";
        } else {
            $input['vehicle'] = "No";
        }
        
        if ($input['other'] == true) {
            $input['other'] = "Other";
        } else {
            $input['other'] = "No";
        }
        $sql = "INSERT INTO transport_complaint (comp_id, received_from, country, received_through, contact_no, transporter, vehicle, other, deatils, date, comp_id1) 
        VALUES ('$no', '".$input["received_from"]."', '".$input["country"]."', '".$input["received_through"]."', '".$input["contact_no"]."', '".$input["transporter"]."', '".$input["vehicle"]."',  '".$input["other"]."', '".$input["deatils"]."', '$entry_date', '$comp_id1')"; 
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        }
        else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if($_GET["type"]=="getComplaintlist") {
    	$sql = "SELECT * FROM transport_complaint ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
    	}
} else if($_GET["type"]=="getVehicleById") {
    $sql = "SELECT vehicle_no FROM vehicle_management WHERE vendor_name='".$_GET["selectedvendor_name"]."' ";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else {
        echo "[]";
    }
} else if ($_GET["type"]=="AddTransporterBill") {
    
        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM transporter_bill";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "BID_0".$i_no1;
        } else if ($num == 2) {
            $no = "BID_".$i_no1;
        }

    $file1 = "images/admin/".$_FILES['file_bill']['name'];
    $file_loc1 = $_FILES['file_bill']['tmp_name'];
    $final_file1=str_replace(' ','-',$file1);
  
    if((move_uploaded_file($file_loc1,$final_file1)))
    {
        $sql = "INSERT INTO transporter_bill(transporter_bill_id, date, from_date, to_date, file_bill, vendor_name, vehicle_no, km, i_no1) VALUES
        ('$no', '$entry_date', '".$_POST['from_date']."', '".$_POST['to_date']."', '$final_file1','".$_POST['vendor_name']."', '".$_POST['vehicle_no']."', '".$_POST['km']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
} else if($_GET["type"]=="getBillData") {
    	$sql = "SELECT * FROM transporter_bill ORDER BY id DESC";
     $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="AddLaundryData") {

        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM laundry";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "LID0".$i_no1;
        } else if ($num == 2) {
            $no = "LID".$i_no1;
        }
        
        $sql = "INSERT INTO laundry(laundry_id, date, department_name, uniform_type, no_units, size, i_no1) VALUES
        ('$no', '$entry_date', '".$_POST['department_name']."', '".$_POST['uniform_type']."', '".$_POST['no_units']."', '".$_POST['size']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if($_GET["type"]=="getLaundrylist") {
    	$sql = "SELECT * FROM laundry ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
    	}
  
} else if ($_GET["type"] == "saveGownsEntry") {
    
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM gowns_distribution";
    $i_no1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"]; 
            break;
        }
    }
    
    $i_no1++;
    $num_length = strlen((string)$i_no1);
    
    if($num_length == 1) {
       $distribution_id= "DRID00".$i_no1;
    } else if($num_length == 2) {
       $distribution_id = "DRID0".$i_no1;
    } else {
       $distribution_id = "DRID".$i_no1; 
    }
    
    $sql = "INSERT INTO gowns_distribution(distribution_id, entry_date, department_name, i_no1)
    VALUES ('$distribution_id', '$entry_date', '".$_POST['department_name']."', $i_no1)";
    
    if ($conn->query($sql) === TRUE) {
        
        $flag = 0;
        $gowns = json_decode($_POST["gowns"], true);
        $length = sizeof($gowns);
    	
    	for($i = 0; $i < $length; $i++) {
    	    $data = $gowns[$i];
    	    $sql = "INSERT INTO gowns(distribution_id, emp_name, size, no_units) VALUES ('$distribution_id', '".$data["emp_name"]."', '".$data["size"]."', '".$data["no_units"]."')";
    	    if ($conn->query($sql) === TRUE) {
    	        $flag = 0;
    	    } else {
    	        $flag = 1;
    	    }
    	}
    	
    	if ($flag == 0) {
            echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
    
} else if ($_GET["type"]=="AddLaundryRegistration") {

        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM laundry_registration";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "LID0".$i_no1;
        } else if ($num == 2) {
            $no = "LID".$i_no1;
        }
        
        $sql = "INSERT INTO laundry_registration(laundry_id, date, vendor_name, contact_person, address, phone_no, gst_no, i_no1) VALUES
        ('$no', '$entry_date', '".$_POST['vendor_name']."', '".$_POST['contact_person']."', '".$_POST['address']."', '".$_POST['phone_no']."', '".$_POST['gst_no']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if($_GET["type"]=="getLaundryRegistration") {
    	$sql = "SELECT * FROM laundry_registration ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
        }
} else if($_GET["type"]=="getGownsList") {
    
        $sql = "SELECT * from gowns_distribution ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select distribution_id, emp_name, size, no_units from gowns where distribution_id = '".$row['distribution_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["Gowns"] = $data;
                $output[] = $row;
            }
        }
    
        echo json_encode($output);
} else if ($_GET["type"]=="AddGownTestingRecord") {
    
        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM appron_testing";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "TID_0".$i_no1;
        } else if ($num == 2) {
            $no = "TID_".$i_no1;
        }

    $file1 = "images/admin/".$_FILES['file_certificate']['name'];
    $file_loc1 = $_FILES['file_certificate']['tmp_name'];
    $final_file1=str_replace(' ','-',$file1);
  
    if((move_uploaded_file($file_loc1,$final_file1)))
    {
        $sql = "INSERT INTO appron_testing(testing_id, testing_date, gown_type, lab_name, file_certificate, address, contact_no, i_no1) VALUES
        ('$no', '".$_POST['testing_date']."', '".$_POST['gown_type']."', '".$_POST['lab_name']."', '$final_file1','".$_POST['address']."', '".$_POST['contact_no']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
} else if($_GET["type"]=="getTestingData") {
    	$sql = "SELECT * FROM appron_testing ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
    	}
   
} else if ($_GET["type"] == "saveDailyWork") {

    header('Content-Type: application/json; charset=utf-8');

    $entry_date  = isset($_POST['entry_date']) ? trim($_POST['entry_date']) : '';
    $activity    = isset($_POST['activity']) ? trim($_POST['activity']) : '';
    $approx_time = isset($_POST['approx_time']) ? trim($_POST['approx_time']) : '';
    $steps       = $_POST['steps'] ?? '';
    $emp_id      = isset($_POST['emp_id']) ? trim($_POST['emp_id']) : (isset($_GET['emp_id']) ? trim($_GET['emp_id']) : '');

    if ($entry_date === '' || $activity === '' || $approx_time === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Missing required fields (entry_date, activity, approx_time).',
        ]);
        exit;
    }

    // Generate i_no1
    $sql = "SELECT IFNULL(MAX(i_no1), 0) AS i_no1 FROM labour_daily_work";
    $i_no1 = 0;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $i_no1 = (int) $row['i_no1'];
    }
    $i_no1++;

    // Generate work_id
    if ($i_no1 < 10) {
        $work_id = 'WID00' . $i_no1;
    } elseif ($i_no1 < 100) {
        $work_id = 'WID0' . $i_no1;
    } else {
        $work_id = 'WID' . $i_no1;
    }

    $stmt = $conn->prepare(
        'INSERT INTO labour_daily_work (work_id, entry_date, activity, approx_time, checked_by, i_no1)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param('sssssi', $work_id, $entry_date, $activity, $approx_time, $emp_id, $i_no1);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Steps: comma-separated string from client, or array
    $stepsArray = [];
    if ($steps !== '' && $steps !== null) {
        if (is_string($steps)) {
            $stepsArray = array_map('trim', explode(',', $steps));
        } elseif (is_array($steps)) {
            $stepsArray = $steps;
        }
    }

    $flag = 0;
    $labourStmt = $conn->prepare('INSERT INTO labour_data (work_id, labour_name) VALUES (?, ?)');
    if (!$labourStmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    foreach ($stepsArray as $step) {
        $step = trim((string) $step);
        if ($step === '') {
            continue;
        }
        $labourStmt->bind_param('ss', $work_id, $step);
        if (!$labourStmt->execute()) {
            $flag = 1;
        }
    }
    $labourStmt->close();

    if ($flag === 0) {
        echo json_encode([
            'status'   => 'success',
            'work_id'  => $work_id,
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => $conn->error,
        ]);
    }
} else if($_GET["type"]=="getDailyWorkDetails") {
    
        $sql = "SELECT * from labour_daily_work  ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select * from labour_management where labour_id = '".$row['work_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["steps"] = $data;
                $output[] = $row;
            }
        }
    echo json_encode($output);
} else if ($_GET["type"]=="savePlantCount") {
    
        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM gardan_plant";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "PID_0".$i_no1;
        } else if ($num == 2) {
            $no = "PID_".$i_no1;
        }

        $sql = "INSERT INTO gardan_plant(plant_id, plant_name, count, plantation_date, plant_age, i_no1) VALUES
        ('$no', '".$_POST['plant_name']."', '".$_POST['count']."', '".$_POST['plantation_date']."', '".$_POST['plant_age']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if($_GET["type"]=="getPlantCount") {
    	$sql = "SELECT * FROM gardan_plant ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
   }
} else if ($_GET["type"]=="addResource") {
        $input = json_decode(file_get_contents('php://input'),true);
        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM gardan_resource";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "RID_0".$i_no1;
        } else if ($num == 2) {
            $no = "RID_".$i_no1;
        }
        
        if ($input['urgent'] == true) {
            $input['required_urgent'] = "Required Urgent";
        } else {
            $input['required_urgent'] = "Not Required";
        }
        
         if ($input['usual'] == true) {
            $input['required_usual'] = "Required Usual";
        } else {
            $input['required_usual'] = "Not Required";
        }

        $sql = "INSERT INTO gardan_resource(resource_id, date, required_resources, details, required_urgent, required_usual, i_no1)
        VALUES
        ('$no', '$entry_date', '".$input['required_resources']."', '".$input['details']."', '".$input['required_urgent']."', '".$input['required_usual']."', '$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if($_GET["type"]=="getResourcelist") {
    	$sql = "SELECT * FROM gardan_resource ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
   }
} else if ($_GET["type"]=="AddStationaryData") {

    $input = json_decode(file_get_contents("php://input"), true);

    // Debug (optional)
    // print_r($input); exit;

    $stationary = $input['stationary'] ?? '';
    $no_required = $input['no_required'] ?? '';
    $company = $input['company'] ?? '';
    $details = $input['details'] ?? '';
    $emp_id = $_GET['emp_id'] ?? '';

    // Get max number
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM stationary_management";
    $result = $conn->query($sql);

    $i_no1 = 0;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $i_no1 = $row["i_no1"];
    }

    $no = "SID" . str_pad($no_required, 2, "0", STR_PAD_LEFT);

    // Insert query
 $sql = "INSERT INTO stationary_management 
(stationary_id, stationary, no_required, company, details, i_no1, checked_by, status)
VALUES 
('$no', '$stationary', '$no_required', '$company', '$details', '$i_no1', '$emp_id', 'Pending')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => $conn->error,
            "query" => $sql
        ]);
    }
}


else if ($_GET["type"]=="updateStationary") {
       $sql = "UPDATE stationary_management SET status='".$_GET["status"]."', checked_by='".$_GET["emp_id"]."' 
     WHERE id='".$_GET['id']."' "; 
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if($_GET["type"]=="getStationarylist") {
    	$sql = "SELECT * FROM stationary_management where status='pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
   }
}
else if($_GET["type"]=="getStationarylist1") {
    	$sql = "SELECT * FROM stationary_management where status='Approved' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
   }
}
else if ($_GET["type"] == "saveAgreementData") {
    
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM agreement_details";
    $i_no1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"];
            break;
        }
    }
    
    $i_no1++;
    $num_length = strlen((string)$i_no1);
    
    if($num_length == 1) {
        $agreement_id = "AID00".$i_no1;
    } else if($num_length == 2) {
        $agreement_id = "AID0".$i_no1;
    } else {
       $agreement_id = "AID".$i_no1; 
    }
    
    $file1 = "images/admin/".$_FILES['file_agreement']['name'];
    $file_loc1 = $_FILES['file_agreement']['tmp_name'];
    $final_file1=str_replace(' ','-',$file1);
  
    if((move_uploaded_file($file_loc1,$final_file1)))
    {
    
    $sql = "INSERT INTO agreement_details(agreement_id, entry_date, agreement_title, company_name, description, agreement_date, legal_form, file_agreement, i_no1)
    VALUES ('$agreement_id', '$entry_date', '".$_POST['agreement_title']."', '".$_POST['company_name']."', '".$_POST['description']."',  '".$_POST['agreement_date']."', '".$_POST['legal_form']."', '$final_file1', $i_no1)";
    if ($conn->query($sql) === TRUE) {
        
        $flag = 0;
        $steps = explode(",", $_POST["steps"]);
    	for($i = 0; $i < count($steps); $i++) {
    	    $sql = "INSERT INTO agreement_partner(agreement_id, partner_name) VALUES ('$agreement_id', '".$steps[$i]."')";
    	    if ($conn->query($sql) === TRUE) {
    	        $flag = 0;
    	    } else {
    	        $flag = 1;
    	    }
    	}
    	if ($flag == 0) {
            echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
    }  
} else if($_GET["type"]=="getAgreementDetails") {
    
        $sql = "SELECT * from agreement_details ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select partner_name, agreement_id from agreement_partner where agreement_id = '".$row['agreement_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                $row["steps"] = $data;
                $output[] = $row;
            }
        }
    echo json_encode($output);
} else if ($_GET["type"]=="AddHousekeepingData") {
    
        $sql = "SELECT IFNULL(MAX(i_no1), 0) as i_no1 FROM housekeeping_management";
        $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "MID0".$i_no1;
        } else if ($num == 2) {
            $no = "MID".$i_no1;
        }

        $sql = "INSERT INTO housekeeping_management( material, no_required, company, details,checked_by, i_no1) VALUES
        ( '".$_POST['material']."', '".$_POST['no_required']."', '".$_POST['company']."', '".$_POST['details']."',  '".$_GET['emp_id']."','$i_no1')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
} else if ($_GET["type"]=="updateMaterial") {
    $sql = "UPDATE housekeeping_management SET status='".$_GET["status"]."', checked_by='".$_GET["emp_id"]."' WHERE id=".$_GET["id"];
    if ($qa->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"]=="getMateriallist") {
    	$sql = "SELECT * FROM housekeeping_management ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
   }
} else if ($_GET["type"]=="AddContaractorBill") {
    
     $input = $_POST; 
     
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM contractor_bill";
      $result = $conn->query($sql);
        $no = "";
        $i_no1 = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no1 = $row["i_no1"];
            }
        }
        $i_no1++;
        $num = strlen($i_no1);
        if ($num == 1) {
            $no = "PID_0".$i_no1;
        } else if ($num == 2) {
            $no = "PID_".$i_no1;
        }
        
        
    	$target_dir = "../../upload/contractor/bills/";
            
       	if(isset($_FILES["file_bill1"]["name"])) {
            $file_tmp =$_FILES['file_bill1']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['file_bill1']['name'])));
            $file_name = $i_no1."file_bill1.".$file_ext;
            $file_bill1 = $file_name;
            move_uploaded_file($file_tmp,"$target_dir".$file_name);
        }
 
    $sql = "INSERT INTO contractor_bill( date,month, labours_no, file_bill1, gents, gents_per_wages, operators, operators_per_wages, womens,womens_per_wages, i_no1)
    VALUES ( '$date', '".$input['month']."', '".$input['labours_no']."',  '$file_bill1', '".$input['gents']."',  '".$input['gents_per_wages']."', '".$input['operators']."', 
    '".$input['operators_per_wages']."','".$input['womens']."','".$input['womens_per_wages']."', $i_no1)";
    
     if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    } 
    
} else if($_GET["type"]=="getContaractorBillData") {
    	$sql = "SELECT * FROM contractor_bill  ORDER BY id DESC";
   $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
} else if ($_GET["type"] == "saveGownsReceivedEntry") {
    
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM gowns_received";
    $i_no1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"]; 
            break;
        }
    }
    
    $i_no1++;
    $num_length = strlen((string)$i_no1);
    
    if($num_length == 1) {
       $received_id= "PID00".$i_no1;
    } else if($num_length == 2) {
       $received_id = "PID0".$i_no1;
    } else {
       $received_id = "PID".$i_no1; 
    }
    
    $sql = "INSERT INTO gowns_received(received_id, entry_date, po_number, vendor_name, quality_review, i_no1)
    VALUES ('$received_id', '$entry_date', '".$_POST['po_number']."', '".$_POST['vendor_name']."', '".$_POST['quality_review']."', $i_no1)";
    
    if ($conn->query($sql) === TRUE) {
        
        $flag = 0;
        $gowns = json_decode($_POST["gowns"], true);
        $length = sizeof($gowns);
    	
    	for($i = 0; $i < $length; $i++) {
    	    $data = $gowns[$i];
    	    $sql = "INSERT INTO gowns_data(received_id, size, qty_received) VALUES ('$received_id', '".$data["size"]."', '".$data["qty_received"]."')";
    	    if ($conn->query($sql) === TRUE) {
    	        $flag = 0;
    	    } else {
    	        $flag = 1;
    	    }
    	}
    	
    	if ($flag == 0) {
            echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
    
} else if($_GET["type"]=="getGownsReceivedEntry") {
    
        $sql = "SELECT * from gowns_received ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select received_id, size, qty_received from gowns_data where received_id = '".$row['received_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["Gowns"] = $data;
                $output[] = $row;
            }
        }
    
        echo json_encode($output);
} else if ($_GET["type"] == "saveEmployeeAgreement") {

    // entry_date was used in your old code, make sure it has a value
    $entry_date = $_POST['entry_date'] ?? ($_POST['agreement_date'] ?? date('Y-m-d'));

    $agreement_title = $_POST['agreement_title'] ?? '';
    $company_name    = $_POST['company_name'] ?? '';
    $employee_name   = $_POST['employee_name'] ?? '';
    $description     = $_POST['description'] ?? '';
    $agreement_date  = $_POST['agreement_date'] ?? '';
    $valid_date      = $_POST['valid_date'] ?? '';          // ✅ NEW
    $legal_form      = $_POST['legal_form'] ?? '';
    $agreement_type  = $_POST['agreement_type'] ?? '';      // ✅ NEW

    $sql = "INSERT INTO employee_agreement
            (entry_date, agreement_title, company_name, employee_name, description, agreement_date, valid_date, legal_form, agreement_type)
            VALUES
            ('$entry_date', '$agreement_title', '$company_name', '$employee_name', '$description', '$agreement_date', '$valid_date', '$legal_form', '$agreement_type')";

    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"error\",\"message\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"]=="saveEmployeeAgreementHr") {
     
    $sql = "INSERT INTO employee_agreement( entry_date, agreement_title, company_name, employee_name, description, agreement_date, legal_form,upToDate)
    VALUES ( '$entry_date', '".$_POST['agreement_title']."', '".$_POST['company_name']."',  '".$_POST['employee_name']."', '".$_POST['description']."', 
    '".$_POST['agreement_date']."', '".$_POST['legal_form']."','".$_POST['upToDate']."' )";
     if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
     
}
else if ($_GET["type"]=="saveglowns") {
    
 
 
    $sql = "INSERT INTO glowns( hire_date,stationary, no_required, company, company1, details )
    VALUES ( '$entry_date', '".$input['stationary']."', '".$input['no_required']."',  '".$input['company']."', '".$input['company1']."',  '".$input['details']."')";
     if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
     
}
else if($_GET["type"]=="getGlownslist") {
    	$sql = "SELECT * FROM glowns ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
   }
} 


else if($_GET["type"]=="getEmployeeAgreement") {
    	$sql = "SELECT * FROM employee_agreement ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    		echo json_encode($output);
    	}
    	else {
    		echo "[]";
    }
}    else if ($_GET["type"] == "getContractorlist_log_pdf") {
            
            $_GET['filename'] = ''; 
             
            $_GET['pdftype'] = 'onlyheader';
                
            include("./pdfimp2.php");
        $html= "";
        
        $html.='
         <table  cellpadding="3">
 
                    <tr>
                        <td style="  width: 540px; font-size: 13px;  font-weight: bold; text-align: center; ">Labour Contractor Log</td>
 
                    </tr>
        </table>
        <div></div>
        
 <table border="1" cellpadding="2">
  

                   <tr>
                        <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">Sr. No.</td>
                        <td style="width: 130px; font-size: 8; font-weight: bold; text-align: center; ">Contractor Firm</td>
                        <td style="width: 70px;font-size: 8; font-weight: bold; text-align: center; ">Authorised Person</td>
                        <td style="width: 140px; font-size: 8; font-weight: bold; text-align: center; ">Address</td>
                         <td style="width: 90px; font-size: 8; font-weight: bold; text-align: center; ">Email Id</td>
                        <td style="width: 60px; font-size: 8;  font-weight: bold;text-align: center; ">Status</td>

                   </tr> ';
                   
                   $i=1;
                     $sql = "SELECT * FROM labour_contractor  ORDER BY id DESC";
                     $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                             
                             $html.='
                             
                                <tr>
                                    <td style="width: 50px; font-size: 8; font-weight: bold; text-align: center; ">'.$i++.'</td>
                                    <td style="width: 130px; font-size: 8;    ">'.$row['contract_firm'].'  </td>
                                    <td style="width: 70px;font-size: 8;   text-align: center; ">'.$row['person'].'</td>
                                    <td style="width: 140px; font-size: 8;                      ">'.$row['address'].'</td>
                                     <td style="width: 90px; font-size: 8;   text-align: center; "> '.$row['email'].'</td>
                                    <td style="width: 60px; font-size: 8;   text-align: center; ">'.$row['status'].'</td>
            
                               </tr> ';
                            
                            
                        }
                    }
          
        
        $html.='</table> ';
          
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Labour Contractor LOG.pdf', 'I');
       
         } 
      
 }else {
    echo "[]";
}

// $conn->close();
// $qc->close();
// $store->close();
// $purchase->close();
// $security->close();
// $qa->close();
// $hr->close();
?>