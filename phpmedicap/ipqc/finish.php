<?php
    require '../db.php';
    require '../token.php';
    
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);

    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
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
    
    if ($_GET["type"] == "getIntimationLog") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s 
        LEFT JOIN product p ON s.product_code=p.product_code ORDER BY s.id DESC";
        
        //WHERE DATE(s.prepared_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          $sql1 = "SELECT * FROM spec_tests WHERE user_no='".$_GET["user_no"]."' AND   specification_no='".$row["sampling_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                //if (count($output1) > 0) {
                    $row1["spec_tests"] = $output1;
                    $output[] = $row;
                //}
            }
        }
        echo json_encode($output);
          }
    //       else if ($_GET["type"] == "getPendingAQAApproval") {
    //     $output = array();
    //     // $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type 
    //     // FROM samplingfg s 
    //     // LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='approve' 
    //     // AND s.intimation_status='receive ORDER BY s.id DESC";
    //   echo  $sql="SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='inprocess' or s.status='Approved' and s.intimation_status!=''";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //     // $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code
    //     //         where t.status='Approved'";
    //         // $sql="    SELECT * FROM samplingfg a left join product b on a.product_code=b.product_code where a.status='Approved' order by 1 desc";
    //     // $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t 
    //     // JOIN material m ON
    //     // t.material_code=m.material_code AND t.status='Approved'";
    //     $output = Array();
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row["chemical_analysis"] = $row1["chemical_analysis"];
    //                     $row["microbiology_analysis"] = $row1["microbiology_analysis"];
    //                     $row["reserve_analysis"] = $row1["reserve_analysis"];
    //                     $row["sample_qty"] = $row1["total_qty"];
    //                 }
    //             } else {
    //                 $row["chemical_analysis"] = 0;
    //                 $row["microbiology_analysis"] = 0;
    //                 $row["reserve_analysis"] = 0;
    //                 $row["sample_qty"] = 0;
    //             }
                
    //               $sql2 = "SELECT * FROM testing_tests where testing_no='".$row["sampling_no"]."'";
    //             $result2 = $conn->query($sql2);
    //             if ($result2->num_rows > 0) {
    //                 while ($row2 = $result2->fetch_assoc()) {
                
    //             $output[] = $row;
    //         }
    //     }
    //         }
    //     }
    //     echo json_encode($output);
    // } 
       
        
        
    //       }
          else if ($_GET["type"] == "saveReleaseStatus") {
              
     $sql = "UPDATE testing_tests  SET observation='".$input["observation"]."' , status='AQAA Aproved',release_status='".$_GET["release_status"]."' WHERE specification_no='".$_GET["specification_no"]."'";
    // echo $sql = "UPDATE testing_tests  SET observation='".$input["observation"]."' and status='".$_GET["status"]."' WHERE specification_no='".$_GET["specification_no"]."'";
  if ($conn->query($sql)) {
      
      
      $sql1="UPDATE samplingfg set status='AQAA Aproved' where specification_no='".$_GET["specification_no"]."'";
      
      
      
   $conn->query($sql1);
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    
          }
          else if ($_GET["type"] == "saveCOAApproval") {
              
    echo $sql = "UPDATE testing_tests  SET observation='".$input["observation"]."' , status='COAA Aproved',release_status='".$_GET["release_status"]."' WHERE specification_no='".$_GET["specification_no"]."'";
    // echo $sql = "UPDATE testing_tests  SET observation='".$input["observation"]."' and status='".$_GET["status"]."' WHERE specification_no='".$_GET["specification_no"]."'";
  if ($conn->query($sql)) {
      
      
    echo  $sql1="UPDATE samplingfg set status='COAA Aproved' where specification_no='".$_GET["specification_no"]."'";
      
      
      
   $conn->query($sql1);
            
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
      
    
          }
          else if ($_GET["type"] == "getPendingAQAApproval") {
        $output = Array();
        //  $sql = "SELECT   t.*, 
        //  (select material_name from master_material  where material_code = t.material_code limit 1)  as material_name,
        //  (select grade from master_material  where material_code = t.material_code limit 1)  as grade ,
        //  (select grade from master_material  where material_code = t.material_code limit 1)  as grades ,
        //  (select GROUP_CONCAT(grade.grade) from grade where grade.id  in( grades)) as gradeName ,
        //  (select specification_no from specification  where specification.material_code = t.material_code limit 1)  as specification_no ,
         
        //  (select vendor_coa from challan_materials  where challan_materials.grn_no = t.grn_no limit 1)  as vendor_coa  ,
        //  (select ar_no from sampling  where t.grn_no = sampling.grn_no limit 1)  as arno  
         
        //  FROM testing t
        //  where t.status='pending' having  (vendor_coa='NO') order by 1 desc";
              $sql="SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='inprocess' or s.status='Approved' and s.intimation_status!=''";


         
          
        $result = $conn->query($sql);
         
       
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["ar_no"] = $row["arno"];
                $output1 = Array();
               $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
                     

                 
                
                $result2 = $conn->query($sql1);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                   $row["chemical_analysis"] = $row2["chemical_analysis"];
                        $row["microbiology_analysis"] = $row2["microbiology_analysis"];
                        $row["reserve_analysis"] = $row2["reserve_analysis"];
                        $row["sample_qty"] = $row2["total_qty"];
                    }
                }

                $output2 =  Array();
                 $sql3 = "SELECT * ,t.result as t_result FROM testing_tests t where specification_no='".$row["specification_no"]."' and status='inprocess' ";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {

                        $output2[] = $row3;
                       
                       
                    }
                }

                $row["tests"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);}
    //       else if ($_GET["type"] == "getPendingAQAApproval") {
    //     $output = array();
    //     // $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s 
    //     // LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='approve' 
    //     // AND s.intimation_status='receive' ORDER BY s.id DESC";
    //   $sql="SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='inprocess' or s.status='Approved' and s.intimation_status!=''";

    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row["chemical_analysis"] = $row1["chemical_analysis"];
    //                     $row["microbiology_analysis"] = $row1["microbiology_analysis"];
    //                     $row["reserve_analysis"] = $row1["reserve_analysis"];
    //                     $row["sample_qty"] = $row1["total_qty"];
    //                 }
    //             } else {
    //                 $row["chemical_analysis"] = 0;
    //                 $row["microbiology_analysis"] = 0;
    //                 $row["reserve_analysis"] = 0;
    //                 $row["sample_qty"] = 0;
    //             }
                
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
        
        
        
    // }
          else if ($_GET["type"] == "getPendingCOAApproval") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s 
        LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='AQAA Aprov' 
        AND s.intimation_status!='' ORDER BY s.id DESC";
        // $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s 
        // LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='approve' 
        // AND s.intimation_status='receive' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   $row["ar_no"] = $row["arno"];
                $output1 = Array();
               $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
                     

                 
                
                $result2 = $conn->query($sql1);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                   $row["chemical_analysis"] = $row2["chemical_analysis"];
                        $row["microbiology_analysis"] = $row2["microbiology_analysis"];
                        $row["reserve_analysis"] = $row2["reserve_analysis"];
                        $row["sample_qty"] = $row2["total_qty"];
                    }
                }

                $output2 =  Array();
                 $sql3 = "SELECT * ,t.result as t_result FROM testing_tests t where specification_no='".$row["specification_no"]."' and status='AQAA Aproved' ";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {

                        $output2[] = $row3;
                       
                       
                    }
                }

                $row["tests"] = $output2;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
        
    }
    else if ($_GET["type"] == "getPendingQAIntimations") {
        $output = array();
       $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.dosage_form FROM 
        samplingfg s 
        LEFT JOIN product p ON s.product_code=p.product_code ";
        // WHERE s.status='approve' AND s.intimation_status='pending' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingQAIntimations_saipro") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.dosage_form,c.lot FROM 
        samplingfg s 
        LEFT JOIN product p ON s.product_code=p.product_code  left join sp_bmr_sifting c on s.sp_bmr_sifting_id=c.id
        WHERE  s.intimation_status='pending' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "receiveIntimation") {
        $sql = "UPDATE samplingfg SET intimation_status='receive', received_by='".$_GET["emp_id"]."', received_date='$entry_date'
        WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingWithdrawals") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type 
        FROM samplingfg s 
        LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='approve' AND s.intimation_status='receive' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["chemical_analysis"] = $row1["chemical_analysis"];
                        $row["microbiology_analysis"] = $row1["microbiology_analysis"];
                        $row["reserve_analysis"] = $row1["reserve_analysis"];
                        $row["sample_qty"] = $row1["total_qty"];
                    }
                } else {
                    $row["chemical_analysis"] = 0;
                    $row["microbiology_analysis"] = 0;
                    $row["reserve_analysis"] = 0;
                    $row["sample_qty"] = 0;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "withdrawSample") {
        $sql = "UPDATE samplingfg SET intimation_status='withdraw',chemical_analysis='".$input["chemical_analysis"]."', microbiology_analysis='".$input["microbiology_analysis"]."', reserve_analysis='".$input["reserve_analysis"]."', additional_sample='".$input["additional_sample"]."', total_qty='".$input["total_qty"]."', withdrawal_by='".$_GET["emp_id"]."', withdrawal_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingTestings") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type
        FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='approve' 
        AND s.intimation_status='withdraw' AND s.testing_status='pending'";// ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no= 
                (SELECT specification_no FROM specification WHERE spec_type LIKE '%Finish Product%' 
                AND product_code='".$row["product_code"]."')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $row["isspecification"] = "yes";
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["tests"] = $output1;
                } else {
                    $row["isspecification"] = "no";
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTestingReport") {
        $id = 0;
        $sql = "SELECT COUNT(id) as id FROM samplingfg WHERE ar_no=''";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = +$row["id"];
            }
        }
        $id++;
        $ar_no = "AR0".$id;
        $sql = "UPDATE samplingfg SET ar_no='".$ar_no."',tests='".json_encode($input["tests"])."', remark='".$input["remark"]."', testing_by='".$_GET["emp_id"]."', testing_date='$entry_date', testing_status='inprocess' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getInprocessTestings") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type 
        FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.status='approve' 
        AND s.intimation_status='withdraw' AND s.testing_status='inprocess' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveTesting") {
        $sql = "UPDATE samplingfg SET testing_status='".$_GET["status"]."', testing_check_by='".$_GET["emp_id"]."', testing_check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTestingLog") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type 
        FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.testing_status='approve' 
        ORDER BY s.id DESC";
        //AND DATE(s.prepared_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    // else if ($_GET["type"] == "getPendingBatchRelease") {
    //     $output1 = Array();
    //     $sql1 = "SELECT f.*, p.product_type, p.product_name, p.grade FROM samplingfg f LEFT JOIN product p ON f.product_code=p.product_code WHERE f.testing_status='approve' AND f.release_Status='pending'";
    //     $result1 = $conn->query($sql1);
    //     if ($result1->num_rows > 0) {
    //         while ($row1 = $result1->fetch_assoc()) {
    
    //             $sql2 = "SELECT * FROM batch_checklist WHERE product_code='".$row1["product_code"]."'";
    //             $result2 = $conn->query($sql2);
    //             if ($result2->num_rows > 0) {
    //                 while ($row2 = $result2->fetch_assoc()) {
    //                     $checkpoints = json_decode($row2["checkpoints"]);
    //                     $output2 = Array();
    //                     for ($i = 0; $i < count($checkpoints); $i++) {
    //                         $temp = Array();
    //                         $temp["checkpoint"] = $checkpoints[$i];
    //                         $temp["status"] = "";
    //                         $temp["remark"] = "";
    //                         $output2[] = $temp;
    //                     }
    //                     $row1["checklist"] = $output2;
    //                     break;
    //                 }
    //                 $row1["ischecklist"] = "yes";
    //             } else {
    //                 $row1["ischecklist"] = "no";
    //             }
    //             $output1[] = $row1;
    //         }
    //     }
    //     echo json_encode($output1);
    // }
    else if ($_GET["type"] == "getdosagechklist") {
        
        $output = Array();
        $sql = "SELECT a.* FROM mst_batch_checklist a left JOIN batch_checklist b on a.chklist_id=b.id WHERE dosage_form='".$_GET["dosage_form"]."' order by id desc";
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    }else if ($_GET["type"] == "saveChecklist") {
    $sql = "INSERT INTO batch_checklist (dosage_form,dosage_type, product_code,  entry_by, entry_date,plant_id) 
    VALUES ('".$input["dosage_form"]."','".$input["dosage_type"]."', '".$input["product_code"]."',
    '".$_GET["emp_id"]."', '$entry_date', '".$_GET["plant_id"]."')";
   
 if ($conn->query($sql)) {
    	
        $last_id = $conn->insert_id;

        foreach($input['checkpoints'] as $input){
    		
    	$sql="INSERT INTO mst_batch_checklist (chklist_id ,checkpoints)
                                value(".$last_id.",
                                      '".$input["checkpoint"]."')";
    	 $conn->query($sql);
        }
    
    
         echo "{\"status\":\"success\"}";	
    	} else {
        echo "{\"status\":\"failed\"}";
    }
}
    else if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product GROUP BY product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT s.*,v.vendor_name FROM stock_book s LEFT JOIN vendor v ON s.vendor_no=v.vendor_no 
                WHERE s.user_no='".$_GET["user_no"]."' AND s.product_code='".$row["product_code"]."' 
                AND s.status IN ('Under Test','Approved')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $used_qty = 0;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM material_issue WHERE ar_no='".$row1["ar_no"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                                $issue_qty += +$row2["qty"];
                                $used_qty += +$row2["qty"];
                            }
                        }
                       $row1["issued"] = $output2;
                        $row1["issue_qty"] = $used_qty;
                        $balance_qty = +$row1["qty"] - $used_qty;
                        $balance_qty = round($balance_qty, 2);
                        $row1["balance_qty"] = $balance_qty;
                        $row1["issues"] = $output2;
                        $output1[] = $row1;
                        
                        $received_qty += +$row1["qty"];
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["grns"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveBatchRelease") {
        $sql = "UPDATE samplingfg SET checkpoints='".json_encode($input["checklist"])."', release_status='".$input["remark"]."', release_by='".$_GET["emp_id"]."', release_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    // else if ($_GET["type"] == "getBatchReleaseLog") {
    //     $output = Array();
    //     $sql = "SELECT * FROM product";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $received_qty = 0;
    //             $issue_qty = 0;
    //             $balance_qty = 0;
    //             $output1 = array();
    //             $sql1 = "SELECT s.*,v.vendor_name FROM stock_book s LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.product_code='".$row["product_code"]."' AND s.status IN ('Under Test','Approved')";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {

    //                     $used_qty = 0;
                        
    //                     $output2 = array();
    //                     $sql2 = "SELECT * FROM material_issue WHERE ar_no='".$row1["ar_no"]."' AND status='approve'";
    //                     $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output2[] = $row2;
    //                             $issue_qty += +$row2["qty"];
    //                             $used_qty += +$row2["qty"];
    //                         }
    //                     }
    //                     $row1["issued"] = $output2;
    //                     $row1["issue_qty"] = $used_qty;
    //                     $balance_qty = +$row1["qty"] - $used_qty;
    //                     $balance_qty = round($balance_qty, 2);
    //                     $row1["balance_qty"] = $balance_qty;
    //                     $row1["issues"] = $output2;
    //                     $output1[] = $row1;
                        
    //                     $received_qty += +$row1["qty"];
    //                 }
    //                 $balance_qty = $received_qty - $issue_qty;
    //                 $balance_qty = round($balance_qty, 2);
    //                 $row["received_qty"] = $received_qty;
    //                 $row["issue_qty"] = $issue_qty;
    //                 $row["balance_qty"] = $balance_qty;
    //                 $row["grns"] = $output1;
    //                 $output[] = $row;
    //             }
    //         }
    //     }
    //     echo json_encode($output);
    // }
    // else if($_GET["type"] == "getBatchReleaseLog") {
    //     $output = Array();
    //     $sql = "SELECT b.*, DATE(b.release_date) as release_date, p.product_name, p.grade, p.product_type FROM samplingfg b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.release_status !='pending' AND p.product_type LIKE '%".$_GET["product_type"]."%' AND DATE(b.release_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND b.company_unit LIKE '%".$_GET["company_unit"]."%'";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $row["checkpoints"] = json_decode($row["checkpoints"]);
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    
        
        else if($_GET["type"] == "getBatchReleaseLog") {
        $output = Array();
        // $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM stock_book b LEFT JOIN product p 
        // ON b.product_code=p.product_code WHERE b.product_code!=''";
        $sql = "SELECT * FROM batch_release a left JOIN fg_stock_book b on a.product_code=b.material_code LEFT JOIN product c on a.product_code=c.product_code WHERE a.status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        } else if ($_GET["type"] == "downloadIntimationRecord") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Requests Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
       
         $html.='<h2 style="color:brown;">Sampling Details:</h2>';
          
          $html.=' 
          <table cellpadding="8" border="0.1">';
       
       $sql = "SELECT s.*,  DATE(s.prepared_date) as prepared_date,p.product_name, p.grade, p.product_type FROM samplingfg s 
        LEFT JOIN product p ON s.product_code=p.product_code ";//ORDER BY s.id 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        
       $html.='  <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Product Name</b></td>
           <td style="width:30%">'.$row['product_name'].'</td>
            <td style="width:20%;background-color:#DDDAD9;"><b>Product Grade</b></td>
           <td style="width:30%">'.$row['grade'].'</td>
         </tr>
         <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Lot/ Batch No.</b></td>
           <td style="width:30%">'.$row['batch_no'].'</td>
            <td style="width:20%;background-color:#DDDAD9;"><b>Lot /Batch Qty</b></td>
           <td style="width:30%">'.$row['batch_qty'].'</td>
         </tr>
         <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>MFG Date</b></td>
           <td style="width:30%">'.$row['mfg_date'].'</td>
            <td style="width:20%;background-color:#DDDAD9;"><b>Exp Date</b></td>
           <td style="width:30%">'.$row['exp_date'].'</td>
         </tr>
         <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Prepare By</b></td>
           <td style="width:30%">'.$row['prepared_by'].'</td>
            <td style="width:20%;background-color:#DDDAD9;"><b>Check By</b></td>
           <td style="width:30%">'.$row['checked_by'].'</td>
         </tr>
         <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Receive Date</b></td>
           <td style="width:30%">'.$row['received_date'].'</td>
            <td style="width:20%;background-color:#DDDAD9;"><b>Receive by</b></td>
           <td style="width:30%">'.$row['received_by'].'</td>
         </tr>
         <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Withdrawal Date</b></td>
           <td style="width:30%">'.$row['withdrawal_date'].'</td>
            <td style="width:20%;background-color:#DDDAD9;"><b>Withdrawal by</b></td>
           <td style="width:30%">'.$row['withdrawal_by'].'</td>
         </tr>';
          $html.=' </table>';
            $html.='
         <h2 style="color:brown;">Sample Qty:</h2>
         <table cellpadding="5" border="0.1">
         <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Chemical Analysis</b></td>
          <td style="width:80%">'.$row['chemical_analysis'].'</td>
         </tr>
          <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Microbiology Analysis</b></td>
          <td style="width:80%">'.$row['microbiology_analysis'].'</td>
         </tr>
          <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Reserse Qty</b></td>
          <td style="width:80%">'.$row['reserve_analysis'].'</td>
         </tr>
          <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Additionnal Sample Qty</b></td>
          <td style="width:80%">'.$row['additional_sample'].'</td>
         </tr>
          <tr>
         <td style="width:20%;background-color:#DDDAD9;"><b>Total Qty</b></td>
          <td style="width:80%">'.$row['total_qty'].'</td>
         </tr>';
            
        
         $html.=' </table>
         <h2 style="color:brown;">Tests:</h2>
         <table cellpadding="5" border="0.1">
          <tr style="background-color:#DDDAD9;">
         <td style="width:10%;text-align:center"><b>Sr No.</b></td>
          <td style="width:25%;text-align:center"><b>Test</b></td>
           <td style="width:25%;text-align:center"><b>Subtest</b></td>
            <td style="width:20%;text-align:center"><b>Limits</b></td>
             <td style="width:20%;text-align:center"><b>Result</b></td>
         </tr>';
         
         $sql1 = "SELECT * FROM spec_tests WHERE user_no='".$_GET["user_no"]."' AND   specification_no	='".$row["sampling_no"]."'";
                $output1 = Array();
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                  $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
         $html.='  <tr>
         <td style="width:10%;text-align:center">'.$counter++.'</td>
          <td style="width:25%;text-align:center">'.$row1['test'].'</td>
           <td style="width:25%;text-align:center">'.$row1['subtest'].'</td>
            <td style="width:20%;text-align:center">'.$row1['limits'].'</td>
             <td style="width:20%;text-align:center">'.$row1[''].'</td>
         </tr>';
                    }
                }
         $html.='  </table><div></div><div></div><div></div><div></div>
          <table cellpadding="5" border="0.1">
                <tr style="text-align:center;background-color:#DDDAD9;">
                 <td style="width:25%"></td>
                    <td style="width:25%"><b>Prepared by</b></td>
                    <td style="width:25%"><b>Checked By</b></td>
                    <td style="width:25%"><b>Approved By</b></td>
                </tr>
                <tr>
                 <td style="width:25%"><b>Name</b></td>
                    <td style="width:25%"></td>
                    <td style="width:25%"></td>
                    <td style="width:25%"></td>
                </tr>
                <tr>
                 <td style="width:25%"><b>Sign</b></td>
                    <td style="width:25%"></td>
                    <td style="width:25%"></td>
                    <td style="width:25%"></td>
                </tr>
                <tr>
                 <td style="width:25%"><b>Date</b></td>
                    <td style="width:25%"></td>
                    <td style="width:25%"></td>
                    <td style="width:25%"></td>
                </tr>';
                   
                
          $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Request.pdf', 'I');
            }
        }
    } else if ($_GET["type"] == "downloadTestingLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Requests Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Requests Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 12%;">BMR /LMR No.		</td>
                    <td style="width: 12%;">Document Type		</td>
                    <td style="width: 12%;">Sampling No.		</td>
                    <td style="width: 10%;">Product Type		</td>
                    <td style="width: 10%;">Product Name		</td>
                    <td style="width: 8%;">Grade	</td>
                    <td style="width: 8%;">Batch No.	</td>
                    <td style="width: 8%;">Batch Size		</td>
                    <td style="width: 10%;">Actio</td>
                </tr>
            </thead>';
                 $output = array();
                 $sql = "SELECT t.*, p.product_name, p.grade FROM etp_inprocess t LEFT JOIN product p ON t.next_product=p.product_code WHERE DATE(t.send_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND t.plant_no LIKE '%".$_GET["plant_no"]."%'";
                 $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                         while ($row = $result->fetch_assoc()) {
                         $row["tests"] = json_decode($row["tests"]);
                         $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row[''].'.</td>
                        <td style="width: 12%;">'.$row[''].'</td>
                        <td style="width: 12%;">'.$row[''].'</td>
                        <td style="width: 12%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 8%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                    </tr>';
                $i++;
                     
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Request.pdf', 'I');
    }
     else if ($_GET["type"] == "downloadTestingReport") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Testing Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.testing_status='approve' AND s.id='".$_GET["id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               // $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
        $html.='
        <h2 style="text-align:center">Testing Report</h2>
        <table border="1" cellpadding="5">
                <tr>
                    <td style="width:25%;"><b>Product Name:</b></td>
                    <td style="width:25%;">'.$row['product_name'].'</td>
                    <td style="width:25%;"><b>Product Grade</b></td>
                    <td style="width:25%;">'.$row['product_code'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Lot/ Batch No.:</b></td>
                    <td style="width:25%;">'.$row['batch_no'].'</td>
                    <td style="width:25%;"><b>Lot /Batch Qty</b></td>
                    <td style="width:25%;">'.$row['batch_qty'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>MFG Date:</b></td>
                    <td style="width:25%;">'.$row['mfg_date'].'</td>
                    <td style="width:25%;"><b>Exp Date:</b></td>
                    <td style="width:25%;">'.$row['exp_date'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Retest Date:</b></td>
                    <td style="width:25%;">'.$row['retest'].'</td>
                    <td style="width:25%;"><b>BMR NO.:</b></td>
                    <td style="width:25%;">'.$row['document_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Receive Date:</b></td>
                    <td style="width:25%;">'.$row['received_date'].'</td>
                    <td style="width:25%;"><b>Receive By:</b></td>
                    <td style="width:25%;">'.$row['received_by'].'</td>
                </tr>
                </table>
                <div></div>';
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%;"><b>Sample Qty:</b></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Chemical Analysis:</b></td>
                    <td style="width:75%;">'.$row['chemical_analysis'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Microbiology Analysis:</b></td>
                    <td style="width:75%;">'.$row['microbiology_analysis'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Reserse Qty:</b></td>
                    <td style="width:75%;">'.$row['reserve_analysis'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Additional Sample Qty:</b></td>
                    <td style="width:75%;">'.$row['additional_sample'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Total Qty:</b></td>
                    <td style="width:75%;">'.$row['total_qty'].'</td>
                </tr>
                </table>
                <div></div>';
                
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:15%;"><b>Sr No.</b></td>
                    <td style="width:20%;"><b>Test</b></td>
                    <td style="width:20%;"><b>Subtest</b></td>
                    <td style="width:15%;"><b>Description</b></td>
                    <td style="width:15%;"><b>Limits</b></td>
                    <td style="width:15%;"><b>Result</b></td>
                </tr>';
                 $row["tests"] = json_decode($row["tests"]);
                 $j=1;
                 $tests = $row["tests"];
                 for ($i = 0; $i < count($tests); $i++) {
                 $test = $tests[$i];
        $html.='<tr>
                    <td style="width:15%;">'.$j.'</td>
                    <td style="width:20%;">'.$test->test.'</td>
                    <td style="width:20%;">'.$test->subtest.'</td>
                    <td style="width:15%;">'.$test->description.'</td>
                    <td style="width:15%;">'.$test->limits.'</td>
                    <td style="width:15%;">'.$test->result.'</td>
                </tr>';
                $j++;
                 }
        $html.='</table>
                <div></div>';
                
                
        $html.='<h3>Remarks</h3>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%;">'.$row['remark'].'</td>
                </tr>';
        $html.="</table>";
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Testing Report.pdf', 'I');
    }else if ($_GET["type"] == "downloadBatchReleaseLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Batch Release Status'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Batch Release Status</h2>
        <table cellpadding="5" border="1">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Sr.</td>
                        <td style="width:10%;">Date	</td>
                        <td style="width:10%;">Product Type</td>
                        <td style="width:10%;">Product Code</td>
                        <td style="width:10%;">Product Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:10%;">Batch No.</td>
                        <td style="width:10%;">Mfg. Date</td>
                        <td style="width:10%;">Exp. Date</td>
                        <td style="width:10%;">Qty</td>
                    </tr>';
                    $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM stock_book b LEFT JOIN product p 
        ON b.product_code=p.product_code WHERE b.product_code!=''";
        $result = $conn->query($sql);
         $j=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                     $html.='<tr>
                        <td style="width:10%;">'.$j.'</td>
                        <td style="width:10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'	</td>
                        <td style="width:10%;">'.$row['product_type'].'</td>
                        <td style="width:10%;">'.$row['product_code'].'</td>
                        <td style="width:10%;">'.$row['product_name'].'</td>
                        <td style="width:10%;">'.$row['grade'].'</td>
                        <td style="width:10%;">'.$row['batch_no'].'</td>
                        <td style="width:10%;">'.$row['mfg_date'].'</td>
                        <td style="width:10%;">'.$row['exp_date'].'</td>
                        <td style="width:10%;">'.$row['qty'].'</td>
                    </tr>';
                       $j++;
                    }
                    }
               $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadBatchReleaseLog.pdf', 'I');
    }else if ($_GET["type"] == "downloadshortagereport") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Shortages Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Shortages Report</h2>
        <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:5%;">Sr.</td>
                        <td style="width:10%;">Date</td>
                        <td style="width:10%;">Product Type</td>
                        <td style="width:10%;">Product Code</td>
                        <td style="width:15%;">Product Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:10%;">Batch No.</td>
                        <td style="width:10%;">Mfg. Date</td>
                        <td style="width:10%;">Exp. Date</td>
                        <td style="width:10%;">Qty</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Shortages Report.pdf', 'I');
    }


}

$conn->close();
?>