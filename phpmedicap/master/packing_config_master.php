<?php
require '../db.php';
require '../token.php';
header('response_token: test123456');
header('Content-Type: application/json; charset=utf-8');

$token = isset($_GET["token"]) ? $_GET["token"] : "";
$sqlTok = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sqlTok);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $entry_date = date("Y-m-d H:i:s");
    $plant_id = isset($_GET["plant_id"]) ? trim((string)$_GET["plant_id"]) : "";
    if ($plant_id === "" || strtolower($plant_id) === "null" || strtolower($plant_id) === "undefined") {
        $plant_id = "1126";
    }
    $plant_id = $conn->real_escape_string($plant_id);

    /**
     * Rows match the current plant, OR are "global" (NULL/blank plant), OR still have the seed placeholder.
     * Without this, imported SQL often keeps YOUR_PLANT_ID or empty plant_id and the log looks empty.
     */
    $plantMatchSql = "(CAST(plant_id AS CHAR) = '" . $plant_id . "' OR plant_id IS NULL OR TRIM(COALESCE(plant_id,'')) = '' OR plant_id = 'YOUR_PLANT_ID')";

    if ($_GET["type"] == "listPackingConfigMaster") {
        $output = array();
        $includeInactive = isset($_GET["includeInactive"]) && $_GET["includeInactive"] === "1";
        $includeJson = isset($_GET["includeJson"]) && $_GET["includeJson"] === "1";
        $activeClause = $includeInactive ? "" : " AND is_active=1 ";
        $cols = $includeJson
            ? "id, plant_id, configuration_title, dosage_nature, combination_title, pattern_hint, configuration_json, is_active, entry_by, entry_date"
            : "id, plant_id, configuration_title, dosage_nature, combination_title, pattern_hint, is_active, entry_by, entry_date";
        $sql = "SELECT " . $cols . "
                FROM packing_configuration_master 
                WHERE " . $plantMatchSql . $activeClause . "
                ORDER BY id DESC";
        $r = @$conn->query($sql);
        if (!$r) {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        } else {
            if ($r->num_rows > 0) {
                while ($row = $r->fetch_assoc()) {
                    if ($includeJson && isset($row['configuration_json']) && is_string($row['configuration_json'])) {
                        $decoded = json_decode($row['configuration_json'], true);
                        $row['config'] = is_array($decoded) ? $decoded : array();
                        unset($row['configuration_json']);
                    }
                    $output[] = $row;
                }
            }
            // Fallback: if plant filter yields nothing, still return active templates
            if (count($output) === 0) {
                $sql2 = "SELECT " . $cols . " FROM packing_configuration_master WHERE 1=1 " . $activeClause . " ORDER BY id DESC LIMIT 100";
                $r2 = @$conn->query($sql2);
                if ($r2 && $r2->num_rows > 0) {
                    while ($row = $r2->fetch_assoc()) {
                        if ($includeJson && isset($row['configuration_json']) && is_string($row['configuration_json'])) {
                            $decoded = json_decode($row['configuration_json'], true);
                            $row['config'] = is_array($decoded) ? $decoded : array();
                            unset($row['configuration_json']);
                        }
                        $output[] = $row;
                    }
                }
            }
            $flags = JSON_UNESCAPED_UNICODE;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }
            $json = json_encode($output, $flags);
            echo ($json !== false) ? $json : '[]';
        }
    } else if ($_GET["type"] == "getPackingConfigMasterById") {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
        $output = null;
        if ($id > 0) {
            $sql = "SELECT * FROM packing_configuration_master WHERE id=" . $id . " AND " . $plantMatchSql . " LIMIT 1";
            $r = $conn->query($sql);
            if ($r && $r->num_rows > 0) {
                $output = $r->fetch_assoc();
                if (isset($output['configuration_json']) && is_string($output['configuration_json'])) {
                    $decoded = json_decode($output['configuration_json'], true);
                    $output['config'] = is_array($decoded) ? $decoded : array();
                } else {
                    $output['config'] = array();
                }
            }
        }
        echo json_encode($output === null ? new stdClass() : $output);
    } else if ($_GET["type"] == "savePackingConfigMaster") {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            echo json_encode(array("status" => "invalid", "message" => "JSON body required"));
            exit;
        }

        $title = isset($input["configuration_title"]) ? trim($input["configuration_title"]) : "";
        if ($title === "") {
            echo json_encode(array("status" => "invalid", "message" => "configuration_title required"));
            exit;
        }

        $dosage_nature = isset($input["dosage_nature"]) ? $conn->real_escape_string($input["dosage_nature"]) : "";
        $combination_title = isset($input["combination_title"]) ? $conn->real_escape_string($input["combination_title"]) : "";
        $pattern_hint = isset($input["pattern_hint"]) ? $conn->real_escape_string($input["pattern_hint"]) : "";
        $titleEsc = $conn->real_escape_string($title);
        $jsonEsc = $conn->real_escape_string(isset($input["configuration_json"]) ? $input["configuration_json"] : "{}");
        $emp = $conn->real_escape_string($_GET["emp_id"]);

        $sql = "INSERT INTO packing_configuration_master (plant_id, configuration_title, dosage_nature, combination_title, pattern_hint, configuration_json, is_active, entry_by, entry_date) VALUES (
            '" . $plant_id . "',
            '" . $titleEsc . "',
            '" . $dosage_nature . "',
            '" . $combination_title . "',
            '" . $pattern_hint . "',
            '" . $jsonEsc . "',
            1,
            '" . $emp . "',
            '" . $entry_date . "'
        )";

        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success", "id" => $conn->insert_id));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    } else if ($_GET["type"] == "deactivatePackingConfigMaster") {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
        if ($id <= 0) {
            echo json_encode(array("status" => "invalid"));
            exit;
        }
        $sql = "UPDATE packing_configuration_master SET is_active=0 WHERE id=" . $id . " AND " . $plantMatchSql;
        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    } else {
        echo json_encode(array("status" => "unknown_type"));
    }
} else {
    echo json_encode(array("status" => "invalid_token"));
}
