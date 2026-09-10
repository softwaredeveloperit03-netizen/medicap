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
    if (!is_array($input)) {
        $input = array();
    }

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

    // ---- Checked requisitions awaiting PO conversion (common for RM/PM and General) ----
    if ($_GET["type"] == "getCheckedRequisitions") {

        $output = array();
        $rmpm_status_filter = "(i.status ='pending' OR (i.status = 'approve' AND (i.indend_no IS NULL OR i.indend_no = '')))";

        if ($_GET['For'] == 'RMPM') {
            $typeFilter = "AND (i.material_type = 'Raw Material' OR i.material_type = 'Packing Material')";
        } else {
            $typeFilter = "AND (i.material_type != 'Raw Material' AND i.material_type != 'Packing Material')";
        }

        $sql = "SELECT max(id) as id, max(indend_no) as indend_no, max(material_type) as material_type, max(no) as no,
                max(entry_date) as entry_date, max(entry_by) as entry_by, max(status) as status, request_no
                FROM indend_raw i
                WHERE ".$rmpm_status_filter." AND i.plant_id='".$_GET["plant_id"]."' ".$typeFilter."
                GROUP BY request_no ORDER BY id DESC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $materials = array();
                $sql8 = "SELECT v.vendor_name, i.*, m.material_name, m.grade
                         FROM indend_raw i
                         LEFT JOIN my_view m ON i.material_code = m.material_code
                         LEFT JOIN vendor v ON i.specific_vendor = v.vendor_no
                         WHERE i.no = '".$row["no"]."' AND ".$rmpm_status_filter;

                $result8 = $conn->query($sql8);
                if ($result8 && $result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {

                        $vendors = array();
                        $sql1 = "SELECT a.id as quotId, a.material_code, a.quotation_amt, a.quotation_per, a.currency, a.gst_per,
                                 a.pack_size, a.pack_unit, b.quotation_no, b.vendor_quotation_no, b.vendor_id,
                                 c.vendor_no, c.vendor_name
                                 FROM quotation_dtl a
                                 LEFT JOIN quotation_hdr b ON a.quotation_hdr_id = b.id
                                 LEFT JOIN vendor c ON b.vendor_id = c.id
                                 WHERE b.status = 'approve' AND a.material_code = '".$row8["material_code"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1 && $result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $vendors[] = $row1;
                            }
                        }

                        $row8["required_for"] = json_decode($row8["required_for"]);
                        $row8["vendors"] = $vendors;
                        $materials[] = $row8;
                    }
                }
                $row["materials"] = $materials;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    // ---- Approve / reject (convert to PO) — common for RM/PM and General ----
    else if ($_GET["type"] == "approveRequisition") {

        $flag = 0;
        for ($k = 0; $k < count($input); $k++) {
            $materials = $input[$k]['materials'];

            $sql = "SELECT max(indend_no) as indend_no FROM indend_raw ORDER BY id DESC";
            $result = $conn->query($sql);
            $last_id = null;
            while ($row = $result->fetch_assoc()) {
                $last_id = $row['indend_no'];
            }
            if ($last_id == null) {
                $last_id = 1;
            } else {
                $int_var = (int) filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
                $last_id = $int_var + 1;
            }
            $number = substr(str_repeat(0, 4).$last_id, -4);
            $ind_no = "IN".$number;

            for ($i = 0; $i < count($materials); $i++) {
                $indend = $materials[$i];
                $approveStatus = $_GET["status"];
                $poIndendSql = '';
                if ($approveStatus === 'approve') {
                    $poIndendSql = ", po_indend='pending'";
                } else if ($approveStatus === 'Rejected') {
                    $poIndendSql = ", po_indend=''";
                }
                $sql = "UPDATE indend_raw SET indend_no = '".$ind_no."', vendor_no = '".$indend["vendor_no"]."',
                        quotation_no = '".$indend["quotation_no"]."', currency = '".$indend["currency"]."',
                        quotation_amt = '".$indend["quotation_amt"]."', quotation_per = '".$indend["quotation_per"]."',
                        order_qty = '".$indend["order_qty"]."', gst = '".$indend["gst_per"]."',
                        gross_total = '".$indend["gross_total"]."', gst_total = '".$indend["gst_total"]."',
                        net_total = '".$indend["net_total"]."', status = '".$approveStatus."',
                        approve_by = '".$_GET["emp_id"]."', approve_date = '$entry_date'".$poIndendSql."
                        WHERE id = '".$indend["id"]."'";
                if (!$conn->query($sql)) {
                    $flag++;
                }
            }
        }
        echo $flag == 0 ? "{\"status\":\"success\"}" : "{\"status\":\"".$conn->error."\"}";
    }

    else {
        echo "{\"status\":\"invalid\"}";
    }
?>
