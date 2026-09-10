<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

   require '../db.php';
    require '../token.php';
    // require'tcpdf/tcpdf.php';
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
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


	if($_GET["type"]=="saveQuery") {
 
	         $input = $_POST;        
          
          $data = json_decode($input["data"], true);
           
        	if(isset($_FILES["photo"])) {
            $file_tmp =$_FILES['photo']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
            $file_name = $_GET["plant_id"].'-'.$entry_date."photo.".$file_ext;
            $photo =$file_name;
              
            move_uploaded_file($file_tmp,"../support/upload/".$file_name);
              }
	    
  $sql = "INSERT INTO support (entry_by, attachment, client_name, software_type, department, query_type, form_name, description, plant_id,url,entry_date) 
        VALUES ('".$_GET["loger_id"]."', '".$photo."', '".$input["client_name"]."', '".$input["software_type"]."', '".$_GET["department1"]."', '".$input["Querytype"]."',
        '".$input["form_name"]."', '".$input["description"]."', '".$_GET["plant_id"]."', '".$input["url"]."','$entry_date')";
                if($conn->query($sql)){
                	echo "{\"status\":\"success\"}";
                } else {
                	echo "{\"status\":\"failed\"}";
                }
        }
	if($_GET["type"]=="upDateDev") {
 
	           
          
   $sql = "update support set devloper='".$input["Devloper"]."' where id='".$_GET["id"]."' ";
                if($conn->query($sql)){
                	echo "{\"status\":\"success\"}";
                } else {
                	echo "{\"status\":\"failed\"}";
                }
        }
	if($_GET["type"]=="upDateerrortype") {
    
    if($input["error_type"]=='Other Issue'){
        
   $sql = "update support set error_type='".$input["error_type"]."',status='Sent to Support' where id='".$_GET["id"]."' ";
    }else{
        
   $sql = "update support set error_type='".$input["error_type"]."' ,status='Complete' where id='".$_GET["id"]."' ";
    }
	           
          
                if($conn->query($sql)){
                	echo "{\"status\":\"success\"}";
                } else {
                	echo "{\"status\":\"failed\"}";
                }
        }
	if($_GET["type"]=="GETsaveQuery") {
        $output = Array();
        $sql = "SELECT * from support order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }



// else{
//     echo 'no record';
// }
$conn->close();
?>