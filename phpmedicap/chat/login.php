<?php 
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
     $currentUrl =$_GET["description"];



    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);

if ($_GET["type"] == "chatlogin") {
    session_start();
    
    $sql = "SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."' AND password='".$_GET["password"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        // echo json_encode(array("status"=>"success","emp_id"=>$_GET["emp_id"]));
        $sql1 = "UPDATE employee SET status1='active' WHERE emp_id='".$_GET["emp_id"]."'";
        if ($conn->query($sql1)) {
           echo json_encode(array("status"=>"active","emp_id"=>$_GET["emp_id"]));
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else {
        echo "failed";
    }
}else if ($_GET["type"] == "getAllEmployees") {
        $output = array();
        $sql = "SELECT * FROM employee WHERE status IN ('active', 'approve') AND firstname LIKE '%".$_GET['firstname']."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
?>