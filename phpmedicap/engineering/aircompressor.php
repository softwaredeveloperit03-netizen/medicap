<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

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

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

    if($_GET["type"] == "saveOperation"){
       $sql="INSERT INTO aircompressor_operation(AC02,AC05 ,AC06,air_pressure,operator,entry_time, entry_date ,entry_by)VALUES('".$input["AC02"]."','".$input["AC05"]."', '".$input["AC06"]."','".$input["air_pressure"]."' ,'".$input["operator"]."' ,'".$input["entry_time"]."','".$input["entry_date"]."', '".$_GET["emp_id"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }else if($_GET["type"]=="getOpeartions"){
        $output = Array();
        $sql = "SELECT a.*, e.firstname FROM aircompressor_operation a LEFT JOIN employee e ON a.operator=e.emp_id WHERE a.status='APPROVE' AND MONTH(a.entry_date)= MONTH('".$_GET["month"]."-01') AND YEAR(a.entry_date)= YEAR('".$_GET["month"]."-01')";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    else if($_GET["type"] == "saveFilterPressure"){
        $sql ="INSERT INTO aircompressor_pressure(plant_name, filter_id, remark ,pressure_before, pressure_after, entry_by ,entry_date)VALUES('".$input["plant_name"]."' , '".$input["filter_id"]."','".$input["remark"]."' , '".$input["pressure_before"]."' ,'".$input["pressure_after"]."' ,'".$_GET["emp_id"]."' , '$entry_date')";
        if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    } 
    else if($_GET["type"] == "saveairCompressorMeha"){
        $sql ="Insert into  airCompressor (date,oilLevel,DischargeTemp,Dischargepressure,Voltage,MotorLoadR,MotorLoadY,MotorLoadB,
                CompressorRun,CompressorLoad,UnloadTime,entry_date,entry_by)Values('".$input['date']."','".$input['oilLevel']."',
                '".$input['DischargeTemp']."','".$input['Dischargepressure']."','".$input['Voltage']."',
                '".$input['MotorLoadR']."','".$input['MotorLoadY']."','".$input['MotorLoadB']."',
                '".$input['CompressorRun']."','".$input['CompressorLoad']."','".$input['UnloadTime']."','".$_GET['emp_id']."','$entry_date')";
        if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    } 
    else if($_GET["type"] == "getFilterPressure"){
        $output = Array();
        $sql="SELECT * FROM aircompressor_pressure WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
       else  if($_GET["type"] == "getairCompressorMehaLog"){
        $output = Array();
        $sql = "SELECT * FROM airCompressor";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
           else if ($_GET["type"] == "UpdateaircompressorMehaStatus") {
        
        $sql = "UPDATE airCompressor SET status='".$input["status"]."', approve_by='".$_GET["emp_id"]."' , approve_date='$entry_date'   WHERE id = '".$input["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if($_GET["type"] == "getairCompressorMEHA"){
        $output = Array();
        $sql="SELECT * FROM airCompressor WHERE status='".$_GET['status']."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getDeptFilterPressure"){
        $output = Array();
        $sql="SELECT * FROM aircompressor_pressure WHERE plant_name='".$_GET["plant_name"]."' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    } else if($_GET["type"] == "saveFilterReplacement"){
        $sql ="INSERT INTO aircompressor_filterreplce(filter_id, remark ,next_date , entry_by ,entry_date)VALUES('".$input["filter_id"]."','".$input["remark"]."' , '".$input["next_date"]."','".$_GET["emp_id"]."' , '$entry_date')";
        if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    } else if($_GET["type"] == "getFilterReplacement"){
        $output=Array();
        $sql="SELECT * FROM aircompressor_filterreplce WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
          $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    
    } 
    else if ($_GET["type"] == "downloadOpeartions") {
        $_GET['filename'] = ' Operation of Air Compressor'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 7%;">Date.</td>
                        <td rowspan="2" style="width: 10%;">Time</td>
                        <td rowspan="2" style="width: 15%;">Compressor Run Hour</td>
                        <td rowspan="2" style="width: 20%;">Receiver Air presssure in Kg/cm2(4 to 7 Kg/cm2)</td>
                        <td rowspan="2" style="width: 10%;">Name of Operator</td>
                        <td rowspan="2" style="width: 5%;">Action</td>
                        <td  style="width: 11%; text-align:center;">AC-02</td>
                        <td  style="width: 11%; text-align:center;">AC-05</td>
                        <td  style="width: 11%; text-align:center;">AC-06</td>
                       
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:6%;">Reading</td>
                        <td style="width:5%;">Diff</td>
                        <td style="width:6%;">Reading</td>
                        <td style="width:5%;">Diff</td>
                        <td style="width:6%;">Reading</td>
                        <td style="width:5%;">Diff</td>
                    </tr>
                </thead>
               ';
            $sql = "SELECT a.*, e.firstname FROM aircompressor_operation a LEFT JOIN employee e ON a.operator=e.emp_id WHERE a.status='APPROVE' AND MONTH(a.entry_date)= MONTH('".$_GET["month"]."-01') AND YEAR(a.entry_date)= YEAR('".$_GET["month"]."-01')";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                $html.='<tr>
                        <td style="width: 7%;">'.$row['entry_date'].'</td>
                        <td style="width: 10%;">'.$row['entry_time'].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row['air_pressure'].'</td>
                        <td style="width: 10%;">'.$row['entry_by'].'</td>
                        <td style="width: 5%;">'.$row[''].'</td>
                        <td style="width: 6%;">'.$row['AC02'].'</td>
                        <td style="width: 5%;">'.$row['AC02_diff'].'</td>
                        <td style="width: 6%;">'.$row['AC05'].'</td>
                        <td style="width: 5%;">'.$row['AC05_diff'].'</td>
                        <td style="width: 6%;">'.$row['AC06'].'</td>
                        <td style="width: 5%;">'.$row['AC06_diff'].'</td>
                    
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure Monitoring.pdf', 'I');
        }
        else if ($_GET["type"] == "downloadFilterPressure") {
        $_GET['filename'] = 'Pressure Monitoring';$_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td rowspan="2" style="width: 20%;">Date.</td>
                        <td rowspan="2" style="width: 10%;">Filter Id No.	</td>

                        <td  style="width: 30%; text-align:center;">Pressure In Kg/Cm2</td>
                        <td rowspan="2" style="width: 20%;">Entry By</td>
                        <td rowspan="2" style="width: 20%;">Remark</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Before Filter</td>
                        <td style="width:15%;">After Filter</td>
                    </tr>
                </thead>
               ';
           $sql="SELECT * FROM aircompressor_pressure WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                     
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                       
                        <td style="width: 10%;">'.$row['filter_id'].'</td>
                        <td style="width: 15%;">'.$row['pressure_before'].'</td>
                        <td style="width: 15%;">'.$row['pressure_after'].'</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.$row['remark'].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Pressure Monitoring.pdf', 'I');
    }else if ($_GET["type"] == "downloadFilterReplacement") {
        $_GET['filename'] = 'Filter Replacement Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                     
                        <td style="width: 15%;">Date</td>
                        <td style="width: 15%;">Filter Id No.</td>
                        <td style="width: 25%;">Next Replacement Due Date</td>
                        <td style="width: 15%;">Replaced By</td>
                        <td style="width: 15%;">Checked By</td>
                        <td style="width: 15%;">Remark</td>
        
                    </tr>
                </thead>';
            $sql="SELECT * FROM aircompressor_filterreplce WHERE status='pending' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                       
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 15%;">'.$row['filter_id'].'</td>
                        <td style="width: 25%;">'.date('d-m-Y',strtotime($row['next_date'])).'</td>
                        <td style="width: 15%;">'.$row['entry_by'].'</td>
                        <td style="width: 15%;">'.$row['check_by'].'</td>
                        <td style="width: 15%;">'.$row['remark'].'</td>
                    </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Filter Replacement Record.pdf', 'I');
    }
    
}

$conn->close();
?>