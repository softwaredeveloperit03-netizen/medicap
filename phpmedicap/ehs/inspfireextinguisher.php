<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

// ini_set('display_errors', 1);
// error_reporting(E_ALL);


$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='".$token."'";
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

    $conn->query("CREATE TABLE IF NOT EXISTS ehs_inspection_perform_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) DEFAULT NULL,
        equipment_maintenance_id INT DEFAULT NULL,
        equipment_id INT DEFAULT NULL,
        equipment_code VARCHAR(100) DEFAULT NULL,
        equipment_name VARCHAR(255) DEFAULT NULL,
        location VARCHAR(255) DEFAULT NULL,
        inspection_date DATE DEFAULT NULL,
        observation TEXT,
        description TEXT,
        remark TEXT,
        performed_by VARCHAR(50) DEFAULT NULL,
        performed_by_name VARCHAR(255) DEFAULT NULL,
        due_type VARCHAR(50) DEFAULT NULL,
        entry_date DATETIME DEFAULT NULL,
        verified_by VARCHAR(50) DEFAULT NULL,
        verified_by_name VARCHAR(255) DEFAULT NULL,
        verified_on DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

    // Add missing columns only if they do not already exist (safe for mysqli exceptions)
    $ehsLogCols = array();
    $colRes = $conn->query("SHOW COLUMNS FROM ehs_inspection_perform_log");
    if ($colRes) {
        while ($colRow = $colRes->fetch_assoc()) {
            $ehsLogCols[$colRow['Field']] = true;
        }
    }
    if (!isset($ehsLogCols['verified_by'])) {
        $conn->query("ALTER TABLE ehs_inspection_perform_log ADD COLUMN verified_by VARCHAR(50) DEFAULT NULL");
    }
    if (!isset($ehsLogCols['verified_by_name'])) {
        $conn->query("ALTER TABLE ehs_inspection_perform_log ADD COLUMN verified_by_name VARCHAR(255) DEFAULT NULL");
    }
    if (!isset($ehsLogCols['verified_on'])) {
        $conn->query("ALTER TABLE ehs_inspection_perform_log ADD COLUMN verified_on DATETIME DEFAULT NULL");
    }

    if ($_GET["type"] == "GetInspectionRecord") {
        $output = array();
        $plantFilter = "";
        if (isset($_GET["plant_id"]) && $_GET["plant_id"] !== '') {
            $plantFilter = " WHERE plant_id='".$conn->real_escape_string($_GET["plant_id"])."' ";
        }
        $sql = "SELECT * FROM ehs_inspection_perform_log ".$plantFilter." ORDER BY id DESC";
        $result2 = $conn->query($sql);
        if ($result2 && $result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadInspectionLogPdf") {
        // A4 portrait (not landscape) so it prints on standard A4 paper
        $_GET['filename'] = 'Fire Extinguisher Inspection Log';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdffonts'] = 7;
        $_GET['pdfleft'] = 8;
        $_GET['pdfright'] = 8;
        include("../pdfimp2.php");

        $html = "";
        $html .= '<table border="1" cellpadding="2" style="font-size:7px; width:100%;">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; text-align:center;">
                    <td style="width:4%;">Sr.</td>
                    <td style="width:9%;">Insp. Date</td>
                    <td style="width:12%;">Equipment / ID</td>
                    <td style="width:9%;">Location</td>
                    <td style="width:15%;">Observations / Deficiencies</td>
                    <td style="width:15%;">Description of Maint. Work</td>
                    <td style="width:14%;">Inspector Comments / Remarks</td>
                    <td style="width:11%;">Performed by</td>
                    <td style="width:11%;">Verified by</td>
                </tr>
            </thead>';

        $plantFilter = "";
        if (isset($_GET["plant_id"]) && $_GET["plant_id"] !== '') {
            $plantFilter = " WHERE plant_id='".$conn->real_escape_string($_GET["plant_id"])."' ";
        }
        $sql = "SELECT * FROM ehs_inspection_perform_log ".$plantFilter." ORDER BY id DESC";
        $result2 = $conn->query($sql);
        $i = 1;
        if ($result2 && $result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $equip = htmlspecialchars(trim($row['equipment_name'].' '.($row['equipment_code'] ? '('.$row['equipment_code'].')' : '')));
                $performed = htmlspecialchars($row['performed_by_name'] ? $row['performed_by_name'] : $row['performed_by']);
                $verified = htmlspecialchars($row['verified_by_name'] ? $row['verified_by_name'] : ($row['verified_by'] ? $row['verified_by'] : '-'));
                $html .= '<tr nobr="true">
                    <td style="width:4%;">'.$i.'.</td>
                    <td style="width:9%;">'.htmlspecialchars($row['inspection_date']).'</td>
                    <td style="width:12%;">'.$equip.'</td>
                    <td style="width:9%;">'.htmlspecialchars($row['location']).'</td>
                    <td style="width:15%;">'.htmlspecialchars($row['observation']).'</td>
                    <td style="width:15%;">'.htmlspecialchars($row['description']).'</td>
                    <td style="width:14%;">'.htmlspecialchars($row['remark']).'</td>
                    <td style="width:11%;">'.$performed.'</td>
                    <td style="width:11%;">'.$verified.'</td>
                </tr>';
                $i++;
            }
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Fire_Extinguisher_Inspection_Log.pdf', 'I');
    } else {
        echo "{\"status\":\"invalid type\"}";
    }
} else {
    echo "{\"status\":\"token error\"}";
}
?>
