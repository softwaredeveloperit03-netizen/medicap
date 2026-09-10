<?php



ini_set('display_errors', 1);
error_reporting(E_ALL);





require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

function temp_department($conn) {
    $dept = trim((string)($_GET["department"] ?? "Store"));
    if ($dept === "") {
        $dept = "Store";
    }
    return $conn->real_escape_string($dept);
}

function temp_department_raw() {
    $dept = trim((string)($_GET["department"] ?? "Store"));
    return $dept !== "" ? $dept : "Store";
}

function temp_master_department($tempDept) {
    $map = array(
        "Store" => "Material Management",
        "Warehouse" => "Material Management",
        "Production" => "Production",
        "QC" => "Quality Control",
    );
    $key = trim((string)$tempDept);
    return isset($map[$key]) ? $map[$key] : $key;
}

function temp_ensure_master_section($conn, $tempDept, $areaName, $empId, $plantId) {
    $areaName = trim((string)$areaName);
    if ($areaName === "") {
        return;
    }
    $masterDept = $conn->real_escape_string(temp_master_department($tempDept));
    $sectionName = $conn->real_escape_string($areaName);
    $roomName = $sectionName;
    $sectionNumber = $conn->real_escape_string("-");
    $floor = "Ground";
    $plantEsc = $conn->real_escape_string((string)$plantId);
    $empEsc = $conn->real_escape_string((string)$empId);
    $entryDate = date("Y-m-d H:i:s");

    $checkSql = "SELECT id FROM section WHERE department='".$masterDept."' AND section_name='".$sectionName."'";
    if ($plantEsc !== "") {
        $checkSql .= " AND (plant_id='".$plantEsc."' OR plant_id IS NULL OR plant_id='')";
    }
    $checkSql .= " LIMIT 1";
    $check = $conn->query($checkSql);
    if ($check && $check->num_rows > 0) {
        return;
    }

    $insertSql = "INSERT INTO section (department, floor, section_name, room_name, section_number, entry_by, entry_date, plant_id) VALUES
        ('".$masterDept."', '".$floor."', '".$sectionName."', '".$roomName."', '".$sectionNumber."', '".$empEsc."', '".$entryDate."', '".$plantEsc."')";
    @$conn->query($insertSql);
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
    
 
    if ($_GET["type"] == "saveTemperature") {
        $dept = temp_department($conn);
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = array();
        }
        $entry_date = date('Y-m-d H:i:s');
        $section = $conn->real_escape_string((string)($input["section"] ?? ''));
        $thermo = $conn->real_escape_string((string)($input["thermo_hygrometer"] ?? ''));

        $sql = "INSERT INTO temperature (department, section, thermo_hygrometer, entry_by, entry_date, entry_time) VALUES 
        ('".$dept."', '".$section."', '".$thermo."', '".$_GET["emp_id"]."', '$entry_date', '$entry_date' )";

         if ($conn->query($sql)) {
             
            $temp_id = $conn->insert_id;
        
            $json_obj = json_encode($input["fromlevel1"]);
            $array = json_decode($json_obj, true);
        
            foreach ($array as $values) {
                 $sql1 = "INSERT INTO `lableform`(`temperature_id`, `minimum`, `maximum`, `current`, `tempType`) VALUES ('".$temp_id."', '".$values["minimum"]."', '".$values["maximum"]."',  '".$values["current"]."',  'Temperature')";
                 $conn->query($sql1);
            }
         
            $json_obj = json_encode($input["fromlevel"]);
            $array = json_decode($json_obj, true);
        
             foreach ($array as $values) {
                $sql1 = "INSERT INTO `lableform`(`temperature_id`, `minimum`, `maximum`, `current`, `tempType`) VALUES ('".$temp_id."', '".$values["minimum"]."', '".$values["maximum"]."',  '".$values["current"]."',  'Relative')";
                $conn->query($sql1);
            }

            temp_ensure_master_section(
                $conn,
                temp_department_raw(),
                $input["section"] ?? "",
                $_GET["emp_id"] ?? "",
                $_GET["plant_id"] ?? ""
            );
        
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"Error: ".$conn->error."\"}";
        }
        
    }

    else if ($_GET["type"] == "getTemperature") {
        $dept = temp_department($conn);
        $output = array();
        $sql =  " SELECT  * FROM temperature WHERE department='".$dept."' Order By id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 =  " SELECT  * FROM lableform where temperature_id = '".$row['id']."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row['labelData'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getThermohygrometers") {
        $dept = temp_department($conn);
        $output = array();
          $sql = "SELECT * FROM equipment WHERE department='".$dept."' AND equipment_type = 'Thermo Hygrometer' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadTemperature") {
        $dept = temp_department($conn);
        $_GET['filename'] = 'Temperature'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:cenetr">Temperature</h2>
        <table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Date</td>
                        <td style="width:10%;">Time</td>
                        <td style="width:10%;">Area</td>
                        <td style="width:15%;">ThermoHygrometer</td>
                        <td style="width:15%;">Temperature</td>
                        <td style="width:15%;">Relative Humidity</td>
                        <td style="width:10%;">Done By</td>
                        <td style="width:10%;">Checked By</td>
                    </tr>';
        $sql = "SELECT * FROM temperature WHERE department='".$dept."' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                        <td style="width:15%;">'.$row['entry_date'].'</td>
                        <td style="width:10%;">'.$row['entry_time'].'</td>
                        <td style="width:10%;">'.$row['section'].'</td>
                        <td style="width:15%;">'.$row['thermo_hygrometer'].'</td>
                        <td style="width:15%;">'.$row['temperature'].'</td>
                        <td style="width:15%;">'.$row['humidity'].'</td>
                        <td style="width:10%;">'.$row['entry_by'].'</td>
                        <td style="width:10%;">'.$row['checked_by'].'</td>
                    </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Temperature.pdf', 'I');
    }
    
    else if ($_GET["type"] == "downloadTemperature1") {
        $_GET['filename'] = 'Temperature'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

      
        //       $sql =  " SELECT temperature.*, lableform.minimum1, lableform.current1, lableform.maximum1, lableform1.maximum, 
        //   lableform1.minimum, lableform1.current FROM temperature LEFT JOIN lableform ON temperature.id = lableform.temperature_id 
        //   LEFT JOIN lableform1 ON temperature.id = lableform1.temperature_id And '".$_GET["temperature_id"]."'";
        $sql = "SELECT temperature.*, 
               lableform.minimum1, lableform.current1, lableform.maximum1, 
               lableform1.maximum, lableform1.minimum, lableform1.current 
        FROM temperature 
        LEFT JOIN lableform ON temperature.id = lableform.temperature_id 
        LEFT JOIN lableform1 ON temperature.id = lableform1.temperature_id 
        WHERE temperature.id = :temperature_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html='
 <table border="1">
  
 <tr>
    <td style="line-height:20px;width: 540px;text-align:center;">Warehouse</td>
</tr>
<tr>
    <td style="line-height:20px;width: 540px;text-align:center;">Daily Temperature & Humidity Monitoring Record</td>
</tr>
<tr>
     <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;"> Format No.: WH/012/FM-001/00</td>
     <td style="line-height:20px;width: 270px;text-align:left;"> Change Control No.: NA</td>
 </tr>
 <tr>
     <td style="line-height:20px;width: 270px;border-bottom:none;text-align:left;"> Effective Date:</td>
     <td style="line-height:20px;width: 270px;text-align:left;"> Review Date:</td>
 </tr>
 <tr>
    <td style="line-height:20px;width: 540px;text-align:left;"> Reference SOP No. ALL/QC/051/00 </td>
</tr>
</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Department </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Month </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  </td>
</tr>
<tr>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Location </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  Frequency</td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Once in a day </td>
</tr>
<tr>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Thermo/Hygrometer ID No.: </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">'.$row['thermo_hygrometer'].' </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Calibration Due on </td>
    <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  </td>
</tr>
</table>
<div></div>

<table>
<tr>
<td style="line-height:20px;width: 540px;border-bottom:none;text-align:left;"> Prefer Time:10 hrs.to 11 hrs. </td>
</tr>
</table>
<div></div>


<table border="1">
<tr>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"rowspan="2">Date</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"rowspan="2">Time </td>
    <td style="line-height:20px;width: 162px;border-bottom:none;text-align:center;"colspan="3">Temperature(for Information)</td>
    <td style="line-height:20px;width: 162px;border-bottom:none;text-align:center;"colspan="3"> Relative Humidity (for Information)</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"rowspan="2"> Recorded By </td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;"rowspan="2"> Remark</td>
</tr>
<tr>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">Minimum</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">Maximum</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">Current</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">Minimum</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">Maximum</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">Current</td>
</tr>
<tr>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['entry_date'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['entry_time'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['maximum'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['minimum'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['current'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['maximum1'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['minimum1'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row['current1'].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row[''].'</td>
    <td style="line-height:20px;width: 54px;border-bottom:none;text-align:center;">'.$row[''].'</td>
</tr>
</table>
<div></div>

<table>
<tr>
<td style="line-height:20px;width: 540px;border-bottom:none;text-align:left;"> Reviewed by Warehouse(Sign/Date) </td>
</tr>
</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Sign/Date</td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Sign/Date </td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Sign/Date </td>
</tr>
<tr>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Prepared By WH</td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Checked By WH</td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Approved By QA</td>
</tr>
</table>
';
            }
        }
        
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Temperature.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
