<?php
    require '../../db.php';
    require '../../token.php';
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
    
    if ($_GET["type"] == "getDispensingRequests") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form, p.label_claim, p.shelf_life FROM packing_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='approve' AND b.dispensing='request' ORDER BY b.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $materials = json_decode($input["materials"]);
                
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    $material->req_qty = round(+$material->batch_qty + ((+$material->overages * +$material->batch_qty) / 100), 2);
                        
                    $required_qty = +$material->req_qty;
                    
                    $sql4 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$material->material_code."' AND status='Approved'";
                    $result4 = $conn->query($sql4);
                    if ($result4->num_rows > 0) {
                        while ($row4 = $result4->fetch_assoc()) {
                            
                            if ($required_qty <= +$row4["qty"]) {
                                $material->avl_qty = $required_qty;
                                $material->short_qty = 0.00;
                                $material->status = "available";
                                
                            } else if (+$row4["qty"] > 0) {
                                $material->avl_qty = +$row4["qty"];
                                $material->short_qty = round($material->req_qty - $material->avl_qty, 2);
                                $material->status = "short";
                                
                            } else if (+$row4["qty"] == 0) {
                                $material->avl_qty = 0.00;
                                $material->short_qty = +$material->req_qty;
                                $material->status = "not available";
                                
                            }
                        }
                    }
                    
                    if ($material->status == "short") {
                        $material->shortage = round($material->req_qty - $material->avl_qty, 2);
                    } else {
                        $material->shortage = round(0, 2);
                    }
                    $output1[] = $row1;
                }
                $row["materials"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "allocatePerson") {
        $input["allocate_by"] =$_GET["emp_id"];
        $input["allocate_date"] = $entry_date;
        $input["dispensing_by"] = $_GET["person"];
        
        $sql = "UPDATE packing_plan SET dispensing_data='".json_encode($input)."', dispensing='allocate' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingDispensing") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM packing_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.dispensing='allocate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $flag = 0;
                $output1 = array();
                $materials = json_decode($row["materials"]);
                
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    
                    $material->req_qty = round(+$material->batch_qty + ((+$material->overages * +$material->batch_qty) / 100), 2);
                        
                    $required_qty = +$material->req_qty;
                    
                    $sql4 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$material->material_code."' AND status='Approved'";
                    $result4 = $conn->query($sql4);
                    if ($result4->num_rows > 0) {
                        while ($row4 = $result4->fetch_assoc()) {
                            
                            if ($required_qty <= +$row4["qty"]) {
                                $material->avl_qty = $required_qty;
                                $material->short_qty = 0.00;
                                $material->status = "available";
                                
                            } else if (+$row4["qty"] > 0) {
                                $material->avl_qty = +$row4["qty"];
                                $material->short_qty = round($material->req_qty - $material->avl_qty, 2);
                                $material->status = "short";
                                
                            } else if (+$row4["qty"] == 0) {
                                $material->avl_qty = 0.00;
                                $material->short_qty = +$material->req_qty;
                                $material->status = "not available";
                                
                            }
                        }
                    }
                    
                    $material->gross_wt = +$material->req_qty;
                    $material->tare_wt = 0;
                    $material->net_wt = +$material->gross_wt;
                    
                    if ($material->status == "short") {
                        $material->shortage = round($material->req_qty - $material->avl_qty, 2);
                    } else {
                        $material->shortage = round(0, 2);
                    }
                    
                    if ($material->status !== 'available') {
                        $flag = 1;
                    }
                    
                    $output1[] = $material;
                }
                $row["materials"] = $output1;
                
                if ($flag == 0) {
                    $row["status"] = "available";
                } else {
                    $row["status"] = "short";
                }
                $row["dispensing_data"] = json_decode($row["dispensing_data"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDispensingForm") {
        $data = $input["dispensing_data"];
        $data["dispensing_date"] = $entry_date;
        $sql = "UPDATE packing_plan SET dispensing='active', dispensing_data='".json_encode($data)."', materials='".json_encode($input["materials"])."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDispensingLog") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form, p.label_claim, p.shelf_life FROM packing_plan b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='approve' AND b.dispensing NOT IN ('pending', 'request') ORDER BY b.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["materials"] = json_decode($row["materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>