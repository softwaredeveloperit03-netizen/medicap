<?php



// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
    
    if ($_GET["type"] == "saveWorkHeight") {
         $sql = "INSERT INTO work_height( plant_id,user_no,plant_name,emp_id,contractor,date,details_work,location,validity_from,validity_to,entry_by,entry_date) VALUES ( '".$_GET["plant_id"]."','".$_GET["user_no"]."','".$input["plant_name"]."','".$input["emp_id"]."','".$input["contractor"]."','".$input["date"]."','".$input["details_work"]."','".$input["location"]."','".$input["validity_from"]."','".$input["validity_to"]."','".$_GET["emp_id"]."', '$entry_date')";
      	if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
    }else if ($_GET["type"] == "getWorkHeight") {
	    $output = array();  
	   //  echo $sql = "SELECT w.*, e.firstname FROM work_height w LEFT JOIN employee e ON w.emp_id=e.emp_id WHERE w.status='pending'  ORDER BY id DESC";
	        $sql = "SELECT * FROM work_height  WHERE status='pending'  ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}else if ($_GET["type"] == "checkWorkHeight") {
            $sql = "UPDATE work_height SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date',conditions='".json_encode($input["conditions"])."'  WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
	}else if ($_GET["type"] == "getCheckedWorkHeight") {
	    $output = array();  
	    $sql = "SELECT w.*, e.firstname FROM work_height w LEFT JOIN employee e ON w.emp_id=e.emp_id WHERE w.status='checked'  ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["conditions"] = json_decode($row["conditions"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
   }else if ($_GET["type"] == "approveWorkHeight") {
            $sql = "UPDATE work_height SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date',comment='".$_GET["comment"]."'  WHERE id='".$_GET["id"]."'";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
   }else if ($_GET["type"] == "workHeightLog") {
	    $output = array();
	  $sql = "SELECT w.*, e.firstname FROM work_height w LEFT JOIN employee e ON w.emp_id=e.emp_id WHERE DATE(w.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["conditions"] = json_decode($row["conditions"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
   }
   else if ($_GET["type"] == "downloadWorkHightLog") {
        
        $_GET['filename'] = 'WorkHeight Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Plant Name</td>
                    <td style="width: 10%;">Employee</td>
                    <td style="width: 11%;">Contractor</td>
                    <td style="width: 11%;">date</td>
                    <td style="width: 14%;">Location</td>
                    <td style="width: 14%;">Validity From</td>
                    <td style="width: 15%;">Validity To</td>
                    <td style="width: 10%;">Status</td>
                </tr>
            </thead>';
            $output = array();
	        $sql = "SELECT w.*, e.firstname FROM work_height w LEFT JOIN employee e ON w.emp_id=e.emp_id WHERE DATE(w.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
	        $result = $conn->query($sql);
	        $i=1;
	       if ($result->num_rows > 0) {
	       while ($row = $result->fetch_assoc()) {
	           $row["conditions"] = json_decode($row["conditions"]);
	           $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['plant_name'].'</td>
                        <td style="width: 10%;">'.$row['emp_id'].'</td>
                        <td style="width: 11%;">'.$row['contractor'].'</td>
                        <td style="width: 11%;">'.$row['date'].'</td>
                        <td style="width: 14%;">'.$row['location'].'</td>
                        <td style="width: 14%;">'.$row['validity_from'].'</td>
                        <td style="width: 15%;">'.$row['validity_to'].'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('WorkHeight log.pdf', 'I');
    }
      else if ($_GET["type"] == "downloadLog") {
        
        $_GET['filename'] = 'WORK ORDER-CUM-PERMIT (WORK ON HEIGHT)'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";
        $output = array();
	    $sql = "SELECT w.*, e.firstname FROM work_height w LEFT JOIN employee e ON w.emp_id=e.emp_id WHERE w.id='".$_GET["id"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["conditions"] = json_decode($row["conditions"]);
	            $conditions=$row["conditions"];
	            $output[] = $row;
        $html.='<table cellpadding="5">
                <tr>
                    <td style="width:50%; border:none;"><b>Format No:EHS034/F/01-01</b></td>
                    <td style="width:50%; border:none;"><b>PERMIT No:</b></td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">Plant   :'.$row['plant_name'].' </td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">Date    :  '.$row['date'].'</td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">Exact Location of Work     :'.$row['location'].' </td>
                </tr>
                <tr>
                    <td style="width:35%; border:none;">Valid date :'.$row['date'].'</td>
                    <td style="width:35%; border:none;">From : '.$row['validity_from'].'hrs</td>
                    <td style="width:30%; border:none;">To  :'.$row['validity_to'].'hrs</td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">Description of the job to be carried out   : '.$row['details_work'].'</td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">Job carried out by (No. of the person and Contractor)  :'.$row['contractor'].'</td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">Name of the supervisor  : '.$row['first_name'].'</td>
                </tr>
                <tr>
                    <td style="width:100%;"><table border="1" cellpadding="4">
                                            <tr>
                                                <td style="width:100%; text-align:center;"><b>SAFETY CHECKS</b></td>
                                            </tr>';
                                            $j=1;
                                            for ($k = 0; $k < count($conditions); $k++) {
                                                $condition = $conditions[$k];   
                                $html.='    <tr>
                                                <td style="width:5%;">'.$j++.'</td>
                                                <td style="width:85%;">'.$condition->condition.'</td>
                                                <td style="width:10%;">'.$condition->applicable.'</td>
                                            </tr>';
                                            }
                                $html.='    </table>
                    </td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;"><b>*All the above conditions are checked & Complied & found OK</b></td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;"><b>*Any Other Special Instruction :- '.$row['comment'].'</b></td>
                </tr>
                 <tr>
                   <td style="width:33.33%; border:none;"><b>Name/Sign of Safety Dept</b></td>
                   <td style="width:33.33%; border:none;"><b>Name/Sign of Permittee</b></td>
                   <td style="width:33.33%; border:none;"><b>Name/ Sign of permit Area in charge</b></td>
                </tr>
                 <tr>
                   <td style="width:33.33%; border:none;"></td>
                   <td style="width:33.33%; border:none;"></td>
                   <td style="width:33.33%; border:none;"></td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;">NOTE: Permit is valid only during general shift hours. Any extension beyond 18:00 hrs should be specifically authorized.</td>
                </tr>
                <tr>
                    <td style="width:100%; border:none;"><b><u>Permit must be deemed cancelled in case of emergency situation like Fire, Toxic release, Siren etc)</u></b></td>
                <tr>
               
                ';
            
        $html.="</table>";
	           }
	        }

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('WorkHeight log.pdf', 'I');
    }
}
$conn->close();
?>