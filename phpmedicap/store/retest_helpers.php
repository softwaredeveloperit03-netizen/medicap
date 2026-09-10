<?php
/**
 * Shared GMP retest helpers — released RM/PM stock due for retest cycle.
 */

if (!function_exists('medicap_retest_valid_date')) {
    function medicap_retest_valid_date($value) {
        $v = trim((string)$value);
        return $v !== '' && $v !== '0000-00-00' && strpos($v, '0000-00-00') !== 0;
    }
}

if (!function_exists('medicap_retest_retest_date_from_stock')) {
    /** Prefer stock_book.retest_date when set (supports UAT SQL updates in phpMyAdmin). */
    function medicap_retest_retest_date_from_stock($stock, $fallbackDate) {
        if (is_array($stock)) {
            $dbRetest = trim((string)($stock['retest_date'] ?? ''));
            if (medicap_retest_valid_date($dbRetest)) {
                return date('Y-m-d', strtotime($dbRetest));
            }
        }
        return $fallbackDate;
    }
}

if (!function_exists('medicap_retest_send_grn_due_days')) {
    /** Send GRN / retest sampling allowed when due within this many days (or overdue). */
    function medicap_retest_send_grn_due_days() {
        return 15;
    }
}

if (!function_exists('medicap_retest_notice_days')) {
    /** Days before retest due date when awaiting list / intimation slip shows the line. */
    function medicap_retest_notice_days() {
        return 10;
    }
}

if (!function_exists('medicap_retest_test_due_in_days')) {
    /** Optional UAT flag: ?test_due_in=10 forces retest due today+N (does not write DB). */
    function medicap_retest_test_due_in_days() {
        if (!isset($_GET['test_due_in']) || trim((string)$_GET['test_due_in']) === '') {
            return null;
        }
        $n = (int)$_GET['test_due_in'];
        return ($n >= 0) ? $n : null;
    }
}

if (!function_exists('medicap_retest_apply_test_due_to_row')) {
    function medicap_retest_apply_test_due_to_row($row, $days = null) {
        if (!is_array($row)) {
            return $row;
        }
        if ($days === null) {
            $days = medicap_retest_test_due_in_days();
        }
        if ($days === null) {
            return $row;
        }
        $retestDate = date('Y-m-d', strtotime('+'.$days.' days'));
        $row['retest_date'] = $retestDate;
        $row['due_days'] = medicap_retest_compute_due_days($retestDate);
        $row['due_label'] = medicap_retest_due_label($row['due_days']);
        $row['due_bucket'] = medicap_retest_due_bucket($row['due_days']);
        $row['test_due_override'] = true;
        return $row;
    }
}

if (!function_exists('medicap_retest_period_months')) {
    function medicap_retest_period_months($conn, $materialCode, $materialSubtype) {
        $codeEsc = $conn->real_escape_string((string)$materialCode);
        $res = $conn->query("SELECT retest_period FROM specification WHERE material_code = '".$codeEsc."' AND LOWER(TRIM(status)) IN ('approve', 'approved') ORDER BY id DESC LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if ($row['retest_period'] !== null && $row['retest_period'] !== '') {
                return (int)$row['retest_period'];
            }
        }
        $subtypeEsc = $conn->real_escape_string((string)$materialSubtype);
        $resMt = $conn->query("SELECT restest_months FROM material_type WHERE material_subtype = '".$subtypeEsc."' LIMIT 1");
        if ($resMt && $resMt->num_rows > 0) {
            $rowMt = $resMt->fetch_assoc();
            if ($rowMt['restest_months'] !== null && $rowMt['restest_months'] !== '') {
                return (int)$rowMt['restest_months'];
            }
        }
        return null;
    }
}

if (!function_exists('medicap_retest_compute_due_days')) {
    /** Signed whole days: positive = days left, 0 = due today, negative = overdue. */
    function medicap_retest_compute_due_days($retestDate) {
        if (!medicap_retest_valid_date($retestDate)) {
            return null;
        }
        $today = new DateTime(date('Y-m-d'));
        $rt = new DateTime(date('Y-m-d', strtotime($retestDate)));
        return (int)$today->diff($rt)->format('%r%a');
    }
}

if (!function_exists('medicap_retest_due_label')) {
    function medicap_retest_due_label($dueDays) {
        if ($dueDays === null) {
            return '-';
        }
        $d = (int)$dueDays;
        if ($d > 0) {
            return $d.' days left';
        }
        if ($d === 0) {
            return 'Due Today';
        }
        return abs($d).' days Overdue';
    }
}

if (!function_exists('medicap_retest_due_bucket')) {
    function medicap_retest_due_bucket($dueDays) {
        if ($dueDays === null) {
            return 'unknown';
        }
        $d = (int)$dueDays;
        if ($d < 0) {
            return 'overdue';
        }
        if ($d === 0) {
            return 'today';
        }
        return 'future';
    }
}

if (!function_exists('medicap_retest_resolve_date')) {
    function medicap_retest_resolve_date($conn, &$row) {
        $row['retest_period'] = medicap_retest_period_months(
            $conn,
            $row['material_code'] ?? '',
            $row['material_subtype'] ?? ''
        );
        $stockRt = trim((string)($row['stock_retest_date'] ?? $row['retest_date'] ?? ''));
        if (medicap_retest_valid_date($stockRt)) {
            return date('Y-m-d', strtotime($stockRt));
        }
        // Row may carry live stock_book.retest_date without stock_retest_date alias
        if (!empty($row['id']) && medicap_retest_valid_date($row['retest_date'] ?? '')) {
            return date('Y-m-d', strtotime($row['retest_date']));
        }
        if ($row['retest_period'] === null) {
            return null;
        }
        $baseDate = !empty($row['approve_date']) ? $row['approve_date'] : ($row['release_date'] ?? '');
        if (!medicap_retest_valid_date($baseDate)) {
            return null;
        }
        return date('Y-m-d', strtotime('+'.$row['retest_period'].' months', strtotime($baseDate)));
    }
}

if (!function_exists('medicap_retest_material_type_sql')) {
    function medicap_retest_material_type_sql($conn, $filter) {
        $filter = trim((string)$filter);
        if ($filter === 'Raw Material') {
            return " AND m.material_type = 'Raw Material' ";
        }
        if ($filter === 'Packing Material') {
            return " AND m.material_type = 'Packing Material' ";
        }
        return " AND m.material_type IN ('Raw Material', 'Packing Material') ";
    }
}

if (!function_exists('medicap_retest_material_scope_sql')) {
    /** Raw scope matches legacy calendar: material_type OR R-prefix code. */
    function medicap_retest_material_scope_sql($conn, $filter) {
        $filter = trim((string)$filter);
        if ($filter === 'Raw Material') {
            return " AND (m.material_type = 'Raw Material' OR s.material_code LIKE 'R%' OR s.material_code REGEXP '^[0-9]') ";
        }
        if ($filter === 'Packing Material') {
            return " AND (m.material_type = 'Packing Material' OR s.material_code LIKE 'P%') ";
        }
        if ($filter !== '') {
            return medicap_retest_material_type_sql($conn, $filter);
        }
        return " AND (m.material_type IN ('Raw Material', 'Packing Material') OR s.material_code LIKE 'R%' OR s.material_code LIKE 'P%' OR s.material_code REGEXP '^[0-9]') ";
    }
}

if (!function_exists('medicap_retest_testing_material_scope_sql')) {
    function medicap_retest_testing_material_scope_sql($conn, $filter, $tAlias = 't', $mAlias = 'b') {
        $filter = trim((string)$filter);
        if ($filter === 'Raw Material') {
            return " AND (".$mAlias.".material_type = 'Raw Material' OR ".$tAlias.".material_code LIKE 'R%' OR ".$tAlias.".material_code REGEXP '^[0-9]') ";
        }
        if ($filter === 'Packing Material') {
            return " AND (".$mAlias.".material_type = 'Packing Material' OR ".$tAlias.".material_code LIKE 'P%') ";
        }
        if ($filter !== '') {
            return " AND ".$mAlias.".material_type='".$conn->real_escape_string($filter)."' ";
        }
        return " AND (".$mAlias.".material_type IN ('Raw Material', 'Packing Material') OR ".$tAlias.".material_code LIKE 'R%' OR ".$tAlias.".material_code LIKE 'P%' OR ".$tAlias.".material_code REGEXP '^[0-9]') ";
    }
}

if (!function_exists('medicap_retest_stock_status_sql')) {
    function medicap_retest_stock_status_sql($alias = 's') {
        return " LOWER(TRIM(COALESCE(".$alias.".status,''))) IN ('approved', 'approve') ";
    }
}

if (!function_exists('medicap_retest_resolve_batch_no')) {
    function medicap_retest_resolve_batch_no($row) {
        $batch = trim((string)($row['batch_no'] ?? ''));
        if ($batch !== '') {
            return $batch;
        }
        return trim((string)($row['ar_no'] ?? ''));
    }
}

if (!function_exists('medicap_retest_row_dedupe_key')) {
    function medicap_retest_row_dedupe_key($row) {
        $grn = strtoupper(trim((string)($row['grn_no'] ?? '')));
        $code = strtoupper(trim((string)($row['material_code'] ?? '')));
        $batch = strtoupper(trim(medicap_retest_resolve_batch_no($row)));
        return $grn.'|'.$code.'|'.$batch;
    }
}

if (!function_exists('medicap_retest_stock_base_sql')) {
    function medicap_retest_stock_base_sql($conn, $plantId, $materialTypeFilter = '') {
        $plantSql = '';
        if ($plantId !== '') {
            $plantEsc = $conn->real_escape_string((string)$plantId);
            $plantSql = " AND s.plant_id = '".$plantEsc."' ";
        }
        return "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name,
                (SELECT COALESCE(SUM(mi.qty),0) FROM material_issue mi WHERE mi.material_code = s.material_code AND mi.grn_no = s.grn_no) AS used_qty,
                DATE(COALESCE(NULLIF(s.release_date,''), NULLIF(s.approve_date,''), s.grn_date, DATE(s.entry_date))) AS release_date,
                DATE(s.approve_date) AS approve_date,
                s.retest_date AS stock_retest_date
                FROM stock_book s
                LEFT JOIN material m ON s.material_code = m.material_code
                LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
                WHERE ".trim(medicap_retest_stock_status_sql('s'))."
                AND LOWER(TRIM(COALESCE(s.retest_status,''))) IN ('pending', 'in_sampling', 'sampling')
                ".$plantSql
                .medicap_retest_material_type_sql($conn, $materialTypeFilter);
    }
}

if (!function_exists('medicap_retest_awaiting_stock_sql')) {
    /** Broader stock filter for Store awaiting — matches legacy calendar (R% stock, not only retest_status=pending). */
    function medicap_retest_awaiting_stock_sql($conn, $plantId, $materialTypeFilter = '') {
        $plantSql = '';
        if ($plantId !== '') {
            $plantEsc = $conn->real_escape_string((string)$plantId);
            $plantSql = " AND s.plant_id = '".$plantEsc."' ";
        }
        return "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name,
                (SELECT COALESCE(SUM(mi.qty),0) FROM material_issue mi WHERE mi.material_code = s.material_code AND mi.grn_no = s.grn_no) AS used_qty,
                DATE(COALESCE(NULLIF(s.release_date,''), NULLIF(s.approve_date,''), s.grn_date, DATE(s.entry_date))) AS release_date,
                DATE(s.approve_date) AS approve_date,
                s.retest_date AS stock_retest_date
                FROM stock_book s
                LEFT JOIN material m ON s.material_code = m.material_code
                LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
                WHERE ".trim(medicap_retest_stock_status_sql('s'))."
                AND LOWER(TRIM(COALESCE(s.retest_status,''))) NOT IN ('approve', 'approved', 'done')
                AND (
                    LOWER(TRIM(COALESCE(s.retest_status,''))) IN ('pending', 'in_sampling', 'sampling', 'na', '')
                    OR (
                        s.retest_date IS NOT NULL AND TRIM(s.retest_date) != '' AND s.retest_date NOT LIKE '0000-%'
                    )
                )
                ".$plantSql
                .medicap_retest_material_scope_sql($conn, $materialTypeFilter);
    }
}

