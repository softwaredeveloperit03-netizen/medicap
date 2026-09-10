<?php 
require '../db.php';
require '../token.php';
$output = Array();
require '../tcpdf/tcpdf.php';

if (!function_exists('medicap_ensure_control_sample_extra_columns')) {
    function medicap_ensure_control_sample_extra_columns($conn) {
        static $done = false;
        if ($done) {
            return;
        }
        $columns = array(
            'actual_control_sample' => "ALTER TABLE control_sample ADD COLUMN actual_control_sample VARCHAR(64) DEFAULT NULL AFTER sample_quantity",
        );
        foreach ($columns as $name => $alterSql) {
            $nameEsc = $conn->real_escape_string($name);
            $res = @$conn->query("SHOW COLUMNS FROM control_sample LIKE '".$nameEsc."'");
            if (!$res || $res->num_rows === 0) {
                @$conn->query($alterSql);
            }
        }
        $done = true;
    }
}

if (!function_exists('medicap_cs_normalize_plant_id')) {
    function medicap_cs_normalize_plant_id($plantId) {
        $plantId = trim((string)$plantId);
        if ($plantId === '' || strtolower($plantId) === 'null' || strtolower($plantId) === 'undefined') {
            return '';
        }
        return $plantId;
    }
}

if (!function_exists('medicap_cs_format_date_field')) {
    function medicap_cs_format_date_field($value) {
        $value = trim((string)$value);
        if ($value === '' || strpos($value, '0000') === 0) {
            return '';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }
        return date('Y-m-d', $ts);
    }
}

if (!function_exists('medicap_cs_control_sample_entry_exists')) {
    function medicap_cs_control_sample_entry_exists($conn, $materialCode, $arNo, $batchNo) {
        $codeEsc = $conn->real_escape_string(trim((string)$materialCode));
        if ($codeEsc === '') {
            return false;
        }
        $arEsc = $conn->real_escape_string(trim((string)$arNo));
        $batchEsc = $conn->real_escape_string(trim((string)$batchNo));
        $sql = "SELECT id FROM control_sample WHERE material_code='".$codeEsc."'
            AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('rejected', 'destroyed', 'destroy')";
        if ($arEsc === '' && $batchEsc === '') {
            return false;
        }
        if ($arEsc !== '' && $batchEsc !== '') {
            $sql .= " AND ar_no='".$arEsc."' AND batch_no='".$batchEsc."'";
        } elseif ($arEsc !== '') {
            $sql .= " AND ar_no='".$arEsc."'";
        } elseif ($batchEsc !== '') {
            $sql .= " AND batch_no='".$batchEsc."'";
        }
        $sql .= " LIMIT 1";
        $res = $conn->query($sql);
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('medicap_cs_spec_type_sql')) {
    function medicap_cs_spec_type_sql($alias = '') {
        $p = ($alias !== '') ? $alias.'.' : '';
        return "(
            ".$p."spec_type IN ('Raw Material', 'Packing Material', 'Finish Product', 'Finished Product',
                'Raw Material Specification', 'Packing Material Specification', 'Finish Product Specification')
            OR ".$p."spec_type LIKE 'Raw Material%'
            OR ".$p."spec_type LIKE 'Packing Material%'
            OR ".$p."spec_type LIKE 'Finish Product%'
            OR ".$p."spec_type LIKE 'Finished Product%'
        )";
    }
}

if (!function_exists('medicap_cs_spec_with_sampling_plan_subquery')) {
  /** Latest approved specification per material that has Sampling Plan + Control Sample Qty (specification-logs source). */
  function medicap_cs_spec_with_sampling_plan_subquery($conn, $plantId = '') {
        $plantId = medicap_cs_normalize_plant_id($plantId);
        $plantEsc = ($plantId !== '') ? $conn->real_escape_string($plantId) : '';
        $plantFilter = ($plantEsc !== '') ? " AND plant_id='".$plantEsc."'" : '';
        $specTypeSql = medicap_cs_spec_type_sql();
        return "(
            SELECT s.*
            FROM specification s
            INNER JOIN (
                SELECT material_code, MAX(id) AS max_id
                FROM specification
                WHERE ".$specTypeSql."
                AND LOWER(TRIM(COALESCE(status,''))) IN ('approve', 'approved')
                AND COALESCE(NULLIF(TRIM(sampling_plan),''), '') <> ''
                AND CAST(COALESCE(NULLIF(TRIM(control_sample),''), '0') AS DECIMAL(18,4)) > 0
                ".$plantFilter."
                GROUP BY material_code
            ) latest ON latest.max_id = s.id
        )";
    }
}

if (!function_exists('medicap_cs_apply_spec_sampling_plan_row')) {
    function medicap_cs_apply_spec_sampling_plan_row($row) {
        if (!is_array($row)) {
            return $row;
        }
        $controlSample = trim((string)($row['control_sample'] ?? ''));
        if ($controlSample !== '') {
            $row['sample_quantity'] = $controlSample;
        }
        if (empty($row['specification_no']) && !empty($row['spec_no'])) {
            $row['specification_no'] = $row['spec_no'];
        }
        return $row;
    }
}

