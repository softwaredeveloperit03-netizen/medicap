<?php
require 'db.php';
require 'token.php';
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

if($_GET["type"]=="getPendingInwords") {

    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["inword_details"] = json_decode($row["inword_details"]);
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
			}
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} 
else if ($_GET["type"] == "getPendingGRN") {
        $output = Array();
      echo  $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND m.material_type ='Packing Material' AND c.receiving='Approved' AND c.grn='pending' ORDER BY c.challan_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output1 = array();
                $sql1 = "SELECT c.*, m.material_type, m.material_subtype, m.material_name FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code WHERE c.challan_no='".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1 = array_map('utf8_encode', $row1);
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
else if($_GET["type"]=="receiveMaterial") {
    $flag = 0;
    $file = "";
    $dev_no = "";
    if ($_POST["coa_received"] == 'Yes') {
        if (isset($_FILES["coa"])) {
            $rand_no = mt_rand(100000,999999);
            $file = "upload/coa/".$rand_no.basename($_FILES["file"]["name"]).".pdf";
            move_uploaded_file($_FILES["file"]["tmp_name"], $file);
            $file = $rand_no.basename($_FILES["file"]["name"]).".pdf";
        } else {
            $flag = 1;
        }
    } else {
        $sql = "SELECT IFNULL(MAX(i_no), 0) as  i_no FROM deviation";
        $i_no = 0;
        $invoice_no = "";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
                break;
            }
        }
        $i_no++;
        $num = strlen($i_no);
        if($num == '1'){
            $dev_no = 'DEV-00'.$i_no;
        }else if($num == '2'){
            $dev_no = 'DEV-0'.$i_no;
        }else{
            $dev_no = 'DEV-'.$i_no;
        }
        $mfg_date = $_POST["mfg_date"];
        $expiry_date = $_POST["exp_date"];

        $deviation = json_decode($_POST["deviation"]);
        
        $product = Array();
        $product["product_code"] = $_POST["material_code"];
        $product["batch_no"] = $_POST["batch_no"];
        $product["mfg_date"] = $_POST["mfg_date"];
        $product["exp_date"] = $_POST["exp_date"];

        $sql = "INSERT INTO deviation (dev_no,i_no,department,related_to,category,type,deviation_date,justification,cause,description,entry_by,entry_date, product_details) VALUES ('$dev_no','$i_no','".$_GET["department"]."','".$deviation->related_to."','".$deviation->deviation_category."', '".$deviation->type."', '".$entry_date."', '".$deviation->justification."','".$deviation->cause."','".$deviation->description."','".$_GET["emp_id"]."','$entry_date', '".json_encode($product)."')";
        if ($conn->query($sql)) {
            $departments = $deviation->departments;
            if(count($departments) > 0){
                for ($i = 0; $i < count($departments); $i++) {
                    $temp = $departments[$i];
                    $sql = "INSERT INTO deviation_comments (dev_no,department) VALUES ('$dev_no','$temp->name')";
                    $conn->query($sql);
                }
            }
        }
    }

    if($flag==0){
        $damange = "pending";
        $temp = Array();
        $temp["pack_size"] = $_POST["pack_size"];
        $temp["challan_qty"] = $_POST["challan_qty"];
        if ($_POST["isdamagecontainer"] == "Yes") {
            $temp["outer_damage"] = $_POST["outer_damage"];
            $temp["inner_damage"] = $_POST["inner_damage"];
            $temp["damage_status"] = "pending";
        } else {
            $damange = "no";
        }
        $temp["isdamagecontainer"] = $_POST["isdamagecontainer"];
        $temp["damange_remark"] = $_POST["remark"];
        $temp["packing_condition"] = $_POST["packing_condition"];
        $temp["outer_packing"] = $_POST["outer_packing"];
        $temp["container_type"] = $_POST["container_type"];
        $temp["container_subtype"] = $_POST["container_subtype"];
        $temp["vehicle_condition"] = $_POST["vehicle_condition"];
        $temp["coa_received"] = $_POST["coa_received"];
        if ($_POST["coa_received"] == 'No') {
            $temp["deviation_no"] = $dev_no;
        } else {
            $temp["coa_file"] = $file;
        }
        $sql = "UPDATE raw_material SET batch_no='".$_POST["batch_no"]."', received_qty='".$_POST["qty_received"]."', containers='".$_POST["total_containers"]."', mfg_date='".$_POST["mfg_date"]."', exp_date='".$_POST["exp_date"]."', receiving_details='".json_encode($temp)."', coa_received='".$_POST["coa_received"]."', status='inprocess', receiving='inprocess', damage='".$damange."', received_by='".$_GET["emp_id"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
        }
    }else{
        echo "{\"status\":\"failed\",\"reason\":\"Upload COA\"}"; 
    }
} else if ($_GET["type"] == "getRawMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            if ($row["status"] == "pending") {
                $row["current_status"] = "Receiving Pending";
            } else if ($row["status"] == "inprocess") {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $receiving_details = $row["receiving_details"];
                $flag = 0;
                if ($row["coa_received"] == 'No') {
                    $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            if ($row1["status"] == "approve") {
                                $row["deviation"] = "approve";
                            } else if ($row1["status"] == "pending") {
                                $row["current_status"] = "Deviation Inprocess";
                                $flag = 1;
                                $row["deviation"] = "pending";
                            } else if ($row1["status"] == "reject") {
                                $row["deviation"] = "reject";
                                $row["current_status"] = "Deviation Rejected";
                                $flag = 1;
                            }
                            
                            $output2 = Array();
                            $sql2 = "SELECT * FROM deviation_comments WHERE dev_no='".$receiving_details->deviation_no."'";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output2[] = $row2;
                                }
                            }
                            $row1["comments"] = $output2;
                            $row["dev_details"] = $row1;
                            break;
                        }
                    }
                }
                if ($flag == 0) {
                    if ($row["dedusting"] == "pending") {
                        $row["current_status"] = "Dedusting of Material is Pending";
                    } else if ($row["damage"] == "pending") {
                        $row["current_status"] = "Damange Inspection is Pending";
                    } else if ($row["weighing"] == "pending") {
                        $row["current_status"] = "Weighing of Material is Pending";
                    } else if ($row["grn"] == "pending") {
                        $row["current_status"] = "GRN is Pending";
                    } else if ($row["grn"] == "inprocess") {
                        $row["current_status"] = "GRN Checking & Approval is Pending";
                    } else if ($row["grn"] == "approve") {
                        $row["current_status"] = "Approve";
                    } else if ($row["grn"] == "reject") {
                        $row["current_status"] = "GRN has been rejected";
                    } else if ($row["grn"] == "labels") {
                        $row["current_status"] = "Label Printing is Pending";
                    }
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingReceivings") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='inprocess' AND receiving='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);

            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] == "approve") {
                            $row["deviation"] = "approve";
                        } else if ($row1["status"] == "pending") {
                            $row["current_status"] = "Deviation Inprocess";
                            $flag = 1;
                            $row["deviation"] = "pending";
                        } else if ($row1["status"] == "reject") {
                            $row["deviation"] = "reject";
                            $row["current_status"] = "Deviation Rejected";
                            $flag = 1;
                        }
                        
                        $output2 = Array();
                        $sql2 = "SELECT * FROM deviation_comments WHERE dev_no='".$receiving_details->deviation_no."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["comments"] = $output2;
                        $row["dev_details"] = $row1;
                        break;
                    }
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateReceivedMaterial") {
    $sql = "UPDATE raw_material SET receiving='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getReceivingLog") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE receiving !='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);

            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] == "approve") {
                            $row["deviation"] = "approve";
                        } else if ($row1["status"] == "pending") {
                            $row["current_status"] = "Deviation Inprocess";
                            $flag = 1;
                            $row["deviation"] = "pending";
                        } else if ($row1["status"] == "reject") {
                            $row["deviation"] = "reject";
                            $row["current_status"] = "Deviation Rejected";
                            $flag = 1;
                        }
                        
                        $output2 = Array();
                        $sql2 = "SELECT * FROM deviation_comments WHERE dev_no='".$receiving_details->deviation_no."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["comments"] = $output2;
                        $row["dev_details"] = $row1;
                        break;
                    }
                } else {
                    $row["dev_details"] = [];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingDamages") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            if ($flag == 0) {
                if ($row["dedusting"] == "approve" && $row["damage"] == 'pending') {
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDamageInspection") {
    $input["entry_by"] = $_GET["emp_id"];
    $input["entry_date"] = $entry_date;
    $sql = "UPDATE raw_material SET damage='inprocess', damage_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "updateDamageInspection") {
    $input["approve_by"] = $_GET["emp_id"];
    $input["approve_date"] = $entry_date;
    $sql = "UPDATE raw_material SET damage='".$_GET["status"]."', damage_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDamageLog") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE damage NOT IN ('pending', 'no')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            $row["damage_details"] = json_decode($row["damage_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getInprocessDamages") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE damage='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            $row["damage_details"] = json_decode($row["damage_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingDedustingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            if ($flag == 0) {
                if ($row["receiving"] == "approve" && $row["dedusting"] == 'pending') {
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveMaterialDedusting") {
    $sql = "UPDATE raw_material SET dedusting='inprocess', dedusting_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getCheckingDedustingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE dedusting='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }

            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $row["dedusting_details"] = json_decode($row["dedusting_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateDedustingMaterial") {
    $input["check_by"] = $_GET["emp_id"];
    $input["check_date"] = $entry_date;
    $sql = "UPDATE raw_material SET dedusting='".$_GET["status"]."', dedusting_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getDedustingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE dedusting !='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }

            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $row["dedusting_details"] = json_decode($row["dedusting_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingDamageMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }
            
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            if ($flag == 0) {
                if ($row["dedusting"] == 'approve' && $row["damage"] == "pending") {
                    $damage_containers = +$receiving_details->inner_damage + +$receiving_details->outer_damage;
                    $row["total_damage"] = $damage_containers;
                    $output1 = Array();
                    for ($i = 0; $i < $damage_containers; $i++) {
                        $temp = Array();
                        $temp["container_no"] = $i + 1;
                        $temp["condition"] = "";
                        $temp["remark"] = "";
                        $output1[] = $temp;
                    }
                    $row["damage_containers"] = $output1;
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDamageInspection") {
    $sql = "UPDATE raw_material SET damage='inprocess', damage_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingWeighingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }
            
            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            if ($flag == 0) {
                if (($row["damage"] == 'approve' || $row["damage"] == "no") && $row["weighing"] == "pending") {
                    $damage = 0;
                    if ($row["damage"] == "approve") {
                        $row["damage_details"] = json_decode($row["damage_details"]);

                        $damage_details = $row["damage_details"];
                        $damange_containers = $damage_details->containers;
                        $damage = +$damage_details->total_damage;
                        for ($i = 0; $i < count($damange_containers); $i++) {
                            $container = $damange_containers[$i];
                            $container->gross_wt = 0;
                            $container->tare_wt = 0;
                            $container->net_wt = 0;
                            $container->weight_by = "";
                            $container->check_by = "";
                            $damange_containers[$i]  = $container;
                        }
                        $row["damage_containers"] = $damange_containers;
                    }else{
                        $row["damage_containers"] = [];
                    }
                    $output1 = Array();
                    $containers = +$row["containers"] - $damage;
                    for ($i = 1; $i <= $containers; $i++) {
                        $temp = Array();
                        $temp["container_no"] = $i;
                        $temp["gross_wt"] = 0;
                        $temp["tare_wt"] = 0;
                        $temp["net_wt"] = 0;
                        $temp['weight_by'] = "";
                        $temp['check_by'] = "";
                        $output1[] = $temp;
                    }
                    
                    $row["weight_containers"] = $output1;

                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveWeighingMaterials") {
    $sql = "UPDATE raw_material SET weighing='inprocess', weighing_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getCheckingWeighingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE weighing='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }
            
            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            $row["weighing_details"] = json_decode($row["weighing_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getWeighingMaterials") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE weighing !='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }
            
            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
            }
            
            $row["inword_details"] = json_decode($row["inword_details"]);
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            $row["weighing_details"] = json_decode($row["weighing_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateWeighing") {
    $input["check_by"] = $_GET["emp_id"];
    $input["check_date"] = $entry_date;
    $sql = "UPDATE raw_material SET weighing='approve', weighing_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingGRN") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }

            $row["inword_details"] = json_decode($row["inword_details"]);
            
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $receiving_details = $row["receiving_details"];
            $flag = 0;
            if ($row["coa_received"] == 'No') {
                $sql1 = "SELECT * FROM deviation WHERE dev_no='".$receiving_details->deviation_no."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["status"] !== "approve") {
                            $flag = 1;
                        }
                    }
                }
            }
            if ($flag == 0) {
                if ($row["weighing"] == 'approve' && $row["grn"] == "pending") {
                    $row["weighing_details"] = json_decode($row["weighing_details"]);
                    $weighings = $row["weighing_details"];

                    $accept_qty = 0;
                    $reject_qty = 0;
                    $containers = $weighings->containers;
                    $damages = $weighings->damage_containers;

                    for ($i = 0; $i < count($containers); $i++) {
                        $container = $containers[$i];
                        $accept_qty += +$container->net_wt;
                    }

                    for ($i = 0; $i < count($damages); $i++) {
                        $container = $damages[$i];
                        if ($container->status == "approve") {
                            $accept_qty += +$container->net_wt;
                        } else {
                            $reject_qty += +$container->net_wt;
                        }
                    }
                    $short_qty = number_format(+$row["order_qty"] - ($accept_qty + $reject_qty), 2);
                    $row["accept_qty"] = $accept_qty;
                    $row["reject_qty"] = $reject_qty;
                    $row["short_qty"] = $short_qty;
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveGRN") {
    $input["entry_by"] = $_GET["emp_id"];
    $input["entry_date"] = $entry_date;
    $sql = "UPDATE raw_material SET grn='inprocess',accept_qty='".$input["accept_qty"]."', grn_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingCheckingGRN") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE grn='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }

            $row["inword_details"] = json_decode($row["inword_details"]);
            
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $row["weighing_details"] = json_decode($row["weighing_details"]);

            $weighings = $row["weighing_details"];

            $output1 = Array();
            $containers = $weighings->containers;
            for ($i = 0; $i < count($containers); $i++) {
                $output1[] = $containers[$i];
            }

            $damages = $weighings->damage_containers;
            for ($i = 0; $i < count($damages); $i++) {
                $damage = $damages[$i];
                if ($damage->status == 'approve') {
                    $output1[] = $damages[$i];
                }
            }
            $row["container_details"] = $output1;

            $row["grn_details"] = json_decode($row["grn_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateGRN") {
    $input["entry_by"] = $_GET["emp_id"];
    $input["entry_date"] = $entry_date;
    $sql = "";
    if ($input["status"] == "approve") {
        $sql = "UPDATE raw_material SET grn='".$input["status"]."', status='approve' WHERE id='".$input["id"]."'";
    } else {
        $sql = "UPDATE raw_material SET grn='".$input["status"]."' WHERE id='".$input["id"]."'";
    }
    
    if ($conn->query($sql)) {
        $grn_no = "GRN-".$input["id"];
        $ar_no = "AR-".$input["id"];
        $sql1 = "INSERT INTO stock_book (grn_no,ar_no,vendor_no,material_code, batch_no,qty,unit,mfg_date,exp_date,entry_by,entry_date, containers, receiving_no, section, rack_no) VALUES ('$grn_no', '$ar_no','".$input["vendor_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["accept_qty"]."', '".$input["unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($input["containers"])."', '".$input["id"]."', '".$input["section"]."', '".$input["rack"]."')";
        $conn->query($sql1);
        
        $sql1 = "UPDATE rack SET empty='no', material_type='Raw Material', material_code='".$input["material_code"]."', qty='".$input["accept_qty"]."', material_unit='".$input["unit"]."' WHERE rack_no='".$input["rack"]."'";
        $conn->query($sql1);
        
        $sql1 = "INSERT INTO sampling (material_code, batch_no, containers, grn_no, grn_date, mfg_date, exp_date) VALUES ('".$input["material_code"]."', '".$input["batch_no"]."', '".count($input["containers"])."','$grn_no', '$entry_date', '".$input["mfg_date"]."', '".$input["exp_date"]."')";
        $conn->query($sql1);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getGRNLog") {
    $output = Array();
    $sql = "SELECT * FROM raw_material WHERE grn NOT IN ('inprocess', 'pending')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_subtype"] = $row1["material_subtype"];
                    $row["grade"] = $row1["grade"];
                    $row["material_name"] = $row1["material_name"];
                }
            }

            $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }

            $row["inword_details"] = json_decode($row["inword_details"]);
            
            $row["receiving_details"] = json_decode($row["receiving_details"]);
            $row["weighing_details"] = json_decode($row["weighing_details"]);

            $row["grn_details"] = json_decode($row["grn_details"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if($_GET["type"]=="getStock") {
    if($_GET['from_date'] != '' && $_GET['material_code'] != ''){
        $sql = "SELECT * FROM stock_book WHERE material_code LIKE 'R%' AND material_code = '".$_GET['material_code']."' AND  DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER by id DESC";
    }else if($_GET['from_date'] != '' && $_GET['material_code'] == ''){
        $sql = "SELECT * FROM stock_book WHERE material_code LIKE 'R%' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER by id DESC";
    }else if($_GET['from_date'] == '' && $_GET['material_code'] != ''){
        $sql = "SELECT * FROM stock_book WHERE material_code LIKE 'R%' AND material_code = '".$_GET['material_code']."' ORDER by id DESC";
    }else{
       $sql = "SELECT * FROM stock_book WHERE material_code LIKE 'R%'";
    }
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while ($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["material_name"] = $row1["material_name"];
		            $row["material_type"] = $row1["material_type"];
		            $row["grade"] = $row1["grade"];
		        }
		    }
		    
		    $output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="rawmateriallist") {
    $sql = "SELECT * FROM stock_book WHERE material_code LIKE 'R%' GROUP by material_code";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while ($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["material_name"] = $row1["material_name"];
		            $row["material_type"] = $row1["material_type"];
		            $row["grade"] = $row1["grade"];
		        }
		    }
		    
		    $output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getWeighingPurchaseorder") {
	$sql = "SELECT * FROM purchaseorder where dedusting_status = 'active' && weighing_status='pending'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getGRNPurchaseorder"){
	$sql = "SELECT * FROM purchaseorder where dedusting_status = 'active' && weighing_status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$sql1 = "SELECT * FROM inword_material where po_no='".$row["id"]."'";
			$result1 = $conn->query($sql1);
			if($result1->num_rows > 0){
				while($row1 = $result1->fetch_assoc()){
					$row["transport_company"] = $row1["transport_company"];
					$row["vehicle_no"] = $row1["vehicle_no"];
					$row["challan_no"] = $row1["challan_no"];
					$row["challan_date"] = $row1["challan_date"];
					$row["tax_invoice_no"] = $row1["tax_invoice_no"];
					$row["driver_name"] = $row1["driver_name"];
					$row["driver_mobile"] = $row1["driver_mobile"];
					$row["inword_date"] = $row1["entry_date"];
					break;
				}
			}
			$sql2 = "SELECT * FROM po_material where po_no='".$row["id"]."'";
			$result2 = $conn->query($sql2);
			if($result2->num_rows > 0){
				$output2 = Array();
				while($row2 = $result2->fetch_assoc()){
					$output2[] = $row2;
				}
				$row["materials"] = $output2;
			}
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if ($_GET["type"]=="getAllDedustings") {
    $sql = "SELECT * FROM dedusting_material";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $sql2 = "SELECT material_code FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_code"] = $row2["material_code"];
		            $sql1 = "SELECT * FROM material WHERE material_code='".$row2["material_code"]."'";
        		    $result1 = $conn->query($sql1);
        		    if ($result1->num_rows > 0) {
        		        while ($row1 = $result1->fetch_assoc()) {
        		            $row["material_name"] = $row1["material_name"];
        		            break;
        		        }
        		    }
		        }
		    }
		    
		    $sql1 = "SELECT * FROM equipment WHERE equipment_code='".$row["equipment_code"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["equipment_name"] = $row1["equipment_name"];
		            $row["capacity"] = $row1["capacity"];
		            break;
		        }
		    }
		    
			$output[] = $row;
		}
	}
	echo json_encode($output);
}else if($_GET["type"]=="getPendingDedustingPO"){
	$sql = "SELECT * FROM material_received where dedusting_status='pending'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="getSelectedEquipments"){
    $output = Array();
	$sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND equipment_type='Vacuum Cleaner' AND status='approve'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getPendingDamageMaterials"){
	$sql = "SELECT * FROM material_received where total_damage > 0 AND damage_status='pending'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["material_name"] = $row1["material_name"];
		            $row["material_type"] = $row1["material_type"];
		        }
		    }
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
/* else if($_GET["type"]=="saveDamageInspection") {
	$flag = 0;
	$data = $input["containerweighing"];
	for($i=1;$i<=$input["total_damage"];$i++) {
		$sql = "INSERT INTO damage_inspection (receiving_no,material_code,container_no,conditions,remark,entry_by,entry_date) VALUES ('".$input["receiving_no"]."','".$input["material_code"]."','".$data["container".$i]."','".$data["condition".$i]."','".$data["remark".$i]."','".$_GET["emp_id"]."','$entry_date')";
		if($conn->query($sql)===TRUE) {
			$flag = 0;
		} else {
			$flag = 1;
			break;
		}
	}

    if($flag == 0){
		echo "{\"status\":\"success\"}";
		$sql = "UPDATE material_received SET damage_status='inprocess' WHERE material_code='".$input["material_code"]."' AND receiving_no='".$input["receiving_no"]."'";
		$conn->query($sql);
	}
	else {
		echo "{\"status\":\"failed\"}";
	}
} */ else if ($_GET["type"] == "getDamageContainersReport") {
    $output = Array();
    $sql = "SELECT * FROM damage_inspection";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["total_damage"] = $row1["total_damage"];
                    $row["batch_no"] = $row1["batch_no"];
                }
            }
            
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["material_grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
/* else if($_GET["type"]=="saveMaterialDedusting") {
	$sql = "UPDATE material_received SET dedusting_status='active' WHERE id='".$input["id"]."'";
	if($conn->query($sql)===TRUE) {
		echo "{\"status\":\"success\"}";
		$sql = "INSERT INTO dedusting_material (receiving_no, operator, equipment_code, equip_cleaned_by, area_cleaned_by, equip_cleaned_from, equip_cleaned_to, area_cleaned_from, area_cleaned_to, equipment_usage_start, equipment_usage_end, entry_by, entry_date) VALUES ('".$input["receiving_no"]."','".$input["operator"]."','".$input["equipment_code"]."','".$input["equip_cleaned_by"]."','".$input["area_cleaned_by"]."','".$input["equip_cleaned_from"]."','".$input["equip_cleaned_to"]."','".$input["area_cleaned_from"]."','".$input["area_cleaned_to"]."','".$input["start_time"]."','".$input["end_time"]."','".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql);
		
		$used_from = date("Y-m-d", $timestamp). " ".$input["equipment_usage_start"];
		$used_to = date("Y-m-d", $timestamp). " ".$input["equipment_usage_end"];
		$sql1 = "INSERT INTO equipment_uses (equipment_no, activity, cleaning_type, start_time, end_time, operator, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', 'dedusting', '".$input["cleaning_type"]."', '$used_from', '$used_to', '".$input["operator"]."', '".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql1);
		
		$cleaned_from = date("Y-m-d", $timestamp). " ".$input["equip_cleaned_from"];
		$cleaned_to = date("Y-m-d", $timestamp). " ".$input["equip_cleaned_to"];
		$sql1 = "INSERT INTO equipment_uses (equipment_no, activity, cleaning_type, start_time, end_time, operator, entry_by, entry_date) VALUES ('".$input["equipment_code"]."', 'cleaning', '".$input["cleaning_type"]."', '$cleaned_from', '$cleaned_to', '".$input["operator"]."', '".$_GET["emp_id"]."','".$entry_date."')";
		$conn->query($sql1);
	} else {
		echo "{\"status\":\"An error has occurred, Please try again.\"}";
	}
} */
else if($_GET["type"]=="getRejectionInformation") {
	$sql = "SELECT * FROM damage_inspection WHERE qa_status='reject' AND rejection_status='pending'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
			$sql1 = "SELECT * FROM material_received WHERE material_code='".$row["material_code"]."' AND received_no='".$row["received_no"]."'";
			$result1 = $conn->query($sql1);
			if($result1->num_rows > 0){
				while($row1 = $result1->fetch_assoc()){
					$row["total_containers"] = $row1["total_containers"];
					$row["total_damage"] = $row1["total_damage"];
				}
			}
			
			$sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
			$result1 = $conn->query($sql1);
			if($result1->num_rows > 0) {
				while($row1 = $result1->fetch_assoc()){
					$row["material_type"] = $row1["material_type"];
					$row["material_name"] = $row1["material_name"];
				}
			}
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="saveRejectionReport") {
	if(!isset($input["storage_location"])) {
		$input["storage_location"] = "";
	}
	if(!isset($input["reason"])) {
		$input["reason"] = "";
	}
	
	$sql = "INSERT INTO rejection_report (receiving_no,material_code,container_no,rejected_qty,rejection_reason,isRejectionArea,reason,storage_location,supplier_inform,reject_action,entry_by,entry_date) VALUES ('".$input["receiving_no"]."','".$input["material_code"]."','".$input["total_containers"]."','".$input["total_damage"]."','".$input["total_rejected"]."','".$input["rejected_qty"]."','".$input["rejection_reason"]."','".$input["isRejectionArea"]."','".$input["reason"]."','".$input["storage_location"]."','".$input["supplier_inform"]."','".$input["to_destroyed"]."','".$input["send_supplier"]."','".$_GET["emp_id"]."','$entry_date')";
	if($conn->query($sql)===TRUE) {
		$sql1 = "UPDATE damage_inspection SET rejection_status='active' WHERE challan_no='".$input["challan_no"]."' AND material_name='".$input["material_name"]."'";
		if($conn->query($sql1)===TRUE) {
			echo "{\"status\":\"success\"}";
		} else {
			echo "{\"status\":\"An error has occurred, Please try again.\"}";
		}
	}
	else {
		echo "{\"status\":\"An error has occurred, Please try again.\"}";
	}
}
else if($_GET["type"]=="getPendingRejectionInformation") {
	$sql = "";
	if($_GET["department"]=="Store") {
		$sql = "SELECT * FROM rejection_report WHERE store_approve_by=''";
	} else if($_GET["department"]=="Quality Assurance") {
		$sql = "SELECT * FROM rejection_report WHERE qa_approve_by=''";
	}
	$result = $conn->query($sql);
	$output = Array();
	$index = 0;
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output = $row;
			break;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="updateRejectionReport") {
	$sql ="";
	if($_GET["department"]=="Store") {
		$sql = "UPDATE rejection_report SET store_approve_by='".$_GET["emp_id"]."',store_approve_date='".$entry_date."' WHERE challan_no='".$_GET["challan_no"]."' AND material_name='".$_GET["material_name"]."'";
	} else if($_GET["department"]=="Quality Assurance") {
		$sql = "UPDATE rejection_report SET status='active',qa_approve_by='".$_GET["emp_id"]."',qa_approve_date='".$entry_date."' WHERE challan_no='".$_GET["challan_no"]."' AND material_name='".$_GET["material_name"]."'";
	}
	if($conn->query($sql)===TRUE) {
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"An error has occurred, Please try again.\"}";
	}
} else if ($_GET["type"]=="getReceivingMaterials") {
    $sql = "SELECT * FROM material_received";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM inword_material WHERE id=".$row["inword_no"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["po_no"] = $row1["po_no"];
                    $row["po_date"] = $row1["po_date"];
                    $row["vendor_no"] = $row1["vendor_no"];
                    $row["challan_no"] = $row1["challan_no"];
                    $row["challan_date"] = $row1["challan_date"];
                    $row["vehicle_no"] = $row1["vehicle_no"];
                    $row["vehicle_no"] = $row1["vehicle_no"];
                }
            }
            
            if ($row["coa_received"] == "No" && $row["coa_deviation"] == "Raise Deviation" && $row["deviation_no"] !== "") {
                $sql9 = "SELECT * FROM deviation WHERE dev_no='".$row["deviation_no"]."'";
                $result9 = $conn->query($sql9);
                if ($result9->num_rows > 0) {
                    while ($row9 = $result9->fetch_assoc()) {
                        $row["deviation_status"] = $row9["status"];
                    }
                }
            }
            
            $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                    $row["is_approved_vendor"] = $row1["vendor_status"];
                }
            }
            
            $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_type"] = $row2["material_type"];
		            $row["material_subtype"] = $row2["material_subtype"];
		            $row["grade"] = $row2["grade"];
		            $row["material_name"] = $row2["material_name"];
		        }
		    }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getIndendReceivingMaterials") {
    $sql = "SELECT * FROM material_received";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getPendingMaterialWeighing") {
    $sql = "SELECT receiving_no, COUNT(container_name) as total_containers, SUM(pack_weight) as pack_weight, SUM(actual_weight) as actual_weight, entry_by, entry_date FROM weighing_containers WHERE status='pending' GROUP BY receiving_no";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while($row2 = $result2->fetch_assoc()) {
                            $row["material_name"] = $row2["material_name"];
                        }
                    }
                    
                    $sql2 = "SELECT * FROM inword_material WHERE id='".$row1["inword_no"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while($row2 = $result2->fetch_assoc()) {
                            $row["challan_no"] = $row2["challan_no"];
                            $row["challan_date"] = $row2["challan_date"];
                        }
                    }
                }
            }
            $sql1 = "SELECT * FROM weighing_containers WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["material"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateMaterialWeighting") {
    $sql = "UPDATE weighing_containers SET status='active' WHERE receiving_no='".$_GET["receiving_no"]."'";
    if($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getPendingGRN1") {
    $sql = "SELECT * FROM grn WHERE status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM inword_material WHERE id=".$row1["inword_no"];
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["challan_no"] = $row2["challan_no"];
                            $row["challan_date"] = $row2["challan_date"];
                            $row["po_no"] = $row2["po_no"];
                            $row["po_date"] = $row2["po_date"];
                            $row["inword_no"] = $row1["inword_no"];
                            $row["inword_date"] = $row2["entry_date"];
                            $sql3 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row2["vendor_no"]."'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $row["vendor_name"] = $row3["vendor_name"];
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }
            
            $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 =$conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["unit"] = $row2["unit"];
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
} else if ($_GET["type"]=="getCheckedGRN") {
    $sql = "SELECT * FROM grn WHERE status='checked'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM inword_material WHERE id=".$row1["inword_no"];
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["challan_no"] = $row2["challan_no"];
                            $row["challan_date"] = $row2["challan_date"];
                            $row["po_no"] = $row2["po_no"];
                            $row["po_date"] = $row2["po_date"];
                            $row["inword_no"] = $row1["inword_no"];
                            $row["inword_date"] = $row2["entry_date"];
                            $sql3 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row2["vendor_no"]."'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $row["vendor_name"] = $row3["vendor_name"];
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }
            
            $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 =$conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_name"] = $row2["material_name"];
                            $row1["unit"] = $row2["unit"];
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
} else if($_GET["type"]=="updatePendingGRN") {
    $sql = "UPDATE grn SET check_remark='".$_GET["remark"]."', status='".$_GET["action"]."', check_date='".$entry_date."', check_by='".$_GET["emp_id"]."' WHERE grn_no='".$_GET["grn_no"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"]=="updateCheckedGRN") {
    $sql = "UPDATE grn SET approve_remark='".$_GET["remark"]."', status='".$_GET["action"]."', approve_date='".$entry_date."', approve_by='".$_GET["emp_id"]."' WHERE grn_no='".$_GET["grn_no"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
        
        $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$_GET["grn_no"]."'";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $s_no1 = 0;
            	$s_no = '';
            	$sql = "SELECT IFNULL(MAX(s_no1), 0) as s_no1 FROM sampling";
            	$result = $conn->query($sql);
            	if ($result->num_rows > 0) {
            	    while ($row = $result->fetch_assoc()) {
            	        $s_no1 = $row["s_no1"];
            	    }
            	}
            	$s_no1++;
            	$no = strlen($s_no1);
            	if ($no == 1) {
            	    $s_no = "S-000".$s_no1;
            	} else if ($no == 2) {
            	    $s_no = "S-00".$s_no1;
            	} else if ($no == 3) {
            	    $s_no = "S-0".$s_no1;
            	} else if ($no == 4) {
            	    $s_no = "S-".$s_no1;
            	}
            	
                $sql2 = "INSERT INTO sampling (grn_no, sampling_no, material_code, total_containers, s_no1) VALUES ('".$_GET["grn_no"]."', '$s_no', '".$row1["material_code"]."', '".$row1["containers"]."', $s_no1)";
                $conn->query($sql2);
            }
        }
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getGRNReport") {
    $sql = "SELECT * FROM grn";
    $output = Array();
    $result = $conn->query($sql);
    $no = $result->num_rows;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["manufacturer"] = $row1["manufacturer"];
                    $sql2 = "SELECT * FROM inword_material WHERE id=".$row1["inword_no"];
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["challan_no"] = $row2["challan_no"];
                            $row["challan_date"] = $row2["challan_date"];
                            $row["po_no"] = $row2["po_no"];
                            $row["po_date"] = $row2["po_date"];
                            $row["inword_no"] = $row1["inword_no"];
                            $row["inword_date"] = $row2["entry_date"];
                            $sql3 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row2["vendor_no"]."'";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                while ($row3 = $result3->fetch_assoc()) {
                                    $row["vendor_name"] = $row3["vendor_name"];
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }
            
            $sql1 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row1["material_type"] = $row2["material_type"];
                            $row1["material_name"] = $row2["material_name"];
                            $row1["material_grade"] = $row2["grade"];
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
} else if ($_GET["type"]=="getPendingReceiving") {
    $sql = "SELECT * FROM material_received WHERE status='pending'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM inword_material WHERE id=".$row["inword_no"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["po_no"] = $row1["po_no"];
                    $row["po_date"] = $row1["po_date"];
                    $row["vendor_no"] = $row1["vendor_no"];
                    $row["challan_no"] = $row1["challan_no"];
                    $row["challan_date"] = $row1["challan_date"];
                }
            }
            
            $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }
            
            $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_type"] = $row2["material_type"];
		            $row["material_subtype"] = $row2["material_subtype"];
		            $row["grade"] = $row2["grade"];
		            $row["material_name"] = $row2["material_name"];
		        }
		    }
				    
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getCheckedReceiving") {
    $sql = "SELECT * FROM material_received WHERE status='checked'";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM inword_material WHERE id=".$row["inword_no"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["po_no"] = $row1["po_no"];
                    $row["po_date"] = $row1["po_date"];
                    $row["vendor_no"] = $row1["vendor_no"];
                    $row["challan_no"] = $row1["challan_no"];
                    $row["challan_date"] = $row1["challan_date"];
                }
            }
            
            $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }
            
            $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_type"] = $row2["material_type"];
		            $row["material_subtype"] = $row2["material_subtype"];
		            $row["grade"] = $row2["grade"];
		            $row["material_name"] = $row2["material_name"];
		        }
		    }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="updateReceivingMaterial") {
    $sql = "UPDATE material_received SET checker_remark='".$_GET["remark"]."', check_by='".$_GET["emp_id"]."', check_date='".$entry_date."', status='".$_GET["action"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="updateCheckedReceivingMaterial") {
    $sql = "UPDATE material_received SET approver_remark='".$_GET["remark"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."', status='".$_GET["action"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        $sql1 = "SELECT * FROM material_received WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql1);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "INSERT INTO stock_book (material_code, receiving_no, qty,unit,expiry_date, status, entry_by, entry_date) VALUES ('".$row["material_code"]."', '".$row["receiving_no"]."', ".$row["qty_received"].", '".$row["unit"]."', '".$row["exp_date"]."', 'Quarantine', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql1);
            }
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="updateCheckedReceivingMaterial") {
    $sql = "UPDATE material_received SET approve_remark='".$_GET["remark"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."', status='".$_GET["action"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="getApprovedReceiving") {
    $sql = "SELECT * FROM material_received WHERE status='approve' AND dedusting_status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM inword_material WHERE id=".$row["inword_no"];
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["po_no"] = $row1["po_no"];
                    $row["po_date"] = $row1["po_date"];
                    $row["vendor_no"] = $row1["vendor_no"];
                    $row["challan_no"] = $row1["challan_no"];
                    $row["challan_date"] = $row1["challan_date"];
                }
            }
            
            $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["vendor_name"] = $row1["vendor_name"];
                }
            }
            
            $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_type"] = $row2["material_type"];
		            $row["material_subtype"] = $row2["material_subtype"];
		            $row["grade"] = $row2["grade"];
		            $row["material_name"] = $row2["material_name"];
		        }
		    }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getPendingDedustings") {
    $sql = "SELECT * FROM dedusting_material WHERE status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM inword_material WHERE id=".$row1["inword_no"];
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["po_no"] = $row2["po_no"];
                            $row["po_date"] = $row2["po_date"];
                            $row["vendor_no"] = $row2["vendor_no"];
                            $row["challan_no"] = $row2["challan_no"];
                            $row["challan_date"] = $row2["challan_date"];
                        }
                    }
                    
                    $sql2 = "SELECT * FROM material WHERE material_code='".$row1["material_code"]."'";
        		    $result2 = $conn->query($sql2);
        		    if ($result2->num_rows > 0) {
        		        while ($row2 = $result2->fetch_assoc()) {
        		            $row["material_type"] = $row2["material_type"];
        		            $row["material_subtype"] = $row2["material_subtype"];
        		            $row["grade"] = $row2["grade"];
        		            $row["material_name"] = $row2["material_name"];
        		        }
        		    }
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="updatePendingDedusting"){
    $sql = "UPDATE dedusting_material SET status='".$_GET["action"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveCalibration") {
    $sql = "INSERT INTO balance_calibration (department, balance_id, location, weight1, weight2, weight3, weight4, weight5, status, entry_by, entry_date) VALUES ('".$_GET["department"]."','".$input["balance_id"]."', '".$input["location"]."', '".$input["weight1"]."', '".$input["weight2"]."', '".$input["weight3"]."', '".$input["weight4"]."', '".$input["weight5"]."', 'pending', '".$_GET["emp_id"]."', '".$entry_date."')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "dailyVerification") {
    $sql = "INSERT INTO balance_calibration (department, balance_id, weight1, weight2, weight3, weight4, weight5, status, entry_by, entry_date) VALUES ('".$_GET["department"]."','".$input["balance_id"]."', '".$input["weight1"]."', '".$input["weight2"]."', '".$input["weight3"]."', '".$input["weight4"]."', '".$input["weight5"]."', 'pending', '".$_GET["emp_id"]."', '".$entry_date."')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getPendingBalanceCalibration") {
    $sql = "SELECT * FROM balance_calibration WHERE status='pending'";
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT capacity FROM equipment WHERE equipment_code='".$row["balance_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["capacity"] = $row1["capacity"];
                    $row["working_range"] = $row1["min_capacity"] . " to " . $row1["max_capacity"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getLocations") {
    $sql ="SELECT location FROM balance_calibration WHERE status='pending' GROUP BY location";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT balance_id FROM balance_calibration WHERE location='".$row["location"]."' AND status='pending'";
            $output1 = Array();
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["balance"] = $output1;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updatePendingBalanceCalibration") {
    $sql = "UPDATE balance_calibration SET status='".$_GET["action"]."', check_by='".$_GET["emp_id"]."', check_date='".$entry_date."' WHERE balance_id='".$_GET["balance_id"]."' AND entry_date='".$_GET["entry_date"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "updateCheckedBalanceCalibration") {
    $sql = "UPDATE balance_calibration SET status='".$_GET["action"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE balance_id='".$_GET["balance_id"]."' AND entry_date='".$_GET["entry_date"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "DispendingLineClearance") {
    $cid = 0;
    $sql = "SELECT MAX(cid) as cid FROM lineclearance";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cid = $row["cid"];
        }
    }
    $cid++;
    $clearance_no = "LC-".$cid;
    $sql = "INSERT INTO lineclearance (clearance_no, department, section, activity, material_no, batch_no, lot_no, request_by, request_date, status, cid) VALUES ('".$clearance_no."', 'Store', 'Dispensing', 'Dispensing of Material', '', '', '".$input["lot_no"]."', '".$_GET["emp_id"]."', '$entry_date', 'pending', $cid)";
    if ($conn->query($sql) === TRUE) {
        $sql = "UPDATE batch_planning SET dispensing_lineclearance='inprocess', clearance_no='".$clearance_no."' WHERE no='". $_GET["no"]."'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "saveMaintainanceNote") {
    $sql = "INSERT INTO maintainance_note (department, section, maintenance_type, urgency_level, equipment_code, problem, description, breakdown_time, breakdown_date, entry_by, entry_date) VALUES ('".$_GET["department"]."', '".$input["section"]."', '".$input["maintainance_type"]."', '".$input["urgency_level"]."','".$input["equipment_code"]."', '".$input["problem"]."', '".$input["description"]."', '".$input["breakdown_time"]."', '".$input["breakdown_date"]."', '".$_GET["emp_id"]."', '".$entry_date."')";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getMaintainanceNoteReport") {
    $output = Array();
    $sql = "SELECT * FROM maintainance_note";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM equipment WHERE equipment_code='".$row["equipment_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["equipment_name"] = $row1["equipment_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDispensingLog") {
    $sql = "SELECT * FROM dispensing_material";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingGeneralInwords") {
    $isgeneral = false;
    
	$sql = "SELECT * FROM inword_material where status='pending'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
		    
		    $sql1 = "SELECT * FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
			$result1 = $conn->query($sql1);
			if ($result1->num_rows > 0) {
			    while ($row1 = $result1->fetch_assoc()) {
			        $row["vendor_name"] = $row1["vendor_name"];
			    }
			}
			
			$sql1 = "SELECT * FROM po_material WHERE po_no='".$row['po_no']."' AND isreceive='No'";
			$result1 = $conn->query($sql1);
			$output1 = Array();
			if($result1->num_rows > 0) {
				while($row1 = $result1->fetch_assoc()){
				    
				    $sql2 = "SELECT * FROM purchaseorder WHERE po_no='".$row["po_no"]."'";
				    $result2 = $conn->query($sql2);
				    if ($result2->num_rows > 0) {
				        while ($row2 = $result2->fetch_assoc()) {
				            if ($row2["isgeneral"] == "yes") {
				                $isgeneral = true;
				            } else {
				                $isgeneral = false;
				            }
				        }
				    }
				    
				    $sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
				    $result2 = $conn->query($sql2);
				    if ($result2->num_rows > 0) {
				        while ($row2 = $result2->fetch_assoc()) {
				            $row1["material_type"] = $row2["material_type"];
				            $row1["material_name"] = $row2["material_name"];
				        }
				    }
				    
					$row3 = array_merge($row, $row1);
					$row3["id"] = $row["id"];
					
					if ($isgeneral == true) {
					    $output[] = $row3;
					}
				}
			}
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "raiseDeviation") {
    $d_no1 = 0;
    $sql = "SELECT IFNULL(MAX(d_no1), 0) as d_no1 FROM deviation";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $d_no1 = $row["d_no1"];
        }
    }
    $d_no1++;
    $deviation_no = "DEV-00".$d_no1;
    $sql = "INSERT INTO deviation (dev_no, department, format_no, form_no, material_type, material_code, mfg_date, batch_no, expiry_date, category, deviation_details, qa_decision, entry_by, entry_date, d_no1) VALUES ('$deviation_no', '".$_GET["department"]."', '21','".$input["receiving_no"]."', 'material', '".$input["material_code"]."', '".$input["mfg_date"]."', '".$input["batch_no"]."', '".$input["exp_date"]."', '".$input["category"]."', '".$input["deviation_details"]."', '".$input["qa_decision"]."', '".$_GET["emp_id"]."', '$entry_date', $d_no1)";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
        $sql = "UPDATE material_received SET deviation_status='inprocess', deviation_no='$deviation_no' WHERE receiving_no='".$input["receiving_no"]."'";
        $conn->query($sql);
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getWighingMaterialReport") {
    $output = Array();
    $sql = "SELECT * FROM weighing_containers GROUP BY receiving_no";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM weighing_containers WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["weights"] = $output1;
            
            $sql1 = "SELECT * FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_code"] = $row1["material_code"];
                    $row["pack_size"] = $row1["pack_size"];
                    $row["unit"] = $row1["unit"];
                    $row["batch_no"] = $row1["batch_no"];
                    $row["total_containers"] = $row1["total_containers"];
                    $row["total_damage"] = $row1["total_damage"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getStoreSections") {
    $output = Array();
    $sql = "SELECT * FROM section WHERE department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getMaintenanceHistory") {
    $output = Array();
    $sql = "SELECT * FROM maintainance_note WHERE department='".$_GET["department"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM equipment WHERE equipment_code='".$row["equipment_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["equipment_name"] = $row1["equipment_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveGeneralMaterial") {
    $sql = "INSERT INTO gm_receiving (challan_no, challan_date, po_no, po_date, inword_no, vendor_no, entry_by, entry_date) VALUES ('".$input["challan_no"]."', '".$input["challan_date"]."', '".$input["po_no"]."', '".$input["po_date"]."', '".$input["id"]."', '".$input["vendor_no"]."','".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) == TRUE) {
        $last_id = $conn->insert_id;
        $materials = $input["material"];
        for ($i = 0; $i < count($materials); $i++) {
            $data = $materials[$i];
            $sql1 = "INSERT INTO gm_materials (received_no, material_code, order_qty, received_qty) VALUES ('".$last_id."', '".$data["material_code"]."', '".$data["required_qty"]."', '".$data["received_qty"]."')";
            $conn->query($sql1);
        }
        echo "{\"status\":\"success\"}";
        $sql = "UPDATE inword_material SET status='active' WHERE id='".$input["id"]."'";
        $conn->query($sql);
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getGeneralmaterialReport") {
    $output = Array();
    $sql = "SELECT * FROM gm_receiving";
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
            $sql1 = "SELECT * FROM gm_materials WHERE received_no='".$row["id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $sql2 = "SELECT * FROM general_material WHERE material_no='".$row1["material_code"]."'";
				    $result2 = $conn->query($sql2);
				    if ($result2->num_rows > 0) {
				        while ($row2 = $result2->fetch_assoc()) {
				            $row1["material_type"] = $row2["material_type"];
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
} else if ($_GET["type"] == "updateGeneralMaterial") {
    $sql = "SELECT id FROM gm_receiving WHERE id='".$_GET["receiving_no"]."' AND status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $sql = "UPDATE gm_receiving SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id='".$_GET["receiving_no"]."'";
        if ($conn->query($sql) === TRUE) {
            if ($_GET["status"] == "approve") {
                $sql = "SELECT * FROM gm_materials WHERE received_no='".$_GET["receiving_no"]."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $sql1 = "INSERT INTO general_stock (receiving_no, material_code, received_qty, available_qty) VALUES ('".$_GET["receiving_no"]."', '".$row["material_code"]."', '".$row["received_qty"]."', '".$row["received_qty"]."')";
                        $conn->query($sql1);
                    }
                }
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else {
        echo "{\"status\":\"success\"}";
    }
} else if ($_GET["type"] == "getGeneralStock") {
    $output = Array();
    $sql = "SELECT * FROM general_stock";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql2 = "SELECT * FROM general_material WHERE material_no='".$row["material_code"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["material_type"] = $row2["material_type"];
		            $row["material_name"] = $row2["material_name"];
		        }
		    }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getDeviations") {
    $output = Array();
    $sql = "SELECT * FROM deviation WHERE department='Store'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["material_type"] == "material") {
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
    		    $result2 = $conn->query($sql2);
    		    if ($result2->num_rows > 0) {
    		        while ($row2 = $result2->fetch_assoc()) {
    		            $row["material_type"] = $row2["material_type"];
    		            $row["material_name"] = $row2["material_name"];
    		        }
    		    }
            }
		    
		    $sql2 = "SELECT * FROM z_forms WHERE id='".$row["format_no"]."'";
		    $result2 = $conn->query($sql2);
		    if ($result2->num_rows > 0) {
		        while ($row2 = $result2->fetch_assoc()) {
		            $row["form_name"] = $row2["form_name"];
		        }
		    }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateDeviation") {
    $sql = "UPDATE deviation SET status='".$_GET["status"]."' WHERE dev_no='".$_GET["dev_no"]."'";
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"]=="getSOP") {
	$sql = "SELECT * FROM z_forms where department='store'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
} else if ($_GET["type"] == "saveStock") {
    $sql = "INSERT INTO stock_book (receiving_no, grn_no, ar_no, material_code, qty, unit, expiry_date, isExport, status, entry_by, entry_date) VALUES ('direct', '".$input["grn_no"]."', '".$input["ar_no"]."', '".$input["material_code"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["expiry_date"]."', 'no', 'Approved', '".$_GET["emp_id"]."', '$entry_date')";
    if ($conn->query($sql) == TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getStoreBalances") {
    $output = Array();
    $sql = "SELECT * FROM equipment WHERE department='Store' AND category='Balance'";
    $result = $conn->query($sql);
	if($result->num_rows > 0) {
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="getPendingLabels"){
	$sql = "SELECT * FROM grn where label = 'pending' AND status='approve'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if($_GET["type"]=="getStockLocation") {
    $sql = "SELECT * FROM stock_book";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while ($row = $result->fetch_assoc()) {
		    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["material_name"] = $row1["material_name"];
		            $row["material_type"] = $row1["material_type"];
		            $row["material_grade"] = $row1["grade"];
		        }
		    }
		    
		    $sql1 = "SELECT inword_no, batch_no FROM material_received WHERE receiving_no='".$row["receiving_no"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["batch_no"] = $row1["batch_no"];
		            
		            $sql1 = "SELECT challan_no, challan_date, vendor_no FROM inword_material WHERE id='".$row1["inword_no"]."'";
        		    $result1 = $conn->query($sql1);
        		    if ($result1->num_rows > 0) {
        		        while ($row1 = $result1->fetch_assoc()) {
        		            $row["challan_no"] = $row1["challan_no"];
        		            $row["challan_date"] = $row1["challan_date"];
        		            
        		            $sql1 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row1["vendor_no"]."'";
                		    $result1 = $conn->query($sql1);
                		    if ($result1->num_rows > 0) {
                		        while ($row1 = $result1->fetch_assoc()) {
                		            $row["vendor_name"] = $row1["vendor_name"];
                		            break;
                		        }
                		    }
        		            break;
        		        }
        		    }
        		    break;
		        }
		    }
		    
		    $sql1 = "SELECT IFNULL(SUM(net_wt), 0) as issued_qty FROM dispensing_material WHERE material_code='".$row["material_code"]."' AND ar_no='".$row["ar_no"]."'";
		    $result1 = $conn->query($sql1);
		    if ($result1->num_rows > 0) {
		        while ($row1 = $result1->fetch_assoc()) {
		            $row["issued_qty"] = $row1["issued_qty"];
		        }
		    }
		    
		    $row["location"] = "";
		    
		    $output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] === "getRetest") {
    
    $sql = "SELECT sb.ar_no, t.material_code, sp.sample_status, sp.sampling_person, m.status as material_status, t.approve_date, t.status, m.material_name, t.entry_date, s.specification_no, s.retest_period FROM stock_book sb 
    JOIN testing t ON sb.ar_no = t.ar_no
    JOIN specification s ON s.specification_no = t.specification_no
    JOIN material m ON m.material_code = s.material_code
    JOIN sampling sp ON sp.sampling_no = t.sampling_no
    ";
    $result = $conn->query($sql);
    $data = array();
    if ($result ->num_rows > 0) {
        while($row = $result -> fetch_assoc()) {
            $retestDate = date('Y-m-d h:i:s', strtotime("+".$row["retest_period"]." months", strtotime($row["entry_date"])));
            $row["retest_date"] = $retestDate;
            $data[] = $row;
        }
    }
    echo json_encode($data);
    
} else if ($_GET["type"] === "getRetestGRN") {
    
    $sql = "SELECT sb.ar_no, t.material_code, sp.sample_status, sp.sampling_person, m.status as material_status, t.approve_date, t.status, m.material_name, t.entry_date, s.specification_no, s.retest_period FROM stock_book sb 
    JOIN testing t ON sb.ar_no = t.ar_no
    JOIN specification s ON s.specification_no = t.specification_no
    JOIN material m ON m.material_code = s.material_code
    JOIN sampling sp ON sp.sampling_no = t.sampling_no";
    $result = $conn->query($sql);
    $data = array();
    if ($result -> num_rows > 0) {
        while($row = $result -> fetch_assoc()) {
            $retestDate = date('Y-m-d h:i:s', strtotime("+".$row["retest_period"]." months", strtotime($row["entry_date"])));
            $row["retest"] = $retestDate;
            
            $datetime1 = new DateTime($retestDate);
            $datetime2 = new DateTime($entry_date);
            
            $difference = $datetime1->diff($datetime2);
            
            if ($difference->y == 0 && $difference->m == 0 && $difference->d <= 15) {
                $row["due_day"] = $difference->d.' days';
                   
                   $data[] = $row;
            }
        }
    }
    echo json_encode($data);
}
else if($_GET["type"] == "savereturngetpass"){
   echo "{\"status\":\"success\"}";
}
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>