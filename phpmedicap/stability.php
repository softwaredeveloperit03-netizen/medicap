<?php



//     ini_set('display_errors', 1);
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
    
if ($_GET["type"] == "getStabilities") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.dosage_form ,p.grade FROM stability_study s LEFT JOIN product p 
    ON s.product_code=p.product_code WHERE s.plant_id='".$_GET["plant_id"]."' and s.status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                        
                    $output1[] = $row1;
                }
            }
            // $output2 = Array();
            // $sql2 = "SELECT * FROM stability_study_file WHERE stability_study_id='".$row["id"]."'";
            // $result2 = $conn->query($sql2);
            // if ($result2->num_rows > 0) {
            //     while ($row2 = $result2->fetch_assoc()) {
            //         $output2[] = $row2;
            //     }
            // }
            // $row["files"] = $output2;
            $row["batches"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getStabilitiesReview") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.dosage_form ,p.grade FROM stability_study s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' and s.status='".$_GET['status']."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["conditions"] = json_decode($row1["conditions"]);
                       
                    $output1[] = $row1;
                }
            }
            $output2 = Array();
            $sql2 = "SELECT * FROM stability_study_file WHERE stability_study_id='".$row["id"]."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    
                       
                    $output2[] = $row2;
                }
            }
            $row["files"] = $output2;
            $row["batches"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}




else if ($_GET["type"] == "saveFile") {
      $input = $_POST;
      $target_dir = "../../upload/qa/";

        $id = date("YmdHis", $timestamp);
           $file_name = "";
    	if(isset($_FILES["document"]["name"])) {
        	$file_ext = strtolower(end(explode('.',$_FILES['document']['name'])));
        	$file_name = 'JD'.$_GET['plant_id'].'1'.$id.'.'.$file_ext;
        	move_uploaded_file($_FILES["document"]["tmp_name"], $target_dir.$file_name);
    	}
    	
       $sql = "INSERT INTO stability_study_file (stability_study_id,file_name,conditions,intervals,total_intervals,temperature,sample_qty)VALUES 
        ('".$input["stability_study_id"]."','$file_name','".$input["condition"]."','".$input["intervals"]."','".$input["total_intervals"]."','".$input["temperature"]."','".$input["sample_qty"]."')";
    	   if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    	
    	
}
else if ($_GET["type"] == "getStabilityChembers") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' 
    AND equipment_type='STABILITY CHEMBER'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getStabilityProduction") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.stability='Yes' ";
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
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPendingStabilityAllocation") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.dosage_form, p.grade, p.generic_name FROM stability_study s LEFT JOIN product p ON s.product_code=p.product_code
    WHERE s.user_no='".$_GET["user_no"]."' AND s.sampling='done' AND s.charging='done'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            $output1 = Array();
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no=".$row["id"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["batches"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="getStabilityProducts") {
    $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
    $result = $conn->query($sql);
    $output = Array();
    if($result->num_rows > 0){
	    while($row = $result->fetch_assoc()) {
	        $sql1 = "SELECT * FROM specification WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."'";
	        $result1 = $conn->query($sql1);
	        if ($result1->num_rows > 0) {
	            $row["isspecification"] = true;
	            while ($row1 = $result1->fetch_assoc()) {
	                $row["specification_no"] = $row1["specification_no"];
	                $row["sample_qty"] = $row1["sample_qty"];
	                $row["unit"] = $row1["unit"];
	            }
	        } else {
	            $row["isspecification"] = false;
	        }
		    $output[] = $row;
    	}
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveStabilityStudy") {
    
    
  
        
        $sql = "INSERT INTO stability_study (plant_id,user_no,product_code,specification_no, packing, market, batches,
        batch_type,sample_qty,unit, entry_by, entry_date, conditions,status)
        VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$input["product_code"]."','".$input["specification_no"]."',
        '".$input["packing"]."', '".$input["market"]."', '".$input["batch"]."', '".$input["batch_type"]."', '".$input["sample_qty"]."', 
        '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($input["conditions"])."','pending')";
        if ($conn->query($sql)) {
            
            $last_id = $conn->insert_id;
            
            echo "{\"status\":\"success\"}";
            
            $data = $input["batches"];
            
            for ($i = 0; $i < count($data); $i++) {
                $temp = $data[$i];
                $sql1 = "INSERT INTO stability_batch (plant_id,stability_no, batch_no, mfg_date, exp_date, purpose) VALUES ('".$_GET["plant_id"]."','$last_id', '".$temp["batch_no"]."', '".$temp["mfg_date"]."', '".$temp["exp_date"]."', '".$temp["purpose"]."')";
                $conn->query($sql1);
            }
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
  
    
    
    
} else if ($_GET["type"] == "getStabilityTestingReport") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.grade FROM stability_testing s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateStabilityProtocol") {
    $sql = "UPDATE stability_study SET status='".$_GET["action"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}

