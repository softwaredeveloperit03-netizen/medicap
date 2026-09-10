<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
require __DIR__.'/retest_helpers.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');
$token = $_GET['token'] ?? '';
$timestamp = time();
$entry_date = date('Y-m-d h:i:s', $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string((string)$token)."'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
$_GET['plant_id'] = $_GET['plant_id'] ?? '';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    $type = $_GET['type'] ?? '';
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$type.'", "actiontime": "'.$entry_date.'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($type === 'getRetestIntimationCandidates') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_retest_intimation_candidates($conn));
    } else if ($type === 'saveRetestIntimationSlip') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_save_retest_intimation_slip($conn, $input, $_GET['plant_id'] ?? '', $_GET['emp_id'] ?? '', $entry_date));
    } else if ($type === 'getPendingRetestIntimationSlips') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_retest_intimation_slip_rows($conn, 'sent'));
    } else if ($type === 'getRetestIntimationSlipLog') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_retest_intimation_slip_rows($conn, ''));
    } else if ($type === 'getRetestIntimationSlipDetail') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_retest_intimation_slip_detail($conn, $_GET['id'] ?? ''));
    } else if ($type === 'receiveRetestIntimationSlip') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_receive_retest_intimation_slip($conn, $input, $_GET['emp_id'] ?? '', $entry_date));
    } else if ($type === 'downloadRetestIntimationSlip') {
        $detail = medicap_retest_intimation_slip_detail($conn, $_GET['id'] ?? '');
        $header = $detail['header'];
        $lines = $detail['lines'];
        if (!$header) {
            echo json_encode(array('status' => 'failed', 'msg' => 'Slip not found.'));
        } else {
            $_GET['filename'] = 'Retest Intimation Slip';
            $_GET['pdftype'] = 'onlyheader';
            include('../pdfimp2.php');
            $html = '<h2 style="text-align:center">Retest Intimation Slip</h2>
                <p><b>Slip No:</b> '.htmlspecialchars($header['slip_no']).' &nbsp; <b>Date:</b> '.htmlspecialchars($header['intimation_date']).'</p>
                <p><b>Status:</b> '.htmlspecialchars($header['status']).' &nbsp; <b>Sent By:</b> '.htmlspecialchars($header['sent_by'] ?? '-').'</p>
                <table border="1" cellpadding="4" style="width:100%;border-collapse:collapse;">
                <tr style="background:#DDDAD9;font-weight:bold;">
                    <td>Sr</td><td>Receiving No.</td><td>Material</td><td>Medicap Lot No</td><td>Release</td><td>Retest</td><td>Due</td><td>Qty</td>
                </tr>';
            $i = 1;
            foreach ($lines as $line) {
                $html .= '<tr>
                    <td>'.$i.'</td>
                    <td>'.htmlspecialchars($line['grn_no'] ?? '').'</td>
                    <td>'.htmlspecialchars(($line['material_code'] ?? '').' '.($line['material_name'] ?? '')).'</td>
                    <td>'.htmlspecialchars($line['batch_no'] ?? '').'</td>
                    <td>'.htmlspecialchars($line['release_date'] ?? '').'</td>
                    <td>'.htmlspecialchars($line['retest_date'] ?? '').'</td>
                    <td>'.htmlspecialchars((string)($line['due_days'] ?? '')).'</td>
                    <td>'.htmlspecialchars(($line['qty'] ?? '').' '.($line['unit'] ?? '')).'</td>
                </tr>';
                $i++;
            }
            $html .= '</table>';
            if (!empty($header['remarks'])) {
                $html .= '<p><b>Remarks:</b> '.htmlspecialchars($header['remarks']).'</p>';
            }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('retest-intimation-slip.pdf', 'I');
        }
    }
}

$conn->close();
