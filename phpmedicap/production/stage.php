<?php


    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

    require '../tcpdf/tcpdf.php';
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    if ($_GET["type"] == "save_iqpc_stage_pk") {
        
        
        $sql="SELECT * FROM packing_stages_ipqc WHERE product_code='".$input["product_code"]."'   AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"BMR Checklist for this product already exists. Duplicate Values are not allowed\"}";
        }
        else {

            $sql = "Insert into packing_stages_ipqc(plant_id,product_type,product_name,product_code,exp_yeild_min,exp_yeild_max,stages_test,user_no,status)
            values('".$_GET["plant_id"]."','".$input["product_type"]."','".$input["product_name"]."','".$input["product_code"]."','".$input["exp_yeild_min"]."',
            '".$input["exp_yeild_max"]."','".json_encode($input["stages_test"])."','".$_GET["user_no"]."','Pending');";
            
            if($conn->query($sql)){
                $last_id = $conn->insert_id;
                $materials = $input["stages_test"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "INSERT INTO packing_stages_ipqc_dtl (stage_hdr_id,process_type, stage_name, stage, ipqc_testing, next_stage,yieldRequired, exp_yeild_percent,yeild_unit, 
                    test_name, sub_test,result_type,unit,lower_limit,upper_limit,less_than_value,more_than_value,split_into_lots,blending_mixing,fg_sampling)
                    VALUES ('".$last_id."','".$material['process_type']."', '".$material['stage_name']."', 
                    '".$material['stage']."', '".$material['ipqc_testing']."', '".$material['next_stage']."','".$material['yieldRequired']."', '".$material['exp_yeild_percent']."', '".$material['yeild_unit']."',
                    '".$material["test_name"]."', '".$material["sub_test_name"]."', '".$material["limit"]."', '".$material["unit"]."',
                     '".$material["min"]."', '".$material["max"]."', '".$material["notlessthan"]."', '".$material["notmorethan"]."',
                     '".$material["split_into_lots"]."', '".$material["blending_mixing"]."', '".$material["fg_sampling"]."')";
                    $conn->query($sql1);
                }
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
            
        }
    }
    if ($_GET["type"] == "save_iqpc_stage") {
        
        
        $sql="SELECT * FROM stages_ipqc WHERE product_code='".$input["product_code"]."'   AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"BMR Checklist for this product already exists. Duplicate Values are not allowed\"}";
        }
        else {

            $sql = "Insert into stages_ipqc(plant_id,product_type,product_name,product_code,exp_yeild_min,exp_yeild_max,stages_test,user_no,status)
            values('".$_GET["plant_id"]."','".$input["product_type"]."','".$input["product_name"]."','".$input["product_code"]."','".$input["exp_yeild_min"]."',
            '".$input["exp_yeild_max"]."','".json_encode($input["stages_test"])."','".$_GET["user_no"]."','Pending');";
            
            if($conn->query($sql)){
                $last_id = $conn->insert_id;
                $materials = $input["stages_test"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "INSERT INTO stages_ipqc_dtl (stage_hdr_id,process_type, stage_name, stage, ipqc_testing, next_stage,yieldRequired, exp_yeild_percent,yeild_unit, 
                    test_name, sub_test,result_type,unit,lower_limit,upper_limit,less_than_value,more_than_value,split_into_lots,blending_mixing,fg_sampling)
                    VALUES ('".$last_id."','".$material['process_type']."', '".$material['stage_name']."', 
                    '".$material['stage']."', '".$material['ipqc_testing']."', '".$material['next_stage']."','".$material['yieldRequired']."', '".$material['exp_yeild_percent']."', '".$material['yeild_unit']."',
                    '".$material["test_name"]."', '".$material["sub_test_name"]."', '".$material["limit"]."', '".$material["unit"]."',
                     '".$material["min"]."', '".$material["max"]."', '".$material["notlessthan"]."', '".$material["notmorethan"]."',
                     '".$material["split_into_lots"]."', '".$material["blending_mixing"]."', '".$material["fg_sampling"]."')";
                    $conn->query($sql1);
                }
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
            
        }
    }
    else if ($_GET["type"] == "revised_iqpc_stage") {
         

            $sql = "Update stages_ipqc SET status = 'Revised' ,ccNo = '".$input['ccNo']."'   where id = '".$_GET['id']."'";
            
            if($conn->query($sql)){
                
                $materials = $input["stages_test"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    $sql1 = "Update stages_ipqc_dtl SET   ipqc_testing = '".$material['ipqc_testing']."', next_stage = '".$material['next_stage']."',
                    yieldRequired = '".$material['yieldRequired']."' where id = '".$material['id']."'";
                     
                    $conn->query($sql1);
                }
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
            
        
    }
    else if ($_GET["type"] == "getSamplingType") {
         
         
         $output1 = ['samplingType' => ''];
 
         
        $sql1 = "SELECT fieldValue FROM  dynamicData where  field = '".$_GET["field"]."'  AND  plant_id =  '".$_GET["plant_id"]."' ";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1['samplingType'] = $row1['fieldValue'];
            }
        } 

        
        
     echo json_encode($output1);

        
            
    }
    else if ($_GET["type"] == "saveSamplingType") {
         
            $sql = "insert into dynamicData (plant_id,field,fieldValue,entryBy,entryDate) values ('".$_GET["plant_id"]."',
            '".$input["field"]."','".$input["fieldValue"]."','".$_GET["emp_id"]."','$entry_date') ";
            
            if($conn->query($sql)){
                
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
            
    }
    else if ($_GET["type"] == "editSamplingType") {
         
            $sql = "UPDATE dynamicData SET fieldValue = '".$input["fieldValue"]."'  where field =  '".$input["field"]."' AND  plant_id =  '".$_GET["plant_id"]."' ";
            
            if($conn->query($sql)){
                
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
            
    }
    
    else if ($_GET["type"] == "UpdateStageStatus") {
         
            $sql = "UPDATE stages_ipqc SET status = '".$_GET["status"]."'  where id =  '".$_GET["id"]."' ";
            
            if($conn->query($sql)){
                
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
            
    }
    
    // else if ($_GET["type"] == "save_iqpc_stage_pk") {
        
        
    //     $sql="SELECT * FROM packing_stage WHERE product_code='".$input["product_code"]."' AND material_type= '".$input["material_type"]."' AND plant_id ='".$_GET["plant_id"]."' ";
    //     $result =$conn->query($sql);
    //     if ($result->num_rows > 0) {
    //       	echo "{\"status\":\"BMR Checklist for this product already exists. Duplicate Values are not allowed\"}";
    //     }
    //     else {

    //         $sql = "Insert into packing_stage(plant_id,product_type,product_name,product_code,exp_yeild_min,exp_yeild_max)
    //         values('".$_GET["plant_id"]."','".$input["product_type"]."','".$input["product_name"]."','".$input["product_code"]."','".$input["exp_yeild_min"]."',
    //         '".$input["exp_yeild_max"]."');";
            
    //         if($conn->query($sql)){
    //             $last_id = $conn->insert_id;
    //             $materials = $input["stages_test"];
    //             for ($i = 0; $i < count($materials); $i++) {
    //                 $material = $materials[$i];
    //                 $sql1 = "INSERT INTO packing_stages_ipqc_dtl (stage_hdr_id,process_type, stage_name, stage, ipqc_testing, next_stage, exp_yeild_percent,yeild_unit, 
    //                 test_name, sub_test,result_type,unit,lower_limit,upper_limit,less_than_value,more_than_value,split_into_lots,blending_mixing,fg_sampling)
    //                 VALUES ('".$last_id."','".$material['process_type']."', '".$material['stage_name']."', 
    //                 '".$material['stage']."', '".$material['ipqc_testing']."', '".$material['next_stage']."', '".$material['exp_yeild_percent']."', '".$material['yeild_unit']."',
    //                 '".$material["test_name"]."', '".$material["sub_test_name"]."', '".$material["limit"]."', '".$material["unit"]."',
    //                  '".$material["min"]."', '".$material["max"]."', '".$material["notlessthan"]."', '".$material["notmorethan"]."',
    //                  '".$material["split_into_lots"]."', '".$material["blending_mixing"]."', '".$material["fg_sampling"]."')";
    //                 $conn->query($sql1);
    //             }
    //     		echo "{\"status\":\"success\"}";
    //     	} else {
    //     		echo "{\"status\":\"".$conn->error."\"}";
    //     	}
            
    //     }
    // }
    else if ($_GET["type"] == "downloadlog") {
        $_GET['formatno'] = 'Bill of Material'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
       <h2 style="text-align:center">Bill of Material</h2>
            <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:5%;">Sr</td>
                        <td style="width:10%;">MFR No</td>
                        <td style="width:10%;">Product Type</td>
                        <td style="width:15%;">Product Code</td>
                        <td style="width:15%;">Product Name</td>
                        <td style="width:10%;">Grade.</td>
                        <td style="width:10%;">Input Qty</td>
                        <td style="width:10%;">Dispatch Qty</td>
                        <td style="width:10%;">Yield Quantity</td>
                        <td style="width:5%;">Unit</td>
                    </tr>
                </thead>';
            $output = array();
            $sql = "SELECT u.*, p.product_name, p.grade, p.product_type FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE p.product_type LIKE '%".$_GET["product_type"]."%'";
    	    //$sql = "SELECT u.*, p.product_name, p.grade, p.product_type FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.user_no='".$_GET["user_no"]."' ";
    	    $result = $conn->query($sql);
    	    $i=1;
    	    if ($result->num_rows > 0) {
    	        while ($row = $result->fetch_assoc()) {
    	        
                $html.='
                <tbody>
                    <tr>
                        <td style="width:5%;">'.$i.'</td>
                        <td style="width:10%;">'.$row['mfr_no'].'</td>
                        <td style="width:10%;">'.$row['product_type'].'</td>
                        <td style="width:15%;">'.$row['product_code'].'</td>
                        <td style="width:15%;">'.$row['product_name'].'</td>
                        <td style="width:10%;">'.$row['grade'].'</td>
                        <td style="width:10%;">'.$row['input_qty'].'</td>
                        <td style="width:10%;">'.$row['dispatch_qty'].'</td>
                        <td style="width:10%;">'.$row['yield_qty'].'</td>
                        <td style="width:5%;">'.$row['unit'].'</td>
                    </tr>
                </tbody>';
                $i++;
                }
            }
        $html.="
        </table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MFR Log.pdf', 'I');
    }
    else if ($_GET["type"] == "get_iqpc_stages") {
           $output = Array();
          $output1 = Array();
        $sql = "Select * from stages_ipqc where plant_id ='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                        $sql1 = "Select * from stages_ipqc_dtl where stage_hdr_id ='".$row["id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $row["stages_test"] = $output1;
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_iqpc_stagesForChecking") {   $output = Array();
          $output1 = Array();
        $sql = "Select * from stages_ipqc where plant_id ='".$_GET["plant_id"]."' AND status = 'Pending'";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                        $sql1 = "Select * from stages_ipqc_dtl where stage_hdr_id ='".$row["id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $row["stages_test"] = $output1;
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_iqpc_stagesForApproval") {
           $output = Array();
          $output1 = Array();
        $sql = "Select * from stages_ipqc where plant_id ='".$_GET["plant_id"]."' AND ( status = 'Reviewed' OR status = 'Revised')  ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                        $sql1 = "Select * from stages_ipqc_dtl where stage_hdr_id ='".$row["id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $row["stages_test"] = $output1;
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_iqpc_stagesForRevision") {
         $output = Array();
          $output1 = Array();
        $sql = "Select * from stages_ipqc where plant_id ='".$_GET["plant_id"]."' AND status = 'For_Revision' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                        $sql1 = "Select * from stages_ipqc_dtl where stage_hdr_id ='".$row["id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $row["stages_test"] = $output1;
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_inprocess_checks") {
        $sql = "Select * from manufacturing_process where stage ='".$_GET["stage"]."' and  step ='".$_GET["step"]."' and inprocess_checks='Applicable'";
        ///and material_type = '".$_GET["material_type"]."'  order by 1 desc";
        
     $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "fg_sub_materials") {
        $sql = "SELECT * FROM master_fg_types where plant_id ='".$_GET["plant_id"]."' ";
     $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_stages_by_process_type") {
         $output = Array();
        $data = array();
            $output = Array();
             $sql = "SELECT  * FROM product where dosage_form =  '".$_GET["dosage_form"]."' and plant_id ='".$_GET["plant_id"]."'  ";
            // $sql = "SELECT distinct * FROM manufacturing_process where dosage_form =  '".$_GET["dosage_form"]."' and plant_id ='".$_GET["plant_id"]."'  order by process_type";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT distinct stage FROM manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and plant_id ='".$_GET["plant_id"]."'  order by stage";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output3 = Array();
                            $sql3 = "SELECT distinct step FROM manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and  stage =  '".$row2["stage"]."'  and plant_id ='".$_GET["plant_id"]."'  order by step";
                           
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $output3[] = $row3;
                                }
                            }
                            $row2["steps"] = $output3;
                            
                            $output2[] = $row2;
                        }
                    }
                    $row["stages"] = $output2;
                     
                    $output[] = $row;
                }
            }
        echo json_encode($output);
         
    }
    else if ($_GET["type"] == "get_stages_by_process_type_pk") {
         $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT distinct process_type FROM packing_manufacturing_process where dosage_form =  '".$_GET["dosage_form"]."' and plant_id ='".$_GET["plant_id"]."'  order by process_type";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT distinct stage FROM packing_manufacturing_process where process_type =  '".$row["process_type"]."' and plant_id ='".$_GET["plant_id"]."'  order by stage";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output3 = Array();
                            $sql3 = "SELECT distinct step FROM packing_manufacturing_process where process_type =  '".$row["process_type"]."' and  stage =  '".$row2["stage"]."'  and plant_id ='".$_GET["plant_id"]."'  order by step";
                           
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $output3[] = $row3;
                                }
                            }
                            $row2["steps"] = $output3;
                            
                            $output2[] = $row2;
                        }
                    }
                    $row["stages"] = $output2;
                     
                    $output[] = $row;
                }
            }
        echo json_encode($output);
         
    }
    else   if ($_GET["type"] == "update_iqpc_stage") {

        $sql = "UPDATE stages_ipqc
        set plant_id = '".$input["plant_id"]."',
           product_type= '".$input["product_type"]."',
           product_name = '".$input["product_name"]."',
           product_code = '".$input["product_code"]."',
           exp_yeild_min = '".$input["exp_yeild_min"]."',
           exp_yeild_max= '".$input["exp_yeild_max"]."',
           stages_test = '".$input["stages_test"]."'
           Where id = '".$_GET["id"]."'";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "saveStage") {
        if ($input["equipments"] == true) {
            $input["equipments"] = "true";
        } else {
            $input["equipments"] = "false";
        }
        
        if ($input["clearance"] == true) {
            $input["clearance"] = "true";
        } else {
            $input["clearance"] = "false";
        }
        
        if ($input["enviornmental"] == true) {
            $input["enviornmental"] = "true";
        } else {
            $input["enviornmental"] = "false";
        }
        
        if ($input["inprocess"] == true) {
            $input["inprocess"] = "true";
        } else {
            $input["inprocess"] = "false";
        }
        
        $istest = "false";
        if ($_GET["inprocess_testing"] == true) {
            $istest = "true";
        } else {
            $istest = "false";
        }
        
        $isweighing = "false";
        if ($_GET["weighing_material"] == true) {
            $isweighing = "true";
        } else {
            $isweighing = "false";
        }
        
        $sql = "INSERT INTO stages (user_no, product_code, stage, instructions, procedures, isequipment, isclerance, isenviornmental, isinprocess, equipments, clearances, stage_allocation, istest, isweighing, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["stage"]."', '".json_encode($input["instructionList"])."', '".json_encode($input["procedureList"])."', '".$input["equipments"]."', '".$input["clearance"]."', '".$input["enviornmental"]."', '".$input["inprocess"]."', '".json_encode($input["equipmentsList"])."', '".json_encode($input["clearanceList"])."', '".$input["stage_allocation"]."', '$istest', '$isweighing', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getStageDetails") {
        $sql = "SELECT b.*, b1.product_code, b1.batch_no, b1.batch_size, b1.mfr_no, b1.bmr_no as std_bmr_no, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr_stages b LEFT JOIN bmr b1 ON b.bmr_no=b1.id LEFT JOIN product p ON b1.product_code=p.product_code WHERE b.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $row["data"] = json_decode($row["data"]);
                
                $details = $row["data"];
                for ($i = 0; $i < count($details); $i++) {
                    $data = $details[$i];
                    if ($data->option == 'equipment') {
                        $row["equipments"] = $data->list;
                    } else if ($data->option == 'procedure') {
                        $row["procedures"] = $data->list;
                    }
                }
                
                $sql1 = "SELECT * FROM inprocess_checks WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND stage='".$row["stage"]."'";
                $result1 = $conn->query($sql);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["checks"] = json_decode($row1["checkpoints"]);
                        $row["ischeck"] = 'yes';
                        break;
                    }
                }
                
                // $row["checks"] = json_decode($row["checks"]);
                
                if ($row["istest"] == "yes") {
                    $sql1 = "SELECT id FROM technical_info WHERE bmr_no='".$row["bmr_no"]."' AND stage='".$row["stage"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $row["intimation"] = "send";
                    } else {
                        $row["intimation"] = "no";
                    }
                }
                
                $row["instructions"] = json_decode($row["instructions"]);
                $row["clearances"] = json_decode($row["clearances"]);
                $row["procedures"] = json_decode($row["procedures"]);
                $row["equipments"] = json_decode($row["equipments"]);
                
                if ($row["isclearance"] == "inprocess") {
                    $sql1 = "SELECT * FROM lineclearance WHERE status='active' AND id='".$row["clearance_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["isclearance"] = "active";
                            $row["clearances"] = json_decode($row1["checkpoints"]);
                            $row["clearance_request_by"] = $row1["request_by"];
                            $row["clearance_request_date"] = $row1["request_date"];
                            $row["clearance_by"] = $row1["entry_by"];
                            $row["clearance_date"] = $row1["entry_date"];
                        }
                    }
                }
                
                if ($row["isweighing"] == "true") {
                    $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."' AND id <".$row["id"]. " AND isweighing='true' ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["prev_yield_qty"] = $row1["yeild_qty"];
                            $row["prev_yield_per"] = $row1["yeild_per"];
                        }
                    } else {
                        $row["prev_yield_qty"] = $row["batch_size"];
                        $row["prev_yield_per"] = 100;
                    }
                }
                
                echo json_encode($row);
                break;
            }
        }
    } else if ($_GET["type"] == "getEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE equipment_name LIKE '%".$_GET["equipment_name"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getOperators") {
        $output = Array();
        $sql = "SELECT * FROM labour WHERE status='approve' AND category='Operator'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getIPqc_testss") {
        $output = Array();
        $sql = "SELECT * FROM stages_ipqc_dtl WHERE  stage='".$_GET["stage"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getInprocessCheckpoints") {
        $output = array();
        $sql = "SELECT * FROM inprocess_checks WHERE status='approve' AND stage='".$_GET["stage"]."' AND product_code='".$_GET["product_code"]."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    }/* else if ($_GET["type"] == "saveStage") {
        
        $istest = "no";
        if ($_GET["inprocess_testing"] == true) {
            $istest = "yes";
        } else {
            $istest = "no";
        }
        
        $sql = "UPDATE batch_stages SET instructions='".json_encode($input["instructions"])."', procedures='".json_encode($input["procedures"])."', details='".json_encode($input)."', status='inprocess', istest='$istest' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "SELECT * FROM batch_stages WHERE status='pending' AND no='".$_GET["no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                $sql1 = "UPDATE batch_formula SET status='inprocess' WHERE id='".$_GET["no"]."'";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }*/ else if ($_GET["type"] == "sendIntimation") {
        $sql = "INSERT INTO technical_info (bmr_no, product_code, stage, entry_by, entry_date) VALUES ('".$_GET["bmr_no"]."', '".$_GET["product_code"]."', '".$_GET["stage"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingStages") {
        $output = array();
        $sql = "SELECT s.*, p.product_name, p.grade FROM stages s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.status='pending' GROUP BY s.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["clearances"] = json_decode($row["clearances"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["instructions"] = json_decode($row["instructions"]);
                $row["procedures"] = json_decode($row["procedures"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateStage") {
        $sql = "UPDATE stages SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getProducts") {
        $output = array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND product_code LIKE '%".$_GET["product_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT s.*, p.product_name, p.grade FROM stages s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.product_code='".$row["product_code"]."' GROUP BY s.id";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["clearances"] = json_decode($row1["clearances"]);
                        $row1["equipments"] = json_decode($row1["equipments"]);
                        $row1["instructions"] = json_decode($row1["instructions"]);
                        $row1["procedures"] = json_decode($row1["procedures"]);
                        $output1[] = $row1;
                    }
                    $row["stages"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTemperature") {
        $sql = "INSERT INTO temperature (user_no, department, bmr_no, stage, temperature, humidity, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', 'Production', '".$_GET["bmr_no"]."', '".$_GET["stage"]."', '".$input["temperature"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTemperatures") {
        $output = array();
        $sql = "SELECT *, DATE(entry_date) as entry_date, TIME(entry_date) as entry_time FROM temperature WHERE user_no='".$_GET["user_no"]."' AND bmr_no='".$_GET["bmr_no"]."' AND stage='".$_GET["stage"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePressure") {
        $sql = "INSERT INTO pressure (user_no, pressure, department, bmr_no, stage, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["pressure"]."', 'Production', '".$_GET["bmr_no"]."', '".$_GET["stage"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPressures") {
        $output = array();
        $sql = "SELECT *, DATE(entry_date) as entry_date, TIME(entry_date) as entry_time FROM pressure WHERE user_no='".$_GET["user_no"]."' AND bmr_no='".$_GET["bmr_no"]."' AND stage='".$_GET["stage"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "callforclearance") {
        $sql = "INSERT INTO lineclearance (user_no, department, activity, bmr_no, checkpoints, request_by, request_date) VALUES ('".$_GET["user_no"]."', 'Production', '".$_GET["stage"]."', '".$_GET["bmr_no"]."', '".json_encode($input)."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $sql = "UPDATE bmr_stages SET isclearance='inprocess', clearances='".json_encode($input)."', clearance_no='".$last_id."' WHERE id='".$_GET["id"]."'";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "get_dosage_types") {
        $output = Array();
        //$sql = "SELECT distinct dosage_form FROM product WHERE plant_id = '".$_GET["plant_id"]."' ORDER BY dosage_form";
       $sql = "Select DISTINCT dosage_form_type as dosage_form from master_fg_types WHERE plant_id = '".$_GET["plant_id"]."'";// ORDER BY dosage_form_type";
        
        //$sql = "Select product_type From product WHERE plant_id = '".$_GET["plant_id"]."' ORDER BY product_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getstage_master") {
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT distinct dosage_form FROM packing_manufacturing_process where  plant_id ='".$_GET["plant_id"]."'  order by dosage_form";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT distinct process_type FROM packing_manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and plant_id ='".$_GET["plant_id"]."'  order by process_type";
                    $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT distinct stage FROM packing_manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and process_type =  '".$row2["process_type"]."' and plant_id ='".$_GET["plant_id"]."'  order by stage";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $output4 = Array();
                                        $sql4 = "SELECT distinct step FROM packing_manufacturing_process where dosage_form =  '".$row["dosage_form"]."' and process_type =  '".$row2["process_type"]."' and stage =  '".$row3["stage"]."'  and plant_id ='".$_GET["plant_id"]."'  order by step";
                                        
                                        $result4 = $conn->query($sql4);
                                        if ($result4->num_rows > 0) {
                                            while ($row4 = $result4->fetch_assoc()) {
                                                $output4[] = $row4;
                                            }
                                        }
                                    $row3["steps"] =    $output4;
                                    $output3[] = $row3;
                                    }
                                }
                                $row2["stages"] = $output3;
                                $output2[] = $row2;
                            }
                        }
                    $row["process_types"] = $output2;
                     
                    $output[] = $row;
                }
                
            }
        echo json_encode($output);
       
    }
     else if ($_GET["type"] == "savestage_master") {
        
        
         $sql="SELECT * FROM packing_manufacturing_process WHERE process_type='".$_GET["process_type"]."'
          AND dosage_form='".$_GET["dosage_form"]."' AND plant_id ='".$_GET["plant_id"]."' ";
          
        $result =$conn->query($sql);
        
       // echo $sql;
       
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Process Type Already Exists. Duplicate Values are not allowed\"}";
        }else{
        $flag = 0;
        for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            
            $sql = "INSERT INTO packing_manufacturing_process (plant_id,user_no, dosage_form, process_type, stage, step, entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '".$data["dosage_form"]."', '".$data["process_type"]."', '".$data["stage"]."', '".$data["step"]."', '".$_GET["emp_id"]."', '$entry_date')";
            if ($conn->query($sql)) {
                $flag = 1;
            } else {
                $flag = 0;
                break;
            }
        }
        if ($flag == 1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }}
    }
    
    


}

$conn->close();
?>