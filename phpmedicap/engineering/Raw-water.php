<?php

  ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
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
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if($_GET["type"] == "savesafetywork") {
      $sql = "INSERT INTO permit (
                department,
                status,
                location,
                dateFrom,
                timeFrom,
                dateTo,
                timeTo,
                jobDescription,
                jobPerformers,
                jobKnowledgeConfirm,
                intimatedPerson,
                equipmentTools,
                additionalRemarks,

                -- Hot Work
                hot1, hot2, hot3, hot4, hot5, hot6,

                -- Height Work
                height1, height2, height3, height4, height5,

                -- Confined Space
                conf1, conf2, conf3, conf4, conf5,

                -- Electrical Work
                elec1, elec2, elec3, elec4, elec5,

                -- Cold Work
                cold1, cold2, cold3, cold4, cold5, cold6,

                -- PPE
                ppeHandGloves, ppeFaceShield, ppeSafetyGoggles, ppeAprons,
                ppeHelmet, ppeRespirators, ppeFullBodyHarness, ppeElectricalGloves, ppeSafetyShoes,

                -- General Precautions (assuming 5 options)
                precaution_0, precaution_1, precaution_2, precaution_3, precaution_4,

                entry_by, entry_date
            ) VALUES (
                '".$input["department"]."',
                'Pending',
                '".$input["location"]."',
                '".$input["dateFrom"]."',
                '".$input["timeFrom"]."',
                '".$input["dateTo"]."',
                '".$input["timeTo"]."',
                '".$input["jobDescription"]."',
                '".$input["jobPerformers"]."',
                '".$input["jobKnowledgeConfirm"]."',
                '".$input["intimatedPerson"]."',
                '".$input["equipmentTools"]."',
                '".$input["additionalRemarks"]."',

                '".$input["hot1"]."', '".$input["hot2"]."', '".$input["hot3"]."', '".$input["hot4"]."', '".$input["hot5"]."', '".$input["hot6"]."',

                '".$input["height1"]."', '".$input["height2"]."', '".$input["height3"]."', '".$input["height4"]."', '".$input["height5"]."',

                '".$input["conf1"]."', '".$input["conf2"]."', '".$input["conf3"]."', '".$input["conf4"]."', '".$input["conf5"]."',

                '".$input["elec1"]."', '".$input["elec2"]."', '".$input["elec3"]."', '".$input["elec4"]."', '".$input["elec5"]."',

                '".$input["cold1"]."', '".$input["cold2"]."', '".$input["cold3"]."', '".$input["cold4"]."', '".$input["cold5"]."', '".$input["cold6"]."',

                '".$input["ppeHandGloves"]."', '".$input["ppeFaceShield"]."', '".$input["ppeSafetyGoggles"]."', '".$input["ppeAprons"]."',
                '".$input["ppeHelmet"]."', '".$input["ppeRespirators"]."', '".$input["ppeFullBodyHarness"]."', '".$input["ppeElectricalGloves"]."', '".$input["ppeSafetyShoes"]."',

                '".$input["precaution_0"]."', '".$input["precaution_1"]."', '".$input["precaution_2"]."', '".$input["precaution_3"]."', '".$input["precaution_4"]."',

                '".$_GET["emp_id"]."',
                '$entry_date'
            )";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}
    
    else if($_GET["type"] == "getSafeWork") {
        $output = array();
        $sql="SELECT * FROM permit  where status='Pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      else if($_GET["type"] == "getResponse") {
        $output = array();
        $sql="SELECT * FROM rawWater  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
// submitOverhead40Form
// submitOverhead25Form
// submitDmUGForm
// submitDmRawForm
// submitRaw70Form
else if ($_GET["type"] == "submitForm") {
    $input = json_decode(file_get_contents('php://input'), true);
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");

    $sql = "INSERT INTO rawWater (
                entry_by, entry_date, entry_time ,done_by,Tools,date,agent
            ) VALUES (
            
                '" . $_GET["emp_id"] . "', '$entry_date', '$entry_time','" . $input["done_by"] . "', '" . $input["Tools"] . "',
                '" . $input["date"] . "', '" . $input["agent"] . "'
            )";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"" . $conn->error . "\"}";
    }
}

    else if($_GET['type'] == 'submitRaw70Form'){ 
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");
    $sql = "UPDATE `rawWater` SET 70KLDate = '".$input['70date']."' ,70KLStartTime = '".$input['70start']."' ,
    70KLStopTime = '".$input['70stop']."' ,70KLStatus = 'Pending', status70='pending',
    70KLTo = '$entry_date' ,70KLBy='" . $_GET["emp_id"] . "' where id =  '".$_GET['id']."'"; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
       else if($_GET['type'] == 'submitDmRawForm'){
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");
    $sql = "UPDATE `rawWater` SET Dmdate = '".$input['Dmdate']."' ,DmStartTime = '".$input['Dmstart']."' ,
    DmStopTime = '".$input['Dmstop']."' ,DmStatus = 'Pending', statusDm='pending',
    DmTo = '$entry_date' ,DmBy='" . $_GET["emp_id"] . "'where id =  '".$_GET['id']."'";
    
    if($conn->query($sql)) {
    echo "{\"status\":\"success\"}";
    } else {
    echo "{\"status\":\"".$conn->error."\"}";
    }
    }
    
    
    else if($_GET['type'] == 'submitDmUGForm'){
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");
    $sql = "UPDATE `rawWater` SET UGdate = '".$input['UGdate']."' ,UGStartTime = '".$input['UGstart']."' ,
    UGStopTime = '".$input['UGstop']."' ,UGStatus = 'Pending', statusUG='pending',
    UGTo = '$entry_date' ,UGBy='" . $_GET["emp_id"] . "' where id =  '".$_GET['id']."'";
    
    if($conn->query($sql)) {
    echo "{\"status\":\"success\"}";
    } else {
    echo "{\"status\":\"".$conn->error."\"}";
    }
    }
    
    
    else if($_GET['type'] == 'submitOverhead25Form'){
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");
    $sql = "UPDATE `rawWater` SET 25date = '".$input['25date']."' ,25StartTime = '".$input['25start']."' ,
    25StopTime = '".$input['25stop']."' , status25KL='pending',
    25To = '$entry_date' ,25By='" . $_GET["emp_id"] . "' where id =  '".$_GET['id']."'";
    
    if($conn->query($sql)) {
    echo "{\"status\":\"success\"}";
    } else {
    echo "{\"status\":\"".$conn->error."\"}";
    }
    }
    
    
    else if($_GET['type'] == 'submitOverhead40Form'){
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");
    $sql = "UPDATE `rawWater` SET 40date = '".$input['40date']."' ,40StartTime = '".$input['40start']."' ,
    40StopTime = '".$input['40stop']."' , Status40 ='Pending',  
    40To = '$entry_date' ,40By='" . $_GET["emp_id"] . "' where id =  '".$_GET['id']."'";
    
    if($conn->query($sql)) {
    echo "{\"status\":\"success\"}";
    } else {
    echo "{\"status\":\"".$conn->error."\"}";
    }
    }


    else if ($_GET["type"] == "downloadPressure2") {
        $_GET['filename'] = 'Pressure Differance Monitoring Across Filter At PLENUM'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
   
    $html = "";
 $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    $html .= '<h2 style="text-align:center;">Permit to Work Checklist</h2>';

    $html .= '<table border="1" cellpadding="5">
        <tr>
            <td><b>Date From</b></td>
            <td>' . $row['dateFrom'] . '</td>
            <td><b>Date To</b></td>
            <td>' . $row['dateTo'] . '</td>
        </tr>
        <tr>
            <td><b>Time From</b></td>
            <td>' . $row['timeFrom'] . '</td>
            <td><b>Time To</b></td>
            <td>' . $row['timeTo'] . '</td>
        </tr>
        <tr>
            <td><b>Department</b></td>
            <td>' . $row['department'] . '</td>
            <td><b>Location / Equipment</b></td>
            <td>' . $row['location'] . '</td>
        </tr>
        <tr>
            <td><b>Description of Job</b></td>
            <td>' . $row['jobDescription'] . '</td>
            <td><b>Job Performer(s)</b></td>
            <td>' . $row['jobPerformers'] . '</td>
        </tr>
        <tr>
            <td><b>Equipment / Tools</b></td>
            <td>' . $row['equipmentTools'] . '</td>
            <td><b>Intimated Person</b></td>
            <td>' . $row['intimatedPerson'] . '</td>
        </tr>
    </table>';

    $html .= '<h4>Safety Precautions</h4>
    <table border="1" cellpadding="5">
        <tr><th>Sr. No.</th><th>Precaution</th><th>Yes / No</th></tr>
        <tr><td>1</td><td>Work area inspected properly for safe work</td><td>' . $row['precaution_0'] . '</td></tr>
        <tr><td>2</td><td>Equipment & Tools checked properly</td><td>' . $row['precaution_1'] . '</td></tr>
        <tr><td>3</td><td>PPE like Goggle, Gloves, etc.</td><td>' . $row['precaution_2'] . '</td></tr>
        <tr><td>4</td><td>Barricading Tapes, Tags, Cautionary Notices</td><td>' . $row['precaution_3'] . '</td></tr>
        <tr><td>5</td><td>System de-energized (Electrical, Pneumatic, etc.)</td><td>' . $row['precaution_4'] . '</td></tr>
    </table>';

    $html .= '<h4>Closure of Permit</h4>
    <table border="1" cellpadding="5">
        <tr><th>Sr. No.</th><th>Post-Work Checks</th><th>Yes / No</th></tr>
        <tr><td>1</td><td>Job performed satisfactorily</td><td>' . $row['closureCheck_0'] . '</td></tr>
        <tr><td>2</td><td>Housekeeping done</td><td>' . $row['closureCheck_1'] . '</td></tr>
        <tr><td>3</td><td>Safe work conditions restored</td><td>' . $row['closureCheck_2'] . '</td></tr>
    </table>';

    $html .= '<h4>Additional Remarks</h4>
    <p>' . (!empty($row['additionalRemarks']) ? $row['additionalRemarks'] : 'N/A') . '</p>';

    $html .= '<h4>Confirmation</h4>
    <p>' . ($row['jobKnowledgeConfirm'] ? 'Yes, user has been informed about the job and safety guidelines.' : 'No confirmation provided.') . '</p>';

    
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure1.pdf', 'I');
    
    }
        }}
} else {
    echo "{\"status\":\"Invalid Token\"}";
}

$conn->close();
?>