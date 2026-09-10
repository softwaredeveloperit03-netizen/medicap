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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    // if ($_GET["type"] == "getTechnicalInfoLog") {
    //     $output = array();
    //     $sql = "SELECT t.*, p.dosage_form, p.product_name, p.grade, b.batch_no, b.batch_size, b.bmr_no as std_bmr_no FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code LEFT JOIN bmr b ON t.bmr_no=b.id WHERE p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND DATE(t.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    //     echo $sql;
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
                
    //             $output1 = array();
    //             $sql1 = "SELECT * FROM spec_tests WHERE specification_no= (SELECT specification_no FROM specification WHERE spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row["stage"]."')";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row["tests"] = $output1;
                
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
     if ($_GET["type"] == "getTechnicalInfoLog") {
        $output = array();
        // $sql = "SELECT t.*, p.product_name, p.grade,p.dosage_form FROM technical_info t 
        // LEFT JOIN product p ON t.product_code=p.product_code 
        // WHERE t.status='done'";
        $sql = "SELECT b1. *,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no,s.sample_qty FROM batch_stages_ipqc_dtl b1 left join stages_ipqc_dtl b2 ON b1.stage_hdr_id=b2.id LEFT JOIN spec_tests s ON b2.stage_name=s.stage";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveTISheet") {
        $sql = "INSERT INTO technical_info (product_code, batch_no,  batch_size,ar_no, stage, step, sample_id, equipment_code, sample_qty,unit,entry_by, entry_date)
        VALUES ('".$input["product_code"]."', '".$input["batch_no"]."','".$input["batch_size"]."', '$ar_no', '".$input["stage"]."', '".$input["step"]."', '".$input["sample_id"]."', '".$input["equipment_code"]."', '".$input["sample_qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
        if ($conn->query($sql)) {
             echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if($_GET["type"] == "getPendingRequests"){
        $output = array();
        $sql="SELECT t.*,p.product_name FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='pending'";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if ($_GET["type"] == "acceptRequest") {
        $ar_no = "IPQC/";
        $prod_code = '';
        $id1 = '';
        
        $sql = "SELECT IFNULL(COUNT(id), 0) as id FROM technical_info WHERE ar_no !=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = +$row["id"];
            }
        }
        $id++;
        if ($input["product_code"] == "FM026") {
            $prod_code = 'CB';
            $id++;
        } else if ($input["product_code"] == "FM096") {
            $prod_code = 'THB';
        } else if ($input["product_code"] == "FM030") {
            $prod_code = 'CS';
        } else {
            $prod_code = $input["product_code"];
        }
        if (strlen($id) == 1) {
            $id1= "0000";
        } else if (strlen($id) == 2) {
            $id1= "000";
        } else if (strlen($id) == 3) {
            $id1= "00";
        } else if (strlen($id) == 4) {
            $id1= "0";
        } else if (strlen($id) == 5) {
            $id1= "";
        }
        $ar_no.= $prod_code.'/'.date("y", $timestamp).'/'.$id1.''.$id;
        
        $sql = "UPDATE technical_info SET ar_no='$ar_no',status='".$_GET["status"]."', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"IT updated successfully!"));
        } else {
            echo json_encode(array("status"=>"success","msg"=>$conn->error));
        }
    }
    // else if($_GET["type"] == "getPendingTechnicals"){
    //     $output = array();
    //     $sql="SELECT t.*,p.product_name FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='ACCEPT'";
    //      $result = $conn->query($sql);
    //         if ($result->num_rows > 0) {
    //             while ($row = $result->fetch_assoc()) {
    //                 $sql1="SELECT * FROM spec_tests WHERE stage='".$row["stage"]."'";
    //                   $result1 = $conn->query($sql1);
    //                     if ($result1->num_rows > 0) {
    //                         while ($row1 = $result1->fetch_assoc()) {
    //                             $row1["conditions"] = json_decode($row1["conditions"]); 
    //                             $row1["reagents"] = json_decode($row1["reagents"]); 
    //                             $row1["solutions"] = json_decode($row1["solutions"]);
    //                             $row1["instruments"] = json_decode($row1["instruments"]);
    //                             $output1[] = $row1;
    //                         }
    //                     }
    //                 $row['tests'] = $output1;
    //                 $output[] = $row;
    //             }
    //         }
    //     echo json_encode($output);
    // }
    
     else if ($_GET["type"] == "getPendingTechnicals") {
        $output = array();
        $sql = "SELECT t.*, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='ACCEPT' ORDER BY t.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT s.stage, s.test, s.subtest, s.limits, s.limit_type, s.lower_limit, s.upper_limit, s.lessthan, s.morethan, s.procedures, s.instruments, s.calculation, f.image, f.parameters FROM spec_tests s LEFT JOIN formulas f ON s.calculation=f.formula_name WHERE s.specification_no IN (SELECT specification_no FROM specification WHERE spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."') AND s.stage='".$row["stage"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["parameters"] = json_decode($row1["parameters"]);
                        $row1["formula"] = 'https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/formula/'.$row1["image"];
                        if ($row1["instruments"] !== '') {
                            $instruments = json_decode($row1["instruments"]);
                            for ($i = 0; $i < count($instruments); $i++) {
                                $instrument = $instruments[$i];
                                $output2 = array();
                                $sql2 = "SELECT * FROM equipment WHERE department='Quality Control' AND equipment_name LIKE '%".$instrument->equipment_name."%'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }
                                $instrument->equipments = $output2;
                                $instruments[$i] = $instrument;
                            }
                            $row1["instruments"] = $instruments;
                        }
                        $output1[] = $row1;
                    }
                    $row["isspecification"] = "yes";
                } else {
                    $row["isspecification"] = "no";
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTestingReport") {
        
        if(isset($_FILES["testattachment"]["name"])) {
        	$target_file = $target_dir."".$id.basename($_FILES["testattachment"]["name"]);
        	$userphoto = $id.basename($_FILES["testattachment"]["name"]);
        	$file3 = basename($_FILES["testattachment"]["name"]);
        	move_uploaded_file($_FILES["testattachment"]["tmp_name"], $target_file);
    	}
    	
        $sql = "UPDATE technical_info SET tests='".$_POST["tests"]."', remark='".$_POST["remark"]."', analysis_by='".$_GET["emp_id"]."', analysis_date='$entry_date', status='inprocess' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getInprocessTechnicals") {
        $output = array();
        $sql = "SELECT t.*, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='inprocess' ORDER BY t.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkTestingReport") {
        $sql = "UPDATE technical_info SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getPendingTechnicalInfos") {
        $output = array();
        $sql = "SELECT t.*, p.dosage_form, p.product_name, p.grade, b.batch_no, b.batch_size, b.bmr_no as std_bmr_no FROM technical_info t 
        LEFT JOIN product p ON t.product_code=p.product_code LEFT JOIN bmr b ON t.bmr_no=b.id WHERE t.status='pending' AND p.dosage_form 
        LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND DATE(t.entry_date) 
        BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT sample_qty, unit FROM specification WHERE spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row["stage"]."')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["sample_qty"] = $row1["sampe_qty"];
                        $row["unit"] = $row1["unit"];
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "withdrawSample") {
        $sql = "insert into technical_info SET sample_qty='".$_GET["sample_qty"]."', unit='".$_GET["unit"]."', status='withdraw', withdraw_by='".$_GET["emp_id"]."', withdraw_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "completeFGSampling") {
        $sql = "UPDATE batch_stages_ipqc_dtl SET sampleQty='".$input["sampleQty"]."' ,unit='".$input["unit"]."' , samplingStatus = 'Complete', test_result_status = 'For_Allocation',
        withdraw_by='".$_GET["emp_id"]."', withdraw_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
         $sql1 = "UPDATE technical_info SET sample_qty='".$input["sampleQty"]."' ,unit='".$input["unit"]."' , status = 'For_Allocation'
          WHERE ti_no='".$_GET["ti_no"]."'";
          $conn->query($sql1);
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "completeFGonlySampling") {
      $sql = "UPDATE batch_stages_ipqc_dtl 
        SET sampleQty='" . $input["sampleQty"] . "', 
            unit='" . $input["unit"] . "', 
            fg_withdraw_by='" . $_GET["emp_id"] . "', 
            fg_withdraw_date='$entry_date', 
            RequiredQty='" . $input['RequiredQty'] . "', 
            Requiredunit='" . $input['Requiredunit'] . "', 
            Stability_Qty='" . $input['Stability_Qty'] . "', 
            Stability_unit='" . $input['Stability_unit'] . "', 
            control_sample_Qty='" . $input['control_sample_Qty'] . "', 
            control_sample_unit='" . $input['control_sample_unit'] . "', 
            Marketing_sample_Qty='" . $input['Marketing_sample_Qty'] . "', 
            Marketing_sample_unit='" . $input['Marketing_sample_unit'] . "' 
        WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
         $sql1 = "UPDATE technical_info SET sample_qty='".$input["sampleQty"]."' ,unit='".$input["unit"]."' , status = 'For_Allocation'
          WHERE ti_no='".$_GET["ti_no"]."'";
          $conn->query($sql1);
          
          $sql2 = "UPDATE stages_ipqc_dtl 
        SET sampleQty='" . $input["sampleQty"] . "', 
            unit='" . $input["unit"] . "', 
            fg_withdraw_by='" . $_GET["emp_id"] . "', 
            fg_withdraw_date='$entry_date', 
            RequiredQty='" . $input['RequiredQty'] . "', 
            Requiredunit='" . $input['Requiredunit'] . "', 
            Stability_Qty='" . $input['Stability_Qty'] . "', 
            Stability_unit='" . $input['Stability_unit'] . "', 
            control_sample_Qty='" . $input['control_sample_Qty'] . "', 
            control_sample_unit='" . $input['control_sample_unit'] . "', 
            Marketing_sample_Qty='" . $input['Marketing_sample_Qty'] . "', 
            Marketing_sample_unit='" . $input['Marketing_sample_unit'] . "' ,
            fg_sampling='At Production Done'
        WHERE id='" . $_GET["s_id"] . "'";

             $conn->query($sql2);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "completeFGSampling_pk") {
        $sql = "UPDATE packing_batch_stages_ipqc_dtl SET sampleQty='".$input["sampleQty"]."' ,unit='".$input["unit"]."' , samplingStatus = 'Complete', test_result_status = 'For_Allocation',
        withdraw_by='".$_GET["emp_id"]."', withdraw_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
         $sql1 = "UPDATE technical_info SET sample_qty='".$input["sampleQty"]."' ,unit='".$input["unit"]."' , status = 'For_Allocation'
          WHERE ti_no='".$_GET["ti_no"]."'";
          $conn->query($sql1);
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
   
    
    
    else if ($_GET["type"] == "getPendingReceivingForAllocation") {
        $output = array(); // Initialize outside the loop
$sql = "SELECT t.*,b.stage_dtl_id, p.dosage_form, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code 
        LEFT JOIN packing_batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no  where t.plant_id = '".$_GET["plant_id"]."' AND t.status = 'Received in qc' and t.data_from='Packing'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output1 = array(); // Reset output1 for each row

        $sql1 = "SELECT specification_no FROM specification 
                 WHERE product_code = '" . $row["product_code"] . "' 
                 AND spec_type = 'Inprocess Specification'";

        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $row["specification_no"] = $row1["specification_no"];
            }
        }

        $sql6 = "SELECT a.id, a.stage_hdr_id, a.process_type, a.stage_name, a.stage, 
                        a.ipqc_testing, a.next_stage, s.id AS spec_test_id, 
                        a.yieldRequired, a.exp_yeild_percent, a.yeild_unit, a.split_into_lots, 
                        s.specification_no, a.blending_mixing, s.test_type, s.test, s.limit_type, 
                        s.description, s.lower_limit, s.upper_limit, s.limits 
                 FROM packing_stages_ipqc_dtl a 
                 LEFT JOIN spec_tests s ON a.test_name = s.test  
                 WHERE a.id = '".$row['stage_dtl_id']."'
                 AND s.specification_no = '" . $row["specification_no"] . "'";

        $result6 = $conn->query($sql6);
        if ($result6->num_rows > 0) {
            while ($row6 = $result6->fetch_assoc()) {
                $output1[] = $row6;
            }
        }

        $row["tests"] = $output1;
        $output[] = $row; // Append the row to the main output array
    }
}

