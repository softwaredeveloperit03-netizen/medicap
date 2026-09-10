<?php
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
//     ini_set('display_errors', 1);
// error_reporting(E_ALL);
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $Todate_entry_date = date("Y-m-d");
    $input = json_decode(file_get_contents('php://input'),true);

     $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    //  echo('hi');
    if($result->num_rows > 0){
        //  echo('hi');
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    //  echo('hi');
    if($_GET["type"] == "MedicalYearRecord") {
    
//  echo('hi');
// Fetch client ID from query parameter
// $yearInput = $_GET['year'];

// Prepare CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=Medical_list.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, [
   
    'Employee Id', 
    'Candidate Name', 
    'Checkup Type', 
    'Last Checkup Date', 
    'Next Checkup Date', 
  
]);

// Fetch data from database
$yearInput = mysqli_real_escape_string($conn, $_GET["year"]);  
$plant_id = mysqli_real_escape_string($conn, $_GET["plant_id"]);

$sql = "SELECT 
            m.emp_id, 
            m.frequency, 
            m.last_checkup_date, 
            mc.id AS lastId,
            DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH) AS next_checkup_date,
            c.candidate_name
        FROM (
            SELECT 
                emp_id, 
                MAX(testDate) AS last_checkup_date,
                frequency
            FROM medicalcheckup
            WHERE status != 'pending' 
            AND lastIdGeted = 'Pending' 
            AND plant_id = '$plant_id'
            GROUP BY emp_id, frequency
        ) m
        JOIN medicalcheckup mc 
            ON m.emp_id = mc.emp_id 
            AND m.last_checkup_date = mc.testDate
        JOIN candidate c 
            ON mc.emp_id = c.id
        WHERE YEAR(DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH)) = '$yearInput'  
        AND mc.lastIdGeted = 'Pending'
        ORDER BY m.last_checkup_date DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row["checkup_type"] = "Normal";
        $row["type"] = "Candidate";
        // Prepare row data
        $data = [
         
            $row['emp_id'],
            $row['candidate_name'],
            $row['checkup_type'],
            $row['last_checkup_date'],
            $row['next_checkup_date'],
            
        ];

        // Add row to CSV
        fputcsv($output, $data);

        $i++;
    }
} else {
    // Add message for no data
    fputcsv($output, ['No data found for this client']);
}

// Close the output stream
fclose($output);
exit;


}
    if($_GET["type"] == "MedicalMonthRecord") {
    
//  echo('hi');
// Fetch client ID from query parameter
// $yearInput = $_GET['year'];

// Prepare CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=stock_report.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, [
    'Sr. No.', 
    'Employee Id', 
    'Candidate Name', 
    'Checkup Type', 
    'Last Checkup Date', 
    'Next Checkup Date', 
  
]);

// Fetch data from database
  $monthInput = $_GET["month"]; 
$date = DateTime::createFromFormat('Y-m', $monthInput);
$formattedYear = $date->format('Y'); // Converts to "05-2025"
$formattedMonth = $date->format('m'); // Converts to "05-2025"
        $output = [];

                     $sql = "SELECT 
                        m.emp_id, 
                        m.frequency, 
                        m.last_checkup_date, 
                        mc.id AS lastId,
                        DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH) AS next_checkup_date,
                        c.candidate_name
                    FROM (
                        SELECT 
                            emp_id, 
                            MAX(testDate) AS last_checkup_date,
                            frequency
                        FROM medicalcheckup
                        WHERE status != 'pending' 
                        AND lastIdGeted = 'Pending' 
                        AND plant_id = '".$_GET['plant_id']."'
                        GROUP BY emp_id, frequency
                    ) m
                    JOIN medicalcheckup mc ON m.emp_id = mc.emp_id AND m.last_checkup_date = mc.testDate
                    JOIN candidate c ON mc.emp_id = c.id
                    WHERE YEAR(DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH)) = '$formattedYear' 
                    AND MONTH(DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH)) = '$formattedMonth'
                    and mc.lastIdGeted='Pending'
                    ORDER BY m.last_checkup_date DESC;
";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row["checkup_type"] = "Normal";
        $row["type"] = "Candidate";
        // Prepare row data
        $data = [
            $i,
           
            $row['emp_id'],
            $row['candidate_name'],
            $row['checkup_type'],
            $row['last_checkup_date'],
            $row['next_checkup_date'],
            
        ];

        // Add row to CSV
        fputcsv($output, $data);

        $i++;
    }
} else {
    // Add message for no data
    fputcsv($output, ['No data found for this client']);
}

