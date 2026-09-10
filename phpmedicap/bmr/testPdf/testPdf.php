<?php
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    file_put_contents('../../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "testPdf") {
        $sql = "SELECT * FROM vendor ";
        $_GET['filename'] = 'testPdf'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html = "";
        

$html.= '
<div style="margin-bottom: 20px;">

<table cellpadding="10" border="1" >

  <tr>
    <th style="text-align:left; font-weight:bold; font-size:12px;" >Inactive Material Dispensing Record :</th>
  </tr>
</table>
<table cellpadding="10" border="1">

  <tr>
    <th style="text-align:left; font-weight:bold; font-size:12px;" >Lubrication Material </th>
  </tr>
</table>


<table cellpadding="5" border="1">
 
  <tr style="border: solid 1px black">
    <th rowspan="2" >RM Code</th>
    <th rowspan="2">Material Name</th>
    <th rowspan="2">SPC</th>
    <th rowspan="2">LOT</th>
    <th rowspan="2">Batch Qty. (Kg)</th>
    <th rowspan="2">AR. NO</th>
    <th colspan="3">Weight in KG</th>
    <th rowspan="2">Done By Stores</th>
    <th rowspan="2">Checked By Prod.</th>
  </tr>
  <tr>
    <th rowspan="1">Gross Wt</th>
    <th rowspan="1">Tare Wt</th>
    <th rowspan="1">Net Wt</th>
  </tr>
  <tr>
    <td>RM021</td>
    <td>Magnesium Stearate</td>
    <td>BP</td>
    <td></td>
    <td>2.75</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>RP065</td>
    <td>Sodium Starch Glycolate (Primojel)</td>
    <td>BP</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
</table>
</div>

<div>
<table cellpadding="10" border="1">

  <tr>
    <th style="text-align:center; font-weight:bold; font-size:12px;" >Dispensed Material Transfer Details</th>
  </tr>
</table>
<table cellpadding="5" border="1">

  <tr>
    <th style="text-align:center;" >Transferred By Sign/Date</th>
    <th style="text-align:center;"  >Received By Sign/Date</th>
    <th style="text-align:center;" >Checked By Sign/Date</th>
  </tr>
  <tr height="50px">
     <td></td>
    <td ></td>
    <td ></td>
  </tr>
  <!-- add more rows and columns as needed -->
</table>
</div>


<div>
<h3 style="text-align:center;">General Instruction</h3>
<table cellpadding="5" border="1" >
  <tr>
    <td width="30px">1</td>
    <td width="509px">Ensure that Every Equipment is Cleaned, calibrated and Qualified for use.</td>
  </tr>
  <tr>
    <td width="30px">2</td>
    <td width="509px">Ensure that area is cleaned properly.</td>
  </tr>
  <tr>
    <td width="30px">3</td>
    <td width="509px">Gloves, nose masks & safety wares are to be worn during manufacturing operations.</td>
  </tr>
  <tr>
    <td width="30px">4</td>
    <td width="509px">Every area and equipment shall be Labelled for its status.</td>
  </tr>
  <tr>
    <td width="30px">5</td>
    <td width="509px">Ensure proper Line clearance by Quality Unit before starting the operations.</td>
  </tr>
  <tr>
    <td width="30px">6</td>
    <td width="509px">Ensure that all the material weights will be checked before addition by competent officer of Production.</td>
  </tr>
  <tr>
    <td width="30px">7</td>
    <td width="509px">Containers used for manufacturing shall be cleaned ,covered and labelled properly.</td>
  </tr>
  <tr>
    <td width="30px">8</td>
    <td width="509px">All storage conditions shall be followed as per material storage specifications.</td>
  </tr>
  <tr>
    <td width="30px">9</td>
    <td width="509px">Each stage of process shall be reconciled and losses shall be reported in BMR.</td>
  </tr>
  <tr>
    <td width="30px">10</td>
    <td width="509px">If any deviation, Incident or abnormal process behaviour shall be reported Immediately to QA Department.</td>
  </tr>
  <tr>
    <td width="30px">11</td>
    <td width="509px">Wherever necessary sieve integrity shall be checked.</td>
  </tr>

</table>
</div>


<div>
<h3 style="text-align:left;">List of Equipments to be used for Manufacturing:</h3>
<table cellpadding="5" border="1" >
<tr style="border: solid 1px black;">
    <th width="30px" style="border: solid 1px black;">Sr. No</th>
    <th width="150px" style="border: solid 1px black;">Name of Equipment</th>
    <th width="50px" style="border: solid 1px black;">Capacity</th>
    <th width="150px" style="border: solid 1px black;">Equipment ID</th>
    <th width="158px" style="border: solid 1px black;">Operation/Cleaning SOP No</th>
  </tr>
  <tr style="border: solid 1px black;">
    <td width="30px" style="border: solid 1px black;">1</td>
    <td width="150px" style="border: solid 1px black;">Mechanical Sifter</td>
    <td width="50px" style="border: solid 1px black;">30 Inch.</td>
    <td width="150px" style="border: solid 1px black;">PD/EQ/005/01,02,03<br>PD/EQ/005/04,05.</td>
    <td width="158px" style="border: solid 1px black;">SOP/PR/EQP/032-02<br>SOP/PR/EQP/033-02<br>SOP/PR/EQP/003-02</td>
  </tr>
  <tr style="border: solid 1px black;">
    <td width="30px" style="border: solid 1px black;">2</td>
    <td width="150px" style="border: solid 1px black;">Rapid Mixer Granulator (RMG)</td>
    <td width="50px" style="border: solid 1px black;">250Kg</td>
    <td width="150px" style="border: solid 1px black;">PD/EQ/007/01</td>
    <td width="158px" style="border: solid 1px black;">SOP/PR/EQP/004-02</td>
  </tr>
  <tr style="border: solid 1px black;">
    <td width="30px" style="border: solid 1px black;">3</td>
    <td width="150px" style="border: solid 1px black;">Fluidised Bed Dryer (FBD)</td>
    <td width="50px" style="border: solid 1px black;">250Kg</td>
    <td width="150px" style="border: solid 1px black;">PD/EQ/008</td>
    <td width="158px" style="border: solid 1px black;">SOP/PR/EQP/005-02</td>
  </tr>
  <tr style="border: solid 1px black;">
    <td width="30px" style="border: solid 1px black;">4</td>
    <td width="150px" style="border: solid 1px black;">Multimill</td>
    <td width="50px" style="border: solid 1px black;">3 H.P.</td>
    <td width="150px" style="border: solid 1px black;">PD/EQ/009/01,02,03</td>
    <td width="158px" style="border: solid 1px black;">SOP/PR/EQP/006-02</td>
  </tr>
  <tr style="border: solid 1px black;">
    <td width="30px" style="border: solid 1px black;">5</td>
    <td width="150px" style="border: solid 1px black;">Octagonal Blender</td>
    <td width="50px" style="border: solid 1px black;">1000 Kg</td>
    <td width="150px" style="border: solid 1px black;">PD/EQ/049/01</td>
    <td width="158px" style="border: solid 1px black;">SOP/PR/EQP/038-02</td>
  </tr>
 
   <tr>
    <td width="30px">7</td>
    <td width="150px"  style="border: solid 1px black;">Tablet Compression Machine</td>
    <td width="50px"  style="border: solid 1px black;">29 Station</td>
    <td width="150px"  style="border: solid 1px black;">PD/EQ/026/01</td>
    <td width="158px"  style="border: solid 1px black;">SOP/PR/EQP/027-03<br>SOP/PR/EQP/018-02</td>
  </tr>
  <tr>
    <td width="30px">8</td>
    <td width="150px"  style="border: solid 1px black;">Tablet Compression Machine</td>
    <td width="50px"  style="border: solid 1px black;">29 Station</td>
    <td width="150px"  style="border: solid 1px black;">PD/EQ/026/02</td>
    <td width="158px"  style="border: solid 1px black;">SOP/PR/EQP/027-03<br>SOP/PR/EQP/018-02</td>
  </tr>
  <tr>
    <td width="30px"  style="border: solid 1px black;">9</td>
    <td width="150px"  style="border: solid 1px black;">Tablet Compression Machine</td>
    <td width="50px"  style="border: solid 1px black;">47 Station</td>
    <td width="150px"  style="border: solid 1px black;">PD/EQ/026/05</td>
    <td width="158px"  style="border: solid 1px black;">SOP/PR/EQP/056-01<br>SOP/PR/EQP/027-03</td>
  </tr>
  <tr>
    <td width="30px"  style="border: solid 1px black;">10</td>
    <td width="150px"  style="border: solid 1px black;">Tablet Compression Machine</td>
    <td width="50px"  style="border: solid 1px black;">51 Station</td>
    <td width="150px"  style="border: solid 1px black;">PD/EQ/026/04</td>
    <td width="158px"  style="border: solid 1px black;">SOP/PR/EQP/052-01<br>SOP/PR/EQP/027-03
</td>
    </tr>
</table>
</div>

<h2>STAGE 3.0 GRANULATION</h2>

<h3>STAGE 3.1 Line Clearance for Granulation:</h3>

<ol>


<ol>
  <li style="font-size: 10px;">Ensure that Granulation area and all equipments are cleaned properly and there are no traces of Previous product of material.</li>
  <li style="font-size: 10px;">Ensure that balance being used are calibrated and verified for calibration.</li>
  <li style="font-size: 10px;">Ensure that Temperature and humidity of the area is as prescribed limits.</li>
</ol>
</ol>

<table cellpadding="5" border="1" >
<tr style="border: solid 1px black;">
    <th>Previous Product</th>
    <td >:</td>
    <td></td>
     <th>Batch No </th>
    <td>:</td>
 </tr>
 <tr style="border: solid 1px black;">
    <th>Area Cleaning Done By</th>
    <td >:</td>
    <td></td>
     <th>Checked By</th>
    <td >:</td>
 </tr>
 <tr >
    <th>Line Clearance Given By QA</th>
    <td >:</td>
    <td></td>
     <th>Date</th>
    <td>:</td>
 </tr>
 <tr>
    <th>Line Clearance SOP Ref No : SOP/QA/GE/</th>
    <td >:</td>
    <td></td>
     <th>Time</th>
    <td >:</td>
 </tr>
 <tr>
    <td width="538px">Ensure That Temp. Shall NMT 25°C, % RH. Shall NMT 55% during the processing of the materials in Granulation area </td>
 </tr>

</table>

<div>
<h2>SEquipment Cleanliness Checks:</h2>
<table  cellpadding="5" border="1" >
  <tr style="border: solid 1px black;">
    <th>Name of Equipment</th>
    <th>Equipment ID</th>
    <th>Cleaned By</th>
    <th>Checked By</th>
  </tr>
  <tr style="border: solid 1px black;">
    <td>Mechanical Sifter</td>
    <td>PD/EQ/005/</td>
    <td></td>
    <td></td>
  </tr>
  <tr style="border: solid 1px black;">
    <td>Rapid Mixer Granulator (RMG)</td>
    <td>PD/EQ/007/</td>
    <td></td>
    <td></td>
  </tr>
</table>
</div>

<table  cellpadding="5" border="1">
  <tr  style="border: solid 1px black;">
    <td>Fluidised Bed Dryer (FBD)</td>
    <td>PD/EQ/008/</td>
    <td></td>
    <td></td>
  </tr>
  <tr  style="border: solid 1px black;">
    <td>Multimill</td>
    <td>PD/EQ/009/</td>
    <td></td>
    <td></td>
  </tr>
  <tr  style="border: solid 1px black;">
    <td>Octagonal Blender</td>
    <td>PD/EQ/049</td>
    <td></td>
    <td></td>
  </tr>
  <tr  style="border: solid 1px black;">
    <td>Tipper</td>
    <td>PD/EQ/038</td>
    <td></td>
    <td></td>
  </tr>
</table>

<h2>STEP 3.2 MILLING OF ACTIVE INGREDIENT AND SIFTING OF EXCIPIENTS</h2>
    <ol>
      <li style="font-size: 12px;">3.2.1 Assemble &amp; Operate Sifter as per SOP No. SOP/PR/EQP/003-02</li>
      <li style="font-size: 12px;">3.2.2 Fix the specified mesh in the sifter and place a <abbr title="High-Density Polyethylene">HDPE</abbr> drum lined with polythene bag at the discharge end and tie the bag properly.</li>
      <li style="font-size: 12px;" >3.2.3 Sift the materials through respective mesh specified. Collect the sifted materials in polythene bag in <abbr title="High-Density Polyethylene">HDPE</abbr> drum.</li>
      <li style="font-size: 12px;"><strong>Note :</strong> Pregelatinised Starch (Universal) and Sodium Starch Glycolate (<abbr title="Primogel">Primogel</abbr>) of dry mixing to be pass through 60#</li>
      <li style="font-size: 12px;">3.2.4 Check the integrity of sifter sieves before and after sifting the materials</li>
      <li style="font-size: 12px;">3.2.5 Mill the <abbr title="Paracetamol">Paracetamol</abbr> using 0.5 mm screen. Mill at impact forward Position.</li>
    </ol>
      
  <table>
  <tr>
    <th>Equipment No:</th>
    <td>PD/EQ/_______</td>
  </tr>
  <tr>
    <th rowspan="4" >Material</th>
    <th  rowspan="4">LOT No</th>
    <th  rowspan="4">Weight In Kg</th>
    <th  rowspan="4">Sieve/ Mesh Size</th>
    <th  colspan="2">Time/ Mesh Size</th>
    <th  colspan="2">Intigrity/ Mesh Size</th>
    <th rowspan="4">Done By</th>
    <th rowspan="4">Checked By</th>
  </tr>
  <tr>
      <th rowspan="1" >start time </th>
      <th rowspan="1" >end time</th>
      <th rowspan="1" >before</th>
      <th rowspan="1" >after</th>
  </tr>
  <tr>
  <td>Paracetamol</td>
    <td>LOT 1</td>

  </tr>

</table>';
    
  







        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Test.pdf', 'I');
        exit(); 
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
