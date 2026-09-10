<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
     if ($_GET["type"] == "downloadTestsLog") {
        $_GET['filename'] = 'Medical Test'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Medical Test</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%;"><b>Sr</b></td>
              
                <td style="width:45%;"><b>Test Name</b></td>
                <td style="width:45%;"><b>Test</b></td>
            </tr>';
            $i=1;
            $sql = "SELECT * FROM test_medical";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'.</td>
             
                <td style="width:45%;">'.$row['test_name'].'</td>
                <td style="width:45%;">'.$row['test'].'</td>
            </tr>';
            $i++;
            }
            }
        $html.='</table>';
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Medical Test.pdf', 'I');
    }

    
    
    
    
    
    
    } else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>