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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

if ($_GET["type"] == "getMaterials") {
    $output = array();
    $sql = "SELECT * FROM general_material WHERE material_type='Stationary'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}  else if ($_GET["type"] == "getVendors") {
    $output = array();
    $sql = "SELECT * FROM vendor WHERE status='Approved'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "getStationaryIssuance") {
    $output = array();
    $sql = "SELECT * FROM stationary_issuance";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "getPendingStationaryIssuance") {
    $output = array();
    $sql = "SELECT * FROM stationary_issuance WHERE status = 'pending' AND department = '".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "getApprovedStationaryIssuance") {
    $output = array();
    $sql = "SELECT * FROM stationary_issuance WHERE status = 'approve' AND department = '".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if ($_GET["type"] == "getStationaryIssuanceByDpt") {
    $output = array();
    $sql = "SELECT * FROM stationary_issuance WHERE department='".$_GET["department"]."' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 

else if($_GET["type"]=="getDepartment") {
    $sql = "SELECT DISTINCT department as department FROM section";
    $result = $conn->query($sql);
    $output = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
        echo json_encode($output);
    }
} 

 else if($_GET["type"]=="getsectionbyDpt") {
    $sql = "SELECT * FROM section WHERE department='".$_GET["selecteddepartment"]."' ";
    $result = $conn->query($sql);
    $output = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
        
    }
	echo json_encode($output);
} 
else if ($_GET["type"] == "getStationaryReceiving") {
    $output = array();
    $sql = "SELECT * FROM stationary_inward ORDER BY id DESC";
    $result = $conn->query($sql);
	
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
    
            $sql1 = "SELECT * FROM stationary_materials WHERE no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
			$data = array();
			
			if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
            
					$sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                        }
                    } else {
                        $row1["material_name"] = "";
                    }
                    $data[] = $row1;
					
				}
            }
			$row["material"] = $data;
            $output[] = $row;
        }
    }
    echo json_encode($output);		
}

else if($_GET["type"]=="saveStationaryIssuance") {
    
        $sql = "INSERT INTO stationary_issuance(department, section_name, material_name, qty, entry_by, entry_date) VALUES
        ('".$_POST["department"]."','".$_POST["section_name"]."', '".$_POST["material_name"]."','".$_POST["qty"]."', '".$_GET["emp_id"]."', '$entry_date')"; 
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
        }
        else {
           echo "{\"status\":\"".$conn->error."\"}";
        }
}
 else if ($_GET["type"] == "saveStationaryReceiving") {
    $output = array();
    $sql = "INSERT INTO stationary_inward(challan_no, challan_date, vendor_no, entry_by, entry_date) VALUES ('".$_POST["challan_no"]."', '".$_POST["challan_date"]."', '".$_POST["vendor_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) === TRUE) {
		
		$last_id = $conn->insert_id;
		$flag = 0;
        $material = json_decode($_POST["material"], true);
        $length = sizeof($material);
    	
    	for($i = 0; $i < $length; $i++) { 
    	    $data = $material[$i];
		    $sql = "INSERT INTO stationary_materials (no, material_code, qty) VALUES ('$last_id', '".$data["material_code"]."', '".$data['qty']."')";
    	    if ($conn->query($sql) === TRUE) {
    	        $flag = 0;
    	    } else {
    	        $flag = 1;
    	    }
    	}
	
		if ($flag == 0) {
            echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}

    } else {
        echo "{\"status\":\"failed\"}";
    }
} 

else if ($_GET["type"]=="updateStationaryIssue") {
    $sql = "UPDATE stationary_issuance SET status='".$_GET["status"]."' WHERE id=".$_GET["id"];
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\" ".$conn->error."\"}";
    }
}

 else if ($_GET["type"] == "getPendingStationaryReceiving") {
    $output = array();
    $sql = "SELECT * from stationary_inward WHERE status='pending' ORDER BY id DESC";
    $result = $conn->query($sql);
	
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
    
            $sql1 = "SELECT * FROM stationary_materials WHERE no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
			$data = array();
			
			if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
            
					$sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                        }
                    } else {
                        $row1["material_name"] = "";
                    }
                    $data[] = $row1;
					
				}
            }
			$row["material"] = $data;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"]=="updateStationary") {
    $sql = "UPDATE stationary_inward SET status='".$_GET["status"]."' WHERE id=".$_GET["id"];
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}

else if ($_GET["type"] == "getStock") {
    $output = array();
    $sql = "SELECT * from stationary_inward WHERE status='approve' ORDER BY id DESC";
    $result = $conn->query($sql);
	
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
			
		$sql1 = "SELECT material_code, SUM(qty) as qty FROM stationary_materials WHERE no='".$row["id"]."' GROUP BY material_code";
            $result1 = $conn->query($sql1);
			$data = array();
			
			if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                
					$sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                        }
                    } else {
                        $row1["material_name"] = "";
                    }
                    $data[] = $row1;
					
				}
            }
			$row["material"] = $data;
            $output[] = $row;
        }
    }
    echo json_encode($output);
}

 else if ($_GET["type"] = "getStockMaterial") {
	
	$sql = "SELECT st.material_code, SUM(st.qty) as qty FROM stationary_materials st JOIN stationary_inward si ON st.no = si.id WHERE si.status = 'approve' GROUP BY st.material_code";
	$result = $conn->query($sql);
	$output = array();
	
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			
			$sql2 = "SELECT * FROM general_material WHERE material_no='".$row["material_code"]."'";
			$result2 = $conn->query($sql2);
			
			if ($result2->num_rows > 0) {
				while ($row2 = $result2->fetch_assoc()) {
					$row["material_name"] = $row2["material_name"];
				}
			} else {
				$row["material_name"] = "";
			}
			$output[] = $row;
		}
	}
	echo json_encode($output);
}

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>