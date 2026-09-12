<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = isset($_GET["token"]) ? $_GET["token"] : '';
$currentUrl = isset($_GET["description"]) ? $_GET["description"] : '';
$typeEarly = isset($_GET["type"]) ? $_GET["type"] : '';
if ($typeEarly !== "downloadWorkOrderPdf" && $typeEarly !== "downloadIssuanceLogPdf") {
    header('Content-Type: application/json; charset=utf-8');
}
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);

// db.php already reads php://input once — do not re-read (stream is empty after).
if (!isset($input) || !is_array($input)) {
    $input = array();
}

function wo_tick($val, $expect) {
    return (strtoupper(trim((string)$val)) === strtoupper(trim((string)$expect))) ? '[x]' : '[ ]';
}

/** HTML datetime-local "2026-09-11T16:30" → MySQL "2026-09-11 16:30:00" */
function wo_sql_datetime($val) {
    $v = trim((string)$val);
    if ($v === '') {
        return null;
    }
    $v = str_replace('T', ' ', $v);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
        $v .= ':00';
    }
    return $v;
}

function wo_esc($conn, $val) {
    return $conn->real_escape_string((string)$val);
}

/** Safe column add — PHP 8.1+ mysqli throws on duplicate column even with @ */
function wo_ensure_column($conn, $table, $column, $definition) {
    $tableEsc = $conn->real_escape_string($table);
    $colEsc = $conn->real_escape_string($column);
    $check = @$conn->query("SHOW COLUMNS FROM `".$tableEsc."` LIKE '".$colEsc."'");
    if ($check && $check->num_rows > 0) {
        return;
    }
    try {
        $conn->query("ALTER TABLE `".$tableEsc."` ADD COLUMN ".$definition);
    } catch (Throwable $e) {
        // ignore duplicate / race
    }
}