echo json_encode($output);
} 
    else if ($_GET["type"] == "getPendingReceivingForAllocationProd") {
        $output = array(); // Initialize outside the loop
$sql = "SELECT t.*,b.stage_dtl_id, p.dosage_form, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code 
        LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no  where t.plant_id = '".$_GET["plant_id"]."' AND t.status = 'Received in qc' and t.data_from is null";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output1 = array(); // Reset output1 for each row

        $sql1 = "SELECT specification_no FROM specification 
                 WHERE product_code = '" . $row["product_code"] . "' 
                 AND spec_type = 'Inprocess Specification'";

        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $row["specification_no"] = $row1["specification_no"];
            }
        }

        $sql6 = "SELECT a.id, a.stage_hdr_id, a.process_type, a.stage_name, a.stage, 
                        a.ipqc_testing, a.next_stage, s.id AS spec_test_id, 
                        a.yieldRequired, a.exp_yeild_percent, a.yeild_unit, a.split_into_lots, 
                        s.specification_no, a.blending_mixing, s.test_type, s.test, s.limit_type, 
                        s.description, s.lower_limit, s.upper_limit, s.limits 
                 FROM stages_ipqc_dtl a 
                 LEFT JOIN spec_tests s ON a.test_name = s.test  
                 WHERE a.id = '".$row['stage_dtl_id']."'
                 AND s.specification_no = '" . $row["specification_no"] . "'";

        $result6 = $conn->query($sql6);
        if ($result6->num_rows > 0) {
            while ($row6 = $result6->fetch_assoc()) {
                $output1[] = $row6;
            }
        }

        $row["tests"] = $output1;
        $output[] = $row; // Append the row to the main output array
    }
}

