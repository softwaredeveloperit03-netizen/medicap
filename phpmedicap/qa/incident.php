<?php
header('Access-Control-Allow-Origin: *');
require '../db.php';
require '../token.php';

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

    if ($_GET["type"] == "saveIncident") {
        $mfg_date = date('M-Y', strtotime($input['mfg_date']));
        $expiry_date = date('M-Y', strtotime($input['exp_date']));
    
        $product = Array();
        if ($input["affecting_product"] == "yes") {
            $product['product_code'] = $input["product_code"];
            $product['batch_no'] = $input["batch_no"];
            $product['mfg_date'] = $input["mfg_date"];
            $product['exp_date'] = $input["exp_date"];
        }
    
        $equipment = Array();
        if ($input["affecting_equipment"] == "yes") {
            $equipment['equipment_name'] = $input["equipment_name"];
        }
    
        $sql = "INSERT INTO incident (user_no, department,related_to,category,type,justification,cause,description,affecting_product,affecting_equipment,product_details, equip_details,entry_by,entry_date) VALUES ('".$_GET["user_no"]."','".$_GET["department"]."','".$input["related_to"]."','".$input["category"]."', '".$input["type"]."', '".$input["justification"]."','".$input["cause"]."','".$input["description"]."','".$input["affecting_product"]."','".$input["affecting_equipment"]."','".json_encode($product)."','".json_encode($equipment)."','".$_GET["emp_id"]."','$entry_date' )";
        
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $data = $input["departments"];
            for ($i = 0; $i < count($data); $i++) {
                $sql = "INSERT INTO incident_comments (inc_no,department) VALUES ('$last_id','".$data[$i]."')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"$conn->error\"}";
        }
    } else if ($_GET["type"] == "getPendingIncidents") {
        $output = Array();
        $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='pending' AND department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_details"] = json_decode($row["product_details"]);
                $output1 = Array();
                $sql1 = "SELECT * FROM incident_comments WHERE inc_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIncident") {
        $sql = "UPDATE incident SET status='".$_GET["status"]."', check_remark='".$_GET["remark"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getInprocessIncidents") {
        $output = Array();
        $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='inprocess' AND department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_details"] = json_decode($row["product_details"]);
                $output1 = Array();
                $sql1 = "SELECT * FROM incident_comments WHERE inc_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "verifyIncident") {
        $sql = "UPDATE incident SET status='".$_GET["status"]."', verify_remark='".$_GET["remark"]."', verify_by='".$_GET["emp_id"]."', verify_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if($_GET["type"] == "getPendingReview") {
        $output = Array();
        $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='active' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);
    
                $sql1 = "SELECT * FROM incident_comments WHERE inc_no='".$row["id"]."' AND department='".$_GET["department"]."' AND status='pending'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["comm_no"] = $row1["id"];
                        $output[] = $row;
                    }
                }
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "saveReview") {
        $sql = "UPDATE incident_comments SET status='active', comment='".$_GET["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $sql1 = "SELECT * FROM incident_comments WHERE status='pending' AND inc_no='".$_GET["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows == 0) {
                $sql2 = "UPDATE incident SET status='checked' WHERE incident_no='".$_GET["incident_no"]."'";
                $conn->query($sql2);
            }
            echo "{\"status\": \"true\"}";
        } else {
            echo "{\"status\": \"false\"}";
        }
    } else if($_GET["type"] == "getCheckedIncident") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='checked' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='checked' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);
    
                $output1 = Array();
                $sql1 = "SELECT * FROM incident_comments WHERE inc_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "saveQAApproval") {
        $capa_no = "";
        if ($input['capa'] == 'yes') {
            $id = 0;
            $sql = "SELECT IFNULL(MAX(id), 0) as id FROM capa";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $id = $row["id"];
                    break;
                }
            }
            $id++;
            $capa_no = "CAPA-".$id;
    
            $sql = "INSERT INTO capa (user_no,capa_no, department, required_to, category, form_no, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','$capa_no', '".$input["department"]."', '".$input["related_to"]."', 'Deviation', '".$input["dev_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
            $conn->query($sql);
        }
        
        $sql = "UPDATE incident SET status='".$input["status"]."', capa='".$input["capa"]."', capa_no='$capa_no', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', approve_remark='".$input["comment"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\": \"true\"}";
        } else {
            echo "{\"status\": \"false\"}";
        }
    } else if($_GET["type"] == "getIncidentsLog") {
        $output = Array();
        $sql = "";
        if ($_GET["department"] == "Quality Assurance" || $_GET["department"] == "Management") {
            $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' AND category LIKE '".$_GET["category"]."%' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        } else {
            $sql = "SELECT *, DATE(entry_date) as entry_date, DATE(close_date) as close_date FROM incident WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND category LIKE '%".$_GET["category"]."%' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);
    
                $output1 = Array();
                $sql1 = "SELECT * FROM incident_comments WHERE inc_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getAllIncident") {
        $output = Array();
        if ($_GET["department"] == "Quality Assurance" || $_GET["department"] == "Management") {
            $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' AND category LIKE '".$_GET["category"]."%' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        } else if($_GET["category"] !== '') {
            $sql = "SELECT *, DATE(entry_date) as entry_date, DATE(close_date) as close_date FROM incident WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND category LIKE '%".$_GET["category"]."%' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }
        $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);
    
                $output1 = Array();
                $sql1 = "SELECT * FROM incident_comments WHERE inc_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();

?>