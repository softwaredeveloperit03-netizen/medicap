<?php
date_default_timezone_set('Asia/Kolkata');
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$recordId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($recordId <= 0) {
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

function fps_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function fps_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : fps_pdf_esc($value);
}

function fps_pdf_logo_url($conn, $plantId)
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

function fps_pdf_json_decode($str)
{
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function fps_pdf_default_test_rows()
{
    return array(
        array('key' => 'description_id', 'label' => 'Description/Identification', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'assay_impurities', 'label' => 'Assay and Organic Impurities', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'uniformity', 'label' => 'Uniformity', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'water_content', 'label' => 'Water Content', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'dissolution', 'label' => 'Dissolution', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'microbial_limits', 'label' => 'Microbial Limits (where applicable)', 'required_qty' => '', 'sampled_qty' => ''),
        array('key' => 'total_qty', 'label' => 'Total Quantity Required for Full testing (excluding microbial limits)', 'required_qty' => '', 'sampled_qty' => ''),
    );
}

function fps_pdf_merge_test_rows($inputRows)
{
    $defaults = fps_pdf_default_test_rows();
    if (!is_array($inputRows)) {
        return $defaults;
    }
    $map = array();
    foreach ($inputRows as $row) {
        if (!empty($row['key'])) {
            $map[$row['key']] = $row;
        }
    }
    foreach ($defaults as $i => $def) {
        if (isset($map[$def['key']])) {
            $defaults[$i]['required_qty'] = $map[$def['key']]['required_qty'] ?? '';
            $defaults[$i]['sampled_qty'] = $map[$def['key']]['sampled_qty'] ?? '';
        }
    }
    return $defaults;
}

function fps_pdf_val($value)
{
    $v = trim((string) ($value ?? ''));
    return $v !== '' ? fps_pdf_esc($v) : '';
}

function fps_pdf_underline($value, $width = '68%')
{
    $display = fps_pdf_val($value);
    if ($display === '') {
        $display = '&nbsp;';
    }
    return '<span style="display:inline-block;width:' . $width . ';border-bottom:1px solid #000;min-height:11px;line-height:11px;">' . $display . '</span>';
}

function fps_pdf_product_row($label1, $value1, $label2, $value2)
{
    return '<tr>
        <td colspan="2" style="font-size:7px;padding:5px 0;border:none;">
            <b>' . fps_pdf_esc($label1) . '</b> ' . fps_pdf_underline($value1, '72%') . '
        </td>
        <td colspan="2" style="font-size:7px;padding:5px 0;border:none;">
            <b>' . fps_pdf_esc($label2) . '</b> ' . fps_pdf_underline($value2, '72%') . '
        </td>
    </tr>';
}

function fps_pdf_approval_cell($sigValue)
{
    $sig = fps_pdf_val($sigValue);
    $line = $sig !== '' ? fps_pdf_esc($sig) : '&nbsp;';
    return '<span style="display:block;border-bottom:1px solid #000;min-height:14px;">' . $line . '</span>
        <span style="font-size:5.5px;">SIGNATURE</span>';
}

function fps_pdf_date_cell($dateValue, $fallback = '')
{
    $dt = fps_pdf_date($dateValue);
    if ($dt === '' && $fallback !== '') {
        $dt = fps_pdf_esc($fallback);
    }
    return '<span style="display:block;border-bottom:1px solid #000;min-height:14px;">' . ($dt !== '' ? $dt : '&nbsp;') . '</span>
        <span style="font-size:5.5px;">DATE</span>';
}

function fps_pdf_sign_line($label, $by, $date)
{
    return '<div style="margin-top:5px;">
        <span style="font-size:6.5px;">
            <b>' . fps_pdf_esc($label) . ':</b> ' . fps_pdf_underline($by, '32%') . '
            &nbsp;&nbsp;<b>Date:</b> ' . fps_pdf_underline(fps_pdf_date($date), '18%') . '
        </span>
        <div style="font-size:5.5px;text-align:center;padding-top:1px;">(signature)</div>
    </div>';
}

function fps_pdf_test_table($title, $qtyHeader, $testRows, $qtyField)
{
    $hdrGrey = '#D3D3D3';
    $html = '<div style="font-size:8px;font-weight:bold;margin:10px 0 4px;">' . fps_pdf_esc($title) . '</div>';
    $html .= '<table class="fps-grid" cellpadding="4" cellspacing="0" border="1" style="margin-bottom:2px;">
        <colgroup>
            <col style="width:6%;" /><col style="width:44%;" /><col style="width:50%;" />
        </colgroup>
        <tr style="font-size:6px;font-weight:bold;text-align:center;">
            <td style="background-color:' . $hdrGrey . ';">#</td>
            <td style="background-color:' . $hdrGrey . ';">Name of Test</td>
            <td style="background-color:' . $hdrGrey . ';">' . fps_pdf_esc($qtyHeader) . '</td>
        </tr>';

    $i = 1;
    foreach ($testRows as $row) {
        $qty = fps_pdf_val($row[$qtyField] ?? '');
        $html .= '<tr style="font-size:6px;">
            <td style="text-align:center;">' . $i . '</td>
            <td style="text-align:left;">' . fps_pdf_esc($row['label']) . '</td>
            <td style="text-align:center;">' . ($qty !== '' ? fps_pdf_esc($qty) : '&nbsp;') . '</td>
        </tr>';
        $i++;
    }
    $html .= '</table>';
    return $html;
}

$res = $conn->query("SELECT * FROM qc_finished_product_sampling
    WHERE id = '" . $recordId . "' AND plant_id = '" . $plantId . "' LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo 'Record not found';
    exit;
}

$record = $res->fetch_assoc();
$testRows = fps_pdf_merge_test_rows(fps_pdf_json_decode($record['test_rows'] ?? ''));
$logoUrl = fps_pdf_logo_url($conn, $plantId);
$hdrGrey = '#D3D3D3';
$fs = '6.5px';
$fsSm = '6px';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('FINISHED PRODUCT SAMPLING PLAN FOR FULL TESTING');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$html = '<style>
    table.fps-form, table.fps-grid, table.fps-body { border-collapse: collapse; width: 100%; table-layout: fixed; }
    table.fps-form td, table.fps-grid td { border: 1px solid #000000; vertical-align: top; }
    table.fps-body td { border: none; vertical-align: top; }
</style>';

$html .= '<table cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-bottom:4px;"><tr>
    <td style="width:28%; text-align:left; vertical-align:top;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . fps_pdf_esc($logoUrl) . '" width="95" height="42" />';
} else {
    $html .= '<span style="font-size:14px;font-weight:bold;color:#C41E3A;">Medicap</span><br/>
              <span style="font-size:11px;font-weight:bold;">Laboratories</span>';
}
$html .= '</td><td style="width:72%;"></td></tr></table>';

$html .= '<table class="fps-form" cellpadding="4" cellspacing="0" border="1" style="margin-bottom:8px;">
    <colgroup>
        <col style="width:14%;" /><col style="width:28%;" /><col style="width:16%;" />
        <col style="width:22%;" /><col style="width:20%;" />
    </colgroup>
    <tr>
        <td style="font-size:' . $fsSm . ';font-weight:bold;background-color:' . $hdrGrey . ';">TITLE</td>
        <td colspan="2" style="font-size:7px;font-weight:bold;text-align:center;vertical-align:middle;">
            FINISHED PRODUCT SAMPLING PLAN FOR FULL TESTING
        </td>
        <td style="font-size:' . $fsSm . ';font-weight:bold;background-color:' . $hdrGrey . ';">FORM NO.</td>
        <td style="font-size:' . $fsSm . ';text-align:center;">FQC-005-12-A</td>
    </tr>
    <tr>
        <td style="font-size:' . $fsSm . ';font-weight:bold;vertical-align:top;">DEPARTMENT APPROVAL:</td>
        <td style="font-size:' . $fsSm . ';vertical-align:bottom;">' . fps_pdf_approval_cell('') . '</td>
        <td style="font-size:' . $fsSm . ';vertical-align:bottom;">' . fps_pdf_date_cell('', 'Jun 13/25') . '</td>
        <td style="font-size:' . $fsSm . ';font-weight:bold;background-color:' . $hdrGrey . ';">REVISION NO.</td>
        <td style="font-size:' . $fsSm . ';text-align:center;">00</td>
    </tr>
    <tr>
        <td style="font-size:' . $fsSm . ';font-weight:bold;vertical-align:top;">QA APPROVAL:</td>
        <td style="font-size:' . $fsSm . ';vertical-align:bottom;">' . fps_pdf_approval_cell('') . '</td>
        <td style="font-size:' . $fsSm . ';vertical-align:bottom;">' . fps_pdf_date_cell('', 'Jun 16/25') . '</td>
        <td style="font-size:' . $fsSm . ';font-weight:bold;background-color:' . $hdrGrey . ';">EFFECTIVE DATE</td>
        <td style="font-size:' . $fsSm . ';text-align:center;">JUN 16 2025</td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
        <td style="font-size:' . $fsSm . ';font-weight:bold;background-color:' . $hdrGrey . ';">PAGE NO.</td>
        <td style="font-size:' . $fsSm . ';text-align:center;">1 OF 1</td>
    </tr>
</table>';

$html .= '<div style="font-size:8px;font-weight:bold;margin:0 0 4px;">1.0 Product Information:</div>';
$html .= '<table class="fps-body" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:4px;">
    <colgroup>
        <col style="width:25%;" /><col style="width:25%;" />
        <col style="width:25%;" /><col style="width:25%;" />
    </colgroup>';
$html .= fps_pdf_product_row('Product Name:', $record['product_name'], 'Strength:', $record['strength']);
$html .= fps_pdf_product_row('Lot No:', $record['lot_no'], 'Code No:', $record['code_no']);
$html .= '</table>';

$html .= fps_pdf_test_table(
    '2.0 Sampling Quantity for Full Testing',
    'Quantity Required for Full-Testing as per Release Specification',
    $testRows,
    'required_qty'
);

$html .= fps_pdf_sign_line('Quality Control Manager Approval', $record['qc_manager_by'] ?? '', $record['qc_manager_date'] ?? '');
$html .= fps_pdf_sign_line('QA Approval', $record['qa_approved_by'] ?? '', $record['qa_approved_date'] ?? '');

$html .= fps_pdf_test_table(
    '3.0 Quantity Sampled by Production',
    'Quantity Sampled by Production',
    $testRows,
    'sampled_qty'
);

$html .= fps_pdf_sign_line('Production Supervisor', $record['production_supervisor_by'] ?? '', $record['production_supervisor_date'] ?? '');

$html .= '<div style="font-size:6.5px;text-align:center;margin-top:14px;">&quot;Confidential Information: Do Not Disclose without Authorization&quot;</div>';

$pdf->writeHTML($html, true, false, true, false, '');

$safeName = 'FPS_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $record['form_no'] ?? ('ID_' . $recordId));
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
