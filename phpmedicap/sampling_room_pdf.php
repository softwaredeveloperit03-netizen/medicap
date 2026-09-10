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

function sr_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function sr_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : sr_pdf_esc($value);
}

function sr_pdf_checkbox($value)
{
    return strtoupper((string) $value) === 'YES'
        ? '<span style="font-family:dejavusans;">&#9745;</span>'
        : '<span style="font-family:dejavusans;">&#9744;</span>';
}

function sr_pdf_logo_url($conn, $plantId)
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

$sqlSheet = "SELECT s.*,
        (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.entry_by LIMIT 1) AS entry_by_name,
        (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.verified_by LIMIT 1) AS verified_by_name
        FROM sampling_room_log_sheet s
        WHERE s.id = '" . $sheetId . "' AND s.plant_id = '" . $plantId . "' LIMIT 1";
$resSheet = $conn->query($sqlSheet);
if (!$resSheet || $resSheet->num_rows === 0) {
    echo 'Log sheet not found';
    exit;
}
$sheet = $resSheet->fetch_assoc();

$entries = array();
$sqlEntries = "SELECT e.*,
        (SELECT CONCAT(em.firstname, ' ', em.lastname) FROM employee em WHERE em.emp_id = e.verified_by LIMIT 1) AS verified_by_name
        FROM sampling_room_log_entry e
        WHERE e.sheet_id = '" . $sheetId . "'
        ORDER BY e.row_order ASC, e.id ASC";
$resEntries = $conn->query($sqlEntries);
if ($resEntries && $resEntries->num_rows > 0) {
    while ($entry = $resEntries->fetch_assoc()) {
        if (empty($entry['verified_by_name']) && !empty($entry['verified_by'])) {
            $entry['verified_by_name'] = $entry['verified_by'];
        }
        $entries[] = $entry;
    }
}

$minRows = 7;
while (count($entries) < $minRows) {
    $entries[] = array(
        'material_description' => '',
        'material_lot_no' => '',
        'cleaning_full' => 'No',
        'cleaning_partial' => 'No',
        'cleaning_agent_name' => '',
        'cleaning_agent_lot' => '',
        'cleaning_agent_expiry' => '',
        'balance_cleaned' => 'No',
        'balance_verified' => 'No',
        'magnehelic_reading_1' => '',
        'magnehelic_reading_2' => '',
        'magnehelic_reading_3' => '',
        'performed_by' => '',
        'verified_by_name' => '',
    );
}

$logoUrl = sr_pdf_logo_url($conn, $plantId);
$sheetNo = sr_pdf_esc($sheet['sheet_no']);
$logDate = sr_pdf_date($sheet['log_date']);

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('SAMPLING ROOM - USAGE & CLEANING LOG');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$hdrGrey = '#B8B8B8';
$hdrGrey2 = '#C8C8C8';

