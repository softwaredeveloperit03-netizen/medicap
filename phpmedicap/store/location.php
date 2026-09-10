<?php


ini_set('display_errors', 1);
error_reporting(E_ALL);


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

    if ($_GET["type"] == "saveRack") {
        
        if (empty(trim($input["laneNO"]))) die("Invalid lane number");

        $laneNO = trim($input["laneNO"]);
        $section_name = trim($input["section_name"]);
        $plant_id = $_GET["plant_id"] ?? '';
        $emp_id   = $_GET["emp_id"] ?? '';
        $capacity = $input["capacity"] ?? '';
        $entry_date = date("Y-m-d H:i:s");
        
        if (empty($plant_id) || empty($emp_id)) die("Missing parameters");
        
        // Get last rack id
        $id = 0;
        $res = $conn->query("SELECT COUNT(*) AS countId FROM rack WHERE laneNO = '$laneNO'");
        if ($res && $row = $res->fetch_assoc()) {
            $id = (int)$row['countId'];
        }
        if ($res) $res->free();
        $new_id = str_pad($id + 1, 4, '0', STR_PAD_LEFT);
        $rack_no = $laneNO . "-" . $new_id;

        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM rack WHERE rack_no = ? AND section_name = ? AND laneNO = ?");
        $check->bind_param("sss", $rack_no,$section_name,$laneNO);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();
        
        if ($count > 0) {
            die("{\"status\":\"error\", \"message\":\"Dupplicate Rack No.\"}");
        }
        
 
        $stmt = $conn->prepare("INSERT INTO rack (plant_id, department, section_name, laneNO, rack_no, capacity, status, entry_by, entry_date) VALUES (?, 'Store',?, ?, ?, ?, 'Approved', ?, ?)");
        $stmt->bind_param("sssssss", $plant_id,  $section_name,  $laneNO,  $rack_no, $capacity, $emp_id, $entry_date);
        
        if ($stmt->execute()) {
            echo "{\"status\":\"success\", \"message\":\"Rack Generated Successfully " . $rack_no . "\"}";
        } else {
            echo "{\"status\":\"error\", \"message\":\"" . $stmt->error . "\"}";
        }
        
    } 
    else if ($_GET["type"] == "addNewLane") {
        
        $laneNO = trim($input["laneNO"]);
        $section_name = trim($input["section_name"]);
        $plant_id = $_GET["plant_id"] ?? '';
        $emp_id   = $_GET["emp_id"] ?? '';
        $entry_date = date("Y-m-d H:i:s");
        
        // --- Check for duplicate lane ---
        $checkSql = "SELECT COUNT(*) AS cnt FROM laneMaster WHERE laneNO = '$laneNO' AND section_name = '$section_name' AND plant_id = '$plant_id'";
        $checkResult = $conn->query($checkSql);
        if (!$checkResult) {
            echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
            exit;
        }
        
        $row = $checkResult->fetch_assoc();
        if ($row['cnt'] > 0) {
            echo json_encode(["status" => "duplicate_lane", "message" => "Lane already exists."]);
            exit;
        }
        
        // --- Insert new lane ---
        $insertSql = "
            INSERT INTO laneMaster (plant_id, section_name, laneNO, status, entryBy, entryOn)
            VALUES ('$plant_id', '$section_name','$laneNO', 'Active', '$emp_id', '$entry_date')
        ";
        if ($conn->query($insertSql)) {
            echo json_encode(["status" => "success", "message" => "Lane added successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        
    } 
    else if ($_GET["type"] == "generateLocations") {
        
        $laneNO = trim($input["laneNO"]);
        $rack_no = trim($input["rack_no"]);
        $capacity = (int)$input["capacity"];
        $section_name = trim($input["section_name"]);
        $plant_id = $_GET["plant_id"] ?? '';
        $emp_id   = $_GET["emp_id"] ?? '';
        $entry_date = date("Y-m-d H:i:s");
        
        
        
        
        
        
        for ($i = 1; $i <= $capacity; $i++) {
        
            // Count existing entries for the rack/lane/section
            $id = 0;
            $res = $conn->query("SELECT COUNT(*) AS countId FROM locationMaster WHERE laneNO = '$laneNO' AND rack_no = '$rack_no' AND section_name = '$section_name'");
            if ($res && $row = $res->fetch_assoc()) { $id = (int)$row['countId']; }
            if ($res) $res->free();
        
            // Generate new location number
            $new_id = str_pad($id + 1, 5, '0', STR_PAD_LEFT);
            $locationNo = $rack_no . "-" . $new_id;
        
            // Check for duplicates
            $checkSql = "SELECT COUNT(*) AS cnt FROM locationMaster WHERE locationNo = '$locationNo' AND plant_id = '$plant_id'";
            $checkResult = $conn->query($checkSql);
            if (!$checkResult) {
                echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
                exit;
            }
        
            $row = $checkResult->fetch_assoc();
            if ($row['cnt'] > 0) { continue;}
        
            // Insert new location
            $insertSql = "INSERT INTO `locationMaster` (`plant_id`, `section_name`, `laneNO`, `rack_no`, `locationNo`, `status`, `entryBy`, `entryOn`)
            VALUES ('$plant_id', '$section_name', '$laneNO', '$rack_no', '$locationNo', 'Active', '$emp_id', '$entry_date')";
        
            if (!$conn->query($insertSql)) {
                echo json_encode(["status" => "error", "message" => $conn->error]);
                exit;
            }
        }
        
        // After all inserts
        echo json_encode(["status" => "success", "message" => "All $capacity locations created successfully"]);

        
    } 
    else if ($_GET["type"] == "savePalette") {
        
        $noOfPalette = (int)$input["noOfPalette"];
        $section_name = trim($input["section_name"]);
        $plant_id = $_GET["plant_id"] ?? '';
        $emp_id   = $_GET["emp_id"] ?? '';
        $entry_date = date("Y-m-d H:i:s");
        
         
        for ($i = 1; $i <= $noOfPalette; $i++) {
            
            $insertSql = "INSERT INTO `palatteMaster`(`plant_id`, `section_name`, `status`, `entryBy`, `entryOn`) VALUES 
            ('$plant_id', '$section_name', 'Active', '$emp_id', '$entry_date')";
        
            if (!$conn->query($insertSql)) {
                echo json_encode(["status" => "error", "message" => $conn->error]);
                exit;
            }
        }
        
        // After all inserts
        echo json_encode(["status" => "success", "message" => "All $noOfPalette Palette created successfully"]);

        
    } 
    else if ($_GET["type"] == "getLocationByRacks") {
        
        $output = Array();
        
        if($_GET["section_name"] == 'ALL' || $_GET["laneNO"] == 'ALL' || $_GET["rack_no"] == 'ALL' ){
            $sql = "SELECT * FROM locationMaster WHERE plant_id = '".$_GET["plant_id"]."' AND status = 'Active'";

        }else{
            $sql = "SELECT * FROM locationMaster WHERE plant_id = '".$_GET["plant_id"]."' AND  section_name = '".$_GET["section_name"]."' AND  laneNO = '".$_GET["laneNO"]."' AND  rack_no = '".$_GET["rack_no"]."' AND status = 'Active'";
        }
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getDataForBarcodePrinting") {
        
        $output = Array();
        
        if($_GET["isView"] == 'Lanes'){
            $sql = "SELECT `id`, `plant_id`, `section_name`, `laneNO`, `laneNO` as barcodeUniqueId,  `status`, `entryBy`, `entryOn`  FROM laneMaster WHERE plant_id = '".$_GET["plant_id"]."' 
            AND section_name = '".$_GET["section_name"]."' AND status = 'Active'";
            
        }else if($_GET["isView"] == 'Racks'){
            $sql = "SELECT `id`, `plant_id`, `department`, `section_name`, `laneNO`, `rack_no`, `rack_no` as barcodeUniqueId, `capacity`, `status`, `entry_by` as entryBy, `entry_date` as entryOn FROM rack 
            WHERE plant_id = '".$_GET["plant_id"]."' AND section_name = '".$_GET["section_name"]."' AND laneNO = '".$_GET["laneNO"]."' AND status = 'Approved'";
            
        }else if($_GET["isView"] == 'Locations'){
            $sql = "SELECT `id`, `plant_id`, `section_name`, `laneNO`, `rack_no`, `locationNo`, `locationNo` as barcodeUniqueId, `status`, `entryBy`, `entryOn` FROM locationMaster WHERE plant_id = '".$_GET["plant_id"]."' 
            AND section_name = '".$_GET["section_name"]."' AND laneNO = '".$_GET["laneNO"]."' AND rack_no = '".$_GET["rack_no"]."' AND status = 'Active'";
            
        }else if($_GET["isView"] == 'Palettes'){
            $sql = "SELECT `id`, `plant_id`, `section_name`, `paletteNo`, `paletteNo` as barcodeUniqueId, `status`, `entryBy`, `entryOn` FROM palatteMaster WHERE plant_id = '".$_GET["plant_id"]."' 
            AND section_name = '".$_GET["section_name"]."' AND status = 'Active'";
            
        }
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPalette") {
        
        $output = Array();
        $sql = "SELECT * FROM palatteMaster WHERE plant_id='".$_GET["plant_id"]."' AND status='Active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLanes") {
        
        $output = Array();
        $sql = "SELECT * FROM laneMaster WHERE plant_id='".$_GET["plant_id"]."' AND status='Active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getLaneBySection") {
        
        $output = Array();
        $sql = "SELECT * FROM laneMaster WHERE plant_id='".$_GET["plant_id"]."' AND section_name='".$_GET["section_name"]."'  AND status='Active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingRacks") {
        
        $output = Array();
        $sql = "SELECT * FROM rack WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM section WHERE section_code='".$row["section"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["section_name"] = $row1["section_name"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "updateRack") {
        $sql = "UPDATE rack SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } 
    else if ($_GET["type"] == "getRacksLog") {
        $output = Array();
        $sql = "SELECT * FROM rack WHERE status = 'Approved' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
     
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRacksByLane") {
        $output = Array();
        $sql = "SELECT * FROM rack WHERE status = 'Approved' AND laneNO='".$_GET["laneNO"]."' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
     
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingRacksForLocationCreationByLane") {
        $output = Array();
        $sql = "SELECT * FROM rack WHERE status = 'Approved' AND laneNO = '" . $_GET["laneNO"] . "' AND plant_id = '" . $_GET["plant_id"] . "' 
        AND rack_no NOT IN ( SELECT rack_no FROM locationMaster WHERE plant_id = '" . $_GET["plant_id"] . "')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
     
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getSections") {
        $output = Array();
        $sql = "SELECT * FROM section WHERE plant_id = '".$_GET["plant_id"]."' AND department='Store'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getMaterialsByTypes") {
        $output = Array();
          $sql = "SELECT * FROM material where material_type = '".$_GET["material_type"]."' AND plant_id ='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getUnits") {
        $output = Array();
        
         $sql = "SELECT * FROM unit WHERE user_no='".$_GET["user_no"]."' AND plant_id='".$_GET["plant_id"]."'";
        //$sql = "SELECT * FROM unit";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSectionRacks") {
        $output = Array();
        $sql = "SELECT * FROM section WHERE user_no='".$_GET["user_no"]."' AND department='Store'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM rack WHERE section='".$row["section_code"]."' 
                AND status='approve' AND empty='yes'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                    $row["racks"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getAllocatedRacks") {
    $output = array();
    $sql = "SELECT r.*, s.section_name FROM rack r LEFT JOIN section s ON r.section=s.section_code WHERE r.user_no='".$_GET["user_no"]."' AND r.empty='no' AND r.section LIKE '%".$_GET["section_code"]."' AND r.material_type LIKE '%".$_GET["material_type"]."%'ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingMaterials") {
    $output = array();
    $sql = "SELECT s.*, m.material_name, m.material_type, m.material_subtype, m.grade, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.status='Approved' and s.rack_no='' AND m.material_type IN ('Raw Material', 'Packing Material')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "allocateRack") {
    $sql = "UPDATE stock_book SET section='".$input["section"]."', rack_no='".$input["rack_no"]."' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
        
        $sql = "UPDATE rack SET empty='no', material_type='".$input["material_type"]."', material_code='".$input["material_code"]."', qty='".$input["qty"]."', material_unit='".$input["unit"]."' WHERE rack_no='".$input["rack_no"]."'";
        $conn->query($sql);
        
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}
else if($_GET["type"] == 'getChartData'){
    $output = Array();
    $series = Array();
    $lables = Array();
    
    $sql = "SELECT * FROM section WHERE department='Store'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $lables[] =$row['section_name'];
            
            $sql2 = "SELECT * FROM rack WHERE empty='no' AND section_name = '".$row['section_name']."'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $series[] = $row2;
                }
            }
        }
    }
    
    $output["series"] = $series;
    $output["lables"] = $lables;
    echo json_encode($output);
}
else if ($_GET["type"] == "downloadLocationChart") 
{
       if($_GET["plant_id"] == 96) {//Olive
       
        $_GET['filename'] = 'Location Chart'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Location Chart</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td>Rack No.</td>
                    <td>Item Code.</td>
                    <td>Item Name.</td>
                    <td >Update by (Sign/Date)</td>
                    <td>Remark</td>
                </tr>
            </thead>';
            $i=1;
        $sql = "SELECT r.*, s.section_name FROM rack r LEFT JOIN section s ON r.section=s.section_code WHERE r.user_no='".$_GET["user_no"]."' AND r.empty='no' AND r.section LIKE '%".$_GET["section_code"]."' AND r.material_type LIKE '%".$_GET["material_type"]."%'ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM section WHERE section_code='".$row["section"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["section_name"] = $row1["section_name"];
                }
            }
                $html.='<tr nobr="true">
                        <td>'.$row['section_name'].'</td>
                        <td >'.$row['rack_no'].'</td>
                        <td >'.$row['material_type'].'</td>
                        <td >'.$row['material_code'].'</td>
                        <td style="text-align:right;">'.$row['qty'].'<td style="text-align:left;">'.$row['unit'].'</td></td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('OliveLocationChart.pdf', 'I');
    }
        $_GET['filename'] = 'Location Chart'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Location Chart</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 15%;">Sr.</td>
                    <td style="width: 15%;">Section</td>
                    <td style="width: 15%;">Rack No.</td>
                    <td style="width: 20%;">Material Type</td>
                    <td style="width: 20%;">Material Code</td>
                    <td style="width: 15%;">Qty</td>
                </tr>
            </thead>';
            $i=1;
        $sql = "SELECT r.*, s.section_name FROM rack r LEFT JOIN section s ON r.section=s.section_code WHERE r.user_no='".$_GET["user_no"]."' AND r.empty='no' AND r.section LIKE '%".$_GET["section_code"]."' AND r.material_type LIKE '%".$_GET["material_type"]."%'ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM section WHERE section_code='".$row["section"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["section_name"] = $row1["section_name"];
                }
            }
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$i.'.</td>
                        <td style="width: 15%;">'.$row['section_name'].'</td>
                        <td style="width: 15%;">'.$row['rack_no'].'</td>
                        <td style="width: 20%;">'.$row['material_type'].'</td>
                        <td style="width: 20%;">'.$row['material_code'].'</td>
                        <td style="width: 15%;text-align:right;">'.$row['qty'].'<td style="text-align:left;">'.$row['unit'].'</td></td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('LocationChart.pdf', 'I');
    }

    else if ($_GET["type"] == "downloadRacksLog") {
        $_GET['filename'] = 'Racks List'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Racks List</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:25%; text-align:centre;"><b>Section</b></td>
                <td style="width:20%; text-align:centre;"><b>Rack No.</b></td>
                <td style="width:25%; text-align:centre;"><b>Rack Name</b></td>
                <td style="width:20%; text-align:centre;"><b>Capacity</b></td>
            </tr>';
             $sql = "SELECT * FROM rack WHERE user_no='".$_GET["user_no"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM section WHERE section_code='".$row["section"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["section_name"] = $row1["section_name"];
                }
            }
            $i=1;
            $html.='<tr nobr="true">
                <td style="width:10%;  text-align:centre;">'.$i.'</td>
                <td style="width:25%;  text-align:centre;">'.$row['section_name'].'</td>
                <td style="width:20%;  text-align:centre;">'.$row['rack_no'].'</td>
                <td style="width:25%;  text-align:centre;">'.$row['rack_name'].'</td>
                <td style="width:20%; text-align:centre;">'.$row['capacity'].'</td>
            </tr>';
            $i++;
        }
    }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RacksLog.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>