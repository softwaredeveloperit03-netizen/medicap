<?php



require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

// Token validation

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
    
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

    // 1. Save Record
   if ($_GET["type"] === "saveExtinguisherRecord") {

   
$sql = "INSERT INTO extinguisher_log (
    ext_id, location, type, capacity, `condition`, nozzle, pressure, refillDate, entry_by, entry_date, entry_time
) VALUES (
    '" . $input["equipment_code"] . "',
    '" . $input["location"] . "',
    '" . $input["equipment_type"] . "',
    '" . $input["capacity"] . "',
    '" . $input["condition"] . "',
    '" . $input["nozzle"] . "',
    '" . $input["pressure"] . "',
    '" . $input["refillDate"] . "',
    '" . $_GET["emp_id"] . "',
    '$entry_date',
    '$entry_time'
)";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => $conn->error]);
    }
}


    // 2. Verify Entry
    else if ($_GET["type"] === "saveVerifyEntry") {
        //   ini_set('display_errors', 1);
        //   error_reporting(E_ALL); 
        $sql = "UPDATE extinguisher_log SET 
            verified_by ='" . $input["checked_by"] . "',
            remark='" . $input["remark"] . "',
            verified_on = '$entry_date'
            WHERE id = '" . $_GET["id"] . "'";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "failed", "error" => $conn->error]);
        }
    }

    // 3. Get All Records
    else if ($_GET["type"] === "getExtinguisherRecords") {
        $output = [];
        $sql = "SELECT * FROM extinguisher_log ";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] === "getEquipment") {
        $output = [];
        $sql = "SELECT * FROM equipment WHERE equipment_name LIKE '%fire%'; ";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
  else if ($_GET["type"] === "getFire_extinguisher") {
        $output = [];
        $sql = "SELECT * FROM others_material WHERE material_name LIKE '%fire_extinguisher%'; ";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }

    // 4. Download PDF
    else if ($_GET["type"] === "downloadExtinguisher") {
        $_GET['filename'] = 'Extinguisher Log Book';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;">Fire Extinguisher Log Book</h3>
                 <table border="1" cellpadding="5">
                    <thead>
                      <tr style="background-color:#DDDAD9;">
                        <th>Sr. No</th>
                        <th>Date</th>
                        <th>Extinguisher ID</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Condition</th>
                        <th>Nozzle</th>
                        <th>Pressure</th>
                        <th>Checked By</th>
                        <th>Verified By</th>
                      </tr>
                    </thead><tbody>';

        $sql = "SELECT * FROM extinguisher_log ORDER BY date DESC";
        $result = $conn->query($sql);
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>
                        <td>" . $i++ . "</td>
                        <td>{$row['date']}</td>
                        <td>{$row['ext_id']}</td>
                        <td>{$row['location']}</td>
                        <td>{$row['type']}</td>
                        <td>{$row['capacity']}</td>
                        <td>{$row['condition']}</td>
                        <td>{$row['nozzle']}</td>
                        <td>{$row['pressure']}</td>
                        <td>{$row['checked_by']}</td>
                        <td>{$row['verified_by']}</td>
                      </tr>";
        }

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Extinguisher-Log.pdf', 'I');
    }

} else {
    echo json_encode(["status" => "Invalid Token"]);
}

$conn->close();
?>
