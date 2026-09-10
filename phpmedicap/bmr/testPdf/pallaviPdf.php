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

<table cellpadding="10">
 <tr>
        <td style="text-align: left; font-weight:bold">  STEP - 4: COMPRESSION</td>
    </tr>
     <tr>
         <td style="text-align: left; font-weight:bold">  STEP 4.1 Line Clearances - Tablets Compression Area</td>
    </tr>
    
    
</table>


<table border="1" style="width: 540px;">
   <tr>
        <td style="text-align: left; width: 270px; font-weight:bold">Equipment ID :</td>
         <td style="text-align: left; width: 270px; font-weight:bold">Capacity (Station ) :</td>
    </tr>

      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Line Clearance :</td>
    </tr>
     <tr>
        <td style="text-align: left; width: 540px;">• Check area ,walls, doors, flowerings is Cleaned Properly and no Traces of previous Product </td>
    </tr>
     <tr>
        <td style="text-align: left; width: 540px;">• Ensure QC release of Granules before taking for compression.   </td>
    </tr>
     <tr>
        <td style="text-align: left; width: 540px;">• Ensure that compression Machine, dust extractor, dedusting unit is cleaned and arranged properly</td>
    </tr>
     <tr>
        <td style="text-align: left;width: 130;">Previous Product</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left;width: 130;"></td>
        <td style="text-align: left;width: 130;">Batch No.</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left; width: 130;"></td>
    </tr>
         
         <tr>
        <td style="text-align: left;width: 130;">Area Cleaning Done By</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left;width: 130;"></td>
        <td style="text-align: left;width: 130;">Checked By</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left; width: 130;"></td>
    </tr>
         <tr>
        <td style="text-align: left;width: 130;">Line Clearance Given By QA</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left;width: 130;"></td>
        <td style="text-align: left;width: 130;">Date</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left; width: 130;"></td>
    </tr>
         <tr>
        <td style="text-align: left;width: 130;">Line Clearance SOP Ref No</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left;width: 130;"></td>
        <td style="text-align: left;width: 130;">Time</td>
        <td style="text-align: left;width: 10px;">:</td>
        <td style="text-align: left; width: 130;"></td>
    </tr>
       <tr>
        <td style="text-align: left;width: 540;"><b>Note: </b>Ensure That Temp. Shall NMT 25°C, % RH. Shall NMT 55% during the processing of the materials in  Compression area. </td>
    </tr>
</table>




<table style="width: 540px;">
      <tr>
        <td style="width: 40px; font-weight:bold">4.2</td>
        <td style="text-align: left;width: 500px; font-weight:bold">Instruction:</td>
    </tr>
     <tr>
             <td style="width: 40px;">4.2.1 </td>
        <td style="text-align: left; width: 500px;">Set up and Operate Compression machine as per SOP .</td>
    </tr>
     <tr>
             <td style="width: 40px;">4.2.2 </td>
        <td style="text-align: left; width: 500px;">Compress the blend after due QC approval, Machines-29 Station/51 Station/47 Station </td>
    </tr>
     <tr>
             <td style="width: 40px;">4.2.3</td>
        <td style="text-align: left; width: 500px;">Compress the blend as per the specification and carryout in process checks at specified interval.</td>
    </tr>
    </table>

     <div></div>

<table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">PUNCHES AND DIES – CHECK RECORD : For 29 Station/47 Station/51 Station :</td>
    </tr>
    
    </table>

<div></div>

<table style="width: 540px;" border="1">

    <tr>
        <td style="text-align: center;width: 250;">Description</td>
        <td style="text-align: left;width: 140px;">Fitted by Operator</td>
        <td style="text-align: left;width: 150px;">Checked by Production</td>
      
    </tr>
       <tr>
        <td style="text-align: left;width: 250;">Upper punches</td>
        <td style="text-align: left;width: 140px;"></td>
        <td style="text-align: left;width: 150px;"></td>
      
    </tr>
      <tr>
        <td style="text-align: left;width: 250;">Lower Punches</td>
        <td style="text-align: left;width: 140px;"></td>
        <td style="text-align: left;width: 150px;"></td>
      
    </tr>
      <tr>
        <td style="text-align: left;width: 250;">Dies:</td>
        <td style="text-align: left;width: 140px;"></td>
        <td style="text-align: left;width: 150px;"></td>
      
    </tr>
        </table>
