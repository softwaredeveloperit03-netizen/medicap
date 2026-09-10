<?php
/**
 * One-shot production helper: ensure master tables + seed chemicals / glassware.
 * GET: ?key=MedicapSeed1126&plant_id=1126&do=schema|chemicals|glasswares|all
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'MedicapSeed1126') {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    exit;
}

$_GET['plant_id'] = isset($_GET['plant_id']) ? $_GET['plant_id'] : '1126';
$_GET['emp_id'] = 'master';
$_GET['user_no'] = 'GMP22052';
$_GET['department'] = 'QC';
$_GET['token'] = 'deploy';
$_GET['description'] = 'deploy_seed_masters';

try {
require_once __DIR__ . '/db.config.php';
$cfg = cyclone_get_db_config();
$conn = new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], cyclone_resolve_dbname($_GET['plant_id']));
if ($conn->connect_error) {
    throw new Exception('DB connect failed: '.$conn->connect_error);
}
require_once __DIR__ . '/schema_tables.php';
require_once __DIR__ . '/qc/standard_chemicals_seed.php';
require_once __DIR__ . '/qc/standard_glasswares_seed.php';
require_once __DIR__ . '/qc/seed_om_helper.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('status' => 'error', 'phase' => 'bootstrap', 'message' => $e->getMessage()));
    exit;
}

$do = isset($_GET['do']) ? $_GET['do'] : 'all';
$plant = $conn->real_escape_string($_GET['plant_id']);
$empId = 'master';
$userNo = 'GMP22052';
$entry_date = date('Y-m-d H:i:s');
$out = array('status' => 'success', 'plant_id' => $plant, 'steps' => array());

$out['steps']['schema'] = medicap_ensure_schema($conn);

function medicap_deploy_seed_chemicals($conn, $plant, $empId, $userNo, $entry_date) {
    $chemicals = medicap_standard_chemicals_seed();
    $chemCols = array();
    $colRes = $conn->query("SHOW COLUMNS FROM chemical");
    if ($colRes) {
        while ($col = $colRes->fetch_assoc()) {
            $chemCols[$col['Field']] = true;
        }
    }
    $omCols = array();
    $omColRes = $conn->query("SHOW COLUMNS FROM others_material");
    if ($omColRes) {
        while ($col = $omColRes->fetch_assoc()) {
            $omCols[$col['Field']] = true;
        }
    }
    $added = 0;
    $skipped = 0;
    $errors = array();
    $seq = 1;
    $maxSql = $conn->query("SELECT chemical_no FROM chemical WHERE plant_id='".$plant."' AND chemical_no LIKE 'CHM-%' ORDER BY id DESC LIMIT 1");
    if ($maxSql && $maxSql->num_rows > 0) {
        $lastNo = $maxSql->fetch_assoc();
        if (preg_match('/CHM-(\d+)/', $lastNo['chemical_no'], $m)) {
            $seq = intval($m[1]) + 1;
        }
    }
    $hasAutoInc = false;
    $aiRes = $conn->query("SHOW COLUMNS FROM chemical WHERE Field='id' AND Extra LIKE '%auto_increment%'");
    $hasAutoInc = ($aiRes && $aiRes->num_rows > 0);
    $nextId = 1;
    $idRes = $conn->query("SELECT COALESCE(MAX(id),0)+1 AS next_id FROM chemical");
    if ($idRes && ($idRow = $idRes->fetch_assoc())) {
        $nextId = intval($idRow['next_id']);
    }

    foreach ($chemicals as $chem) {
        $name = isset($chem['chemical_name']) ? trim($chem['chemical_name']) : '';
        if ($name === '') continue;
        $nameEsc = $conn->real_escape_string($name);
        $check = $conn->query("SELECT id FROM chemical WHERE plant_id='".$plant."' AND chemical_name='".$nameEsc."' LIMIT 1");
        if ($check && $check->num_rows > 0) { $skipped++; continue; }
        $mw = $conn->real_escape_string(isset($chem['molecular_wt']) ? $chem['molecular_wt'] : '');
        $cas = $conn->real_escape_string(isset($chem['cas_name']) ? $chem['cas_name'] : '');
        $grade = $conn->real_escape_string(isset($chem['grade']) ? $chem['grade'] : 'AR');
        $unit = $conn->real_escape_string(isset($chem['unit']) ? $chem['unit'] : 'g');
        $chemType = $conn->real_escape_string(isset($chem['chem_type']) ? $chem['chem_type'] : 'Chemical');
        $chemNo = 'CHM-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        $seq++;
        $fields = array('plant_id','user_no','chemical_no','chemical_name','molecular_wt','pack_size','cas_name','grade','make','unit','entry_by','entry_date','status','approve_by','approve_date');
        $values = array("'".$plant."'","'".$userNo."'","'".$chemNo."'","'".$nameEsc."'","'".$mw."'","'[]'","'".$cas."'","'".$grade."'","'[]'","'".$unit."'","'".$empId."'","'".$entry_date."'","'approve'","'".$empId."'","'".$entry_date."'");
        if (isset($chemCols['chem_type'])) {
            array_splice($fields, 2, 0, array('chem_type'));
            array_splice($values, 2, 0, array("'".$chemType."'"));
        }
        if (!$hasAutoInc && isset($chemCols['id'])) {
            array_unshift($fields, 'id');
            array_unshift($values, "'".$nextId."'");
            $nextId++;
        }
        $useFields = array(); $useValues = array();
        for ($i = 0; $i < count($fields); $i++) {
            if (isset($chemCols[$fields[$i]])) { $useFields[] = $fields[$i]; $useValues[] = $values[$i]; }
        }
        if ($conn->query("INSERT INTO chemical (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")")) {
            $added++;
            $code = $chemNo;
            $newId = $conn->insert_id;
            if ($newId) {
                $cr = $conn->query("SELECT chemical_no FROM chemical WHERE id='".$newId."' LIMIT 1");
                if ($cr && ($crow = $cr->fetch_assoc()) && !empty($crow['chemical_no'])) $code = $crow['chemical_no'];
            }
            $codeEsc = $conn->real_escape_string($code);
            if (isset($omCols['material_subtype']) && function_exists('medicap_others_material_insert')) {
                $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Chemicals' AND (material_code='".$codeEsc."' OR material_name='".$nameEsc."') LIMIT 1");
                if (!$omCheck || $omCheck->num_rows === 0) {
                    @medicap_others_material_insert($conn, array(
                        'plant_id' => "'".$plant."'",
                        'material_type' => "'QC Material'",
                        'material_subtype' => "'Chemicals'",
                        'material_code' => "'".$codeEsc."'",
                        'material_name' => "'".$nameEsc."'",
                        'unit' => "'".$unit."'",
                        'grade' => "'".$grade."'",
                        'cas_no' => "'".$cas."'",
                        'cas_name' => "'".$cas."'",
                        'status' => "'Approved'",
                        'entry_by' => "'".$empId."'",
                        'entry_date' => "'".$entry_date."'",
                        'description' => "''",
                        'composition' => "''"
                    ));
                }
            }
        } else {
            $errors[] = $name.': '.$conn->error;
        }
    }
    return array('added' => $added, 'skipped' => $skipped, 'errors' => $errors, 'total_seed' => count($chemicals));
}

function medicap_deploy_seed_glasswares($conn, $plant, $empId, $userNo, $entry_date) {
    $items = medicap_standard_glasswares_seed();
    $gCols = array();
    $colRes = $conn->query("SHOW COLUMNS FROM glassware");
    if ($colRes) {
        while ($col = $colRes->fetch_assoc()) { $gCols[$col['Field']] = true; }
    }
    $omCols = array();
    $omColRes = $conn->query("SHOW COLUMNS FROM others_material");
    if ($omColRes) {
        while ($col = $omColRes->fetch_assoc()) { $omCols[$col['Field']] = true; }
    }
    $added = 0; $skipped = 0; $errors = array(); $seq = 1;
    $maxSql = $conn->query("SELECT glassware_no FROM glassware WHERE plant_id='".$plant."' AND glassware_no LIKE 'GW-%' ORDER BY id DESC LIMIT 1");
    if ($maxSql && $maxSql->num_rows > 0) {
        $lastNo = $maxSql->fetch_assoc();
        if (preg_match('/GW-(\d+)/', $lastNo['glassware_no'], $m)) $seq = intval($m[1]) + 1;
    }
    foreach ($items as $gw) {
        $name = isset($gw['name']) ? trim($gw['name']) : '';
        $capacity = isset($gw['capacity']) ? trim($gw['capacity']) : '';
        if ($name === '') continue;
        $nameEsc = $conn->real_escape_string($name);
        $capEsc = $conn->real_escape_string($capacity);
        $check = $conn->query("SELECT id FROM glassware WHERE plant_id='".$plant."' AND name='".$nameEsc."' AND capacity='".$capEsc."' LIMIT 1");
        if ($check && $check->num_rows > 0) { $skipped++; continue; }
        $unit = $conn->real_escape_string(isset($gw['unit']) ? $gw['unit'] : 'ml');
        $gclass = $conn->real_escape_string(isset($gw['glassware_class']) ? $gw['glassware_class'] : 'type A');
        $desc = $conn->real_escape_string(isset($gw['description']) ? $gw['description'] : '');
        $make = $conn->real_escape_string(isset($gw['make']) ? $gw['make'] : 'Borosil');
        $gwNo = 'GW-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        $seq++;
        $fields = array('plant_id','user_no','glassware_no','name','capacity','unit','glassware_class','description','make','entry_by','entry_date','status','approve_by','approve_date','coa');
        $values = array("'".$plant."'","'".$userNo."'","'".$gwNo."'","'".$nameEsc."'","'".$capEsc."'","'".$unit."'","'".$gclass."'","'".$desc."'","'".$make."'","'".$empId."'","'".$entry_date."'","'approve'","'".$empId."'","'".$entry_date."'","'0'");
        $useFields = array(); $useValues = array();
        for ($i = 0; $i < count($fields); $i++) {
            if (isset($gCols[$fields[$i]])) { $useFields[] = $fields[$i]; $useValues[] = $values[$i]; }
        }
        if ($conn->query("INSERT INTO glassware (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")")) {
            $added++;
            $code = $gwNo;
            $newId = $conn->insert_id;
            if ($newId) {
                $cr = $conn->query("SELECT glassware_no FROM glassware WHERE id='".$newId."' LIMIT 1");
                if ($cr && ($crow = $cr->fetch_assoc()) && !empty($crow['glassware_no'])) $code = $crow['glassware_no'];
            }
            $codeEsc = $conn->real_escape_string($code);
            if (isset($omCols['material_subtype']) && function_exists('medicap_others_material_insert')) {
                $matName = $nameEsc.($capacity !== '' ? ' '.$capEsc.' '.$unit : '');
                $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Glassware' AND (material_code='".$codeEsc."' OR material_name='".$matName."') LIMIT 1");
                if (!$omCheck || $omCheck->num_rows === 0) {
                    @medicap_others_material_insert($conn, array(
                        'plant_id' => "'".$plant."'",
                        'material_type' => "'QC Material'",
                        'material_subtype' => "'Glassware'",
                        'material_code' => "'".$codeEsc."'",
                        'material_name' => "'".$matName."'",
                        'unit' => "'".$unit."'",
                        'grade' => "'".$gclass."'",
                        'status' => "'Approved'",
                        'entry_by' => "'".$empId."'",
                        'entry_date' => "'".$entry_date."'",
                        'description' => "'".$desc."'",
                        'composition' => "''"
                    ));
                }
            }
        } else {
            $errors[] = $name.' '.$capacity.': '.$conn->error;
        }
    }
    return array('added' => $added, 'skipped' => $skipped, 'errors' => $errors, 'total_seed' => count($items));
}

try {
    if ($do === 'chemicals' || $do === 'all') {
        $out['steps']['chemicals'] = medicap_deploy_seed_chemicals($conn, $plant, $empId, $userNo, $entry_date);
    }
    if ($do === 'glasswares' || $do === 'all') {
        $out['steps']['glasswares'] = medicap_deploy_seed_glasswares($conn, $plant, $empId, $userNo, $entry_date);
    }
    echo json_encode($out, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('status' => 'error', 'phase' => 'seed', 'message' => $e->getMessage(), 'partial' => $out));
}
$conn->close();
