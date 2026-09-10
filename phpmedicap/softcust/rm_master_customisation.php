<?php
/**
 * RM Master Customisation API
 * Upload path: phpCyclone/php/phpDevelopCyclone/softcust/rm_master_customisation.php
 * Uses db.php from parent directory for $conn (plant_id from GET selects database).
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
    'material_group_type', 'sub_group_type', 'nature_of_material', 'category', 'in_active',
    'material_name', 'grade', 'item_unit', 'billing_unit', 'hsn', 'tax_type', 'retest_month',
    'other_information', 'storage_condition', 'min_inventory_level', 'max_inventory_level',
    'specific_gravity', 'moq', 'plastic_type', 'inventory_value_max', 'premix_item',
    'qc_lead_time_days', 'description', 'safety'
);

function rowToRecord($row, $fieldColumns) {
    $out = array(
        'id' => (int)$row['id'],
        'request_no' => isset($row['request_no']) ? $row['request_no'] : null,
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
    $sql = "SELECT id FROM rm_master_customisation_log ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    $nextId = 1;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nextId = (int)$row['id'] + 1;
    }
    return 'RMC' . date('Y') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

switch ($type) {

    case 'getActiveCustomisation':
        $sql = "SELECT * FROM rm_master_customisation_active ORDER BY id DESC LIMIT 1";
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
        $sql = "SELECT id, request_no, entry_by, entry_date, approval_by, approval_date, status, " .
            implode(', ', $fieldColumns) . " FROM rm_master_customisation_log ORDER BY id DESC";
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
        $sql = "SELECT id, request_no, entry_by, entry_date, approval_by, approval_date, status, " .
            implode(', ', $fieldColumns) . " FROM rm_master_customisation_log WHERE id = '" . (int)$id . "'";
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
        $sql = "SELECT id, request_no, entry_by, entry_date, status, " .
            implode(', ', $fieldColumns) . " FROM rm_master_customisation_log WHERE status = 'Pending' ORDER BY id ASC";
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

        $cols = "request_no, entry_by, entry_date, status";
        $vals = "'" . $request_no_esc . "', '" . $entry_by . "', NOW(), 'Pending'";

        foreach ($fieldColumns as $col) {
            $v = 'Applicable';
            if (isset($input[$col]) && ($input[$col] === 'Applicable' || $input[$col] === 'Not Applicable')) {
                $v = $input[$col];
            }
            $cols .= ", " . $col;
            $vals .= ", '" . $conn->real_escape_string($v) . "'";
        }

        $sql = "INSERT INTO rm_master_customisation_log (" . $cols . ") VALUES (" . $vals . ")";
        if ($conn->query($sql)) {
            $id = (int)$conn->insert_id;
            echo json_encode(array('status' => 'success', 'message' => 'Request saved. Sent for approval.', 'id' => $id, 'request_no' => $request_no));
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

        $sql = "UPDATE rm_master_customisation_log SET status = 'Approved', approval_by = '" . $approval_by . "', approval_date = NOW() WHERE id = '" . (int)$id . "'";
        $conn->query($sql);

        $sql = "SELECT * FROM rm_master_customisation_log WHERE id = '" . (int)$id . "'";
        $result = $conn->query($sql);
        $row = null;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
        }
        if (!$row) {
            echo json_encode(array('status' => 'error', 'message' => 'Record not found'));
            break;
        }

        $conn->query("DELETE FROM rm_master_customisation_active");

        $reqNo = $conn->real_escape_string($row['request_no']);
        $logId = (int)$row['id'];
        $vals = "'" . $logId . "', '" . $reqNo . "'";
        foreach ($fieldColumns as $col) {
            $v = isset($row[$col]) ? $conn->real_escape_string($row[$col]) : 'Applicable';
            $vals .= ", '" . $v . "'";
        }
        $sql = "INSERT INTO rm_master_customisation_active (log_id, request_no, " . implode(', ', $fieldColumns) . ") VALUES (" . $vals . ")";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success', 'message' => 'Approved. Customisation is now implemented in QC module.'));
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
        $sql = "UPDATE rm_master_customisation_log SET status = 'Rejected' WHERE id = '" . (int)$id . "'";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success', 'message' => 'Rejected.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        }
        break;

    default:
        echo json_encode(array('status' => 'error', 'message' => 'Unknown type'));
}