if (!function_exists('medicap_retest_find_stock_for_retest')) {
    function medicap_retest_find_stock_for_retest($conn, $plantId, $grn, $code, $batch, $arNo = '') {
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $grnEsc = $conn->real_escape_string((string)$grn);
        $codeEsc = $conn->real_escape_string((string)$code);
        $baseSql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name,
                (SELECT COALESCE(SUM(mi.qty),0) FROM material_issue mi WHERE mi.material_code = s.material_code AND mi.grn_no = s.grn_no) AS used_qty,
                DATE(COALESCE(NULLIF(s.release_date,''), NULLIF(s.approve_date,''), s.grn_date, DATE(s.entry_date))) AS release_date,
                DATE(s.approve_date) AS approve_date,
                s.retest_date AS stock_retest_date
                FROM stock_book s
                LEFT JOIN material m ON s.material_code = m.material_code
                LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
                WHERE s.plant_id='".$plantEsc."' AND s.material_code='".$codeEsc."'
                AND (s.grn_no='".$grnEsc."' OR s.receiving_no='".$grnEsc."' OR s.ar_no='".$grnEsc."')
                AND ".trim(medicap_retest_stock_status_sql('s'));

        $candidates = array();
        foreach (array($batch, $arNo) as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }
        foreach ($candidates as $candidate) {
            $batchEsc = $conn->real_escape_string($candidate);
            $res = $conn->query($baseSql." AND s.batch_no='".$batchEsc."' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc();
            }
            $resAr = $conn->query($baseSql." AND s.ar_no='".$batchEsc."' LIMIT 1");
            if ($resAr && $resAr->num_rows > 0) {
                return $resAr->fetch_assoc();
            }
        }
        $resAll = $conn->query($baseSql." ORDER BY s.id DESC LIMIT 1");
        if ($resAll && $resAll->num_rows > 0) {
            return $resAll->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('medicap_retest_find_stock_by_recv')) {
    function medicap_retest_find_stock_by_recv($conn, $plantId, $code, $recv, $batch, $arNo = '') {
        $code = trim((string)$code);
        $recv = trim((string)$recv);
        if ($code === '') {
            return null;
        }
        $plantSql = ($plantId !== '') ? " AND s.plant_id='".$conn->real_escape_string((string)$plantId)."'" : '';
        $codeEsc = $conn->real_escape_string($code);
        $recvSql = '';
        if ($recv !== '') {
            $recvEsc = $conn->real_escape_string($recv);
            $recvSql = " AND (s.grn_no='".$recvEsc."' OR s.receiving_no='".$recvEsc."' OR s.ar_no='".$recvEsc."')";
        }
        $baseSql = "SELECT s.* FROM stock_book s WHERE s.material_code='".$codeEsc."'".$plantSql.$recvSql;

        $candidates = array();
        foreach (array($batch, $arNo) as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }
        foreach ($candidates as $candidate) {
            $batchEsc = $conn->real_escape_string($candidate);
            $res = $conn->query($baseSql." AND s.batch_no='".$batchEsc."' ORDER BY s.id DESC LIMIT 1");
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc();
            }
            $resAr = $conn->query($baseSql." AND s.ar_no='".$batchEsc."' ORDER BY s.id DESC LIMIT 1");
            if ($resAr && $resAr->num_rows > 0) {
                return $resAr->fetch_assoc();
            }
        }
        $resAll = $conn->query($baseSql." ORDER BY s.id DESC LIMIT 1");
        if ($resAll && $resAll->num_rows > 0) {
            return $resAll->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('medicap_retest_find_stock_row_loose')) {
    /** Locate stock_book without status filter — used when syncing after QC approval. */
    function medicap_retest_find_stock_row_loose($conn, $plantId, $grn, $code, $batch, $arNo = '') {
        $recvKeys = array();
        foreach (array($grn, $arNo) as $v) {
            $v = trim((string)$v);
            if ($v !== '' && !in_array($v, $recvKeys, true)) {
                $recvKeys[] = $v;
            }
        }
        if (strpos((string)$grn, 'R-') === 0) {
            $stripped = substr((string)$grn, 2);
            if ($stripped !== '' && !in_array($stripped, $recvKeys, true)) {
                $recvKeys[] = $stripped;
            }
        }

        $plantVariants = array((string)$plantId);
        if ((string)$plantId !== '') {
            $plantVariants[] = '';
        }

        foreach ($plantVariants as $plant) {
            foreach ($recvKeys as $recv) {
                $stock = medicap_retest_find_stock_by_recv($conn, $plant, $code, $recv, $batch, $arNo);
                if ($stock !== null) {
                    return $stock;
                }
            }
        }

        foreach ($plantVariants as $plant) {
            $stock = medicap_retest_find_stock_by_recv($conn, $plant, $code, '', $batch, $arNo);
            if ($stock !== null) {
                return $stock;
            }
        }

        return null;
    }
}

if (!function_exists('medicap_retest_apply_testing_approval')) {
    /** After QC testing approval, set release/retest dates on stock_book (feeds Store retest calendar). */
    function medicap_retest_apply_testing_approval($conn, $input, $plantId, $testingId = '') {
        $status = trim((string)($input['status'] ?? ''));
        $approved = (strcasecmp($status, 'Approved') === 0);
        $retest = $approved ? 'Pending' : 'NA';

        $retestMonth = (int)($input['retest_month'] ?? 0);
        if ($retestMonth <= 0) {
            $codeEsc = $conn->real_escape_string((string)($input['material_code'] ?? ''));
            $resM = $conn->query("SELECT retest_month FROM material WHERE material_code='".$codeEsc."' LIMIT 1");
            if ($resM && $resM->num_rows > 0) {
                $retestMonth = (int)($resM->fetch_assoc()['retest_month'] ?? 0);
            }
        }
        if ($retestMonth <= 0) {
            $retestMonth = 36;
        }

        $releaseDate = trim((string)($input['release_date'] ?? ''));
        if (!medicap_retest_valid_date($releaseDate)) {
            $releaseDate = date('Y-m-d');
        }
        $retestDate = date('Y-m-d', strtotime('+'.$retestMonth.' months', strtotime($releaseDate)));

        $grnNo = trim((string)($input['grn_no'] ?? ''));
        $arNo = trim((string)($input['ar_no'] ?? ''));
        $code = trim((string)($input['material_code'] ?? ''));
        $batch = trim((string)($input['batch_no'] ?? ''));

        $stockGrn = $grnNo;
        $stockAr = $arNo;
        if (strpos($grnNo, 'R-') === 0) {
            $stockGrn = substr($grnNo, 2);
            if (strpos($arNo, 'R-') === 0) {
                $stockAr = substr($arNo, 2);
            }
            if ($approved) {
                $retest = 'Pending';
            } else {
                $retest = 'NA';
            }
        }

        $stock = medicap_retest_find_stock_row_loose($conn, $plantId, $stockGrn, $code, $batch, $stockAr);
        if ($stock === null && $stockGrn !== $grnNo) {
            $stock = medicap_retest_find_stock_row_loose($conn, $plantId, $grnNo, $code, $batch, $arNo);
        }

        if ($stock === null) {
            return array('ok' => false, 'msg' => 'stock_book row not found for '.$code.' / '.$stockGrn);
        }

        $stockId = (int)($stock['id'] ?? 0);
        if ($stockId <= 0) {
            return array('ok' => false, 'msg' => 'Invalid stock_book row.');
        }

        $releaseDateEsc = $conn->real_escape_string($releaseDate);
        $statusEsc = $conn->real_escape_string($status);
        $retestEsc = $conn->real_escape_string($retest);
        $retestDateEsc = $conn->real_escape_string($retestDate);
        $testingIdEsc = $conn->real_escape_string((string)$testingId);

        $sql = "UPDATE stock_book SET release_date='".$releaseDateEsc."', retest_date='".$retestDateEsc."',
            retest_status='".$retestEsc."', retest_grn_no=NULL, status='".$statusEsc."', testingId='".$testingIdEsc."'
            WHERE id='".$stockId."'";

        if (!$conn->query($sql)) {
            return array('ok' => false, 'msg' => $conn->error);
        }

        return array('ok' => true, 'stock_id' => $stockId, 'retest_date' => $retestDate, 'release_date' => $releaseDate);
    }
}

if (!function_exists('medicap_retest_helpers_version')) {
    function medicap_retest_helpers_version() {
        return '20260831i';
    }
}

if (!function_exists('medicap_retest_normalize_plant_id')) {
    function medicap_retest_normalize_plant_id($plantId) {
        $plantId = trim((string)$plantId);
        if ($plantId === '' || strtolower($plantId) === 'null' || strtolower($plantId) === 'undefined') {
            return '';
        }
        return $plantId;
    }
}

if (!function_exists('medicap_retest_query_approved_testing')) {
    /** Same approved rows as QC → Testing Log (getApproveTestingLog). */
    function medicap_retest_query_approved_testing($conn, $plantId = '', $materialType = '') {
        $plantId = medicap_retest_normalize_plant_id($plantId !== '' ? $plantId : ($_GET['plant_id'] ?? ''));
        $materialType = trim((string)($materialType !== '' ? $materialType : ($_GET['material_type'] ?? '')));

        $attempts = array();
        $plantSql = '';
        if ($plantId !== '') {
            $plantEsc = $conn->real_escape_string($plantId);
            $plantSql = " AND t.plant_id='".$plantEsc."' ";
        }
        $scopeSql = medicap_retest_testing_material_scope_sql($conn, $materialType, 't', 'b');
        $strictMatSql = '';
        if ($materialType === 'Raw Material') {
            $strictMatSql = " AND b.material_type='Raw Material' ";
        } else if ($materialType === 'Packing Material') {
            $strictMatSql = " AND b.material_type='Packing Material' ";
        } else if ($materialType !== '') {
            $strictMatSql = " AND b.material_type='".$conn->real_escape_string($materialType)."' ";
        }

        $select = "SELECT t.id AS testingId, t.plant_id, t.testing_no, t.grn_no, t.ar_no, t.batch_no, t.material_code, t.status,
                t.approve_date, t.allocationOn,
                b.retest_month, b.material_type, b.material_subtype, b.material_name, b.grade
                FROM testing t
                LEFT JOIN material b ON t.material_code=b.material_code
                WHERE (t.status='Approved' OR LOWER(TRIM(t.status))='approved') ";

        $attempts[] = $select.$plantSql.$strictMatSql." ORDER BY t.allocationOn DESC, t.approve_date DESC, t.id DESC";
        if ($scopeSql !== $strictMatSql) {
            $attempts[] = $select.$plantSql.$scopeSql." ORDER BY t.allocationOn DESC, t.approve_date DESC, t.id DESC";
        }
        if ($plantSql !== '') {
            $attempts[] = $select.$scopeSql." ORDER BY t.allocationOn DESC, t.approve_date DESC, t.id DESC";
        }
        if ($plantSql !== '' || $scopeSql !== '') {
            $attempts[] = $select." ORDER BY t.allocationOn DESC, t.approve_date DESC, t.id DESC";
        }

        $seenSql = array();
        foreach ($attempts as $sql) {
            if (isset($seenSql[$sql])) {
                continue;
            }
            $seenSql[$sql] = true;
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                return $res;
            }
        }
        return null;
    }
}

if (!function_exists('medicap_retest_build_simple_testing_row')) {
    /** Minimal awaiting/calendar row from approved testing (no stock_book required). */
    function medicap_retest_build_simple_testing_row($conn, $t) {
        $approveDate = medicap_retest_resolve_approve_date($t);
        $retestMonth = (int)($t['retest_month'] ?? 0);
        if ($retestMonth <= 0) {
            $period = medicap_retest_period_months($conn, $t['material_code'] ?? '', $t['material_subtype'] ?? '');
            $retestMonth = ($period !== null && $period > 0) ? $period : 36;
        }
        $retestDate = date('Y-m-d', strtotime('+'.$retestMonth.' months', strtotime($approveDate)));

        $stock = medicap_retest_find_stock_row_loose(
            $conn,
            (string)($t['plant_id'] ?? ''),
            (string)($t['grn_no'] ?? ''),
            (string)($t['material_code'] ?? ''),
            (string)($t['batch_no'] ?? ''),
            (string)($t['ar_no'] ?? '')
        );
        $retestDate = medicap_retest_retest_date_from_stock($stock, $retestDate);
        $dueDays = medicap_retest_compute_due_days($retestDate);

        return array(
            'plant_id' => $t['plant_id'] ?? '',
            'grn_no' => $t['grn_no'] ?? '',
            'grn_date' => (string)($stock['grn_date'] ?? $stock['entry_date'] ?? ''),
            'material_code' => $t['material_code'] ?? '',
            'material_name' => $t['material_name'] ?? '',
            'material_type' => $t['material_type'] ?? '',
            'material_subtype' => $t['material_subtype'] ?? '',
            'grade' => $t['grade'] ?? '',
            'batch_no' => medicap_retest_resolve_batch_no($t),
            'ar_no' => $t['ar_no'] ?? '',
            'release_date' => $approveDate,
            'approve_date' => $approveDate,
            'retest_date' => $retestDate,
            'due_days' => $dueDays,
            'due_label' => medicap_retest_due_label($dueDays),
            'due_bucket' => medicap_retest_due_bucket($dueDays),
            'retest_workflow_status' => 'Pending Retest',
            'retest_next_step' => 'Store → Raw Retest → Awaiting',
            'qty' => (float)($stock['qty'] ?? 0),
            'unit' => (string)($stock['unit'] ?? ''),
            'testingId' => $t['testingId'] ?? ($t['id'] ?? ''),
            'testing_no' => $t['testing_no'] ?? '',
        );
    }
}

if (!function_exists('medicap_retest_fallback_pending_from_testing')) {
    /** Last-resort list built exactly like QC getApproveTestingLog. */
    function medicap_retest_fallback_pending_from_testing($conn, $plantId = '', $materialType = 'Raw Material') {
        $res = medicap_retest_query_approved_testing($conn, $plantId, $materialType);
        if ($res === null) {
            return array();
        }
        $output = array();
        $seen = array();
        while ($t = $res->fetch_assoc()) {
            $key = medicap_retest_row_dedupe_key(medicap_retest_build_simple_testing_row($conn, $t));
            if ($key === '||' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $output[] = medicap_retest_build_simple_testing_row($conn, $t);
        }
        return $output;
    }
}

if (!function_exists('medicap_retest_resolve_approve_date')) {
    function medicap_retest_resolve_approve_date($t) {
        foreach (array('approve_date', 'allocationOn') as $field) {
            $v = trim((string)($t[$field] ?? ''));
            if (medicap_retest_valid_date($v)) {
                return date('Y-m-d', strtotime($v));
            }
        }
        return date('Y-m-d');
    }
}

if (!function_exists('medicap_retest_finalize_testing_row')) {
    function medicap_retest_finalize_testing_row($conn, $row, $retestDate, $approveDate) {
        $row['retest_date'] = $retestDate;
        unset($row['stock_retest_date']);
        $row['due_days'] = medicap_retest_compute_due_days($retestDate);
        $row['due_label'] = medicap_retest_due_label($row['due_days']);
        $row['due_bucket'] = medicap_retest_due_bucket($row['due_days']);
        $row['release_date'] = $approveDate;
        $row['approve_date'] = $approveDate;
        $row['retest_workflow_status'] = 'Pending Retest';
        $row['retest_next_step'] = 'Store → Raw Retest → Awaiting';
        $resolvedBatch = medicap_retest_resolve_batch_no($row);
        if ($resolvedBatch !== '') {
            $row['batch_no'] = $resolvedBatch;
        }
        if (trim((string)($row['material_name'] ?? '')) === '' && trim((string)($row['material_code'] ?? '')) !== '') {
            $codeEsc = $conn->real_escape_string((string)$row['material_code']);
            $mRes = $conn->query("SELECT material_name, material_type, material_subtype, grade, retest_month FROM material WHERE material_code='".$codeEsc."' LIMIT 1");
            if ($mRes && $mRes->num_rows > 0) {
                $m = $mRes->fetch_assoc();
                foreach (array('material_name', 'material_type', 'material_subtype', 'grade') as $f) {
                    if (trim((string)($row[$f] ?? '')) === '' && trim((string)($m[$f] ?? '')) !== '') {
                        $row[$f] = $m[$f];
                    }
                }
            }
        }
        return $row;
    }
}

if (!function_exists('medicap_retest_build_row_from_testing')) {
    function medicap_retest_build_row_from_testing($conn, $t) {
        $approveDate = medicap_retest_resolve_approve_date($t);
        $retestMonth = (int)($t['retest_month'] ?? 0);
        if ($retestMonth <= 0) {
            $period = medicap_retest_period_months($conn, $t['material_code'] ?? '', $t['material_subtype'] ?? '');
            $retestMonth = ($period !== null && $period > 0) ? $period : 36;
        }
        $retestDate = date('Y-m-d', strtotime('+'.$retestMonth.' months', strtotime($approveDate)));

        $stock = medicap_retest_find_stock_row_loose(
            $conn,
            (string)($t['plant_id'] ?? ''),
            (string)($t['grn_no'] ?? ''),
            (string)($t['material_code'] ?? ''),
            (string)($t['batch_no'] ?? ''),
            (string)($t['ar_no'] ?? '')
        );
        $retestDate = medicap_retest_retest_date_from_stock($stock, $retestDate);
        $qty = 0;
        $unit = '';
        $grnDate = '';
        if ($stock !== null) {
            $qty = (float)($stock['qty'] ?? 0);
            $unit = (string)($stock['unit'] ?? '');
            $grnDate = (string)($stock['grn_date'] ?? $stock['entry_date'] ?? '');
        }

        $row = array(
            'plant_id' => $t['plant_id'] ?? '',
            'grn_no' => $t['grn_no'] ?? '',
            'material_code' => $t['material_code'] ?? '',
            'batch_no' => $t['batch_no'] ?? '',
            'ar_no' => $t['ar_no'] ?? '',
            'material_name' => $t['material_name'] ?? '',
            'material_type' => $t['material_type'] ?? '',
            'material_subtype' => $t['material_subtype'] ?? '',
            'grade' => $t['grade'] ?? '',
            'stock_retest_date' => $retestDate,
            'release_date' => $approveDate,
            'approve_date' => $approveDate,
            'grn_date' => $grnDate,
            'retest_status' => 'Pending',
            'status' => 'Approved',
            'used_qty' => 0,
            'qty' => $qty,
            'unit' => $unit,
            'testingId' => $t['testingId'] ?? '',
            'testing_no' => $t['testing_no'] ?? '',
        );

        $enriched = medicap_retest_enrich_stock_row($conn, $row, false);
        if ($enriched !== null) {
            return $enriched;
        }
        return medicap_retest_finalize_testing_row($conn, $row, $retestDate, $approveDate);
    }
}

if (!function_exists('medicap_retest_rows_from_approved_testing')) {
    /** Calendar/awaiting fallback when stock_book was never synced after QC approval. */
    function medicap_retest_rows_from_approved_testing($conn, $plantId = '', $materialType = '') {
        $res = medicap_retest_query_approved_testing($conn, $plantId, $materialType);
        if ($res === null) {
            return array();
        }
        $output = array();
        $seen = array();
        while ($t = $res->fetch_assoc()) {
            $preview = medicap_retest_build_simple_testing_row($conn, $t);
            $key = medicap_retest_row_dedupe_key($preview);
            if ($key === '||' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $row = medicap_retest_build_row_from_testing($conn, $t);
            if ($row === null) {
                $row = $preview;
            }
            $output[] = $row;
        }
        return $output;
    }
}

if (!function_exists('medicap_retest_merge_retest_rows')) {
    function medicap_retest_merge_retest_rows($primary, $extra) {
        $seen = array();
        $out = array();
        foreach (array_merge($primary, $extra) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = medicap_retest_row_dedupe_key($row);
            if ($key === '||' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }
        usort($out, function ($a, $b) {
            $da = (string)($a['retest_date'] ?? '');
            $db = (string)($b['retest_date'] ?? '');
            if ($da === $db) {
                return strcmp((string)($a['grn_no'] ?? ''), (string)($b['grn_no'] ?? ''));
            }
            return strcmp($da, $db);
        });
        return $out;
    }
}

if (!function_exists('medicap_retest_sync_approved_testing_to_stock')) {
    /**
     * Back-fill stock_book retest dates from approved QC testing rows (fixes calendar when sync was missed).
     */
    function medicap_retest_sync_approved_testing_to_stock($conn, $plantId = '', $materialType = '') {
        $plantId = (string)($plantId !== '' ? $plantId : ($_GET['plant_id'] ?? ''));
        if ($materialType === '') {
            $materialType = trim((string)($_GET['material_type'] ?? ''));
        }
        $res = medicap_retest_query_approved_testing($conn, $plantId, $materialType);
        if ($res === null) {
            return 0;
        }

        $synced = 0;
        $seen = array();
        while ($row = $res->fetch_assoc()) {
            $key = strtoupper(trim((string)($row['material_code'] ?? ''))).'|'.strtoupper(trim((string)($row['grn_no'] ?? '')));
            if ($key === '|' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $stock = medicap_retest_find_stock_row_loose(
                $conn,
                (string)($row['plant_id'] ?? $plantId),
                (string)($row['grn_no'] ?? ''),
                (string)($row['material_code'] ?? ''),
                (string)($row['batch_no'] ?? ''),
                (string)($row['ar_no'] ?? '')
            );
            if ($stock === null) {
                continue;
            }

            $rtSt = strtolower(trim((string)($stock['retest_status'] ?? '')));
            if (in_array($rtSt, array('pending', 'in_sampling', 'sampling'), true)
                && medicap_retest_valid_date($stock['retest_date'] ?? '')) {
                continue;
            }

            $payload = array(
                'status' => 'Approved',
                'material_code' => $row['material_code'] ?? '',
                'grn_no' => $row['grn_no'] ?? '',
                'ar_no' => $row['ar_no'] ?? '',
                'batch_no' => $row['batch_no'] ?? '',
                'retest_month' => $row['retest_month'] ?? '',
                'release_date' => medicap_retest_resolve_approve_date($row),
            );
            $result = medicap_retest_apply_testing_approval(
                $conn,
                $payload,
                (string)($row['plant_id'] ?? $plantId),
                (string)($row['testingId'] ?? '')
            );
            if (!empty($result['ok'])) {
                $synced++;
            }
        }
        return $synced;
    }
}

if (!function_exists('medicap_retest_enrich_from_retest_row')) {
    function medicap_retest_enrich_from_retest_row($conn, $retestRow, $stock = null) {
        $row = is_array($stock) ? $stock : array();
        foreach (array('plant_id', 'grn_no', 'material_code', 'batch_no', 'ar_no', 'qty', 'unit', 'release_date', 'mfg_date', 'exp_date') as $field) {
            if (empty($row[$field]) && !empty($retestRow[$field])) {
                $row[$field] = $retestRow[$field];
            }
        }
        if (empty($row['material_name']) && !empty($retestRow['material_name'])) {
            $row['material_name'] = $retestRow['material_name'];
        }
        if (empty($row['material_type']) && !empty($retestRow['material_type'])) {
            $row['material_type'] = $retestRow['material_type'];
        }
        if (empty($row['material_subtype']) && !empty($retestRow['material_subtype'])) {
            $row['material_subtype'] = $retestRow['material_subtype'];
        }
        if (empty($row['grade']) && !empty($retestRow['grade'])) {
            $row['grade'] = $retestRow['grade'];
        }
        if (empty($row['grn_date']) && !empty($retestRow['grn_date'])) {
            $row['grn_date'] = $retestRow['grn_date'];
        }
        if (!isset($row['used_qty'])) {
            $row['used_qty'] = 0;
        }
        if (medicap_retest_valid_date($retestRow['retest_date'] ?? '')) {
            $row['stock_retest_date'] = date('Y-m-d', strtotime($retestRow['retest_date']));
        }
        return medicap_retest_enrich_stock_row($conn, $row, false);
    }
}

if (!function_exists('medicap_ensure_sampling_retest_columns')) {
    /** Add retest workflow columns to legacy sampling table when missing. */
    function medicap_ensure_sampling_retest_columns($conn) {
        static $done = false;
        if ($done) {
            return;
        }
        $columns = array(
            'old_grn' => "ALTER TABLE sampling ADD COLUMN old_grn VARCHAR(100) DEFAULT NULL AFTER grn_no",
            'retest_id' => "ALTER TABLE sampling ADD COLUMN retest_id INT DEFAULT NULL AFTER ar_no",
            'request_by' => "ALTER TABLE sampling ADD COLUMN request_by VARCHAR(50) DEFAULT NULL AFTER alternate_micro_person",
            'request_date' => "ALTER TABLE sampling ADD COLUMN request_date VARCHAR(50) DEFAULT NULL AFTER request_by",
        );
        foreach ($columns as $name => $alterSql) {
            $nameEsc = $conn->real_escape_string($name);
            $res = @$conn->query("SHOW COLUMNS FROM sampling LIKE '".$nameEsc."'");
            if (!$res || $res->num_rows === 0) {
                @$conn->query($alterSql);
            }
        }
        $done = true;
    }
}

if (!function_exists('medicap_ensure_sampling_retest_id_column')) {
    function medicap_ensure_sampling_retest_id_column($conn) {
        medicap_ensure_sampling_retest_columns($conn);
    }
}

if (!function_exists('medicap_retest_sampling_scope_sql')) {
    /** SQL predicate: sampling rows created for Store/QC retest workflow. */
    function medicap_retest_sampling_scope_sql($alias = 'a') {
        $p = ($alias !== '') ? $alias.'.' : '';
        return "(
            (COALESCE(".$p."retest_id, 0) > 0)
            OR ".$p."grn_no LIKE 'R-%'
            OR (COALESCE(".$p."old_grn,'') <> '' AND ".$p."old_grn = ".$p."grn_no)
        )";
    }
}

if (!function_exists('medicap_retest_mark_sampling_approved')) {
    function medicap_retest_mark_sampling_approved($conn, $samplingRow) {
        if (!is_array($samplingRow)) {
            return;
        }
        $retestId = (int)($samplingRow['retest_id'] ?? 0);
        if ($retestId > 0) {
            $conn->query("UPDATE retest SET status='sampled' WHERE id='".$conn->real_escape_string((string)$retestId)."'");
            return;
        }
        $oldGrn = trim((string)($samplingRow['old_grn'] ?? medicap_retest_display_receiving_no($samplingRow)));
        if ($oldGrn === '') {
            return;
        }
        $oldGrnEsc = $conn->real_escape_string($oldGrn);
        $rtRes = $conn->query("SELECT id FROM retest WHERE grn_no='".$oldGrnEsc."' ORDER BY id DESC LIMIT 1");
        if ($rtRes && $rtRes->num_rows > 0) {
            $rtRow = $rtRes->fetch_assoc();
            $conn->query("UPDATE retest SET status='sampled' WHERE id='".$rtRow['id']."'");
        }
    }
}

if (!function_exists('medicap_retest_display_receiving_no')) {
    function medicap_retest_display_receiving_no($row) {
        $old = trim((string)($row['old_grn'] ?? ''));
        if ($old !== '') {
            return $old;
        }
        $grn = trim((string)($row['grn_no'] ?? ''));
        if (strpos($grn, 'R-') === 0) {
            return substr($grn, 2);
        }
        return $grn;
    }
}

if (!function_exists('medicap_retest_find_row_for_stock')) {
    function medicap_retest_find_row_for_stock($conn, $plantId, $grn, $code, $batch) {
        $plantSql = ($plantId !== '') ? " AND plant_id='".$conn->real_escape_string((string)$plantId)."'" : '';
        $grnEsc = $conn->real_escape_string((string)$grn);
        $codeEsc = $conn->real_escape_string((string)$code);
        $batchEsc = $conn->real_escape_string((string)$batch);
        $res = $conn->query("SELECT * FROM retest WHERE grn_no='".$grnEsc."' AND material_code='".$codeEsc."' AND batch_no='".$batchEsc."'".$plantSql." ORDER BY id DESC LIMIT 1");
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('medicap_retest_has_sampling')) {
    function medicap_retest_has_sampling($conn, $receivingNo, $retestId = 0) {
        medicap_ensure_sampling_retest_id_column($conn);
        $retestId = (int)$retestId;
        if ($retestId > 0) {
            $idEsc = $conn->real_escape_string((string)$retestId);
            $samp = $conn->query("SELECT id FROM sampling WHERE retest_id='".$idEsc."' LIMIT 1");
            if ($samp && $samp->num_rows > 0) {
                return true;
            }
        }
        $receivingNo = trim((string)$receivingNo);
        if ($receivingNo === '') {
            return false;
        }
        $recvEsc = $conn->real_escape_string($receivingNo);
        $samp = $conn->query("SELECT id FROM sampling WHERE retest_id IS NOT NULL AND retest_id > 0 AND (old_grn='".$recvEsc."' OR grn_no='".$recvEsc."') LIMIT 1");
        if ($samp && $samp->num_rows > 0) {
            return true;
        }
        $legacyEsc = $conn->real_escape_string('R-'.$receivingNo);
        $legacy = $conn->query("SELECT id FROM sampling WHERE grn_no='".$legacyEsc."' LIMIT 1");
        return ($legacy && $legacy->num_rows > 0);
    }
}

if (!function_exists('medicap_retest_has_r_sampling')) {
    function medicap_retest_has_r_sampling($conn, $grnNo) {
        return medicap_retest_has_sampling($conn, $grnNo, 0);
    }
}

if (!function_exists('medicap_retest_workflow_info')) {
    function medicap_retest_workflow_info($conn, $row) {
        $grn = trim((string)($row['grn_no'] ?? ''));
        $code = trim((string)($row['material_code'] ?? ''));
        $batch = trim((string)($row['batch_no'] ?? ''));
        $plantId = trim((string)($row['plant_id'] ?? ''));
        $plantSql = ($plantId !== '') ? " AND plant_id='".$conn->real_escape_string($plantId)."'" : '';

        if ($grn !== '' && medicap_retest_has_sampling($conn, $grn)) {
            return array(
                'retest_workflow_status' => 'In Retest Sampling',
                'retest_next_step' => 'QC → Retest → Sampling',
            );
        }

        if (in_array(strtolower(trim((string)($row['retest_status'] ?? ''))), array('in_sampling', 'sampling'), true)) {
            return array(
                'retest_workflow_status' => 'In Retest Sampling',
                'retest_next_step' => 'QC → Retest → Sampling',
            );
        }

        if ($grn !== '' && $code !== '') {
            $batchSql = ($batch !== '') ? " AND batch_no='".$conn->real_escape_string($batch)."'" : '';
            $retest = $conn->query("SELECT LOWER(TRIM(COALESCE(status,''))) AS st FROM retest WHERE grn_no='".$conn->real_escape_string($grn)."' AND material_code='".$conn->real_escape_string($code)."'".$batchSql.$plantSql." ORDER BY id DESC LIMIT 1");
            if ($retest && $retest->num_rows > 0) {
                $st = (string)$retest->fetch_assoc()['st'];
                if ($st === 'intimated') {
                    return array(
                        'retest_workflow_status' => 'Intimated',
                        'retest_next_step' => 'QC → Retest → Allocation',
                    );
                }
                if ($st === 'pending') {
                    return array(
                        'retest_workflow_status' => 'Allocated',
                        'retest_next_step' => 'QC → Retest → Sampling',
                    );
                }
                if (in_array($st, array('sampled', 'requested'), true)) {
                    return array(
                        'retest_workflow_status' => 'Sampled',
                        'retest_next_step' => 'QC → Testing',
                    );
                }
            }
        }

        return array(
            'retest_workflow_status' => 'Pending Retest',
            'retest_next_step' => 'Store → Raw Retest → Awaiting',
        );
    }
}

if (!function_exists('medicap_retest_enrich_stock_row')) {
    function medicap_retest_enrich_stock_row($conn, $row, $excludeSent = false) {
        $retestDate = medicap_retest_resolve_date($conn, $row);
        if ($retestDate === null) {
            return null;
        }
        $row['retest_date'] = $retestDate;
        unset($row['stock_retest_date']);
        $row['due_days'] = medicap_retest_compute_due_days($retestDate);
        $row['due_label'] = medicap_retest_due_label($row['due_days']);
        $row['due_bucket'] = medicap_retest_due_bucket($row['due_days']);
        $usedQty = (float)($row['used_qty'] ?? 0);
        $row['qty'] = (float)($row['qty'] ?? 0) - $usedQty;
        $resolvedBatch = medicap_retest_resolve_batch_no($row);
        if ($resolvedBatch !== '') {
            $row['batch_no'] = $resolvedBatch;
        }
        if ($excludeSent) {
            $rs = strtolower(trim((string)($row['retest_status'] ?? '')));
            if (in_array($rs, array('in_sampling', 'sampling'), true)) {
                return null;
            }
            $recv = trim((string)($row['grn_no'] ?? ''));
            $retestRow = medicap_retest_find_row_for_stock($conn, (string)($row['plant_id'] ?? ''), $recv, (string)($row['material_code'] ?? ''), medicap_retest_resolve_batch_no($row));
            $retestId = (int)($retestRow['id'] ?? 0);
            $retestSt = strtolower(trim((string)($retestRow['status'] ?? '')));
            if (in_array($retestSt, array('sampled', 'requested'), true)) {
                return null;
            }
            if ($recv !== '' && medicap_retest_has_sampling($conn, $recv, $retestId)) {
                return null;
            }
        }
        $workflow = medicap_retest_workflow_info($conn, $row);
        $row['retest_workflow_status'] = $workflow['retest_workflow_status'];
        $row['retest_next_step'] = $workflow['retest_next_step'];
        return $row;
    }
}

if (!function_exists('medicap_retest_pending_rows')) {
    function medicap_retest_pending_rows($conn, $options = array()) {
        $plantId = (string)($options['plant_id'] ?? ($_GET['plant_id'] ?? ''));
        $materialType = (string)($options['material_type'] ?? '');
        $excludeSent = !empty($options['exclude_sent']);
        $dueMax = isset($options['due_max']) ? (int)$options['due_max'] : null;
        $dueMin = isset($options['due_min']) ? (int)$options['due_min'] : null;
        $includeOverdue = !isset($options['include_overdue']) || $options['include_overdue'];
        $useAwaitingStock = !empty($options['use_awaiting_stock']);

        if ($useAwaitingStock) {
            $sql = medicap_retest_awaiting_stock_sql($conn, $plantId, $materialType)." ORDER BY s.retest_date ASC, s.grn_no ASC";
        } else {
            $sql = medicap_retest_stock_base_sql($conn, $plantId, $materialType)." ORDER BY s.retest_date ASC, s.grn_no ASC";
        }
        $result = $conn->query($sql);
        $output = array();
        if (!$result) {
            return $output;
        }
        while ($row = $result->fetch_assoc()) {
            $enriched = medicap_retest_enrich_stock_row($conn, $row, $excludeSent);
            if ($enriched === null) {
                continue;
            }
            $due = $enriched['due_days'];
            if ($due === null) {
                continue;
            }
            if (!$includeOverdue && $due < 0) {
                continue;
            }
            if ($dueMax !== null && $due > $dueMax) {
                continue;
            }
            if ($dueMin !== null && $due < $dueMin) {
                continue;
            }
            $output[] = $enriched;
        }
        return $output;
    }
}

if (!function_exists('medicap_retest_awaiting_rows')) {
    /**
     * Store awaiting list: approved stock in retest cycle + active retest/intimation rows.
     * Keeps lines visible after intimation slip (also shown in intimation log).
     */
    function medicap_retest_awaiting_rows($conn, $options = array()) {
        $plantId = (string)($options['plant_id'] ?? ($_GET['plant_id'] ?? ''));
        $materialType = (string)($options['material_type'] ?? 'Raw Material');
        $includeOverdue = !isset($options['include_overdue']) || $options['include_overdue'];

        medicap_retest_sync_approved_testing_to_stock($conn, $plantId, $materialType);

        $output = array();
        $seen = array();

        $dueMax = isset($options['due_max']) ? (int)$options['due_max'] : null;

        $appendRow = function ($row) use (&$output, &$seen, $includeOverdue, $dueMax) {
            if (!is_array($row) || empty($row)) {
                return;
            }
            if (!isset($row['due_days']) || $row['due_days'] === null) {
                if (!empty($row['retest_date']) && medicap_retest_valid_date($row['retest_date'])) {
                    $row['due_days'] = medicap_retest_compute_due_days($row['retest_date']);
                    $row['due_label'] = medicap_retest_due_label($row['due_days']);
                } else {
                    return;
                }
            }
            $due = $row['due_days'];
            if (!$includeOverdue && $due < 0) {
                return;
            }
            if ($dueMax !== null && $due > $dueMax) {
                return;
            }
            $key = medicap_retest_row_dedupe_key($row);
            if ($key === '||' || isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $output[] = $row;
        };

        foreach (medicap_retest_rows_from_approved_testing($conn, $plantId, $materialType) as $testingRow) {
            $appendRow($testingRow);
        }

        $sql = medicap_retest_awaiting_stock_sql($conn, $plantId, $materialType)." ORDER BY s.retest_date ASC, s.grn_no ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $appendRow(medicap_retest_enrich_stock_row($conn, $row, false));
            }
        }

        $plantSql = ($plantId !== '') ? " AND r.plant_id='".$conn->real_escape_string($plantId)."'" : '';
        $scopeSql = '';
        if ($materialType === 'Raw Material') {
            $scopeSql = " AND (m.material_type = 'Raw Material' OR r.material_code LIKE 'R%' OR r.material_code REGEXP '^[0-9]') ";
        } else if ($materialType === 'Packing Material') {
            $scopeSql = " AND (m.material_type = 'Packing Material' OR r.material_code LIKE 'P%') ";
        } else if ($materialType !== '') {
            $scopeSql = medicap_retest_material_type_sql($conn, $materialType);
        }

        $retestSql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade
                FROM retest r
                LEFT JOIN material m ON r.material_code = m.material_code
                WHERE LOWER(TRIM(COALESCE(r.status,''))) IN ('intimated', 'pending', 'requested', 'sampled')
                ".$plantSql.$scopeSql."
                ORDER BY r.retest_date ASC, r.grn_no ASC";
        $retestRes = $conn->query($retestSql);
        if ($retestRes) {
            while ($retestRow = $retestRes->fetch_assoc()) {
                $st = strtolower(trim((string)($retestRow['status'] ?? '')));
                if ($st === 'sampled') {
                    continue;
                }
                $batch = medicap_retest_resolve_batch_no($retestRow);
                $stock = medicap_retest_find_stock_for_retest(
                    $conn,
                    (string)($retestRow['plant_id'] ?? $plantId),
                    (string)($retestRow['grn_no'] ?? ''),
                    (string)($retestRow['material_code'] ?? ''),
                    $batch,
                    (string)($retestRow['ar_no'] ?? '')
                );
                $appendRow(medicap_retest_enrich_from_retest_row($conn, $retestRow, $stock));
            }
        }

        medicap_ensure_retest_intimation_tables($conn);
        $slipPlantSql = ($plantId !== '') ? " AND s.plant_id='".$conn->real_escape_string($plantId)."'" : '';
        $lineScopeSql = '';
        if ($materialType === 'Raw Material') {
            $lineScopeSql = " AND (l.material_type = 'Raw Material' OR l.material_code LIKE 'R%' OR l.material_code REGEXP '^[0-9]') ";
        } else if ($materialType === 'Packing Material') {
            $lineScopeSql = " AND (l.material_type = 'Packing Material' OR l.material_code LIKE 'P%') ";
        }
        $lineSql = "SELECT l.*, s.plant_id, s.intimation_date, s.status AS slip_status
                FROM retest_intimation_slip_line l
                INNER JOIN retest_intimation_slip s ON s.id = l.slip_id
                WHERE 1=1 ".$slipPlantSql.$lineScopeSql."
                ORDER BY l.retest_date ASC, l.grn_no ASC";
        $lineRes = $conn->query($lineSql);
        if ($lineRes) {
            while ($line = $lineRes->fetch_assoc()) {
                $batch = medicap_retest_resolve_batch_no($line);
                $retestRow = medicap_retest_find_row_for_stock(
                    $conn,
                    (string)($line['plant_id'] ?? $plantId),
                    (string)($line['grn_no'] ?? ''),
                    (string)($line['material_code'] ?? ''),
                    $batch
                );
                if ($retestRow) {
                    $rtSt = strtolower(trim((string)($retestRow['status'] ?? '')));
                    if (in_array($rtSt, array('sampled', 'approve', 'approved', 'done'), true)) {
                        continue;
                    }
                }
                $stock = medicap_retest_find_stock_for_retest(
                    $conn,
                    (string)($line['plant_id'] ?? $plantId),
                    (string)($line['grn_no'] ?? ''),
                    (string)($line['material_code'] ?? ''),
                    $batch,
                    (string)($line['ar_no'] ?? '')
                );
                $pseudoRetest = array_merge($line, array(
                    'plant_id' => $line['plant_id'] ?? $plantId,
                    'batch_no' => $batch,
                ));
                $enriched = medicap_retest_enrich_from_retest_row($conn, $pseudoRetest, $stock);
                if ($enriched !== null) {
                    $slipSt = strtolower(trim((string)($line['slip_status'] ?? '')));
                    if ($slipSt === 'received' && ($enriched['retest_workflow_status'] ?? '') === 'Pending Retest') {
                        $enriched['retest_workflow_status'] = 'Intimated';
                        $enriched['retest_next_step'] = 'QC → Retest → Allocation';
                    }
                    $appendRow($enriched);
                }
            }
        }

        usort($output, function ($a, $b) {
            $da = (string)($a['retest_date'] ?? '');
            $db = (string)($b['retest_date'] ?? '');
            if ($da === $db) {
                return strcmp((string)($a['grn_no'] ?? ''), (string)($b['grn_no'] ?? ''));
            }
            return strcmp($da, $db);
        });

        return $output;
    }
}

if (!function_exists('medicap_retest_store_retest_rows')) {
    /** Unified list for Calendar, Awaiting, and Retest Detail. */
    function medicap_retest_store_retest_rows($conn, $options = array()) {
        $plantId = (string)($options['plant_id'] ?? ($_GET['plant_id'] ?? ''));
        $materialType = (string)($options['material_type'] ?? '');
        try {
            medicap_retest_sync_approved_testing_to_stock($conn, $plantId, $materialType);
        } catch (Throwable $e) {
            // keep calendar working even if stock sync fails
        }
        $fromTesting = medicap_retest_rows_from_approved_testing($conn, $plantId, $materialType);
        $rows = medicap_retest_pending_rows($conn, array(
            'include_overdue' => !isset($options['include_overdue']) || $options['include_overdue'],
            'use_awaiting_stock' => true,
            'material_type' => $materialType,
            'plant_id' => $plantId,
        ));
        return medicap_retest_merge_retest_rows($fromTesting, $rows);
    }
}

if (!function_exists('medicap_retest_json_echo_rows')) {
    function medicap_retest_json_echo_rows($conn, $options = array()) {
        header('Content-Type: application/json; charset=utf-8');
        $plantId = medicap_retest_normalize_plant_id((string)($options['plant_id'] ?? ($_GET['plant_id'] ?? '')));
        $materialType = (string)($options['material_type'] ?? ($_GET['material_type'] ?? 'Raw Material'));
        $dueMax = array_key_exists('due_max', $options) ? (int)$options['due_max'] : null;
        $testDueIn = medicap_retest_test_due_in_days();
        $fetchDueMax = ($dueMax !== null && $testDueIn === null) ? $dueMax : null;
        $rows = array();
        try {
            if (function_exists('medicap_retest_awaiting_rows')) {
                $rows = medicap_retest_awaiting_rows($conn, array(
                    'include_overdue' => !isset($options['include_overdue']) || $options['include_overdue'],
                    'material_type' => $materialType,
                    'plant_id' => $plantId,
                    'due_max' => $fetchDueMax,
                ));
            } else if (function_exists('medicap_retest_store_retest_rows')) {
                $rows = medicap_retest_store_retest_rows($conn, $options);
            }
        } catch (Throwable $e) {
            $rows = array();
        }
        if (!is_array($rows) || count($rows) === 0) {
            $fallback = medicap_retest_fallback_pending_from_testing($conn, $plantId, $materialType);
            if ($fetchDueMax === null) {
                $rows = $fallback;
            } else {
                $rows = array_values(array_filter($fallback, function ($row) use ($fetchDueMax) {
                    return isset($row['due_days']) && $row['due_days'] !== null && (int)$row['due_days'] <= $fetchDueMax;
                }));
            }
        }
        if ($testDueIn !== null && is_array($rows)) {
            $rows = array_map(function ($row) {
                return medicap_retest_apply_test_due_to_row($row);
            }, $rows);
            if ($dueMax !== null) {
                $rows = array_values(array_filter($rows, function ($row) use ($dueMax) {
                    return isset($row['due_days']) && $row['due_days'] !== null && (int)$row['due_days'] <= $dueMax;
                }));
            }
        }
        echo json_encode(is_array($rows) ? $rows : array());
    }
}

if (!function_exists('medicap_retest_calendar_rows')) {
    function medicap_retest_calendar_rows($conn) {
        return medicap_retest_store_retest_rows($conn, array(
            'include_overdue' => true,
            'material_type' => (string)($_GET['material_type'] ?? ''),
            'plant_id' => (string)($_GET['plant_id'] ?? ''),
        ));
    }
}

if (!function_exists('medicap_retest_expired_rows')) {
    function medicap_retest_expired_rows($conn) {
        $output = array();
        $plantId = (string)($_GET['plant_id'] ?? '');
        $plantSql = '';
        if ($plantId !== '') {
            $plantEsc = $conn->real_escape_string($plantId);
            $plantSql = " AND s.plant_id = '".$plantEsc."' ";
        }
        $materialType = medicap_retest_material_type_sql($conn, (string)($_GET['material_type'] ?? ''));
        $today = date('Y-m-d');
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name
                FROM stock_book s
                LEFT JOIN material m ON s.material_code = m.material_code
                LEFT JOIN vendor v ON s.vendor_no = v.vendor_no
                WHERE s.status = 'Approved'
                AND s.exp_date IS NOT NULL AND s.exp_date != '' AND s.exp_date != '0000-00-00'
                AND DATE(s.exp_date) < '".$today."'
                ".$plantSql.$materialType."
                ORDER BY s.exp_date ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $exp = date('Y-m-d', strtotime($row['exp_date']));
                $todayDt = new DateTime($today);
                $expDt = new DateTime($exp);
                $row['expired_days'] = (int)$todayDt->diff($expDt)->format('%a');
                $output[] = $row;
            }
        }
        return $output;
    }
}

if (!function_exists('medicap_retest_find_pending_stock')) {
    function medicap_retest_find_pending_stock($conn, $plantId, $grn, $code, $batch, $arNo = '') {
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $grnEsc = $conn->real_escape_string((string)$grn);
        $codeEsc = $conn->real_escape_string((string)$code);
        $plantSql = ($plantId !== '') ? " AND s.plant_id='".$plantEsc."'" : '';
        $recvSql = " AND (s.grn_no='".$grnEsc."' OR s.receiving_no='".$grnEsc."' OR s.ar_no='".$grnEsc."')";
        $baseSql = "SELECT s.*, m.material_subtype FROM stock_book s
            LEFT JOIN material m ON s.material_code = m.material_code
            WHERE s.material_code='".$codeEsc."'
            AND ".trim(medicap_retest_stock_status_sql('s'))."
            AND LOWER(TRIM(COALESCE(s.retest_status,''))) IN ('pending', 'in_sampling', 'sampling', 'na', '')
            ".$plantSql.$recvSql;

        $candidates = array();
        foreach (array($batch, $arNo) as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }
        foreach ($candidates as $candidate) {
            $batchEsc = $conn->real_escape_string($candidate);
            $res = $conn->query($baseSql." AND s.batch_no='".$batchEsc."' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc();
            }
            $resAr = $conn->query($baseSql." AND s.ar_no='".$batchEsc."' LIMIT 1");
            if ($resAr && $resAr->num_rows > 0) {
                return $resAr->fetch_assoc();
            }
        }

        $resAll = $conn->query($baseSql." ORDER BY s.id DESC");
        if ($resAll && $resAll->num_rows === 1) {
            return $resAll->fetch_assoc();
        }
        if ($resAll && $resAll->num_rows > 1) {
            return $resAll->fetch_assoc();
        }
        return medicap_retest_find_stock_for_retest($conn, $plantId, $grn, $code, $batch, $arNo);
    }
}

if (!function_exists('medicap_retest_upsert_on_grn_sent')) {
    function medicap_retest_upsert_on_grn_sent($conn, $stock, $retestDate, $plantId, $empId, $entryDate, $batchNo) {
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $grnEsc = $conn->real_escape_string((string)($stock['grn_no'] ?? ''));
        $codeEsc = $conn->real_escape_string((string)($stock['material_code'] ?? ''));
        $batchEsc = $conn->real_escape_string((string)$batchNo);
        $rtDateEsc = $conn->real_escape_string((string)$retestDate);
        $empEsc = $conn->real_escape_string((string)$empId);
        $arNo = $conn->real_escape_string(trim((string)($stock['ar_no'] ?? '')));
        $qty = $conn->real_escape_string((string)($stock['qty'] ?? ''));
        $unit = $conn->real_escape_string((string)($stock['unit'] ?? ''));
        $releaseDate = $conn->real_escape_string((string)($stock['release_date'] ?? ''));
        $mfgDate = $conn->real_escape_string((string)($stock['mfg_date'] ?? ''));
        $expDate = $conn->real_escape_string((string)($stock['exp_date'] ?? ''));

        $check = $conn->query("SELECT id FROM retest WHERE plant_id='".$plantEsc."' AND grn_no='".$grnEsc."' AND material_code='".$codeEsc."' AND batch_no='".$batchEsc."' ORDER BY id DESC LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $existing = $check->fetch_assoc();
            $conn->query("UPDATE retest SET retest_date='".$rtDateEsc."', qty='".$qty."', unit='".$unit."', ar_no='".$arNo."',
                release_date='".$releaseDate."', mfg_date='".$mfgDate."', exp_date='".$expDate."', status='requested',
                entry_by='".$empEsc."', entry_date='".$entryDate."' WHERE id='".$existing['id']."'");
            return;
        }

        $conn->query("INSERT INTO retest (plant_id, retest_date, grn_no, material_code, batch_no, qty, unit, ar_no, release_date, mfg_date, exp_date, status, entry_by, entry_date)
            VALUES ('".$plantEsc."', '".$rtDateEsc."', '".$grnEsc."', '".$codeEsc."', '".$batchEsc."', '".$qty."', '".$unit."',
            '".$arNo."', '".$releaseDate."', '".$mfgDate."', '".$expDate."', 'requested', '".$empEsc."', '".$entryDate."')");
    }
}

if (!function_exists('medicap_send_retest_grn')) {
    /** Opens QC retest sampling using receiving no. — no separate retest GRN number. */
    function medicap_send_retest_grn($conn, $input, $plantId, $empId, $entryDate) {
        medicap_ensure_sampling_retest_id_column($conn);
        if (!isset($input['containers']) || (float)$input['containers'] <= 0) {
            return array('status' => 'failed', 'msg' => 'Containers must be greater than 0.');
        }
        $receivingNo = trim((string)($input['grn_no'] ?? ''));
        $code = trim((string)($input['material_code'] ?? ''));
        if ($receivingNo === '' || $code === '') {
            return array('status' => 'failed', 'msg' => 'Receiving no. and Material Code are required.');
        }

        $batch = medicap_retest_resolve_batch_no($input);
        if ($batch === '') {
            return array('status' => 'failed', 'msg' => 'Batch No / Medicap Lot No is required.');
        }

        $retestId = (int)($input['retest_id'] ?? 0);
        if ($retestId <= 0) {
            $retestRow = medicap_retest_find_row_for_stock($conn, $plantId, $receivingNo, $code, $batch);
            $retestId = (int)($retestRow['id'] ?? 0);
        }
        if (medicap_retest_has_sampling($conn, $receivingNo, $retestId)) {
            return array('status' => 'failed', 'msg' => 'Retest sampling is already open for this receiving no.');
        }

        $arNoInput = trim((string)($input['ar_no'] ?? ''));
        $stock = medicap_retest_find_pending_stock($conn, $plantId, $receivingNo, $code, $batch, $arNoInput);
        if ($stock === null) {
            return array('status' => 'failed', 'msg' => 'Approved pending retest stock not found for this receiving no.');
        }

        $batch = medicap_retest_resolve_batch_no($stock);
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $recvEsc = $conn->real_escape_string($receivingNo);
        $codeEsc = $conn->real_escape_string($code);
        $batchEsc = $conn->real_escape_string($batch);
        $stock['material_subtype'] = $stock['material_subtype'] ?? '';

        $retestDate = medicap_retest_resolve_date($conn, $stock);
        if ($retestDate === null) {
            return array('status' => 'failed', 'msg' => 'Could not determine retest date.');
        }
        $dueDays = medicap_retest_compute_due_days($retestDate);
        $noticeDays = medicap_retest_send_grn_due_days();
        if (empty($input['skip_due_check']) && ($dueDays === null || $dueDays > $noticeDays)) {
            return array('status' => 'failed', 'msg' => 'Retest sampling is allowed only when due within '.$noticeDays.' days or overdue.');
        }

        $specNo = '';
        $specRes = $conn->query("SELECT specification_no FROM specification WHERE material_code='".$codeEsc."' AND LOWER(TRIM(status)) IN ('approve','approved') ORDER BY id DESC LIMIT 1");
        if ($specRes && $specRes->num_rows > 0) {
            $specRow = $specRes->fetch_assoc();
            $specNo = $conn->real_escape_string((string)$specRow['specification_no']);
        }

        $arNo = trim((string)($stock['ar_no'] ?? $arNoInput));
        $containers = $conn->real_escape_string((string)$input['containers']);
        $recvDate = $conn->real_escape_string((string)($stock['grn_date'] ?? $input['grn_date'] ?? date('Y-m-d')));
        $mfgDate = $conn->real_escape_string((string)($stock['mfg_date'] ?? ''));
        $expDate = $conn->real_escape_string((string)($stock['exp_date'] ?? ''));
        $empEsc = $conn->real_escape_string((string)$empId);
        $retestIdSql = ($retestId > 0) ? "'".$conn->real_escape_string((string)$retestId)."'" : 'NULL';

        $sql = "INSERT INTO sampling (plant_id, material_code, batch_no, containers, grn_no, old_grn, grn_date, mfg_date, exp_date,
            request_by, request_date, specification_no, ar_no, retest_id, status, entry_by, entry_date)
            VALUES ('".$plantEsc."', '".$codeEsc."', '".$batchEsc."', '".$containers."', '".$recvEsc."', '".$recvEsc."',
            '".$recvDate."', '".$mfgDate."', '".$expDate."', '".$empEsc."', '".$entryDate."', '".$specNo."',
            '".$conn->real_escape_string($arNo)."', ".$retestIdSql.", 'inprocess', '".$empEsc."', '".$entryDate."')";

        if (!$conn->query($sql)) {
            return array('status' => 'failed', 'msg' => $conn->error);
        }

        $conn->query("UPDATE stock_book SET retest_status='in_sampling' WHERE plant_id='".$plantEsc."' AND grn_no='".$recvEsc."' AND material_code='".$codeEsc."' AND batch_no='".$batchEsc."'");

        medicap_retest_upsert_on_grn_sent($conn, $stock, $retestDate, $plantId, $empId, $entryDate, $batch);

        return array(
            'status' => 'success',
            'msg' => 'Retest sampling opened for receiving no. '.$receivingNo.'.',
            'receiving_no' => $receivingNo,
            'retest_id' => $retestId,
        );
    }
}

if (!function_exists('medicap_retest_resolve_containers')) {
    function medicap_retest_resolve_containers($conn, $plantId, $retestRow) {
        $containers = (int)($retestRow['containers'] ?? 0);
        if ($containers > 0) {
            return $containers;
        }
        $grn = trim((string)($retestRow['grn_no'] ?? ''));
        $code = trim((string)($retestRow['material_code'] ?? ''));
        $batch = medicap_retest_resolve_batch_no($retestRow);
        $arNo = trim((string)($retestRow['ar_no'] ?? ''));
        $stock = medicap_retest_find_pending_stock($conn, $plantId, $grn, $code, $batch, $arNo);
        if ($stock !== null) {
            foreach (array('total_containers', 'containers', 'no_of_containers') as $key) {
                if (isset($stock[$key]) && (int)$stock[$key] > 0) {
                    return (int)$stock[$key];
                }
            }
        }
        return 1;
    }
}

if (!function_exists('medicap_retest_apply_sampling_persons')) {
    function medicap_retest_apply_sampling_persons($conn, $retestRow, $legacyGrnNo = '') {
        medicap_ensure_sampling_retest_id_column($conn);
        $qc = $conn->real_escape_string((string)($retestRow['qc_person'] ?? ''));
        $qcAlt = $conn->real_escape_string((string)($retestRow['qc_alternate_person'] ?? ''));
        $micro = $conn->real_escape_string((string)($retestRow['micro_person'] ?? ''));
        $microAlt = $conn->real_escape_string((string)($retestRow['micro_alternate_person'] ?? ''));
        $retestId = (int)($retestRow['id'] ?? 0);
        if ($retestId > 0) {
            $where = "retest_id='".$conn->real_escape_string((string)$retestId)."'";
        } else {
            $recv = trim((string)($retestRow['grn_no'] ?? $legacyGrnNo));
            if ($recv === '') {
                return;
            }
            $recvEsc = $conn->real_escape_string($recv);
            $where = "(old_grn='".$recvEsc."' OR grn_no='".$recvEsc."' OR grn_no='".$conn->real_escape_string('R-'.$recv)."')";
        }
        $conn->query("UPDATE sampling SET sampling_person='".$qc."', micro_person='".$micro."',
            alternate_qc_person='".$qcAlt."', alternate_micro_person='".$microAlt."'
            WHERE ".$where);
    }
}

if (!function_exists('medicap_retest_push_to_sampling')) {
    /** Open retest sampling queue after QC allocation. */
    function medicap_retest_push_to_sampling($conn, $retestRow, $plantId, $empId, $entryDate) {
        $receivingNo = trim((string)($retestRow['grn_no'] ?? ''));
        if ($receivingNo === '') {
            return array('status' => 'failed', 'msg' => 'Receiving no. is missing.');
        }
        $retestId = (int)($retestRow['id'] ?? 0);
        if (medicap_retest_has_sampling($conn, $receivingNo, $retestId)) {
            medicap_retest_apply_sampling_persons($conn, $retestRow);
            return array('status' => 'skipped', 'msg' => 'Already in Retest Sampling.', 'receiving_no' => $receivingNo);
        }

        $batch = medicap_retest_resolve_batch_no($retestRow);
        $input = array(
            'retest_id' => $retestId,
            'grn_no' => $receivingNo,
            'material_code' => $retestRow['material_code'] ?? '',
            'batch_no' => $batch,
            'ar_no' => $retestRow['ar_no'] ?? '',
            'containers' => medicap_retest_resolve_containers($conn, $plantId, $retestRow),
            'skip_due_check' => true,
        );
        $result = medicap_send_retest_grn($conn, $input, $plantId, $empId, $entryDate);
        if (in_array(($result['status'] ?? ''), array('success', 'skipped'), true)) {
            medicap_retest_apply_sampling_persons($conn, $retestRow);
        }
        return $result;
    }
}

if (!function_exists('medicap_retest_fetch_row_by_id')) {
    function medicap_retest_fetch_row_by_id($conn, $retestId, $plantId = '') {
        $idEsc = $conn->real_escape_string((string)$retestId);
        $plantSql = ($plantId !== '') ? " AND r.plant_id='".$conn->real_escape_string((string)$plantId)."'" : '';
        $res = $conn->query("SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade
            FROM retest r
            LEFT JOIN material m ON r.material_code = m.material_code
            WHERE r.id='".$idEsc."'".$plantSql." LIMIT 1");
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        if ($plantId !== '') {
            return medicap_retest_fetch_row_by_id($conn, $retestId, '');
        }
        return null;
    }
}

if (!function_exists('medicap_retest_resolve_retest_row_for_allocation')) {
    /** Locate retest row by id, or GRN/material/batch when id lookup fails. */
    function medicap_retest_resolve_retest_row_for_allocation($conn, $input, $plantId = '') {
        $retestId = trim((string)($input['retest_id'] ?? $input['id'] ?? ''));
        if ($retestId !== '') {
            $row = medicap_retest_fetch_row_by_id($conn, $retestId, $plantId);
            if ($row !== null) {
                return $row;
            }
        }
        $grn = trim((string)($input['grn_no'] ?? ''));
        $code = trim((string)($input['material_code'] ?? ''));
        $batch = trim((string)($input['batch_no'] ?? ''));
        $arNo = trim((string)($input['ar_no'] ?? ''));
        if ($grn === '' || $code === '') {
            return null;
        }
        foreach (array($plantId, '') as $plant) {
            $row = medicap_retest_find_row_for_stock($conn, $plant, $grn, $code, $batch);
            if ($row !== null) {
                return $row;
            }
            if ($batch === '' && $arNo !== '') {
                $row = medicap_retest_find_row_for_stock($conn, $plant, $grn, $code, $arNo);
                if ($row !== null) {
                    return $row;
                }
            }
            $plantEsc = ($plant !== '') ? " AND plant_id='".$conn->real_escape_string((string)$plant)."'" : '';
            $grnEsc = $conn->real_escape_string($grn);
            $codeEsc = $conn->real_escape_string($code);
            $res = $conn->query("SELECT * FROM retest WHERE grn_no='".$grnEsc."' AND material_code='".$codeEsc."'".$plantEsc."
                ORDER BY id DESC LIMIT 1");
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc();
            }
        }
        return null;
    }
}

if (!function_exists('medicap_retest_allocate_sampling_person')) {
    function medicap_retest_allocate_sampling_person($conn, $input, $plantId, $empId, $entryDate) {
        if (!is_array($input)) {
            $input = array();
        }
        $qcPerson = trim((string)($input['qc_person'] ?? ''));
        $qcAlt = trim((string)($input['qc_alternate_person'] ?? ''));
        $microPerson = trim((string)($input['micro_person'] ?? ''));
        $microAlt = trim((string)($input['micro_alternate_person'] ?? ''));
        if ($qcPerson === '' || $qcAlt === '' || $microPerson === '' || $microAlt === '') {
            return array('status' => 'failed', 'msg' => 'All sampling person fields are required.');
        }

        $retestRow = medicap_retest_resolve_retest_row_for_allocation($conn, $input, $plantId);
        if ($retestRow === null) {
            return array('status' => 'failed', 'msg' => 'Retest line not found.');
        }

        $st = strtolower(trim((string)($retestRow['status'] ?? '')));
        if (in_array($st, array('sampled', 'approve', 'approved', 'done'), true)) {
            return array('status' => 'failed', 'msg' => 'Retest line is already sampled or completed.');
        }

        $retestId = (int)($retestRow['id'] ?? 0);
        if ($retestId <= 0) {
            return array('status' => 'failed', 'msg' => 'Retest line not found.');
        }

        $idEsc = $conn->real_escape_string((string)$retestId);
        $qcEsc = $conn->real_escape_string($qcPerson);
        $qcAltEsc = $conn->real_escape_string($qcAlt);
        $microEsc = $conn->real_escape_string($microPerson);
        $microAltEsc = $conn->real_escape_string($microAlt);
        $sql = "UPDATE retest SET qc_person='".$qcEsc."', qc_alternate_person='".$qcAltEsc."',
                micro_person='".$microEsc."', micro_alternate_person='".$microAltEsc."',
                status='pending' WHERE id='".$idEsc."'";
        if (!$conn->query($sql)) {
            return array('status' => 'failed', 'msg' => $conn->error);
        }

        $retestRow = medicap_retest_fetch_row_by_id($conn, (string)$retestId, '');
        if ($retestRow === null) {
            return array('status' => 'failed', 'msg' => 'Retest line not found after save.');
        }
        $retestRow['qc_person'] = $qcPerson;
        $retestRow['qc_alternate_person'] = $qcAlt;
        $retestRow['micro_person'] = $microPerson;
        $retestRow['micro_alternate_person'] = $microAlt;

        $push = medicap_retest_push_to_sampling($conn, $retestRow, $plantId, $empId, $entryDate);
        if (($push['status'] ?? '') === 'failed') {
            return array(
                'status' => 'partial',
                'msg' => ($push['msg'] ?? 'Person allocated but could not open Retest Sampling.') . ' Check the list below.',
            );
        }
        return array('status' => 'success', 'msg' => 'Sampling person allocated. Open Retest → Sampling to continue.');
    }
}

if (!function_exists('medicap_ensure_retest_intimation_tables')) {
    function medicap_ensure_retest_intimation_tables($conn) {
        $conn->query("CREATE TABLE IF NOT EXISTS retest_intimation_slip (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(32) NOT NULL,
            slip_no VARCHAR(64) NOT NULL,
            intimation_date DATE NOT NULL,
            material_type VARCHAR(64) DEFAULT NULL,
            remarks TEXT,
            status VARCHAR(32) NOT NULL DEFAULT 'sent',
            sent_by VARCHAR(64) DEFAULT NULL,
            sent_date DATETIME DEFAULT NULL,
            received_by VARCHAR(64) DEFAULT NULL,
            received_date DATETIME DEFAULT NULL,
            receive_remarks TEXT,
            entry_date DATETIME DEFAULT NULL,
            UNIQUE KEY uq_slip_no (slip_no),
            KEY idx_plant_status (plant_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS retest_intimation_slip_line (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slip_id INT NOT NULL,
            grn_no VARCHAR(64) NOT NULL,
            grn_date DATE DEFAULT NULL,
            material_code VARCHAR(64) NOT NULL,
            material_name VARCHAR(255) DEFAULT NULL,
            material_type VARCHAR(64) DEFAULT NULL,
            material_subtype VARCHAR(128) DEFAULT NULL,
            batch_no VARCHAR(64) DEFAULT NULL,
            ar_no VARCHAR(64) DEFAULT NULL,
            qty DECIMAL(18,4) DEFAULT NULL,
            unit VARCHAR(32) DEFAULT NULL,
            release_date DATE DEFAULT NULL,
            retest_date DATE DEFAULT NULL,
            due_days INT DEFAULT NULL,
            containers VARCHAR(32) DEFAULT NULL,
            KEY idx_slip (slip_id),
            KEY idx_grn (grn_no, material_code, batch_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('medicap_next_retest_slip_no')) {
    function medicap_next_retest_slip_no($conn, $plantId) {
        $year = date('y');
        $prefix = 'RT-IS/'.$year.'/';
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $prefixEsc = $conn->real_escape_string($prefix);
        $res = $conn->query("SELECT slip_no FROM retest_intimation_slip WHERE plant_id='".$plantEsc."' AND slip_no LIKE '".$prefixEsc."%' ORDER BY id DESC LIMIT 1");
        $next = 1;
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $parts = explode('/', (string)$row['slip_no']);
            $last = (int)end($parts);
            $next = $last + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('medicap_upsert_retest_intimation_row')) {
    function medicap_upsert_retest_intimation_row($conn, $line, $plantId, $empId, $entryDate) {
        $payload = array(
            'material_code' => $line['material_code'] ?? '',
            'grn_no' => $line['grn_no'] ?? '',
            'batch_no' => $line['batch_no'] ?? '',
            'retest_date' => $line['retest_date'] ?? '',
            'ar_no' => $line['ar_no'] ?? '',
            'release_date' => $line['release_date'] ?? '',
            'qty' => $line['qty'] ?? '',
            'unit' => $line['unit'] ?? '',
            'mfg_date' => $line['mfg_date'] ?? '',
            'exp_date' => $line['exp_date'] ?? '',
        );
        $grn = $conn->real_escape_string((string)$payload['grn_no']);
        $code = $conn->real_escape_string((string)$payload['material_code']);
        $batch = $conn->real_escape_string((string)$payload['batch_no']);
        $retestDate = $conn->real_escape_string((string)$payload['retest_date']);
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $check = $conn->query("SELECT id, status FROM retest WHERE plant_id='".$plantEsc."' AND grn_no='".$grn."' AND material_code='".$code."' AND batch_no='".$batch."' AND retest_date='".$retestDate."' ORDER BY id DESC LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $existing = $check->fetch_assoc();
            $st = strtolower(trim((string)($existing['status'] ?? '')));
            if (!in_array($st, array('sampled', 'approve', 'approved', 'done'), true)) {
                $conn->query("UPDATE retest SET status='intimated', entry_by='".$conn->real_escape_string((string)$empId)."', entry_date='".$entryDate."' WHERE id='".$existing['id']."'");
            }
            return (int)$existing['id'];
        }
        $arNo = $conn->real_escape_string((string)$payload['ar_no']);
        $releaseDate = $conn->real_escape_string((string)$payload['release_date']);
        $qty = $conn->real_escape_string((string)$payload['qty']);
        $unit = $conn->real_escape_string((string)$payload['unit']);
        $mfgDate = $conn->real_escape_string((string)$payload['mfg_date']);
        $expDate = $conn->real_escape_string((string)$payload['exp_date']);
        $sql = "INSERT INTO retest (plant_id, material_code, batch_no, grn_no, ar_no, release_date, retest_date, qty, unit, mfg_date, exp_date, status, entry_by, entry_date)
                VALUES ('".$plantEsc."', '".$code."', '".$batch."', '".$grn."', '".$arNo."', '".$releaseDate."', '".$retestDate."', '".$qty."', '".$unit."', '".$mfgDate."', '".$expDate."', 'intimated', '".$conn->real_escape_string((string)$empId)."', '".$entryDate."')";
        if ($conn->query($sql)) {
            return (int)$conn->insert_id;
        }
        return 0;
    }
}

if (!function_exists('medicap_retest_intimation_candidates')) {
    /**
     * WH→QC intimation slip lines: same stock/testing source as Retest Detail,
     * filtered to due within send_grn window and not already intimated/sampling.
     */
    function medicap_retest_intimation_candidates($conn) {
        $plantId = medicap_retest_normalize_plant_id((string)($_GET['plant_id'] ?? ''));
        $materialType = (string)($_GET['material_type'] ?? '');
        $dueDays = medicap_retest_send_grn_due_days();

        $rows = array();
        try {
            if (function_exists('medicap_retest_awaiting_rows')) {
                $rows = medicap_retest_awaiting_rows($conn, array(
                    'plant_id' => $plantId,
                    'material_type' => $materialType,
                    'include_overdue' => true,
                ));
            } else if (function_exists('medicap_retest_store_retest_rows')) {
                $rows = medicap_retest_store_retest_rows($conn, array(
                    'plant_id' => $plantId,
                    'material_type' => $materialType,
                    'include_overdue' => true,
                ));
            } else {
                $rows = medicap_retest_pending_rows($conn, array(
                    'exclude_sent' => false,
                    'include_overdue' => true,
                    'use_awaiting_stock' => true,
                    'material_type' => $materialType,
                    'plant_id' => $plantId,
                ));
            }
        } catch (Throwable $e) {
            $rows = array();
        }

        if (!is_array($rows) || count($rows) === 0) {
            $fallback = medicap_retest_fallback_pending_from_testing($conn, $plantId, $materialType);
            if (is_array($fallback) && count($fallback) > 0) {
                $rows = $fallback;
            }
        }

        $skipWorkflow = array('intimated', 'in retest sampling', 'sampled', 'allocated');
        $output = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $wf = strtolower(trim((string)($row['retest_workflow_status'] ?? '')));
            if (in_array($wf, $skipWorkflow, true)) {
                continue;
            }
            $due = isset($row['due_days']) ? $row['due_days'] : null;
            if ($due === null && !empty($row['retest_date']) && medicap_retest_valid_date($row['retest_date'])) {
                $due = medicap_retest_compute_due_days($row['retest_date']);
                $row['due_days'] = $due;
                $row['due_label'] = medicap_retest_due_label($due);
            }
            if ($due === null || (int)$due > $dueDays) {
                continue;
            }
            $output[] = $row;
        }
        return $output;
    }
}

if (!function_exists('medicap_save_retest_intimation_slip')) {
    function medicap_save_retest_intimation_slip($conn, $input, $plantId, $empId, $entryDate) {
        medicap_ensure_retest_intimation_tables($conn);
        $lines = $input['lines'] ?? array();
        if (!is_array($lines) || count($lines) === 0) {
            return array('status' => 'failed', 'msg' => 'Select at least one material line.');
        }
        $remarks = $conn->real_escape_string(trim((string)($input['remarks'] ?? '')));
        $materialType = $conn->real_escape_string(trim((string)($input['material_type'] ?? '')));
        $intimationDate = $conn->real_escape_string(trim((string)($input['intimation_date'] ?? date('Y-m-d'))));
        $plantEsc = $conn->real_escape_string((string)$plantId);
        $empEsc = $conn->real_escape_string((string)$empId);
        $slipNo = medicap_next_retest_slip_no($conn, $plantId);
        $slipEsc = $conn->real_escape_string($slipNo);

        $sql = "INSERT INTO retest_intimation_slip (plant_id, slip_no, intimation_date, material_type, remarks, status, sent_by, sent_date, entry_date)
                VALUES ('".$plantEsc."', '".$slipEsc."', '".$intimationDate."', '".$materialType."', '".$remarks."', 'sent', '".$empEsc."', '".$entryDate."', '".$entryDate."')";
        if (!$conn->query($sql)) {
            return array('status' => 'failed', 'msg' => $conn->error);
        }
        $slipId = (int)$conn->insert_id;

        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }
            medicap_upsert_retest_intimation_row($conn, $line, $plantId, $empId, $entryDate);
            $grnEsc = $conn->real_escape_string((string)($line['grn_no'] ?? ''));
            $codeEsc = $conn->real_escape_string((string)($line['material_code'] ?? ''));
            $batchEsc = $conn->real_escape_string((string)($line['batch_no'] ?? ''));
            $nameEsc = $conn->real_escape_string((string)($line['material_name'] ?? ''));
            $typeEsc = $conn->real_escape_string((string)($line['material_type'] ?? ''));
            $subtypeEsc = $conn->real_escape_string((string)($line['material_subtype'] ?? ''));
            $arEsc = $conn->real_escape_string((string)($line['ar_no'] ?? ''));
            $qtyEsc = $conn->real_escape_string((string)($line['qty'] ?? ''));
            $unitEsc = $conn->real_escape_string((string)($line['unit'] ?? ''));
            $releaseEsc = $conn->real_escape_string((string)($line['release_date'] ?? ''));
            $retestEsc = $conn->real_escape_string((string)($line['retest_date'] ?? ''));
            $dueEsc = $conn->real_escape_string((string)($line['due_days'] ?? ''));
            $grnDateEsc = $conn->real_escape_string((string)($line['grn_date'] ?? ''));
            $containersEsc = $conn->real_escape_string((string)($line['containers'] ?? ''));
            $conn->query("INSERT INTO retest_intimation_slip_line (slip_id, grn_no, grn_date, material_code, material_name, material_type, material_subtype, batch_no, ar_no, qty, unit, release_date, retest_date, due_days, containers)
                VALUES ('".$slipId."', '".$grnEsc."', '".$grnDateEsc."', '".$codeEsc."', '".$nameEsc."', '".$typeEsc."', '".$subtypeEsc."', '".$batchEsc."', '".$arEsc."', '".$qtyEsc."', '".$unitEsc."', '".$releaseEsc."', '".$retestEsc."', '".$dueEsc."', '".$containersEsc."')");
        }

        return array('status' => 'success', 'msg' => 'Retest intimation slip created.', 'slip_no' => $slipNo, 'slip_id' => $slipId);
    }
}

if (!function_exists('medicap_retest_intimation_slip_rows')) {
    function medicap_retest_intimation_slip_rows($conn, $statusFilter = '') {
        medicap_ensure_retest_intimation_tables($conn);
        $plantId = $conn->real_escape_string((string)($_GET['plant_id'] ?? ''));
        $plantSql = ($plantId !== '') ? " AND s.plant_id = '".$plantId."'" : '';
        $statusSql = '';
        if ($statusFilter !== '') {
            $statusSql = " AND LOWER(TRIM(s.status)) = '".$conn->real_escape_string(strtolower($statusFilter))."' ";
        }
        $output = array();
        $sql = "SELECT s.*, (SELECT COUNT(*) FROM retest_intimation_slip_line l WHERE l.slip_id = s.id) AS line_count
                FROM retest_intimation_slip s WHERE 1=1 ".$plantSql.$statusSql." ORDER BY s.id DESC";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $output[] = $row;
            }
        }
        return $output;
    }
}

if (!function_exists('medicap_retest_intimation_slip_detail')) {
    function medicap_retest_intimation_slip_detail($conn, $slipId) {
        medicap_ensure_retest_intimation_tables($conn);
        $idEsc = $conn->real_escape_string((string)$slipId);
        $header = null;
        $res = $conn->query("SELECT * FROM retest_intimation_slip WHERE id='".$idEsc."' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $header = $res->fetch_assoc();
        }
        $lines = array();
        $resL = $conn->query("SELECT * FROM retest_intimation_slip_line WHERE slip_id='".$idEsc."' ORDER BY id ASC");
        if ($resL) {
            while ($row = $resL->fetch_assoc()) {
                $lines[] = $row;
            }
        }
        return array('header' => $header, 'lines' => $lines);
    }
}

if (!function_exists('medicap_receive_retest_intimation_slip')) {
    function medicap_receive_retest_intimation_slip($conn, $input, $empId, $entryDate) {
        medicap_ensure_retest_intimation_tables($conn);
        $slipId = $conn->real_escape_string((string)($input['slip_id'] ?? $input['id'] ?? ''));
        if ($slipId === '') {
            return array('status' => 'failed', 'msg' => 'Slip not found.');
        }
        $check = $conn->query("SELECT id, status FROM retest_intimation_slip WHERE id='".$slipId."' LIMIT 1");
        if (!$check || $check->num_rows === 0) {
            return array('status' => 'failed', 'msg' => 'Slip not found.');
        }
        $row = $check->fetch_assoc();
        if (strtolower(trim((string)$row['status'])) === 'received') {
            return array('status' => 'success', 'msg' => 'Slip already received.');
        }
        $remarks = $conn->real_escape_string(trim((string)($input['receive_remarks'] ?? '')));
        $empEsc = $conn->real_escape_string((string)$empId);
        $sql = "UPDATE retest_intimation_slip SET status='received', received_by='".$empEsc."', received_date='".$entryDate."', receive_remarks='".$remarks."' WHERE id='".$slipId."'";
        if ($conn->query($sql)) {
            return array('status' => 'success', 'msg' => 'Intimation slip received by QC.');
        }
        return array('status' => 'failed', 'msg' => $conn->error);
    }
}
