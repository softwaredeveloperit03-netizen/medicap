<?php
require '../db.php';
require '../tcpdf/tcpdf.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

function roundChallanDecimal($value, $decimals = 4) {
    if ($value === null || $value === '') {
        return 0;
    }
    return round((float) $value, $decimals);
}

function formatChallanPdfDate($date) {
    if ($date === null || $date === '') {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : $date;
}

function formatChallanPdfMonthYear($date) {
    if ($date === null || $date === '') {
        return '';
    }
    $ts = strtotime($date);
    if ($ts) {
        return date('m/Y', $ts);
    }
    return $date;
}

function formatChallanPdfQty($value) {
    return number_format(roundChallanDecimal($value), 4, '.', '');
}

function formatChallanPdfAmount($value) {
    return number_format(roundChallanDecimal($value, 2), 2, '.', '');
}

function formatChallanEWayBillNo($value) {
    $digits = preg_replace('/\D/', '', (string) $value);
    if ($digits === '') {
        return '-';
    }
    return trim(chunk_split($digits, 4, ' '));
}

function getChallanEmployeeDisplayName($empId, $conn) {
    if ($empId === null || trim((string) $empId) === '') {
        return '';
    }
    $empIdEsc = $conn->real_escape_string(trim((string) $empId));
    $sql = "SELECT TRIM(CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))) AS emp_name
            FROM employee WHERE emp_id = '".$empIdEsc."' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = trim($row['emp_name']);
        if ($name !== '') {
            return $name;
        }
    }
    return trim((string) $empId);
}

function resolveChallanPdfSignatureImage($empId, $conn) {
    $candidates = array();
    if ($empId !== null && trim((string) $empId) !== '') {
        $empIdEsc = $conn->real_escape_string(trim((string) $empId));
        $sql = "SELECT sign FROM employee WHERE emp_id = '".$empIdEsc."' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $signFile = trim((string) ($row['sign'] ?? ''));
            if ($signFile !== '') {
                $candidates[] = __DIR__ . '/../upload/employee/' . $signFile;
                $candidates[] = __DIR__ . '/../upload/employee/' . basename($signFile);
            }
        }
    }
    $candidates[] = __DIR__ . '/../upload/pdf/sign.jpg';
    $candidates[] = __DIR__ . '/../upload/pdf/sign.png';
    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            return str_replace('\\', '/', $candidate);
        }
    }
    return '';
}

function challanRecalculateProductLine(&$row, $gstType) {
    $qty = roundChallanDecimal($row['requiredQty']);
    $rate = roundChallanDecimal($row['rate']);
    $gst = roundChallanDecimal($row['gst']);

    $row['requiredQty'] = $qty;
    $row['rate'] = $rate;
    $row['gst'] = $gst;
    $row['taxable'] = roundChallanDecimal($qty * $rate);
    $row['taxAmt'] = roundChallanDecimal(($row['taxable'] * $gst) / 100);

    $gstType = strtoupper(trim((string) $gstType));
    if ($gstType === 'IGST') {
        $row['igst'] = $row['taxAmt'];
        $row['cgst'] = 0;
        $row['sgst'] = 0;
    } else {
        $row['igst'] = 0;
        $row['cgst'] = roundChallanDecimal($row['taxAmt'] / 2);
        $row['sgst'] = roundChallanDecimal($row['taxAmt'] / 2);
    }
    $row['netAmt'] = roundChallanDecimal($row['taxable'] + $row['taxAmt']);
}

