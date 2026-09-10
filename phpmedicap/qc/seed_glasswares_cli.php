<?php
/**
 * CLI: seed standard laboratory glassware into Glassware Master.
 * Usage:
 *   php seed_glasswares_cli.php [plant_id]
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$_GET['plant_id'] = isset($argv[1]) ? $argv[1] : '1126';
$_GET['type'] = 'seedStandardGlasswares';
$_GET['token'] = 'cli';
$_GET['description'] = 'seed_glasswares_cli';
$_GET['emp_id'] = 'master';
$_GET['department'] = 'QC';
$_GET['user_no'] = 'GMP22052';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/standard_glasswares_seed.php';

$plant = $conn->real_escape_string($_GET['plant_id']);
$empId = 'master';
$userNo = $conn->real_escape_string($_GET['user_no']);
$items = medicap_standard_glasswares_seed();
$entry_date = date('Y-m-d H:i:s');

$conn->query("CREATE TABLE IF NOT EXISTS glassware (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_no text DEFAULT NULL,
    glassware_no text DEFAULT NULL,
    name text DEFAULT NULL,
    capacity text DEFAULT NULL,
    unit text DEFAULT NULL,
    glassware_class text DEFAULT NULL,
    description text DEFAULT NULL,
    make text DEFAULT NULL,
    gst text DEFAULT NULL,
    hsn text DEFAULT NULL,
    status text DEFAULT 'pending',
    entry_by text DEFAULT NULL,
    entry_date text DEFAULT NULL,
    approve_by text DEFAULT NULL,
    approve_date text DEFAULT NULL,
    plant_id text DEFAULT NULL,
    coa text DEFAULT '0',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

$gCols = array();
$colRes = $conn->query("SHOW COLUMNS FROM glassware");
if ($colRes) {
    while ($col = $colRes->fetch_assoc()) {
        $gCols[$col['Field']] = true;
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
$maxSql = $conn->query("SELECT glassware_no FROM glassware WHERE plant_id='".$plant."' AND glassware_no LIKE 'GW-%' ORDER BY id DESC LIMIT 1");
if ($maxSql && $maxSql->num_rows > 0) {
    $lastNo = $maxSql->fetch_assoc();
    if (preg_match('/GW-(\d+)/', $lastNo['glassware_no'], $m)) {
        $seq = intval($m[1]) + 1;
    }
}

foreach ($items as $gw) {
    $name = isset($gw['name']) ? trim($gw['name']) : '';
    $capacity = isset($gw['capacity']) ? trim($gw['capacity']) : '';
    if ($name === '') {
        continue;
    }
    $nameEsc = $conn->real_escape_string($name);
    $capEsc = $conn->real_escape_string($capacity);
    $check = $conn->query("SELECT id FROM glassware WHERE plant_id='".$plant."' AND name='".$nameEsc."' AND capacity='".$capEsc."' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $skipped++;
        continue;
    }
    $unit = $conn->real_escape_string(isset($gw['unit']) ? $gw['unit'] : 'ml');
    $gclass = $conn->real_escape_string(isset($gw['glassware_class']) ? $gw['glassware_class'] : 'type A');
    $desc = $conn->real_escape_string(isset($gw['description']) ? $gw['description'] : '');
    $make = $conn->real_escape_string(isset($gw['make']) ? $gw['make'] : 'Borosil');
    $gwNo = 'GW-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    $seq++;

    $fields = array('plant_id', 'user_no', 'glassware_no', 'name', 'capacity', 'unit', 'glassware_class', 'description', 'make', 'entry_by', 'entry_date', 'status', 'approve_by', 'approve_date', 'coa');
    $values = array("'".$plant."'", "'".$userNo."'", "'".$gwNo."'", "'".$nameEsc."'", "'".$capEsc."'", "'".$unit."'", "'".$gclass."'", "'".$desc."'", "'".$make."'", "'".$empId."'", "'".$entry_date."'", "'approve'", "'".$empId."'", "'".$entry_date."'", "'0'");
    $useFields = array();
    $useValues = array();
    for ($fi = 0; $fi < count($fields); $fi++) {
        if (isset($gCols[$fields[$fi]])) {
            $useFields[] = $fields[$fi];
            $useValues[] = $values[$fi];
        }
    }
    $sql = "INSERT INTO glassware (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")";
    if ($conn->query($sql)) {
        $newId = $conn->insert_id;
        $code = $gwNo;
        if ($newId) {
            $codeRes = $conn->query("SELECT glassware_no FROM glassware WHERE id='".$newId."' LIMIT 1");
            if ($codeRes && ($codeRow = $codeRes->fetch_assoc()) && !empty($codeRow['glassware_no'])) {
                $code = $codeRow['glassware_no'];
            }
        }
        $codeEsc = $conn->real_escape_string($code);
        $added++;
        if (isset($omCols['material_subtype'])) {
            $matName = $nameEsc.($capacity !== '' ? ' '.$capEsc.' '.$unit : '');
            $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Glassware' AND (material_code='".$codeEsc."' OR material_name='".$matName."') LIMIT 1");
            if (!$omCheck || $omCheck->num_rows === 0) {
                $omFields = array();
                $omValues = array();
                $omMap = array(
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
                    'description' => "'".$desc."'"
                );
                foreach ($omMap as $of => $ov) {
                    if (isset($omCols[$of])) {
                        $omFields[] = $of;
                        $omValues[] = $ov;
                    }
                }
                if (count($omFields) > 0) {
                    $conn->query("INSERT INTO others_material (".implode(',', $omFields).") VALUES (".implode(',', $omValues).")");
                }
            }
        }
    } else {
        $errors[] = $name.' '.$capacity.': '.$conn->error;
    }
}

echo json_encode(array(
    'status' => 'success',
    'plant_id' => $plant,
    'added' => $added,
    'skipped' => $skipped,
    'errors' => $errors,
    'total_seed' => count($items),
), JSON_PRETTY_PRINT) . PHP_EOL;

$conn->close();
