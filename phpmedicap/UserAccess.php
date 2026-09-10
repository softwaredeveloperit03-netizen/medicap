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
    // if($_GET["type"] == "UserAccess") {
    // print_r($_GET);exit;
    $output = Array();
    echo $sql = "SELECT * FROM emp_rights WHERE emp_id='v-302'";exit;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    // }
    echo json_encode($output);
}
?>