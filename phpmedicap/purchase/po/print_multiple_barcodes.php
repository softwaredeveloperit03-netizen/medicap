<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

    require '../../db.php';
    require '../../tcpdf/tcpdf_barcodes_1d.php';

$output = [];

// ---------------- FETCH DATA ----------------
if($_GET["isView"] == 'Lanes'){
    $sql = "SELECT laneNO as barcodeUniqueId FROM laneMaster 
            WHERE plant_id = '{$_GET["plant_id"]}' 
            AND section_name = '{$_GET["section_name"]}' 
            AND status = 'Active'";
}
else if($_GET["isView"] == 'Racks'){
    $sql = "SELECT rack_no as barcodeUniqueId FROM rack 
            WHERE plant_id = '{$_GET["plant_id"]}' 
            AND section_name = '{$_GET["section_name"]}' 
            AND laneNO = '{$_GET["laneNO"]}' 
            AND status = 'Approved'";
}
else if($_GET["isView"] == 'Locations'){
    $sql = "SELECT locationNo as barcodeUniqueId FROM locationMaster 
            WHERE plant_id = '{$_GET["plant_id"]}' 
            AND section_name = '{$_GET["section_name"]}' 
            AND laneNO = '{$_GET["laneNO"]}' 
            AND rack_no = '{$_GET["rack_no"]}' 
            AND status = 'Active'";
}
else if($_GET["isView"] == 'Palettes'){
    $sql = "SELECT paletteNo as barcodeUniqueId FROM palatteMaster 
            WHERE plant_id = '{$_GET["plant_id"]}' 
            AND section_name = '{$_GET["section_name"]}' 
            AND status = 'Active'";
}

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $output[] = $row['barcodeUniqueId'];
}

// ---------------- BARCODE SIZE SETTINGS ----------------
$barcodeWidth  = 220;   // px
$barcodeHeight = 60;    // px
$gap = 10;              // spacing

// How many barcodes fit per row (auto)
$pageWidth = 1000; // browser printable width approx
$perRow = floor($pageWidth / ($barcodeWidth + $gap));
if($perRow < 1) $perRow = 1;

// ---------------- GENERATE BARCODE IMAGES ----------------
function generateBarcode($text){
    $barcodeobj = new TCPDFBarcode($text, 'C128');
    $imageData = $barcodeobj->getBarcodePngData(2, 60);
    return base64_encode($imageData);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Print Barcodes</title>

<style>
body {
    font-family: Arial;
    margin: 20px;
}

 
 
/* ---------- GRID LAYOUT ---------- */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);   /* 3 per row */
    gap: 12px;
}

.barcode-box {
    border: 1px solid #000;
    padding: 8px;
    text-align: center;
}

.barcode-box img {
    width: 100%;
    height: auto;
}

.code {
    font-size: 13px;
    margin-top: 5px;
    font-weight: bold;
}

@media print {
    button { display: none; }
}
</style>
</head>

<body>

<button onclick="window.print()">🖨️ Print</button>
<br><br>

<div class="grid">

<?php foreach($output as $trackingId): ?>
    <div class="barcode-box">
        <img src="data:image/png;base64,<?= generateBarcode($trackingId) ?>">
        <div class="code"><?= htmlspecialchars($trackingId) ?></div>
    </div>
<?php endforeach; ?>

</div>

</body>
</html>
