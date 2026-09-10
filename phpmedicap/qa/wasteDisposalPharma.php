<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$sql = "SELECT * FROM token WHERE token='" . $conn->real_escape_string($token) . "'";
$result = $conn->query($sql);
$_GET['emp_id'] = '';
$_GET['department'] = '';
$entry_date = date('Y-m-d H:i:s');

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET['token'], $row['key1'], $row['key2']);
        $string = explode('$', $string);
        $_GET['emp_id'] = $string[0];
        $_GET['department'] = $string[1];
        break;
    }

    $txt = '{"process":"FRONTEND","token":"' . $token . '","action":"' . ($_GET['type'] ?? '') . '","actiontime":"' . $entry_date . '","department":"' . $_GET['department'] . '","emp_id":"' . $_GET['emp_id'] . '","method":"' . $_SERVER['REQUEST_METHOD'] . '","REMOTE_ADDR":"' . $_SERVER['REMOTE_ADDR'] . '"}';
    file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    function esc($conn, $value)
    {
        return $conn->real_escape_string($value ?? '');
    }

    function decodeJsonField($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value ?? '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    function formatDateDisplay($value)
    {
        if (empty($value) || $value === '0000-00-00') {
            return '';
        }
        return date('d-m-Y', strtotime($value));
    }

    function dateFilterSql($fromDate, $toDate, $column)
    {
        if ($fromDate !== '' && $toDate !== '') {
            return " AND DATE($column) BETWEEN '$fromDate' AND '$toDate'";
        }
        return '';
    }

    function formHeaderHtml($title, $formNo, $sopRef = 'SOP-QA-022', $effectiveDate = 'APR 01 2025')
    {
        return '<h3 style="text-align:center;">' . htmlspecialchars($title) . '</h3>
        <table border="1" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr>
                <td style="width:15%;"><b>FORM NO.:</b></td>
                <td style="width:35%;">' . htmlspecialchars($formNo) . '</td>
                <td style="width:15%;"><b>REF:</b></td>
                <td style="width:35%;">' . htmlspecialchars($sopRef) . '</td>
            </tr>
            <tr>
                <td><b>REVISION NO.:</b></td>
                <td>00</td>
                <td><b>EFFECTIVE DATE:</b></td>
                <td>' . htmlspecialchars($effectiveDate) . '</td>
            </tr>
        </table><br>';
    }

    function sourceOptions()
    {
        return [
            'QC Lab',
            'Production',
            'Formulation (PD)',
            'Analytical Lab',
            'Packaging/ printed components',
            'Normal Waste',
        ];
    }

    function ensureWasteDisposalTable($conn)
    {
        $conn->query("CREATE TABLE IF NOT EXISTS waste_disposal_pharma (
            id INT(11) NOT NULL AUTO_INCREMENT,
            plant_id VARCHAR(50) NOT NULL,
            form_no VARCHAR(50) DEFAULT 'FQA-022-A',
            sop_ref VARCHAR(50) DEFAULT 'SOP-QA-022',
            record_date DATE DEFAULT NULL,
            source_of_waste LONGTEXT DEFAULT NULL,
            waste_rows LONGTEXT DEFAULT NULL,
            entry_by VARCHAR(100) DEFAULT NULL,
            entry_date DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function formatWasteRow($row)
    {
        $row['source_of_waste'] = decodeJsonField($row['source_of_waste'] ?? '[]');
        $row['waste_rows'] = decodeJsonField($row['waste_rows'] ?? '[]');
        return $row;
    }

    function buildWasteDisposalHtml($row)
    {
        $sources = is_array($row['source_of_waste']) ? $row['source_of_waste'] : [];
        $wasteRows = is_array($row['waste_rows']) ? $row['waste_rows'] : [];
        $html = formHeaderHtml(
            'WASTE DISPOSAL - PHARMACEUTICAL SOLIDS, LIQUIDS AND SOLVENTS',
            $row['form_no'] ?? 'FQA-022-A',
            $row['sop_ref'] ?? 'SOP-QA-022'
        );

        $html .= '<p style="font-weight:bold;text-align:center;">Waste Disposal Record</p>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:8px;">
            <tr>
                <td style="width:20%;"><b>Record Date:</b></td>
                <td style="width:30%;">' . formatDateDisplay($row['record_date'] ?? '') . '</td>
                <td style="width:20%;"><b>Entry By:</b></td>
                <td style="width:30%;">' . htmlspecialchars($row['entry_by'] ?? '') . '</td>
            </tr>
        </table><br>';

        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:7px;">
            <tr style="background-color:#DDDAD9;font-weight:bold;">
                <td colspan="6">Source of Waste</td>
            </tr>
            <tr>';
        foreach (sourceOptions() as $option) {
            $checked = in_array($option, $sources, true) ? '[X]' : '[ ]';
            $html .= '<td style="width:16%;">' . $checked . ' ' . htmlspecialchars($option) . '</td>';
        }
        $html .= '</tr></table><br>';

        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:7px;table-layout:fixed;">
            <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center;">
                <td style="width:28%;">Name of Waste Material</td>
                <td style="width:12%;">Approximate Weight (Kg)</td>
                <td style="width:14%;">Solid/Liquid/Solvent</td>
                <td style="width:12%;">Initials</td>
                <td style="width:12%;">Date</td>
                <td style="width:22%;">Comments</td>
            </tr>';

        if (count($wasteRows) === 0) {
            for ($i = 0; $i < 5; $i++) {
                $html .= '<tr nobr="true"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>';
            }
        } else {
            foreach ($wasteRows as $item) {
                $html .= '<tr nobr="true">
                    <td>' . htmlspecialchars($item['waste_material_name'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($item['approximate_weight_kg'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($item['waste_type'] ?? '') . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($item['initials'] ?? '') . '</td>
                    <td style="text-align:center;">' . formatDateDisplay($item['row_date'] ?? '') . '</td>
                    <td>' . htmlspecialchars($item['comments'] ?? '') . '</td>
                </tr>';
            }
        }

        $html .= '</table>';
        $html .= '<p style="font-size:8px;font-style:italic;text-align:center;">Confidential Information: Do Not Disclose</p>';
        return $html;
    }

    ensureWasteDisposalTable($conn);

    if ($_GET['type'] == 'saveWasteDisposalRecord') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['status' => 'Invalid request']);
            exit;
        }

        $sources = isset($input['source_of_waste']) ? json_encode($input['source_of_waste']) : '[]';
        $wasteRows = isset($input['waste_rows']) ? json_encode($input['waste_rows']) : '[]';
        $rowsDecoded = decodeJsonField($input['waste_rows'] ?? []);
        $hasRow = false;
        foreach ($rowsDecoded as $item) {
            if (trim((string)($item['waste_material_name'] ?? '')) !== '') {
                $hasRow = true;
                break;
            }
        }
        if (!$hasRow) {
            echo json_encode(['status' => 'Please add at least one waste material row']);
            exit;
        }

        $sql = "INSERT INTO waste_disposal_pharma (
            plant_id, form_no, sop_ref, record_date, source_of_waste, waste_rows, entry_by, entry_date, status
        ) VALUES (
            '" . esc($conn, $_GET['plant_id']) . "',
            '" . esc($conn, $input['form_no'] ?? 'FQA-022-A') . "',
            '" . esc($conn, $input['sop_ref'] ?? 'SOP-QA-022') . "',
            " . (empty($input['record_date']) ? 'NULL' : "'" . esc($conn, $input['record_date']) . "'") . ",
            '" . esc($conn, $sources) . "',
            '" . esc($conn, $wasteRows) . "',
            '" . esc($conn, $_GET['emp_id']) . "',
            '$entry_date',
            'active'
        )";

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['status' => $conn->error]);
        }
    } else if ($_GET['type'] == 'getWasteDisposalLog') {
        $output = [];
        $fromDate = esc($conn, $_GET['from_date'] ?? '');
        $toDate = esc($conn, $_GET['to_date'] ?? '');
        $filter = dateFilterSql($fromDate, $toDate, 'record_date');

        $sql = "SELECT id, form_no, sop_ref, record_date, source_of_waste, waste_rows, entry_by, entry_date
                FROM waste_disposal_pharma
                WHERE plant_id='" . esc($conn, $_GET['plant_id']) . "' $filter
                ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $formatted = formatWasteRow($row);
                $formatted['row_count'] = count($formatted['waste_rows']);
                $formatted['source_summary'] = implode(', ', $formatted['source_of_waste']);
                $output[] = $formatted;
            }
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'getWasteDisposalById') {
        $output = [];
        $sql = "SELECT * FROM waste_disposal_pharma
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $output = formatWasteRow($result->fetch_assoc());
        }
        echo json_encode($output);
    } else if ($_GET['type'] == 'downloadWasteDisposalForm') {
        $_GET['filename'] = 'Waste Disposal Record';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');

        $sql = "SELECT * FROM waste_disposal_pharma
                WHERE id='" . esc($conn, $_GET['id']) . "'
                AND plant_id='" . esc($conn, $_GET['plant_id']) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = formatWasteRow($result->fetch_assoc());
            $pdf->writeHTML(buildWasteDisposalHtml($row), true, false, true, false, '');
            $pdf->Output('Waste_Disposal_Record_' . $row['id'] . '.pdf', 'I');
        }
    }
}