echo json_encode($output);
} 
    else if ($_GET["type"] == "getPendingReceivingForAllocationReciving") {
        $output = array(); // Initialize outside the loop
$sql = "SELECT t.*,b.stage_dtl_id, p.dosage_form, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code 
        LEFT JOIN packing_batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no  where t.plant_id = '".$_GET["plant_id"]."' AND t.status = 'For_Allocation' and t.data_from='Packing'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output1 = array(); // Reset output1 for each row

        $sql1 = "SELECT specification_no FROM specification 
                 WHERE product_code = '" . $row["product_code"] . "' 
                 AND spec_type = 'Inprocess Specification'";

        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $row["specification_no"] = $row1["specification_no"];
            }
        }

        $sql6 = "SELECT a.id, a.stage_hdr_id, a.process_type, a.stage_name, a.stage, 
                        a.ipqc_testing, a.next_stage, s.id AS spec_test_id, 
                        a.yieldRequired, a.exp_yeild_percent, a.yeild_unit, a.split_into_lots, 
                        s.specification_no, a.blending_mixing, s.test_type, s.test, s.limit_type, 
                        s.description, s.lower_limit, s.upper_limit, s.limits 
                 FROM packing_stages_ipqc_dtl a 
                 LEFT JOIN spec_tests s ON a.test_name = s.test  
                 WHERE a.id = '".$row['stage_dtl_id']."'
                 AND s.specification_no = '" . $row["specification_no"] . "'";

        $result6 = $conn->query($sql6);
        if ($result6->num_rows > 0) {
            while ($row6 = $result6->fetch_assoc()) {
                $output1[] = $row6;
            }
        }

        $row["tests"] = $output1;
        $output[] = $row; // Append the row to the main output array
    }
}

echo json_encode($output);
} 
    else if ($_GET["type"] == "getPendingReceivingForAllocationRecivingProd") {
        $output = array(); // Initialize outside the loop
$sql = "SELECT t.*,b.stage_dtl_id, p.dosage_form, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code 
        LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no  where t.plant_id = '".$_GET["plant_id"]."' AND t.status = 'For_Allocation' and t.data_from is null";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output1 = array(); // Reset output1 for each row

        $sql1 = "SELECT specification_no FROM specification 
                 WHERE product_code = '" . $row["product_code"] . "' 
                 AND spec_type = 'Inprocess Specification'";

        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $row["specification_no"] = $row1["specification_no"];
            }
        }

        $sql6 = "SELECT a.id, a.stage_hdr_id, a.process_type, a.stage_name, a.stage, 
                        a.ipqc_testing, a.next_stage, s.id AS spec_test_id, 
                        a.yieldRequired, a.exp_yeild_percent, a.yeild_unit, a.split_into_lots, 
                        s.specification_no, a.blending_mixing, s.test_type, s.test, s.limit_type, 
                        s.description, s.lower_limit, s.upper_limit, s.limits 
                 FROM stages_ipqc_dtl a 
                 LEFT JOIN spec_tests s ON a.test_name = s.test  
                 WHERE a.id = '".$row['stage_dtl_id']."'
                 AND s.specification_no = '" . $row["specification_no"] . "'";

        $result6 = $conn->query($sql6);
        if ($result6->num_rows > 0) {
            while ($row6 = $result6->fetch_assoc()) {
                $output1[] = $row6;
            }
        }

        $row["tests"] = $output1;
        $output[] = $row; // Append the row to the main output array
    }
}

