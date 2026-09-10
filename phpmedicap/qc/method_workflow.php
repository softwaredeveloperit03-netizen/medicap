<?php
require '../db.php';
require '../token.php';
if (is_file(__DIR__ . '/../zuma_schema_helper.php')) {
    require_once '../zuma_schema_helper.php';
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$token = $_GET['token'] ?? '';
$sqlTok = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$resultTok = $conn->query($sqlTok);
$_GET['emp_id'] = '';
$_GET['department'] = '';
if ($resultTok && $resultTok->num_rows > 0) {
    while ($rowTok = $resultTok->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $rowTok['key1'], $rowTok['key2']);
        $parts = explode('$', $string);
        $_GET['emp_id'] = $parts[0] ?? '';
        $_GET['department'] = $parts[1] ?? '';
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

function mw_ensure_columns($conn)
{
    $cols = [
        "method_wf_status VARCHAR(50) DEFAULT NULL",
        "method_log_status VARCHAR(50) DEFAULT NULL",
        "method_wf_remark TEXT NULL",
        "method_checker_remark TEXT NULL",
        "method_approver_remark TEXT NULL",
        "method_checked_by VARCHAR(64) DEFAULT NULL",
        "method_checked_date DATETIME DEFAULT NULL",
        "method_approved_by VARCHAR(64) DEFAULT NULL",
        "method_approved_date DATETIME DEFAULT NULL",
        "method_rejected_by VARCHAR(64) DEFAULT NULL",
        "method_rejected_date DATETIME DEFAULT NULL",
        "method_line_check VARCHAR(20) DEFAULT NULL",
        "method_line_approve VARCHAR(20) DEFAULT NULL",
    ];
    foreach ($cols as $def) {
        $name = trim(explode(' ', $def)[0]);
        $chk = $conn->query("SHOW COLUMNS FROM spec_tests LIKE '" . $conn->real_escape_string($name) . "'");
        if ($chk && $chk->num_rows === 0) {
            @$conn->query("ALTER TABLE spec_tests ADD COLUMN $def");
        }
    }
}

function mw_ensure_revision_table($conn)
{
    if (!function_exists('zuma_ensure_table')) {
        @$conn->query("CREATE TABLE IF NOT EXISTS qc_method_revision_request (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(20) DEFAULT NULL,
            spec_test_id INT DEFAULT NULL,
            specification_no VARCHAR(120) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'pending_qa'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return;
    }
    zuma_ensure_table($conn, 'qc_method_revision_request', "
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(20) DEFAULT NULL,
        spec_test_id INT DEFAULT NULL,
        specification_no VARCHAR(120) DEFAULT NULL,
        document_type VARCHAR(120) DEFAULT 'Test Method',
        document_no VARCHAR(120) DEFAULT NULL,
        document_name VARCHAR(255) DEFAULT NULL,
        test_name VARCHAR(255) DEFAULT NULL,
        subtest VARCHAR(255) DEFAULT NULL,
        source_department VARCHAR(120) DEFAULT NULL,
        revision_reason TEXT,
        proposed_changes TEXT,
        effective_date DATE DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'pending_qa',
        qa_remark TEXT,
        qa_approved_by VARCHAR(50) DEFAULT NULL,
        qa_approved_date DATETIME DEFAULT NULL,
        cc_no VARCHAR(120) DEFAULT NULL,
        cc_id INT DEFAULT NULL,
        method_edit_link VARCHAR(500) DEFAULT NULL,
        entry_by VARCHAR(50) DEFAULT NULL,
        entry_date DATETIME DEFAULT NULL,
        INDEX idx_spec_test (spec_test_id),
        INDEX idx_status (status),
        INDEX idx_plant (plant_id)
    ");
}

function mw_table_columns($conn, $table)
{
    $cols = [];
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $res = @$conn->query("SHOW COLUMNS FROM `" . $safe . "`");
    if ($res) {
        while ($c = $res->fetch_assoc()) {
            $cols[$c['Field']] = true;
        }
    }
    return $cols;
}

function mw_ensure_spec_moa_columns($conn)
{
    // TEXT cannot use DEFAULT on older MySQL — omit DEFAULT to avoid ALTER failure.
    $cols = [
        "moa_wf_status VARCHAR(50) NULL",
        "moa_wf_remark TEXT NULL",
        "moa_checker_remark TEXT NULL",
        "moa_approver_remark TEXT NULL",
    ];
    foreach ($cols as $def) {
        $name = trim(explode(' ', $def)[0]);
        $chk = @$conn->query("SHOW COLUMNS FROM specification LIKE '" . $conn->real_escape_string($name) . "'");
        if ($chk && $chk->num_rows === 0) {
            @$conn->query("ALTER TABLE specification ADD COLUMN $def");
        }
    }
}

function mw_fetch_spec_queue($conn, $stage, $plantId)
{
    $output = [];
    $plant = $conn->real_escape_string($plantId);
    $plantSql = ($plant !== '') ? " AND sp.plant_id='$plant'" : '';
    $spCols = mw_table_columns($conn, 'specification');
    $hasMoaStatus = isset($spCols['moa_wf_status']);

    if ($stage === 'checking') {
        $wfCond = "EXISTS (SELECT 1 FROM spec_tests st WHERE st.specification_no = sp.specification_no AND st.method_wf_status = 'pending_review')";
    } elseif ($stage === 'approval') {
        $wfCond = "EXISTS (SELECT 1 FROM spec_tests st WHERE st.specification_no = sp.specification_no AND st.method_wf_status = 'pending_approval')
                   AND NOT EXISTS (SELECT 1 FROM spec_tests st2 WHERE st2.specification_no = sp.specification_no AND st2.method_wf_status = 'pending_review')";
    } else {
        $wfCond = "EXISTS (SELECT 1 FROM spec_tests st WHERE st.specification_no = sp.specification_no AND st.method_wf_status IN ('correction','rejected'))";
        if ($hasMoaStatus) {
            $wfCond = "(" . $wfCond . " OR sp.moa_wf_status = 'correction')";
        }
    }

    $select = [
        "sp.id",
        "sp.specification_no",
        "sp.material_code",
        "sp.version_no",
        isset($spCols['stpNo']) ? "sp.stpNo AS method_no" : "'' AS method_no",
        $hasMoaStatus ? "sp.moa_wf_status" : "NULL AS moa_wf_status",
        isset($spCols['moa_wf_remark']) ? "sp.moa_wf_remark" : "NULL AS moa_wf_remark",
        isset($spCols['moa_checker_remark']) ? "sp.moa_checker_remark" : "NULL AS moa_checker_remark",
        isset($spCols['moa_approver_remark']) ? "sp.moa_approver_remark" : "NULL AS moa_approver_remark",
        "m.material_name",
        "m.material_subtype",
        "(SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = sp.specification_no) AS test_count",
        "(SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = sp.specification_no AND st.ismethod = 'YES') AS prepared_count",
    ];

    $sql = "SELECT " . implode(", ", $select) . "
            FROM specification sp
            LEFT JOIN material m ON m.material_code = sp.material_code
            WHERE $wfCond $plantSql
            ORDER BY sp.id DESC";

    $result = @$conn->query($sql);
    if (!$result) {
        $sql = "SELECT sp.id, sp.specification_no, sp.material_code, sp.version_no,
                       '' AS method_no, m.material_name, m.material_subtype
                FROM specification sp
                LEFT JOIN material m ON m.material_code = sp.material_code
                WHERE $wfCond $plantSql
                ORDER BY sp.id DESC";
        $result = @$conn->query($sql);
    }
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (empty($row['method_no'])) {
                $mn = @$conn->query("SELECT MIN(NULLIF(test_method_no,'')) AS mn FROM spec_tests WHERE specification_no='" . $conn->real_escape_string($row['specification_no']) . "'");
                if ($mn && ($mr = $mn->fetch_assoc()) && !empty($mr['mn'])) {
                    $row['method_no'] = $mr['mn'];
                }
            }
            $output[] = $row;
        }
    }
    return $output;
}

function mw_spec_has_unreviewed_lines($conn, $specNo, $stage)
{
    $specEsc = $conn->real_escape_string($specNo);
    if ($stage === 'checking') {
        $sql = "SELECT COUNT(*) AS c FROM spec_tests
                WHERE specification_no='$specEsc' AND method_wf_status='pending_review'
                AND (method_line_check IS NULL OR method_line_check='' OR method_line_check='pending')";
    } else {
        $sql = "SELECT COUNT(*) AS c FROM spec_tests
                WHERE specification_no='$specEsc' AND method_wf_status='pending_approval'
                AND (method_line_approve IS NULL OR method_line_approve='' OR method_line_approve='pending')";
    }
    $r = $conn->query($sql);
    if ($r && ($row = $r->fetch_assoc())) {
        return (int)$row['c'] > 0;
    }
    return false;
}

/** Mark specification MOA complete when all prepared test methods are approved and none remain in workflow. */
function mw_sync_spec_moa_complete($conn, $specNo, $remark = '')
{
    mw_ensure_spec_moa_columns($conn);
    $specEsc = $conn->real_escape_string(trim((string)$specNo));
    if ($specEsc === '') {
        return false;
    }
    $wfRes = $conn->query("SELECT COUNT(*) AS c FROM spec_tests WHERE specification_no='$specEsc'
        AND method_wf_status IN ('pending_review','pending_approval')");
    $wfPending = ($wfRes && ($r = $wfRes->fetch_assoc())) ? (int)$r['c'] : 0;
    if ($wfPending > 0) {
        return false;
    }
    $needRes = $conn->query("SELECT COUNT(*) AS c FROM spec_tests WHERE specification_no='$specEsc'
        AND UPPER(COALESCE(ismethod,''))='YES'");
    $need = ($needRes && ($r = $needRes->fetch_assoc())) ? (int)$r['c'] : 0;
    if ($need === 0) {
        $needRes2 = $conn->query("SELECT COUNT(*) AS c FROM spec_tests st WHERE st.specification_no='$specEsc'
            AND EXISTS (SELECT 1 FROM test_methods tm WHERE tm.spec_test_id = st.id LIMIT 1)");
        $need = ($needRes2 && ($r2 = $needRes2->fetch_assoc())) ? (int)$r2['c'] : 0;
    }
    if ($need === 0) {
        $needRes3 = $conn->query("SELECT COUNT(*) AS c FROM spec_tests WHERE specification_no='$specEsc'");
        $need = ($needRes3 && ($r3 = $needRes3->fetch_assoc())) ? (int)$r3['c'] : 0;
    }
    $appRes = $conn->query("SELECT COUNT(*) AS c FROM spec_tests WHERE specification_no='$specEsc'
        AND (LOWER(COALESCE(method,'')) IN ('approve','approved') OR LOWER(COALESCE(method_wf_status,''))='approved')");
    $app = ($appRes && ($r = $appRes->fetch_assoc())) ? (int)$r['c'] : 0;
    $specWfRes = $conn->query("SELECT LOWER(COALESCE(moa_wf_status,'')) AS wf FROM specification WHERE specification_no='$specEsc' LIMIT 1");
    $specWf = ($specWfRes && ($sw = $specWfRes->fetch_assoc())) ? trim((string)$sw['wf']) : '';
    if ($specWf === 'approved' || ($need > 0 && $app >= $need)) {
        $remarkEsc = $conn->real_escape_string(trim((string)$remark));
        $sets = "ismoa='approve', moa_wf_status='approved'";
        if ($remarkEsc !== '') {
            $sets .= ", moa_approver_remark='$remarkEsc'";
        }
        $conn->query("UPDATE specification SET $sets WHERE specification_no='$specEsc'");
        return true;
    }
    return false;
}

function mw_enrich_spec_test($conn, $row)
{
    if (isset($row['method_details']) && is_string($row['method_details'])) {
        $row['method_details'] = json_decode($row['method_details']);
    }
    $sql1 = "SELECT * FROM specification WHERE specification_no='" . $conn->real_escape_string($row['specification_no']) . "'";
    $result1 = $conn->query($sql1);
    if ($result1 && $result1->num_rows > 0) {
        $row1 = $result1->fetch_assoc();
        $row['material_code'] = $row1['material_code'];
        $row['spec_type'] = $row1['spec_type'];
        $row['product_code'] = $row1['product_code'];
    }
    $specType = $row['spec_type'] ?? '';
    if (in_array($specType, ['Finish Product', 'Inprocess Specification', 'Stability Specification'], true)) {
        $sqlP = "SELECT * FROM product WHERE product_code='" . $conn->real_escape_string($row['product_code'] ?? '') . "'";
        $resP = $conn->query($sqlP);
        if ($resP && $resP->num_rows > 0) {
            $p = $resP->fetch_assoc();
            $row['product_name'] = $p['product_name'];
            $row['grade'] = $p['grade'];
            $row['generic_name'] = $p['generic_name'];
        }
    } else {
        $sqlM = "SELECT * FROM material WHERE material_code='" . $conn->real_escape_string($row['material_code'] ?? '') . "'";
        $resM = $conn->query($sqlM);
        if ($resM && $resM->num_rows > 0) {
            $m = $resM->fetch_assoc();
            $row['material_type'] = $m['material_type'];
            $row['material_subtype'] = $m['material_subtype'];
            $row['material_name'] = $m['material_name'];
            $row['grade'] = $m['grade'];
        }
    }
    $revId = (int)($row['revision_request_id'] ?? 0);
    if ($revId <= 0) {
        $qRev = "SELECT id, status, cc_no FROM qc_method_revision_request WHERE spec_test_id='" . (int)$row['id'] . "' ORDER BY id DESC LIMIT 1";
        $rRev = $conn->query($qRev);
        if ($rRev && $rRev->num_rows > 0) {
            $rev = $rRev->fetch_assoc();
            $row['revision_request_id'] = $rev['id'];
            $row['revision_request_status'] = $rev['status'];
            $row['revision_cc_no'] = $rev['cc_no'];
        }
    }
    return $row;
}

/**
 * Plant scope for spec_tests rows. spec_tests.plant_id is not populated on the
 * raw-material creation path, so filtering on it silently drops every row on a
 * plant with a non-empty plant_id. Scope by the parent specification's plant_id
 * (always populated) via EXISTS so legacy rows are handled correctly.
 */
function mw_plant_scope($plant)
{
    if ($plant === '') {
        return '';
    }
    return " AND EXISTS (SELECT 1 FROM specification sp WHERE sp.specification_no = st.specification_no AND sp.plant_id = '$plant')";
}

function mw_fetch_by_wf($conn, $wfStatus, $plantId)
{
    $output = [];
    $plant = $conn->real_escape_string($plantId);
    $wf = $conn->real_escape_string($wfStatus);
    $scope = mw_plant_scope($plant);
    $sql = "SELECT st.* FROM spec_tests st WHERE st.method_wf_status='$wf'$scope ORDER BY st.id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = mw_enrich_spec_test($conn, $row);
        }
    }
    if ($wfStatus === 'pending_review') {
        $sqlLegacy = "SELECT st.* FROM spec_tests st WHERE (st.method_wf_status IS NULL OR st.method_wf_status='') AND LOWER(st.method) IN ('active') AND st.ismethod='YES'$scope ORDER BY st.id DESC";
        $resLegacy = $conn->query($sqlLegacy);
        if ($resLegacy && $resLegacy->num_rows > 0) {
            while ($row = $resLegacy->fetch_assoc()) {
                $output[] = mw_enrich_spec_test($conn, $row);
            }
        }
    }
    if ($wfStatus === 'pending_approval') {
        $sqlLegacy = "SELECT st.* FROM spec_tests st WHERE (st.method_wf_status IS NULL OR st.method_wf_status='') AND LOWER(st.method)='active'$scope ORDER BY st.id DESC";
        $resLegacy = $conn->query($sqlLegacy);
        if ($resLegacy && $resLegacy->num_rows > 0) {
            while ($row = $resLegacy->fetch_assoc()) {
                $output[] = mw_enrich_spec_test($conn, $row);
            }
        }
    }
    if ($wfStatus === 'correction') {
        $sqlLegacy = "SELECT st.* FROM spec_tests st WHERE st.method_wf_status='rejected'$scope ORDER BY st.id DESC";
        $resLegacy = $conn->query($sqlLegacy);
        if ($resLegacy && $resLegacy->num_rows > 0) {
            while ($row = $resLegacy->fetch_assoc()) {
                $output[] = mw_enrich_spec_test($conn, $row);
            }
        }
    }
    return $output;
}

mw_ensure_columns($conn);
mw_ensure_spec_moa_columns($conn);
@mw_ensure_revision_table($conn);

$type = $_GET['type'] ?? '';

if ($type === 'submitForReview') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $sql = "UPDATE spec_tests
            SET method_wf_status='pending_review',
                method=IF(LOWER(COALESCE(method,'')) IN ('pending','reject'),'Active',method),
                method_wf_remark=NULL
            WHERE id='$id'
              AND (method_wf_status IS NULL OR method_wf_status='' OR method_wf_status IN ('correction','rejected'))";
    echo json_encode(['status' => $conn->query($sql) ? 'success' : 'failed']);
} elseif ($type === 'submitSpecForReview') {
    // Send the ENTIRE Method of Analysis document (all prepared tests of one
    // specification) into the checking queue in a single action. Only tests that
    // have a method prepared (ismethod=YES) and are not already approved are moved
    // to pending_review; tests without a method are skipped.
    $specNo = $conn->real_escape_string($_GET['specification_no'] ?? $input['specification_no'] ?? '');
    if ($specNo === '') {
        echo json_encode(['status' => 'failed', 'message' => 'specification_no required']);
    } else {
        $sql = "UPDATE spec_tests
                SET method_wf_status='pending_review',
                    method=IF(LOWER(COALESCE(method,'')) IN ('pending','reject'),'Active',method),
                    method_wf_remark=NULL,
                    method_line_check='pending',
                    method_line_approve='pending'
                WHERE specification_no='$specNo'
                  AND ismethod='YES'
                  AND LOWER(COALESCE(method,'')) NOT IN ('approve','approved')
                  AND (method_wf_status IS NULL OR method_wf_status='' OR method_wf_status IN ('correction','rejected'))";
        if ($conn->query($sql)) {
            $moved = $conn->affected_rows;
            $conn->query("UPDATE specification SET moa_wf_status='pending_checking', moa_wf_remark=NULL WHERE specification_no='$specNo'");
            // How many tests still have no method prepared (blocks completion).
            $pend = 0;
            $pRes = $conn->query("SELECT COUNT(*) AS c FROM spec_tests WHERE specification_no='$specNo' AND (ismethod IS NULL OR ismethod<>'YES' OR LOWER(COALESCE(method,''))='pending' OR method IS NULL OR method='')");
            if ($pRes && ($pr = $pRes->fetch_assoc())) { $pend = (int)$pr['c']; }
            echo json_encode(['status' => 'success', 'moved' => $moved, 'pending_methods' => $pend]);
        } else {
            echo json_encode(['status' => 'failed', 'message' => $conn->error]);
        }
    }
} elseif ($type === 'getReviewQueue') {
    echo json_encode(mw_fetch_spec_queue($conn, 'checking', $_GET['plant_id'] ?? ''));
} elseif ($type === 'getCorrectionQueue') {
    echo json_encode(mw_fetch_spec_queue($conn, 'correction', $_GET['plant_id'] ?? ''));
} elseif ($type === 'getApprovalQueue') {
    echo json_encode(mw_fetch_spec_queue($conn, 'approval', $_GET['plant_id'] ?? ''));
} elseif ($type === 'getTestReviewQueue') {
    echo json_encode(mw_fetch_by_wf($conn, 'pending_review', $_GET['plant_id'] ?? ''));
} elseif ($type === 'getTestApprovalQueue') {
    echo json_encode(mw_fetch_by_wf($conn, 'pending_approval', $_GET['plant_id'] ?? ''));
} elseif ($type === 'testLineAction') {
    $id = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';
    $stage = $_GET['stage'] ?? 'checking';
    $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? '')));
    $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
    if ($action === 'accept') {
        if ($stage === 'checking') {
            $sql = "UPDATE spec_tests SET method_line_check='accepted' WHERE id='$id' AND method_wf_status='pending_review'";
        } else {
            $sql = "UPDATE spec_tests SET method_line_approve='accepted' WHERE id='$id' AND method_wf_status='pending_approval'";
        }
    } else {
        if ($remark === '') {
            echo json_encode(['status' => 'failed', 'message' => 'Rejection remark is required']);
            exit;
        }
        $lineCol = ($stage === 'checking') ? "method_line_check='rejected'" : "method_line_approve='rejected'";
        $sql = "UPDATE spec_tests SET method_wf_status='correction', method='Active', $lineCol,
                method_wf_remark='$remark', method_rejected_by='$emp', method_rejected_date='$entry_date'
                WHERE id='$id'";
    }
    echo json_encode(['status' => $conn->query($sql) ? 'success' : 'failed']);
} elseif ($type === 'specReviewAction') {
    $specNo = $conn->real_escape_string($_GET['specification_no'] ?? $input['specification_no'] ?? '');
    $action = $_GET['action'] ?? '';
    $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? '')));
    $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
    if ($specNo === '') {
        echo json_encode(['status' => 'failed', 'message' => 'specification_no required']);
        exit;
    }
    if ($action === 'forward') {
        if (mw_spec_has_unreviewed_lines($conn, $specNo, 'checking')) {
            echo json_encode(['status' => 'failed', 'message' => 'Review each test method (Accept/Reject) before forwarding the complete method.']);
            exit;
        }
        $conn->query("UPDATE spec_tests SET method_wf_status='pending_approval', method_line_approve='pending'
                      WHERE specification_no='$specNo' AND method_wf_status='pending_review' AND method_line_check='accepted'");
        $conn->query("UPDATE specification SET moa_wf_status='pending_approval', moa_checker_remark='$remark' WHERE specification_no='$specNo'");
        echo json_encode(['status' => 'success']);
    } else {
        if ($remark === '') {
            echo json_encode(['status' => 'failed', 'message' => 'Rejection remark is required']);
            exit;
        }
        $conn->query("UPDATE spec_tests SET method_wf_status='correction', method='Active',
                      method_wf_remark='$remark', method_rejected_by='$emp', method_rejected_date='$entry_date'
                      WHERE specification_no='$specNo' AND method_wf_status IN ('pending_review','pending_approval')");
        $conn->query("UPDATE specification SET moa_wf_status='correction', moa_wf_remark='$remark' WHERE specification_no='$specNo'");
        echo json_encode(['status' => 'success']);
    }
} elseif ($type === 'specApprovalAction') {
    $specNo = $conn->real_escape_string($_GET['specification_no'] ?? $input['specification_no'] ?? '');
    $action = $_GET['action'] ?? '';
    $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? '')));
    $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
    if ($specNo === '') {
        echo json_encode(['status' => 'failed', 'message' => 'specification_no required']);
        exit;
    }
    if ($action === 'approve') {
        if (mw_spec_has_unreviewed_lines($conn, $specNo, 'approval')) {
            echo json_encode(['status' => 'failed', 'message' => 'Review each test method (Accept/Reject) before final approval of the complete method.']);
            exit;
        }
        $conn->query("UPDATE spec_tests SET method_wf_status='approved', method='approve', method_log_status='active',
                      method_approver_remark='$remark', method_approved_by='$emp', method_approved_date='$entry_date'
                      WHERE specification_no='$specNo' AND method_wf_status='pending_approval' AND method_line_approve='accepted'");
        mw_sync_spec_moa_complete($conn, $specNo, $remark);
        echo json_encode(['status' => 'success']);
    } else {
        if ($remark === '') {
            echo json_encode(['status' => 'failed', 'message' => 'Rejection remark is required']);
            exit;
        }
        $conn->query("UPDATE spec_tests SET method_wf_status='correction', method='Active',
                      method_wf_remark='$remark', method_rejected_by='$emp', method_rejected_date='$entry_date'
                      WHERE specification_no='$specNo' AND method_wf_status='pending_approval'");
        $conn->query("UPDATE specification SET moa_wf_status='correction', moa_wf_remark='$remark' WHERE specification_no='$specNo'");
        echo json_encode(['status' => 'success']);
    }
} elseif ($type === 'reviewAction') {
    $id = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';
    $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? $_GET['remark'] ?? '')));
    $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
    if ($action === 'forward') {
        $sql = "UPDATE spec_tests SET method_wf_status='pending_approval',
                method_checker_remark='$remark', method_checked_by='$emp', method_checked_date='$entry_date'
                WHERE id='$id'";
    } else {
        if ($remark === '') {
            echo json_encode(['status' => 'failed', 'message' => 'Rejection remark is required']);
            exit;
        }
        $sql = "UPDATE spec_tests SET method_wf_status='correction', method='Active',
                method_wf_remark='$remark', method_checker_remark='$remark',
                method_rejected_by='$emp', method_rejected_date='$entry_date'
                WHERE id='$id'";
    }
    echo json_encode(['status' => $conn->query($sql) ? 'success' : 'failed']);
} elseif ($type === 'approvalAction') {
    $id = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';
    $specNo = $conn->real_escape_string($_GET['specification_no'] ?? '');
    if ($action === 'approve') {
        $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? $_GET['remark'] ?? '')));
        $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
        $sql = "UPDATE spec_tests SET method_wf_status='approved', method='approve', method_log_status='active',
                method_approver_remark='$remark', method_approved_by='$emp', method_approved_date='$entry_date'
                WHERE id='$id'";
        if ($conn->query($sql)) {
            if ($specNo !== '') {
                mw_sync_spec_moa_complete($conn, $specNo, $remark);
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'failed']);
        }
    } else {
        $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? $_GET['remark'] ?? '')));
        $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
        if ($remark === '') {
            echo json_encode(['status' => 'failed', 'message' => 'Rejection remark is required']);
            exit;
        }
        $sql = "UPDATE spec_tests SET method_wf_status='correction', method='Active',
                method_wf_remark='$remark', method_approver_remark='$remark',
                method_rejected_by='$emp', method_rejected_date='$entry_date'
                WHERE id='$id'";
        echo json_encode(['status' => $conn->query($sql) ? 'success' : 'failed']);
    }
} elseif ($type === 'getMethodLog') {
    $output = [];
    $plant = $conn->real_escape_string($_GET['plant_id'] ?? '');
    $scope = mw_plant_scope($plant);
    $sql = "SELECT st.* FROM spec_tests st WHERE (st.method_wf_status='approved' OR st.method='approve')$scope ORDER BY st.id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (empty($row['method_log_status'])) {
                $row['method_log_status'] = 'active';
            }
            $output[] = mw_enrich_spec_test($conn, $row);
        }
    }
    echo json_encode($output);
} elseif ($type === 'updateLogStatus') {
    $id = (int)($_GET['id'] ?? 0);
    $logStatus = $conn->real_escape_string($_GET['log_status'] ?? '');
    $allowed = ['active', 'inactive', 'obsolete', 'under_revision'];
    if (!in_array($logStatus, $allowed, true)) {
        echo json_encode(['status' => 'invalid_status']);
        exit;
    }
    $sql = "UPDATE spec_tests SET method_log_status='$logStatus' WHERE id='$id'";
    echo json_encode(['status' => $conn->query($sql) ? 'success' : 'failed']);
} elseif ($type === 'getRevisionTab') {
    $output = [];
    $plant = $conn->real_escape_string($_GET['plant_id'] ?? '');
    $scope = mw_plant_scope($plant);
    $sql = "SELECT st.*, rr.id AS revision_request_id, rr.status AS revision_request_status, rr.cc_no AS revision_cc_no
            FROM spec_tests st
            LEFT JOIN qc_method_revision_request rr ON rr.spec_test_id = st.id AND rr.id = (
              SELECT MAX(id) FROM qc_method_revision_request WHERE spec_test_id = st.id
            )
            WHERE (st.method_wf_status='approved' OR st.method='approve')$scope
            ORDER BY st.id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = mw_enrich_spec_test($conn, $row);
        }
    }
    echo json_encode($output);
} elseif ($type === 'submitRevisionRequest') {
    $specTestId = (int)($input['spec_test_id'] ?? 0);
    $specNo = $conn->real_escape_string($input['specification_no'] ?? '');
    $reason = $conn->real_escape_string($input['revision_reason'] ?? '');
    $changes = $conn->real_escape_string($input['proposed_changes'] ?? '');
    $eff = $conn->real_escape_string($input['effective_date'] ?? '');
    $docNo = $conn->real_escape_string($input['document_no'] ?? '');
    $docName = $conn->real_escape_string($input['document_name'] ?? '');
    $testName = $conn->real_escape_string($input['test_name'] ?? '');
    $subtest = $conn->real_escape_string($input['subtest'] ?? '');
    $dept = $conn->real_escape_string($_GET['department'] ?? 'Quality Control');
    $plant = $conn->real_escape_string($_GET['plant_id'] ?? '');
    $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
    $sql = "INSERT INTO qc_method_revision_request
        (plant_id, spec_test_id, specification_no, document_type, document_no, document_name, test_name, subtest,
         source_department, revision_reason, proposed_changes, effective_date, status, entry_by, entry_date)
        VALUES ('$plant','$specTestId','$specNo','Test Method','$docNo','$docName','$testName','$subtest',
         '$dept','$reason','$changes'," . ($eff ? "'$eff'" : 'NULL') . ",'pending_qa','$emp','$entry_date')";
    if ($conn->query($sql)) {
        $conn->query("UPDATE spec_tests SET method_log_status='under_revision' WHERE id='$specTestId'");
        echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'failed', 'message' => $conn->error]);
    }
} elseif ($type === 'getQaRevisionRequests') {
    $output = [];
    $plant = $conn->real_escape_string($_GET['plant_id'] ?? '');
    $status = $_GET['status'] ?? 'pending_qa';
    $where = "1=1";
    if ($plant !== '') {
        $where .= " AND plant_id='$plant'";
    }
    if ($status !== 'all') {
        $where .= " AND status='" . $conn->real_escape_string($status) . "'";
    }
    $sql = "SELECT * FROM qc_method_revision_request WHERE $where ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} elseif ($type === 'qaRevisionAction') {
    $id = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';
    $remark = $conn->real_escape_string($input['qa_remark'] ?? $_GET['qa_remark'] ?? '');
    $emp = $conn->real_escape_string($_GET['emp_id'] ?? '');
    if ($action === 'approve') {
        $editLink = '/qc/moa/methods/new/' . (int)($_GET['spec_test_id'] ?? $input['spec_test_id'] ?? 0);
        $sql = "UPDATE qc_method_revision_request SET status='qa_approved', qa_remark='$remark',
                qa_approved_by='$emp', qa_approved_date='$entry_date', method_edit_link='$editLink' WHERE id='$id'";
    } else {
        $sql = "UPDATE qc_method_revision_request SET status='qa_rejected', qa_remark='$remark',
                qa_approved_by='$emp', qa_approved_date='$entry_date' WHERE id='$id'";
    }
    if ($conn->query($sql)) {
        if ($action === 'reject') {
            $q = "SELECT spec_test_id FROM qc_method_revision_request WHERE id='$id' LIMIT 1";
            $r = $conn->query($q);
            if ($r && $r->num_rows > 0) {
                $specTestId = (int)$r->fetch_assoc()['spec_test_id'];
                $conn->query("UPDATE spec_tests SET method_log_status='active' WHERE id='$specTestId'");
            }
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'failed']);
    }
} elseif ($type === 'linkChangeControl') {
    $id = (int)($_GET['id'] ?? $input['revision_request_id'] ?? 0);
    $ccNo = $conn->real_escape_string($input['cc_no'] ?? $_GET['cc_no'] ?? '');
    $ccId = (int)($input['cc_id'] ?? $_GET['cc_id'] ?? 0);
    $editLink = $conn->real_escape_string($input['method_edit_link'] ?? '');
    $sql = "UPDATE qc_method_revision_request SET cc_no='$ccNo', cc_id='$ccId'";
    if ($editLink !== '') {
        $sql .= ", method_edit_link='$editLink'";
    }
    $sql .= " WHERE id='$id'";
    echo json_encode(['status' => $conn->query($sql) ? 'success' : 'failed']);
} else {
    echo json_encode(['status' => 'invalid_type']);
}

$conn->close();
