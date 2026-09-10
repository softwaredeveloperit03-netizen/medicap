<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "getRetestCalender") {
        $output = array();
        $plantId = $conn->real_escape_string((string)($_GET['plant_id'] ?? ''));
        $plantSql = ($plantId !== '') ? " AND r.plant_id = '".$plantId."'" : '';
        $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade
                FROM retest r
                LEFT JOIN material m ON r.material_code = m.material_code
                WHERE LOWER(TRIM(r.status)) = 'intimated'".$plantSql."
                ORDER BY r.retest_date ASC";
        $result = $conn->query($sql);
        $today = date('Y-m-d');
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $retestDate = trim((string)($row['retest_date'] ?? ''));
                if ($retestDate !== '' && $retestDate !== '0000-00-00') {
                    $diff = abs(strtotime($retestDate) - strtotime($today));
                    $dtF = new DateTime('@0');
                    $dtT = new DateTime('@'.$diff);
                    $row['due_days'] = $dtF->diff($dtT)->format('%a');
                } else {
                    $row['due_days'] = '-';
                }
                $row['retest_id'] = $row['id'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRetestIntimationsForAllocation") {
        require_once __DIR__.'/../store/retest_helpers.php';
        header('Content-Type: application/json; charset=utf-8');
        $output = array();
        $plantId = medicap_retest_normalize_plant_id((string)($_GET['plant_id'] ?? ''));
        $plantSql = ($plantId !== '') ? " AND r.plant_id = '".$conn->real_escape_string($plantId)."'" : '';
        $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade
                FROM retest r
                LEFT JOIN material m ON r.material_code = m.material_code
                WHERE LOWER(TRIM(COALESCE(r.status,''))) IN ('intimated', 'pending')".$plantSql."
                ORDER BY r.retest_date ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $st = strtolower(trim((string)($row['status'] ?? '')));
                $retestId = (int)($row['id'] ?? 0);
                if ($st === 'pending' && medicap_retest_has_sampling($conn, $row['grn_no'] ?? '', $retestId)) {
                    continue;
                }
                $due = medicap_retest_compute_due_days($row['retest_date'] ?? '');
                $row['due_days'] = ($due === null) ? '-' : $due;
                $row['due_label'] = medicap_retest_due_label($due);
                $row['retest_id'] = $row['id'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "allocateSamplingPerson") {
        require_once __DIR__.'/../store/retest_helpers.php';
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(medicap_retest_allocate_sampling_person(
            $conn,
            is_array($input) ? $input : array(),
            medicap_retest_normalize_plant_id((string)($_GET['plant_id'] ?? '')),
            $_GET['emp_id'] ?? '',
            $entry_date
        ));
    } else if ($_GET["type"] == "getAwaitingSamplingRetests") {
        require_once __DIR__.'/../store/retest_helpers.php';
        header('Content-Type: application/json; charset=utf-8');
        medicap_ensure_sampling_retest_columns($conn);
        $output = array();
        $plantId = medicap_retest_normalize_plant_id((string)($_GET['plant_id'] ?? ''));
        $plantSql = ($plantId !== '') ? " AND s.plant_id = '".$conn->real_escape_string($plantId)."'" : '';
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade,
                sb.release_date, sb.retest_date AS stock_retest_date, sb.retest_status
                FROM sampling s
                LEFT JOIN material m ON s.material_code = m.material_code
                LEFT JOIN stock_book sb ON sb.material_code = s.material_code AND sb.batch_no = s.batch_no
                    AND (sb.grn_no = s.grn_no OR sb.grn_no = COALESCE(NULLIF(s.old_grn,''), s.grn_no)
                        OR sb.receiving_no = COALESCE(NULLIF(s.old_grn,''), s.grn_no))
                WHERE (
                    (s.retest_id IS NOT NULL AND s.retest_id > 0)
                    OR s.grn_no LIKE 'R-%'
                    OR (COALESCE(s.old_grn,'') <> '' AND s.old_grn = s.grn_no)
                )
                AND LOWER(TRIM(COALESCE(s.status,''))) IN ('inprocess', 'pending', '')
                ".$plantSql."
                ORDER BY COALESCE(NULLIF(s.request_date,''), s.entry_date, s.id) ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                try {
                    if (!empty($row['stock_retest_date']) || !empty($row['release_date'])) {
                        $row['retest_date'] = medicap_retest_resolve_date($conn, $row) ?? ($row['stock_retest_date'] ?? '');
                    }
                } catch (Throwable $e) {
                    if (!empty($row['stock_retest_date'])) {
                        $row['retest_date'] = $row['stock_retest_date'];
                    }
                }
                $row['receiving_no'] = medicap_retest_display_receiving_no($row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getAllocatedRetestsAwaitingGrn") {
        require_once __DIR__.'/../store/retest_helpers.php';
        $output = array();
        $plantId = $conn->real_escape_string((string)($_GET['plant_id'] ?? ''));
        $plantSql = ($plantId !== '') ? " AND r.plant_id = '".$plantId."'" : '';
        $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade
                FROM retest r
                LEFT JOIN material m ON r.material_code = m.material_code
                WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'pending'".$plantSql."
                ORDER BY r.retest_date ASC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (medicap_retest_has_sampling($conn, $row['grn_no'] ?? '', (int)($row['id'] ?? 0))) {
                    continue;
                }
                $push = medicap_retest_push_to_sampling($conn, $row, $_GET['plant_id'] ?? '', $_GET['emp_id'] ?? '', $entry_date);
                if (($push['status'] ?? '') === 'failed') {
                    $row['retest_id'] = $row['id'];
                    $row['due_days'] = medicap_retest_compute_due_days($row['retest_date'] ?? '');
                    $row['due_label'] = medicap_retest_due_label($row['due_days']);
                    $row['retest_workflow_status'] = 'Allocated';
                    $row['retest_next_step'] = $push['msg'] ?? 'Could not open Retest Sampling.';
                    $output[] = $row;
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($output);
    } else if ($_GET["type"] == "getRetestCalendarDueWindow") {
        require_once __DIR__.'/../store/retest_helpers.php';
        medicap_retest_json_echo_rows($conn, array(
            'include_overdue' => true,
            'material_type' => (string)($_GET['material_type'] ?? ''),
            'plant_id' => medicap_retest_normalize_plant_id((string)($_GET['plant_id'] ?? '')),
        ));
    } else if ($_GET["type"] == "markRetestSamplingComplete") {
        require_once __DIR__.'/../store/retest_helpers.php';
        header('Content-Type: application/json; charset=utf-8');
        $id = $conn->real_escape_string((string)($input['id'] ?? ''));
        if ($id === '') {
            echo json_encode(array('status' => 'failed', 'msg' => 'Sampling record not found.'));
        } else {
            $sampRes = $conn->query("SELECT * FROM sampling WHERE id='".$id."' LIMIT 1");
            if (!$sampRes || $sampRes->num_rows === 0) {
                echo json_encode(array('status' => 'failed', 'msg' => 'Sampling record not found.'));
            } else {
                $sampling = $sampRes->fetch_assoc();
                medicap_retest_mark_sampling_approved($conn, $sampling);
                echo json_encode(array('status' => 'success'));
            }
        }
    } else if ($_GET["type"] == "saveRetestSampling") {
        require_once __DIR__.'/../store/retest_helpers.php';
        $id = $conn->real_escape_string((string)($input['id'] ?? ''));
        if ($id === '') {
            echo json_encode(array('status' => 'failed', 'msg' => 'Sampling record not found.'));
        } else {
            $sampRes = $conn->query("SELECT * FROM sampling WHERE id='".$id."' LIMIT 1");
            if (!$sampRes || $sampRes->num_rows === 0) {
                echo json_encode(array('status' => 'failed', 'msg' => 'Sampling record not found.'));
            } else {
                $sampling = $sampRes->fetch_assoc();
                $containers = $conn->real_escape_string((string)($input['containers'] ?? '1'));
                $sampleQty = $conn->real_escape_string((string)($input['sample_qty'] ?? ''));
                $sampleUnit = $conn->real_escape_string((string)($input['sample_unit'] ?? ''));
                $samplingDetails = $conn->real_escape_string(json_encode($input['container_details'] ?? array()));
                $startTime = $conn->real_escape_string((string)($input['start_sampling'] ?? $input['start_time'] ?? ''));
                $stopTime = $conn->real_escape_string((string)($input['stop_sampling'] ?? $input['stop_time'] ?? ''));

                $sqlUp = "UPDATE sampling SET containers='".$containers."', sample_qty='".$sampleQty."', sample_unit='".$sampleUnit."',
                    sampling_details='".$samplingDetails."', sampledContainers='".$conn->real_escape_string((string)($input['sampling_containers'] ?? $containers))."',
                    start_time='".$startTime."', end_time='".$stopTime."', status='Sampled', entry_by='".$_GET['emp_id']."', entry_date='".$entry_date."'
                    WHERE id='".$id."'";

                if ($conn->query($sqlUp)) {
                    $retestId = (int)($sampling['retest_id'] ?? 0);
                    if ($retestId > 0) {
                        $conn->query("UPDATE retest SET status='sampled' WHERE id='".$conn->real_escape_string((string)$retestId)."'");
                    } else {
                        $oldGrn = $conn->real_escape_string((string)($sampling['old_grn'] ?? medicap_retest_display_receiving_no($sampling)));
                        if ($oldGrn !== '') {
                            $rtRes = $conn->query("SELECT id FROM retest WHERE grn_no='".$oldGrn."' ORDER BY id DESC LIMIT 1");
                            if ($rtRes && $rtRes->num_rows > 0) {
                                $rtRow = $rtRes->fetch_assoc();
                                $conn->query("UPDATE retest SET status='sampled' WHERE id='".$rtRow['id']."'");
                            }
                        }
                    }
                    echo json_encode(array('status' => 'success', 'msg' => 'Retest sampling saved successfully.'));
                } else {
                    echo json_encode(array('status' => 'failed', 'msg' => $conn->error));
                }
            }
        }
    }
else if ($_GET["type"] == "downloadRetestCalender") {
        $_GET['filename'] = 'Withdrawal Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Withdrawal Log</h2>
        <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Sr</td>
                    <td style="width:10%;">Material Type	</td>
                    <td style="width:10%;">Category</td>
                    <td style="width:10%;">Material Code	</td>
                    <td style="width:10%;">Material Name	</td>
                    <td style="width:5%;">AR.No	</td>
                    <td style="width:10%;">Receiving no.</td>
                    <td style="width:10%;">Date</td>
                    <td style="width:10%;">Release Date	</td>
                      <td style="width:10%;">Retest Date		</td>
                        <td style="width:10%;">Due Days	</td>
                          
                  
                </tr>';
                $i=1;
                $output = Array();
                 $effectiveDate = date('Y-m-d', strtotime("+15 days", strtotime($entry_date)));
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='Approved' AND retest_date BETWEEN DATE('".$entry_date."') AND '".$effectiveDate."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                        $row["sampling_qty"] = +$row["identification_qty"] + +$row["composite_qty"] + +$row["reserve_qty"];
                        $row["containersList"] = json_decode($row["containersList"]);
            
                         $output[] = $row;
                    
                    $html.='<tr>
                                <td style="width:5%;">'.$i.'</td>
                                <td style="width:10%;">'.$row['material_type'].'</td>
                                <td style="width:10%;">'.$row['material_subtype'].'</td>
                                <td style="width:10%;">'.$row['material_code'].'</td>
                                <td style="width:10%;">'.$row['material_name'].'</td>
                                <td style="width:5%;">'.$row['ar_no'].'</td>
                                <td style="width:10%;">'.$row['grn_no'].'</td>
                                <td style="width:10%;">'.$row['grn_date'].'</td>
                                <td style="width:10%;">'.$row['release_date'].'</td>
                               <td style="width:10%;">'.$row['retest_date'].'</td>
                               <td style="width:10%;">'.$row['due_days'].'</td>
                             
                            </tr>';
                            $i++;
                            }
                        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('withdrawallog.pdf', 'I');
}
}

$conn->close();
?>