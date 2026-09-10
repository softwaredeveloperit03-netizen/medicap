<?php
/**
 * Insert into others_material with required columns filled safely.
 */
function medicap_others_material_insert($conn, $valuesMap) {
    $cols = array();
    $res = $conn->query("SHOW COLUMNS FROM others_material");
    if (!$res) {
        return false;
    }
    while ($col = $res->fetch_assoc()) {
        $cols[$col['Field']] = $col;
    }
    $fields = array();
    $values = array();
    foreach ($valuesMap as $field => $sqlValue) {
        if (isset($cols[$field])) {
            $fields[] = $field;
            $values[] = $sqlValue;
            unset($cols[$field]);
        }
    }
    // Fill remaining NOT NULL columns that have no default.
    foreach ($cols as $field => $meta) {
        if (strtoupper($meta['Null']) === 'NO' && $meta['Default'] === null && strpos($meta['Extra'], 'auto_increment') === false) {
            $fields[] = $field;
            $values[] = "''";
        }
    }
    if (count($fields) === 0) {
        return false;
    }
    $sql = "INSERT INTO others_material (".implode(',', $fields).") VALUES (".implode(',', $values).")";
    return $conn->query($sql);
}
