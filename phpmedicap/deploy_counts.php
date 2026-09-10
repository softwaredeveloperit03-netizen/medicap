<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.config.php';
$cfg = cyclone_get_db_config();
$plant = isset($_GET['plant_id']) ? $_GET['plant_id'] : '1126';
$conn = new mysqli($cfg['servername'], $cfg['username'], $cfg['password'], cyclone_resolve_dbname($plant));
if ($conn->connect_error) {
    echo json_encode(array('ok' => false, 'error' => $conn->connect_error));
    exit;
}
$out = array('ok' => true, 'plant_id' => $plant);
foreach (array(
    'chemical' => "SELECT COUNT(*) c FROM chemical WHERE plant_id='".$conn->real_escape_string($plant)."'",
    'glassware' => "SELECT COUNT(*) c FROM glassware WHERE plant_id='".$conn->real_escape_string($plant)."'",
    'om_chemicals' => "SELECT COUNT(*) c FROM others_material WHERE plant_id='".$conn->real_escape_string($plant)."' AND material_subtype='Chemicals'",
    'om_glassware' => "SELECT COUNT(*) c FROM others_material WHERE plant_id='".$conn->real_escape_string($plant)."' AND material_subtype='Glassware'",
) as $k => $sql) {
    $r = $conn->query($sql);
    $out[$k] = ($r && ($row = $r->fetch_assoc())) ? intval($row['c']) : null;
}
echo json_encode($out, JSON_PRETTY_PRINT);
$conn->close();
