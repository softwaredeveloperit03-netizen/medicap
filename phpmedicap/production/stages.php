<?php
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

    if ($_GET["type"] == "getManufacturingStages") {
        $output = array();
     $sql="select * from product where plant_id='".$_GET["plant_id"]."' order by id desc";
    //   echo  $sql = "SELECT u.*, p.dosage_form, p.product_name, p.grade, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code  where plant_id='".$_GET["plant_id"]."'";
       
    //   $sql="select p.*,u.mfr_no FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code where u.plant_id='29' order by p.id desc";
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["instructions"] = json_decode($row["instructions"]);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]); 
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                
                $equipments = array();
                $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE product_code='".$row["product_code"]."' and plant_id='".$_GET["plant_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        if ($row1["isinitial"] == 'YES') {
                            $row1["initial_checks"] = json_decode($row1["initial_checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                
                  $output2 = Array();
                        $sql1 = "SELECT * FROM unitformula WHERE product_code='".$row["product_code"]."' and plant_id='".$_GET["plant_id"]."' ";
                          $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                
                $row["equipments"] = $equipments;
                $row["abbreviation"] = json_decode($row["abbreviation"]);
                $row["bmr_checklist"] = json_decode($row["bmr_checklist"]);
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["stages"] = $output1;
                 $row["unitformula"] = $output2;
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_inprocess_data") {
        
        // ini_set('display_errors', 1);
        // error_reporting(E_ALL);

        $output = Array();
        
      //   $sql="SELECT DISTINCT a.stage_name,a.batch_size,a.batch_number, a.step_name,b.specification_no,b.material_code,b.totalsample_qty,a.product_code,c.product_name,c.product_type FROM bmr_qcsample_tests a LEFT JOIN specification b ON b.specification_no = a.inp_spec_no LEFT JOIN product c ON b.material_code = c.product_code WHERE (b.spec_type = 'Finish Product' OR b.spec_type = 'Intermediate Product') AND a.testing_type ='".$_GET['testing_type']."'  ";
       $sql="SELECT DISTINCT a.status,a.qc_receving_status,a.id as bmr_qcsample_tests_id,a.spec_test_id,a.batch_size,a.Specification,a.stage_name,
       a.step_name,b.specification_no,b.material_code,
           b.totalsample_qty,a.product_code,c.product_name,c.product_type FROM bmr_qcsample_tests a LEFT 
           JOIN specification b ON b.specification_no = a.inp_spec_no LEFT JOIN product c ON b.material_code = c.product_code WHERE (b.spec_type = 'Finish Product' OR b.spec_type = 'Intermediate Product' or b.spec_type = 'Inprocess Specification') AND a.testing_type ='".$_GET['testing_type']."'  ";
        //  $sql = "select * from bmr_qcsample_tests a left join product b on a.product_code=b.product_code left join spec_tests c on a.spec_test_id=c.id and a.inp_spec_no=c.specification_no where a.status='Send To QC' and  a.testing_type='".$_GET['testing_type']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["ar_no"] = $row["arno"];
                $output1 = Array();
                $sql2 = "SELECT spec_tests.*,(select t.person  from testing_tests t where  t.spec_test_id='".$row["spec_test_id"]."' and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as al_person, 
                        (select  t.person_alt from testing_tests t where  t.spec_test_id='".$row["spec_test_id"]."' and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as al_alt_person,
                        (select  status from testing_tests t where  t.spec_test_id='".$row["spec_test_id"]."' and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as t_status,
                        (select  id from testing_tests t where  t.spec_test_id='".$row["spec_test_id"]."' and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as testing_test_id
                        FROM spec_tests 
                        
                 WHERE spec_tests.id='".$row["spec_test_id"]."' and  specification_no in(SELECT specification_no FROM specification WHERE material_code='".$row["product_code"]."')
                  and plant_id=".$_GET["plant_id"]  ;          
                      
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                       $q = 'select * from test left join test_methods on test_methods.test_id = test.id where  test.test = "'.$row2["test"].'"';
                
                    $r = $conn->query($q);
                       if ($r->num_rows > 0) {
                        $row2["methodID"] = 1 ;
                       }else{
                        $row2["methodID"] =0 ;

                       }
                        if($row2["test_type"]!="Microbiology")
                        $output1[] = $row2;
                    }
                }
                $row["spec_tests"] = $output1;
                $row["grn_grade_name"] = '';
                $output2 = [];
                $sql3 = "select * from challan_materials  where grn_no='".$row["grn_no"]."'";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {

                        $output2[] = $row3;
                        $grn_grade_data = json_decode($row3["grn_grade"],true);
                        $row["grn_grade_name"] = $row3["grn_grade"];
                       
                    }
                }

                $row["grn_grade"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      else if ($_GET["type"] == "get_inprocess_data1") {
        //   ini_set('display_errors', 1);
            // error_reporting(E_ALL);
        $output = Array();
               $sql="SELECT DISTINCT a.stage_name,a.batch_size,a.batch_number, a.step_name,b.specification_no,a.qc_receving_status,a.id as bmr_qcsample_tests_id,a.status,
               b.material_code,b.totalsample_qty,a.product_code,c.product_name,c.product_type
               FROM bmr_qcsample_tests a LEFT JOIN specification b ON b.specification_no = a.inp_spec_no 
               LEFT JOIN product c ON b.material_code = c.product_code WHERE 
               (b.spec_type = 'Finish Product' OR b.spec_type = 'Intermediate Product') AND
               a.testing_type ='".$_GET['testing_type']."'  ";

        //  $sql = "select * from bmr_qcsample_tests a left join product b on a.product_code=b.product_code left join spec_tests c on a.spec_test_id=c.id and a.inp_spec_no=c.specification_no where a.status='Send To QC' and  a.testing_type='".$_GET['testing_type']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["ar_no"] = $row["arno"];
                $output1 = Array();
                $sql2 = "SELECT spec_tests.*,(select t.person  from testing_tests t where  t.spec_test_id=spec_tests.id and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as al_person, 
                        (select  t.person_alt from testing_tests t where  t.spec_test_id=spec_tests.id and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as al_alt_person,
                        (select  status from testing_tests t where  t.spec_test_id=spec_tests.id and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as t_status,
                        (select  id from testing_tests t where  t.spec_test_id=spec_tests.id and t.bmr_qcsample_tests_id='".$row["bmr_qcsample_tests_id"]."' order by id desc limit 1) as testing_test_id
                        FROM spec_tests 
                        
                 WHERE    specification_no in(SELECT specification_no FROM specification WHERE material_code='".$row["product_code"]."')
                  and plant_id=".$_GET["plant_id"]."";          
                     

                 
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                       $q = 'select * from test left join test_methods on test_methods.test_id = test.id where  test.test = "'.$row2["test"].'"';
                
                    $r = $conn->query($q);
                       if ($r->num_rows > 0) {
                        $row2["methodID"] = 1 ;
                       }else{
                        $row2["methodID"] =0 ;

                       }
                        if($row2["test_type"]!="Microbiology")
                        $output1[] = $row2;
                    }
                }
                $row["spec_tests"] = $output1;
                $row["grn_grade_name"] = '';
                $output2 = [];
                $sql3 = "select * from challan_materials  where grn_no='".$row["grn_no"]."'";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {

                        $output2[] = $row3;
                        $grn_grade_data = json_decode($row3["grn_grade"],true);
                        $row["grn_grade_name"] = $row3["grn_grade"];
                       
                    }
                }

                $row["grn_grade"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getManufacturingStages_formulation") {
        $output = array();
     $sql="SELECT * FROM bmr_products a left join product b on a.product_code=b.product_code where a.plant_id='".$_GET["plant_id"]."' order by a.id desc";
    //   echo  $sql = "SELECT u.*, p.dosage_form, p.product_name, p.grade, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code  where plant_id='".$_GET["plant_id"]."'";
       
    //   $sql="select p.*,u.mfr_no FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code where u.plant_id='29' order by p.id desc";
       $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["instructions"] = json_decode($row["instructions"]);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]); 
                $row["additional_materials"] = json_decode($row["additional_materials"]);
                
                $equipments = array();
                $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE product_code='".$row["product_code"]."' and plant_id='".$_GET["plant_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        if ($row1["isinitial"] == 'YES') {
                            $row1["initial_checks"] = json_decode($row1["initial_checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                
                  $output2 = Array();
                        $sql1 = "SELECT * FROM unitformula WHERE product_code='".$row["product_code"]."' and plant_id='".$_GET["plant_id"]."' ";
                          $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                
                $row["equipments"] = $equipments;
                $row["abbreviation"] = json_decode($row["abbreviation"]);
                $row["bmr_checklist"] = json_decode($row["bmr_checklist"]);
                $row["label_claim"] = json_decode($row["label_claim"]);
                $row["stages"] = $output1;
                 $row["unitformula"] = $output2;
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveProcedure") {
        if($input["type"]=='save'){
            
         $sql = "UPDATE stages SET isprocedure='".$input["isprocedure"]."', procedures='".json_encode($input["procedures"])."' WHERE id='".$input["id"]."'";
        }
        else if($input["type"]=='conf'){
            
         $sql = "UPDATE stages SET isprocedure='2',   WHERE id='".$input["id"]."'";
        }
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "delstage") {
            $sql = "DELETE FROM bmr_stages where id='".$_GET["id"]."'" ;
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "saveInstruction") {
        // $sql = "SELECT instructions FROM stages WHERE id='".$input["id"]."'";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
        //         $instructions = array();
        //         if ($row["instructions"] == '') {
        //             $instructions = array();
        //         } else {
        //             $temp = json_decode($row["instructions"]);
        //             for ($i = 0; $i < count($temp); $i++) {
        //                 $temp1 = $temp[$i];
        //                 $instructions[] = $temp1;
        //             }
        //         }
            $instructions = $input["instruction"];
                
                $sql = "UPDATE stages SET instructions='".json_encode($instructions)."' WHERE id='".$input["id"]."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"Instruction saved successfully!", "instructions"=>$instructions));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
        //     }
        // } else {
        //     echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        // }
    } else if ($_GET["type"] == "deleteInstruction") {
        $sql = "UPDATE stages SET instructions='.json_encode($input).' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Instruction deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveEquipment") {
        // $sql = "SELECT equipments FROM stages WHERE id='".$input["id"]."'";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
        //         $instructions = array();
        //         if ($row["equipments"] == '') {
        //             $instructions = array();
        //         } else {
        //             $temp = json_decode($row["equipments"]);
        //             for ($i = 0; $i < count($temp); $i++) {
        //                 $temp1 = $temp[$i];
        //                 $instructions[] = $temp1;
        //             }
        //         }
                // $equipment = array();
                //  $equipment['equipment_name'] = $input["equipment_name"];
                 $instructions = json_encode($input["equipment"]);
                
                $sql = "UPDATE stages SET equipments='$instructions' WHERE id='".$input["id"]."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"Equipments saved successfully!", "equipments"=>$instructions));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
        //     }
        // } else {
        //     echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        // }
    } else if ($_GET["type"] == "deleteEquipment") {
        $sql = "UPDATE stages SET equipments='.json_encode($input).' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Equipment deleted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveClearance") {
        $sql = "UPDATE stages SET isclerance='".$input["isclerance"]."', prod_clearances ='".json_encode($input["prod_clearances"])."',qa_clearances ='".json_encode($input["qa_clearances"])."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveWeighing") {
        // $sql = "UPDATE stages SET isweighing='".$input["isweighing"]."', weighings='".json_encode($input["weighings"])."' , WHERE id='".$input["id"]."'";
        $sql = "UPDATE stages SET weighings='".json_encode($input["checkpoint"])."', weighing_frequency='".$input["weighing_frequency"]."', we_frq_unit='".$input["we_frq_unit"]."', weighing_qa='".$input["weighing_qa"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveEnvironment") {
      echo  $sql = "UPDATE stages SET environments='".json_encode($input["checkpoint"])."', env_frequency='".$input["env_frequency"]."', env_freq_unit='".$input["env_freq_unit"]."', env_qa='".$input["env_qa"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveCheck") {
        $sql = "UPDATE stages SET ischeck='".$input["ischeck"]."', checks='".json_encode($input["checks"])."', inprocess_frequency='".$input["inprocess_frequency"]."', inprocess_freq_unit='".$input["inprocess_freq_unit"]."', inprocess_production='".$input["inprocess_production"]."', inprocess_qa='".$input["inprocess_qa"]."', inprocess_qc='".$input["inprocess_qc"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveInitialCheck") {
        $sql = "UPDATE stages SET initial_checks='".json_encode($input["checkpoint"])."', initial_frequency='".$input["initial_frequency"]."', initial_freq_unit='".$input["initial_freq_unit"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveSieveCheck") {
        $sql = "UPDATE stages SET sieve='".json_encode($input["Sieve"])."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveScreenCheck") {
        $sql = "UPDATE stages SET screen='".json_encode($input["Screen"])."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveWashCheck") {
        $sql = "SELECT wash_rinse FROM stages WHERE id='".$input["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $instructions = array();
                if ($row["wash_rinse"] == '') {
                    $instructions = array();
                } else {
                    $temp = json_decode($row["wash_rinse"]);
                    for ($i = 0; $i < count($temp); $i++) {
                        $temp1 = $temp[$i];
                        $instructions[] = $temp1;
                    }
                }
                $temp = array();
                $temp['checkpoint'] = $input['checkpoint'];
                $instructions[] = $temp;
                
                $sql = "UPDATE stages SET wash_rinse='".json_encode($instructions)."' WHERE id='".$input["id"]."'";
                if ($conn->query($sql)) {
                    echo json_encode(array("status"=>"success","msg"=>"Wash Rinse Sample Table saved successfully!"));
                } else {
                    echo json_encode(array("status"=>"failed","msg"=>$conn->error));
                }
            }
        } else {
            echo json_encode(array("status"=>"success","msg"=>"Failed: Invalid Form No."));
        }
    } 
    else if ($_GET["type"] == "newStage") {
        $sql = "INSERT INTO stages (plant_id,product_code, mfr_no, process, stage, isprocedure, isinstruction, isequipment, isclerance, isweighing, isenvironment, ischeck, isinitial, issieve, isscreen, isdrying, iswash ,isinprocess ,isqcchecks) VALUES ('".$_GET["plant_id"]."','".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["process"]."', '".$input["stage"]."', '".$input['isprocedure']."', '".$input['isinstruction']."', '".$input['isequipment']."', '".$input['isclerance']."', '".$input['isweighing']."', '".$input['isenvironment']."', '".$input['ischeck']."', '".$input['isinitial']."', '".$input['issieve']."', '".$input['isscreen']."', '".$input['isdrying']."', '".$input['iswash']."' ,'".$input["isinprocess"]."' ,'".$input["isqcchecks"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "newStage_saipro") {
        $sql = "INSERT INTO bmr_stages (plant_id,product_code, mfr_no, process, stage, isprocedure, isinstruction, isequipment, isclerance, isweighing, isenvironment, ischeck, isinitial, issieve, isscreen, isdrying, iswash ,isinprocess ,isqcchecks) VALUES ('".$_GET["plant_id"]."','".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["process"]."', '".$input["stage"]."', '".$input['isprocedure']."', '".$input['isinstruction']."', '".$input['isequipment']."', '".$input['isclerance']."', '".$input['isweighing']."', '".$input['isenvironment']."', '".$input['ischeck']."', '".$input['isinitial']."', '".$input['issieve']."', '".$input['isscreen']."', '".$input['isdrying']."', '".$input['iswash']."' ,'".$input["isinprocess"]."' ,'".$input["isqcchecks"]."')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Stage Inserted successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
        else if ($_GET["type"] == "GET_bmr_stages_saipro") {
        	$output = Array();
     	  $sql = "select * from bmr_stages";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
     else if ($_GET["type"] == "getSpecificationsLog") {
        $output = Array();
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Inprocess%' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' AND s.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['tests'] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['revision_history'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>