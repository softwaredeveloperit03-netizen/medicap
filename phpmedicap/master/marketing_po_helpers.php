<?php
/**
 * Marketing FO/PO — product dropdown helpers (common.php getProductsss + po.php formula gate).
 */

if (!function_exists('gw_marketing_unit_formula_status_sql')) {
    /** SQL predicate: unit formula row is usable for marketing PO product list. */
    function gw_marketing_unit_formula_status_sql($alias = 'uf')
    {
        $a = preg_replace('/[^a-z_]/i', '', (string)$alias);
        if ($a === '') {
            $a = 'uf';
        }
        return "(
            LOWER(TRIM(IFNULL({$a}.status,''))) IN ('approve', 'approved')
            OR (
                TRIM(IFNULL({$a}.approve_by,'')) <> ''
                AND TRIM(IFNULL({$a}.checked_by,'')) <> ''
            )
            OR (
                LOWER(TRIM(IFNULL({$a}.status,''))) = 'pending'
                AND (
                    TRIM(IFNULL({$a}.raw_materials,'')) NOT IN ('', '[]', 'null', '{}')
                    OR TRIM(IFNULL({$a}.packing_materials,'')) NOT IN ('', '[]', 'null', '{}')
                    OR TRIM(IFNULL({$a}.primary_pm_list,'')) NOT IN ('', '[]', 'null', '{}')
                    OR TRIM(IFNULL({$a}.consumeableMaterial,'')) NOT IN ('', '[]', 'null', '{}')
                    OR TRIM(IFNULL({$a}.additional_materials,'')) NOT IN ('', '[]', 'null', '{}')
                )
            )
        )";
    }
}

if (!function_exists('gw_marketing_enrich_po_product_row')) {
    function gw_marketing_enrich_po_product_row($conn, array $row, $plantId)
    {
        $plantId = trim((string)$plantId);
        $productCode = trim((string)($row['product_code'] ?? ''));
        $pcEsc = $conn->real_escape_string($productCode);
        $plEsc = $conn->real_escape_string($plantId);

        $output1 = array();
        $ufStatusSql = gw_marketing_unit_formula_status_sql('a');
        $sql1 = "SELECT b.pack_size, b.unit FROM unitformula a
                 LEFT JOIN unitformula_pm_dtl b ON a.id = b.unit_formula_id
                 WHERE a.product_code = '$pcEsc'
                   AND a.plant_id = '$plEsc'
                   AND ($ufStatusSql)
                 ORDER BY a.id DESC, b.id ASC";
        $result1 = $conn->query($sql1);
        if ($result1 && $result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }
        $row['pack_size'] = $output1;

        $output2 = array();
        $sql2 = "SELECT *, a.batch_formula_weight AS batch_size, a.rm_batch_size_unit AS batch_unit
                 FROM batch_formula_info a
                 WHERE a.product_code = '$pcEsc'
                   AND a.plant_id = '$plEsc'
                 ORDER BY a.id DESC";
        $result2 = $conn->query($sql2);
        if ($result2 && $result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output2[] = $row2;
            }
        }
        $row['batch_size'] = $output2;

        $hasBatchFormula = count($output2) > 0;
        $hasBatchMaterials = false;
        if ($hasBatchFormula) {
            $bfrNos = array();
            foreach ($output2 as $bfrRow) {
                $bfrNo = trim((string)($bfrRow['bfr_no'] ?? ''));
                if ($bfrNo !== '') {
                    $bfrNos[] = $bfrNo;
                }
            }
            $bfrNos = array_values(array_unique($bfrNos));
            if (count($bfrNos) > 0) {
                $bfrList = "'" . implode("','", array_map(array($conn, 'real_escape_string'), $bfrNos)) . "'";
                $bmRes = $conn->query(
                    "SELECT COUNT(*) AS c FROM batch_materials
                     WHERE bfr_no IN ($bfrList)
                       AND TRIM(IFNULL(material_code, '')) NOT IN ('', '-', 'N/A')"
                );
                if ($bmRes && ($bmRow = $bmRes->fetch_assoc())) {
                    $hasBatchMaterials = ((int)($bmRow['c'] ?? 0)) > 0;
                }
            }
        }
        $row['has_batch_formula'] = $hasBatchFormula;
        $row['has_batch_materials'] = $hasBatchMaterials;
        $row['formula_plan_ready'] = $hasBatchFormula && $hasBatchMaterials;

        return $row;
    }
}

