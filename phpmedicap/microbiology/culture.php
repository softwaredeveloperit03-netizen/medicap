<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveCulture") {
        $sql = "INSERT INTO culture (culture_name,atcc_no ,source,opening_culture,maintanance,transfer_date,slant_no ,generation_no,done_by ,growth_observe,purity,check_by,next_transfer) VALUES
        ('".$input["culture_name"]."','".$input["atcc_no"]."', '".$input["source"]."' , '".$input["opening_culture"]."' ,'".$input["maintanance"]."','".$entry_date."', '".$input["slant_no"]."','".$input["generation_no"]."' ,'".$_GET["emp_id"]."' ,'".$input["growth_observe"]."','".$input["purity"]."' ,'','".$input["next_transfer"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getCultureMaster"){
        $output=Array();
        $sql="SELECT * FROM others_material WHERE material_subtype = 'Cultures' AND plant_id =  '".$_GET["plant_id"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if($_GET["type"] == "saveCultureIdentification") {
        $sql="INSERT INTO culture_identification(organism_name,atcc_no,feature,feature_gram, microscope,done_by,entry_date)VALUES ('".$input["organism_name"]."' ,'".$input["atcc_no"]."','".$input["feature"]."','".$input["feature_gram"]."', '".$input["microscope"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getCultureIdentification"){
        $output=Array();
        $sql="SELECT * FROM culture_identification ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
      echo json_encode($output);
    }else if ($_GET["type"] == "downloadCulture") {
        $_GET['filename'] = 'Culture'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <td style="width: 20px;">Sr.</td>
                    <td style="width: 50px;">Dt of transfer</td>
                    <td style="width: 50px;">Name of Culture</td>
                    <td style="width: 35px;">ATCC No</td>
                    <td style="width: 40px;">Source</td>
                    <td style="width: 50px;">Dt Of opening lyophilize Culture</td>
                    <td style="width: 43px;">Maintance of medium</td>
                    <td style="width: 30px;">Slant No</td>
                    <td style="width: 40px;">Gen No</td>
                    <td style="width: 55px;">Media Lot No</td>
                    <td style="width: 50px;">Growth Obs on</td>
                    <td style="width: 35px;">Purity</td>
                    <td style="width: 40px;">Next Transfer Due On</td>
                  </tr>
            </thead>';
        $i=1;
        $sql="SELECT * FROM culture order by id desc";
        // WHERE DATE(transfer_date) BETWEEN '".$_GET["from_date"]."' 
        //AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr>
                    <td style="width: 20px;text-align:center;">'.$i.'.</td>
                    <td style="width: 50px;text-align:center;">'.$row['transfer_date'].'</td>
                    <td style="width: 50px;text-align:center;">'.$row['culture_name'].'</td>
                    <td style="width: 35px;text-align:center;">'.$row['atcc_no'].'</td>
                    <td style="width: 40px;text-align:center;">'.$row['source'].'</td>
                    <td style="width: 50px;text-align:center;">'.$row['opening_culture'].'</td>
                    <td style="width: 43px;text-align:center;">'.$row['maintanance'].'</td>
                    <td style="width: 30px;text-align:center;">'.$row['slant_no'].'</td>
                    <td style="width: 40px;text-align:center;">'.$row['generation_no'].'</td>
                    <td style="width: 55px;text-align:center;">'.$row['lot_no'].'</td>
                    <td style="width: 50px;text-align:center;">'.$row['growth_observe'].'</td>
                    <td style="width: 35px;text-align:center;">'.$row['purity'].'</td>
                    <td style="width: 40px;text-align:center;">'.$row['next_transfer'].'</td>
                </tr>';
            $i++;
            }
        }
        $html.='</table><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
        <table cellpadding="5" border="1">
        <tr>
        <td style="width:50%;text-align:center"><b>CHECKED BY</b></td>
         <td style="width:50%;text-align:center"><b>DONE BY</b></td>
        </tr>
         <tr>
        <td style="width:50%;text-align:center"><b></b></td>
         <td style="width:50%;text-align:center"><b></b></td>
        </tr>
        </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Culture.pdf', 'I');
    }else if ($_GET["type"] == "downloadCultureIdentification") {
        $_GET['filename'] = 'CultureIdentification'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Culture Identification</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 20%;">Name of Orangnism</td>
                        <td style="width: 15%;">ATCC No</td>
                        <td style="width: 20%;">Microscopic Features</td>
                        <td style="width: 15%;">Microscopic Features(Gram straining)</td>
                        <td style="width: 15%;">Done By</td>
                        <td style="width: 15%;">Checked By</td>
                    </tr>
                </thead>';
        $sql="SELECT * FROM culture_identification ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='<tr nobr="true">
                    <td style="width: 20%;">'.$row['organism_name'].'</td>
                    <td style="width: 15%;">'.$row['atcc_no'].'</td>
                    <td style="width: 20%;">'.$row['feature'].'</td>
                    <td style="width: 15%;">'.$row['feature_gram'].'</td>
                    <td style="width: 15%;">'.$row['done_by'].'</td>
                    <td style="width: 15%;">'.$row['check_by'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CultureIdentification.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>