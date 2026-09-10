<?php
//   ini_set('display_errors', 1);
//     error_reporting(E_ALL); 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

// Token validation
$sql = "SELECT * FROM token WHERE token='" . $token . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
    $string = explode("$", $string);
    $_GET["emp_id"] = $string[0];
    $_GET["department"] = $string[1];

    // Log action
    $logSql = "INSERT INTO log (process, token, action, actiontime, department, emp_id, method, REMOTE_ADDR)
               VALUES ('FRONTEND', '$token', '" . $_GET["type"] . "', '$entry_date', '" . $_GET["department"] . "', '" . $_GET["emp_id"] . "', '" . $_SERVER['REQUEST_METHOD'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')";
    $conn->query($logSql);

    // Handle save
    if ($_GET["type"] == "saveFireRecord") {
    //       ini_set('display_errors', 1);
    // error_reporting(E_ALL); 
        $sql = "INSERT INTO fire_extinguisher (
                     location,
                     type,
                     cylinder,
                     refill_date,
                     last_service_date,
                     checked_by,
                     entry_by,
                     entry_date,
                     entry_time
                ) VALUES (
                    '" . $input["location"] . "',
                    '" . $input["type"] . "',
                      '" . $input["cylinder_no"] . "',
                    '" . $input["refill_date"] . "',
                    '" . $input["valid_upto"] . "',
                    '" . $input["checked_by"] . "',
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

 else if ($_GET["type"] == "saveLocation") {
    //       ini_set('display_errors', 1);
    // error_reporting(E_ALL); 
        $sql = "INSERT INTO fire_extinguisher_location (
                     location,
                     type,
                     department,
                     entry_by,
                     entry_date,
                     entry_time
                ) VALUES (
                    '" . $input["location"] . "',
                    '" . $input["type"] . "',
                      '" . $input["department"] . "',
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
    // Handle fetch
    else if ($_GET["type"] == "getFireRecords") {
        $output = [];
        $sql = "SELECT * FROM fire_extinguisher ORDER BY entry_date DESC";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getlocation") {
        $output = [];
            $sql = "SELECT * FROM fire_extinguisher_location order by 1 desc";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "get_Location") {
        $output = [];
            $sql = "SELECT * FROM fire_extinguisher_location order by 1 desc";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getType") {
        $output = [];
            $sql = "SELECT * FROM others_material WHERE material_name='Fire Extinguisher_CX' order by 1 desc";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        echo json_encode($output);
    }
   else if ($_GET["type"] == "getGeneralMaterials1") {
        $output = array();
        $plant = $_GET["plant_id"];
 
        $sql = "SELECT * FROM others_material WHERE plant_id='".$_GET["plant_id"]."' 
        and material_type='".$_GET["material_type"]."' order by 1 desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['pack_size'] = json_decode($row['pack_size']);
                $row['chem_manufacturer'] = json_decode($row['chem_manufacturer']);
             
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 


    // Handle download PDF
    else if ($_GET["type"] == "downloadFirePDF") {
        $_GET['filename'] = 'Fire Extinguisher Inspection Log';
        $_GET['pdftype'] = 'landscape';
        include("../pdfimp2.php");

        $html = '<h3 style="text-align:center;">Fire Extinguisher Log</h3>
                <table border="1" cellpadding="5">
                    <thead>
                      <tr style="background-color:#DDDAD9;">
                            <th>Sr. No</th>
                            <th>Type</th>
                            <th>Location</th> <th>Cylinder No.</th>
                            <th>Refill Date</th>
                            <th>Valid Up To</th>
                            <th>Checked_by</th>
                            
                            <th>Entry Date</th>
                        </tr>
                    </thead><tbody>';

   $sql = "SELECT * FROM fire_extinguisher ORDER BY entry_date DESC";
$result = $conn->query($sql);
$i = 1;

while ($row = $result->fetch_assoc()) {
    $html .= "<tr>
                <td>" . $i++ . "</td>
                <td>{$row['type']}</td>
                <td>{$row['location']}</td>
                <td>{$row['cylinder']}</td>
                <td>{$row['refill_date']}</td>
                <td>{$row['last_service_date']}</td>
             
                <td>{$row['entry_by']}</td>
                <td>{$row['entry_date']}</td>
            </tr>";
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('Fire-Extinguishers.pdf', 'I');
    }

} else {
    echo json_encode(["status" => "Invalid Token"]);
}

$conn->close();
?>
