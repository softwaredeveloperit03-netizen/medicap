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
    } else if ($_GET["type"] == "getStartedBatches") {
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type FROM bmr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.user_no='".$_GET["user_no"]."' AND b.status ='inprocess'";
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
                
                $row["current_stage"] = "";
                $stages = $row["stages"];
                for ($i = 0; $i < count($stages); $i++) {
                    $stage = $stages[$i];

                    if ($stage->status !== 'complete' && $row["current_stage"] == "") {
                        $row["current_stage"] = $stage->stage;
                    }
                    $sql1 = "SELECT * FROM technical_info WHERE product_code='".$row["product_code"]."' AND batch_no='".$row["batch_no"]."' AND stage='".$stage->stage."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row1["tests"] = json_decode($row1["tests"]);
                            $stage->technical_info = $row1;
                            $stage->isInfo = "done";
                        }
                    } else {
                        $stage->isInfo = "pending";
                    }
                    $stages[$i] = $stage;
                }
                $row["stages"] = $stages;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "startStage") {
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
    }else if ($_GET["type"] == "downloadPlans") {
        $_GET['filename'] = 'Batch Planning'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Batch Planning</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:4%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:16%; text-align:centre;"><b>Date</b></td>
            <td style="width:8%; text-align:centre;"><b>Plan No</b></td>
            <td style="width:7%; text-align:centre;"><b>BOM Type</b></td>
            <td style="width:10%; text-align:centre;"><b>BOM For</b></td>
            <td style="width:10%; text-align:centre;"><b>Product code</b></td>
            <td style="width:10%; text-align:centre;"><b>Product Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Planned Qty</b></td>
            <td style="width:10%; text-align:centre;"><b>No. of Batches Planned	</b></td>
            <td style="width:10%; text-align:centre;"><b>No. of Tailing Batches	</b></td>
            <td style="width:5%; text-align:centre;"><b>Status</b></td>
         </tr>';
            $sql = "SELECT b.*, b.id,count(b.plan_no) as no_of_batches,uf.id as u_id, b.plan_no,b.plan_type,b.plan_type,b.plan_for,
          b.product_code,count(planned_qty) as min_output_qty,count(qty_can_planned) as max_output_qty,uf.batch_size_unit, bf.rm_batch_size_unit,
          b.pack_unit as unit,b.status,b.entry_by,b.entry_date,p.product_name,cl.company,bf.batch_formula_weight as planned_batch_size
          FROM batch_planning b LEFT JOIN product p ON b.product_code=p.product_code and b.plant_id=p.plant_id 
          left JOIN client cl on b.plan_client_code= cl.client_code  and b.plant_id=cl.plant_id 
          LEFT join batch_formula_info bf on b.bfr_no = bf.bfr_no and b.plant_id = bf.plant_id
          LEFT join unitformula uf on uf.mfr_no = bf.mfr_no and uf.plant_id = bf.plant_id
          Where b.plant_id = '".$_GET["plant_id"]."'
          group by cl.company,plan_no,plan_type,plan_for,b.product_code,b.plan_no,bf.rm_batch_size_unit,
          b.status,b.entry_by,b.entry_date,p.product_name,b.pack_unit,b.id,bf.batch_formula_weight ,uf.batch_size_unit,u_id
          ORDER By b.id desc";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
      $html.='    <tr>
            <td style="width:4%;">'.$i++.'</td>
            <td style="width:16%;">'.$row['approve_date'].'</td>
            <td style="width:8%;">'.$row['plan_no'].'</td>
            <td style="width:7%;">'.$row['material_type'].'</td>
            <td style="width:10%;">'.$row['plan_for'].'</td>
            <td style="width:10%;">'.$row['product_code'].'</td>
            <td style="width:10%;">'.$row['product_name'].'</td>
            <td style="width:10%;">'.$row['planned_qty'].'</td>
            <td style="width:10%;">'.$row['no_of_batches'].'</td>
             <td style="width:10%;">'.$row['plan_no'].'</td>
            <td style="width:5%;">'.$row['status'].'</td>
        </tr>';
               
            }
        }
        
      $html.='   </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Batch Manufacturing Status.pdf', 'I');
    }

}

$conn->close();
?>