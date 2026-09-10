<?php 

 
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($_GET["type"] == "getTestname") {
        
        $output = array();
        $sql = "SELECT * from test_medical";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    }

$conn->close();
?>