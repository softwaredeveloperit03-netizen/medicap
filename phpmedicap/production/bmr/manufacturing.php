<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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
    
    if ($_GET["type"] == "getPendingBatches") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                
                $materials = $row["raw_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["raw_materials"] = $materials;
                
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                
                $materials = $row["packing_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["packing_materials"] = $materials;
                $row["stages"] = json_decode($row["stages"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startProduction") {
        $sql = "UPDATE bmr SET status='start', start_date='$entry_date', start_by='".$_GET["emp_id"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getActiveBatches") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status !='Completed'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "uploadBMR") {
        $id = date("Ymdhis", $timestamp);

    	$bmrfile = "";
        if(isset($_FILES['bmrfile'])) {
            $file_tmp =$_FILES['bmrfile']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['bmrfile']['name'])));
            $file_name = $id."bmrfile.".$file_ext;
            $bmrfile = $file_name;
            move_uploaded_file($file_tmp,"../../upload/bmr/".$file_name);
        }
        
        $batch_size = $_POST["batch_size"];
        $yield_qty = $_POST["yield_qty"];
        
        $diff = $batch_size - $yield_qty;
        $yield_per = ($yield_qty * 100) / $batch_size;
        $yield_per = round($yield_per, 2);
        
        $sql = "UPDATE bmr SET status='Completed', bmr_file='".$bmrfile."', yield_qty='".$_POST["yield_qty"]."', yield_per='$yield_per', complete_date='".$entry_date."', pack='".$_POST["pack_qty"]."', pack_unit='".$_POST["pack_unit"]."', exp_date='".$_POST["exp_date"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    // else if ($_GET["type"] == "getStartedBatches") {
    //     $output = array();
    //     $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status ='inprocess'";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $row["raw_materials"] = json_decode($row["raw_materials"]);
                
    //             $materials = $row["raw_materials"];
    //             for ($i = 0; $i < count($materials); $i++) {
    //                 $material = $materials[$i];
    //                 $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
    //                 $result1 = $conn->query($sql1);
    //                 if ($result1->num_rows > 0) {
    //                     while ($row1 = $result1->fetch_assoc()) {
    //                         $material->material_name = $row1["material_name"];
    //                         $material->grade = $row1["grade"];
    //                     }
    //                 }
    //                 $materials[$i] = $material;
    //             }
    //             $row["raw_materials"] = $materials;
                
    //             $row["packing_materials"] = json_decode($row["packing_materials"]);
                
    //             $materials = $row["packing_materials"];
    //             for ($i = 0; $i < count($materials); $i++) {
    //                 $material = $materials[$i];
    //                 $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
    //                 $result1 = $conn->query($sql1);
    //                 if ($result1->num_rows > 0) {
    //                     while ($row1 = $result1->fetch_assoc()) {
    //                         $material->material_name = $row1["material_name"];
    //                         $material->grade = $row1["grade"];
    //                     }
    //                 }
    //                 $materials[$i] = $material;
    //             }
    //             $row["packing_materials"] = $materials;
    //             $row["stages"] = json_decode($row["stages"]);
                
    //             $row["current_stage"] = "";
    //             $stages = $row["stages"];
    //             for ($i = 0; $i < count($stages); $i++) {
    //                 $stage = $stages[$i];

    //                 if ($stage->status !== 'complete' && $row["current_stage"] == "") {
    //                     $row["current_stage"] = $stage->stage;
    //                 }
    //                 $sql1 = "SELECT * FROM technical_info WHERE product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$stage->stage."'";
    //                 $result1 = $conn->query($sql1);
    //                 if ($result1->num_rows > 0) {
    //                     while ($row1 = $result1->fetch_assoc()) {
    //                         $row1["tests"] = json_decode($row1["tests"]);
    //                         $stage->technical_info = $row1;
    //                         $stage->isInfo = "done";
    //                     }
    //                 } else {
    //                     $stage->isInfo = "pending";
    //                 }
    //                 $stages[$i] = $stage;
    //             }
    //             $row["stages"] = $stages;
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    
    
    else if ($_GET["type"] == "getStartedBatches") {
            $output = Array();
            $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status ='inprocess'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT b.*, m.material_name, m.grade FROM bmr_material b LEFT JOIN material m ON b.material_code=m.material_code WHERE b.no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["materials"] = $output1;
                    $temp = 0;
                    $output1 = Array();
                    $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='BMR0001'";
                                                                                    
                    // $sql1 = "SELECT * FROM bmr_stages WHERE bmr_no='".$row["bmr_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["supervisors"] = json_decode($row1["supervisors"]);
                            $row1["workers"] = json_decode($row1["workers"]);
                            $output1[] = $row1;
                        }
                    }
                    if ($temp == 0) {
                        $row["stages"] = $output1;
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "startStage") {
        $sql = "SELECT * FROM bmr WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stages = json_decode($row["stages"]);
                for ($i = 0; $i < count($stages); $i++) {
                    $stage = $stages[$i];
                    if ($stage->stage == $_GET["stage"]) {
                        $stage->status = 'start';
                        $stage->start_by = $_GET["emp_id"];
                        $stage->start_date = date("Y-m-d", $timestamp);
                        $stage->start_time = date("H:i:s", $timestamp);
                        
                        $stages[$i] = $stage;
                    }
                }
                $sql = "UPDATE bmr SET stages='".json_encode($stages)."' WHERE id='".$_GET["id"]."'";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            }
        }
    } else if ($_GET["type"] == "completeStage") {
        $sql = "SELECT * FROM bmr WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stages = json_decode($row["stages"]);
                for ($i = 0; $i < count($stages); $i++) {
                    $stage = $stages[$i];
                    if ($stage->stage == $_GET["stage"]) {
                        $stage->status = 'complete';
                        $stage->complete_by = $_GET["emp_id"];
                        $stage->complete_date = date("Y-m-d", $timestamp);
                        $stage->complete_time = date("H:i:s", $timestamp);
                        
                        $stages[$i] = $stage;
                    }
                }
                if ($_GET["index"] == "last") {
                    $sql = "UPDATE bmr SET stages='".json_encode($stages)."', status='active' WHERE id='".$_GET["id"]."'";
                } else {
                    $sql = "UPDATE bmr SET stages='".json_encode($stages)."' WHERE id='".$_GET["id"]."'";
                }
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            }
        }
    }else if ($_GET["type"] == "downloadStartedBatches") {
        $_GET['filename'] = 'Batch Manufacturing Status'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center"></h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:15%; text-align:centre;"><b>Plan No</b></td>
            <td style="width:15%; text-align:centre;"><b>Product Type</b></td>
            <td style="width:15%; text-align:centre;"><b>Product Code</b></td>
            <td style="width:15%; text-align:centre;"><b>Product Name</b></td>
            <td style="width:15%; text-align:centre;"><b>Grade</b></td>
            <td style="width:15%; text-align:centre;"><b>Batch size</b></td>
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
        </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Batch Manufacturing Status.pdf', 'I');
    }

}

$conn->close();
?>