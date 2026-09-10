<?php 

    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);


require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = is_array($_POST) ? $_POST : array();
}

function medicapVendorTermsTable($conn) {
    foreach (array('vendorterms', 'vendorTerms', 'vendor_terms') as $name) {
        try {
            $r = $conn->query("SHOW COLUMNS FROM `".$name."`");
            if ($r) {
                return $name;
            }
        } catch (Throwable $e) {
        }
    }
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `vendorterms` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `plant_id` varchar(10) DEFAULT NULL,
          `term_heading` text DEFAULT NULL,
          `term` text DEFAULT NULL,
          `termValidDate` varchar(50) DEFAULT NULL,
          `vendor_no` varchar(50) DEFAULT NULL,
          `status` varchar(100) DEFAULT NULL,
          `entry_by` varchar(50) DEFAULT NULL,
          `entry_date` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
        return 'vendorterms';
    } catch (Throwable $e) {
        return '';
    }
}

$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    @file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveTerms") {
        
        $sql = "INSERT INTO `terms`(`plant_id`, `term_heading`, `term`, `entry_by`, `entry_date`, `status`) VALUES ('".$_GET["plant_id"]."', '".$input["term_heading"]."', '".$input["term"]."', '".$_GET["emp_id"]."', '$entry_date', '".$_GET["status"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    	
    } 
    else if ($_GET["type"] == "saveTermsVendor") {
        header('Content-Type: application/json; charset=utf-8');
        if (!is_array($input)) {
            $input = array();
        }
        $table = medicapVendorTermsTable($conn);
        if ($table === '') {
            echo json_encode(array('status' => 'vendor terms table missing'));
            exit;
        }
        $colSet = array();
        try {
            $colRes = $conn->query("SHOW COLUMNS FROM `".$table."`");
            if ($colRes) {
                while ($c = $colRes->fetch_assoc()) {
                    $colSet[strtolower($c['Field'])] = true;
                }
            }
        } catch (Throwable $e) {
            echo json_encode(array('status' => $e->getMessage()));
            exit;
        }
        $has = function ($name) use ($colSet) {
            return isset($colSet[strtolower($name)]);
        };
        $fields = array();
        $values = array();
        $add = function ($col, $val) use (&$fields, &$values, $has, $conn) {
            if (!$has($col)) {
                return;
            }
            $fields[] = '`'.$col.'`';
            $values[] = "'".$conn->real_escape_string((string) ($val ?? ''))."'";
        };
        $add('plant_id', isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
        $add('term_heading', isset($input['term_heading']) ? $input['term_heading'] : '');
        $add('term', isset($input['term']) ? $input['term'] : '');
        $add('termValidDate', isset($input['termValidDate']) ? $input['termValidDate'] : '');
        $add('vendor_no', isset($input['vendor_no']) ? $input['vendor_no'] : '');
        $add('status', isset($_GET['status']) ? $_GET['status'] : 'Vendor');
        $add('entry_by', isset($_GET['emp_id']) ? $_GET['emp_id'] : '');
        $add('entry_date', $entry_date);
        if (count($fields) === 0) {
            echo json_encode(array('status' => 'no matching vendorTerms columns'));
            exit;
        }
        $sql = 'INSERT INTO `'.$table.'` ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        try {
            if ($conn->query($sql)) {
                echo '{"status":"success"}';
            } else {
                echo json_encode(array('status' => $conn->error ? $conn->error : 'insert failed'));
            }
        } catch (Throwable $e) {
            echo json_encode(array('status' => $e->getMessage()));
        }
        exit;
    } 
    if ($_GET["type"] == "saveTermsApprisal") {
        $sql = "INSERT INTO  termsApprisal  ( term,entry_by,entry_date) VALUES ('".$input["term"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "saveQuatationTerms") {
        $sql = "INSERT INTO terms (term_heading, term,entry_by,entry_date,status) VALUES 
        ('".$input["term_heading"]."','".$input["term"]."','".$_GET["emp_id"]."','$entry_date','Quatation')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "saveQttermsForTerm") {
        $sql = "INSERT INTO quatation_terms (term_heading, term,entry_by,entry_date,status) VALUES 
        ('NA','".$input["term"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["status"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    
    else if ($_GET["type"] == "getQuatationTerms") {
        $output = array();
         $sql = "SELECT * FROM terms WHERE status = 'Quatation'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    
    else if ($_GET["type"] == "getqtaddterms") {
        $output = array();
          $sql = "SELECT * FROM quatation_terms WHERE status = '".$_GET["status"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTerms") {
        $output = array();
        $sql = "SELECT * FROM terms WHERE status = 'approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTermForPO") {
        $output = array();
        $sql = "SELECT * FROM terms WHERE status = 'PO' AND plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }
    else if ($_GET["type"] == "getTermForVendor") {
        $output = array();
        $table = medicapVendorTermsTable($conn);
        if ($table === '') {
            echo json_encode($output);
            exit;
        }
        $plantId = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
        $vendorNo = isset($_GET["vendor_no"]) ? $conn->real_escape_string($_GET["vendor_no"]) : '';
        $sql = "SELECT * FROM `".$table."` WHERE status = 'Vendor' AND plant_id = '".$plantId."'";
        if ($vendorNo !== '') {
            $sql .= " AND vendor_no = '".$vendorNo."'";
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        exit;
    }
 
     else if ($_GET["type"] == "saveAddterms") {
        $sql = "INSERT INTO additional_term (plant_id,additional_term,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["add_term"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "getAddterms") {
        $output = array();
     $sql = "SELECT * FROM additional_term  where status!='Deleted' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
        }
        }
        echo json_encode($output);
        
    }
     else if ($_GET["type"] == "saveList") {
        $sql = "INSERT INTO list_customer (plant_id,list_customer,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["list_customer"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "getList") {
        $output = array();
        $sql = "SELECT * FROM list_customer WHERE status!='Deleted' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_decode($output);
        
    }
        else if ($_GET["type"] == "deleteList") {
        $sql = "UPDATE list_customer SET status='Deleted' WHERE id='".$_GET["id"]."'";
       // $sql = "DELETE from terms WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "deleteTerm") {
        $sql = "UPDATE terms SET status = 'Deleted' WHERE id='".$_GET["id"]."'";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "deleteTermMeha") {
        $sql = "UPDATE  termsApprisal  SET status='Deleted' WHERE id='".$_GET["id"]."'";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "delQttermss") {
        $sql = "UPDATE quatation_terms SET status='Deleted' WHERE id='".$_GET["id"]."'";
         if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "deleteAddterm") {
        $sql = "UPDATE additional_term SET status='Deleted' WHERE id='".$_GET["id"]."'";
       // $sql = "DELETE from terms WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

}

$conn->close();
?>