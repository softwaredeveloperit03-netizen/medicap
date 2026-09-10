<?php
 
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata"); 
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
 
function buildQuarantineReceivingLabelSheet($item)
{
    $esc = function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };
    $rowCell = 'font-family:helvetica;font-size:8px;font-weight:bold;padding:3px 6px;line-height:1.25;border-bottom:1px solid #000;';
    $lastCell = 'font-family:helvetica;font-size:8px;font-weight:bold;padding:3px 6px;line-height:1.25;';

    $dataTable = '
<table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #000;">
<tr><td style="'.$rowCell.'text-align:center;font-size:9px;"> '.$esc($item['plantName']).'</td></tr>
<tr><td style="'.$rowCell.'"> PRODUCT BRIEF DESCRIPTION: '.$esc($item['productDesc']).'</td></tr>
<tr><td style="'.$rowCell.'"> MFG LOT: '.$esc($item['mfgLot']).'</td></tr>
<tr><td style="'.$rowCell.'"> MANUFACTURER: '.$esc($item['manufacturer']).'</td></tr>
<tr><td style="'.$rowCell.'"> SUPPLIER: '.$esc($item['supplier']).'</td></tr>
<tr><td style="'.$rowCell.'"> CODE #: '.$esc($item['materialCode']).'</td></tr>
<tr><td style="'.$rowCell.'"> Medicap lot no.: '.$esc($item['lotNo']).'</td></tr>
<tr><td style="'.$rowCell.'"> Retest/Expiry Date: '.$esc($item['expDate']).'</td></tr>
<tr><td style="'.$rowCell.'"> Container: '.$item['containerNo'].' of '.$item['containerTotal'].'</td></tr>
<tr><td style="'.$rowCell.'"> Received By: '.$esc($item['receivedBy']).'</td></tr>
<tr><td style="'.$rowCell.'"> Received Date: '.$esc($item['receivedDate']).'</td></tr>
<tr><td style="'.$lastCell.'"> Total Quantity Rec\'d: '.$esc($item['totalQty']).'</td></tr>
</table>';

    $quarantineBox = '<br><br><br><br><br>
<table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #000;">
<tr>
<td style="background-color:#ffb6c1;font-family:helvetica;font-size:10px;font-weight:bold;text-align:center;vertical-align:middle;padding:4px;height:22px;">QUARANTINED</td>
</tr>
</table>';

    return '
<table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
<tr>
<td width="52%" valign="top" style="padding:0;border:none;">'.$dataTable.'</td>
<td width="16%" valign="middle" style="padding:0;border:none;">&nbsp;</td>
<td width="26%" valign="top" style="padding:32px 0 0 0;border:none;">'.$quarantineBox.'</td>
</tr>
</table>';
}

function sr_fmt_label_date($value) {
    if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date('d-m-Y', $ts) : (string)$value;
}

function sr_resolve_employee_name($conn, $row) {
    $fromJoin = array('sampled_by_name', 'sampling_person_name', 'sampleByName', 'samplngPersonName');
    foreach ($fromJoin as $key) {
        if (!empty($row[$key])) {
            $name = trim(preg_replace('/\s+/', ' ', (string)$row[$key]));
            if ($name !== '' && !preg_match('/^\d+$/', $name)) {
                return $name;
            }
        }
    }
    $ids = array();
    foreach (array('sampledBy', 'sampling_person') as $key) {
        if (!empty($row[$key])) {
            $ids[] = trim((string)$row[$key]);
        }
    }
    foreach ($ids as $id) {
        if ($id === '') {
            continue;
        }
        if (!preg_match('/^\d+$/', $id) && preg_match('/[A-Za-z]/', $id)) {
            return $id;
        }
        $esc = $conn->real_escape_string($id);
        $q = @$conn->query("SELECT TRIM(CONCAT(IFNULL(firstname,''), ' ', IFNULL(lastname,''))) AS nm FROM employee WHERE emp_id = '".$esc."' OR CAST(emp_id AS CHAR) = '".$esc."' LIMIT 1");
        if ($q && ($er = $q->fetch_assoc())) {
            $name = trim(preg_replace('/\s+/', ' ', (string)$er['nm']));
            if ($name !== '') {
                return $name;
            }
        }
    }
    return isset($row['sampledBy']) ? trim((string)$row['sampledBy']) : '';
}

function buildSampledByQcLabelSheet($item) {
    $esc = function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };
    $name = $esc(isset($item['employeeName']) ? $item['employeeName'] : '');
    $date = $esc(isset($item['sampledOn']) ? $item['sampledOn'] : '');
    return '
<table cellpadding="0" cellspacing="0" border="1" style="width:270px; border:1px solid #000;">
<tr>
<td style="border:1px solid #000; padding:6px 8px 8px 8px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
<tr>
<td align="center" style="font-family:helvetica; font-size:13px; font-weight:bold; padding-bottom:6px;">Sampled By QC</td>
</tr>
<tr>
<td style="font-family:helvetica; font-size:11px; text-align:left;">Employee Name: <b>'.$name.'</b></td>
</tr>
<tr>
<td style="font-family:helvetica; font-size:11px; text-align:left;">Date: <b>'.$date.'</b></td>
</tr>
</table>
</td>
</tr>
</table>';
}

function sr_qc_pdf_date($value) {
    if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime($value);
    return $ts ? date('d-m-Y', $ts) : (string)$value;
}

function sr_qc_sampling_pdf_logo_html($logo) {
    $var = (!empty($logo))
        ? 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/'.$logo
        : '';
    if ($var === '') {
        return '&nbsp;';
    }
    $esc = htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
    return '<img src="'.$esc.'" border="0" style="max-width:52mm; max-height:14mm; width:auto; height:auto;" />';
}

function sr_qc_sampling_pdf_header($logoHtml, $plantName, $plantAddress) {
    return '
            <table cellpadding="0" cellspacing="0" border="1" width="100%" style="border-collapse:collapse; font-family:dejavusans;">
                <tr>
                    <td width="24%" align="center" valign="middle" style="height:16mm; padding:2px 4px; line-height:1;">'.$logoHtml.'</td>
                    <td width="76%" align="center" valign="middle" style="padding:4px 6px;">
                        <span style="font-size:12px; font-weight:bold;">'.$plantName.'</span><br>
                        <span style="font-size:8px;">'.$plantAddress.'</span>
                    </td>
                </tr>
            </table>';
}

function sr_qc_sampling_pdf_begin($title) {
    $pdf = new TCPDF('L', 'mm', 'A4');
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Medicap Laboratories');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetFont('dejavusans', '', 8);
    $pdf->SetMargins(8, 8, 8);
    $pdf->SetAutoPageBreak(true, 8);
    $pdf->AddPage();
    return $pdf;
}

function sr_qc_sampling_pdf_title($title) {
    return '<br /><div style="text-align:center; font-size:10px; font-weight:bold; font-family:dejavusans;">'.$title.'</div><br />';
}

