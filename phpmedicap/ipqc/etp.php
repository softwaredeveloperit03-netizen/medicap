<?php



 
    require '../db.php';
    require '../token.php';
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "saveRequest") {
        $sql = "INSERT INTO etp_inprocess (plant_no, previous_product, next_product, sample_from, equipment_code, etp_no, sample_id, stage, qty, unit, send_by, send_date, send_time) VALUES ('".$input["plant_no"]."','".$input["previous_product"]."','".$input["next_product"]."','".$input["sample_from"]."','".$input["equipment_code"]."','".$input["etp_no"]."','".$input["sample_id"]."','".$input["stage"]."','".$input["qty"]."','".$input["unit"]."','".$_GET["emp_id"]."','$entry_date','$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRequestsLog") {
        $output = array();
        $sql = "SELECT t.*, p.product_name, p.grade FROM etp_inprocess t LEFT JOIN product p ON t.next_product=p.product_code WHERE DATE(t.send_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND t.plant_no LIKE '%".$_GET["plant_no"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["tests"] = json_decode($row["tests"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingRequests") {
        $output = array();
        $sql = "SELECT e.*, p.product_name, p.grade FROM etp_inprocess e LEFT JOIN product p ON e.next_product=p.product_code WHERE e.status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no= (SELECT specification_no FROM specification WHERE spec_type LIKE '%Inprocess Specification%' AND product_code='".$row["product_code"]."')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $row["isspecification"] = "yes";
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["tests"] = $output1;
                } else {
                    $row["isspecification"] = "no";
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveTestingRequest") {
 
        $sql = "UPDATE etp_inprocess SET tests='".json_encode($input["tests"])."', remark='".$input["remark"]."', analysis_by='".$input["emp_id"]."', analysis_date='$entry_date', status='done' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     if ($_GET["type"] == "downloadRequestsReport") {
          require_once('../tcpdf/tcpdf.php');

        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
       
        
        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_RIGHT);
        
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        
        // set font
        $pdf->SetFont('times', '', 10);
        
        // add a page
        $pdf->AddPage();
        
        // set some text to print
        
        
        $output = array();
        $sql = "SELECT t.*, p.product_name, p.grade,p.approve_by,p.approve_date FROM etp_inprocess t LEFT JOIN product p ON t.next_product=p.product_code WHERE t.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<table border="1" cellpadding="5">
                <tr>
                    <td style="width:20%;text-align:center;"><img src="https://bajaj.paperlessgmp.live/api/gmptotal/upload/user/bajaj1.png" height="50" width="50"></td>
                    <td style="width:80%;font-size:18px;text-align:center;"><b>BAJAJ HEALTHCARE LTD. (UNIT II), SAVLI</b></td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;"><b>INPROCESS TEST REQUEST SLIP</b></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Format No:</b>: EHS001/F/02-01</td>
                    <td style="width:50%;"><b>Tick mark on required Stage/Sample/Test details</b></td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>TO</b>: QCLab.</td>
                    <td style="width:50%;"><b>From</b>: Plant No. - 7</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Previous Product:</b>'.$row['previous_product'].'</td>
                    <td style="width:50%;"><b>Next Product:</b>'.$row['next_product'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Equipment/Tank ID:</b>'.$row['equipment_code'].'</td>
                    <td style="width:50%;"><b>ETP No:</b>'.$row['etp_no'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Sample ID</b>'.$row['sample_id'].'</td>
                    <td style="width:50%;"><b>Stage:</b>'. $row['stage'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;"><b>Kindly test thesample And Send back Report</b></td>
                </tr>
                <tr>
                    <td style="width:25%;"><b>Sample send by:</b>'.$row['send_by'].'</td>
                    <td style="width:25%;"><b>Date:</b>'.$row['send_date'].'</td>
                    <td style="width:25%;"><b>Time:</b>'.$row['send_time'].'</td>
                    <td style="width:25%;"><b>Sample received by :</b>'.$row['approve_by'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;text-align:center;"><b>TEST ANALYSIS REPORT </b></td>
                </tr>
                <tr>
                     <td style="width:50%;"><b>From :</b>'.$row['sample_from'].'</td>
                     <td style="width:50%;"><b>A. R. No.:</b>'.$row['ar_no'].'</td>
                </tr>
                <tr>
                    <td style="width:15%;"><b>Stage</b></td>
                    <td style="width:5%;"><b>No</b></td>
                    <td style="width:30%;"><b>Test</b></td>
                    <td style="width:25%;"><b>Result</b></td>
                    <td style="width:25%;"><b>Limit</b></td>
                </tr>';
                $j=1;
                $row["tests"] = json_decode($row["tests"]);
                $tests = $row["tests"];
                for ($k = 0; $k < count($tests); $k++) {
                 $test = $tests[$k];        
        $html.='<tr>
                    <td style="width:15%;">'.$test->stage.'</td>
                    <td style="width:5%;">'.$j++.'.</td>
                    <td style="width:30%;">'.$test->test.'</td>
                    <td style="width:25%;">'.$test->result.'</td>
                    <td style="width:25%;">'.$test->limits.'</td>
                </tr>';
                }
                
        $html.='<tr>
                    <td style="width:100%;"><b>Remarks:</b>'.$row['remark'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Analyzed by/Date:</b>'.$row['analysis_by'].'<br>'.$row['analysis_date'].'<div></div></td>
                    <td style="width:50%;"><b>Checked by/Date : </b>'.$row['approve_by'].'<br>'.$row['approve_date'].'<div></div></td>
                </tr>
                </table>';
         
            }
        }
      
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Testing Report.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadRequestsLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Requests Log'; $_GET['pdftype'] = 'noheader'; include("../pdfimp1.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Previous Product	</td>
                    <td style="width: 12%;">Next Product	</td>
                    <td style="width: 12%;">Equipment/Tank ID	</td>
                    <td style="width: 12%;">ETP No	</td>
                    <td style="width: 10%;">Sample ID	</td>
                    <td style="width: 10%;">Sample Qty	</td>
                    <td style="width: 8%;">Unit</td>
                    <td style="width: 8%;">Stage</td>
                    <td style="width: 8%;">Sample Send by	</td>
                    <td style="width: 10%;">Actio</td>
                </tr>
            </thead>';
                 $output = array();
                 $sql = "SELECT t.*, p.product_name, p.grade FROM etp_inprocess t LEFT JOIN product p ON t.next_product=p.product_code WHERE DATE(t.send_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND t.plant_no LIKE '%".$_GET["plant_no"]."%'";
                 $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                         while ($row = $result->fetch_assoc()) {
                         $row["tests"] = json_decode($row["tests"]);
                         $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row['plant_no'].'.</td>
                        <td style="width: 12%;">'.$row['previous_product'].'</td>
                        <td style="width: 12%;">'.$row['next_product'].'</td>
                        <td style="width: 12%;">'.$row['equipment_code'].'</td>
                        <td style="width: 10%;">'.$row['etp_no'].'</td>
                        <td style="width: 10%;">'.$row['sample_id'].'</td>
                        <td style="width: 8%;">'.$row['qty'].'</td>
                        <td style="width: 8%;">'.$row['unit'].'</td>
                        <td style="width: 8%;">'.$row['stage'].'</td>
                        <td style="width: 10%;">'.$row['send_by'].'</td>
                    </tr>';
                $i++;
                     
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Request.pdf', 'I');
    }
     
     else if ($_GET["type"] == "downloadRequestLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Requests Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.No</td>
                    <td style="width: 11%;">Date</td>
                    <td style="width: 9%;">Plant Name</td>
                    <td style="width: 10%;">Previous Product</td>
                    <td style="width: 10%;">Next Product</td>
                    <td style="width: 11%;">Equipment Code</td>
                    <td style="width: 7%;">Stage</td>
                    <td style="width: 6%;">ETP No</td>
                    <td style="width: 8%;">Sample From</td>
                    <td style="width: 8%;">Sample ID</td>
                    <td style="width: 8%;">Sample Qty</td>
                    <td style="width: 7%;">Unit</td>
                  
                </tr>
            </thead>';
                 $output = array();
                 $sql = "SELECT t.*, p.product_name, p.grade FROM etp_inprocess t LEFT JOIN product p ON t.next_product=p.product_code WHERE DATE(t.send_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND t.plant_no LIKE '%".$_GET["plant_no"]."%'";
                 $result = $conn->query($sql);
                 $i=1;
                    if ($result->num_rows > 0) {
                         while ($row = $result->fetch_assoc()) {
                         $row["tests"] = json_decode($row["tests"]);
                         $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 11%;">'.$row['send_date'].'.</td>
                        <td style="width: 9%;">'.$row['plant_no'].'.</td>
                        <td style="width: 10%;">'.$row['previous_product'].'</td>
                        <td style="width: 10%;">'.$row['next_product'].'</td>
                        <td style="width: 11%;">'.$row['equipment_code'].'</td>
                        <td style="width: 7%;">'.$row['stage'].'.</td>
                        <td style="width: 6%;">'.$row['etp_no'].'</td>
                        <td style="width: 8%;">'.$row['sample_from'].'.</td>
                        <td style="width: 8%;">'.$row['sample_id'].'</td>
                        <td style="width: 8%;">'.$row['qty'].'</td>
                        <td style="width: 7%;">'.$row['unit'].'</td>
                    </tr>';
                $i++;
                     
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Request.pdf', 'I');
    }

}

$conn->close();
?>