else if ($_GET["type"] == "getQAOfficers") {
    $output = Array();
   // $sql = "SELECT * FROM employee WHERE department='Quality Assurance' AND designation='Executive'";
    $sql = "SELECT * FROM employee WHERE department='Quality Assurance'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "StabilityChembersChart") {
    $output = Array();
     $sql = "SELECT * FROM stability_chember";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "allocateIntervalSamplingPerson") {
    $sql = "UPDATE stability_study SET conditions='".file_get_contents('php://input')."' WHERE id='".$_GET["stability_no"]."'";
    if ($conn->query($sql)) {
        $condition = $input[$_GET["condition"]];
        $intervals = $condition["interval"];
        $interval = $intervals[$_GET["interval"]];

        $batches = $interval["batches"];
        for ($i = 0; $i < count($batches); $i++) {
            $batch = $batches[$i];

            $sql = "INSERT INTO stability_testing (plant_id, stability_no,product_code,specification_no, batch_no, conditions,intervals, interval_date, sample_qty,
            entry_by, entry_date,unit) VALUES ('".$_GET["plant_id"]."','".$_GET["stability_no"]."','".$_GET["product_code"]."','".$_GET["specification_no"]."', 
            '".$batch["batch_no"]."', '".$_GET["condition"]."','".$_GET["interval"]."', '".$interval['date']."', '".$interval["singal_analysis"]."', 
            '".$_GET["emp_id"]."', '$entry_date','".$_GET["unit"]."')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingStabilitySampling") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.dosage_form, p.grade, p.generic_name FROM stability_study s 
    LEFT JOIN product p ON s.product_code=p.product_code WHERE s.plant_id='".$_GET["plant_id"]."' 
    AND s.sampling='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no=".$row["id"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["conditions"] = json_decode($row1["conditions"]);
                    $output1[] = $row1;
                }
            }
            $row["batches"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInprocessStabilitySampling") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.dosage_form, p.grade, p.generic_name FROM stability_study s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.sampling='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no=".$row["id"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["conditions"] = json_decode($row1["conditions"]);
                    $output1[] = $row1;
                }
            }
            $row["batches"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}


