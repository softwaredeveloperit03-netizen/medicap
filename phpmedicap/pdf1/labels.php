<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
 



// ini_set('display_errors', 1);
// error_reporting(E_ALL);

function labels_safe_date($value) {
    if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime((string)$value);
    return ($ts && $ts > 0) ? date('d-m-Y', $ts) : '-';
}

function labels_container_count($row) {
    $fromGet = isset($_GET['containers']) ? (int)$_GET['containers'] : 0;
    if ($fromGet > 0) {
        return $fromGet;
    }
    foreach (array('sampledContainers', 'sampled_container', 'containers', 'total_containers') as $key) {
        if (isset($row[$key]) && (int)$row[$key] > 0) {
            return (int)$row[$key];
        }
    }
    return 1;
}


$token = isset($_GET['token']) ? str_replace(' ', '+', trim((string)$_GET['token'])) : '';
$_GET['token'] = $token;
$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result && $result->num_rows > 0){
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]); 
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

   $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$conn->real_escape_string($token)."','".$conn->real_escape_string((string)($_GET["type"] ?? ''))."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
@$conn->query($sql);
    if($_GET["type"]=="labelPrints"){
        $sql = "SELECT * FROM stock_book WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            if($row['status'] == 'Approved'){
                $color = '#9CFC8E';
            }else if($row['status'] == 'Under Test'){
                $color = '#99E8FD';
            }else if($row['status'] == 'Quarantine'){
                $color = '#FFC1A7';
            }
                
            $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
            $result1 = $conn->query($sql1);
            $row1 = $result1->fetch_assoc();
            
            class MYPDF extends TCPDF {
                public function Header() {}
                public function Footer() {}
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(10, 10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            for ($i=1; $i <= $_GET['no']; $i++) {
                $html.='&nbsp;<br>
                    <table cellpadding="-5" style="width:100%;">
                        <tr>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:'.$color.';">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:40%;"><b>Name of Material</b></td>
                                        <td style="border:solid 1px BCBBBA; width:60%;">'.$row1['material_name'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>Grade</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row1['grade'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>Batch No</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row['batch_no'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>GRN No</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row['grn_no'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>Status</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row['status'].'</td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width:2%;"></td>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:'.$color.';">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:40%;"><b>Name of Material</b></td>
                                        <td style="border:solid 1px BCBBBA; width:60%;">'.$row1['material_name'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>Grade</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row1['grade'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>Batch No</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row['batch_no'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>GRN No</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row['grn_no'].'</td>
                                    </tr>
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;"><b>Status</b></td>
                                        <td style="border:solid 1px BCBBBA;">'.$row['status'].'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>';
            }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }else{
            echo 'Invalid id';
        }
    }
    else if($_GET["type"]=="receivingLabels"){
        $sql = "SELECT * FROM challan_materials WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                
                class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 10);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                for ($i=1; $i <= $row['containers']; $i++) {
                    if($i == $row['containers'] && $i % 2 !== 0){
                        $html.='&nbsp;<br>
                        <table cellpadding="-5" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Receiving</b></td>
                                            <td style="border:solid 1px BCBBBA; width:74%;">
                                                <table>
                                                    <tr>
                                                        <td colspan="2"><b>'.$row1['material_name'].'</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Grade</b></td>
                                                        <td>'.$row1['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Batch No</b></td>
                                                        <td>'.$row['batch_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Container No</b></td>
                                                        <td>'.$i.' /'.$row['containers'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;"></td>
                            </tr>
                        </table>';
                    }else{
                    $html.='&nbsp;<br>
                        <table cellpadding="-5" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Receiving</b></td>
                                            <td style="border:solid 1px BCBBBA; width:74%;">
                                                <table>
                                                    <tr>
                                                        <td colspan="2"><b>'.$row1['material_name'].'</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Grade</b></td>
                                                        <td>'.$row1['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Batch No</b></td>
                                                        <td>'.$row['batch_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Container No</b></td>
                                                        <td>'.$i.' /'.$row['containers'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;">
                                    <table cellpadding="5" nobr="true" style="background-color:'.$color.';">
                                        <tr>
                                            <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Receiving</b></td>
                                            <td style="border:solid 1px BCBBBA; width:74%;">
                                                <table>
                                                    <tr>
                                                        <td colspan="2"><b>'.$row1['material_name'].'</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Grade</b></td>
                                                        <td>'.$row1['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Batch No</b></td>
                                                        <td>'.$row['batch_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Container No</b></td>
                                                        <td>'.($i+1).' /'.$row['containers'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>';
                    }
                    $i++;
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }else{
            echo 'Invalid id';
        }
    }
    else if($_GET["type"]=="grnLabels"){
        $sql = "SELECT * FROM raw_material WHERE grn NOT IN ('inprocess', 'pending') AND id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                
                class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 10);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                for ($i=1; $i <= $row['containers']; $i++) {
                    if($i == $row['containers'] && $i % 2 !== 0){
                        $html.='&nbsp;<br>
                        <table cellpadding="-5" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                                    <table cellpadding="5" nobr="true" style="background-color:#C4A484">
                                        <tr>
                                            <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                            <td style="border:solid 1px BCBBBA; width:74%;">
                                                <table>
                                                    <tr>
                                                        <td colspan="2"><b>'.$row1['material_name'].'</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Grade</b></td>
                                                        <td>'.$row1['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Batch No</b></td>
                                                        <td>'.$row['batch_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Container No</b></td>
                                                        <td>'.$i.' /'.$row['containers'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;"></td>
                            </tr>
                        </table>';
                    }else{
                    $html.='&nbsp;<br>
                        <table cellpadding="-5" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                                    <table cellpadding="5" nobr="true" style="background-color:#C4A484">
                                        <tr>
                                            <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                            <td style="border:solid 1px BCBBBA; width:74%;">
                                                <table>
                                                    <tr>
                                                        <td colspan="2"><b>'.$row1['material_name'].'</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Grade</b></td>
                                                        <td>'.$row1['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Batch No</b></td>
                                                        <td>'.$row['batch_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Container No</b></td>
                                                        <td>'.$i.' /'.$row['containers'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;">
                                    <table cellpadding="5" nobr="true" style="background-color:#C4A484;">
                                        <tr>
                                            <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                            <td style="border:solid 1px BCBBBA; width:74%;">
                                                <table>
                                                    <tr>
                                                        <td colspan="2"><b>'.$row1['material_name'].'</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Grade</b></td>
                                                        <td>'.$row1['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Batch No</b></td>
                                                        <td>'.$row['batch_no'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Container No</b></td>
                                                        <td>'.($i+1).' /'.$row['containers'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>';
                    }
                    $i++;
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }else{
            echo 'Invalid id';
        }
    }
    else if($_GET["type"]=="samplingLabels") {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
            $sql = "SELECT s.*,m.material_name,m.grade,v1.vendor_name as manufactNAme, v2.vendor_name as supplierName FROM sampling s 
            LEFT JOIN material m ON s.material_code = m.material_code  
            LEFT JOIN vendor v1 ON s.manufacturer_no = v1.vendor_no  
            LEFT JOIN vendor v2 ON s.supplier_no = v2.vendor_no  
            WHERE   s.id='".$_GET['id']."'";
          $j =1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html = '';
                $containerCount = labels_container_count($row);
                $row['containers'] = $containerCount;
                $labelsMfgDate = labels_safe_date($row['mfg_date'] ?? '');
                $labelsExpDate = labels_safe_date($row['exp_date'] ?? '');
                $labelsSampledOn = labels_safe_date($row['sampledOn'] ?? ($row['entry_date'] ?? ''));
                $labelsSampledBy = trim((string)($row['sampledBy'] ?? ($row['entry_by'] ?? '')));
      
 
                 
                class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 10);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                for ($i=1; $i <= $containerCount; $i++) {
                    if($i == $containerCount && $i % 2 !== 0){
                        $html.='
                                         
                     <table style="border: 1px solid black; background-color: yellow;">
                     
        <tr>
           <td style="width: 50px; text-align: left;"><img src="../../upload/pdf/novo1.png" style="width:60px;height:60px;"> </td>
           <td style="width: 200px; font-weight: bold;font-size: 12px; text-align: Center;"><br><br>Quality Control Department </td>
        </tr>
        </table>
        <table  style="border: 1px solid black; background-color: yellow;">
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Material Name </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['material_name'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> RM/PM Code</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['material_code'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Pharmacopeial Grade </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['grade'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Medicap lot no. </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['batch_no'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Mfg Date  </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> ' . $labelsMfgDate . '</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Exp Date</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> ' . $labelsExpDate . ' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Mfg By</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['manufactNAme'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Supplied By</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['supplierName'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Receiving no.</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['grn_no'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Sampled By / On </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$labelsSampledBy.' / '.$labelsSampledOn.'  </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Container No.</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$j++.' of '.$row['containers'].' </td>
       </tr>
         
       <tr>
       <td style="width:125px; height: 10px; font-size: 10px; border-right: 1px solid black;"> </td>
       <td style="width:125px; height: 10px; font-size: 10px;">  
       <div style="border: 1px solid black; padding: 2px; display: inline-block;  background-color: yellow;">
        <span style="font-size: 8px; background-color: yellow;">Label No. L/SOP/QC/006/01-02</span>
      </div></td>
       </tr>
       </table>
       <table style="border: 1px solid black; background-color: yellow;">
       <tr>
          <td style="width: 250px; height: 30px; font-weight: bold;font-size: 18px; text-align: center;"><br>UNDER TEST</td>
       </tr>
       </table>
       <br>
       <br>
       <br>
       <br>
                                 ';
                    }else{
                    $html.='
                    <div></div>&nbsp;
                        <table cellpadding="0" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                               <table style="border: 1px solid black; background-color: yellow;">
        <tr>
           <td style="width: 50px; text-align: left;">  </td>
           <td style="width: 200px; font-weight: bold;font-size: 12px; text-align: Center;"><br><br>Quality Control Department </td>
        </tr>
        </table>
        <table  style="border: 1px solid black; background-color: yellow;">
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Material Name </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['material_name'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> RM/PM Code</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['material_code'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Pharmacopeial Grade </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['grade'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Medicap lot no. </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['batch_no'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Mfg Date  </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> ' . $labelsMfgDate . ' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Exp Date</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> ' . $labelsExpDate . ' </td>
       </tr>
         <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Mfg By</td>
       <td style="width:125px; height: 15px; font-size: 10px;">'.$row['manufactNAme'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Supplied By</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['supplierName'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Receiving no.</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['grn_no'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Sampled By / On </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$labelsSampledBy.' / '.$labelsSampledOn.'  </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Container No.</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$j++.' of '.$row['containers'].' </td>
       </tr>
          

 

       <tr>
       <td style="width:125px; height: 10px; font-size: 10px; border-right: 1px solid black;"> </td>
       <td style="width:125px; height: 10px; font-size: 10px;">  
       <div style="border: 1px solid black; padding: 2px; display: inline-block;  background-color: yellow;">
        <span style="font-size: 8px; background-color: yellow;">Label No. L/SOP/QC/006/01-02</span>
      </div></td>
       </tr>
       </table>
       <table style="border: 1px solid black; background-color: yellow;">
       <tr>
          <td style="width: 250px; height: 30px; font-weight: bold;font-size: 18px; text-align: center;"><br>UNDER TEST</td>
       </tr>
       </table><br>
       <br>
       <br>
       <br>
       <br>
 
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;">
                         <table style="border: 1px solid black; background-color: yellow;">
        <tr>
           <td style="width: 50px; text-align: left;"> </td>
           <td style="width: 200px; font-weight: bold;font-size: 12px; text-align: Center;"><br><br>Quality Control Department </td>
        </tr>
        </table>
        <table  style="border: 1px solid black; background-color: yellow;">
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Material Name </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['material_name'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> RM/PM Code</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['material_code'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Pharmacopeial Grade </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['grade'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Medicap lot no. </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['batch_no'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Mfg Date  </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> ' . $labelsMfgDate . '  </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Exp Date</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> ' . $labelsExpDate . '  </td>
       </tr>
         <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Mfg By</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['manufactNAme'].' </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Supplied By</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['supplierName'].'</td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Receiving no.</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$row['grn_no'].' </td>
       </tr>
        <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Sampled By / On </td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$labelsSampledBy.' / '.$labelsSampledOn.'  </td>
       </tr>
       <tr>
       <td style="width:125px; height: 15px; font-size: 10px; border-right: 1px solid black;"> Container No.</td>
       <td style="width:125px; height: 15px; font-size: 10px;"> '.$j++.' of '.$row['containers'].' </td>
       </tr>
      
     <tr>
       <td style="width:125px; height: 10px; font-size: 10px; border-right: 1px solid black;"> </td>
       <td style="width:125px; height: 10px; font-size: 10px;">  
       <div style="border: 1px solid black; padding: 2px; display: inline-block;  background-color: yellow;">
        <span style="font-size: 8px; background-color: yellow;">Label No. L/SOP/QC/006/01-02</span>
      </div></td>
       </tr>
       </table>
       <table style="border: 1px solid black; background-color: yellow;">
       <tr>
          <td style="width: 250px; height: 30px; font-weight: bold;font-size: 18px; text-align: center;"><br>UNDER TEST</td>
       </tr>
       </table>
 
                                </td>
                            </tr>
                        </table>';
                    }
                    $i++;
                }
            }
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
             $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
            exit;
        }else{
            echo 'Invalid id';
        }
    }
    
    else if($_GET["type"]=="testingLabels") {
          if($_GET["plant_id"] == 77) {
        //  $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess') AND id='".$_GET['id']."'";
         $sql = "SELECT * FROM sampling WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                 $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row1['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row1['gradeName'] = $prodLatest['gradeName'];
                
                class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 10);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                    
                for ($i=1; $i <= $row['containers']; $i++) {
                    if($i == $row['containers'] && $i % 2 == 0){
                        $html.='
                           <table cellpadding="0" style="width:100%;">
                                <tr>
                                    <th colspan="2" style="text-align: center;">ZUMA PHARMA LLC</th>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align: center;">Parkent, Tashkent, Uzbekistan</td>
                                </tr>
                                <tr>
                                    <th colspan="2" style="text-align: center;">QUALITY CONTROL DEPARTMENT</th>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align: center; font-weight: bold;">APPROVED</td>
                                </tr>
                                <tr>
                                    <td><strong>Material name</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Item Code</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>A.R. No</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Mfg. date</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Expiry date</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Retest Date</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Approved By</strong></td>
                                    <td>___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Container number</strong></td>
                                    <td>___________ of ___________</td>
                                </tr>
                                <tr>
                                    <td><strong>Format No.</strong></td>
                                    <td>SOP/QC/015-F01</td>
                                </tr>
                            </table>
                        ';
                                  
 
                    }else{$html .= '
<div></div>&nbsp;
<table cellpadding="1" style="width:100%;">
    <tr>
        <!-- First Label Section -->
        <td style="width:49%;">
            <table cellpadding="2" border="1" style="width: 100%;">
                <tr>
                    <th rowspan="3" style="width: 63px; text-align: left;">
                        <img src="../../../upload/pdf/zuma.jpg" style="width:111px;height:50px;">
                    </th>
                    <th colspan="3" style="text-align: center; padding: 10px; background-color: #00CF00;">ZUMA PHARMA LLC <br>Parkent, Tashkent, Uzbekistan</th>
                </tr>
               
                <tr>
                    <th colspan="3" style="text-align: center; padding: 10px; background-color: #00CF00;">QUALITY CONTROL DEPARTMENT</th>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 10px; font-weight: bold; background-color: #00CF00;">APPROVED</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Material name</strong></td>
                    <td colspan="2" style="padding: 10px;">'.$row1['material_name'].' </td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Item Code</strong></td>
                    <td style="padding: 10px;">'.$row1['material_code'].' </td>
                    <td style="padding: 10px;"><strong>A.R. No</strong></td>
                    <td style="padding: 10px;">'.$row1['ar_no'].' </td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Mfg. date</strong></td>
                    <td style="padding: 10px;">'.$row1['mfg_date'].' </td>
                    <td style="padding: 10px;"><strong>Expiry date</strong></td>
                    <td style="padding: 10px;">'.$row1['exp_date'].' </td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Retest Date</strong></td>
                    <td style="padding: 10px;">'.$row1['approve_date'].' </td>
                    <td style="padding: 10px;"><strong>Approved By</strong></td>
                    <td style="padding: 10px;">'.$row1['approve_by'].' </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Container number</strong></td>
                    <td colspan="2" style="padding: 10px;">'.$row1['containers'].'  of '.$row1['containers'].' </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Format No.</strong></td>
                    <td colspan="2" style="padding: 10px;">SOP/QC/015-F01</td>
                </tr>
            </table>
        </td>

        <!-- Spacer between labels -->
        <td style="width:2%;"></td>

        <!-- Second Label Section -->
        <td style="width:49%;">
            <table cellpadding="2" border="1" style="width: 100%;">
                <tr>
                    <th rowspan="3" style="width: 63px; text-align: left;">
                        <img src="../../../upload/pdf/zuma.jpg" style="width:111px;height:50px;">
                    </th>
                    <th colspan="3" style="text-align: center; padding: 10px; background-color: #00CF00;">ZUMA PHARMA LLC <br> Parkent, Tashkent, Uzbekistan</th>
                </tr>
                <tr>
                    <th colspan="3" style="text-align: center; padding: 10px; background-color: #00CF00;">QUALITY CONTROL DEPARTMENT</th>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 10px; font-weight: bold; background-color: #00CF00;">APPROVED</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Material name</strong></td>
                    <td colspan="2" style="padding: 10px;">'.$row1['material_name'].' </td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Item Code</strong></td>
                    <td style="padding: 10px;"></td>
                    <td style="padding: 10px;"><strong>A.R. No</strong></td>
                    <td style="padding: 10px;"></td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Mfg. date</strong></td>
                    <td style="padding: 10px;"></td>
                    <td style="padding: 10px;"><strong>Expiry date</strong></td>
                    <td style="padding: 10px;"></td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Retest Date</strong></td>
                    <td style="padding: 10px;"></td>
                    <td style="padding: 10px;"><strong>Approved By</strong></td>
                    <td style="padding: 10px;"></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Container number</strong></td>
                    <td colspan="2" style="padding: 10px;">_____ of _____</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Format No.</strong></td>
                    <td colspan="2" style="padding: 10px;">SOP/QC/015-F01</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

';

}
                    $i++;
                }
            }
             $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }else{
            echo 'Invalid id';
        }

              
          }
 else if  ($_GET["plant_id"] == 177 || $_GET["plant_id"] == 149 )  {
               //  $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess') AND id='".$_GET['id']."'";
         $sql = "SELECT * FROM sampling WHERE   id='".$_GET['id']."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                 $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row1['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row1['gradeName'] = $prodLatest['gradeName'];
                 $sq0 = "SELECT vendor_name FROM vendor  WHERE vendor_no = '".$row['supplier_no']."'";
         $result0 = $conn->query($sq0);
        if ($result0->num_rows > 0) {
            while ($row0 = $result0->fetch_assoc()) {
                $supplier = $row0['vendor_name'];
            }
        }  
                class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 20);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                
                
                // $dynamicQRData = 'https://example.com/dynamic_qr_data?id=' . $row['id']; 
            //    $dynamicQRData = "Material Name : ".$row1['material_name'] . PHP_EOL . "Pharmacopeial Grade : " . $row1['grade'] . PHP_EOL .
             //   "A R No. : ".$row['ar_no'] . PHP_EOL . " Release Date : " . $row['approve_date'] . PHP_EOL . " Status : " . $row['status']  ;
            //    $qrCodeURL = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($dynamicQRData);
//$qrCodeURL = 'https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=' . urlencode($dynamicQRData) . '&chld=L|0&choe=UTF-8&chf=bg,s,FFFFFF00';

                
                for ($i=1; $i <= $row['containers']; $i++) {
                    if($i == $row['containers'] && $i % 2 !== 0){
                        $html.='
                           <table cellpadding="0" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                            <table style="border: 1px solid black; background-color: rgb(57, 157, 57);">
                           <tr> 
           <td style="width: 40px; text-align: left; margin-top:18px;"> <img src="../../../gmptotal/logos/nootannnn.png" style="width:130px;height:80px;"> </td>
           <td style="width: 210px; font-weight: bold;font-size: 12px; text-align: Center;"><br>APPROVED<br>NOOTAN PHARMACEATICAL<br>QUALITY CONTROL DEPARTMENT<br> (Raw Material) </td>
        </tr>
                            </table>
                                
                          <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Name of Raw Material</td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['material_name'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Batch No. </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['batch_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Mfg No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['manufacturer_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Exp Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  ' .date('d-m-Y', strtotime($row['exp_date'])). '  </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Mfg By  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  ' .date('d-m-Y', strtotime($row['mfg_date'])). '</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Supplied By   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$supplier.' </td>
                                </tr>
                                 <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Qty Received  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['received_qty'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> GRN No. </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['grn_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> A R No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['ar_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Assay (as such)  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Qty Accepted  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Retest Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$row1['retest'] . ' </td>
                                </tr>        
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Sign of Chemist   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"></td>
                                </tr>        
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['approve_date'].'  </td>
                                </tr>        
                            </table>
                            <br>
 
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;"> ';
                                   }else{
                    $html.='
                    <div></div>&nbsp;
                        <table cellpadding="0" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                            <tr> 
           <td style="width: 40px; text-align: left; margin-top:18px;"> <img src="../../../gmptotal/logos/nootannnn.png" style="width:130px;height:80px;"> </td>
           <td style="width: 210px; font-weight: bold;font-size: 12px; text-align: Center;"><br>APPROVED<br>NOOTAN PHARMACEATICAL<br>QUALITY CONTROL DEPARTMENT<br> (Raw Material) </td>
        </tr>
                               
                            </table>
                                
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Name of Raw Material</td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['material_name'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Batch No. </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['batch_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Mfg No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['manufacturer_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Exp Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  ' .date('d-m-Y', strtotime($row['exp_date'])). '  </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Mfg By  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  ' .date('d-m-Y', strtotime($row['mfg_date'])). '</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Supplied By   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$supplier.' </td>
                                </tr>
                                 <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Qty Received  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['received_qty'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> GRN No. </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['grn_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> A R No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['ar_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Assay (as such)  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Qty Accepted  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Retest Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$row1['retest'] . ' </td>
                                </tr>        
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Sign of Chemist   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"></td>
                                </tr>        
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['approve_date'].'  </td>
                                </tr>        
                            </table>
                            <br>
 
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;">
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                            <tr> 
           <td style="width: 40px; text-align: left; margin-top:18px;"> <img src="../../../gmptotal/logos/nootannnn.png" style="width:130px;height:80px;"> </td>
           <td style="width: 210px; font-weight: bold;font-size: 12px; text-align: Center;"><br>APPROVED<br>NOOTAN PHARMACEATICAL<br>QUALITY CONTROL DEPARTMENT<br> (Raw Material) </td>
        </tr>
                        </table>
                            
                         <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Name of Raw Material</td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['material_name'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Batch No. </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['batch_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Mfg No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['manufacturer_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Exp Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  ' .date('d-m-Y', strtotime($row['exp_date'])). '  </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Mfg By  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  ' .date('d-m-Y', strtotime($row['mfg_date'])). '</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Supplied By   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$supplier.' </td>
                                </tr>
                                 <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Qty Received  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['received_qty'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> GRN No. </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['grn_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> A R No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['ar_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Assay (as such)  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Qty Accepted  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Retest Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$row1['retest'] . ' </td>
                                </tr>        
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Sign of Chemist   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"></td>
                                </tr>        
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['approve_date'].'  </td>
                                </tr>        
                            </table>
                                </td>
                            </tr>
                        </table>';
                    }
                    $i++;
                }
            }
             $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }else{
            echo 'Invalid id';
        }
        }
        else{
               //  $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess') AND id='".$_GET['id']."'";
         $sql = "SELECT * FROM sampling WHERE   id='".$_GET['id']."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                 $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row1['grade']."')";
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row1['gradeName'] = $prodLatest['gradeName'];
                
                class MYPDF extends TCPDF {
                    public function Header() {}
                    public function Footer() {}
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(10, 10, 10, 10);
                $pdf->SetAutoPageBreak(TRUE, 10);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                
                
                // $dynamicQRData = 'https://example.com/dynamic_qr_data?id=' . $row['id']; 
            //    $dynamicQRData = "Material Name : ".$row1['material_name'] . PHP_EOL . "Pharmacopeial Grade : " . $row1['grade'] . PHP_EOL .
             //   "A R No. : ".$row['ar_no'] . PHP_EOL . " Release Date : " . $row['approve_date'] . PHP_EOL . " Status : " . $row['status']  ;
            //    $qrCodeURL = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($dynamicQRData);
//$qrCodeURL = 'https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=' . urlencode($dynamicQRData) . '&chld=L|0&choe=UTF-8&chf=bg,s,FFFFFF00';

                
                for ($i=1; $i <= $row['containers']; $i++) {
                    if($i == $row['containers'] && $i % 2 !== 0){
                        $html.='
                           <table cellpadding="0" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                            <table style="border: 1px solid black; background-color: rgb(57, 157, 57);">
                            <tr>
                          <td style="width: 70px; text-align: left;"><img src="../../../upload/pdf/novo2.png" style="width:111px;height:50px;"> </td>
                                <td style="width: 180px; font-weight: bold;font-size: 20px; text-align: center;">APPROVED </td>
                            </tr>
                                <tr>
                                    <td style="width: 250px; font-size: 9px;  text-align: center;">Label No. : L/SOP/QC/006/02-03 </td>
                                </tr>
                            </table>
                                
                            <table style="border: 1px solid black; background-color: rgb(57, 157, 57);">
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Material Name  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['material_name'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Pharmacopeial Grade </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['gradeName'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> A R No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['ar_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Release Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> ' .date('d-m-Y', strtotime($row['approve_date'])). ' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Retest Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['release_date'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Sign/ Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$row1['status'] . ' ' . date('d-m-Y').' </td>
                                </tr>
                                         
                            </table>
                            <br>
 
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;"> ';
                                   }else{
                    $html.='
                    <div></div>&nbsp;
                        <table cellpadding="0" style="width:100%;">
                            <tr>
                                <td style="width:49%;">
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                            <tr>
                          <td style="width: 70px; text-align: left;"><img src="../../../upload/pdf/novo2.png" style="width:111px;height:50px;"> </td>
                                <td style="width: 180px; font-weight: bold;font-size: 20px; text-align: center;">APPROVED </td>
                            </tr>
                                <tr>
                                    <td style="width: 250px; font-size: 9px;  text-align: center;">Label No. : L/SOP/QC/006/02-03 </td>
                                </tr>
                            </table>
                                
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Material Name  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['material_name'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Pharmacopeial Grade </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['gradeName'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> A R No.  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['ar_no'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Release Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> ' .date('d-m-Y', strtotime($row['approve_date'])). ' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Retest Date  </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['release_date'].' </td>
                                </tr>
                                <tr>
                                    <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Sign/ Date   </td>
                                    <td style="width:165px; height: 20px; font-size: 10px;">  '.$row1['status'] . ' ' . date('d-m-Y').' </td>
                                </tr>
                                         
                            </table>
                            <br>
 
                                </td>
                                <td style="width:2%;"></td>
                                <td style="width:49%;">
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                            <tr>
                          <td style="width: 70px; text-align: left;"><img src="../../../upload/pdf/novo2.png" style="width:111px;height:50px;"> </td>
                                <td style="width: 180px; font-weight: bold;font-size: 20px; text-align: center;">APPROVED </td>
                            </tr>
                            <tr>
                            
                                <td style="width: 250px; font-size: 9px;  text-align: center;">Label No. : L/SOP/QC/006/02-03 </td>
                            </tr>
                        </table>
                            
                        <table style="border: 0px solid black; background-color: rgb(57, 157, 57);">
                            <tr>
                                <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Material Name  </td>
                                <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['material_name'].' </td>
                            </tr>
                            <tr>
                                <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Pharmacopeial Grade </td>
                                <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['gradeName'].'</td>
                            </tr>
                            <tr>
                                <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> A R No.  </td>
                                <td style="width:165px; height: 20px; font-size: 10px;"> '.$row['ar_no'].' </td>
                            </tr>
                            <tr>
                                <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Release Date  </td>
                                <td style="width:165px; height: 20px; font-size: 10px;"> ' .date('d-m-Y', strtotime($row['approve_date'])). '</td>
                            </tr>
                            <tr>
                                <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Retest Date  </td>
                                <td style="width:165px; height: 20px; font-size: 10px;"> '.$row1['release_date'].' </td>
                            </tr>
                            <tr>
                                <td style="width:90px; height: 20px; font-size: 10px; border-right: 1px solid black;"> Sign/ Date   </td>
                                <td style="width:165px; height: 20px; font-size: 10px;">  '.$row1['status'] . ' ' . date('d-m-Y').'</td>
                            </tr>
                                    
                        </table>
 
                                </td>
                            </tr>
                        </table>';
                    }
                    $i++;
                }
            }
             $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }else{
            echo 'Invalid id';
        }
        }
     
    }
 
} else {
    echo "[]";
}
?>