<?php
require '../db.php';
require '../token.php';
//  require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/SMTP.php';

// // Import namespaces
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;
// use PHPMailer\PHPMailer\SMTP;
header('Access-Control-Allow-Origin: *');

        ini_set('display_errors', 1);
        error_reporting(E_ALL);


date_default_timezone_set("Asia/Kolkata");

function escLead($conn, $value) {
    return $conn->real_escape_string(isset($value) ? $value : '');
}

function ensureEnquiryServiceColumns($conn) {
    $cols = array(
        'serviceCategory' => 'TEXT NULL',
        'serviceDescription' => 'TEXT NULL',
        'serviceDescriptionData' => 'LONGTEXT NULL',
        'selectedProductCodes' => 'TEXT NULL'
    );
    foreach ($cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `enquiry` LIKE '".$col."'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `enquiry` ADD COLUMN `".$col."` ".$def);
        }
    }
}

function syncClientServiceFromLead($conn, $client_code, $input) {
    if ($client_code == '') {
        return;
    }
    $serviceCategory = escLead($conn, isset($input['serviceCategory']) ? $input['serviceCategory'] : '');
    $serviceDescription = escLead($conn, isset($input['serviceDescription']) ? $input['serviceDescription'] : '');
    $serviceDescriptionData = escLead($conn, isset($input['serviceDescriptionData']) ? $input['serviceDescriptionData'] : '');
    $selectedProductCodes = escLead($conn, isset($input['selectedProductCodes']) ? $input['selectedProductCodes'] : '');

    $tables = array('client', 'tempClient');
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '".$table."'");
        if (!$check || $check->num_rows == 0) {
            continue;
        }
        $colCheck = $conn->query("SHOW COLUMNS FROM `".$table."` LIKE 'serviceCategory'");
        if (!$colCheck || $colCheck->num_rows == 0) {
            continue;
        }
        $sql = "UPDATE `".$table."` SET
            `serviceCategory`='".$serviceCategory."',
            `serviceDescription`='".$serviceDescription."',
            `serviceDescriptionData`='".$serviceDescriptionData."',
            `selectedProductCodes`='".$selectedProductCodes."'
            WHERE `client_code`='".escLead($conn, $client_code)."'";
        $conn->query($sql);
    }
}

function assignEnquiryNumber($conn, $enquiryId, $plant_id) {
    $enquiryId = (int)$enquiryId;
    if ($enquiryId <= 0) {
        return '';
    }
    $check = $conn->query("SHOW COLUMNS FROM `enquiry` LIKE 'enquiry_no'");
    if (!$check || $check->num_rows == 0) {
        return '';
    }
    $result = $conn->query("SELECT enquiry_no FROM enquiry WHERE id = ".$enquiryId." LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['enquiry_no'])) {
            return $row['enquiry_no'];
        }
    }
    $enquiry_no = 'ENQ'.escLead($conn, $plant_id).str_pad((string)$enquiryId, 5, '0', STR_PAD_LEFT);
    $conn->query("UPDATE enquiry SET enquiry_no = '".$enquiry_no."' WHERE id = ".$enquiryId);
    return $enquiry_no;
}

function leadEnquiryProductColumns($conn) {
    static $cols = null;
    if ($cols !== null) {
        return $cols;
    }
    $cols = array();
    $res = $conn->query("SHOW COLUMNS FROM enquiryProduct");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }
    return $cols;
}

function leadUpdateEnquiryProductFields($conn, $productId, $fieldValues) {
    $cols = leadEnquiryProductColumns($conn);
    if (count($cols) === 0) {
        return false;
    }
    $setParts = array();
    foreach ($fieldValues as $field => $value) {
        if (isset($cols[$field])) {
            $setParts[] = "`".$field."`='".escLead($conn, $value)."'";
        }
    }
    if (count($setParts) === 0) {
        return false;
    }
    $sql = "UPDATE enquiryProduct SET ".implode(', ', $setParts)." WHERE id='".escLead($conn, $productId)."'";
    return $conn->query($sql);
}