if (!function_exists('medicap_cs_resolve_spec_control_sample')) {
    function medicap_cs_resolve_spec_control_sample($conn, $testingNo, $materialCode, $plantId = '') {
        $qty = '';
        $unit = '';
        $testingNoEsc = $conn->real_escape_string(trim((string)$testingNo));
        if ($testingNoEsc !== '') {
            $sql = "SELECT sp.control_sample, sp.unit, sp.sample_qty, sp.additional_sample, sp.totalsample_qty, sp.sampling_plan, sp.specification_no
                FROM testing_tests tt
                INNER JOIN specification sp ON sp.specification_no = tt.specification_no
                WHERE tt.testing_no = '".$testingNoEsc."'
                AND LOWER(TRIM(COALESCE(sp.status,''))) IN ('approve', 'approved')
                AND CAST(COALESCE(NULLIF(TRIM(sp.control_sample),''), '0') AS DECIMAL(18,4)) > 0
                ORDER BY sp.id DESC
                LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $qty = trim((string)($row['control_sample'] ?? ''));
                $unit = trim((string)($row['unit'] ?? ''));
                return array(
                    'sample_quantity' => $qty,
                    'unit' => $unit,
                    'sample_qty' => $row['sample_qty'] ?? '',
                    'additional_sample' => $row['additional_sample'] ?? '',
                    'totalsample_qty' => $row['totalsample_qty'] ?? '',
                    'sampling_plan' => $row['sampling_plan'] ?? '',
                    'specification_no' => $row['specification_no'] ?? '',
                );
            }
        }
        $codeEsc = $conn->real_escape_string(trim((string)$materialCode));
        $plantId = medicap_cs_normalize_plant_id($plantId);
        $plantEsc = ($plantId !== '') ? $conn->real_escape_string($plantId) : '';
        $plantFilter = ($plantEsc !== '') ? " AND plant_id='".$plantEsc."'" : '';
        $specTypeSql = medicap_cs_spec_type_sql();
        $sql2 = "SELECT control_sample, unit, sample_qty, additional_sample, totalsample_qty, sampling_plan, specification_no
            FROM specification
            WHERE material_code = '".$codeEsc."'
            AND ".$specTypeSql."
            AND LOWER(TRIM(COALESCE(status,''))) IN ('approve', 'approved')
            AND COALESCE(NULLIF(TRIM(sampling_plan),''), '') <> ''
            AND CAST(COALESCE(NULLIF(TRIM(control_sample),''), '0') AS DECIMAL(18,4)) > 0
            ".$plantFilter."
            ORDER BY id DESC
            LIMIT 1";
        $res2 = $conn->query($sql2);
        if ($res2 && $res2->num_rows > 0) {
            $row2 = $res2->fetch_assoc();
            $qty = trim((string)($row2['control_sample'] ?? ''));
            $unit = trim((string)($row2['unit'] ?? ''));
            return array(
                'sample_quantity' => $qty,
                'unit' => $unit,
                'sample_qty' => $row2['sample_qty'] ?? '',
                'additional_sample' => $row2['additional_sample'] ?? '',
                'totalsample_qty' => $row2['totalsample_qty'] ?? '',
                'sampling_plan' => $row2['sampling_plan'] ?? '',
                'specification_no' => $row2['specification_no'] ?? '',
            );
        }
        return array('sample_quantity' => '', 'unit' => '');
    }
}

if (!function_exists('medicap_cs_map_material_type_label')) {
    function medicap_cs_map_material_type_label($materialType) {
        $t = trim((string)$materialType);
        if ($t === 'Finish Product') {
            return 'Finished Product';
        }
        return $t;
    }
}

