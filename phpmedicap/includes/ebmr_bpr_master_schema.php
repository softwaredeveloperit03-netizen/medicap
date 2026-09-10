<?php
/**
 * Cyclone eBMR / BPR master (Process Master module) — product-linked stage trees
 * for Production and Packing execution.
 */
function ebmr_bpr_master_bootstrap($conn)
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $conn->query("CREATE TABLE IF NOT EXISTS cyclone_ebmr_bpr_master (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(20) NOT NULL DEFAULT '142',
        product_code VARCHAR(80) NOT NULL,
        product_name VARCHAR(255) DEFAULT NULL,
        department VARCHAR(30) NOT NULL DEFAULT 'production',
        master_json LONGTEXT NOT NULL,
        revision INT NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'Active',
        updated_at DATETIME DEFAULT NULL,
        updated_by_emp_id VARCHAR(50) DEFAULT NULL,
        UNIQUE KEY uniq_plant_product_dept (plant_id, product_code, department),
        KEY idx_dept (department, plant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
