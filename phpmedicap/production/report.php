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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


    if($_GET["type"]=="getFinishGoodsChartData") {
        $output = Array();
        $sql = "SELECT DISTINCT(product_code) as product_code FROM finish_product";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT MONTH(entry_date) as month, count(id) as total, SUM(qty) as qty, unit FROM finish_product WHERE product_code='".$row["product_code"]."' GROUP BY MONTH(entry_date)";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["month"] == 1) {
                            $row1["month"] = "January";
                        } else if ($row1["month"] == 2) {
                            $row1["month"] = "February";
                        } else if ($row1["month"] == 3) {
                            $row1["month"] = "March";
                        } else if ($row1["month"] == 4) {
                            $row1["month"] = "April";
                        } else if ($row1["month"] == 5) {
                            $row1["month"] = "May";
                        } else if ($row1["month"] == 6) {
                            $row1["month"] = "June";
                        } else if ($row1["month"] == 7) {
                            $row1["month"] = "July";
                        } else if ($row1["month"] == 8) {
                            $row1["month"] = "August";
                        } else if ($row1["month"] == 9) {
                            $row1["month"] = "September";
                        } else if ($row1["month"] == 10) {
                            $row1["month"] = "October";
                        } else if ($row1["month"] == 11) {
                            $row1["month"] = "November";
                        } else if ($row1["month"] == 12) {
                            $row1["month"] = "December";
                        }
                        $output1[] = $row1;
                    }
                }
                $row["data"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCompletedBatches") {
        $output = Array();
        $sql = "SELECT b.*, p.dosage_form, p.product_name, p.grade FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='complete' AND b.product_code LIKE '%".$_GET["product_code"]."%' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND DATE(b.complete_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM product WHERE status='approve' AND dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["products"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTemperatureLog") {
        $output = array();
        $sql = "SELECT *, DATE(entry_date) as entry_date, TIME(entry_date) as entry_time FROM temperature WHERE user_no='".$_GET["user_no"]."' AND department='Production' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPressuresLogs") {
        $output = array();
        $sql = "SELECT *, DATE(entry_date) as entry_date, TIME(entry_date) as entry_time FROM pressure WHERE user_no='".$_GET["user_no"]."' AND department='Production' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
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