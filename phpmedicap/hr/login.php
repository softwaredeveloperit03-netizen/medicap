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
    
    // if ($_GET["type"] == "saveResignation") {
    //     $sql = "INSERT INTO resignation (plant_id,user_no, emp_id, subject, expected_notice, expected_releaving, reason, entry_date) 
    //     VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."', '".$_GET["emp_id"]."', '".$input["subject"]."', '".$input["expected_notice"]."', '".$input["expected_releaving"]."', '".$input["reason"]."', '$entry_date')";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
    if ($_GET["type"] == "saveResignation") {
        $sql = "INSERT INTO resignation (plant_id,resignation_type, emp_id, emp_name, department, designation, description, resignation_date, expected_releaving, entry_date) 
        VALUES ('".$_GET["plant_id"]."','".$input["resignation_type"]."', '".$input["emp_id"]."', '".$input["emp_name"]."', '".$input["department"]."', '".$input["designation"]."', '".$input["description"]."','".$input["resignation_date"]."','".$input["expected_releaving"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
    }
        else if ($_GET["type"] == "get_resignation") {
        $output = Array();
        $sql = "SELECT * FROM resignation";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getPendingResignations") {
        $output = array();
        $sql = "SELECT r.*, e.emp_name, e.department, e.designation FROM resignation r LEFT JOIN employee e 
        ON r.emp_id=e.emp_id 
       WHERE r.user_no='".$_GET["user_no"]."' AND r.status='pending'";
      // $sql = "SELECT r.*, e.emp_name, e.department, e.designation FROM resignation r LEFT JOIN employee e ON r.emp_id=e.emp_id 
        //order by 1 desc";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } else if($_GET["type"] == "submitResignationReport") {
        $sql = "UPDATE resignation SET resignation_status='".$input["resignation_status"]."', resignation_remark='".$input["resignation_remark"]."', releaving_date='".$input["releaving_date"]."' WHERE id='".$input["resignation_id"]."'";
    	if($conn->query($sql)===TRUE){
    		echo "{\"status\":\"success\"}";
    		$data = $input["clerance_department"];
    		foreach ($data as $key => $value) {
    		    if($value == true) {
        		    $sql1 = "INSERT INTO resignation_clearnace (plant_id,user_no,resignation_id, department,emp_id,subject,releaving_date,emp_name,designation) 
        		    VALUES ('".$_GET["plant_id"]."','".$_GET["user_no"]."','".$_GET["resignation_id"]."','".$_GET["department"]."','".$_GET["emp_id"]."',
        		   '".$_GET["subject"]."', '".$_GET["releaving_date"]."','".$_GET["emp_name"]."','".$_GET["designation"]."')";
        		    $conn->query($sql1);
    		    }
    		}
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
    } else if($_GET["type"] == "getResignationCleanrance") {
       $output = Array();
        $sql = "SELECT * FROM resignation_clearnace WHERE  status='pending'";
          $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		   $sql1 = "SELECT * FROM resignation WHERE id='".$row["resignation_id"]."'";
    		    $result1 = $conn->query($sql1);
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    $row["emp_id"] = $row1["emp_id"];
            		    $row["department"] = $row1["department"];
            		    $row["releaving_date"] = $row1["releaving_date"];
            		    $row["subject"] = $row1["subject"];
            		   
            	//	$sql2 = "SELECT emp_name, department, designation FROM employee WHERE emp_id='".$row["emp_id"]."'";
    		   // $result2 = $conn->query($sql2);
            	//if($result2->num_rows > 0){
            		//while($row2 = $result2->fetch_assoc()){
            		    //$row["emp_name"] = $row1["emp_name"];
            		    //$row["department"] = $row1["department"];
            		    //$row["designation"] = $row1["designation"];
            		   // break;
            		//}
            	//}
            
            	
    		    $output[] = $row;
            		}
            	}
    		}
    	}
    	echo json_encode($output);
    } else if($_GET["type"]=="appoveDepartmentClearence") {
        $sql = "UPDATE resignation_clearnace SET status='approve'WHERE resignation_id='".$input["reg_id"]."' 
        AND department ='".$input["department"]."' ";
        if($conn->query($sql)===TRUE){
            $sql1 = "SELECT * FROM resignation_clearnace WHERE resignation_id='".$input["reg_id"]."' 
            AND status='pending' ";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0){
                return;
            } else {
                $sql2 = "UPDATE resignation SET status='approve' WHERE id ='".$input["reg_id"]."' "; 
                $conn->query($sql2);
            }
    		 echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
    } else if($_GET["type"]=="getPendingExitInterview") {
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE status='approve' ";
        //AND exitinterview_by=''";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } else if($_GET["type"]=="saveExitInterview") {
        $sql = "UPDATE resignation SET is_real_reason='".$input["is_real_reason"]."', 
        interpersonal_disputes='".$input["interpersonal_disputes"]."', other_reason='".$input["other_reason"]."',
        retain_service='".$input["retain_service"]."',exitinterview_by='".$_GET["emp_id"]."',exitinterview_date='".$entry_date."' 
       WHERE id='".$input["id"]."'";
        if($conn->query($sql)===TRUE){
    		echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"failed\"}";
    	}
    } else if($_GET["type"]=="getPendingExperience") {
        $output = Array();
        $sql = "SELECT * FROM resignation WHERE status='approve' AND exp_letter='pending'"; //AND 	exitinterview_by !='' ";
        $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row['entry_date'] = date('Y-m-d', strtotime($row['entry_date']));
    		    $sql1 = "SELECT emp_name, department, designation FROM employee WHERE emp_id='".$row["emp_id"]."'";
    		    $result1 = $conn->query($sql1);
            	if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    $row["emp_name"] = $row1["emp_name"];
            		    $row["department"] = $row1["department"];
            		    $row["designation"] = $row1["designation"];
            		    break;
            		}
            	}
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if($_GET["type"]=="generateexpletter") {
        $sql = "UPDATE resignation SET exp_letter='generated', releaving_date = '".$input["releaving_date"]."' WHERE id='".$input["id"]."'";
        if($conn->query($sql)===TRUE){
            echo "{\"status\":\"success\"}";
            $sql1 = "UPDATE employee SET status='inactive' WHERE emp_id='".$input["emp_id"]."'";
            $conn->query($sql1);
        }
        else {
            echo "{\"status\":\"An error has occurred, Please try again.\"}";
        }
    }else if($_GET['type']=='getexperienceletter'){
        $output = array();
        $sql = "SELECT * FROM resignation WHERE exp_letter='generated' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $row['entry_date'] = date('d-m-Y', strtotime($row['entry_date']));
    		    $row['releaving_date'] = date('d-m-Y', strtotime($row['releaving_date']));
    		    $row['exitinterview_date'] = date('d-m-Y', strtotime($row['exitinterview_date']));
    		    $sql1 = "SELECT * FROM employee WHERE emp_id = '".$row['emp_id']."'";
    		    $result1 = $conn->query($sql1);
    	        if($result1->num_rows > 0){
    		        while($row1 = $result1->fetch_assoc()){
    		            $row['emp_name'] = $row1['emp_name'];
    		        }
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