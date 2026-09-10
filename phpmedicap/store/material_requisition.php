<?php

//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);

    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'), true);

    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
            $string = explode("$", $string);
            $_GET["emp_id"] = $string[0];
            $_GET["department"] = $string[1];
            break;
        }
    }

    /**
     * Returns requisitions grouped by request_no, each with a nested `materials` array.
     * $where must reference table alias `i` (indend_raw).
     */
    function fetchGroupedRequisitions($conn, $where) {
        $output = array();

        $sql = "SELECT max(id) as id, max(indend_no) as indend_no, max(material_type) as material_type, max(no) as no,
                max(entry_date) as entry_date, max(entry_by) as entry_by, max(status) as status, request_no
                FROM indend_raw i
                WHERE $where
                GROUP BY request_no ORDER BY id DESC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $materials = array();
                $sql8 = "SELECT DISTINCT v.vendor_name, i.*,
                         COALESCE(NULLIF(TRIM(m.material_name), ''), NULLIF(TRIM(mv.material_name), ''), i.material_name) as material_name,
                         COALESCE(NULLIF(TRIM(m.grade), ''), NULLIF(TRIM(mv.grade), '')) as grade
                         FROM indend_raw i
                         LEFT JOIN material m ON i.material_code = m.material_code
                         LEFT JOIN my_view mv ON i.material_code = mv.material_code
                         LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
                         WHERE i.no='".$row["no"]."' AND (i.material_type!='' OR i.material_type!=NULL) LIMIT 50";

                $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $gradeRaw = isset($row8['grade']) ? trim((string)$row8['grade']) : '';
                        if ($gradeRaw !== '') {
                            $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                            if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                                $q = "SELECT GROUP_CONCAT(grade SEPARATOR ', ') as gradeName FROM grade WHERE id IN (".$idPart.")";
                                $resQ = $conn->query($q);
                                if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['gradeName'])) {
                                    $row8['grade'] = $g['gradeName'];
                                }
                            }
                        }
                        $materials[] = $row8;
                    }
                }
                $row["materials"] = $materials;
                $output[] = $row;
            }
        }
        return $output;
    }

    // ---- RM/PM (Raw / Packing) Material Requisition log ----
    if ($_GET["type"] == "getRmpmRequisitions") {
        $where = "i.plant_id='".$_GET["plant_id"]."' AND i.material_type IN ('Raw Material','Packing Material')";
        echo json_encode(fetchGroupedRequisitions($conn, $where));
    }

    // ---- General (GM) Material Requisition log ----
    else if ($_GET["type"] == "getGeneralRequisitions") {
        $dept = isset($_GET["department_name"]) ? $_GET["department_name"] : $_GET["department"];
        if ($dept == 'R AND D') {
            $dept = 'Product Development';
        }
        $where = "i.plant_id='".$_GET["plant_id"]."' AND i.department='".$dept."' AND i.material_type NOT IN ('Raw Material','Packing Material')";
        echo json_encode(fetchGroupedRequisitions($conn, $where));
    }

    // ---- Mark a requisition (all its line items) as checked ----
    else if ($_GET["type"] == "checkRequisition") {
        $request_no = $_GET["request_no"];
        $sql = "UPDATE indend_raw SET status='checked' WHERE request_no='".$request_no."' AND plant_id='".$_GET["plant_id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"invalid\"}";
        }
    }

    else {
        echo "{\"status\":\"invalid\"}";
    }
?>
