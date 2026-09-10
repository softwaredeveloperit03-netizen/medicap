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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getChemicalsLog") {
        $output = array();
        $sql = "SELECT i.*, c.chemical_name, c.grade, e.department FROM chemical_indend i LEFT JOIN chemical c ON i.chemical_no=c.chemical_no LEFT JOIN employee e ON i.entry_by=e.emp_id WHERE i.user_no='".$_GET["user_no"]."' AND e.department LIKE '%".$_GET["department_name"]."%' AND i.chemical_no LIKE '%".$_GET["chemical_no"]."%' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveChemicalIndend") {
        $sql = "INSERT INTO chemical_indend (user_no, chemical_no, requirement, purpose, vendor_no, required_for, client_code, days, req_qty, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["chemical_no"]."', '".$input["requirement"]."', '".$input["purpose"]."', '".$input["vendor_no"]."', '".$input["required_for"]."', '".$input["client_code"]."', '".$input["days"]."', '".$input["req_qty"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingChemicals") {
        $output = array();
        $sql = "SELECT i.*, c.chemical_name, c.grade, e.department FROM chemical_indend i LEFT JOIN chemical c ON i.chemical_no=c.chemical_no LEFT JOIN employee e ON i.entry_by=e.emp_id WHERE i.user_no='".$_GET["user_no"]."' AND e.department='".$_GET["department"]."' AND i.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkChemical") {
        $sql = "UPDATE chemical_indend SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCheckedChemicals") {
        $output = array();
        $sql = "SELECT i.*, c.chemical_name, c.grade, e.department FROM chemical_indend i LEFT JOIN chemical c ON i.chemical_no=c.chemical_no LEFT JOIN employee e ON i.entry_by=e.emp_id WHERE i.user_no='".$_GET["user_no"]."' AND e.department='".$_GET["department"]."' AND i.status='checked'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM po_material WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["chemical_no"]."' ORDER BY id DESC";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $sql2 = "SELECT * FROM purchaseorder WHERE user_no='".$_GET["user_no"]."' AND po_no='".$row1["po_no"]."'";
    		            $result2 = $conn->query($sql2);
    		            if ($result2->num_rows > 0) {
    		                while ($row2 = $result2->fetch_assoc()) {
    		                    $row["last_purchase"] = $row2["vendor_no"];
    		                }
    		            }
    		        }
    		    } else {
    		        $row["last_purchase"] = "";
    		    }
    		    
    		    $output1 = Array();
    		    $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row["chemical_no"]."%' AND q.status='approve'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
            		    
            		    if ($row1["vendor_no"] == $row["vendor_no"]) {
            		        $row1["last_purchase"] = "yes";
            		    } else {
            		        $row1["last_purchase"] = "no";
            		    }
            		    
    		            $materials = json_decode($row1["materials"]);
                        for ($i = 0; $i < count($materials); $i++) {
                            $material = $materials[$i];
                            if ($material->chemical_no == $row["chemical_no"]) {
                                $row1["quotation_amt"] = $material->quotation_amt;
                                $row1["quotation_per"] = $material->quotation_per;
                                $output1[] = $row1;
                                break;
                            }
                        }
    		        }
    		    }
    		    
    		    $row["vendors"] = $output1;
    		    
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveChemical") {
        $sql = "UPDATE chemical_indend SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."', quotation_no='".$input["quotation_no"]."', gross_total='".$input["gross_total"]."', gst_total='".$input["gst_total"]."', net_total='".$input["net_total"]."', vendor_no='".$input["vendor_no"]."', order_qty='".$input["order_qty"]."', quotation_amt='".$input["quotation_amt"]."', quotation_per='".$input["quotation_per"]."', gst='".$input["gst"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>