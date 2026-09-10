<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
require_once __DIR__ . '/../qc/seed_om_helper.php';
$output = Array();
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
    
    if ($_GET["type"] == "saveGeneralMaterial") {
        try {
            $data = is_array($input) ? $input : array();
            if (count($data) === 0 && !empty($_POST)) {
                $data = $_POST;
            }
            if (count($data) === 0) {
                $rawBody = file_get_contents('php://input');
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }

            $materialName = isset($data["material_name"]) ? trim((string)$data["material_name"]) : '';
            if ($materialName === '') {
                echo json_encode(array("status" => "error", "message" => "Material name is required"));
                exit;
            }

            $esc = function ($key, $default = '') use ($conn, $data) {
                $val = isset($data[$key]) ? trim((string)$data[$key]) : $default;
                return "'".$conn->real_escape_string($val)."'";
            };

            $valuesMap = array(
                'plant_id' => "'".$conn->real_escape_string($_GET["plant_id"])."'",
                'material_type' => $esc('material_type'),
                'material_subtype' => $esc('material_subtype'),
                'material_name' => "'".$conn->real_escape_string($materialName)."'",
                'unit' => $esc('unit'),
                'gst' => $esc('gst'),
                'hsn' => $esc('hsn'),
                'equipment' => $esc('equipment'),
                'part_type' => $esc('part_type'),
                'part_size' => $esc('part_size'),
                'part_no' => $esc('part_no'),
                'storage_condition' => $esc('storage_condition', 'NA'),
                'inventory' => $esc('inventory', '0'),
                'description' => $esc('description'),
                'safety' => $esc('safety'),
                'grade' => "'NA'",
                'capacity' => $esc('capacity'),
                'status' => "'Approved'",
                'entry_by' => "'".$conn->real_escape_string($_GET["emp_id"])."'",
                'entry_date' => "'".$entry_date."'",
            );

            if (!function_exists('medicap_others_material_insert') || !medicap_others_material_insert($conn, $valuesMap)) {
                echo json_encode(array("status" => "error", "message" => $conn->error ?: "Could not save material"));
                exit;
            }

            $material_type = isset($data['material_type']) ? $data['material_type'] : '';
            $mtcode = 'O';
            if ($material_type == "Engg Store Mech") {
                $mtcode = "M";
            } elseif ($material_type == "Engg Store Elect") {
                $mtcode = "E";
            } elseif ($material_type == "Stationary") {
                $mtcode = "S";
            } elseif ($material_type == "House Keepiing" || $material_type == "Housekeeping") {
                $mtcode = "H";
            } elseif ($material_type == "Canteen") {
                $mtcode = "C";
            } elseif ($material_type == "Building Material") {
                $mtcode = "B";
            } elseif ($material_type == "Machine Spares (Dedicated)") {
                $mtcode = "D";
            } elseif ($material_type == "Machine Spares (Common)") {
                $mtcode = "N";
            } elseif ($material_type == "QC Materials") {
                $mtcode = "Q";
            } elseif ($material_type == "IT Materials") {
                $mtcode = "I";
            }

            $last_id = $conn->insert_id;
            $plant_code = isset($data['plant_code']) ? $data['plant_code'] : '';
            $materialSubTypeCode = isset($data['materialSubTypeCode']) ? $data['materialSubTypeCode'] : '';
            $padded_id = str_pad((string)$last_id, 4, '0', STR_PAD_LEFT);
            $material_code = $plant_code.$mtcode.$materialSubTypeCode.$padded_id;

            $update = $conn->prepare("UPDATE others_material SET material_code=? WHERE id=?");
            if ($update) {
                $update->bind_param("si", $material_code, $last_id);
                $update->execute();
            }

            echo json_encode(array("status" => "success", "material_code" => $material_code));
        } catch (\Throwable $e) {
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
    }
    else if ($_GET["type"] == "getequipments") {
        $output = Array();
         $sql = "SELECT id,plant_id,equipment_category,equipment_type,equipment_code,equipment_name,department,location ,serial_no,status FROM equipment  WHERE plant_id='".$_GET["plant_id"]."' AND status = 'Active' ORDER BY equipment_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getGeneralMaterials1") {
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $materialType = isset($_GET["material_type"]) ? trim((string)$_GET["material_type"]) : 'ALL';
        $q = isset($_GET["q"]) ? trim((string)$_GET["q"]) : '';
        $paged = isset($_GET["page"]);
        $page = max(1, (int)($_GET["page"] ?? 1));
        $pageSize = (int)($_GET["pageSize"] ?? 10);
        if (!in_array($pageSize, array(10, 20, 50, 100), true)) {
            $pageSize = 10;
        }

        $where = "plant_id = '".$plantId."' AND status = 'Approved'";
        if ($materialType !== '' && strtoupper($materialType) !== 'ALL') {
            $where .= " AND material_type = '".$conn->real_escape_string($materialType)."'";
        }
        if ($q !== '') {
            $like = "%".$conn->real_escape_string($q)."%";
            $where .= " AND (
                material_type LIKE '".$like."'
                OR material_subtype LIKE '".$like."'
                OR material_code LIKE '".$like."'
                OR material_name LIKE '".$like."'
                OR unit LIKE '".$like."'
                OR entry_by LIKE '".$like."'
            )";
        }

        $sql = "SELECT * FROM others_material WHERE ".$where." ORDER BY material_name ASC";
        if ($paged) {
            $total = 0;
            $countRes = $conn->query("SELECT COUNT(*) AS cnt FROM others_material WHERE ".$where);
            if ($countRes && ($c = $countRes->fetch_assoc())) {
                $total = (int)$c['cnt'];
            }
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT ".$offset.", ".$pageSize;
            $rows = array();
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            echo json_encode(array(
                'rows' => $rows,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
            ));
        } else {
            $output = array();
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        }
    } 
    else if ($_GET["type"] == "getGeneralMaterialSubtypes") {
        $output = array();
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $materialType = $conn->real_escape_string(trim((string)($_GET["material_type"] ?? '')));
        if ($materialType !== '') {
            $sql = "SELECT DISTINCT material_subtype FROM others_material WHERE plant_id = '".$plantId."' AND status = 'Approved' AND material_type = '".$materialType."' AND IFNULL(material_subtype,'') <> '' ORDER BY material_subtype ASC";
        $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
            if (count($output) === 0 && strcasecmp($materialType, 'QC Materials') === 0) {
                foreach (array('Chemical', 'Chemicals', 'Reagents', 'Glassware') as $fallbackSubtype) {
                    $output[] = array('material_subtype' => $fallbackSubtype);
                }
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getMaterialForGeneralQuatation") {
        $output = array();
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $materialType = $conn->real_escape_string(trim((string)($_GET["material_type"] ?? '')));
        $materialSubtype = $conn->real_escape_string(trim((string)($_GET["material_subtype"] ?? '')));
        if ($materialType !== '' && $materialSubtype !== '') {
            $subtypeVariants = array($materialSubtype);
            $subtypeLower = strtolower($materialSubtype);
            if ($subtypeLower === 'chemical' || $subtypeLower === 'chemicals') {
                $subtypeVariants = array('Chemical', 'Chemicals');
            } elseif ($subtypeLower === 'reagent' || $subtypeLower === 'reagents') {
                $subtypeVariants = array('Reagent', 'Reagents');
            }
            $subtypeList = array();
            foreach ($subtypeVariants as $variant) {
                $subtypeList[] = "'".$conn->real_escape_string($variant)."'";
            }
            $subtypeIn = implode(',', array_unique($subtypeList));
            $isReagent = ($subtypeLower === 'reagent' || $subtypeLower === 'reagents');
            $isChemical = ($subtypeLower === 'chemical' || $subtypeLower === 'chemicals');

            // Chemicals: chemical master. Reagents: others_material only (do not dump all chemicals).
            if (strcasecmp($materialType, 'QC Materials') === 0 && $isChemical) {
                $sqlChem = "SELECT * FROM chemical WHERE plant_id = '".$plantId."'
                    AND LOWER(TRIM(IFNULL(status,''))) NOT IN ('reject', 'rejected', 'inactive', 'pending')
                    ORDER BY chemical_name ASC";
                $resultChem = $conn->query($sqlChem);
                if ($resultChem && $resultChem->num_rows > 0) {
                    while ($row = $resultChem->fetch_assoc()) {
                        $output[] = array(
                            'id' => $row['id'],
                            'material_code' => $row['chemical_no'],
                            'chemical_no' => $row['chemical_no'],
                            'material_name' => $row['chemical_name'],
                            'material_type' => 'QC Materials',
                            'material_subtype' => 'Chemicals',
                            'grade' => isset($row['grade']) ? $row['grade'] : '',
                            'unit' => isset($row['unit']) ? $row['unit'] : '',
                            'gst' => isset($row['gst']) ? $row['gst'] : '',
                            'quotation_amt' => 0,
                            'selected' => false,
                            'quotation_per' => isset($row['unit']) ? $row['unit'] : '',
                            'tax' => isset($row['gst']) ? $row['gst'] : '',
                        );
                    }
                }
            }

            // Always include matching others_material rows (Reagents live here).
            $queries = array(
                "SELECT * FROM others_material WHERE plant_id = '".$plantId."' AND status = 'Approved' AND material_type = '".$materialType."' AND material_subtype IN (".$subtypeIn.") ORDER BY material_name ASC",
                "SELECT * FROM my_view WHERE plant_id = '".$plantId."' AND material_type = '".$materialType."' AND material_subtype IN (".$subtypeIn.") ORDER BY material_name ASC",
            );
            foreach ($queries as $sql) {
                $result = @$conn->query($sql);
                if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                        if (empty($row['material_code']) && !empty($row['id'])) {
                            $row['material_code'] = 'GEN-OM-'.$row['id'];
                        }
                $row['quotation_amt'] = 0;
                $row['selected'] = false;
                        $row['quotation_per'] = isset($row['unit']) ? $row['unit'] : '';
                        $row['tax'] = isset($row['gst']) ? $row['gst'] : '';
                $output[] = $row;
                    }
                    if ($isReagent || count($output) > 0) {
                        break;
                    }
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getGeneralMaterialsForRequisition") {
        $plantId = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $materialType = isset($_GET["material_type"]) ? trim((string)$_GET["material_type"]) : 'ALL';
        $typeEsc = $conn->real_escape_string($materialType);
        $includeAllTypes = ($materialType === '' || strtoupper($materialType) === 'ALL');

        $byCode = array();

        $appendRow = function ($row) use (&$byCode) {
            if (!is_array($row)) {
                return;
            }
            $code = trim((string)($row['material_code'] ?? ''));
            if ($code === '' && !empty($row['id'])) {
                $code = 'GEN-OM-'.$row['id'];
                $row['material_code'] = $code;
            }
            if ($code === '') {
                return;
            }
            $key = strtoupper($code);
            if (!isset($byCode[$key])) {
                $byCode[$key] = $row;
            }
        };

        $othersWhere = "plant_id = '".$plantId."' AND LOWER(TRIM(IFNULL(status,''))) NOT IN ('reject', 'rejected', 'inactive')";
        if (!$includeAllTypes) {
            $othersWhere .= " AND material_type = '".$typeEsc."'";
        }
        $othersSql = "SELECT * FROM others_material WHERE ".$othersWhere." ORDER BY material_name ASC";
        $othersRes = $conn->query($othersSql);
        if ($othersRes && $othersRes->num_rows > 0) {
            while ($row = $othersRes->fetch_assoc()) {
                if (trim((string)($row['material_code'] ?? '')) === '' && !empty($row['id'])) {
                    $row['material_code'] = 'GEN-OM-'.$row['id'];
                }
                $row['uom'] = isset($row['unit']) ? $row['unit'] : '';
                $appendRow($row);
            }
        }

        if ($includeAllTypes || strcasecmp($materialType, 'QC Materials') === 0) {
            $chemSql = "SELECT id, chemical_no, chemical_name, grade, unit, gst, status
                FROM chemical
                WHERE plant_id = '".$plantId."'
                AND LOWER(TRIM(IFNULL(status,''))) NOT IN ('reject', 'rejected', 'inactive')
                ORDER BY chemical_name ASC";
            $chemRes = $conn->query($chemSql);
            if ($chemRes && $chemRes->num_rows > 0) {
                while ($row = $chemRes->fetch_assoc()) {
                    $appendRow(array(
                        'id' => $row['id'],
                        'material_code' => $row['chemical_no'],
                        'chemical_no' => $row['chemical_no'],
                        'material_name' => $row['chemical_name'],
                        'material_type' => 'QC Materials',
                        'material_subtype' => 'Chemical',
                        'grade' => isset($row['grade']) ? $row['grade'] : '',
                        'unit' => isset($row['unit']) ? $row['unit'] : '',
                        'uom' => isset($row['unit']) ? $row['unit'] : '',
                        'gst' => isset($row['gst']) ? $row['gst'] : '',
                        'status' => isset($row['status']) ? $row['status'] : '',
                    ));
                }
            }
        }

        if ($includeAllTypes) {
            $legacySql = "SELECT * FROM general_material WHERE plant_id = '".$plantId."' ORDER BY material_name ASC";
            $legacyRes = @$conn->query($legacySql);
            if ($legacyRes && $legacyRes->num_rows > 0) {
                while ($row = $legacyRes->fetch_assoc()) {
                    if (trim((string)($row['material_code'] ?? '')) === '') {
                        continue;
                    }
                    $row['uom'] = isset($row['unit']) ? $row['unit'] : '';
                    $appendRow($row);
                }
            }
        }

        $output = array_values($byCode);
        usort($output, function ($a, $b) {
            return strcasecmp((string)($a['material_name'] ?? ''), (string)($b['material_name'] ?? ''));
        });
        echo json_encode($output);
    } 
    
 

}

$conn->close();
?>