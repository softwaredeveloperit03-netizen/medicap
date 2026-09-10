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
    
    
if(isset($_FILES["lic"])) {
            $file_tmp =$_FILES['proof']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['proof']['name'])));
            $file_name = $id."proof.".$file_ext;
            $lic = $file_name;
            move_uploaded_file($file_tmp," /upload/expaince/".$file_name);
        }

 
if ($_GET["type"] == "saveExpense") {
  echo $sql = "INSERT INTO saveExpense (plant_id,proof, pay_mode, amount, expense_type) VALUES ('".$_GET["plant_id"]."','".$input["proof"]."','".$input["pay_mode"]."','".$input["amount"]."','".$input["expense_type"]."' )";      
  
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }







/* else if ($_GET["type"] == "getPendingPlans") {
    $output = Array();
    $sql = "SELECT * FROM batch_plan WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["product_name"] = $row1["product_name"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}*/ else if ($_GET["type"] == "updatePlan") {
    $sql = "UPDATE batch_plan SET status='approve', approve_by='".$_GET["id"]."' ";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}
else if ($_GET["type"] == "getExpensesLog") {
    $output = array();
// 		$sql = "  SELECT * FROM emtryfrom where status = 'Approve' ";
        $sql = "SELECT * FROM saveExpense  ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc())  {
             $output[] = $row;
        }
    }
    echo json_encode($output);
}   



else if ($_GET["type"] == "getPendingPlans") {
    $output = array();
    $sql = "SELECT * FROM production_plan WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["dosage_form"] = $row1["dosage_form"];
                    $row["product_name"] = $row1["product_name"];
                    $row["grade"] = $row1["grade"];
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
}  


}

$conn->close();
?>