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
    
// ini_set('display_errors', 1);
//      error_reporting(E_ALL);
     
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
       
       if ($_GET["type"] == "savePendingAllocation") {
         $sql = "INSERT INTO sampling (material_code, batch_no, containers, grn_no, grn_date ) 
        VALUES ('".$input["material_code"]."', '".$input["batch_no"]."', '".$input["containers"]."','".$input["grn_no"]."','".$input["grn_date"]."')";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   
             
     else if ($_GET["type"] == "getpackingallocation101") { 

         $output = Array();
        //   $sql = "SELECT
        //         c.*,
        //         c1.challan_date,
        //         c1.po_no,
        //         c1.po_date,
        //         c1.vendor_no,
        //         v.vendor_name,
        //         m.material_type,
        //         m.material_subtype,
        //         m.material_name,
        //         m.grade,
        //         s.specification_no
        //     FROM
        //         challan_materials c
        //     LEFT JOIN challan c1 ON
        //         c.challan_no = c1.challan_no
        //     LEFT JOIN material m ON
        //         c.material_code = m.material_code
        //     LEFT JOIN vendor v ON
        //         c1.vendor_no = v.vendor_no
        //     LEFT JOIN specification s ON
        //         c.material_code = s.material_code
        //   WHERE c1.plant_id= '".$_GET["plant_id"]."' AND c.status='approve' AND s.specification_no != '' ORDER BY c.id DESC ";
        
          $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name,
        m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' AND c.status='approve'
        AND m.material_type='Packing Material'  ORDER BY c.id DESC ";
        
        // AND m.material_type='Raw Material'
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
               // $row["grn_grade"] = json_decode($row["grn_grade"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }


                $row["grn_grade_name"] = '';
                $output2 = [];
                $sql3 = "select * from challan_materials  where grn_no='".$row["grn_no"]."'";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        
                        $output2[] = $row3;
                      //  if(isset($row3["grn_grade"]["grade"]))
                      
                        
                      $output2[] = $row3;
                      $row["grn_grade_name"] = $row3["grn_grade"];
                       
                    }
                }
        
             
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
        
   
   
   
   else if ($_GET["type"] == "getPendingAllocation") {
        $output = Array();
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON 
         s.material_code=m.material_code where m.material_type='Packing Material'   order by 1 desc"; 
        //$sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.material_code, s.batch_no, m.material_subtype,m.material_name, m.grade, s1.specification_no FROM sampling s LEFT JOIN material m ON s.material_code=m.material_codeLEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE s.user_no='".$_GET["user_no"]."'AND s.status='pending'AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = $prodLatest['gradeName'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "allocatePerson") {
        $sql = "UPDATE sampling SET sampling_person='".$input["sampling_person"]."',alt_sampling_person='".$input["sampling_person"]."', status='inprocess', request_by='".$_GET["emp_id"]."', request_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAllocationLog") {
        $output = Array();
         //$sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON  s.material_code=m.material_code  order by 1 desc"; 
        $sql = "SELECT s.*, m.material_subtype, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code
         WHERE s.status !='pending' AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = $prodLatest['gradeName'];
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "changePerson") {
        $sql = "UPDATE sampling SET sampling_person='".$input["sampling_person"]."', status='inprocess'"; 
        //allocate_by='".$_GET["emp_id"]."', allocate_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getAwaitingSamplings") {
        $output = Array();
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON 
         s.material_code=m.material_code   order by 1 desc"; 
        //$sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='inprocess' AND s.sampling_person='".$_GET["emp_id"]."' AND m.material_type='Packing Material'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingAreaCheckpoints") {
        $output = Array();
        // $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, 
        // m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit 
        // FROM sampling s LEFT JOIN material m ON 
        // s.material_code=m.material_code LEFT JOIN specification s1 ON s.material_code=s1.material_code 
        // WHERE s.status='inprocess' 
        // AND s.area_status IN ('pending', 'inprocess') AND m.material_type='Packing Material'";
        $sql = "SELECT  s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, 
    m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit, s.allocation_date
FROM sampling s 
LEFT JOIN material m ON s.material_code = m.material_code 
LEFT JOIN specification s1 ON s.material_code = s1.material_code 
WHERE s.status = 'inprocess' 
    AND s.area_status IN ('pending', 'inprocess') 
    AND m.material_type = 'Packing Material'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                
                if (+$row["containers"] > 10) {
                    $row["sampling_container"] = ceil(sqrt (+$row["containers"]) + 1);
                } else {
                    $row["sampling_container"] = ceil(+$row["containers"]);
                }
    
                if ($row["area_status"] !== "pending") {
                    $row["area_details"] = json_decode($row["area_details"]);
                }
    
                if ($row["specification_no"] == "") {
                    $row["current_status"] = "Specification not Available";
                } else if ($row["area_status"] == 'pending') {
                    $row["current_status"] = 'Area Checkpoints';
                } else if ($row["area_status"] !== 'complete') {
                    $row["current_status"] = 'Area Checkpoints';
                }
    
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveAreaCheckpoints") {
        $sql = "UPDATE sampling SET area_details='".json_encode($input)."', laf_start='".$input["laf_start"]."', area_status='complete' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $sql = "INSERT INTO equipment_usages (equipment_code, operator, activity, usage_from, status, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', '".$_GET["emp_id"]."', 'Sampling', '".$input["laf_start"]."','inprocess', '".$_GET["emp_id"]."', '$entry_date')";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }  else if ($_GET["type"] == "getPendingLineClearance") {
        $output = array();
        $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, s.clearance_status, s.clearance_no, s.area_details, m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE s.status='inprocess' AND s.area_status='complete' AND s.clearance_status IN ('pending', 'inprocess') AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["area_details"] = json_decode($row["area_details"]);
    
                if ($row["clearance_status"] !== 'pending') {
                    $temp = Array();
                    $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                            $temp["area_cleaned"] = $row1["area_cleaned"];
                            $temp["product_traces"] = $row1["product_traces"];
                            $temp["temperature"] = $row1["temperature"];
                            $temp["humidity"] = $row1["humidity"];
                            $temp["entry_by"] = $row1["entry_by"];
                            $temp["entry_date"] = $row1["entry_date"];
                            $temp["status"] = $row1["status"];
    
                            if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                                $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                                $conn->query($sql2);
                                $row["clearance_status"] = "complete";
                            }
                            break;
                        }
                    }
    
                    $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $temp["material_code"] = $row1["material_no"];
                            $temp["batch_no"] = $row1["batch_no"];
                            break;
                        }
                    }
                    $row["clearance_details"] = $temp;
                }
    
                $row["current_status"] = 'Line Clearance';
    
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "callforclearance") {
        $c_id = 0;
        $sql = "SELECT MAX(cid) as cid FROM lineclearance";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $c_id = $row["cid"];
            }
        }
        $c_id++;
        $clearance_no = "LC-".$c_id;
            
        $sql = "INSERT INTO lineclearance (clearance_no, department,section,activity,material_no,grn_no,batch_no, checkpoints,request_by,request_date, cid) VALUES ('$clearance_no','".$_GET["department"]."','Sampling','".$input["material_type"]." Sampling','".$input["material_code"]."','".$input["grn_no"]."','".$input["batch_no"]."','".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','$entry_date', $c_id)";
        if ($conn->query($sql)) {
            $sql = "UPDATE sampling SET clearance_no='".$clearance_no."', clearance_status='inprocess' WHERE id='".$input["id"]."'";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
     } else if ($_GET["type"] == "getPendingSamplingForm") {
      
    $output = array();
     $sql = "SELECT * FROM sampling a left join material b on a.material_code=b.material_code WHERE  a.clearance_status ='pending' and a.sample_status!='complete' AND b.material_type ='Packing Material'
     ORDER BY a.id DESC";
     
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'  and  plant_id =  '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            
             $sql1 = "SELECT * FROM material_type WHERE material_type='".$row["material_type"]."' and material_subtype='".$row["material_subtype"]."' and plant_id =  '".$_GET["plant_id"]."'  order by 1 desc limit 1 ";
         
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["sampling_criteria"] = $row1["sampling_criteria"];
                    $row["control_sample_type"] = $row1["control_sample_type"];
                    $row["control_reserve_criteria"] = $row1["control_reserve_criteria"];
                    $row["retest_type"] = $row1["retest_type"];
                }
            }
            
            
             $output1 = array();
             $sql1 = "SELECT received_qty FROM challan_materials  WHERE grn_no ='".$row["grn_no"]."' ";
         
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                  $i=0;
                while ($row1 = $result1->fetch_assoc()) {
                   $output1[$i]['received_qty'] = (int)$row1["received_qty"];
                     $i++;
                }
            }
            
            
             
            
            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Packing Material' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                  
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row1["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }
            
            $row['received_qty'] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);

         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
         
    //     $output = Array();
    //     $sql = "SELECT s.*, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no,
    //     s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, m.material_subtype, m.material_name, m.grade,
    //     s1.specification_no, s1.sample_qty, s1.unit FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code 
    //     LEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE s.status='inprocess' AND s.sample_status='pending' 
    //     AND m.material_type='Packing Material'";
        
        
        
    //     //  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name,
    //     // m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
    //     // LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' AND c.status='approve'
    //     // AND m.material_type='Packing Material'  ORDER BY c.id DESC ";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
                
    //             if (+$row["containers"] > 10) {
    //                 $row["sampling_container"] = ceil(sqrt (+$row["containers"]) + 1);
    //             } else {
    //                 $row["sampling_container"] = ceil(+$row["containers"]);
    //             }
                
    //             $row["area_details"] = json_decode($row["area_details"]);
    
    //             $temp = Array();
    //             $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $temp["checkpoints"] = json_decode($row1["checkpoints"]);
    //                     $temp["area_cleaned"] = $row1["area_cleaned"];
    //                     $temp["product_traces"] = $row1["product_traces"];
    //                     $temp["temperature"] = $row1["temperature"];
    //                     $temp["humidity"] = $row1["humidity"];
    //                     $temp["entry_by"] = $row1["entry_by"];
    //                     $temp["entry_date"] = $row1["entry_date"];
    //                     $temp["status"] = $row1["status"];

    //                     if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
    //                         $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
    //                         $conn->query($sql2);
    //                         $row["clearance_status"] = "complete";
    //                     }
    //                     break;
    //                 }
    //             }
    //             $row["clearance_details"] = $temp;
    
    //             $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row["specification_no"] = $row1["specification_no"];
    //                     $row["composite_qty"] = +$row1["sample_qty"];
    //                     $row["unit"] = $row1["unit"];
    
    //                     $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
    //                     $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $row["identication_qty"] = +$row2["identication_qty"];
    //                         }
    //                     } else {
    //                         $row["identication_qty"] = 0;
    //                     }
    //                     $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
    //                     $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
    //                     break;
    //                 }
    //             }
    
    //             $output1 = Array();
    //             for ($i = 0; $i < +$row["sampling_container"]; $i++) {
    //                 $temp = Array();
    //                 $temp['container_no'] = $i + 1;
    //                 $temp['identication_qty'] = number_format(+$row["identication_qty"] / +$row["sampling_container"], 2);
    //                 $temp['composite_qty'] = number_format(+$row["composite_qty"] / +$row["sampling_container"], 2);
                    
    //                 $temp['withdrawal_identication_qty'] = number_format(+$temp["identication_qty"] * 2, 2);
    //                 $temp['withdrawal_composite_qty'] = number_format((+$temp["composite_qty"] * 2) + +$temp["composite_qty"], 2);
    //                 $temp['status'] = "pending";
    //                 $output1[] = $temp;
    //             }
    //             $row['withdrawal_identication_qty'] = number_format(+$row["identication_qty"] * 2, 2);
    //             $row['withdrawal_composite_qty'] = number_format((+$row["composite_qty"] * 2) + +$row["composite_qty"], 2);
    //             $row["container_details"] = $output1;
    
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    }
    else if ($_GET["type"] == "saveSamplingInfo") {
        
         $sql = "UPDATE sampling SET sampling_details='".json_encode($input)."', sampling_start_time = '".$input["start_time"]."', 
        sampling_end_time = '".$input["stop_date"]."', sample_status='complete',
        status='active', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
          
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if ($_GET["type"] == "getActiveSamplings") {
        
        
        
        
          $output = array();
        $sql = "SELECT * FROM sampling WHERE   sample_status='complete' AND status='active' AND material_code LIKE 'PM%' 
     ORDER BY id DESC";
     
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
                $row["sampling_details"] = json_decode($row["sampling_details"]);

              $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'  and  plant_id =  '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                       $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = $prodLatest['gradeName'];
                }
            }
            
             $sql1 = "SELECT * FROM material_type WHERE material_type='".$row["material_type"]."' and material_subtype='".$row["material_subtype"]."' and plant_id =  '".$_GET["plant_id"]."'  order by 1 desc limit 1 ";
         
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["sampling_criteria"] = $row1["sampling_criteria"];
                    $row["control_sample_type"] = $row1["control_sample_type"];
                    $row["control_reserve_criteria"] = $row1["control_reserve_criteria"];
                    $row["retest_type"] = $row1["retest_type"];
                }
            }
            
             
             
            
            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Packing Material' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                  
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                         
                        break;
                    }
                }
            }
            
            $row['received_qty'] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);

         
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        // $output = Array();
        // $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, 
        // s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, 
        // m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit FROM sampling s LEFT JOIN material m ON 
        // s.material_code=m.material_code LEFT JOIN specification s1 ON s.material_code=s1.material_code Where s.sample_status='complete' order by 1 desc";
        // //WHERE s.status='active' AND m.material_type='Packing Material'";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
    
        //         $temp = Array();
        //         $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        //                 $temp["checkpoints"] = json_decode($row1["checkpoints"]);
        //                 $temp["area_cleaned"] = $row1["area_cleaned"];
        //                 $temp["product_traces"] = $row1["product_traces"];
        //                 $temp["temperature"] = $row1["temperature"];
        //                 $temp["humidity"] = $row1["humidity"];
        //                 $temp["entry_by"] = $row1["entry_by"];
        //                 $temp["entry_date"] = $row1["entry_date"];
        //                 $temp["status"] = $row1["status"];
        //                 break;
        //             }
        //         }
        //         $row["clearance_details"] = $temp;
    
        //         $row["area_details"] = json_decode($row["area_details"]);
        //         $row["sampling_details"] = json_decode($row["sampling_details"]);
    
        //         $output[] = $row;
        //     }
        // }
        // echo json_encode($output);
    } else if ($_GET["type"] == "updateActiveSampling") {
        $sql = "UPDATE sampling SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getCheckedSamplings") {
        
        
        
          $output = array();
     $sql = "SELECT * FROM sampling WHERE   sample_status='complete' AND status='checked' AND material_code LIKE 'PM%' 
     ORDER BY id DESC";
     
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
                $row["sampling_details"] = json_decode($row["sampling_details"]);

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'  and  plant_id =  '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                       $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = $prodLatest['gradeName'];
                }
            }
            
             $sql1 = "SELECT * FROM material_type WHERE material_type='".$row["material_type"]."' and material_subtype='".$row["material_subtype"]."' and plant_id =  '".$_GET["plant_id"]."'  order by 1 desc limit 1 ";
         
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["sampling_criteria"] = $row1["sampling_criteria"];
                    $row["control_sample_type"] = $row1["control_sample_type"];
                    $row["control_reserve_criteria"] = $row1["control_reserve_criteria"];
                    $row["retest_type"] = $row1["retest_type"];
                }
            }
            
             
             
            
            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Packing Material' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                  
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                         
                        break;
                    }
                }
            }
            
            $row['received_qty'] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);

         
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        // $output = Array();
        // $sql = "SELECT s.*, m.material_subtype, m.material_name, m.grade, s1.specification_no, 
        // s1.sample_qty, s1.unit FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code 
        // LEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE s.status='checked'";
        
        
        //  $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
                
                    
        //         $temp = Array();
        //         $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        //                 $temp["checkpoints"] = json_decode($row1["checkpoints"]);
        //                 $temp["area_cleaned"] = $row1["area_cleaned"];
        //                 $temp["product_traces"] = $row1["product_traces"];
        //                 $temp["temperature"] = $row1["temperature"];
        //                 $temp["humidity"] = $row1["humidity"];
        //                 $temp["entry_by"] = $row1["entry_by"];
        //                 $temp["entry_date"] = $row1["entry_date"];
        //                 $temp["status"] = $row1["status"];
        //                 break;
        //             }
        //         }
        //         $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
        //         $result1 = $conn->query($sql1);
        //         if ($result1->num_rows > 0) {
        //             while ($row1 = $result1->fetch_assoc()) {
        //                 $temp["material_code"] = $row1["material_no"];
        //                 $temp["batch_no"] = $row1["batch_no"];
        //                 break;
        //             }
        //         }
        //         $row["clearance_details"] = $temp;
    
        //         $row["area_details"] = json_decode($row["area_details"]);
        //         $row["sampling_details"] = json_decode($row["sampling_details"]);
    
        //         $output[] = $row;
        //     }
        // }
        // echo json_encode($output);
        
        
    } else if ($_GET["type"] == "updateCheckedSampling") {
        
        
        $sql = "UPDATE sampling SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date'
        WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
    
            $t_no1 = 0;
            $t_no = "";
            $sql = "SELECT IFNULL(MAX(t_no1), 0) as t_no1 FROM testing";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $t_no1 = $row["t_no1"];
                }
            }
            $t_no1++;
            $no = strlen($t_no1);
            if ($no == 1) {
                $t_no = "T-00".$t_no1;
            } else if ($no == 2) {
                $t_no = "T-0".$t_no1;
            } else if ($no >= 3) {
                $t_no = "T-".$t_no1;
            }
    
            $ar_no = $input["ar_no"] ; 
    
            $sql = "INSERT INTO testing (plant_id,testing_no, sampling_no, grn_no, ar_no, specification_no, material_code, entry_by, entry_date, t_no1)
            VALUES ('".$_GET["plant_id"]."','$t_no','".$input["sampling_no"]."', '".$input["grn_no"]."','$ar_no','".$input["specification_no"]."',
            '".$input["material_code"]."','".$_GET["emp_id"]."','".$entry_date."','$t_no1')";
            if ($conn->query($sql)) {
                $sql = "UPDATE sampling SET testing_status='active' WHERE id='".$_GET["id"]."'";
                $conn->query($sql);
            }
            
            
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getSamplings") {
      
          $output = array();
     $sql = "SELECT * FROM sampling WHERE   testing_status = 'active' AND material_code LIKE 'PM%' 
     ORDER BY id DESC";
     
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
                $row["sampling_details"] = json_decode($row["sampling_details"]);

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'  and  plant_id =  '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            
             $sql1 = "SELECT * FROM material_type WHERE material_type='".$row["material_type"]."' and material_subtype='".$row["material_subtype"]."' and plant_id =  '".$_GET["plant_id"]."'  order by 1 desc limit 1 ";
         
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["sampling_criteria"] = $row1["sampling_criteria"];
                    $row["control_sample_type"] = $row1["control_sample_type"];
                    $row["control_reserve_criteria"] = $row1["control_reserve_criteria"];
                    $row["retest_type"] = $row1["retest_type"];
                }
            }
            
             
             
            
            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Packing Material' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                  
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                         
                        break;
                    }
                }
            }
            
            $row['received_qty'] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);

    }
    else if ($_GET["type"] == "getPendingLabels") {
        $output = Array();
        $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, 
        m.material_subtype, m.material_name, m.grade, s.status FROM sampling s LEFT JOIN material m ON 
        s.material_code=m.material_code WHERE m.material_type='Packing Material'"; 
        //AND s.label='pending' AND s.status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getApprovedLabels") {
        $output = Array();
        //$sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no,
       // m.material_subtype, m.material_name, m.grade, s.status FROM sampling s LEFT JOIN material m 
        //ON s.material_code=m.material_code 
       //order by 1 desc'";
        $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no,
        m.material_subtype, m.material_name, m.grade, s.status FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code 
         WHERE m.material_type='Packing Material' AND s.label='done' AND s.status='approve' ";
         //AND s.id NOT IN (SELECT document_no FROM labels 
         //WHERE label_for='Sampling' AND category='additional' AND status='pending')";
         $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_quantity_sampling") {
        
                     $output1 = array();

        if($_GET["quantity"] == 'Fixed Qty'){
              $sql1 = "SELECT * FROM samplling_plan_packing  WHERE material_subtype='".$_GET["material_subtype"]."' AND
             quantity='".$_GET["quantity"]."'";
        }else{
               $sql1 = "SELECT * FROM samplling_plan_packing  WHERE material_subtype='".$_GET["material_subtype"]."' AND
             quantity='".$_GET["quantity"]."' AND  ".$_GET["received_qty"]." BETWEEN from_range AND to_range";
        }
        
        $received_qty = $_GET["received_qty"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                 while ($row1 = $result1->fetch_assoc()) {
                     
                 $percentage = (float) str_replace('%', '', $row1['sampling_criteria']);


                    if($_GET["quantity"] == 'Fixed Qty'){

                   $row1['qty_widrawn']  =  $row1['fixed_qty'];
                    }else{
                            $row1['qty_widrawn']  =  $received_qty * ($percentage / 100);
                    }
                   
                   
                   $output1[] = $row1;
                 }
            }
              
           echo json_encode($output1);
          
    } 
    else if ($_GET["type"] == "saveLabelRequest") {
        $sql = "INSERT INTO labels (label_for, document_no, category, label_type, no, reason, entry_by, entry_date) VALUES ('Sampling', '".$_GET["id"]."', 'additional', 'new', '".$input["no"]."', '".$input["reason"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getPendingAdditionalLabels") {
        $output = array();
        $sql = "SELECT s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, 
        s.batch_no, m.material_subtype, m.material_name, m.grade, s.status, l.no, reason, 
        l.entry_by,l.entry_date, l.id FROM labels l LEFT JOIN sampling s ON l.document_no=s.id 
        LEFT JOIN material m ON s.material_code=m.material_code WHERE m.material_type='Packing Material' 
        AND l.status='pending' AND l.label_for='Sampling' AND l.category='additional'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLabelsLog") {
        $output = array();
        $sql = "SELECT s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, m.material_subtype, m.material_name, m.grade, s.status, l.no, reason, l.entry_by,l.entry_date, l.id FROM labels l LEFT JOIN sampling s ON l.document_no=s.id LEFT JOIN material m ON s.material_code=m.material_code WHERE m.material_type='Packing Material' AND l.label_for='Sampling'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateAdditionalLabel") {
        $sql = "UPDATE labels SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }else if ($_GET["type"] == "downloadLabelsLog") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Packing Material Sample Log</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:5%; text-align:center;"><b>Sr.</b></td>
                 <td style="width:20%; text-align:center;"><b>Material Name</b></td>
                <td style="width:20%; text-align:center;"><b>Material Type</b></td>
                <td style="width:10%; text-align:center;"><b>Material Code</b></td>
                 <td style="width:15%; text-align:center;"><b>Grade</b></td>
                <td style="width:10%; text-align:center;"><b>Batch No.</b></td>
                <td style="width:10%; text-align:center;"><b>Containers</b></td>
                <td style="width:10%; text-align:center;"><b>GRN No.</b></td>
            </tr>';
              $i=1;
             $sql = "SELECT s.id,s.entry_date,s.sampling_no, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit, s.status 
        FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 
        ON s.material_code=s1.material_code WHERE m.material_type='Packing Material' 
        AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' ";//AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                 $html.=' <tr>
                    <td style="width:5%;">'.$i.'</td>
                    <td style="width:20%;">'.$row['material_name'].'</td>
                    <td style="width:20%;">'.$row['material_type'].'</td>
                    <td style="width:10%;">'.$row['material_code'].'</td>
                     <td style="width:15%;">'.$row['grade'].'</td>
                    <td style="width:10%;">'.$row['batch_no'].'</td>
                    <td style="width:10%;">'.$row['containers'].'</td>
                    <td style="width:10%;">'.$row['grn_no'].'</td>
            </tr>';
               $i++;
          }
          }
      $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Samplings Log.pdf', 'I');
    
        
    } else if ($_GET["type"] == "downloadSamplingsReport") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
       
        $html.='
     <h3>Material Details:</h3>
        <table cellpadding="5" border="0.1">';
        $sql = "SELECT s.id,s.entry_date,s.sampling_no, s.containers, s.grn_no, s.grn_date, s.mfg_date, 
        s.exp_date, s.containers, s.material_code, s.batch_no, s.laf_start, s.clearance_status, 
        s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, 
        m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit, s.status 
        FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 
        ON s.material_code=s1.material_code WHERE m.material_type='Packing Material' 
        AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'  ";//AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
        <td style="width:100%;"><b>Sampling No.</b> : '.$row['sampling_no'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Material Type :</b>'.$row['material_subtype'].'</td>
          <td style="width:50%;"><b>Grade :</b>'.$row['grade'].'</td>
         </tr>
         <tr>
        <td style="width:50%;"><b>GRN No. :</b>'.$row['grn_no'].'</td>
         <td style="width:50%;"><b>GRN Date :</b>'.$row['grn_date'].'</td>
        </tr>
        <tr>
        <td style="width:50%;"><b>Material Name :</b>'.$row['material_name'].'</td>
        <td style="width:50%;"><b>Material Code :</b>'.$row['material_code'].'</td>
        </tr>
        <tr>
        <td style="width:50%;"><b>Mfg Date :</b>'.$row['mfg_date'].'</td>
         <td style="width:50%;"><b>Exp Date :</b>'.$row['exp_date'].'</td>
        </tr>
        </table>
        <div></div>
        <table cellpadding="3" border="0.1">
        <tr  style="background-color:#87CEEB">
         <td style="width:50%;"><h3>Area CheckPoints :</h3></td>
          <td style="width:50%;"><h3>Balance Information :</h3></td>
        </tr>
        <tr>
          <td style="width:50%;">Temp(oC) :</td>
           <td style="width:20%;">Balance Id :</td>
            <td style="width:30%;">Date Of Calibration :</td>
        </tr>
         <tr>
          <td style="width:50%;">Relative Humidity :</td>
           <td style="width:50%;">Calibration Done By :</td>
           
        </tr>
         <tr>
          <td style="width:50%;">Cleaning Status of Area :</td>
            <td style="width:50%;">Calibration Checked By:</td>
         </tr>';
        }
        }
        $html.=' </table>
       
        <h3>Line Clearance CheckPoints :</h3>
        <table cellpadding="5" border="0.1">
      <tr style="background-color:#87CEEB">
      <td style="width:10%;text-align:center"><b>Sr.</b></td>
      <td style="width:35%;text-align:center"><b>CheckPoints</b></td>
      <td style="width:25%;text-align:center"><b>Status</b></td>
      <td style="width:30%;text-align:center"><b>Remark</b></td>
      </tr>
       <tr>
      <td style="width:10%"><b></b></td>
      <td style="width:35%"><b></b></td>
      <td style="width:25%"><b></b></td>
      <td style="width:30%"><b></b></td>
      </tr>
         </table>
         <div></div><div></div>
         <table cellpadding="3" border="0.1">
        <tr  style="background-color:#87CEEB">
         <td style="width:50%;"><h3>Previous Product Details :</h3></td>
          <td style="width:50%;"><h3>Line Clearance Details: </h3></td>
        </tr>
        <tr>
          <td style="width:50%;">Previous Product: :</td>
           <td style="width:25%;">Temp.:</td>
             <td style="width:25%;">Humidity :</td>
        </tr>
        <tr>
         <td style="width:50%;">Batch No./Lot No. :</td>
          <td style="width:50%;">Is Area Cleaned :</td>
         </tr>
         <tr>
          <td style="width:50%;">Area Cleaned By:</td>
           <td style="width:50%;">Previous Product Trace:</td>
            </tr>
           <tr>
             <td style="width:100%;">Clearance By:</td>
           </tr>
           </table>
           <h3>Sampling Information :</h3>
           <table cellpadding="5" borde="0.1">
           <tr  style="background-color:#87CEEB">
           <td style="width:50%;"><h4>Standard Sampling Qty: (From Specification)</h4></td>
             <td style="width:50%;"><h4>Reserve / Control Sample Qty:</h4></td>
           </tr>
           <tr>
           <td style="width:25%;">Indentification: </td>
           <td style="width:25%;">Composite:</td>
             <td style="width:50%;">Composite:</td>
           </tr>
           </table>
           <div></div>
           <table cellpadding="5" borde="0.1">
           <tr>
           <td style="width:50%;"><h4>Actual Sampling Qty:(Spec. Qty * 2 (Test, OOS))</h4></td>
             <td style="width:50%;"><h4>Total Sample Withdrawal Qty: (From Every Container)</h4></td>
           </tr>
           <tr>
           <td style="width:50%;">Indentification: </td>
           <td style="width:50%;">Indentification:</td>
            </tr>
           </table>
            <div></div>
            <table>
           <tr  style="background-color:#87CEEB">
           <td style="width:50%;text-align:center">Proper consignment label affixed</td>
            <td style="width:50%;text-align:center">Condition of Containers</td>
           </tr>
           </table>
          <table cellpadding="5" border="0.1">
           <tr>
           <td style="width:50%"></td>
            <td style="width:50%"></td>
           </tr>
           </table>
           <h4>No. of Containers To be Sampled:</h4>
           <table cellpadding="5" border="0.1">
           <tr  style="background-color:#87CEEB">
           <td style="width:25%;text-align:cenetr"> <b>Container No.</b></td>
              <td style="width:25%;text-align:cenetr"><b>Indentification Qty</b></td>
                 <td style="width:25%;text-align:cenetr"><b>Composite Qty</b></td>
                   <td style="width:25%;text-align:cenetr"><b>Status</b></td>
           </tr>
           <tr>
           <td style="width:25%;text-align:cenetr"> </td>
              <td style="width:25%;text-align:cenetr"></td>
                 <td style="width:25%;text-align:cenetr"></td>
                    <td style="width:25%;text-align:cenetr"></td>
                      </tr>
                     
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Samplings Log.pdf', 'I');
        
        
   } else if ($_GET["type"] == "downloadSamplingsDigital") {
        $_GET['filename'] = 'PACKING MATERIAL SAMPLE LOG'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
       
        $html.='
     <h3>Material Details:</h3>
        <table cellpadding="5" border="0.1">';
        $sql = "SELECT s.id,s.entry_date,s.sampling_no, s.containers, s.grn_no, s.grn_date, s.mfg_date, 
        s.exp_date, s.containers, s.material_code, s.batch_no, s.laf_start, s.clearance_status, 
        s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, 
        m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit, s.status 
        FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 
        ON s.material_code=s1.material_code WHERE m.material_type='Packing Material' 
        AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' ";//AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.=' <tr>
        <td style="width:100%;"><b>Sampling No.</b> : '.$row['sampling_no'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Material Type :</b>'.$row['material_subtype'].'</td>
          <td style="width:50%;"><b>Grade :</b>'.$row['grade'].'</td>
         </tr>
         <tr>
        <td style="width:50%;"><b>GRN No. :</b>'.$row['grn_no'].'</td>
         <td style="width:50%;"><b>GRN Date :</b>'.$row['grn_date'].'</td>
        </tr>
        <tr>
        <td style="width:50%;"><b>Material Name :</b>'.$row['material_name'].'</td>
        <td style="width:50%;"><b>Material Code :</b>'.$row['material_code'].'</td>
        </tr>
        <tr>
        <td style="width:50%;"><b>Mfg Date :</b>'.$row['mfg_date'].'</td>
         <td style="width:50%;"><b>Exp Date :</b>'.$row['exp_date'].'</td>
        </tr>
        </table>
        <div></div>
        <table cellpadding="3" border="0.1">
        <tr  style="background-color:#87CEEB">
         <td style="width:50%;"><h3>Area CheckPoints :</h3></td>
          <td style="width:50%;"><h3>Balance Information :</h3></td>
        </tr>
        <tr>
          <td style="width:50%;">Temp(oC) :</td>
           <td style="width:20%;">Balance Id :</td>
            <td style="width:30%;">Date Of Calibration :</td>
        </tr>
         <tr>
          <td style="width:50%;">Relative Humidity :</td>
           <td style="width:50%;">Calibration Done By :</td>
           
        </tr>
         <tr>
          <td style="width:50%;">Cleaning Status of Area :</td>
            <td style="width:50%;">Calibration Checked By:</td>
         </tr>';
        }
        }
        $html.=' </table>
       
        <h3>Line Clearance CheckPoints :</h3>
        <table cellpadding="5" border="0.1">
      <tr style="background-color:#87CEEB">
      <td style="width:10%;text-align:center"><b>Sr.</b></td>
      <td style="width:35%;text-align:center"><b>CheckPoints</b></td>
      <td style="width:25%;text-align:center"><b>Status</b></td>
      <td style="width:30%;text-align:center"><b>Remark</b></td>
      </tr>
       <tr>
      <td style="width:10%"><b></b></td>
      <td style="width:35%"><b></b></td>
      <td style="width:25%"><b></b></td>
      <td style="width:30%"><b></b></td>
      </tr>
         </table>
         <div></div><div></div>
         <table cellpadding="3" border="0.1">
        <tr  style="background-color:#87CEEB">
         <td style="width:50%;"><h3>Previous Product Details :</h3></td>
          <td style="width:50%;"><h3>Line Clearance Details: </h3></td>
        </tr>
        <tr>
          <td style="width:50%;">Previous Product: :</td>
           <td style="width:25%;">Temp.:</td>
             <td style="width:25%;">Humidity :</td>
        </tr>
        <tr>
         <td style="width:50%;">Batch No./Lot No. :</td>
          <td style="width:50%;">Is Area Cleaned :</td>
         </tr>
         <tr>
          <td style="width:50%;">Area Cleaned By:</td>
           <td style="width:50%;">Previous Product Trace:</td>
            </tr>
           <tr>
             <td style="width:100%;">Clearance By:</td>
           </tr>
           </table>
           <h3>Sampling Information :</h3>
           <table cellpadding="5" borde="0.1">
           <tr  style="background-color:#87CEEB">
           <td style="width:50%;"><h4>Standard Sampling Qty: (From Specification)</h4></td>
             <td style="width:50%;"><h4>Reserve / Control Sample Qty:</h4></td>
           </tr>
           <tr>
           <td style="width:25%;">Indentification: </td>
           <td style="width:25%;">Composite:</td>
             <td style="width:50%;">Composite:</td>
           </tr>
           </table>
           <div></div>
           <table cellpadding="5" borde="0.1">
           <tr>
           <td style="width:50%;"><h4>Actual Sampling Qty:(Spec. Qty * 2 (Test, OOS))</h4></td>
             <td style="width:50%;"><h4>Total Sample Withdrawal Qty: (From Every Container)</h4></td>
           </tr>
           <tr>
           <td style="width:50%;">Indentification: </td>
           <td style="width:50%;">Indentification:</td>
            </tr>
           </table>
            <div></div>
            <table>
           <tr  style="background-color:#87CEEB">
           <td style="width:50%;text-align:center">Proper consignment label affixed</td>
            <td style="width:50%;text-align:center">Condition of Containers</td>
           </tr>
           </table>
          <table cellpadding="5" border="0.1">
           <tr>
           <td style="width:50%"></td>
            <td style="width:50%"></td>
           </tr>
           </table>
           <h4>No. of Containers To be Sampled:</h4>
           <table cellpadding="5" border="0.1">
           <tr  style="background-color:#87CEEB">
           <td style="width:25%;text-align:cenetr"> <b>Container No.</b></td>
              <td style="width:25%;text-align:cenetr"><b>Indentification Qty</b></td>
                 <td style="width:25%;text-align:cenetr"><b>Composite Qty</b></td>
                   <td style="width:25%;text-align:cenetr"><b>Status</b></td>
           </tr>
           <tr>
           <td style="width:25%;text-align:cenetr"> </td>
              <td style="width:25%;text-align:cenetr"></td>
                 <td style="width:25%;text-align:cenetr"></td>
                    <td style="width:25%;text-align:cenetr"></td>
                      </tr>
                     
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Samplings digital.pdf', 'I');
        
   }else if ($_GET["type"] == "downloadAllocationLog") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center;color:brown">Packing Material Allocation of Sampling Log</h2>
        <table cellpadding="5" border="0.1">
        <tr style="background-color:#DDDAD9">
        <td style="width:5%;text-align:center"><b>Sr</b></td>
           <td style="width:20%;text-align:center"><b>Material Name</b></td>
              <td style="width:20%;text-align:center"><b>Material Type</b></td>
                 <td style="width:15%;text-align:center"><b>Material Code</b></td>
                    <td style="width:10%;text-align:center"><b>Grade</b></td>
                       <td style="width:15%;text-align:center"><b>GRN No.</b></td>
                          <td style="width:15%;text-align:center"><b>GRN Date</b></td>
                  </tr>';
                 $i=1;
                   $sql = "SELECT s.*, m.material_subtype, m.material_name, m.grade FROM sampling s 
        LEFT JOIN material m ON s.material_code=m.material_code
         WHERE s.status !='pending' AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $html.=' <tr>
            <td style="width:5%;text-align:center">'.$i.'</td>
           <td style="width:20%">'.$row['material_name'].'</td>
              <td style="width:20%">'.$row['material_subtype'].'</td>
                 <td style="width:15%">'.$row['material_code'].'</td>
                    <td style="width:10%">'.$row['grade'].'</td>
                       <td style="width:15%">'.$row['grn_no'].'</td>
                          <td style="width:15%">'.$row['entry_date'].'</td>
        </tr>';
        $i++;
            }
        }
        
     $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Samplings Log.pdf', 'I');
    
        
        
        
    }else if ($_GET["type"] == "downloadSamplingsLog") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center;color:brown">Packing Material Sample Log</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:5%; text-align:center;"><b>Sr.</b></td>
                <td style="width:13%; text-align:center;"><b>Date</b></td>
                <td style="width:10%; text-align:center;"><b>Sampling No.</b></td>
                <td style="width:17%; text-align:center;"><b>Material Type</b></td>
                <td style="width:10%; text-align:center;"><b>Material Code</b></td>
                <td style="width:15%; text-align:center;"><b>Material Name</b></td>
                <td style="width:10%; text-align:center;"><b>Batch No.</b></td>
                <td style="width:10%; text-align:center;"><b>Containers</b></td>
                <td style="width:10%; text-align:center;"><b>GRN No.</b></td>
            </tr>';
              $i=1;
             $sql = "SELECT s.id,s.entry_date,s.sampling_no, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit, s.status 
        FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 
        ON s.material_code=s1.material_code WHERE m.material_type='Packing Material'   AND testing_status = 'active'
        AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' ";//AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
    //      $sql = "SELECT * FROM sampling WHERE   testing_status = 'active' AND material_code LIKE 'PM%' 
    //  ORDER BY id DESC";
        if ($result->num_rows > 0) {
           
            while ($row = $result->fetch_assoc()) {
                 $html.=' <tr>
                    <td style="width:5%;">'.$i.'</td>
                    <td style="width:13%;">'.$row['entry_date'].'</td>
                    <td style="width:10%;">'.$row['sampling_no'].'</td>
                    <td style="width:17%;">'.$row['material_type'].'</td>
                    <td style="width:10%;">'.$row['material_code'].'</td>
                    <td style="width:15%;">'.$row['material_name'].'</td>
                    <td style="width:10%;">'.$row['batch_no'].'</td>
                    <td style="width:10%;">'.$row['containers'].'</td>
                    <td style="width:10%;">'.$row['grn_no'].'</td>
            </tr>';
               $i++;
          }
          }
      $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Samplings Log.pdf', 'I');
    }



}

$conn->close();
?>