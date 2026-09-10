<?php
require '../db.php';
require '../token.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);

function ensureFgChecklistTables($conn)
{
    $sql1 = "CREATE TABLE IF NOT EXISTS fg_sampling_checklist_master (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) NOT NULL,
        checklist_no VARCHAR(80) NOT NULL,
        sampling_type VARCHAR(80) DEFAULT 'Raw Material Sampling',
        checklist_title VARCHAR(255) NOT NULL,
        revision_no INT NOT NULL DEFAULT 1,
        supersede_no VARCHAR(50) DEFAULT NULL,
        effective_date DATE DEFAULT NULL,
        next_review_date DATE DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        entry_by VARCHAR(50) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        check_by VARCHAR(50) DEFAULT NULL,
        check_date DATETIME DEFAULT NULL,
        approve_by VARCHAR(50) DEFAULT NULL,
        approve_date DATETIME DEFAULT NULL,
        update_by VARCHAR(50) DEFAULT NULL,
        update_date DATETIME DEFAULT NULL
    )";
    $conn->query($sql1);
    $conn->query("ALTER TABLE fg_sampling_checklist_master ADD COLUMN IF NOT EXISTS sampling_type VARCHAR(80) DEFAULT 'Raw Material Sampling' AFTER checklist_no");

    $sql2 = "CREATE TABLE IF NOT EXISTS fg_sampling_checklist_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        checklist_id INT NOT NULL,
        checkpoint VARCHAR(255) NOT NULL,
        checkpoint_description TEXT,
        checkpoint_particular VARCHAR(80) DEFAULT 'Remark',
        remark_type VARCHAR(50) DEFAULT 'Like Remark',
        yes_no_option VARCHAR(30) DEFAULT 'Yes/No',
        checked_option VARCHAR(40) DEFAULT 'Checked/Not Checked',
        availability_option VARCHAR(50) DEFAULT 'Available/Not Available',
        priority_no INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        entry_by VARCHAR(50) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql2);
    $conn->query("ALTER TABLE fg_sampling_checklist_points ADD COLUMN IF NOT EXISTS checkpoint_particular VARCHAR(80) DEFAULT 'Remark' AFTER checkpoint_description");
}

