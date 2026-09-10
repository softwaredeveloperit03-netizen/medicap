<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"];
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
    
    if($_GET["type"] == "saveDeviations"){
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
        $mfg_date = date('Y-m-d', strtotime($input['mfg_date']));
        $expiry_date = date('Y-m-d', strtotime($input['exp_date']));

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

        $sql = "INSERT INTO deviation (dev_no,i_no,deviation_for,observed_in,description,immediate_action,impact_assessment,impact_other,materials,products,equipments,departments,entry_by,entry_date) VALUES ('$dev_no','$i_no','".$input["deviation_for"]."','".json_encode($input["observed_in"])."','".$input["description"]."', '".$input["immediate_action"]."', '".$input["impact_assessment"]."','".$input["impact_other"]."','".json_encode($input["materials"])."','".json_encode($input["products"])."','".json_encode($input["equipments"])."','".json_encode($input["departments"])."','".$_GET["emp_id"]."','$entry_date' )";
       //echo $sql;
        if ($conn->query($sql)) {
            $data = $input["departmentlist"];
            for ($i = 0; $i < count($data); $i++) {
                $sql = "INSERT INTO deviation_comments (dev_no,departments) VALUES ('$dev_no','".$data[$i]."')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }} 
    else if($_GET["type"] == "DeviationList"){
        $output = Array();
        if(isset($_GET['dep'])){
            if(isset($_GET['fromdate'])){
                $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
            }else{
                $sql = "SELECT * FROM deviation WHERE status = '".$_GET['status']."' ORDER by id DESC";   
            }
        }else{
            $sql = "SELECT * FROM deviation WHERE status = '".$_GET['status']."' ORDER by id DESC";
        }
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
    }
    else if($_GET["type"] == "DeviationListUR"){
        $output = Array();
        $sql = "SELECT * FROM deviation ORDER by id DESC"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $commentslist = [];
    	        $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row['dev_no']."' AND department='".$_GET['department']."'";
            	$result1 = $conn->query($sql1);
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    $commentslist[] = $row1;
                    }
                    $row['commentslist'] = $commentslist;
                    $output[] = $row;
            	}
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "approveQAhead"){
        $sql = "UPDATE deviation SET status='approve',approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE dev_no='".$input["devno"]."'";
        if($conn->query($sql) === TRUE){
            $sql1 = "UPDATE deviation_comments SET comment= '".$input['comment']."',entry_date='".$entry_date."' WHERE department = '".$input['dep']."' AND dev_no = '".$input['devno']."'";
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if($_GET['type'] == "rejectQAhead"){
        $sql = "UPDATE deviation SET status='reject', reject_reason='".$input['reason']."' WHERE dev_no='".$input["devno"]."'";
        if($conn->query($sql) === TRUE){
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if($_GET["type"] == "updateDeviation"){
        $sql = "UPDATE deviation SET status='pending', entry_by='".$_GET["emp_id"]."', entry_date='".$entry_date."' WHERE dev_no='".$input["devno"]."'";
        if($conn->query($sql) === TRUE){
            echo "{\"status\":\"success\"}";
        }else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if($_GET['type'] == "approvedDeviations"){
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status = 'approve'";
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
    }
    else if($_GET["type"] == "getDeviation") {
        $output = Array();
        if(isset($_GET['fromdate']) && $_GET['category'] != ''){
            $sql = "SELECT * FROM deviation WHERE category = '".$_GET['category']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate']) && $_GET['category'] == ''){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
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
    else if($_GET["type"] == "getDeviationDept") {
        $output = Array();
        if(isset($_GET['fromdate']) && $_GET['category'] != ''){
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND category = '".$_GET['category']."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate']) && $_GET['category'] == ''){
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND  DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        }else if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."' ORDER by id DESC";
        } else{
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
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
    else if($_GET["type"] == "getPendingDeviations") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='pending' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);
                $row["departments"] = json_decode($row["departments"]);
                $row["observed_in"] = json_decode($row["observed_in"]);
                // $output1 = Array();
                // $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $output1[] = $row1;
                //     }
                // }
                // $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if($_GET["type"] == "getDeviationsLog") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='checked' AND departments LIKE'%EHS%' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["products"] = json_decode($row["products"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["materials"] = json_decode($row["materials"]);
                $row["departments"] = json_decode($row["departments"]);
                $row["observed_in"] = json_decode($row["observed_in"]);
                // $output1 = Array();
                // $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $output1[] = $row1;
                //     }
                // }
                // $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "checkDeviation") {
        $sql = "UPDATE deviation SET status='".$_GET["status"]."', check_remark='".$_GET["remark"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if($_GET["type"] == "getInprocessDeviations") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='inprocess' ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
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
    else if($_GET["type"] == "verifyDeviation") {
        $sql = "UPDATE deviation SET status='".$_GET["status"]."', verify_remark='".$_GET["remark"]."', verify_by='".$_GET["emp_id"]."', verify_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
   else if($_GET["type"] == "getPendingReview") {
        $output = Array();
        $sql = "SELECT * FROM deviation WHERE status='pending' AND departments LIKE'%EHS%'  ORDER by id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["products"] = json_decode($row["products"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["materials"] = json_decode($row["materials"]);
                $row["departments"] = json_decode($row["departments"]);
                $row["observed_in"] = json_decode($row["observed_in"]);

                // $output1 = Array();
                // $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
                // $result1 = $conn->query($sql1);
                // if ($result1->num_rows > 0) {
                //     while ($row1 = $result1->fetch_assoc()) {
                //         $output1[] = $row1;
                //     }
                // }
                // $row["departments"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET["type"] == "saveReview") {
        $sql = "UPDATE deviation_comments SET status='active', comment='".$_GET["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE dev_no='".$_GET["dev_no"]."'";
        if ($conn->query($sql)) {
            $sql1 = "SELECT * FROM deviation_comments WHERE status='pending' AND dev_no='".$_GET["dev_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows == 0) {
                $sql2 = "UPDATE deviation SET status='checked' WHERE dev_no='".$_GET["dev_no"]."'";
                $conn->query($sql2);
            }
            echo "{\"status\": true}";
        } else {
            echo "{\"status\": false}";
        }
    } 
    else if($_GET["type"] == "getCapaDeviations") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE capa='yes' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation WHERE capa='yes' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
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
    else if($_GET["type"] == "getCapaDeviationsDept") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND capa='yes' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation WHERE department='".$_GET["department"]."' AND capa='yes' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."'";
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
    else if($_GET["type"] == "getCheckedDeviations") {
        $output = Array();
        if(isset($_GET['fromdate'])){
            $sql = "SELECT * FROM deviation WHERE status='checked' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND  '".$_GET["todate"]."' ORDER by id DESC";
        }else{
            $sql = "SELECT * FROM deviation WHERE status='checked' ORDER by id DESC";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $row["product_details"] = json_decode($row["product_details"]);
                $row["equip_details"] = json_decode($row["equip_details"]);

                $output1 = Array();
                $sql1 = "SELECT * FROM deviation_comments WHERE dev_no='".$row["dev_no"]."' AND department !='Quality Assurance'";
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
    else if($_GET["type"] == "saveQAApproval") {
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

            $sql = "INSERT INTO capa (capa_no, department, required_to, category, form_no, entry_by, entry_date) VALUES ('$capa_no', '".$input["department"]."', '".$input["related_to"]."', 'Deviation', '".$input["dev_no"]."', '".$_GET["emp_id"]."', '$entry_date')";
            $conn->query($sql);
        }
        
        $sql = "UPDATE deviation SET status='".$input["status"]."', capa='".$input["capa"]."', capa_no='$capa_no', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', approve_remark='".$input["comment"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\": true}";
        } else {
            echo "{\"status\": false}";
        }
    }
    else if($_GET['type'] == 'getcategorychart'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, category  FROM deviation GROUP BY category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["category"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getcategorychartDept'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, category  FROM deviation WHERE department = '".$_GET['department']."' GROUP BY category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["category"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getdepartmentchart'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, department  FROM deviation GROUP BY department";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["department"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
} else {
    echo "Invalid Token";
}

$conn->close();
?>