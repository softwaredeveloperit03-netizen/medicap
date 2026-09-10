<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
// Capture any explicit department filter BEFORE token decrypt overwrites $_GET['department']
$requestedFilterDepartment = trim($_GET['for_department'] ?? $_GET['filter_department'] ?? $_GET['department'] ?? '');
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

    function getEmpStamp($conn, $empId)
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
        return $empName . ' (' . $empId . ') - ' . date('d-m-Y H:i');
    }

    function isQaDepartmentName($departmentName)
    {
        $d = strtolower(trim($departmentName ?? ''));
        return $d === 'quality assurance' || $d === 'qa' || strpos($d, 'quality assurance') !== false;
    }

    function empIsDeptHead($conn, $empId, $plantId, $departmentName = null)
    {
        // master always treated as department head for workflow actions
        if (strcasecmp(trim((string) $empId), 'master') === 0) {
            return true;
        }
        $deptFilter = '';
        if ($departmentName !== null && $departmentName !== '') {
            $deptFilter = " AND department='" . esc($conn, $departmentName) . "'";
        }
        $sql = "SELECT dept_head FROM emp_rights WHERE emp_id='" . esc($conn, $empId) . "' AND plant_id='" . esc($conn, $plantId) . "' $deptFilter ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dept_head'] ?? '') === 'Yes';
        }
        return false;
    }

    function empIsQaHead($conn, $empId, $plantId, $userDepartment)
    {
        if (strcasecmp(trim((string) $empId), 'master') === 0) {
            return true;
        }
        if (!isQaDepartmentName($userDepartment)) {
            return false;
        }
        return empIsDeptHead($conn, $empId, $plantId, $userDepartment);
    }

    function requireQaHeadAccess($conn)
    {
        if (!empIsQaHead($conn, $_GET['emp_id'], $_GET['plant_id'], $_GET['department'])) {
            echo json_encode(['status' => 'Unauthorized: QA Head (dept_head) rights required in Quality Assurance']);
            exit;
        }
    }

    /** HOD may authorize if they have dept_head=Yes for the CC initiating department (or are master). */
    function requireHodAccessForCc($conn, $recordDepartment)
    {
        $empId = $_GET['emp_id'] ?? '';
        $plantId = $_GET['plant_id'] ?? '';
        if (strcasecmp(trim((string) $empId), 'master') === 0) {
            return;
        }
        if ($recordDepartment === null || trim((string) $recordDepartment) === '') {
            echo json_encode(['status' => 'Unauthorized: CC department missing']);
            exit;
        }
        if (!empIsDeptHead($conn, $empId, $plantId, $recordDepartment)) {
            echo json_encode(['status' => 'Unauthorized: Department Head rights required for ' . $recordDepartment]);
            exit;
        }
    }

    function getEmpHodDepartments($conn, $empId, $plantId)
    {
        $depts = [];
        if (strcasecmp(trim((string) $empId), 'master') === 0) {
            return ['*']; // all departments
        }
        $sql = "SELECT DISTINCT department FROM emp_rights
            WHERE emp_id='" . esc($conn, $empId) . "'
            AND plant_id='" . esc($conn, $plantId) . "'
            AND dept_head='Yes'
            AND department IS NOT NULL AND department != ''";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $depts[] = $row['department'];
            }
        }
        return $depts;
    }

    function empCanAuthorizeCcDepartment($conn, $empId, $plantId, $recordDepartment)
    {
        if (strcasecmp(trim((string) $empId), 'master') === 0) {
            return true;
        }
        return empIsDeptHead($conn, $empId, $plantId, $recordDepartment);
    }

    function dateFilterSql($fromDate, $toDate, $column)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE($column) BETWEEN '$fromDate' AND '$toDate'";
        }
        return '';
    }

    function nullableDateSql($conn, $value)
    {
        return !empty($value) ? "'" . esc($conn, $value) . "'" : 'NULL';
    }

    function formHeaderHtml($title, $formNo)
    {
        return '<h3 style="text-align:center;">' . htmlspecialchars($title) . '</h3>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:15%;"><b>FORM NO.:</b></td>
                <td style="width:35%;">' . htmlspecialchars($formNo) . '</td>
                <td style="width:15%;"><b>REF:</b></td>
                <td style="width:35%;">SOP-QA-009</td>
            </tr>
            <tr>
                <td><b>REVISION NO.:</b></td>
                <td>00</td>
                <td><b>EFFECTIVE DATE:</b></td>
                <td>APR 16 2025</td>
            </tr>
        </table><br>';
    }

    function getDepartmentCode($conn, $departmentName)
    {
        $departmentCode = 'QA';
        $sql = "SELECT department_code FROM department WHERE department_name='" . esc($conn, $departmentName) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['department_code'])) {
                $departmentCode = $row['department_code'];
            }
        }
        return $departmentCode;
    }

    function getFinancialYear()
    {
        $currentMonth = date('m');
        if ($currentMonth >= 4) {
            return date('y') . '-' . date('y', strtotime('+1 year'));
        }
        return date('y', strtotime('-1 year')) . '-' . date('y');
    }

    /** SOP-QA-009 / FQA-009-B: CCR # = YY/XXX (calendar year + 3-digit sequence) */
    function generateCcNo($conn, $plantId, $departmentName)
    {
        $departmentCode = getDepartmentCode($conn, $departmentName);
        $yearYy = date('y');
        $financialYear = $yearYy;
        $sqlSr = "SELECT MAX(sr_no) AS max_sr FROM cc_procedure_initiation
            WHERE plant_id='" . esc($conn, $plantId) . "'
            AND financial_year='" . esc($conn, $financialYear) . "'
            AND cc_no LIKE '" . esc($conn, $yearYy) . "/%'";
        $resultSr = $conn->query($sqlSr);
        $nextSr = 1;
        if ($resultSr && $resultSr->num_rows > 0) {
            $rowSr = $resultSr->fetch_assoc();
            if (!empty($rowSr['max_sr'])) {
                $nextSr = ((int) $rowSr['max_sr']) + 1;
            }
        }
        return [
            'cc_no' => $yearYy . '/' . str_pad((string) $nextSr, 3, '0', STR_PAD_LEFT),
            'department_code' => $departmentCode,
            'financial_year' => $financialYear,
            'sr_no' => $nextSr,
        ];
    }

    function ensureColumn($conn, $table, $column, $definition)
    {
        $check = $conn->query("SHOW COLUMNS FROM $table LIKE '" . esc($conn, $column) . "'");
        if ($check && $check->num_rows === 0) {
            if (!$conn->query("ALTER TABLE $table ADD COLUMN $column $definition")) {
                // AFTER <col> can fail if the reference column is missing — retry without position
                $bare = preg_replace('/\s+AFTER\s+`?[A-Za-z0-9_]+`?/i', '', $definition);
                $conn->query("ALTER TABLE $table ADD COLUMN $column $bare");
            }
        }
        $verify = $conn->query("SHOW COLUMNS FROM $table LIKE '" . esc($conn, $column) . "'");
        return $verify && $verify->num_rows > 0;
    }

    function columnExists($conn, $table, $column)
    {
        $check = $conn->query("SHOW COLUMNS FROM $table LIKE '" . esc($conn, $column) . "'");
        return $check && $check->num_rows > 0;
    }

    function ensureChangeControlProcedureTables($conn)
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS cc_procedure_initiation (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-009-A',
                cc_no VARCHAR(50) DEFAULT NULL,
                department_code VARCHAR(50) DEFAULT NULL,
                financial_year VARCHAR(20) DEFAULT NULL,
                sr_no INT(11) DEFAULT NULL,
                department_name VARCHAR(255) DEFAULT NULL,
                date_of_issuance DATE DEFAULT NULL,
                section VARCHAR(255) DEFAULT NULL,
                title_of_change VARCHAR(255) DEFAULT NULL,
                name_of_product_doc VARCHAR(255) DEFAULT NULL,
                batch_no_doc_no VARCHAR(255) DEFAULT NULL,
                change_requested_for LONGTEXT DEFAULT NULL,
                change_requested_for_other VARCHAR(255) DEFAULT NULL,
                existing_procedure TEXT DEFAULT NULL,
                changed_details TEXT DEFAULT NULL,
                justification_of_change TEXT DEFAULT NULL,
                change_due_to_capa VARCHAR(10) DEFAULT NULL,
                capa_reference VARCHAR(255) DEFAULT NULL,
                capa_date DATE DEFAULT NULL,
                tentative_date_closing DATE DEFAULT NULL,
                remark TEXT DEFAULT NULL,
                change_affected_docs LONGTEXT DEFAULT NULL,
                initiated_by VARCHAR(255) DEFAULT NULL,
                auth_by VARCHAR(255) DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS cc_procedure_impact_assessment (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-009-B',
                cc_reference_no VARCHAR(50) DEFAULT NULL,
                assessment_date DATE DEFAULT NULL,
                departments_concerned VARCHAR(255) DEFAULT NULL,
                impact_product_quality VARCHAR(10) DEFAULT NULL,
                impact_validation VARCHAR(10) DEFAULT NULL,
                impact_regulatory VARCHAR(10) DEFAULT NULL,
                impact_training VARCHAR(10) DEFAULT NULL,
                impact_documentation VARCHAR(10) DEFAULT NULL,
                risk_assessment_required VARCHAR(10) DEFAULT NULL,
                recommended_action TEXT DEFAULT NULL,
                qa_assessment_comments TEXT DEFAULT NULL,
                qa_head_approved_by VARCHAR(255) DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS cc_procedure_closure (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plant_id VARCHAR(50) NOT NULL,
                form_no VARCHAR(50) DEFAULT 'FQA-009-C',
                cc_reference_no VARCHAR(50) DEFAULT NULL,
                closure_date DATE DEFAULT NULL,
                implementation_completed VARCHAR(10) DEFAULT NULL,
                training_completed VARCHAR(10) DEFAULT NULL,
                document_revision_completed VARCHAR(10) DEFAULT NULL,
                validation_completed VARCHAR(10) DEFAULT NULL,
                effectiveness_check_required VARCHAR(10) DEFAULT NULL,
                effectiveness_check_result TEXT DEFAULT NULL,
                closure_comments TEXT DEFAULT NULL,
                qa_closure_approved_by VARCHAR(255) DEFAULT NULL,
                entry_by VARCHAR(100) DEFAULT NULL,
                entry_date DATETIME DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($tables as $tableSql) {
            $conn->query($tableSql);
        }
        $initCols = [
            'department_code' => "VARCHAR(50) DEFAULT NULL AFTER cc_no",
            'financial_year' => "VARCHAR(20) DEFAULT NULL AFTER department_code",
            'sr_no' => "INT(11) DEFAULT NULL AFTER financial_year",
            'department_name' => "VARCHAR(255) DEFAULT NULL AFTER sr_no",
            'date_of_issuance' => "DATE DEFAULT NULL AFTER department_name",
            'section' => "VARCHAR(255) DEFAULT NULL AFTER date_of_issuance",
            'name_of_product_doc' => "VARCHAR(255) DEFAULT NULL AFTER title_of_change",
            'batch_no_doc_no' => "VARCHAR(255) DEFAULT NULL AFTER name_of_product_doc",
            'change_requested_for' => "LONGTEXT DEFAULT NULL AFTER batch_no_doc_no",
            'change_requested_for_other' => "VARCHAR(255) DEFAULT NULL AFTER change_requested_for",
            'existing_procedure' => "TEXT DEFAULT NULL AFTER change_requested_for_other",
            'changed_details' => "TEXT DEFAULT NULL AFTER existing_procedure",
            'justification_of_change' => "TEXT DEFAULT NULL AFTER changed_details",
            'tentative_date_closing' => "DATE DEFAULT NULL AFTER capa_date",
            'remark' => "TEXT DEFAULT NULL AFTER tentative_date_closing",
            'change_affected_docs' => "LONGTEXT DEFAULT NULL AFTER remark",
            'auth_by' => "VARCHAR(255) DEFAULT NULL AFTER initiated_by",
            'workflow_status' => "VARCHAR(50) DEFAULT 'Initiated' AFTER status",
            'sop_extra_json' => "LONGTEXT DEFAULT NULL",
            'required_departments' => "LONGTEXT DEFAULT NULL",
            'change_due_to_capa' => "VARCHAR(10) DEFAULT NULL",
            'capa_reference' => "VARCHAR(255) DEFAULT NULL",
            'capa_date' => "DATE DEFAULT NULL",
        ];
        foreach ($initCols as $col => $def) {
            ensureColumn($conn, 'cc_procedure_initiation', $col, $def);
        }
        $assessCols = [
            'issued_by' => "VARCHAR(255) DEFAULT NULL",
            'document_title' => "VARCHAR(255) DEFAULT NULL",
            'document_no' => "VARCHAR(255) DEFAULT NULL",
            'requested_by' => "VARCHAR(255) DEFAULT NULL",
            'comments' => "TEXT DEFAULT NULL",
            'closing_date' => "DATE DEFAULT NULL",
        ];
        foreach ($assessCols as $col => $def) {
            ensureColumn($conn, 'cc_procedure_impact_assessment', $col, $def);
        }
        ensureColumn($conn, 'cc_procedure_closure', 'review_completed_by', "VARCHAR(255) DEFAULT NULL");
    }

    function getInitiationSopExtra($row)
    {
        if (empty($row['sop_extra_json'])) {
            return [];
        }
        $extra = json_decode($row['sop_extra_json'], true);
        return is_array($extra) ? $extra : [];
    }

    function normalizeCcDeptKey($name)
    {
        $s = strtolower(trim((string) $name));
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if ($s === '') {
            return '';
        }
        // QC aliases (do NOT treat as QA)
        if ($s === 'qc'
            || $s === 'quality control'
            || strpos($s, 'quality control') !== false
            || $s === 'qc chemistry analytical services'
            || strpos($s, 'analytical services') !== false
        ) {
            return 'quality_control';
        }
        // QA aliases
        if ($s === 'qa'
            || $s === 'quality assurance'
            || strpos($s, 'quality assurance') !== false
            || strpos($s, 'qa compliance') !== false
        ) {
            return 'quality_assurance';
        }
        if (strpos($s, 'production') !== false) {
            return 'production';
        }
        if (strpos($s, 'warehouse') !== false || strpos($s, 'material management') !== false || strpos($s, 'materials management') !== false || $s === 'store' || strpos($s, 'stores') !== false || $s === 'stores') {
            return 'warehouse';
        }
        if (strpos($s, 'engineering') !== false) {
            return 'engineering';
        }
        if (strpos($s, 'regulatory') !== false || $s === 'ra') {
            return 'regulatory';
        }
        if ($s === 'it' || strpos($s, 'information technology') !== false) {
            return 'it';
        }
        if (strpos($s, 'r d') !== false || strpos($s, 'research') !== false || strpos($s, 'product development') !== false) {
            return 'rnd';
        }
        return $s;
    }

    function ccDepartmentsMatch($a, $b)
    {
        $a = trim((string) $a);
        $b = trim((string) $b);
        if ($a === '' || $b === '') {
            return false;
        }
        if (strcasecmp($a, $b) === 0) {
            return true;
        }
        $ka = normalizeCcDeptKey($a);
        $kb = normalizeCcDeptKey($b);
        if ($ka !== '' && $ka === $kb) {
            return true;
        }
        $al = strtolower($a);
        $bl = strtolower($b);
        return strpos($al, $bl) !== false || strpos($bl, $al) !== false;
    }

    function initiationRequiresDepartment($mapped, $department)
    {
        if (trim((string) $department) === '') {
            return false;
        }
        $required = $mapped['requiredDepartments'] ?? [];
        if (!is_array($required) || count($required) === 0) {
            // fallback: dedicated column may be a JSON string
            if (!empty($mapped['required_departments'])) {
                $decoded = json_decode($mapped['required_departments'], true);
                $required = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', (string) $mapped['required_departments'])));
            }
        }
        if (!is_array($required)) {
            $required = [];
        }
        foreach ($required as $req) {
            if (ccDepartmentsMatch($department, $req)) {
                return true;
            }
        }
        $rows = $mapped['section3Assessments'] ?? [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $label = $row['label'] ?? $row['department'] ?? $row['assessed_department'] ?? '';
                if (ccDepartmentsMatch($department, $label)) {
                    return true;
                }
            }
        }
        return false;
    }

    function resolveViewerDepartment($conn, $tokenDepartment, $requestedFilterDepartment)
    {
        $dept = trim((string) $tokenDepartment);
        if ($dept === '') {
            $dept = trim((string) $requestedFilterDepartment);
        }
        if ($dept === '' && !empty($_GET['emp_id'])) {
            $empRes = $conn->query("SELECT department FROM employee WHERE emp_id='" . esc($conn, $_GET['emp_id']) . "' LIMIT 1");
            if ($empRes && $empRes->num_rows > 0) {
                $dept = trim($empRes->fetch_assoc()['department'] ?? '');
            }
        }
        return $dept;
    }

    /**
     * SOP-QA-009 workflow:
     * Initiated → Authorized (HOD) → Tracking_Logged (FQA-009-B) →
     * Qa_Final_Approved (A §3) → Closed (§5 / B Closing Date) → Evaluated (§7)
     * Optional: Mpd_Reviewed (FQA-009-C) before/with QA Final for Master Production Documents
     * Optional: Cancelled (§6)
     */
    function syncCcWorkflowStatus($conn, $plantId, $ccNo)
    {
        $ccNo = esc($conn, $ccNo);
        $plantId = esc($conn, $plantId);
        $status = 'Initiated';
        // Older DBs may lack FQA-009-B/C columns added later — create before SELECT
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'closing_date', "DATE DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'issued_by', "VARCHAR(255) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'document_title', "VARCHAR(255) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'document_no', "VARCHAR(255) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'requested_by', "VARCHAR(255) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'comments', "TEXT DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_closure', 'review_completed_by', "VARCHAR(255) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_initiation', 'workflow_status', "VARCHAR(50) DEFAULT 'Initiated'");

        $initSql = "SELECT * FROM cc_procedure_initiation WHERE cc_no='$ccNo' AND plant_id='$plantId' LIMIT 1";
        $initRes = $conn->query($initSql);
        if (!$initRes || $initRes->num_rows === 0) {
            return $status;
        }
        $init = $initRes->fetch_assoc();
        $extra = getInitiationSopExtra($init);

        if (!empty($extra['cancelled_by_qa']) || !empty($extra['section6_reason'])) {
            if (!empty($extra['cancelled_by_qa']) && !empty($extra['cancelled_by_dept_mgr'])) {
                $status = 'Cancelled';
                $conn->query("UPDATE cc_procedure_initiation SET workflow_status='$status' WHERE cc_no='$ccNo' AND plant_id='$plantId'");
                return $status;
            }
        }

        if (!empty($init['auth_by']) || !empty($init['hod_approved_by'])) {
            $status = 'Authorized';
        }

        // SELECT * so missing optional columns never fatal the workflow sync
        $assessSql = "SELECT * FROM cc_procedure_impact_assessment
            WHERE cc_reference_no='$ccNo' AND plant_id='$plantId' ORDER BY id DESC LIMIT 1";
        $assessRes = @$conn->query($assessSql);
        $assess = null;
        if ($assessRes && $assessRes->num_rows > 0) {
            $assess = $assessRes->fetch_assoc();
            $status = 'Tracking_Logged';
        }

        $closureSql = "SELECT * FROM cc_procedure_closure
            WHERE cc_reference_no='$ccNo' AND plant_id='$plantId' ORDER BY id DESC LIMIT 1";
        $closureRes = @$conn->query($closureSql);
        $closure = null;
        if ($closureRes && $closureRes->num_rows > 0) {
            $closure = $closureRes->fetch_assoc();
            if (!empty($closure['review_completed_by']) || !empty($closure['qa_closure_approved_by'])) {
                $status = 'Mpd_Reviewed';
            }
        }

        if (!empty($extra['qa_final_approved_by'])) {
            $status = 'Qa_Final_Approved';
        }

        if (!empty($extra['closure_by']) || (!empty($assess) && !empty($assess['closing_date']))) {
            $status = 'Closed';
        }

        if (!empty($extra['evaluated_by_qa'])) {
            $status = 'Evaluated';
        }

        $conn->query("UPDATE cc_procedure_initiation SET workflow_status='$status' WHERE cc_no='$ccNo' AND plant_id='$plantId'");
        return $status;
    }

    function buildCcTravelStages($conn, $plantId, $ccNo)
    {
        $ccNoEsc = esc($conn, $ccNo);
        $plantEsc = esc($conn, $plantId);
        $initSql = "SELECT * FROM cc_procedure_initiation WHERE cc_no='$ccNoEsc' AND plant_id='$plantEsc' LIMIT 1";
        $initRes = $conn->query($initSql);
        if (!$initRes || $initRes->num_rows === 0) {
            return null;
        }
        $init = mapInitiationRow($initRes->fetch_assoc());

        $assess = null;
        $assessSql = "SELECT * FROM cc_procedure_impact_assessment WHERE cc_reference_no='$ccNoEsc' AND plant_id='$plantEsc' ORDER BY id DESC LIMIT 1";
        $assessRes = $conn->query($assessSql);
        if ($assessRes && $assessRes->num_rows > 0) {
            $assess = $assessRes->fetch_assoc();
        }

        $closure = null;
        $closureSql = "SELECT * FROM cc_procedure_closure WHERE cc_reference_no='$ccNoEsc' AND plant_id='$plantEsc' ORDER BY id DESC LIMIT 1";
        $closureRes = $conn->query($closureSql);
        if ($closureRes && $closureRes->num_rows > 0) {
            $closure = $closureRes->fetch_assoc();
        }

        $extra = getInitiationSopExtra($init);
        $stages = [
            [
                'key' => 'initiated',
                'label' => '1. Initiate FQA-009-A (§§1–2)',
                'completed' => !empty($init['initiated_by']),
                'date' => $init['date_of_issuance'] ?? $init['entry_date'] ?? null,
                'user' => $init['initiated_by'] ?? null,
            ],
            [
                'key' => 'authorized',
                'label' => '2. Dept Manager Pre-Approval',
                'completed' => !empty($init['auth_by']) || !empty($init['hod_approved_by']),
                'date' => null,
                'user' => $init['auth_by'] ?? $init['hod_approved_by'] ?? null,
            ],
            [
                'key' => 'tracking',
                'label' => '3. Issue CCR # on FQA-009-B',
                'completed' => $assess !== null,
                'date' => $assess['assessment_date'] ?? null,
                'user' => $assess['issued_by'] ?? $assess['entry_by'] ?? null,
            ],
            [
                'key' => 'mpd_review',
                'label' => '4. MPD Review FQA-009-C (if Master Doc)',
                'completed' => $closure !== null && (!empty($closure['review_completed_by']) || !empty($closure['qa_closure_approved_by'])),
                'date' => $closure['closure_date'] ?? null,
                'user' => $closure['review_completed_by'] ?? $closure['entry_by'] ?? null,
            ],
            [
                'key' => 'qa_final',
                'label' => '5. QA Final Approval (A §3)',
                'completed' => !empty($extra['qa_final_approved_by']),
                'date' => null,
                'user' => $extra['qa_final_approved_by'] ?? null,
            ],
            [
                'key' => 'closed',
                'label' => '6. Verify & Close (A §5 / B Closing Date)',
                'completed' => !empty($extra['closure_by']) || ($assess !== null && !empty($assess['closing_date'])),
                'date' => $assess['closing_date'] ?? null,
                'user' => $extra['closure_by'] ?? null,
            ],
            [
                'key' => 'evaluated',
                'label' => '7. Change Evaluation (A §7)',
                'completed' => !empty($extra['evaluated_by_qa']),
                'date' => null,
                'user' => $extra['evaluated_by_qa'] ?? null,
            ],
        ];
        return [
            'ctrl_no' => $init['cc_no'],
            'department_name' => $init['department_name'] ?? '',
            'title_of_change' => $init['title_of_change'] ?? '',
            'status' => syncCcWorkflowStatus($conn, $plantId, $ccNo),
            'stages' => $stages,
        ];
    }

    function mapInitiationRow($row)
    {
        $row['changeReqFor'] = json_decode($row['change_requested_for'] ?? '[]', true);
        if (!is_array($row['changeReqFor'])) {
            $row['changeReqFor'] = json_decode($row['change_types'] ?? '[]', true);
        }
        $row['changeAffDoc'] = json_decode($row['change_affected_docs'] ?? '[]', true);
        if (!is_array($row['changeAffDoc'])) {
            $row['changeAffDoc'] = [];
        }
        $row['dateOfIssuance'] = $row['date_of_issuance'] ?? $row['initiation_date'] ?? null;
        $row['nameOfProductDoc'] = $row['name_of_product_doc'] ?? $row['product_document_name'] ?? null;
        $row['batchNoDocNo'] = $row['batch_no_doc_no'] ?? $row['batch_document_no'] ?? null;
        $row['existingProcedure'] = $row['existing_procedure'] ?? null;
        $row['changedDetails'] = $row['changed_details'] ?? $row['proposed_change_details'] ?? null;
        $row['justificationOfChange'] = $row['justification_of_change'] ?? $row['justification'] ?? null;
        $row['changeCAPA'] = $row['change_due_to_capa'] ?? null;
        $row['capaDetails'] = $row['capa_reference'] ?? null;
        $row['tentativeDateClosing'] = $row['tentative_date_closing'] ?? null;
        $row['initiating_department'] = $row['department_name'] ?? $row['initiating_department'] ?? null;
        $row['hod_approved_by'] = $row['auth_by'] ?? $row['hod_approved_by'] ?? null;
        if (!empty($row['sop_extra_json'])) {
            $extra = json_decode($row['sop_extra_json'], true);
            if (is_array($extra)) {
                foreach ($extra as $k => $v) {
                    $row[$k] = $v;
                }
            }
        }
        if (empty($row['requiredDepartments']) && !empty($row['required_departments'])) {
            $decoded = json_decode($row['required_departments'], true);
            if (is_array($decoded)) {
                $row['requiredDepartments'] = $decoded;
            }
        }
        if (!isset($row['requiredDepartments']) || !is_array($row['requiredDepartments'])) {
            $row['requiredDepartments'] = [];
        }
        return $row;
    }

    ensureChangeControlProcedureTables($conn);

    $type = $_GET['type'] ?? '';

    if ($type === 'getCcInitiationOptions') {
        $output = [];
        $stage = esc($conn, $_GET['stage'] ?? 'all');
        $plantEsc = esc($conn, $_GET['plant_id']);
        $sql = "SELECT i.id, i.cc_no, i.title_of_change, i.department_name, i.name_of_product_doc, i.auth_by, i.workflow_status
            FROM cc_procedure_initiation i
            WHERE i.plant_id='$plantEsc'";
        if ($stage === 'assessment') {
            $sql .= " AND (i.auth_by IS NOT NULL AND i.auth_by != '' AND i.auth_by != 'NA')
                AND NOT EXISTS (
                    SELECT 1 FROM cc_procedure_impact_assessment a
                    WHERE a.cc_reference_no = i.cc_no AND a.plant_id = i.plant_id
                )";
        } else if ($stage === 'closure') {
            // FQA-009-C: after tracking log; preferably for Master Document changes
            $sql .= " AND EXISTS (
                    SELECT 1 FROM cc_procedure_impact_assessment a
                    WHERE a.cc_reference_no = i.cc_no AND a.plant_id = i.plant_id
                )
                AND NOT EXISTS (
                    SELECT 1 FROM cc_procedure_closure c
                    WHERE c.cc_reference_no = i.cc_no AND c.plant_id = i.plant_id
                )";
        }
        $sql .= " ORDER BY i.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'getCcTravelHistory') {
        $ccNo = trim($_GET['cc_no'] ?? $_GET['ctrl_no'] ?? '');
        if ($ccNo === '') {
            echo json_encode(['found' => false, 'message' => 'Enter CC number']);
            exit;
        }
        $travel = buildCcTravelStages($conn, $_GET['plant_id'], $ccNo);
        if ($travel === null) {
            echo json_encode(['found' => false, 'message' => 'CC not found']);
            exit;
        }
        echo json_encode(array_merge(['found' => true], $travel));
    } else if ($type === 'getCcPendingActions') {
        $plantEsc = esc($conn, $_GET['plant_id']);
        $pendingAuthorize = [];
        $sqlAuth = "SELECT id, cc_no, title_of_change, department_name, date_of_issuance, initiated_by, workflow_status
            FROM cc_procedure_initiation
            WHERE plant_id='$plantEsc'
            AND (auth_by IS NULL OR auth_by = '' OR auth_by = 'NA')
            AND initiated_by IS NOT NULL AND initiated_by != ''
            ORDER BY id DESC LIMIT 100";
        $resAuth = $conn->query($sqlAuth);
        if ($resAuth && $resAuth->num_rows > 0) {
            while ($row = $resAuth->fetch_assoc()) {
                $row['can_authorize'] = empCanAuthorizeCcDepartment(
                    $conn,
                    $_GET['emp_id'] ?? '',
                    $_GET['plant_id'] ?? '',
                    $row['department_name'] ?? ''
                ) ? 'Yes' : 'No';
                $pendingAuthorize[] = $row;
            }
        }

        // Pending QA Final Approval (FQA-009-A §3) — after tracking log, before qa_final stamp
        $pendingQaAssessment = [];
        $sqlQaFinal = "SELECT i.id, i.cc_no AS cc_reference_no, i.date_of_issuance AS assessment_date,
                i.department_name AS departments_concerned, i.entry_by, i.title_of_change, i.sop_extra_json, i.workflow_status
            FROM cc_procedure_initiation i
            WHERE i.plant_id='$plantEsc'
            AND (i.auth_by IS NOT NULL AND i.auth_by != '')
            AND EXISTS (
                SELECT 1 FROM cc_procedure_impact_assessment a
                WHERE a.cc_reference_no = i.cc_no AND a.plant_id = i.plant_id
            )
            ORDER BY i.id DESC LIMIT 100";
        $resQaFinal = $conn->query($sqlQaFinal);
        if ($resQaFinal && $resQaFinal->num_rows > 0) {
            while ($row = $resQaFinal->fetch_assoc()) {
                $extra = getInitiationSopExtra($row);
                if (empty($extra['qa_final_approved_by'])) {
                    $pendingQaAssessment[] = $row;
                }
            }
        }

        // Pending verification/closure (A §5) — QA Final done, not yet closed
        $pendingQaClosure = [];
        $sqlClose = "SELECT i.id, i.cc_no AS cc_reference_no, i.date_of_issuance AS closure_date,
                i.title_of_change AS implementation_completed, i.entry_by, i.sop_extra_json, i.workflow_status
            FROM cc_procedure_initiation i
            WHERE i.plant_id='$plantEsc'
            ORDER BY i.id DESC LIMIT 100";
        $resClose = $conn->query($sqlClose);
        if ($resClose && $resClose->num_rows > 0) {
            while ($row = $resClose->fetch_assoc()) {
                $extra = getInitiationSopExtra($row);
                if (!empty($extra['qa_final_approved_by']) && empty($extra['closure_by'])) {
                    $pendingQaClosure[] = $row;
                }
            }
        }

        echo json_encode([
            'pending_authorize' => $pendingAuthorize,
            'pending_qa_assessment' => $pendingQaAssessment,
            'pending_qa_closure' => $pendingQaClosure,
            'hod_departments' => getEmpHodDepartments($conn, $_GET['emp_id'] ?? '', $_GET['plant_id'] ?? ''),
        ]);
    } else if ($type === 'getCcHodDepartments') {
        echo json_encode([
            'departments' => getEmpHodDepartments($conn, $_GET['emp_id'] ?? '', $_GET['plant_id'] ?? ''),
        ]);
    } else if ($type === 'saveCcInitiation') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $deptName = $input['department_name'] ?? $_GET['department'] ?? '';
        $ccMeta = generateCcNo($conn, $_GET['plant_id'], $deptName);
        $changeReqFor = $input['changeReqFor'] ?? $input['change_types'] ?? [];
        if (is_array($changeReqFor)) {
            $changeReqForJson = json_encode($changeReqFor);
        } else {
            $changeReqForJson = json_encode([]);
        }
        $changeAffDoc = $input['changeAffDoc'] ?? [];
        $changeAffDocJson = is_array($changeAffDoc) ? json_encode($changeAffDoc) : '[]';
        $sopExtra = [
            'changeType' => $input['changeType'] ?? '',
            'levelOfChange' => $input['levelOfChange'] ?? '',
            'documentCodeRevision' => $input['documentCodeRevision'] ?? '',
            'proposedImplementationDate' => $input['proposedImplementationDate'] ?? '',
            'tempStartDate' => $input['tempStartDate'] ?? '',
            'tempEndDate' => $input['tempEndDate'] ?? '',
            'impactedDocuments' => $input['impactedDocuments'] ?? '',
            'attachmentsSupporting' => $input['attachmentsSupporting'] ?? '',
            'initiatorTitleDept' => $input['initiatorTitleDept'] ?? '',
            'requiredDepartments' => is_array($input['requiredDepartments'] ?? null) ? $input['requiredDepartments'] : [],
            'section3Assessments' => is_array($input['section3Assessments'] ?? null) ? $input['section3Assessments'] : [],
        ];
        $hasSopExtra = ensureColumn($conn, 'cc_procedure_initiation', 'sop_extra_json', "LONGTEXT DEFAULT NULL");
        $hasRequiredDepts = ensureColumn($conn, 'cc_procedure_initiation', 'required_departments', "LONGTEXT DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_initiation', 'workflow_status', "VARCHAR(50) DEFAULT 'Initiated'");
        ensureColumn($conn, 'cc_procedure_initiation', 'change_due_to_capa', "VARCHAR(10) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_initiation', 'capa_reference', "VARCHAR(255) DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_initiation', 'capa_date', "DATE DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_initiation', 'initiated_by', "TEXT DEFAULT NULL");
        $requiredDeptsJson = json_encode($sopExtra['requiredDepartments']);
        $sopExtraSql = $hasSopExtra ? ("'" . esc($conn, json_encode($sopExtra)) . "'") : 'NULL';
        $requiredDeptsSql = $hasRequiredDepts ? ("'" . esc($conn, $requiredDeptsJson) . "'") : null;
        $extraCols = '';
        $extraVals = '';
        if ($hasSopExtra) {
            $extraCols .= ', sop_extra_json';
            $extraVals .= ', ' . $sopExtraSql;
        }
        if ($hasRequiredDepts) {
            $extraCols .= ', required_departments';
            $extraVals .= ', ' . $requiredDeptsSql;
        }
        $hasWorkflow = columnExists($conn, 'cc_procedure_initiation', 'workflow_status');
        $workflowCol = $hasWorkflow ? ', workflow_status' : '';
        $workflowVal = $hasWorkflow ? ", 'Initiated'" : '';
        $sql = "INSERT INTO cc_procedure_initiation (
            plant_id, form_no, cc_no, department_code, financial_year, sr_no, department_name,
            date_of_issuance, section, title_of_change, name_of_product_doc, batch_no_doc_no,
            change_requested_for, change_requested_for_other, existing_procedure, changed_details,
            justification_of_change, change_due_to_capa, capa_reference, capa_date,
            tentative_date_closing, remark, change_affected_docs, initiated_by, entry_by, entry_date, status$workflowCol$extraCols
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "', 'FQA-009-A',
            '" . esc($conn, $ccMeta['cc_no']) . "',
            '" . esc($conn, $ccMeta['department_code']) . "',
            '" . esc($conn, $ccMeta['financial_year']) . "',
            '" . esc($conn, $ccMeta['sr_no']) . "',
            '" . esc($conn, $deptName) . "',
            '" . esc($conn, $input['dateOfIssuance'] ?? date('Y-m-d')) . "',
            '" . esc($conn, $input['section'] ?? '') . "',
            '" . esc($conn, $input['titleOfcc'] ?? '') . "',
            '" . esc($conn, $input['nameOfProductDoc'] ?? '') . "',
            '" . esc($conn, $input['batchNoDocNo'] ?? '') . "',
            '" . esc($conn, $changeReqForJson) . "',
            '" . esc($conn, $input['changeReqForOther'] ?? '') . "',
            '" . esc($conn, $input['existingProcedure'] ?? $input['impactedDocuments'] ?? '') . "',
            '" . esc($conn, $input['changedDetails'] ?? '') . "',
            '" . esc($conn, $input['justificationOfChange'] ?? '') . "',
            '" . esc($conn, $input['changeCAPA'] ?? 'NO') . "',
            '" . esc($conn, $input['capaDetails'] ?? '') . "',
            " . nullableDateSql($conn, $input['capaDate'] ?? '') . ",
            " . nullableDateSql($conn, $input['tentativeDateClosing'] ?? '') . ",
            '" . esc($conn, $input['remark'] ?? '') . "',
            '" . esc($conn, $changeAffDocJson) . "',
            '" . esc($conn, $input['initiatedBy'] ?? '') . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date', 'active'$workflowVal$extraVals
        )";
        if ($conn->query($sql)) {
            echo json_encode([
                'status' => 'success',
                'id' => $conn->insert_id,
                'cc_no' => $ccMeta['cc_no'],
                'requiredDepartments' => $sopExtra['requiredDepartments'],
            ]);
        } else {
            echo json_encode(['status' => 'Save failed: ' . ($conn->error ?: 'database error')]);
        }
    } else if ($type === 'getCcDeptAssessmentPending') {
        $plantEsc = esc($conn, $_GET['plant_id']);
        $dept = resolveViewerDepartment($conn, $_GET['department'] ?? '', $requestedFilterDepartment ?? '');
        $output = [];
        ensureColumn($conn, 'cc_procedure_initiation', 'sop_extra_json', "LONGTEXT DEFAULT NULL");
        ensureColumn($conn, 'cc_procedure_initiation', 'required_departments', "LONGTEXT DEFAULT NULL");
        // Show as soon as CC is initiated and department is selected
        $sql = "SELECT * FROM cc_procedure_initiation
            WHERE plant_id='$plantEsc'
            AND initiated_by IS NOT NULL AND initiated_by != ''
            ORDER BY id DESC LIMIT 500";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $mapped = mapInitiationRow($row);
                // Ensure requiredDepartments from dedicated column if missing in JSON
                if (empty($mapped['requiredDepartments']) && !empty($row['required_departments'])) {
                    $decoded = json_decode($row['required_departments'], true);
                    if (is_array($decoded)) {
                        $mapped['requiredDepartments'] = $decoded;
                        $mapped['required_departments'] = $row['required_departments'];
                    }
                }
                if (initiationRequiresDepartment($mapped, $dept)) {
                    $output[] = $mapped;
                    // backfill dedicated column for older rows
                    if (empty($row['required_departments']) && !empty($mapped['requiredDepartments']) && is_array($mapped['requiredDepartments'])) {
                        $conn->query(
                            "UPDATE cc_procedure_initiation SET required_departments='" . esc($conn, json_encode($mapped['requiredDepartments'])) . "'
                             WHERE id='" . esc($conn, $row['id']) . "'"
                        );
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($type === 'saveCcDeptAssessment') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $dept = trim($input['department'] ?? '');
        $checkSql = "SELECT id, cc_no, sop_extra_json FROM cc_procedure_initiation WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        $extra = getInitiationSopExtra($existing);
        $rows = $extra['section3Assessments'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }
        $updated = false;
        foreach ($rows as &$row) {
            $label = $row['label'] ?? $row['department'] ?? '';
            if (ccDepartmentsMatch($dept, $label)) {
                $row['selected'] = $input['selected'] ?? [];
                $row['otherText'] = $input['otherText'] ?? '';
                $row['reportingType'] = $input['reportingType'] ?? '';
                $row['assessmentComments'] = $input['assessmentComments'] ?? '';
                $row['assessorStamp'] = $input['assessorStamp'] ?? '';
                $row['pending'] = false;
                $row['assessed_department'] = $dept;
                $updated = true;
                break;
            }
        }
        unset($row);
        if (!$updated) {
            $rows[] = [
                'key' => $dept,
                'label' => $dept,
                'selected' => $input['selected'] ?? [],
                'otherText' => $input['otherText'] ?? '',
                'reportingType' => $input['reportingType'] ?? '',
                'assessmentComments' => $input['assessmentComments'] ?? '',
                'assessorStamp' => $input['assessorStamp'] ?? '',
                'pending' => false,
                'assessed_department' => $dept,
            ];
        }
        $extra['section3Assessments'] = $rows;
        $updateSql = "UPDATE cc_procedure_initiation SET sop_extra_json='" . esc($conn, json_encode($extra)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        if ($conn->query($updateSql)) {
            syncCcWorkflowStatus($conn, $_GET['plant_id'], $existing['cc_no']);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'getCcInitiationLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'date_of_issuance');
        if ($filter === '') {
            $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'initiation_date');
        }
        $sql = "SELECT * FROM cc_procedure_initiation WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                syncCcWorkflowStatus($conn, $_GET['plant_id'], $row['cc_no']);
                $statusRes = $conn->query("SELECT workflow_status FROM cc_procedure_initiation WHERE id='" . esc($conn, $row['id']) . "' LIMIT 1");
                if ($statusRes && $statusRes->num_rows > 0) {
                    $row['workflow_status'] = $statusRes->fetch_assoc()['workflow_status'];
                }
                $output[] = mapInitiationRow($row);
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateCcInitiationAuthBy') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT auth_by, hod_approved_by, department_name FROM cc_procedure_initiation WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        requireHodAccessForCc($conn, $existing['department_name'] ?? '');
        if (!empty($existing['auth_by']) || !empty($existing['hod_approved_by'])) {
            echo json_encode(['status' => 'already_stamped', 'auth_by' => $existing['auth_by'] ?: $existing['hod_approved_by']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $updateSql = "UPDATE cc_procedure_initiation SET auth_by='" . esc($conn, $stamp) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        if ($conn->query($updateSql)) {
            $ccRow = $conn->query("SELECT cc_no FROM cc_procedure_initiation WHERE id='$id' LIMIT 1");
            if ($ccRow && $ccRow->num_rows > 0) {
                $ccData = $ccRow->fetch_assoc();
                syncCcWorkflowStatus($conn, $_GET['plant_id'], $ccData['cc_no']);
            }
            echo json_encode(['status' => 'success', 'auth_by' => $stamp]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'updateCcInitiationHodApproved') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT auth_by, hod_approved_by, department_name FROM cc_procedure_initiation WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        requireHodAccessForCc($conn, $existing['department_name'] ?? '');
        if (!empty($existing['auth_by']) || !empty($existing['hod_approved_by'])) {
            echo json_encode(['status' => 'already_stamped', 'hod_approved_by' => $existing['auth_by'] ?: $existing['hod_approved_by']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $updateSql = "UPDATE cc_procedure_initiation SET auth_by='" . esc($conn, $stamp) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        if ($conn->query($updateSql)) {
            $ccRow = $conn->query("SELECT cc_no FROM cc_procedure_initiation WHERE id='$id' LIMIT 1");
            if ($ccRow && $ccRow->num_rows > 0) {
                syncCcWorkflowStatus($conn, $_GET['plant_id'], $ccRow->fetch_assoc()['cc_no']);
            }
            echo json_encode(['status' => 'success', 'hod_approved_by' => $stamp, 'auth_by' => $stamp]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'downloadCcInitiationForm') {
        $_GET['filename'] = 'Change Control Form';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM cc_procedure_initiation WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = mapInitiationRow($result->fetch_assoc());
            $typesText = is_array($row['changeReqFor']) ? implode(', ', $row['changeReqFor']) : '';
            $html = formHeaderHtml('CHANGE CONTROL FORM', 'FQA-009-A');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>CC No.</b></td><td>' . htmlspecialchars($row['cc_no']) . '</td>
                <td style="width:25%;"><b>Department</b></td><td>' . htmlspecialchars($row['department_name']) . '</td></tr>
                <tr><td><b>Date Of Issuance</b></td><td>' . (!empty($row['date_of_issuance']) ? date('d-m-Y', strtotime($row['date_of_issuance'])) : '') . '</td>
                <td><b>Room Name</b></td><td>' . htmlspecialchars($row['section']) . '</td></tr>
                <tr><td><b>Name Of Product / Document</b></td><td>' . htmlspecialchars($row['name_of_product_doc']) . '</td>
                <td><b>Batch No/Document No</b></td><td>' . htmlspecialchars($row['batch_no_doc_no']) . '</td></tr>
                <tr><td><b>Changed Requested For</b></td><td colspan="3">' . htmlspecialchars($typesText) . '</td></tr>
                <tr><td colspan="4"><b>Standard Current Procedure / Document:</b><br>' . nl2br(htmlspecialchars($row['existing_procedure'])) . '</td></tr>
                <tr><td colspan="4"><b>Details Of Change Proposed:</b><br>' . nl2br(htmlspecialchars($row['changed_details'])) . '</td></tr>
                <tr><td colspan="4"><b>Justification Proposed Change:</b><br>' . nl2br(htmlspecialchars($row['justification_of_change'])) . '</td></tr>
                <tr><td><b>Change due to CAPA</b></td><td>' . htmlspecialchars($row['change_due_to_capa']) . '</td>
                <td><b>CAPA / Dated On</b></td><td>' . htmlspecialchars($row['capa_reference']) . ' / ' . (!empty($row['capa_date']) ? date('d-m-Y', strtotime($row['capa_date'])) : '') . '</td></tr>
                <tr><td><b>Tentative Date Of Closing</b></td><td>' . (!empty($row['tentative_date_closing']) ? date('d-m-Y', strtotime($row['tentative_date_closing'])) : '') . '</td>
                <td><b>Remark</b></td><td>' . htmlspecialchars($row['remark']) . '</td></tr>
                <tr><td><b>Change Initiated By</b></td><td>' . htmlspecialchars($row['initiated_by']) . '</td>
                <td><b>Authorized By</b></td><td>' . htmlspecialchars($row['auth_by']) . '</td></tr>
            </table>';
            if (!empty($row['changeAffDoc']) && is_array($row['changeAffDoc'])) {
                $html .= '<br><b>Change Affected Documents</b><table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                    <tr><th>Sr.</th><th>Document No.</th><th>Document Title</th><th>Effective Date</th><th>Type Of Impact</th><th>TDC Implementation</th></tr>';
                foreach ($row['changeAffDoc'] as $i => $doc) {
                    $html .= '<tr><td>' . ($i + 1) . '</td><td>' . htmlspecialchars($doc['docNo'] ?? '') . '</td><td>' . htmlspecialchars($doc['docTitle'] ?? '') . '</td><td>' . htmlspecialchars($doc['effectiveDate'] ?? '') . '</td><td>' . htmlspecialchars($doc['typeOfImpact'] ?? '') . '</td><td>' . htmlspecialchars($doc['tcdImplementation'] ?? '') . '</td></tr>';
                }
                $html .= '</table>';
            }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Change_Control_Form.pdf', 'I');
        }
    } else if ($type === 'saveCcImpactAssessment') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $ccRef = esc($conn, $input['cc_reference_no'] ?? '');
        $authCheck = "SELECT cc_no FROM cc_procedure_initiation
            WHERE cc_no='$ccRef' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'
            AND auth_by IS NOT NULL AND auth_by != '' LIMIT 1";
        $authRes = $conn->query($authCheck);
        if (!$authRes || $authRes->num_rows === 0) {
            echo json_encode(['status' => 'Initiation must be Authorized By HOD before Tracking Log entry']);
            exit;
        }
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'issued_by', "VARCHAR(255) DEFAULT NULL AFTER departments_concerned");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'document_title', "VARCHAR(255) DEFAULT NULL AFTER issued_by");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'document_no', "VARCHAR(255) DEFAULT NULL AFTER document_title");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'requested_by', "VARCHAR(255) DEFAULT NULL AFTER document_no");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'comments', "TEXT DEFAULT NULL AFTER requested_by");
        ensureColumn($conn, 'cc_procedure_impact_assessment', 'closing_date', "DATE DEFAULT NULL AFTER comments");
        $sql = "INSERT INTO cc_procedure_impact_assessment (
            plant_id, form_no, cc_reference_no, assessment_date, departments_concerned,
            impact_product_quality, impact_validation, impact_regulatory, impact_training,
            impact_documentation, risk_assessment_required, recommended_action, qa_assessment_comments,
            issued_by, document_title, document_no, requested_by, comments, closing_date,
            entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "', 'FQA-009-B',
            '" . esc($conn, $input['cc_reference_no']) . "',
            '" . esc($conn, $input['assessment_date'] ?? date('Y-m-d')) . "',
            '" . esc($conn, $input['departments_concerned'] ?? $input['requested_by'] ?? '') . "',
            '" . esc($conn, $input['impact_product_quality'] ?? 'NA') . "',
            '" . esc($conn, $input['impact_validation'] ?? 'NA') . "',
            '" . esc($conn, $input['impact_regulatory'] ?? 'NA') . "',
            '" . esc($conn, $input['impact_training'] ?? 'NA') . "',
            '" . esc($conn, $input['impact_documentation'] ?? 'NA') . "',
            '" . esc($conn, $input['risk_assessment_required'] ?? 'NO') . "',
            '" . esc($conn, $input['recommended_action'] ?? $input['document_title'] ?? '') . "',
            '" . esc($conn, $input['qa_assessment_comments'] ?? $input['comments'] ?? '') . "',
            '" . esc($conn, $input['issued_by'] ?? '') . "',
            '" . esc($conn, $input['document_title'] ?? '') . "',
            '" . esc($conn, $input['document_no'] ?? '') . "',
            '" . esc($conn, $input['requested_by'] ?? '') . "',
            '" . esc($conn, $input['comments'] ?? '') . "',
            " . nullableDateSql($conn, $input['closing_date'] ?? '') . ",
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date', 'active'
        )";
        if ($conn->query($sql)) {
            syncCcWorkflowStatus($conn, $_GET['plant_id'], $input['cc_reference_no']);
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'getCcImpactAssessmentLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'assessment_date');
        $sql = "SELECT * FROM cc_procedure_impact_assessment WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateCcImpactQaApproved' || $type === 'updateCcQaFinalApproval') {
        // SOP: QA Final Approval is on FQA-009-A §3 (not on tracking log B)
        requireQaHeadAccess($conn);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT id, cc_no, sop_extra_json FROM cc_procedure_initiation WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            // legacy: id may still be from impact_assessment table
            $legacySql = "SELECT id, cc_reference_no, qa_head_approved_by FROM cc_procedure_impact_assessment WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
            $legacyRes = $conn->query($legacySql);
            if ($legacyRes && $legacyRes->num_rows > 0) {
                $legacy = $legacyRes->fetch_assoc();
                $initLookup = $conn->query("SELECT id, cc_no, sop_extra_json FROM cc_procedure_initiation WHERE cc_no='" . esc($conn, $legacy['cc_reference_no']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1");
                if ($initLookup && $initLookup->num_rows > 0) {
                    $checkResult = $initLookup;
                } else {
                    echo json_encode(['status' => 'Record not found']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'Record not found']);
                exit;
            }
        }
        $existing = $checkResult->fetch_assoc();
        $extra = getInitiationSopExtra($existing);
        if (!empty($extra['qa_final_approved_by'])) {
            echo json_encode(['status' => 'already_stamped', 'qa_head_approved_by' => $extra['qa_final_approved_by'], 'qa_final_approved_by' => $extra['qa_final_approved_by']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        $extra['qa_final_approved_by'] = $stamp;
        $extra['qa_final_approval_for_implementation'] = 'Yes';
        $updateSql = "UPDATE cc_procedure_initiation SET sop_extra_json='" . esc($conn, json_encode($extra)) . "' WHERE id='" . esc($conn, $existing['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        if ($conn->query($updateSql)) {
            syncCcWorkflowStatus($conn, $_GET['plant_id'], $existing['cc_no']);
            echo json_encode(['status' => 'success', 'qa_head_approved_by' => $stamp, 'qa_final_approved_by' => $stamp]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'updateCcInitiationPackage') {
        // Update FQA-009-A §§3–7 fields stored in sop_extra_json
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT id, cc_no, sop_extra_json FROM cc_procedure_initiation WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        $extra = getInitiationSopExtra($existing);
        $mergeKeys = [
            'section3Assessments', 'section4_amendments', 'section4_initiator', 'section4_dept_mgr', 'section4_qa',
            'section5_verification', 'implementation_completed_by', 'closure_by',
            'section6_reason', 'cancelled_by_dept_mgr', 'cancelled_by_qa',
            'section7_objectives_achieved', 'section7_details', 'evaluated_by_qa',
            'qa_final_approved_by', 'qa_final_approval_for_implementation',
        ];
        foreach ($mergeKeys as $key) {
            if (array_key_exists($key, $input)) {
                $extra[$key] = $input[$key];
            }
        }
        $updateSql = "UPDATE cc_procedure_initiation SET sop_extra_json='" . esc($conn, json_encode($extra)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        if ($conn->query($updateSql)) {
            syncCcWorkflowStatus($conn, $_GET['plant_id'], $existing['cc_no']);
            echo json_encode(['status' => 'success', 'record' => mapInitiationRow(array_merge($existing, $extra, ['sop_extra_json' => json_encode($extra)]))]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'downloadCcImpactAssessmentForm') {
        $_GET['filename'] = 'Change Control Impact Assessment';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM cc_procedure_impact_assessment WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $html = formHeaderHtml('CHANGE CONTROL TRACKING LOG', 'FQA-009-B');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>CCR #</b></td><td>' . htmlspecialchars($row['cc_reference_no']) . '</td>
                <td style="width:25%;"><b>Date</b></td><td>' . (!empty($row['assessment_date']) ? date('d-m-Y', strtotime($row['assessment_date'])) : '') . '</td></tr>
                <tr><td><b>Issued By</b></td><td>' . htmlspecialchars($row['issued_by'] ?? '') . '</td>
                <td><b>Requested By</b></td><td>' . htmlspecialchars($row['requested_by'] ?? $row['departments_concerned'] ?? '') . '</td></tr>
                <tr><td><b>Document Title</b></td><td colspan="3">' . htmlspecialchars($row['document_title'] ?? $row['recommended_action'] ?? '') . '</td></tr>
                <tr><td><b>Document #</b></td><td>' . htmlspecialchars($row['document_no'] ?? '') . '</td>
                <td><b>Closing Date</b></td><td>' . (!empty($row['closing_date']) ? date('d-m-Y', strtotime($row['closing_date'])) : '') . '</td></tr>
                <tr><td colspan="4"><b>Comments:</b><br>' . nl2br(htmlspecialchars($row['comments'] ?? $row['qa_assessment_comments'] ?? '')) . '</td></tr>
            </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Change_Control_Tracking_Log.pdf', 'I');
        }
    } else if ($type === 'saveCcClosure') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $ccRef = esc($conn, $input['cc_reference_no'] ?? '');
        $assessCheck = "SELECT id FROM cc_procedure_impact_assessment
            WHERE cc_reference_no='$ccRef' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'
            AND qa_head_approved_by IS NOT NULL AND qa_head_approved_by != '' LIMIT 1";
        $assessRes = $conn->query($assessCheck);
        if (!$assessRes || $assessRes->num_rows === 0) {
            echo json_encode(['status' => 'QA Head must approve Tracking Log (FQA-009-B) before Master Document Review']);
            exit;
        }
        $checklistJson = is_array($input['checklist'] ?? null) ? json_encode($input['checklist']) : '[]';
        ensureColumn($conn, 'cc_procedure_closure', 'product_code_formula', "VARCHAR(255) DEFAULT NULL AFTER closure_date");
        ensureColumn($conn, 'cc_procedure_closure', 'current_revision_no', "VARCHAR(100) DEFAULT NULL AFTER product_code_formula");
        ensureColumn($conn, 'cc_procedure_closure', 'product_description_stage', "TEXT DEFAULT NULL AFTER current_revision_no");
        ensureColumn($conn, 'cc_procedure_closure', 'checklist_json', "LONGTEXT DEFAULT NULL AFTER product_description_stage");
        ensureColumn($conn, 'cc_procedure_closure', 'all_corrections_completed', "VARCHAR(10) DEFAULT NULL AFTER checklist_json");
        ensureColumn($conn, 'cc_procedure_closure', 'review_completed_by', "VARCHAR(255) DEFAULT NULL AFTER all_corrections_completed");
        $sql = "INSERT INTO cc_procedure_closure (
            plant_id, form_no, cc_reference_no, closure_date, implementation_completed,
            training_completed, document_revision_completed, validation_completed,
            effectiveness_check_required, effectiveness_check_result, closure_comments,
            product_code_formula, current_revision_no, product_description_stage, checklist_json,
            all_corrections_completed, review_completed_by,
            entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "', 'FQA-009-C',
            '" . esc($conn, $input['cc_reference_no']) . "',
            '" . esc($conn, $input['closure_date'] ?? date('Y-m-d')) . "',
            '" . esc($conn, $input['implementation_completed'] ?? 'YES') . "',
            '" . esc($conn, $input['training_completed'] ?? 'NA') . "',
            '" . esc($conn, $input['document_revision_completed'] ?? 'YES') . "',
            '" . esc($conn, $input['validation_completed'] ?? 'NA') . "',
            '" . esc($conn, $input['effectiveness_check_required'] ?? 'NO') . "',
            '" . esc($conn, $input['effectiveness_check_result'] ?? '') . "',
            '" . esc($conn, $input['closure_comments'] ?? $input['product_description_stage'] ?? '') . "',
            '" . esc($conn, $input['product_code_formula'] ?? '') . "',
            '" . esc($conn, $input['current_revision_no'] ?? '') . "',
            '" . esc($conn, $input['product_description_stage'] ?? '') . "',
            '" . esc($conn, $checklistJson) . "',
            '" . esc($conn, $input['all_corrections_completed'] ?? 'NO') . "',
            '" . esc($conn, $input['review_completed_by'] ?? '') . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date', 'active'
        )";
        if ($conn->query($sql)) {
            syncCcWorkflowStatus($conn, $_GET['plant_id'], $input['cc_reference_no']);
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'getCcClosureLog') {
        $output = [];
        $filter = dateFilterSql(esc($conn, $_GET['from_date'] ?? ''), esc($conn, $_GET['to_date'] ?? ''), 'closure_date');
        $sql = "SELECT * FROM cc_procedure_closure WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($type === 'updateCcClosureQaApproved') {
        // SOP: Change Control Closure is FQA-009-A §5 (QA), not FQA-009-C
        requireQaHeadAccess($conn);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }
        $id = esc($conn, $input['id']);
        $checkSql = "SELECT id, cc_no, sop_extra_json FROM cc_procedure_initiation WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            echo json_encode(['status' => 'Record not found']);
            exit;
        }
        $existing = $checkResult->fetch_assoc();
        $extra = getInitiationSopExtra($existing);
        if (!empty($extra['closure_by'])) {
            echo json_encode(['status' => 'already_stamped', 'qa_closure_approved_by' => $extra['closure_by']]);
            exit;
        }
        $stamp = getEmpStamp($conn, $_GET['emp_id']);
        if (empty($extra['implementation_completed_by'])) {
            $extra['implementation_completed_by'] = $stamp;
        }
        $extra['closure_by'] = $stamp;
        $updateSql = "UPDATE cc_procedure_initiation SET sop_extra_json='" . esc($conn, json_encode($extra)) . "' WHERE id='$id' AND plant_id='" . esc($conn, $_GET['plant_id']) . "'";
        if ($conn->query($updateSql)) {
            // Also set Closing Date on FQA-009-B tracking log if present
            ensureColumn($conn, 'cc_procedure_impact_assessment', 'closing_date', "DATE DEFAULT NULL");
            $conn->query("UPDATE cc_procedure_impact_assessment SET closing_date=CURDATE()
                WHERE cc_reference_no='" . esc($conn, $existing['cc_no']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "'
                AND (closing_date IS NULL OR closing_date = '0000-00-00')");
            syncCcWorkflowStatus($conn, $_GET['plant_id'], $existing['cc_no']);
            echo json_encode(['status' => 'success', 'qa_closure_approved_by' => $stamp, 'closure_by' => $stamp]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($type === 'downloadCcClosureForm') {
        $_GET['filename'] = 'Change Control Closure Checklist';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $sql = "SELECT * FROM cc_procedure_closure WHERE id='" . esc($conn, $_GET['id']) . "' AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $html = formHeaderHtml('CHANGE CONTROL CLOSURE CHECKLIST', 'FQA-009-C');
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
                <tr><td style="width:25%;"><b>CC Reference No.</b></td><td>' . htmlspecialchars($row['cc_reference_no']) . '</td>
                <td style="width:25%;"><b>Closure Date</b></td><td>' . (!empty($row['closure_date']) ? date('d-m-Y', strtotime($row['closure_date'])) : '') . '</td></tr>
                <tr><td><b>Implementation Completed</b></td><td>' . htmlspecialchars($row['implementation_completed']) . '</td>
                <td><b>Training Completed</b></td><td>' . htmlspecialchars($row['training_completed']) . '</td></tr>
                <tr><td><b>Document Revision Completed</b></td><td>' . htmlspecialchars($row['document_revision_completed']) . '</td>
                <td><b>Validation Completed</b></td><td>' . htmlspecialchars($row['validation_completed']) . '</td></tr>
                <tr><td><b>Effectiveness Check Required</b></td><td colspan="3">' . htmlspecialchars($row['effectiveness_check_required']) . '</td></tr>
                <tr><td colspan="4"><b>Effectiveness Check Result:</b><br>' . nl2br(htmlspecialchars($row['effectiveness_check_result'])) . '</td></tr>
                <tr><td colspan="4"><b>Closure Comments:</b><br>' . nl2br(htmlspecialchars($row['closure_comments'])) . '</td></tr>
                <tr><td><b>QA Closure Approved By</b></td><td colspan="3">' . htmlspecialchars($row['qa_closure_approved_by']) . '</td></tr>
            </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Change_Control_Closure.pdf', 'I');
        }
    }
}
