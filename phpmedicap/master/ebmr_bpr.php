<?php
/**
 * EBMR/BPR Master API
 * Generic-profile eBMR / eBPR builder + supporting masters.
 *
 * All tables are auto-created on first call (CREATE TABLE IF NOT EXISTS).
 * Auth + token pattern matches the other master/*.php files.
 */

require '../db.php';
require '../token.php';

/* ============================================================
   TABLE BOOTSTRAP
============================================================ */
function ebmrbpr_ensure_tables($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_stage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        stage_code VARCHAR(60) DEFAULT NULL,
        stage_name VARCHAR(255) NOT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        seq_no INT DEFAULT 0,
        description TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_stage_plant (plant_id),
        INDEX idx_stage_dosage (dosage_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_step (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        stage_id INT DEFAULT NULL,
        step_code VARCHAR(60) DEFAULT NULL,
        step_name VARCHAR(255) NOT NULL,
        seq_no INT DEFAULT 0,
        description TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_step_stage (stage_id),
        INDEX idx_step_plant (plant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_inprocess_check (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        check_code VARCHAR(60) DEFAULT NULL,
        check_name VARCHAR(255) NOT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_ref VARCHAR(120) DEFAULT NULL,
        check_type VARCHAR(40) DEFAULT 'numeric',
        uom VARCHAR(40) DEFAULT NULL,
        target_value VARCHAR(120) DEFAULT NULL,
        min_limit VARCHAR(120) DEFAULT NULL,
        max_limit VARCHAR(120) DEFAULT NULL,
        tolerance VARCHAR(120) DEFAULT NULL,
        options_json LONGTEXT,
        frequency VARCHAR(120) DEFAULT NULL,
        sampling_plan VARCHAR(255) DEFAULT NULL,
        instrument VARCHAR(255) DEFAULT NULL,
        acceptance_criteria TEXT,
        is_critical VARCHAR(10) DEFAULT 'No',
        remarks TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ipc_plant (plant_id),
        INDEX idx_ipc_dosage (dosage_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_checkpoint (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        checkpoint_code VARCHAR(60) DEFAULT NULL,
        category VARCHAR(40) DEFAULT 'line_clearance',
        checkpoint_text TEXT NOT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_ref VARCHAR(120) DEFAULT NULL,
        expected_response VARCHAR(60) DEFAULT 'Yes/No',
        responsibility VARCHAR(120) DEFAULT NULL,
        seq_no INT DEFAULT 0,
        is_critical VARCHAR(10) DEFAULT 'No',
        remarks TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cp_plant (plant_id),
        INDEX idx_cp_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_ipqc_spec (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        spec_code VARCHAR(60) DEFAULT NULL,
        spec_name VARCHAR(255) NOT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_ref VARCHAR(120) DEFAULT NULL,
        parameter VARCHAR(255) DEFAULT NULL,
        test_method VARCHAR(255) DEFAULT NULL,
        specification TEXT,
        uom VARCHAR(40) DEFAULT NULL,
        min_limit VARCHAR(120) DEFAULT NULL,
        max_limit VARCHAR(120) DEFAULT NULL,
        frequency VARCHAR(120) DEFAULT NULL,
        is_critical VARCHAR(10) DEFAULT 'No',
        remarks TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_spec_plant (plant_id),
        INDEX idx_spec_dosage (dosage_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_work_allocation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        alloc_code VARCHAR(60) DEFAULT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_ref VARCHAR(120) DEFAULT NULL,
        activity VARCHAR(255) NOT NULL,
        designation VARCHAR(120) DEFAULT NULL,
        responsibility TEXT,
        manpower_count INT DEFAULT 1,
        skill_level VARCHAR(60) DEFAULT NULL,
        remarks TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_wa_plant (plant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_procedure (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        procedure_code VARCHAR(60) DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_ref VARCHAR(120) DEFAULT NULL,
        paragraphs_json LONGTEXT,
        remarks TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_proc_plant (plant_id),
        INDEX idx_proc_dosage (dosage_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_form_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        table_code VARCHAR(60) DEFAULT NULL,
        table_type VARCHAR(30) NOT NULL,
        title VARCHAR(255) NOT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_ref VARCHAR(120) DEFAULT NULL,
        columns_json LONGTEXT,
        rows_json LONGTEXT,
        remarks TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ft_type (table_type),
        INDEX idx_ft_plant (plant_id),
        INDEX idx_ft_dosage (dosage_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        profile_code VARCHAR(80) DEFAULT NULL,
        profile_name VARCHAR(255) NOT NULL,
        record_type VARCHAR(20) DEFAULT 'eBMR',
        dosage_form VARCHAR(120) DEFAULT NULL,
        version VARCHAR(30) DEFAULT '1.0',
        header_json LONGTEXT,
        static_json LONGTEXT,
        right_tabs_json LONGTEXT,
        status VARCHAR(30) DEFAULT 'Draft',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_by VARCHAR(60) DEFAULT NULL,
        updated_date DATETIME DEFAULT NULL,
        INDEX idx_profile_plant (plant_id),
        INDEX idx_profile_type (record_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_profile_stage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        stage_master_id INT DEFAULT NULL,
        stage_name VARCHAR(255) NOT NULL,
        seq_no INT DEFAULT 0,
        config_json LONGTEXT,
        INDEX idx_pstage_profile (profile_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_profile_step (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        profile_stage_id INT NOT NULL,
        step_master_id INT DEFAULT NULL,
        step_name VARCHAR(255) NOT NULL,
        seq_no INT DEFAULT 0,
        config_json LONGTEXT,
        INDEX idx_pstep_profile (profile_id),
        INDEX idx_pstep_stage (profile_stage_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_profile_product (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        plant_id VARCHAR(30) DEFAULT NULL,
        product_code VARCHAR(120) NOT NULL,
        product_name VARCHAR(255) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pp_profile (profile_id),
        INDEX idx_pp_product (product_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ---------- batch execution ---------- */
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_batch (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        profile_id INT NOT NULL,
        profile_code VARCHAR(80) DEFAULT NULL,
        record_type VARCHAR(20) DEFAULT 'eBMR',
        batch_no VARCHAR(120) NOT NULL,
        product_code VARCHAR(120) DEFAULT NULL,
        product_name VARCHAR(255) DEFAULT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        batch_size VARCHAR(120) DEFAULT NULL,
        batch_size_uom VARCHAR(40) DEFAULT NULL,
        mfg_date DATE DEFAULT NULL,
        exp_date DATE DEFAULT NULL,
        header_json LONGTEXT,
        static_json LONGTEXT,
        right_tabs_json LONGTEXT,
        exec_tabs_json LONGTEXT,
        status VARCHAR(30) DEFAULT 'In Progress',
        prod_signoff VARCHAR(20) DEFAULT 'Pending',
        prod_signoff_by VARCHAR(60) DEFAULT NULL,
        prod_signoff_at DATETIME DEFAULT NULL,
        prod_remark TEXT,
        qa_signoff VARCHAR(20) DEFAULT 'Pending',
        qa_signoff_by VARCHAR(60) DEFAULT NULL,
        qa_signoff_at DATETIME DEFAULT NULL,
        qa_remark TEXT,
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_batch_plant (plant_id),
        INDEX idx_batch_profile (profile_id),
        INDEX idx_batch_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_batch_step (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        stage_seq INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        stage_instructions TEXT,
        step_seq INT DEFAULT 0,
        step_name VARCHAR(255) DEFAULT NULL,
        template_json LONGTEXT,
        data_json LONGTEXT,
        status VARCHAR(20) DEFAULT 'Pending',
        done_by VARCHAR(60) DEFAULT NULL,
        done_at DATETIME DEFAULT NULL,
        checked_by VARCHAR(60) DEFAULT NULL,
        checked_at DATETIME DEFAULT NULL,
        remarks TEXT,
        INDEX idx_bstep_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_batch_stage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        stage_seq INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        config_json LONGTEXT,
        status VARCHAR(40) DEFAULT 'In Progress',
        checking_required TINYINT(1) DEFAULT 1,
        approval_required TINYINT(1) DEFAULT 0,
        qa_check_required TINYINT(1) DEFAULT 0,
        qa_approval_required TINYINT(1) DEFAULT 0,
        line_clearance_ipqa TINYINT(1) DEFAULT 1,
        sent_for_checking_by VARCHAR(60) DEFAULT NULL,
        sent_for_checking_at DATETIME DEFAULT NULL,
        checked_by VARCHAR(60) DEFAULT NULL,
        checked_at DATETIME DEFAULT NULL,
        checker_remark TEXT,
        check_status VARCHAR(20) DEFAULT 'Pending',
        approved_by VARCHAR(60) DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        approver_remark TEXT,
        approval_status VARCHAR(20) DEFAULT 'Pending',
        qa_checked_by VARCHAR(60) DEFAULT NULL,
        qa_checked_at DATETIME DEFAULT NULL,
        qa_check_remark TEXT,
        qa_check_status VARCHAR(20) DEFAULT 'Pending',
        qa_approved_by VARCHAR(60) DEFAULT NULL,
        qa_approved_at DATETIME DEFAULT NULL,
        qa_approval_status VARCHAR(20) DEFAULT 'Pending',
        reject_remark TEXT,
        operator_sign TEXT,
        UNIQUE KEY uk_batch_stage (batch_id, stage_seq),
        INDEX idx_bstage_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ebmrbpr_add_col($conn, 'ebmrbpr_batch_stage', 'doer_name', "doer_name VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_stage', 'doer_designation', "doer_designation VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_stage', 'checker_name', "checker_name VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_stage', 'checker_designation', "checker_designation VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_stage', 'approver_name', "approver_name VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_stage', 'approver_designation', "approver_designation VARCHAR(120) DEFAULT NULL");

    ebmrbpr_add_col($conn, 'ebmrbpr_batch_step', 'checker_remark', "checker_remark TEXT");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_batch_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        action VARCHAR(120) DEFAULT NULL,
        detail TEXT,
        by_emp VARCHAR(60) DEFAULT NULL,
        at_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_blog_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* CFR 21 Part 11 — extended audit trail columns (immutable event metadata) */
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'plant_id', "plant_id VARCHAR(30) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'emp_name', "emp_name VARCHAR(150) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'department', "department VARCHAR(120) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'designation', "designation VARCHAR(120) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'ip_address', "ip_address VARCHAR(64) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'user_agent', "user_agent VARCHAR(255) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'module', "module VARCHAR(120) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'record_ref', "record_ref VARCHAR(120) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'meaning', "meaning VARCHAR(80) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'auth_method', "auth_method VARCHAR(120) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'auth_type', "auth_type VARCHAR(40) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'signature_token', "signature_token VARCHAR(64) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'esign_id', "esign_id INT DEFAULT 0");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'batch_no', "batch_no VARCHAR(80) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'product_code', "product_code VARCHAR(60) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'product_name', "product_name VARCHAR(255) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'stage_name', "stage_name VARCHAR(255) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'step_name', "step_name VARCHAR(255) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'old_value', "old_value TEXT");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'new_value', "new_value TEXT");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'reason', "reason TEXT");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'record_hash', "record_hash VARCHAR(64) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_batch_log', 'host_name', "host_name VARCHAR(120) DEFAULT NULL");
    @$conn->query("CREATE INDEX idx_blog_time ON ebmrbpr_batch_log (at_time)");
    @$conn->query("CREATE INDEX idx_blog_action ON ebmrbpr_batch_log (action)");
    @$conn->query("CREATE INDEX idx_blog_emp ON ebmrbpr_batch_log (by_emp)");
    @$conn->query("CREATE INDEX idx_blog_ip ON ebmrbpr_batch_log (ip_address)");

    /* ---------- GMP correction / deviation / incident (CFR 21 Part 11, EU Annex 11) ---------- */
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_correction (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        batch_step_id INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        step_name VARCHAR(255) DEFAULT NULL,
        field_key VARCHAR(255) DEFAULT NULL,
        field_label VARCHAR(255) DEFAULT NULL,
        old_value TEXT,
        new_value TEXT,
        action_type VARCHAR(30) DEFAULT 'Correction',
        reason TEXT,
        ref_no VARCHAR(80) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'Raised',
        raised_by VARCHAR(60) DEFAULT NULL,
        raised_at DATETIME DEFAULT NULL,
        corrected_by VARCHAR(60) DEFAULT NULL,
        corrected_at DATETIME DEFAULT NULL,
        verified_by VARCHAR(60) DEFAULT NULL,
        verified_at DATETIME DEFAULT NULL,
        checker_remark TEXT,
        INDEX idx_corr_batch (batch_id),
        INDEX idx_corr_step (batch_step_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_deviation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        batch_id INT NOT NULL,
        batch_step_id INT DEFAULT 0,
        dev_no VARCHAR(80) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT,
        classification VARCHAR(30) DEFAULT 'Minor',
        root_cause TEXT,
        capa TEXT,
        impact TEXT,
        status VARCHAR(30) DEFAULT 'Open',
        raised_by VARCHAR(60) DEFAULT NULL,
        raised_at DATETIME DEFAULT NULL,
        closed_by VARCHAR(60) DEFAULT NULL,
        closed_at DATETIME DEFAULT NULL,
        INDEX idx_dev_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_incident (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        batch_id INT NOT NULL,
        batch_step_id INT DEFAULT 0,
        inc_no VARCHAR(80) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT,
        immediate_action TEXT,
        investigation TEXT,
        severity VARCHAR(30) DEFAULT 'Low',
        status VARCHAR(30) DEFAULT 'Open',
        raised_by VARCHAR(60) DEFAULT NULL,
        raised_at DATETIME DEFAULT NULL,
        closed_by VARCHAR(60) DEFAULT NULL,
        closed_at DATETIME DEFAULT NULL,
        INDEX idx_inc_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // BMR Configuration master (BMR no, dosage form, process type, approval matrix + workflow)
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        bmr_no VARCHAR(60) DEFAULT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        process_type VARCHAR(120) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        matrix_json LONGTEXT,
        status VARCHAR(30) DEFAULT 'Draft',
        prepared_emp VARCHAR(120) DEFAULT NULL,
        prepared_at DATETIME DEFAULT NULL,
        prepared_remark TEXT,
        reviewed_emp VARCHAR(120) DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        reviewed_remark TEXT,
        approved_emp VARCHAR(120) DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        approved_remark TEXT,
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cfg_plant (plant_id),
        INDEX idx_cfg_dosage (dosage_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // BMR Configuration ↔ Product binding (generic / brand)
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_config_product (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        config_id INT NOT NULL,
        bmr_no VARCHAR(60) DEFAULT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        product_code VARCHAR(120) DEFAULT NULL,
        product_name VARCHAR(255) DEFAULT NULL,
        product_type VARCHAR(30) DEFAULT 'Generic',
        status VARCHAR(20) DEFAULT 'active',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cfgp_config (config_id),
        INDEX idx_cfgp_product (product_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Stage & Step binding for a Configuration (process_type + dosage_form pair).
    // Independent of per-product masters; one row per stage-step under a config.
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_config_stage_step (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        config_id INT NOT NULL,
        bmr_no VARCHAR(60) DEFAULT NULL,
        process_type VARCHAR(120) DEFAULT NULL,
        dosage_form VARCHAR(120) DEFAULT NULL,
        stage_seq INT DEFAULT 0,
        stage_name VARCHAR(255) NOT NULL,
        step_seq INT DEFAULT 0,
        step_name VARCHAR(255) DEFAULT NULL,
        ipqc_testing VARCHAR(10) DEFAULT 'No',
        instruction TEXT,
        remark TEXT,
        status VARCHAR(20) DEFAULT 'active',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_css_config (config_id),
        INDEX idx_css_pair (process_type, dosage_form),
        INDEX idx_css_plant (plant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CFR Part 11 electronic-signature manifest log (all signed entries/saves)
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_esign (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        emp_id VARCHAR(60) DEFAULT NULL,
        emp_name VARCHAR(150) DEFAULT NULL,
        meaning VARCHAR(60) DEFAULT NULL,
        module VARCHAR(120) DEFAULT NULL,
        record_ref VARCHAR(120) DEFAULT NULL,
        detail VARCHAR(255) DEFAULT NULL,
        reason TEXT,
        ip VARCHAR(60) DEFAULT NULL,
        signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_esign_ref (module, record_ref),
        INDEX idx_esign_emp (emp_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // backfill columns for installs created before release/yield additions
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'released_by', "released_by VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'released_at', "released_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'release_remark', "release_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'hold_prev_status', "hold_prev_status VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'yield_json', "yield_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'work_order_id', "work_order_id INT DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'work_order_no', "work_order_no VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'batch_plan_id', "batch_plan_id INT DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'plan_no', "plan_no VARCHAR(80) DEFAULT NULL");
    // pending|done — Released eBMR awaiting / completed transfer to packing
    ebmrbpr_add_col($conn, 'ebmrbpr_batch', 'packing_transfer', "packing_transfer VARCHAR(20) DEFAULT 'pending'");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_step', 'qa_verified_by', "qa_verified_by VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_step', 'qa_verified_at', "qa_verified_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_step', 'qa_status', "qa_status VARCHAR(20) DEFAULT 'Pending'");

    /* ---------- batch-level work allocation (planning → personnel → execution) ---------- */
    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_work_alloc_header (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        work_order_id INT NOT NULL,
        work_order_no VARCHAR(60) DEFAULT NULL,
        batch_plan_id INT DEFAULT NULL,
        plan_no VARCHAR(80) DEFAULT NULL,
        batch_number VARCHAR(80) DEFAULT NULL,
        product_code VARCHAR(120) DEFAULT NULL,
        product_name VARCHAR(255) DEFAULT NULL,
        profile_id INT DEFAULT NULL,
        profile_code VARCHAR(80) DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'Pending',
        entry_by VARCHAR(60) DEFAULT NULL,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        allocated_by VARCHAR(60) DEFAULT NULL,
        allocated_at DATETIME DEFAULT NULL,
        UNIQUE KEY uk_wa_wo (work_order_id),
        INDEX idx_wa_hdr_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_batch_work_alloc (
        id INT AUTO_INCREMENT PRIMARY KEY,
        header_id INT NOT NULL,
        work_order_id INT NOT NULL,
        stage_seq INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        operator_emp VARCHAR(60) DEFAULT NULL,
        operator_name VARCHAR(120) DEFAULT NULL,
        office_emp VARCHAR(60) DEFAULT NULL,
        office_name VARCHAR(120) DEFAULT NULL,
        alt_operator_emp VARCHAR(60) DEFAULT NULL,
        alt_operator_name VARCHAR(120) DEFAULT NULL,
        alt_officer_emp VARCHAR(60) DEFAULT NULL,
        alt_officer_name VARCHAR(120) DEFAULT NULL,
        reviewer_emp VARCHAR(60) DEFAULT NULL,
        reviewer_name VARCHAR(120) DEFAULT NULL,
        approver_emp VARCHAR(60) DEFAULT NULL,
        approver_name VARCHAR(120) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        INDEX idx_bwa_hdr (header_id),
        INDEX idx_bwa_wo (work_order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_work_alloc_change_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        header_id INT DEFAULT 0,
        work_order_id INT NOT NULL,
        batch_number VARCHAR(80) DEFAULT NULL,
        action VARCHAR(40) DEFAULT 'Allocate',
        change_reason TEXT,
        before_json LONGTEXT,
        after_json LONGTEXT,
        changed_by VARCHAR(60) DEFAULT NULL,
        changed_by_name VARCHAR(120) DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_wal_wo (work_order_id),
        INDEX idx_wal_at (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Configure-master ↔ stage/step mapping + BMR preparation workflow on the profile
    ebmrbpr_add_col($conn, 'ebmrbpr_config', 'stage_map_json', "stage_map_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'config_id', "config_id INT DEFAULT 0");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'prep_emp', "prep_emp VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'prep_at', "prep_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'review_emp', "review_emp VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'review_at', "review_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'review_remark', "review_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'prod_emp', "prod_emp VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'prod_at', "prod_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'prod_remark', "prod_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'qa_emp', "qa_emp VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'qa_at', "qa_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile', 'qa_remark', "qa_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'product_code', "product_code VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'product_name', "product_name VARCHAR(255) DEFAULT NULL");

    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'stage_seq', "stage_seq INT DEFAULT 0");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'stage_name', "stage_name VARCHAR(255) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'qms_deviation_id', "qms_deviation_id INT DEFAULT 0");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'qms_status', "qms_status VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'qms_form_json', "qms_form_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'prod_head_remark', "prod_head_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'prod_head_continue', "prod_head_continue VARCHAR(20) DEFAULT 'Pending'");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'prod_head_by', "prod_head_by VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_deviation', 'prod_head_at', "prod_head_at DATETIME DEFAULT NULL");

    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'stage_seq', "stage_seq INT DEFAULT 0");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'stage_name', "stage_name VARCHAR(255) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'qms_incident_id', "qms_incident_id INT DEFAULT 0");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'qms_status', "qms_status VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'qms_form_json', "qms_form_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'next_stage_remark', "next_stage_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'next_stage_remark_by', "next_stage_remark_by VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_incident', 'next_stage_remark_at', "next_stage_remark_at DATETIME DEFAULT NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_breakdown (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        batch_id INT NOT NULL,
        stage_seq INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        breakdown_id INT DEFAULT 0,
        intimation_no VARCHAR(80) DEFAULT NULL,
        machine_name VARCHAR(255) DEFAULT NULL,
        machine_id VARCHAR(128) DEFAULT NULL,
        area_location VARCHAR(255) DEFAULT NULL,
        nature_of_breakdown VARCHAR(64) DEFAULT NULL,
        description TEXT,
        workflow_status VARCHAR(48) DEFAULT 'pending_qa_review',
        form_json LONGTEXT,
        raised_by VARCHAR(60) DEFAULT NULL,
        raised_at DATETIME DEFAULT NULL,
        INDEX idx_bd_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'product_code', "product_code VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'product_name', "product_name VARCHAR(255) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'sampling_by', "sampling_by VARCHAR(20) DEFAULT 'Production'");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'time_stamp', "time_stamp VARCHAR(20) DEFAULT 'Not Applicable'");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'yield_reconciliation', "yield_reconciliation VARCHAR(20) DEFAULT 'Not Applicable'");
    ebmrbpr_add_col($conn, 'ebmrbpr_config_stage_step', 'equipment_point', "equipment_point VARCHAR(20) DEFAULT 'Not Applicable'");
    ebmrbpr_add_col($conn, 'ebmrbpr_inprocess_check', 'responsibility', "responsibility VARCHAR(40) DEFAULT 'Production'");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_step', 'ipqc_testing', "ipqc_testing VARCHAR(10) DEFAULT 'No'");
    ebmrbpr_add_col($conn, 'ebmrbpr_batch_step', 'sampling_by', "sampling_by VARCHAR(20) DEFAULT 'Production'");
    @ebmrbpr_add_col($conn, 'technical_info', 'ebmr_batch_id', "ebmr_batch_id INT DEFAULT 0");
    @ebmrbpr_add_col($conn, 'technical_info', 'ebmr_sampling_id', "ebmr_sampling_id INT DEFAULT 0");

    /* ---------- master step/stage digital-signature workflow ---------- */
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'workflow_status', "workflow_status VARCHAR(40) DEFAULT 'Draft'");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'prepared_json', "prepared_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'checked_json', "checked_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'reviewed_json', "reviewed_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'approved_json', "approved_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'correction_remark', "correction_remark TEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'sent_back_by', "sent_back_by VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'sent_back_at', "sent_back_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_step', 'sent_back_from_role', "sent_back_from_role VARCHAR(40) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_stage', 'frozen', "frozen TINYINT(1) DEFAULT 0");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_stage', 'frozen_at', "frozen_at DATETIME DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_stage', 'frozen_by', "frozen_by VARCHAR(60) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_profile_stage', 'freeze_sign_json', "freeze_sign_json LONGTEXT");
    ebmrbpr_add_col($conn, 'ebmrbpr_esign', 'signature_token', "signature_token VARCHAR(64) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_esign', 'auth_method', "auth_method VARCHAR(80) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_esign', 'auth_type', "auth_type VARCHAR(40) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_esign', 'department', "department VARCHAR(120) DEFAULT NULL");
    ebmrbpr_add_col($conn, 'ebmrbpr_esign', 'designation', "designation VARCHAR(120) DEFAULT NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_master_step_workflow_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        profile_id INT NOT NULL,
        profile_stage_id INT DEFAULT 0,
        profile_step_id INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        step_name VARCHAR(255) DEFAULT NULL,
        action VARCHAR(60) DEFAULT NULL,
        from_status VARCHAR(40) DEFAULT NULL,
        to_status VARCHAR(40) DEFAULT NULL,
        role_label VARCHAR(40) DEFAULT NULL,
        remark TEXT,
        emp_id VARCHAR(60) DEFAULT NULL,
        emp_name VARCHAR(150) DEFAULT NULL,
        signature_token VARCHAR(64) DEFAULT NULL,
        esign_id INT DEFAULT 0,
        entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_mswl_profile (profile_id),
        INDEX idx_mswl_step (profile_step_id),
        INDEX idx_mswl_action (action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS ebmrbpr_sampling (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(30) DEFAULT NULL,
        batch_id INT NOT NULL,
        batch_step_id INT DEFAULT 0,
        stage_seq INT DEFAULT 0,
        stage_name VARCHAR(255) DEFAULT NULL,
        step_seq INT DEFAULT 0,
        step_name VARCHAR(255) DEFAULT NULL,
        product_code VARCHAR(60) DEFAULT NULL,
        batch_no VARCHAR(80) DEFAULT NULL,
        sampling_by VARCHAR(20) DEFAULT 'Production',
        sample_qty VARCHAR(60) DEFAULT NULL,
        unit VARCHAR(40) DEFAULT NULL,
        sample_id VARCHAR(80) DEFAULT NULL,
        equipment_code VARCHAR(80) DEFAULT NULL,
        technical_info_id INT DEFAULT 0,
        ar_no VARCHAR(80) DEFAULT NULL,
        spec_json LONGTEXT,
        test_results_json LONGTEXT,
        status VARCHAR(40) DEFAULT 'pending_ipqa',
        ipqa_notified TINYINT(1) DEFAULT 0,
        qc_notified TINYINT(1) DEFAULT 0,
        ipqa_accepted_by VARCHAR(60) DEFAULT NULL,
        ipqa_accepted_at DATETIME DEFAULT NULL,
        form_json LONGTEXT,
        raised_by VARCHAR(60) DEFAULT NULL,
        raised_at DATETIME DEFAULT NULL,
        INDEX idx_samp_batch (batch_id),
        INDEX idx_samp_step (batch_step_id),
        INDEX idx_samp_status (status),
        INDEX idx_samp_ti (technical_info_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @ebmrbpr_add_col($conn, 'ebmrbpr_sampling', 'sample_by', "sample_by VARCHAR(150) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'ebmrbpr_sampling', 'sampled_at', "sampled_at DATETIME DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'technical_info', 'sample_by', "sample_by VARCHAR(150) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'technical_info', 'ebmr_batch_id', "ebmr_batch_id INT DEFAULT 0");
    @ebmrbpr_add_col($conn, 'technical_info', 'ebmr_sampling_id', "ebmr_sampling_id INT DEFAULT 0");
}

/* ============================================================
   SMALL HELPERS
============================================================ */
function ebmrbpr_in($input, $key, $default = '')
{
    return isset($input[$key]) ? $input[$key] : $default;
}
function ebmrbpr_esc($conn, $val)
{
    return $conn->real_escape_string($val === null ? '' : (string)$val);
}
function ebmrbpr_next_code($conn, $table, $col, $prefix)
{
    $res = $conn->query("SELECT $col AS c FROM $table WHERE $col LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
    $n = 1;
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['c'];
        $n = (int)preg_replace('/[^0-9]/', '', $last) + 1;
    }
    return $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
}
function ebmrbpr_ok($extra = array())
{
    echo json_encode(array_merge(array('status' => 'success'), $extra));
}
function ebmrbpr_err($conn)
{
    echo json_encode(array('status' => 'error', 'message' => $conn->error));
}

function ebmrbpr_emp_on_stage_row($emp_id, $row, &$roles_out)
{
    $map = array(
        'operator' => 'operator_emp',
        'office' => 'office_emp',
        'alt_operator' => 'alt_operator_emp',
        'alt_officer' => 'alt_officer_emp',
        'reviewer' => 'reviewer_emp',
        'approver' => 'approver_emp',
    );
    $roles = array();
    foreach ($map as $role => $col) {
        if ((string)($row[$col] ?? '') === (string)$emp_id) {
            $roles[] = $role;
        }
    }
    $roles_out = $roles;
    return count($roles) > 0;
}

function ebmrbpr_user_full_batch_access($conn, $emp_id, $department)
{
    $dept = strtolower(trim((string)$department));
    // QA / QC / IPQA can always view full batch
    if (in_array($dept, array('quality assurance', 'qa', 'ipqa', 'quality control', 'qc'), true)) {
        return true;
    }
    if ($emp_id === '') {
        return false;
    }
    $eid = ebmrbpr_esc($conn, $emp_id);
    // Only heads / approvers get full access — stage operators see allocated stages only
    $res = @$conn->query(
        "SELECT dept_head, isapprover, plant_head, quality_head
         FROM emp_rights WHERE emp_id='$eid' LIMIT 1"
    );
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        foreach (array('dept_head', 'isapprover', 'plant_head', 'quality_head') as $c) {
            $v = strtolower(trim((string) ($r[$c] ?? '')));
            if ($v === 'yes' || $v === '1' || $v === 'y' || $v === 'true') {
                return true;
            }
        }
    }
    return false;
}

function ebmrbpr_batch_stage_access($conn, $batch, $emp_id, $department)
{
    $default = array(
        'mode' => 'full',
        'visible_stage_seqs' => array(),
        'editable_stage_seqs' => array(),
        'read_only_stage_seqs' => array(),
        'allocated_roles' => array(),
        'primary_stage_seq' => 0,
    );
    $wo_id = (int)($batch['work_order_id'] ?? 0);
    if ($wo_id <= 0 || $emp_id === '') {
        return $default;
    }
    if (ebmrbpr_user_full_batch_access($conn, $emp_id, $department)) {
        return $default;
    }
    $hdr = $conn->query("SELECT status FROM ebmrbpr_work_alloc_header WHERE work_order_id=$wo_id LIMIT 1");
    $hdrSt = ($hdr && $hdr->num_rows > 0) ? ($hdr->fetch_assoc()['status'] ?? '') : '';
    if ($hdrSt !== 'Allocated') {
        return $default;
    }
    $alloc = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_batch_work_alloc WHERE work_order_id=$wo_id ORDER BY stage_seq ASC, id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $alloc[] = $r;
        }
    }
    if (count($alloc) === 0) {
        return $default;
    }
    $assigned = array();
    foreach ($alloc as $row) {
        $roles = array();
        if (ebmrbpr_emp_on_stage_row($emp_id, $row, $roles)) {
            $seq = (int)$row['stage_seq'];
            if (!isset($assigned[$seq])) {
                $assigned[$seq] = array();
            }
            $assigned[$seq] = array_values(array_unique(array_merge($assigned[$seq], $roles)));
        }
    }
    if (count($assigned) === 0) {
        // Allocated but current user not on any stage.
        // If no personnel was filled on the WO at all (seed / open shell), allow full access.
        $anyPersonnel = false;
        foreach ($alloc as $row) {
            foreach (array('operator_emp', 'office_emp', 'alt_operator_emp', 'alt_officer_emp', 'reviewer_emp', 'approver_emp') as $col) {
                if (trim((string)($row[$col] ?? '')) !== '') {
                    $anyPersonnel = true;
                    break 2;
                }
            }
        }
        if (!$anyPersonnel) {
            return $default;
        }
        // User not on any stage — deny (do not grant full Production access)
        return array_merge($default, array('mode' => 'denied'));
    }
    $allSeqs = array();
    foreach ($alloc as $row) {
        $allSeqs[] = (int)$row['stage_seq'];
    }
    if (!empty($batch['steps']) && is_array($batch['steps'])) {
        foreach ($batch['steps'] as $st) {
            $allSeqs[] = (int)($st['stage_seq'] ?? 0);
        }
    }
    $allSeqs = array_values(array_unique(array_filter($allSeqs, function ($v) { return $v > 0; })));
    sort($allSeqs, SORT_NUMERIC);
    $visible = array();
    $editable = array();
    foreach (array_keys($assigned) as $seq) {
        $seq = (int)$seq;
        $editable[] = $seq;
        $idx = array_search($seq, $allSeqs, true);
        if ($idx !== false && $idx > 0) {
            $visible[] = $allSeqs[$idx - 1];
        }
        $visible[] = $seq;
    }
    $visible = array_values(array_unique($visible));
    sort($visible, SORT_NUMERIC);
    $editable = array_values(array_unique($editable));
    sort($editable, SORT_NUMERIC);
    $readOnly = array_values(array_diff($visible, $editable));
    return array(
        'mode' => 'restricted',
        'visible_stage_seqs' => $visible,
        'editable_stage_seqs' => $editable,
        'read_only_stage_seqs' => $readOnly,
        'allocated_roles' => $assigned,
        'primary_stage_seq' => count($editable) ? min($editable) : 0,
    );
}

function ebmrbpr_filter_steps_by_access($steps, $access)
{
    if (!is_array($steps) || ($access['mode'] ?? 'full') !== 'restricted') {
        return $steps;
    }
    $vis = array_flip($access['visible_stage_seqs'] ?? array());
    return array_values(array_filter($steps, function ($s) use ($vis) {
        return isset($vis[(int)($s['stage_seq'] ?? 0)]);
    }));
}

function ebmrbpr_step_stage_seq($conn, $step_id)
{
    $res = $conn->query("SELECT stage_seq, batch_id FROM ebmrbpr_batch_step WHERE id=" . (int)$step_id . " LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return null;
    }
    return $res->fetch_assoc();
}

function ebmrbpr_assert_step_mutable($conn, $step_id, $access, $require_editable = true)
{
    if (($access['mode'] ?? 'full') !== 'restricted') {
        return true;
    }
    $row = ebmrbpr_step_stage_seq($conn, $step_id);
    if (!$row) {
        return false;
    }
    $seq = (int)$row['stage_seq'];
    $allowed = $require_editable
        ? ($access['editable_stage_seqs'] ?? array())
        : ($access['visible_stage_seqs'] ?? array());
    return in_array($seq, $allowed, true);
}

/** Reject mutations when batch is on hold / released / cancelled / deleted. */
function ebmrbpr_batch_mutable_message($conn, $batch_id)
{
    $batch_id = (int)$batch_id;
    if ($batch_id <= 0) {
        return 'Invalid batch';
    }
    $res = $conn->query("SELECT status FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return 'Batch not found';
    }
    $st = trim((string)$res->fetch_assoc()['status']);
    $blocked = array('On Hold', 'Cancelled', 'Deleted', 'Released', 'Released for Packing');
    if (in_array($st, $blocked, true)) {
        return 'Batch is ' . $st . ' — edits are not allowed';
    }
    return '';
}

/** Verify fresh e-sign token belongs to session user (max 15 min). Empty string = ok. */
function ebmrbpr_require_valid_esign($conn, $input, $emp_id)
{
    $token = trim((string)ebmrbpr_in($input, 'signature_token', ''));
    $esignId = (int)ebmrbpr_in($input, 'esign_id', 0);
    if ($token === '' && $esignId <= 0) {
        return 'Electronic signature required';
    }
    if ($esignId > 0) {
        $q = @$conn->query("SELECT emp_id, signed_at, signature_token FROM ebmrbpr_esign WHERE id=$esignId LIMIT 1");
    } else {
        $q = @$conn->query("SELECT emp_id, signed_at, signature_token FROM ebmrbpr_esign WHERE signature_token='" . ebmrbpr_esc($conn, $token) . "' LIMIT 1");
    }
    if (!$q || $q->num_rows === 0) {
        return 'Invalid or expired electronic signature — please sign again';
    }
    $row = $q->fetch_assoc();
    if (strcasecmp(trim((string)$row['emp_id']), trim((string)$emp_id)) !== 0) {
        return 'Signature does not match the signed-in user';
    }
    $signed = strtotime((string)($row['signed_at'] ?? ''));
    if ($signed && (time() - $signed) > 900) {
        return 'Electronic signature expired — please sign again';
    }
    return '';
}

/**
 * Server-side completeness checks before completing a step.
 * $dataArr = decoded step data; $template = decoded template.
 */
function ebmrbpr_validate_step_for_complete($template, $dataArr)
{
    if (!is_array($template)) {
        $template = array();
    }
    if (!is_array($dataArr)) {
        $dataArr = array();
    }
    $lc = isset($template['line_clearance']) && is_array($template['line_clearance']) ? $template['line_clearance'] : array();
    if (count($lc) > 0) {
        $so = isset($dataArr['lc_signoff']) && is_array($dataArr['lc_signoff']) ? $dataArr['lc_signoff'] : array();
        if (empty($so['prod_by']) || empty($so['qa_by'])) {
            return 'Line clearance must be signed by both Production and IPQA before completing this step';
        }
        foreach ($lc as $cp) {
            if (!is_array($cp)) {
                continue;
            }
            $id = (string)($cp['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $e = isset($dataArr['line_clearance'][$id]) ? $dataArr['line_clearance'][$id] : null;
            if (!is_array($e) || trim((string)($e['response'] ?? '')) === '') {
                return 'Record a response for every line-clearance checkpoint before completing';
            }
        }
    }
    $ts = isset($template['step_timestamp']) && is_array($template['step_timestamp']) ? $template['step_timestamp'] : array();
    if (!empty($ts['enabled'])) {
        $d = isset($dataArr['step_timestamp']) && is_array($dataArr['step_timestamp']) ? $dataArr['step_timestamp'] : array();
        if (trim((string)($d['date'] ?? '')) === '' || trim((string)($d['start_time'] ?? '')) === '' || trim((string)($d['end_time'] ?? '')) === '') {
            return 'Capture Date, Start Time and End Time for this step before completing';
        }
        $subs = isset($ts['substeps']) && is_array($ts['substeps']) ? $ts['substeps'] : array();
        foreach ($subs as $i => $ss) {
            if (!is_array($ss) || trim((string)($ss['name'] ?? '')) === '') {
                continue;
            }
            $key = (string)($ss['id'] ?? $i);
            $e = isset($d['substeps'][$key]) && is_array($d['substeps'][$key]) ? $d['substeps'][$key] : array();
            if (trim((string)($e['date'] ?? '')) === '' || trim((string)($e['start_time'] ?? '')) === '' || trim((string)($e['end_time'] ?? '')) === '') {
                return 'Capture Date, Start Time and End Time for sub-step: ' . ($ss['name'] ?? $key);
            }
        }
    }
    $dept = isset($template['dept_checks']) && is_array($template['dept_checks']) ? $template['dept_checks'] : array();
    foreach ($dept as $cp) {
        if (!is_array($cp)) {
            continue;
        }
        $id = (string)($cp['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $e = isset($dataArr['dept_checks'][$id]) ? $dataArr['dept_checks'][$id] : null;
        if (!is_array($e) || trim((string)($e['response'] ?? '')) === '') {
            return 'All department checkpoints must be answered before completing this step';
        }
    }
    $qa = isset($template['qa_checks']) && is_array($template['qa_checks']) ? $template['qa_checks'] : array();
    foreach ($qa as $cp) {
        if (!is_array($cp)) {
            continue;
        }
        $id = (string)($cp['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $e = isset($dataArr['qa_checks'][$id]) ? $dataArr['qa_checks'][$id] : null;
        if (!is_array($e) || trim((string)($e['response'] ?? '')) === '') {
            return 'All QA checkpoints must be answered before completing this step';
        }
    }
    $ipc = isset($template['inprocess_checks']) && is_array($template['inprocess_checks']) ? $template['inprocess_checks'] : array();
    foreach ($ipc as $c) {
        if (!is_array($c)) {
            continue;
        }
        $id = (string)($c['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $bucket = isset($dataArr['inprocess'][$id]) ? $dataArr['inprocess'][$id] : null;
        $has = false;
        if (is_array($bucket)) {
            if (!empty($bucket['entries']) && is_array($bucket['entries'])) {
                foreach ($bucket['entries'] as $ent) {
                    if (is_array($ent) && trim((string)($ent['value'] ?? '')) !== '') {
                        $has = true;
                        break;
                    }
                }
            } elseif (trim((string)($bucket['value'] ?? '')) !== '') {
                $has = true;
            }
        }
        if (!$has) {
            return 'Record at least one observation for in-process check: ' . ($c['check_name'] ?? $id);
        }
    }
    return '';
}

function ebmrbpr_batch_access_from_id($conn, $batch_id, $emp_id, $department)
{
    $res = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=" . (int)$batch_id . " LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return array('mode' => 'denied');
    }
    $batch = $res->fetch_assoc();
    $steps = array();
    $sres = $conn->query("SELECT id, stage_seq FROM ebmrbpr_batch_step WHERE batch_id=" . (int)$batch_id . " ORDER BY stage_seq ASC");
    if ($sres) {
        while ($s = $sres->fetch_assoc()) {
            $steps[] = $s;
        }
    }
    $batch['steps'] = $steps;
    return ebmrbpr_batch_stage_access($conn, $batch, $emp_id, $department);
}

function ebmrbpr_wo_dispensing_received($wo)
{
    if (!is_array($wo)) {
        return false;
    }
    $by = trim((string)($wo['rm_received_by'] ?? ''));
    if ($by !== '' && $by !== '0') {
        return true;
    }
    $st = strtolower(trim((string)($wo['rm_receiving_status'] ?? '')));
    return in_array($st, array('discpensing_completed', 'dispensing_completed', 'received', 'completed'), true);
}

function ebmrbpr_dispensing_pipeline_status($wo)
{
    if (!is_array($wo)) {
        return 'Unknown';
    }
    if (ebmrbpr_wo_dispensing_received($wo)) {
        return 'RM Received in Production';
    }
    $dispSt = trim((string)($wo['dispensing_status'] ?? ''));
    $storeDone = trim((string)($wo['rm_disp_completed_by'] ?? ''));
    if ($dispSt === 'Request Sent') {
        if ($storeDone !== '' && $storeDone !== '0') {
            return 'Store Dispensing Done — Awaiting Production Receipt';
        }
        return 'Requisition Sent — Store Dispensing';
    }
    if (!empty($wo['batch_number']) && !empty($wo['qa_person'])) {
        return 'Awaiting Dispensing Requisition';
    }
    return 'Not Ready';
}

function ebmrbpr_fetch_store_dispensing_rows($conn, $work_order_id, $plant_id)
{
    $output = array();
    $wo_id = (int)$work_order_id;
    if ($wo_id <= 0) {
        return $output;
    }
    // dispensing_details_hdr real columns: ars, containers, net_total/gross_total, done_by, entry_by
    // Prefer minimal columns that exist in production; never block getBatch on schema drift.
    $sql = "SELECT wd.id AS dtl_id, wd.material_code, wd.batch_qty, wd.unit, wd.stage, wd.total_batch_qty,
            m.material_name, m.material_type, m.material_subtype,
            dd.id AS dispense_id, dd.ars AS ar_nos, dd.containers,
            COALESCE(dd.net_total, dd.gross_total) AS dispensed_qty,
            dd.done_by AS checked_by,
            COALESCE(dd.done_by, dd.entry_by) AS dispensing_by,
            NULL AS dispensing_date,
            dd.qa_status, dd.prod_status, dd.prod_checking, dd.qa_checking,
            wbl.dispense_recd_status, wbl.received_by, wbl.received_on
            FROM mfg_work_order_dtl wd
            JOIN mfg_work_order_hdr h ON h.id = wd.work_order_id
            LEFT JOIN material m ON m.material_code = wd.material_code AND m.plant_id = h.plant_id
            LEFT JOIN dispensing_details_hdr dd ON dd.dtl_id = wd.id
            LEFT JOIN work_order_batch_lots wbl ON wbl.work_order_id = h.id AND wbl.material_code = wd.material_code
            WHERE wd.work_order_id = $wo_id AND h.plant_id = '" . ebmrbpr_esc($conn, $plant_id) . "'
            ORDER BY wd.id ASC";
    try {
        $res = @$conn->query($sql);
        if (!$res) {
            // Fallback without work_order_batch_lots extras
            $sql2 = "SELECT wd.id AS dtl_id, wd.material_code, wd.batch_qty, wd.unit, wd.stage, wd.total_batch_qty,
                m.material_name, m.material_type, m.material_subtype,
                dd.id AS dispense_id, dd.ars AS ar_nos, dd.containers,
                COALESCE(dd.net_total, dd.gross_total) AS dispensed_qty,
                dd.done_by AS checked_by,
                COALESCE(dd.done_by, dd.entry_by) AS dispensing_by,
                NULL AS dispensing_date,
                dd.qa_status, dd.prod_status, dd.prod_checking, dd.qa_checking,
                NULL AS dispense_recd_status, NULL AS received_by, NULL AS received_on
                FROM mfg_work_order_dtl wd
                JOIN mfg_work_order_hdr h ON h.id = wd.work_order_id
                LEFT JOIN material m ON m.material_code = wd.material_code AND m.plant_id = h.plant_id
                LEFT JOIN dispensing_details_hdr dd ON dd.dtl_id = wd.id
                WHERE wd.work_order_id = $wo_id AND h.plant_id = '" . ebmrbpr_esc($conn, $plant_id) . "'
                ORDER BY wd.id ASC";
            $res = @$conn->query($sql2);
        }
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['containers']) && is_string($row['containers'])) {
                    $decoded = json_decode($row['containers'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row['containers'] = $decoded;
                    }
                }
                if (!empty($row['ar_nos']) && is_string($row['ar_nos'])) {
                    $decoded = json_decode($row['ar_nos'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row['ar_nos'] = $decoded;
                    }
                }
                $output[] = $row;
            }
        }
    } catch (Throwable $e) {
        // Do not block batch open if store dispensing join fails
        return $output;
    }
    return $output;
}

function ebmrbpr_merge_store_dispensing_into_static(&$static, $storeRows)
{
    if (!is_array($static)) {
        $static = array();
    }
    if (!isset($static['dispensing']) || !is_array($static['dispensing'])) {
        $static['dispensing'] = array('sections' => array(), 'materials' => array());
    }
    $disp = &$static['dispensing'];
    $disp['store_records'] = $storeRows;
    if (!isset($disp['materials']) || !is_array($disp['materials'])) {
        $disp['materials'] = array();
    }
    $byCode = array();
    foreach ($disp['materials'] as $idx => $m) {
        if (!is_array($m)) {
            continue;
        }
        $code = trim((string)($m['material_code'] ?? ''));
        if ($code !== '') {
            $byCode[$code] = $idx;
        }
    }
    foreach ($storeRows as $sr) {
        $code = trim((string)($sr['material_code'] ?? ''));
        $dispQty = trim((string)($sr['dispensed_qty'] ?? $sr['batch_qty'] ?? ''));
        $entry = array(
            'material' => trim((string)($sr['material_name'] ?? $code)),
            'material_code' => $code,
            'qty' => $dispQty,
            'uom' => trim((string)($sr['unit'] ?? '')),
            'stage' => trim((string)($sr['stage'] ?? 'Dispensing in Production')),
            'dispensed_qty' => $dispQty,
            'ar_nos' => $sr['ar_nos'] ?? array(),
            'containers' => $sr['containers'] ?? array(),
            'dispensing_by' => trim((string)($sr['dispensing_by'] ?? '')),
            'dispensing_date' => trim((string)($sr['dispensing_date'] ?? '')),
            'received_by' => trim((string)($sr['received_by'] ?? '')),
            'received_on' => trim((string)($sr['received_on'] ?? '')),
            'store_status' => !empty($sr['checked_by']) ? 'Dispensed' : 'Pending',
        );
        if ($code !== '' && isset($byCode[$code])) {
            $disp['materials'][$byCode[$code]] = array_merge($disp['materials'][$byCode[$code]], $entry);
        } else {
            $disp['materials'][] = $entry;
            if ($code !== '') {
                $byCode[$code] = count($disp['materials']) - 1;
            }
        }
    }
}

function ebmrbpr_default_stage_esign()
{
    // Every manufacturing stage requires digital signatures:
    // Doer (send for checking) → Checker → Final Approver
    return array(
        'checking_required' => true,
        'approval_required' => true,
        'qa_check_required' => false,
        'qa_approval_required' => false,
        'line_clearance_ipqa' => true,
        'doer_required' => true,
    );
}

function ebmrbpr_init_batch_stages($conn, $batch_id, $profile_id)
{
    $batch_id = (int)$batch_id;
    $profile_id = (int)$profile_id;
    if ($batch_id <= 0 || $profile_id <= 0) {
        return;
    }
    $conn->query("DELETE FROM ebmrbpr_batch_stage WHERE batch_id=$batch_id");
    $cfgWf = array();
    $pres = $conn->query("SELECT config_id FROM ebmrbpr_profile WHERE id=$profile_id LIMIT 1");
    if ($pres && $pres->num_rows > 0) {
        $cfgId = (int)$pres->fetch_assoc()['config_id'];
        if ($cfgId > 0) {
            $cres = $conn->query("SELECT matrix_json FROM ebmrbpr_config WHERE id=$cfgId LIMIT 1");
            if ($cres && $cres->num_rows > 0) {
                $mx = json_decode($cres->fetch_assoc()['matrix_json'], true);
                if (is_array($mx) && isset($mx['workflow']) && is_array($mx['workflow'])) {
                    $cfgWf = $mx['workflow'];
                }
            }
        }
    }
    $sres = $conn->query("SELECT * FROM ebmrbpr_profile_stage WHERE profile_id=$profile_id ORDER BY seq_no ASC, id ASC");
    if (!$sres) {
        return;
    }
    while ($stage = $sres->fetch_assoc()) {
        $cfg = json_decode($stage['config_json'], true);
        if (!is_array($cfg)) {
            $cfg = array();
        }
        $esign = isset($cfg['esign']) && is_array($cfg['esign']) ? $cfg['esign'] : ebmrbpr_default_stage_esign();
        // Hard rule: every stage needs Doer + Checker + Final Approver e-sign
        $checking = 1;
        $approval = 1;
        $qaCheck = isset($esign['qa_check_required']) ? (int)(!!$esign['qa_check_required']) : 0;
        $qaAppr = isset($esign['qa_approval_required']) ? (int)(!!$esign['qa_approval_required']) : 0;
        $lcIpqa = isset($esign['line_clearance_ipqa']) ? (int)(!!$esign['line_clearance_ipqa']) : 1;
        // Config matrix may enable QA legs, but cannot disable Prod checking/approval
        if (isset($cfgWf['qa_check_applicable'])) {
            $qaCheck = (int)(!!$cfgWf['qa_check_applicable']);
        }
        if (isset($cfgWf['qa_approval_applicable'])) {
            $qaAppr = (int)(!!$cfgWf['qa_approval_applicable']);
        }
        $esign['checking_required'] = true;
        $esign['approval_required'] = true;
        $esign['doer_required'] = true;
        $cfg['esign'] = $esign;
        $seq = (int)$stage['seq_no'];
        $name = ebmrbpr_esc($conn, $stage['stage_name']);
        $conn->query("INSERT INTO ebmrbpr_batch_stage
            (batch_id, stage_seq, stage_name, config_json, status, checking_required, approval_required,
             qa_check_required, qa_approval_required, line_clearance_ipqa)
            VALUES ($batch_id, $seq, '$name', '" . ebmrbpr_esc($conn, json_encode($cfg)) . "',
            'In Progress', $checking, $approval, $qaCheck, $qaAppr, $lcIpqa)");
    }
}

function ebmrbpr_get_batch_stages($conn, $batch_id)
{
    $output = array();
    $res = $conn->query("SELECT * FROM ebmrbpr_batch_stage WHERE batch_id=" . (int)$batch_id . " ORDER BY stage_seq ASC, id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['config'] = json_decode($row['config_json'], true);
            $output[] = $row;
        }
    }
    return $output;
}

/** Parse product master shelf life text to months (e.g. "24 Months", "2 Years", "36"). */
function ebmrbpr_shelf_life_months($shelf_life)
{
    $s = trim(strtolower((string)$shelf_life));
    if ($s === '') {
        return 0;
    }
    if (!preg_match('/(\d+(?:\.\d+)?)/', $s, $m)) {
        return 0;
    }
    $n = (float)$m[1];
    if (strpos($s, 'year') !== false) {
        return (int)round($n * 12);
    }
    return (int)round($n);
}

/** Expiry = mfg date + shelf life (months) from product master. */
function ebmrbpr_calc_exp_date($mfg_date, $shelf_life)
{
    $months = ebmrbpr_shelf_life_months($shelf_life);
    $mfg = trim(substr((string)$mfg_date, 0, 10));
    if ($months <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $mfg)) {
        return '';
    }
    try {
        $dt = new DateTime($mfg);
        $dt->modify('+' . $months . ' months');
        return $dt->format('Y-m-d');
    } catch (Exception $e) {
        return '';
    }
}

/** Mfg date = date of RM dispensing (store dispensing date or WO dispensing completion). */
function ebmrbpr_resolve_dispensing_mfg_date($conn, $batch, $storeRows = null)
{
    $wo_id = (int)($batch['work_order_id'] ?? 0);
    $plant_id = trim((string)($batch['plant_id'] ?? ''));
    $dispDates = array();

    if ($storeRows === null && $wo_id > 0) {
        $storeRows = ebmrbpr_fetch_store_dispensing_rows($conn, $wo_id, $plant_id);
    }
    if (is_array($storeRows)) {
        foreach ($storeRows as $sr) {
            $d = trim(substr((string)($sr['dispensing_date'] ?? ''), 0, 10));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                $dispDates[] = $d;
            }
        }
    }
    if (count($dispDates) > 0) {
        sort($dispDates);
        return end($dispDates);
    }

    if ($wo_id > 0) {
        $wo = $conn->query("SELECT rm_disp_completed_date, rm_received_date FROM mfg_work_order_hdr WHERE id=$wo_id LIMIT 1");
        if ($wo && $wo->num_rows > 0) {
            $w = $wo->fetch_assoc();
            foreach (array('rm_disp_completed_date', 'rm_received_date') as $k) {
                $d = trim(substr((string)($w[$k] ?? ''), 0, 10));
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    return $d;
                }
            }
        }
    }
    return '';
}

/** Resolve shelf life from product master or batch header snapshot. */
function ebmrbpr_batch_shelf_life($conn, $batch)
{
    $header = isset($batch['header']) && is_array($batch['header']) ? $batch['header'] : array();
    if (!empty($header['shelf_life'])) {
        return trim((string)$header['shelf_life']);
    }
    $code = trim((string)($batch['product_code'] ?? ''));
    if ($code === '') {
        return '';
    }
    $prod = ebmrbpr_fetch_product_for_bmr($conn, $code);
    return $prod && !empty($prod['shelf_life']) ? trim((string)$prod['shelf_life']) : '';
}

/** Compute mfg/exp from dispensing + product shelf life; persist when changed. */
function ebmrbpr_sync_batch_dates($conn, &$batch, $storeRows = null)
{
    $batch_id = (int)($batch['id'] ?? 0);
    $mfg = ebmrbpr_resolve_dispensing_mfg_date($conn, $batch, $storeRows);
    if ($mfg === '') {
        $existing = trim(substr((string)($batch['mfg_date'] ?? ''), 0, 10));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $existing)) {
            $mfg = $existing;
        }
    }
    $shelf = ebmrbpr_batch_shelf_life($conn, $batch);
    $exp = $mfg !== '' ? ebmrbpr_calc_exp_date($mfg, $shelf) : '';
    if ($exp === '') {
        $existingExp = trim(substr((string)($batch['exp_date'] ?? ''), 0, 10));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $existingExp)) {
            $exp = $existingExp;
        }
    }

    $batch['shelf_life'] = $shelf;
    $batch['mfg_date'] = $mfg !== '' ? $mfg : ($batch['mfg_date'] ?? null);
    $batch['exp_date'] = $exp !== '' ? $exp : ($batch['exp_date'] ?? null);

    if ($batch_id > 0) {
        $curMfg = trim(substr((string)($batch['mfg_date'] ?? ''), 0, 10));
        $curExp = trim(substr((string)($batch['exp_date'] ?? ''), 0, 10));
        $mfgSql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $curMfg) ? "'" . ebmrbpr_esc($conn, $curMfg) . "'" : 'NULL';
        $expSql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $curExp) ? "'" . ebmrbpr_esc($conn, $curExp) . "'" : 'NULL';
        $conn->query("UPDATE ebmrbpr_batch SET mfg_date=$mfgSql, exp_date=$expSql WHERE id=$batch_id");
    }

    if (isset($batch['header']) && is_array($batch['header']) && $shelf !== '') {
        $batch['header']['shelf_life'] = $shelf;
    }
    if (isset($batch['static']['product_approval']) && is_array($batch['static']['product_approval'])) {
        if ($shelf !== '') {
            $batch['static']['product_approval']['shelf_life'] = $shelf;
        }
        if ($mfg !== '') {
            $batch['static']['product_approval']['mfg_date'] = $mfg;
        }
        if ($exp !== '') {
            $batch['static']['product_approval']['expiry_date'] = $exp;
        }
    }
    return $batch;
}

function ebmrbpr_batch_stage_by_seq($conn, $batch_id, $stage_seq)
{
    $res = $conn->query("SELECT * FROM ebmrbpr_batch_stage WHERE batch_id=" . (int)$batch_id . " AND stage_seq=" . (int)$stage_seq . " LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $row['config'] = json_decode($row['config_json'], true);
        return $row;
    }
    return null;
}

/** Config-level stage/step counts from ebmrbpr_config_stage_step (excludes per-product rows). */
function ebmrbpr_config_stage_counts($conn, $configId)
{
    $out = array('stage_count' => 0, 'step_count' => 0);
    $res = $conn->query("SELECT COUNT(DISTINCT stage_name) AS sc,
        SUM(CASE WHEN TRIM(IFNULL(step_name,'')) <> '' THEN 1 ELSE 0 END) AS stc
        FROM ebmrbpr_config_stage_step WHERE config_id=" . (int)$configId . "
        AND (product_code IS NULL OR product_code='')");
    if ($res && $res->num_rows) {
        $r = $res->fetch_assoc();
        $out['stage_count'] = (int)$r['sc'];
        $out['step_count'] = (int)$r['stc'];
    }
    return $out;
}

/** Seed profile stages/steps from Configure Stage & Step rows; falls back to stage_map_json. Returns stage count seeded. */
function ebmrbpr_seed_profile_stages($conn, $profileId, $configId, $stageMapJson)
{
    $groups = array();
    $res = $conn->query("SELECT stage_seq, stage_name, step_seq, step_name, ipqc_testing, sampling_by FROM ebmrbpr_config_stage_step
        WHERE config_id=" . (int)$configId . " AND (product_code IS NULL OR product_code='')
        ORDER BY stage_seq ASC, step_seq ASC, id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $sn = trim((string)$r['stage_name']);
            if ($sn === '') {
                continue;
            }
            if (!isset($groups[$sn])) {
                $groups[$sn] = array('stage_seq' => (int)$r['stage_seq'], 'steps' => array());
            }
            $stepName = trim((string)$r['step_name']);
            if ($stepName !== '') {
                $groups[$sn]['steps'][] = array(
                    'step_name' => $stepName,
                    'step_seq' => (int)$r['step_seq'],
                    'ipqc_testing' => $r['ipqc_testing'] ?? 'No',
                    'sampling_by' => $r['sampling_by'] ?? 'Production',
                );
            }
        }
    }
    if (count($groups) === 0) {
        $map = is_string($stageMapJson) ? json_decode($stageMapJson, true) : $stageMapJson;
        if (!is_array($map)) {
            return 0;
        }
        $sidx = 0;
        foreach ($map as $st) {
            $sidx++;
            $sn = isset($st['stage_name']) ? trim((string)$st['stage_name']) : '';
            if ($sn === '') {
                continue;
            }
            $groups[$sn] = array(
                'stage_seq' => (int)(isset($st['seq_no']) ? $st['seq_no'] : $sidx),
                'steps' => array(),
                'stage_id' => (int)(isset($st['stage_id']) ? $st['stage_id'] : 0),
            );
            $steps = isset($st['steps']) && is_array($st['steps']) ? $st['steps'] : array();
            $tidx = 0;
            foreach ($steps as $sp) {
                $tidx++;
                $tn = isset($sp['step_name']) ? trim((string)$sp['step_name']) : '';
                if ($tn === '') {
                    continue;
                }
                $groups[$sn]['steps'][] = array(
                    'step_name' => $tn,
                    'step_seq' => (int)(isset($sp['seq_no']) ? $sp['seq_no'] : $tidx),
                    'step_id' => (int)(isset($sp['step_id']) ? $sp['step_id'] : 0),
                );
            }
        }
    }
    if (count($groups) === 0) {
        return 0;
    }
    $sidx = 0;
    foreach ($groups as $stageName => $g) {
        $sidx++;
        $sSeq = !empty($g['stage_seq']) ? (int)$g['stage_seq'] : $sidx;
        $sMaster = isset($g['stage_id']) ? (int)$g['stage_id'] : 0;
        $sName = ebmrbpr_esc($conn, $stageName);
        $conn->query("INSERT INTO ebmrbpr_profile_stage (profile_id, stage_master_id, stage_name, seq_no, config_json)
            VALUES (" . (int)$profileId . ", $sMaster, '$sName', $sSeq, '')");
        $stageRowId = $conn->insert_id;
        $tidx = 0;
        foreach ($g['steps'] as $sp) {
            $tidx++;
            $tSeq = !empty($sp['step_seq']) ? (int)$sp['step_seq'] : $tidx;
            $tMaster = isset($sp['step_id']) ? (int)$sp['step_id'] : 0;
            $tName = ebmrbpr_esc($conn, $sp['step_name']);
            $stepCfg = array(
                'ipqc_testing' => isset($sp['ipqc_testing']) ? $sp['ipqc_testing'] : 'No',
                'sampling_by' => isset($sp['sampling_by']) ? $sp['sampling_by'] : 'Production',
            );
            $conn->query("INSERT INTO ebmrbpr_profile_step (profile_id, profile_stage_id, step_master_id, step_name, seq_no, config_json)
                VALUES (" . (int)$profileId . ", $stageRowId, $tMaster, '$tName', $tSeq,
                '" . ebmrbpr_esc($conn, json_encode($stepCfg)) . "')");
        }
    }
    return count($groups);
}

/** Parse label_claim from product master into display text. */
function ebmrbpr_label_claim_text($labelClaim)
{
    if ($labelClaim === null || $labelClaim === '') {
        return '';
    }
    if (is_string($labelClaim)) {
        $decoded = json_decode($labelClaim, true);
        if (is_array($decoded)) {
            if (!empty($decoded['strength'])) {
                return (string)$decoded['strength'];
            }
            if (!empty($decoded['composition'])) {
                return (string)$decoded['composition'];
            }
            return trim(implode(' ', array_filter(array_map('strval', $decoded))));
        }
        return trim($labelClaim);
    }
    if (is_array($labelClaim)) {
        return trim(implode(' ', array_filter(array_map('strval', $labelClaim))));
    }
    return (string)$labelClaim;
}

/** Fetch product master row + MFR no for BMR draft header. */
function ebmrbpr_fetch_product_for_bmr($conn, $productCode)
{
    $code = ebmrbpr_esc($conn, trim((string)$productCode));
    if ($code === '') {
        return null;
    }
    $res = @$conn->query("SELECT p.product_code, p.product_name, p.generic_name, p.grade, p.dosage_form,
        p.manufactured_under, p.manufactured_for, p.market_type, p.label_claim, p.shelf_life,
        p.storage_condition, p.mfg_lic, p.pack_desc, p.pack_sizes, p.thera, p.unit, p.process_type
        FROM product p WHERE p.product_code='$code' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return null;
    }
    $row = $res->fetch_assoc();
    $mfrNo = '';
    $mfr = @$conn->query("SELECT mfr_no FROM unitformula WHERE product_code='$code' ORDER BY id DESC LIMIT 1");
    if ($mfr && $mfr->num_rows) {
        $mfrNo = (string)$mfr->fetch_assoc()['mfr_no'];
    }
    $row['mfr_no'] = $mfrNo;
    $mu = strtolower(trim((string)(isset($row['manufactured_under']) ? $row['manufactured_under'] : '')));
    if ($mu === 'client' || trim((string)(isset($row['manufactured_for']) ? $row['manufactured_for'] : '')) !== '') {
        $row['product_for'] = 'Client Product';
        $cn = @$conn->query("SELECT LglNm FROM client WHERE client_code='" . ebmrbpr_esc($conn, $row['manufactured_for']) . "' LIMIT 1");
        $row['client_name'] = ($cn && $cn->num_rows) ? $cn->fetch_assoc()['LglNm'] : '';
    } else {
        $row['product_for'] = 'Own Product';
        $row['client_name'] = '';
    }
    $strength = trim((string)(isset($row['grade']) ? $row['grade'] : ''));
    if ($strength === '' && !empty($row['label_claim'])) {
        $lc = is_string($row['label_claim']) ? json_decode($row['label_claim'], true) : $row['label_claim'];
        if (is_array($lc) && isset($lc['strength'])) {
            $strength = (string)$lc['strength'];
        }
    }
    $row['product_strength'] = $strength;
    $row['label_claim_text'] = ebmrbpr_label_claim_text($row['label_claim']);
    $row['therapeutic_category'] = isset($row['thera']) ? trim((string)$row['thera']) : '';
    $row['market'] = isset($row['market_type']) ? trim((string)$row['market_type']) : '';
    $row['mfg_license'] = isset($row['mfg_lic']) ? trim((string)$row['mfg_lic']) : '';
    $row['pack_size'] = isset($row['pack_desc']) && trim((string)$row['pack_desc']) !== ''
        ? trim((string)$row['pack_desc'])
        : trim((string)(isset($row['pack_sizes']) ? $row['pack_sizes'] : ''));
    $row['batch_size_uom'] = isset($row['unit']) ? trim((string)$row['unit']) : '';
    return $row;
}

/** Build header_json for a new BMR draft profile. */
function ebmrbpr_build_prep_header($conn, $cfg, $bmrFor, $productCode)
{
    $header = array(
        'bmr_for' => ($bmrFor === 'Product') ? 'Product' : 'Generic',
        'bmr_no' => isset($cfg['bmr_no']) ? $cfg['bmr_no'] : '',
        'process_type' => isset($cfg['process_type']) ? $cfg['process_type'] : '',
        'product_code' => '',
        'product_name' => '',
        'product_for' => '',
        'generic_name' => '',
        'product_strength' => '',
        'mfr_no' => '',
        'client_name' => '',
    );
    if ($header['bmr_for'] === 'Product' && trim((string)$productCode) !== '') {
        $prod = ebmrbpr_fetch_product_for_bmr($conn, $productCode);
        if ($prod) {
            $header['product_code'] = $prod['product_code'];
            $header['product_name'] = $prod['product_name'];
            $header['product_for'] = $prod['product_for'];
            $header['generic_name'] = isset($prod['generic_name']) ? $prod['generic_name'] : '';
            $header['product_strength'] = isset($prod['product_strength']) ? $prod['product_strength'] : '';
            $header['mfr_no'] = isset($prod['mfr_no']) ? $prod['mfr_no'] : '';
            $header['client_name'] = isset($prod['client_name']) ? $prod['client_name'] : '';
            $header['label_claim'] = isset($prod['label_claim_text']) ? $prod['label_claim_text'] : '';
            $header['shelf_life'] = isset($prod['shelf_life']) ? $prod['shelf_life'] : '';
            $header['storage_condition'] = isset($prod['storage_condition']) ? $prod['storage_condition'] : '';
            $header['market'] = isset($prod['market']) ? $prod['market'] : '';
            $header['mfg_license'] = isset($prod['mfg_license']) ? $prod['mfg_license'] : '';
            $header['therapeutic_category'] = isset($prod['therapeutic_category']) ? $prod['therapeutic_category'] : '';
            $header['pack_size'] = isset($prod['pack_size']) ? $prod['pack_size'] : '';
            $header['batch_size_uom'] = isset($prod['batch_size_uom']) ? $prod['batch_size_uom'] : '';
        }
    }
    return $header;
}

function ebmrbpr_fetch_by_ids($conn, $table, $ids)
{
    $out = array();
    if (!is_array($ids) || count($ids) == 0) return $out;
    $clean = array();
    foreach ($ids as $x) {
        $n = (int)$x;
        if ($n > 0) $clean[] = $n;
    }
    if (count($clean) == 0) return $out;
    $in = implode(',', $clean);
    $res = $conn->query("SELECT * FROM $table WHERE id IN ($in)");
    if ($res) {
        $byId = array();
        while ($row = $res->fetch_assoc()) {
            $byId[$row['id']] = $row;
        }
        // preserve the selected order
        foreach ($clean as $id) {
            if (isset($byId[$id])) $out[] = $byId[$id];
        }
    }
    return $out;
}

/**
 * Apply builder step-level overrides (role / frequency) onto snapshotted IPC rows.
 * $setup keyed by check id: { responsibility|role, frequency }.
 */
function ebmrbpr_merge_inprocess_setup($rows, $setup)
{
    if (!is_array($rows)) return array();
    if (!is_array($setup)) $setup = array();
    foreach ($rows as &$row) {
        $id = (string)($row['id'] ?? '');
        $meta = isset($setup[$id]) && is_array($setup[$id])
            ? $setup[$id]
            : (isset($setup[(int)$id]) && is_array($setup[(int)$id]) ? $setup[(int)$id] : array());
        if (!empty($meta['responsibility'])) {
            $row['responsibility'] = ebmrbpr_normalize_ipc_role($meta['responsibility']);
        } elseif (!empty($meta['role'])) {
            $row['responsibility'] = ebmrbpr_normalize_ipc_role($meta['role']);
        } else {
            $row['responsibility'] = ebmrbpr_normalize_ipc_role($row['responsibility'] ?? 'Production');
        }
        if (isset($meta['frequency']) && trim((string)$meta['frequency']) !== '') {
            $row['frequency'] = trim((string)$meta['frequency']);
        }
    }
    unset($row);
    return $rows;
}
function ebmrbpr_add_col($conn, $table, $col, $ddl)
{
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($col) . "'");
    if ($r && $r->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN $ddl");
    }
}

/** Build a signer snapshot array from e-sign result / request body. */
function ebmrbpr_signer_snapshot($sign, $fallback_emp = '')
{
    if (!is_array($sign)) {
        $sign = array();
    }
    $emp = trim((string)($sign['emp_id'] ?? $fallback_emp));
    $name = trim((string)($sign['emp_name'] ?? ''));
    $token = trim((string)($sign['signature_token'] ?? ''));
    if ($token === '' && $emp !== '') {
        $token = hash('sha256', implode('|', array(
            $emp,
            $sign['meaning'] ?? '',
            $sign['signed_at'] ?? date('Y-m-d H:i:s'),
            $sign['esign_id'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        )));
    }
    return array(
        'emp_id' => $emp,
        'emp_name' => $name,
        'department' => trim((string)($sign['department'] ?? '')),
        'designation' => trim((string)($sign['designation'] ?? '')),
        'signed_at' => trim((string)($sign['signed_at'] ?? date('Y-m-d H:i:s'))),
        'signature_token' => $token,
        'auth_method' => trim((string)($sign['auth_method'] ?? '')),
        'auth_type' => trim((string)($sign['auth_type'] ?? '')),
        'esign_id' => (int)($sign['esign_id'] ?? 0),
        'reason' => trim((string)($sign['reason'] ?? '')),
        'meaning' => trim((string)($sign['meaning'] ?? '')),
    );
}

function ebmrbpr_json_encode_sign($sign)
{
    return json_encode(ebmrbpr_signer_snapshot($sign), JSON_UNESCAPED_UNICODE);
}

function ebmrbpr_decode_sign_json($raw)
{
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }
    $d = json_decode($raw, true);
    return is_array($d) ? $d : null;
}

function ebmrbpr_log_master_step_workflow($conn, $plant_id, $row)
{
    $conn->query("INSERT INTO ebmrbpr_master_step_workflow_log
        (plant_id, profile_id, profile_stage_id, profile_step_id, stage_name, step_name,
         action, from_status, to_status, role_label, remark, emp_id, emp_name, signature_token, esign_id)
        VALUES (
            '" . ebmrbpr_esc($conn, $plant_id) . "',
            " . (int)($row['profile_id'] ?? 0) . ",
            " . (int)($row['profile_stage_id'] ?? 0) . ",
            " . (int)($row['profile_step_id'] ?? 0) . ",
            '" . ebmrbpr_esc($conn, $row['stage_name'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['step_name'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['action'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['from_status'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['to_status'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['role_label'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['remark'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['emp_id'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['emp_name'] ?? '') . "',
            '" . ebmrbpr_esc($conn, $row['signature_token'] ?? '') . "',
            " . (int)($row['esign_id'] ?? 0) . "
        )");
}

/** Hydrate workflow fields on a profile_step row for API responses. */
function ebmrbpr_hydrate_profile_step_workflow(&$t)
{
    $t['workflow_status'] = $t['workflow_status'] ?? 'Draft';
    $t['prepared'] = ebmrbpr_decode_sign_json($t['prepared_json'] ?? null);
    $t['checked'] = ebmrbpr_decode_sign_json($t['checked_json'] ?? null);
    $t['reviewed'] = ebmrbpr_decode_sign_json($t['reviewed_json'] ?? null);
    $t['approved'] = ebmrbpr_decode_sign_json($t['approved_json'] ?? null);
}

function ebmrbpr_hydrate_profile_stage_workflow(&$s)
{
    $s['frozen'] = (int)($s['frozen'] ?? 0);
    $s['freeze_sign'] = ebmrbpr_decode_sign_json($s['freeze_sign_json'] ?? null);
}

/** True when every step under a stage is Approved (or Frozen). */
function ebmrbpr_stage_steps_all_approved($conn, $profile_stage_id)
{
    $profile_stage_id = (int)$profile_stage_id;
    $res = $conn->query("SELECT workflow_status FROM ebmrbpr_profile_step WHERE profile_stage_id=$profile_stage_id");
    if (!$res || $res->num_rows == 0) {
        return false;
    }
    while ($r = $res->fetch_assoc()) {
        $st = trim((string)($r['workflow_status'] ?? 'Draft'));
        if ($st !== 'Approved' && $st !== 'Frozen') {
            return false;
        }
    }
    return true;
}

/** Profile ready for batch if all stages with steps are frozen (or profile Approved). */
function ebmrbpr_profile_master_workflow_ready($conn, $profile_id)
{
    $profile_id = (int)$profile_id;
    $pres = $conn->query("SELECT status FROM ebmrbpr_profile WHERE id=$profile_id LIMIT 1");
    if ($pres && $pres->num_rows > 0) {
        $pst = $pres->fetch_assoc()['status'] ?? '';
        if (strcasecmp((string)$pst, 'Approved') === 0) {
            return array('ok' => true, 'message' => '');
        }
    }
    // Legacy profiles that never used master step workflow remain startable.
    $wf = $conn->query("SELECT COUNT(*) AS c FROM ebmrbpr_profile_step
        WHERE profile_id=$profile_id AND workflow_status IS NOT NULL
          AND workflow_status<>'' AND workflow_status<>'Draft'");
    $usedWorkflow = ($wf && $wf->num_rows) ? (int)$wf->fetch_assoc()['c'] : 0;
    if ($usedWorkflow === 0) {
        return array('ok' => true, 'message' => '');
    }
    $sres = $conn->query("SELECT id, stage_name, frozen FROM ebmrbpr_profile_stage WHERE profile_id=$profile_id ORDER BY seq_no ASC, id ASC");
    if (!$sres || $sres->num_rows == 0) {
        return array('ok' => false, 'message' => 'Profile has no stages to freeze');
    }
    $hasSteps = false;
    while ($s = $sres->fetch_assoc()) {
        $cnt = $conn->query("SELECT COUNT(*) AS c FROM ebmrbpr_profile_step WHERE profile_stage_id=" . (int)$s['id']);
        $n = ($cnt && $cnt->num_rows) ? (int)$cnt->fetch_assoc()['c'] : 0;
        if ($n <= 0) {
            continue;
        }
        $hasSteps = true;
        if ((int)($s['frozen'] ?? 0) !== 1) {
            return array('ok' => false, 'message' => 'Stage "' . $s['stage_name'] . '" must be Saved & Frozen before starting a batch');
        }
    }
    if (!$hasSteps) {
        return array('ok' => false, 'message' => 'Profile has no configured steps');
    }
    return array('ok' => true, 'message' => '');
}

/** Attach live QMS deviation record to eBMR deviation row. */
function ebmrbpr_enrich_deviation_row($conn, &$row)
{
    $qid = (int)($row['qms_deviation_id'] ?? 0);
    $row['qms'] = null;
    if ($qid <= 0) {
        if (!empty($row['qms_form_json'])) {
            $row['qms'] = json_decode($row['qms_form_json'], true);
        }
        return;
    }
    $res = $conn->query("SELECT * FROM deviation WHERE id=$qid LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $q = $res->fetch_assoc();
        $row['qms'] = $q;
        $row['qms_status'] = $q['status'] ?? ($row['qms_status'] ?? '');
        $st = strtolower(trim((string)($q['status'] ?? '')));
        if (in_array($st, array('closed', 'approved', 'complete', 'completed'), true)) {
            $row['status'] = 'Closed';
        } elseif ($st !== '') {
            $row['status'] = 'In QMS Workflow';
        }
        $conn->query("UPDATE ebmrbpr_deviation SET qms_status='" . ebmrbpr_esc($conn, $row['qms_status']) . "',
            status='" . ebmrbpr_esc($conn, $row['status']) . "' WHERE id=" . (int)$row['id']);
    }
}

function ebmrbpr_enrich_incident_row($conn, &$row)
{
    $qid = (int)($row['qms_incident_id'] ?? 0);
    $row['qms'] = null;
    if ($qid <= 0) {
        if (!empty($row['qms_form_json'])) {
            $row['qms'] = json_decode($row['qms_form_json'], true);
        }
        return;
    }
    $res = $conn->query("SELECT * FROM new_incident WHERE id=$qid LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $q = $res->fetch_assoc();
        $row['qms'] = $q;
        $row['qms_status'] = $q['status'] ?? ($row['qms_status'] ?? '');
        $st = strtolower(trim((string)($q['status'] ?? '')));
        if (strpos($st, 'close') !== false || strpos($st, 'complete') !== false || strpos($st, 'approved') !== false) {
            $row['status'] = 'Closed';
        } elseif ($st !== '') {
            $row['status'] = 'In QMS Workflow';
        }
        $conn->query("UPDATE ebmrbpr_incident SET qms_status='" . ebmrbpr_esc($conn, $row['qms_status']) . "',
            status='" . ebmrbpr_esc($conn, $row['status']) . "' WHERE id=" . (int)$row['id']);
    }
}

function ebmrbpr_enrich_breakdown_row($conn, &$row)
{
    $bid = (int)($row['breakdown_id'] ?? 0);
    $row['breakdown'] = null;
    if ($bid <= 0) {
        return;
    }
    $res = $conn->query("SELECT * FROM breakdown_intimation WHERE id=$bid LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $b = $res->fetch_assoc();
        $row['breakdown'] = $b;
        $row['workflow_status'] = $b['workflow_status'] ?? ($row['workflow_status'] ?? '');
        $row['intimation_no'] = $b['intimation_no'] ?? ($row['intimation_no'] ?? '');
        $conn->query("UPDATE ebmrbpr_breakdown SET workflow_status='" . ebmrbpr_esc($conn, $row['workflow_status']) . "',
            intimation_no='" . ebmrbpr_esc($conn, $row['intimation_no']) . "' WHERE id=" . (int)$row['id']);
    }
}

/**
 * Enrich step equipment rows from equipment master (capacity, calibration_required, etc.).
 */
function ebmrbpr_enrich_step_equipment($conn, $rows)
{
    if (!is_array($rows) || count($rows) === 0) {
        return is_array($rows) ? $rows : array();
    }
    $codes = array();
    foreach ($rows as $eq) {
        if (!is_array($eq)) {
            continue;
        }
        $code = trim((string)($eq['equipment_code'] ?? $eq['id_no'] ?? ''));
        if ($code !== '') {
            $codes[$code] = true;
        }
    }
    $byCode = array();
    if (count($codes) > 0) {
        $esc = array();
        foreach (array_keys($codes) as $c) {
            $esc[] = "'" . ebmrbpr_esc($conn, $c) . "'";
        }
        $in = implode(',', $esc);
        $res = @$conn->query(
            "SELECT equipment_code, equipment_name, serial_no, make, capacity, from_range, to_range, unit,
                    calibration_required, preventive_maintenance
             FROM equipment WHERE equipment_code IN ($in)"
        );
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $code = trim((string)($r['equipment_code'] ?? ''));
                if ($code !== '') {
                    $byCode[$code] = $r;
                }
            }
        }
    }
    $out = array();
    foreach ($rows as $eq) {
        if (!is_array($eq)) {
            continue;
        }
        $code = trim((string)($eq['equipment_code'] ?? $eq['id_no'] ?? ''));
        $m = ($code !== '' && isset($byCode[$code])) ? $byCode[$code] : null;
        if ($m) {
            if (empty($eq['name']) && !empty($m['equipment_name'])) {
                $eq['name'] = $m['equipment_name'];
            }
            if (empty($eq['equipment_code'])) {
                $eq['equipment_code'] = $code;
            }
            if (empty($eq['id_no'])) {
                $eq['id_no'] = $code ?: ($m['serial_no'] ?? '');
            }
            if (empty($eq['make']) && !empty($m['make'])) {
                $eq['make'] = $m['make'];
            }
            if (empty($eq['capacity'])) {
                $from = trim((string)($m['from_range'] ?? ''));
                $to = trim((string)($m['to_range'] ?? ''));
                $cap = trim((string)($m['capacity'] ?? ''));
                if ($from && $to && $from !== $to) {
                    $eq['capacity'] = $from . ' - ' . $to;
                } elseif ($cap !== '') {
                    $eq['capacity'] = $cap;
                } elseif ($to !== '') {
                    $eq['capacity'] = $to;
                } elseif ($from !== '') {
                    $eq['capacity'] = $from;
                }
            }
            if (empty($eq['uom']) && !empty($m['unit'])) {
                $eq['uom'] = $m['unit'];
            }
            $cal = isset($eq['calibration_applicable']) && trim((string)$eq['calibration_applicable']) !== ''
                ? $eq['calibration_applicable']
                : ($m['calibration_required'] ?? 'Not Applicable');
            $eq['calibration_applicable'] = ebmrbpr_normalize_applicable_flag($cal);
            $eq['calibration_required'] = $eq['calibration_applicable'];
        } else {
            $cal = $eq['calibration_applicable'] ?? $eq['calibration_required'] ?? 'Not Applicable';
            $eq['calibration_applicable'] = ebmrbpr_normalize_applicable_flag($cal);
            $eq['calibration_required'] = $eq['calibration_applicable'];
        }
        $out[] = $eq;
    }
    return $out;
}

/** Resolve ipqc_testing / sampling_by / time_stamp / yield_reconciliation / equipment_point from config stage-step. */
function ebmrbpr_lookup_step_meta($conn, $config_id, $product_code, $stage_name, $step_name)
{
    $out = array(
        'ipqc_testing' => 'No',
        'sampling_by' => 'Production',
        'time_stamp' => 'Not Applicable',
        'yield_reconciliation' => 'Not Applicable',
        'equipment_point' => 'Not Applicable',
    );
    $config_id = (int)$config_id;
    if ($config_id <= 0 || trim($stage_name) === '' || trim($step_name) === '') {
        return $out;
    }
    $sn = ebmrbpr_esc($conn, trim($stage_name));
    $tn = ebmrbpr_esc($conn, trim($step_name));
    $pc = ebmrbpr_esc($conn, trim((string)$product_code));
    $sql = "SELECT ipqc_testing, sampling_by, time_stamp, yield_reconciliation, equipment_point FROM ebmrbpr_config_stage_step
        WHERE config_id=$config_id AND stage_name='$sn' AND step_name='$tn'";
    if ($pc !== '') {
        $res = $conn->query($sql . " AND product_code='$pc' ORDER BY id DESC LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $out['ipqc_testing'] = $r['ipqc_testing'] ?: 'No';
            $out['sampling_by'] = $r['sampling_by'] ?: 'Production';
            $out['time_stamp'] = ebmrbpr_normalize_applicable_flag($r['time_stamp'] ?? 'Not Applicable');
            $out['yield_reconciliation'] = ebmrbpr_normalize_applicable_flag($r['yield_reconciliation'] ?? 'Not Applicable');
            $out['equipment_point'] = ebmrbpr_normalize_applicable_flag($r['equipment_point'] ?? 'Not Applicable');
            return $out;
        }
    }
    $res = $conn->query($sql . " AND (product_code IS NULL OR product_code='') ORDER BY id DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        $out['ipqc_testing'] = $r['ipqc_testing'] ?: 'No';
        $out['sampling_by'] = $r['sampling_by'] ?: 'Production';
        $out['time_stamp'] = ebmrbpr_normalize_applicable_flag($r['time_stamp'] ?? 'Not Applicable');
        $out['yield_reconciliation'] = ebmrbpr_normalize_applicable_flag($r['yield_reconciliation'] ?? 'Not Applicable');
        $out['equipment_point'] = ebmrbpr_normalize_applicable_flag($r['equipment_point'] ?? 'Not Applicable');
    }
    return $out;
}

/** Normalize Applicable | Not Applicable flags from Configure Stage & Step. */
function ebmrbpr_normalize_applicable_flag($value)
{
    $v = strtolower(trim((string)$value));
    if ($v === 'applicable' || $v === 'yes' || $v === 'y' || $v === '1' || $v === 'true') {
        return 'Applicable';
    }
    return 'Not Applicable';
}

/** In-process check performed-by role: Production | QA | Both | Alternate. */
function ebmrbpr_normalize_ipc_role($value)
{
    $v = strtolower(trim((string)$value));
    if ($v === 'qa' || $v === 'quality' || $v === 'ipqa' || $v === 'quality assurance') {
        return 'QA';
    }
    if ($v === 'both' || $v === 'production & qa' || $v === 'production and qa' || $v === 'prod+qa') {
        return 'Both';
    }
    if (
        strpos($v, 'alternate') !== false ||
        strpos($v, 'alternet') !== false ||
        $v === 'qa/production alternate' ||
        $v === 'production/qa alternate'
    ) {
        return 'QA/Production Alternate';
    }
    return 'Production';
}

/** @deprecated use ebmrbpr_normalize_applicable_flag */
function ebmrbpr_normalize_time_stamp_flag($value)
{
    return ebmrbpr_normalize_applicable_flag($value);
}

/** Build specification list for a step (master IPQC specs + in-process spec_tests). */
function ebmrbpr_fetch_step_ipqc_specs($conn, $product_code, $stage_name, $template_ipqc)
{
    $specs = array();
    if (is_array($template_ipqc)) {
        foreach ($template_ipqc as $sp) {
            if (is_array($sp)) {
                $specs[] = $sp;
            }
        }
    }
    $pc = ebmrbpr_esc($conn, trim((string)$product_code));
    $st = ebmrbpr_esc($conn, trim((string)$stage_name));
    if ($pc !== '' && $st !== '') {
        $sql = "SELECT s.stage, s.test, s.subtest, s.limits, s.limit_type, s.lower_limit, s.upper_limit,
            s.lessthan, s.morethan, s.procedures, s.instruments, s.calculation
            FROM spec_tests s
            WHERE s.specification_no IN (
                SELECT specification_no FROM specification
                WHERE spec_type='Inprocess Specification' AND product_code='$pc'
            ) AND s.stage='$st'";
        $res = @$conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $r['source'] = 'spec_tests';
                $specs[] = $r;
            }
        }
    }
    return $specs;
}

/** Insert technical_info row for eBMR sampling (QC in-process pipeline). */
function ebmrbpr_create_technical_info_for_sampling($conn, $plant_id, $emp_id, $entry_date, $batch, $step_row, $sampling_id, $form)
{
    $pc = ebmrbpr_esc($conn, $batch['product_code'] ?? '');
    $batchNo = ebmrbpr_esc($conn, $batch['batch_no'] ?? '');
    $batchSize = ebmrbpr_esc($conn, $batch['batch_size'] ?? '');
    $stage = ebmrbpr_esc($conn, $step_row['stage_name'] ?? ebmrbpr_in($form, 'stage_name'));
    $step = ebmrbpr_esc($conn, $step_row['step_name'] ?? ebmrbpr_in($form, 'step_name'));
    $sampleId = ebmrbpr_esc($conn, ebmrbpr_in($form, 'sample_id'));
    $equip = ebmrbpr_esc($conn, ebmrbpr_in($form, 'equipment_code'));
    $qty = ebmrbpr_esc($conn, ebmrbpr_in($form, 'sample_qty'));
    $unit = ebmrbpr_esc($conn, ebmrbpr_in($form, 'unit'));
    $sampleBy = ebmrbpr_esc($conn, ebmrbpr_in($form, 'sample_by', $emp_id));
    $bid = (int)($batch['id'] ?? 0);
    $sid = (int)$sampling_id;
    @ebmrbpr_add_col($conn, 'technical_info', 'sample_by', "sample_by VARCHAR(150) DEFAULT NULL");
    @ebmrbpr_add_col($conn, 'technical_info', 'ebmr_batch_id', "ebmr_batch_id INT DEFAULT 0");
    @ebmrbpr_add_col($conn, 'technical_info', 'ebmr_sampling_id', "ebmr_sampling_id INT DEFAULT 0");
    $sql = "INSERT INTO technical_info (product_code, batch_no, batch_size, stage, step, sample_id, equipment_code,
        sample_qty, unit, sample_by, entry_by, entry_date, status, ebmr_batch_id, ebmr_sampling_id)
        VALUES ('$pc','$batchNo','$batchSize','$stage','$step','$sampleId','$equip','$qty','$unit','$sampleBy',
        '" . ebmrbpr_esc($conn, $emp_id) . "','$entry_date','pending',$bid,$sid)";
    if (!$conn->query($sql)) {
        return 0;
    }
    return (int)$conn->insert_id;
}

/** Sync ebmrbpr_sampling from linked technical_info; push approved results into batch step. */
function ebmrbpr_sync_sampling_from_technical($conn, &$row)
{
    $tiId = (int)($row['technical_info_id'] ?? 0);
    $row['technical'] = null;
    $row['tests'] = array();
    if ($tiId <= 0) {
        if (!empty($row['spec_json'])) {
            $row['specs'] = json_decode($row['spec_json'], true);
        }
        if (!empty($row['test_results_json'])) {
            $row['tests'] = json_decode($row['test_results_json'], true);
        }
        return;
    }
    $res = @$conn->query("SELECT * FROM technical_info WHERE id=$tiId LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return;
    }
    $ti = $res->fetch_assoc();
    $row['technical'] = $ti;
    $row['ar_no'] = $ti['ar_no'] ?? ($row['ar_no'] ?? '');
    $st = strtolower(trim((string)($ti['status'] ?? '')));
    $newStatus = $row['status'];
    if ($st === 'pending') {
        $newStatus = 'pending_qc_receive';
    } elseif ($st === 'accept') {
        $newStatus = 'qc_accepted';
    } elseif ($st === 'inprocess') {
        $newStatus = 'in_testing';
    } elseif ($st === 'done') {
        $newStatus = 'approved';
    }
    $tests = array();
    if (!empty($ti['tests'])) {
        $decoded = is_string($ti['tests']) ? json_decode($ti['tests'], true) : $ti['tests'];
        if (is_array($decoded)) {
            $tests = $decoded;
        }
    }
    if (count($tests) === 0 && $st === 'done') {
        $tres = @$conn->query("SELECT test, subtest, result, observation, remark, status FROM technical_tests
            WHERE ti_id=$tiId OR technical_info_id=$tiId ORDER BY id ASC");
        if ($tres) {
            while ($tr = $tres->fetch_assoc()) {
                $tests[] = $tr;
            }
        }
    }
    $row['tests'] = $tests;
    $testsJson = ebmrbpr_esc($conn, json_encode($tests));
    $conn->query("UPDATE ebmrbpr_sampling SET status='" . ebmrbpr_esc($conn, $newStatus) . "',
        ar_no='" . ebmrbpr_esc($conn, $row['ar_no']) . "',
        test_results_json='$testsJson'
        WHERE id=" . (int)$row['id']);
    $row['status'] = $newStatus;
    if ($newStatus === 'approved') {
        ebmrbpr_push_sampling_results_to_step($conn, $row, $tests);
    }
}

function ebmrbpr_push_sampling_results_to_step($conn, $sampling_row, $tests)
{
    $stepId = (int)($sampling_row['batch_step_id'] ?? 0);
    if ($stepId <= 0) {
        return;
    }
    $sres = $conn->query("SELECT data_json, template_json, status FROM ebmrbpr_batch_step WHERE id=$stepId LIMIT 1");
    if (!$sres || $sres->num_rows === 0) {
        return;
    }
    $sr = $sres->fetch_assoc();
    $data = json_decode($sr['data_json'], true);
    if (!is_array($data)) {
        $data = array();
    }
    if (!isset($data['ipqc']) || !is_array($data['ipqc'])) {
        $data['ipqc'] = array();
    }
    $template = json_decode($sr['template_json'], true);
    $masterSpecs = (is_array($template) && isset($template['ipqc'])) ? $template['ipqc'] : array();
    $common = array();
    foreach ($tests as $t) {
        $key = 'qc_' . md5(($t['test'] ?? '') . '|' . ($t['subtest'] ?? ''));
        $row = array(
            'test' => $t['test'] ?? '',
            'subtest' => $t['subtest'] ?? '',
            'result' => $t['result'] ?? '',
            'observation' => $t['observation'] ?? '',
            'remark' => $t['remark'] ?? '',
            'status' => $t['status'] ?? 'done',
            'ar_no' => $sampling_row['ar_no'] ?? '',
            'source' => 'QC',
            'value' => $t['result'] ?? '',
        );
        $data['ipqc'][$key] = $row;
        $common[] = $row;
    }
    foreach ($masterSpecs as $sp) {
        if (!is_array($sp)) {
            continue;
        }
        $sid = (string)($sp['id'] ?? '');
        if ($sid !== '' && !isset($data['ipqc'][$sid])) {
            $data['ipqc'][$sid] = array(
                'result' => 'See QC AR ' . ($sampling_row['ar_no'] ?? ''),
                'value' => 'See QC AR ' . ($sampling_row['ar_no'] ?? ''),
                'source' => 'QC',
            );
        }
    }
    $data['ipqc_sampling_id'] = (int)($sampling_row['id'] ?? 0);
    $data['ipqc_ar_no'] = $sampling_row['ar_no'] ?? '';
    $data['ipqc_status'] = 'approved';
    $data['ipqc_released_at'] = date('Y-m-d H:i:s');
    $data['ipqc_results'] = $common;
    $data['ipqc_hold_released'] = true;
    $conn->query("UPDATE ebmrbpr_batch_step SET data_json='" . ebmrbpr_esc($conn, json_encode($data)) . "' WHERE id=$stepId");
    // Log release — unlocks step completion / next-step progression for IPQC hold
    $batchId = (int)($sampling_row['batch_id'] ?? 0);
    if ($batchId > 0 && function_exists('ebmrbpr_log')) {
        ebmrbpr_log(
            $conn,
            $batchId,
            'IPQC_RELEASED',
            'QC released IPQC results — stage/step hold lifted: ' . ($sampling_row['stage_name'] ?? '') . ' / ' . ($sampling_row['step_name'] ?? '')
                . ' AR ' . ($sampling_row['ar_no'] ?? ''),
            $sampling_row['raised_by'] ?? ''
        );
    }
}

/** Sync eBMR sampling when linked technical_info is released (status=done). */
function ebmrbpr_sync_sampling_after_technical_done($conn, $technical_info_id)
{
    $tiId = (int)$technical_info_id;
    if ($tiId <= 0) {
        return false;
    }
    $tres = @$conn->query("SELECT ebmr_sampling_id FROM technical_info WHERE id=$tiId LIMIT 1");
    if (!$tres || $tres->num_rows === 0) {
        return false;
    }
    $sid = (int)($tres->fetch_assoc()['ebmr_sampling_id'] ?? 0);
    if ($sid <= 0) {
        $sfind = @$conn->query("SELECT id FROM ebmrbpr_sampling WHERE technical_info_id=$tiId LIMIT 1");
        if ($sfind && $sfind->num_rows) {
            $sid = (int)$sfind->fetch_assoc()['id'];
        }
    }
    if ($sid <= 0) {
        return false;
    }
    $sres = $conn->query("SELECT * FROM ebmrbpr_sampling WHERE id=$sid LIMIT 1");
    if (!$sres || $sres->num_rows === 0) {
        return false;
    }
    $row = $sres->fetch_assoc();
    ebmrbpr_sync_sampling_from_technical($conn, $row);
    return true;
}

function ebmrbpr_step_ipqc_approved($conn, $batch_step_id)
{
    $batch_step_id = (int)$batch_step_id;
    if ($batch_step_id <= 0) {
        return true;
    }
    $sres = $conn->query("SELECT ipqc_testing FROM ebmrbpr_batch_step WHERE id=$batch_step_id LIMIT 1");
    if (!$sres || $sres->num_rows === 0) {
        return true;
    }
    if (strtoupper(trim((string)$sres->fetch_assoc()['ipqc_testing'])) !== 'YES') {
        return true;
    }
    $q = $conn->query("SELECT id FROM ebmrbpr_sampling WHERE batch_step_id=$batch_step_id AND status='approved' LIMIT 1");
    return ($q && $q->num_rows > 0);
}

function ebmrbpr_insert_qms_deviation($conn, $plant_id, $emp_id, $entry_date, $form, $batch)
{
    $identifiedBy = ebmrbpr_esc($conn, ebmrbpr_in($form, 'identifiedBy', $emp_id));
    $devOccuredDate = ebmrbpr_esc($conn, ebmrbpr_in($form, 'devOccuredDate', date('Y-m-d')));
    $devOccuredDept = ebmrbpr_esc($conn, ebmrbpr_in($form, 'devOccuredDept', $_GET['department'] ?? 'Production'));
    $devIdentifiedDate = ebmrbpr_esc($conn, ebmrbpr_in($form, 'devIdentifiedDate', date('Y-m-d')));
    $timeOfDev = ebmrbpr_esc($conn, ebmrbpr_in($form, 'timeOfDev', date('H:i')));
    $typeOfDev = ebmrbpr_esc($conn, ebmrbpr_in($form, 'typeOfDev', 'Unplanned'));
    $devScope = ebmrbpr_esc($conn, ebmrbpr_in($form, 'devScope', 'Product'));
    $scopeItem = ebmrbpr_esc($conn, ebmrbpr_in($form, 'scopeItem', ($batch['product_name'] ?? '') . ' (' . ($batch['product_code'] ?? '') . ')'));
    $detailsOfDev = ebmrbpr_esc($conn, ebmrbpr_in($form, 'detailsOfDev'));
    $standProcedureSystem = ebmrbpr_esc($conn, ebmrbpr_in($form, 'standProcedureSystem'));
    $batchRef = 'Batch: ' . ($batch['batch_no'] ?? '') . ' | BMR: ' . ($batch['profile_code'] ?? '');
    $detailsOfDevFull = ebmrbpr_esc($conn, $batchRef . "\n" . ebmrbpr_in($form, 'detailsOfDev'));
    $sql = "INSERT INTO deviation (plant_id, status, identifiedBy, devOccuredDate, devOccuredDept, devIdentifiedDate, timeOfDev,
        typeOfDev, devScope, scopeItem, detailsOfDev, devDetDoc, standProcedureSystem, standProceSysDoc, entryBy, entryDate,
        rnd,qa,regulatory,hr,it,micro,store,qc,ehs,production,admin,engg,capaData,closureData)
        VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "','Pending','$identifiedBy','$devOccuredDate','$devOccuredDept',
        '$devIdentifiedDate','$timeOfDev','$typeOfDev','$devScope','$scopeItem','$detailsOfDevFull','','" . $standProcedureSystem . "','',
        '" . ebmrbpr_esc($conn, $emp_id) . "','$entry_date','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','NA','[]','[]')";
    if (!$conn->query($sql)) {
        return array('ok' => false, 'message' => $conn->error);
    }
    return array('ok' => true, 'id' => (int)$conn->insert_id);
}

function ebmrbpr_insert_qms_incident($conn, $emp_id, $entry_date, $form, $batch)
{
    $Date_Of_INR = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Date_Of_INR', date('Y-m-d')));
    $Name_of_Department = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Name_of_Department', $_GET['department'] ?? 'Production'));
    $INR_No = ebmrbpr_esc($conn, ebmrbpr_in($form, 'INR_No', 'INR-' . date('ymdHis')));
    $Target_Date = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Target_Date', date('Y-m-d', strtotime('+14 days'))));
    $INR_Details = ebmrbpr_esc($conn, 'Batch ' . ($batch['batch_no'] ?? '') . ' — ' . ebmrbpr_in($form, 'INR_Details'));
    $Probable_Cause = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Probable_Cause_Root_Cause_for_Incidence'));
    $Corrective_Action = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Corrective_Action'));
    $Preventive_Action = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Preventive_Action'));
    $Root_Cause = ebmrbpr_esc($conn, ebmrbpr_in($form, 'Root_Cause_Identified'));
    $classification_inr = ebmrbpr_esc($conn, ebmrbpr_in($form, 'classification_inr', 'Minor'));
    $incident_relateds = ebmrbpr_esc($conn, ebmrbpr_in($form, 'incident_relateds', '[]'));
    $potential_impact = ebmrbpr_esc($conn, ebmrbpr_in($form, 'potential_impact', '[]'));
    $sql = "INSERT INTO new_incident (Date_Of_INR, Name_of_Department, INR_No, CAPA_Ref_No, Ref_QMS_Document_No, Target_Date,
        INR_Details, incident_supportive_document, Probable_Cause_Root_Cause_for_Incidence, Corrective_Action,
        Corrective_Reference_Document, Preventive_Action, Preventive_Reference_Document, Root_Cause_Identified,
        incident_relateds, classification_inr, potential_impact, status, entry_by, entry_date)
        VALUES ('$Date_Of_INR','$Name_of_Department','$INR_No','','','$Target_Date','$INR_Details','','$Probable_Cause',
        '$Corrective_Action','','$Preventive_Action','','$Root_Cause','$incident_relateds','$classification_inr',
        '$potential_impact','send for review','" . ebmrbpr_esc($conn, $emp_id) . "','$entry_date')";
    if (!$conn->query($sql)) {
        return array('ok' => false, 'message' => $conn->error);
    }
    return array('ok' => true, 'id' => (int)$conn->insert_id, 'inr_no' => $INR_No);
}

/**
 * CFR Part 11 batch audit trail entry.
 * Captures who / what / when / where (IP) / why with optional e-sign linkage.
 * $extra keys: emp_name, department, designation, module, record_ref, meaning,
 *   auth_method, auth_type, signature_token, esign_id, stage_name, step_name,
 *   old_value, new_value, reason, plant_id, batch_no, product_code, product_name
 */
function ebmrbpr_log($conn, $batch_id, $action, $detail, $by, $extra = array())
{
    if (!is_array($extra)) {
        $extra = array();
    }
    $batch_id = (int)$batch_id;
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $xff = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        $cand = trim($xff[0]);
        if ($cand !== '') {
            $ip = $cand;
        }
    }
    $ua = substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255);
    $host = substr(trim((string)($_SERVER['HTTP_HOST'] ?? gethostname() ?: '')), 0, 120);
    $plant = trim((string)($extra['plant_id'] ?? ($_GET['plant_id'] ?? '')));
    $dept = trim((string)($extra['department'] ?? ($_GET['department'] ?? '')));
    $empName = trim((string)($extra['emp_name'] ?? ''));
    $designation = trim((string)($extra['designation'] ?? ''));
    $by = trim((string)$by);

    if (($empName === '' || $designation === '') && $by !== '') {
        $er = @$conn->query("SELECT firstname, lastname, department, designation FROM employee WHERE emp_id='" . ebmrbpr_esc($conn, $by) . "' LIMIT 1");
        if ($er && $er->num_rows) {
            $e = $er->fetch_assoc();
            if ($empName === '') {
                $empName = trim(($e['firstname'] ?? '') . ' ' . ($e['lastname'] ?? ''));
            }
            if ($dept === '') {
                $dept = trim((string)($e['department'] ?? ''));
            }
            if ($designation === '') {
                $designation = trim((string)($e['designation'] ?? ''));
            }
        }
    }

    $batchNo = trim((string)($extra['batch_no'] ?? ''));
    $productCode = trim((string)($extra['product_code'] ?? ''));
    $productName = trim((string)($extra['product_name'] ?? ''));
    if ($batch_id > 0 && ($batchNo === '' || $productCode === '')) {
        $br = @$conn->query("SELECT batch_no, product_code, product_name, plant_id FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
        if ($br && $br->num_rows) {
            $b = $br->fetch_assoc();
            if ($batchNo === '') {
                $batchNo = (string)($b['batch_no'] ?? '');
            }
            if ($productCode === '') {
                $productCode = (string)($b['product_code'] ?? '');
            }
            if ($productName === '') {
                $productName = (string)($b['product_name'] ?? '');
            }
            if ($plant === '') {
                $plant = (string)($b['plant_id'] ?? '');
            }
        }
    }

    $at = date('Y-m-d H:i:s');
    $module = trim((string)($extra['module'] ?? 'execution'));
    $recordRef = trim((string)($extra['record_ref'] ?? (string)$batch_id));
    $meaning = trim((string)($extra['meaning'] ?? ''));
    $authMethod = trim((string)($extra['auth_method'] ?? ''));
    $authType = trim((string)($extra['auth_type'] ?? ''));
    $sigToken = trim((string)($extra['signature_token'] ?? ''));
    $esignId = (int)($extra['esign_id'] ?? 0);
    $stage = trim((string)($extra['stage_name'] ?? ''));
    $step = trim((string)($extra['step_name'] ?? ''));
    $oldV = isset($extra['old_value']) ? (is_scalar($extra['old_value']) ? (string)$extra['old_value'] : json_encode($extra['old_value'])) : '';
    $newV = isset($extra['new_value']) ? (is_scalar($extra['new_value']) ? (string)$extra['new_value'] : json_encode($extra['new_value'])) : '';
    $reason = trim((string)($extra['reason'] ?? ''));

    $hashSrc = implode('|', array(
        $batch_id, $action, $detail, $by, $at, $ip, $module, $recordRef, $sigToken, $oldV, $newV
    ));
    $recordHash = hash('sha256', $hashSrc);

    $sql = "INSERT INTO ebmrbpr_batch_log (
        batch_id, action, detail, by_emp, at_time,
        plant_id, emp_name, department, designation, ip_address, user_agent, host_name,
        module, record_ref, meaning, auth_method, auth_type, signature_token, esign_id,
        batch_no, product_code, product_name, stage_name, step_name,
        old_value, new_value, reason, record_hash
    ) VALUES (
        $batch_id,
        '" . ebmrbpr_esc($conn, $action) . "',
        '" . ebmrbpr_esc($conn, $detail) . "',
        '" . ebmrbpr_esc($conn, $by) . "',
        '" . ebmrbpr_esc($conn, $at) . "',
        '" . ebmrbpr_esc($conn, $plant) . "',
        '" . ebmrbpr_esc($conn, $empName) . "',
        '" . ebmrbpr_esc($conn, $dept) . "',
        '" . ebmrbpr_esc($conn, $designation) . "',
        '" . ebmrbpr_esc($conn, $ip) . "',
        '" . ebmrbpr_esc($conn, $ua) . "',
        '" . ebmrbpr_esc($conn, $host) . "',
        '" . ebmrbpr_esc($conn, $module) . "',
        '" . ebmrbpr_esc($conn, $recordRef) . "',
        '" . ebmrbpr_esc($conn, $meaning) . "',
        '" . ebmrbpr_esc($conn, $authMethod) . "',
        '" . ebmrbpr_esc($conn, $authType) . "',
        '" . ebmrbpr_esc($conn, $sigToken) . "',
        $esignId,
        '" . ebmrbpr_esc($conn, $batchNo) . "',
        '" . ebmrbpr_esc($conn, $productCode) . "',
        '" . ebmrbpr_esc($conn, $productName) . "',
        '" . ebmrbpr_esc($conn, $stage) . "',
        '" . ebmrbpr_esc($conn, $step) . "',
        '" . ebmrbpr_esc($conn, $oldV) . "',
        '" . ebmrbpr_esc($conn, $newV) . "',
        '" . ebmrbpr_esc($conn, $reason) . "',
        '" . ebmrbpr_esc($conn, $recordHash) . "'
    )";
    @$conn->query($sql);
}

/** Collect unique equipment lookup tokens from batch static + step templates. */
function ebmrbpr_batch_equipment_lookup_values($batch)
{
    $vals = array();
    $add = function ($eq) use (&$vals) {
        if (!is_array($eq)) {
            return;
        }
        foreach (array('equipment_code', 'id_no', 'name') as $f) {
            $v = trim((string)($eq[$f] ?? ''));
            if ($v !== '') {
                $vals[$v] = true;
            }
        }
    };
    foreach (($batch['static']['equipment_selection'] ?? array()) as $eq) {
        $add($eq);
    }
    foreach (($batch['steps'] ?? array()) as $step) {
        foreach (($step['template']['equipment'] ?? array()) as $eq) {
            $add($eq);
        }
    }
    return array_keys($vals);
}

/** PM flags aligned with Engineering perform queue (overdue + next 30 days). */
function ebmrbpr_pm_row_flags($remaining_days)
{
    $rd = (int)$remaining_days;
    return array(
        'alert' => ($rd <= 30),
        'blocked' => ($rd <= 0),
        'overdue' => ($rd < 0),
    );
}

function ebmrbpr_pm_format_row($row)
{
    $flags = ebmrbpr_pm_row_flags($row['remaining_days'] ?? 0);
    $row['alert'] = $flags['alert'];
    $row['blocked'] = $flags['blocked'];
    $row['overdue'] = $flags['overdue'];
    $row['remaining_days'] = (int)($row['remaining_days'] ?? 0);
    return $row;
}

/**
 * Preventive maintenance status for all equipment referenced on a batch.
 * Uses equipment_maintenance (due_type Preventive, status Pending) — same source as Engineering PM queue.
 */
function ebmrbpr_fetch_equipment_pm_status($conn, $plant_id, $batch)
{
    $out = array('by_key' => array(), 'items' => array());
    $lookup = ebmrbpr_batch_equipment_lookup_values($batch);
    if (count($lookup) === 0) {
        return $out;
    }
    $esc = array();
    foreach ($lookup as $v) {
        $esc[] = "'" . $conn->real_escape_string($v) . "'";
    }
    $in = implode(',', $esc);
    $plant_sql = $plant_id ? " AND b.plant_id='" . $conn->real_escape_string($plant_id) . "'" : '';
    $sql = "SELECT b.id AS equipment_id, TRIM(b.equipment_code) AS equipment_code, TRIM(b.equipment_name) AS equipment_name
        FROM equipment b
        WHERE (TRIM(b.equipment_code) IN ($in) OR TRIM(b.equipment_name) IN ($in)) $plant_sql";
    $res = $conn->query($sql);
    if (!$res || $res->num_rows === 0) {
        return $out;
    }
    $eqById = array();
    $eqIds = array();
    $codeToId = array();
    while ($r = $res->fetch_assoc()) {
        $eid = (int)$r['equipment_id'];
        $eqIds[] = $eid;
        $code = trim((string)$r['equipment_code']);
        $eqById[$eid] = $r;
        if ($code !== '') {
            $codeToId[$code] = $eid;
        }
    }
    if (count($eqIds) === 0) {
        return $out;
    }
    $idIn = implode(',', array_map('intval', array_unique($eqIds)));
    $pmSql = "SELECT a.id AS maintenance_id, a.equipment_id, a.due_date, a.frequency, a.status,
            DATEDIFF(a.due_date, CURDATE()) AS remaining_days
        FROM equipment_maintenance a
        WHERE a.due_type = 'Preventive'
        AND a.status = 'Pending'
        AND a.equipment_id IN ($idIn)
        AND DATEDIFF(a.due_date, CURDATE()) <= 30
        ORDER BY a.due_date ASC";
    $pmRes = $conn->query($pmSql);
    $pmByEq = array();
    if ($pmRes && $pmRes->num_rows > 0) {
        while ($pm = $pmRes->fetch_assoc()) {
            $eid = (int)$pm['equipment_id'];
            if (!isset($pmByEq[$eid])) {
                $eq = $eqById[$eid] ?? array();
                $pm['equipment_code'] = $eq['equipment_code'] ?? '';
                $pm['equipment_name'] = $eq['equipment_name'] ?? '';
                $pmByEq[$eid] = ebmrbpr_pm_format_row($pm);
            }
        }
    }
    foreach ($pmByEq as $eid => $pm) {
        $out['items'][] = $pm;
    }
    foreach ($lookup as $token) {
        $eid = isset($codeToId[$token]) ? $codeToId[$token] : null;
        if (!$eid) {
            foreach ($eqById as $id => $eq) {
                if (strcasecmp(trim((string)$eq['equipment_name']), $token) === 0) {
                    $eid = (int)$id;
                    break;
                }
            }
        }
        if ($eid && isset($pmByEq[$eid])) {
            $out['by_key'][$token] = $pmByEq[$eid];
        }
    }
    foreach ($pmByEq as $eid => $pm) {
        $code = trim((string)($pm['equipment_code'] ?? ''));
        if ($code !== '' && !isset($out['by_key'][$code])) {
            $out['by_key'][$code] = $pm;
        }
    }
    return $out;
}

function ebmrbpr_equipment_template_keys($equipment_list)
{
    $keys = array();
    if (!is_array($equipment_list)) {
        return $keys;
    }
    foreach ($equipment_list as $eq) {
        if (!is_array($eq)) {
            continue;
        }
        foreach (array('equipment_code', 'id_no', 'name') as $f) {
            $v = trim((string)($eq[$f] ?? ''));
            if ($v !== '') {
                $keys[] = $v;
            }
        }
    }
    return array_unique($keys);
}

function ebmrbpr_step_pm_blocked_message($conn, $plant_id, $batch, $template)
{
    $equip = isset($template['equipment']) ? $template['equipment'] : array();
    if (!is_array($equip) || count($equip) === 0) {
        return null;
    }
    $pm = ebmrbpr_fetch_equipment_pm_status($conn, $plant_id, $batch);
    $byKey = $pm['by_key'] ?? array();
    $blocked = array();
    foreach ($equip as $eq) {
        foreach (ebmrbpr_equipment_template_keys(array($eq)) as $k) {
            if (isset($byKey[$k]) && !empty($byKey[$k]['blocked'])) {
                $label = ($byKey[$k]['equipment_name'] ?? $eq['name'] ?? $k) . ' (' . ($byKey[$k]['equipment_code'] ?? $k) . ')';
                $blocked[$label] = true;
            }
        }
    }
    if (count($blocked) === 0) {
        return null;
    }
    return 'Preventive maintenance overdue/due — equipment usage restricted: ' . implode('; ', array_keys($blocked));
}

function ebmrbpr_batch_stub_for_pm($batch)
{
    return array(
        'static' => isset($batch['static']) ? $batch['static'] : array(),
        'steps' => isset($batch['steps']) ? $batch['steps'] : array(),
    );
}

/** Last yield actual / % from batch yield statement + step data. */
function ebmrbpr_extract_yield_summary($conn, $batch_id)
{
    $out = array('actual_qty' => '', 'yield_pct' => '', 'uom' => '');
    $batch_id = (int)$batch_id;
    $bres = $conn->query("SELECT yield_json, batch_size_uom FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    if (!$bres || !$bres->num_rows) {
        return $out;
    }
    $b = $bres->fetch_assoc();
    $y = json_decode($b['yield_json'] ?? '{}', true);
    if (is_array($y) && !empty($y['final_pct'])) {
        $out['yield_pct'] = trim((string)$y['final_pct']);
    }
    $out['uom'] = trim((string)($b['batch_size_uom'] ?? ''));
    $sres = $conn->query("SELECT template_json, data_json FROM ebmrbpr_batch_step WHERE batch_id=$batch_id ORDER BY stage_seq DESC, step_seq DESC, id DESC");
    if ($sres) {
        while ($s = $sres->fetch_assoc()) {
            $tpl = json_decode($s['template_json'], true);
            $data = json_decode($s['data_json'], true);
            if (!is_array($tpl) || empty($tpl['yield']['enabled'])) {
                continue;
            }
            $rows = isset($tpl['yield']['rows']) ? $tpl['yield']['rows'] : array();
            foreach ($rows as $i => $r) {
                $d = isset($data['yield'][$i]) ? $data['yield'][$i] : array();
                if (!empty($d['actual'])) {
                    $out['actual_qty'] = trim((string)$d['actual']);
                    if ($out['yield_pct'] === '' && !empty($d['pct'])) {
                        $out['yield_pct'] = trim((string)$d['pct']);
                    }
                }
            }
            if ($out['actual_qty'] !== '') {
                break;
            }
        }
    }
    return $out;
}

/** Push release yield/dates into mfg_work_order_hdr for Production post-completion tabs. */
function ebmrbpr_sync_work_order_on_release($conn, $batch_id, $emp_id, $entry_date, $plant_id)
{
    $batch_id = (int)$batch_id;
    $bres = $conn->query("SELECT * FROM ebmrbpr_batch WHERE id=$batch_id LIMIT 1");
    if (!$bres || !$bres->num_rows) {
        return;
    }
    $batch = $bres->fetch_assoc();
    $wo_id = (int)($batch['work_order_id'] ?? 0);
    if ($wo_id <= 0) {
        return;
    }

    $batchStub = array(
        'id' => $batch_id,
        'work_order_id' => $wo_id,
        'plant_id' => $plant_id,
        'product_code' => $batch['product_code'],
        'mfg_date' => $batch['mfg_date'],
        'exp_date' => $batch['exp_date'],
        'header' => json_decode($batch['header_json'], true),
    );
    ebmrbpr_sync_batch_dates($conn, $batchStub);

    $yield = ebmrbpr_extract_yield_summary($conn, $batch_id);
    $commence = '';
    $complete = substr($entry_date, 0, 10);
    $sres = $conn->query("SELECT MIN(started_at) AS started, MAX(completed_at) AS completed FROM ebmrbpr_batch_step WHERE batch_id=$batch_id AND status IN ('Done','Checked')");
    if ($sres && $sres->num_rows) {
        $d = $sres->fetch_assoc();
        if (!empty($d['started'])) {
            $commence = substr($d['started'], 0, 10);
        }
        if (!empty($d['completed'])) {
            $complete = substr($d['completed'], 0, 10);
        }
    }
    if ($commence === '' && !empty($batchStub['mfg_date'])) {
        $commence = substr($batchStub['mfg_date'], 0, 10);
    }

    $actual = ebmrbpr_esc($conn, $yield['actual_qty'] !== '' ? $yield['actual_qty'] : '0');
    $pct = ebmrbpr_esc($conn, $yield['yield_pct'] !== '' ? $yield['yield_pct'] : '0');
    $days = 0;
    if ($commence !== '' && $complete !== '') {
        $days = max(0, (int)((strtotime($complete) - strtotime($commence)) / 86400));
    }

    $conn->query("UPDATE mfg_work_order_hdr SET
        actual_yeild='$actual',
        yeild_percentage='$pct',
        batch_commence_date='" . ebmrbpr_esc($conn, $commence) . "',
        batch_complete_date='" . ebmrbpr_esc($conn, $complete) . "',
        no_of_days='" . $days . "',
        bmr_status='Complete',
        tr_to_packing_dept_by='" . ebmrbpr_esc($conn, $emp_id) . "',
        tr_to_packing_dept_date='" . ebmrbpr_esc($conn, $entry_date) . "',
        statusForCheckAndTranfer='For_Checking',
        dispensing_status=IF(dispensing_status IS NULL OR dispensing_status='', 'Request Sent', dispensing_status),
        rm_qa_dislc_status=IF(rm_qa_dislc_status IS NULL OR rm_qa_dislc_status='', 'Approved', rm_qa_dislc_status)
        WHERE id=$wo_id");
}

/* ============================================================
   AUTH + DISPATCH
============================================================ */
if (defined('EBMRBPR_SKIP_DISPATCH') && EBMRBPR_SKIP_DISPATCH) {
    return;
}

$token = isset($_GET["token"]) ? $_GET["token"] : '';
$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $token, $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = isset($string[1]) ? $string[1] : '';
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . ($_GET["type"] ?? '') . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    @file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    ebmrbpr_ensure_tables($conn);

    require_once __DIR__ . '/ebmr_bpr_seed_aurenyx.php';

    $type = $_GET["type"] ?? '';
    $plant_id = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
    $emp_id = $conn->real_escape_string($_GET["emp_id"]);

    require __DIR__ . '/ebmr_bpr_handlers.php';
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
}

$conn->close();
?>
