<?php 
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
    
    if ($_GET["type"] == "saveTools") {
        $sql = "INSERT INTO tools (tooling_type,punch_dia,die_dia,type,punch_length,size,entry_by,entry_date) VALUES ('".$input["tooling_type"]."','".$input["punch_dia"]."','".$input["die_dia"]."','".$input["type"]."','".$input["punch_length"]."','".$input["size"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "getTools") {
        $output = array();
        $sql = "SELECT * FROM tools";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
else if ($_GET["type"] == "downloadTools") {
        $_GET['filename'] = 'Punch Tools'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Punch Tools</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%; ">Sr No</td>
                    <td style="width: 20%; ">Tooling Type</td>
                    <td style="width: 20%; ">Punch Dia.(mm)</td>
                    <td style="width: 20%; ">Die Dia.(mm):</td>
                    <td style="width: 20%; ">Punch Length(mm):</td>
                    <td style="width: 10%; ">Type</td>
                   
                </tr>
            </thead>';
            $i=1;
            $output = array();
             $sql = "SELECT * FROM tools";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                $html.='<tr nobr="true">
                            <td style="width: 10%; ">'.$i.'.</td>
                            <td style="width: 20%;">'.$row['tooling_type'].'</td>
                            <td style="width: 20%;">'.$row['punch_dia'].'</td>
                            <td style="width: 20%; ">'.$row['die_dia'].'</td>
                            <td style="width: 20%;">'.$row['punch_length'].'</td>
                            <td style="width: 10%; ">'.$row['type'].'</td>
                            
                        </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('General Materials.pdf', 'I');
}
}

$conn->close();
?>