$html = '<style>
    .form-table td { border: 1px solid #000000; }
    .data-table th, .data-table td { border: 1px solid #000000; vertical-align: middle; }
    .hdr { background-color: ' . $hdrGrey . '; color: #000000; font-weight: bold; text-align: center; }
    .hdr2 { background-color: ' . $hdrGrey2 . '; color: #000000; font-weight: bold; text-align: center; }
    .cell-center { text-align: center; }
    .cell-left { text-align: left; }
    .check-line { font-size: 6.5px; line-height: 1.35; }
</style>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;">
    <tr>
        <td style="width:28%; text-align:left;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . sr_pdf_esc($logoUrl) . '" width="95" height="42" />';
} else {
    $html .= '<span style="font-size:14px; font-weight:bold; color:#C41E3A;">Medicap</span><br/>
              <span style="font-size:11px; font-weight:bold;">Laboratories</span>';
}
$html .= '</td>
        <td style="width:44%;"></td>
        <td style="width:28%; text-align:right; font-size:8px;"><b>Ref: WI-QC-005-01</b></td>
    </tr>
</table>';

$html .= '<table class="form-table" cellpadding="4" cellspacing="0" border="1" style="width:100%; margin-bottom:6px;">
    <tr>
        <td colspan="4" style="text-align:center; font-size:10px; font-weight:bold; background-color:#EFEFEF;">
            TITLE: SAMPLING ROOM &ndash; USAGE &amp; CLEANING LOG
        </td>
    </tr>
    <tr>
        <td style="width:25%; font-size:7px;"><b>FORM NO.:</b> FQC-005-01-A</td>
        <td style="width:25%; font-size:7px;"><b>REVISION NO.:</b> 00</td>
        <td style="width:25%; font-size:7px;"><b>EFFECTIVE DATE:</b> JUN 16 2025</td>
        <td style="width:25%; font-size:7px;"><b>PAGE NO.:</b> 1 OF 1</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:7px; height:34px; vertical-align:top;">
            <b>DEPARTMENT APPROVAL:</b><br/><br/>
            <span style="font-size:6px;">Jun 13/25</span>
        </td>
        <td colspan="2" style="font-size:7px; height:34px; vertical-align:top;">
            <b>QA APPROVAL:</b><br/><br/>
            <span style="font-size:6px;">Jun 16/25</span>
        </td>
    </tr>
</table>';

$html .= '<table class="data-table" cellpadding="3" cellspacing="0" border="1" style="width:100%;">
    <thead>
        <tr class="hdr">
            <th rowspan="2" style="width:17%; font-size:7px;">Material Description</th>
            <th rowspan="2" style="width:7%; font-size:7px;">Material Lot #</th>
            <th rowspan="2" style="width:8%; font-size:7px;">Cleaning Performed</th>
            <th colspan="3" style="width:18%; font-size:7px;">Cleaning Agent</th>
            <th rowspan="2" style="width:8%; font-size:7px;">Balance<sup>1</sup></th>
            <th colspan="3" style="width:12%; font-size:7px;">Magnehelic Gauge Reading<sup>2</sup><br/>
                <span style="font-size:6px; font-weight:normal;">Limit: NLT 0.02&quot; WC</span></th>
            <th rowspan="2" style="width:8%; font-size:7px;">Performed By</th>
            <th rowspan="2" style="width:8%; font-size:7px;">Verified By</th>
        </tr>
        <tr class="hdr2">
            <th style="width:7%; font-size:6.5px;">Name</th>
            <th style="width:5%; font-size:6.5px;">Lot #</th>
            <th style="width:6%; font-size:6.5px;">Expiry Date</th>
            <th style="width:4%; font-size:6.5px;">#1</th>
            <th style="width:4%; font-size:6.5px;">#2</th>
            <th style="width:4%; font-size:6.5px;">#3</th>
        </tr>
    </thead>
    <tbody>';

foreach ($entries as $entry) {
    $html .= '<tr>
        <td class="cell-left" style="font-size:6.5px; height:24px;">' . sr_pdf_esc($entry['material_description']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['material_lot_no']) . '</td>
        <td class="cell-left check-line" style="font-size:6px; padding-left:3px;">
            Full ' . sr_pdf_checkbox($entry['cleaning_full']) . '<br/>
            Partial ' . sr_pdf_checkbox($entry['cleaning_partial']) . '
        </td>
        <td class="cell-left" style="font-size:6.5px;">' . sr_pdf_esc($entry['cleaning_agent_name']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['cleaning_agent_lot']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_date($entry['cleaning_agent_expiry']) . '</td>
        <td class="cell-left check-line" style="font-size:6px; padding-left:3px;">
            Cleaned ' . sr_pdf_checkbox($entry['balance_cleaned']) . '<br/>
            Verified ' . sr_pdf_checkbox($entry['balance_verified']) . '
        </td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['magnehelic_reading_1']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['magnehelic_reading_2']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['magnehelic_reading_3']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['performed_by']) . '</td>
        <td class="cell-center" style="font-size:6.5px;">' . sr_pdf_esc($entry['verified_by_name']) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

$html .= '<br/>
<table cellpadding="1" cellspacing="0" border="0" style="width:100%;">
    <tr>
        <td style="width:75%; font-size:6.5px;">
            <sup>1</sup> Balance Verification is performed on the day of use with 100g, Class 1 certified weights.<br/>
            <sup>2</sup> Reading from Magnehelic gauge to be taken before sampling.
        </td>
        <td style="width:25%; font-size:7px; text-align:right;">
            <b>Sheet No.:</b> ' . $sheetNo . ($logDate !== '' ? '<br/><span style="font-size:6px;">Log Date: ' . $logDate . '</span>' : '') . '
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$safeName = 'Sampling_Room_Log_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $sheet['sheet_no'] ?? 'export');
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
