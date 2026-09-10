<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
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

    $sqlLog = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR)
               VALUES ('FRONTEND','" . $token . "','" . $_GET["type"] . "','" . $entry_date . "','" . $_GET["department"] . "','" . $_GET["emp_id"] . "','" . $_SERVER['REQUEST_METHOD'] . "','" . $_SERVER['REMOTE_ADDR'] . "')";
    $conn->query($sqlLog);

    if ($_GET["type"] == "getMoaStpCombinedLogs") {
        $output = Array();
        $sql = "SELECT * FROM test WHERE plant_id = '" . $_GET["plant_id"] . "' ORDER BY id DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                if (!isset($row["test_method_no"]) || trim($row["test_method_no"]) == "") {
                    $row["test_method_no"] = "TEST-" . $row["id"];
                }
                if (!isset($row["version_no"]) || trim($row["version_no"]) == "") {
                    $row["version_no"] = "01";
                }
                if (!isset($row["effective_date"]) || trim($row["effective_date"]) == "") {
                    $row["effective_date"] = $row["approve_date"];
                }
                if (!isset($row["review_date"]) || trim($row["review_date"]) == "") {
                    $baseDate = $row["effective_date"];
                    if ($baseDate != null && $baseDate != "") {
                        $row["review_date"] = date('Y-m-d', strtotime($baseDate . ' +365 day'));
                    } else {
                        $row["review_date"] = "";
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMoaStpRevisionRequestStatus") {
        $output = Array();
        $raw_docs = isset($_GET["doc_nos"]) ? $_GET["doc_nos"] : '';
        $parts = explode(",", $raw_docs);
        $safe = Array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe[] = "'" . $conn->real_escape_string($p) . "'";
            }
        }
        if (count($safe) === 0) {
            echo json_encode($output);
        } else {
            $in = implode(",", $safe);
            $sql = "SELECT id, refDocNo, status, revisionComment, entryBy, entryOn
                    FROM revisionRequest
                    WHERE reqFor = 'MOA/STP Revision Request'
                    AND refDocNo IN ($in)
                    ORDER BY id DESC";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $docNo = $row["refDocNo"];
                    if (!isset($output[$docNo])) {
                        $output[$docNo] = $row;
                    }
                }
            }
            echo json_encode($output);
        }
    } else if ($_GET["type"] == "saveMoaStpRevisionRequest") {
        $refDocNo = isset($input["doc_no"]) ? trim($input["doc_no"]) : '';
        $refDocId = isset($input["doc_id"]) ? trim($input["doc_id"]) : '';
        $refDocName = isset($input["doc_name"]) ? trim($input["doc_name"]) : '';
        $reason = isset($input["reason"]) ? trim($input["reason"]) : '';
        $remarks = isset($input["remarks"]) ? trim($input["remarks"]) : '';

        if ($refDocNo == '' || $reason == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing required fields\"}";
        } else {
            $comment = $reason;
            if ($remarks != '') {
                $comment .= " | Remarks: " . $remarks;
            }

            $sqlCheck = "SELECT id FROM revisionRequest
                         WHERE plant_id = '" . $_GET["plant_id"] . "'
                         AND reqFor = 'MOA/STP Revision Request'
                         AND refDocNo = '" . $conn->real_escape_string($refDocNo) . "'
                         AND status = 'Pending'
                         ORDER BY id DESC LIMIT 1";
            $resCheck = $conn->query($sqlCheck);
            if ($resCheck && $resCheck->num_rows > 0) {
                echo "{\"status\":\"exists\"}";
            } else {
                $sql = "INSERT INTO revisionRequest (plant_id, refDocNo, refDocId, refDocName, revisionComment, reqFor, entryBy, entryOn, status)
                        VALUES ('" . $_GET["plant_id"] . "',
                                '" . $conn->real_escape_string($refDocNo) . "',
                                '" . $conn->real_escape_string($refDocId) . "',
                                '" . $conn->real_escape_string($refDocName) . "',
                                '" . $conn->real_escape_string($comment) . "',
                                'MOA/STP Revision Request',
                                '" . $_GET["emp_id"] . "',
                                '" . $entry_date . "',
                                'Pending')";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"" . $conn->error . "\"}";
                }
            }
        }
    } else if ($_GET["type"] == "getChangeControlStatusForMoaStp") {
        $output = Array();
        $raw_docs = isset($_GET["doc_nos"]) ? $_GET["doc_nos"] : '';
        $parts = explode(",", $raw_docs);
        foreach ($parts as $p) {
            $docNo = trim($p);
            if ($docNo == '') {
                continue;
            }
            $esc = $conn->real_escape_string($docNo);
            $sql = "SELECT id, ctrl_no, status, titleOfcc, entryDate
                    FROM changecontrol
                    WHERE plant_id = '" . $_GET["plant_id"] . "'
                    AND (
                        titleOfcc LIKE 'MOA/STP Revision - " . $esc . "%'
                        OR proposed LIKE '%MOA/STP: " . $esc . "%'
                    )
                    ORDER BY id DESC LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $output[$docNo] = $res->fetch_assoc();
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMoaStpWithDetailsForDraft") {
        $output = Array();
        $docNo = isset($_GET["doc_no"]) ? trim($_GET["doc_no"]) : '';
        if ($docNo == '') {
            echo json_encode($output);
        } else {
            $sql = "SELECT * FROM test
                    WHERE plant_id = '" . $_GET["plant_id"] . "'
                    AND test_method_no = '" . $conn->real_escape_string($docNo) . "'
                    ORDER BY id DESC LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $subtests = Array();
                $sqlSub = "SELECT * FROM subtest WHERE test_id = '" . $row["id"] . "'";
                $resSub = $conn->query($sqlSub);
                if ($resSub && $resSub->num_rows > 0) {
                    while ($s = $resSub->fetch_assoc()) {
                        $subtests[] = $s;
                    }
                }
                $row["subtests"] = $subtests;
                $output = $row;
            }
            echo json_encode($output);
        }
    } else if ($_GET["type"] == "saveMoaStpDraft") {
        $sqlCreate = "CREATE TABLE IF NOT EXISTS moa_stp_draft (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            doc_no VARCHAR(100) NOT NULL,
            source_doc_id VARCHAR(50) DEFAULT NULL,
            source_version_no VARCHAR(20) DEFAULT NULL,
            draft_version_no VARCHAR(20) DEFAULT NULL,
            draft_title VARCHAR(255) DEFAULT NULL,
            draft_data LONGTEXT,
            status VARCHAR(30) DEFAULT 'Draft',
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP,
            updateBy VARCHAR(50) DEFAULT NULL,
            updateOn DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreate);

        $sqlCreateObs = "CREATE TABLE IF NOT EXISTS moa_stp_obsolete_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            doc_no VARCHAR(100) NOT NULL,
            previous_version_no VARCHAR(20) DEFAULT NULL,
            previous_doc_data LONGTEXT,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(50) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreateObs);

        $docNo = isset($input["doc_no"]) ? trim($input["doc_no"]) : '';
        if ($docNo == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing document number\"}";
        } else {
            $payload = json_encode($input);
            $title = isset($input["draft_title"]) ? $input["draft_title"] : "MOA/STP Draft";
            $sourceId = isset($input["source_doc_id"]) ? $input["source_doc_id"] : '';
            $sourceVersion = isset($input["source_version_no"]) ? $input["source_version_no"] : '';
            $draftVersion = isset($input["draft_version_no"]) ? $input["draft_version_no"] : '';

            if ($sourceVersion != '') {
                $sqlObsCheck = "SELECT id FROM moa_stp_obsolete_log
                                WHERE plant_id = '" . $_GET["plant_id"] . "'
                                AND doc_no = '" . $conn->real_escape_string($docNo) . "'
                                AND previous_version_no = '" . $conn->real_escape_string($sourceVersion) . "'
                                ORDER BY id DESC LIMIT 1";
                $resObsCheck = $conn->query($sqlObsCheck);
                if (!($resObsCheck && $resObsCheck->num_rows > 0)) {
                    $base = Array();
                    $sqlBase = "SELECT * FROM test
                                WHERE plant_id = '" . $_GET["plant_id"] . "'
                                AND test_method_no = '" . $conn->real_escape_string($docNo) . "'
                                ORDER BY id DESC LIMIT 1";
                    $resBase = $conn->query($sqlBase);
                    if ($resBase && $resBase->num_rows > 0) {
                        $rowB = $resBase->fetch_assoc();
                        $subs = Array();
                        $sqlSub = "SELECT * FROM subtest WHERE test_id = '" . $rowB["id"] . "'";
                        $resSub = $conn->query($sqlSub);
                        if ($resSub && $resSub->num_rows > 0) {
                            while ($r = $resSub->fetch_assoc()) {
                                $subs[] = $r;
                            }
                        }
                        $rowB["subtests"] = $subs;
                        $base = $rowB;
                    }
                    $sqlObsInsert = "INSERT INTO moa_stp_obsolete_log
                                    (plant_id, doc_no, previous_version_no, previous_doc_data, entryBy)
                                    VALUES
                                    ('" . $_GET["plant_id"] . "',
                                     '" . $conn->real_escape_string($docNo) . "',
                                     '" . $conn->real_escape_string($sourceVersion) . "',
                                     '" . $conn->real_escape_string(json_encode($base)) . "',
                                     '" . $_GET["emp_id"] . "')";
                    $conn->query($sqlObsInsert);
                }
            }

            $sqlCheck = "SELECT id FROM moa_stp_draft
                         WHERE plant_id = '" . $_GET["plant_id"] . "'
                         AND doc_no = '" . $conn->real_escape_string($docNo) . "'
                         AND status = 'Draft'
                         ORDER BY id DESC LIMIT 1";
            $resCheck = $conn->query($sqlCheck);
            if ($resCheck && $resCheck->num_rows > 0) {
                $id = $resCheck->fetch_assoc()["id"];
                $sqlU = "UPDATE moa_stp_draft SET
                            source_doc_id = '" . $conn->real_escape_string($sourceId) . "',
                            source_version_no = '" . $conn->real_escape_string($sourceVersion) . "',
                            draft_version_no = '" . $conn->real_escape_string($draftVersion) . "',
                            draft_title = '" . $conn->real_escape_string($title) . "',
                            draft_data = '" . $conn->real_escape_string($payload) . "',
                            updateBy = '" . $_GET["emp_id"] . "'
                         WHERE id = '" . $id . "'";
                if ($conn->query($sqlU)) {
                    echo "{\"status\":\"success\",\"id\":\"" . $id . "\"}";
                } else {
                    echo "{\"status\":\"" . $conn->error . "\"}";
                }
            } else {
                $sqlI = "INSERT INTO moa_stp_draft
                        (plant_id, doc_no, source_doc_id, source_version_no, draft_version_no, draft_title, draft_data, status, entryBy, updateBy)
                        VALUES
                        ('" . $_GET["plant_id"] . "',
                         '" . $conn->real_escape_string($docNo) . "',
                         '" . $conn->real_escape_string($sourceId) . "',
                         '" . $conn->real_escape_string($sourceVersion) . "',
                         '" . $conn->real_escape_string($draftVersion) . "',
                         '" . $conn->real_escape_string($title) . "',
                         '" . $conn->real_escape_string($payload) . "',
                         'Draft',
                         '" . $_GET["emp_id"] . "',
                         '" . $_GET["emp_id"] . "')";
                if ($conn->query($sqlI)) {
                    echo "{\"status\":\"success\",\"id\":\"" . $conn->insert_id . "\"}";
                } else {
                    echo "{\"status\":\"" . $conn->error . "\"}";
                }
            }
        }
    } else if ($_GET["type"] == "getMoaStpDraftByDocNo") {
        $output = Array();
        $docNo = isset($_GET["doc_no"]) ? trim($_GET["doc_no"]) : '';
        if ($docNo == '') {
            echo json_encode($output);
        } else {
            $sql = "SELECT * FROM moa_stp_draft
                    WHERE plant_id = '" . $_GET["plant_id"] . "'
                    AND doc_no = '" . $conn->real_escape_string($docNo) . "'
                    ORDER BY id DESC LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $row["draft_data"] = json_decode($row["draft_data"], true);
                $output = $row;
            }
            echo json_encode($output);
        }
    } else if ($_GET["type"] == "finalizeMoaStpDraftToPending") {
        $docNo = isset($input["doc_no"]) ? trim($input["doc_no"]) : '';
        if ($docNo == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing MOA/STP number\"}";
        } else {
            $escDoc = $conn->real_escape_string($docNo);
            $sqlDraft = "SELECT * FROM moa_stp_draft
                         WHERE plant_id = '" . $_GET["plant_id"] . "'
                           AND doc_no = '" . $escDoc . "'
                         ORDER BY id DESC
                         LIMIT 1";
            $resDraft = $conn->query($sqlDraft);
            if (!($resDraft && $resDraft->num_rows > 0)) {
                echo "{\"status\":\"failed\",\"message\":\"Draft not found\"}";
            } else {
                $draftRow = $resDraft->fetch_assoc();
                $draftData = json_decode($draftRow["draft_data"], true);
                if (!is_array($draftData)) {
                    echo "{\"status\":\"failed\",\"message\":\"Invalid draft data\"}";
                } else {
                    $testId = intval(isset($draftData["source_doc_id"]) ? $draftData["source_doc_id"] : 0);
                    if ($testId <= 0) {
                        $sqlTest = "SELECT id FROM test
                                    WHERE plant_id = '" . $_GET["plant_id"] . "'
                                      AND test_method_no = '" . $escDoc . "'
                                    ORDER BY id DESC LIMIT 1";
                        $resTest = $conn->query($sqlTest);
                        if ($resTest && $resTest->num_rows > 0) {
                            $testId = intval($resTest->fetch_assoc()["id"]);
                        }
                    }
                    if ($testId <= 0) {
                        echo "{\"status\":\"failed\",\"message\":\"MOA/STP test record not found\"}";
                    } else {
                        $testType = $conn->real_escape_string(isset($draftData["test_type"]) ? $draftData["test_type"] : '');
                        $testName = $conn->real_escape_string(isset($draftData["test"]) ? $draftData["test"] : '');
                        $sqlUpdate = "UPDATE test SET
                                      test_type = '".$testType."',
                                      test = '".$testName."',
                                      status = 'Pending',
                                      approve_by = '',
                                      approve_date = NULL
                                      WHERE id = '".$testId."'";
                        if (!($conn->query($sqlUpdate))) {
                            echo "{\"status\":\"failed\",\"message\":\"".$conn->error."\"}";
                        } else {
                            $conn->query("DELETE FROM subtest WHERE test_id = '".$testId."'");
                            $subtests = isset($draftData["subtests"]) && is_array($draftData["subtests"]) ? $draftData["subtests"] : Array();
                            for ($i = 0; $i < count($subtests); $i++) {
                                $s = $subtests[$i];
                                $sqlIns = "INSERT INTO subtest
                                          (plant_id, test_id, test_type, test, subtest, status, entry_by, entry_date)
                                          VALUES
                                          ('".$_GET["plant_id"]."',
                                           '".$testId."',
                                           '".$testType."',
                                           '".$testName."',
                                           '".$conn->real_escape_string(isset($s["subtest"]) ? $s["subtest"] : "")."',
                                           'Pending',
                                           '".$_GET["emp_id"]."',
                                           '".$entry_date."')";
                                $conn->query($sqlIns);
                            }
                            $conn->query("UPDATE moa_stp_draft SET status = 'Final Draft', updateBy = '".$_GET["emp_id"]."' WHERE id = '".$draftRow["id"]."'");
                            echo "{\"status\":\"success\"}";
                        }
                    }
                }
            }
        }
    } else if ($_GET["type"] == "getMoaStpDraftStatusForTests") {
        $output = Array();
        $raw_docs = isset($_GET["doc_nos"]) ? $_GET["doc_nos"] : '';
        $parts = explode(",", $raw_docs);
        $safe = Array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe[] = "'" . $conn->real_escape_string($p) . "'";
            }
        }
        if (count($safe) === 0) {
            echo json_encode($output);
        } else {
            $in = implode(",", $safe);
            $sql = "SELECT id, doc_no, draft_version_no, updateOn, status
                    FROM moa_stp_draft
                    WHERE plant_id = '" . $_GET["plant_id"] . "'
                    AND doc_no IN ($in)
                    ORDER BY id DESC";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $docNo = $row["doc_no"];
                    if (!isset($output[$docNo])) {
                        $output[$docNo] = $row;
                    }
                }
            }
            echo json_encode($output);
        }
    } else if ($_GET["type"] == "saveMoaStpCcEditHistory") {
        $sqlCreate = "CREATE TABLE IF NOT EXISTS moa_stp_cc_edit_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            doc_no VARCHAR(100) NOT NULL,
            draft_particular VARCHAR(255) DEFAULT NULL,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(80) DEFAULT NULL,
            rejected_from_department VARCHAR(120) DEFAULT NULL,
            rejected_by VARCHAR(80) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreate);

        $docNo = isset($input["doc_no"]) ? trim($input["doc_no"]) : '';
        if ($docNo == '') {
            echo "{\"status\":\"failed\"}";
        } else {
            $sql = "INSERT INTO moa_stp_cc_edit_history
                    (plant_id, doc_no, draft_particular, linked_ctrl_no, linked_ctrl_status, rejected_from_department, rejected_by, entryBy)
                    VALUES
                    ('" . $_GET["plant_id"] . "',
                     '" . $conn->real_escape_string($docNo) . "',
                     '" . $conn->real_escape_string(isset($input["draft_particular"]) ? $input["draft_particular"] : '') . "',
                     '" . $conn->real_escape_string(isset($input["ctrl_no"]) ? $input["ctrl_no"] : '') . "',
                     '" . $conn->real_escape_string(isset($input["ctrl_status"]) ? $input["ctrl_status"] : '') . "',
                     '-',
                     '-',
                     '" . $_GET["emp_id"] . "')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"" . $conn->error . "\"}";
            }
        }
    } else if ($_GET["type"] == "getMoaStpCcEditHistory") {
        $output = Array();
        $sqlCreate = "CREATE TABLE IF NOT EXISTS moa_stp_cc_edit_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) NOT NULL,
            doc_no VARCHAR(100) NOT NULL,
            draft_particular VARCHAR(255) DEFAULT NULL,
            linked_ctrl_no VARCHAR(50) DEFAULT NULL,
            linked_ctrl_status VARCHAR(80) DEFAULT NULL,
            rejected_from_department VARCHAR(120) DEFAULT NULL,
            rejected_by VARCHAR(80) DEFAULT NULL,
            entryBy VARCHAR(50) DEFAULT NULL,
            entryOn DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sqlCreate);

        $docNo = isset($_GET["doc_no"]) ? trim($_GET["doc_no"]) : '';
        if ($docNo == '') {
            echo json_encode($output);
        } else {
            $escDoc = $conn->real_escape_string($docNo);
            $sqlCC = "SELECT id, ctrl_no, status, department_name, check_by, qaReviewedBy, concernHodBy
                      FROM changecontrol
                      WHERE plant_id = '" . $_GET["plant_id"] . "'
                      AND (titleOfcc LIKE 'MOA/STP Revision - " . $escDoc . "%'
                           OR proposed LIKE '%MOA/STP: " . $escDoc . "%')
                      ORDER BY id DESC LIMIT 1";
            $resCC = $conn->query($sqlCC);
            if ($resCC && $resCC->num_rows > 0) {
                $cc = $resCC->fetch_assoc();
                $ccStatus = strtolower(trim($cc["status"]));
                $isRejected = (strpos($ccStatus, 'reject') !== false || strpos($ccStatus, 'back') !== false);
                $isApproved = in_array($ccStatus, Array("approve", "approved", "complete", "closed", "close"));
                if ($isApproved) {
                    echo json_encode(Array());
                    return;
                }
                if ($isRejected) {
                    $ctrlNo = isset($cc["ctrl_no"]) ? $cc["ctrl_no"] : '';
                    $sqlExists = "SELECT id FROM moa_stp_cc_edit_history
                                  WHERE plant_id = '" . $_GET["plant_id"] . "'
                                  AND doc_no = '" . $escDoc . "'
                                  AND linked_ctrl_no = '" . $conn->real_escape_string($ctrlNo) . "'
                                  AND (LOWER(linked_ctrl_status) LIKE '%reject%' OR LOWER(linked_ctrl_status) LIKE '%back%')
                                  ORDER BY id DESC LIMIT 1";
                    $resEx = $conn->query($sqlExists);
                    if (!($resEx && $resEx->num_rows > 0)) {
                        $rejectedBy = $cc["check_by"];
                        if ($rejectedBy == '' || $rejectedBy == null) {
                            $rejectedBy = $cc["qaReviewedBy"];
                        }
                        if ($rejectedBy == '' || $rejectedBy == null) {
                            $rejectedBy = $cc["concernHodBy"];
                        }
                        $rejDept = isset($cc["department_name"]) ? $cc["department_name"] : 'Department';
                        $sqlIns = "INSERT INTO moa_stp_cc_edit_history
                                   (plant_id, doc_no, draft_particular, linked_ctrl_no, linked_ctrl_status, rejected_from_department, rejected_by, entryBy)
                                   VALUES
                                   ('" . $_GET["plant_id"] . "',
                                    '" . $escDoc . "',
                                    'Change Control sent back for draft rework',
                                    '" . $conn->real_escape_string($ctrlNo) . "',
                                    '" . $conn->real_escape_string($cc["status"]) . "',
                                    '" . $conn->real_escape_string($rejDept) . "',
                                    '" . $conn->real_escape_string($rejectedBy) . "',
                                    '" . $_GET["emp_id"] . "')";
                        $conn->query($sqlIns);
                    }
                }
            }

            $sql = "SELECT * FROM moa_stp_cc_edit_history
                    WHERE plant_id = '" . $_GET["plant_id"] . "'
                    AND doc_no = '" . $escDoc . "'
                    ORDER BY id DESC";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>

