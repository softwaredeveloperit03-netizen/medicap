<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json; charset=UTF-8');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set("Asia/Kolkata");
require '../db.php';
require '../token.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!is_array($input)) {
    $input = array();
}

function so_json_out($data) {
    echo json_encode($data);
    exit;
}

function so_esc($conn, $val) {
    return $conn->real_escape_string((string)$val);
}

function so_emp_name($conn, $empId) {
    if (!$empId) {
        return '';
    }
    $res = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".so_esc($conn, $empId)."' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nm = trim($row['nm'] ?? '');
        if ($nm !== '') {
            return $nm;
        }
    }
    return $empId;
}

function ensure_shipping_order_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `qc_shipping_order` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `order_no` varchar(50) DEFAULT NULL,
        `ship_to` varchar(255) DEFAULT NULL,
        `ship_to_client_code` varchar(50) DEFAULT NULL,
        `po_number` varchar(100) DEFAULT NULL,
        `ship_to_address` text DEFAULT NULL,
        `from_tel` varchar(50) DEFAULT NULL,
        `from_fax` varchar(50) DEFAULT NULL,
        `tests_required` text DEFAULT NULL,
        `quotation_ref` varchar(255) DEFAULT NULL,
        `shipping_vendor` varchar(255) DEFAULT NULL,
        `shipping_vendor_no` varchar(50) DEFAULT NULL,
        `packaged_by` varchar(100) DEFAULT NULL,
        `packaged_by_emp` varchar(50) DEFAULT NULL,
        `packaged_date` date DEFAULT NULL,
        `checked_by` varchar(100) DEFAULT NULL,
        `checked_by_emp` varchar(50) DEFAULT NULL,
        `checked_date` date DEFAULT NULL,
        `shipped_by` varchar(100) DEFAULT NULL,
        `shipped_by_emp` varchar(50) DEFAULT NULL,
        `shipped_date` date DEFAULT NULL,
        `results_reviewed_by` varchar(100) DEFAULT NULL,
        `results_reviewed_by_emp` varchar(50) DEFAULT NULL,
        `results_reviewed_date` date DEFAULT NULL,
        `results_notes` text DEFAULT NULL,
        `status` varchar(50) DEFAULT 'draft',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $extraCols = array(
        'ship_to_client_code' => "varchar(50) DEFAULT NULL",
        'shipping_vendor_no' => "varchar(50) DEFAULT NULL",
    );
    foreach ($extraCols as $col => $def) {
        $colCheck = $conn->query("SHOW COLUMNS FROM qc_shipping_order LIKE '".so_esc($conn, $col)."'");
        if (!$colCheck || $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE qc_shipping_order ADD COLUMN `".$col."` ".$def);
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `qc_shipping_order_line` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `shipping_order_id` int(11) NOT NULL,
        `row_order` int(11) DEFAULT 0,
        `lab_sample_no` varchar(100) DEFAULT NULL,
        `quantity` varchar(50) DEFAULT NULL,
        `lot_batch_no` varchar(100) DEFAULT NULL,
        `description` varchar(500) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
}

function so_next_order_no($conn, $plantId) {
    $year = date('Y');
    $prefix = 'SO-'.$year.'-';
    $prefixEsc = so_esc($conn, $prefix);
    $plantEsc = so_esc($conn, $plantId);
    $res = $conn->query("SELECT order_no FROM qc_shipping_order
        WHERE plant_id = '".$plantEsc."' AND order_no LIKE '".$prefixEsc."%'
        ORDER BY id DESC LIMIT 1");
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $last = $row['order_no'] ?? '';
        if (preg_match('/-(\d+)$/', $last, $m)) {
            $next = intval($m[1]) + 1;
        }
    }
    return $prefix.str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

function so_fetch_order($conn, $id, $plantId) {
    $id = intval($id);
    $plantEsc = so_esc($conn, $plantId);
    $res = $conn->query("SELECT o.*,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.entry_by LIMIT 1) AS entry_by_name,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.packaged_by_emp LIMIT 1) AS packaged_by_name,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.checked_by_emp LIMIT 1) AS checked_by_name,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.shipped_by_emp LIMIT 1) AS shipped_by_name,
            (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.results_reviewed_by_emp LIMIT 1) AS results_reviewed_by_name
            FROM qc_shipping_order o
            WHERE o.id = '".$id."' AND o.plant_id = '".$plantEsc."' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return null;
    }
    $order = $res->fetch_assoc();
    $lines = array();
    $lineRes = $conn->query("SELECT * FROM qc_shipping_order_line
        WHERE shipping_order_id = '".$id."'
        ORDER BY row_order ASC, id ASC");
    if ($lineRes && $lineRes->num_rows > 0) {
        while ($row = $lineRes->fetch_assoc()) {
            $lines[] = $row;
        }
    }
    $order['lines'] = $lines;
    return $order;
}

function so_save_lines($conn, $orderId, $lines) {
    $orderId = intval($orderId);
    $conn->query("DELETE FROM qc_shipping_order_line WHERE shipping_order_id = '".$orderId."'");
    if (!is_array($lines)) {
        return 0;
    }
    $rowOrder = 0;
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $labSample = trim($line['lab_sample_no'] ?? '');
        $qty = trim($line['quantity'] ?? '');
        $lot = trim($line['lot_batch_no'] ?? '');
        $desc = trim($line['description'] ?? '');
        if ($labSample === '' && $qty === '' && $lot === '' && $desc === '') {
            continue;
        }
        $rowOrder++;
        $sql = "INSERT INTO qc_shipping_order_line
                (shipping_order_id, row_order, lab_sample_no, quantity, lot_batch_no, description)
                VALUES (
                    '".$orderId."',
                    '".$rowOrder."',
                    '".so_esc($conn, $labSample)."',
                    '".so_esc($conn, $qty)."',
                    '".so_esc($conn, $lot)."',
                    '".so_esc($conn, $desc)."'
                )";
        if (!$conn->query($sql)) {
            return false;
        }
    }
    return $rowOrder;
}

function so_format_address($parts) {
    $lines = array();
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '') {
            $lines[] = $part;
        }
    }
    return implode("\n", $lines);
}

