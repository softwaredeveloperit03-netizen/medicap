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

function ipps_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function ipps_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : ipps_pdf_esc($value);
}

function ipps_pdf_logo_url($conn, $plantId)
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

function ipps_pdf_sample_type_label($type)
{
    $map = array(
        'in_process' => 'In-process Product',
        'finished' => 'Finished Product',
        'stability' => 'Stability Product',
    );
    return isset($map[$type]) ? $map[$type] : ipps_pdf_esc($type);
}

function ipps_pdf_val($value)
{
    $v = trim((string) ($value ?? ''));
    return $v !== '' ? ipps_pdf_esc($v) : '&nbsp;';
}

function ipps_pdf_field_full($label, $value)
{
    return '<tr>
        <td colspan="4" style="font-size:6.5px;padding:4px 3px;">
            <b>' . ipps_pdf_esc($label) . '</b><br/>
            <span style="display:block;border-bottom:1px solid #000;min-height:14px;padding-top:2px;">' . ipps_pdf_val($value) . '</span>
        </td>
    </tr>';
}

function ipps_pdf_field_pair($label1, $value1, $label2, $value2)
{
    return '<tr>
        <td colspan="2" style="font-size:6.5px;padding:4px 3px;">
            <b>' . ipps_pdf_esc($label1) . '</b><br/>
            <span style="display:block;border-bottom:1px solid #000;min-height:14px;padding-top:2px;">' . ipps_pdf_val($value1) . '</span>
        </td>
        <td colspan="2" style="font-size:6.5px;padding:4px 3px;">
            <b>' . ipps_pdf_esc($label2) . '</b><br/>
            <span style="display:block;border-bottom:1px solid #000;min-height:14px;padding-top:2px;">' . ipps_pdf_val($value2) . '</span>
        </td>
    </tr>';
}

$res = $conn->query("SELECT * FROM qc_in_process_sampling
    WHERE id = '" . $recordId . "' AND plant_id = '" . $plantId . "' LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo 'Record not found';
    exit;
}

$record = $res->fetch_assoc();
$logoUrl = ipps_pdf_logo_url($conn, $plantId);
$hdrGrey = '#D3D3D3';
$fs = '6.5px';
$fsSm = '6px';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('SPECIAL SAMPLE REQUEST FORM');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$html = '<style>
    table.ipps-form, table.ipps-body { border-collapse: collapse; width: 100%; table-layout: fixed; }
    table.ipps-form td, table.ipps-body td { border: 1px solid #000000; vertical-align: top; }
</style>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:30%; text-align:left;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . ipps_pdf_esc($logoUrl) . '" width="90" height="40" />';
} else {
    $html .= '<span style="font-size:13px;font-weight:bold;color:#C41E3A;">Medicap</span><br/>
              <span style="font-size:10px;font-weight:bold;">Laboratories</span>';
}
$html .= '</td>
    <td style="width:40%;"></td>
    <td style="width:30%; text-align:right;font-size:8px;"><b>Ref: WI-QC-005-11</b></td>
</tr></table>';

$html .= '<table class="ipps-form" cellpadding="3" cellspacing="0" border="1" style="margin-bottom:6px;">
    <colgroup>
        <col style="width:16%;" /><col style="width:34%;" />
        <col style="width:25%;" /><col style="width:25%;" />
    </colgroup>
    <tr>
        <td style="font-size:' . $fs . ';font-weight:bold;background-color:' . $hdrGrey . ';">TITLE</td>
        <td colspan="2" style="font-size:7px;font-weight:bold;text-align:center;background-color:' . $hdrGrey . ';">
            SPECIAL SAMPLE REQUEST FORM: SAMPLES OF IN-PROCESS MATERIAL AND FINISHED PRODUCT
        </td>
        <td style="font-size:' . $fsSm . ';"><b>REFERENCE DOCUMENT CODE:</b><br/>FQC-005-11-A</td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';font-weight:bold;background-color:' . $hdrGrey . ';">DEPARTMENT</td>
        <td colspan="2" style="font-size:' . $fs . ';text-align:center;">QUALITY CONTROL</td>
        <td style="font-size:' . $fsSm . ';"><b>REFERENCE REVISION NO.:</b> 00</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:' . $fsSm . ';height:34px;vertical-align:top;">
            <b>DEPARTMENT APPROVAL:</b><br/><br/>
            <span style="font-size:' . $fsSm . ';">SIGNATURE</span>
            <span style="display:inline-block;width:52%;border-bottom:1px solid #000;">&nbsp;</span>
            &nbsp;&nbsp;<span style="font-size:' . $fsSm . ';">DATE</span> Jun 13/25
        </td>
        <td colspan="2" style="font-size:' . $fs . ';vertical-align:middle;"><b>EFFECTIVE DATE:</b> JUN 16 2025</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:' . $fsSm . ';height:34px;vertical-align:top;">
            <b>QA APPROVAL:</b><br/><br/>
            <span style="font-size:' . $fsSm . ';">SIGNATURE</span>
            <span style="display:inline-block;width:52%;border-bottom:1px solid #000;">&nbsp;</span>
            &nbsp;&nbsp;<span style="font-size:' . $fsSm . ';">DATE</span> Jun 16/25
        </td>
        <td colspan="2" style="font-size:' . $fs . ';vertical-align:middle;"><b>PAGE NO.:</b> 1 OF 1</td>
    </tr>
