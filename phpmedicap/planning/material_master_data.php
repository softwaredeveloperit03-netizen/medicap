<?php
/**
 * Planning — Material Master Data (forecast lead-time days on RM/PM).
 * Types: getList | saveOne | saveBulk
 */
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

try {
    $token = $_GET['token'] ?? '';
    $entry_date = date('Y-m-d H:i:s');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = array();
    }

    $sql = "SELECT * FROM token WHERE token='" . mysqli_real_escape_string($conn, $token) . "'";
    $result = $conn->query($sql);
    $_GET['emp_id'] = '';
    $_GET['department'] = '';
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $string = decrypt('decrypt', $token, $row['key1'], $row['key2']);
            $string = explode('$', $string);
            $_GET['emp_id'] = $string[0] ?? '';
            $_GET['department'] = $string[1] ?? '';
            break;
        }
    }

    $plant_id = mysqli_real_escape_string($conn, $_GET['plant_id'] ?? '');
    $type = $_GET['type'] ?? '';

    /* ---------- helpers ---------- */

    function mmd_col_exists($conn, $table, $column)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = $conn->real_escape_string($column);
        $check = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $check && $check->num_rows > 0;
    }

    function mmd_ensure_day_columns($conn, $table)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $cols = array(
            'Indent_Approve_date' => "INT NULL DEFAULT 0 COMMENT 'indent approval days'",
            'testing_prepare_date' => "INT NULL DEFAULT 0 COMMENT 'testing days after sampling'",
            'documentation_release_date' => "INT NULL DEFAULT 0 COMMENT 'doc and release days'",
            'total_days' => "INT NULL DEFAULT 0 COMMENT 'forecast total receiving+release'",
        );
        foreach ($cols as $name => $def) {
            if (!mmd_col_exists($conn, $table, $name)) {
                $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$def}");
            }
        }
    }

    function mmd_day($v)
    {
        if ($v === null || $v === '') {
            return 0;
        }
        $n = (int)$v;
        return $n < 0 ? 0 : $n;
    }

    function mmd_type_bucket($materialType)
    {
        $t = strtoupper(trim((string)$materialType));
        if ($t === 'RM' || strpos($t, 'RAW') !== false) {
            return 'RM';
        }
        if ($t === 'PM' || strpos($t, 'PACK') !== false) {
            return 'PM';
        }
        if (strpos($t, 'MANUF') !== false || strpos($t, 'BULK') !== false) {
            return 'Manufacturing';
        }
        return 'Others';
    }

    function mmd_is_rm_pm($materialType)
    {
        $b = mmd_type_bucket($materialType);
        return $b === 'RM' || $b === 'PM';
    }

    function mmd_target_table($materialType)
    {
        return mmd_is_rm_pm($materialType) ? 'material' : 'others_material';
    }

    function mmd_map_row($row)
    {
        $indentApproval = mmd_day($row['Indent_Approve_date'] ?? 0);
        $poAfter = mmd_day($row['Purchase_prepare_date'] ?? 0);
        $payment = mmd_day($row['ForPayment'] ?? 0);
        $vendorTransit = mmd_day($row['PurchaseDeliveryTime'] ?? 0);
        $totalReceiving = $indentApproval + $poAfter + $payment + $vendorTransit;

        // Indent before recv is always = total receiving (auto)
        $indentBefore = $totalReceiving;

        $sampling = mmd_day($row['Sampling_prepare_date'] ?? 0);
        $testing = mmd_day($row['testing_prepare_date'] ?? 0);
        if ($testing <= 0) {
            // legacy: release_prepare_date held testing+doc together; prefer split cols when present
            $testing = 0;
        }
        $doc = mmd_day($row['documentation_release_date'] ?? 0);
        if ($doc <= 0 && $testing <= 0) {
            // old single release field → treat as testing for display if new cols empty
            $legacyRelease = mmd_day($row['release_prepare_date'] ?? 0);
            if ($legacyRelease > 0) {
                $testing = $legacyRelease;
            }
        }
        $totalRelease = $sampling + $testing + $doc;
        $forecastTotal = $totalReceiving + $totalRelease;

        $materialType = $row['material_type'] ?? '';
        return array(
            'id' => $row['id'] ?? '',
            'material_code' => $row['material_code'] ?? '',
            'material_name' => $row['material_name'] ?? '',
            'material_type' => $materialType,
            'material_subtype' => $row['material_subtype'] ?? '',
            'grade' => $row['grade'] ?? '',
            'type_bucket' => mmd_type_bucket($materialType),
            'source_table' => $row['source_table'] ?? mmd_target_table($materialType),
            'status' => $row['status'] ?? '',
            // receiving
            'indent_approval' => $indentApproval,
            'po_after_indent' => $poAfter,
            'payment_days' => $payment,
            'vendor_lead_days' => $vendorTransit,
            'total_receiving' => $totalReceiving,
            'indent_before_recv' => $indentBefore,
            'purchase_lead_days' => $totalReceiving,
            // release
            'sampling_days' => $sampling,
            'testing_days' => $testing,
            'doc_days' => $doc,
            'total_release' => $totalRelease,
            'forecast_total' => $forecastTotal,
            // raw DB names (for save compatibility)
            'indend_prepare_date' => $indentBefore,
            'Indent_Approve_date' => $indentApproval,
            'Purchase_prepare_date' => $poAfter,
            'ForPayment' => $payment,
            'PurchaseDeliveryTime' => $vendorTransit,
            'Sampling_prepare_date' => $sampling,
            'testing_prepare_date' => $testing,
            'documentation_release_date' => $doc,
            'release_prepare_date' => ($testing + $doc),
            'total_days' => $forecastTotal,
        );
    }

    function mmd_select_cols($conn, $table, $aliasSource)
    {
        $parts = array(
            'id',
            'material_code',
            "COALESCE(material_name,'') AS material_name",
            "COALESCE(material_type,'') AS material_type",
            "COALESCE(material_subtype,'') AS material_subtype",
            "COALESCE(grade,'') AS grade",
            "COALESCE(status,'') AS status",
            "COALESCE(indend_prepare_date,0) AS indend_prepare_date",
            "COALESCE(Purchase_prepare_date,0) AS Purchase_prepare_date",
            "COALESCE(ForPayment,0) AS ForPayment",
            "COALESCE(PurchaseDeliveryTime,0) AS PurchaseDeliveryTime",
            "COALESCE(Sampling_prepare_date,0) AS Sampling_prepare_date",
            "COALESCE(release_prepare_date,0) AS release_prepare_date",
        );
        if (mmd_col_exists($conn, $table, 'Indent_Approve_date')) {
            $parts[] = "COALESCE(Indent_Approve_date,0) AS Indent_Approve_date";
        } else {
            $parts[] = "0 AS Indent_Approve_date";
        }
        if (mmd_col_exists($conn, $table, 'testing_prepare_date')) {
            $parts[] = "COALESCE(testing_prepare_date,0) AS testing_prepare_date";
        } else {
            $parts[] = "0 AS testing_prepare_date";
        }
        if (mmd_col_exists($conn, $table, 'documentation_release_date')) {
            $parts[] = "COALESCE(documentation_release_date,0) AS documentation_release_date";
        } else {
            $parts[] = "0 AS documentation_release_date";
        }
        if (mmd_col_exists($conn, $table, 'total_days')) {
            $parts[] = "COALESCE(total_days,0) AS total_days";
        } else {
            $parts[] = "0 AS total_days";
        }
        $parts[] = "'{$aliasSource}' AS source_table";
        return implode(",\n                ", $parts);
    }

    function mmd_build_where($conn, $plant_id, $materialTypeFilter, $search)
    {
        $w = array();
        if ($plant_id !== '' && mmd_col_exists($conn, 'material', 'plant_id')) {
            $w[] = "plant_id='" . mysqli_real_escape_string($conn, $plant_id) . "'";
        }
        // Prefer approved / active; exclude obsolete
        $w[] = "(LOWER(IFNULL(status,'')) IN ('approved','approve','active','') OR status IS NULL)";
        $w[] = "LOWER(IFNULL(status,'')) NOT IN ('obsolete','absolute','reject','rejected','inactive','in-active')";

        $mt = trim((string)$materialTypeFilter);
        if ($mt === '' || strtoupper($mt) === 'ALL_RM_PM' || strtoupper($mt) === 'RM_PM') {
            $w[] = "(material_type IN ('Raw Material','Packing Material','RM','PM') OR material_type LIKE '%Raw%' OR material_type LIKE '%Pack%')";
        } else if (strtoupper($mt) === 'RM' || stripos($mt, 'Raw') !== false) {
            $w[] = "(material_type IN ('Raw Material','RM') OR material_type LIKE '%Raw%')";
        } else if (strtoupper($mt) === 'PM' || stripos($mt, 'Pack') !== false) {
            $w[] = "(material_type IN ('Packing Material','PM') OR material_type LIKE '%Pack%')";
        } else if (strtoupper($mt) === 'MANUFACTURING') {
            $w[] = "(material_type LIKE '%Manuf%' OR material_type LIKE '%Bulk%')";
        } else if (strtoupper($mt) === 'OTHERS') {
            $w[] = "(material_type NOT IN ('Raw Material','Packing Material','RM','PM') AND material_type NOT LIKE '%Raw%' AND material_type NOT LIKE '%Pack%')";
        }
        // ALL types = no type filter beyond status

        if ($search !== '') {
            $s = mysqli_real_escape_string($conn, $search);
            $w[] = "(material_code LIKE '%{$s}%' OR material_name LIKE '%{$s}%' OR IFNULL(grade,'') LIKE '%{$s}%')";
        }
        return $w;
    }

    function mmd_compute_from_input($input)
    {
        $indentApproval = mmd_day($input['indent_approval'] ?? $input['Indent_Approve_date'] ?? 0);
        $poAfter = mmd_day($input['po_after_indent'] ?? $input['Purchase_prepare_date'] ?? 0);
        $payment = mmd_day($input['payment_days'] ?? $input['ForPayment'] ?? 0);
        $vendorTransit = mmd_day($input['vendor_lead_days'] ?? $input['PurchaseDeliveryTime'] ?? 0);
        $totalReceiving = $indentApproval + $poAfter + $payment + $vendorTransit;

        $sampling = mmd_day($input['sampling_days'] ?? $input['Sampling_prepare_date'] ?? 0);
        $testing = mmd_day($input['testing_days'] ?? $input['testing_prepare_date'] ?? 0);
        $doc = mmd_day($input['doc_days'] ?? $input['documentation_release_date'] ?? 0);
        $totalRelease = $sampling + $testing + $doc;

        return array(
            'indent_approval' => $indentApproval,
            'po_after_indent' => $poAfter,
            'payment_days' => $payment,
            'vendor_lead_days' => $vendorTransit,
            'total_receiving' => $totalReceiving,
            'indent_before_recv' => $totalReceiving,
            'sampling_days' => $sampling,
            'testing_days' => $testing,
            'doc_days' => $doc,
            'total_release' => $totalRelease,
            'forecast_total' => $totalReceiving + $totalRelease,
        );
    }

    function mmd_save_one($conn, $materialCode, $materialType, $days)
    {
        $code = mysqli_real_escape_string($conn, $materialCode);
        $table = mmd_target_table($materialType);
        mmd_ensure_day_columns($conn, $table);

        $sets = array(
            "indend_prepare_date='" . (int)$days['indent_before_recv'] . "'",
            "Indent_Approve_date='" . (int)$days['indent_approval'] . "'",
            "Purchase_prepare_date='" . (int)$days['po_after_indent'] . "'",
            "ForPayment='" . (int)$days['payment_days'] . "'",
            "PurchaseDeliveryTime='" . (int)$days['vendor_lead_days'] . "'",
            "Sampling_prepare_date='" . (int)$days['sampling_days'] . "'",
            "testing_prepare_date='" . (int)$days['testing_days'] . "'",
            "documentation_release_date='" . (int)$days['doc_days'] . "'",
            "release_prepare_date='" . ((int)$days['testing_days'] + (int)$days['doc_days']) . "'",
            "total_days='" . (int)$days['forecast_total'] . "'",
        );
        // also keep purchase_lead_time if column exists (legacy consumers)
        if (mmd_col_exists($conn, $table, 'purchase_lead_time')) {
            $sets[] = "purchase_lead_time='" . (int)$days['total_receiving'] . "'";
        }

        $sql = "UPDATE `{$table}` SET " . implode(',', $sets) . " WHERE material_code='{$code}'";
        if (!$conn->query($sql)) {
            return array('ok' => false, 'error' => $conn->error);
        }
        if ($conn->affected_rows < 1) {
            // try other table if type ambiguous
            $other = ($table === 'material') ? 'others_material' : 'material';
            mmd_ensure_day_columns($conn, $other);
            $sql2 = "UPDATE `{$other}` SET " . implode(',', $sets) . " WHERE material_code='{$code}'";
            if (!$conn->query($sql2)) {
                return array('ok' => false, 'error' => $conn->error);
            }
        }
        return array('ok' => true);
    }

    /* ---------- ensure schema once ---------- */
    mmd_ensure_day_columns($conn, 'material');
    mmd_ensure_day_columns($conn, 'others_material');

    /* ---------- APIs ---------- */

    if ($type == 'getList') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['pageSize'] ?? 25);
        if (!in_array($pageSize, array(25, 50, 100, 200), true)) {
            $pageSize = 25;
        }
        $search = trim((string)($_GET['search'] ?? ''));
        $materialType = trim((string)($_GET['material_type'] ?? 'RM_PM'));
        $offset = ($page - 1) * $pageSize;

        $whereParts = mmd_build_where($conn, $plant_id, $materialType, $search);
        $whereSql = count($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        $colsMat = mmd_select_cols($conn, 'material', 'material');
        $colsOth = mmd_select_cols($conn, 'others_material', 'others_material');

        $mtUp = strtoupper($materialType);
        // Default RM/PM → material table only. Manufacturing / Others / ALL also scan others_material.
        $includeOthers = in_array($mtUp, array('ALL', 'MANUFACTURING', 'OTHERS'), true);

        $union = "SELECT {$colsMat} FROM material {$whereSql}";
        if ($includeOthers) {
            $union .= " UNION ALL SELECT {$colsOth} FROM others_material {$whereSql}";
        }

        $countSql = "SELECT COUNT(*) AS cnt FROM ({$union}) AS u";
        $countRes = $conn->query($countSql);
        $total = 0;
        if ($countRes && ($crow = $countRes->fetch_assoc())) {
            $total = (int)$crow['cnt'];
        }

        $dataSql = "SELECT * FROM ({$union}) AS u ORDER BY material_code ASC LIMIT {$pageSize} OFFSET {$offset}";
        $dataRes = $conn->query($dataSql);
        $rows = array();
        if ($dataRes) {
            while ($r = $dataRes->fetch_assoc()) {
                $rows[] = mmd_map_row($r);
            }
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error, 'data' => array(), 'total' => 0));
            exit;
        }

        echo json_encode(array(
            'status' => 'success',
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 1,
        ));
        exit;
    }

    else if ($type == 'saveOne') {
        $code = trim((string)($input['material_code'] ?? ''));
        $mtype = trim((string)($input['material_type'] ?? 'Raw Material'));
        if ($code === '') {
            echo json_encode(array('status' => 'error', 'message' => 'material_code required'));
            exit;
        }
        $days = mmd_compute_from_input($input);
        $res = mmd_save_one($conn, $code, $mtype, $days);
        if ($res['ok']) {
            echo json_encode(array('status' => 'success', 'totals' => $days));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $res['error']));
        }
        exit;
    }

    else if ($type == 'saveBulk') {
        $list = $input['materials'] ?? $input['material_codes'] ?? array();
        if (!is_array($list) || count($list) === 0) {
            echo json_encode(array('status' => 'error', 'message' => 'No materials selected'));
            exit;
        }
        $days = mmd_compute_from_input($input);
        $ok = 0;
        $fail = 0;
        $errors = array();
        foreach ($list as $item) {
            if (is_string($item)) {
                $code = $item;
                $mtype = 'Raw Material';
            } else {
                $code = trim((string)($item['material_code'] ?? ''));
                $mtype = trim((string)($item['material_type'] ?? 'Raw Material'));
            }
            if ($code === '') {
                $fail++;
                continue;
            }
            $res = mmd_save_one($conn, $code, $mtype, $days);
            if ($res['ok']) {
                $ok++;
            } else {
                $fail++;
                $errors[] = $code . ': ' . $res['error'];
            }
        }
        echo json_encode(array(
            'status' => $ok > 0 ? 'success' : 'error',
            'updated' => $ok,
            'failed' => $fail,
            'errors' => $errors,
            'message' => $ok > 0 ? 'Material timeline updated' : 'Update failed',
        ));
        exit;
    }

    else {
        echo json_encode(array('status' => 'error', 'message' => 'Unknown type'));
    }
} catch (Throwable $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
