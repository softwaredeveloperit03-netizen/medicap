<?php
/**
 * CLI: seed standard QC lab chemicals into Chemical Master.
 * Usage:
 *   php seed_chemicals_cli.php [plant_id]
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$_GET['plant_id'] = isset($argv[1]) ? $argv[1] : '1126';
$_GET['type'] = 'seedStandardChemicals';
$_GET['token'] = 'cli';
$_GET['description'] = 'seed_chemicals_cli';
$_GET['emp_id'] = 'master';
$_GET['department'] = 'QC';
$_GET['user_no'] = 'GMP22052';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/standard_chemicals_seed.php';

$plant = $conn->real_escape_string($_GET['plant_id']);
$empId = 'master';
$userNo = $conn->real_escape_string($_GET['user_no']);
$chemicals = medicap_standard_chemicals_seed();
$entry_date = date('Y-m-d H:i:s');
// Reuse the same API handler path by simulating a POST body.
$input = array(
    'masterUserName' => 'Master User',
    'chemicals' => $chemicals,
);
$GLOBALS['__seed_cli_input'] = $input;

// Inline seed (mirrors seedStandardChemicals) so CLI works without token.
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
$nextId = 1;
$idRes = $conn->query("SELECT COALESCE(MAX(id),0)+1 AS next_id FROM chemical");
if ($idRes && ($idRow = $idRes->fetch_assoc())) {
    $nextId = intval($idRow['next_id']);
}
$hasAutoInc = false;
if (isset($chemCols['id'])) {
    $aiRes = $conn->query("SHOW COLUMNS FROM chemical WHERE Field='id' AND Extra LIKE '%auto_increment%'");
    $hasAutoInc = ($aiRes && $aiRes->num_rows > 0);
}
$maxSql = $conn->query("SELECT chemical_no FROM chemical WHERE plant_id='".$plant."' AND chemical_no LIKE 'CHM-%' ORDER BY id DESC LIMIT 1");
if ($maxSql && $maxSql->num_rows > 0) {
    $lastNo = $maxSql->fetch_assoc();
    if (preg_match('/CHM-(\d+)/', $lastNo['chemical_no'], $m)) {
        $seq = intval($m[1]) + 1;
    }
}

foreach ($chemicals as $chem) {
    $name = isset($chem['chemical_name']) ? trim($chem['chemical_name']) : '';
    if ($name === '') {
        continue;
    }
    $nameEsc = $conn->real_escape_string($name);
    $check = $conn->query("SELECT id FROM chemical WHERE plant_id='".$plant."' AND chemical_name='".$nameEsc."' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $skipped++;
        continue;
    }
    $mw = $conn->real_escape_string(isset($chem['molecular_wt']) ? $chem['molecular_wt'] : '');
    $cas = $conn->real_escape_string(isset($chem['cas_name']) ? $chem['cas_name'] : '');
    $grade = $conn->real_escape_string(isset($chem['grade']) ? $chem['grade'] : 'AR');
    $unit = $conn->real_escape_string(isset($chem['unit']) ? $chem['unit'] : 'g');
    $chemType = $conn->real_escape_string(isset($chem['chem_type']) ? $chem['chem_type'] : 'Chemical');
    $chemNo = 'CHM-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    $seq++;
    $makeJson = '[]';
    $fields = array('plant_id', 'user_no', 'chemical_no', 'chemical_name', 'molecular_wt', 'pack_size', 'cas_name', 'grade', 'make', 'unit', 'entry_by', 'entry_date', 'status', 'approve_by', 'approve_date');
    $values = array("'".$plant."'", "'".$userNo."'", "'".$chemNo."'", "'".$nameEsc."'", "'".$mw."'", "'[]'", "'".$cas."'", "'".$grade."'", "'".$makeJson."'", "'".$unit."'", "'".$empId."'", "'".$entry_date."'", "'approve'", "'".$empId."'", "'".$entry_date."'");
    if (isset($chemCols['chem_type'])) {
        array_splice($fields, 2, 0, array('chem_type'));
        array_splice($values, 2, 0, array("'".$chemType."'"));
    }
    if (!$hasAutoInc && isset($chemCols['id'])) {
        array_unshift($fields, 'id');
        array_unshift($values, "'".$nextId."'");
        $nextId++;
    }
    $useFields = array();
    $useValues = array();
    for ($fi = 0; $fi < count($fields); $fi++) {
        if (isset($chemCols[$fields[$fi]])) {
            $useFields[] = $fields[$fi];
            $useValues[] = $values[$fi];
        }
    }
    $sql = "INSERT INTO chemical (".implode(',', $useFields).") VALUES (".implode(',', $useValues).")";
    if ($conn->query($sql)) {
        $added++;
        if (isset($omCols['material_subtype'])) {
            $omCheck = $conn->query("SELECT id FROM others_material WHERE plant_id='".$plant."' AND material_subtype='Chemicals' AND (material_code='".$chemNo."' OR material_name='".$nameEsc."') LIMIT 1");
            if (!$omCheck || $omCheck->num_rows === 0) {
                $omFields = array();
                $omValues = array();
                $omMap = array(
                    'plant_id' => "'".$plant."'",
                    'material_type' => "'QC Material'",
                    'material_subtype' => "'Chemicals'",
                    'material_code' => "'".$chemNo."'",
                    'material_name' => "'".$nameEsc."'",
                    'unit' => "'".$unit."'",
                    'grade' => "'".$grade."'",
                    'cas_no' => "'".$cas."'",
                    'cas_name' => "'".$cas."'",
                    'status' => "'Approved'",
                    'entry_by' => "'".$empId."'",
                    'entry_date' => "'".$entry_date."'"
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
        $errors[] = $name.': '.$conn->error;
    }
}

echo json_encode(array(
    'status' => 'success',
    'plant_id' => $plant,
    'added' => $added,
    'skipped' => $skipped,
    'errors' => $errors,
    'total_seed' => count($chemicals),
), JSON_PRETTY_PRINT) . PHP_EOL;

$conn->close();
