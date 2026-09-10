<?php



// ini_set('display_errors', 1);
// error_reporting(E_ALL);



require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);


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
    $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
    [$emp_id, $department] = explode("$", $string);

    $conn->query("INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR)
        VALUES ('FRONTEND', '$token', '".$_GET["type"]."', '$entry_date', '$department', '$emp_id',
        '".$_SERVER['REQUEST_METHOD']."', '".$_SERVER['REMOTE_ADDR']."')");

    $type = $_GET["type"];


    if ($type === "saveDMRecord") {
        
        $sql = "INSERT INTO `dm_cleaning_log`(`plant_id`, `date`, `dm_stop`, `fp_start`, `fp_stop`, `inspection`, `bf_start`, `bf_stop`, `agent_used`, `dm_start`,
        `done_by`, `checked_by`, `remarks`) VALUES ('".$_GET["plant_id"]."','".$input["date"]."', '".$input["dm_stop"]."', '".$input["fp_start"]."','".$input["fp_stop"]."', 
        '".$input["inspection"]."', '".$input["bf_start"]."', '".$input["bf_stop"]."', '".$input["agent_used"]."', '".$input["dm_start"]."',
        '".$input["done_by"]."', 'Pending','".$input["remarks"]."')";

        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    else if ($type === "getDMRecords") {
        $output = array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_byName From employee e WHERE e.emp_id = a.done_by limit 1) as done_byName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS checked_byName From employee e WHERE e.emp_id = a.checked_by limit 1) as checked_byName
        FROM dm_cleaning_log a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($type === "checkPressBasket") {
        
        $sql = "UPDATE `dm_cleaning_log` SET  `checked_by` = '".$_GET["emp_id"]."' where id = '".$input["id"]."' ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    
    else if ($type === "saveAlumDosing") {
        
        $sql = "INSERT INTO `alumDosingLog`(`plant_id`, `date`, `arNo`, `alumQty12Hour`, `dilutionQtyAlum`, `startTime`,`stopTime`, `done_by`,`remarks`, 
        `entryBy`, `entryOn`,`verifiedBy`) VALUES ('".$_GET["plant_id"]."','".$input["date"]."','".$input["arNo"]."','".$input["alumQty12Hour"]."',
        '".$input["dilutionQtyAlum"]."','".$input["startTime"]."','".$input["stopTime"]."','".$input["done_by"]."','".$input["remarks"]."',
        '".$_GET["emp_id"]."','$entry_date','Pending') ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "verifyAlumDosing") {
        
        $sql = "UPDATE `alumDosingLog` SET  `verifiedBy` = '".$_GET["emp_id"]."',`verifyOn` = '$entry_date' where id = '".$input["id"]."' ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "getAlumDosingLog") {
        
        $output = array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_by_name From employee e WHERE e.emp_id = a.done_by limit 1) as done_by_name,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS verifiedByName From employee e WHERE e.emp_id = a.verifiedBy limit 1) as verifiedByName
        FROM alumDosingLog a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($type === "saveHypoDosing") {
        
        $sql = "INSERT INTO `hypoDosingLog`(`plant_id`, `date`, `arNo`, `hypoQty`, `dilutionQtyHypo`, `startTime`,`stopTime`, `done_by`,`remarks`, 
        `entryBy`, `entryOn`,`verifiedBy`) VALUES ('".$_GET["plant_id"]."','".$input["date"]."','".$input["arNo"]."','".$input["hypoQty"]."',
        '".$input["dilutionQtyHypo"]."','".$input["startTime"]."','".$input["stopTime"]."','".$input["done_by"]."','".$input["remarks"]."',
        '".$_GET["emp_id"]."','$entry_date','Pending') ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "verifyHypoDosing") {
        
        $sql = "UPDATE `hypoDosingLog` SET  `verifiedBy` = '".$_GET["emp_id"]."',`verifyOn` = '$entry_date' where id = '".$input["id"]."' ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "getHypoDosingLog") {
        
        $output = array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_by_name From employee e WHERE e.emp_id = a.done_by limit 1) as done_by_name,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS verifiedByName From employee e WHERE e.emp_id = a.verifiedBy limit 1) as verifiedByName
        FROM hypoDosingLog a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($type === "saveSin") {
        
        $sql = "INSERT INTO `sampleInformationNote`(`plant_id`, `date`, `sinNo`, `forQc`, `equipmentUsed`, `issuedBy`,`issuedOn`, `reacievedBy`,`entryBy`, 
        `entryOn`) VALUES ('".$_GET["plant_id"]."','".$input["date"]."','".$input["sinNo"]."','".$input["forQc"]."','".$input["equipmentUsed"]."',
        '".$input["issuedBy"]."','".$input["issuedOn"]."','Pending','".$_GET["emp_id"]."','$entry_date') ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "receivedSin") {
        
        $sql = "UPDATE `sampleInformationNote` SET  `reacievedBy` = '".$_GET["emp_id"]."',`reacievedOn` = '$entry_date' where id = '".$input["id"]."' ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "getSinLog") {
        
        $output = array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS issuedByName From employee e WHERE e.emp_id = a.issuedBy limit 1) as issuedByName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS reacievedByName From employee e WHERE e.emp_id = a.reacievedBy limit 1) as reacievedByName,
        (SELECT TRIM(CONCAT_WS(' ', equipment_name, equipment_code )) AS equipmentUsedName From equipment e WHERE e.equipment_code = a.equipmentUsed limit 1) as equipmentUsedName
        FROM sampleInformationNote a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($type === "savePortableWaterSanititazation") {
        
        $sql = "INSERT INTO `portableWaterSanitazation`(`plant_id`, `date`, `sanitizingAgentUsed`, `sanitizationStartedOn`, `sanitizationConpletedOn`, `done_by`,
        `remarks`,`checkedBy`,`entryBy`,`entryOn`) VALUES ('".$_GET["plant_id"]."','".$input["date"]."','".$input["sanitizingAgentUsed"]."','".$input["sanitizationStartedOn"]."',
        '".$input["sanitizationConpletedOn"]."','".$input["done_by"]."','".$input["remarks"]."','Pending','".$_GET["emp_id"]."','$entry_date') ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "checkPortableWaterSanititazation") {
        
        $sql = "UPDATE `portableWaterSanitazation` SET  `checkedBy` = '".$_GET["emp_id"]."',`checkedOn` = '$entry_date' where id = '".$input["id"]."' ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "getPortableWaterSanititazation") {
        
        $output = array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_byName From employee e WHERE e.emp_id = a.done_by limit 1) as done_byName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS checkedByName From employee e WHERE e.emp_id = a.checkedBy limit 1) as checkedByName
        FROM portableWaterSanitazation a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($type === "saveSinDetails") {
        
        $sql = "INSERT INTO `sinDetails`(`plant_id`, `date`, `shift`, `tankNo`, `sinNo`, `givenBy`,`receivedBy`,`remarks`,`entryBy`,`entryOn`) VALUES 
        ('".$_GET["plant_id"]."','".$input["date"]."','".$input["shift"]."','".$input["tankNo"]."','".$input["sinNo"]."','".$input["givenBy"]."',
        '".$input["receivedBy"]."','".$input["remarks"]."','".$_GET["emp_id"]."','$entry_date') ";
 
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
            
    }
    else if ($type === "getSinDetailsLog") {
        
        $output = array();
        $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS givenByName From employee e WHERE e.emp_id = a.givenBy limit 1) as givenByName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS receivedByName From employee e WHERE e.emp_id = a.receivedBy limit 1) as receivedByName
        FROM sinDetails a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    

    else if ($type === "downloadDMCleaningPDF") {
        $_GET['filename'] = 'DM Filter Cleaning Log';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">
        DM FILTER, FILTER PRESS, BASKET FILTER CLEANING LOG</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <th>Sr. No.</th>
                    <th>Date</th>
                    <th>DM Filter Stop</th>
                    <th>Filter Press Start</th>
                    <th>Filter Press Stop</th>
                    <th>Inspection of Filter Clothes</th>
                    <th>Basket Filter Start</th>
                    <th>Basket Filter Stop</th>
                    <th>Cleaning Agent Used</th>
                    <th>DM Filter Start</th>
                    <th>Done By ENG</th>
                    <th>Checked By ENG</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>';

        $result = $conn->query("SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_byName From employee e WHERE e.emp_id = a.done_by limit 1) as done_byName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS checked_byName From employee e WHERE e.emp_id = a.checked_by limit 1) as checked_byName
        FROM dm_cleaning_log a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC");
        $i = 1;
        while ($row = $result->fetch_assoc()) {
          $html .= '<tr>
                <td>'.$i++.'</td>
                <td>'.$row["date"].'</td>
                <td>'.$row["dm_stop"].'</td>
                <td>'.$row["fp_start"].'</td>
                <td>'.$row["fp_stop"].'</td>
                <td>'.$row["inspection"].'</td>
                <td>'.$row["bf_start"].'</td>
                <td>'.$row["bf_stop"].'</td>
                <td>'.$row["agent_used"].'</td>
                <td>'.$row["dm_start"].'</td>
                <td>'.$row["done_byName"].'</td>
                <td>'.$row["checked_byName"].'</td>
                <td>'.$row["remarks"].'</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('DM_Cleaning_Log.pdf', 'I');
    }
    
    else if ($type === "downloadAlumLog") {
        
        $_GET['filename'] = 'AlumDosingLog';
        
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">LOG BOOK FOR ALUM DOSING</h3> <div></div>
        
                <table border="1" cellpadding="4">
                    <tr>
                        <td style="width:60px; font-weight: bold;background-color: aquamarine;" rowspan="2">DATE</td>
                        <td style="width:80px; font-weight: bold;background-color: aquamarine;" rowspan="2">Medicap lot no</td>
                        <td style="width:70px; font-weight: bold;background-color: aquamarine;" rowspan="2">Alum Qty for 12 hrs</td>
                        <td style="width:75px; font-weight: bold;background-color: aquamarine;" rowspan="2">Dilution Qty of water for Alum</td>
                        <td style="width:110px; font-weight: bold;background-color: aquamarine;" colspan="2">Time for Alum dosing</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;" rowspan="2">Verify by ENG</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;" rowspan="2">Done by ENG</td>
                        <td style="width:200px; font-weight: bold;background-color: aquamarine;" rowspan="2">Remarks</td>
                     </tr>
                    <tr>
                        <td style="font-weight: bold;background-color: aquamarine;">Start</td>
                        <td style="font-weight: bold;background-color: aquamarine;">Stop</td>
                    </tr>
               ';    
                
            $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_by_name From employee e WHERE e.emp_id = a.done_by limit 1) as done_by_name,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS verifiedByName From employee e WHERE e.emp_id = a.verifiedBy limit 1) as verifiedByName
        FROM alumDosingLog a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
               $html .= '<tr>
                            <td style="text-align: left;">'.date('d-m-Y', strtotime($row["date"])).'</td>
                            <td style="text-align: left;">'.$row["arNo"].'</td>
                            <td style="text-align: left;">'.$row["alumQty12Hour"].'</td>
                            <td style="text-align: left;">'.$row["dilutionQtyAlum"].'</td>
                            <td style="text-align: left;">'.$row["startTime"].'</td>
                            <td style="text-align: left;">'.$row["stopTime"].'</td>
                            <td style="text-align: left;">'.$row["done_by_name"].' </td>
                            <td style="text-align: left;">'.$row["verifiedByName"].'</td>
                            <td style="text-align: left;">'.$row["remarks"].' </td>
                         </tr>';
                     
                }
            }
            
            $html .= ' </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('AlumDosingLog.pdf', 'I');
        
    }
    
    else if ($type === "downloadHypoLog") {
        
        $_GET['filename'] = 'hypoDosingLog';
        
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">LOG BOOK FOR Hypo DOSING</h3> <div></div>
        
                <table border="1" cellpadding="4">
                    <tr>
                        <td style="width:60px; font-weight: bold;background-color: aquamarine;" rowspan="2">DATE</td>
                        <td style="width:80px; font-weight: bold;background-color: aquamarine;" rowspan="2">Medicap lot no</td>
                        <td style="width:70px; font-weight: bold;background-color: aquamarine;" rowspan="2">Hypo Qty.</td>
                        <td style="width:75px; font-weight: bold;background-color: aquamarine;" rowspan="2">Dilution Qty of water for Hypo</td>
                        <td style="width:110px; font-weight: bold;background-color: aquamarine;" colspan="2">Time for Hypo dosing</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;" rowspan="2">Verify by ENG</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;" rowspan="2">Done by ENG</td>
                        <td style="width:200px; font-weight: bold;background-color: aquamarine;" rowspan="2">Remarks</td>
                     </tr>
                    <tr>
                        <td style="font-weight: bold;background-color: aquamarine;">Start</td>
                        <td style="font-weight: bold;background-color: aquamarine;">Stop</td>
                    </tr>
               ';    
                
            $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_by_name From employee e WHERE e.emp_id = a.done_by limit 1) as done_by_name,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS verifiedByName From employee e WHERE e.emp_id = a.verifiedBy limit 1) as verifiedByName
        FROM hypoDosingLog a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
               $html .= '<tr>
                            <td style="text-align: left;">'.date('d-m-Y', strtotime($row["date"])).'</td>
                            <td style="text-align: left;">'.$row["arNo"].'</td>
                            <td style="text-align: left;">'.$row["hypoQty"].'</td>
                            <td style="text-align: left;">'.$row["dilutionQtyHypo"].'</td>
                            <td style="text-align: left;">'.$row["startTime"].'</td>
                            <td style="text-align: left;">'.$row["stopTime"].'</td>
                            <td style="text-align: left;">'.$row["done_by_name"].' </td>
                            <td style="text-align: left;">'.$row["verifiedByName"].'</td>
                            <td style="text-align: left;">'.$row["remarks"].' </td>
                         </tr>';
                     
                }
            }
            
            $html .= ' </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('hypoDosingLog.pdf', 'I');
        
    }
    
    else if ($type === "downloadSin") {
        
        $_GET['filename'] = 'sin';
        
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '';    
                
            $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS issuedByName From employee e WHERE e.emp_id = a.issuedBy limit 1) as issuedByName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS reacievedByName From employee e WHERE e.emp_id = a.reacievedBy limit 1) as reacievedByName,
        (SELECT TRIM(CONCAT_WS(' ', equipment_name, equipment_code )) AS equipmentUsedName From equipment e WHERE e.equipment_code = a.equipmentUsed limit 1) as equipmentUsedName
        FROM sampleInformationNote a where  a.id = '".$_GET['id']."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
               $html .= ' 
               
               
                <table border="1" cellpadding="10">
                    <tr>
                        <td  colspan="2" style="text-align: center;font-weight: bold;font-size:17px;" >SAMPLE INFORMATION NOTE</td>
                    </tr>
                    <tr>
                        <td style="width:50%; font-weight: bold;text-align: left;"> SIN NO : <span style="color:blue;"> '.$row["sinNo"].'</span></td>
                        <td style="width:50%; font-weight: bold;text-align: left;"> Date : <span style="color:blue;"> '.date('d-m-Y', strtotime($row["date"])).'</span></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight: bold;text-align: left;"> To, QC <br> <br> Please collect the sample of 
                            <span style="color:blue;"> '.$row["forQc"].'</span>  <br> <br>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:20%; font-weight: bold;text-align: left;"> Equipment :</td>
                        <td style="width:80%; font-weight: bold;text-align: left;color:blue;">'.$row["equipmentUsedName"].' </td>
                    </tr>
                    <tr>
                        <td style="width:50%; font-weight: bold;text-align: left;"> 
                            SIN Issued By : <span style="color:blue;"> '.$row["issuedByName"].'</span> <br><br>
                            Date : <span style="color:blue;"> '.date('d-m-Y', strtotime($row["issuedOn"])).' </span>
                        </td>
                        <td style="width:50%; font-weight: bold;text-align: left;"> 
                            SIN and Sample Received By QC : <span style="color:blue;"> '.$row["reacievedByName"].'</span> <br><br>
                            Date : <span style="color:blue;"> '.date('d-m-Y', strtotime($row["reacievedOn"])).' </span>
                        </td>
                     </tr>
               
                </table>
                
               ';
                     
                }
            }
            
           
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('sin.pdf', 'I');
        
    }
    
    else if ($type === "downloadPortableWaterSanititazation") {
        
        $_GET['filename'] = 'portableWaterSanititazation';
        
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">LOG BOOK FOR SANITIZATION OF POTABLE WATER PIPE LINES</h3> <div></div>
        
                <table border="1" cellpadding="4">
                    <tr>
                        <td style="width:60px; font-weight: bold;background-color: aquamarine;">Sr.No.</td>
                        <td style="width:80px; font-weight: bold;background-color: aquamarine;">Date of Sanitization</td>
                        <td style="width:110px; font-weight: bold;background-color: aquamarine;">Sanitizing Agent Used</td>
                        <td style="width:75px; font-weight: bold;background-color: aquamarine;">Sanitization Started on (Time)</td>
                        <td style="width:75px; font-weight: bold;background-color: aquamarine;">Sanitization Completed on (Time)</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;">Sanitization Done By ENG</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;">Checked By ENG</td>
                        <td style="width:205px; font-weight: bold;background-color: aquamarine;">Remarks</td>
                     </tr>
                 
               ';    
                $i = 1;
            $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS done_byName From employee e WHERE e.emp_id = a.done_by limit 1) as done_byName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS checkedByName From employee e WHERE e.emp_id = a.checkedBy limit 1) as checkedByName
        FROM portableWaterSanitazation a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
               $html .= '<tr>
                            <td style="text-align: left;">'.$i.'</td>
                            <td style="text-align: left;">'.date('d-m-Y', strtotime($row["date"])).'</td>
                            <td style="text-align: left;">'.$row["sanitizingAgentUsed"].'</td>
                            <td style="text-align: left;">'.$row["sanitizationStartedOn"].'</td>
                            <td style="text-align: left;">'.$row["sanitizationConpletedOn"].'</td>
                            <td style="text-align: left;">'.$row["done_byName"].'</td>
                            <td style="text-align: left;">'.$row["checkedByName"].'</td>
                            <td style="text-align: left;">'.$row["remarks"].' </td>
                          </tr>';
                     $i++;
                }
            }
            
            $html .= ' </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('portableWaterSanititazation.pdf', 'I');
        
    }
    
    else if ($type === "downloadSinDetails") {
        
        $_GET['filename'] = 'sinDetails';
        
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;background-color:#DDDAD9;font-weight:bold;">LOG BOOK FOR SIN DETAILS</h3> <div></div>
        
                <table border="1" cellpadding="4">
                    <tr>
                        <td style="width:40px; font-weight: bold;background-color: aquamarine;">Sr.No.</td>
                        <td style="width:60px; font-weight: bold;background-color: aquamarine;">Date</td>
                        <td style="width:160px; font-weight: bold;background-color: aquamarine;">Shift</td>
                        <td style="width:70px; font-weight: bold;background-color: aquamarine;">Tank No.</td>
                        <td style="width:70px; font-weight: bold;background-color: aquamarine;">SIN No.</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;">Given By</td>
                        <td style="width:90px; font-weight: bold;background-color: aquamarine;">Received By</td>
                        <td style="width:205px; font-weight: bold;background-color: aquamarine;">Remarks</td>
                     </tr>
                 
               ';    
                $i = 1;
            $sql = "SELECT a.*,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS givenByName From employee e WHERE e.emp_id = a.givenBy limit 1) as givenByName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname, emp_id )) AS receivedByName From employee e WHERE e.emp_id = a.receivedBy limit 1) as receivedByName
        FROM sinDetails a where  a.plant_id = '".$_GET['plant_id']."' ORDER BY a.date DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
               $html .= '<tr>
                            <td >'.$i.'</td>
                            <td style="text-align: left;">'.date('d-m-Y', strtotime($row["date"])).'</td>
                            <td style="text-align: left;">'.$row["shift"].'</td>
                            <td style="text-align: left;">'.$row["tankNo"].'</td>
                            <td style="text-align: left;">'.$row["sinNo"].'</td>
                            <td style="text-align: left;">'.$row["givenByName"].'</td>
                            <td style="text-align: left;">'.$row["receivedByName"].'</td>
                            <td style="text-align: left;">'.$row["remarks"].' </td>
                          </tr>';
                     $i++;
                }
            }
            
            $html .= ' </table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('sinDetails.pdf', 'I');
        
    }

} else {
    echo "{\"status\":\"Invalid Token\"}";
}

$conn->close();
?>
