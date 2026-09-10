<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];



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
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveGenericProduct") {
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
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        $sql = "INSERT INTO generic_product (user_no,product_code, grade,tshape,tsize, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, sale_ts, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, cess, category, mfg_lic, pack_desc, apperance, storage_condition, mrp, other_expn, invt_depo_Ref, cylinder_cost, packing_design, permission_cost, status) VALUES
        ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["grade"]."','".$input["tshape"]."','".$input["tsize"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["sale_ts"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["cess"]."', '".$input["category"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["mrp"]."', '".$input["other_expn"]."', '".$input["invt_depo_Ref"]."', '".$input["cylinder_cost"]."', '".$input["packing_design"]."', '".$input["permission_cost"]."', 'approve')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getGenericProductsLog") {
        $output = Array();
        $sql = "SELECT * FROM generic_product WHERE user_no='".$_GET["user_no"]."' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%' AND status LIKE '%".$_GET["status"]."%' AND generic_name LIKE '%".$_GET["generic_name"]."%' ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getApprovedGenericProducts") {
        if (!isset($_GET["grade"])) {
            $_GET["grade"] = "";
        }
        if (!isset($_GET["dosage_type"])) {
            $_GET["dosage_type"] = "";
        }
        if (!isset($_GET["dosage_form"])) {
            $_GET["dosage_form"] = "";
        }
        $output = Array();
        $sql = "SELECT * FROM generic_product WHERE status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_name"] = $row["label_claim"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getLabelClaims") {
        if (!isset($_GET["grade"])) {
            $_GET["grade"] = "";
        }
        if (!isset($_GET["dosage_type"])) {
            $_GET["dosage_type"] = "";
        }
        if (!isset($_GET["dosage_form"])) {
            $_GET["dosage_form"] = "";
        }
        $output = Array();
        $sql = "SELECT * FROM product WHERE category='Generic' AND status='approve' AND dosage_type LIKE '%".$_GET["dosage_type"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND GRADE LIKE '%".$_GET["grade"]."%' ORDER BY generic_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["product_name"] = $row["label_claim"];
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    } else if ($_GET["type"] == "saveBrandProduct") {
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
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['fsc'])) {
            $file_tmp =$_FILES['fsc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['fsc']['name'])));
            $file_name = $id."fsc.".$file_ext;
            $fsc = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['copp'])) {
            $file_tmp =$_FILES['copp']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['copp']['name'])));
            $file_name = $id."copp.".$file_ext;
            $copp = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        if(isset($_FILES['artwork'])) {
            $file_tmp =$_FILES['artwork']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $file_name = $id."artwork.".$file_ext;
            $artwork = $file_name;
            move_uploaded_file($file_tmp,"../upload/product/".$file_name);
        }
        
        $sql = "INSERT INTO product (category,product_code, product_name, grade, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, manufactured_under, manufactured_for, manufactured_type, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, mfg_lic, pack_desc, apperance, storage_condition, market, mrp,similar_name,copy_from,tsize,tshape,plant) VALUES
        ('".$input["category"]."','".$input["product_code"]."', '".$input["product_name"]."', '".$input["grade"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["manufactured_under"]."', '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."', '".$input["similar_name"]."','".$input["copy_from"]."','".$input["tsize"]."','".$input["tshape"]."' ,'".$input["plant"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getBrandProductsLog") {
        $output = Array();
        $sql = "SELECT * FROM product  WHERE status='approve'";
        // $sql = "SELECT * FROM product ORDER BY dosage_form, grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalents"] = json_decode($row["equivalent"]);
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getProductDetails") {
        $sql = "SELECT * FROM product WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } else if ($_GET["type"] == "editProduct") {
        $label_claim = 'Each'.$input["coating_type"]." ".$input["dosage_form"]." Contains\n";
        $equivalents = $input["equivalents"];
        for ($i = 0; $i < count($equivalents); $i++) {
            $equivalent = $equivalents[$i];
            $test = '';
            if ($equivalent['equivalent_to'] !== '') {
                $test = ' '.$equivalent['equivalent_to']. ' '. $equivalent['strength']. ' '. $equivalent['unit'];
            }
            $label_claim.= $equivalent['material_name']. ' '. $equivalent['strength']. ' '. $equivalent['unit'].$test."\n";
        }
        $label_claim.='Excipient - QS\nColor -'.$input["apperance"];
        $sql = "UPDATE product SET category='".$input["category"]."', dosage_type='".$input["dosage_type"]."', dosage_form='".$input["dosage_form"]."', grade='".$input["grade"]."', packing_style='".$input["packing_style"]."', shelf_life='".$input["shelf_life"]."', testing_time='".$input["testing_time"]."', retest_period='".$input["retest_period"]."', thera='".$input["thera"]."', storage_condition='".$input["storage_condition"]."', apperance='".$input["apperance"]."', tshape='".$input["tshape"]."', tsize='".$input["tsize"]."', mrp='".$input["mrp"]."', hsn='".$input["hsn"]."', gtin='".$input["gtin"]."', equivalents='".json_encode($input["equivalents"])."', label_claim='$label_claim' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
}

$conn->close();
?>