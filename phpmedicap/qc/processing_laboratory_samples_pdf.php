<?php
date_default_timezone_set('Asia/Kolkata');
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$sampleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if ($sampleId <= 0) {
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

function pls_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function pls_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : pls_pdf_esc($value);
}

function pls_pdf_json_decode($str)
{
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function pls_pdf_logo_url($conn, $plantId)
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

function pls_pdf_val($col, $field)
{
    return trim((string) ($col[$field] ?? ''));
}

function pls_pdf_column_has_data($col)
{
    $fields = array(
        'fmm_attached', 'retest_date', 'remaining_drums', 'medicap_composite_g', 'id_test_g',
        'contract_chemical_g', 'contract_micro_g', 'retain_g', 'sampled_by', 'sampled_date',
        'test_review', 'coa_conforms', 'contract_lab_conforms', 'assigned_retest_expiry',
        'released_to_qa_by', 'released_to_qa_date', 'comments'
    );
    foreach ($fields as $field) {
        if (pls_pdf_val($col, $field) !== '') {
            return true;
        }
    }
    return false;
}

function pls_pdf_filter_columns($sectionD)
{
    if (!is_array($sectionD)) {
        return array();
    }
    $out = array();
    foreach ($sectionD as $col) {
        if (is_array($col) && pls_pdf_column_has_data($col)) {
            $out[] = $col;
        }
    }
    return $out;
}

function pls_pdf_row_has_data($cols, $fields)
{
    foreach ($cols as $col) {
        foreach ((array) $fields as $field) {
            if (pls_pdf_val($col, $field) !== '') {
                return true;
            }
        }
    }
    return false;
}

function pls_pdf_yn_cell($value)
{
    $v = strtoupper(trim((string) $value));
    $y = ($v === 'Y' || $v === 'YES') ? '<b>Y</b>' : 'Y';
    $n = ($v === 'N' || $v === 'NO') ? '<b>N</b>' : 'N';
    return $y . '  /  ' . $n;
}

function pls_pdf_form_header($pageNo, $totalPages, $hdrGrey)
{
    $fs = '6.5px';
    return '<table class="pls-pdf" cellpadding="3" cellspacing="0" border="1" style="width:100%; margin-bottom:5px; table-layout:fixed; border-collapse:collapse;">
        <colgroup>
            <col style="width:14%;" />
            <col style="width:32%;" />
            <col style="width:22%;" />
            <col style="width:32%;" />
        </colgroup>
        <tr>
            <td style="font-size:' . $fs . ';font-weight:bold;background-color:' . $hdrGrey . ';">TITLE</td>
            <td colspan="2" style="font-size:8px;font-weight:bold;text-align:center;">SAMPLING AND QC RELEASE FORM &ndash; RAW MATERIALS</td>
            <td style="font-size:' . $fs . ';"><b>FORM NO.:</b> FQC-005-08-A</td>
        </tr>
        <tr>
            <td style="font-size:' . $fs . ';font-weight:bold;background-color:' . $hdrGrey . ';">DEPARTMENT</td>
            <td colspan="2" style="font-size:' . $fs . ';text-align:center;">QUALITY CONTROL</td>
            <td style="font-size:' . $fs . ';"><b>REVISION NO.:</b> 00</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td style="font-size:' . $fs . ';font-weight:bold;background-color:' . $hdrGrey . ';">PAGE NO.</td>
            <td style="font-size:' . $fs . ';">' . intval($pageNo) . ' of ' . intval($totalPages) . '</td>
        </tr>
    </table>';
}

function pls_pdf_section_d_html($cols, $hdrGrey, $hdrGrey2)
{
    $n = count($cols);
    if ($n === 0) {
        return '';
    }

    $labelW = 28;
    $dataW = round((100 - $labelW) / $n, 2);
    $fs = '6px';
    $fsLbl = '6.5px';

    $colgroup = '<colgroup><col style="width:' . $labelW . '%;" />';
    for ($i = 0; $i < $n; $i++) {
        $colgroup .= '<col style="width:' . $dataW . '%;" />';
    }
    $colgroup .= '</colgroup>';

    $html = '<table class="pls-pdf" cellpadding="3" cellspacing="0" border="1" style="width:100%; table-layout:fixed; border-collapse:collapse;">'
        . $colgroup
        . '<tr><td colspan="' . ($n + 1) . '" style="font-size:7px;font-weight:bold;text-align:center;background-color:' . $hdrGrey . ';">Section D: FOR RETEST ONLY</td></tr>';

    $rows = array(
        array('label' => 'FMM-004-01-B copy Attached', 'fields' => array('fmm_attached'), 'type' => 'text'),
        array('label' => 'Retest Date', 'fields' => array('retest_date'), 'type' => 'date'),
        array('label' => '# of Remaining Drums', 'fields' => array('remaining_drums'), 'type' => 'text'),
    );

    foreach ($rows as $row) {
        if (!pls_pdf_row_has_data($cols, $row['fields'])) {
            continue;
        }
        $html .= '<tr><td style="font-size:' . $fsLbl . ';"><b>' . pls_pdf_esc($row['label']) . '</b></td>';
        foreach ($cols as $col) {
            $val = pls_pdf_val($col, $row['fields'][0]);
            if ($row['type'] === 'date') {
                $val = pls_pdf_date($val);
            }
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc($val) . '</td>';
        }
        $html .= '</tr>';
    }

    if (pls_pdf_row_has_data($cols, array('medicap_composite_g', 'id_test_g'))) {
        $html .= '<tr><td colspan="' . ($n + 1) . '" style="font-size:' . $fsLbl . ';font-weight:bold;background-color:' . $hdrGrey2 . ';text-align:center;">Sample Quantity</td></tr>';
        $html .= '<tr><td style="font-size:' . $fs . ';"><b>Chemical testing at Medicap Laboratories</b><br/>Composite (g)</td>';
        foreach ($cols as $col) {
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc(pls_pdf_val($col, 'medicap_composite_g')) . '</td>';
        }
        $html .= '</tr><tr><td style="font-size:' . $fs . ';"><b>For ID test (g)</b></td>';
        foreach ($cols as $col) {
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc(pls_pdf_val($col, 'id_test_g')) . '</td>';
        }
        $html .= '</tr>';
    }

    $qtyRows = array(
        array('label' => 'Chemical testing at Contract Lab (g)', 'field' => 'contract_chemical_g'),
        array('label' => 'Microbiological testing at Contract Lab (g)', 'field' => 'contract_micro_g'),
        array('label' => 'Retain sample (g)', 'field' => 'retain_g'),
    );
    foreach ($qtyRows as $row) {
        if (!pls_pdf_row_has_data($cols, array($row['field']))) {
            continue;
        }
        $html .= '<tr><td style="font-size:' . $fsLbl . ';"><b>' . pls_pdf_esc($row['label']) . '</b></td>';
        foreach ($cols as $col) {
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc(pls_pdf_val($col, $row['field'])) . '</td>';
        }
        $html .= '</tr>';
    }

    if (pls_pdf_row_has_data($cols, array('sampled_by', 'sampled_date'))) {
        $html .= '<tr><td style="font-size:' . $fsLbl . ';"><b>Sampled by:</b><br/>Date:</td>';
        foreach ($cols as $col) {
            $by = pls_pdf_val($col, 'sampled_by');
            $dt = pls_pdf_date(pls_pdf_val($col, 'sampled_date'));
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc($by) . ($dt !== '' ? '<br/>' . pls_pdf_esc($dt) : '') . '</td>';
        }
        $html .= '</tr>';
    }

    $reviewRows = array(
        array('label' => 'Test Review:<br/>Conforms', 'field' => 'test_review', 'type' => 'yn'),
        array('label' => 'Certificate of Analysis:<br/>Conforms', 'field' => 'coa_conforms', 'type' => 'yn'),
        array('label' => 'Contract Lab Results:<br/>Conforms', 'field' => 'contract_lab_conforms', 'type' => 'yn'),
    );
    $hasReview = false;
    foreach ($reviewRows as $row) {
        if (pls_pdf_row_has_data($cols, array($row['field']))) {
            $hasReview = true;
            break;
        }
    }
    if ($hasReview || pls_pdf_row_has_data($cols, array('assigned_retest_expiry', 'released_to_qa_by', 'released_to_qa_date', 'comments'))) {
        $html .= '<tr><td colspan="' . ($n + 1) . '" style="font-size:' . $fsLbl . ';font-weight:bold;background-color:' . $hdrGrey2 . ';text-align:center;">QC Review</td></tr>';
    }
    foreach ($reviewRows as $row) {
        if (!pls_pdf_row_has_data($cols, array($row['field']))) {
            continue;
        }
        $html .= '<tr><td style="font-size:' . $fs . ';"><b>' . $row['label'] . '</b></td>';
        foreach ($cols as $col) {
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_yn_cell(pls_pdf_val($col, $row['field'])) . '</td>';
        }
        $html .= '</tr>';
    }

    if (pls_pdf_row_has_data($cols, array('assigned_retest_expiry'))) {
        $html .= '<tr><td style="font-size:' . $fsLbl . ';"><b>Assigned Retest/Expiry Date:</b></td>';
        foreach ($cols as $col) {
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc(pls_pdf_date(pls_pdf_val($col, 'assigned_retest_expiry'))) . '</td>';
        }
        $html .= '</tr>';
    }

    if (pls_pdf_row_has_data($cols, array('released_to_qa_by', 'released_to_qa_date'))) {
        $html .= '<tr><td style="font-size:' . $fsLbl . ';"><b>Results released to QA &amp; Compliance By/Date:</b></td>';
        foreach ($cols as $col) {
            $by = pls_pdf_val($col, 'released_to_qa_by');
            $dt = pls_pdf_date(pls_pdf_val($col, 'released_to_qa_date'));
            $html .= '<td style="font-size:' . $fs . ';text-align:center;">' . pls_pdf_esc($by) . ($dt !== '' ? '<br/>' . pls_pdf_esc($dt) : '') . '</td>';
        }
        $html .= '</tr>';
    }

    if (pls_pdf_row_has_data($cols, array('comments'))) {
        $html .= '<tr><td style="font-size:' . $fsLbl . ';"><b>Comments:</b></td>';
        foreach ($cols as $col) {
            $html .= '<td style="font-size:' . $fs . ';">' . pls_pdf_esc(pls_pdf_val($col, 'comments')) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table>';
    return $html;
}

$res = $conn->query("SELECT * FROM processing_laboratory_sample
    WHERE id='" . $sampleId . "' AND plant_id='" . $plantId . "' LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo 'Sample not found';
    exit;
}

$sample = $res->fetch_assoc();
$sample['section_a'] = pls_pdf_json_decode($sample['section_a'] ?? '');
$sample['section_b'] = pls_pdf_json_decode($sample['section_b'] ?? '');
$sample['section_c'] = pls_pdf_json_decode($sample['section_c'] ?? '');
$sectionD = pls_pdf_json_decode($sample['section_d'] ?? '');
$sectionDCols = pls_pdf_filter_columns($sectionD);

$hasPage2 = count($sectionDCols) > 0;
$totalPages = $hasPage2 ? 2 : 1;
$logoUrl = pls_pdf_logo_url($conn, $plantId);
$hdrGrey = '#B8B8B8';
$hdrGrey2 = '#C8C8C8';
$fs = '6.5px';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('SAMPLING AND QC RELEASE FORM - RAW MATERIALS');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$html = '<style>
    table.pls-pdf { border-collapse: collapse; width: 100%; table-layout: fixed; }
    table.pls-pdf td { border: 1px solid #000000; vertical-align: middle; }
</style>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:30%;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . pls_pdf_esc($logoUrl) . '" width="90" height="40" />';
} else {
    $html .= '<span style="font-size:13px;font-weight:bold;color:#C41E3A;">Medicap</span><br/><span style="font-size:10px;font-weight:bold;">Laboratories</span>';
}
$html .= '</td><td style="width:40%;"></td>
    <td style="width:30%;text-align:right;font-size:8px;"><b>Ref: WI-QC-005-06</b></td></tr></table>';

$html .= pls_pdf_form_header(1, $totalPages, $hdrGrey);

$html .= '<div style="font-size:7px;font-weight:bold;margin:4px 0 2px;">Sample Identification Label</div>';
$html .= '<table class="pls-pdf" cellpadding="3" cellspacing="0" border="1" style="margin-bottom:4px;">
    <colgroup>
        <col style="width:22%;" /><col style="width:28%;" />
        <col style="width:22%;" /><col style="width:28%;" />
    </colgroup>
    <tr>
        <td style="font-size:' . $fs . ';"><b>Chemical Name</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['chemical_name']) . '</td>
        <td style="font-size:' . $fs . ';"><b>Commercial Name</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['commercial_name']) . '</td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';"><b>Manufacturer</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['manufacturer']) . '</td>
        <td style="font-size:' . $fs . ';"><b>Manufacturer\'s Lot #</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['manufacturer_lot']) . '</td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';"><b>Medicap Code #</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['medicap_code']) . '</td>
        <td style="font-size:' . $fs . ';"><b>Medicap Lot #</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['medicap_lot']) . '</td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';"><b>Lab #</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['lab_number']) . '</td>
        <td style="font-size:' . $fs . ';"><b># Containers Received</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['containers_received']) . '</td>
    </tr>
    <tr>
        <td style="font-size:' . $fs . ';"><b>Sample Category</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['sample_category']) . '</td>
        <td style="font-size:' . $fs . ';"><b>Type of Sample</b></td>
        <td style="font-size:' . $fs . ';">' . pls_pdf_esc($sample['sample_type_label']) . '</td>
    </tr>
