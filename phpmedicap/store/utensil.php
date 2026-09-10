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

    if ($_GET["type"] == "saveUtensil") {
        $sql="INSERT INTO utensil_usage(utensil_type ,equipment_code, cleaned_by , cleaned_date, remark ,check_by,check_date)VALUES
        ('".$input["utensil_type"]."' ,'".$input["equipment_code"]."' ,'".$input["cleaned_by"]."' ,'".$input["cleaned_date"]."' ,'".$input["remark"]."' , '".$_GET["emp_id"]."' , '".$entry_date."') ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getUtensil"){
        $output=Array();
        $sql="SELECT * FROM utensil_usage WHERE status='pending' AND DATE(check_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
          $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadUtensil") {
        $_GET['filename'] = 'Cleaning Record of Utensil'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:cenetr">Cleaning Record of Utensil</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 11%;">Date</td>
                    <td style="width: 15%;">Type of Utensil</td>
                    <td style="width: 14%;">Id No</td>
                    <td style="width: 15%;">Cleaned By</td>
                    <td style="width: 15%;">Cleaned Date</td>
                    <td style="width: 15%;">Checked Date</td>
                    <td style="width: 15%;">Remark</td>
                </tr>
            </thead>';
        $sql="SELECT * FROM utensil_usage WHERE status='pending' AND DATE(check_date) BETWEEN '".$_GET["from_date"]."' 
        AND '".$_GET["to_date"]."' ";
         $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 11%;">'.$row['check_date'].'.</td>
                    <td style="width: 15%;">'.$row['utensil_type'].'</td>
                    <td style="width: 14%;">'.$row['equipment_code'].'</td>
                    <td style="width: 15%;">'.$row['cleaned_by'].'</td>
                    <td style="width: 15%;">'.$row['cleaned_date'].'</td>
                    <td style="width: 15%;">'.$row['check_date'].'</td>
                    <td style="width: 15%;">'.$row['remark'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Cleaning Record of Utensil.pdf', 'I');
    }

}

$conn->close();
?>