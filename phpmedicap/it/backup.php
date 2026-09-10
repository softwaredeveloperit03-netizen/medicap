<?php
require '../db.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

date_default_timezone_set("Asia/Kolkata");

$type = $_GET["type"] ?? '';
$token = $_GET["token"] ?? '';
$plant_id = $_GET["plant_id"] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);

function send_json($payload, $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function ensure_directory($path)
{
    if (!is_dir($path)) {
        return mkdir($path, 0777, true);
    }
    return true;
}

function backup_root_path()
{
    return 'D:\\CycloneBackups\\sql';
}

function backup_log_file_path()
{
    return __DIR__ . '/../../../upload/it/backup/backup_log.json';
}

function load_backup_logs($path)
{
    if (!file_exists($path)) {
        return array();
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return array();
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array();
}

function save_backup_logs($path, $logs)
{
    $logs = array_slice($logs, 0, 500);
    ensure_directory(dirname($path));
    file_put_contents($path, json_encode($logs, JSON_PRETTY_PRINT));
}

function append_backup_log($entry)
{
    $log_file = backup_log_file_path();
    $logs = load_backup_logs($log_file);
    array_unshift($logs, $entry);
    save_backup_logs($log_file, $logs);
}

function format_bytes($bytes)
{
    if ($bytes <= 0) {
        return "0 B";
    }
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $power = floor(log($bytes, 1024));
    $power = min($power, count($units) - 1);
    $value = $bytes / pow(1024, $power);
    return round($value, 2) . ' ' . $units[$power];
}

function prune_old_day_folders($root, $keep_days = 30)
{
    if (!is_dir($root)) {
        return;
    }

    $dirs = array();
    foreach (glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $folder) {
        $name = basename($folder);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $name)) {
            $dirs[$folder] = filemtime($folder);
        }
    }

    if (count($dirs) <= $keep_days) {
        return;
    }

    asort($dirs);
    $remove = array_slice(array_keys($dirs), 0, count($dirs) - $keep_days);
    foreach ($remove as $dir) {
        delete_directory_recursive($dir);
    }
}

function delete_directory_recursive($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            delete_directory_recursive($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function resolve_mysqldump_path()
{
    // Optional override via environment variable.
    $env_override = getenv('MYSQLDUMP_PATH');
    if ($env_override && file_exists($env_override)) {
        return $env_override;
    }

    $candidates = array(
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        'C:\\xampp\\mysql\\bin\\mysqldump',
        'D:\\xampp\\mysql\\bin\\mysqldump.exe',
        'D:\\xampp\\mysql\\bin\\mysqldump',
        'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
        'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
        'C:\\Program Files\\MariaDB 10.6\\bin\\mysqldump.exe',
        'C:\\Program Files\\MariaDB 10.5\\bin\\mysqldump.exe'
    );

    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            return $candidate;
        }
    }

    // Fallback: search from PATH on Windows.
    $where_output = @shell_exec('where mysqldump 2>nul');
    if ($where_output) {
        $lines = preg_split('/\r\n|\r|\n/', trim($where_output));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && file_exists($line)) {
                return $line;
            }
        }
    }

    return '';
}

function create_full_sql_backup($conn, $db_config, $requested_by, $trigger_type, $slot)
{
    $root = backup_root_path();
    $day_folder = $root . DIRECTORY_SEPARATOR . date('Y-m-d');
    $time_folder = $day_folder . DIRECTORY_SEPARATOR . date('H-i-s');

    ensure_directory($root);
    ensure_directory($day_folder);
    ensure_directory($time_folder);

    $file_name = $db_config["dbname"] . '_' . $slot . '_' . date('Y-m-d_H-i-s') . '.sql';
    $file_path = $time_folder . DIRECTORY_SEPARATOR . $file_name;
    $error_file = $time_folder . DIRECTORY_SEPARATOR . 'mysqldump_error.log';
    $mysqldump = resolve_mysqldump_path();

    if ($mysqldump === '') {
        return array(
            "status" => "error",
            "message" => "mysqldump.exe not found on server machine."
        );
    }

    // Build a Windows-safe command for cmd.exe.
    $command =
        'cmd /c ""' . $mysqldump . '"' .
        ' --host="' . $db_config["servername"] . '"' .
        ' --user="' . $db_config["username"] . '"' .
        ' --password="' . $db_config["password"] . '"' .
        ' --routines --triggers --events --single-transaction --quick --databases "' . $db_config["dbname"] . '"' .
        ' > "' . $file_path . '"' .
        ' 2> "' . $error_file . '""';

    $output = array();
    $return_code = 1;
    exec($command, $output, $return_code);

    if ($return_code !== 0 || !file_exists($file_path) || filesize($file_path) <= 0) {
        $error_text = file_exists($error_file) ? file_get_contents($error_file) : 'Unknown mysqldump error.';
        return array(
            "status" => "error",
            "message" => trim((string)$error_text) === '' ? 'Database backup failed.' : $error_text
        );
    }

    $latest_folder = $root . DIRECTORY_SEPARATOR . 'latest' . DIRECTORY_SEPARATOR . $slot;
    ensure_directory($latest_folder);
    $latest_file = $latest_folder . DIRECTORY_SEPARATOR . $db_config["dbname"] . '_' . $slot . '_latest.sql';
    @copy($file_path, $latest_file);

    prune_old_day_folders($root, 30);

    $size = filesize($file_path);
    $log_entry = array(
        "id" => uniqid('bk_', true),
        "backup_time" => date('Y-m-d H:i:s'),
        "slot" => $slot,
        "trigger_type" => $trigger_type,
        "status" => "success",
        "requested_by" => $requested_by,
        "backup_file_name" => $file_name,
        "backup_file_path" => $file_path,
        "backup_size_bytes" => $size,
        "backup_size_text" => format_bytes($size),
        "local_save_mode" => "Auto saved to D drive",
        "downloaded_from_server" => ($trigger_type === 'manual') ? "Yes" : "No"
    );
    append_backup_log($log_entry);

    return array(
        "status" => "success",
        "message" => "Backup generated successfully.",
        "file_path" => $file_path,
        "file_name" => $file_name,
        "file_size_bytes" => $size,
        "file_size_text" => format_bytes($size),
        "log_entry" => $log_entry
    );
}

