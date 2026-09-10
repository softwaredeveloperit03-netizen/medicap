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
        
        
        
        $sql = "INSERT INTO product (user_no,product_code, product_name, grade, dosage_type, dosage_form, generic_name, packing_style, packing_mode, label_claim, shelf_life,min_shelf, thera, testing_time, retest_period, division, sale_ts, manufactured_under, manufactured_for, manufactured_type, product_lic, fsc, copp, artwork, entry_by, entry_date, hsn, gtin, gst, cess, category, mfg_lic, pack_desc, apperance, storage_condition, market, mrp) VALUES
        ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["product_name"]."', '".$input["grade"]."', '".$input["dosage_type"]."', '".$input["dosage_form"]."', '".$input["generic_name"]."', '".$input["packing_style"]."','".$input["packing_mode"]."', '".$input["label_claim"]."', '".$input["shelf_life"]."', '".$input["min_shelf"]."', '".$input["thera"]."', '".$input["testing_time"]."', '".$input["retest_period"]."', '".$input["division"]."', '".$input["sale_ts"]."', '".$input["manufactured_under"]."', '".$input["manufactured_for"]."', '".$input["manufactured_type"]."','$product_lic', '$fsc', '$copp', '$artwork','".$_GET["emp_id"]."', '$entry_date', '".$input["hsn"]."', '".$input["gtin"]."', '".$input["gst"]."', '".$input["cess"]."', '".$input["category"]."', '".$input["mfg_lic"]."', '".$input["pack_desc"]."', '".$input["apperance"]."', '".$input["storage_condition"]."', '".$input["market"]."', '".$input["mrp"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getGrades") {
        $output = Array();
        $sql = "SELECT * FROM grade WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getProductsLog") {
        $output = Array();
        $sql = "SELECT p.*, c.company as manufactured_for FROM product p LEFT JOIN client c ON p.manufactured_for=c.client_code WHERE p.user_no='".$_GET["user_no"]."' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' AND p.status LIKE '%".$_GET["status"]."%' AND p.generic_name LIKE '%".$_GET["generic_name"]."%' ORDER BY p.dosage_form, p.product_name, p.grade";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT mrp, rate FROM product_mrp WHERE product_code='".$row["product_code"]."' AND status='approve' ORDER BY id DESC LIMIT 1";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["mrp"] = $row1["mrp"];
                        $row["rate"] = $row1["rate"];
                        break;
                    }
                } else {
                    $row["mrp"] = 0;
                    $row["rate"] = 0;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "approveProduct") {
        $sql = "UPDATE product SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "getApprovedProduct") {
        $output = Array();
         $sql = "SELECT * FROM product WHERE status='approve' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%'";
        //$sql = "SELECT * FROM product WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getProductDetails") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
                break;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "updateProduct") {
        $input = $_POST;
        
        $sql = "UPDATE product SET product_code='".$input["product_code"]."', product_name='".$input["product_name"]."', grade='".$input["grade"]."', dosage_type='".$input["dosage_type"]."', dosage_form='".$input["dosage_form"]."', generic_name='".$input["generic_name"]."', packing_style='".$input["packing_style"]."', excipient='".$input["excipient"]."',
        label_claim='".$input["label_claim"]."', shelf_life='".$input["shelf_life"]."', manufactured_under='".$input["manufactured_under"]."', manufactured_for='".$input["manufactured_for"]."', color_used='".$input["color_used"]."', manufactured_type='".$input["manufactured_type"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "updateMRP") {
        $sql = "INSERT INTO product_mrp (user_no,product_code, mrp, rate, entry_by, entry_date, status)
        VALUES ('".$_GET["user_no"]."','".$input["product_code"]."', '".$input["mrp"]."', '".$input["rate"]."', 
        '".$_GET["emp_id"]."', '$entry_date', 'approve')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingMRPs") {
        $output = Array();
        $sql = "SELECT * FROM product_mrp WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                break;
            }
        }
        echo json_encode($output);
    } 
    
         else if ($_GET['type'] == 'QA_stability_pdf') {
        $sql = "SELECT * FROM product_mrp WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $_GET['filename'] = ''; $_GET['pdftype'] = 'headfoot'; include("../pdfimp.php");
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
       
       $html='<table style="width:540px">
              <tr style="width:540px">
             <td style="width:540px; line-height:25px;"><h3 style="text-align:center">Annexure-I</h3>
            </td>
             </tr>
             </table>
            <div></div>
             <table border="1" style="width=540px">
              <tr style="width:540px">
             <td style="width:540px; line-height:25px;"><h3 style="text-align:center">Withdrawal Schedule</h3>
            </td>
             </tr>
             <tr style="width:540px">
             <td style="width:540px; line-height:25px;"><h4 style="text-align:left">Storage Condition: 30°C/75 % RH [INTERMEDIATE / LONG TERM]</h4>
            </td>
             </tr>
            <tr>
                <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;">Station (Month)</td>
                <td style="line-height:20px;width: 58px;border-bottom:none;text-align:center;">Batch no.</td>
                <td style="line-height:20px;width: 67px;border-bottom:none;text-align:center;">Scheduled Withdrawal date</td>
                <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Withdrawn on</td>
                <td style="line-height:20px;width: 64px;border-bottom:none;text-align:center;">Qty Withdrawn</td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">Remaining Qty</td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">Withdrawn by</td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">Date of Report Sign/Date</td>
                <td style="line-height:20px;width: 55px;border-bottom:none;text-align:center;">Remark (if any)</td>
            </tr>
            <tr>
                <td style="line-height:20px;width: 50px;border-bottom:none;text-align:center;"  rowspan="3"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 70px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 70px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
            </tr>
            <tr>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 70px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"></td>
            </tr>
            </table>
            
            <div></div>
            <div></div>
            
            <table border="1">
            <tr>
                <td style="line-height:30px;width: 40px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;">Prepared By QC </td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;">Reviewed By QC</td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;">Reviewed By QA</td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;">Approved By</td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;">Authorised By</td>
            
            </tr>
            <tr>
                <td style="line-height:30px;width: 40px;border-bottom:none;text-align:center;">Sign</td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
            </tr>
            <tr>
                <td style="line-height:30px;width: 40px;border-bottom:none;text-align:center;">Date</td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
                <td style="line-height:30px;width: 100px;border-bottom:none;text-align:center;"></td>
            </tr>';
   $html.='</table>';
            }}
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadproductmasterlog.pdf', 'I');
    }
    
    else if ($_GET["type"] == "approveMRP") {
        $sql = "UPDATE product_mrp SET status='".$input["status"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            if ($input["status"] == "approve") {
                $sql = "UPDATE product SET mrp='".$input["mrp"]."' WHERE product_code='".$input["product_code"]."'";
                $conn->query($sql);
            }
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getMRPChangeHistory") {
        $output = Array();
        $sql = "SELECT * FROM product";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT mrp, rate FROM product_mrp WHERE product_code='".$row["product_code"]."' 
            AND status='approve' ORDER BY id DESC LIMIT 1";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["mrp"] = $row1["mrp"];
                        $row["rate"] = $row1["rate"];
                    }
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["mrps"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET['type'] == 'productmasterpdf') {
        $sql = "SELECT * FROM product WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $_GET['filename'] = ''; $_GET['pdftype'] = 'headfoot'; include("../pdfimp.php");
        $sql = "SELECT * FROM product WHERE  id='".$_GET["id"]."' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='
        <h3>Product Details:</h3>
        <table cellpadding="5" style="border:solid 1px BCBBBA;">
        <tr>
            <td style="width:25%;"><b>Product Code:</b></td>
            <td style="width:25%;">'.$row['product_code'].'</td>
            <td style="width:25%;"><b>Product Name:</b></td>
            <td style="width:25%;">'.$row['product_name'].'</td>
        </tr>
        <tr>
            <td style="width:25%;"><b>Grade:</b></td>
            <td style="width:25%;">'.$row['grade'].'</td>
            <td style="width:25%;"><b>Generic Name:</b></td>
            <td style="width:25%;">'.$row['generic_name'].'</td>
        </tr>
        <tr>
            <td style="width:25%;"><b>Type Of Dosage:</b></td>
            <td style="width:25%;">'.$row['dosage_type'].'</td>
            <td style="width:25%;"><b>Dosage Form:</b></td>
            <td style="width:25%;">'.$row['dosage_form'].'</td>
        </tr>
        <tr>
            <td style="width:25%;"><b>Shelf Life:</b></td>
            <td style="width:25%;">'.$row['shelf_life'].'</td>
            <td style="width:25%;"><b>Manufactured Under:</b></td>
            <td style="width:25%;">'.$row['manufactured_under'].'</td>
        </tr>
        <tr>
            <td style="width:25%;"><b>Minimum Shelf Life:</b></td>
            <td style="width:25%;">'.$row['min_shelf'].'</td>
            <td style="width:25%;"><b>Approximate Testing Time:</b></td>
            <td style="width:25%;">'.$row['testing_time'].'</td>
        </tr>
        <tr>
            <td style="width:25%;"><b>Label Claim:</b></td>
            <td style="width:75%;">'.$row['label_claim'].'</td>
        </tr>
        <tr>
            <td style="width:25%;"><b>Rate:</b></td>
            <td style="width:75%;">'.$row['rate'].'</td>
        </tr>
        </table>
        <div></div>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:50%;"><b>Entry By</b></td>
            <td style="width:50%;"><b>Approve By</b></td>
        </tr>
        <tr>
            <td style="width:50%;">'.$row['entry_by'].'</td>
            <td style="width:50%;"></td>
        </tr>
        <tr>
            <td style="width:50%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
            <td style="width:50%;"></td>
        </tr>';
            }
        }
        
        $html.='</table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if ($_GET['type'] == 'productmasterlog') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html.='<h3 style="text-align:center;">Product List</h3>
            <table cellpadding="5">
            <thead>
                <tr style="background-color:#DCDCDC;font-weight:bold;">
                    <td style="width:10%;">Sr No.</td>
                    <td style="width:15%;">Product Type</td>
                    <td style="width:20%;">Product Code</td>
                    <td style="width:20%;">Product Name</td>
                    <td style="width:10%;">Rate</td>
                    <td style="width:10%;">MRP</td>
                    <td style="width:15%;">Grade</td>
                </tr>
            </thead>';
            $i=1;
             $sql = "SELECT * FROM product WHERE status='approve' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%'";
           // $sql = "SELECT * FROM product WHERE dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%' AND status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tbody>
                        <tr nobr="true">
                            <td style="width:10%;">'.$i.'.</td>
                            <td style="width:15%;">'.$row['product_type'].'</td>
                            <td style="width:20%;">'.$row['product_code'].'</td>
                            <td style="width:20%;">'.$row['product_name'].'</td>
                            <td style="width:10%;">'.$row['rate'].'</td>
                            <td style="width:10%;">'.$row['mrp'].'</td>
                            <td style="width:15%;">'.$row['grade'].'</td>
                        </tr>
                    </tbody>';
                    $i++;
                }
            }
            $html.='
        </table>';
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('material.pdf', 'I');
    }else if ($_GET['type'] == 'MRPChangeHistoryPDF') {
        $_GET['filename'] = 'Product MRP History'; $_GET['sop']=''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
  
     $sql = "SELECT * FROM product WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
         
           
            $html.='
              <h3 style="text-align:center">Product MRP History</h3>
            <h3>Product Details</h3>
            <table cellpadding="8">
                <tr>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Product Code</td>
                    <td style="width:25%;">'.$row['product_code'].'</td>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Product Name</td>
                    <td style="width:25%;">'.$row['product_name'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Grade</td>
                    <td style="width:25%;">'.$row['grade'].'</td>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Product Type</td>
                    <td style="width:25%;">'.$row['product_type'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Product Appearance</td>
                    <td style="width:25%;">'.$row['product_apperance'].'</td>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Storage Condition</td>
                    <td style="width:25%;">'.$row['storage_condition'].'</td>
                </tr>
                <tr>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Manufactured Under</td>
                    <td style="width:25%;">'.$row['manufactured_under'].'</td>
                    <td style="width:25%;font-weight:bold;background-color:#DDDAD9;font-weight:bold; border: solid 1px black">Manufactured For</td>
                    <td style="width:25%;">'.$row['manufactured_for'].'</td>
                </tr>
              
            </table><div></div>
            <h3>MRP List:</h3>
            <table cellpadding="6">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr</td>
                    <td style="width:25%;">Rate</td>
                    <td style="width:25%;">Mrp</td>
                    <td style="width:20%;">Entry By</td>
                    <td style="width:20%;">Entry Date</td>
                    
                </tr>';
                $j=1;
                $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html.='
                        <tr >
                            <td style="width:10%;">'.$j++.'</td>
                            <td style="width:25%;">'.$row1['rate'].'</td>
                            <td style="width:25%;">'.$row1['mrp'].'</td>
                            <td style="width:20%;">'.$row1['entry_by'].'</td>
                            <td style="width:20%;">'.$row1['entry_date'].'</td>
                            
                        </tr>';
                    }
                }
                    
                
            $html.='
            </table>';
            }
        }
             $html.='<div></div><div></div><div></div><div></div>
             <table cellpadding="5" border="0.1">
             <tr style="text-align:center;background-color:#DDDAD9">
             <td style="width:10%"><b></b></td>
              <td style="width:30%"><b>Prepared By</b></td>
               <td style="width:30%"><b>Checked By</b></td>
                <td style="width:30%"><b>Approved By</b></td>
             </tr>
             <tr>
              <td style="width:10%"><b>Name</b></td>
              <td style="width:30%"></td>
               <td style="width:30%"></td>
                <td style="width:30%"></td>
             </tr>
             <tr>
              <td style="width:10%"><b>Date</b></td>
              <td style="width:30%"></td>
               <td style="width:30%"></td>
                <td style="width:30%"></td>
             </tr>
             <tr>
              <td style="width:10%"><b>Sign</b></td>
              <td style="width:30%"></td>
               <td style="width:30%"></td>
                <td style="width:30%"></td>
             </tr>
             </table>';
        
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MRPChangeHistory.pdf', 'I');
    }else if ($_GET['type'] == 'downloadproductmasterlog') {
        $_GET['filename'] = 'Product List'; $_GET['sop']=''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html.="";
        $html.='<table cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;"><b>Sr.</b></td>
                        <td style="width:15%;"><b>Dosage Form</b></td>
                        <td style="width:15%;"><b>Product Code</b></td>
                        <td style="width:15%;"><b>Product Name</b></td>
                        <td style="width:10%;"><b>Rate</b></td>
                        <td style="width:10%;"><b>MRP</b></td>
                        <td style="width:10%;"><b>Grade</b></td>
                        <td style="width:15%;"><b>Shelf Life</b></td>
                    </tr>';
                    $i=1;
                    $sql = "SELECT * FROM product WHERE status='approve' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND grade LIKE '%".$_GET["grade"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                        <td>'.$i.'.</td>
                        <td>'.$row['dosage_form'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['rate'].'</td>
                        <td>'.$row['mrp'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['shelf_life'].'</td>
                    </tr>';
                    $i++;
            }
        }
        $html.='</table>';
        $EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('downloadproductmasterlog.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>