function challanEnrichProductRow(&$row, $plantId, $conn) {
    $productCode = trim((string) ($row['product_code'] ?? ''));
    $batchNo = trim((string) ($row['batch_no'] ?? ''));
    $arNo = trim((string) ($row['ar_no'] ?? ''));
    $plantEsc = $conn->real_escape_string($plantId);

    if ($productCode !== '') {
        $sqlProd = "SELECT product_name, hsn FROM product
                    WHERE plant_id = '".$plantEsc."' AND TRIM(product_code) = '".$conn->real_escape_string($productCode)."'
                    LIMIT 1";
        $resProd = $conn->query($sqlProd);
        if ($resProd && $resProd->num_rows > 0) {
            $prod = $resProd->fetch_assoc();
            if (empty($row['product_name']) && !empty($prod['product_name'])) {
                $row['product_name'] = $prod['product_name'];
            }
            $hsn = trim((string) ($row['hsn'] ?? ''));
            if ($hsn === '' || strtoupper($hsn) === 'NA') {
                $row['hsn'] = !empty($prod['hsn']) ? $prod['hsn'] : 'NA';
            }
        }
    }

    $needsStockDates = empty($row['mfg_date']) || empty($row['exp_date']) || empty($row['pack_size']);
    if ($needsStockDates && $batchNo !== '') {
        $batchEsc = $conn->real_escape_string($batchNo);
        $codeEsc = $conn->real_escape_string($productCode);
        $arEsc = $conn->real_escape_string($arNo);
        $sqlStock = "SELECT mfg_date, exp_date, pack_size, unit, ar_no
                     FROM fg_stock_book
                     WHERE plant_id = '".$plantEsc."' AND TRIM(batch_no) = '".$batchEsc."'";
        if ($productCode !== '') {
            $sqlStock .= " AND TRIM(material_code) = '".$codeEsc."'";
        }
        if ($arNo !== '') {
            $sqlStock .= " AND TRIM(ar_no) = '".$arEsc."'";
        }
        $sqlStock .= " ORDER BY id DESC LIMIT 1";
        $resStock = $conn->query($sqlStock);
        if ($resStock && $resStock->num_rows > 0) {
            $stock = $resStock->fetch_assoc();
            if (empty($row['mfg_date']) && !empty($stock['mfg_date'])) {
                $row['mfg_date'] = $stock['mfg_date'];
            }
            if (empty($row['exp_date']) && !empty($stock['exp_date'])) {
                $row['exp_date'] = $stock['exp_date'];
            }
            if (empty($row['pack_size']) && !empty($stock['pack_size'])) {
                $row['pack_size'] = $stock['pack_size'];
            }
            if (empty($row['unit']) && !empty($stock['unit'])) {
                $row['unit'] = $stock['unit'];
            }
            if (empty($row['ar_no']) && !empty($stock['ar_no'])) {
                $row['ar_no'] = $stock['ar_no'];
            }
        }
    }

    if (empty($row['unit'])) {
        $row['unit'] = 'Kg';
    }
}

function challanFetchOrderProducts($orderId, $plantId, $gstType, $conn) {
    $products = array();
    $orderEsc = $conn->real_escape_string($orderId);
    $plantEsc = $conn->real_escape_string($plantId);
    $sql = "SELECT * FROM sales_product WHERE plant_id = '".$plantEsc."' AND orderId = '".$orderEsc."' ORDER BY id ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            challanEnrichProductRow($row, $plantId, $conn);
            challanRecalculateProductLine($row, $gstType);
            $products[] = $row;
        }
    }
    return $products;
}

function challanSummarizeProducts($products) {
    $summary = array(
        'taxableTotal' => 0,
        'taxAmtTotal' => 0,
        'netTotal' => 0,
        'igstTotal' => 0,
        'cgstTotal' => 0,
        'sgstTotal' => 0,
        'totalQty' => 0
    );
    foreach ($products as $row) {
        $summary['taxableTotal'] = roundChallanDecimal($summary['taxableTotal'] + (float) $row['taxable']);
        $summary['taxAmtTotal'] = roundChallanDecimal($summary['taxAmtTotal'] + (float) $row['taxAmt']);
        $summary['netTotal'] = roundChallanDecimal($summary['netTotal'] + (float) $row['netAmt']);
        $summary['igstTotal'] = roundChallanDecimal($summary['igstTotal'] + (float) $row['igst']);
        $summary['cgstTotal'] = roundChallanDecimal($summary['cgstTotal'] + (float) $row['cgst']);
        $summary['sgstTotal'] = roundChallanDecimal($summary['sgstTotal'] + (float) $row['sgst']);
        $summary['totalQty'] = roundChallanDecimal($summary['totalQty'] + (float) $row['requiredQty']);
    }
    return $summary;
}

