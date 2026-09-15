<?php
/**
 * MRP Phase 9 — smoke checks for Phases 1–8 (read-only + optional light seed).
 * Endpoint: mrp/mrp_phase_smoke.php?type=runMrpPhaseSmoke&plant_id=1126&token=...
 */

if (!function_exists('gw_mrp_smoke_ok')) {
    function gw_mrp_smoke_ok($name, $pass, $detail = '', $meta = array()) {
        return array(
            'phase' => $name,
            'pass' => (bool)$pass,
            'detail' => (string)$detail,
            'meta' => is_array($meta) ? $meta : array(),
        );
    }
}

if (!function_exists('gw_run_mrp_phase_smoke')) {
    function gw_run_mrp_phase_smoke($conn, $params = array()) {
        $plantId = trim((string)($params['plant_id'] ?? '1126'));
        $wo = trim((string)($params['workorder_no'] ?? 'BO002'));
        $material = trim((string)($params['material_code'] ?? 'RM0129'));
        $results = array();

        // Phase 1 — demand_source column + sample data (+ normalize parity)
        $p1 = false;
        $p1Detail = '';
        try {
            $colOk = false;
            $sample = null;
            $col = @$conn->query("SHOW COLUMNS FROM split_planning_qty LIKE 'demand_source'");
            $colOk = ($col && $col->num_rows > 0);
            if ($colOk) {
                $rs = @$conn->query(
                    "SELECT id, order_no, product_code, demand_source FROM split_planning_qty
                     WHERE TRIM(COALESCE(demand_source,'')) <> '' ORDER BY id DESC LIMIT 1"
                );
                if ($rs && $rs->num_rows > 0) {
                    $sample = $rs->fetch_assoc();
                }
            }
            // Parity with po_normalize_demand_source (Forecast aliases)
            $normTest = strtoupper(trim('Forcast'));
            if (in_array($normTest, array('FORCAST', 'FORECAST', 'FC'), true)) {
                $normTest = 'FORECAST';
            }
            $hasFn = function_exists('po_normalize_demand_source');
            if ($hasFn) {
                $normTest = po_normalize_demand_source('Forcast');
            }
            $p1 = $colOk && is_array($sample) && in_array(strtoupper((string)($sample['demand_source'] ?? '')), array('FORECAST', 'CONFIRMED'), true);
            $p1Detail = 'col=' . ($colOk ? 'yes' : 'no')
                . '; sample=' . ($sample['demand_source'] ?? 'none')
                . '; order=' . ($sample['order_no'] ?? '')
                . '; normalize_forcast=' . $normTest;
            $results[] = gw_mrp_smoke_ok('1_FORECAST_CONFIRMED', $p1, $p1Detail, array('sample' => $sample));
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('1_FORECAST_CONFIRMED', false, $e->getMessage());
        }

        // Phase 2 — material availability
        try {
            $ok = false;
            $detail = 'helper missing';
            $meta = array();
            if (function_exists('gw_get_mrp_material_availability_detail')) {
                $av = gw_get_mrp_material_availability_detail($conn, $material, array('plant_id' => $plantId));
                $ok = is_array($av) && (($av['status'] ?? '') === 'success') && isset($av['available_store_qty']);
                $detail = $ok
                    ? ('availability for ' . $material . ' store=' . ($av['available_store_qty'] ?? ''))
                    : ('availability failed: ' . ($av['message'] ?? 'bad shape'));
                $meta = array(
                    'material_code' => $av['material_code'] ?? $material,
                    'material_name' => $av['material_name'] ?? '',
                    'available_store_qty' => $av['available_store_qty'] ?? null,
                    'open_po_qty' => $av['open_po_qty'] ?? null,
                    'open_indent_qty' => $av['open_indent_qty'] ?? null,
                );
            }
            $results[] = gw_mrp_smoke_ok('2_MATERIAL_AVAILABILITY', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('2_MATERIAL_AVAILABILITY', false, $e->getMessage());
        }

        // Phase 3 — shortage approval row
        try {
            $ok = false;
            $detail = 'table/row missing';
            $meta = array();
            $t = @$conn->query("SHOW TABLES LIKE 'mrp_shortage_approval'");
            if ($t && $t->num_rows > 0) {
                $rs = @$conn->query(
                    "SELECT id, material_code, approval_status, net_shortage_qty FROM mrp_shortage_approval
                     WHERE material_code = '".$conn->real_escape_string($material)."'
                     ORDER BY id DESC LIMIT 1"
                );
                if ($rs && $rs->num_rows > 0) {
                    $row = $rs->fetch_assoc();
                    $ok = true;
                    $detail = 'approval #'.$row['id'].' '.$row['approval_status'];
                    $meta = $row;
                }
            }
            $results[] = gw_mrp_smoke_ok('3_SHORTAGE_APPROVAL', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('3_SHORTAGE_APPROVAL', false, $e->getMessage());
        }

        // Phase 4 — indent lock
        try {
            $ok = false;
            $detail = 'no locked indent';
            $meta = array();
            $t = @$conn->query("SHOW TABLES LIKE 'mrp_indents_confirmation'");
            if ($t && $t->num_rows > 0) {
                $rs = @$conn->query(
                    "SELECT id, material_code, confirmation_status, is_locked, lock_remark
                     FROM mrp_indents_confirmation
                     WHERE material_code = '".$conn->real_escape_string($material)."'
                       AND (COALESCE(is_locked,0)=1 OR UPPER(COALESCE(confirmation_status,''))='LOCKED')
                     ORDER BY id DESC LIMIT 1"
                );
                if ($rs && $rs->num_rows > 0) {
                    $row = $rs->fetch_assoc();
                    $ok = true;
                    $detail = 'locked confirmation #'.$row['id'];
                    $meta = $row;
                }
            }
            $results[] = gw_mrp_smoke_ok('4_INDENT_LOCK', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('4_INDENT_LOCK', false, $e->getMessage());
        }

        // Phase 5 — planning horizon IMMEDIATE
        try {
            $ok = false;
            $detail = 'no IMMEDIATE WO';
            $meta = array();
            if (function_exists('gw_mrp_get_wo_planning_horizons')) {
                $hz = gw_mrp_get_wo_planning_horizons($conn, array(
                    'plant_id' => $plantId,
                    'horizon' => 'IMMEDIATE',
                    'search' => $wo,
                    'limit' => 20,
                ));
                $rows = $hz['rows'] ?? array();
                $ok = is_array($rows) && count($rows) > 0;
                $detail = $ok ? ('IMMEDIATE rows=' . count($rows)) : 'no IMMEDIATE rows for '.$wo;
                $meta = array('counts' => $hz['counts'] ?? array(), 'first' => $rows[0] ?? null);
            } else {
                $detail = 'horizon helper missing';
            }
            $results[] = gw_mrp_smoke_ok('5_PLANNING_HORIZON', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('5_PLANNING_HORIZON', false, $e->getMessage());
        }

        // Phase 6 — line booking history versions
        try {
            $ok = false;
            $detail = 'no history versions';
            $meta = array();
            if (function_exists('gw_mrp_get_line_booking_versions')) {
                $hv = gw_mrp_get_line_booking_versions($conn, array('workorder_no' => $wo, 'limit' => 50));
                $rows = $hv['rows'] ?? array();
                $ok = is_array($rows) && count($rows) >= 1;
                $detail = $ok ? ('versions=' . count($rows) . ' for '.$wo) : 'no versions for '.$wo;
                $meta = array('total' => $hv['total'] ?? 0, 'latest' => $rows[0] ?? null);
            } else {
                $t = @$conn->query("SHOW TABLES LIKE 'mrp_line_booking_history'");
                if ($t && $t->num_rows > 0) {
                    $c = @$conn->query(
                        "SELECT COUNT(*) c FROM mrp_line_booking_history
                         WHERE workorder_no='".$conn->real_escape_string($wo)."'"
                    );
                    $n = ($c && ($row = $c->fetch_assoc())) ? (int)$row['c'] : 0;
                    $ok = $n >= 1;
                    $detail = 'history count='.$n;
                    $meta = array('count' => $n);
                } else {
                    $detail = 'history helper/table missing';
                }
            }
            $results[] = gw_mrp_smoke_ok('6_LINE_BOOKING_HISTORY', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('6_LINE_BOOKING_HISTORY', false, $e->getMessage());
        }

        // Phase 7 — cancel recalc
        try {
            $ok = false;
            $detail = 'no cancel recalc';
            $meta = array();
            if (function_exists('gw_mrp_get_cancel_recalc_log')) {
                $log = gw_mrp_get_cancel_recalc_log($conn, array('material_code' => $material, 'limit' => 10));
                $rows = $log['rows'] ?? array();
                $ok = is_array($rows) && count($rows) >= 1;
                $detail = $ok ? ('recalc rows=' . count($rows)) : 'no recalc for '.$material;
                $meta = array('latest' => $rows[0] ?? null);
            }
            $results[] = gw_mrp_smoke_ok('7_CANCEL_RECALC', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('7_CANCEL_RECALC', false, $e->getMessage());
        }

        // Phase 8 — traceability last stage
        try {
            $ok = false;
            $detail = 'trace failed';
            $meta = array();
            if (function_exists('gw_get_mrp_traceability')) {
                $tr = gw_get_mrp_traceability($conn, array(
                    'workorder_no' => $wo,
                    'material_code' => $material,
                    'plant_id' => $plantId,
                    'write_audit' => '0',
                ));
                $stages = $tr['stages'] ?? array();
                $names = array();
                foreach ($stages as $s) {
                    $names[] = $s['stage'] ?? '';
                }
                $ok = (($tr['status'] ?? '') === 'success')
                    && count($stages) > 0
                    && !empty($tr['last_stage']);
                $detail = $ok
                    ? ('stages=' . count($stages) . ' last=' . ($tr['last_stage']['stage'] ?? ''))
                    : ('trace status=' . ($tr['status'] ?? 'n/a'));
                $meta = array(
                    'total_stages' => count($stages),
                    'stage_names' => array_values(array_unique($names)),
                    'last_stage' => $tr['last_stage'] ?? null,
                );
            }
            $results[] = gw_mrp_smoke_ok('8_TRACEABILITY_KPI', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('8_TRACEABILITY_KPI', false, $e->getMessage());
        }

        // KPI strip fields present
        try {
            $ok = false;
            $detail = 'dashboard helper missing';
            $meta = array();
            if (function_exists('gw_get_mrp_dashboard_stats')) {
                $kpi = gw_get_mrp_dashboard_stats($conn, array('plant_id' => $plantId));
                $required = array(
                    'shortage_approvals_pending',
                    'indents_locked',
                    'planning_immediate',
                    'line_booking_history_versions',
                    'cancel_recalc_events',
                );
                $missing = array();
                foreach ($required as $k) {
                    if (!array_key_exists($k, $kpi)) {
                        $missing[] = $k;
                    }
                }
                $ok = count($missing) === 0;
                $detail = $ok ? 'KPI keys present' : ('missing: ' . implode(',', $missing));
                $meta = array(
                    'planning_immediate' => $kpi['planning_immediate'] ?? null,
                    'indents_locked' => $kpi['indents_locked'] ?? null,
                    'line_booking_history_versions' => $kpi['line_booking_history_versions'] ?? null,
                    'cancel_recalc_events' => $kpi['cancel_recalc_events'] ?? null,
                    'shortage_approvals_pending' => $kpi['shortage_approvals_pending'] ?? null,
                );
            }
            $results[] = gw_mrp_smoke_ok('8B_KPI_KEYS', $ok, $detail, $meta);
        } catch (Throwable $e) {
            $results[] = gw_mrp_smoke_ok('8B_KPI_KEYS', false, $e->getMessage());
        }

        $passed = 0;
        $failed = 0;
        foreach ($results as $r) {
            if (!empty($r['pass'])) {
                $passed++;
            } else {
                $failed++;
            }
        }

        return array(
            'status' => $failed === 0 ? 'success' : 'failed',
            'message' => $failed === 0
                ? 'All MRP phase smoke checks passed'
                : ($failed . ' smoke check(s) failed'),
            'plant_id' => $plantId,
            'workorder_no' => $wo,
            'material_code' => $material,
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($results),
            'checks' => $results,
        );
    }
}
