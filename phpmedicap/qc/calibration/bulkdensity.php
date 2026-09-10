<?php
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php';
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

    if($_GET["type"] == "saveBulkDensity"){
       $sql="INSERT INTO bulkdensity(bulkmethod,bulkset,bulktime,bulk_stocks,remark,entry_date ,entry_by)VALUES('".$input["bulkmethod"]."','".$input["bulkset"]."', '".$input["bulktime"]."','".$input["bulk_stocks"]."' ,'".$input["remark"]."' ,'$entry_date', '".$_GET["emp_id"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }else if($_GET["type"] == "getBulkDensity"){
        $output = Array();
        $sql="SELECT * FROM bulkdensity WHERE status='pending' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] == "getUSP1BulkDensity"){
        $output = Array();
        $sql="SELECT * FROM bulkdensity WHERE bulkmethod='USP-I' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] == "getUSP2BulkDensity"){
        $output = Array();
        $sql="SELECT * FROM bulkdensity WHERE bulkmethod='USP-II' ";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }if ($_GET["type"] == "BulkDensityCalibration") {
        $_GET['formatno'] = 'Format No:QC077/F/01-01'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
            
        $sql="SELECT b.*,e.firstname FROM bulkdensity b LEFT JOIN employee e ON b.entry_by=e.emp_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $_GET['prepared_by']=$row['firstname'];
            $_GET['prepared_id']=$row['entry_by'];
            $_GET['prepared_date']=date('d-m-Y',strtotime($row['entry_date']));
            $html= "";
            $html.='
            <h2 style="text-align:center"; >Bulk Density Calibration</h2>
            <table style="border:1px solid black" width="100%" cellpadding="6">
                <tr>
                   <td style="border:1px solid black"  width="22%"><b>Subject</b></td>
                   <td style="border:1px solid black"  width="3%">:</td>
                   <td style="border:1px solid black"  width="25%"></td>
                   <td style="border:1px solid black"  width="22%"><b>Date Of Calibration</b></td> 
                   <td style="border:1px solid black"  width="3%">:</td>
                   <td style="border:1px solid black"  width="25%">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                </tr>
                <tr>
                   <td style="border:1px solid black"  width="22%"><b>Instrument</b></td>
                   <td style="border:1px solid black"  width="3%">:</td>
                   <td style="border:1px solid black"  width="25%"></td>
                   <td style="border:1px solid black"  width="22%"><b>Next Calibration Due</b></td> 
                   <td style="border:1px solid black"  width="3%">:</td>
                   <td style="border:1px solid black"  width="25%"></td>
                </tr>
                <tr>
                   <td style="border:1px solid black"   width="100%" colspan="2"><b>Instrument ID</b></td>
                </tr>
            </table>
            <p><b>Method:</b>USP-I</p>
            <table style="border:1px solid black" width="100%" cellpadding="4">
                <thead>
                    <tr>
                       <th style="border:1px solid black"  width="10%"><b>Sr. No.</b></th>
                       <th style="border:1px solid black"  width="10%"><b>Set</b></th> 
                       <th style="border:1px solid black"  width="30%"><b>Std. Time(Std.: 60 Sec.)</b></th>
                       <th style="border:1px solid black"  width="50%"><b>No. of Stokes observed</b></th>
                    </tr>
                </thead>';
                $i=1;
                $sql1="SELECT * FROM bulkdensity WHERE bulkmethod='USP-I'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tbody>
                        <tr>
                            <td style="border:1px solid black"  width="10%">'.$i++.'</td>
                            <td style="border:1px solid black"  width="10%">'.$row1['bulkset'].'</td> 
                            <td style="border:1px solid black"  width="30%">'.$row1['bulktime'].'</td>
                            <td style="border:1px solid black"  width="50%">'.$row1['bulk_stocks'].'</td>
                        </tr>
                    </tbody>';
                    }
                }
                $html.='
                <tr>
                   <th style="border:1px solid black" colspan="3";><b>Mean</b></th>
                   <th style="border:1px solid black";></th>
                </tr>
                <tr>
                   <th style="border:1px solid black" colspan="3";><b>Acceptance criteria</b></th>
                   <th style="border:1px solid black";></th>
                </tr>
            </table>
            <p><b>Method:</b>USP-II</p>
            <table style="border:1px solid black" width="100%" cellpadding="3">
                <thead>
                    <tr>
                       <th style="border:1px solid black"  width="10%"><b>Sr. No.</b></th>
                       <th style="border:1px solid black"  width="10%"><b>Set</b></th> 
                       <th style="border:1px solid black"  width="30%"><b>Std. Time(Std.: 60 Sec.)</b></th>
                       <th style="border:1px solid black"  width="50%"><b>No. of Stokes observed</b></th>
                   </tr>
                </thead>';
                $j=1;
                $sql2="SELECT * FROM bulkdensity WHERE bulkmethod='USP-II'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                    $html.='
                    <tbody>
                        <tr>
                            <td style="border:1px solid black"  width="10%">'.$j++.'</td>
                            <td style="border:1px solid black"  width="10%">'.$row2['bulkset'].'</td> 
                            <td style="border:1px solid black"  width="30%">'.$row2['bulktime'].'</td>
                            <td style="border:1px solid black"  width="50%">'.$row2['bulk_stocks'].'</td>
                        </tr>
                    </tbody>';
                    }
                }
                $html.='
                <tr>
                    <td style="border:1px solid black" colspan="3"><b>Mean</b></td>
                    <td style="border:1px solid black";></td>
                </tr>
                <tr>
                    <td style="border:1px solid black" colspan="3"><b>Acceptance criteria</b></td>
                    <td style="border:1px solid black";></td>
                </tr>
            </table>
            <div></div>
            <table>
                <tr>
                   <td style="text-align:left" width:"100%"><b>Remarks:</b>'.$row['remark'].'</td>
                </tr>
            </table>
            <div></div>
            <table>
                <tr>
                   <td style="text-align:left" width:"50%"><b>Calibrated by/ Date:</b>'.$row['entry_by'].''.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                   <td style="text-align:right" width:"50%"><b>Verified by/ Date:</b></td>
                </tr>
            </table>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('BULK DENSITY CALIBRATION.pdf', 'I');
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
    } else if($_GET["type"] == "saveFilterPressure"){
        $sql ="INSERT INTO aircompressor_pressure(plant_name, filter_id, remark ,pressure_before, pressure_after, entry_by ,entry_date)VALUES('".$input["plant_name"]."' , '".$input["filter_id"]."','".$input["remark"]."' , '".$input["pressure_before"]."' ,'".$input["pressure_after"]."' ,'".$_GET["emp_id"]."' , '$entry_date')";
        if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    } else if($_GET["type"] == "getFilterPressure"){
        $output = Array();
        $sql="SELECT * FROM aircompressor_pressure WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    } else if($_GET["type"] == "getDeptFilterPressure"){
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
     else if($_GET["type"] == "save_monthly"){
        if (!is_array($input) || empty($input)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
        }
        if (!is_array($input)) {
            $input = array();
        }
        $esc = function ($v) use ($conn) {
            return $conn->real_escape_string(trim((string)$v));
        };
        $plantId = $esc(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
        $fullRange = json_encode(isset($input['Full_Range_calibration']) ? $input['Full_Range_calibration'] : array());
        $corner = json_encode(isset($input['Corner_Load_Test']) ? $input['Corner_Load_Test'] : array());
        $repeat = json_encode(isset($input['Repetability_Test']) ? $input['Repetability_Test'] : array());
        $sql = "INSERT INTO monthly_calibration(plant_id, location, equipment_id, make, max_capacity, model, Least_Count, equipment_sr_no, weight_box_id, calibration_date, next_calibration_date, Full_Range_calibration, Corner_Load_Test, Repetability_Test, uncertainity)
                VALUES ('".$plantId."','".$esc(isset($input['location']) ? $input['location'] : '')."','".$esc(isset($input['equipment_id']) ? $input['equipment_id'] : '')."','".$esc(isset($input['make']) ? $input['make'] : '')."','".$esc(isset($input['max_capacity']) ? $input['max_capacity'] : '')."','".$esc(isset($input['model']) ? $input['model'] : '')."','".$esc(isset($input['Least_Count']) ? $input['Least_Count'] : '')."','".$esc(isset($input['equipment_sr_no']) ? $input['equipment_sr_no'] : '')."','".$esc(isset($input['weight_box_id']) ? $input['weight_box_id'] : '')."','".$esc(isset($input['calibration_date']) ? $input['calibration_date'] : '')."','".$esc(isset($input['next_calibration_date']) ? $input['next_calibration_date'] : '')."','".$conn->real_escape_string($fullRange)."','".$conn->real_escape_string($corner)."','".$conn->real_escape_string($repeat)."','".$esc(isset($input['uncertainity']) ? $input['uncertainity'] : '')."')";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'failed', 'message' => $conn->error));
        }
        exit;
    }
    else if($_GET["type"] == "get_monthly_calibration"){
        $output=Array();
         $sql="SELECT * , a.id as a_id FROM  monthly_calibration a left join equipment b on a.equipment_id=b.equipment_code WHERE a.plant_id='".$_GET["plant_id"]."' and b.department='".$_GET["department1"]."'";
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