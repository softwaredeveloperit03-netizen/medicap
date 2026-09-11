<?php 
require '../db.php';
require '../token.php';

    // ini_set('display_errors', 1);
    //     error_reporting(E_ALL); 
$output = Array();
$token = $_GET["token"] ?? '';
$currentUrl = $_GET["description"] ?? '';

$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$sql = "SELECT * FROM token WHERE token='".$token."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode(($rawInput !== false && $rawInput !== '') ? $rawInput : '{}', true);
    if (!is_array($input)) {
        $input = array();
    }
    
    // ============================================
    // STOCK VERIFICATION FUNCTION
    // Verifies stock availability before allowing line booking
    // ============================================
    function verifyStockForLineBooking($conn, $workorder_no, $plant_id) {
        $verificationResult = [
            'stockVerified' => false,
            'hasShortage' => false,
            'indentRaised' => false,
            'shortageMaterials' => [],
            'message' => '',
            'canProceed' => false
        ];
        
        // Get deductions for this work order
        $dedSql = "SELECT d.*, 
                  (SELECT material_name FROM my_view m WHERE m.material_code = d.material_code LIMIT 1) as material_name
                  FROM WO_deductions d 
                  WHERE d.workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'";
        $dedResult = $conn->query($dedSql);
        
        if (!$dedResult || $dedResult->num_rows == 0) {
            // No deductions found - assume stock is verified
            $verificationResult['stockVerified'] = true;
            $verificationResult['canProceed'] = true;
            $verificationResult['message'] = 'No material deductions found. Stock verification passed.';
            return $verificationResult;
        }
        
        $hasShortage = false;
        $allIndentsRaised = true;
        $shortageMaterials = [];
        
        while ($dedRow = $dedResult->fetch_assoc()) {
            // Calculate required qty
            $requiredQty = (floatval($dedRow['deducted_from_MC'] ?? 0) + floatval($dedRow['deducted_from_RM'] ?? 0) + floatval($dedRow['shortage'] ?? 0));
            
            // Check current stock availability
            $material_code = mysqli_real_escape_string($conn, $dedRow['material_code'] ?? '');
            $currentAvailableRM = 0;
            $currentAvailableMC = 0;
            $currentTotalAvailable = 0;
            $currentShortage = 0;
            
            if (!empty($material_code) && !empty($plant_id)) {
                // Check current RM stock from vw_total_available_stock
                $currentStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                  WHERE material_code = '".$material_code."' 
                                  AND plant_id = '".mysqli_real_escape_string($conn, $plant_id)."' 
                                  LIMIT 1";
                $currentStockResult = $conn->query($currentStockSql);
                if ($currentStockResult && $currentStockResult->num_rows > 0) {
                    $currentStockRow = $currentStockResult->fetch_assoc();
                    $currentAvailableRM = floatval($currentStockRow['available_qty'] ?? 0);
                }
                
                // Check for Mother Code stock
                $motherCodeSql = "SELECT mother_material_code FROM material 
                                 WHERE material_code = '".$material_code."' 
                                 LIMIT 1";
                $motherCodeResult = $conn->query($motherCodeSql);
                if ($motherCodeResult && $motherCodeResult->num_rows > 0) {
                    $motherCodeRow = $motherCodeResult->fetch_assoc();
                    $mother_code = $motherCodeRow['mother_material_code'] ?? null;
                    
                    if ($mother_code) {
                        $mcStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                     WHERE material_code = '".mysqli_real_escape_string($conn, $mother_code)."' 
                                     AND plant_id = '".mysqli_real_escape_string($conn, $plant_id)."' 
                                     LIMIT 1";
                        $mcStockResult = $conn->query($mcStockSql);
                        if ($mcStockResult && $mcStockResult->num_rows > 0) {
                            $mcStockRow = $mcStockResult->fetch_assoc();
                            $currentAvailableMC = floatval($mcStockRow['available_qty'] ?? 0);
                        }
                    }
                }
                
                // Calculate current total available and shortage
                $currentTotalAvailable = $currentAvailableRM + $currentAvailableMC;
                if ($currentTotalAvailable < $requiredQty) {
                    $currentShortage = $requiredQty - $currentTotalAvailable;
                } else {
                    $currentShortage = 0;
                }
            }
            
            // Check if indent exists for this material
            $indentExists = false;
            $indentNo = '';
            $indentId = '';
            
            if (!empty($material_code)) {
                // Check indend_raw table
                $indentCheckSql = "SELECT id, no, request_no, status 
                                  FROM indend_raw 
                                  WHERE material_code = '".$material_code."'
                                  AND required_for LIKE '%\"work_order_no\":\"".mysqli_real_escape_string($conn, $workorder_no)."\"%'
                                  AND status != 'Rejected'
                                  ORDER BY id DESC LIMIT 1";
                $indentCheckResult = $conn->query($indentCheckSql);
                
                if ($indentCheckResult && $indentCheckResult->num_rows > 0) {
                    $indentRow = $indentCheckResult->fetch_assoc();
                    $indentExists = true;
                    $indentNo = $indentRow['no'] ?? '';
                    $indentId = $indentRow['id'] ?? '';
                }
            }
            
            // Check WO_deductions indent_status
            $indentStatus = $dedRow['indent_status'] ?? 'Not Raised';
            if ($indentStatus === 'Raised' || $indentStatus === 'Indent Sent') {
                $indentExists = true;
                if (empty($indentNo) && !empty($dedRow['indent_no'])) {
                    $indentNo = $dedRow['indent_no'];
                }
                if (empty($indentId) && !empty($dedRow['indent_id'])) {
                    $indentId = $dedRow['indent_id'];
                }
            }
            
            // If there's a shortage, check if indent is raised
            if ($currentShortage > 0) {
                $hasShortage = true;
                $shortageMaterials[] = [
                    'material_code' => $material_code,
                    'material_name' => $dedRow['material_name'] ?? '',
                    'required_qty' => $requiredQty,
                    'available_qty' => $currentTotalAvailable,
                    'shortage' => $currentShortage,
                    'indent_raised' => $indentExists,
                    'indent_no' => $indentNo,
                    'indent_id' => $indentId
                ];
                
                // If indent is not raised for this shortage, mark as not all indents raised
                if (!$indentExists) {
                    $allIndentsRaised = false;
                }
            }
        }
        
        // Determine if booking can proceed
        if (!$hasShortage) {
            // No shortages - stock is complete
            $verificationResult['stockVerified'] = true;
            $verificationResult['canProceed'] = true;
            $verificationResult['message'] = 'Stock verification passed. All materials are available.';
        } else if ($hasShortage && $allIndentsRaised) {
            // Shortages exist but all indents are raised
            $verificationResult['stockVerified'] = true;
            $verificationResult['hasShortage'] = true;
            $verificationResult['indentRaised'] = true;
            $verificationResult['canProceed'] = true;
            $verificationResult['shortageMaterials'] = $shortageMaterials;
            $verificationResult['message'] = 'Stock verification passed. Shortages exist but indents are raised for all materials.';
        } else {
            // Shortages exist and indents are not raised
            $verificationResult['stockVerified'] = false;
            $verificationResult['hasShortage'] = true;
            $verificationResult['indentRaised'] = false;
            $verificationResult['canProceed'] = false;
            $verificationResult['shortageMaterials'] = $shortageMaterials;
            $materialList = array_map(function($mat) {
                return $mat['material_code'] . ' (' . $mat['material_name'] . ')';
            }, $shortageMaterials);
            $verificationResult['message'] = 'Stock verification failed. Shortages exist and indents are not raised for: ' . implode(', ', $materialList);
        }
        
        return $verificationResult;
    }

    if (!function_exists('lbEnsureLineMasterMappedTables')) {
        function lbEnsureLineMasterMappedTables($conn) {
            if (!($conn instanceof mysqli)) {
                return;
            }
            @$conn->query("CREATE TABLE IF NOT EXISTS `linemaster_mapped_Equipment` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `linemaster_id` INT NULL DEFAULT NULL,
                `equipment_name` VARCHAR(255) NULL DEFAULT NULL,
                `equipment_code` VARCHAR(100) NULL DEFAULT NULL,
                `capacity` VARCHAR(100) NULL DEFAULT NULL,
                `from_range` VARCHAR(100) NULL DEFAULT NULL,
                `to_range` VARCHAR(100) NULL DEFAULT NULL,
                `unit` VARCHAR(50) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_lme_line` (`linemaster_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            @$conn->query("CREATE TABLE IF NOT EXISTS `linemaster_mapped_Product` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `linemaster_id` INT NULL DEFAULT NULL,
                `product_code` VARCHAR(100) NULL DEFAULT NULL,
                `category` VARCHAR(100) NULL DEFAULT NULL,
                `dosage_form` VARCHAR(100) NULL DEFAULT NULL,
                `generic_name` VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_lmp_line` (`linemaster_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            @$conn->query("CREATE TABLE IF NOT EXISTS `linemaster_groups_stages` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `linemaster_id` INT NULL DEFAULT NULL,
                `dosage_form` VARCHAR(100) NULL DEFAULT NULL,
                `stage` VARCHAR(100) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_lgs_line` (`linemaster_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
    if (!function_exists('lbWoOrderJoinSql')) {
        function lbWoOrderJoinSql() {
            return " FROM Work_order_materials a
                LEFT JOIN order_materials b ON a.order_no = b.order_no
                  AND (b.product_code = a.product_code OR TRIM(IFNULL(a.product_code,'')) = '')";
        }
    }
    if (!function_exists('lbLinemasterPlantSql')) {
        function lbLinemasterPlantSql($conn, $alias = 'lm') {
            $plant = mysqli_real_escape_string($conn, (string)($_GET['plant_id'] ?? ''));
            static $hasCol = null;
            if ($hasCol === null) {
                $chk = @$conn->query("SHOW COLUMNS FROM linemaster LIKE 'plant_id'");
                $hasCol = ($chk && $chk->num_rows > 0);
            }
            if ($plant === '' || !$hasCol) {
                return '';
            }
            return " AND ({$alias}.plant_id = '{$plant}' OR {$alias}.plant_id IS NULL OR TRIM(IFNULL({$alias}.plant_id,'')) = '')";
        }
    }
    if (!function_exists('lbWoPlantFilterSql')) {
        function lbWoPlantFilterSql($conn) {
            $plant = mysqli_real_escape_string($conn, (string)($_GET['plant_id'] ?? ''));
            if ($plant === '') {
                return '';
            }
            return " AND (a.plant_id = '{$plant}' OR a.plant_id IS NULL OR TRIM(IFNULL(a.plant_id,'')) = '')";
        }
    }
    if (!function_exists('lbWoSearchAnd')) {
        function lbWoSearchAnd($conn) {
            $q = trim((string)($_GET['search'] ?? ''));
            if ($q === '') {
                return '';
            }
            $s = mysqli_real_escape_string($conn, $q);
            return " AND (
                a.workorder_no LIKE '%{$s}%'
                OR a.order_no LIKE '%{$s}%'
                OR a.product_code LIKE '%{$s}%'
                OR b.product_code LIKE '%{$s}%'
            )";
        }
    }
    if (!function_exists('lbListPageParams')) {
        function lbListPageParams($withLimit = false) {
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = $withLimit ? max(0, intval($_GET['limit'] ?? 25)) : 0;
            $offset = ($page - 1) * max($limit, 1);
            return array($page, $limit, $offset);
        }
    }
    if (!function_exists('lbCountJoin')) {
        function lbCountJoin($conn, $joinSql) {
            $res = @$conn->query("SELECT COUNT(*) AS c ".$joinSql);
            if ($res && ($row = $res->fetch_assoc())) {
                return intval($row['c'] ?? 0);
            }
            return 0;
        }
    }
    if (!function_exists('lbPaginatedPayload')) {
        function lbPaginatedPayload($rows, $total, $page, $limit) {
            return array(
                'status' => 'success',
                'data' => $rows,
                'total' => intval($total),
                'page' => intval($page),
                'limit' => intval($limit),
            );
        }
    }
    if (!function_exists('lbDecodeSelectedLines')) {
        function lbDecodeSelectedLines($raw) {
            if (is_array($raw)) {
                return $raw;
            }
            if ($raw === null || $raw === '') {
                return array();
            }
            $decoded = json_decode((string)$raw, true);
            return is_array($decoded) ? $decoded : array();
        }
    }
    if (!function_exists('lbProductLineArea')) {
        function lbProductLineArea($conn, $productCode) {
            $code = trim((string)$productCode);
            if ($code === '') {
                return '';
            }
            $codeEsc = mysqli_real_escape_string($conn, $code);
            $sql = "SELECT lm.Section AS area
                    FROM product p
                    INNER JOIN linemaster_groups_stages lgs ON lgs.dosage_form = p.dosage_form
                    INNER JOIN linemaster lm ON lm.id = lgs.linemaster_id
                    WHERE p.product_code = '".$codeEsc."'
                      AND TRIM(IFNULL(lm.Section, '')) != ''
                    ORDER BY lm.line_no ASC
                    LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $r = $res->fetch_assoc();
                return trim((string)($r['area'] ?? ''));
            }
            return '';
        }
    }
    if (!function_exists('lbEnrichSelectedLinesFromMaster')) {
        function lbEnrichSelectedLinesFromMaster($conn, $selectedLines) {
            if (!is_array($selectedLines)) {
                return array();
            }
            foreach ($selectedLines as &$line) {
                if (!is_array($line)) {
                    continue;
                }
                $id = intval($line['id'] ?? ($line['linemaster_id'] ?? 0));
                if ($id <= 0) {
                    $line['area'] = $line['area'] ?? ($line['Section'] ?? '');
                    continue;
                }
                $res = $conn->query("SELECT * FROM linemaster WHERE id = ".$id." LIMIT 1");
                if ($res && $res->num_rows > 0) {
                    $master = $res->fetch_assoc();
                    $line = array_merge($master, $line);
                    $section = trim((string)($master['Section'] ?? ($line['Section'] ?? '')));
                    $line['Section'] = $section;
                    $line['area'] = $section !== '' ? $section : trim((string)($line['area'] ?? ''));
                } else {
                    $line['area'] = $line['area'] ?? ($line['Section'] ?? '');
                }
            }
            unset($line);
            return $selectedLines;
        }
    }
    if (!function_exists('lbValidateBookingPlan')) {
        function lbValidateBookingPlan($conn, $plantId, $wo, $selectedLines, $startDate, $startTime, $endDate, $endTime) {
            $lines = is_array($selectedLines) ? $selectedLines : array();
            $kg = floatval($wo['batch_size_kg'] ?? ($wo['batch_size'] ?? ($wo['plan_qty'] ?? 0)));
            $eqRuns = 0;
            $area = '';
            foreach ($lines as $line) {
                if (!is_array($line)) {
                    continue;
                }
                if ($area === '') {
                    $area = trim((string)($line['area'] ?? ($line['Section'] ?? '')));
                }
                if (!empty($line['equipmentList']) && is_array($line['equipmentList'])) {
                    $eqRuns += count($line['equipmentList']);
                } elseif (!empty($line['equipment_count'])) {
                    $eqRuns += intval($line['equipment_count']);
                }
            }
            $summary = $area !== '' ? $area : 'Line selected';
            if ($eqRuns > 0) {
                $summary .= ' · '.$eqRuns.' equipment';
            }
            return array(
                'valid' => true,
                'errors' => array(),
                'warnings' => array(),
                'equipment_runs' => $eqRuns,
                'equipment_summary' => $summary,
                'quantities' => array('batch_size_kg' => $kg),
            );
        }
    }
    if (!function_exists('lbEnrichLineCandidate')) {
        function lbEnrichLineCandidate($conn, $plantId, $lineRow, $woContext, $lineType) {
            $lineRow['line_type'] = $lineType;
            $lineRow['suggested_schedule'] = null;
            $lineRow['capacity_validation'] = array('valid' => true);
            return $lineRow;
        }
    }
    if (!function_exists('lbAutoSelectBestLine')) {
        function lbAutoSelectBestLine($candidates) {
            return (is_array($candidates) && count($candidates) > 0) ? $candidates[0] : null;
        }
    }
    if (!function_exists('lbIsBlankDate')) {
        function lbIsBlankDate($v) {
            $v = trim((string)$v);
            if ($v === '' || strtolower($v) === 'null') {
                return true;
            }
            return strpos($v, '0000-00-00') === 0;
        }
    }
    if (!function_exists('lbFillWoQtyAndDates')) {
        function lbFillWoQtyAndDates(&$row) {
            $dd = $row['deliveryDate'] ?? '';
            if (lbIsBlankDate($dd)) {
                $dd = $row['om_deliveryDate'] ?? ($row['delivery_date'] ?? '');
            }
            if (lbIsBlankDate($dd)) {
                $dd = $row['om_deliveryDate_fallback'] ?? '';
            }
            $row['deliveryDate'] = lbIsBlankDate($dd) ? '' : $dd;
            $row['delivery_date'] = $row['deliveryDate'];

            $kg = floatval($row['batch_size_kg'] ?? 0);
            if ($kg <= 0) {
                $kg = floatval($row['batch_size'] ?? 0);
            }
            if ($kg <= 0) {
                $kg = floatval($row['plan_qty'] ?? 0);
            }
            if ($kg <= 0) {
                $kg = floatval($row['work_order_planned_qty'] ?? 0);
            }
            if ($kg > 0) {
                $row['batch_size_kg'] = $kg;
            }
        }
    }
    if (!function_exists('lbEnrichWoDisplayRow')) {
        function lbEnrichWoDisplayRow($conn, &$row) {
            if (empty($row['product_code']) && !empty($row['om_product_code'])) {
                $row['product_code'] = $row['om_product_code'];
            }
            lbFillWoQtyAndDates($row);
            if (empty($row['area']) && empty($row['line_area']) && !empty($row['product_code'])) {
                $row['line_area'] = lbProductLineArea($conn, $row['product_code']);
                $row['area'] = $row['line_area'];
            }
        }
    }
    if (!function_exists('summarizeLineNumbers')) {
        function summarizeLineNumbers($selectedLines) {
            if (!is_array($selectedLines)) {
                return '';
            }
            $nos = array();
            foreach ($selectedLines as $line) {
                if (is_array($line) && !empty($line['line_no'])) {
                    $nos[] = $line['line_no'];
                }
            }
            return implode(', ', $nos);
        }
    }
    if (!function_exists('filterLinesByCategory')) {
        function filterLinesByCategory($selectedLines, $category) {
            if (!is_array($selectedLines)) {
                return array();
            }
            $out = array();
            foreach ($selectedLines as $line) {
                $cat = $line['line_type_category'] ?? ($line['line_type'] ?? '');
                if (strcasecmp((string)$cat, (string)$category) === 0) {
                    $out[] = $line;
                }
            }
            return $out;
        }
    }
    if (!function_exists('resolveLineTypeCategory')) {
        function resolveLineTypeCategory($lineRow) {
            $t = strtolower(trim((string)($lineRow['line_type'] ?? $lineRow['lineType'] ?? '')));
            if (strpos($t, 'pack') !== false) {
                return 'packing';
            }
            return 'manufacturing';
        }
    }
    if (!function_exists('lbRecheckWorkOrderOwnCodeStock')) {
        function lbRecheckWorkOrderOwnCodeStock($conn, $workorder_no, $plant_id) {
            $deductions = array();
            $shortage_materials = array();
            $sql = "SELECT * FROM WO_deductions WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'";
            $res = @$conn->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $deductions[] = $row;
                    if (floatval($row['shortage'] ?? 0) > 0) {
                        $shortage_materials[] = $row;
                    }
                }
            }
            return array(
                'deductions' => $deductions,
                'has_shortage' => count($shortage_materials) > 0,
                'shortage_materials' => $shortage_materials,
            );
        }
    }
    if (!function_exists('lbEnsureColumn')) {
        function lbEnsureColumn($conn, $table, $column, $definition) {
            $col = @$conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".mysqli_real_escape_string($conn, $column)."'");
            if (!$col || $col->num_rows === 0) {
                @$conn->query("ALTER TABLE `".$table."` ADD COLUMN `".$column."` ".$definition);
            }
        }
    }
    if (!function_exists('lbEnsureStpSchema')) {
        function lbEnsureStpSchema($conn) {
            if (!($conn instanceof mysqli)) {
                return;
            }
            $woCols = array(
                'selectedLines' => "LONGTEXT NULL",
                'expected_production_start_date' => "VARCHAR(30) NULL",
                'expected_production_start_time' => "VARCHAR(20) NULL",
                'expected_production_end_date' => "VARCHAR(30) NULL",
                'expected_production_end_time' => "VARCHAR(20) NULL",
                'no_of_hours_required' => "VARCHAR(20) NULL",
                'responsible_person' => "VARCHAR(255) NULL",
                'stp_planned_flag' => "VARCHAR(10) NULL DEFAULT 'No'",
                'stp_planned_by' => "VARCHAR(100) NULL",
                'stp_planned_on' => "VARCHAR(50) NULL",
                'stp_line_approval_status' => "VARCHAR(30) NULL",
                'stp_line_approval_sent_by' => "VARCHAR(100) NULL",
                'stp_line_approval_sent_on' => "VARCHAR(50) NULL",
                'advance_planning_flag' => "VARCHAR(10) NULL DEFAULT 'No'",
            );
            foreach ($woCols as $col => $def) {
                lbEnsureColumn($conn, 'Work_order_materials', $col, $def);
            }
            @$conn->query("CREATE TABLE IF NOT EXISTS `line_booking` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `workorder_no` VARCHAR(100) NULL,
                `linemaster_id` INT NULL,
                `line_no` VARCHAR(50) NULL,
                `product_code` VARCHAR(100) NULL,
                `product_name` VARCHAR(255) NULL,
                `booking_start_date` VARCHAR(30) NULL,
                `booking_start_time` VARCHAR(20) NULL,
                `booking_end_date` VARCHAR(30) NULL,
                `booking_end_time` VARCHAR(20) NULL,
                `responsible_person` VARCHAR(255) NULL,
                `selected_equipments` LONGTEXT NULL,
                `capacity_required` VARCHAR(50) NULL,
                `no_of_hours_required` VARCHAR(20) NULL,
                `status` VARCHAR(50) NULL DEFAULT 'Parked',
                `entry_by` VARCHAR(100) NULL,
                `entry_date` VARCHAR(50) NULL,
                `updated_by` VARCHAR(100) NULL,
                `updated_date` VARCHAR(50) NULL,
                `plant_id` VARCHAR(20) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_lb_wo` (`workorder_no`),
                KEY `idx_lb_line` (`linemaster_id`),
                KEY `idx_lb_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $lbCols = array(
                'workorder_no' => "VARCHAR(100) NULL",
                'linemaster_id' => "INT NULL",
                'line_no' => "VARCHAR(50) NULL",
                'product_code' => "VARCHAR(100) NULL",
                'product_name' => "VARCHAR(255) NULL",
                'booking_start_date' => "VARCHAR(30) NULL",
                'booking_start_time' => "VARCHAR(20) NULL",
                'booking_end_date' => "VARCHAR(30) NULL",
                'booking_end_time' => "VARCHAR(20) NULL",
                'responsible_person' => "VARCHAR(255) NULL",
                'selected_equipments' => "LONGTEXT NULL",
                'capacity_required' => "VARCHAR(50) NULL",
                'no_of_hours_required' => "VARCHAR(20) NULL",
                'status' => "VARCHAR(50) NULL DEFAULT 'Parked'",
                'entry_by' => "VARCHAR(100) NULL",
                'entry_date' => "VARCHAR(50) NULL",
                'updated_by' => "VARCHAR(100) NULL",
                'updated_date' => "VARCHAR(50) NULL",
                'plant_id' => "VARCHAR(20) NULL",
            );
            foreach ($lbCols as $col => $def) {
                lbEnsureColumn($conn, 'line_booking', $col, $def);
            }
        }
    }
    if (!function_exists('lbSlimSelectedLines')) {
        function lbSlimSelectedLines($selectedLines) {
            $out = array();
            if (!is_array($selectedLines)) {
                return $out;
            }
            foreach ($selectedLines as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $id = $line['id'] ?? ($line['linemaster_id'] ?? '');
                $out[] = array(
                    'id' => $id,
                    'linemaster_id' => $line['linemaster_id'] ?? $id,
                    'line_no' => $line['line_no'] ?? '',
                    'line_name' => $line['line_name'] ?? '',
                    'Section' => $line['Section'] ?? ($line['area'] ?? ''),
                    'area' => $line['area'] ?? ($line['Section'] ?? ''),
                    'lineType' => $line['lineType'] ?? ($line['Type'] ?? ($line['line_type_category'] ?? '')),
                    'line_type_category' => $line['line_type_category'] ?? ($line['lineType'] ?? ($line['Type'] ?? '')),
                    'Type' => $line['Type'] ?? ($line['lineType'] ?? ''),
                    'equipmentList' => is_array($line['equipmentList'] ?? null) ? $line['equipmentList'] : array(),
                );
            }
            return $out;
        }
    }
    if (!function_exists('stpUpsertParkedLineBookings')) {
        function stpUpsertParkedLineBookings($conn, $workorderNo, $selectedLines, $parkMeta, $empId, $entryDate, $plantId) {
            lbEnsureStpSchema($conn);
            $count = 0;
            $errors = array();
            $selectedLines = lbSlimSelectedLines($selectedLines);
            if (!is_array($selectedLines) || count($selectedLines) === 0) {
                return array('count' => 0, 'errors' => array('No selected lines'));
            }
            foreach ($selectedLines as $line) {
                $linemasterId = intval($line['id'] ?? $line['linemaster_id'] ?? 0);
                $lineNo = trim((string)($line['line_no'] ?? ''));
                if ($linemasterId <= 0 && $lineNo !== '') {
                    $lnEsc = mysqli_real_escape_string($conn, $lineNo);
                    $find = @$conn->query("SELECT id FROM linemaster WHERE line_no = '".$lnEsc."' LIMIT 1");
                    if ($find && $find->num_rows > 0) {
                        $linemasterId = intval($find->fetch_assoc()['id'] ?? 0);
                    }
                }
                if ($linemasterId <= 0) {
                    $errors[] = 'Missing line id for '.$lineNo;
                    continue;
                }
                $woEsc = mysqli_real_escape_string($conn, $workorderNo);
                $exists = @$conn->query("SELECT id FROM line_booking
                    WHERE workorder_no = '".$woEsc."'
                      AND linemaster_id = '".$linemasterId."'
                      AND status NOT IN ('Cancelled')
                    LIMIT 1");
                $equipJson = mysqli_real_escape_string($conn, json_encode($line['equipmentList'] ?? array()));
                $fields = array(
                    "product_code = '".mysqli_real_escape_string($conn, (string)($parkMeta['product_code'] ?? ''))."'",
                    "product_name = '".mysqli_real_escape_string($conn, (string)($parkMeta['product_name'] ?? ''))."'",
                    "line_no = '".mysqli_real_escape_string($conn, $lineNo)."'",
                    "booking_start_date = '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_start_date'] ?? ''))."'",
                    "booking_start_time = '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_start_time'] ?? '00:00:00'))."'",
                    "booking_end_date = '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_end_date'] ?? ''))."'",
                    "booking_end_time = '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_end_time'] ?? '23:59:59'))."'",
                    "responsible_person = '".mysqli_real_escape_string($conn, (string)($parkMeta['responsible_person'] ?? ''))."'",
                    "selected_equipments = '".$equipJson."'",
                    "capacity_required = '".mysqli_real_escape_string($conn, (string)($parkMeta['batch_size'] ?? ($parkMeta['plan_qty'] ?? '')))."'",
                    "status = 'Parked'",
                    "entry_by = '".mysqli_real_escape_string($conn, (string)$empId)."'",
                    "entry_date = '".mysqli_real_escape_string($conn, (string)$entryDate)."'",
                    "plant_id = '".mysqli_real_escape_string($conn, (string)$plantId)."'",
                );
                if ($exists && $exists->num_rows > 0) {
                    $rowId = intval($exists->fetch_assoc()['id'] ?? 0);
                    $sql = "UPDATE line_booking SET ".implode(', ', $fields)." WHERE id = ".$rowId." LIMIT 1";
                } else {
                    $sql = "INSERT INTO line_booking (
                        workorder_no, linemaster_id, line_no, product_code, product_name,
                        booking_start_date, booking_start_time, booking_end_date, booking_end_time,
                        responsible_person, selected_equipments, capacity_required, status, entry_by, entry_date, plant_id
                    ) VALUES (
                        '".$woEsc."',
                        '".$linemasterId."',
                        '".mysqli_real_escape_string($conn, $lineNo)."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['product_code'] ?? ''))."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['product_name'] ?? ''))."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_start_date'] ?? ''))."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_start_time'] ?? '00:00:00'))."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_end_date'] ?? ''))."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['booking_end_time'] ?? '23:59:59'))."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['responsible_person'] ?? ''))."',
                        '".$equipJson."',
                        '".mysqli_real_escape_string($conn, (string)($parkMeta['batch_size'] ?? ($parkMeta['plan_qty'] ?? '')))."',
                        'Parked',
                        '".mysqli_real_escape_string($conn, (string)$empId)."',
                        '".mysqli_real_escape_string($conn, (string)$entryDate)."',
                        '".mysqli_real_escape_string($conn, (string)$plantId)."'
                    )";
                }
                if ($conn->query($sql)) {
                    $count++;
                } else {
                    $errors[] = $conn->error;
                }
            }
            return array('count' => $count, 'errors' => $errors);
        }
    }

    lbEnsureLineMasterMappedTables($conn);
    lbEnsureStpSchema($conn);
    
    // ============================================
    // GET VERIFIED WORK ORDERS (Sent for Batch Allocation)
    // ============================================
    if ($_GET["type"] == "getVerifiedWorkOrders") {
        $output = Array();
        
        $sql = "SELECT a.*, b.product_code, b.work_order_planned_qty, b.planMonth,
                b.mainGroupName, b.packingStyle AS packing_type,
                b.deliveryDate AS om_deliveryDate,
                (SELECT NULLIF(NULLIF(TRIM(om.deliveryDate), ''), '0000-00-00')
                   FROM order_materials om
                  WHERE om.order_no = a.order_no
                  ORDER BY CASE WHEN om.product_code = COALESCE(a.product_code, b.product_code) THEN 0 ELSE 1 END, om.id DESC
                  LIMIT 1) AS om_deliveryDate_fallback,
                (SELECT product_name FROM product c 
                 WHERE COALESCE(b.product_code, a.product_code) = c.product_code LIMIT 1) AS product_name 
                FROM Work_order_materials a
                LEFT JOIN order_materials b ON a.order_no = b.order_no
                WHERE a.status = 'Sent for Batch Allocation'
                AND IFNULL(a.stp_planned_flag, 'No') != 'Yes'
                AND LOWER(TRIM(IFNULL(a.stp_line_approval_status, ''))) NOT IN ('pending', 'approved')
                AND NOT EXISTS (
                    SELECT 1 FROM line_booking lbq
                    WHERE lbq.workorder_no = a.workorder_no
                      AND lbq.status IN ('Parked', 'Booked')
                )
                ORDER BY a.Wo_Generated_on DESC";
        
        $result = $conn->query($sql);
        
        $colors = [
            "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", "#D1C4E9", "#FFE0B2",
            "#F8BBD0", "#B2EBF2", "#E6EE9C", "#FFECB3", "#CFD8DC", "#F0F4C3",
            "#DCEDC8", "#F5F5F5", "#E1BEE7", "#BBDEFB", "#FFCDD2", "#D7CCC8",
            "#FFCC80", "#C8E6C9"
        ];
        $colorMap = [];
        $colorIndex = 0;
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $po = $row["order_no"];
                if (!isset($colorMap[$po])) {
                    $colorMap[$po] = $colors[$colorIndex % count($colors)];
                    $colorIndex++;
                }
                $row["bg_color"] = $colorMap[$po];
                lbFillWoQtyAndDates($row);
                if (empty($row['area']) && empty($row['line_area'])) {
                    $row['line_area'] = lbProductLineArea($conn, $row['product_code'] ?? '');
                    $row['area'] = $row['line_area'];
                }
                
                $row["selectedLines"] = json_decode($row["selectedLines"] ?? '[]', true);
                if (!is_array($row["selectedLines"])) {
                    $row["selectedLines"] = array();
                }
                $row["selectedLines"] = lbEnrichSelectedLinesFromMaster($conn, $row["selectedLines"]);
                // Get deductions/materials
                $deductions = [];
                $dedSql = "SELECT * FROM WO_deductions WHERE workorder_no = '".$row['workorder_no']."'";
                $dedResult = $conn->query($dedSql);
                if ($dedResult && $dedResult->num_rows > 0) {
                    while ($dedRow = $dedResult->fetch_assoc()) {
                        $deductions[] = $dedRow;
                    }
                }
                $row["Deductions"] = $deductions;
                
                // Get already booked lines for this work order
                $bookedLines = [];
                $bookedSql = "SELECT * FROM line_booking WHERE workorder_no = '".$row['workorder_no']."' AND status != 'Cancelled'";
                $bookedResult = $conn->query($bookedSql);
                if ($bookedResult && $bookedResult->num_rows > 0) {
                    while ($bookedRow = $bookedResult->fetch_assoc()) {
                        $bookedLines[] = $bookedRow;
                    }
                }
                $row["bookedLines"] = $bookedLines;
                
                // Get available lines based on product dosage form
                $availableLines = [];
                $productCodeEsc = mysqli_real_escape_string($conn, $row['product_code'] ?? '');
                $dosageForm = '';
                if ($productCodeEsc !== '') {
                    $dfRes = $conn->query("SELECT dosage_form FROM product WHERE product_code = '".$productCodeEsc."' LIMIT 1");
                    if ($dfRes && $dfRes->num_rows > 0) {
                        $dfRow = $dfRes->fetch_assoc();
                        $dosageForm = trim((string)($dfRow['dosage_form'] ?? ''));
                    }
                }
                if ($dosageForm != '') {
                    $lineSql = "SELECT lm.*, 
                                (SELECT COUNT(*) FROM linemaster_mapped_Equipment WHERE linemaster_id = lm.id) as equipment_count
                                FROM linemaster lm
                                LEFT JOIN linemaster_groups_stages lgs ON lm.id = lgs.linemaster_id
                                WHERE lgs.dosage_form = '".mysqli_real_escape_string($conn, $dosageForm)."'
                                GROUP BY lm.id
                                ORDER BY lm.line_no";
                    $lineResult = $conn->query($lineSql);
                    if ($lineResult && $lineResult->num_rows > 0) {
                        while ($lineRow = $lineResult->fetch_assoc()) {
                            $lineId = $lineRow['id'];
                            
                            // Get equipment list for this line
                            $eqList = [];
                            $eqSql = "SELECT * FROM linemaster_mapped_Equipment WHERE linemaster_id = '".$lineId."'";
                            $eqResult = $conn->query($eqSql);
                            if ($eqResult && $eqResult->num_rows > 0) {
                                while ($eqRow = $eqResult->fetch_assoc()) {
                                    $eqList[] = $eqRow;
                                }
                            }
                            $lineRow["equipmentList"] = $eqList;
                            
                            // Get stages for this line
                            $stages = [];
                            $stageSql = "SELECT dosage_form, stage FROM linemaster_groups_stages 
                                        WHERE linemaster_id = '".$lineId."' 
                                        ORDER BY dosage_form, stage";
                            $stageResult = $conn->query($stageSql);
                            if ($stageResult && $stageResult->num_rows > 0) {
                                while ($stageRow = $stageResult->fetch_assoc()) {
                                    $stages[] = $stageRow;
                                }
                            }
                            $lineRow["stages"] = $stages;
                            $lineRow["area"] = $lineRow["Section"] ?? '';
                            
                            // Check if line is available for booking (no conflicts)
                            $lineRow["isAvailable"] = true;
                            $availableLines[] = $lineRow;
                        }
                    }
                }
                $row["Lines"] = $availableLines;
                if ((empty($row['area']) && empty($row['line_area'])) && count($availableLines) > 0) {
                    $row['line_area'] = $availableLines[0]['area'] ?? ($availableLines[0]['Section'] ?? '');
                    $row['area'] = $row['line_area'];
                }
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    
// ============================================
// GET BOOKED STOCK (RM/PM) FOR RMPMBOOKING MODULE
// ============================================
else if ($_GET["type"] == "getBookedStock") {
    $output = array();
    $plant_id = $_GET["plant_id"] ?? '';
    
    if (empty($plant_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Plant ID is required']);
        exit;
    }
    
    // Get booked stock from WO_deductions where qty_status = 'Booked' or materials allocated to work orders
    // Also include materials from work_order_batch_lots and mfg_work_order_dtl
    $sql = "SELECT DISTINCT
                wd.id,
                wd.workorder_no,
                wd.material_code,
                
                wd.mat_type,
                wd.plan_qty as required_qty,
                (COALESCE(wd.deducted_from_RM, 0) + COALESCE(wd.deducted_from_MC, 0)) as booked_qty,
                wd.unit,
                wd.shortage,
                wd.qty_status,
                wd.status,
                wd.entry_by,
                wd.entry_date,
                wom.product_code,
                wom.batch_size,
                wom.plan_qty AS work_order_planned_qty,
                p.product_name,
                m.material_name as material_name_full,
                m.material_type,
                m.grade,
                m.material_subtype,
                -- Get available stock from stock_book
                (SELECT COALESCE(SUM(sb.qty), 0) 
                 FROM stock_book sb 
                 WHERE sb.material_code = wd.material_code 
                 AND sb.plant_id = '".mysqli_real_escape_string($conn, $plant_id)."'
                 AND sb.status = 'Approved') as available_stock,
                -- Get issued qty from material_issue
                (SELECT COALESCE(SUM(mi.qty), 0) 
                 FROM material_issue mi 
                 WHERE mi.material_code = wd.material_code 
                 AND mi.plant_id = '".mysqli_real_escape_string($conn, $plant_id)."') as issued_qty
            FROM WO_deductions wd
            LEFT JOIN Work_order_materials wom ON wd.workorder_no = wom.workorder_no
            LEFT JOIN product p ON wom.product_code = p.product_code AND wom.plant_id = p.plant_id
            LEFT JOIN material m ON wd.material_code = m.material_code AND wd.plant_id = m.plant_id
            WHERE wd.plant_id = '".mysqli_real_escape_string($conn, $plant_id)."'
            AND (
                wd.qty_status = 'Booked' 
                OR wd.status IN ('CAN_PLAN', 'CANNOT_PLAN')
                OR EXISTS (
                    SELECT 1 FROM work_order_batch_lots wobl
                    JOIN mfg_work_order_hdr mwoh ON wobl.work_order_id = mwoh.id
                    WHERE mwoh.work_order_no = wd.workorder_no
                    AND wobl.material_code = wd.material_code
                )
                OR EXISTS (
                    SELECT 1 FROM mfg_work_order_dtl mwod
                    JOIN mfg_work_order_hdr mwoh ON mwod.work_order_id = mwoh.id
                    WHERE mwoh.work_order_no = wd.workorder_no
                    AND mwod.material_code = wd.material_code
                )
            )
            ORDER BY wd.entry_date DESC, wd.workorder_no DESC, wd.material_code";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate net available stock (available - issued)
            $availableStock = floatval($row['available_stock'] ?? 0);
            $issuedQty = floatval($row['issued_qty'] ?? 0);
            $row['net_available_stock'] = max(0, $availableStock - $issuedQty);
            
            // Use material_name from material table if available, otherwise use from WO_deductions
            if (empty($row['material_name_full'])) {
                $row['material_name'] = $row['material_name'] ?? '';
            } else {
                $row['material_name'] = $row['material_name_full'];
            }
            
            // Set material type properly
            if (empty($row['material_type'])) {
                $row['material_type'] = ($row['mat_type'] === 'RM') ? 'Raw Material' : 
                                       (($row['mat_type'] === 'PM') ? 'Packing Material' : '');
            }
            
            $output[] = $row;
        }
    }
    
    echo json_encode($output);
}
    
    // ============================================
    // GET AVAILABLE LINES FOR PRODUCT
    // ============================================
     else if ($_GET["type"] == "updateActualDates") {
        if (!is_array($input)) {
            $input = array();
        }
        $booking_id = $input['booking_id'] ?? '';
        
        if (empty($booking_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Booking ID is required']);
            exit;
        }
        
        $updates = [];
        $statusChanged = false;
        $willComplete = false;
        
        // Update actual start date/time
        if (isset($input['actual_start_date'])) {
            if ($input['actual_start_date'] == '' || $input['actual_start_date'] == null) {
                $updates[] = "actual_start_date = NULL";
                $updates[] = "actual_start_time = NULL";
            } else {
                $updates[] = "actual_start_date = '".mysqli_real_escape_string($conn, $input['actual_start_date'])."'";
                // If actual start is set and status is Booked, change to In Progress
                $checkStatusSql = "SELECT status FROM line_booking WHERE id = '".mysqli_real_escape_string($conn, $booking_id)."'";
                $checkStatusResult = $conn->query($checkStatusSql);
                if ($checkStatusResult && $checkStatusResult->num_rows > 0) {
                    $statusRow = $checkStatusResult->fetch_assoc();
                    if ($statusRow['status'] == 'Booked') {
                        $updates[] = "status = 'In Progress'";
                        $statusChanged = true;
                    }
                }
            }
        }
        if (isset($input['actual_start_time']) && isset($input['actual_start_date']) && $input['actual_start_date'] != '') {
            $updates[] = "actual_start_time = '".mysqli_real_escape_string($conn, $input['actual_start_time'])."'";
        }
        
        // Update actual end date/time
        if (isset($input['actual_end_date'])) {
            if ($input['actual_end_date'] == '' || $input['actual_end_date'] == null) {
                $updates[] = "actual_end_date = NULL";
                $updates[] = "actual_end_time = NULL";
            } else {
                $updates[] = "actual_end_date = '".mysqli_real_escape_string($conn, $input['actual_end_date'])."'";
                // Auto-complete: If actual end date is set, status becomes Completed
                $checkStatusSql = "SELECT status FROM line_booking WHERE id = '".mysqli_real_escape_string($conn, $booking_id)."'";
                $checkStatusResult = $conn->query($checkStatusSql);
                if ($checkStatusResult && $checkStatusResult->num_rows > 0) {
                    $statusRow = $checkStatusResult->fetch_assoc();
                    if ($statusRow['status'] != 'Completed' && $statusRow['status'] != 'Cancelled') {
                        $updates[] = "status = 'Completed'";
                        $statusChanged = true;
                        $willComplete = true;
                    }
                }
            }
        }
        if (isset($input['actual_end_time']) && isset($input['actual_end_date']) && $input['actual_end_date'] != '') {
            $updates[] = "actual_end_time = '".mysqli_real_escape_string($conn, $input['actual_end_time'])."'";
        }
        
        if (empty($updates)) {
            echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
            exit;
        }
        
        $updates[] = "updated_by = '".$_GET["emp_id"]."'";
        $updates[] = "updated_date = '".$entry_date."'";
        
        $sql = "UPDATE line_booking SET " . implode(', ', $updates) . " WHERE id = '".mysqli_real_escape_string($conn, $booking_id)."'";
        
        if ($conn->query($sql)) {
            $message = 'Actual dates updated successfully';
            if ($willComplete) {
                $message .= '. Line booking marked as Completed. Line is now available for new bookings.';
            } else if ($statusChanged) {
                $message .= '. Status updated to In Progress.';
            }
            
            // Log the action
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'updateActualDates: Booking ID ' . mysqli_real_escape_string($conn, $booking_id);
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            echo json_encode(['status' => 'success', 'message' => $message, 'will_complete' => $willComplete]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update actual dates: ' . $conn->error]);
        }
    }
    
    else if ($_GET["type"] == "getAvailableLines") {
        $product_code = $_GET["product_code"] ?? '';
        $workorder_no = $_GET["workorder_no"] ?? '';
        $start_date = $_GET["start_date"] ?? '';
        $start_time = $_GET["start_time"] ?? '';
        $end_date = $_GET["end_date"] ?? '';
        $end_time = $_GET["end_time"] ?? '';
        
        $output = Array();
        
        // Get product dosage form
        $dosageForm = '';
        if ($product_code) {
            $prodSql = "SELECT dosage_form FROM product WHERE product_code = '".$product_code."' LIMIT 1";
            $prodResult = $conn->query($prodSql);
            if ($prodResult && $prodResult->num_rows > 0) {
                $prodRow = $prodResult->fetch_assoc();
                $dosageForm = $prodRow['dosage_form'] ?? '';
            }
        }
        
        if ($dosageForm == '') {
            echo json_encode(['status' => 'error', 'message' => 'Product dosage form not found']);
            exit;
        }
        
        // Get lines matching dosage form
        $lineSql = "SELECT lm.*, 
                    (SELECT COUNT(*) FROM linemaster_mapped_Equipment WHERE linemaster_id = lm.id) as equipment_count
                    FROM linemaster lm
                    LEFT JOIN linemaster_groups_stages lgs ON lm.id = lgs.linemaster_id
                    WHERE lgs.dosage_form = '".$dosageForm."'
                    GROUP BY lm.id
                    ORDER BY lm.line_no";
        $lineResult = $conn->query($lineSql);
        
        if ($lineResult && $lineResult->num_rows > 0) {
            while ($lineRow = $lineResult->fetch_assoc()) {
                $lineId = $lineRow['id'];
                
                // Get equipment list
                $eqList = [];
                $eqSql = "SELECT * FROM linemaster_mapped_Equipment WHERE linemaster_id = '".$lineId."'";
                $eqResult = $conn->query($eqSql);
                if ($eqResult && $eqResult->num_rows > 0) {
                    while ($eqRow = $eqResult->fetch_assoc()) {
                        $eqList[] = $eqRow;
                    }
                }
                $lineRow["equipmentList"] = $eqList;
                
                // Get stages for this line
                $stages = [];
                $stageSql = "SELECT dosage_form, stage FROM linemaster_groups_stages 
                            WHERE linemaster_id = '".$lineId."' 
                            ORDER BY dosage_form, stage";
                $stageResult = $conn->query($stageSql);
                if ($stageResult && $stageResult->num_rows > 0) {
                    while ($stageRow = $stageResult->fetch_assoc()) {
                        $stages[] = $stageRow;
                    }
                }
                $lineRow["stages"] = $stages;
                $lineRow["area"] = $lineRow["Section"] ?? '';
                
                // Check availability (conflict detection)
                $isAvailable = true;
                $conflicts = [];
                
                if ($start_date && $end_date) {
                    // Check for time conflicts
                    $conflictSql = "SELECT * FROM line_booking 
                                   WHERE linemaster_id = '".$lineId."' 
                                   AND status NOT IN ('Cancelled', 'Completed')
                                   AND (
                                       (booking_start_date <= '".$end_date."' AND booking_end_date >= '".$start_date."')
                                   )";
                    $conflictResult = $conn->query($conflictSql);
                    if ($conflictResult && $conflictResult->num_rows > 0) {
                        while ($conflictRow = $conflictResult->fetch_assoc()) {
                            // Check time overlap
                            $conflictStart = $conflictRow['booking_start_date'] . ' ' . $conflictRow['booking_start_time'];
                            $conflictEnd = $conflictRow['booking_end_date'] . ' ' . $conflictRow['booking_end_time'];
                            $requestStart = $start_date . ' ' . $start_time;
                            $requestEnd = $end_date . ' ' . $end_time;
                            
                            if (strtotime($requestStart) < strtotime($conflictEnd) && strtotime($requestEnd) > strtotime($conflictStart)) {
                                $isAvailable = false;
                                $conflicts[] = [
                                    'workorder_no' => $conflictRow['workorder_no'],
                                    'start' => $conflictStart,
                                    'end' => $conflictEnd
                                ];
                            }
                        }
                    }
                }
                
                $lineRow["isAvailable"] = $isAvailable;
                $lineRow["conflicts"] = $conflicts;
                
                $output[] = $lineRow;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // BOOK LINE FOR WORK ORDER
    // ============================================
    else if ($_GET["type"] == "bookLine") {
        $workorder_no = $input['workorder_no'] ?? '';
        $linemaster_id = $input['linemaster_id'] ?? '';
        $line_no = $input['line_no'] ?? '';
        $product_code = $input['product_code'] ?? '';
        $product_name = $input['product_name'] ?? '';
        $booking_start_date = $input['booking_start_date'] ?? '';
        $booking_start_time = $input['booking_start_time'] ?? '';
        $booking_end_date = $input['booking_end_date'] ?? '';
        $booking_end_time = $input['booking_end_time'] ?? '';
        $responsible_person = $input['responsible_person'] ?? '';
        $selected_equipments = $input['selected_equipments'] ?? [];
        $capacity_required = $input['capacity_required'] ?? '';
        $no_of_hours_required = $input['no_of_hours_required'] ?? 0;
        $status = $input['status'] ?? 'Booked';
        
        if (empty($workorder_no) || empty($linemaster_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number and line ID are required']);
            exit;
        }
        
        // ============================================
        // STOCK VERIFICATION BEFORE BOOKING
        // ============================================
        $plant_id = $_GET["plant_id"] ?? '';
        if (empty($plant_id)) {
            // Try to get plant_id from Work_order_materials
            $plantSql = "SELECT om.plant_id 
                        FROM Work_order_materials wom
                        LEFT JOIN order_materials om ON wom.order_no = om.order_no
                        LEFT JOIN po_entry po ON om.order_no = po.order_no
                        WHERE wom.workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'
                        LIMIT 1";
            $plantResult = $conn->query($plantSql);
            if ($plantResult && $plantResult->num_rows > 0) {
                $plantRow = $plantResult->fetch_assoc();
                $plant_id = $plantRow['plant_id'] ?? '';
            }
        }
        
        $stockVerification = verifyStockForLineBooking($conn, $workorder_no, $plant_id);
        
        if (!$stockVerification['canProceed']) {
            echo json_encode([
                'status' => 'error', 
                'message' => $stockVerification['message'],
                'stockVerification' => $stockVerification
            ]);
            exit;
        }
        
        // Since stock verification passed, set status to 'Booked' to enable View button in QA BMR Pending
        // This ensures the work order can proceed immediately after stock verification
        $status = 'Booked';
        
        // Check for conflicts
        $conflicts = [];
        $conflictWarning = '';
        if ($booking_start_date && $booking_end_date) {
            $conflictSql = "SELECT * FROM line_booking 
                           WHERE linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."' 
                           AND status NOT IN ('Cancelled', 'Completed')
                           AND workorder_no != '".mysqli_real_escape_string($conn, $workorder_no)."'
                           AND (
                               (booking_start_date <= '".mysqli_real_escape_string($conn, $booking_end_date)."' AND booking_end_date >= '".mysqli_real_escape_string($conn, $booking_start_date)."')
                           )";
            $conflictResult = $conn->query($conflictSql);
            if ($conflictResult && $conflictResult->num_rows > 0) {
                while ($conflictRow = $conflictResult->fetch_assoc()) {
                    $conflictStart = $conflictRow['booking_start_date'] . ' ' . $conflictRow['booking_start_time'];
                    $conflictEnd = $conflictRow['booking_end_date'] . ' ' . $conflictRow['booking_end_time'];
                    $requestStart = $booking_start_date . ' ' . $booking_start_time;
                    $requestEnd = $booking_end_date . ' ' . $booking_end_time;
                    
                    if (strtotime($requestStart) < strtotime($conflictEnd) && strtotime($requestEnd) > strtotime($conflictStart)) {
                        $conflicts[] = $conflictRow['workorder_no'];
                    }
                }
                
                if (count($conflicts) > 0) {
                    // Show warning but allow booking (don't exit)
                    $conflictWarning = 'Warning: Line is already booked for the selected time slot. Conflicts with work orders: ' . implode(', ', $conflicts);
                    // Continue with booking - just add warning to response
                }
            }
        }
        
        // Insert booking
        $equipmentJson = mysqli_real_escape_string($conn, json_encode($selected_equipments));
        
        // Check if already exists with same workorder and line (avoid duplicates)
        $checkExisting = "SELECT id FROM line_booking 
                         WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."' 
                         AND linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."'
                         AND status = '".mysqli_real_escape_string($conn, $status)."'";
        $existingResult = $conn->query($checkExisting);
        
        if ($existingResult && $existingResult->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Line already booked for this work order']);
            exit;
        }
        
        $sql = "INSERT INTO line_booking (
            workorder_no,
            linemaster_id,
            line_no,
            product_code,
            product_name,
            booking_start_date,
            booking_start_time,
            booking_end_date,
            booking_end_time,
            responsible_person,
            selected_equipments,
            capacity_required,
            no_of_hours_required,
            status,
            entry_by,
            entry_date,
            plant_id
        ) VALUES (
            '".mysqli_real_escape_string($conn, $workorder_no)."',
            '".mysqli_real_escape_string($conn, $linemaster_id)."',
            '".mysqli_real_escape_string($conn, $line_no)."',
            '".mysqli_real_escape_string($conn, $product_code)."',
            '".mysqli_real_escape_string($conn, $product_name)."',
            '".mysqli_real_escape_string($conn, $booking_start_date)."',
            '".mysqli_real_escape_string($conn, $booking_start_time)."',
            '".mysqli_real_escape_string($conn, $booking_end_date)."',
            '".mysqli_real_escape_string($conn, $booking_end_time)."',
            '".mysqli_real_escape_string($conn, $responsible_person)."',
            '".$equipmentJson."',
            '".mysqli_real_escape_string($conn, $capacity_required)."',
            '".mysqli_real_escape_string($conn, $no_of_hours_required)."',
            '".mysqli_real_escape_string($conn, $status)."',
            '".$_GET["emp_id"]."',
            '".$entry_date."',
            '".$_GET["plant_id"]."'
        )";
        
        if ($conn->query($sql)) {
            // Log the action to database log table
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'bookLine: ' . mysqli_real_escape_string($conn, $workorder_no) . ' - Line: ' . mysqli_real_escape_string($conn, $line_no);
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            // Also enhance file log with workorder number
            $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "bookLine", "actiontime": "'.$logEntryDate.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'", "workorder_no": "'.$workorder_no.'", "line_no": "'.$line_no.'"}';
            $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            $response = [
                'status' => 'success', 
                'message' => 'Line booked successfully', 
                'booking_id' => $conn->insert_id,
                'stockVerification' => $stockVerification
            ];
            if (isset($conflictWarning)) {
                $response['warning'] = $conflictWarning;
                $response['conflicts'] = $conflicts;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to book line: ' . $conn->error]);
        }
    }
    
    // ============================================
    // GET BOOKING HISTORY
    // ============================================
    else if ($_GET["type"] == "getBookingHistory") {
        $output = Array();
        
        $whereClause = "1=1";
        if (isset($_GET["workorder_no"]) && $_GET["workorder_no"] != '') {
            $whereClause .= " AND lb.workorder_no = '".mysqli_real_escape_string($conn, $_GET["workorder_no"])."'";
        }
        if (isset($_GET["line_no"]) && $_GET["line_no"] != '') {
            $whereClause .= " AND lb.line_no = '".mysqli_real_escape_string($conn, $_GET["line_no"])."'";
        }
        if (isset($_GET["linemaster_id"]) && $_GET["linemaster_id"] != '') {
            $whereClause .= " AND lb.linemaster_id = '".mysqli_real_escape_string($conn, $_GET["linemaster_id"])."'";
        }
        if (isset($_GET["status"]) && $_GET["status"] != '') {
            // Handle comma-separated status values
            $statuses = explode(',', $_GET["status"]);
            $statusConditions = [];
            foreach ($statuses as $status) {
                $status = trim($status);
                if ($status != '') {
                    $statusConditions[] = "lb.status = '".mysqli_real_escape_string($conn, $status)."'";
                }
            }
            if (count($statusConditions) > 0) {
                $whereClause .= " AND (".implode(" OR ", $statusConditions).")";
            }
        }
        if (isset($_GET["date_from"]) && $_GET["date_from"] != '') {
            $whereClause .= " AND lb.booking_start_date >= '".mysqli_real_escape_string($conn, $_GET["date_from"])."'";
        }
        if (isset($_GET["date_to"]) && $_GET["date_to"] != '') {
            $whereClause .= " AND lb.booking_end_date <= '".mysqli_real_escape_string($conn, $_GET["date_to"])."'";
        }
        
        $sql = "SELECT lb.*, 
                lm.line_name, lm.Section, lm.MfgLineMinCapacity, lm.MfgLineMaxCapacity,
                lm.FillingLineMinCapacity, lm.FillingLineMaxCapacity
                FROM line_booking lb
                LEFT JOIN linemaster lm ON lb.linemaster_id = lm.id
                WHERE ".$whereClause."
                ORDER BY lb.booking_start_date DESC, lb.booking_start_time DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decode equipment JSON
                $row["selected_equipments"] = json_decode($row["selected_equipments"] ?? '[]', true);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // UPDATE BOOKING
    // ============================================
    else if ($_GET["type"] == "updateBooking") {
        $booking_id = $input['booking_id'] ?? '';
        
        if (empty($booking_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Booking ID is required']);
            exit;
        }
        
        $updates = [];
        
        if (isset($input['booking_start_date'])) {
            $updates[] = "booking_start_date = '".mysqli_real_escape_string($conn, $input['booking_start_date'])."'";
        }
        if (isset($input['booking_start_time'])) {
            $updates[] = "booking_start_time = '".mysqli_real_escape_string($conn, $input['booking_start_time'])."'";
        }
        if (isset($input['booking_end_date'])) {
            $updates[] = "booking_end_date = '".mysqli_real_escape_string($conn, $input['booking_end_date'])."'";
        }
        if (isset($input['booking_end_time'])) {
            $updates[] = "booking_end_time = '".mysqli_real_escape_string($conn, $input['booking_end_time'])."'";
        }
        if (isset($input['responsible_person'])) {
            $updates[] = "responsible_person = '".mysqli_real_escape_string($conn, $input['responsible_person'])."'";
        }
        if (isset($input['selected_equipments'])) {
            $equipmentJson = mysqli_real_escape_string($conn, json_encode($input['selected_equipments']));
            $updates[] = "selected_equipments = '".$equipmentJson."'";
        }
        if (isset($input['status'])) {
            $updates[] = "status = '".mysqli_real_escape_string($conn, $input['status'])."'";
        }
        
        if (empty($updates)) {
            echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
            exit;
        }
        
        $updates[] = "updated_by = '".$_GET["emp_id"]."'";
        $updates[] = "updated_date = '".$entry_date."'";
        
        $sql = "UPDATE line_booking SET " . implode(', ', $updates) . " WHERE id = '".mysqli_real_escape_string($conn, $booking_id)."'";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update booking: ' . $conn->error]);
        }
    }
    
    // ============================================
    // CANCEL BOOKING
    // ============================================
    else if ($_GET["type"] == "cancelBooking") {
        $booking_id = $input['booking_id'] ?? '';
        $cancellation_reason = $input['cancellation_reason'] ?? '';
        
        if (empty($booking_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Booking ID is required']);
            exit;
        }
        
        $sql = "UPDATE line_booking 
                SET status = 'Cancelled',
                    cancellation_reason = '".mysqli_real_escape_string($conn, $cancellation_reason)."',
                    cancelled_by = '".$_GET["emp_id"]."',
                    cancelled_date = '".$entry_date."'
                WHERE id = '".mysqli_real_escape_string($conn, $booking_id)."'";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Booking cancelled successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to cancel booking: ' . $conn->error]);
        }
    }
    
    // ============================================
    // CHECK LINE AVAILABILITY
    // ============================================
    else if ($_GET["type"] == "checkLineAvailability") {
        $linemaster_id = $_GET["linemaster_id"] ?? '';
        $start_date = $_GET["start_date"] ?? '';
        $start_time = $_GET["start_time"] ?? '';
        $end_date = $_GET["end_date"] ?? '';
        $end_time = $_GET["end_time"] ?? '';
        $exclude_booking_id = $_GET["exclude_booking_id"] ?? '';
        
        if (empty($linemaster_id) || empty($start_date) || empty($end_date)) {
            echo json_encode(['status' => 'error', 'message' => 'Line ID, start date, and end date are required']);
            exit;
        }
        
        $whereClause = "linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."' 
                      AND status NOT IN ('Cancelled', 'Completed')
                      AND (
                          (booking_start_date <= '".mysqli_real_escape_string($conn, $end_date)."' 
                           AND booking_end_date >= '".mysqli_real_escape_string($conn, $start_date)."')
                      )";
        
        if ($exclude_booking_id != '') {
            $whereClause .= " AND id != '".mysqli_real_escape_string($conn, $exclude_booking_id)."'";
        }
        
        $sql = "SELECT * FROM line_booking WHERE ".$whereClause;
        $result = $conn->query($sql);
        
        $conflicts = [];
        $isAvailable = true;
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $conflictStart = $row['booking_start_date'] . ' ' . $row['booking_start_time'];
                $conflictEnd = $row['booking_end_date'] . ' ' . $row['booking_end_time'];
                $requestStart = $start_date . ' ' . ($start_time ? $start_time : '00:00:00');
                $requestEnd = $end_date . ' ' . ($end_time ? $end_time : '23:59:59');
                
                if (strtotime($requestStart) < strtotime($conflictEnd) && strtotime($requestEnd) > strtotime($conflictStart)) {
                    $isAvailable = false;
                    $conflicts[] = [
                        'booking_id' => $row['id'],
                        'workorder_no' => $row['workorder_no'],
                        'start' => $conflictStart,
                        'end' => $conflictEnd,
                        'status' => $row['status']
                    ];
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'isAvailable' => $isAvailable,
            'conflicts' => $conflicts
        ]);
    }
    
    // ============================================
    // GET LINE BOOKING DASHBOARD
    // ============================================
    else if ($_GET["type"] == "getLineBookingDashboard") {
        $output = Array();
        
        // Get all lines with booking status
        $lineSql = "SELECT lm.*, 
                    COUNT(lb.id) as total_bookings,
                    SUM(CASE WHEN lb.status = 'Booked' THEN 1 ELSE 0 END) as booked_count,
                    SUM(CASE WHEN lb.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN lb.status = 'Completed' THEN 1 ELSE 0 END) as completed_count
                    FROM linemaster lm
                    LEFT JOIN line_booking lb ON lm.id = lb.linemaster_id AND lb.status != 'Cancelled'
                    GROUP BY lm.id
                    ORDER BY lm.line_no";
        
        $lineResult = $conn->query($lineSql);
        
        if ($lineResult && $lineResult->num_rows > 0) {
            while ($lineRow = $lineResult->fetch_assoc()) {
                $lineId = $lineRow['id'];
                
                // Get current bookings
                $currentBookings = [];
                $bookingSql = "SELECT * FROM line_booking 
                              WHERE linemaster_id = '".$lineId."' 
                              AND status IN ('Booked', 'In Progress')
                              ORDER BY booking_start_date, booking_start_time";
                $bookingResult = $conn->query($bookingSql);
                if ($bookingResult && $bookingResult->num_rows > 0) {
                    while ($bookingRow = $bookingResult->fetch_assoc()) {
                        $bookingRow["selected_equipments"] = json_decode($bookingRow["selected_equipments"] ?? '[]', true);
                        $currentBookings[] = $bookingRow;
                    }
                }
                
                $lineRow["currentBookings"] = $currentBookings;
                $output[] = $lineRow;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // PARK LINES FOR MFGLINES (Save selected lines for production)
    // ============================================
    else if ($_GET["type"] == "parkLinesForMfg") {
        $workorder_no = $input['workorder_no'] ?? '';
        $workorder_id = $input['workorder_id'] ?? '';
        $lineList = $input['lineList'] ?? [];
        $product_code = $input['product_code'] ?? '';
        $product_name = $input['product_name'] ?? '';
        $booking_start_date = $input['booking_start_date'] ?? '';
        $booking_start_time = $input['booking_start_time'] ?? '';
        $booking_end_date = $input['booking_end_date'] ?? '';
        $booking_end_time = $input['booking_end_time'] ?? '';
        $responsible_person = $input['responsible_person'] ?? '';
        $batch_size = $input['batch_size'] ?? '';
        $planUnit = $input['planUnit'] ?? '';
        
        if (empty($workorder_no) || empty($lineList) || count($lineList) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number and line list are required']);
            exit;
        }
        
        // ============================================
        // STOCK VERIFICATION BEFORE PARKING LINES
        // ============================================
        $plant_id = $_GET["plant_id"] ?? '';
        if (empty($plant_id)) {
            // Try to get plant_id from Work_order_materials
            $plantSql = "SELECT om.plant_id 
                        FROM Work_order_materials wom
                        LEFT JOIN order_materials om ON wom.order_no = om.order_no
                        LEFT JOIN po_entry po ON om.order_no = po.order_no
                        WHERE wom.workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'
                        LIMIT 1";
            $plantResult = $conn->query($plantSql);
            if ($plantResult && $plantResult->num_rows > 0) {
                $plantRow = $plantResult->fetch_assoc();
                $plant_id = $plantRow['plant_id'] ?? '';
            }
        }
        
        $stockVerification = verifyStockForLineBooking($conn, $workorder_no, $plant_id);
        
        if (!$stockVerification['canProceed']) {
            echo json_encode([
                'status' => 'error', 
                'message' => $stockVerification['message'],
                'stockVerification' => $stockVerification
            ]);
            exit;
        }
        
        // Since stock verification passed, set status to 'Booked' instead of 'Parked'
        // This enables the View button in QA BMR Pending immediately after stock verification
        $bookingStatus = 'Booked';
        
        $parkedCount = 0;
        $errors = [];
        
        foreach ($lineList as $line) {
            $linemaster_id = $line['id'] ?? '';
            $line_no = $line['line_no'] ?? '';
            $equipmentList = $line['equipmentList'] ?? [];
            
            if (empty($linemaster_id) || empty($line_no)) {
                $errors[] = "Invalid line data for line: " . ($line_no ?: 'Unknown');
                continue;
            }
            
            // Check if already booked/parked for this work order and line
            $checkSql = "SELECT id, status FROM line_booking 
                        WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."' 
                        AND linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."'
                        AND status IN ('Parked', 'Booked')";
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                // Update existing entry to 'Booked' status (since stock is verified)
                $equipmentJson = mysqli_real_escape_string($conn, json_encode($equipmentList));
                $updateSql = "UPDATE line_booking SET
                    product_code = '".mysqli_real_escape_string($conn, $product_code)."',
                    product_name = '".mysqli_real_escape_string($conn, $product_name)."',
                    booking_start_date = '".mysqli_real_escape_string($conn, $booking_start_date)."',
                    booking_start_time = '".mysqli_real_escape_string($conn, $booking_start_time)."',
                    booking_end_date = '".mysqli_real_escape_string($conn, $booking_end_date)."',
                    booking_end_time = '".mysqli_real_escape_string($conn, $booking_end_time)."',
                    responsible_person = '".mysqli_real_escape_string($conn, $responsible_person)."',
                    selected_equipments = '".$equipmentJson."',
                    capacity_required = '".mysqli_real_escape_string($conn, $batch_size)."',
                    status = '".mysqli_real_escape_string($conn, $bookingStatus)."',
                    entry_by = '".$_GET["emp_id"]."',
                    entry_date = '".$entry_date."',
                    updated_by = '".$_GET["emp_id"]."',
                    updated_date = '".$entry_date."'
                    WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."' 
                    AND linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."'";
                
                if ($conn->query($updateSql)) {
                    $parkedCount++;
                } else {
                    $errors[] = "Failed to update line {$line_no}: " . $conn->error;
                }
            } else {
                // Insert new entry with 'Booked' status (since stock is verified)
                $equipmentJson = mysqli_real_escape_string($conn, json_encode($equipmentList));
                
                $insertSql = "INSERT INTO line_booking (
                    workorder_no,
                    linemaster_id,
                    line_no,
                    product_code,
                    product_name,
                    booking_start_date,
                    booking_start_time,
                    booking_end_date,
                    booking_end_time,
                    responsible_person,
                    selected_equipments,
                    capacity_required,
                    status,
                    entry_by,
                    entry_date,
                    plant_id
                ) VALUES (
                    '".mysqli_real_escape_string($conn, $workorder_no)."',
                    '".mysqli_real_escape_string($conn, $linemaster_id)."',
                    '".mysqli_real_escape_string($conn, $line_no)."',
                    '".mysqli_real_escape_string($conn, $product_code)."',
                    '".mysqli_real_escape_string($conn, $product_name)."',
                    '".mysqli_real_escape_string($conn, $booking_start_date)."',
                    '".mysqli_real_escape_string($conn, $booking_start_time)."',
                    '".mysqli_real_escape_string($conn, $booking_end_date)."',
                    '".mysqli_real_escape_string($conn, $booking_end_time)."',
                    '".mysqli_real_escape_string($conn, $responsible_person)."',
                    '".$equipmentJson."',
                    '".mysqli_real_escape_string($conn, $batch_size)."',
                    '".mysqli_real_escape_string($conn, $bookingStatus)."',
                    '".$_GET["emp_id"]."',
                    '".$entry_date."',
                    '".$_GET["plant_id"]."'
                )";
                
                if ($conn->query($insertSql)) {
                    $parkedCount++;
                } else {
                    $errors[] = "Failed to book line {$line_no}: " . $conn->error;
                }
            }
        }
        
        if ($parkedCount > 0) {
            $message = "Successfully booked {$parkedCount} line(s) with stock verification";
            if (count($errors) > 0) {
                $message .= ". Errors: " . implode(", ", $errors);
            }
            
            // Log the action to database log table
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'parkLinesForMfg: ' . mysqli_real_escape_string($conn, $workorder_no) . ' (Stock Verified - Status: Booked)';
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            // Also enhance file log with workorder number
            $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "parkLinesForMfg", "actiontime": "'.$logEntryDate.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'", "workorder_no": "'.$workorder_no.'", "status": "Booked", "stock_verified": true}';
            $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            // Note: We don't update Work_order_materials status to 'Parked for Approval' anymore
            // since the line is already 'Booked' and can proceed directly
            
            echo json_encode([
                'status' => 'success', 
                'message' => $message, 
                'parked_count' => $parkedCount,
                'stockVerification' => $stockVerification,
                'lineStatus' => 'Booked'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to book lines. Errors: ' . implode(", ", $errors)]);
        }
    }
    
    // ============================================
    // GET PARKED FOR APPROVAL LOG (Work orders sent for approval)
    // ============================================
    else if ($_GET["type"] == "getParkedForApprovalLog") {
        $output = Array();
        
        $sql = "SELECT a.*, b.product_code, b.work_order_planned_qty, b.planMonth,
                b.mainGroupName, b.packingStyle AS packing_type,
                (SELECT product_name FROM product c 
                 WHERE b.product_code = c.product_code LIMIT 1) AS product_name 
                FROM Work_order_materials a
                LEFT JOIN order_materials b ON a.order_no = b.order_no
                WHERE a.status = 'Parked for Approval'
                ORDER BY a.Wo_Generated_on DESC";
        
        $result = $conn->query($sql);
        
        $colors = [
            "#FFCCCB", "#CCFFCC", "#CCE5FF", "#FFFACD", "#D1C4E9", "#FFE0B2",
            "#F8BBD0", "#B2EBF2", "#E6EE9C", "#FFECB3", "#CFD8DC", "#F0F4C3",
            "#DCEDC8", "#F5F5F5", "#E1BEE7", "#BBDEFB", "#FFCDD2", "#D7CCC8",
            "#FFCC80", "#C8E6C9"
        ];
        $colorMap = [];
        $colorIndex = 0;
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $po = $row["order_no"];
                if (!isset($colorMap[$po])) {
                    $colorMap[$po] = $colors[$colorIndex % count($colors)];
                    $colorIndex++;
                }
                $row["bg_color"] = $colorMap[$po];
                
                // Get deductions from WO_deductions table
                $deductionsSql = "SELECT * FROM WO_deductions WHERE workorder_no = '".mysqli_real_escape_string($conn, $row['workorder_no'])."'";
                $deductionsResult = $conn->query($deductionsSql);
                $deductions = [];
                
                if ($deductionsResult && $deductionsResult->num_rows > 0) {
                    while ($dedRow = $deductionsResult->fetch_assoc()) {
                        $deductions[] = [
                            'material_code' => $dedRow['material_code'] ?? '',
                            'material_name' => $dedRow['material_name'] ?? '',
                            'mat_type' => $dedRow['mat_type'] ?? '',
                            'requiredQty' => floatval($dedRow['required_qty'] ?? 0),
                            'deducted_from_RM' => floatval($dedRow['deducted_from_RM'] ?? 0),
                            'deducted_from_MC' => floatval($dedRow['deducted_from_MC'] ?? 0),
                            'shortage' => floatval($dedRow['shortage'] ?? 0),
                            'unit' => $dedRow['unit'] ?? ''
                        ];
                    }
                }
                
                // Get selected lines from line_booking table
                $linesSql = "SELECT lb.*, lm.line_name 
                            FROM line_booking lb 
                            LEFT JOIN linemaster lm ON lb.linemaster_id = lm.id 
                            WHERE lb.workorder_no = '".mysqli_real_escape_string($conn, $row['workorder_no'])."' 
                            AND lb.status = 'Parked'";
                $linesResult = $conn->query($linesSql);
                $selectedLines = [];
                
                if ($linesResult && $linesResult->num_rows > 0) {
                    while ($lineRow = $linesResult->fetch_assoc()) {
                        $equipmentList = json_decode($lineRow['selected_equipments'] ?? '[]', true);
                        $selectedLines[] = [
                            'id' => $lineRow['linemaster_id'],
                            'line_no' => $lineRow['line_no'],
                            'line_name' => $lineRow['line_name'] ?? '',
                            'equipmentList' => $equipmentList,
                            'booking_start_date' => $lineRow['booking_start_date'],
                            'booking_start_time' => $lineRow['booking_start_time'],
                            'booking_end_date' => $lineRow['booking_end_date'],
                            'booking_end_time' => $lineRow['booking_end_time'],
                            'responsible_person' => $lineRow['responsible_person']
                        ];
                    }
                }
                
                $row['Deductions'] = $deductions;
                $row['selectedLines'] = $selectedLines;
                $output[] = $row;
            }
        }
        
        echo json_encode([
            'can_plan_work_orders' => $output,
            'shortage_info' => []
        ]);
    }
    
    // ============================================
    // GET CALENDAR VIEW (Bookings by Date)
    // ============================================
    else if ($_GET["type"] == "getCalendarView") {
        $start_date = $_GET["start_date"] ?? date('Y-m-d');
        $end_date = $_GET["end_date"] ?? date('Y-m-d', strtotime('+30 days'));
        $line_no = $_GET["line_no"] ?? '';
        
        $output = Array();
        
        $whereClause = "booking_start_date >= '".mysqli_real_escape_string($conn, $start_date)."' 
                       AND booking_end_date <= '".mysqli_real_escape_string($conn, $end_date)."'
                       AND status != 'Cancelled'";
        
        if ($line_no != '') {
            $whereClause .= " AND line_no = '".mysqli_real_escape_string($conn, $line_no)."'";
        }
        
        $sql = "SELECT lb.*, lm.line_name, lm.Section
                FROM line_booking lb
                LEFT JOIN linemaster lm ON lb.linemaster_id = lm.id
                WHERE ".$whereClause."
                ORDER BY lb.booking_start_date, lb.booking_start_time";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["selected_equipments"] = json_decode($row["selected_equipments"] ?? '[]', true);
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // GET PARKED WORK ORDERS (For Approval Tab)
    // ============================================
    else if ($_GET["type"] == "getParkedWorkOrders") {
        $output = Array();
        
        // Get work orders that have parked lines
        // Get product_code, product_name, plan_qty, planUnit, and responsible_person from line_booking and order_materials
        $sql = "SELECT DISTINCT 
                    wom.id,
                    wom.workorder_no,
                    wom.order_no,
                    wom.selectedLines,
                    wom.batch_size,
                    wom.expected_production_start_date,
                    wom.expected_production_start_time,
                    wom.expected_production_end_date,
                    wom.expected_production_end_time,
                    wom.no_of_hours_required,
                    wom.responsible_person AS wo_responsible_person,
                    COALESCE(
                        NULLIF(TRIM(wom.product_code), ''),
                        (SELECT lb.product_code FROM line_booking lb
                          WHERE lb.workorder_no = wom.workorder_no AND lb.status = 'Parked' LIMIT 1)
                    ) as product_code,
                    (SELECT p.product_name FROM product p
                      WHERE p.product_code = COALESCE(NULLIF(TRIM(wom.product_code), ''),
                        (SELECT lb.product_code FROM line_booking lb
                          WHERE lb.workorder_no = wom.workorder_no AND lb.status = 'Parked' LIMIT 1))
                      LIMIT 1) as product_name,
                    (SELECT om.planMonth FROM order_materials om
                      WHERE om.order_no = wom.order_no
                      ORDER BY CASE WHEN om.product_code = wom.product_code THEN 0 ELSE 1 END, om.id DESC
                      LIMIT 1) AS planMonth,
                    (SELECT NULLIF(NULLIF(TRIM(om.deliveryDate), ''), '0000-00-00')
                       FROM order_materials om
                      WHERE om.order_no = wom.order_no
                      ORDER BY CASE WHEN om.product_code = wom.product_code THEN 0 ELSE 1 END, om.id DESC
                      LIMIT 1) AS om_deliveryDate,
                    COALESCE(
                        (SELECT NULLIF(TRIM(om.mainGroupName), '') FROM order_materials om
                          WHERE om.order_no = wom.order_no
                          ORDER BY CASE WHEN om.product_code = wom.product_code THEN 0 ELSE 1 END, om.id DESC
                          LIMIT 1),
                        (SELECT c.LglNm FROM po_entry po
                          LEFT JOIN client c ON po.client_code = c.client_code
                          WHERE po.order_no = wom.order_no LIMIT 1)
                    ) AS mainGroupName,
                    (SELECT lb.capacity_required 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Parked' 
                     LIMIT 1) as plan_qty,
                    (SELECT lb.responsible_person 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Parked' 
                     LIMIT 1) as lb_responsible_person,
                    COALESCE(
                        NULLIF(TRIM(wom.planUnit), ''),
                        (SELECT NULLIF(TRIM(om.planUnit), '') FROM order_materials om
                          WHERE om.order_no = wom.order_no
                          ORDER BY CASE WHEN om.product_code = wom.product_code THEN 0 ELSE 1 END, om.id DESC
                          LIMIT 1)
                    ) AS planUnit,
                    (SELECT po.client_code 
                     FROM order_materials om left join po_entry po on om.order_no=po.order_no 
                     WHERE om.order_no = wom.order_no 
                     LIMIT 1) as client_code,
                    (SELECT c.LglNm 
                     FROM order_materials om left join po_entry po on om.order_no=po.order_no  left join client c on po.client_code=c.client_code
                     WHERE om.order_no = wom.order_no 
                     LIMIT 1) as client_name
                FROM Work_order_materials wom
                WHERE (
                    LOWER(TRIM(IFNULL(wom.stp_line_approval_status, ''))) = 'pending'
                    OR EXISTS (
                        SELECT 1 FROM line_booking lb2
                        WHERE lb2.workorder_no = wom.workorder_no
                        AND lb2.status = 'Parked'
                    )
                )
                AND LOWER(TRIM(IFNULL(wom.stp_line_approval_status, ''))) NOT IN ('approved', 'rejected')
                AND IFNULL(wom.advance_planning_flag, 'No') != 'Yes'
                ORDER BY wom.workorder_no DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Parse selectedLines JSON
                $selectedLines = json_decode($row["selectedLines"] ?? '[]', true);
                $row["selectedLines"] = is_array($selectedLines) ? $selectedLines : array();
                if (empty($row['responsible_person'])) {
                    $row['responsible_person'] = $row['wo_responsible_person'] ?? ($row['lb_responsible_person'] ?? '');
                }
                if (empty($row['mainGroupName'])) {
                    $row['mainGroupName'] = $row['client_name'] ?? '';
                }
                if (empty($row['plan_qty']) || floatval($row['plan_qty']) <= 0) {
                    $row['plan_qty'] = $row['batch_size'] ?? '';
                }
                lbFillWoQtyAndDates($row);
                if (function_exists('lbEnrichWoDisplayRow')) {
                    lbEnrichWoDisplayRow($conn, $row);
                }
                
                // Get deductions/materials for stock verification
                $deductions = [];
                $dedSql = "SELECT d.*, 
                          (SELECT material_name FROM my_view m WHERE m.material_code = d.material_code LIMIT 1) as material_name
                          FROM WO_deductions d 
                          WHERE d.workorder_no = '".$row['workorder_no']."'";
                $dedResult = $conn->query($dedSql);
                
                // Get plant_id for stock checking
                $plant_id = $_GET["plant_id"] ?? '';
                if (empty($plant_id)) {
                    // Try to get plant_id from order_materials
                    $plantSql = "SELECT om.plant_id 
                                FROM order_materials om 
                                LEFT JOIN po_entry po ON om.order_no = po.order_no 
                                WHERE om.order_no = '".mysqli_real_escape_string($conn, $row['order_no'] ?? '')."' 
                                LIMIT 1";
                    $plantResult = $conn->query($plantSql);
                    if ($plantResult && $plantResult->num_rows > 0) {
                        $plantRow = $plantResult->fetch_assoc();
                        $plant_id = $plantRow['plant_id'] ?? '';
                    }
                }
                
                if ($dedResult && $dedResult->num_rows > 0) {
                    while ($dedRow = $dedResult->fetch_assoc()) {
                        // Calculate required qty
                        $requiredQty = (floatval($dedRow['deducted_from_MC'] ?? 0) + floatval($dedRow['deducted_from_RM'] ?? 0) + floatval($dedRow['shortage'] ?? 0));
                        $dedRow['requiredQty'] = $requiredQty;
                        
                        // RE-CHECK CURRENT STOCK AVAILABILITY FROM stock_book/vw_total_available_stock
                        $material_code = mysqli_real_escape_string($conn, $dedRow['material_code'] ?? '');
                        $currentAvailableRM = 0;
                        $currentAvailableMC = 0;
                        $currentTotalAvailable = 0;
                        $currentShortage = 0;
                        
                        if (!empty($material_code) && !empty($plant_id)) {
                            // Check current RM stock from vw_total_available_stock
                            $currentStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                              WHERE material_code = '".$material_code."' 
                                              AND plant_id = '".mysqli_real_escape_string($conn, $plant_id)."' 
                                              LIMIT 1";
                            $currentStockResult = $conn->query($currentStockSql);
                            if ($currentStockResult && $currentStockResult->num_rows > 0) {
                                $currentStockRow = $currentStockResult->fetch_assoc();
                                $currentAvailableRM = floatval($currentStockRow['available_qty'] ?? 0);
                            }
                            
                            // Check for Mother Code stock
                            $motherCodeSql = "SELECT mother_material_code FROM material 
                                             WHERE material_code = '".$material_code."' 
                                             LIMIT 1";
                            $motherCodeResult = $conn->query($motherCodeSql);
                            if ($motherCodeResult && $motherCodeResult->num_rows > 0) {
                                $motherCodeRow = $motherCodeResult->fetch_assoc();
                                $mother_code = $motherCodeRow['mother_material_code'] ?? null;
                                
                                if ($mother_code) {
                                    $mcStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                                 WHERE material_code = '".mysqli_real_escape_string($conn, $mother_code)."' 
                                                 AND plant_id = '".mysqli_real_escape_string($conn, $plant_id)."' 
                                                 LIMIT 1";
                                    $mcStockResult = $conn->query($mcStockSql);
                                    if ($mcStockResult && $mcStockResult->num_rows > 0) {
                                        $mcStockRow = $mcStockResult->fetch_assoc();
                                        $currentAvailableMC = floatval($mcStockRow['available_qty'] ?? 0);
                                    }
                                }
                            }
                            
                            // Calculate current total available and shortage
                            $currentTotalAvailable = $currentAvailableRM + $currentAvailableMC;
                            if ($currentTotalAvailable < $requiredQty) {
                                $currentShortage = $requiredQty - $currentTotalAvailable;
                            } else {
                                $currentShortage = 0; // Stock is available
                            }
                            
                            // Update shortage with current stock check result
                            $dedRow['shortage'] = $currentShortage;
                            $dedRow['current_available_RM'] = $currentAvailableRM;
                            $dedRow['current_available_MC'] = $currentAvailableMC;
                            $dedRow['current_total_available'] = $currentTotalAvailable;
                        }
                        
                        // Check if indent exists in indend_raw table for this material and work order
                        $workorder_no = mysqli_real_escape_string($conn, $row['workorder_no']);
                        
                        $indentExists = false;
                        $indentNo = '';
                        $indentId = '';
                        
                        if (!empty($material_code)) {
                            // Check indend_raw table - required_for contains JSON with work_order_no
                            $indentCheckSql = "SELECT id, no, request_no, status 
                                              FROM indend_raw 
                                              WHERE material_code = '".$material_code."'
                                              AND required_for LIKE '%\"work_order_no\":\"".$workorder_no."\"%'
                                              AND status != 'Rejected'
                                              ORDER BY id DESC LIMIT 1";
                            $indentCheckResult = $conn->query($indentCheckSql);
                            
                            if ($indentCheckResult && $indentCheckResult->num_rows > 0) {
                                $indentRow = $indentCheckResult->fetch_assoc();
                                $indentExists = true;
                                $indentNo = $indentRow['no'] ?? '';
                                $indentId = $indentRow['id'] ?? '';
                                
                                // Also update WO_deductions if indent_status is not set
                                if (empty($dedRow['indent_status']) || $dedRow['indent_status'] == 'Not Raised') {
                                    $dedRow['indent_status'] = 'Raised';
                                    $dedRow['indent_no'] = $indentNo;
                                    $dedRow['indent_id'] = $indentId;
                                }
                            }
                        }
                        
                        // If WO_deductions has indent_status, use that (it's more reliable)
                        if (!empty($dedRow['indent_status']) && ($dedRow['indent_status'] == 'Raised' || $dedRow['indent_status'] == 'Indent Sent')) {
                            $indentExists = true;
                            if (empty($indentNo) && !empty($dedRow['indent_no'])) {
                                $indentNo = $dedRow['indent_no'];
                            }
                            if (empty($indentId) && !empty($dedRow['indent_id'])) {
                                $indentId = $dedRow['indent_id'];
                            }
                        }
                        
                        $dedRow['indent_exists_in_indend_raw'] = $indentExists;
                        if ($indentExists) {
                            $dedRow['indent_no_from_indend_raw'] = $indentNo;
                            $dedRow['indent_id_from_indend_raw'] = $indentId;
                        }
                        
                        $deductions[] = $dedRow;
                    }
                }
                $row["Deductions"] = $deductions;
                
                // Check if stock is complete (no shortages)
                $hasShortage = false;
                foreach ($deductions as $ded) {
                    if (floatval($ded['shortage'] ?? 0) > 0) {
                        $hasShortage = true;
                        break;
                    }
                }
                $row["stockComplete"] = !$hasShortage;
                $row["hasShortage"] = $hasShortage;
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // APPROVE WORK ORDER (Change status from Parked to Booked)
    // ============================================
    else if ($_GET["type"] == "approveWorkOrder") {
        if (!is_array($input)) {
            $input = array();
        }
        $workorder_id = $input["workorder_id"] ?? '';
        $workorder_no = $input["workorder_no"] ?? '';
        
        if (empty($workorder_no)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
            exit;
        }
        
        // Update status from 'Parked' to 'Booked' for all lines of this work order
        $sql = "UPDATE line_booking 
                SET status = 'Booked',
                    entry_by = '".mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '')."',
                    entry_date = NOW()
                WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'
                AND status = 'Parked'";
        
        if ($conn->query($sql)) {
            $woEsc = mysqli_real_escape_string($conn, $workorder_no);
            @$conn->query("UPDATE Work_order_materials SET
                    stp_line_approval_status = 'Approved'
                  WHERE workorder_no = '".$woEsc."'");
            $bpHelper = dirname(__DIR__) . '/marketing/can_planned_wo_helpers.php';
            if (is_file($bpHelper)) {
                require_once $bpHelper;
            }
            if (function_exists('stp_ensure_batch_plan_for_work_order')) {
                stp_ensure_batch_plan_for_work_order(
                    $conn,
                    $workorder_no,
                    $_GET['emp_id'] ?? '',
                    $_GET['plant_id'] ?? ''
                );
            }
            // Log the action to database log table
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'approveWorkOrder: ' . mysqli_real_escape_string($conn, $workorder_no);
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            // Also enhance file log with workorder number
            $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "approveWorkOrder", "actiontime": "'.$logEntryDate.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'", "workorder_no": "'.$workorder_no.'"}';
            $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            echo json_encode(['status' => 'success', 'message' => 'Line approved. Next step: Production → Batch Planning.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to approve work order: ' . $conn->error]);
        }
    }
    
    // ============================================
    // REJECT WORK ORDER (Remove parked entries)
    // ============================================
    else if ($_GET["type"] == "rejectWorkOrder") {
        if (!is_array($input)) {
            $input = array();
        }
        $workorder_id = $input["workorder_id"] ?? '';
        $workorder_no = $input["workorder_no"] ?? '';
        
        if (empty($workorder_no)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
            exit;
        }
        
        // Delete parked entries for this work order
        $sql = "DELETE FROM line_booking 
                WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'
                AND status = 'Parked'";
        
        if ($conn->query($sql)) {
            // Also clear selectedLines from Work_order_materials
            $sql2 = "UPDATE Work_order_materials 
                     SET selectedLines = NULL,
                         stp_line_approval_status = 'Rejected',
                         stp_planned_flag = 'No'
                     WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'";
            $conn->query($sql2);
            
            // Log the action to database log table
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'rejectWorkOrder: ' . mysqli_real_escape_string($conn, $workorder_no);
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            // Also enhance file log with workorder number
            $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "rejectWorkOrder", "actiontime": "'.$logEntryDate.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'", "workorder_no": "'.$workorder_no.'"}';
            $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            echo json_encode(['status' => 'success', 'message' => 'Work order rejected successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reject work order: ' . $conn->error]);
        }
    }
    
    // ============================================
    // GET APPROVED WORK ORDERS LOG (Work orders with Booked status)
    // ============================================
    else if ($_GET["type"] == "getApprovedWorkOrdersLog") {
        $output = Array();
        
        // Get work orders that have booked lines (approved)
        $sql = "SELECT DISTINCT 
                    wom.id,
                    wom.workorder_no,
                    wom.order_no,
                    wom.selectedLines,
                    (SELECT lb.product_code 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Booked' 
                     LIMIT 1) as product_code,
                    (SELECT lb.product_name 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Booked' 
                     LIMIT 1) as product_name,
                    (SELECT lb.capacity_required 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Booked' 
                     LIMIT 1) as plan_qty,
                    (SELECT lb.responsible_person 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Booked' 
                     LIMIT 1) as responsible_person,
                   (SELECT om.plant_id 
                     FROM order_materials om left join po_entry po on om.order_no=po.order_no 
                     WHERE om.order_no = wom.order_no 
                     LIMIT 1) as planUnit,
                    (SELECT po.client_code 
                     FROM order_materials om left join po_entry po on om.order_no=po.order_no 
                     WHERE om.order_no = wom.order_no 
                     LIMIT 1) as client_code,
                    (SELECT c.LglNm 
                     FROM order_materials om left join po_entry po on om.order_no=po.order_no  left join client c on po.client_code=c.client_code
                     WHERE om.order_no = wom.order_no 
                     LIMIT 1) as client_name
                FROM Work_order_materials wom
                WHERE EXISTS (
                    SELECT 1 FROM line_booking lb2 
                    WHERE lb2.workorder_no = wom.workorder_no 
                    AND lb2.status = 'Booked'
                )
                ORDER BY wom.workorder_no DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Parse selectedLines JSON if exists
                $selectedLines = json_decode($row["selectedLines"] ?? '[]', true);
                $row["selectedLines"] = $selectedLines;
                
                // Get booked lines from line_booking table
                $linesSql = "SELECT lb.*, lm.line_name 
                            FROM line_booking lb 
                            LEFT JOIN linemaster lm ON lb.linemaster_id = lm.id 
                            WHERE lb.workorder_no = '".mysqli_real_escape_string($conn, $row['workorder_no'])."' 
                            AND lb.status = 'Booked'";
                $linesResult = $conn->query($linesSql);
                $bookedLines = [];
                
                if ($linesResult && $linesResult->num_rows > 0) {
                    while ($lineRow = $linesResult->fetch_assoc()) {
                        $equipmentList = json_decode($lineRow['selected_equipments'] ?? '[]', true);
                        $bookedLines[] = [
                            'id' => $lineRow['linemaster_id'],
                            'line_no' => $lineRow['line_no'],
                            'line_name' => $lineRow['line_name'] ?? '',
                            'equipmentList' => $equipmentList,
                            'booking_start_date' => $lineRow['booking_start_date'],
                            'booking_start_time' => $lineRow['booking_start_time'],
                            'booking_end_date' => $lineRow['booking_end_date'],
                            'booking_end_time' => $lineRow['booking_end_time'],
                            'responsible_person' => $lineRow['responsible_person']
                        ];
                    }
                }
                
                // Get deductions/materials for stock verification
                $deductions = [];
                $dedSql = "SELECT d.*, 
                          (SELECT material_name FROM my_view m WHERE m.material_code = d.material_code LIMIT 1) as material_name
                          FROM WO_deductions d 
                          WHERE d.workorder_no = '".mysqli_real_escape_string($conn, $row['workorder_no'])."'";
                $dedResult = $conn->query($dedSql);
                
                // Get plant_id for stock checking
                $plant_id = $_GET["plant_id"] ?? '';
                if (empty($plant_id)) {
                    // Try to get plant_id from order_materials
                    $plantSql = "SELECT om.plant_id 
                                FROM order_materials om 
                                LEFT JOIN po_entry po ON om.order_no = po.order_no 
                                WHERE om.order_no = '".mysqli_real_escape_string($conn, $row['order_no'] ?? '')."' 
                                LIMIT 1";
                    $plantResult = $conn->query($plantSql);
                    if ($plantResult && $plantResult->num_rows > 0) {
                        $plantRow = $plantResult->fetch_assoc();
                        $plant_id = $plantRow['plant_id'] ?? '';
                    }
                }
                
                if ($dedResult && $dedResult->num_rows > 0) {
                    while ($dedRow = $dedResult->fetch_assoc()) {
                        // Calculate required qty
                        $requiredQty = (floatval($dedRow['deducted_from_MC'] ?? 0) + floatval($dedRow['deducted_from_RM'] ?? 0) + floatval($dedRow['shortage'] ?? 0));
                        $dedRow['requiredQty'] = $requiredQty;
                        
                        // RE-CHECK CURRENT STOCK AVAILABILITY FROM stock_book/vw_total_available_stock
                        $material_code = mysqli_real_escape_string($conn, $dedRow['material_code'] ?? '');
                        $currentAvailableRM = 0;
                        $currentAvailableMC = 0;
                        $currentTotalAvailable = 0;
                        $currentShortage = 0;
                        
                        if (!empty($material_code) && !empty($plant_id)) {
                            // Check current RM stock from vw_total_available_stock
                            $currentStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                              WHERE material_code = '".$material_code."' 
                                              AND plant_id = '".mysqli_real_escape_string($conn, $plant_id)."' 
                                              LIMIT 1";
                            $currentStockResult = $conn->query($currentStockSql);
                            if ($currentStockResult && $currentStockResult->num_rows > 0) {
                                $currentStockRow = $currentStockResult->fetch_assoc();
                                $currentAvailableRM = floatval($currentStockRow['available_qty'] ?? 0);
                            }
                            
                            // Check for Mother Code stock
                            $motherCodeSql = "SELECT mother_material_code FROM material 
                                             WHERE material_code = '".$material_code."' 
                                             LIMIT 1";
                            $motherCodeResult = $conn->query($motherCodeSql);
                            if ($motherCodeResult && $motherCodeResult->num_rows > 0) {
                                $motherCodeRow = $motherCodeResult->fetch_assoc();
                                $mother_code = $motherCodeRow['mother_material_code'] ?? null;
                                
                                if ($mother_code) {
                                    $mcStockSql = "SELECT available_qty FROM vw_total_available_stock 
                                                 WHERE material_code = '".mysqli_real_escape_string($conn, $mother_code)."' 
                                                 AND plant_id = '".mysqli_real_escape_string($conn, $plant_id)."' 
                                                 LIMIT 1";
                                    $mcStockResult = $conn->query($mcStockSql);
                                    if ($mcStockResult && $mcStockResult->num_rows > 0) {
                                        $mcStockRow = $mcStockResult->fetch_assoc();
                                        $currentAvailableMC = floatval($mcStockRow['available_qty'] ?? 0);
                                    }
                                }
                            }
                            
                            // Calculate current total available and shortage
                            $currentTotalAvailable = $currentAvailableRM + $currentAvailableMC;
                            if ($currentTotalAvailable < $requiredQty) {
                                $currentShortage = $requiredQty - $currentTotalAvailable;
                            } else {
                                $currentShortage = 0; // Stock is available
                            }
                            
                            // Update shortage with current stock check result
                            $dedRow['shortage'] = $currentShortage;
                            $dedRow['current_available_RM'] = $currentAvailableRM;
                            $dedRow['current_available_MC'] = $currentAvailableMC;
                            $dedRow['current_total_available'] = $currentTotalAvailable;
                        }
                        
                        // Check if indent exists in indend_raw table for this material and work order
                        $workorder_no = mysqli_real_escape_string($conn, $row['workorder_no']);
                        
                        $indentExists = false;
                        $indentNo = '';
                        $indentId = '';
                        
                        if (!empty($material_code)) {
                            // Check indend_raw table - required_for contains JSON with work_order_no
                            $indentCheckSql = "SELECT id, no, request_no, status 
                                              FROM indend_raw 
                                              WHERE material_code = '".$material_code."'
                                              AND required_for LIKE '%\"work_order_no\":\"".$workorder_no."\"%'
                                              AND status != 'Rejected'
                                              ORDER BY id DESC LIMIT 1";
                            $indentCheckResult = $conn->query($indentCheckSql);
                            
                            if ($indentCheckResult && $indentCheckResult->num_rows > 0) {
                                $indentRow = $indentCheckResult->fetch_assoc();
                                $indentExists = true;
                                $indentNo = $indentRow['no'] ?? '';
                                $indentId = $indentRow['id'] ?? '';
                                
                                // Also update WO_deductions if indent_status is not set
                                if (empty($dedRow['indent_status']) || $dedRow['indent_status'] == 'Not Raised') {
                                    $dedRow['indent_status'] = 'Raised';
                                    $dedRow['indent_no'] = $indentNo;
                                    $dedRow['indent_id'] = $indentId;
                                }
                            }
                        }
                        
                        // If WO_deductions has indent_status, use that (it's more reliable)
                        if (!empty($dedRow['indent_status']) && ($dedRow['indent_status'] == 'Raised' || $dedRow['indent_status'] == 'Indent Sent')) {
                            $indentExists = true;
                            if (empty($indentNo) && !empty($dedRow['indent_no'])) {
                                $indentNo = $dedRow['indent_no'];
                            }
                            if (empty($indentId) && !empty($dedRow['indent_id'])) {
                                $indentId = $dedRow['indent_id'];
                            }
                        }
                        
                        $dedRow['indent_exists_in_indend_raw'] = $indentExists;
                        if ($indentExists) {
                            $dedRow['indent_no_from_indend_raw'] = $indentNo;
                            $dedRow['indent_id_from_indend_raw'] = $indentId;
                        }
                        
                        $deductions[] = $dedRow;
                    }
                }
                
                $row["Deductions"] = $deductions;
                $row["selectedLines"] = $bookedLines; // Use booked lines instead of selectedLines
                
                // Check if stock is complete and indent status
                $hasShortage = false;
                $indentRaised = false;
                foreach ($deductions as $ded) {
                    if (floatval($ded['shortage'] ?? 0) > 0) {
                        $hasShortage = true;
                        // Check if indent is raised for this material
                        $indentStatus = $ded['indent_status'] ?? 'Not Raised';
                        $indentExistsInIndendRaw = $ded['indent_exists_in_indend_raw'] || false;
                        if ($indentStatus === 'Raised' || $indentStatus === 'Indent Sent' || $indentExistsInIndendRaw) {
                            $indentRaised = true;
                        }
                    }
                }
                $row["stockComplete"] = !$hasShortage;
                $row["hasShortage"] = $hasShortage;
                $row["stockVerified"] = count($deductions) > 0;
                $row["indentRaised"] = $indentRaised;
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // GET LINE STAGES (Get stages for a specific line)
    // ============================================
    else if ($_GET["type"] == "getLineStages") {
        $linemaster_id = $_GET["linemaster_id"] ?? '';
        
        if (empty($linemaster_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Line master ID is required']);
            exit;
        }
        
        $output = Array();
        $sql = "SELECT DISTINCT dosage_form, stage 
                FROM linemaster_groups_stages 
                WHERE linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."' 
                ORDER BY dosage_form, stage";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // GET STAGE DATES (Get tentative and actual completion dates for stages)
    // ============================================
    else if ($_GET["type"] == "getStageDates") {
        $workorder_nos = $_GET["workorder_nos"] ?? '';
        
        if (empty($workorder_nos)) {
            echo json_encode([]);
            exit;
        }
        
        // Create table if it doesn't exist
        $createTableSql = "CREATE TABLE IF NOT EXISTS `workorder_stage_dates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `workorder_no` varchar(100) NOT NULL,
            `dosage_form` varchar(100) NOT NULL,
            `stage` varchar(100) NOT NULL,
            `tentative_completion_date` date DEFAULT NULL,
            `actual_completion_date` date DEFAULT NULL,
            `entry_by` varchar(100) DEFAULT NULL,
            `entry_date` datetime DEFAULT NULL,
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_wo_stage` (`workorder_no`, `dosage_form`, `stage`),
            KEY `idx_workorder_no` (`workorder_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $conn->query($createTableSql);
        
        $workorderArray = explode(',', $workorder_nos);
        $workorderList = [];
        foreach ($workorderArray as $wo) {
            $wo = trim($wo);
            if (!empty($wo)) {
                $workorderList[] = "'".mysqli_real_escape_string($conn, $wo)."'";
            }
        }
        
        if (empty($workorderList)) {
            echo json_encode([]);
            exit;
        }
        
        $output = Array();
        $sql = "SELECT * FROM workorder_stage_dates 
                WHERE workorder_no IN (".implode(',', $workorderList).")";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // SAVE STAGE DATE (Save or update tentative/actual completion date)
    // ============================================
    else if ($_GET["type"] == "saveStageDate") {
        if (!is_array($input)) {
            $input = array();
        }
        $workorder_no = $input['workorder_no'] ?? '';
        $dosage_form = $input['dosage_form'] ?? '';
        $stage = $input['stage'] ?? '';
        $date_type = $input['date_type'] ?? ''; // 'tentative' or 'actual'
        $date_value = $input['date_value'] ?? '';
        
        if (empty($workorder_no) || empty($dosage_form) || empty($stage) || empty($date_type)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        // Create table if it doesn't exist
        $createTableSql = "CREATE TABLE IF NOT EXISTS `workorder_stage_dates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `workorder_no` varchar(100) NOT NULL,
            `dosage_form` varchar(100) NOT NULL,
            `stage` varchar(100) NOT NULL,
            `tentative_completion_date` date DEFAULT NULL,
            `actual_completion_date` date DEFAULT NULL,
            `entry_by` varchar(100) DEFAULT NULL,
            `entry_date` datetime DEFAULT NULL,
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_wo_stage` (`workorder_no`, `dosage_form`, `stage`),
            KEY `idx_workorder_no` (`workorder_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $conn->query($createTableSql);
        
        $entry_date = date('Y-m-d H:i:s');
        $workorder_no_escaped = mysqli_real_escape_string($conn, $workorder_no);
        $dosage_form_escaped = mysqli_real_escape_string($conn, $dosage_form);
        $stage_escaped = mysqli_real_escape_string($conn, $stage);
        $date_value_escaped = !empty($date_value) ? "'".mysqli_real_escape_string($conn, $date_value)."'" : 'NULL';
        
        // Check if record exists
        $checkSql = "SELECT id FROM workorder_stage_dates 
                     WHERE workorder_no = '".$workorder_no_escaped."' 
                     AND dosage_form = '".$dosage_form_escaped."' 
                     AND stage = '".$stage_escaped."'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Update existing record
            $dateField = $date_type === 'tentative' ? 'tentative_completion_date' : 'actual_completion_date';
            $updateSql = "UPDATE workorder_stage_dates 
                         SET ".$dateField." = ".$date_value_escaped.",
                             updated_by = '".mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '')."',
                             updated_date = '".$entry_date."'
                         WHERE workorder_no = '".$workorder_no_escaped."' 
                         AND dosage_form = '".$dosage_form_escaped."' 
                         AND stage = '".$stage_escaped."'";
            
            if ($conn->query($updateSql)) {
                echo json_encode(['status' => 'success', 'message' => 'Stage date updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update stage date: ' . $conn->error]);
            }
        } else {
            // Insert new record
            $tentativeDate = $date_type === 'tentative' ? $date_value_escaped : 'NULL';
            $actualDate = $date_type === 'actual' ? $date_value_escaped : 'NULL';
            
            $insertSql = "INSERT INTO workorder_stage_dates 
                         (workorder_no, dosage_form, stage, tentative_completion_date, actual_completion_date, entry_by, entry_date) 
                         VALUES 
                         ('".$workorder_no_escaped."', 
                          '".$dosage_form_escaped."', 
                          '".$stage_escaped."', 
                          ".$tentativeDate.", 
                          ".$actualDate.", 
                          '".mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '')."', 
                          '".$entry_date."')";
            
            if ($conn->query($insertSql)) {
                echo json_encode(['status' => 'success', 'message' => 'Stage date saved successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save stage date: ' . $conn->error]);
            }
        }
    }
    
    // ============================================
    // RAISE INDENT FOR SHORTAGE MATERIALS (Legacy - Now handled by savePlanningIndentStore)
    // This endpoint is kept for backward compatibility
    // Actual indent creation happens in purchase/indent.php?type=savePlanningIndentStore
    // ============================================
    else if ($_GET["type"] == "raiseIndentForShortage") {
        if (!is_array($input)) {
            $input = array();
        }
        $workorder_no = $input["workorder_no"] ?? '';
        $materials = $input["materials"] ?? [];
        
        if (empty($workorder_no) || empty($materials)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number and materials are required']);
            exit;
        }
        
        $entry_date = date('Y-m-d H:i:s');
        $allSuccess = true;
        $errors = [];
        
        // Log indent raising action
        $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                   VALUES ('FRONTEND', '".$token."', 'raiseIndentForShortage', '".$entry_date."', '".$_GET["department"]."', '".$_GET["emp_id"]."', 'POST', '".$_SERVER['REMOTE_ADDR']."', '".$currentUrl."')";
        $conn->query($logSql);
        
        foreach ($materials as $mat) {
            $material_code = mysqli_real_escape_string($conn, $mat['material_code'] ?? '');
            $shortage_qty = floatval($mat['shortage_qty'] ?? 0);
            
            if ($shortage_qty <= 0) continue;
            
            // Update WO_deductions to mark indent as raised
            $sql = "UPDATE WO_deductions 
                    SET indent_status = 'Raised',
                        indent_raised_by = '".$_GET['emp_id']."',
                        indent_raised_on = '".$entry_date."'
                    WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'
                    AND material_code = '".$material_code."'
                    AND shortage > 0";
            
            if (!$conn->query($sql)) {
                $allSuccess = false;
                $errors[] = "Failed to update indent for material: " . $material_code;
            }
        }
        
        if ($allSuccess) {
            echo json_encode(['status' => 'success', 'message' => 'Indent raised successfully for ' . count($materials) . ' material(s)']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Some errors occurred: ' . implode(', ', $errors)]);
        }
    }
    
    // ============================================
    // UPDATE INDENT STATUS
    // ============================================
    else if ($_GET["type"] == "updateIndentStatus") {
        if (!is_array($input)) {
            $input = array();
        }
        $workorder_no = $input["workorder_no"] ?? '';
        $indent_status = $input["indent_status"] ?? 'Raised';
        
        if (empty($workorder_no)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
            exit;
        }
        
        $entry_date = date('Y-m-d H:i:s');
        
        $sql = "UPDATE WO_deductions 
                SET indent_status = '".mysqli_real_escape_string($conn, $indent_status)."',
                    indent_raised_by = '".$_GET['emp_id']."',
                    indent_raised_on = '".$entry_date."'
                WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'
                AND shortage > 0";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Indent status updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update indent status: ' . $conn->error]);
        }
    }
    
    // ============================================
    // GET LINE STAGES (Get stages for a specific line)
    // ============================================
    else if ($_GET["type"] == "getLineStages") {
        $linemaster_id = $_GET["linemaster_id"] ?? '';
        
        if (empty($linemaster_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Line master ID is required']);
            exit;
        }
        
        $output = Array();
        $sql = "SELECT DISTINCT dosage_form, stage 
                FROM linemaster_groups_stages 
                WHERE linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."' 
                ORDER BY dosage_form, stage";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // GET STAGE DATES (Get tentative and actual completion dates for stages)
    // ============================================
    else if ($_GET["type"] == "getStageDates") {
        $workorder_nos = $_GET["workorder_nos"] ?? '';
        
        if (empty($workorder_nos)) {
            echo json_encode([]);
            exit;
        }
        
        // Create table if it doesn't exist
        $createTableSql = "CREATE TABLE IF NOT EXISTS `workorder_stage_dates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `workorder_no` varchar(100) NOT NULL,
            `dosage_form` varchar(100) NOT NULL,
            `stage` varchar(100) NOT NULL,
            `tentative_completion_date` date DEFAULT NULL,
            `actual_completion_date` date DEFAULT NULL,
            `entry_by` varchar(100) DEFAULT NULL,
            `entry_date` datetime DEFAULT NULL,
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_wo_stage` (`workorder_no`, `dosage_form`, `stage`),
            KEY `idx_workorder_no` (`workorder_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $conn->query($createTableSql);
        
        $workorderArray = explode(',', $workorder_nos);
        $workorderList = [];
        foreach ($workorderArray as $wo) {
            $wo = trim($wo);
            if (!empty($wo)) {
                $workorderList[] = "'".mysqli_real_escape_string($conn, $wo)."'";
            }
        }
        
        if (empty($workorderList)) {
            echo json_encode([]);
            exit;
        }
        
        $output = Array();
        $sql = "SELECT * FROM workorder_stage_dates 
                WHERE workorder_no IN (".implode(',', $workorderList).")";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // SAVE STAGE DATE (Save or update tentative/actual completion date)
    // ============================================
    else if ($_GET["type"] == "saveStageDate") {
        if (!is_array($input)) {
            $input = array();
        }
        $workorder_no = $input['workorder_no'] ?? '';
        $dosage_form = $input['dosage_form'] ?? '';
        $stage = $input['stage'] ?? '';
        $date_type = $input['date_type'] ?? ''; // 'tentative' or 'actual'
        $date_value = $input['date_value'] ?? '';
        
        if (empty($workorder_no) || empty($dosage_form) || empty($stage) || empty($date_type)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        // Create table if it doesn't exist
        $createTableSql = "CREATE TABLE IF NOT EXISTS `workorder_stage_dates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `workorder_no` varchar(100) NOT NULL,
            `dosage_form` varchar(100) NOT NULL,
            `stage` varchar(100) NOT NULL,
            `tentative_completion_date` date DEFAULT NULL,
            `actual_completion_date` date DEFAULT NULL,
            `entry_by` varchar(100) DEFAULT NULL,
            `entry_date` datetime DEFAULT NULL,
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_wo_stage` (`workorder_no`, `dosage_form`, `stage`),
            KEY `idx_workorder_no` (`workorder_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $conn->query($createTableSql);
        
        $entry_date = date('Y-m-d H:i:s');
        $workorder_no_escaped = mysqli_real_escape_string($conn, $workorder_no);
        $dosage_form_escaped = mysqli_real_escape_string($conn, $dosage_form);
        $stage_escaped = mysqli_real_escape_string($conn, $stage);
        $date_value_escaped = !empty($date_value) ? "'".mysqli_real_escape_string($conn, $date_value)."'" : 'NULL';
        
        // Check if record exists
        $checkSql = "SELECT id FROM workorder_stage_dates 
                     WHERE workorder_no = '".$workorder_no_escaped."' 
                     AND dosage_form = '".$dosage_form_escaped."' 
                     AND stage = '".$stage_escaped."'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Update existing record
            $dateField = $date_type === 'tentative' ? 'tentative_completion_date' : 'actual_completion_date';
            $updateSql = "UPDATE workorder_stage_dates 
                         SET ".$dateField." = ".$date_value_escaped.",
                             updated_by = '".mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '')."',
                             updated_date = '".$entry_date."'
                         WHERE workorder_no = '".$workorder_no_escaped."' 
                         AND dosage_form = '".$dosage_form_escaped."' 
                         AND stage = '".$stage_escaped."'";
            
            if ($conn->query($updateSql)) {
                echo json_encode(['status' => 'success', 'message' => 'Stage date updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update stage date: ' . $conn->error]);
            }
        } else {
            // Insert new record
            $tentativeDate = $date_type === 'tentative' ? $date_value_escaped : 'NULL';
            $actualDate = $date_type === 'actual' ? $date_value_escaped : 'NULL';
            
            $insertSql = "INSERT INTO workorder_stage_dates 
                         (workorder_no, dosage_form, stage, tentative_completion_date, actual_completion_date, entry_by, entry_date) 
                         VALUES 
                         ('".$workorder_no_escaped."', 
                          '".$dosage_form_escaped."', 
                          '".$stage_escaped."', 
                          ".$tentativeDate.", 
                          ".$actualDate.", 
                          '".mysqli_real_escape_string($conn, $_GET["emp_id"] ?? '')."', 
                          '".$entry_date."')";
            
            if ($conn->query($insertSql)) {
                echo json_encode(['status' => 'success', 'message' => 'Stage date saved successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save stage date: ' . $conn->error]);
            }
        }
    }

    else if ($_GET["type"] == "saveStpPlan") {
        header('Content-Type: application/json');
        $workOrders = is_array($input['work_orders'] ?? null) ? $input['work_orders'] : [];
        if (!is_array($workOrders) || count($workOrders) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'No work orders selected']);
            exit;
        }

        $plannedCount = 0;
        $failed = [];
        foreach ($workOrders as $wo) {
            $workorderNo = trim($wo['workorder_no'] ?? '');
            $workorderId = trim((string)($wo['workorder_id'] ?? ''));
            $selectedLines = lbSlimSelectedLines(lbEnrichSelectedLinesFromMaster($conn, $wo['selectedLines'] ?? []));
            $startDate = trim($wo['expected_production_start_date'] ?? '');
            $startTime = trim($wo['expected_production_start_time'] ?? '');
            $endDate = trim($wo['expected_production_end_date'] ?? '');
            $endTime = trim($wo['expected_production_end_time'] ?? '');
            $responsiblePerson = trim($wo['responsible_person'] ?? '');
            $hoursRequired = trim((string)($wo['no_of_hours_required'] ?? ''));

            if ($workorderNo === '' || !is_array($selectedLines) || count($selectedLines) === 0) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Missing WO or selected lines'];
                continue;
            }
            if ($startDate === '' || $endDate === '' || $responsiblePerson === '') {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Start/end date and responsible person are required'];
                continue;
            }

            $validation = lbValidateBookingPlan($conn, $_GET['plant_id'] ?? '', $wo, $selectedLines, $startDate, $startTime, $endDate, $endTime);
            if (!$validation['valid']) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => implode('; ', $validation['errors'])];
                continue;
            }

            $lineListJson = mysqli_real_escape_string($conn, json_encode($selectedLines));
            $updateSql = "UPDATE Work_order_materials SET
                            selectedLines = '".$lineListJson."',
                            expected_production_start_date = '".mysqli_real_escape_string($conn, $startDate)."',
                            expected_production_start_time = '".mysqli_real_escape_string($conn, $startTime)."',
                            expected_production_end_date = '".mysqli_real_escape_string($conn, $endDate)."',
                            expected_production_end_time = '".mysqli_real_escape_string($conn, $endTime)."',
                            no_of_hours_required = ".($hoursRequired === '' ? "NULL" : "'".mysqli_real_escape_string($conn, $hoursRequired)."'").",
                            responsible_person = '".mysqli_real_escape_string($conn, $responsiblePerson)."',
                            stp_planned_flag = 'Yes',
                            stp_planned_by = '".mysqli_real_escape_string($conn, $_GET["emp_id"])."',
                            stp_planned_on = '".$entry_date."'
                          WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorderNo)."'";
            if ($workorderId !== '') {
                $updateSql .= " AND id = '".mysqli_real_escape_string($conn, $workorderId)."'";
            }
            $updateSql .= " LIMIT 1";

            if ($conn->query($updateSql)) {
                $parkMeta = array(
                    'product_code' => $wo['product_code'] ?? '',
                    'product_name' => $wo['product_name'] ?? '',
                    'booking_start_date' => $startDate,
                    'booking_start_time' => $startTime !== '' ? $startTime : '00:00:00',
                    'booking_end_date' => $endDate,
                    'booking_end_time' => $endTime !== '' ? $endTime : '23:59:59',
                    'responsible_person' => $responsiblePerson,
                    'batch_size' => $wo['plan_qty'] ?? $wo['batch_size'] ?? '',
                    'plan_qty' => $wo['plan_qty'] ?? '',
                );
                $parkResult = stpUpsertParkedLineBookings(
                    $conn,
                    $workorderNo,
                    $selectedLines,
                    $parkMeta,
                    $_GET['emp_id'] ?? '',
                    $entry_date,
                    $_GET['plant_id'] ?? ''
                );
                if ($parkResult['count'] > 0) {
                    $plannedCount++;
                    $empEsc = mysqli_real_escape_string($conn, (string)($_GET['emp_id'] ?? ''));
                    $woEscPending = mysqli_real_escape_string($conn, $workorderNo);
                    $pendingSql = "UPDATE Work_order_materials SET
                            stp_line_approval_status = 'Pending',
                            stp_line_approval_sent_by = '".$empEsc."',
                            stp_line_approval_sent_on = '".$entry_date."'
                          WHERE workorder_no = '".$woEscPending."'
                            AND LOWER(TRIM(COALESCE(stp_line_approval_status, ''))) NOT IN ('approved', 'rejected')";
                    if ($workorderId !== '') {
                        $pendingSql .= " AND id = '".mysqli_real_escape_string($conn, $workorderId)."'";
                    }
                    $pendingSql .= " LIMIT 1";
                    $conn->query($pendingSql);
                } else {
                    $failed[] = ['workorder_no' => $workorderNo, 'message' => implode('; ', $parkResult['errors']) ?: 'Failed to reserve line on live board'];
                }
            } else {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => $conn->error];
            }
        }

        echo json_encode([
            'status' => $plannedCount > 0 ? 'success' : 'error',
            'message' => $plannedCount > 0 ? 'Line booking saved. Work order(s) are now on Line Approval.' : 'Failed to save STP planning',
            'planned_count' => $plannedCount,
            'failed_count' => count($failed),
            'failed' => $failed
        ]);
    }

    else if ($_GET["type"] == "sendForLineApproval") {
        $workOrders = $input['work_orders'] ?? [];
        if (!is_array($workOrders) || count($workOrders) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'No work orders selected']);
            exit;
        }

        $sentCount = 0;
        $failed = [];
        $plantId = $_GET['plant_id'] ?? '';

        foreach ($workOrders as $wo) {
            $workorderNo = trim($wo['workorder_no'] ?? '');
            $workorderId = trim((string) ($wo['workorder_id'] ?? ''));
            $selectedLines = lbDecodeSelectedLines($wo['selectedLines'] ?? []);
            $startDate = trim($wo['expected_production_start_date'] ?? '');
            $startTime = trim($wo['expected_production_start_time'] ?? '');
            $endDate = trim($wo['expected_production_end_date'] ?? '');
            $endTime = trim($wo['expected_production_end_time'] ?? '');
            $responsiblePerson = trim($wo['responsible_person'] ?? '');
            $hoursRequired = trim((string) ($wo['no_of_hours_required'] ?? ''));

            if ($workorderNo === '') {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Missing work order number'];
                continue;
            }

            $woEscSend = mysqli_real_escape_string($conn, $workorderNo);
            $idSql = $workorderId !== '' ? " AND id = '".mysqli_real_escape_string($conn, $workorderId)."'" : '';
            $dbWoSql = "SELECT * FROM Work_order_materials WHERE workorder_no = '".$woEscSend."'".$idSql." LIMIT 1";
            $dbWoRes = $conn->query($dbWoSql);
            $dbWo = ($dbWoRes && $dbWoRes->num_rows > 0) ? $dbWoRes->fetch_assoc() : null;
            if (!$dbWo) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Work order not found'];
                continue;
            }

            if (count($selectedLines) === 0) {
                $selectedLines = lbDecodeSelectedLines($dbWo['selectedLines'] ?? []);
            }
            if ($startDate === '') {
                $startDate = trim((string)($dbWo['expected_production_start_date'] ?? ''));
            }
            if ($startTime === '') {
                $startTime = trim((string)($dbWo['expected_production_start_time'] ?? ''));
            }
            if ($endDate === '') {
                $endDate = trim((string)($dbWo['expected_production_end_date'] ?? ''));
            }
            if ($endTime === '') {
                $endTime = trim((string)($dbWo['expected_production_end_time'] ?? ''));
            }
            if ($responsiblePerson === '') {
                $responsiblePerson = trim((string)($dbWo['responsible_person'] ?? ''));
            }
            if ($hoursRequired === '') {
                $hoursRequired = trim((string)($dbWo['no_of_hours_required'] ?? ''));
            }

            if (!is_array($selectedLines) || count($selectedLines) === 0) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Missing WO or selected lines'];
                continue;
            }
            if ($startDate === '' || $endDate === '' || $responsiblePerson === '') {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Start/end date and responsible person are required'];
                continue;
            }

            $chkSql = "SELECT stp_line_approval_status, advance_planning_flag FROM Work_order_materials
                WHERE workorder_no = '".$woEscSend."'".$idSql." LIMIT 1";
            $chkRes = $conn->query($chkSql);
            if ($chkRes && $chkRes->num_rows > 0) {
                $chkRow = $chkRes->fetch_assoc();
                if (strtolower(trim((string) ($chkRow['stp_line_approval_status'] ?? ''))) === 'pending') {
                    $sentCount++;
                    continue;
                }
                if (strtolower(trim((string) ($chkRow['advance_planning_flag'] ?? ''))) === 'yes') {
                    $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Already released to production'];
                    continue;
                }
            }

            $lineListJson = mysqli_real_escape_string($conn, json_encode($selectedLines));
            $updateSql = "UPDATE Work_order_materials SET
                            selectedLines = '".$lineListJson."',
                            expected_production_start_date = '".mysqli_real_escape_string($conn, $startDate)."',
                            expected_production_start_time = '".mysqli_real_escape_string($conn, $startTime)."',
                            expected_production_end_date = '".mysqli_real_escape_string($conn, $endDate)."',
                            expected_production_end_time = '".mysqli_real_escape_string($conn, $endTime)."',
                            no_of_hours_required = ".($hoursRequired === '' ? 'NULL' : "'".mysqli_real_escape_string($conn, $hoursRequired)."'").",
                            responsible_person = '".mysqli_real_escape_string($conn, $responsiblePerson)."',
                            stp_planned_flag = 'Yes',
                            stp_line_approval_status = 'Pending',
                            stp_line_approval_sent_by = '".mysqli_real_escape_string($conn, $_GET['emp_id'])."',
                            stp_line_approval_sent_on = '".$entry_date."',
                            advance_planning_flag = 'No'
                          WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorderNo)."'";
            if ($workorderId !== '') {
                $updateSql .= " AND id = '".mysqli_real_escape_string($conn, $workorderId)."'";
            }
            $updateSql .= ' LIMIT 1';

            if (!$conn->query($updateSql)) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => $conn->error];
                continue;
            }

            $parkMeta = [
                'product_code' => $wo['product_code'] ?? '',
                'product_name' => $wo['product_name'] ?? '',
                'booking_start_date' => $startDate,
                'booking_start_time' => $startTime ?: '00:00:00',
                'booking_end_date' => $endDate,
                'booking_end_time' => $endTime ?: '23:59:59',
                'responsible_person' => $responsiblePerson,
                'batch_size' => $wo['plan_qty'] ?? $wo['batch_size'] ?? '',
                'plan_qty' => $wo['plan_qty'] ?? '',
            ];
            $parkResult = stpUpsertParkedLineBookings(
                $conn,
                $workorderNo,
                $selectedLines,
                $parkMeta,
                $_GET['emp_id'] ?? '',
                $entry_date,
                $plantId
            );
            if ($parkResult['count'] <= 0) {
                $alreadyParked = 0;
                $existParkRes = $conn->query("SELECT COUNT(*) AS cnt FROM line_booking
                    WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorderNo)."'
                    AND status = 'Parked'");
                if ($existParkRes && $existParkRes->num_rows > 0) {
                    $alreadyParked = intval($existParkRes->fetch_assoc()['cnt'] ?? 0);
                }
                if ($alreadyParked <= 0) {
                    $failed[] = [
                        'workorder_no' => $workorderNo,
                        'message' => 'WO updated but line_booking not created: ' . implode('; ', $parkResult['errors']),
                    ];
                    $conn->query("UPDATE Work_order_materials SET stp_line_approval_status = NULL,
                        stp_line_approval_sent_by = NULL, stp_line_approval_sent_on = NULL
                        WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorderNo)."' LIMIT 1");
                    continue;
                }
            }

            $payload = mysqli_real_escape_string($conn, json_encode($wo));
            $histSql = "INSERT INTO stp_advance_planning_history
                (plant_id, workorder_id, workorder_no, action, action_by, action_on, payload)
                VALUES (
                    '".mysqli_real_escape_string($conn, $plantId)."',
                    '".mysqli_real_escape_string($conn, $workorderId)."',
                    '".mysqli_real_escape_string($conn, $workorderNo)."',
                    'SENT_FOR_APPROVAL',
                    '".mysqli_real_escape_string($conn, $_GET['emp_id'])."',
                    '".$entry_date."',
                    '".$payload."'
                )";
            $conn->query($histSql);
            $sentCount++;
        }

        echo json_encode([
            'status' => $sentCount > 0 ? 'success' : 'error',
            'message' => $sentCount > 0
                ? 'Sent to Line Approval. Approve on Line Approval screen to release to production.'
                : 'Failed to send for line approval',
            'sent_count' => $sentCount,
            'failed_count' => count($failed),
            'failed' => $failed,
        ]);
    }

    else if ($_GET["type"] == "autoAllocateLineBooking") {
        $wo = is_array($input) ? $input : array();
        $plantId = $_GET['plant_id'] ?? '';
        $lineType = trim($wo['line_type'] ?? $wo['line_category'] ?? 'Manufacturing');
        $_GET['product_code'] = $wo['product_code'] ?? '';
        $_GET['workorder_no'] = $wo['workorder_no'] ?? '';
        $_GET['start_date'] = $wo['expected_production_start_date'] ?? '';
        $_GET['start_time'] = $wo['expected_production_start_time'] ?? '';
        $_GET['end_date'] = $wo['expected_production_end_date'] ?? '';
        $_GET['end_time'] = $wo['expected_production_end_time'] ?? '';
        $_GET['line_type'] = $lineType;
        $_GET['plan_qty'] = $wo['plan_qty'] ?? '';
        $_GET['batch_size'] = $wo['batch_size'] ?? '';
        ob_start();
        $product_code = $_GET['product_code'];
        $workorder_no = $_GET['workorder_no'];
        $start_date = $_GET['start_date'];
        $start_time = $_GET['start_time'];
        $end_date = $_GET['end_date'];
        $end_time = $_GET['end_time'];
        $line_type = $_GET['line_type'];
        $plan_qty = $_GET['plan_qty'];
        $batch_size = $_GET['batch_size'];
        $candidates = array();
        $dosageForm = '';
        if ($product_code) {
            $prodSql = "SELECT dosage_form FROM product WHERE product_code = '".mysqli_real_escape_string($conn, $product_code)."' LIMIT 1";
            $prodResult = $conn->query($prodSql);
            if ($prodResult && $prodResult->num_rows > 0) {
                $dosageForm = $prodResult->fetch_assoc()['dosage_form'] ?? '';
            }
        }
        if ($dosageForm !== '') {
            $lineSql = "SELECT lm.* FROM linemaster lm
                        LEFT JOIN linemaster_groups_stages lgs ON lm.id = lgs.linemaster_id
                        WHERE lgs.dosage_form = '".mysqli_real_escape_string($conn, $dosageForm)."'
                        ".lbLinemasterPlantSql($conn, 'lm')."
                        GROUP BY lm.id ORDER BY lm.line_no";
            $lineResult = $conn->query($lineSql);
            $woContext = array_merge($wo, array(
                'product_code' => $product_code,
                'workorder_no' => $workorder_no,
                'expected_production_start_date' => $start_date,
                'expected_production_start_time' => $start_time,
                'expected_production_end_date' => $end_date,
                'expected_production_end_time' => $end_time,
                'plan_qty' => $plan_qty,
                'batch_size' => $batch_size
            ));
            if ($lineResult && $lineResult->num_rows > 0) {
                while ($lineRow = $lineResult->fetch_assoc()) {
                    $eqList = array();
                    $eqResult = $conn->query("SELECT * FROM linemaster_mapped_Equipment WHERE linemaster_id = '".intval($lineRow['id'])."'");
                    if ($eqResult && $eqResult->num_rows > 0) {
                        while ($eqRow = $eqResult->fetch_assoc()) {
                            $eqList[] = $eqRow;
                        }
                    }
                    $lineRow['equipmentList'] = $eqList;
                    $candidates[] = lbEnrichLineCandidate($conn, $plantId, $lineRow, $woContext, $lineType);
                }
            }
        }
        ob_end_clean();
        $best = lbAutoSelectBestLine($candidates);
        if (!$best) {
            echo json_encode(['status' => 'error', 'message' => 'No suitable line found for auto allocation', 'candidates' => $candidates]);
            exit;
        }
        echo json_encode([
            'status' => 'success',
            'allocated_line' => $best,
            'selectedLines' => array($best),
            'suggested_schedule' => $best['suggested_schedule'] ?? null,
            'validation' => $best['capacity_validation'] ?? null,
            'candidates_count' => count($candidates)
        ]);
    }

    else if ($_GET["type"] == "validateLineBookingPlan") {
        header('Content-Type: application/json');
        try {
            $wo = is_array($input) ? $input : array();
            $selectedLines = lbEnrichSelectedLinesFromMaster($conn, $wo['selectedLines'] ?? array());
            $validation = lbValidateBookingPlan(
                $conn,
                $_GET['plant_id'] ?? '',
                $wo,
                $selectedLines,
                trim($wo['expected_production_start_date'] ?? ''),
                trim($wo['expected_production_start_time'] ?? ''),
                trim($wo['expected_production_end_date'] ?? ''),
                trim($wo['expected_production_end_time'] ?? '')
            );
            echo json_encode(array_merge(['status' => $validation['valid'] ? 'success' : 'error'], $validation));
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'valid' => false,
                'errors' => ['Validation failed: '.$e->getMessage()],
                'warnings' => array(),
            ]);
        }
    }

    else if ($_GET["type"] == "getLineOccupancyCalendar") {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $lineType = $_GET['line_type'] ?? 'All';
        $plantId = $_GET['plant_id'] ?? '';
        $linesOut = array();
        $lineSql = "SELECT lm.* FROM linemaster lm WHERE 1=1 ".lbLinemasterPlantSql($conn, 'lm')." ORDER BY lm.line_no";
        $lineResult = $conn->query($lineSql);
        if ($lineResult && $lineResult->num_rows > 0) {
            while ($lineRow = $lineResult->fetch_assoc()) {
                $lineRow['line_type_category'] = resolveLineTypeCategory($lineRow);
                if ($lineType !== '' && $lineType !== 'All') {
                    $requested = strtolower(trim($lineType));
                    $actual = strtolower($lineRow['line_type_category']);
                    if ($requested === 'manufacturing' && $actual !== 'manufacturing') continue;
                    if ($requested === 'packing' && $actual !== 'packing') continue;
                }
                $bookings = array();
                $bookingSql = "SELECT lb.* FROM line_booking lb
                               WHERE lb.linemaster_id = '".intval($lineRow['id'])."'
                               AND lb.status NOT IN ('Cancelled','Completed')
                               AND lb.booking_start_date <= '".mysqli_real_escape_string($conn, $endDate)."'
                               AND lb.booking_end_date >= '".mysqli_real_escape_string($conn, $startDate)."'";
                $bookingResult = $conn->query($bookingSql);
                if ($bookingResult && $bookingResult->num_rows > 0) {
                    while ($b = $bookingResult->fetch_assoc()) {
                        $bookings[] = $b;
                    }
                }
                $lineRow['bookings'] = $bookings;
                $lineRow['occupied_days'] = count($bookings);
                $linesOut[] = $lineRow;
            }
        }
        echo json_encode([
            'status' => 'success',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'line_count' => count($linesOut),
            'lines' => $linesOut
        ]);
    }

    else if ($_GET["type"] == "updateStpLineBooking") {
        $workOrders = $input['work_orders'] ?? [];
        if (!is_array($workOrders) || count($workOrders) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'No work orders supplied']);
            exit;
        }

        $updated = 0;
        $failed = [];
        $plantId = $_GET['plant_id'] ?? '';

        foreach ($workOrders as $wo) {
            $workorderNo = trim($wo['workorder_no'] ?? '');
            if ($workorderNo === '') {
                $failed[] = ['workorder_no' => '', 'message' => 'Missing work order number'];
                continue;
            }
            $woEsc = mysqli_real_escape_string($conn, $workorderNo);
            $chkSql = "SELECT * FROM Work_order_materials WHERE workorder_no = '{$woEsc}' LIMIT 1";
            $chkRes = $conn->query($chkSql);
            if (!$chkRes || $chkRes->num_rows === 0) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Work order not found'];
                continue;
            }
            $chkRow = $chkRes->fetch_assoc();
            if (workOrderSentToProduction($chkRow)) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'Already sent to production and cannot be changed'];
                continue;
            }

            $existingLines = json_decode($chkRow['selectedLines'] ?? '[]', true);
            if (!is_array($existingLines)) {
                $existingLines = [];
            }
            $lineCategory = trim($wo['line_category'] ?? 'All');
            $incomingLines = $wo['selectedLines'] ?? [];
            if ($lineCategory !== '' && $lineCategory !== 'All') {
                $selectedLines = mergeSelectedLinesByCategory($existingLines, $incomingLines, $lineCategory);
            } else {
                $selectedLines = is_array($incomingLines) ? $incomingLines : $existingLines;
            }

            $startDate = trim($wo['expected_production_start_date'] ?? ($chkRow['expected_production_start_date'] ?? ''));
            $startTime = trim($wo['expected_production_start_time'] ?? ($chkRow['expected_production_start_time'] ?? ''));
            $endDate = trim($wo['expected_production_end_date'] ?? ($chkRow['expected_production_end_date'] ?? ''));
            $endTime = trim($wo['expected_production_end_time'] ?? ($chkRow['expected_production_end_time'] ?? ''));
            $responsiblePerson = trim($wo['responsible_person'] ?? ($chkRow['responsible_person'] ?? ''));
            $hoursRequired = trim((string) ($wo['no_of_hours_required'] ?? ($chkRow['no_of_hours_required'] ?? '')));

            if (!is_array($selectedLines) || count($selectedLines) === 0) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => 'At least one line is required'];
                continue;
            }

            $lineListJson = mysqli_real_escape_string($conn, json_encode($selectedLines));
            $updateSql = "UPDATE Work_order_materials SET
                            selectedLines = '{$lineListJson}',
                            expected_production_start_date = '".mysqli_real_escape_string($conn, $startDate)."',
                            expected_production_start_time = '".mysqli_real_escape_string($conn, $startTime)."',
                            expected_production_end_date = '".mysqli_real_escape_string($conn, $endDate)."',
                            expected_production_end_time = '".mysqli_real_escape_string($conn, $endTime)."',
                            no_of_hours_required = ".($hoursRequired === '' ? 'NULL' : "'".mysqli_real_escape_string($conn, $hoursRequired)."'").",
                            responsible_person = '".mysqli_real_escape_string($conn, $responsiblePerson)."'
                          WHERE workorder_no = '{$woEsc}' LIMIT 1";
            if (!$conn->query($updateSql)) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => $conn->error];
                continue;
            }

            $parkMeta = [
                'product_code' => $wo['product_code'] ?? ($chkRow['product_code'] ?? ''),
                'product_name' => $wo['product_name'] ?? '',
                'booking_start_date' => $startDate,
                'booking_start_time' => $startTime ?: '00:00:00',
                'booking_end_date' => $endDate,
                'booking_end_time' => $endTime ?: '23:59:59',
                'responsible_person' => $responsiblePerson,
                'batch_size' => $wo['plan_qty'] ?? $chkRow['plan_qty'] ?? $chkRow['batch_size'] ?? '',
                'plan_qty' => $wo['plan_qty'] ?? $chkRow['plan_qty'] ?? '',
            ];
            $parkResult = stpUpsertParkedLineBookings(
                $conn,
                $workorderNo,
                $selectedLines,
                $parkMeta,
                $_GET['emp_id'] ?? '',
                $entry_date,
                $plantId
            );
            if ($parkResult['count'] <= 0) {
                $failed[] = ['workorder_no' => $workorderNo, 'message' => implode('; ', $parkResult['errors']) ?: 'Unable to refresh parked line bookings'];
                continue;
            }
            $updated++;
        }

        echo json_encode([
            'status' => count($failed) === 0 ? 'success' : 'partial',
            'updated_count' => $updated,
            'failed_count' => count($failed),
            'failed' => $failed,
            'message' => $updated > 0 ? 'Line booking updated successfully' : 'No line bookings updated'
        ]);
    }

    else if ($_GET["type"] == "getUpdatableLineBookings") {
        list($page, $limit, $offset) = lbListPageParams(true);
        $output = [];
        $plantFilter = lbWoPlantFilterSql($conn);
        $where = "IFNULL(a.stp_planned_flag, 'No') = 'Yes'
                AND IFNULL(a.advance_planning_flag, 'No') != 'Yes'
                ".$plantFilter.lbWoSearchAnd($conn);
        $joinSql = lbWoOrderJoinSql()." WHERE ".$where;
        $total = lbCountJoin($conn, $joinSql);
        $sql = "SELECT a.*, b.product_code AS om_product_code, b.planMonth, b.mainGroupName,
                       b.packingStyle AS packing_type, b.planQty AS order_materials_planQty,
                       b.planUnit AS order_materials_planUnit, b.deliveryDate AS om_deliveryDate,
                       COALESCE(NULLIF(TRIM(b.product_name), ''),
                           (SELECT product_name FROM product p WHERE p.product_code = COALESCE(NULLIF(TRIM(a.product_code), ''), b.product_code) LIMIT 1)
                       ) AS product_name
                ".$joinSql."
                ORDER BY a.expected_production_start_date DESC, a.workorder_no DESC";
        if ($limit > 0) {
            $sql .= " LIMIT ".$offset.", ".$limit;
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['selectedLines'] = json_decode($row['selectedLines'] ?? '[]', true);
                if (!is_array($row['selectedLines'])) {
                    $row['selectedLines'] = array();
                }
                $row['line_numbers'] = summarizeLineNumbers($row['selectedLines']);
                $row['mfg_line_numbers'] = summarizeLineNumbers(filterLinesByCategory($row['selectedLines'], 'Manufacturing'));
                $row['packing_line_numbers'] = summarizeLineNumbers(filterLinesByCategory($row['selectedLines'], 'Packing'));
                $row['can_update'] = !workOrderSentToProduction($row);
                lbEnrichWoDisplayRow($conn, $row);
                $output[] = $row;
            }
        }
        echo json_encode(lbPaginatedPayload($output, $total, $page, $limit > 0 ? $limit : max(count($output), 1)));
    }

    else if ($_GET["type"] == "getLineBookingDisplayTokens") {
        header('Content-Type: application/json');
        ensureLineBookingDisplayTokensTable($conn);
        $output = [];
        $plantId = trim((string)($_GET['plant_id'] ?? ''));
        $plantFilter = ($plantId !== '' && strtolower($plantId) !== 'null')
            ? " WHERE plant_id = '".mysqli_real_escape_string($conn, $plantId)."'"
            : '';
        $sql = "SELECT id, plant_id, display_token, label, status, created_by, created_on, last_used_on
                FROM line_booking_display_tokens {$plantFilter}
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "generateLineBookingDisplayToken") {
        header('Content-Type: application/json');
        try {
            ensureLineBookingDisplayTokensTable($conn);
            $plantId = trim((string)($_GET['plant_id'] ?? ''));
            if ($plantId === '' || strtolower($plantId) === 'null' || strtolower($plantId) === 'undefined') {
                echo json_encode(['status' => 'error', 'message' => 'Plant ID is required. Please re-login and try again.']);
                exit;
            }
            $body = is_array($input) ? $input : [];
            $label = trim((string)($body['label'] ?? 'Line Booking TV Board'));
            if ($label === '') {
                $label = 'Line Booking TV Board';
            }
            if (function_exists('random_bytes')) {
                $tokenValue = bin2hex(random_bytes(24));
            } else {
                $tokenValue = bin2hex(openssl_random_pseudo_bytes(24));
            }
            $plantEsc = mysqli_real_escape_string($conn, $plantId);
            $tokenEsc = mysqli_real_escape_string($conn, $tokenValue);
            $labelEsc = mysqli_real_escape_string($conn, $label);
            $empEsc = mysqli_real_escape_string($conn, $_GET['emp_id'] ?? '');
            $insertSql = "INSERT INTO line_booking_display_tokens (plant_id, display_token, label, status, created_by, created_on)
                          VALUES ('{$plantEsc}', '{$tokenEsc}', '{$labelEsc}', 'Active', '{$empEsc}', '{$entry_date}')";
            if (!$conn->query($insertSql)) {
                echo json_encode(['status' => 'error', 'message' => 'DB insert failed: ' . $conn->error]);
                exit;
            }
            echo json_encode([
                'status' => 'success',
                'display_token' => $tokenValue,
                'plant_id' => $plantId,
                'label' => $label
            ]);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => 'Unable to generate TV link: ' . $e->getMessage()]);
        }
    }

    else if ($_GET["type"] == "getStpPlannedCalendar") {
        $startDate = $_GET["start_date"] ?? date('Y-m-01');
        $endDate = $_GET["end_date"] ?? date('Y-m-t');
        $output = Array();

        $sql = "SELECT a.*, b.product_code AS om_product_code, b.work_order_planned_qty, b.planMonth,
                b.planQty AS order_materials_planQty, b.planUnit AS order_materials_planUnit,
                b.mainGroupName, b.packingStyle AS packing_type, b.deliveryDate AS om_deliveryDate,
                COALESCE(NULLIF(TRIM(b.product_name), ''),
                    (SELECT product_name FROM product c WHERE c.product_code = COALESCE(NULLIF(TRIM(a.product_code), ''), b.product_code) LIMIT 1)
                ) AS product_name
                ".lbWoOrderJoinSql()."
                WHERE a.status = 'Sent for Batch Allocation'
                AND COALESCE(a.stp_planned_flag, 'No') = 'Yes'
                AND COALESCE(a.advance_planning_flag, 'No') = 'No'
                AND COALESCE(a.stp_line_approval_status, '') NOT IN ('Pending', 'Approved')
                AND a.expected_production_start_date IS NOT NULL
                AND DATE(a.expected_production_start_date) BETWEEN '".mysqli_real_escape_string($conn, $startDate)."'
                    AND '".mysqli_real_escape_string($conn, $endDate)."'
                ORDER BY a.expected_production_start_date ASC, a.expected_production_start_time ASC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["selectedLines"] = json_decode($row["selectedLines"] ?? '[]', true);
                if (!is_array($row["selectedLines"])) {
                    $row["selectedLines"] = array();
                }
                $row["line_numbers"] = summarizeLineNumbers($row["selectedLines"]);
                lbEnrichWoDisplayRow($conn, $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getAdvancePlanningHistory") {
        list($page, $limit, $offset) = lbListPageParams(true);
        $plantFilter = lbWoPlantFilterSql($conn);
        $where = "IFNULL(a.stp_planned_flag, 'No') = 'Yes'".$plantFilter.lbWoSearchAnd($conn);
        $joinSql = lbWoOrderJoinSql()." WHERE ".$where;
        $total = lbCountJoin($conn, $joinSql);
        $output = [];
        $sql = "SELECT a.*, b.product_code AS om_product_code, b.work_order_planned_qty, b.planMonth,
                       b.planQty AS order_materials_planQty, b.planUnit AS order_materials_planUnit,
                       b.mainGroupName, b.packingStyle AS packing_type, b.deliveryDate AS om_deliveryDate,
                       COALESCE(NULLIF(TRIM(b.product_name), ''),
                           (SELECT product_name FROM product p WHERE p.product_code = COALESCE(NULLIF(TRIM(a.product_code), ''), b.product_code) LIMIT 1)
                       ) AS product_name
                ".$joinSql."
                ORDER BY a.stp_planned_on DESC, a.workorder_no DESC";
        if ($limit > 0) {
            $sql .= " LIMIT ".$offset.", ".$limit;
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["selectedLines"] = json_decode($row["selectedLines"] ?? '[]', true);
                if (!is_array($row["selectedLines"])) {
                    $row["selectedLines"] = array();
                }
                $row["line_numbers"] = summarizeLineNumbers($row["selectedLines"]);
                $row["mfg_line_numbers"] = summarizeLineNumbers(filterLinesByCategory($row["selectedLines"], 'Manufacturing'));
                $row["packing_line_numbers"] = summarizeLineNumbers(filterLinesByCategory($row["selectedLines"], 'Packing'));
                $approval = strtolower(trim((string)($row['stp_line_approval_status'] ?? '')));
                if ($approval === 'approved') {
                    $row["log_status"] = 'Approved';
                } elseif ($approval === 'pending') {
                    $row["log_status"] = 'Pending Line Approval';
                } else {
                    $row["log_status"] = 'Planned';
                }
                $row["entry_date"] = $row["stp_planned_on"] ?? '';
                $row["entry_by"] = $row["stp_planned_by"] ?? '';
                lbEnrichWoDisplayRow($conn, $row);
                $output[] = $row;
            }
        }
        echo json_encode(lbPaginatedPayload($output, $total, $page, $limit > 0 ? $limit : max(count($output), 1)));
    }

    else if ($_GET["type"] == "recheckLineApprovalStock") {
        if (!is_array($input)) {
            $input = [];
        }
        $workorder_no = trim((string)($input['workorder_no'] ?? ''));
        if ($workorder_no === '') {
            echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
            exit;
        }
        $plant_id = trim((string)($_GET['plant_id'] ?? ''));
        if ($plant_id === '') {
            $plantSql = "SELECT plant_id FROM Work_order_materials
                WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."' LIMIT 1";
            $plantRes = $conn->query($plantSql);
            if ($plantRes && $plantRes->num_rows > 0) {
                $plant_id = trim((string)($plantRes->fetch_assoc()['plant_id'] ?? ''));
            }
        }
        $live = lbRecheckWorkOrderOwnCodeStock($conn, $workorder_no, $plant_id);
        $deductions = $live['deductions'];
        foreach ($deductions as &$dedRow) {
            $material_code = mysqli_real_escape_string($conn, $dedRow['material_code'] ?? '');
            $woEsc = mysqli_real_escape_string($conn, $workorder_no);
            $indentExists = false;
            $indentNo = '';
            $indentId = '';
            if ($material_code !== '') {
                $indentCheckSql = "SELECT id, no, request_no, status
                    FROM indend_raw
                    WHERE material_code = '".$material_code."'
                    AND required_for LIKE '%\"work_order_no\":\"".$woEsc."\"%'
                    AND status != 'Rejected'
                    ORDER BY id DESC LIMIT 1";
                $indentCheckResult = $conn->query($indentCheckSql);
                if ($indentCheckResult && $indentCheckResult->num_rows > 0) {
                    $indentRow = $indentCheckResult->fetch_assoc();
                    $indentExists = true;
                    $indentNo = $indentRow['no'] ?? '';
                    $indentId = $indentRow['id'] ?? '';
                }
            }
            $indentStatus = $dedRow['indent_status'] ?? 'Not Raised';
            if ($indentStatus === 'Raised' || $indentStatus === 'Indent Sent') {
                $indentExists = true;
                if ($indentNo === '' && !empty($dedRow['indent_no'])) {
                    $indentNo = $dedRow['indent_no'];
                }
                if ($indentId === '' && !empty($dedRow['indent_id'])) {
                    $indentId = $dedRow['indent_id'];
                }
            }
            $dedRow['indent_exists_in_indend_raw'] = $indentExists;
            if ($indentExists) {
                $dedRow['indent_no_from_indend_raw'] = $indentNo;
                $dedRow['indent_id_from_indend_raw'] = $indentId;
                if (empty($dedRow['indent_status']) || $dedRow['indent_status'] === 'Not Raised') {
                    $dedRow['indent_status'] = 'Raised';
                }
            }
        }
        unset($dedRow);

        $hasShortage = !empty($live['has_shortage']);
        $message = $hasShortage
            ? 'Own-code stock recheck complete. Shortage found.'
            : 'Own-code stock recheck complete. All materials are available.';

        echo json_encode([
            'status' => 'success',
            'has_shortage' => $hasShortage,
            'stockComplete' => !$hasShortage,
            'stockVerified' => count($deductions) > 0,
            'deductions' => $deductions,
            'shortage_materials' => $live['shortage_materials'],
            'message' => $message,
        ]);
        exit;
    }

}

$conn->close();
?>
