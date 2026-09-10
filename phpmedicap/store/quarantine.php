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
    
    if ($_GET["type"] == "getAwaitingQuarantineRawLabels") {
        $output = Array();
        $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name, c1.inword_no, DATE(c.receiving_date) as receiving_date FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN challan c1 ON c.inward_no=c1.inword_no WHERE m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM label WHERE label_type='Quarantine' AND material_code='".$row["material_code"]."' AND inward_no='".$row["inward_no"]."' AND batch_no='".$input["batch_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $batches = json_decode($row["batches"]);
                    for ($i = 0; $i < count($batches); $i++) {
                        $batch = $batches[$i];
                        $batch->id = $row["id"];
                        $batch->inward_no = $row["inward_no"];
                        $batch->material_name = $row["material_name"];
                        $batch->material_code = $row["material_code"];
                        $batch->grade = $row["grade"];
                        $batch->manufacturer = $row["manufacturer"];
                        $batch->vendor_no = $row["vendor_no"];
                        $batch->receiving_date = $row["receiving_date"];
                        $output[] = $batch;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getQuarantineLabels") {
        $output = array();
        $sql = "SELECT l.*, m.material_name, m.grade FROM label l LEFT JOIN material m ON l.material_code=m.material_code WHERE l.label_type='Quarantine'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "printQuarantineLabel") {
        $_GET['filename'] = 'Quarantine'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $output = Array();
        $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code  WHERE m.material_type='Raw Material' AND c.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $batches=$_GET["batch_no"];
                $sql1 = "SELECT * FROM label WHERE label_type='Quarantine' AND material_code='".$row["material_code"]."' AND inward_no='".$row["inward_no"]."' AND batch_no='".$input["batch_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $batches = json_decode($row["batches"]);
                    for ($i = 0; $i < count($batches); $i++) {
                        $batch = $batches[$i];
                        $batch->id = $row["id"];
                        $batch->inward_no = $row["inward_no"];
                        $batch->material_name = $row["material_name"];
                        $batch->material_code = $row["material_code"];
                        $batch->grade = $row["grade"];
                        $batch->manufacturer = $row["manufacturer"];
                        $batch->vendor_no = $row["vendor_no"];
                        $batch->receiving_date = $row["receiving_date"];
                        $mfg_date=$batch->mfg_date;
                        $exp_date=$batch->exp_date;
                        $batch_no=$batch->batch_no;
                        $total_containers=$batch->total_containers;
                        $qty_received=$batch->qty_received;
                        class MYPDF extends TCPDF {
                            public function Header() {}
                            public function Footer() {}
                        }
                    
                        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetMargins(10, 10, 10, 15);
                        $pdf->SetAutoPageBreak(TRUE, 10);
                        $pdf->AddPage('P', 'A4');
                        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                        
                        $batches=$_GET["batch_no"];
                        for ($i=1; $i <=$total_containers; $i++) {
                            if($i ==  $total_containers && $i % 2 !== 0){
                                $html.='
                                <h2 style="text-align:center">Quarantine</h2>
                                &nbsp;<br>
                                <table cellpadding="-5" style="width:100%;">
                                    <tr>
                                        <td style="width:49%;">
                                        </tr>
                                            <table cellpadding="2" nobr="true" style="background-color:#C4A484">
                                                <tr>
                                                    <td style="width:100%; text-align:center; font-weight:bold; font-size:12px;">Quarantine</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:30%;"><b>Material Name</b></td>
                                                    <td style="width:70%;">:'.$row['material_name'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:30%;"><b>Batch/LOT.No</b></td>
                                                    <td style="width:20%;">:'.$batch_no.'</td>
                                                    <td style="width:25%;"><b>RECD.QTY</b></td>
                                                    <td style="width:25%;">:'.$qty_received.'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:38%;"><b>MFG.Name & Add:</b></td>
                                                    <td style="width:62%;">'.$row['manufacturer'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>SUPP.Name</b></td>
                                                    <td style="width:75%;">:'.$row['supplier'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>MFG.Date</b></td>
                                                    <td style="width:25%;">:'.$mfg_date.'</td>
                                                    <td style="width:25%;"><b>Exp.Date</b></td>
                                                    <td style="width:25%;">:'.$exp_date.'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>G.R.N.No</b></td>
                                                    <td style="width:25%;">:'.$row['grn_no'].'</td>
                                                    <td style="width:25%;"><b>CONT.NO</b></td>
                                                    <td style="width:25%;">:'.$row['contact'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;"><b>Sign/Date</b></td>
                                                    <td style="width:25%;"><b>Receipt.DT</b></td>
                                                    <td style="width:25%;">:'.$row['receipt'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width:4%;"></td>
                                        <td style="width:49%;"></td>
                                    </tr>
                                </table>';
                            }else{
                                $html.='&nbsp;<br>
                                <table cellpadding="-5" style="width:100%;">
                                    <tr>
                                        <td style="width:49%;">
                                             <table cellpadding="2" nobr="true" style="background-color:#C4A484">
                                                <tr>
                                                    <td style="width:100%; text-align:center; font-weight:bold; font-size:12px;">Quarantine</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:30%;"><b>Material Name</b></td>
                                                    <td style="width:70%;">:'.$row['material_name'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:30%;"><b>Batch/LOT.No</b></td>
                                                    <td style="width:20%;">:'.$batch_no.'</td>
                                                    <td style="width:25%;"><b>RECD.QTY</b></td>
                                                    <td style="width:25%;">:'.$qty_received.'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:38%;"><b>MFG.Name & Add:</b></td>
                                                    <td style="width:62%;">'.$row['manufacturer'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>SUPP.Name</b></td>
                                                    <td style="width:75%;">:'.$row['supplier'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>MFG.Date</b></td>
                                                    <td style="width:25%;">:'.$mfg_date.'</td>
                                                    <td style="width:25%;"><b>Exp.Date</b></td>
                                                    <td style="width:25%;">:'.$exp_date.'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>G.R.N.No</b></td>
                                                    <td style="width:25%;">:'.$row['grn_no'].'</td>
                                                    <td style="width:25%;"><b>CONT.NO</b></td>
                                                    <td style="width:25%;">:'.$row['contact'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;"><b>Sign/Date</b></td>
                                                    <td style="width:25%;"><b>Receipt.DT</b></td>
                                                    <td style="width:25%;">:'.$row['receipt'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width:2%;"></td>
                                        <td style="width:49%;">
                                            <table cellpadding="2" nobr="true" style="background-color:#C4A484">
                                                <tr>
                                                    <td style="width:100%; text-align:center; font-weight:bold; font-size:12px;">Quarantine</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:30%;"><b>Material Name</b></td>
                                                    <td style="width:70%;">:'.$row['material_name'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:30%;"><b>Batch/LOT.No</b></td>
                                                    <td style="width:20%;">:'.$batch_no.'</td>
                                                    <td style="width:25%;"><b>RECD.QTY</b></td>
                                                    <td style="width:25%;">:'.$qty_received.'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:38%;"><b>MFG.Name & Add:</b></td>
                                                    <td style="width:62%;">'.$row['manufacturer'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>SUPP.Name</b></td>
                                                    <td style="width:75%;">:'.$row['supplier'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>MFG.Date</b></td>
                                                    <td style="width:25%;">:'.$mfg_date.'</td>
                                                    <td style="width:25%;"><b>Exp.Date</b></td>
                                                    <td style="width:25%;">:'.$exp_date.'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:25%;"><b>G.R.N.No</b></td>
                                                    <td style="width:25%;">:'.$row['grn_no'].'</td>
                                                    <td style="width:25%;"><b>CONT.NO</b></td>
                                                    <td style="width:25%;">:'.$row['contact'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width:50%;"><b>Sign/Date</b></td>
                                                    <td style="width:25%;"><b>Receipt.DT</b></td>
                                                    <td style="width:25%;">:'.$row['receipt'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>';
                            }
                            $i++;
                        }
                    }
                }
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuarantineLabel.pdf', 'I');
    }
}

$conn->close();
?>