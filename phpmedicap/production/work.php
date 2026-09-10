<?php




ini_set('display_errors', 1);
error_reporting(E_ALL);


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
    
    if ($_GET["type"] == "getReadyBatchPlans") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b 
        LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending' GROUP BY b.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code 
                WHERE b.no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                
                $temp = 0;
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows == 0) {
                            $temp = 1;
                        }
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."' AND person_type='Officer'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["officers"] = $output2;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."' AND person_type='Operator'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["operators"] = $output2;
                        
                        $output1[] = $row1;
                    }
                }
                if ($temp == 1) {
                    $row["stages"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getWorkDetails") {
        $sql = "SELECT * FROM bmr_stages WHERE id='".$_GET["id"]."'";
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
                
                if ($row["work"] == "allocate") {
                    $row["work_data"] = json_decode($row["work_data"]);
                }
                echo json_encode($row);
            }
        }
    } else if ($_GET["type"] == "getOfficers") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active' AND department='Production' AND designation='Officer'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getOperators") {
        $output = Array();
        $sql = "SELECT * FROM labour WHERE status='approve' AND category='Operator'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLabours") {
        $output = Array();
        $sql = "SELECT * FROM labour WHERE status='approve' AND category='Labour'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveWork") {
        $sql = "UPDATE bmr_stages SET work_data='".json_encode($input)."', work='allocate' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStageOfficers") {
        $bmr_no = "";
        $flag = 0;
        for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            $sql = "INSERT INTO bmr_work (bmr_no, stage, person_type, person_no, entry_by, entry_date) VALUES ('".$data["bmr_no"]."', '".$data["stage"]."', 'Officer', '".$data["emp_id"]."', '".$_GET["emp_id"]."', '$entry_date')";
            if (!$conn->query($sql)) {
                $flag = 1;
                break;
            }
            $bmr_no = $data["bmr_no"];
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveStageOperators") {
        $flag = 0;
        for ($i = 0; $i < count($input); $i++) {
            $data = $input[$i];
            $sql = "INSERT INTO bmr_work (bmr_no, stage, person_type, person_no, entry_by, entry_date) VALUES ('".$data["bmr_no"]."', '".$data["stage"]."', 'Operator', '".$data["emp_id"]."', '".$_GET["emp_id"]."', '$entry_date')";
            if (!$conn->query($sql)) {
                $flag = 1;
                break;
            }
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getWorkAllocationLog") {
        $output = Array();
         $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending' GROUP BY b.id";
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
                
                $temp = 0;
                $output1 = Array();
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='$bmr_no' AND stage='".$row1["stage"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows == 0) {
                            $temp = 1;
                        }
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."' AND person_type='Officer'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["officers"] = $output2;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."' AND person_type='Operator'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["operators"] = $output2;
                        
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getInprocessBatches") {
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status IN ('ready', 'inprocess') AND b.work !='pending'";
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
                $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["id"]."' AND status !='complete'";
                $result1 = $conn->query($sql1);
                if ($result->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."' AND person_type='Officer'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["officers"] = $output2;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr_work WHERE bmr_no='".$row["id"]."' AND stage='".$row1["stage"]."' AND person_type='Operator'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["operators"] = $output2;
                        
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }


}

$conn->close();
?>