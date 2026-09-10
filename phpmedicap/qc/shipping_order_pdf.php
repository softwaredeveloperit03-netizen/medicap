<?php
date_default_timezone_set('Asia/Kolkata');
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    echo 'Invalid id';
    exit;
}

$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['plant_id'] = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';

if (!$result || $result->num_rows === 0) {
    echo 'Invalid token';
    exit;
}

while ($row = $result->fetch_assoc()) {
    $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
    $string = explode('$', $string);
    $_GET['emp_id'] = $string[0];
    break;
}

$plantId = $conn->real_escape_string($_GET['plant_id']);

function so_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function so_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : so_pdf_esc($value);
}

function so_pdf_logo_url($conn, $plantId)
{
    $res = $conn->query("SELECT logo_path FROM plant WHERE plant_id='" . $plantId . "' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['logo_path'])) {
            return 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/' . $row['logo_path'];
        }
    }
    return '';
}

function so_pdf_multiline($value)
{
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return '&nbsp;';
    }
    return nl2br(so_pdf_esc($text));
}

function so_pdf_line_has_data($line)
{
    if (!is_array($line)) {
        return false;
    }
    $fields = array('lab_sample_no', 'quantity', 'lot_batch_no', 'description');
    foreach ($fields as $field) {
        if (trim((string) ($line[$field] ?? '')) !== '') {
            return true;
        }
    }
    return false;
}

$res = $conn->query("SELECT o.*,
        (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.packaged_by_emp LIMIT 1) AS packaged_by_name,
        (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.checked_by_emp LIMIT 1) AS checked_by_name
        FROM qc_shipping_order o
        WHERE o.id = '" . $orderId . "' AND o.plant_id = '" . $plantId . "' LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo 'Shipping order not found';
    exit;
}

