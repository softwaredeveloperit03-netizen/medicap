<?php

    require 'db.php';
    require 'token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
     $currentUrl =$_GET["description"];



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
 $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    // $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    // $conn->query($sql);
    
    if ($_GET["type"] == "") {
        session_start();
        $outgoing_id = $_SESSION['emp_id'];
        $sql = "SELECT * FROM employee WHERE NOT emp_id = {$outgoing_id} ORDER BY emp_id DESC";
        $query = mysqli_query($conn, $sql);
        $output = "";
        if(mysqli_num_rows($query) == 0){
            $output .= "No users are available to chat";
        }elseif(mysqli_num_rows($query) > 0){
            $sql2 = "SELECT * FROM messages WHERE (incoming_msg_id = {$row['emp_id']}
            OR outgoing_msg_id = {$row['emp_id']}) AND (outgoing_msg_id = {$outgoing_id} 
            OR incoming_msg_id = {$outgoing_id}) ORDER BY msg_id DESC LIMIT 1";
            $query2 = mysqli_query($conn, $sql2);
            $row2 = mysqli_fetch_assoc($query2);
            (mysqli_num_rows($query2) > 0) ? $result = $row2['msg'] : $result ="No message available";
            (strlen($result) > 28) ? $msg =  substr($result, 0, 28) . '...' : $msg = $result;
            if(isset($row2['outgoing_msg_id'])){
                ($outgoing_id == $row2['outgoing_msg_id']) ? $you = "You: " : $you = "";
            }else{
                $you = "";
            }
            ($row['status'] == "Offline now") ? $offline = "offline" : $offline = "";
            ($outgoing_id == $row['emp_id']) ? $hid_me = "hide" : $hid_me = "";

            $output .= '
            <a href="chat.php?id='. $row['emp_id'] .'">
                <div class="content">
                <img src="php/images/'. $row['img'] .'" alt="">
                <div class="details">
                    <span>'. $row['fname']. " " . $row['lname'] .'</span>
                    <p>'. $you . $msg .'</p>
                </div>
                </div>
                <div class="status-dot '. $offline .'"><i class="fas fa-circle"></i></div>
            </a>';
        }
        echo $output;
    }
        
}

?>