<?php
/**
 * Ensure Medicap master tables exist (create from PHP when missing).
 * Call medicap_ensure_schema($conn) from seed / setup endpoints.
 */
function medicap_ensure_schema($conn) {
    $results = array();

    $sqlChemical = "CREATE TABLE IF NOT EXISTS chemical (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_no text DEFAULT NULL,
        chemical_no text DEFAULT NULL,
        chemical_name text DEFAULT NULL,
        molecular_wt text DEFAULT NULL,
        pack_size text DEFAULT NULL,
        cas_name text DEFAULT NULL,
        grade text DEFAULT NULL,
        make text DEFAULT NULL,
        unit text DEFAULT NULL,
        gst text DEFAULT NULL,
        hsn text DEFAULT NULL,
        status text DEFAULT 'pending',
        entry_by text DEFAULT NULL,
        entry_date text DEFAULT NULL,
        approve_by text DEFAULT NULL,
        approve_date text DEFAULT NULL,
        plant_id text DEFAULT NULL,
        chem_type text DEFAULT NULL,
        msds_file text DEFAULT NULL,
        pka text DEFAULT NULL,
        ph_range text DEFAULT NULL,
        base_color text DEFAULT NULL,
        acid_color text DEFAULT NULL,
        indicator text DEFAULT NULL,
        open_date text DEFAULT NULL,
        start_date text DEFAULT NULL,
        scrap_source text DEFAULT '0',
        scrap_name text DEFAULT '0',
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $results['chemical'] = $conn->query($sqlChemical) ? 'ok' : $conn->error;

    $sqlGlassware = "CREATE TABLE IF NOT EXISTS glassware (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_no text DEFAULT NULL,
        glassware_no text DEFAULT NULL,
        name text DEFAULT NULL,
        capacity text DEFAULT NULL,
        unit text DEFAULT NULL,
        glassware_class text DEFAULT NULL,
        description text DEFAULT NULL,
        make text DEFAULT NULL,
        gst text DEFAULT NULL,
        hsn text DEFAULT NULL,
        status text DEFAULT 'pending',
        entry_by text DEFAULT NULL,
        entry_date text DEFAULT NULL,
        approve_by text DEFAULT NULL,
        approve_date text DEFAULT NULL,
        plant_id text DEFAULT NULL,
        coa text DEFAULT '0',
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $results['glassware'] = $conn->query($sqlGlassware) ? 'ok' : $conn->error;

    $sqlChemMfg = "CREATE TABLE IF NOT EXISTS chem_manufaturer (
        id int(11) NOT NULL AUTO_INCREMENT,
        chemical_id int(11) DEFAULT NULL,
        vendor_no text DEFAULT NULL,
        vendor_name text DEFAULT NULL,
        plant_id text DEFAULT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $results['chem_manufaturer'] = $conn->query($sqlChemMfg) ? 'ok' : $conn->error;

    // Best-effort: add missing columns on older chemical tables.
    $chemExtra = array(
        'chem_type' => "ALTER TABLE chemical ADD COLUMN chem_type text DEFAULT NULL",
        'msds_file' => "ALTER TABLE chemical ADD COLUMN msds_file text DEFAULT NULL",
        'pka' => "ALTER TABLE chemical ADD COLUMN pka text DEFAULT NULL",
        'ph_range' => "ALTER TABLE chemical ADD COLUMN ph_range text DEFAULT NULL",
        'base_color' => "ALTER TABLE chemical ADD COLUMN base_color text DEFAULT NULL",
        'acid_color' => "ALTER TABLE chemical ADD COLUMN acid_color text DEFAULT NULL",
        'indicator' => "ALTER TABLE chemical ADD COLUMN indicator text DEFAULT NULL",
        'open_date' => "ALTER TABLE chemical ADD COLUMN open_date text DEFAULT NULL",
        'start_date' => "ALTER TABLE chemical ADD COLUMN start_date text DEFAULT NULL",
    );
    $existing = array();
    $colRes = $conn->query("SHOW COLUMNS FROM chemical");
    if ($colRes) {
        while ($col = $colRes->fetch_assoc()) {
            $existing[$col['Field']] = true;
        }
    }
    foreach ($chemExtra as $col => $alterSql) {
        if (!isset($existing[$col])) {
            $conn->query($alterSql);
        }
    }

    return $results;
}
