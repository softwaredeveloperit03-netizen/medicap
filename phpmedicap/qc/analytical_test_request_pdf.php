<?php
date_default_timezone_set('Asia/Kolkata');
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($requestId <= 0) {
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

$ATR_SAMPLE_CATEGORIES = array(
    'API', 'Excipient', 'In-process Product', 'Bulk Product',
    'Semi - Finished Product', 'Cleaning Verification', 'Finished Product', 'Stability Samples',
    'Pilot Bio batch', 'Innovator', 'Validation', 'Complaint Samples',
    'Water Sample', 'Retest', 'Other'
);

$ATR_TEST_OPTIONS = array(
    array('key' => 'description', 'label' => 'Description'),
    array('key' => 'impurities', 'label' => 'Impurities'),
    array('key' => 'potency', 'label' => 'Potency (Target Potency)', 'textKey' => 'potency_target'),
    array('key' => 'blend_uniformity', 'label' => 'Blend uniformity'),
    array('key' => 'assay', 'label' => 'Assay'),
    array('key' => 'id_test', 'label' => 'ID', 'textKey' => 'id_detail'),
    array('key' => 'content_uniformity', 'label' => 'Content Uniformity'),
    array('key' => 'ph', 'label' => 'pH'),
    array('key' => 'residual_solvent', 'label' => 'Residual Solvent', 'textKey' => 'residual_solvent_detail'),
    array('key' => 'moisture', 'label' => 'Moisture(KF/LOD)'),
    array('key' => 'particle_size', 'label' => 'Particle Size'),
    array('key' => 'residue_on_ignition', 'label' => 'Residue on Ignition'),
    array('key' => 'heavy_metal', 'label' => 'Heavy Metal'),
    array('key' => 'dsc', 'label' => 'DSC'),
    array('key' => 'tlc', 'label' => 'TLC'),
    array('key' => 'micro', 'label' => 'Micro'),
    array('key' => 'toc', 'label' => 'TOC'),
    array('key' => 'dissolution', 'label' => 'Dissolution Media', 'textKey' => 'dissolution_media'),
    array('key' => 'other_test', 'label' => 'Other', 'textKey' => 'other_test_detail'),
);

$ATR_BINDER_OPTIONS = array(
    'Dissolution', 'Finished Product', 'In-Process', 'Water testing Binder',
    'Raw Material', 'Residual Solvent', 'Stability', 'Contract Lab', 'Others'
);

$ATR_DEPT_OPTIONS = array('QA', 'RA', 'Production', 'Product Development', 'Quality and Compliance', 'Others');

function atr_pdf_esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function atr_pdf_date($value, $format = 'd-m-Y')
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : atr_pdf_esc($value);
}

function atr_pdf_logo_url($conn, $plantId)
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

function atr_pdf_json_decode($str)
{
    if ($str === null || $str === '') {
        return array();
    }
    $decoded = json_decode($str, true);
    return is_array($decoded) ? $decoded : array();
}

function atr_pdf_check($checked)
{
    return $checked
        ? '<span style="font-family:dejavusans;">&#9745;</span>'
        : '<span style="font-family:dejavusans;">&#9744;</span>';
}

function atr_pdf_field_line($label, $value, $suffix = '')
{
    $v = trim((string) $value);
    $display = $v !== '' ? atr_pdf_esc($v) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    return '<b>' . atr_pdf_esc($label) . '</b> <span style="border-bottom:1px solid #000;">' . $display . '</span>' . $suffix;
}

function atr_pdf_test_cell($test, $tests)
{
    $checked = !empty($tests[$test['key']]);
    $html = atr_pdf_check($checked) . ' ' . atr_pdf_esc($test['label']);
    if (!empty($test['textKey']) && $checked && !empty($tests[$test['textKey']])) {
        $html .= ': <span style="border-bottom:1px solid #000;">' . atr_pdf_esc($tests[$test['textKey']]) . '</span>';
    } elseif (!empty($test['textKey']) && ($test['key'] === 'id_test' || $test['key'] === 'potency' || $test['key'] === 'dissolution' || $test['key'] === 'other_test')) {
        $html .= ': <span style="border-bottom:1px solid #000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
    }
    return $html;
}

function atr_pdf_in_list($list, $val)
{
    return is_array($list) && in_array($val, $list, true);
}

