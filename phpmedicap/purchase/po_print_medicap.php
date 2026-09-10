<?php
/**
 * Medicap Laboratories purchase order PDF layout.
 * Included from purchase/po_print.php when type=downloadPOReport (non-plant-182).
 */

$_GET['filename'] = 'Purchase Order';
$_GET['pdftype'] = 'onlyheader';
$_GET['pdffont'] = 'helvetica';
$_GET['pdffonts'] = 8;
$_GET['pdfy'] = 32;
$_GET['pdftop'] = 24;
$_GET['pdfpagebr'] = 18;
include('../pdfimp2.php');

function medicapPoEsc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function medicapPoDate($value)
{
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date('m/d/Y', $ts) : medicapPoEsc($value);
}

function medicapTableColumns($conn, $table)
{
    static $cache = array();
    if (!isset($cache[$table])) {
        $cache[$table] = array();
        $result = $conn->query('SHOW COLUMNS FROM `' . $conn->real_escape_string($table) . '`');
        if ($result) {
            while ($col = $result->fetch_assoc()) {
                $cache[$table][] = $col['Field'];
            }
        }
    }
    return $cache[$table];
}

function medicapHasColumn($conn, $table, $column)
{
    return in_array($column, medicapTableColumns($conn, $table), true);
}

function medicapSelectExpr($conn, $table, $alias, $candidates, $outputAlias)
{
    foreach ($candidates as $column) {
        if (medicapHasColumn($conn, $table, $column)) {
            return $alias . '.' . $column . ' AS ' . $outputAlias;
        }
    }
    return "'' AS " . $outputAlias;
}

function medicapFirstValue($source, $keys)
{
    if (!is_array($source)) {
        return '';
    }
    foreach ($keys as $key) {
        if (!empty($source[$key])) {
            return $source[$key];
        }
    }
    return '';
}

function medicapFormatAddressBlock($data)
{
    if (!is_array($data)) {
        return array();
    }

    $lines = array();
    $name = medicapFirstValue($data, array('company_name', 'plant_name'));
    if ($name !== '') {
        $lines[] = $name;
    }

    $address = medicapFirstValue($data, array('address', 'plant_full_address', 'address1'));
    if ($address !== '') {
        $lines[] = $address;
    }

    $city = trim(medicapFirstValue($data, array('present_city', 'area')));
    $state = trim(medicapFirstValue($data, array('present_state', 'state_name', 'b_state', 's_state')));
    $pin = trim(medicapFirstValue($data, array('pin', 'ship_pin', 'bill_pin', 'pincode')));

    $cityState = $city;
    if ($state !== '') {
        $cityState = $cityState !== '' ? $cityState . ', ' . $state : $state;
    }
    if ($pin !== '') {
        $cityState = $cityState !== '' ? $cityState . ' ' . $pin : $pin;
    }
    if ($cityState !== '') {
        $lines[] = $cityState;
    }

    return $lines;
}

function medicapExpectedDate($row, $items)
{
    if (!empty($row['delivery_schedule_date']) && $row['delivery_schedule_date'] !== '0000-00-00') {
        return medicapPoDate($row['delivery_schedule_date']);
    }

    if (!empty($row['schedule_data'])) {
        $schedule = json_decode($row['schedule_data'], true);
        if (is_array($schedule)) {
            foreach ($schedule as $entry) {
                if (!empty($entry['delivery_schedule_date'])) {
                    return medicapPoDate($entry['delivery_schedule_date']);
                }
            }
        }
    }

    foreach ($items as $item) {
        if (!empty($item['delivery_schedule_date']) && $item['delivery_schedule_date'] !== '0000-00-00') {
            return medicapPoDate($item['delivery_schedule_date']);
        }
    }

    return '';
}

function medicapCollectUnique($items, $field)
{
    $values = array();
    foreach ($items as $item) {
        if (!empty($item[$field])) {
            $values[(string) $item[$field]] = true;
        }
    }
    return implode(', ', array_keys($values));
}

$vendorAddressExpr = medicapHasColumn($conn, 'vendor', 'address')
    ? 'v.address'
    : (medicapHasColumn($conn, 'vendor', 'address_corporate') ? 'v.address_corporate' : "''");
