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
    
    if ($_GET["type"] == "getProducts") {
        $output = array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM batch_formula WHERE product_code='".$_GET["product_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = array();
                        $sql2 = "SELECT * FROM bmr WHERE product_code='".$_GET["product_code"]."' AND bom_no='".$row1["id"]."' AND status='complete'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["batches"] = $output2;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM unit_materials WHERE mfr_no=(SELECT id FROM unitformula WHERE mfr_no='".$row1["mfr_no"]."')";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["materials"] = $output2;
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                if (count($output1) > 0) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDocuments") {
        $output = array();
        
        $temp = array();
        $temp["name"] = "Product Manual";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Process Validation Protocol";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Manufacturing work order";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Batch Manufacturing Record";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Packaging work order";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Batch Packing";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Batch Packing Record";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "In-Process product specification";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Finished product specification";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        $temp = array();
        $temp["name"] = "Shelf life specification";
        $temp["refrence_no"] = "";
        $output[] = $temp;
        
        echo json_encode($output);
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>