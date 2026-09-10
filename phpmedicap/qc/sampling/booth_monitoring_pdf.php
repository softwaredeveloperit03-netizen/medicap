<?php
date_default_timezone_set('Asia/Kolkata');
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$sheetId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sheetId <= 0) {
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

function bm_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function bm_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : bm_pdf_esc($value);
}

function bm_pdf_logo_url($conn, $plantId)
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

function bm_pdf_instrument_display($entry)
{
    $name = trim((string) ($entry['equipment_name'] ?? ''));
    $id = trim((string) ($entry['instrument_id'] ?? ''));
    if ($name !== '' && $id !== '') {
        return $name . ' (' . $id . ')';
    }
    return $id !== '' ? $id : $name;
}

function bm_pdf_entry_has_data($entry)
{
    $textFields = array(
        'instrument_id', 'equipment_name', 'air_velocity',
        'magnehelic_reading_1', 'magnehelic_reading_2', 'magnehelic_reading_3',
        'performed_by', 'reviewed_by', 'reviewed_by_name'
    );
    foreach ($textFields as $field) {
        if (trim((string) ($entry[$field] ?? '')) !== '') {
            return true;
        }
    }
    if (!empty($entry['entry_date']) && $entry['entry_date'] !== '0000-00-00') {
        return true;
    }
    if (!empty($entry['calibration_due']) && $entry['calibration_due'] !== '0000-00-00') {
        return true;
    }
    return false;
}

$sqlSheet = "SELECT s.*,
        (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.entry_by LIMIT 1) AS entry_by_name,
        (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.reviewed_by LIMIT 1) AS reviewed_by_name
        FROM sampling_booth_monitoring_sheet s
        WHERE s.id = '" . $sheetId . "' AND s.plant_id = '" . $plantId . "' LIMIT 1";
$resSheet = $conn->query($sqlSheet);
if (!$resSheet || $resSheet->num_rows === 0) {
    echo 'Log sheet not found';
    exit;
}
$sheet = $resSheet->fetch_assoc();

$entries = array();
$sqlEntries = "SELECT e.*,
        (SELECT CONCAT(em.firstname, ' ', em.lastname) FROM employee em WHERE em.emp_id = e.reviewed_by LIMIT 1) AS reviewed_by_name
        FROM sampling_booth_monitoring_entry e
        WHERE e.sheet_id = '" . $sheetId . "'
        ORDER BY e.row_order ASC, e.id ASC";
$resEntries = $conn->query($sqlEntries);
if ($resEntries && $resEntries->num_rows > 0) {
    while ($entry = $resEntries->fetch_assoc()) {
        if (empty($entry['reviewed_by_name']) && !empty($entry['reviewed_by'])) {
            $entry['reviewed_by_name'] = $entry['reviewed_by'];
        }
        $entries[] = $entry;
    }
}

$entries = array_values(array_filter($entries, 'bm_pdf_entry_has_data'));

$logoUrl = bm_pdf_logo_url($conn, $plantId);
$sheetNo = bm_pdf_esc($sheet['sheet_no']);
$logDate = bm_pdf_date($sheet['log_date']);

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('SAMPLING BOOTH MONITORING');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$hdrGrey = '#B8B8B8';
$hdrGrey2 = '#C8C8C8';

$html = '<style>
    table.bm-form-table, table.bm-data-table { border-collapse: collapse; width: 100%; }
    table.bm-form-table td, table.bm-data-table td {
        border: 1px solid #000000;
        vertical-align: middle;
    }
    .bm-hdr-main { background-color: ' . $hdrGrey . '; font-weight: bold; text-align: center; }
    .bm-hdr-sub { background-color: ' . $hdrGrey2 . '; font-weight: bold; text-align: center; }
    .bm-cell-center { text-align: center; }
    .bm-cell-left { text-align: left; }
</style>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;">
    <tr>
        <td style="width:28%; text-align:left;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . bm_pdf_esc($logoUrl) . '" width="95" height="42" />';
} else {
    $html .= '<span style="font-size:14px; font-weight:bold; color:#C41E3A;">Medicap</span><br/>
              <span style="font-size:11px; font-weight:bold;">Laboratories</span>';
}
$html .= '</td>
        <td style="width:44%;"></td>
        <td style="width:28%; text-align:right; font-size:8px;"><b>Ref: WI-QC-005-01</b></td>
    </tr>
