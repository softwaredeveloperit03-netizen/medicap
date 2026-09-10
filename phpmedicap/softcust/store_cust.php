<?php
/**
 * Store Receiving Form Customisation API
 * Upload path: phpCyclone/php/phpDevelopCyclone/softcust/store_cust.php
 * Uses db.php from parent directory. Same pattern as rm_master_customisation.php.
 *
 * Types: getActiveCustomisation | getLog | getById | getPendingApprovals | saveRequest | approve | reject
 */

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

$fieldColumns = array(
    'material_type', 'search', 'datagrid_sr_no', 'datagrid_material_type', 'datagrid_inward_date',
    'datagrid_po_no_date', 'datagrid_challan_no_date', 'datagrid_vendor_name', 'datagrid_material_name',
    'datagrid_material_code', 'datagrid_action', 'detail_material_name', 'detail_material_code',
    'detail_vendor_no', 'detail_vendor_name', 'detail_challan_no', 'detail_challan_date', 'detail_po_no',
    'detail_material_type', 'detail_material_subtype', 'detail_qty', 'labeling_batch_no', 'labeling_batch_qty',
    'labeling_pack_size', 'labeling_unit', 'labeling_no_containers', 'labeling_mfg_date', 'labeling_exp_date',
    'labeling_coa_received', 'labeling_upload_coa', 'container_type', 'container_subtype', 'qty_as_per_challan',
    'actual_received_qty', 'no_of_containers', 'short_extra_qty', 'po_status', 'damage_container_observed',
    'damage_batch_no', 'total_damage_containers', 'hold_qty', 'dedusting_applicable', 'receiving_start_time',
    'receiving_end_time', 'receiving_checklist'
);

function rowToRecord($row, $fieldColumns) {
    $out = array(
        'id' => (int)$row['id'],
        'request_no' => isset($row['request_no']) ? $row['request_no'] : null,
        'revision_number' => isset($row['revision_number']) ? $row['revision_number'] : null,
        'entry_by' => isset($row['entry_by']) ? $row['entry_by'] : null,
        'entry_date' => isset($row['entry_date']) ? $row['entry_date'] : null,
        'approval_by' => isset($row['approval_by']) ? $row['approval_by'] : null,
        'approval_date' => isset($row['approval_date']) ? $row['approval_date'] : null,
        'status' => isset($row['status']) ? $row['status'] : null
    );
    foreach ($fieldColumns as $col) {
        if (array_key_exists($col, $row)) {
            $out[$col] = $row[$col];
        }
    }
    return $out;
}

function generateRequestNo($conn) {
    $sql = "SELECT id FROM store_receiving_customisation_log ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    $nextId = 1;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nextId = (int)$row['id'] + 1;
    }
    return 'SRC' . date('Y') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

function generateRevisionNo($conn) {
    $sql = "SELECT revision_number FROM store_receiving_customisation_log WHERE revision_number IS NOT NULL AND revision_number != '' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    $nextNum = 1;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rev = $row['revision_number'];
        if (preg_match('/Cust\/Rev\/(\d+)$/i', $rev, $m)) {
            $nextNum = (int)$m[1] + 1;
        }
    }
    return 'Cust/Rev/' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

