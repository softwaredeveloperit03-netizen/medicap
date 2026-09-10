<?php
/**
 * GMP Material Master — dynamic form layout (labels, field type, order, applicable).
 * Path: softcust/gmp_customisation_form.php
 *
 * type=getDefaults | getActiveLayout | getLog | getById | getPendingApprovals |
 *      saveRequest | approve | reject
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

$DEFAULT_FORM_CODE = 'MATERIAL_MASTER';
$ALLOWED_FORM_CODES = array(
    'MATERIAL_MASTER',
    'SPECIFICATION_RAW_MATERIAL',
    'SPECIFICATION_PACKING_MATERIAL',
    'SPECIFICATION_FINISH_PRODUCT',
    'RECEIVING_AWAITING_FORM'
);

$ALLOWED_LAYOUT = array('core', 'full', 'grid', 'other', 'others', 'version', 'general', 'sampling', 'tests', 'revision', 'labeling', 'receiving', 'container', 'checklist');
$ALLOWED_TYPE = array('STANDARD', 'TEXT', 'CHECKBOX', 'DROPDOWN', 'YESNO');
$ALLOWED_APPLICABLE = array('Applicable', 'Not Applicable');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = array();
}

$colCheck = $conn->query("SHOW COLUMNS FROM gmp_customisation_form_field LIKE 'field_options'");
if (!($colCheck && $colCheck->num_rows > 0)) {
    $conn->query("ALTER TABLE gmp_customisation_form_field ADD COLUMN field_options TEXT NULL AFTER field_type");
}
$colCheck1 = $conn->query("SHOW COLUMNS FROM gmp_customisation_form_field LIKE 'visible_when_key'");
if (!($colCheck1 && $colCheck1->num_rows > 0)) {
    $conn->query("ALTER TABLE gmp_customisation_form_field ADD COLUMN visible_when_key VARCHAR(128) NULL AFTER field_options");
}
$colCheck2 = $conn->query("SHOW COLUMNS FROM gmp_customisation_form_field LIKE 'visible_when_value'");
if (!($colCheck2 && $colCheck2->num_rows > 0)) {
    $conn->query("ALTER TABLE gmp_customisation_form_field ADD COLUMN visible_when_value VARCHAR(255) NULL AFTER visible_when_key");
}
$colCheck3 = $conn->query("SHOW COLUMNS FROM gmp_customisation_form_field LIKE 'default_value'");
if (!($colCheck3 && $colCheck3->num_rows > 0)) {
    $conn->query("ALTER TABLE gmp_customisation_form_field ADD COLUMN default_value VARCHAR(255) NULL AFTER visible_when_value");
}

function generateRequestNo($conn, $prefix) {
    $sql = "SELECT id FROM gmp_customisation_form_log ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    $nextId = 1;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nextId = (int)$row['id'] + 1;
    }
    return $prefix . date('Y') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

function resolveFormCode($input, $default, $allowed) {
    $fc = '';
    if (isset($_GET['form_code'])) {
        $fc = strtoupper(trim($_GET['form_code']));
    }
    if ($fc === '' && isset($input['form_code'])) {
        $fc = strtoupper(trim($input['form_code']));
    }
    if ($fc === '' || !in_array($fc, $allowed, true)) {
        return $default;
    }
    return $fc;
}

function fetchFieldsForLog($conn, $logId) {
    $logId = (int)$logId;
    $sql = "SELECT field_key, field_label, layout_group, field_type, field_options, visible_when_key, visible_when_value, default_value, sort_order, applicable
            FROM gmp_customisation_form_field WHERE log_id = '" . $logId . "'
            ORDER BY layout_group, sort_order ASC, id ASC";
    $result = $conn->query($sql);
    $rows = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row = array_map('utf8_encode', $row);
            $rows[] = utf8ize($row);
        }
    }
    return $rows;
}

function logRowMeta($row) {
    return array(
        'id' => (int)$row['id'],
        'request_no' => isset($row['request_no']) ? $row['request_no'] : null,
        'revision_number' => isset($row['revision_number']) ? $row['revision_number'] : null,
        'entry_by' => isset($row['entry_by']) ? $row['entry_by'] : null,
        'entry_date' => isset($row['entry_date']) ? $row['entry_date'] : null,
        'approval_by' => isset($row['approval_by']) ? $row['approval_by'] : null,
        'approval_date' => isset($row['approval_date']) ? $row['approval_date'] : null,
        'status' => isset($row['status']) ? $row['status'] : null,
        'form_code' => isset($row['form_code']) ? $row['form_code'] : null,
    );
}

switch ($type) {

    case 'getDefaults':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        echo json_encode(array('form_code' => $FORM_CODE, 'fields' => array()));
        break;

    case 'getActiveLayout':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $sql = "SELECT log_id FROM gmp_customisation_form_active WHERE form_code = '" .
            $conn->real_escape_string($FORM_CODE) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $logId = (int)$row['log_id'];
            $fields = fetchFieldsForLog($conn, $logId);
            echo json_encode(utf8ize(array('form_code' => $FORM_CODE, 'log_id' => $logId, 'fields' => $fields)));
        } else {
            echo json_encode(null);
        }
        break;

    case 'getLog':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $sql = "SELECT id, form_code, request_no, revision_number, entry_by, entry_date, approval_by, approval_date, status
                FROM gmp_customisation_form_log WHERE form_code = '" .
            $conn->real_escape_string($FORM_CODE) . "' ORDER BY id DESC";
        $result = $conn->query($sql);
        $list = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $meta = utf8ize(logRowMeta($row));
                $meta['fields'] = fetchFieldsForLog($conn, (int)$row['id']);
                $list[] = $meta;
            }
        }
        echo json_encode($list);
        break;

    case 'getById':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(null);
            break;
        }
        $sql = "SELECT * FROM gmp_customisation_form_log WHERE id = '" . $id . "' AND form_code = '" .
            $conn->real_escape_string($FORM_CODE) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row = array_map('utf8_encode', $row);
            $meta = utf8ize(logRowMeta($row));
            $meta['fields'] = fetchFieldsForLog($conn, $id);
            echo json_encode($meta);
        } else {
            echo json_encode(null);
        }
        break;

    case 'getPendingApprovals':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $sql = "SELECT id, form_code, request_no, revision_number, entry_by, entry_date, status
                FROM gmp_customisation_form_log
                WHERE form_code = '" . $conn->real_escape_string($FORM_CODE) . "' AND status = 'Pending'
                ORDER BY id ASC";
        $result = $conn->query($sql);
        $list = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $list[] = utf8ize(logRowMeta($row));
            }
        }
        echo json_encode($list);
        break;

    case 'saveRequest':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $entry_by = isset($input['entry_by']) ? $conn->real_escape_string($input['entry_by']) : '';
        $fieldsIn = isset($input['fields']) && is_array($input['fields']) ? $input['fields'] : array();
        if (count($fieldsIn) === 0) {
            echo json_encode(array('status' => 'error', 'message' => 'No fields payload'));
            break;
        }

        $prefix = 'GMF';
        if ($FORM_CODE === 'SPECIFICATION_RAW_MATERIAL') {
            $prefix = 'SFR';
        } else if ($FORM_CODE === 'SPECIFICATION_PACKING_MATERIAL') {
            $prefix = 'SFP';
        } else if ($FORM_CODE === 'SPECIFICATION_FINISH_PRODUCT') {
            $prefix = 'SFF';
        } else if ($FORM_CODE === 'RECEIVING_AWAITING_FORM') {
            $prefix = 'RFC';
        }
        $request_no = generateRequestNo($conn, $prefix);
        $req_esc = $conn->real_escape_string($request_no);
        $rev = 'REV-' . date('Y') . '-' . substr($request_no, -5);

        $sqlLog = "INSERT INTO gmp_customisation_form_log (form_code, request_no, revision_number, entry_by, entry_date, status)
                   VALUES ('" . $conn->real_escape_string($FORM_CODE) . "', '" . $req_esc . "', '" .
                   $conn->real_escape_string($rev) . "', '" . $entry_by . "', NOW(), 'Pending')";
        if (!$conn->query($sqlLog)) {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
            break;
        }
        $logId = (int)$conn->insert_id;

        foreach ($fieldsIn as $f) {
            if (!is_array($f)) {
                continue;
            }
            $key = isset($f['field_key']) ? trim($f['field_key']) : '';
            if ($key === '' || !preg_match('/^[a-z][a-z0-9_]{1,127}$/', $key)) {
                continue;
            }
            $label = isset($f['field_label']) ? $f['field_label'] : $key;
            $lg = isset($f['layout_group']) ? strtolower(trim($f['layout_group'])) : 'grid';
            if (!in_array($lg, $ALLOWED_LAYOUT, true)) {
                $lg = 'grid';
            }
            $ft = isset($f['field_type']) ? strtoupper(trim($f['field_type'])) : 'STANDARD';
            if (!in_array($ft, $ALLOWED_TYPE, true)) {
                $ft = 'STANDARD';
            }
            $fo = isset($f['field_options']) ? trim((string)$f['field_options']) : '';
            $vwk = isset($f['visible_when_key']) ? trim((string)$f['visible_when_key']) : '';
            $vwv = isset($f['visible_when_value']) ? trim((string)$f['visible_when_value']) : '';
            $dv = isset($f['default_value']) ? trim((string)$f['default_value']) : '';
            if ($vwk !== '' && !preg_match('/^[a-z][a-z0-9_]{1,127}$/', $vwk)) {
                $vwk = '';
                $vwv = '';
            }
            $so = isset($f['sort_order']) ? (int)$f['sort_order'] : 0;
            $app = isset($f['applicable']) ? $f['applicable'] : 'Applicable';
            if (!in_array($app, $ALLOWED_APPLICABLE, true)) {
                $app = 'Applicable';
            }

            $sqlF = "INSERT INTO gmp_customisation_form_field
                (log_id, field_key, field_label, layout_group, field_type, field_options, visible_when_key, visible_when_value, default_value, sort_order, applicable) VALUES (" .
                $logId . ", '" . $conn->real_escape_string($key) . "', '" .
                $conn->real_escape_string($label) . "', '" . $conn->real_escape_string($lg) . "', '" .
                $conn->real_escape_string($ft) . "', '" . $conn->real_escape_string($fo) . "', '" .
                $conn->real_escape_string($vwk) . "', '" . $conn->real_escape_string($vwv) . "', '" . $conn->real_escape_string($dv) . "', " . $so . ", '" . $conn->real_escape_string($app) . "')";
            if (!$conn->query($sqlF)) {
                echo json_encode(array('status' => 'error', 'message' => $conn->error));
                break 2;
            }
        }

        echo json_encode(array(
            'status' => 'success',
            'message' => 'Form layout saved. Sent for approval.',
            'id' => $logId,
            'request_no' => $request_no,
            'revision_number' => $rev,
        ));
        break;

    case 'approve':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid id'));
            break;
        }
        $approval_by = isset($input['approval_by']) && $input['approval_by'] !== ''
            ? $conn->real_escape_string($input['approval_by']) : 'Admin';

        $sql = "UPDATE gmp_customisation_form_log SET status = 'Approved', approval_by = '" . $approval_by .
            "', approval_date = NOW() WHERE id = '" . $id . "' AND status = 'Pending' AND form_code = '" .
            $conn->real_escape_string($FORM_CODE) . "'";
        $conn->query($sql);
        if ($conn->affected_rows === 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Record not pending or not found'));
            break;
        }

        $conn->query("DELETE FROM gmp_customisation_form_active WHERE form_code = '" .
            $conn->real_escape_string($FORM_CODE) . "'");
        $sqlA = "INSERT INTO gmp_customisation_form_active (form_code, log_id, implemented_at) VALUES ('" .
            $conn->real_escape_string($FORM_CODE) . "', '" . $id . "', NOW())";
        if ($conn->query($sqlA)) {
            echo json_encode(array('status' => 'success', 'message' => 'Approved. Layout is active on Material Master (new).'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error));
        }
        break;

    case 'reject':
        $FORM_CODE = resolveFormCode($input, $DEFAULT_FORM_CODE, $ALLOWED_FORM_CODES);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid id'));
            break;
        }
        $sql = "UPDATE gmp_customisation_form_log SET status = 'Rejected' WHERE id = '" . $id . "' AND status = 'Pending' AND form_code = '" .
            $conn->real_escape_string($FORM_CODE) . "'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(array('status' => 'success', 'message' => 'Rejected.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Update failed'));
        }
        break;

    default:
        echo json_encode(array('status' => 'error', 'message' => 'Unknown type'));
}