</table>';

$html .= '<table class="bm-form-table" cellpadding="4" cellspacing="0" border="1" style="width:100%; margin-bottom:6px;">
    <tr>
        <td colspan="4" style="text-align:center; font-size:9px; font-weight:bold; background-color:#EFEFEF;">
            TITLE: SAMPLING BOOTH MONITORING MAGNEHELIC GAUGES HEPA FILTERS AND AIR VELOCITY
        </td>
    </tr>
    <tr>
        <td style="width:25%; font-size:7px;"><b>FORM NO.:</b> FQC-005-01-B</td>
        <td style="width:25%; font-size:7px;"><b>REVISION NO.:</b> 01</td>
        <td style="width:25%; font-size:7px;"><b>EFFECTIVE DATE:</b> NOV 26 2025</td>
        <td style="width:25%; font-size:7px;"><b>PAGE NO.:</b> 1 OF 1</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:7px; height:34px; vertical-align:top;">
            <b>DEPARTMENT APPROVAL:</b><br/><br/>
            <span style="font-size:6px;">Nov 26/25</span>
        </td>
        <td colspan="2" style="font-size:7px; height:34px; vertical-align:top;">
            <b>QA APPROVAL:</b><br/><br/>
            <span style="font-size:6px;">Nov 26/25</span>
        </td>
    </tr>
</table>';

$html .= '<table class="bm-data-table" cellpadding="4" cellspacing="0" border="1" style="width:100%; table-layout:fixed;">
    <tr class="bm-hdr-main">
        <td rowspan="2" style="width:10%; font-size:7px;">Date</td>
        <td rowspan="2" style="width:22%; font-size:7px;">Instrument ID</td>
        <td rowspan="2" style="width:11%; font-size:7px;">Calibration Due</td>
        <td rowspan="2" style="width:12%; font-size:7px;">Air Velocity*<br/>
            <span style="font-size:6px; font-weight:normal;">(72&ndash;108 ft/min)</span></td>
        <td colspan="3" style="width:18%; font-size:7px;">Magnehelic Gauge Reading<br/>
            <span style="font-size:6px; font-weight:normal;">Limit: NLT 0.02&quot; WC</span></td>
        <td rowspan="2" style="width:13.5%; font-size:7px;">Performed By</td>
        <td rowspan="2" style="width:13.5%; font-size:7px;">Reviewed By</td>
    </tr>
    <tr class="bm-hdr-sub">
        <td style="width:6%; font-size:6.5px;">#1</td>
        <td style="width:6%; font-size:6.5px;">#2</td>
        <td style="width:6%; font-size:6.5px;">#3</td>
    </tr>';

foreach ($entries as $entry) {
    $html .= '<tr>
        <td class="bm-cell-center" style="font-size:6.5px; height:24px;">' . bm_pdf_date($entry['entry_date']) . '</td>
        <td class="bm-cell-left" style="font-size:6.5px;">' . bm_pdf_esc(bm_pdf_instrument_display($entry)) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_date($entry['calibration_due']) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_esc($entry['air_velocity']) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_esc($entry['magnehelic_reading_1']) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_esc($entry['magnehelic_reading_2']) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_esc($entry['magnehelic_reading_3']) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_esc($entry['performed_by']) . '</td>
        <td class="bm-cell-center" style="font-size:6.5px;">' . bm_pdf_esc($entry['reviewed_by_name']) . '</td>
    </tr>';
}

$html .= '</table>';

$html .= '<br/>
<table cellpadding="1" cellspacing="0" border="0" style="width:100%;">
    <tr>
        <td style="width:75%; font-size:6.5px;">
            * Air Velocity acceptable range: 72&ndash;108 ft/min.<br/>
            Magnehelic gauge readings must be not less than (NLT) 0.02&quot; WC.
        </td>
        <td style="width:25%; font-size:7px; text-align:right;">
            <b>Sheet No.:</b> ' . $sheetNo . ($logDate !== '' ? '<br/><span style="font-size:6px;">Log Date: ' . $logDate . '</span>' : '') . '
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$safeName = 'Booth_Monitoring_Log_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $sheet['sheet_no'] ?? 'export');
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
