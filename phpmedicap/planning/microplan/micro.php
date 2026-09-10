<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL); 


require '../../db.php';
require '../../token.php';


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
    // GET WORK ORDERS BY PRODUCT CODE
    // For micro component - returns work orders with batch_number and actual dates
    // ============================================
    if ($_GET["type"] == "getWorkOrdersByProduct") {
        $product_code = $_GET["product_code"] ?? '';
        $status = $_GET["status"] ?? '';
        
        if (empty($product_code)) {
            echo json_encode([]);
            exit;
        }
        
        $whereClause = "lb.product_code = '".mysqli_real_escape_string($conn, $product_code)."'";
        
        // Handle status filter (comma-separated values)
        if (!empty($status)) {
            $statuses = explode(',', $status);
            $statusConditions = [];
            foreach ($statuses as $stat) {
                $stat = trim($stat);
                if ($stat != '') {
                    $statusConditions[] = "lb.status = '".mysqli_real_escape_string($conn, $stat)."'";
                }
            }
            if (count($statusConditions) > 0) {
                $whereClause .= " AND (".implode(" OR ", $statusConditions).")";
            }
        }
        
        $sql = "SELECT 
                lb.id,
                lb.workorder_no,
                lb.product_code,
                lb.product_name,
                lb.status,
                lb.booking_start_date,
                lb.booking_start_time,
                lb.booking_end_date,
                lb.booking_end_time,
                lb.responsible_person,
                lb.linemaster_id,
                lb.line_no,
                mwoh.batch_number,
                mwoh.batch_commence_date AS actual_start_date,
                mwoh.batch_complete_date AS actual_end_date,
                mwoh.qa_person,
                mwoh.qa_date,
                mwoh.dispensing_status
                FROM line_booking lb
                LEFT JOIN mfg_work_order_hdr mwoh ON lb.workorder_no = mwoh.work_order_no
                WHERE ".$whereClause."
                ORDER BY lb.booking_start_date DESC, lb.booking_start_time DESC";
        
        $result = $conn->query($sql);
        $output = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decode equipment JSON if exists
                if (isset($row["selected_equipments"])) {
                    $row["selected_equipments"] = json_decode($row["selected_equipments"] ?? '[]', true);
                }
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // GET STAGE DATES FOR WORK ORDERS
    // Returns tentative and actual dates for production stages
    // ============================================
     else if ($_GET["type"] == "getStageDates") {
        $workorder_nos = $_GET["workorder_nos"] ?? '';
        
        if (empty($workorder_nos)) {
            echo json_encode([]);
            exit;
        }
        
        // Create table if it doesn't exist (with all new fields)
        $createTableSql = "CREATE TABLE IF NOT EXISTS `workorder_stage_dates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `workorder_no` varchar(100) NOT NULL,
            `dosage_form` varchar(100) NOT NULL,
            `stage` varchar(100) NOT NULL,
            `tentative_start_date` date DEFAULT NULL,
            `tentative_start_time` time DEFAULT NULL,
            `actual_start_date` date DEFAULT NULL,
            `actual_start_time` time DEFAULT NULL,
            `tentative_completion_date` date DEFAULT NULL,
            `tentative_completion_time` time DEFAULT NULL,
            `actual_completion_date` date DEFAULT NULL,
            `actual_completion_time` time DEFAULT NULL,
            `stage_status` ENUM('Not Started', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Not Started',
            `duration_hours` decimal(10,2) DEFAULT NULL,
            `entry_by` varchar(100) DEFAULT NULL,
            `entry_date` datetime DEFAULT NULL,
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_wo_stage` (`workorder_no`, `dosage_form`, `stage`),
            KEY `idx_workorder_no` (`workorder_no`),
            KEY `idx_workorder_stage` (`workorder_no`, `stage`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $conn->query($createTableSql);
        
        // Add new columns if they don't exist (for existing tables)
        $alterColumns = [
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `tentative_start_date` date DEFAULT NULL AFTER `stage`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `tentative_start_time` time DEFAULT NULL AFTER `tentative_start_date`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `actual_start_date` date DEFAULT NULL AFTER `tentative_start_time`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `actual_start_time` time DEFAULT NULL AFTER `actual_start_date`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `tentative_completion_time` time DEFAULT NULL AFTER `tentative_completion_date`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `actual_completion_time` time DEFAULT NULL AFTER `actual_completion_date`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `stage_status` ENUM('Not Started', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Not Started' AFTER `actual_completion_time`",
            "ALTER TABLE `workorder_stage_dates` ADD COLUMN `duration_hours` decimal(10,2) DEFAULT NULL AFTER `stage_status`"
        ];
        
        foreach ($alterColumns as $alterSql) {
            $columnName = '';
            if (preg_match("/ADD COLUMN `([^`]+)`/", $alterSql, $matches)) {
                $columnName = $matches[1];
                $checkColumnSql = "SELECT COUNT(*) as col_count FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_SCHEMA = DATABASE() 
                                  AND TABLE_NAME = 'workorder_stage_dates' 
                                  AND COLUMN_NAME = '".$columnName."'";
                $checkResult = $conn->query($checkColumnSql);
                if ($checkResult && $checkResult->num_rows > 0) {
                    $checkRow = $checkResult->fetch_assoc();
                    if ($checkRow['col_count'] == 0) {
                        @$conn->query($alterSql);
                    }
                }
            }
        }
        
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
        // Get only the latest entry for each workorder_no and stage combination (based on MAX(id))
        $sql = "SELECT wsd.* FROM workorder_stage_dates wsd
                INNER JOIN (
                    SELECT workorder_no, stage, MAX(id) as max_id 
                    FROM workorder_stage_dates 
                    WHERE workorder_no IN (".implode(',', $workorderList).")
                    GROUP BY workorder_no, stage
                ) latest ON wsd.workorder_no = latest.workorder_no 
                    AND wsd.stage = latest.stage 
                    AND wsd.id = latest.max_id
                WHERE wsd.workorder_no IN (".implode(',', $workorderList).")";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Also include tentative_date for backward compatibility
                if (!isset($row['tentative_date']) && isset($row['tentative_completion_date'])) {
                    $row['tentative_date'] = $row['tentative_completion_date'];
                }
                if (!isset($row['tentative_time']) && isset($row['tentative_completion_time'])) {
                    $row['tentative_time'] = $row['tentative_completion_time'];
                }
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }
    
    // ============================================
    // DEFAULT: Return empty array if type not found
    // ============================================
    else {
        echo json_encode(['error' => 'Invalid type parameter']);
    }
    
} else {
    echo json_encode(['error' => 'Invalid token']);
}
