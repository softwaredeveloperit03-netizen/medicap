<?php
/**
 * WhatsApp - New operator API (template with text header + body).
 * gatePassWhatsAppSms($phoneNumber, $meetingwithName)
 * Body params (from plant + param): plant_full_name, meetingwithName, saftyInstruction, GMPInstruction, visitVideoLink, plant_full_name
 */

require 'db.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"] ?? '';
$timestamp = time();
$entry_time = date("h:i:s", $timestamp);
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);



$rowplant = [];
if (!empty($_GET['plant_id']) && isset($conn)) {
    $pid = $conn->real_escape_string($_GET['plant_id']);
    $sqlplant = "SELECT * FROM plant WHERE plant_id = '$pid' LIMIT 1";
    $resultplant = $conn->query($sqlplant);
    if ($resultplant && $resultplant->num_rows > 0) {
        $rowplant = $resultplant->fetch_assoc();
    }
}
$plant_full_name   = $rowplant['plant_full_name'] ?? '';
$saftyInstruction = $rowplant['saftyInstruction'] ?? 'Please Add Safty Instruction';
$GMPInstruction   = $rowplant['GMPInstruction'] ?? 'Please Add GMP Instruction';
$visitVideoLink   = $rowplant['visitVideoLink'] ?? 'Please Add Visti Video Link';

// --- Config (edit these) ---
define('WHATSAPP_API_BASE_URL', 'https://api.uniquedigitaloutreach.com');
define('WHATSAPP_API_KEY', 'cKnsuearAAuRgGK88k7c0CptFBWp03');
define('WHATSAPP_FROM', '+15558342917');
define('WHATSAPP_CAMPAIGN_NAME', 'gatepass');
define('WHATSAPP_TEMPLATE_NAME', 'gatepass');






/**
 * Send WhatsApp template (text header + body params).
 * Same API/curl pattern as working downloadPdf (media); this uses type "text" for header per operator doc.
 * @param string $phoneNumber     Recipient phone (e.g. 9876543210 or +919876543210)
 * @param string $meetingwithName Meeting-with person name (body param 2)
 * @return bool  true on success, false on failure
 */


function gatePassWhatsAppSms($phoneNumber, $meetingwithName) {
    global $plant_full_name, $saftyInstruction, $GMPInstruction, $visitVideoLink;

    $apikey = WHATSAPP_API_KEY;
    $fromNumber = WHATSAPP_FROM;

    // Sanitize phone: same as working downloadPdf (digits only, then 91 prefix for India)
    $toNumber = preg_replace('/[^0-9]/', '', trim((string) $phoneNumber));
    if (empty($toNumber)) {
        error_log('WhatsApp gatepass: empty phone number');
        return false;
    }
    if (strlen($toNumber) == 10) {
        $toNumber = '91' . $toNumber;
    } elseif (strpos($toNumber, '91') !== 0 && strlen($toNumber) > 10) {
        $toNumber = '91' . substr($toNumber, -10);
    }
    $toNumber = '+' . $toNumber;

    $headerText = ($plant_full_name !== null && $plant_full_name !== '') ? $plant_full_name : 'Visitor Pass';
    // Template expects exactly 5 body variables (order must match template in operator dashboard)
    $bodyParams = [
        (string) ($meetingwithName ?? ''),
        (string) ($saftyInstruction ?? ''),
        (string) ($GMPInstruction ?? ''),
        (string) ($visitVideoLink ?? ''),
        (string) ($plant_full_name ?? '')

    ];

    // Payload structure as per operator doc (text header)
    $payload = [
        'from'         => $fromNumber,
        'campaignName' => WHATSAPP_CAMPAIGN_NAME,
        'to'           => $toNumber,
        'templateName' => WHATSAPP_TEMPLATE_NAME,
        'components'   => [
            'body'   => [ 'params' => $bodyParams ],
            'header' => [ 'type' => 'text', 'text' => $headerText ]
        ],
        'type' => 'template'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.uniquedigitaloutreach.com/v1/whatsapp');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $apikey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $logFile = __DIR__ . '/whatsapp_gatepass_debug.log';
    $logEntry = date('Y-m-d H:i:s') . ' | Gatepass To: ' . $toNumber . ' | HTTP: ' . $httpCode . ' | Response: ' . substr((string) $response, 0, 500) . ($curlError ? ' | cURL Error: ' . $curlError : '') . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    if ($curlError) {
        error_log('WhatsApp API cURL Error: ' . $curlError);
        return false;
    }

    return ($httpCode >= 200 && $httpCode < 300);
}





function awaitingVisitorNOtification($visitDate, $visitorName , $meetingwith , $company ) {
    global $plant_full_name;

    $apikey = WHATSAPP_API_KEY;
    $fromNumber = WHATSAPP_FROM;



    $rowplant = [];
    if (!empty($meetingwith) && isset($conn)) {
        $pid = $conn->real_escape_string($_GET['plant_id']);
        $sqlplant = "SELECT CONCAT(firstname,' ',lastname) AS meetingWithName , contact_no FROM employee WHERE plant_id = '$pid' AND emp_id = '$meetingwith' LIMIT 1";
        $resultplant = $conn->query($sqlplant);
        if ($resultplant && $resultplant->num_rows > 0) {
            $rowplant = $resultplant->fetch_assoc();
        }
    }
    $meetingWithName   = $rowplant['meetingWithName'] ?? '';
    $phoneNumber   = $rowplant['contact_no'] ?? '';




    // Sanitize phone: same as working downloadPdf (digits only, then 91 prefix for India)
    $toNumber = preg_replace('/[^0-9]/', '', trim((string) $phoneNumber));
    if (empty($toNumber)) {
        error_log('WhatsApp gatepass: empty phone number');
        return false;
    }
    if (strlen($toNumber) == 10) {
        $toNumber = '91' . $toNumber;
    } elseif (strpos($toNumber, '91') !== 0 && strlen($toNumber) > 10) {
        $toNumber = '91' . substr($toNumber, -10);
    }
    $toNumber = '+' . $toNumber;

    $headerText = 'Awaiting Visitor Notification';
    // Template expects exactly 5 body variables (order must match template in operator dashboard)
    $bodyParams = [
        (string) ($meetingWithName ?? ''),
        (string) ($visitorName ?? ''),
        (string) ($company ?? ''),
        (string) ($visitDate ?? ''),
        (string) ($plant_full_name ?? '')

    ];

    // Payload structure as per operator doc (text header)
    $payload = [
        'from'         => $fromNumber,
        'campaignName' => WHATSAPP_CAMPAIGN_NAME,
        'to'           => $toNumber,
        'templateName' => WHATSAPP_TEMPLATE_NAME,
        'components'   => [
            'body'   => [ 'params' => $bodyParams ],
            'header' => [ 'type' => 'text', 'text' => $headerText ]
        ],
        'type' => 'template'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.uniquedigitaloutreach.com/v1/whatsapp');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $apikey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $logFile = __DIR__ . '/whatsapp_gatepass_debug.log';
    $logEntry = date('Y-m-d H:i:s') . ' | Gatepass To: ' . $toNumber . ' | HTTP: ' . $httpCode . ' | Response: ' . substr((string) $response, 0, 500) . ($curlError ? ' | cURL Error: ' . $curlError : '') . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    if ($curlError) {
        error_log('WhatsApp API cURL Error: ' . $curlError);
        return false;
    }

    return ($httpCode >= 200 && $httpCode < 300);
}