//   ini_set('display_errors', 1);
// error_reporting(E_ALL);
 
 
    try{
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
    
     if ($_GET["type"] == "getBatchesForGrnReceiving") {
         $output = Array();
         $materialType = isset($_GET["material_type"]) ? $_GET["material_type"] : "Raw Material";
         $materialTypeFilter = "";
         if ($materialType !== "") {
             $materialTypeFilter = " AND COALESCE(m.material_type, mv.material_type) = '".$materialType."'";
         }

        $sql = "SELECT a.id, a.plant_id, a.trackingId, a.material_code, a.batch_no, a.ar_no, a.grn_no, a.sampled_container, a.status, a.mfg_date, a.exp_date, a.mfg_by, a.pack_size, a.total_containers, a.qty_received,a.grn_receive_remark, 
        a.grnReceiveBy,a.grnReceiveOn,a.container_no, a.challan_no, a.ch_no, a.root_container, a.unit, a.coa_received, a.coa_files,
        COALESCE(m.material_type, mv.material_type) as material_type, COALESCE(m.grade, mv.grade) as grade, COALESCE(m.material_name, mv.material_name) as material_name,
        (select c.receiving_no from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as receiving_no,
        (select c.grn_date from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_date,
        (select c1.po_no from challan c1 where c1.challan_no = a.challan_no  limit 1) as po_no
        FROM sampling_batches a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        LEFT JOIN my_view mv ON a.material_code = mv.material_code AND mv.plant_id = a.plant_id
        WHERE a.plant_id = '".$_GET["plant_id"]."'
        AND UPPER(TRIM(a.status)) IN ('APPROVED', 'APPROVE')
        AND (a.isGrnReceive IS NULL OR a.isGrnReceive = '' OR a.isGrnReceive = 'NO')
        AND a.grn_no IS NOT NULL AND TRIM(a.grn_no) != ''
        AND a.ar_no IS NOT NULL AND TRIM(a.ar_no) != ''".$materialTypeFilter."
        ORDER BY a.id desc ";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "gerRejectedGrnReceivingLog") {
         $output = Array();
  
        
        $sql = "SELECT a.id, a.plant_id, a.trackingId, a.material_code, a.batch_no, a.ar_no, a.grn_no, a.sampled_container, a.status, a.mfg_date, a.exp_date, a.mfg_by, a.pack_size, a.total_containers, a.qty_received,a.grn_receive_remark, 
        a.grnReceiveBy,a.grnReceiveOn,a.container_no, a.challan_no, a.ch_no, a.root_container, a.unit, a.coa_received, a.coa_files, m.material_type, m.grade, m.material_name, m.grade,
        (select c.receiving_no from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as receiving_no,
        (select c1.po_no from challan c1 where c1.challan_no = a.challan_no  limit 1) as po_no
        FROM sampling_batches a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Rejected' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.id desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "gerOnHoldGrnReceivingLog") {
         $output = Array();
  
        
        $sql = "SELECT a.id, a.plant_id, a.trackingId, a.material_code, a.batch_no, a.ar_no, a.grn_no, a.sampled_container, a.status, a.mfg_date, a.exp_date, a.mfg_by, a.pack_size, a.total_containers, a.qty_received,a.grn_receive_remark, 
        a.grnReceiveBy,a.grnReceiveOn,a.container_no, a.challan_no, a.ch_no, a.root_container, a.unit, a.coa_received, a.coa_files, m.material_type, m.grade, m.material_name, m.grade,
        (select c.receiving_no from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as receiving_no,
        (select c.grn_date from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_date,
        (select c1.po_no from challan c1 where c1.challan_no = a.challan_no  limit 1) as po_no
        FROM sampling_batches a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'ON_HOLD' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.id desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "gerGrnReceivingLog") {
         $output = Array();
  
         
        $sql = "SELECT a.id, a.plant_id, a.trackingId, a.material_code, a.batch_no, a.ar_no, a.grn_no, a.sampled_container, a.status, a.mfg_date, a.exp_date, a.mfg_by, a.pack_size, a.total_containers, a.qty_received,a.grn_receive_remark, 
        a.grnReceiveBy,a.grnReceiveOn,a.container_no, a.challan_no, a.ch_no, a.root_container, a.unit, a.coa_received, a.coa_files, m.material_type, m.grade, m.material_name, m.grade,
        (select c.receiving_no from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as receiving_no,
        (select c1.po_no from challan c1 where c1.challan_no = a.challan_no  limit 1) as po_no
        FROM sampling_batches a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND isGrnReceive = 'YES' AND a.status != 'OPENING' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.id desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingAllocation") {
         $output = Array();
  
         
        $sql = "SELECT a.id, a.plant_id, a.sampling_no, a.material_code, a.batch_no, a.containers, a.grn_no, a.challan_no, a.ch_no, a.po_no, a.ar_no, a.grn_date, a.mfg_date, a.exp_date, a.received_qty, 
        a.received_unit, a.manufacturer_no, a.supplier_no, a.status, a.entry_by, a.entry_date, m.material_type, m.grade, m.material_name, m.grade,
        (select s.grnReceiveBy from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveBy,
        (select s.grnReceiveOn from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveOn,
        (select c.grn_by from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_by
        FROM sampling a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Pending' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.id desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['check'] = false;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSamplingAllocationLog") {
         $output = Array();
  
         
        $sql = "SELECT a.id, a.plant_id, a.sampling_no, a.material_code, a.batch_no, a.containers, a.grn_no, a.challan_no, a.ch_no, a.po_no, a.ar_no, a.grn_date, a.mfg_date, a.exp_date, a.received_qty, 
        a.received_unit, a.manufacturer_no, a.supplier_no, a.status, a.entry_by, a.entry_date,a.entry_date,a.alloocationBy,a.alloocationOn, m.material_type, m.grade, m.material_name, m.grade,
        (select s.grnReceiveBy from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveBy,
        (select s.grnReceiveOn from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveOn,
        (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) AS sampling_personName from  employee e where e.emp_id = a.sampling_person limit 1) as sampling_personName,
        (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) AS micro_personName from  employee e where e.emp_id = a.micro_person limit 1) as micro_personName,
        (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) AS alternate_qc_personName from  employee e where e.emp_id = a.alternate_qc_person limit 1) as alternate_qc_personName,
        (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) AS alternate_micro_personName from  employee e where e.emp_id = a.alternate_micro_person limit 1) as alternate_micro_personName,
        (select c.grn_by from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_by
        FROM sampling a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status != 'Pending' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.alloocationOn desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['check'] = false;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingSamplingDataForAreaCleaning") {
         $output = Array();
  
         
        $sql = "SELECT a.id, a.plant_id, a.sampling_no, a.material_code, a.batch_no, a.containers, a.grn_no, a.challan_no, a.ch_no, a.po_no, a.ar_no, a.grn_date, a.mfg_date, a.exp_date, a.received_qty, 
        a.received_unit, a.manufacturer_no, a.supplier_no, a.status, a.entry_by, a.entry_date,a.entry_date,a.alloocationBy,a.alloocationOn, m.material_type, m.grade, m.material_name, m.grade,
        (select s.grnReceiveBy from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveBy,
        (select s.grnReceiveOn from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveOn,
        (select c.grn_by from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_by
        FROM sampling a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Allocated' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.alloocationOn desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['check'] = false;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingSamplingDataForBalanceCleaning") {
        
        $output = Array();
        $sql = "SELECT a.id, a.plant_id, a.sampling_no, a.material_code, a.batch_no, a.containers, a.grn_no, a.challan_no, a.ch_no, a.po_no, a.ar_no, a.grn_date, a.mfg_date, a.exp_date, a.received_qty, 
        a.received_unit, a.manufacturer_no, a.supplier_no, a.status, a.entry_by, a.entry_date,a.entry_date,a.alloocationBy,a.alloocationOn, a.cleaningAgentsUsed, a.areaCleaningChecklist, m.material_type, m.grade, m.material_name, m.grade,
        (select s.grnReceiveBy from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveBy,
        (select s.grnReceiveOn from  sampling_batches  s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveOn,
        (select c.grn_by from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_by
        FROM sampling a 
        LEFT JOIN material m ON a.material_code=m.material_code 
        WHERE a.plant_id = '".$_GET["plant_id"]."' AND a.status = 'Area_Cleaning_Done' AND  m.material_type = '".$_GET["material_type"]."' ORDER BY a.alloocationOn desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['check'] = false;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getAreaCleaningDetailsForBalance") {
        $output = array('cleaningAgentsUsed' => null, 'areaCleaningChecklist' => null);
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $plant = $conn->real_escape_string($_GET['plant_id'] ?? '');
            $sql = "SELECT cleaningAgentsUsed, areaCleaningChecklist FROM sampling WHERE id = '".$id."' AND plant_id = '".$plant."' LIMIT 1";
            $result = @$conn->query($sql);
            if (!$result) {
                $sql = "SELECT areaCleaningChecklist FROM sampling WHERE id = '".$id."' AND plant_id = '".$plant."' LIMIT 1";
                $result = $conn->query($sql);
            }
            if ($result && $result->num_rows > 0) {
                $output = $result->fetch_assoc();
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLastBatchNoForArea") {
        
        $output = Array();
        
        $sql1 = "SELECT material_code as priviousMaterial,batch_no as priviousBatchNo,cleaningDoneBy as priviousdone_by,cleaningDate as priviouscleanDate FROM sampling WHERE plant_id = '".$_GET["plant_id"]."' 
        ORDER BY cleaningEntryOn desc limit 1";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output = $row1;
            }
        }
       
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLastBatchNoForweighing") {
        
        $output = Array();
        
        $sql1 = "SELECT material_code as priviousMaterial,batch_no as priviousBatchNo,weighBalCleanDoneBy as priviousdone_by,weighBalCleanDate as priviouscleanDate FROM sampling WHERE plant_id = '".$_GET["plant_id"]."' 
        ORDER BY wbCheanEntryOn desc limit 1";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output = $row1;
            }
        }
       
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLastBatchNoForlaf") {
        
        $output = Array();
        
        $sql1 = "SELECT material_code as priviousMaterial,batch_no as priviousBatchNo,cleaningDoneBy as priviousdone_by,cleaningDate as priviouscleanDate FROM sampling WHERE plant_id = '".$_GET["plant_id"]."' 
        ORDER BY sampledOn desc limit 1";
        
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output = $row1;
            }
        }
       
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "downloadLafLog") {
        $_GET['filename'] = 'Packing Material Approved Stock Book'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
          $html= "";
    		      $html.='
        <h2 style="text-align:center">OPERATION & CLEANING LOG FOR SAMPLING AND DISPENSING ROOM</h2>
        <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:7%; text-align:centre;"><b>Challan For</b></td>
                <td style="width:7%; text-align:centre;"><b>GRN No.</b></td>
                <td style="width:5%; text-align:centre;"><b>Medicap lot no</b></td>
                <td style="width:8%; text-align:centre;"><b>Vendor Name</b></td>
                <td style="width:8%; text-align:centre;"><b>Material Type</b></td>
                <td style="width:8%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:8%; text-align:centre;"><b>Material Name</b></td>
                <td style="width:5%; text-align:centre;"><b>Grade</b></td>
                <td style="width:7%; text-align:centre;"><b>Batch No.</b></td>
                <td style="width:8%; text-align:centre;"><b>Sampling Date</b></td>
                <td style="width:8%; text-align:centre;"><b>Release Date</b></td>
                <td style="width:8%; text-align:centre;"><b>Received Qty</b></td>
                <td style="width:8%; text-align:centre;"><b>Balance Qty</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                <td style="width:5%;">'.$i.'.</td>
                <td style="width:7%;">'.$row['required_for'].'</td>
                <td style="width:7%;">'.$row['grn_no'].'</td>
                <td style="width:5%;">'.$row['ar_no'].'</td>
                <td style="width:8%;">'.$row['vendor_name'].'</td>
                <td style="width:8%;">'.$row['material_subtype'].'</td>
                <td style="width:8%;">'.$row['material_type'].'</td>
                <td style="width:8%;">'.$row['material_name'].'</td>
                <td style="width:5%;">'.$row['grade'].'</td>
                <td style="width:7%;">'.$row['batch_no'].'</td>
                <td style="width:8%;">'.$row['sample_date'].'</td>
                <td style="width:8%;">'.$row['release_date'].'</td>
                <td style="width:8%;">'.$row['qty'].'</td>
                <td style="width:8%;">'.$row['qty'].'</td>
            </tr>';
            $i++;
    		}
    	}
        $html.='</table>';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Approved Stock Book.pdf','I');
    }
 
  
     else if ($_GET["type"] == "getpackingallocation101") { 

         $output = Array();
         
          $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name,
        m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' AND c.status='approve'
        AND m.material_type='Packing Material'  ORDER BY c.id DESC ";
        
        // AND m.material_type='Raw Material'
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
               // $row["grn_grade"] = json_decode($row["grn_grade"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }


                $row["grn_grade_name"] = '';
                $output2 = [];
                $sql3 = "select * from challan_materials  where grn_no='".$row["grn_no"]."'";
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        
                        $output2[] = $row3;
                      //  if(isset($row3["grn_grade"]["grade"]))
                      
                        
                      $output2[] = $row3;
                      $row["grn_grade_name"] = $row3["grn_grade"];
                       
                    }
                }
        
             
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
              

     else if ($_GET["type"] == "getCheckPointByForm") { 

      
        $output = array();
        $sql = "SELECT cd.*,mc.module,mc.form_name,mc.department FROM mst_chlist_dtl cd 
                left join master_checklist mc on cd.chklist_id = mc.id 
                where mc.module='".$_GET["module"]."' and form_name='".$_GET["form"]."'";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    else if ($_GET["type"] == "updateReceivingGrn") {
        
        if($input["status"] == 'Accepted'){
            
            $sql = "UPDATE sampling_batches SET status = '".$input["status"]."' , isGrnReceive = 'YES' , grn_receive_remark ='".$input["grn_receive_remark"]."' , 
            grnReceiveBy = '".$_GET['emp_id']."' , grnReceiveOn = '$entry_date' WHERE id = '".$input["id"]."' ";
            
        }else{
            $sql = "UPDATE sampling_batches SET status = '".$input["status"]."' , grn_receive_remark ='".$input["grn_receive_remark"]."' , grnReceiveBy = '".$_GET['emp_id']."' , grnReceiveOn = '$entry_date' WHERE id = '".$input["id"]."' ";
        }
        
        
        

        if ($conn->query($sql)) {
            
            
            if($input["status"] == 'Accepted'){
                
                     $sql1 = "INSERT INTO `sampling`(`plant_id`,`material_code`, `batch_no`, `containers`, `grn_no`, `challan_no`, `ch_no`, `po_no`, `ar_no`, `grn_date`, `mfg_date`, `exp_date`, `received_qty`, 
                    `received_unit`, `manufacturer_no`, `supplier_no`, `status`, `entry_by`, `entry_date`) VALUES ('".$_GET["plant_id"]."','".$input["material_code"]."', '".$input["batch_no"]."', 
                    '".$input["total_containers"]."', '".$input["grn_no"]."','".$input["challan_no"]."', '".$input["ch_no"]."','".$input["po_no"]."', '".$input["ar_no"]."','".$input["grn_date"]."',
                    '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["qty_received"]."','".$input["unit"]."','".$input["mfg_by"]."','".$input["mfg_by"]."', 'Pending', '".$_GET["emp_id"]."','$entry_date')";
                    
                   $conn->query($sql1);
            
            }
                
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "RejectReceivingGrn") {
        $sql = "UPDATE challan_materials SET status='".$input["status"]."',correction = '".$input["reject_remark"]."'  WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {      
          
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if ($_GET["type"] == "RejectReceivingGrn1") {
        $sql = "UPDATE challan_materials SET status='".$input["status"]."'   WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {      
          
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    
    else if($_GET["type"] == ''){
         $_GET['filename'] = 'Sampling Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        
             $output = Array();
                $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess') AND id='".$_GET["id"]."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row["material_type"] = $row1["material_type"];
                                $row["material_name"] = $row1["material_name"];
                                $row["grade"] = $row1["grade"];
                            }
                        
            
                        
            $html.='
            <h2 style="text-align:cenetr">Sampling Report</h2>
            <table cellpadding="5" style="text-align:left;">
                <tr><td style="width:100%"><b>Line Clearance By QA</b></td></tr>
                <tr>
                    <td style="width:25%"><b>Previous Material Name</b></td>
                    <td style="width:75%">'.$row['material_name'].'</td>
                </tr>
                <tr>
                    <td><b>Previous Material GRN No.</b></td>
                    <td>'.$row['grn_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%"><b>Area Cleaned By</b></td>
                    <td style="width:25%">'.$row['sampling_person'].'</td>
                    <td style="width:25%"><b>Cleaning Date</b></td>
                    <td style="width:25%">'.date("d/m/Y", strtotime($row['entry_date'])).'</td>
                </tr>
                <tr>
                    <td style="width:100%"><b>QA Person has to ensure that all Traces of Previous Material Shall be Cleaned and Ensure that area is cleaned.</b><br><br>
                        <table>
                            <tr>
                                <td style="border:none;"><b>Time of Line Clearance :</b></td>
                                <td style="border:none;"><b>Sign and Date of IPQA</b></td>
                            </tr>
                            <tr>
                                <td style="width:100%; border:none;"><b>Person</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr>
                    <td style="width:100%;"><b>Name of Raw Material:</b> '.$row['material_name'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Item Code :</b> '.$row['material_code'].'</td>
                    <td style="width:50%;"><b>A.R. No :</b> '.$row['ar_no'].'</td>
                </tr>
                <tr>
                    <td><b>Mfg Date:</b> '.date("d/m/Y", strtotime($row['mfg_date'])).'</td>
                    <td><b>Exp Date:</b> '.date("d/m/Y", strtotime($row['exp_date'])).'</td>
                </tr>
                <tr>
                    <td><b>Manufacturer ( Vendor)</b> : '.$row['manufacturer'].'</td>
                    <td><b>Approved Not Approved</b></td>
                </tr>
                <tr><td style="width:100%;"><b>Supplier Name:</b></td></tr>
                <tr><td><b>Cleaning of surrounding area of consignment : </b>'.$row['cleaning_area'].'</td></tr>
                <tr><td><b>Proper consignment label affixed by vendor: </b>'.$row['label_affixed'].'</td></tr>
                <tr><td><b>Material’s Physical Description:</b> '.$row['physical_description'].'</td></tr>
                <tr>
                    <td style="width:50%"><b>Total No. of containers:</b> '.$row['total_containers'].'</td>
                    <td style="width:50%"><b>No. Of Containers Sampled:</b> '.$row['containers'].'</td>
                </tr>
                <tr><td style="width:100%;"><b>Containers number Sampled (For n+1 criteria):</b></td></tr>
                <tr><td><b>Temperature /Rel. Humidity of Sampling Room: </b>'.$row['room_temperature'].'°C / '.$row['humidity'].'%</td></tr>
                <tr><td><b>After Sampling Containers sealed and closed by:</b> '.$row['emp_name'].'</td></tr>
                <tr><td><b>Remark : </b>'.$row['remark'].'</td></tr>
                <tr><td><b>Sampled by :</b> '.$row['emp_name'].' &nbsp;&nbsp;&nbsp;<b> Sampled Date :</b> '.date("d/m/Y", strtotime($row['entry_date'])).'</td></tr>
                <tr><td><b>Reviewed by QA (If any Abnormality) sign/Date:</b></td></tr>
            </table>
            <div></div>';
             }
        }

            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Sampling Report.pdf', 'I');
                }
        
    
    }  
        
    else if ($_GET["type"] == "getPendingSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            if ($row["area_status"] == "complete") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'pending') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'inprocess') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["clearance_status"] == 'complete' && $row["area_status"] == 'complete' && $row["sample_status"] == 'pending') {
                $row["current_status"] = 'Sampling Information';
                $output1 = Array();
                for ($i = 0; $i < +$row["containers"]; $i++) {
                    $temp = Array();
                    $temp['container_no'] = $i + 1;
                    $temp['identication_qty'] = +$row["identication_qty"];
                    $temp['composite_qty'] = +$row["composite_qty"];
                    $temp['status'] = "pending";
                    $output1[] = $temp;
                }
                $row["container_details"] = $output1;
            }
 $row["area_details"] = json_decode($row["area_details"]);
            $output[] = $row;
            
             if(empty($input["plant_id"])){

                $plant_id = 0 ;
            }else{

                $plant_id = $input["plant_id"] ;
            }
          
           foreach($input["checklist"] as $chkListData){

            $chkSql = "INSERT INTO checklist_transaction(trans_id,chk_id,plant_id,module,form,deprt,params,chk_type,chk_value)
                                            VALUES      ('$rec_no',
                                                         ".$chkListData["id"].",
                                                         ".$plant_id .",
                                                         '".$chkListData["module"]."',
                                                         '".$chkListData["form_name"]."',
                                                         '".$chkListData["department"]."',
                                                         '".$chkListData["check_point"]."',
                                                         '".$chkListData["evl_pr"]."',
                                                         '".$chkListData["check"]."')";

                $conn->query($chkSql); 
           }
             
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingLineClearance") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."' AND area_status='complete' AND (clearance_status='pending' OR clearance_status='inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE id='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($row1['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE id !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $row["current_status"] = 'Line Clearance';
            
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
}

else if ($_GET["type"] == "callforclearance") {
    $sql = "INSERT INTO lineclearance (department,section,activity,material_no,grn_no,batch_no, checkpoints,request_by,request_date) VALUES ('".$_GET["department"]."','Sampling','".$input["material_type"]." Sampling','".$input["material_code"]."','".$input["grn_no"]."','".$input["batch_no"]."','".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','$entry_date')";
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $sql = "UPDATE sampling SET clearance_no='".$last_id."', clearance_status='inprocess' WHERE id='".$input["id"]."'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}
else if ($_GET["type"] == "getSamplingEquipment") {
    
    
        $output = array();
    // $sql = "SELECT e.location,e.department,e.equipment_name,e.equipment_code,e.status,e.equipment_type,e.plant_id,e.id,e.qualification_status,
    // (select a.frequency from equipment_maintenance a where a.equipment_id=e.id and due_type='Preventive' order by id desc limit 1) as frequency
    
    // FROM equipment e WHERE  
    // e.equipment_type ='Sampling Equipment'  and e.plant_id='".$_GET["plant_id"]."'  ";
    
    
        $sql = "SELECT e.location, e.department, e.equipment_name, e.equipment_code, e.status, e.equipment_type,
        e.plant_id, e.id, e.qualification_status, (SELECT a.frequency FROM equipment_maintenance a WHERE
        a.equipment_id = e.id AND a.due_type = 'Preventive' ORDER BY a.id DESC LIMIT 1) AS frequency FROM equipment e   WHERE
        e.equipment_type = 'Sampling Equipment' and e.plant_id='".$_GET["plant_id"]."'  ";
        
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
                if($row['status']=='pending'){
                    $row['status']='Not Active';
                }else if($row['status']=='Pending Status'){
                    $row['status']='Not Active';
                }
             
             
             
             
    $sql11 = "SELECT * FROM cleanActivity WHERE equipmentClean='".$row["equipment_code"]."'  order by id desc limit 1 ";
    $result11 = $conn->query($sql11);
    
    if($result11->num_rows > 0) {
         while ($row11 = $result11->fetch_assoc()) {
            $row['cleanBy']=$row11['cleanBy'];
            $row['C_date']=$row11['C_date'];
            $row['status']=$row11['status'];
            
         }
    }   
          
           
                
         if($row['frequency'] == 'Daily'){
    // Get yesterday's date
    $yesterday = date('Y-m-d', strtotime('yesterday'));

    // Query for yesterday's status
    $sql1 = "SELECT a.status 
            FROM equipment_maintenance a 
            WHERE a.equipment_id='".$row["id"]."' 
            AND a.due_date = '".$yesterday."' 
            AND a.due_type = 'Preventive'  order by a.id desc limit 1 ";
    $result1 = $conn->query($sql1);
    
    if($result1->num_rows > 0) {
         while ($row1 = $result1->fetch_assoc()) {
       $row['main_status']=$row1['status'];
         }
    }
    else{
       $row['main_status']='Maintance Not Done';
    }
 }
elseif($row['frequency'] == 'Weekly'){
    // Get the start and end of last week
    $start_of_week = date('Y-m-d', strtotime('last sunday -6 days'));
    $end_of_week = date('Y-m-d', strtotime('last sunday'));

    // Query for last week's status
    $sql1 = "SELECT a.status 
            FROM equipment_maintenance a 
            WHERE a.equipment_id='".$row["id"]."' 
            AND a.due_date BETWEEN '".$start_of_week."' AND '".$end_of_week."' 
            AND a.due_type = 'Preventive'  order by a.id desc limit 1 ";
    $result1 = $conn->query($sql1);
    
    if($result1->num_rows > 0) {
         while ($row1 = $result1->fetch_assoc()) {
       $row['main_status']=$row1['status'];
         }
    }else{
       $row['main_status']='Maintance Not Done';
    }
}
elseif($row['frequency'] == 'Monthly'){
    // Get the start and end of last month
    $start_of_month = date('Y-m-01', strtotime('first day of last month'));
    $end_of_month = date('Y-m-t', strtotime('last day of last month'));

    // Query for last month's status
     $sql1 = "SELECT a.status 
            FROM equipment_maintenance a 
            WHERE a.equipment_id='".$row["id"]."' 
            AND a.due_date BETWEEN '".$start_of_month."' AND '".$end_of_month."' 
            AND a.due_type = 'Preventive'  order by a.id desc limit 1 ";
    $result1 = $conn->query($sql1);
    
    if($result1->num_rows > 0) {
         while ($row1 = $result1->fetch_assoc()) {
       $row['main_status']=$row1['status'];
         }
    }else{
       $row['main_status']='Maintance Not Done';
    }
}
elseif($row['frequency'] == 'Quarterly'){
    
    $quarter = ceil(date('n') / 3); // Get the current quarter

// Calculate the first day of the last quarter
$start_of_last_quarter = date('Y-m-d', strtotime('-' . (($quarter - 1) * 3) . ' months'));

// Calculate the first day of the next quarter
$start_of_next_quarter = date('Y-m-d', strtotime('+' . (4 - $quarter) . ' quarter'));

// Subtract 1 day from the start of the next quarter to get the last day of the last quarter
$end_of_last_quarter = date('Y-m-d', strtotime($start_of_next_quarter . ' -1 day'));

// Query for the last quarter's status
$sql1 = "SELECT a.status 
        FROM equipment_maintenance a 
        WHERE a.equipment_id='".$row["id"]."' 
        AND a.due_date BETWEEN '".$start_of_last_quarter."' AND '".$end_of_last_quarter."' 
        AND a.due_type = 'Preventive'  order by a.id desc limit 1 ";

 $result1 = $conn->query($sql1);
    
    if($result1->num_rows > 0) {
         while ($row1 = $result1->fetch_assoc()) {
       $row['main_status']=$row1['status'];
         }
    }else{
       $row['main_status']='Maintance Not Done';
    }

}
elseif($row['frequency'] == 'Half-Yearly'){
    // Get the start and end of the last 6 months
    $start_of_half_year = date('Y-m-d', strtotime('-6 months first day of January'));
    $end_of_half_year = date('Y-m-d', strtotime('-6 months last day of June'));

    // Query for the last 6 months' status
    $sql1 = "SELECT a.status 
            FROM equipment_maintenance a 
            WHERE a.equipment_id='".$row["id"]."' 
            AND a.due_date BETWEEN '".$start_of_half_year."' AND '".$end_of_half_year."' 
            AND a.due_type = 'Preventive'  order by a.id desc limit 1 ";
  $result1 = $conn->query($sql1);
    
    if($result1->num_rows > 0) {
         while ($row1 = $result1->fetch_assoc()) {
       $row['main_status']=$row1['status'];
         }
    }else{
       $row['main_status']='Maintance Not Done';
    }
}
elseif($row['frequency'] == 'Annually'){
    // Get the start and end of the last year
    $start_of_year = date('Y-01-01', strtotime('last year'));
    $end_of_year = date('Y-12-31', strtotime('last year'));

    // Query for last year's status
    $sql1 = "SELECT a.status 
            FROM equipment_maintenance a 
            WHERE a.equipment_id='".$row["id"]."' 
            AND a.due_date BETWEEN '".$start_of_year."' AND '".$end_of_year."' 
            AND a.due_type = 'Preventive'   order by a.id desc limit 1 ";
 $result1 = $conn->query($sql1);
    
    if($result1->num_rows > 0) {
         while ($row1 = $result1->fetch_assoc()) {
       $row['main_status']=$row1['status'];
         }
    }else{
       $row['main_status']='Maintance Not Done';
    }
}

            
             $output[] = $row;
        }
    }
      echo json_encode($output);
    
 

}


else if ($_GET["type"] == "saveAreaCheckpoints") {
  if($_GET['for']=='oos'){
        
    $sql1 = "UPDATE newoos SET status='Line Clearance complete' WHERE id='".$_GET["new_oos_id"]."'";
    $conn->query($sql1);
    $sql = "UPDATE sampling SET oos_area_details='".json_encode($input)."', oos_sampling='Line Clearance complete' ,oos_laf_qeuipment_code ='".$input['laf_id']."' WHERE id='".$_GET["id"]."'";
            // $sql = "UPDATE sampling SET area_details='".json_encode($input)."',laf_qeuipment_code ='".$input['laf_id']."', area_status='complete' WHERE id='".$_GET["id"]."'";

    }else{
       // print_r($input); exit;
        $sql = "UPDATE sampling SET area_details='".json_encode($input)."',laf_qeuipment_code ='".$input['laf_id']."', area_status='complete' WHERE id='".$_GET["id"]."'";
       }
       if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    
    
    else if ($_GET["type"] == "getPendingSamplingForm") {
    $output = array();
    $materialType = isset($_GET["material_type"]) ? trim($_GET["material_type"]) : '';
    if ($materialType === '') {
        $materialType = 'Raw Material';
    }
    $materialTypeEsc = $conn->real_escape_string($materialType);

    $sql = "SELECT a.*,c.material_type,c.material_subtype,c.material_name,c.grade,c.grade as grn_grade,c.storage_condition,c.material_subtype,c.sampleForTesting,c.samplingUnit,c.unit as baseUnit,
            (select CONCAT(e.firstname , ' ' , e.lastname) as samplngPersonName from employee e where e.emp_id = a.sampling_person limit 1) as  samplngPersonName,
            (select vendor_name from vendor e where e.vendor_no = a.manufacturer_no limit 1) as  manuNAme,
            (select vendor_name from vendor e where e.vendor_no = a.supplier_no limit 1) as  supplierNAme
            FROM sampling a 
            join material c on a.material_code = c.material_code 
            WHERE a.status = 'Balance_Cleaning_Done' AND  a.plant_id = '".$_GET["plant_id"]."' AND  c.material_type = '".$materialTypeEsc."'";
     
 
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { 
             
            $sql01 = "SELECT CONCAT(cleaning_date, 'T', TIME_FORMAT(startTime, '%H:%i') ) AS LAF_start_date FROM sampling_LAF_Clean_record WHERE equipment_type = 'LAF'  and  DATE(cleaning_date) = CURDATE() order by id desc limit 1";
            $result01 = $conn->query($sql01);
            if ($result01->num_rows > 0) {
                while ($row01 = $result01->fetch_assoc()) {
                    $row['LAF_start_date']=$row01['LAF_start_date'];
                }
            }else{
                $row['LAF_status']='Need To Start LAF';
            }
             
             $output[] = $row;
        }
    }
    echo json_encode($output);
} 
 
       

    else if ($_GET["type"] == "saveSamplingInfo") {
     
        $sql = "UPDATE sampling SET sampling_details = '".json_encode($input["container_details"])."', specification_no = '".$input["specification_no"]."', sampledContainers = '".$input["sampledContainers"]."', 
        physicalObservation = '".$input["physicalObservation"]."', appearance = '".$input["appearance"]."', sample_qty = '".$input["sample_qty"]."', sample_unit = '".$input["samplingUnit"]."', samplingRemark = '".$input["samplingRemark"]."', 
        presenceOfForeignParticles = '".$input["presenceOfForeignParticles"]."', perfumeOderVerification = '".$input["perfumeOderVerification"]."',  samplerId = '".$input["samplerId"]."',start_time = '".$input["start_time"]."',
        end_time = '".$input["end_time"]."',LAF_start_date = '".$input["LAF_start_date"]."',LAF_stop_date = '".$input["LAF_stop_date"]."',
        status = 'Active', sampledBy='".$_GET["emp_id"]."', sampledOn = '$entry_date' , convertedQtyToBaseUnit='".$input["convertedQtyToBaseUnit"]."'
        WHERE id = '".$input["id"]."'";  
         
     
        if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }

    else if($_GET["type"] == 'downloadSamplingsRecord') {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        include("../../pdfimp2.php");

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string(trim((string)$_GET['plant_id'])) : '';
        $var = (!empty($logo))
            ? 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/'.$logo
            : '';

        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('RM/PM SAMPLING AND INSPECTION CHECKLIST');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();

        $sql = "SELECT a.*, c.material_type, c.material_subtype, c.material_name, c.grade, c.storage_condition, c.sampleForTesting, c.unit as baseUnit,
                (SELECT TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))) FROM employee e WHERE CAST(e.emp_id AS CHAR) = CAST(a.sampling_person AS CHAR) LIMIT 1) AS samplngPersonName,
                (SELECT vendor_name FROM vendor e WHERE e.vendor_no = a.manufacturer_no LIMIT 1) AS manuNAme,
                (SELECT vendor_name FROM vendor e WHERE e.vendor_no = a.supplier_no LIMIT 1) AS supplierNAme,
                (SELECT TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))) FROM employee e WHERE CAST(e.emp_id AS CHAR) = CAST(a.sampledBy AS CHAR) LIMIT 1) AS sampled_by_name,
                (SELECT TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))) FROM employee e WHERE CAST(e.emp_id AS CHAR) = CAST(a.check_by AS CHAR) LIMIT 1) AS cheeckedByName
                FROM sampling a
                LEFT JOIN material c ON a.material_code = c.material_code
                WHERE a.id = '".$id."'";
        if ($plantId !== '') {
            $sql .= " AND a.plant_id = '".$plantId."'";
        }
        $sql .= " LIMIT 1";

        $result = @$conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Sampling record not found';
            exit;
        }
        $row = $result->fetch_assoc();

        $esc = function ($value) {
            return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
        };
        $val = function ($value) use ($esc) {
            $text = trim((string)$value);
            return $text === '' ? '-' : $esc($text);
        };
        $dateVal = function ($value) use ($val) {
            $fmt = sr_fmt_label_date($value);
            return $fmt === '' ? '-' : $val($fmt);
        };
        $dateTimeVal = function ($value) use ($val) {
            if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return '-';
            }
            $ts = strtotime($value);
            if (!$ts) {
                return $val($value);
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$value))) {
                return $val(date('d-m-Y', $ts));
            }
            return $val(date('d-m-Y H:i', $ts));
        };

        $sampledName = sr_resolve_employee_name($conn, $row);
        $checkedName = trim(isset($row['cheeckedByName']) ? $row['cheeckedByName'] : '');
        if ($checkedName === '' || preg_match('/^\d+$/', $checkedName)) {
            $checkedName = sr_resolve_employee_name($conn, array(
                'sampledBy' => isset($row['check_by']) ? $row['check_by'] : '',
            ));
        }
        $mfgName = trim(isset($row['manuNAme']) ? $row['manuNAme'] : '');
        if ($mfgName === '' && !empty($row['mfg_by'])) {
            $mfgName = $row['mfg_by'];
        }
        $supplierName = trim(isset($row['supplierNAme']) ? $row['supplierNAme'] : '');
        $qtyReceived = trim((isset($row['received_qty']) ? $row['received_qty'] : '').' '.(isset($row['baseUnit']) ? $row['baseUnit'] : ''));
        $receivedCont = isset($row['total_containers']) && $row['total_containers'] !== '' && $row['total_containers'] !== null
            ? $row['total_containers']
            : (isset($row['containers']) ? $row['containers'] : '');
        $sampledCont = isset($row['sampledContainers']) && $row['sampledContainers'] !== '' && $row['sampledContainers'] !== null
            ? $row['sampledContainers']
            : (isset($row['containers']) ? $row['containers'] : '');
        $sampleQty = trim((isset($row['sample_qty']) ? $row['sample_qty'] : '').' '.(isset($row['sample_unit']) ? $row['sample_unit'] : ''));
        $lab = 'font-size:8px; font-weight:bold; text-align:left; vertical-align:middle;';
        $dat = 'font-size:8px; text-align:left; vertical-align:middle; color:#1a4f9c;';
        $meta = 'font-size:8px; text-align:left; vertical-align:middle;';
        $logoHtml = $var !== ''
            ? '<img src="'.$esc($var).'" width="58" height="18" />'
            : '&nbsp;';

        $html = '
            <table cellpadding="4" cellspacing="0" border="1" width="100%">
                <tr>
                    <td width="40%" align="center" valign="middle">'.$logoHtml.'</td>
                    <td width="60%" align="center" valign="middle">
                        <span style="font-size:13px; font-weight:bold;">'.$esc($plant_full_name).'</span><br>
                        <span style="font-size:8px;">'.$esc($plant_full_address).'</span>
                    </td>
                </tr>
                <tr>
                    <td width="40%" style="'.$meta.'">Quality Control Department</td>
                    <td width="60%" style="'.$meta.'">Format No. : QC/GEN/09/F/04-R/00</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$meta.'">SOP Ref. No. : QC/GEN/09</td>
                    <td width="60%" style="'.$meta.'">Page No. 1 Of 1</td>
                </tr>
                <tr>
                    <td width="100%" colspan="2" align="center" style="font-size:10px; font-weight:bold;">RM/PM SAMPLING AND INSPECTION CHECKLIST</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">1. ITEM</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['material_name']) ? $row['material_name'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">2. Receiving No.</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['grn_no']) ? $row['grn_no'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">3. Medicap Lot No.</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['batch_no']) ? $row['batch_no'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">4. Date of receipt</td>
                    <td width="60%" style="'.$dat.'">'.$dateVal(isset($row['grn_date']) ? $row['grn_date'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">5. Qty. received</td>
                    <td width="60%" style="'.$dat.'">'.$val($qtyReceived).'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">6. Manufacturer Name</td>
                    <td width="60%" style="'.$dat.'">'.$val($mfgName).'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">7. Supplier Name</td>
                    <td width="60%" style="'.$dat.'">'.$val($supplierName).'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">8. Mfg Date</td>
                    <td width="60%" style="'.$dat.'">'.$dateVal(isset($row['mfg_date']) ? $row['mfg_date'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">9. Exp. Date</td>
                    <td width="60%" style="'.$dat.'">'.$dateVal(isset($row['exp_date']) ? $row['exp_date'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">10. Physical observations</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['physicalObservation']) ? $row['physicalObservation'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">&nbsp;&nbsp;i) Appearance</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['appearance']) ? $row['appearance'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">&nbsp;&nbsp;ii) Presence of foreign particles</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['presenceOfForeignParticles']) ? $row['presenceOfForeignParticles'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">11. No. of Cont./Pallets/Boxes received</td>
                    <td width="60%" style="'.$dat.'">'.$val($receivedCont).'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">12. No. of Cont./Pallets/Boxes Sampled</td>
                    <td width="60%" style="'.$dat.'">'.$val($sampledCont).'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">13. Sample Quantity</td>
                    <td width="60%" style="'.$dat.'">'.$val($sampleQty).'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">Remark</td>
                    <td width="60%" style="'.$dat.'">'.$val(isset($row['samplingRemark']) ? $row['samplingRemark'] : '').'</td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">Sampled By :- <span style="font-weight:normal; color:#1a4f9c;">'.$val($sampledName).'</span></td>
                    <td width="60%" style="'.$lab.'">Checked By :- <span style="font-weight:normal; color:#1a4f9c;">'.$val($checkedName).'</span></td>
                </tr>
                <tr>
                    <td width="40%" style="'.$lab.'">Sign / Date :- <span style="font-weight:normal; color:#1a4f9c;">'.$val($sampledName).' / '.$dateTimeVal(isset($row['sampledOn']) ? $row['sampledOn'] : '').'</span></td>
                    <td width="60%" style="'.$lab.'">Sign / Date :- <span style="font-weight:normal; color:#1a4f9c;">'.$val($checkedName).' / '.$dateTimeVal(isset($row['check_date']) ? $row['check_date'] : '').'</span></td>
                </tr>
            </table>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('RM_PM_Sampling_Checklist.pdf', 'I');
        exit;
    }
    else if($_GET["type"] == 'downloadSamplingLog') {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $materialType = trim((string)(isset($_GET["material_type"]) ? $_GET["material_type"] : ''));
        $samplingScope = trim((string)(isset($_GET["sampling_scope"]) ? $_GET["sampling_scope"] : ''));
        if ($materialType === '' && $samplingScope !== 'retest') {
            $materialType = 'Raw Material';
        }
        $logTitle = ($samplingScope === 'retest')
            ? 'RETEST SAMPLING LOG'
            : trim($materialType.' SAMPLING LOG');
        $_GET['filename'] = $logTitle;
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include("../../pdfimp2.php");

        $html .= '
            <h2 style="text-align:center; font-size:12px;">'.htmlspecialchars($logTitle, ENT_QUOTES, 'UTF-8').'</h2>
            <table cellpadding="2" cellspacing="0" border="1" width="100%" style="border-collapse:collapse;">
                <tr style="text-align:center; background-color:#DDDAD9; font-weight:bold;">
                    <td width="4%" style="font-size:7px;">Sr.No</td>
                    <td width="10%" style="font-size:7px;">Sampling No.</td>
                    <td width="16%" style="font-size:7px;">Material Name</td>
                    <td width="10%" style="font-size:7px;">Material Code</td>
                    <td width="9%" style="font-size:7px;">Medicap lot no.</td>
                    <td width="10%" style="font-size:7px;">Receiving No.</td>
                    <td width="8%" style="font-size:7px;">Status</td>
                    <td width="11%" style="font-size:7px;">Samp. By/On</td>
                    <td width="11%" style="font-size:7px;">Check. By/On</td>
                    <td width="11%" style="font-size:7px;">Appr. By/On</td>
                </tr>
            ';


      $sql = "SELECT a.*,c.material_type,c.material_subtype,c.material_name,c.grade,c.storage_condition,c.material_subtype,c.sampleForTesting,c.unit as baseUnit,
                (select CONCAT(e.firstname , ' ' , e.lastname) as samplngPersonName from employee e where e.emp_id = a.sampling_person limit 1) as  samplngPersonName,
                (select vendor_name from vendor e where e.vendor_no = a.manufacturer_no limit 1) as  manuNAme,
                (select vendor_name from vendor e where e.vendor_no = a.supplier_no limit 1) as  supplierNAme
                FROM sampling a 
                join material c on a.material_code = c.material_code 
                WHERE a.status = 'Approved' AND  a.plant_id = '".$_GET["plant_id"]."'";
        if ($materialType !== '') {
            $sql .= " AND c.material_type = '".$conn->real_escape_string($materialType)."'";
        }
        if ($samplingScope === 'retest') {
            require_once __DIR__.'/../../store/retest_helpers.php';
            $sql .= ' AND '.medicap_retest_sampling_scope_sql('a');
        }
        $sql .= " ORDER BY a.id DESC";
                $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
                 
                 
                $html .= '
                <tr>
                    <td style="font-size: 7px; text-align:center;">'.$i.'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['sampling_no'].'</td>
                    <td style="font-size: 7px; text-align:left;">'.$row['material_name'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['material_code'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['batch_no'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['grn_no'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['status'].'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['sampledBy'].' / '.(!empty($row['sampledOn']) ? date("d-m-Y", strtotime($row['sampledOn'])) : '-' ).'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['check_by'].' / '.(!empty($row['check_date']) ? date("d-m-Y", strtotime($row['check_date'])) : '-' ).'</td>
                    <td style="font-size: 7px; text-align:center;">'.$row['approve_by'].' / '.(!empty($row['approve_date']) ? date("d-m-Y", strtotime($row['approve_date'])) : '-' ).'</td>
                </tr>
                ';
                 
                 
                 
                 $i++;
                 
            }
        }
       
        $html .= '</table>';
            
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('sampling_log.pdf', 'I');
        exit;
   }
    else if($_GET["type"] == 'downloadGrnReceivingLog') {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $materialType = isset($_GET["material_type"]) ? $_GET["material_type"] : "Raw Material";
        $_GET['filename'] = $materialType.' SAMPLING INTIMATION LOG';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include("../../pdfimp2.php");

        $html .= '
            <h2 style="text-align:center; font-size:12px;">'.$materialType.' SAMPLING INTIMATION LOG</h2>
            <table cellpadding="2" cellspacing="0" border="1" width="100%" style="border-collapse:collapse;">
                <tr style="text-align:center; background-color:#DDDAD9; font-weight:bold;">
                    <td width="4%" style="font-size:7px;">Sr.No</td>
                    <td width="8%" style="font-size:7px;">Material Type</td>
                    <td width="18%" style="font-size:7px;">Material Name</td>
                    <td width="10%" style="font-size:7px;">Material Code</td>
                    <td width="7%" style="font-size:7px;">Grade</td>
                    <td width="12%" style="font-size:7px;">Medicap Lot No</td>
                    <td width="7%" style="font-size:7px;">Total Cont.</td>
                    <td width="8%" style="font-size:7px;">Status</td>
                    <td width="26%" style="font-size:7px;">Received By/On</td>
                </tr>
            ';

        $sql = "SELECT a.id, a.material_code, a.batch_no, a.ar_no, a.grn_no, a.status, a.total_containers,
                a.grnReceiveBy, a.grnReceiveOn, m.material_type, m.grade, m.material_name,
                (SELECT TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,'')))
                 FROM employee e WHERE CAST(e.emp_id AS CHAR) = CAST(a.grnReceiveBy AS CHAR) LIMIT 1) AS grnReceiveByName
                FROM sampling_batches a
                LEFT JOIN material m ON a.material_code = m.material_code
                WHERE a.plant_id = '".$_GET["plant_id"]."'
                AND a.isGrnReceive = 'YES'
                AND a.status != 'OPENING'
                AND m.material_type = '".$conn->real_escape_string($materialType)."'
                ORDER BY a.id DESC";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $receivedBy = trim((string)(!empty($row['grnReceiveByName']) ? $row['grnReceiveByName'] : $row['grnReceiveBy']));
                $receivedOn = (!empty($row['grnReceiveOn']) ? date('d-m-Y', strtotime($row['grnReceiveOn'])) : '-');
                $html .= '
                <tr>
                    <td width="4%" style="font-size:6px; text-align:center;">'.$i.'</td>
                    <td width="8%" style="font-size:6px; text-align:center;">'.$row['material_type'].'</td>
                    <td width="18%" style="font-size:6px; text-align:left;">'.$row['material_name'].'</td>
                    <td width="10%" style="font-size:6px; text-align:center;">'.$row['material_code'].'</td>
                    <td width="7%" style="font-size:6px; text-align:center;">'.$row['grade'].'</td>
                    <td width="12%" style="font-size:6px; text-align:center;">'.$row['batch_no'].'</td>
                    <td width="7%" style="font-size:6px; text-align:center;">'.$row['total_containers'].'</td>
                    <td width="8%" style="font-size:6px; text-align:center;">'.$row['status'].'</td>
                    <td width="26%" style="font-size:6px; text-align:center;">'.$receivedBy.' / '.$receivedOn.'</td>
                </tr>
                ';
                $i++;
            }
        }

        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('grn_receiving_log.pdf', 'I');
        exit;
   }
    else if($_GET["type"] == 'downloadSamplingAllocationLog') {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $materialType = isset($_GET["material_type"]) ? $_GET["material_type"] : "Raw Material";
        $_GET['filename'] = $materialType.' SAMPLING ALLOCATION LOG';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdfpage'] = 'L';
        include("../../pdfimp2.php");

        $html .= '
            <h2 style="text-align:center; font-size:12px;">'.$materialType.' SAMPLING ALLOCATION LOG</h2>
            <table cellpadding="2" cellspacing="0" border="1" width="100%" style="border-collapse:collapse;">
                <tr style="text-align:center; background-color:#DDDAD9; font-weight:bold;">
                    <td style="font-size: 6px;">Sr</td>
                    <td style="font-size: 6px;">Sampling No</td>
                    <td style="font-size: 6px;">Material</td>
                    <td style="font-size: 6px;">Medicap Lot No</td>
                    <td style="font-size: 6px;">Receiving No/Dt</td>
                    <td style="font-size: 6px;">Pack Slip</td>
                    <td style="font-size: 6px;">Cont.</td>
                    <td style="font-size: 6px;">Receiving by</td>
                    <td style="font-size: 6px;">Receive By/Dt.</td>
                    <td style="font-size: 6px;">Samp QC</td>
                    <td style="font-size: 6px;">Samp Micro</td>
                    <td style="font-size: 6px;">Alt QC</td>
                    <td style="font-size: 6px;">Alt Micro</td>
                    <td style="font-size: 6px;">Alloc By/Dt</td>
                    <td style="font-size: 6px;">Status</td>
                </tr>';

        $sql = "SELECT a.id, a.sampling_no, a.material_code, a.batch_no, a.containers, a.grn_no, a.ch_no, a.ar_no, a.grn_date,
                a.status, a.alloocationBy, a.alloocationOn, m.material_name,
                (select s.grnReceiveBy from sampling_batches s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveBy,
                (select s.grnReceiveOn from sampling_batches s where s.batch_no = a.batch_no AND s.material_code = a.material_code limit 1) as grnReceiveOn,
                (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) from employee e where e.emp_id = a.sampling_person limit 1) as sampling_personName,
                (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) from employee e where e.emp_id = a.micro_person limit 1) as micro_personName,
                (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) from employee e where e.emp_id = a.alternate_qc_person limit 1) as alternate_qc_personName,
                (select CONCAT(e.firstname, ' ', e.lastname, ' - ', e.emp_id) from employee e where e.emp_id = a.alternate_micro_person limit 1) as alternate_micro_personName,
                (select c.grn_by from challan_materials c where c.challan_no = a.challan_no AND c.material_code = a.material_code limit 1) as grn_by
                FROM sampling a
                LEFT JOIN material m ON a.material_code = m.material_code
                WHERE a.plant_id = '".$_GET["plant_id"]."'
                AND a.status != 'Pending'
                AND m.material_type = '".$conn->real_escape_string($materialType)."'
                ORDER BY a.alloocationOn DESC";

        $i = 1;
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '
                <tr>
                    <td style="font-size: 6px; text-align:center;">'.$i.'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['sampling_no'].'</td>
                    <td style="font-size: 6px; text-align:left;">'.$row['material_name'].' ('.$row['material_code'].')</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['batch_no'].'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['grn_no'].' / '.sr_qc_pdf_date($row['grn_date']).'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['ch_no'].'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['containers'].'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['grn_by'].'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['grnReceiveBy'].' / '.sr_qc_pdf_date($row['grnReceiveOn']).'</td>
                    <td style="font-size: 6px; text-align:center;">'.($row['sampling_personName'] ?: 'NA').'</td>
                    <td style="font-size: 6px; text-align:center;">'.($row['micro_personName'] ?: 'NA').'</td>
                    <td style="font-size: 6px; text-align:center;">'.($row['alternate_qc_personName'] ?: 'NA').'</td>
                    <td style="font-size: 6px; text-align:center;">'.($row['alternate_micro_personName'] ?: 'NA').'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['alloocationBy'].' / '.sr_qc_pdf_date($row['alloocationOn']).'</td>
                    <td style="font-size: 6px; text-align:center;">'.$row['status'].'</td>
                </tr>';
                $i++;
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('sampling_allocation_log.pdf', 'I');
        exit;
   }
    else if ($_GET["type"] == "printQuarantineReceivingLabel") {
        if (!isset($_GET['id']) || trim($_GET['id']) === '') {
            echo 'Invalid id';
            exit;
        }

        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('Receiving & Quarantine Material Labels');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 8);

        $sql = "SELECT s.*, m.material_name, m.grade, m.storage_condition,
            cm.receiving_no, cm.received_by, cm.receiving_date, cm.manufacturer_no,
            c1.po_no, c1.vendor_no,
            v.vendor_name,
            vm.vendor_name AS manufacturer_name,
            p.plant_full_name,
            (SELECT CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,'')) FROM employee e WHERE e.emp_id = s.grnReceiveBy LIMIT 1) AS grn_receive_by_name,
            (SELECT CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,'')) FROM employee e WHERE e.emp_id = cm.received_by LIMIT 1) AS received_by_name
            FROM sampling_batches s
            LEFT JOIN material m ON s.material_code = m.material_code
            LEFT JOIN challan_materials cm ON cm.challan_no = s.challan_no AND cm.material_code = s.material_code
            LEFT JOIN challan c1 ON c1.challan_no = s.challan_no
            LEFT JOIN vendor v ON c1.vendor_no = v.vendor_no
            LEFT JOIN vendor vm ON cm.manufacturer_no = vm.vendor_no
            LEFT JOIN plant p ON s.plant_id = p.plant_id
            WHERE s.id = '".$_GET['id']."' LIMIT 1";

        $result = $conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            echo 'Invalid id';
            exit;
        }

        $row = $result->fetch_assoc();
        $count = max(1, (int)$row['total_containers']);
        $plantName = 'MEDICAP LABORATORIES';
        if (trim($row['plant_full_name']) !== '') {
            $plantName = strtoupper(trim($row['plant_full_name']));
        }
        $productDesc = trim($row['material_name']);
        if (!empty($row['grade'])) {
            $productDesc .= ' / ' . $row['grade'];
        }
        $manufacturer = trim($row['manufacturer_name']);
        if ($manufacturer === '' && !empty($row['mfg_by'])) {
            $manufacturer = $row['mfg_by'];
        }
        $supplier = trim($row['vendor_name']);
        $receivedBy = trim($row['grn_receive_by_name']);
        if ($receivedBy === '') {
            $receivedBy = trim($row['received_by_name']);
        }
        if ($receivedBy === '') {
            $receivedBy = $row['grnReceiveBy'];
        }
        $receivedDate = '';
        if (!empty($row['grnReceiveOn'])) {
            $receivedDate = date('d-m-Y', strtotime($row['grnReceiveOn']));
        } else if (!empty($row['receiving_date'])) {
            $receivedDate = date('d-m-Y', strtotime($row['receiving_date']));
        }
        $expDate = !empty($row['exp_date']) ? date('d-m-Y', strtotime($row['exp_date'])) : $row['exp_date'];
        $totalQty = trim($row['qty_received'] . ' ' . $row['unit']);
        $mfgLot = trim($row['supplier_batch_no']);
        if ($mfgLot === '') {
            $mfgLot = $row['batch_no'];
        }

        $html = '';
        for ($i = 1; $i <= $count; $i++) {
            if ($i > 1) {
                $html .= '<br pagebreak="true"/>';
            }
            $html .= buildQuarantineReceivingLabelSheet(array(
                'plantName' => $plantName,
                'productDesc' => $productDesc,
                'mfgLot' => $mfgLot,
                'manufacturer' => $manufacturer,
                'supplier' => $supplier,
                'materialCode' => $row['material_code'],
                'lotNo' => $row['batch_no'],
                'expDate' => $expDate,
                'containerNo' => $i,
                'containerTotal' => $count,
                'receivedBy' => $receivedBy,
                'receivedDate' => $receivedDate,
                'totalQty' => $totalQty,
            ));
        }

        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Quarantine_Receiving_Labels.pdf', 'I');
        exit;
    }
    else if ($_GET["type"] == "printSampledByQcLabel") {
        @ini_set('display_errors', '0');
        error_reporting(E_ERROR | E_PARSE);
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid sampling id';
            exit;
        }

        $idEsc = $conn->real_escape_string((string)$id);
        $sql = "SELECT a.*,
                (SELECT TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))) FROM employee e WHERE CAST(e.emp_id AS CHAR) = CAST(a.sampledBy AS CHAR) LIMIT 1) AS sampled_by_name,
                (SELECT TRIM(CONCAT(IFNULL(e.firstname,''), ' ', IFNULL(e.lastname,''))) FROM employee e WHERE CAST(e.emp_id AS CHAR) = CAST(a.sampling_person AS CHAR) LIMIT 1) AS sampling_person_name
                FROM sampling a
                WHERE a.id = '".$idEsc."'
                LIMIT 1";
        $result = @$conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Sampling record not found';
            exit;
        }
        $row = $result->fetch_assoc();

        $employeeName = sr_resolve_employee_name($conn, $row);
        $sampledOn = sr_fmt_label_date(isset($row['sampledOn']) ? $row['sampledOn'] : '');
        if ($sampledOn === '') {
            $sampledOn = sr_fmt_label_date(isset($row['entry_date']) ? $row['entry_date'] : '');
        }

        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('Sampled By QC Label');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $x = 12;
        $y = 12;
        $w = 92;
        $h = 34;
        $pdf->SetLineWidth(0.35);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Rect($x, $y, $w, $h);

        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetXY($x, $y + 3);
        $pdf->Cell($w, 8, 'Sampled By QC', 0, 1, 'C');

        $pdf->SetXY($x + 5, $y + 14);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(6, 'Employee Name: ');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(6, $employeeName !== '' ? $employeeName : '-');

        $pdf->SetXY($x + 5, $y + 22);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(6, 'Date: ');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(6, $sampledOn !== '' ? $sampledOn : '-');

        $pdf->Output('Sampled_By_QC_Label.pdf', 'I');
        exit;
    }
     
     
     
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
}catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
?>   