$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
if (!is_array($input)) {
    $input = array();
}

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
    
    if ($_GET["type"] == "getClients") {
        $output = Array();
        $sql = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"]=="saveLead") {
        $sql = "SELECT IFNULL(MAX(i_no), 0) as i_no FROM enquiry WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        $no = "";
        $i_no = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
            }
        }
        $i_no++;
        $num = strlen($i_no);
        $no = "ENR".$i_no;
         $sql = "INSERT INTO enquiry (user_no,enquiry_no, entry_date, client_code, region, country, through, reference, details, i_no, products,plant_id) 
         VALUES ('".$_GET["user_no"]."','$no', '$entry_date', '".$input["client_code"]."', '".$input["region"]."', '".$input["country"]."', 
         '".$input["through"]."', '".$input["reference"]."', '".$input["details"]."', '$i_no', '".json_encode($input["products"])."','".$_GET["plant_id"]."')"; 
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     
    else if ($_GET["type"] == "saveAction") {
        
        $sql = "INSERT INTO `enquiry_actions`(`plant_id`, `enquiry_no`, `date`, `action`, `remark`) VALUES ('".$_GET["plant_id"]."',
        '".$input["enquiry_no"]."', '".$input["date"]."', '".$input["action"]."', '".$input["remark"]."')";
        
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveLeads") {
        $sql = "INSERT INTO enquiry (user_no,enquiry_no, through, entry_date, complete_date,status) 
        VALUES ('".$_GET["user_no"]."','".$_GET["enquiry_no"]."', '".$input["through"]."', 
        '".$input["entry_date"]."', '".$input["complete_date"]."','".$input["status"]."')";
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
     else if ($_GET["type"] == "saveLeadEnquiry") {
         
            $input = $_POST;
            ensureEnquiryServiceColumns($conn);
            
            $plant_id =  $_GET["plant_id"];
            $emp_id   =  $_GET["emp_id"];
            $r = mt_rand(1000, 9999);
            $no = $plant_id.$emp_id.$r;
            
           $target_dir = "../../../upload/leads/";
         
           
           if(isset($_FILES["other_req"]["name"])) {
            	$target_file3 = $target_dir.$no."other_req"."_".basename($_FILES["other_req"]["name"]);
            	$other_req = $no."other_req"."_".basename($_FILES["other_req"]["name"]);
        	    move_uploaded_file($_FILES["other_req"]["tmp_name"], $target_file3);
           }else{
               $other_req = 'NA';
           }
         
            $client_code         = escLead($conn, isset($input["client_code"]) ? $input["client_code"] : '');
            $enqGeneratedThrough = escLead($conn, isset($input["enqGeneratedThrough"]) ? $input["enqGeneratedThrough"] : '');
            $reference           = escLead($conn, isset($input["reference"]) ? $input["reference"] : '');
            $sample_required     = escLead($conn, isset($input["sample_required"]) ? $input["sample_required"] : '');
            $sample_qty          = escLead($conn, isset($input["sample_qty"]) ? $input["sample_qty"] : '');
            $reference_sample    = escLead($conn, isset($input["reference_sample"]) ? $input["reference_sample"] : '');
            $refNoOfunits        = escLead($conn, isset($input["refNoOfunits"]) ? $input["refNoOfunits"] : '');
            $country             = escLead($conn, isset($input["country"]) ? $input["country"] : '');
            $details             = escLead($conn, isset($input["details"]) ? $input["details"] : '');
            $other_req           = $conn->real_escape_string($other_req);
            $serviceCategory     = escLead($conn, isset($input["serviceCategory"]) ? $input["serviceCategory"] : '');
            $serviceDescription  = escLead($conn, isset($input["serviceDescription"]) ? $input["serviceDescription"] : '');
            $serviceDescriptionData = escLead($conn, isset($input["serviceDescriptionData"]) ? $input["serviceDescriptionData"] : '');
            $selectedProductCodes = escLead($conn, isset($input["selectedProductCodes"]) ? $input["selectedProductCodes"] : '');
            $entry_date          = date("Y-m-d H:i:s");

 
            $sql = "INSERT INTO enquiry (plant_id, status, client_code, enqGeneratedThrough, reference, sample_required, sample_qty, reference_sample,refNoOfunits, 
            country, details, other_req, serviceCategory, serviceDescription, serviceDescriptionData, selectedProductCodes, entryBy, entryOn) VALUES ( '$plant_id', 'Pending', '$client_code', 
            '$enqGeneratedThrough', '$reference', '$sample_required', '$sample_qty', '$reference_sample', '$refNoOfunits', '$country', '$details', 
            '$other_req', '$serviceCategory', '$serviceDescription', '$serviceDescriptionData', '$selectedProductCodes', '$emp_id', '$entry_date')";
         
            if($conn->query($sql)) {
                $enquiryId = $conn->insert_id;
                assignEnquiryNumber($conn, $enquiryId, $plant_id);
                syncClientServiceFromLead($conn, $client_code, $input);

                $array = json_decode(isset($input["productsList"]) ? $input["productsList"] : '[]', true);
                if (!is_array($array)) {
                    $array = array();
                }

                foreach($array as $values) {
                    if (!is_array($values)) {
                        continue;
                    }
                    $status                    = "Pending";
                    $tentative_launch_dt       = escLead($conn, isset($values["tentative_launch_dt"]) ? $values["tentative_launch_dt"] : '');
                    $product_name              = escLead($conn, isset($values["product_name"]) ? $values["product_name"] : '');
                    $suggestive_fill_volume    = escLead($conn, isset($values["suggestive_fill_volume"]) ? $values["suggestive_fill_volume"] : '');
                    $demography                = escLead($conn, isset($values["demography"]) ? $values["demography"] : '');
                    $projected_volume          = escLead($conn, isset($values["projected_volume"]) ? $values["projected_volume"] : '');
                    $projected_volume_for      = escLead($conn, isset($values["projected_volume_for"]) ? $values["projected_volume_for"] : '');
                    $fg_benchmark_product      = escLead($conn, isset($values["fg_benchmark_product"]) ? $values["fg_benchmark_product"] : '');
                    $fragrance_reference       = escLead($conn, isset($values["fragrance_reference"]) ? $values["fragrance_reference"] : '');
                    $fg_benchmark_productCode  = escLead($conn, isset($values["fg_benchmark_productCode"]) ? $values["fg_benchmark_productCode"] : '');
                    $pack_size                 = escLead($conn, isset($values["pack_size"]) ? $values["pack_size"] : '');
                    $target_price              = escLead($conn, isset($values["target_price"]) ? $values["target_price"] : '');
                    $mrp                       = escLead($conn, isset($values["mrp"]) ? $values["mrp"] : '');
                    $otherDescription          = escLead($conn, isset($values["otherDescription"]) ? $values["otherDescription"] : '');

                    $sql1 = "INSERT INTO enquiryProduct (plant_id, status, enquiryId, client_code, tentative_launch_dt, product_name, suggestive_fill_volume, 
                    demography, projected_volume, projected_volume_for, fg_benchmark_product, fragrance_reference,fg_benchmark_productCode, pack_size, target_price, 
                    mrp, otherDescription) VALUES ('$plant_id', '$status', '$enquiryId','$client_code', '$tentative_launch_dt', '$product_name', 
                    '$suggestive_fill_volume', '$demography', '$projected_volume', '$projected_volume_for','$fg_benchmark_product', '$fragrance_reference',
                    '$fg_benchmark_productCode', '$pack_size', '$target_price','$mrp', '$otherDescription')";
                    $conn->query($sql1);
                }

                echo json_encode(array("status" => "success", "enquiryId" => $enquiryId));
            } else {
                echo json_encode(array("status" => "failed", "message" => $conn->error));
            }
        
    }
    else if ($_GET["type"] == "getLeadEnquiry") {
        $output = Array();
        $plant_id = escLead($conn, $_GET["plant_id"]);
        
        if($_GET['leadFor'] == 'Yes'){
        	$sql = "SELECT e.*,c.LglNm FROM enquiry e LEFT JOIN client_combined_view c ON e.client_code = c.client_code
        	WHERE e.plant_id = '".$plant_id."' order by e.id desc"; 
        }else{
        	$sql = "SELECT e.*,c.LglNm FROM enquiry e LEFT JOIN client_combined_view c ON e.client_code = c.client_code 
        	WHERE e.plant_id = '".$plant_id."' AND e.entryBy = '".escLead($conn, $_GET["emp_id"])."' order by e.id desc"; 
        }
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   
                $output1 = Array();
                $sql1 = "SELECT * FROM enquiryProduct where enquiryId = '".$row["id"]."' ";   
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row['productList'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getInprocessLeads") {
        $output = Array();
        $sql = "SELECT e.*,c.LglNm FROM enquiry e LEFT JOIN client_combined_view c ON e.client_code = c.client_code  WHERE 
        e.status = 'Pending' AND e.entryBy = '".$_GET["emp_id"]."' order by e.id desc";   
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   
                $output1 = Array();
                $sql1 = "SELECT * FROM enquiryProduct where enquiryId = '".$row["id"]."' ";   
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $output11 = Array();
                $sql11 = "SELECT * FROM enquiry_actions WHERE enquiry_no = '".$row["enquiry_no"]."' AND plant_id = '".$_GET["plant_id"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $output11[] = $row11;
                    }
                }
                
                $row["actions"] = $output11;
                $row['productList'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getLeadsLog") {
        $output = Array();
        $plant_id = escLead($conn, $_GET["plant_id"]);
        
        if($_GET['leadFor'] == 'Yes'){
            $sql = "SELECT e.*,c.LglNm,c.status as clientStatus,c.source_table FROM enquiry e LEFT JOIN client_combined_view c ON e.client_code = c.client_code
            WHERE e.plant_id = '".$plant_id."' order by e.id desc"; 
        }else{
               $sql = "SELECT e.*,c.LglNm ,c.status as clientStatus,c.source_table FROM enquiry e LEFT JOIN client_combined_view c ON e.client_code = c.client_code
            WHERE e.plant_id = '".$plant_id."' AND e.entryBy = '".escLead($conn, $_GET["emp_id"])."' order by e.id desc"; 
        }
        
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   
                $output1 = Array();
                $sql1 = "SELECT * FROM enquiryProduct where enquiryId = '".$row["id"]."' ";   
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $output11 = Array();
                $sql11 = "SELECT * FROM enquiry_actions WHERE enquiry_no = '".$row["enquiry_no"]."' AND plant_id = '".$_GET["plant_id"]."'";
                $result11 = $conn->query($sql11);
                if ($result11->num_rows > 0) {
                    while ($row11 = $result11->fetch_assoc()) {
                        $output11[] = $row11;
                    }
                }
                
                $row["actions"] = $output11;
                $row['productList'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getMarketingLeadForRegulatory") {
        $output = Array();
        
        $sql = "SELECT e.*,c.LglNm FROM enquiry e LEFT JOIN client_combined_view c ON e.client_code = c.client_code  WHERE 
        e.status = 'Complete'   order by e.id desc"; 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                   
                $output1 = Array();
                $sql1 = "SELECT * FROM enquiryProduct where enquiryId = '".$row["id"]."' AND status != 'Pending'";   
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
    
                $row['productList'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "completeEnquiry") {
        
        $sql = "UPDATE enquiry SET status = '".$input["status"]."' , complete_by = '".$_GET["emp_id"]."', complete_date = '$entry_date' WHERE id = '".$input["id"]."'";
       
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    else if ($_GET["type"] == "makeFinalUpdateProduct") {
        if (!is_array($input) || empty($input['id'])) {
            echo json_encode(array('status' => 'error', 'message' => 'Product id is required'));
            exit;
        }

        $productId = escLead($conn, $input['id']);
        $clientCode = isset($input['client_code']) ? escLead($conn, $input['client_code']) : '';

        if ($clientCode === '') {
            $prodRes = $conn->query("SELECT client_code, enquiryId FROM enquiryProduct WHERE id='".$productId."' LIMIT 1");
            if ($prodRes && $prodRes->num_rows > 0) {
                $prodRow = $prodRes->fetch_assoc();
                $clientCode = escLead($conn, isset($prodRow['client_code']) ? $prodRow['client_code'] : '');
                if ($clientCode === '' && !empty($prodRow['enquiryId'])) {
                    $enqRes = $conn->query("SELECT client_code FROM enquiry WHERE id='".escLead($conn, $prodRow['enquiryId'])."' LIMIT 1");
                    if ($enqRes && $enqRes->num_rows > 0) {
                        $enqRow = $enqRes->fetch_assoc();
                        $clientCode = escLead($conn, isset($enqRow['client_code']) ? $enqRow['client_code'] : '');
                    }
                }
            }
        }

        if ($clientCode === '' && !empty($input['enquiryId'])) {
            $enqRes = $conn->query("SELECT client_code FROM enquiry WHERE id='".escLead($conn, $input['enquiryId'])."' LIMIT 1");
            if ($enqRes && $enqRes->num_rows > 0) {
                $enqRow = $enqRes->fetch_assoc();
                $clientCode = escLead($conn, isset($enqRow['client_code']) ? $enqRow['client_code'] : '');
            }
        }

        $prefix = 'FGRD'.$clientCode.'01-';
        $nextNumber = 1;

        $latestSql = "SELECT fg_benchmark_productCode
                      FROM enquiryProduct
                      WHERE fg_benchmark_productCode LIKE '".$prefix."%'
                      ORDER BY id DESC LIMIT 1";
        $result = $conn->query($latestSql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastCode = $row['fg_benchmark_productCode'];
            $parts = explode('-', $lastCode);
            if (isset($parts[1])) {
                $lastNum = intval($parts[1]);
                $nextNumber = $lastNum + 1;
            }
        }

        $productCode = $prefix . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

        $fieldValues = array(
            'status' => 'FINAL',
            'finalBy' => $_GET['emp_id'],
            'finalOn' => $entry_date,
            'tentative_launch_dt' => isset($input['tentative_launch_dt']) ? $input['tentative_launch_dt'] : '',
            'product_name' => isset($input['product_name']) ? $input['product_name'] : '',
            'suggestive_fill_volume' => isset($input['suggestive_fill_volume']) ? $input['suggestive_fill_volume'] : '',
            'demography' => isset($input['demography']) ? $input['demography'] : '',
            'projected_volume' => isset($input['projected_volume']) ? $input['projected_volume'] : '',
            'projected_volume_for' => isset($input['projected_volume_for']) ? $input['projected_volume_for'] : '',
            'primary_packaging_type' => isset($input['primary_packaging_type']) ? $input['primary_packaging_type'] : '',
            'secondary_packaging_type' => isset($input['secondary_packaging_type']) ? $input['secondary_packaging_type'] : '',
            'primary_claims' => isset($input['primary_claims']) ? $input['primary_claims'] : '',
            'secondary_claim' => isset($input['secondary_claim']) ? $input['secondary_claim'] : '',
            'product_certification' => isset($input['product_certification']) ? $input['product_certification'] : '',
            'colour_match_reference' => isset($input['colour_match_reference']) ? $input['colour_match_reference'] : '',
            'textures_appearance_reference' => isset($input['textures_appearance_reference']) ? $input['textures_appearance_reference'] : '',
            'fg_benchmark_product' => isset($input['fg_benchmark_product']) ? $input['fg_benchmark_product'] : '',
            'fg_benchmark_productCode' => isset($input['fg_benchmark_productCode']) ? $input['fg_benchmark_productCode'] : '',
            'pack_size' => isset($input['pack_size']) ? $input['pack_size'] : '',
            'performance_expectation' => isset($input['performance_expectation']) ? $input['performance_expectation'] : '',
            'benchmark_ingredient_list' => isset($input['benchmark_ingredient_list']) ? $input['benchmark_ingredient_list'] : '',
            'fragrance_reference' => isset($input['fragrance_reference']) ? $input['fragrance_reference'] : '',
            'phase_of_development' => isset($input['phase_of_development']) ? $input['phase_of_development'] : '',
            'export_certification' => isset($input['export_certification']) ? $input['export_certification'] : '',
            'shelf_life' => isset($input['shelf_life']) ? $input['shelf_life'] : '',
            'mandatory_tests_reports' => isset($input['mandatory_tests_reports']) ? $input['mandatory_tests_reports'] : '',
            'target_price' => isset($input['target_price']) ? $input['target_price'] : '',
            'mrp' => isset($input['mrp']) ? $input['mrp'] : '',
            'sample_timeline' => isset($input['sample_timeline']) ? $input['sample_timeline'] : '',
            'formula_number' => isset($input['formula_number']) ? $input['formula_number'] : '',
            'submission_date' => isset($input['submission_date']) ? $input['submission_date'] : '',
            'revision_required' => isset($input['revision_required']) ? $input['revision_required'] : '',
            'second_sample_submission' => isset($input['second_sample_submission']) ? $input['second_sample_submission'] : '',
            'otherDescription' => isset($input['otherDescription']) ? $input['otherDescription'] : '',
        );

        $cols = leadEnquiryProductColumns($conn);
        if (isset($cols['product_code'])) {
            $fieldValues['product_code'] = $productCode;
        }

        if (leadUpdateEnquiryProductFields($conn, $productId, $fieldValues)) {
            echo json_encode(array('status' => 'success', 'product_code' => $productCode));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $conn->error ?: 'Could not update product'));
        }
    } 
     
      else if($_GET["type"]=="getClientsLog") {
        $output = array();
    	 $sql = "SELECT c.*, a.agent_name, s.state_name FROM client c LEFT JOIN agent a ON c.agent_no=a.agent_no 
    	LEFT JOIN state s ON c.state_code=s.state_code WHERE c.user_no='".$_GET["user_no"]."'"; //ORDER BY c.company";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $row["branch"] = json_decode($row["branch"]);
    		   $row["divisions"] = json_decode($row["divisions"]);
    		    $output[] = $row;
    		}
    	}	
        echo json_encode($output);
    }
    
    
    
    
    
    else if($_GET["type"]=="saveNewClient") {
        
    $sql = "INSERT INTO client (plant_id, LglNm, TrdNm, company, phone,email,country,address ,entry_by, entry_date,status) VALUES
    ('" . $input["name"] . "', '" . $input["client_name"] . "', '".$input["client_name"]."', '" . $input["client_name"] . "',
    '" . $input["contact"] . "','" . $input["email"] . "','" . $input["country"] . "','" . $input["address"] . "', 
    '" . $_GET["emp_id"] . "', '" . $entry_date . "','New')";
    
    
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
      else if ($_GET["type"] == "sendMailtoClient"){
        
        
             $sql0 = "SELECT p.id,p.user_no,p.plant_id,p.po_no,p.vendor_no,p.net_total,p.entry_date,v.vendor_name,v.email,a.plant_full_name
            FROM purchaseorder p INNER JOIN vendor v ON p.vendor_no = v.vendor_no 
            INNER JOIN plant a ON p.plant_id = a.plant_id
            WHERE p.po_no ='" . $input['po_no'] . "'";
            
                 $result0 = $conn->query($sql0);
                if ($result0->num_rows > 0)
              
                    while ($row0 = $result0->fetch_assoc())
                    {
                          $pid = $row0['id'];
                          $po_no = $row0['po_no'];
                          $user_no = $row0['user_no'];
                          $plant_id = $row0['plant_id'];
                          $vendor_no = $row0['vendor_no']; 
                          $net_total = $row0['net_total'];
                          $approve_date = $row0['entry_date']; //podate
                          $vendor_name = $row0['vendor_name'];
                          $plant_full_name = $row0['plant_full_name'];
                    }
        
         $email = $input['client_email'];
        
        
        
            $msg = '
            
            
            <!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title></title>
    <style type="text/css" rel="stylesheet" media="all">
        
        @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            -webkit-text-size-adjust: none;
        }
        a {
            color: #3869D4;
        }
        a img {
            border: none;
        }
        td {
            word-break: break-word;
        }
        body,
        td,
        th {
            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
        }
        h1 {
            margin-top: 0;
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
        }
        h2 {
            margin-top: 0;
            color: #333333;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }
        h3 {
            margin-top: 0;
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }
        td,
        th {
            font-size: 16px;
        }
        p,
        ul,
        ol,
        blockquote {
            margin: .4em 0 1.1875em;
            font-size: 16px;
            line-height: 1.625;
        }

        p.sub {
            font-size: 13px;
        }
        /* Utilities ------------------------------ */
        .align-right {
            text-align: right;
        }

        .align-left {
            text-align: left;
        }

        .align-center {
            text-align: center;
        }

        .u-margin-bottom-none {
            margin-bottom: 0;
        }
        /* Buttons ------------------------------ */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
                text-align: center !important;
            }
        }
        /* Attribute list ------------------------------ */
        .attributes {
            margin: 0 0 21px;
        }

        .attributes_content {
            background-color: #F4F4F7;
            padding: 16px;
        }

        .attributes_item {
            padding: 0;
        }
        /* Related Items ------------------------------ */
        .related {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .related_item {
            padding: 10px 0;
            color: #CBCCCF;
            font-size: 15px;
            line-height: 18px;
        }

        .related_item-title {
            display: block;
            margin: .5em 0 0;
        }

        .related_item-thumb {
            display: block;
            padding-bottom: 10px;
        }

        .related_heading {
            border-top: 1px solid #CBCCCF;
            text-align: center;
            padding: 25px 0 10px;
        }
        /* Social Icons ------------------------------ */
        .social {
            width: auto;
        }
        .social td {
            padding: 0;
            width: auto;
        }
        .social_icon {
            height: 20px;
            margin: 0 8px 10px 8px;
            padding: 0;
        }
        /* Data table ------------------------------ */
        .purchase {
            width: 100%;
            margin: 0;
            padding: 35px 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_content {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .purchase_item {
            padding: 10px 0;
            color: #51545E;
            font-size: 15px;
            line-height: 18px;
        }
        .purchase_heading {
            padding-bottom: 8px;
            border-bottom: 1px solid #EAEAEC;
        }
        .purchase_heading p {
            margin: 0;
            color: #85878E;
            font-size: 12px;
        }
        .purchase_footer {
            padding-top: 15px;
            border-top: 1px solid #EAEAEC;
        }
        .purchase_total {
            margin: 0;
            text-align: right;
            font-weight: bold;
            color: #333333;
        }
        .purchase_total--label {
            padding: 0 15px 0 0;
        }
        body {
            background-color: #F2F4F6;
            color: #51545E;
        }
        p {
            color: #51545E;
        }
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F2F4F6;
        }
        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }
        .email-masthead_logo {
            width: 94px;
        }
        .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #A8AAAF;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }
        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
        }
        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .email-footer p {
            color: #A8AAAF;
        }
        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }
        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EAEAEC;
        }
        .content-cell {
            padding: 45px;
        }
        @media only screen and (max-width: 600px) {

            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }
        @media (prefers-color-scheme: dark) {
            body,
            .email-body,.email-body_inner,.email-content,.email-wrapper,.email-masthead,.email-footer {
                background-color: #333333 !important;
                color: #FFF !important;
            }
            p,ul,ol,blockquote, h1, h2,h3,span,
            .purchase_item {
                color: #FFF !important;
            }
            .attributes_content,
            .discount {
                background-color: #222 !important;
            }
            .email-masthead_name {
                text-shadow: none !important;
            }
        }
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
    </style>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="email-masthead">
                            <a href="https://techtalenttrack.com" class="f-fallback email-masthead_name">
                                '.$plant_full_name.'
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        <div class="f-fallback">
                                            <h1>Dear Sir/Mam,</h1>
                                            <p> Here With Find Attached Purchase Order From 
                                            <strong>'.$plant_full_name.'</strong> .</p>
                                            <table class="attributes" width="100%" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tr>
                                                    <td class="attributes_content">
                                                        <table width="100%" cellpadding="0" cellspacing="0"
                                                            role="presentation">
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO NO. : </strong> '.$po_no.'                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Value :</strong> '.$net_total.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="attributes_item">
                                                                    <span class="f-fallback">
                                                                        <strong>PO Date :</strong> '.$approve_date.'
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        
                                                </tr>
                                            </table>
                                    </td>
                                </tr>
                            </table>
                            <p>Thanks & Regards,
                                <br>Purchase Manager
                                <br>'.$plant_full_name.'
                                <br>'.$email.'
                            </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0"
                    role="presentation">
                    <tr>
                        <td class="content-cell" align="center">
                            <p class="f-fallback sub align-center">
                                GMP Software Pvt. Ltd. <br>
                                <img src="http://demo.gmpsoftwareindia.com/assets/logo1.png" width="150px" height="70px">
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>
</body>
</html>
            ';
        
        
        
        
        
        
        
   $sub = ' Purchase Order From  '.$plant_full_name.' ';
   
   
    $pdf_url ="https://cpplgmp.com/php/phpdevlop/gmptotal/purchase/po_print.php?type=downloadPOReport&id=$pid&token=$token&user_no=gmpdemo1&plant_id=$plant_id";
   
 //  $binary_content = file_get_contents($pdf_url);
   $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $pdf_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$binary_content = curl_exec($ch);