</table>';

$sectionA = is_array($sample['section_a']) ? $sample['section_a'] : array();
if (!empty($sample['section_a_by']) && count($sectionA) > 0) {
    $sectionARows = array(
        array('key' => 'medicap_chemical', 'label' => 'Chemical testing at Medicap Laboratories'),
        array('key' => 'contract_chemical', 'label' => 'Chemical testing at Contract Lab'),
        array('key' => 'contract_micro', 'label' => 'Microbiological testing at Contract Lab'),
        array('key' => 'retain', 'label' => 'Retain sample'),
    );
    $html .= '<div style="font-size:7px;font-weight:bold;margin:4px 0 2px;">Section A: Sampling Instructions (Ref: WI-QC-005-02)</div>';
    $html .= '<table class="pls-pdf" cellpadding="3" cellspacing="0" border="1" style="margin-bottom:4px;">
        <colgroup>
            <col style="width:40%;" /><col style="width:20%;" />
            <col style="width:20%;" /><col style="width:20%;" />
        </colgroup>
        <tr style="background-color:' . $hdrGrey . ';font-size:6px;font-weight:bold;text-align:center;">
            <td>Sample Quantity</td><td>Full Testing (g)</td><td>Reduced Testing (g)</td><td>Comments</td>
        </tr>';
    foreach ($sectionARows as $r) {
        $row = isset($sectionA[$r['key']]) && is_array($sectionA[$r['key']]) ? $sectionA[$r['key']] : array();
        if (trim((string)($row['full_testing_g'] ?? '')) === '' && trim((string)($row['reduced_testing_g'] ?? '')) === '' && trim((string)($row['comments'] ?? '')) === '') {
            continue;
        }
        $html .= '<tr>
            <td style="font-size:6px;">' . pls_pdf_esc($r['label']) . '</td>
            <td style="font-size:6px;text-align:center;">' . pls_pdf_esc($row['full_testing_g'] ?? '') . '</td>
            <td style="font-size:6px;text-align:center;">' . pls_pdf_esc($row['reduced_testing_g'] ?? '') . '</td>
            <td style="font-size:6px;">' . pls_pdf_esc($row['comments'] ?? '') . '</td>
        </tr>';
    }
    $html .= '</table><div style="font-size:6px;margin-bottom:3px;">Completed By: ' . pls_pdf_esc($sample['section_a_by']) . ' | ' . pls_pdf_date($sample['section_a_date']) . '</div>';
}

