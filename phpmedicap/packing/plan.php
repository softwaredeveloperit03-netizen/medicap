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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getPendingPlans") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form FROM bpr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "savePlan") {
        $sql = "UPDATE bpr SET status='PLANNED', packing_for='".$input["client_code"]."', tenative_complete_date='".$input["tenative_date"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status"=>"success","msg"=>"Plan saved successfully!"));
        } else {
            echo json_encode(array("status"=>"failed", "msg"=>$conn->error));
        }
    } else if ($_GET["type"] == "getPlans") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.dosage_form, c.LglNm as packing_for FROM bpr b 
        LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN client c ON b.packing_for=c.client_code 
        WHERE b.status !='pending'";
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