else if ($_GET["type"] == "allocateSamplingPerson") {
    $sql = "UPDATE stability_study SET sampling='inprocess', sampling_person='".$_GET["sampling_person"]."'
    WHERE id='".$_GET["stability_no"]."'";
    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 


else if ($_GET["type"] == "getPendingStabilityCharging") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.dosage_form, p.grade, p.generic_name FROM stability_study s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.sampling='done' AND s.charging='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            
            $output1 = Array();
            $sql1 = "SELECT * FROM stability_batch WHERE stability_no=".$row["id"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["conditions"] = json_decode($row1["conditions"]);
                    $output1[] = $row1;
                }
            }
            $row["batches"] = $output1;
            
            $row["sampling_data"] = json_decode($row["sampling_data"]);
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingWidrawal") {
    $output = Array();
    $sql = "SELECT s.*, s1.product_code, s1.packing, s1.market, s1.sample_qty, s1.batch_type, p.product_name, p.grade FROM stability_testing s LEFT JOIN stability_study s1 ON s.stability_no=s1.id LEFT JOIN product p ON s1.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.status='pending' AND s.testing_person='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["conditions"] = json_decode($row["conditions"]);
            $sql1 = "SELECT * FROM stability_batch WHERE id='".$row["batch_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["conditions"] = json_decode($row1["conditions"]);
                    
                    $conditions = $row1["conditions"];
                    $row["condition"] = $conditions[$row["conditions"]];
                    
                    $row["batch"] = $row1["batch_no"];
                    $row["mfg_date"] = $row1["mfg_date"];
                    $row["exp_date"] = $row1["exp_date"];
                    break;
                }
            }
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveStabilitySampling") {
     $sql = "UPDATE stability_study SET sampling='done', sampling_data='".file_get_contents('php://input')."' WHERE id='".$_GET["stability_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveStabilityCharging") {
    for ($i = 0; $i < count($input); $i++) {
        
        $condition = $input[$i];
        $batches = $condition["batches"];

        for ($j = 0; $j < count($batches); $j++) {
            $batch = $batches[$j];
            $sql = "INSERT INTO stability_chember (plant_id,user_no, chember_no, usages, temp, humidity, entry_by, entry_date) 
            VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$batch["chember"]."', 'OPEN', '".$batch["open_temp"]."', '".$batch["open_humidity"]."', '".$_GET["emp_id"]."', 
            '$entry_date')";
            $conn->query($sql);
            
            $sql = "INSERT INTO stability_chember (plant_id,user_no, chember_no, usages, temp, humidity, entry_by, entry_date)
            VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$batch["chember"]."', 'CLOSE', '".$batch["close_temp"]."', '".$batch["close_humidity"]."', '".$_GET["emp_id"]."', 
            '$entry_date')";
            $conn->query($sql);
        }
    }
    $sql = "UPDATE stability_study SET charging='done', conditions='".file_get_contents('php://input')."' WHERE id='".$_GET["stability_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 

else if ($_GET["type"] == "getStabilityChemberLog") {
    $output = Array();
    $sql = "SELECT * FROM stability_chember WHERE user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "getspecificationNo") {
    $output = Array();
     $sql = "SELECT specification_no FROM specification WHERE spec_type = 'Stability Specification' AND  product_code='".$_GET["product_code"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"] == "saveChemberTemperature") {
    $sql = "INSERT INTO stability_chember (user_no, chember_no, usages, temp, humidity, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["chember_no"]."', 'CHECK', '".$input["temperature"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingStabilityTestings") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.grade, p.generic_name, p.dosage_form FROM stability_testing s
    LEFT JOIN product p ON s.product_code=p.product_code 
    WHERE s.user_no='".$_GET["user_no"]."' AND s.status='pending'";
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                    $row["generic_name"] = $row1["generic_name"];
                    $row["dosage_form"] = $row1["dosage_form"];
                }
            }

            $output1 = Array();
             $sql1 = "SELECT * FROM spec_tests WHERE  release_stability = 'Applicable' AND specification_no= '".$row["specification_no"]."'";
             $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["tests"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getQCOfficers") {
    $output = Array();
    $sql = "SELECT emp_id,firstname,lastname,department FROM employee WHERE department='Quality Control' ";
    // $sql = "SELECT * FROM employee WHERE department='Quality Control' AND designation='Officer'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "allocateTesting") {
    $sql = "UPDATE stability_testing SET person='".$_GET["person"]."', status='inprocess' WHERE id='".$_GET["testing_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 



    else if ($_GET["type"] == "allocateTestingPerson_finish1") {
      
     $sql = "UPDATE stability_testing SET  status='inprocess' WHERE id='".$_GET["testing_no"]."'";
   
     if ($conn->query($sql)) {
            $data = $input['tests'];
            for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];

            if($row1["methodID"]=='') $method = "Yes"  ; else $method = "No" ;

             
            $sql1 = "INSERT INTO testing_tests (plant_id, spec_test_id,testing_no,fg_sampling_no,test,subtest,description,isoutside,person,
            person_alt,ismethod,method_details,outside_testing,bmr_qcsample_tests_id,specification_no)VALUES ('".$_GET["plant_id"]."','".$row1["id"]."',
            '".$_GET["testing_no"]."','".$_GET["sampling_no"]."','".$row1["test"]."','".$row1["subtest"]."','".$row1["description"]."',
            '".$row1["isoutside"]."','".$row1["person"]."','".$row1["alternate_chemist"]."','".$method."','".$row1["methodID"]."',
            '".$row1["lab_name"]."','".$input["bmr_qcsample_tests_id"]."', '".$_GET["specification_no"]."')"; 
            
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }




else if ($_GET["type"] == "getInprocessStabilityTestings") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.grade, p.generic_name, p.dosage_form FROM stability_testing s LEFT JOIN
    product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $output1 = Array();
            $sql1 = "SELECT t.*,s.limits,s.test_type FROM testing_tests t left join spec_tests s ON t.spec_test_id = s.id WHERE  t.testing_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["tests"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveStabilityTesting") {
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);
    // $tests = $input["tests"];
    // for ($i = 0;$i < count($tests); $i++) {
    //     $test = $tests[$i];
    //     if ($test["limit_type"] == "Range") {
    //         if ($test["lower_limit"] <= $test["result"] && $test["result"] <= $test["upper_limit"]) {
    //             $test["observation"] = "pass";
    //         } else {
    //             $test["observation"] = "failed";
    //         }
    //     } else if ($test["limit_type"] == "Not LessThan") {
    //         if ($test["result"] <= $test["lower_limit"]) {
    //             $test["observation"] = "pass";
    //         } else {
    //             $test["observation"] = "failed";
    //         }
    //     } else if ($test["limit_type"] == "Not MoreThan") {
    //         if ($test["upper_limit"] <= $test["result"]) {
    //             $test["observation"] = "pass";
    //         } else {
    //             $test["observation"] = "failed";
    //         }
    //     } else if ($test["limit_type"] == "Compliances") {
    //         if ($test["result"] == 'complies') {
    //             $test["observation"] = "pass";
    //         } else {
    //             $test["observation"] = "failed";
    //         }
    //     }
    //     $tests[$i] = $test;
    //     break;
    // }
    // $input["tests"] = $tests;
    // $sql = "UPDATE stability_testing SET tests='".json_encode($input["tests"])."', testing_by='".$_GET["emp_id"]."', testing_date='$entry_date', 
    // status='active' WHERE id='".$input["id"]."'";
    $tests = $input["tests"];