<div></div>
<div></div>

<table style="width: 540px;" border="1">

    <tr>
        <td style="text-align: center;width: 65;" rowspan="6">Upper Punches</td>
        <td style="text-align: center;width: 25;">1</td>
        <td style="text-align: center;width: 25;">2</td>
        <td style="text-align: center;width: 25;">3</td>
        <td style="text-align: center;width: 25;">4</td>
        <td style="text-align: center;width: 25;">5</td>
        <td style="text-align: center;width: 25;">6</td>
        <td style="text-align: center;width: 25;">7</td>
        <td style="text-align: center;width: 25;">8</td>
        <td style="text-align: center;width: 25;">9</td>
        <td style="text-align: center;width: 25;">10</td>
        <td style="text-align: center;width: 25;">11</td>
        <td style="text-align: center;width: 25;">12</td>
        <td style="text-align: center;width: 25;">13</td>
        <td style="text-align: center;width: 25;">14</td>
        <td style="text-align: center;width: 25;">15</td>
        <td style="text-align: center;width: 25;">16</td>
        <td style="text-align: center;width: 25;">17</td>
        <td style="text-align: center;width: 25;">18</td>
        <td style="text-align: center;width: 25;">19</td>
    </tr>
        <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;">20</td>
        <td style="text-align: center;width: 25;">21</td>
        <td style="text-align: center;width: 25;">22</td>
        <td style="text-align: center;width: 25;">23</td>
        <td style="text-align: center;width: 25;">24</td>
        <td style="text-align: center;width: 25;">25</td>
        <td style="text-align: center;width: 25;">26</td>
        <td style="text-align: center;width: 25;">27</td>
        <td style="text-align: center;width: 25;">28</td>
        <td style="text-align: center;width: 25;">29</td>
        <td style="text-align: center;width: 25;">30</td>
        <td style="text-align: center;width: 25;">31</td>
        <td style="text-align: center;width: 25;">32</td>
        <td style="text-align: center;width: 25;">33</td>
        <td style="text-align: center;width: 25;">34</td>
        <td style="text-align: center;width: 25;">35</td>
        <td style="text-align: center;width: 25;">36</td>
        <td style="text-align: center;width: 25;">37</td>
        <td style="text-align: center;width: 25;">38</td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;">39</td>
        <td style="text-align: center;width: 25;">40</td>
        <td style="text-align: center;width: 25;">41</td>
        <td style="text-align: center;width: 25;">42</td>
        <td style="text-align: center;width: 25;">43</td>
        <td style="text-align: center;width: 25;">44</td>
        <td style="text-align: center;width: 25;">45</td>
        <td style="text-align: center;width: 25;">46</td>
        <td style="text-align: center;width: 25;">47</td>
        <td style="text-align: center;width: 25;">48</td>
        <td style="text-align: center;width: 25;">49</td>
        <td style="text-align: center;width: 25;">50</td>
        <td style="text-align: center;width: 25;">51</td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
    
    <tr>
        <td style="text-align: center;width: 65;" rowspan="6">Upper Punches</td>
        <td style="text-align: center;width: 25;">1</td>
        <td style="text-align: center;width: 25;">2</td>
        <td style="text-align: center;width: 25;">3</td>
        <td style="text-align: center;width: 25;">4</td>
        <td style="text-align: center;width: 25;">5</td>
        <td style="text-align: center;width: 25;">6</td>
        <td style="text-align: center;width: 25;">7</td>
        <td style="text-align: center;width: 25;">8</td>
        <td style="text-align: center;width: 25;">9</td>
        <td style="text-align: center;width: 25;">10</td>
        <td style="text-align: center;width: 25;">11</td>
        <td style="text-align: center;width: 25;">12</td>
        <td style="text-align: center;width: 25;">13</td>
        <td style="text-align: center;width: 25;">14</td>
        <td style="text-align: center;width: 25;">15</td>
        <td style="text-align: center;width: 25;">16</td>
        <td style="text-align: center;width: 25;">17</td>
        <td style="text-align: center;width: 25;">18</td>
        <td style="text-align: center;width: 25;">19</td>
    </tr>
        <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;">20</td>
        <td style="text-align: center;width: 25;">21</td>
        <td style="text-align: center;width: 25;">22</td>
        <td style="text-align: center;width: 25;">23</td>
        <td style="text-align: center;width: 25;">24</td>
        <td style="text-align: center;width: 25;">25</td>
        <td style="text-align: center;width: 25;">26</td>
        <td style="text-align: center;width: 25;">27</td>
        <td style="text-align: center;width: 25;">28</td>
        <td style="text-align: center;width: 25;">29</td>
        <td style="text-align: center;width: 25;">30</td>
        <td style="text-align: center;width: 25;">31</td>
        <td style="text-align: center;width: 25;">32</td>
        <td style="text-align: center;width: 25;">33</td>
        <td style="text-align: center;width: 25;">34</td>
        <td style="text-align: center;width: 25;">35</td>
        <td style="text-align: center;width: 25;">36</td>
        <td style="text-align: center;width: 25;">37</td>
        <td style="text-align: center;width: 25;">38</td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;">39</td>
        <td style="text-align: center;width: 25;">40</td>
        <td style="text-align: center;width: 25;">41</td>
        <td style="text-align: center;width: 25;">42</td>
        <td style="text-align: center;width: 25;">43</td>
        <td style="text-align: center;width: 25;">44</td>
        <td style="text-align: center;width: 25;">45</td>
        <td style="text-align: center;width: 25;">46</td>
        <td style="text-align: center;width: 25;">47</td>
        <td style="text-align: center;width: 25;">48</td>
        <td style="text-align: center;width: 25;">49</td>
        <td style="text-align: center;width: 25;">50</td>
        <td style="text-align: center;width: 25;">51</td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
    
    <tr>
        <td style="text-align: center;width: 65;" rowspan="6">Upper Punches</td>
        <td style="text-align: center;width: 25;">1</td>
        <td style="text-align: center;width: 25;">2</td>
        <td style="text-align: center;width: 25;">3</td>
        <td style="text-align: center;width: 25;">4</td>
        <td style="text-align: center;width: 25;">5</td>
        <td style="text-align: center;width: 25;">6</td>
        <td style="text-align: center;width: 25;">7</td>
        <td style="text-align: center;width: 25;">8</td>
        <td style="text-align: center;width: 25;">9</td>
        <td style="text-align: center;width: 25;">10</td>
        <td style="text-align: center;width: 25;">11</td>
        <td style="text-align: center;width: 25;">12</td>
        <td style="text-align: center;width: 25;">13</td>
        <td style="text-align: center;width: 25;">14</td>
        <td style="text-align: center;width: 25;">15</td>
        <td style="text-align: center;width: 25;">16</td>
        <td style="text-align: center;width: 25;">17</td>
        <td style="text-align: center;width: 25;">18</td>
        <td style="text-align: center;width: 25;">19</td>
    </tr>
        <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;">20</td>
        <td style="text-align: center;width: 25;">21</td>
        <td style="text-align: center;width: 25;">22</td>
        <td style="text-align: center;width: 25;">23</td>
        <td style="text-align: center;width: 25;">24</td>
        <td style="text-align: center;width: 25;">25</td>
        <td style="text-align: center;width: 25;">26</td>
        <td style="text-align: center;width: 25;">27</td>
        <td style="text-align: center;width: 25;">28</td>
        <td style="text-align: center;width: 25;">29</td>
        <td style="text-align: center;width: 25;">30</td>
        <td style="text-align: center;width: 25;">31</td>
        <td style="text-align: center;width: 25;">32</td>
        <td style="text-align: center;width: 25;">33</td>
        <td style="text-align: center;width: 25;">34</td>
        <td style="text-align: center;width: 25;">35</td>
        <td style="text-align: center;width: 25;">36</td>
        <td style="text-align: center;width: 25;">37</td>
        <td style="text-align: center;width: 25;">38</td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;">39</td>
        <td style="text-align: center;width: 25;">40</td>
        <td style="text-align: center;width: 25;">41</td>
        <td style="text-align: center;width: 25;">42</td>
        <td style="text-align: center;width: 25;">43</td>
        <td style="text-align: center;width: 25;">44</td>
        <td style="text-align: center;width: 25;">45</td>
        <td style="text-align: center;width: 25;">46</td>
        <td style="text-align: center;width: 25;">47</td>
        <td style="text-align: center;width: 25;">48</td>
        <td style="text-align: center;width: 25;">49</td>
        <td style="text-align: center;width: 25;">50</td>
        <td style="text-align: center;width: 25;">51</td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
      <tr>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
        <td style="text-align: center;width: 25;"></td>
    </tr>
        </table>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.3 COMPRESSION PARAMETERS – Specifications</td>
    </tr>
    </table>
    <div></div>
    <table style="width: 540px;" border="1">
      <tr>
       <td style="text-align: center;width: 70px;">Parameter & Frequency</td>
        <td style="text-align: center;width: 105px;">Description Every 30 min.</td>
        <td style="text-align: center;width: 105px;">Avg. wt of 20 tablets Every 30 min.</td>
        <td style="text-align: center;width: 70px;">Thickness Every 30 min.</td>
        <td style="text-align: center;width: 60px;">Hardness Every 30 min.</td>
        <td style="text-align: center;width: 60px;">DT Every 120 min.</td>
        <td style="text-align: center;width: 60px;">Friability Every 120 min.</td>
    </tr>
      <tr>
       <td style="text-align: center;width: 70px;">Standard</td>
        <td style="text-align: center;width: 105px;" rowspan="2">White, circular flat bevel edged  uncoated tablet with break line on one side & other side plain</td>
        <td style="text-align: center;width: 105px;">11.40g ± 5 %</td>
        <td style="text-align: center;width: 70px;" rowspan="2">4.1 ± 0.5 mm 3.6mm to 4.6 mm</td>
        <td style="text-align: center;width: 60px;" rowspan="2">NLT 3 Kg/cm2</td>
        <td style="text-align: center;width: 60px;" rowspan="2">NMT 15min</td>
        <td style="text-align: center;width: 60px;" rowspan="2">NMT 1 % w/w </td>
    </tr>
      <tr>
       <td style="text-align: center;width: 70px;">Limits</td>
        <td style="text-align: center;width: 105px;">10.830 g to 11.970 g</td>
     
    </tr>
    </table>
     <div></div>
    <table style="width: 540px;" border="1">
      <tr>
       <td style="text-align: center;width: 135px;">Operator</td>
        <td style="text-align: center;width: 135px;">Cubicle No.</td>
        <td style="text-align: center;width: 135px;">Equip. No.</td>
        <td style="text-align: center;width: 135px;">M/C speed RPM</td>
    </tr>
        <tr>
       <td style="text-align: center;width: 135px;"></td>
        <td style="text-align: center;width: 135px;"></td>
        <td style="text-align: center;width: 135px;">PD/EQ/ _______</td>
        <td style="text-align: center;width: 135px;"></td>
    </tr>
        <tr>
       <td style="text-align: center;width: 135px;">Compression Start Date </td>
        <td style="text-align: center;width: 135px;"></td>
        <td style="text-align: center;width: 135px;">Compression End Date </td>
        <td style="text-align: center;width: 135px;"></td>
    </tr>
     </table>
    <div></div>
         <div></div>

    <table style="width: 540px;" border="1">
        <tr>
        <td style="text-align: center;width: 33px;" rowspan="2">Date</td>
        <td style="text-align: center;width: 35px;" rowspan="2">Time</td>
        <td style="text-align: center;width: 68px;" rowspan="2">Description</td>
        <td style="text-align: center;width: 62px;">Wt.of 20tabs.(gm)</td>
        <td style="text-align: center;width: 62px;">Thickness (mm)</td>
        <td style="text-align: center;width: 62px;">Hardness Kg/cm2 </td>
        <td style="text-align: center;width: 62px;">DT.(min) </td>
        <td style="text-align: center;width: 62px;">Friability %</td>
        <td style="text-align: center;width: 96px;">Checked by Production</td>
      
    </tr>
    <tr>    
    
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 54px;">Operator</td>
        <td style="text-align: center;width: 42px;">Officer</td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;" rowspan="2"></td>
        <td style="text-align: center;width: 32px;" rowspan="2"></td>
        <td style="text-align: center;width: 30px;" rowspan="4"></td>
        <td style="text-align: center;width: 32px;" rowspan="4"></td>
        <td style="text-align: center;width: 30px;" rowspan="4"></td>
        <td style="text-align: center;width: 32px;" rowspan="4"></td>
        <td style="text-align: center;width: 54px;" rowspan="4"></td>
        <td style="text-align: center;width: 42px;" rowspan="4"></td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
      
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;" rowspan="2"></td>
        <td style="text-align: center;width: 32px;" rowspan="2"></td>
        
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
       

    </tr>
    </table>
 <div></div>
      <div></div>

    <table style="width: 540px;" border="1">
        <tr>
        <td style="text-align: center;width: 33px;" rowspan="2">Date</td>
        <td style="text-align: center;width: 35px;" rowspan="2">Time</td>
        <td style="text-align: center;width: 68px;" rowspan="2">Description</td>
        <td style="text-align: center;width: 62px;">Wt.of 20tabs.(gm)</td>
        <td style="text-align: center;width: 62px;">Thickness (mm)</td>
        <td style="text-align: center;width: 62px;">Hardness Kg/cm2 </td>
        <td style="text-align: center;width: 62px;">DT.(min) </td>
        <td style="text-align: center;width: 62px;">Friability %</td>
        <td style="text-align: center;width: 96px;">Checked by Production</td>
      
    </tr>
    <tr>    
    
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 54px;">Operator</td>
        <td style="text-align: center;width: 42px;">Officer</td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;" rowspan="2"></td>
        <td style="text-align: center;width: 32px;" rowspan="2"></td>
        <td style="text-align: center;width: 30px;" rowspan="4"></td>
        <td style="text-align: center;width: 32px;" rowspan="4"></td>
        <td style="text-align: center;width: 30px;" rowspan="4"></td>
        <td style="text-align: center;width: 32px;" rowspan="4"></td>
        <td style="text-align: center;width: 54px;" rowspan="4"></td>
        <td style="text-align: center;width: 42px;" rowspan="4"></td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
      
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;" rowspan="2"></td>
        <td style="text-align: center;width: 32px;" rowspan="2"></td>
        
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
       

    </tr>
    </table>
     <div></div>
          <div></div>

    <table style="width: 540px;" border="1">
        <tr>
        <td style="text-align: center;width: 33px;" rowspan="2">Date</td>
        <td style="text-align: center;width: 35px;" rowspan="2">Time</td>
        <td style="text-align: center;width: 68px;" rowspan="2">Description</td>
        <td style="text-align: center;width: 62px;">Wt.of 20tabs.(gm)</td>
        <td style="text-align: center;width: 62px;">Thickness (mm)</td>
        <td style="text-align: center;width: 62px;">Hardness Kg/cm2 </td>
        <td style="text-align: center;width: 62px;">DT.(min) </td>
        <td style="text-align: center;width: 62px;">Friability %</td>
        <td style="text-align: center;width: 96px;">Checked by Production</td>
      
    </tr>
    <tr>    
    
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 30px;">LHS</td>
        <td style="text-align: center;width: 32px;">RHS</td>
        <td style="text-align: center;width: 54px;">Operator</td>
        <td style="text-align: center;width: 42px;">Officer</td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;" rowspan="2"></td>
        <td style="text-align: center;width: 32px;" rowspan="2"></td>
        <td style="text-align: center;width: 30px;" rowspan="4"></td>
        <td style="text-align: center;width: 32px;" rowspan="4"></td>
        <td style="text-align: center;width: 30px;" rowspan="4"></td>
        <td style="text-align: center;width: 32px;" rowspan="4"></td>
        <td style="text-align: center;width: 54px;" rowspan="4"></td>
        <td style="text-align: center;width: 42px;" rowspan="4"></td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
      
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;" rowspan="2"></td>
        <td style="text-align: center;width: 32px;" rowspan="2"></td>
        
    </tr>
      <tr>    
        <td style="text-align: center;width: 33px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 68px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
        <td style="text-align: center;width: 30px;"></td>
        <td style="text-align: center;width: 32px;"></td>
       

    </tr>
    </table>
    <div></div>
    <div></div>
    <div></div>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.4 INDIVIDUAL WEIGHT VARIATION RECORD</td>
    </tr>
      <tr>
        <td style="text-align: left;width: 540px;">To be done by Production Supervisor and IPQA officer Alternatively Means Production Chemist will perform checks Initial,08,16 ....and IPQA Officer  at 04,12,...so on</td>
    </tr>
          <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Individual wt. variation: 570 mg ± 5% (541.5 mg – 598.5 mg)	Frequency: 240 min.</td>
    </tr>

    </table>
    <div></div>
    <div></div>

     <table style="width: 540px;" border="1">
        <tr>
        <td style="text-align: center;width: 50px;" rowspan="3">No. of Tablets</td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date: </td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date: </td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date: </td>
        <td style="text-align: center;width: 70px;">Wt. (mg) Date:</td>
      
    </tr>
      <tr>    
    
        <td style="text-align: center;width: 70px;">Time:</td>
        <td style="text-align: center;width: 70px;">Time:</td>
        <td style="text-align: center;width: 70px;">Time:</td>
        <td style="text-align: center;width: 70px;">Time:</td>
        <td style="text-align: center;width: 70px;">Time:</td>
        <td style="text-align: center;width: 70px;">Time:</td>
        <td style="text-align: center;width: 70px;">Time:</td>
    </tr>
    <tr>    
    
        <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
        <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
        <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
        <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
        <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
       <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
        <td style="text-align: center;width: 35px;">LHS</td>
        <td style="text-align: center;width: 35px;">RHS</td>
    </tr>
      <tr>    
        <td style="text-align: center;width: 50px;">1</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">Total (T)</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">Avg(A)=T/20</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">Min Wt. </td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">Max Wt.</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">- %*</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">+ %*</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">No. of tab above 5%#</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">No. of tab below5%#</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
     <tr>    
        <td style="text-align: center;width: 50px;">Check By</td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
        <td style="text-align: center;width: 35px;"></td>
    </tr>
      </table>
    <div></div>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px;">*  + 5% of the average weight. # NMT 2 tablets are more than 5% & none more than 10%.</td>
    </tr>
      <tr>
        <td style="text-align: left;width: 540px;">Calculate as   -% =   A- Min x 100/A		+% = Max- A x 100/A</td>
    </tr>
     
    </table>
     <div></div>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.5  COMPRESSED TABLETS CONTAINE</td>
    </tr>
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">RS WEIGHING RECORD</td>
    </tr>
      <tr>
               <td style="text-align: left;width: 270px; font-weight:bold">Balance ID:</td>
      <td style="text-align: left;width: 270px; font-weight:bold">Date:____________</td>
    </tr>
    </table>
      <div></div>
        <table style="width: 540px;" border="1">

        <tr>    
      
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
       <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
    </tr>
        <tr>    
      
        <td style="text-align: center;width: 54px;">01</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">11</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">02</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">12</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">03</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">13</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">04</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">14</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">05</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">15</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">06</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">16</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">07</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">17</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">08</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">18</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">09</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">19</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">10</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">20</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
      </table>
    <div></div>
       <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">COMPRESSED TABLETS CONTAINERS WEIGHING RECORD</td>
    </tr>
      <tr>
               <td style="text-align: left;width: 270px; font-weight:bold">Balance ID:</td>
      <td style="text-align: left;width: 270px; font-weight:bold">Date:____________</td>
    </tr>
    </table>
      <div></div>
        <table style="width: 540px;" border="1">
        
        <tr>    
      
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
       <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
    </tr>
        <tr>    
      
        <td style="text-align: center;width: 54px;">01</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">11</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">02</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">12</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">03</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">13</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">04</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">14</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">05</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">15</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">06</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">16</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">07</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">17</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">08</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">18</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">09</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">19</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">10</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">20</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
      </table>
     <div></div>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Total Number of Containers:  _______________ </td>
    </tr>
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Total Net weight of the Tablets: _______________Kg. = No. of Tablets: _______________</td>
    </tr>
      <tr>
               <td style="text-align: left;width: 270px; font-weight:bold"> INPROCESS YIELD: </td>
    </tr>
    </table>
      <div></div>   

    <table style="width: 540px;" border="1">
   <tr>      
        <td style="text-align: center; font-weight:bold;width: 70px;">Theoretical batch Yield (Kg) A</td>
        <td style="text-align: center; font-weight:bold;width: 70px;">Weight of Granules Received (B)</td>
        <td style="text-align: center; font-weight:bold;width: 70px;">No of Tablets to be Compressed (C)</td>
        <td style="text-align: center; font-weight:bold;width: 70px;">Weight of Tablets After Compression (D)</td>
        <td style="text-align: center; font-weight:bold;width: 65px;">No of tablets Compressed (E)</td>
        <td style="text-align: center; font-weight:bold;width: 65px;">Loss during Compression = B- D </td>
        <td style="text-align: center; font-weight:bold;width: 65px;">Recoverable Tablets</td>
       <td style="text-align: center; font-weight:bold;width: 65px;">Percentage Yield = D X 100/A</td>
    </tr>
    <tr>      
        <td style="text-align: center;width: 70px;"></td>
        <td style="text-align: center;width: 70px;"></td>
        <td style="text-align: center;width: 70px;"></td>
        <td style="text-align: center;width: 70px;"></td>
        <td style="text-align: center;width: 65px;"></td>
        <td style="text-align: center;width: 65px;"></td>
        <td style="text-align: center;width: 65px;"></td>
       <td style="text-align: center;width: 65px;"></td>
    </tr>
      </table>
     <div></div>
    <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4 .6	COMPRESSED TABLETS INSPECTION RECORD</td>
    </tr><br>
      <tr>
        <td style="text-align: left;width: 540px;">Transfer the tablets to inspection area and inspect the tablets for broken, chipped, black spots and defective tablets on inspection belt or on SS tray using butter paper.</td>
    </tr><br>
      <tr>
               <td style="text-align: left;width: 540px;">	Operation done by: - ______________From:- _________           to: - ____________</td>
    </tr><br>
     <tr>
               <td style="text-align: left;width: 540px;">	Total tablets taken for inspection (A):-          ______________ kg.</td>
    </tr><br>
     <tr>
               <td style="text-align: left;width: 540px;">	Total recoverable tablets after inspection (B):-    ______________ kg.</td>
    </tr><br>
     <tr>
               <td style="text-align: left;width: 540px;">	Total Good tablets after inspection (A-B):       ______________ kg</td>
    </tr><br>
     <tr>
               <td style="text-align: left;width: 540px;">	% Yield _______________ (NLT 98.0 %) </td>
    </tr>
    </table>
      <div></div>   
 <div></div> <div></div> <div></div> <div></div>
       <div></div>
       <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">4.6.1 WEIGHT OF SORTED TABLETS</td>
    </tr>
      <tr>
               <td style="text-align: left;width: 270px; font-weight:bold">Balance ID:</td>
      <td style="text-align: left;width: 270px; font-weight:bold">Date:____________</td>
    </tr>
    </table>
      <div></div>
        <table style="width: 540px;" border="1">
        
        <tr>    
      
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
       <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
    </tr>
        <tr>    
      
        <td style="text-align: center;width: 54px;">01</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">11</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">02</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">12</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">03</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">13</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">04</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">14</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">05</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">15</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">06</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">16</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">07</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">17</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">08</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">18</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">09</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">19</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">10</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">20</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
      </table>
     <div></div>

      <div></div>
       <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">WEIGHT OF SORTED TABLETS </td>
    </tr>
      <tr>
               <td style="text-align: left;width: 270px; font-weight:bold">Balance ID:</td>
      <td style="text-align: left;width: 270px; font-weight:bold">Date:____________</td>
    </tr>
    </table>
      <div></div>
        <table style="width: 540px;" border="1">
        
        <tr>    
      
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
        <td style="text-align: center;width: 54px;">Drum  No</td>
        <td style="text-align: center;width: 54px;">Gross Wt (Kg)</td>
       <td style="text-align: center;width: 54px;">Tare Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Net Wt (Kg)</td>
        <td style="text-align: center;width: 54px;">Done By</td>
    </tr>
        <tr>    
      
        <td style="text-align: center;width: 54px;">01</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">11</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">02</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">12</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">03</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">13</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">04</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">14</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">05</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">15</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">06</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">16</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">07</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">17</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">08</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">18</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">09</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">19</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
       <tr>    
      
        <td style="text-align: center;width: 54px;">10</td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;">20</td>
        <td style="text-align: center;width: 54px;"></td>
       <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
        <td style="text-align: center;width: 54px;"></td>
    </tr>
      </table>
     <div></div>
      <div></div>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Total Number of Containers:  _______________ </td>
    </tr><br>
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Total Net weight of the Tablets: _______________Kg. = No. of Tablets: _______________</td>
    </tr>
      
    </table>
       <div></div>
      <div></div>
        <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.7 COMPRESSED TABLETS SAMPLING DETAILS</td>
    </tr><br>
      <tr>
        <td style="text-align: left;width: 180px;">Intimation given by:</td>
        <td style="text-align: left;width: 180px;"> Sign/Date: _____________</td>
        <td style="text-align: left;width: 180px;">Time: ___________</td>
    </tr><br>
        <tr>
        <td style="text-align: left;width: 180px;">Sampled by (IPQA):</td>
        <td style="text-align: left;width: 180px;"> Sign/Date: _____________</td>
        <td style="text-align: left;width: 180px;">Time: ___________</td>
    </tr>
    </table>
      <div></div>   
            <div></div>   

                  <div></div>   
 <table style="width: 540px;">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.8  YIELD RECONCILIATION</td>
    </tr>
      </table>