echo json_encode($output);
} 
    else if ($_GET["type"] == "getPendingReceiving") {
        $output = array();
         
        $sql = "SELECT t.*,b.batch_size, m.batch_number,s.stage_name, p.dosage_form, p.product_name, p.grade,s.fg_sampling FROM batch_stages_ipqc_dtl t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_planning b ON  t.plan_no = b.plan_no LEFT JOIN stages_ipqc_dtl s ON  s.id = t.stage_dtl_id
         left JOIN mfg_work_order_hdr m on m.batch_plan_id = 
             b.id and m.plant_id = b.plant_id
        where t.samplingStatus = 'Pending' AND p.plant_id = '".$_GET["plant_id"]."'  and m.batch_number!=''";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  
                $sql1 = "SELECT sample_qty, unit,specification_no FROM specification WHERE   product_code='".$row["product_code"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["sample_qty"] = $row1["sampe_qty"];
                        $row["unit"] = $row1["unit"];
                        $row["specification_no"] = $row1["specification_no"];
                    }
                }
                
             
                 
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getPendingReceivingFg") {
        $output = array();
         
        $sql = "SELECT t.*,b.batch_size, m.batch_number,s.stage_name, p.dosage_form, p.product_name, p.grade,s.id as s_id FROM batch_stages_ipqc_dtl t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_planning b ON  t.plan_no = b.plan_no LEFT JOIN stages_ipqc_dtl s ON  s.id = t.stage_dtl_id
         left JOIN mfg_work_order_hdr m on m.batch_plan_id = 
             b.id and m.plant_id = b.plant_id
        where s.fg_sampling = 'At Production' AND p.plant_id = '".$_GET["plant_id"]."'  and m.batch_number!=''";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  
                $sql1 = "SELECT sample_qty, unit,specification_no FROM specification WHERE   product_code='".$row["product_code"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["sample_qty"] = $row1["sampe_qty"];
                        $row["unit"] = $row1["unit"];
                        $row["specification_no"] = $row1["specification_no"];
                    }
                }
                
             
                 
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getPendingReceiving_PK") {
        $output = array();
         
        $sql = "SELECT t.*,b.batch_size, m.batch_number,s.stage_name, p.dosage_form, p.product_name, p.grade FROM packing_batch_stages_ipqc_dtl t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_planning b ON  t.plan_no = b.plan_no LEFT JOIN packing_stages_ipqc_dtl s ON  s.id = t.stage_dtl_id
         left JOIN mfg_work_order_hdr m on m.batch_plan_id = 
             b.id and m.plant_id = b.plant_id
        where t.samplingStatus = 'Pending' AND p.plant_id = '".$_GET["plant_id"]."'  and m.batch_number!=''";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT sample_qty, unit,specification_no FROM specification WHERE   product_code='".$row["product_code"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["sample_qty"] = $row1["sampe_qty"];
                        $row["unit"] = $row1["unit"];
                        $row["specification_no"] = $row1["specification_no"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    // else if ($_GET["type"] == "getPendingReceivingForAllocation") {
    //     $output = array();
         
    //     $sql = "SELECT t.*,b.batch_size, m.batch_number,s.stage_name,  p.dosage_form, p.product_name, p.grade FROM batch_stages_ipqc_dtl t LEFT JOIN product p ON 
    //     t.product_code = p.product_code  LEFT JOIN batch_planning b ON  t.plan_no = b.plan_no LEFT JOIN stages_ipqc_dtl s ON  s.id = t.stage_dtl_id
    //      left JOIN mfg_work_order_hdr m on m.batch_plan_id = 
    //          b.id and m.plant_id = b.plant_id
    //     where t.samplingStatus = 'Complete' AND p.plant_id = '".$_GET["plant_id"]."'";
       
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
                  
    //             $sql1 = "SELECT specification_no FROM specification WHERE   product_code='".$row["product_code"]."' AND spec_type = 'Inprocess Specification' ";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                      $row["specification_no"] = $row1["specification_no"];
    //                 }
    //             }
                
    //             $output1 = array();
    //              $sql6="select a.id,a.stage_hdr_id,a.process_type,a.stage_name,a.stage,a.ipqc_testing,a.next_stage,
    //             a.yieldRequired,a.exp_yeild_percent,a.yeild_unit,a.split_into_lots,s.specification_no,a.blending_mixing, s.test_type,s.test,s.limit_type,s.description,s.lower_limit,
    //             s.upper_limit,s.limits,s.description from stages_ipqc_dtl a LEFT JOIN spec_tests s on a.test_name=s.test  where
    //             a.id = '".$row['stage_dtl_id']."' AND  s.specification_no = '".$row["specification_no"]."' ";
                
    //             $result6 = $conn->query($sql6);
    //             if ($result6->num_rows > 0) {
    //                 while ($row6 = $result6->fetch_assoc()) {
                          
    //                      $output1[] = $row6;
    //                 }
    //             }
                 
    //              $row["tests"] = $output1;
                 
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
        
    // } 
    
    
    
    else if ($_GET["type"] == "allocateTests") {
        
        
         $ar_no = "IPQC/";
         
        $id1 = '';
        
        $sql = "SELECT IFNULL(COUNT(id), 0) as id FROM technical_info WHERE ar_no !=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = +$row["id"];
            }
        }
        $id++;
         
            $prod_code = 'F';
       
        if (strlen($id) == 1) {
            $id1= "0000";
        } else if (strlen($id) == 2) {
            $id1= "000";
        } else if (strlen($id) == 3) {
            $id1= "00";
        } else if (strlen($id) == 4) {
            $id1= "0";
        } else if (strlen($id) == 5) {
            $id1= "";
        }
        $ar_no.= $prod_code.'/'.date("y", $timestamp).'/'.$id1.''.$id;
         
        
        $sql = "UPDATE technical_info SET status='Testing',ar_no = '$ar_no', allocate_by='".$_GET["emp_id"]."', 
        allocate_date='$entry_date' WHERE id = '".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $tests = $input["tests"];
            for ($i = 0; $i < count($tests); $i++) {
                $test = $tests[$i];
                
            $sql1 = "INSERT INTO testing_tests (user_no, spec_test_id,testing_no, bmr_qcsample_tests_id,test,description,isoutside,person, 
            person_alt,method_details,outside_testing,specification_no,plant_id)VALUES ('".$_GET["user_no"]."','".$test["spec_test_id"]."',
            '".$input["ti_no"]."','".$input["id"]."','".$test["test"]."','".$test["description"]."',
            'No','".$test["person"]."','".$test["alternate_chemist"]."','".$_GET["testing_type"]."',
            'No','".$input["specification_no"]."','".$_GET["plant_id"]."')"; 
            
            
                $conn->query($sql1);
                 
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } 
    
    else if ($_GET["type"] == "receiveTests") {
                 
            $sql1="update technical_info set status='Received in qc' where id='".$_GET["id"]."'";
            if ($conn->query($sql1)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
         
    }
    else if ($_GET["type"] == "saveTestingawaitForm") {
                 
            $sql1="update technical_info set status='Inprocess' where id='".$_GET["testing_no"]."'";
            if ($conn->query($sql1)) {
                   
       
            
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
         
    }

    
     
    
    else if ($_GET["type"] == "getPendingTestings") {
        $output = array();
         $sql = "SELECT t.*,t.id as testing_no,c.stage_name, p.dosage_form, p.product_name, p.grade,  b.stage_dtl_id FROM technical_info t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN packing_batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no
        LEFT JOIN stages_ipqc_dtl c ON t.stage = c.id WHERE t.status='Testing' AND    t.data_from='Packing' and
        t.plant_id = '".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                 $output1 = array();
                 
                 $sql6="select a.*,  a.id as ttt_id,
                s.test_type,s.test,s.test_method_no,s.limit_type,s.description,s.lower_limit,s.upper_limit,s.limits,s.description from testing_tests a 
                LEFT JOIN spec_tests s on a.spec_test_id = s.id  where a.bmr_qcsample_tests_id = '".$row['id']."' ";
                
                $result6 = $conn->query($sql6);
                if ($result6->num_rows > 0) {
                    while ($row6 = $result6->fetch_assoc()) {
                          
                         $output1[] = $row6;
                    }
                }
                 
                 $row["tests"] = $output1;
                    $output[] = $row;
                    
                }
            }
            echo json_encode($output);
        }
    else if ($_GET["type"] == "getPendingTestingsProd") {
        $output = array();
         $sql = "SELECT t.*,t.id as testing_no,c.stage_name, p.dosage_form, p.product_name, p.grade,  b.stage_dtl_id FROM technical_info t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no
        LEFT JOIN stages_ipqc_dtl c ON t.stage = c.id WHERE t.status='Testing'    and t.data_from is null and
        t.plant_id = '".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                 $output1 = array();
                 
                 $sql6="select a.*,  a.id as ttt_id,
                s.test_type,s.test,s.test_method_no,s.limit_type,s.description,s.lower_limit,s.upper_limit,s.limits,s.description from testing_tests a 
                LEFT JOIN spec_tests s on a.spec_test_id = s.id  where a.bmr_qcsample_tests_id = '".$row['id']."' ";
                
                $result6 = $conn->query($sql6);
                if ($result6->num_rows > 0) {
                    while ($row6 = $result6->fetch_assoc()) {
                          
                         $output1[] = $row6;
                    }
                }
                 
                 $row["tests"] = $output1;
                    $output[] = $row;
                    
                }
            }
            echo json_encode($output);
        }
        
     else if ($_GET["type"] == "saveTesting") {
        
        $observation = "";
        if ($input["limit_type"] == "Limits") {
            if ($input["result"] >= $input["lower_limit"] && $input["result"] <= $input["upper_limit"]) {
                $observation = "pass";
            } else {
                $observation = "fail";
            }
        } else if ($input["limit_type"] == "LessThan") {
            if ($input["result"] <= $input["lessthan"]) {
                $observation = "pass";
            } else {
                $observation = "fail";
            }
        } else if ($input["limit_type"] == "MoreThan") {
            if ($input["result"] >= $input["morethan"]) {
                $observation = "pass";
            } else {
                $observation = "fail";
            }
        } else if ($input["limit_type"] == "Compliances") {
            if ($input["result"] == "complies") {
                $observation = "pass";
            } else {
                $observation = "fail";
            }
        }
        
        $sql = "UPDATE technical_tests SET status='inprocess', result='".$input["result"]."', observation='".$observation."', remark='".$input["remark"]."', analysis_start_time='".$input["analysis_start_time"]."', analysis_end_time='".$input["analysis_end_time"]."', analysis_date='$entry_date', details='".json_encode($input["method_details"])."' WHERE id='".$input["test_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    else if ($_GET["type"] == "saveTestingForm1") {
         
        $input=$_POST;
          
     $sql = "UPDATE testing_tests  SET start_time = '".$input["start_time"]."', remark='".$input["remark"]."', result='".$_GET["result"]."', observation='".$input["observation"]."',
     end_time='".$input["end_time"]."',status='Inprocess',perform_by='".$_GET["emp_id"]."' WHERE id='".$input["testing_test_id"]."'";
    
    if ($conn->query($sql)) {
        
        
        
            $sql11="update batch_stages_ipqc_dtl set ti_recd_date = '".$input["end_time"]."', test_remarks = '".$input["remark"]."' ,
            test_result= '".$_GET["result"]."' , ti_recd_by='".$_GET["emp_id"]."' ,  test_result_status = 'Inprocess' where ti_no='".$_GET["ti_no"]."'";
            $conn->query($sql11);
        
        
        
       
        $sql11= "Insert into test_moa(bmr_qcsample_tests_id, `procedure`,test_id,test_method_no,balance,equipment_instruments,glasswares,dilutions,standards,gc,uw,
        volumetric_solutions,solution_preparation,entry_by,chemical_reagents,spec_test_id,hplc,media,phases,Documentations,Trendings,Genral_Instruction,
        Safety,purpose,Scope,Associative_Document,Refrenced_Document,defination,testinginstruction,weighing_table,calculations)
        values('".$input["bmr_qcsample_tests_id"]."','".$input["procedure"]."','".$input["testing_test_id"]."','".$input["test_method_no"]."','".json_encode($input["balance"])."','".json_encode($input["eqdates"])."','".json_encode($input["glasswares"])."',
        '".json_encode($input["dilutions"])."',
      '".$input["standards"]."','".$input["gc"]."','".$input["uw"]."',
        '".json_encode($input["volumetric_solutions"])."','".$input["solution_preparation"]."','".$_GET["emp_id"]."',
        '".json_encode($input["chemi_data"])."','".$input["id"]."','".json_encode($input["hplc"])."','".json_encode($input["mediaData"])."',
        '".json_encode($input["phases"])."','".$input["Documentations"]."','".$input["Trendings"]."','".json_encode($input["Genral_Instruction"])."',
        '".$input["Safety"]."','".$input["purpose"]."','".$input["Scope"]."','".json_encode($input["Associative_Document"])."',
        '".json_encode($input["Refrenced_Document"])."','".json_encode($input["defination"])."',
        '".json_encode($input["testinginstruction"])."','".json_encode($input["weighing_table"])."','".json_encode($input["calculations"])."')";
      
      $conn->query($sql11);
        
                     $json_obj1 = json_encode($input["chemi_data"]);
              $array1 = json_decode($json_obj1, true);
               
                foreach ($array1 as $values) 
                {
                    
                    $sql1 = "INSERT INTO `engi_stock_isshue`( `isshue_for`, `material_code`,  `batch_no`, `qty`, `entry_by`, `entry_date`,
                    `plant_id`,disp_material_name,disp_material_code,testing_no) VALUES ('TESTING','".$values["material_code"]."','".$values["batch_nos"]."',
                    '".$values["dis_qty_chem"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."','".$input["material_name"]."',
                    '".$input["material_code"]."','".$input["testing_no"]."')";
                     $conn->query($sql1);
                    
                }
                
               $json_obj2 = json_encode($input["volumetric_solutions"]);
              $array2 = json_decode($json_obj2, true);
                
                foreach ($array2 as $values)
                {
                     $sql1 = "INSERT INTO `engi_stock_isshue`( `isshue_for`, `material_code`,  `batch_no`, `qty`, `entry_by`, `entry_date`,
                    `plant_id`,disp_material_name,disp_material_code,testing_no) VALUES ('TESTING','".$values["solution_no"]."','".$values["batch_no1"]."',
                    '".$values["dis_qty"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."','".$input["material_name"]."',
                    '".$input["material_code"]."','".$input["testing_no"]."')";
                     $conn->query($sql1);
                    
                }
       
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    }
    else if ($_GET["type"] == "saveTestingForm12") {
         
         
        $input=$_POST;
         if($input['from_rec']=='Packing'){
         $table_NAme='packing_batch_stages_ipqc_dtl';
         }else{
         $table_NAme='batch_stages_ipqc_dtl';
         }
          
     $sql = "UPDATE testing_tests  SET start_time = '".$input["start_time"]."', remark='".$input["remark"]."', result='".$_GET["result"]."', observation='".$input["observation"]."',
     end_time='".$input["end_time"]."',status='Inprocess',perform_by='".$_GET["emp_id"]."' WHERE id='".$input["testing_test_id"]."'";
    
    if ($conn->query($sql)) {
         
            $sql11="update $table_NAme set ti_recd_date = '".$input["end_time"]."', test_remarks = '".$input["remark"]."' ,
            test_result= '".$_GET["result"]."' , ti_recd_by='".$_GET["emp_id"]."' ,  test_result_status = 'Inprocess' where id='".$input["Tech_info_id"]."'";
            $conn->query($sql11);
         
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    }

     
    
    
    
    // else if ($_GET["type"] == "getInprocessTestings") {
    //     $output = array();
    //      $sql = "SELECT t.*,t.id as testing_no,c.stage_name, p.dosage_form, p.product_name, p.grade,  b.stage_dtl_id FROM technical_info t LEFT JOIN product p ON 
    //     t.product_code = p.product_code  LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no
    //     LEFT JOIN stages_ipqc_dtl c ON t.stage = c.id WHERE t.status='Inprocess' AND 
    //     t.plant_id = '".$_GET["plant_id"]."'";
        
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
                
    //              $output1 = array();
                 
                      
    //   $sql1="SELECT t.*,s2.limits,t.remark as remark,t.id as ttt_id,t.status as t_status,tt.method as moa_status ,(select count(ttt.test_method_no) 
    //               from test_methods ttt  where ttt.test_method_no=s2.test_method_no) as method_count FROM testing_tests t   
    //               LEFT JOIN spec_tests s2 on s2.test=t.test  and 
    //           s2.id = t.spec_test_id left join test tt on tt.test_method_no=s2.test_method_no 
    //               AND tt.test=s2.test  WHERE t.bmr_qcsample_tests_id='".$row["id"]."'     ";
                
    //             $output1 = Array();
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     if ($row1["observation"] == "fail") {
    //                         $row["observation"] = "fail";
    //                     }
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row["tests"] = $output1;
    //                 $output[] = $row;
                    
    //             }
    //         }
    //         echo json_encode($output);
    //     } 
    else if ($_GET["type"] == "getInprocessTestings") {
        $output = array();
         $sql = "SELECT t.*,t.id as testing_no,c.stage_name, p.dosage_form, p.product_name, p.grade,  b.stage_dtl_id FROM technical_info t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no
        LEFT JOIN packing_stages_ipqc_dtl c ON t.stage = c.id WHERE t.status='Inprocess' AND t.data_from='Packing' and
        t.plant_id = '".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                 $output1 = array();
                 
                      
      $sql1="SELECT t.*,s2.limits,t.remark as remark,t.id as ttt_id,t.status as t_status,tt.method as moa_status ,(select count(ttt.test_method_no) 
                  from test_methods ttt  where ttt.test_method_no=s2.test_method_no) as method_count FROM testing_tests t   
                  LEFT JOIN spec_tests s2 on s2.test=t.test  and 
               s2.id = t.spec_test_id left join test tt on tt.test_method_no=s2.test_method_no 
                   AND tt.test=s2.test  WHERE t.bmr_qcsample_tests_id='".$row["id"]."'     ";
                
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                    $output[] = $row;
                    
                }
            }
            echo json_encode($output);
        } 
    else if ($_GET["type"] == "getInprocessTestingsProd") {
        $output = array();
         $sql = "SELECT t.*,t.id as testing_no,c.stage_name, p.dosage_form, p.product_name, p.grade,  b.stage_dtl_id FROM technical_info t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no
        LEFT JOIN stages_ipqc_dtl c ON t.stage = c.id WHERE t.status='Inprocess' AND  t.data_from is null and
        t.plant_id = '".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                 $output1 = array();
                 
                      
      $sql1="SELECT t.*,s2.limits,t.remark as remark,t.id as ttt_id,t.status as t_status,tt.method as moa_status ,(select count(ttt.test_method_no) 
                  from test_methods ttt  where ttt.test_method_no=s2.test_method_no) as method_count FROM testing_tests t   
                  LEFT JOIN spec_tests s2 on s2.test=t.test  and 
               s2.id = t.spec_test_id left join test tt on tt.test_method_no=s2.test_method_no 
                   AND tt.test=s2.test  WHERE t.bmr_qcsample_tests_id='".$row["id"]."'     ";
                
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                    $output[] = $row;
                    
                }
            }
            echo json_encode($output);
        } 
    

 
    
    
    else if ($_GET["type"] == "checkTesting") {
        
            $isRejct=true;  
             $json_obj = json_encode($input["spec_tests"]);
            $array = json_decode($json_obj, true);
            
                foreach ($array as $values)
                {
                    if($values["observation"] !== 'complies'){
                      $isRejct=false;
                    }
                }
                
            if($isRejct){
                 $sql = "UPDATE technical_info SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
            }
            else{
                
                $sql = "UPDATE technical_info SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date',correction='YES' WHERE id='".$_GET["id"]."'";
           
            }
            
        if ($conn->query($sql)) {
            
            
        $sql11="update batch_stages_ipqc_dtl set    test_result_status = '".$_GET["status"]."' where ti_no='".$_GET["ti_no"]."'";
            $conn->query($sql11);
            
            
            
            
           
            $json_obj = json_encode($input["spec_tests"]);
            $array = json_decode($json_obj, true);
            
                foreach ($array as $values)
                {
                      $sql1 = "Update testing_tests set checked_by = '".$_GET["emp_id"]."', checked_date='$entry_date' , checker_action = '".$values["error_type"]."'  WHERE spec_test_id='".$values["spec_test_id"]."'";
        
                    $conn->query($sql1);
                    
                }
                
                
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } 
    
    
    
    
    
    else if ($_GET["type"] == "getTestingsLog") {
        $output = array();
         $sql = "SELECT t.*,t.id as testing_no,c.stage_name, p.dosage_form, p.product_name, p.grade,  b.stage_dtl_id FROM technical_info t LEFT JOIN product p ON 
        t.product_code = p.product_code  LEFT JOIN batch_stages_ipqc_dtl b ON t.ti_no = b.ti_no
        LEFT JOIN stages_ipqc_dtl c ON t.stage = c.id WHERE ( t.status='Approved' OR t.status ='reject' ) AND 
        t.plant_id = '".$_GET["plant_id"]."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                 $output1 = array();
                 
                      
      $sql1="SELECT t.*,s2.limits,t.remark as remark,t.id as ttt_id,t.status as t_status,tt.method as moa_status ,(select count(ttt.test_method_no) 
                  from test_methods ttt  where ttt.test_method_no=s2.test_method_no) as method_count FROM testing_tests t   
                  LEFT JOIN spec_tests s2 on s2.test=t.test  and 
               s2.id = t.spec_test_id left join test tt on tt.test_method_no=s2.test_method_no 
                   AND tt.test=s2.test  WHERE t.bmr_qcsample_tests_id='".$row["id"]."'     ";
                
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["observation"] == "fail") {
                            $row["observation"] = "fail";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                    $output[] = $row;
                    
                }
            }
            echo json_encode($output);
        }
    else if($_GET["type"]=="TechnicalInfoLogPDF"){
        $_GET['filename'] = 'Technical Information Sheet Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      $html.='
      <h2 style="text-align:center">Technical Information Sheet Log</h2>
        <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;"><b>Sr</b></td>
                <td style="width:10%;"><b>TI No</b></td>
                <td style="width:10%;"><b>BMR No</b></td>
                <td style="width:10%;"><b>Product Code</b></td>
                <td style="width:11%;"><b>Product Name</b></td>
                <td style="width:10%;"><b>Stage</b></td>
                <td style="width:10%;"><b>Grade</b></td>
                <td style="width:12%;"><b>Sampling Date</b></td>
                <td style="width:12%;"><b>Release Date</b></td>
                <td style="width:10%;"><b>Status</b></td>
            </tr>';
            $sql = "SELECT t.*, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='done'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                <td style="width:5%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['ti_no'].'</td>
                <td style="width:10%;">'.$row['bmr_no'].'</td>
                <td style="width:10%;">'.$row['product_code'].'</td>
                <td style="width:11%;">'.$row['product_name'].'</td>
                <td style="width:10%;">'.$row['stage'].'</td>
                <td style="width:10%;">'.$row['grade'].'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                <td style="width:12%;">'.date('d-m-Y',strtotime($row['receive_date'])).'</td>
                <td style="width:10%;">'.$row['status'].'</td>
            </tr>';
            $i++;
            }
        }
            $html.='</table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('changecontrol.pdf', 'I');
        
        
    } else if($_GET['type'] == 'downloadTechnicalRecord'){
            $_GET['filename'] = 'INPROCESS SPECIFICATION'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
                $html.='
                <h3>INPROCESS TEST REQUEST SLIP</h3>
           <table cellpadding="8" border="0.1">
           <tr>
           <td style="width:15%;background-color:#DDDAD9;"><b>FROM</b></td>
           <td style="width:35%;"></td>
           <td style="width:15%;background-color:#DDDAD9;"><b>TO</b></td>
           <td style="width:35%"></td>
           </tr>
           <tr>
           <td style="width:15%;background-color:#DDDAD9;"><b>Sample Qty</b></td>
           <td style="width:35%"></td>
           <td style="width:15%;background-color:#DDDAD9;"><b>Unit</b></td>
           <td style="width:35%"></td>
           </tr>
           <tr>
           <td style="width:15%;background-color:#DDDAD9;"><b>Equipment ID</b></td>
           <td style="width:35%"></td>
           <td style="width:15%;background-color:#DDDAD9;"><b>Stage</b></td>
           <td style="width:35%"></td>
           </tr>
           
           <tr>
           <td style="width:15%;background-color:#DDDAD9;"><b>TI NO.</b></td>
           <td style="width:35%"></td>
           <td style="width:15%;background-color:#DDDAD9;"><b>Batch No.</b></td>
           <td style="width:35%"></td>
           </tr>
           </table><div></div>
            <h3>Tests:</h3>
           <table cellpadding="8" border="0.1">
          <tr style="text-align:center;background-color:#DDDAD9;">
           <td style="width:20%;text-align:center"><b>Sr No.</b></td>
           <td style="width:20%;text-align:center"><b>Test</b></td>
           <td style="width:20%;text-align:center"><b>Subtest</b></td>
           <td style="width:20%;text-align:center"><b>Limits</b></td>
           <td style="width:20%;text-align:center"><b>Result</b></td>
           </tr>
           <tr>
           <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
           </tr>
           </table><div></div>
           <h3>Remark</h3>
           <table cellpadding="5" border="0.1">
           <tr>
           <td style="width:100%"></td>
           </tr>
           </table>
            <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:15%"> </td>
                    <td style="width:30%;text-align:center"><b>Prepared by</b></td>
                    <td style="width:25%;text-align:center"><b>Checked By</b></td>
                    <td style="width:30%;text-align:center"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:15%;text-align:center"><b>Name</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
              <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Date</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
               <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Sign</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
             <td style="width:30%"></td>
              </tr>
         </table>';
    
       $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        
    }
    else if($_GET['type'] == 'TechnicalInfoPDF'){
        $_GET['filename'] = 'Technical Information Sheet Details'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html.="";
        $sql = "SELECT t.*, p.product_name, p.grade FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code WHERE t.status='done'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
         $html.='
         <h3 style="text-align:center">Product Details:</h3>
         <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%; text-align:centre;"><b>Product Code</b></td>
                <td style="width:25%; text-align:centre;">'.$row['product_code'].'</td>
                <td style="width:25%; text-align:centre;"><b>Product Name</b></td>
                <td style="width:25%; text-align:centre;">'.$row['product_name'].'</td>
            </tr>
            <tr>
                <td style="width:25%; text-align:centre;"><b>Batch Size</b></td>
                <td style="width:25%; text-align:centre;">'.$row['batch_size'].'</td>
                <td style="width:25%; text-align:centre;"><b>Batch No</b></td>
                <td style="width:25%; text-align:centre;">'.$row['batch_no'].'</td>
            </tr>
            <tr>
                <td style="width:25%; text-align:centre;"><b>Dosage Form</b></td>
                <td style="width:25%; text-align:centre;">'.$row['dosage_form'].'</td>
                <td style="width:25%; text-align:centre;"><b>Grade</b></td>
                <td style="width:25%; text-align:centre;">'.$row['grade'].'</td>
            </tr>
         </table>
         <h3>Sampling Information:</h3>
         <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%; text-align:centre;"><b>Sampled By</b></td>
                <td style="width:25%; text-align:centre;">'.$row['sample_by'].'</td>
                <td style="width:25%; text-align:centre;"><b>Check By</b></td>
                <td style="width:25%; text-align:centre;">'.$row['receive_by'].'</td>
            </tr>
            <tr>
                <td style="width:25%; text-align:centre;"><b>Sampling Qty</b></td>
                <td style="width:25%; text-align:centre;">'.$row['sample_qty'].'</td>
                <td style="width:25%; text-align:centre;"><b>Sampling Date</b></td>
                <td style="width:25%; text-align:centre;"></td>
            </tr>';
            }
        }
         $html.='</table>
         <h3>Analytical Information:</h3>
         <table cellpadding="5" border="1">
            <tr>
                <td style="width:20%; text-align:centre;"><b>Test</b></td>
                <td style="width:20%; text-align:centre;"><b>Specification</b></td>
                <td style="width:20%; text-align:centre;"><b>Result</b></td>
                <td style="width:20%; text-align:centre;"><b>Analysis By</b></td>
                <td style="width:20%; text-align:centre;"><b>Check By</b></td>
            </tr>';
                
            $html.='<tr>
                <td style="width:20%; "></td>
                <td style="width:20%; "></td>
                <td style="width:20%; "></td>
                <td style="width:20%; "></td>
                <td style="width:20%; "></td>
            </tr>';
         $html.='</table>';
         EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    } else if($_GET['type'] == 'downloadTechnicalTestLog'){
            $_GET['filename'] = 'INPROCESS SPECIFICATION'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
                $html.='
                <h2 style="text-align:Center">INPROCESS SPECIFICATION</h2>
                <table cellpadding="5">
                    
                        <tr style="background-color:#DDDAD9;font-weight:bold">
                            <td>Spec No.</td>
                            <td>Doasge Form</td>
                             <td>Product Name</td>
                            <td>Product Code</td>
                            <td>Reference</td>
                            <td>Sample Qty</td>
                            <td>Version No</td>
                            <td>Supersed No.</td>
                            <td>Effective date</td>
                            <td>Revision Period</td>
                             <td>Status</td>
                        </tr>
                
                    ';
            $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND status!='obsolate'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["product_name"] = $row2["product_name"];
                            $row["generic_name"] = $row2["generic_name"];
                            $row["grade"] = $row2["grade"];
                        }
                    }
                    $html.='
                    <tr nobr="true">
                        <td>'.$row['specification_no'].'</td>
                           <td>'.$row['doasge_form'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                              <td>'.$row['effective_date'].'</td>
                            <td>'.$row['revision_period'].'</td>
                             <td>'.$row['status'].'</td>
                    </tr>
                    ';
                }
            }
            $html.='</tbody></table>';
            // EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'TechnicalInfodigitalPDF'){
        $sql = "SELECT t.*, p.dosage_form, p.product_name, p.grade, b.batch_no, b.batch_size, b.bmr_no as std_bmr_no FROM technical_info t LEFT JOIN product p ON t.product_code=p.product_code LEFT JOIN bmr b ON t.bmr_no=b.id WHERE t.id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Technical Information Sheet'; $_GET['type'] = 'onlyheader'; include("../pdfimp2.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'headerlandscape';
                        include("../pdfimp.php");
                    }
                    public function Footer() {}
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 15);
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <h4>Product Details</h4>
                    <table cellpadding="3">
                        <tr>
                            <td><b>Product Code</b></td>
                            <td>'.$row['product_code'].'</td>
                            <td><b>Product Name</b></td>
                            <td>'.$row['product_code'].'</td>
                        </tr>
                        <tr>
                            <td><b>Batch Size</b></td>
                            <td>'.$row['batch_size'].'</td>
                            <td><b>Batch No</b></td>
                            <td>'.$row['batch_no'].'</td>
                        </tr>
                        <tr>
                            <td><b>Dosage Form</b></td>
                            <td>'.$row['dosage_form'].'</td>
                            <td><b>Grade</b></td>
                            <td>'.$row['grade'].'</td>
                        </tr>
                    </table>
                    <h4>Sampling Information</h4>
                    <table cellpadding="3">
                        <tr>
                            <td><b>Sampled By</b></td>
                            <td>'.$row['withdraw_by'].'</td>
                            <td><b>Check By</b></td>
                            <td>'.$row['check_by'].'</td>
                        </tr>
                        <tr>
                            <td><b>Sampling Qty</b></td>
                            <td>'.$row['sample_qty'].'</td>
                            <td><b>Sampling Date</b></td>
                            <td>'.$row['withdraw_date'].'</td>
                        </tr>
                    </table>';
                    $output1 = array();
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no= (SELECT specification_no FROM specification WHERE spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row["stage"]."')";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $html.='<h4>Analytical Information:</h4>
                        <table cellpadding="3">
                            <tr style="font-weight:bold;">
                                <td>Test</td>
                                <td>Specification</td>
                                <td>Result</td>
                                <td>Analysis By</td>
                            </tr>';
                        while ($row1 = $result1->fetch_assoc()) {
                        $html.='
                        <tr>
                            <td>'.$row1['test'].'</td>
                            <td>'.$row1['description'].'</td>
                            <td>'.$row1[''].'</td>
                            <td>'.$row1[''].'</td>
                        </tr>';
                        }
                        $html.='
                        </table>';
                    }
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('', 'I');
            }
        }
    }

}

$conn->close();
?>