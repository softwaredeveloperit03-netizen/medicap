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
    
    if ($_GET["type"] == "saveState") {
        $sql = "INSERT INTO state_master (plant_id,state_code,state_name,country) VALUES ('".$input["plant_id"]."','".$input["state_code"]."','".$input["state_name"]."','".$input["country"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if($_GET["type"]=="updateinactivestate"){
        $sql = "UPDATE state_master SET status='inactive' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if($_GET["type"]=="updateBackliststate"){
        $sql = "UPDATE state_master SET status='blacklist' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getState") {
        $output = array();
        $plant=$_GET["plant_id"];
        //if($plant==0){
        $sql = "SELECT * FROM state_master WHERE status!='blacklist'";
       // }else{
        //$sql = "SELECT * FROM state_master WHERE status!='blacklist' AND plant_id='".$_GET["plant_id"]."'";   
       // }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getStateBycountry") {
        $output = array();
        $sql = "SELECT * FROM state_master WHERE  status!='blacklist'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadtransport") {
        $_GET['filename'] = 'Transport Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Transport Master</h2>
        <table cellpadding="5" border="1">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%; text-align:centre;"><b>Sr No.</b></td>
                        <td style="width:25%; text-align:centre;">Transport / Company Name</td>
                        <td style="width:15%; text-align:centre;">Contact Person</td>
                        <td style="width:15%; text-align:centre;">Countries</td>
                        <td style="width:15%; text-align:centre;">State</td>
                        <td style="width:15%; text-align:centre;">City</td>
                    </tr>
                </thead>
                <tbody>';
                $i=1;
                $sql = "SELECT * FROM transport_master";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            $html.='<tr>
                        <td style="width:15%;">'.$i.'.</td>
                        <td style="width:25%;">'.$row['transport_company'].'</td>
                        <td style="width:15%;">'.$row['contact_person'].'</td>
                        <td style="width:15%;">'.$row['country'].'</td>
                        <td style="width:15%;">'.$row['states'].'</td>
                        <td style="width:15%;">'.$row['city'].'</td>
                    </tr>
                </tbody>';
                $i++;
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('transport.pdf', 'I');
    }

}

$conn->close();
?>