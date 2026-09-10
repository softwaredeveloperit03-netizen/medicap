<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    
    
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);


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
    
    if ($_GET["type"] == "get_workorders_for_approval") {
        
        $sql="SELECT a.*, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,b.plan_no,
        b.pack_size,b.pack_unit FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = b.id and
        a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and b.plant_id = p.plant_id
        where a.plant_id = '".$_GET["plant_id"]."' and a.status='pending' order by a.id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
              
                 $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type as m_material_type,b.material_subtype as m_material_subtype,COALESCE(b.material_name, p.product_name) AS material_name,
b.grade as m_grade
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id left join product p on a.material_code = p.product_code and c.plant_id = p.plant_id where a.work_order_id = '".$row["id"]."' and  (a.material_subtype='Raw Material' or a.material_subtype='Intermediate') ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code ";
                
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        /////gradeName
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
        //   $row1['gradeName'] = $prodLatest['gradeName']; 
            $row1['gradeName'] = $prodLatest['gradeName'] ?? $row1['p_grade'];

           /////perccent qty
           $sql5=" SELECT
        JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].material_subtype')) AS material_subtype,
    JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].percent_qty')) AS percent_qty
FROM unitformula a
LEFT JOIN batch_planning b ON a.mfr_no = b.mfr_no
WHERE JSON_SEARCH(raw_materials, 'one', '".$row1['material_code']."') IS NOT NULL AND b.plan_no = '".$row['plan_no']."'";
      
                             $resQ1 = $conn->query($sql5);
              $prodLatest1 = $resQ1->fetch_assoc(); 
         
          $row1['percent_qty'] = $prodLatest1['percent_qty']; 
                        
                        
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["materials"] = $output1;
                 
                 
                $output4 = Array(); 
                 $sql2="select distinct lot_type from work_order_batch_lots where work_order_id ='".$row["id"]."' and plant_id= '".$_GET["plant_id"]."'";
                 $result4 = $conn->query($sql2);
                 if ($result4->num_rows > 0) {
                    while ($row4 = $result4->fetch_assoc()) {
                        $output3 = Array();
                        $sql2="select lot_no from work_order_batch_lots where work_order_id ='".$row["id"]."' and lot_type = '".$row4["lot_type"]."'  and plant_id= '".$_GET["plant_id"]."' group by lot_no";
                     
                        $result1 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2="select a.id,a.material_code,b.material_subtype,b.material_name,a.batch_qty from work_order_batch_lots a 
                                left join material b on a.material_code=b.material_code and a.plant_id = b.plant_id
                                where a.work_order_id = '".$row["id"]."' and a.lot_no ='".$row1["lot_no"]."' and a.plant_id= '".$_GET["plant_id"]."'  and lot_type = '".$row4["lot_type"]."' ";
                                   
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    $output2 = Array();
                                    while ($row2 = $result2->fetch_assoc()) {
                                            $output2[]=$row2;   
                                    }
                                   // $lot_info["lot_no"] = $row1["lot_no"];
                                    //$lot_info["lots_data"] = $output2;
                                      $row1['lots_list'] = $output2;
                                }
                                $output3[]=$row1;
                                
                               
                                
                            }
                            $row4["lots"] = $output3;
                        }
                    $output4[]=   $row4 ;
                    }    
                 }
                   $row["lots"] = $output4;
                 
                 
                 
                 
                 
                 
                 
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
    if ($_GET["type"] == "get_workorders_for_approvalMeha") {
        
        $sql="SELECT a.*, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,p.product_type,b.plan_no,
        b.pack_size,b.pack_unit FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = b.id and
        a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and b.plant_id = p.plant_id
        where a.plant_id = '".$_GET["plant_id"]."' and a.status='pending' order by a.id desc";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
              
                 $sql2="select a.*,b.avbl_stock from (SELECT a.*,b.material_type as m_material_type,b.material_subtype as m_material_subtype,COALESCE(b.material_name, p.product_name) AS material_name,
b.grade as m_grade
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id left join material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id left join product p on a.material_code = p.product_code and c.plant_id = p.plant_id where a.work_order_id = '".$row["id"]."' and  (a.material_subtype='Raw Material' or a.material_subtype='Intermediate') ) as a left join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code ";
                
                 $result1 = $conn->query($sql2);
                 if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        /////gradeName
                         $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['m_grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
        //   $row1['gradeName'] = $prodLatest['gradeName']; 
            $row1['gradeName'] = $prodLatest['gradeName'] ?? $row1['p_grade'];

           /////perccent qty
           $sql5=" SELECT
        JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].material_subtype')) AS material_subtype,
    JSON_UNQUOTE(JSON_EXTRACT(raw_materials, '$[0].percent_qty')) AS percent_qty