</table>';

$html .= '<table class="ipps-body" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:4px;">
    <colgroup>
        <col style="width:25%;" /><col style="width:25%;" />
        <col style="width:25%;" /><col style="width:25%;" />
    </colgroup>';

$html .= ipps_pdf_field_full('Product Name', $record['product_name']);
$html .= ipps_pdf_field_pair('Product Code', $record['product_code'], 'Lot Number', $record['lot_number']);
$html .= ipps_pdf_field_pair('Amount of sample requested', $record['amount_requested'], 'Amount of sample provided', $record['amount_provided']);

$html .= '<tr>
    <td colspan="4" style="font-size:6.5px;padding:4px 3px;">
        <b>Reason</b><br/>
        <span style="display:block;border-bottom:1px solid #000;min-height:14px;padding-top:2px;">' . ipps_pdf_val($record['reason']) . '</span>
        <span style="font-size:5.5px;font-style:italic;">(Complete Analytical Test Request Form FQC-005-06-A if testing is required)</span>
    </td>
</tr>';

$html .= ipps_pdf_field_full('Provide samples to', $record['provide_samples_to']);
$html .= ipps_pdf_field_pair(
    'Requested by',
    $record['requested_by'],
    'Date',
    ipps_pdf_date($record['requested_date'])
);
$html .= ipps_pdf_field_pair(
    'Sampled by',
    $record['sampled_by'],
    'Date',
    ipps_pdf_date($record['sampled_date'])
);

$html .= '</table>';

$metaRows = array();
if (trim((string) ($record['form_no'] ?? '')) !== '') {
    $metaRows[] = array('Form No.', $record['form_no']);
}
if (trim((string) ($record['sample_type'] ?? '')) !== '') {
    $metaRows[] = array('Sample Type', ipps_pdf_sample_type_label($record['sample_type']));
}
if (trim((string) ($record['lab_number'] ?? '')) !== '') {
    $metaRows[] = array('QC Lab Number', $record['lab_number']);
}
if (($record['testing_required'] ?? '') === 'Yes') {
    $metaRows[] = array('Testing Required', 'Yes');
}

if (count($metaRows) > 0) {
    $html .= '<table class="ipps-body" cellpadding="3" cellspacing="0" border="1" style="margin-top:4px;margin-bottom:4px;">
        <colgroup>
            <col style="width:25%;" /><col style="width:25%;" />
            <col style="width:25%;" /><col style="width:25%;" />
        </colgroup>
        <tr style="background-color:' . $hdrGrey . ';font-size:6px;font-weight:bold;text-align:center;">
            <td colspan="4">Record Information</td>
        </tr>';
    for ($i = 0; $i < count($metaRows); $i += 2) {
        $left = $metaRows[$i];
        $right = isset($metaRows[$i + 1]) ? $metaRows[$i + 1] : null;
        $html .= '<tr style="font-size:6px;">';
        $html .= '<td style="font-weight:bold;">' . ipps_pdf_esc($left[0]) . '</td><td>' . ipps_pdf_val($left[1]) . '</td>';
        if ($right) {
            $html .= '<td style="font-weight:bold;">' . ipps_pdf_esc($right[0]) . '</td><td>' . ipps_pdf_val($right[1]) . '</td>';
        } else {
            $html .= '<td colspan="2">&nbsp;</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
}

$workflowRows = array();
if (trim((string) ($record['received_by'] ?? '')) !== '') {
    $workflowRows[] = 'QC Received: ' . $record['received_by'] . ' | ' . ipps_pdf_date($record['received_date']);
}
if (trim((string) ($record['testing_by'] ?? '')) !== '') {
    $workflowRows[] = 'Testing: ' . $record['testing_by'] . ' | ' . ipps_pdf_date($record['testing_date']);
}
if (trim((string) ($record['reviewed_by'] ?? '')) !== '') {
    $workflowRows[] = 'QC Review: ' . $record['reviewed_by'] . ' | ' . ipps_pdf_date($record['reviewed_date']);
}
if (trim((string) ($record['results_entered_by'] ?? '')) !== '') {
    $workflowRows[] = 'Analyst: ' . $record['results_entered_by'] . ' | ' . ipps_pdf_date($record['results_entered_date']);
}
if (trim((string) ($record['approved_by'] ?? '')) !== '') {
    $workflowRows[] = 'QC Approved: ' . $record['approved_by'] . ' | ' . ipps_pdf_date($record['approved_date']);
}

if (count($workflowRows) > 0) {
    $html .= '<table class="ipps-body" cellpadding="3" cellspacing="0" border="1" style="margin-top:4px;">
        <colgroup><col style="width:100%;" /></colgroup>
        <tr style="background-color:' . $hdrGrey . ';font-size:6px;font-weight:bold;text-align:center;">
            <td>Workflow Sign-offs</td>
        </tr>';
    foreach ($workflowRows as $line) {
        $html .= '<tr><td style="font-size:6px;">' . ipps_pdf_esc($line) . '</td></tr>';
    }
    $html .= '</table>';
}

$pdf->writeHTML($html, true, false, true, false, '');

$safeName = 'IPPS_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $record['form_no'] ?? ('ID_' . $recordId));
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
