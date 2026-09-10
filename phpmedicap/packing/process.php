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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
//     if ($_GET["type"] == "getAwaitingForPacking") {
//         $output = array();
//         $fifo_method='';
//         $sql ="select param_value from software_customization where param_label ='Fifo Method' 
//         and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
//       $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
//             $fifo_method = $row["param_value"];
                
//             }
            
//         }
//         $lod_status='';
//         $assay_status='';
//               $sql = "SELECT *,a.id as a_id,b.product_name as pname ,b.id as b_id,
//   (SELECT COUNT(pm_receiving) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.pm_receiving='done' ) as pm_receiving_count

             
//              FROM mfg_work_order_hdr a LEFT
//                 JOIN batch_planning b on a.batch_plan_id=b.id left join product c 
//                 on b.product_code=c.product_code WHERE (a.pm_receiving='done' or a.pm_receiving=' ') and a.bpr_status='' HAVING    pm_receiving_count != '0' order by a.id desc";
       
//         $result = $conn->query($sql);

//         if ($result->num_rows > 0) {
//             while ($row = $result->fetch_assoc()) {
              
//                 $output[] = $row;
//             }
//         } 
        
        
//         echo json_encode($output);
//     } 
    if ($_GET["type"] == "getAwaitingForPacking") {
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' 
        and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
      $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
                     $sql = "SELECT *,a.id as a_id,b.product_name as pname FROM mfg_work_order_hdr a LEFT
                JOIN batch_planning b on a.batch_plan_id=b.id left join product c 
                on b.product_code=c.product_code WHERE a.pm_receiving='done' and a.bpr_status='' order by a.id desc";
      
       
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
              
              
                  $sql1 = "SELECT d.id, a.material_code ,a.material_type,b.material_name,a.batch_qty ,b.material_subtype
                            FROM
                                batch_planning_materials a
                            LEFT JOIN material b ON
                                a.material_code = b.material_code
                                 LEFT JOIN batch_planing_raw_material_hdr e ON
                      a.pm_hdr_id=e.id LEFT JOIN mfg_work_order_hdr  d ON e.batch_plan_id = d.batch_plan_id
                            WHERE
                                a.material_type = 'Packing Material' and a.batch_plan_id='".$row["batch_plan_id"]."' AND d.work_order_no = '".$row["work_order_no"]."'
                                GROUP by a.material_code,a.material_type,b.material_name,a.batch_qty,b.material_subtype,d.id";
            
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $category = $row1["category"];
                       
                       
                         $sql11 = "select actual_qtyy from mfg_work_order_dtl  where material_code = '".$row1["material_code"]."' AND work_order_id = '".$row1["id"]."'";
                      $result11 = $conn->query($sql11);
                        if ($result11->num_rows > 0) {
                            while ($row11 = $result11->fetch_assoc()) {
                                
                               
                                 $row1["actual_qtyy"] = $row11["actual_qtyy"];
                            }
                        }


                        $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2 = $row2;
                            }
                        }
                         $row1["gross_total"] = $output2["gross_total"];
                          $row1["net_total"] = $output2["net_total"];
                          $row1["tare_total"] = $output2["tare_total"];
                        $row1["available_ars"] = json_decode($output2["ars"]);
                        $row1["containers"] = json_decode($output2["containers"]);
                       
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "bmr_approve") {
        $sql=" update sp_bmr_sifting set bmr_status='Complete',bmr_completed_by='".$_GET["emp_id"]."'  where id='".$_GET["sift_id"]."'";
        // $sql=" update mfg_work_order_hdr set bmr_status='Complete',bmr_completed_by='".$_GET["emp_id"]."'  where id='".$_GET["work_id"]."'";
        // $sql=" update mfg_work_order_hdr set bpr_status='START' where batch_plan_id='".$_GET["batch_plan_id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Batch Started Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "send_bmr_check") {
        // $sql = "UPDATE bpr SET status='START', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql=" update sp_bmr_sifting set bmr_status='checking'  where id='".$_GET["sift_id"]."'";
        // $sql=" update mfg_work_order_hdr set bmr_status='checking'  where id='".$_GET["work_id"]."'";
        // $sql=" update mfg_work_order_hdr set bpr_status='START' where batch_plan_id='".$_GET["batch_plan_id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Batch Started Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "startPacking") {
        // $sql = "UPDATE bpr SET status='START', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql=" update mfg_work_order_hdr set bpr_status='START' ,receiving='done' where id='".$_GET["id"]."'";
        // $sql=" update mfg_work_order_hdr set bpr_status='START' where batch_plan_id='".$_GET["batch_plan_id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Batch Started Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "startPacking_sp") {
        // $sql = "UPDATE bpr SET status='START', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql=" update sp_bmr_sifting set bpr_status='START' ,receiving='done' where id='".$_GET["sift_id"]."'";
        // $sql=" update mfg_work_order_hdr set bpr_status='START' where batch_plan_id='".$_GET["batch_plan_id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Batch Started Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    // else if ($_GET["type"] == "startPacking_sp") {
    //     // $sql = "UPDATE bpr SET status='START', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
    //     $sql=" update mfg_work_order_hdr set bpr_status='START' ,receiving='done' where id='".$_GET["id"]."'";
    //     // $sql=" update mfg_work_order_hdr set bpr_status='START' where batch_plan_id='".$_GET["batch_plan_id"]."'";
    //     if ($conn->query($sql)) {
    //         echo json_encode(array("status"=>"success","msg"=>"Batch Started Successfully!"));
    //     } else {
    //         echo json_encode(array("status"=>"failed","msg"=>$conn->error));
    //     }
    // }
    else if ($_GET["type"] == "getStartedBatches") {
        $output = array();
        $sql = "SELECT l.*, p.dosage_form, p.product_name, p.grade, b1.packing_materials FROM bpr l LEFT JOIN product p ON l.product_code=p.product_code LEFT JOIN unitformula b1 ON l.mfr_no=b1.mfr_no WHERE l.status='START'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "completePacking") {
        $sql = "UPDATE bpr SET status='COMPLETED',primary_qty='".$input["primary_qty"]."', secondary_qty='".$input["secondary_qty"]."', tertiary_qty='".$input["tertiary_qty"]."', remark='".$input["remark"]."', complete_by='".$_GET["emp_id"]."', complete_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Packing Process Completed Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getCompletedBatches") {
        $output = array();
        $sql = "SELECT l.*, p.dosage_form, p.product_name, p.grade, b1.packing_materials FROM bpr l LEFT JOIN product p ON l.product_code=p.product_code LEFT JOIN unitformula b1 ON l.mfr_no=b1.mfr_no WHERE l.status='COMPLETED'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "getDisp_material") {
        
	$output = Array();
	
	
 	    $sql1 = "SELECT *,a.batch_qty as b_qty, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code  
                  LEFT JOIN mfg_work_order_dtl_pm_lots d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id  WHERE 
                   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$_GET["batch_plan_id"]."'  AND d.work_order_id = '".$_GET["work_order_id"]."' and d.sp_bmr_sifting_id='".$_GET["sift_id"]."'";
           
                   
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                                           	$output[] = $row1;

                }
            }

  
    // 		}
    // 	}
	
	echo json_encode($output);

    }
