<?php
/**
 * One-shot: seed standard HPLC/GC columns for plant 1126.
 * GET: ?key=MedicapSeed1126&plant_id=1126
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('max_execution_time', '300');

$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'MedicapSeed1126') {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    exit;
}

$plant = isset($_GET['plant_id']) ? $_GET['plant_id'] : '1126';
$entry_date = date('Y-m-d H:i:s');

try {
    require_once __DIR__ . '/db.config.php';
    $cfg = cyclone_get_db_config();
    $conn = new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], cyclone_resolve_dbname($plant));
    if ($conn->connect_error) {
        throw new Exception('DB connect failed: '.$conn->connect_error);
    }
    require_once __DIR__ . '/qc/standard_hplc_columns_seed.php';

    $conn->query("CREATE TABLE IF NOT EXISTS hplc_gc_column_master (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        column_no VARCHAR(30) DEFAULT NULL,
        plant_id TEXT DEFAULT NULL,
        technique VARCHAR(20) DEFAULT NULL,
        column_name TEXT DEFAULT NULL,
        usp_l_code TEXT DEFAULT NULL,
        pharmacopoeia_reference TEXT DEFAULT NULL,
        stationary_phase TEXT DEFAULT NULL,
        dimensions_mm TEXT DEFAULT NULL,
        particle_size_um TEXT DEFAULT NULL,
        pore_size_a TEXT DEFAULT NULL,
        end_capped TEXT DEFAULT NULL,
        manufacturer TEXT DEFAULT NULL,
        catalog_no TEXT DEFAULT NULL,
        status TEXT DEFAULT NULL,
        entry_by TEXT DEFAULT NULL,
        entry_date TEXT DEFAULT NULL,
        approve_by TEXT DEFAULT NULL,
        approve_date TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

    $columns = medicap_standard_hplc_columns_seed();
    $plantEsc = $conn->real_escape_string($plant);
    $entryBy = 'Master';
    $added = 0;
    $skipped = 0;
    $errors = array();

    foreach ($columns as $col) {
        $technique = $conn->real_escape_string(isset($col['technique']) ? $col['technique'] : 'HPLC');
        $column_name = isset($col['column_name']) ? trim($col['column_name']) : '';
        if ($column_name === '') {
            continue;
        }
        $nameEsc = $conn->real_escape_string($column_name);
        $dimEsc = $conn->real_escape_string(isset($col['dimensions_mm']) ? $col['dimensions_mm'] : '');
        $mfrEsc = $conn->real_escape_string(isset($col['manufacturer']) ? $col['manufacturer'] : '');
        $check = $conn->query(
            "SELECT id FROM hplc_gc_column_master
             WHERE plant_id='".$plantEsc."'
               AND column_name='".$nameEsc."'
               AND IFNULL(dimensions_mm,'')='".$dimEsc."'
               AND IFNULL(manufacturer,'')='".$mfrEsc."'
             LIMIT 1"
        );
        if ($check && $check->num_rows > 0) {
            $skipped++;
            continue;
        }
        $usp = $conn->real_escape_string(isset($col['usp_l_code']) ? $col['usp_l_code'] : '');
        $pharma = $conn->real_escape_string(isset($col['pharmacopoeia_reference']) ? $col['pharmacopoeia_reference'] : '');
        $phase = $conn->real_escape_string(isset($col['stationary_phase']) ? $col['stationary_phase'] : '');
        $ps = $conn->real_escape_string(isset($col['particle_size_um']) ? $col['particle_size_um'] : '');
        $pore = $conn->real_escape_string(isset($col['pore_size_a']) ? $col['pore_size_a'] : '');
        $end = $conn->real_escape_string(isset($col['end_capped']) ? $col['end_capped'] : '');
        $cat = $conn->real_escape_string(isset($col['catalog_no']) ? $col['catalog_no'] : '');

        $sql = "INSERT INTO hplc_gc_column_master
            (plant_id, technique, column_name, usp_l_code, pharmacopoeia_reference, stationary_phase, dimensions_mm, particle_size_um, pore_size_a, end_capped, manufacturer, catalog_no, status, entry_by, entry_date, approve_by, approve_date)
            VALUES
            ('".$plantEsc."', '".$technique."', '".$nameEsc."', '".$usp."', '".$pharma."', '".$phase."', '".$dimEsc."', '".$ps."', '".$pore."', '".$end."', '".$mfrEsc."', '".$cat."', 'Approved', '".$entryBy."', '".$entry_date."', '".$entryBy."', '".$entry_date."')";
        if ($conn->query($sql)) {
            $newId = $conn->insert_id;
            $columnNo = 'HGC' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);
            $conn->query("UPDATE hplc_gc_column_master SET column_no='".$columnNo."' WHERE id='".$newId."'");
            $added++;
        } else {
            $errors[] = $column_name.': '.$conn->error;
        }
    }

    $countRes = $conn->query("SELECT COUNT(*) AS c FROM hplc_gc_column_master WHERE plant_id='".$plantEsc."'");
    $total = ($countRes && ($r = $countRes->fetch_assoc())) ? intval($r['c']) : 0;

    echo json_encode(array(
        'status' => 'success',
        'plant_id' => $plant,
        'added' => $added,
        'skipped' => $skipped,
        'total' => $total,
        'seed_count' => count($columns),
        'errors' => $errors
    ), JSON_PRETTY_PRINT);
    $conn->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
