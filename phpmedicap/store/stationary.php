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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getVendors") {
        $output = Array();
        $sql = "SELECT * FROM vendor WHERE status='Approved'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT * FROM general_material WHERE material_type='Stationary' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUnits") {
        $output = Array();
        $sql  = "SELECT * FROM unit WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveChallan") {
        $sql = "INSERT INTO challan (material_type, challan_no, challan_date, vendor_no, discount, driver_name, driver_contact, tax_invoice, transport_company, materials, entry_by, entry_date) VALUES ('Stationary','".$input["challan_no"]."', '".$input["challan_date"]."', '".$input["vendor_no"]."', '".$input["discount"]."', '".$input["driver_name"]."', '".$input["driver_contact"]."', '".$input["tax_invoice"]."', '".$input["transport_company"]."', '".json_encode($input["materials"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $materials = $input["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO challan_materials (challan_no, material_type, material_code, qty, unit, rate, required_for, client_code) VALUES ('".$input["challan_no"]."','".$material["material_subtype"]."', '".$material["material_code"]."', '".$material["qty"]."', '".$material["unit"]."', '".$material["rate"]."', '".$material["required_for"]."', '".$material["client_code"]."')";
                $conn->query($sql1);
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingChallans") {
        $output = Array();
        $sql = "SELECT * FROM challan WHERE status='pending' AND material_type='Stationary'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
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
    } else if ($_GET["type"] == "updateChallan") {
        $sql = "UPDATE challan SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getChallansLog") {
        $output = Array();
        $sql = "SELECT * FROM challan WHERE material_type='Stationary'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
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
    } else if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
        $sql = "SELECT * FROM challan WHERE status='approve' AND material_type='Stationary' AND receiving_status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
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
    } else if ($_GET["type"] == "getInprocessReceivings") {
        $output = Array();
        $sql = "SELECT * FROM challan WHERE status='approve' AND material_type='Stationary' AND receiving_status='inprocess'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
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
    } else if ($_GET["type"] == "receiveStationary") {
        $materials = $input["materials"];
        for ($i = 0; $i < count($materials); $i++) {
            $material = $materials[$i];
            $sql = "UPDATE challan_materials SET received_qty='".$material["received_qty"]."' WHERE id='".$material["id"]."'";
            $conn->query($sql);
            
            $sql1 = "INSERT INTO stock_book (material_type, grn_no,ar_no,vendor_no,material_code, batch_no,qty,unit,mfg_date,exp_date, status,entry_by,entry_date, containers, receiving_no, section, rack_no) VALUES ('Stationary','', '','".$input["vendor_no"]."', '".$material["material_code"]."', '', '".$material["received_qty"]."', '".$material["unit"]."', '', '','approve', '".$_GET["emp_id"]."', '$entry_date', '', '".$input["id"]."', '', '')";
            $conn->query($sql1);
        }
        $sql = "UPDATE challan SET receiving_status='approve' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getReceivingLog") {
        $output = Array();
        $sql = "SELECT * FROM challan WHERE status='approve' AND material_type='Stationary'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["vendor_name"] = $row1["vendor_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM challan_materials WHERE challan_no='".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM general_material WHERE material_code='".$row1["material_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["material_name"] = $row2["material_name"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStock") {
        $sql = "SELECT c.*, m.material_type, m.material_code,m.material_name,m.entry_date FROM challan_materials c LEFT JOIN material m ON 
        c.material_code=m.material_code WHERE c.user_no='".$_GET["user_no"]."'"; //AND DATE(m.entry_date) 
        //BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }

}

$conn->close();
?>