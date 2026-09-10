<?php




//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);





    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = array();
    }
    
    function vesc($conn, $value) {
        return $conn->real_escape_string((string)$value);
    }

    function vol_json_array($raw) {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            return array();
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : array();
    }

    function vol_resolve_chemical_code($conn, $plantId, $item) {
        $code = trim((string)(isset($item['material_code']) ? $item['material_code'] : ''));
        if ($code !== '') {
            return $code;
        }
        $name = trim((string)(isset($item['chemical_name']) ? $item['chemical_name'] : ''));
        if ($name === '') {
            return '';
        }
        $nameEsc = vesc($conn, $name);
        $plantEsc = vesc($conn, $plantId);
        $sqlList = array(
            "SELECT chemical_no AS material_code FROM chemical WHERE plant_id='".$plantEsc."' AND chemical_name='".$nameEsc."' LIMIT 1",
            "SELECT material_code FROM others_material WHERE plant_id='".$plantEsc."' AND material_subtype='Chemicals' AND material_name='".$nameEsc."' LIMIT 1",
        );
        foreach ($sqlList as $sql) {
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                if (!empty($row['material_code'])) {
                    return trim($row['material_code']);
                }
            }
        }
        return '';
    }

    function vol_resolve_reagent_code($conn, $plantId, $item) {
        $code = trim((string)(isset($item['material_code']) ? $item['material_code'] : (isset($item['indicator_no']) ? $item['indicator_no'] : '')));
        if ($code !== '') {
            return $code;
        }
        $name = trim((string)(isset($item['reagent']) ? $item['reagent'] : (isset($item['material_name']) ? $item['material_name'] : '')));
        if ($name === '') {
            return '';
        }
        $nameEsc = vesc($conn, $name);
        $plantEsc = vesc($conn, $plantId);
        $sqlList = array(
            "SELECT indicator_no AS material_code FROM indicator WHERE plant_id='".$plantEsc."' AND LOWER(TRIM(indicator))=LOWER('".$nameEsc."') LIMIT 1",
            "SELECT material_code FROM others_material WHERE plant_id='".$plantEsc."' AND material_subtype IN ('Reagents','Reagent','Chemicals') AND LOWER(TRIM(material_name))=LOWER('".$nameEsc."') LIMIT 1",
            "SELECT chemical_no AS material_code FROM chemical WHERE plant_id='".$plantEsc."' AND LOWER(TRIM(chemical_name))=LOWER('".$nameEsc."') LIMIT 1",
            "SELECT material_code FROM my_view WHERE LOWER(TRIM(material_name))=LOWER('".$nameEsc."') AND (material_subtype IN ('Reagents','Reagent','Chemicals') OR material_type IN ('QC Materials','Raw Material')) LIMIT 1",
            "SELECT e.material_code FROM engi_stock e
                LEFT JOIN my_view m ON e.material_code=m.material_code
                LEFT JOIN others_material om ON e.material_code=om.material_code
                WHERE e.plant_id='".$plantEsc."'
                AND (LOWER(TRIM(IFNULL(m.material_name,'')))=LOWER('".$nameEsc."')
                     OR LOWER(TRIM(IFNULL(om.material_name,'')))=LOWER('".$nameEsc."'))
                LIMIT 1",
            "SELECT sb.material_code FROM sampling_batches sb
                LEFT JOIN my_view m ON sb.material_code=m.material_code
                LEFT JOIN others_material om ON sb.material_code=om.material_code
                WHERE sb.plant_id='".$plantEsc."'
                AND (LOWER(TRIM(IFNULL(m.material_name,'')))=LOWER('".$nameEsc."')
                     OR LOWER(TRIM(IFNULL(om.material_name,'')))=LOWER('".$nameEsc."'))
                ORDER BY sb.id DESC LIMIT 1",
        );
        foreach ($sqlList as $sql) {
            $res = @$conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                if (!empty($row['material_code'])) {
                    return trim($row['material_code']);
                }
            }
        }
        return '';
    }

    function vol_normalize_stock_row($row) {
        if (!is_array($row)) {
            return $row;
        }
        $batch = trim((string)(isset($row['batch_no']) ? $row['batch_no'] : ''));
        if ($batch === '') {
            $batch = trim((string)(isset($row['ar_no']) ? $row['ar_no'] : ''));
        }
        if ($batch === '') {
            $batch = trim((string)(isset($row['medicap_lot_no']) ? $row['medicap_lot_no'] : ''));
        }
        $row['batch_no'] = $batch;
        return $row;
    }

    function vol_enrich_preparation_log_row($conn, &$row) {
        $row['details'] = vol_json_array(isset($row['details']) ? $row['details'] : '[]');
        $chemicals = vol_json_array(isset($row['chemicals']) ? $row['chemicals'] : '[]');
        $reagents = vol_json_array(isset($row['Reagent']) ? $row['Reagent'] : '[]');

        if (empty(trim((string)(isset($row['material_code']) ? $row['material_code'] : '')))) {
            foreach (array_merge($chemicals, $reagents) as $item) {
                if (!empty($item['material_code'])) {
                    $row['material_code'] = trim((string)$item['material_code']);
                    break;
                }
            }
        }
        if (empty(trim((string)(isset($row['material_code']) ? $row['material_code'] : '')))) {
            $row['material_code'] = trim((string)(isset($row['solution_no']) ? $row['solution_no'] : ''));
        }

        if (empty(trim((string)(isset($row['batch_no']) ? $row['batch_no'] : '')))) {
            foreach (array_merge($chemicals, $reagents) as $item) {
                $batch = trim((string)(isset($item['batch_no']) ? $item['batch_no'] : (isset($item['ar_no']) ? $item['ar_no'] : '')));
                if ($batch !== '') {
                    $row['batch_no'] = $batch;
                    break;
                }
            }
        }
        if (empty(trim((string)(isset($row['batch_no']) ? $row['batch_no'] : '')))) {
            $row['batch_no'] = trim((string)(isset($row['rp_no']) ? $row['rp_no'] : ''));
        }

        if (empty(trim((string)(isset($row['make']) ? $row['make'] : '')))) {
            $row['make'] = trim((string)(isset($row['solution_name']) ? $row['solution_name'] : ''));
        }
        if (empty(trim((string)(isset($row['make']) ? $row['make'] : ''))) && !empty($chemicals[0]['chemical_name'])) {
            $row['make'] = trim((string)$chemicals[0]['chemical_name']);
        }

        $stdDetails = vol_json_array(isset($row['standard_details']) ? $row['standard_details'] : '[]');
        if (empty(trim((string)(isset($row['primary_standard']) ? $row['primary_standard'] : ''))) && is_array($stdDetails)) {
            $row['primary_standard'] = trim((string)(isset($stdDetails['primary_standard']) ? $stdDetails['primary_standard'] : (isset($stdDetails[0]['primary_standard']) ? $stdDetails[0]['primary_standard'] : '')));
        }
        if (empty(trim((string)(isset($row['secondary_standard']) ? $row['secondary_standard'] : ''))) && is_array($stdDetails)) {
            $row['secondary_standard'] = trim((string)(isset($stdDetails['secondary_standard']) ? $stdDetails['secondary_standard'] : (isset($stdDetails[0]['secondary_standard']) ? $stdDetails[0]['secondary_standard'] : '')));
        }

        return $row;
    }

    function vol_is_approved_preparation_row($row) {
        $status = strtolower(trim((string)(isset($row['status']) ? $row['status'] : '')));
        if (in_array($status, array('approved', 'approve'), true)) {
            return true;
        }
        $approveBy = trim((string)(isset($row['approve_by']) ? $row['approve_by'] : ''));
        if ($approveBy !== '') {
            return true;
        }
        $std = strtolower(trim((string)(isset($row['standardization']) ? $row['standardization'] : '')));
        return ($std === 'done');
    }

    function vol_attach_solution_meta($conn, &$row) {
        $solNo = vesc($conn, isset($row['solution_no']) ? $row['solution_no'] : '');
        if ($solNo === '') {
            return;
        }
        $solRes = @$conn->query("SELECT solution_name, strength FROM volumetric_solution WHERE solution_no='".$solNo."' LIMIT 1");
        if ($solRes && $solRes->num_rows > 0) {
            $sol = $solRes->fetch_assoc();
            $row['solution_name'] = isset($sol['solution_name']) ? $sol['solution_name'] : '';
            $row['sol_strength'] = isset($sol['strength']) ? $sol['strength'] : '';
        }
    }

    function vol_fetch_solution_log_rows($conn, $plantId) {
        $output = array();
        $plantEsc = vesc($conn, $plantId);
        $plantSql = ($plantEsc !== '') ? " WHERE plant_id='".$plantEsc."'" : '';
        $sql = "SELECT * FROM volumetric_preparation".$plantSql." ORDER BY id DESC";
        $result = @$conn->query($sql);
        if (!$result) {
            return $output;
        }
        while ($row = $result->fetch_assoc()) {
            if (!vol_is_approved_preparation_row($row)) {
                continue;
            }
            vol_attach_solution_meta($conn, $row);
            vol_enrich_preparation_log_row($conn, $row);
            $output[] = $row;
        }
        return $output;
    }

    function vol_fetch_stock_data($conn, $plantId, $materialCode) {
        $materialCode = trim((string)$materialCode);
        if ($materialCode === '') {
            return array();
        }
        $plantEsc = vesc($conn, $plantId);
        $codeEsc = vesc($conn, $materialCode);
        $merged = array();
        $seen = array();
        $queries = array(
            "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty, unit, '' AS ar_no FROM engi_stock WHERE material_code='".$codeEsc."' AND plant_id='".$plantEsc."'",
            "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty, unit, IFNULL(ar_no,'') AS ar_no FROM stock_book WHERE material_code='".$codeEsc."' AND plant_id='".$plantEsc."'",
            "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty_received AS qty, unit, IFNULL(ar_no,'') AS ar_no FROM sampling_batches WHERE material_code='".$codeEsc."' AND plant_id='".$plantEsc."'",
        );
        // Fallback without plant filter (legacy rows)
        $fallbackQueries = array(
            "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty, unit, '' AS ar_no FROM engi_stock WHERE material_code='".$codeEsc."'",
            "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty, unit, IFNULL(ar_no,'') AS ar_no FROM stock_book WHERE material_code='".$codeEsc."'",
            "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty_received AS qty, unit, IFNULL(ar_no,'') AS ar_no FROM sampling_batches WHERE material_code='".$codeEsc."'",
        );

        $runQueries = function ($list) use ($conn, &$merged, &$seen) {
            foreach ($list as $sql) {
                $result = @$conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row = vol_normalize_stock_row($row);
                        if ($row['batch_no'] === '') {
                            continue;
                        }
                        $key = $row['batch_no'].'|'.(isset($row['grn_no']) ? $row['grn_no'] : '');
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $merged[] = $row;
                        }
                    }
                }
            }
        };

        $runQueries($queries);
        if (count($merged) === 0) {
            $runQueries($fallbackQueries);
        }
        return $merged;
    }

    function vol_fetch_engi_stock_by_name($conn, $plantId, $reagentName) {
        $reagentName = trim((string)$reagentName);
        if ($reagentName === '') {
            return array();
        }
        $plantEsc = vesc($conn, $plantId);
        $nameEsc = vesc($conn, $reagentName);
        $sql = "SELECT e.material_code, e.grn_no, e.batch_no, e.mfg_date, e.exp_date, e.qty, e.unit, '' AS ar_no
            FROM engi_stock e
            LEFT JOIN my_view m ON e.material_code = m.material_code
            LEFT JOIN others_material om ON e.material_code = om.material_code AND om.plant_id = e.plant_id
            WHERE e.plant_id='".$plantEsc."'
            AND (
                LOWER(TRIM(IFNULL(m.material_name,''))) = LOWER('".$nameEsc."')
                OR LOWER(TRIM(IFNULL(om.material_name,''))) = LOWER('".$nameEsc."')
            )
            ORDER BY e.id DESC";
        $merged = array();
        $seen = array();
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = vol_normalize_stock_row($row);
                if ($row['batch_no'] === '') {
                    continue;
                }
                $key = $row['batch_no'].'|'.(isset($row['grn_no']) ? $row['grn_no'] : '');
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $merged[] = $row;
                }
            }
        }
        return $merged;
    }

    function vol_merge_stock_rows($primary, $secondary) {
        $merged = array();
        $seen = array();
        foreach (array($primary, $secondary) as $list) {
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $row = vol_normalize_stock_row($row);
                if ($row['batch_no'] === '') {
                    continue;
                }
                $key = $row['batch_no'].'|'.(isset($row['grn_no']) ? $row['grn_no'] : '');
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $merged[] = $row;
                }
            }
        }
        return $merged;
    }

    function vol_fetch_reagent_stock_from_receiving($conn, $plantId, $materialCode, $reagentName) {
        $plantEsc = vesc($conn, $plantId);
        $materialCode = trim((string)$materialCode);
        $reagentName = trim((string)$reagentName);
        $matchParts = array();
        if ($materialCode !== '') {
            $codeEsc = vesc($conn, $materialCode);
            $matchParts[] = "sb.material_code='".$codeEsc."'";
            $matchParts[] = "cm.material_code='".$codeEsc."'";
        }
        if ($reagentName !== '') {
            $nameEsc = vesc($conn, $reagentName);
            $matchParts[] = "LOWER(TRIM(IFNULL(m.material_name,'')))=LOWER('".$nameEsc."')";
            $matchParts[] = "LOWER(TRIM(IFNULL(om.material_name,'')))=LOWER('".$nameEsc."')";
        }
        if (count($matchParts) === 0) {
            return array();
        }

        $sql = "SELECT sb.material_code,
                COALESCE(NULLIF(sb.grn_no,''), NULLIF(cm.grn_no,''), NULLIF(cm.receiving_no,'')) AS grn_no,
                sb.batch_no, sb.mfg_date, sb.exp_date, sb.qty_received AS qty, sb.unit,
                IFNULL(sb.ar_no,'') AS ar_no,
                cm.receiving_no, cm.challan_no,
                COALESCE(NULLIF(m.material_name,''), NULLIF(om.material_name,'')) AS material_name
            FROM sampling_batches sb
            LEFT JOIN challan_materials cm ON cm.material_code = sb.material_code AND cm.challan_no = sb.challan_no
            LEFT JOIN challan c1 ON c1.challan_no = cm.challan_no
            LEFT JOIN my_view m ON sb.material_code = m.material_code
            LEFT JOIN others_material om ON sb.material_code = om.material_code
            WHERE sb.plant_id='".$plantEsc."'
            AND (".implode(' OR ', $matchParts).")
            ORDER BY sb.id DESC";

        $merged = array();
        $seen = array();
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = vol_normalize_stock_row($row);
                if ($row['batch_no'] === '') {
                    continue;
                }
                $key = $row['batch_no'].'|'.(isset($row['grn_no']) ? $row['grn_no'] : '');
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $merged[] = $row;
                }
            }
        }
        return $merged;
    }

    function vol_fetch_reagent_stock_from_engi_receiving($conn, $plantId, $materialCode, $reagentName) {
        // Same Labeling Details source as store/raw.php getLabelingBatchesForMaterial.
        $plantEsc = vesc($conn, $plantId);
        $materialCode = trim((string)$materialCode);
        $reagentName = trim((string)$reagentName);
        $merged = array();
        $seen = array();

        $matchParts = array();
        if ($materialCode !== '') {
            $codeEsc = vesc($conn, $materialCode);
            $matchParts[] = "c.material_code='".$codeEsc."'";
        }
        if ($reagentName !== '') {
            $nameEsc = vesc($conn, $reagentName);
            $matchParts[] = "LOWER(TRIM(COALESCE(m.material_name, om.material_name, ch.chemical_name,'')))=LOWER('".$nameEsc."')";
            $matchParts[] = "LOWER(TRIM(COALESCE(m.material_name, om.material_name, ch.chemical_name,''))) LIKE CONCAT('%', LOWER('".$nameEsc."'), '%')";
        }
        if (count($matchParts) === 0) {
            return $merged;
        }

        $sql = "SELECT c.material_code, c.challan_no, c1.ch_no,
                COALESCE(m.material_name, om.material_name, ch.chemical_name) AS material_name
            FROM challan_materials c
            LEFT JOIN challan c1 ON c.challan_no = c1.challan_no
            LEFT JOIN my_view m ON c.material_code = m.material_code
            LEFT JOIN others_material om ON c.material_code = om.material_code
            LEFT JOIN chemical ch ON c.material_code = ch.chemical_no
            WHERE c1.plant_id='".$plantEsc."'
            AND c.status != 'pending'
            AND c.receiving = 'approve'
            AND (".implode(' OR ', $matchParts).")
            ORDER BY c1.id DESC";

        $pushBatchRow = function ($batch) use (&$merged, &$seen) {
            if (!is_array($batch)) {
                return;
            }
            $batch = vol_normalize_stock_row($batch);
            if ($batch['batch_no'] === '' || $batch['batch_no'] === '#Autogenerated') {
                return;
            }
            $key = $batch['batch_no'].'|'.(isset($batch['challan_no']) ? $batch['challan_no'] : '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                if (!isset($batch['qty']) && isset($batch['qty_received'])) {
                    $batch['qty'] = $batch['qty_received'];
                }
                $merged[] = $batch;
            }
        };

        $fetchBatches = function ($codeEsc, $challanEsc, $chNoEsc) use ($conn, $plantEsc, $pushBatchRow) {
            $queries = array(
                "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty_received, qty_received AS qty, unit, challan_no, IFNULL(ar_no,'') AS ar_no
                    FROM sampling_batches
                    WHERE material_code='".$codeEsc."' AND challan_no='".$challanEsc."'
                    AND (plant_id='".$plantEsc."' OR IFNULL(plant_id,'')='')",
                "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty_received, qty_received AS qty, unit, challan_no, IFNULL(ar_no,'') AS ar_no
                    FROM sampling_batches
                    WHERE material_code='".$codeEsc."' AND challan_no='".$challanEsc."'",
            );
            if ($chNoEsc !== '' && $chNoEsc !== $challanEsc) {
                $queries[] = "SELECT material_code, grn_no, batch_no, mfg_date, exp_date, qty_received, qty_received AS qty, unit, challan_no, IFNULL(ar_no,'') AS ar_no
                    FROM sampling_batches
                    WHERE material_code='".$codeEsc."' AND (ch_no='".$chNoEsc."' OR challan_no='".$chNoEsc."')";
            }
            foreach ($queries as $batchSql) {
                $batchResult = @$conn->query($batchSql);
                if ($batchResult && $batchResult->num_rows > 0) {
                    while ($batch = $batchResult->fetch_assoc()) {
                        $pushBatchRow($batch);
                    }
                    return true;
                }
            }
            return false;
        };

        $recvResult = @$conn->query($sql);
        if ($recvResult && $recvResult->num_rows > 0) {
            while ($recv = $recvResult->fetch_assoc()) {
                $codeEsc = vesc($conn, isset($recv['material_code']) ? $recv['material_code'] : '');
                $challanEsc = vesc($conn, isset($recv['challan_no']) ? $recv['challan_no'] : '');
                $chNoEsc = vesc($conn, isset($recv['ch_no']) ? $recv['ch_no'] : '');
                if ($codeEsc === '' || $challanEsc === '') {
                    continue;
                }
                $fetchBatches($codeEsc, $challanEsc, $chNoEsc);
            }
        }

        if (count($merged) === 0 && $materialCode !== '') {
            $values = vol_fetch_stock_data($conn, $plantId, $materialCode);
            foreach ($values as $row) {
                $pushBatchRow($row);
            }
        }
        if (count($merged) === 0 && $reagentName !== '') {
            $values = vol_fetch_engi_stock_by_name($conn, $plantId, $reagentName);
            foreach ($values as $row) {
                $pushBatchRow($row);
            }
        }

        return $merged;
    }

    function vol_attach_stock_rows($conn, $plantId, $items, $kind) {
        if (!is_array($items)) {
            return array();
        }
        foreach ($items as &$values) {
            if (!is_array($values)) {
                continue;
            }
            if ($kind === 'chemical') {
                $materialCode = vol_resolve_chemical_code($conn, $plantId, $values);
                $chemName = trim((string)(isset($values['chemical_name']) ? $values['chemical_name'] : ''));
                if ($materialCode !== '') {
                    $values['material_code'] = $materialCode;
                }
                $values['stock_data'] = vol_fetch_reagent_stock_from_engi_receiving($conn, $plantId, $materialCode, $chemName);
                if (!is_array($values['stock_data']) || count($values['stock_data']) === 0) {
                    $values['stock_data'] = vol_fetch_stock_data($conn, $plantId, $materialCode);
                }
            } else {
                $materialCode = vol_resolve_reagent_code($conn, $plantId, $values);
                $reagentName = trim((string)(isset($values['reagent']) ? $values['reagent'] : (isset($values['material_name']) ? $values['material_name'] : '')));
                if ($materialCode !== '') {
                    $values['material_code'] = $materialCode;
                }
                // Primary source: engi-store/receivingNew/log (QC Materials receiving batches).
                $values['stock_data'] = vol_fetch_reagent_stock_from_engi_receiving($conn, $plantId, $materialCode, $reagentName);
                if (!is_array($values['stock_data']) || count($values['stock_data']) === 0) {
                    $values['stock_data'] = vol_fetch_engi_stock_by_name($conn, $plantId, $reagentName);
                }
            }
        }
        unset($values);
        return $items;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS volumetric_solution_update_history (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        solution_id INT DEFAULT NULL,
        solution_no TEXT,
        action_type VARCHAR(30) DEFAULT NULL,
        old_solution_name TEXT,
        old_percentage TEXT,
        old_unit TEXT,
        old_strength TEXT,
        old_standard_type TEXT,
        new_solution_name TEXT,
        new_percentage TEXT,
        new_unit TEXT,
        new_strength TEXT,
        new_standard_type TEXT,
        requested_by TEXT,
        requested_date TEXT,
        approved_by TEXT,
        approved_date TEXT,
        remarks TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $conn->query("ALTER TABLE volumetric_solution
        ADD COLUMN IF NOT EXISTS pending_action TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS pending_solution_name TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS pending_percentage TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS pending_unit TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS pending_strength TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS pending_standard_type TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS request_by TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS request_date TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS updated_by TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS updated_date TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0");

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

        if ($_GET["type"] == "getMaterials") {
            $output = Array();
           // $sql = "SELECT m.*, s.batch_no FROM material m LEFT JOIN sampling s ON m.material_code = s.material_code";
           $sql = "SELECT material_name,material_code FROM material ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
         $sql1="select batch_no from sampling where material_code='".$row['material_code']."'";
          $result1 = $conn->query($sql1);
                      if ($result1->num_rows > 0) {
                while($row1 = $result1->fetch_assoc()) {
                    
                      $output1[] = $row1;
                }
                }
                    
                    
                    $row['batch_no']=$output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } else if ($_GET["type"] == "saveVolumetricMaster") {
            $solution_name = vesc($conn, isset($input["solution_name"]) ? $input["solution_name"] : "");
            $percentage = vesc($conn, isset($input["percentage"]) ? $input["percentage"] : "");
            $unit = vesc($conn, isset($input["strength_unit"]) ? $input["strength_unit"] : "");
            $strength = vesc($conn, isset($input["strength"]) ? $input["strength"] : "");
            $solution_type = vesc($conn, isset($input["solution_type"]) ? $input["solution_type"] : "");
            $exp_date = vesc($conn, isset($input["exp_date"]) ? $input["exp_date"] : "");
            
            $sql = "INSERT INTO volumetric_solution (plant_id,solution_name, percentage, unit, strength, standard_type, status, pending_action, request_by, request_date, entry_by, entry_date,exp_date, is_deleted)
            VALUES('".$_GET["plant_id"]."','".$solution_name."', '".$percentage."', '".$unit."', 
            '".$strength."','".$solution_type."','Pending','New','".$_GET["emp_id"]."', '$entry_date', '".$_GET["emp_id"]."', '$entry_date', '".$exp_date."', 0)";
           
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
        } else if ($_GET["type"] == "getPendingVolumetricMaster") {
            $output = Array();
            $sql = "SELECT * FROM volumetric_solution WHERE LOWER(IFNULL(status,''))='pending' AND IFNULL(is_deleted,0)=0 ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } else if ($_GET["type"] == "updateVolumetricSolution") {
            $id = intval($_GET["id"]);
            $action = isset($_GET["action"]) ? strtolower($_GET["action"]) : "";
            $status = isset($_GET["status"]) ? strtolower($_GET["status"]) : "";
            if($action == "approve" || $status == "approve"){
                $sql0 = "SELECT * FROM volumetric_solution WHERE id='".$id."' LIMIT 1";
                $result0 = $conn->query($sql0);
                if($result0->num_rows > 0){
                    $row0 = $result0->fetch_assoc();
                    $pendingAction = strtolower(trim((string)$row0["pending_action"]));
                    if($pendingAction == "update"){
                        $hist = "INSERT INTO volumetric_solution_update_history
                            (solution_id, solution_no, action_type, old_solution_name, old_percentage, old_unit, old_strength, old_standard_type,
                            new_solution_name, new_percentage, new_unit, new_strength, new_standard_type, requested_by, requested_date, approved_by, approved_date, remarks)
                            VALUES
                            ('".$id."', '".$conn->real_escape_string($row0["solution_no"])."', 'Update',
                            '".$conn->real_escape_string($row0["solution_name"])."', '".$conn->real_escape_string($row0["percentage"])."',
                            '".$conn->real_escape_string($row0["unit"])."', '".$conn->real_escape_string($row0["strength"])."',
                            '".$conn->real_escape_string($row0["standard_type"])."',
                            '".$conn->real_escape_string($row0["pending_solution_name"])."', '".$conn->real_escape_string($row0["pending_percentage"])."',
                            '".$conn->real_escape_string($row0["pending_unit"])."', '".$conn->real_escape_string($row0["pending_strength"])."',
                            '".$conn->real_escape_string($row0["pending_standard_type"])."',
                            '".$conn->real_escape_string($row0["request_by"])."', '".$conn->real_escape_string($row0["request_date"])."',
                            '".$conn->real_escape_string($_GET["emp_id"])."', '$entry_date', 'Approved update request')";
                        $conn->query($hist);
                        $sql = "UPDATE volumetric_solution
                            SET solution_name=IFNULL(NULLIF(pending_solution_name,''),solution_name),
                                percentage=IFNULL(pending_percentage,''),
                                unit=IFNULL(NULLIF(pending_unit,''),unit),
                                strength=IFNULL(pending_strength,''),
                                standard_type=IFNULL(NULLIF(pending_standard_type,''),standard_type),
                                updated_by=request_by,
                                updated_date=request_date,
                                status='Approved',
                                approve_by='".$_GET["emp_id"]."',
                                approve_date='$entry_date',
                                pending_action=NULL,
                                pending_solution_name=NULL,
                                pending_percentage=NULL,
                                pending_unit=NULL,
                                pending_strength=NULL,
                                pending_standard_type=NULL,
                                request_by=NULL,
                                request_date=NULL
                            WHERE id='".$id."'";
                    } else if($pendingAction == "delete"){
                        $hist = "INSERT INTO volumetric_solution_update_history
                            (solution_id, solution_no, action_type, old_solution_name, old_percentage, old_unit, old_strength, old_standard_type,
                            requested_by, requested_date, approved_by, approved_date, remarks)
                            VALUES
                            ('".$id."', '".$conn->real_escape_string($row0["solution_no"])."', 'Delete',
                            '".$conn->real_escape_string($row0["solution_name"])."', '".$conn->real_escape_string($row0["percentage"])."',
                            '".$conn->real_escape_string($row0["unit"])."', '".$conn->real_escape_string($row0["strength"])."',
                            '".$conn->real_escape_string($row0["standard_type"])."',
                            '".$conn->real_escape_string($row0["request_by"])."', '".$conn->real_escape_string($row0["request_date"])."',
                            '".$conn->real_escape_string($_GET["emp_id"])."', '$entry_date', 'Approved delete request')";
                        $conn->query($hist);
                        $sql = "UPDATE volumetric_solution
                            SET is_deleted=1,
                                status='Deleted',
                                approve_by='".$_GET["emp_id"]."',
                                approve_date='$entry_date',
                                pending_action=NULL,
                                request_by=NULL,
                                request_date=NULL
                            WHERE id='".$id."'";
                    } else {
                        $sql = "UPDATE volumetric_solution
                            SET status='Approved',
                                approve_by='".$_GET["emp_id"]."',
                                approve_date='$entry_date',
                                pending_action=NULL,
                                request_by=NULL,
                                request_date=NULL
                            WHERE id='".$id."'";
                    }
                } else {
                    $sql = "UPDATE volumetric_solution SET status='Approved', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$id."'";
                }
            } else {
                $sql0 = "SELECT status,pending_action FROM volumetric_solution WHERE id='".$id."' LIMIT 1";
                $result0 = $conn->query($sql0);
                $fallbackStatus = "Rejected";
                if($result0->num_rows > 0){
                    $row0 = $result0->fetch_assoc();
                    if(strtolower(trim((string)$row0["pending_action"])) == "new"){
                        $fallbackStatus = "Rejected";
                    } else {
                        $fallbackStatus = "Approved";
                    }
                }
                $sql = "UPDATE volumetric_solution
                    SET status='".$fallbackStatus."',
                        pending_action=NULL,
                        pending_solution_name=NULL,
                        pending_percentage=NULL,
                        pending_unit=NULL,
                        pending_strength=NULL,
                        pending_standard_type=NULL,
                        request_by=NULL,
                        request_date=NULL
                    WHERE id='".$id."'";
            }
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else if ($_GET["type"] == "requestVolumetricUpdate") {
            $id = intval(isset($input["id"]) ? $input["id"] : 0);
            $solution_name = vesc($conn, isset($input["solution_name"]) ? $input["solution_name"] : "");
            $percentage = vesc($conn, isset($input["percentage"]) ? $input["percentage"] : "");
            $unit = vesc($conn, isset($input["strength_unit"]) ? $input["strength_unit"] : "");
            $strength = vesc($conn, isset($input["strength"]) ? $input["strength"] : "");
            $solution_type = vesc($conn, isset($input["solution_type"]) ? $input["solution_type"] : "");
            $sql = "UPDATE volumetric_solution SET
                status='Pending',
                pending_action='Update',
                pending_solution_name='".$solution_name."',
                pending_percentage='".$percentage."',
                pending_unit='".$unit."',
                pending_strength='".$strength."',
                pending_standard_type='".$solution_type."',
                request_by='".$_GET["emp_id"]."',
                request_date='$entry_date'
                WHERE id='".$id."' AND IFNULL(is_deleted,0)=0";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else if ($_GET["type"] == "requestVolumetricDelete") {
            $id = intval(isset($input["id"]) ? $input["id"] : 0);
            $sql = "UPDATE volumetric_solution SET
                status='Pending',
                pending_action='Delete',
                request_by='".$_GET["emp_id"]."',
                request_date='$entry_date'
                WHERE id='".$id."' AND IFNULL(is_deleted,0)=0";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else if ($_GET["type"] == "getVolumetricUpdateHistory") {
            $output = Array();
            $solutionId = intval(isset($_GET["solution_id"]) ? $_GET["solution_id"] : 0);
            $sql = "SELECT * FROM volumetric_solution_update_history WHERE solution_id='".$solutionId."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if($result && $result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } else if ($_GET["type"] == "getVolumetricMaster") {
            $output = Array();
            $plant=$_GET['plant_id'];
            $includeDeleted = (isset($_GET["include_deleted"]) && $_GET["include_deleted"] == "1");
            $deletedCondition = $includeDeleted ? "1=1" : "IFNULL(is_deleted,0)=0";
            if($plant==0){
            $sql = "SELECT * FROM volumetric_solution WHERE ".$deletedCondition." ORDER BY `id` DESC";
            }else{
            $sql = "SELECT * FROM volumetric_solution WHERE plant_id='".$_GET["plant_id"]."' AND ".$deletedCondition." ORDER BY `id` DESC";    
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["procedures"] = json_decode($row["procedures"]);
                      $row["Reagent"] = json_decode($row["Reagent"]);
                      $row["Equipment"] = json_decode($row["Equipment"]);
 
                      $row["chemicals"] = json_decode($row["chemicals"]);
                      
                     $jsonString = $row["Safty"]; 
                    
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    
             
                    $safetyData = json_decode($jsonString, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);
                    
                    if ($safetyData === null && json_last_error() !== JSON_ERROR_NONE) {
                         
                        $errorMessage = json_last_error_msg();
                        
                        //echo "Error decoding JSON: $errorMessage";
                    } else {
                       
                        $row["Safty"] = $safetyData;

                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);

            
        } 
        
        
        else if ($_GET["type"] == "getStockVolumetic") {
            
            $output = Array();
             $sql = "SELECT id,solution_no,solution_name,plant_id FROM volumetric_solution where plant_id = '".$_GET["plant_id"]."' AND IFNULL(is_deleted,0)=0 AND LOWER(IFNULL(status,''))='approved' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                    
           
                $sql1 = "SELECT  IFNULL(SUM(volume_prepared), 0) as totalQty   FROM volumetric_preparation WHERE   solution_no = '".$row["solution_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["totalQty"] = number_format((float)$row1["totalQty"], 2, '.', '');
                    }
                }
                
                
                $sql31 = "SELECT   IFNULL(SUM(qty), 0) as ishueQty  FROM engi_stock_isshue 
                WHERE  material_code = '".$row["solution_no"]."'";
                
                $result31 = $conn->query($sql31);
                if ($result31->num_rows > 0) {
                    while ($row31 = $result31->fetch_assoc()) {
                        $row["ishueQty"] = number_format((float)$row31["ishueQty"], 2, '.', '');
                    }
                }
                
                
                
                 $output2 = Array();
                $sql2 = "SELECT  id,solution_no,batch_no,date_of_prep,exp_date,volume_prepared  FROM volumetric_preparation WHERE   solution_no= '".$row["solution_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        
                $sql3 = "SELECT   IFNULL(SUM(qty), 0) as ishueQty  FROM engi_stock_isshue 
                WHERE batch_no= '".$row2["batch_no"]."' AND   material_code= '".$row2["solution_no"]."'";
                
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $row2["ishueQty"] = number_format((float)$row3["ishueQty"], 2, '.', '');
                    }
                }
                         
                         $row2["balanceQty"] = number_format($row2["volume_prepared"] - $row2["ishueQty"], 2, '.', '');
                         
                         
                         
                         $output24 = Array();  
                         
                         
                $sql35 = "SELECT  * FROM engi_stock_isshue WHERE batch_no= '".$row2["batch_no"]."' AND   material_code= '".$row2["solution_no"]."'";
                
                $result35 = $conn->query($sql35);
                if ($result35->num_rows > 0) {
                    while ($row35 = $result35->fetch_assoc()) {
                        $output24[] = $row35 ;
                    }
                }
                          
                         
                         $row2['desp_data'] = $output24;
                         
                         
                        
                          $output2[] = $row2;
                    }
                }
                    
                
           
                    
               $row['batch_data'] = $output2;
                  $row["balanceQty"] = number_format($row["totalQty"] - $row["ishueQty"], 2, '.', '');
                  
                     $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        
        
        
        
        
        else if ($_GET["type"] == "getVolumetricSol") {
          
            $output = Array();
            $plant=$_GET['plant_id'];
            if($_GET["solType"] == "manitol"){
            $sql = "SELECT * FROM volumetric_solution  WHERE plant_id='".$_GET["plant_id"]."'  AND solution_name LIKE '%MANNITOL%' AND IFNULL(is_deleted,0)=0 AND LOWER(IFNULL(status,''))='approved' ORDER BY `id` DESC";
            }else{
            $sql = "SELECT * FROM volumetric_solution WHERE plant_id='".$_GET["plant_id"]."' AND IFNULL(is_deleted,0)=0 AND LOWER(IFNULL(status,''))='approved' ORDER BY `id` DESC";    
            }
            
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["procedures"] = vol_json_array($row["procedures"]);
                    $row["Equipment"] = vol_json_array($row["Equipment"]);

                    $jsonString = isset($row["Safty"]) ? preg_replace('/[[:cntrl:]]/', '', $row["Safty"]) : '';
                    $safetyData = vol_json_array($jsonString);
                    $row["Safty"] = $safetyData;

                    $chemicals = vol_json_array($row["chemicals"]);
                    $row["chemicals"] = vol_attach_stock_rows($conn, $plant, $chemicals, 'chemical');

                    $reagent = vol_json_array($row["Reagent"]);
                    $row["Reagent"] = vol_attach_stock_rows($conn, $plant, $reagent, 'reagent');

                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if ($_GET["type"] == "getemployees") {
          
            $output = Array();
             
            $sql = "SELECT * FROM employee WHERE plant_id='".$_GET["plant_id"]."' AND status = 'active'  AND department='Quality Control'  ORDER BY `id` DESC";    
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                      $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if ($_GET["type"] == "saveVolumetricPreparation") {
            
            $input = $_POST;
            
            $plant_id =$_GET["plant_id"];
            $target_dir = "../../../upload/volumetric/";
           
        
        $flag = 1;
           
           if(isset($_FILES["weigh_slip"]["name"])) {
            	$target_file = $target_dir.$plant_id.$input["solution_no"]."_".basename($_FILES["weigh_slip"]["name"]);
            	$msds_file = $plant_id.$input["solution_no"]."_".basename($_FILES["weigh_slip"]["name"]);
        	   // move_uploaded_file($_FILES["weigh_slip"]["tmp_name"], $target_file);
        	     $flag =0;
           }
            
            
          $sql = "INSERT INTO volumetric_preparation(plant_id, solution_no, rp_no, anylist, date_of_prep, exp_date,volume_prepared, Reagent, Equipment, 
        procedures, weigh_slip_req, weigh_slip, entry_by, entry_date,disposal,Storage,Expiry,solution_b,sol_b_temp,sol_b_humidity,solution_a,sol_a_humidity,sol_a_temp,
        safty,sol_b_exp_time,sol_a_exp_time,sol_type,standard_details,chemicals,status) VALUES ('".$_GET["plant_id"]."','".$input["solution_no"]."',
        '".$input["rp_no"]."','".$input["anylist"]."', '".$input["date_of_prep"]."','".$input["exp_date"]."','".$input["volume_prepared"]."','".$input["Reagent"]."',
        '".$input["Equipment"]."','".$input["procedures"]."', '".$input["weigh_slip_req"]."','$msds_file','".$_GET["emp_id"]."', '$entry_date',
        '".$input["disposal"]."','".$input["Storage"]."','".$input["Expiry"]."','".$input["solution_b"]."','".$input["sol_b_temp"]."','".$input["sol_b_humidity"]."',
        '".$input["solution_a"]."', '".$input["sol_a_humidity"]."','".$input["sol_a_temp"]."','".$input["safty"]."','".$input["sol_b_exp_time"]."',
        '".$input["sol_a_exp_time"]."','".$input["sol_type"]."' ,'".$input["standard_details"]."','".$input["chemicals"]."','Pending')";
            
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
                
                
            if($flag == 0) {
        	    move_uploaded_file($_FILES["weigh_slip"]["tmp_name"], $target_file);    
             }
                
            } else {
                echo "{\"status\":\"failed\"}";
            }
            
            
        }    
        
        
        
        
        else if ($_GET["type"] == "getVolumetricPreparation") {
            $output = Array();
            $sql = "SELECT v.*,v.disposal as disposal1,
            vs.storage_condition,vs.disposal,vs.Expirary,vs.Safty,vs.unit as strength_unit,vs.percentage,vs.strength,vs.solution_name FROM
            volumetric_preparation v left join volumetric_solution vs on v.solution_no = vs.solution_no where v.plant_id = '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["procedures"] = json_decode($row["procedures"]);
                    $row["Equipment"] = json_decode($row["Equipment"]);
                    $row["Reagent"] = json_decode($row["Reagent"]);
                    $row["chemicals"] = json_decode($row["chemicals"]);
                   
                    $jsonString = $row["Safty"];
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    $safetyData = json_decode($jsonString, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);
                    if ($safetyData === null && json_last_error() !== JSON_ERROR_NONE) {
                        $errorMessage = json_last_error_msg();
                        //echo "Error decoding JSON: $errorMessage";
                    } else {
                        $row["Safty"] = $safetyData;
                    }
                     $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if ($_GET["type"] == "getVolumetricPreparationForApproval") {
            $output = Array();
            $sql = "SELECT v.*,v.disposal as disposal1,
            vs.storage_condition,vs.disposal,vs.Expirary,vs.Safty, vs.unit as strength_unit,vs.percentage,vs.strength,vs.solution_name FROM
            volumetric_preparation v left join volumetric_solution vs on v.solution_no = vs.solution_no where v.plant_id = '".$_GET["plant_id"]."' AND (v.status = 'Pending' OR v.status IS NULL OR TRIM(IFNULL(v.status,'')) = '')";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["procedures"] = json_decode($row["procedures"]);
                    $row["Equipment"] = json_decode($row["Equipment"]);
                    $row["Reagent"] = json_decode($row["Reagent"]);
                    $row["chemicals"] = json_decode($row["chemicals"]);
             
                     
                    $jsonString = $row["Safty"];
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    $safetyData = json_decode($jsonString, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);
                    if ($safetyData === null && json_last_error() !== JSON_ERROR_NONE) {
                        $errorMessage = json_last_error_msg();
                       // echo "Error decoding JSON: $errorMessage";
                    } else {
                        $row["Safty"] = $safetyData;
                    }
                     $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        else if ($_GET["type"] == "getVolumetricPreparationLog") {
            $output = Array();
            $sql = "SELECT v.*,v.disposal as disposal1,
            vs.storage_condition,vs.disposal,vs.Expirary,vs.Safty, vs.unit as strength_unit,vs.percentage,vs.strength,vs.solution_name FROM
            volumetric_preparation v left join volumetric_solution vs on v.solution_no = vs.solution_no where v.plant_id = '".$_GET["plant_id"]."'
            AND (LOWER(TRIM(IFNULL(v.status,''))) IN ('approved','approve') OR TRIM(IFNULL(v.approve_by,'')) != '')";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["procedures"] = json_decode($row["procedures"]);
                    $row["Equipment"] = json_decode($row["Equipment"]);
                    $row["Reagent"] = json_decode($row["Reagent"]);
                    $row["chemicals"] = json_decode($row["chemicals"]);
                    $jsonString = $row["Safty"];
                    $jsonString = preg_replace('/[[:cntrl:]]/', '', $jsonString);
                    $safetyData = json_decode($jsonString, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);
                    if ($safetyData === null && json_last_error() !== JSON_ERROR_NONE) {
                        $errorMessage = json_last_error_msg();
                    } else {
                        $row["Safty"] = $safetyData;
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
        
        else if ($_GET["type"] == "approveVolSol") {
          $id = intval(isset($_GET["id"]) ? $_GET["id"] : 0);
          if ($id <= 0) {
              echo json_encode(array('status' => 'failed', 'message' => 'Invalid record'));
              exit;
          }
          $empEsc = vesc($conn, isset($_GET["emp_id"]) ? $_GET["emp_id"] : '');
          $sql = "UPDATE volumetric_preparation SET status='Approved', approve_by='".$empEsc."', approve_on='".$entry_date."' WHERE id='".$id."'";
            
            if ($conn->query($sql)) {
                @$conn->query("UPDATE volumetric_preparation SET standardization='done' WHERE id='".$id."'");
                echo json_encode(array('status' => 'success'));
              
              $chemicals = vol_json_array(isset($input["chemicals"]) ? $input["chemicals"] : array());
              foreach ($chemicals as $values) {
                  if (!is_array($values)) {
                      continue;
                  }
                  $materialCode = vesc($conn, isset($values["material_code"]) ? $values["material_code"] : '');
                  if ($materialCode === '') {
                      continue;
                  }
                     $sq = "INSERT INTO `engi_stock_isshue`(`isshue_for`, `material_code`, `grn_no`, `batch_no`, `qty`, `entry_by`, `entry_date`, 
                    `plant_id`, `disp_material_name`, `disp_material_code`, `testing_no`) 
                    VALUES ('Sol Preparation', '".$materialCode."', '".vesc($conn, isset($values["grn_no"]) ? $values["grn_no"] : '')."', '".vesc($conn, isset($values["batch_no"]) ? $values["batch_no"] : '')."', '".vesc($conn, isset($values["usedQty"]) ? $values["usedQty"] : '')."',
                    '".$empEsc."','$entry_date','".vesc($conn, isset($_GET["plant_id"]) ? $_GET["plant_id"] : '')."', '".vesc($conn, isset($values["chemical_name"]) ? $values["chemical_name"] : '')."','".$materialCode."' , 'NA')";
                   $conn->query($sq);
              }
                
              $Reagent = vol_json_array(isset($input["Reagent"]) ? $input["Reagent"] : array());
              foreach ($Reagent as $values) {
                  if (!is_array($values)) {
                      continue;
                  }
                  $materialCode = vesc($conn, isset($values["material_code"]) ? $values["material_code"] : '');
                  if ($materialCode === '') {
                      continue;
                  }
                      $sq1 = "INSERT INTO `engi_stock_isshue`(`isshue_for`, `material_code`, `grn_no`, `batch_no`, `qty`, `entry_by`, `entry_date`, 
                    `plant_id`, `disp_material_name`, `disp_material_code`, `testing_no`) 
                    VALUES ('Sol Preparation', '".$materialCode."', '".vesc($conn, isset($values["grn_no"]) ? $values["grn_no"] : '')."', '".vesc($conn, isset($values["batch_no"]) ? $values["batch_no"] : '')."', '".vesc($conn, isset($values["usedQty"]) ? $values["usedQty"] : '')."',
                    '".$empEsc."','$entry_date','".vesc($conn, isset($_GET["plant_id"]) ? $_GET["plant_id"] : '')."', '".vesc($conn, isset($values["reagent"]) ? $values["reagent"] : '')."','".$materialCode."' , 'NA')";
                   $conn->query($sq1);
              }
            } else {
                echo json_encode(array('status' => 'failed', 'message' => $conn->error));
            }
            
        } 
        
         else if ($_GET["type"] == "getPendingStandardization") {
            $output = Array();
            $sql = "SELECT * FROM volumetric_preparation WHERE standardization='pending'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM volumetric_solution WHERE id='".$row["solution_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["strength"] = $row1["strength"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        } else if ($_GET["type"] == "saveStandardization") {
            $sql = "UPDATE volumetric_preparation SET standardization='done', details='".json_encode($input['details'])."', mean='".$input["mean"]."', sd='".$input["sd"]."', rsd='".$input["rsd"]."' WHERE id='".$input["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
        } else if ($_GET["type"] == "getSolutionLog") {
            $plantId = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';
            echo json_encode(vol_fetch_solution_log_rows($conn, $plantId));
        } else if ($_GET["type"] == "downloadSolutionLog") {
            @ini_set('display_errors', '0');
            error_reporting(E_ERROR | E_PARSE);
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            $_GET['filename'] = 'Volumetric Solution Log';
            $_GET['pdftype'] = 'onlyheader';
            $html = '';
            include("../pdfimp2.php");
            $html .= '<h2 style="text-align:center">Volumetric Solution Log</h2>
                <table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:8px;">
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                    <td>Sr.</td>
                    <td>Material Code</td>
                    <td>Solution No.</td>
                    <td>Make</td>
                    <td>Medicap Lot No</td>
                    <td>Primary Standard</td>
                    <td>Secondary Standard</td>
                    <td>Date of Prep</td>
                    <td>Expiry Date</td>
                </tr>';
            $plantId = isset($_GET['plant_id']) ? $_GET['plant_id'] : '';
            $rows = vol_fetch_solution_log_rows($conn, $plantId);
            $i = 1;
            foreach ($rows as $row) {
                $html .= '<tr>
                    <td style="text-align:center;">'.$i.'</td>
                    <td>'.htmlspecialchars((string)(isset($row['material_code']) ? $row['material_code'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['solution_no']) ? $row['solution_no'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['make']) ? $row['make'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['batch_no']) ? $row['batch_no'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['primary_standard']) ? $row['primary_standard'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['secondary_standard']) ? $row['secondary_standard'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['date_of_prep']) ? $row['date_of_prep'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                    <td>'.htmlspecialchars((string)(isset($row['exp_date']) ? $row['exp_date'] : ''), ENT_QUOTES, 'UTF-8').'</td>
                </tr>';
                $i++;
            }
            if ($i === 1) {
                $html .= '<tr><td colspan="9" style="text-align:center;">No records found</td></tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Volumetric Solution Log.pdf', 'I');
            exit;
        }else if ($_GET["type"] == "downloadVolumetricMaster") {
        $_GET['filename'] = 'Volumetric Solution Master '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Volumetric Solution Master</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr.No.</td>
                    <td style="width:15%;">Solution No.</td>
                    <td style="width:20%;">Solution Name</td>
                    <td style="width:20%;">Percentage</td>
                    <td style="width:20%;">Strength</td>
                    <td style="width:20%;">Standard Type</td>
                </tr>
            </thead>';
            $i=1;
             $sql = "SELECT * FROM volumetric_solution WHERE  solution_name LIKE '%".$_GET["solution_name"]."%'  ORDER BY solution_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width:5%;">'.$i.'</td>
                            <td style="width:15%;">'.$row['solution_no'].'</td>
                            <td style="width:20%;">'.$row['solution_name'].'</td>
                            <td style="width:20%;">'.$row['percentage'].'</td>
                            <td style="width:20%;">'.$row['strength'].'</td>
                            <td style="width:20%;">'.$row['standard_type'].'</td>
                        </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Volumetric Master .pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>