$order = $res->fetch_assoc();
$lines = array();
$lineRes = $conn->query("SELECT * FROM qc_shipping_order_line
    WHERE shipping_order_id = '" . $orderId . "'
    ORDER BY row_order ASC, id ASC");
if ($lineRes && $lineRes->num_rows > 0) {
    while ($row = $lineRes->fetch_assoc()) {
        if (so_pdf_line_has_data($row)) {
            $lines[] = $row;
        }
    }
}

$logoUrl = so_pdf_logo_url($conn, $plantId);
$hdrGrey = '#EFEFEF';
$fs = '6.5px';
$fsSm = '6px';

$packagedBy = trim((string) ($order['packaged_by_name'] ?? ''));
if ($packagedBy === '') {
    $packagedBy = trim((string) ($order['packaged_by'] ?? ''));
}
$checkedBy = trim((string) ($order['checked_by_name'] ?? ''));
if ($checkedBy === '') {
    $checkedBy = trim((string) ($order['checked_by'] ?? ''));
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('SHIPPING ORDER FORM');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$html = '<style>
    table.so-form, table.so-grid { border-collapse: collapse; width: 100%; table-layout: fixed; }
    table.so-form td, table.so-grid td { border: 1px solid #000000; vertical-align: top; }
</style>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:30%; text-align:left;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . so_pdf_esc($logoUrl) . '" width="90" height="40" />';
} else {
    $html .= '<span style="font-size:13px;font-weight:bold;color:#C41E3A;">Medicap</span><br/><span style="font-size:10px;font-weight:bold;">Laboratories</span>';
}
$html .= '</td><td style="width:40%;"></td>
    <td style="width:30%; text-align:right;font-size:8px;"><b>Ref: WI-QC-005-09</b></td></tr></table>';

$html .= '<table class="so-form" cellpadding="3" cellspacing="0" border="1" style="margin-bottom:5px;">
    <colgroup>
        <col style="width:14%;" /><col style="width:32%;" />
        <col style="width:22%;" /><col style="width:32%;" />
    </colgroup>
    <tr>
        <td style="font-size:' . $fs . ';font-weight:bold;">TITLE</td>
        <td colspan="2" style="text-align:center;font-size:9px;font-weight:bold;background-color:' . $hdrGrey . ';">SHIPPING ORDER FORM</td>
        <td style="font-size:' . $fs . ';"><b>FORM NO.:</b> FQC-005-09-A</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:' . $fs . ';height:32px;vertical-align:top;"><b>DEPARTMENT APPROVAL:</b><br/><span style="font-size:' . $fsSm . ';">Jun 13/25</span></td>
        <td style="font-size:' . $fs . ';"><b>REVISION NO.:</b> 00</td>
        <td style="font-size:' . $fs . ';"><b>PAGE NO.:</b> 1 of 1</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:' . $fs . ';height:32px;vertical-align:top;"><b>QA APPROVAL:</b><br/><span style="font-size:' . $fsSm . ';">Jun 16/25</span></td>
        <td colspan="2" style="font-size:' . $fs . ';"><b>EFFECTIVE DATE:</b> JUN 16 2025</td>
    </tr>
</table>';

$html .= '<table class="so-grid" cellpadding="4" cellspacing="0" border="1" style="margin-bottom:5px;">
    <colgroup>
        <col style="width:12%;" /><col style="width:38%;" />
        <col style="width:12%;" /><col style="width:38%;" />
    </colgroup>
    <tr>
        <td style="font-size:' . $fs . ';font-weight:bold;">Ship To</td>
        <td style="font-size:' . $fs . ';">' . so_pdf_esc($order['ship_to']) . '</td>
        <td style="font-size:' . $fs . ';font-weight:bold;">From</td>
        <td style="font-size:' . $fs . ';font-weight:bold;">Medicap Laboratories</td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';font-weight:bold;">P.O.#</td>
        <td style="font-size:' . $fs . ';">' . so_pdf_esc($order['po_number']) . '</td>
        <td style="font-size:' . $fs . ';font-weight:bold;">Address</td>
        <td style="font-size:' . $fs . ';">
            30 Worcester Rd.<br/>
            Toronto, Ontario, M9W 5X2<br/>
            <b>Tel:</b> ' . so_pdf_esc($order['from_tel']) . ' &nbsp;&nbsp; <b>Fax:</b> ' . so_pdf_esc($order['from_fax']) . '
        </td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';font-weight:bold;">Address</td>
        <td colspan="3" style="font-size:' . $fs . ';">' . so_pdf_multiline($order['ship_to_address']) . '</td>
    </tr>';

if (trim((string) ($order['quotation_ref'] ?? '')) !== '') {
    $html .= '<tr>
        <td style="font-size:' . $fs . ';font-weight:bold;">Quotation Ref</td>
        <td colspan="3" style="font-size:' . $fs . ';">' . so_pdf_esc($order['quotation_ref']) . '</td>
    </tr>';
}

$html .= '</table>';

if (trim((string) ($order['tests_required'] ?? '')) !== '') {
    $html .= '<table class="so-grid" cellpadding="4" cellspacing="0" border="1" style="margin-bottom:4px;">
        <colgroup>
            <col style="width:18%;" /><col style="width:82%;" />
        </colgroup>
        <tr>
            <td style="font-size:' . $fs . ';font-weight:bold;">Tests Required</td>
            <td style="font-size:' . $fs . ';">' . so_pdf_multiline($order['tests_required']) . '</td>
        </tr>
    </table>';
}

$html .= '<table class="so-grid" cellpadding="4" cellspacing="0" border="1" style="margin-bottom:8px;">
    <colgroup>
        <col style="width:22%;" /><col style="width:12%;" />
        <col style="width:22%;" /><col style="width:44%;" />
    </colgroup>
    <tr style="background-color:' . $hdrGrey . ';font-size:' . $fs . ';font-weight:bold;text-align:center;">
        <td>Lab sample #</td>
        <td>Quantity</td>
        <td>Lot/Batch#</td>
        <td>Description</td>
    </tr>';

if (count($lines) > 0) {
    foreach ($lines as $line) {
        $html .= '<tr style="font-size:' . $fs . ';">
            <td style="text-align:center;">' . so_pdf_esc($line['lab_sample_no']) . '</td>
            <td style="text-align:center;">' . so_pdf_esc($line['quantity']) . '</td>
            <td style="text-align:center;">' . so_pdf_esc($line['lot_batch_no']) . '</td>
            <td>' . so_pdf_esc($line['description']) . '</td>
        </tr>';
    }
} else {
    for ($i = 0; $i < 4; $i++) {
        $html .= '<tr style="font-size:' . $fs . ';height:18px;">
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>';
    }
}

$html .= '</table>';

$html .= '<table class="so-grid" cellpadding="4" cellspacing="0" border="1">
    <colgroup>
        <col style="width:18%;" /><col style="width:32%;" />
        <col style="width:10%;" /><col style="width:40%;" />
    </colgroup>
    <tr style="font-size:' . $fs . ';">
        <td style="font-weight:bold;">Packaged By</td>
        <td>' . so_pdf_esc($packagedBy) . '</td>
        <td style="font-weight:bold;">Date</td>
        <td>' . so_pdf_esc(so_pdf_date($order['packaged_date'])) . '</td>
    </tr>
    <tr style="font-size:' . $fs . ';">
        <td style="font-weight:bold;">Checked By</td>
        <td>' . so_pdf_esc($checkedBy) . '</td>
        <td style="font-weight:bold;">Date</td>
        <td>' . so_pdf_esc(so_pdf_date($order['checked_date'])) . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

$safeName = 'ShippingOrder_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $order['order_no'] ?? ('ID_' . $orderId));
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
