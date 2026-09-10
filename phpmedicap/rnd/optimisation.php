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
    
    if ($_GET["type"] == "getDevTrials") {
        $output = array();
        $sql = "SELECT d.*, p.product_name, p.grade, p.dosage_form FROM rnd_dev_trial d LEFT JOIN product p ON 
        d.product_code=p.product_code WHERE    d.optimisation='pending' 
        ORDER BY d.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM rnd_dev_trials WHERE dev_no='".$row["dev_no"]."' AND optimisation='yes'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["procedures"] = json_decode($row1["procedures"]);
                        $row["materials"] = json_decode($row1["materials"]);
                        $row["equipments"] = json_decode($row1["equipments"]);
                        $row["testings"] = json_decode($row1["testings"]);
                        $row["observations"] = json_decode($row1["observations"]);
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveInitialOptimisation") {
        $sql = "INSERT INTO rnd_dev_optimisation (user_no, product_code, trial_title, batch_size, unit, objective, procedures, materials, equipments, testings, observations, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."','".$input["trial_title"]."','".$input["batch_size"]."', '".$input["unit"]."','".$input["objective"]."','".json_encode($input["procedures"])."','".json_encode($input["materials"])."','".json_encode($input["equipments"])."','".json_encode($input["testings"])."','".json_encode($input["observations"])."', '".$_GET["emp_id"]."', '".$entry_date."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "UPDATE rnd_dev_trial SET optimisation='done' WHERE id='".$input["id"]."'";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingOptimisations") {
        $output = array();
        $sql = "SELECT d.*, p.product_name, p.grade, p.dosage_form FROM rnd_dev_optimisation d LEFT JOIN product p
        ON d.product_code=p.product_code WHERE d.user_no='".$_GET["user_no"]."' AND d.status='pending'   ORDER BY d.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["procedures"] = json_decode($row["procedures"]);
                $row["materials"] = json_decode($row["materials"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["testings"] = json_decode($row["testings"]);
                $row["observations"] = json_decode($row["observations"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveOptimisationTrial") {
        $sql = "INSERT INTO rnd_optimisation_trials (user_no, product_code, dev_no, procedures, materials, equipments, testings, observations, optimisation, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."','".$input["dev_no"]."','".json_encode($input["procedures"])."','".json_encode($input["materials"])."','".json_encode($input["equipments"])."','".json_encode($input["testings"])."','".json_encode($input["observations"])."', '".$input["optimisation"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            if ($input["optimisation"] == "yes") {
                $sql = "UPDATE rnd_dev_optimisation SET status='approve', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE dev_no='".$input["dev_no"]."'";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getOptimisationsLog") {
        $output = array();
        $sql = "SELECT d.*, p.product_name, p.grade, p.dosage_form FROM rnd_dev_optimisation d LEFT JOIN product p ON 
        d.product_code=p.product_code WHERE   d.status='approve'  ORDER BY d.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["procedures"] = json_decode($row["procedures"]);
                $row["materials"] = json_decode($row["materials"]);
                $row["equipments"] = json_decode($row["equipments"]);
                $row["testings"] = json_decode($row["testings"]);
                $row["observations"] = json_decode($row["observations"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM rnd_optimisation_trials WHERE dev_no='".$row["dev_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["procedures"] = json_decode($row1["procedures"]);
                        $row1["materials"] = json_decode($row1["materials"]);
                        $row1["equipments"] = json_decode($row1["equipments"]);
                        $row1["testings"] = json_decode($row1["testings"]);
                        $row1["observations"] = json_decode($row1["observations"]);
                        $output1[] = $row1;
                    }
                }
                $row["trials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>