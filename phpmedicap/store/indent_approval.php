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
     * $where must reference table alias `i` (indend_raw). $statusFilter (optional) limits
     * the nested line items to the same status as the header row.
     */
    function fetchGroupedRequisitions($conn, $where, $statusFilter = '') {
        $output = array();

        $sql = "SELECT max(id) as id, max(indend_no) as indend_no, max(material_type) as material_type, max(no) as no,
                max(entry_date) as entry_date, max(entry_by) as entry_by, max(status) as status,
                max(purpose) as plant_head_remark, request_no
                FROM indend_raw i
                WHERE $where
                GROUP BY request_no ORDER BY id DESC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $statusClause = $statusFilter !== '' ? " AND i.status = '".$statusFilter."' " : "";

                $materials = array();
                $noEsc = mysqli_real_escape_string($conn, (string)$row["no"]);
                $sql8 = "SELECT DISTINCT v.vendor_name, i.*,
                         COALESCE(
                             NULLIF(TRIM(i.material_name), ''),
                             NULLIF(TRIM(m.material_name), ''),
                             NULLIF(TRIM(mat.material_name), ''),
                             NULLIF(TRIM(om.material_name), ''),
                             NULLIF(TRIM(ch.chemical_name), ''),
                             NULLIF(TRIM(gm.material_name), ''),
                             i.material_code
                         ) AS material_name,
                         m.grade AS view_grade, mat.grade AS mat_grade, om.grade AS om_grade, ch.grade AS ch_grade
                         FROM indend_raw i
                         LEFT JOIN my_view m ON i.material_code = m.material_code
                         LEFT JOIN material mat ON i.material_code = mat.material_code
                         LEFT JOIN others_material om ON i.material_code = om.material_code
                         LEFT JOIN chemical ch ON i.material_code = ch.chemical_no
                         LEFT JOIN general_material gm ON i.material_code = gm.material_code
                         LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
                         WHERE i.no='".$noEsc."' $statusClause LIMIT 50";

                $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $gradeRaw = isset($row8['view_grade']) ? trim((string)$row8['view_grade']) : '';
                        if ($gradeRaw === '' && isset($row8['mat_grade'])) {
                            $gradeRaw = trim((string)$row8['mat_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['om_grade'])) {
                            $gradeRaw = trim((string)$row8['om_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['ch_grade'])) {
                            $gradeRaw = trim((string)$row8['ch_grade']);
                        }
                        if ($gradeRaw === '' && isset($row8['grade'])) {
                            $gradeRaw = trim((string)$row8['grade']);
                        }
                        $row8['grade'] = '';
                        if ($gradeRaw !== '') {
                            $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                            if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                                $resQ = $conn->query("SELECT GROUP_CONCAT(grade) AS grade FROM grade WHERE id IN (".$idPart.")");
                                if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['grade'])) {
                                    $row8['grade'] = $g['grade'];
                                }
                            }
                            if ($row8['grade'] === '') {
                                $row8['grade'] = $gradeRaw;
                            }
                        }
                        unset($row8['view_grade'], $row8['mat_grade'], $row8['om_grade'], $row8['ch_grade']);
                        $materials[] = $row8;
                    }
                }
                $row["materials"] = $materials;
                $output[] = $row;
            }
        }
        return $output;
    }

    /**
     * Normalize department names used across raise/log/approval screens.
     * Returns SQL fragment for i.department IN (...).
     */
    function deptHeadDepartmentInClause($dept) {
        $dept = trim((string)$dept);
        // Empty / master / plant-head style: no department restriction (all plant pending).
        if ($dept === '' || strcasecmp($dept, 'master') === 0 || strcasecmp($dept, 'Plant Head') === 0 || strcasecmp($dept, 'Management') === 0) {
            return '1=1';
        }
        if ($dept === 'R AND D' || $dept === 'R&D' || $dept === 'Product Development') {
            return "i.department IN ('R AND D','R&D','Product Development')";
        }
        if ($dept === 'Human Resource' || $dept === 'HR') {
            return "i.department IN ('Human Resource','HR')";
        }
        if ($dept === 'Quality Control' || $dept === 'QC') {
            return "i.department IN ('Quality Control','QC')";
        }
        if ($dept === 'Quality Assurance' || $dept === 'QA' || $dept === 'Quality Assurance & Compliance') {
            return "i.department IN ('Quality Assurance','QA','Quality Assurance & Compliance')";
        }
        if ($dept === 'Production' || $dept === 'F-Production' || $dept === 'F Production') {
            return "i.department IN ('Production','F-Production','F Production')";
        }
        return "i.department='".str_replace("'", "''", $dept)."'";
    }

    // True Dept Head pending only (exclude To_Purchase_Head — those already skipped HOD).
    $deptHeadPendingStatuses = "'TO_HOD','To_HOD_RMPM','Revert_To_Dept_Head','Revert_To_Engg','Revert_To_QA','To_Store_Head'";
    $rmpmMaterialTypes = "'Raw Material','Packing Material','RM/PM Material'";

    /**
     * Resolve department filter for Dept Head list APIs.
     * all_depts=1 / department_name=all|master => plant-wide.
     */
    function deptHeadListDeptClause() {
        $allDepts = isset($_GET['all_depts']) && ($_GET['all_depts'] === '1' || strtolower($_GET['all_depts']) === 'true');
        $dept = isset($_GET["department_name"]) ? trim($_GET["department_name"]) : '';
        if ($dept === '' && !$allDepts && !empty($_GET["department"])) {
            $dept = $_GET["department"];
        }
        if ($allDepts || strcasecmp($dept, 'all') === 0 || strcasecmp($dept, 'master') === 0 || $dept === '') {
            return '1=1';
        }
        return deptHeadDepartmentInClause($dept);
    }

    // ---- All requisitions pending Dept Head approval ----
    // All = RM/PM pending + General pending (same plant scope).
    if ($_GET["type"] == "getAllForDeptHeadApproval") {
        $deptClause = deptHeadListDeptClause();
        $where = "i.plant_id='".$_GET["plant_id"]."' AND ".$deptClause."
                  AND i.status IN (".$deptHeadPendingStatuses.")";
        echo json_encode(fetchGroupedRequisitions($conn, $where));
    }

    // ---- RM/PM requisitions pending HOD (To_HOD_RMPM / store-head RM) ----
    else if ($_GET["type"] == "getRmpmForApproval") {
        $deptClause = deptHeadListDeptClause();
        $where = "i.plant_id='".$_GET["plant_id"]."' AND ".$deptClause."
                  AND i.status IN ('To_HOD_RMPM','To_Store_Head')
                  AND i.material_type IN (".$rmpmMaterialTypes.")";
        echo json_encode(fetchGroupedRequisitions($conn, $where));
    }

    // ---- General material pending HOD (TO_HOD + reverts, non RM/PM) ----
    else if ($_GET["type"] == "getGeneralForApproval") {
        $deptClause = deptHeadListDeptClause();
        $where = "i.plant_id='".$_GET["plant_id"]."' AND ".$deptClause."
                  AND i.status IN ('TO_HOD','Revert_To_Dept_Head','Revert_To_Engg','Revert_To_QA')
                  AND (i.material_type IS NULL OR i.material_type = '' OR i.material_type NOT IN (".$rmpmMaterialTypes."))";
        echo json_encode(fetchGroupedRequisitions($conn, $where));
    }

    // ---- Approve / reject General (HOD) requisition ----
    else if ($_GET["type"] == "approveGeneral") {
        $materials = (isset($input["materials"]) && is_array($input["materials"])) ? $input["materials"] : array();
        if (count($materials) === 0) {
            echo "{\"status\":\"error\",\"message\":\"No materials to approve\"}";
            exit;
        }
        $hodRemark = isset($input["hodRemark"]) ? mysqli_real_escape_string($conn, (string)$input["hodRemark"]) : '';
        $flag = 0;
        $array = json_decode(json_encode($materials), true);
        foreach ($array as $values) {
            if ($_GET['status'] == 'Rejected') {
                $status = 'Rejected';
            } else if ($_GET['status'] == 'TO_QA_MANAGER') {
                $status = 'TO_QA_MANAGER';
            } else {
                $status = 'pending';
            }
            $sql = "UPDATE indend_raw SET status='".$status."', dept_head_remark='".$hodRemark."',
                    req_qty='".$values["req_qty"]."', approve_hod_by='".$_GET["emp_id"]."', approve_hod_on='$entry_date'
                    WHERE id='".$values["id"]."'";
            if (!$conn->query($sql)) {
                $flag++;
            }
        }
        echo $flag == 0 ? "{\"status\":\"success\"}" : "{\"status\":\"".$conn->error."\"}";
    }

    // ---- Revert-approve General requisition (with dept head remark) ----
    else if ($_GET["type"] == "revertGeneral") {
        $flag = 0;
        $array = json_decode(json_encode($input["materials"]), true);
        foreach ($array as $values) {
            $sql = "UPDATE indend_raw SET status='".$_GET['status']."', dept_head_remark='".$input['dept_head_remark']."',
                    approve_hod_by='".$_GET["emp_id"]."', approve_hod_on='$entry_date'
                    WHERE id='".$values["id"]."'";
            if (!$conn->query($sql)) {
                $flag++;
            }
        }
        echo $flag == 0 ? "{\"status\":\"success\"}" : "{\"status\":\"".$conn->error."\"}";
    }

    // ---- Approve / reject RM/PM requisition ----
    else if ($_GET["type"] == "approveRmpm") {
        $materials = (isset($input["materials"]) && is_array($input["materials"])) ? $input["materials"] : array();
        if (count($materials) === 0) {
            echo "{\"status\":\"error\",\"message\":\"No materials to approve\"}";
            exit;
        }
        $flag = 0;
        $array = json_decode(json_encode($materials), true);
        foreach ($array as $values) {
            $sql = "UPDATE indend_raw SET status='".$_GET['status']."', approve_hod_by='".$_GET["emp_id"]."',
                    approve_hod_on='$entry_date', req_qty='".$values["req_qty"]."'
                    WHERE id='".$values["id"]."'";
            if (!$conn->query($sql)) {
                $flag++;
            }
        }
        echo $flag == 0 ? "{\"status\":\"success\"}" : "{\"status\":\"".$conn->error."\"}";
    }

    else {
        echo "{\"status\":\"invalid\"}";
    }
?>
