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

if($_GET["type"]=="getMaterialsByType") {
    $output = Array();
    $sql = "";
    if ($_GET["material_type"] == "Raw Material" || $_GET["material_type"] == "Packing Material") {
        $sql = "SELECT * FROM material WHERE material_subtype='".$_GET["subtype"]."' AND status='approve'";
    } else {
        $sql = "SELECT * FROM chemical_master WHERE status='active'";
    }
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}else if($_GET["type"]=="getRawMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE material_subtype='".$_GET["material_type"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="getVendors") {
    $output = Array();
	$sql = "SELECT * FROM vendor WHERE status='Approved'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="getUnits") {
    $output = Array();
	$sql = "SELECT * FROM unit";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getClients") {
    $output = Array();
	$sql = "SELECT * FROM client WHERE status='approve'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getRawMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE status='approve' AND material_type='Raw Material' AND material_subtype='".$_GET["material_type"]."' ORDER BY material_name";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["available_stock"] = +$row1["qty"];
		        }
		    } else {
		        $row["available_stock"] = 0;
		    }
		    
		    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Under Test'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["under_stock"] = +$row1["qty"];
		        }
		    } else {
		        $row["under_stock"] = 0;
		    }
		    
		    $sql1 = "SELECT IFNULL(SUM(required_qty), 0) as qty FROM po_material WHERE material_code='".$row["material_code"]."' AND po_no IN (SELECT po_no FROM purchaseorder WHERE is_security_receive='No')";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["po_qty"] = +$row1["qty"];
		        }
		    } else {
		        $row["po_qty"] = 0;
		    }
		    
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "getPackingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE status='approve' AND material_type='Packing Material' AND material_subtype='".$_GET["material_type"]."' ORDER BY material_name";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["available_stock"] = +$row1["qty"];
		        }
		    } else {
		        $row["available_stock"] = 0;
		    }
		    
		    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Under Test'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["under_stock"] = +$row1["qty"];
		        }
		    } else {
		        $row["under_stock"] = 0;
		    }
		    
		    $sql1 = "SELECT IFNULL(SUM(required_qty), 0) as qty FROM po_material WHERE material_code='".$row["material_code"]."' AND po_no IN (SELECT po_no FROM purchaseorder WHERE is_security_receive='No')";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["po_qty"] = +$row1["qty"];
		        }
		    } else {
		        $row["po_qty"] = 0;
		    }
		    
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "saveIndend") {
    $flag = 0;
    for ($i = 0; $i < count($input); $i++) {
        $temp = $input[$i];
		$sql = "INSERT INTO indend (user_no, material_type, material_subtype, material_no, required_qty, unit, requirement, vendor_no, required_for, client_code, entry_by, entry_date, entry_dept, purpose) VALUES 
		('".$_GET["user_no"]."','".$temp["material_type"]."','".$temp["material_subtype"]."','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."','".$temp["requirement"]."','".$temp["vendor"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$_GET["emp_id"]."','".$entry_date."','".$_GET["department"]."','".$temp["purpose"]."')";
		if($conn->query($sql)) {
			$flag = 0;
		} else {
			$flag = 1;
			break;
		}
    }
    if ($flag == 0) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveGeneralIndend") {
	
    $flag = 0;
    for ($i = 0; $i < count($input); $i++) {
        $temp = $input[$i];
		$sql = "INSERT INTO indend (user_no,material_type, material_no, required_qty, unit, requirement, vendor_no, required_for, client_code, entry_by, entry_date, entry_dept, purpose) VALUES 
		('".$_GET["user_no"]."','Stationary','".$temp["material_code"]."','".$temp["qty"]."','Nos','".$temp["requirement"]."','','','','".$_GET["emp_id"]."','".$entry_date."','".$_GET["department"]."','')";
		if($conn->query($sql)) {
			$flag = 0;
		} else {
			$flag = 1;
			break;
		}
    }
    if ($flag == 0) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getPendingIndends") {
    $sql = "SELECT indend_no, entry_by, entry_date, requirement, COUNT(id) as total_items FROM indend WHERE user_no='".$_GET["user_no"]."' AND entry_dept='".$_GET["department"]."' AND status='pending' GROUP BY indend_no, entry_by, entry_date, requirement";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT i.*, v.vendor_name FROM indend i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.indend_no='".$row["indend_no"]."' AND i.status='pending'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
		    if($result1->num_rows > 0) {
		        while($row1 = $result1->fetch_assoc()) {
		            if ($row1["material_type"] == "Stationary") {
		                $sql2 = "SELECT * FROM stationary_master WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row1["material_no"]."'";
            		    $result2 = $conn->query($sql2);
            		    if ($result2->num_rows > 0) {
            		        while ($row2 = $result2->fetch_assoc()) {
            		            $row1["material_name"] = $row2["material_name"];
            		            break;
            		        }
            		    }
		            } else {
		                $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_no"]."'";
            		    $result2 = $conn->query($sql2);
            		    if ($result2->num_rows > 0) {
            		        while ($row2 = $result2->fetch_assoc()) {
            		            $row1["material_type"] = $row2["material_type"]." (".$row2["material_subtype"].")";
            		            $row1["material_name"] = $row2["material_name"];
            		            $row1["grade"] = $row2["grade"];
            		            break;
            		        }
            		    }
		            }
        		    
		            $output1[] = $row1;
		        }
		        $row["materials"] = $output1;
			    $output[] = $row;
		    }
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "checkIndend") {
    $materials = $input["materials"];
    $flag = 0;
    for ($i = 0; $i < count($materials); $i++) {
        $material = $materials[$i];
        $sql = "UPDATE indend SET status='".$material["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$material["id"]."'";
        if ($conn->query($sql)) {
            $flag = 0;
        } else {
            $flag = 1;
            break;
        }
    }
    if ($flag == 0) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getDeptIndendsLog") {
    $output = Array();
    $sql = "SELECT * FROM indend WHERE user_no='".$_GET["user_no"]."' AND entry_dept='".$_GET["department"]."' GROUP BY indend_no";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT i.*, v.vendor_name FROM indend i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.indend_no='".$row["indend_no"]."'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
		    if($result1->num_rows > 0) {
		        while($row1 = $result1->fetch_assoc()) {
		            if ($row["material_type"] == "Stationary") {
		                $sql2 = "SELECT * FROM stationary_master WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row1["material_no"]."'";
            		    $result2 = $conn->query($sql2);
            		    if ($result2->num_rows > 0) {
            		        while ($row2 = $result2->fetch_assoc()) {
            		            $row1["material_name"] = $row2["material_name"];
            		            break;
            		        }
            		    }
		            } else {
		                $sql2 = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row1["material_no"]."'";
            		    $result2 = $conn->query($sql2);
            		    if ($result2->num_rows > 0) {
            		        while ($row2 = $result2->fetch_assoc()) {
            		            $row1["material_type"] = $row2["material_type"]." (".$row2["material_subtype"].")";
            		            $row1["material_name"] = $row2["material_name"];
            		            $row1["grade"] = $row2["grade"];
            		            break;
            		        }
            		    }
		            }
        		    
		            $output1[] = $row1;
		        }
		        $row["materials"] = $output1;
			    $output[] = $row;
		    }
		}
	}
	echo json_encode($output);
} else if ($_GET["type"]=="getIndendsLog") {
    $output = Array();
    $sql = "SELECT i.*, DATE(i.entry_date) as entry_date, e.department FROM indend i LEFT JOIN employee e ON i.entry_by=e.emp_id WHERE i.user_no='".$_GET["user_no"]."' AND e.department LIKE '%".$_GET["department_name"]."%' AND i.status LIKE '%".$_GET["status"]."%' GROUP BY i.indend_no ORDER BY id DESC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    
		    $sql1 = "SELECT i.*, v.vendor_name FROM indend i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.indend_no='".$row["indend_no"]."'";
		    $result1 = $conn->query($sql1);
		    $output1 = Array();
		    if($result1->num_rows > 0) {
		        while($row1 = $result1->fetch_assoc()) {
		            if ($row["material_type"] == "Stationary") {
		                $sql2 = "SELECT * FROM stationary_master WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row1["material_no"]."'";
            		    $result2 = $conn->query($sql2);
            		    if ($result2->num_rows > 0) {
            		        while ($row2 = $result2->fetch_assoc()) {
            		            $row1["material_name"] = $row2["material_name"];
            		            break;
            		        }
            		    }
		            } else {
		                $sql2 = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row1["material_no"]."'";
            		    $result2 = $conn->query($sql2);
            		    if ($result2->num_rows > 0) {
            		        while ($row2 = $result2->fetch_assoc()) {
            		            $row1["material_type"] = $row2["material_type"]." (".$row2["material_subtype"].")";
            		            $row1["material_name"] = $row2["material_name"];
            		            $row1["grade"] = $row2["grade"];
            		            break;
            		        }
            		    }
		            }
        		    
		            $output1[] = $row1;
		        }
		        $row["materials"] = $output1;
			    $output[] = $row;
		    }
		}
	}
	echo json_encode($output);
} else if ($_GET["type"]=="getCheckedIndends") {
    $output = Array();
    $sql = "SELECT i.*, v.vendor_name FROM indend i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' 
    AND i.status='checked'";
	$result = $conn->query($sql);
	if($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
		    
		    if ($row["material_type"] == "Stationary") {
		        $sql1 = "SELECT * FROM stationary_master WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["material_no"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["material_name"] = $row1["material_name"];
    		            $row["gst"] = $row1["gst"];
    		            break;
    		        }
    		    }
		    } else {
		        $sql1 = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["material_no"]."'";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $row["material_type"] = $row1["material_type"]." (".$row1["material_subtype"].")";
    		            $row["material_name"] = $row1["material_name"];
    		            $row["grade"] = $row1["grade"];
    		           $row["gst"] = $row1["gst"];
    		            break;
    		        }
    		    }
		    }
		    
		    $lowest = array();
		    $highest = array();
		    $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["material_code"]."' ORDER BY id DESC";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
		            $result2 = $conn->query($sql2);
		            if ($result2->num_rows > 0) {
		                while ($row2 = $result2->fetch_assoc()) {
		                    $row["last_purchase"] = $row2["vendor_no"];
		                    
		                    $flag = 0;
		                    if ($lowest["rate"] < $lowest["rate"]) {
	                            $flag = 1;
	                        }
		                    if ($flag == 0) {
		                        $lowest["rate"] = $row1["rate"];
		                        $lowest["vendor_name"] = $row2["vendor_name"];
    		                    $lowest["vendor_no"] = $row2["vendor_no"];
    		                    $lowest["challan_no"] = $row1["challan_no"];
    		                    $lowest["challan_date"] = $row2["challan_date"];
		                    }
		                    
		                    $flag = 0;
		                    if ($highest["rate"] < $highest["rate"]) {
	                            $flag = 1;
	                        }
		                    if ($flag == 0) {
		                        $highest["rate"] = $row1["rate"];
		                        $highest["vendor_name"] = $row2["vendor_name"];
    		                    $highest["vendor_no"] = $row2["vendor_no"];
    		                    $highest["challan_no"] = $row1["challan_no"];
    		                    $highest["challan_date"] = $row2["challan_date"];
		                    }
		                }
		            }
		        }
		    } else {
		        $row["last_purchase"] = "";
		    }
		    
		    $row["lowest"] = $lowest;
		    $row["highest"] = $highest;
		    
		    $output1 = Array();
		    $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row["material_no"]."%' AND q.status='approve'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            
		            $sql2 = "SELECT min(rate) as lowest_rate, max(rate) as highest_rate FROM challan_materials m ON challan c ON m.challan_no=c.challan_no WHERE m.user_no='".$_GET["user_no"]."' AND m.material_code='".$row["material_code"]."' AND c.vendor_no='".$row1["vendor_no"]."' ORDER BY m.id DESC";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row1["lowest_rate"] = $row2["lowest_rate"];
        		            $row1["highest_rate"] = $row2["highest_rate"];
        		        }
        		    } else {
        		        $row1["lowest_rate"] = 0.00;
        		        $row1["highest_rate"] = 0.00;
        		    }
        		    
        		    $sql2 = "SELECT rate FROM challan_materials m ON challan c ON m.challan_no=c.challan_no WHERE m.user_no='".$_GET["user_no"]."' AND m.material_code='".$row["material_code"]."' AND c.vendor_no='".$row1["vendor_no"]."' ORDER BY m.id DESC LIMIT 1";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row1["last_rate"] = $row2["rate"];
        		        }
        		    } else {
        		        $row1["last_rate"] = 0.00;
        		    }
        		    
		            $materials = json_decode($row1["materials"]);
                    for ($i = 0; $i < count($materials); $i++) {
                        $material = $materials[$i];
                        if ($material->material_code == $row["material_no"]) {
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
} else if ($_GET["type"] == "approveIndend") {
     $sql = "UPDATE indend SET vendor_no='".$input["vendor_no"]."', quotation_no='".$input["quotation_no"]."', quotation_amt='".$input["quotation_amt"]."', quotation_per='".$input["quotation_per"]."', qty='".$input["order_qty"]."', gst='".$input["gst"]."', gross_total='".$input["gross_total"]."', gst_total='".$input["gst_total"]."', net_total='".$input["net_total"]."',status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if($_GET['type'] == 'indendLogPDF'){
    $_GET['filename'] =='Indend of Material Log'; $_GET['pdftype'] ='headfoot'; include('../pdfimp2.php');
    $html.='
    <h2 style="text-align:center">Indend of Material Log</h2>
    <table>
        <tr>
            <td>Sr No</td>
            <td>Date</td>
            <td>Department</td>
            <td>Indend No</td>
            <td>Indend By</td>
            <td>No of Items</td>
            <td>Requirement</td>
            <td>Status</td>
        </tr>';
    $output = Array();
    $sql = "SELECT * FROM indend WHERE user_no='".$_GET["user_no"]."' AND department LIKE '%".$_GET["department_name"]."%' AND status LIKE '%".$_GET["status"]."%' GROUP BY indend_no";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $output1 = Array();
		    $sql1 = "SELECT * FROM indend WHERE indend_no='".$row["indend_no"]."'";
		    $result1 = $conn->query($sql1);
		    if($result1->num_rows > 0) {
		        while($row1 = $result1->fetch_assoc()) {
		            $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_no"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row1["material_type"] = $row2["material_type"]." (".$row2["material_subtype"].")";
        		            $row1["material_name"] = $row2["material_name"];
        		            $row1["grade"] = $row2["grade"];
        		            break;
        		        }
        		    }
        		    
        		    $sql2 = "SELECT * FROM vendor WHERE vendor_no='".$row1["vendor_no"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row1["vendor_name"] = $row2["vendor_name"];
        		            break;
        		        }
        		    }
        		    
		            $output1[] = $row1;
		        }
		        $row["materials"] = $output1;
			    $output[] = $row;
		    }
		    $html.='
		    <tr>
		        <td>'.$counter++.'</td>
		        <td>'.$row['entry_date'].'</td>
		        <td>'.$row['department'].'</td>
		        <td>'.$row['indend_no'].'</td>
		        <td></td>
		        <td></td>
		        <td></td>
		        <td>'.$row['status'].'</td>
		    </tr>
		    ';
		}
	}
	$html.='
	</table>';
    EOD;
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('', 'I');
}
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>