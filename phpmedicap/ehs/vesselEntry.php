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

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveVesselEntry") {
         $sql = "INSERT INTO vessel_entry(user_no,date,department_from,department_to,details_work,location,validity_from,validity_to,entry_by,entry_date) VALUES ('".$_GET["user_no"]."','".$input["date"]."','".$input["department_from"]."','".$input["department_to"]."','".$input["details_work"]."','".$input["location"]."','".$input["validity_from"]."','".$input["validity_to"]."','".$_GET["emp_id"]."', '$entry_date')";
      	if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
    }else if ($_GET["type"] == "getPendingVessel") {
	    $output = array();  
	    $sql = "SELECT * FROM vessel_entry WHERE status='pending'  ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}else if ($_GET["type"] == "checkVessel") {
            $sql = "UPDATE vessel_entry SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date',conditions='".json_encode($input["conditions"])."'  WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
	}else if ($_GET["type"] == "getCheckedVessel") {
	    $output = array();  
	    $sql = "SELECT * FROM vessel_entry WHERE status='checked'  ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	             $row["conditions"] = json_decode($row["conditions"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
   }else if ($_GET["type"] == "approveVessel") {
              $sql = "UPDATE vessel_entry SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date',comment='".$_GET["comment"]."'  WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
   }else if ($_GET["type"] == "VesselLog") {
	    $output = array();
	  $sql = "SELECT * FROM vessel_entry WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["conditions"] = json_decode($row["conditions"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
   }
   else if ($_GET["type"] == "downloadVesselLog") {
        
        $_GET['filename'] = 'Vessel Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 11%;">Date</td>
                    <td style="width: 15%;">From Department</td>
                    <td style="width: 15%;">To Department</td>
                    <td style="width: 14%;">Location</td>
                    <td style="width: 15%;">Validity From</td>
                    <td style="width: 15%;">Validity To</td>
                    <td style="width: 10%;">Status</td>
                </tr>
            </thead>';
            $output = array();
	        $sql = "SELECT * FROM vessel_entry WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
	        $result = $conn->query($sql);
	        $i=1;
	        if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["conditions"] = json_decode($row["conditions"]);
	            $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 11%;">'.$row['date'].'</td>
                        <td style="width: 15%;">'.$row['department_from'].'</td>
                        <td style="width: 15%;">'.$row['department_to'].'</td>
                        <td style="width: 14%;">'.$row['location'].'</td>
                        <td style="width: 15%;">'.$row['validity_from'].'</td>
                        <td style="width: 15%;">'.$row['validity_to'].'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vessel log.pdf', 'I');
    } else if ($_GET["type"] == "downloadLog") {
        
        $_GET['filename'] = 'Vessel Entry Permit Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $output = array();
	    $sql = "SELECT * FROM vessel_entry  WHERE id='".$_GET["id"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	             $row["conditions"] = json_decode($row["conditions"]);
                 $conditions=$row["conditions"];
	           
        $html.='<table border="1" cellpadding="4">
                <tr>
                    <td style="width:100%;"><table>
                                            <tr>
                                                <td style="width:10%; border:none;"><b>Date:</b></td>
                                                <td style="width:15%; border:none;">'.$row['date'].'</td>
                                                <td style="width:20%; border:none;"><b>From Department:</b></td>
                                                <td style="width:20%; border:none;">'.$row['department_from'].'</td>
                                                <td style="width:15%; border:none;"><b>To Department:</b></td>
                                                <td style="width:20%; border:none;">'.$row['department_to'].'</td>
                                            </tr>
                                            <tr>
                                                <td style="width:15%; border:none;"><b>Details of Work: </b></td>
                                                <td style="width:85%; border:none;">'.$row['details_work'].'</td>
                                            </tr>
                                            <tr>
                                                <td style="width:20%;  border:none;"><b>Location of Work: </b></td>
                                                <td style="width:80%;  border:none;">'.$row['location'].'</td>
                                            </tr>
                                            <tr>
                                                <td style="width:15%;  border:none;"><b>Validity Time:</b></td>
                                                <td style="width:10%;  border:none;"><b>From:</b></td>
                                                <td style="width:15%;  border:none;">'.$row['validity_from'].'Hrs</td>
                                                <td style="width:10%;  border:none;"><b>To:</b></td>
                                                <td style="width:15%;  border:none;">'.$row['validity_to'].'Hrs</td>
                                            </tr>
                                            <tr>
                                                <td style="width:15%;  border:none;"><b>Initiated By:</b></td>
                                                <td style="width:35%;  border:none;">'.$row['entry_by'].'</td>
                                                <td style="width:20%;  border:none;"><b>Received By(Eng.):</b></td>
                                                <td style="width:30%;  border:none;">'.$row['approve_by'].'</td>
                                            </tr>
                                             <tr>
                                                <td style="width:10%;  border:none;"><b>Name:</b></td>
                                                <td style="width:40%;  border:none;"></td>
                                                <td style="width:10%;  border:none;"><b>Name:</b></td>
                                                <td style="width:40%;  border:none;"></td>
                                            </tr>
                                            <tr>
                                                <td style="width:20%;  border:none;"><b>(Sign/Date/Time)</b></td>
                                                <td style="width:30%;  border:none;"></td>
                                                <td style="width:20%;  border:none;"><b>(Sign/Date/Time)</b></td>
                                                <td style="width:30%;  border:none;"></td>
                                            </tr>
                                            <tr>
                                                <td style="width:100%;  border:none;"><b>Mark clearly Yes, No or NA (Not applicable) in front of each conditions mentioned below.</b></td>
                                            </tr>';
                                           
                                             $j=1;
                                             for ($k = 0; $k < count($conditions); $k++) {
                                                $condition = $conditions[$k];  
                                    $html.='<tr>
                                                <td style="width:5%; border:none;"></td>
                                                <td style="width:5%; border:none;">'.$j++.'</td>
                                                <td style="width:70%; border:none;">'.$condition->condition.'</td>
                                                <td style="width:20%; border:none;">('.$condition->applicable.')</td>
                                            </tr>';
                                             }
                                    $html.='
                                            <tr>
                                                <td style="width:50%; border:none;"><b>Supervisor Name :</b>______________________________</td>
                                                <td style="width:50%; border:none;"><b>Sign: </b>______________________________</td>
                                            </tr>
                                            </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><table>
                                            <tr>
                                                <td style="width:10%; border:none;"><b>O2:</b></td>
                                                <td style="width:15%; border:none;"></td>
                                                <td style="width:10%; border:none;"><b>CO PPM : </b></td>
                                                <td style="width:15%; border:none;"></td>
                                                <td style="width:10%; border:none;"><b>LEL:</b></td>
                                                <td style="width:15%; border:none;"></td>
                                                <td style="width:10%; border:none;"><b>H2S PPM:</b></td>
                                                <td style="width:15%; border:none;"></td>
                                            </tr>
                                            <tr>
                                                <td style="width:60%; border:none;"><b>Department Head/Designee:</b> Name:  </td>
                                                <td style="width:40%; border:none;">Sign/Date/Time :</td>
                                            </tr>
                                            <tr>
                                                <td style="width:60%; border:none;"><b>Site Head/Designee:</b> Name:</td>
                                                <td style="width:40%; border:none;">Sign/Date/Time :</td>
                                            </tr>
                                            </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><table>
                                            <tr>
                                                <td style="width:100%; border:none;"><b>EHS Department Comments:</b></td>
                                            </tr>
                                            <tr>
                                                <td style="width:60%; border:none;"><b>EHS Department:</b> Name:   </td>
                                                <td style="width:40%; border:none;">Sign/Date/Time :</td>
                                            </tr>
                                            </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%;"><table>
                                            <tr>
                                                <td style="width:100%; border:none;"><b>Extension:(To be obtained 1 hrs. Before expiry of permit required: from ____________hrs.to ______________hrs.)</b></td>
                                            </tr>
                                            <tr>
                                                <td style="width:60%; border:none;"><b>Name of Person</b> Mr. </td>
                                                <td style="width:40%; border:none;"><b>Sign/Date</b></td>
                                            </tr>
                                            <tr>
                                                <td style="width:100%;">Will supervise the job till end.</td>
                                            </tr>
                                            <tr>
                                                <td style="width:60%; border:none;"><b>Department Head/Designee:</b> Name</td>
                                                <td style="width:40%; border:none;">Sign/Date/Time </td>
                                            </tr>
                                            <tr>
                                                <td style="width:100%; border:none;">EHS Department Comment: </td>
                                            </tr>
                                            <tr>
                                                <td style="width:100%; border:none;"></td>
                                            </tr>
                                            <tr>
                                                <td style="width:50%; border:none;"><b>Name:</b></td>
                                                <td style="width:50%; border:none;"><b>Sign/Date/Time</b> </td>
                                            </tr>
                                            <tr>
                                                <td style="width:100%; border:none;"><b>Closure of Work Permit (Permit close Same Date)</b></td>
                                            </tr>
                                            <tr>
                                                <td style="width:40%; border:none;"><b>Name of Person (Department Head) :</b> </td>
                                                <td style="width:20%; border:none;"><b>Sign :</b></td>
                                                <td style="width:20%; border:none;"><b>Date :</b></td>
                                                <td style="width:20%; border:none;"><b>Time :</b></td>
                                            </tr>
                                            </table>
                    </td>
                </tr>
                 ';
               
        $html.="</table>";
	        }
	    }

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Vessel log.pdf', 'I');
    }
}
$conn->close();
?>