if (!function_exists('gw_marketing_get_po_products_for_plant')) {
    function gw_marketing_get_po_products_for_plant($conn, $plantId)
    {
        $plantId = trim((string)$plantId);
        if ($plantId === '') {
            return array();
        }
        $plEsc = $conn->real_escape_string($plantId);
        $ufEligible = gw_marketing_unit_formula_status_sql('uf');
        $output = array();
        $seen = array();

        $sqlWithFormula = "SELECT p.*, m.plant_name, m.plant_full_name, m.client_name, m.plant_code, uf.status AS unit_formula_status
            FROM product p
            LEFT JOIN plant m ON p.plant_id = m.plant_id
            INNER JOIN unitformula uf ON uf.product_code = p.product_code AND uf.plant_id = p.plant_id
            INNER JOIN (
                SELECT product_code, plant_id, MAX(id) AS max_id
                FROM unitformula
                WHERE plant_id = '$plEsc' AND ($ufEligible)
                GROUP BY product_code, plant_id
            ) latest ON latest.max_id = uf.id
            WHERE p.plant_id = '$plEsc'
            GROUP BY p.id
            ORDER BY p.product_name";
        $result = $conn->query($sqlWithFormula);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['plant_display_name'] = gw_plant_display_name($row);
                $row['plant_name'] = $row['plant_display_name'];
                $row['has_unit_formula'] = true;
                $code = trim((string)($row['product_code'] ?? ''));
                if ($code !== '') {
                    $seen[$code] = true;
                }
                $output[] = gw_marketing_enrich_po_product_row($conn, $row, $plantId);
            }
        }

        $sqlApprovedOnly = "SELECT p.*, m.plant_name, m.plant_full_name, m.client_name, m.plant_code
            FROM product p
            LEFT JOIN plant m ON p.plant_id = m.plant_id
            WHERE p.plant_id = '$plEsc'
              AND LOWER(TRIM(IFNULL(p.status,''))) = 'approve'
            ORDER BY p.product_name";
        $result2 = $conn->query($sqlApprovedOnly);
        if ($result2 && $result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $code = trim((string)($row['product_code'] ?? ''));
                if ($code === '' || isset($seen[$code])) {
                    continue;
                }
                $seen[$code] = true;
                $row['plant_display_name'] = gw_plant_display_name($row);
                $row['plant_name'] = $row['plant_display_name'];
                $row['has_unit_formula'] = false;
                $row['unit_formula_status'] = '';
                $output[] = gw_marketing_enrich_po_product_row($conn, $row, $plantId);
            }
        }

        usort($output, function ($a, $b) {
            $aReady = !empty($a['formula_plan_ready']) ? 1 : 0;
            $bReady = !empty($b['formula_plan_ready']) ? 1 : 0;
            if ($aReady !== $bReady) {
                return $bReady - $aReady;
            }
            $aUf = !empty($a['has_unit_formula']) ? 1 : 0;
            $bUf = !empty($b['has_unit_formula']) ? 1 : 0;
            if ($aUf !== $bUf) {
                return $bUf - $aUf;
            }
            $aBfr = !empty($a['has_batch_formula']) ? 1 : 0;
            $bBfr = !empty($b['has_batch_formula']) ? 1 : 0;
            if ($aBfr !== $bBfr) {
                return $bBfr - $aBfr;
            }
            return strcasecmp((string)($a['product_name'] ?? ''), (string)($b['product_name'] ?? ''));
        });

        return $output;
    }
}
