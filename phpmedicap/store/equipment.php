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

    if ($_GET["type"] == "getEquipmentList") {
		$output = array();
		$sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND department='Store' AND equipment_type LIKE '%".$_GET["equipment_type"]."%'";
      
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		echo json_encode($output);
	}

    else if ($_GET["type"] == "downloadEquipmentList") {
        require '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Equipment Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="text-align:center">Equipment Log</h2>
        <table cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;"><b>Sr.no</b></td>
                    <td style="width: 10%;"><b>Equipment Name</b></td>
                    <td style="width: 10%;"><b>Equipment Code</b></td>
                    <td style="width: 10%;"><b>Equipment Type</b></td>
                    <td style="width: 10%;"><b>Make</b></td>
                    <td style="width: 20%;"><b>Capacity</b></td>
                    <td style="width: 20%;"><b>Calibration</b></td>
                    <td style="width: 10%;"><b>Section</b></td>
                </tr>';
                $i=1;
				$sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND department='Store' AND equipment_type LIKE '%".$_GET["equipment_type"]."%'";
            $result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['equipment_name'].'</td>
                        <td style="width: 10%;">'.$row['equipment_code'].'</td>
                        <td style="width: 10%;">'.$row['equipment_type'].'</td>
                        <td style="width: 10%;">'.$row['make'].'</td>
                        <td style="width: 20%;">'.$row['capacity'].'</td>
                        <td style="width: 20%;">'.$row['calibration'].'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Equipment.pdf', 'I');
    } else if ($_GET["type"] == "getVaccumCleaners") {
        $sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND equipment_type='Vacuum Cleaner'";
        $result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }

}

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
?>