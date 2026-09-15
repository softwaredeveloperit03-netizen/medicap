<?php
/**
 * MRP Phase 8 — end-to-end traceability timeline for WO / material / order.
 */

if (!function_exists('gw_mrp_trace_table_exists')) {
    function gw_mrp_trace_table_exists($conn, $tableName) {
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

if (!function_exists('gw_mrp_trace_stage')) {
    function gw_mrp_trace_stage($stage, $status, $refId, $at, $by, $summary, $detail = array()) {
        return array(
            'stage' => (string)$stage,
            'status' => (string)$status,
            'ref_id' => $refId,
            'at' => $at !== null && $at !== '' ? (string)$at : null,
            'by' => $by !== null && $by !== '' ? (string)$by : null,
            'summary' => (string)$summary,
            'detail' => is_array($detail) ? $detail : array(),
        );
    }
}

if (!function_exists('gw_get_mrp_traceability')) {
    function gw_get_mrp_traceability($conn, $filters = array()) {
        $workorderNo = trim((string)($filters['workorder_no'] ?? ''));
        $materialCode = trim((string)($filters['material_code'] ?? ''));
        $orderNo = trim((string)($filters['order_no'] ?? ''));
        $plantId = trim((string)($filters['plant_id'] ?? ''));
        $writeAudit = !isset($filters['write_audit']) || (string)$filters['write_audit'] !== '0';

        if ($workorderNo === '' && $materialCode === '' && $orderNo === '') {
            return array(
                'status' => 'error',
                'message' => 'Provide workorder_no and/or material_code and/or order_no',
                'stages' => array(),
                'total_stages' => 0,
            );
        }

        $stages = array();
        $context = array(
            'workorder_no' => $workorderNo,
            'material_code' => $materialCode,
            'order_no' => $orderNo,
            'plant_id' => $plantId,
            'product_code' => '',
            'product_name' => '',
        );

        // 1) Work order header + planning horizon
        if ($workorderNo !== '' || $orderNo !== '') {
            $where = array();
            if ($workorderNo !== '') {
                $where[] = "workorder_no = '".$conn->real_escape_string($workorderNo)."'";
            }
            if ($orderNo !== '') {
                $where[] = "order_no = '".$conn->real_escape_string($orderNo)."'";
            }
            if ($plantId !== '') {
                $p = $conn->real_escape_string($plantId);
                $where[] = "(plant_id = '$p' OR plant_id IS NULL OR TRIM(IFNULL(plant_id,'')) = '')";
            }
            $sql = "SELECT * FROM Work_order_materials WHERE ".implode(' AND ', $where)." ORDER BY id DESC LIMIT 5";
            try {
                $res = $conn->query($sql);
                if ($res) {
                    while ($wo = $res->fetch_assoc()) {
                        if ($workorderNo === '' && !empty($wo['workorder_no'])) {
                            $workorderNo = (string)$wo['workorder_no'];
                            $context['workorder_no'] = $workorderNo;
                        }
                        if ($orderNo === '' && !empty($wo['order_no'])) {
                            $orderNo = (string)$wo['order_no'];
                            $context['order_no'] = $orderNo;
                        }
                        $context['product_code'] = (string)($wo['product_code'] ?? '');
                        if (function_exists('gw_mrp_attach_planning_horizon')) {
                            gw_mrp_attach_planning_horizon($wo);
                        }
                        $stages[] = gw_mrp_trace_stage(
                            'WORK_ORDER',
                            (string)($wo['status'] ?? ''),
                            $wo['id'] ?? null,
                            $wo['Wo_Generated_on'] ?? ($wo['entryOn'] ?? null),
                            $wo['Wo_Generated_by'] ?? ($wo['entryBy'] ?? null),
                            'WO '.$wo['workorder_no'].' / '.$wo['order_no'].' — '.$wo['product_code'],
                            array(
                                'workorder_no' => $wo['workorder_no'] ?? '',
                                'order_no' => $wo['order_no'] ?? '',
                                'product_code' => $wo['product_code'] ?? '',
                                'batch_size' => $wo['batch_size'] ?? '',
                                'planUnit' => $wo['planUnit'] ?? '',
                            )
                        );
                        $hz = strtoupper(trim((string)($wo['planning_horizon'] ?? '')));
                        if ($hz !== '') {
                            $stages[] = gw_mrp_trace_stage(
                                'PLANNING_HORIZON',
                                $hz,
                                $wo['id'] ?? null,
                                $wo['planning_horizon_set_on'] ?? null,
                                $wo['planning_horizon_set_by_name'] ?? ($wo['planning_horizon_set_by'] ?? null),
                                ($wo['planning_horizon_label'] ?? $hz)
                                    .' ('.($wo['planning_horizon_source'] ?? 'AUTO').')'
                                    .' ref '.($wo['planning_horizon_ref_date'] ?? ''),
                                array(
                                    'planning_horizon' => $hz,
                                    'planning_horizon_source' => $wo['planning_horizon_source'] ?? '',
                                    'planning_horizon_days' => $wo['planning_horizon_days'] ?? null,
                                    'planning_horizon_ref_date' => $wo['planning_horizon_ref_date'] ?? null,
                                    'expected_production_start_date' => $wo['expected_production_start_date'] ?? null,
                                )
                            );
                        }
                    }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        // 2) Shortage approval (material)
        if ($materialCode !== '' && gw_mrp_trace_table_exists($conn, 'mrp_shortage_approval')) {
            $code = $conn->real_escape_string($materialCode);
            try {
                $res = $conn->query(
                    "SELECT * FROM mrp_shortage_approval WHERE material_code = '$code' ORDER BY id DESC LIMIT 10"
                );
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $stages[] = gw_mrp_trace_stage(
                            'SHORTAGE_APPROVAL',
                            (string)($row['approval_status'] ?? ''),
                            $row['id'] ?? null,
                            $row['submitted_at'] ?? ($row['approved_at'] ?? ($row['updated_at'] ?? null)),
                            $row['approved_by_name'] ?? ($row['submitted_by_name'] ?? null),
                            'Shortage approval '.$row['material_code'].' — '.$row['approval_status']
                                .' (net '.(string)($row['net_shortage_qty'] ?? '').')',
                            array(
                                'material_code' => $row['material_code'] ?? '',
                                'net_shortage_qty' => $row['net_shortage_qty'] ?? null,
                                'revision_no' => $row['revision_no'] ?? null,
                            )
                        );
                    }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        // 3) Indent confirmation + lock
        if (gw_mrp_trace_table_exists($conn, 'mrp_indents_confirmation')) {
            $parts = array();
            if ($materialCode !== '') {
                $parts[] = "material_code = '".$conn->real_escape_string($materialCode)."'";
            }
            if ($workorderNo !== '') {
                $parts[] = "workorder_no = '".$conn->real_escape_string($workorderNo)."'";
            }
            if ($orderNo !== '') {
                $parts[] = "order_no = '".$conn->real_escape_string($orderNo)."'";
            }
            if (count($parts) > 0) {
                try {
                    $res = $conn->query(
                        "SELECT * FROM mrp_indents_confirmation WHERE (".implode(' OR ', $parts).")
                         ORDER BY id DESC LIMIT 20"
                    );
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $locked = ((int)($row['is_locked'] ?? 0) === 1)
                                || strtoupper(trim((string)($row['confirmation_status'] ?? ''))) === 'LOCKED';
                            $stages[] = gw_mrp_trace_stage(
                                'INDENT_CONFIRMATION',
                                (string)($row['confirmation_status'] ?? ''),
                                $row['id'] ?? null,
                                $row['updated_at'] ?? ($row['indent_raised_date'] ?? ($row['created_at'] ?? null)),
                                $row['updated_by_name'] ?? ($row['indent_raised_by_name'] ?? null),
                                'Indent confirm #'.$row['id'].' '.$row['material_code']
                                    .' — '.$row['confirmation_status']
                                    .($locked ? ' (LOCKED)' : ''),
                                array(
                                    'material_code' => $row['material_code'] ?? '',
                                    'raised_indent_qty' => $row['raised_indent_qty'] ?? null,
                                    'is_locked' => $locked ? 1 : 0,
                                    'lock_revision_no' => $row['lock_revision_no'] ?? null,
                                    'workorder_no' => $row['workorder_no'] ?? '',
                                )
                            );
                            if ($locked) {
                                $stages[] = gw_mrp_trace_stage(
                                    'INDENT_LOCK',
                                    'LOCKED',
                                    $row['id'] ?? null,
                                    $row['locked_at'] ?? null,
                                    $row['locked_by_name'] ?? ($row['locked_by'] ?? null),
                                    'Indent #'.$row['id'].' locked'
                                        .(!empty($row['lock_remark']) ? ': '.$row['lock_remark'] : ''),
                                    array(
                                        'lock_revision_no' => $row['lock_revision_no'] ?? null,
                                        'lock_remark' => $row['lock_remark'] ?? '',
                                    )
                                );
                            }
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }

        // 4) Cancel recalc
        if (gw_mrp_trace_table_exists($conn, 'mrp_cancel_recalc_log')) {
            $parts = array();
            if ($materialCode !== '') {
                $parts[] = "material_code = '".$conn->real_escape_string($materialCode)."'";
            }
            if ($workorderNo !== '') {
                $parts[] = "workorder_no = '".$conn->real_escape_string($workorderNo)."'";
            }
            if (count($parts) > 0) {
                try {
                    $res = $conn->query(
                        "SELECT * FROM mrp_cancel_recalc_log WHERE (".implode(' OR ', $parts).")
                         ORDER BY id DESC LIMIT 20"
                    );
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $stages[] = gw_mrp_trace_stage(
                                'CANCEL_RECALC',
                                (string)($row['event_type'] ?? 'INDENT_CANCEL'),
                                $row['id'] ?? null,
                                $row['event_datetime'] ?? null,
                                $row['action_by_name'] ?? ($row['action_by'] ?? null),
                                'Cancel recalc #'.$row['id'].' '.$row['material_code']
                                    .' cancelled_qty='.(string)($row['cancelled_qty'] ?? ''),
                                array(
                                    'confirmation_id' => $row['confirmation_id'] ?? null,
                                    'cancelled_qty' => $row['cancelled_qty'] ?? null,
                                    'open_indent_qty_after' => $row['open_indent_qty_after'] ?? null,
                                    'cancelled_indent_qty_total' => $row['cancelled_indent_qty_total'] ?? null,
                                    'net_shortage_qty' => $row['net_shortage_qty'] ?? null,
                                )
                            );
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }

        // 5) Line booking + history versions
        if ($workorderNo !== '' && gw_mrp_trace_table_exists($conn, 'line_booking')) {
            $woEsc = $conn->real_escape_string($workorderNo);
            try {
                $res = $conn->query(
                    "SELECT * FROM line_booking WHERE workorder_no = '$woEsc' ORDER BY id DESC LIMIT 10"
                );
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $stages[] = gw_mrp_trace_stage(
                            'LINE_BOOKING',
                            (string)($row['status'] ?? ''),
                            $row['id'] ?? null,
                            $row['updated_date'] ?? ($row['entry_date'] ?? null),
                            $row['updated_by'] ?? ($row['entry_by'] ?? null),
                            'Line booking #'.$row['id'].' line '.$row['line_no']
                                .' — '.$row['status'],
                            array(
                                'linemaster_id' => $row['linemaster_id'] ?? null,
                                'line_no' => $row['line_no'] ?? '',
                                'booking_start_date' => $row['booking_start_date'] ?? null,
                                'booking_end_date' => $row['booking_end_date'] ?? null,
                            )
                        );
                    }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        if (($workorderNo !== '') && gw_mrp_trace_table_exists($conn, 'mrp_line_booking_history')) {
            $woEsc = $conn->real_escape_string($workorderNo);
            try {
                $res = $conn->query(
                    "SELECT * FROM mrp_line_booking_history WHERE workorder_no = '$woEsc'
                     ORDER BY version_no DESC, id DESC LIMIT 30"
                );
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $stages[] = gw_mrp_trace_stage(
                            'LINE_BOOKING_HISTORY',
                            (string)($row['action_type'] ?? 'SNAPSHOT'),
                            $row['id'] ?? null,
                            $row['changed_on'] ?? null,
                            $row['changed_by_name'] ?? ($row['changed_by'] ?? null),
                            'Booking history v'.$row['version_no']
                                .' booking#'.$row['booking_id']
                                .' — '.$row['action_type'],
                            array(
                                'booking_id' => $row['booking_id'] ?? null,
                                'version_no' => $row['version_no'] ?? null,
                                'line_no' => $row['line_no'] ?? '',
                                'booking_status' => $row['booking_status'] ?? '',
                                'change_remark' => $row['change_remark'] ?? '',
                            )
                        );
                    }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        // 6) Optional recent audit rows
        if (function_exists('gw_get_mrp_audit_log')) {
            try {
                $auditRows = gw_get_mrp_audit_log($conn, array(
                    'workorder_no' => $workorderNo,
                    'material_code' => $materialCode,
                    'order_no' => $orderNo,
                    'plant_id' => $plantId,
                    'limit' => 25,
                ));
                if (is_array($auditRows)) {
                    foreach ($auditRows as $ar) {
                        $stages[] = gw_mrp_trace_stage(
                            'AUDIT_'.strtoupper((string)($ar['stage'] ?? 'EVENT')),
                            (string)($ar['event_status'] ?? ($ar['status_to'] ?? '')),
                            $ar['id'] ?? null,
                            $ar['event_datetime'] ?? null,
                            $ar['emp_name'] ?? ($ar['emp_id'] ?? null),
                            (string)($ar['event_type'] ?? ($ar['stage'] ?? 'audit'))
                                .(!empty($ar['remark']) ? ' — '.$ar['remark'] : ''),
                            array(
                                'source_screen' => $ar['source_screen'] ?? '',
                                'status_from' => $ar['status_from'] ?? '',
                                'status_to' => $ar['status_to'] ?? '',
                            )
                        );
                    }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        // Sort stages by datetime ascending when possible; keep stable for nulls at end of unknowns.
        usort($stages, function ($a, $b) {
            $ta = strtotime((string)($a['at'] ?? '')) ?: 0;
            $tb = strtotime((string)($b['at'] ?? '')) ?: 0;
            if ($ta === $tb) {
                return 0;
            }
            return ($ta < $tb) ? -1 : 1;
        });

        $lastStage = count($stages) > 0 ? $stages[count($stages) - 1] : null;

        if ($writeAudit && function_exists('gw_mrp_audit_log')) {
            try {
                @gw_mrp_audit_log($conn, array(
                    'stage' => 'TRACEABILITY',
                    'event_type' => 'TRACEABILITY_VIEW',
                    'event_status' => 'success',
                    'source_screen' => 'mrp_traceability',
                    'workorder_no' => $workorderNo,
                    'order_no' => $orderNo,
                    'material_code' => $materialCode,
                    'product_code' => $context['product_code'],
                    'plant_id' => $plantId,
                    'emp_id' => $_GET['emp_id'] ?? '',
                    'department' => $_GET['department'] ?? '',
                    'remark' => 'Traceability view ('.$workorderNo.' / '.$materialCode.')',
                    'event_detail' => array(
                        'total_stages' => count($stages),
                        'last_stage' => $lastStage,
                    ),
                ));
            } catch (Throwable $e) {
                // never break caller
            }
        }

        return array(
            'status' => 'success',
            'context' => $context,
            'stages' => $stages,
            'total_stages' => count($stages),
            'last_stage' => $lastStage,
        );
    }
}
