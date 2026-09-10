<?php
 
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../db.php';
require '../token.php';
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"] ?? '';
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    // ============================================
    // AUTHORIZATION OPERATIONS
    // ============================================
    
    if($_GET["type"]=="saveAuthorization") {
        
        // Get data from POST (FormData) or JSON input
        $data = array();
        if(!empty($_POST)) {
            $data = $_POST;
        } elseif(!empty($input)) {
            $data = $input;
        }
        
        // Handle file uploads
        $authorization_copy_url = '';
        $annexures_url = '';
        $digital_signature_url = '';
        
        // Create directory once at the beginning - consistent path
        $upload_dir = '../../../uploads/exports/authorization/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                echo "{\"status\":\"Failed to create upload directory\"}";
                return;
            }
        }
        
        // Upload authorization copy
        if(isset($_FILES['authorization_copy']) && $_FILES['authorization_copy']['error'] == 0) {
            $file_name = time() . '_' . basename($_FILES['authorization_copy']['name']);
            $target_file = $upload_dir . $file_name;
            if(move_uploaded_file($_FILES['authorization_copy']['tmp_name'], $target_file)) {
                $authorization_copy_url = '../../../uploads/exports/authorization/' . $file_name;
            }
        }
        
        // Upload annexures
        if(isset($_FILES['annexures']) && $_FILES['annexures']['error'] == 0) {
            $file_name = time() . '_' . basename($_FILES['annexures']['name']);
            $target_file = $upload_dir . $file_name;
            if(move_uploaded_file($_FILES['annexures']['tmp_name'], $target_file)) {
                $annexures_url = '../../../uploads/exports/authorization/' . $file_name;
            }
        }
        
        // Upload digital signature
        if(isset($_FILES['digital_signature']) && $_FILES['digital_signature']['error'] == 0) {
            $file_name = time() . '_' . basename($_FILES['digital_signature']['name']);
            $target_file = $upload_dir . $file_name;
            if(move_uploaded_file($_FILES['digital_signature']['tmp_name'], $target_file)) {
                $digital_signature_url = '../../../uploads/exports/authorization/' . $file_name;
            }
        }
        
        // Calculate remaining quantity
        $remaining_quantity = floatval($data['authorized_quantity'] ?? 0) - floatval($data['used_quantity'] ?? 0);
        
        // Get IP address
        $ip_logged = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Create action log
        $action_log = json_encode(array(
            array(
                'action' => 'Created',
                'user' => $_GET["emp_id"],
                'date' => $entry_date,
                'ip' => $ip_logged
            )
        ));
        
        $sql = "INSERT INTO `export_authorization`(
            `plant_id`, `authorization_type`, `company_name`, `authorization_holder_name`,
            `designation`, `contact_number`, `email_id`, `iec_code`, `gst_tax_id`, `company_address`,
            `authorization_license_number`, `issuing_authority`, `issue_date`, `valid_from_date`, `valid_till_date`, `status`,
            `product_category`, `product_name`, `hs_code`, `itc_code`, `drug_license_no`, `controlled_substance_check`,
            `country_of_origin`, `country_of_destination`, `port_of_entry_exit`, `authorized_quantity`, `unit`,
            `used_quantity`, `remaining_quantity`, `is_product_restricted`, `special_conditions`, `storage_requirement`,
            `hazardous_material_declaration`, `schedule_drug_category`, `authorization_copy_file_url`, `annexures_file_url`,
            `digital_signature_file_url`, `created_by`, `created_date_time`, `ip_logged`, `action_log`,
            `auto_renewal_reminder_before`, `expiry_alert_toggle`, `notify_to`, `requires_internal_approval`,
            `approver1`, `approver2`, `approver3`, `approval_status`, `approval_remarks`
        ) VALUES (
            '".$_GET["plant_id"]."', '".($data['authorization_type'] ?? '')."', '".($data['company_name'] ?? '')."', '".($data['authorization_holder_name'] ?? '')."',
            '".($data['designation'] ?? '')."', '".($data['contact_number'] ?? '')."', '".($data['email_id'] ?? '')."', '".($data['iec_code'] ?? '')."',
            '".($data['gst_tax_id'] ?? '')."', '".($data['company_address'] ?? '')."', '".($data['authorization_license_number'] ?? '')."',
            '".($data['issuing_authority'] ?? '')."', '".($data['issue_date'] ?? '')."', '".($data['valid_from_date'] ?? '')."',
            '".($data['valid_till_date'] ?? '')."', '".($data['status'] ?? 'Pending')."', '".($data['product_category'] ?? '')."',
            '".($data['product_name'] ?? '')."', '".($data['hs_code'] ?? '')."', '".($data['itc_code'] ?? '')."',
            '".($data['drug_license_no'] ?? '')."', '".($data['controlled_substance_check'] ?? 'No')."',
            '".($data['country_of_origin'] ?? '')."', '".($data['country_of_destination'] ?? '')."',
            '".($data['port_of_entry_exit'] ?? '')."', '".($data['authorized_quantity'] ?? 0)."', '".($data['unit'] ?? '')."',
            '".($data['used_quantity'] ?? 0)."', '$remaining_quantity', '".($data['is_product_restricted'] ?? 'No')."',
            '".($data['special_conditions'] ?? '')."', '".($data['storage_requirement'] ?? '')."',
            '".($data['hazardous_material_declaration'] ?? 'No')."', '".($data['schedule_drug_category'] ?? '')."',
            '$authorization_copy_url', '$annexures_url', '$digital_signature_url',
            '".$_GET["emp_id"]."', '$entry_date', '$ip_logged', '$action_log',
            '".($data['auto_renewal_reminder_before'] ?? '30')."', '".($data['expiry_alert_toggle'] ?? 'Yes')."',
            '".($data['notify_to'] ?? '')."', '".($data['requires_internal_approval'] ?? 'Yes')."',
            '".($data['approver1'] ?? '')."', '".($data['approver2'] ?? '')."', '".($data['approver3'] ?? '')."',
            '".($data['approval_status'] ?? 'Pending')."', '".($data['approval_remarks'] ?? '')."'
        )";
        
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    if($_GET["type"]=="getAuthorizations") {
        
 
        
        $sql = "SELECT * FROM export_authorization a WHERE  (a.approver1='".$_GET["emp_id"]."' && a.approver1_approved_date is null) or (a.approver2='".$_GET["emp_id"]."' && a.approver2_approved_date is null) or (a.approver3='".$_GET["emp_id"]."' && a.approver3_approved_date is null)";
        $result = $conn->query($sql);
        
        $authorizations = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if($row['approver1_approved_date'] == null && $row['approver2_approved_date'] == null && $row['approver3_approved_date'] == null){
                    $row['update']='approver1_approved_date';
                }
               else if($row['approver1_approved_date'] != null && $row['approver2_approved_date'] == null && $row['approver3_approved_date'] == null){
                    $row['update']='approver2_approved_date';
                }
               else if($row['approver1_approved_date'] != null && $row['approver2_approved_date'] != null && $row['approver3_approved_date'] == null){
                    $row['update']='approver3_approved_date';
                }
                $authorizations[] = $row;
            }
        }
        
        echo json_encode($authorizations);
    }
    
    if($_GET["type"]=="getAuthorizationById") {
        
        $id = $_GET["id"];
        $sql = "SELECT * FROM export_authorization WHERE id = '".$id."' LIMIT 1";
        $result = $conn->query($sql);
        
        if($result && $result->num_rows > 0) {
            $authorization = $result->fetch_assoc();
            echo json_encode($authorization);
        } else {
            echo "{\"status\":\"Authorization not found\"}";
        }
    }
    
    if($_GET["type"] == "approveChecking") {
        $col = $input['update'] ?? '';
        $id = $input['id'] ?? '';
        
        if(empty($col) || empty($id)) {
            echo json_encode(array("status" => "error", "message" => "Missing required parameters"));
            return;
        }
        
        // Update the approval date for the specific approver
        $sql = "UPDATE export_authorization SET $col = '$entry_date' WHERE id = '".$id."'";
        $result = $conn->query($sql);
        
        if($result) {
            // Check if all approvers have approved, then update approval_status
            $check_sql = "SELECT approver1_approved_date, approver2_approved_date, approver3_approved_date, approver1, approver2, approver3 FROM export_authorization WHERE id = '".$id."'";
            $check_result = $conn->query($check_sql);
            
            if($check_result && $check_result->num_rows > 0) {
                $row = $check_result->fetch_assoc();
                $all_approved = true;
                
                // Check if all assigned approvers have approved
                if(!empty($row['approver1']) && empty($row['approver1_approved_date'])) {
                    $all_approved = false;
                }
                if(!empty($row['approver2']) && empty($row['approver2_approved_date'])) {
                    $all_approved = false;
                }
                if(!empty($row['approver3']) && empty($row['approver3_approved_date'])) {
                    $all_approved = false;
                }
                
                // If all approvers have approved, update approval_status to 'Approved'
                if($all_approved) {
                    $update_status_sql = "UPDATE export_authorization SET approval_status = 'Approved' WHERE id = '".$id."'";
                    $conn->query($update_status_sql);
                }
            }
            
            echo json_encode(array("status" => "success", "message" => "Approval updated successfully"));
        } else {
            echo json_encode(array("status" => "error", "message" => $conn->error));
        }
    }
    
      if($_GET["type"] == "getAuthorizationLogs") {
        $where = "1=1";
        if(!empty($_GET["plant_id"])) {
            $where .= " AND plant_id = '".$_GET["plant_id"]."'";
        }
        if(!empty($_GET["status"])) {
            $where .= " AND status = '".$_GET["status"]."'";
        }
        if(!empty($_GET["authorization_type"])) {
            $where .= " AND authorization_type = '".$_GET["authorization_type"]."'";
        }
        
        $sql = "SELECT * FROM export_authorization WHERE $where ORDER BY created_date_time DESC";
        $result = $conn->query($sql);
        
        $authorizations = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $authorizations[] = $row;
            }
        }
        
        echo json_encode($authorizations);
    }
    
    
    
    
    
    
    
     
    // ============================================
    // IMPORT DETAILS OPERATIONS
    // ============================================
    
    
    
   
    
 if($_GET["type"] == "getImportDetails") {
        $sql = "SELECT 
                    a.*,
                    b.*,
                    c.*,
                    COALESCE(a.po_no, b.po_no ) AS po_no,
                    COALESCE(c.grn_no, d.grn_no ) AS grn_no,
                    COALESCE(a.currency, d.currency ) AS currency1,
                    (select vendor_name from vendor v where v.vendor_no=a.vendor_no) as supplier_name,
                    (select vendor_name from vendor v where v.vendor_no=a.vendor_no) as vendor_name,
                    (select material_name from material v where v.material_code=c.material_code) as item_description,
                    (select material_name from material v where v.material_code=c.material_code) as import_item_description,

                     c.grn_date, d.supplier_name as import_supplier_name,
                    c.tax_invoice_date  as invDate, c.tax_invoice as invNo, b.eway_bill_no as blAwbNo, d.port_of_loading, d.port_of_discharge,
                    d.incoterms, d.mode, d.currency, d.exchange_rate, d.basic_invoice_value,
                    d.packing_charges, d.design_mould_cost, d.inv_value_in_rs, d.packing_charges_in_rs,
                    d.design_mould_cost_in_rs, c.material_code as import_item_code, 
                    c.qty as import_quantity, c.unit as import_uom, c.rate, d.rate_in_rs,
                    c.id as import_details_id,
                    il.id as logistics_id, il.ocean_air_freight_percent, il.ocean_air_freight_amount,
                    il.insurance_value, il.miscellaneous_charge, il.origin_port_charges, il.export_customs_charges,
                    il.assessable_value_cif, il.cif_value, il.weight, il.volume, il.weight_volume,
                    il.freight_document_url, il.insurance_document_url, il.customs_document_url, il.other_document_url,
                    ci.id as customs_id, ci.assessable_value, ci.bcd_percent, ci.bcd_value, ci.sws_percent, ci.sws_value,
                    ci.other_charges, ci.total_duty_value, ci.gst_percent, ci.gst_value, ci.anti_dumping_duty,
                    ci.penalty_percent, ci.penalty_value, ci.advance_license_no, ci.advance_license_benefits,
                     ci.road_tape_no, ci.rt_benefits, ci.total_paid_custom_duty,
                        ab.id as agency_bills_id, ab.agency_name, ab.agency_bill_no, ab.bill_date, ab.bill_amount,ab.gst_percent,
                        ab.gst_percent as agency_gst_percent, ab.cif_term_amount_with_gst,
                    cfs.id as cfs_id, cfs.agency_name as cfs_agency_name, cfs.bill_count as cfs_bill_count,
                    cfs.bill_date as cfs_bill_date, cfs.total_bill_amount as cfs_total_bill_amount,
                    cfs.gst_percent as cfs_gst_percent, cfs.cif_term_amount_with_gst as cfs_cif_term_amount_with_gst,
                    dp.id as duty_payment_id, dp.icegate_ref_no, dp.payment_date_time, dp.bank_transaction_no,
                    dp.document_no, dp.challan_no_payment, dp.payment_amount, dp.payment_status,
                    dp.payment_mode, dp.bank_name, dp.remarks,
                    ff.id as freight_forwarder_id, ff.forwarder_name, ff.forwarder_inv_date, ff.forwarder_inv_no,
                    ff.origin_charges, ff.origin_charges_gst_percent, ff.origin_charges_with_gst,
                    ff.ocean_freight, ff.ocean_freight_gst_percent, ff.ocean_freight_with_gst,
                    ff.destination_inv_date, ff.destination_inv_no, ff.destination_charges,
                    ff.destination_charges_gst_percent, ff.destination_charges_with_gst, ff.total_forwarder_amount,
                      dc.id as destination_clearing_id, dc.cha_name, dc.inv_date as destination_clearing_inv_date, dc.inv_no as destination_clearing_inv_no,
                    dc.cha_clearing_agent_fees, dc.document_fee, dc.loading_and_unloading, dc.other_charges,
                    dc.gst_percent as destination_clearing_gst_percent, dc.stamp_duty, dc.stamp_duty_gst_percent,
                    dc.total_cha_with_gst,
                    t.id as transport_id, t.transport_name, t.transport_bill_no, t.transport_bill_date,
                    t.inland_transportation, t.unloading_charges, t.gst_percent as transport_gst_percent,
                   t.total_transport_amount_with_gst,
                    pb.id as payment_bank_id, pb.ref_document_no, pb.bank_name as payment_bank_name,
                    pb.paid_amount, pb.paid_date, pb.gst_percent as payment_bank_gst_percent,
                    pb.total_paid_with_gst,w.agency_bill_no as agencyBillNo,
                    w.id as warehousing_id, w.agency_name as warehousing_agency_name, w.agency_bill_no,
                    w.agency_bill_date as agencyBillDate, w.bill_value, w.gst_percent as warehousing_gst_percent,
                     w.total_warehouse_amount_with_gst,
                    ih.id as inhand_id, ih.agency_name as inhand_agency_name, ih.agency_bill_no,
                    ih.agency_bill_date, ih.inspection_survey_fees, ih.gst_percent as inhand_gst_percent,
                    ih.total_amount_with_gst
                FROM purchaseorder a 
                LEFT JOIN challan b ON a.po_no = b.po_no 
                LEFT JOIN challan_materials c ON b.challan_no = c.challan_no
                LEFT JOIN import_details d ON a.po_no = d.po_no AND b.challan_no = d.challan_no
                LEFT JOIN int_logistics il ON a.po_no = il.po_no AND b.challan_no = il.challan_no AND c.grn_no = il.grn_no
                 LEFT JOIN customs_import ci ON a.po_no = ci.po_no AND b.challan_no = ci.challan_no AND c.grn_no = ci.grn_no
                   LEFT JOIN agency_bills ab ON a.po_no = ab.po_no AND b.challan_no = ab.challan_no AND c.grn_no = ab.grn_no
                LEFT JOIN cfs ON a.po_no = cfs.po_no AND b.challan_no = cfs.challan_no AND c.grn_no = cfs.grn_no
                LEFT JOIN duty_payment dp ON a.po_no = dp.po_no AND b.challan_no = dp.challan_no AND c.grn_no = dp.grn_no
                               LEFT JOIN freight_forwarder ff ON a.po_no = ff.po_no AND b.challan_no = ff.challan_no AND c.grn_no = ff.grn_no
                LEFT JOIN destination_clearing dc ON a.po_no = dc.po_no AND b.challan_no = dc.challan_no AND c.grn_no = dc.grn_no
                  LEFT JOIN transport t ON a.po_no = t.po_no AND b.challan_no = t.challan_no AND c.grn_no = t.grn_no
                LEFT JOIN payment_bank pb ON a.po_no = pb.po_no AND b.challan_no = pb.challan_no AND c.grn_no = pb.grn_no
                 LEFT JOIN warehousing w ON a.po_no = w.po_no AND b.challan_no = w.challan_no AND c.grn_no = w.grn_no
                LEFT JOIN inhand ih ON a.po_no = ih.po_no AND b.challan_no = ih.challan_no AND c.grn_no = ih.grn_no
               WHERE a.marketType <> 'Domestic'
  AND c.grn_no IS NOT NULL
  
 ORDER BY a.entry_date DESC";
    	$result = $conn->query($sql);
        
        $importData = array();
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Merge import_details data with main data
                if(!empty($row['import_details_id'])) {
                    $row['grn_no'] = $row['grn_no'];
                    $row['grn_date'] = $row['grn_date'];
                    $row['supplier_name'] = $row['import_supplier_name'] ?: $row['vendor_name'];
                    $row['inv_date'] = $row['tax_invoice_date'];
                    $row['inv_no'] = $row['tax_invoice'];
                    $row['bl_awb_no'] = $row['eway_bill_no'];
                    $row['port_of_loading'] = $row['port_of_loading'];
                    $row['port_of_discharge'] = $row['port_of_discharge'];
                    $row['incoterms'] = $row['incoterms'];
                    $row['mode'] = $row['mode'];
                    $row['currency'] = $row['currency1'];
                    $row['exchange_rate'] = $row['exchange_rate'];
                    $row['basic_invoice_value'] = $row['net_total'];
                    $row['packing_charges'] = $row['packing_charges'];
                    $row['design_mould_cost'] = $row['design_mould_cost'];
                    $row['gst_percent'] = $row['gst_percent'];
                    $row['inv_value_in_rs'] = $row['inv_value_in_rs'];
                    $row['packing_charges_in_rs'] = $row['packing_charges_in_rs'];
                    $row['design_mould_cost_in_rs'] = $row['design_mould_cost_in_rs'];
                    $row['item_code'] = $row['import_item_code'] ?: $row['material_code'];
                    $row['item_description'] = $row['import_item_description'] ?: $row['material_name'];
                    $row['quantity'] = $row['import_quantity'] ?: $row['qty'];
                    $row['uom'] = $row['import_uom'] ?: $row['unit'];
                    $row['inv_rate'] = $row['rate'];
                    $row['rate_in_rs'] = $row['rate_in_rs'];
                }
                $importData[] = $row;
            }
        }
        
        echo json_encode($importData);
    }
    
    if($_GET["type"] == "saveImportDetails") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape all input data
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            
            // Check if record exists by po_no, challan_no, and grn_no
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                // If ID is provided, check by ID first
                $check_sql = "SELECT id FROM import_details WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM import_details WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            $grn_date = $conn->real_escape_string($data['grn_date'] ?? '');
            $supplier_name = $conn->real_escape_string($data['supplier_name'] ?? '');
            $inv_date = $conn->real_escape_string($data['inv_date'] ?? '');
            $inv_no = $conn->real_escape_string($data['inv_no'] ?? '');
            $bl_awb_no = $conn->real_escape_string($data['bl_awb_no'] ?? '');
            $port_of_loading = $conn->real_escape_string($data['port_of_loading'] ?? '');
            $port_of_discharge = $conn->real_escape_string($data['port_of_discharge'] ?? '');
            $incoterms = $conn->real_escape_string($data['incoterms'] ?? '');
            $mode = $conn->real_escape_string($data['mode'] ?? '');
            $currency = $conn->real_escape_string($data['currency'] ?? '');
            $exchange_rate = floatval($data['exchange_rate'] ?? 0);
            $basic_invoice_value = floatval($data['basic_invoice_value'] ?? 0);
            $packing_charges = floatval($data['packing_charges'] ?? 0);
            $design_mould_cost = floatval($data['design_mould_cost'] ?? 0);
            $inv_value_in_rs = floatval($data['inv_value_in_rs'] ?? 0);
            $packing_charges_in_rs = floatval($data['packing_charges_in_rs'] ?? 0);
            $design_mould_cost_in_rs = floatval($data['design_mould_cost_in_rs'] ?? 0);
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $inv_rate = floatval($data['inv_rate'] ?? 0);
            $rate_in_rs = floatval($data['rate_in_rs'] ?? 0);
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO import_details_history(
                    original_id, plant_id, po_no, challan_no, grn_no, grn_date, supplier_name,
                    inv_date, inv_no, bl_awb_no, port_of_loading, port_of_discharge,
                    incoterms, mode, currency, exchange_rate, basic_invoice_value,
                    packing_charges, design_mould_cost, inv_value_in_rs, packing_charges_in_rs,
                    design_mould_cost_in_rs, item_code, item_description, quantity, uom,
                    inv_rate, rate_in_rs, created_by, created_date_time, last_modified_by,
                    modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, grn_date, supplier_name,
                    inv_date, inv_no, bl_awb_no, port_of_loading, port_of_discharge,
                    incoterms, mode, currency, exchange_rate, basic_invoice_value,
                    packing_charges, design_mould_cost, inv_value_in_rs, packing_charges_in_rs,
                    design_mould_cost_in_rs, item_code, item_description, quantity, uom,
                    inv_rate, rate_in_rs, created_by, created_date_time, last_modified_by,
                    modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM import_details WHERE id = '$existing_id'";
                $conn->query($history_sql);

                // Update existing record
                $id = $existing_id;
                
                $sql = "UPDATE import_details SET
                    grn_no = '$grn_no',
                    grn_date = '$grn_date',
                    supplier_name = '$supplier_name',
                    inv_date = '$inv_date',
                    inv_no = '$inv_no',
                    bl_awb_no = '$bl_awb_no',
                    port_of_loading = '$port_of_loading',
                    port_of_discharge = '$port_of_discharge',
                    incoterms = '$incoterms',
                    mode = '$mode',
                    currency = '$currency',
                    exchange_rate = $exchange_rate,
                    basic_invoice_value = $basic_invoice_value,
                    packing_charges = $packing_charges,
                    design_mould_cost = $design_mould_cost,
                    inv_value_in_rs = $inv_value_in_rs,
                    packing_charges_in_rs = $packing_charges_in_rs,
                    design_mould_cost_in_rs = $design_mould_cost_in_rs,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    inv_rate = $inv_rate,
                    rate_in_rs = $rate_in_rs,
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO import_details(
                    plant_id, po_no, challan_no, grn_no, grn_date, supplier_name,
                    inv_date, inv_no, bl_awb_no, port_of_loading, port_of_discharge,
                    incoterms, mode, currency, exchange_rate, basic_invoice_value,
                    packing_charges, design_mould_cost, inv_value_in_rs, packing_charges_in_rs,
                    design_mould_cost_in_rs, item_code, item_description, quantity, uom,
                    inv_rate, rate_in_rs, created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$grn_date', '$supplier_name',
                    '$inv_date', '$inv_no', '$bl_awb_no', '$port_of_loading', '$port_of_discharge',
                    '$incoterms', '$mode', '$currency', $exchange_rate, $basic_invoice_value,
                    $packing_charges, $design_mould_cost, $inv_value_in_rs, $packing_charges_in_rs,
                    $design_mould_cost_in_rs, '$item_code', '$item_description', $quantity, '$uom',
                    $inv_rate, $rate_in_rs, '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Import details saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }
    
    // ============================================
    // INTERNATIONAL LOGISTICS OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveIntLogistics") {
        try {
            // Handle file uploads
            $upload_dir = '../../../uploads/exports/int-logistics/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    echo json_encode(array("status" => "error", "message" => "Failed to create upload directory"));
                    exit();
                }
            }
            
            $freight_document_url = '';
            $insurance_document_url = '';
            $customs_document_url = '';
            $other_document_url = '';
            
            // Upload freight document
            if(isset($_FILES['freight_document']) && $_FILES['freight_document']['error'] == 0) {
                $file_name = time() . '_freight_' . basename($_FILES['freight_document']['name']);
                $target_file = $upload_dir . $file_name;
                if(move_uploaded_file($_FILES['freight_document']['tmp_name'], $target_file)) {
                    $freight_document_url = '../../../uploads/exports/int-logistics/' . $file_name;
                }
            }
            
            // Upload insurance document
            if(isset($_FILES['insurance_document']) && $_FILES['insurance_document']['error'] == 0) {
                $file_name = time() . '_insurance_' . basename($_FILES['insurance_document']['name']);
                $target_file = $upload_dir . $file_name;
                if(move_uploaded_file($_FILES['insurance_document']['tmp_name'], $target_file)) {
                    $insurance_document_url = '../../../uploads/exports/int-logistics/' . $file_name;
                }
            }
            
            // Upload customs document
            if(isset($_FILES['customs_document']) && $_FILES['customs_document']['error'] == 0) {
                $file_name = time() . '_customs_' . basename($_FILES['customs_document']['name']);
                $target_file = $upload_dir . $file_name;
                if(move_uploaded_file($_FILES['customs_document']['tmp_name'], $target_file)) {
                    $customs_document_url = '../../../uploads/exports/int-logistics/' . $file_name;
                }
            }
            
            // Upload other document
            if(isset($_FILES['other_document']) && $_FILES['other_document']['error'] == 0) {
                $file_name = time() . '_other_' . basename($_FILES['other_document']['name']);
                $target_file = $upload_dir . $file_name;
                if(move_uploaded_file($_FILES['other_document']['tmp_name'], $target_file)) {
                    $other_document_url = '../../../uploads/exports/int-logistics/' . $file_name;
                }
            }
            
            // Get form data
            $id = $conn->real_escape_string($_POST['id'] ?? '');
            $po_no = $conn->real_escape_string($_POST['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($_POST['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($_POST['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($_POST['item_code'] ?? '');
            $item_description = $conn->real_escape_string($_POST['item_description'] ?? '');
            $quantity = floatval($_POST['quantity'] ?? 0);
            $uom = $conn->real_escape_string($_POST['uom'] ?? '');
            $ocean_air_freight_percent = floatval($_POST['ocean_air_freight_percent'] ?? 0);
            $ocean_air_freight_amount = floatval($_POST['ocean_air_freight_amount'] ?? 0);
            $insurance_value = floatval($_POST['insurance_value'] ?? 0);
            $miscellaneous_charge = floatval($_POST['miscellaneous_charge'] ?? 0);
            $origin_port_charges = floatval($_POST['origin_port_charges'] ?? 0);
            $export_customs_charges = floatval($_POST['export_customs_charges'] ?? 0);
            $assessable_value_cif = floatval($_POST['assessable_value_cif'] ?? 0);
            $cif_value = floatval($_POST['cif_value'] ?? 0);
            $weight = floatval($_POST['weight'] ?? 0);
            $volume = floatval($_POST['volume'] ?? 0);
            $weight_volume = $conn->real_escape_string($_POST['weight_volume'] ?? '');
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';

            if(!empty($id)) {
                $check_sql = "SELECT id FROM int_logistics WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }

            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM int_logistics WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO int_logistics_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    ocean_air_freight_percent, ocean_air_freight_amount, insurance_value, miscellaneous_charge,
                    origin_port_charges, export_customs_charges, assessable_value_cif, cif_value,
                    weight, volume, weight_volume, freight_document_url, insurance_document_url,
                    customs_document_url, other_document_url, created_by, created_date_time, last_modified_by,
                    modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    ocean_air_freight_percent, ocean_air_freight_amount, insurance_value, miscellaneous_charge,
                    origin_port_charges, export_customs_charges, assessable_value_cif, cif_value,
                    weight, volume, weight_volume, freight_document_url, insurance_document_url,
                    customs_document_url, other_document_url, created_by, created_date_time, last_modified_by,
                    modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM int_logistics WHERE id = '$existing_id'";
                $conn->query($history_sql);

                // Update existing record
                $id = $existing_id;
                
                $update_fields = array();
                $update_fields[] = "ocean_air_freight_percent = $ocean_air_freight_percent";
                $update_fields[] = "ocean_air_freight_amount = $ocean_air_freight_amount";
                $update_fields[] = "insurance_value = $insurance_value";
                $update_fields[] = "miscellaneous_charge = $miscellaneous_charge";
                $update_fields[] = "origin_port_charges = $origin_port_charges";
                $update_fields[] = "export_customs_charges = $export_customs_charges";
                $update_fields[] = "assessable_value_cif = $assessable_value_cif";
                $update_fields[] = "cif_value = $cif_value";
                $update_fields[] = "weight = $weight";
                $update_fields[] = "volume = $volume";
                $update_fields[] = "weight_volume = '$weight_volume'";
                $update_fields[] = "last_modified_by = '".$_GET["emp_id"]."'";
                $update_fields[] = "modified_date_time = '$entry_date'";
                
                if(!empty($freight_document_url)) {
                    $update_fields[] = "freight_document_url = '$freight_document_url'";
                }
                if(!empty($insurance_document_url)) {
                    $update_fields[] = "insurance_document_url = '$insurance_document_url'";
                }
                if(!empty($customs_document_url)) {
                    $update_fields[] = "customs_document_url = '$customs_document_url'";
                }
                if(!empty($other_document_url)) {
                    $update_fields[] = "other_document_url = '$other_document_url'";
                }
                
                $sql = "UPDATE int_logistics SET " . implode(', ', $update_fields) . " WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO int_logistics(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    ocean_air_freight_percent, ocean_air_freight_amount, insurance_value, miscellaneous_charge,
                    origin_port_charges, export_customs_charges, assessable_value_cif, cif_value,
                    weight, volume, weight_volume, freight_document_url, insurance_document_url,
                    customs_document_url, other_document_url, created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description',
                    $quantity, '$uom', $ocean_air_freight_percent, $ocean_air_freight_amount, $insurance_value,
                    $miscellaneous_charge, $origin_port_charges, $export_customs_charges, $assessable_value_cif,
                    $cif_value, $weight, $volume, '$weight_volume', '$freight_document_url', '$insurance_document_url',
                    '$customs_document_url', '$other_document_url', '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "International logistics saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }

    // ============================================
    // CUSTOMS IMPORT OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveCustomsImport") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $assessable_value = floatval($data['assessable_value'] ?? 0);
            $assessable_value_gst_base = floatval($data['assessable_value_gst_base'] ?? 0);
            $bcd_percent = floatval($data['bcd_percent'] ?? 0);
            $bcd_value = floatval($data['bcd_value'] ?? 0);
            $sws_percent = floatval($data['sws_percent'] ?? 0);
            $sws_value = floatval($data['sws_value'] ?? 0);
            $other_charges = floatval($data['other_charges'] ?? 0);
            $total_duty_value = floatval($data['total_duty_value'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $gst_value = floatval($data['gst_value'] ?? 0);
            $anti_dumping_duty = floatval($data['anti_dumping_duty'] ?? 0);
            $penalty_percent = floatval($data['penalty_percent'] ?? 0);
            $penalty_value = floatval($data['penalty_value'] ?? 0);
            $advance_license_no = $conn->real_escape_string($data['advance_license_no'] ?? '');
            $advance_license_benefits = floatval($data['advance_license_benefits'] ?? 0);
            $road_tape_no = $conn->real_escape_string($data['road_tape_no'] ?? '');
            $rt_benefits = floatval($data['rt_benefits'] ?? 0);
            $total_paid_custom_duty = floatval($data['total_paid_custom_duty'] ?? 0);
            $qty_value = floatval($data['qty_value'] ?? 0);
            
            // Check existing
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM customs_import WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM customs_import WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // history
                $history_sql = "INSERT INTO customs_import_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    assessable_value, assessable_value_gst_base, bcd_percent, bcd_value, sws_percent, sws_value, other_charges, total_duty_value,
                    gst_percent, gst_value, anti_dumping_duty, penalty_percent, penalty_value, advance_license_no,
                    advance_license_benefits, road_tape_no, rt_benefits, total_paid_custom_duty, qty_value, created_by,
                    created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    assessable_value, assessable_value_gst_base, bcd_percent, bcd_value, sws_percent, sws_value, other_charges, total_duty_value,
                    gst_percent, gst_value, anti_dumping_duty, penalty_percent, penalty_value, advance_license_no,
                    advance_license_benefits, road_tape_no, rt_benefits, total_paid_custom_duty, qty_value, created_by,
                    created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM customs_import WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // update
                $id = $existing_id;
                $sql = "UPDATE customs_import SET
                    assessable_value = $assessable_value,
                    assessable_value_gst_base = $assessable_value_gst_base,
                    bcd_percent = $bcd_percent,
                    bcd_value = $bcd_value,
                    sws_percent = $sws_percent,
                    sws_value = $sws_value,
                    other_charges = $other_charges,
                    total_duty_value = $total_duty_value,
                    gst_percent = $gst_percent,
                    gst_value = $gst_value,
                    anti_dumping_duty = $anti_dumping_duty,
                    penalty_percent = $penalty_percent,
                    penalty_value = $penalty_value,
                    advance_license_no = '$advance_license_no',
                    advance_license_benefits = $advance_license_benefits,
                    road_tape_no = '$road_tape_no',
                    rt_benefits = $rt_benefits,
                    total_paid_custom_duty = $total_paid_custom_duty,
                    qty_value = $qty_value,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // insert
                $sql = "INSERT INTO customs_import(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    assessable_value, assessable_value_gst_base, bcd_percent, bcd_value, sws_percent, sws_value, other_charges, total_duty_value,
                    gst_percent, gst_value, anti_dumping_duty, penalty_percent, penalty_value, advance_license_no,
                    advance_license_benefits, road_tape_no, rt_benefits, total_paid_custom_duty, qty_value,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    $assessable_value, $assessable_value_gst_base, $bcd_percent, $bcd_value, $sws_percent, $sws_value, $other_charges, $total_duty_value,
                    $gst_percent, $gst_value, $anti_dumping_duty, $penalty_percent, $penalty_value, '$advance_license_no',
                    $advance_license_benefits, '$road_tape_no', $rt_benefits, $total_paid_custom_duty, $qty_value,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Customs import saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }

    // ============================================
    // AGENCY BILLS OPERATIONS
    // ============================================
    
   else if($_GET["type"] == "saveAgencyBills") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $agency_name = $conn->real_escape_string($data['agency_name'] ?? '');
            $agency_bill_no = $conn->real_escape_string($data['agency_bill_no'] ?? '');
            $bill_date = $conn->real_escape_string($data['bill_date'] ?? '');
            $bill_amount = floatval($data['bill_amount'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $cif_term_amount_with_gst = floatval($data['cif_term_amount_with_gst'] ?? 0);
            
            // Check existing
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM agency_bills WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM agency_bills WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO agency_bills_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, bill_date, bill_amount, gst_percent, cif_term_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, bill_date, bill_amount, gst_percent, cif_term_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM agency_bills WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE agency_bills SET
                    agency_name = '$agency_name',
                    agency_bill_no = '$agency_bill_no',
                    bill_date = '$bill_date',
                    bill_amount = $bill_amount,
                    gst_percent = $gst_percent,
                    cif_term_amount_with_gst = $cif_term_amount_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO agency_bills(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, bill_date, bill_amount, gst_percent, cif_term_amount_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$agency_name', '$agency_bill_no', '$bill_date', $bill_amount, $gst_percent, $cif_term_amount_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Agency bills saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }
   
    // ============================================
    // DUTY PAYMENT OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveDutyPayment") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $icegate_ref_no = $conn->real_escape_string($data['icegate_ref_no'] ?? '');
            $payment_date_time = $conn->real_escape_string($data['payment_date_time'] ?? '');
            $bank_transaction_no = $conn->real_escape_string($data['bank_transaction_no'] ?? '');
            $document_no = $conn->real_escape_string($data['document_no'] ?? '');
            $challan_no_payment = $conn->real_escape_string($data['challan_no_payment'] ?? '');
            $payment_amount = floatval($data['payment_amount'] ?? 0);
            $payment_status = $conn->real_escape_string($data['payment_status'] ?? 'Pending');
            $payment_mode = $conn->real_escape_string($data['payment_mode'] ?? '');
            $bank_name = $conn->real_escape_string($data['bank_name'] ?? '');
            $remarks = $conn->real_escape_string($data['remarks'] ?? '');
            
            // Check if record exists
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM duty_payment WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // Also check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM duty_payment WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO duty_payment_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    icegate_ref_no, payment_date_time, bank_transaction_no, document_no, challan_no_payment,
                    payment_amount, payment_status, payment_mode, bank_name, remarks,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    icegate_ref_no, payment_date_time, bank_transaction_no, document_no, challan_no_payment,
                    payment_amount, payment_status, payment_mode, bank_name, remarks,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM duty_payment WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE duty_payment SET
                    icegate_ref_no = '$icegate_ref_no',
                    payment_date_time = '$payment_date_time',
                    bank_transaction_no = '$bank_transaction_no',
                    document_no = '$document_no',
                    challan_no_payment = '$challan_no_payment',
                    payment_amount = $payment_amount,
                    payment_status = '$payment_status',
                    payment_mode = '$payment_mode',
                    bank_name = '$bank_name',
                    remarks = '$remarks',
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO duty_payment(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    icegate_ref_no, payment_date_time, bank_transaction_no, document_no, challan_no_payment,
                    payment_amount, payment_status, payment_mode, bank_name, remarks,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$icegate_ref_no', '$payment_date_time', '$bank_transaction_no', '$document_no', '$challan_no_payment',
                    $payment_amount, '$payment_status', '$payment_mode', '$bank_name', '$remarks',
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Duty payment saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    } 
    
     // ============================================
    // CFS OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveCFS") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $agency_name = $conn->real_escape_string($data['agency_name'] ?? '');
            $bill_count = intval($data['bill_count'] ?? 0);
            $bill_date = $conn->real_escape_string($data['bill_date'] ?? '');
            $total_bill_amount = floatval($data['total_bill_amount'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $cif_term_amount_with_gst = floatval($data['cif_term_amount_with_gst'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM cfs WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM cfs WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO cfs_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, bill_count, bill_date, total_bill_amount, gst_percent, cif_term_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, bill_count, bill_date, total_bill_amount, gst_percent, cif_term_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM cfs WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE cfs SET
                    agency_name = '$agency_name',
                    bill_count = $bill_count,
                    bill_date = '$bill_date',
                    total_bill_amount = $total_bill_amount,
                    gst_percent = $gst_percent,
                    cif_term_amount_with_gst = $cif_term_amount_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO cfs(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, bill_count, bill_date, total_bill_amount, gst_percent, cif_term_amount_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$agency_name', $bill_count, '$bill_date', $total_bill_amount, $gst_percent, $cif_term_amount_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "CFS saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }
    
    
    
    // ============================================
    // FREIGHT FORWARDER OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveFreightForwarder") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $forwarder_name = $conn->real_escape_string($data['forwarder_name'] ?? '');
            $forwarder_inv_date = $conn->real_escape_string($data['forwarder_inv_date'] ?? '');
            $forwarder_inv_no = $conn->real_escape_string($data['forwarder_inv_no'] ?? '');
            $origin_charges = floatval($data['origin_charges'] ?? 0);
            $origin_charges_gst_percent = floatval($data['origin_charges_gst_percent'] ?? 0);
            $origin_charges_with_gst = floatval($data['origin_charges_with_gst'] ?? 0);
            $ocean_freight = floatval($data['ocean_freight'] ?? 0);
            $ocean_freight_gst_percent = floatval($data['ocean_freight_gst_percent'] ?? 0);
            $ocean_freight_with_gst = floatval($data['ocean_freight_with_gst'] ?? 0);
            $destination_inv_date = $conn->real_escape_string($data['destination_inv_date'] ?? '');
            $destination_inv_no = $conn->real_escape_string($data['destination_inv_no'] ?? '');
            $destination_charges = floatval($data['destination_charges'] ?? 0);
            $destination_charges_gst_percent = floatval($data['destination_charges_gst_percent'] ?? 0);
            $destination_charges_with_gst = floatval($data['destination_charges_with_gst'] ?? 0);
            $total_forwarder_amount = floatval($data['total_forwarder_amount'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM freight_forwarder WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM freight_forwarder WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO freight_forwarder_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    forwarder_name, forwarder_inv_date, forwarder_inv_no, origin_charges, origin_charges_gst_percent,
                    origin_charges_with_gst, ocean_freight, ocean_freight_gst_percent, ocean_freight_with_gst,
                    destination_inv_date, destination_inv_no, destination_charges, destination_charges_gst_percent,
                    destination_charges_with_gst, total_forwarder_amount,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    forwarder_name, forwarder_inv_date, forwarder_inv_no, origin_charges, origin_charges_gst_percent,
                    origin_charges_with_gst, ocean_freight, ocean_freight_gst_percent, ocean_freight_with_gst,
                    destination_inv_date, destination_inv_no, destination_charges, destination_charges_gst_percent,
                    destination_charges_with_gst, total_forwarder_amount,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM freight_forwarder WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE freight_forwarder SET
                    forwarder_name = '$forwarder_name',
                    forwarder_inv_date = '$forwarder_inv_date',
                    forwarder_inv_no = '$forwarder_inv_no',
                    origin_charges = $origin_charges,
                    origin_charges_gst_percent = $origin_charges_gst_percent,
                    origin_charges_with_gst = $origin_charges_with_gst,
                    ocean_freight = $ocean_freight,
                    ocean_freight_gst_percent = $ocean_freight_gst_percent,
                    ocean_freight_with_gst = $ocean_freight_with_gst,
                    destination_inv_date = '$destination_inv_date',
                    destination_inv_no = '$destination_inv_no',
                    destination_charges = $destination_charges,
                    destination_charges_gst_percent = $destination_charges_gst_percent,
                    destination_charges_with_gst = $destination_charges_with_gst,
                    total_forwarder_amount = $total_forwarder_amount,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO freight_forwarder(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    forwarder_name, forwarder_inv_date, forwarder_inv_no, origin_charges, origin_charges_gst_percent,
                    origin_charges_with_gst, ocean_freight, ocean_freight_gst_percent, ocean_freight_with_gst,
                    destination_inv_date, destination_inv_no, destination_charges, destination_charges_gst_percent,
                    destination_charges_with_gst, total_forwarder_amount,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$forwarder_name', '$forwarder_inv_date', '$forwarder_inv_no', $origin_charges, $origin_charges_gst_percent,
                    $origin_charges_with_gst, $ocean_freight, $ocean_freight_gst_percent, $ocean_freight_with_gst,
                    '$destination_inv_date', '$destination_inv_no', $destination_charges, $destination_charges_gst_percent,
                    $destination_charges_with_gst, $total_forwarder_amount,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Freight forwarder saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }
    
     // ============================================
    // DESTINATION CLEARING OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveDestinationClearing") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $cha_name = $conn->real_escape_string($data['cha_name'] ?? '');
            $inv_date = $conn->real_escape_string($data['inv_date'] ?? '');
            $inv_no = $conn->real_escape_string($data['inv_no'] ?? '');
            $cha_clearing_agent_fees = floatval($data['cha_clearing_agent_fees'] ?? 0);
            $document_fee = floatval($data['document_fee'] ?? 0);
            $loading_and_unloading = floatval($data['loading_and_unloading'] ?? 0);
            $other_charges = floatval($data['other_charges'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $stamp_duty = floatval($data['stamp_duty'] ?? 0);
            $stamp_duty_gst_percent = floatval($data['stamp_duty_gst_percent'] ?? 0);
            $total_cha_with_gst = floatval($data['total_cha_with_gst'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM destination_clearing WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM destination_clearing WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO destination_clearing_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    cha_name, inv_date, inv_no, cha_clearing_agent_fees, document_fee, loading_and_unloading,
                    other_charges, gst_percent, stamp_duty, stamp_duty_gst_percent, total_cha_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    cha_name, inv_date, inv_no, cha_clearing_agent_fees, document_fee, loading_and_unloading,
                    other_charges, gst_percent, stamp_duty, stamp_duty_gst_percent, total_cha_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM destination_clearing WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE destination_clearing SET
                    cha_name = '$cha_name',
                    inv_date = '$inv_date',
                    inv_no = '$inv_no',
                    cha_clearing_agent_fees = $cha_clearing_agent_fees,
                    document_fee = $document_fee,
                    loading_and_unloading = $loading_and_unloading,
                    other_charges = $other_charges,
                    gst_percent = $gst_percent,
                    stamp_duty = $stamp_duty,
                    stamp_duty_gst_percent = $stamp_duty_gst_percent,
                    total_cha_with_gst = $total_cha_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO destination_clearing(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    cha_name, inv_date, inv_no, cha_clearing_agent_fees, document_fee, loading_and_unloading,
                    other_charges, gst_percent, stamp_duty, stamp_duty_gst_percent, total_cha_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$cha_name', '$inv_date', '$inv_no', $cha_clearing_agent_fees, $document_fee, $loading_and_unloading,
                    $other_charges, $gst_percent, $stamp_duty, $stamp_duty_gst_percent, $total_cha_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Destination clearing saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }
    
    
    // ============================================
    // TRANSPORT OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveTransport") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $transport_name = $conn->real_escape_string($data['transport_name'] ?? '');
            $transport_bill_no = $conn->real_escape_string($data['transport_bill_no'] ?? '');
            $transport_bill_date = $conn->real_escape_string($data['transport_bill_date'] ?? '');
            $inland_transportation = floatval($data['inland_transportation'] ?? 0);
            $unloading_charges = floatval($data['unloading_charges'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $total_transport_amount_with_gst = floatval($data['total_transport_amount_with_gst'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM transport WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM transport WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO transport_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    transport_name, transport_bill_no, transport_bill_date, inland_transportation, unloading_charges,
                    gst_percent, total_transport_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    transport_name, transport_bill_no, transport_bill_date, inland_transportation, unloading_charges,
                    gst_percent, total_transport_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM transport WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE transport SET
                    transport_name = '$transport_name',
                    transport_bill_no = '$transport_bill_no',
                    transport_bill_date = '$transport_bill_date',
                    inland_transportation = $inland_transportation,
                    unloading_charges = $unloading_charges,
                    gst_percent = $gst_percent,
                    total_transport_amount_with_gst = $total_transport_amount_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO transport(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    transport_name, transport_bill_no, transport_bill_date, inland_transportation, unloading_charges,
                    gst_percent, total_transport_amount_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$transport_name', '$transport_bill_no', '$transport_bill_date', $inland_transportation, $unloading_charges,
                    $gst_percent, $total_transport_amount_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Transport saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }


  
    // ============================================
    // PAYMENT BANK OPERATIONS
    // ============================================
    
    if($_GET["type"] == "savePaymentBank") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $ref_document_no = $conn->real_escape_string($data['ref_document_no'] ?? '');
            $bank_name = $conn->real_escape_string($data['bank_name'] ?? '');
            $paid_amount = floatval($data['paid_amount'] ?? 0);
            $paid_date = $conn->real_escape_string($data['paid_date'] ?? '');
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $total_paid_with_gst = floatval($data['total_paid_with_gst'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM payment_bank WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM payment_bank WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO payment_bank_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    ref_document_no, bank_name, paid_amount, paid_date, gst_percent, total_paid_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    ref_document_no, bank_name, paid_amount, paid_date, gst_percent, total_paid_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM payment_bank WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE payment_bank SET
                    ref_document_no = '$ref_document_no',
                    bank_name = '$bank_name',
                    paid_amount = $paid_amount,
                    paid_date = '$paid_date',
                    gst_percent = $gst_percent,
                    total_paid_with_gst = $total_paid_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO payment_bank(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    ref_document_no, bank_name, paid_amount, paid_date, gst_percent, total_paid_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$ref_document_no', '$bank_name', $paid_amount, '$paid_date', $gst_percent, $total_paid_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Payment bank saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }



    // ============================================
    // WAREHOUSING OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveWarehousing") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $agency_name = $conn->real_escape_string($data['agency_name'] ?? '');
            $agency_bill_no = $conn->real_escape_string($data['agency_bill_no'] ?? '');
            $agency_bill_date = $conn->real_escape_string($data['agency_bill_date'] ?? '');
            $bill_value = floatval($data['bill_value'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $total_warehouse_amount_with_gst = floatval($data['total_warehouse_amount_with_gst'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM warehousing WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM warehousing WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO warehousing_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, agency_bill_date, bill_value, gst_percent, total_warehouse_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, agency_bill_date, bill_value, gst_percent, total_warehouse_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM warehousing WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE warehousing SET
                    agency_name = '$agency_name',
                    agency_bill_no = '$agency_bill_no',
                    agency_bill_date = '$agency_bill_date',
                    bill_value = $bill_value,
                    gst_percent = $gst_percent,
                    total_warehouse_amount_with_gst = $total_warehouse_amount_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO warehousing(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, agency_bill_date, bill_value, gst_percent, total_warehouse_amount_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$agency_name', '$agency_bill_no', '$agency_bill_date', $bill_value, $gst_percent, $total_warehouse_amount_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Warehousing saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }

  
    // ============================================
    // INHAND OPERATIONS
    // ============================================
    
    if($_GET["type"] == "saveInhand") {
        try {
            $data = array();
            if(!empty($_POST)) {
                $data = $_POST;
            } elseif(!empty($input)) {
                $data = $input;
            } else {
                echo json_encode(array("status" => "error", "message" => "No data received"));
                exit();
            }
            
            // Escape input
            $id = $conn->real_escape_string($data['id'] ?? '');
            $po_no = $conn->real_escape_string($data['po_no'] ?? '');
            $challan_no = $conn->real_escape_string($data['challan_no'] ?? '');
            $grn_no = $conn->real_escape_string($data['grn_no'] ?? '');
            $item_code = $conn->real_escape_string($data['item_code'] ?? '');
            $item_description = $conn->real_escape_string($data['item_description'] ?? '');
            $quantity = floatval($data['quantity'] ?? 0);
            $uom = $conn->real_escape_string($data['uom'] ?? '');
            $agency_name = $conn->real_escape_string($data['agency_name'] ?? '');
            $agency_bill_no = $conn->real_escape_string($data['agency_bill_no'] ?? '');
            $agency_bill_date = $conn->real_escape_string($data['agency_bill_date'] ?? '');
            $inspection_survey_fees = floatval($data['inspection_survey_fees'] ?? 0);
            $gst_percent = floatval($data['gst_percent'] ?? 0);
            $total_amount_with_gst = floatval($data['total_amount_with_gst'] ?? 0);
            
            // Check if record exists
            $check_sql = "";
            $record_exists = false;
            $existing_id = '';
            
            if(!empty($id)) {
                $check_sql = "SELECT id FROM inhand WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            // If not found by ID, check by po_no, challan_no, and grn_no combination
            if(!$record_exists && !empty($po_no) && !empty($challan_no) && !empty($grn_no)) {
                $check_sql = "SELECT id FROM inhand WHERE po_no = '$po_no' AND challan_no = '$challan_no' AND grn_no = '$grn_no'";
                $check_result = $conn->query($check_sql);
                if($check_result && $check_result->num_rows > 0) {
                    $record_exists = true;
                    $existing = $check_result->fetch_assoc();
                    $existing_id = $existing['id'];
                }
            }
            
            if($record_exists && !empty($existing_id)) {
                // Save history before updating
                $history_sql = "INSERT INTO inhand_history(
                    original_id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, agency_bill_date, inspection_survey_fees, gst_percent, total_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, history_created_by
                ) SELECT 
                    id, plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, agency_bill_date, inspection_survey_fees, gst_percent, total_amount_with_gst,
                    created_by, created_date_time, last_modified_by, modified_date_time, ip_logged, '".$_GET["emp_id"]."'
                FROM inhand WHERE id = '$existing_id'";
                $conn->query($history_sql);
                
                // Update existing record
                $id = $existing_id;
                $sql = "UPDATE inhand SET
                    agency_name = '$agency_name',
                    agency_bill_no = '$agency_bill_no',
                    agency_bill_date = '$agency_bill_date',
                    inspection_survey_fees = $inspection_survey_fees,
                    gst_percent = $gst_percent,
                    total_amount_with_gst = $total_amount_with_gst,
                    item_code = '$item_code',
                    item_description = '$item_description',
                    quantity = $quantity,
                    uom = '$uom',
                    last_modified_by = '".$_GET["emp_id"]."',
                    modified_date_time = '$entry_date'
                    WHERE id = '$id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO inhand(
                    plant_id, po_no, challan_no, grn_no, item_code, item_description, quantity, uom,
                    agency_name, agency_bill_no, agency_bill_date, inspection_survey_fees, gst_percent, total_amount_with_gst,
                    created_by, created_date_time, ip_logged
                ) VALUES (
                    '".$_GET["plant_id"]."', '$po_no', '$challan_no', '$grn_no', '$item_code', '$item_description', $quantity, '$uom',
                    '$agency_name', '$agency_bill_no', '$agency_bill_date', $inspection_survey_fees, $gst_percent, $total_amount_with_gst,
                    '".$_GET["emp_id"]."', '$entry_date', '".$_SERVER['REMOTE_ADDR']."'
                )";
            }
            
            if($conn->query($sql)) {
                echo json_encode(array("status" => "success", "message" => "Inhand saved successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $conn->error));
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        exit();
    }

}

$conn->close();
?>

