
 
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

//   require 'db.php';
//     require 'token.php';
    // require'tcpdf/tcpdf.php';
    
        require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

   




    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $entry_date1 = date("Y-m-d");
    $input = json_decode(file_get_contents('php://input'),true);        

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
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


 
	if($_GET["type"]=="getFace") {
	$sql = "SELECT face_encoding,emp_id,firstname,lastname,department,designation FROM employee WHERE face_encoding IS NOT NULL";
$result = $conn->query($sql);

$faces = [];
while ($row = $result->fetch_assoc()) {
    $faces[] = [
        "employeeId" => $row["emp_id"],
         "emp_name" => $row['firstname'] . ' ' . $row['lastname'], // Correct concatenation
         "department" => $row['department']  , // Correct concatenation
         "designation" => $row['designation']  , // Correct concatenation
        "descriptor" => $row["face_encoding"]
    ];
}

echo json_encode($faces);
	    
	}
        else if($_GET["type"]=="registerface") {
	    
	    
	    
	    $data = json_decode(file_get_contents("php://input"));

if (!isset($data->employeeId) || !isset($data->descriptor)) {
    die(json_encode(["status" => "error", "message" => "Invalid input"]));
}

$employeeId = $conn->real_escape_string($data->employeeId);
$descriptor = $conn->real_escape_string($data->descriptor);
            
             $sql = "Update employee set  face_encoding='$descriptor' where emp_id='$employeeId' and plant_id='".$_GET["plant_id"]."'";
// $sql = "INSERT INTO faces (employeeId, descriptor) VALUES ('$employeeId', '$descriptor')";
if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Face data saved successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
	    
	    
	     
	}
 else if($_GET["type"]=="mark_attendance"){
  

// Mark attendance in the database
$date = date('Y-m-d');
$time = date('H:i:s');
$recognized_emp_id=$_GET['emp_id1'];
// Check if employee already has an entry today
$query = "SELECT * FROM attendance WHERE emp_id = '$recognized_emp_id' AND date = '$date'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0 ) {
    $row = mysqli_fetch_assoc($result);
    $entry_time = $row['entry_time'];

    // Convert times to timestamps
    $entryTimestamp = strtotime($entry_time);
    $currentTimestamp = strtotime($time);
    $diffMinutes = ($currentTimestamp - $entryTimestamp) / 60;
    // If entry exists, update the exit time
    if ($diffMinutes > 45) {
    $update = "UPDATE attendance SET exit_time = '$time' WHERE emp_id = '$recognized_emp_id' AND date = '$date'";
    mysqli_query($conn, $update);
    
       $query = "SELECT * FROM employee WHERE emp_id = '$recognized_emp_id'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $employee = mysqli_fetch_assoc($result);
            echo json_encode([
                "message" => "Exit time recorded for $recognized_emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                "data" => $employee
            ]);
        }
    }else{
        
    
       $query = "SELECT * FROM employee WHERE emp_id = '$recognized_emp_id'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $employee = mysqli_fetch_assoc($result);
            echo json_encode([
                "message" => "Already Attedence Marked For $recognized_emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                "data" => $employee
            ]);
        }
    
    }
    // echo json_encode(["message" => "Exit time recorded for $recognized_emp_id"]);
} else {
    // If no entry, mark entry time
    $insert = "INSERT INTO attendance (emp_id, date, entry_time,entry_date) VALUES ('$recognized_emp_id', '$date', '$time','$date')";
    mysqli_query($conn, $insert);
     $query = "SELECT * FROM employee WHERE emp_id = '$recognized_emp_id'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $employee = mysqli_fetch_assoc($result);
            echo json_encode([
                "message" => "Entry time recorded for $recognized_emp_id",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                "data" => $employee
            ]);
        }
    // echo json_encode(["message" => "Entry time recorded for $recognized_emp_id"]);
}
 }
         else if($_GET["type"]=="verify_employee"){
             
             $recognized_emp_id = $_GET['employee_id'];
        
        // Query to check if employee exists
        $query = "SELECT * FROM employee WHERE emp_id = '$recognized_emp_id'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            // Fetch employee data
            $employee = mysqli_fetch_assoc($result);
            
            echo json_encode([
                "status" => "success",
                // "emp_name" => $employee[0].['firstname']+' '+$employee[0].['firstname'];
                     "emp_name" => $employee['firstname'] . ' ' . $employee['lastname'], // Correct concatenation
                     "department" => $employee['department']  , // Correct concatenation
                     "designation" => $employee['designation']  , // Correct concatenation
                "data" => $employee
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Employee not found"]);
        }
         }
 


// else{
//     echo 'no record';
// }
$conn->close();
?>