function validate_frontend_token($conn, $token)
{
    if ($token === '') {
        return null;
    }

    $safe_token = $conn->real_escape_string($token);
    $sql = "SELECT * FROM token WHERE token='" . $safe_token . "'";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows <= 0) {
        return null;
    }

    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        return array(
            "emp_id" => $string[0] ?? '',
            "department" => $string[1] ?? '',
            "username" => $string[2] ?? ''
        );
    }

    return null;
}

function is_local_scheduler_request()
{
    $allowed = array('127.0.0.1', '::1');
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($remote, $allowed, true);
}

$user_ctx = validate_frontend_token($conn, $token);

if ($type === 'runScheduledBackup') {
    if (!is_local_scheduler_request()) {
        send_json(array("status" => "invalid", "message" => "Scheduled backup API is allowed only from localhost."), 403);
    }

    $slot = strtolower(trim($_GET["slot"] ?? ''));
    if ($slot !== 'morning' && $slot !== 'evening') {
        $slot = date('H') < 12 ? 'morning' : 'evening';
    }

    $backup = create_full_sql_backup(
        $conn,
        array(
            "servername" => $servername,
            "username" => $username,
            "password" => $password,
            "dbname" => $dbname
        ),
        'SYSTEM-SCHEDULER',
        'auto',
        $slot
    );

    send_json($backup, $backup["status"] === 'success' ? 200 : 500);
}

if ($user_ctx === null) {
    send_json(array("status" => "invalid", "message" => "Invalid token"), 401);
}

$emp_id = $user_ctx["emp_id"];
$department = $user_ctx["department"];

$log_sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','" .
    $conn->real_escape_string($token) . "','" .
    $conn->real_escape_string($type) . "','" .
    $entry_date . "','" .
    $conn->real_escape_string($department) . "','" .
    $conn->real_escape_string($emp_id) . "','" .
    $_SERVER['REQUEST_METHOD'] . "','" .
    $_SERVER['REMOTE_ADDR'] . "')";
$conn->query($log_sql);

if ($type === 'getBackupLogs') {
    $logs = load_backup_logs(backup_log_file_path());
    send_json($logs);
} else if ($type === 'manualBackupDownload') {
    $slot = date('H') < 12 ? 'morning' : 'evening';
    $backup = create_full_sql_backup(
        $conn,
        array(
            "servername" => $servername,
            "username" => $username,
            "password" => $password,
            "dbname" => $dbname
        ),
        $emp_id,
        'manual',
        $slot
    );

    if ($backup["status"] !== 'success') {
        send_json($backup, 500);
    }

    $file = $backup["file_path"];
    $filename = $backup["file_name"];
    if (!file_exists($file)) {
        send_json(array("status" => "error", "message" => "Backup file not found after generation."), 500);
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($file);
    exit;
} else if ($type === 'getBackupSchedule') {
    $base_url =
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
        '://' .
        $_SERVER['HTTP_HOST'] .
        dirname($_SERVER['REQUEST_URI']);

    $morning_url = $base_url . '/backup.php?type=runScheduledBackup&slot=morning';
    $evening_url = $base_url . '/backup.php?type=runScheduledBackup&slot=evening';

    send_json(array(
        "status" => "success",
        "schedule" => array(
            array("time" => "06:20", "slot" => "morning", "url" => $morning_url),
            array("time" => "18:30", "slot" => "evening", "url" => $evening_url)
        )
    ));
} else {
    send_json(array("status" => "invalid", "message" => "Unknown type"), 400);
}

$conn->close();
?>
