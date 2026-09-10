<?php

define('DISPATCH_INVOICE_START', 2601);
define('DISPATCH_CHALLAN_START', 26001);
define('DISPATCH_CHALLAN_OFFSET', 23400);
define('DISPATCH_INVOICE_PREFIX', 'HKP-');

function dispatch_ensure_sales_challan_column($conn) {
    $chk = $conn->query("SHOW COLUMNS FROM sales LIKE 'challan_no'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE sales ADD COLUMN challan_no VARCHAR(50) NULL DEFAULT NULL");
    }
    $chkPo = $conn->query("SHOW COLUMNS FROM sales LIKE 'po_no'");
    if ($chkPo && $chkPo->num_rows === 0) {
        $conn->query("ALTER TABLE sales ADD COLUMN po_no VARCHAR(100) NULL DEFAULT NULL");
    }
}

function dispatch_parse_invoice_serial($invoiceNo) {
    $s = trim((string)$invoiceNo);
    if ($s === '') {
        return 0;
    }
    if (preg_match('/(\d+)$/', $s, $m)) {
        return (int)$m[1];
    }
    return 0;
}

function dispatch_parse_challan_serial($challanNo) {
    $digits = preg_replace('/\D/', '', (string)$challanNo);
    if ($digits === '') {
        return 0;
    }
    $n = (int)$digits;
    if ($n >= DISPATCH_CHALLAN_START) {
        return $n - DISPATCH_CHALLAN_OFFSET;
    }
    if ($n >= DISPATCH_INVOICE_START) {
        return $n;
    }
    return 0;
}

function dispatch_get_max_used_serial($conn, $plantId) {
    $max = DISPATCH_INVOICE_START - 1;
    $pid = $conn->real_escape_string(trim((string)$plantId));

    $queries = array(
        "SELECT Invoice_no AS num FROM tax_invoice WHERE plant_id = '$pid' AND Invoice_no IS NOT NULL AND Invoice_no != ''",
        "SELECT Invoice_no AS num FROM sales WHERE plant_id = '$pid' AND Invoice_no IS NOT NULL AND Invoice_no != ''",
    );

    dispatch_ensure_sales_challan_column($conn);
    $queries[] = "SELECT challan_no AS num FROM sales WHERE plant_id = '$pid' AND challan_no IS NOT NULL AND challan_no != ''";

    foreach ($queries as $sql) {
        $res = $conn->query($sql);
        if (!$res) {
            continue;
        }
        while ($row = $res->fetch_assoc()) {
            $val = trim((string)($row['num'] ?? ''));
            $serial = dispatch_parse_invoice_serial($val);
            if ($serial <= 0) {
                $serial = dispatch_parse_challan_serial($val);
            }
            if ($serial > $max) {
                $max = $serial;
            }
        }
    }

    return $max;
}

function dispatch_numbers_from_serial($serial) {
    $serial = (int)$serial;
    return array(
        'invoice_serial' => $serial,
        'invoice_no' => DISPATCH_INVOICE_PREFIX . $serial,
        'challan_no' => (string)($serial + DISPATCH_CHALLAN_OFFSET),
    );
}

function dispatch_challan_exists($conn, $plantId, $challanNo, $excludeSalesId = 0) {
    $ch = trim((string)$challanNo);
    if ($ch === '') {
        return false;
    }
    dispatch_ensure_sales_challan_column($conn);
    $pid = $conn->real_escape_string(trim((string)$plantId));
    $chEsc = $conn->real_escape_string($ch);
    $exclude = (int)$excludeSalesId;
    $excludeSql = $exclude > 0 ? " AND id != '$exclude'" : '';
    $res = $conn->query("SELECT id FROM sales WHERE plant_id = '$pid' AND challan_no = '$chEsc' $excludeSql LIMIT 1");
    return $res && $res->num_rows > 0;
}

function dispatch_invoice_exists($conn, $plantId, $invoiceNo, $excludeSalesId = 0, $excludeTaxInvoiceId = 0) {
    $inv = trim((string)$invoiceNo);
    if ($inv === '') {
        return false;
    }
    $pid = $conn->real_escape_string(trim((string)$plantId));
    $invEsc = $conn->real_escape_string($inv);
    $excludeSales = (int)$excludeSalesId;
    $excludeTax = (int)$excludeTaxInvoiceId;
    $salesExclude = $excludeSales > 0 ? " AND id != '$excludeSales'" : '';
    $taxExclude = $excludeTax > 0 ? " AND id != '$excludeTax'" : '';

    $taxRes = $conn->query("SELECT id FROM tax_invoice WHERE plant_id = '$pid' AND Invoice_no = '$invEsc' $taxExclude LIMIT 1");
    if ($taxRes && $taxRes->num_rows > 0) {
        return true;
    }

    $salesRes = $conn->query("SELECT id FROM sales WHERE plant_id = '$pid' AND Invoice_no = '$invEsc' $salesExclude LIMIT 1");
    return $salesRes && $salesRes->num_rows > 0;
}

