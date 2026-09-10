<?php

  ini_set('display_errors', 1);
    error_reporting(E_ALL);


require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql); 
$_GET["emp_id"] = "";
$_GET["department"] = "";
$entry_date = date("Y-m-d h:i:s", $timestamp);

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
    
    
   if ($_GET["type"] == "saveRca") {

    $input = json_decode(file_get_contents("php://input"), true);

   $plant_id = $_GET["plant_id"] ?? '';
    $entry_by = $_GET["emp_id"]?? '';

    // ===== AUTO RCA NO =====
    $year = date('y');
    $prefix = "RCA/$year/";

    $sqlLast = "SELECT rca_no 
                FROM rca_reports 
                WHERE rca_no LIKE '$prefix%' 
                ORDER BY id DESC 
                LIMIT 1";

    $result = $conn->query($sqlLast);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = (int)substr($row['rca_no'], -3);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    $rcaNo = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    // ======================


    // ===== FIX FOR EMPTY VALUES =====
$date = !empty($input['date']) ? $input['date'] : date('Y-m-d');
$mfg = !empty($input['mfg']) ? $input['mfg'] : date('Y-m-d');
$expiry = !empty($input['expiry']) ? $input['expiry'] : date('Y-m-d');

    $dept = isset($input['dept']) ? $input['dept'] : '';
    $qmsno = isset($input['qmsno']) ? $input['qmsno'] : '';
    $material_name = isset($input['material_name']) ? $input['material_name'] : '';
    $batch = isset($input['batch']) ? $input['batch'] : '';
    $observation = isset($input['observation']) ? $input['observation'] : '';
    $tool = isset($input['tool']) ? $input['tool'] : '';
    $capa_action = isset($input['capa_action']) ? $input['capa_action'] : '';
    $capa = isset($input['capa']) ? $input['capa'] : '';
    // ======================


    $sql = "INSERT INTO rca_reports 
        (dept, date, rca_no, qms_no, material_name, batch, mfg_date, expiry, observation, tools, plant_id, entry_by, entry_date, capa_action, capa_required, status)
        VALUES (
            '$dept',
            ".($date ? "'$date'" : "NULL").",
            '$rcaNo',
            '$qmsno',
            '$material_name',
            '$batch',
            ".($mfg ? "'$mfg'" : "NULL").",
            ".($expiry ? "'$expiry'" : "NULL").",
            '$observation',
            '$tool',
            '$plant_id',
            '$entry_by',
            '$entry_date',
            '$capa_action',
            '$capa',
            'open'
        )";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "failed", "error" => $conn->error]);
    }
}


    else if ($_GET["type"] == "getRcaLog") {
    $sql = "SELECT * FROM rca_reports where status='open' ORDER BY id DESC";
    $res = mysqli_query($conn, $sql);
    $data = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }

    echo json_encode($data);
}

 else if ($_GET["type"] == "getRcaMainLog") {
    $sql = "SELECT * FROM rca_reports where status='close' ORDER BY id DESC";
    $res = mysqli_query($conn, $sql);
    $data = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }

    echo json_encode($data);
}

else if ($_GET['type'] == 'approveRca') {

    $data = json_decode(file_get_contents("php://input"), true);
    $id = (int)$data['id'];

    $sql = "UPDATE rca_reports
            SET status = 'close', approve_date = NOW(), approve_by ='".$_GET["emp_id"]."'
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}

    
}
$conn->close();
?>
