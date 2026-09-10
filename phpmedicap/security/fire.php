<?php
    require '../db.php';
    require '../token.php';
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
    
    if ($_GET["type"] == "saveFireExtinguisher") {
        $sql = "INSERT INTO fire_extinguisher (user_no, no, location, type, size,unit,fire_class,refill_date,frequency, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["no"]."', '".$input["location"]."', '".$input["type"]."', '".$input["size"]."','".$input["unit"]."', '".$input["fire_class"]."','".$input["refill_date"]."','".$input["frequency"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingFireExtinguishers") {
        $output = array();
        $sql = "SELECT * FROM fire_extinguisher WHERE user_no='".$_GET["user_no"]."' AND status='pending' AND location LIKE '%".$_GET["location"]."%' AND no LIKE '%".$_GET["no"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }else if ($_GET["type"] == "getAllPendingFireExtinguishers") {
        $output = array();
        $sql = "SELECT * FROM fire_extinguisher WHERE user_no='".$_GET["user_no"]."' AND status='pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateFireExtinguisher") {
        $sql = "UPDATE fire_extinguisher SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getFireExtinguishersLog") {
        $output = array();
        $sql = "SELECT * FROM fire_extinguisher WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getApprovedFireExtinguishersLog") {
        $output = array();
        $sql = "SELECT * FROM fire_extinguisher WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getFireExtinguishers") {
        $output = array();
        $sql = "SELECT * FROM fire_extinguisher WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadFireExtinguishersLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Fire Extinguishers Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Fire Extinguishers Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr No.</td>
                     <td style="width: 15%;">Extinguishers Type</td>
                     <td style="width: 15%;">No.</td>
                    <td style="width: 20%;">location</td>
                    <td style="width: 20%;">Last Refill Date</td>
                      <td style="width: 20%;">Due Date</td>
                   
                  
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT * FROM fire_extinguisher ";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                    

                <td style="width: 10%; ">'.$i.'</td>
                   <td style="width:15%; ">'.$row['type'].'</td>
                <td style="width: 15%; ">'.$row['no'].'</td>
                <td style="width: 20%; ">'.$row['location'].'</td>
                <td style="width: 20%; ">'.date('d-m-y',strtotime($row['refill_date'])).'</td>
               <td style="width: 20%; ">'.date('d-m-y',strtotime($row['due_date'])).'</td>
         
                
               
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Fire Extinguishers Log.pdf', 'I');
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>