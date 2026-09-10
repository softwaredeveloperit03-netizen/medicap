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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ============================================
    // GET VERIFIED WORK ORDERS (Sent for Batch Allocation)
    // ============================================
    if ($_GET["type"] == "getVerifiedWorkOrders") {
        $output = Array();
        
        $sql = "SELECT a.*, b.product_code, b.work_order_planned_qty, b.planMonth,
                b.mainGroupName, b.packingStyle AS packing_type,
                (SELECT product_name FROM product c 
                 WHERE b.product_code = c.product_code LIMIT 1) AS product_name 
                FROM Work_order_materials a
                LEFT JOIN order_materials b ON a.order_no = b.order_no
                WHERE a.status = 'Sent for Batch Allocation'
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
                
                   $row["selectedLines"] = json_decode($row["selectedLines"]);
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
                $dosageForm = $row["mainGroupName"] ?? '';
                if ($dosageForm != '') {
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
                            
                            // Check if line is available for booking (no conflicts)
                            $lineRow["isAvailable"] = true;
                            $availableLines[] = $lineRow;
                        }
                    }
                }
                $row["Lines"] = $availableLines;
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // GET AVAILABLE LINES FOR PRODUCT
    // ============================================
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
            
            $response = ['status' => 'success', 'message' => 'Line booked successfully', 'booking_id' => $conn->insert_id];
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
            
            // Check if already parked for this work order and line
            $checkSql = "SELECT id FROM line_booking 
                        WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."' 
                        AND linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."'
                        AND status = 'Parked'";
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                // Update existing parked entry
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
                    updated_by = '".$_GET["emp_id"]."',
                    updated_date = '".$entry_date."'
                    WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."' 
                    AND linemaster_id = '".mysqli_real_escape_string($conn, $linemaster_id)."'
                    AND status = 'Parked'";
                
                if ($conn->query($updateSql)) {
                    $parkedCount++;
                } else {
                    $errors[] = "Failed to update parked line {$line_no}: " . $conn->error;
                }
            } else {
                // Insert new parked entry
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
                    'Parked',
                    '".$_GET["emp_id"]."',
                    '".$entry_date."',
                    '".$_GET["plant_id"]."'
                )";
                
                if ($conn->query($insertSql)) {
                    $parkedCount++;
                } else {
                    $errors[] = "Failed to park line {$line_no}: " . $conn->error;
                }
            }
        }
        
        if ($parkedCount > 0) {
            $message = "Successfully parked {$parkedCount} line(s)";
            if (count($errors) > 0) {
                $message .= ". Errors: " . implode(", ", $errors);
            }
            
            // Log the action to database log table
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'parkLinesForMfg: ' . mysqli_real_escape_string($conn, $workorder_no);
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            // Also enhance file log with workorder number
            $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "parkLinesForMfg", "actiontime": "'.$logEntryDate.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'", "workorder_no": "'.$workorder_no.'"}';
            $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            // Update work order status to 'Parked for Approval' to remove from planning tab
            $updateStatusSql = "UPDATE Work_order_materials SET status = 'Parked for Approval' WHERE workorder_no = '".mysqli_real_escape_string($conn, $workorder_no)."'";
            $conn->query($updateStatusSql);
            
            echo json_encode(['status' => 'success', 'message' => $message, 'parked_count' => $parkedCount]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to park lines. Errors: ' . implode(", ", $errors)]);
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
                    (SELECT lb.product_code 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Parked' 
                     LIMIT 1) as product_code,
                    (SELECT lb.product_name 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Parked' 
                     LIMIT 1) as product_name,
                    (SELECT lb.capacity_required 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Parked' 
                     LIMIT 1) as plan_qty,
                    (SELECT lb.responsible_person 
                     FROM line_booking lb 
                     WHERE lb.workorder_no = wom.workorder_no 
                     AND lb.status = 'Parked' 
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
                WHERE wom.selectedLines IS NOT NULL 
                AND wom.selectedLines != ''
                AND wom.selectedLines != 'null'
                AND EXISTS (
                    SELECT 1 FROM line_booking lb2 
                    WHERE lb2.workorder_no = wom.workorder_no 
                    AND lb2.status = 'Parked'
                )
                ORDER BY wom.workorder_no DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Parse selectedLines JSON
                $selectedLines = json_decode($row["selectedLines"] ?? '[]', true);
                $row["selectedLines"] = $selectedLines;
                
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
        $input = json_decode(file_get_contents('php://input'), true);
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
            // Log the action to database log table
            $logEntryDate = date("Y-m-d H:i:s", time());
            $actionWithWO = 'approveWorkOrder: ' . mysqli_real_escape_string($conn, $workorder_no);
            $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR, frontend_url) 
                       VALUES ('FRONTEND', '".mysqli_real_escape_string($conn, $token)."', '".$actionWithWO."', '".$logEntryDate."', '".mysqli_real_escape_string($conn, $_GET["department"])."', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."', '".mysqli_real_escape_string($conn, $currentUrl)."')";
            $conn->query($logSql);
            
            // Also enhance file log with workorder number
            $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "approveWorkOrder", "actiontime": "'.$logEntryDate.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'", "workorder_no": "'.$workorder_no.'"}';
            $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            echo json_encode(['status' => 'success', 'message' => 'Work order approved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to approve work order: ' . $conn->error]);
        }
    }
    
    // ============================================
    // REJECT WORK ORDER (Remove parked entries)
    // ============================================
    else if ($_GET["type"] == "rejectWorkOrder") {
        $input = json_decode(file_get_contents('php://input'), true);
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
                     SET selectedLines = NULL 
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
        $input = json_decode(file_get_contents('php://input'), true);
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
        $input = json_decode(file_get_contents('php://input'), true);
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
        $input = json_decode(file_get_contents('php://input'), true);
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
        $input = json_decode(file_get_contents('php://input'), true);
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
}

$conn->close();
?>
