<?php
/**
 * Shared bootstrap for medicap MRP feature PHP endpoints.
 * Creates tables / adds missing columns when helpers run.
 */
if (!defined('MRP_FO_BOOTSTRAPPED')) {
    define('MRP_FO_BOOTSTRAPPED', true);

    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/mrp-feature-error.log');

    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../token.php';

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }

    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"] ?? "";
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        $input = array();
    }

    $tokenEsc = $conn->real_escape_string((string)$token);
    $sql = "SELECT * FROM token WHERE token='" . $tokenEsc . "'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = $_GET["emp_id"] ?? "";
    $_GET["department"] = "";

    $mrp_auth_ok = false;
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $string = decrypt("decrypt", $_GET["token"], $row["key1"], $row["key2"]);
            $parts = explode("$", $string);
            $_GET["emp_id"] = $parts[0] ?? "";
            $_GET["department"] = $parts[1] ?? "";
            break;
        }
        $mrp_auth_ok = true;
        $txt = '{"process":"FRONTEND","token":"'.$token.'","action":"'.($_GET["type"] ?? "").'","actiontime":"'.$entry_date.'","department":"'.$_GET["department"].'","emp_id":"'.$_GET["emp_id"].'","method":"'.($_SERVER["REQUEST_METHOD"] ?? "").'","REMOTE_ADDR":"'.($_SERVER["REMOTE_ADDR"] ?? "").'"}';
        @file_put_contents(__DIR__ . '/../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    if (!$mrp_auth_ok) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('status' => 'error', 'message' => 'Invalid or missing token'));
        exit;
    }
}