$sql = "SELECT * FROM token WHERE token='".$token."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result && $result->num_rows > 0) {
  try {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = isset($string[0]) ? $string[0] : '';
        $_GET["department"] = isset($string[1]) ? $string[1] : '';
        break;
    }

    $typeLog = isset($_GET["type"]) ? $_GET["type"] : '';
    try {
        $conn->query("INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$typeLog."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')");
    } catch (Throwable $e) {
        // logging must not block save
    }

    try {
        $conn->query("CREATE TABLE IF NOT EXISTS equipment_facility_work_order (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) DEFAULT NULL,
            work_order_no VARCHAR(100) DEFAULT NULL,
            initiating_department VARCHAR(150) DEFAULT NULL,
            asset_type VARCHAR(20) DEFAULT 'Equipment',
            equipment_id VARCHAR(50) DEFAULT NULL,
            equipment_name VARCHAR(255) DEFAULT NULL,
            equipment_code VARCHAR(150) DEFAULT NULL,
            sub_component_id VARCHAR(150) DEFAULT NULL,
            is_breakdown VARCHAR(10) DEFAULT NULL,
            is_repair VARCHAR(10) DEFAULT NULL,
            priority VARCHAR(10) DEFAULT NULL,
            available_for_maintenance DATETIME DEFAULT NULL,
            required_for_use DATETIME DEFAULT NULL,
            problem_description TEXT,
            ref_ncr_no VARCHAR(150) DEFAULT NULL,
            submitted_by VARCHAR(150) DEFAULT NULL,
            submitted_date DATE DEFAULT NULL,
            gmp_impact VARCHAR(10) DEFAULT NULL,
            historical_impact VARCHAR(10) DEFAULT NULL,
            qa_approval_required VARCHAR(10) DEFAULT NULL,
            reviewed_by VARCHAR(150) DEFAULT NULL,
            reviewed_date DATE DEFAULT NULL,
            work_performed TEXT,
            parts_used TEXT,
            require_requalification VARCHAR(10) DEFAULT NULL,
            require_calibration VARCHAR(10) DEFAULT NULL,
            requal_calib_date DATE DEFAULT NULL,
            performed_by VARCHAR(150) DEFAULT NULL,
            performed_date DATE DEFAULT NULL,
            verified_by VARCHAR(150) DEFAULT NULL,
            verified_date DATE DEFAULT NULL,
            qa_reinstate VARCHAR(10) DEFAULT NULL,
            qa_approved_by VARCHAR(150) DEFAULT NULL,
            qa_approved_date DATE DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'TO_ENGG_REVIEW',
            closed_by VARCHAR(150) DEFAULT NULL,
            closed_date DATE DEFAULT NULL,
            entry_by VARCHAR(50) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            updated_on DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        echo json_encode(array("status" => "db_error", "error" => "create_table: ".$e->getMessage()));
        exit;
    }

    // Upgrade older installs without throwing on duplicate column (PHP 8.1+)
    wo_ensure_column($conn, 'equipment_facility_work_order', 'asset_type', "asset_type VARCHAR(20) DEFAULT 'Equipment' AFTER initiating_department");
    wo_ensure_column($conn, 'equipment_facility_work_order', 'interior_checklist_json', "interior_checklist_json LONGTEXT NULL");
    wo_ensure_column($conn, 'equipment_facility_work_order', 'exterior_checklist_json', "exterior_checklist_json LONGTEXT NULL");
    wo_ensure_column($conn, 'equipment_facility_work_order', 'interior_checklist_done', "interior_checklist_done VARCHAR(10) DEFAULT 'No'");
    wo_ensure_column($conn, 'equipment_facility_work_order', 'exterior_checklist_done', "exterior_checklist_done VARCHAR(10) DEFAULT 'No'");

    $type = isset($_GET["type"]) ? $_GET["type"] : '';

    if ($type == "getEquipmentByDepartment") {
        $output = array();
        $dept = isset($_GET["department_name"]) ? trim($_GET["department_name"]) : '';
        if ($dept === '' && !empty($_GET["department"])) {
            $dept = trim($_GET["department"]);
        }
        $dept = $conn->real_escape_string($dept);
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        // Match Breakdown API: Active equipment for department (LIKE for name variants)
        $sql = "SELECT * FROM equipment
                WHERE plant_id = '".$plant."'
                  AND (status = 'Active' OR status = 'approve' OR status = 'Approved' OR LOWER(status) = 'active')
                  AND department LIKE '%".$dept."%'
                ORDER BY equipment_name";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        // Fallback exact match without status filter if still empty
        if (count($output) === 0 && $dept !== '') {
            $sql2 = "SELECT * FROM equipment WHERE plant_id = '".$plant."' AND department LIKE '%".$dept."%' ORDER BY equipment_name";
            $result2 = $conn->query($sql2);
            if ($result2 && $result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($type == "getAreasByDepartment") {
        $output = array();
        $seen = array();
        $dept = isset($_GET["department_name"]) ? trim($_GET["department_name"]) : '';
        if ($dept === '' && !empty($_GET["department"])) {
            $dept = trim($_GET["department"]);
        }
        $dept = $conn->real_escape_string($dept);
        $plant = $conn->real_escape_string($_GET["plant_id"]);

        $sql = "SELECT DISTINCT location AS area_name,
                       MIN(COALESCE(NULLIF(tag_no,''), NULLIF(equipment_code,''), NULLIF(serial_no,''), location)) AS sub_component_id
                FROM equipment
                WHERE plant_id='".$plant."' AND department LIKE '%".$dept."%'
                AND location IS NOT NULL AND TRIM(location) <> ''
                GROUP BY location
                ORDER BY location";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $name = trim($row['area_name']);
                if ($name !== '' && !isset($seen[$name])) {
                    $seen[$name] = true;
                    $output[] = array(
                        'area_name' => $name,
                        'id' => $name,
                        'sub_component_id' => trim($row['sub_component_id'] ? $row['sub_component_id'] : $name)
                    );
                }
            }
        }

        $sql2 = "SELECT DISTINCT section_name AS area_name FROM section
                 WHERE department LIKE '%".$dept."%' AND section_name IS NOT NULL AND TRIM(section_name) <> ''
                 ORDER BY section_name";
        $result2 = @$conn->query($sql2);
        if ($result2 && $result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $name = trim($row['area_name']);
                if ($name !== '' && !isset($seen[$name])) {
                    $seen[$name] = true;
                    $output[] = array('area_name' => $name, 'id' => $name);
                }
            }
        }
        echo json_encode($output);
    }
    else if ($type == "savePartA") {
        $plant = wo_esc($conn, isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $deptRaw = isset($input["initiating_department"]) ? $input["initiating_department"] : (isset($_GET["department"]) ? $_GET["department"] : '');
        $dept = wo_esc($conn, $deptRaw);
        $equipName = isset($input["equipment_name"]) ? trim((string)$input["equipment_name"]) : '';
        if ($plant === '' || $dept === '' || $equipName === '') {
            echo json_encode(array("status" => "missing required fields", "need" => "plant_id, initiating_department, equipment_name"));
            exit;
        }

        $woPrefix = "WO-".date("Ymd");
        $cnt = 1;
        $seqRes = @$conn->query("SELECT COUNT(*) AS cnt FROM equipment_facility_work_order WHERE plant_id='".$plant."' AND DATE(entry_date)=CURDATE()");
        if ($seqRes && $seqRes->num_rows > 0) {
            $cnt = intval($seqRes->fetch_assoc()["cnt"]) + 1;
        }
        $work_order_no = $woPrefix."-".str_pad($cnt, 3, "0", STR_PAD_LEFT);

        $avail = wo_sql_datetime(isset($input["available_for_maintenance"]) ? $input["available_for_maintenance"] : '');
        $reqUse = wo_sql_datetime(isset($input["required_for_use"]) ? $input["required_for_use"] : '');
        $availSql = $avail ? "'".wo_esc($conn, $avail)."'" : "NULL";
        $reqSql = $reqUse ? "'".wo_esc($conn, $reqUse)."'" : "NULL";

        $sql = "INSERT INTO equipment_facility_work_order (
            plant_id, work_order_no, initiating_department, asset_type, equipment_id, equipment_name, equipment_code, sub_component_id,
            is_breakdown, is_repair, priority, available_for_maintenance, required_for_use, problem_description, ref_ncr_no,
            submitted_by, submitted_date, status, entry_by, entry_date
        ) VALUES (
            '".$plant."',
            '".wo_esc($conn, $work_order_no)."',
            '".$dept."',
            '".wo_esc($conn, isset($input["asset_type"]) ? $input["asset_type"] : "Equipment")."',
            '".wo_esc($conn, isset($input["equipment_id"]) ? $input["equipment_id"] : "")."',
            '".wo_esc($conn, $equipName)."',
            '".wo_esc($conn, isset($input["equipment_code"]) ? $input["equipment_code"] : "")."',
            '".wo_esc($conn, isset($input["sub_component_id"]) ? $input["sub_component_id"] : "")."',
            '".wo_esc($conn, isset($input["is_breakdown"]) ? $input["is_breakdown"] : "")."',
            '".wo_esc($conn, isset($input["is_repair"]) ? $input["is_repair"] : "")."',
            '".wo_esc($conn, isset($input["priority"]) ? $input["priority"] : "")."',
            ".$availSql.",
            ".$reqSql.",
            '".wo_esc($conn, isset($input["problem_description"]) ? $input["problem_description"] : "")."',
            '".wo_esc($conn, isset($input["ref_ncr_no"]) ? $input["ref_ncr_no"] : "")."',
            '".wo_esc($conn, isset($input["submitted_by"]) ? $input["submitted_by"] : "")."',
            '".wo_esc($conn, isset($input["submitted_date"]) ? $input["submitted_date"] : date("Y-m-d"))."',
            'TO_ENGG_REVIEW',
            '".wo_esc($conn, $_GET["emp_id"])."',
            '".$entry_date."'
        )";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success", "work_order_no" => $work_order_no, "id" => $conn->insert_id));
        } else {
            echo json_encode(array("status" => "db_error", "error" => $conn->error));
        }
    }
    else if ($type == "saveEnggReview") {
        $id = $conn->real_escape_string(isset($_GET["id"]) ? $_GET["id"] : '');
        $assetType = 'Equipment';
        $chk = @$conn->query("SELECT asset_type FROM equipment_facility_work_order WHERE id='".$id."' LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $ar = $chk->fetch_assoc();
            $assetType = isset($ar['asset_type']) ? $ar['asset_type'] : 'Equipment';
        }
        // Equipment + Area both go to Work Performed (Area fills Interior/Exterior checklist there)
        $sql = "UPDATE equipment_facility_work_order SET
            gmp_impact='".wo_esc($conn, isset($input["gmp_impact"]) ? $input["gmp_impact"] : "")."',
            historical_impact='".wo_esc($conn, isset($input["historical_impact"]) ? $input["historical_impact"] : "")."',
            qa_approval_required='".wo_esc($conn, isset($input["qa_approval_required"]) ? $input["qa_approval_required"] : "")."',
            reviewed_by='".wo_esc($conn, isset($input["reviewed_by"]) ? $input["reviewed_by"] : "")."',
            reviewed_date='".wo_esc($conn, isset($input["reviewed_date"]) ? $input["reviewed_date"] : "")."',
            status='TO_PART_B',
            updated_on='".$entry_date."'
            WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success", "next_status" => "TO_PART_B", "asset_type" => $assetType));
        } else {
            echo json_encode(array("status" => "db_error", "error" => $conn->error));
        }
    }
    else if ($type == "savePartB") {
        $id = $conn->real_escape_string(isset($_GET["id"]) ? $_GET["id"] : '');
        $requalDate = isset($input["requal_calib_date"]) ? trim((string)$input["requal_calib_date"]) : '';

        $assetType = 'Equipment';
        $chk = @$conn->query("SELECT asset_type FROM equipment_facility_work_order WHERE id='".$id."' LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $ar = $chk->fetch_assoc();
            $assetType = isset($ar['asset_type']) ? $ar['asset_type'] : 'Equipment';
        }
        $isArea = (strtoupper(trim((string)$assetType)) === 'AREA');

        $checklistSql = '';
        if ($isArea) {
            $ctype = isset($input["checklist_type"]) ? strtoupper(trim((string)$input["checklist_type"])) : '';
            if ($ctype !== 'INTERIOR' && $ctype !== 'EXTERIOR') {
                echo json_encode(array("status" => "error", "error" => "Select Interior or Exterior checklist for Area work order"));
                exit;
            }
            $cl = isset($input["checklist"]) && is_array($input["checklist"]) ? $input["checklist"] : array();
            $payload = json_encode($cl);
            if ($payload === false) {
                $payload = '{}';
            }
            if ($ctype === 'INTERIOR') {
                $checklistSql = ", interior_checklist_json='".wo_esc($conn, $payload)."', interior_checklist_done='Yes'";
            } else {
                $checklistSql = ", exterior_checklist_json='".wo_esc($conn, $payload)."', exterior_checklist_done='Yes'";
            }
        }

        $sql = "UPDATE equipment_facility_work_order SET
            work_performed='".wo_esc($conn, isset($input["work_performed"]) ? $input["work_performed"] : "")."',
            parts_used='".wo_esc($conn, isset($input["parts_used"]) ? $input["parts_used"] : "")."',
            require_requalification='".wo_esc($conn, isset($input["require_requalification"]) ? $input["require_requalification"] : "")."',
            require_calibration='".wo_esc($conn, isset($input["require_calibration"]) ? $input["require_calibration"] : "")."',
            requal_calib_date=".($requalDate !== '' ? "'".wo_esc($conn, $requalDate)."'" : "NULL").",
            performed_by='".wo_esc($conn, isset($input["performed_by"]) ? $input["performed_by"] : "")."',
            performed_date='".wo_esc($conn, isset($input["performed_date"]) ? $input["performed_date"] : "")."'
            ".$checklistSql.",
            status='TO_VERIFY',
            updated_on='".$entry_date."'
            WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "db_error", "error" => $conn->error));
        }
    }
    else if ($type == "saveVerify") {
        $id = $conn->real_escape_string(isset($_GET["id"]) ? $_GET["id"] : '');
        $qaRequired = isset($input["qa_approval_required"]) ? $input["qa_approval_required"] : '';
        $nextStatus = (strtoupper((string)$qaRequired) == 'YES') ? 'TO_QA' : 'CLOSED';
        $verifiedBy = isset($input["verified_by"]) ? $input["verified_by"] : '';
        $verifiedDate = isset($input["verified_date"]) ? $input["verified_date"] : '';
        $closedBy = ($nextStatus == 'CLOSED') ? wo_esc($conn, $verifiedBy) : '';
        $closedDate = ($nextStatus == 'CLOSED') ? wo_esc($conn, $verifiedDate) : '';
        $sql = "UPDATE equipment_facility_work_order SET
            verified_by='".wo_esc($conn, $verifiedBy)."',
            verified_date='".wo_esc($conn, $verifiedDate)."',
            status='".$nextStatus."',
            closed_by=".($closedBy !== '' ? "'".$closedBy."'" : "NULL").",
            closed_date=".($closedDate !== '' ? "'".$closedDate."'" : "NULL").",
            updated_on='".$entry_date."'
            WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success", "next_status" => $nextStatus));
        } else {
            echo json_encode(array("status" => "db_error", "error" => $conn->error));
        }
    }
    else if ($type == "saveQaApproval") {
        $id = $conn->real_escape_string(isset($_GET["id"]) ? $_GET["id"] : '');
        $sql = "UPDATE equipment_facility_work_order SET
            qa_reinstate='".wo_esc($conn, isset($input["qa_reinstate"]) ? $input["qa_reinstate"] : "")."',
            qa_approved_by='".wo_esc($conn, isset($input["qa_approved_by"]) ? $input["qa_approved_by"] : "")."',
            qa_approved_date='".wo_esc($conn, isset($input["qa_approved_date"]) ? $input["qa_approved_date"] : "")."',
            status='CLOSED',
            closed_by='".wo_esc($conn, isset($input["qa_approved_by"]) ? $input["qa_approved_by"] : "")."',
            closed_date='".wo_esc($conn, isset($input["qa_approved_date"]) ? $input["qa_approved_date"] : "")."',
            updated_on='".$entry_date."'
            WHERE id='".$id."'";
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "db_error", "error" => $conn->error));
        }
    }
    else if ($type == "getByStatus") {
        $output = array();
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $statusRaw = isset($_GET["status"]) ? trim($_GET["status"]) : '';
        $dept = isset($_GET["department_name"]) ? $conn->real_escape_string($_GET["department_name"]) : '';
        $scope = isset($_GET["scope"]) ? $_GET["scope"] : 'department';
        $assetType = isset($_GET["asset_type"]) ? trim($_GET["asset_type"]) : '';

        // Migrate any leftover Area checklist queue into Work Performed
        @$conn->query("UPDATE equipment_facility_work_order SET status='TO_PART_B', updated_on='".$entry_date."' WHERE status='TO_AREA_CHECKLIST'");

        $sql = "SELECT * FROM equipment_facility_work_order WHERE plant_id='".$plant."'";
        if (strpos($statusRaw, ',') !== false) {
            $parts = array();
            foreach (explode(',', $statusRaw) as $st) {
                $st = trim($st);
                if ($st !== '') {
                    $parts[] = "'".$conn->real_escape_string($st)."'";
                }
            }
            if (count($parts) > 0) {
                $sql .= " AND status IN (".implode(',', $parts).")";
            }
        } else {
            $sql .= " AND status='".$conn->real_escape_string($statusRaw)."'";
        }
        if ($scope !== 'all' && $dept !== '') {
            $sql .= " AND initiating_department='".$dept."'";
        }
        if ($assetType !== '') {
            $sql .= " AND UPPER(TRIM(IFNULL(asset_type,'')))='".$conn->real_escape_string(strtoupper($assetType))."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($type == "getLog") {
        $output = array();
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $dept = isset($_GET["department_name"]) ? $conn->real_escape_string($_GET["department_name"]) : '';
        $scope = isset($_GET["scope"]) ? $_GET["scope"] : 'department';
        $statusFilter = isset($_GET["status"]) ? trim($_GET["status"]) : '';

        $sql = "SELECT * FROM equipment_facility_work_order WHERE plant_id='".$plant."'";
        if ($statusFilter !== '') {
            if (strtoupper($statusFilter) === 'ISSUANCE') {
                // After dept verification: CLOSED, or waiting QA
                $sql .= " AND (status='CLOSED' OR status='TO_QA')";
            } else if (strpos($statusFilter, ',') !== false) {
                $parts = array();
                foreach (explode(',', $statusFilter) as $st) {
                    $st = trim($st);
                    if ($st !== '') {
                        $parts[] = "'".$conn->real_escape_string($st)."'";
                    }
                }
                if (count($parts) > 0) {
                    $sql .= " AND status IN (".implode(',', $parts).")";
                }
            } else {
                $sql .= " AND status='".$conn->real_escape_string($statusFilter)."'";
            }
        }
        if ($scope !== 'all' && $dept !== '') {
            $sql .= " AND initiating_department='".$dept."'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($type == "getById") {
        $id = $conn->real_escape_string($_GET["id"]);
        $sql = "SELECT * FROM equipment_facility_work_order WHERE id='".$id."'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(new stdClass());
        }
    }
    else if ($type == "downloadWorkOrderPdf") {
        // FEN-001-01-B — filled Equipment & Facility Work Order
        $tcpdfPath = dirname(__DIR__) . '/tcpdf/tcpdf.php';
        if (!is_file($tcpdfPath)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array("status" => "error", "error" => "TCPDF not found"));
            exit;
        }
        require_once $tcpdfPath;
        $id = $conn->real_escape_string($_GET["id"]);
        $deptFilter = isset($_GET["department_name"]) ? $conn->real_escape_string($_GET["department_name"]) : '';
        $sql = "SELECT * FROM equipment_facility_work_order WHERE id='".$id."' AND plant_id='".$conn->real_escape_string($_GET["plant_id"])."'";
        if ($deptFilter !== '') {
            $sql .= " AND initiating_department='".$deptFilter."'";
        }
        $result = $conn->query($sql);
        if (!$result || $result->num_rows == 0) {
            echo "Record not found";
            exit;
        }
        $r = $result->fetch_assoc();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Medicap');
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('FEN-001-01-B '.$r['work_order_no']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 9);

        $prob = nl2br(htmlspecialchars($r['problem_description']));
        $work = nl2br(htmlspecialchars($r['work_performed']));
        $parts = nl2br(htmlspecialchars($r['parts_used']));

        // Paper FEN-001-01-B header: logo + control table (match scanned form)
        $logoFile = dirname(__DIR__) . '/logos/medicap-logo.png';
        if (is_file($logoFile)) {
            $pdf->Image($logoFile, 12, 10, 38, 0, 'PNG', '', '', true, 150);
        } else {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(196, 30, 58);
            $pdf->SetXY(12, 12);
            $pdf->Cell(50, 6, 'Medicap', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetX(12);
            $pdf->Cell(50, 4, 'LABORATORIES', 0, 1, 'L');
        }
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(150, 10);
        $pdf->Cell(50, 5, 'Ref: WI-EN-001-01', 0, 1, 'R');
        $pdf->Ln(18);

        $html = '
        <table border="1" cellpadding="4" width="100%" style="border-collapse:collapse;">
          <tr>
            <td width="16%" bgcolor="#d9d9d9"><b>TITLE</b></td>
            <td width="54%" align="center"><b>EQUIPMENT &amp; FACILITY WORK ORDER</b></td>
            <td width="15%" bgcolor="#d9d9d9"><b>FORM NO.</b></td>
            <td width="15%" bgcolor="#cfe8f5" align="center"><b>FEN-001-01-B</b></td>
          </tr>
          <tr>
            <td bgcolor="#d9d9d9"><b>DEPARTMENT APPROVAL</b></td>
            <td>SIGNATURE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; DATE</td>
            <td bgcolor="#d9d9d9"><b>REVISION NO.</b></td>
            <td align="center">00</td>
          </tr>
          <tr>
            <td bgcolor="#d9d9d9"><b>QA APPROVAL</b></td>
            <td>SIGNATURE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; DATE</td>
            <td bgcolor="#d9d9d9"><b>EFFECTIVE DATE</b></td>
            <td align="center">MAY 06 2025</td>
          </tr>
          <tr>
            <td colspan="2">&nbsp;</td>
            <td bgcolor="#d9d9d9"><b>PAGE NO.</b></td>
            <td align="center">1 OF 2</td>
          </tr>
        </table>
        <br/>
        <div><b>Work Order #:</b> '.htmlspecialchars($r['work_order_no']).'</div>
        <br/>
        <div><b><u>Part A-The Initiator:</u></b></div>
        <table border="1" cellpadding="5" width="100%">
          <tr>
            <td width="35%" bgcolor="#f3f4f6"><b>Equipment / Area Name:</b></td>
            <td width="65%">'.htmlspecialchars(($r['asset_type'] ? $r['asset_type'].' — ' : '').$r['equipment_name']).'</td>
          </tr>
          <tr>
            <td bgcolor="#f3f4f6"><b>Equipment / Area ID #:</b></td>
            <td>'.htmlspecialchars($r['equipment_code']).'</td>
          </tr>
          <tr>
            <td bgcolor="#f3f4f6"><b>Sub-Component ID#:</b></td>
            <td>'.htmlspecialchars($r['sub_component_id']).'</td>
          </tr>
        </table>
        <br/>
        <div><b>Is this:</b></div>
        <table cellpadding="3" width="100%">
          <tr>
            <td width="34%"><b>Breakdown:</b> '.wo_tick($r['is_breakdown'],'Yes').' Yes &nbsp; '.wo_tick($r['is_breakdown'],'No').' No</td>
            <td width="33%"><b>Repair:</b> '.wo_tick($r['is_repair'],'Yes').' Yes &nbsp; '.wo_tick($r['is_repair'],'No').' No</td>
            <td width="33%"><b>Priority:</b> '.wo_tick($r['priority'],'1').' 1 &nbsp; '.wo_tick($r['priority'],'2').' 2 &nbsp; '.wo_tick($r['priority'],'3').' 3</td>
          </tr>
        </table>
        <br/>
        <div>When the equipment / area is available for maintenance: <b>'.htmlspecialchars($r['available_for_maintenance']).'</b></div>
        <div>When the equipment / area is required for use: <b>'.htmlspecialchars($r['required_for_use']).'</b></div>
        <br/>
        <div><b>Describe the problem:</b></div>
        <table border="1" cellpadding="6" width="100%"><tr><td height="45">'.$prob.'</td></tr></table>
        <br/>
        <div>Ref NCR # (if applicable): <b>'.htmlspecialchars($r['ref_ncr_no']).'</b></div>
        <div>Submitted By: <b>'.htmlspecialchars($r['submitted_by']).'</b> &nbsp;&nbsp; Date: <b>'.htmlspecialchars($r['submitted_date']).'</b></div>
        <br/>
        <table border="1" cellpadding="4" width="100%" style="background-color:#f3f4f6;">
          <tr><td align="center"><b>Submit to Engineering and Maintenance Department for completion of Part A</b></td></tr>
        </table>
        <br/>
        <table cellpadding="3" width="100%">
          <tr>
            <td><b>GMP Impact:</b> '.wo_tick($r['gmp_impact'],'Yes').' Yes &nbsp; '.wo_tick($r['gmp_impact'],'No').' No</td>
            <td><b>Historical Impact:</b> '.wo_tick($r['historical_impact'],'Yes').' Yes &nbsp; '.wo_tick($r['historical_impact'],'No').' No</td>
            <td><b>QA Approval Required:</b> '.wo_tick($r['qa_approval_required'],'Yes').' Yes &nbsp; '.wo_tick($r['qa_approval_required'],'No').' No</td>
          </tr>
        </table>
        <div>Reviewed By: <b>'.htmlspecialchars($r['reviewed_by']).'</b> &nbsp;&nbsp; Date: <b>'.htmlspecialchars($r['reviewed_date']).'</b></div>
        <div style="font-size:8px;color:#666;">Dept: '.htmlspecialchars($r['initiating_department']).'</div>
        ';
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->AddPage();
        $html2 = '
        <table border="1" cellpadding="4" width="100%">
          <tr>
            <td width="70%" align="center"><b>EQUIPMENT &amp; FACILITY WORK ORDER</b><br/>ENGINEERING AND MAINTENANCE</td>
            <td width="30%">FORM NO. FEN-001-01-B<br/>PAGE NO. 2 OF 2</td>
          </tr>
        </table>
        <br/>
        <div><b><u>Engineering and Maintenance Department / Contractor</u></b></div>
        <div><b>Work Performed:</b></div>
        <table border="1" cellpadding="6" width="100%"><tr><td>'.$work.'&nbsp;</td></tr></table>
        <br/>
        <div><b>Parts Used:</b></div>
        <table border="1" cellpadding="6" width="100%"><tr><td>'.$parts.'&nbsp;</td></tr></table>
        <br/>
        <div>Does this equipment require re-qualification? '.wo_tick($r['require_requalification'],'Yes').' Yes &nbsp; '.wo_tick($r['require_requalification'],'No').' No</div>
        <div>Does this equipment require calibration after the work performed? '.wo_tick($r['require_calibration'],'Yes').' Yes &nbsp; '.wo_tick($r['require_calibration'],'No').' No</div>
        <div>If yes, Re-qualification/calibration performed on: <b>'.htmlspecialchars($r['requal_calib_date']).'</b></div>
        <div>Performed by: <b>'.htmlspecialchars($r['performed_by']).'</b> &nbsp;&nbsp; Date: <b>'.htmlspecialchars($r['performed_date']).'</b></div>
        <br/>
        <div><b><u>Work Order Verification: Initiating Department</u></b></div>
        <div>Verified by: <b>'.htmlspecialchars($r['verified_by']).'</b> &nbsp;&nbsp; Date: <b>'.htmlspecialchars($r['verified_date']).'</b></div>
        <br/>
        <div><b><u>QA Approval: Manager, Quality Assurance &amp; Compliance (IF REQUIRED)</u></b></div>
        <div>Equipment/Facility can be reinstated: '.wo_tick($r['qa_reinstate'],'Yes').' Yes &nbsp; '.wo_tick($r['qa_reinstate'],'No').' No</div>
        <div>Approved by: <b>'.htmlspecialchars($r['qa_approved_by']).'</b> &nbsp;&nbsp; Date: <b>'.htmlspecialchars($r['qa_approved_date']).'</b></div>
        <br/><hr/>
        <div align="center"><i>Confidential Information: Do Not Disclose without Authorization</i></div>
        ';
        $pdf->writeHTML($html2, true, false, true, false, '');
        $pdf->Output('FEN-001-01-B-'.$r['work_order_no'].'.pdf', 'I');
        exit;
    }
    else if ($type == "downloadIssuanceLogPdf") {
        // FEN-001-01-A — draw dynamically (no scanned template / no header signatures)
        $tcpdfPath = dirname(__DIR__) . '/tcpdf/tcpdf.php';
        if (!is_file($tcpdfPath)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array("status" => "error", "error" => "TCPDF not found"));
            exit;
        }
        require_once $tcpdfPath;

        $plant = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '');
        $scope = isset($_GET["scope"]) ? $_GET["scope"] : 'all';
        $dept = isset($_GET["department_name"]) ? $conn->real_escape_string($_GET["department_name"]) : '';
        $statusFilter = isset($_GET["status"]) ? trim($_GET["status"]) : 'ISSUANCE';
        $idFilter = isset($_GET["id"]) ? $conn->real_escape_string($_GET["id"]) : '';

        $sql = "SELECT * FROM equipment_facility_work_order WHERE plant_id='".$plant."'";
        if ($idFilter !== '') {
            $sql .= " AND id='".$idFilter."'";
        } else if ($statusFilter === '' || strtoupper($statusFilter) === 'ISSUANCE') {
            $sql .= " AND (status='CLOSED' OR status='TO_QA')";
        } else if (strpos($statusFilter, ',') !== false) {
            $parts = array();
            foreach (explode(',', $statusFilter) as $st) {
                $st = trim($st);
                if ($st !== '') {
                    $parts[] = "'".$conn->real_escape_string($st)."'";
                }
            }
            if (count($parts) > 0) {
                $sql .= " AND status IN (".implode(',', $parts).")";
            }
        } else {
            $sql .= " AND status='".$conn->real_escape_string($statusFilter)."'";
        }
        if ($idFilter === '' && $scope !== 'all' && $dept !== '') {
            $sql .= " AND initiating_department='".$dept."'";
        }
        $sql .= " ORDER BY id ASC";
        $result = @$conn->query($sql);

        $dataRows = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dataRows[] = $row;
            }
        }

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Medicap');
        $pdf->SetAuthor('Medicap Laboratories');
        $pdf->SetTitle('FEN-001-01-A WORK ORDER ISSUANCE LOG FORM');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->SetTextColor(0, 0, 0);

        $logoFile = dirname(__DIR__) . '/logos/medicap-logo.png';
        if (is_file($logoFile)) {
            $pdf->Image($logoFile, 12, 8, 40, 0, 'PNG', '', '', true, 150);
        } else {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(196, 30, 58);
            $pdf->SetXY(12, 10);
            $pdf->Cell(50, 6, 'Medicap', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetX(12);
            $pdf->Cell(50, 4, 'LABORATORIES', 0, 1, 'L');
        }
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(220, 10);
        $pdf->Cell(65, 5, 'Ref: WI-EN-001-01', 0, 1, 'R');
        $pdf->SetY(26);

        $lbl = '#d9d9d9';

        // Header: blank approval lines (no signature / date values)
        $html = '
        <table border="1" cellpadding="4" width="100%" style="border-collapse:collapse;">
          <tr>
            <td width="14%" bgcolor="'.$lbl.'"><b>TITLE</b></td>
            <td width="52%" align="center"><b>WORK ORDER ISSUANCE LOG FORM</b></td>
            <td width="14%" bgcolor="'.$lbl.'" align="center"><b>FORM NO.</b></td>
            <td width="20%" align="center"><b>FEN-001-01-A</b></td>
          </tr>
          <tr>
            <td bgcolor="'.$lbl.'"><b>DEPARTMENT APPROVAL:</b></td>
            <td align="center" height="22">&nbsp;</td>
            <td bgcolor="'.$lbl.'" align="center"><b>REVISION NO.</b></td>
            <td align="center"><b>00</b></td>
          </tr>
          <tr>
            <td bgcolor="'.$lbl.'"><b>QA APPROVAL:</b></td>
            <td align="center" height="22">&nbsp;</td>
            <td bgcolor="'.$lbl.'" align="center"><b>EFFECTIVE DATE</b></td>
            <td align="center"><b>MAY 06 2025</b></td>
          </tr>
          <tr>
            <td colspan="2">&nbsp;</td>
            <td bgcolor="'.$lbl.'" align="center"><b>PAGE NO.</b></td>
            <td align="center"><b>1 OF 1</b></td>
          </tr>
        </table>
        <br/>
        <table border="1" cellpadding="5" width="100%" style="border-collapse:collapse;">
          <tr bgcolor="'.$lbl.'">
            <td width="12%" align="center"><b>Work Order #</b></td>
            <td width="13%" align="center"><b>Date of Initiation</b></td>
            <td width="18%" align="center"><b>Initiated By &amp; Date</b></td>
            <td width="39%" align="center"><b>Brief Description of Problem</b></td>
            <td width="18%" align="center"><b>Closed By &amp; Date</b></td>
          </tr>
        ';

        // Data first, then only 3 blank rows
        $blankPad = 3;
        $totalRows = count($dataRows) + $blankPad;
        if ($totalRows < 1) {
            $totalRows = $blankPad;
        }

        for ($i = 0; $i < $totalRows; $i++) {
            $wo = '';
            $initDate = '';
            $initiated = '';
            $prob = '';
            $closed = '';
            if ($i < count($dataRows)) {
                $r = $dataRows[$i];
                $wo = isset($r['work_order_no']) ? $r['work_order_no'] : '';
                $initDate = isset($r['submitted_date']) ? $r['submitted_date'] : '';
                $initiated = trim(
                    (isset($r['submitted_by']) ? $r['submitted_by'] : '')
                    .((isset($r['submitted_date']) && $r['submitted_date'] !== '') ? ' / '.$r['submitted_date'] : '')
                );
                $prob = isset($r['problem_description']) ? $r['problem_description'] : '';
                if (isset($r['status']) && $r['status'] === 'CLOSED') {
                    $closed = trim(
                        (isset($r['closed_by']) ? $r['closed_by'] : '')
                        .((isset($r['closed_date']) && $r['closed_date'] !== '') ? ' / '.$r['closed_date'] : '')
                    );
                } else if (isset($r['status']) && $r['status'] === 'TO_QA') {
                    $closed = trim(
                        (isset($r['verified_by']) ? $r['verified_by'] : '')
                        .((isset($r['verified_date']) && $r['verified_date'] !== '') ? ' / '.$r['verified_date'] : '')
                    );
                }
            }
            $html .= '
          <tr>
            <td width="12%" align="center" height="18">'.htmlspecialchars($wo).'&nbsp;</td>
            <td width="13%" align="center">'.htmlspecialchars($initDate).'&nbsp;</td>
            <td width="18%" align="center">'.htmlspecialchars($initiated).'&nbsp;</td>
            <td width="39%" align="left">'.nl2br(htmlspecialchars($prob)).'&nbsp;</td>
            <td width="18%" align="center">'.htmlspecialchars($closed).'&nbsp;</td>
          </tr>';
        }

        $html .= '
        </table>
        <br/>
        <div align="right" style="font-size:8px;"><i>Confidential Information: Do Not Disclose without Authorization</i></div>
        ';

        $pdf->SetFont('helvetica', '', 9);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('FEN-001-01-A-Work-Order-Issuance-Log.pdf', 'I');
        exit;
    }
    else {
        echo json_encode(array("status" => "invalid type"));
    }
  } catch (Throwable $e) {
    echo json_encode(array(
      "status" => "server_error",
      "error" => $e->getMessage(),
      "file" => basename($e->getFile()),
      "line" => $e->getLine()
    ));
  }
} else {
    echo json_encode(array(array("status" => "invalidToken")));
}
?>
