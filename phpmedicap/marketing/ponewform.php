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
$input = json_decode(file_get_contents('php://input'), true);

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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    function poFormEnsureClientServiceColumns($conn) {
        $cols = array(
            'serviceCategory' => 'TEXT NULL',
            'serviceDescription' => 'TEXT NULL',
            'serviceDescriptionData' => 'LONGTEXT NULL',
            'selectedProductCodes' => 'TEXT NULL'
        );
        foreach ($cols as $col => $def) {
            $check = $conn->query("SHOW COLUMNS FROM `client` LIKE '".$col."'");
            if ($check && $check->num_rows == 0) {
                $conn->query("ALTER TABLE `client` ADD COLUMN `".$col."` ".$def);
            }
        }
    }

    function poFormEsc($conn, $value) {
        return $conn->real_escape_string(isset($value) ? $value : '');
    }

    function poFormLoadClientServiceDetails($conn, $client_code) {
        $output = array();
        if ($client_code === '') {
            return $output;
        }
        $tableCheck = $conn->query("SHOW TABLES LIKE 'client_service_details'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            return $output;
        }
        $code = poFormEsc($conn, $client_code);
        $sql = "SELECT * FROM `client_service_details` WHERE `client_code`='".$code."' ORDER BY id ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['products'] = json_decode($row['products_json'], true);
                $row['descriptions'] = json_decode($row['descriptions_json'], true);
                $output[] = $row;
            }
        }
        return $output;
    }

    // Print ALL clients (no selection dependency). Includes nested client divisions.
    if ($_GET["type"] == "getAllClients") {
        poFormEnsureClientServiceColumns($conn);
        $output = array();
        $sql = "SELECT id, plant_id, status, client_code, LglNm, TrdNm, clientGroup, client_type, category,
                serviceCategory, serviceDescription, serviceDescriptionData, selectedProductCodes
                FROM client
                ORDER BY LglNm ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $divisions = array();
                $sql1 = "SELECT id, plant_id, status, client_code, LglNm, TrdNm, clientGroup,
                         serviceCategory, serviceDescription, serviceDescriptionData, selectedProductCodes
                         FROM client
                         WHERE clientGroup = '".$row['client_code']."'
                         ORDER BY LglNm ASC";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1['clientServiceDetails'] = poFormLoadClientServiceDetails($conn, $row1['client_code']);
                        $divisions[] = $row1;
                    }
                }
                $row['clientGroups'] = $divisions;
                $row['clientServiceDetails'] = poFormLoadClientServiceDetails($conn, $row['client_code']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    // Print ALL products (no client/selection dependency). Pack sizes decoded.
    else if ($_GET["type"] == "getAllProducts") {
        $output = array();
        $plantFilter = "";
        if (isset($_GET["plant_id"]) && $_GET["plant_id"] !== "") {
            $plantFilter = " WHERE plant_id = '".$_GET["plant_id"]."'";
        }
        $sql = "SELECT * FROM product".$plantFilter." ORDER BY product_name ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["pack_sizes"] = json_decode($row["pack_sizes"]);
                $productCodeEsc = $conn->real_escape_string($row['product_code']);
                $packSizes = array();
                $sqlPack = "SELECT DISTINCT b.pack_size, b.unit, b.packing_type, b.batch_size
                            FROM unitformula a
                            INNER JOIN (
                                SELECT MAX(id) AS max_id
                                FROM unitformula
                                WHERE product_code = '".$productCodeEsc."'
                            ) latest ON a.id = latest.max_id
                            LEFT JOIN unitformula_pm_dtl b ON a.id = b.unit_formula_id
                            WHERE b.pack_size IS NOT NULL AND TRIM(b.pack_size) != ''
                            ORDER BY b.pack_size";
                $resultPack = $conn->query($sqlPack);
                if ($resultPack && $resultPack->num_rows > 0) {
                    while ($rowPack = $resultPack->fetch_assoc()) {
                        $packSizes[] = $rowPack;
                    }
                }
                $row["unit_formula_pack_sizes"] = $packSizes;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    // Pack sizes saved in unit formula (latest formula for product).
    else if ($_GET["type"] == "getPackSizesByProductCode") {
        $output = array();
        $productCode = isset($_GET["product_code"]) ? $conn->real_escape_string($_GET["product_code"]) : "";
        if ($productCode !== "") {
            $sqlPack = "SELECT DISTINCT b.pack_size, b.unit, b.packing_type, b.batch_size
                        FROM unitformula a
                        INNER JOIN (
                            SELECT MAX(id) AS max_id
                            FROM unitformula
                            WHERE product_code = '".$productCode."'
                        ) latest ON a.id = latest.max_id
                        LEFT JOIN unitformula_pm_dtl b ON a.id = b.unit_formula_id
                        WHERE b.pack_size IS NOT NULL AND TRIM(b.pack_size) != ''
                        ORDER BY b.pack_size";
            $resultPack = $conn->query($sqlPack);
            if ($resultPack && $resultPack->num_rows > 0) {
                while ($rowPack = $resultPack->fetch_assoc()) {
                    $output[] = $rowPack;
                }
            }
        }
        echo json_encode($output);
    }
}