function getChecklistByNo($conn, $plantId, $checklistNo)
{
    $output = array();
    $escNo = $conn->real_escape_string($checklistNo);
    $sql = "SELECT * FROM fg_sampling_checklist_master
            WHERE plant_id = '".$plantId."' AND checklist_no = '".$escNo."'
            ORDER BY revision_no DESC, id DESC
            LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $row["checkpoints"] = array();
        $sqlP = "SELECT * FROM fg_sampling_checklist_points
                 WHERE checklist_id = '".$row["id"]."' AND status = 'active'
                 ORDER BY priority_no ASC, id ASC";
        $resP = $conn->query($sqlP);
        if ($resP && $resP->num_rows > 0) {
            while ($p = $resP->fetch_assoc()) {
                $row["checkpoints"][] = $p;
            }
        }
        $output = $row;
    }
    return $output;
}

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

    ensureFgChecklistTables($conn);

    if ($_GET["type"] == "saveChecklistMaster") {
        $samplingType = isset($input["sampling_type"]) ? trim($input["sampling_type"]) : 'Raw Material Sampling';
        $title = isset($input["checklist_title"]) ? trim($input["checklist_title"]) : '';
        $effective = isset($input["effective_date"]) ? trim($input["effective_date"]) : '';
        $nextReview = isset($input["next_review_date"]) ? trim($input["next_review_date"]) : '';
        $checkpoints = isset($input["checkpoints"]) && is_array($input["checkpoints"]) ? $input["checkpoints"] : array();

        if ($title == '' || $effective == '' || $nextReview == '' || count($checkpoints) == 0) {
            echo "{\"status\":\"failed\",\"message\":\"Missing required fields\"}";
        } else {
            $sqlIns = "INSERT INTO fg_sampling_checklist_master
                       (plant_id, checklist_no, sampling_type, checklist_title, revision_no, supersede_no, effective_date, next_review_date, status, entry_by, entry_date)
                       VALUES
                       ('".$_GET["plant_id"]."', '', '".$conn->real_escape_string($samplingType)."', '".$conn->real_escape_string($title)."', 1, NULL, '".$conn->real_escape_string($effective)."', '".$conn->real_escape_string($nextReview)."', 'Pending', '".$_GET["emp_id"]."', '".$entry_date."')";
            if ($conn->query($sqlIns)) {
                $id = $conn->insert_id;
                $checklistNo = 'FGSCL-' . $_GET["plant_id"] . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                $conn->query("UPDATE fg_sampling_checklist_master SET checklist_no = '".$conn->real_escape_string($checklistNo)."' WHERE id = '".$id."'");

                for ($i = 0; $i < count($checkpoints); $i++) {
                    $cp = $checkpoints[$i];
                    $point = isset($cp["checkpoint"]) ? trim($cp["checkpoint"]) : '';
                    if ($point == '') {
                        continue;
                    }
                    $desc = isset($cp["checkpoint_description"]) ? $cp["checkpoint_description"] : '';
                    $particular = isset($cp["checkpoint_particular"]) ? $cp["checkpoint_particular"] : 'Remark';
                    $priority = $i + 1;

                    $sqlP = "INSERT INTO fg_sampling_checklist_points
                             (checklist_id, checkpoint, checkpoint_description, checkpoint_particular, priority_no, status, entry_by, entry_date)
                             VALUES
                             ('".$id."',
                              '".$conn->real_escape_string($point)."',
                              '".$conn->real_escape_string($desc)."',
                              '".$conn->real_escape_string($particular)."',
                              '".$priority."',
                              'active',
                              '".$_GET["emp_id"]."',
                              '".$entry_date."')";
                    $conn->query($sqlP);
                }

                echo "{\"status\":\"success\",\"checklist_no\":\"".$checklistNo."\"}";
            } else {
                echo "{\"status\":\"failed\",\"message\":\"".$conn->error."\"}";
            }
        }
    } else if ($_GET["type"] == "getChecklistLog") {
        $output = array();
        $sqlL = "SELECT * FROM fg_sampling_checklist_master
                 WHERE plant_id = '".$_GET["plant_id"]."'
                 ORDER BY checklist_no DESC, revision_no DESC, id DESC";
        $resL = $conn->query($sqlL);
        if ($resL && $resL->num_rows > 0) {
            while ($row = $resL->fetch_assoc()) {
                $row["checkpoints"] = array();
                $sqlP = "SELECT * FROM fg_sampling_checklist_points
                         WHERE checklist_id = '".$row["id"]."' AND status = 'active'
                         ORDER BY priority_no ASC, id ASC";
                $resP = $conn->query($sqlP);
                if ($resP && $resP->num_rows > 0) {
                    while ($p = $resP->fetch_assoc()) {
                        $row["checkpoints"][] = $p;
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getChecklistByNo") {
        $checklistNo = isset($_GET["checklist_no"]) ? trim($_GET["checklist_no"]) : '';
        if ($checklistNo == '') {
            echo json_encode(array());
        } else {
            echo json_encode(getChecklistByNo($conn, $_GET["plant_id"], $checklistNo));
        }
    } else if ($_GET["type"] == "updateCheckpointPriorities") {
        $checklistNo = isset($input["checklist_no"]) ? trim($input["checklist_no"]) : '';
        $priorities = isset($input["priorities"]) && is_array($input["priorities"]) ? $input["priorities"] : array();
        if ($checklistNo == '' || count($priorities) == 0) {
            echo "{\"status\":\"failed\"}";
        } else {
            for ($i = 0; $i < count($priorities); $i++) {
                $p = $priorities[$i];
                $id = intval(isset($p["id"]) ? $p["id"] : 0);
                $priorityNo = intval(isset($p["priority_no"]) ? $p["priority_no"] : 0);
                if ($id > 0 && $priorityNo > 0) {
                    $conn->query("UPDATE fg_sampling_checklist_points SET priority_no = '".$priorityNo."' WHERE id = '".$id."'");
                }
            }
            echo "{\"status\":\"success\"}";
        }
    } else if ($_GET["type"] == "updateChecklistStatus") {
        $id = intval(isset($input["id"]) ? $input["id"] : 0);
        $status = isset($input["status"]) ? trim($input["status"]) : '';
        if ($id <= 0 || $status == '') {
            echo "{\"status\":\"failed\"}";
        } else {
            $escStatus = $conn->real_escape_string($status);
            $sqlU = "UPDATE fg_sampling_checklist_master
                     SET status = '".$escStatus."', update_by = '".$_GET["emp_id"]."', update_date = '".$entry_date."'
                     WHERE id = '".$id."'";
            if (strtolower($status) == 'checked') {
                $sqlU = "UPDATE fg_sampling_checklist_master
                         SET status = 'Checked', check_by = '".$_GET["emp_id"]."', check_date = '".$entry_date."', update_by = '".$_GET["emp_id"]."', update_date = '".$entry_date."'
                         WHERE id = '".$id."'";
            } else if (strtolower($status) == 'approved') {
                $sqlU = "UPDATE fg_sampling_checklist_master
                         SET status = 'Approved', approve_by = '".$_GET["emp_id"]."', approve_date = '".$entry_date."', update_by = '".$_GET["emp_id"]."', update_date = '".$entry_date."'
                         WHERE id = '".$id."'";
            }
            if ($conn->query($sqlU)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\",\"message\":\"".$conn->error."\"}";
            }
        }
    } else if ($_GET["type"] == "getChecklistRevisionRequestStatus") {
        $output = array();
        $raw = isset($_GET["checklist_nos"]) ? $_GET["checklist_nos"] : '';
        $parts = explode(",", $raw);
        $safe = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $safe[] = "'".$conn->real_escape_string($p)."'";
            }
        }
        if (count($safe) === 0) {
            echo json_encode($output);
        } else {
            $sqlR = "SELECT id, refDocNo, status, revisionComment, entryBy, entryOn
                     FROM revisionRequest
                     WHERE reqFor = 'FG Sampling Checklist Revision Request'
                       AND refDocNo IN (".implode(",", $safe).")
                     ORDER BY id DESC";
            $resR = $conn->query($sqlR);
            if ($resR && $resR->num_rows > 0) {
                while ($r = $resR->fetch_assoc()) {
                    $no = $r["refDocNo"];
                    if (!isset($output[$no])) {
                        $output[$no] = $r;
                    }
                }
            }
            echo json_encode($output);
        }
    } else if ($_GET["type"] == "saveChecklistRevisionRequest") {
        $checklistNo = isset($input["checklist_no"]) ? trim($input["checklist_no"]) : '';
        $checklistId = isset($input["checklist_id"]) ? trim($input["checklist_id"]) : '';
        $checklistName = isset($input["checklist_name"]) ? trim($input["checklist_name"]) : '';
        $reason = isset($input["reason"]) ? trim($input["reason"]) : '';
        $remarks = isset($input["remarks"]) ? trim($input["remarks"]) : '';

        if ($checklistNo == '' || $reason == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing required fields\"}";
        } else {
            $comment = $reason;
            if ($remarks != '') {
                $comment .= " | Remarks: " . $remarks;
            }
            $sqlC = "SELECT id FROM revisionRequest
                     WHERE plant_id = '".$_GET["plant_id"]."'
                       AND reqFor = 'FG Sampling Checklist Revision Request'
                       AND refDocNo = '".$conn->real_escape_string($checklistNo)."'
                       AND status = 'Pending'
                     ORDER BY id DESC LIMIT 1";
            $resC = $conn->query($sqlC);
            if ($resC && $resC->num_rows > 0) {
                echo "{\"status\":\"exists\"}";
            } else {
                $sqlI = "INSERT INTO revisionRequest
                        (plant_id, refDocNo, refDocId, refDocName, revisionComment, reqFor, entryBy, entryOn, status)
                        VALUES
                        ('".$_GET["plant_id"]."',
                         '".$conn->real_escape_string($checklistNo)."',
                         '".$conn->real_escape_string($checklistId)."',
                         '".$conn->real_escape_string($checklistName)."',
                         '".$conn->real_escape_string($comment)."',
                         'FG Sampling Checklist Revision Request',
                         '".$_GET["emp_id"]."',
                         '".$entry_date."',
                         'Pending')";
                if ($conn->query($sqlI)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\",\"message\":\"".$conn->error."\"}";
                }
            }
        }
    } else if ($_GET["type"] == "getChangeControlStatusForChecklists") {
        $output = array();
        $raw = isset($_GET["checklist_nos"]) ? $_GET["checklist_nos"] : '';
        $parts = explode(",", $raw);
        for ($i = 0; $i < count($parts); $i++) {
            $no = trim($parts[$i]);
            if ($no == '') {
                continue;
            }
            $esc = $conn->real_escape_string($no);
            $sqlCC = "SELECT id, ctrl_no, status, titleOfcc, entryDate
                      FROM changecontrol
                      WHERE plant_id = '".$_GET["plant_id"]."'
                        AND (
                            titleOfcc LIKE 'FG Sampling Checklist Revision - ".$esc."%'
                            OR proposed LIKE '%Checklist: ".$esc."%'
                        )
                      ORDER BY id DESC LIMIT 1";
            $resCC = $conn->query($sqlCC);
            if ($resCC && $resCC->num_rows > 0) {
                $output[$no] = $resCC->fetch_assoc();
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "createRevisedChecklistFromApprovedCc") {
        $checklistNo = isset($input["checklist_no"]) ? trim($input["checklist_no"]) : '';
        if ($checklistNo == '') {
            echo "{\"status\":\"failed\",\"message\":\"Missing checklist number\"}";
        } else {
            $esc = $conn->real_escape_string($checklistNo);
            $sqlBase = "SELECT * FROM fg_sampling_checklist_master
                        WHERE plant_id = '".$_GET["plant_id"]."'
                          AND checklist_no = '".$esc."'
                        ORDER BY revision_no DESC, id DESC
                        LIMIT 1";
            $resBase = $conn->query($sqlBase);
            if (!($resBase && $resBase->num_rows > 0)) {
                echo "{\"status\":\"failed\",\"message\":\"Checklist not found\"}";
            } else {
                $base = $resBase->fetch_assoc();
                $nextRev = intval($base["revision_no"]) + 1;
                $sqlExists = "SELECT id FROM fg_sampling_checklist_master
                              WHERE plant_id = '".$_GET["plant_id"]."'
                                AND checklist_no = '".$esc."'
                                AND revision_no = '".$nextRev."'
                              LIMIT 1";
                $resExists = $conn->query($sqlExists);
                if ($resExists && $resExists->num_rows > 0) {
                    echo "{\"status\":\"exists\"}";
                } else {
                    $conn->query("UPDATE fg_sampling_checklist_master
                                  SET status = 'Superseded', update_by = '".$_GET["emp_id"]."', update_date = '".$entry_date."'
                                  WHERE id = '".$base["id"]."'");

                    $sqlNew = "INSERT INTO fg_sampling_checklist_master
                               (plant_id, checklist_no, sampling_type, checklist_title, revision_no, supersede_no, effective_date, next_review_date, status, entry_by, entry_date)
                               VALUES
                               ('".$_GET["plant_id"]."',
                                '".$esc."',
                                '".$conn->real_escape_string($base["sampling_type"])."',
                                '".$conn->real_escape_string($base["checklist_title"])."',
                                '".$nextRev."',
                                '".$conn->real_escape_string($base["revision_no"])."',
                                '".$conn->real_escape_string($base["effective_date"])."',
                                '".$conn->real_escape_string($base["next_review_date"])."',
                                'Pending',
                                '".$_GET["emp_id"]."',
                                '".$entry_date."')";
                    if ($conn->query($sqlNew)) {
                        $newId = $conn->insert_id;
                        $sqlPts = "SELECT * FROM fg_sampling_checklist_points
                                   WHERE checklist_id = '".$base["id"]."' AND status = 'active'
                                   ORDER BY priority_no ASC, id ASC";
                        $resPts = $conn->query($sqlPts);
                        if ($resPts && $resPts->num_rows > 0) {
                            while ($p = $resPts->fetch_assoc()) {
                                $conn->query("INSERT INTO fg_sampling_checklist_points
                                    (checklist_id, checkpoint, checkpoint_description, checkpoint_particular, priority_no, status, entry_by, entry_date)
                                    VALUES
                                    ('".$newId."',
                                     '".$conn->real_escape_string($p["checkpoint"])."',
                                     '".$conn->real_escape_string($p["checkpoint_description"])."',
                                     '".$conn->real_escape_string(isset($p["checkpoint_particular"]) ? $p["checkpoint_particular"] : "Remark")."',
                                     '".intval($p["priority_no"])."',
                                     'active',
                                     '".$_GET["emp_id"]."',
                                     '".$entry_date."')");
                            }
                        }
                        echo "{\"status\":\"success\"}";
                    } else {
                        echo "{\"status\":\"failed\",\"message\":\"".$conn->error."\"}";
                    }
                }
            }
        }
    } else {
        echo "{\"status\":\"invalid\"}";
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>

