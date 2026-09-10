<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

function escSample($conn, $value) {
    return $conn->real_escape_string(isset($value) ? $value : '');
}

function ensureMarketingSampleUnitColumn($conn) {
    $check = $conn->query("SHOW COLUMNS FROM `marketing_sample` LIKE 'unit'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE `marketing_sample` ADD COLUMN `unit` VARCHAR(50) NULL AFTER `quantity_required`");
    }
}

function sampleListWhereClause($conn, $plant_id, $pendingOnly = false) {
    $where = "m.plant_id='".escSample($conn, $plant_id)."'
              AND m.client_code IS NOT NULL AND m.client_code != ''
              AND m.product_name IS NOT NULL AND m.product_name != ''
              AND m.quantity_required IS NOT NULL AND m.quantity_required != ''";
    if ($pendingOnly) {
        $where .= " AND (m.status='Pending' OR m.status IS NULL OR m.status='')";
    }
    return $where;
}

function ensureMarketingSampleWorkflowColumns($conn) {
    $cols = array(
        'status'       => "VARCHAR(40) DEFAULT 'Pending'",
        'issued_qty'   => "VARCHAR(50) NULL",
        'issued_unit'  => "VARCHAR(50) NULL",
        'issued_by'    => "VARCHAR(50) NULL",
        'issued_date'  => "DATETIME NULL",
        'qa_remarks'   => "TEXT NULL",
    );
    foreach ($cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `marketing_sample` LIKE '".$col."'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `marketing_sample` ADD COLUMN `".$col."` ".$def);
        }
    }
}

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    ensureMarketingSampleUnitColumn($conn);
    ensureMarketingSampleWorkflowColumns($conn);

    if ($_GET["type"] == "saveSample") {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(array("status" => "error", "message" => "Invalid request method"));
            exit;
        }

        $client_code = escSample($conn, isset($input["client_code"]) ? $input["client_code"] : '');
        $requirement = escSample($conn, isset($input["requirement"]) ? $input["requirement"] : '');
        $quantity_required = escSample($conn, isset($input["quantity_required"]) ? $input["quantity_required"] : '');
        $unit = escSample($conn, isset($input["unit"]) ? $input["unit"] : '');
        $purpose = escSample($conn, isset($input["purpose"]) ? $input["purpose"] : '');
        $product_name = escSample($conn, isset($input["product_name"]) ? $input["product_name"] : '');

        if ($client_code === '' || $requirement === '' || $quantity_required === '' || $unit === '' || $purpose === '' || $product_name === '') {
            echo json_encode(array("status" => "error", "message" => "All fields are required"));
            exit;
        }

         $sql = "INSERT INTO marketing_sample (client_code, requirement , quantity_required, unit, purpose, product_name, status, entry_by, entry_date, plant_id)
         VALUES ('".$client_code."','".$requirement."','".$quantity_required."',
         '".$unit."','".$purpose."', '".$product_name."',
         'Pending','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getSamples") {
        $output = array();
        $sql = "SELECT m.*, c.LglNm as client_name, c.TrdNm as client_trade_name
                FROM marketing_sample m
                LEFT JOIN client c ON m.client_code = c.client_code
                WHERE ".sampleListWhereClause($conn, $_GET["plant_id"], true)."
                ORDER BY m.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['status'])) { $row['status'] = 'Pending'; }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingSampleCount") {
        $count = 0;
        $sql = "SELECT COUNT(*) as cnt FROM marketing_sample m
                WHERE ".sampleListWhereClause($conn, $_GET["plant_id"], true);
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $count = (int)$row['cnt'];
        }
        echo json_encode(array("count" => $count));
    }
    else if ($_GET["type"] == "issueSample") {
        $id = escSample($conn, isset($input["id"]) ? $input["id"] : $_GET["id"]);
        $issued_qty = escSample($conn, isset($input["issued_qty"]) ? $input["issued_qty"] : '');
        $issued_unit = escSample($conn, isset($input["issued_unit"]) ? $input["issued_unit"] : '');
        $remarks = escSample($conn, isset($input["qa_remarks"]) ? $input["qa_remarks"] : '');

        if ($id === '' || $issued_qty === '') {
            echo json_encode(array("status" => "error", "message" => "Issued quantity is required"));
        } else {
            $sql = "UPDATE marketing_sample SET
                        issued_qty='".$issued_qty."',
                        issued_unit='".$issued_unit."',
                        issued_by='".$_GET["emp_id"]."',
                        issued_date='".$entry_date."',
                        qa_remarks='".$remarks."',
                        status='Issued'
                    WHERE id='".$id."'";
            if ($conn->query($sql)) {
                echo json_encode(array("status" => "success"));
            } else {
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        }
    }
    else if ($_GET["type"] == "rejectSample") {
        $id = escSample($conn, isset($input["id"]) ? $input["id"] : $_GET["id"]);
        $remarks = escSample($conn, isset($input["qa_remarks"]) ? $input["qa_remarks"] : '');
        $sql = "UPDATE marketing_sample SET
                    status='Rejected',
                    issued_by='".$_GET["emp_id"]."',
                    issued_date='".$entry_date."',
                    qa_remarks='".$remarks."'
                WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    }
    else if ($_GET["type"] == "updateCheckingStatus") {
        $sql = "UPDATE marketing_sample SET status='".escSample($conn, $_GET["status"])."' WHERE id='".escSample($conn, $_GET["id"])."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getSamplesLog") {
        $output = array();
        $sql = "SELECT m.*, c.LglNm as client_name, c.TrdNm as client_trade_name
                FROM marketing_sample m
                LEFT JOIN client c ON m.client_code = c.client_code
                WHERE ".sampleListWhereClause($conn, $_GET["plant_id"], false)."
                ORDER BY m.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['status'])) { $row['status'] = 'Pending'; }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>
