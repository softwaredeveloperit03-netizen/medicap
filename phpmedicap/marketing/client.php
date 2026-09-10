<?php
 
// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

function escStr($conn, $value) {
    return $conn->real_escape_string(isset($value) ? $value : '');
}

function getRequestRawBody() {
    static $raw = null;
    if ($raw === null) {
        $body = file_get_contents('php://input');
        $raw = ($body === false) ? '' : $body;
    }
    return $raw;
}

function getJsonInput() {
    static $cached = null;
    static $loaded = false;
    if ($loaded) {
        return $cached;
    }
    $loaded = true;

    if (!empty($_POST) && is_array($_POST)) {
        $formKeys = array('payload', 'data', 'json');
        foreach ($formKeys as $key) {
            if (!empty($_POST[$key]) && is_string($_POST[$key])) {
                $decoded = json_decode(stripslashes($_POST[$key]), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $cached = $decoded;
                    return $cached;
                }
            }
        }
        if (isset($_POST['LglNm']) || isset($_POST['mobNo']) || isset($_POST['TrdNm'])) {
            $cached = $_POST;
            return $cached;
        }
    }

    $raw = getRequestRawBody();
    if ($raw !== false && trim($raw) !== '') {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw));
        $attempts = array($raw, stripslashes($raw));
        if (isset($raw[0]) && $raw[0] === '"') {
            $inner = json_decode($raw, true);
            if (is_string($inner)) {
                $attempts[] = $inner;
            }
        }
        foreach ($attempts as $attempt) {
            $decoded = json_decode($attempt, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cached = $decoded;
                return $cached;
            }
        }
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $parsed = array();
            parse_str($raw, $parsed);
            if (!empty($parsed) && is_array($parsed)) {
                $cached = $parsed;
                return $cached;
            }
        }
    }

    $cached = null;
    return null;
}

function parseServiceDescriptionData($input) {
    if (!is_array($input) || !isset($input['serviceDescriptionData'])) {
        return array();
    }
    $raw = $input['serviceDescriptionData'];
    if (is_array($raw)) {
        return $raw;
    }
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }
    return array();
}

function encodeServiceDescriptionData($value) {
    if (is_array($value)) {
        return json_encode($value);
    }
    return isset($value) ? $value : '';
}