if (!function_exists('medicap_get_pending_control_sample_entry_log')) {
    function medicap_get_pending_control_sample_entry_log($conn, $plantId = '') {
        medicap_ensure_control_sample_extra_columns($conn);
        $output = array();
        $seen = array();
        $plantId = medicap_cs_normalize_plant_id($plantId);
        $plantEsc = ($plantId !== '') ? $conn->real_escape_string($plantId) : '';
        $plantSqlT = ($plantEsc !== '') ? " AND t.plant_id='".$plantEsc."'" : '';
        $plantSqlS = ($plantEsc !== '') ? " AND s.plant_id='".$plantEsc."'" : '';
        $specSub = medicap_cs_spec_with_sampling_plan_subquery($conn, $plantId);

        $append = function ($row) use (&$output, &$seen, $conn, $plantId) {
            if (!is_array($row)) {
                return;
            }
            $row = medicap_cs_apply_spec_sampling_plan_row($row);
            $code = trim((string)($row['material_code'] ?? ''));
            $grn = trim((string)($row['grn_no'] ?? ''));
            $batch = trim((string)($row['batch_no'] ?? ''));
            $ar = trim((string)($row['ar_no'] ?? ''));
            if ($code === '') {
                return;
            }
            $key = strtolower($code.'|'.$grn.'|'.$batch.'|'.$ar);
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            if (medicap_cs_control_sample_entry_exists($conn, $code, $ar, $batch)) {
                return;
            }
            $spec = medicap_cs_resolve_spec_control_sample($conn, $row['testing_no'] ?? '', $code, $plantId);
            $sampleQty = trim((string)($row['sample_quantity'] ?? $row['control_sample'] ?? $spec['sample_quantity'] ?? ''));
            if ($sampleQty === '' || (float)$sampleQty <= 0) {
                return;
            }
            $row['sample_quantity'] = $sampleQty;
            $row['control_sample'] = $sampleQty;
            foreach (array('unit', 'sample_qty', 'additional_sample', 'totalsample_qty', 'sampling_plan', 'specification_no') as $field) {
                if (empty($row[$field]) && !empty($spec[$field])) {
                    $row[$field] = $spec[$field];
                }
            }
            if (empty($row['material_name']) && !empty($row['product_name'])) {
                $row['material_name'] = $row['product_name'];
            }
            $row['material_type'] = medicap_cs_map_material_type_label($row['material_type'] ?? '');
            if (empty($row['sampling_date']) && !empty($row['sampling_start_time'])) {
                $row['sampling_date'] = medicap_cs_format_date_field($row['sampling_start_time']);
            } elseif (!empty($row['sampling_date'])) {
                $row['sampling_date'] = medicap_cs_format_date_field($row['sampling_date']);
            }
            if (empty($row['release_date']) && !empty($row['approve_date'])) {
                $row['release_date'] = medicap_cs_format_date_field($row['approve_date']);
            } elseif (!empty($row['release_date'])) {
                $row['release_date'] = medicap_cs_format_date_field($row['release_date']);
            }
            if (empty($row['status'])) {
                $row['status'] = 'Pending Entry';
            }
            $output[] = $row;
        };

        $runPendingQuery = function ($sql) use ($conn, $append) {
            try {
                $res = $conn->query($sql);
            } catch (Throwable $e) {
                return;
            }
            if (!$res || $res->num_rows <= 0) {
                return;
            }
            while ($row = $res->fetch_assoc()) {
                $row['status'] = 'Pending Entry';
                $append($row);
            }
        };

        // Approved QC testing lines for materials that have approved spec with Sampling Plan (specification-logs)
        $sqlTesting = "SELECT t.id AS testing_id, t.testing_no, t.material_code, t.grn_no,
                t.approve_date AS release_date, t.approve_date,
                COALESCE(NULLIF(t.check_date,''), t.entry_date, '') AS analysis_date,
                COALESCE(NULLIF(s.start_time,''), NULLIF(s.end_time,''), s.entry_date, '') AS sampling_start_time,
                s.exp_date, s.mfg_date,
                COALESCE(NULLIF(s.sampledBy,''), s.entry_by, '') AS sample_by,
                COALESCE(NULLIF(s.batch_no,''), t.batch_no, '') AS batch_no,
                COALESCE(NULLIF(s.ar_no,''), t.ar_no, '') AS ar_no,
                m.material_type, m.material_subtype, m.material_name, m.grade,
                sp.control_sample, sp.sample_qty, sp.additional_sample, sp.totalsample_qty, sp.unit,
                sp.sampling_plan, sp.specification_no,
                sp.control_sample AS sample_quantity
            FROM testing t
            INNER JOIN ".$specSub." sp ON sp.material_code = t.material_code
            LEFT JOIN material m ON t.material_code = m.material_code
            LEFT JOIN sampling s ON s.grn_no = t.grn_no
            WHERE (
                LOWER(TRIM(COALESCE(t.status,''))) IN ('approved', 'approve')
                OR TRIM(COALESCE(t.status,'')) = 'Approved'
            )".$plantSqlT."
            ORDER BY t.id DESC
            LIMIT 1000";
        $runPendingQuery($sqlTesting);

        // Completed sampling for materials with approved spec Sampling Plan (before/alongside testing approval)
        $sqlSampling = "SELECT s.id AS sampling_id, s.material_code, s.grn_no,
                COALESCE(NULLIF(s.batch_no,''), '') AS batch_no,
                COALESCE(NULLIF(s.ar_no,''), '') AS ar_no,
                s.mfg_date, s.exp_date,
                COALESCE(NULLIF(s.sampledBy,''), s.entry_by, '') AS sample_by,
                COALESCE(NULLIF(s.start_time,''), NULLIF(s.end_time,''), s.entry_date, '') AS sampling_start_time,
                COALESCE(NULLIF(s.end_time,''), NULLIF(s.entry_date,''), NULLIF(s.start_time,'')) AS sampling_date,
                m.material_type, m.material_subtype, m.material_name, m.grade,
                sp.control_sample, sp.sample_qty, sp.additional_sample, sp.totalsample_qty, sp.unit,
                sp.sampling_plan, sp.specification_no,
                sp.control_sample AS sample_quantity
            FROM sampling s
            INNER JOIN ".$specSub." sp ON sp.material_code = s.material_code
            LEFT JOIN material m ON s.material_code = m.material_code
            WHERE LOWER(TRIM(COALESCE(s.status,''))) NOT IN ('pending', 'inprocess', 'allocate', 'allocated', 'rejected', 'reject')".$plantSqlS."
            ORDER BY s.id DESC
            LIMIT 1000";
        $runPendingQuery($sqlSampling);

        // Approved specs (specification-logs) + latest sampling line per material when not already listed
        $sqlSpecLines = "SELECT sp.material_code, sp.control_sample, sp.sample_qty, sp.additional_sample, sp.totalsample_qty,
                sp.unit, sp.sampling_plan, sp.specification_no, sp.spec_type,
                s.id AS sampling_id, s.grn_no,
                COALESCE(NULLIF(s.batch_no,''), '') AS batch_no,
                COALESCE(NULLIF(s.ar_no,''), '') AS ar_no,
                s.mfg_date, s.exp_date,
                COALESCE(NULLIF(s.sampledBy,''), s.entry_by, '') AS sample_by,
                COALESCE(NULLIF(s.start_time,''), NULLIF(s.end_time,''), s.entry_date, '') AS sampling_start_time,
                COALESCE(NULLIF(s.end_time,''), NULLIF(s.entry_date,''), NULLIF(s.start_time,'')) AS sampling_date,
                m.material_type, m.material_subtype, m.material_name, m.grade,
                sp.control_sample AS sample_quantity
            FROM ".$specSub." sp
            LEFT JOIN material m ON sp.material_code = m.material_code
            LEFT JOIN sampling s ON s.id = (
                SELECT MAX(s2.id) FROM sampling s2
                WHERE s2.material_code = sp.material_code
                AND LOWER(TRIM(COALESCE(s2.status,''))) NOT IN ('pending', 'inprocess', 'rejected', 'reject')
                ".str_replace('s.plant_id', 's2.plant_id', $plantSqlS)."
            )
            ORDER BY sp.id DESC
            LIMIT 500";
        $resSpecLines = @$conn->query($sqlSpecLines);
        if ($resSpecLines && $resSpecLines->num_rows > 0) {
            while ($row = $resSpecLines->fetch_assoc()) {
                if (empty($row['material_type'])) {
                    $st = trim((string)($row['spec_type'] ?? ''));
                    if (stripos($st, 'packing') !== false) {
                        $row['material_type'] = 'Packing Material';
                    } elseif (stripos($st, 'finish') !== false) {
                        $row['material_type'] = 'Finished Product';
                    } else {
                        $row['material_type'] = 'Raw Material';
                    }
                }
                $row['status'] = 'Pending Entry';
                $append($row);
            }
        }

        if (count($output) === 0 && $plantId !== '') {
            return medicap_get_pending_control_sample_entry_log($conn, '');
        }
        return $output;
    }
}

