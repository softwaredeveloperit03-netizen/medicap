<?php
/**
 * End-to-end demo pipeline: Product Master → Unit Formula → BFR → Planning → WO → QA → Dispensing →
 * Work Allocation → eBMR profile → Execution → Release → Production Report.
 */

function ebmrbpr_demo_rm_material($conn, $plant_id)
{
    $res = $conn->query("SELECT material_code, material_name, grade FROM material
        WHERE plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' AND material_type LIKE '%Raw%'
        ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return array('material_code' => 'RM-DEMO-API', 'material_name' => 'Demo API', 'grade' => 'IP');
}

function ebmrbpr_seed_aurenyx_inj_demo($conn, $plant_id, $emp_id, $entry_date, $target = 'production_report')
{
    $pc = 'AURENYX-INJ-DEMO';
    $pn = 'AurenyxInj Demo';
    $mfr = 'MFR-AURENYX-INJ-DEMO';
    $dosage = 'Liquid Injection';
    $batchSize = '5000';
    $packUnit = 'Vials';
    $grade = 'Demo';
    $out = array('status' => 'success', 'product_code' => $pc, 'product_name' => $pn, 'steps' => array());

    $conn->begin_transaction();
    try {
        // --- 1. Product Master ---
        $pres = $conn->query("SELECT product_code FROM product WHERE product_code='" . ebmrbpr_esc($conn, $pc) . "' AND plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' LIMIT 1");
        if (!$pres || !$pres->num_rows) {
            $conn->query("INSERT INTO product (plant_id, product_type, product_code, product_name, grade, dosage_form, dosage_type,
                generic_name, shelf_life, label_claim, pack_unit, entry_by, entry_date, status)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', 'Finished Product', '" . ebmrbpr_esc($conn, $pc) . "',
                '" . ebmrbpr_esc($conn, $pn) . "', '" . ebmrbpr_esc($conn, $grade) . "', '" . ebmrbpr_esc($conn, $dosage) . "',
                'Injectable', 'Aurenyx Demo Injection', '24', 'Demo label claim', '" . ebmrbpr_esc($conn, $packUnit) . "',
                '" . ebmrbpr_esc($conn, $emp_id) . "', '" . ebmrbpr_esc($conn, $entry_date) . "', 'Approved')");
            $out['steps'][] = 'Product created';
        } else {
            $out['steps'][] = 'Product exists';
        }

        // --- 2. Unit Formula (MFR) ---
        $rm = ebmrbpr_demo_rm_material($conn, $plant_id);
        $rmJson = json_encode(array(array(
            'material_code' => $rm['material_code'],
            'material_name' => $rm['material_name'],
            'grade' => $rm['grade'] ?? 'IP',
            'qty' => '1.000',
            'unit' => 'Kg',
            'overages' => '0',
            'role' => 'Active',
            'stage' => 'Compounding',
        )));
        $ufRes = $conn->query("SELECT id FROM unitformula WHERE mfr_no='" . ebmrbpr_esc($conn, $mfr) . "' AND plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' LIMIT 1");
        if (!$ufRes || !$ufRes->num_rows) {
            $conn->query("INSERT INTO unitformula (plant_id, product_code, mfr_no, bom_type, product_type, batch_size, batch_size_unit,
                min_output_qty, max_output_qty, dispatch_qty, unit, raw_materials, packing_materials, status, entry_by, entry_date)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', '" . ebmrbpr_esc($conn, $pc) . "', '" . ebmrbpr_esc($conn, $mfr) . "',
                'Formulation', 'Finished Product', '" . ebmrbpr_esc($conn, $batchSize) . "', '" . ebmrbpr_esc($conn, $packUnit) . "',
                '" . ebmrbpr_esc($conn, $batchSize) . "', '" . ebmrbpr_esc($conn, $batchSize) . "', '" . ebmrbpr_esc($conn, $batchSize) . "',
                '" . ebmrbpr_esc($conn, $packUnit) . "', '" . ebmrbpr_esc($conn, $rmJson) . "', '[]', 'Approved',
                '" . ebmrbpr_esc($conn, $emp_id) . "', '" . ebmrbpr_esc($conn, $entry_date) . "')");
            $out['steps'][] = 'Unit formula created';
        } else {
            $out['steps'][] = 'Unit formula exists';
        }

        // --- 3. Batch Formula (BFR) ---
        $bfrNo = '';
        $bfRes = $conn->query("SELECT bfr_no FROM batch_formula_info WHERE product_code='" . ebmrbpr_esc($conn, $pc) . "' AND plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' AND status='Approve' ORDER BY id DESC LIMIT 1");
        if ($bfRes && $bfRes->num_rows > 0) {
            $bfrNo = $bfRes->fetch_assoc()['bfr_no'];
            $out['steps'][] = 'Batch formula exists';
        } else {
            $cnt = $conn->query("SELECT COUNT(*)+1 AS c FROM batch_formula_info WHERE plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'");
            $n = ($cnt && $cnt->num_rows) ? (int)$cnt->fetch_assoc()['c'] : 1;
            $bfrNo = 'BFR-AID-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
            $conn->query("INSERT INTO batch_formula_info (plant_id, mfr_no, bfr_no, product_type, product_code, batch_formula_weight,
                rm_batch_size_unit, status, entry_by, entry_date)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', '" . ebmrbpr_esc($conn, $mfr) . "', '" . ebmrbpr_esc($conn, $bfrNo) . "',
                'Finished Product', '" . ebmrbpr_esc($conn, $pc) . "', '" . ebmrbpr_esc($conn, $batchSize) . "',
                '" . ebmrbpr_esc($conn, $packUnit) . "', 'Approve', '" . ebmrbpr_esc($conn, $emp_id) . "', '" . ebmrbpr_esc($conn, $entry_date) . "')");
            $out['steps'][] = 'Batch formula created';
        }
        $out['bfr_no'] = $bfrNo;
        $out['mfr_no'] = $mfr;

        // --- 4. Batch Planning + Work Order ---
        $woId = 0;
        $planId = 0;
        $woRes = $conn->query("SELECT a.id AS wo_id, a.batch_plan_id, a.batch_number, a.work_order_no
            FROM mfg_work_order_hdr a
            JOIN batch_planning b ON a.batch_plan_id = b.id
            WHERE b.product_code='" . ebmrbpr_esc($conn, $pc) . "' AND a.plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'
            ORDER BY a.id DESC LIMIT 1");
        if ($woRes && $woRes->num_rows > 0) {
            $w = $woRes->fetch_assoc();
            $woId = (int)$w['wo_id'];
            $planId = (int)$w['batch_plan_id'];
            $out['work_order_id'] = $woId;
            $out['plan_no'] = $w['batch_plan_id'];
            $out['batch_number'] = $w['batch_number'];
            $out['work_order_no'] = $w['work_order_no'];
            $out['steps'][] = 'Work order exists';
        } else {
            $cnt = $conn->query("SELECT COUNT(*)+1 AS c FROM batch_planning WHERE plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'");
            $pnNum = ($cnt && $cnt->num_rows) ? (int)$cnt->fetch_assoc()['c'] : 1;
            $planNo = 'PL' . str_pad((string)$pnNum, 4, '0', STR_PAD_LEFT);
            $conn->query("INSERT INTO batch_planning (plant_id, plan_no, product_code, product_name, grade, bfr_no, mfr_no,
                batch_size, pack_unit, total_batches, status, entry_by, entry_date, product_type)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', '" . ebmrbpr_esc($conn, $planNo) . "',
                '" . ebmrbpr_esc($conn, $pc) . "', '" . ebmrbpr_esc($conn, $pn) . "', '" . ebmrbpr_esc($conn, $grade) . "',
                '" . ebmrbpr_esc($conn, $bfrNo) . "', '" . ebmrbpr_esc($conn, $mfr) . "',
                '" . ebmrbpr_esc($conn, $batchSize) . "', '" . ebmrbpr_esc($conn, $packUnit) . "', '1', 'Approved',
                '" . ebmrbpr_esc($conn, $emp_id) . "', '" . ebmrbpr_esc($conn, $entry_date) . "', 'Finished Product')");
            $planId = (int)$conn->insert_id;
            $woCnt = $conn->query("SELECT COUNT(*)+1 AS c FROM mfg_work_order_hdr WHERE plant_id='" . ebmrbpr_esc($conn, $plant_id) . "'");
            $woN = ($woCnt && $woCnt->num_rows) ? (int)$woCnt->fetch_assoc()['c'] : 1;
            $workOrderNo = 'WO' . str_pad((string)$woN, 4, '0', STR_PAD_LEFT);
            $conn->query("INSERT INTO mfg_work_order_hdr (plant_id, batch_plan_id, work_order_no, status, material_type,
                calculation_type, no_of_lots, entry_by, ebmr_status)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', $planId, '" . ebmrbpr_esc($conn, $workOrderNo) . "',
                'pending', 'RM', 'Standard', '1', '" . ebmrbpr_esc($conn, $emp_id) . "', 'Applicable')");
            $woId = (int)$conn->insert_id;
            $conn->query("INSERT INTO mfg_work_order_dtl (work_order_id, material_code, grade, unit_qty, unit, batch_qty, total_batch_qty, stage)
                VALUES ($woId, '" . ebmrbpr_esc($conn, $rm['material_code']) . "', '" . ebmrbpr_esc($conn, $rm['grade'] ?? 'IP') . "',
                '1.000', 'Kg', '" . ebmrbpr_esc($conn, $batchSize) . "', '" . ebmrbpr_esc($conn, $batchSize) . "', 'Compounding')");
            $out['work_order_id'] = $woId;
            $out['plan_no'] = $planNo;
            $out['work_order_no'] = $workOrderNo;
            $out['steps'][] = 'Batch plan + work order created';
        }

        // --- 5. Prod Head approval ---
        $conn->query("UPDATE mfg_work_order_hdr SET status='approved', approved_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            approved_date='" . ebmrbpr_esc($conn, $entry_date) . "' WHERE id=$woId");
        $out['steps'][] = 'Work order approved';

        // --- 6. QA batch number ---
        $ym = date('ym');
        $batchNo = $plant_id . '/' . $ym . '/AID' . str_pad((string)$woId, 3, '0', STR_PAD_LEFT);
        $mfgDate = date('Y-m-d');
        $expDate = date('Y-m-d', strtotime('+24 months'));
        $conn->query("UPDATE mfg_work_order_hdr SET batch_number='" . ebmrbpr_esc($conn, $batchNo) . "',
            qa_person='" . ebmrbpr_esc($conn, $emp_id) . "', qa_date='" . ebmrbpr_esc($conn, $entry_date) . "',
            mfg_date='" . ebmrbpr_esc($conn, $mfgDate) . "', exp_date='" . ebmrbpr_esc($conn, $expDate) . "',
            rm_qa_dislc_status='Approved', dispensing_status='Request Sent',
            dispense_request_sent_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            dispense_request_sent_on='" . ebmrbpr_esc($conn, $entry_date) . "',
            rm_disp_completed_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            rm_disp_completed_date='" . ebmrbpr_esc($conn, $entry_date) . "'
            WHERE id=$woId");
        $out['batch_number'] = $batchNo;
        $out['steps'][] = 'QA batch number assigned';

        // --- 7. Dispensing received in Production ---
        $conn->query("UPDATE mfg_work_order_hdr SET rm_received_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            rm_received_date='" . ebmrbpr_esc($conn, $entry_date) . "',
            rm_receiving_status='dispensing_completed'
            WHERE id=$woId");
        $out['steps'][] = 'RM received in Production';

        if ($target === 'dispensing') {
            $conn->commit();
            $out['message'] = 'Demo ready through Dispensing (step 4). Continue with Work Allocation.';
            return $out;
        }

        // --- 8. eBMR Master profile ---
        $profileId = 0;
        $profCode = 'AURENYX-INJ-BMR';
        $pRes = $conn->query("SELECT id FROM ebmrbpr_profile WHERE profile_code='" . ebmrbpr_esc($conn, $profCode) . "' AND plant_id='" . ebmrbpr_esc($conn, $plant_id) . "' LIMIT 1");
        if ($pRes && $pRes->num_rows > 0) {
            $profileId = (int)$pRes->fetch_assoc()['id'];
            $out['steps'][] = 'eBMR profile exists';
        } else {
            $hdr = json_encode(array('product_code' => $pc, 'product_name' => $pn, 'batch_size' => $batchSize, 'batch_size_uom' => $packUnit, 'shelf_life' => '24 Months'));
            $static = json_encode(array('product_approval' => array('product_name' => $pn, 'shelf_life' => '24 Months'), 'safety' => 'Demo safety for injectable.', 'dispensing' => array('sections' => array(), 'materials' => array())));
            $conn->query("INSERT INTO ebmrbpr_profile (plant_id, profile_code, profile_name, record_type, dosage_form, version, status, header_json, static_json, right_tabs_json, entry_by)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', '" . ebmrbpr_esc($conn, $profCode) . "', 'AurenyxInj Demo eBMR', 'eBMR',
                '" . ebmrbpr_esc($conn, $dosage) . "', '1.0', 'Approved', '" . ebmrbpr_esc($conn, $hdr) . "',
                '" . ebmrbpr_esc($conn, $static) . "', '{}', '" . ebmrbpr_esc($conn, $emp_id) . "')");
            $profileId = (int)$conn->insert_id;
            $stagesDef = array(
                array('Compounding', 'Solution Preparation'),
                array('Filtration', 'Sterile Filtration'),
                array('Filling', 'Aseptic Filling & Sealing'),
                array('Inspection', 'Visual Inspection'),
            );
            $sidx = 0;
            foreach ($stagesDef as $sd) {
                $sidx++;
                $cfg = json_encode(array('instructions' => 'Demo: ' . $sd[0], 'esign' => array('checking_required' => true, 'approval_required' => false, 'qa_check_required' => ($sidx <= 2))));
                $sName = ebmrbpr_esc($conn, $sd[0]);
                $conn->query("INSERT INTO ebmrbpr_profile_stage (profile_id, stage_master_id, stage_name, seq_no, config_json)
                    VALUES ($profileId, 0, '$sName', $sidx, '" . ebmrbpr_esc($conn, $cfg) . "')");
                $stageRowId = (int)$conn->insert_id;
                $stepCfg = json_encode(array('master_forms' => array('procedure' => true), 'checkpoint_sequence' => array('procedure'), 'procedure' => 'Demo procedure: ' . $sd[1]));
                $tName = ebmrbpr_esc($conn, $sd[1]);
                $conn->query("INSERT INTO ebmrbpr_profile_step (profile_id, profile_stage_id, step_master_id, step_name, seq_no, config_json)
                    VALUES ($profileId, $stageRowId, 0, '$tName', 1, '" . ebmrbpr_esc($conn, $stepCfg) . "')");
            }
            $out['steps'][] = 'eBMR profile created';
        }
        $out['profile_id'] = $profileId;

        $bind = $conn->query("SELECT id FROM ebmrbpr_profile_product WHERE product_code='" . ebmrbpr_esc($conn, $pc) . "' AND profile_id=$profileId AND status='active' LIMIT 1");
        if (!$bind || !$bind->num_rows) {
            $conn->query("INSERT INTO ebmrbpr_profile_product (profile_id, product_code, status, entry_by, entry_date)
                VALUES ($profileId, '" . ebmrbpr_esc($conn, $pc) . "', 'active', '" . ebmrbpr_esc($conn, $emp_id) . "', '" . ebmrbpr_esc($conn, $entry_date) . "')");
            $out['steps'][] = 'Product bound to eBMR profile';
        }

        // --- 9. Work allocation ---
        $hdrId = 0;
        $hRes = $conn->query("SELECT id FROM ebmrbpr_work_alloc_header WHERE work_order_id=$woId LIMIT 1");
        if ($hRes && $hRes->num_rows > 0) {
            $hdrId = (int)$hRes->fetch_assoc()['id'];
        } else {
            $woRow = $conn->query("SELECT work_order_no, batch_number FROM mfg_work_order_hdr WHERE id=$woId LIMIT 1")->fetch_assoc();
            $conn->query("INSERT INTO ebmrbpr_work_alloc_header (plant_id, work_order_id, work_order_no, batch_plan_id, plan_no, batch_number,
                product_code, product_name, profile_id, profile_code, status, entry_by)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', $woId, '" . ebmrbpr_esc($conn, $woRow['work_order_no']) . "',
                $planId, '" . ebmrbpr_esc($conn, $out['plan_no'] ?? '') . "',
                '" . ebmrbpr_esc($conn, $woRow['batch_number']) . "',
                '" . ebmrbpr_esc($conn, $pc) . "', '" . ebmrbpr_esc($conn, $pn) . "',
                $profileId, '" . ebmrbpr_esc($conn, $profCode) . "', 'Pending', '" . ebmrbpr_esc($conn, $emp_id) . "')");
            $hdrId = (int)$conn->insert_id;
            $sres = $conn->query("SELECT seq_no, stage_name FROM ebmrbpr_profile_stage WHERE profile_id=$profileId ORDER BY seq_no ASC");
            while ($st = $sres->fetch_assoc()) {
                $conn->query("INSERT INTO ebmrbpr_batch_work_alloc (header_id, work_order_id, stage_seq, stage_name, status,
                    operator_emp, operator_name, approver_emp, approver_name)
                    VALUES ($hdrId, $woId, " . (int)$st['seq_no'] . ", '" . ebmrbpr_esc($conn, $st['stage_name']) . "',
                    'Allocated', '" . ebmrbpr_esc($conn, $emp_id) . "', 'Demo Operator', '" . ebmrbpr_esc($conn, $emp_id) . "', 'Demo Approver')");
            }
            $conn->query("UPDATE ebmrbpr_work_alloc_header SET status='Allocated', allocated_by='" . ebmrbpr_esc($conn, $emp_id) . "',
                allocated_at='" . ebmrbpr_esc($conn, $entry_date) . "' WHERE id=$hdrId");
            $out['steps'][] = 'Work allocation completed';
        }

        if ($target === 'allocation') {
            $conn->commit();
            $out['message'] = 'Demo ready through Work Allocation (step 5). Start eBMR from batch list.';
            return $out;
        }

        // --- 10. Start eBMR batch ---
        $batchId = 0;
        $bRes = $conn->query("SELECT id, status FROM ebmrbpr_batch WHERE work_order_id=$woId AND status<>'Deleted' ORDER BY id DESC LIMIT 1");
        if ($bRes && $bRes->num_rows > 0) {
            $br = $bRes->fetch_assoc();
            $batchId = (int)$br['id'];
            $out['ebmr_batch_id'] = $batchId;
            $out['ebmr_status'] = $br['status'];
            $out['steps'][] = 'eBMR batch exists';
        } else {
            $pres = $conn->query("SELECT * FROM ebmrbpr_profile WHERE id=$profileId LIMIT 1");
            $profile = $pres->fetch_assoc();
            $conn->query("INSERT INTO ebmrbpr_batch (plant_id, profile_id, profile_code, record_type, batch_no, product_code, product_name,
                dosage_form, batch_size, batch_size_uom, mfg_date, exp_date, header_json, static_json, right_tabs_json,
                work_order_id, work_order_no, batch_plan_id, plan_no, status, entry_by, yield_json)
                VALUES ('" . ebmrbpr_esc($conn, $plant_id) . "', $profileId, '" . ebmrbpr_esc($conn, $profCode) . "', 'eBMR',
                '" . ebmrbpr_esc($conn, $batchNo) . "', '" . ebmrbpr_esc($conn, $pc) . "', '" . ebmrbpr_esc($conn, $pn) . "',
                '" . ebmrbpr_esc($conn, $dosage) . "', '" . ebmrbpr_esc($conn, $batchSize) . "', '" . ebmrbpr_esc($conn, $packUnit) . "',
                '" . ebmrbpr_esc($conn, $mfgDate) . "', '" . ebmrbpr_esc($conn, $expDate) . "',
                '" . ebmrbpr_esc($conn, $profile['header_json']) . "', '" . ebmrbpr_esc($conn, $profile['static_json']) . "',
                '" . ebmrbpr_esc($conn, $profile['right_tabs_json'] ?? '{}') . "',
                $woId, '" . ebmrbpr_esc($conn, $out['work_order_no']) . "', $planId,
                '" . ebmrbpr_esc($conn, $out['plan_no'] ?? '') . "', 'In Progress', '" . ebmrbpr_esc($conn, $emp_id) . "',
                '{\"final_pct\":\"98.5\",\"remark\":\"AurenyxInj Demo yield\"}')");
            $batchId = (int)$conn->insert_id;
            $sres = $conn->query("SELECT * FROM ebmrbpr_profile_stage WHERE profile_id=$profileId ORDER BY seq_no ASC");
            while ($stage = $sres->fetch_assoc()) {
                $sconf = json_decode($stage['config_json'], true);
                $stageInstr = is_array($sconf) && isset($sconf['instructions']) ? $sconf['instructions'] : '';
                $tres = $conn->query("SELECT * FROM ebmrbpr_profile_step WHERE profile_stage_id=" . (int)$stage['id'] . " ORDER BY seq_no ASC");
                while ($step = $tres->fetch_assoc()) {
                    $tpl = json_decode($step['config_json'], true);
                    if (!is_array($tpl)) {
                        $tpl = array();
                    }
                    $conn->query("INSERT INTO ebmrbpr_batch_step (batch_id, stage_seq, stage_name, stage_instructions, step_seq, step_name, template_json, data_json, status)
                        VALUES ($batchId, " . (int)$stage['seq_no'] . ", '" . ebmrbpr_esc($conn, $stage['stage_name']) . "',
                        '" . ebmrbpr_esc($conn, $stageInstr) . "', " . (int)$step['seq_no'] . ", '" . ebmrbpr_esc($conn, $step['step_name']) . "',
                        '" . ebmrbpr_esc($conn, json_encode($tpl)) . "', '{}', 'Pending')");
                }
            }
            ebmrbpr_init_batch_stages($conn, $batchId, $profileId);
            $out['ebmr_batch_id'] = $batchId;
            $out['steps'][] = 'eBMR batch started';
        }

        if ($target === 'execution') {
            $conn->commit();
            $out['message'] = 'Demo ready for eBMR Execution (step 6). Open execution to complete steps manually.';
            return $out;
        }

        // --- 11. Complete execution + release for Production Report ---
        $conn->query("UPDATE ebmrbpr_batch_step SET status='Checked', completed_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            completed_at='" . ebmrbpr_esc($conn, $entry_date) . "', checked_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            checked_at='" . ebmrbpr_esc($conn, $entry_date) . "'
            WHERE batch_id=$batchId");
        $conn->query("UPDATE ebmrbpr_batch_stage SET status='Approved', check_status='Approved', approval_status='Approved',
            qa_check_status='Approved', qa_approval_status='Approved' WHERE batch_id=$batchId");
        $conn->query("UPDATE ebmrbpr_batch SET status='Approved', prod_signoff='Approved', prod_signoff_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            prod_signoff_at='" . ebmrbpr_esc($conn, $entry_date) . "', qa_signoff='Approved', qa_signoff_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            qa_signoff_at='" . ebmrbpr_esc($conn, $entry_date) . "',
            yield_json='{\"final_pct\":\"98.5\",\"remark\":\"AurenyxInj Demo — auto demo release\"}'
            WHERE id=$batchId");
        $conn->query("UPDATE ebmrbpr_batch SET status='Released', released_by='" . ebmrbpr_esc($conn, $emp_id) . "',
            released_at='" . ebmrbpr_esc($conn, $entry_date) . "',
            release_remark='AurenyxInj Demo pipeline seed — production report demo'
            WHERE id=$batchId");
        ebmrbpr_sync_work_order_on_release($conn, $batchId, $emp_id, $entry_date, $plant_id);
        $out['ebmr_status'] = 'Released';
        $out['steps'][] = 'Batch released for Production Report';

        $conn->commit();
        $out['message'] = 'AurenyxInj Demo pipeline complete through Production Report. Open /prod-f-ebmr/batch/completed to verify.';
        $out['routes'] = array(
            'product' => '/master/product',
            'unit_formula' => '/unitformula',
            'batch_planning' => '/production/batch-planning',
            'dispensing' => '/fproduction/dispensing',
            'work_allocation' => '/fproduction/work-allocation',
            'ebmr_execution' => '/master/ebmr-bpr/execution/' . $batchId,
            'production_report' => '/prod-f-ebmr/batch/completed',
        );
        return $out;
    } catch (Exception $e) {
        $conn->rollback();
        return array('status' => 'error', 'message' => $e->getMessage());
    }
}
