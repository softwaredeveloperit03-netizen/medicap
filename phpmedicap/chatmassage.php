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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    // if ($_GET["type"] == "getmassage") {
    //     $output = array();
    //     $sql = "Select * FORM token WHERE user_no='".$_GET["user_no"]."'";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    session_start();

    $data = array(
     ':to_user_id'  => $_POST['to_user_id'],
     ':from_user_id'  => $_SESSION['user_id'],
     ':chat_message'  => $_POST['chat_message'],
     ':status'   => '1'
    );

    if($_GET["type"]=="saveMessage"){
        $sql = "INSERT INTO chat_message (to_user_id, from_user_id, chat_message, status) VALUES ($to_user_id,$from_user_id,$chat_message,$status)";
        $statement = $connect->prepare($sql);
        if($statement->execute($data)){
            echo fetch_user_chat_history($_SESSION['user_id'], $_POST['to_user_id'], $connect);
        }
    }
    else if($_GET["type"]=="getmessage"){
        $sql="SELECT * FROM chat_message WHERE (from_user_id = '".$from_user_id."' AND to_user_id = '".$to_user_id."') OR (from_user_id = '".$to_user_id."' AND to_user_id = '".$from_user_id."') ORDER BY timestamp DESC ";
        $statement=$connect->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();
        $output = '<ul class="list-unstyled">';
        foreach($result as $row)
        {
          $user_name = '';
          if($row["from_user_id"] == $from_user_id)
          {
           $user_name = '<b class="text-success">You</b>';
          }
          else
          {
           $user_name = '<b class="text-danger">'.get_user_name($row['from_user_id'], $connect).'</b>';
          }
          $output .= '
          <li style="border-bottom:1px dotted #ccc">
           <p>'.$user_name.' - '.$row["chat_message"].'
            <div align="right">
             - <small><em>'.$row['timestamp'].'</em></small>
            </div>
           </p>
          </li>';
        }
    }
    
}

$conn->close();
?>