FROM unitformula a
LEFT JOIN batch_planning b ON a.mfr_no = b.mfr_no
WHERE JSON_SEARCH(raw_materials, 'one', '".$row1['material_code']."') IS NOT NULL AND b.plan_no = '".$row['plan_no']."'";
      
                             $resQ1 = $conn->query($sql5);
              $prodLatest1 = $resQ1->fetch_assoc(); 
         
          $row1['percent_qty'] = $prodLatest1['percent_qty']; 
                        
                        
                            $output1[]=$row1;   
                    }
                     
                 }
                 $row["materials"] = $output1;
                 
                 
                $output4 = Array(); 
                 $sql2="select distinct lot_type from work_order_batch_lots where work_order_id ='".$row["id"]."' and plant_id= '".$_GET["plant_id"]."'";
                 $result4 = $conn->query($sql2);
                 if ($result4->num_rows > 0) {
                    while ($row4 = $result4->fetch_assoc()) {
                        $output3 = Array();
                        $sql2="select lot_no from work_order_batch_lots where work_order_id ='".$row["id"]."' and lot_type = '".$row4["lot_type"]."'  and plant_id= '".$_GET["plant_id"]."' group by lot_no";
                     
                        $result1 = $conn->query($sql2);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2="select a.id,a.material_code,b.material_subtype,b.material_name,a.batch_qty from work_order_batch_lots a 
                                left join material b on a.material_code=b.material_code and a.plant_id = b.plant_id
                                where a.work_order_id = '".$row["id"]."' and a.lot_no ='".$row1["lot_no"]."' and a.plant_id= '".$_GET["plant_id"]."'  and lot_type = '".$row4["lot_type"]."' ";
                                   
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    $output2 = Array();
                                    while ($row2 = $result2->fetch_assoc()) {
                                            $output2[]=$row2;   
                                    }
                                   // $lot_info["lot_no"] = $row1["lot_no"];
                                    //$lot_info["lots_data"] = $output2;
                                      $row1['lots_list'] = $output2;
                                }
                                $output3[]=$row1;
                                
                               
                                
                            }
                            $row4["lots"] = $output3;
                        }
                    $output4[]=   $row4 ;
                    }    
                 }
                   $row["lots"] = $output4;
                 
                 
                 
                 
                 
                 
                 
                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }
        if ($_GET["type"] == "save_work_order") {
         
          $last_id=0;
        $sql = "Select count(*)+1 as count from mfg_work_order_hdr where  plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $work_order_no =  "WO".$number;
        
       
        
        $sql = "INSERT INTO mfg_work_order_hdr (plant_id,batch_plan_id,batch_id,work_order_no,lod_status,assay_status,
        batch_overages,overages_percent,ebmr_status,calculation_type,no_of_lots,entry_by,material_type)
        values('".$_GET["plant_id"]."','".$input["batch_plan_id"]."','".$input["selected_batch_index"]."','".$work_order_no."','".$input["lod_criteria"]."',
               '".$input["assay_criteria"]."','".$input["batch_overages"]."','".$input["overages_percent"]."',
               '".$input["ebmr_status"]."','".$input["calculation_type"]."','".$input["no_of_lots"]."','".$_GET["emp_id"]."',
               '".$input["material_type"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $r_materials = $input["raw_materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,lod_status,assay_status)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$material["batch_qty"]."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["lod_status"]."','".$material["assay_status"]."')";
                $conn->query($sql);
                
            }
            $p_materials = $input["packing_materials"];
            for ($i = 0; $i <count($p_materials); $i++) { 
                  $material = $p_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,lod_status,assay_status)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$material["batch_qty"]."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["lod_status"]."','".$material["assay_status"]."')";
                $conn->query($sql);
                 
            }
            $lots =  $input["lots"];
            
            for ($k = 0; $k <count($lots); $k++) { 
               // $sql ="SELECT concat('L',LPAD((select count(distinct(lot_no)) as lot_no from work_order_batch_lots) , 4, 0)) as lot_no";
                $sql ="select count(distinct(lot_no))+1 as lot_no from work_order_batch_lots where lot_type='Lot Materials' and plant_id = '".$_GET["plant_id"]."' ";
                $res = $conn->query($sql);
                $rowData = $res->fetch_assoc();
                $lot_no = $rowData['lot_no'];
                  $materials = $lots[$k]['lots']; 
                  for ($j = 0; $j <count($materials); $j++) { 
                      $material = $materials[$j]; 
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by)
                    values('Lot Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."')";
                    $conn->query($sql);
                 
                } 
                   
            }
           
            $lots =  $input["coated_lots"];
            for ($k = 0; $k <count($lots); $k++) { 
                //$sql ="SELECT concat('L',LPAD((select count(distinct(lot_no)) as lot_no from work_order_batch_lots) , 4, 0)) as lot_no";
                $sql ="select count(distinct(lot_no))+1 as lot_no from work_order_batch_lots where lot_type='Coating Materials' and plant_id = '".$_GET["plant_id"]."' ";
                $res = $conn->query($sql);
                $rowData = $res->fetch_assoc();
                $lot_no = $rowData['lot_no'];
                  $materials = $lots[$k]['lots']; 
                  for ($j = 0; $j <count($materials); $j++) { 
                      $material = $materials[$j]; 
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by)
                    values('Coating Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."')";
                     
                    $conn->query($sql);
                 
                } 
                   
            }
            
            $lots =  $input["common_materails"]; 
            
            for ($k = 0; $k <count($lots); $k++) { 
                $lot_no = '';
                  $material = $lots[$k];  
                     
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by)
                    values('Common Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."')";
                       
                    $conn->query($sql);
                 
                 
                   
            } 
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
        if ($_GET["type"] == "save_work_orderMeha") {
            
            
         
          $last_id=0;
        $sql = "Select count(*)+1 as count from mfg_work_order_hdr where  plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }
        $length = 4;
        $number = substr(str_repeat(0, $length).$last_id, - $length);
        $work_order_no =  "WO".$number;
        
       
        
        $sql = "INSERT INTO mfg_work_order_hdr (plant_id,batch_plan_id,batch_id,work_order_no,lod_status,assay_status,
        batch_overages,overages_percent,ebmr_status,calculation_type,no_of_lots,entry_by,material_type)
        values('".$_GET["plant_id"]."','".$input["batch_plan_id"]."','".$input["selected_batch_index"]."','".$work_order_no."','".$input["lod_criteria"]."',
               '".$input["assay_criteria"]."','".$input["batch_overages"]."','".$input["overages_percent"]."',
               '".$input["ebmr_status"]."','".$input["calculation_type"]."','".$input["no_of_lots"]."','".$_GET["emp_id"]."',
               '".$input["material_type"]."')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $r_materials = $input["raw_materials"];
            for ($i = 0; $i <count($r_materials); $i++) {
                $material = $r_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,lod_status,assay_status,material_subtype,material_type,dispensingIn)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$material["batch_qty"]."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["lod_status"]."','".$material["assay_status"]."'
                ,'".$material["material_subtype"]."','".$material["material_type"]."','".$material["dispensingIn"]."')";
                $conn->query($sql);
                
            }
            $p_materials = $input["packing_materials"];
            for ($i = 0; $i <count($p_materials); $i++) { 
                  $material = $p_materials[$i];
                $sql = "INSERT INTO  mfg_work_order_dtl(work_order_id,material_code,grade,unit_qty,unit,overages,total_unit_qty,batch_qty,
                batch_overages,total_batch_qty,lod_status,assay_status)
                values('".$last_id."','".$material["material_code"]."','".$material["grade"]."',
                 '".$material["qty"]."','".$material["unit"]."', '".$material["overages"]."','".$material["total_qty"]."',
                 '".$material["batch_qty"]."','".$material["batch_overages"]."',
                '".$material["total_final_qty"]."','".$material["lod_status"]."','".$material["assay_status"]."')";
                $conn->query($sql);
                 
            }
            $lots =  $input["lots"];
            
            for ($k = 0; $k <count($lots); $k++) { 
               // $sql ="SELECT concat('L',LPAD((select count(distinct(lot_no)) as lot_no from work_order_batch_lots) , 4, 0)) as lot_no";
                $sql ="select count(distinct(lot_no))+1 as lot_no from work_order_batch_lots where lot_type='Lot Materials' and plant_id = '".$_GET["plant_id"]."' ";
                $res = $conn->query($sql);
                $rowData = $res->fetch_assoc();
                $lot_no = $rowData['lot_no'];
                  $materials = $lots[$k]['lots']; 
                  for ($j = 0; $j <count($materials); $j++) { 
                      $material = $materials[$j]; 
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by,material_subtype,material_type,dispensingIn)
                    values('Lot Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."','".$material["material_subtype"]."','".$material["material_type"]."','".$material["dispensingIn"]."')";
                    $conn->query($sql);
                 
                } 
                   
            }
           
            $lots =  $input["coated_lots"];
            for ($k = 0; $k <count($lots); $k++) { 
                //$sql ="SELECT concat('L',LPAD((select count(distinct(lot_no)) as lot_no from work_order_batch_lots) , 4, 0)) as lot_no";
                $sql ="select count(distinct(lot_no))+1 as lot_no from work_order_batch_lots where lot_type='Coating Materials' and plant_id = '".$_GET["plant_id"]."' ";
                $res = $conn->query($sql);
                $rowData = $res->fetch_assoc();
                $lot_no = $rowData['lot_no'];
                  $materials = $lots[$k]['lots']; 
                  for ($j = 0; $j <count($materials); $j++) { 
                      $material = $materials[$j]; 
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by,material_subtype,material_type,dispensingIn)
                    values('Coating Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."','".$material["material_subtype"]."','".$material["material_type"]."','".$material["dispensingIn"]."')";
                     
                    $conn->query($sql);
                 
                } 
                   
            }
            
            $lots =  $input["common_materails"]; 
            
            for ($k = 0; $k <count($lots); $k++) { 
                $lot_no = '';
                  $material = $lots[$k];  
                     
                    $sql = "INSERT INTO  work_order_batch_lots(lot_type,plant_id, work_order_id, lot_no, material_code, batch_qty, entry_by,material_subtype,material_type,dispensingIn)
                    values('Common Materials','".$_GET["plant_id"]."','".$last_id."','".$lot_no."','".$material["material_code"]."',
                     '".$material["each_lot_qty"]."','".$_GET["emp_id"]."','".$material["material_subtype"]."','".$material["material_type"]."','".$material["dispensingIn"]."')";
                       
                    $conn->query($sql);
                 
                 
                   
            } 
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
        } 
 