$sectionB = is_array($sample['section_b']) ? $sample['section_b'] : array();
if (!empty($sample['section_b_by']) && count($sectionB) > 0) {
    $html .= '<div style="font-size:7px;font-weight:bold;margin:4px 0 2px;">Section B: Sampling and Material Inspection</div>';
    $inspRows = array(
        array('key' => 'conform_description', 'label' => 'Conform to description'),
        array('key' => 'absence_foreign_matter', 'label' => 'Absence of foreign matter'),
    );
    $html .= '<table class="pls-pdf" cellpadding="3" cellspacing="0" border="1" style="margin-bottom:3px;">
        <colgroup>
            <col style="width:55%;" /><col style="width:15%;" /><col style="width:30%;" />
        </colgroup>
        <tr style="background-color:' . $hdrGrey . ';font-size:6px;font-weight:bold;text-align:center;">
            <td>Inspection</td><td>Answer</td><td>Comment</td>
        </tr>';
    $inspections = isset($sectionB['inspections']) && is_array($sectionB['inspections']) ? $sectionB['inspections'] : array();
    foreach ($inspRows as $r) {
        $ans = isset($inspections[$r['key']]) ? $inspections[$r['key']] : array();
        if (trim((string)($ans['answer'] ?? '')) === '' && trim((string)($ans['comment'] ?? '')) === '') {
            continue;
        }
        $html .= '<tr>
            <td style="font-size:6px;">' . pls_pdf_esc($r['label']) . '</td>
            <td style="font-size:6px;text-align:center;">' . pls_pdf_esc($ans['answer'] ?? '') . '</td>
            <td style="font-size:6px;">' . pls_pdf_esc($ans['comment'] ?? '') . '</td>
        </tr>';
    }
    $html .= '</table>';
    $html .= '<div style="font-size:6px;">Sampled &amp; Inspected: ' . pls_pdf_esc($sectionB['sampled_by'] ?? '') . ' / ' . pls_pdf_date($sectionB['sampled_date'] ?? '') .
        ' | Retain Stored: ' . pls_pdf_esc($sectionB['retain_stored_by'] ?? '') . ' / ' . pls_pdf_date($sectionB['retain_stored_date'] ?? '') . '</div>';
    $html .= '<div style="font-size:6px;margin-bottom:3px;">Completed By: ' . pls_pdf_esc($sample['section_b_by']) . ' | ' . pls_pdf_date($sample['section_b_date']) . '</div>';
}

