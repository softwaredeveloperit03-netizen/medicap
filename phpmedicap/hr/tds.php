<?php


// ini_set('display_errors', 1);
// error_reporting(E_ALL);



    require '../db.php';
    require '../token.php';
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
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getinsert_invest") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $output = array();
        //  $sql = "select * from tds_investment where emp_id='".$_GET["emp_id1"]."'";
         $sql = "select * from tds_investment where emp_id='".$_GET["emp_id1"]."' and header='80c_80ccc_80ccd'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
  
   else if ($_GET["type"] == "getinsert_invest80CCD_1b") {
        $output = array();
        //  $sql = "select * from tds_investment where emp_id='".$_GET["emp_id1"]."'";
         $sql = "select * from tds_investment where emp_id='".$_GET["emp_id1"]."' and header='80CCD_1b'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
   else if ($_GET["type"] == "getinsert_invest80E_80D_80DD8_80U_80DD") {
        $output = array();
        //  $sql = "select * from tds_investment where emp_id='".$_GET["emp_id1"]."'";
         $sql = "select * from tds_investment where emp_id='".$_GET["emp_id1"]."' and header='80E_80D_80DD8_80U_80DD'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
   else if ($_GET["type"] == "getotherIncome") {
        $output = array();
         $sql = "select * from tds_otherIncome where emp_id='".$_GET["emp_id1"]."' order by id desc limit 2";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
   else if ($_GET["type"] == "get_tds_investment_ex") {
        $output = array();
         $sql = "select * from tds_investment_ex ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
   else if ($_GET["type"] == "get_save_tds") {
        $output = array();
         $sql = "select * from employee_tds where emp_id='".$_GET["emp_id1"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
   else if ($_GET["type"] == "print_emp_tds") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
     
          
       
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
         }  
    else if ($_GET["type"] == "insert_invest") {
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
        $sql = "INSERT INTO `tds_investment`( emp_id,`plant_id`, `particular`, `amount`, `entry_date`,header) VALUES ( '".$_GET["emp_id1"]."','".$_GET["plant_id"]."','".$input["particular"]."','".$input["amount"]."','$entry_date','".$input["header"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "insert_tds_investment_ex") {
        $sql = "INSERT INTO `tds_investment_ex`( `plant_id`, `particular`, `amount`, `entry_date`,head_tit) VALUES
        ( '".$_GET["plant_id"]."','".$input["particular"]."','".$input["amount"]."','$entry_date','".$input["head_tit"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "insert_otherIncome") {
               $json_obj = json_encode($input);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql = "INSERT INTO `tds_otherIncome`( emp_id,`plant_id`, `particular`, `amount`, `entry_date`) VALUES ( '".$_GET["emp_id1"]."','".$_GET["plant_id"]."',
  '".$values["param"]."','".$values["value"]."','$entry_date')";
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
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        // //////////////////////////////////////
        // $sql = "INSERT INTO `tds_otherIncome`( emp_id,`plant_id`, `particular`, `amount`, `entry_date`) VALUES ( '".$_GET["emp_id1"]."','".$_GET["plant_id"]."','".$input["particular"]."','".$input["amount"]."','$entry_date')";
        // if ($conn->query($sql)) {
        //     echo "{\"status\":\"success\"}";
        // } else {
        //     echo "{\"status\":\"".$conn->error."\"}";
        // }
    }
     else if ($_GET["type"] == "savePaymentDetails") {
               $input = $_POST;
            if(isset($_FILES["paymentFile"]["name"])) {
            $file_ext=strtolower(end(explode('.',$_FILES['paymentFile']['name'])));
            }

    	 $sql = "INSERT INTO tds_payment_details(employer_pan_no,quarter, amount, cess, total_payment,payment_mode,transaction_no,cheque_no,
    	 ack_no,upload_receipt,tds_statement_id,plant_id,entry_date,entry_by)
    	 VALUES ('".$input["employer_pan_no"]."','".$input["quarter"]."','".$input["amount"]."','".$input["cess"]."','".$input["total_payment"]."',
    	 '".$input["payment_mode"]."','".$input["transaction_no"]."','".$input["cheque_no"]."','".$input["ack_no"]."','".$input["recipt"]."',
    	 '".$_GET["ID"]."','".$_GET["plant_id"]."','$entry_date','".$_GET["emp_id"]."')";
        if ($conn->query($sql)) {
              
                move_uploaded_file($_FILES["paymentFile"]["tmp_name"], "../../../upload/paymentReceipt/".$file_ext);
            echo "{\"status\":\"success\"}";
               

    	}
            
    
    }
    else if ($_GET["type"] == "getTdsPaymentDetails") {
        $output = array();
         $sql = "select * from tds_payment_details";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  
    
    
    
    
    else if ($_GET["type"] == "save_tds") {
        
       $sql = "INSERT INTO `employee_tds`(
            regime_type,
            emp_id,
            plant_id,
            monthly_new,
            monthly_old,
            annual_new,
            annual_old,
            invest_80cc,
            invest_80D,
            HRA,
            standard_deduction,
            total_invest_new,
            total_invest_old,
            total_income_old,
            total_income_new,
            income_tax_new,
            income_tax_old,
            HAECess_new,
            HAECess_old,
            surcharge,
            balence_tax_deduction,
            total_tax_old,
            total_tax_new,
            monthly_tds_new,
            monthly_tds_old,
            CTC,
            paidAmount
        ) VALUES (
            '".$input["regime"]."',
            '".$_GET["emp_id1"]."',
            '".$_GET["plant_id"]."',
            '".$input["new_regime_month"]."',
            '".$input["old_regime_month"]."',
            '".$input["new_regime_annual"]."',
            '".$input["old_regime_annual"]."',
            '".$input["suminvestmets80CCD_1b"]."',
            '".$input["suminvestmets80E_80D_80DD8_80U_80DD"]."',
            '".$input["exempted_value"]."',
            '".$input["standatd_deduction"]."',
            '".$input["sum_investmentsnew"]."',
            '".$input["sum_investmentsold"]."',
            '".$input["total_earn_old"]."',
            '".$input["total_earn_new"]."',
            '".$input["tdsnew"]."',
            '".$input["tdsold"]."',
            '".$input["med_cess_new"]."',
            '".$input["med_cess_old"]."',
            '".$input["surcharge"]."',
            '".$input["balence_tax_deduction"]."',
            '".$input["total_tax_old"]."',
            '".$input["total_tax_new"]."',
            '".$input["new_tds_month"]."',
            '".$input["old_tds_annual"]."',
            '".$input["ctc_annual"]."',
            '".$input["paidAmount"]."'
        )";

        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>