//         else if ($_GET["type"] == "getDisp_material") {
        
// 	$output = Array();
	
	
// 	   $sql1 = "SELECT d.id, a.material_code ,a.material_type,b.material_name,a.batch_qty ,b.material_subtype
//                             FROM
//                                 batch_planning_materials a
//                             LEFT JOIN material b ON
//                                 a.material_code = b.material_code
//                                  LEFT JOIN batch_planing_raw_material_hdr e ON
//                       a.pm_hdr_id=e.id LEFT JOIN mfg_work_order_hdr  d ON e.batch_plan_id = d.batch_plan_id
//                             WHERE
//                                 a.material_type = 'Packing Material' and a.batch_plan_id='".$_GET["batch_plan_id"]."' AND d.work_order_no = '".$_GET["work_order_no"]."'
//                                 GROUP by a.material_code,a.material_type,b.material_name,a.batch_qty,b.material_subtype,d.id";
                      
           
                   
//                 $result1 = $conn->query($sql1);
//                 if ($result1->num_rows > 0) {
//                     while ($row1 = $result1->fetch_assoc()) {
//                       $category = $row1["category"];
                       
                       
//                           $sql11 = "select actual_qtyy from mfg_work_order_dtl  where material_code = '".$row1["material_code"]."' AND work_order_id = '".$row1["id"]."'";
//                       $result11 = $conn->query($sql11);
//                         if ($result11->num_rows > 0) {
//                             while ($row11 = $result11->fetch_assoc()) {
                                
                               
//                                  $row1["actual_qtyy"] = $row11["actual_qtyy"];
//                             }
//                         }


