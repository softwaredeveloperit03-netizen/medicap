<?php 


//   ini_set('display_errors', 1);
// error_reporting(E_ALL);
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "downloadDailyTemperatureLog") {
          $_GET['filename'] = 'CC Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='<h2 style="text-align:center;color:brown">Temperature / Humidity Readings Register</h2>
        <table border="1" cellpadding="5">
        <tr style="text-align:center;background-color:#DDDAD9">
        <td style="width:5%"><b>Sr No.</b></td>
         <td style="width:12%"><b>Date</b></td>
         <td style="width:10%"><b>Time</b></td>
          <td style="width:20%"><b>Department</b></td>
           <td style="width:15%"><b>Section</b></td>
            <td style="width:15%"><b>Temperature</b></td>
             <td style="width:13%"><b>Humidity</b></td>
              <td style="width:10%"><b>Reading By</b></td>
             </tr>';
              $i=1;
             $sql = "SELECT *, DATE(entry_date) as entry_date, TIME(entry_date) as entry_time FROM temperature WHERE user_no='".$_GET["user_no"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               $html.=' <tr >
        <td style="width:5%">'.$i.'</td>
         <td style="width:12%">'.$row['entry_date'].'</td>
         <td style="width:10%">'.$row['entry_time'].'</td>
          <td style="width:20%">'.$row['department'].'</td>
           <td style="width:15%">'.$row['section'].'</td>
            <td style="width:15%">'.$row['temperature'].'</td>
             <td style="width:13%">'.$row['humidity'].'</td>
              <td style="width:10%">'.$row['entry_by'].'</td>
              </tr>';
               $i++;
            }
        }
        $html.=' </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CC Record.pdf', 'I');
    
    
    
    
   }else if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM section WHERE user_no='".$_GET["user_no"]."' AND department='".$row["department_name"]."' AND temp_status='pending'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["sections"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTemperature") {
        $sql = "UPDATE section SET temp_status='inprocess', min_temp='".$input["min_temp"]."', max_temp='".$input["max_temp"]."', min_humidity='".$input["min_humidity"]."', max_humidity='".$input["max_humidity"]."' WHERE department='".$input["department"]."' AND section_name='".$input["section"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingTemperatures") {
        $output = Array();
        $sql = "SELECT * FROM section WHERE user_no='".$_GET["user_no"]."' AND temp_status='inprocess'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateTemperature") {
        $sql = "UPDATE section SET temp_status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTemperaturesLog") {
        $output = Array();
        $sql = "SELECT * FROM section WHERE   department LIKE '%".$_GET["department_name"]."%' AND section_name LIKE '%".$_GET["section"]."%' ORDER BY department, section_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSections") {
        $output = Array();
        $sql = "SELECT * FROM section WHERE user_no='".$_GET["user_no"]."' department='".$_GET["department"]."' AND temp_status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDailyTemperature") {
        $sql = "INSERT INTO temperature (user_no, department, section, temperature, humidity, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["department"]."', '".$input["section"]."', '".$input["temperature"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
   }
    else if ($_GET["type"] == "savemacro") {
        $sql = "INSERT INTO microbiological (plant_id , monitoring_id, date_of_monitoring, location, temperature, pressure_diffrential, sampling_method,sampling_start_time,
        sampling_end_time,total_microbial_count,identified_microorganisms,personal_conducting_monitoring,supervisor_name,observations_comments,reviewing_officer,review_date,supporting_documents) 
     VALUES ('".$_GET["plant_id"]."','".$input["monitoring_id"]."', '".$input["monitoring_date"]."', '".$input["location"]."', '".$input["temperature"]."', '".$input["Pressure_diffrential"]."',
        '".$input["shipping_method"]."', '".$input["shipping_start_time"]."', '".$input["shipping_end_time"]."', '".$input["total_microbial_cout"]."', '".$input["identified_microorganisms"]."', '".$input["p_cunducting_monitoring"]."',
        '".$input["supervisor_name"]."', '".$input["observations"]."', '".$input["reviewing_officer"]."', '".$input["review_date"]."', '".$input["supporting_doc"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    
   }
   else if ($_GET["type"] == "getDailyTemperatureLog") {
        $output = Array();
        $sql = "SELECT *, DATE(entry_date) as entry_date, TIME(entry_date) as entry_time,(select section_name from section where section.section_code=temperature.section limit 1) as sectionName  FROM temperature WHERE user_no='".$_GET["user_no"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>