function getSaveTempClientPayload($input) {
    if (!empty($_POST) && is_array($_POST)) {
        if (!empty($_POST['payload']) && is_string($_POST['payload'])) {
            $decoded = json_decode(stripslashes($_POST['payload']), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        if (isset($_POST['LglNm']) || isset($_POST['mobNo']) || isset($_POST['TrdNm'])) {
            return $_POST;
        }
    }

    $raw = getRequestRawBody();
    if ($raw !== false && trim($raw) !== '') {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw));
        $attempts = array($raw, stripslashes($raw));
        foreach ($attempts as $attempt) {
            $decoded = json_decode($attempt, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        $parsed = array();
        parse_str($raw, $parsed);
        if (!empty($parsed) && (isset($parsed['LglNm']) || isset($parsed['mobNo']) || isset($parsed['TrdNm']))) {
            return $parsed;
        }
        if (!empty($parsed['payload']) && is_string($parsed['payload'])) {
            $decoded = json_decode(stripslashes($parsed['payload']), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
    }

    if (is_array($input) && !empty($input)) {
        return $input;
    }
    return null;
}

function inputVal($input, $key, $default = '') {
    return (is_array($input) && isset($input[$key])) ? $input[$key] : $default;
}

function ensureClientServiceDetailsTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `client_service_details` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `plant_id` varchar(50) DEFAULT NULL,
      `client_code` varchar(50) DEFAULT NULL,
      `service_category` varchar(255) DEFAULT NULL,
      `products_json` text,
      `descriptions_json` text,
      `entry_by` varchar(50) DEFAULT NULL,
      `entry_date` datetime DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureClientContactPersonTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `clientContactPerson` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `plant_id` varchar(50) DEFAULT NULL,
      `client_code` varchar(50) DEFAULT NULL,
      `contactPerson` varchar(255) DEFAULT NULL,
      `designation` varchar(255) DEFAULT NULL,
      `mobNo` varchar(50) DEFAULT NULL,
      `email` varchar(255) DEFAULT NULL,
      `communicationPreference` varchar(100) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `client_code` (`client_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureClientBranchesTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `clientBranches` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `plant_id` varchar(50) DEFAULT NULL,
      `client_code` varchar(50) DEFAULT NULL,
      `branch_name` varchar(255) DEFAULT NULL,
      `address` text,
      `country` varchar(100) DEFAULT NULL,
      `state` varchar(100) DEFAULT NULL,
      `city` varchar(100) DEFAULT NULL,
      `pincode` varchar(20) DEFAULT NULL,
      `state_code` varchar(20) DEFAULT NULL,
      `gst_no` varchar(50) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `client_code` (`client_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureTempClientTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `tempClient` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `plant_id` varchar(50) DEFAULT NULL,
      `client_code` varchar(50) DEFAULT NULL,
      `status` varchar(100) DEFAULT 'Pending',
      `LglNm` varchar(255) DEFAULT NULL,
      `TrdNm` varchar(255) DEFAULT NULL,
      `client_type` varchar(100) DEFAULT NULL,
      `category` varchar(100) DEFAULT NULL,
      `contactPerson` varchar(255) DEFAULT NULL,
      `designation` varchar(255) DEFAULT NULL,
      `mobNo` varchar(50) DEFAULT NULL,
      `email` varchar(255) DEFAULT NULL,
      `communicationPreference` varchar(100) DEFAULT NULL,
      `address` text,
      `billingAddress` text,
      `country` varchar(100) DEFAULT NULL,
      `state` varchar(100) DEFAULT NULL,
      `city` varchar(100) DEFAULT NULL,
      `pincode` varchar(20) DEFAULT NULL,
      `refered_by` varchar(100) DEFAULT NULL,
      `agent_no` varchar(50) DEFAULT NULL,
      `enquiryTicketSize` varchar(100) DEFAULT NULL,
      `NoOfProducts` varchar(50) DEFAULT NULL,
      `approxTurnover` varchar(100) DEFAULT NULL,
      `companySize` varchar(100) DEFAULT NULL,
      `exitingBussiness` varchar(100) DEFAULT NULL,
      `serviceCategory` text,
      `serviceDescription` text,
      `serviceDescriptionData` longtext,
      `selectedProductCodes` text,
      `newClientCode` varchar(50) DEFAULT NULL,
      `agree_name` varchar(255) DEFAULT NULL,
      `enquiry_no` varchar(100) DEFAULT NULL,
      `entry_by` varchar(50) DEFAULT NULL,
      `entry_date` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `client_code` (`client_code`),
      KEY `plant_id` (`plant_id`),
      KEY `entry_by` (`entry_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function assignTempClientCodeIfMissing($conn, $temp_id, $plant_id) {
    $temp_id = (int)$temp_id;
    if ($temp_id <= 0) {
        return '';
    }
    $result = $conn->query("SELECT client_code FROM tempClient WHERE id = ".$temp_id);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['client_code'])) {
            return $row['client_code'];
        }
    }

    $seq = 1;
    $countResult = $conn->query("SELECT COUNT(*) AS cnt FROM tempClient WHERE plant_id = '".escStr($conn, $plant_id)."'");
    if ($countResult && $countResult->num_rows > 0) {
        $countRow = $countResult->fetch_assoc();
        $seq = max(1, (int)$countRow['cnt']);
    }
    $client_code = 'TC' . str_pad((string)$seq, 2, '0', STR_PAD_LEFT);
    $conn->query("UPDATE tempClient SET client_code = '".escStr($conn, $client_code)."' WHERE id = ".$temp_id);
    return $client_code;
}

function ensureClientRelatedTables($conn) {
    ensureTempClientTable($conn);
    ensureClientContactPersonTable($conn);
    ensureClientBranchesTable($conn);
    ensureClientServiceDetailsTable($conn);
}

function loadClientContactPerson($conn, $client_code, $markOld = false) {
    ensureClientContactPersonTable($conn);
    $output = array();
    $sql = "SELECT * FROM `clientContactPerson` WHERE `client_code`='".escStr($conn, $client_code)."' ORDER BY id ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($markOld) {
                $row['type'] = 'OLD';
            }
            $output[] = $row;
        }
    }
    return $output;
}

function loadClientBranches($conn, $client_code, $markOld = false) {
    ensureClientBranchesTable($conn);
    $output = array();
    $sql = "SELECT * FROM `clientBranches` WHERE `client_code`='".escStr($conn, $client_code)."' ORDER BY id ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($markOld) {
                $row['type'] = 'OLD';
            }
            $output[] = $row;
        }
    }
    return $output;
}

function ensureClientServiceColumns($conn) {
    $cols = array(
        'serviceCategory' => 'TEXT NULL',
        'serviceDescription' => 'TEXT NULL',
        'serviceDescriptionData' => 'LONGTEXT NULL',
        'selectedProductCodes' => 'TEXT NULL'
    );
    foreach ($cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `client` LIKE '".$col."'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `client` ADD COLUMN `".$col."` ".$def);
        }
    }
}

function ensureTempClientServiceColumns($conn) {
    ensureTempClientTable($conn);
    $cols = array(
        'serviceCategory' => 'TEXT NULL',
        'serviceDescription' => 'TEXT NULL',
        'serviceDescriptionData' => 'LONGTEXT NULL',
        'selectedProductCodes' => 'TEXT NULL'
    );
    foreach ($cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `tempClient` LIKE '".$col."'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `tempClient` ADD COLUMN `".$col."` ".$def);
        }
    }
}

function updateTempClientServiceSummary($conn, $temp_id, $input) {
    ensureTempClientServiceColumns($conn);
    $serviceCategory = escStr($conn, isset($input['serviceCategory']) ? $input['serviceCategory'] : '');
    $serviceDescription = escStr($conn, isset($input['serviceDescription']) ? $input['serviceDescription'] : '');
    $serviceDescriptionData = escStr($conn, encodeServiceDescriptionData(isset($input['serviceDescriptionData']) ? $input['serviceDescriptionData'] : ''));
    $selectedProductCodes = escStr($conn, isset($input['selectedProductCodes']) ? $input['selectedProductCodes'] : '');
    $sql = "UPDATE `tempClient` SET
        `serviceCategory`='".$serviceCategory."',
        `serviceDescription`='".$serviceDescription."',
        `serviceDescriptionData`='".$serviceDescriptionData."',
        `selectedProductCodes`='".$selectedProductCodes."'
        WHERE `id`='".escStr($conn, $temp_id)."'";
    $conn->query($sql);
}

function appendTempClientServiceData($conn, &$row) {
    $client_code = isset($row['client_code']) ? $row['client_code'] : '';
    if ($client_code !== '') {
        $row['clientServiceDetails'] = loadClientServiceDetails($conn, $client_code);
    } else {
        $row['clientServiceDetails'] = array();
    }
}

function saveClientServiceDetails($conn, $plant_id, $client_code, $emp_id, $entry_date, $input) {
    ensureClientServiceDetailsTable($conn);
    $serviceData = parseServiceDescriptionData($input);
    $savedEntries = (is_array($serviceData) && isset($serviceData['savedEntries'])) ? $serviceData['savedEntries'] : array();
    if (!is_array($savedEntries)) {
        return;
    }
    foreach ($savedEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $category = escStr($conn, isset($entry['category']) ? $entry['category'] : '');
        $productsJson = escStr($conn, json_encode(isset($entry['products']) ? $entry['products'] : array()));
        $descJson = escStr($conn, json_encode(isset($entry['descriptions']) ? $entry['descriptions'] : array()));
        $sqlSvc = "INSERT INTO `client_service_details` (`plant_id`, `client_code`, `service_category`, `products_json`, `descriptions_json`, `entry_by`, `entry_date`)
        VALUES ('".$plant_id."', '".$client_code."', '".$category."', '".$productsJson."', '".$descJson."', '".$emp_id."', '".$entry_date."')";
        $conn->query($sqlSvc);
    }
}

function loadClientServiceDetails($conn, $client_code) {
    ensureClientServiceDetailsTable($conn);
    $output = array();
    $sql = "SELECT * FROM `client_service_details` WHERE `client_code`='".escStr($conn, $client_code)."' ORDER BY id ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['products'] = json_decode($row['products_json'], true);
            $row['descriptions'] = json_decode($row['descriptions_json'], true);
            $output[] = $row;
        }
    }
    return $output;
}

function updateClientServiceSummary($conn, $client_id, $input) {
    ensureClientServiceColumns($conn);
    $serviceCategory = escStr($conn, isset($input['serviceCategory']) ? $input['serviceCategory'] : '');
    $serviceDescription = escStr($conn, isset($input['serviceDescription']) ? $input['serviceDescription'] : '');
    $serviceDescriptionData = escStr($conn, encodeServiceDescriptionData(isset($input['serviceDescriptionData']) ? $input['serviceDescriptionData'] : ''));
    $selectedProductCodes = escStr($conn, isset($input['selectedProductCodes']) ? $input['selectedProductCodes'] : '');
    $sql = "UPDATE `client` SET
        `serviceCategory`='".$serviceCategory."',
        `serviceDescription`='".$serviceDescription."',
        `serviceDescriptionData`='".$serviceDescriptionData."',
        `selectedProductCodes`='".$selectedProductCodes."'
        WHERE `id`='".escStr($conn, $client_id)."'";
    $conn->query($sql);
}

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = getJsonInput();

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    ensureClientRelatedTables($conn);
    
    if($_GET["type"]=="saveClient") {
        if (!is_array($input)) {
            echo json_encode(array("status" => "failed", "message" => "Invalid request body"));
        } else {
            $plant_id = escStr($conn, $_GET["plant_id"]);
            $emp_id = escStr($conn, $_GET["emp_id"]);
            $tempClientCode = escStr($conn, isset($input["tempClientCode"]) ? $input["tempClientCode"] : '');

            $sql = "INSERT INTO `client`(`plant_id`, `status`, `password`, `LglNm`, `TrdNm`, `client_type`, `category`, `dateOfOnboarding`, `contactPerson`,
            `designation`, `mobNo`, `email`, `communicationPreference`, `address`, `billingAddress`, `country`, `state`, `city`, `pincode`, `state_code`,
            `gst_no`, `pan_no`, `importExportCode`, `dlNo`, `fdaCdscoNo`, `refered_by`, `agent_no`,`entry_by`, `entry_date`,`tempClientCode`) VALUES 
            ('".$plant_id."', 'Pending', 'Gmp@123',
            '".escStr($conn, $input["LglNm"])."','".escStr($conn, $input["TrdNm"])."','".escStr($conn, $input["client_type"])."','".escStr($conn, $input["category"])."',
            '".escStr($conn, $input["dateOfOnboarding"])."', '".escStr($conn, $input["contactPerson"])."', '".escStr($conn, $input["designation"])."','".escStr($conn, $input["mobNo"])."','".escStr($conn, $input["email"])."',
            '".escStr($conn, $input["communicationPreference"])."','".escStr($conn, $input["address"])."','".escStr($conn, $input["billingAddress"])."','".escStr($conn, $input["country"])."', '".escStr($conn, $input["state"])."',
            '".escStr($conn, $input["city"])."', '".escStr($conn, $input["pincode"])."','".escStr($conn, $input["state_code"])."','".escStr($conn, $input["gst_no"])."', '".escStr($conn, $input["pan_no"])."',
            '".escStr($conn, $input["importExportCode"])."','".escStr($conn, $input["dlNo"])."', '".escStr($conn, $input["fdaCdscoNo"])."','".escStr($conn, $input["refered_by"])."','".escStr($conn, $input["agent_no"])."',
            '".$emp_id."', '$entry_date','".$tempClientCode."')";

            if($conn->query($sql)) {
                $last_id = $conn->insert_id;
                $client_code = '';
                $result = $conn->query("SELECT client_code FROM client WHERE id = ".$last_id);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $client_code = $row['client_code'];
                }

                $array1 = (isset($input["personData"]) && is_array($input["personData"])) ? $input["personData"] : array();
                foreach ($array1 as $values){
                    if (!is_array($values)) {
                        continue;
                    }
                    $sql1 = "INSERT INTO `clientContactPerson`(`plant_id`, `client_code`, `contactPerson`, `designation`, `mobNo`, `email`,
                    `communicationPreference`) VALUES  ('".$plant_id."','".escStr($conn, $client_code)."','".escStr($conn, $values["contactPerson"])."','".escStr($conn, $values["designation"])."',
                    '".escStr($conn, $values["mobNo"])."','".escStr($conn, $values["email"])."','".escStr($conn, $values["communicationPreference"])."')";
                    $conn->query($sql1);
                }

                $array2 = (isset($input["branches"]) && is_array($input["branches"])) ? $input["branches"] : array();
                foreach ($array2 as $values){
                    if (!is_array($values)) {
                        continue;
                    }
                    $sql2 = "INSERT INTO `clientBranches`(`plant_id`, `client_code`, `branch_name`, `address`, `country`, `state`, `city`, `pincode`,
                    `state_code`, `gst_no`) VALUES   ('".$plant_id."','".escStr($conn, $client_code)."','".escStr($conn, $values["branch_name"])."','".escStr($conn, $values["address"])."',
                    '".escStr($conn, $values["country"])."','".escStr($conn, $values["state"])."','".escStr($conn, $values["city"])."','".escStr($conn, $values["pincode"])."','".escStr($conn, $values["state_code"])."',
                    '".escStr($conn, $values["gst_no"])."')";
                    $conn->query($sql2);
                }

                if(isset($input['clientFrom']) && $input['clientFrom'] == 'Lead' && $tempClientCode != ''){
                    $sql22 = "UPDATE tempClient SET status = 'Registration_Complete' , newClientCode = '".escStr($conn, $client_code)."'
                    WHERE client_code = '".$tempClientCode."' ";
                    $conn->query($sql22);
                }

                updateClientServiceSummary($conn, $last_id, $input);
                if ($client_code != '') {
                    saveClientServiceDetails($conn, $plant_id, $client_code, $emp_id, $entry_date, $input);
                }

                echo "{\"status\":\"success\"}";
            } else {
                echo json_encode(array("status" => "failed", "message" => $conn->error));
            }
        }
    
    
      
    } 
    else if($_GET["type"]=="saveTempClient") {
        ensureTempClientTable($conn);
        $payload = getSaveTempClientPayload($input);
        if (!is_array($payload)) {
            echo json_encode(array(
                "status" => "failed",
                "message" => "Invalid request body"
            ));
        } else {
            $plant_id = escStr($conn, $_GET["plant_id"]);
            $emp_id = escStr($conn, $_GET["emp_id"]);

            $sql = "INSERT INTO `tempClient`(`plant_id`, `status`, `LglNm`, `TrdNm`, `client_type`, `category`, `contactPerson`,`designation`, `mobNo`, `email`,
            `communicationPreference`, `address`, `billingAddress`, `country`, `state`, `city`, `pincode`,`refered_by`, `agent_no`, `enquiryTicketSize`,`NoOfProducts`,
            `approxTurnover`,`companySize`,`exitingBussiness`, `entry_by`, `entry_date`) VALUES ('".$plant_id."', 'Pending',
            '".escStr($conn, isset($payload['LglNm']) ? $payload['LglNm'] : '')."','".escStr($conn, isset($payload['TrdNm']) ? $payload['TrdNm'] : '')."','".escStr($conn, isset($payload['client_type']) ? $payload['client_type'] : '')."','".escStr($conn, isset($payload['category']) ? $payload['category'] : '')."',
            '".escStr($conn, isset($payload['contactPerson']) ? $payload['contactPerson'] : '')."', '".escStr($conn, isset($payload['designation']) ? $payload['designation'] : '')."','".escStr($conn, isset($payload['mobNo']) ? $payload['mobNo'] : '')."',
            '".escStr($conn, isset($payload['email']) ? $payload['email'] : '')."','".escStr($conn, isset($payload['communicationPreference']) ? $payload['communicationPreference'] : '')."','".escStr($conn, isset($payload['address']) ? $payload['address'] : '')."',
            '".escStr($conn, isset($payload['billingAddress']) ? $payload['billingAddress'] : '')."','".escStr($conn, isset($payload['country']) ? $payload['country'] : '')."', '".escStr($conn, isset($payload['state']) ? $payload['state'] : '')."',
            '".escStr($conn, isset($payload['city']) ? $payload['city'] : '')."', '".escStr($conn, isset($payload['pincode']) ? $payload['pincode'] : '')."', '".escStr($conn, isset($payload['refered_by']) ? $payload['refered_by'] : '')."',
            '".escStr($conn, isset($payload['agent_no']) ? $payload['agent_no'] : '')."','".escStr($conn, isset($payload['enquiryTicketSize']) ? $payload['enquiryTicketSize'] : '')."',
            '".escStr($conn, isset($payload['NoOfProducts']) ? $payload['NoOfProducts'] : '')."','".escStr($conn, isset($payload['approxTurnover']) ? $payload['approxTurnover'] : '')."',
            '".escStr($conn, isset($payload['companySize']) ? $payload['companySize'] : '')."','".escStr($conn, isset($payload['exitingBussiness']) ? $payload['exitingBussiness'] : '')."', '".$emp_id."','$entry_date')";

            if($conn->query($sql)) {
                $last_id = $conn->insert_id;
                $client_code = assignTempClientCodeIfMissing($conn, $last_id, $plant_id);

                if (function_exists('updateTempClientServiceSummary')) {
                    @updateTempClientServiceSummary($conn, $last_id, $payload);
                }
                if ($client_code != '' && function_exists('saveClientServiceDetails')) {
                    @saveClientServiceDetails($conn, $plant_id, $client_code, $emp_id, $entry_date, $payload);
                }

                echo "{\"status\":\"success\"}";
            } else {
                echo json_encode(array("status" => "failed", "message" => $conn->error));
            }
        }
     
    } 
    else if($_GET["type"]=="updateClient") {
     
     
     // `createGroup`='".$input['createGroup']."', `clientGroup`='".$input['clientGroup']."',
     
        $sql = "UPDATE `client` SET `status`='Pending', `LglNm`='".$input['LglNm']."',`TrdNm`='".$input['TrdNm']."',
        `client_type`='".$input['client_type']."',`category`='".$input['category']."',`dateOfOnboarding`='".$input['dateOfOnboarding']."',
        `contactPerson`='".$input['contactPerson']."',`designation`='".$input['designation']."',`mobNo`='".$input['mobNo']."',
        `email`='".$input['email']."',`communicationPreference`='".$input['communicationPreference']."',`address`='".$input['address']."',
        `billingAddress`='".$input['billingAddress']."',`country`='".$input['country']."',`state`='".$input['state']."',`city`='".$input['city']."',
        `pincode`='".$input['pincode']."',`state_code`='".$input['state_code']."',`gst_no`='".$input['gst_no']."',`pan_no`='".$input['pan_no']."',
        `importExportCode`='".$input['importExportCode']."',`dlNo`='".$input['dlNo']."',`fdaCdscoNo`='".$input['fdaCdscoNo']."', `clientGroup`='".$input['clientGroup']."',
        `refered_by`='".$input['refered_by']."',`agent_no`='".$input['agent_no']."',`entry_by`='".$_GET["emp_id"]."',`entry_date`='$entry_date'
        WHERE id = '".$input['id']."' "; 
          
        if($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
       
            $client_code = $input['client_code'];
           
            $personData = json_encode($input["clientContactPerson"]);
            $array1 = json_decode($personData, true);
            foreach ($array1 as $values){
                if($values['type'] != 'OLD'){
                    $sql1 = "INSERT INTO `clientContactPerson`(`plant_id`, `client_code`, `contactPerson`, `designation`, `mobNo`, `email`, 
                    `communicationPreference`) VALUES  ('".$_GET["plant_id"]."','".$client_code."','".$values["contactPerson"]."','".$values["designation"]."',
                    '".$values["mobNo"]."','".$values["email"]."','".$values["communicationPreference"]."')";
    
                    $conn->query($sql1);   
                }
            }    
            
            $branches = json_encode($input["clientBranches"]);
            $array2 = json_decode($branches, true);
            foreach ($array2 as $values){
                if($values['type'] != 'OLD'){
                    $sql2 = "INSERT INTO `clientBranches`(`plant_id`, `client_code`, `branch_name`, `address`, `country`, `state`, `city`, `pincode`, 
                    `state_code`, `gst_no`) VALUES   ('".$_GET["plant_id"]."','".$client_code."','".$values["branch_name"]."','".$values["address"]."',
                    '".$values["country"]."','".$values["state"]."','".$values["city"]."','".$values["pincode"]."','".$values["state_code"]."',
                    '".$values["gst_no"]."')";
    
                    $conn->query($sql2);   
                }
            }
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
      
    } 
     
    else if($_GET["type"]=="getClientProfileById") {
        $output = array();
    	$sql = "SELECT * from client where   client_code = '".$_GET['emp_id']."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
            	$sql11 = "SELECT LglNm as groupCodeName from client where client_code = '".escStr($conn, $row['clientGroup'])."' LIMIT 1";
            	$result11 = $conn->query($sql11);
            	if($result11 && $result11->num_rows > 0){
            		while($row11 = $result11->fetch_assoc()) {
            		    $row['groupCodeName'] = $row11['groupCodeName'];
            		}
            	}else{
            	    $row['groupCodeName'] = 'NA';
            	}

    		    $row['clientContactPerson'] = loadClientContactPerson($conn, $row['client_code'], true);
    		    $row['clientBranches'] = loadClientBranches($conn, $row['client_code'], true);
    		    $row['clientServiceDetails'] = loadClientServiceDetails($conn, $row['client_code']);
    		    $output = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getClientsDetails") {
        $output = array();
    	$sql = "SELECT * from client where status != 'Pending' order by LglNm ASC";
    	$result = $conn->query($sql);
    	if($result && $result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
            	$sql11 = "SELECT LglNm as groupCodeName from client where client_code = '".escStr($conn, $row['clientGroup'])."' LIMIT 1";
            	$result11 = $conn->query($sql11);
            	if($result11 && $result11->num_rows > 0){
            		while($row11 = $result11->fetch_assoc()) {
            		    $row['groupCodeName'] = $row11['groupCodeName'];
            		}
            	}else{
            	    $row['groupCodeName'] = 'NA';
            	}

    		    $row['clientContactPerson'] = loadClientContactPerson($conn, $row['client_code'], true);
    		    $row['clientBranches'] = loadClientBranches($conn, $row['client_code'], true);
    		    $row['clientServiceDetails'] = loadClientServiceDetails($conn, $row['client_code']);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getActiveClient") {
        $output = array();
    	$sql = "SELECT `id`, `plant_id`, `status`, `client_code`,  `LglNm`, `TrdNm`, `client_type`, `category`, `dateOfOnboarding`, `contactPerson`, `designation`, `mobNo`, `email`,`createGroup`, 
    	`clientGroup`, `entry_by`, `entry_date`, `approvedBy`, `approvedOn` from client where status = 'Active' order by LglNm ASC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getClientSeries") {
        $output = array();
    	$sql = "SELECT `id`, `plant_id`, `status`, `client_code`,  `LglNm`, `TrdNm`, `client_type`, `category`, `dateOfOnboarding`, `contactPerson`, `designation`, `mobNo`, `email`,`createGroup`, 
    	`clientGroup`, `entry_by`, `entry_date`, `approvedBy`, `approvedOn` from client where status = 'Active' AND clientGroup = '".$_GET["client_code"]."' order by LglNm ASC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getTempClient") {
        ensureTempClientTable($conn);
        $output = array();
        
        if($_GET['clientFor'] == 'Yes'){
        	$sql = "SELECT c.*,CONCAT(e.firstname, ' ', e.emp_id) AS entryByName from tempClient c LEFT JOIN employee e ON c.entry_by = e.emp_id 
        	order by c.LglNm ASC";
        }else{
        	$sql = "SELECT c.*,CONCAT(e.firstname, ' ', e.emp_id) AS entryByName from tempClient c LEFT JOIN employee e ON c.entry_by = e.emp_id 
        	 where c.entry_by = '".$_GET['emp_id']."' order by c.LglNm ASC";
        }
        

    	$result = $conn->query($sql);
    	if($result && $result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    appendTempClientServiceData($conn, $row);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getClientFOrLegalApproval") {
        ensureTempClientTable($conn);
        $output = array();
        
    	$sql = "SELECT c.*,CONCAT(e.firstname, ' ', e.emp_id) AS entryByName from tempClient c LEFT JOIN employee e ON c.entry_by = e.emp_id 
    	WHERE  c.status = 'To_Legal' order by c.LglNm ASC";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getTempClientForRegisterByLeadRegiId") {
        ensureTempClientTable($conn);
        $output = array();

        $sql = "SELECT c.*,CONCAT(e.firstname, ' ', e.emp_id) AS entryByName from tempClient c LEFT JOIN employee e ON c.entry_by = e.emp_id 
    	WHERE  c.status = 'For_Registration_In_Marketing' AND c.entry_by = '".$_GET['emp_id']."'  order by c.LglNm ASC";
    	
    	$result = $conn->query($sql);
    	if($result && $result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    appendTempClientServiceData($conn, $row);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getClientForMarketing") {
        
        $output = array();
        
        if($_GET['clientFor'] == 'Yes'){
        	$sql = "SELECT c.* from client_combined_view c order by c.LglNm ASC";
        }else{
            $sql = "SELECT c.* from client_combined_view c  where c.entry_by = '".$_GET['emp_id']."' order by LglNm ASC";
        }
        

    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    appendTempClientServiceData($conn, $row);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getClientWithGroup") {
        $output = array();
    	$sql = "SELECT id,plant_id,status,client_code,LglNm,TrdNm,clientGroup from client where status = 'Active' order by LglNm ASC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    
            	$output1 = array();
            	$sql11 = "SELECT id,plant_id,status,client_code,LglNm,TrdNm,clientGroup from client where status = 'Active' AND clientGroup = '".$row['client_code']."'  ";
            	$result11 = $conn->query($sql11);
            	if($result11->num_rows > 0){
            		while($row11 = $result11->fetch_assoc()) {
            		    $output1[] = $row11;
            		}
            	}
            	
    		    $row['clientGroups'] = $output1;
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if($_GET["type"]=="getPendingClients") {
        $output = array();
        $plant_id = escStr($conn, $_GET["plant_id"]);
        $sql = "SELECT * from client where status = 'Pending'";
        if ($plant_id !== '') {
            $sql .= " AND plant_id = '".$plant_id."'";
        }
        $sql .= " order by LglNm ASC";
    	$result = $conn->query($sql);
    	if($result && $result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
            	$client_code = escStr($conn, $row['client_code']);
            	$sql11 = "SELECT LglNm as groupCodeName from client where client_code = '".escStr($conn, $row['clientGroup'])."' LIMIT 1";
            	$result11 = $conn->query($sql11);
            	if($result11 && $result11->num_rows > 0){
            		while($row11 = $result11->fetch_assoc()) {
            		    $row['groupCodeName'] = $row11['groupCodeName'];
            		}
            	}else{
            	    $row['groupCodeName'] = 'NA';
            	}

    		    $row['clientContactPerson'] = loadClientContactPerson($conn, $client_code);
    		    $row['clientBranches'] = loadClientBranches($conn, $client_code);
    		    $row['clientServiceDetails'] = ($client_code !== '') ? loadClientServiceDetails($conn, $client_code) : array();
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updatePendingClients") {
        $approvedBy = isset($_GET["approval_by"]) && $_GET["approval_by"] != ''
            ? escStr($conn, $_GET["approval_by"])
            : escStr($conn, $_GET["emp_id"]);
        $sql = "UPDATE client SET status = '".escStr($conn, $_GET["status"])."' , approvedBy='".$approvedBy."', approvedOn = '$entry_date'
        WHERE id='".escStr($conn, $_GET["id"])."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    }
    else if($_GET["type"]=="getClientsLog") {
        $output = array();
      	$sql = "SELECT id,LglNm,client_code,TrdNm,clientGroup,createGroup FROM client where status = 'Active' "; 
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    else if ($_GET["type"] == "uploadClientAgrement") {
        
        $clcode      = $_POST["client_code"];
        $agree_name  = $_POST["agree_name"];
        $valid_till  = $_POST["valid_till"];
        $doc_type    = $_POST["doc_type"];
        $file_name   = null;
    
        if (isset($_FILES["agreFile"]) && $_FILES["agreFile"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = "../../../upload/client/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
    
            $fileTmp  = $_FILES["agreFile"]["tmp_name"];
            $original = $_FILES["agreFile"]["name"];
            $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    
            // Allow only certain extensions
            $allowed = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
            if (!in_array($ext, $allowed)) {
                echo json_encode(["status" => "Invalid file type"]);
                exit;
            }
    
            // Create a unique filename
            $file_name = $agree_name . "_" . $clcode . "_" . time() . "." . $ext;
            $target    = $uploadDir . $file_name;
    
            if (!move_uploaded_file($fileTmp, $target)) {
                echo json_encode(["status" => "File upload failed"]);
                exit;
            }
        }
    
        // Insert into DB
        $sql = "INSERT INTO client_agrements (agree_name, client_code, valid_till, doc_type, file_name) 
                VALUES ('$agree_name', '$clcode', '$valid_till', '$doc_type', '$file_name')";
    
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => $conn->error]);
        }
    }
    else if ($_GET["type"] == "uploadClientAgreementFromLegal") {
        
        $clcode      = $_POST["client_code"];
        $agree_name  = "firstLegalAgree";
        
        $file_name   = null;
        if (isset($_FILES["agreFile"]) && $_FILES["agreFile"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = "../../../upload/client/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
    
            $fileTmp  = $_FILES["agreFile"]["tmp_name"];
            $original = $_FILES["agreFile"]["name"];
            $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    
            // Allow only certain extensions
            $allowed = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
            if (!in_array($ext, $allowed)) {
                echo json_encode(["status" => "Invalid file type"]);
                exit;
            }
    
            // Create a unique filename
            $file_name = $agree_name . "_" . $clcode . "_" . time() . "." . $ext;
            $target    = $uploadDir . $file_name;
    
            if (!move_uploaded_file($fileTmp, $target)) {
                echo json_encode(["status" => "File upload failed"]);
                exit;
            }
        }
    
 
        $sql = "UPDATE tempClient SET agree_name = '$file_name' , status = 'For_Registration_In_Marketing' WHERE client_code = '".$_POST["client_code"]."' ";
    
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => $conn->error]);
        }
    }

    else if ($_GET["type"] == "getClientAgrement") {
        $output = Array();
        $sql = "SELECT *  FROM client_agrements WHERE client_code='".$_GET["client_code"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

}


$conn->close();
?>