function challanBuildAddressBlock($sales, $prefix) {
    $name = trim((string) ($sales[$prefix] ?? ''));
    $lines = array();
    if ($name !== '') {
        $lines[] = ($prefix === 'BillTo' ? 'M/s: ' : '').$name;
    }
    if (!empty($sales[$prefix.'Address'])) {
        $lines[] = $sales[$prefix.'Address'];
    }
    if (!empty($sales[$prefix.'City'])) {
        $lines[] = 'Dist - '.$sales[$prefix.'City'];
    }
    $cityPin = trim(($sales[$prefix.'City'] ?? '').' - '.($sales[$prefix.'Postal Code'] ?? ''));
    if ($cityPin !== ' - ') {
        $lines[] = $cityPin;
    }
    if (!empty($sales[$prefix.'State'])) {
        $lines[] = $sales[$prefix.'State'];
    }
    if (!empty($sales[$prefix.'GSTIN'])) {
        $lines[] = 'GSTIN: '.$sales[$prefix.'GSTIN'];
    }
    if (!empty($sales[$prefix.'PanNo'])) {
        $lines[] = 'PAN: '.$sales[$prefix.'PanNo'];
    }
    return trim(implode("\n", $lines));
}

$token = $_GET['token'] ?? '';
$timestamp = time();
$entry_date = date('Y-m-d h:i:s', $timestamp);
$_GET['emp_id'] = '';
$_GET['department'] = '';

$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

while ($row = $result->fetch_assoc()) {
    $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
    $string = explode('$', $string);
    $_GET['emp_id'] = $string[0];
    $_GET['department'] = $string[1];
    break;
}

$action = $_GET['type'] ?? 'deliveryChallanPDF';
$plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string($_GET['plant_id']) : '';
$txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$action.'", "actiontime": "'.$entry_date.'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

if ($action === 'getChallanOrders') {
    header('Content-Type: application/json');
    $output = array();
    $sqlOrders = "SELECT s.*, c.LglNm as clientName FROM sales s
                  LEFT JOIN client c ON s.client_code = c.client_code
                  WHERE s.plant_id = '".$plant_id."' ORDER BY s.id DESC";
    $resOrders = $conn->query($sqlOrders);
    if ($resOrders && $resOrders->num_rows > 0) {
        while ($salesRow = $resOrders->fetch_assoc()) {
            $products = challanFetchOrderProducts($salesRow['id'], $plant_id, $salesRow['gst_type'], $conn);
            $summary = challanSummarizeProducts($products);
            $salesRow['products'] = $products;
            $salesRow['taxableTotal'] = $summary['taxableTotal'];
            $salesRow['taxAmtTotal'] = $summary['taxAmtTotal'];
            $salesRow['netTotal'] = $summary['netTotal'];
            $salesRow['igstTotal'] = $summary['igstTotal'];
            $salesRow['cgstTotal'] = $summary['cgstTotal'];
            $salesRow['sgstTotal'] = $summary['sgstTotal'];
            $output[] = $salesRow;
        }
    }
    echo json_encode($output);
    exit;
}

if ($action !== 'deliveryChallanPDF') {
    http_response_code(400);
    echo json_encode(array('status' => 'failed', 'msg' => 'Invalid request type.'));
    exit;
}

if (!class_exists('MYPDFDeliveryChallan', false)) {
    class MYPDFDeliveryChallan extends TCPDF {
        public function Header() {}
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
        }
    }
}

