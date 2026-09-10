<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = $_GET["token"];
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0) { 
    
    
    
}  else{
    echo "Invalid Token";
}
?>