<?php
// Allow CORS for requests from any domain
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Your existing backup script below
$batFile = __DIR__ . "/backup.bat"; // Ensure the correct path

if (!file_exists($batFile)) {
    echo json_encode(["status" => "error", "message" => "Backup file not found!"]);
    exit;
}

$output = shell_exec("cmd /c $batFile 2>&1");

echo json_encode(["status" => "success", "message" => $output]);
?>