$pdf = new MYPDFDeliveryChallan(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetMargins(8, 5, 8);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage('P');

$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

$plant_name = '';
$plant_full_name = '';
$plant_full_address = '';
$mobNo = '7778885053';
$gst_no = '';
$panNo = '';
$plant_state = 'Gujarat';
$state_code = '24';
$logoSrc = '';

$resolvePlantLogo = function ($logoPath) {
    $candidates = array();
    if ($logoPath !== null && trim((string) $logoPath) !== '') {
        $lp = str_replace('\\', '/', trim((string) $logoPath));
        $base = basename($lp);
        $candidates[] = __DIR__ . '/../logos/' . $base;
        $candidates[] = __DIR__ . '/../logos/' . ltrim($lp, '/');
        $candidates[] = __DIR__ . '/../' . ltrim($lp, '/');
    }
    $candidates[] = __DIR__ . '/../logos/apistar.jpg';
    $candidates[] = __DIR__ . '/../logos/apistar.png';
    $candidates[] = __DIR__ . '/../upload/User/logo.png';
    $candidates[] = __DIR__ . '/hk-logo.png';
    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            return str_replace('\\', '/', $candidate);
        }
    }
    return '';
};

$sql1 = "SELECT * FROM plant WHERE plant_id = '".$plant_id."' LIMIT 1";
$result1 = $conn->query($sql1);
if ($result1 && $result1->num_rows > 0) {
    $row1 = $result1->fetch_assoc();
    $plant_name = isset($row1['plant_name']) ? $row1['plant_name'] : '';
    $plant_full_name = !empty($row1['plant_full_name']) ? $row1['plant_full_name'] : $plant_name;
    $plant_full_address = isset($row1['plant_full_address']) ? $row1['plant_full_address'] : '';
    $gst_no = isset($row1['gst_no']) ? $row1['gst_no'] : '';
    $panNo = isset($row1['panNo']) ? $row1['panNo'] : '';
    $logoSrc = $resolvePlantLogo(isset($row1['logo_path']) ? $row1['logo_path'] : '');
}

$sqlCo = "SELECT * FROM company WHERE plant_id = '".$plant_id."' LIMIT 1";
$resCo = $conn->query($sqlCo);
if ($resCo && $resCo->num_rows > 0) {
    $co = $resCo->fetch_assoc();
    if (!empty($co['company_name'])) {
        $plant_full_name = trim($co['company_name']);
    }
    if (!empty($co['address'])) {
        $plant_full_address = trim($co['address']);
    }
    if (!empty($co['gst_no'])) {
        $gst_no = trim($co['gst_no']);
    }
    if (!empty($co['pan_no'])) {
        $panNo = trim($co['pan_no']);
    }
    if (!empty($co['present_state'])) {
        $plant_state = trim($co['present_state']);
    }
    if (!empty($co['scode'])) {
        $state_code = trim($co['scode']);
    }
}

if ($logoSrc === '') {
    $logoSrc = $resolvePlantLogo('');
}

if ($gst_no !== '' && strlen($gst_no) >= 12) {
    if ($state_code === '' || $state_code === null) {
        $state_code = substr($gst_no, 0, 2);
    }
    if ($panNo === '' || $panNo === null) {
        $panNo = substr($gst_no, 2, 10);
    }
}

$displayName = $plant_full_name !== '' ? $plant_full_name : $plant_name;
$displayNameUpper = strtoupper($displayName);
$phoneDisplay = $mobNo;

$bd = 'border:1px solid #000000;';
$cell = $bd . ' font-size:8px; padding:2px 3px; vertical-align:top;';
$boldCell = $cell . ' font-weight:bold;';

$logoHtml = '';
if ($logoSrc !== '') {
    $logoHtml = '<img src="'.$logoSrc.'" style="width:52px;height:52px;" alt="">';
}