$vendorPhoneExpr = medicapSelectExpr($conn, 'vendor', 'v', array('contact_number', 'mobile_no', 'tel_no1', 'contact_no', 'phone'), 'vendor_phone');
$vendorFaxExpr = medicapSelectExpr($conn, 'vendor', 'v', array('fax', 'fax_no'), 'vendor_fax');
$vendorContactExpr = medicapHasColumn($conn, 'vendor', 'contact_person') ? 'v.contact_person' : "''";
$shipPhoneExpr = medicapSelectExpr($conn, 'company', 'c1', array('mobile_no', 'mobile_no1', 'contact_no'), 'ship_phone');
$shipFaxExpr = medicapSelectExpr($conn, 'company', 'c1', array('fax_no', 'fax'), 'ship_fax');
$shipCityExpr = medicapHasColumn($conn, 'company', 'present_city') ? 'c1.present_city' : "''";
$shipStateExpr = medicapHasColumn($conn, 'company', 'present_state') ? 'c1.present_state' : "''";
$shipPinExpr = medicapHasColumn($conn, 'company', 'pin') ? 'c1.pin' : "''";
$shipAreaExpr = medicapHasColumn($conn, 'company', 'area') ? 'c1.area' : "''";

$sql = "SELECT p.*, v.vendor_name, " . $vendorAddressExpr . " AS vendor_address, " . $vendorContactExpr . " AS contact_person,
        " . $vendorPhoneExpr . ", " . $vendorFaxExpr . ",
        c1.company_name AS company_name1, c1.address AS address1,
        " . $shipCityExpr . " AS ship_city, " . $shipStateExpr . " AS ship_state,
        " . $shipPinExpr . " AS ship_pin, " . $shipAreaExpr . " AS ship_area,
        " . $shipPhoneExpr . ", " . $shipFaxExpr . ",
        e.firstname, e.lastname,
        ea.firstname AS approve_firstname, ea.lastname AS approve_lastname,
        pid.plant_full_address, pid.plant_name
        FROM purchaseorder p
        LEFT JOIN vendor v ON p.vendor_no = v.vendor_no
        LEFT JOIN company c1 ON p.shipcompany_code = c1.company_code
        LEFT JOIN employee e ON p.entry_by = e.emp_id
        LEFT JOIN employee ea ON p.approve_by = ea.emp_id
        LEFT JOIN plant pid ON p.plant_id = pid.plant_id
        WHERE p.id = '" . $conn->real_escape_string($_GET['id']) . "' LIMIT 1";

$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo 'Purchase order not found.';
    exit;
}

$row = $result->fetch_assoc();

$selectedShip = array();
if (!empty($row['selectedShip'])) {
    $decodedShip = json_decode($row['selectedShip'], true);
    if (is_array($decodedShip)) {
        $selectedShip = $decodedShip;
    }
}

$items = array();
$sqlItems = "SELECT p.*, m.material_name, m.grade, m.hsn
    FROM po_material p
    LEFT JOIN my_view m ON p.material_code = m.material_code
    WHERE p.plant_id = '" . $conn->real_escape_string($_GET['plant_id']) . "'
      AND p.po_no = '" . $conn->real_escape_string($row['id']) . "'
    ORDER BY p.id";

$resultItems = $conn->query($sqlItems);
if ($resultItems && $resultItems->num_rows > 0) {
    while ($item = $resultItems->fetch_assoc()) {
        $items[] = $item;
    }
}

$vendorPhone = $row['vendor_phone'] ?? '';
$vendorFax = $row['vendor_fax'] ?? '';
$contact = $row['contact_person'] ?? '';
$orderedBy = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
$approvedBy = trim(($row['approve_firstname'] ?? '') . ' ' . ($row['approve_lastname'] ?? ''));
$approveDate = medicapPoDate($row['approve_date'] ?? '');
$orderDate = medicapPoDate($row['entry_date'] ?? '');
$expectedDate = medicapExpectedDate($row, $items);
$prNo = medicapCollectUnique($items, 'indend_no');
$quoteNo = medicapCollectUnique($items, 'quotation_no');

$shipData = $selectedShip;
if (empty($shipData)) {
    $shipData = array(
        'company_name' => $row['company_name1'] ?? '',
        'address' => $row['address1'] ?? '',
        'present_city' => $row['ship_city'] ?? '',
        'present_state' => $row['ship_state'] ?? '',
        'pin' => $row['ship_pin'] ?? '',
        'area' => $row['ship_area'] ?? '',
        'mobile_no' => $row['ship_phone'] ?? '',
        'fax_no' => $row['ship_fax'] ?? ''
    );
}
if (empty(medicapFirstValue($shipData, array('company_name')))) {
    $shipData['company_name'] = $row['plant_name'] ?? 'Medicap Laboratories';
}
if (empty(medicapFirstValue($shipData, array('address', 'plant_full_address')))) {
    $shipData['address'] = $row['plant_full_address'] ?? '';
}

