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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
      $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
      $string = explode("$",$string);
      $_GET["emp_id"] = $string[0];
      $_GET["department"] = $string[1];
      break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getDailyEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='Store' AND equipment_name LIKE '%Balance%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["weights"] = json_decode($row["weights"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM calibration WHERE equipment_code='".$row["equipment_code"]."' AND entry_date=CURDATE() AND status !='REVERT'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $row1 = array();
                    $row1["entry_date"] = date("Y-m-d", $timestamp);
                    $row1["status"] = 'PENDING';
                    $output1[] = $row1;
                }
                
                $sql1 = "SELECT c.*, e.firstname as check_by, e1.firstname as entry_by FROM calibration c LEFT JOIN employee e ON c.check_by=e.emp_id LEFT JOIN employee e1 ON c.entry_by=e1.emp_id WHERE c.equipment_code='".$row["equipment_code"]."' AND c.status !='REVERT' AND c.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weights"] = json_decode($row1["weights"]);
                        $output1[] = $row1;
                    }
                }
                $row["records"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCalibrationLog") {
        $output = array();
        $sql = "SELECT * FROM calibration WHERE equipment_code='".$_GET["equipment_code"]."' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDailyCalibration") {
        $sql = "INSERT INTO calibration (frequency,equipment_code, weights, calibration_status, remark, status, entry_by, entry_date, check_by) VALUES ('Daily', '".$input["equipment_code"]."', '".json_encode($input["weights"])."', '".$input["status1"]."', '".$input["remark"]."', 'approve', '".$input["done_by"]."', '$entry_date', '".$_GET["emp_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "checkCalibration") {
        $sql = "UPDATE calibration SET status='CHECKED', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            if ($_GET["calibration_status"] == "NOT OK") {
                $sql = "INSERT INTO ooc (equipment_code, initiate_by, initiate_date, calibration_date) VALUES ('".$_GET["equipment_code"]."', '".$_GET["emp_id"]."', '$entry_date', '$entry_date')";
                $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "revertCalibration") {
        $sql = "UPDATE calibration SET status='REVERT', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingOOC") {
        $output = array();
        $sql = "SELECT o.*, e.equipment_name, e.make, e.location, e.department, e.model, e.serial_no AS code_no, e.stage
                FROM ooc o
                LEFT JOIN equipment e ON o.equipment_code = e.equipment_code
                WHERE o.status='PENDING'
                ORDER BY o.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveOOC") {
        $esc = function ($value) use ($conn) {
            return $conn->real_escape_string((string)($value ?? ''));
        };
        $id = $esc($input['id'] ?? '');
        $equipmentCode = $esc($input['equipment_code'] ?? '');
        $lastCalibrationDate = $esc($input['last_calibration_date'] ?? '');
        $measuredParameter = $esc($input['measured_parameter'] ?? '');
        $implication = $esc($input['implication'] ?? '');
        $calibrationParameter = $esc(json_encode($input['calibration_parameter'] ?? []));
        $isNew = !empty($input['is_new']);
        $error = '';

        if ($isNew || $id === '') {
            if ($equipmentCode === '') {
                $error = 'Equipment is required';
            } else {
                $sql = "INSERT INTO ooc (equipment_code, initiate_by, initiate_date, calibration_date, status)
                        VALUES ('".$equipmentCode."', '".$esc($_GET['emp_id'])."', '$entry_date', '".$lastCalibrationDate."', 'PENDING')";
                if (!$conn->query($sql)) {
                    $error = $conn->error;
                } else {
                    $id = (string)$conn->insert_id;
                }
            }
        }

        if ($error !== '') {
            echo json_encode(array('status' => 'error', 'message' => $error));
        } else {
            $sql = "UPDATE ooc SET
                        calibration_date='".$lastCalibrationDate."',
                        measured_parameter='".$measuredParameter."',
                        implication='".$implication."',
                        calibration_parameter='".$calibrationParameter."',
                        status='DONE'
                    WHERE id='".$id."'";
            if ($conn->query($sql)) {
                echo json_encode(array('status' => 'success'));
            } else {
                echo json_encode(array('status' => 'error', 'message' => $conn->error));
            }
        }
    } else if($_GET['type'] == 'downloadDailyEquipments'){
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheadeer';  include('../pdfimp2.php');
        $html.='<h3 style="text-align:center;">DailyEquipments</h3>
        <h3>Format No:</h3>
        <table cellpadding="5">
            <thead>
                <tr style=" background-color:#DDDAD9;">
                    <td style="width: 25%;">Sr.</td>
                    <td style="width: 25%;">Equipment Code</td>
                    <td style="width: 25%;">Location</td>
                    <td style="width: 25%;">Capacity</td>
                </tr>
            </thead>
            <tbody>';
            $counter=1;
            $sql = "SELECT * FROM equipment WHERE department='Store' AND equipment_name LIKE '%Balance%'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr>
                        <td style="width: 25%;">'.$counter++.'</td>
                        <td style="width: 25%;">'.$row["equipment_code"].'</td>
                        <td style="width: 25%;">'.$row["location"].'</td>
                        <td style="width: 25%;">'.$row["capacity"].'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('DailyEquipments','I');
    } else if($_GET['type'] == 'downloadDailyEquipmentRwport'){
        $_GET['filename'] = 'Weighing Balance Daily Verification'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
    	$html.='
    	<h2 style="text-align:cenetr">Weighing Balance Daily Verification</h2>
    	<table cellpadding="5" border="1">
    	    <tr>
    	        <td style="width:35%; text-align:centre;"><b>Equipment Code</b></td>
    	        <td style="width:30%; text-align:centre;"><b>Location</b></td>
    	        <td style="width:35%; text-align:centre;"><b>Capacity</b></td>
    	    </tr>';
    	    $sql = "SELECT * FROM equipment WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    	    $html.='<tr>
    	        <td style="width:35%;">'.$row['equipment_code'].'</td>
    	        <td style="width:30%;">'.$row['location'].'</td>
    	        <td style="width:35%;">'.$row['capacity'].'</td>
    	    </tr>';
            }
        }
    	$html.='</table>
    	<div></div>
    	<table cellpadding="5" border="1">
    	    <tr>
    	        <td style="width:15%; text-align:centre;"><b>Date</b></td>
    	        <td style="width:25%; text-align:centre;"><b>Standard Weight (kg)</b></td>
    	        <td style="width:20%; text-align:centre;"><b>Status Ok / Not Ok</b></td>
    	        <td style="width:20%; text-align:centre;"><b>Remark</b></td>
    	        <td style="width:20%; text-align:centre;"><b>Done By</b></td>
    	    </tr>';
    	     $sql1 = "SELECT * FROM calibration WHERE id='".$_GET["id"]."'";

                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $row1 = array();
                    $row1["entry_date"] = date("Y-m-d", $timestamp);
                    $row1["status"] = 'PENDING';
                    $output1[] = $row1;
                
                
                $sql1 = "SELECT c.*, e.firstname as check_by, e1.firstname as entry_by FROM calibration c LEFT JOIN employee e ON c.check_by=e.emp_id LEFT JOIN employee e1 ON c.entry_by=e1.emp_id WHERE c.equipment_code='".$row["equipment_code"]."' AND c.status !='REVERT' AND c.entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weights"] = json_decode($row1["weights"]);
                        $output1[] = $row1;
                    
                
                $row["records"] = $output1;
    	    $html.='<tr>
    	        <td style="width:15%;">'.$row['entry_date'].'</td>
    	        <td style="width:25%;"></td>
    	        <td style="width:20%;"></td>
    	        <td style="width:20%;"></td>
    	        <td style="width:20%;"></td>
    	    </tr>';
                    }
                }
            }
    	$html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('DailyEquipments','I');
    }else if($_GET['type'] == 'downloadMonthlyEquipmentRwport'){
        // $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $sql = "SELECT * FROM equipment WHERE department='Store' AND equipment_name LIKE '%Weighing Balance%' AND id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
           
                $sql1 = "SELECT c.*,e.firstname,e1.firstname as checked FROM calibration c LEFT JOIN employee e ON c.entry_by=e.emp_id LEFT JOIN employee e1 ON c.check_by=e1.emp_id WHERE c.equipment_code='".$row["equipment_code"]."' AND  c.id='".$_GET["id"]."' ";
                //$sql1 = "SELECT c.*,m.entry_date,m.uncertinty,m.uncertinty_remark,e.firstname,e1.firstname as checked FROM calibration c LEFT JOIN calibration_monthly m ON c.equipment_code=m.equipment_code  LEFT JOIN employee e ON c.entry_by=e.emp_id LEFT JOIN employee e1 ON c.check_by=e1.emp_id WHERE c.equipment_code='".$row["equipment_code"]."' AND  c.id='".$_GET["id"]."' ";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $row["weights"] = json_decode($row["weights"]);
                
                $_GET["capacity"]=$row["capacity"];
                $_GET["unit"]=$row["unit"];
                $_GET["equipment_code"]=$row["equipment_code"];
                $_GET["calibration_date"]=date('d-m-Y',strtotime($row1["entry_date"]));
                

                class MYPDF extends TCPDF {
                public function Header() {
                    $table='
                    <style>td { border:solid 1px BCBBBA;}</style>
                    <table>
                        <tr>
                            <td style="width:20%;">';$this->Image('@'.file_get_contents($_GET['logo']),20,7,17);$table.='</td>
                            <td style="width:80%;text-align:center;font-weight:bold;">
                                <span style="font-family:times;font-size:17px;">'.$_GET['company_name'].'</span><br>
                                <span style="font-size:9px;">'.$_GET['address'].'</span>
                            </td>
                        </tr>
                    </table>
                    <h3 style="text-align: center;">MONTHLY CALIBRATION RECORD OF WEIGHING BALANCE ('.$_GET["capacity"].' '.$_GET["unit"].')</h3>';
                    if( $_GET["capacity"]=='300'){
                        $table1='<h3>Format No:WH014/F/04-04</h3>';
                    }else if( $_GET["capacity"]=='200'){
                        $table1='<h3>Format No:WH014/F/01-06</h3>';
                    }else{
                        $table1='<h3>Format No:WH014/F/01-06</h3>';
                    }
                    $table4='
                    <table cellpadding="3" border="1">
                        <tr>
                            <td style="width:22%;">Subject</td><td style="width:3%;">:</td><td style="width:25%;">Calibration </td>
                            <td style="width:22%;">Date of Calibration</td><td style="width:3%;">:</td><td style="width:25%;">'.$_GET['calibration_date'].'</td>
                        </tr>
                        <tr>
                            <td style="width:22%;">Instrument </td><td style="width:3%;">:</td><td style="width:25%;">Weighing Balance</td>
                            <td style="width:22%;">Next Calibration Due Date</td><td style="width:3%;">:</td><td style="width:25%;"></td>
                        </tr>
                        <tr>
                            <td style="width:22%;">ID No.</td><td style="width:3%;">:</td><td style="width:25%;">'.$_GET['equipment_code'].'</td>
                            <td style="width:22%;">Certificate no.</td><td style="width:3%;">:</td><td style="width:25%;"></td>
                        </tr>
                    </table>';
                    $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                    $this->SetY('35'); $this->writeHTML($table1, true, false, false, false, '');
                    $this->SetY('45'); $this->writeHTML($table4, true, false, false, false, '');
                    $this->SetY(25); $this->SetFont('helvetica', '', 12); $this->Cell(0, 0, $_GET['formatno'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                    
                }
                public function Footer(){
                     $table='
                    <style>td { border:solid 1px BCBBBA;}</style>
                    <table cellpadding="5">
                        <tr style="text-align:center;background-color:#DDDAD9;">
                            <td style="width:25%">Prepared By</td>
                            <td style="width:25%">Reviewed By</td>
                            <td style="width:25%">Reviewed By</td>
                            <td style="width:25%">Approved By</td>
                        </tr>
                        <tr>
                            <td style="width:10%">Name.</td>
                            <td style="width:15%">'.$_GET['prepared_by'].'('.$_GET['prepared_id'].')</td>
                            <td style="width:10%">Name.</td>
                            <td style="width:15%">'.$_GET['reviewed_by1'].'</td>
                            <td style="width:10%">Name.</td>
                            <td style="width:15%">'.$_GET['reviewed_by2'].'</td>
                            <td style="width:10%">Name.</td>
                            <td style="width:15%">'.$_GET['approved_by'].'('.$_GET['approved_id'].')</td>
                        </tr>
                        <tr>
                            <td style="width:10%">Date/Sign.</td>
                            <td style="width:15%">'.$_GET['prepared_date'].'</td>
                            <td style="width:10%">Sign.</td>
                            <td style="width:15%">'.$_GET['reviewed_date1'].'</td>
                            <td style="width:10%">Sign.</td>
                            <td style="width:15%">'.$_GET['reviewed_date2'].'</td>
                            <td style="width:10%">Sign.</td>
                            <td style="width:15%">'.$_GET['approved_date'].'</td>
                        </tr>
                    </table>';
                    $this->SetY(-40);
                    $this->SetFont('Times', '', 10);
                    $this->writeHTML($table, true, false, false, false, '');
                    $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                }
            }
            $pdf = new MYPDF (L, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(10,70,10, $_GET['pdfbottom']);
            $pdf->SetAutoPageBreak(TRUE, 45);
            $pdf->AddPage($_GET['pdfpage']);
            $pdf->SetY(70);
            $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
            $html='<style>td { border:solid 1px BCBBBA;}</style>';
           
                $html.='
                <h3>1.	Measurement of Repeatability and Uncertainty: </h3>
                <table border="1" cellpadding="3">
                    <thead>
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width: 5%;">Sr. No.</td>
                            <td style="width: 10%;">Standard weight</td>
                            <td style="width: 10%;">Calibration Start Time</td>
                            <td style="width: 10%;">Acceptance criteria</td>
                            <td style="width: 45%;">Observed weight</td>
                            <td style="width: 10%;">Calibration End Time</td>
                            <td style="width: 10%;">Uncertainty =(SD x 2) / Std. Wt.(NMT 0.10 %)</td>
                        </tr>
                    </thead>';
                    $j=1;
                    //$sql2="SELECT * FROM calibration_monthly"; 
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                        $row2["uncertinty"] = json_decode($row2["uncertinty"]);
                        $uncertinty=$row2["uncertinty"];
                        for($i=0;$i< count($uncertinty);$i++){
                            $uncertainty=$uncertinty[$i];
                            $html.='<tr nobr="true">
                                <td rowspan="4" style="width: 5%;">'.$j.'</td>
                                <td rowspan="4" style="width: 10%;">150 kg</td>
                                <td rowspan="4" style="width: 10%;"></td>
                                <td rowspan="4" style="width: 10%;">149.85 to 150.15 kg</td>
                                <td style="width:9%;">1</td>
                                <td style="width:9%;">2</td>
                                <td style="width:9%;">3</td>
                                <td style="width:9%;">4</td>
                                <td style="width:9%;">5</td>
                                <td rowspan="4" style="width: 10%;">'.$row['po_no'].'</td>
                                <td rowspan="4" style="width: 10%;">'.$row['po_date'].'</td>
                            </tr>
                            <tr>
                                <td style="width:9%;">'.$uncertainty->obs_1.'</td>
                                <td style="width:9%;">'.$uncertainty->obs_2.'</td>
                                <td style="width:9%;">'.$uncertainty->obs_3.'</td>
                                <td style="width:9%;">'.$uncertainty->obs_4.'</td>
                                <td style="width:9%;">'.$uncertainty->obs_5.'</td>
                            </tr>
                            <tr>
                                <td style="width:9%;">6</td>
                                <td style="width:9%;">7</td>
                                <td style="width:9%;">8</td>
                                <td style="width:9%;">9</td>
                                <td style="width:9%;">10</td>
                            </tr>
                            <tr>
                                <td style="width:9%;"></td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;"></td>
                                <td style="width:9%;"></td>
                            </tr>';
                        }
                        }
                    }
        $html.='</table>';
        //     }
        // }
        $html.='
        <table cellpadding="3">
            <tr>
                <td style="width:100%; border:none;"><b>Remarks:</b>'.$row1['uncertinty_remark'].'</td>
            </tr>
            <tr>
                <td style="width:50%;"><b>Calibrated by:</b></td><td style="width:50%;"><b>Checked by:</b></td>
            </tr>';
        $html.='</table>
        <br pagebreak="true"/>';
        $html.='
        <h3>2.	Drift measurement:</h3>
                <table border="1" cellpadding="3">
                    <thead>
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width: 5%;">Sr. No.</td>
                            <td style="width: 10%;">Standard weight</td>
                            <td style="width: 10%;">Calibration Start Time</td>
                            <td style="width: 10%;">Acceptance criteria</td>
                            <td style="width: 45%;">Observed weight</td>
                            <td style="width: 10%;">Calibration End Time</td>
                            <td style="width: 10%;">Uncertainty =(SD x 2) / Std. Wt.(NMT 0.10 %)</td>
                        </tr>
                    </thead>';
                    $html.='<tr nobr="true">
                        <td rowspan="4" style="width: 5%;">'.$i.'.</td>
                        <td rowspan="4" style="width: 10%;">100 kg</td>
                        <td rowspan="4" style="width: 10%;">'.$row['client_type'].'</td>
                        <td rowspan="4" style="width: 10%;">99.90 to 100.10 kg </td>
                        <td style="width:9%;">0</td>
                        <td style="width:9%;">1</td>
                        <td style="width:9%;">2</td>
                        <td style="width:9%;">3</td>
                        <td style="width:9%;">4</td>
                        <td rowspan="4" style="width: 10%;">'.$row['po_no'].'</td>
                        <td rowspan="4" style="width: 10%;">'.$row['po_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:9%;"></td>
                        <td style="width:9%;"></td>
                        <td style="width:9%;"></td>
                        <td style="width:9%;"></td>
                        <td style="width:9%;"></td>
                    </tr>';
      
            $html.='</table>
            <table cellpadding="3">
                <tr>
                    <td style="width:100%; border:none;"><b>Remarks:</b> Satisfactory/Not Satisfactory</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Calibrated by:</b></td><td style="width:50%;"><b>Checked by:</b></td>
                </tr>';
            $html.='</table>
            <br pagebreak="true"/>';
            
            $html.='
        <h3>3.Whole Range Calibration:</h3>
                <table border="1" cellpadding="3">
                    <thead>
                        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                            <td style="width: 5%;">Sr. No.</td>
                            <td style="width: 10%;">Standard weight</td>
                            <td style="width: 10%;">Calibration Start Time</td>
                            <td style="width: 15%;">Observed weight</td>
                            <td style="width: 30%;">Acceptance criteria Tolerance limit is ± 0.10% of actual weight.</td>
                            <td style="width: 10%;">Calibration End Time</td>
                            <td style="width: 10%;">Status Ok / Not Ok</td>
                            <td style="width: 10%;">Remark</td>
                        </tr>
                    </thead>';
                    $html.='<tr nobr="true">
                        <td style="width: 5%;">1</td>
                        <td style="width: 10%;">0.40 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 30%;">0.40 to 0.42 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                    </tr>
                    <tr nobr="true">
                        <td style="width: 5%;">2</td>
                        <td style="width: 10%;">50.00 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 30%;">49.96 to 50.06 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                    </tr>
                     <tr nobr="true">
                        <td style="width: 5%;">3</td>
                        <td style="width: 10%;">100.00 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 30%;">99.90 to 100.10 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                    </tr>
                     <tr nobr="true">
                        <td style="width: 5%;">4</td>
                        <td style="width: 10%;">200.00 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 15%;"></td>
                        <td style="width: 30%;">199.80 to 200.20 kg</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%;"></td>
                    </tr>';
            $html.='</table>
            <table cellpadding="3">
                <tr>
                    <td style="width:100%; border:none;"><b>Remarks:</b> Satisfactory/Not Satisfactory</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Calibrated by:</b></td><td style="width:50%;"><b>Checked by:</b></td>
                </tr>';
            $html.='</table>';
            //     }
            // }
           
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MonthlyEquipmentRwport.pdf', 'I');
    }
}

$conn->close();
?>