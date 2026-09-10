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
    if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    try{
    if($_GET["type"]=="getrejection") {
        $sql = "SELECT * FROM rejection_online WHERE department= '".$_GET['department']."' AND status = 'pending' ORDER by id DESC";
        $result = $conn->query($sql);
        $output = Array();
        if($result->num_rows > 0){
    	    while($row = $result->fetch_assoc()){
    	        if($row['approve_by'] = '' ){
    	            $row['dep_status'] = 'Approved';
    	        }else{
    	            $row['dep_status'] = 'Pending';
    	        }
    		    $output[] = $row;
    	    }
        }
        echo json_encode($output);
    }
    else if($_GET["type"]=="onlinerejectionapproval") {
	  echo  $sql = "SELECT * FROM rejection_online WHERE approve_by='' ORDER by id DESC";
	    $result = $conn->query($sql);
	    $output = Array();
	    if($result->num_rows > 0){
		    while($row = $result->fetch_assoc()){
			    $output[] = $row;
		    }
	    }
	    echo json_encode($output);
	}
	else if($_GET["type"]=="onlinerejectionapprove") {
	    $sql = "UPDATE rejection_online SET approve_by='".$_GET['emp_id']."',approve_date='$entry_date' WHERE id='".$_GET['id']."'";
	    if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
	}
	 else if ($_GET["type"] == "upreject") {

        $sql = "UPDATE rejection_online SET status='Reject'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
	else if($_GET["type"]=="onlinerejectionqaapproval") {
	    $sql = "SELECT * FROM rejection_online WHERE approve_by!='' AND qa_approve_by ='' ORDER by id DESC";
	    $result = $conn->query($sql);
	    $output = Array();
	    if($result->num_rows > 0){
		    while($row = $result->fetch_assoc()){
			    $output[] = $row;
		    }
	    }
	    echo json_encode($output);
	}
	else if($_GET["type"]=="onlinerejectionqaapprove") {
	    $sql = "UPDATE rejection_online SET qa_approve_by='".$_GET['emp_id']."',qa_approve_date='$entry_date' WHERE id='".$_GET['id']."'";
	    if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
	}
    else if($_GET["type"]=="saveonlinerejection") {
    	$sql = "INSERT INTO rejection_online(department,material_type,product_name, grade, batch_no, batch_size, lot_no, stage, equipment_name, equipment_id,rejected_quantity,rejection_type,description,entry_by,entry_date)
    	VALUES ('".$_GET["department"]."','".$input['material_type1']."','".$input['product_name']."','".$input['grade']."','".$input['batch_no']."','".$input['batch_size']."','".$input['lot_no']."','".$input['stage']."','".$input['equipment_name']."','".$input['equipment_id']."','".$input['rejected_quantity']."','".$input['rejection_type']."','".$input['description']."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
         else if($_GET['type']=='getonlinerejection') {
        $sql = "SELECT * FROM rejection_online"; 
        $result = $conn->query($sql);
        $output = Array();
        if($result->num_rows > 0){
    	    while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    	    }
        }
        echo json_encode($output);
    }
    else if($_GET["type"]=="rawpackingrejectionreport") {
    	    $sql = "SELECT * FROM rejection_rawpacking";
    	    $result = $conn->query($sql);
    	    $output = Array();
    	    if($result->num_rows > 0){
    		    while($row = $result->fetch_assoc()){
    			    $output[] = $row;
    		    }
    	    }
    	    echo json_encode($output);
        
    }
    else if($_GET["type"]=="onlinerejectionreport") {
    	    $sql = "SELECT * FROM rejection_online";
    	    $result = $conn->query($sql);
    	    $output = Array();
    	    if($result->num_rows > 0){
    		    while($row = $result->fetch_assoc()){
    		        $row['quantity_rejected'] = $row['rejected_quantity'];
    			    $output[] = $row;
    		    }
    	    }
    	    echo json_encode($output);
    }
    else if($_GET["type"]=="getdestruction") {
        $output = Array();
        $sql = "SELECT * FROM rejection_online WHERE qa_approve_by !='' AND department='".$_GET['department']."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
    	    while($row = $result->fetch_assoc()){
    	        $datarow['type'] = 'online';
    	        $datarow['material_type'] = $row['material_type'];
    	        $datarow['product_name'] = $row['product_name'];
    	        $datarow['rejection_type'] = $row['rejection_type'];
    	        $datarow['rejected_quantity'] = $row['rejected_quantity'];
    	        $datarow['stage'] = $row['stage'];
    	    }
            $output[] = $datarow;
        }
        $sql1 = "SELECT * FROM rejection_rawpacking WHERE remark = 'destroy' AND qa_approve_by !='' AND department='".$_GET['department']."'";
        $result1 = $conn->query($sql1);
        if($result1->num_rows > 0){
    	    while($row1= $result1->fetch_assoc()){
    	        $datarow1['type'] = 'rawpacking';
    		    $datarow1 = $row1;
    	    }
            $output[] = $datarow1;
        }
        echo json_encode($output);
    }
    else if($_GET['type']=='getreturn') {
        $sql = "SELECT * FROM rejection_rawpacking WHERE remark = 'return' AND store_approve_by=''";
        $result = $conn->query($sql);
        $output = Array();
        if($result->num_rows > 0){
    	    while($row = $result->fetch_assoc()){
    		    $output[] = $row;
    	    }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getonlinechart"){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, rejection_type  FROM rejection_online GROUP BY rejection_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0){
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["rejection_type"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getdestructionchart'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, rejection_type  FROM rejection_online GROUP BY rejection_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0){
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["rejection_type"].' ('.$row["total"].')';
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }
}catch(Exception $e) {
  //echo 'Exception: ' .$e->getMessage();
    echo "{\"Exception\":\"".$e->getMessage()."\"}";
}
}

$conn->close(); 

?>