// Close the output stream
fclose($output);
exit;


}



    if ($_GET["type"] == "getCandidates") {
        $output = Array();
         $sql = "SELECT * FROM candidate WHERE checkup_type = 'Pre Employeement'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["type"] = "Candidate";
                 $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getCandidates_medical_due") {
        $output = Array();
       $sql = "SELECT 
            m.emp_id, 
            m.frequency, 
            m.last_checkup_date, 
            m.lastId,
            DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH) AS next_checkup_date 
        FROM (
            SELECT 
                emp_id, 
                MAX(testDate) AS last_checkup_date,
                (SELECT id 
                 FROM medicalcheckup 
                 WHERE emp_id = mc.emp_id 
                 AND status != 'pending' 
                 AND lastIdGeted = 'Pending' 
                 AND plant_id = '".$_GET["plant_id"]."'
                 ORDER BY testDate DESC 
                 LIMIT 1) AS lastId,
                (SELECT frequency 
                 FROM medicalcheckup 
                 WHERE emp_id = mc.emp_id 
                 AND status != 'pending' 
                 AND lastIdGeted = 'Pending' 
                 AND plant_id = '".$_GET["plant_id"]."'
                 ORDER BY testDate DESC 
                 LIMIT 1) AS frequency
            FROM medicalcheckup mc 
            WHERE status != 'pending' 
            AND lastIdGeted = 'Pending' 
            AND plant_id = '".$_GET["plant_id"]."'
            GROUP BY emp_id
        ) m";



         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $sql1 = "SELECT * FROM candidate WHERE id =  '".$row["emp_id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["checkup_type"] = "Normal";
                            $row["candidate_name"] = $row1["candidate_name"];
                        }
                    }
                    
                $row["type"] = "Candidate";
                $output[] = $row;
            }
        }
        
     
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getCandidates_medical_dueMonth") {
        $monthInput = $_GET["month"]; 
$date = DateTime::createFromFormat('Y-m', $monthInput);
$formattedYear = $date->format('Y'); // Converts to "05-2025"
$formattedMonth = $date->format('m'); // Converts to "05-2025"
        $output = [];

                     $sql = "SELECT 
                        m.emp_id, 
                        m.frequency, 
                        m.last_checkup_date, 
                        mc.id AS lastId,
                        DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH) AS next_checkup_date,
                        c.candidate_name
                    FROM (
                        SELECT 
                            emp_id, 
                            MAX(testDate) AS last_checkup_date,
                            frequency
                        FROM medicalcheckup
                        WHERE status != 'pending' 
                        AND lastIdGeted = 'Pending' 
                        AND plant_id = '".$_GET['plant_id']."'
                        GROUP BY emp_id, frequency
                    ) m
                    JOIN medicalcheckup mc ON m.emp_id = mc.emp_id AND m.last_checkup_date = mc.testDate
                    JOIN candidate c ON mc.emp_id = c.id
                    WHERE YEAR(DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH)) = '$formattedYear' 
                    AND MONTH(DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH)) = '$formattedMonth'
                    and mc.lastIdGeted='Pending'
                    ORDER BY m.last_checkup_date DESC;
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row["checkup_type"] = "Normal";
        $row["type"] = "Candidate";
        $output[] = $row;
    }
}

echo json_encode($output);

    } 
    else if ($_GET["type"] == "getCandidates_medical_dueYear") {
      $yearInput = $_GET["year"]; 
$plant_id = $_GET["plant_id"];

$output = [];

  $sql = "SELECT 
            m.emp_id, 
            m.frequency, 
            m.last_checkup_date, 
            mc.id AS lastId,
            DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH) AS next_checkup_date,
            c.candidate_name
        FROM (
            SELECT 
                emp_id, 
                MAX(testDate) AS last_checkup_date,
                frequency
            FROM medicalcheckup
            WHERE status != 'pending' 
            AND lastIdGeted = 'Pending' 
            AND plant_id = '$plant_id'
            GROUP BY emp_id, frequency
        ) m
        JOIN medicalcheckup mc ON m.emp_id = mc.emp_id AND m.last_checkup_date = mc.testDate
        JOIN candidate c ON mc.emp_id = c.id
        WHERE YEAR(DATE_ADD(m.last_checkup_date, INTERVAL m.frequency MONTH)) = '$yearInput' 
        AND mc.lastIdGeted = 'Pending'
        ORDER BY m.last_checkup_date DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row["checkup_type"] = "Normal";
        $row["type"] = "Candidate";
        $output[] = $row;
    }
}

