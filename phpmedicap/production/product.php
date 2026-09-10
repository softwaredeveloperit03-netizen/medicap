<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    
        // ini_set('display_errors', 1);
        // error_reporting(E_ALL);

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
    if ($_GET["type"] == "getProductsByType") {
        $sql = "SELECT product_code,product_type,product_name,dosage_form FROM product WHERE status='approve' and 
        product_type = '".$_GET["product_type"]."' and plant_id = '".$_GET["plant_id"]."'  ORDER BY product_name ";
        $result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "get_fg_materials") {
          $sql = "SELECT dosage_form FROM product where
        (dosage_type = '".$_GET["product_type"]."' ) and plant_id = '".$_GET["plant_id"]."' group by dosage_form  ORDER BY product_name ";
  
        $result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		} 
		echo json_encode($output);
    }
    else if ($_GET["type"] == "getProductsByDosageForm") {
          $sql = "SELECT * FROM product where
        (product_type = '".$_GET["product_type"]."' or dosage_form = '".$_GET["product_type"]."') and plant_id = '".$_GET["plant_id"]."'  ORDER BY product_name ";
  
        $result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		} 
		echo json_encode($output);
    }
    
    else if ($_GET["type"] == "get_saveoprp1") {
        
        	$output = Array();
     	  $sql = "select * from sp_bmr where work_order_id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM sp_bmr_dtl WHERE sp_bmr_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprpccp_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    
        
    }
       else if ($_GET["type"] == "get_savebmr_sift") {
        	$output = Array();
     	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_intimation") {
        	$output = Array();
     	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["id"]."' and qc_status='sent'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_intimation_check") {
        	$output = Array();
     	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["id"]."' and qc_status='done'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		          
                 $output2 = array();
                     $sql1 ="SELECT * FROM bmr_intimation_checklist where work_order_id =  '".$_GET["id"]."' and sp_bmr_sifting_id= '".$row["id"]."' ";
                   $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                        $output2[] = $row1;
                        }
                    }
             $row['data'] = $output2;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pkplanning") {
        	$output = Array();
     	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["id"]."' and qc_status='Approve' and  (pk_plan='0' or pk_plan='')";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){

      
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_request") {
        $output = array();
                	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["a_id"]."' and  pk_plan!='0' and pm_store_status='0'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "select batch_plan_id,pack_size,batch_size from batch_planing_raw_material_hdr where batch_plan_id = '".$_GET["id"]."' group by batch_plan_id,batch_size,pack_size LIMIT 20";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                       
                         $sql2 ="SELECT * FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code
                             left JOIN mfg_work_order_dtl_pm_lots d on
                            b.material_code=d.material_code  WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."'
                            and d.work_order_id='".$_GET["a_id"]."' AND a.pack_size = '".$row2["pack_size"]."' and  d.sp_bmr_sifting_id='".$row["id"]."'  LIMIT 50 ";
                            
                                
                                
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row3['gradeName'] = $prodLatest['gradeName']; 
                        
                               
                               
                                   $output2[] = $row3;
                            }
                        }
                        $row2["packing_material"] = $output2;
                         $output1[] = $row2;
                    }
                    $row["pack_sizes"] = $output1;
                }
                $output[] = $row;
            }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_request_check") {
        $output = array();
                	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["a_id"]."' and  pm_store_status='pending'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "select batch_plan_id,pack_size,batch_size from batch_planing_raw_material_hdr where batch_plan_id = '".$_GET["id"]."' group by batch_plan_id,batch_size,pack_size LIMIT 20";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                       
                        $sql2 ="SELECT * FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code
                             left JOIN mfg_work_order_dtl_pm_lots d on
                            b.material_code=d.material_code  WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."'
                            and d.work_order_id='".$_GET["a_id"]."' AND a.pack_size = '".$row2["pack_size"]."' and  d.sp_bmr_sifting_id='".$row["id"]."'  LIMIT 50 ";
                            
                                
                                
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row3['gradeName'] = $prodLatest['gradeName']; 
                        
                               
                               
                                   $output2[] = $row3;
                            }
                        }
                        $row2["packing_material"] = $output2;
                         $output1[] = $row2;
                    }
                    $row["pack_sizes"] = $output1;
                }
                $output[] = $row;
            }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_request_approve") {
        $output = array();
                	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["a_id"]."' and  pm_store_status='checked'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "select batch_plan_id,pack_size,batch_size from batch_planing_raw_material_hdr where batch_plan_id = '".$_GET["id"]."' group by batch_plan_id,batch_size,pack_size LIMIT 20";
                $result2 = $conn->query($sql);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2 = array();
                       
                        $sql2 ="SELECT * FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code
                             left JOIN mfg_work_order_dtl_pm_lots d on
                            b.material_code=d.material_code  WHERE a.material_type = 'Packing Material' and a.batch_plan_id='".$row2["batch_plan_id"]."'
                            and d.work_order_id='".$_GET["a_id"]."' AND a.pack_size = '".$row2["pack_size"]."' and  d.sp_bmr_sifting_id='".$row["id"]."'  LIMIT 50 ";
                            
                                
                                
                        $result3 = $conn->query($sql2);
                        if ($result3->num_rows > 0) {
                           while ($row3 = $result3->fetch_assoc()) {
                                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row3['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row3['gradeName'] = $prodLatest['gradeName']; 
                        
                               
                               
                                   $output2[] = $row3;
                            }
                        }
                        $row2["packing_material"] = $output2;
                         $output1[] = $row2;
                    }
                    $row["pack_sizes"] = $output1;
                }
                $output[] = $row;
            }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_request_inprocess_activity") {
        $output = array();
                 	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  pm_store_status='Accept' and pm_disp_completed_by='0'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        //          $calculation_type = $row['calculation_type'];
        //         $row["checkpoints"] = json_decode($row["checkpoints"]);
        //          $sql9 = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["b_id"]."'";
        //          $result9 = $conn->query($sql9);
        //         $pm_id;
        // if ($result9->num_rows > 0) {
        //     while ($row9 = $result9->fetch_assoc()) {
        //         $pm_id=$row9['id'];
                
        //     }
        // }
                $output1 = array();
               
                 
                  $sql1="SELECT *,d.id as upid,a.pack_size as p_size, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code 
              LEFT JOIN mfg_work_order_dtl_pm_lots d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id   WHERE 
                   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$_GET["batch_plan_id"]."'  AND d.sp_bmr_sifting_id='".$row["id"]."'";
                   
                   
            
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        
                         $sql24 ="select sum(qty) as avbl_qty from stock_book where material_code = '".$row1['material_code']."'";
                        
                           $result24 = $conn->query($sql24);
                        if ($result24->num_rows > 0) {
                            while ($row24 = $result24->fetch_assoc()) {
                                $row1['avbl_qty'] = $row24['avbl_qty'];
                            }
                        }
                        
                        
                        
                        
                        
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(a.undertest_qty,0)-IFNULL(b.issued_qty,0)) as balance_qty,
                                floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers, (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty,
                                case when 'LOD Basis' = 'Lod Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                                case when 'LOD Basis' ='Assay Basis' AND 'Active' = 'Active' then ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty from
                            (SELECT IFNULL(SUM(qty), 0) as qty, grn_no,ar_no ,undertest_qty,pack_size ,batch_no,containers,lod_per,assay FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY grn_no,ar_no,pack_size,undertest_qty, batch_no,containers,lod_per,assay) a
                        left join (SELECT grn_no,ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by grn_no,ar_no)b on TRIM(a.ar_no) = TRIM(b.ar_no)"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row2['product_code'] = $row['product_code'];
                                $row2['material_code'] = $row1['material_code'];
                                $row2['work_order_id'] = $row1['work_order_id'];
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_recieve") {
        $output = array();
                 	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  pm_disp_completed_by!='0' and pm_receiving!='done'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
             $output1 = array();
                   $sql1="SELECT *,a.batch_qty as b_qty, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code  
                  LEFT JOIN mfg_work_order_dtl_pm_lots d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id  WHERE 
                   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$_GET["batch_plan_id"]."'  AND d.work_order_id = '".$_GET["work_id"]."' and d.sp_bmr_sifting_id='".$row["id"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
              
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
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_start_pk") {
        $output = array();
                  	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  pm_receiving='done' and bpr_status='0'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                   
                       $sql1="SELECT *,a.batch_qty as b_qty, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code  
                  LEFT JOIN mfg_work_order_dtl_pm_lots d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id  WHERE 
                   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$_GET["batch_plan_id"]."'  AND d.work_order_id = '".$_GET["work_id"]."' and d.sp_bmr_sifting_id='".$row["id"]."'";
                
                  $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
                       
                       
                         $sql11 = "select actual_qtyy from mfg_work_order_dtl_pm_lots  where material_code = '".$row1["material_code"]."' AND work_order_id = '".$row1["work_id"]."'";
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
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "save_prod_label") {
        
      $input = $_POST;
        
        	if(isset($_FILES["label"])) {
            $file_tmp =$_FILES['label']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['label']['name'])));
            $file_name = $entry_date.basename($_FILES["label"]["name"]);
            $label = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/production_label/".$file_name);
        }

           $sql = "update sp_bmr_sifting set label='$label' where id='".$_GET["id"]."'";
    	if($conn->query($sql)){
    	   
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
           
       }
       else if ($_GET["type"] == "del_prod_label") {
        


           $sql = "update sp_bmr_sifting set label='0' where id='".$_GET["id"]."'";
    	if($conn->query($sql)){
    	   
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
           
       }
       else if ($_GET["type"] == "update_label_ipqc_status") {
        
        if($_GET["dept"]=='ipqc'){
                       $sql = "update sp_bmr_sifting set ipqc_status='".$_GET["status"]."',ipqc_approve_by='".$_GET["emp_id"]."' where id='".$_GET["id"]."'";
        
        }
        else if($_GET["dept"]=='prod'){
                         $sql = "update sp_bmr_sifting set production_status='".$_GET["status"]."',production_approve_by='".$_GET["emp_id"]."' where id='".$_GET["id"]."'";

        }
        else if($_GET["dept"]=='qa'){
                         $sql = "update sp_bmr_sifting set qa_status='".$_GET["status"]."',qa_approve_by='".$_GET["emp_id"]."' where id='".$_GET["id"]."'";
        }

    	if($conn->query($sql)){
    	   
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    
           
       }
       else if ($_GET["type"] == "get_savebmr_sift_pk_inprocess_pk") {
        $output = array();
                  	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  bpr_status='START' and bmr_status='0' ";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                   
                       $sql1="SELECT *,a.batch_qty as b_qty, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code  
                  LEFT JOIN mfg_work_order_dtl_pm_lots d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id  WHERE 
                   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$_GET["batch_plan_id"]."'  AND d.work_order_id = '".$_GET["work_id"]."'";
                
                  $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
                       
                       
                         $sql11 = "select actual_qtyy from mfg_work_order_dtl_pm_lots  where material_code = '".$row1["material_code"]."' AND work_order_id = '".$row1["work_id"]."'";
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
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_fg_intimation") {
        $output = array();
                  	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  bmr_status='Complete' and fg_intimation_receive_date='0'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                     $output1 = Array();
                         $sql1 = "SELECT * FROM packing_quality_sample_check WHERE work_order_id='".$_GET["work_id"]."'  and  sp_bmr_sifting_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                   
                        $row["sample_check"] = $output1;
                        $output[] = $row;
                }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_inprocess_check_pk") {
        $output = array();
                  	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  bmr_status='checking'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$_GET["work_id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$_GET["work_id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                
          
                        // ////////////////////////////////////////////////
                  $output9 = array();
                     
                      $sql2="select * from pk_bmr_Equipment_data where work_order_id= '".$_GET["work_id"]."' and sp_bmr_sifting_id='".$row["id"]."'"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output9[] = $row2;
                            }
                        }
                        // ////////////////////////////////////////////////
                  $output5 = array();
                     
                      $sql2="select * from packing_quality_sample_check where work_order_id= '".$_GET["work_id"]."' and sp_bmr_sifting_id='".$row["id"]."'"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output5[] = $row2;
                            }
                        }
                        // ////////////////////////////////////////////////
                  $output6 = array();
                     
                      $sql2="select * from bmr_final_batch_plan where work_order_id='".$_GET["work_id"]."' and sp_bmr_sifting_id='".$row["id"]."'"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output6[] = $row2;
                            }
                        }
                        // ////////////////////////////////////////////////
                  $output3 = array();
                     
                      $sql2="select * from primary_packing where work_order_id= '".$_GET["work_id"]."' and sp_bmr_sifting_id='".$row["id"]."'"; 
  
                          $result2 = $conn->query($sql2);
                                    if ($result2->num_rows > 0) {
                                        while ($row2 = $result2->fetch_assoc()) {
                                     $output2 = array();
                                 $sql3="select * from primary_packing_dtl where primary_packing_id= '".$row2["id"]."' "; 
                                  $result3 = $conn->query($sql3);
                                    if ($result3->num_rows > 0) {
                                        while ($row3 = $result3->fetch_assoc()) {
                                            $output2[] = $row3;
                                        }
                                    }
                                          $row2["primary_packing_dtl1"] = $output2;
                                            
                                            $output3[] = $row2;
                                        }
                                    }
                      // ////////////////////////////////////////////////
                  $output4 = array();
                     
                      $sql2="select * from secondary_packing where work_order_id= '".$_GET["work_id"]."' and sp_bmr_sifting_id='".$row["id"]."'"; 
  
                          $result2 = $conn->query($sql2);
                                    if ($result2->num_rows > 0) {
                                        while ($row2 = $result2->fetch_assoc()) {
                                     $output2 = array();
                                 $sql3="select * from secondary_packing_dtl where secondary_packing_id= '".$row2["id"]."'"; 
                                  $result3 = $conn->query($sql3);
                                    if ($result3->num_rows > 0) {
                                        while ($row3 = $result3->fetch_assoc()) {
                                            $output2[] = $row3;
                                        }
                                    }
                                          $row2["secondary_packing_dtl"] = $output2;
                                            
                                            $output4[] = $row2;
                                        }
                                    }
                      // ////////////////////////////////////////////////
                      
                      
                            
                
                
                
                
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                // $row["packing_equipment"] = $output2;
                $row["primary_packing"] = $output3;
                $row["secondary_packing"] = $output4;
                $row["sample_check"] = $output5;
                $row["bmr_final_batch_plan"] = $output6;
                $row["pk_eq"] = $output9;
                $output[] = $row;
            }
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_pk_intimation_pk") {
        $output = array();
                  	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["work_id"]."' and  qc_intimation='sent'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                   
                       $sql1="SELECT *,a.batch_qty as b_qty, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code  
                  LEFT JOIN mfg_work_order_dtl_pm_lots d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id  WHERE 
                   a.material_type = 'Packing Material' AND a.batch_plan_id = '".$_GET["batch_plan_id"]."'  AND d.work_order_id = '".$_GET["work_id"]."' and d.sp_bmr_sifting_id='".$row["id"]."'";
                
                  $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       $category = $row1["category"];
                       
                       
                         $sql11 = "select actual_qtyy from mfg_work_order_dtl_pm_lots  where material_code = '".$row1["material_code"]."' AND work_order_id = '".$row1["work_id"]."'";
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
        }else{
            $output=[];
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_savebmr_sift_intimation_approve") {
        	$output = Array();
     	  $sql = "SELECT * FROM sp_bmr_sifting   where work_order_id='".$_GET["id"]."' and qc_status='check'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		          
                 $output2 = array();
                     $sql1 ="SELECT * FROM bmr_intimation_checklist where work_order_id =  '".$_GET["id"]."' and sp_bmr_sifting_id= '".$row["id"]."' ";
                   $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                        $output2[] = $row1;
                        }
                    }
             $row['data'] = $output2;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
           else if ($_GET["type"] == "getavaliablestock") {
        	$output = Array();
     	  $sql = "SELECT IFNULL(SUM(qty), 0) as qty1  FROM stock_book  where material_code ='".$_GET["material_code"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    
    		    
    		           $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty2  FROM material_issue  where material_code ='".$_GET["material_code"]."'";
                    	$result1 = $conn->query($sql1);
                    	if($result1->num_rows > 0){
                    		while($row1 = $result1->fetch_assoc()){
                    		    $row['qty2'] = $row1['qty2'];
                    		    
                    		}
                    	}
    		    
    		    
    		    
    		    $avl_qty = $row['qty1'] - $row['qty2'];
    		    $avl_qty_formatted = number_format($avl_qty, 2);

    		    
    		    
    			$output[] = $row;
    		}
    	}
		echo json_encode($avl_qty_formatted);
    }
    
    else if ($_GET["type"] == "getavaliablestockfinished") {
        	$output = Array();
     	  // $sql = "SELECT IFNULL(SUM(qty), 0) as qty1  FROM fg_stock_book  where material_code ='".$_GET["product_code"]."'";
     	    $sql = "SELECT *   FROM fg_stock_book  where material_code ='".$_GET["product_code"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		           //$sql1 = "SELECT IFNULL(SUM(qty), 0) as qty2  FROM fg_material_issue  where material_code ='".$_GET["product_code"]."'";
    		           $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty2  FROM fg_material_issue  where batch_no ='".$row["batch_no"]."'";
                    	$result1 = $conn->query($sql1);
                    	if($result1->num_rows > 0){
                    		while($row1 = $result1->fetch_assoc()){
                    		    $row['qty2'] =  $row1['qty2'];
                    		}
                    	}
    		    $avl_qty = $row['qty'] - $row['qty2'];
    		    $avl_qty_formatted = number_format($avl_qty, 2);
    		    $row['avl_qty'] = $avl_qty_formatted;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }

       else if ($_GET["type"] == "bmr_all_stages") {
        	$output = Array();
     	  $sql = "SELECT * FROM bmr_all_stages  where work_order_id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "get_savebmr_blend") {
        	$output = Array();
     	  $sql = "SELECT * FROM sp_bmr_blender where work_order_id='".$_GET["id"]."' ORDER BY Processing ASC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "del_savebmr_blend") {
        	$output = Array();
     	  $sql = "DELETE FROM sp_bmr_blender where id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "update_qc_stat") {
        	$output = Array();
     	  $sql = "UPDATE sp_bmr_sifting set qc_status='sent', raw_qc_intimation_raised_by='".$_GET["emp_id"]."',raw_qc_intimation_raised_date='$entry_date'
 where id='".$_GET["id"]."'";
    	if($conn->query($sql)===TRUE){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
    }
    else if ($_GET["type"] == "del_savebmr_sift") {
        	$output = Array();
     	  $sql = "DELETE FROM sp_bmr_sifting where id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    }
    else if ($_GET["type"] == "get_saveoprp2") {
        
        	$output = Array();
     	  $sql = "select * from sp_bmr_oprpccp2 where work_order_id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM sp_bmr_oprpccp2_dtl WHERE sp_bmr_oprpccp2='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["oprpccp_details"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    
        
    }
    else if ($_GET["type"] == "get_sifting") {
        
        	$output = Array();
     	  $sql = "select * from sifting where work_order_id='".$_GET["id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $output1 = Array();
                        $sql1 = "SELECT * FROM sifting_dtl WHERE sifting_id='".$row["id"]."'";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                          while ($row1 = $result1->fetch_assoc()) {
                                $output1 []= $row1;
                          }
                        }
                         $row["sifting_dtl"] = $output1;
    			$output[] = $row;
    		}
    	}
		echo json_encode($output);
    
        
    }
    else if ($_GET["type"] == "saveoprp1") {
         $sql="UPDATE mfg_work_order_hdr set oprpccp2='1',oprpccp1='2'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
        $data=$input["oprps"];
//  echo   $data = json_decode($input["oprps"]);
   $flag=0;
        foreach ($data as $item) {
    $id = $item['id'];
    $name = $item['name'];

    // Construct the INSERT query for main data table
     $sql = "INSERT INTO sp_bmr( work_order_id, oprp1_room, oprp1_observation, oprp1_remark) VALUES ('".$_GET["id"]."','".$item["room"]."','".$item["observation"]."','".$item["remark"]."')";
    if($conn->query($sql)){
 $flag=1;

    // $subdata = $item['oprp_room_details'];


    // foreach ($subdata as $subitem) {

         $product_id = $conn->insert_id;

   
         $sql1 = "INSERT INTO sp_bmr_dtl (sp_bmr_id,humidity,temp) VALUES ('$product_id','".$item["humidity"]."','".$item["temp"]."')";
//   echo     $sql1 = "INSERT INTO sp_bmr_dtl (sp_bmr_id,humidity,temp) VALUES ('$product_id','".$subitem["humidity"]."','".$subitem["temp"]."')";
        $conn->query($sql1);
    // }
    
    }
   } echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"".$conn->error."\"}";
   }
    }
    else if ($_GET["type"] == "saveoprp111 ") {
    
        $data=$input["oprps"];
//  echo   $data = json_decode($input["oprps"]);
   $flag=0;
        foreach ($data as $item) {
    $id = $item['id'];
    $name = $item['name'];

    // Construct the INSERT query for main data table
    $sql = "INSERT INTO oprp_sheet2( work_order_id, equipment) VALUES ('".$_GET["id"]."','".$item["equipment"]."')";
    if($conn->query($sql)){
 $flag=1;

    // Get the subdata array
    $subdata = $item['oprpccp_details'];

    // Loop through the subdata array
    foreach ($subdata as $subitem) {
        // $sub_id = $subitem['sub_id'];
        // $value = $subitem['value'];
         $product_id = $conn->insert_id;

        // Construct the INSERT query for subdata table
        $sql1 = "INSERT INTO oprp_sheet2_dtl(oprp_sheet2_id, airpressure, cleanliness_discharge_channel, cleanliness_hoper, film_folds, heater_working,
        humidity, seal_cleanliness, seal_strength, sensitivity, tmep, wad_film_folds, wad_heater_working, wad_seal_cleanliness, wad_seal_strength, time,
        obervation, remark, fe, non_fe, ss) VALUES ( '$product_id','".$subitem["airpressure"]."',
        '".$subitem["cleanliness_discharge_channel"]."','".$subitem["cleanliness_hoper"]."','".$subitem["film_folds"]."','".$subitem["heater_working"]."',
'".$subitem["humidity"]."','".$subitem["seal_cleanliness"]."','".$subitem["seal_strength"]."','".$subitem["sensitivity"]."','".$subitem["tmep"]."',
'".$subitem["wad_film_folds"]."','".$subitem["wad_heater_working"]."','".$subitem["wad_seal_cleanliness"]."','".$subitem["wad_seal_strength"]."',
'".$subitem["time"]."','".$subitem["observation"]."','".$subitem["remark"]."','".$subitem["FE"]."','".$subitem["NON_FE"]."','".$subitem["SS"]."')";
        $conn->query($sql1);
    }
    
    
        
            }else{
                 $flag=0;
                   
           }
   } 
      
      if($flag== 1){
          echo "{\"status\":\"success\"}";
      }else{
           echo "{\"status\":\"".$conn->error."\"}";
      }
      
      
      
    }
    else if ($_GET["type"] == "save_lc_procc_cheklist2323") {    
        $json_obj = json_encode($input["chk_poins"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $item)
                {
        $sql = "INSERT INTO bmr_lc_process_chklist( work_order_id, parameter, result) VALUES ('".$_GET["id"]."','".$item["checkpoint"]."','".$item["remarkvalue"]."')";      
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
    else if ($_GET["type"] == "save_sifitng") {
   
    $sql = "INSERT INTO sifting( work_order_id, equipment,lot) VALUES ('".$_GET["id"]."','".$input["sifter_equip"]."','".$input["Processing"]."')";
    if($conn->query($sql)){
         $product_id = $conn->insert_id;
 $json_obj = json_encode($input["sifting"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $subitem)
                {

        $sql1 = "INSERT INTO sifting_dtl (sifting_id,material,sieve_size,start_time,end_time,sieve_before,sieve_after) VALUES ('$product_id','".$subitem["material"]."','".$subitem["sieve_size"]."'
                    ,'".$subitem["start_time"]."','".$subitem["end_time"]."','".$subitem["sieve_before"]."','".$subitem["sieve_after"]."')";
        $conn->query($sql1);
    }
    
    
   echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"".$conn->error."\"}";
   
    }
    }
    else if ($_GET["type"] == "send_qc_int") {
        
  $sql="UPDATE mfg_work_order_hdr set  raw_qc_intimation='sent',raw_qc_intimation_raised_by='".$_GET["emp_id"]."',raw_qc_intimation_raised_date='$entry_date' WHERE id='".$_GET["id"]."'";       
  if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
         }else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "saveoprp2") {
         $sql="UPDATE mfg_work_order_hdr set blending='1',oprpccp2='2'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
        $data=$input["oprps2"];
     
//  echo   $data = json_decode($input["oprps"]);
        foreach ($data as $item) {
    $id = $item['id'];
    $name = $item['name'];

    // Construct the INSERT query for main data table
    $sql = "INSERT INTO sp_bmr_oprpccp2( work_order_id, blender, checkpoint, sifter) VALUES ('".$_GET["id"]."','".$item["blender"]."','".$item["checkpoint"]."','".$item["sifter"]."')";
    if($conn->query($sql)){
        $flag=1;
    // Get the subdata array
     $product_id = $conn->insert_id;
    $subdata = $item['oprpccp_details'];

    // Loop through the subdata array
    foreach ($subdata as $subitem) {
        // $sub_id = $subitem['sub_id'];
        // $value = $subitem['value'];
        

        // Construct the INSERT query for subdata table
        $sql1 = "INSERT INTO sp_bmr_oprpccp2_dtl (sp_bmr_oprpccp2,mesh_size,sieves, observation, remark) VALUES ('$product_id','".$subitem["mesh_size"]."','".$subitem["sieves"]."','".$subitem["observation"]."','".$subitem["remark"]."')";
        $conn->query($sql1);
    }
    
    }
   }
   if($flag=1) {echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"".$conn->error."\"}";
   }
    }
    }
    else if ($_GET["type"] == "saveblend") {       
 // /
$sql = "INSERT INTO sp_bmr_blender (work_order_id,blend_end_time,blend_start_time	,blender,Processing)
       VALUES ('".$_GET["id"]."','".$input["blend_end_time"]."','".$input["blend_start_time"]."','".$input["blender_equip"]."',
       '".$input["Processing"]."')";
	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        }
    else if ($_GET["type"] == "saveblendmfg") {       
        $sql="UPDATE mfg_work_order_hdr set mfg_date='".$input["mfg_date"]."',exp_date='".$input["exp_date"]."'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
        
                   echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "save_bmr_instruction") {
           $array=$input["instructions"];
                foreach ($array as $values)
                {
$sql = "INSERT INTO bmr_genral_instruction( work_order_id, instructions)
          VALUES ('".$_GET["id"]."','".$values["instruction"]."')";
          if ($conn->query($sql)) {
        $sql1="update mfg_work_order_hdr set Genral_Instruction='2' where id='".$_GET["id"]."'";
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
    else if ($_GET["type"] == "save_bmr_sp_equpments_all_data") {
           $array=$input["equips"];
                foreach ($array as $values)
                {
$sql = "INSERT INTO bmr_sp_equpments_all_data( work_order_id, equipment_code, clean_by, cleaning_type, clean_from, clean_to,
status, entry_by, entry_date, approve_by, approve_date, equipment_name, capacity) VALUES ('".$_GET["id"]."','".$values["equipment_code"]."',
'".$values["clean_by"]."','".$values["cleaning_type"]."','".$values["clean_from"]."',
'".$values["clean_to"]."','".$values["status"]."','".$values["entry_by"]."',
'".$values["entry_date"]."','".$values["approve_by"]."','".$values["approve_date"]."',
'".$values["equipment_name"]."','".$values["capacity"]."')";
          if ($conn->query($sql)) {
               $sql1="update mfg_work_order_hdr set Equipments='2' where id='".$_GET["id"]."'";
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
 
    else if ($_GET["type"] == "save_stage") {       
        $sql="UPDATE mfg_work_order_hdr set bmr_stages='2'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
           $array=$input["Stage_List"];
                foreach ($array as $values)
                {
 $sql = "INSERT INTO bmr_all_stages( work_order_id, Area_Clean_By, batch_number, Checked_by, date, Humidity, Line_clearance_given_by, previous_product, stage, temp, time)
          VALUES ('".$_GET["id"]."','".$values["Area_Clean_By"]."','".$values["batch_number"]."','".$values["Checked_by"]."','".$values["date"]."','".$values["Humidity"]."',
          '".$values["Line_clearance_given_by"]."','".$values["previous_product"]."','".$values["stage"]."','".$values["temp"]."','".$values["time"]."')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
    else if ($_GET["type"] == "save_sifting") {       
  
       
  $sql = "INSERT INTO sp_bmr_sifting (work_order_id,sampling_time,sift_end_time	,sift_start_time,siftter_equip,lot)
       VALUES ('".$_GET["id"]."','".$input["sampling_time"]."','".$input["sift_end_time"]."','".$input["sift_start_time"]."',
       '".$input["sifter_equip"]."','".$input["Processing"]."')";
        if ($conn->query($sql)) {
 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
   elseif ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM unitformula WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["unitformula"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM packingformula WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["packingformula"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM stages WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM steps WHERE product_code='".$row["product_code"]."' AND stage_no='".$row1["stage_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["steps"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedStages") {
        $output = Array();
        $sql = "SELECT DISTINCT(stage) as stage FROM stages WHERE product_code='".$_GET["product_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM batch_formula WHERE product_code='".$row1["product_code"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT * FROM batch_materials WHERE no='".$row2["id"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $sql4 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row3["material_code"]."' AND status='Approved'";
                                        $result4 = $conn->query($sql4);
                                        if ($result4->num_rows > 0) {
                                            $row3["status"] = "available";
                                            while ($row4 = $result4->fetch_assoc()) {
                                                if (+$row4["qty"] == 0) {
                                                    $row3["avl_qty"] = 0.00;
                                                    $row3["status"] = "na";
                                                } else if (+$row4["qty"] < +$row3["batch_qty"]) {
                                                    $row3["avl_qty"] = +$row3["qty"];
                                                    $row3["status"] = "short";
                                                } else if (+$row4["qty"] >= +$row3["batch_qty"]) {
                                                    $row3["avl_qty"] = +$row3["batch_qty"];
                                                    $row3["status"] = "available";
                                                }
                                            }
                                        } else {
                                            $row3["avl_qty"] = 0;
                                            $row3["status"] = "not available";
                                        }
                                        $output3[] = $row3;
                                    }
                                }
                                $row2["materials"] = $output3;
                                
                                $output3 = Array();
                                $sql3 = "SELECT * FROM batch_stages WHERE no='".$row2["id"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $output3[] = $row3;
                                    }
                                }
                                $row2["stages"] = $output3;
                                $output2[] = $row2;
                            }
                        }
                        $row1["batches"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDosagesforMaster") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT * FROM unitformula WHERE product_code='".$row1["product_code"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output3 = Array();
                                $sql3 = "SELECT * FROM unit_materials WHERE mfr_no='".$row2["mfr_no"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $sql4 = "SELECT * FROM material WHERE material_code='".$row3["material_code"]."'";
                                        $result4 = $conn->query($sql4);
                                        if ($result4->num_rows > 0) {
                                            while ($row4 = $result4->fetch_assoc()) {
                                                $row3["material_name"] = $row4["material_name"];
                                                $row3["grade"] = $row4["grade"];
                                            }
                                        }
                                        $row3["batch_qty"] = 0;
                                        $row3["batch_unit"] = $row3["unit"];
                                        $row3["lot_qty"] = 0;
                                        $row3["lot_unit"] = $row3["unit"];
                                        $output3[] = $row3;
                                    }
                                }
                                $row2["materials"] = $output3;
                                $row2["lots"] = [];
                                $output2[] = $row2;
                            }
                        }
                        $row1["mfrs"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveInitBMR") {
        $sql = "INSERT INTO batch_formula (product_code, mfr_no, batch_size, lots, granulation, packing_style, entry_by, entry_date) VALUES ('".$input["product_code"]."', '".$input["mfr_no"]."', '".$input["batch_size"]."', '".$input["lots"]."', '".$input["granulation"]."', '".$input["packing_style"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."')";
                $conn->query($sql1);
            }
            
            $j = 0;
            $stages = $input["stages"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                $j++;
                $sql1 = "INSERT INTO batch_stages (no, stage_for, stage_no, stage, details) VALUES ('$last_id', '".$stage["stage_for"]."', '".$j."', '".$stage["stage"]."', '".json_encode($stage)."')";
                $conn->query($sql1);
            }
            
            $stages = $input["packing"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                $j++;
                $sql1 = "INSERT INTO batch_stages (no, stage_no, stage_for, stage, details) VALUES ('$last_id', '".$j."','".$stage["stage_for"]."', '".$stage["stage"]."', '".json_encode($stage)."')";
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingStages") {
        $output = Array();
        $sql = "SELECT * FROM batch_formula WHERE status='pending'";
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
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                $output1 = Array();
                $sql1 = "SELECT * FROM batch_stages WHERE no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStageDetails") {
        $output = Array();
        $sql = "SELECT * FROM batch_stages WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM batch_formula WHERE id='".$row["no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["mfr_no"] = $row1["mfr_no"];
                        $row["batch_size"] = $row1["batch_size"];
                        $row["lots"] = $row1["lots"];
                        $row["product_code"] = $row1["product_code"];
                    }
                }
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } 
    else if ($_GET["type"] == "getEquipments") {
        $output = Array();
        $sql = "SELECT * from equipment WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "materialnamebytype") {
        $output = Array();
        $sql = "SELECT * from material WHERE material_type = '".$_GET["material_type"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveBatchPlan") {
        $sql = "INSERT INTO bmr (dosage_form, product_code, batch_size, mfr_no, bom_no, entry_by, entry_date) VALUES ('".$input["dosage_form"]."', '".$input["product_code"]."', '".$input["batch_size"]."', '".$input["mfr_no"]."', '".$input["id"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            echo "{\"status\":\"success\"}";
            $stages = $input["stages"];
            for ($i = 0; $i < count($stages); $i++) {
                $stage = $stages[$i];
                $sql1 = "INSERT INTO bmr_stages (bmr_no, stage_no, stage, data) VALUES ('$last_id', '".$stage["stage_no"]."', '".$stage["stage"]."', '".$stage["details"]."')";
                $conn->query($sql1);
            }
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO bmr_material (no, material_code, overages, qty, unit, batch_qty, batch_unit, lot_qty, lot_unit, role, process, materials) VALUES ('$last_id', '".$material["material_code"]."', '".$material["overages"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["batch_qty"]."', '".$material["batch_unit"]."', '".$material["lot_qty"]."', '".$material["lot_unit"]."', '".$material["role"]."', '".$material["process"]."', '".$material["materials"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingBatchPlan") {
        $output = Array();
        $sql = "SELECT * FROM bmr WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
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
    } else if ($_GET["type"] == "getCheckedBatchPlan") {
        $output = Array();
        $sql = "SELECT * FROM bmr WHERE status='checked'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
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
    } else if ($_GET["type"] == "getBatchPlanLog") {
        $output = Array();
        $sql = "SELECT * FROM bmr";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
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
    } else if ($_GET["type"] == "checkBMRPlan") {
        $sql = "UPDATE bmr SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "approveBMRPlan") {
        $sql = "UPDATE bmr SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingBatchNoPlan") {
        $output = Array();
        $sql = "SELECT * FROM bmr WHERE status='approve' AND bno='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
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
    }
    else if ($_GET["type"] == "getBatchNo") {
        $id = 0;
        $sql = "SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'bmr_plan' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row["auto_increment"];
                break;
            }
        }
        echo "{\"batch_no\":\"BATCH$id\"}";
    } 
    else if ($_GET["type"] == "getCompletedBMR") {
        // $sql = "SELECT b1. *,s2.product_code,s2.product_name ,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no,s.sample_qty FROM batch_stages_ipqc_dtl b1 left join stages_ipqc_dtl b2 ON b1.stage_hdr_id=b2.id LEFT JOIN spec_tests s ON b2.stage_name=s.stage LEFT JOIN stages_ipqc s2 on b2.stage_hdr_id=s2.id";
        $sql = "SELECT b1. *,s2.product_code,s2.product_name ,b2.stage_hdr_id as stage_id,b2.stage_name,b1.lot_no as test_no,s.sample_qty,m.batch_number,m.batch_complete_date,m.batch_commence_date,b.batch_size FROM batch_stages_ipqc_dtl b1 left join stages_ipqc_dtl b2 ON b1.stage_hdr_id=b2.id LEFT JOIN spec_tests s ON b2.stage_name=s.stage LEFT JOIN stages_ipqc s2 on b2.stage_hdr_id=s2.id LEFT JOIN batch_planning b on s2.product_code =b.product_code left join mfg_work_order_hdr m on b.id=m.batch_plan_id";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
    else if ($_GET["type"] == "allocateBatchNo") {
        $details = Array();
        $details["batchno_allocate_by"] = $_GET["emp_id"];
        $details["batchno_allocate_date"] = $entry_date;
        $sql = "UPDATE bmr SET batch_no='".$_GET["batch_no"]."',status='ready', bno='allocate', details='".json_encode($details)."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "get_lot_materials_testing_receiving") {
        $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
         a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,
         a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,.p.product_type,
         b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, ";
         if($_GET["material_type"] == 'Raw Material'){
            $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
         }else{
            $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
         }
         
         
        $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
         b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
         b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
         and dispensing_status ='Request Sent'   "; 
        if($_GET["material_type"] == 'Raw Material'){
            //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
        // old code
        // $sql = $sql." and rm_disp_completed_by!='' and stage_completed_by='' and rm_qa_dislc_status='Approved' ";
         $sql = $sql." and rm_disp_completed_by='' and stage_completed_by='' and rm_qa_dislc_status='Approved' ";
        
            
        }
        else{
            $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
        }
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_lot_materials_testing_receiving_pk") {
        $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by,  a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date 
             
           FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   and a.receiving='done' a.stage_completed_by='' ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_lot_materials_testing_checking") {
        $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,a.lod_status,a.calculation_type,
         a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,
         a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,.p.product_type,
         b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,rm_disp_completed_by, ";
         if($_GET["material_type"] == 'Raw Material'){
            $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
         }else{
            $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
         }
         
         
        $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
         b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
         b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
         and dispensing_status ='Request Sent'   "; 
        if($_GET["material_type"] == 'Raw Material'){
            //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            $sql = $sql."  stage_completed_by!='' and  stage_checked_by='' ";
        }
        else{
            $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
        }
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getReadyBatchPlans_pk") {
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
          $sql ="SELECT
                        s.total_qty,
                        a.id,
                        a.batch_number,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
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
                        p.dosage_form,
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date
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
             and dispensing_status ='Request Sent'   and a.receiving='done' ";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
    else if ($_GET["type"] == "getReadyBatchPlans_pk_sp_intimation") {
        $output = Array();
       $sql = "SELECT
                       
                        a.bmr_no,
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
                        p.dosage_form,
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
              (SELECT COUNT(qc_intimation) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.qc_intimation='sent') as pm_store_status_count

                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                
             where  a.plant_id ='".$_GET["plant_id"]."' and (qc_intimation='sent' or qc_intimation=' ')  HAVING    pm_store_status_count != '0'";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
               
                        $output[] = $row;
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getReadyBatchPlans_pk_sp_intimation_fg") {
        $output = Array();
       $sql = "SELECT
                        
                        a.id as a_id,
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
                        a.exp_date,
                        a.mfg_date,
                        uf.min_per AS min_yeild,
                        uf.max_per AS max_yeild,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,
                        p.product_name,
                        b.product_type,
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        (SELECT COUNT(bmr_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bmr_status='Complete') as bmr_status_count,
                        (SELECT COUNT(fg_sampling_intimation) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.fg_sampling_intimation='done') as fg_sampling_intimation_count
                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
               
                    
             where  a.plant_id ='".$_GET["plant_id"]."' and (fg_sampling_intimation='sent' or fg_sampling_intimation='0') HAVING bmr_status_count!='0' ";
        $result = $conn->query($sql);
        //$result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    //  $output1 = Array();
                    //     $sql1 = "SELECT * FROM packing_quality_sample_check WHERE work_order_id='".$row["id"]."'  ";
                        
                    //     $result1 = $conn->query($sql1);
                    //     if ($result1->num_rows > 0) {
                    //         while ($row1 = $result1->fetch_assoc()) {
                    //             $output1[] = $row1;
                    //         }
                    //     }
                   
                    //     $row["sample_check"] = $output1;
                  $lot=  $row['no_of_lots'];
                $Count=    $row['fg_sampling_intimation_count'];
                    
                    // echo $lot;
                    // echo('hi');
                    // echo $Count;
                        $output[] = $row;
                        
                        if($row['no_of_lots']==$row['fg_sampling_intimation_count']){
        $sql = "update mfg_work_order_hdr set fg_sampling_intimation='done' where id ='".$row["a_id"]."'";
                $conn->query($sql);
                            
                        }
                }
            }
            //  $data["material_types"] = $output;
        echo json_encode($output);
    }
        
    
    else if ($_GET["type"] == "save_qc_sample_qty") {    
     $date = date('Y-m-d');
              $sql = "update sp_bmr_sifting set qc_intimation='done',qc_sample_qty_by='".$_GET["emp_id"]."',qc_sample_qty_packing='".$input["sample_qty"]."',qc_sample_qty_packing_date='$date' where id ='".$_GET["sift_id"]."'";
            //   $sql = "update mfg_work_order_hdr set qc_intimation='done',qc_sample_qty_by='".$_GET["emp_id"]."',qc_sample_qty_packing='".$input["sample_qty"]."',qc_sample_qty_packing_date='$date' where id ='".$_GET["work_id"]."'";

        if ($conn->query($sql)) {
     
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if ($_GET["type"] == "receive_fg") {    
        
        $date = date('Y-m-d');
        
              $sql = "update sp_bmr_sifting set fg_sampling_intimation='done' ,fg_intimation_receive_date='$date',
              fg_intimation_receive_by='".$_GET["emp_id"]."' where id ='".$_GET["sift_id"]."'";
        
       

        if ($conn->query($sql)) {
     
    
            echo "{\"status\":\"success\"}";
            
            $sql1 = "  INSERT INTO fg_stock_book( plant_id, batch_no, stock_type, product_type, material_code, qty, 
             unit, mfg_date, exp_date, status, entry_by, entry_date)  VALUES ('".$_GET["plant_id"]."', 
             '".$input["batch_number"]."', 'Production', '".$input["product_type"]."', '".$input["product_code"]."', 
             '".$input["batch_size"]."', ' ', '".$input["mfg_date"]."', '".$input["exp_date"]."', 
             'Approved'  ,'".$_GET["emp_id"]."','$entry_date')";
             
             $conn->query($sql1);
             
             $lastInsertedId = $conn->insert_id;
             
             $ar = 'AR-'.$lastInsertedId;
             
             $sql2 = "update fg_stock_book set ar_no = '$ar'  where id ='$lastInsertedId' ";
             $conn->query($sql2);
             
             
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if ($_GET["type"] == "getReadyBatchPlans_pk_sp") {
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
         $sql ="SELECT
                       
                        a.id as a_id,
                        a.batch_number,
                          a.bmr_no,
                          a.no_of_lots,
                        a.fg_sampling_intimation,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        p.dosage_form,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.batch_plan_id,
                        a.sampling_intimation,
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
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        a.mfg_date,a.exp_date,
          (SELECT COUNT(bpr_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bpr_status='START') as bpr_status_count

                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                   
                    
             where  a.plant_id ='".$_GET["plant_id"]."'
             and (dispensing_status ='Request Sent' or dispensing_status=' ')   and (a.receiving='done' or a.receiving=' ') and (a.bmr_status='start' or a.bmr_status='0')  HAVING    bpr_status_count != '0'";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
    else if ($_GET["type"] == "getReadyBatchPlans_pk_sp_Label") {
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
         $sql ="SELECT
                       
                        a.id as a_id,
                        a.batch_number,
                          a.bmr_no,
                          a.no_of_lots,
                        a.fg_sampling_intimation,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        p.dosage_form,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.batch_plan_id,
                        a.sampling_intimation,
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
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        a.mfg_date,a.exp_date,
          (SELECT COUNT(bpr_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bpr_status='START') as bpr_status_count,
           (SELECT COUNT(ipqc_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bpr_status='START' and z.ipqc_status='pending') as ipqc_status_count,
           (SELECT COUNT(production_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bpr_status='START' and z.production_status='pending') as prod_status_count,
           (SELECT COUNT(label) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bpr_status='START' and z.label!='0' ) as label_count

                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                   
                    
             where  a.plant_id ='".$_GET["plant_id"]."'
             and (dispensing_status ='Request Sent' or dispensing_status=' ')   and (a.receiving='done' or a.receiving=' ') and (a.bmr_status='start' or a.bmr_status='0')  HAVING    bpr_status_count != '0'";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
            
    }
    // else if ($_GET["type"] == "getReadyBatchPlans_pk_sp") {
    //       $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' 
    //     and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //      $sql ="SELECT
    //                     s.total_qty,
    //                     a.id as a_id,
    //                     a.batch_number,
    //                       a.bmr_no,
    //                       a.no_of_lots,
    //                     a.fg_sampling_intimation,
    //                     a.work_order_no,
    //                     a.rm_qa_dislc_date AS palnned_by,
    //                     p.dosage_form,
    //                     a.approved_by,
    //                     a.lod_status,
    //                     a.calculation_type,
    //                     a.batch_commence_date,
    //                     a.batch_plan_id,
    //                     a.sampling_intimation,
    //                     a.stage_checked_by,
    //                     a.tr_to_packing_dept_by,
    //                     a.batch_complete_date,
    //                     a.stability,
    //                     a.stability_reason,
    //                     a.process_validation,
    //                     a.qa_person,
    //                     a.qa_date,
    //                     a.no_of_lots,
    //                     uf.min_per AS min_yeild,
    //                     uf.max_per AS max_yeild,
    //                     a.approved_by,
    //                     b.plan_no,
    //                     b.bfr_no,
    //                     b.mfr_no,
    //                     b.product_code,
    //                     b.batch_size,
    //                     p.product_name,
    //                     p.product_type,
    //                     p.grade,
    //                     b.pack_size,
    //                     b.pack_unit,
    //                     a.dispense_request_sent_by,
    //                     a.dispense_request_sent_on,
    //                     a.dispensing_status,
    //                     rm_disp_completed_by,
    //                     a.pm_qa_dislc_status AS lc_status,
    //                     a.pm_qa_dislc_by AS lc_by,
    //                     a.pm_qa_dislc_date AS lc_date,
    //                     a.mfg_date,a.exp_date,
    //       (SELECT COUNT(bpr_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bpr_status='START') as bpr_status_count

    //                 FROM
    //                     mfg_work_order_hdr a
    //                 JOIN batch_planning b ON
    //                     a.batch_plan_id = b.id AND a.plant_id = b.plant_id
    //                 JOIN product p ON
    //                     b.product_code = p.product_code AND b.plant_id = p.plant_id
    //                 LEFT JOIN unitformula uf ON
    //                     uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
    //                 LEFT JOIN samplingfg s ON
    //                     b.product_code = s.product_code
                    
    //          where  a.plant_id ='".$_GET["plant_id"]."'
    //          and (dispensing_status ='Request Sent' or dispensing_status=' ')   and (a.receiving='done' or a.receiving=' ') and (a.bmr_status='start' or a.bmr_status='0')  HAVING    bpr_status_count != '0'";
            
        
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $calculation_type = $row['calculation_type'];
    //             $row["checkpoints"] = json_decode($row["checkpoints"]);
    //             $output1 = array();
    //             $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
    //             IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
    //              join mfg_work_order_hdr c on a.work_order_id = c.id
    //              left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
    //              left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
    //              left join dispensing_details_hdr dd on a.id = dd.lot_id
    //              where a.work_order_id = '".$row["id"]."') as a left join
    //              (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
    //              WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
    //              GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                   $output2 = array();
    //                   $category = $row1["category"];
    //                   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
    //                     floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
    //                     (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
    //                     case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
    //                     case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
    //                     from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
    //                     FROM stock_book
    //                     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
    //                     left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
    //                       $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output2[] = $row2;
    //                         }
    //                     }
    //                     $row1["available_ars"] = $output2;
                        
    //                     $row1["containers"] = json_decode($row1["containers"]);
    //                     $row1["ars"] = json_decode($row1["ars"]);
    //                     if ($row1["status"] == "pending") {
    //                         $flag = 1;
    //                     }
                      
    //                                   $output1[] = $row1;
    //                 }
    //             }
                
                
                
                
    //             $row["fifo_method"] =$fifo_method;
    //             $row["materials"] = $output1;
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
            
    // }
    else if ($_GET["type"] == "getReadyBatchPlans_pk_sp_check") {
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
         $sql ="SELECT			d.LglNm as client_name,
							d.id,
                        s.total_qty,
                        a.id as a_id,
                        a.fg_intimation_raised_by,
                        a.fg_intimation_raised_date,
                        a.fg_intimation_receive_by,
                        a.fg_intimation_receive_date,
                        a.batch_number,
                        p.dosage_form,
                        a.bmr_no,
                        a.fg_sampling_intimation,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.batch_plan_id,
                        a.sampling_intimation,
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
                        a.bmr_start_by,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,              
                        p.product_name,
                        p.product_type,
                        b.pack_size,
                        d.TrdNm,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        p.manufactured_for,
          (SELECT COUNT(bmr_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bmr_status='checking') as bmr_status_count        

                        
                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                           left join client d on 
                    d.LglNm=p.manufactured_for
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                    LEFT JOIN samplingfg s ON
                        b.product_code = s.product_code
             where  a.plant_id ='".$_GET["plant_id"]."'
             and (dispensing_status ='Request Sent' or dispensing_status=' ')  and (a.receiving='done' or a.receiving=' ') and (a.bmr_status='checking' or a.bmr_status='0' or a.bmr_status='start')   GROUP by d.LglNm,
							d.id,
                        s.total_qty,  a_id, a.fg_intimation_raised_by, a.fg_intimation_raised_date, 
                        a.fg_intimation_receive_by, a.fg_intimation_receive_date, a.batch_number, p.dosage_form, 
                        a.bmr_no, a.fg_sampling_intimation, a.work_order_no, palnned_by, a.approved_by, 
                        a.lod_status, a.calculation_type, a.batch_commence_date, a.batch_plan_id,      
                        a.sampling_intimation, a.stage_checked_by,
                        a.tr_to_packing_dept_by,a.batch_complete_date,
                        a.stability,a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
                        a.no_of_lots,
                        min_yeild,
                         max_yeild,
                        a.bmr_start_by,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,              
                        p.product_name,
                        p.product_type,
                        b.pack_size,
                        d.TrdNm,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        lc_status,
                        lc_by,
                      lc_date,
                        p.manufactured_for  HAVING    bmr_status_count != '0'";
            //  and (dispensing_status ='Request Sent' or dispensing_status=' ')  and (a.receiving='done' or a.receiving=' ') and (a.bmr_status='checking' or a.bmr_status='0')  HAVING    bmr_status_count != '0'";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
            
    }
    // else if ($_GET["type"] == "getReadyBatchPlans_pk_sp_check") {
    //       $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' 
    //     and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //      $sql ="SELECT			d.LglNm as client_name,
				// 			d.id,
    //                     s.total_qty,
    //                     a.id,
    //                     a.fg_intimation_raised_by,
    //                     a.fg_intimation_raised_date,
    //                     a.fg_intimation_receive_by,
    //                     a.fg_intimation_receive_date,
    //                     a.batch_number,
    //                     p.dosage_form,
    //                     a.bmr_no,
    //                     a.fg_sampling_intimation,
    //                     a.work_order_no,
    //                     a.rm_qa_dislc_date AS palnned_by,
    //                     a.approved_by,
    //                     a.lod_status,
    //                     a.calculation_type,
    //                     a.batch_commence_date,
    //                     a.batch_plan_id,
    //                     a.sampling_intimation,
    //                     a.stage_checked_by,
    //                     a.tr_to_packing_dept_by,
    //                     a.batch_complete_date,
    //                     a.stability,
    //                     a.stability_reason,
    //                     a.process_validation,
    //                     a.qa_person,
    //                     a.qa_date,
    //                     a.no_of_lots,
    //                     uf.min_per AS min_yeild,
    //                     uf.max_per AS max_yeild,
    //                     a.bmr_start_by,
    //                     a.approved_by,
    //                     b.plan_no,
    //                     b.bfr_no,
    //                     b.mfr_no,
    //                     b.product_code,
    //                     b.batch_size,
    //                     p.product_name,
    //                     p.product_type,
    //                     p.grade,
    //                     b.pack_size,
    //                     d.TrdNm,
    //                     b.pack_unit,
    //                     a.dispense_request_sent_by,
    //                     a.dispense_request_sent_on,
    //                     a.dispensing_status,
    //                     rm_disp_completed_by,
    //                     a.pm_qa_dislc_status AS lc_status,
    //                     a.pm_qa_dislc_by AS lc_by,
    //                     a.pm_qa_dislc_date AS lc_date,
    //       (SELECT COUNT(bmr_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.bmr_status='checking') as bmr_status_count        

                        
    //                 FROM
    //                     mfg_work_order_hdr a
    //                 JOIN batch_planning b ON
    //                     a.batch_plan_id = b.id AND a.plant_id = b.plant_id
    //                 JOIN product p ON
    //                     b.product_code = p.product_code AND b.plant_id = p.plant_id
    //                       left join client d on 
    //                 d.LglNm=p.manufactured_for
    //                 LEFT JOIN unitformula uf ON
    //                     uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
    //                 LEFT JOIN samplingfg s ON
    //                     b.product_code = s.product_code
    //          where  a.plant_id ='".$_GET["plant_id"]."'
    //          and (dispensing_status ='Request Sent' or dispensing_status=' ')  and (a.receiving='done' or a.receiving=' ') and (a.bmr_status='checking' or a.bmr_status='0')  HAVING    bmr_status_count != '0'";
            
        
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $calculation_type = $row['calculation_type'];
    //             $row["checkpoints"] = json_decode($row["checkpoints"]);
    //             $output1 = array();
    //             $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
    //             IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
    //              join mfg_work_order_hdr c on a.work_order_id = c.id
    //              left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
    //              left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
    //              left join dispensing_details_hdr dd on a.id = dd.lot_id
    //              where a.work_order_id = '".$row["id"]."') as a left join
    //              (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
    //              WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
    //              GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                   $output2 = array();
    //                   $category = $row1["category"];
    //                   $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
    //                     floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
    //                     (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
    //                     case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
    //                     case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
    //                     from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
    //                     FROM stock_book
    //                     WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
    //                     left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
    //                       $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output2[] = $row2;
    //                         }
    //                     }
    //                     $row1["available_ars"] = $output2;
                        
    //                     $row1["containers"] = json_decode($row1["containers"]);
    //                     $row1["ars"] = json_decode($row1["ars"]);
    //                     if ($row1["status"] == "pending") {
    //                         $flag = 1;
    //                     }
                      
    //                                   $output1[] = $row1;
    //                 }
    //             }
                
          
    //                     // ////////////////////////////////////////////////
    //               $output9 = array();
                     
    //                   $sql2="select * from pk_bmr_Equipment_data where work_order_id= '".$row["id"]."'"; 
  
    //                       $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output9[] = $row2;
    //                         }
    //                     }
    //                     // ////////////////////////////////////////////////
    //               $output5 = array();
                     
    //                   $sql2="select * from packing_quality_sample_check where work_order_id= '".$row["id"]."'"; 
  
    //                       $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output5[] = $row2;
    //                         }
    //                     }
    //                     // ////////////////////////////////////////////////
    //               $output6 = array();
                     
    //                   $sql2="select * from bmr_final_batch_plan where work_order_id= '".$row["id"]."'"; 
  
    //                       $result2 = $conn->query($sql2);
    //                     if ($result2->num_rows > 0) {
    //                         while ($row2 = $result2->fetch_assoc()) {
    //                             $output6[] = $row2;
    //                         }
    //                     }
    //                     // ////////////////////////////////////////////////
    //               $output3 = array();
                     
    //                   $sql2="select * from primary_packing where work_order_id= '".$row["id"]."'"; 
  
    //                       $result2 = $conn->query($sql2);
    //                                 if ($result2->num_rows > 0) {
    //                                     while ($row2 = $result2->fetch_assoc()) {
    //                                  $output2 = array();
    //                              $sql3="select * from primary_packing_dtl where primary_packing_id= '".$row2["id"]."'"; 
    //                               $result3 = $conn->query($sql3);
    //                                 if ($result3->num_rows > 0) {
    //                                     while ($row3 = $result3->fetch_assoc()) {
    //                                         $output2[] = $row3;
    //                                     }
    //                                 }
    //                                       $row2["primary_packing_dtl1"] = $output2;
                                            
    //                                         $output3[] = $row2;
    //                                     }
    //                                 }
    //                   // ////////////////////////////////////////////////
    //               $output4 = array();
                     
    //                   $sql2="select * from secondary_packing where work_order_id= '".$row["id"]."'"; 
  
    //                       $result2 = $conn->query($sql2);
    //                                 if ($result2->num_rows > 0) {
    //                                     while ($row2 = $result2->fetch_assoc()) {
    //                                  $output2 = array();
    //                              $sql3="select * from secondary_packing_dtl where secondary_packing_id= '".$row2["id"]."'"; 
    //                               $result3 = $conn->query($sql3);
    //                                 if ($result3->num_rows > 0) {
    //                                     while ($row3 = $result3->fetch_assoc()) {
    //                                         $output2[] = $row3;
    //                                     }
    //                                 }
    //                                       $row2["secondary_packing_dtl"] = $output2;
                                            
    //                                         $output4[] = $row2;
    //                                     }
    //                                 }
    //                   // ////////////////////////////////////////////////
                      
                      
                            
                
                
                
                
    //             $row["fifo_method"] =$fifo_method;
    //             $row["materials"] = $output1;
    //             // $row["packing_equipment"] = $output2;
    //             $row["primary_packing"] = $output3;
    //             $row["secondary_packing"] = $output4;
    //             $row["sample_check"] = $output5;
    //             $row["bmr_final_batch_plan"] = $output6;
    //             $row["pk_eq"] = $output9;
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
            
    // }
    
    
     else if ($_GET["type"] == "complete_bmr_master") {
            
           if($input["status"]=='Complete'){
               
                 $sql = "UPDATE mfg_work_order_hdr SET `bmr_status`='".$input["status"]."' ,`bmr_completed_by`='".$_GET["emp_id"].'-'.$entry_date."'   where id='".$input["id"]."'";
           }
           if($input["status"]=='review'){
               
                  $sql = "UPDATE mfg_work_order_hdr SET `bmr_status`='".$input["status"]."' ,`bmr_entry_by`='".$_GET["emp_id"].'-'.$entry_date."'   where id='".$input["id"]."'";
           }
 
        if ($conn->query($sql)) {
            
            
          
            
            
            
            echo json_encode(array("status"=>"success","msg"=>"  successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    
    else if ($_GET["type"] == "getReadyBatchPlans") {
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
         $sql ="SELECT a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date,a.bmr_status ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date,a.bmr_status ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND a.statusForCheckAndTranfer = 'Pending'  and a.pm_statusForCheckAndTranfer is null
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
                $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                
                
                      $output114 = array();
                 $sql114 = "SELECT * FROM bmr_process WHERE  product_code='" . $row['product_code'] . "' ORDER BY id DESC";
                      
                $result114 = $conn->query($sql114);
                if ($result114->num_rows > 0) {
                    while ($row114 = $result114->fetch_assoc()) {
                          $output114[] = $row114;
                        
                        }
                }
                
                
                
                
                
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $row["BMRDATA"] = $output114;
                
                
                
                
                $output[] = $row;
            }
        } 
        
                               

        echo json_encode($output);
           
    } 
    else if ($_GET["type"] == "getReadyBatchPlansMeha") {
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
         $sql ="SELECT a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date,a.bmr_status ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date,a.bmr_status ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND a.statusForCheckAndTranfer = 'Pending'  and a.pm_statusForCheckAndTranfer is null
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
                $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.grade,b.density,  b.uom,b.alternate_uom,b.material_nature,b.unit_conversion ,b.category,
                 COALESCE(b.material_name, p.product_name) AS material_name,wd.lod_status,
                wd.assay_status,IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join product p on a.material_code = p.product_code and c.plant_id = p.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a 
                 left join
                 ( SELECT material_code,balance_qty as avbl_stock from vw_stock_summary
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 ) as b on a.material_code = b.material_code 
                 and a.material_type='Raw Material'
                 left join
                 (SELECT 
                            material_code, 
                            (SELECT SUM(qty) 
                             FROM fg_stock_book 
                             WHERE plant_id = '".$_GET["plant_id"]."' 
                               AND material_code IN (
                                   SELECT material_code 
                                   FROM work_order_batch_lots 
                                   WHERE work_order_id = '".$row["id"]."'
                               )
                            ) - 
                            (SELECT SUM(qty) 
                             FROM fg_stock_book 
                             WHERE plant_id = '".$_GET["plant_id"]."' 
                               AND material_code IN (
                                   SELECT material_code 
                                   FROM work_order_batch_lots 
                                   WHERE work_order_id = '".$row["id"]."'
                               )
                            ) AS avbl_stock 
                        FROM fg_stock_book 
                        WHERE plant_id = '".$_GET["plant_id"]."' 
                        AND material_code IN (
                            SELECT material_code 
                            FROM work_order_batch_lots 
                            WHERE work_order_id = '".$row["id"]."'
                        )) as c on a.material_code = c.material_code
                        and a.material_type='Intermediate'";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "getReadyBatchPlansPacking") {
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
         $sql ="SELECT a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date,a.bmr_status ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date,a.bmr_status ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND   a.pm_receiving='done'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "getComplitedReadyBatchPlans") {
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
         $sql ="SELECT a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date,a.bmr_status ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date,a.bmr_status ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND a.bmr_status = 'Complete' 
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    
    else if ($_GET["type"] == "getReadyBatchPlansForChecking") {
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
         $sql ="SELECT a.actual_yeild,a.yeild_percentage,a.no_of_days,a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND a.statusForCheckAndTranfer = 'For_Checking' 
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "getReadyBatchPlansForCheckingPacking") {
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
         $sql ="SELECT a.pm_actual_yeild as actual_yeild,a.pm_yeild_percentage as yeild_percentage,a.pm_no_of_days as no_of_days,a.id,a.oprpccp1,
         a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.pm_batch_commence_date as batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.pm_batch_complete_date as batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND   a.pm_receiving='bpr Start'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "getReadyBatchPlansLog") {
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
         $sql ="SELECT a.bmrFile,a.batchCheckRemark,a.actual_yeild,a.yeild_percentage,a.no_of_days,a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,
         a.approved_by, a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type, p.dosage_form,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  AND a.statusForCheckAndTranfer = 'Checked' 
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                 $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    
    
    else if ($_GET["type"] == "getReadyBatchPlans_sp") {
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
         $sql ="SELECT a.id,p.label_claim,p.shelf_life,a.bmr_status,a.batch_number,a.bmr_no,p.dosage_form,b.entry_date as plan_date,a.Equipments,a.Genral_Instruction,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.mfg_date,a.exp_date,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."' and a.bmr_status='0'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
                $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
              echo  $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "get_QC_ReadyBatchPlans_sp") {
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
         $sql ="SELECT a.raw_qc_intimation_raised_by,p.label_claim,p.shelf_life,a.batch_number,a.bmr_no,p.dosage_form,b.entry_date as plan_date,
         a.bmr_no,a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, (SELECT COUNT(qc_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.qc_status!='0') as int_status,
             (SELECT COUNT(raw_qc_sample_qty) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and raw_qc_sample_qty!='0') as raw_qc_sample_qty_count,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'  and (a.raw_qc_intimation='sent' or a.raw_qc_intimation=' ')
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
                $sql = $sql." and rm_qa_dislc_status='Approved' HAVING   int_status!='0'";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
              echo  $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             
                  // $lot=$row["no_of_lots"];
                // $qc_status_count=$row["qc_status_count"];
                // echo ('no_of_lots='+ $lot);
                // echo('$qc_status_count='+$qc_status_count);
                    if($row["no_of_lots"]==$row["raw_qc_sample_qty_count"]){
                   $sql="UPDATE mfg_work_order_hdr set  raw_qc_intimation='received' WHERE id='".$row["id"]."'";       
                         $conn->query($sql);
                }
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
         
    } 
    else  if ($_GET["type"] == "saveparams") {
        $sql = "INSERT INTO lot_parameter (parameter, plant_id) VALUES ('".$input["param"]."','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else  if ($_GET["type"] == "saveCoaparams") {
        $sql = "INSERT INTO coa_parameter (parameter, plant_id) VALUES
        ('".$input["param"]."','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "getparamss") {
        $output = array();
        $sql = "SELECT * FROM lot_parameter ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getparamssss") {
        $output = array();
        $sql = "SELECT * FROM coa_parameter ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
       else if ($_GET["type"] == "deleteCOATerm") {
        $sql = "DELETE FROM coa_parameter WHERE id='".$_GET["id"]."'";
       // $sql = "DELETE from terms WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
       else if ($_GET["type"] == "deleteTerm") {
        $sql = "DELETE FROM lot_parameter WHERE id='".$_GET["id"]."'";
       // $sql = "DELETE from terms WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "get_QC_ReadyBatchPlans_sp_check") {
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
         $sql ="SELECT a.raw_qc_intimation_raised_by,p.label_claim,p.shelf_life,a.batch_number,a.bmr_no,p.dosage_form,b.entry_date as plan_date,a.bmr_no,a.raw_qc_sample_qty,
                a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
                a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             (SELECT COUNT(raw_qc_sample_qty) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.raw_qc_sample_qty!='0') as raw_qc_sample_qty_count,
             (SELECT COUNT(check_by) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.check_by!='0') as check_by_count,
             
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."' and   (a.raw_qc_intimation='sent' or a.raw_qc_intimation=' ' or a.raw_qc_intimation='received')
             and dispensing_status ='Request Sent' ";
            //  a.bmr_status='start' and a.raw_qc_intimation='done'
            //  and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' HAVING   raw_qc_sample_qty_count!='0' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
              echo  $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                     if($row["no_of_lots"]==$row["check_by_count"]){
                   $sql="UPDATE mfg_work_order_hdr set  raw_qc_intimation='check' WHERE id='".$row["id"]."'";       
                         $conn->query($sql);
                }
             
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
         
    } 
    else if ($_GET["type"] == "get_QC_ReadyBatchPlans_sp_Approve") {
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
         $sql ="SELECT a.raw_qc_intimation_raised_by,p.label_claim,p.shelf_life,a.batch_number,a.bmr_no,p.dosage_form,b.entry_date as plan_date,
         a.bmr_no,a.raw_qc_sample_qty,a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
              (SELECT COUNT(check_by) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.check_by!='0') as check_by_count,
              (SELECT COUNT(approve_by) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.approve_by!='0') as approve_by_count,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."' and (a.raw_qc_intimation='sent' or a.raw_qc_intimation=' ' or a.raw_qc_intimation='received' and a.raw_qc_intimation='check')
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved'  HAVING   check_by_count!='0' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
              echo  $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
            //      $output2 = array();
            //          $sql1 ="SELECT * FROM bmr_intimation_checklist where work_order_id =  '".$row["id"]."' ";
            //       $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //             $output2[] = $row1;
            //             }
            //         }
            //  $row['data'] = $output2;
            
            
                 if($row["no_of_lots"]==$row["approve_by_count"]){
                   $sql="UPDATE mfg_work_order_hdr set  raw_qc_intimation='Approve' WHERE id='".$row["id"]."'";       
                         $conn->query($sql);
                }
             
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
         
    } 
    else if ($_GET["type"] == "fg_coa") {
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
         $sql ="SELECT a.coa_checklist_ipqa,a.raw_qc_intimation_raised_by,p.label_claim,p.shelf_life,a.batch_number,a.bmr_no,p.dosage_form,b.entry_date as plan_date,a.bmr_no,a.raw_qc_sample_qty,a.id,a.oprpccp1,a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."' and ( a.bmr_status='start' or a.bmr_status='Complete') and a.raw_qc_intimation='Approve'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){

               $sql = $sql." and rm_qa_dislc_status='Approved' order by a.id DESC";
                 
            }
            else{
              echo  $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                 $output2 = array();
                     $sql1 ="SELECT * FROM bmr_intimation_checklist where work_order_id =  '".$row["id"]."' ";
                   $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                        $output2[] = $row1;
                        }
                    }
             $row['data'] = $output2;
             
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
         
    } 
    else if ($_GET["type"] == "getunder_prod_BatchPlans_sp") {
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
         $sql ="SELECT a.id,a.raw_qc_intimation,p.shelf_life,p.label_claim,a.batch_number,a.bmr_no,p.dosage_form,b.entry_date as plan_date,a.oprpccp1,
             a.oprpccp2,a.blending,a.sifting,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
             a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,a.batch_complete_date,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date, (SELECT COUNT(work_order_id) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id) as sift_done,
                (SELECT COUNT(qc_status) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and qc_status!='0') as qc_status_count ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."' and (a.bmr_status='start' or a.bmr_status='0')
             and dispensing_status ='Request Sent' and a.raw_qc_intimation=' ' "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23 
               $sql = $sql." and rm_qa_dislc_status='Approved'  HAVING   sift_done != 0 ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
            
                
                
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                
                // $lot=$row["no_of_lots"];
                // $qc_status_count=$row["qc_status_count"];
                // echo ('no_of_lots='+ $lot);
                // echo('$qc_status_count='+$qc_status_count);
                    if($row["no_of_lots"]==$row["qc_status_count"]){
                    $sql="UPDATE mfg_work_order_hdr set  raw_qc_intimation='sent' WHERE id='".$row["id"]."'";       
                         $conn->query($sql);
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "getCompletedBatch") {
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
         $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,p.dosage_form,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by, ";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_qa_dislc_status as lc_status,a.rm_qa_dislc_by as lc_by,a.rm_qa_dislc_date as lc_date ";
             }else{
                $sql = $sql." a.pm_qa_dislc_status as lc_status,a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."' 
             and dispensing_status ='Request Sent' and tr_to_packing_dept_by!=''  "; 
            if($_GET["material_type"] == 'Raw Material'){
                //$sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
                       
            //new vivek 06.03.23
               $sql = $sql." and rm_qa_dislc_status='Approved' ";
                                    //old  06.03.23
               // $sql = $sql." and rm_disp_completed_by!='' and rm_qa_dislc_status='Approved' ";
            }
            else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock  from (SELECT a.*,b.material_type,b.category,b.material_subtype,b.material_name,wd.lod_status,wd.assay_status,
                IFNULL(dd.id,0) as dispence_id,dd.qa_status,dd.prod_status,dd.qa_checking,dd.prod_checking FROM work_order_batch_lots  a 
                 join mfg_work_order_hdr c on a.work_order_id = c.id
                 left join mfg_work_order_dtl wd on wd.work_order_id = c.id and wd.material_code = a.material_code 
                 left join material b on a.material_code = b.material_code and c.plant_id = b.plant_id 
                 left join dispensing_details_hdr dd on a.id = dd.lot_id
                 where a.work_order_id = '".$row["id"]."') as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE plant_id='".$_GET["plant_id"]."' and material_code in(SELECT material_code from work_order_batch_lots where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                      
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $output2 = array();
                      $category = $row1["category"];
                      $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$calculation_type."' = 'Lod Basis' AND  '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                        case when '".$calculation_type."' ='Assay Basis' AND '".$category."' = 'Active' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no"; 
  
                          $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
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
            /*$output = Array();
            $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT id, stage, expected_start_date, expected_complete_date, supervisors, workers FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);*/
    } 
    else if ($_GET["type"] == "from_prod") {
          $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' 
    //     and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
         $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,p.dosage_form,
         a.lod_status,a.calculation_type,a.batch_commence_date,a.stage_checked_by,a.tr_to_packing_dept_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,a.no_of_lots,uf.min_per as 
             min_yeild,uf.max_per as max_yeild,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,
             p.product_type,
             b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,
             a.dispensing_status,rm_disp_completed_by FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id 
             left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id
             where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent' and tr_to_packing_dept_by!=''  "; 

        
          	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
}


    else if ($_GET["type"] == "save_sample_qty") {
        
        $sql = "UPDATE sp_bmr_sifting  SET raw_qc_sample_qty_by='".$_GET['emp_id']."' ,raw_qc_sample_qty='".$_GET['sample_qty']."',raw_qc_sample_qty__date='$entry_date',qc_status ='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
              $json_obj = json_encode($input["lots_data"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
  $sql1 = "INSERT INTO `bmr_intimation_checklist`( `work_order_id`, `parameter`, `lots`,observation,remark,sp_bmr_sifting_id)
       VALUES ('".$_GET["work_id"]."','".$values["parameter"]."','".json_encode($values["lotsss"])."','".$values["Observation"]."','".$values["result"]."','".$_GET["id"]."')";
       $conn->query($sql1);       
                }
        
     
            
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "update_qc_sample") {
        
        $sql = "UPDATE sp_bmr_sifting  SET qc_status='".$_GET['status']."' ,check_by='".$_GET['emp_id']."',check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
    
            
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "update_qc_sample_update") {
        
        $sql = "UPDATE sp_bmr_sifting  SET qc_status='".$_GET['status']."' ,approve_by='".$_GET['emp_id']."',approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
    
            
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
//     else if ($_GET["type"] == "save_sample_qty") {
        
//         $sql = "UPDATE mfg_work_order_hdr SET raw_qc_sample_qty_by='".$_GET['emp_id']."' ,raw_qc_sample_qty='".$_GET['sample_qty']."',raw_qc_sample_qty__date='$entry_date',raw_qc_intimation='done' WHERE id='".$_GET["id"]."'";
//         if ($conn->query($sql)) {
//               $json_obj = json_encode($input["lots_data"]);
//               $array = json_decode($json_obj, true);
//                  $k=1;
//                 foreach ($array as $values)
//                 {
//   $sql1 = "INSERT INTO `bmr_intimation_checklist`( `work_order_id`, `parameter`, `lots`,observation,remark)
//       VALUES ('".$_GET["id"]."','".$values["parameter"]."','".json_encode($values["lotsss"])."','".$values["Observation"]."','".$values["lots"]."')";
//       $conn->query($sql1);       
//                 }
        
     
            
//             echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
//         } else {
//             echo json_encode(array("status"=>"failed","msg"=>$conn->error));
//         }
//     }
    
    else if ($_GET["type"] == "save_coa_checklist") {
        
        
          $sql = "UPDATE mfg_work_order_hdr SET coa_checklist_ipqa ='1'  WHERE id='".$_GET["id"]."'";
          $conn->query($sql);
       
              $json_obj = json_encode($input["lots_data"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                 
                foreach ($array as $values)
                {
                   $sql1 = "INSERT INTO `coa_checklist`(plant_id, `work_order_id`, `parameter`,observation,specifications,test_method)
                   VALUES ('".$_GET["plant_id"]."','".$_GET["id"]."','".$values["parameter"]."','".$values["Observation"]."','".$values["specifications"]."','".$values["test_method"]."')";
                   
                   $conn->query($sql1); 
                    if ($conn->query($sql)) {
                        $k = 1;
                    }else{
                        $k = 0;
                    }
       
                }
        
        if($k = 1){
            
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
        
        
    }
    
    
    
    // else if ($_GET["type"] == "update_qc_sample") {
    //     $sql = "UPDATE mfg_work_order_hdr SET raw_qc_check_by='".$_GET['emp_id']."' ,raw_qc_check_date='$entry_date',raw_qc_intimation='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
    //     } else {
    //         echo json_encode(array("status"=>"failed","msg"=>$conn->error));
    //     }
    // }
    
    
    else if ($_GET["type"] == "update_qc_sample_app") {
        $sql = "UPDATE mfg_work_order_hdr SET raw_qc_approve_by = '".$_GET['emp_id']."' , raw_qc_approve_date = '$entry_date',
        raw_qc_intimation='".$_GET["status"]."' WHERE id='".$_GET["id"]."'"; 
        
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    
    
    else if ($_GET["type"] == "saveWorkAllocation") {
        $sql = "UPDATE bmr_stages SET ".$_GET['action']."='".$_GET['value']."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "saveSupervisors") {
        $sql = "UPDATE bmr_stages SET supervisors='".json_encode($input["supervisors"])."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "saveWorkers") {
        $sql = "UPDATE bmr_stages SET workers='".json_encode($input["workers"])."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Record Updated successfully!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    }
    else if ($_GET["type"] == "startBatch") {
        $sql = "UPDATE bmr SET status='start', start_by='".$_GET["emp_id"]."', start_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "startBatch_sp") {
        $id=$_GET["id"];
        $plant=$_GET["plant_id"];
        $bmr_no="BMR".$plant.$id;
        $sql = "UPDATE mfg_work_order_hdr SET bmr_status='start', bmr_start_by='".$_GET["emp_id"]."', bmr_start_date='$entry_date',bmr_no='$bmr_no' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getInprocessBatches") {
        $output = Array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date FROM bmr b WHERE b.status='start'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $flag = 0;
                $output1 = Array();
                $sql1 = "SELECT *, DATE(start_date) as start_date, TIME(start_date) as start_time FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["stage"] == "DISPENSING" && $row1["status"] == 'receive') {
                            
                        } else {
                            if ($flag == 0 && ($row1["status"] !== 'complete')) {
                                $row["current_stage"] = $row1["stage"];
                                $row["stage_no"] = $row1["stage_no"];
                                $flag = 1;
                            }
                            $output1[] = $row1;
                        }
                    }
                }
                if ($row["current_stage"] !== "DISPENSING") {
                    $row["stages"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDispensingDetails") {
        $sql = "SELECT * FROM bmr_stages WHERE stage='DISPENSING' AND id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM bmr WHERE id='".$row["bmr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_code"] = $row1["product_code"];
                        $row["batch_no"] = $row1["batch_no"];
                        $row["batch_size"] = $row1["batch_size"];
                        $row["mfr_no"] = $row1["mfr_no"];
                    }
                }
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                        $row["packing_style"] = $row1["packing_style"];
                        $row["dosage_type"] = $row1["dosage_type"];
                        $row["dosage_form"] = $row1["dosage_form"];
                        $row["excipient"] = $row1["excipient"];
                        $row["shelf_life"] = $row1["shelf_life"];
                        $row["color_used"] = $row1["color_used"];
                        $row["label_claim"] = $row1["label_claim"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_material WHERE no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                                $row1["grade"] = $row2["grade"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                echo json_encode($row);
            }
        }
    } else if ($_GET['type'] == 'completedBMRPDF1'){
            if($_GET['user_no'] == 'GMP007'){
                $sql = "SELECT b.*, DATE(b.start_date) as start_date, DATE(b.complete_date) as complete_date, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.id='".$_GET['id']."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row["materials"] = json_decode($row['materials']);
                        $output1 = Array();
                        $sql1 = "SELECT *, TIME(start_date) as start_time, TIME(complete_date) as complete_time FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row1["details"] = json_decode($row1["details"]);
                                $row1["instructions"] = json_decode($row1["instructions"]);
                                $row1["clearances"] = json_decode($row1["clearances"]);
                                if ($row1["isclearance"] == "inprocess") {
                                    $sql2 = "SELECT * FROM lineclearance WHERE status='active' AND id='".$row1["clearance_no"]."'";
                                    $result2 = $conn->query($sql2);
                                    if ($result2->num_rows > 0) {
                                        while ($row2 = $result2->fetch_assoc()) {
                                            $row1["isclearance"] = "active";
                                            $row1["clearances"] = json_decode($row2["checkpoints"]);
                                            $row1["clearance_request_by"] = $row2["request_by"];
                                            $row1["clearance_request_date"] = $row2["request_date"];
                                            $row1["clearance_by"] = $row2["entry_by"];
                                            $row1["clearance_date"] = $row2["entry_date"];
                                        }
                                    }
                                }
                                $output1[] = $row1;
                            }
                        }
                        $row["stages"] = $output1;
                        $_GET['product_code'] = $row['product_code'];
                        $_GET['product_name'] = $row['product_name'];
                        $_GET['batch_no'] = $row['batch_no'];
                        $_GET['bmr_no'] = $row['bmr_no'];
                        $_GET['entry_date'] = $row['entry_date'];
                        $_GET['check_date'] = $row['check_date'];
                        $_GET['approve_date'] = $row['approve_date'];
                        class MYPDF extends TCPDF {
                            public function Header() {
                                $this->writeHTMLCell($w='', $h='', $x='', $y='', $this->header, $border=0, $ln=0, $fill=0, $reseth=true, $align='L', $autopadding=true);
                                $this->SetLineStyle( array( 'width' => 0.40, 'color' => array(27, 30, 35)));
                                $this->Line(14, 14, $this->getPageWidth()-14, 14); 
                                $this->Line($this->getPageWidth()-14, 14, $this->getPageWidth()-14,  $this->getPageHeight()-14);
                                $this->Line(14, $this->getPageHeight()-14, $this->getPageWidth()-14, $this->getPageHeight()-14);
                                $this->Line(14, 14, 14, $this->getPageHeight()-14);
                                if($this->page==1){
                                }else{
                            $table='<style>td { border:solid 1px BCBBBA;}</style>
                                    <table style="font-size:10px" cellpadding="3">
                                        <tr>
                                            <td style="width:80%;text-align:center;font-weight:bold;">
                                                <span style="font-size:12px;">INJECT CARE PARENTERALS PVT. LTD.</span><br>
                                                <span style="font-size:9px;">Plot No.130, Silvassa Road, G.I.D.C., Vapi-396 195, Gujarat, India<br></span>
                                            </td>
                                            <td style="width:20%;">';
                                                $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/user/'.$_GET['user_no'].'.png'),169,15,20);
                                                $table.='
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:100%" align="center">BATCH MANUFACTURING RECORD</td>
                                        </tr>
                                        <tr>
                                            <td style="width:14%">Product</td>
                                            <td style="width:55%">'.$_GET['product_name'].'</td>
                                            <td style="width:11%">Doc. No.</td>
                                            <td style="width:20%">'.$_GET['bmr_no'].'</td>
                                        </tr>
                                        <tr>
                                            <td>Product Code</td>
                                            <td>'.$_GET['product_code'].'</td>
                                            <td>Ver. No.</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>Batch No.</td>
                                            <td>'.$_GET['batch_no'].'</td>
                                            <td>Page No </td>
                                            <td>Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages().'</td>
                                        </tr>
                                    </table>';
                                    $this->SetY('15'); $this->writeHTML($table, true, false, false, false, '');
                                }
                            }
                            public function Footer() {
                        $table='<style>td { border:solid 1px BCBBBA;}</style>
                                <table style="font-size:10px" cellpadding="3" align="center">
                                    <tr>
                                        <td colspan="2">Prepared By</td>
                                        <td colspan="2">Checked By</td>
                                        <td>Approved By</td>
                                    </tr>
                                    <tr>
                                        <td>Quality Assurance<br>Sign & Date</td>
                                        <td>Production<br>Sign & Date</td>
                                        <td>Quality Assurance<br>Sign & Date</td>
                                        <td>Production Head<br>Sign & Date</td>
                                        <td>QA Head<br>Sign & Date</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>'.date('d/m/Y', strtotime($_GET['entry_date'])).'</td>
                                        <td></td>
                                        <td>'.date('d/m/Y', strtotime($_GET['check_date'])).'</td>
                                        <td>'.date('d/m/Y', strtotime($_GET['approve_date'])).'</td>
                                    </tr>
                                </table>';
                                $this->SetY(-40);
                                $this->writeHTML($table, true, false, false, false, '');
                            }
                        }
                        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetMargins(15, 60, 15, 15);
                        $pdf->SetAutoPageBreak(TRUE, 35);
                        $pdf->AddPage('P', 'A4');
                        $pdf->SetY(15);
                        $pdf->SetFont ('Times', '', '10' , '', 'default', true );
                        $html.='
                        <style>
                            table tr td {
                                border:solid 1px BCBBBA;
                            }
                            .tablenone tr td {
                                border:none;
                            }
                        </style>
                        <table cellpadding="7">
                            <tr>
                                <td style="width:80%;text-align:center;font-weight:bold;">
                                    <span style="font-size:12px;">INJECT CARE PARENTERALS PVT. LTD.</span><br>
                                    <span style="font-size:9px;">Plot No.130, Silvassa Road, G.I.D.C., Vapi-396 195, Gujarat, India<br></span>
                                </td>
                                <td style="width:20%;">';
                                $pdf->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/user/'.$_GET['user_no'].'.png'),164,22,24,14);
                                $html.='
                                </td>
                            </tr>
                        </table>
                        <h3 align="center">BATCH MANUFACTURING RECORD</h3>
                        <h3 align="center">PART-I</h3>
                        <table cellpadding="3">
                            <tr>
                                <td style="width:25%"><b>Product Code</b></td>
                                <td style="width:15%">'.$row['product_code'].'</td>
                                <td style="width:20%"><b>Effective Batch No.</b></td>
                                <td style="width:15%"></td>
                                <td style="width:15%"><b>Version No.</b></td>
                                <td style="width:10%">00</td>
                            </tr>
                            <tr>
                                <td><b>Document No</b></td>
                                <td>'.$row['bmr_no'].'</td>
                                <td><b>Effective Date</b></td>
                                <td></td>
                                <td colspan="2">Page '.$pdf->getAliasNumPage().' of '.$pdf->getAliasNbPages().'</td>
                            </tr>
                            <tr>
                                <td><b>Product Name</b></td>
                                <td colspan="5">'.$row['product_name'].'</td>
                            </tr>
                            <tr>
                                <td><b>Generic Name</b></td>
                                <td colspan="5">'.$row['generic_name'].'</td>
                            </tr>
                            <tr>
                                <td><b>Label Claim</b></td>
                                <td colspan="5">'.$row['label_claim'].'</td>
                            </tr>
                            <tr>
                                <td style="width:25%"><b>Batch No.</b></td>
                                <td style="width:25%">'.$row['batch_no'].'</td>
                                <td style="width:25%"><b>Batch Size</b></td>
                                <td style="width:25%">'.$row['batch_size'].'</td>
                            </tr>
                            <tr>
                                <td><b>MFG. Date</b></td>
                                <td></td>
                                <td><b>EXP. Date</b></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><b>STD. Batch Size<br>(In Units)</b></td>
                                <td></td>
                                <td><b>STD. Batch Size<br>(In kg)</b></td>
                                <td></td>
                            </tr>
                            <tr>		
                                <td><b>MARKET</b></td>
                                <td></td>
                                <td><b>Mfg. Lic. No.</b></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><b>Issued By QA<br>Sign & Date</b></td>
                                <td></td>
                                <td><b>Received By Production<br>Sign & Date</b></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><b>MFG. Commenced On</b></td>
                                <td></td>
                                <td><b>MFG. Completed On</b></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><b>Yield Limit : (97.00 % to 99.5%) & (87 % to 96.99 % for Batch size below 5000 )</b></td>
                                <td></td>
                                <td><b>Released Date</b></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" align="center"><b>BATCH MANUFACTURING RECORD REVIEW [AFTER COMPLETION OF MFG. ACTIVITY]</b></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr align="center">
                                <td><b>Production Officer<br>Sign/Date</b></td>
                                <td><b>Production Head<br>Sign/Date</b></td>
                                <td><b>Reviewed By Q.A.<br>Sign/ Date</b></td>
                                <td><b>Approved by - QA Head<br>Sign/Date</b></td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h3 align="center">TABLE NO.: 01</h3>
                        <table cellpadding="3" align="center">
                            <tr>
                                <td style="width:12%">Section</td>
                                <td style="width:68%">Content</td>
                                <td style="width:20%">Page No.</td>
                            </tr>
                            <tr>
                                <td>1.</td>
                                <td align="left">General instruction</td>
                                <td>03 to 03</td>
                            </tr>
                            <tr>
                                <td>2.</td>
                                <td align="left">List of Equipment</td>
                                <td>04 to 04</td>
                            </tr>
                            <tr>
                                <td>3.</td>
                                <td align="left">Calculation for fill value</td>
                                <td>05 to 05</td>
                            </tr>
                            <tr>
                                <td>4.</td>
                                <td align="left">Dispensing of Raw material</td>
                                <td>06 to 06</td>
                            </tr>
                            <tr>
                                <td>5.</td>
                                <td align="left">Dispensing of  primary packing material</td>
                                <td>08 to 08</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h3 align="center">01: GENERAL INSTRUCTION</h3>
                        <table cellpadding="3" class="tablenone">
                            <tr>
                                <td style="width:5%">1.</td>
                                <td style="width:95%">Enter each area as per respective entry procedure<br>
                                    <table cellpadding="3" class="tablenone">
                                        <tr>
                                            <td style="width:5%;border:none;">a.</td>
                                            <td style="width:95%;border:none;">Gents entry and exit procedure for manufacturing area -SOP- PRD/039</td>
                                        </tr>
                                        <tr>
                                            <td style="border:none;">b.</td>
                                            <td style="border:none;">Entry and exit to/from washing area. SOP- PRD/001</td>
                                        </tr>
                                        <tr>
                                            <td style="border:none;">c.</td>
                                            <td style="border:none;">Entry and exit to/from sterile area. SOP- PRD/002</td>
                                        </tr>
                                        <tr>
                                            <td style="border:none;">d.</td>
                                            <td style="border:none;">Entry and exit procedure for  Visitors- PRD/062</td>
                                        </tr>
                                        <tr>
                                            <td style="border:none;">e.</td>
                                            <td style="border:none;">Ladies entry and exit procedure for manufacturing area- PRD/065</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>2.</td>
                                <td>Ensure that gowns are cleaned and sterilized where required. Gloves are intact leak proof and virgin.- PRD/005 & PRD/006</td>
                            </tr>
                            <tr>
                                <td>3.</td>
                                <td>Ensure that all the equipments and accessories come into contact of product are sterile. Example filling assembly, hopper, rubber stopper assembly etc.- PRD/022</td>
                            </tr>
                            <tr>
                                <td>4.</td>
                                <td>Ensure that proper status labeling at all stages in manufacturing process- PRD/016, PRD/049</td>
                            </tr>
                            <tr>
                                <td>5.</td>
                                <td>Ensure that equipments instruments used for both manufacturing and packing at all stages are calibrated and in good state of use. - PRD/032, PRD/033, PRD/037, PRD/050</td>
                            </tr>
                            <tr>
                                <td>6.</td>
                                <td>Wipe the exterior of all equipment, or the machine with sterile lint free mop dipped in disinfectant solution before taking into the area.</td>
                            </tr>
                            <tr>
                                <td>7.</td>
                                <td>Ensure that equipments are cleaned and sterilized after use.</td>
                            </tr>
                            <tr>
                                <td>8.</td>
                                <td>Ensure clean room procedure followed in clean area.- PRD/003, PRD/004</td>
                            </tr>
                            <tr>
                                <td>9.</td>
                                <td>Ensure adherence to the cGMP.</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h3 align="center">02:  LIST OF EQUIPMENTS</h3>
                        <h3 align="center">TABLE NO.: 2.1</h3>
                        <table cellpadding="3">
                            <tr style="font-weight:bold;align:center;">
                                <td style="width:10%">SR.NO.</td>
                                <td style="width:45%">EQUIPMENT TYPE</td>
                                <td style="width:45%">MAJOR EQUIPMENT</td>
                            </tr>';
                            $counter = 1;
                            for ($b = 0; $b < count($output1); $b++) {
                                $list = $output1[$b];
                                $eqipments = json_decode($list['equipments']);
                                for($c = 0; $c < count($eqipments); $c++){
                                    $eqilist = $eqipments[$c];
                                    $html.='
                                    <tr>
                                        <td align="center">'.$counter++.'</td>
                                        <td>'.$eqilist->equipment_type.'</td>
                                        <td>'.$eqilist->equipment_name.'</td>
                                    </tr>';
                                }
                            }
                            $html.='
                        </table>
                        <br><br>
                        Note:  Tick Mark  √  above on used equipment No.  and single horizontal strike out on remaining equipment No.
                        <br pagebreak="true"/>
                        <h3 align="center">SECTION 03: CALCULATION FOR FILL VALUE</h3>
                        <table cellpadding="3" class="tablenone">
                            <tr>
                                <td style="width:25%">Bulk Lot No.</td>
                                <td>:</td>
                            </tr>
                            <tr>
                                <td>A.R.No.</td>
                                <td>:</td>
                            </tr>
                            <tr>
                                <td>Potency / Assay</td>
                                <td>:</td>
                            </tr>
                            <tr>
                                <td>Actual fill Quantity</td>
                                <td>gm/vial</td>
                            </tr>
                        </table><br><br>
                        Target fills wt gm/vial :<br><br><br>
                        <table cellpadding="3" class="tablenone" nobr="true">
                            <tr align="center">
                                <td>Calculation done by:<br>Production Officer<br>Sign & Date</td>
                                <td>Counter checked by:<br>Production Head<br>Sign & Date</td>
                                <td>Calculation checked by:<br>IPQA officer<br>Sign & Date</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h3 align="center">SECTION 03: DISPENSING OF RAW MATERIAL SOP-STR /005</h3>
                        Area cleaned as per SOP NO. STR/007. Previous product: ________________________Batch No.:______________<br>
                        Area cleanliness checked by Stores (Sign & Date):_________________ Production (Sign & Date): _______________<br>
                        <h3 align="center">TABLE NO.: 03  LINE CLEARANCE CHECKLIST SOP-QAD/008</h3>
                        <table cellpadding="3">
                            <tr>
                                <td style="width:10%">Sr. No.</td>
                                <td style="width:70%">Check point</td>
                                <td style="width:10%">Stores</td>
                                <td style="width:10%">QA</td>
                            </tr>';
                            for ($b = 0; $b < count($output1); $b++) {
                                $list = $output1[$b];
                                if($list['stage'] == 'Dispensing'){
                                    $counter = 1;
                                    for($c = 0; $c < count($list['clearances']); $c++){
                                        $list1 = $list['clearances'][$c];
                                        $html.='
                                        <tr>
                                            <td>'.$counter++.'</td>
                                            <td>'.$list1->checkpoint.'</td>
                                            <td></td>
                                            <td>'.$list1->observation.'</td>
                                        </tr>';
                                    }
                                }
                            }
                            $html.='
                            <tr>
                                <td colspan="2">Line clearance given by (Sign & Date)</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                        Note: OK - If found satisfactory, NOT OK - If not satisfactory.
                        <h3 align="center">TABLE NO.: 04 BILL OF MATERIAL (MATERIAL FOR BULK)</h3>
                        *Manufacturing Date of Blend:___________ Total Hold time of Blend:_____________ (Limit: NMT _______)<br><br>
                        <table cellpadding="3">
                            <tr style="font-weight:bold">
                                <td style="width:11%">Item Code</td>
                                <td style="width:18%">Item Name</td>
                                <td style="width:12%">Standard Qty. (For 10000) Vials</td>
                                <td style="width:12%">Required Qty. (In Kg)</td>
                                <td style="width:12%">Issued Qty. (In Kg)</td>
                                <td style="width:11%">A.R. No.</td>
                                <td style="width:12%">Mfg. Date</td>
                                <td style="width:12%">Exp. Date</td>
                            </tr>';
                            $materials = $row['materials'];
                            for ($j = 0; $j < count($materials); $j++) {
                                $material = $materials[$j];
                                $issued = $material->gross_wt - $material->tare_wt;
                                $html.='
                                <tr nobr="true">
                                    <td>'.$material->material_code.'</td>
                                    <td>'.$material->material_name.'</td>
                                    <td>'.$material->batch_qty.'</td>
                                    <td>'.$material->req_qty.'</td>
                                    <td>'.$issued.'</td>
                                    <td>'.$material->ar_no.'</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                ';
                            }
                $html.='</table>
                        <div></div>
                        <table cellpadding="3" class="tablenone" nobr="true">
                        	<tr>
                        	    <td style="border:none;">Material Issued By<br>(Stores)<br><br>Sign :_________________<br>Date :_________________</td>
                        	    <td style="border:none;">Material checked By<br>(QA)<br><br>Sign :_________________<br>Date :_________________</td>
                        	    <td style="border:none;">Material checked and received By<br><br>(Production)<br>Sign :_________________<br>Date :_________________</td>
                        	</tr>
                        </table>
                        *Note: Applicable in case of Blend Manufactured at site used for filling.
                        <br pagebreak="true"/>
                        <h3 align="center">TABLE NO.: 05  WEIGHT OF STERILE BULK</h3>
                        <table cellpadding="5">
                            <tr>
                                <td style="border:none;">
                                    Start Time  : _________________________<br><br>
                                    <table cellpadding="3">
                                        <tr>
                                            <td rowspan="2">Sr. No.</td>
                                            <td rowspan="2">Container No.</td>
                                            <td colspan="3">Wt. Of Material</td>
                                        </tr>
                                        <tr>
                                            <td>Gross Wt. (in kg)</td>
                                            <td>Tare Wt. (in kg)</td>
                                            <td>Net Wt. (in kg)</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border:none;">
                                    End Time : ______________________________<br><br>
                                    <table cellpadding="3">
                                        <tr>
                                            <td rowspan="2">Sr. No.</td>
                                            <td rowspan="2">Container No.</td>
                                            <td colspan="3">Wt. Of Material</td>
                                        </tr>
                                        <tr>
                                            <td>Gross Wt. (in kg)</td>
                                            <td>Tare Wt. (in kg)</td>
                                            <td>Net Wt. (in kg)</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table cellpadding="3" class="tablenone">
                            <tr>
                                <td>Material Issued By<br>(Stores)<br><br>Sign :_________________<br>Date :_________________</td>
                                <td>Material checked By<br>(QA)<br><br>Sign :_________________<br>Date :_________________</td>
                                <td>Material checked and received By<br>(Production)<br><br>Sign :_________________<br>Date :_________________</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h3 align="center">SECTION 04: DISPENSING OF PRIMARY PACKING MATERIAL SOP –STR/006</h3>
                        Area cleaned as per SOP NO. STR/008. Previous product: ________________________Batch No.:______________<br>
                        Area cleanliness checked by Stores (Sign & Date):_________________ Production (Sign & Date): ______________ <br>        
                        <h3 align="center">TABLE NO.: 06 LINE CLEARANCE CHECKLIST SOP-QAD/008</h3>
                        <table cellpadding="3">
                            <tr>
                                <td style="width:10%">Sr. No.</td>
                                <td style="width:70%">Check point</td>
                                <td style="width:10%">Stores</td>
                                <td style="width:10%">QA</td>
                            </tr>
                            <tr>
                                <td>1.</td>
                                <td>Ensure that previous products are removed.</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>2.</td>
                                <td>Visually check the area for its cleanliness.</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>3.</td>
                                <td>Ensure material having QC approved label before dispensing.</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2">Line clearance given by (Sign & Date)</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                        Note: OK - If found satisfactory, NOT OK - If not satisfactory.
                        
                        <h3 align="center">TABLE NO.: 07  BILL OF MATERIAL (PRIMARY PACKING MATERIAL)</h3>
                        <table cellpadding="3">
                            <tr>
                                <td>Item Code</td>
                                <td>Item Name</td>
                                <td>Unit</td>
                                <td>Over age (In %)</td>
                                <td>Std. Qty. For 10000 Vials</td>
                                <td>Req. Qty. In Nos.</td>
                                <td>A.R No.</td>
                                <td>Issued Qty. In Nos.</td>
                            </tr>
                        </table>
                                <table cellpadding="3">
                                    <tr align="center">
                                        <td style="border:none;">Material Issued By<br>(Stores)<br>Sign :_________________<br>Date :_________________</td>
                                        <td style="border:none;">Material checked By<br>(QA)<br>Sign:_______________<br>Date:_______________</td>
                                        <td style="border:none;">Material checked and received By<br>(Production)<br>Sign:_________________<br>Date:_________________</td>
                                    </tr>
                                </table>';
                                EOD;
                                $pdf->writeHTML($html, true, false, false, false, '');
                                $pdf->Output('BMR.pdf', 'I');
                            }
                        }
                    }else{
                        $sql = "SELECT b.*, p.product_name, p.generic_name, p.dosage_form, p.label_claim FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.id='".$_GET['id']."'";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                            $_GET['pdffonts'] = '11'; 
                            $_GET['pdftype'] = 'onlyheader'; 
                            include("../pdfimp2.php");
                            $sql1 = "SELECT * FROM instructions WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND status='approve'";
                            $result1 = $conn->query($sql1);
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                                    $row1["instructions"] = json_decode($row1["instructions"]);
                                    $row["instructions"] = $row1["instructions"];
                                }
                            }else {
                        $output1 = array();
                        $row["instructions"] = $output1;
                    }
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM unit_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.mfr_no=(SELECT id FROM unitformula WHERE mfr_no='".$row["mfr_no"]."')";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["unit_materials"] = $output1;
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM batch_materials b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["batch_materials"] = $output1;
                    $output1 = array();
                    $sql1 = "SELECT * FROM stages WHERE user_no='".$_GET["user_no"]."' AND product_code='".$row["product_code"]."' AND status='approve'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["details"] = json_decode($row1["details"]);
                            $row1["instructions"] = json_decode($row1["instructions"]);
                            $row1["procedures"] = json_decode($row1["procedures"]);
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $row1["clearances"] = json_decode($row1["clearances"]);
                            if ($row1["istest"] == "true") {
                                $output2 = array();
                                $sql2 = "SELECT * FROM spec_tests WHERE specification_no = (SELECT specification_no FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row1["stage"]."' ORDER BY id DESC LIMIT 1)";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["spec_tests"] = $output2;
                            }
                            if ($row1["isinprocess"] == "true") {
                                $output2 = array();
                                $sql2 = "SELECT * FROM spec_tests WHERE specification_no = (SELECT specification_no FROM specification WHERE user_no='".$_GET["user_no"]."' AND spec_type='Inprocess Specification' AND product_code='".$row["product_code"]."' AND stage='".$row1["stage"]."' ORDER BY id DESC LIMIT 1)";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }
                                $row1["spec_tests"] = $output2;
                            }
                            $output1[] = $row1;
                        }
                    }
                    $row["stages"] = $output1;
                    $html='
                    <style>table tr td { border:solid 1px BCBBBA; }</style>
                    <table cellpadding="3">
                    <tr>
                        <td align="center">BMR FORMAT APPROVED BY</td>
                    </tr>
                    <tr>
                        <td style="width:20%;">Particulars</td>
                        <td style="width:40%;">Production</td>
                        <td style="width:40%;">Quality Assurance</td>
                    </tr>
                    <tr>
                        <td>Name</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Designation</td>
                        <td>Manager - Production</td>
                        <td>Manager - Quality Assurance</td>
                    </tr>
                    <tr>
                        <td>Sign/date</td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <div>
                    <p>Manufacturing Commencement Date: (dd/mm/yy)	: _____________________</p>
                    <p>Manufacturing Completion Date: (dd/mm/yy)	: ____________________</p>
                    <p><b>Shelf Life:</b></p>
                </div>
                <h4>BATCH  DETAILS :</h4>
                <table cellpadding="3">
                    <tr>
                        <td style="width:50%;border:none;"><b>BATCH NO. : </b></td>
                        <td style="width:50%;border:none;"><b>BATCH SIZE : '.$row['batch_size'].'</b></td>
                    </tr>
                    <tr>
                        <td style="border:none;"><b>MFG.  DATE :</b></td>
                        <td style="border:none;"><b>EXP. DATE :</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="3">
                    <tr>
                        <td align="center"><b>BMR ISSUANCE</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;"><b>BMR issued by</b></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="width:50%;"><b>Sign /Date</b></td>
                        <td></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="3">
                    <tr>
                        <td><b>Reason for Change</b></td>
                        <td><b>New BMR</b></td>
                    </tr>
                </table>
                <h3 align="center">Dispensing and Manufacturing Commencement Details</h3>
                <table cellpadding="3">
                    <tr style="text-align:center; font-weight:bold;">
                        <td>Operations</td>
                        <td>Date of Commencement</td>
                        <td>Date of completion</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="3">
                    <tr>
                        <td><b>BMR release and review status:</b></td>
                    </tr>
                    <tr>
                        <td align="center"><b>APPROVED</b></td>
                    </tr>
                    <tr>
                        <td align="center" style="width:33.33%"><b>Particulars</b></td>
                        <td align="center" style="width:33.33%"><b>Production</b></td>
                        <td align="center" style="width:33.33%"><b>Quality Control</b></td>
                    </tr>
                    <tr>
                        <td><b>Name</b></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Designation</b></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Sign and Date</b></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3"><b>BMR review by Quality Assurance</b></td>
                    </tr>
                    <tr>
                        <td><b>Particulars</b></td>
                        <td><b>Reviewed by</b></td>
                        <td><b>Checked by</b></td>
                    </tr>
                    <tr>
                        <td><b>Name</b></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Designation</b></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><b>Sign and Date</b></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <div>
                    <b>LABEL CLAIM:</b><br>
                    <p>Each ml contains:</p>
                    <p>Ferric hydroxide in complex with Sucrose equivalent to elemental Iron  …………. 20mg/ml.</p>
                    <p>Water for Injection USP ……. Q.S</p>
                </div>
                <h3>MANUFACTURING FORMULA:</h3>
                <table cellpadding="3">
                    <tr style="font-weight:bold;">
                        <td>Sr. no.</td>
                        <td>Materials</td>
                        <td>Material code</td>
                        <td>Contents mg/ml</td>
                        <td>Overages</td>
                        <td>Qty for 10 lts.</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6">* Calculated with respect to assay content.</td>
                    </tr>
                </table>
                <div></div>
                <table>
                    <tr>
                        <td>
                            <b>CALCULATION: Refer to the below calculation in case the Assay has been provided:</b><br>
                            If only one batch number of  Iron sucrose to be used, calculate the quantity as follow:<br>
                            A.R. number :              Assay of iron :<br>
                                                        2
                            Quantity required = ---------------- X 10 =<br>
                                                    Iron Assay<br>
                            <b>If two A. R. numbers of Iron sucrose are to be used, calculate the quantity as follow:</b><br>
                            A) First  A.R. number : ___________________               Quantity available (X) =  _______________ kg<br>
                            Assay of iron : ______________<br>
                        </td>
                    </tr>
                </table>
                Actual quantity after calculation of the Iron sucrose for per batch (A) ____________________  
                The quantity of Iron Sucrose required from the next batch is to be calculated as follows                                                 
                A (____________________kg) – X(___________________kg) = __________________kg (Y)
                
                              Assay of first batch no.
                Y (kg) X  …………………………........................  =   ________________ kg (Z) from next batch no.
                              Assay of second batch no.                                                           
                Total qty. of Iron Sucrose to be dispensed =                                                       
                Calculation done by:                                                                                      Checked by QA:  
                <br>
                <h3>Dispensing</h3>
                <table cellpadding="3">
                <tr>
                    <td><b>Previous Product:</b></td>
                    <td><b>Batch No.:</b></td>
                </tr>
                <tr>
                    <td><b>Date:</b></td>
                    <td><b>Time:</b></td>
                </tr>
                </table>
                <table cellpadding="3">
                <tr style="font-weight:bold;">
                    <td>Sr. No.</td>
                    <td>Area / Equipment</td>
                    <td>ID No.</td>
                    <td>Cleaning SOP No.</td>
                    <td>Cleaned By</td>
                    <td>Checked By (Store)</td>
                    <td>Verified By QA</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                <div></div>
                <table cellpadding="3">
                <tr style="font-weight:bold;">
                    <td>Environmental Conditions</td>
                    <td>Limit</td>
                    <td>Observations</td>
                    <td>Observed By sign./date</td>
                </tr>
                <tr>
                    <td>Temperature</td>
                    <td>23°C ± 2°C</td>
                    <td>°C</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Humidity</td>
                    <td>55% ± 5%</td>
                    <td>%</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"><b>Differential pressure of Filters</b></td>
                </tr>
                <tr>
                    <td>Pre-filter 10micron</td>
                    <td>1 to 5 MMWC</td>
                    <td>MMWC</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Fine filter 5micron</td>
                    <td>2 to 7 MMWC</td>
                    <td>MMWC</td>
                    <td></td>
                </tr>
                <tr>
                    <td>HEPA filter</td>
                    <td>8 to 16 MMWC</td>
                    <td>MMWC</td>
                    <td></td>
                </tr>
                </table>
                <h3>Take line clearance from QA as per checklist given for dispensing. (As Per SOP/QA/20)</h3>
                <table cellpadding="3">
                <tr style="font-weight:bold;">
                    <td>Sr. No.</td>
                    <td>Checklist</td>
                    <td>Done by Sign./date</td>
                    <td>Checked by Sign./date</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                <h3>Cleaning Verification and Line Clearance</h3>
                <table cellpadding="3">
                <tr>
                    <td rowspan="2"><b>Activity</b></td>
                    <td colspan="2"><b>Line Clearance</b></td>
                </tr>
                <tr>
                    <td><b>Done by Stores (Sign./Date)</b></td>
                    <td><b>Verified by QA  (Sign./Date)</b></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                <table cellpadding="3">
                <tr>
                    <td><b>Magnehelic Gauge Differential Pressure Reading</b></td>
                </tr>
                <tr style="font-weight:bold;">
                    <td style="width:25%">Area</td>
                    <td style="width:25%">Limit in pascal</td>
                    <td style="width:25%">At the beginning of  dispensing in pascal</td>
                    <td style="width:25%">At the end of the dispensing in pascal</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                     <td colspan="2"><b>Done by Stores:</b></td>
                     <td colspan="2"><b>Checked by QA:</b></td>
                 </tr>
                 <tr>
                     <td colspan="2"><b>(Sign./Date)</b></td>
                     <td colspan="2"><td>(Sign. /Date)</td></td>
                 </tr>
                </table>
                <div>
                PRECAUTION:<br>1. All the balances should be checked for zero error.
                </div>
                <h3 align="center">BMR Copy</h3>
                <h3 align="center">RAW MATERIAL ISSUANCE NOTE</h3>
                <table cellpadding="3">
                <tr style="font-weight:bold;">
                    <td rowspan="2">Sr. No.</td>
                    <td rowspan="2">Mat. Code</td>
                    <td rowspan="2">Ingredient</td>
                    <td rowspan="2">Std. Qty. Per Batch In Kgs</td>
                    <td rowspan="2">A.R. No.</td>
                    <td rowspan="2">Balance I.D. no.</td>
                    <td colspan="3">Qty Used/ Issued</td>
                    <td rowspan="2">Dispensed by</td>
                    <td rowspan="2">Checked by</td>
                </tr>
                <tr>
                    <td>Tare Wt. in Kgs</td>
                    <td>Net Wt. in Kgs</td>
                    <td>Gross Wt. in Kgs</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="width:50%">Stores:</td>
                    <td style="width:50%">Quality Assurance:</td>
                </tr>
                <tr>
                    <td>Sign./date:</td>
                    <td>Sign./date:</td>
                </tr>
                </table>
                Stage- Autoclave 
                Line Clearance of Autoclave for Sterilization of Garments and Components.(As per SOP/QA/20)
                
                <h3>List of equipments</h3>
                <table cellpadding="3">
                <tr>
                    <td rowspan="2">Sr. no.</td>
                    <td rowspan="2">Equipment name</td>
                    <td rowspan="2">Equipment I.D.</td>
                    <td rowspan="2">SOP No.</td>
                    <td rowspan="2">Cleaned By</td>
                    <td colspan="2">Checked by</td>
                </tr>
                <tr>
                    <td>PRD.</td>
                    <td>QA</td>
                </tr>
                <tr nobr="true">
                    <td>1.</td>
                    <td>Double door Autoclave</td>
                    <td>SBP/PR/AT/11</td>
                    <td>SOP/PR/10</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                Activity	Line Clearance
                Done by Production (Sign/Date)	Verified by QA (Sign/Date)
                Area and Equipment cleanliness.		
                Verification of updated logs.		
                
                <h3 align="center">Sterilization of Garments and Components</h3>
                <table cellpadding="3">
                <tr>
                    <td>Date</td>
                    <td>Cycle No.</td>
                    <td>Load Details</td>
                    <td>Cycle started at</td>
                    <td>Hold time start</td>
                    <td>Steam Pressure (psi)</td>
                    <td>Hold time end</td>
                    <td>Operated by</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4">Production Chemist:</td>
                    <td colspan="4">Checked by QA</td>
                </tr>
                <tr>
                    <td>Sign./Date:</td>
                    <td>Sign./Date:</td>
                </tr>
                </table>
                <h4>Note: Attach sterilization cycle print out and strip chart recorder printout to BMR</h4>
                <h3>Stage: - Manufacturing Operation</h3>
                <table>
                <tr>
                    <td>Previous Product:</td>
                    <td>Batch No.: </td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td>Time:</td>
                </tr>
                </table>
                <table>
                <tr style="font-weight:bold;">
                    <td>Sr. No.</td>
                    <td>Checklist</td>
                    <td>Observation</td>
                    <td>Checked By PRD</td>
                    <td>Verified By QA</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                Temperature		:  NMT 250C<br>
                Relative Humidity	:  55 ± 5 % ______ °C ______  %<br>		
                7.	Check and ensure that all employees of the respective area are in proper gowning.	Complies/      Doesn’t Comply<br>		
                8.	Swab/Rinse analysis report no. _____________	Complies/   Doesn’t Comply<br>	
                
                Line clearance
                Take line clearance for manufacturing as per below checklist (Ref. Sop no.-SOP/QA/20)
                Activity	Line Clearance
                Done by Production (Sign./Date)	Verified by QA (Sign./Date)
                Area and Equipment free from previous materials, product and records.		
                Verification of updated logs<br>		
                <table cellpadding="3">
                <tr>
                    <td colspan="8">List of Equipment and Instrument and Cleaning Status</td>
                </tr>
                <tr>
                    <td>Sr. no.</td>
                    <td>Equipment name</td>
                    <td>Equipment I.D.</td>
                    <td>SOP No.</td>
                    <td>Cleaned By</td>
                    <td>Checked by</td>
                    <td>Prod. Sign./Date</td>
                    <td>QA Sign./Date</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                Pressure Differential Reading
                Area	Limit in pascal	At the start of mfg. in pascal	At the End of mfg. in pascal	Checked by prd. Sign./date
                Air Lock -1	15 ± 5			
                Air Lock -2	7.5 ± 5			
                Air Lock -3	7.5 ± 5			
                Exit Air Lock	7.5 ± 5			
                Component preparation room	7.5 ± 5			
                Core manufacturing area	7.5 ± 5			
                Component washing	22.5 ±5			
                
                <h3>BMR Copy</h3>
                <h3>Water for Injection test request form and Analysis Status</h3>
                <table cellpadding="3">
                    <tr>
                        <td>Mfg. date	:</td>
                    </tr>
                    <tr>
                        <td>Exp. date	:</td>
                    </tr>
                    <tr>
                        <td>Stage of sample	:</td>
                    </tr>
                    <tr>
                        <td>Requested by	:	Sign./date</td>
                    </tr>
                    <tr>
                        <td>Quantity sampled	:</td>
                    </tr>
                    <tr>
                        <td>Sampled by	:	Sign./date</td>
                    </tr>
                    <tr>
                        <td>Sampling time	:</td>
                    </tr>
                    <tr>
                        <td>A.R. No.	:</td>
                    </tr>
                </table>
                <table cellpadding="3">
                    <tr>
                        <td>Test</td>
                        <td>Specification</td>
                        <td>Observations</td>
                    </tr>
                    <tr>
                        <td>Description</td>
                        <td>Clear, colorless and odorless liquid</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>pH</td>
                        <td>5.0 to 7.0</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Bacterial Endotoxin Test</td>
                        <td>Less than 0.25EU/ml</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="width:100%">Remarks: The Sample Complies / Does not Comply as per Specification</td>
                    </tr>
                    <tr>
                        <td style="width:50%">Analyzed  by /date:</td>
                        <td style="width:50%">Checked By/ Date :</td>
                    </tr>
                </table>
                BULK MANUFACTURING STAGE
                Equipment I.D. for Manufacturing: 
                Line clearance of Area and Equipment given by______________ Date: __________ Time: _________
                Processing steps	Time	Done By Sign/date	Checked By Sign/date
                From	To		
                Standardize and calibrate the pH meter.				
                			
                Close the vessel after blanketing the overhead space with nitrogen.
                Take the sample for bulk analysis and transfer to QC.
                Verified by (QA):
                Sign./date
                
                Volume	Specific Gravity 	Load cell reading (kg.)(Specific gravity X volume)
                _____________Lts.	  ____________Wt/ml	________________________Kgs.
                Silicone Tube Issuance and Destruction Record
                (Destroy silicone tubes after completion of each product campaign)
                Item name	 Size	Issuance	Destruction 
                	Qty. issued	Issued by/date	Checked by/date	Qty. destroyed	Destroyed by/date	Checked by/date
                Silicon tube							
                Silicon tube							
                Silicon tube							
                <div>
                <h3>BMR Copy</h3>
                <h3>Bulk test request form and Analysis Status</h3>
                </div>
                <table cellpadding="3">
                    <tr>
                        <td>Mfg. date	:</td>
                    </tr>
                    <tr>
                        <td>Exp. date	:</td>
                    </tr>
                    <tr>
                        <td>Stage of sample	:</td>
                    </tr>
                    <tr>
                        <td>Requested by	:	Sign./date</td>
                    </tr>
                    <tr>
                        <td>Quantity sampled	:</td>
                    </tr>
                    <tr>
                        <td>Sampled by	:	Sign./date</td>
                    </tr>
                    <tr>
                        <td>Sampling time	:</td>
                    </tr>
                    <tr>
                        <td>A.R. No.	:</td>
                    </tr>
                    </table>
                    <table cellpadding="3">
                    <tr>
                        <td style="width:33%">Test</td>
                        <td style="width:34%">Specification</td>
                        <td style="width:33%">Observations</td>
                    </tr>
                    <tr>
                        <td>Description</td>
                        <td>A dark brownish liquid.</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Alkalinity</td>
                        <td>Not less than 0.5ml and not more than 0.8ml of 0.1N HCL is consumed per ml of solution.</td>
                        <td></td>
                     </tr>
                     <tr>
                        <td style="width:100%;">Remarks: The Sample Complies / Does not Comply as per Specification.</td>
                     </tr>
                     <tr>
                        <td style="width:50%;">Analyzed  by /date:</td>
                        <td style="width:50%;">Checked By/ Date :</td>
                     </tr>
                </table>
                PRE-FILTRATION
                PREPARATION OF MEMBRANE FILTER ASSEMBLY FOR PRE-FILTRATION:
                Pre-filter the bulk solution with 2/2.0micron glass fibre pre-filter in pressure vessel.
                NOTE: Take batch for pre-filtration immediately after manufacturing.
                PROCEDURE:<br>
                1.	Remove membrane filter (2.0 µ) from packet and wet it in WFI.<br>
                2.	Place the 2.2 µ membrane filters on the mesh of assembly.<br>
                3.	Put the lid on filters and tie screws uniformly.<br>
                4.	Connect the silicon tubing’s.<br>
                5.	Use this assembly for pre-filtration of the batch.<br>
                Details of filter to be used for Pre-filtration:<br> 
                Make: ____________________<br>                           
                Lot number/Batch no.:  ___________________<br> 
                <table>
                <tr>
                    <td>Date</td>
                    <td>Pressure vessel I.D. No.</td>
                    <td>Membrane Holder I.D.</td>
                    <td>Filtration Time</td>
                    <td>Done By</td>
                    <td>Checked By</td>
                </tr>
                </table>
                FINAL FILTRATION
                NOTE: Take batch for final filtration not later than 24 hours from end of manufacturing
                PROCEDURE:<br>
                1.0	Remove capsule filter (0.2 µ) from packet and wet it in WFI.<br>
                2.0	Operate as per the instruction given on it.<br>
                3.0	Sterilize in double door Autoclave as per validated sterilization cycle.<br>
                4.0	Sterilize filling vessels also, to collect sterile product.<br>
                5.0	Assemble the capsule filtration assembly in the filtration room.<br>
                6.0	Check the integrity of the filter and filter the batch as per the SOP/PR/09.<br>
                7.0	Filtration by membrane filter<br>
                7.1	Remove membrane filter (2.0/2.2 µ and 0.2 µ) from packet and wet it in WFI.<br>
                7.2	Place the 0.2 µ membrane filters on the mesh of assembly.<br>
                7.3	Then put 2.0 µ filters over 0.2 µ filter.<br>
                7.4	Put the lid on filters and tie screws uniformly. Connect the silicon tubing’s.<br>
                7.5	Wrap the open ends of tubes with aluminum foil.<br>
                7.6	Sterilize in double door Autoclave as per validated sterilization cycle.<br>
                7.7	Sterilize filling vessels also, to collect sterile product.<br>
                7.8	Assemble the filtration assembly in the filtration room.<br>
                7.9	Check the integrity of the filter and filter the pre-filter batch as per the SOP/PR/09<br>
                Details of filter to be used for Final-filtration:  <br>
                Make: ___________________          <br>                  
                Lot no./Batch no. of 0.2µ filters used: ___________________, ___________________	<br>
                Make: ________________
                Lot no./Batch no. of 2.0/2.2µ Membrane filters used: ___________________, ___________________<br>
                Bubble point test results before and after filtration:<br>
                <table>
                    <tr>
                        <td>Date</td>
                        <td>Time</td>
                        <td>Test result</td>
                        <td>Done by Sign /date</td>
                        <td>Checked by Sign./date</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                BEFORE FILTRATION
                	Complies /does not comply		
                	Complies /does not comply		
                	Complies /does not comply		
                AFTER FILTRATION
                	Complies /does not comply		
                	Complies /does not comply		
                	Complies / does not comply		
                
                Details of Filtration:
                For capsule filtration:
                Date	Filling vessel I.D. No.	Capsule filter I.D.	Filtration Time	Done By	Checked By
                		From	To		
                SBP/PR/FV/					
                SBP/PR/FV/					
                SBP/PR/FV/					
                Date	Filling vessel I.D. No.	Membrane Holder I.D.	Filtration Time	Done By	Checked By
                		From	To		
                SBP/PR/FV/	SBP/PR/MF/				
                SBP/PR/FV/	SBP/PR/MF/				
                SBP/PR/FV/	SBP/PR/MF/				
                Note: Take batch for filtration immediately not later than 30 hours from end of manufacturing.
                1.	Filling to be started only after filter passes Bubble Point test.
                2.	In case of failure in post filtration bubble point test, carry out re-filtration, using fresh membrane filter assembly. Use additional copy of relevant pages for documenting re-filtration.
                BMR Copy
                Primary Packaging Material Issuance Note
                Item	Item Code	UOM	Std. Qty.	Medicap lot no	Issued
                Qty.	Issued By/Date	Received By/date
                5ml ampoules ___________________________________________________		Nos.					
                <h3>Primary Packaging Material Returned Note</h3>
                <table>
                <tr>
                    <td>Item</td>
                    <td>Item Code</td>
                    <td>UOM</td>
                    <td>Medicap lot no</td>
                    <td>Qty. Returned</td>
                    <td>Returned By/Date</td>
                    <td>Received By/date</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                <h3>Stage: - Ampoule Decartoning and Washing Operation.</h3>
                <h3>List of equipments</h3>
                <table>
                <tr>
                    <td rowspan="2">Sr. no.</td>
                    <td rowspan="2">Equipment name</td>
                    <td rowspan="2">Equipment I.D.</td>
                    <td rowspan="2">SOP No.</td>
                    <td rowspan="2">Cleaned By</td>
                    <td colspan="2">Checked by</td>
                </tr>
                <tr>
                    <td>PRD.</td>
                    <td>QA</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                <h3>Take line clearance for manufacturing as per below checklist (Ref. Sop no.-SOP/QA/20)</h3>
                <table>
                <tr>
                    <td>Previous Product:</td>
                    <td>Batch No.: 	</td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td>Time:</td>
                </tr>
                <table>
                <table>
                    <tr>
                        <td style="width:10%;">Sr. no.</td>
                        <td style="width:30%;">Checklist</td>
                        <td style="width:20%;">Observations</td>
                        <td style="width:20%;">Checked by (Prd.)</td>
                        <td style="width:20%;">Verified by (QA)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td rowspan="2">Activity</td>
                        <td colspan="2">Line Clearance</td>
                    </tr>
                    <tr>
                        <td>Done by Production (Sign/Date)</td>
                        <td>Verified by QA (Sign/Date)</td>
                    </tr>
                    <tr>
                        <td>Area and Equipment free from previous materials, product and records.</td>
                        <td>Verification of updated logs</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="3">
                    <tr>
                        <td>Decartoning</td>
                    </tr>
                    <tr>
                        <td>Decartoning the ampoules from cardboard boxes and load to hopper of through the decartoning area ampoule washing machine as per 
                            SOP No.: SOP/PR/14
                        </td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>Shift</td>
                        <td>Decartoning done by</td>
                        <td>Checked by Prd. (Sign./Date)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                Ampoule Washing and Sterilization<br>
                No. of ampoules issued for washing: ___________________ no’s.<br>
                Carry out washing of ampoules (with Recycled water, Purified Water, Water for Injection and Compressed air) using Automatic Ampoule Washing Machine. The washed ampoules are then subjected to depyrogenation/sterilization tunnel as per validated cycle. Record washing machine parameters every one hour in the following table.<br>
                Type of Ampoules (____________________________________________)	Complies / Does not Comply<br>
                Washing / sterilization/depyrogenation date :  ____________          Washing started at:   ___________ <br>
                Water jets checked by: (prior to washing)  __________Washing &sterilization carried out by _______<br>
                Date & Time	Recycled water jet (1.5– 2.5 kg/cm2)	Fresh purified water pulse  (1.5 -2.5kg/cm2)	Compressed air pressure        (2.0-3.0kg/cm2)	Fresh WFI pulse            (1.5-2.5 kg/cm2)	Checked by Prd.<br>
                Washing Completed at: Date   _______________, Time: _____________<br>
                Washing rejections: __________________nos.<br>
                Depyrogenation /Sterilization Tunnel record<br>
                Tunnel Heaters Put ON at ________hours and wait till it attains the required temperature as per validated cycle.<br> 
                Tunnel Temperature 3050C attained Date________   & Time _________.  Temperature  _________<br>
                Tunnel Heaters OFF at ________ hours. Tunnel stopped at______________ hours.<br> 
                Record the parameters in the below table at every one hour.<br>
                Date /Time	Tunnel pressure (mm of water)	Tunnel temp.     (limit: 305 to 3350C)	Conveyor speed (mm/min)	Checked by Prd.<br>
                Drying zone
                (7-15mm WC)	Hot zone
                (18-24mm WC)	Cooling zone
                (7-15 mm WC)			
                Entry temp.	Exit temp.		
                Stage: - Ampoule Filling and Sealing Operation
                List of equipment
                <table>
                <tr>
                    <td rowspan="2">Sr. no.</td>
                    <td rowspan="2">Equipment name</td>
                    <td rowspan="2">Equipment I.D.</td>
                    <td rowspan="2">SOP No.</td>
                    <td rowspan="2">Cleaned By</td>
                    <td colspan="2">Checked by</td>
                </tr>
                <tr>
                    <td>PRD.</td>
                    <td>QA</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>			
                <h3>Checklist given for line clearance of ampoule filling and sealing.</h3>
                <table cellpadding="3">
                <tr>
                    <td>Previous Product:</td>
                    <td>Batch No.:</td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td.Time:</td>
                </tr>
                </table>
                <table cellpadding="3">
                <tr>
                    <td>Sr. no.</td>
                    <td>Checklist</td>
                    <td>Observation</td>
                    <td>Checked. By (Prod.)</td>
                    <td>Verified By (QA)</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                <h3>Line Clearance ForAmpoule Filling and Sealing (as per SOP/QA/20)</h3>
                <table>
                    <tr>
                        <td rowspan="2">Activity</td>
                        <td colspan="2">Line Clearance</td>
                    </tr>
                    <tr>
                        <td>Done by Production (Sign/Date)</td>
                        <td>Verified by QA (Sign/Date)</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <h3>No. of ampoules given for filling: _____________________nos.</h3>
                <table>
                    <tr>
                        <td>Magnehelic Gauge Differential Pressure Reading</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Area</td>
                        <td style="width:25%;">At the beginning of filling pascal</td>
                        <td style="width:25%;">At the end of filling pascal</td>
                        <td style="width:25%;">Limit in pascal</td>
                    </tr>
                    <tr>
                        <td>Observed by Prd. Sign./date</td>
                        <td></td>
                        <td></td>
                        <td rowspan="2"></td>
                    </tr>
                    <tr>
                        <td>Checked by QA sign./date</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Environment condition</td>
                        <td>Start  of filling</td>
                        <td>Middle of filling</td>
                        <td>End of filling</td>
                    </tr>
                    <tr>
                        <td>Temperature in 0C</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Relative humidity %</td>
                        <td></td>
                        <td></td>
                        <td></td>
                     </tr>
                     <tr>
                         <td>Prd. Chemist sign./date:</td>
                         <td></td>
                         <td></td>
                         <td></td>
                     </tr>
                </table>		
                <h3>Ampoule Filling and sealing</h3>
                <table>
                    <tr>
                        <td>Machine ID No.</td>
                        <td>Filling started at</td>
                        <td>Filling completed at</td>
                        <td>Duration of filling</td>
                    </tr>
                    <tr>
                        <td colspan="2">Production Chemist:</td>
                        <td colspan="2">Filling done by:</td>
                    </tr>
                </table>
                Parameter checking:
                Parameter	Oxygen  pressure	LPG pressure	Nitrogen Pressure	Done by
                PRD.         Sign.	Checked by QA Sign.
                Limits		
                Date/Time	Intervals	0.3 to 2 kg/cm²	0.3 to 1 kg/cm²	NLT 0.3  kg/cm²		
                Initial					
                Middle					
                End				
                <div>
                In Process Checks during Ampoules Filling & Sealing
                <p>Check:  Fill volume (limit: 5.0ml to 5.75ml) at every 30 minutes. Target volume - 5.2 ml</p>
                </div>
                <table cellpadding="3">
                <tr>
                    <td style="width:12%" rowspan="2">Date/Time</td>
                    <td style="width:40%" colspan="8">Volume at Each station(ml)</td>
                    <td style="width:12%" rowspan="2">itrogen Purging Before filling (ok/not ok)</td>
                    <td style="width:12%" rowspan="2">Nitrogen Purging After filling (ok/not ok)</td>	
                    <td style="width:12%" rowspan="2">Done By</td>
                    <td style="width:12%" rowspan="2">Ckd.  By</td>
                </tr>
                <tr>
                    <td style="width:5%">1</td>
                    <td style="width:5%">2</td>
                    <td style="width:5%">3</td>
                    <td style="width:5%">4</td>
                    <td style="width:5%">5</td>
                    <td style="width:5%">6</td>
                    <td style="width:5%">7</td>
                    <td style="width:5%">8</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Ok / Not ok</td>
                    <td>Ok / Not ok</td>
                    <td></td>
                    <td></td>
                </tr>
                </table>
                In Process Checks: Record Ampoules Sealing Height at Every one hour
                Date/Time	Sealing Height (Limit 76 - 78mm)	Done By	Ckd. by
                1	2	3	4	5	6	7	8		
                							
                Number of ampoules filled and sealed: ________________<br>
                
                Rejections during filling and sealing: _________________<br>
                
                Samples taken during filling and sealing: ______________<br>
                
                Stage - Autoclave Operation for ampoule leak test of filled and sealed ampoules.<br>
                
                Checklist for Line Clearance For Autoclave (Line clearance as per SOP/QA/20)<br>
                <table>
                    <tr>
                        <td>Sr. no.</td>
                        <td>Equipment name</td>
                        <td>Equipment I.D.</td>
                        <td>SOP No.</td>
                        <td>Cleaned By</td>
                        <td>Checked by</td>
                        <td>PRD.</td>
                        <td>QA</td>
                    </tr>
                    <tr>
                        <td>1.</td>
                        <td>Double Door Autoclave</td>
                        <td>SBP/PR/AT/11</td>
                        <td>SOP/PR/10</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                Previous Product:  Batch No.:<br>
                Date:		       Time:<br>
                <table>
                    <tr>
                        <td>Sr. no.</td>
                        <td>Checklist</td>
                        <td>Observation</td>
                        <td>Checked By (Prod.)</td>
                        <td>Verified By (QA)</td>
                    </tr>
                    <tr>
                        <td>1.</td>
                        <td>Check and ensure proper working of  Air locking System, Pressure Differentials, HVAC system</td>
                        <td>Satisfactory /    Not satisfactory</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>Check and ensure that all employees of the respective area are in proper gowning</td>
                        <td>Satisfactory /   Not satisfactory</td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                Activity	Line Clearance
                Done by Production
                (Sign/Date)	Verified by QA
                (Sign/Date)
                Area and Equipment free from previous materials, product and records.		
                Verification of updated logs.		
                Leak Test of Filled and Sealed Ampoules
                Date	Cycle no.	Time	Hold time	Vacuum
                Production Chemist:                                                         Checked by QA:
                Sign./Date:                                                                          Sign./Date:
                <h3>BATCH RECONCILIATION</h3>
                <table cellpadding="3">
                    <tr>
                        <td style="width:10%">Sr. no.</td>
                        <td style="width:40%">Particulars</td>
                        <td style="width:25%">Volume</td>
                        <td style="width:25%">Number of Ampoules</td>
                    </tr>
                    <tr>
                        <td>1.</td>
                        <td>Batch Size</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>Quantity Manufactured</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>3.</td>
                        <td>Sample taken during Manufacturing</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>4.</td>
                        <td>Rejections during Manufacturing</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>5.</td>
                        <td>Sample taken during filtration</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>6.</td>
                        <td>Total (3+4+5) - 2</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>7.</td>
                        <td>Quantity Filled</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>8.</td>
                        <td>Rejections during Filling</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>9.</td>
                        <td>Samples taken during Filling</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>10.</td>
                        <td>Sample taken after leak test</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>11.</td>
                        <td>Total (8+9+10)</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>12.</td>
                        <td>Total Qty.mfg. (6+11)</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>13.</td>
                        <td>Manufacturing yield (12/1x100)</td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                Calculation done by PRD.:              			             Checked by QA:
                Date/sign:							  Date/sign:
                DOCUMENT REVIEW:
                Enclosures	Attached
                Yes / No	Production (Sign)	QA            (Sign)
                1. 	Release Slips of QC Water For Injections.			
                2.  	Issue Slips of dispensed material.			
                3.	Ampoule sterilization/Depyrogenation Print out.			
                4.	Garment, Machine parts & Accessories sterilization print out.			
                5.    Print out of ampoules leak test.			
                6.     Deviation: Yes / No			
                Remarks (if any) :
                COMPLETED BMR CHECKED BY  :
                PRODUCTION 
                (Signature & Date) 	QUALITY ASSURANCE 
                (Signature & Date)
                ';
                EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('BMR.pdf', 'I');
                }
            }
        }
    }
    else if ($_GET["type"] == "downloadCompletedBMR") {
        $_GET['filename'] = 'Completed BMR '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
        <h2 style="text-align:center">Completed BMR</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:center;">Sr.</td>
                    <td style="width: 10%; text-align:center;">BMR No</td>
                    <td style="width: 10%; text-align:center;">Product Code</td>
                    <td style="width: 10%; text-align:center;">Product Name</td>
                    <td style="width: 10%; text-align:center;">Batch No</td>
                    <td style="width: 10%; text-align:center;">Batch Size</td>
                    <td style="width: 10%; text-align:center;">Yield Qty</td>
                    <td style="width: 12%; text-align:center;">Prepared By</td>
                    <td style="width: 12%; text-align:center;">Started Date</td>
                    <td style="width: 11%; text-align:center;">Completed Date</td>
                </tr>
            </thead>';
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim ,u.instructions ,u.abbreviation,u.raw_materials ,u.packing_materials ,u.bmr_checklist FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula u ON b.mfr_no = u.mfr_no WHERE b.status='COMPLETED'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width: 5%; text-align:center;">'.$i.'.</td>
                            <td style="width: 10%; text-align:center;">'.$row['bmr_no'].'</td>
                            <td style="width: 10%; text-align:center;">'.$row['product_code'].'</td>
                            <td style="width: 10%; text-align:center;">'.$row['product_name'].'</td>
                            <td style="width: 10%; text-align:center;">'.$row['batch_no'].'</td>
                            <td style="width: 10%; text-align:center;">'.$row['batch_size'].'</td>
                            <td style="width: 10%; text-align:center;">'.$row['yield_qty'].'</td>
                            <td style="width: 12%; text-align:center;">'.$row['entry_by'].'</td>
                            <td style="width: 12%; text-align:center;">'.date('d-m-Y',strtotime($row['start_date'])).'</td>
                            <td style="width: 11%; text-align:center;">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                        </tr>';
                        $i++;
                 
            }
        }
        $html.="</table>";
    
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Completed BMR.pdf', 'I');
    } 
    else if ($_GET["type"] == "fgcoa") {
        $_GET['filename'] = 'Completed BMR '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        
        
            $sql ="SELECT  
          p.dosage_form,
          a.*,
          b.plan_no,
          b.bfr_no,
          b.mfr_no,
         b.product_code,
         b.batch_size, 
         p.product_name,
         p.product_type,
         b.pack_size,
         b.pack_unit 
         FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
        b.plant_id = p.plant_id left join unitformula uf on uf.mfr_no= b.mfr_no and uf.plant_id= b.plant_id where  a.plant_id ='".$_GET["plant_id"]."' and 
        a.bmr_status='start' and a.raw_qc_intimation='Approve' and rm_qa_dislc_status='Approved' AND a.id= '".$_GET["id"]."' ";
           
        
        $result = $conn->query($sql);
        
         $html= "";
             $html.= '
                    <table>
                        <tr>
                            <td style="font-size:15px;width: 540px;font-weight: bold;text-align:center;">Certificate Of Analysis</td>
                        </tr>
                    </table>
                    <div></div>
                    <table >';
         
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            
                    
                      $html.= '
                    <tr>
                        <td style="width: 100px;text-align:left;"> Product Name  </td>
                        <td style="width: 35px;text-align:center;"> : </td>
                        <td style="width: 135px;text-align:left;">  '.$row['product_name'].'</td>
                    
                        <td style="width: 100px;text-align:left;"> Generic name </td>
                          <td style="width: 35px;text-align:center;"> : </td>
                        <td style="width: 135px;text-align:left;">  '.$row['dosage_form'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 100px;text-align:left;"> Batch no</td>
                        <td style="width: 35px;text-align:center;"> : </td>
                        <td style="width: 135px;text-align:left;">  '.$row['batch_number'].'</td>
                        
                        <td style="width: 100px;text-align:left;"> Product code  </td>
                        <td style="width: 35px;text-align:center;"> : </td>
                        <td style="width: 135px;text-align:left;">  '.$row['product_code'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 100px;text-align:left;"> Mfg. Date</td>
                        <td style="width: 35px;text-align:center;"> : </td>
                        <td style="width: 135px;text-align:left;">  '.date('d-m-Y',strtotime($row['mfg_date'])).'</td>
                        
                        <td style="width: 100px;text-align:left;"> Exp.Date</td>
                        <td style="width: 35px;text-align:center;"> : </td>
                        <td style="width: 135px;text-align:left;">  '.date('d-m-Y',strtotime($row['exp_date'])).'</td>
                    </tr>
                </table>
                <div></div>
                ';
                
                
                  $html.= '
                <table border="1">
                <tr>
                    <td style="line-height:20px;width: 40px;border-bottom:none;text-align:center;font-weight: bold;">  Sr.No</td>
                    <td style="line-height:20px;width: 150px;border-bottom:none;text-align:center;font-weight: bold;"> Parameter</td>
                    <td style="line-height:20px;width: 150px;border-bottom:none;text-align:center;font-weight: bold;"> Specifications</td>
                    <td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;font-weight: bold;"> Observations</td>
                    <td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;font-weight: bold;"> Test Method</td>
                </tr>';
                
                
                      $sql1 ="SELECT * FROM coa_checklist where work_order_id =  '".$row["id"]."' ";
                   $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $i = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                
                $html.= '
                <tr>
                    <td style="line-height:20px;width: 40px;border-bottom:none;text-align:center;">'.$i.' </td>
                    <td style="line-height:20px;width: 150px;border-bottom:none;text-align:center;"> '.$row1['parameter'].'</td>
                    <td style="line-height:20px;width: 150px;border-bottom:none;text-align:center;"> '.$row1['specifications'].' </td>
                    <td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;"> '.$row1['observation'].'</td>
                    <td style="line-height:20px;width: 100px;border-bottom:none;text-align:center;"> '.$row1['test_method'].' </td>
                </tr> ';
                
                $i++;
                 
                         }
                    }
       
      
        
               
                  $html.= '
               </table>
                <div></div>
                <table>
                    <tr>
                        <td style="line-height:20px;width: 200px;border-bottom:none;text-align:left;font-weight: bold;"> NMT-Not more than </td>
                        <td style="line-height:20px;width: 200px;border-bottom:none;text-align:left;font-weight: bold;"> NLT-Not less than </td>
                        <td style="line-height:20px;width: 200px;border-bottom:none;text-align:left;font-weight: bold;"> cfu-colony forming unit </td>
                    </tr>
                    <div></div>
                    <tr>
                        <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;"> For Saipro Industries Pvt.Ltd. </td>
                    </tr>
                    <div></div>
                     <tr>
                        <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;"> '.$row['qa_person'].' </td>
                        <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;">   </td>
                    </tr>
                     <tr>
                        <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;"> QC Checked By </td>
                        <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;"> Authorized Signatory </td>
                    </tr>
                </table>
                    ';
            }
        } 
                
    
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Completed BMR.pdf', 'I');
    } 
    
    
    else if ($_GET["type"] == "downloadYieldStatement") {
        $_GET['filename'] = 'Completed BMR '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Completed BMR</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:center;">Sr.</td>
                    <td style="width: 10%; text-align:center;">Product Code</td>
                    <td style="width: 15%; text-align:center;">Product Name</td>
                    <td style="width: 10%; text-align:center;">Grade</td>
                    <td style="width: 10%; text-align:center;">Batch No</td>
                    <td style="width: 10%; text-align:center;">Start Date</td>
                    <td style="width: 10%; text-align:center;">Completion Date</td>
                    <td style="width: 10%; text-align:center;">Batch Size</td>
                    <td style="width: 10%; text-align:center;">Yield Qty</td>
                    <td style="width: 10%; text-align:center;">Yield %</td>
                </tr>
            </thead>';
        $output = array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, DATE(b.complete_date) as complete_date, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='complete' AND b.user_no='".$_GET['user_no']."' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.product_code LIKE '%".$_GET["product_code"]."' AND DATE(b.complete_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='
            <tr nobr="true">
                <td style="width: 5%; text-align:center;">'.$i.'.</td>
                <td style="width: 10%; text-align:center;">'.$row['product_code'].'</td>
                <td style="width: 15%; text-align:center;">'.$row['product_name'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['grade'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['batch_no'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['start_date'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['comletion_date'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['batch_size'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['yeild_qty'].'</td>
                <td style="width: 10%; text-align:center;">'.$row['yeild'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Completed BMR.pdf', 'I');
        }
    } else if ($_GET["type"] == "completedBMRPDF") {
        $_GET['filename'] = 'BMR'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $output = array();
        $sql = "SELECT b.*, DATE(b.start_date) as start_date, DATE(b.complete_date) as complete_date,p.pack_desc,p.storage_condition,p.apperance, p.product_name, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.id='".$_GET['id']."' ";
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
            $sql1 = "SELECT *, TIME(start_date) as start_time, TIME(complete_date) as complete_time FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["details"] = json_decode($row1["details"]);
                    $row1["instructions"] = json_decode($row1["instructions"]);
                        $row1["clearances"] = json_decode($row1["clearances"]);
                        
                        if ($row1["isclearance"] == "inprocess") {
                            $sql2 = "SELECT * FROM lineclearance WHERE status='active' AND id='".$row1["clearance_no"]."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $row1["isclearance"] = "active";
                                    $row1["clearances"] = json_decode($row2["checkpoints"]);
                                    $row1["clearance_request_by"] = $row2["request_by"];
                                    $row1["clearance_request_date"] = $row2["request_date"];
                                    $row1["clearance_by"] = $row2["entry_by"];
                                    $row1["clearance_date"] = $row2["entry_date"];
                                }
                            }
                        }
                    $output1[] = $row1;
                }
            }
            $row["stages"] = $output1;
            $output[] = $row;
        
        $html.='
        <h2 style="text-align:center">BMR</h2>
        <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style="width:30%;"><b>Label Claim:</b></td>
                    <td style="width:70%;"><b>Each ml contains:</b>'.$row['label_claim'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Primary pack description:</b></td>
                    <td style="width:70%;">'.$row['pack_desc'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Storage Condition:</b></td>
                    <td style="width:70%;">'.$row['storage_condition'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Product Appearance:</b></td>
                    <td style="width:70%;">'.$row['apperance'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Effective Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Issued By (QA) Sign/Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Received By (Production) Sign/Date:</b></td>
                    <td style="width:70%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Batch Commencement Date:</b></td>
                    <td style="width:70%;">'.$row['start_date'].'</td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Batch Completion Date:</b></td>
                    <td style="width:70%;">'.$row['complete_date'].'</td>
                </tr>
                ';
        $html.='</table><div></div>';
        
        $html.='<table border="1" cellpadding="5" style=" font-size:12px;">
                <tr>
                    <td rowspan="2" style="width:20%;"><b></b></td>
                    <td style="width:15%;"><b>Prepared By</b></td>
                    <td style="width:15%;"><b>Checked By</b></td>
                    <td colspan="2" style="width:30%; text-align:center;"><b>Reviewed By</b></td>
                    <td style="width:20%;"><b>Approved By</b></td>
                </tr>
                <tr>
                    <td style="width:15%;"><b>QA</b></td>
                    <td style="width:15%;"><b>Production</b></td>
                    <td style="width:15%;"><b>Production</b></td>
                    <td style="width:15%;"><b>QA</b></td>
                    <td style="width:20%;"><b>Head QA</b></td>
                </tr>
                <tr >
                    <td style="width:20%;"><b>Name</b></td>
                    <td style="width:15%;">'.$row['entry_by'].'</td>
                    <td style="width:15%;">'.$row['check_by'].'</td>
                    <td style="width:15%;">'.$row['complete_by'].'</td>
                    <td style="width:15%;"></td>
                    <td style="width:20%;">'.$row['approve_by'].'</td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Sign & Date</b></td>
                    <td style="width:15%;">'.$row['entry_date'].'</td>
                    <td style="width:15%;">'.$row['check_date'].'</td>
                    <td style="width:15%;">'.$row['complete_date'].'</td>
                    <td style="width:15%;"></td>
                    <td style="width:20%;">'.$row['approve_date'].'</td>
                </tr>';
        $html.='</table><div></div>
                <br pagebreak="true"/>';
                
        
        $html.='<h3>STAGE 1.0 GENERAL INSTRUCTION</h3>
                <ul type="square" style="font-size:12px;">
                    <li>Do not alter or over write letters and numbers.</li>
                    <li>All the entries should be correct and legible.</li>
                    <li>Do not use staples/paper clip in packing material.</li>
                    <li>Follow GDP practices in case of wrong entry, cut single line on entry error and write the correct data. Write the entry error remark along with signature and date.</li>
                    <li>“Checked by” or “Reviewed by” cannot be signed prior to the “Done by/performed by”.</li>
                    <li>Check the availability of packing materials of specified batch before packing process.</li>
                    <li>Before starting the packing activity check the cleanliness of areas and equipment’s as per the current version SOP’s practices.</li>
                    <li>Line clearance shall be performed by QA before operation of the each & every stage as mentioned in BPR.</li>
                    <li>Follow Good Documentation Practice (GDP) during execution of BPR</li>
                    <li>Do not keep blank page, Strike the blank space & Put “NA” acknowledge with signature /Date. </li>
                    <li>Record all data by using blue ball pen for production person & green ball pen for IPQA person.</li>
                    <li>Record time as HH:MM format or HH:MM:SS in 24 hours format.</li>
                    <li>Record Date in DD/MM/YYYY or DD/MM/YY or DD-MM-YYYY or DD-MM-YY format. Do not leave any column in document unfilled. If any column in a document is not applicable, write ‘Not Applicable’ (NA) along with sign & date. If any column used for recording quantity write the number, if the quantity is zero then write the number “00”.</li>
                    <li>Encircle the correct choice, if choice is given.</li>
                    <li>Personnel signing the document shall put the ‘Date’ along with the signature and remark for better clarity. </li>
                    <li>Record discrepancies and deviation in defined summary place.</li>
                    <li>All operation must be performed in accordance with current Good Manufacturing Practices.</li>
                    <li>Any deviation observed during batch processing should be informed to Production Head, QA Head and duly recorded.</li>
                    <li>Machine breakdown pertaining to packing equipment’s during processing assessed for its impact on product quality by Production Head & to be logged under deviation, if required.</li>
                    <li>Record the details of following activities along with the date & time in BPR.<br> &nbsp;&nbsp;a) Trial taken b) Unusual observation c) If any correction done.</li>
                    <li>Quality Control Department must approve all packing materials before dispensing.</li>
                    <li>Equipment’s to be suitably labeled indicating the current status with date.</li>
                    <li>In- process control must be strictly followed and ensured the data must be recorded at regular interval in the batch packing record.</li>
                    <li>All entries should be legible, correct and signatures are with their corresponding dates.</li>
                    <li>After Completion of BMR, Reviewed by the concerned HOD & Submitted to QA.</li>
                   
                </ul>
                <div></div>';
                
        $html.='<h3>STAGE: 2.0 DISPENSING OF PACKING MATERIAL</h3>
                <span style="font-size:12px;"> &nbsp;&nbsp;2.1 Take line clearance of packing material dispensing area as per SOP No. BPL/GEN/QAI/004.</span><br>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;"><b>Table Number : 2.1</b></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td rowspan="2" style="width:50%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Date:</b></td>
                    <td style="width:20%;"><b>Time:</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">01</td>
                    <td style="width:50%;">Previous Product Name</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">02</td>
                    <td style="width:50%;">Previous Product Batch .No.</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">03</td>
                    <td style="width:50%;">Record the temperature of dispensing areaTemperature (NMT 27°C)  </td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%"><b>Sr. No.</b></td>
                    <td style="width:50%"><b>Checks Points: YES / NO</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:10%">01</td>
                    <td style="width:50%">Ensure the materials of previous products removed from area.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td style="width:10%">02</td>
                    <td style="width:50%">Check the QC approve label of Packing material to be dispense.</td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                    <td style="width:10%"></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%;"></td>
                    <td style="width:50%;"><b>Checked By (Store)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Verified By (QA)</b></td>
                    <td colspan="2" style="width:20%;"></td>
                    <td colspan="2" style="width:20%;"></td>
                </tr>
                </table>
                <div></div>
                <span style="font-size:12px;">&nbsp;&nbsp;Note: Physically check the packaging materials code, A.R. No, Quantity as per the dispensing slips and attach the dispensed labels to BMR. </span>
                <div></div>
                <span style="font-size:12px;">&nbsp;&nbsp;Attached By Prod. Sign & Date 27/01/21 </span>
                ';
                
        $html.='<h2>STAGE: 3.0 DISPENSING AND VERIFICATION OF PACKAGING MATERIALS </h2>
                <h3>&nbsp;&nbsp;3.1	Packaging Material Details:</h3>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 3.1</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:10%;"><b>Item Code</b></td>
                    <td style="width:10%;"><b>Packing Material</b></td>
                    <td style="width:5%;"><b>Spec</b></td>
                    <td style="width:5%;"><b>Unit</b></td>
                    <td style="width:10%;"><b>Standard  Batch Size-120 Lit.</b></td>
                    <td style="width:5%;"><b>OA %</b></td>
                    <td style="width:10%;"><b>Actual qty. per batch including % O.A</b></td>
                    <td style="width:10%;"><b>Qty. received from store</b></td>
                    <td style="width:5%;"><b>A.R. NO</b></td>
                    <td style="width:9%;"><b>Issued by(Store)</b></td>
                    <td style="width:8%;"><b>Checked by (Prod.)</b></td>
                    <td style="width:8%;"><b>Verify by (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b>1</b></td>
                    <td style="width:10%;"><b>P001</b></td>
                    <td style="width:10%;"><b>test</b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:5%;"><b>kg</b></td>
                    <td style="width:10%;"><b>120</b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b>80%</b></td>
                    <td style="width:10%;"><b>22%</b></td>
                    <td style="width:5%;"><b>26632</b></td>
                    <td style="width:9%;"><b>master</b></td>
                    <td style="width:8%;"><b>master</b></td>
                    <td style="width:8%;"><b>master</b></td>
                </tr>
                </table>
                ';
        
        $html.='<h2>3.2	Dispensing of Additional Packing Materials:</h2>
                <span style="font-size:12px;"> &nbsp;&nbsp;Dispense additional packaging materials required as per current version of SOP No: SPK/OP/04.  & enter the details in following table.</span><br>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 3.2</b></td>
                </tr>
                <tr >
                    <td style="width:5%;"><b>Sr.No</b></td>
                    <td style="width:10%;"><b>Item Code</b></td>
                    <td style="width:10%;"><b>Packing Material Name</b></td>
                    <td style="width:10%;"><b>Spec</b></td>
                    <td style="width:5%;"><b>Unit.( Nos.)</b></td>
                    <td style="width:10%;"><b>Req. Additional qty.</b></td>
                    <td style="width:10%;"><b>Qty. Issued By store</b></td>
                    <td style="width:10%;"><b>A.R. NO</b></td>
                    <td style="width:10%;"><b>Issued by(Store)</b></td>
                    <td style="width:10%;"><b>Checked by (Prod.)</b></td>
                    <td style="width:10%;"><b>Verify by (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:5%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                    <td style="width:10%;"><b></b></td>
                </tr>
                </table>
                <br pagebreak="true"/>
                ';
                
        $html.='<h2>STAGE: 4.0  PACK STYLE PHOTO VIEW:</h2>
                    ';
                    
        $html.='<h2>4.1 PACKING PROCEDURE:</h2>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;";><b>Table Number : 4.1</b></td>
                </tr>
                 <tr>
                    <td style="width:10%;"><b>Sr.No</b></td>
                    <td style="width:90%;"><b>Packing profile                             ( Pack style : 20x10x10x2ml )</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">1.</td>
                    <td style="width:90%;">Affix overprinted label on each filled ampoule.</td>
                </tr>
                <tr>
                    <td style="width:10%;">2.</td>
                    <td style="width:90%;">Pack such 10 proper labelled ampoules in a transparent PVC tray. </td>
                </tr>
                <tr>
                    <td style="width:10%;">3.</td>
                    <td style="width:90%;">Check the overprinting of carton specimen details. Pack one filled ampoule tray with one leaflet in a carton and close it properly.</td>
                </tr>
                <tr>
                    <td style="width:10%;">4.</td>
                    <td style="width:90%;">Pack 10 filled cartons in a shrink sleeve and Pass through the hot tunnel.</td>
                </tr>
                <tr>
                    <td style="width:10%;">5.</td>
                    <td style="width:90%;">Pack the 20 nos. of such shrink sleeves cartons in to a shipper and check the shipper weight.</td>
                </tr>
                <tr>
                    <td style="width:10%;">6.</td>
                    <td style="width:90%;">Affix one handle with care label and shipper label on each 5-ply shipper boxes.</td>
                </tr>
                <tr>
                    <td style="width:10%;">7.</td>
                    <td style="width:90%;">Close and seal the 5-ply shipper boxes with the help of BOPP BPL Logo Printed tape.</td>
                </tr>
                <tr>
                    <td style="width:10%;">8.</td>
                    <td style="width:90%;">After seal , strapping the 5-ply shipper boxes with the help of strapping machine.</td>
                </tr>
                <tr>
                    <td style="width:10%;">9.</td>
                    <td style="width:90%;">Numbers the each 5- ply shippers sequence wise. Record the shipper’s weight in BPR log sheet and on that same shipper label.</td>
                </tr>
                
                </table>
                <br pagebreak="true"/>
                ';
                
        $html.='<h2>STAGE: 5.0 Details of specimen printing and frequency check of Product.</h2>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 5.0</b></td>
                </tr>
                <tr>
                    <td style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td style="width:70%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%; text-align:center;"><b>Checked by(Prod.)</b></td>
                </tr>
                <tr>
                    <td style="width:10%; ">1.</td>
                    <td style="width:70%; "><b>Label specimen details:</b><br>
                                            B. No.:12545<br>
                                            Mfg. Date:28/07/21 <br>
                                            Exp. Date:28/08/21<br>
                                            <b>Frequency:</b> Check and attach the specimen details of label, start of every roll and start & end of the day. In case of label roll A.R.No. Changes attach the specimen detail. The specimen should be sign duly (QA & production officer) during printing activity.
                    </td>
                    <td style="width:20%; ">master</td>
                </tr>
                <tr>
                    <td style="width:10%; ">2.</td>
                    <td style="width:70%; "><b>Barcode print on carton:</b><br>
                                            GTIN No.:455<br>
                                            Exp. Date :28/08/21<br>
                                            B. No.:44<br>
                                            Serial No. ______________________ to _________________________.<br>
                                            Before start of packing activity generate the barcode label form PD department with reference of requisition slip of product. Print the barcode details on carton after specimen verify by QA.<br>
                    </td>
                    <td style="width:20%; ">master</td>
                </tr>
                <tr>
                    <td style="width:10%; ">3.</td>
                    <td style="width:70%; "><b>Carton specimen details:</b>
                                            B. No.:45<br>
                                            Mfg. Date:24/07/21<br>
                                            Exp. Date:28/07/21<br>
                                            <b>Frequency:</b> Check and attach the specimen details of carton, start & end of the over printing of the day. In case of change in A.R. No of cartons attach the specimen details of same. The specimen should be sign duly (QA& production officer) during printing activity.
    
                    </td>
                    <td style="width:20%; ">master</td>
                </tr>
                <tr>
                    <td style="width:10%; ">4.</td>
                    <td style="width:70%; "><b>Leaflet</b><br>
                                            <b>Frequency:</b> Check the text matter details of leaflet before start & end of the day. In case of change in A.R. No of leaflet attach the specimen details of same to verify any change in text matter, color, folding size etc. The specimen should be sign duly (QA& production officer) before attachment.
    
                    </td>
                    <td style="width:20%; ">master</td>
                </tr>
                <tr>
                    <td style="width:10%; ">4.</td>
                    <td style="width:70%; "><b>Shipper label specimen details:</b><br>
                                            Pack profile :<br>
                                            B. No.:50<br>
                                            Mfg. date:27/07/21<br>
                                            Exp. Date:28/08/21<br>
                                            <b>Frequency:</b> At the start of packing activity checks the printed specimen details of shipper label as per the BPR and duly sign on same shipper label both Production and QA officer. Attach the specimen signed shipper label start and end of the batch.
    
                    </td>
                    <td style="width:20%; ">master</td>
                </tr>
                </table>
                <div></div>';
                
        $html.='<h3>STAGE 6.0: LABEL OVERPRINTING AND LABELING OPERATION: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.1 Line clearance of Ampoule labeling area.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.1.1 Take line clearance of labeling area as per SOP No.: BPL/GEN/QAI/04</span><br>
                <div></div>
                <table border="1" cellpadding="5" style="font-size:12px;">
                <tr>
                    <td style=" width:100%; text-align:center;"><b>Table Number : 6.1</b></td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%; text-align:center;"><b>Sr. No.</b></td>
                    <td rowspan="2" style="width:30%; text-align:center;"><b>Checks Points</b></td>
                    <td style="width:20%;"><b>Date:</b>24/07/21</td>
                    <td style="width:20%;"><b>Time:</b>3:30</td>
                    <td style="width:20%;"><b>Date:</b>24/07/21</td>
                </tr>
                <tr>
                    <td style="width:20%;"><b>Date:24/07/21</b></td>
                    <td style="width:20%;"><b>Time:</b>3:38</td>
                    <td style="width:20%;"><b>Date:</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">01</td>
                    <td style="width:30%;">Previous Product Name</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">02</td>
                    <td style="width:30%;">Previous Product Batch .No.</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:10%;">03</td>
                    <td style="width:30%;">Labeling machine ID No.</td>
                    <td style="width:20%;"><b>ID No.456</b></td>
                    <td style="width:20%;"><b>ID No.458</b></td>
                    <td style="width:20%;"><b>ID No.478</b></td>
                </tr>
                <tr>
                    <td style="width:10%;">04</td>
                    <td style="width:30%;">Record the temperature and relative humidity of labeling area.Temperature NMT 27°C.</td>
                    <td style="width:20%;">27C</td>
                    <td style="width:20%;">27°C</td>
                    <td style="width:20%;">27°C</td>
                </tr>
                
                <tr>
                    <td style="width:10%"><b>Sr. No.</b></td>
                    <td style="width:30%"><b>Checks Points: YES / NO</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                    <td style="width:10%"><b> YES / NO (Prod.)</b></td>
                    <td style="width:10%"><b> YES / NO (QA)</b></td>
                </tr>
                <tr>
                    <td style="width:10%">01</td>
                    <td style="width:30%">Ensure the area should be absence of previous product materials.</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                </tr>
                <tr>
                    <td style="width:10%">02</td>
                    <td style="width:30%">Check the cleanness of labeling machine and surrounding area</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">yes</td>
                </tr>
                <tr>
                    <td style="width:10%">03</td>
                    <td style="width:30%">Ensure the machine changeover is done as per the ampoule size</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">NO</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                </tr>
                <tr>
                    <td style="width:10%">04</td>
                    <td style="width:30%">Check the cleanness of waste bin</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">Yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">Yes</td>
                </tr>
                <tr>
                    <td style="width:10%">05</td>
                    <td style="width:30%">Update the product details on status board.</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">yes</td>
                </tr>
                <tr>
                    <td style="width:10%">06</td>
                    <td style="width:30%">Check the received labels quantity from store as per the requisition slip.</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">yes</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">No</td>
                    <td style="width:10%">yes</td>
                </tr>
                <tr>
                    <td rowspan="2" style="width:10%;"></td>
                    <td style="width:30%;"><b>Checked By (Prod.)</b></td>
                    <td colspan="2" style="width:20%;">master</td>
                    <td colspan="2"style="width:20%;"></td>
                    <td colspan="2"style="width:20%;"></td>
                </tr>
                <tr>
                    <td style="width:30%;"><b>Verified By (QA)</b></td>
                    <td colspan="2" style="width:20%;">master</td>
                    <td colspan="2" style="width:20%;">master</td>
                    <td colspan="2" style="width:20%;">master</td>
                </tr>
                </table><div></div>';
                
        $html.='<h3>6.2 Sticker label Overprinting & Labeling operation:</h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.1 Check the quantity & any damage of sticker label roll before labeling activity.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.2 Check and attach the specimen details of label start of every roll and start & end of the day.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.3 The specimen should be sign duly (QA & production officer) start of printing & labeling activity.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.4During specimen signature verification check the artwork code of label & write the time along with date on same label.</span><br>
                <span style="font-size:12px;">&nbsp;&nbsp;6.2.5 After initial specimen signature sign by QA, start the labeling activity as per SOP No.: BPL/GEN/PAR/070 & 074.</span><br>
                <div></div>
                <h3>6.3	Attach the specimen of overprinted Labels: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;6.3.1 Labeling start Date &time:25/07/21_______     End Date &Time: 1/09/21___  </span><br>
                <div></div>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 6.2</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><div></div><div></div><div></div></td>
                </tr>
                </table>
                <div></div>
                <h3>6.3	Attach the specimen of overprinted Labels:</h3>
                <table border="1" cellpadding="5">
                <tr>
                    <td style="width:100%; text-align:center;"><b>Table Number : 6.3</b></td>
                </tr>
                <tr>
                    <td style="width:100%;"><div></div><div></div><div></div></td>
                </tr>
                </table>
                <div></div>
                ';
        $html.='<h3>6.4 In-process checks during label overprinting and labeling: </h3>
                <span style="font-size:12px;">&nbsp;&nbsp;Check 05-10 Ampoules randomly during in-process.<br>(Frequency- Hourly for Production persons and after every two hours for Q.A person’s)
                 </span><br>
                 <div></div>
                 <table border="1" cellpadding="5">
                 <tr>
                    <td style="width:100%; text-align:center;">Table Number : 6.4</td>
                 </tr>
                 <tr>
                    <td style="width:11%;"><b>Date</b></td>
                    <td style="width:11%;"><b>Time</b></td>
                    <td style="width:11%;"><b>Crack / Unclean ampoules</b></td>
                    <td style="width:11%;"><b>Ampoule identification(2ml Clear Glass Ampoule With White C/B Snep Off.)</b></td>
                    <td style="width:11%;"><b>Quality of labels.(Cross, Smudge, folding, Without label, Double labels)</b></td>
                    <td style="width:12%;"><b>Correctness of specimens (B. No., Mfg. Date, Exp. Date)</b></td>
                    <td style="width:11%;"><b>Legible of Overprinting Details and without print labels</b></td>
                    <td style="width:11%;"><b>Checked By(Prod.)</b></td>
                    <td style="width:11%;"><b>Checked By(QA)</b></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:12%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                    <td style="width:11%;"></td>
                 </tr>
                 </table>
                 
                 <span style="width:100%;">“√” mark means nil defects and “×” mark means defects identify during in-process checks and do the needful corrective action and put the remark with proper justification.</span>
                 <div></div>';
                 
        $html.= '<h3>6.5 Reconciliation of ampoules after labelling activity:</h3>
                 <table border="1" cellpadding="5">
                 <tr>
                    <td style="width:100%; text-align:center;">Table Number : 6.5</td>
                 </tr>
                 <tr>
                    <td rowspan="2" style="width:11%;"><b>Date</b></td>
                    <td rowspan="2" style="width:20%;"><b>Inspected good ampoules received from inspection</b></td>
                    <td style="width:20%;"><b>Labelling Started</b></td>
                    <td rowspan="2" style="width:10%;"><b>No.of ampoules labelled</b></td>
                    <td rowspan="2" style="width:20%;"><b>No.of ampoules rejected during labelling</b></td>
                    <td rowspan="2" style="width:9%;"><b>Done By(Operator)</b></td>
                    <td rowspan="2" style="width:10%;"><b>Checked By</b></td>
                 </tr>
                 <tr>
                    <td style="width:10%;"><b>From</b></td>
                    <td style="width:10%;"><b>To</b></td>
                 </tr>
                 <tr>
                    <td style="width:11%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                 </tr>
                 <tr>
                    <td style="width:11%;">Total</td>
                    <td style="width:20%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:10%;"></td>
                    <td style="width:20%;"></td>
                    <td style="width:9%;"></td>
                    <td style="width:10%;"></td>
                 </tr>
                 </table>
                 <div></div>
                 <table>
                    <tr>
                        <td style="width:35%; border:none;">Rejection amps. destruction Done By(Prod.)</td>
                        <td style="width:10%; border:none;"></td>
                        <td style="width:15%; border:none;">Checked By.(Prod.): </td>
                        <td style="width:15%; border:none;"></td>
                        <td style="width:15%; border:none;">Verify By (QA):</td>
                        <td style="width:10%; border:none;"></td>
                    </tr>
                    <tr>
                        <td style="width:30%; border:none;">(Sign / Date)</td>
                        <td style="width:15%; border:none;"></td>
                        <td style="width:10%; border:none;">(Sign / Date)</td>
                        <td style="width:10%; border:none;"></td>
                        <td style="width:15%; border:none;">(Sign / Date)</td>
                        <td style="width:10%; border:none;"></td>
                    </tr>
                 </table>
                 <div></div>
                 ';
        $html.='<h3>6.6 Reconciliation of labels after labelling activity:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b>Table Number : 6.6</b></td>
                    </tr>
                    <tr>
                        <td style="width:10%;">Date</td>
                        <td style="width:10%;">Received labels quantity from store(A)</td>
                        <td style="width:10%;">Additional labels taken during labelling (B)</td>
                        <td style="width:10%;">Labels used in finished product (C)</td>
                        <td style="width:10%;">Labels used for specimen(D)</td>
                        <td style="width:10%;">Labels reject during Labelling (E)</td>
                        <td style="width:10%;">Excess (without print) labels return to store (F)</td>
                        <td style="width:10%;">Rejection percentage (G) =E ÷ (A + B) – F x 100NMT 3%</td>
                        <td style="width:10%;">Checked By</td>
                        <td style="width:10%;">Verified By (QA)</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                </table>
                <div></div>
                <table>
                    <tr>
                        <td style="width:100%; border:none;">In case of labels returned, Note the return slip Number:</td>
                    </tr>
                    <tr>
                        <td style="width:100%; border:none;">Return By (Prod.) Sign & Date:master</td>
                    </tr>
                </table>
                <div></div>
                ';
                
        $html.='<h3>STAGE: 7.0 CARTON OVERPRINTING OPERATION:</h3>
                &nbsp;&nbsp;<h3>7.1 Line clearance for carton Overprinting area. </h3>
                &nbsp;&nbsp;<h4>7.1.1.	Take line clearance of area as per SOP No.: BPL/GEN/QAI/04.</h4>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:100%; text-align:center;"><b></b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Sr. No</b></td>
                        <td style="width:35%;"><b>Table Number : 7.1</b></td>
                        <td style="width:20%;"><b>Date: 28/07/21  </b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date: 28/07/21 _</b><br><b>Time:___________</b></td>
                        <td style="width:20%;"><b>Date: 28/07/21 </b><br><b>Time:___________</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Previous Product Name</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Previous Product Batch .No.</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Carton over printing machine ID No.</td>
                        <td style="width:20%;"><b>ID No.458</b></td>
                        <td style="width:20%;"><b>ID No.485</b></td>
                        <td style="width:20%;"><b>ID No.488</b></td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Record the temperature of  carton overprinting area Temperature. NMT 27°C  </td>
                        <td style="width:20%;">27°C</td>
                        <td style="width:20%;">27°C</td>
                        <td style="width:20%;">27°C</td>
                    </tr>
                </table>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:5%;"><b>Sr No</b></td>
                        <td style="width:35%;"><b>Checks Points: YES (√) / NO (X)</b></td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                        <td style="width:10%;">(√) / (X)(Prod.)</td>
                        <td style="width:10%;">(√) / (X)(QA.)</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">01</td>
                        <td style="width:35%;">Ensure the area should be absence of previous product materials</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">02</td>
                        <td style="width:35%;">Check the cleanness of carton overprinting machine and surrounding area.</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">yes</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">03</td>
                        <td style="width:35%;">Ensure the machine setting is done as per the carton size.</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">04</td>
                        <td style="width:35%;">Check the cleanness of waste bin.</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">yes</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">05</td>
                        <td style="width:35%;">Status board of area updated.</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">yes</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                    </tr>
                    <tr>
                        <td style="width:5%;">06</td>
                        <td style="width:35%;">Check the received cartons quantity from store as per the requisition slip.</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                        <td style="width:10%;">No</td>
                    </tr>
                    <tr>
                        <td style="width:5%;" rowspan="2"></td>
                        <td style="width:35%;">Checked By (Prod.)</td>
                        <td style="width:20%;">master</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                    <tr>
                        <td style="width:35%;">Verified By (QA)</td>
                        <td style="width:20%;">master</td>
                        <td style="width:20%;"></td>
                        <td style="width:20%;"></td>
                    </tr>
                </table>
               ';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BMR.pdf', 'I');
    }


$conn->close();
?>