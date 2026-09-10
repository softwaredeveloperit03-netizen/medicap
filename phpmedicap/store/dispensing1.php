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

    if ($_GET["type"] == "getStoreEmployees") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE department='Store'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDispensingRequests") {
        $output = array();
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.user_no='".$_GET["user_no"]."' AND d.dispensing_for='BMR' AND d.status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
               $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,.p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_store_status as store_status,a.rm_store_status_entry_by as store_status_entry_by,a.rm_store_status_entry_date as store_status_entry_date ";
             }else{
                $sql = $sql." a.pm_store_status as store_status,a.pm_store_status_entry_by as store_status_entry_by,a.pm_store_status_entry_date as store_status_entry_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." and rm_store_status_entry_by='' ";
            }else{
                $sql = $sql." and pm_store_status_entry_by='' ";
            }
             
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id join master_material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getDispensingAcceptedRequests") {
        $output = array();
        //$sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='pending' GROUP BY d.product_code";
        
         $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.entry_by as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,.p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,";
             if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." a.rm_store_lc_status as store_lc_status,a.rm_store_status as store_status,a.rm_store_status_entry_by as store_status_entry_by,a.rm_store_status_entry_date as store_status_entry_date ";
             }else{
                $sql = $sql." a.pm_store_lc_status as store_lc_status,a.pm_store_status as store_status,a.pm_store_status_entry_by as store_status_entry_by,a.pm_store_status_entry_date as store_status_entry_date ";
             }
             
             
            $sql = $sql."FROM mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   "; 
            if($_GET["material_type"] == 'Raw Material'){
                $sql = $sql." and rm_store_status_entry_by!='' and rm_store_lc_status='Pending' ";
            }else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_store_lc_status='Pending' ";
            }
            
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id join master_material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_Dispensing_Requests_For_Inprocess_Activity") {
        $output = array();
        //$sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='pending' GROUP BY d.product_code";
        
         $sql ="SELECT a.id,a.batch_number,a.work_order_no,a.rm_qa_dislc_date as palnned_by,a.approved_by,
             a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
             a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,b.batch_size, p.product_name,.p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, a.dispense_request_sent_on,a.dispensing_status,";
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
                $sql = $sql." and rm_store_status_entry_by!='' and rm_qa_dislc_status='Approved' ";
            }else{
                $sql = $sql." and pm_store_status_entry_by!='' and pm_qa_dislc_status='Approved' ";
            }
            
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                $output1 = array();
                $sql1 = "select a.*,b.avbl_stock from (SELECT a.*,b.material_type,b.material_subtype,b.material_name
                 FROM mfg_work_order_dtl a join mfg_work_order_hdr c on a.work_order_id = c.id join master_material b
                 on a.material_code = b.material_code and c.plant_id = b.plant_id where a.work_order_id = '".$row["id"]."') as a join
                 (SELECT material_code,sum(qty) as avbl_stock FROM stock_book
                 WHERE material_code in(SELECT material_code from mfg_work_order_dtl where work_order_id ='".$row["id"]."') 
                 GROUP by material_code) as b on a.material_code = b.material_code and a.material_type='".$_GET["material_type"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        echo json_encode($output);
      } else if ($_GET["type"] == "getHoldRequests") {
        $output = array();
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.user_no='".$_GET["user_no"]."' AND d.status='On Hold' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRejectedRequests") {
        $output = array();
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.user_no='".$_GET["user_no"]."' AND d.status='Reject' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveRequest") {
        $sql = "UPDATE dispensing SET status='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAcceptedRequests") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='Accept' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM equipment WHERE equipment_name='RLAF' AND department='Store' AND location='Dispensing'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $row["isrlaf"] = "yes";
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["rlaf_id"] = $row1["equipment_code"];
                    }
                } else {
                    $row["isrlaf"] = "no";
                }
                
                $flag = 0;
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                        $output1[] = $row1;
                    }
                }
                if ($flag == 0) {
                    $row["dispensing_status"] = "done";
                } else {
                    $row["dispensing_status"] = "pending";
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPackingAcceptedRequests") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='Accept' AND d.dispensing_for='PACKING' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = array();
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as qty, ar_no FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $received_qty = 0;
                                $issued_qty = 0;
                                $balance_qty = 0;
                                
                                $received_qty = $row2["qty"];
                                
                                $sql3 = "SELECT IFNULL(SUM(qty), 0) as qty FROM material_issue WHERE ar_no='".$row2["ar_no"]."'";
                                $result3 = $conn->query($sql3);
                                if ($result3->num_rows > 0) {
                                    while ($row3 = $result3->fetch_assoc()) {
                                        $issued_qty = $row3["qty"];
                                    }
                                }
                                $balance_qty = $received_qty - $issued_qty;
                                $row2["received_qty"] = $received_qty;
                                $row2["issue_qty"] = $issued_qty;
                                $row2["balance_qty"] = $balance_qty;
                                $output2[] = $row2;
                            }
                        }
                        $row1["available_ars"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDispensingForm") {
        $sql = "UPDATE dispensing_materials SET status='done', dispensing_room='".$input["dispensing_room"]."',rlaf_start='".$input["rlaf_start"]."',pressure_reading='".$input["pressure_reading"]."',containers='".json_encode($input['containers'])."', ars='".json_encode($input["ars"])."', done_by='".$input["done_by"]."', check_by='".$_GET["emp_id"]."', entry_date='$entry_date', gross_wt='".$input["gross_total"]."', tare_wt='".$input["tare_total"]."', net_wt='".$input["net_total"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            $ars = $input["ars"];
            for ($i = 0; $i < count($ars); $i++) {
                $ar = $ars[$i];
                $sql1 = "INSERT INTO material_issue (ar_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date) VALUES ('".$ar["ar_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["product_code"]."', '".$input["prod_batch_code"]."', 'DISPENSING', '".$ar["qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql1);
            }
            
            $sql = "SELECT id FROM dispensing_materials WHERE status='pending' AND dispensing_no='".$_GET["dispensing_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
                $sql = "UPDATE dispensing SET status='Active' WHERE id='".$_GET["dispensing_no"]."'";
                $conn->query($sql);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "savePackingDispensingForm") {
        $sql = "UPDATE dispensing SET status='Active' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Packing Material Dispensing completed!"));
            
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql = "UPDATE dispensing_materials SET ar_no='".$material["ar_no"]."', issue_qty='".$material["issue_qty"]."', done_by='".$material["done_by"]."' WHERE id='".$material["id"]."'";
                $conn->query($sql);
                
                $sql1 = "INSERT INTO material_issue (ar_no, material_code, batch_no, product_code, prod_batch_code, issue_for, qty, unit, entry_by, entry_date, dispensing_room) VALUES ('".$material["ar_no"]."', '".$material["material_code"]."', '".$material["batch_no"]."', '".$input["product_code"]."', '".$input["batch_code"]."', 'DISPENSING', '".$material["issue_qty"]."', '".$material["unit"]."', '".$_GET["emp_id"]."', '$entry_date', '".$material["dispensing_room"]."')";
                $conn->query($sql1);
            }
        } else {
            echo json_encode(array("status"=>"success","msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getActiveDispensing") {
        $output = array();
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.status='Active' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $flag = 0;
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."' AND m.material_type='Raw Material' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDispensingActivity") {
        $sql = "UPDATE dispensing SET complete_date='$entry_date', dispensing_date='".$input["dispensing_date"]."', dispensing_from='".$input["dispensing_from"]."', dispensing_to='".$input["dispensing_to"]."', lineclearance_by='".$input["lineclearance_by"]."', production_person='".$input["production_person"]."', status='complete', cleaning_from='".$input["cleaning_from"]."', cleaning_to='".$input["cleaning_to"]."', cleaned_by='".$input["cleaned_by"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingLog") {
        $output = array();
        $sql = "SELECT d.*,e.firstname,e1.firstname as clean,e2.firstname as checked, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id LEFT JOIN employee e1 ON d.cleaned_by=e1.emp_id LEFT JOIN employee e2 ON d.receive_by=e2.emp_id WHERE d.status='complete' AND p.product_type LIKE '%".$_GET["product_type"]."%' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ars = array();
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        
                        $temp = $row1["ars"];
                        for ($i = 0; $i < count($temp); $i++) {
                            $data = $temp[$i];
                            $data->material_name = $row1["material_name"];
                            $ars[] = $data;
                        }
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $row["ars"] = $ars;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPackingDispensingLog") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.dispensing_for='PACKING' AND d.status='Active' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ars = array();
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadDispensingLog") {
        $_GET['formatno'] = 'Format No:'; $_GET['pdftype'] = 'topheader'; include("../pdfimp.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Dispensing Log</h3>
        <h3>Format No:</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:center;">Sr.</td>
                    <td style="width: 11%; text-align:center;">Date</td>
                    <td style="width: 14%; text-align:center;">Document No</td>
                    <td style="width: 10%; text-align:center;">Dispensing For</td>
                    <td style="width: 15%; text-align:center;">Product Type</td>
                    <td style="width: 15%; text-align:center;">Product Code</td>
                    <td style="width: 15%; text-align:center;">Product Name</td>
                    <td style="width: 15%; text-align:center;">Request By</td>
                </tr>
            </thead>';
            $output = array();
                $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade, e.firstname as request FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete'  ORDER BY id DESC";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                    
                $html.='<tr nobr="true">
                        <td style="width: 5%; text-align:center;">'.$i.'.</td>
                        <td style="width: 11%; text-align:center;">'.date('d-m-Y',strtotime($row['request_date'])).'</td>
                        <td style="width: 14%; text-align:center;">'.$row['document_no'].'</td>
                        <td style="width: 10%; text-align:center;">'.$row['dispensing_for'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['product_type'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['product_code'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['product_name'].'</td>
                        <td style="width: 15%; text-align:center;">'.$row['request'].'('.$row['request_by'].')</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadMaterialRequisitionSlip") {
        $_GET['formatno'] = ''; $_GET['pdftype'] = 'topheader'; include("../pdfimp.php");
        $html= "";
    
        $html.='<h3 style="text-align:center;">MATERIAL REQUISITION SLIP</h3>
        <h3>Format No:WH012/F/01-02</h3>';
        $output = array();
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.status='Active' AND d.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $flag = 0;
                $output1 = array();
                $ar_no="";
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."' AND m.material_type='Raw Material' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        $ars=$row1["ars"];
                        $ar_no=$ars->ar_no;
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            $html.='
            <table cellpadding="3" border="1" >
                <tr>
                    <td style="width:10%; "><b>From:</b></td>
                    <td style="width:25%; "></td>
                    <td style="width:10%; "><b>Date:</b></td>
                    <td style="width:20%; ">'.date('d-m-Y',strtotime($row["dispensing_date"])).'</td>
                    <td style="width:15%; "><b>Raised By:</b></td>
                    <td style="width:20%; "></td>
                </tr>
                <tr>
                    <td style="width:25%; "><b>Name of the Product:</b></td>
                    <td style="width:35%; ">'.$row["product_name"].'</td>
                    <td style="width:15%; "><b>Batch No.</b></td>
                    <td style="width:25%; ">'.$row["batch_no"].'</td>
                </tr>
                <tr>
                    <td style="width:5%; "><b>Sr. No.</b></td>
                    <td style="width:25%; "><b>Description</b></td>
                    <td style="width:10%; "><b>A.R.No.</b></td>
                    <td style="width:15%; "><b>Required Quantity Kg./Lit./Nos.</b></td>
                    <td style="width:15%; "><b>Issued Quantity Kg./Lit./Nos.</b></td>
                    <td style="width:15%; "><b>No. of Container & Quantity</b></td>
                    <td style="width:15%; "><b>Ledger Folio No.</b></td>
                </tr>';
                $i=1;
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["ars"] = json_decode($row1["ars"]);
                        $ars=$row1["ars"];
                        for($j=0;$j<count($ars);$j++){
                            $ar_no=$ars[$j];
                        // $ar_no->ar_no;
                        $html.='
                        <tr>
                            <td style="width:5%; ">'.$i++.'</td>
                            <td style="width:25%; ">'.$row1['material_name'].'</td>
                            <td style="width:10%; ">'.$ar_no->ar_no.'</td>
                            <td style="width:15%; "></td>
                            <td style="width:15%; ">'.$row1["qty"].''.$row1["unit"].'</td>
                            <td style="width:15%; ">'.Count($row1["containers"]).'</td>
                            <td style="width:15%; "></td>
                        </tr>';
                        }
                    }
                }
                $html.='
                <tr>
                    <td style="width:100%; "><b>Note:</b>container,Weight and surrounding area of the balance should be cleaned before and after weighing. </td>
                </tr>
                <tr>
                    <td style="width:10%; "><b>Remark:</b></td>
                    <td style="width:90%; "></td>
                </tr>
                <tr>
                    <td style="width:10%; "><b>Weight By:</b></td>
                    <td style="width:15%; ">'.$row["firstname"].'('.$row["request_by"].')</td>
                    <td style="width:15%; "><b>Checked and Issued By:</b></td>
                    <td style="width:13%; ">'.$row1["check_by"].'</td>
                    <td style="width:7%;  "><b>Date:</b></td>
                    <td style="width:15%; ">'.date('d-m-Y',strtotime($row1["entry_date"])).'</td>
                    <td style="width:10%; "><b>Received By:</b></td>
                    <td style="width:15%; "></td>
                </tr>
            </table>';
            }
        }   
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadPackingDispensingLog") {
        $_GET['formatno'] = 'Format No:WH013/F/01-02'; $_GET['pdftype'] = 'topheader'; include("../pdfimp.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Dispensing Log</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; text-align:center;">Sr.</td>
                    <td style="width: 11%; text-align:center;">Date</td>
                    <td style="width: 14%; text-align:center;">Document No</td>
                    <td style="width: 10%; text-align:center;">Dispensing For</td>
                    <td style="width: 15%; text-align:center;">Product Type</td>
                    <td style="width: 15%; text-align:center;">Product Code</td>
                    <td style="width: 15%; text-align:center;">Product Name</td>
                    <td style="width: 15%; text-align:center;">Request By</td>
                </tr>
            </thead>';
            $output = array();
            $sql = "SELECT d.*, e.firstname,DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.dispensing_for='PACKING' AND d.status='Active' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $ars = array();
                    $output1 = array();
                    $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                        $html.='<tr nobr="true">
                                <td style="width: 5%; text-align:center;">'.$i.'.</td>
                                <td style="width: 11%; text-align:center;">'.date('d-m-Y',strtotime($row['request_date'])).'</td>
                                <td style="width: 14%; text-align:center;">'.$row['document_no'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row['dispensing_for'].'</td>
                                <td style="width: 15%; text-align:center;">'.$row['product_type'].'</td>
                                <td style="width: 15%; text-align:center;">'.$row['product_code'].'</td>
                                <td style="width: 15%; text-align:center;">'.$row['product_name'].'</td>
                                <td style="width: 15%; text-align:center;">'.$row['firstname'].'('.$row['request_by'].')</td>
                            </tr>';
                        $i++;
                        }
                    }
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadDispensingRecord") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'topheader'; include("../pdfimp.php");
        $html= "";
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete'  ORDER BY id DESC";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
         while ($row = $result->fetch_assoc()) {
            
         $html.='<h3 style="text-align:center;">Dispensing Record</h3>
                <h3>Format No:</h3>
                <h2>Product Details:</h2>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;">Product Code</td>
                        <td style="width:25%;">'.$row['product_code'].'</td>
                        <td style="width:25%;">Product Name</td>
                        <td style="width:25%;">'.$row['product_name'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Product Type</td>
                        <td style="width:25%;">'.$row['product_type'].'</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;"></td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Grade</td>
                        <td style="width:25%;">'.$row['grade'].'</td>
                        <td style="width:25%;">Request Date</td>
                        <td style="width:25%;">'.date('d-m-Y',strtotime($row['request_date'])).'</td>
                    </tr>';
        $html.="</table>";
        
        $html.='<h2>Material Details</h2>
                <table border="1" cellpadding="5">
                    <thead>
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width: 5%; text-align:center;">Sr.</td>
                            <td style="width: 10%; text-align:center;">Dispensing No</td>
                            <td style="width: 10%; text-align:center;">Material Subtype</td>
                            <td style="width: 10%; text-align:center;">Material Code</td>
                            <td style="width: 14%; text-align:center;">Material Name</td>
                            <td style="width: 10%; text-align:center;">Grade</td>
                            <td style="width: 10%; text-align:center;">Qty</td>
                            <td style="width: 10%; text-align:center;">Overages</td>
                            <td style="width: 7%; text-align:center;">Gross Wt</td>
                            <td style="width: 7%; text-align:center;">Tare Wt</td>
                            <td style="width: 7%; text-align:center;">Net Wt</td>
                        </tr>
                    </thead>';
                    $output1 = array();
                    $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                         
                        
                    $html.='<tr nobr="true">
                                <td style="width: 5%; text-align:center;">'.$i.'.</td>
                                <td style="width: 10%; text-align:center;">'.$row1['dispensing_no'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['material_subtype'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['material_code'].'</td>
                                <td style="width: 14%; text-align:center;">'.$row1['material_name'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['grade'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['qty'].''.$row1['unit'].'</td>
                                <td style="width: 10%; text-align:center;">'.$row1['overages'].''.$row1['unit'].'</td>
                                <td style="width: 7%; text-align:center;">'.$row1['gross_wt'].''.$row1['unit'].'</td>
                                <td style="width: 7%; text-align:center;">'.$row1['tare_wt'].''.$row1['unit'].'</td>
                                <td style="width: 7%; text-align:center;">'.$row1['net_wt'].''.$row1['unit'].'</td>
                            </tr>';
                            $i++;
                        }
                    }
            $html.="</table><br><br>";
            
            $html.='<table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;">Balance ID</td>
                        <td style="width:25%;">'.$row['balance_id'].'</td>
                        <td style="width:25%;">RLAF ID</td>
                        <td style="width:25%;">'.$row['laf_id'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">RLAF Start Time</td>
                        <td style="width:25%;">'.$row['laf_start_time'].'</td>
                        <td style="width:25%;">RLAF Stop Time</td>
                        <td style="width:25%;">'.$row['laf_stop_time'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;">Pressure Differential Reading</td>
                        <td style="width:75%;">'.$row['pressure'].'</td>
                    </tr>
                        ';
            $html.="</table>";
    
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Dispensing Record.pdf', 'I');
            
           }
        }
    }
    else if ($_GET["type"] == "downloadDispensingReport") {
        $_GET['formatno'] = 'Format No:WH013/F/01-02'; $_GET['pdftype'] = 'topheader-landscape'; include("../pdfimp.php");
        $html= '
        <h3 style="text-align: center;">Raw Material Dispensing Report</h3>';

        $html.='<table border="1" cellpadding="2">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td  rowspan="2" style="width: 8%;">Date</td>
                    <td  rowspan="2" style="width: 5%;">RLRF Start Time</td>
                    <td  rowspan="2" style="width: 6%;">Diff. Pre.</td>
                    <td  rowspan="2" style="width: 10%;">Product Name</td>
                    <td  rowspan="2" style="width: 10%;">Batch No</td>
                    <td  rowspan="2" style="width: 11%;">Material Name</td>
                    <td  rowspan="2" style="width: 8%;">AR.No</td>
                    <td style="width: 10%;">Dispensing Time</td>
                    <td  rowspan="2" style="width: 7%;">Dispensed By</td>
                    <td style="width: 10%;">Cleaning Time</td>
                    <td  rowspan="2" style="width: 7%;">Cleaned By</td>
                    <td rowspan="2"  style="width: 7%;">Checked By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">From</td>
                    <td style="width:5%;">To</td>
                    <td style="width:5%;">From</td>
                    <td style="width:5%;">To</td>
                </tr>
            </thead>';
        $output = array();
        $sql = "SELECT d.*, e.firstname as request_by,e1.firstname as cleaned,e1.firstname as receive,DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id LEFT JOIN employee e1 ON d.cleaned_by=e1.emp_id LEFT JOIN employee e2 ON d.receive_by=e2.emp_id WHERE d.dispensing_for='BMR' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ars = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["ars"] = json_decode($row1["ars"]);
                        $temp = $row1["ars"];
                        for ($i = 0; $i < count($temp); $i++) {
                            $data = $temp[$i];
                            $data->material_name = $row1["material_name"];
                            $ars[] = $data;
                        }
                    }
                }
                $total_ar = count($ars);
                $html.='<tr>
                    <td style="width: 8%;" rowspan="'.$total_ar.'">'.date('d-m-Y', strtotime($row["dispensing_date"])).'</td>
                    <td style="width: 5%;" rowspan="'.$total_ar.'">'.date('H:i',strtotime($row['laf_start_time'])).'</td>
                    <td style="width: 6%;" rowspan="'.$total_ar.'">'.$row['pressure'].'</td>
                    <td style="width: 10%;"rowspan="'.$total_ar.'">'.$row['product_name'].'</td>
                    <td style="width: 10%;" rowspan="'.$total_ar.'">'.$row['batch_no'].'</td>';
                
                $html.='<td style="width: 11%;">'.$ars[0]->material_name.'</td><td style="width: 8%;">'.$ars[0]->ar_no.'</td>';
                $html.='<td style="width: 5%;" rowspan="'.$total_ar.'">'.date('H:i',strtotime($row['dispensing_from'])).'</td>
                    <td style="width: 5%;" rowspan="'.$total_ar.'">'.date('H:i',strtotime($row['dispensing_to'])).'</td>
                    <td style="width: 7%;" rowspan="'.$total_ar.'">'.$row['request_by'].'</td>
                    <td style="width: 5%;" rowspan="'.$total_ar.'">'.date('H:i',strtotime($row['cleaning_from'])).'</td>
                    <td style="width: 5%;" rowspan="'.$total_ar.'">'.date('H:i',strtotime($row['cleaning_to'])).'</td>
                    <td style="width: 7%;" rowspan="'.$total_ar.'">'.$row['cleaned'].'('.$row['cleaned_by'].')</td>
                    <td style="width: 7%;" rowspan="'.$total_ar.'">'.$row['receive'].'('.$row['receive_by'].')</td>
                </tr>';
                
                for ($i = 1; $i < count($ars); $i++) {
                    $ar = $ars[$i];
                    $html.='<tr><td style="width: 11%;">'.$ar->material_name.'</td><td style="width: 8%;">'.$ar->ar_no.'</td></tr>';
                }
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing Report.pdf', 'I');
    } else if ($_GET["type"] == "downloadReport") {
        require_once "../PHPExcel/Classes/PHPExcel.php";
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $rowCount = 1;
        // $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount,Date);
        // $objPHPEXcel->getActiveSheet()->SetCellValue('B'.$rowCount,RLRF_Start_Time);
        // $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount,Differential_Pressure);
        // $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount,Product_Name);
        // $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount,Batch_NO);
        // $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount,Material_Name);
        // $objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount,ar_no);
        // $objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount,Dispensing_Time);
        // $objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount,Dispensed_By);
        // $objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount,Cleaning_Time);
        // $objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount,Cleaned_by);
        // $objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount,Checked_by);
        
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='complete' AND p.product_type LIKE '%".$_GET["product_type"]."%' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ars = array();
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["containers"] = json_decode($row1["containers"]);
                        $row1["ars"] = json_decode($row1["ars"]);
                        
                        $temp = $row1["ars"];
                        for ($i = 0; $i < count($temp); $i++) {
                            $data = $temp[$i];
                            $data->material_name = $row1["material_name"];
                            $ars[] = $data;
                        }
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $row["ars"] = $ars;
                
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row["request_date"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row["laf_start_time"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $row["pressure"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $row["product_name"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $row["batch_no"]);
                $ars = $row["ars"];
                for ($i = 0; $i < count($ars); $i++) {
                    $ar = $ars[$i];
                    $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $ar->material_name);
                    $objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $ar->ar_no);
                    $rowCount++;
                }
                // $rowCount++;
                $objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $row["dispensing_time"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $row["dispensed_by"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, $row["cleaning_time"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, $row["cleaned_by"]);
                $objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, $row["Checked_by"]);
                
            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        // $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        
        // We'll be outputting an excel file
        header('Content-type: application/vnd.ms-excel');
        
        // It will be called file.xls
        header('Content-Disposition: attachment; filename="file.xls"');
        
        // Write file to the browser
        $objWriter->save('php://output');
        // $objWriter->save('some_excel_file.xlsx');
    } else if ($_GET["type"] == "getAHURecords") {
        $output = array();
        $sql = "SELECT *FROM equipment WHERE equipment_name='Air Handling Unit' AND department='Store' AND equipment_code NOT IN (SELECT equipment_code FROM ahu_usages WHERE entry_date=CURDATE() AND status !='STOP')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["status"] = 'PENDING';
                $row["entry_date"] = date("Y-m-d", $timestamp);
                $row["start_time"] = "";
                $row["stop_time"] = "";
                $row["start_by"] = "";
                $row["stop_by"] = "";
                $output[] = $row;
            }
        }
        $sql = "SELECT a.*,e.firstname,e1.firstname as stop_name FROM ahu_usages a LEFT JOIN employee e ON a.start_by=e.emp_id LEFT JOIN employee e1 ON a.stop_by=e1.emp_id   WHERE a.department='Store' AND DATE(a.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startAHU") {
        $start_time = date("H:i:s", $timestamp);
        $sql = "INSERT INTO ahu_usages (department, section, equipment_code, entry_date, status, start_time, start_by, corridor, sampling, dispensing) VALUES ('".$input["department"]."', '".$input["location"]."', '".$input["equipment_code"]."', '".$entry_date."', 'START', '".$input["start_time"]."', '".$_GET["emp_id"]."', '".$input["corridor"]."', '".$input["sampling"]."', '".$input["dispensing"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveAHUPressure") {
        $start_time = date("H:i:s", $timestamp);
        $sql = "UPDATE ahu_usages SET status='INPROCESS', corridor='".$input["corridor"]."', sampling='".$input["sampling"]."', dispensing='".$input["dispensing"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "stopAHU") {
        $stop_time = date("H:i:s", $timestamp);
        $sql = "UPDATE ahu_usages SET stop_time='".$_GET["stop_time"]."', stop_by='".$_GET["emp_id"]."', status='STOP' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "startRLAF") {
        $sql = "UPDATE dispensing SET status='RLAF STARTED', dispensing_date='$entry_date', laf_id='".$_GET["laf_id"]."', laf_pressure='".$_GET["laf_pressure"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getStartedDispensing") {
        $output = array();
        $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.status='RLAF STARTED' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $flag = 0;
                $output1 = array();
                $sql1 = "SELECT d.*,e.firstname, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code LEFT JOIN employee e ON d.done_by=e.emp_id WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = array();
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as qty, ar_no, pack_size, tare_wt FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $received_qty = 0;
                                $issued_qty = 0;
                                $balance_qty = 0;
                                
                                $received_qty = $row2["qty"];
                                $balance_qty = $received_qty - $issued_qty;
                                $row2["received_qty"] = $received_qty;
                                $row2["issue_qty"] = $issued_qty;
                                $row2["balance_qty"] = $balance_qty;
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
                if ($flag == 0) {
                    $row["dispensing_status"] = "done";
                } else {
                    $row["dispensing_status"] = "pending";
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="downloadAHURecords"){
        $_GET['formatno'] = 'Format No:'; $_GET['pdftype'] = 'topheader'; include("../pdfimp.php");
        $html= "";
        $html.='<h3 style="text-align:center;">Air Handling Unit</h3>
                <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:12%;">Date</td>
                    <td style="width:16%;">AHUR No</td>
                    <td style="width:12%;">Start Pressure Reading</td>
                    <td style="width:12%;">Stop Pressure Reading</td>
                    <td style="width:11%;">Start Time</td>
                    <td style="width:10%;">Stop Time</td>
                    <td style="width:11%;">Start By</td>
                    <td style="width:11%;">Stop By</td>
                </tr>';
        $output = array();
         $sql = "SELECT a.*,e.firstname ,e1.firstname as stop_by FROM equipment a LEFT JOIN employee e ON a.start_by=e.emp_id LEFT JOIN employee e1 ON a.stop_by=e1.emp_id  WHERE a.equipment_name='Air Handling Unit' AND a.department='Store' AND a.equipment_code NOT IN (SELECT equipment_code FROM ahu_usages WHERE entry_date=CURDATE() AND status !='STOP')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["status"] = 'PENDING';
                // $row["entry_date"] = date("Y-m-d", $timestamp);
                // $row["start_time"] = "";
                // $row["stop_time"] = "";
                // $row["start_by"] = "";
                // $row["stop_by"] = "";
                $output[] = $row;
            }
        }
        $i=1;
        $sql = "SELECT * FROM ahu_usages WHERE department='Store' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $start_by = '';
                $sql1 = "SELECT firstname FROM employee WHERE emp_id='".$row['start_by']."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $start_by = $row1["firstname"];
                    }
                }
                 $stop_by = '';
                $sql1 = "SELECT firstname FROM employee WHERE emp_id='".$row['stop_by']."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $stop_by = $row1["firstname"];
                    }
                }
                $output[] = $row;
        $html.=' <tr>
                    <td style="width:5%;">'.$i++.'</td>
                    <td style="width:12%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                    <td style="width:16%;">'.$row['equipment_code'].'</td>
                    <td style="width:12%;">'.$row['start_pressure'].'</td>
                    <td style="width:12%;">'.$row['stop_pressure'].'</td>
                    <td style="width:11%;">'.date('H:i',strtotime($row['start_time'])).'</td>
                    <td style="width:10%;">'.date('H:I',strtotime($row['stop_time'])).'</td>
                    <td style="width:11%;">'.$start_by.'('.$row['start_by'].')</td>
                    <td style="width:11%;">'.$stop_by.'('.$row['stop_by'].')</td>
                </tr>';
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html,true,false,false,false,'');
        $pdf->Output('AHURecords','I');
    }else if ($_GET["type"] == "printLabel") {
	    $grn_no = "GRN-".$_GET["id"];
	    $sql = "SELECT d.*,e.firstname, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.id='".$_GET['id']."'";
        $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
                // $flag = 0;
            // $output1 = array();
            $sql1 = "SELECT d.*,e.firstname,m.material_code, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code LEFT JOIN employee e ON d.done_by=e.emp_id WHERE d.dispensing_no='".$_GET["id"]."' AND m.material_code='".$_GET["material_code"]."' GROUP BY d.dispensing_no";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                        // $output2 = array();
                        // $sql2 = "SELECT IFNULL(SUM(qty), 0) as qty, ar_no, pack_size, tare_wt FROM stock_book WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no";
                        // $result2 = $conn->query($sql2);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {
                        //         $received_qty = 0;
                        //         $issued_qty = 0;
                        //         $balance_qty = 0;
                                
                        //         $received_qty = $row2["qty"];
                        //         $balance_qty = $received_qty - $issued_qty;
                        //         $row2["received_qty"] = $received_qty;
                        //         $row2["issue_qty"] = $issued_qty;
                        //         $row2["balance_qty"] = $balance_qty;
                        //         $output2[] = $row2;
	       // require '../tcpdf/tcpdf.php';
            class MYPDF extends TCPDF {
                public function Header() {}
                public function Footer() {}
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(10, 10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            for ($i=1; $i <= count($row1['containers']); $i++) {
                if($i == +count($row1['containers']) && $i % 2 !== 0){
                    $html.='&nbsp;<br>
                            <table cellpadding="-5" style="width:100%;">
                                <tr>
                                    <td style="width:49%;">
                                        <table border="1" cellpadding="2" nobr="true">
                                            <tr style="background-color:#0076BE;">
                                                <td style="width:100%; text-align:center; font-weight:bold; font-size:12px;">Dispensing</td>
                                            </tr>
                                            <tr>
                                                <td style="width:100%;">
                                                    <table>
                                                        <tr>
                                                            <td style="width:30%;"><b>Material Name </b></td>
                                                            <td style="width:70%;">:'.$row1['material_name'].'</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:30%;"><b>Batch No</b></td>
                                                            <td style="width:30%;">:'.$row['batch_no'].'</td>
                                                            <td style="width:15%;"><b>Grade</b></td>
                                                            <td style="width:25%;">:'.$row1['grade'].'</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:34%;"><b>Amt:</b>'.$row1['gross_wt'].'</td>
                                                            <td style="width:33%;"><b>Tare.wt:</b>'.$row1['tare_wt'].''.$row1['unit'].'</td>
                                                            <td style="width:33%;"><b>Net.wt :</b>'.$row1['net_wt'].''.$row1['unit'].'</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:25%;"><b>Dispens By</b></td>
                                                            <td style="width:25%;">:'.$row['request_by'].'</td>
                                                            <td style="width:25%;"><b>Dispens Dt:</b></td>
                                                            <td style="width:25%;">'.date('d-m-Y',strtotime($row['dispensing_date'])).'</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:25%;"><b>Check By:</b></td>
                                                            <td style="width:25%;">'.$row1['firstname'].'('.$row1['done_by'].')</td>
                                                            <td style="width:25%;"><b>Receipt Dt:</b></td>
                                                            <td style="width:25%;">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width:20%;"><img src="../upload/user/bajaj1.png" style="width: 250px; height: 200px;"></td>
                                                <td style="width:80%;">
                                                    <table>
                                                        <tr>
                                                            <td style="width:100%;text-align:center;font-weight:bold;">BAJAJ HEALTHCARE LTD</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:100%;text-align:center;font-weight:bold;">SAVLI. UNIT-II</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:100%;text-align:center;font-weight:bold;">FORMAT NO:</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="width:4%;"></td>
                                    <td style="width:49%;"></td>
                                </tr>
                            </table>';
                            }else{
                                $html.='&nbsp;<br>
                                <table cellpadding="-5" style="width:100%;">
                                    <tr>
                                        <td style="width:49%;">
                                            <table border="1" cellpadding="2" nobr="true">
                                                <tr style="background-color:#0076BE;">
                                                    <td style="width:100%; text-align:center; font-weight:bold; font-size:12px;">Dispensing</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;">
                                                        <table>
                                                            <tr>
                                                                <td style="width:30%;"><b>Material Name </b></td>
                                                                <td style="width:70%;">:'.$row1['material_name'].'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:30%;"><b>Batch No</b></td>
                                                                <td style="width:30%;">:'.$row['batch_no'].'</td>
                                                                <td style="width:15%;"><b>Grade</b></td>
                                                                <td style="width:25%;">:'.$row1['grade'].''.$unit.'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:34%;"><b>Amt:</b>'.$row1['gross_wt'].'</td>
                                                                <td style="width:33%;"><b>Tare.wt:</b>'.$row1['tare_wt'].''.$row1['unit'].'</td>
                                                                <td style="width:33%;"><b>Net.wt :</b>'.$row1['net_wt'].''.$row1['unit'].'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:25%;"><b>Dispens By</b></td>
                                                                <td style="width:25%;">:'.$row['request_by'].'</td>
                                                                <td style="width:25%;"><b>Dispens Dt:</b></td>
                                                                <td style="width:25%;">'.date('d-m-Y',strtotime($row['dispensing_date'])).'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:25%;"><b>Check By:</b></td>
                                                                <td style="width:25%;">'.$row1['firstname'].'('.$row1['done_by'].')</td>
                                                                <td style="width:25%;"><b>Receipt Dt:</b></td>
                                                                <td style="width:25%;">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="width:20%;"><img src="../upload/user/bajaj1.png" style="width: 250px; height: 200px;"></td>
                                                    <td style="width:80%;">
                                                        <table>
                                                            <tr>
                                                                <td style="width:100%;text-align:center;font-weight:bold;">BAJAJ HEALTHCARE LTD</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:100%;text-align:center;font-weight:bold;">SAVLI. UNIT-II</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:100%;text-align:center;font-weight:bold;">FORMAT NO:</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width:2%;"></td>
                                        <td style="width:49%;">
                                            <table border="1" cellpadding="2" nobr="true">
                                                <tr style="background-color:#0076BE;">
                                                    <td style="width:100%; text-align:center; font-weight:bold; font-size:12px;">Dispensing</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:100%;">
                                                        <table>
                                                            <tr>
                                                                <td style="width:30%;"><b>Material Name </b></td>
                                                                <td style="width:70%;">:'.$row1['material_name'].'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:30%;"><b>Batch No</b></td>
                                                                <td style="width:30%;">:'.$row['batch_no'].'</td>
                                                                <td style="width:15%;"><b>Grade</b></td>
                                                                <td style="width:25%;">:'.$row1['grade'].''.$unit.'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:34%;"><b>Amt:</b>'.$row1['gross_wt'].'</td>
                                                                <td style="width:33%;"><b>Tare.wt:</b>'.$row1['tare_wt'].''.$row1['unit'].'</td>
                                                                <td style="width:33%;"><b>Net.wt :</b>'.$row1['net_wt'].''.$row1['unit'].'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:25%;"><b>Dispens By</b></td>
                                                                <td style="width:25%;">:'.$row['request_by'].'</td>
                                                                <td style="width:25%;"><b>Dispens Dt:</b></td>
                                                                <td style="width:25%;">'.date('d-m-Y',strtotime($row['dispensing_date'])).'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:25%;"><b>Check By:</b></td>
                                                                <td style="width:25%;">'.$row1['firstname'].'('.$row1['done_by'].')</td>
                                                                <td style="width:25%;"><b>Receipt Dt:</b></td>
                                                                <td style="width:25%;">'.date('d-m-Y',strtotime($row['complete_date'])).'</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="width:20%;"><img src="../upload/user/bajaj1.png" style="width: 250px; height: 200px;"></td>
                                                    <td style="width:80%;">
                                                        <table>
                                                            <tr>
                                                                <td style="width:100%;text-align:center;font-weight:bold;">BAJAJ HEALTHCARE LTD</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:100%;text-align:center;font-weight:bold;">SAVLI. UNIT-II</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:100%;text-align:center;font-weight:bold;">FORMAT NO:</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>';
                            }
                            $i++;
                        }
                    }
                }
         
            
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    
    }

}

$conn->close();
?>