else if ($_GET["type"] == "Get_quotation_Log") {
		$output = array();
  	    $sql="select * from  New_quotation where status= 'approve'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		echo json_encode($output);
	}
else if ($_GET["type"] == "Get_quotation") {
		$output = array();
  	    $sql="select * from  New_quotation where status= 'pending'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		echo json_encode($output);
	}
	
else if ($_GET["type"] == "updateQuotation") {
       $sql = "UPDATE New_quotation SET status='".$_GET["status"]."'  WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
 }
    
    
    
    
    
    
    
    
    
    
    else if ($_GET["type"] == "approve_work_order") {
        $sql ="update mfg_work_order_hdr set status = '".$_GET["status"]."' ,
               approved_by ='".$_GET["emp_id"]."',approved_date= '".$entry_date."'
               where  id ='".$_GET["id"]."' "; 
             
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
//         else if ($_GET["type"] == "update_qa_status") {
            
//             $plant_id = $_GET["plant_id"];
// $emp_id   = $_GET["emp_id"];
// $entry_date = date("Y-m-d H:i:s");

// /* ======================================
//   STEP 1: GENERATE BATCH NUMBER
// ====================================== */

// // Example plant code (you can fetch from DB also)
// $plantCode = "PL".$plant_id;

// // Year-Month
// $ym = date("ym");

// // Get last batch number
// $sqlLast = "
//     SELECT batch_number 
//     FROM mfg_work_order_hdr 
//     WHERE plant_id = '".$plant_id."' 
//     AND batch_number IS NOT NULL 
//     ORDER BY id DESC 
//     LIMIT 1
// ";

// $resLast = $conn->query($sqlLast);

// $nextSeq = 1;

// if ($resLast->num_rows > 0) {
//     $rowLast = $resLast->fetch_assoc();
//     $lastBatch = $rowLast['batch_number'];

//     // Extract last running number
//     $parts = explode('/', $lastBatch);
//     if (count($parts) == 3) {
//         $nextSeq = intval($parts[2]) + 1;
//     }
// }

// // Pad running number
// $seq = str_pad($nextSeq, 4, "0", STR_PAD_LEFT);

// // Final Batch Number
// $batch_number = $plantCode.'/'.$ym.'/'.$seq;


// /* ======================================
//   STEP 2: CHECK DUPLICATE (SAFETY)
// ====================================== */

// $chkSql = "
//     SELECT id 
//     FROM mfg_work_order_hdr 
//     WHERE batch_number = '".$batch_number."' 
//     AND plant_id = '".$plant_id."'
// ";

// $chkRes = $conn->query($chkSql);

// if ($chkRes->num_rows > 0) {
//     echo json_encode(["status" => "Batch number already exists"]);
//     exit;
// }


// /* ======================================
//   STEP 3: UPDATE WORK ORDER
// ====================================== */

// $sqlUpdate = "
// UPDATE mfg_work_order_hdr SET 
//     stability              = '".$input["stability"]."',
//     stability_reason       = '".$input["stability_reason"]."',
//     process_validation     = '".$input["process_validation"]."',
//     hard_copy_issued       = '".$input["hard_copy_issued"]."',
//     ebmr_number            = '".$input["ebmr_number"]."',
//     status            = '".$input["status"]."',
//     hard_copy_issued_by    = '".$input["hard_copy_issued_by"]."',
//     batch_number           = '".$batch_number."',
//     qa_person              = '".$emp_id."',
//     qa_date                = '".$entry_date."'
// WHERE id = '".$input["id"]."'
// ";

// if ($conn->query($sqlUpdate)) {
//     echo json_encode([
//         "status" => "success",
//         "batch_number" => $batch_number
//     ]);
// } else {
//     echo json_encode(["status" => $conn->error]);
// }

//         } 
else if ($_GET["type"] == "update_qa_status") {
            
    $plant_id = $_GET["plant_id"];
    $emp_id   = $_GET["emp_id"];
    $entry_date = date("Y-m-d H:i:s");

    /* ======================================
       STEP 1: GENERATE BASE DETAILS
    ====================================== */

    $plantCode = "PL".$plant_id;
    $ym = date("ym");

    // Get last batch number
    $sqlLast = "
        SELECT batch_number 
        FROM mfg_work_order_hdr 
        WHERE plant_id = '".$plant_id."' 
        AND batch_number IS NOT NULL 
        ORDER BY id DESC 
        LIMIT 1
    ";

    $resLast = $conn->query($sqlLast);

    $nextSeq = 1;

    if ($resLast->num_rows > 0) {
        $rowLast = $resLast->fetch_assoc();
        $lastBatch = $rowLast['batch_number'];

        $parts = explode('/', $lastBatch);
        if (count($parts) == 3) {
            $nextSeq = intval($parts[2]) + 1;
        }
    }

    /* ======================================
       STEP 2: GENERATE UNIQUE BATCH NUMBER
    ====================================== */

    while (true) {

        $seq = str_pad($nextSeq, 4, "0", STR_PAD_LEFT);
        $batch_number = $plantCode.'/'.$ym.'/'.$seq;

        $chkSql = "
            SELECT id 
            FROM mfg_work_order_hdr 
            WHERE batch_number = '".$batch_number."' 
            AND plant_id = '".$plant_id."'
        ";

        $chkRes = $conn->query($chkSql);

        if ($chkRes->num_rows == 0) {
            break; // unique found
        }

        $nextSeq++; // try next
    }

    /* ======================================
       STEP 3: UPDATE WORK ORDER
    ====================================== */

    $sqlUpdate = "
    UPDATE mfg_work_order_hdr SET 
        stability              = '".$input["stability"]."',
        stability_reason       = '".$input["stability_reason"]."',
        process_validation     = '".$input["process_validation"]."',
        hard_copy_issued       = '".$input["hard_copy_issued"]."',
        ebmr_number            = '".$input["ebmr_number"]."',
        status                 = '".$input["status"]."',
        hard_copy_issued_by    = '".$input["hard_copy_issued_by"]."',
        batch_number           = '".$batch_number."',
        qa_person              = '".$emp_id."',
        qa_date                = '".$entry_date."'
    WHERE id = '".$input["id"]."'
    ";

    if ($conn->query($sqlUpdate)) {
        echo json_encode([
            "status" => "success",
            "batch_number" => $batch_number
        ]);
    } else {
        echo json_encode(["status" => $conn->error]);
    }

}

        else if ($_GET["type"] == "get_approved_work_orders_for_qa_approval") {
            
        // Handle work orders from canplan (without batch_plan_id) and from batch_planning (with batch_plan_id)
        $sql = "SELECT a.*, 
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.product_code FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.product_code
                END AS product_code,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT p.product_name FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        p.product_name
                END AS product_name,
   
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.batch_size
                END AS batch_size,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.packingStyle FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.pack_size
                END AS pack_size,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.packingUnit FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.pack_unit
                END AS pack_unit,
                p.dosage_form, p.product_type,
                b.bfr_no, b.mfr_no, b.plan_no,
                (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(middlename, ''), ' ', IFNULL(lastname, ''))
                 FROM employee 
                 WHERE emp_id = a.entry_by
                ) AS entry_by_name
                FROM mfg_work_order_hdr a 
                LEFT JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                LEFT JOIN product p ON (
                    CASE 
                        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                            (SELECT om.product_code FROM Work_order_materials wm 
                             LEFT JOIN order_materials om ON wm.order_no = om.order_no
                             WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                        ELSE
                            b.product_code
                    END
                ) = p.product_code AND a.plant_id = p.plant_id
                WHERE a.plant_id = '".$_GET["plant_id"]."' AND (qa_person IS NULL OR qa_person = '') 
                ORDER BY a.id DESC"; 
        
        $result = $conn->query($sql);                   
        $output = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                
                // Get mfr_no and bfr_no for canplan work orders (if not already set from batch_planning)
                if (empty($row['mfr_no']) || empty($row['bfr_no'])) {
                    // Get from Work_order_materials and unitformula
                    $woSql = "SELECT wm.product_code, wm.batch_size, wm.planUnit 
                             FROM Work_order_materials wm 
                             WHERE wm.workorder_no = '".$row['work_order_no']."' LIMIT 1";
                    $woResult = $conn->query($woSql);
                    if ($woResult && $woResult->num_rows > 0) {
                        $woRow = $woResult->fetch_assoc();
                        $product_code = $woRow['product_code'] ?? '';
                        $batch_size = $woRow['batch_size'] ?? '';
                        $planUnit = $woRow['planUnit'] ?? '';
                        
                        if (!empty($product_code)) {
                            // Get mfr_no from unitformula
                            $mfrSql = "SELECT mfr_no FROM unitformula 
                                      WHERE product_code = '".$product_code."' 
                                      AND status = 'Approve' 
                                      ORDER BY id DESC LIMIT 1";
                            $mfrResult = $conn->query($mfrSql);
                            if ($mfrResult && $mfrResult->num_rows > 0) {
                                $mfrRow = $mfrResult->fetch_assoc();
                                $row['mfr_no'] = $mfrRow['mfr_no'] ?? '';
                                
                                // Get bfr_no from batch_formula_info
                                if (!empty($row['mfr_no']) && !empty($batch_size) && !empty($planUnit)) {
                                    $bfrSql = "SELECT bfr_no FROM batch_formula_info 
                                              WHERE mfr_no = '".$row['mfr_no']."' 
                                              AND batch_formula_weight = '".$batch_size."'
                                              AND batch_formula_weight_unit = '".$planUnit."'
                                              AND status = 'Approve'
                                              LIMIT 1";
                                    $bfrResult = $conn->query($bfrSql);
                                    if ($bfrResult && $bfrResult->num_rows > 0) {
                                        $bfrRow = $bfrResult->fetch_assoc();
                                        $row['bfr_no'] = $bfrRow['bfr_no'] ?? '';
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Get materials from mfg_work_order_dtl
                // Include all materials (RM, Bulk, PM) - don't filter by material_subtype
                $sql2 = "SELECT a.*, 
                        b.grade as m_grade,
                        COALESCE(b.material_subtype, 'Raw Material') as material_subtype,
                        COALESCE(b.material_type, 'RM') as material_type,
                        COALESCE(b.material_name, p.product_name, bm.bulkName) AS material_name,
                        COALESCE(a.stage, 'Default') as stage
                        FROM mfg_work_order_dtl a 
                        LEFT JOIN mfg_work_order_hdr c ON a.work_order_id = c.id 
                        LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id 
                        LEFT JOIN product p ON a.material_code = p.product_code AND c.plant_id = p.plant_id
                        LEFT JOIN bulkmaster bm ON a.material_code = bm.bulkCode
                        WHERE a.work_order_id = '".$row["id"]."'";
                
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        // Get gradeName
                        $m_grade = $row1['m_grade'] ?? '';
                        if (!empty($m_grade)) {
                            $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ('".$m_grade."')";
                            $resQ = $conn->query($q);
                            if ($resQ && $resQ->num_rows > 0) {
                                $prodLatest = $resQ->fetch_assoc();
                                $row1['gradeName'] = $prodLatest['gradeName'] ?? '';
                            }
                        }
                        $row1['grade'] = $row1['gradeName'] ?? $row1['m_grade'] ?? '';
                        
                        // Get percent_qty from batch_materials (not from unitformula raw_materials JSON)
                        $percent_qty = null;
                        if (!empty($row['bfr_no'])) {
                            // Get percent_qty from batch_materials
                            $percentSql = "SELECT qty, 
                                          (SELECT batch_formula_weight FROM batch_formula_info WHERE bfr_no = '".$row['bfr_no']."' LIMIT 1) as batch_formula_weight
                                          FROM batch_materials 
                                          WHERE bfr_no = '".$row['bfr_no']."' 
                                          AND material_code = '".$row1['material_code']."' 
                                          AND material_type = 'Raw Material'
                                          LIMIT 1";
                            $percentResult = $conn->query($percentSql);
                            if ($percentResult && $percentResult->num_rows > 0) {
                                $percentRow = $percentResult->fetch_assoc();
                                $batch_formula_weight = floatval($percentRow['batch_formula_weight'] ?? 0);
                                $qty_per_batch = floatval($percentRow['qty'] ?? 0);
                                if ($batch_formula_weight > 0) {
                                    // Calculate percent: (qty / batch_formula_weight) * 100
                                    $percent_qty = ($qty_per_batch / $batch_formula_weight) * 100;
                                }
                            }
                        }
                        $row1['percent_qty'] = $percent_qty;
                        
                        // Get available stock
                        $stockSql = "SELECT COALESCE(SUM(qty), 0) as avbl_stock FROM stock_book 
                                    WHERE material_code = '".$row1['material_code']."' 
                                    AND plant_id = '".$_GET["plant_id"]."'";
                        $stockResult = $conn->query($stockSql);
                        $row1['avbl_stock'] = 0;
                        if ($stockResult && $stockResult->num_rows > 0) {
                            $stockRow = $stockResult->fetch_assoc();
                            $row1['avbl_stock'] = floatval($stockRow['avbl_stock'] ?? 0);
                        }
                        
                        // Use material_name_final
                        $row1['material_name'] = $row1['material_name_final'] ?? $row1['material_code'];
                        
                        // Set default values for fields expected by HTML
                        $row1['overages'] = $row1['overages'] ?? 0;
                        $row1['total_unit_qty'] = $row1['total_unit_qty'] ?? ($row1['unit_qty'] ?? 0);
                        $row1['batch_overages'] = $row1['batch_overages'] ?? 0;
                        $row1['total_batch_qty'] = $row1['total_batch_qty'] ?? ($row1['batch_qty'] ?? 0);
                        $row1['stage'] = $row1['stage'] ?? 'Default';
                        
                        // Ensure material_subtype is set
                        if (empty($row1['material_subtype'])) {
                            // Check if it's bulk
                            $isBulkCheck = "SELECT COUNT(*) as cnt FROM bulkmaster WHERE bulkCode = '".$row1['material_code']."'";
                            $bulkCheckResult = $conn->query($isBulkCheck);
                            if ($bulkCheckResult && $bulkCheckResult->num_rows > 0) {
                                $bulkCheckRow = $bulkCheckResult->fetch_assoc();
                                if (intval($bulkCheckRow['cnt'] ?? 0) > 0) {
                                    $row1['material_subtype'] = 'Bulk';
                                } else {
                                    $row1['material_subtype'] = 'Raw Material';
                                }
                            } else {
                                $row1['material_subtype'] = 'Raw Material';
                            }
                        }
                        
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                // Set plan_no for canplan work orders (if not set)
                if (empty($row['plan_no'])) {
                    $row['plan_no'] = $row['work_order_no'] ?? '';
                }
                
                // Get lots (if work_order_batch_lots table exists, otherwise empty array)
                $output4 = Array();
                $lotsCheckSql = "SHOW TABLES LIKE 'work_order_batch_lots'";
                $lotsTableExists = $conn->query($lotsCheckSql);
                if ($lotsTableExists && $lotsTableExists->num_rows > 0) {
                    $sql2 = "SELECT DISTINCT lot_type FROM work_order_batch_lots 
                            WHERE work_order_id = '".$row["id"]."' AND plant_id = '".$_GET["plant_id"]."'";
                    $result4 = $conn->query($sql2);
                    if ($result4 && $result4->num_rows > 0) {
                        while ($row4 = $result4->fetch_assoc()) {
                            $output3 = Array();
                            $sql2 = "SELECT lot_no FROM work_order_batch_lots 
                                    WHERE work_order_id = '".$row["id"]."' 
                                    AND lot_type = '".$row4["lot_type"]."' 
                                    AND plant_id = '".$_GET["plant_id"]."' 
                                    GROUP BY lot_no";
                            
                            $result1 = $conn->query($sql2);
                            if ($result1 && $result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                                    $sql2 = "SELECT a.id, a.material_code, b.material_subtype, b.material_name, a.batch_qty 
                                            FROM work_order_batch_lots a 
                                            LEFT JOIN material b ON a.material_code = b.material_code AND a.plant_id = b.plant_id
                                            WHERE a.work_order_id = '".$row["id"]."' 
                                            AND a.lot_no = '".$row1["lot_no"]."' 
                                            AND a.plant_id = '".$_GET["plant_id"]."' 
                                            AND lot_type = '".$row4["lot_type"]."'";
                                    
                                    $result2 = $conn->query($sql2);
                                    if ($result2 && $result2->num_rows > 0) {
                                        $output2 = Array();
                                        while ($row2 = $result2->fetch_assoc()) {
                                            $output2[] = $row2;
                                        }
                                        $row1['lots_list'] = $output2;
                                    }
                                    $output3[] = $row1;
                                }
                                $row4["lots"] = $output3;
                            }
                            $output4[] = $row4;
                        }
                    }
                }
                $row["lots"] = $output4;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "get_approved_work_orders_for_qa_approvalMeha") {
        // Handle work orders from canplan (without batch_plan_id) and from batch_planning (with batch_plan_id)
        $sql = "SELECT a.*, 
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT om.product_code FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.product_code
                END AS product_code,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT p.product_name FROM Work_order_materials wm 
                         LEFT JOIN order_materials om ON wm.order_no = om.order_no
                         LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
                         WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        p.product_name
                END AS product_name,
                CASE 
                    WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                        (SELECT wm.batch_size FROM Work_order_materials wm WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                    ELSE
                        b.batch_size
                END AS batch_size,
                p.dosage_form, p.short_code, p.product_type, p.batch_type,
                b.bfr_no, b.mfr_no, b.plan_no, b.pack_size, b.pack_unit
                FROM mfg_work_order_hdr a 
                LEFT JOIN batch_planning b ON a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                LEFT JOIN product p ON (
                    CASE 
                        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                            (SELECT om.product_code FROM Work_order_materials wm 
                             LEFT JOIN order_materials om ON wm.order_no = om.order_no
                             WHERE wm.workorder_no = a.work_order_no LIMIT 1)
                        ELSE
                            b.product_code
                    END
                ) = p.product_code AND a.plant_id = p.plant_id
                WHERE a.plant_id = '".$_GET["plant_id"]."' 
                AND (qa_person IS NULL OR qa_person = '') 
                AND a.material_type = 'RM' 
                ORDER BY a.id DESC"; 
        
                
        $result = $conn->query($sql);
        $output = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Get mfr_no and bfr_no for canplan work orders (if not already set from batch_planning)
                if (empty($row['mfr_no']) || empty($row['bfr_no'])) {
                    // Get from Work_order_materials and unitformula
                    $woSql = "SELECT wm.product_code, wm.batch_size, wm.planUnit 
                             FROM Work_order_materials wm 
                             WHERE wm.workorder_no = '".$row['work_order_no']."' LIMIT 1";
                    $woResult = $conn->query($woSql);
                    if ($woResult && $woResult->num_rows > 0) {
                        $woRow = $woResult->fetch_assoc();
                        $product_code = $woRow['product_code'] ?? '';
                        $batch_size = $woRow['batch_size'] ?? '';
                        $planUnit = $woRow['planUnit'] ?? '';
                        
                        if (!empty($product_code)) {
                            // Get mfr_no from unitformula
                            $mfrSql = "SELECT mfr_no FROM unitformula 
                                      WHERE product_code = '".$product_code."' 
                                      AND status = 'Approve' 
                                      ORDER BY id DESC LIMIT 1";
                            $mfrResult = $conn->query($mfrSql);
                            if ($mfrResult && $mfrResult->num_rows > 0) {
                                $mfrRow = $mfrResult->fetch_assoc();
                                $row['mfr_no'] = $mfrRow['mfr_no'] ?? '';
                                
                                // Get bfr_no from batch_formula_info
                                if (!empty($row['mfr_no']) && !empty($batch_size) && !empty($planUnit)) {
                                    $bfrSql = "SELECT bfr_no FROM batch_formula_info 
                                              WHERE mfr_no = '".$row['mfr_no']."' 
                                              AND batch_formula_weight = '".$batch_size."'
                                              AND batch_formula_weight_unit = '".$planUnit."'
                                              AND status = 'Approve'
                                              LIMIT 1";
                                    $bfrResult = $conn->query($bfrSql);
                                    if ($bfrResult && $bfrResult->num_rows > 0) {
                                        $bfrRow = $bfrResult->fetch_assoc();
                                        $row['bfr_no'] = $bfrRow['bfr_no'] ?? '';
                                    }
                                }
                            }
                        }
                    }
                }
                
                $output1 = Array();
                
                // Get materials from mfg_work_order_dtl
                $sql2 = "SELECT a.*, 
                        b.grade as m_grade,
                        COALESCE(b.material_subtype, 'Raw Material') as material_subtype,
                        COALESCE(b.material_type, 'RM') as material_type,
                        COALESCE(b.material_name, p.product_name, bm.bulkName) AS material_name,
                        COALESCE(a.stage, 'Default') as stage
                        FROM mfg_work_order_dtl a 
                        LEFT JOIN mfg_work_order_hdr c ON a.work_order_id = c.id 
                        LEFT JOIN material b ON a.material_code = b.material_code AND c.plant_id = b.plant_id 
                        LEFT JOIN product p ON a.material_code = p.product_code AND c.plant_id = p.plant_id
                        LEFT JOIN bulkmaster bm ON a.material_code = bm.bulkCode
                        WHERE a.work_order_id = '".$row["id"]."'";
                
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        // Get gradeName
                        $m_grade = $row1['m_grade'] ?? '';
                        if (!empty($m_grade)) {
                            $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ('".$m_grade."')";
                            $resQ = $conn->query($q);
                            if ($resQ && $resQ->num_rows > 0) {
                                $prodLatest = $resQ->fetch_assoc();
                                $row1['gradeName'] = $prodLatest['gradeName'] ?? '';
                            }
                        }
                        $row1['grade'] = $row1['gradeName'] ?? $row1['m_grade'] ?? '';
                        
                        // Get percent_qty from batch_materials (not from unitformula raw_materials JSON)
                        $percent_qty = null;
                        if (!empty($row['bfr_no'])) {
                            // Get percent_qty from batch_materials
                            $percentSql = "SELECT qty, 
                                          (SELECT batch_formula_weight FROM batch_formula_info WHERE bfr_no = '".$row['bfr_no']."' LIMIT 1) as batch_formula_weight
                                          FROM batch_materials 
                                          WHERE bfr_no = '".$row['bfr_no']."' 
                                          AND material_code = '".$row1['material_code']."' 
                                          AND material_type = 'Raw Material'
                                          LIMIT 1";
                            $percentResult = $conn->query($percentSql);
                            if ($percentResult && $percentResult->num_rows > 0) {
                                $percentRow = $percentResult->fetch_assoc();
                                $batch_formula_weight = floatval($percentRow['batch_formula_weight'] ?? 0);
                                $qty_per_batch = floatval($percentRow['qty'] ?? 0);
                                if ($batch_formula_weight > 0) {
                                    // Calculate percent: (qty / batch_formula_weight) * 100
                                    $percent_qty = ($qty_per_batch / $batch_formula_weight) * 100;
                                }
                            }
                        }
                        $row1['percent_qty'] = $percent_qty;
                        
                        // Get available stock
                        $stockSql = "SELECT COALESCE(SUM(qty), 0) as avbl_stock FROM stock_book 
                                    WHERE material_code = '".$row1['material_code']."' 
                                    AND plant_id = '".$_GET["plant_id"]."'";
                        $stockResult = $conn->query($stockSql);
                        $row1['avbl_stock'] = 0;
                        if ($stockResult && $stockResult->num_rows > 0) {
                            $stockRow = $stockResult->fetch_assoc();
                            $row1['avbl_stock'] = floatval($stockRow['avbl_stock'] ?? 0);
                        }
                        
                        // Set default values for fields expected by HTML
                        $row1['overages'] = $row1['overages'] ?? 0;
                        $row1['total_unit_qty'] = $row1['total_unit_qty'] ?? ($row1['unit_qty'] ?? 0);
                        $row1['batch_overages'] = $row1['batch_overages'] ?? 0;
                        $row1['total_batch_qty'] = $row1['total_batch_qty'] ?? ($row1['batch_qty'] ?? 0);
                        
                        // Ensure material_subtype is set
                        if (empty($row1['material_subtype'])) {
                            // Check if it's bulk
                            $isBulkCheck = "SELECT COUNT(*) as cnt FROM bulkmaster WHERE bulkCode = '".$row1['material_code']."'";
                            $bulkCheckResult = $conn->query($isBulkCheck);
                            if ($bulkCheckResult && $bulkCheckResult->num_rows > 0) {
                                $bulkCheckRow = $bulkCheckResult->fetch_assoc();
                                if (intval($bulkCheckRow['cnt'] ?? 0) > 0) {
                                    $row1['material_subtype'] = 'Bulk';
                                } else {
                                    $row1['material_subtype'] = 'Raw Material';
                                }
                            } else {
                                $row1['material_subtype'] = 'Raw Material';
                            }
                        }
                        
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                // Set plan_no for canplan work orders (if not set)
                if (empty($row['plan_no'])) {
                    $row['plan_no'] = $row['work_order_no'] ?? '';
                }
                 
                
                // Get lots (if work_order_batch_lots table exists, otherwise empty array)
                $output4 = Array();
                $lotsCheckSql = "SHOW TABLES LIKE 'work_order_batch_lots'";
                $lotsTableExists = $conn->query($lotsCheckSql);
                if ($lotsTableExists && $lotsTableExists->num_rows > 0) {
                    $sql2 = "SELECT DISTINCT lot_type FROM work_order_batch_lots 
                            WHERE work_order_id = '".$row["id"]."' AND plant_id = '".$_GET["plant_id"]."'";
                    $result4 = $conn->query($sql2);
                    if ($result4 && $result4->num_rows > 0) {
                        while ($row4 = $result4->fetch_assoc()) {
                            $output3 = Array();
                            $sql2 = "SELECT lot_no FROM work_order_batch_lots 
                                    WHERE work_order_id = '".$row["id"]."' 
                                    AND lot_type = '".$row4["lot_type"]."' 
                                    AND plant_id = '".$_GET["plant_id"]."' 
                                    GROUP BY lot_no";
                            
                            $result1 = $conn->query($sql2);
                            if ($result1 && $result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                                    $sql2 = "SELECT a.id, a.material_code, b.material_subtype, b.material_name, a.batch_qty 
                                            FROM work_order_batch_lots a 
                                            LEFT JOIN material b ON a.material_code = b.material_code AND a.plant_id = b.plant_id
                                            WHERE a.work_order_id = '".$row["id"]."' 
                                            AND a.lot_no = '".$row1["lot_no"]."' 
                                            AND a.plant_id = '".$_GET["plant_id"]."' 
                                            AND lot_type = '".$row4["lot_type"]."'";
                                    
                                    $result2 = $conn->query($sql2);
                                    if ($result2 && $result2->num_rows > 0) {
                                        $output2 = Array();
                                        while ($row2 = $result2->fetch_assoc()) {
                                            $output2[] = $row2;
                                        }
                                        $row1['lots_list'] = $output2;
                                    }
                                    $output3[] = $row1;
                                }
                                $row4["lots"] = $output3;
                            }
                            $output4[] = $row4;
                        }
                    }
                }
                $row["lots"] = $output4;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_qa_approved_work_orders_WO_BMR") {
        // Get QA approved work orders for dashboard
        // Handle work orders from canplan (without batch_plan_id) and from batch_planning (with batch_plan_id)
        $material_type = $_GET["material_type"] ?? 'RM';
        
      $sql = "SELECT a.*, 
    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
            (SELECT om.product_code FROM Work_order_materials wm 
             LEFT JOIN order_materials om ON wm.order_no = om.order_no
             WHERE wm.workorder_no = a.work_order_no LIMIT 1)
        ELSE
            b.product_code
    END AS product_code,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
            (SELECT p.product_name FROM Work_order_materials wm 
             LEFT JOIN order_materials om ON wm.order_no = om.order_no
             LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
             WHERE wm.workorder_no = a.work_order_no LIMIT 1)
        ELSE
            p.product_name
    END AS product_name,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
            (SELECT wm.batch_size 
             FROM Work_order_materials wm 
             WHERE wm.workorder_no = a.work_order_no 
             LIMIT 1)
        ELSE
            b.batch_size
    END AS batch_size,

    CASE 
        WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
            (SELECT p.product_type 
             FROM Work_order_materials wm 
             LEFT JOIN order_materials om ON wm.order_no = om.order_no
             LEFT JOIN product p ON om.product_code = p.product_code AND wm.plant_id = p.plant_id
             WHERE wm.workorder_no = a.work_order_no 
             LIMIT 1)
        ELSE
            p.product_type
    END AS product_type,

    p.dosage_form,
    b.plan_no,
    (SELECT CONCAT(IFNULL(firstname, ''), ' ', IFNULL(middlename, ''), ' ', IFNULL(lastname, ''))
     FROM employee 
     WHERE emp_id = a.entry_by
    ) AS entry_by_name

FROM mfg_work_order_hdr a 
LEFT JOIN batch_planning b 
    ON a.batch_plan_id = b.id 
   AND a.plant_id = b.plant_id

LEFT JOIN product p 
    ON (
        CASE 
            WHEN a.batch_plan_id IS NULL OR a.batch_plan_id = '' OR a.batch_plan_id = '0' THEN
                (SELECT om.product_code 
                 FROM Work_order_materials wm 
                 LEFT JOIN order_materials om ON wm.order_no = om.order_no
                 WHERE wm.workorder_no = a.work_order_no 
                 LIMIT 1)
            ELSE
                b.product_code
        END
    ) = p.product_code 
   AND a.plant_id = p.plant_id

WHERE a.plant_id = '".$_GET["plant_id"]."' 
  AND (qa_person IS NOT NULL AND qa_person != '')
  AND a.material_type = '".$material_type."'
ORDER BY a.id DESC";

        
        $result = $conn->query($sql);
        $output = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Set plan_no for canplan work orders (if not set)
                if (empty($row['plan_no'])) {
                    $row['plan_no'] = $row['work_order_no'] ?? '';
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }



}

$conn->close();
?>