function dispatch_find_next_available_numbers($conn, $plantId) {
    $serial = dispatch_get_max_used_serial($conn, $plantId) + 1;
    if ($serial < DISPATCH_INVOICE_START) {
        $serial = DISPATCH_INVOICE_START;
    }
    for ($i = 0; $i < 500; $i++) {
        $nums = dispatch_numbers_from_serial($serial);
        if (!dispatch_challan_exists($conn, $plantId, $nums['challan_no'])
            && !dispatch_invoice_exists($conn, $plantId, $nums['invoice_no'])) {
            return $nums;
        }
        $serial++;
    }
    return dispatch_numbers_from_serial($serial);
}

function dispatch_get_next_numbers($conn, $plantId) {
    return dispatch_find_next_available_numbers($conn, $plantId);
}

function dispatch_invoice_from_challan($challanNo) {
    $serial = dispatch_parse_challan_serial($challanNo);
    if ($serial < DISPATCH_INVOICE_START) {
        return '';
    }
    return DISPATCH_INVOICE_PREFIX . $serial;
}

function dispatch_challan_from_invoice($invoiceNo) {
    $serial = dispatch_parse_invoice_serial($invoiceNo);
    if ($serial < DISPATCH_INVOICE_START) {
        return '';
    }
    return (string)($serial + DISPATCH_CHALLAN_OFFSET);
}

function dispatch_apply_invoice_for_sales_row(&$row) {
    $challan = trim((string)($row['challan_no'] ?? ''));
    $invoiceFromChallan = dispatch_invoice_from_challan($challan);
    if ($invoiceFromChallan !== '') {
        $row['Invoice_no'] = $invoiceFromChallan;
        return;
    }
    $existing = trim((string)($row['Invoice_no'] ?? ''));
    if ($existing !== '') {
        if ($challan === '') {
            $derivedChallan = dispatch_challan_from_invoice($existing);
            if ($derivedChallan !== '') {
                $row['challan_no'] = $derivedChallan;
            }
        }
        return;
    }
    $serial = dispatch_parse_challan_serial($challan);
    if ($serial >= DISPATCH_INVOICE_START) {
        $row['Invoice_no'] = DISPATCH_INVOICE_PREFIX . $serial;
    }
}

function dispatch_ensure_sales_dispatch_numbers($conn, $plantId, $row) {
    if (!is_array($row) || empty($row['id'])) {
        return $row;
    }

    dispatch_ensure_sales_challan_column($conn);
    $salesId = (int)$row['id'];
    $pidEsc = $conn->real_escape_string(trim((string)$plantId));
    $challan = trim((string)($row['challan_no'] ?? ''));
    $invoice = trim((string)($row['Invoice_no'] ?? ''));

    if ($challan === '' && $invoice === '') {
        $nums = dispatch_find_next_available_numbers($conn, $plantId);
        $chEsc = $conn->real_escape_string($nums['challan_no']);
        $invEsc = $conn->real_escape_string($nums['invoice_no']);
        $conn->query("UPDATE sales SET challan_no = '$chEsc', Invoice_no = '$invEsc' WHERE id = '$salesId' AND plant_id = '$pidEsc'");
        $row['challan_no'] = $nums['challan_no'];
        $row['Invoice_no'] = $nums['invoice_no'];
        return $row;
    }

    if ($challan === '' && $invoice !== '') {
        $derivedChallan = dispatch_challan_from_invoice($invoice);
        if ($derivedChallan !== '') {
            $chEsc = $conn->real_escape_string($derivedChallan);
            $conn->query("UPDATE sales SET challan_no = '$chEsc' WHERE id = '$salesId' AND plant_id = '$pidEsc'");
            $row['challan_no'] = $derivedChallan;
        }
    }

    if ($challan !== '' && $invoice === '') {
        $invoiceFromChallan = dispatch_invoice_from_challan($challan);
        if ($invoiceFromChallan !== '') {
            $invEsc = $conn->real_escape_string($invoiceFromChallan);
            $conn->query("UPDATE sales SET Invoice_no = '$invEsc' WHERE id = '$salesId' AND plant_id = '$pidEsc'");
            $row['Invoice_no'] = $invoiceFromChallan;
        }
    }

    dispatch_apply_invoice_for_sales_row($row);
    return $row;
}
