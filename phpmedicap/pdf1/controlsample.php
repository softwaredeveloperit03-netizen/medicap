<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';


// ini_set('display_errors', 1);
// error_reporting(E_ALL);


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
    
 if ($_GET["type"] == "expiredsamplelog") {
        $_GET['filename'] = 'expired sample log'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        $html.='
           <h3 style="text-align:center">Expired Sample Log</h3>
        <table cellpadding="5" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample ID</b></td>
                <td style="width:15%; text-align:centre;"><b>Name of Product</b></td>
                <td style="width:10%; text-align:centre;"><b>Medicap lot</b></td>
                <td style="width:15%; text-align:centre;"><b>MFG Date</b></td>
                <td style="width:10%; text-align:centre;"><b>EXP Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Avl Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Expired</b></td>
            </tr>';
            $i=1;
          //   $sql = "SELECT * FROM control_sample ";
            $sql = " SELECT * FROM control_sample a left join material b on a.material_code=b.material_code  where a.material_type = 'Raw Material'";
    //  if($_GET["material_type"] == 'Finish Product'){
    //   $sql = "SELECT a.*,b.product_name as material_name FROM control_sample a left join product b on a.material_code=b.product_code  where a.material_type = '".$_GET["material_type"]."'";

    // }else{
        
    //  $sql = "SELECT * FROM control_sample a left join material b on a.material_code=b.material_code  where a.material_type = '".$_GET["material_type"]."'";

    // }
    $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['cs_id'].'</td>
                <td style="width:15%;">'.$row['material_name'].'</td>
                <td style="width:10%;">'.$row['batch_no'].'</td>
                <td style="width:15%;">'.date('m-Y',strtotime($row['mfg_date'])).'</td>
                <td style="width:10%;">'.date('m-Y',strtotime($row['exp_date'])).'</td>
                <td style="width:10%;">'.$row['sample_quantity'].'</td>
                <td style="width:10%;">'.$row['sample_quantity'].'</td>
                <td style="width:10%;">'.$row['expired'].'</td>
            </tr>';
            $i++;
        }
    }
        $html.='</table>';
        
        
        
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('expiredsamplelog.pdf', 'I');
    }
 else if ($_GET["type"] == "withdrawallog") {
        $_GET['filename'] = 'expired sample log'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample ID</b></td>
                <td style="width:15%; text-align:centre;"><b>Name of Product</b></td>
                <td style="width:10%; text-align:centre;"><b>Medicap lot</b></td>
                <td style="width:15%; text-align:centre;"><b>MFG Date</b></td>
                <td style="width:10%; text-align:centre;"><b>EXP Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Avl Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Expired</b></td>
            </tr>';
            $i=1;
  $sql = "SELECT * FROM sample_withdrawal";
  $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['cs_id'].'</td>
                <td style="width:15%;">'.$row['product_name'].'</td>
                <td style="width:10%;">'.$row['batch_no'].'</td>
                <td style="width:15%;">'.date('m-Y',strtotime($row['mfg_date'])).'</td>
                <td style="width:10%;">'.date('m-Y',strtotime($row['exp_date'])).'</td>
                <td style="width:10%;">'.$row['sample_quantity'].'</td>
                <td style="width:10%;">'.$row['sample_quantity'].'</td>
                <td style="width:10%;">'.$row['expired'].'</td>
            </tr>';
            $i++;
        }
    }
        $html.='</table>';
        
        
        
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('expiredsamplelog.pdf', 'I');
    }
 else if ($_GET["type"] == "controlsamplelog") {
        $_GET['filename'] = 'Control Sample Log Book';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $plantId = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $materialType = $conn->real_escape_string($_GET["material_type"] ?? 'Raw Material');
        $fromDate = $conn->real_escape_string($_GET["fromdate"] ?? $_GET["from_date"] ?? '');
        $toDate = $conn->real_escape_string($_GET["todate"] ?? $_GET["to_date"] ?? '');

        $html = '<h3 style="text-align:center;">Control Sample Log Book</h3>';
        $html .= '<p style="text-align:center;">Material Type: '.htmlspecialchars($materialType, ENT_QUOTES, 'UTF-8');
        if ($fromDate !== '' && $toDate !== '') {
            $html .= ' | Period: '.htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8').' to '.htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8');
        }
        $html .= '</p>';
        $html .= '<table cellpadding="4" border="1" cellspacing="0">
            <tr style="background-color:#DDDAD9;font-weight:bold;">
                <td style="width:4%;"><b>Sr</b></td>
                <td style="width:10%;"><b>Material Type</b></td>
                <td style="width:10%;"><b>Material Code</b></td>
                <td style="width:12%;"><b>Material Name</b></td>
                <td style="width:10%;"><b>Medicap lot no</b></td>
                <td style="width:6%;"><b>Grade</b></td>
                <td style="width:9%;"><b>Analysis Date</b></td>
                <td style="width:9%;"><b>Release Date</b></td>
                <td style="width:8%;"><b>Sample Qty</b></td>
                <td style="width:8%;"><b>Actual CS</b></td>
                <td style="width:6%;"><b>Rack No</b></td>
                <td style="width:8%;"><b>Checked By</b></td>
                <td style="width:8%;"><b>Status</b></td>
            </tr>';

        $sql = "SELECT * FROM control_sample WHERE plant_id='".$plantId."' AND material_type='".$materialType."' AND status='approve'";
        if ($fromDate !== '' && $toDate !== '') {
            $sql .= " AND DATE(entry_date) BETWEEN '".$fromDate."' AND '".$toDate."'";
        }
        $sql .= " ORDER BY id DESC";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $materialName = $row['material_name'] ?? '';
                if ($materialName === '') {
                    if ($row['material_type'] === 'Finished Product') {
                        $sql1 = "SELECT product_name FROM product WHERE product_code='".$conn->real_escape_string($row['material_code'])."' LIMIT 1";
                        $res1 = $conn->query($sql1);
                        if ($res1 && $res1->num_rows > 0) {
                            $materialName = $res1->fetch_assoc()['product_name'];
                        }
                    } else {
                        $sql1 = "SELECT material_name, grade FROM material WHERE material_code='".$conn->real_escape_string($row['material_code'])."' LIMIT 1";
                        $res1 = $conn->query($sql1);
                        if ($res1 && $res1->num_rows > 0) {
                            $mat = $res1->fetch_assoc();
                            $materialName = $mat['material_name'];
                            if (empty($row['grade'])) {
                                $row['grade'] = $mat['grade'];
                            }
                        }
                    }
                }

                $html .= '<tr>
                    <td>'.$i.'.</td>
                    <td>'.htmlspecialchars($row['material_type'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['material_code'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($materialName ?: '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['batch_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['grade'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['analysis_date'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['release_date'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['sample_quantity'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['actual_control_sample'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['rack_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['checked_by'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="13" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ControlSampleLogBook.pdf', 'I');
    }
 else if ($_GET["type"] == "distructionlog") {
        $_GET['filename'] = 'Control Sample Destruction Log';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $plantId = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $html = '<h3 style="text-align:center;">Control Sample Destruction Log</h3>';
        $html .= '<table cellpadding="4" border="1" cellspacing="0">
            <tr style="background-color:#DDDAD9;font-weight:bold;">
                <td style="width:4%;"><b>Sr</b></td>
                <td style="width:8%;"><b>Destruction ID</b></td>
                <td style="width:8%;"><b>Control Sample ID</b></td>
                <td style="width:14%;"><b>Product Name</b></td>
                <td style="width:10%;"><b>Medicap Lot No</b></td>
                <td style="width:7%;"><b>Qty</b></td>
                <td style="width:10%;"><b>Department</b></td>
                <td style="width:12%;"><b>Destroyed By</b></td>
                <td style="width:10%;"><b>Equipment</b></td>
                <td style="width:10%;"><b>Location</b></td>
                <td style="width:7%;"><b>Status</b></td>
            </tr>';

        $sql = "SELECT * FROM control_sample_distruction WHERE plant_id='".$plantId."' ORDER BY id DESC";
        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>
                    <td>'.$i.'.</td>
                    <td>'.htmlspecialchars($row['distruction_id'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['cs_id'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['product_name'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['batch_no'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['quantity'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['department_name'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['request_by_employee'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['equipment'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['distruction_loc'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars($row['action'] ?? '-', ENT_QUOTES, 'UTF-8').'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="11" style="text-align:center;">No records found</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ControlSampleDestructionLog.pdf', 'I');
    }




}

else{
    echo "Invalid Token";
}
?>