switch ($type) {

    case 'getActiveCustomisation':
        $sql = "SELECT * FROM store_receiving_customisation_active ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row = array_map('utf8_encode', $row);
            echo json_encode(utf8ize(rowToRecord($row, $fieldColumns)));
        } else {
            echo json_encode(null);
        }
        break;

    case 'getLog':
        $sql = "SELECT id, request_no, revision_number, entry_by, entry_date, approval_by, approval_date, status, " .
            implode(', ', $fieldColumns) . " FROM store_receiving_customisation_log ORDER BY id DESC";
        $result = $conn->query($sql);
        $list = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $list[] = utf8ize(rowToRecord($row, $fieldColumns));
            }
        }
        echo json_encode($list);
        break;

    case 'getById':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(null);
            break;
        }
        $sql = "SELECT id, request_no, revision_number, entry_by, entry_date, approval_by, approval_date, status, " .
            implode(', ', $fieldColumns) . " FROM store_receiving_customisation_log WHERE id = '" . (int)$id . "'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row = array_map('utf8_encode', $row);
            echo json_encode(utf8ize(rowToRecord($row, $fieldColumns)));
        } else {
            echo json_encode(null);
        }
        break;

    case 'getPendingApprovals':
        $sql = "SELECT id, request_no, revision_number, entry_by, entry_date, status, " .
            implode(', ', $fieldColumns) . " FROM store_receiving_customisation_log WHERE status = 'Pending' ORDER BY id ASC";
        $result = $conn->query($sql);
        $list = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $list[] = utf8ize(rowToRecord($row, $fieldColumns));
            }
        }
        echo json_encode($list);
        break;

    case 'saveRequest':
        $entry_by = isset($input['entry_by']) ? $conn->real_escape_string($input['entry_by']) : '';
        $request_no = generateRequestNo($conn);
        $request_no_esc = $conn->real_escape_string($request_no);
        $revision_number = generateRevisionNo($conn);
        $revision_number_esc = $conn->real_escape_string($revision_number);

        $cols = "request_no, revision_number, entry_by, entry_date, status";
        $vals = "'" . $request_no_esc . "', '" . $revision_number_esc . "', '" . $entry_by . "', NOW(), 'Pending'";

        foreach ($fieldColumns as $col) {
            $v = 'Applicable';
            if (isset($input[$col]) && ($input[$col] === 'Applicable' || $input[$col] === 'Not Applicable')) {
                $v = $input[$col];
            }
            $cols .= ", " . $col;
            $vals .= ", '" . $conn->real_escape_string($v) . "'";
        }

        $sql = "INSERT INTO store_receiving_customisation_log (" . $cols . ") VALUES (" . $vals . ")";
        if ($conn->query($sql)) {
            $id = (int)$conn->insert_id;
            echo json_encode(array('status' => 'success', 'message' => 'Request saved. Sent for approval.', 'id' => $id, 'request_no' => $request_no, 'revision_number' => $revision_number));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        }
        break;

    case 'approve':
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid id'));
            break;
        }
        $approval_by = isset($input['approval_by']) && $input['approval_by'] !== ''
            ? $conn->real_escape_string($input['approval_by']) : 'Admin';

        $sql = "UPDATE store_receiving_customisation_log SET status = 'Approved', approval_by = '" . $approval_by . "', approval_date = NOW() WHERE id = '" . (int)$id . "'";
        $conn->query($sql);

        $sql = "SELECT * FROM store_receiving_customisation_log WHERE id = '" . (int)$id . "'";
        $result = $conn->query($sql);
        $row = null;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
        }
        if (!$row) {
            echo json_encode(array('status' => 'error', 'message' => 'Record not found'));
            break;
        }

        $conn->query("DELETE FROM store_receiving_customisation_active");

        $reqNo = $conn->real_escape_string($row['request_no']);
        $logId = (int)$row['id'];
        $vals = "'" . $logId . "', '" . $reqNo . "'";
        foreach ($fieldColumns as $col) {
            $v = isset($row[$col]) ? $conn->real_escape_string($row[$col]) : 'Applicable';
            $vals .= ", '" . $v . "'";
        }
        $sql = "INSERT INTO store_receiving_customisation_active (log_id, request_no, " . implode(', ', $fieldColumns) . ") VALUES (" . $vals . ")";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success', 'message' => 'Approved. Customisation implemented for receiving form.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        }
        break;

    case 'reject':
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid id'));
            break;
        }
        $sql = "UPDATE store_receiving_customisation_log SET status = 'Rejected' WHERE id = '" . (int)$id . "'";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success', 'message' => 'Rejected.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        }
        break;

    default:
        echo json_encode(array('status' => 'error', 'message' => 'Unknown type'));
}