curl_close($ch);

             
                    try {
                        
                        $mail = new PHPMailer();
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'mail.cpplgmp.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'info@cpplgmp.com';
                         $mail->Password = 'Cppl@1979';
                        $mail->SMTPSecure = 'tls';  
                        $mail->Port = 587;  
                        //$mail->SMTPDebug = 2;
                        // Recipients
                        $mail->setFrom('info@cpplgmp.com', 'Paperless GMP');
                        $mail->addAddress($email);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = $sub;
                        $mail->Body    = $msg;
                        
                        $mail->ContentType = 'text/html';
                        $mail->AddStringAttachment($binary_content, "po.pdf", $encoding = 'base64', $type = 'application/pdf');
                        $mail->AddAttachment($pdf_url);
                        
                        $mail->send();
                        echo 'Email sent successfully!';
                    } catch (Exception $e) {
                        echo "Email sending failed. Error: {$mail->ErrorInfo}";
                    }
                            
        
        
        
    }
     else if($_GET["type"]=="getNewClients") {
    $output = Array();
        $sql = "SELECT * FROM NewClient";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
                echo json_encode($output);

    }
    else if ($_GET["type"] == "getLeadsQuatation") {
        $output = Array();
        $sql = "SELECT e.*, c.LglNm as company FROM enquiry e LEFT JOIN client c ON 
        e.client_code=c.client_code
        WHERE e.user_no='".$_GET["user_no"]."' AND e.client_code LIKE '%".$_GET["client_code"]."%'"; 
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $row["products"] = json_decode($row["products"]);
      
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
}

$conn->close();
?>