<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    require_once __DIR__ . '/standard_hplc_columns_seed.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    if (!isset($input) || !is_array($input)) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        $input = is_array($decoded) ? $decoded : array();
    }

    function hesc($conn, $value) {
        return $conn->real_escape_string((string)$value);
    }

    function hx($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    $conn->query("CREATE TABLE IF NOT EXISTS hplc_gc_column_master (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        column_no VARCHAR(30) DEFAULT NULL,
        plant_id TEXT DEFAULT NULL,
        technique VARCHAR(20) DEFAULT NULL,
        column_name TEXT DEFAULT NULL,
        usp_l_code TEXT DEFAULT NULL,
        pharmacopoeia_reference TEXT DEFAULT NULL,
        stationary_phase TEXT DEFAULT NULL,
        dimensions_mm TEXT DEFAULT NULL,
        particle_size_um TEXT DEFAULT NULL,
        pore_size_a TEXT DEFAULT NULL,
        end_capped TEXT DEFAULT NULL,
        manufacturer TEXT DEFAULT NULL,
        catalog_no TEXT DEFAULT NULL,
        status TEXT DEFAULT NULL,
        entry_by TEXT DEFAULT NULL,
        entry_date TEXT DEFAULT NULL,
        approve_by TEXT DEFAULT NULL,
        approve_date TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

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

        if ($_GET["type"] == "saveHPLC") {
            $technique = hesc($conn, isset($input["technique"]) ? $input["technique"] : "HPLC");
            $column_name = hesc($conn, isset($input["column_name"]) ? $input["column_name"] : "");
            $usp_l_code = hesc($conn, isset($input["usp_l_code"]) ? $input["usp_l_code"] : "");
            $pharmacopoeia_reference = hesc($conn, isset($input["pharmacopoeia_reference"]) ? $input["pharmacopoeia_reference"] : "");
            $stationary_phase = hesc($conn, isset($input["stationary_phase"]) ? $input["stationary_phase"] : "");
            $dimensions_mm = hesc($conn, isset($input["dimensions_mm"]) ? $input["dimensions_mm"] : "");
            $particle_size_um = hesc($conn, isset($input["particle_size_um"]) ? $input["particle_size_um"] : "");
            $pore_size_a = hesc($conn, isset($input["pore_size_a"]) ? $input["pore_size_a"] : "");
            $end_capped = hesc($conn, isset($input["end_capped"]) ? $input["end_capped"] : "");
            $manufacturer = hesc($conn, isset($input["manufacturer"]) ? $input["manufacturer"] : "");
            $catalog_no = hesc($conn, isset($input["catalog_no"]) ? $input["catalog_no"] : "");

            $sql = "INSERT INTO hplc_gc_column_master
                (plant_id, technique, column_name, usp_l_code, pharmacopoeia_reference, stationary_phase, dimensions_mm, particle_size_um, pore_size_a, end_capped, manufacturer, catalog_no, status, entry_by, entry_date)
                VALUES
                ('".$_GET["plant_id"]."', '".$technique."', '".$column_name."', '".$usp_l_code."', '".$pharmacopoeia_reference."', '".$stationary_phase."', '".$dimensions_mm."', '".$particle_size_um."', '".$pore_size_a."', '".$end_capped."', '".$manufacturer."', '".$catalog_no."', 'Pending', '".$_GET["emp_id"]."', '$entry_date')";
            if ($conn->query($sql)) {
                $newId = $conn->insert_id;
                $columnNo = "HGC" . str_pad($newId, 4, "0", STR_PAD_LEFT);
                $conn->query("UPDATE hplc_gc_column_master SET column_no='".$columnNo."' WHERE id='".$newId."'");
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else if ($_GET["type"] == "getPendingHPLC") {
            $output = Array();
            $sql = "SELECT * FROM hplc_gc_column_master WHERE LOWER(IFNULL(status,''))='pending' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } else if ($_GET["type"] == "updateHPLC") {
            $newStatus = strtolower($_GET["status"]) == "approve" ? "Approved" : "Rejected";
            $sql = "UPDATE hplc_gc_column_master SET status='".$newStatus."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else if ($_GET["type"] == "getHPLCLog") {
            $output = Array();
            $plant=$_GET['plant_id'];
            if($plant==0){
            $sql = "SELECT * FROM hplc_gc_column_master ORDER BY `id` DESC";
            }else{
            $sql = "SELECT * FROM hplc_gc_column_master WHERE plant_id='".$_GET["plant_id"]."' ORDER BY `id` DESC";    
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    //$row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } else if ($_GET["type"] == "seedStandardHplcColumns") {
            $plant = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "1126");
            $empId = $conn->real_escape_string(isset($_GET["emp_id"]) ? $_GET["emp_id"] : "master");
            $masterUserName = isset($input["masterUserName"]) ? trim($input["masterUserName"]) : "Master User";
            $entryBy = $masterUserName !== "" ? $masterUserName : $empId;
            $entryByEsc = hesc($conn, $entryBy);

            $columns = (isset($input["columns"]) && is_array($input["columns"])) ? $input["columns"] : array();
            if (count($columns) === 0 && function_exists("medicap_standard_hplc_columns_seed")) {
                $columns = medicap_standard_hplc_columns_seed();
            }

            $added = 0;
            $skipped = 0;
            $errors = array();

            foreach ($columns as $col) {
                $technique = isset($col["technique"]) ? trim($col["technique"]) : "HPLC";
                $column_name = isset($col["column_name"]) ? trim($col["column_name"]) : "";
                if ($column_name === "") {
                    continue;
                }
                $usp_l_code = isset($col["usp_l_code"]) ? trim($col["usp_l_code"]) : "";
                $pharmacopoeia_reference = isset($col["pharmacopoeia_reference"]) ? trim($col["pharmacopoeia_reference"]) : "";
                $stationary_phase = isset($col["stationary_phase"]) ? trim($col["stationary_phase"]) : "";
                $dimensions_mm = isset($col["dimensions_mm"]) ? trim($col["dimensions_mm"]) : "";
                $particle_size_um = isset($col["particle_size_um"]) ? trim($col["particle_size_um"]) : "";
                $pore_size_a = isset($col["pore_size_a"]) ? trim($col["pore_size_a"]) : "";
                $end_capped = isset($col["end_capped"]) ? trim($col["end_capped"]) : "";
                $manufacturer = isset($col["manufacturer"]) ? trim($col["manufacturer"]) : "";
                $catalog_no = isset($col["catalog_no"]) ? trim($col["catalog_no"]) : "";

                $nameEsc = hesc($conn, $column_name);
                $dimEsc = hesc($conn, $dimensions_mm);
                $mfrEsc = hesc($conn, $manufacturer);
                $check = $conn->query(
                    "SELECT id FROM hplc_gc_column_master
                     WHERE plant_id='".$plant."'
                       AND column_name='".$nameEsc."'
                       AND IFNULL(dimensions_mm,'')='".$dimEsc."'
                       AND IFNULL(manufacturer,'')='".$mfrEsc."'
                     LIMIT 1"
                );
                if ($check && $check->num_rows > 0) {
                    $skipped++;
                    continue;
                }

                $sql = "INSERT INTO hplc_gc_column_master
                    (plant_id, technique, column_name, usp_l_code, pharmacopoeia_reference, stationary_phase, dimensions_mm, particle_size_um, pore_size_a, end_capped, manufacturer, catalog_no, status, entry_by, entry_date, approve_by, approve_date)
                    VALUES
                    ('".$plant."', '".hesc($conn, $technique)."', '".$nameEsc."', '".hesc($conn, $usp_l_code)."', '".hesc($conn, $pharmacopoeia_reference)."', '".hesc($conn, $stationary_phase)."', '".$dimEsc."', '".hesc($conn, $particle_size_um)."', '".hesc($conn, $pore_size_a)."', '".hesc($conn, $end_capped)."', '".$mfrEsc."', '".hesc($conn, $catalog_no)."', 'Approved', '".$entryByEsc."', '".$entry_date."', '".$entryByEsc."', '".$entry_date."')";
                if ($conn->query($sql)) {
                    $newId = $conn->insert_id;
                    $columnNo = "HGC" . str_pad((string)$newId, 4, "0", STR_PAD_LEFT);
                    $conn->query("UPDATE hplc_gc_column_master SET column_no='".$columnNo."' WHERE id='".$newId."'");
                    $added++;
                } else {
                    $errors[] = $column_name.": ".$conn->error;
                }
            }

            echo json_encode(array(
                "status" => "success",
                "added" => $added,
                "skipped" => $skipped,
                "errors" => $errors
            ));
        } else if ($_GET["type"] == "saveHplcWorkflowEntries") {
            $conn->query("CREATE TABLE IF NOT EXISTS hplc_workflow_store (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                plant_id VARCHAR(50) DEFAULT NULL,
                store_key VARCHAR(80) NOT NULL,
                payload LONGTEXT,
                entry_by TEXT,
                entry_date TEXT,
                UNIQUE KEY uk_plant_key (plant_id, store_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");
            $plant = hesc($conn, $_GET["plant_id"] ?? '1126');
            $key = hesc($conn, $input["key"] ?? '');
            if ($key === '') {
                echo json_encode(array('status' => 'error', 'message' => 'key required'));
                exit;
            }
            $payload = hesc($conn, json_encode(isset($input["rows"]) && is_array($input["rows"]) ? $input["rows"] : array()));
            $emp = hesc($conn, $_GET["emp_id"] ?? '');
            $sql = "INSERT INTO hplc_workflow_store (plant_id, store_key, payload, entry_by, entry_date)
                VALUES ('".$plant."', '".$key."', '".$payload."', '".$emp."', '".$entry_date."')
                ON DUPLICATE KEY UPDATE payload='".$payload."', entry_by='".$emp."', entry_date='".$entry_date."'";
            if ($conn->query($sql)) {
                echo json_encode(array('status' => 'success'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => $conn->error));
            }
        } else if ($_GET["type"] == "getHplcWorkflowEntries") {
            $conn->query("CREATE TABLE IF NOT EXISTS hplc_workflow_store (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                plant_id VARCHAR(50) DEFAULT NULL,
                store_key VARCHAR(80) NOT NULL,
                payload LONGTEXT,
                entry_by TEXT,
                entry_date TEXT,
                UNIQUE KEY uk_plant_key (plant_id, store_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");
            $plant = hesc($conn, $_GET["plant_id"] ?? '1126');
            $key = hesc($conn, $_GET["key"] ?? ($input["key"] ?? ''));
            $rows = array();
            if ($key !== '') {
                $res = $conn->query("SELECT payload FROM hplc_workflow_store WHERE plant_id='".$plant."' AND store_key='".$key."' LIMIT 1");
                if ($res && ($row = $res->fetch_assoc())) {
                    $decoded = json_decode($row['payload'], true);
                    if (is_array($decoded)) {
                        $rows = $decoded;
                    }
                }
            }
            echo json_encode($rows);
        } else if ($_GET["type"] == "downloadHPLCLog") {
        $_GET['filename'] = 'HPLC GC Column Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $name = isset($_GET["name"]) ? $conn->real_escape_string($_GET["name"]) : "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:8%;">Sr.</td>
                    <td style="width:12%;">Column No</td>
                    <td style="width:10%;">Technique</td>
                    <td style="width:18%;">Column Name</td>
                    <td style="width:8%;">USP L</td>
                    <td style="width:14%;">Pharmacopoeia Ref</td>
                    <td style="width:14%;">Dimensions</td>
                    <td style="width:8%;">Particle</td>
                    <td style="width:8%;">Status</td>
                </tr>
            </thead>';
            $i=1;
            $sql = "SELECT * FROM hplc_gc_column_master WHERE column_name LIKE '%".$name."%' ORDER BY column_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width:8%;">'.$i.'.</td>
                        <td style="width:12%;">'.$row['column_no'].'</td>
                        <td style="width:10%;">'.$row['technique'].'</td>
                        <td style="width:18%;">'.$row['column_name'].'</td>
                        <td style="width:8%;">'.$row['usp_l_code'].'</td>
                        <td style="width:14%;">'.$row['pharmacopoeia_reference'].'</td>
                        <td style="width:14%;">'.$row['dimensions_mm'].'</td>
                        <td style="width:8%;">'.$row['particle_size_um'].'</td>
                        <td style="width:8%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('HPLC_GC_Column_Master.pdf', 'I');
    } else if ($_GET["type"] == "downloadHPLCLogExcel") {
        $name = isset($_GET["name"]) ? $conn->real_escape_string($_GET["name"]) : "";
        $plant = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : "0";

        $where = "column_name LIKE '%".$name."%'";
        if($plant !== "0"){
            $where .= " AND plant_id='".$plant."'";
        }

        $sql = "SELECT * FROM hplc_gc_column_master WHERE ".$where." ORDER BY column_name";
        $result = $conn->query($sql);

        $filename = "HPLC_GC_Column_Master_" . date("Ymd_His") . ".xls";
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";

        $html = '<html><head><meta charset="UTF-8"><style>
            table { border-collapse: collapse; width: 2200px; table-layout: fixed; font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
            th, td { border: 1px solid #9aa7b3; padding: 8px 6px; vertical-align: top; overflow-wrap: anywhere; word-wrap: break-word; white-space: normal; }
            th { background: #1f4e78; color: #ffffff; text-align: center; font-weight: 700; }
            tr:nth-child(even) td { background: #f6f9fc; }
            .w-60 { width: 60px; } .w-90 { width: 90px; } .w-120 { width: 120px; } .w-140 { width: 140px; }
            .w-160 { width: 160px; } .w-180 { width: 180px; } .w-220 { width: 220px; } .w-280 { width: 280px; }
            .title { font-size: 14pt; font-weight: 700; color: #1f4e78; margin-bottom: 10px; }
        </style></head><body>';

        $html .= '<div class="title">HPLC / GC Column Master</div>';
        $html .= '<table><thead><tr>
            <th class="w-60">Sr</th>
            <th class="w-120">Column No</th>
            <th class="w-90">Technique</th>
            <th class="w-220">Column Name</th>
            <th class="w-90">USP L</th>
            <th class="w-220">Pharmacopoeia Reference</th>
            <th class="w-220">Stationary Phase</th>
            <th class="w-140">Dimensions (mm)</th>
            <th class="w-120">Particle Size (um)</th>
            <th class="w-120">Pore Size (A)</th>
            <th class="w-90">End Capped</th>
            <th class="w-180">Manufacturer</th>
            <th class="w-180">Catalog No</th>
            <th class="w-90">Status</th>
            <th class="w-120">Entry By</th>
            <th class="w-140">Entry Date</th>
        </tr></thead><tbody>';

        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>
                    <td>'.($i++).'</td>
                    <td>'.hx($row['column_no']).'</td>
                    <td>'.hx($row['technique']).'</td>
                    <td>'.hx($row['column_name']).'</td>
                    <td>'.hx($row['usp_l_code']).'</td>
                    <td>'.hx($row['pharmacopoeia_reference']).'</td>
                    <td>'.hx($row['stationary_phase']).'</td>
                    <td>'.hx($row['dimensions_mm']).'</td>
                    <td>'.hx($row['particle_size_um']).'</td>
                    <td>'.hx($row['pore_size_a']).'</td>
                    <td>'.hx($row['end_capped']).'</td>
                    <td>'.hx($row['manufacturer']).'</td>
                    <td>'.hx($row['catalog_no']).'</td>
                    <td>'.hx($row['status']).'</td>
                    <td>'.hx($row['entry_by']).'</td>
                    <td>'.hx($row['entry_date']).'</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="16" style="text-align:center;">No records found</td></tr>';
        }

        $html .= '</tbody></table></body></html>';
        echo $html;
        exit;
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>