for ($i = 0; $i < count($tests); $i++) {
    $test = &$tests[$i]; // Use reference to directly modify the array
         
    if ($test["limit_type"] == "Range") {
        if ($test["lower_limit"] <= $test["result"] && $test["result"] <= $test["upper_limit"]) {
            $test["observation"] = "pass";
            // echo ('Range=pass');
        } else {
            $test["observation"] = "failed";
            //   echo ('Range=failed');
        }
    } else if ($test["limit_type"] == "Not LessThan") {
        if ($test["result"] >= $test["upper_limit"]) { // Corrected comparison
            $test["observation"] = "pass";
            //   echo ('Not LessThan=pass');
        } else {
            $test["observation"] = "failed";
            //   echo ('Not LessThan=failed');
        }
    } else if ($test["limit_type"] == "Not MoreThan") {
        if ($test["result"] <= $test["lower_limit"]) { // Corrected comparison
            $test["observation"] = "pass";
            //   echo ('Not MoreThan=pass');
        } else {
            $test["observation"] = "failed";
            //   echo ('Not MoreThan=failed');
        }
    } else if ($test["limit_type"] == "Compliances") {
        if ($test["result"] == 'complies') {
            $test["observation"] = "pass";
            //   echo ('Range=complies');
        } else {
            $test["observation"] = "failed";
            //   echo ('failed=failed');
        }
    }
}

$input["tests"] = $tests;
$sql = "UPDATE stability_testing SET tests='" . json_encode($input["tests"]) . "', testing_by='" . $_GET["emp_id"] . "', testing_date='$entry_date', 
    status='active' WHERE id='" . $input["id"] . "'";

