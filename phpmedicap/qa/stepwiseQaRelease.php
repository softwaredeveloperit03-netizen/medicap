<?php
require '../db.php';
require '../token.php';
$pdfTypes = ['downloadStepwiseReviewForm', 'downloadFinalReleaseForm'];
if (in_array($_GET['type'] ?? '', $pdfTypes, true)) {
    require '../tcpdf/tcpdf.php';
}
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
$entry_date = date('Y-m-d H:i:s');

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    $txt = '{"process":"FRONTEND","token":"' . $token . '","action":"' . ($_GET['type'] ?? '') . '","actiontime":"' . $entry_date . '","department":"' . $_GET['department'] . '","emp_id":"' . $_GET['emp_id'] . '","method":"' . $_SERVER['REQUEST_METHOD'] . '","REMOTE_ADDR":"' . $_SERVER['REMOTE_ADDR'] . '"}';
    file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    function esc($conn, $value)
    {
        return $conn->real_escape_string($value ?? '');
    }

    function decodeJsonField($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value ?? '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    function formatDateDisplay($value)
    {
        if (empty($value) || $value === '0000-00-00') {
            return '';
        }
        return date('d-m-Y', strtotime($value));
    }

    function dateFilterSql($conn, $fromDate, $toDate, $column)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE($column) BETWEEN '" . esc($conn, $fromDate) . "' AND '" . esc($conn, $toDate) . "'";
        }
        return '';
    }

    function getEmpName($conn, $empId)
    {
        $empName = $empId;
        $nameSql = "SELECT firstname, lastname FROM employee WHERE emp_id='" . esc($conn, $empId) . "' LIMIT 1";
        $nameResult = $conn->query($nameSql);
        if ($nameResult && $nameResult->num_rows > 0) {
            $empRow = $nameResult->fetch_assoc();
            $fullName = trim(($empRow['firstname'] ?? '') . ' ' . ($empRow['lastname'] ?? ''));
            if ($fullName !== '') {
                $empName = $fullName;
            }
        }
        return $empName;
    }

    function formHeaderHtml($title, $formNo, $sopRef = 'SOP-QA-035', $effectiveDate = 'APR 15 2025')
    {
        return '<h3 style="text-align:center;">' . htmlspecialchars($title) . '</h3>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:15%;"><b>FORM NO.:</b></td>
                <td style="width:35%;">' . htmlspecialchars($formNo) . '</td>
                <td style="width:15%;"><b>REF:</b></td>
                <td style="width:35%;">' . htmlspecialchars($sopRef) . '</td>
            </tr>
            <tr>
                <td><b>REVISION NO.:</b></td>
                <td>00</td>
                <td><b>EFFECTIVE DATE:</b></td>
                <td>' . htmlspecialchars($effectiveDate) . '</td>
            </tr>
        </table><br>';
    }

    function ensureSqrTables($conn)
    {
        $conn->query("CREATE TABLE IF NOT EXISTS sqr_stepwise_review (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-035-B',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-035',
            product_stage VARCHAR(50) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            lot_number VARCHAR(100) DEFAULT NULL,
            batch_no VARCHAR(100) DEFAULT NULL,
            processing_start_date DATE DEFAULT NULL,
            mfg_date DATE DEFAULT NULL,
            exp_date DATE DEFAULT NULL,
            batch_size VARCHAR(100) DEFAULT NULL,
            checklist_data LONGTEXT DEFAULT NULL,
            conditionally_released VARCHAR(10) DEFAULT 'No',
            cr_no VARCHAR(50) DEFAULT NULL,
            cr_released_by VARCHAR(255) DEFAULT NULL,
            cr_date DATE DEFAULT NULL,
            ncr_list TEXT DEFAULT NULL,
            ncr_status VARCHAR(50) DEFAULT NULL,
            open_items TEXT DEFAULT NULL,
            open_items_status VARCHAR(50) DEFAULT NULL,
            analytical_results_reviewed VARCHAR(10) DEFAULT NULL,
            cleaning_verification_reviewed VARCHAR(10) DEFAULT NULL,
            lot_disposition VARCHAR(50) DEFAULT NULL,
            reviewed_by VARCHAR(255) DEFAULT NULL,
            reviewed_date DATETIME DEFAULT NULL,
            approved_by VARCHAR(255) DEFAULT NULL,
            approved_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Draft',
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS sqr_final_release (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-035-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-035',
            stepwise_review_id INT(11) DEFAULT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            product_name VARCHAR(255) DEFAULT NULL,
            lot_number VARCHAR(100) DEFAULT NULL,
            lot_count VARCHAR(50) DEFAULT NULL,
            inprocess_records LONGTEXT DEFAULT NULL,
            oos_ncr_summary TEXT DEFAULT NULL,
            all_lirs_ncrs_completed VARCHAR(10) DEFAULT NULL,
            final_product_analysis_signed VARCHAR(10) DEFAULT NULL,
            audit_trail_reviewed VARCHAR(10) DEFAULT NULL,
            labelled_submission_only VARCHAR(10) DEFAULT NULL,
            labelled_commercial_use VARCHAR(10) DEFAULT NULL,
            retain_samples_logged VARCHAR(10) DEFAULT NULL,
            release_date DATE DEFAULT NULL,
            qa_director VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'Draft',
            approved_by VARCHAR(255) DEFAULT NULL,
            approved_date DATETIME DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function formatStepwiseRow($row)
    {
        $row['checklist_data'] = decodeJsonField($row['checklist_data'] ?? '[]');
        return $row;
    }

    function formatFinalRow($row)
    {
        $row['inprocess_records'] = decodeJsonField($row['inprocess_records'] ?? '[]');
        return $row;
    }

    function safeQueryRows($conn, $sql)
    {
        $rows = [];
        $result = @$conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    ensureSqrTables($conn);

    if ($_GET['type'] == 'getPendingBatchesForStepwiseReview') {
        $output = [];
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type, p.dosage_form
                FROM samplingfg s
                LEFT JOIN product p ON s.product_code = p.product_code
                WHERE s.status LIKE '%Aprov%' OR s.status LIKE '%Approved%' OR s.status='COAA Aproved'
                ORDER BY s.id DESC LIMIT 100";
        foreach (safeQueryRows($conn, $sql) as $row) {
            $output[] = [
                'product_code' => $row['product_code'] ?? '',
                'product_name' => $row['product_name'] ?? '',
                'grade' => $row['grade'] ?? '',
                'dosage_form' => $row['dosage_form'] ?? '',
                'batch_no' => $row['batch_no'] ?? '',
                'lot_number' => $row['batch_no'] ?? '',
                'mfg_date' => $row['mfg_date'] ?? '',
                'exp_date' => $row['exp_date'] ?? '',
                'batch_size' => $row['batch_size'] ?? $row['sample_qty'] ?? '',
                'ar_no' => $row['arno'] ?? $row['ar_no'] ?? '',
            ];
        }
        if (!count($output)) {
            $bmrSql = "SELECT b.batch_no, b.mfg_date, b.exp_date, b.batch_size, b.status, p.product_code, p.product_name, p.grade, p.dosage_form
                       FROM bmr b LEFT JOIN product p ON b.product_code = p.product_code
                       ORDER BY b.entry_date DESC LIMIT 50";
            foreach (safeQueryRows($conn, $bmrSql) as $row) {
                $output[] = [
                    'product_code' => $row['product_code'] ?? '',
                    'product_name' => $row['product_name'] ?? '',
                    'grade' => $row['grade'] ?? '',
                    'dosage_form' => $row['dosage_form'] ?? '',
                    'batch_no' => $row['batch_no'] ?? '',
                    'lot_number' => $row['batch_no'] ?? '',
                    'mfg_date' => $row['mfg_date'] ?? '',
                    'exp_date' => $row['exp_date'] ?? '',
                    'batch_size' => $row['batch_size'] ?? '',
                    'ar_no' => '',
                ];
            }
        }
        echo json_encode(['status' => 'success', 'batches' => $output]);
    } else if ($_GET['type'] == 'saveStepwiseReview') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }
        $status = esc($conn, $input['status'] ?? 'Draft');
        $checklist = esc($conn, json_encode($input['checklist_data'] ?? []));
        $reviewedBy = esc($conn, $input['reviewed_by'] ?? getEmpName($conn, $_GET['emp_id']));
        $reviewedDateSql = $status === 'Pending QA Manager' ? "'$entry_date'" : 'NULL';
        $id = intval($input['id'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE sqr_stepwise_review SET
                product_stage='" . esc($conn, $input['product_stage'] ?? '') . "',
                product_code='" . esc($conn, $input['product_code'] ?? '') . "',
                product_name='" . esc($conn, $input['product_name'] ?? '') . "',
                lot_number='" . esc($conn, $input['lot_number'] ?? '') . "',
                batch_no='" . esc($conn, $input['batch_no'] ?? '') . "',
                processing_start_date=" . (empty($input['processing_start_date']) ? 'NULL' : "'" . esc($conn, $input['processing_start_date']) . "'") . ",
                mfg_date=" . (empty($input['mfg_date']) ? 'NULL' : "'" . esc($conn, $input['mfg_date']) . "'") . ",
                exp_date=" . (empty($input['exp_date']) ? 'NULL' : "'" . esc($conn, $input['exp_date']) . "'") . ",
                batch_size='" . esc($conn, $input['batch_size'] ?? '') . "',
                checklist_data='$checklist',
                conditionally_released='" . esc($conn, $input['conditionally_released'] ?? 'No') . "',
                cr_no='" . esc($conn, $input['cr_no'] ?? '') . "',
                cr_released_by='" . esc($conn, $input['cr_released_by'] ?? '') . "',
                cr_date=" . (empty($input['cr_date']) ? 'NULL' : "'" . esc($conn, $input['cr_date']) . "'") . ",
                ncr_list='" . esc($conn, $input['ncr_list'] ?? '') . "',
                ncr_status='" . esc($conn, $input['ncr_status'] ?? '') . "',
                open_items='" . esc($conn, $input['open_items'] ?? '') . "',
                open_items_status='" . esc($conn, $input['open_items_status'] ?? '') . "',
                analytical_results_reviewed='" . esc($conn, $input['analytical_results_reviewed'] ?? '') . "',
                cleaning_verification_reviewed='" . esc($conn, $input['cleaning_verification_reviewed'] ?? '') . "',
                lot_disposition='" . esc($conn, $input['lot_disposition'] ?? '') . "',
                reviewed_by='$reviewedBy',
                reviewed_date=$reviewedDateSql,
                status='$status'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        } else {
            $sql = "INSERT INTO sqr_stepwise_review (
                plant_id, form_no, sop_ref, product_stage, product_code, product_name, lot_number, batch_no,
                processing_start_date, mfg_date, exp_date, batch_size, checklist_data, conditionally_released,
                cr_no, cr_released_by, cr_date, ncr_list, ncr_status, open_items, open_items_status,
                analytical_results_reviewed, cleaning_verification_reviewed, lot_disposition, reviewed_by,
                reviewed_date, status, entry_by, entry_date
            ) VALUES (
                '" . esc($conn, $_GET['plant_id']) . "', 'FQA-035-B', 'SOP-QA-035',
                '" . esc($conn, $input['product_stage'] ?? '') . "',
                '" . esc($conn, $input['product_code'] ?? '') . "',
                '" . esc($conn, $input['product_name'] ?? '') . "',
                '" . esc($conn, $input['lot_number'] ?? '') . "',
                '" . esc($conn, $input['batch_no'] ?? '') . "',
                " . (empty($input['processing_start_date']) ? 'NULL' : "'" . esc($conn, $input['processing_start_date']) . "'") . ",
                " . (empty($input['mfg_date']) ? 'NULL' : "'" . esc($conn, $input['mfg_date']) . "'") . ",
                " . (empty($input['exp_date']) ? 'NULL' : "'" . esc($conn, $input['exp_date']) . "'") . ",
                '" . esc($conn, $input['batch_size'] ?? '') . "',
                '$checklist',
                '" . esc($conn, $input['conditionally_released'] ?? 'No') . "',
                '" . esc($conn, $input['cr_no'] ?? '') . "',
                '" . esc($conn, $input['cr_released_by'] ?? '') . "',
                " . (empty($input['cr_date']) ? 'NULL' : "'" . esc($conn, $input['cr_date']) . "'") . ",
                '" . esc($conn, $input['ncr_list'] ?? '') . "',
                '" . esc($conn, $input['ncr_status'] ?? '') . "',
                '" . esc($conn, $input['open_items'] ?? '') . "',
                '" . esc($conn, $input['open_items_status'] ?? '') . "',
                '" . esc($conn, $input['analytical_results_reviewed'] ?? '') . "',
                '" . esc($conn, $input['cleaning_verification_reviewed'] ?? '') . "',
                '" . esc($conn, $input['lot_disposition'] ?? '') . "',
                '$reviewedBy', $reviewedDateSql, '$status',
                '" . esc($conn, $_GET['emp_id']) . "', '$entry_date'
            )";
        }

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $id > 0 ? $id : $conn->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getStepwiseReviewLog') {
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $sql = "SELECT id, product_stage, product_name, product_code, lot_number, lot_disposition, status, reviewed_by, approved_by, entry_date
                FROM sqr_stepwise_review WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'"
            . dateFilterSql($conn, $fromDate, $toDate, 'entry_date')
            . ' ORDER BY id DESC';
        echo json_encode(safeQueryRows($conn, $sql));
    } else if ($_GET['type'] == 'getStepwiseReviewById') {
        $id = intval($_GET['id'] ?? 0);
        $sql = "SELECT * FROM sqr_stepwise_review WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode(formatStepwiseRow($result->fetch_assoc()));
        } else {
            echo json_encode(['error' => 'Not found']);
        }
    } else if ($_GET['type'] == 'getPendingStepwiseApprovals') {
        $sql = "SELECT id, product_stage, product_name, product_code, lot_number, lot_disposition, reviewed_by, reviewed_date, entry_date
                FROM sqr_stepwise_review WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Pending QA Manager' ORDER BY id DESC";
        echo json_encode(safeQueryRows($conn, $sql));
    } else if ($_GET['type'] == 'approveStepwiseReview') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $action = esc($conn, $input['action'] ?? 'approve');
        $newStatus = $action === 'reject' ? 'Rejected' : 'Approved';
        $sql = "UPDATE sqr_stepwise_review SET status='$newStatus',
                approved_by='" . esc($conn, getEmpName($conn, $_GET['emp_id'])) . "',
                approved_date='$entry_date'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Pending QA Manager'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not update record']);
        }
    } else if ($_GET['type'] == 'getApprovedStepwiseForFinal') {
        $sql = "SELECT id, product_stage, product_name, product_code, lot_number, lot_disposition, approved_by, approved_date
                FROM sqr_stepwise_review
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND product_stage='Finished Product' AND status='Approved'
                AND id NOT IN (SELECT IFNULL(stepwise_review_id,0) FROM sqr_final_release WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "')
                ORDER BY id DESC";
        echo json_encode(safeQueryRows($conn, $sql));
    } else if ($_GET['type'] == 'saveFinalRelease') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }
        $status = esc($conn, $input['status'] ?? 'Draft');
        $records = esc($conn, json_encode($input['inprocess_records'] ?? []));
        $id = intval($input['id'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE sqr_final_release SET
                stepwise_review_id=" . intval($input['stepwise_review_id'] ?? 0) . ",
                product_code='" . esc($conn, $input['product_code'] ?? '') . "',
                product_name='" . esc($conn, $input['product_name'] ?? '') . "',
                lot_number='" . esc($conn, $input['lot_number'] ?? '') . "',
                lot_count='" . esc($conn, $input['lot_count'] ?? '') . "',
                inprocess_records='$records',
                oos_ncr_summary='" . esc($conn, $input['oos_ncr_summary'] ?? '') . "',
                all_lirs_ncrs_completed='" . esc($conn, $input['all_lirs_ncrs_completed'] ?? '') . "',
                final_product_analysis_signed='" . esc($conn, $input['final_product_analysis_signed'] ?? '') . "',
                audit_trail_reviewed='" . esc($conn, $input['audit_trail_reviewed'] ?? '') . "',
                labelled_submission_only='" . esc($conn, $input['labelled_submission_only'] ?? '') . "',
                labelled_commercial_use='" . esc($conn, $input['labelled_commercial_use'] ?? '') . "',
                retain_samples_logged='" . esc($conn, $input['retain_samples_logged'] ?? '') . "',
                release_date=" . (empty($input['release_date']) ? 'NULL' : "'" . esc($conn, $input['release_date']) . "'") . ",
                qa_director='" . esc($conn, $input['qa_director'] ?? getEmpName($conn, $_GET['emp_id'])) . "',
                status='$status'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        } else {
            $sql = "INSERT INTO sqr_final_release (
                plant_id, form_no, sop_ref, stepwise_review_id, product_code, product_name, lot_number, lot_count,
                inprocess_records, oos_ncr_summary, all_lirs_ncrs_completed, final_product_analysis_signed,
                audit_trail_reviewed, labelled_submission_only, labelled_commercial_use, retain_samples_logged,
                release_date, qa_director, status, entry_by, entry_date
            ) VALUES (
                '" . esc($conn, $_GET['plant_id']) . "', 'FQA-035-A', 'SOP-QA-035',
                " . intval($input['stepwise_review_id'] ?? 0) . ",
                '" . esc($conn, $input['product_code'] ?? '') . "',
                '" . esc($conn, $input['product_name'] ?? '') . "',
                '" . esc($conn, $input['lot_number'] ?? '') . "',
                '" . esc($conn, $input['lot_count'] ?? '') . "',
                '$records',
                '" . esc($conn, $input['oos_ncr_summary'] ?? '') . "',
                '" . esc($conn, $input['all_lirs_ncrs_completed'] ?? '') . "',
                '" . esc($conn, $input['final_product_analysis_signed'] ?? '') . "',
                '" . esc($conn, $input['audit_trail_reviewed'] ?? '') . "',
                '" . esc($conn, $input['labelled_submission_only'] ?? '') . "',
                '" . esc($conn, $input['labelled_commercial_use'] ?? '') . "',
                '" . esc($conn, $input['retain_samples_logged'] ?? '') . "',
                " . (empty($input['release_date']) ? 'NULL' : "'" . esc($conn, $input['release_date']) . "'") . ",
                '" . esc($conn, $input['qa_director'] ?? getEmpName($conn, $_GET['emp_id'])) . "',
                '$status',
                '" . esc($conn, $_GET['emp_id']) . "', '$entry_date'
            )";
        }
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $id > 0 ? $id : $conn->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getFinalReleaseLog') {
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $sql = "SELECT id, product_name, product_code, lot_number, lot_count, status, qa_director, release_date, entry_date
                FROM sqr_final_release WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "'"
            . dateFilterSql($conn, $fromDate, $toDate, 'entry_date')
            . ' ORDER BY id DESC';
        echo json_encode(safeQueryRows($conn, $sql));
    } else if ($_GET['type'] == 'getFinalReleaseById') {
        $id = intval($_GET['id'] ?? 0);
        $sql = "SELECT * FROM sqr_final_release WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode(formatFinalRow($result->fetch_assoc()));
        } else {
            echo json_encode(['error' => 'Not found']);
        }
    } else if ($_GET['type'] == 'getPendingFinalReleaseApprovals') {
        $sql = "SELECT id, product_name, product_code, lot_number, lot_count, qa_director, release_date, entry_date
                FROM sqr_final_release WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Pending Final Release' ORDER BY id DESC";
        echo json_encode(safeQueryRows($conn, $sql));
    } else if ($_GET['type'] == 'approveFinalRelease') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $action = esc($conn, $input['action'] ?? 'approve');
        $newStatus = $action === 'reject' ? 'Rejected' : 'Released';
        $sql = "UPDATE sqr_final_release SET status='$newStatus',
                approved_by='" . esc($conn, getEmpName($conn, $_GET['emp_id'])) . "',
                approved_date='$entry_date'
                WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' AND status='Pending Final Release'";
        if ($conn->query($sql) && $conn->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not update record']);
        }
    } else if ($_GET['type'] == 'downloadStepwiseReviewForm') {
        $id = intval($_GET['id'] ?? 0);
        $result = $conn->query("SELECT * FROM sqr_stepwise_review WHERE id='$id' LIMIT 1");
        if (!$result || !$result->num_rows) {
            echo 'Not found';
            exit;
        }
        $row = formatStepwiseRow($result->fetch_assoc());
        $_GET['filename'] = 'FQA-035-B_' . ($row['lot_number'] ?? 'Review');
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $html = formHeaderHtml('STEPWISE BATCH RECORD REVIEW & RELEASE', 'FQA-035-B');
        $html .= '<table border="1" cellpadding="3" style="width:100%;font-size:8px;">
            <tr><td><b>Product:</b></td><td>' . htmlspecialchars($row['product_name'] ?? '') . '</td>
                <td><b>Code:</b></td><td>' . htmlspecialchars($row['product_code'] ?? '') . '</td></tr>
            <tr><td><b>Lot #:</b></td><td>' . htmlspecialchars($row['lot_number'] ?? '') . '</td>
                <td><b>Stage:</b></td><td>' . htmlspecialchars($row['product_stage'] ?? '') . '</td></tr>
            <tr><td><b>Disposition:</b></td><td>' . htmlspecialchars($row['lot_disposition'] ?? '') . '</td>
                <td><b>Status:</b></td><td>' . htmlspecialchars($row['status'] ?? '') . '</td></tr>
        </table><br>';
        foreach ($row['checklist_data'] as $item) {
            $html .= '<p style="font-size:7px;"><b>' . intval($item['item_no']) . '.</b> ' . htmlspecialchars($item['label'] ?? '') .
                ' — <b>' . htmlspecialchars($item['status'] ?? '') . '</b></p>';
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output($_GET['filename'] . '.pdf', 'I');
    } else if ($_GET['type'] == 'downloadFinalReleaseForm') {
        $id = intval($_GET['id'] ?? 0);
        $result = $conn->query("SELECT * FROM sqr_final_release WHERE id='$id' LIMIT 1");
        if (!$result || !$result->num_rows) {
            echo 'Not found';
            exit;
        }
        $row = formatFinalRow($result->fetch_assoc());
        $_GET['filename'] = 'FQA-035-A_' . ($row['lot_number'] ?? 'Release');
        $_GET['pdftype'] = 'onlyheader';
        include '../pdfimp2.php';
        $html = formHeaderHtml('FINAL RELEASE CHECKLIST FOR FINISHED PRODUCT', 'FQA-035-A');
        $html .= '<table border="1" cellpadding="3" style="width:100%;font-size:8px;">
            <tr><td><b>Product:</b></td><td>' . htmlspecialchars($row['product_name'] ?? '') . '</td>
                <td><b>Lot #:</b></td><td>' . htmlspecialchars($row['lot_number'] ?? '') . '</td></tr>
            <tr><td><b>Count:</b></td><td>' . htmlspecialchars($row['lot_count'] ?? '') . '</td>
                <td><b>Release Date:</b></td><td>' . formatDateDisplay($row['release_date'] ?? '') . '</td></tr>
            <tr><td colspan="4"><b>OOS/NCR Summary:</b><br>' . nl2br(htmlspecialchars($row['oos_ncr_summary'] ?? '')) . '</td></tr>
            <tr><td><b>QA Director:</b></td><td colspan="3">' . htmlspecialchars($row['qa_director'] ?? '') . '</td></tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output($_GET['filename'] . '.pdf', 'I');
    }
}

$conn->close();
