<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

 
    require '../../tcpdf/tcpdf_barcodes_1d.php';

// ---------------- GET PARAMETERS ----------------
$material_code     = $_GET['material_code'] ?? '';
$material_name     = $_GET['material_name'] ?? '';
$batch_no     = $_GET['batch_no'] ?? '';
$trackingId        = $_GET['trackingId'];
$total_containers  = (int)($_GET['total_containers'] ?? 1);
if($total_containers < 1) $total_containers = 1;

// ---------------- BARCODE GENERATOR FUNCTION ----------------
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

/* ---------- HEADER ---------- */
.header {
    text-align: center;
    margin-bottom: 15px;
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

/* ---------- PRINT SETTINGS ---------- */
@media print {
    button { display: none; }
    body { margin: 5mm; }
}
</style>
</head>

<body>

<!-- ================= HEADER ================= -->
<div class="header">
    <table border="1" cellpadding="4" style="width:100%;">
        <tr>
            <td style="text-align:left;"><strong>Material Name</strong></td>
            <td style="text-align:left; color:blue;"><?= htmlspecialchars($material_name) ?></td>
            <td style="text-align:left;"><strong>Material Code</strong></td>
            <td style="text-align:left; color:blue;"><?= htmlspecialchars($material_code) ?></td>
        </tr>
        <tr>
            <td style="text-align:left;"><strong>Medicap Lot No</strong></td>
            <td style="text-align:left; color:blue;"><?= htmlspecialchars($batch_no) ?></td>
            <td style="text-align:left;"><strong>Tracking No</strong></td>
            <td style="text-align:left; color:blue;"><?= htmlspecialchars($trackingId) ?></td>
        </tr>
    </table>
</div>

<button onclick="window.print()">🖨️ Print</button>
<br><br>

<!-- ================= BARCODE GRID ================= -->
<div class="grid">

<?php for($i=1; $i <= $total_containers; $i++): ?>
    <div class="barcode-box">
        <img src="data:image/png;base64,<?= generateBarcode($trackingId) ?>">
        <div class="code"><?= htmlspecialchars($trackingId) ?></div>
        <div style="font-size:11px;">Container <?= $i ?> of <?= $total_containers ?></div>
    </div>
<?php endfor; ?>

</div>

</body>
</html>
