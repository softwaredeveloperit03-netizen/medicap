<?php

if (!function_exists('gw_mrp_dashboard_scalar_count')) {
    function gw_mrp_dashboard_scalar_count($conn, $sql)
    {
        try {
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_row())) {
                return (int)($row[0] ?? 0);
            }
        } catch (Throwable $e) {
            return 0;
        }
        return 0;
    }
}

if (!function_exists('gw_mrp_dashboard_table_exists')) {
    function gw_mrp_dashboard_table_exists($conn, $tableName)
    {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tableName);
        if ($tableName === '') {
            return false;
        }
        try {
            $res = $conn->query("SHOW TABLES LIKE '$tableName'");
            return ($res && $res->num_rows > 0);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gw_mrp_dashboard_column_exists')) {
    function gw_mrp_dashboard_column_exists($conn, $tableName, $colName)
    {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tableName);
        $colName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$colName);
        if ($tableName === '' || $colName === '') {
            return false;
        }
        try {
            $res = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$colName'");
            return ($res && $res->num_rows > 0);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gw_get_mrp_dashboard_stats')) {
    function gw_get_mrp_dashboard_stats($conn, $filters = [])
    {
        $plantId = mysqli_real_escape_string($conn, trim($filters['plant_id'] ?? ''));
        $woPlant = $plantId !== '' ? " AND (plant_id = '$plantId' OR plant_id IS NULL OR TRIM(plant_id) = '')" : '';
        $dedPlant = $plantId !== '' ? " AND (plant_id = '$plantId' OR plant_id IS NULL OR TRIM(plant_id) = '')" : '';
        $logPlant = $plantId !== '' ? " AND (l.plant_id = '$plantId' OR l.plant_id IS NULL OR TRIM(l.plant_id) = '')" : '';

        $totalWo = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM Work_order_materials WHERE TRIM(COALESCE(workorder_no, '')) <> ''$woPlant"
        );

        $totalMaterial = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(DISTINCT material_code) FROM WO_deductions
             WHERE TRIM(COALESCE(material_code, '')) <> ''$dedPlant"
        );

        $indentsUnderConfirmation = 0;
        $latestPendingIndentId = 0;
        if (gw_mrp_dashboard_table_exists($conn, 'mrp_indents_confirmation')) {
            $indentsUnderConfirmation = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM mrp_indents_confirmation WHERE UPPER(COALESCE(confirmation_status, '')) = 'PENDING'"
            );
            $latestPendingIndentId = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COALESCE(MAX(id), 0) FROM mrp_indents_confirmation WHERE UPPER(COALESCE(confirmation_status, '')) = 'PENDING'"
            );
        }

        $poProcessed = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(DISTINCT COALESCE(NULLIF(CAST(l.indent_id AS CHAR), '0'), CAST(l.id AS CHAR)))
             FROM mrp_shortages_indent_log l
             LEFT JOIN indend_raw ir ON CAST(ir.id AS CHAR) = CAST(l.indent_id AS CHAR)
             LEFT JOIN mrp_raised_indnd_qty rq ON CAST(rq.indend_id AS CHAR) = CAST(l.indent_id AS CHAR)
             WHERE 1=1 $logPlant
               AND (
                 TRIM(COALESCE(rq.po_no, '')) <> ''
                 OR LOWER(COALESCE(ir.po_indend, '')) = 'approve'
               )"
        );

        $poReceived = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(DISTINCT l.id)
             FROM mrp_shortages_indent_log l
             INNER JOIN mrp_raised_indnd_qty rq
               ON CAST(rq.indend_id AS CHAR) = CAST(l.indent_id AS CHAR)
               AND TRIM(COALESCE(rq.po_no, '')) <> ''
             INNER JOIN challan c ON c.po_no = rq.po_no
             INNER JOIN challan_materials cm
               ON cm.challan_no = c.challan_no
               AND cm.material_code = l.material_code
             WHERE 1=1 $logPlant
               AND (
                 LOWER(COALESCE(cm.receiving, '')) IN ('approve','approved','yes','done','complete','completed')
                 OR TRIM(COALESCE(cm.grn_no, '')) <> ''
                 OR LOWER(COALESCE(cm.grn, '')) IN ('approve','approved','yes','done')
               )"
        );

        $spPlant = $plantId !== '' ? " AND (a.plant_id = '$plantId' OR a.plant_id IS NULL OR TRIM(a.plant_id) = '')" : '';

        $processingPending = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM split_planning_qty a
             INNER JOIN order_materials om
               ON om.order_no = a.order_no
               AND om.product_code = a.product_code
               AND om.reqStatus = 'Inprocess'
             WHERE (a.status IS NULL OR TRIM(IFNULL(a.status, '')) = '' OR LOWER(TRIM(a.status)) = 'pending')
               AND NOT EXISTS (
                 SELECT 1 FROM Work_order_materials w
                 WHERE CAST(w.doc_no AS CHAR) = CAST(a.id AS CHAR)
                   AND w.doc_no IS NOT NULL AND TRIM(w.doc_no) <> '' AND w.doc_no <> '0'
               )$spPlant"
        );

        $approvalPending = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM split_planning_qty a
             WHERE a.status = 'Work Order Processed'$spPlant"
        );

        $generateWoPending = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM Work_order_materials a
             WHERE (a.send_for_analysis_date IS NULL OR TRIM(IFNULL(a.send_for_analysis_date, '')) = '')
               AND (
                 a.status IS NULL OR TRIM(a.status) = ''
                 OR a.status IN (
                   'Work Order Processed', 'Work Order Preparation Approved',
                   'CANNOT_PLAN', 'CAN_PLAN', 'CAN_PLAN_MC_QTY_USED', 'Pending'
                 )
               )$woPlant"
        );

        $shortagesPending = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM Work_order_materials a
             WHERE a.status = 'CANNOT_PLAN'$woPlant"
        );

        $canPlannedWoPending = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM Work_order_materials a
             WHERE a.status IN ('CAN_PLAN', 'CAN_PLAN_MC_QTY_USED')$woPlant"
        );

        $verifyStockPending = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM Work_order_materials a
             WHERE a.status = 'Pending Verification'$woPlant"
        );

        // Phase 3–7 KPI counts (additive; guarded).
        $shortageApprovalsPending = 0;
        $shortageApprovalsApproved = 0;
        if (gw_mrp_dashboard_table_exists($conn, 'mrp_shortage_approval')) {
            $shortageApprovalsPending = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM mrp_shortage_approval WHERE UPPER(COALESCE(approval_status, '')) = 'PENDING'"
            );
            $shortageApprovalsApproved = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM mrp_shortage_approval WHERE UPPER(COALESCE(approval_status, '')) = 'APPROVED'"
            );
        }

        $indentsLocked = 0;
        if (gw_mrp_dashboard_table_exists($conn, 'mrp_indents_confirmation')) {
            if (gw_mrp_dashboard_column_exists($conn, 'mrp_indents_confirmation', 'is_locked')) {
                $indentsLocked = gw_mrp_dashboard_scalar_count(
                    $conn,
                    "SELECT COUNT(*) FROM mrp_indents_confirmation
                     WHERE COALESCE(is_locked, 0) = 1
                        OR UPPER(COALESCE(confirmation_status, '')) = 'LOCKED'"
                );
            } else {
                $indentsLocked = gw_mrp_dashboard_scalar_count(
                    $conn,
                    "SELECT COUNT(*) FROM mrp_indents_confirmation
                     WHERE UPPER(COALESCE(confirmation_status, '')) = 'LOCKED'"
                );
            }
        }

        $planningImmediate = 0;
        $planningFuture = 0;
        if (gw_mrp_dashboard_column_exists($conn, 'Work_order_materials', 'planning_horizon')) {
            $planningImmediate = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM Work_order_materials
                 WHERE UPPER(COALESCE(planning_horizon, '')) = 'IMMEDIATE'$woPlant"
            );
            $planningFuture = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM Work_order_materials
                 WHERE UPPER(COALESCE(planning_horizon, '')) = 'FUTURE'$woPlant"
            );
        } elseif (gw_mrp_dashboard_column_exists($conn, 'Work_order_materials', 'expected_production_start_date')) {
            $planningImmediate = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM Work_order_materials
                 WHERE expected_production_start_date IS NOT NULL
                   AND TRIM(expected_production_start_date) <> ''
                   AND expected_production_start_date <> '0000-00-00'
                   AND DATEDIFF(expected_production_start_date, CURDATE()) <= 30
                   $woPlant"
            );
            $planningFuture = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM Work_order_materials
                 WHERE expected_production_start_date IS NOT NULL
                   AND TRIM(expected_production_start_date) <> ''
                   AND expected_production_start_date <> '0000-00-00'
                   AND DATEDIFF(expected_production_start_date, CURDATE()) > 30
                   $woPlant"
            );
        }

        $lineBookingHistoryVersions = 0;
        if (gw_mrp_dashboard_table_exists($conn, 'mrp_line_booking_history')) {
            $lineBookingHistoryVersions = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM mrp_line_booking_history"
            );
        }

        $cancelRecalcEvents = 0;
        if (gw_mrp_dashboard_table_exists($conn, 'mrp_cancel_recalc_log')) {
            $cancelRecalcEvents = gw_mrp_dashboard_scalar_count(
                $conn,
                "SELECT COUNT(*) FROM mrp_cancel_recalc_log"
            );
        }

        return [
            'status' => 'success',
            'po_received' => $poReceived,
            'po_processed' => $poProcessed,
            'total_wo' => $totalWo,
            'total_material' => $totalMaterial,
            'indents_under_confirmation' => $indentsUnderConfirmation,
            'latest_pending_indent_id' => $latestPendingIndentId,
            'shortage_approvals_pending' => $shortageApprovalsPending,
            'shortage_approvals_approved' => $shortageApprovalsApproved,
            'indents_locked' => $indentsLocked,
            'planning_immediate' => $planningImmediate,
            'planning_future' => $planningFuture,
            'line_booking_history_versions' => $lineBookingHistoryVersions,
            'cancel_recalc_events' => $cancelRecalcEvents,
            'module_pending' => [
                'processing' => $processingPending,
                'approval' => $approvalPending,
                'generate_wo' => $generateWoPending,
                'shortages' => $shortagesPending,
                'indents' => $indentsUnderConfirmation,
                'can_planned_wo' => $canPlannedWoPending,
                'verify_stock' => $verifyStockPending,
            ],
        ];
    }
}