<table style="width: 540px;" border="1">
   <tr>      
        <td style="text-align: center; font-weight:bold;width: 90px;">STAGE </td>
        <td style="text-align: center; font-weight:bold;width: 90px;">THEORETICAL BATCH SIZE(A)</td>
        <td style="text-align: center; font-weight:bold;width: 90px;">ACTUAL YIELD (B)</td>
        <td style="text-align: center; font-weight:bold;width: 90px;">% YIELD = B X 100 A</td>
        <td style="text-align: center; font-weight:bold;width: 90px;">CHECKED BY (PROD)</td>
        <td style="text-align: center; font-weight:bold;width: 90px;">VERIFIED BY (QA)</td>

    </tr>
    <tr>      
        <td style="text-align: center;width: 90px;"></td>
        <td style="text-align: center;width: 90px;"></td>
        <td style="text-align: center;width: 90px;"></td>
        <td style="text-align: center;width: 90px;"></td>
        <td style="text-align: center;width: 90px;"></td>
        <td style="text-align: center;width: 90px;"></td>
       
    </tr>
      </table>
     <div></div>
      <table style="width: 540px;"  border="1">
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">Document checked by:</td>
    </tr>
    <tr>
        <td style="text-align: left;width: 270px;">Production Chemist: </td>
        <td style="text-align: left;width: 270px;">Quality Assurance: </td>

    </tr>
    <tr>
        <td style="text-align: left;width: 270px;">Sign / Date:</td>
        <td style="text-align: left;width: 270px;">Sign / Date:</td>

    </tr>
      </table>
           <div></div>
            <table style="width: 540px;" >
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.9  Destruct Irrecoverable Rejection Tablet by putting in water in presence of QA. </td>

        </tr>
         <tr>
           <td style="text-align: left;width: 270px; font-weight:bold">SOP No.:</td>

        </tr>
              </table>

            <div></div>
      <table style="width: 540px;"  border="1">
     
    <tr>
        <td style="text-align: left;width: 270px;">Destruction supervised done by (Production): </td>
        <td style="text-align: left;width: 270px;"></td>

    </tr>
    <tr>
        <td style="text-align: left;width: 270px;">In presence of QA: </td>
        <td style="text-align: left;width: 270px;"></td>

    </tr>  <tr>
        <td style="text-align: left;width: 270px;">Sign & Date:</td>
        <td style="text-align: left;width: 270px;"></td>

    </tr>
      </table>
        <div></div>

       <table style="width: 540px;" >
      <tr>
        <td style="text-align: left;width: 540px; font-weight:bold">STEP 4.10  DEVIATION APPROVAL SHEET	</td>

        </tr>
         </table>
                <div></div>
 
<table style="width: 540px;" border="1">
   <tr>      
        <td style="text-align: center; font-weight:bold;width: 135px;">DEVIATION </td>
        <td style="text-align: center; font-weight:bold;width: 135px;">REASON & JUSTIFICATION</td>
        <td style="text-align: center; font-weight:bold;width: 135px;">PROPOSED BY Production Chemist</td>
        <td style="text-align: center; font-weight:bold;width: 135px;">APPROVED BY QA</td>
       

    </tr>
    <tr>      
        <td style="text-align: center;width: 135px;"></td>
        <td style="text-align: center;width: 135px;"></td>
        <td style="text-align: center;width: 135px;"></td>
        <td style="text-align: center;width: 135px;"></td>

       
    </tr>
      </table>

';







        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Test.pdf', 'I');
        exit(); 
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
