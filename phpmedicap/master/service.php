<?php


ini_set('display_startup_errors', 1);
error_reporting(E_ALL);










require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveService") {
        
        $sql = "INSERT INTO service ( plant_id, department, service_code, service_type, service_desp, service_title, entry_by, entry_date, status,gst_per,sac_code ) 
        VALUES ('".$_GET["plant_id"]."','".$input["department"]."', '".$input["service_code"]."', '".$input["service_type"]."', 
        '".$input["service_desp"]."', '".$input["service_title"]."','".$_GET["emp_id"]."', '".$entry_date."', 'Approved',
        '".$input["tax"]."', '".$input["sac_code"]."')";
   
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "deleteService") {
        $sql = "UPDATE service SET status='".$_GET["status"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "updateService") {
        $sql = "UPDATE service SET department='".$input["department"]."', service_type='".$input["service_type"]."', service_title='".$input["service_title"]."',service_code='".$input["service_code"]."', service_desp='".$input["service_desp"]."' WHERE id='".$input["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "getService") {
        $output = array();
        $plant = $_GET["plant_id"];
        $sql = "SELECT s.*, TRIM(CONCAT(COALESCE(e.firstname,''),' ',COALESCE(e.middlename,''),' ',COALESCE(e.lastname,''))) AS entryByName
                FROM service s
                LEFT JOIN employee e ON e.emp_id = s.entry_by AND e.plant_id = s.plant_id
                WHERE s.plant_id='".$_GET["plant_id"]."' ORDER BY s.id DESC";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadService") {
        $_GET['filename'] = 'Service'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Service</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:15%;">Material Type</td>
                    <td style="width:12%;">Material Code</td>
                    <td style="width:15%;">Material Name</td>
                    <td style="width:8%;">Unit</td>
                    <td style="width:10%;">Lead Time</td>
                    <td style="width:15%;">Purchase Exceed Limit</td>
                    <td style="width:10%;">Gst</td>
                    <td style="width:10%;">Hsn</td>
                </tr>
            </thead>';
            $sql = "SELECT * FROM service";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $i=1;
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width:5%;">'.$i.'.</td>
                            <td style="width:15%;">'.$row['material_type'].'</td>
                            <td style="width:12%;">'.$row['material_code'].'</td>
                            <td style="width:15%;">'.$row['material_name'].'</td>
                            <td style="width:8%;">'.$row['unit'].'</td>
                            <td style="width:10%;">'.$row['lead_time'].'</td>
                            <td style="width:15%;">'.$row['exceed_limit'].'</td>
                            <td style="width:10%;">'.$row['gst'].'</td>
                            <td style="width:10%;">'.$row['hsn'].'</td>
                        </tr>';
                    $i++;
                }
            }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Service.pdf', 'I');
    }

}

$conn->close();
?>