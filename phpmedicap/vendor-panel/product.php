<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
    
   if ($_GET["type"] == "saveVendorQuotation") {
    $target_dir = "../upload/quotation/";
    
    $file_name = "";
    if(isset($_FILES["document"]["name"])){
        $target_file = $target_dir."quotation-".basename($_FILES["document"]["name"]);
        $file_name = "quotation-".basename($_FILES["document"]["name"]);
        move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
    }
    $sql = "INSERT INTO vendorquotation (user_no , materials,documents, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$_POST["materials"]."','$file_name','".$_GET["emp_id"]."','".$entry_date."')";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
  }else if($_GET["type"] == "getQuotationLog"){
      $output = array();
      $sql = "SELECT * FROM vendorquotation WHERE status= 'pending'";
       $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['materials'] = json_decode($row['materials']);
                    $output[] = $row;
                }
            }
    echo json_encode($output);
  }
    
    

}

$conn->close();