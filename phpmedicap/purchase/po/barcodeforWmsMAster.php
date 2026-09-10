<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

 
    require '../../tcpdf/tcpdf_barcodes_1d.php';

// ---------------- GET PARAMETERS ----------------
$isView     = $_GET['isView'] ?? '';
 
$trackingId        = $_GET['trackingId'];
 
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
            <td style="text-align:center;"><strong><?= htmlspecialchars($isView) ?> Barcode Priting</strong></td>
        </tr>
    </table>
</div>

<button onclick="window.print()">🖨️ Print</button>
<br><br>

<!-- ================= BARCODE GRID ================= -->
<div class="grid">

    <div class="barcode-box">
        <img src="data:image/png;base64,<?= generateBarcode($trackingId) ?>">
        <div class="code"><?= htmlspecialchars($trackingId) ?></div>
    </div>

</div>

</body>
</html>
