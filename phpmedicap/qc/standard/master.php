<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

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

    if ($_GET["type"] == "saveMasters") {
        $sql = "INSERT INTO masters (standard_name,grade,chemical_name,category,strength,unit, entry_by, entry_date) VALUES ('".$input["standard_name"]."','".$input["grade"]."', '".$input["chemical_name"]."', '".$input["category"]."', '".$input["strength"]."', '".$input["unit"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getMasters") {
        $output = Array();
        $sql = "SELECT * FROM masters WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadStandardMaster") {
        $_GET['filename'] = 'Standard Master'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Standard Master</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr No.</b></td>
            <td style="width:15%; text-align:centre;"><b>Standard Name</b></td>
            <td style="width:15%; text-align:centre;"><b>pharmcopoieal grade</b></td>
            <td style="width:15%; text-align:centre;"><b>Chemical Name</b></td>
            <td style="width:15%; text-align:centre;"><b>Category</b></td>
            <td style="width:15%; text-align:centre;"><b>strength</b></td>
            <td style="width:15%; text-align:centre;"><b>Unit</b></td>
        </tr>';
        $i=1;
        $sql = "SELECT * FROM masters WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr>
            <td style="width:10%;">'.$i.'.</td>
            <td style="width:15%;">'.$row['standard_name'].'</td>
            <td style="width:15%;">'.$row['grade'].'</td>
            <td style="width:15%;">'.$row['chemical_name'].'</td>
            <td style="width:15%;">'.$row['category'].'</td>
            <td style="width:15%;">'.$row['strength'].'</td>
            <td style="width:15%;">'.$row['unit'].'</td>
        </tr>';
        $i++;
            }
        }
        $html.='</table>';
        
        
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('StandardMaster.pdf', 'I');
    }
}
$conn->close();
?>