$sqlReq = "SELECT * FROM analytical_test_request
        WHERE id = '" . $requestId . "' AND plant_id = '" . $plantId . "' LIMIT 1";
$resReq = $conn->query($sqlReq);
if (!$resReq || $resReq->num_rows === 0) {
    echo 'Request not found';
    exit;
}
$req = $resReq->fetch_assoc();
$req['sample_categories'] = atr_pdf_json_decode($req['sample_categories'] ?? '');
$req['tests_requested'] = atr_pdf_json_decode($req['tests_requested'] ?? '');
$req['binder_forward'] = atr_pdf_json_decode($req['binder_forward'] ?? '');
$req['dept_forward'] = atr_pdf_json_decode($req['dept_forward'] ?? '');

$logoUrl = atr_pdf_logo_url($conn, $plantId);
$tests = is_array($req['tests_requested']) ? $req['tests_requested'] : array();
$categories = is_array($req['sample_categories']) ? $req['sample_categories'] : array();
$hasApprovedSpecs = !empty($tests['has_approved_specs']);

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Medicap Laboratories');
$pdf->SetAuthor('Medicap Laboratories');
$pdf->SetTitle('ANALYTICAL TEST REQUEST FORM');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 7);

$hdrGrey = '#EFEFEF';
$fs = '6.5px';
$fsSm = '6px';

