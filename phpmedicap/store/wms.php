<?php

require '../db.php';
require '../token.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

$type = isset($_GET['type']) ? $_GET['type'] : '';
$plant_id = isset($_SESSION['plant_id']) ? $_SESSION['plant_id'] : (isset($_GET['plant_id']) ? $_GET['plant_id'] : '');

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = ($page - 1) * $limit;

try {
    switch ($type) {
        
        // ==================== TRACKING FUNCTIONS ====================
        
        case 'trackMaterial':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            // Get material details from batch tables using tracking_id
            $sql = "SELECT 
                        sb.trackingId as tracking_id,
                        sb.material_code,
                        mva.material_name,
                        mva.material_type,
                        sb.batch_no,
                        sb.qty_received as qty,
                        sb.unit,
                        v.vendor_name,
                        sb.mfg_by as vendor_code,
                        sb.grn_no,
                        sb.ar_no,
                        sb.grnReceiveBy,
                        sb.grnReceiveOn,
                        sb.challan_no,
                        sb.ch_no,
                        sb.mfg_date,
                        sb.exp_date,
                        sb.total_containers,
                        sb.pack_size,
                        cm.receiving_no,
                        cm.received_by,
                        cm.receiving_date,
                        cm.weighing_no,
                        cm.weighing_by,
                        cm.weighing_date,
                        cm.grn_by,
                        cm.grn_date,
                        vw.total_stock_qty,
                        vw.available_qty,
                        vw.status as stock_status
                    FROM sampling_batches sb
                    LEFT JOIN my_view_all mva ON sb.material_code = mva.material_code
                    LEFT JOIN vendor v ON sb.mfg_by = v.vendor_no AND sb.plant_id = v.plant_id
                    LEFT JOIN challan_materials cm ON sb.challan_no = cm.challan_no AND sb.ch_no = cm.ch_no
                    LEFT JOIN vw_available_stock vw ON sb.trackingId = vw.trackingId  
                    WHERE sb.trackingId = ? AND sb.plant_id = ?
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Material not found']);
                exit;
            }
            
            $material = $result->fetch_assoc();
            
            // Get location hierarchy through mappings
            $locationHierarchy = null;
            
            // Check material-palate mapping
            $sqlMapping = "SELECT palate_barcode, mapped_date 
                          FROM wms_material_palate_mapping 
                          WHERE material_barcode = ? AND status = 'active' 
                          LIMIT 1";
            $stmtMapping = $conn->prepare($sqlMapping);
            $stmtMapping->bind_param("s", $barcode);
            $stmtMapping->execute();
            $mappingResult = $stmtMapping->get_result();
            
            if ($mappingResult->num_rows > 0) {
                $mapping = $mappingResult->fetch_assoc();
                $palateBarcode = $mapping['palate_barcode'];
                
                // Check palate-location mapping
                $sqlLocation = "SELECT location_barcode 
                               FROM wms_palate_location_mapping 
                               WHERE palate_barcode = ? AND status = 'active' 
                               LIMIT 1";
                $stmtLocation = $conn->prepare($sqlLocation);
                $stmtLocation->bind_param("s", $palateBarcode);
                $stmtLocation->execute();
                $locationResult = $stmtLocation->get_result();
                
                if ($locationResult->num_rows > 0) {
                    $locationMapping = $locationResult->fetch_assoc();
                    $locationBarcode = $locationMapping['location_barcode'];
                    
                    // Get location details (includes rack_no and laneNO from master)
                    $sqlLocationDetails = "SELECT locationNo, rack_no, laneNO, section_name 
                                          FROM locationMaster 
                                          WHERE locationNo = ? AND plant_id = ? 
                                          LIMIT 1";
                    $stmtLocationDetails = $conn->prepare($sqlLocationDetails);
                    $stmtLocationDetails->bind_param("ss", $locationBarcode, $plant_id);
                    $stmtLocationDetails->execute();
                    $locationDetailsResult = $stmtLocationDetails->get_result();
                    
                    if ($locationDetailsResult->num_rows > 0) {
                        $locationDetails = $locationDetailsResult->fetch_assoc();
                        $locationHierarchy = [
                            'palate_barcode' => $palateBarcode,
                            'locationNo' => $locationDetails['locationNo'],
                            'rack_no' => $locationDetails['rack_no'],
                            'laneNO' => $locationDetails['laneNO'],
                            'section_name' => $locationDetails['section_name']
                        ];
                    }
                }
            }
            
            $material['location_hierarchy'] = $locationHierarchy;
            
            // Log tracking action
            logTrackingAction($conn, $barcode, 'material', 'track', 'Material tracked: ' . $material['material_code']);
            
            echo json_encode(['status' => 'success', 'data' => $material]);
            break;
            
        case 'trackPalate':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            // Get palate details
            $sql = "SELECT * FROM palatteMaster WHERE paletteNo = ? AND plant_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Palate not found']);
                exit;
            }
            
            $palate = $result->fetch_assoc();
            
            // Get materials on palate (paginated)
            $sqlMaterials = "SELECT 
                                sb.trackingId as tracking_id,
                                sb.material_code,
                                mva.material_name,
                                mva.material_type,
                                sb.batch_no,
                                sb.qty_received as qty,
                                sb.unit,
                                v.vendor_name,
                                sb.mfg_by as vendor_code,
                                sb.mfg_date,
                                sb.exp_date,
                                sb.total_containers,
                                sb.pack_size,
                                sb.grn_no,
                                sb.ar_no,
                                sb.grnReceiveBy,
                                sb.grnReceiveOn,
                                cm.receiving_no,
                                cm.received_by,
                                cm.receiving_date,
                                cm.weighing_no,
                                cm.weighing_by,
                                cm.weighing_date,
                                cm.grn_by,
                                cm.grn_date,
                                vw.total_stock_qty,
                                vw.available_qty,
                                vw.status as stock_status
                            FROM wms_material_palate_mapping mpm
                            JOIN sampling_batches sb ON mpm.material_barcode = sb.trackingId
                            LEFT JOIN my_view_all mva ON sb.material_code = mva.material_code
                            LEFT JOIN vendor v ON sb.mfg_by = v.vendor_no AND sb.plant_id = v.plant_id
                            LEFT JOIN challan_materials cm ON sb.challan_no = cm.challan_no AND sb.ch_no = cm.ch_no
                            LEFT JOIN vw_available_stock vw ON sb.trackingId = vw.trackingId  
                            WHERE mpm.palate_barcode = ? AND mpm.status = 'active'
                            LIMIT ? OFFSET ?";
            
            $stmtMaterials = $conn->prepare($sqlMaterials);
            $stmtMaterials->bind_param("sii", $barcode, $limit, $offset);
            $stmtMaterials->execute();
            $materialsResult = $stmtMaterials->get_result();
            
            $materials = [];
            while ($row = $materialsResult->fetch_assoc()) {
                $materials[] = $row;
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total 
                        FROM wms_material_palate_mapping 
                        WHERE palate_barcode = ? AND status = 'active'";
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bind_param("s", $barcode);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            // Get location hierarchy
            $locationHierarchy = null;
            $sqlLocation = "SELECT plm.location_barcode, lm.locationNo, lm.rack_no, lm.laneNO, lm.section_name
                           FROM wms_palate_location_mapping plm
                           JOIN locationMaster lm ON plm.location_barcode = lm.locationNo
                           WHERE plm.palate_barcode = ? AND plm.status = 'active' AND lm.plant_id = ?
                           LIMIT 1";
            $stmtLocation = $conn->prepare($sqlLocation);
            $stmtLocation->bind_param("ss", $barcode, $plant_id);
            $stmtLocation->execute();
            $locationResult = $stmtLocation->get_result();
            
            if ($locationResult->num_rows > 0) {
                $locationData = $locationResult->fetch_assoc();
                $locationHierarchy = [
                    'locationNo' => $locationData['locationNo'],
                    'rack_no' => $locationData['rack_no'],
                    'laneNO' => $locationData['laneNO'],
                    'section_name' => $locationData['section_name']
                ];
            }
            
            $palate['location_hierarchy'] = $locationHierarchy;
            
            // Log tracking action
            logTrackingAction($conn, $barcode, 'palate', 'track', 'Palate tracked');
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'palate' => $palate,
                    'materials' => $materials,
                    'total' => $total
                ]
            ]);
            break;
            
        case 'trackLocation':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            // Get location details (includes rack_no and laneNO from master)
            $sql = "SELECT * FROM locationMaster WHERE locationNo = ? AND plant_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Location not found']);
                exit;
            }
            
            $location = $result->fetch_assoc();
            
            // Get palate at location
            $palate = null;
            $sqlPalate = "SELECT plm.palate_barcode, pm.*
                         FROM wms_palate_location_mapping plm
                         JOIN palatteMaster pm ON plm.palate_barcode = pm.paletteNo
                         WHERE plm.location_barcode = ? AND plm.status = 'active' AND pm.plant_id = ?
                         LIMIT 1";
            $stmtPalate = $conn->prepare($sqlPalate);
            $stmtPalate->bind_param("ss", $barcode, $plant_id);
            $stmtPalate->execute();
            $palateResult = $stmtPalate->get_result();
            
            if ($palateResult->num_rows > 0) {
                $palate = $palateResult->fetch_assoc();
                $palateBarcode = $palate['paletteNo'];
                
            // Get materials on palate (paginated)
            $sqlMaterials = "SELECT 
                                sb.trackingId as tracking_id,
                                sb.material_code,
                                mva.material_name,
                                mva.material_type,
                                sb.batch_no,
                                sb.qty_received as qty,
                                sb.unit,
                                v.vendor_name,
                                sb.mfg_by as vendor_code,
                                sb.mfg_date,
                                sb.exp_date,
                                sb.total_containers,
                                sb.pack_size,
                                sb.grn_no,
                                sb.ar_no,
                                sb.grnReceiveBy,
                                sb.grnReceiveOn,
                                cm.receiving_no,
                                cm.received_by,
                                cm.receiving_date,
                                cm.weighing_no,
                                cm.weighing_by,
                                cm.weighing_date,
                                cm.grn_by,
                                cm.grn_date,
                                vw.total_stock_qty,
                                vw.available_qty,
                                vw.status as stock_status
                            FROM wms_material_palate_mapping mpm
                            JOIN sampling_batches sb ON mpm.material_barcode = sb.trackingId
                            LEFT JOIN my_view_all mva ON sb.material_code = mva.material_code
                            LEFT JOIN vendor v ON sb.mfg_by = v.vendor_no AND sb.plant_id = v.plant_id
                            LEFT JOIN challan_materials cm ON sb.challan_no = cm.challan_no AND sb.ch_no = cm.ch_no
                            LEFT JOIN vw_available_stock vw ON sb.trackingId = vw.trackingId  
                            WHERE mpm.palate_barcode = ? AND mpm.status = 'active'
                            LIMIT ? OFFSET ?";
                
                $stmtMaterials = $conn->prepare($sqlMaterials);
                $stmtMaterials->bind_param("sii", $palateBarcode, $limit, $offset);
                $stmtMaterials->execute();
                $materialsResult = $stmtMaterials->get_result();
                
                $materials = [];
                while ($row = $materialsResult->fetch_assoc()) {
                    $materials[] = $row;
                }
                
                // Get total count
                $sqlCount = "SELECT COUNT(*) as total 
                            FROM wms_material_palate_mapping 
                            WHERE palate_barcode = ? AND status = 'active'";
                $stmtCount = $conn->prepare($sqlCount);
                $stmtCount->bind_param("s", $palateBarcode);
                $stmtCount->execute();
                $countResult = $stmtCount->get_result();
                $total = $countResult->fetch_assoc()['total'];
            } else {
                $materials = [];
                $total = 0;
            }
            
            // Log tracking action
            logTrackingAction($conn, $barcode, 'location', 'track', 'Location tracked');
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'location' => $location,
                    'palate' => $palate,
                    'materials' => $materials,
                    'total' => $total
                ]
            ]);
            break;
            
        case 'trackRack':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            // Get rack details (includes laneNO from master)
            $sql = "SELECT * FROM rack WHERE rack_no = ? AND plant_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Rack not found']);
                exit;
            }
            
            $rack = $result->fetch_assoc();
            
            // Get locations in rack (paginated) - using subquery for better accuracy
            $sqlLocations = "SELECT 
                                lm.locationNo,
                                COALESCE(plm.palate_barcode, '') as palate_barcode,
                                COALESCE((
                                    SELECT COUNT(DISTINCT mpm.material_barcode)
                                    FROM wms_material_palate_mapping mpm
                                    WHERE mpm.palate_barcode = plm.palate_barcode 
                                    AND mpm.status = 'active'
                                ), 0) as materials_count
                            FROM locationMaster lm
                            LEFT JOIN wms_palate_location_mapping plm ON lm.locationNo = plm.location_barcode AND plm.status = 'active'
                            WHERE lm.rack_no = ? AND lm.plant_id = ?
                            ORDER BY lm.locationNo
                            LIMIT ? OFFSET ?";
            
            $stmtLocations = $conn->prepare($sqlLocations);
            $stmtLocations->bind_param("ssii", $barcode, $plant_id, $limit, $offset);
            $stmtLocations->execute();
            $locationsResult = $stmtLocations->get_result();
            
            $locations = [];
            while ($row = $locationsResult->fetch_assoc()) {
                $palateBarcode = trim($row['palate_barcode']);
                $locations[] = [
                    'locationNo' => $row['locationNo'],
                    'palate_barcode' => $palateBarcode ?: '',
                    'materials_count' => (int)$row['materials_count'],
                    'occupied' => !empty($palateBarcode)
                ];
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total 
                        FROM locationMaster 
                        WHERE rack_no = ? AND plant_id = ?";
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bind_param("ss", $barcode, $plant_id);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            // Log tracking action
            logTrackingAction($conn, $barcode, 'rack', 'track', 'Rack tracked');
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'rack' => $rack,
                    'locations' => $locations,
                    'total' => $total
                ]
            ]);
            break;
            
        case 'trackLane':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            // Get lane details
            $sql = "SELECT * FROM laneMaster WHERE laneNO = ? AND plant_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Lane not found']);
                exit;
            }
            
            $lane = $result->fetch_assoc();
            
            // Get racks in lane (paginated)
            $sqlRacks = "SELECT 
                            r.rack_no,
                            r.status,
                            COALESCE(COUNT(DISTINCT lm.locationNo), 0) as locations_count,
                            COALESCE(COUNT(DISTINCT plm.palate_barcode), 0) as palates_count,
                            COALESCE(COUNT(DISTINCT mpm.material_barcode), 0) as materials_count
                        FROM rack r
                        LEFT JOIN locationMaster lm ON r.rack_no = lm.rack_no AND lm.plant_id = ?
                        LEFT JOIN wms_palate_location_mapping plm ON lm.locationNo = plm.location_barcode AND plm.status = 'active'
                        LEFT JOIN wms_material_palate_mapping mpm ON plm.palate_barcode = mpm.palate_barcode AND mpm.status = 'active'
                        WHERE r.laneNO = ? AND r.plant_id = ?
                        GROUP BY r.rack_no, r.status
                        ORDER BY r.rack_no
                        LIMIT ? OFFSET ?";
            
            $stmtRacks = $conn->prepare($sqlRacks);
            $stmtRacks->bind_param("sssii", $plant_id, $barcode, $plant_id, $limit, $offset);
            $stmtRacks->execute();
            $racksResult = $stmtRacks->get_result();
            
            $racks = [];
            $totalLocations = 0;
            $totalPalates = 0;
            $totalMaterials = 0;
            
            while ($row = $racksResult->fetch_assoc()) {
                $rack = [
                    'rack_no' => $row['rack_no'],
                    'status' => $row['status'],
                    'locations_count' => (int)$row['locations_count'],
                    'palates_count' => (int)$row['palates_count'],
                    'materials_count' => (int)$row['materials_count']
                ];
                $racks[] = $rack;
                $totalLocations += $rack['locations_count'];
                $totalPalates += $rack['palates_count'];
                $totalMaterials += $rack['materials_count'];
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total 
                        FROM rack 
                        WHERE laneNO = ? AND plant_id = ?";
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bind_param("ss", $barcode, $plant_id);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            // Log tracking action
            logTrackingAction($conn, $barcode, 'lane', 'track', 'Lane tracked');
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'lane' => $lane,
                    'racks' => $racks,
                    'total' => $total,
                    'summary' => [
                        'totalLocations' => $totalLocations,
                        'totalPalates' => $totalPalates,
                        'totalMaterials' => $totalMaterials
                    ]
                ]
            ]);
            break;
            
        // ==================== VALIDATION FUNCTIONS ====================
        
        case 'validateMaterial':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            $sql = "SELECT 
                        sb.trackingId as tracking_id,
                        sb.material_code,
                        mva.material_name,
                        mva.material_type,
                        sb.batch_no,
                        sb.qty_received as qty,
                        sb.unit,
                        v.vendor_name,
                        sb.mfg_by as vendor_code,
                        sb.mfg_date,
                        sb.exp_date,
                        sb.total_containers,
                        sb.pack_size,
                        sb.grn_no,
                        sb.grnReceiveBy,
                        sb.grnReceiveOn,
                        cm.receiving_no,
                        cm.received_by,
                        cm.receiving_date,
                        cm.weighing_no,
                        cm.weighing_by,
                        cm.weighing_date,
                        cm.grn_by,
                        cm.grn_date
                    FROM sampling_batches sb
                    LEFT JOIN my_view_all mva ON sb.material_code = mva.material_code
                    LEFT JOIN vendor v ON sb.mfg_by = v.vendor_no AND sb.plant_id = v.plant_id
                    LEFT JOIN challan_materials cm ON sb.challan_no = cm.challan_no AND sb.ch_no = cm.ch_no
                    WHERE sb.trackingId = ? AND sb.plant_id = ? 
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Material not found']);
                exit;
            }
            
            echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            break;
            
        case 'validatePalate':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            $sql = "SELECT * FROM palatteMaster WHERE paletteNo = ? AND plant_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Palate not found']);
                exit;
            }
            
            echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            break;
            
        case 'validateLocation':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            $sql = "SELECT * FROM locationMaster WHERE locationNo = ? AND plant_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $barcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Location not found']);
                exit;
            }
            
            echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            break;
            
        case 'checkLocationOccupancy':
            $locationBarcode = isset($_GET['location_barcode']) ? $_GET['location_barcode'] : '';
            if (empty($locationBarcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Location barcode is required']);
                exit;
            }
            
            $sql = "SELECT plm.palate_barcode, pm.*
                    FROM wms_palate_location_mapping plm
                    JOIN palatteMaster pm ON plm.palate_barcode = pm.paletteNo
                    WHERE plm.location_barcode = ? AND plm.status = 'active' AND pm.plant_id = ?
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $locationBarcode, $plant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'occupied' => true,
                        'palate' => $result->fetch_assoc()
                    ]
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'occupied' => false,
                        'palate' => null
                    ]
                ]);
            }
            break;
            
        // ==================== MAPPING FUNCTIONS ====================
        
        case 'mapMaterialToPalate':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['palate_barcode']) || !isset($data['materials']) || !is_array($data['materials'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
                exit;
            }
            
            $palateBarcode = $data['palate_barcode'];
            $materials = $data['materials'];
            $mappedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
            $mappedDate = date('Y-m-d H:i:s');
            
            $conn->begin_transaction();
            
            try {
                foreach ($materials as $materialBarcode) {
                    // Check if mapping already exists
                    $sqlCheck = "SELECT id FROM wms_material_palate_mapping 
                                WHERE material_barcode = ? AND palate_barcode = ? AND status = 'active'";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param("ss", $materialBarcode, $palateBarcode);
                    $stmtCheck->execute();
                    $checkResult = $stmtCheck->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        continue; // Skip if already mapped
                    }
                    
                    // Insert mapping
                    $sql = "INSERT INTO wms_material_palate_mapping 
                            (material_barcode, palate_barcode, mapped_date, mapped_by, status) 
                            VALUES (?, ?, ?, ?, 'active')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $materialBarcode, $palateBarcode, $mappedDate, $mappedBy);
                    $stmt->execute();
                    
                    // Log action for each material
                    logTrackingAction($conn, $materialBarcode, 'material', 'map', 'Mapped to palate: ' . $palateBarcode);
                }
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Mapping saved successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Failed to save mapping: ' . $e->getMessage()]);
            }
            break;
            
        case 'mapPalateToLocation':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['palate_barcode']) || !isset($data['location_barcode'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
                exit;
            }
            
            $palateBarcode = $data['palate_barcode'];
            $locationBarcode = $data['location_barcode'];
            $mappedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
            $mappedDate = date('Y-m-d H:i:s');
            
            $conn->begin_transaction();
            
            try {
                // Check if location is already occupied
                $sqlCheck = "SELECT palate_barcode FROM wms_palate_location_mapping 
                            WHERE location_barcode = ? AND status = 'active'";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bind_param("s", $locationBarcode);
                $stmtCheck->execute();
                $checkResult = $stmtCheck->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $existingPalate = $checkResult->fetch_assoc()['palate_barcode'];
                    // Deactivate existing mapping
                    $sqlDeactivate = "UPDATE wms_palate_location_mapping 
                                      SET status = 'removed', removed_date = ?, removed_by = ?
                                      WHERE location_barcode = ? AND status = 'active'";
                    $stmtDeactivate = $conn->prepare($sqlDeactivate);
                    $stmtDeactivate->bind_param("sss", $mappedDate, $mappedBy, $locationBarcode);
                    $stmtDeactivate->execute();
                }
                
                // Check if palate is already mapped to another location
                $sqlCheckPalate = "SELECT location_barcode FROM wms_palate_location_mapping 
                                  WHERE palate_barcode = ? AND status = 'active'";
                $stmtCheckPalate = $conn->prepare($sqlCheckPalate);
                $stmtCheckPalate->bind_param("s", $palateBarcode);
                $stmtCheckPalate->execute();
                $checkPalateResult = $stmtCheckPalate->get_result();
                
                if ($checkPalateResult->num_rows > 0) {
                    // Deactivate existing mapping
                    $sqlDeactivatePalate = "UPDATE wms_palate_location_mapping 
                                           SET status = 'removed', removed_date = ?, removed_by = ?
                                           WHERE palate_barcode = ? AND status = 'active'";
                    $stmtDeactivatePalate = $conn->prepare($sqlDeactivatePalate);
                    $stmtDeactivatePalate->bind_param("sss", $mappedDate, $mappedBy, $palateBarcode);
                    $stmtDeactivatePalate->execute();
                }
                
                // Insert new mapping
                $sql = "INSERT INTO wms_palate_location_mapping 
                        (palate_barcode, location_barcode, mapped_date, mapped_by, status) 
                        VALUES (?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $palateBarcode, $locationBarcode, $mappedDate, $mappedBy);
                $stmt->execute();
                
                // Log action
                logTrackingAction($conn, $palateBarcode, 'palate', 'map', 'Mapped to location: ' . $locationBarcode);
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Mapping saved successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Failed to save mapping: ' . $e->getMessage()]);
            }
            break;
            
        case 'emptyMaterialFromPalate':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['material_barcode']) || !isset($data['palate_barcode'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
                exit;
            }
            
            $materialBarcode = $data['material_barcode'];
            $palateBarcode = $data['palate_barcode'];
            $removedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
            $removedDate = date('Y-m-d H:i:s');
            
            $sql = "UPDATE wms_material_palate_mapping 
                    SET status = 'removed', removed_date = ?, removed_by = ?
                    WHERE material_barcode = ? AND palate_barcode = ? AND status = 'active'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $removedDate, $removedBy, $materialBarcode, $palateBarcode);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                // Log action
                logTrackingAction($conn, $materialBarcode, 'material', 'empty', 'Removed from palate: ' . $palateBarcode);
                echo json_encode(['status' => 'success', 'message' => 'Material removed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Mapping not found']);
            }
            break;
            
        case 'emptyPalateFromLocation':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['palate_barcode']) || !isset($data['location_barcode'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
                exit;
            }
            
            $palateBarcode = $data['palate_barcode'];
            $locationBarcode = $data['location_barcode'];
            $removedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
            $removedDate = date('Y-m-d H:i:s');
            
            $sql = "UPDATE wms_palate_location_mapping 
                    SET status = 'removed', removed_date = ?, removed_by = ?
                    WHERE palate_barcode = ? AND location_barcode = ? AND status = 'active'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $removedDate, $removedBy, $palateBarcode, $locationBarcode);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                // Log action
                logTrackingAction($conn, $palateBarcode, 'palate', 'empty', 'Removed from location: ' . $locationBarcode);
                echo json_encode(['status' => 'success', 'message' => 'Palate removed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Mapping not found']);
            }
            break;
            
        case 'getMaterialPalateMapping':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            $sql = "SELECT 
                        mpm.material_barcode,
                        mpm.palate_barcode,
                        mpm.mapped_date,
                        sb.material_code,
                        mva.material_name,
                        mva.material_type,
                        sb.batch_no,
                        pm.paletteNo,
                        pm.section_name,
                        pm.status as palate_status
                    FROM wms_material_palate_mapping mpm
                    JOIN sampling_batches sb ON mpm.material_barcode = sb.trackingId
                    LEFT JOIN my_view_all mva ON sb.material_code = mva.material_code
                    JOIN palatteMaster pm ON mpm.palate_barcode = pm.paletteNo
                    WHERE mpm.material_barcode = ? AND mpm.status = 'active'
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Mapping not found']);
                exit;
            }
            
            $mapping = $result->fetch_assoc();
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'mapping' => [
                        'mapped_date' => $mapping['mapped_date']
                    ],
                    'material' => [
                        'tracking_id' => $mapping['material_barcode'],
                        'material_code' => $mapping['material_code'],
                        'material_name' => $mapping['material_name'],
                        'batch_no' => $mapping['batch_no']
                    ],
                    'palate' => [
                        'paletteNo' => $mapping['paletteNo'],
                        'section_name' => $mapping['section_name'],
                        'status' => $mapping['palate_status']
                    ]
                ]
            ]);
            break;
            
        case 'getPalateLocationMapping':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            if (empty($barcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
                exit;
            }
            
            $sql = "SELECT 
                        plm.palate_barcode,
                        plm.location_barcode,
                        plm.mapped_date,
                        pm.paletteNo,
                        pm.section_name,
                        pm.status as palate_status,
                        lm.locationNo,
                        lm.rack_no,
                        lm.laneNO
                    FROM wms_palate_location_mapping plm
                    JOIN palatteMaster pm ON plm.palate_barcode = pm.paletteNo
                    JOIN locationMaster lm ON plm.location_barcode = lm.locationNo
                    WHERE plm.palate_barcode = ? AND plm.status = 'active'
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Mapping not found']);
                exit;
            }
            
            $mapping = $result->fetch_assoc();
            
            // Get materials on palate (paginated)
            $sqlMaterials = "SELECT 
                                sb.trackingId as tracking_id,
                                sb.material_code,
                                mva.material_name,
                                mva.material_type,
                                sb.batch_no,
                                v.vendor_name,
                                sb.mfg_by as vendor_code,
                                sb.mfg_date,
                                sb.exp_date,
                                sb.total_containers,
                                sb.pack_size,
                                sb.grn_no,
                                sb.grnReceiveBy,
                                sb.grnReceiveOn,
                                cm.receiving_no,
                                cm.received_by,
                                cm.receiving_date,
                                cm.weighing_no,
                                cm.weighing_by,
                                cm.weighing_date,
                                cm.grn_by,
                                cm.grn_date
                            FROM wms_material_palate_mapping mpm
                            JOIN sampling_batches sb ON mpm.material_barcode = sb.trackingId
                            LEFT JOIN my_view_all mva ON sb.material_code = mva.material_code
                            LEFT JOIN vendor v ON sb.mfg_by = v.vendor_no AND sb.plant_id = v.plant_id
                            LEFT JOIN challan_materials cm ON sb.challan_no = cm.challan_no AND sb.ch_no = cm.ch_no
                            WHERE mpm.palate_barcode = ? AND mpm.status = 'active'
                            LIMIT ? OFFSET ?";
            
            $stmtMaterials = $conn->prepare($sqlMaterials);
            $stmtMaterials->bind_param("sii", $barcode, $limit, $offset);
            $stmtMaterials->execute();
            $materialsResult = $stmtMaterials->get_result();
            
            $materials = [];
            while ($row = $materialsResult->fetch_assoc()) {
                $materials[] = $row;
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total 
                        FROM wms_material_palate_mapping 
                        WHERE palate_barcode = ? AND status = 'active'";
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bind_param("s", $barcode);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'mapping' => [
                        'mapped_date' => $mapping['mapped_date'],
                        'palate' => [
                            'paletteNo' => $mapping['paletteNo'],
                            'section_name' => $mapping['section_name'],
                            'status' => $mapping['palate_status']
                        ],
                        'location' => [
                            'locationNo' => $mapping['locationNo'],
                            'section_name' => $mapping['section_name'],
                            'rack_no' => $mapping['rack_no'],
                            'laneNO' => $mapping['laneNO']
                        ]
                    ],
                    'materials' => $materials,
                    'total' => $total
                ]
            ]);
            break;
            
        // ==================== GRAPHICAL VIEW FUNCTIONS ====================
        
        case 'getRackLocationStatus':
            $rackBarcode = isset($_GET['rack_barcode']) ? $_GET['rack_barcode'] : '';
            if (empty($rackBarcode)) {
                echo json_encode(['status' => 'error', 'message' => 'Rack barcode is required']);
                exit;
            }
            
            $sql = "SELECT 
                        lm.locationNo,
                        plm.palate_barcode,
                        COUNT(DISTINCT mpm.material_barcode) as materials_count
                    FROM locationMaster lm
                    LEFT JOIN wms_palate_location_mapping plm ON lm.locationNo = plm.location_barcode AND plm.status = 'active'
                    LEFT JOIN wms_material_palate_mapping mpm ON plm.palate_barcode = mpm.palate_barcode AND mpm.status = 'active'
                    WHERE lm.rack_no = ? AND lm.plant_id = ?
                    GROUP BY lm.locationNo, plm.palate_barcode
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $rackBarcode, $plant_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $locations = [];
            while ($row = $result->fetch_assoc()) {
                $locations[] = [
                    'locationNo' => $row['locationNo'],
                    'palate_barcode' => $row['palate_barcode'],
                    'materials_count' => $row['materials_count'] ?: 0,
                    'occupied' => !empty($row['palate_barcode'])
                ];
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total 
                        FROM locationMaster 
                        WHERE rack_no = ? AND plant_id = ?";
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bind_param("ss", $rackBarcode, $plant_id);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'locations' => $locations,
                    'total' => $total
                ]
            ]);
            break;
            
        case 'getPalateStatus':
            $sectionFilter = isset($_GET['section_name']) ? $_GET['section_name'] : '';
            
            $sql = "SELECT 
                        pm.paletteNo,
                        pm.section_name,
                        pm.status,
                        COALESCE((
                            SELECT COUNT(DISTINCT mpm.material_barcode)
                            FROM wms_material_palate_mapping mpm
                            WHERE mpm.palate_barcode = pm.paletteNo AND mpm.status = 'active'
                        ), 0) as materials_count,
                        COALESCE(plm.location_barcode, '') as location,
                        CASE WHEN (
                            SELECT COUNT(DISTINCT mpm.material_barcode)
                            FROM wms_material_palate_mapping mpm
                            WHERE mpm.palate_barcode = pm.paletteNo AND mpm.status = 'active'
                        ) > 0 THEN 1 ELSE 0 END as has_materials,
                        CASE WHEN plm.location_barcode IS NOT NULL THEN 1 ELSE 0 END as at_location
                    FROM palatteMaster pm
                    LEFT JOIN wms_palate_location_mapping plm ON pm.paletteNo = plm.palate_barcode AND plm.status = 'active'
                    WHERE pm.plant_id = ?";
            
            if (!empty($sectionFilter)) {
                $sql .= " AND pm.section_name = ?";
            }
            
            $sql .= " ORDER BY pm.paletteNo
                     LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            if (!empty($sectionFilter)) {
                $stmt->bind_param("ssii", $plant_id, $sectionFilter, $limit, $offset);
            } else {
                $stmt->bind_param("sii", $plant_id, $limit, $offset);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $palates = [];
            while ($row = $result->fetch_assoc()) {
                $location = trim($row['location']);
                $hasMaterials = ((int)$row['has_materials'] > 0) ? true : false;
                $atLocation = ((int)$row['at_location'] > 0) ? true : false;
                $palates[] = [
                    'paletteNo' => $row['paletteNo'],
                    'section_name' => $row['section_name'],
                    'status' => $row['status'],
                    'materials_count' => (int)$row['materials_count'],
                    'location' => $location ?: null,
                    'has_materials' => $hasMaterials,
                    'at_location' => $atLocation
                ];
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total 
                        FROM palatteMaster pm
                        WHERE pm.plant_id = ?";
            if (!empty($sectionFilter)) {
                $sqlCount .= " AND pm.section_name = ?";
            }
            
            $stmtCount = $conn->prepare($sqlCount);
            if (!empty($sectionFilter)) {
                $stmtCount->bind_param("ss", $plant_id, $sectionFilter);
            } else {
                $stmtCount->bind_param("s", $plant_id);
            }
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'palates' => $palates,
                    'total' => $total
                ]
            ]);
            break;
            
        // ==================== LOGS FUNCTION ====================
        
        case 'getTrackingLogs':
            $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
            $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
            $barcodeType = isset($_GET['barcode_type']) ? $_GET['barcode_type'] : '';
            
            $sql = "SELECT * FROM wms_tracking_logs WHERE 1=1";
            $params = [];
            $types = '';
            
            if (!empty($fromDate)) {
                $sql .= " AND DATE(scanned_date) >= ?";
                $params[] = $fromDate;
                $types .= 's';
            }
            
            if (!empty($toDate)) {
                $sql .= " AND DATE(scanned_date) <= ?";
                $params[] = $toDate;
                $types .= 's';
            }
            
            if (!empty($barcodeType)) {
                $sql .= " AND barcode_type = ?";
                $params[] = $barcodeType;
                $types .= 's';
            }
            
            $sql .= " ORDER BY scanned_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $conn->prepare($sql);
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            
            // Get total count
            $sqlCount = "SELECT COUNT(*) as total FROM wms_tracking_logs WHERE 1=1";
            $countParams = [];
            $countTypes = '';
            
            if (!empty($fromDate)) {
                $sqlCount .= " AND DATE(scanned_date) >= ?";
                $countParams[] = $fromDate;
                $countTypes .= 's';
            }
            
            if (!empty($toDate)) {
                $sqlCount .= " AND DATE(scanned_date) <= ?";
                $countParams[] = $toDate;
                $countTypes .= 's';
            }
            
            if (!empty($barcodeType)) {
                $sqlCount .= " AND barcode_type = ?";
                $countParams[] = $barcodeType;
                $countTypes .= 's';
            }
            
            $stmtCount = $conn->prepare($sqlCount);
            if (!empty($countTypes)) {
                $stmtCount->bind_param($countTypes, ...$countParams);
            }
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'logs' => $logs,
                    'total' => $total
                ]
            ]);
            break;
            
        case 'getLiveStatus':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Get recent activities from tracking logs
            $sql = "SELECT 
                        barcode_scanned,
                        barcode_type,
                        action,
                        details,
                        scanned_date,
                        scanned_by
                    FROM wms_tracking_logs 
                    WHERE action IN ('map', 'empty')
                    AND scanned_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY scanned_date DESC 
                    LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $timeAgo = getTimeAgo($row['scanned_date']);
                $title = '';
                $detailsText = '';
                
                $detailsLower = strtolower($row['details']);
                
                if ($row['action'] === 'map') {
                    if (strpos($detailsLower, 'palate') !== false && $row['barcode_type'] === 'material') {
                        // Material mapped to palate
                        $palateBarcode = '';
                        if (preg_match('/palate:\s*([^\s,]+)/i', $row['details'], $matches)) {
                            $palateBarcode = $matches[1];
                        } else if (strpos($row['details'], ':') !== false) {
                            $palateBarcode = trim(substr($row['details'], strpos($row['details'], ':') + 1));
                        }
                        $title = 'Material Mapped to Palate';
                        $detailsText = 'Material ' . $row['barcode_scanned'] . ' → Palate ' . ($palateBarcode ?: 'N/A');
                    } else if (strpos($detailsLower, 'location') !== false && $row['barcode_type'] === 'palate') {
                        // Palate mapped to location
                        $locationBarcode = '';
                        if (preg_match('/location:\s*([^\s,]+)/i', $row['details'], $matches)) {
                            $locationBarcode = $matches[1];
                        } else if (strpos($row['details'], ':') !== false) {
                            $locationBarcode = trim(substr($row['details'], strpos($row['details'], ':') + 1));
                        }
                        $title = 'Palate Mapped to Location';
                        $detailsText = 'Palate ' . $row['barcode_scanned'] . ' → Location ' . ($locationBarcode ?: 'N/A');
                    } else {
                        $title = 'Mapping Created';
                        $detailsText = $row['details'] ?: 'New mapping created';
                    }
                } else if ($row['action'] === 'empty') {
                    if (strpos($detailsLower, 'palate') !== false && $row['barcode_type'] === 'material') {
                        $title = 'Material Removed from Palate';
                        $detailsText = 'Material ' . $row['barcode_scanned'] . ' removed from palate';
                    } else if (strpos($detailsLower, 'location') !== false && $row['barcode_type'] === 'palate') {
                        $title = 'Palate Removed from Location';
                        $detailsText = 'Palate ' . $row['barcode_scanned'] . ' removed from location';
                    } else {
                        $title = 'Mapping Removed';
                        $detailsText = $row['details'] ?: 'Mapping removed';
                    }
                }
                
                $activities[] = [
                    'title' => $title,
                    'details' => $detailsText,
                    'time' => $timeAgo,
                    'action' => $row['action'],
                    'barcode_type' => $row['barcode_type'],
                    'barcode_scanned' => $row['barcode_scanned'],
                    'scanned_by' => $row['scanned_by']
                ];
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'activities' => $activities
                ]
            ]);
            break;
            
        case 'getWMSStats':
            // Get total materials count
            $sqlMaterials = "SELECT COUNT(DISTINCT trackingId) as total 
                            FROM sampling_batches 
                            WHERE plant_id = ?";
            $stmtMaterials = $conn->prepare($sqlMaterials);
            $stmtMaterials->bind_param("s", $plant_id);
            $stmtMaterials->execute();
            $materialsResult = $stmtMaterials->get_result();
            $totalMaterials = $materialsResult->fetch_assoc()['total'];
            
            // Get total palates count
            $sqlPalates = "SELECT COUNT(*) as total 
                          FROM palatteMaster 
                          WHERE plant_id = ?";
            $stmtPalates = $conn->prepare($sqlPalates);
            $stmtPalates->bind_param("s", $plant_id);
            $stmtPalates->execute();
            $palatesResult = $stmtPalates->get_result();
            $totalPalates = $palatesResult->fetch_assoc()['total'];
            
            // Get total locations count
            $sqlLocations = "SELECT COUNT(*) as total 
                            FROM locationMaster 
                            WHERE plant_id = ?";
            $stmtLocations = $conn->prepare($sqlLocations);
            $stmtLocations->bind_param("s", $plant_id);
            $stmtLocations->execute();
            $locationsResult = $stmtLocations->get_result();
            $totalLocations = $locationsResult->fetch_assoc()['total'];
            
            // Get active mappings count
            $sqlMappings = "SELECT 
                            (SELECT COUNT(*) FROM wms_material_palate_mapping WHERE status = 'active') +
                            (SELECT COUNT(*) FROM wms_palate_location_mapping WHERE status = 'active') as total";
            $stmtMappings = $conn->prepare($sqlMappings);
            $stmtMappings->execute();
            $mappingsResult = $stmtMappings->get_result();
            $activeMappings = $mappingsResult->fetch_assoc()['total'];
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'totalMaterials' => (int)$totalMaterials,
                    'totalPalates' => (int)$totalPalates,
                    'totalLocations' => (int)$totalLocations,
                    'activeMappings' => (int)$activeMappings
                ]
            ]);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Helper function to get time ago
function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $currentTime = time();
    $diff = $currentTime - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } else if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } else if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('d M Y', $timestamp);
    }
}

// Helper function to log tracking actions
function logTrackingAction($conn, $barcode, $barcodeType, $action, $details) {
    try {
        $scannedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
        $scannedDate = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO wms_tracking_logs 
                (barcode_scanned, barcode_type, scanned_by, scanned_date, action, details) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssss", $barcode, $barcodeType, $scannedBy, $scannedDate, $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error logging tracking action: " . $e->getMessage());
    }
}

$conn->close();

?>
