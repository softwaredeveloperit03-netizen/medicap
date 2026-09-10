<?php
 
 
  
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;




$secretKey = 'e5b4a9098f7c4d41c6efc15c7de5020a9a90ab01d5e6b3cd1377a6a9fc3f3f1a';

// Allow CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Extract headers safely
$headers = [];

if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
}

// Normalize header keys
$headers = array_change_key_case($headers, CASE_LOWER);

$jwt = null;
$isOpenAccess = false;

// 1. From Bearer Authorization header
if (isset($headers['authorization']) && preg_match('/Bearer\s(\S+)/', $headers['authorization'], $matches)) {
    $jwt = $matches[1];
}

// 2. Fallback to GET (for window.open)
if (!$jwt && isset($_GET['token'])) {
    $jwt = $_GET['token'];
    $isOpenAccess = true;
}

// 3. If no token at all
if (!$jwt) {
    http_response_code(401);
    die('No token provided.');
}

// 4. Decode the token
try {
    $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
    $userData = (array) $decoded->data;

    // Set user info for downstream use
    $_GET["emp_id"] = $userData['emp_id'] ?? null;
    $_GET["department"] = $userData['department'] ?? null;

    // Limit open GET access to specific scripts
    if ($isOpenAccess && !in_array(basename($_SERVER['SCRIPT_NAME']), ['secureReport.php', 'publicViewer.php'])) {
        http_response_code(403);
        die('Token in URL not allowed for this API.');
    }

} catch (Exception $e) {
    http_response_code(401);
    die('Invalid or expired token: ' . $e->getMessage());
}

 
  
 
 
 
 
 ?>
