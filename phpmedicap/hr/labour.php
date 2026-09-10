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


if ($_GET["type"] == "saveLabour") {
    $input = $_POST;
    
    $id = 0;
    $sql = "SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'labour' LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["auto_increment"];
            break;
        }
    }

	$front_side = "";
	$back_side = "";
    $photo = "";
    
    $id11 = $input["labour_name"];
        	$target_dir = "../../../upload/contractor/labour/";
            
        
    if(isset($_FILES['front_side'])) {
        $file_tmp =$_FILES['front_side']['tmp_name'];
        $file_ext=strtolower(end(explode('.',$_FILES['front_side']['name'])));
        $file_name = $id11."front_side.".$file_ext;
        $front_side = $file_name;
        move_uploaded_file($file_tmp,"$target_dir".$file_name);
    }
    // if(isset($_FILES['back_side'])) {
    //     $file_tmp =$_FILES['back_side']['tmp_name'];
    //     $file_ext=strtolower(end(explode('.',$_FILES['back_side']['name'])));
    //     $file_name = $id11."back_side.".$file_ext;
    //     $back_side = $file_name;
    //     move_uploaded_file($file_tmp,"$target_dir".$file_name);
    // }
    if(isset($_FILES['photo'])) {
        $file_tmp =$_FILES['photo']['tmp_name'];
        $file_ext=strtolower(end(explode('.',$_FILES['photo']['name'])));
        $file_name = $id11."photo.".$file_ext;
        $photo = $file_name;
        move_uploaded_file($file_tmp,"$target_dir".$file_name);
    }

	$sql = "INSERT INTO labour (plant_id,labour_name,dob,address,contractor_name,daily_wages,gender,category,front_side, photo,entry_by,entry_date) 
	VALUES ('".$_GET["plant_id"]."','".$input["labour_name"]."','".$input["dob"]."','".$input["address"]."','".$input["contractor_name"]."','".$input["daily_wages"]."',
	'".$input["gender"]."','".$input["category"]."','$front_side','$photo','".$_GET["emp_id"]."','$entry_date')";
	if($conn->query($sql)){
		echo "{\"status\":\"success\"}";
	} else {
		echo "{\"status\":\"".$conn->error."\"}";
	}
} else if ($_GET["type"] == "getLaboursLog") {
    $output = array();
    $sql = "SELECT * FROM labour WHERE plant_id = '".$_GET["plant_id"]."'  order by id desc";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
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