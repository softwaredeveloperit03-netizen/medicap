<?php


 ini_set('display_errors', 1);
 error_reporting(E_ALL);
 
    require '../db.php';
    // require 'SimpleXLSX.php';
   require_once __DIR__ . '/SimpleXLSX.php';
if (!class_exists('SimpleXLSX')) {
    die(json_encode(["message" => "Error: SimpleXLSX class not found!"]));
}

    require '../token.php';
     require '../tcpdf/tcpdf.php';
    require '../phpmailer/class.phpmailer.php';
    require '../PHPExcel/Classes/PHPExcel.php';
  
 header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $entry_date1 = date("Y-m-d");
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
  //  $_GET["plant_id"] = "";
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	     $string = explode("$",$string);
	  //  $_GET["plant_id"] = $string[0];
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
   
        if ($_GET["type"] == "UploadAttendance") {
            // Check if a file is uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(["message" => "No file uploaded or file upload error!"]));
}

$file = $_FILES['file']['tmp_name'];

// Parse the XLSX file
if (!$xlsx = SimpleXLSX::parse($file)) {
    die(json_encode(["message" => "Error parsing XLSX file!"]));
}

$data = $xlsx->rows();

$employee_id = "";
$employee_name = "";
$headers = [];
$attendance_records = [];

// Loop through rows
foreach ($data as $rowIndex => $row) {
    if ($rowIndex < 8) continue; // Skip first 8 rows

    if ($rowIndex == 8) {
        // Extract headers from 9th row (index 8)
        $headers = array_slice($row, 1); // Headers start from column B
        continue;
    }

    if (!empty($row[1]) && strpos($row[1], "Employee :") !== false) {
        // Extract Employee ID & Name from column C (index 2)
        $emp_data = explode("-", $row[2]); 
        $employee_id = isset($emp_data[0]) ? trim($emp_data[0]) : "";
        $employee_name = isset($emp_data[1]) ? trim($emp_data[1]) : "";
        continue;
    }

    // Process attendance data (if the row has a valid date)
    if (!empty($row[1]) && strtotime($row[1])) {
        $attendance_records[] = [
            "employeeId" => $employee_id,
            "employeeName" => $employee_name,
            "Date" => date('Y-m-d', strtotime($row[1])),
            "In Door" => $row[2] ?? "",
            "In Time" => $row[3] ?? "",
            "Out Door" => $row[4] ?? "",
            "Out Time" => $row[5] ?? "",
            "Duration" => $row[6] ?? "",
        ];
    }
}
 
// Insert into Database
if (!empty($attendance_records)) {
    $conn = new mysqli("localhost", "your_username", "your_password", "your_database");

    if ($conn->connect_error) {
        die(json_encode(["message" => "Database connection failed!"]));
    }

    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, employee_name, date, in_door, in_time, out_door, out_time, duration) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($attendance_records as $entry) {
        $stmt->bind_param(
            "ssssssss",
            $entry["employeeId"],
            $entry["employeeName"],
            $entry["Date"],
            $entry["In Door"],
            $entry["In Time"],
            $entry["Out Door"],
            $entry["Out Time"],
            $entry["Duration"]
        );
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();

    echo json_encode(["message" => "Data inserted successfully!", "records" => count($attendance_records)]);
} else {
    echo json_encode(["message" => "No valid attendance data found!"]);
}
        }
    }

   
$conn->close();
?>