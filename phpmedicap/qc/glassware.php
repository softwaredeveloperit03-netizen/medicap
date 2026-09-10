<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    require_once __DIR__ . '/standard_glasswares_seed.php';
    require_once __DIR__ . '/seed_om_helper.php';
    require_once __DIR__ . '/../schema_tables.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    if (!is_array($input)) {
        $input = array();
    }

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

    if ($_GET["type"] == "saveGlassware") {
         $input = $_POST;  
  $data = json_decode($input["data"], true);
         $sql1 = "SELECT MAX(id) as id FROM glassware ";
          $result1 = $conn->query($sql1);
         $row1 = $result1->fetch_assoc();
         $last_id=$row1["id"]; 
         $target_dir = "../../../upload/product/";
           if(isset($_FILES["coa"]["name"])) {
            	$target_file = $target_dir.$last_id."_".basename($_FILES["coa"]["name"]);
            	$structure_file =$last_id."_".basename($_FILES["coa"]["name"]);
        	    move_uploaded_file($_FILES["coa"]["tmp_name"], $target_file);
        	   
           }
           if($_GET["plant_id"]==59){
                 $sql = "INSERT INTO glassware (coa,plant_id,name, capacity, unit, glassware_class, description, make, entry_by, entry_date,hsn,gst)
        VALUES ('".$structure_file."','".$_GET["plant_id"]."','".$data["name"]."', '".$data["capacity"]."', '".$data["unit"]."', '".$data["glassware_class"]."',
        '".$data["description"]."','".$data["make"]."', '".$data["emp_id"]."', '$entry_date','".$data["hsn"]."','".$data["gst"]."')";
           }
           else{
                 $sql = "INSERT INTO glassware (coa,plant_id,name, capacity, unit, glassware_class, description, make, entry_by, entry_date,hsn,gst)
        VALUES ('".$structure_file."','".$_GET["plant_id"]."','".$input["name"]."', '".$input["capacity"]."', '".$input["unit"]."', '".$input["glassware_class"]."',
        '".$input["description"]."','".$input["make"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["hsn"]."','".$input["gst"]."')";
           }
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}"; 
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingGlasswares") {
        $output = Array();
        $sql = "SELECT * FROM glassware WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateGlassware") {
        $sql = "UPDATE glassware SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getGlasswaresLog") {
        $output = Array();
        $plant=$_GET["plant_id"];
        if($plant==0){
        $sql = "SELECT * FROM glassware  ORDER BY `id` DESC ";
        }else{
        $sql = "SELECT * FROM glassware WHERE plant_id='".$_GET["plant_id"]."'ORDER BY `id` DESC ";    
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "seedStandardGlasswares") {
        if (function_exists('medicap_ensure_schema')) {
            medicap_ensure_schema($conn);
        }

        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $empId = $conn->real_escape_string($_GET["emp_id"]);
        $userNo = isset($_GET["user_no"]) ? $conn->real_escape_string($_GET["user_no"]) : '';
        $masterUserName = isset($input["masterUserName"]) ? $conn->real_escape_string(trim($input["masterUserName"])) : 'Master User';
        $items = (isset($input["glasswares"]) && is_array($input["glasswares"])) ? $input["glasswares"] : array();
        if (count($items) === 0 && function_exists('medicap_standard_glasswares_seed')) {
            $items = medicap_standard_glasswares_seed();
        }

        $gCols = array();
        $colRes = $conn->query("SHOW COLUMNS FROM glassware");
        if ($colRes) {
            while ($col = $colRes->fetch_assoc()) {
                $gCols[$col['Field']] = true;
            }
        }
        $omCols = array();
        $omColRes = $conn->query("SHOW COLUMNS FROM others_material");
        if ($omColRes) {
            while ($col = $omColRes->fetch_assoc()) {
                $omCols[$col['Field']] = true;
            }
        }

        $added = 0;
        $skipped = 0;
        $errors = array();
        $seq = 1;
        $maxSql = $conn->query("SELECT glassware_no FROM glassware WHERE plant_id='".$plant."' AND glassware_no LIKE 'GW-%' ORDER BY id DESC LIMIT 1");
        if ($maxSql && $maxSql->num_rows > 0) {
            $lastNo = $maxSql->fetch_assoc();
            if (preg_match('/GW-(\d+)/', $lastNo['glassware_no'], $m)) {
                $seq = intval($m[1]) + 1;
            }
        }

        foreach ($items as $gw) {
            $name = isset($gw["name"]) ? trim($gw["name"]) : '';
            $capacity = isset($gw["capacity"]) ? trim($gw["capacity"]) : '';
            if ($name === '') {
                continue;
            }
            $nameEsc = $conn->real_escape_string($name);
            $capEsc = $conn->real_escape_string($capacity);
            $check = $conn->query("SELECT id FROM glassware WHERE plant_id='".$plant."' AND name='".$nameEsc."' AND capacity='".$capEsc."' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $skipped++;
                continue;
            }
            $unit = $conn->real_escape_string(isset($gw["unit"]) ? $gw["unit"] : 'ml');
            $gclass = $conn->real_escape_string(isset($gw["glassware_class"]) ? $gw["glassware_class"] : 'type A');
            $desc = $conn->real_escape_string(isset($gw["description"]) ? $gw["description"] : '');
            $make = $conn->real_escape_string(isset($gw["make"]) ? $gw["make"] : 'Borosil');
            $gwNo = 'GW-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $seq++;

            $fields = array('plant_id', 'user_no', 'glassware_no', 'name', 'capacity', 'unit', 'glassware_class', 'description', 'make', 'entry_by', 'entry_date', 'status', 'approve_by', 'approve_date', 'coa');
            $values = array("'".$plant."'", "'".$userNo."'", "'".$gwNo."'", "'".$nameEsc."'", "'".$capEsc."'", "'".$unit."'", "'".$gclass."'", "'".$desc."'", "'".$make."'", "'".$empId."'", "'".$entry_date."'", "'approve'", "'".$empId."'", "'".$entry_date."'", "'0'");
            $useFields = array();
            $useValues = array();
            for ($fi = 0; $fi < count($fields); $fi++) {
                if (isset($gCols[$fields[$fi]])) {
                    $useFields[] = $fields[$fi];
                    $useValues[] = $values[$fi];
                }
            }
            $sql = "INSERT INTO glassware (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")";
            if ($conn->query($sql)) {
                // If a trigger overwrote glassware_no, reload it for others_material sync.
                $newId = $conn->insert_id;
                $code = $gwNo;
                if ($newId) {
                    $codeRes = $conn->query("SELECT glassware_no FROM glassware WHERE id='".$newId."' LIMIT 1");
                    if ($codeRes && ($codeRow = $codeRes->fetch_assoc()) && !empty($codeRow['glassware_no'])) {
                        $code = $codeRow['glassware_no'];
                    }
                }
                $codeEsc = $conn->real_escape_string($code);
                $added++;

                if (isset($omCols['material_subtype']) && function_exists('medicap_others_material_insert')) {
                    $matName = $nameEsc.($capacity !== '' ? ' '.$capEsc.' '.$unit : '');
                    $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Glassware' AND (material_code='".$codeEsc."' OR material_name='".$matName."') LIMIT 1");
                    if (!$omCheck || $omCheck->num_rows === 0) {
                        @medicap_others_material_insert($conn, array(
                            'plant_id' => "'".$plant."'",
                            'material_type' => "'QC Material'",
                            'material_subtype' => "'Glassware'",
                            'material_code' => "'".$codeEsc."'",
                            'material_name' => "'".$matName."'",
                            'unit' => "'".$unit."'",
                            'grade' => "'".$gclass."'",
                            'status' => "'Approved'",
                            'entry_by' => "'".$empId."'",
                            'entry_date' => "'".$entry_date."'",
                            'description' => "'".$desc."'",
                            'composition' => "''"
                        ));
                    }
                }
            } else {
                $errors[] = $name.' '.$capacity.': '.$conn->error;
            }
        }

        echo json_encode(array(
            "status" => "success",
            "added" => $added,
            "skipped" => $skipped,
            "errors" => $errors,
            "masterUserName" => $masterUserName
        ));
    }else if ($_GET["type"] == "downloadChallansLog") {
        $_GET['filename'] = 'Glasswares Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Glasswares Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ;">Sr.</td>
                    <td style="width: 10%; ">Material Type	</td>
                    <td style="width: 10%; ">Vendor Name	</td>
                    <td style="width: 10%; ">Challan No.	</td>
                    <td style="width: 10%; ">Challan Date	</td>
                    <td style="width: 5%; ">PO No.	</td>
                    <td style="width: 10%; ">PO Date	</td>
                    <td style="width: 10%; ">Invoice No.	</td>
                    <td style="width: 10%; ">Amount	</td>
                    <td style="width: 10%; ">Prepared Date		</td>
                    <td style="width: 10%; ">Prepared By		</td>
                    
                </tr>
            </thead>';
              $output = Array();
                 $sql = "SELECT * FROM indicator ORDER BY indicator";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 5%; ">'.$i.'.</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 5%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                         <td style="width: 10%;">'.$row[''].'</td>
                          <td style="width: 10%;">'.$row[''].'</td>
                           <td style="width: 10%;">'.$row[''].'</td>
                         
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glasswares Log.pdf', 'I');
    
}
  else if ($_GET["type"] == "receivingMaterialLogPDF") {
        $_GET['filename'] = 'Glasswares Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Glasswares Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ;">Sr.</td>
                    <td style="width: 10%; ">Glassware No</td>
                    <td style="width: 10%; ">Name</td>
                    <td style="width: 15%; ">Capacity</td>
                    <td style="width: 15%; ">Make</td>
                    <td style="width: 15%; ">Unit</td>
                    <td style="width: 15%; ">Class</td>
                    <td style="width: 15%; ">Discription</td>
                </tr>
            </thead>';
              
                $html.='<tr nobr="true">
                        <td style="width: 5%; ">'.$i.'.</td>
                        <td style="width: 10%; ">'.$row['glassware_no'].'</td>
                        <td style="width: 10%; ">'.$row['name'].'</td>
                        <td style="width: 15%; ">'.$row['capacity'].'</td>
                        <td style="width: 15%; ">'.$row['make'].'</td>
                        <td style="width: 15%;">'.$row['unit'].'</td>
                        <td style="width: 15%;">'.$row['glassware_class'].'</td>
                        <td style="width: 15%;">'.$row['description'].'</td>
                    </tr>';
           
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glasswares Log.pdf', 'I');
  }
    
    else if ($_GET["type"] == "downloadGlasswaresLog") {
        $_GET['filename'] = 'Glasswares Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Glasswares Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr.</td>
                    <td style="width:15%;">Glassware No</td>
                    <td style="width:10%;">Name</td>
                    <td style="width:15%;">Capacity</td>
                    <td style="width:15%;">Unit</td>
                    <td style="width:15%;">Class</td>
                    <td style="width:15%;">Description</td>
                </tr>
            </thead>';
              $output = Array();
                $sql = "SELECT * FROM glassware WHERE name LIKE '%".$_GET["name"]."%'  ORDER BY name";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width:10%;">'.$i.'.</td>
                        <td style="width:15%;">'.$row['glassware_no'].'</td>
                        <td style="width:10%;">'.$row['name'].'</td>
                        <td style="width:15%;">'.$row['capacity'].'</td>
                        <td style="width:15%;">'.$row['unit'].'</td>
                        <td style="width:15%;">'.$row['glassware_class'].'</td>
                        <td style="width:15%;">'.$row['description'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glasswares Log.pdf', 'I');
    }else if ($_GET["type"] == "GRNLogPDF") {
        $_GET['filename'] = 'GRN'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html='
        <h2 style="text-align:center">GRN</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:15%; text-align:centre;"><b>Glassware Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Grade</b></td>
                <td style="width:15%; text-align:centre;"><b>Vendor Name</b></td>
                <td style="width:10%; text-align:centre;"><b>Containers</b></td>
                <td style="width:10%; text-align:centre;"><b>Receiving Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Qty</b></td>
            </tr>
            <tr>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:15%;"></td>
                <td style="width:10%;"></td>
                <td style="width:15%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
                <td style="width:10%;"></td>
            </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('GRN.pdf', 'I');
    } else if ($_GET["type"] == "getGlasswaresForCleaning") {
        $output = Array();
        $plant = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $sql = "SELECT id, glassware_no, name, capacity, unit, glassware_class
                FROM glassware
                WHERE status='approve'";
        if ($plant !== '') {
            $sql .= " AND plant_id='".$plant."'";
        }
        $sql .= " ORDER BY name, glassware_no";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getGlasswareCleaningLog") {
        header('Content-Type: application/json; charset=UTF-8');
        $output = array();
        @$conn->query("CREATE TABLE IF NOT EXISTS qc_glassware_cleaning (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) DEFAULT NULL,
            glassware_name VARCHAR(255) DEFAULT NULL,
            glassware_code VARCHAR(100) DEFAULT NULL,
            clean_from DATE DEFAULT NULL,
            clean_to DATE DEFAULT NULL,
            cleaning_type VARCHAR(100) DEFAULT NULL,
            clean_by VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            entry_by VARCHAR(50) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $plant = $conn->real_escape_string(trim((string)(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '')));
        $sql = "SELECT * FROM qc_glassware_cleaning WHERE 1=1";
        if ($plant !== '') {
            $sql .= " AND plant_id='".$plant."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        exit;
    } else if ($_GET["type"] == "downloadGlasswareCleaningLog") {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $_GET['filename'] = 'Glassware Cleaning Log';
        $_GET['pdftype'] = 'onlyheader';
        $html = '';
        include("../pdfimp2.php");
        $html .= '<h2 style="text-align:center">Glassware Cleaning Log</h2>
            <table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:8px;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td>Sr.</td><td>Glassware Name</td><td>Glassware Code</td><td>Clean From</td><td>Clean To</td><td>Clean By</td><td>Cleaning Type</td>
            </tr>';
        $plant = $conn->real_escape_string(trim((string)(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '')));
        $sql = "SELECT * FROM qc_glassware_cleaning WHERE 1=1";
        if ($plant !== '') {
            $sql .= " AND plant_id='".$plant."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = @$conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>
                    <td style="text-align:center;">'.$i.'</td>
                    <td>'.htmlspecialchars((string)($row['glassware_name'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)($row['glassware_code'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)($row['clean_from'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)($row['clean_to'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)($row['clean_by'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)($row['cleaning_type'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
                </tr>';
                $i++;
            }
        }
        if ($i === 1) {
            $html .= '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glassware Cleaning Log.pdf', 'I');
        exit;
    } else if ($_GET["type"] == "saveGlasswareCleaning") {
        $esc = function ($value) use ($conn) {
            return $conn->real_escape_string(trim((string)($value ?? '')));
        };
        if (trim((string)($input['glassware_name'] ?? '')) === '' || trim((string)($input['glassware_code'] ?? '')) === '' || trim((string)($input['clean_by'] ?? '')) === '' || trim((string)($input['cleaning_type'] ?? '')) === '' || trim((string)($input['clean_from'] ?? '')) === '' || trim((string)($input['clean_to'] ?? '')) === '') {
            echo json_encode(array('status' => 'failed', 'message' => 'All fields are required'));
            exit;
        }
        $plant = $esc(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $conn->query("CREATE TABLE IF NOT EXISTS qc_glassware_cleaning (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) DEFAULT NULL,
            glassware_name VARCHAR(255) DEFAULT NULL,
            glassware_code VARCHAR(100) DEFAULT NULL,
            clean_from DATE DEFAULT NULL,
            clean_to DATE DEFAULT NULL,
            cleaning_type VARCHAR(100) DEFAULT NULL,
            clean_by VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            entry_by VARCHAR(50) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $sql = "INSERT INTO qc_glassware_cleaning
                (plant_id, glassware_name, glassware_code, clean_from, clean_to, cleaning_type, clean_by, status, entry_by, entry_date)
                VALUES (
                    '".$plant."',
                    '".$esc($input['glassware_name'] ?? '')."',
                    '".$esc($input['glassware_code'] ?? '')."',
                    '".$esc($input['clean_from'] ?? '')."',
                    '".$esc($input['clean_to'] ?? '')."',
                    '".$esc($input['cleaning_type'] ?? '')."',
                    '".$esc($input['clean_by'] ?? '')."',
                    'pending',
                    '".$esc($_GET['emp_id'])."',
                    '$entry_date'
                )";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'failed', 'message' => $conn->error));
        }
    }


}

$conn->close();
?>