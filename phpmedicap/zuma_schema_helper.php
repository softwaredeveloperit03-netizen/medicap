<?php
/**
 * Uniform department-prefixed names for SOP PHP files and MySQL tables.
 *
 * Convention:
 *   PHP file:  {dept}/sop_{dept_code}_{sop3}.php     e.g. microbiology/sop_ml_055.php
 *   Table:     {dept}_sop_{dept_code}{sop3}_f{fmt2}  e.g. micro_sop_ml055_f01
 *
 * Department codes:
 *   micro  — Microbiology (SOP-ML-xxx)
 *   qc     — Quality Control / QA SOP (SOP-QA-xxx)
 *   hr     — Human Resources
 *   pr     — Production
 *
 * Every SOP endpoint must call zuma_ensure_table() on first request so tables
 * are created automatically on the remote server DB.
 */

if (!function_exists('zuma_dept_prefix')) {
    function zuma_dept_prefix($dept)
    {
        $map = array(
            'microbiology' => 'micro',
            'micro'        => 'micro',
            'mb'           => 'micro',
            'qa'           => 'qc',
            'qc'           => 'qc',
            'qa_sop'       => 'qc',
            'hr'           => 'hr',
            'production'   => 'pr',
            'pr'           => 'pr',
            'fnd'          => 'fnd',
            'fd'           => 'fnd',
            'rnd'          => 'fnd',
            'it'           => 'it',
        );
        $key = strtolower(trim((string)$dept));
        return isset($map[$key]) ? $map[$key] : preg_replace('/[^a-z0-9_]/', '', $key);
    }
}

if (!function_exists('zuma_sop_table_name')) {
    /**
     * @param string $dept     micro | qc | hr | pr
     * @param string $sopCode  ML-055, QA-063, HR-001, PR-003
     * @param string $format   F01, F02, prep_log, etc.
     */
    function zuma_sop_table_name($dept, $sopCode, $format = 'f01')
    {
        $prefix = zuma_dept_prefix($dept);
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$sopCode));
        $fmt = strtolower(preg_replace('/[^a-z0-9_]/', '', (string)$format));
        if ($fmt === '') {
            $fmt = 'f01';
        }
        if (!preg_match('/^f\d+/', $fmt) && !preg_match('/^[a-z]/', $fmt)) {
            $fmt = 'f' . $fmt;
        }
        return $prefix . '_sop_' . strtolower($code) . '_' . $fmt;
    }
}

if (!function_exists('zuma_sop_php_basename')) {
    /** e.g. sop_ml_055.php for Microbiology SOP-ML-055 */
    function zuma_sop_php_basename($deptCode, $sopNum)
    {
        $dc = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$deptCode));
        $n = str_pad(preg_replace('/\D/', '', (string)$sopNum), 3, '0', STR_PAD_LEFT);
        return 'sop_' . $dc . '_' . $n . '.php';
    }
}

if (!function_exists('zuma_ensure_table')) {
    /**
     * Run CREATE TABLE IF NOT EXISTS. Returns true on success.
     * $columnsSql is the inner column/index definitions (without outer parens).
     */
    function zuma_ensure_table($conn, $tableName, $columnsSql)
    {
        $safe = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$tableName));
        if ($safe === '') {
            return false;
        }
        $sql = 'CREATE TABLE IF NOT EXISTS `' . $safe . '` (' . $columnsSql . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        return (bool)$conn->query($sql);
    }
}

if (!function_exists('zuma_sop_standard_columns')) {
    /** Shared audit columns for department SOP log tables. */
    function zuma_sop_standard_columns()
    {
        return "
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            plant_id VARCHAR(50) DEFAULT NULL,
            log_date DATE DEFAULT NULL,
            form_data LONGTEXT DEFAULT NULL,
            signatures LONGTEXT DEFAULT NULL,
            status VARCHAR(30) DEFAULT 'pending',
            checking_remark VARCHAR(500) DEFAULT NULL,
            checked_on DATETIME DEFAULT NULL,
            entry_by VARCHAR(50) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            INDEX idx_plant (plant_id),
            INDEX idx_status (status),
            INDEX idx_log_date (log_date)
        ";
    }
}
