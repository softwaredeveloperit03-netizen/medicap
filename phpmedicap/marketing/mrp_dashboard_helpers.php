<?php

if (!function_exists('gw_mrp_dashboard_scalar_count')) {
    function gw_mrp_dashboard_scalar_count($conn, $sql)
    {
        $result = $conn->query($sql);
        if ($result && ($row = $result->fetch_row())) {
            return (int)($row[0] ?? 0);
        }
        return 0;
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

        $indentsUnderConfirmation = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COUNT(*) FROM mrp_indents_confirmation WHERE UPPER(COALESCE(confirmation_status, '')) = 'PENDING'"
        );
        $latestPendingIndentId = gw_mrp_dashboard_scalar_count(
            $conn,
            "SELECT COALESCE(MAX(id), 0) FROM mrp_indents_confirmation WHERE UPPER(COALESCE(confirmation_status, '')) = 'PENDING'"
        );

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

        return [
            'po_received' => $poReceived,
            'po_processed' => $poProcessed,
            'total_wo' => $totalWo,
            'total_material' => $totalMaterial,
            'indents_under_confirmation' => $indentsUnderConfirmation,
            'latest_pending_indent_id' => $latestPendingIndentId,
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
