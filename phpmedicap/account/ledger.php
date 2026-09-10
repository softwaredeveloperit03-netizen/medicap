<?php



// error_reporting(E_ALL);
// ini_set('display_errors', 1);


require '../db.php';
require '../token.php';
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
    
    if($_GET["type"]=="getClients") {
        $output = array();
    	$sql = "SELECT * FROM ledgers ";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) { 
    		    $output[] = $row;
    		    
    		}
    	}	
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getClientDetails") {
        $sql = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND client_code='".$_GET["client_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = Array();
                $sql1 = "SELECT * FROM ledger WHERE user_no='".$_GET["user_no"]."' AND client_code='".$_GET["client_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $debit_amount = 0;
                        $sql2 = "SELECT IFNULL(SUM(amount), 0) as amount FROM ledger WHERE user_no='".$_GET["user_no"]."' AND id <= ".$row1["id"] . " AND entry_for='debit'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $debit_amount = +$row2["amount"];
                                break;
                            }
                        }
                        
                        $credit_amount = 0;
                        $sql2 = "SELECT IFNULL(SUM(amount), 0) as amount FROM ledger WHERE user_no='".$_GET["user_no"]."' AND id <= ".$row1["id"] . " AND entry_for='credit'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $credit_amount = +$row2["amount"];
                                break;
                            }
                        }
                        
                        if ($debit_amount < $credit_amount) {
                            $row1["cr_dr"] = "CR";
                            $row1["balance"] = $credit_amount - $debit_amount;
                        } else if ($credit_amount < $debit_amount) {
                            $row1["cr_dr"] = "DR";
                            $row1["balance"] = -($debit_amount - $credit_amount);
                        } else if ($credit_amount == $debit_amount) {
                            $row1["cr_dr"] = "NIL";
                            $row1["balance"] = 0.00;
                        }
                        
                        $output[] = $row1;
                    }
                }
                $row["details"] = $output;
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } 
    else if ($_GET["type"] == "getClient_ledgers") {   
        
      
                $output = Array();
                 $sql1 = "SELECT TrdNm,client_code FROM client";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output1 = Array();
                          $sql2 = "SELECT  * FROM voucherentry WHERE  client_code  = '".$row1["client_code"]."' ";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                
                                
                                if($row2['credit_type'] == 'Dr'){
                                    $row2['amount'] =  $row2['debit']; 
                                }else{
                                    $row2['amount'] =  $row2['credit']; 
                                }
                                
                                
                                
                                
                                
                                $output1[] = $row2;
                             }
                        }
                        
 
                     
                        
                         
                        $row1['data']  = $output1;
                        $output[] = $row1;
                
                    }
                }

             echo json_encode($output);

    
} 
    else if ($_GET["type"] == "saveLedgerEntry") {
        $sql = "INSERT INTO ledger (user_no, client_code, particular, entry_for, amount, pay_mode, entry_by, 
        entry_date) VALUES ('".$_GET["user_no"]."','".$input["client_code"]."', '".$input["particular"]."', '".$input["entry_for"]."', '".$input["amount"]."', '".$input["pay_mode"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getCredits") {
        $output = Array();
        $sql = "SELECT * FROM ledger WHERE user_no='".$_GET["user_no"]."' AND entry_for='credit'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['entry_date'] = date('d-m-Y', strtotime($row['entry_date']));
                $sql1 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row["client_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["client_name"] = $row1["company"];
                        break;
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } else if ($_GET["type"] == "getAllLedgers") {
        $output = Array();
        $sql = "SELECT * FROM client";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['entry_date'] = date('d-m-Y', strtotime($row['entry_date']));
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else  if($_GET["type"]=="saveClient") {
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
        
        
   $sql2 = "INSERT INTO client (user_no,refered_by, agent_no,division, percentage,cl_type, client_subtype, 
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
  
     $conn->query($sql2);
  
  
  
  
  
 
            
            
            
            
            
            
            
            
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET['type'] == 'getjournallist'){
        $output = Array();
        $sql = "SELECT * FROM ledger WHERE user_no='".$_GET["user_no"]."'  AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['entry_date'] = date('d-m-Y', strtotime($row['entry_date']));
                $sql1 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row["client_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if($row['entry_for'] == 'credit'){
                            $row['entry_for'] = 'Cr';
                        }else{
                            $row['entry_for'] = 'Dr';
                        }
                        $row["client_name"] = $row1["company"];
                        break;
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"]=="getLedgersLog") {
        $output = array();
    	  $sql = "SELECT c.*, a.agent_name, s.state_name FROM ledgers c LEFT JOIN agent a ON c.agent_no=a.agent_no 
    	LEFT JOIN state s ON c.state_code=s.state_code WHERE c.user_no='".$_GET["user_no"]."'"; //ORDER BY c.company";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["branch"] = json_decode($row["branch"]);
    		   $row["divisions"] = json_decode($row["divisions"]);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    
    
    else if ($_GET['type'] == 'downloadLedger'){
        $sql = "SELECT * FROM ledger WHERE user_no='".$_GET["user_no"]."' AND client_code='".$_GET["client_code"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row["client_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if($row['entry_for'] == 'credit'){
                            $row['entry_for'] = 'Cr';
                        }else{
                            $row['entry_for'] = 'Dr';
                        }
                        $row["client_name"] = $row1["company"];
                        break;
                    }
                }
                require '../PHPExcel/Classes/PHPExcel.php';
                $objPHPExcel = new PHPExcel();
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'GMP Software Pvt Ltd');
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', $row["client_name"]);
                $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:C1');
                $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:C2');
                
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Date');
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Credit / Debit');
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Amount');
                $objPHPExcel->getActiveSheet()->getStyle('A3:C3')->getFill()->applyFromArray(array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array(
                         'rgb' => 'A2C2FF'
                    )
                ));
                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $i = 4;
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, date('d-m-Y', strtotime($row['entry_date'])));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $row["entry_for"]);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, $row["amount"]);
                $i++;
                $objPHPExcel->getActiveSheet()->setAutoFilter('A3:C3');
                $autoFilter = $objPHPExcel->getActiveSheet('A3:C3')->getAutoFilter();
        		$objPHPExcel->setActiveSheetIndex(0);
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                $objWriter->save(__DIR__."/ledger.xls");
                
                header('Content-Disposition: attachment; filename="'.basename('ledger.xls').'"');
                header('Content-Length: ' . filesize('ledger.xls'));
                readfile('ledger.xls');
                unlink('ledger.xls');
            }
        }
    }
    else if ($_GET['type'] == 'downloadJournal'){
        require '../PHPExcel/Classes/PHPExcel.php';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'GMP Software Pvt Ltd');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:D1');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Pune');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Date');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Client / Vendor Name');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Credit / Debit');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Amount');
        $objPHPExcel->getActiveSheet()->getStyle('A3:D3')->getFill()->applyFromArray(array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => array(
                 'rgb' => 'A2C2FF'
            )
        ));
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $i = 4;
        $sql = "SELECT * FROM ledger WHERE user_no='".$_GET["user_no"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND  '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND client_code='".$row["client_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if($row['entry_for'] == 'credit'){
                            $row['entry_for'] = 'Cr';
                        }else{
                            $row['entry_for'] = 'Dr';
                        }
                        $row["client_name"] = $row1["company"];
                        break;
                    }
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, date('d-m-Y', strtotime($row['entry_date'])));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $row["client_name"]);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, $row["entry_for"]);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, $row["amount"]);
                $i++;
            }
        }
        $objPHPExcel->getActiveSheet()->setAutoFilter('A3:D3');
        $autoFilter = $objPHPExcel->getActiveSheet('A3:D3')->getAutoFilter();
		$objPHPExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(__DIR__."/journal.xls");
        
        header('Content-Disposition: attachment; filename="'.basename('journal.xls').'"');
        header('Content-Length: ' . filesize('journal.xls'));
        readfile('journal.xls');
        unlink('journal.xls');
    }
}
else{
    echo 'Invalid Token';
}
$conn->close();
?>