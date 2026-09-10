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

    if ($_GET["type"] == "saveMaterialOutForm") {
        $materials = isset($input["materials"]) && is_array($input["materials"]) ? $input["materials"] : array();
        $unitList = array();
        foreach ($materials as $mat) {
            if (!empty($mat["unit"])) {
                $u = trim((string)$mat["unit"]);
                if ($u !== '' && !in_array($u, $unitList, true)) {
                    $unitList[] = $u;
                }
            }
        }
        $unitStr = count($unitList) > 0 ? implode(', ', $unitList) : 'NA';
        $transportCompany = isset($input["transport_company"]) ? $input["transport_company"] : '';
        $vehicleNo = isset($input["vehicle_no"]) ? $input["vehicle_no"] : '';
        $driverName = isset($input["driver_name"]) ? $input["driver_name"] : '';
        $driverMobile = isset($input["driver_mobile"]) ? $input["driver_mobile"] : '';
        $partyName = isset($input["party_name"]) ? $input["party_name"] : (isset($input["send_to"]) ? $input["send_to"] : '');

        $sql = "INSERT INTO outword (plant_id,department,user_no, material_code,transport_company, send_to,pin_code,address, reason, qty, unit, 
	 	outword_type,request_by, transport_by, vehicle_no,materials,requiredQty, entry_by, entry_date, driver_name, driver_mobile)
		VALUES ('".mysqli_real_escape_string($conn, $_GET["plant_id"])."','".mysqli_real_escape_string($conn, $input["department"])."','".mysqli_real_escape_string($conn, isset($_GET["user_no"]) ? $_GET["user_no"] : '')."', 'NA','".mysqli_real_escape_string($conn, $transportCompany)."', 
		'".mysqli_real_escape_string($conn, $partyName)."','".mysqli_real_escape_string($conn, $input["pin_code"])."','".mysqli_real_escape_string($conn, $input["address"])."', '".mysqli_real_escape_string($conn, $input["reason"])."', 'NA', '".mysqli_real_escape_string($conn, $unitStr)."', 
		'".mysqli_real_escape_string($conn, $input["outword_type"])."', '".mysqli_real_escape_string($conn, $input["request_by"])."', '".mysqli_real_escape_string($conn, $input["transport_by"])."', '".mysqli_real_escape_string($conn, $vehicleNo)."','".mysqli_real_escape_string($conn, json_encode($materials))."',
		'0', '".mysqli_real_escape_string($conn, $_GET["emp_id"])."', '$entry_date','".mysqli_real_escape_string($conn, $driverName)."','".mysqli_real_escape_string($conn, $driverMobile)."')";
		
		if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	}	
	
	else if ($_GET["type"] == "getoutward") { 
	     $output = Array();
        $sql = "SELECT * FROM outword ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $mats = array();
                if (!empty($row["materials"])) {
                    $decoded = json_decode($row["materials"], true);
                    if (is_array($decoded)) {
                        $mats = $decoded;
                    }
                }
                // Derive unit from materials when header unit missing
                if (empty($row["unit"]) || $row["unit"] === 'NA') {
                    $units = array();
                    foreach ($mats as $m) {
                        if (!empty($m["unit"])) {
                            $u = trim((string)$m["unit"]);
                            if ($u !== '' && !in_array($u, $units, true)) {
                                $units[] = $u;
                            }
                        }
                    }
                    if (count($units) > 0) {
                        $row["unit"] = implode(', ', $units);
                    }
                }
                $row["party_name"] = !empty($row["send_to"]) ? $row["send_to"] : (isset($row["party_name"]) ? $row["party_name"] : '');
                $row["materials_parsed"] = $mats;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
	else if ($_GET["type"] == "getEquipments") { 
	     $output = Array();
        $sql = "SELECT * FROM equipment where status = 'Active' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getMaterials") {
        $output = Array();
      $sql = "SELECT * FROM material WHERE material_subtype='".$_GET["material_subtype"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT s.*,v.vendor_name FROM stock_book s LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $used_qty = 0;
                        
                        $output2 = array();
                        $sql2 = "SELECT * FROM material_issue WHERE ar_no='".$row1["ar_no"]."' AND status='approve'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                                $issue_qty += +$row2["qty"];
                                $used_qty += +$row2["qty"];
                            }
                        }
                        $row1["issued"] = $output2;
                        $row1["issue_qty"] = $used_qty;
                        $balance_qty = +$row1["qty"] - $used_qty;
                        $balance_qty = round($balance_qty, 2);
                        $row1["balance_qty"] = $balance_qty;
                        $row1["issues"] = $output2;
                        $output1[] = $row1;
                        
                        $received_qty += +$row1["qty"];
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["grns"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getOutwordLog") {
	    $output = array();
	    $sql = "SELECT o.*, DATE(o.entry_date) as entry_date, m.material_type, m.material_subtype, m.material_name, 
	    m.grade FROM outword o LEFT JOIN material m ON o.material_code=m.material_code AND o.department LIKE '%".$_GET["department_name"]."%' AND DATE(o.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	             $row = array_map('utf8_encode', $row);
	            $row["materials"]=json_decode($row["materials"]);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}else if ($_GET["type"] == "getAllOutwordLog") {
	    $output = array();
	    $sql = "SELECT o.*, DATE(o.entry_date) as entry_date, m.material_type, m.material_subtype, m.material_name, m.grade FROM outword o LEFT JOIN material m ON o.material_code=m.material_code WHERE m.material_type='Raw Material'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	       $row = array_map('utf8_encode', $row);
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}if ($_GET["type"] == "downloadOutwordLog") {
        $_GET['filename'] = "Material / Equipment Outward"; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Material / Equipment Outward</h2>
        <table cellpadding="5" border="1">
            <tr style="text-align: center; background-color:#DDDAD9;">
                <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:10%; text-align:centre;"><b>Date</b></td>
                <td style="width:20%; text-align:centre;"><b>Department</b></td>
                <td style="width:20%; text-align:centre;"><b>Outward Type</b></td>
                <td style="width:20%; text-align:centre;"><b>Request By</b></td>
                <td style="width:15%; text-align:centre;"><b>Transport</b></td>
                <td style="width:10%; text-align:centre;"><b>Vehicle No.</b></td>
            </tr>';
              $i=1;
            $sql = "SELECT * from outword";
            //$sql = "SELECT o.*, DATE(o.entry_date) as entry_date, m.material_type, m.material_subtype, m.material_name, m.grade FROM outword o LEFT JOIN material m ON o.material_code=m.material_code WHERE m.material_type='Raw Material' AND DATE(o.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:5%;">'.$i++.'</td>
                <td style="width:10%;">'.$row['entry_date'].'</td>
                <td style="width:20%;">'.$row['department'].'</td>
                <td style="width:20%;">'.$row['outword_type'].'</td>
                <td style="width:20%;">'.$row['request_by'].'</td>
                <td style="width:15%;">'.$row['transport_by'].'</td>
                <td style="width:10%;">'.$row['vehicle_no'].'</td>
            </tr>';
	        }
	    }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('OutwordLog.pdf', 'I');
    } 

} 

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
?>