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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "getRetestCalender") {
        $output = array();
        $effectiveDate = date('Y-m-d', strtotime("+15 days", strtotime($entry_date)));
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='Approved' AND retest_date BETWEEN DATE('".$entry_date."') AND '".$effectiveDate."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT material_code, batch_no, grn_no, retest_date FROM retest WHERE material_code='".$row["material_code"]."' AND batch_no='".$row["batch_no"]."' AND grn_no='".$row["grn_no"]."' AND retest_date='".$row["retest_date"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $date1 = new DateTime($row["retest_date"]);
                    $date2 = new DateTime(date("Y-m-d", $timestamp));
                    $interval = $date1->diff($date2);
                    $row["due_days"] = $interval->days;
                    $output[] = $row;
                }
            }
        }
        
        $effectiveDate = date('Y-m-d', strtotime("+15 days", strtotime($entry_date)));
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='Approved' AND s.id NOT IN (SELECT id FROM stock_book WHERE status='Approved' AND retest_date BETWEEN DATE('".$entry_date."') AND '".$effectiveDate."')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $date1 = new DateTime($row["retest_date"]);
                $date2 = new DateTime(date("Y-m-d", $timestamp));
                $interval = $date1->diff($date2);
                $row["due_days"] = $interval->days;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "allocateSamplingPerson") {
        $sql = "INSERT INTO retest (material_code, batch_no, grn_no, ar_no, release_date, retest_date, qty, unit, mfg_date, exp_date, qc_person, qc_alternate_person, micro_person, micro_alternate_person, entry_by, entry_date) VALUES ('".$input["material_code"]."', '".$input["batch_no"]."', '".$input["grn_no"]."', '".$input["ar_no"]."', '".$input["release_date"]."', '".$input["retest_date"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["qc_person"]."', '".$input["qc_alternate_person"]."', '".$input["micro_person"]."', '".$input["micro_alternate_person"]."', '".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success", "msg"=>"Sampling Person Allocated Successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getAwaitingSamplingRetests") {
        $output = array();
        $sql = "SELECT * FROM retest WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>