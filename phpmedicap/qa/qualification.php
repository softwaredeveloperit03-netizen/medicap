<?php 

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require '../db.php';
require '../token.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql); 
$_GET["emp_id"] = "";
$_GET["department"] = "";
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
        $input = json_decode(file_get_contents('php://input'),true);

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
    
    if ($_GET["type"] == "saveProduct") {
        
        $sql = "INSERT INTO equipment_requirement (user_no, equipment_name,equipment_code,perform_qualifi,quali_report,installation_date, entry_by, entry_date) VALUES 
        ('".$_GET["user_no"]."','".$input["equipment_name"]."', '".$input["equipment_code"]."', '".$input["perform_qualifi"]."', '".$input["quali_report"]."',
        '".$input["installation_date"]."', '".$_GET["emp_id"]."', '$entry_date')";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } 
    
    // else if ($_GET["type"] == "saveUserRequirementSpecification") {
    //      $input    = $_POST;
    //                 $target_dir = "../../upload/deviation/";
    //                 $file_name = "";
    //                 // if(isset($_FILES["urs_file"]["name"])){
    //                 //     $target_file = $target_dir."initialFile".basename($_FILES["urs_file"]["name"]);
    //                 //     $file_name ="initialFile".basename($_FILES["urs_file"]["name"]);
    //                 //      move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_file);
    //                 // }
                     
    //     if (isset($_FILES["urs_file"]["name"]) && $_FILES["urs_file"]["error"] == 0) {
    //     // Create a safe file name
    //     $file_name = "initialFile" . basename($_FILES["urs_file"]["name"]);
    //     $target_file = $target_dir . $file_name;
    //     // Move the uploaded file to the target directory
    //     if (move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_file)) {
    //         echo "File uploaded successfully.";
    //     } else {
    //         echo "Sorry, there was an error uploading your file.";
    //         exit;
    //     }
    //     } else {
    //         echo "No file uploaded or an error occurred with the file upload.";
    //         exit;
    //     }    
    else if ($_GET["type"] == "saveUserRequirementSpecification") {
        if($_GET["plant_id"] == 77) {
    // $input = $_POST;
    // $target_dir = "../upload/deviation/";
    // $file_name = "";
    
    // // Check if file is uploaded and has no errors
    // if (isset($_FILES["urs_file"]["name"]) && $_FILES["urs_file"]["error"] == 0) {
    //     // Create a safe file name
    //     $file_name = "initialFile" . basename($_FILES["urs_file"]["name"]);
    //     $target_file = $target_dir . $file_name;
        
    //     // Move the uploaded file to the target directory
    //     if (move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_file)) {
    //         // File uploaded successfully
    //     } else {
    //         echo "Sorry, there was an error uploading your file.";
    //         exit;
    //     }
    // } else {
    //     echo "No file uploaded or an error occurred with the file upload.";
    //     exit;
    // }

  $input = $_POST;
        
        $target_dir = "../../../upload/qa/";

        $id = date("YmdHis", $timestamp);
      $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'urs-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}

    $entry_date = date('Y-m-d H:i:s');
    // Set the current date for entry_date
    $entry_date = date('Y-m-d H:i:s'); // Example: current date and time
     $sql = "INSERT INTO equipment_requirement 
         (department,vendor_no, capacity, expected_output, equipments_no, equipment_name, entry_by, urs_file, entry_date)
         VALUES ('".$_GET["department"]."','".$input["vendor_no"]."',
         '".$input["capacity"]."',
         '".$input["expected_output"]."',
         '".$input["equipments_no"]."',
         '".$input["equipment_name"]."',
         '".$_GET["emp_id"]."',
         '$file_name',
          '$entry_date' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } else  {
 
    $entry_date = date('Y-m-d H:i:s'); // Example: current date and time
     $sql = "INSERT INTO equipment_requirement 
         (department,vendor_no, capacity, expected_output, equipments_no, equipment_name, entry_by,specifications, entry_date)
         VALUES ('".$_GET["department"]."','".$input["vendor_no"]."',
         '".$input["capacity"]."',
         '".$input["expected_output"]."',
         '".$input["equipments_no"]."',
         '".$input["equipment_name"]."',
         '".$_GET["emp_id"]."',
         '" . json_encode($input["specs"]) . "',
          '$entry_date' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } }
    else if ($_GET["type"] == "getqualifiequip") {
        $output = array();
        $sql = "SELECT * FROM equipment_requirement where perform_qualifi LIKE '%YES%' order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPedingRequirements") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE  e.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateRequirement") {
        $sql = "UPDATE equipment_requirement SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRequirementsLog") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE e.status='approve' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingDQ") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no 
        WHERE e.user_no='".$_GET["user_no"]."' AND e.status='approve' AND e.dq_status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "saveDQ") {
        $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'dq-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s');
        $sql = "UPDATE equipment_requirement SET dq_file='$file_name', dq_status='inprocess',
        dq_by='".$_GET["emp_id"]."', dq_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "getInprocessDQ") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.status='approve' AND e.dq_status='inprocess'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateDQ") {
        
        $sql = "UPDATE equipment_requirement SET dq_status='".$_GET["status"]."', dq_approve_by='".$_GET["emp_id"]."',
        dq_approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } else if ($_GET["type"] == "getDQLog") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no 
        WHERE e.user_no='".$_GET["user_no"]."' AND e.status='approve' AND e.dq_status !='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getPendingFactory") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v 
        ON e.vendor_no=v.vendor_no WHERE e.user_no='".$_GET["user_no"]."' AND e.dq_status='approve' AND factory_status = 'Pending' ";
        //AND e.factory_status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
      else if ($_GET["type"] == "getApprvlFactory") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v 
        ON e.vendor_no=v.vendor_no WHERE e.factory_status='done' AND e.user_no='".$_GET["user_no"]."' 
        AND e.dq_status='approve' ";
        //AND e.factory_status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveFactoryApprvl") {
        $sql = "UPDATE equipment_requirement SET factory_status='Approved', factory_by='".$_GET["emp_id"]."', factory_date='$entry_date' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
     else if ($_GET["type"] == "updateIq") {
        $sql = "UPDATE equipment_requirement SET eiqr_status='Approved' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
      else if ($_GET["type"] == "updatePq") {
        $sql = "UPDATE equipment_requirement SET pq_status='Approved' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
      else if ($_GET["type"] == "updateReq") {
        $sql = "UPDATE equipment_requirement SET req_status='Approved' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
        else if ($_GET["type"] == "updateVe") {
        $sql = "UPDATE equipment_requirement SET ve_status='Approved' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
      else if ($_GET["type"] == "updateQr") {
        $sql = "UPDATE equipment_requirement SET qr_status='Approved' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
        else if ($_GET["type"] == "updateOq") {
        $sql = "UPDATE equipment_requirement SET oq_status='Approved' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
     else if ($_GET["type"] == "saveSiteApprvl") {
        $sql = "UPDATE equipment_requirement SET site_status='Approved' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveFactory") {
           $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'fa-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        $sql = "UPDATE equipment_requirement SET fa_file='$file_name', 
        factory_status='done', factory_by='".$_GET["emp_id"]."', factory_date='$entry_date' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getFactoryLog") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v 
        ON e.vendor_no=v.vendor_no WHERE e.user_no='".$_GET["user_no"]."' AND e.factory_status='Approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingSites") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no
        WHERE e.user_no='".$_GET["user_no"]."' AND e.factory_status='Approved' AND e.site_status='Pending'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getApprvSites") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no
        WHERE e.user_no='".$_GET["user_no"]."' AND e.factory_status='Approved' AND e.site_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "saveSite") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'site-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
        $sql = "UPDATE equipment_requirement SET  site_file='$file_name', site_status='done', site_by='".$_GET["emp_id"]."', site_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveIq") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'eiqr-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
        $sql = "UPDATE equipment_requirement SET  eiqr_file='$file_name', eiqr_status='done', eiqr_by='".$_GET["emp_id"]."', 
        eiqr_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     else if ($_GET["type"] == "saveOq") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'oq-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
        $sql = "UPDATE equipment_requirement SET  oq_file='$file_name', oq_status='done', oq_by='".$_GET["emp_id"]."', 
        oq_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
       else if ($_GET["type"] == "savePq") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'pq-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
        $sql = "UPDATE equipment_requirement SET  pq_file='$file_name', pq_status='done', pq_by='".$_GET["emp_id"]."', 
        pq_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
         else if ($_GET["type"] == "saveReq") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'req-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
   $sql = "UPDATE equipment_requirement SET  req_file='$file_name', req_status='done', req_by='".$_GET["emp_id"]."', 
        req_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
      else if ($_GET["type"] == "saveVe") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 've-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
   $sql = "UPDATE equipment_requirement SET  ve_file='$file_name', ve_status='done', ve_by='".$_GET["emp_id"]."', 
        ve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
            else if ($_GET["type"] == "saveQr") {
        
         $input = $_POST;
        $target_dir = "../../../upload/qa/";
        $id = date("YmdHis", $timestamp);
        $file_name = "";
    	if(isset($_FILES["urs_file"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['urs_file']['name'])));
        	$file_name = 'qr-'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["urs_file"]["tmp_name"], $target_dir.$file_name);
    	}
    $entry_date = date('Y-m-d H:i:s'); 
        
   $sql = "UPDATE equipment_requirement SET  qr_file='$file_name', qr_status='done', qr_by='".$_GET["emp_id"]."', 
        qr_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getSiteLog") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.site_status='Approved'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLogIq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.eiqr_status='Approved'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      else if ($_GET["type"] == "getLogVe") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.ve_status='Approved'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getLogPq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.pq_status='Approved'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      else if ($_GET["type"] == "getLogReq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.req_status='Approved'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getLogQr") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.qr_status='Approved'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getVe") {
        $output = array();
      $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.qr_status='Approved' AND e.ve_status='Pending'";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getReq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.pq_status='Approved' AND e.req_status='Pending'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getQr") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.req_status='Approved' AND e.qr_status='Pending'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getAprReq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.req_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getAprVe") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.ve_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      else if ($_GET["type"] == "getAprQr") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.qr_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
      else if ($_GET["type"] == "getLogOq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.oq_status='Approved' AND e.pq_status='Pending'"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
      else if ($_GET["type"] == "getLogOq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.oq_status='Approved' AND e.pq_status='Pending'"; 
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
        else if ($_GET["type"] == "getOq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.eiqr_status='Approved' AND e.oq_status='Pending'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

     else if ($_GET["type"] == "getRequest") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.site_status='Approved' AND e.eiqr_status='Pending'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getPendingIq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.eiqr_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getPendingPq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.pq_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "getPendingOq") {
        $output = array();
        $sql = "SELECT e.*, v.vendor_name FROM equipment_requirement e LEFT JOIN vendor v ON e.vendor_no=v.vendor_no WHERE
        e.user_no='".$_GET["user_no"]."' AND e.oq_status='done'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["specifications"] = json_decode($row["specifications"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveQualificationRequest") {
        $sql = "INSERT INTO qualificationreq ( plant_id,equipment_code, capacity, make, equip_name, section_name, department_name,entry_by,entry_date)
        VALUES ('".$_GET["plant_id"]."', '".$input["equipment_code"]."', '".$input["capacity"]."','".$input["make"]."','".$input["equip_name"]."',
        '".$input["section_name"]."', '".$input["department_name"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    //  else if ($_GET["type"] == "savePq") {
    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,qa_procedure,acceptance, scope, objective,department,List,entry_date,entry_by)
    //     VALUES ('".$_GET["plant_id"]."', '".$input["qa_procedure"]."', '".$input["acceptance"]."','".$input["scope"]."','".$input["objective"]."','" . json_encode($input["department"]) . "','" . json_encode($input["List"]) . "','".$_GET["emp_id"]."','$entry_date'
    //     )";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    else if ($_GET["type"] == "getpqchecklist") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='pending' and plant_id =  '".$_GET["plant_id"]."' ";
	    
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
	
	
	else if ($_GET["type"] == "getoqchecklist") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='update'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }    
	    echo json_encode($output);
	}  
	
		
	else if ($_GET["type"] == "getRequest1") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='final'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["department"] = json_decode($row["department"]);
	               $row["deviationList"] = json_decode($row["deviationList"]);
	                  $row["List"] = json_decode($row["List"]);
	                    $row["utilityList"] = json_decode($row["utilityList"]);
	                      $row["trialList"] = json_decode($row["trialList"]);
	                        $row["reportList"] = json_decode($row["reportList"]);
	                          $row["reporptList"] = json_decode($row["reporptList"]);
	                            $row["procedureList"] = json_decode($row["procedureList"]);
	                              $row["opertorList"] = json_decode($row["opertorList"]);
	                                $row["metList"] = json_decode($row["metList"]);
	                                  $row["materialList"] = json_decode($row["materialList"]);
	                                    $row["freqList"] = json_decode($row["freqList"]);
	                                    
	            $output[] = $row;
	        }
	    }    
	    echo json_encode($output);
	}  
	else if ($_GET["type"] == "getdeviation") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='report'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }    
	    echo json_encode($output);
	}  
		else if ($_GET["type"] == "getPerformanceReport") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='procedure'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }    
	    echo json_encode($output);
	}  
		else if ($_GET["type"] == "getProdure") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='material'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }  
	    echo json_encode($output);
	}  
		else if ($_GET["type"] == "getutility") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='step'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }  
	    echo json_encode($output);
	} 
		else if ($_GET["type"] == "getMaterialpq") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='variable'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }  
	    echo json_encode($output);
	} 
	else if ($_GET["type"] == "getVariable") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='steped'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
		else if ($_GET["type"] == "getperformance") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='updated'";
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
    //      else if ($_GET["type"] == "saveOq") {
    //     $sql = "INSERT INTO qualificationreq ( plant_id,purpose_oq,equip-discription_oq, qa_procedure_oq, acceptance_oq,scope_oq,objective_oq,department,List)
    //     VALUES ('".$_GET["plant_id"]."', '".$input["purpose_oq"]."', '".$input["equip-discription_oq"]."', '".$input["qa_procedure_oq"]."', '".$input["acceptance_oq"]."','".$input["scope_oq"]."','".$input["objective_oq"]."','" . json_encode($input["department"]) . "','" . json_encode($input["List"]) . "'
    //     )";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
     
     else if ($_GET["type"] == "saveOperation") {
       $sql = "UPDATE qualificationreq SET status='final', plant_id='".$_GET["plant_id"]."',reportList='" . json_encode($input["reportList"]) . "'  WHERE id='".$_GET["id"]."'";
    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,reportList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["reportList"]) . "'
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "savechecklist") {
           echo  $sql = "UPDATE qualificationreq SET status='update', trialList='" . json_encode($input["trialList"]) . "',opertorList='" . json_encode($input["opertorList"]) . "'  WHERE id='".$_GET["id"]."'";
    //  echo   $sql = "UPDATE INTO qualificationreq (status, plant_id, trialList, opertorList)
    //     VALUES ('update','".$_GET["plant_id"]."','" . json_encode($input["trialList"]) . "','" . json_encode($input["opertorList"]) . "' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "getpqfinalreport") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='pending' and plant_id =  '".$_GET["plant_id"]."' ";
	    
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
    
    
     else if ($_GET["type"] == "savePer") {
              echo  $sql = "UPDATE qualificationreq SET status='final', plant_id='".$_GET["plant_id"]."',freqList='" . json_encode($input["freqList"]) . "'  WHERE id='".$_GET["id"]."'";

    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,freqList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["freqList"]) . "' 
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "saveUtility") {
    echo  $sql = "UPDATE qualificationreq SET status='final' , plant_id='".$_GET["plant_id"]."',utilityList='" . json_encode($input["utilityList"]) . "'  WHERE id='".$_GET["id"]."'";
     //  $sql = "INSERT INTO qualificationreq ( plant_id,utilityList)
     // VALUES ('".$_GET["plant_id"]."','" . json_encode($input["utilityList"]) . "'
     // )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
      else if ($_GET["type"] == "saveVariable") {
              echo  $sql = "UPDATE qualificationreq SET status='final' , plant_id='".$_GET["plant_id"]."',metList='" . json_encode($input["metList"]) . "'  WHERE id='".$_GET["id"]."'";

    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,metList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["metList"]) . "'    variable
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }   
    
    
      else if ($_GET["type"] == "saveDoc") {
        echo  $sql = "UPDATE qualificationreq SET status='final' , plant_id='".$_GET["plant_id"]."',materialList='" . json_encode($input["materialList"]) . "'  WHERE id='".$_GET["id"]."'";
    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,materialList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["materialList"]) . "'
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
       else if ($_GET["type"] == "saveProcedure") {
     echo  $sql = "UPDATE qualificationreq SET status='final' , plant_id='".$_GET["plant_id"]."',procedureList='" . json_encode($input["procedureList"]) . "'  WHERE id='".$_GET["id"]."'";
    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,procedureList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["procedureList"]) . "'
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
    }
    else if ($_GET["type"] == "saveReport") {
     echo  $sql = "UPDATE qualificationreq SET status='final' , plant_id='".$_GET["plant_id"]."',reporptList='" . json_encode($input["reporptList"]) . "'  WHERE id='".$_GET["id"]."'";
    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,reporptList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["reporptList"]) . "'
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "saveDeviation") {
     echo  $sql = "UPDATE qualificationreq SET status='final' , plant_id='".$_GET["plant_id"]."',deviationList='" . json_encode($input["deviationList"]) . "'  WHERE id='".$_GET["id"]."'";
    //  echo   $sql = "INSERT INTO qualificationreq ( plant_id,deviationList)
    //     VALUES ('".$_GET["plant_id"]."','" . json_encode($input["deviationList"]) . "'
    //     )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getpqform") {
	    $output = array();
	    $sql = "SELECT * FROM qualificationreq WHERE status='pending' and plant_id =  '".$_GET["plant_id"]."' ";
	    
 	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} 
	
    
    else if ($_GET["type"] == "save_report") {
        
        
        
        $equipCode = $_GET["equipment_code"];
        
        $randomNumber = rand(1000, 9990); 
        
        $newCombination = $equipCode . $randomNumber;
         
                  
        if(isset($_FILES["report"])) {
            $file_tmp =$_FILES['report']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['report']['name'])));
            $file_name = $newCombination."report.".$file_ext;
            $report = $file_name;
           // move_uploaded_file($file_tmp,"../../../upload/qualification_report/".$file_name);
        }
        
         $sql = "INSERT INTO qualification_report ( plant_id, equipment_code, equipment_name, report, date)
        VALUES ('".$_GET["plant_id"]."', '".$_GET["equipment_code"]."','".$_GET["equipment_name"]."','$report','$entry_date')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            move_uploaded_file($file_tmp,"../../../upload/qualification_report/".$file_name);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET["type"] == "getQualificationRequest") {
        $output = Array();
        $sql = "SELECT * FROM qualificationreq WHERE plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "getRequest") {
        $output = Array();
   //   echo  $sql = "SELECT * FROM qualificationreq WHERE status='Pending' AND plant_id='".$_GET["plant_id"]."'";
//    AND id= '".$_GET["id"]."' AND plant_id='".$_GET["plant_id"]."'
    $sql = "SELECT * FROM qualificationreq  WHERE status='set'";
echo $sql;

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getIQLog") {
        $output = array();
        $sql = "SELECT * FROM equipment_iq WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    // else if ($_GET["type"] == "saveIq") {

        
    //       $sql = "INSERT INTO equipment_iq(plant_id, safety, plc, electric_panel, size, capacity, make,equipment_name, location, area, dept, electricity, 
    //      configuration, steam_require,water_require, steampressure, dm_water, distrilled_water, ro_water, tap_water, installList,machineList, blankList, 
    //      entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','".$input["safety"]."','".$input["plc"]."','".$input["electric_panel"]."',
    //      '".$input["size"]."','".$input["capacity"]."','".$input["capacity"]."','".$input["make"]."',
    //     '".$input["equipment_name"]."','".$input["location"]."','".$input["area"]."','".$input["dept"]."','".$input["electricity"]."',
    //     '".$input["configuration"]."','".$input["steam_require"]."','".$input["water_require"]."','".$input["steampressure"]."',
    //     '".$input["dm_water"]."','".$input["distrilled_water"]."','".$input["ro_water"]."','".$input["tap_water"]."',
    //     '".json_encode($input["installList"])."','".json_encode($input["machineList"])."','".json_encode($input["blankList"])."','".$_GET["emp_id"]."','$entry_date')";
        
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
        
    // }

    
    
    
    
    
    else if ($_GET["type"] == "getEquipmentBydept") {
        $output = array();
        $sql = "SELECT * FROM equipment WHERE department='".$_GET["deptName"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getemployeeByDept") {
        $output = array();
        $sql = "SELECT id,plant_id,employee_type,emp_level,emp_id,firstname,middlename,lastname,middlename,department,operator_category,qualification,gender,joining_date,isinduction FROM employee WHERE status = 'active' AND department='".$_GET["deptName"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }


}else {
    echo "Invalid Token";
}

$conn->close();
?>