<?php
    require '../../db.php';
    require '../../token.php';
     require '../../tcpdf/tcpdf.php';
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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     if ($_GET["type"] == "saveAllocations") {
        $sql = "INSERT INTO testing (material_code,grn_no,material_grade,ar_no,status) 
        VALUES('".$input["material_code"]."','".$input["grn_no"]."','".$input["material_grade"]."','".$input["ar_no"]."','".$input["status"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   
   }else if ($_GET["type"] == "getPendingAllocations") {
        $output = Array();
        $sql = "SELECT t.*, m.material_name, m.grade, s.specification_no FROM testing t LEFT JOIN master_material m ON t.material_code=m.material_code LEFT JOIN specification s ON t.material_code=s.material_code WHERE t.status='pending' order by 1 desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql2 = "SELECT * FROM spec_tests WHERE specification_no in(SELECT specification_no FROM specification WHERE material_code='".$row["material_code"]."')";
                
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
                $row["spec_tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } else if ($_GET["type"] == "allocateTestingPerson") {
        $sql = "UPDATE testing SET testing_person='done', status='inprocess', specification_no='".$input["specification_no"]."' WHERE testing_no='".$_GET["testing_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $data = $input['spec_tests'];
            for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];
                $sql1 = "INSERT INTO testing_tests (testing_no, test, subtest, description, method_details, isoutside, person) VALUES ('".$_GET["testing_no"]."','".$row1["test"]."','".$row1["subtest"]."','".$row1["description"]."', '".$row1["method_details"]."', '".$row1["isoutside"]."', '".$row1["person"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
        else if ($_GET["type"] == "allocateTestingPersonwater") {
      
        if($_GET['testing_type']=='1')
        {
            $Is_RDs = 1;
        }
        elseif($_GET['testing_type']=='2')
        {
            $Is_RDs = 0;
        }

        // print_r( $input['spec_tests']); 

        // exit;
       $sql = "UPDATE water_point SET allocation='".$input["allocation"]."',  testing_person='done', status='awaiting',is_RDS='".$Is_RDs."', specification_no='".$input["specification_no"]."' WHERE id='".$input["id"]."'";
   
     if ($conn->query($sql)) {
            $data = $input['spec_tests'];
            for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];

                if($row1["methodID"]=='') $method = "Yes"  ; else $method = "No" ;

             
                $sql1 = "INSERT INTO testing_tests (user_no, 
                                                    testing_no, 
                                                    test, 
                                                    subtest, 
                                                    description, 
                                                    isoutside, 
                                                    person, 
                                                    person_alt,
                                                    ismethod,
                                                    method_details,
                                                    outside_testing,
                                                    specification_no) 
                                          VALUES ('".$_GET["user_no"]."',
                                                 '".$input["id"]."',
                                                 '".$row1["test"]."',
                                                 '".$row1["subtest"]."','
                                                 ".$row1["description"]."', 
                                                 '".$row1["isoutside"]."', 
                                                 '".$row1["person"]."', 
                                                 '".$row1["alternate_chemist"]."', 
                                                 '".$method."',
                                                 '".$row1["methodID"]."',
                                                 '".$row1["lab_name"]."',
                                                 '".$row1["specification_no"]."')"; 
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

    
     else if ($_GET["type"] == "getPendingAllocationTestings") {
        $output = Array();
         $sql = "SELECT   t.*, 
             (select specification_no from specification  where specification.water_type = t.water_type  order BY specification.id desc limit 1)  as specification_no
         FROM water_point t  where t.status='pending'  order by 1 desc";
        //  where t.status='pending' and t.plant_id='".$_GET["plant_id"]."'  order by 1 desc";

         
          
        $result = $conn->query($sql);
         
       
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["ar_no"] = $row["arno"];
                $output1 = Array();
                $sql2 = "SELECT spec_tests.*  FROM spec_tests 
                        
                 WHERE specification_no in(SELECT specification_no FROM specification WHERE material_code='".$row["material_code"]."')and specification_no='".$row["specification_no"]."'
                  and plant_id=".$_GET["plant_id"]   ;          
                     

                 
                
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
                    //     if($row2["test_type"]!="Microbiology")
                        $output1[] = $row2;
                    }
                }
                $row["spec_tests"] = $output1;
                //$row["grn_grade_name"] = '';
                // $output2 = [];
                // $sql3 = "select * from challan_materials  where grn_no='".$row["grn_no"]."'";
                // $result3 = $conn->query($sql3);
                // if ($result3->num_rows > 0) {
                //     while ($row3 = $result3->fetch_assoc()) {

                //         $output2[] = $row3;
                //         $grn_grade_data = json_decode($row3["grn_grade"],true);
                //         $row["grn_grade_name"] = $row3["grn_grade"];
                       
                //     }
                // }

               // $row["grn_grade"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
   
   
    } 
    
    
    
                
    else if ($_GET["type"] == "getPendingTestingFormsWater") {
        
                $sql = "SELECT t.* FROM water_point t  WHERE t.status='awaiting'  and is_RDS = 1  order by 1 desc ";
            $result = $conn->query($sql);
            $output = Array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
             
            
                    $output1 = Array();
                    
                         $sql1="SELECT *,t3.test_id as spec_test_id,t.id as ttt_id,t.status as t_status,t3.chemical_reagents,t3.balance,t3.equipment_instruments,t3.glasswares 
                       FROM testing_tests t left join water_point t1 ON t.testing_no=t1.id 
                   LEFT JOIN spec_tests s2 on s2.test=t.test AND s2.subtest=t.subtest 
                    LEFT join test_methods t3 on t3.test_id=s2.id WHERE t.testing_no='".$row["id"]."' AND 
                    s2.specification_no='".$row["specification_no"]."'";
                    
                    
                                 
                            $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["method_details"] = json_decode($row1["method_details"]);
                            $row1["chemical_reagents"] = json_decode($row1["chemical_reagents"]);
                            $row1["balance"] = json_decode($row1["balance"]);
                            $row1["glasswares"] = json_decode($row1["glasswares"]);
                            $row1["equipment_instruments"] = json_decode($row1["equipment_instruments"]);
                   
                          $output26 = [];
                    
                            foreach ($equipmentData as $value) {
                                $sql26 = "SELECT id, equipment_name, capacity,category, make, calibration_frequency_inhouse FROM equipment WHERE equipment_code = '" . $value->equipment_id . "'";
                                $result11 = $conn->query($sql26);
                            
                                if ($result11->num_rows > 0) {
                                    while ($row11 = $result11->fetch_assoc()) {
                                         $output26[] = $row11;
                                    }
                                }
                            }
                            
                             $row1["equipment_instrumentsss"] = $output26;
                            $output1[] = $row1;
                    
                        }
                    }
                    
                     $row["tests1"] = $output1;
                     
                                    $output[] = $row;
                                
                            
            }
        }
        
                    echo json_encode($output);
                    
     }

    
    
    
    else if ($_GET["type"] == "getPendingTestingForms") {
        $sql = "SELECT t.*, m.material_name, m.grade, m.material_type FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' order by 1 desc";
        $result = $conn->query($sql);
        $output = Array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                //$sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."' AND status='pending' AND person='".$_GET["emp_id"]."'";
                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."' AND status='pending' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $row1["method_details"] = json_decode($row1["method_details"]);
                            
                        $descriptions = $row1["method_details"];
                        for ($i = 0; $i < count($descriptions); $i++) {
                            $data = $descriptions[$i];
                            if ($data->option == "chemical") {
                                $chemicals = $data->list;
                                for ($j = 0; $j < count($chemicals); $j++) {
                                    $chemical = $chemicals[$j];
                                    $output2 = Array();
                                    $sql3 = "SELECT * FROM chemicals WHERE chemical_no='".$chemical->id."' AND received_qty > issue_qty";
                                    $result3 = $conn->query($sql3);
                                    if ($result3->num_rows > 0) {
                                        while ($row3 = $result3->fetch_assoc()) {
                                            $output2[] = $row3;
                                        }
                                    }
                                    $chemical->batches = $output2;
                                    $chemicals[$j] = $chemical;
                                }
                                $data->list = $chemicals;
                            }
                            $descriptions[$i] = $data;
                        }
                        $output1[]= $row1;
                    }
                }
                
                
                if (count($output1) > 0) {
                    $row["tests"] = $output1;
                   
                }
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCheckedTestingReport") {
       $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM testing_tests WHERE user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                if (count($output1) > 0) {
                    $row["tests"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getTestingReport") {
        
        //   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
        //      $resQ = $conn->query($q);
        //       $prodLatest = $resQ->fetch_assoc(); 
        //      $row['gradeName'] = $prodLatest['gradeName']; 

          $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t 
        LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='Approved' AND m.material_type='Packing Material' ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM testing_tests WHERE user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                if (count($output1) > 0) {
                    $row["spec_tests"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveTestingForm") {
        error_reporting(0);
        $sql = "UPDATE testing_tests SET result='".$input["result"]."', remark='".$input["remark"]."', start_time='".$input["start_time"]."', end_time='".$input["end_time"]."', status='active', descriptions='".json_encode($input["descriptions"])."' WHERE id='".$input["test_no"]."'";
        if ($conn->query($sql) == TRUE) {
            $descriptions = $input["descriptions"];
            for ($i = 0; $i < count($descriptions); $i++) {
                $data = $descriptions[$i];
                if ($data['option'] == "chemical") {
                    $chemicals = $data['list'];
                    for ($j = 0; $j < count($chemicals); $j++) {
                        $chemical = $chemicals[$j];
    
                        $sql1 = "INSERT INTO chemical_issue (chemical_no, batch_no, qty, purpose, status, entry_by, entry_date) VALUES ('".$chemical['id']."', '".$chemical['batch_no']."', '".$chemical['qty']."', 'TESTING', 'approve', '".$_GET["emp_id"]."', '$entry_date')"; 
                        $conn->query($sql1);
    
                        $sql1 = "SELECT * FROM chemicals WHERE chemical_no='".$chemical['id']."' AND batch_no='".$chemical['batch_no']."'";
                        $result = $conn->query($sql1);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $received_qty = $row["received_qty"];
                                $issue_qty = $row["issue_qty"];
                                $issue_qty += +$chemical['qty'];
                                $sql1 = "UPDATE chemicals SET issue_qty='$issue_qty' WHERE id='".$row['id']."'";
                                $conn->query($sql1);
                                break;
                            }
                        }
                    }
                }
            }
            /* $sql = "INSERT INTO equipment_uses (equipment_no, batch_no, activity, cleaning_type, start_time, end_time, operator, entry_by, entry_date) VALUES ('".$input["equipment"]."', '', 'Testing', '".$input["cleaning_type"]."', '".$start_time."', '".$end_time."', '".$_GET["emp_id"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
            $conn->query($sql); */
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        $sql = "SELECT * FROM testing_tests WHERE testing_no='".$input["testing_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
        } else {
            $sql = "UPDATE testing SET status='active' WHERE testing_no='".$input["testing_no"]."'";
            $conn->query($sql);
        }
    } else if ($_GET["type"] == "approveTesting") {
        $sql = "UPDATE testing SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "UPDATE stock_book SET status='Approved' WHERE material_code='".$_GET["material_code"]."' AND batch_no='".$_GET["batch_no"]."'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getPendingTestingReport") {
         $sql = "SELECT t.*,s.method_details, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code LEFT JOIN spec_tests s ON t.specification_no=s.specification_no AND m.material_type='Raw Material' ORDER BY t.id DESC";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["observation"] = "";
                $sql1 = "SELECT * FROM testing_tests WHERE user_no='".$_GET["user_no"]."' AND testing_no='".$row["testing_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE specification_no=(SELECT specification_no FROM testing WHERE testing_no='".$row["testing_no"]."') AND test='".$row1["test"]."' AND subtest='".$row1["subtest"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                if ($row1["observation"] !== "pass") {
                                    if ($row2["limit_type"] == "Limits") {
                                        if ($row1["result"] >= $row2["lower_limit"] && $row1["result"] <= $row2["upper_limit"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "LessThan") {
                                        if ($row1["result"] <= $row2["lessthan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "MoreThan") {
                                        if ($row1["result"] >= $row2["morethan"]) {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    } else if ($row2["limit_type"] == "Compliances") {
                                        if ($row1["result"] == "complies") {
                                            $row1["observation"] = "pass";
                                        } else {
                                            $row1["observation"] = "fail";
                                        }
                                    }
                                }
                            }
                        }
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
    


    }else if ($_GET["type"] == "downloadTestingReport") {
        $_GET['filename'] = 'A.R.Report'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">A.R.Report</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">A. R. No.</td>
                    <td style="width: 10%;"> Sampling No</td>
                    <td style="width: 20%;">Specification No</td>
                    <td style="width: 20%;">Material Name</td>
                    <td style="width: 10%;">Material Code</td>
                    <td style="width: 15%;">Material Grade</td>
                    <td style="width: 15%;">view</td>
                </tr>
            </thead>';
              $output = Array();
                $sql = "SELECT * FROM testing_tests ORDER BY name";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%; ">'.$i.'.</td>
                        <td style="width: 10%; ">'.$row['sampling_no'].'</td>
                        <td style="width: 20%; ">'.$row['specification_no'].'</td>
                        <td style="width: 20%; ">'.$row['material_name'].'</td>
                        <td style="width: 10%; ">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material Grade'].'</td>
                        <td style="width: 15%;">'.$row['view'].'</td>
                        
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glasswares Log.pdf', 'I');
}
}

$conn->close();
?>