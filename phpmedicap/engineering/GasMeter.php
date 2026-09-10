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

if($_GET["type"] == "saveGasMeterRecord") {
   $sql = "INSERT INTO NaturalGasSystem (
                log_date,
                gas_meter_reading,
                gas_pressure_digital,
                gas_pressure_inlet,
                gas_temperature,
                total_gas_consumption,
                remark,entry_by,
                entry_date)
        VALUES('".$input["date"]."',
                '".$input["gas_meter_reading"]."' ,
                '".$input["gas_pressure_digital"]."' ,
                '".$input["gas_pressure_inlet"]."' ,
                '".$input["gas_temperature"]."' ,
                '".$input["total_gas_consumption"]."' ,
                '".$input["remark"]."' ,'".$_GET["emp_id"]."',
                '$entry_date')";
                
   if ($conn->query($sql)) {
       echo "{\"status\":\"success\"}";
   } else {
       echo "{\"status\":\"".$conn->error."\"}";
   }
}


    else if($_GET["type"] == "getPressure1") {
        $output = array();
        $sql="SELECT * FROM pressure_plenum WHERE type='PRESSURE1'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if($_GET["type"] == "getGasMeterRecords") {
        $output = array();
        $sql="SELECT * FROM NaturalGasSystem ";
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
    
    else if($_GET["type"] == "savePressure2") {
        $sql ="INSERT INTO pressure_plenum (type, plant_name ,equipment_code,primary_filter ,hepa_filter,remark,entry_by,entry_date, entry_time)VALUES('PRESSURE2','".$input["plant_name"]."' ,'".$input["equipment_code"]."' ,'".$input["primary_filter"]."','".$input["hepa_filter"]."','".$input["remark"]."' ,'".$_GET["emp_id"]."' ,'$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"] == "getPressure2") {
        $output = array();
        $sql="SELECT * FROM pressure_plenum WHERE type='PRESSURE2' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     
            else if ($_GET["type"] == "downloadGasMeter") {
        $_GET['filename'] = 'Pressure Differance Monitoring Across Filter At PLENUM'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";

    $html.='   <h3 style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center">Natural Gas System</h3>
        <table border="1" cellpadding="5" class="table table-bordered">
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
              <th>Date</th>
                    <th>Gas Meter Reading (M3)</th>
                    <th>Gas Pressure (Digital Meter)</th>
                    <th>Gas Pressure (Inlet) (NMT 130mpa)</th>
                    <th>Gas Temperature</th>
                    <th>Total Gas Consumption (m3)</th>
                    <th>Remark</th>
        </tr>';
        $sql = "SELECT * FROM NaturalGasSystem";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $html.=' <tr>
                    <td>'.$row['log_date'].'</td>
                    <td>'.$row['gas_meter_reading'].'</td>
                    <td>'.$row['gas_pressure_digital'].'</td>
                    <td>'.$row['gas_pressure_inlet'].'</td>
                    <td>'.$row['gas_temperature'].'</td>
                    <td>'.$row['total_gas_consumption'].'</td>
                    <td>'.$row['remark'].'</td>
                </tr>';
                 $i++;
            }
        } 
$html.='</table>';


        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure1.pdf', 'I');
    
    }



            else if ($_GET["type"] == "downloaddieselgenerator") {
        $_GET['filename'] = 'Pressure Differance Monitoring Across Filter At PLENUM'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";

    $html.='   <h3 style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center">POWAR HOUSE</h3>
        <table border="1" cellpadding="5" class="table table-bordered">
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
         
                           <th>Date</th>
                        <th colspan="2">Time Hours</th>
                        <th colspan="2">Running Hours</th>
                        <th>Diesel Top-Up (Ltr.)</th>
                        <th>Temperature (°C)</th>
                        <th>Remarks</th>
                    </tr>
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                         <th></th>
                        <th>From</th>
                        <th>To</th>
                        <th>Reading</th>
                        <th>Cumulative</th>
                        <th></th>
                        <th></th>
                        <th></th>
        </tr>';
        $sql = "SELECT * FROM DieselGenerator";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $html.=' <tr>
                    <td>'.$row['log_date'].'</td>
                    <td>'.$row['time_from'].'</td>
                    <td>'.$row['time_to'].'</td>
                    <td>'.$row['running_hours'].'</td>
                    <td>'.$row['running_cumulative'].'</td>
                    <td>'.$row['diesel_topup'].'</td>
                    <td>'.$row['temperature'].'</td>
                    <td>'.$row['remark'].'</td>
                </tr>';
                 $i++;
            }
        } 
$html.='</table>';


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
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="3" style="width: 10%;">Date</td>
                        <td rowspan="3" style="width: 10%;">Time</td>
                       
                        <td rowspan="3" style="width: 10%;">AHU Id</td>
                        <td style="width: 40%;text-align:center;">Pressure Accross</td>
                        <td rowspan="3" style="width: 10%;">Remark</td>
                        <td rowspan="3" style="width: 20%;">Recorded By</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:20%;">Primary Filter(10u)</td>
                        <td style="width:20%;">HEPA Filter(0.3u)</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:20%;">Limit:4-12 mm of wg</td>
                        <td style="width:20%;">Limit:24-75 mm of wg</td>
                    </tr>
                </thead>';
         $sql="SELECT * FROM pressure_plenum WHERE type='PRESSURE2' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 10%;">'.$row['entry_time'].'</td>
                       
                        <td style="width: 10%;">'.$row['id'].'</td>
                        <td style="width: 20%;">'.$row['primary_filter'].'</td>
                        <td style="width: 20%;">'.$row['hepa_filter'].'</td>
                        <td style="width: 10%;">'.$row['remark'].'</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure1.pdf', 'I');
    
    }
    
} else {
    echo "{\"status\":\"Invalid Token\"}";
}

$conn->close();
?>