<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';


 
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

 
 
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
    
 
if ($_GET['type'] === 'saveDossier') {
    $entry_date = date('Y-m-d H:i:s');
    $timestamp = time();

    // GET params (if sent via querystring)
    $user_no = isset($_GET['user_no']) ? $_GET['user_no'] : '';
    $dossier_req_id = isset($_GET['dossier_req_id']) ? $_GET['dossier_req_id'] : '';
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    // POST fields (from FormData)
    $brand_name = isset($_POST['brand_name']) ? $_POST['brand_name'] : '';
    $generic_name = isset($_POST['generic_name']) ? $_POST['generic_name'] : '';
    $label_claim = isset($_POST['label_claim']) ? $_POST['label_claim'] : '';
    $finished_product_life = isset($_POST['finished_product_life']) ? $_POST['finished_product_life'] : '';
    $packing_style = isset($_POST['packing_style']) ? $_POST['packing_style'] : '';
    $country = isset($_POST['country']) ? $_POST['country'] : '';
    $mfg_by = isset($_POST['mfg_by']) ? $_POST['mfg_by'] : '';
    $marketed_by = isset($_POST['marketed_by']) ? $_POST['marketed_by'] : '';
    $mfg_address = isset($_POST['mfg_address']) ? $_POST['mfg_address'] : '';
    $marketing_address = isset($_POST['marketing_address']) ? $_POST['marketing_address'] : '';
    $applicant_name = isset($_POST['applicant_name']) ? $_POST['applicant_name'] : '';
    $market_product = isset($_POST['market_product']) ? $_POST['market_product'] : '';
    $dossier_lang = isset($_POST['dossier_lang']) ? $_POST['dossier_lang'] : '';
    $registered_product = isset($_POST['registered_product']) ? $_POST['registered_product'] : '';
    $client_name = isset($_POST['client_name']) ? $_POST['client_name'] : '';
    $document_checklist = isset($_POST['document_checklist']) ? $_POST['document_checklist'] : '';
    $expected_date = isset($_POST['expected_date']) ? $_POST['expected_date'] : null;
    $special_remark = isset($_POST['special_remark']) ? $_POST['special_remark'] : '';
    $dossiers = isset($_POST['dossiers']) ? $_POST['dossiers'] : '';

    // Handle file upload (optional)
    $file_name = '';
    if (!empty($_FILES['file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $file_name = date('YmdHis', $timestamp) . '.pdf';
            $upload_dir = __DIR__ . '/../upload/dossier/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $dst = $upload_dir . $file_name;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dst)) {
                // upload failed; you can decide whether to abort or continue without file
                echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF allowed.']);
            exit;
        }
    }

    // Prepared statement to avoid SQL injection (assuming $conn is mysqli)
    $sql = "INSERT INTO dossier_entry(
        user_no, dossier_req_id, brand_name, generic_name, label_claim,
        finished_product_life, packing_style, country, mfg_by, marketed_by,
        mfg_address, marketing_address, applicant_name, market_product, dossier_lang,
        registered_product, client_name, document_checklist, expected_date, special_remark,
        file, apis, entry_by, entry_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            'ssssssssssssssssssssssss',
            $user_no,
            $dossier_req_id,
            $brand_name,
            $generic_name,
            $label_claim,
            $finished_product_life,
            $packing_style,
            $country,
            $mfg_by,
            $marketed_by,
            $mfg_address,
            $marketing_address,
            $applicant_name,
            $market_product,
            $dossier_lang,
            $registered_product,
            $client_name,
            $document_checklist,
            $expected_date,
            $special_remark,
            $file_name,
            $dossiers,
            $emp_id,
            $entry_date
        );

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
    else if ($_GET["type"] == "getDossiersLog") {
        $output = array();
         $sql = "SELECT * FROM dossier_entry   ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["apis"] = json_decode($row["apis"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>