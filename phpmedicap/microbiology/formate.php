<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveTemperature") {
        $sql = "INSERT INTO temperature (department, section, thermo_hygrometer, temperature, humidity, entry_by, entry_date, entry_time) VALUES ('Microbiology', '".$input["section"]."', '".$input["thermo_hygrometer"]."', '".$input["temperature"]."', '".$input["humidity"]."', '".$_GET["emp_id"]."', '$entry_date', '$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getTemperature") {
        $output = array();
        $sql = "SELECT * FROM temperature WHERE department='Microbiology' AND entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getThermohygrometers") {
        $output = array();
        $sql = "SELECT * FROM equipment WHERE department='Microbiology' AND equipment_name='Thermo hygrometer'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);

 }else if ($_GET["type"] == "downloadtemplet") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
            $html.='
            <h3 style="text-align:center">MOTHER/STOCK CULTURE</h3>
            <div></div>
        <table>
        <tr>
        <td style="width:30%;text-align:center">First Generation</td>
        </tr><div></div>
        <tr>
        <td style="width:30%;text-align:center">Second Generation</td>
        </tr>
        
        <tr>
        <td style="width:100%;text-align:center">YC1</td>
        </tr>
        <tr>
        <td style="width:100%;text-align:center">YC2</td>
        </tr>
        <tr>
        <td style="width:100%;text-align:center">YC3</td>
        </tr>
         <tr>
        <td style="width:30%;text-align:center">Third Generation</td>
        </tr>
       
        <tr>
        <td style="width:100%;text-align:center">YC1M1</td>
        </tr>
        <tr>
        <td style="width:100%;text-align:center">YC1M7</td>
        </tr>
        <tr>
        <td style="width:100%;text-align:center">YC1M6</td>
        </tr>
         <tr>
        <td style="width:100%;text-align:center">YC1M5</td>
        </tr>
         <tr>
        <td style="width:100%;text-align:center">YC1M4</td>
        </tr>
         <tr>
        <td style="width:100%;text-align:center">YC1M3</td>
        </tr>
         <tr>
        <td style="width:100%;text-align:center">YC1M2</td>
        </tr><div></div>
        <tr>
        <td style="width:80%;text-align:center">Prepare Positive suspension of culture for routine analysis as per SOP No. SOP/QM/023</td>
        </tr>
         <tr>
        <td style="width:85%;text-align:center">Titled: Preparation of standardized suspension of microorganism to be used as positive control. 
        </td>
        </tr><div></div>
        <tr>
        <td style="width:20%;text-align:center"><b>Note:</b></td>
        </tr>
        <tr>
        <td style="width:75%;text-align:center">YC1-Yearly culture1 1- Number, M1- Monthly 1 –Number</td>
        </tr>
        </table>
        <br pagebreak="true"/>
        
            <h3 style="text-align:center">Culture Reciept</h3>
       <table cellpadding="3" border="0.1">
       <tr>
       <td style="width:100%">Name of Standard Culture :</td>
       </tr>
       <tr>
       <td style="width:10%;text-align:center"><b>ATCC No.</b></td>
       <td style="width:10%;text-align:center"><b>Received Date</b></td>
       <td style="width:10%;text-align:center"><b>In-house B. No.</b></td>
       <td style="width:20%;text-align:center"><b>Date of Sub culturing /Yearly subculture Slant</b></td>
       <td style="width:20%;text-align:center"><b>Date of Sub culturing/ Monthly subculture Slant</b></td>
       <td style="width:20%;text-align:center"><b>Date of preparation of positive suspension</b></td>
       <td style="width:10%;text-align:center"><b>Slant used</b></td>
      </tr>
        <tr>
       <td style="width:10%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
      
       </tr>
       <tr>
       <td style="width:10%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
      
       </tr>
       <tr>
       <td style="width:10%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:20%;text-align:center"></td>
       <td style="width:10%;text-align:center"></td>
      </tr>
       </table>
        <br pagebreak="true"/>
        
            <h3 style="text-align:center">Identification</h3>
       <table cellpadding="3" border="0.1">
       <tr>
       <td style="width:30%;">Name of Standard Culture :</td>
       <td style="width:70%;"></td>
       </tr>
       <tr>
       <td style="width:30%;">Standard Culture received from :</td>
       <td style="width:70%;"></td>
       </tr>
       <tr>
       <td style="width:30%;">Standard Culture received on :</td>
       <td style="width:70%;"></td>
       </tr>
       <tr>
       <td style="width:30%;">Storage Condition:</td>
       <td style="width:70%;"></td>
       </tr>
       <tr>
       <td style="width:30%;">Date of Analysis:</td>
       <td style="width:70%;"></td>
       </tr>
       <tr>
       <td style="width:30%;">Date of Completion :</td>
       <td style="width:70%;"></td>
       </tr>
        </table><div></div>
        <tr>
        <td style="width:100%"><b>Preparation of Primary/Mother Culture</b>: Heavily saturate the swab with the hydrated </td>
        </tr><br>
        <tr>
        <td style="width:10%">material </td>
        <td style="width:10%">inside </td>
        <td style="width:10%">the </td>
        <td style="width:10%">KWIK </td>
        <td style="width:10%">STIK </td>
        <td style="width:10%">and </td>
        <td style="width:10%">transfer </td>
        <td style="width:10%">to </td>
        <td style="width:10%">the </td>
        </tr><br>
        <tr>
        <td style="width:100%">___________________________________agar plate. Using a sterile loop, streak to </td>
        </tr><br>
         <tr>
        <td style="width:100%">facilitate colony isolation Incubate plate at ______ °c for _______hrs. </td>
        </tr>
        <tr>
        <ul>
        <li><b>Colony Characters:</b></li>
        </ul>
        </tr>
        <table cellpadding="3" border="0.1">
        <tr>
        <td style="width:10%;text-align:center"><b>Size</b></td>
           <td style="width:10%;text-align:center"><b>Shape</b></td>
             <td style="width:10%;text-align:center"><b>Margin</b></td>
               <td style="width:20%;text-align:center"><b>Elevation</b></td>
                 <td style="width:20%;text-align:center"><b>Consistency</b></td>
                   <td style="width:10%;text-align:center"><b>Colour</b></td>
                      <td style="width:10%;text-align:center"><b>Opacity</b></td>
        </tr>
         <tr>
        <td style="width:10%;text-align:center"></td>
           <td style="width:10%;text-align:center"></td>
             <td style="width:10%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
                 <td style="width:20%;text-align:center"></td>
                   <td style="width:10%;text-align:center"></td>
                      <td style="width:10%;text-align:center"></td>
        </tr>
        </table>
        <tr>
        <ul>
        <li><b>Morphological character:  (Microscopic):</b></li>
        </ul>
        </tr>
        <table cellpadding="3" border="0.1">
        <tr>
        <td style="width:30%;text-align:center"><b>Test</b></td>
           <td style="width:30%;text-align:center"><b>Observation</b></td>
             <td style="width:30%;text-align:center"><b>Remark (C/NC)/ Sign</b></td>
              
        </tr>
         <tr>
        <td style="width:30%;text-align:center">Gram staining</td>
           <td style="width:30%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
              
        </tr>
        <tr>
        <td style="width:30%;text-align:center">Motility</td>
           <td style="width:30%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
              </tr>
        
        </table>
        <tr>
        <ul>
        <li><b>Biochemical characters:</b></li>
        </ul>
        </tr>
        <table cellpadding="3" border="0.1">
        <tr>
        <td style="width:20%;text-align:center"><b>Date of Analysis</b></td>
           <td style="width:20%;text-align:center"><b>Test</b></td>
             <td style="width:30%;text-align:center"><b>Result</b></td>
             <td style="width:20%;text-align:center"><b>Result</b></td>
              </tr>
         <tr>
        <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              </tr>
        <tr>
        <td style="width:20%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
             <td style="width:30%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              </tr>
        </table><br><br>
        <tr>
        <td style="width:90%"><b>Note</b>: C- Complies, NC- Not Complies, SCDM-Soyabeam casein digest medium, SDB-Sabourauds Dextrose Broth. </td>
        </tr>
        <tr>
        <td style="width:90%"><b>Remark</b>: From above observation, organisms was/was not identified as _________________________________________ .
       </td>
        </tr><div></div><div></div><div></div>
        <table cellpadding="3" border="0.1">
        <tr>
        <td style="width:50%;text-align:center"><b>Analyzed By/On</b></td>
        <td style="width:50%;text-align:center"><b>Checked by /On:</b></td>
        </tr>
         <tr>
        <td style="width:50%;text-align:center"><b></b></td>
        <td style="width:50%;text-align:center"><b></b></td>
        </tr>
         </table>
          <br pagebreak="true"/>
          <h3 style="text-align:center">GPT Liquid</h3>
          <table cellpadding="5" border="0.1">
          <tr>
          <td style="width:50%;">Name of Media:</td>
          <td style="width:50%;">Media code No.:</td>
          </tr>
          <tr>
          <td style="width:50%;">Quantity Received:</td>
          <td style="width:50%;">Date of Received</td>
          </tr>
          <tr>
          <td style="width:50%;">Media Lot No.:</td>
          <td style="width:50%;">Date of Opening:</td>
          </tr>
          <tr>
          <td style="width:50%;">Expiry Date:</td>
          <td style="width:50%;">Date of Report:</td>
          </tr>
          <tr>
          <td style="width:50%;">Bottle No.:</td>
          <td style="width:50%;">Incubator ID No.:</td>
          </tr>
          <tr>
          <td style="width:100%;">Incubation Condition :</td>
         </tr>
         <tr>
          <td style="width:50%;">Approved Lot No.:</td>
          <td style="width:25%;text-align:center" rowspan="2">pH after Sterilization</td>
          <td style="width:25%;"></td>
          </tr>
          <tr>
          <td style="width:50%;">Testing Lot No.:</td>
          <td style="width:25%;"></td>
          </tr>
          <tr>
          <td style="width:50%;">Reason for Test:new/Retest/Others</td>
          <td style="width:50%;">Method:Inoculation/Streaking</td>
         </tr>
          </table><br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:5%;text-align:center"><b>Sr NO.</b></td>
          <td style="width:20%;text-align:center"><b>Name of Organism</b></td>
          <td style="width:20%;text-align:center"><b>Approved Lot(Standard)</b></td>
          <td style="width:20%;text-align:center"><b>Testing Lot</b></td>
          <td style="width:20%;text-align:center"><b>Negative Control</b></td>
          <td style="width:15%;text-align:center"><b>Remark C/NC</b></td>
          </tr>
          <tr>
          <td style="width:5%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center" rowspan="4"></td>
          <td style="width:15%;text-align:center"></td>
          </tr>
           <tr>
          <td style="width:5%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
           <td style="width:15%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:5%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
         <td style="width:15%;text-align:center"></td>
          </tr>
           <tr>
          <td style="width:5%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
         <td style="width:15%;text-align:center"></td>
          </tr>
           <tr>
         <td style="width:100%;"><b>Acceptence Criteria: 1.</b>Selective media: Colony Characters should be as per specification.
         <br> 2. Enrichment broth: Growth of both approved lot and tested lot should be Identical</td>
          </tr>
          </table><br><br>
          <tr>
          <td style="width:40%;text-align:center"><b>Notice:</b> C-Compiles, NC-Not Compiles</td>
          </tr>
          <tr>
          <td style="width:70%;text-align:center"><b>Conclusion:</b> From the above results,it is concluded that the above media is GPT Approved/Not Approved is Suitable/Not Suitable for use.</td>
          </tr><br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:50%;text-align:center"></td>
           <td style="width:50%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:50%;text-align:center"><b>ANALYSED BY/ON</b></td>
           <td style="width:50%;text-align:center"><b>CHECKED BY/ON</b></td>
          </tr>
          </table><div></div><div></div><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
           <br pagebreak="true"/>
          <h3 style="text-align:center">GPT Solid</h3>
          <table cellpadding="5" border="0.1">
          <tr>
          <td style="width:50%;">Name of Media:</td>
          <td style="width:50%;">Media code No.:</td>
          </tr>
          <tr>
          <td style="width:50%;">Quantity Received:</td>
          <td style="width:50%;">Date of Received</td>
          </tr>
          <tr>
          <td style="width:50%;">Media Lot No.:</td>
          <td style="width:50%;">Date of Opening:</td>
          </tr>
          <tr>
          <td style="width:50%;">Expiry Date:</td>
          <td style="width:50%;">Date of Report:</td>
          </tr>
          <tr>
          <td style="width:50%;">Bottle No.:</td>
          <td style="width:50%;">Incubator ID No.:</td>
          </tr>
          <tr>
          <td style="width:100%;">Incubation Condition :</td>
         </tr>
         <tr>
          <td style="width:50%;">Approved Lot No.:</td>
          <td style="width:25%;text-align:center" rowspan="2">pH after Sterilization</td>
          <td style="width:25%;"></td>
          </tr>
          <tr>
          <td style="width:50%;">Testing Lot No.:</td>
          <td style="width:25%;"></td>
          </tr>
          <tr>
          <td style="width:50%;">Reason for Test:new/Retest/Others</td>
          <td style="width:50%;">Method:Inoculation/Streaking</td>
         </tr>
          </table><br><br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;text-align:center"><b>Observation</b></td>
          </tr>
          <tr>
          <td style="width:5%;text-align:center" rowspan="2"><b>Sr No.</b></td>
           <td style="width:20%;text-align:center" rowspan="2"><b>Name of Organism</b></td>
            <td style="width:20%;text-align:center"><b>Approved Lot (Standard)</b></td>
             <td style="width:20%;text-align:center"><b>Testing Lot</b></td>
              <td style="width:15%;text-align:center"><b>Limit % recovery</b></td>
               <td style="width:10%;text-align:center" rowspan="2"><b>Negative Control</b></td>
                <td style="width:10%;text-align:center" rowspan="2"><b>Remark C/NC</b></td>
          </tr>
          <tr>
          
            <td style="width:10%;text-align:center">Plants</td>
             <td style="width:10%;text-align:center">Mean</td>
             <td style="width:10%;text-align:center">Plants</td>
              <td style="width:10%;text-align:center">Mean</td>
              <td style="width:15%;text-align:center">50%-200%</td>
             </tr>
            <tr>
          <td style="width:5%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:15%;text-align:center"></td>
               <td style="width:10%;text-align:center"></td>
                <td style="width:10%;text-align:center"></td>
          </tr>
           <tr>
          <td style="width:5%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:15%;text-align:center"></td>
               <td style="width:10%;text-align:center"></td>
                <td style="width:10%;text-align:center"></td>
          </tr>
          </table><div></div><div></div>
           <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
           <br pagebreak="true"/>
        <table cellpadding="5" border="0.1">
        <tr>
        <td style="width:100%;">Calculation the % recovery by the following formula.<br>%Recovery= Test colony count(Mean)/Standard colony count(Mean)x100
        <br><br><br><b>Acceptence criteria:</b><br>Agar:Recovery of microorganism:50%-200% Approved lot<br>(i.e For Solid media, growth obtained must not differ by a factor greter than 2 from the calculated value for a standerlized inoculum) )
       <br><br><br> For freshly prepared inoculum, growth of micro-organisms comparable to that previously obtained with a previously tested and approved batch of medium occurs.
        <br><br><br> <b>CONCLUSION</b>: From the above results,it is concluded that the above media is GPT <b>Approved /Not approved and is suitable/Not suitable</b> for use
        </td>
        </tr>
       </table><div></div><div></div>
       <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:50%;text-align:center"></td>
           <td style="width:50%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:50%;text-align:center"><b>ANALYSED BY/ON</b></td>
           <td style="width:50%;text-align:center"><b>CHECKED BY/ON</b></td>
          </tr>
          </table><div></div><div></div><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
          <br pagebreak="true"/>
         <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;">Date:</td>
          </tr>
          <tr>
          <td style="width:100%;text-align:center">Media Lot Consumption Record</td>
          </tr>
           <tr>
          <td style="width:20%;text-align:center"><b>Name of Media</b></td>
          <td style="width:10%;text-align:center"><b>Lot No.</b></td>
          <td style="width:20%;text-align:center"><b>Bottal/Box Number</b></td>
          <td style="width:20%;text-align:center"><b>Media Consumed(g)</b></td>
          <td style="width:20%;text-align:center"><b>Balance Quantity(g)</b></td>
          <td style="width:10%;text-align:center"><b>Volume prepared(ml) </b></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          </tr>
          </table><br><br>
          <table cellpadding="5" border="0.1">
          <tr>
          <td style="width:100%;">Date:</td>
          </tr>
          <tr>
          <td style="width:100%;text-align:center">Media Distribution Record</td>
          </tr>
           <tr>
          <td style="width:20%;text-align:center"><b>In house lot number</b></td>
          <td style="width:80%;text-align:center"><b>Prepared Form<br>Plates(P)/Conical Flask(CF)/Bottles(B)/Test TubeS(TT)/Molten Agar(MA)</b></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center"></td>
          <td style="width:80%;text-align:center"></td>
          </tr>
          </table><br><br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;text-align:center"><b>Usage record of Prepared media/Readymade media</b></td>
          </tr>
          <tr>
          <td style="width:10%;text-align:center"><b>Date</b></td>
           <td style="width:10%;text-align:center"><b>Usage Code(U)</b></td>
            <td style="width:20%;text-align:center"><b>Used for testing</b></td>
             <td style="width:20%;text-align:center"><b>Quantity used </b></td>
              <td style="width:20%;text-align:center"><b>Remaining quantity</b></td>
               <td style="width:10%;text-align:center"><b>Done By</b></td>
                <td style="width:10%;text-align:center"><b>Checked By</b></td>
          </tr>
          <tr>
          <td style="width:10%;text-align:center"></td>
           <td style="width:10%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
               <td style="width:10%;text-align:center"></td>
                <td style="width:10%;text-align:center"></td>
                </tr>
          <tr>
          <td style="width:10%;text-align:center"></td>
           <td style="width:10%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
               <td style="width:10%;text-align:center"></td>
                <td style="width:10%;text-align:center"></td>
                </tr>
          </table><div></div><div></div> <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
          <br pagebreak="true"/>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:70%;">Bottle/in-house lot No.:SCDA</td>
           <td style="width:30%;">Date:</td>
          </tr>
         
          <tr>
          <td style="width:10%;text-align:center"><b>M-Code</b></td>
           <td style="width:20%;text-align:center"><b>Lot/B.No.</b></td>
            <td style="width:20%;text-align:center"><b>Use before</b></td>
             <td style="width:10%;text-align:center"><b>g/l</b></td>
              <td style="width:20%;text-align:center"><b>Volume(ml)</b></td>
               <td style="width:20%;text-align:center"><b>Media Weight(g)</b></td>
               </tr>
                <tr>
          <td style="width:10%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:10%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
               </tr>
          </table><br><br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:70%;">pH(lIMITS:7.10-7.50)</td>
           <td style="width:30%;">Distribution</td>
          </tr>
         
          <tr>
          <td style="width:20%;text-align:center" rowspan="2">After sterilization</td>
           <td style="width:20%;text-align:center" rowspan="2"></td>
            <td style="width:20%;text-align:center"><b>Container Type</b></td>
             <td style="width:20%;text-align:center"><b>No. of Container</b></td>
              <td style="width:20%;text-align:center"><b>ml container</b></td>
               
               </tr>
                <tr>
              <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
              </tr>
          </table>
         <br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:20%;text-align:center" rowspan="2"><b>Autoclave No./Load</b></td>
           <td style="width:20%;text-align:center"><b>Sterilization</b></td>
            <td style="width:20%;text-align:center"><b>Duration</b></td>
             <td style="width:20%;text-align:center"><b>Prepared By</b></td>
              <td style="width:20%;text-align:center"><b>Remarks</b></td>
          </tr>
          <tr>
         
           <td style="width:10%;text-align:center"><b>Temp oC</b></td>
           <td style="width:10%;text-align:center"><b>Pressure psi</b></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center" ></td>
           <td style="width:10%;text-align:center"></td>
           <td style="width:10%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
          </tr>
          </table><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;text-align:center"><b>Positive Control/Growth Promotion Test</b></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center"><b>Plantes used</b></td>
          <td style="width:20%;text-align:center"><b>Organism Used </b></td>
          <td style="width:10%;text-align:center"><b>Incubated at/ For</b></td>
          <td style="width:10%;text-align:center"><b>cfu/ml added</b></td>
          <td style="width:10%;text-align:center"><b>cfu/ml recovered</b></td>
          <td style="width:20%;text-align:center"><b>% of recovery(50%-200%)</b></td>
          <td style="width:10%;text-align:center"><b>Remark Sign C/NC</b></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          </tr>
          </table><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;text-align:center"><b>Sterility results of prepared media/Negative Control</b></td>
          </tr>
          
          <tr>
          <td style="width:10%;text-align:center"><b>Date</b></td>
          <td style="width:20%;text-align:center"><b>Container Used </b></td>
          <td style="width:20%;text-align:center"><b>Incubated at/ For</b></td>
          <td style="width:20%;text-align:center"><b>Observation</b></td>
          <td style="width:10%;text-align:center"><b>Remark Sign C/NC</b></td>
          <td style="width:20%;text-align:center"><b>Sign</b></td>
          
          </tr>
          <tr>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
         
          </tr>
          </table><br><br>
           <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:50%;text-align:center"></td>
           <td style="width:50%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:50%;text-align:center"><b>ANALYSED BY/ON</b></td>
           <td style="width:50%;text-align:center"><b>CHECKED BY/ON</b></td>
          </tr>
          </table><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
          <br pagebreak="true"/>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:70%;">Bottle/in-house lot No.:SCDM</td>
           <td style="width:30%;">Date:</td>
          </tr>
         
          <tr>
          <td style="width:10%;text-align:center"><b>M-Code</b></td>
           <td style="width:20%;text-align:center"><b>Lot/B.No.</b></td>
            <td style="width:20%;text-align:center"><b>Use before</b></td>
             <td style="width:10%;text-align:center"><b>g/l</b></td>
              <td style="width:20%;text-align:center"><b>Volume(ml)</b></td>
               <td style="width:20%;text-align:center"><b>Media Weight(g)</b></td>
               </tr>
                <tr>
          <td style="width:10%;text-align:center"></td>
           <td style="width:20%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:10%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
               <td style="width:20%;text-align:center"></td>
               </tr>
          </table><br><br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:70%;">pH(lIMITS:7.10-7.50)</td>
           <td style="width:30%;">Distribution</td>
          </tr>
         
          <tr>
          <td style="width:20%;text-align:center" rowspan="2">After sterilization</td>
           <td style="width:20%;text-align:center" rowspan="2"></td>
            <td style="width:20%;text-align:center"><b>Container Type</b></td>
             <td style="width:20%;text-align:center"><b>No. of Container</b></td>
              <td style="width:20%;text-align:center"><b>ml container</b></td>
               
               </tr>
                <tr>
              <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
              </tr>
          </table>
         <br>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:20%;text-align:center" rowspan="2"><b>Autoclave No./Load</b></td>
           <td style="width:20%;text-align:center"><b>Sterilization</b></td>
            <td style="width:20%;text-align:center"><b>Duration</b></td>
             <td style="width:20%;text-align:center"><b>Prepared By</b></td>
              <td style="width:20%;text-align:center"><b>Remarks</b></td>
          </tr>
          <tr>
         
           <td style="width:10%;text-align:center"><b>Temp oC</b></td>
           <td style="width:10%;text-align:center"><b>Pressure psi</b></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center" ></td>
           <td style="width:10%;text-align:center"></td>
           <td style="width:10%;text-align:center"></td>
            <td style="width:20%;text-align:center"></td>
             <td style="width:20%;text-align:center"></td>
              <td style="width:20%;text-align:center"></td>
          </tr>
          </table><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;text-align:center"><b>Positive Control/Growth Promotion Test</b></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center"><b>Plantes used</b></td>
          <td style="width:20%;text-align:center"><b>Organism Used </b></td>
          <td style="width:10%;text-align:center"><b>Incubated at/ For</b></td>
          <td style="width:10%;text-align:center"><b>cfu/ml added</b></td>
          <td style="width:10%;text-align:center"><b>cfu/ml recovered</b></td>
          <td style="width:20%;text-align:center"><b>% of recovery(50%-200%)</b></td>
          <td style="width:10%;text-align:center"><b>Remark Sign C/NC</b></td>
          </tr>
          <tr>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          </tr>
          </table><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:100%;text-align:center"><b>Sterility results of prepared media/Negative Control</b></td>
          </tr>
          
          <tr>
          <td style="width:10%;text-align:center"><b>Date</b></td>
          <td style="width:20%;text-align:center"><b>Container Used </b></td>
          <td style="width:20%;text-align:center"><b>Incubated at/ For</b></td>
          <td style="width:20%;text-align:center"><b>Observation</b></td>
          <td style="width:10%;text-align:center"><b>Remark Sign C/NC</b></td>
          <td style="width:20%;text-align:center"><b>Sign</b></td>
          
          </tr>
          <tr>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:20%;text-align:center"></td>
         
          </tr>
          </table><br><br>
           <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:50%;text-align:center"></td>
           <td style="width:50%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:50%;text-align:center"><b>ANALYSED BY/ON</b></td>
           <td style="width:50%;text-align:center"><b>CHECKED BY/ON</b></td>
          </tr>
          </table><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
          <br pagebreak="true"/>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;"><b>Formate Titale:</b></td>
           <td style="width:75%;"><b>Media Receipt Record</b></td>
          </tr>
           <tr>
          <td style="width:25%;"><b>Formate No.:</b></td>
           <td style="width:25%;">F/SOP/QM/009/01-00</td>
           <td style="width:25%;"><b>Page No.:</b></td>
           <td style="width:25%;"></td>
          </tr>
          <tr>
          <td style="width:25%;"><b>Ref.SOP No.:</b></td>
           <td style="width:75%;">SOP/QM/009</td>
          </tr>
          </table><div></div>
          <table cellpadding="5" border="0.1">
          <tr>
          <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:10%;text-align:center"><b>Name 0f Media</b></td>
          <td style="width:10%;text-align:center"><b>Make and Code No.</b></td>
          <td style="width:7%;text-align:center"><b>Batch No.</b></td>
          <td style="width:9%;text-align:center"><b>Expiry Date</b></td>
          <td style="width:10%;text-align:center"><b>Date of receipt</b></td>
          <td style="width:8%;text-align:center"><b>Received by</b></td>
          <td style="width:10%;text-align:center"><b>Date of opening</b></td>
          <td style="width:8%;text-align:center"><b>Approved on</b></td>
          <td style="width:8%;text-align:center"><b>Used From</b></td>
          <td style="width:8%;text-align:center"><b>Used upto</b></td>
          <td style="width:7%;text-align:center"><b>Done by</b></td>
          </tr>
           <tr>
          <td style="width:5%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:7%;text-align:center"></td>
          <td style="width:9%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:8%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:8%;text-align:center"></td>
          <td style="width:8%;text-align:center"></td>
          <td style="width:8%;text-align:center"></td>
          <td style="width:7%;text-align:center"></td>
          </tr>
        </table><div></div><div></div><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>
          <br pagebreak="true"/>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;"><b>Formate Titale:</b></td>
           <td style="width:75%;"><b>Media Receipt Record</b></td>
          </tr>
           <tr>
          <td style="width:25%;"><b>Formate No.:</b></td>
           <td style="width:25%;">F/SOP/QM/009/01-00</td>
           <td style="width:25%;"><b>Page No.:</b></td>
           <td style="width:25%;"></td>
          </tr>
          <tr>
          <td style="width:25%;"><b>Ref.SOP No.:</b></td>
           <td style="width:75%;">SOP/QM/009</td>
          </tr>
          </table><div></div>
         
          <table cellpadding="5" border="0.1">
          <tr>
          <td style="width:100%">Equipment ID No.:</td>
          </tr><div></div>
          <tr>
          <td style="width:5%;text-align:center" rowspan="2"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"rowspan="2"><b>Date</b></td>
          <td style="width:10%;text-align:center"rowspan="2"><b>Loade</b></td>
          <td style="width:10%;text-align:center"rowspan="2"><b>Cycle No.</b></td>
          <td style="width:20%;text-align:center"><b>Parameter</b></td>
          <td style="width:20%;text-align:center"><b>Operation of cycle</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Done by</b></td>
          <td style="width:10%;text-align:center" rowspan="2"><b>Checked by</b></td>
          
          </tr>
           <tr>
          
          <td style="width:7%;text-align:center">Temp(oC)</td>
            <td style="width:6%;text-align:center">Time(mins)</td>
              <td style="width:7%;text-align:center">Pressure(lbs)</td>
          <td style="width:6%;text-align:center">Start on</td>
            <td style="width:6%;text-align:center">Hold on</td>
              <td style="width:8%;text-align:center">Completed on</td>
          </tr>
           <tr>
          <td style="width:5%;text-align:center" ></td>
          <td style="width:15%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:10%;text-align:center"></td>
          <td style="width:7%;text-align:center"></td>
            <td style="width:6%;text-align:center"></td>
              <td style="width:7%;text-align:center"></td>
          <td style="width:6%;text-align:center"></td>
            <td style="width:6%;text-align:center"></td>
              <td style="width:8%;text-align:center"></td>
          <td style="width:10%;text-align:center" ></td>
          <td style="width:10%;text-align:center" ></td>
          
          </tr>
        </table><div></div><div></div><div></div>
          <table cellpadding="3" border="0.1">
          <tr>
          <td style="width:25%;text-align:center"><b></b></td>
           <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
            <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
             <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
          </tr>
           <tr>
          <td style="width:25%;text-align:center"><b>Name</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Sign/Date</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Designation</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          <tr>
          <td style="width:25%;text-align:center"><b>Department</b></td>
           <td style="width:25%;text-align:center"></td>
            <td style="width:25%;text-align:center"></td>
             <td style="width:25%;text-align:center"></td>
          </tr>
          </table>';
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Temperature.pdf', 'I');
  
    
  }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>

