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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
     if ($_GET["type"] == "packingListLogPDF") {
        $_GET['filename'] = 'Packing List Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Packing List Log</h2>
        <table cellpadding="5" border="1">
        <tr>
            <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:15%; text-align:centre;"><b>Product Code</b></td>
            <td style="width:15%; text-align:centre;"><b>Product Name</b></td>
            <td style="width:10%; text-align:centre;"><b>Grade</b></td>
            <td style="width:15%; text-align:centre;"><b>Batch No</b></td>
            <td style="width:15%; text-align:centre;"><b>Batch Size</b></td>
            <td style="width:20%; text-align:centre;"><b>Packing Quantity</b></td>
        </tr>
        <tr>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:10%;"></td>
            <td style="width:15%;"></td>
            <td style="width:15%;"></td>
            <td style="width:20%;"></td>
        </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing List Log.pdf', 'I');
    }else if ($_GET["type"] == "packingListPDF") {
        $_GET['filename'] = 'Packing List Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing List Log.pdf', 'I');
    }
    else if ($_GET["type"] == "savemobileapplicationform") {
        
             $sql = "INSERT INTO mobile_application   (plant_id, request_name, request_email, request_date, apl_title, apl_platform, apl_version, apl_purpose, licence_type, other, 
             dist_method, target_dev, user_id, user_name, acc_permisstion, integration_sys, dependancies,security_requir,approvel_requird,approver_name,approval_date,comments,declaration_date) 
     VALUES ('".$_GET["plant_id"]."', '".$input["request_name"]."', '".$input["request_email"]."', '".$input["request_date"]."', '".$input["apl_title"]."', '".$input["apl_platform"]."',
        '".$input["apl_version"]."', '".$input["apl_purpose"]."', '".$input["licence_type"]."', '".$input["other"]."', 
        '".$input["dist_method"]."', '".$input["target_dev"]."','".$input["user_id"]."','".$input["user_name"]."','".$input["acc_permisstion"]."','".$input["integration_sys"]."',
            '".$input["dependancies"]."','".$input["security_requir"]."','".$input["approvel_requird"]."','".$input["approver_name"]."','".$input["approval_date"]."','".$input["comments"]."','".$input["declaration_date"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
}
    
    
    }

$conn->close();
?>