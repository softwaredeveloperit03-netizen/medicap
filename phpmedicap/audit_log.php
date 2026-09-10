<?php

require 'db.php';  
try{
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
   $conn->query($sql);
    
}
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}
?>