echo json_encode($output);

    } 
    else if ($_GET["type"] == "getPhysicians") {
        $output = Array();
        $sql = "SELECT * FROM physician WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "savePremedical") {
        
         $sql = "INSERT INTO medicalcheckup (form_type, emp_id, phisician_no, tests,frequency, entry_by, entry_date,checkup_type,plant_id) VALUES
         ('premedical','".$input["emp_id"]."', '".$input["phisician_no"]."', '".json_encode($input)."','".$input["frequency"]."',
         '".$_GET["emp_id"]."', '$entry_date','".$input["checkup_type"]."','".$_GET["plant_id"]."')";
         
        if ($conn->query($sql)) {
            
            if($input["due"]=='duecompleted'){
                
            $sql="update candidate set premedical='Due-complete' WHERE id='".$input["emp_id"]."'";
            }else{
                
            $sql="update candidate set premedical='complete' WHERE id='".$input["emp_id"]."'";
            }
            
            $conn->query($sql);
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    } 
    else if ($_GET["type"] == "savePremedicalMeha") {
        
        
         $sql = "INSERT INTO medicalcheckup (form_type, emp_id, phisician_no, tests,frequency, entry_by, entry_date,checkup_type,plant_id) VALUES
         ('premedical','".$input["emp_id"]."', '".$input["phisician_no"]."', '".json_encode($input)."','".$input["frequency"]."',
         '".$_GET["emp_id"]."', '$entry_date','".$input["checkup_type"]."','".$_GET["plant_id"]."')";
         
        if ($conn->query($sql)) {
            
            if($input["due"]=='duecompleted'){
                
            $sql="update candidate set premedical='Due-complete' WHERE id='".$input["emp_id"]."'";
            }else{
                
            $sql="update candidate set premedical='complete' WHERE id='".$input["emp_id"]."'";
            }
            
            $conn->query($sql);
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    
        
//         $sql1 = "INSERT INTO medical_rep_due (candidate_id, due_date, due_type, frequency, entry_by, entry_date) VALUES ";

// $values = []; // Store values here

// if (trim($input["frequency"]) == '3') {
//     $date = $Todate_entry_date;
//     for ($i = 0; $i < 8; $i++) {
//         $date = date('Y-m-d', strtotime($date . ' + 90 days'));
//         $values[] = "('".$input["emp_id"]."', '".$date."', 'Inspection', 'Quarterly', '".$_GET["emp_id"]."', '$entry_date')";
//     }
// } elseif (trim($input["frequency"]) == '6') {
//     $date = $Todate_entry_date;
//     for ($i = 0; $i < 4; $i++) {
//         $date = date('Y-m-d', strtotime($date . ' + 180 days'));
//         $values[] = "('".$input["emp_id"]."', '".$date."', 'Inspection', 'Half-Yearly', '".$_GET["emp_id"]."', '$entry_date')";
//     }
// } elseif (trim($input["frequency"]) == '12') {
//     $date = $Todate_entry_date;
//     for ($i = 0; $i < 2; $i++) {
//         $date = date('Y-m-d', strtotime($date . ' + 364 days'));
//         $values[] = "('".$input["emp_id"]."', '".$date."', 'Inspection', 'Annually', '".$_GET["emp_id"]."', '$entry_date')";
//     }
// }

// // Ensure values exist before executing the query
// if (!empty($values)) {
//     $sql1 .= implode(", ", $values); // Join values with commas
//     $conn->query($sql1);
// }

// echo "{\"status\":\"success\"}";

    } 
    else if ($_GET["type"] == "saveRegularRoutineCheckup") {
        
         $sql = "INSERT INTO medicalcheckup (form_type, emp_id, phisician_no, tests,frequency, entry_by, entry_date,checkup_type,plant_id,lastIdGeted) VALUES
         ('premedical','".$input["emp_id"]."', '".$input["phisician_no"]."', '".json_encode($input)."','".$input["frequency"]."',
         '".$_GET["emp_id"]."', '$entry_date','".$input["checkup_type"]."','".$_GET["plant_id"]."','Pending')";
         
        if ($conn->query($sql)) {
            $sql1 = "update medicalcheckup set lastIdGeted = 'DONE' WHERE id='".$input["lastId"]."'";
            $conn->query($sql1);
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    } 
    else if ($_GET["type"] == "saverePremedical") {
        $sql1="update medicalcheckup set status='Re-Medical' where id='".$_GET["em_id"]."'";
        if ($conn->query($sql1)) {
        
        $sql = "INSERT INTO medicalcheckup (form_type, emp_id, phisician_no, tests,frequency, entry_by, entry_date) VALUES ('premedical','".$input["emp_id"]."', '".$input["phisician"]."', '".json_encode($input)."','".$input["frequency"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }}
    } 
    else if ($_GET["type"] == "getMedicalCheckupReport") {
        
        $output = Array();
        $sql = "SELECT * FROM medicalcheckup WHERE form_type='Checkup'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                 $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getMedicalCheckupReportLog") {
        
        $output = Array();
        $sql = "SELECT m. *,m.id as m_id,c.id,c.department as Cdepartment FROM medicalcheckup m left JOIN 
        candidate c on m.emp_id=c.id WHERE  m.status != 'pending'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM candidate WHERE id='".$row["emp_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["candidate_name"] = $row1["candidate_name"];
                    }
                }
                
                $sql1 = "SELECT * FROM physician WHERE id='".$row["phisician_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["doctor_name"] = $row1["doctor_name"];
                    }
                }
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "re_checkup_medical") {
        
        $output = Array();
     $sql = "SELECT m.*, m.id AS m_id, c.id, c.department AS Cdepartment FROM medicalcheckup m LEFT JOIN candidate c ON m.emp_id = c.id";
                    //  WHERE m.tests_document != ''  and m.status!='status' 
                    //  AND DATE_ADD(m.entry_date, INTERVAL m.frequency MONTH) <= CURDATE() ";
                          
    //  $sql = "SELECT * FROM medicalcheckup WHERE form_type='premedical'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM candidate WHERE id='".$row["emp_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["candidate_name"] = $row1["candidate_name"];
                    }
                }
                
                $sql1 = "SELECT * FROM physician WHERE id='".$row["phisician_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["doctor_name"] = $row1["doctor_name"];
                    }
                }
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveReport") {
        $target_dir = "../../../upload/employee/";

        $uploaded_files = [];
        foreach ($_FILES as $field_name => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $file_name = basename($entry_date . $file['name']);
                $uploaded_files[] = $file_name;
                move_uploaded_file($file['tmp_name'], $target_dir . $file_name);
            }
        }

        // Convert the array of uploaded file names to a JSON string
        $uploaded_files_json = json_encode($uploaded_files);
        
        $input = $_POST;

        // SQL query with a placeholder
          $sql = "UPDATE medicalcheckup SET status='doc_upload', testDate = '$entry_date', tests_document = ? WHERE emp_id = ?";
        $stmt = $conn->prepare($sql);
        
        // Check for errors in preparing the statement
        if (!$stmt) {
            die('Error in preparing the statement: ' . $conn->error);
        }
        
        // Bind parameters
        $stmt->bind_param("si", $uploaded_files_json, $_GET["id"]);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => $stmt->error]);
        }
        
        // Close the statement
        $stmt->close();


        
    }