$shipLines = medicapFormatAddressBlock($shipData);
$shipPhone = medicapFirstValue($shipData, array('mobile_no', 'mobile_no1', 'contact_no', 'ship_phone'));
if ($shipPhone === '') {
    $shipPhone = $row['ship_phone'] ?? '';
}
$shipFax = medicapFirstValue($shipData, array('fax_no', 'fax', 'ship_fax'));
if ($shipFax === '') {
    $shipFax = $row['ship_fax'] ?? '';
}

$currency = strtoupper(trim($row['currency'] ?? $row['currancy'] ?? 'CDN'));
$finalTotal = number_format((float) ($row['final_total'] ?? 0), 2, '.', ',');
$usTotal = ($currency === 'USD') ? $finalTotal : '';
$cdnTotal = ($currency !== 'USD') ? $finalTotal : '';

$tblW = 540;
$wLeft = 178;
$wMid = 178;
$wRight = 184;
$cellPad = 3;
$border = 'border:1px solid #000;';
$base = 'font-family:helvetica,arial,sans-serif;font-size:8px;line-height:1.25;';
$lbl = 'font-weight:bold;white-space:nowrap;';
$bc = $border . 'vertical-align:top;' . $base;
$hdr = $border . 'text-align:center;font-weight:bold;font-size:7px;vertical-align:middle;' . $base;
$lineH = '16px';
$qtyInner = 'padding:2px 3px;text-align:center;vertical-align:middle;';

function medicapLineCell($content, $align = 'left')
{
    global $border, $base, $cellPad;
    return '<td style="' . $border . 'text-align:' . $align . ';vertical-align:middle;padding:' . $cellPad . 'px;' . $base . '">' . $content . '</td>';
}

$vendorAddressBlock = '<span style="' . $lbl . '">Purchased From:</span><br/><br/>
    <span style="font-weight:bold;">' . medicapPoEsc($row['vendor_name']) . '</span><br/>'
    . nl2br(medicapPoEsc($row['vendor_address']));

$shipAddressBlock = '<span style="' . $lbl . '">Ship To:</span><br/>';
foreach ($shipLines as $shipLine) {
    $shipAddressBlock .= medicapPoEsc($shipLine) . '<br/>';
}

$metaFields = array(
    array('P.O. #:', $row['po_no']),
    array('P.R. #:', $prNo),
    array('Quote #:', $quoteNo),
    array('Order Date:', $orderDate),
    array('Expected Date:', $expectedDate),
    array('Contact:', $contact),
    array('Ordered By:', $orderedBy),
);

$addressRowSpan = 5;
$phoneRow = '<span style="' . $lbl . '">Phone:</span> ';
$faxRow = '<span style="' . $lbl . '">Fax:</span> ';

$html = '<table cellpadding="' . $cellPad . '" cellspacing="0" width="' . $tblW . '" style="border-collapse:collapse;' . $base . '">';

for ($i = 0; $i < count($metaFields); $i++) {
    $metaCell = '<span style="' . $lbl . '">' . medicapPoEsc($metaFields[$i][0]) . '</span> ' . medicapPoEsc($metaFields[$i][1]);

    if ($i === 0) {
        $html .= '<tr>
            <td rowspan="' . $addressRowSpan . '" width="' . $wLeft . '" style="' . $bc . '">' . $vendorAddressBlock . '</td>
            <td rowspan="' . $addressRowSpan . '" width="' . $wMid . '" style="' . $bc . '">' . $shipAddressBlock . '</td>
            <td width="' . $wRight . '" style="' . $bc . '">' . $metaCell . '</td>
        </tr>';
    } elseif ($i === 5) {
        $html .= '<tr>
            <td width="' . $wLeft . '" style="' . $bc . '">' . $phoneRow . medicapPoEsc($vendorPhone) . '</td>
            <td width="' . $wMid . '" style="' . $bc . '">' . $phoneRow . medicapPoEsc($shipPhone) . '</td>
            <td width="' . $wRight . '" style="' . $bc . '">' . $metaCell . '</td>
        </tr>';
    } elseif ($i === 6) {
        $html .= '<tr>
            <td width="' . $wLeft . '" style="' . $bc . '">' . $faxRow . medicapPoEsc($vendorFax) . '</td>
            <td width="' . $wMid . '" style="' . $bc . '">' . $faxRow . medicapPoEsc($shipFax) . '</td>
            <td width="' . $wRight . '" style="' . $bc . '">' . $metaCell . '</td>
        </tr>';
    } else {
        $html .= '<tr><td width="' . $wRight . '" style="' . $bc . '">' . $metaCell . '</td></tr>';
    }
}

