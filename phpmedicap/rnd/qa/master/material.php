<?php
    require '../../../db.php';
    require '../../../token.php';
    require '../../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    function qa_mat_val($conn, $input, $key, $default = '') {
        if (!is_array($input) || !isset($input[$key]) || $input[$key] === null) {
            return $default;
        }
        return $conn->real_escape_string($input[$key]);
    }

    function qa_table_has_column($conn, $table, $column) {
        $tableEsc = $conn->real_escape_string($table);
        $colEsc = $conn->real_escape_string($column);
        $res = $conn->query("SHOW COLUMNS FROM `".$tableEsc."` LIKE '".$colEsc."'");
        return ($res && $res->num_rows > 0);
    }

    function qa_safe_json_echo($data) {
        header('Content-Type: application/json; charset=UTF-8');
        if (is_array($data)) {
            array_walk_recursive($data, function (&$item) {
                if (is_string($item) && function_exists('mb_convert_encoding')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });
        }
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($data, $flags);
        echo ($json === false) ? '[]' : $json;
    }

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
    $myfile = file_put_contents('../../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


    if ($_GET["type"] == "saveMaterial") {
        $user_no = isset($_GET["user_no"]) ? $conn->real_escape_string($_GET["user_no"]) : '';
        $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
        $material_type = qa_mat_val($conn, $input, 'material_type');
        $material_subtype = qa_mat_val($conn, $input, 'material_subtype');
        $material_name = qa_mat_val($conn, $input, 'material_name');
        $cas_name = qa_mat_val($conn, $input, 'cas_name');
        $upac_name = qa_mat_val($conn, $input, 'upac_name');
        $grade = qa_mat_val($conn, $input, 'grade');
        $material_nature = qa_mat_val($conn, $input, 'material_nature');
        $m_id1 = qa_mat_val($conn, $input, 'm_id1');
        $location = qa_mat_val($conn, $input, 'location');
        $thera = qa_mat_val($conn, $input, 'thera');
        $storage_condition = qa_mat_val($conn, $input, 'storage_condition');
        $special_grade = qa_mat_val($conn, $input, 'special_grade');
        $order_qty = qa_mat_val($conn, $input, 'order_qty');
        $category = qa_mat_val($conn, $input, 'category');
        $retest = qa_mat_val($conn, $input, 'retest');
        $shelf_life = qa_mat_val($conn, $input, 'shelf_life');
        $min_shelf = qa_mat_val($conn, $input, 'min_shelf');
        $lead_time = qa_mat_val($conn, $input, 'lead_time');
        $unit = qa_mat_val($conn, $input, 'unit');
        $hsn = qa_mat_val($conn, $input, 'hsn');
        $isprinted = qa_mat_val($conn, $input, 'isprinted');
        $for_product = qa_mat_val($conn, $input, 'for_product');
        $product_code = qa_mat_val($conn, $input, 'product_code');
        $inventory = qa_mat_val($conn, $input, 'inventory');
        $equivalent = json_encode(isset($input['equivalent']) ? $input['equivalent'] : array());
        $equivalent = $conn->real_escape_string($equivalent);

        $columns = array(
            'user_no', 'material_type', 'material_subtype', 'material_name', 'cas_name', 'upac_name', 'grade',
            'material_nature', 'entry_by', 'entry_date', 'm_id1', 'location', 'thera', 'storage_condition',
            'special_grade', 'order_qty', 'category', 'retest', 'shelf_life', 'min_shelf', 'lead_time',
            'equivalent', 'unit', 'hsn', 'isprinted', 'for_product', 'product_code', 'inventory'
        );
        $values = array(
            "'".$user_no."'", "'".$material_type."'", "'".$material_subtype."'", "'".$material_name."'",
            "'".$cas_name."'", "'".$upac_name."'", "'".$grade."'", "'".$material_nature."'",
            "'".$conn->real_escape_string($_GET["emp_id"])."'", "'".$entry_date."'", "'".$m_id1."'",
            "'".$location."'", "'".$thera."'", "'".$storage_condition."'", "'".$special_grade."'",
            "'".$order_qty."'", "'".$category."'", "'".$retest."'", "'".$shelf_life."'", "'".$min_shelf."'",
            "'".$lead_time."'", "'".$equivalent."'", "'".$unit."'", "'".$hsn."'", "'".$isprinted."'",
            "'".$for_product."'", "'".$product_code."'", "'".$inventory."'"
        );

        if (qa_table_has_column($conn, 'rnd_material', 'status')) {
            $columns[] = 'status';
            $values[] = "'Pending'";
        }

        if ($plant_id !== '' && qa_table_has_column($conn, 'rnd_material', 'plant_id')) {
            array_unshift($columns, 'plant_id');
            array_unshift($values, "'".$plant_id."'");
        }

        $sql = "INSERT INTO rnd_material (".implode(', ', $columns).") VALUES (".implode(', ', $values).")";

    	if($conn->query($sql)) {
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "getMaterialsLog") {
        $output = Array();
        $plant_id = isset($_GET["plant_id"]) ? trim($_GET["plant_id"]) : '';
        $material_type = isset($_GET["material_type"]) ? trim($_GET["material_type"]) : '';
        $material_subtype = isset($_GET["material_subtype"]) ? trim($_GET["material_subtype"]) : '';
        $status = isset($_GET["status"]) ? trim($_GET["status"]) : '';

        $where = array('1=1');

        if ($plant_id !== '' && qa_table_has_column($conn, 'rnd_material', 'plant_id')) {
            $pid = $conn->real_escape_string($plant_id);
            $where[] = "(IFNULL(m.plant_id,'') = '".$pid."' OR IFNULL(m.plant_id,'') = '')";
        }

        if ($material_type !== '') {
            $where[] = "m.material_type LIKE '%".$conn->real_escape_string($material_type)."%'";
        }
        if ($material_subtype !== '') {
            $where[] = "m.material_subtype LIKE '%".$conn->real_escape_string($material_subtype)."%'";
        }
        if ($status !== '') {
            $where[] = "m.status LIKE '%".$conn->real_escape_string($status)."%'";
        }

        $sql = "SELECT m.*, p.product_name
                FROM rnd_material m
                LEFT JOIN product p ON m.product_code = p.product_code
                WHERE ".implode(' AND ', $where)."
                ORDER BY m.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["equivalent"] = json_decode($row["equivalent"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStorageConditions") {
        $output = array();
        $plant_id = isset($_GET["plant_id"]) ? trim($_GET["plant_id"]) : '';
        if ($plant_id !== '') {
            $pid = $conn->real_escape_string($plant_id);
            $sql = "SELECT * FROM storage_conditions WHERE CAST(plant_id AS CHAR)='".$pid."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        if (count($output) === 0) {
            $sql = "SELECT * FROM storage_conditions ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        qa_safe_json_echo($output);
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
