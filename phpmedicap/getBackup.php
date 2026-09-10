<?php

//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);
 
 
require 'db.php';
require 'token.php';

$token = $_GET["token"] ?? '';
$type = $_GET["type"] ?? '';

$sql = "SELECT * FROM token WHERE token='$token'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Set download headers
    $filename = $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    $mysqli = new mysqli($servername, $username, $password, $dbname);
    if ($mysqli->connect_error) {
        die('Connect Error: ' . $mysqli->connect_error);
    }

    $dump = "-- Database Backup\n";
    $dump .= "-- Database: `$dbname`\n";
    $dump .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
    
    $dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $dump .= "SET AUTOCOMMIT = 0;\n";
    $dump .= "START TRANSACTION;\n";
    $dump .= "SET time_zone = '+00:00';\n";
    $dump .= "SET NAMES utf8mb4;\n\n";


    $dump = "-- Database Backup\n";
    $dump .= "-- Database: `$dbname`\n";
    $dump .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";

    $tables = [];
    $result_tables = $mysqli->query("SHOW TABLES");
    while ($row = $result_tables->fetch_row()) {
        if (!empty($row[0])) {
            $tables[] = $row[0];
        }
    }

    foreach ($tables as $table) {
        if (empty($table)) continue;

        // Escape table name safely
        $escaped_table = "`" . str_replace("`", "``", $table) . "`";

        // Get table structure
        $res = $mysqli->query("SHOW CREATE TABLE $escaped_table");
        if ($res && $row = $res->fetch_row()) {
            $dump .= "-- Table structure for `$table`\n";
            $dump .= "DROP TABLE IF EXISTS $escaped_table;\n";
            $dump .= $row[1] . ";\n\n";
        } else {
            // Skip if structure can't be read
            continue;
        }

        // Get table data
        $res = $mysqli->query("SELECT * FROM $escaped_table");
        if ($res && $res->num_rows > 0) {
            $dump .= "-- Dumping data for `$table`\n";
            while ($data = $res->fetch_assoc()) {
                $columns = array_map(fn($col) => "`" . str_replace("`", "``", $col) . "`", array_keys($data));
                $values = array_map(function ($val) use ($mysqli) {
                    return is_null($val) ? 'NULL' : "'" . $mysqli->real_escape_string($val) . "'";
                }, array_values($data));

                $dump .= "INSERT INTO $escaped_table (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
            }
            $dump .= "\n";
        }
    }

    echo $dump;
    $dump .= "COMMIT;\n";
    exit;

} else {
    http_response_code(401);
    echo "❌ Invalid Token";
}
?>