$html = '<style>
    table.atr-form td, table.atr-grid td { border: 1px solid #000000; vertical-align: top; }
    .atr-section { font-size: 8px; font-weight: bold; text-decoration: underline; }
    .atr-label { font-weight: bold; }
</style>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:30%; text-align:left;">';
if ($logoUrl !== '') {
    $html .= '<img src="' . atr_pdf_esc($logoUrl) . '" width="90" height="40" />';
} else {
    $html .= '<span style="font-size:13px;font-weight:bold;color:#C41E3A;">Medicap</span><br/><span style="font-size:10px;font-weight:bold;">Laboratories</span>';
}
$html .= '</td><td style="width:40%;"></td>
    <td style="width:30%; text-align:right;font-size:8px;"><b>Ref: WI-QC-005-06</b></td></tr></table>';

$html .= '<table class="atr-form" cellpadding="3" cellspacing="0" border="1" style="width:100%; margin-bottom:5px;">
    <tr>
        <td style="width:14%;font-size:' . $fs . ';font-weight:bold;">TITLE</td>
        <td colspan="2" style="text-align:center;font-size:9px;font-weight:bold;background-color:' . $hdrGrey . ';">ANALYTICAL TEST REQUEST FORM</td>
        <td style="width:22%;font-size:' . $fs . ';"><b>FORM NO.:</b> FQC-005-06-A</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:' . $fs . ';height:32px;vertical-align:top;"><b>DEPARTMENT APPROVAL:</b><br/><span style="font-size:' . $fsSm . ';">Jun 13/25</span></td>
        <td style="width:22%;font-size:' . $fs . ';"><b>REVISION NO.:</b> 00</td>
        <td style="width:22%;font-size:' . $fs . ';"><b>PAGE NO.:</b> 1 of 1</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:' . $fs . ';height:32px;vertical-align:top;"><b>QA APPROVAL:</b><br/><span style="font-size:' . $fsSm . ';">Jun 16/25</span></td>
        <td colspan="2" style="font-size:' . $fs . ';"><b>EFFECTIVE DATE:</b> JUN 16 2025</td>
    </tr>
</table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:4px;"><tr>
    <td style="width:55%;" class="atr-section">SECTION A: REQUESTOR</td>
    <td style="width:45%;font-size:' . $fs . ';text-align:right;">' . atr_pdf_field_line('LAB SAMPLE NUMBER:', $req['lab_sample_number']) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:3px;"><tr>
    <td style="font-size:' . $fs . ';">' . atr_pdf_field_line('PRODUCT DESCRIPTION:', $req['product_description']) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:3px;"><tr>
    <td style="width:33%;font-size:' . $fs . ';">' . atr_pdf_field_line('PRODUCT CODE:', $req['product_code']) . '</td>
    <td style="width:33%;font-size:' . $fs . ';">' . atr_pdf_field_line('LOT #:', $req['lot_number']) . '</td>
    <td style="width:34%;font-size:' . $fs . ';">' . atr_pdf_field_line('MFG DATE:', atr_pdf_date($req['mfg_date'])) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:3px;"><tr>
    <td style="width:33%;font-size:' . $fs . ';">' . atr_pdf_field_line('TYPE OF SAMPLES:', $req['sample_type']) . '</td>
    <td style="width:33%;font-size:' . $fs . ';">' . atr_pdf_field_line('SAMPLING SITE:', $req['sampling_site']) . '</td>
    <td style="width:34%;font-size:' . $fs . ';">' . atr_pdf_field_line('# OF SAMPLES:', $req['num_of_samples']) . '</td>
</tr></table>';

$html .= '<div style="font-size:' . $fs . '; font-weight:bold; text-decoration:underline; margin:4px 0 2px;">SAMPLE CATEGORY:</div>';
$html .= '<table class="atr-grid" cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:3px;">';
$catChunks = array_chunk($ATR_SAMPLE_CATEGORIES, 4);
foreach ($catChunks as $chunk) {
    $html .= '<tr>';
    $colCount = count($chunk);
    foreach ($chunk as $cat) {
        $checked = in_array($cat, $categories, true);
        $label = $cat;
        if ($cat === 'Other' && !empty($req['sample_category_other'])) {
            $label .= ': ' . $req['sample_category_other'];
        }
        $html .= '<td style="width:25%;font-size:' . $fsSm . '; border:none;">' . atr_pdf_check($checked) . ' ' . atr_pdf_esc($label) . '</td>';
    }
    for ($i = $colCount; $i < 4; $i++) {
        $html .= '<td style="width:25%;border:none;"></td>';
    }
    $html .= '</tr>';
}
$html .= '</table>';

$html .= '<div style="font-size:' . $fs . '; font-weight:bold; text-decoration:underline; margin:4px 0 2px;">TEST(S) REQUESTED:</div>';
$html .= '<div style="font-size:' . $fsSm . '; margin-bottom:3px;">' . atr_pdf_check($hasApprovedSpecs) . ' <b>SAMPLES WHICH HAVE APPROVED SPECIFICATIONS: REFER RESPECTIVE APPROVED SPECIFICATION FOR TESTING REQUIREMENTS</b></div>';

if (!$hasApprovedSpecs) {
    $selectedTests = array();
    foreach ($ATR_TEST_OPTIONS as $test) {
        if (!empty($tests[$test['key']])) {
            $selectedTests[] = $test;
        }
    }
    if (count($selectedTests) > 0) {
        $testChunks = array_chunk($selectedTests, 4);
        $html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:3px;">';
        foreach ($testChunks as $chunk) {
            $html .= '<tr>';
            $colCount = count($chunk);
            foreach ($chunk as $test) {
                $html .= '<td style="width:25%;font-size:' . $fsSm . ';">' . atr_pdf_test_cell($test, $tests) . '</td>';
            }
            for ($i = $colCount; $i < 4; $i++) {
                $html .= '<td style="width:25%;"></td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
    }
}

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:50%;font-size:' . $fs . ';">' . atr_pdf_field_line('FOR BATCH SAMPLES ONLY: QA VERIFIED BY:', $req['qa_verified_by']) . ' &nbsp; ' . atr_pdf_field_line('DATE:', atr_pdf_date($req['qa_verified_date'])) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:50%;font-size:' . $fs . ';">' . atr_pdf_field_line('PROCESSING START DATE:', atr_pdf_date($req['processing_start_date'])) . ' &nbsp; ' . atr_pdf_field_line('HOLDING TIME:', $req['holding_time']) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="width:50%;font-size:' . $fs . ';">' . atr_pdf_field_line('FOR PRODUCTION SAMPLES ONLY: VERIFIED BY:', $req['production_verified_by']) . ' &nbsp; ' . atr_pdf_field_line('DATE:', atr_pdf_date($req['production_verified_date'])) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;"><tr>
    <td style="font-size:' . $fs . ';">' . atr_pdf_field_line('Comments:', $req['comments']) . '</td>
</tr></table>';

$html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:4px;"><tr>
    <td style="width:33%;font-size:' . $fs . ';">' . atr_pdf_field_line('REQUESTOR:', $req['requestor']) . '</td>
    <td style="width:33%;font-size:' . $fs . ';">' . atr_pdf_field_line('DATE:', atr_pdf_date($req['requestor_date'])) . '</td>
    <td style="width:34%;font-size:' . $fs . ';">' . atr_pdf_field_line('DATE RESULTS REQUIRED:', atr_pdf_date($req['date_results_required'])) . '</td>
</tr></table>';

$showSectionB = !empty($req['completed_by']) || !empty($req['analyst_by']) || !empty($req['lab_manager_by'])
    || count($req['binder_forward']) > 0 || count($req['dept_forward']) > 0
    || !empty($req['binder_forward_other']) || !empty($req['dept_forward_other'])
    || !empty($req['analyst_remark']) || ($req['alternative_method'] ?? '') === 'Yes';

if ($showSectionB) {
    $html .= '<hr style="border:1px solid #000; margin:6px 0;" />';
    $html .= '<div class="atr-section" style="margin-bottom:4px;">SECTION B: LABORATORY ANALYST</div>';

    if (!empty($req['analyst_remark'])) {
        $html .= '<div style="font-size:' . $fs . '; margin-bottom:2px;">' . atr_pdf_field_line('Analyst Remark:', $req['analyst_remark']) . '</div>';
    }

    $html .= '<div style="font-size:' . $fs . '; font-weight:bold; margin:3px 0 2px;">RESULTS FORWARDED TO BINDER:</div>';
    $binderChunks = array_chunk($ATR_BINDER_OPTIONS, 4);
    $html .= '<table cellpadding="1" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;">';
    foreach ($binderChunks as $chunk) {
        $html .= '<tr>';
        foreach ($chunk as $opt) {
            $checked = atr_pdf_in_list($req['binder_forward'], $opt);
            if ($opt === 'Others' && !empty($req['binder_forward_other'])) {
                $checked = $checked || trim($req['binder_forward_other']) !== '';
            }
            $label = $opt;
            if ($opt === 'Others' && !empty($req['binder_forward_other'])) {
                $label .= ': ' . $req['binder_forward_other'];
            }
            $html .= '<td style="width:25%;font-size:' . $fsSm . ';">' . atr_pdf_check($checked) . ' ' . atr_pdf_esc($label) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';

    $html .= '<div style="font-size:' . $fs . '; font-weight:bold; margin:3px 0 2px;">COPY OF RESULTS FORWARDED: TO DEPARTMENT:</div>';
    $deptChunks = array_chunk($ATR_DEPT_OPTIONS, 4);
    $html .= '<table cellpadding="1" cellspacing="0" border="0" style="width:100%; margin-bottom:2px;">';
    foreach ($deptChunks as $chunk) {
        $html .= '<tr>';
        foreach ($chunk as $opt) {
            $checked = atr_pdf_in_list($req['dept_forward'], $opt);
            if ($opt === 'Others' && !empty($req['dept_forward_other'])) {
                $checked = $checked || trim($req['dept_forward_other']) !== '';
            }
            $label = $opt;
            if ($opt === 'Others' && !empty($req['dept_forward_other'])) {
                $label .= ': ' . $req['dept_forward_other'];
            }
            $html .= '<td style="width:25%;font-size:' . $fsSm . ';">' . atr_pdf_check($checked) . ' ' . atr_pdf_esc($label) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';

    $html .= '<div style="font-size:' . $fsSm . '; margin:3px 0;">(Attach result forms/data sheets to this form)</div>';

    $html .= '<table cellpadding="2" cellspacing="0" border="0" style="width:100%; margin-bottom:3px;"><tr>
        <td style="width:50%;font-size:' . $fs . ';">' . atr_pdf_field_line('COMPLETED BY:', $req['completed_by']) . ' &nbsp; ' . atr_pdf_field_line('DATE:', atr_pdf_date($req['completed_date'])) . '</td>
    </tr></table>';
}

$html .= '<hr style="border:1px solid #000; margin:8px 0 4px;" />';
$html .= '<div style="text-align:center; font-size:' . $fsSm . '; font-style:italic;">*Confidential Information: Do Not Disclose without Authorization*</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$safeName = 'ATR_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $req['request_no'] ?? ('ID_' . $requestId));
$pdf->Output($safeName . '.pdf', 'I');

$conn->close();
