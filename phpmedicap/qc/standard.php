<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$output = Array();
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

function sesc($conn, $value) {
    return $conn->real_escape_string((string)$value);
}

$conn->query("CREATE TABLE IF NOT EXISTS standard_master (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    standard_no TEXT DEFAULT NULL,
    plant_id TEXT DEFAULT NULL,
    user_no TEXT DEFAULT NULL,
    material_type TEXT DEFAULT NULL,
    material_subtype TEXT DEFAULT NULL,
    material_name TEXT DEFAULT NULL,
    standard_name TEXT DEFAULT NULL,
    standard_category TEXT DEFAULT NULL,
    grade TEXT DEFAULT NULL,
    standard TEXT DEFAULT NULL,
    analyte_marker TEXT DEFAULT NULL,
    pharmacopeia_reference TEXT DEFAULT NULL,
    cas_no TEXT DEFAULT NULL,
    potency TEXT DEFAULT NULL,
    purity TEXT DEFAULT NULL,
    manufacturer TEXT DEFAULT NULL,
    catalog_no TEXT DEFAULT NULL,
    batch_no TEXT DEFAULT NULL,
    storage_condition TEXT DEFAULT NULL,
    valid_upto TEXT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    status TEXT DEFAULT NULL,
    entry_by TEXT DEFAULT NULL,
    entry_date TEXT DEFAULT NULL,
    approve_by TEXT DEFAULT NULL,
    approve_date TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

$conn->query("ALTER TABLE standard_master
    ADD COLUMN IF NOT EXISTS standard_no TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS standard_name TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS standard_category TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS analyte_marker TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pharmacopeia_reference TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cas_no TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS potency TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS purity TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS manufacturer TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS catalog_no TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS batch_no TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS storage_condition TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS valid_upto TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL");

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "getPendingMaterials") {
        $output = array();
        $sql = "";
        if ($_GET["material_type"] == "Raw Material" || $_GET["material_type"] == "Packing Material") {
            $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND material_type='".$_GET["material_type"]."' AND material_subtype='".$_GET["material_subtype"]."'";
        } else if ($_GET["material_type"] == "Analytical Standard") {
            $sql = "SELECT * FROM chemical WHERE user_no='".$_GET["user_no"]."'";
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($_GET["material_type"] == "Analytical Standard") {
                    $row["material_name"] = $row["chemical_name"];
                    $row["material_code"] = $row["chemical_no"];
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveStandard") {
        $plant_id = sesc($conn, isset($_GET["plant_id"]) ? $_GET["plant_id"] : "0");
        $user_no = sesc($conn, isset($_GET["user_no"]) ? $_GET["user_no"] : "gmpdemo1");
        $material_type = sesc($conn, isset($input["material_type"]) ? $input["material_type"] : "");
        $material_subtype = sesc($conn, isset($input["material_subtype"]) ? $input["material_subtype"] : "");
        $material_name = sesc($conn, isset($input["material_name"]) ? $input["material_name"] : "");
        $standard_name = sesc($conn, isset($input["standard_name"]) ? $input["standard_name"] : $material_name);
        $standard_category = sesc($conn, isset($input["standard_category"]) ? $input["standard_category"] : (isset($input["standard"]) ? $input["standard"] : ""));
        $grade = sesc($conn, isset($input["grade"]) ? $input["grade"] : "");
        $standard = sesc($conn, isset($input["standard"]) ? $input["standard"] : $standard_category);
        $analyte_marker = sesc($conn, isset($input["analyte_marker"]) ? $input["analyte_marker"] : "");
        $pharmacopeia_reference = sesc($conn, isset($input["pharmacopeia_reference"]) ? $input["pharmacopeia_reference"] : (isset($input["pharmacopoeia_reference"]) ? $input["pharmacopoeia_reference"] : ""));
        $cas_no = sesc($conn, isset($input["cas_no"]) ? $input["cas_no"] : "");
        $potency = sesc($conn, isset($input["potency"]) ? $input["potency"] : "");
        $purity = sesc($conn, isset($input["purity"]) ? $input["purity"] : "");
        $manufacturer = sesc($conn, isset($input["manufacturer"]) ? $input["manufacturer"] : "");
        $catalog_no = sesc($conn, isset($input["catalog_no"]) ? $input["catalog_no"] : "");
        $batch_no = sesc($conn, isset($input["batch_no"]) ? $input["batch_no"] : "");
        $storage_condition = sesc($conn, isset($input["storage_condition"]) ? $input["storage_condition"] : "");
        $valid_upto = sesc($conn, isset($input["valid_upto"]) ? $input["valid_upto"] : "");
        $remarks = sesc($conn, isset($input["remarks"]) ? $input["remarks"] : "");

        $sql = "INSERT INTO standard_master
            (plant_id, user_no, material_type, material_subtype, material_name, standard_name, standard_category, grade, standard, analyte_marker, pharmacopeia_reference, cas_no, potency, purity, manufacturer, catalog_no, batch_no, storage_condition, valid_upto, remarks, status, entry_by, entry_date)
            VALUES
            ('".$plant_id."', '".$user_no."', '".$material_type."', '".$material_subtype."', '".$material_name."', '".$standard_name."', '".$standard_category."', '".$grade."', '".$standard."', '".$analyte_marker."', '".$pharmacopeia_reference."', '".$cas_no."', '".$potency."', '".$purity."', '".$manufacturer."', '".$catalog_no."', '".$batch_no."', '".$storage_condition."', '".$valid_upto."', '".$remarks."', 'Pending', '".$_GET["emp_id"]."', '".$entry_date."')";
        if ($conn->query($sql)) {
            $newId = $conn->insert_id;
            $standardNo = "STD" . str_pad($newId, 5, "0", STR_PAD_LEFT);
            $conn->query("UPDATE standard_master SET standard_no='".$standardNo."' WHERE id='".$newId."'");
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getStandards") {
        $output = array();
        $plant = isset($_GET['plant_id']) ? $_GET['plant_id'] : "0";
        $statusFilter = isset($_GET["status"]) ? strtolower(trim($_GET["status"])) : "";
        $where = "1=1";
        if ($plant != "0") {
            $where .= " AND plant_id='".$conn->real_escape_string($plant)."'";
        }
        if ($statusFilter != "" && $statusFilter != "all") {
            $where .= " AND LOWER(IFNULL(status,''))='".$conn->real_escape_string($statusFilter)."'";
        }
        $sql = "SELECT *,
            IFNULL(NULLIF(standard_name,''), material_name) as display_name,
            IFNULL(NULLIF(standard_category,''), standard) as display_category
            FROM standard_master WHERE ".$where." ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingStandards") {
        $output = array();
        $plant = isset($_GET['plant_id']) ? $_GET['plant_id'] : "0";
        $where = "LOWER(IFNULL(status,''))='pending'";
        if ($plant != "0") {
            $where .= " AND plant_id='".$conn->real_escape_string($plant)."'";
        }
        $sql = "SELECT * FROM standard_master WHERE ".$where." ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateStandard") {
        $id = intval($_GET["id"]);
        $newStatus = strtolower($_GET["status"]) == "approve" ? "Approved" : "Rejected";
        $sql = "UPDATE standard_master SET status='".$newStatus."', approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "downloadStandards") {
        $_GET['filename'] = 'Standard Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $status = isset($_GET["status"]) ? strtolower(trim($_GET["status"])) : "";
        $where = "1=1";
        if ($status != "" && $status != "all") {
            $where .= " AND LOWER(IFNULL(status,''))='".$conn->real_escape_string($status)."'";
        }
        if (isset($_GET["plant_id"]) && $_GET["plant_id"] != "0") {
            $where .= " AND plant_id='".$conn->real_escape_string($_GET["plant_id"])."'";
        }
        $html.='
        <h2 style="text-align:center">Standard Master</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:8%;">Sr</td>
                    <td style="width:12%;">Standard No</td>
                    <td style="width:15%;">Standard Name</td>
                    <td style="width:10%;">Category</td>
                    <td style="width:10%;">Material Type</td>
                    <td style="width:14%;">Marker</td>
                    <td style="width:14%;">Pharmacopoeia Ref</td>
                    <td style="width:8%;">Status</td>
                    <td style="width:9%;">Entry By</td>
                </tr>
            </thead>';
        $i=1;
        $sql = "SELECT * FROM standard_master WHERE ".$where." ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $name = $row['standard_name'] != '' ? $row['standard_name'] : $row['material_name'];
                $cat = $row['standard_category'] != '' ? $row['standard_category'] : $row['standard'];
                $html.='<tr nobr="true">
                    <td style="width:8%;">'.$i.'</td>
                    <td style="width:12%;">'.$row['standard_no'].'</td>
                    <td style="width:15%;">'.$name.'</td>
                    <td style="width:10%;">'.$cat.'</td>
                    <td style="width:10%;">'.$row['material_type'].'</td>
                    <td style="width:14%;">'.$row['analyte_marker'].'</td>
                    <td style="width:14%;">'.$row['pharmacopeia_reference'].'</td>
                    <td style="width:8%;">'.$row['status'].'</td>
                    <td style="width:9%;">'.$row['entry_by'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Standard_Master.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>