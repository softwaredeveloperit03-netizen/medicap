<?php
require_once __DIR__ . '/processing_laboratory_samples_funcs.php';

function pls_dispatch($conn, $action, $input, $plantIdRaw, $empIdRaw, $entry_date) {
    ensure_pls_schema($conn);
    $plantId = pls_esc($conn, $plantIdRaw);
    $empId = pls_esc($conn, $empIdRaw);
    if (!is_array($input)) {
        $input = array();
    }
    include __DIR__ . '/processing_laboratory_samples_dispatch.php';
}