if (!function_exists('medicap_normalize_control_sample_material_type')) {
    function medicap_normalize_control_sample_material_type($materialType) {
        $t = trim((string)$materialType);
        if ($t === 'Finished Product') {
            return 'Finish Product';
        }
        return $t;
    }
}

if (!function_exists('medicap_save_or_update_control_sample')) {
    function medicap_save_or_update_control_sample($conn, $input, $get, $entryDate, $isUpdate) {
        medicap_ensure_control_sample_extra_columns($conn);
        if (!is_array($input)) {
            return array('status' => 'failed', 'msg' => 'Invalid request.');
        }
        $id = (int)($input['id'] ?? 0);
        if ($isUpdate && $id <= 0) {
            return array('status' => 'failed', 'msg' => 'Control sample record not found.');
        }
        $materialType = medicap_normalize_control_sample_material_type($input['material_type'] ?? '');
        $materialCode = trim((string)($input['material_code'] ?? $input['product_code'] ?? ''));
        if ($materialType === '' || $materialCode === '') {
            return array('status' => 'failed', 'msg' => 'Material type and code are required.');
        }
        $fields = array(
            'batch_no' => trim((string)($input['batch_no'] ?? '')),
            'batch_size' => trim((string)($input['batch_size'] ?? '')),
            'ar_no' => trim((string)($input['ar_no'] ?? '')),
            'analysis_date' => trim((string)($input['analysis_date'] ?? '')),
            'release_date' => trim((string)($input['release_date'] ?? '')),
            'sampling_date' => trim((string)($input['sampling_date'] ?? '')),
            'mfg_date' => trim((string)($input['mfg_date'] ?? '')),
            'exp_date' => trim((string)($input['exp_date'] ?? '')),
            'sample_quantity' => trim((string)($input['sample_quantity'] ?? '')),
            'actual_control_sample' => trim((string)($input['actual_control_sample'] ?? '')),
            'unit' => trim((string)($input['unit'] ?? '')),
            'sample_by' => trim((string)($input['sample_by'] ?? '')),
            'pack_no' => trim((string)($input['pack_no'] ?? '')),
            'rack_no' => trim((string)($input['rack_no'] ?? '')),
            'room_temp' => trim((string)($input['room_temp'] ?? '')),
            'humidity' => trim((string)($input['humidity'] ?? '')),
        );
        $plantEsc = $conn->real_escape_string(trim((string)($get['plant_id'] ?? '')));
        $empEsc = $conn->real_escape_string(trim((string)($get['emp_id'] ?? '')));
        $typeEsc = $conn->real_escape_string($materialType);
        $codeEsc = $conn->real_escape_string($materialCode);

        if ($isUpdate) {
            $sets = array();
            foreach ($fields as $col => $val) {
                $sets[] = $col."='".$conn->real_escape_string($val)."'";
            }
            $sets[] = "material_type='".$typeEsc."'";
            $sets[] = "material_code='".$codeEsc."'";
            $sets[] = "status='approve'";
            $sets[] = "entry_by='".$empEsc."'";
            $sets[] = "entry_date='".$conn->real_escape_string($entryDate)."'";
            $sql = "UPDATE control_sample SET ".implode(', ', $sets)." WHERE id='".$conn->real_escape_string((string)$id)."'";
        } else {
            $sql = "INSERT INTO control_sample (plant_id, material_type, material_code, batch_no, batch_size, ar_no, analysis_date, release_date,
                sampling_date, mfg_date, exp_date, sample_quantity, actual_control_sample, unit, sample_by, pack_no, rack_no, room_temp, humidity,
                status, entry_by, entry_date)
                VALUES ('".$plantEsc."', '".$typeEsc."', '".$codeEsc."',
                '".$conn->real_escape_string($fields['batch_no'])."', '".$conn->real_escape_string($fields['batch_size'])."',
                '".$conn->real_escape_string($fields['ar_no'])."', '".$conn->real_escape_string($fields['analysis_date'])."',
                '".$conn->real_escape_string($fields['release_date'])."', '".$conn->real_escape_string($fields['sampling_date'])."',
                '".$conn->real_escape_string($fields['mfg_date'])."', '".$conn->real_escape_string($fields['exp_date'])."',
                '".$conn->real_escape_string($fields['sample_quantity'])."', '".$conn->real_escape_string($fields['actual_control_sample'])."',
                '".$conn->real_escape_string($fields['unit'])."', '".$conn->real_escape_string($fields['sample_by'])."',
                '".$conn->real_escape_string($fields['pack_no'])."', '".$conn->real_escape_string($fields['rack_no'])."',
                '".$conn->real_escape_string($fields['room_temp'])."', '".$conn->real_escape_string($fields['humidity'])."',
                'approve', '".$empEsc."', '".$conn->real_escape_string($entryDate)."')";
        }
        if ($conn->query($sql)) {
            return array('status' => 'success');
        }
        return array('status' => $conn->error);
    }
}