function so_client_display_name($row) {
    $trade = trim($row['TrdNm'] ?? '');
    $legal = trim($row['LglNm'] ?? '');
    return $trade !== '' ? $trade : $legal;
}

function so_build_data($conn, $input) {
    return array(
        'ship_to' => $input['ship_to'] ?? '',
        'ship_to_client_code' => $input['ship_to_client_code'] ?? '',
        'po_number' => $input['po_number'] ?? '',
        'ship_to_address' => $input['ship_to_address'] ?? '',
        'from_tel' => $input['from_tel'] ?? '',
        'from_fax' => $input['from_fax'] ?? '',
        'tests_required' => $input['tests_required'] ?? '',
        'quotation_ref' => $input['quotation_ref'] ?? '',
        'shipping_vendor' => $input['shipping_vendor'] ?? '',
        'shipping_vendor_no' => $input['shipping_vendor_no'] ?? '',
    );
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$action = isset($_GET['type']) ? $_GET['type'] : '';

$sql = "SELECT * FROM token WHERE token='".$conn->real_escape_string($token)."'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    ensure_shipping_order_schema($conn);
    $plantId = so_esc($conn, $_GET['plant_id'] ?? '');
    $empId = so_esc($conn, $_GET['emp_id']);
    $entry_date = date('Y-m-d H:i:s');

    if ($action == 'getNextOrderNo') {
        so_json_out(array('order_no' => so_next_order_no($conn, $_GET['plant_id'] ?? '')));
    }
    else if ($action == 'getClients') {
        $output = array();
        $sql = "SELECT client_code, LglNm, TrdNm, address, billingAddress, city, state, country, pincode, mobNo, email
                FROM client
                WHERE status = 'Active'
                ORDER BY LglNm ASC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $addr = trim($row['address'] ?? '');
                if ($addr === '') {
                    $addr = trim($row['billingAddress'] ?? '');
                }
                $output[] = array(
                    'client_code' => $row['client_code'] ?? '',
                    'display_name' => so_client_display_name($row),
                    'LglNm' => $row['LglNm'] ?? '',
                    'TrdNm' => $row['TrdNm'] ?? '',
                    'address' => $addr,
                    'formatted_address' => so_format_address(array(
                        $addr,
                        trim(($row['city'] ?? '').($row['pincode'] ? ' - '.$row['pincode'] : '')),
                        trim($row['state'] ?? ''),
                        trim($row['country'] ?? '')
                    )),
                    'mobNo' => $row['mobNo'] ?? '',
                    'email' => $row['email'] ?? '',
                );
            }
        }
        so_json_out($output);
    }
    else if ($action == 'getVendors') {
        $output = array();
        $sql = "SELECT vendor_no, vendor_name, address, city, permanent_state, permanent_state as state_name, country, pincode, contact_number, contact_person FROM vendor
                
                WHERE status = 'Approved' AND plant_id = '".$plantId."'
                ORDER BY vendor_name ASC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $state = trim($row['permanent_state'] ?? '');
                if ($state === '') {
                    $state = trim($row['state_name'] ?? '');
                }
                $output[] = array(
                    'vendor_no' => $row['vendor_no'] ?? '',
                    'vendor_name' => $row['vendor_name'] ?? '',
                    'address' => trim($row['address'] ?? ''),
                    'formatted_address' => so_format_address(array(
                        trim($row['address'] ?? ''),
                        trim(($row['city'] ?? '').($row['pincode'] ? ' - '.$row['pincode'] : '')),
                        $state,
                        trim($row['country'] ?? '')
                    )),
                    'contact_number' => $row['contact_number'] ?? '',
                    'contact_person' => $row['contact_person'] ?? '',
                );
            }
        }
        so_json_out($output);
    }
    else if ($action == 'saveOrder' || $action == 'submitOrder') {
        if (count($input) === 0) {
            so_json_out(array('status' => 'invalid', 'message' => 'Missing request body'));
        }
        $id = intval($input['id'] ?? 0);
        $data = so_build_data($conn, $input);
        $lines = isset($input['lines']) ? $input['lines'] : array();

        if ($id > 0) {
            $existing = so_fetch_order($conn, $id, $_GET['plant_id'] ?? '');
            if (!$existing || $existing['status'] !== 'draft') {
                so_json_out(array('status' => 'invalid', 'message' => 'Only draft orders can be updated'));
            }
            $sets = array();
            foreach ($data as $k => $v) {
                $sets[] = "`".$k."` = '".so_esc($conn, $v)."'";
            }
            $sql = "UPDATE qc_shipping_order SET ".implode(', ', $sets)." WHERE id = '".$id."' AND plant_id = '".$plantId."'";
            if (!$conn->query($sql)) {
                so_json_out(array('status' => 'error', 'message' => $conn->error));
            }
            $lineCount = so_save_lines($conn, $id, $lines);
            if ($lineCount === false) {
                so_json_out(array('status' => 'error', 'message' => $conn->error));
            }
            if ($lineCount === 0) {
                so_json_out(array('status' => 'invalid', 'message' => 'Add at least one sample line'));
            }
            if ($action == 'submitOrder') {
                $name = so_emp_name($conn, $_GET['emp_id']);
                $conn->query("UPDATE qc_shipping_order SET
                    status = 'pending_check',
                    packaged_by = '".so_esc($conn, $name)."',
                    packaged_by_emp = '".$empId."',
                    packaged_date = '".date('Y-m-d')."'
                    WHERE id = '".$id."' AND plant_id = '".$plantId."'");
            }
            so_json_out(array('status' => 'success', 'id' => $id));
        }

        $orderNo = trim($input['order_no'] ?? '');
        if ($orderNo === '') {
            $orderNo = so_next_order_no($conn, $_GET['plant_id'] ?? '');
        }
        $status = ($action == 'submitOrder') ? 'pending_check' : 'draft';
        $name = so_emp_name($conn, $_GET['emp_id']);
        $extraCols = '';
        $extraVals = '';
        if ($action == 'submitOrder') {
            $extraCols = ', packaged_by, packaged_by_emp, packaged_date';
            $extraVals = ", '".so_esc($conn, $name)."', '".$empId."', '".date('Y-m-d')."'";
        }
        $sql = "INSERT INTO qc_shipping_order
                (plant_id, order_no, status, entry_by, entry_date,
                 ship_to, ship_to_client_code, po_number, ship_to_address, from_tel, from_fax,
                 tests_required, quotation_ref, shipping_vendor, shipping_vendor_no".$extraCols.")
                VALUES (
                    '".$plantId."',
                    '".so_esc($conn, $orderNo)."',
                    '".so_esc($conn, $status)."',
                    '".$empId."',
                    '".$entry_date."',
                    '".so_esc($conn, $data['ship_to'])."',
                    '".so_esc($conn, $data['ship_to_client_code'])."',
                    '".so_esc($conn, $data['po_number'])."',
                    '".so_esc($conn, $data['ship_to_address'])."',
                    '".so_esc($conn, $data['from_tel'])."',
                    '".so_esc($conn, $data['from_fax'])."',
                    '".so_esc($conn, $data['tests_required'])."',
                    '".so_esc($conn, $data['quotation_ref'])."',
                    '".so_esc($conn, $data['shipping_vendor'])."',
                    '".so_esc($conn, $data['shipping_vendor_no'])."'
                    ".$extraVals."
                )";
        if (!$conn->query($sql)) {
            so_json_out(array('status' => 'error', 'message' => $conn->error));
        }
        $newId = $conn->insert_id;
        $lineCount = so_save_lines($conn, $newId, $lines);
        if ($lineCount === false) {
            $conn->query("DELETE FROM qc_shipping_order WHERE id = '".$newId."'");
            so_json_out(array('status' => 'error', 'message' => $conn->error));
        }
        if ($lineCount === 0) {
            $conn->query("DELETE FROM qc_shipping_order WHERE id = '".$newId."'");
            so_json_out(array('status' => 'invalid', 'message' => 'Add at least one sample line'));
        }
        so_json_out(array('status' => 'success', 'id' => $newId, 'order_no' => $orderNo));
    }
    else if ($action == 'getOrders') {
        $from = so_esc($conn, $_GET['from_date'] ?? '');
        $to = so_esc($conn, $_GET['to_date'] ?? '');
        $status = so_esc($conn, $_GET['status'] ?? '');
        $where = "o.plant_id = '".$plantId."'";
        if ($from !== '') {
            $where .= " AND DATE(o.entry_date) >= '".$from."'";
        }
        if ($to !== '') {
            $where .= " AND DATE(o.entry_date) <= '".$to."'";
        }
        if ($status !== '' && $status !== 'all') {
            $where .= " AND o.status = '".$status."'";
        }
        $sql = "SELECT o.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = o.entry_by LIMIT 1) AS entry_by_name,
                (SELECT COUNT(*) FROM qc_shipping_order_line l WHERE l.shipping_order_id = o.id) AS line_count
                FROM qc_shipping_order o
                WHERE ".$where."
                ORDER BY o.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        so_json_out($output);
    }
    else if ($action == 'getOrderById') {
        $id = intval($_GET['id'] ?? 0);
        $order = so_fetch_order($conn, $id, $_GET['plant_id'] ?? '');
        if (!$order) {
            so_json_out(array('status' => 'invalid', 'message' => 'Not found'));
        }
        so_json_out(array('status' => 'success', 'order' => $order));
    }
    else if ($action == 'checkOrder') {
        if (empty($input['id'])) {
            so_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = so_fetch_order($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_check') {
            so_json_out(array('status' => 'invalid', 'message' => 'Not pending check'));
        }
        $name = so_emp_name($conn, $_GET['emp_id']);
        $sql = "UPDATE qc_shipping_order SET
                status = 'checked',
                checked_by = '".so_esc($conn, $name)."',
                checked_by_emp = '".$empId."',
                checked_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            so_json_out(array('status' => 'success'));
        }
        so_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'markShipped') {
        if (empty($input['id'])) {
            so_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = so_fetch_order($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'checked') {
            so_json_out(array('status' => 'invalid', 'message' => 'Order must be checked before shipping'));
        }
        $name = so_emp_name($conn, $_GET['emp_id']);
        $vendor = so_esc($conn, $input['shipping_vendor'] ?? $existing['shipping_vendor'] ?? '');
        $sql = "UPDATE qc_shipping_order SET
                status = 'shipped',
                shipping_vendor = '".$vendor."',
                shipped_by = '".so_esc($conn, $name)."',
                shipped_by_emp = '".$empId."',
                shipped_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            so_json_out(array('status' => 'success'));
        }
        so_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'markResultsReceived') {
        if (empty($input['id'])) {
            so_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = so_fetch_order($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'shipped') {
            so_json_out(array('status' => 'invalid', 'message' => 'Order must be shipped first'));
        }
        $sql = "UPDATE qc_shipping_order SET status = 'pending_results_review' WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            so_json_out(array('status' => 'success'));
        }
        so_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else if ($action == 'reviewResults') {
        if (empty($input['id'])) {
            so_json_out(array('status' => 'invalid', 'message' => 'Missing id'));
        }
        $id = intval($input['id']);
        $existing = so_fetch_order($conn, $id, $_GET['plant_id'] ?? '');
        if (!$existing || $existing['status'] !== 'pending_results_review') {
            so_json_out(array('status' => 'invalid', 'message' => 'Not pending QC results review'));
        }
        $name = so_emp_name($conn, $_GET['emp_id']);
        $notes = so_esc($conn, $input['results_notes'] ?? '');
        $sql = "UPDATE qc_shipping_order SET
                status = 'closed',
                results_notes = '".$notes."',
                results_reviewed_by = '".so_esc($conn, $name)."',
                results_reviewed_by_emp = '".$empId."',
                results_reviewed_date = '".date('Y-m-d')."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            so_json_out(array('status' => 'success'));
        }
        so_json_out(array('status' => 'error', 'message' => $conn->error));
    }
    else {
        so_json_out(array('status' => 'invalid', 'message' => 'Unknown action'));
    }
} else {
    so_json_out(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