if (!empty($sample['lab_received_by'])) {
    $html .= '<div style="font-size:6px;margin-bottom:3px;">Lab Received: ' . pls_pdf_esc($sample['lab_received_by']) . ' | ' . pls_pdf_date($sample['lab_received_date']) . ' | Lab #: ' . pls_pdf_esc($sample['lab_number']) . '</div>';
}

$sectionC = is_array($sample['section_c']) ? $sample['section_c'] : array();
if (!empty($sample['section_c_by']) && count($sectionC) > 0) {
    $html .= '<div style="font-size:7px;font-weight:bold;margin:4px 0 2px;">Section C: QC Review</div>';
    $html .= '<table class="pls-pdf" cellpadding="3" cellspacing="0" border="1" style="margin-bottom:3px;">
        <colgroup>
            <col style="width:33.33%;" /><col style="width:33.33%;" /><col style="width:33.34%;" />
        </colgroup>
        <tr style="font-size:6px;">
            <td><b>Test Review Conforms:</b> ' . pls_pdf_yn_cell($sectionC['test_review'] ?? '') . '</td>
            <td><b>CoA Conforms:</b> ' . pls_pdf_yn_cell($sectionC['coa_conforms'] ?? '') . '</td>
            <td><b>Contract Lab Attached:</b> ' . pls_pdf_yn_cell($sectionC['contract_lab_attached'] ?? '') . '</td>
        </tr>
        <tr style="font-size:6px;">
            <td colspan="2"><b>QC Released By/Date:</b> ' . pls_pdf_esc($sectionC['qc_released_by'] ?? '') . ' / ' . pls_pdf_date($sectionC['qc_released_date'] ?? '') . '</td>
            <td><b>Assigned Retest/Expiry:</b> ' . pls_pdf_date($sectionC['assigned_retest_expiry_date'] ?? '') . '</td>
        </tr>
    </table>';
    $html .= '<div style="font-size:6px;margin-bottom:3px;">Completed By: ' . pls_pdf_esc($sample['section_c_by']) . ' | ' . pls_pdf_date($sample['section_c_date']) . '</div>';
}

$pdf->writeHTML($html, true, false, true, false, '');

if ($hasPage2) {
    $pdf->AddPage('L');
    $pdf->SetFont('helvetica', '', 7);
    $html2 = '<style>
        table.pls-pdf { border-collapse: collapse; width: 100%; table-layout: fixed; }
        table.pls-pdf td { border: 1px solid #000000; vertical-align: middle; }
    </style>';
    $html2 .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
        <td style="width:30%;">';
    if ($logoUrl !== '') {
        $html2 .= '<img src="' . pls_pdf_esc($logoUrl) . '" width="90" height="40" />';
    }
    $html2 .= '</td><td style="width:40%;"></td>
        <td style="width:30%;text-align:right;font-size:8px;"><b>Ref: WI-QC-005-06</b></td></tr></table>';
    $html2 .= pls_pdf_form_header(2, $totalPages, $hdrGrey);
    $html2 .= pls_pdf_section_d_html($sectionDCols, $hdrGrey, $hdrGrey2);
    $pdf->writeHTML($html2, true, false, true, false, '');
}

$safeName = 'PLS_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $sample['form_no'] ?? ('ID_' . $sampleId));
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