//                         	$output[] = $row1;

//                 }
//             }

//     //  	  $sql = "SELECT d.actual_qtyy,a.material_code ,a.material_type,b.material_name,a.batch_qty ,b.material_subtype
//     //                         FROM
//     //                             batch_planning_materials a
//     //                         LEFT JOIN material b ON
//     //                             a.material_code = b.material_code
//     //                         LEFT JOIN stock_book c ON
//     //                             b.material_code = c.material_code
//     //                              LEFT JOIN mfg_work_order_dtl d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
//     //             e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id 
//     //                         WHERE
//     //                             a.material_type = 'Packing Material' and a.batch_plan_id='".$_GET["batch_plan_id"]."'
//     //                             GROUP by a.material_code,a.material_type,b.material_name,a.batch_qty,b.material_subtype,d.actual_qtyy";
                                
                                          
                   
    	
//     // 	$result = $conn->query($sql);
//     // 	if($result->num_rows > 0){
//     // 		while($row = $result->fetch_assoc()){
//     // 			$output[] = $row;
//     // 		}
//     // 	}
	
// 	echo json_encode($output);

//     }

    else if ($_GET["type"] == "SAVE_bmr_Equipment_data") {    
        $json_obj = json_encode($input["pk_equip_list"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $input)
                {
              $sql = "INSERT INTO pk_bmr_Equipment_data( plant_id, capacity,equipment_code,equipment_name,work_order_id,sp_bmr_sifting_id) VALUES ('".$_GET["plant_id"]."','".$input["capacity"]."','".$input["equipment_code"]."','".$input["equipment_name"]."','".$_GET["work_id"]."','".$_GET["sift_id"]."')";

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
        }
    else if ($_GET["type"] == "savepacking_quality_sample_check") {    
        $json_obj = json_encode($input["sample_checks"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $input)
                {
              $sql = "INSERT INTO packing_quality_sample_check( work_order_id,parameter,remark,sp_bmr_sifting_id) VALUES ('".$_GET["work_id"]."','".$input["parameter"]."','".$input["remarkvalue"]."','".$_GET["sift_id"]."')";

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
        }
    else if ($_GET["type"] == "savepacking_Final_quality_sample_check") {    
 
          $sql = "INSERT INTO bmr_final_batch_plan( work_order_id,units_checked,defect_observed,deviation,sp_bmr_sifting_id) VALUES ('".$_GET["work_id"]."','".$input["units_checked"]."','".$input["defect_observed"]."','".$input["deviation"]."','".$_GET["sift_id"]."')";

        if ($conn->query($sql)) {

            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "SAVE_primary_packing_section") {
        
            $sql = "INSERT INTO primary_packing( work_order_id, Filling_qty, packing_type, plant_id,sp_bmr_sifting_id	) 
                    VALUES ('".$_GET["work_id"]."','".$input["Filling_qty"]."','".$input["packing_type"]."','".$_GET["plant_id"]."','".$_GET["sift_id"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
         $json_obj = json_encode($input["primary_pk"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $input)
                {
              $sql = "INSERT INTO primary_packing_dtl( primary_packing_id, start_time,start_date,end_time,end_date, pack_size, unit_packed, unit_damage, pack_material_losss,prepare_by)
                        VALUES ('$product_id','".$input["start_time"]."','".$input["start_date"]."','".$input["end_time"]."','".$input["end_date"]."','".$input["pack_size"]."','".$input["unit_packed"]."','".$input["unit_damage"]."','".$input["pack_material_losss"]."','".$_GET["emp_id"]."')";

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
        
    }
    }
    else if ($_GET["type"] == "SAVE_secondary_packing_section") {
        $date = date('Y-m-d');
        
            $sql = "INSERT INTO secondary_packing( work_order_id, Filling_qty, packing_type, plant_id,sp_bmr_sifting_id	) 
                    VALUES ('".$_GET["work_id"]."','".$input["Filling_qty"]."','".$input["packing_type"]."','".$_GET["plant_id"]."','".$_GET["sift_id"]."')";
         if ($conn->query($sql)) {
             
             $product_id = $conn->insert_id;
         $json_obj = json_encode($input["secondary_pk"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $input)
                {
              $sql = "INSERT INTO secondary_packing_dtl( secondary_packing_id, start_time,start_date,end_time,end_date, pack_size, unit_packed, unit_damage, pack_material_losss,prepare_by)
                        VALUES ('$product_id','".$input["start_time"]."','".$input["start_date"]."','".$input["end_time"]."','".$input["end_date"]."','".$input["pack_size"]."','".$input["unit_packed"]."','".$input["unit_damage"]."','".$input["pack_material_losss"]."','".$_GET["emp_id"]."')";

        if ($conn->query($sql)) {
              $sql1 = "update sp_bmr_sifting set qc_intimation='sent' , qc_intimation_raised_by='".$_GET["emp_id"]."' ,qc_intimation_raised_date='$date' where id='".$_GET["sift_id"]."'";
            //  $sql1 = "update mfg_work_order_hdr set qc_intimation='sent' , qc_intimation_raised_by='".$_GET["emp_id"]."' ,qc_intimation_raised_date='$date' where id='".$_GET["work_id"]."'";
             $conn->query($sql1);
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
        
    }
    }
      else if ($_GET["type"] == "getReadyBatchPlans_pk_sp_intimation") {
        $output = Array();
       $sql = "SELECT
                     
                        a.id,
                        a.batch_number,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.batch_plan_id,
                        a.stage_checked_by,
                        a.tr_to_packing_dept_by,
                        a.batch_complete_date,
                        a.stability,
                        a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
                        a.no_of_lots,
                        uf.min_per AS min_yeild,
                        uf.max_per AS max_yeild,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,
                        p.product_name,
                        p.product_type,
                        p.grade,
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        (SELECT COUNT(qc_intimation) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.qc_intimation='sent') as qc_intimation_count
                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                    LEFT JOIN samplingfg s ON
                        b.product_code = s.product_code
             where  a.plant_id ='".$_GET["plant_id"]."'
             and (dispensing_status ='Request Sent' or dispensing_status =' ')   and (a.receiving='done' or a.receiving=' ') and (a.bpr_status='START' or a.bpr_status=' ')   HAVING    qc_intimation_count != '0'";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        // $sql1 = "SELECT * FROM salary_head_details WHERE salary_hdr_id='".$row["id"]."' and salary_head_group='Earnings' ";
                        
                        // $result1 = $conn->query($sql1);
                        // if ($result1->num_rows > 0) {
                        //     while ($row1 = $result1->fetch_assoc()) {
                        //         $output1[] = $row1;
                        //     }
                        // }
                        // $output2 = Array();
                        // $sql1 = "SELECT * FROM salary_head_details WHERE salary_hdr_id='".$row["id"]."' and salary_head_group='CTC Calculation' ";
                        //   $result2 = $conn->query($sql1);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {
                        //         $output2[] = $row2;
                        //     }
                        // }
                        // $output3 = Array();
                        // $sql1 = "SELECT * FROM salary_head_details WHERE salary_hdr_id='".$row["id"]."' and salary_head_group='Deductions' ";
                        //   $result3 = $conn->query($sql1);
                        // if ($result3->num_rows > 0) {
                        //     while ($row3 = $result3->fetch_assoc()) {
                        //         $output3[] = $row3;
                        //     }
                        // }
                        // $row["Deductions"] = $output3;
                        // $row["CTC Calculation"] = $output2;
                        // $row["Earnings"] = $output1;
                        $output[] = $row;
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
    }

}

$conn->close();
?>