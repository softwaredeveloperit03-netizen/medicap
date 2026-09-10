<?php
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
require '../../db.php';
require '../../token.php';

mysqli_report(MYSQLI_REPORT_OFF);

// db.php already reads php://input once — do not read again
if (!is_array($input)) {
    $input = array();
}

$output = array();
$token = isset($_GET['token']) ? $_GET['token'] : '';
$action = isset($_GET['type']) ? $_GET['type'] : '';

function sr_table_columns($conn, $table, $forceRefresh = false) {
    static $cache = array();
    if ($forceRefresh) {
        $cache = array();
    }
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $cols = array();
    $tableEsc = $conn->real_escape_string($table);
    $res = @$conn->query("SHOW COLUMNS FROM `".$tableEsc."`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }
    $cache[$table] = $cols;
    return $cols;
}

function sr_run_query($conn, $sql) {
    try {
        return $conn->query($sql);
    } catch (Exception $e) {
        return false;
    }
}

function ensure_sampling_room_schema($conn) {
    sr_run_query($conn, "CREATE TABLE IF NOT EXISTS `sampling_room_log_sheet` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `sheet_no` varchar(50) DEFAULT NULL,
        `log_date` date DEFAULT NULL,
        `status` varchar(30) DEFAULT 'pending',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        `verified_by` varchar(50) DEFAULT NULL,
        `verified_on` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    sr_run_query($conn, "CREATE TABLE IF NOT EXISTS `sampling_room_log_entry` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sheet_id` int(11) NOT NULL,
        `sampling_id` int(11) DEFAULT NULL,
        `material_code` varchar(50) DEFAULT NULL,
        `material_description` text DEFAULT NULL,
        `material_lot_no` varchar(100) DEFAULT NULL,
        `cleaning_full` varchar(5) DEFAULT 'No',
        `cleaning_partial` varchar(5) DEFAULT 'No',
        `cleaning_agent_id` int(11) DEFAULT NULL,
        `cleaning_agent_name` varchar(255) DEFAULT NULL,
        `cleaning_agent_lot` varchar(100) DEFAULT NULL,
        `cleaning_agent_expiry` date DEFAULT NULL,
        `balance_cleaned` varchar(5) DEFAULT 'No',
        `balance_verified` varchar(5) DEFAULT 'No',
        `magnehelic_reading_1` varchar(50) DEFAULT NULL,
        `magnehelic_reading_2` varchar(50) DEFAULT NULL,
        `magnehelic_reading_3` varchar(50) DEFAULT NULL,
        `performed_by` varchar(100) DEFAULT NULL,
        `verified_by` varchar(100) DEFAULT NULL,
        `row_order` int(11) DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    sr_run_query($conn, "CREATE TABLE IF NOT EXISTS `sampling_room_cleaning_agent` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plant_id` varchar(50) DEFAULT NULL,
        `agent_name` varchar(255) NOT NULL,
        `manufacturer` varchar(255) DEFAULT NULL,
        `concentration` varchar(100) DEFAULT NULL,
        `default_lot_no` varchar(100) DEFAULT NULL,
        `default_expiry` date DEFAULT NULL,
        `storage_condition` varchar(255) DEFAULT NULL,
        `remarks` text DEFAULT NULL,
        `status` varchar(20) DEFAULT 'Active',
        `entry_by` varchar(50) DEFAULT NULL,
        `entry_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $entryCols = sr_table_columns($conn, 'sampling_room_log_entry');
    if (!isset($entryCols['material_code'])) {
        sr_run_query($conn, "ALTER TABLE `sampling_room_log_entry` ADD `material_code` varchar(50) DEFAULT NULL");
    }
    if (!isset($entryCols['cleaning_agent_id'])) {
        sr_run_query($conn, "ALTER TABLE `sampling_room_log_entry` ADD `cleaning_agent_id` int(11) DEFAULT NULL");
    }

    sr_table_columns($conn, 'sampling_room_log_entry', true);
}

function sr_insert_log_entry($conn, $sheetId, $entry, $rowOrder) {
    ensure_sampling_room_schema($conn);

    $samplingId = !empty($entry['sampling_id']) ? intval($entry['sampling_id']) : null;
    $materialCode = $entry['material_code'] ?? '';
    $materialDesc = $entry['material_description'] ?? '';
    if (!$materialDesc && $materialCode) {
        $materialDesc = $materialCode;
    }
    $materialLot = $entry['material_lot_no'] ?? '';
    $cleaningFull = ($entry['cleaning_full'] === 'Yes') ? 'Yes' : 'No';
    $cleaningPartial = ($entry['cleaning_partial'] === 'Yes') ? 'Yes' : 'No';
    $agentId = !empty($entry['cleaning_agent_id']) ? intval($entry['cleaning_agent_id']) : null;
    $agentName = $entry['cleaning_agent_name'] ?? '';
    $agentLot = $entry['cleaning_agent_lot'] ?? '';
    $agentExpiry = $entry['cleaning_agent_expiry'] ?? '';
    $balanceCleaned = ($entry['balance_cleaned'] === 'Yes') ? 'Yes' : 'No';
    $balanceVerified = ($entry['balance_verified'] === 'Yes') ? 'Yes' : 'No';
    $mag1 = $entry['magnehelic_reading_1'] ?? '';
    $mag2 = $entry['magnehelic_reading_2'] ?? '';
    $mag3 = $entry['magnehelic_reading_3'] ?? '';
    $performedBy = $entry['performed_by'] ?? '';
    $verifiedBy = $entry['verified_by'] ?? '';

    $row = array(
        'sheet_id' => intval($sheetId),
        'sampling_id' => $samplingId,
        'material_code' => $materialCode,
        'material_description' => $materialDesc,
        'material_lot_no' => $materialLot,
        'cleaning_full' => $cleaningFull,
        'cleaning_partial' => $cleaningPartial,
        'cleaning_agent_id' => $agentId,
        'cleaning_agent_name' => $agentName,
        'cleaning_agent_lot' => $agentLot,
        'cleaning_agent_expiry' => $agentExpiry,
        'balance_cleaned' => $balanceCleaned,
        'balance_verified' => $balanceVerified,
        'magnehelic_reading_1' => $mag1,
        'magnehelic_reading_2' => $mag2,
        'magnehelic_reading_3' => $mag3,
        'performed_by' => $performedBy,
        'verified_by' => $verifiedBy,
        'row_order' => intval($rowOrder),
    );

    $tableCols = sr_table_columns($conn, 'sampling_room_log_entry');
    $fields = array();
    $values = array();

    foreach ($row as $field => $val) {
        if (!isset($tableCols[$field])) {
            continue;
        }
        $fields[] = "`".$field."`";
        if ($val === null) {
            $values[] = 'NULL';
        } else if (in_array($field, array('sheet_id', 'sampling_id', 'cleaning_agent_id', 'row_order'), true)) {
            $values[] = "'".intval($val)."'";
        } else if ($field === 'cleaning_agent_expiry' && $val === '') {
            $values[] = 'NULL';
        } else {
            $values[] = "'".$conn->real_escape_string((string)$val)."'";
        }
    }

    if (count($fields) === 0) {
        return array('ok' => false, 'error' => 'No matching columns in sampling_room_log_entry table');
    }

    $sql = "INSERT INTO `sampling_room_log_entry` (".implode(', ', $fields).") VALUES (".implode(', ', $values).")";
    try {
        $ok = $conn->query($sql);
        if ($ok) {
            return array('ok' => true);
        }
        return array('ok' => false, 'error' => $conn->error);
    } catch (Exception $e) {
        return array('ok' => false, 'error' => $e->getMessage());
    }
}

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

    ensure_sampling_room_schema($conn);

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$action.'", "actiontime": "'.$entry_date.'", "department": "'.$_GET['department'].'", "emp_id": "'.$_GET['emp_id'].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = @file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    $plantId = $conn->real_escape_string(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');

    if ($action == 'getNextSheetNo') {
        $year = date('Y');
        $prefix = 'SR-'.$year.'-';
        $sql = "SELECT sheet_no FROM sampling_room_log_sheet
                WHERE plant_id = '".$plantId."' AND sheet_no LIKE '".$prefix."%'
                ORDER BY id DESC LIMIT 1";
        $res = $conn->query($sql);
        $next = 1;
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $parts = explode('-', $row['sheet_no']);
            $last = intval(end($parts));
            $next = $last + 1;
        }
        echo json_encode(array('sheet_no' => $prefix.str_pad($next, 3, '0', STR_PAD_LEFT)));
    }
    else if ($action == 'getSamplingRoomLiveLog') {
        $output = array();
        $materialType = isset($_GET['material_type']) ? trim($_GET['material_type']) : '';
        $sampCols = sr_table_columns($conn, 'sampling');
        $select = array(
            'a.id',
            'a.plant_id',
            'a.sampling_no',
            'a.material_code',
            'a.batch_no',
            'a.status',
            'a.ar_no'
        );
        $optional = array(
            'cleaningDate', 'cleaningTime', 'cleaningDoneBy', 'cleanRemark',
            'cleaningEntryBy', 'cleaningEntryOn', 'cleaningAgentsUsed', 'cleaningType',
            'weighBalCleanDate', 'weighBalCleanDoneBy', 'weighBalCleaningPerformed',
            'weighBalMagnehelic1', 'weighBalMagnehelic2', 'weighBalMagnehelic3',
            'wbCheanEntryOn'
        );
        foreach ($optional as $col) {
            if (isset($sampCols[$col])) {
                $select[] = 'a.`'.$col.'`';
            }
        }

        $sql = "SELECT ".implode(', ', $select).", m.material_name, m.material_type
                FROM sampling a
                LEFT JOIN material m ON a.material_code = m.material_code
                WHERE a.plant_id = '".$plantId."'";

        $doneParts = array(
            "a.status IN ('Area_Cleaning_Done','Balance_Cleaning_Done','LAF_Cleaning_Done','laf_cleaning_done')",
            "a.status LIKE '%Cleaning_Done%'",
            "a.status LIKE '%Cleaning Done%'"
        );
        if (isset($sampCols['cleaningDate'])) {
            $doneParts[] = "(a.cleaningDate IS NOT NULL AND a.cleaningDate != '' AND a.cleaningDate != '0000-00-00')";
        }
        if (isset($sampCols['cleaningEntryOn'])) {
            $doneParts[] = "(a.cleaningEntryOn IS NOT NULL AND a.cleaningEntryOn != '' AND a.cleaningEntryOn != '0000-00-00 00:00:00')";
        }
        $sql .= " AND (".implode(' OR ', $doneParts).")";
        $sql .= " AND a.status NOT IN ('Pending','Allocated','Rejected','ON_HOLD')";

        if ($materialType !== '') {
            $sql .= " AND m.material_type = '".$conn->real_escape_string($materialType)."'";
        }

        $orderCol = isset($sampCols['cleaningEntryOn']) ? 'a.cleaningEntryOn' : 'a.id';
        $sql .= " ORDER BY ".$orderCol." DESC, a.id DESC LIMIT 500";

        $res = @$conn->query($sql);
        $seen = array();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $sid = isset($row['id']) ? $row['id'] : '';
                if ($sid !== '' && isset($seen[$sid])) {
                    continue;
                }
                if ($sid !== '') {
                    $seen[$sid] = true;
                }

                $agents = array();
                if (!empty($row['cleaningAgentsUsed'])) {
                    $decoded = json_decode($row['cleaningAgentsUsed'], true);
                    if (is_array($decoded)) {
                        $agents = $decoded;
                    }
                }
                $agentNames = array();
                foreach ($agents as $agent) {
                    if (!is_array($agent)) {
                        continue;
                    }
                    $label = '';
                    if (!empty($agent['material_name'])) {
                        $label = $agent['material_name'];
                    } else if (!empty($agent['agent_name'])) {
                        $label = $agent['agent_name'];
                    } else if (!empty($agent['material_code'])) {
                        $label = $agent['material_code'];
                    }
                    if ($label !== '') {
                        $agentNames[] = $label;
                    }
                }

                $cleanType = strtolower(trim(isset($row['cleaningType']) ? $row['cleaningType'] : ''));
                $isPartial = ($cleanType === 'partial');
                $statusVal = isset($row['status']) ? $row['status'] : '';
                $balanceDate = isset($row['weighBalCleanDate']) ? $row['weighBalCleanDate'] : '';
                $balanceDone = (stripos($statusVal, 'Balance') !== false)
                    || ($balanceDate !== '' && $balanceDate !== '0000-00-00');
                $performed = isset($row['weighBalCleaningPerformed']) ? $row['weighBalCleaningPerformed'] : '';
                $balanceVerified = (stripos($performed, 'verif') !== false) || $balanceDone;

                $materialName = isset($row['material_name']) ? $row['material_name'] : '';
                $materialCode = isset($row['material_code']) ? $row['material_code'] : '';

                $output[] = array(
                    'sampling_id' => $sid,
                    'id' => $sid,
                    'sampling_no' => isset($row['sampling_no']) ? $row['sampling_no'] : '',
                    'material_code' => $materialCode,
                    'material_name' => $materialName,
                    'material_description' => $materialName !== '' ? $materialName : $materialCode,
                    'material_lot_no' => isset($row['batch_no']) ? $row['batch_no'] : '',
                    'status' => $statusVal,
                    'cleaning_full' => $isPartial ? 'No' : 'Yes',
                    'cleaning_partial' => $isPartial ? 'Yes' : 'No',
                    'cleaning_agents' => $agents,
                    'cleaning_agent_name' => count($agentNames) ? implode(', ', $agentNames) : '',
                    'balance_cleaned' => $balanceDone ? 'Yes' : 'No',
                    'balance_verified' => $balanceVerified ? 'Yes' : 'No',
                    'magnehelic_reading_1' => isset($row['weighBalMagnehelic1']) ? $row['weighBalMagnehelic1'] : '',
                    'magnehelic_reading_2' => isset($row['weighBalMagnehelic2']) ? $row['weighBalMagnehelic2'] : '',
                    'magnehelic_reading_3' => isset($row['weighBalMagnehelic3']) ? $row['weighBalMagnehelic3'] : '',
                    'performed_by' => isset($row['cleaningDoneBy']) ? $row['cleaningDoneBy'] : '',
                    'verified_by' => isset($row['weighBalCleanDoneBy']) ? $row['weighBalCleanDoneBy'] : ''
                );
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getQcMaterials') {
        $materialType = $conn->real_escape_string($_GET['material_type']);
        $sql = "SELECT m.id, m.material_code, m.material_name, m.material_type, m.material_subtype, m.grade, m.unit
                FROM material m
                WHERE m.plant_id = '".$plantId."'
                AND m.material_type = '".$materialType."'
                AND m.status NOT IN ('In-Active', 'Absolute')
                ORDER BY m.material_name ASC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getCleaningAgents') {
        $sql = "SELECT * FROM sampling_room_cleaning_agent
                WHERE plant_id = '".$plantId."' AND status = 'Active'
                ORDER BY agent_name ASC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getCleaningAgentsFromOthers') {
        $output = array();
        $seen = array();
        $plantEsc = $conn->real_escape_string($plantId);

        $cleanWhere = "
                IFNULL(om.status, '') NOT IN ('In-Active', 'Absolute', 'Rejected')
                AND (
                    om.material_subtype = 'Cleaning Agent'
                    OR om.material_subtype = 'Cleaning Agents'
                    OR LOWER(IFNULL(om.material_subtype, '')) LIKE '%clean%'
                    OR LOWER(IFNULL(om.material_subtype, '')) LIKE '%sanit%'
                    OR LOWER(IFNULL(om.material_name, '')) LIKE '%cleaning agent%'
                )";

        $addRow = function ($row) use (&$output, &$seen) {
            $code = trim((string)($row['material_code'] ?? ''));
            $lot = trim((string)($row['batch_no'] ?? ''));
            if ($code === '' || $lot === '' || $lot === '#Autogenerated') {
                return;
            }
            $key = $code.'|'.$lot;
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $output[] = array(
                'material_code' => $code,
                'material_name' => $row['material_name'] ?? '',
                'material_type' => $row['material_type'] ?? '',
                'material_subtype' => $row['material_subtype'] ?? '',
                'unit' => $row['unit'] ?? '',
                'batch_no' => $lot,
                'medicap_lot_no' => $lot,
                'exp_date' => $row['exp_date'] ?? '',
                'mfg_date' => $row['mfg_date'] ?? '',
                'challan_no' => $row['challan_no'] ?? '',
                'option_key' => $key,
            );
        };

        // 1) Received cleaning agents with lot from sampling_batches (match challan when possible).
        $sql = "SELECT
                    om.material_code,
                    om.material_name,
                    om.material_type,
                    om.material_subtype,
                    COALESCE(NULLIF(TRIM(sb.unit), ''), NULLIF(TRIM(om.unit), ''), '') AS unit,
                    NULLIF(TRIM(sb.batch_no), '') AS batch_no,
                    NULLIF(TRIM(sb.exp_date), '') AS exp_date,
                    NULLIF(TRIM(sb.mfg_date), '') AS mfg_date,
                    c.challan_no,
                    c.receiving
                FROM challan_materials c
                INNER JOIN challan c1 ON c.challan_no = c1.challan_no
                INNER JOIN others_material om ON om.material_code = c.material_code
                    AND (om.plant_id = '".$plantEsc."' OR IFNULL(om.plant_id,'') = '')
                INNER JOIN sampling_batches sb ON sb.material_code = c.material_code
                    AND (sb.plant_id = '".$plantEsc."' OR IFNULL(sb.plant_id,'') = '')
                    AND (
                        sb.challan_no = c.challan_no
                        OR IFNULL(sb.challan_no,'') = ''
                        OR sb.challan_no = c.ch_no
                        OR sb.ch_no = c.challan_no
                    )
                WHERE c1.plant_id = '".$plantEsc."'
                AND LOWER(TRIM(IFNULL(c.receiving,''))) IN ('approve', 'approved')
                AND ".$cleanWhere."
                ORDER BY om.material_name ASC, sb.id DESC, c.id DESC";
        $res = @$conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $addRow($row);
            }
        }

        // 2) Stock book lots for cleaning agents that already have completed receiving.
        $sql2 = "SELECT
                    om.material_code,
                    om.material_name,
                    om.material_type,
                    om.material_subtype,
                    COALESCE(NULLIF(TRIM(sb.unit), ''), NULLIF(TRIM(om.unit), ''), '') AS unit,
                    NULLIF(TRIM(sb.batch_no), '') AS batch_no,
                    NULLIF(TRIM(sb.exp_date), '') AS exp_date,
                    NULLIF(TRIM(sb.mfg_date), '') AS mfg_date,
                    IFNULL(sb.receiving_no, '') AS challan_no
                FROM stock_book sb
                INNER JOIN others_material om ON om.material_code = sb.material_code
                    AND (om.plant_id = '".$plantEsc."' OR IFNULL(om.plant_id,'') = '')
                WHERE ".$cleanWhere."
                AND IFNULL(sb.batch_no,'') <> ''
                AND IFNULL(sb.batch_no,'') <> '#Autogenerated'
                AND EXISTS (
                    SELECT 1
                    FROM challan_materials c
                    INNER JOIN challan c1 ON c.challan_no = c1.challan_no
                    WHERE c.material_code = sb.material_code
                    AND c1.plant_id = '".$plantEsc."'
                    AND LOWER(TRIM(IFNULL(c.receiving,''))) IN ('approve', 'approved')
                )
                ORDER BY om.material_name ASC, sb.id DESC";
        $res2 = @$conn->query($sql2);
        if ($res2 && $res2->num_rows > 0) {
            while ($row = $res2->fetch_assoc()) {
                $addRow($row);
            }
        }

        // Only agents with completed receiving + Medicap lot are returned.
        echo json_encode($output);
    }
    else if ($action == 'getCleaningAgentMaster') {
        $sql = "SELECT a.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = a.entry_by LIMIT 1) AS entry_by_name
                FROM sampling_room_cleaning_agent a
                WHERE a.plant_id = '".$plantId."'
                ORDER BY a.agent_name ASC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'saveCleaningAgent') {
        if (!$input || empty($input['agent_name'])) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Agent name is required'));
            exit;
        }
        $agentName = $conn->real_escape_string($input['agent_name']);
        $manufacturer = $conn->real_escape_string($input['manufacturer'] ?? '');
        $concentration = $conn->real_escape_string($input['concentration'] ?? '');
        $defaultLot = $conn->real_escape_string($input['default_lot_no'] ?? '');
        $defaultExpiry = $conn->real_escape_string($input['default_expiry'] ?? '');
        $storage = $conn->real_escape_string($input['storage_condition'] ?? '');
        $remarks = $conn->real_escape_string($input['remarks'] ?? '');
        $entryBy = $conn->real_escape_string($input['entry_by'] ?? $_GET['emp_id']);

        $sql = "INSERT INTO sampling_room_cleaning_agent
                (plant_id, agent_name, manufacturer, concentration, default_lot_no, default_expiry,
                storage_condition, remarks, status, entry_by, entry_date)
                VALUES (
                '".$plantId."', '".$agentName."', '".$manufacturer."', '".$concentration."', '".$defaultLot."',
                ".($defaultExpiry ? "'".$defaultExpiry."'" : "NULL").",
                '".$storage."', '".$remarks."', 'Active', '".$entryBy."', '".$entry_date."')";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'updateCleaningAgent') {
        if (!$input || empty($input['id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $id = intval($input['id']);
        $agentName = $conn->real_escape_string($input['agent_name'] ?? '');
        $manufacturer = $conn->real_escape_string($input['manufacturer'] ?? '');
        $concentration = $conn->real_escape_string($input['concentration'] ?? '');
        $defaultLot = $conn->real_escape_string($input['default_lot_no'] ?? '');
        $defaultExpiry = $conn->real_escape_string($input['default_expiry'] ?? '');
        $storage = $conn->real_escape_string($input['storage_condition'] ?? '');
        $remarks = $conn->real_escape_string($input['remarks'] ?? '');
        $status = $conn->real_escape_string($input['status'] ?? 'Active');

        $sql = "UPDATE sampling_room_cleaning_agent SET
                agent_name = '".$agentName."',
                manufacturer = '".$manufacturer."',
                concentration = '".$concentration."',
                default_lot_no = '".$defaultLot."',
                default_expiry = ".($defaultExpiry ? "'".$defaultExpiry."'" : "NULL").",
                storage_condition = '".$storage."',
                remarks = '".$remarks."',
                status = '".$status."'
                WHERE id = '".$id."' AND plant_id = '".$plantId."'";
        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
    else if ($action == 'getPendingSampling') {
        $materialType = $conn->real_escape_string($_GET['material_type']);
        $sql = "SELECT a.id, a.sampling_no, a.material_code, a.batch_no, a.ar_no, a.grn_no, a.grn_date,
                a.containers, a.status, m.material_name, m.material_type, m.grade
                FROM sampling a
                LEFT JOIN material m ON a.material_code = m.material_code
                WHERE a.plant_id = '".$plantId."'
                AND a.status = 'Allocated'
                AND m.material_type = '".$materialType."'
                AND a.id NOT IN (
                    SELECT e.sampling_id FROM sampling_room_log_entry e
                    INNER JOIN sampling_room_log_sheet s ON s.id = e.sheet_id
                    WHERE e.sampling_id IS NOT NULL AND s.status != 'cancelled'
                )
                ORDER BY a.alloocationOn DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'saveSamplingRoomLog') {
        if (!$input) {
            echo json_encode(array('status' => 'invalid', 'message' => 'No data received'));
            exit;
        }

        ensure_sampling_room_schema($conn);

        $sheetNo = $conn->real_escape_string($input['sheet_no']);
        $logDate = $conn->real_escape_string($input['log_date']);
        $entryBy = $conn->real_escape_string($input['entry_by']);

        $sql = "INSERT INTO sampling_room_log_sheet
                (plant_id, sheet_no, log_date, status, entry_by, entry_date)
                VALUES ('".$plantId."', '".$sheetNo."', '".$logDate."', 'pending', '".$entryBy."', '".$entry_date."')";

        try {
            if (!$conn->query($sql)) {
                echo json_encode(array('status' => $conn->error));
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(array('status' => $e->getMessage()));
            exit;
        }

        $sheetId = $conn->insert_id;
        $entries = isset($input['entries']) ? $input['entries'] : array();
        $rowOrder = 0;

        foreach ($entries as $entry) {
            if (empty($entry['material_description']) && empty($entry['material_lot_no']) && empty($entry['material_code'])) {
                continue;
            }
            $rowOrder++;
            $insertResult = sr_insert_log_entry($conn, $sheetId, $entry, $rowOrder);
            if (!$insertResult['ok']) {
                $conn->query("DELETE FROM sampling_room_log_entry WHERE sheet_id = '".$sheetId."'");
                $conn->query("DELETE FROM sampling_room_log_sheet WHERE id = '".$sheetId."'");
                echo json_encode(array('status' => $insertResult['error']));
                exit;
            }
        }

        if ($rowOrder === 0) {
            $conn->query("DELETE FROM sampling_room_log_sheet WHERE id = '".$sheetId."'");
            echo json_encode(array('status' => 'invalid', 'message' => 'No log rows to save'));
            exit;
        }

        echo json_encode(array('status' => 'success', 'sheet_id' => $sheetId));
    }
    else if ($action == 'getSamplingRoomLogs') {
        $fromDate = isset($_GET['from_date']) ? $conn->real_escape_string($_GET['from_date']) : '';
        $toDate = isset($_GET['to_date']) ? $conn->real_escape_string($_GET['to_date']) : '';
        $where = "s.plant_id = '".$plantId."'";
        if ($fromDate) {
            $where .= " AND s.log_date >= '".$fromDate."'";
        }
        if ($toDate) {
            $where .= " AND s.log_date <= '".$toDate."'";
        }

        $sql = "SELECT s.*,
                (SELECT COUNT(*) FROM sampling_room_log_entry e WHERE e.sheet_id = s.id) AS entry_count,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.entry_by LIMIT 1) AS entry_by_name
                FROM sampling_room_log_sheet s
                WHERE ".$where."
                ORDER BY s.log_date DESC, s.id DESC";
        $res = $conn->query($sql);
        $output = array();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($action == 'getSamplingRoomLogById') {
        $sheetId = intval($_GET['id']);
        $sql = "SELECT s.*,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.entry_by LIMIT 1) AS entry_by_name,
                (SELECT CONCAT(e.firstname, ' ', e.lastname) FROM employee e WHERE e.emp_id = s.verified_by LIMIT 1) AS verified_by_name
                FROM sampling_room_log_sheet s
                WHERE s.id = '".$sheetId."' AND s.plant_id = '".$plantId."' LIMIT 1";
        $res = $conn->query($sql);
        $sheet = null;
        if ($res && $res->num_rows > 0) {
            $sheet = $res->fetch_assoc();
        }

        $entries = array();
        if ($sheet) {
            $sql2 = "SELECT e.*,
                     (SELECT CONCAT(em.firstname, ' ', em.lastname) FROM employee em WHERE em.emp_id = e.verified_by LIMIT 1) AS verified_by_name,
                     sam.sampling_no, sam.material_code AS sampling_material_code
                     FROM sampling_room_log_entry e
                     LEFT JOIN sampling sam ON sam.id = e.sampling_id
                     WHERE e.sheet_id = '".$sheetId."'
                     ORDER BY e.row_order ASC, e.id ASC";
            $res2 = $conn->query($sql2);
            if ($res2 && $res2->num_rows > 0) {
                while ($row = $res2->fetch_assoc()) {
                    if (empty($row['verified_by_name']) && !empty($row['verified_by'])) {
                        $row['verified_by_name'] = $row['verified_by'];
                    }
                    $entries[] = $row;
                }
            }
        }

        echo json_encode(array('sheet' => $sheet, 'entries' => $entries));
    }
    else if ($action == 'verifySamplingRoomLogEntry') {
        if (!$input || empty($input['sheet_id']) || empty($input['entry_id'])) {
            echo json_encode(array('status' => 'invalid', 'message' => 'Missing entry data'));
            exit;
        }
        $sheetId = intval($input['sheet_id']);
        $entryId = intval($input['entry_id']);
        $verifiedByEmp = $conn->real_escape_string($input['verified_by'] ?? $_GET['emp_id']);
        $verifiedByDisplay = trim($input['verified_by_name'] ?? '');
        if ($verifiedByDisplay === '') {
            $verifiedByDisplay = $verifiedByEmp;
            $nameRes = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".$verifiedByEmp."' LIMIT 1");
            if ($nameRes && $nameRes->num_rows > 0) {
                $nameRow = $nameRes->fetch_assoc();
                $nm = trim($nameRow['nm'] ?? '');
                if ($nm !== '') {
                    $verifiedByDisplay = $nm;
                }
            }
        }
        $verifiedByName = $conn->real_escape_string($verifiedByDisplay);

        $sqlEntry = "UPDATE sampling_room_log_entry
                     SET verified_by = '".$verifiedByName."'
                     WHERE id = '".$entryId."' AND sheet_id = '".$sheetId."'";
        if (!$conn->query($sqlEntry)) {
            echo json_encode(array('status' => $conn->error));
            exit;
        }

        $pendingRes = $conn->query("SELECT COUNT(*) AS cnt FROM sampling_room_log_entry
                                    WHERE sheet_id = '".$sheetId."'
                                    AND (verified_by IS NULL OR TRIM(verified_by) = '')");
        $pendingCount = 0;
        if ($pendingRes && $pendingRes->num_rows > 0) {
            $pendingCount = intval($pendingRes->fetch_assoc()['cnt']);
        }

        $sheetVerified = false;
        if ($pendingCount === 0) {
            $conn->query("UPDATE sampling_room_log_sheet
                          SET status = 'verified', verified_by = '".$verifiedByEmp."', verified_on = '".$entry_date."'
                          WHERE id = '".$sheetId."' AND plant_id = '".$plantId."'");
            $sheetVerified = true;
        }

        echo json_encode(array(
            'status' => 'success',
            'verified_by_name' => $verifiedByDisplay,
            'sheet_verified' => $sheetVerified,
            'pending_count' => $pendingCount
        ));
    }
    else if ($action == 'verifySamplingRoomLog') {
        if (!$input || empty($input['sheet_id'])) {
            echo json_encode(array('status' => 'invalid'));
            exit;
        }
        $sheetId = intval($input['sheet_id']);
        $verifiedByEmp = $conn->real_escape_string($input['verified_by'] ?? $_GET['emp_id']);

        $verifiedByDisplay = $input['verified_by'] ?? $_GET['emp_id'];
        $nameRes = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS nm FROM employee WHERE emp_id = '".$verifiedByEmp."' LIMIT 1");
        if ($nameRes && $nameRes->num_rows > 0) {
            $nameRow = $nameRes->fetch_assoc();
            $nm = trim($nameRow['nm'] ?? '');
            if ($nm !== '') {
                $verifiedByDisplay = $nm;
            }
        }
        $verifiedByName = $conn->real_escape_string($verifiedByDisplay);

        $sql = "UPDATE sampling_room_log_sheet
                SET status = 'verified', verified_by = '".$verifiedByEmp."', verified_on = '".$entry_date."'
                WHERE id = '".$sheetId."' AND plant_id = '".$plantId."'";

        if ($conn->query($sql)) {
            $hasRowOverride = false;
            if (!empty($input['entries']) && is_array($input['entries'])) {
                foreach ($input['entries'] as $entry) {
                    if (empty($entry['id'])) {
                        continue;
                    }
                    $entryId = intval($entry['id']);
                    $customVerified = trim($entry['verified_by'] ?? '');
                    $rowVerifiedBy = $customVerified !== '' ? $conn->real_escape_string($customVerified) : $verifiedByName;
                    $hasRowOverride = true;
                    $conn->query("UPDATE sampling_room_log_entry SET verified_by = '".$rowVerifiedBy."' WHERE id = '".$entryId."' AND sheet_id = '".$sheetId."'");
                }
            }
            if (!$hasRowOverride) {
                $conn->query("UPDATE sampling_room_log_entry SET verified_by = '".$verifiedByName."' WHERE sheet_id = '".$sheetId."'");
            }
            echo json_encode(array('status' => 'success', 'verified_by_name' => $verifiedByDisplay));
        } else {
            echo json_encode(array('status' => $conn->error));
        }
    }
} else {
    echo json_encode(array('status' => 'invalid', 'message' => 'Invalid token'));
}
$conn->close();
?>
