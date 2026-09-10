<?php

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

function mx($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

require '../db.php';
require '../token.php';
header('response_token: test123456');

$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$output = array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    $input = json_decode(file_get_contents('php://input'), true);

    if ($_GET["type"] == "saveMaterial") {
        try {
            $data = $_POST;

            $msdsFile = "NA";

            $matIs = $data['matIs'];

            if ($matIs == 'OWN') {
                $GL = 'MC00';
            } else {
                $GL = $data['client_code'];
            }

            $material_name = mysqli_real_escape_string($conn, $data["material_name"]);
            $grade = $data["grade"];

            $sql = "INSERT INTO `rnd_material`(`plant_id`, `material_type`,`material_sub_type_id`, `material_subtype`, `material_name`,`material_nature`, `location`, 
            `storage_condition`, `inventory`, `density`, `unit`, `lead_time`, `category`, `hsn`, `gst`,`tax`, `status`, `entry_by`,`entry_date`, `sub_type`, 
            `equivalancy_applicable`, `equivalent_to`, `description`, `client_code`,`mainGroupSeries`, `grade`, `uom`, `alternate_uom`,`pack_size`,`packing_requirement`, 
            `safety`, `color_index`, `type`, `product_n`, `m_photo`,`leverages`,`specificGravity`,`texture`,`madeOf`,`dimension`,`material_name_report`,`plant_code`,
            `materialTypeCode`,`materialSubTypeCode`,`packSizeCode`,`tax_type`,`maxInventory`,`moq`,`plasticType`,`inventoryValueMax`,`premixItem`,`assayCalculation`,
            `Functional_categoryList`,`indent_type`,`artwork`) VALUES ('".$_GET["plant_id"]."','".$data["material_type"]."','".$data["material_sub_type_id"]."',
            '".$data["material_subtype"]."','".$material_name."','".$data["material_nature"] ."','".$data["location"]."','".$data["storage_condition"]."',
            '".$data["inventory"]."','".$data["density"]."','".$data["uom"]."','".$data["lead_time"]."','".$data["category"]."','".$data["hsn"]."','".$data["gst"]."','".$data["tax"]."', 
            'Pending','".$_GET["emp_id"]."','".$entry_date."','".$data["sub_type"]."','".$data["equivalancy_applicable"]."','".$data["equivalent_to"]."',
            '".$data["description"]."','".$data["client_code"]."','".$data["client_code"]."','$grade','".$data["uom"]."','".$data["alternate_uom"]."',
            '".$data["pack_size"]."','".$data["packing_requirement"]."','".$data["safety"]."','".$data["color_index"]."','".$data["type"]."','".$data["product_n"]."',
            '$msdsFile','".$data["leverages"]."','".$data["specificGravity"]."','".$data["texture"]."','".$data["madeOf"]."','".$data["dimension"]."',
            '".$data["material_name_report"]."','".$data["plant_code"]."','".$data["materialTypeCode"]."','".$data["materialSubTypeCode"]."',
            '".$data["packSizeCode"]."','".$data["taxType"]."','".$data["maxInventory"]."','".$data["moq"]."','".$data["plasticType"]."','".$data["inventoryValueMax"]."','".$data["premixItem"]."','".$data["assayCalculation"]."',
            '".$data["Functional_categoryList"]."', '".$data["indent_type"]."', '".$data["artwork"]."')";

            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";

                $material_code = 'NA';
                $last_id = $conn->insert_id;
                $plant_code = $data['plant_code'];
                $materialTypeCode = $data['materialTypeCode'];
                $materialSubTypeCode = $data['materialSubTypeCode'];
                $packSizeCode = $data['packSizeCode'];
                $padded_id = str_pad($last_id, 4, '0', STR_PAD_LEFT);

                if ($data["material_type"] == 'Raw Material') {
                    $material_code = "RM".$padded_id;
                } else {
                    $material_code = "PM".$padded_id;
                }

                $mother_material_code = '';
                if ($matIs == 'OWN') {
                    $mother_material_code = $material_code;
                } else {
                    if (!empty($data['mother_material_code'])) {
                        $mother_material_code = $data['mother_material_code'];
                    } else {
                        $mother_material_code = '';
                    }
                }

                $update = $conn->prepare("UPDATE rnd_material SET material_code=? ,mother_material_code=? WHERE id=?");
                $update->bind_param("ssi", $material_code, $mother_material_code, $last_id);
                $update->execute();
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } catch (\Throwable $e) {
            echo "{\"statuse\":\"".$e."\"}";
        }
    } else if ($_GET["type"] == "downloadMaterialLogExcel") {
        $material_type = isset($_GET["material_type"]) ? mysqli_real_escape_string($conn, $_GET["material_type"]) : '';
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : '';
        $searchEsc = mysqli_real_escape_string($conn, $search);
        $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);

        $where = array();
        $where[] = "plant_id = '".$plant_id."'";
        if ($material_type !== '') {
            $where[] = "material_type = '".$material_type."'";
        }
        $where[] = "(status = 'Approved' OR status = 'In-Active')";

        if ($search !== '') {
            $like = "%".$searchEsc."%";
            $where[] = "(
                IFNULL(material_code,'') LIKE '".$like."' OR
                IFNULL(material_name,'') LIKE '".$like."' OR
                IFNULL(material_subtype,'') LIKE '".$like."' OR
                IFNULL(grade,'') LIKE '".$like."' OR
                IFNULL(material_nature,'') LIKE '".$like."' OR
                IFNULL(uom,'') LIKE '".$like."' OR
                IFNULL(status,'') LIKE '".$like."' OR
                IFNULL(entry_by,'') LIKE '".$like."' OR
                IFNULL(entry_date,'') LIKE '".$like."'
            )";
        }

        $sql = "SELECT id, entry_date, material_type, material_subtype, material_code, material_name, grade, material_nature, uom, status, entry_by,
                category, alternate_uom, material_name_report, sub_type, color_index, dimension, madeOf
                FROM rnd_material
                WHERE ".implode(" AND ", $where)."
                ORDER BY id DESC";

        $result = $conn->query($sql);

        $mode = 'mixed';
        if ($material_type === 'Raw Material') {
            $mode = 'raw';
        } else if ($material_type === 'Packing Material') {
            $mode = 'packing';
        }

        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=RND_Material_Master_Log_".date('Ymd_His').".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo chr(239).chr(187).chr(191);

        if ($mode === 'raw') {
            $headers = array(
                "Sr. No", "Entry Date", "Material Group/Type", "Material Code", "Material Name",
                "Category", "Nature of Material", "Pharmacopoeia/Grade", "Item Unit", "Billing Unit",
                "Status", "Material Status", "Entry By"
            );
        } else if ($mode === 'packing') {
            $headers = array(
                "Sr. No", "Entry Date", "Material Group/Type", "Material Code", "Material Name",
                "Material name (report)", "Pharmacopoeia/Grade", "Item Unit", "Billing Unit",
                "Sub Type", "Color", "Dimension", "Material Made of",
                "Status", "Material Status", "Entry By"
            );
        } else {
            $headers = array(
                "Sr. No", "Entry Date", "Material Type", "Material Group/Type", "Material Code", "Material Name",
                "Category", "Nature of Material", "Material name (report)", "Pharmacopoeia/Grade",
                "Item Unit", "Billing Unit", "Sub Type", "Color", "Dimension", "Material Made of",
                "Status", "Material Status", "Entry By"
            );
        }

        $ncols = count($headers);

        $headerClasses = array("h1","h2","h3","h4","h5","h6","h7","h8","h9","h10","h11");

        echo '<html><head><meta charset="UTF-8">
        <style>
            body { font-family: Calibri, Arial, sans-serif; margin: 0; padding: 10px; }
            .title { font-size: 15px; font-weight: 700; color: #1d3557; margin-bottom: 8px; }
            table { border-collapse: collapse; width: 100%; table-layout: auto; }
            th, td {
                border: 1px solid #9aa7b3;
                padding: 6px 8px;
                vertical-align: middle;
                word-wrap: break-word;
                overflow-wrap: anywhere;
                white-space: normal;
                font-size: 11px;
            }
            th { color: #fff; text-align: center; font-weight: 700; }
            .h1 { background: #1f4e78; } .h2 { background: #2f5597; } .h3 { background: #0070c0; }
            .h4 { background: #0f766e; } .h5 { background: #2e7d32; } .h6 { background: #6a1b9a; }
            .h7 { background: #5d4037; } .h8 { background: #37474f; } .h9 { background: #455a64; }
            .h10 { background: #1565c0; } .h11 { background: #283593; }
            .num { text-align: center; }
            .center { text-align: center; }
            .row-even { background: #f7fbff; }
            .row-odd { background: #ffffff; }
            .status-approved { color: #1b5e20; font-weight: 700; }
            .status-inactive { color: #b71c1c; font-weight: 700; }
            .ms-active { color: #0d6b0d; font-weight: 700; }
            .ms-inactive { color: #c0392b; font-weight: 700; }
        </style>
        </head><body>';

        echo '<div class="title">R&D Material Master Log - '.mx($material_type === '' ? 'All' : $material_type).'</div>';
        echo '<table>';
        echo '<thead><tr>';
        for ($i = 0; $i < $ncols; $i++) {
            echo '<th class="'.$headerClasses[$i % 11].'">'.mx($headers[$i]).'</th>';
        }
        echo '</tr></thead><tbody>';

        $cellNA = 'NA';

        if ($result && $result->num_rows > 0) {
            $sr = 1;
            while ($row = $result->fetch_assoc()) {
                $entryDate = $row['entry_date'];
                if (!empty($entryDate)) {
                    $time = strtotime($entryDate);
                    $entryDate = $time ? date('d-m-Y H:i', $time) : $entryDate;
                } else {
                    $entryDate = 'NA';
                }

                $status = $row['status'] ?: 'NA';
                $materialStatus = 'NA';
                if ($status === 'Approved') {
                    $materialStatus = 'Active';
                } else if ($status === 'In-Active') {
                    $materialStatus = 'In Active';
                }

                $rowClass = ($sr % 2 === 0) ? 'row-even' : 'row-odd';
                $statusClass = ($status === 'Approved') ? 'status-approved' : (($status === 'In-Active') ? 'status-inactive' : '');
                $msClass = ($materialStatus === 'Active') ? 'ms-active' : (($materialStatus === 'In Active') ? 'ms-inactive' : '');

                $mt = isset($row['material_type']) ? $row['material_type'] : '';
                $isRowRaw = ($mt === 'Raw Material');
                $isRowPacking = ($mt === 'Packing Material');

                echo '<tr class="'.$rowClass.'">';
                echo '<td class="num">'.mx($sr).'</td>';
                echo '<td class="center">'.mx($entryDate).'</td>';

                if ($mode === 'raw') {
                    echo '<td>'.mx($row['material_subtype'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_code'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['category'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_nature'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['grade'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['uom'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['alternate_uom'] ?: $cellNA).'</td>';
                    echo '<td class="'.$statusClass.'">'.mx($status).'</td>';
                    echo '<td class="'.$msClass.'">'.mx($materialStatus).'</td>';
                    echo '<td>'.mx($row['entry_by'] ?: $cellNA).'</td>';
                } else if ($mode === 'packing') {
                    echo '<td>'.mx($row['material_subtype'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_code'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name_report'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['grade'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['uom'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['alternate_uom'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['sub_type'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['color_index'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['dimension'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['madeOf'] ?: $cellNA).'</td>';
                    echo '<td class="'.$statusClass.'">'.mx($status).'</td>';
                    echo '<td class="'.$msClass.'">'.mx($materialStatus).'</td>';
                    echo '<td>'.mx($row['entry_by'] ?: $cellNA).'</td>';
                } else {
                    echo '<td>'.mx($mt ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_subtype'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_code'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($row['material_name'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($isRowRaw ? ($row['category'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowRaw ? ($row['material_nature'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['material_name_report'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($row['grade'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['uom'] ?: $cellNA).'</td>';
                    echo '<td class="center">'.mx($row['alternate_uom'] ?: $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['sub_type'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['color_index'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['dimension'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td>'.mx($isRowPacking ? ($row['madeOf'] ?: $cellNA) : $cellNA).'</td>';
                    echo '<td class="'.$statusClass.'">'.mx($status).'</td>';
                    echo '<td class="'.$msClass.'">'.mx($materialStatus).'</td>';
                    echo '<td>'.mx($row['entry_by'] ?: $cellNA).'</td>';
                }

                echo '</tr>';

                $sr++;
            }
        } else {
            echo '<tr><td colspan="'.(int)$ncols.'" class="center">No records found</td></tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    } else if ($_GET["type"] == "saveClientMatCode") {
        try {
            $data = $input;

            $msdsFile = "NA";

            $material_name = mysqli_real_escape_string($conn, $data["material_name"]);
            $grade = $data["grade"];

            $sql = " INSERT INTO `rnd_material`(`plant_id`, `matIs`,`material_type`,`mother_material_code`,`material_sub_type_id`, `material_subtype`, `material_name`,`material_nature`, `location`, 
            `storage_condition`, `inventory`, `density`, `unit`, `lead_time`, `category`, `hsn`, `gst`,`tax`, `status`, `entry_by`,`entry_date`, `sub_type`, `equivalancy_applicable`, `equivalent_to`, `description`, 
            `client_code`,`mainGroupSeries`, `grade`, `uom`, `alternate_uom`,`pack_size`,`packing_requirement`, `safety`, `color_index`, `type`, `product_n`, `m_photo`,`leverages`,`specificGravity`,`texture`,`madeOf`,
            `dimension`,`material_name_report`,`plant_code`, `materialTypeCode`,`materialSubTypeCode`,`packSizeCode`,`tax_type`,`maxInventory`,`moq`,`plasticType`,`inventoryValueMax`,`premixItem`,`assayCalculation`,
            Functional_categoryList,testingRequired,controlSample,sampleForTesting,samplingUnit,indent_type) VALUES ('".$_GET["plant_id"]."','CHILD','".$data["material_type"]."','".$data["material_code"]."','".$data["material_sub_type_id"]."',
            '".$data["material_subtype"]."','".$material_name."','".$data["material_nature"] ."','".$data["location"]."','".$data["storage_condition"]."','".$data["inventory"]."','".$data["density"]."','".$data["uom"]."',
            '".$data["lead_time"]."','".$data["category"]."','".$data["hsn"]."','".$data["gst"]."','".$data["tax"]."','Pending','".$_GET["emp_id"]."','".$entry_date."','".$data["sub_type"]."','".$data["equivalancy_applicable"]."',
            '".$data["equivalent_to"]."','".$data["description"]."','".$data["client_code"]."','".$data["mainGroupSeries"]."','$grade','".$data["uom"]."','".$data["alternate_uom"]."', '".$data["pack_size"]."',
            '".$data["packing_requirement"]."','".$data["safety"]."','".$data["color_index"]."','".$data["type"]."','".$data["product_n"]."','$msdsFile','".$data["leverages"]."','".$data["specificGravity"]."','".$data["texture"]."',
            '".$data["madeOf"]."','".$data["dimension"]."','".$data["material_name_report"]."','".$data["plant_code"]."','".$data["materialTypeCode"]."','".$data["materialSubTypeCode"]."',
            '".$data["packSizeCode"]."','".$data["tax_type"]."','".$data["maxInventory"]."','".$data["moq"]."','".$data["plasticType"]."','".$data["inventoryValueMax"]."','".$data["premixItem"]."',
            '".$data["assayCalculation"]."','".$data["Functional_categoryList"]."','".$data["testingRequired"]."','".$data["controlSample"]."','".$data["sampleForTesting"]."','".$data["samplingUnit"]."','".$data["indent_type"]."')";

            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";

                $material_code = 'NA';
                $last_id = $conn->insert_id;
                $plant_code = $data['plant_code'];
                $materialTypeCode = $data['materialTypeCode'];
                $materialSubTypeCode = $data['materialSubTypeCode'];
                $packSizeCode = $data['packSizeCode'];
                $padded_id = str_pad($last_id, 4, '0', STR_PAD_LEFT);
                $GL = $data['client_code'];
                if ($data["material_type"] == 'Raw Material') {
                    $material_code = $plant_code.$materialTypeCode.$GL.$materialSubTypeCode.$padded_id;
                } else {
                    $material_code = $plant_code.$materialTypeCode.$GL.$materialSubTypeCode.$packSizeCode.$padded_id;
                }
                $update = $conn->prepare("UPDATE rnd_material SET material_code=? WHERE id=?");
                $update->bind_param("si", $material_code, $last_id);
                $update->execute();
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } catch (\Throwable $e) {
            echo "{\"statuse\":\"".$e."\"}";
        }
    } else if ($_GET["type"] == "approveMaterial") {
        $sql = "UPDATE rnd_material SET status = 'For_QA_Approval' ,approve_by = '".$_GET["emp_id"]."' , approve_date = '".$entry_date."' 
        where id = '".$_GET["id"]."'";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getMaterials") {
        $output = array();

        $sql = "SELECT * FROM rnd_material m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."'
        AND (status != 'In-Active' AND status != 'Absolute' ) ORDER BY m.id DESC";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalent_to"] = json_decode($row["equivalent_to"]);
                $output[] = $row;
            }
        }
        $output = utf8ize($output);
        echo json_encode($output);
    } else if ($_GET["type"] == "getExistingMaterial") {
        $output = array();

        $sql = "SELECT m.* FROM rnd_material m  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.material_subtype='".$_GET["material_subtype"]."'
        AND m.status = 'Approved' AND  m.matIs = 'OWN' ORDER BY m.id DESC";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterialsForApproval") {
        $output = array();
        $sql = "SELECT * FROM rnd_material  WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status='Pending' ORDER BY material_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalent_to"] = json_decode($row["equivalent_to"]);
                $output[] = $row;
            }
        }
        $output = utf8ize($output);
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterialsByStatus") {
        $output = array();
        $sql = "SELECT * FROM rnd_material   WHERE plant_id= '".$_GET["plant_id"]."' AND material_type='".$_GET["material_type"]."'
        AND status = '".$_GET["status"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalent_to"] = json_decode($row["equivalent_to"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getallmatdataForIndent") {
        $output = array();
        $plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);
        $material_type = mysqli_real_escape_string($conn, $_GET["material_type"]);
        $sql = "SELECT m.*, m.unit AS uom, v.vendor_name FROM rnd_material m LEFT JOIN vendor v ON m.vendor_no = v.vendor_no 
            WHERE m.plant_id = '".$plant_id."' AND m.material_type LIKE '%".$material_type."%' ";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        $output = utf8ize($output);
        echo json_encode($output);
    } else if ($_GET["type"] == "ChangeMaterialStatus") {
        $sql = "UPDATE rnd_material SET status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
}

$conn->close();
?>
