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
    
if ($_GET["type"] == "saveQuotation") {
    $target_dir = "../upload/quotation/";
    
    $file_name = "";
    if(isset($_FILES["document"]["name"])){
        $target_file = $target_dir."quotation-".basename($_FILES["document"]["name"]);
        $file_name = "quotation-".basename($_FILES["document"]["name"]);
        move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
    }
   // echo $file_name;
     $sql = "INSERT INTO quotation (plant_id,selected_approver,user_no, vendor_no, materials,documents, entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','".$_POST["selected_approver"]."','".$_GET["user_no"]."','".$_POST["vendor_no"]."','".$_POST["materials"]."','$file_name','".$_GET["emp_id"]."','".$entry_date."')";
    //$sql = "INSERT INTO quotation (selected_approver,user_no, vendor_no, materials,documents, entry_by, entry_date) VALUES ('".$_POST["selected_approver"]."','".$_GET["user_no"]."','".$_POST["vendor_no"]."','".$_POST["materials"]."','$file_name','".$_GET["emp_id"]."','".$entry_date."')";
   // echo json_encode($result);
   //echo $sql;
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
        $conn->query($sql1);
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getPendingQuotations") {
    $output = Array();
    $sql = "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.status='pending' ORDER BY q.id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $row["materials"] = json_decode($row["materials"]);
            
            $materials = $row["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                
                if ($material->material_type == 'Chemicals') {
                    $sql1 = "SELECT * FROM chemical WHERE chemical_no='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["chemical_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                } else if ($material->material_type == 'Glasswares') {
                    $sql1 = "SELECT * FROM glassware WHERE glassware_no='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["description"];
                        }
                    }
                } else if ($material->material_type == 'General Material') {
                    $sql1 = "SELECT * FROM general_material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                        }
                    }
                } else {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                }
                
                $materials[$i] = $material;
            }
            $row["materials"] = $materials;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "updateQuotation") {
    $sql = "UPDATE quotation SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} else if ($_GET["type"] == "getQuotationLog") {
    $output = Array();
    $sql = "SELECT q.*, v.vendor_no, v.vendor_name, v.state_code, v.gst_no, v.email, v.city FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='gmpdemo1' AND q.vendor_no LIKE '%%' AND q.status='approve' ORDER BY q.id DESC";
    //$sql = "SELECT q.*, v.vendor_no, v.vendor_name, v.state_code, v.gst_no, v.email, v.city FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND q.status='approve' ORDER BY q.id DESC";
    //echo $sql;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $row["materials"] = json_decode($row["materials"]);
            
            $materials = $row["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                
                if ($material->material_type == 'Chemicals') {
                    $sql1 = "SELECT * FROM chemical WHERE chemical_no='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["chemical_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                } else if ($material->material_type == 'Glasswares') {
                    $sql1 = "SELECT * FROM glassware WHERE glassware_no='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["description"];
                        }
                    }
                } else if ($material->material_type == 'General Material') {
                    $sql1 = "SELECT * FROM general_material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                        }
                    }
                } else {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                }
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="getApproveQuotations") {
    if($_GET['from'] == '' && $_GET['to'] == ''){
        $sql = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.status='approve'";
    }else{
        $sql = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."'AND vendor_no LIKE '%".$_GET["vendor_no"]."%' AND q.entry_date BETWEEN '" . $_GET['from'] . "' AND  '" . $_GET['to'] . "' AND q.status='approve'";
    }
    $result = $conn->query($sql);
    $output = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $materials = json_decode($row["materials"]);
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql2 = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_code='".$material->material_code."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $material->material_name = $row2["material_name"];
                        $material->material_type = $row2["material_type"];
                        $material->material_subtype = $row2["material_subtype"];
                        $material->grade = $row2["grade"];
                        $materials[$i] = $material;
                    }
                }
            }
            $row["materials"] = $materials;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getComparatives") {
    $output = Array();
    $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row["material_code"]."%' AND q.status='approve'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $materials = json_decode($row1["materials"]);
                    for ($i = 0; $i < count($materials); $i++) {
                        $material = $materials[$i];
                        if ($material->material_code == $row["material_code"]) {
                            $row1["quotation_amt"] = $material->quotation_amt;
                            $row1["quotation_per"] = $material->quotation_per;
                            $output1[] = $row1;
                            break;
                        }
                    }
                }
            }
            if (count($output1) > 1) {
                $row["quotations"] = $output1;
                $output[] = $row;
            }
        }
    }
    echo json_encode($output);
} else if($_GET['type'] == 'downloadQuotationLog'){
        $_GET['filename'] = 'Quotation Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html.='
        <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;"><b>Sr No.</b></td>
                <td style="width:15%;"><b>Quotation No</b></td>
                <td style="width:15%;"><b>Material Type</b></td>
                <td style="width:25%;"><b>Vendor Name</b></td>
                <td style="width:20%;"><b>No of Material</b></td>
                <td style="width:20%;"><b>Quotation Date</b></td>
            </tr>';

    $j=1;
    $sql = "SELECT q.*, v.vendor_no, v.vendor_name, v.state_code, v.gst_no, v.email, v.city FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='gmpdemo1' AND q.vendor_no LIKE '%%' AND q.status='approve' ORDER BY q.id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
        $row["materials"] = json_decode($row["materials"]);
            
            $materials = $row["materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                
            //     if ($material->material_type == 'Chemicals') {
            //         $sql1 = "SELECT * FROM chemical WHERE chemical_no='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["chemical_name"];
            //                 $material->grade = $row1["grade"];
            //             }
            //         }
            //     } else if ($material->material_type == 'Glasswares') {
            //         $sql1 = "SELECT * FROM glassware WHERE glassware_no='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["description"];
            //             }
            //         }
            //     } else if ($material->material_type == 'General Material') {
            //         $sql1 = "SELECT * FROM general_material WHERE material_code='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["material_name"];
            //             }
            //         }
            //     } else {
            //         $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
            //         $result1 = $conn->query($sql1);
            //         if ($result1->num_rows > 0) {
            //             while ($row1 = $result1->fetch_assoc()) {
            //                 $material->material_name = $row1["material_name"];
            //                 $material->grade = $row1["grade"];
            //             }
            //         }
            //     }
            }
            $output[] = $row;
             $html.='
             <tr nobr="true">
                <td style="width: 5%;">'.$j++.'</td>
                <td style="width: 15%;">'.$row['quotation_no'].'</td>
                <td style="width: 15%;">'.$row['material_type'].'</td>
                <td style="width: 25%;">'.$row['vendor_name'].'</td>
                <td style="width: 20%;">'.count($materials).'</td>
                <td style="width: 20%;">'.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
            </tr>';

            }
        }
    // }
           $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuotationLog.pdf', 'I');
    }
}else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>