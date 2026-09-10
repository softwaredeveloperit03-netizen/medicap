<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);


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
    
      if ($_GET["type"] == "getUnitFormulas") {
        $output = Array();
        $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.user_no='".$_GET["user_no"]."' WHERE status='approve' GROUP BY u.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Raw Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["raw_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Additional Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["additional_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Packing Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["packing_materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="saveStandardBatchSize"){
        $sql = "UPDATE unitformula SET batch_size='".$_GET["batch_size"]."',  raw_materials='".json_encode($input['raw_materials'])."', additional_materials='".json_encode($input['additional_materials'])."', packing_materials='".json_encode($input['packing_materials'])."', status='isbatch' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "downloadBOM"){
        $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.id='".$_GET["id"]."' LIMIT 1";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                 require '../tcpdf/tcpdf.php';
                $_GET['filename'] = ""; $_GET['pdftype'] = "onlyheader"; include("../pdfimp2.php");
                $html.='
                     <style>td { border:solid 1px BCBBBA;}</style>
                        <table cellpadding="5">
                            <tr>
                                <td style="background-color:#DDDAD9; width:100%; text-align:center;">Unit Manufacturing Formula</td>
                            </tr>
                        </table>
                        <div></div>
                    <table cellpadding="3" border="1">
                    <tr>
                        <td style="width:20%">Product Name</td>
                        <td style="width:30%">'.$row['product_name'].'</td>
                        <td style="width:20%">Product Code</td>
                        <td style="width:30%">'.$row['product_code'].'</td>
                    </tr>
                    <tr>
                        <td>Unit Formula No.</td>
                        <td>'.$row['mfr_no'].'</td>
                        <td>Dosage Form</td>
                        <td>'.$row['dosage_form'].'</td>
                    </tr> 
                    <tr>
                        <td>Grade</td>
                        <td>'.$row['grade'].'</td>
                        <td>Strength:</td>
                        <td>'.$row['strength'].'</td>
                    </tr>
                      <tr>
                        <td>Average Weight</td>
                        <td>'.$row['avg_wt'].'</td>
                    </tr>
                </table>
                <div></div>
                 <h3>Raw Materials:</h3>
                <table cellpadding="3">
                    <tr>
                        <td style="width:5%;">Sr.</td>
                        <td style="width:15%;">Material Code</td>
                        <td style="width:38%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:12%;">Qty</td>
                        <td style="width:10%;">Overages</td>
                        <td style="width:10%;">Role</td>
                    </tr>';
                    $output1 = Array();
                    $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Raw Material'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['material_code'].'</td>
                                <td>'.$row1['material_name'].'</td>
                                <td>'.$row1['grade'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['overages'].'</td>
                                <td>'.$row1['role'].'</td>
                            </tr>';
                        }
                    }
                $html.='
                </table>
                 <h3>Additional Materials List:</h3>
                <table cellpadding="3">
                    <tr>
                        <td style="width:5%;">Sr.</td>
                     
                        <td style="width:15%;">Material Code</td>
                        <td style="width:38%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:12%;">Qty</td>
                        <td style="width:10%;">Overages</td>
                        <td style="width:10%;">Role</td>
                    </tr>';
                    $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Additional Material'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td>'.$counter++.'</td>
                               
                                <td>'.$row1['material_code'].'</td>
                                <td>'.$row1['material_name'].'</td>
                                <td>'.$row1['grade'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['overages'].'</td>
                                <td>'.$row1['role'].'</td>
                            </tr>';
                        }
                    }
                $html.='
                </table>
               
                <h3>Packing Materials List:</h3>
                <table cellpadding="3">
                    <tr>
                        <td style="width:5%;">Sr.</td>
                        <td style="width:38%;">Material Code</td>
                        <td style="width:15%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:12%;">Qty</td>
                        <td style="width:10%;">Overages</td>
                        <td style="width:10%;">Role</td>
                    </tr>';
                    $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Packing Material'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['material_code'].'</td>
                                <td>'.$row1['material_name'].'</td>
                                <td>'.$row1['grade'].'</td>
                                <td>'.$row1['qty'].'</td>
                                <td>'.$row1['overages'].'</td>
                                <td>'.$row1['role'].'</td>
                            </tr>';
                        }
                    }
                $html.='
                </table>';
        
             EOD;
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('', 'I');
            }
        }
    }
    else if($_GET["type"] == "getPendingBatchFormulas"){
          $output = Array();
        $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.user_no='".$_GET["user_no"]."' WHERE status='approve' GROUP BY u.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Raw Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["raw_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Additional Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["additional_materials"] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Packing Material'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["packing_materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getProductsByDosage") {
        $output = array();
        $sql = "SELECT * FROM product WHERE status='approve' AND dosage_form='".$_GET["dosage_form"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT * FROM unitformula WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["raw_materials"] = json_decode($row1["raw_materials"]);
                        $row1["packing_materials"] = json_decode($row1["packing_materials"]);
                        $row1["additional_materials"] = json_decode($row1["additional_materials"]);
                        $output1[] = $row1;
                    }
                }
                $row["mfrs"] = $output1;
                
                $output1 = array();
                $sql1 = "SELECT * FROM manufacturing_process WHERE dosage_form='".$row["dosage_form"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "saveMaster") {
        $sql = "INSERT INTO ini_mfr (user_no, mfr_no,dosage_form, process_type, product_code, average_wt, unit, color, description, packing_type, status ,packing_style, primary_subtype, unit_wt,primary_qty, primary_unit, mono_qty, mono_unit, outer_qty, outer_unit, shipper_qty, shipper_unit, ismono, isouter,mfr,entry_by, entry_date) VALUES 
        ('".$_GET["user_no"]."', '".$input["mfr_no"]."' , '".$input["dosage_form"]."', '".$input["process_type"]."', '".$input["product_code"]."', '".$input["average_wt"]."', '".$input["unit"]."', '".$input["color"]."', '".$input["description"]."', '".$input["packing_type"]."', 'pending', '".$_input["packing_style"]."','".$input["primary_subtype"]."', '".$input["unit_wt"]."', '".$input["primary_qty"]."', '".$input["primary_unit"]."', '".$input["mono_qty"]."', '".$input["mono_unit"]."', '".$input["outer_qty"]."', '".$input["outer_unit"]."', '".$input["shipper_qty"]."', '".$input["shipper_unit"]."', '".$input["ismono"]."', '".$input["isouter"]."','".json_encode($input["mfr"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getProductsByUnits") {
        $output = array();
        //  $sql = "SELECT p.*, u.mfr_no,u.formula_for,u.average_weight FROM product p LEFT JOIN unitformula u ON u.product_code = p.product_code WHERE p.status='approve' AND p.dosage_form='".$_GET["dosage_form"]."' ";
         $sql = "SELECT u.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code WHERE u.user_no='".$_GET["user_no"]."' AND p.dosage_form='".$_GET["dosage_form"]."' GROUP BY u.id";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM unitformula WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output2 = Array();
                        $sql2 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Raw Material'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["raw_materials"] = $output2;
                        
                         $output2 = Array();
                        $sql2 = "SELECT u.*, m.material_subtype, m.material_name, m.grade FROM unit_materials u LEFT JOIN material m ON u.material_code=m.material_code WHERE u.mfr_no='".$row["id"]."' AND u.material_type='Packing Material'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["packing_materials"] = $output2;
                        $output1[] = $row1;
                        
                    }
                }
                $row["mfrs"] = $output1;
                
                ///stages/////
                 $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE mfr_no='".$row["mfr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        if ($row1["isinitial"] == 'YES') {
                            $row1["initial_checks"] = json_decode($row1["initial_checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                
                $row["stages"] = $output1;

                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "saveBatchFormula"){
        $sql = "INSERT INTO batch_formula (user_no, product_code, batch_size,mfr_no ,lots, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["product_code"]."','".$input["batch_size"]."' ,'".$input["mfr_no"]."', '".$input["lots"]."' , '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            $materials = $input["raw_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no,material_code,mfr_no , qty, unit, overages, role, process,batch_qty) VALUES ('$last_id','".$material['material_code']."', '".$input["mfr_no"]."' ,'".$material['qty']."', '".$material['unit']."', '".$material['overages']."', '".$material['role']."', '".$material['process']."','".$material["batch_qty"]."')";
                $conn->query($sql1);
            }
            
            $materials = $input["packing_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                $sql1 = "INSERT INTO batch_materials (no ,material_code,mfr_no , qty, unit, overages, role, process,batch_qty) VALUES ('$last_id', '".$material['material_code']."', '".$input["mfr_no"]."' ,'".$material['qty']."', '".$material['unit']."', '".$material['overages']."', '".$material['role']."', '".$material['process']."','".$material["batch_qty"]."')";
                $conn->query($sql1);
            }
            
            $stages = $input["batch_stages"];
            for($i=0; $i < count($stages); $i++){
                $stage = $stages[$i];
                if($stage["for_lot"] == true){
                    $stage["for_lot"] = 'yes';
                }
                $sql1 ="INSERT INTO batch_stages(mfr_no, for_lot , stage)VALUES('".$input["mfr_no"]."' ,'".$stage["for_lot"]."' ,'".$stage["stage"]."')";
                $conn->query($sql1);
            }
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }

    }else if($_GET["type"] == "getBatchFormula"){
        $output = Array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.generic_name, p.dosage_form, p.shelf_life, p.label_claim ,u.product_code,u.formula_for,u.instructions,u.abbreviation ,u.bmr_checklist ,u.raw_materials ,u.packing_materials FROM batch_formula b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN unitformula u ON u.mfr_no=b.mfr_no WHERE b.user_no='".$_GET["user_no"]."' GROUP BY b.id";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["instructions"] = json_decode($row["instructions"]);
                $equipments = array();
                $output1 = array();
                $sql1 = "SELECT * FROM stages WHERE mfr_no='".$row["mfr_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        if ($row1["isprocedure"] == 'YES') {
                            $row1["procedures"] = json_decode($row1["procedures"]);
                        }
                        if ($row1["isinstruction"] == 'YES') {
                            $row1["instructions"] = json_decode($row1["instructions"]);
                        }
                        if ($row1["isequipment"] == 'YES') {
                            $row1["equipments"] = json_decode($row1["equipments"]);
                            $equip = $row1["equipments"];
                            for ($i = 0; $i < count($equip); $i++) {
                                $temp = $equip[$i];
                                $temp->stage = $row1["stage"];
                                $equipments[] = $temp;
                            }
                        }
                        if ($row1["isclerance"] == 'YES') {
                            $row1["clearances"] = json_decode($row1["clearances"]);
                        }
                        if ($row1["isweighing"] == 'YES') {
                            $row1["weighings"] = json_decode($row1["weighings"]);
                        }
                        if ($row1["isenvironment"] == 'YES') {
                            $row1["environments"] = json_decode($row1["environments"]);
                        }
                        if ($row1["ischeck"] == 'YES') {
                            $row1["checks"] = json_decode($row1["checks"]);
                        }
                        if ($row1["isinitial"] == 'YES') {
                            $row1["initial_checks"] = json_decode($row1["initial_checks"]);
                        }
                        $output1[] = $row1;
                    }
                }
                $row["stages"] = $output1;
                
                /////Batch material details////
                $output2 = array();
                $sql2 = "SELECT b.*, m.material_name,m.material_type FROM batch_materials b LEFT JOIN material m ON b.material_code = m.material_code WHERE b.no ='".$row["id"]."'";
                $result2 = $conn->query($sql2);
                if($result2->num_rows > 0) {
                     while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                $row["materials"] = $output2;
                
                $row["equipments"] = $equipments;
                $row["abbreviation"] = json_decode($row["abbreviation"]);
                $row["bmr_checklist"] = json_decode($row["bmr_checklist"]);
                $row["raw_materials"] = json_decode($row["raw_materials"]);
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    

}

$conn->close();
?>