//     else if ($_GET["type"] == "saveReport") {
        
// $target_dir = "../../../upload/employee/";

// $uploaded_files = [];
// foreach ($_FILES as $field_name => $file) {
//     if ($file['error'] === UPLOAD_ERR_OK) {
//         $file_name = basename($entry_date.$file['name']);
//         $uploaded_files[] = $file_name;
//         move_uploaded_file($file['tmp_name'], $target_dir . $file_name);
//     }
// }

// // Convert the array of uploaded file names to a JSON string
// $uploaded_files_json = json_encode($uploaded_files);


// $input = $_POST;

//  $sql = "update medicalcheckup set tests_document= ? where emp_id='".$_GET["id"]."'";
// $conn->query($sql);
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("s", $uploaded_files_json);

// if ($stmt->execute()) {
//     echo json_encode(["status" => "success"]);
// } else {
//     echo json_encode(["status" => $conn->error]);
// }

// $stmt->close();
// $conn->close();


//     }



    
    else if ($_GET["type"] == "getPremedicalsLog") {
        
        $output = Array();
 
        $sql = "SELECT * FROM medicalcheckup WHERE form_type='premedical' and status='pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM candidate WHERE id='".$row["emp_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                     $row["candidate_name"] = $row1["firstname"] . ' ' . $row1["lastname"];

                    }
                }
                
                $sql1 = "SELECT * FROM physician WHERE id='".$row["phisician_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["doctor_name"] = $row1["doctor_name"];
                    }
                }
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
       else if($_GET["type"] == "get_monthly_schedule") {
        $output = Array();
          $sql = "SELECT a.*,b.candidate_name ,b.tag_no,b.description,b.department,b.location,
                         DATEDIFF(a.due_date, CURDATE()) AS remaining_days

         FROM medical_rep_due  a JOIN candidate b on a.candidate_id= b.id WHERE month(due_date)='".$_GET["month"]."' and year(due_date)='".$_GET["year"]."'
         and a.due_type =   '".$_GET["due_type"]."'  order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 
                $row['checklist'] = json_decode($row['checklist']);
                
                if (isset($row['intimation_data'])) {
                    $row['intimation_data'] = json_decode($row['intimation_data'], true);
                } else {
                    $row['intimation_data'] = [];
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>