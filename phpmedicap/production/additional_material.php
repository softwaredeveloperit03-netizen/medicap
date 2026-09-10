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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getadditional") {
        $output = Array();
        $sql = "SELECT * FROM additional_material";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveAdditional") {
        
        $sql = "INSERT INTO additional_material (plant_id,material_type,material_name, material_code, product, product_name, qty, purpose, justification) VALUES ('".$_GET['plant_id']."','".$input["material_type"]."', '".$input["material_name"]."', '".$input["material_code"]."','".$input["product"]."','".$input["product_name"]."', '".$input["qty"]."','".$input["purpose"]."', '".$input["justification"]."' )";
        
        if ($conn->query($sql)) 
        {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "approveAdditional"){

        $sql = "UPDATE additional_material SET status='Approved'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    
    else if ($_GET["type"] == "get_additional_materials"){
        
	$output = Array();
	
    	$sql = "select * from additional_material where status!='' ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
    else if ($_GET["type"] == "rejectAdditional"){
       
         $sql = "UPDATE additional_material SET status='Rejected'  WHERE id='" . $_GET["id"] . "'";
      
        $conn->query($sql);

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    else if ($_GET["type"] == "approve_by_qa"){

        $sql = "UPDATE additional_material SET qa_status='Approved'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    else if ($_GET["type"] == "getApprovmaterials"){
 
	$output = Array();
	if ($_GET["department_name"] == 'undefined') {
            $_GET['department_name'] = '';
            
        }
        	$sql = "select * from additional_material where status='Approved' ";

    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

  }
    else if ($_GET["type"] == "getApprove_qa_materials"){
 
	$output = Array();
	if ($_GET["department_name"] == 'undefined') {
            $_GET['department_name'] = '';
            
        }
        	$sql = "select * from additional_material where qa_status='Approved' ";

    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

  }
  
  else if ($_GET["type"] == "requestmaterial"){

        $sql = "UPDATE additional_material SET store_status='Reject'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
  else if ($_GET["type"] == "requestmaterial_approved"){

        $sql = "UPDATE additional_material SET store_status='Accept'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
  else if ($_GET["type"] == "request_acceptmaterial"){

        $sql = "UPDATE additional_material SET store_status='pending'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
  else if ($_GET["type"] == "saveIssuance"){

        $sql = "UPDATE additional_material SET store_status='Accept', dispensed_qty= '".$input["dispensed_qty"]."'
        ,unit='".$input["unit"]."'
        ,dispend_by='".$input["dispend_by"]."'
        ,ar_no='".$input["ar_no"]."'
        WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    
     else if ($_GET["type"] == "get_request_materials"){
        
	$output = Array();
	
    	$sql = "select * from additional_material where store_status='Reject' or store_status='Accept' ";
    	
    	$result = $conn->query($sql);
    	
    	if($result->num_rows > 0)
    	{
    		while($row = $result->fetch_assoc())
    		{
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
     else if ($_GET["type"] == "get_request_materials_for_approval"){
        
	$output = Array();
	
    	$sql = "select * from additional_material where store_status='pending' ";
    	
    	$result = $conn->query($sql);
    	
    	if($result->num_rows > 0)
    	{
    		while($row = $result->fetch_assoc())
    		{
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
     else if ($_GET["type"] == "get_product_list"){
        
	$output = Array();
	
    	$sql = "SELECT a.*,c.product_code,c.product_name FROM mfg_work_order_hdr a left join batch_planning b on a.batch_plan_id=b.id left join product c on b.product_code=c.product_code WHERE a.tr_to_packing_dept_by=''  ";
    	
    	$result = $conn->query($sql);
    	
    	if($result->num_rows > 0)
    	{
    		while($row = $result->fetch_assoc())
    		{
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
    
    else if ($_GET["type"] == "getissuance") {
        
        $output = Array();
        $sql = "SELECT * FROM issuance_material";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "get_receving_materials"){
        
	$output = Array();
	
    	$sql = "select * from additional_material where store_status='Accept' ";
    	
    	$result = $conn->query($sql);
    	
    	if($result->num_rows > 0)
    	{
    		while($row = $result->fetch_assoc())
    		{
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
    else if ($_GET["type"] == "acceptreceving")    {

      echo  $sql = "UPDATE additional_material SET receving_status='Accept'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    else if ($_GET["type"] == "get_receving"){
        
	$output = Array();
	
    	$sql = "select * from additional_material where receving_status='Accept' ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
    
    
  
  
  
}
$conn->close();
?>