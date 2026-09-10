<?php
//   ini_set('display_errors', 1);
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "getProductsByDosage") {
		$output = array();
		$sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND product_type='".$_GET["product_type"]."'";
	 
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		echo json_encode($output);
	} 
	else if ($_GET["type"] == "export_grade") {
	    
	    	$output = array();
		$sql = "SELECT Grade ,plant_id FROM grade WHERE grade NOT LIKE '%IP%'";
	 
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		echo json_encode($output);
	    
	}
 
	else if ($_GET["type"] == "saveMFR") {
	     
	    $sql = "INSERT INTO unitformula (plant_id,master_formula_type,product_type,user_no,bom_type, product_code,input_qty, yield_qty, dispatch_qty, unit,
	    raw_materials, packing_materials, stages, min_per, max_per, min_output_qty, max_output_qty, revison_history, entry_by, 
	    entry_date, status,drum_type,product_id,batch_size,pm_batch_size,pm_pack_size,pm_pack_unit) VALUES ('".$_GET["plant_id"]."',
	    '".$input["master_formula_type"]."','".$input["product_type"]."','".$_GET["user_no"]."','".$input["bom_type"]."','".$input["product_code"]."',
	    '".$input["input_qty"]."','".$input["yield_qty"]."','".$input["dispatch_qty"]."', 'Kg','".json_encode($input["raw_materials"])."',
	    '".json_encode($input["packing_materials"])."', '".json_encode($input["stages"])."', '".$input["min_per"]."', '".$input["max_per"]."',
	    '".$input["min_output_qty"]."', '".$input["max_output_qty"]."',
	    '".mysqli_real_escape_string($conn, json_encode(isset($input["revisionList"]) ? $input["revisionList"] : (isset($input["revison_history"]) ? $input["revison_history"] : array())))."',
	    '".$_GET["emp_id"]."','$entry_date', 
	    'pending','".$input["drum_type"]."','".$input["product_id"]."','".$input["batch_size"]."'
	    ,'".$input["pm_batch_size"]."','".$input["pm_pack_size"]."','".$input["pm_pack_unit"]."')";
	    
	    if ($conn->query($sql)) {
	          $unitformula_id = $conn->insert_id;    
	       // below table  is only for auto purchase 
	               $json_obj = json_encode($input["raw_materials"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql1 = "INSERT INTO unitformula_raw_materials (plant_id, material_subtype, material_name, material_Code, 
        grade,qty,overages_per, qty_overages_qty,percent_qty,yeild_contribution,role,split_into_lots,unit,assay_compensation,overage_qty,total_qty,unitformula_id)
       VALUES ('".$_GET["plant_id"]."','".$values["material_subtype"]."','".$values["material_name"]."',
       '".$values["material_Code"]."','".$values["grade"]."','".$values["qty"]."','".$values["overages_per"]."',
       '".$values["qty_overages_qty"]."','".$values["percent_qty"]."','".$values["yeild_contribution"]."','".$values["role"]."'
       ,'".$values["split_into_lots"]."','".$values["unit"]."','".$values["assay_compensation"]."','".$values["overage_qty"]."','".$values["total_qty"]."','".$unitformula_id."')";
        $conn->query($sql1);
                    
                }
                
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
 	    else if ($_GET["type"] == "deleteMFR") {
        $sql = "DELETE FROM unitformula  WHERE id='".$_GET["id"]."'";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveMFR_Formulation") {
        
        
        $instructions = isset($input["ProcedureList"]) ? $input["ProcedureList"] : '';
        $precuation = isset($input["InstructionList"]) ? $input["InstructionList"] : '';
        $equpment_list = isset($input["EquipmentList"]) ? $input["EquipmentList"] : '';
        
        $instructions_esc = $conn->real_escape_string(json_encode($instructions));
        $precuation_esc = $conn->real_escape_string(json_encode($precuation));
        $equpment_list_esc = $conn->real_escape_string(json_encode($equpment_list));
        
        $sql = "INSERT INTO `unitformula`(
            `plant_id`, `product_type`, `product_code`, `formula_for`, 
            `average_weight`, `average_weight_unit`, `mfr_description`, 
            `theoritical_yield`, `min_per`, `max_per`, `revison_history`, 
            `status`, `entry_by`, `entry_date`, `instructions`, 
            `precuation`, `equpment_list`
        ) VALUES (
            '".mysqli_real_escape_string($conn, $_GET["plant_id"])."', 
            '".mysqli_real_escape_string($conn, $input["product_type"])."', 
            '".mysqli_real_escape_string($conn, $input["product_code"])."', 
            '".mysqli_real_escape_string($conn, $input["formula_for"])."', 
            '".mysqli_real_escape_string($conn, $input["average_weight"])."', 
            '".mysqli_real_escape_string($conn, $input["average_weight_unit"])."', 
            '".mysqli_real_escape_string($conn, $input["mfr_description"])."', 
            '".mysqli_real_escape_string($conn, $input["theoritical_yield"])."', 
            '".mysqli_real_escape_string($conn, $input["min_per"])."', 
            '".mysqli_real_escape_string($conn, $input["max_per"])."', 
            '".mysqli_real_escape_string($conn, json_encode($input["revison_history"]))."',
            'Pending', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."',
            '$entry_date', '$instructions_esc', '$precuation_esc', '$equpment_list_esc'
        )";

    
        if ($conn->query($sql)) {
            
            // Get the MFR number generated by the trigger
            $unitformula_id = $conn->insert_id;
            $res = $conn->query("SELECT mfr_no FROM unitformula WHERE id = '".$unitformula_id."'");
            $row = $res->fetch_assoc();
            $mfr_no = $row["mfr_no"];
    
            // Insert raw materials linked to mfr_no
            if (!empty($input["raw_materials"]) && is_array($input["raw_materials"])) {
                foreach ($input["raw_materials"] as $mat) {
                    
                    $mat_sql = "INSERT INTO `unitFormulaMaterial`(`plant_id`, `mfr_no`, `material_type`, `material_subtype`, `material_code`, `qty`, `unit`, 
                    `overages_per`, `total_qty`, `entryOn`,`material_name`) VALUES ('".mysqli_real_escape_string($conn, $_GET["plant_id"])."', 
                    '".mysqli_real_escape_string($conn, $mfr_no)."',
                    '".mysqli_real_escape_string($conn, $mat["material_type"])."',
                    '".mysqli_real_escape_string($conn, $mat["material_subtype"])."',
                    '".mysqli_real_escape_string($conn, $mat["material_code"])."',
                    '".mysqli_real_escape_string($conn, $mat["qty"])."',
                    '".mysqli_real_escape_string($conn, $mat["unit"])."',
                    '".mysqli_real_escape_string($conn, $mat["overages_per"])."',
                    '".mysqli_real_escape_string($conn, $mat["total_qty"])."','$entry_date',
                    '".mysqli_real_escape_string($conn, $mat["material_name"])."')";
                    
                    $conn->query($mat_sql);
                }
            }
    
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
        	else if ($_GET["type"] == "saveMFR_FormulationZuma") {
	  
	    $sql = "INSERT INTO unitformula (plant_id,master_formula_type,product_type,user_no,bom_type, product_code,input_qty, yield_qty, dispatch_qty, unit,
                                	    raw_materials, packing_materials,consumeableMaterial, stages,theoritical_yield, min_per, max_per, min_output_qty, max_output_qty, entry_by, 
                                	    entry_date, status,drum_type,product_id,batch_size,pm_batch_size,pm_pack_size,pm_pack_unit,average_weight,average_weight_unit,formula_for,
                                	    batch_size_unit,batch_size_uom,dosage_form,dose_unit_type,mfr_description,revison_history,dose_pack,strength,strength_unit,
                                	    primary_pm_batch_size,primary_pm_list,bom_batch_size,bom_batch_size_unit)
	    
	        VALUES ('".$_GET["plant_id"]."','".$input["master_formula_type"]."','".$input["dosage_form"]."','".$_GET["user_no"]."','".$input["bom_type"]."','".$input["product_code"]."',
                            	    '".$input["input_qty"]."','".$input["yield_qty"]."','".$input["dispatch_qty"]."', '".$input["average_weight_unit"]."','".json_encode($input["raw_materials"])."',
                            	    '".json_encode($input["packing_materials"])."','".json_encode($input["consumeableMaterial"])."', '".json_encode($input["stages"])."', '".$input["theoritical_yield"]."', '".$input["min_per"]."', '".$input["max_per"]."',
                            	    '".$input["min_output_qty"]."', '".$input["max_output_qty"]."','".$_GET["emp_id"]."','$entry_date', 
                            	    'pending','".$input["drum_type"]."','".$input["product_id"]."','".$input["average_weight"]."'
                            	    ,'".$input["pm_batch_size"]."','".$input["pm_pack_size"]."','".$input["pm_pack_unit"]."','".$input["average_weight"]."',
                            	    '".$input["average_weight_unit"]."','".$input["formula_for"]."', '".$input["batch_size_unit"]."','".$input["batch_size_uom"]."',
                            	    '".$input["dosage_form"]."','".$input["dose_unit_type"]."','".$input["mfr_description"]."','".json_encode($input["revisionList"])."',
                            	    '".$input["dose_pack"]."','".$input["strength"]."','".$input["strength_unit"]."','".$input["pm_batch_size"]."',
                            	    '".json_encode($input["packing_materials"])."','".$input["BatchSize"]."','".$input["BatchSize_unit"]."')";
	   
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
		else if ($_GET["type"] == "save_packing_materialZuma") {
 
         
        $sql="Insert into unitformula_pm_dtl(unit_formula_id,market_type,country_specific,country_name,
        packing_type,batch_size,pack_size,unit,entry_by,packing_instruction,product_brand_name) values(
        '".$input["unit_formula_id"]."', '".$input["market_type"]."','".$input["country_specific"]."','".$input["country_name"]."',
        '".$input["packing_type"]."', '".$input["batch_size"]."','".$input["pack_size"]."','".$input["unit"]."',
        '".$_GET["emp_id"]."','".$input["packing_instruction"]."','".$input["brand_name"]."')";   
       
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $r_materials = $input["packing_materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "INSERT INTO  unitformula_packing_materials(for_brand_product,unit_formula_dtl_id,material_subtype,material_name,qty,unit_name,overages,role,total_qty,
                material_code,grade)
                values('".$input["brand_name"]."','".$last_id."','".$material["material_subtype"]."','".$material["material_name"]."',
                '".$material["qty"]."', '".$material["unit_name"]."','".$material["overages"]."','".$material["role"]."','".$material["total_qty"]."',
                '".$material["material_code"]."','".$material["grade"]."')";
                
                $conn->query($sql);
                
            }
            
            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "saveTentitiveFormula") {
    
 
        // Insert into main table
        $sql = "INSERT INTO `tentitiveUnitformula`(`plant_id`,`enquiryProductId`, `product_type`,`category`,`dosage_form`,`dosage_type`, `product_name`, `formula_for`, `average_weight`,`average_weight_unit`, `mfr_description`, 
        `theoritical_yield`, `min_per`,`max_per`, `revison_history`, `status`, `entry_by`, `entry_date`,`product_code`) VALUES ('".mysqli_real_escape_string($conn, $_GET["plant_id"])."', 
        '".mysqli_real_escape_string($conn, $input["enquiryProductId"])."', 
        '".mysqli_real_escape_string($conn, $input["product_type"])."', 
        '".mysqli_real_escape_string($conn, $input["category"])."', 
        '".mysqli_real_escape_string($conn, $input["dosage_form"])."', 
        '".mysqli_real_escape_string($conn, $input["dosage_type"])."', 
        '".mysqli_real_escape_string($conn, $input["product_name"])."', 
        '".mysqli_real_escape_string($conn, $input["formula_for"])."', 
        '".mysqli_real_escape_string($conn, $input["average_weight"])."', 
        '".mysqli_real_escape_string($conn, $input["average_weight_unit"])."', 
        '".mysqli_real_escape_string($conn, $input["mfr_description"])."', 
        '".mysqli_real_escape_string($conn, $input["theoritical_yield"])."', 
        '".mysqli_real_escape_string($conn, $input["min_per"])."', 
        '".mysqli_real_escape_string($conn, $input["max_per"])."', 
        '".mysqli_real_escape_string($conn, json_encode($input["revison_history"]))."',
        'Pending', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."','$entry_date',
          '".mysqli_real_escape_string($conn, $input["product_code"])."')";
    
        if ($conn->query($sql)) {
            
            // Get the MFR number generated by the trigger
            $unitformula_id = $conn->insert_id;
            $res = $conn->query("SELECT mfr_no FROM tentitiveUnitformula WHERE id = '".$unitformula_id."'");
            $row = $res->fetch_assoc();
            $mfr_no = $row["mfr_no"];
    
            // Insert raw materials linked to mfr_no
            if (!empty($input["raw_materials"]) && is_array($input["raw_materials"])) {
                foreach ($input["raw_materials"] as $mat) {
                    
                    $mat_sql = "INSERT INTO `tentitiveUnitFormulaMaterial`(`plant_id`, `mfr_no`, `material_type`, `material_subtype`, `material_code`, `qty`, `unit`, 
                    `overages_per`, `total_qty`,`contriToYield`,`formulaPer`, `entryOn`) VALUES ('".mysqli_real_escape_string($conn, $_GET["plant_id"])."', 
                    '".mysqli_real_escape_string($conn, $mfr_no)."',
                    '".mysqli_real_escape_string($conn, $mat["material_type"])."',
                    '".mysqli_real_escape_string($conn, $mat["material_subtype"])."',
                    '".mysqli_real_escape_string($conn, $mat["material_code"])."',
                    '".mysqli_real_escape_string($conn, $mat["qty"])."',
                    '".mysqli_real_escape_string($conn, $mat["unit"])."',
                    '".mysqli_real_escape_string($conn, $mat["overages_per"])."',
                    '".mysqli_real_escape_string($conn, $mat["total_qty"])."',
                    '".mysqli_real_escape_string($conn, $mat["contriToYield"])."',
                    '".mysqli_real_escape_string($conn, $mat["formulaPer"])."',
                    '$entry_date')";
                    
                    $conn->query($mat_sql);
                }
            }
            
            $sql1 = "UPDATE `enquiryProduct` SET  `isTentitiveFormulaPrepared`= 'YES' , status = 'Tentitive_Formula_Created' WHERE id = '".$input["enquiryProductId"]."'";
            $conn->query($sql1);
    
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
	else if ($_GET["type"] == "save_packing_material") {
	    
       /* $sql = "UPDATE unitformula SET packing_materials='".json_encode($input["packing_materials"])."',
        pm_batch_size = '".$input["pm_batch_size"]."',
        pm_pack_size = '".$input["pm_pack_size"]."',
        pm_pack_unit = '".$input["pm_pack_unit"]."'
        WHERE id='".$_GET["id"]."'";*/
        // $pk_id='".$_GET["id"]."';
         
        $sql="Insert into unitformula_pm_dtl(unit_formula_id,market_type,country_specific,country_name,
        packing_type,batch_size,pack_size,unit,entry_by,packing_instruction) values(
        '".$input["unit_formula_id"]."', '".$input["market_type"]."','".$input["country_specific"]."','".$input["country_name"]."',
        '".$input["packing_type"]."', '".$input["batch_size"]."','".$input["pack_size"]."','".$input["unit"]."',
        '".$_GET["emp_id"]."','".$input["packing_instruction"]."')";   
       
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $r_materials = $input["packing_materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "INSERT INTO  unitformula_packing_materials(unit_formula_dtl_id,material_subtype,material_name,qty,unit_name,overages,role,total_qty,
                material_code,grade)
                values('".$last_id."','".$material["material_subtype"]."','".$material["material_name"]."',
                '".$material["qty"]."', '".$material["unit_name"]."','".$material["overages"]."','".$material["role"]."','".$material["total_qty"]."',
                '".$material["material_code"]."','".$material["grade"]."')";
                
                $conn->query($sql);
                
            }
            
            
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
  	
	else if ($_GET["type"] == "savePlant5BOM") {
	    $temp = array();
	    $temp['product_code'] = $input["finish_product_code"];
	    $temp["qty"] = $input["qty"];
	    $temp["unit"] = $input["unit"];
	    $sql = "INSERT INTO unitformula (user_no, product_code, bom_for, input_qty, yield_qty, dispatch_qty, unit, finish_materials, raw_materials, packing_materials, stages, min_per, max_per, min_output_qty, max_output_qty, entry_by, entry_date, status) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["bom_for"]."','".$input["input_qty"]."','".$input["yield_qty"]."','".$input["dispatch_qty"]."', 'Kg','".json_encode($temp)."','".json_encode($input["raw_materials"])."','".json_encode($input["packing_materials"])."', '".json_encode($input["stages"])."', '".$input["min_per"]."', '".$input["max_per"]."', '".$input["min_output_qty"]."', '".$input["max_output_qty"]."','".$_GET["emp_id"]."','$entry_date', 'approve')";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	else if ($_GET["type"] == "getMFRLog") {
	    $output = array();
	    //$sql = "SELECT u.*, p.product_name, p.grade, p.product_type FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code GROUP BY p.product_code";
	    //$sql = "SELECT u.*, p.product_name, p.grade, p.product_type FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code order by 1 desc";
	    $sql = "SELECT u.*, p.product_name, p.grade, p.product_type FROM unitformula u LEFT JOIN product p ON u.product_id=p.id order by 1 desc";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row = array_map('utf8_encode', $row);
	            $row["finish_materials"] = json_decode($row["finish_materials"]);
	            $materials = $row["finish_materials"];
	            $sql1 = "SELECT * FROM product WHERE product_code='".$materials->product_code."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $materials->product_type = $row1["product_type"];
                        $materials->product_name = $row1["product_name"];
                        $materials->grade = $row1["grade"];
                    }
                }
                $row["finish_materials"] = $materials;
	            
	            $row["raw_materials"] = json_decode($row["raw_materials"]);
                
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1 = array_map('utf8_encode', $row1);
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["raw_materials"] = $materials;
                
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                
                $materials = $row["packing_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1 = array_map('utf8_encode', $row1);
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["packing_materials"] = $materials;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	
	else if ($_GET["type"] == "getPrimixLog") {
	    
	    $output = array();
	    
 	    $sql = "SELECT * FROM primixMaster WHERE status = 'Approved' AND plant_id = '".$_GET['plant_id']."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            
	            $output1 = array();
	            $sql1 = "SELECT a.*,b.material_name,b.grade FROM primixMaterials a LEFT JOIN my_view_all b ON a.material_code = b.material_code WHERE a.premixCode = '".$row['premixCode']."' AND a.plant_id = '".$_GET['plant_id']."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }

                $row["materialList"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "getApprovedPrimix") {  // for bulk master
	    
	    $output = array();
	    
 	    $sql = "SELECT *,premixCode as material_code, premixName as material_name, 'NA' as grade, 'Premix' as material_subtype  FROM primixMaster WHERE status = 'Approved' AND plant_id = '".$_GET['plant_id']."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	
	 
	
    else if ($_GET["type"] === "savePremix") {
    
        $entry_date = date("Y-m-d H:i:s");
    
    
        // Basic validation
        if ( empty($_GET["plant_id"]) || empty($input["premixName"]) || empty($input["materialList"]) || !is_array($input["materialList"])) {
            echo json_encode(["status" => "failed", "message" => "Invalid input"]);
            exit;
        }
    
        $plant_id   = $_GET["plant_id"];
        $premixName = $input["premixName"];
        $primixDesc = $input["premixDescription"] ?? "";
        $emp_id     = $_GET["emp_id"];
    
        // Start transaction
        $conn->begin_transaction();
    
        try {
    
            /* Insert primix master */
            $stmt = $conn->prepare("INSERT INTO primixMaster(plant_id, status, premixName, premixDescription, entryBy, entryOn) VALUES (?, 'Approved', ?, ?, ?, ?)" );
    
            $stmt->bind_param("sssss", $plant_id, $premixName, $primixDesc, $emp_id, $entry_date);
    
            if (!$stmt->execute()) {
                throw new Exception("Primix master insert failed");
            }
    
            $last_id = $conn->insert_id;
            $stmt->close();
    
            /* Fetch premixCode */
            $stmt = $conn->prepare("SELECT premixCode FROM primixMaster WHERE id = ?");
            $stmt->bind_param("i", $last_id);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 0) {
                throw new Exception("Primix code not found");
            }
    
            $premixCode = $result->fetch_assoc()["premixCode"];
            $stmt->close();
    
            /* Insert materials */
            $stmt = $conn->prepare("INSERT INTO primixMaterials (plant_id, premixCode, material_type, material_subtype, material_code, perQty, entryBy, entryOn) VALUES (?, ?, ?, ?, ?, ?, ?,?)");
    
            foreach ($input["materialList"] as $material) {
    
                if ( empty($material["material_type"]) || empty($material["material_subtype"]) || empty($material["material_code"])) {
                    throw new Exception("Invalid material data");
                }
    
                $stmt->bind_param("ssssssss", $plant_id, $premixCode, $material["material_type"], $material["material_subtype"], $material["material_code"], $material["perQty"], $emp_id, $entry_date);
    
                if (!$stmt->execute()) {
                    throw new Exception("Material insert failed");
                }
            }
    
             $stmt->close();
    
            // Commit transaction
            $conn->commit();
    
            echo json_encode(["status" => "success","premixCode" => $premixCode]);
    
        } catch (Exception $e) {
            
            // Rollback on failure
            $conn->rollback();
    
            echo json_encode(["status" => "failed","message" => $e->getMessage()]);
        }
    }
    else if ($_GET["type"] === "saveDayStore") {
        
        $plant_id   = $_GET["plant_id"];
        $dayStoreName = $input["dayStoreName"];
        $dayStoreDescription = $input["dayStoreDescription"] ?? "";
        $emp_id     = $_GET["emp_id"];
        $entry_date = date("Y-m-d H:i:s");
        
        $sql = "INSERT INTO dayStoreMaster(plant_id, status, dayStoreName, dayStoreDescription, entryBy, entryOn) VALUES ('".$_GET["plant_id"]."','Approved','$dayStoreName','$dayStoreDescription','$emp_id','$entry_date')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
	else if ($_GET["type"] == "getApprovedDayStores") {
	    
	    $output = array();
	    
 	    $sql = "SELECT * FROM dayStoreMaster WHERE status = 'Approved' AND plant_id = '".$_GET['plant_id']."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}

    
    
    
    else if ($_GET["type"] === "saveBulk") {
    
        $entry_date = date("Y-m-d H:i:s");
    
    
        // Basic validation
        if ( empty($_GET["plant_id"]) || empty($input["bulkName"]) || empty($input["materialList"]) || !is_array($input["materialList"])) {
            echo json_encode(["status" => "failed", "message" => "Invalid input"]);
            exit;
        }
    
        $plant_id   = $_GET["plant_id"];
        $bulkName = $input["bulkName"];
        $bulkDescription = $input["bulkDescription"] ?? "";
        $emp_id     = $_GET["emp_id"];
    
        // Start transaction
        $conn->begin_transaction();
    
        try {
    
            /* Insert primix master */
            $stmt = $conn->prepare("INSERT INTO bulkMaster(plant_id, status, bulkName, bulkDescription, entryBy, entryOn) VALUES (?, 'Approved', ?, ?, ?, ?)" );
    
            $stmt->bind_param("sssss", $plant_id, $bulkName, $bulkDescription, $emp_id, $entry_date);
    
            if (!$stmt->execute()) {
                throw new Exception("Bulk master insert failed");
            }
    
            $last_id = $conn->insert_id;
            $stmt->close();
    
            /* Fetch bulkCode */
            $stmt = $conn->prepare("SELECT bulkCode FROM bulkMaster WHERE id = ?");
            $stmt->bind_param("i", $last_id);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 0) {
                throw new Exception("bulk code not found");
            }
    
            $bulkCode = $result->fetch_assoc()["bulkCode"];
            $stmt->close();
    
            /* Insert materials */
            $stmt = $conn->prepare("INSERT INTO bulkMaterials (plant_id, bulkCode, material_type, material_subtype, material_code, perQty, entryBy, entryOn) VALUES (?, ?, ?, ?, ?, ?,?, ?)");
    
            foreach ($input["materialList"] as $material) {
    
                if ( empty($material["material_type"]) || empty($material["material_subtype"]) || empty($material["material_code"])) {
                    throw new Exception("Invalid material data");
                }
    
                $stmt->bind_param("ssssssss", $plant_id, $bulkCode, $material["material_type"], $material["material_subtype"], $material["material_code"], $material["perQty"], $emp_id, $entry_date);
    
                if (!$stmt->execute()) {
                    throw new Exception("Material insert failed");
                }
            }
    
            $stmt->close();
    
            // Commit transaction
            $conn->commit();
    
            echo json_encode(["status" => "success","bulkCode" => $bulkCode]);
    
        } catch (Exception $e) {
            
            // Rollback on failure
            $conn->rollback();
    
            echo json_encode(["status" => "failed","message" => $e->getMessage()]);
        }
    }

	
	else if ($_GET["type"] == "getApprovedBulk") {
	    
	    $output = array();
	    
 	    $sql = "SELECT * FROM bulkMaster WHERE status = 'Approved' AND plant_id = '".$_GET['plant_id']."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            
	            $output1 = array();
	            $sql1 = "SELECT a.*,b.material_name,b.grade FROM bulkMaterials a LEFT JOIN my_view_all b ON a.material_code = b.material_code WHERE a.bulkCode = '".$row['bulkCode']."' AND a.plant_id = '".$_GET['plant_id']."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        if($row1['material_type'] == 'Premix'){
                            
                            $sql11 = "SELECT b.premixName as material_name FROM primixMaster b WHERE b.premixCode = '".$row1['material_code']."'  ";
                            $result11 = $conn->query($sql11);
                            if ($result11->num_rows > 0) {
                                while ($row11 = $result11->fetch_assoc()) {
                                    $row1['material_name'] = $row11['material_name'];
                                    $row1['grade'] = 'NA';
                                }
                            }
                            
                        }

                        $output1[] = $row1;
                    }
                }

                $row["materialList"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if ($_GET["type"] == "getApprovedBulkForUnitFormula") {
	    
	    $output = array();
	    
 	    $sql = "SELECT id,status,bulkCode as material_code,bulkCode, bulkName as material_name, 'Bulk' as material_subtype,'NA' as grade FROM bulkMaster WHERE status = 'Approved' AND plant_id = '".$_GET['plant_id']."' ";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            
	            $output1 = array();
	            $sql1 = "SELECT a.*,b.material_name,b.grade FROM bulkMaterials a LEFT JOIN my_view_all b ON a.material_code = b.material_code WHERE a.bulkCode = '".$row['bulkCode']."' AND a.plant_id = '".$_GET['plant_id']."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output12 = array();
                        if($row1['material_type'] == 'Premix'){
                            $material_name = '';
                            
                            $sql11 = "SELECT b.id, b.plant_id, b.premixCode, b.material_type, b.material_subtype, b.material_code, b.perQty, pm.premixName as priNAme, m.material_name,m.grade FROM primixMaterials b 
                            LEFT JOIN primixMaster pm ON b.premixCode = pm.premixCode
                            LEFT JOIN my_view_all m ON b.material_code = m.material_code
                            WHERE b.premixCode = '".$row1['material_code']."'  ";
                            $result11 = $conn->query($sql11);
                            if ($result11->num_rows > 0) { 
                                while ($row11 = $result11->fetch_assoc()) {
                                    $material_name = $row11['priNAme'];
                                    $output12[] = $row11;
                                }
                            }
                            $row1['material_name'] = $material_name;
                            $row1['grade'] = 'NA';
                        } 
                        
                        $row1['materials'] = $output12;
                        $output1[] = $row1;
                    }
                }

                $row["materialList"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	else if ($_GET["type"] == "downloadMFRLog") {
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

}

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
?>