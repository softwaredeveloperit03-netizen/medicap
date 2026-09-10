<?php
/**
 * One-shot: copy missing marketing/master helpers from sibling phpzuma into phpmedicap.
 * Open in browser (once) after upload:
 *   .../phpmedicap/mrp/sync_marketing_helpers_from_zuma.php?key=medicap_heal_2026
 *
 * Safe to re-run; only copies when destination file is missing (or ?force=1).
 */
header('Content-Type: application/json; charset=utf-8');
$key = $_GET['key'] ?? '';
if ($key !== 'medicap_heal_2026') {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    exit;
}

$force = isset($_GET['force']) && $_GET['force'] === '1';
$medicapRoot = dirname(__DIR__); // phpmedicap
$zumaRoots = array(
    dirname($medicapRoot) . '/phpzuma',
    dirname($medicapRoot, 1) . '/phpzuma',
);

$zumaRoot = null;
foreach ($zumaRoots as $cand) {
    if (is_dir($cand)) {
        $zumaRoot = $cand;
        break;
    }
}
if ($zumaRoot === null) {
    echo json_encode(array('status' => 'error', 'message' => 'phpzuma sibling folder not found', 'tried' => $zumaRoots));
    exit;
}

$files = array(
    'master/marketing_po_helpers.php',
    'marketing/mrp_indents_confirmation_helpers.php',
    'marketing/mrp_shortages_log_helpers.php',
    'marketing/mrp_indent_status_log_helpers.php',
    'marketing/mrp_dashboard_helpers.php',
    'marketing/processing_po_helpers.php',
    'marketing/wo_analysis_proceed_helpers.php',
    'marketing/wo_plan_status_helpers.php',
    'marketing/mrp_audit_log_helpers.php',
    'marketing/change_forecast_plan_helpers.php',
    'marketing/can_planned_wo_helpers.php',
);

$copied = array();
$skipped = array();
$missing = array();

foreach ($files as $rel) {
    $src = $zumaRoot . '/' . $rel;
    $dst = $medicapRoot . '/' . $rel;
    if (!is_file($src)) {
        $missing[] = $rel;
        continue;
    }
    $dstDir = dirname($dst);
    if (!is_dir($dstDir)) {
        @mkdir($dstDir, 0755, true);
    }
    if (is_file($dst) && !$force) {
        $skipped[] = $rel;
        continue;
    }
    if (@copy($src, $dst)) {
        $copied[] = $rel;
    } else {
        $missing[] = $rel . ' (copy failed)';
    }
}

echo json_encode(array(
    'status' => 'success',
    'zuma_root' => $zumaRoot,
    'medicap_root' => $medicapRoot,
    'copied' => $copied,
    'skipped_existing' => $skipped,
    'failed_or_missing' => $missing,
    'next' => 'Reload /marketing/po/processing — getPendingProcessingPOs should return JSON instead of HTTP 500.',
));