// Execute the SQL query here

    
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingApprovalStabilityTestings") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.grade, p.generic_name, p.dosage_form FROM stability_testing s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["tests"] = json_decode($row["tests"]);

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateStabilityTesting") {
    $tests = $input["tests"];
    for ($i = 0;$i < count($tests); $i++) {
        $test = $tests[$i];
        $test["testing_by"] = $_GET["emp_id"];
        $test["testing_date"] = $entry_date;
        $test["approve_by"] = $_GET["emp_id"];
        $test["approve_date"] = $entry_date;
    }
    $sql = "UPDATE stability_testing SET approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', status='".$_GET["action"]."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {

        $sql1 = "SELECT * FROM stability_study WHERE user_no='".$_GET["user_no"]."' AND id='".$input["stability_no"]."'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $conditions = json_decode($row1["conditions"]);
                $condition = $conditions[$input["conditions"]];

                $intervals = $condition->interval;
                $interval = $intervals[$input["intervals"]];

                $batches = $interval->batches;
                for ($i = 0; $i < count($batches); $i++) {
                    $batch = $batches[$i];
                    if ($batch->batch_no == $input["batch_no"]) {
                        $batch->tests = $tests;
                    }
                    $batches[$i] = $batch;
                }
                $interval->batches = $batches;

                $intervals[$input["intervals"]] = $interval;
                $condition->interval = $intervals;

                $conditions[$input["conditions"]] = $condition;

                $sql2 = "UPDATE stability_study SET conditions='".json_encode($conditions)."' WHERE id='".$input["stability_no"]."'";
                $conn->query($sql2);
            }
        }

        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getStabilityTestingLog") {
    $output = Array();
    $sql = "SELECT s.*, p.product_name, p.grade, p.generic_name, p.dosage_form FROM stability_testing s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.user_no='".$_GET["user_no"]."' AND s.status !='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["tests"] = json_decode($row["tests"]);

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getSummaryReport") {
    $output = Array();
    $sql = "SELECT s.*,t.tests, p.product_name, p.grade, p.generic_name, p.dosage_form FROM stability_study s
    LEFT JOIN product p ON s.product_code=p.product_code
     LEFT JOIN stability_testing t ON s.id=t.stability_no WHERE s.plant_id='".$_GET["plant_id"]."' AND t.status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

         
            $row["tests"] = json_decode($row["tests"]);

            $row["conditions"] = json_decode($row["conditions"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
    
    
    else if($_GET["type"] == "summaryReportPdf") {



$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");

$html.='

 <table border="1" style="width=540px">
  <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:center">STABILITY DATA SHEET FOR REAL TIME / INTERMEDIATE STUDY</h4>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:center">STABILITY SUMMARY DATA</h4>
</td>
 </tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Product Name:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Protocol No:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Label Claim:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Specification No.:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Finished Product Code:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Batch No.:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Batch No. of API:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Batch Size:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Market:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Mfg.:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">API Manufacturer:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Exp:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Condition:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Charging Date:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Pack Style:</td>
    <td style="line-height:20px;width:420px;text-align:left;"></td>
  
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Purpose of Study:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Manufactured by:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Container Closure Details:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;"></td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
</table>
<div></div>

<table border="1" style="width=540px">
<tr>
    <td style="line-height:20px;width:77px;text-align:center;">Test</td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:386px;text-align:center;">Station</td>
</tr>
<tr>
    <td style="line-height:20px;width:77px;text-align:center;">Parameters</td>
    <td style="line-height:20px;width:77px;text-align:center;">Specification</td>
     <td style="line-height:20px;width:77px;text-align:center;">Initial</td>
    <td style="line-height:20px;width:77px;text-align:center;">1 months</td>
     <td style="line-height:20px;width:77px;text-align:center;">2 months</td>
    <td style="line-height:20px;width:77px;text-align:center;">3 months</td>
    <td style="line-height:20px;width:77px;text-align:center;">6 months</td>

</tr>
<tr>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Date of Withdrawal</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Date of analysis</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Remarks</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Conclusions</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
</tr>
</table>
<div></div>

<table style="width=540px;border:none;">
<tr style="width=540px">
    <td style="line-height:25px;width: 140px;border:none;text-align:left;">Abbreviations:</td>
    <td style="line-height:25px;width: 400px;border:none;text-align:left;"></td>
</tr>
<tr style="width=540px">
    <td style="line-height:25px;width: 140px;border:none;text-align:left;">Remarks:</td>
    <td style="line-height:25px;width: 400px;border:none;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 60px;border:none;text-align:center;"></td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;">Compiled By (Assistant Manager QC)</td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;">Reviewed By (Manager QC)</td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;">Approved By (Manager QA)</td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;">Authorised By (V/P Quality)</td>
</tr>
<tr>
    <td style="line-height:20px;width: 40px;border:none;text-align:center;">Sign</td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;"></td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;"></td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;"></td>
    <td style="line-height:20px;width: 120px;border:none;text-align:center;"></td>
</tr>

</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 65px;text-align:center;"></td>
    <td style="line-height:30px;width: 95px;text-align:center;">Prepared By QC </td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Approved By</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Authorised By</td>

</tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Name</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>User Id</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Signature</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Date/Time</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('summaryReportPdf.pdf', 'I');
    

}

    else if($_GET["type"] == "summaryReportPdf2") {



$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");

$html.='

 <table border="1" style="width=540px">
  <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:center">STABILITY DATA SHEET FOR ACCELERATED STUDY</h4>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:center">STABILITY SUMMARY DATA</h4>
</td>
 </tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Product Name:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Protocol No:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Label Claim:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Specification No.:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Finished Product Code:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Batch No.:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Batch No. of API:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Batch Size:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Market:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Mfg.:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">API Manufacturer:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Exp:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Condition:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Charging Date:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Pack Style:</td>
    <td style="line-height:20px;width:420px;text-align:left;"></td>
  
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Purpose of Study:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;">Manufactured by:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:120px;text-align:left;">Container Closure Details:</td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
    <td style="line-height:20px;width:120px;text-align:left;"></td>
    <td style="line-height:20px;width:150px;text-align:left;"></td>
</tr>
</table>
<div></div>

<table border="1" style="width=540px">
<tr>
    <td style="line-height:20px;width:77px;text-align:center;">Test</td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:386px;text-align:center;">Station</td>
</tr>
<tr>
    <td style="line-height:20px;width:77px;text-align:center;">Parameters</td>
    <td style="line-height:20px;width:77px;text-align:center;">Specification</td>
     <td style="line-height:20px;width:77px;text-align:center;">Initial</td>
   
    <td style="line-height:20px;width:154px;text-align:center;">3 months</td>
    <td style="line-height:20px;width:154px;text-align:center;">6 months</td>

</tr>
<tr>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:77px;text-align:left;"></td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Date of Withdrawal</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
     <td style="line-height:20px;width:154px;text-align:left;"></td>
   
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Date of analysis</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
     <td style="line-height:20px;width:154px;text-align:left;"></td>
  
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Remarks</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:154px;text-align:left;">Conclusions</td>
     <td style="line-height:20px;width:77px;text-align:left;"></td>
    <td style="line-height:20px;width:154px;text-align:left;"></td>
     <td style="line-height:20px;width:154px;text-align:left;"></td>
   
</tr>
</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 65px;text-align:center;"></td>
    <td style="line-height:30px;width: 95px;text-align:center;">Prepared By QC </td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Approved By</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Authorised By</td>

</tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Name</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>User Id</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Signature</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Date/Time</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('summaryReportPdf2.pdf', 'I');
    

}


 else if($_GET["type"] == "schedulePdf") {



$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");

$html.='

 <table border="1" style="width=540px">
  <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h3 style="text-align:center">Withdrawal Schedule</h3>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:left">Storage Condition: 30°C/75 % RH [INTERMEDIATE / LONG TERM]</h4>
</td>
 </tr>
<tr>
    <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;">Station (Month)</td>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;">Batch no.</td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;">Scheduled Withdrawal date</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn on</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Qty Withdrawn</td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;">Remaining Qty</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn by</td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">Date of Report Sign/Date</td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;">Remark (if any)</td>
</tr>
<tr>
    <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;"   rowspan="3"></td>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
</table>

<div></div>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 65px;text-align:center;"></td>
    <td style="line-height:30px;width: 95px;text-align:center;">Prepared By QC </td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Approved By</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Authorised By</td>

</tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Name</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>User Id</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Signature</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Date/Time</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('downloadjoblog.pdf', 'I');
    

}

 else if($_GET["type"] == "schedulePdf2") {



$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");

$html.='

 <table border="1" style="width=540px">
  <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h3 style="text-align:center">Withdrawal Schedule</h3>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:left">Storage Condition: 25°C/60 % RH [LONG TERM]</h4>
</td>
 </tr>
<tr>
    <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;">Station (Month)</td>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;">Batch no.</td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;">Scheduled Withdrawal date</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn on</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Qty Withdrawn</td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;">Remaining Qty</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn by</td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">Date of Report Sign/Date</td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;">Remark (if any)</td>
</tr>
<tr>
    <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;"   rowspan="3"></td>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
</table>

<div></div>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 65px;text-align:center;"></td>
    <td style="line-height:30px;width: 95px;text-align:center;">Prepared By QC </td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Approved By</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Authorised By</td>

</tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Name</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>User Id</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Signature</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Date/Time</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('downloadjoblog.pdf', 'I');
    

}

 else if($_GET["type"] == "schedulePdf3") {



$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");

$html.='

 <table border="1" style="width=540px">
  <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h3 style="text-align:center">Withdrawal Schedule</h3>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:left">Storage Condition: Cold Storage 5+-3°C[LONG TERM]</h4>
</td>
 </tr>
<tr>
    <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;">Station (Month)</td>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;">Batch no.</td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;">Scheduled Withdrawal date</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn on</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Qty Withdrawn</td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;">Remaining Qty</td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn by</td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">Date of Report Sign/Date</td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;">Remark (if any)</td>
</tr>
<tr>
    <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;"   rowspan="3"></td>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 65px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
    <td style="line-height:20px;width: 48px;border-bottom:none;text-align:center;"></td>
</tr>
</table>

<div></div>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 65px;text-align:center;"></td>
    <td style="line-height:30px;width: 95px;text-align:center;">Prepared By QC </td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Approved By</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Authorised By</td>

</tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Name</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>User Id</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Signature</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Date/Time</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('downloadjoblog.pdf', 'I');
    

}

 else if($_GET["type"] == "SampleWithdrawlPdf") {

$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("./pdfimp2.php");
$html= "";
$html.=' 

<table border="1" style="width=540px">
  <tr>
 <td style="width:540px;line-height:25px;"><h4 style="text-align:center">STABILITY SAMPLE INWARD/WITHDRAWAL REGISTER</h4>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px;line-height:25px;"><h4 style="text-align:center">INWARD DETAILS</h4>
</td>
 </tr>
 <tr>
    <td style="line-height:20px;width:270px;text-align:left;font-weight:bold;">Name of Product:</td>
    <td style="line-height:20px;width:270px;text-align:left;font-weight:bold;">Reference Protocol no.:</td>
</tr>
<tr>
    <td style="line-height:20px;width:75px;text-align:center;">Batch No.</td>
    <td style="line-height:20px;width:127px;text-align:center;">Mfg. Date</td>
    <td style="line-height:20px;width:137px;text-align:center;">Exp. Date</td>
    <td style="line-height:20px;width:67px;text-align:center;">Batch size</td>
    <td style="line-height:20px;width:134px;text-align:center;">Pack style</td>
</tr>
<tr>
    <td style="line-height:20px;width:75px;text-align:center;"></td>
    <td style="line-height:20px;width:127px;text-align:center;"></td>
    <td style="line-height:20px;width:137px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:134px;text-align:center;"></td>
</tr>

<tr>
    <td style="line-height:20px;width:75px;text-align:center;">Stability Condition</td>
    <td style="line-height:20px;width:60px;text-align:center;">Qty. of Sample charged</td>
    <td style="line-height:20px;width:67px;text-align:center;">Charged in Chamber ID. No.</td>
    <td style="line-height:20px;width:67px;text-align:center;">location in chamber</td>
    <td style="line-height:20px;width:70px;text-align:center;">Total qty. of Sample charged</td>
    <td style="line-height:20px;width:67px;text-align:center;">Sample charged on</td>
    <td style="line-height:20px;width:67px;text-align:center;">Initial analysis report date [0 M]</td>
    <td style="line-height:20px;width:67px;text-align:center;">Entered by Sign/date</td>
</tr>
<tr>
    <td style="line-height:20px;width:75px;text-align:center;">40 C/75% RH</td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:70px;text-align:center;" rowspan="4"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:75px;text-align:center;">25 C/65% RH</td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:75px;text-align:center;">30 C/75% RH</td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:75px;text-align:center;">25 C/60% RH</td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
</tr>
</table>


<div></div>

 <table border="1" style="width=540px">
  <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:center">WITHDRAWAL SCHEDULE</h4>
</td>
 </tr>
 <tr style="width:540px">
 <td style="width:540px; line-height:25px;"><h4 style="text-align:left">Storage Condition:40 C/75 % RH [ACCELERATED]</h4>
</td>
 </tr>
<tr>
    <td style="line-height:20px;width:50px;text-align:center;">Station (Month)</td>
    <td style="line-height:20px;width:58px;text-align:center;">Batch no.</td>
    <td style="line-height:20px;width:67px;text-align:center;">Scheduled Withdrawal date</td>
    <td style="line-height:20px;width:64px;text-align:center;">Withdrawn on</td>
    <td style="line-height:20px;width:64px;text-align:center;">Qty Withdrawn</td>
    <td style="line-height:20px;width:65px;text-align:center;">Remaining Qty</td>
    <td style="line-height:20px;width:64px;text-align:center;">Withdrawn by</td>
    <td style="line-height:20px;width:60px;text-align:center;">Date of Report Sign/Date</td>
    <td style="line-height:20px;width:48px;text-align:center;">Remark (if any)</td>
</tr>
<tr>
    <td style="line-height:20px;width:50px;text-align:center;"rowspan="3"></td>
    <td style="line-height:20px;width:58px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:65px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:48px;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:58px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:65px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:48px;text-align:center;"></td>
</tr>
<tr>
    <td style="line-height:20px;width:58px;text-align:center;"></td>
    <td style="line-height:20px;width:67px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:65px;text-align:center;"></td>
    <td style="line-height:20px;width:64px;text-align:center;"></td>
    <td style="line-height:20px;width:60px;text-align:center;"></td>
    <td style="line-height:20px;width:48px;text-align:center;"></td>
</tr>
</table>
<div></div>
<table border="1">
<tr>
    <td style="line-height:30px;width: 65px;text-align:center;"></td>
    <td style="line-height:30px;width: 95px;text-align:center;">Prepared By QC </td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Approved By</td>
    <td style="line-height:30px;width: 95px;text-align:center;">Authorised By</td>

</tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Name</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>User Id</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Signature</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

     <tr style="line-height:30px;width: 65px;text-align:center;">
        <td>Date/Time</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('SampleWithdrawlPdf.pdf', 'I');
}

    else if($_GET["type"] == "downloadStabilityTestingLog") {



$_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("./pdfimp2.php");

$html.='

<table style="width: 785px;border:none;">
      <tr>
        <td style="height: 30px; text-align:center;border:none;"><h4>STABILITY SAMPLE ANALYSIS REGISTER</h4></td>
    </tr>
</table>
<div></div>
<table style="width: 785px;" border="1">
    <tr>
        <td style="width: 25px;text-align:center;height:20px;">Sr.no</td>
        <td style="width: 55px;text-align:center;height:20px;">Name of Product</td>
        <td style="width: 40px;text-align:center;height:20px;">Batch No.</td>
        <td style="width: 35px;text-align:center;height:20px;">Pack Style</td>
        <td style="width: 58px;text-align:center;height:20px;">Storage Condition</td>
        <td style="width: 45px;text-align:center;height:20px;">Station</td>
        <td style="width: 35px;text-align:center;height:20px;">Mfg Date</td>
        <td style="width: 35px;text-align:center;height:20px;">Exp Date</td>
        <td style="width: 50px;text-align:center;height:20px;">Quant. Withdr.</td>
        <td style="width: 45px;text-align:center;height:20px;">A.R.No</td>
        <td style="width: 48px;text-align:center;height:20px;">Micro Applic. (Y/N)</td>
        <td style="width: 60px;text-align:center;height:20px;">Micro sample submitted by(sign / date)</td>
        <td style="width: 50px;text-align:center;height:20px;">Entered by(sign/date)</td>
        <td style="width: 60px;text-align:center;height:20px;">Name of Analyst</td>
        <td style="width: 35px;text-align:center;height:20px;">Date of Report</td>
        <td style="width: 60px;text-align:center;height:20px;">Remaining qty destroyed by / date</td>
        <td style="width: 50px;text-align:center;height:20px;">Section head (sign / date)</td>
    </tr>
    <tr>
    <td  style="width: 25px;text-align:left;height:20px;"></td>
    <td style="width: 55px;text-align:left;height:20px;"></td>
    <td style="width: 40px;text-align:left;height:20px;"></td>
    <td style="width: 35px;text-align:left;height:20px;"></td>
    <td style="width: 58px;text-align:left;height:20px;"></td>
    <td style="width: 45px;text-align:left;height:20px;"></td>
    <td style="width: 35px;text-align:left;height:20px;"></td>
    <td style="width: 35px;text-align:left;height:20px;"></td>
    <td style="width: 50px;text-align:left;height:20px;"></td>
    <td style="width: 45px;text-align:left;height:20px;"></td>
    <td style="width: 48px;text-align:left;height:20px;"></td>
    <td style="width: 60px;text-align:left;height:20px;"></td>
    <td style="width: 50px;text-align:left;height:20px;"></td>
    <td style="width: 60px;text-align:left;height:20px;"></td>
    <td style="width: 35px;text-align:left;height:20px;"></td>
    <td style="width: 60px;text-align:left;height:20px;"></td>
    <td style="width: 50px;text-align:left;height:20px;"></td>
    </tr>
</table>

<div></div>

<table border="1" style="width: 785px;">
<tr>
    <td style="line-height:25px;width: 85px;text-align:center;"></td>
    <td style="line-height:25px;width: 140px;text-align:center;">Prepared By QC </td>
    <td style="line-height:25px;width: 140px;text-align:center;">Reviewed By QC</td>
    <td style="line-height:25px;width: 140px;text-align:center;">Reviewed By QA</td>
    <td style="line-height:25px;width: 140px;text-align:center;">Approved By</td>
    <td style="line-height:25px;width: 140px;text-align:center;">Authorised By</td>

</tr>
    <tr>
        <td style="line-height:25px;width: 85px;text-align:center;">Name</td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
    </tr>
     <tr>
        <td style="line-height:25px;width: 85px;text-align:center;">User Id</td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
    </tr>
    <tr>
        <td style="line-height:25px;width: 85px;text-align:center;">Signature</td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
    </tr>

     <tr>
        <td style="line-height:25px;width: 85px;text-align:center;">Date/Time</td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
        <td style="line-height:25px;width: 140px;text-align:center;"></td>
    </tr>
</table>

';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('summaryReportPdf2.pdf', 'I');
    

}


    } 
    else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>