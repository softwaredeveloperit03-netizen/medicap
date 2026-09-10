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
    
    if ($_GET["type"] == "saveTransport") {
         $sql = "INSERT INTO transport_master (plant_id,transport_company,isd_code,states,city ,gst_type ,gst_no ,contact_person, country,transport_type,pincode,contact_number) VALUES 
        ('".$input["plant_id"]."','".$input["transport_company"]."','".$input["isd_code"]."','".$input["states"]."' ,'".$input["city"]."' ,'".$input["gst_type"]."'
        ,'".$input["gst_no"]."' ,'".$input["contact_person"]."','".$input["countries"]."','".$input["transport_type"]."','".$input["pincode"]."','".$input["contact_number"]."')";
    if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getTransport") {
        $output = array();
        $plant=$_GET['plant_id'];
        // if($plant==0){
        $sql = "SELECT * FROM transport_master WHERE status='pending'ORDER BY id DESC";
        //}else{
    //   $sql = "SELECT * FROM transport_master WHERE status='pending' AND plant_id='".$_GET["plant_id"]."'";   
       
        // }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "editTransport") {
        $sql = "UPDATE transport_master SET transport_company='".$input["transport_company"]."',city='".$input["city"]."',states='".$input["states"]."',country='".$input["country"]."',contact_person='".$input["contact_person"]."'  WHERE id ='".$_GET["id"]."' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "downloadTransport") {
        $_GET['filename'] = 'Training Certificate '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr</td>
                    <td style="width: 25%;">Transport / Company Name</td>
                    <td style="width: 15%;">Contact Person</td>
                    <td style="width: 20%;">Countries</td>
                    <td style="width: 15%;">State</td>
                    <td style="width: 20%;">City</td>
                     
                   
                </tr>
            </thead>';
             $i=1;
              $sql = "SELECT * FROM transport_master WHERE status='pending'ORDER BY id DESC";

    //   echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0){  
            $i = 1;
            while($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 25%;">'.$row['transport_company'].'</td>
                        <td style="width: 15%;">'.$row['contact_person'].'</td>
                        <td style="width: 20%;">'.$row['country'].'</td>
                        <td style="width: 15%;">'.$row['states'].'</td>
                        <td style="width: 20%;">'.$row['city'].'</td>
                        
                    </tr>';
                $i++;
            }
        }
       
             $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Training Certificate.pdf', 'I');
    }
    else if($_GET["type"] == "deleteTransport"){
        $sql = "UPDATE transport_master SET status='Deleted'  WHERE id ='".$_GET["id"]."' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }

}

$conn->close();
?>