// ini_set('display_errors', 1);
// error_reporting(E_ALL);




$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveRack") {
        $sql = "INSERT INTO controlsample_rack (user_no, rack_type, rack_no, subrack, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["material_type"]."', '".$input["rack_no"]."', '".json_encode($input["rackList"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingRacks") {
        $output = Array();
        $sql = "SELECT * FROM controlsample_rack WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subrack"] = json_decode($row["subrack"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "updateRack") {
        $sql = "UPDATE controlsample_rack SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "getRacksLog") {
        $output = Array();
        $sql = "SELECT * FROM controlsample_rack WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subrack"] = json_decode($row["subrack"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getSamplingEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE department = 'master'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["subrack"] = json_decode($row["subrack"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getProducts") {
        $output = Array();
        if ($_GET["material_type"] == "Raw Material") {
            $sql = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE plant_id='".$_GET["plant_id"]."' AND material_type='Raw Material' AND status='Approved'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        } else if ($_GET["material_type"] == "Packing Material") {
            $sql = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE plant_id='".$_GET["plant_id"]."' AND material_type='Packing Material' AND status='Approved'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        } else if ($_GET["material_type"] == "Finished Product") {
            $sql = "SELECT * FROM product WHERE plant_id='".$_GET["plant_id"]."' AND status='Approved'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "saveFinishControlSample") {
        $sql = "INSERT INTO control_sample (plant_id, material_type, material_code, batch_no, sampling_date, mfg_date, exp_date, sample_quantity, unit, 
        sample_by, pack_no, rack_no, room_temp, humidity, entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','Finish Product', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["sampling_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sample_quantity"]."', '".$input["unit"]."', '".$input["sample_by"]."', '".$input["pack_no"]."', '".$input["rack_no"]."', '".$input["room_temp"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    else if ($_GET["type"] == "MehasaveFinishControlSample") {
        
        $sql = "INSERT INTO control_sample (plant_id, material_type, material_code, batch_no,batch_size,mfg_date, exp_date,sample_quantity, unit, 
        sample_by, sampling_date, entry_by, entry_date,status) VALUES ('".$_GET["plant_id"]."','".$input["material_type"]."','".$input["material_code"]."',
        '".$input["batch_no"]."','".$input["batch_size"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."','".$input["sample_quantity"]."', 
        '".$input["unit"]."','".$_GET["emp_id"]."', '".$input["sampling_date"]."','".$_GET["emp_id"]."','$entry_date','pending')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "withdrawalControlSampleMeha") {
        
         $sql = "INSERT INTO `controlSampleWithdrawal`(`plant_id`, `material_code`, `batch_no`, `cs_id`, `withdeawalQty`,`unit`, `reasoneForWithdrawal`, `remark`, 
        `controlSampleGivenBy`, `controlSampleTakenBy`, `controlSampleReturnBy`, `entryBy`, `entryOn`) VALUES ('".$_GET["plant_id"]."','".$input["material_code"]."',
        '".$input["batch_no"]."','".$input["cs_id"]."','".$input["withdeawalQty"]."','".$input["unit"]."', '".$input["reasoneForWithdrawal"]."', '".$input["remark"]."',
        '".$input["controlSampleGivenBy"]."','".$input["controlSampleTakenBy"]."','".$input["controlSampleReturnBy"]."','".$_GET["emp_id"]."','$entry_date')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    
    else if ($_GET["type"] == "getwithdrawalLog") {
        $output = Array();
        $sql = "SELECT c.*,p.product_name FROM controlSampleWithdrawal c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE  c.plant_id = '".$_GET["plant_id"]."' order by c.id desc "; 

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getFinishControlSamples") {
        $output = Array();
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc "; 

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getCOntrolSampleForApproval") {
        $output = Array();
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.status='pending' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc "; 

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getApprovedControlSample") {
        $output = Array();
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.status = 'Approved' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc "; 

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT IFNULL(SUM(withdeawalQty), 0) AS withdeawalQty FROM controlSampleWithdrawal where plant_id = '".$_GET["plant_id"]."'  AND batch_no = '".$row["batch_no"]."' 
                 AND material_code = '".$row["material_code"]."'";   
        
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row['withdeawalQty'] = $row1['withdeawalQty'];
                    }
                }else{  $row['withdeawalQty'] = 0; }
                
                $row['avaliableQty'] =   +$row['sample_quantity'] -  +$row['withdeawalQty'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getControlSampleForVerification") {
        $output = Array();
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc "; 

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                   if (!empty($row['dateOfCharging']) && strtotime($row['dateOfCharging'])) {
                        $chargingDate = new DateTime($row['dateOfCharging']);
                        $today = new DateTime();
            
                        $intervals = [12, 24, 36, 48, 60];
                        foreach ($intervals as $months) {
                            $label = "{$months}Month";
                            $futureDate = clone $chargingDate;
                            $futureDate->modify("+{$months} months");
            
                            $remainingDays = $today->diff($futureDate)->format('%r%a');
            
                            // Add both date and remaining days
                            $row[$label . 'Date'] = $futureDate->format('Y-m-d');
                            $row[$label . 'RemainingDays'] = (int)$remainingDays;
                        }
                    } else {
                        // Default values if dateOfCharging is invalid
                        foreach ([12, 24, 36, 48, 60] as $months) {
                            $label = "{$months}Month";
                            $row[$label . 'Date'] = null;
                            $row[$label . 'RemainingDays'] = null;
                        }
                    }
                
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getControlSampleForDestruction") {
        $output = Array();
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc "; 

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT IFNULL(SUM(withdeawalQty), 0) AS withdeawalQty FROM controlSampleWithdrawal where plant_id = '".$_GET["plant_id"]."'  AND batch_no = '".$row["batch_no"]."' 
                AND material_code = '".$row["material_code"]."'";   
        
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row['withdeawalQty'] = $row1['withdeawalQty'];
                    }
                }else{  $row['withdeawalQty'] = 0; }
                
                $row['avaliableQty'] =   +$row['sample_quantity'] -  +$row['withdeawalQty'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveDestruction") {
        
        $sql = "UPDATE control_sample SET status = 'Destroyed ', qtyDistroy = '".$input["qtyDistroy"]."', distroyRemark = '".$input["distroyRemark"]."', 
        distroyBy = '".$input["distroyBy"]."', distroyDate = '".$input["distroyDate"]."' WHERE id = '".$input["id"]."' ";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "getQaEmployees") {
        $output = Array();
        $sql = "SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS empName , emp_id FROM employee where 
        plant_id = '".$_GET["plant_id"]."'  order by firstname ASC ";  // AND department = 'Quality Assurance'

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 


    else if ($_GET["type"] == "getPendingFinishControlSamples") {
        $output = Array();
         $sql = "SELECT c.*, p.dosage_form, p.product_name, p.grade FROM control_sample c LEFT JOIN product p 
        ON c.material_code=p.product_code WHERE c.material_type='Finish Product' AND c.status='pending'  AND c.plant_id = '".$_GET["plant_id"]."'"; //GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 

    
    
    
    else if ($_GET["type"] == "saveControlSampleVerification") {
        
        $sql = "UPDATE `control_sample` SET ";
      
        if($input["verificationFor"] == 'Initial'){
            $sql .= "`initialAppearance`= '".$input["appearance"]."', `initialOder`= '".$input["oder"]."', `initialVerificationBy`= '".$input["verifyBy"]."', 
            `initialVerificationOn`= '".$input["verificationDate"]."' WHERE id = '".$input["id"]."' ";
        }
        else if($input["verificationFor"] == '12Months'){
            $sql .= " `12MonAppearance`= '".$input["appearance"]."', `12MonOder`= '".$input["oder"]."', `12MonVerificationBy`= '".$input["verifyBy"]."', 
            `12MonVerificationOn`= '".$input["verificationDate"]."' WHERE id = '".$input["id"]."' ";
        }
        else if($input["verificationFor"] == '24Months'){
            $sql .= "`24MonAppearance`= '".$input["appearance"]."', `24MonOder`= '".$input["oder"]."', `24MonVerificationBy`= '".$input["verifyBy"]."', 
            `24MonVerificationOn`= '".$input["verificationDate"]."' WHERE id = '".$input["id"]."' ";
        }
        else if($input["verificationFor"] == '36Months'){
            $sql .= "`36MonAppearance`= '".$input["appearance"]."', `36MonOder`= '".$input["oder"]."', `36MonVerificationBy`= '".$input["verifyBy"]."', 
            `36MonVerificationOn`= '".$input["verificationDate"]."' WHERE id = '".$input["id"]."' ";
        }
        else if($input["verificationFor"] == '48Months'){
            $sql .= "`48MonAppearance`= '".$input["appearance"]."', `48MonOder`= '".$input["oder"]."', `48MonVerificationBy`= '".$input["verifyBy"]."', 
            `48MonVerificationOn`= '".$input["verificationDate"]."' WHERE id = '".$input["id"]."' ";
        }
        else if($input["verificationFor"] == '60Months'){
            $sql .= "`60MonAppearance`= '".$input["appearance"]."', `60MonOder`= '".$input["oder"]."', `60MonVerificationBy`= '".$input["verifyBy"]."', 
            `60MonVerificationOn`= '".$input["verificationDate"]."' WHERE id = '".$input["id"]."' ";
        }
         
        
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            
            echo "{\"status\":\"".$conn->error."\"}";
            
        }
        
        
    } 
    else if ($_GET["type"] == "saveRawControlSample") {
        
         $sql = "INSERT INTO control_sample (plant_id, material_type, material_code, batch_no, ar_no, analysis_date, release_date, sampling_date, mfg_date, exp_date,
        sample_quantity, unit, sample_by, pack_no, rack_no, room_temp, humidity, entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','Raw Material', 
        '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["ar_no"]."', '".$input["analysis_date"]."', '".$input["release_date"]."',
        '".$input["sampling_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sample_quantity"]."', '".$input["unit"]."',
        '".$input["sample_by"]."', '".$input["pack_no"]."', '".$input["rack_no"]."', '".$input["room_temp"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."',
        '$entry_date')";
        
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
            
        } else {
            
            echo "{\"status\":\"".$conn->error."\"}";
            
        }
        
        
    } 
    
    else if ($_GET["type"] == "savePackingControlSample") {
        $sql = "INSERT INTO control_sample (plant_id, material_type, material_code, batch_no, ar_no, analysis_date, release_date, sampling_date, mfg_date, exp_date, 
        sample_quantity, unit, sample_by, pack_no, rack_no, room_temp, humidity, entry_by, entry_date) VALUES ('".$_GET["plant_id"]."','Packing Material', 
        '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["ar_no"]."', '".$input["analysis_date"]."', '".$input["release_date"]."', 
        '".$input["sampling_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["sample_quantity"]."', '".$input["unit"]."',
        '".$input["sample_by"]."', '".$input["pack_no"]."', '".$input["rack_no"]."', '".$input["room_temp"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', 
        '$entry_date')";
        
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingRawControlSamples") {
        $output = Array();
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM control_sample c 
        LEFT JOIN material m ON c.material_code=m.material_code WHERE  c.material_type='Raw Material' AND c.status='pending' AND c.plant_id = '".$_GET["plant_id"]."' ";//GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingPackingControlSamples") {
        $output = Array();
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM control_sample 
        c LEFT JOIN material m ON c.material_code=m.material_code WHERE  c.plant_id = '".$_GET["plant_id"]."'
        AND c.material_type='Packing Material' AND c.status='pending' ";//GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "approveControlSample") {
        $sql = "UPDATE control_sample SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "mehaApproveControlSample") {
        
        $sql = "UPDATE control_sample SET dateOfCharging = '".$input["dateOfCharging"]."', dueDateForDistruction = '".$input["dueDateForDistruction"]."' ,
        status = '".$input["status"]."', approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date' WHERE id = '".$input["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    
    else if ($_GET["type"] == "getRawControlSamples") {
         $output = Array();
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM control_sample c 
        LEFT JOIN material m ON c.material_code=m.material_code WHERE  c.plant_id = '".$_GET["plant_id"]."'
        AND c.material_type='Raw Material' ";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPackingControlSamples") {
        $output = Array();
        $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM control_sample c 
        LEFT JOIN material m ON c.material_code=m.material_code WHERE  c.plant_id = '".$_GET["plant_id"]."'
        AND c.material_type='Packing Material' ";
        //AND m.material_subtype LIKE '%".$_GET["material_type"]."%' ";
        //AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    
    else if ($_GET["type"] == "getAvailableSamples") {
        $output = Array();
        $sql = "SELECT c.*, p.dosage_form, p.product_name, p.grade FROM control_sample c LEFT JOIN product p ON c.material_code=p.product_code WHERE c.user_no='".$_GET["user_no"]."' AND c.material_type='Finish Product'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["avl_qty"] = 0;
                $row["withdrawal_qty"] = 0;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "downloadRawControlSamples") {
        // require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Raw Material Control Sample Register'; $_GET['pdftype'] ='onlyheader'; include("../pdfimp2.php");
         $html.='
        
             <h3 style="text-align:center">Raw Material Control Sample Register</h3>  
            <table cellpadding="5" border="1">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%; text-align:centre;"><b>Sr</b></td>
                <td style="width:25%; text-align:centre;"><b>Material Name</b></td>
                <td style="width:20%; text-align:centre;"><b>Material Type</b></td>
                <td style="width:20%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:15%; text-align:centre;"><b>Batch No </b></td>
                <td style="width:15%; text-align:centre;"><b>Status	 </b></td>
                
            </tr>'; 
            $i=1;
           $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM control_sample c LEFT JOIN material m ON 
        c.material_code=m.material_code WHERE c.user_no='".$_GET["user_no"]."'
        AND c.material_type='Raw Material' ";
        //AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' 
        //AND '".$_GET["to_date"]."' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
              
            
        
                     $html.='<tr>
                    <td style="width:5%;text-align:center">'.$i.'</td>
                    <td style="width:25%;text-align:center">'.$row['material_name'].'</td>
                    
                    <td style="width:20%;text-align:center">'.$row['material_subtype'].'</td>
                    <td style="width:20%;text-align:center">'.$row['material_code'].'</td>
                    <td style="width:15%;text-align:center">'.$row['batch_no'].'</td>
                    <td style="width:15%;text-align:center">'.$row['status'].'</td>
                    
                </tr>';
                 $i++;
                }
            }
        
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('NCR.pdf', 'I');
      
    }
   
    else if ($_GET["type"] == "downloadPackingControlSamples") {
        // require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Packing Material Control Sample Register'; $_GET['pdftype'] ='onlyheader'; 
        include("../pdfimp2.php");
          $html.='
          <h3 style="text-align:center">Packing Material Control Sample Register</h3>
         <table cellpadding="5" border="1">
           <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%; text-align:centre;"><b>Sr</b></td>
                <td style="width:25%; text-align:centre;"><b>Material name</b></td>
                <td style="width:20%; text-align:centre;"><b>Material type</b></td>
                <td style="width:20%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:15%; text-align:centre;"><b>Batch No </b></td>
                <td style="width:15%; text-align:centre;"><b>Status</b></td>
            </tr>'; 
            $i=1;
       $sql = "SELECT c.*, m.material_subtype, m.material_name, m.grade FROM control_sample c 
        LEFT JOIN material m ON c.material_code=m.material_code WHERE c.user_no='".$_GET["user_no"]."' 
        AND c.material_type='Packing Material' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                    <tr>
                         <td style="width:5%; text-align:centre;">'.$i.'</td>
                          <td style="width:25%; text-align:centre;">'.$row["material_name"].'</td>
                        <td style="width:20%; text-align:centre;">'.$row["material_type"].'</td>
                        <td style="width:20%; text-align:centre;">'.$row["material_code"].'</td>
                        <td style="width:15%; text-align:centre;">'.$row["batch_no"].'</td>
                        <td style="width:15%; text-align:centre;">'.$row["status"].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material Control Sample Register.pdf', 'I');
  
    }
    else if ($_GET["type"] == "downloadFinishControlSamples") {
        // require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Finish Product Control Sample Register'; $_GET['pdftype'] ='onlyheader';
        include("../pdfimp2.php");
        $html ='
        <h3 style="text-align:center">Finish Product Control Sample Register</h3>
        <table cellpadding="5" border="1">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 10%; text-align:centre;"><b>Sr.</b></td>
                        <td style="width: 40%; text-align:centre;"><b>Product Name</b></td>
                        <td style="width: 20%; text-align:centre;"><b>Batch No.</b></td>
                        <td style="width: 30%; text-align:centre;"><b>status</b></td>
                    </tr>';
                    $i=1;
        $sql = "SELECT c.*, p.dosage_form, p.product_name, p.grade FROM control_sample c LEFT JOIN product p 
        ON c.material_code=p.product_code WHERE c.material_type='Finish Product' order by 1 desc ";
        //AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                        <td style=" width: 10%;text-align:centre;">'.$i.'</td>
                        <td style=" width: 40%;text-align:centre;">'.$row["product_name"].'</td>
                        <td style=" width: 20%;text-align:centre;">'.$row["batch_no"].'</td>
                        <td style=" width: 30%;text-align:centre;">'.$row["status"].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Finish Product Control Sample Register.pdf', 'I');
    } else if ($_GET["type"] == "getControlSamples") {
        $output = Array();
        $sql = "SELECT * FROM control_sample WHERE plant_id = '".$_GET["plant_id"]."'AND  material_type = '".$_GET["material_type"]."' AND  status='approve'";
        $result = $conn->query($sql);
      
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["material_type"] == "Raw Material") {
                    $sql1 = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE material_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"] ?? '';
                         }
                    }
                } else if ($row["material_type"] == "Packing Material") {
                    $sql1 = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE material_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"] ?? '';
                         }
                    }
                } else if ($row["material_type"] == "Finished Product") {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"] ?? '';
                         }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "review_log") {
        $_GET['filename'] = 'expired sample log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='<table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample ID</b></td>
                <td style="width:15%; text-align:centre;"><b>Name of Product</b></td>
                <td style="width:10%; text-align:centre;"><b>Batch No</b></td>
                <td style="width:15%; text-align:centre;"><b>MFG Date</b></td>
                <td style="width:10%; text-align:centre;"><b>EXP Date</b></td>
                <td style="width:10%; text-align:centre;"><b>Sample Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Avl Qty</b></td>
                <td style="width:10%; text-align:centre;"><b>Rack No</b></td>
            </tr>';
            $i=1;
        $sql = "SELECT * FROM control_sample WHERE  status='approve'";
    $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
                <td style="width:10%;">'.$row['cs_id'].'</td>
                <td style="width:15%;">'.$row['product_name'].'</td>
                <td style="width:10%;">'.$row['batch_no'].'</td>
                <td style="width:15%;">'.date('m-Y',strtotime($row['mfg_date'])).'</td>
                <td style="width:10%;">'.date('m-Y',strtotime($row['exp_date'])).'</td>
                <td style="width:10%;">'.$row['sample_quantity'].'</td>
                <td style="width:10%;">'.$row['sample_quantity'].'</td>
                <td style="width:10%;">'.$row['rack_no'].'</td>
            </tr>';
            $i++;
        }
    }
        $html.='</table>';
        
        
        
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('expiredsamplelog.pdf', 'I');
    }
    
    
    
    else if ($_GET["type"] == "saveControlSampleReview") {
        
         $sql = "INSERT INTO controlsample_review (user_no, no, reason, description, remark, over_printing,color_change, observation, entry_by, entry_date,plant_id) 
        VALUES ('".$_GET["user_no"]."','".$_GET["id"]."', '".$input["reason"]."', '".$input["description"]."', '".$input["remark"]."',
        '".$input["over_printing"]."', '".$input["color_change"]."', '".$input["observation"]."', '".$_GET["emp_id"]."', '$entry_date',
        '".$_GET["plant_id"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } 
    
    else if ($_GET["type"] == "getReviewsLog") {
        $output = array();
        $sql = "SELECT r.*, c.* FROM controlsample_review r LEFT JOIN control_sample c ON r.no=c.id 
        WHERE r.plant_id='".$_GET["plant_id"]."' AND c.material_type='".$_GET["material_type"]."'"; //GROUP BY r.id ORDER BY r.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["material_type"] == "Raw Material") {
                    $sql1 = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE material_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
                         }
                    }
                } else if ($row["material_type"] == "Packing Material") {
                    $sql1 = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE material_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
                         }
                    }
                } else if ($row["material_type"] == "Finish Product") {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["product_name"];
                         }
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingControlSampleEntryLog") {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $rows = medicap_get_pending_control_sample_entry_log(
                $conn,
                medicap_cs_normalize_plant_id($_GET['plant_id'] ?? '')
            );
            echo json_encode(is_array($rows) ? $rows : array());
        } catch (Throwable $e) {
            echo json_encode(array());
        }
    } else if ($_GET["type"] == "saveControlSample") {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_save_or_update_control_sample($conn, $input, $_GET, $entry_date, false));
    } else if ($_GET["type"] == "updateControlSample") {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_save_or_update_control_sample($conn, $input, $_GET, $entry_date, true));
    }

}

$conn->close();
?>