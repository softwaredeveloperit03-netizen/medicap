<?php

//   ini_set('display_errors', 1);
// error_reporting(E_ALL);

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
     else if($_GET["type"] == "getSanitizationOf") {
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
     else if($_GET["type"] == "getSafeWorkLog") {
        $output = array();
        $sql="SELECT * FROM permit where status='Approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if($_GET['type'] == 'ApprvSafetywork'){ 
        
    //       ini_set('display_errors', 1);
    // error_reporting(E_ALL); 
             
            $sql = "UPDATE `permit`  SET
            status='Approve',
            closureCheck_0='".$input["closureCheck_0"]."'
            , closureCheck_1='".$input["closureCheck_1"]."'
            , closureCheck_2='".$input["closureCheck_2"]."'
            where id = '".$_GET['id']."' "; 
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"] == "getPowarHouse") {
        $output = array();
        $sql="SELECT * FROM PowarHouse ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if($_GET["type"] == "getAhu") {
        $output = array();
        $sql = "SELECT equipment_name FROM equipment WHERE equipment_name LIKE '%AHU%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if($_GET["type"] == "getEquip") {
        $output = array();
        $sql = "SELECT * FROM equipment where  plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if($_GET["type"] == "getEquipEngineering") {
        $output = array();
        $sql = "SELECT * FROM equipment where department = 'Engineering' AND plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if($_GET["type"] == "getEmp") {
        $output = array();
        $sql = "SELECT * FROM employee where department = 'Engineering' AND plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if($_GET["type"] == "getLocation") {
        $output = array();
        $sql = "SELECT * FROM section";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    // else if($_GET["type"] == "savePressure2") {
    //  echo   $sql ="INSERT INTO pressure_plenum (type, plant_name ,equipment_code,primary_filter ,hepa_filter,remark,entry_by,entry_date, entry_time)VALUES('PRESSURE2','".$input["plant_name"]."' ,'".$input["equipment_code"]."' ,'".$input["primary_filter"]."','".$input["hepa_filter"]."','".$input["remark"]."' ,'".$_GET["emp_id"]."' ,'$entry_date', '$entry_time')";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }
  else if ($_GET["type"] == "getPressure2") {
    $sql = "SELECT * FROM ahu_pressure_readings ORDER BY id DESC";
    $result = $conn->query($sql);

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode($rows);
}
//   else if ($_GET["type"] == "getPressure2") {
//     $output = array();
//     $sql = "SELECT * FROM ahu_pressure_readings " ;
    
//     $result = $conn->query($sql);
//     if ($result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $output[] = $row;
//         }
//     }
//     echo json_encode($output);
// }

    else if ($_GET["type"] == "savePressure1") {
    $input = json_decode(file_get_contents('php://input'), true);
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");

    $sql = "INSERT INTO ahu_cleaning (
                ahu_no, date_Ahu, location, cleaning_from, cleaning_to, 
                cleaned_by, filter_status, entry_by, entry_date, entry_time
            ) VALUES (
                '" . $input["ahu_no"] . "', '" . $input["date_Ahu"] . "', '" . $input["location"] . "',
                '" . $input["cleaning_from"] . "', '" . $input["cleaning_to"] . "',
                '" . $input["cleaned_by"] . "', '" . $input["filter_status"] . "',
                '" . $_GET["emp_id"] . "', '$entry_date', '$entry_time'
            )";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"" . $conn->error . "\"}";
    }
}

     
     else if ($_GET["type"] == "getPressure1") {
    $output = array();
    $sql = "SELECT * FROM ahu_cleaning " ;
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "savePressure2") {
    $input = json_decode(file_get_contents('php://input'), true);
    $entry_date = date("Y-m-d");
    $entry_time = date("H:i:s");

    $sql = "INSERT INTO ahu_pressure_readings (
                date, offBy, offTime, onBy, onTime,
                supplyPressure, returnPressure, cleanedBy, cleaningAgent,
                entry_by, entry_date, entry_time
            ) VALUES (
                '" . $input["date"] . "', '" . $input["offBy"] . "', '" . $input["offTime"] . "',
                '" . $input["onBy"] . "', '" . $input["onTime"] . "',
                '" . $input["supplyPressure"] . "', '" . $input["returnPressure"] . "',
                '" . $input["cleanedBy"] . "', '" . $input["cleaningAgent"] . "',
                '" . $_GET["emp_id"] . "', '$entry_date', '$entry_time'
            )";

    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"" . $conn->error . "\"}";
    }
}


            else if ($_GET["type"] == "downloadPDF") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
            $sql="SELECT * FROM permit where status='Approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
       $html = "";

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
}}
    
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure1.pdf', 'I');
    
    }



    else if ($_GET["type"] == "downloadPressure12") {
        $_GET['filename'] = 'Pressure Differance Monitoring Across Filter At PLENUM'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="3" style="width: 10%;">Date</td>
                        <td rowspan="3" style="width: 9%;">Time</td>
                       
                        <td rowspan="3" style="width: 9%;">AHU Id</td>
                        <td style="width: 45%;text-align:center;">Pressure Accross</td>
                        <td rowspan="3" style="width: 9%;">Remark</td>
                        <td rowspan="3" style="width: 19%;">Recorded By</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Primary Filter(10u)</td>
                        <td style="width:15%;">Secondary Filter(5u)</td>
                        <td style="width:15%;">HEPA Filter(0.3u)</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Limit:4-12 mm of wg</td>
                        <td style="width:15%;">Limit:8-18 mm of wg</td>
                        <td style="width:15%;">Limit:24-75 mm of wg</td>
                    </tr>
                </thead>';
        $sql="SELECT * FROM pressure_plenum WHERE type='PRESSURE1'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 9%;">'.$row['entry_time'].'</td>
                       
                        <td style="width: 9%;">'.$row['id'].'</td>
                        <td style="width: 15%;">'.$row['primary_filter'].'</td>
                        <td style="width: 15%;">'.$row['secondary_filter'].'</td>
                        <td style="width: 15%;">'.$row['hepa_filter'].'</td>
                        <td style="width: 09%;">'.$row['remark'].'</td>
                        <td style="width: 19%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure1.pdf', 'I');
    
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