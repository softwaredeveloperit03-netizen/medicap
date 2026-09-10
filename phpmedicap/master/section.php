<?php 
require '../db.php';
require '../token.php';
header('response_token: test123456');
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
    
    if ($_GET["type"] == "saveSection") {
        $department = $conn->real_escape_string(isset($input["department"]) ? $input["department"] : "");
        $floor = $conn->real_escape_string(isset($input["floor"]) ? $input["floor"] : "");
        $sectionName = $conn->real_escape_string(isset($input["section_name"]) ? $input["section_name"] : "");
        $roomName = $conn->real_escape_string(isset($input["room_name"]) ? $input["room_name"] : "");
        $sectionNumber = $conn->real_escape_string(isset($input["section_number"]) ? $input["section_number"] : "");

        $sql = "INSERT INTO section (department, floor, section_name, room_name, section_number, entry_by, entry_date, plant_id) VALUES 
        ('".$department."', '".$floor."', '".$sectionName."', '".$roomName."', '".$sectionNumber."', '".$_GET["emp_id"]."', '".$entry_date."', '".$_GET["plant_id"]."')";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if($_GET["type"] == "checkSectionCode") {
        $output = Array();
         $sql = "SELECT * FROM section where section_code='".$_GET["section_code"]."' and plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getQualificationsLog") {
        $output = Array();
        $sql = "SELECT * FROM qualification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if($_GET["type"] == "getSection") {
        $output = Array();
        $sql = "SELECT * FROM section";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 else if($_GET["type"] == "getSection1") {
        $output = Array();
        $dept = $conn->real_escape_string(trim((string)($_GET['dept'] ?? '')));
        $sql = "SELECT * FROM section";
        if ($dept !== '') {
            $sql .= " WHERE department = '".$dept."'";
        }
        $sql .= " ORDER BY section_name ASC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadsectionLog") {

    $_GET['filename'] = 'Section Log';
    $_GET['pdftype'] = 'onlyheader';
    include("../pdfimp2.php");

    $html = '
    <h2 style="text-align:center;">Section Log</h2>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr style="background-color:#DDDAD9; font-weight:bold; text-align:center;">
                <th width="10%">Sr. No.</th>
                <th width="30%">Department</th>
                <th width="40%">Room Name</th>
                <th width="20%">Room Number</th>
            </tr>
        </thead>
        <tbody>
    ';

    $i = 1;
    $sql = "SELECT * FROM section ORDER BY id DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $html .= '
            <tr>
                <td width="10%" align="center">'.$i.'</td>
                <td width="30%">'.$row['department'].'</td>
                <td width="40%">'.$row['section_name'].'</td>
                <td width="20%">'.$row['section_number'].'</td>
            </tr>';

            $i++;
        }
    } else {

        $html .= '
        <tr>
            <td colspan="4" align="center">No Records Found</td>
        </tr>';
    }

    $html .= '
        </tbody>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Lab_Master.pdf', 'I');
}
}

$conn->close();
?>