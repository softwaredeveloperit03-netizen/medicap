<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
    

   

// ini_set('display_errors', 1);
// error_reporting(E_ALL);



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
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="getPendingReview") { 
        
	$output = Array();
 
        	$sql = "select *,a.departments as dep_review,a.impact_other,b.id as b_id,(select count(status)
        	from deviation_comments where departments like '%".$_GET['dept']."%' and dev_no=a.deviation_no 
        	and status='pending') as status_count  from deviation a left join deviation_comments b on
        	a.deviation_no=b.dev_no where b.departments like '%".$_GET['dept']."%' having status_count!=0";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		     $row["batches"] = json_decode($row["batches"]); 
    			$output[] = $row;
    		}
    	}
	
 	echo json_encode($output);

        
        
        
    }
   else  if ($_GET["type"]=="get_deviation_files") {
	$output = Array();
 
       $sql = "SELECT * FROM deviation_files WHERE deviation_no='".$_GET["deviation_no"]."'";
      $result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
}
			
			
 
$conn->close();
?>