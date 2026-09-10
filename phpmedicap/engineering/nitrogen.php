<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
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
    
    
    
      if ($_GET["type"] == "save_replacement") {
          $sql = "INSERT INTO replacment ( plant_id,shedule_date,shedule_id,filter_name,filter_id,replacement_interval,shedulereplc_date,previous_date,next_date,
          maintenance_team,maintenance_note,up_mentance,approvel_status) VALUES ( '".$_GET["plant_id"]."','".$input["shedule_date"]."', '".$input["shedule_id"]."', 
          '".$input["filter_name"]."', '".$input["filter_id"]."', '".$input["replacement_interval"]."', '".$input["shedulereplc_date"]."', '".$input["previous_date"]."', 
          '".$input["next_date"]."', '".$input["maintenance_team"]."', '".$input["maintenance_note"]."', '".$input["up_mentance"]."', '".$input["approvel_status"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
     else if($_GET["type"] == "saveOperation") {
        $sql ="INSERT INTO nitrogen_operation (air_pressure, nitrogen_flow, oxygen_per, nitrogen_pressure,entry_by,entry_date, entry_time) VALUES ('".$input["air_pressure"]."' ,'".$input["nitrogen_flow"]."' ,'".$input["oxygen_per"]."' ,'".$input["nitrogen_pressure"]."','".$_GET["emp_id"]."' ,'$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getOperations") {
        $output = array();
        $sql="SELECT * FROM nitrogen_operation WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "savePressure") {
        $sql ="INSERT INTO nitrogen_pressure (plant_name, filter_id, pressure_before, pressure_after, remark,entry_by,entry_date) VALUES ('".$input["plant_name"]."' ,'".$input["filter_id"]."' ,'".$input["pressure_before"]."' ,'".$input["pressure_after"]."','".$input["remark"]."','".$_GET["emp_id"]."' ,'$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if($_GET["type"] == "saveNitrogenLog") {
        $_POST=$input;
        
$air = $conn->real_escape_string($_POST['air']);
$boge = $conn->real_escape_string($_POST['boge']);
$bogecom = $conn->real_escape_string($_POST['bogecom']);
$bogedryer = $conn->real_escape_string($_POST['bogedryer']);
$cylinder = $conn->real_escape_string($_POST['cylinder']);
$date = $conn->real_escape_string($_POST['date']);
$elgicom = $conn->real_escape_string($_POST['elgicom']);
$elgicontrol = $conn->real_escape_string($_POST['elgicontrol']);
$n2reciver = $conn->real_escape_string($_POST['n2reciver']);
$nitrogenpurity = $conn->real_escape_string($_POST['nitrogenpurity']);
$oil = $conn->real_escape_string($_POST['oil']);
$pressure = $conn->real_escape_string($_POST['pressure']);
$purity = $conn->real_escape_string($_POST['purity']);
$remark = $conn->real_escape_string($_POST['remark']);

$sql = "INSERT INTO nitrogen_system_log
(air,boge,bogecom,bogedryer,cylinder,date,elgicom,elgicontrol,n2reciver,nitrogenpurity,oil,pressure,purity,remark)
VALUES
('$air','$boge','$bogecom','$bogedryer','$cylinder','$date','$elgicom','$elgicontrol','$n2reciver','$nitrogenpurity','$oil','$pressure','$purity','$remark')";

if ($conn->query($sql) === TRUE) {
     echo "{\"status\":\"success\"}";
} else {
    echo "Error: " . $conn->error;
}
    }
    else if($_GET["type"] == "getNitrogenLog") {
        $output = array();
        $sql="SELECT * FROM nitrogen_system_log";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getPressure") {
        $output = array();
        $sql="SELECT * FROM nitrogen_pressure WHERE plant_name LIKE '%".$_GET["plant_name"]."%' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveReplacement") {
        $sql ="INSERT INTO nitrogen_replacement (filter_id, replacement_date, remark,entry_by, check_by,entry_date) VALUES ('".$input["filter_id"]."' ,'".$input["replacement_date"]."','".$input["remark"]."', '".$input["replacement_by"]."','".$_GET["emp_id"]."' ,'$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getReplacements") {
        $output = array();
        $sql="SELECT * FROM nitrogen_replacement WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "downloadOperations") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<h3 style="text-align:center;">The Operation OF Nitrogen Plant</h3>
        <table border="1" cellpadding="3">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 15%;">Date</td>
                    <td style="width: 10%;">Time</td>
                    <td style="width: 15%;">Air Pressure</td>
                    <td style="width: 15%;">Nitrogen Flow(NH3/Hr)</td>
                    <td style="width: 15%;">% of Oxygen</td>
                    <td style="width: 15%;">Nitrogen Pressure</td>
                    <td style="width: 15%;">Reading Taken By</td>
                </tr>
            </thead>';
        $sql="SELECT * FROM nitrogen_operation WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 10%;">'.$row['entry_time'].'</td>
                        <td style="width: 15%;">'.$row['air_pressure'].'</td>
                        <td style="width: 15%;">'.$row['nitrogen_flow'].'</td>
                        <td style="width: 15%;">'.$row['oxygen_per'].'</td>
                        <td style="width: 15%;">'.$row['nitrogen_pressure'].'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Operations.pdf', 'I');
    } else if ($_GET["type"] == "downloadPressure") {
        $_GET['filename'] = 'Pressure'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 15%;">Date</td>
                    <td rowspan="2" style="width: 15%;">Filter Id No</td>
                    <td style="width: 40%; text-align:center;">Pressure in Kg Cm2</td>
                    <td rowspan="2" style="width: 15%;">Remark</td>
                    <td rowspan="2" style="width: 15%;">Checked By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Before Filter</td>
                    <td style="width:20%;">After Filter</td>
                </tr>
            </thead>';
        $sql="SELECT * FROM nitrogen_pressure WHERE plant_name LIKE '%".$_GET["plant_name"]."%' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 15%;">'.$row['filter_id'].'</td>
                        <td style="width: 20%;">'.$row['pressure_before'].'</td>
                        <td style="width: 20%;">'.$row['pressure_after'].'</td>
                        <td style="width: 15%;">'.$row['remark'].'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('pressure.pdf', 'I');
    }
    
} else {
    echo "{\"status\":\"Invalid Token\"}";
}

$conn->close();
?>