$html .= '</table>';

$html .= '<table cellpadding="' . $cellPad . '" cellspacing="0" width="' . $tblW . '" style="border-collapse:collapse;margin-top:-1px;' . $base . '">';
$html .= '<tr>
    <td width="40" style="' . $hdr . '">Item #</td>
    <td width="78" style="' . $hdr . '">Catalog #</td>
    <td width="206" style="' . $hdr . '">Product</td>
    <td width="72" style="' . $hdr . '">Quantity</td>
    <td width="72" style="' . $hdr . '">Unit Price</td>
    <td width="72" style="' . $hdr . '">Total Price</td>
</tr>';

$lineNo = 1;
foreach ($items as $item) {
    $product = trim($item['material_name'] ?? '');
    if (!empty($item['grade'])) {
        $product = trim($product . ' ' . $item['grade']);
    }
    $qtyDisplay = trim(medicapPoEsc($item['qty']) . ' ' . medicapPoEsc($item['unit']));
    $unitPrice = number_format((float) ($item['quotation_amt'] ?? 0), 2, '.', ',');
    $lineTotal = number_format((float) ($item['gross_total'] ?? $item['net_total'] ?? 0), 2, '.', ',');

    $html .= '<tr>'
        . medicapLineCell((string) $lineNo, 'center')
        . medicapLineCell(medicapPoEsc($item['material_code']))
        . medicapLineCell(medicapPoEsc($product))
        . '<td style="' . $border . 'text-align:center;vertical-align:middle;padding:2px;">
            <table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td width="50%" style="border-right:1px solid #000;' . $qtyInner . 'height:' . $lineH . ';">' . $qtyDisplay . '</td>
                    <td width="50%" style="' . $qtyInner . '">&nbsp;</td>
                </tr>
            </table>
        </td>'
        . medicapLineCell($unitPrice, 'right')
        . medicapLineCell($lineTotal, 'right')
        . '</tr>';
    $lineNo++;
}

$minRows = max($lineNo, 12);
for ($i = $lineNo; $i <= $minRows; $i++) {
    $html .= '<tr>'
        . medicapLineCell('&nbsp;')
        . medicapLineCell('&nbsp;')
        . medicapLineCell('&nbsp;')
        . '<td style="' . $border . 'text-align:center;vertical-align:middle;padding:2px;">
            <table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td width="50%" style="border-right:1px solid #000;' . $qtyInner . 'height:' . $lineH . ';">&nbsp;</td>
                    <td width="50%" style="' . $qtyInner . '">&nbsp;</td>
                </tr>
            </table>
        </td>'
        . medicapLineCell('&nbsp;')
        . medicapLineCell('&nbsp;')
        . '</tr>';
}

$html .= '<tr>'
    . medicapLineCell('&nbsp;')
    . medicapLineCell('&nbsp;')
    . medicapLineCell('<span style="font-weight:bold;">Total Price</span>', 'right')
    . medicapLineCell('<span style="font-weight:bold;">U.S. ' . medicapPoEsc($usTotal) . '</span>', 'center')
    . medicapLineCell('<span style="font-weight:bold;">CDN ' . medicapPoEsc($cdnTotal) . '</span>', 'center')
    . medicapLineCell('<span style="font-weight:bold;">' . medicapPoEsc($finalTotal) . '</span>', 'right')
    . '</tr>';
$html .= '</table>';

$html .= '<table cellpadding="' . $cellPad . '" cellspacing="0" width="' . $tblW . '" style="border-collapse:collapse;margin-top:-1px;' . $base . '"><tr>
    <td width="351" style="' . $bc . '">
        <span style="' . $lbl . '">Approved By:</span> ' . medicapPoEsc($approvedBy) . '
        <span style="border-bottom:1px solid #000;display:inline-block;width:200px;">&nbsp;</span>
    </td>
    <td width="189" style="' . $bc . '">
        <span style="' . $lbl . '">Date:</span> ' . medicapPoEsc($approveDate) . '
        <span style="border-bottom:1px solid #000;display:inline-block;width:80px;">&nbsp;</span>
    </td>
</tr>';
$html .= '</table>';

$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('', 'I');
