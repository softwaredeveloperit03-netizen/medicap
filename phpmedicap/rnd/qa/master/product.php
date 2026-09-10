<?php
    require '../../../db.php';
    require '../../../token.php';
    require '../../../tcpdf/tcpdf.php';
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
    $myfile = file_put_contents('../../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


    if ($_GET["type"] == "saveProduct") {
        $input = $_POST;
        
        $id = date("YmdHis", $timestamp);
        
        $product_lic = "";
        $fsc = "";
        $copp = "";
        $artwork = "";
        if(isset($_FILES['product_lic'])) {
            $file_tmp =$_FILES['product_lic']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['product_lic']['name'])));
            $file_name = $id."product_lic.".$file_ext;
            $product_lic = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/product/".$file_name);
        }
        
        $sql = "INSERT INTO rnd_product (user_no, product_name, grade, dosage_type, dosage_form, generic_name, packing_style, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, manufactured_under, manufactured_for, manufactured_type, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, mfg_lic, apperance, storage_condition, mrp) VALUES
        ('".$_GET["user_no"]."', '".$input["product_name"]."', '".$input["grade"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["manufactured_under"]."', '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["mfg_lic"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["mrp"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getProductsLog") {
        $output = Array();
        $sql = "SELECT p.*, c.company as manufactured_for FROM rnd_product p LEFT JOIN client c ON p.manufactured_for=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' AND p.status LIKE '%".$_GET["status"]."%' AND p.generic_name LIKE '%".$_GET["generic_name"]."%' ORDER BY p.dosage_form, p.product_name, p.grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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