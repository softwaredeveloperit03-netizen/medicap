<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

    function escDoc($conn, $value) {
        return $conn->real_escape_string(isset($value) ? $value : '');
    }

    function ensureClientDocumentWorkflowColumns($conn) {
        $cols = array(
            'request_no' => "VARCHAR(50) NULL",
            'status' => "VARCHAR(80) DEFAULT 'Pending QA Head'",
            'assigned_dept' => "VARCHAR(50) NULL",
            'assigned_by' => "VARCHAR(50) NULL",
            'assigned_date' => "DATETIME NULL",
            'file' => "VARCHAR(255) DEFAULT 'NA'",
            'uploaded_by' => "VARCHAR(50) NULL",
            'uploaded_date' => "DATETIME NULL",
            'approved_by' => "VARCHAR(50) NULL",
            'approved_date' => "DATETIME NULL",
            'qa_remarks' => "TEXT NULL",
        );
        foreach ($cols as $col => $def) {
            $check = $conn->query("SHOW COLUMNS FROM `client_document` LIKE '".$col."'");
            if ($check && $check->num_rows == 0) {
                $conn->query("ALTER TABLE `client_document` ADD COLUMN `".$col."` ".$def);
            }
        }
    }

    function fetchClientDocumentRows($conn, $where) {
        $output = array();
        $sql = "SELECT d.*, c.TrdNm FROM client_document d
                LEFT JOIN client c ON d.client_code = c.client_code
                WHERE ".$where." ORDER BY d.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['status'])) {
                    $row['status'] = 'Pending QA Head';
                }
                $output[] = $row;
            }
        }
        return $output;
    }

    ensureClientDocumentWorkflowColumns($conn);

    if ($_GET["type"] == "saveRequest") {
        $documents = isset($input["documents"]) && is_array($input["documents"]) ? $input["documents"] : array();
        if (count($documents) === 0) {
            echo json_encode(array("status" => "error", "message" => "No documents added"));
            exit;
        }

        $plant_id = escDoc($conn, $_GET["plant_id"]);
        $client_code = escDoc($conn, $input["client_code"] ?? '');
        $emp_id = escDoc($conn, $_GET["emp_id"]);
        $baseNo = $plant_id.date('YmdHis');
        $success = true;

        for ($i = 0; $i < count($documents); $i++) {
            $document = $documents[$i];
            $request_no = $baseNo.str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
            $document_type = escDoc($conn, $document["document_type"] ?? '');
            $document_name = escDoc($conn, $document["document_name"] ?? '');
            $required_in = escDoc($conn, $document["required_in"] ?? '');

            $sql = "INSERT INTO client_document (
                        plant_id, client_code, document_type, documents, required_in,
                        entry_by, entry_date, request_no, status, file
                    ) VALUES (
                        '".$plant_id."', '".$client_code."', '".$document_type."', '".$document_name."',
                        '".$required_in."', '".$emp_id."', '".$entry_date."', '".$request_no."',
                        'Pending QA Head', 'NA'
                    )";

            if (!$conn->query($sql)) {
                $success = false;
                break;
            }
        }

        if ($success) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    } else if ($_GET["type"] == "getRequests") {
        $plant_id = escDoc($conn, $_GET["plant_id"]);
        echo json_encode(fetchClientDocumentRows($conn, "d.plant_id='".$plant_id."'"));
    } else if ($_GET["type"] == "getRequestsQA" || $_GET["type"] == "getRequestsQAHead") {
        $plant_id = escDoc($conn, $_GET["plant_id"]);
        echo json_encode(fetchClientDocumentRows(
            $conn,
            "d.plant_id='".$plant_id."' AND d.status IN ('Pending QA Head', 'Pending QA Approval')"
        ));
    } else if ($_GET["type"] == "getRequestsByDept") {
        $plant_id = escDoc($conn, $_GET["plant_id"]);
        $dept = escDoc($conn, $_GET["dept"] ?? '');
        echo json_encode(fetchClientDocumentRows(
            $conn,
            "d.plant_id='".$plant_id."' AND d.assigned_dept='".$dept."' AND d.status='Assigned to Department'"
        ));
    } else if ($_GET["type"] == "assignRequestDept") {
        $id = escDoc($conn, $input["id"] ?? $_GET["id"] ?? '');
        $dept = escDoc($conn, $input["assigned_dept"] ?? '');
        $emp_id = escDoc($conn, $_GET["emp_id"]);

        if ($id === '' || ($dept !== 'QC' && $dept !== 'Regulatory')) {
            echo json_encode(array("status" => "error", "message" => "Invalid request or department"));
            exit;
        }

        $sql = "UPDATE client_document SET
                    assigned_dept='".$dept."',
                    assigned_by='".$emp_id."',
                    assigned_date='".$entry_date."',
                    status='Assigned to Department'
                WHERE id='".$id."'";

        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    } else if ($_GET["type"] == "saveDocuments") {
        $target_dir = "../../../upload/masterDocuments/";
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0777, true);
        }

        $plant_id = escDoc($conn, $_GET["plant_id"]);
        $request_no = escDoc($conn, $_GET["request_no"] ?? ($_POST["request_no"] ?? ''));
        $id = escDoc($conn, $_GET["id"] ?? ($_POST["id"] ?? ''));
        $emp_id = escDoc($conn, $_GET["emp_id"]);
        $docFile = 'NA';

        if (isset($_FILES["document"]["name"]) && $_FILES["document"]["name"] !== '') {
            $docFile = $plant_id.$request_no."_".basename($_FILES["document"]["name"]);
            $target_file = $target_dir.$docFile;
            move_uploaded_file($_FILES["document"]["tmp_name"], $target_file);
        } else {
            echo json_encode(array("status" => "error", "message" => "Document file required"));
            exit;
        }

        $where = $id !== '' ? "id='".$id."'" : "request_no='".$request_no."'";
        $sql = "UPDATE client_document SET
                    file='".$conn->real_escape_string($docFile)."',
                    status='Pending QA Approval',
                    uploaded_by='".$emp_id."',
                    uploaded_date='".$entry_date."'
                WHERE ".$where;

        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    } else if ($_GET["type"] == "approveRequestDocument") {
        $id = escDoc($conn, $input["id"] ?? $_GET["id"] ?? '');
        $emp_id = escDoc($conn, $_GET["emp_id"]);
        $remarks = escDoc($conn, $input["qa_remarks"] ?? '');

        $sql = "UPDATE client_document SET
                    status='Approved',
                    approved_by='".$emp_id."',
                    approved_date='".$entry_date."',
                    qa_remarks='".$remarks."'
                WHERE id='".$id."' AND status='Pending QA Approval'";

        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    } else if ($_GET["type"] == "rejectRequestDocument") {
        $id = escDoc($conn, $input["id"] ?? $_GET["id"] ?? '');
        $emp_id = escDoc($conn, $_GET["emp_id"]);
        $remarks = escDoc($conn, $input["qa_remarks"] ?? '');

        $sql = "UPDATE client_document SET
                    status='Rejected',
                    approved_by='".$emp_id."',
                    approved_date='".$entry_date."',
                    qa_remarks='".$remarks."'
                WHERE id='".$id."'";

        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    }
}

$conn->close();
?>