$html = '';
$sql = "SELECT s.*, c.LglNm as clientName FROM sales s LEFT JOIN client c ON s.client_code = c.client_code
        WHERE s.id = '".$id."' AND s.plant_id = '".$plant_id."' LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $sales = $result->fetch_assoc();
    $products = challanFetchOrderProducts($sales['id'], $plant_id, $sales['gst_type'], $conn);
    $summary = challanSummarizeProducts($products);
    $productRowsHtml = '';
    $sr = 1;

    foreach ($products as $row) {
        $qty = (float) $row['requiredQty'];
        $unit = !empty($row['unit']) ? $row['unit'] : 'Kg';
        $descHtml = '<b>'.htmlspecialchars($row['product_name']).'</b><br>'.
            'BATCH NO :- '.htmlspecialchars($row['batch_no']).' = '.formatChallanPdfQty($qty).' '.htmlspecialchars($unit).'<br>'.
            'MFG DATE :- '.formatChallanPdfMonthYear($row['mfg_date']).'<br>'.
            'RETEST DATE :- '.formatChallanPdfMonthYear($row['exp_date']);

        $productRowsHtml .= '
            <tr>
                <td style="'.$cell.' text-align:center; width:8%;">'.$sr.'</td>
                <td style="'.$cell.' text-align:left; width:52%;">'.$descHtml.'</td>
                <td style="'.$cell.' text-align:center; width:20%;">'.htmlspecialchars($row['hsn']).'</td>
                <td style="'.$cell.' text-align:right; width:20%;">'.formatChallanPdfAmount($row['netAmt']).'</td>
            </tr>';
        $sr++;
    }

    $billBlock = challanBuildAddressBlock($sales, 'BillTo');
    if ($billBlock === '' && !empty($sales['clientName'])) {
        $billBlock = 'M/s: '.$sales['clientName'];
    }
    $shipBlock = challanBuildAddressBlock($sales, 'ShipTo');
    if ($shipBlock === '' && !empty($sales['clientName'])) {
        $shipBlock = $sales['clientName'];
    }

    $challanNo = !empty($sales['challan_no']) ? $sales['challan_no'] : (!empty($sales['orderNo']) ? $sales['orderNo'] : ('C'.$sales['id']));
    $challanDateRaw = !empty($sales['approve_date']) ? $sales['approve_date'] : ($sales['entry_date'] ?? '');
    $challanDate = formatChallanPdfDate($challanDateRaw);
    $validityDate = formatChallanPdfDate($sales['dueDate'] ?? '');
    $poNo = !empty($sales['po_no']) ? $sales['po_no'] : (!empty($sales['orderNo']) ? $sales['orderNo'] : '-');
    $eWayBill = formatChallanEWayBillNo($sales['eWayBillNo'] ?? '');

    $authSignEmpId = !empty($sales['approveBy']) ? $sales['approveBy']
        : (!empty($sales['entry_by']) ? $sales['entry_by'] : $_GET['emp_id']);
    $authSignSrc = resolveChallanPdfSignatureImage($authSignEmpId, $conn);
    $authSignName = getChallanEmployeeDisplayName($authSignEmpId, $conn);
    $authSignDateRaw = !empty($sales['approve_date']) ? $sales['approve_date'] : ($sales['entry_date'] ?? '');
    $authSignDate = formatChallanPdfDate($authSignDateRaw);
    $authSignImgHtml = $authSignSrc !== '' ? '<img src="'.$authSignSrc.'" style="width:80px;height:35px;" alt="">' : '';
    $authSignNameHtml = $authSignName !== '' ? htmlspecialchars($authSignName).'<br>' : '';
    $authSignDateHtml = $authSignDate !== '' ? htmlspecialchars($authSignDate).'<br>' : '';

    $html = '
    <table cellpadding="2" cellspacing="0" border="1" style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <tr>
            <td rowspan="3" style="'.$cell.' width:12%; text-align:center; vertical-align:middle;">'.$logoHtml.'</td>
            <td colspan="3" style="'.$boldCell.' text-align:center; color:#0000cc; font-size:13px;">'.htmlspecialchars($displayNameUpper).'</td>
        </tr>
        <tr>
            <td colspan="3" style="'.$cell.' text-align:center;">'.htmlspecialchars($plant_full_address).'</td>
        </tr>
        <tr>
            <td colspan="3" style="'.$cell.' text-align:center;">'.htmlspecialchars($plant_state).' - India. Phone : +91'.htmlspecialchars($phoneDisplay).'</td>
        </tr>
        <tr>
            <td colspan="2" style="'.$cell.' width:50%; vertical-align:top;">
                Mobile No. :- '.htmlspecialchars($mobNo).'<br>
                <b>GST No. :- '.htmlspecialchars($gst_no).'</b><br>
                State :- '.htmlspecialchars($plant_state).'&nbsp;&nbsp;Code :- '.htmlspecialchars($state_code).'<br>
                P. A. N. :- '.htmlspecialchars($panNo).'
            </td>
            <td colspan="2" style="'.$cell.' width:50%; vertical-align:top;">
                Challan No. :- '.htmlspecialchars($challanNo).'<br>
                Challan Date :- '.htmlspecialchars($challanDate).'<br>
                Validity Date :- '.htmlspecialchars($validityDate).'<br>
                P. O. No. :- '.htmlspecialchars($poNo).'<br>
                E-way Bill No. :- '.htmlspecialchars($eWayBill).'
            </td>
        </tr>
        <tr>
            <td colspan="4" style="'.$boldCell.' text-align:center; font-size:11px;">CHALLAN COPY</td>
        </tr>
        <tr>
            <td colspan="2" style="'.$boldCell.' width:50%;">Details of Receiver ( Billed to )</td>
            <td colspan="2" style="'.$boldCell.' width:50%;">Details of Consignee ( Shipped to )</td>
        </tr>
        <tr>
            <td colspan="2" style="'.$cell.' width:50%; height:70px;">'.nl2br(htmlspecialchars($billBlock)).'</td>
            <td colspan="2" style="'.$cell.' width:50%; height:70px;">'.nl2br(htmlspecialchars($shipBlock)).'</td>
        </tr>
        <tr>
            <td colspan="4" style="padding:0;">
                <table cellpadding="2" cellspacing="0" border="1" style="width:100%; border-collapse:collapse; table-layout:fixed;">
                    <tr>
                        <td style="'.$boldCell.' text-align:center; width:8%;">Sr No.</td>
                        <td style="'.$boldCell.' text-align:center; width:52%;">Description</td>
                        <td style="'.$boldCell.' text-align:center; width:20%;">HSN</td>
                        <td style="'.$boldCell.' text-align:center; width:20%;">Net Amount</td>
                    </tr>
                    '.$productRowsHtml.'
                    <tr>
                        <td style="'.$boldCell.' text-align:right;" colspan="2">TOTAL</td>
                        <td style="'.$cell.'">&nbsp;</td>
                        <td style="'.$boldCell.' text-align:right;">'.formatChallanPdfAmount($summary['netTotal']).'</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="'.$cell.' width:58%; vertical-align:top;">
                <b>Term and Conditions</b><br>
                1.If there are any quality issues, kindly inform us within seven days.<br>
                2.If all drum seals are found open, no return will be accepted.<br>
                3.Kindly conduct analysis before dispatching the material for export.<br>
                After dispatch, we will not be responsible for any queries or claims.
            </td>
            <td colspan="2" style="'.$cell.' width:42%; text-align:center; vertical-align:top;">
                <b>For '.htmlspecialchars($displayNameUpper).'</b><br><br>
                '.$authSignImgHtml.'<br>
                '.$authSignNameHtml.'
                '.$authSignDateHtml.'
                <span style="font-size:7px;">Authorised signature</span>
            </td>
        </tr>
    </table>';
}

if (ob_get_length()) {
    ob_end_clean();
}
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('delivery_challan.pdf', 'I');
exit;
